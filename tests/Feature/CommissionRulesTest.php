<?php

use App\Models\BonusEntry;
use App\Models\Member;
use App\Services\CommissionCalculator;
use App\Services\TransactionPoster;

/** Ambil bonus per level untuk satu transaksi. */
function bonusByLevel(int $transactionId): array
{
    return BonusEntry::where('source_transaction_id', $transactionId)
        ->orderBy('level')->pluck('amount', 'level')->all();
}

it('rantai lengkap: Reseller 15% + Agent 3% + Distributor 2% = Rp 120.000', function () {
    $d = member('distributor');
    $a = member('agent', $d);
    $r = member('reseller', $a);
    $buyer = member('reseller', $r);

    $trx = app(TransactionPoster::class)->post($buyer, pin('registration', 1, 600000)->id, 'join');

    expect(bonusByLevel($trx->id))->toBe([1 => 90000, 2 => 18000, 3 => 12000])
        ->and($trx->sponsor_bonus_total)->toBe(120000);
});

it('peringkat tengah lebih rendah dilewati: Agent 18% + Reseller 0 + Distributor 2%', function () {
    $d = member('distributor');
    $r = member('reseller', $d);
    $a = member('agent', $r);
    $buyer = member('reseller', $a);

    // Perhitungan: level 2 dapat 0 karena peringkatnya lebih rendah.
    $allocations = app(CommissionCalculator::class)->distribute(600000, ['agent', 'reseller', 'distributor']);
    expect(array_column($allocations, 'amount'))->toBe([108000, 0, 12000]);

    // Ledger hanya mencatat uang yang benar-benar berpindah.
    $trx = app(TransactionPoster::class)->post($buyer, pin('registration', 1, 600000)->id, 'join');
    expect(bonusByLevel($trx->id))->toBe([1 => 108000, 3 => 12000])
        ->and($trx->sponsor_bonus_total)->toBe(120000);
});

it('tidak ada peringkat lebih tinggi: Reseller 15% + Reseller 0 = Rp 90.000', function () {
    $r2 = member('reseller');
    $r1 = member('reseller', $r2);
    $buyer = member('reseller', $r1);

    $trx = app(TransactionPoster::class)->post($buyer, pin('registration', 1, 600000)->id, 'join');

    expect(bonusByLevel($trx->id))->toBe([1 => 90000])
        ->and($trx->sponsor_bonus_total)->toBe(90000);

    // Level 2 (Reseller, peringkat sama) dihitung 0 — tidak ada uang untuknya.
    $allocations = app(CommissionCalculator::class)->distribute(600000, ['reseller', 'reseller']);
    expect(array_column($allocations, 'amount'))->toBe([90000, 0]);
});

it('Distributor mengajak Agent: Distributor 20% dari Rp 1.800.000 = Rp 360.000', function () {
    $d = member('distributor');
    $buyer = member('reseller', $d);

    $trx = app(TransactionPoster::class)->post($buyer, pin('registration', 3, 1800000)->id, 'join');

    expect(bonusByLevel($trx->id))->toBe([1 => 360000])
        ->and($trx->sponsor_bonus_total)->toBe(360000);
});

it('total bonus tidak pernah melebihi 20% dari nilai transaksi', function () {
    $calc = app(CommissionCalculator::class);
    $ranks = ['reseller', 'agent', 'distributor', 'manager'];
    $amount = 1800000;

    for ($i = 0; $i < 200; $i++) {
        $chain = [];
        for ($j = 0; $j < random_int(1, 6); $j++) {
            $chain[] = $ranks[array_rand($ranks)];
        }

        expect($calc->total($calc->distribute($amount, $chain)))
            ->toBeLessThanOrEqual(intdiv($amount * 2000, 10000));
    }
});

it('rantai tanpa upline menghasilkan bonus Rp 0 dan tidak error', function () {
    $buyer = member('reseller');   // daftar tanpa kode referral

    $trx = app(TransactionPoster::class)->post($buyer, pin('registration', 1, 600000)->id, 'join');

    expect($trx->sponsor_bonus_total)->toBe(0)
        ->and(BonusEntry::count())->toBe(0);
});
