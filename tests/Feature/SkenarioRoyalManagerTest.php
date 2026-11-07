<?php

use App\Models\Member;
use App\Models\MilestoneReward;
use Database\Seeders\SkenarioRoyalManagerSeeder;

/*
 * Mengunci skenario uji Royal Manager: agen di bawah sudah Mobil sementara
 * RM-nya baru Motor, dan RM hanya sebutan untuk Manager yang punya Manager langsung.
 */
beforeEach(function () {
    foreach ([['Produk', 5], ['Voucher', 20], ['Handphone', 75], ['Motor', 500], ['Mobil', 3500]] as [$nama, $ambang]) {
        MilestoneReward::create(['name' => $nama, 'threshold' => $ambang, 'is_active' => true, 'sort' => $ambang]);
    }

    test()->seed(SkenarioRoyalManagerSeeder::class);
});

it('royal manager adalah manager yang punya manager langsung di bawahnya', function () {
    $rm = Member::where('username', 'royalmanager')->first();
    $biasa = Member::where('username', 'managerbiasa')->first();

    expect($rm->is_manager)->toBeTrue()
        ->and($rm->downlines()->where('is_manager', true)->count())->toBe(2)
        ->and($biasa->downlines()->where('is_manager', true)->count())->toBe(0);
});

it('royal manager baru sampai motor, agen di bawahnya sudah mobil', function () {
    $rm = Member::where('username', 'royalmanager')->first();
    $agen = Member::where('username', 'agen111')->first();

    $hadiahRm = $rm->milestoneClaims()->with('reward')->get()->map(fn ($c) => $c->reward->name)->all();
    $hadiahAgen = $agen->milestoneClaims()->with('reward')->get()->map(fn ($c) => $c->reward->name)->all();

    expect($rm->point_pr)->toBe(520)
        ->and($hadiahRm)->toContain('Motor')->not->toContain('Mobil')
        ->and($agen->point_pr)->toBe(3500)
        ->and($hadiahAgen)->toContain('Mobil');
});

it('royal manager dapat bonus leadership dan bonus RM yang langsung bisa ditarik', function () {
    $rm = Member::where('username', 'royalmanager')->first();

    expect((int) $rm->bonusEntries()->where('kind', 'leadership')->sum('amount'))->toBe(2000000)
        ->and((int) $rm->bonusEntries()->where('kind', 'rm')->sum('amount'))->toBe(400000)
        ->and((int) $rm->bonusEntries()->bisaDitarik()->sum('amount'))->toBe(2400000);

    test()->actingAs($rm, 'web')->get('/saldo')->assertOk()->assertSee('Rp 2.400.000');
});
