<?php

use App\Models\PointEntry;

/**
 * Halaman anggota menampilkan progres peringkat dari total PR (PRD v3.0 §3.3),
 * bukan dari total semua poin. Sebelumnya memakai PA+PR+PL, jadi anggota yang
 * banyak PL-nya terlihat hampir naik peringkat padahal PR-nya belum cukup.
 */

function beriPoin(\App\Models\Member $m, string $kind, int $n): void
{
    PointEntry::create([
        'member_id' => $m->id,
        'kind' => $kind,
        'delta' => $n,
        'source_type' => 'test',
        'note' => 'uji',
    ]);
}

it('progres peringkat memakai PR, bukan total semua poin', function () {
    $m = member('reseller');

    // PL banyak, PR sedikit. Kalau dijumlah semua, terlihat hampir naik.
    beriPoin($m, 'pl', 100);
    beriPoin($m, 'pa', 5);
    beriPoin($m, 'pr', 1);

    $this->actingAs($m, 'web')
        ->get('/beranda')
        ->assertOk()
        // Target Agent = 3 PR, sekarang 1 PR → sisa 2.
        ->assertSee('Menuju Agent')
        ->assertSee('poin PR');
});

it('progres Manager memakai poin PA, bukan PR', function () {
    $m = member('distributor');
    beriPoin($m, 'pr', 50);
    beriPoin($m, 'pa', 7);

    $this->actingAs($m, 'web')
        ->get('/beranda')
        ->assertOk()
        ->assertSee('Syarat menjadi Manager')
        ->assertSee('poin PA');
});

it('anggota Manager tidak melihat progres peringkat lagi', function () {
    $m = member('manager', null, ['is_manager' => true]);
    beriPoin($m, 'pr', 100);

    $this->actingAs($m, 'web')
        ->get('/beranda')
        ->assertOk()
        ->assertDontSee('Menuju Manager');
});
