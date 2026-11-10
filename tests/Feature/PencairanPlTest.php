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

    BonusEntry::create([
        'member_id' => $member->id,
        'kind' => 'leadership',
        'amount' => 200000,
        'status' => 'withdrawn',
        'available_at' => now()->startOfMonth(),
    ]);

    $withdrawal = Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 200000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
        'status' => 'approved',
        'requested_at' => now(),
    ]);

    prosesPaid($withdrawal);

    expect($member->pointTotal('pl'))->toBe(0)
        ->and(PointEntry::where('member_id', $member->id)->where('source_type', 'withdrawal')->count())->toBe(1);
});

it('dua penarikan Rp120.000 leadership -> potong 6 poin PL total', function () {
    $member = anggotaManager();
    givePoints($member, 'pl', 6);

    BonusEntry::create([
        'member_id' => $member->id,
        'kind' => 'leadership',
        'amount' => 240000,
        'status' => 'withdrawn',
        'available_at' => now()->startOfMonth(),
    ]);

    foreach ([120000, 120000] as $amount) {
        $withdrawal = Withdrawal::create([
            'member_id' => $member->id,
            'amount' => $amount,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'A',
            'status' => 'approved',
            'requested_at' => now(),
        ]);
        prosesPaid($withdrawal);
    }

    expect($member->pointTotal('pl'))->toBe(0)
        ->and(-(int) PointEntry::where('member_id', $member->id)->where('source_type', 'withdrawal')->sum('delta'))->toBe(6);
});

it('sinkron() dua kali untuk withdrawal sama -> hanya satu baris negatif', function () {
    $member = anggotaManager();
    givePoints($member, 'pl', 3);

    BonusEntry::create([
        'member_id' => $member->id,
        'kind' => 'leadership',
        'amount' => 120000,
        'status' => 'withdrawn',
        'available_at' => now()->startOfMonth(),
    ]);

    $withdrawal = Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 120000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
        'status' => 'approved',
        'requested_at' => now(),
    ]);

    prosesPaid($withdrawal);

    $hasil = app(\App\Services\PencairanPl::class)->sinkron($member, $withdrawal->id);

    expect($hasil)->toBe(0)
        ->and(PointEntry::where('member_id', $member->id)->where('source_type', 'withdrawal')->count())->toBe(1);
});

it('sahih:proses-bonus-bulanan setelah potongan -> nominal tidak berubah', function () {
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

    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10']);

    $bonus = BonusEntry::where('member_id', $member->id)->where('kind', 'leadership')->first();
    expect((int) $bonus->amount)->toBe(200000);

    $withdrawal = Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 200000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'A',
        'status' => 'approved',
        'requested_at' => now(),
    ]);
    prosesPaid($withdrawal);

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
