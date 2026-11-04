<?php

use App\Models\MilestoneClaim;
use App\Models\MilestoneReward;
use App\Models\Transaction;
use App\Services\TransactionPoster;

/**
 * Regresi untuk temuan audit apitod Stage C–D dan E (SAH-19/SAH-20).
 * Setiap uji mengunci satu aturan yang tertulis di BUSINESS_RULES.md / DECISIONS.md.
 */

it('pembeli RO sendiri ikut dinilai tangga hadiahnya (BUSINESS_RULES §7–§8)', function () {
    foreach ([5, 20, 75, 500, 3500] as $i => $threshold) {
        MilestoneReward::create([
            'threshold' => $threshold,
            'name' => "Hadiah {$threshold} poin",
            'is_active' => true,
            'sort' => $i,
        ]);
    }

    $buyer = member('distributor');
    $pin = pin('ro', 3500, 1750000000);

    app(TransactionPoster::class)->post($buyer, $pin->id, 'ro');

    // 3.500 poin melewati 5, 20, 75, 500, 3.500 — semuanya jadi hak pembeli.
    expect(MilestoneClaim::where('member_id', $buyer->id)->count())->toBe(5);
});

it('hadiah tidak dobel walau pembeli dan sponsor dinilai dua kali', function () {
    foreach ([5, 20, 75, 500, 3500] as $i => $threshold) {
        MilestoneReward::create([
            'threshold' => $threshold,
            'name' => "Hadiah {$threshold} poin",
            'is_active' => true,
            'sort' => $i,
        ]);
    }

    $sponsor = member('distributor');
    $buyer = member('reseller', $sponsor);
    $pin = pin('ro', 5, 2500000);

    app(TransactionPoster::class)->post($buyer, $pin->id, 'ro');

    // Pembeli 5 poin, sponsor 5 poin PR: masing-masing satu klaim ambang 5.
    expect(MilestoneClaim::where('member_id', $buyer->id)->count())->toBe(1)
        ->and(MilestoneClaim::where('member_id', $sponsor->id)->count())->toBe(1)
        ->and(MilestoneClaim::count())->toBe(2);
});

it('PIN gabungan di luar paket 1/3/6 poin ditolak saat dibuat untuk mendaftar', function () {
    $admin = adminUji();

    $this->actingAs($admin, 'admin')
        ->post('/admin/pin', ['type' => 'registrasi_upgrade', 'points' => 2, 'price' => 1200000, 'quantity' => 1])
        ->assertSessionHasErrors('points');

    expect(\App\Models\Pin::count())->toBe(0);
});

it('PIN gabungan dengan harga bukan 600.000 x poin ditolak', function () {
    $admin = adminUji();

    $this->actingAs($admin, 'admin')
        ->post('/admin/pin', ['type' => 'registrasi_upgrade', 'points' => 6, 'price' => 100, 'quantity' => 1])
        ->assertSessionHasErrors('price');

    expect(\App\Models\Pin::count())->toBe(0);
});

it('PIN naik peringkat ke Reseller ditolak saat dibuat', function () {
    $admin = adminUji();

    $this->actingAs($admin, 'admin')
        ->post('/admin/pin', ['type' => 'registrasi_upgrade', 'points' => 0, 'price' => 1800000, 'quantity' => 1, 'target_rank' => 'reseller'])
        ->assertSessionHasErrors('target_rank');

    expect(\App\Models\Pin::count())->toBe(0);
});

it('anggota tidak bisa join dua kali', function () {
    $sponsor = member('distributor');
    $buyer = member('reseller', $sponsor);

    $pertama = pin('registrasi_upgrade', 1, 600000);
    $kedua = pin('registrasi_upgrade', 6, 3600000);

    $poster = app(TransactionPoster::class);
    $poster->post($buyer, $pertama->id, 'join');

    expect(fn () => $poster->post($buyer, $kedua->id, 'join'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::where('type', 'join')->count())->toBe(1)
        ->and($kedua->fresh()->status)->toBe('issued');
});

it('PIN pendaftaran yang dititipkan tidak tampil di halaman tukar', function () {
    $anggota = member('reseller');
    // PIN gabungan tanpa peringkat tujuan = PIN pendaftaran; hanya admin yang memakainya.
    $pendaftaran = pin('registrasi_upgrade', 1, 600000);
    $pendaftaran->update(['assigned_member_id' => $anggota->id]);

    $this->actingAs($anggota, 'web')
        ->get('/tukar')
        ->assertOk()
        ->assertDontSee($pendaftaran->code);
});

it('PIN naik peringkat yang dititipkan tampil di halaman tukar', function () {
    $anggota = member('reseller');
    $naik = pin('registrasi_upgrade', 2, 1200000, 'agent');
    $naik->update(['assigned_member_id' => $anggota->id]);

    $this->actingAs($anggota, 'web')
        ->get('/tukar')
        ->assertOk()
        ->assertSee($naik->code);
});

it('PIN naik peringkat yang harganya tidak cocok dengan peringkat pembeli ditolak (DECISIONS #73)', function () {
    // PIN dibuat untuk Reseller -> Agent (2 pin = Rp 1.200.000), tapi pemakainya sudah Agent.
    $anggota = member('agent');
    $pin = pin('registrasi_upgrade', 2, 1200000, 'agent');

    expect(fn () => app(TransactionPoster::class)->post($anggota, $pin->id, 'upgrade'))
        ->toThrow(RuntimeException::class);

    expect($anggota->fresh()->rank)->toBe('agent')
        ->and($pin->fresh()->status)->toBe('issued')
        ->and(Transaction::count())->toBe(0);
});

it('harga PIN naik peringkat mengikuti jumlah pin x Rp 600.000 untuk tiap jalur', function () {
    $harga = app(\App\Services\PackagePrice::class);

    // Selisih poin antar peringkat, dikali harga per pin (DECISIONS #73).
    expect($harga->upgradePrice('reseller', 'agent'))->toBe(1200000)      // 2 pin
        ->and($harga->upgradePrice('reseller', 'distributor'))->toBe(3000000)  // 5 pin
        ->and($harga->upgradePrice('agent', 'distributor'))->toBe(1800000);    // 3 pin
});

it('putaran hadiah tetap 3.500 walau hadiah 3.500 dinonaktifkan (DECISIONS #61)', function () {
    foreach ([5, 20, 75, 500, 3500] as $i => $threshold) {
        MilestoneReward::create([
            'threshold' => $threshold,
            'name' => "Hadiah {$threshold} poin",
            'is_active' => $threshold !== 3500,
            'sort' => $i,
        ]);
    }

    $anggota = member('distributor');
    givePoints($anggota, 'pr', 4000);

    app(\App\Services\MilestoneEvaluator::class)->evaluate($anggota);

    // Satu putaran penuh (3.500) + sisa 500: 4 ambang aktif di putaran 1,
    // 4 ambang aktif lagi di putaran 2. Kalau putaran ikut ambang tertinggi yang
    // aktif (500), hasilnya akan 32 klaim.
    expect(MilestoneClaim::where('member_id', $anggota->id)->count())->toBe(8)
        ->and(MilestoneClaim::where('member_id', $anggota->id)->where('cycle', 2)->count())->toBe(4);
});
