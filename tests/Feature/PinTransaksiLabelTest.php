<?php

it('/tukar memuat PIN Transaksi dan tidak memuat Tukar PIN', function () {
    $member = member('reseller');

    $response = test()->actingAs($member, 'web')->get('/tukar');

    $response->assertOk()
        ->assertSee('PIN Transaksi')
        ->assertDontSee('Tukar PIN');
});
