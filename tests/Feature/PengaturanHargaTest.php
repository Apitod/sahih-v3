<?php

use App\Models\Setting;
use App\Services\PengaturanHarga;
use App\Services\TransactionPoster;

/**
 * Pengaturan yang bisa diubah Owner tanpa menyentuh kode (PRD v3.0 §3.1).
 *
 * Yang diuji: nilai awal diambil dari config, nilai baru benar-benar dipakai
 * oleh perhitungan (bukan cuma tersimpan), dan kunci asing ditolak.
 */

it('nilai awal diambil dari config kalau tabel settings masih kosong', function () {
    $p = app(PengaturanHarga::class);

    expect($p->ambil('harga_pin_registrasi'))->toBe(600000)
        ->and($p->ambil('harga_pin_ro'))->toBe(500000)
        ->and($p->ambil('syarat_manager_pa'))->toBe(20)
        ->and($p->ambil('putaran_hadiah'))->toBe(3500);
});

it('nilai yang disimpan Owner menimpa config', function () {
    $p = app(PengaturanHarga::class);
    $p->simpan('harga_pin_ro', 550000);

    expect($p->ambil('harga_pin_ro'))->toBe(550000);
});

it('harga RO baru benar-benar dipakai saat PIN ditukar', function () {
    app(PengaturanHarga::class)->simpan('harga_pin_ro', 550000);

    $a = member('reseller');

    // PIN lama dengan harga Rp 500.000/pin harus DITOLAK setelah harga naik.
    $pinLama = pin('ro', 2, 1000000);

    expect(fn () => app(TransactionPoster::class)->post($a, $pinLama->id, 'ro'))
        ->toThrow(\RuntimeException::class);

    // PIN dengan harga baru diterima.
    $pinBaru = pin('ro', 2, 1100000);
    app(TransactionPoster::class)->post($a->fresh(), $pinBaru->id, 'ro');

    expect($a->fresh()->pointTotal('pr'))->toBe(2);
});

it('harga registrasi baru dipakai untuk menghitung harga paket', function () {
    app(PengaturanHarga::class)->simpan('harga_pin_registrasi', 700000);

    $harga = app(\App\Services\PackagePrice::class);

    // Agent = 3 pin → 3 × 700.000
    expect($harga->priceForRank('agent'))->toBe(2100000)
        // Upgrade Reseller → Agent = 2 pin × 700.000
        ->and($harga->upgradePrice('reseller', 'agent'))->toBe(1400000);
});

it('syarat Manager baru dipakai saat menaikkan Manager', function () {
    app(PengaturanHarga::class)->simpan('syarat_manager_pa', 2);

    $a = member('distributor');

    // RO 2 pin → 2 PA. Dengan syarat 2, langsung jadi Manager.
    app(TransactionPoster::class)->post($a, pin('ro', 2, 1000000)->id, 'ro');

    expect($a->fresh()->rank)->toBe('manager');
});

it('kunci pengaturan yang tidak dikenal ditolak', function () {
    expect(fn () => app(PengaturanHarga::class)->simpan('harga_sembarang', 1))
        ->toThrow(\InvalidArgumentException::class);
});

it('nilai negatif ditolak', function () {
    expect(fn () => app(PengaturanHarga::class)->simpan('harga_pin_ro', -1))
        ->toThrow(\InvalidArgumentException::class);
});

it('pengaturan tercatat di audit lewat updated_by', function () {
    $admin = \App\Models\Admin::create([
        'name' => 'Admin Uji',
        'email' => 'uji'.uniqid().'@sahih.local',
        'password' => bcrypt('rahasia'),
        'is_active' => true,
    ]);

    app(PengaturanHarga::class)->simpan('harga_pin_ro', 525000, $admin->id);

    expect(Setting::where('key', 'harga_pin_ro')->first()->updated_by)->toBe($admin->id);
});

it('semua() mengembalikan seluruh kunci yang boleh diubah', function () {
    $semua = app(PengaturanHarga::class)->semua();

    expect($semua)->toHaveKeys(array_keys(PengaturanHarga::BOLEH_DIUBAH));
});
