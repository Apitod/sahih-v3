<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MilestoneClaim;
use App\Models\PointEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Skenario kompleks: satu Manager puncak dengan jaringan 10 Distributor langsung.
 *
 * Struktur:
 *   topmanager (Manager) — 500 PR total (cukup sampai Motor saja)
 *     ├─ bigdist1 (Distributor) — sponsori 15 Distributor, qualified Motor (500 PR dari RO downline)
 *     │    ├─ bigdist1_sub1  .. bigdist1_sub15 (Distributor, masing-masing dengan RO)
 *     ├─ bigdist2 (Distributor) — sponsori 20 Distributor, qualified Mobil (3500 PR dari RO downline)
 *     │    ├─ bigdist2_sub1  .. bigdist2_sub20 (Distributor, masing-masing dengan RO)
 *     ├─ dist3 .. dist10 (Distributor, variasi PR)
 *
 * Run: php artisan db:seed --class=ScenarioManagerSeeder
 */
class ScenarioManagerSeeder extends Seeder
{
    public function run(): void
    {
        if (Member::where('username', 'topmanager')->exists()) {
            $this->command->warn('topmanager sudah ada. Lewati.');
            return;
        }

        $this->command->info('🌳 Membangun jaringan skenario Manager...');

        // ── MANAGER PUNCAK ──────────────────────────────────────────────────
        // PR milik sendiri: cukup untuk motor (500) tapi belum cycle ke 3500.
        // PR dari sponsor langsung akan bertambah saat sub-dist kita buat,
        // jadi kita kontrol PR pribadi saja lewat point_entries, lalu evaluasi
        // milestone SETELAH semua downline dibuat.
        $top = $this->buat('topmanager', 'Manager Puncak', 'topmanager123', null, 'manager');

        // ── BIGDIST1: 15 Distributor di bawahnya, qualified Motor ───────────
        // Dist ini butuh 500 PR dari akumulasi RO downline-nya.
        // Tiap sub-dist RO 34 poin → 15 × 34 = 510 PR ke bigdist1 (lebih dari 500).
        $bd1 = $this->buat('bigdist1', 'Big Distributor 1', 'bigdist1pw', $top, 'distributor');

        for ($i = 1; $i <= 15; $i++) {
            $sub = $this->buat("bd1_sub{$i}", "BD1 Sub {$i}", 'sub123pass', $bd1, 'distributor');
            // PR dari RO: masuk sebagai PR sub, dan PR sponsor (bd1)
            $this->tambahPoin($sub, 'pr', 34);  // poin RO milik sub sendiri
            $this->tambahPoin($sub, 'pa', 34);  // PA dari RO (RO kasih PA ke diri sendiri)
            // PR ke bd1 sebagai sponsor: dicatat terpisah (simulasi sponsor mendapat PR dari downline)
            $this->tambahPoinKeMember($bd1, 'pr', 34, "RO dari {$sub->username}");
        }
        // PR pribadi bd1 sendiri (sedikit)
        $this->tambahPoin($bd1, 'pr', 10);

        // ── BIGDIST2: 20 Distributor di bawahnya, qualified Mobil ───────────
        // Butuh 3500 PR dari RO downline-nya.
        // 20 sub × 175 poin RO = 3500 tepat.
        $bd2 = $this->buat('bigdist2', 'Big Distributor 2', 'bigdist2pw', $top, 'distributor');

        for ($i = 1; $i <= 20; $i++) {
            $sub = $this->buat("bd2_sub{$i}", "BD2 Sub {$i}", 'sub123pass', $bd2, 'distributor');
            $this->tambahPoin($sub, 'pr', 175);
            $this->tambahPoin($sub, 'pa', 175);
            $this->tambahPoinKeMember($bd2, 'pr', 175, "RO dari {$sub->username}");
        }
        // Tanpa PR pribadi: total tepat 3500 supaya cycle 2 belum mulai.

        // ── DIST3..10: 8 distributor langsung bawah Manager, variasi ────────
        $varianPR = [80, 45, 120, 30, 200, 60, 15, 90];
        $varianDownline = [3, 0, 5, 1, 7, 2, 0, 4];

        for ($i = 3; $i <= 10; $i++) {
            $pr = $varianPR[$i - 3];
            $jumlahDown = $varianDownline[$i - 3];

            $dist = $this->buat("dist{$i}", "Distributor {$i}", "dist{$i}pass", $top, 'distributor');
            $this->tambahPoin($dist, 'pr', $pr);
            $this->tambahPoin($dist, 'pa', (int)($pr * 0.4));

            for ($j = 1; $j <= $jumlahDown; $j++) {
                $rsel = $this->buat("dist{$i}_rs{$j}", "Reseller {$i}-{$j}", 'rs123pass', $dist, 'reseller');
                $poinRs = rand(2, 8);
                $this->tambahPoin($rsel, 'pr', $poinRs);
                $this->tambahPoinKeMember($dist, 'pr', $poinRs, "dari reseller {$rsel->username}");
            }

            // Evaluasi milestone per dist
            $this->evaluasiMilestone($dist->fresh());
        }

        // ── HITUNG PR TOTAL topmanager ───────────────────────────────────────
        // PR sponsori langsung: tiap Distributor langsung (10 orang) kasih PR ke manager.
        // Tapi aturan: sponsor hanya dapat PR dari transaksi downline LANGSUNG.
        // Simulasi: manager dapat PR dari 10 dist langsung (total poin mereka).
        // Kita set PR manager pribadi = 500 (Motor), lalu evaluate.
        // Jangan tambahkan PR dari seluruh jaringan (itu bukan PR, itu PL untuk Manager).
        $this->tambahPoin($top, 'pr', 500);
        $this->tambahPoin($top, 'pa', 25); // PA dari beberapa join
        $this->tambahPoin($top, 'pl', 400); // PL dari jaringan non-langsung

        // ── EVALUASI MILESTONE ───────────────────────────────────────────────
        $this->evaluasiMilestone($bd1->fresh());
        $this->evaluasiMilestone($bd2->fresh());
        $this->evaluasiMilestone($top->fresh());

        foreach (range(1, 15) as $i) {
            $this->evaluasiMilestone(Member::where('username', "bd1_sub{$i}")->first());
        }
        foreach (range(1, 20) as $i) {
            $this->evaluasiMilestone(Member::where('username', "bd2_sub{$i}")->first());
        }

        // ── RINGKASAN ────────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->table(
            ['Username', 'Password', 'Peringkat', 'PR', 'Milestone'],
            [
                ['topmanager',  'topmanager123',  'Manager',      '500',    'Motor (cycle 1)'],
                ['bigdist1',    'bigdist1pw',      'Distributor',  '520+',   'Motor (cycle 1)'],
                ['bigdist2',    'bigdist2pw',      'Distributor',  '3510+',  'Mobil (cycle 1) = semua'],
                ['bd1_sub1-15', 'sub123pass',      'Distributor',  '34',     'Produk'],
                ['bd2_sub1-20', 'sub123pass',      'Distributor',  '175',    'Produk+Voucher+Handphone'],
                ['dist3',       'dist3pass',        'Distributor',  '80+',    'Produk+Voucher'],
                ['dist4',       'dist4pass',        'Distributor',  '45',     'Produk+Voucher'],
                ['dist5',       'dist5pass',        'Distributor',  '120+',   'Produk+Voucher+Handphone'],
                ['dist6',       'dist6pass',        'Distributor',  '30',     'Produk+Voucher'],
                ['dist7',       'dist7pass',        'Distributor',  '200+',   'Produk+Voucher+Handphone'],
                ['dist8',       'dist8pass',        'Distributor',  '60',     'Produk+Voucher'],
                ['dist9',       'dist9pass',        'Distributor',  '15',     'Produk'],
                ['dist10',      'dist10pass',       'Distributor',  '90+',    'Produk+Voucher'],
            ]
        );

        $total = Member::count();
        $this->command->info("✅ Selesai. Total anggota: {$total}.");
        $this->command->info("💡 Login member di /login | Admin: admin@sahih.local / admin123");
    }

    private function buat(string $username, string $name, string $password, ?Member $sponsor, string $rank): Member
    {
        return Member::create([
            'username'      => $username,
            'name'          => $name,
            'phone'         => '08' . str_pad((string) rand(10000000, 99999999), 10, '0', STR_PAD_LEFT),
            'password'      => Hash::make($password),
            'sponsor_id'    => $sponsor?->id,
            'rank'          => $rank,
            'is_manager'    => $rank === 'manager',
            'manager_since' => $rank === 'manager' ? now()->subMonths(3) : null,
            'status'        => 'active',
            'joined_at'     => now()->subDays(rand(30, 180)),
        ]);
    }

    private function tambahPoin(Member $member, string $kind, int $amount): void
    {
        if ($amount <= 0) return;

        PointEntry::create([
            'member_id'   => $member->id,
            'kind'        => $kind,
            'delta'       => $amount,
            'source_type' => 'scenario_seeder',
            'note'        => "Skenario: {$kind} +{$amount}",
        ]);

        $col = "point_{$kind}";
        $member->increment($col, $amount);
    }

    private function tambahPoinKeMember(Member $member, string $kind, int $amount, string $note): void
    {
        if ($amount <= 0) return;

        PointEntry::create([
            'member_id'   => $member->id,
            'kind'        => $kind,
            'delta'       => $amount,
            'source_type' => 'scenario_seeder',
            'note'        => $note,
        ]);

        $member->increment("point_{$kind}", $amount);
    }

    private function evaluasiMilestone(Member $member): void
    {
        app(\App\Services\MilestoneEvaluator::class)->evaluate($member);
    }
}
