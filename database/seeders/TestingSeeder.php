<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MilestoneClaim;
use App\Models\PointEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder untuk testing dengan berbagai use case:
 * - Anggota dengan banyak downline (jaringan besar)
 * - Anggota mendekati milestone (sisa 1 poin sebelum putaran ulang)
 * - Anggota di berbagai peringkat
 * - Anggota dengan hadiah eligible
 *
 * Run: php artisan db:seed --class=TestingSeeder
 */
class TestingSeeder extends Seeder
{
    public function run(): void
    {
        if (Member::where('username', 'leader')->exists()) {
            $this->command->warn('Data testing sudah ada (username "leader"). Lewati.');
            return;
        }

        $this->command->info('🌱 Membuat akun testing...');

        // Use case 1: Leader dengan jaringan besar (10 tingkat)
        $leader = $this->createMember('leader', 'Leader Network', 'leader123', null, 'manager');
        $this->addPoin($leader, 'pr', 3500); // 1 putaran penuh
        $this->addPoin($leader, 'pa', 25);
        $this->evaluateMilestones($leader);
        $this->buildDeepNetwork($leader, 10);

        // Use case 2: Anggota 1 poin sebelum milestone 3500 (akan cycle ulang)
        $almost = $this->createMember('almost', 'Almost Cycle', 'almost123', null, 'distributor');
        $this->addPoin($almost, 'pr', 3499); // sisa 1 poin lagi → putaran ke-2
        $this->evaluateMilestones($almost);

        // Use case 3: Anggota baru masuk 5 poin (hadiah pertama eligible)
        $newbie = $this->createMember('newbie', 'Newbie Member', 'newbie123', null, 'reseller');
        $this->addPoin($newbie, 'pr', 5);
        $this->evaluateMilestones($newbie);

        // Use case 4: Distributor dengan 3 sponsor langsung + downline bercabang
        $distributor = $this->createMember('dist01', 'Distributor Aktif', 'dist01pass', null, 'distributor');
        $this->addPoin($distributor, 'pr', 150);
        $this->addPoin($distributor, 'pa', 8);
        $this->evaluateMilestones($distributor);

        $branch1 = $this->createMember('dist01_a', 'Branch A', 'branchA123', $distributor, 'agent');
        $this->addPoin($branch1, 'pr', 20);
        $this->buildWideNetwork($branch1, 5, 2); // 5 anak, kedalaman 2

        $branch2 = $this->createMember('dist01_b', 'Branch B', 'branchB123', $distributor, 'agent');
        $this->addPoin($branch2, 'pr', 30);
        $this->buildWideNetwork($branch2, 3, 3);

        $branch3 = $this->createMember('dist01_c', 'Branch C', 'branchC123', $distributor, 'reseller');
        $this->addPoin($branch3, 'pr', 10);

        // Use case 5: Manager dengan PA tinggi
        $manager = $this->createMember('manager01', 'Manager Pro', 'manager123', null, 'manager');
        $this->addPoin($manager, 'pr', 500);
        $this->addPoin($manager, 'pa', 50);
        $this->addPoin($manager, 'pl', 120);
        $this->evaluateMilestones($manager);

        // Use case 6: Anggota dengan hadiah requested (sudah ajukan klaim)
        $claimed = $this->createMember('claimed', 'Has Claimed', 'claimed123', null, 'agent');
        $this->addPoin($claimed, 'pr', 80);
        $this->evaluateMilestones($claimed);
        MilestoneClaim::where('member_id', $claimed->id)->where('status', 'eligible')->first()?->update([
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        // Use case 7: Anggota suspended
        $suspended = $this->createMember('suspended', 'Suspended User', 'suspended123', null, 'reseller', 'suspended');
        $this->addPoin($suspended, 'pr', 2);

        // Use case 8: Agent dengan PR pas ambang (3, 6, 20, 75)
        $exact3 = $this->createMember('exact3', 'Exact 3 PR', 'exact3pass', null, 'agent');
        $this->addPoin($exact3, 'pr', 3);

        $exact6 = $this->createMember('exact6', 'Exact 6 PR', 'exact6pass', null, 'distributor');
        $this->addPoin($exact6, 'pr', 6);

        $exact20 = $this->createMember('exact20', 'Exact 20 PR', 'exact20pass', null, 'agent');
        $this->addPoin($exact20, 'pr', 20);
        $this->evaluateMilestones($exact20);

        $exact75 = $this->createMember('exact75', 'Exact 75 PR', 'exact75pass', null, 'distributor');
        $this->addPoin($exact75, 'pr', 75);
        $this->evaluateMilestones($exact75);

        // Use case 9: Anggota dengan 2x cycle (7000+ poin)
        $veteran = $this->createMember('veteran', 'Veteran 2 Cycle', 'veteran123', null, 'manager');
        $this->addPoin($veteran, 'pr', 7200); // 2 putaran + 200
        $this->addPoin($veteran, 'pa', 30);
        $this->evaluateMilestones($veteran);

        // Use case 10: Wide network (1 parent, 15 direct downline)
        $wide = $this->createMember('wide', 'Wide Network', 'wide123', null, 'distributor');
        $this->addPoin($wide, 'pr', 50);
        $this->buildWideNetwork($wide, 15, 1);

        $this->command->newLine();
        $this->printCredentials();
    }

    private function createMember(string $username, string $name, string $password, ?Member $sponsor = null, string $rank = 'reseller', string $status = 'active'): Member
    {
        return Member::create([
            'username' => $username,
            'name' => $name,
            'phone' => '08' . str_pad((string) rand(100000000, 999999999), 10, '0'),
            'password' => Hash::make($password),
            'sponsor_id' => $sponsor?->id,
            'rank' => $rank,
            'is_manager' => $rank === 'manager',
            'manager_since' => $rank === 'manager' ? now() : null,
            'status' => $status,
            'joined_at' => now()->subDays(rand(1, 60)),
        ]);
    }

    private function addPoin(Member $member, string $kind, int $amount): void
    {
        PointEntry::create([
            'member_id' => $member->id,
            'kind' => $kind,
            'delta' => $amount,
            'source_type' => 'seeder_testing',
            'note' => "Testing data: {$kind} +{$amount}",
        ]);

        $member->update(["point_{$kind}" => $amount]);
    }

    private function buildDeepNetwork(Member $parent, int $depth, int $currentDepth = 1): void
    {
        if ($currentDepth > $depth) return;

        $child = $this->createMember(
            "{$parent->username}_d{$currentDepth}",
            "Downline L{$currentDepth}",
            'test123',
            $parent,
            $currentDepth <= 3 ? 'agent' : 'reseller'
        );

        $this->addPoin($child, 'pr', max(1, 10 - $currentDepth));

        $this->buildDeepNetwork($child, $depth, $currentDepth + 1);
    }

    private function buildWideNetwork(Member $parent, int $count, int $depth, int $currentDepth = 1): void
    {
        if ($currentDepth > $depth) return;

        for ($i = 1; $i <= $count; $i++) {
            $child = $this->createMember(
                "{$parent->username}_w{$i}d{$currentDepth}",
                "Wide {$i} L{$currentDepth}",
                'test123',
                $parent,
                'reseller'
            );

            $this->addPoin($child, 'pr', rand(1, 5));

            if ($currentDepth < $depth && $i <= 2) {
                $this->buildWideNetwork($child, max(1, (int)($count / 2)), $depth, $currentDepth + 1);
            }
        }
    }

    private function evaluateMilestones(Member $member): void
    {
        app(\App\Services\MilestoneEvaluator::class)->evaluate($member);
    }

    private function printCredentials(): void
    {
        $accounts = [
            ['username' => 'leader', 'password' => 'leader123', 'use_case' => 'Jaringan besar 10 tingkat, Manager, 1 cycle (3500 PR)'],
            ['username' => 'almost', 'password' => 'almost123', 'use_case' => '3499 PR — sisa 1 poin sebelum cycle ke-2'],
            ['username' => 'newbie', 'password' => 'newbie123', 'use_case' => 'Baru masuk, 5 PR, hadiah pertama (Produk) eligible'],
            ['username' => 'dist01', 'password' => 'dist01pass', 'use_case' => 'Distributor dengan 3 branch bercabang, 150 PR'],
            ['username' => 'manager01', 'password' => 'manager123', 'use_case' => 'Manager dengan PA 50, PR 500, PL 120'],
            ['username' => 'claimed', 'password' => 'claimed123', 'use_case' => '80 PR, punya hadiah yang sudah di-request'],
            ['username' => 'suspended', 'password' => 'suspended123', 'use_case' => 'Akun dibekukan (status suspended)'],
            ['username' => 'exact3', 'password' => 'exact3pass', 'use_case' => 'Pas 3 PR (ambang Agent)'],
            ['username' => 'exact6', 'password' => 'exact6pass', 'use_case' => 'Pas 6 PR (ambang Distributor)'],
            ['username' => 'exact20', 'password' => 'exact20pass', 'use_case' => 'Pas 20 PR (ambang milestone Voucher)'],
            ['username' => 'exact75', 'password' => 'exact75pass', 'use_case' => 'Pas 75 PR (ambang milestone Handphone)'],
            ['username' => 'veteran', 'password' => 'veteran123', 'use_case' => '7200 PR — 2 cycle penuh + 200, Manager'],
            ['username' => 'wide', 'password' => 'wide123', 'use_case' => '15 direct downline (jaringan lebar)'],
        ];

        $this->command->table(
            ['Username', 'Password', 'Use Case'],
            array_map(fn($a) => [$a['username'], $a['password'], $a['use_case']], $accounts)
        );

        $this->command->newLine();
        $this->command->info('✅ Testing seeder selesai. Login di /login dengan kredensial di atas.');
        $this->command->info('💡 Admin: admin@sahih.local / admin123 (lihat ~/.sahih-admin-credentials)');
    }
}
