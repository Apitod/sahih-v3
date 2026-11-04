<?php

use App\Models\MilestoneClaim;
use App\Models\MilestoneReward;
use App\Models\PointEntry;
use App\Services\MilestoneEvaluator;
use App\Services\TransactionPoster;

/**
 * Aturan PRD v3.0 (koreksi Owner 2 November 2026).
 *
 * Yang diuji di sini:
 *  - Naik peringkat otomatis dari total PR (§3.3)
 *  - RO memberi PR + PA (§3.5, #65)
 *  - Rantai bonus sponsor 50 tingkat (§3.4, #60)
 *  - Hadiah di atas 3.500 dinilai dari total PR (§3.7)
 *  - PL hanya untuk Manager (§3.8)
 */

// ---------------------------------------------------------------- peringkat dari PR

it('peringkat naik otomatis begitu total PR mencapai ambang', function () {
    $a = member('reseller');

    // 1 PR: tetap Reseller.
    app(TransactionPoster::class)->post($a, pin('ro', 1, 500000)->id, 'ro');
    expect($a->fresh()->rank)->toBe('reseller');

    // 3 PR: jadi Agent.
    app(TransactionPoster::class)->post($a, pin('ro', 2, 1000000)->id, 'ro');
    expect($a->fresh()->rank)->toBe('agent');

    // 6 PR: jadi Distributor.
    app(TransactionPoster::class)->post($a, pin('ro', 3, 1500000)->id, 'ro');
    expect($a->fresh()->rank)->toBe('distributor');
});

it('PR dari anggota yang disponsori ikut menaikkan peringkat sponsor', function () {
    $sponsor = member('reseller');
    $a = member('reseller', $sponsor);
    $b = member('reseller', $sponsor);

    // Dua anggota masing-masing daftar 3 poin → sponsor dapat 3 PR + 3 PR = 6 PR.
    app(TransactionPoster::class)->post($a, pin('registrasi_upgrade', 3, 1800000)->id, 'join');
    expect($sponsor->fresh()->rank)->toBe('agent');   // 3 PR

    app(TransactionPoster::class)->post($b, pin('registrasi_upgrade', 3, 1800000)->id, 'join');
    expect($sponsor->fresh()->rank)->toBe('distributor');   // 6 PR
});

it('peringkat tidak pernah turun walau PR tidak berubah', function () {
    $a = member('distributor');

    app(TransactionPoster::class)->post($a, pin('ro', 1, 500000)->id, 'ro');

    expect($a->fresh()->rank)->toBe('distributor');
});

it('Manager tidak berubah peringkatnya karena PR', function () {
    $a = member('manager', null, ['is_manager' => true]);

    app(TransactionPoster::class)->post($a, pin('ro', 1, 500000)->id, 'ro');

    expect($a->fresh()->rank)->toBe('manager');
});

it('kenaikan peringkat dari PR tercatat di riwayat', function () {
    $a = member('reseller');

    app(TransactionPoster::class)->post($a, pin('ro', 3, 1500000)->id, 'ro');

    expect(\App\Models\RankHistory::where('member_id', $a->id)->count())->toBe(1)
        ->and(\App\Models\RankHistory::where('member_id', $a->id)->first()->note)
        ->toContain('total PR');
});

// ---------------------------------------------------------------- RO: PR + PA

it('RO memberi PR dan PA sekaligus kepada pembeli', function () {
    $a = member('reseller');

    app(TransactionPoster::class)->post($a, pin('ro', 2, 1000000)->id, 'ro');

    expect($a->fresh()->pointTotal('pr'))->toBe(2)
        ->and($a->fresh()->pointTotal('pa'))->toBe(2);
});

it('RO 20 pin cukup untuk jadi Manager tanpa mensponsori siapa pun', function () {
    // Konsekuensi yang sudah disetujui Owner (pilihan c, PRD v3.0 §3.5).
    $a = member('reseller');

    app(TransactionPoster::class)->post($a, pin('ro', 20, 10000000)->id, 'ro');

    $a = $a->fresh();
    expect($a->rank)->toBe('manager')
        ->and($a->is_manager)->toBeTrue()
        ->and($a->pointTotal('pa'))->toBe(20);
});

it('sponsor tidak menerima PA dari RO anggota yang disponsori', function () {
    $sponsor = member('reseller');
    $a = member('reseller', $sponsor);

    app(TransactionPoster::class)->post($a, pin('ro', 3, 1500000)->id, 'ro');

    expect($sponsor->fresh()->pointTotal('pa'))->toBe(0)
        ->and($sponsor->fresh()->pointTotal('pr'))->toBe(3);
});

// ---------------------------------------------------------------- rantai 50 tingkat

it('rantai bonus sponsor ditelusuri sampai 50 tingkat', function () {
    // Distributor di puncak, 49 Reseller di antaranya, lalu pembeli.
    // Jarak pembeli ke Distributor = 50 tingkat, jadi masih terjangkau.
    $ujung = member('distributor');
    $rantai = [$ujung];
    for ($i = 0; $i < 49; $i++) {
        $baru = member('reseller', end($rantai));
        $rantai[] = $baru;
    }

    $pembeli = member('reseller', end($rantai));

    app(TransactionPoster::class)->post($pembeli, pin('registrasi_upgrade', 1, 600000)->id, 'join');

    // Distributor di puncak harus tetap dapat selisih 5% walau jaraknya 50 tingkat.
    $bonus = \App\Models\BonusEntry::where('member_id', $ujung->id)->sum('amount');
    expect((int) $bonus)->toBe(30000);   // 5% x Rp 600.000
});

it('tingkat ke-51 ke atas tidak dijangkau lagi', function () {
    // Distributor di puncak, 50 Reseller di antaranya, lalu pembeli.
    // Jarak = 51 tingkat, melewati batas 50 → Distributor tidak dapat.
    $ujung = member('distributor');
    $rantai = [$ujung];
    for ($i = 0; $i < 50; $i++) {
        $baru = member('reseller', end($rantai));
        $rantai[] = $baru;
    }

    $pembeli = member('reseller', end($rantai));

    app(TransactionPoster::class)->post($pembeli, pin('registrasi_upgrade', 1, 600000)->id, 'join');

    expect((int) \App\Models\BonusEntry::where('member_id', $ujung->id)->sum('amount'))->toBe(0);
});

it('uplineRanks membaca batas dari config', function () {
    $a = member('reseller');
    $b = member('reseller', $a);

    expect($b->uplineRanks())->toHaveCount(1);

    config(['sahih.sponsor_chain_max_level' => 1]);
    $c = member('reseller', $b);
    expect($c->uplineRanks())->toHaveCount(1);   // dibatasi 1
});

// ---------------------------------------------------------------- milestone

it('hadiah di dalam putaran terbuka lagi setelah 3.500 poin', function () {
    $a = member('reseller');
    $hadiah = MilestoneReward::create(['threshold' => 5, 'name' => 'Produk', 'is_active' => true, 'sort' => 0]);

    PointEntry::create(['member_id' => $a->id, 'kind' => 'pr', 'delta' => 3505, 'source_type' => 'test', 'note' => 'uji']);

    app(MilestoneEvaluator::class)->evaluate($a);

    // 3.500 penuh (1 putaran) + 5 sisa → hadiah ambang 5 dapat 2 kali.
    $klaim = MilestoneClaim::where('member_id', $a->id)
        ->where('milestone_reward_id', $hadiah->id)
        ->count();

    expect($klaim)->toBe(2);
});

it('hadiah di atas 3.500 dinilai dari total PR, bukan diblokir', function () {
    $a = member('reseller');
    $besar = MilestoneReward::create(['threshold' => 10000, 'name' => 'Emas', 'is_active' => true, 'sort' => 0]);

    // Di bawah ambang: belum dapat.
    PointEntry::create(['member_id' => $a->id, 'kind' => 'pr', 'delta' => 9999, 'source_type' => 'test', 'note' => 'uji']);
    app(MilestoneEvaluator::class)->evaluate($a);
    expect(MilestoneClaim::where('member_id', $a->id)->count())->toBe(0);

    // Lewat ambang: dapat sekali.
    PointEntry::create(['member_id' => $a->id, 'kind' => 'pr', 'delta' => 1, 'source_type' => 'test', 'note' => 'uji']);
    app(MilestoneEvaluator::class)->evaluate($a);
    expect(MilestoneClaim::where('member_id', $a->id)
        ->where('milestone_reward_id', $besar->id)->count())->toBe(1);

    // 20.000 total: dapat dua kali.
    PointEntry::create(['member_id' => $a->id, 'kind' => 'pr', 'delta' => 10000, 'source_type' => 'test', 'note' => 'uji']);
    app(MilestoneEvaluator::class)->evaluate($a);
    expect(MilestoneClaim::where('member_id', $a->id)
        ->where('milestone_reward_id', $besar->id)->count())->toBe(2);
});

it('hadiah 3.500 sendiri tetap terbuka tiap putaran penuh', function () {
    $a = member('reseller');
    $mobil = MilestoneReward::create(['threshold' => 3500, 'name' => 'Mobil', 'is_active' => true, 'sort' => 0]);

    PointEntry::create(['member_id' => $a->id, 'kind' => 'pr', 'delta' => 7000, 'source_type' => 'test', 'note' => 'uji']);
    app(MilestoneEvaluator::class)->evaluate($a);

    expect(MilestoneClaim::where('member_id', $a->id)
        ->where('milestone_reward_id', $mobil->id)->count())->toBe(2);
});

// ---------------------------------------------------------------- PL hanya Manager

it('upline non-Manager tidak dicatat poin leadership sama sekali', function () {
    $upline = member('distributor');   // Distributor, belum Manager
    $a = member('reseller', $upline);

    app(TransactionPoster::class)->post($a, pin('registrasi_upgrade', 3, 1800000)->id, 'join');

    expect(PointEntry::where('member_id', $upline->id)->where('kind', 'pl')->count())->toBe(0);
});

it('upline Manager menerima poin leadership sebesar pin transaksi', function () {
    $upline = member('manager', null, ['is_manager' => true]);
    $a = member('reseller', $upline);

    app(TransactionPoster::class)->post($a, pin('registrasi_upgrade', 3, 1800000)->id, 'join');

    expect($upline->fresh()->pointTotal('pl'))->toBe(3);
});
