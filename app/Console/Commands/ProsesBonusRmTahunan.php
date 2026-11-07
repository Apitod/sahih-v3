<?php

namespace App\Console\Commands;

use App\Models\BonusEntry;
use App\Models\Member;
use App\Models\PointEntry;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Bonus Royal Manager (RM) tahunan — BUSINESS_RULES §5.
 *
 * RM = Manager yang punya minimal satu Manager langsung di bawahnya.
 * RM bukan peringkat, cuma sebutan.
 *
 * Nilai: Rp 2.000 per poin PL yang diterima Manager langsung di bawahnya,
 * diakumulasi setahun, dibayar sekali setahun.
 *
 * Compression [INFERRED]: rantai berhenti antar RM. Yang dihitung hanya PL
 * Manager LANGSUNG di bawahnya — bukan cucu — jadi RM di atas tidak pernah
 * mengambil bagian dari jaringan di bawah RM lain. Owner belum mengonfirmasi
 * tafsir ini; kalau berubah, ubah query managerIds di bawah.
 *
 * Idempoten per (member, tahun) lewat available_at = 1 Januari tahun itu.
 */
class ProsesBonusRmTahunan extends Command
{
    protected $signature = 'sahih:proses-bonus-rm-tahunan {--tahun= : Tahun YYYY (default tahun lalu)}';

    protected $description = 'Hitung bonus Royal Manager setahun dari PL Manager langsung di bawahnya';

    public function handle(): int
    {
        $tahun = (string) ($this->option('tahun') ?: now()->subYear()->year);

        if (! preg_match('/^\d{4}$/', $tahun)) {
            $this->error("Format --tahun harus YYYY (diberikan: {$tahun}).");

            return self::FAILURE;
        }

        $awal = Carbon::createFromDate((int) $tahun, 1, 1)->startOfYear();
        $akhir = $awal->copy()->endOfYear();
        $nilai = (int) config('sahih.royal_manager_value_per_point', 2000);

        $plPerMember = PointEntry::where('kind', 'pl')
            ->whereBetween('created_at', [$awal, $akhir])
            ->groupBy('member_id')
            ->selectRaw('member_id, SUM(delta) as total')
            ->pluck('total', 'member_id');

        $calonRm = Member::where('is_manager', true)
            ->whereHas('downlines', fn ($q) => $q->where('is_manager', true))
            ->get(['id']);

        if ($calonRm->isEmpty()) {
            $this->info("Tidak ada Royal Manager pada {$tahun}.");

            return self::SUCCESS;
        }

        $diproses = 0;
        $rupiah = 0;

        DB::transaction(function () use ($calonRm, $plPerMember, $nilai, $awal, &$diproses, &$rupiah) {
            foreach ($calonRm as $rm) {
                $managerIds = Member::where('sponsor_id', $rm->id)->where('is_manager', true)->pluck('id');

                $totalPl = 0;
                foreach ($managerIds as $id) {
                    $totalPl += (int) ($plPerMember[$id] ?? 0);
                }

                if ($totalPl <= 0) {
                    continue;
                }

                $ada = BonusEntry::where('member_id', $rm->id)->where('kind', 'rm')
                    ->where('available_at', $awal)->exists();

                if ($ada) {
                    continue;
                }

                $amount = $totalPl * $nilai;

                try {
                    BonusEntry::create([
                        'member_id' => $rm->id,
                        'kind' => 'rm',
                        'amount' => $amount,
                        'status' => 'available',
                        'available_at' => $awal,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    // Proses lain sudah menulis tahun ini lebih dulu; biarkan dia yang menang.
                    continue;
                }

                $diproses++;
                $rupiah += $amount;
            }
        });

        $this->info("Tahun {$tahun}: {$diproses} Royal Manager diproses dari {$calonRm->count()} kandidat.");
        $this->info('Total bonus RM: Rp ' . number_format($rupiah, 0, ',', '.'));

        return self::SUCCESS;
    }
}
