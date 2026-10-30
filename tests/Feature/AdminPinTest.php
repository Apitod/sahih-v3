<?php

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Pin;
use Illuminate\Support\Facades\Hash;

function adminUji(): Admin
{
    return Admin::create([
        'name' => 'Admin Uji',
        'email' => 'admin'.uniqid().'@uji.local',
        'password' => Hash::make('rahasia-admin'),
        'role' => 'admin',
        'is_active' => true,
    ]);
}

function kirimPin(array $ganti = []): \Illuminate\Testing\TestResponse
{
    return test()->actingAs(adminUji(), 'admin')->post('/admin/pin', array_merge([
        'type' => 'registration',
        'points' => 1,
        'price' => 600000,
        'quantity' => 1,
    ], $ganti));
}

it('admin membuat PIN registrasi', function () {
    kirimPin()->assertRedirect('/admin/pin');

    $pin = Pin::first();
    expect($pin->type)->toBe('registration')
        ->and($pin->points)->toBe(1)
        ->and($pin->price)->toBe(600000)
        ->and($pin->status)->toBe('issued')
        ->and($pin->issued_by)->not->toBeNull()
        ->and(strlen($pin->code))->toBe(12);
});

it('kode PIN tidak memakai huruf yang mudah tertukar', function () {
    kirimPin(['quantity' => 20]);

    foreach (Pin::pluck('code') as $kode) {
        expect($kode)->not->toMatch('/[O0I1L]/');
    }
});

it('permintaan banyak kode menghasilkan kode berbeda semua', function () {
    kirimPin(['quantity' => 5])->assertRedirect('/admin/pin');

    expect(Pin::count())->toBe(5)
        ->and(Pin::distinct()->count('code'))->toBe(5);
});

it('PIN upgrade wajib menyebut peringkat tujuan', function () {
    kirimPin(['type' => 'upgrade', 'target_rank' => ''])
        ->assertSessionHasErrors('target_rank');

    expect(Pin::count())->toBe(0);
});

it('PIN upgrade menyimpan peringkat tujuan', function () {
    $anggota = member('reseller');

    // Reseller -> Distributor = Rp 3.000.000 - Rp 600.000 (DECISIONS #59).
    kirimPin([
        'type' => 'upgrade',
        'target_rank' => 'distributor',
        'points' => 0,
        'price' => 3000000,
        'username' => $anggota->username,
    ])->assertRedirect('/admin/pin');

    expect(Pin::first()->target_rank)->toBe('distributor');
});

it('PIN upgrade ke Manager ditolak saat dibuat (DECISIONS #58)', function () {
    $anggota = member('distributor');

    kirimPin([
        'type' => 'upgrade',
        'target_rank' => 'manager',
        'points' => 0,
        'price' => 0,
        'username' => $anggota->username,
    ])->assertSessionHasErrors('target_rank');

    expect(Pin::count())->toBe(0);
});

it('PIN upgrade tanpa pemegang ditolak karena harganya tidak bisa dihitung', function () {
    kirimPin(['type' => 'upgrade', 'target_rank' => 'agent', 'points' => 0, 'price' => 1200000])
        ->assertSessionHasErrors('username');

    expect(Pin::count())->toBe(0);
});

it('PIN upgrade dengan harga bukan selisih paket ditolak', function () {
    $anggota = member('reseller');

    kirimPin([
        'type' => 'upgrade',
        'target_rank' => 'agent',
        'points' => 0,
        'price' => 1800000,
        'username' => $anggota->username,
    ])->assertSessionHasErrors('price');

    expect(Pin::count())->toBe(0);
});

it('PIN registrasi tidak menyimpan peringkat tujuan', function () {
    kirimPin(['target_rank' => 'manager']);

    expect(Pin::first()->target_rank)->toBeNull();
});

it('PIN bisa dititipkan ke anggota tertentu', function () {
    $member = member('reseller');

    kirimPin(['username' => $member->username]);

    expect(Pin::first()->assigned_member_id)->toBe($member->id);
});

it('PIN tidak bisa dititipkan ke anggota yang dibekukan', function () {
    $member = member('reseller', null, ['status' => 'suspended']);

    kirimPin(['username' => $member->username])->assertSessionHasErrors('username');

    expect(Pin::count())->toBe(0);
});

it('setiap PIN yang dibuat meninggalkan jejak audit', function () {
    kirimPin(['quantity' => 2]);

    $log = AuditLog::where('action', 'pin.create')->get();
    expect($log)->toHaveCount(2)
        ->and($log->first()->actor_type)->toBe('admin');
});

it('PIN yang belum dipakai bisa dibatalkan dan tercatat', function () {
    kirimPin();
    $pin = Pin::first();

    $this->actingAs(adminUji(), 'admin')
        ->post("/admin/pin/{$pin->id}/batalkan", ['reason' => 'salah harga'])
        ->assertRedirect('/admin/pin');

    expect($pin->fresh()->status)->toBe('void');
    expect(AuditLog::where('action', 'pin.void')->first()->after['reason'])->toBe('salah harga');
});

it('PIN yang sudah dipakai tidak bisa dibatalkan', function () {
    kirimPin();
    $pin = Pin::first();
    $pin->update(['status' => 'used']);

    $this->actingAs(adminUji(), 'admin')
        ->post("/admin/pin/{$pin->id}/batalkan", ['reason' => 'coba'])
        ->assertSessionHasErrors('reason');

    expect($pin->fresh()->status)->toBe('used');
});

it('pembatalan wajib menyebut alasan', function () {
    kirimPin();
    $pin = Pin::first();

    $this->actingAs(adminUji(), 'admin')
        ->post("/admin/pin/{$pin->id}/batalkan", ['reason' => ''])
        ->assertSessionHasErrors('reason');

    expect($pin->fresh()->status)->toBe('issued');
});

it('anggota biasa tidak bisa membuka halaman PIN admin', function () {
    $this->actingAs(member('reseller'), 'web')
        ->get('/admin/pin')
        ->assertRedirect('/admin/masuk');
});

it('admin melihat daftar PIN beserta kodenya', function () {
    kirimPin();
    $pin = Pin::first();

    // 'belum dipakai' juga muncul di <option> filter, jadi bukan bukti tabel terisi.
    // Yang hanya ada di baris tabel: harga terformat dan tombol Batalkan.
    $this->actingAs(adminUji(), 'admin')
        ->get('/admin/pin')
        ->assertOk()
        ->assertSee($pin->code)
        ->assertSee('Rp 600.000')
        ->assertSee('Batalkan');
});
