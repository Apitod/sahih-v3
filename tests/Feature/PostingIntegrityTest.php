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
    $pin = pin('ro', 1, 500000);

    expect(fn () => app(TransactionPoster::class)->post($buyer, $pin->id, 'ro'))
        ->toThrow(RuntimeException::class);

    expect($pin->fresh()->status)->toBe('issued')
        ->and(Transaction::count())->toBe(0);
});

it('PIN naik peringkat ke Manager ditolak — Manager otomatis, bukan dibeli (DECISIONS #58)', function () {
    $buyer = member('distributor');

    // PIN dibuat SAH menurut aturan harga (6 pin x Rp600.000) dan menyebut Manager.
    // Dengan begitu yang menolak benar-benar penjaga Manager, bukan penjaga harga
    // atau penjaga 0 pin — dulu uji ini memakai PIN 0 pin/Rp0 sehingga lulus karena
    // alasan yang salah dan aturan #58 tidak pernah benar-benar teruji.
    $pin = pin('registrasi_upgrade', 6, 3600000, 'manager');

    expect(fn () => app(TransactionPoster::class)->post($buyer, $pin->id, 'upgrade'))
        ->toThrow(RuntimeException::class, 'Manager tidak bisa dibeli lewat PIN');

    expect($buyer->fresh()->rank)->toBe('distributor')
        ->and($buyer->fresh()->is_manager)->toBeFalse()
        ->and(Transaction::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued');
});

it('menolak PIN yang sama dipakai dua kali', function () {
    $buyer = member('reseller');
    $pin = pin('registrasi_upgrade', 1, 600000);
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

    app(TransactionPoster::class)->post($buyer, pin('registrasi_upgrade', 3, 1800000)->id, 'join');

    expect((int) PointEntry::where('member_id', $sponsor->id)->where('kind', 'pa')->sum('delta'))->toBe(3)
        ->and((int) PointEntry::where('member_id', $sponsor->id)->where('kind', 'pr')->sum('delta'))->toBe(3)
        ->and($sponsor->fresh()->point_pa)->toBe(3)
        ->and($sponsor->fresh()->point_pr)->toBe(3);
});

it('RO menambah PR dan PA pembeli, serta menaikkan peringkat dari total PR', function () {
    $sponsor = member('reseller');
    $buyer = member('reseller', $sponsor);

    // PRD v3.0 §3.5: RO memberi PR + PA ke pembeli (koreksi Owner 2 Nov, #65).
    // §3.3: 10 PR melewati ambang Distributor (6 PR), jadi peringkat naik otomatis.
    app(TransactionPoster::class)->post($buyer, pin('ro', 10, 5000000)->id, 'ro');

    expect($buyer->fresh()->rank)->toBe('distributor')
        ->and($buyer->fresh()->pointTotal('pr'))->toBe(10)
        ->and($buyer->fresh()->pointTotal('pa'))->toBe(10)
        // Sponsor hanya dapat PR dari RO, tidak dapat PA.
        ->and((int) PointEntry::where('member_id', $sponsor->id)->where('kind', 'pr')->sum('delta'))->toBe(10)
        ->and((int) PointEntry::where('member_id', $sponsor->id)->where('kind', 'pa')->sum('delta'))->toBe(0);
});

it('Manager otomatis saat PA mencapai 20 dan sudah Distributor', function () {
    $sponsor = member('distributor');
    givePoints($sponsor, 'pa', 19);
    $buyer = member('reseller', $sponsor);

    app(TransactionPoster::class)->post($buyer, pin('registrasi_upgrade', 1, 600000)->id, 'join');

    $sponsor->refresh();
    expect($sponsor->rank)->toBe('manager')
        ->and($sponsor->is_manager)->toBeTrue()
        ->and($sponsor->manager_since)->not->toBeNull();
});

it('belum jadi Manager kalau masih Reseller meski PA sudah 20', function () {
    $sponsor = member('reseller');
    givePoints($sponsor, 'pa', 20);
    $buyer = member('reseller', $sponsor);

    app(TransactionPoster::class)->post($buyer, pin('registrasi_upgrade', 1, 600000)->id, 'join');

    expect($sponsor->fresh()->rank)->toBe('reseller');
});

it('hadiah poin muncul saat ambang terlewati, dan tidak mengurangi poin', function () {
    seedMilestones();

    $sponsor = member('distributor');
    givePoints($sponsor, 'pr', 19);
    $buyer = member('reseller', $sponsor);

    app(TransactionPoster::class)->post($buyer, pin('registrasi_upgrade', 1, 600000)->id, 'join');

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

    app(TransactionPoster::class)->post($buyer, pin('registrasi_upgrade', 1, 600000)->id, 'join');

    // 7.000 poin = dua putaran penuh, lima ambang tiap putaran.
    expect(MilestoneClaim::where('member_id', $sponsor->id)->count())->toBe(10)
        ->and(MilestoneClaim::where('member_id', $sponsor->id)->where('cycle', 2)->count())->toBe(5);
});

it('saldo bonus hanya menghitung bonus yang sudah cair', function () {
    $d = member('distributor');
    $buyer = member('reseller', $d);

    app(TransactionPoster::class)->post($buyer, pin('registrasi_upgrade', 6, 3600000)->id, 'join');

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

    app(TransactionPoster::class)->post($buyer, pin('registrasi_upgrade', 1, 600000)->id, 'join');

    $cycleSatu = MilestoneClaim::where('member_id', $sponsor->id)->where('cycle', 1)->count();
    $cycleDua = MilestoneClaim::where('member_id', $sponsor->id)->where('cycle', 2)->count();

    expect($cycleSatu)->toBe(5)
        ->and($cycleDua)->toBe(2)
        ->and(MilestoneClaim::where('member_id', $sponsor->id)->count())->toBe(7);
});

it('upgrade Reseller ke Distributor memakai PIN 5 pin Rp 3.000.000', function () {
    $member = member('reseller');
    // 5 pin x Rp 600.000 (DECISIONS #73).
    $pin = pin('registrasi_upgrade', 5, 3000000, 'distributor');

    app(TransactionPoster::class)->post($member, $pin->id, 'upgrade');

    expect($member->fresh()->rank)->toBe('distributor')
        ->and(\App\Models\RankHistory::where('member_id', $member->id)->value('to_rank'))->toBe('distributor');
});

it('upgrade Agent ke Distributor memakai PIN 3 pin Rp 1.800.000', function () {
    $member = member('agent');
    // 3 pin x Rp 600.000 (DECISIONS #73).
    $pin = pin('registrasi_upgrade', 3, 1800000, 'distributor');

    app(TransactionPoster::class)->post($member, $pin->id, 'upgrade');

    expect($member->fresh()->rank)->toBe('distributor');
});

it('PIN gabungan tanpa peringkat tujuan ditolak saat dipakai untuk naik peringkat', function () {
    $member = member('reseller');
    // 5 pin tapi tanpa peringkat tujuan = PIN pendaftaran, bukan PIN naik peringkat.
    $pin = pin('registrasi_upgrade', 5, 3000000);

    expect(fn () => app(TransactionPoster::class)->post($member, $pin->id, 'upgrade'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued');
});

it('upgrade tidak menurunkan peringkat yang sudah lebih tinggi', function () {
    $member = member('manager');
    $pin = pin('registrasi_upgrade', 3, 1800000, 'distributor');

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
    $pin = pin('ro', 1, 500000);
    $pin->update(['assigned_member_id' => $owner->id]);

    expect(fn () => app(TransactionPoster::class)->post($buyer, $pin->id, 'ro'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0)
        ->and(BonusEntry::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued');
});

it('post() menolak PIN pendaftaran yang dipakai sebagai ro tanpa membuat transaksi atau bonus', function () {
    $buyer = member('reseller');
    // PIN gabungan tanpa peringkat tujuan = PIN pendaftaran, bukan PIN RO.
    $pin = pin('registrasi_upgrade', 1, 600000);

    expect(fn () => app(TransactionPoster::class)->post($buyer, $pin->id, 'ro'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0)
        ->and(BonusEntry::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued');
});

it('post() menolak PIN RO yang dipakai sebagai join maupun upgrade', function () {
    $buyer = member('reseller');
    $pin = pinRo(2);

    expect(fn () => app(TransactionPoster::class)->post($buyer, $pin->id, 'join'))
        ->toThrow(RuntimeException::class)
        ->and(fn () => app(TransactionPoster::class)->post($buyer, $pin->id, 'upgrade'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued');
});

it('post() menolak jenis PIN di luar dua jenis yang dikenal', function () {
    $buyer = member('reseller');
    // Sisa data lama: jenis `registration` / `upgrade` tidak boleh diterima lagi.
    $pin = pin('registration', 1, 600000);

    expect(fn () => app(TransactionPoster::class)->post($buyer, $pin->id, 'join'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued');
});
