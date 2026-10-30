<?php

use App\Models\BonusEntry;
use App\Models\MilestoneClaim;
use App\Models\MilestoneReward;
use App\Models\PointEntry;
use App\Models\Transaction;
use App\Services\MilestoneEvaluator;
use App\Services\TransactionPoster;

function seedMilestones(): void
{
    foreach ([5, 20, 75, 500, 3500] as $i => $threshold) {
        MilestoneReward::create([
            'threshold' => $threshold,
            'name' => "Hadiah {$threshold} poin",
            'is_active' => true,
            'sort' => $i,
        ]);
    }
}

it('menolak PIN yang sama dipakai dua kali', function () {
    $buyer = member('reseller');
    $pin = pin('registration', 1, 600000);
    $poster = app(TransactionPoster::class);

    $poster->post($buyer, $pin->id, 'join');

    expect(fn () => $poster->post($buyer, $pin->id, 'join'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(1)
        ->and($pin->fresh()->status)->toBe('used');
});

it('pendaftaran mencatat poin PA dan PR ke sponsor langsung', function () {
    $sponsor = member('reseller');
    $buyer = member('reseller', $sponsor);

    app(TransactionPoster::class)->post($buyer, pin('registration', 3, 1800000)->id, 'join');

    expect((int) PointEntry::where('member_id', $sponsor->id)->where('kind', 'pa')->sum('delta'))->toBe(3)
        ->and((int) PointEntry::where('member_id', $sponsor->id)->where('kind', 'pr')->sum('delta'))->toBe(3)
        ->and($sponsor->fresh()->point_pa)->toBe(3)
        ->and($sponsor->fresh()->point_pr)->toBe(3);
});

it('RO hanya menambah PR, peringkat pembeli tidak berubah', function () {
    $sponsor = member('reseller');
    $buyer = member('reseller', $sponsor);

    app(TransactionPoster::class)->post($buyer, pin('ro', 10, 5000000)->id, 'ro');

    expect($buyer->fresh()->rank)->toBe('reseller')
        ->and((int) PointEntry::where('member_id', $sponsor->id)->where('kind', 'pr')->sum('delta'))->toBe(10)
        ->and((int) PointEntry::where('member_id', $sponsor->id)->where('kind', 'pa')->sum('delta'))->toBe(0);
});

it('Manager otomatis saat PA mencapai 20 dan sudah Distributor', function () {
    $sponsor = member('distributor');
    givePoints($sponsor, 'pa', 19);
    $buyer = member('reseller', $sponsor);

    app(TransactionPoster::class)->post($buyer, pin('registration', 1, 600000)->id, 'join');

    $sponsor->refresh();
    expect($sponsor->rank)->toBe('manager')
        ->and($sponsor->is_manager)->toBeTrue()
        ->and($sponsor->manager_since)->not->toBeNull();
});

it('belum jadi Manager kalau masih Reseller meski PA sudah 20', function () {
    $sponsor = member('reseller');
    givePoints($sponsor, 'pa', 20);
    $buyer = member('reseller', $sponsor);

    app(TransactionPoster::class)->post($buyer, pin('registration', 1, 600000)->id, 'join');

    expect($sponsor->fresh()->rank)->toBe('reseller');
});

it('hadiah poin muncul saat ambang terlewati, dan tidak mengurangi poin', function () {
    seedMilestones();

    $sponsor = member('distributor');
    givePoints($sponsor, 'pr', 19);
    $buyer = member('reseller', $sponsor);

    app(TransactionPoster::class)->post($buyer, pin('registration', 1, 600000)->id, 'join');

    $sponsor->refresh();
    $claims = MilestoneClaim::where('member_id', $sponsor->id)->get();

    // 20 poin melewati ambang 5 dan 20; ambang 75 belum.
    expect($claims->count())->toBe(2)
        ->and($claims->pluck('cycle')->unique()->all())->toBe([1])
        ->and(app(MilestoneEvaluator::class)->totalPr($sponsor))->toBe(20);
});

it('hadiah tidak berulang: tiap ambang hanya sekali (perilaku berulang belum diputuskan)', function () {
    seedMilestones();

    $sponsor = member('distributor');
    givePoints($sponsor, 'pr', 6999);
    $buyer = member('reseller', $sponsor);

    app(TransactionPoster::class)->post($buyer, pin('registration', 1, 600000)->id, 'join');

    // 7.000 poin: kelima ambang terlewati, masing-masing satu klaim.
    expect(MilestoneClaim::where('member_id', $sponsor->id)->count())->toBe(5)
        ->and(MilestoneClaim::where('member_id', $sponsor->id)->where('cycle', '>', 1)->count())->toBe(0);
});

it('saldo bonus hanya menghitung bonus yang sudah cair', function () {
    $d = member('distributor');
    $buyer = member('reseller', $d);

    app(TransactionPoster::class)->post($buyer, pin('registration', 6, 3600000)->id, 'join');

    $available = (int) BonusEntry::where('member_id', $d->id)->where('status', 'available')->sum('amount');
    $total = (int) BonusEntry::where('member_id', $d->id)->sum('amount');

    expect($available)->toBe(720000)
        ->and($available)->toBe($total)
        ->and($available)->toBeLessThanOrEqual(intdiv(3600000 * 2000, 10000));
});
