<?php

use App\Models\Admin;
use App\Models\BonusEntry;
use App\Models\Member;
use App\Models\Transaction;

beforeEach(function () {
    $this->admin = Admin::create([
        'name' => 'Admin Test',
        'email' => 'admin@test.local',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'is_active' => true,
    ]);
});

test('admin bisa lihat daftar transaksi', function () {
    $member = member('reseller');
    $pin = pin('registrasi_upgrade', 1, 500000);
    $transaksi = Transaction::create([
        'code' => 'TRX-001',
        'type' => 'join',
        'member_id' => $member->id,
        'pin_id' => $pin->id,
        'points' => 1,
        'amount' => 500000,
        'sponsor_bonus_total' => 50000,
        'posted_at' => now()->subDays(2),
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->get('/admin/transaksi');

    $response->assertOk();
    $response->assertSee('TRX-001');
    $response->assertSee($member->username);
    $response->assertSee('join');
});

test('admin bisa filter transaksi by jenis', function () {
    $member = member('reseller');
    $pin1 = pin('registrasi_upgrade', 1, 500000);
    $pin2 = pin('ro', 2, 100000);
    
    Transaction::create([
        'type' => 'join',
        'member_id' => $member->id,
        'pin_id' => $pin1->id,
        'code' => 'JOIN-001',
        'points' => 1,
        'amount' => 500000,
        'posted_at' => now(),
    ]);
    Transaction::create([
        'type' => 'ro',
        'member_id' => $member->id,
        'pin_id' => $pin2->id,
        'code' => 'RO-001',
        'points' => 2,
        'amount' => 100000,
        'posted_at' => now(),
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->get('/admin/transaksi?jenis=join');

    $response->assertOk();
    $response->assertSee('JOIN-001');
    $response->assertDontSee('RO-001');
});

test('admin bisa filter transaksi by anggota username', function () {
    $user1 = member('reseller');
    $user2 = member('reseller');
    $pin1 = pin('registrasi_upgrade', 1, 500000);
    $pin2 = pin('registrasi_upgrade', 1, 500000);
    
    Transaction::create([
        'member_id' => $user1->id,
        'pin_id' => $pin1->id,
        'code' => 'TRX-A',
        'type' => 'join',
        'points' => 1,
        'amount' => 500000,
        'posted_at' => now(),
    ]);
    Transaction::create([
        'member_id' => $user2->id,
        'pin_id' => $pin2->id,
        'code' => 'TRX-B',
        'type' => 'join',
        'points' => 1,
        'amount' => 500000,
        'posted_at' => now(),
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->get("/admin/transaksi?anggota={$user1->username}");

    $response->assertOk();
    $response->assertSee('TRX-A');
    $response->assertDontSee('TRX-B');
});

test('admin bisa filter transaksi by tanggal dari-sampai', function () {
    $member = member('reseller');
    $pin1 = pin('registrasi_upgrade', 1, 500000);
    $pin2 = pin('registrasi_upgrade', 1, 500000);
    $pin3 = pin('registrasi_upgrade', 1, 500000);
    
    Transaction::create([
        'member_id' => $member->id,
        'pin_id' => $pin1->id,
        'code' => 'TRX-OLD',
        'type' => 'join',
        'points' => 1,
        'amount' => 500000,
        'posted_at' => '2024-01-01',
    ]);
    Transaction::create([
        'member_id' => $member->id,
        'pin_id' => $pin2->id,
        'code' => 'TRX-MID',
        'type' => 'join',
        'points' => 1,
        'amount' => 500000,
        'posted_at' => '2024-06-15',
    ]);
    Transaction::create([
        'member_id' => $member->id,
        'pin_id' => $pin3->id,
        'code' => 'TRX-NEW',
        'type' => 'join',
        'points' => 1,
        'amount' => 500000,
        'posted_at' => '2024-12-01',
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->get('/admin/transaksi?dari=2024-06-01&sampai=2024-06-30');

    $response->assertOk();
    $response->assertSee('TRX-MID');
    $response->assertDontSee('TRX-OLD');
    $response->assertDontSee('TRX-NEW');
});

test('admin bisa lihat detail transaksi dengan bonus entries', function () {
    $member = member('reseller');
    $sponsor = member('agent');
    $pin = pin('registrasi_upgrade', 1, 500000);
    
    $transaksi = Transaction::create([
        'code' => 'TRX-DETAIL',
        'type' => 'join',
        'member_id' => $member->id,
        'pin_id' => $pin->id,
        'points' => 1,
        'amount' => 500000,
        'sponsor_bonus_total' => 50000,
        'posted_at' => now(),
    ]);

    BonusEntry::create([
        'member_id' => $sponsor->id,
        'kind' => 'sponsor',
        'amount' => 50000,
        'source_transaction_id' => $transaksi->id,
        'status' => 'available',
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->get("/admin/transaksi/{$transaksi->id}");

    $response->assertOk();
    $response->assertSee('TRX-DETAIL');
    $response->assertSee($sponsor->username);
    $response->assertSee('sponsor');
    $response->assertSee('50000');
    $response->assertSee('available');
});

test('transaksi list ter-paginate 20 item', function () {
    $member = member('reseller');
    
    for ($i = 1; $i <= 25; $i++) {
        $pin = pin('registrasi_upgrade', 1, 500000);
        Transaction::create([
            'member_id' => $member->id,
            'pin_id' => $pin->id,
            'code' => "TRX-{$i}",
            'type' => 'join',
            'points' => 1,
            'amount' => 500000,
            'posted_at' => now(),
        ]);
    }

    $response = $this->actingAs($this->admin, 'admin')
        ->get('/admin/transaksi');

    $response->assertOk();
    $response->assertSee('TRX-25'); // terbaru dulu
    $response->assertSee('TRX-6');
    $response->assertDontSee('TRX-5'); // page 2
});
