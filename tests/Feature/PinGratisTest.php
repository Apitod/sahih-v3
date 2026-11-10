<?php

use App\Models\Admin;
use App\Models\Member;
use App\Models\Pin;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;

/**
 * PIN registrasi gratis (Owner 9 Nov).
 *
 * Admin bisa menerbitkan PIN gratis (0 poin, Rp 0) dengan peringkat target
 * reseller/agent/distributor. Member yang mendaftar dengan PIN gratis langsung
 * mendapat peringkat target, tanpa poin PR/PA pribadi, dan tanpa bonus sponsor.
 * Kalau diisi, sponsor harus sudah punya poin PA.
 */
if (! function_exists('adminGratis')) {
    function adminGratis(): Admin
    {
        return Admin::create([
            'name' => 'Admin Gratis',
            'email' => 'admin'.uniqid().'@uji.local',
            'password' => Hash::make('rahasia-admin'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}

if (! function_exists('nikGratis')) {
    function nikGratis(): string
    {
        return (string) rand(3200000000000000, 3299999999999999);
    }
}

if (! function_exists('buatPinGratis')) {
    function buatPinGratis(string $targetRank = 'agent', array $ganti = []): Pin
    {
        $admin = adminGratis();

        test()->actingAs($admin, 'admin')
            ->post('/admin/pin', array_merge([
                'type' => 'registrasi_upgrade',
                'is_free' => '1',
                'target_rank' => $targetRank,
                'quantity' => 1,
                'username' => '',
                'username_confirmation' => '',
            ], $ganti))
            ->assertRedirect('/admin/pin');

        return Pin::latest('id')->first();
    }
}

if (! function_exists('daftarGratis')) {
    function daftarGratis(Pin $pin, array $ganti = []): TestResponse
    {
        return test()->post('/daftar', array_merge([
            'username' => 'baru'.substr(uniqid(), -6),
            'name' => 'Anggota Gratis',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'phone' => '0812'.rand(1000000, 9999999),
            'address' => 'Jl. Gratis No. 1',
            'nik_ktp' => nikGratis(),
            'referral' => '',
            'code' => $pin->code,
        ], $ganti));
    }
}

it('admin bisa buat PIN gratis dengan rank target', function () {
    $pin = buatPinGratis('distributor');

    expect($pin->is_free)->toBeTrue()
        ->and($pin->target_rank)->toBe('distributor')
        ->and($pin->points)->toBe(0)
        ->and($pin->price)->toBe(0)
        ->and($pin->type)->toBe('registrasi_upgrade')
        ->and($pin->status)->toBe('issued');
});

it('daftar pakai PIN gratis -> rank target, 0 poin, sponsor NULL', function () {
    $pin = buatPinGratis('agent');

    daftarGratis($pin)
        ->assertRedirect('/beranda')
        ->assertSessionHasNoErrors();

    $baru = Member::orderByDesc('id')->first();
    expect($baru->rank)->toBe('agent')
        ->and($baru->sponsor_id)->toBeNull()
        ->and($baru->pointTotal('pr'))->toBe(0)
        ->and($baru->pointTotal('pa'))->toBe(0)
        ->and(Transaction::where('member_id', $baru->id)->where('type', 'join')->exists())->toBeTrue()
        ->and($pin->fresh()->status)->toBe('used');
});

it('tidak bisa pilih sponsor yang belum punya poin', function () {
    $sponsor = member('reseller');
    $pin = buatPinGratis('reseller');

    daftarGratis($pin, ['referral' => $sponsor->username])
        ->assertSessionHasErrors('referral');

    expect(Member::count())->toBe(1); // hanya sponsor
});

it('PIN gratis dengan poin/harga tidak sah ditolak', function () {
    $pin = Pin::create([
        'code' => 'GRATIS'.strtoupper(substr(uniqid(), -9)),
        'type' => 'registrasi_upgrade',
        'target_rank' => 'agent',
        'points' => 1,
        'price' => 600000,
        'is_free' => true,
        'status' => 'issued',
    ]);

    daftarGratis($pin)->assertSessionHasErrors('code');

    expect(Member::count())->toBe(0)
        ->and($pin->fresh()->status)->toBe('issued');
});

it('bisa pilih sponsor yang sudah punya poin', function () {
    $sponsor = member('reseller');
    givePoints($sponsor, 'pa', 1);

    $pin = buatPinGratis('distributor');

    daftarGratis($pin, ['referral' => $sponsor->username])
        ->assertRedirect('/beranda')
        ->assertSessionHasNoErrors();

    $baru = Member::orderByDesc('id')->first();
    expect($baru->sponsor_id)->toBeNull()
        ->and($baru->rank)->toBe('distributor')
        ->and($baru->pointTotal('pr'))->toBe(0)
        ->and($baru->pointTotal('pa'))->toBe(0);
});
