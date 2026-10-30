<?php

use App\Models\Admin;
use App\Models\Pin;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = Admin::create([
        'name' => 'Admin Uji RO',
        'email' => 'admin.ro.'.uniqid().'@uji.local',
        'password' => Hash::make('rahasia-admin'),
        'role' => 'admin',
        'is_active' => true,
    ]);
});

it('formulir buat PIN menampilkan petunjuk harga RO', function () {
    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.pin.create'))
        ->assertOk()
        ->assertSee('Khusus PIN RO: wajib Rp 500.000');
});

it('menerima PIN RO 2 poin dengan harga Rp 1.000.000', function () {
    $this->actingAs($this->admin, 'admin')
        ->post('/admin/pin', [
            'type' => 'ro',
            'points' => 2,
            'price' => 1000000,
            'quantity' => 1,
        ])
        ->assertRedirect('/admin/pin');

    $pin = Pin::first();
    expect($pin)->not->toBeNull()
        ->and($pin->type)->toBe('ro')
        ->and($pin->points)->toBe(2)
        ->and($pin->price)->toBe(1000000);
});

it('menolak PIN RO 2 poin dengan harga Rp 1.200.000', function () {
    $this->actingAs($this->admin, 'admin')
        ->post('/admin/pin', [
            'type' => 'ro',
            'points' => 2,
            'price' => 1200000,
            'quantity' => 1,
        ])
        ->assertSessionHasErrors([
            'price' => 'PIN RO 2 poin harus Rp 1.000.000.',
        ]);

    expect(Pin::count())->toBe(0);
});

it('menerima PIN RO 3 poin dengan harga Rp 1.500.000', function () {
    $this->actingAs($this->admin, 'admin')
        ->post('/admin/pin', [
            'type' => 'ro',
            'points' => 3,
            'price' => 1500000,
            'quantity' => 1,
        ])
        ->assertRedirect('/admin/pin');

    $pin = Pin::first();
    expect($pin)->not->toBeNull()
        ->and($pin->type)->toBe('ro')
        ->and($pin->points)->toBe(3)
        ->and($pin->price)->toBe(1500000);
});

it('menerima PIN RO 1 poin dengan harga Rp 500.000', function () {
    $this->actingAs($this->admin, 'admin')
        ->post('/admin/pin', [
            'type' => 'ro',
            'points' => 1,
            'price' => 500000,
            'quantity' => 1,
        ])
        ->assertRedirect('/admin/pin');

    $pin = Pin::first();
    expect($pin)->not->toBeNull()
        ->and($pin->type)->toBe('ro')
        ->and($pin->points)->toBe(1)
        ->and($pin->price)->toBe(500000);
});

it('PIN upgrade dan registrasi tidak terpengaruh aturan harga RO', function () {
    // PIN registrasi paket 3 poin: harga wajib 600.000 x 3 (BUSINESS_RULES.md §2).
    $this->actingAs($this->admin, 'admin')
        ->post('/admin/pin', [
            'type' => 'registration',
            'points' => 3,
            'price' => 1800000,
            'quantity' => 1,
        ])
        ->assertRedirect('/admin/pin');

    // PIN upgrade dengan harga bebas (misal 0 poin, 3.000.000)
    $this->actingAs($this->admin, 'admin')
        ->post('/admin/pin', [
            'type' => 'upgrade',
            'points' => 0,
            'price' => 3000000,
            'target_rank' => 'distributor',
            'quantity' => 1,
        ])
        ->assertRedirect('/admin/pin');

    expect(Pin::where('type', 'registration')->count())->toBe(1)
        ->and(Pin::where('type', 'upgrade')->count())->toBe(1);
});

it('menerima PIN RO 0 poin dengan harga Rp 0', function () {
    $this->actingAs($this->admin, 'admin')
        ->post('/admin/pin', [
            'type' => 'ro',
            'points' => 0,
            'price' => 0,
            'quantity' => 1,
        ])
        ->assertRedirect('/admin/pin');

    $pin = Pin::first();
    expect($pin)->not->toBeNull()
        ->and($pin->type)->toBe('ro')
        ->and($pin->points)->toBe(0)
        ->and($pin->price)->toBe(0);
});

it('menolak PIN RO 0 poin dengan harga bukan Rp 0', function () {
    $this->actingAs($this->admin, 'admin')
        ->post('/admin/pin', [
            'type' => 'ro',
            'points' => 0,
            'price' => 500000,
            'quantity' => 1,
        ])
        ->assertSessionHasErrors([
            'price' => 'PIN RO 0 poin harus Rp 0.',
        ]);

    expect(Pin::count())->toBe(0);
});
