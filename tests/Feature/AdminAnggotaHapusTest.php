<?php

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;

function adminHapus(): Admin
{
    return Admin::create([
        'name' => 'Admin Hapus',
        'email' => 'admin'.uniqid().'@uji.local',
        'password' => Hash::make('rahasia-admin'),
        'role' => 'admin',
        'is_active' => true,
    ]);
}

it('admin bisa menghapus anggota yang belum punya jejak', function () {
    $anggota = member();

    test()->actingAs(adminHapus(), 'admin')
        ->get("/admin/anggota/{$anggota->id}/edit")
        ->assertOk()
        ->assertSee('Hapus Anggota');

    test()->actingAs(adminHapus(), 'admin')
        ->delete("/admin/anggota/{$anggota->id}")
        ->assertRedirect('/admin/anggota');

    expect(Member::find($anggota->id))->toBeNull()
        ->and(Member::withTrashed()->find($anggota->id)->deleted_at)->not->toBeNull()
        ->and(AuditLog::where('action', 'anggota.hapus')->count())->toBe(1);
});

it('hapus ditolak kalau anggota sudah punya riwayat poin', function () {
    $anggota = member();
    givePoints($anggota, 'pr', 1);

    test()->actingAs(adminHapus(), 'admin')
        ->delete("/admin/anggota/{$anggota->id}")
        ->assertRedirect();

    expect(Member::find($anggota->id))->not->toBeNull()
        ->and($anggota->fresh()->deleted_at)->toBeNull();
});

it('hapus ditolak kalau anggota masih punya downline', function () {
    $upline = member();
    member('distributor', $upline);

    test()->actingAs(adminHapus(), 'admin')
        ->delete("/admin/anggota/{$upline->id}")
        ->assertRedirect();

    expect($upline->fresh())->not->toBeNull()
        ->and($upline->fresh()->deleted_at)->toBeNull();
});
