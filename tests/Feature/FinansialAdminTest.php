<?php

use App\Models\Admin;
use App\Models\Transaction;
use App\Models\BonusEntry;
use App\Models\Pin;

/** PIN uji: transactions.pin_id NOT NULL dan ber-FK. */
function pinUji(): Pin
{
    static $n = 0;
    $n++;

    return Pin::create([
        'code' => 'PIN-FIN' . $n,
        'type' => 'registrasi_upgrade',
        'points' => 1,
        'price' => 500000,
        'status' => 'used',
    ]);
}

test('admin dapat akses halaman finansial', function () {
    $admin = Admin::create(['name' => 'Admin', 'email' => 'test@admin.com', 
        'password' => bcrypt('test'), 'role' => 'admin']);
    
    $response = $this->actingAs($admin, 'admin')->get('/admin/finansial');
    
    $response->assertOk()
        ->assertSee('Finansial');
});

test('finansial tampilkan total omset', function () {
    $admin = Admin::create(['name' => 'Admin', 'email' => 'test2@admin.com', 
        'password' => bcrypt('test'), 'role' => 'admin']);
    
    $m = member();
    Transaction::create(['code' => 'T1', 'type' => 'join', 'member_id' => $m->id,
        'pin_id' => pinUji()->id, 'points' => 1, 'amount' => 1000000, 'posted_at' => now()]);
    Transaction::create(['code' => 'T2', 'type' => 'join', 'member_id' => $m->id,
        'pin_id' => pinUji()->id, 'points' => 1, 'amount' => 2000000, 'posted_at' => now()]);
    
    $response = $this->actingAs($admin, 'admin')->get('/admin/finansial');
    
    $response->assertSee('3.000.000');
});

test('finansial tampilkan payout', function () {
    $admin = Admin::create(['name' => 'Admin', 'email' => 'test3@admin.com', 
        'password' => bcrypt('test'), 'role' => 'admin']);
    $m = member();
    
    BonusEntry::create([
        'member_id' => $m->id,
        'kind' => 'sponsor',
        'amount' => 500000,
        'status' => 'available',
        'created_at' => now(),
    ]);
    
    $response = $this->actingAs($admin, 'admin')->get('/admin/finansial');
    
    $response->assertSee('500.000');
});

test('finansial hitung profit', function () {
    $admin = Admin::create(['name' => 'Admin', 'email' => 'test4@admin.com', 
        'password' => bcrypt('test'), 'role' => 'admin']);
    $m = member();
    
    Transaction::create(['code' => 'T3', 'type' => 'join', 'member_id' => $m->id,
        'pin_id' => pinUji()->id, 'points' => 1, 'amount' => 5000000, 'posted_at' => now()]);
    BonusEntry::create([
        'member_id' => $m->id,
        'kind' => 'sponsor',
        'amount' => 1000000,
        'status' => 'available',
        'created_at' => now(),
    ]);
    
    $response = $this->actingAs($admin, 'admin')->get('/admin/finansial');
    
    $response->assertSee('4.000.000'); // profit
});

test('finansial tampilkan breakdown bonus', function () {
    $admin = Admin::create(['name' => 'Admin', 'email' => 'test5@admin.com', 
        'password' => bcrypt('test'), 'role' => 'admin']);
    $m = member();
    
    BonusEntry::create([
        'member_id' => $m->id,
        'kind' => 'sponsor',
        'amount' => 300000,
        'status' => 'available',
        'created_at' => now(),
    ]);
    
    BonusEntry::create([
        'member_id' => $m->id,
        'kind' => 'leadership',
        'amount' => 400000,
        'status' => 'available',
        'created_at' => now(),
    ]);
    
    BonusEntry::create([
        'member_id' => $m->id,
        'kind' => 'rm',
        'amount' => 200000,
        'status' => 'available',
        'created_at' => now(),
    ]);
    
    $response = $this->actingAs($admin, 'admin')->get('/admin/finansial');
    
    $response->assertSee('sponsor')
        ->assertSee('leadership')
        ->assertSee('rm');
});
