<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Pin;
use App\Models\PointEntry;
use App\Models\Transaction;
use App\Services\MilestoneEvaluator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

/**
 * Skenario uji "Royal Manager": jaringan bertingkat lengkap dengan satu kasus
 * menonjol — agen di bawahnya sudah sampai Mobil (3.500 poin), sementara
 * Royal Manager-nya baru Motor (500 poin).
 *
 * Struktur:
 *   royalmanager (Manager, 520 PR → Motor)   ← RM karena punya 2 Manager langsung
 *     ├─ mgr1 (Manager)          PL 120 bulan lalu → leadership + bagian bonus RM
 *     │   ├─ dist11 (Distributor, 120 PR → Handphone)
 *     │   │   ├─ agen111 (Agent, 3.500 PR → Mobil)  ← lebih tinggi dari RM-nya
 *     │   │   ├─ agen112 (Agent, 175 PR → Handphone)
 *     │   │   ├─ agen113 (Agent, 34 PR → Voucher)
 *     │   │   └─ agen114 (Agent, 4 PR → Produk)
 *     │   └─ dist12 (Distributor, 80 PR → Voucher)
 *     │       ├─ agen121 (Agent, 40 PR → Voucher)
 *     │       └─ agen122 (Agent, 12 PR → Produk)
 *     ├─ mgr2 (Manager)          PL 80 bulan lalu
 *     │   └─ dist21 (Distributor, 500 PR → Motor)
 *     │       ├─ agen211 (Agent, 210 PR → Handphone)
 *     │       └─ agen212 (Agent, 60 PR → Voucher)
 *     └─ dist13 (Distributor, 20 PR → Voucher)
 *         └─ agen131 (Agent, 8 PR → Produk)
 *
 *   managerbiasa (Manager, 600 PR → Motor) — Manager TANPA Manager di bawah = bukan RM
 *   resellerbeku (Reseller, ditangguhkan) — uji badge "Beku" di pohon
 *
 * Jalankan: php artisan db:seed --class=SkenarioRoyalManagerSeeder
 * Idempoten: kalau `royalmanager` sudah ada, seeder berhenti tanpa menulis apa pun.
 */
class SkenarioRoyalManagerSeeder extends Seeder
{
    private const SANDI = 'Sahih2026';

    private int $pinUrut = 0;

    public function run(): void
    {
        if (Member::where('username', 'royalmanager')->exists()) {
            $this->command->warn('royalmanager sudah ada. Lewati (hapus dulu kalau mau ulang).');

            return;
        }

        $bulanLalu = now()->subMonthNoOverflow()->startOfMonth();

        // ── PUNCAK: Royal Manager ────────────────────────────────────────────
        $rm = $this->buat('royalmanager', 'Royal Manager Uji', 'manager', null, 520);

        $mgr1 = $this->buat('mgr1', 'Manager Satu', 'manager', $rm, 0);
        $mgr2 = $this->buat('mgr2', 'Manager Dua', 'manager', $rm, 0);

        // ── CABANG mgr1 ──────────────────────────────────────────────────────
        $dist11 = $this->buat('dist11', 'Distributor 11', 'distributor', $mgr1, 120);
        $this->buat('agen111', 'Agen 111 (Mobil)', 'agent', $dist11, 3500);
        $this->buat('agen112', 'Agen 112', 'agent', $dist11, 175);
        $this->buat('agen113', 'Agen 113', 'agent', $dist11, 34);
        $this->buat('agen114', 'Agen 114', 'agent', $dist11, 4);

        $dist12 = $this->buat('dist12', 'Distributor 12', 'distributor', $mgr1, 80);
        $this->buat('agen121', 'Agen 121', 'agent', $dist12, 40);
        $this->buat('agen122', 'Agen 122', 'agent', $dist12, 12);

        // ── CABANG mgr2 ──────────────────────────────────────────────────────
        $dist21 = $this->buat('dist21', 'Distributor 21', 'distributor', $mgr2, 500);
        $this->buat('agen211', 'Agen 211', 'agent', $dist21, 210);
        $this->buat('agen212', 'Agen 212', 'agent', $dist21, 60);

        // ── CABANG LANGSUNG RM (distributor, bukan manager) ──────────────────
        $dist13 = $this->buat('dist13', 'Distributor 13', 'distributor', $rm, 20);
        $this->buat('agen131', 'Agen 131', 'agent', $dist13, 8);

        // ── PEMBANDING + KASUS TEPI ──────────────────────────────────────────
        $this->buat('managerbiasa', 'Manager Tanpa Manager Bawah', 'manager', null, 600);

        $beku = $this->buat('resellerbeku', 'Reseller Ditangguhkan', 'reseller', $dist13, 6);
        $beku->update(['status' => 'suspended']);

        // ── POIN PL bulan lalu + RO: syarat bonus leadership ─────────────────
        // PL non-Manager hangus, jadi PL hanya diberikan ke Manager.
        $this->tambahPoin($mgr1, 'pl', 120, $bulanLalu, 'PL jaringan bulan lalu');
        $this->tambahPoin($mgr2, 'pl', 80, $bulanLalu, 'PL jaringan bulan lalu');
        $this->tambahPoin($rm, 'pl', 50, $bulanLalu, 'PL jaringan bulan lalu');

        foreach ([$rm, $mgr1, $mgr2, Member::where('username', 'managerbiasa')->first()] as $m) {
            $this->ro($m);
        }

        // ── Hak hadiah poin ─────────────────────────────────────────────────
        foreach (Member::all() as $m) {
            app(MilestoneEvaluator::class)->evaluate($m);
        }

        // ── Bonus: leadership bulan lalu, lalu RM tahun berjalan ────────────
        Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => $bulanLalu->format('Y-m')]);
        $this->command->line(trim(Artisan::output()));

        Artisan::call('sahih:proses-bonus-rm-tahunan', ['--tahun' => (string) now()->year]);
        $this->command->line(trim(Artisan::output()));

        $this->ringkasan();
    }

    private function buat(string $username, string $name, string $rank, ?Member $sponsor, int $pr): Member
    {
        $member = Member::create([
            'username' => $username,
            'name' => $name,
            'phone' => '08' . str_pad((string) (10000000 + $this->pinUrut++), 10, '0', STR_PAD_LEFT),
            'password' => Hash::make(self::SANDI),
            'sponsor_id' => $sponsor?->id,
            'rank' => $rank,
            'is_manager' => $rank === 'manager',
            'manager_since' => $rank === 'manager' ? now()->subMonths(4) : null,
            'status' => 'active',
            'joined_at' => now()->subMonths(5),
        ]);

        if ($pr > 0) {
            $this->tambahPoin($member, 'pr', $pr, now()->subDays(10), 'RO pribadi');
            $this->tambahPoin($member, 'pa', (int) ($pr * 0.2), now()->subDays(10), 'Pendaftaran');
        }

        return $member;
    }

    /** PointEntry tidak punya timestamps & created_at tidak fillable → set lewat query. */
    private function tambahPoin(Member $member, string $kind, int $delta, $at, string $note): void
    {
        $entry = PointEntry::create([
            'member_id' => $member->id,
            'kind' => $kind,
            'delta' => $delta,
            'source_type' => 'scenario_seeder',
            'note' => $note,
        ]);

        PointEntry::whereKey($entry->getKey())->update(['created_at' => $at]);
        $member->increment("point_{$kind}", $delta);
    }

    private function ro(Member $member): void
    {
        $pin = Pin::create([
            'code' => 'PIN-RM-' . str_pad((string) ++$this->pinUrut, 3, '0', STR_PAD_LEFT),
            'type' => 'ro',
            'points' => 10,
            'price' => 300000,
            'target_rank' => $member->rank,
            'status' => 'used',
        ]);

        Transaction::create([
            'code' => 'TRX-RM-' . str_pad((string) $this->pinUrut, 3, '0', STR_PAD_LEFT),
            'type' => 'ro',
            'member_id' => $member->id,
            'pin_id' => $pin->id,
            'points' => 10,
            'amount' => 300000,
            'sponsor_bonus_total' => 0,
            'posted_at' => now()->subDays(3),
        ]);
    }

    public function ringkasan(): void
    {
        $baris = Member::orderBy('id')->get(['id', 'username', 'rank', 'is_manager', 'status', 'point_pr'])
            ->map(function (Member $m) {
                $milestone = $m->milestoneClaims()->with('reward')
                    ->where('cycle', 1)->get()
                    ->map(fn ($c) => $c->reward?->name)
                    ->filter()->implode(', ') ?: '—';

                $bonus = $m->bonusEntries()->selectRaw('kind, SUM(amount) as total')
                    ->groupBy('kind')->pluck('total', 'kind')
                    ->map(fn ($v, $k) => $k . ' Rp' . number_format((int) $v, 0, ',', '.'))
                    ->implode(' | ') ?: '—';

                return [
                    $m->username,
                    $m->is_manager ? 'Manager (RM?)' : ucfirst($m->rank),
                    $m->status,
                    number_format($m->point_pr, 0, ',', '.'),
                    $milestone,
                    $bonus,
                ];
            });

        $this->command->newLine();
        $this->command->table(['Username', 'Peringkat', 'Status', 'PR', 'Hadiah hak', 'Bonus'], $baris->all());
        $this->command->info('Sandi semua akun: lihat berkas /home/ubuntu/akun-uji-sahih.md');
    }
}
