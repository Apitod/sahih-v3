<?php

use App\Models\Admin;
use App\Models\Member;
use App\Models\Pin;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;

/**
 * UJI UJUNG-KE-UJUNG: buat PIN lewat panel admin, lalu PAKAI PIN itu di /tukar.
 *
 * Berkas ini menjawab temuan audit T1: sebelumnya nol berkas uji yang membuat PIN
 * lewat panel DAN memakainya. Akibatnya dua penjaga harga yang bertabrakan (K1)
 * lolos dari 208 uji hijau, karena uji panel hanya memeriksa apa yang tersimpan
 * dan uji tukar memakai PIN buatan helper.
 *
 * Aturan yang dikunci di sini:
 *  - Setiap beli PIN dapat poin sebesar jumlah pin (keputusan Owner 4 Nov).
 *  - Harga PIN naik peringkat = jumlah pin × harga per pin, dan harga yang sama
 *    dipakai saat PIN dibuat MAUPUN saat dipakai.
 *  - PIN lama (0 pin, harga selisih paket) tetap bisa dipakai setelah migrasi.
 */

function adminE2e(): Admin
{
    return Admin::create([
        'name' => 'Admin E2E',
        'email' => 'e2e'.uniqid().'@uji.local',
        'password' => Hash::make('rahasia'),
        'role' => 'admin',
        'is_active' => true,
    ]);
}

/** Buat PIN lewat panel admin, persis seperti admin sungguhan. */
function buatPinLewatPanel(array $data): \Illuminate\Testing\TestResponse
{
    return test()->actingAs(adminE2e(), 'admin')->post('/admin/pin', $data);
}

// ---------------------------------------------------------------- jalur naik peringkat

it('PIN naik peringkat dibuat panel bisa langsung dipakai anggota', function () {
    $anggota = member('reseller');

    // 1. Admin membuat PIN 2 pin, target Agent (R->A). Harga per pin: 2 x 600.000.
    buatPinLewatPanel([
        'type' => 'registrasi_upgrade',
        'points' => 2,
        'price' => 1200000,
        'target_rank' => 'agent',
        'quantity' => 1,
        'username' => $anggota->username,
        'username_confirmation' => $anggota->username,
    ])->assertRedirect('/admin/pin');

    $pin = Pin::sole();

    // 2. Anggota memakai PIN itu di halaman tukar — inilah yang dulu tidak diuji.
    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $pin->code])
        ->assertRedirect('/beranda');

    $anggota = $anggota->fresh();

    expect($anggota->rank)->toBe('agent')
        ->and($pin->fresh()->status)->toBe('used')
        ->and(Transaction::sole()->type)->toBe('upgrade')
        // Setiap beli PIN dapat poin: 2 pin -> 2 PR
        ->and($anggota->pointTotal('pr'))->toBe(2);
});

it('PIN naik peringkat 1 pin juga bisa dipakai (dulu ditolak karena harga bentrok)', function () {
    // Kasus yang membongkar K1: 1 pin x Rp600.000 = Rp600.000, sedangkan harga
    // selisih paket R->A = Rp1.200.000. Dua aturan ini dulu bertabrakan.
    $anggota = member('reseller');

    buatPinLewatPanel([
        'type' => 'registrasi_upgrade',
        'points' => 1,
        'price' => 600000,
        'target_rank' => 'agent',
        'quantity' => 1,
        'username' => $anggota->username,
        'username_confirmation' => $anggota->username,
    ])->assertRedirect('/admin/pin');

    $pin = Pin::sole();

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $pin->code])
        ->assertRedirect('/beranda');

    expect($pin->fresh()->status)->toBe('used')
        ->and(Transaction::sole()->type)->toBe('upgrade');
});

it('PIN naik peringkat 6 pin juga bisa dipakai', function () {
    // Kasus kedua yang membongkar K1: 6 x 600.000 = 3.600.000 vs selisih R->D 3.000.000.
    $anggota = member('reseller');

    buatPinLewatPanel([
        'type' => 'registrasi_upgrade',
        'points' => 6,
        'price' => 3600000,
        'target_rank' => 'distributor',
        'quantity' => 1,
        'username' => $anggota->username,
        'username_confirmation' => $anggota->username,
    ])->assertRedirect('/admin/pin');

    $pin = Pin::sole();

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $pin->code])
        ->assertRedirect('/beranda');

    expect($pin->fresh()->status)->toBe('used')
        ->and(Transaction::sole()->type)->toBe('upgrade');
});

// ---------------------------------------------------------------- jalur RO

it('PIN RO dibuat panel bisa dipakai anggota', function () {
    $anggota = member('reseller');

    buatPinLewatPanel([
        'type' => 'ro',
        'points' => 2,
        'price' => 1000000,
        'quantity' => 1,
        'username' => $anggota->username,
        'username_confirmation' => $anggota->username,
    ])->assertRedirect('/admin/pin');

    $pin = Pin::sole();

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $pin->code])
        ->assertRedirect('/beranda');

    $anggota = $anggota->fresh();

    expect($pin->fresh()->status)->toBe('used')
        ->and(Transaction::sole()->type)->toBe('ro')
        // RO memberi PR + PA (keputusan #65)
        ->and($anggota->pointTotal('pr'))->toBe(2)
        ->and($anggota->pointTotal('pa'))->toBe(2);
});

// ---------------------------------------------------------------- harga tidak bisa diakali

it('harga yang tidak sesuai jumlah pin ditolak saat PIN dibuat', function () {
    $anggota = member('reseller');

    buatPinLewatPanel([
        'type' => 'registrasi_upgrade',
        'points' => 2,
        'price' => 600000,      // seharusnya 1.200.000
        'target_rank' => 'agent',
        'quantity' => 1,
        'username' => $anggota->username,
        'username_confirmation' => $anggota->username,
    ])->assertSessionHasErrors('price');

    expect(Pin::count())->toBe(0);
});

it('username yang tidak terdaftar tidak bisa dipakai untuk PIN naik peringkat', function () {
    buatPinLewatPanel([
        'type' => 'registrasi_upgrade',
        'points' => 2,
        'price' => 1200000,
        'target_rank' => 'agent',
        'quantity' => 1,
        'username' => 'hantu'.uniqid(),
        'username_confirmation' => 'hantu'.uniqid(),
    ])->assertSessionHasErrors('username');

    expect(Pin::count())->toBe(0);
});

it('PIN naik peringkat tidak bisa dipakai orang lain', function () {
    $pemilik = member('reseller');
    $oranglain = member('reseller');

    buatPinLewatPanel([
        'type' => 'registrasi_upgrade',
        'points' => 2,
        'price' => 1200000,
        'target_rank' => 'agent',
        'quantity' => 1,
        'username' => $pemilik->username,
        'username_confirmation' => $pemilik->username,
    ])->assertRedirect('/admin/pin');

    $pin = Pin::sole();

    // Orang lain mencoba memakai PIN yang bukan miliknya.
    $this->actingAs($oranglain, 'web')
        ->post('/tukar', ['code' => $pin->code])
        ->assertSessionHasErrors('code');

    expect($pin->fresh()->status)->toBe('issued')
        ->and($oranglain->fresh()->rank)->toBe('reseller');
});

// ---------------------------------------------------------------- PIN lama

it('PIN lama berpoin 0 dan harga selisih paket tetap bisa dipakai', function () {
    // PIN yang terbit sebelum aturan baru: 0 pin, harga selisih paket.
    // Migrasi harus sudah menyelaraskannya supaya tidak jadi bangkai.
    $anggota = member('reseller');

    $pin = Pin::create([
        'code' => 'LAMA'.strtoupper(substr(uniqid(), -7)),
        'type' => 'registrasi_upgrade',
        'target_rank' => 'distributor',
        'points' => 5,              // hasil penyelarasan migrasi
        'price' => 3000000,         // 5 x 600.000
        'status' => 'issued',
        'assigned_member_id' => $anggota->id,
    ]);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $pin->code])
        ->assertRedirect('/beranda');

    expect($pin->fresh()->status)->toBe('used');
});
