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

it('service menolak anggota dibekukan tanpa memakai PIN atau membuat transaksi', function () {
    $buyer = member('reseller', null, ['status' => 'suspended']);
    $pin = pin('ro', 1, 600000);

    expect(fn () => app(TransactionPoster::class)->post($buyer, $pin->id, 'ro'))
        ->toThrow(RuntimeException::class);

    expect($pin->fresh()->status)->toBe('issued')
        ->and(Transaction::count())->toBe(0);
});

it('upgrade langsung ke Manager melengkapi penanda Manager', function () {
    $buyer = member('distributor');
    $pin = pin('upgrade', 0, 3000000, 'manager');

    app(TransactionPoster::class)->post($buyer, $pin->id, 'upgrade');

    expect($buyer->fresh()->rank)->toBe('manager')
        ->and($buyer->fresh()->is_manager)->toBeTrue()
        ->and($buyer->fresh()->manager_since)->not->toBeNull();
});

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

it('hadiah terulang: 7.000 poin membuka putaran kedua penuh', function () {
    seedMilestones();

    $sponsor = member('distributor');
    givePoints($sponsor, 'pr', 6999);
    $buyer = member('reseller', $sponsor);

    app(TransactionPoster::class)->post($buyer, pin('registration', 1, 600000)->id, 'join');

    // 7.000 poin = dua putaran penuh, lima ambang tiap putaran.
    expect(MilestoneClaim::where('member_id', $sponsor->id)->count())->toBe(10)
        ->and(MilestoneClaim::where('member_id', $sponsor->id)->where('cycle', 2)->count())->toBe(5);
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

it('hadiah terulang sebagian: 3.520 poin baru melewati ambang 5 dan 20 di putaran dua', function () {
    seedMilestones();

    $sponsor = member('distributor');
    givePoints($sponsor, 'pr', 3519);
    $buyer = member('reseller', $sponsor);

    app(TransactionPoster::class)->post($buyer, pin('registration', 1, 600000)->id, 'join');

    $cycleSatu = MilestoneClaim::where('member_id', $sponsor->id)->where('cycle', 1)->count();
    $cycleDua = MilestoneClaim::where('member_id', $sponsor->id)->where('cycle', 2)->count();

    expect($cycleSatu)->toBe(5)
        ->and($cycleDua)->toBe(2)
        ->and(MilestoneClaim::where('member_id', $sponsor->id)->count())->toBe(7);
});

it('upgrade Reseller ke Distributor memakai PIN Rp 3.000.000', function () {
    $member = member('reseller');
    $pin = pin('upgrade', 3, 3000000, 'distributor');

    app(TransactionPoster::class)->post($member, $pin->id, 'upgrade');

    expect($member->fresh()->rank)->toBe('distributor')
        ->and(\App\Models\RankHistory::where('member_id', $member->id)->value('to_rank'))->toBe('distributor');
});

it('upgrade Agent ke Distributor memakai PIN Rp 1.800.000', function () {
    $member = member('agent');
    $pin = pin('upgrade', 2, 1800000, 'distributor');

    app(TransactionPoster::class)->post($member, $pin->id, 'upgrade');

    expect($member->fresh()->rank)->toBe('distributor');
});

it('PIN upgrade tanpa peringkat tujuan ditolak', function () {
    $member = member('reseller');
    $pin = pin('upgrade', 3, 3000000);

    expect(fn () => app(TransactionPoster::class)->post($member, $pin->id, 'upgrade'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued');
});

it('upgrade tidak menurunkan peringkat yang sudah lebih tinggi', function () {
    $member = member('manager');
    $pin = pin('upgrade', 2, 1800000, 'distributor');

    expect(fn () => app(TransactionPoster::class)->post($member, $pin->id, 'upgrade'))
        ->toThrow(RuntimeException::class);

    expect($member->fresh()->rank)->toBe('manager')
        ->and(Transaction::count())->toBe(0)
        ->and(BonusEntry::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued');
});

it('post() menolak PIN milik orang lain tanpa membuat transaksi atau bonus', function () {
    $owner = member('reseller');
    $buyer = member('reseller');
    $pin = pin('ro', 1, 600000);
    $pin->update(['assigned_member_id' => $owner->id]);

    expect(fn () => app(TransactionPoster::class)->post($buyer, $pin->id, 'ro'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0)
        ->and(BonusEntry::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued');
});

it('post() menolak PIN registrasi yang dipakai sebagai ro tanpa membuat transaksi atau bonus', function () {
    $buyer = member('reseller');
    $pin = pin('registration', 1, 600000);

    expect(fn () => app(TransactionPoster::class)->post($buyer, $pin->id, 'ro'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0)
        ->and(BonusEntry::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued');
});
