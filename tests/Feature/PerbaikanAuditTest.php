<?php

use App\Models\MilestoneClaim;
use App\Models\MilestoneReward;
use App\Models\Transaction;
use App\Services\MilestoneEvaluator;
use App\Services\RankResolver;
use App\Services\TransactionPoster;

/**
 * Regresi untuk putaran audit 31 Okt 2026.
 * Setiap uji mengunci satu temuan yang sudah diperbaiki, supaya tidak balik lagi.
 */

// === T-01: harga PIN RO diperiksa lagi saat PIN dipakai (DECISIONS #56) ===
it('PIN RO dengan harga lama ditolak saat ditukar', function () {
    $anggota = member('reseller');
    // Harga per poin lama (Rp 600.000) — dulu lolos, sekarang harus ditolak.
    $kode = pin('ro', 3, 1800000);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertSessionHasErrors('code');

    expect($kode->fresh()->status)->toBe('issued')
        ->and(Transaction::count())->toBe(0)
        ->and($anggota->fresh()->pointTotal('pr'))->toBe(0);
});

it('PIN RO dengan harga benar tetap diterima', function () {
    $anggota = member('reseller');
    $kode = pin('ro', 3, 1500000);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertRedirect('/beranda');

    expect($kode->fresh()->status)->toBe('used')
        ->and(Transaction::first()->amount)->toBe(1500000)
        ->and($anggota->fresh()->pointTotal('pr'))->toBe(3);
});

// === T-07: PIN registrasi diperiksa harganya dan paketnya saat dipakai ===
it('PIN registrasi dengan harga salah ditolak saat posting', function () {
    $pembeli = member('reseller');
    $kode = pin('registration', 1, 500000); // harga RO, bukan harga pendaftaran

    expect(fn () => app(TransactionPoster::class)->post($pembeli, $kode->id, 'join'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0)
        ->and($kode->fresh()->status)->toBe('issued');
});

it('PIN registrasi dengan poin di luar paket ditolak saat posting', function () {
    $pembeli = member('reseller');
    $kode = pin('registration', 2, 1200000); // paket 2 poin tidak ada di §2

    expect(fn () => app(TransactionPoster::class)->post($pembeli, $kode->id, 'join'))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0);
});

// === T-04: hadiah di atas putaran 3.500 tidak boleh diberikan terlalu cepat ===
it('hadiah berambang di atas 3.500 tidak diberikan saat PR baru 3.500', function () {
    foreach ([5, 20, 75, 500, 3500] as $i => $threshold) {
        MilestoneReward::create([
            'threshold' => $threshold,
            'name' => "Hadiah {$threshold} poin",
            'is_active' => true,
            'sort' => $i,
        ]);
    }

    $besar = MilestoneReward::create([
        'threshold' => 10000,
        'name' => 'Hadiah 10.000 poin',
        'is_active' => true,
        'sort' => 9,
    ]);

    $anggota = member('reseller');
    givePoints($anggota, 'pr', 3500);

    $dibuat = app(MilestoneEvaluator::class)->evaluate($anggota);

    // Lima hadiah putaran pertama tetap jadi hak dia; hadiah 10.000 belum.
    expect($dibuat)->toBe(5)
        ->and(MilestoneClaim::where('member_id', $anggota->id)->count())->toBe(5)
        ->and(MilestoneClaim::where('milestone_reward_id', $besar->id)->count())->toBe(0);
});

it('hadiah 3.500 tetap berulang tiap putaran penuh', function () {
    foreach ([5, 20, 75, 500, 3500] as $i => $threshold) {
        MilestoneReward::create([
            'threshold' => $threshold,
            'name' => "Hadiah {$threshold} poin",
            'is_active' => true,
            'sort' => $i,
        ]);
    }

    $anggota = member('reseller');
    givePoints($anggota, 'pr', 4000);

    $dibuat = app(MilestoneEvaluator::class)->evaluate($anggota);

    // Satu putaran penuh (5 hadiah) + 500 poin sisa yang membuka 4 hadiah lagi.
    expect($dibuat)->toBe(9)
        ->and(MilestoneClaim::where('member_id', $anggota->id)->count())->toBe(9);
});

// === T-09: peringkat tak dikenal ditolak, bukan dianggap peringkat terendah ===
it('peringkat tak dikenal ditolak saat dibandingkan', function () {
    expect(fn () => app(RankResolver::class)->weight('direktur'))
        ->toThrow(InvalidArgumentException::class);
});

// === T-05: batas per IP dinaikkan, batas per akun tetap ketat ===
it('dua belas penukaran PIN dari satu IP tidak kena batas', function () {
    $anggota = member('reseller');

    for ($i = 0; $i < 12; $i++) {
        $this->actingAs($anggota, 'web')
            ->post('/tukar', ['code' => 'SALAHKODE'.$i])
            ->assertSessionHasErrors('code');
    }

    // Tidak ada satu pun yang jadi 429.
    expect(true)->toBeTrue();
});

it('sebelas tebakan password untuk akun yang sama tetap diblokir', function () {
    $anggota = member('reseller');

    for ($i = 0; $i < 10; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.9.9'])
            ->post('/masuk', ['username' => $anggota->username, 'password' => 'salah']);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '192.168.9.9'])
        ->post('/masuk', ['username' => $anggota->username, 'password' => 'salah'])
        ->assertStatus(429);
});
