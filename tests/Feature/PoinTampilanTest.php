<?php

it('PA 25 di kartu terbatas 20, pointTotal tetap 25', function () {
    $member = member('reseller');
    givePoints($member, 'pa', 25);

    $response = test()->actingAs($member, 'web')->get('/beranda');

    $response->assertOk();
    expect($response->viewData('poin'))
        ->pa->toBe(20)
        ->and($member->pointTotal('pa'))->toBe(25);
});

it('PR 7.100 di kartu jadi 100, PR 7.000 jadi 0', function () {
    $member = member('reseller');
    givePoints($member, 'pr', 7100);

    $response = test()->actingAs($member, 'web')->get('/beranda');

    $response->assertOk();
    expect($response->viewData('poin'))
        ->pr->toBe(100)
        ->and($response->viewData('pr_total'))->toBe(7100);

    $member->pointEntries()->where('kind', 'pr')->delete();
    givePoints($member, 'pr', 7000);

    $response = test()->actingAs($member, 'web')->get('/beranda');

    expect($response->viewData('poin'))->pr->toBe(0);
});
