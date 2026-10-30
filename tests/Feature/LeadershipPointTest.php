<?php

use App\Models\PointEntry;
use App\Services\TransactionPoster;

/**
 * Poin leadership (BUSINESS_RULES.md §4, DECISIONS #62).
 * Nilai: sebesar poin transaksi (jawaban Owner "a").
 * Penerima: upline level 1, 2, 3, … sampai dan termasuk Manager pertama, lalu berhenti.
 */

function pl(\App\Models\Member|int $member): int
{
    $id = $member instanceof \App\Models\Member ? $member->id : $member;

    return (int) PointEntry::where('member_id', $id)->where('kind', 'pl')->sum('delta');
}

it('RO 3 poin memberi 3 poin leadership sampai Manager pertama saja', function () {
    $d = member('distributor');          // di atas Manager, tidak dapat apa-apa
    $c = member('manager', $d);          // Manager pertama — dapat, lalu rantai berhenti
    $b = member('agent', $c);            // level 1
    $x = member('agent', $b);            // yang bertransaksi

    app(TransactionPoster::class)->post($x, pin('ro', 3, 1500000)->id, 'ro');

    expect(pl($b))->toBe(3)
        ->and(pl($c))->toBe(3)
        ->and(pl($d))->toBe(0)
        ->and(pl($x))->toBe(0);   // pembeli tidak dapat poin leadership dari dirinya sendiri
});

it('join 3 poin juga memberi poin leadership ke upline', function () {
    $b = member('agent');
    $x = member('reseller', $b);

    app(TransactionPoster::class)->post($x, pin('registration', 3, 1800000)->id, 'join');

    expect(pl($b))->toBe(3);
});

it('pembeli yang sudah Manager tidak memberi poin leadership ke atasnya', function () {
    $b = member('agent');
    $c = member('agent', $b);
    $x = member('manager', $c);

    app(TransactionPoster::class)->post($x, pin('ro', 1, 500000)->id, 'ro');

    expect(pl($c))->toBe(0)
        ->and(pl($b))->toBe(0);
});

it('poin leadership dicatat untuk upline walau dia belum Manager', function () {
    $b = member('reseller');
    $x = member('reseller', $b);

    app(TransactionPoster::class)->post($x, pin('registration', 1, 600000)->id, 'join');

    expect(pl($b))->toBe(1)
        ->and($b->fresh()->rank)->toBe('reseller');   // tetap reseller, poin hanya dicatat
});
