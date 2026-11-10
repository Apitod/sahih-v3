<?php

it('/tukar memuat PIN Transaksi dan tidak memuat Tukar PIN', function () {
    $member = member('reseller');

    $response = test()->actingAs($member, 'web')->get('/tukar');

    $response->assertOk()
        ->assertSee('PIN Transaksi')
        ->assertDontSee('Tukar PIN');
});

it('nav anggota memakai label PIN Transaksi', function () {
    $member = member('reseller');

    test()->actingAs($member, 'web')->get('/beranda')
        ->assertOk()
        ->assertSee('PIN Transaksi')
        ->assertDontSee('Tukar PIN');
});

it('panduan admin memakai label PIN Transaksi', function () {
    $admin = \App\Models\Admin::create([
        'name' => 'Admin Label',
        'email' => 'label'.uniqid().'@uji.local',
        'password' => \Illuminate\Support\Facades\Hash::make('rahasia'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    test()->actingAs($admin, 'admin')->get('/admin/panduan')
        ->assertOk()
        ->assertSee('PIN Transaksi')
        ->assertDontSee('Tukar PIN');
});
