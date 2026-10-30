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

    $this->assertGuest('web');

    $this->actingAs($member, 'web')
        ->get('/tukar')
        ->assertForbidden();

    $this->assertGuest('web');

    $this->actingAs($member, 'web')
        ->post('/tukar', ['code' => $pin->code])
        ->assertForbidden();

    $this->assertGuest('web');

    expect($pin->fresh()->status)->toBe('issued')
        ->and(\App\Models\Transaction::count())->toBe(0);
});

it('anggota yang dibekukan saat sesi aktif ditolak 403, sesinya diakhiri, dan sesi lama tidak bisa dipakai lagi', function () {
    $member = member('reseller', null, ['status' => 'active']);

    $this->post('/masuk', ['username' => $member->username, 'password' => 'rahasia'])
        ->assertRedirect('/beranda');
    $this->assertAuthenticatedAs($member, 'web');

    $member->update(['status' => 'suspended']);

    $this->get('/beranda')->assertForbidden();
    $this->assertGuest('web');

    // Sesi lama tidak bisa dipakai lagi meskipun akun diaktifkan kembali
    $member->update(['status' => 'active']);
    $this->get('/beranda')->assertRedirect('/masuk');
    $this->assertGuest('web');
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

it('31 percobaan login anggota dari satu IP tidak mengunci login admin', function () {
    $admin = Admin::create([
        'name' => 'Admin Uji',
        'email' => 'admin@uji.local',
        'password' => Hash::make('rahasia-admin'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    // Batas per IP untuk /masuk sekarang 30/menit (satu kantor bisa satu IP),
    // sedangkan batas per akun tetap 10/menit di controller.
    for ($i = 0; $i < 30; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
            ->post('/masuk', ['username' => 'user-salah-'.$i, 'password' => 'salah']);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
        ->post('/masuk', ['username' => 'user-salah-30', 'password' => 'salah'])
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

it('login anggota dan admin di sesi sama lalu admin keluar mengakhiri semua guard', function () {
    $member = member('reseller');
    $admin = Admin::create([
        'name' => 'Admin Sesi 2',
        'email' => 'admin-sesi2@uji.local',
        'password' => Hash::make('rahasia-admin'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->post('/masuk', ['username' => $member->username, 'password' => 'rahasia'])
        ->assertRedirect('/beranda');
    $this->post('/admin/masuk', ['email' => 'admin-sesi2@uji.local', 'password' => 'rahasia-admin'])
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($member, 'web');
    $this->assertAuthenticatedAs($admin, 'admin');

    $this->post('/admin/keluar')->assertRedirect('/admin/masuk');

    $this->assertGuest('web');
    $this->assertGuest('admin');

    $this->get('/beranda')->assertRedirect('/masuk');
    $this->get('/admin')->assertRedirect('/admin/masuk');
});

it('admin yang dinonaktifkan dengan sesi aktif ditolak dari rute admin dan sesinya diakhiri', function () {
    $admin = Admin::create([
        'name' => 'Admin Aktif',
        'email' => 'admin-aktif@uji.local',
        'password' => Hash::make('rahasia-admin'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->post('/admin/masuk', ['email' => 'admin-aktif@uji.local', 'password' => 'rahasia-admin'])
        ->assertRedirect('/admin');
    $this->assertAuthenticatedAs($admin, 'admin');

    $admin->update(['is_active' => false]);

    $this->get('/admin/pin')
        ->assertForbidden();

    $this->assertGuest('admin');
});

it('anggota yang sudah masuk dialihkan dari halaman login anggota ke beranda', function () {
    $member = member('reseller');

    $this->actingAs($member, 'web')
        ->get('/masuk')
        ->assertRedirect('/beranda');
});

it('admin yang sudah masuk dialihkan dari halaman login admin ke beranda admin', function () {
    $admin = Admin::create([
        'name' => 'Admin Uji',
        'email' => 'admin-guest@uji.local',
        'password' => Hash::make('rahasia-admin'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->actingAs($admin, 'admin')
        ->get('/admin/masuk')
        ->assertRedirect('/admin');
});

it('respons memuat header keamanan X-Frame-Options, X-Content-Type-Options, dan Referrer-Policy', function () {
    $response = $this->get('/masuk');

    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'same-origin');
});
