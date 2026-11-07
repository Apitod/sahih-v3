<?php

namespace App\Console\Commands;

use App\Models\BonusEntry;
use App\Models\Member;
use App\Models\PointEntry;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Bonus leadership bulanan: PL Manager dikonversi ke rupiah sekali sebulan.
 *
 * Aturan (BUSINESS_RULES §4):
 * - Rp 40.000 per poin PL (config leadership_value_per_point).
 * - Hanya Manager yang punya PL; PL non-Manager hangus.
 * - Syarat: pernah RO minimal sekali (DECISIONS #67).
 * - Belum ada tanggal tutup buku dari Owner → command manual, idempoten
 *   per (member, bulan) memakai available_at = tanggal 1 bulan itu.
 */
class ProsesBonusBulanan extends Command
{
    protected $signature = 'sahih:proses-bonus-bulanan
        {--bulan= : Periode YYYY-MM (default bulan lalu)}
        {--hitung-ulang : Sesuaikan bonus periode ini dengan poin PL terbaru (selisihnya saja)}';

    protected $description = 'Konversi poin PL Manager jadi bonus leadership untuk satu bulan';

    public function handle(): int
    {
        $bulan = $this->option('bulan') ?: now()->subMonthNoOverflow()->format('Y-m');

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $bulan)) {
            $this->error("Format --bulan harus YYYY-MM (diberikan: {$bulan}).");

            return self::FAILURE;
        }

        $awal = Carbon::createFromFormat('Y-m-d H:i:s', $bulan . '-01 00:00:00')->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();
        $nilai = (int) config('sahih.leadership_value_per_point', 40000);

        $totalPl = PointEntry::where('kind', 'pl')
            ->whereBetween('created_at', [$awal, $akhir])
            ->groupBy('member_id')
            ->selectRaw('member_id, SUM(delta) as total')
            ->pluck('total', 'member_id');

        if ($totalPl->isEmpty()) {
            $this->info("Tidak ada poin PL pada {$bulan}.");

            return self::SUCCESS;
        }

        $managerIds = Member::whereIn('id', $totalPl->keys())->where('is_manager', true)->pluck('id');
        $sudahRo = Transaction::where('type', 'ro')->whereIn('member_id', $managerIds)
            ->distinct()->pluck('member_id');

        $hitungUlang = (bool) $this->option('hitung-ulang');
        $diproses = 0;
        $dilewati = 0;
        $rupiah = 0;

        DB::transaction(function () use ($totalPl, $managerIds, $sudahRo, $nilai, $awal, $hitungUlang, &$diproses, &$dilewati, &$rupiah) {
            foreach ($managerIds as $id) {
                $pl = (int) $totalPl[$id];

                if ($pl <= 0 || ! $sudahRo->contains($id)) {
                    $dilewati++;

                    continue;
                }

                $ada = BonusEntry::where('member_id', $id)->where('kind', 'leadership')
                    ->whereDate('available_at', $awal)->first();

                if ($ada) {
                    // Tanpa --hitung-ulang, periode yang sudah diproses dilewati.
                    if (! $hitungUlang) {
                        $dilewati++;

                        continue;
                    }

                    // Dengan --hitung-ulang: hanya selisih yang ditambahkan, jadi poin yang
                    // masuk setelah proses pertama tetap terbayar tanpa membayar dua kali.
                    $seharusnya = $pl * $nilai;
                    $selisih = $seharusnya - (int) $ada->amount;

                    if ($selisih <= 0) {
                        $dilewati++;

                        continue;
                    }

                    // Nominal hanya bisa disesuaikan selagi bonus belum disentuh anggota.
                    // Yang sudah withdrawn/void dibiarkan; admin perlu tindakan manual.
                    if ($ada->status === 'available') {
                        $ada->update(['amount' => $seharusnya]);
                    } else {
                        $this->warn("  {$id}: bonus periode ini sudah {$ada->status}, selisih Rp" . number_format($selisih, 0, ',', '.') . ' tidak ditambahkan otomatis.');

                        continue;
                    }

                    $diproses++;
                    $rupiah += $selisih;

                    continue;
                }

                $amount = $pl * $nilai;

                try {
                    BonusEntry::create([
                        'member_id' => $id,
                        'kind' => 'leadership',
                        'amount' => $amount,
                        'status' => 'available',
                        'available_at' => $awal,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    // Proses lain sudah menulis periode ini lebih dulu; biarkan dia yang menang.
                    $dilewati++;

                    continue;
                }

                $diproses++;
                $rupiah += $amount;
            }
        });

        $this->info("Periode {$bulan}: {$diproses} Manager diproses, {$dilewati} dilewati.");
        $this->info('Total bonus leadership: Rp ' . number_format($rupiah, 0, ',', '.'));

        return self::SUCCESS;
    }
}
