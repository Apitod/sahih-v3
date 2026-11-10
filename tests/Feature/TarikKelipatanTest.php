<?php

use App\Models\Admin;
use App\Models\BonusEntry;
use App\Models\Member;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config(['sahih.pencairan_aktif' => true]);
});

function anggotaTarik(): Member
{
    return Member::create([
        'username' => 'tarik'.uniqid(),
        'name' => 'Anggota Tarik',
        'phone' => '081234567890',
        'password' => Hash::make('rahasia'),
        'status' => 'active',
        'rank' => 'reseller',
        'joined_at' => now(),
    ]);
}

function bonusTarik(Member $member, int $amount): BonusEntry
{
    return BonusEntry::create([
        'member_id' => $member->id,
        'kind' => 'sponsor',
        'amount' => $amount,
        'status' => 'available',
        'available_at' => now()->subDay(),
        'created_at' => now(),
    ]);
}

it('menolak nominal 100.000 karena dibawah minimum dan bukan kelipatan 40.000', function () {
    $member = anggotaTarik();
    bonusTarik($member, 200000);

    $response = test()->actingAs($member, 'web')->post('/tarik', [
        'amount' => 100000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
    ]);

    $response->assertSessionHasErrors('amount');
    expect(Withdrawal::count())->toBe(0);
});

it('menolak nominal 140.000 karena bukan kelipatan 40.000', function () {
    $member = anggotaTarik();
    bonusTarik($member, 200000);

    $response = test()->actingAs($member, 'web')->post('/tarik', [
        'amount' => 140000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
    ]);

    $response->assertSessionHasErrors('amount');
    expect(Withdrawal::count())->toBe(0);
});

it('menerima nominal 120.000 dan membuat withdrawal requested', function () {
    $member = anggotaTarik();
    bonusTarik($member, 200000);

    $response = test()->actingAs($member, 'web')->post('/tarik', [
        'amount' => 120000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
    ]);

    $response->assertRedirect('/saldo');

    $withdrawal = Withdrawal::sole();
    expect($withdrawal->member_id)->toBe($member->id)
        ->and($withdrawal->amount)->toBe(120000)
        ->and($withdrawal->status)->toBe('requested');
});
