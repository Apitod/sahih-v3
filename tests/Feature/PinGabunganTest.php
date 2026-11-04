<?php

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Pin;
use App\Models\Transaction;
use App\Services\TransactionPoster;
use Illuminate\Support\Facades\Hash;

/**
 * Perilaku PIN gabungan (DECISIONS #73, Owner 2 Nov).
 *
 * Dulu ada tiga jenis PIN: registration, upgrade, ro. Sekarang dua:
 *  - `registrasi_upgrade` — satu PIN yang bisa dipakai untuk mendaftar ATAU naik peringkat
 *  - `ro`                 — repeat order
 *
 * Karena satu jenis menampung dua jalur, yang menentukan jalur bukan lagi nama
 * jenis melainkan `target_rank` pada PIN-nya:
 *  - `target_rank` kosong  → PIN pendaftaran, transaksi `join`, peringkat dari poin
 *  - `target_rank` terisi  → PIN naik peringkat, transaksi `upgrade`, peringkat dari target
 *
 * Uji di berkas ini mengunci pembeda itu, plus verifikasi username dua kali di
 * form admin (Owner: "berikan 2 kali verifikasi ketika mau buat pin pastikan
 * username nya betul itu").
 */

function adminGabungan(): Admin
{
    return Admin::create([
        'name' => 'Admin Uji Gabungan',
        'email' => 'admin.gabungan.'.uniqid().'@uji.local',
        'password' => Hash::make('rahasia-admin'),
        'role' => 'admin',
        'is_active' => true,
    ]);
}

// ---------------------------------------------------------------- hanya dua jenis

it('hanya mengenal dua jenis PIN, bukan tiga', function () {
    $jenis = Pin::jenis();

    expect($jenis)->toBe(['registrasi_upgrade', 'ro'])
        ->and($jenis)->not->toContain('registration')
        ->and($jenis)->not->toContain('upgrade');
});

it('PIN gabungan tanpa peringkat tujuan adalah PIN pendaftaran', function () {
    $pin = pin('registrasi_upgrade', 3, 1800000);

    expect($pin->untukPendaftaran())->toBeTrue()
        ->and($pin->untukUpgrade())->toBeFalse();
});

it('PIN gabungan dengan peringkat tujuan adalah PIN naik peringkat', function () {
    $pin = pin('registrasi_upgrade', 2, 1200000, 'agent');

    expect($pin->untukUpgrade())->toBeTrue()
        ->and($pin->untukPendaftaran())->toBeFalse();
});

it('PIN RO bukan PIN pendaftaran dan bukan PIN naik peringkat', function () {
    $pin = pinRo(2);

    expect($pin->untukPendaftaran())->toBeFalse()
        ->and($pin->untukUpgrade())->toBeFalse();
});

// ---------------------------------------------------------------- PIN gabungan dipakai mendaftar

it('PIN gabungan tanpa peringkat tujuan dipakai untuk mendaftar', function () {
    $anggota = member('reseller');
    $pin = pin('registrasi_upgrade', 3, 1800000);

    app(TransactionPoster::class)->post($anggota, $pin->id, 'join');

    expect(Transaction::sole()->type)->toBe('join')
        ->and($pin->fresh()->status)->toBe('used')
        ->and($anggota->fresh()->rank)->toBe('agent')
        ->and($anggota->fresh()->pointTotal('pr'))->toBe(3);
});

it('PIN gabungan tanpa peringkat tujuan ditolak kalau dipakai untuk naik peringkat', function () {
    $anggota = member('reseller');
    $pin = pin('registrasi_upgrade', 2, 1200000);

    expect(fn () => app(TransactionPoster::class)->post($anggota, $pin->id, 'upgrade'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued')
        ->and($anggota->fresh()->rank)->toBe('reseller');
});

// ---------------------------------------------------------------- PIN gabungan dipakai naik peringkat

it('PIN gabungan dengan peringkat tujuan dipakai untuk naik peringkat', function () {
    $anggota = member('reseller');
    $pin = pin('registrasi_upgrade', 2, 1200000, 'agent');

    app(TransactionPoster::class)->post($anggota, $pin->id, 'upgrade');

    expect(Transaction::sole()->type)->toBe('upgrade')
        ->and($anggota->fresh()->rank)->toBe('agent')
        ->and($anggota->fresh()->pointTotal('pr'))->toBe(2)
        ->and($anggota->fresh()->pointTotal('pa'))->toBe(0);
});

it('PIN gabungan dengan peringkat tujuan ditolak kalau dipakai untuk mendaftar', function () {
    $anggota = member('reseller');
    $pin = pin('registrasi_upgrade', 2, 1200000, 'agent');

    expect(fn () => app(TransactionPoster::class)->post($anggota, $pin->id, 'join'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued')
        ->and($anggota->fresh()->rank)->toBe('reseller');
});

it('PIN RO tetap tidak bisa dipakai untuk mendaftar maupun naik peringkat', function () {
    $anggota = member('reseller');
    $ro = pinRo(2);
    $poster = app(TransactionPoster::class);

    expect(fn () => $poster->post($anggota, $ro->id, 'join'))
        ->toThrow(RuntimeException::class)
        ->and(fn () => $poster->post($anggota, $ro->id, 'upgrade'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0)
        ->and($ro->fresh()->status)->toBe('issued');
});

// ---------------------------------------------------------------- harga: per pin, bukan selisih paket

it('harga PIN gabungan untuk mendaftar = jumlah pin x Rp 600.000', function () {
    $anggota = member('reseller');
    $pin = pin('registrasi_upgrade', 3, 1800000);

    app(TransactionPoster::class)->post($anggota, $pin->id, 'join');

    expect(Transaction::sole()->amount)->toBe(1800000);
});

it('harga PIN gabungan untuk naik peringkat = jumlah pin x Rp 600.000', function () {
    $anggota = member('reseller');
    // Reseller -> Agent butuh 2 pin, jadi 2 x Rp 600.000 (DECISIONS #73).
    $pin = pin('registrasi_upgrade', 2, 1200000, 'agent');

    app(TransactionPoster::class)->post($anggota, $pin->id, 'upgrade');

    expect(Transaction::sole()->amount)->toBe(1200000);
});

it('PIN gabungan naik peringkat dengan 0 pin ditolak karena harganya bukan per pin', function () {
    $anggota = member('reseller');
    // Dulu PIN upgrade Reseller -> Distributor cukup "selisih paket" Rp 3.000.000 tanpa
    // menyebut jumlah pin. Aturan #73 menghitung per pin, jadi 0 pin tidak sah.
    $pin = pin('registrasi_upgrade', 0, 3000000, 'distributor');

    expect(fn () => app(TransactionPoster::class)->post($anggota, $pin->id, 'upgrade'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued');
});

it('PIN gabungan naik peringkat 5 pin seharga Rp 3.000.000 diterima', function () {
    $anggota = member('reseller');
    // 5 pin x Rp 600.000 = Rp 3.000.000 (Reseller 1 pin -> Distributor 6 pin).
    $pin = pin('registrasi_upgrade', 5, 3000000, 'distributor');

    app(TransactionPoster::class)->post($anggota, $pin->id, 'upgrade');

    expect($anggota->fresh()->rank)->toBe('distributor')
        ->and(Transaction::sole()->amount)->toBe(3000000);
});

// ---------------------------------------------------------------- satu PIN tetap satu kali pakai

it('satu PIN gabungan tidak bisa dipakai dua kali dengan jalur berbeda', function () {
    $anggota = member('reseller');
    $pin = pin('registrasi_upgrade', 2, 1200000, 'agent');
    $poster = app(TransactionPoster::class);

    $poster->post($anggota, $pin->id, 'upgrade');

    expect(fn () => $poster->post($anggota, $pin->id, 'join'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(1)
        ->and($pin->fresh()->status)->toBe('used');
});

// ---------------------------------------------------------------- admin: verifikasi username dua kali

it('admin membuat PIN gabungan untuk mendaftar tanpa peringkat tujuan', function () {
    $this->actingAs(adminGabungan(), 'admin')
        ->post('/admin/pin', [
            'type' => 'registrasi_upgrade',
            'points' => 3,
            'price' => 1800000,
            'quantity' => 1,
        ])
        ->assertRedirect('/admin/pin');

    $pin = Pin::sole();
    expect($pin->type)->toBe('registrasi_upgrade')
        ->and($pin->target_rank)->toBeNull()
        ->and($pin->untukPendaftaran())->toBeTrue();
});

it('admin membuat PIN gabungan untuk naik peringkat dengan peringkat tujuan', function () {
    $anggota = member('reseller');

    $this->actingAs(adminGabungan(), 'admin')
        ->post('/admin/pin', [
            'type' => 'registrasi_upgrade',
            'points' => 2,
            'price' => 1200000,
            'target_rank' => 'agent',
            'quantity' => 1,
            'username' => $anggota->username,
            'username_confirmation' => $anggota->username,
        ])
        ->assertRedirect('/admin/pin');

    $pin = Pin::sole();
    expect($pin->target_rank)->toBe('agent')
        ->and($pin->untukUpgrade())->toBeTrue()
        ->and($pin->assigned_member_id)->toBe($anggota->id);
});

it('username pemegang wajib diketik dua kali dan harus sama', function () {
    $anggota = member('reseller');

    $this->actingAs(adminGabungan(), 'admin')
        ->post('/admin/pin', [
            'type' => 'registrasi_upgrade',
            'points' => 2,
            'price' => 1200000,
            'target_rank' => 'agent',
            'quantity' => 1,
            'username' => $anggota->username,
            'username_confirmation' => 'username-lain',
        ])
        ->assertSessionHasErrors('username');

    expect(Pin::count())->toBe(0);
});

it('verifikasi username kedua yang kosong ditolak', function () {
    $anggota = member('reseller');

    $this->actingAs(adminGabungan(), 'admin')
        ->post('/admin/pin', [
            'type' => 'registrasi_upgrade',
            'points' => 2,
            'price' => 1200000,
            'target_rank' => 'agent',
            'quantity' => 1,
            'username' => $anggota->username,
        ])
        ->assertSessionHasErrors('username_confirmation');

    expect(Pin::count())->toBe(0);
});

it('PIN naik peringkat dengan username tak dikenal ditolak — wajib pemegang sah (U2 audit)', function () {
    $hantu = 'tidak-ada-'.uniqid();

    $this->actingAs(adminGabungan(), 'admin')
        ->post('/admin/pin', [
            'type' => 'registrasi_upgrade',
            'points' => 2,
            'price' => 1200000,
            'target_rank' => 'agent',
            'quantity' => 1,
            'username' => $hantu,
            'username_confirmation' => $hantu,
        ])
        ->assertSessionHasErrors('username');

    expect(Pin::count())->toBe(0);
});

it('PIN RO tidak wajib menyebut pemegang, jadi verifikasi dua kali tidak diminta', function () {
    $this->actingAs(adminGabungan(), 'admin')
        ->post('/admin/pin', [
            'type' => 'ro',
            'points' => 2,
            'price' => 1000000,
            'quantity' => 1,
        ])
        ->assertRedirect('/admin/pin')
        ->assertSessionHasNoErrors();

    expect(Pin::sole()->assigned_member_id)->toBeNull();
});

it('jejak audit PIN gabungan mencatat peringkat tujuan', function () {
    $anggota = member('reseller');

    $this->actingAs(adminGabungan(), 'admin')
        ->post('/admin/pin', [
            'type' => 'registrasi_upgrade',
            'points' => 2,
            'price' => 1200000,
            'target_rank' => 'agent',
            'quantity' => 1,
            'username' => $anggota->username,
            'username_confirmation' => $anggota->username,
        ]);

    $log = AuditLog::where('action', 'pin.create')->sole();
    expect($log->after['type'])->toBe('registrasi_upgrade')
        ->and($log->after['target_rank'])->toBe('agent');
});
