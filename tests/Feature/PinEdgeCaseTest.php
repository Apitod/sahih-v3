<?php

use App\Models\Pin;
use App\Models\Transaction;
use App\Models\BonusEntry;

// === Kasus 1: PIN harga 0 ===
it('PIN harga 0 tetap bisa dipakai dan bonus sponsor juga 0', function () {
    $sponsor = member('distributor');
    $anggota = member('reseller', $sponsor);
    $kode = pin('ro', 1, 0);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertRedirect('/beranda');

    expect($kode->fresh()->status)->toBe('used')
        ->and(Transaction::first()->amount)->toBe(0)
        ->and(Transaction::first()->sponsor_bonus_total)->toBe(0);
});

// === Kasus 2: PIN poin 0 untuk repeat order ===
it('PIN RO dengan poin 0 tetap tercatat, sponsor tidak dapat poin', function () {
    $sponsor = member('agent');
    $anggota = member('reseller', $sponsor);
    $kode = pin('ro', 0, 500000);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertRedirect('/beranda');

    expect($kode->fresh()->status)->toBe('used')
        ->and(Transaction::first()->points)->toBe(0)
        ->and($sponsor->fresh()->pointTotal('pr'))->toBe(0);
});

// === Kasus 3: Dua anggota memakai PIN sama berurutan ===
it('anggota kedua ditolak setelah anggota pertama memakai PIN yang sama', function () {
    $a1 = member('reseller');
    $a2 = member('reseller');
    $kode = pin('ro', 1, 600000);

    $this->actingAs($a1, 'web')->post('/tukar', ['code' => $kode->code]);
    expect($kode->fresh()->status)->toBe('used');

    $this->actingAs($a2, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertSessionHasErrors('code');

    expect(Transaction::count())->toBe(1)
        ->and($kode->fresh()->used_by_member_id)->toBe($a1->id);
});

// === Kasus 4: PIN upgrade tepat satu tingkat di atas ===
it('PIN upgrade dari reseller ke agent berhasil naik satu tingkat', function () {
    $anggota = member('reseller');
    $kode = pin('upgrade', 0, 1800000, 'agent');

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertRedirect('/beranda');

    expect($anggota->fresh()->rank)->toBe('agent')
        ->and(Transaction::first()->type)->toBe('upgrade');
});

// === Kasus 5: Manager memakai PIN upgrade ke Distributor — ditolak ===
// CATATAN: ini MENGUNCI perilaku sekarang, bukan aturan yang sudah diputuskan.
// BUSINESS_RULES.md:163 bilang "member Manager yang memakai PIN ke Distributor tetap Manager",
// yang bisa dibaca sebagai "transaksi tetap jalan". Keputusan Owner masih terbuka (Q7).
// Kalau Owner memilih "tetap posting", uji ini harus dibalik.
it('Manager tidak bisa pakai PIN upgrade ke Distributor', function () {
    $anggota = member('manager');
    $kode = pin('upgrade', 0, 3000000, 'distributor');

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertSessionHasErrors('code');

    expect($kode->fresh()->status)->toBe('issued')
        ->and($anggota->fresh()->rank)->toBe('manager')
        ->and(Transaction::count())->toBe(0);
});

// === Kasus 6: PIN dititipkan ke diri sendiri — boleh ===
it('PIN yang dititipkan ke diri sendiri boleh dipakai', function () {
    $anggota = member('reseller');
    $kode = pin('ro', 1, 600000);
    $kode->update(['assigned_member_id' => $anggota->id]);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertRedirect('/beranda');

    expect($kode->fresh()->status)->toBe('used')
        ->and($kode->fresh()->used_by_member_id)->toBe($anggota->id);
});

// === Kasus 7: Kode dengan spasi di tengah ===
it('kode PIN dengan spasi di tengah tetap dikenali', function () {
    $anggota = member('reseller');
    $kode = pin('ro', 1, 600000);

    // sisip spasi di tengah kode
    $tengah = (int) (strlen($kode->code) / 2);
    $kodeDenganSpasi = substr($kode->code, 0, $tengah) . ' ' . substr($kode->code, $tengah);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kodeDenganSpasi])
        ->assertRedirect('/beranda');

    expect($kode->fresh()->status)->toBe('used');
});

// === Kasus 8: Bonus sponsor saat upline lebih rendah dari sponsor langsung ===
it('upline peringkat lebih rendah dari sponsor langsung dapat 0, selisih naik ke atas', function () {
    // Rantai: manager -> reseller -> agent -> (pembeli)
    // Sponsor langsung = agent (18%), reseller di atasnya dapat 0 (15% < 18%),
    // manager di atas dapat selisih 20%-18% = 2%.
    $top = member('manager');
    $mid = member('reseller', $top);
    $sponsor = member('agent', $mid);
    $pembeli = member('reseller', $sponsor);
    // Pakai PIN upgrade (bukan RO) agar bonus sponsor tetap jalan.
    $kode = pin('upgrade', 1, 600000, 'agent');

    $this->actingAs($pembeli, 'web')->post('/tukar', ['code' => $kode->code]);

    $trx = Transaction::first();
    $bonuses = $trx->bonuses()->orderBy('level')->get();

    // Reseller (level 2) rate 15% < 18% yang sudah dibayar → tidak ada BonusEntry
    // Hanya 2 baris bonus yang tersimpan:
    expect($bonuses)->toHaveCount(2);

    // Level 1: agent 18% = Rp108.000
    expect($bonuses[0]->member_id)->toBe($sponsor->id)
        ->and($bonuses[0]->amount)->toBe(108000)
        ->and($bonuses[0]->percent_bp)->toBe(1800);

    // Level 3: manager 20%, selisih dari 18% = 2% → Rp12.000
    expect($bonuses[1]->member_id)->toBe($top->id)
        ->and($bonuses[1]->amount)->toBe(12000)
        ->and($bonuses[1]->percent_bp)->toBe(200);

    // Total harus 108.000 + 12.000 = 120.000 (20% dari 600.000)
    expect($trx->sponsor_bonus_total)->toBe(120000);
});
