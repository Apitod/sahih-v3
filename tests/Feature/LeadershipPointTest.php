<?php

use App\Models\PointEntry;
use App\Services\TransactionPoster;

/**
 * Poin leadership (PRD v3.0 §3.8, koreksi Owner 2 November 2026).
 *
 * Aturan yang diuji:
 *  - Hanya Manager yang menerima PL. Non-Manager hangus, tidak dicatat.
 *  - Manager yang RO sendiri ikut menerima PL dari transaksinya.
 *  - PL mengalir ke atas sampai Manager pertama, lalu berhenti (kompresi #62).
 *  - Pembeli yang sudah Manager menutup hak dari struktur itu.
 */

function pl(\App\Models\Member|int $member): int
{
    $id = $member instanceof \App\Models\Member ? $member->id : $member;

    return (int) PointEntry::where('member_id', $id)->where('kind', 'pl')->sum('delta');
}

it('RO 3 poin memberi 3 poin leadership ke Manager pertama saja', function () {
    $d = member('distributor');          // di atas Manager, tidak dapat apa-apa
    $c = member('manager', $d, ['is_manager' => true]);   // Manager pertama
    $b = member('agent', $c);            // level 1, bukan Manager
    $x = member('agent', $b);            // yang bertransaksi

    app(TransactionPoster::class)->post($x, pin('ro', 3, 1500000)->id, 'ro');

    expect(pl($c))->toBe(3)      // Manager pertama dapat
        ->and(pl($b))->toBe(0)   // bukan Manager → hangus, tidak dicatat
        ->and(pl($d))->toBe(0)   // di atas Manager, rantai sudah berhenti
        ->and(pl($x))->toBe(0);  // pembeli bukan Manager
});

it('join 3 poin tidak memberi poin leadership kalau upline bukan Manager', function () {
    $b = member('agent');
    $x = member('reseller', $b);

    app(TransactionPoster::class)->post($x, pin('registrasi_upgrade', 3, 1800000)->id, 'join');

    // Upline masih Agent, belum Manager → PL hangus, tidak dicatat.
    expect(pl($b))->toBe(0);
});

it('join 3 poin memberi poin leadership kalau upline sudah Manager', function () {
    $b = member('manager', null, ['is_manager' => true]);
    $x = member('reseller', $b);

    app(TransactionPoster::class)->post($x, pin('registrasi_upgrade', 3, 1800000)->id, 'join');

    expect(pl($b))->toBe(3);
});

it('pembeli yang sudah Manager tidak memberi poin leadership ke atasnya', function () {
    $b = member('manager', null, ['is_manager' => true]);
    $c = member('manager', $b, ['is_manager' => true]);
    $x = member('manager', $c, ['is_manager' => true]);

    app(TransactionPoster::class)->post($x, pin('ro', 1, 500000)->id, 'ro');

    expect(pl($c))->toBe(0)
        ->and(pl($b))->toBe(0);
});

it('Manager yang RO sendiri ikut menerima poin leadership dari transaksinya', function () {
    $x = member('manager', null, ['is_manager' => true]);

    app(TransactionPoster::class)->post($x, pin('ro', 2, 1000000)->id, 'ro');

    // RO pribadi Manager: 2 pin → 2 PL untuk dirinya sendiri (PRD v3.0 §3.8).
    expect(pl($x))->toBe(2);
});

it('Manager yang RO sendiri tetap memberi PL ke Manager di atasnya', function () {
    $b = member('manager', null, ['is_manager' => true]);
    $x = member('manager', $b, ['is_manager' => true]);

    app(TransactionPoster::class)->post($x, pin('ro', 2, 1000000)->id, 'ro');

    // Pembeli Manager: dia dapat PL dari RO pribadinya, tapi kompresi #62
    // menutup hak upline karena anggota di bawahnya sudah Manager.
    expect(pl($x))->toBe(2)
        ->and(pl($b))->toBe(0);
});

it('non-Manager tidak punya baris poin leadership sama sekali', function () {
    $b = member('reseller');
    $x = member('reseller', $b);

    app(TransactionPoster::class)->post($x, pin('registrasi_upgrade', 1, 600000)->id, 'join');

    expect(pl($b))->toBe(0)
        ->and($b->fresh()->rank)->toBe('reseller');
});
