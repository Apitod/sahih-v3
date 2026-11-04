<?php

use App\Models\Pin;
use App\Models\Transaction;
use App\Models\BonusEntry;

// === Kasus 1: PIN RO dengan harga 0 ===
// Sejak penjaga harga di TransactionPoster (DECISIONS #56: Rp 500.000 per poin),
// PIN RO 1 poin berharga 0 ditolak saat dipakai, bukan diterima diam-diam.
it('PIN RO harga 0 ditolak saat ditukar', function () {
    $sponsor = member('distributor');
    $anggota = member('reseller', $sponsor);
    $kode = pin('ro', 1, 0);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertSessionHasErrors('code');

    expect($kode->fresh()->status)->toBe('issued')
        ->and(Transaction::count())->toBe(0);
});

// === Kasus 2: PIN poin 0 untuk repeat order ===
it('PIN RO dengan poin 0 tetap tercatat, sponsor tidak dapat poin', function () {
    $sponsor = member('agent');
    $anggota = member('reseller', $sponsor);
    $kode = pin('ro', 0, 0);

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
    $kode = pin('ro', 1, 500000);

    $this->actingAs($a1, 'web')->post('/tukar', ['code' => $kode->code]);
    expect($kode->fresh()->status)->toBe('used');

    $this->actingAs($a2, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertSessionHasErrors('code');

    expect(Transaction::count())->toBe(1)
        ->and($kode->fresh()->used_by_member_id)->toBe($a1->id);
});

// === Kasus 4: PIN naik peringkat tepat satu tingkat di atas ===
it('PIN naik peringkat dari reseller ke agent berhasil naik satu tingkat', function () {
    $anggota = member('reseller');
    // Reseller -> Agent = 2 pin x Rp 600.000 (DECISIONS #73).
    $kode = pin('registrasi_upgrade', 2, 1200000, 'agent');
    $kode->update(['assigned_member_id' => $anggota->id]);

    $this->actingAs($anggota, 'web')
        ->post('/tukar', ['code' => $kode->code])
        ->assertRedirect('/beranda');

    expect($anggota->fresh()->rank)->toBe('agent')
        ->and(Transaction::first()->type)->toBe('upgrade');
});

// === Kasus 5: Manager memakai PIN naik peringkat ke Distributor — ditolak ===
// CATATAN: ini MENGUNCI perilaku sekarang, bukan aturan yang sudah diputuskan.
// BUSINESS_RULES.md:163 bilang "member Manager yang memakai PIN ke Distributor tetap Manager",
// yang bisa dibaca sebagai "transaksi tetap jalan". Keputusan Owner masih terbuka (Q7).
// Kalau Owner memilih "tetap posting", uji ini harus dibalik.
it('Manager tidak bisa pakai PIN naik peringkat ke Distributor', function () {
    $anggota = member('manager');
    $kode = pin('registrasi_upgrade', 5, 3000000, 'distributor');

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
    $kode = pin('ro', 1, 500000);
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
    $kode = pin('ro', 1, 500000);

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
    // Pakai PIN naik peringkat (bukan RO) agar bonus sponsor tetap jalan.
    // Reseller -> Agent = 2 pin x Rp 600.000 = Rp 1.200.000 (DECISIONS #73), jadi bonus dihitung dari angka itu.
    $kode = pin('registrasi_upgrade', 2, 1200000, 'agent');
    $kode->update(['assigned_member_id' => $pembeli->id]);

    $this->actingAs($pembeli, 'web')->post('/tukar', ['code' => $kode->code]);

    $trx = Transaction::first();
    $bonuses = $trx->bonuses()->orderBy('level')->get();

    // Reseller (level 2) rate 15% < 18% yang sudah dibayar → tidak ada BonusEntry
    // Hanya 2 baris bonus yang tersimpan:
    expect($bonuses)->toHaveCount(2);

    // Level 1: agent 18% = Rp216.000
    expect($bonuses[0]->member_id)->toBe($sponsor->id)
        ->and($bonuses[0]->amount)->toBe(216000)
        ->and($bonuses[0]->percent_bp)->toBe(1800);

    // Level 3: manager 20%, selisih dari 18% = 2% → Rp24.000
    expect($bonuses[1]->member_id)->toBe($top->id)
        ->and($bonuses[1]->amount)->toBe(24000)
        ->and($bonuses[1]->percent_bp)->toBe(200);

    // Total harus 216.000 + 24.000 = 240.000 (20% dari 1.200.000)
    expect($trx->sponsor_bonus_total)->toBe(240000);
});
