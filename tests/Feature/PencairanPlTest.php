<?php

use App\Models\Admin;
use App\Models\BonusEntry;
use App\Models\Member;
use App\Models\PointEntry;
use App\Models\Transaction;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config(['sahih.pencairan_aktif' => true]);
});

function anggotaManager(): Member
{
    return Member::create([
        'username' => 'mgr'.uniqid(),
        'name' => 'Manager Uji',
        'phone' => '081234567890',
        'password' => Hash::make('rahasia'),
        'status' => 'active',
        'rank' => 'manager',
        'is_manager' => true,
        'joined_at' => now(),
    ]);
}

function adminPencairanPl(): Admin
{
    return Admin::create([
        'name' => 'Admin Uji',
        'email' => 'admin'.uniqid().'@uji.local',
        'password' => Hash::make('rahasia'),
        'role' => 'admin',
        'is_active' => true,
    ]);
}

function prosesPaid(Withdrawal $withdrawal): void
{
    test()->actingAs(adminPencairanPl(), 'admin')
        ->post("/admin/pencairan/{$withdrawal->id}/proses", ['status' => 'paid'])
        ->assertRedirect('/admin/pencairan');
}

it('leadership Rp200.000 withdrawn, PL 5 -> pointTotal(pl) = 0', function () {
    $member = anggotaManager();
    givePoints($member, 'pl', 5);

    $withdrawal = Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 200000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
        'status' => 'approved',
        'requested_at' => now(),
    ]);

    BonusEntry::create([
        'member_id' => $member->id,
        'kind' => 'leadership',
        'amount' => 200000,
        'status' => 'withdrawn',
        'withdrawal_id' => $withdrawal->id,
        'available_at' => now()->startOfMonth(),
    ]);

    prosesPaid($withdrawal);

    expect($member->pointTotal('pl'))->toBe(0)
        ->and(PointEntry::where('member_id', $member->id)->where('source_type', 'withdrawal')->count())->toBe(1);
});

it('dua penarikan Rp120.000 leadership -> potong 3 lalu 3 poin PL', function () {
    $member = anggotaManager();
    givePoints($member, 'pl', 6);

    $pertama = Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 120000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
        'status' => 'approved',
        'requested_at' => now(),
    ]);
    BonusEntry::create([
        'member_id' => $member->id,
        'kind' => 'leadership',
        'amount' => 120000,
        'status' => 'withdrawn',
        'withdrawal_id' => $pertama->id,
        'available_at' => now()->subMonth()->startOfMonth(),
    ]);
    prosesPaid($pertama);

    expect($member->pointTotal('pl'))->toBe(3);

    $kedua = Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 120000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
        'status' => 'approved',
        'requested_at' => now(),
    ]);
    BonusEntry::create([
        'member_id' => $member->id,
        'kind' => 'leadership',
        'amount' => 120000,
        'status' => 'withdrawn',
        'withdrawal_id' => $kedua->id,
        'available_at' => now()->startOfMonth(),
    ]);
    prosesPaid($kedua);

    expect($member->pointTotal('pl'))->toBe(0)
        ->and(-(int) PointEntry::where('member_id', $member->id)->where('source_type', 'withdrawal')->sum('delta'))->toBe(6);
});

it('withdrawal approved belum paid -> belum potong PL', function () {
    $member = anggotaManager();
    givePoints($member, 'pl', 3);

    $withdrawal = Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 120000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
        'status' => 'approved',
        'requested_at' => now(),
    ]);

    BonusEntry::create([
        'member_id' => $member->id,
        'kind' => 'leadership',
        'amount' => 120000,
        'status' => 'withdrawn',
        'withdrawal_id' => $withdrawal->id,
        'available_at' => now()->startOfMonth(),
    ]);

    expect($member->pointTotal('pl'))->toBe(3);

    prosesPaid($withdrawal);

    expect($member->pointTotal('pl'))->toBe(0);
});

it('sinkron() dua kali untuk withdrawal sama -> hanya satu baris negatif', function () {
    $member = anggotaManager();
    givePoints($member, 'pl', 3);

    $withdrawal = Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 120000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
        'status' => 'approved',
        'requested_at' => now(),
    ]);

    BonusEntry::create([
        'member_id' => $member->id,
        'kind' => 'leadership',
        'amount' => 120000,
        'status' => 'withdrawn',
        'withdrawal_id' => $withdrawal->id,
        'available_at' => now()->startOfMonth(),
    ]);

    prosesPaid($withdrawal);

    $hasil = app(\App\Services\PencairanPl::class)->sinkron($member, $withdrawal->id);

    expect($hasil)->toBe(0)
        ->and(PointEntry::where('member_id', $member->id)->where('source_type', 'withdrawal')->count())->toBe(1);
});

it('sahih:proses-bonus-bulanan abaikan baris withdrawal pada bulan yang sama', function () {
    $member = anggotaManager();
    Transaction::create([
        'code' => 'RO-'.uniqid(),
        'type' => 'ro',
        'member_id' => $member->id,
        'pin_id' => pinRo(1)->id,
        'points' => 1,
        'amount' => 500000,
        'posted_at' => now(),
    ]);

    givePoints($member, 'pl', 5, '2026-10-15 00:00:00');

    $potong = PointEntry::create([
        'member_id' => $member->id,
        'kind' => 'pl',
        'delta' => -5,
        'source_type' => 'withdrawal',
        'source_id' => null,
        'note' => 'uji potong PL',
    ]);
    $potong->newQuery()->whereKey($potong->getKey())->update(['created_at' => '2026-10-20 00:00:00']);

    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10']);

    $bonus = BonusEntry::where('member_id', $member->id)->where('kind', 'leadership')->first();
    expect($bonus)->not->toBeNull()
        ->and((int) $bonus->amount)->toBe(200000);

    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10', '--hitung-ulang' => true]);

    expect((int) $bonus->fresh()->amount)->toBe(200000);
});

it('withdrawal rejected -> tidak ada baris negatif PL', function () {
    $member = anggotaManager();
    givePoints($member, 'pl', 5);

    $withdrawal = Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 120000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
        'status' => 'requested',
        'requested_at' => now(),
    ]);

    test()->actingAs(adminPencairanPl(), 'admin')
        ->post("/admin/pencairan/{$withdrawal->id}/proses", [
            'status' => 'rejected',
            'note' => 'Data rekening tidak valid',
        ])
        ->assertRedirect('/admin/pencairan');

    expect($member->pointTotal('pl'))->toBe(5)
        ->and(PointEntry::where('member_id', $member->id)->where('source_type', 'withdrawal')->count())->toBe(0);
});
