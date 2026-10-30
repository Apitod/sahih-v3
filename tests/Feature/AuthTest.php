<?php

use App\Models\Admin;
use App\Models\MilestoneClaim;
use App\Models\MilestoneReward;
use Illuminate\Support\Facades\Hash;

it('tamu yang membuka beranda dikirim ke halaman masuk', function () {
    $this->get('/beranda')->assertRedirect('/masuk');
});

it('username dan password benar masuk ke beranda', function () {
    $member = member('reseller');

    $this->post('/masuk', ['username' => $member->username, 'password' => 'rahasia'])
        ->assertRedirect('/beranda');

    $this->assertAuthenticatedAs($member, 'web');
});

it('password salah ditolak dengan pesan jelas', function () {
    $member = member('reseller');

    $this->from('/masuk')
        ->post('/masuk', ['username' => $member->username, 'password' => 'salah'])
        ->assertRedirect('/masuk')
        ->assertSessionHasErrors('username');

    $this->assertGuest('web');
});

it('anggota yang dibekukan tidak bisa masuk', function () {
    $member = member('reseller', null, ['status' => 'suspended']);

    $this->from('/masuk')
        ->post('/masuk', ['username' => $member->username, 'password' => 'rahasia'])
        ->assertSessionHasErrors('username');

    $this->assertGuest('web');
});

it('anggota yang dibekukan dengan sesi aktif ditolak dari rute tukar dan beranda', function () {
    $member = member('reseller', null, ['status' => 'suspended']);
    $pin = pin('ro', 1, 600000);

    $this->actingAs($member, 'web')
        ->get('/beranda')
        ->assertForbidden();

    $this->actingAs($member, 'web')
        ->get('/tukar')
        ->assertForbidden();

    $this->actingAs($member, 'web')
        ->post('/tukar', ['code' => $pin->code])
        ->assertForbidden();

    expect($pin->fresh()->status)->toBe('issued')
        ->and(\App\Models\Transaction::count())->toBe(0);
});

it('beranda menampilkan poin dari ledger, bukan dari kolom cache', function () {
    $member = member('reseller');

    // Cache sengaja dikosongkan: kalau halaman membaca cache, angkanya akan 0.
    givePoints($member, 'pr', 5);
    givePoints($member, 'pa', 3);
    $member->update(['point_pr' => 0, 'point_pa' => 0]);

    $this->actingAs($member, 'web')
        ->get('/beranda')
        ->assertOk()
        ->assertSee('Poin Reward')
        ->assertSee('5')
        ->assertSee('Poin Afiliasi')
        ->assertSee('3');
});

it('beranda menampilkan hadiah yang sudah jadi hak anggota', function () {
    $member = member('reseller');
    $reward = MilestoneReward::create(['threshold' => 5, 'name' => 'Produk', 'is_active' => true, 'sort' => 0]);

    MilestoneClaim::create([
        'member_id' => $member->id,
        'milestone_reward_id' => $reward->id,
        'cycle' => 1,
        'status' => 'eligible',
    ]);

    $this->actingAs($member, 'web')
        ->get('/beranda')
        ->assertOk()
        ->assertSee('Produk')
        ->assertSee('jadi hak Anda');
});

it('anggota tidak bisa membuka halaman admin', function () {
    $member = member('reseller');

    $this->actingAs($member, 'web')
        ->get('/admin')
        ->assertRedirect('/admin/masuk');
});

it('admin masuk lewat halaman sendiri dan melihat ringkasan', function () {
    $admin = Admin::create([
        'name' => 'Admin Uji',
        'email' => 'admin@uji.local',
        'password' => Hash::make('rahasia-admin'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->post('/admin/masuk', ['email' => 'admin@uji.local', 'password' => 'rahasia-admin'])
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('admin tidak aktif ditolak', function () {
    Admin::create([
        'name' => 'Admin Beku',
        'email' => 'beku@uji.local',
        'password' => Hash::make('rahasia-admin'),
        'role' => 'admin',
        'is_active' => false,
    ]);

    $this->from('/admin/masuk')
        ->post('/admin/masuk', ['email' => 'beku@uji.local', 'password' => 'rahasia-admin'])
        ->assertSessionHasErrors('email');

    $this->assertGuest('admin');
});

it('keluar mengakhiri sesi anggota', function () {
    $member = member('reseller');

    $this->actingAs($member, 'web')
        ->post('/keluar')
        ->assertRedirect('/masuk');

    $this->assertGuest('web');
});

it('11 percobaan login anggota dari satu IP tidak mengunci login admin', function () {
    $admin = Admin::create([
        'name' => 'Admin Uji',
        'email' => 'admin@uji.local',
        'password' => Hash::make('rahasia-admin'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    for ($i = 0; $i < 10; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
            ->post('/masuk', ['username' => 'user-salah-'.$i, 'password' => 'salah']);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
        ->post('/masuk', ['username' => 'user-salah-10', 'password' => 'salah'])
        ->assertStatus(429);

    $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
        ->post('/admin/masuk', ['email' => 'admin@uji.local', 'password' => 'rahasia-admin'])
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('percobaan gagal dari IP lain tidak mengunci akun anggota', function () {
    $member = member('reseller');

    for ($i = 1; $i <= 10; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.2.'.$i])
            ->from('/masuk')
            ->post('/masuk', ['username' => $member->username, 'password' => 'salah'])
            ->assertRedirect('/masuk')
            ->assertSessionHasErrors('username');
    }

    // Orang asing yang menebak dari IP berbeda tidak boleh mengunci pemilik akun.
    $this->withServerVariables(['REMOTE_ADDR' => '192.168.2.11'])
        ->post('/masuk', ['username' => $member->username, 'password' => 'rahasia'])
        ->assertRedirect('/beranda');

    $this->assertAuthenticatedAs($member, 'web');
});

it('percobaan ke-11 untuk akun yang sama dari IP yang sama ditolak', function () {
    $member = member('reseller');

    for ($i = 1; $i <= 10; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.3.1'])
            ->from('/masuk')
            ->post('/masuk', ['username' => $member->username, 'password' => 'salah'])
            ->assertRedirect('/masuk');
    }

    $this->withServerVariables(['REMOTE_ADDR' => '192.168.3.1'])
        ->post('/masuk', ['username' => $member->username, 'password' => 'rahasia'])
        ->assertStatus(429);
});

it('login anggota dan admin di sesi sama lalu keluar membuat rute admin menolak', function () {
    $member = member('reseller');
    $admin = Admin::create([
        'name' => 'Admin Sesi',
        'email' => 'admin-sesi@uji.local',
        'password' => Hash::make('rahasia-admin'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->post('/masuk', ['username' => $member->username, 'password' => 'rahasia'])
        ->assertRedirect('/beranda');
    $this->post('/admin/masuk', ['email' => 'admin-sesi@uji.local', 'password' => 'rahasia-admin'])
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($member, 'web');
    $this->assertAuthenticatedAs($admin, 'admin');

    $this->post('/keluar')->assertRedirect('/masuk');

    $this->assertGuest('web');
    $this->assertGuest('admin');

    $this->get('/admin')->assertRedirect('/admin/masuk');
});

it('respons memuat header keamanan X-Frame-Options, X-Content-Type-Options, dan Referrer-Policy', function () {
    $response = $this->get('/masuk');

    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'same-origin');
});
