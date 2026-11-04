<?php

use App\Models\BonusEntry;
use App\Models\PointEntry;
use App\Models\Transaction;
use App\Services\TransactionPoster;

/**
 * Penjaga use case yang belum diuji (audit 4 Nov):
 *  1. Bonus sponsor pada jalur UPGRADE — jangan sampai upgrade lupa bayar komisi.
 *  2. Poin upgrade: PR saja (bukan PA) untuk pembeli DAN sponsor langsung.
 *  3. Rantai bonus maksimal 50 tingkat, sisa selisih hangus (DECISIONS #70).
 *  4. PL dari transaksi upgrade jaringan bawah (BUSINESS_RULES §4).
 *
 * Semua perilaku ini sudah benar di kode; file ini MENGUNCI supaya tidak
 * berubah tanpa disadari.
 */

it('upgrade membayar bonus sponsor sepanjang rantai, seperti join', function () {
    $dist = member('distributor');
    $agent = member('agent', $dist);
    $buyer = member('reseller', $agent);

    // Upgrade Reseller -> Distributor: 5 pin Rp 3.000.000.
    $pin = pin('registrasi_upgrade', 5, 3000000, 'distributor');
    $trx = app(TransactionPoster::class)->post($buyer, $pin->id, 'upgrade');

    // Agent 18% (Rp 540.000) + Distributor selisih 2% (Rp 60.000).
    $bonus = BonusEntry::where('source_transaction_id', $trx->id)->pluck('amount', 'level')->all();
    expect($bonus)->toBe([1 => 540000, 2 => 60000])
        ->and($trx->sponsor_bonus_total)->toBe(600000);
});

it('upgrade tidak memberi poin PA — PR saja untuk pembeli dan sponsor', function () {
    $sponsor = member('agent');
    $buyer = member('reseller', $sponsor);

    $pin = pin('registrasi_upgrade', 2, 1200000, 'agent');
    app(TransactionPoster::class)->post($buyer, $pin->id, 'upgrade');

    expect($buyer->fresh()->pointTotal('pr'))->toBe(2)
        ->and($buyer->fresh()->pointTotal('pa'))->toBe(0)
        ->and($sponsor->fresh()->pointTotal('pr'))->toBe(2)
        ->and($sponsor->fresh()->pointTotal('pa'))->toBe(0);
});

it('rantai bonus berhenti di 50 tingkat — sisa selisih hangus', function () {
    // Rantai 55 reseller + buyer = 56 tingkatan. Yang dibaca maksimal 50.
    $top = member('reseller');
    $cursor = $top;
    for ($i = 0; $i < 51; $i++) {
        $cursor = member('reseller', $cursor);
    }
    $buyer = member('reseller', $cursor);

    $ranks = $buyer->uplineRanks();
    expect(count($ranks))->toBe(50);

    $calc = app(\App\Services\CommissionCalculator::class);
    $alloc = $calc->distribute(600000, $ranks);

    expect(count($alloc))->toBe(50)
        ->and($alloc[count($alloc) - 1]['level'])->toBe(50)
        // Hanya level 1 (Reseller 15%) yang dibayar; sisanya 0 sampai level 50.
        ->and($calc->total($alloc))->toBe(90000);
});

it('PL dari upgrade jaringan bawah hanya untuk Manager', function () {
    $mgr = member('manager', null, ['is_manager' => true]);
    $agent = member('agent', $mgr);

    // Agent -> Distributor: 3 pin.
    $pin = pin('registrasi_upgrade', 3, 1800000, 'distributor');
    app(TransactionPoster::class)->post($agent, $pin->id, 'upgrade');

    $pl = (int) PointEntry::where('member_id', $mgr->id)->where('kind', 'pl')->sum('delta');
    expect($pl)->toBe(3);
});

it('anggota yang sudah terdaftar ditolak memakai PIN join dari service langsung', function () {
    $buyer = member('reseller');
    $pin = pin('registrasi_upgrade', 1, 600000);

    app(TransactionPoster::class)->post($buyer, $pin->id, 'join');

    $pinKedua = pin('registrasi_upgrade', 1, 600000);
    expect(fn () => app(TransactionPoster::class)->post($buyer, $pinKedua->id, 'join'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::where('member_id', $buyer->id)->where('type', 'join')->count())->toBe(1)
        ->and($pinKedua->fresh()->status)->toBe('issued');
});

it('hadiah di atas putaran (>3.500) dinilai langsung dari total PR (DESISI #71)', function () {
    // Tambah hadiah 10.000 di atas putaran.
    \App\Models\MilestoneReward::create([
        'threshold' => 10000, 'name' => 'Hadiah 10.000', 'is_active' => true, 'sort' => 99,
    ]);

    $member = member('reseller');
    givePoints($member, 'pr', 9999);

    app(\App\Services\MilestoneEvaluator::class)->evaluate($member->fresh());
    expect(\App\Models\MilestoneClaim::where('member_id', $member->id)->count())->toBe(0);

    \App\Models\PointEntry::create([
        'member_id' => $member->id, 'kind' => 'pr', 'delta' => 1,
        'source_type' => 'manual', 'note' => 'uji',
    ]);
    app(\App\Services\MilestoneEvaluator::class)->evaluate($member->fresh());

    // PR 10.000 → hadiah 10.000 sekali (dalam putaran, 5/20/75/500/3500 sudah ditutup? periksa).
    $klaim10k = \App\Models\MilestoneClaim::where('member_id', $member->id)
        ->whereHas('reward', fn ($q) => $q->where('threshold', 10000))->count();
    expect($klaim10k)->toBe(1);
});
