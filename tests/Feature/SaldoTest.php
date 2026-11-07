<?php

use App\Models\Admin;
use App\Models\BonusEntry;
use App\Models\Member;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Hash;

/*
 * Pencairan ditahan di produksi (DECISIONS #57, #67): route tetap terdaftar tapi
 * dijaga middleware CekPencairanAktif yang abort 404 selama config-nya false.
 * Semua test di file ini menyalakan flag supaya modulnya tetap teruji.
 */
beforeEach(function () {
    config(['sahih.pencairan_aktif' => true]);
});

function anggotaUji(): Member
{
    return Member::create([
        'username' => 'M'.uniqid(),
        'name' => 'Anggota Uji',
        'phone' => '081234567890',
        'password' => Hash::make('rahasia'),
        'status' => 'active',
        'rank' => 'reseller',
        'joined_at' => now(),
    ]);
}

function bonusUji(Member $member, int $amount, string $status = 'available', $at = null): BonusEntry
{
    // Default H+1 sudah lewat (2 hari lalu) supaya bonus bisa ditarik.
    return BonusEntry::create([
        'member_id' => $member->id,
        'kind' => 'sponsor',
        'amount' => $amount,
        'status' => $status,
        'available_at' => $at ?? now()->subDays(2),
        'created_at' => now(),
    ]);
}

it('anggota melihat saldo available dari bonus entries', function () {
    $member = anggotaUji();
    bonusUji($member, 50000, 'available');
    bonusUji($member, 75000, 'available');
    bonusUji($member, 30000, 'withdrawn');

    $response = test()->actingAs($member, 'web')->get('/saldo');

    $response->assertOk();
    expect($response->viewData('saldoAvailable'))->toBe(125000);
});

it('anggota melihat form penarikan', function () {
    $member = anggotaUji();

    $response = test()->actingAs($member, 'web')->get('/tarik');

    $response->assertOk()
        ->assertSee('bank_name')
        ->assertSee('account_number');
});

it('anggota bisa ajukan penarikan dengan data valid', function () {
    $member = anggotaUji();
    bonusUji($member, 150000, 'available');

    $response = test()->actingAs($member, 'web')->post('/tarik', [
        'amount' => 120000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'John Doe',
    ]);

    $response->assertRedirect('/saldo');
    
    $withdrawal = Withdrawal::first();
    expect($withdrawal->member_id)->toBe($member->id)
        ->and($withdrawal->amount)->toBe(120000)
        ->and($withdrawal->bank_name)->toBe('BCA')
        ->and($withdrawal->status)->toBe('requested');
});

it('penarikan ditolak kalau kurang dari minimum', function () {
    $member = anggotaUji();
    bonusUji($member, 150000, 'available');

    $response = test()->actingAs($member, 'web')->post('/tarik', [
        'amount' => 50000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'John Doe',
    ]);

    $response->assertSessionHasErrors('amount');
    expect(Withdrawal::count())->toBe(0);
});

it('penarikan ditolak kalau melebihi saldo available', function () {
    $member = anggotaUji();
    bonusUji($member, 150000, 'available');

    $response = test()->actingAs($member, 'web')->post('/tarik', [
        'amount' => 200000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'John Doe',
    ]);

    $response->assertSessionHasErrors('amount');
    expect(Withdrawal::count())->toBe(0);
});

it('penarikan menandai bonus entries sebagai withdrawn secara FIFO', function () {
    $member = anggotaUji();
    $bonus1 = bonusUji($member, 80000, 'available');
    $bonus2 = bonusUji($member, 60000, 'available');
    $bonus3 = bonusUji($member, 40000, 'available');

    test()->actingAs($member, 'web')->post('/tarik', [
        'amount' => 120000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'John Doe',
    ]);

    expect($bonus1->fresh()->status)->toBe('withdrawn')
        ->and($bonus2->fresh()->status)->toBe('withdrawn')
        ->and($bonus3->fresh()->status)->toBe('available');
});

it('admin melihat daftar penarikan', function () {
    $admin = Admin::create([
        'name' => 'Admin Uji',
        'email' => 'admin'.uniqid().'@uji.local',
        'password' => Hash::make('rahasia'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $member = anggotaUji();
    Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 150000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'John Doe',
        'status' => 'requested',
        'requested_at' => now(),
    ]);

    $response = test()->actingAs($admin, 'admin')->get('/admin/pencairan');

    $response->assertOk()
        ->assertSee('150.000')
        ->assertSee('BCA');
});

it('admin bisa approve penarikan', function () {
    $admin = Admin::create([
        'name' => 'Admin Uji',
        'email' => 'admin'.uniqid().'@uji.local',
        'password' => Hash::make('rahasia'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $member = anggotaUji();
    $withdrawal = Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 150000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'John Doe',
        'status' => 'requested',
        'requested_at' => now(),
    ]);

    $response = test()->actingAs($admin, 'admin')->post("/admin/pencairan/{$withdrawal->id}/proses", [
        'status' => 'approved',
    ]);

    $response->assertRedirect('/admin/pencairan');
    expect($withdrawal->fresh()->status)->toBe('approved')
        ->and($withdrawal->fresh()->processed_by)->toBe($admin->id);
});

it('admin bisa reject penarikan dengan note', function () {
    $admin = Admin::create([
        'name' => 'Admin Uji',
        'email' => 'admin'.uniqid().'@uji.local',
        'password' => Hash::make('rahasia'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $member = anggotaUji();
    $withdrawal = Withdrawal::create([
        'member_id' => $member->id,
        'amount' => 150000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder' => 'John Doe',
        'status' => 'requested',
        'requested_at' => now(),
    ]);

    $response = test()->actingAs($admin, 'admin')->post("/admin/pencairan/{$withdrawal->id}/proses", [
        'status' => 'rejected',
        'note' => 'Data rekening tidak valid',
    ]);

    $response->assertRedirect('/admin/pencairan');
    expect($withdrawal->fresh()->status)->toBe('rejected')
        ->and($withdrawal->fresh()->note)->toBe('Data rekening tidak valid');
});

it('entry yang melebihi sisa dipecah: bagian terpakai withdrawn, sisanya tetap available', function () {
    $member = anggotaUji();
    bonusUji($member, 80000);
    bonusUji($member, 60000);

    test()->actingAs($member, 'web')->post('/tarik', [
        'amount' => 120000, 'bank_name' => 'BCA', 'account_number' => '1', 'account_holder' => 'A',
    ]);

    expect((int) $member->bonusEntries()->where('status', 'available')->sum('amount'))->toBe(20000)
        ->and((int) $member->bonusEntries()->where('status', 'withdrawn')->sum('amount'))->toBe(120000)
        ->and((int) $member->bonusEntries()->sum('amount'))->toBe(140000);
});

it('penolakan mengembalikan bonus ke available', function () {
    $member = anggotaUji();
    bonusUji($member, 150000);

    test()->actingAs($member, 'web')->post('/tarik', [
        'amount' => 150000, 'bank_name' => 'BCA', 'account_number' => '1', 'account_holder' => 'A',
    ]);
    $w = Withdrawal::sole();
    expect((int) $member->bonusEntries()->where('status', 'available')->sum('amount'))->toBe(0);

    test()->actingAs(adminPencairan(), 'admin')->post("/admin/pencairan/{$w->id}/proses", [
        'status' => 'rejected', 'note' => 'salah rekening',
    ]);

    expect((int) $member->bonusEntries()->where('status', 'available')->sum('amount'))->toBe(150000);
});

it('status tidak boleh lompat atau mundur', function () {
    $member = anggotaUji();
    $w = Withdrawal::create([
        'member_id' => $member->id, 'amount' => 100000, 'bank_name' => 'BCA',
        'account_number' => '1', 'account_holder' => 'A', 'status' => 'requested', 'requested_at' => now(),
    ]);
    $admin = adminPencairan();

    test()->actingAs($admin, 'admin')->post("/admin/pencairan/{$w->id}/proses", ['status' => 'paid'])
        ->assertSessionHasErrors('status');
    expect($w->fresh()->status)->toBe('requested');

    foreach (['approved', 'paid'] as $s) {
        test()->actingAs($admin, 'admin')->post("/admin/pencairan/{$w->id}/proses", ['status' => $s]);
    }
    expect($w->fresh()->status)->toBe('paid');

    test()->actingAs($admin, 'admin')->post("/admin/pencairan/{$w->id}/proses", ['status' => 'rejected'])
        ->assertSessionHasErrors('status');
    expect($w->fresh()->status)->toBe('paid');
});

it('penolakan wajib menyertakan catatan', function () {
    $member = anggotaUji();
    $w = Withdrawal::create([
        'member_id' => $member->id, 'amount' => 100000, 'bank_name' => 'BCA',
        'account_number' => '1', 'account_holder' => 'A', 'status' => 'requested', 'requested_at' => now(),
    ]);

    test()->actingAs(adminPencairan(), 'admin')->post("/admin/pencairan/{$w->id}/proses", ['status' => 'rejected'])
        ->assertSessionHasErrors('note');
});

it('admin menyaring daftar penarikan per status', function () {
    $member = anggotaUji();
    foreach (['requested' => 111000, 'paid' => 222000] as $st => $amt) {
        Withdrawal::create([
            'member_id' => $member->id, 'amount' => $amt, 'bank_name' => 'BCA',
            'account_number' => '1', 'account_holder' => 'A', 'status' => $st, 'requested_at' => now(),
        ]);
    }

    test()->actingAs(adminPencairan(), 'admin')->get('/admin/pencairan?status=paid')
        ->assertOk()->assertSee('222.000')->assertDontSee('111.000');
});

it('nav anggota dan admin memuat link saldo dan pencairan saat flag menyala', function () {
    test()->actingAs(anggotaUji(), 'web')->get('/saldo')->assertSee('href="'.url('/tarik').'"', false);
    test()->actingAs(adminPencairan(), 'admin')->get('/admin/pencairan')->assertSee(url('/admin/pencairan'), false);
});

it('selama flag mati, saldo dan pencairan balik 404 dan menu tidak muncul', function () {
    config(['sahih.pencairan_aktif' => false]);

    test()->actingAs(anggotaUji(), 'web')->get('/saldo')->assertNotFound();
    test()->actingAs(anggotaUji(), 'web')->get('/tarik')->assertNotFound();
    test()->actingAs(adminPencairan(), 'admin')->get('/admin/pencairan')->assertNotFound();

    test()->actingAs(anggotaUji(), 'web')->get('/beranda')
        ->assertOk()
        ->assertDontSee(url('/saldo'), false);
});

it('penarikan ditolak kalau saldo sudah terpakai lebih dulu', function () {
    $member = anggotaUji();
    $bonus = bonusUji($member, 150000, 'available');

    // Tiru penarikan lain yang sudah memakai bonus ini antara validasi dan transaksi:
    // saldo yang dibaca di awal masih 150.000, yang terkunci di dalam transaksi 0.
    $bonus->update(['status' => 'withdrawn']);

    test()->actingAs($member, 'web')->post('/tarik', [
        'amount' => 150000, 'bank_name' => 'BCA', 'account_number' => '1', 'account_holder' => 'A',
    ])->assertSessionHasErrors('amount');

    expect(Withdrawal::count())->toBe(0);
});

function adminPencairan(): Admin
{
    return Admin::create([
        'name' => 'Admin Uji', 'email' => 'a'.uniqid().'@uji.local',
        'password' => Hash::make('rahasia'), 'role' => 'admin', 'is_active' => true,
    ]);
}

it('bonus bisa ditarik di hari yang sama saat didapat', function () {
    $member = anggotaUji();
    bonusUji($member, 200000, 'available', now()); // masuk hari ini, langsung boleh ditarik

    test()->actingAs($member, 'web')->get('/tarik')->assertOk()->assertSee('200.000');

    test()->actingAs($member, 'web')->post('/tarik', [
        'amount' => 200000,
        'bank_name' => 'BRI',
        'account_number' => '1234567890',
        'account_holder' => 'Anggota Uji',
    ])->assertRedirect('/saldo');

    expect(Withdrawal::count())->toBe(1)
        ->and(BonusEntry::where('member_id', $member->id)->where('status', 'withdrawn')->count())->toBe(1);
});

it('bonus bulanan dijadwalkan tanggal 1 tiap bulan', function () {
    $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
        ->first(fn ($e) => str_contains($e->command, 'sahih:proses-bonus-bulanan'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 1 1 * *');
});

it('bonus pukul 23.00 hari ini tetap bisa ditarik malam itu juga', function () {
    $member = anggotaUji();
    bonusUji($member, 150000, 'available', now()->setTime(23, 0));

    test()->actingAs($member, 'web')->get('/tarik')->assertOk()->assertSee('150.000');
});
