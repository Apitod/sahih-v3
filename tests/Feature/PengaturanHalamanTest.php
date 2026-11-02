<?php

use App\Models\Admin;
use App\Models\Setting;

/**
 * Halaman pengaturan Owner (PRD v3.0 §3.1).
 * Hanya Superadmin yang boleh masuk dan menyimpan.
 */

function adminPengaturan(string $role = 'superadmin'): Admin
{
    return Admin::create([
        'name' => 'Admin '.$role,
        'email' => $role.uniqid().'@sahih.local',
        'password' => bcrypt('rahasia'),
        'role' => $role,
        'is_active' => true,
    ]);
}

it('superadmin bisa membuka halaman pengaturan', function () {
    $this->actingAs(adminPengaturan('superadmin'), 'admin')
        ->get(route('admin.pengaturan'))
        ->assertOk()
        ->assertSee('Pengaturan')
        ->assertSee('Harga 1 pin registrasi');
});

it('admin biasa ditolak saat membuka halaman pengaturan', function () {
    $this->actingAs(adminPengaturan('admin'), 'admin')
        ->get(route('admin.pengaturan'))
        ->assertForbidden();
});

it('tamu diarahkan ke login admin, bukan halaman pengaturan', function () {
    $this->get(route('admin.pengaturan'))->assertRedirect(route('admin.login'));
});

it('superadmin bisa menyimpan harga baru', function () {
    $admin = adminPengaturan('superadmin');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.pengaturan.simpan'), [
            'harga_pin_registrasi' => 650000,
            'harga_pin_ro' => 520000,
            'nilai_poin_leadership' => 40000,
            'syarat_manager_pa' => 20,
            'putaran_hadiah' => 3500,
        ])
        ->assertRedirect(route('admin.pengaturan'));

    expect(Setting::where('key', 'harga_pin_ro')->first()->value)->toBe(['nilai' => 520000]);
});

it('admin biasa tidak bisa menyimpan pengaturan', function () {
    $this->actingAs(adminPengaturan('admin'), 'admin')
        ->post(route('admin.pengaturan.simpan'), [
            'harga_pin_registrasi' => 650000,
            'harga_pin_ro' => 520000,
            'nilai_poin_leadership' => 40000,
            'syarat_manager_pa' => 20,
            'putaran_hadiah' => 3500,
        ])
        ->assertForbidden();

    expect(Setting::count())->toBe(0);
});

it('nilai negatif ditolak saat menyimpan lewat form', function () {
    $this->actingAs(adminPengaturan('superadmin'), 'admin')
        ->post(route('admin.pengaturan.simpan'), [
            'harga_pin_registrasi' => -1,
            'harga_pin_ro' => 520000,
            'nilai_poin_leadership' => 40000,
            'syarat_manager_pa' => 20,
            'putaran_hadiah' => 3500,
        ])
        ->assertSessionHasErrors('harga_pin_registrasi');
});

it('perubahan pengaturan tercatat di jejak audit', function () {
    $admin = adminPengaturan('superadmin');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.pengaturan.simpan'), [
            'harga_pin_registrasi' => 600000,
            'harga_pin_ro' => 510000,
            'nilai_poin_leadership' => 40000,
            'syarat_manager_pa' => 20,
            'putaran_hadiah' => 3500,
        ]);

    $log = \App\Models\AuditLog::where('action', 'pengaturan.update')->first();

    expect($log)->not->toBeNull()
        ->and($log->actor_id)->toBe($admin->id);
});
