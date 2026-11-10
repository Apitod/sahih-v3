<?php

use App\Models\Admin;
use App\Models\BonusEntry;
use App\Models\Member;
use App\Models\PointEntry;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config(['sahih.pencairan_aktif' => true]);
});

function ucMember(): Member
{
    return Member::create([
        'username' => 'uc'.uniqid(),
        'name' => 'Anggota Use Case',
        'phone' => '081234567890',
        'password' => Hash::make('rahasia'),
        'status' => 'active',
        'rank' => 'reseller',
        'joined_at' => now(),
    ]);
}

function ucAdmin(): Admin
{
    return Admin::create([
        'name' => 'Admin Use Case',
        'email' => 'uc'.uniqid().'@uji.local',
        'password' => Hash::make('rahasia'),
        'role' => 'admin',
        'is_active' => true,
    ]);
}

function ucBonus(Member $member, int $amount, string $kind = 'sponsor', ?int $withdrawalId = null, ?string $at = null, string $status = 'withdrawn'): BonusEntry
{
    return BonusEntry::create([
        'member_id' => $member->id,
        'kind' => $kind,
        'amount' => $amount,
        'status' => $status,
        'withdrawal_id' => $withdrawalId,
        'available_at' => $at ?? now()->startOfMonth(),
    ]);
}

function ucPaid(Withdrawal $withdrawal): void
{
    test()->actingAs(ucAdmin(), 'admin')
        ->post("/admin/pencairan/{$withdrawal->id}/proses", ['status' => 'paid'])
        ->assertRedirect('/admin/pencairan');
}

it('kartu PA dibatasi 20 tanpa mengubah poin asli', function (int $pa, int $expected) {
    $member = member('reseller');
    givePoints($member, 'pa', $pa);

    $response = test()->actingAs($member, 'web')->get('/beranda');

    $response->assertOk();
    expect($response->viewData('poin')['pa'])->toBe($expected)
        ->and($member->pointTotal('pa'))->toBe($pa);
})->with([
    [0, 0],
    [19, 19],
    [20, 20],
    [21, 20],
    [25, 20],
]);

it('kartu PR reset ke 0 tiap 3.500 tanpa mengubah total asli', function (int $pr, int $expected) {
    $member = member('reseller');
    givePoints($member, 'pr', $pr);

    $response = test()->actingAs($member, 'web')->get('/beranda');

    $response->assertOk();
    expect($response->viewData('poin')['pr'])->toBe($expected)
        ->and($response->viewData('pr_total'))->toBe($pr);
})->with([
    [0, 0],
    [3499, 3499],
    [3500, 0],
    [3501, 1],
    [6999, 3499],
    [7000, 0],
    [7100, 100],
]);

it('validasi nominal penarikan sesuai kelipatan Rp40.000', function (int|float $amount, bool $valid) {
    $member = ucMember();
    ucBonus($member, 240000, 'sponsor', null, null, 'available');

    $response = test()->actingAs($member, 'web')->post('/tarik', [
        'amount' => $amount,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
    ]);

    if ($valid) {
        $response->assertRedirect('/saldo');
        expect(Withdrawal::count())->toBe(1)
            ->and((int) Withdrawal::sole()->amount)->toBe((int) $amount);
    } else {
        $response->assertSessionHasErrors('amount');
        expect(Withdrawal::count())->toBe(0);
    }
})->with([
    [0, false],
    [39999, false],
    [40000, false],
    [80000, false],
    [100000, false],
    [119999, false],
    [120000, true],
    [140000, false],
    [160000, true],
    [200000, true],
    [240000, true],
    [280000, false],
    [120000.5, false],
]);

it('pencairan bonus sponsor saja tidak memotong PL', function () {
    $member = ucMember();
    givePoints($member, 'pl', 5);

    $withdrawal = Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 120000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
        'status' => 'approved',
        'requested_at' => now(),
    ]);
    ucBonus($member, 120000, 'sponsor', $withdrawal->id);

    ucPaid($withdrawal);

    expect($member->pointTotal('pl'))->toBe(5)
        ->and(PointEntry::where('member_id', $member->id)->where('source_type', 'withdrawal')->count())->toBe(0);
});

it('pencairan campuran hanya memotong porsi leadership', function () {
    $member = ucMember();
    givePoints($member, 'pl', 4);

    $withdrawal = Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 200000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
        'status' => 'approved',
        'requested_at' => now(),
    ]);
    ucBonus($member, 160000, 'leadership', $withdrawal->id, now()->subMonth()->startOfMonth());
    ucBonus($member, 40000, 'sponsor', $withdrawal->id);

    ucPaid($withdrawal);

    expect($member->pointTotal('pl'))->toBe(0)
        ->and(-(int) PointEntry::where('member_id', $member->id)->where('source_type', 'withdrawal')->sum('delta'))->toBe(4);
});

it('leadership lama tanpa withdrawal_id dipotong dengan pembulatan ke bawah', function () {
    $member = ucMember();
    givePoints($member, 'pl', 5);

    $withdrawal = Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 150000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
        'status' => 'approved',
        'requested_at' => now(),
    ]);
    ucBonus($member, 150000, 'leadership', null);

    ucPaid($withdrawal);

    expect($member->pointTotal('pl'))->toBe(2)
        ->and(-(int) PointEntry::where('member_id', $member->id)->where('source_type', 'withdrawal')->sum('delta'))->toBe(3);
});

it('diagnosa menampilkan rekonsiliasi PL yang sudah dicairkan', function () {
    $member = ucMember();
    givePoints($member, 'pl', 5);

    $potong = PointEntry::create([
        'member_id' => $member->id,
        'kind' => 'pl',
        'delta' => -3,
        'source_type' => 'withdrawal',
        'source_id' => null,
        'note' => 'uji potong PL',
    ]);
    $potong->newQuery()->whereKey($potong->getKey())->update(['created_at' => now()]);

    ucBonus($member, 120000, 'leadership', null, now()->startOfMonth());

    Artisan::call('sahih:diagnosa');

    expect(Artisan::output())->toContain('pl dicairkan: 3 poin / Rp120.000');
});
