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
    // Form admin meminta username pemegang diketik dua kali (Owner: "berikan 2 kali
    // verifikasi ketika mau buat pin"). Nilai bawaan di sini kosong = PIN tanpa pemegang,
    // sama seperti PIN RO yang dijual bebas.
    return test()->actingAs(adminUji(), 'admin')->post('/admin/pin', array_merge([
        'type' => 'registrasi_upgrade',
        'points' => 1,
        'price' => 600000,
        'quantity' => 1,
        'username' => '',
        'username_confirmation' => '',
    ], $ganti));
}

it('admin membuat PIN registrasi/upgrade untuk mendaftar', function () {
    kirimPin()->assertRedirect('/admin/pin');

    $pin = Pin::first();
    expect($pin->type)->toBe('registrasi_upgrade')
        ->and($pin->target_rank)->toBeNull()
        ->and($pin->points)->toBe(1)
        ->and($pin->price)->toBe(600000)
        ->and($pin->status)->toBe('issued')
        ->and($pin->issued_by)->not->toBeNull()
        ->and(strlen($pin->code))->toBe(12);
});

it('kode PIN tidak memakai huruf yang mudah tertukar', function () {
    kirimPin(['quantity' => 20])->assertRedirect('/admin/pin');

    // Tanpa asersi jumlah, uji ini akan "lulus" diam-diam kalau tidak ada PIN terbuat.
    expect(Pin::count())->toBe(20);

    foreach (Pin::pluck('code') as $kode) {
        expect($kode)->not->toMatch('/[O0I1L]/');
    }
});

it('permintaan banyak kode menghasilkan kode berbeda semua', function () {
    kirimPin(['quantity' => 5])->assertRedirect('/admin/pin');

    expect(Pin::count())->toBe(5)
        ->and(Pin::distinct()->count('code'))->toBe(5);
});

it('PIN gabungan tanpa peringkat tujuan tersimpan sebagai PIN pendaftaran', function () {
    kirimPin(['type' => 'registrasi_upgrade', 'target_rank' => ''])->assertRedirect('/admin/pin');

    $pin = Pin::sole();
    expect($pin->target_rank)->toBeNull()
        ->and($pin->untukPendaftaran())->toBeTrue()
        ->and($pin->untukUpgrade())->toBeFalse();
});

it('peringkat tujuan di luar daftar ditolak', function () {
    kirimPin(['type' => 'registrasi_upgrade', 'target_rank' => 'direktur'])
        ->assertSessionHasErrors('target_rank');

    expect(Pin::count())->toBe(0);
});

it('PIN naik peringkat menyimpan peringkat tujuan', function () {
    $anggota = member('reseller');

    // Reseller -> Distributor = 5 pin x Rp 600.000 (DECISIONS #73, per pin).
    kirimPin([
        'type' => 'registrasi_upgrade',
        'target_rank' => 'distributor',
        'points' => 5,
        'price' => 3000000,
        'username' => $anggota->username,
        'username_confirmation' => $anggota->username,
    ])->assertRedirect('/admin/pin');

    expect(Pin::first()->target_rank)->toBe('distributor')
        ->and(Pin::first()->untukUpgrade())->toBeTrue();
});

it('PIN naik peringkat ke Manager ditolak saat dibuat (DECISIONS #58)', function () {
    $anggota = member('distributor');

    kirimPin([
        'type' => 'registrasi_upgrade',
        'target_rank' => 'manager',
        'points' => 0,
        'price' => 0,
        'username' => $anggota->username,
        'username_confirmation' => $anggota->username,
    ])->assertSessionHasErrors('target_rank');

    expect(Pin::count())->toBe(0);
});

it('PIN naik peringkat tanpa pemegang ditolak karena harganya tidak bisa dihitung', function () {
    kirimPin(['type' => 'registrasi_upgrade', 'target_rank' => 'agent', 'points' => 2, 'price' => 1200000])
        ->assertSessionHasErrors('username');

    expect(Pin::count())->toBe(0);
});

it('PIN naik peringkat dengan harga bukan Rp 600.000 x jumlah pin ditolak', function () {
    $anggota = member('reseller');

    // Reseller -> Agent = 2 pin = Rp 1.200.000, bukan Rp 1.800.000.
    kirimPin([
        'type' => 'registrasi_upgrade',
        'target_rank' => 'agent',
        'points' => 2,
        'price' => 1800000,
        'username' => $anggota->username,
        'username_confirmation' => $anggota->username,
    ])->assertSessionHasErrors('price');

    expect(Pin::count())->toBe(0);
});

it('PIN pendaftaran tidak menyimpan peringkat tujuan', function () {
    // PIN pendaftaran = tanpa peringkat tujuan. Dulu uji ini mengirim 'manager',
    // padahal Manager memang tidak dijual (#58) — jadi yang diuji bukan lagi
    // "pendaftaran mengabaikan target", melainkan "Manager ditolak". Sudah dipisah:
    // penolakan Manager ada di ujinya sendiri di bawah.
    kirimPin(['target_rank' => '']);

    expect(Pin::first()->target_rank)->toBeNull();
});

it('PIN pendaftaran dengan peringkat tujuan tanpa pemegang ditolak', function () {
    // Peringkat tujuan hanya bermakna kalau PIN memang untuk naik peringkat, dan
    // itu butuh pemegang supaya peringkat sekarangnya diketahui.
    kirimPin(['target_rank' => 'agent', 'points' => 2, 'price' => 1200000])
        ->assertSessionHasErrors('username');

    expect(Pin::count())->toBe(0);
});

it('PIN bisa dititipkan ke anggota tertentu', function () {
    $member = member('reseller');

    kirimPin([
        'username' => $member->username,
        'username_confirmation' => $member->username,
    ]);

    expect(Pin::first()->assigned_member_id)->toBe($member->id);
});

it('PIN tidak bisa dititipkan ke anggota yang dibekukan', function () {
    $member = member('reseller', null, ['status' => 'suspended']);

    kirimPin([
        'username' => $member->username,
        'username_confirmation' => $member->username,
    ])->assertSessionHasErrors('username');

    expect(Pin::count())->toBe(0);
});

it('username pemegang yang diketik dua kali berbeda ditolak', function () {
    $member = member('reseller');

    kirimPin([
        'username' => $member->username,
        'username_confirmation' => $member->username.'x',
    ])->assertSessionHasErrors('username');

    expect(Pin::count())->toBe(0);
});

it('username pemegang hanya diketik sekali ditolak', function () {
    $member = member('reseller');

    kirimPin([
        'username' => $member->username,
        'username_confirmation' => '',
    ])->assertSessionHasErrors('username_confirmation');

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
