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
        ->assertSee('PIN RO: Rp 500.000')
        // Manager tidak dijual lewat PIN (DECISIONS #58).
        ->assertSee('Manager tidak dijual lewat PIN')
        ->assertDontSee('<option value="manager"', false);
});

it('formulir buat PIN hanya menawarkan dua jenis PIN', function () {
    $html = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.pin.create'))
        ->assertOk()
        ->getContent();

    // Tiga nilai lama tidak boleh muncul lagi sebagai pilihan jenis.
    expect($html)->toContain('value="registrasi_upgrade"')
        ->and($html)->toContain('value="ro"')
        ->and(substr_count($html, 'name="type"'))->toBe(1);

    preg_match('/<select[^>]*name="type".*?<\/select>/s', $html, $select);
    expect($select)->not->toBeEmpty()
        ->and(substr_count($select[0], '<option'))->toBe(2)
        ->and($select[0])->not->toContain('value="registration"')
        ->and($select[0])->not->toContain('value="upgrade"');
});

it('formulir buat PIN meminta username pemegang dua kali', function () {
    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.pin.create'))
        ->assertOk()
        ->assertSee('name="username"', false)
        ->assertSee('name="username_confirmation"', false);
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

it('PIN gabungan dan PIN RO tidak terpengaruh aturan harga RO', function () {
    // PIN registrasi/upgrade paket 3 pin: harga wajib 600.000 x 3 (BUSINESS_RULES.md §2).
    $this->actingAs($this->admin, 'admin')
        ->post('/admin/pin', [
            'type' => 'registrasi_upgrade',
            'points' => 3,
            'price' => 1800000,
            'quantity' => 1,
        ])
        ->assertRedirect('/admin/pin');

    // PIN naik peringkat: harga wajib 600.000 x jumlah pin (DECISIONS #73), bukan harga RO.
    $anggota = member('reseller');
    $this->actingAs($this->admin, 'admin')
        ->post('/admin/pin', [
            'type' => 'registrasi_upgrade',
            'points' => 5,
            'price' => 3000000,
            'target_rank' => 'distributor',
            'quantity' => 1,
            'username' => $anggota->username,
            'username_confirmation' => $anggota->username,
        ])
        ->assertRedirect('/admin/pin');

    // Hanya dua jenis yang ada; keduanya tersimpan sebagai registrasi_upgrade.
    expect(Pin::where('type', 'registrasi_upgrade')->count())->toBe(2)
        ->and(Pin::where('type', 'ro')->count())->toBe(0);
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
