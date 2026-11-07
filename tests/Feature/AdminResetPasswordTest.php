<?php

use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = Admin::create([
        'name' => 'Admin Test',
        'email' => 'admin@test.local',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'is_active' => true,
    ]);
});

test('admin bisa reset password anggota', function () {
    $a = member('reseller');

    $this->actingAs($this->admin, 'admin')
        ->post("/admin/anggota/{$a->id}/reset-password", [
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(Hash::check('sandibaru123', $a->fresh()->password))->toBeTrue();
});

test('reset password dicatat di audit_logs tanpa menyimpan sandi', function () {
    $a = member('reseller');

    $this->actingAs($this->admin, 'admin')
        ->post("/admin/anggota/{$a->id}/reset-password", [
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ]);

    $log = AuditLog::where('action', 'anggota.reset_password')->first();
    expect($log)->not->toBeNull()
        ->and($log->actor_type)->toBe('admin')
        ->and($log->actor_id)->toBe($this->admin->id)
        ->and($log->subject_id)->toBe($a->id);
    expect(json_encode($log->toArray()))->not->toContain('sandibaru123');
});

test('password kurang dari 8 karakter ditolak', function () {
    $a = member('reseller');
    $lama = $a->password;

    $this->actingAs($this->admin, 'admin')
        ->post("/admin/anggota/{$a->id}/reset-password", [
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])
        ->assertSessionHasErrors('password');

    expect($a->fresh()->password)->toBe($lama);
    expect(AuditLog::where('action', 'anggota.reset_password')->count())->toBe(0);
});

test('konfirmasi password harus sama', function () {
    $a = member('reseller');

    $this->actingAs($this->admin, 'admin')
        ->post("/admin/anggota/{$a->id}/reset-password", [
            'password' => 'sandibaru123',
            'password_confirmation' => 'beda12345',
        ])
        ->assertSessionHasErrors('password');
});

test('halaman edit anggota punya form reset password', function () {
    $a = member('reseller');

    $this->actingAs($this->admin, 'admin')
        ->get("/admin/anggota/{$a->id}/edit")
        ->assertOk()
        ->assertSee('Reset Password')
        ->assertSee(route('admin.anggota.reset-password', $a), false);
});
