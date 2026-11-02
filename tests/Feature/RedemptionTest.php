<?php

use App\Models\Pin;
use App\Models\Transaction;

it('anggota memakai PIN repeat order', function () {
    $sponsor = member('distributor');
    $anggota = member('reseller', $sponsor);
    $kode = pin('ro', 3, 1500000);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertRedirect('/beranda')
        ->assertSessionHas('status');

    expect($kode->fresh()->status)->toBe('used')
        ->and($kode->fresh()->used_by_member_id)->toBe($anggota->id)
        ->and(Transaction::where('type', 'ro')->count())->toBe(1)
        ->and($sponsor->fresh()->pointTotal('pr'))->toBe(3);
});

it('kode boleh ditulis huruf kecil dan berspasi', function () {
    $anggota = member('reseller');
    $kode = pin('ro', 1, 500000);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => ' '.strtolower($kode->code).' '])
        ->assertRedirect('/beranda');

    expect($kode->fresh()->status)->toBe('used');
});

it('PIN yang sudah dipakai tidak bisa dipakai lagi', function () {
    $anggota = member('reseller');
    $kode = pin('ro', 1, 500000);

    $this->actingAs($anggota, 'web')->post('/tukar', ['code' => $kode->code]);
    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertSessionHasErrors('code');

    expect(Transaction::count())->toBe(1);
});

it('PIN yang dibatalkan admin tidak bisa dipakai', function () {
    $anggota = member('reseller');
    $kode = pin('ro', 1, 500000);
    $kode->update(['status' => 'void']);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertSessionHasErrors('code');

    expect(Transaction::count())->toBe(0);
});

it('PIN yang dititipkan ke anggota lain ditolak', function () {
    $pemilik = member('reseller');
    $lain = member('reseller');
    $kode = pin('ro', 1, 500000);
    $kode->update(['assigned_member_id' => $pemilik->id]);

    $this->actingAs($lain, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertSessionHasErrors('code');

    expect($kode->fresh()->status)->toBe('issued')
        ->and(Transaction::count())->toBe(0);
});

it('PIN registrasi tidak bisa dipakai anggota yang sudah terdaftar', function () {
    $anggota = member('reseller');
    $kode = pin('registration', 1, 600000);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertSessionHasErrors('code');

    expect($kode->fresh()->status)->toBe('issued');
});

it('PIN upgrade menaikkan peringkat anggota', function () {
    $anggota = member('reseller');
    $kode = pin('upgrade', 0, 3000000, 'distributor');

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertRedirect('/beranda');

    expect($anggota->fresh()->rank)->toBe('distributor')
        ->and(Transaction::first()->type)->toBe('upgrade');
});

it('PIN upgrade ke peringkat yang sudah dilewati ditolak dan PIN tetap utuh', function () {
    $anggota = member('distributor');
    $kode = pin('upgrade', 0, 1800000, 'agent');

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertSessionHasErrors('code');

    expect($kode->fresh()->status)->toBe('issued')
        ->and($anggota->fresh()->rank)->toBe('distributor')
        ->and(Transaction::count())->toBe(0);
});

it('PIN upgrade tanpa peringkat tujuan ditolak', function () {
    $anggota = member('reseller');
    $kode = pin('upgrade', 0, 3000000, null);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertSessionHasErrors('code');

    expect($kode->fresh()->status)->toBe('issued');
});

it('kode yang tidak dikenal ditolak dengan pesan jelas', function () {
    $anggota = member('reseller');

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => 'TIDAKADAAAAA'])
        ->assertSessionHasErrors(['code' => 'Kode PIN tidak dikenal. Periksa lagi hurufnya.']);
});

it('pesan galat seragam untuk PIN tidak ada, sudah dipakai, dan milik orang lain (F4)', function () {
    $pesan = 'Kode PIN tidak dikenal. Periksa lagi hurufnya.';
    $anggota = member('reseller');
    $lain = member('reseller');

    // 1. Tidak ada
    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => 'TIDAKADAPIN123'])
        ->assertSessionHasErrors(['code' => $pesan]);

    // 2. Sudah dipakai
    $usedPin = pin('ro', 1, 500000);
    $usedPin->update(['status' => 'used']);
    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $usedPin->code])
        ->assertSessionHasErrors(['code' => $pesan]);

    // 3. Milik orang lain
    $otherPin = pin('ro', 1, 500000);
    $otherPin->update(['assigned_member_id' => $lain->id]);
    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $otherPin->code])
        ->assertSessionHasErrors(['code' => $pesan]);
});

it('kode kosong ditolak', function () {
    $anggota = member('reseller');

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => ''])
        ->assertSessionHasErrors('code');
});

it('tamu tidak bisa membuka halaman tukar PIN', function () {
    $this->get('/tukar')->assertRedirect('/masuk');
    $this->post('/tukar', ['code' => 'APA']) ->assertRedirect('/masuk');
});

it('RO tidak membayar bonus sponsor', function () {
    $sponsor = member('distributor');
    $anggota = member('reseller', $sponsor);
    $kode = pin('ro', 3, 1500000);

    $this->actingAs($anggota, 'web')->post('/tukar', ['code' => $kode->code]);

    $trx = Transaction::first();
    // RO: bonus sponsor = 0 (BUSINESS_RULES.md §6)
    expect($trx->sponsor_bonus_total)->toBe(0)
        ->and((int) $trx->bonuses()->sum('amount'))->toBe(0)
        ->and($trx->bonuses()->count())->toBe(0);
});

it('bonus sponsor tercatat saat PIN join dipakai', function () {
    $sponsor = member('distributor');

    // Buat anggota baru lewat PIN registrasi (join).
    $kode = pin('registration', 3, 1800000);
    $anggota = member('reseller', $sponsor);

    // Simulasikan join langsung lewat service.
    $poster = app(\App\Services\TransactionPoster::class);
    $trx = $poster->post($anggota, $kode->id, 'join');

    // Distributor 20% dari Rp1.800.000 = Rp360.000
    expect($trx->sponsor_bonus_total)->toBe(360000)
        ->and((int) $trx->bonuses()->sum('amount'))->toBe(360000);
});

it('pembeli mendapat PR dari PIN join tanpa PA pribadi', function () {
    $anggota = member();
    $kode = pin('registration', 3, 1800000);

    app(\App\Services\TransactionPoster::class)->post($anggota, $kode->id, 'join');

    expect($anggota->fresh()->pointTotal('pr'))->toBe(3)
        ->and($anggota->fresh()->pointTotal('pa'))->toBe(0);
});

it('pembeli mendapat PR dari PIN upgrade tanpa PA pribadi', function () {
    $anggota = member('reseller');
    // Reseller -> Agent = Rp 1.200.000 (DECISIONS #59).
    $kode = pin('upgrade', 3, 1200000, 'agent');

    app(\App\Services\TransactionPoster::class)->post($anggota, $kode->id, 'upgrade');

    expect($anggota->fresh()->pointTotal('pr'))->toBe(3)
        ->and($anggota->fresh()->pointTotal('pa'))->toBe(0);
});

it('pembeli mendapat PR dan PA dari PIN RO', function () {
    $anggota = member();
    $kode = pin('ro', 3, 1500000);

    app(\App\Services\TransactionPoster::class)->post($anggota, $kode->id, 'ro');

    // PRD v3.0 §3.5 / DECISIONS #65: RO memberi PR + PA ke pembeli.
    expect($anggota->fresh()->pointTotal('pr'))->toBe(3)
        ->and($anggota->fresh()->pointTotal('pa'))->toBe(3);
});

it('pembeli otomatis menjadi Manager saat upgrade ke Distributor dan PA cukup', function () {
    $anggota = member('agent');
    givePoints($anggota, 'pa', 20);
    $kode = pin('upgrade', 0, 1800000, 'distributor');

    app(\App\Services\TransactionPoster::class)->post($anggota, $kode->id, 'upgrade');

    expect($anggota->fresh()->rank)->toBe('manager')
        ->and($anggota->fresh()->is_manager)->toBeTrue();
});
