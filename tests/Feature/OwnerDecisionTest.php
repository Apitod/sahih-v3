<?php

use App\Models\BonusEntry;
use App\Models\Pin;
use App\Models\Transaction;
use App\Services\TransactionPoster;

it('Manager mengajak 1 poin mendapat bonus sponsor 20 persen atau Rp 120.000', function () {
    $upline = member('manager');
    $anggota = member('reseller', $upline);
    $kode = pin('registrasi_upgrade', 1, 600000);

    $trx = app(TransactionPoster::class)->post($anggota, $kode->id, 'join');

    expect(BonusEntry::where('member_id', $upline->id)->value('amount'))->toBe(120000)
        ->and($trx->sponsor_bonus_total)->toBe(120000);
});

it('PIN naik peringkat 3 pin membayar bonus Distributor 20 persen atau Rp 360.000', function () {
    $upline = member('distributor');
    // Agent -> Distributor = 3 pin x Rp 600.000 = Rp 1.800.000 (DECISIONS #73), jadi 20% = Rp 360.000.
    $anggota = member('agent', $upline);
    $kode = pin('registrasi_upgrade', 3, 1800000, 'distributor');
    $kode->update(['assigned_member_id' => $anggota->id]);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertRedirect('/beranda');

    expect(BonusEntry::where('member_id', $upline->id)->value('amount'))->toBe(360000)
        ->and(Transaction::sole()->sponsor_bonus_total)->toBe(360000);
});

it('PIN naik peringkat ke peringkat lebih rendah ditolak tanpa menghanguskan PIN atau membuat transaksi', function () {
    $anggota = member('agent');
    $kode = pin('registrasi_upgrade', 1, 600000, 'reseller');

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertSessionHasErrors('code');

    expect($kode->fresh()->status)->toBe('issued')
        ->and(Transaction::count())->toBe(0);
});

it('PIN naik peringkat ke peringkat sama ditolak tanpa menghanguskan PIN', function () {
    $anggota = member('agent');
    $kode = pin('registrasi_upgrade', 3, 1800000, 'agent');

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertSessionHasErrors('code');

    expect($kode->fresh()->status)->toBe('issued')
        ->and(Transaction::count())->toBe(0);
});
