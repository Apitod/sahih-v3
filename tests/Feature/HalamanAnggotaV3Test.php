<?php

use App\Models\BonusEntry;
use App\Services\TransactionPoster;

/**
 * Halaman anggota sesuai PRD v3.0 §7.2 (koreksi Owner 2 November 2026).
 *
 * Yang diuji di sini:
 *  - Beranda punya bagian "Pohon Genealogi" (maks 3 tingkat, nama + peringkat)
 *  - Beranda punya bagian "Cek Bonus" (level, persen, status, tanggal — tanpa rupiah)
 *  - Halaman tukar menyebut dua jenis PIN dan jalan naik peringkat dari PR
 *  - Tidak ada nilai rupiah di halaman anggota (PRD v3.0 §3.1, §10)
 */

// ---------------------------------------------------------------- kode referral

it('beranda menampilkan kode referral anggota (username) untuk dibagikan', function () {
    $a = member('reseller');

    $respon = $this->actingAs($a, 'web')->get('/beranda');

    $respon->assertOk()
        ->assertSee('Kode Referral')
        ->assertSee($a->username);
    expect($respon->getContent())->toContain($a->username);
});

// ---------------------------------------------------------------- pohon genealogi

it('beranda menampilkan pohon genealogi sampai tiga tingkat', function () {
    $a = member('reseller');
    $b = member('agent', $a);
    $c = member('distributor', $b);
    $d = member('manager', $c, ['is_manager' => true]);
    $e = member('reseller', $d);   // tingkat 4 — di luar batas

    $this->actingAs($a, 'web')
        ->get('/beranda')
        ->assertOk()
        ->assertSee('Pohon Genealogi')
        ->assertSee($b->name)
        ->assertSee($c->name)
        ->assertSee($d->name)
        ->assertDontSee($e->name);
});

it('pohon genealogi menampilkan peringkat tiap anggota', function () {
    $a = member('reseller');
    $b = member('agent', $a);
    $c = member('distributor', $b);

    $this->actingAs($a, 'web')
        ->get('/beranda')
        ->assertOk()
        ->assertSee($b->username)
        ->assertSee('Agent')
        ->assertSee($c->username)
        ->assertSee('Distributor');
});

it('pohon genealogi kosong memakai keadaan kosong, bukan tabel palsu', function () {
    $a = member('reseller');

    $this->actingAs($a, 'web')
        ->get('/beranda')
        ->assertOk()
        ->assertSee('Pohon Genealogi')
        ->assertSee('Belum ada anggota di jaringan Anda');
});

// ---------------------------------------------------------------- cek bonus

it('cek bonus menampilkan level, persen, status, dan tanggal', function () {
    $sponsor = member('distributor');
    $anggota = member('reseller', $sponsor);

    app(TransactionPoster::class)->post($anggota, pin('registrasi_upgrade', 3, 1800000)->id, 'join');

    $bonus = BonusEntry::where('member_id', $sponsor->id)->firstOrFail();
    expect($bonus->percent_bp)->toBe(2000);   // Distributor 20%

    $this->actingAs($sponsor, 'web')
        ->get('/beranda')
        ->assertOk()
        ->assertSee('Cek Bonus')
        ->assertSee('20%')
        ->assertSee('level 1')
        ->assertSee('tersedia');
});

it('cek bonus tidak menampilkan nominal rupiah', function () {
    $sponsor = member('distributor');
    $anggota = member('reseller', $sponsor);

    app(TransactionPoster::class)->post($anggota, pin('registrasi_upgrade', 3, 1800000)->id, 'join');

    // Bonus sponsor 20% x Rp 1.800.000 = Rp 360.000 — angka ini tidak boleh muncul.
    expect((int) BonusEntry::where('member_id', $sponsor->id)->sum('amount'))->toBe(360000);

    $this->actingAs($sponsor, 'web')
        ->get('/beranda')
        ->assertOk()
        ->assertDontSee('360.000')
        ->assertDontSee('Rp');
});

it('cek bonus kosong memakai keadaan kosong', function () {
    $a = member('reseller');

    $this->actingAs($a, 'web')
        ->get('/beranda')
        ->assertOk()
        ->assertSee('Cek Bonus')
        ->assertSee('Belum ada bonus yang masuk');
});

// ---------------------------------------------------------------- halaman tukar

it('halaman tukar menyebut dua jenis PIN saja', function () {
    $a = member('reseller');

    $this->actingAs($a, 'web')
        ->get('/tukar')
        ->assertOk()
        ->assertSee('dua jenis PIN')
        ->assertSee('PIN RO')
        ->assertSee('PIN registrasi/upgrade')
        // Jenis lama tidak boleh muncul sebagai pilihan terpisah. Diperiksa lewat
        // kata yang memang ada di halaman, bukan string hantu yang tak pernah ada
        // di repositori (asersi negatif terhadap string hantu selalu lulus).
        ->assertDontSee('PIN upgrade peringkat,');
});

it('halaman tukar menjelaskan PIN gabungan dipakai untuk daftar dan naik peringkat', function () {
    $a = member('reseller');

    $this->actingAs($a, 'web')
        ->get('/tukar')
        ->assertOk()
        // Satu jenis PIN menampung dua jalur (DECISIONS #73).
        ->assertSee('registrasi/upgrade');
});

it('halaman tukar menjelaskan naik peringkat juga otomatis dari poin reward', function () {
    $a = member('reseller');

    $this->actingAs($a, 'web')
        ->get('/tukar')
        ->assertOk()
        ->assertSee('Naik peringkat juga bisa otomatis dari total Poin Reward Anda.');
});

it('halaman tukar tidak menampilkan nilai rupiah', function () {
    $a = member('reseller');
    $kode = pin('ro', 2, 1000000);
    $kode->update(['assigned_member_id' => $a->id]);

    $this->actingAs($a, 'web')
        ->get('/tukar')
        ->assertOk()
        ->assertSee($kode->code)
        ->assertDontSee('Rp')
        ->assertDontSee('1.000.000');
});

// ---------------------------------------------------------------- status downline di pohon

it('pohon member menampilkan badge Aktif dan Beku pada downline', function () {
    $a = member('reseller');
    $aktif = member('agent', $a);
    $beku = member('agent', $a, ['status' => 'suspended']);

    $html = $this->actingAs($a, 'web')->get('/beranda')->assertOk()->getContent();

    expect($html)->toContain('data-status="active"')
        ->toContain('data-status="suspended"')
        ->toContain('Aktif')
        ->toContain('Beku');
});

it('pohon admin menampilkan badge Beku pada downline yang dibekukan', function () {
    $admin = \App\Models\Admin::create([
        'name' => 'Admin Uji', 'email' => 'au'.uniqid().'@uji.local',
        'password' => bcrypt('rahasia'), 'role' => 'admin', 'is_active' => true,
    ]);
    $a = member('reseller');
    member('agent', $a, ['status' => 'suspended']);

    $this->actingAs($admin, 'admin')->get("/admin/anggota/{$a->id}")
        ->assertOk()
        ->assertSee('data-status="suspended"', false)
        ->assertSee('Beku');
});

// ---------------------------------------------------------------- rupiah di halaman saldo

/**
 * Aturan "tanpa rupiah" (PRD v3.0 §3.1, §10) berlaku untuk beranda dan halaman
 * tukar. Halaman saldo justru WAJIB menampilkan rupiah: anggota memutuskan
 * berapa yang ditarik (phase-6a §10, BUSINESS_RULES §13 item 5, min Rp 100.000).
 */
it('halaman saldo dan tarik menampilkan nominal rupiah', function () {
    config(['sahih.pencairan_aktif' => true]); // modul ditahan secara default

    $a = member('reseller');
    BonusEntry::create([
        'member_id' => $a->id, 'kind' => 'sponsor', 'amount' => 1250000,
        'status' => 'available',
    ]);

    $this->actingAs($a, 'web')->get('/saldo')
        ->assertOk()
        ->assertSee('1.250.000');

    $this->actingAs($a, 'web')->get('/tarik')
        ->assertOk()
        ->assertSee('1.250.000')
        ->assertSee('100.000');
});
