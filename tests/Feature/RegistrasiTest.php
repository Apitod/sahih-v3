<?php

use App\Models\Member;
use App\Models\Pin;
use App\Models\PointEntry;
use App\Models\Transaction;
use App\Models\BonusEntry;

/**
 * Registrasi anggota baru lewat web (BUSINESS_RULES §9, §10; DECISIONS #46).
 *
 * Form: username, nama, password, telepon, alamat, PIN registrasi, kode referral
 * (opsional). PIN registrasi = `registrasi_upgrade` TANPA peringkat tujuan,
 * berisi 1/3/6 pin Rp600.000/pin.
 *
 * Aturan yang dikunci:
 *  - Referral diisi tapi tidak ditemukan -> ditolak, tidak ada akun tercipta.
 *  - Referral kosong -> tanpa upline, bonus sponsor Rp 0, poin PA/PR tetap.
 *  - Referral valid -> akun masuk jaringannya, bonus sponsor terposting.
 *  - PIN tidak valid / sudah terpakai -> ditolak, tidak ada akun tercipta.
 *  - Username kembar -> ditolak.
 */

function pinDaftarPaket(int $poin): Pin
{
    return Pin::create([
        'code' => 'REG'.strtoupper(substr(uniqid(), -9)),
        'type' => 'registrasi_upgrade',
        'target_rank' => null,
        'points' => $poin,
        'price' => $poin * 600000,
        'status' => 'issued',
    ]);
}

function daftar(array $isi = []): \Illuminate\Testing\TestResponse
{
    return test()->post('/daftar', array_merge([
        'username' => 'baru'.substr(uniqid(), -6),
        'name' => 'Anggota Baru',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'phone' => '0812'.rand(1000000, 9999999),
        'address' => 'Jl. Uji No. 1',
        'referral' => '',
        'code' => '',
    ], $isi));
}

it('pendaftaran berhasil dengan PIN valid tanpa referral — tanpa upline, bonus 0, poin PA/PR tercatat', function () {
    $pin = pinDaftarPaket(3);

    daftar(['code' => $pin->code, 'referral' => ''])
        ->assertRedirect('/beranda')
        ->assertSessionHasNoErrors();

    $baru = Member::orderByDesc('id')->first();
    expect(Member::count())->toBe(1)
        ->and($baru->sponsor_id)->toBeNull()
        ->and($baru->rank)->toBe('agent')           // paket 3 pin
        ->and($baru->pointTotal('pr'))->toBe(3)
        ->and($baru->pointTotal('pa'))->toBe(0)
        ->and(Transaction::where('member_id', $baru->id)->where('type', 'join')->exists())->toBeTrue()
        ->and(BonusEntry::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('used');
});

it('pendaftaran dengan referral valid masuk jaringan dan posting bonus sponsor', function () {
    $sponsor = member('distributor');
    $pin = pinDaftarPaket(1);

    daftar(['code' => $pin->code, 'referral' => $sponsor->username])
        ->assertRedirect('/beranda');

    $baru = Member::orderByDesc('id')->first();
    expect($baru->sponsor_id)->toBe($sponsor->id)
        // Sponsor dapat PA + PR dari join baru (paket 1 pin).
        ->and((int) PointEntry::where('member_id', $sponsor->id)->where('kind', 'pa')->sum('delta'))->toBe(1)
        ->and((int) PointEntry::where('member_id', $sponsor->id)->where('kind', 'pr')->sum('delta'))->toBe(1)
        ->and($sponsor->fresh()->point_pa)->toBe(1);

    $trx = Transaction::where('member_id', $baru->id)->where('type', 'join')->first();
    expect((int) BonusEntry::where('source_transaction_id', $trx->id)->sum('amount'))->toBeGreaterThan(0);
});

it('referral diisi tapi tidak ditemukan -> ditolak, tidak ada akun tercipta', function () {
    $pin = pinDaftarPaket(1);

    daftar(['code' => $pin->code, 'referral' => 'tidakada'.uniqid()])
        ->assertSessionHasErrors('referral');

    expect(Member::count())->toBe(0)
        ->and(Transaction::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued');
});

it('kode PIN salah -> ditolak, tidak ada akun tercipta', function () {
    daftar(['code' => 'TIDAKADA'])
        ->assertSessionHasErrors('code');

    expect(Member::count())->toBe(0);
});

it('kode PIN sudah terpakai -> ditolak', function () {
    $pin = pinDaftarPaket(1);
    $orange = member('reseller');
    app(App\Services\TransactionPoster::class)->post($orange, $pin->id, 'join');

    daftar(['code' => $pin->code])
        ->assertSessionHasErrors('code');

    expect(Member::count())->toBe(1);  // cuma yang lama
});

it('username kembar -> ditolak', function () {
    $ada = member('reseller');
    $pin = pinDaftarPaket(1);

    daftar(['username' => $ada->username, 'code' => $pin->code])
        ->assertSessionHasErrors('username');

    expect(Member::count())->toBe(1)   // cuma yang lama
        ->and($pin->fresh()->status)->toBe('issued');
});

it('password dan konfirmasi tidak sama -> ditolak', function () {
    $pin = pinDaftarPaket(1);

    daftar(['code' => $pin->code, 'password' => 'satu123', 'password_confirmation' => 'dua123'])
        ->assertSessionHasErrors('password');

    expect(Member::count())->toBe(0);
});

it('tamu bisa membuka halaman form registrasi', function () {
    $this->get('/daftar')
        ->assertOk()
        ->assertSee('Daftar Anggota Baru')
        ->assertSee('Referral');
});

it('anggota yang sudah login tidak bisa membuka form registrasi', function () {
    $m = member('reseller');

    $this->actingAs($m, 'web')->get('/daftar')->assertRedirect();
});
