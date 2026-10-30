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
