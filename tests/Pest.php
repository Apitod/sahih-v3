<?php

use App\Models\Member;
use App\Models\Pin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

/** Anggota uji. Peringkat diisi langsung supaya rantai bisa disusun apa adanya. */
function member(string $rank = 'reseller', ?Member $sponsor = null, array $attributes = []): Member
{
    static $n = 0;
    $n++;

    return Member::create(array_merge([
        'username' => 'uji'.$n.Str::lower(Str::random(4)),
        'name' => 'Anggota Uji '.$n,
        'phone' => '08120000000'.$n,
        'password' => bcrypt('rahasia'),
        'sponsor_id' => $sponsor?->id,
        'rank' => $rank,
        'joined_at' => now(),
    ], $attributes));
}

/** Memberi poin lewat ledger seperti sistem sungguhan (bukan hanya kolom cache). */
function givePoints(Member $member, string $kind, int $points): void
{
    \App\Models\PointEntry::create([
        'member_id' => $member->id,
        'kind' => $kind,
        'delta' => $points,
        'source_type' => 'test',
        'note' => 'poin awal untuk uji',
    ]);

    $column = "point_{$kind}";
    $member->{$column} = (int) $member->{$column} + $points;
    $member->save();
}

/**
 * PIN uji. Hanya ada dua jenis (DECISIONS #73):
 *  - `registrasi_upgrade` — dipakai untuk mendaftar (join) DAN naik peringkat (upgrade)
 *  - `ro`                 — repeat order
 *
 * `$targetRank` adalah satu-satunya pembeda jalur: kosong = PIN pendaftaran,
 * terisi = PIN naik peringkat.
 */
function pin(string $type = 'registrasi_upgrade', int $points = 1, int $price = 600000, ?string $targetRank = null): Pin
{
    return Pin::create([
        'code' => 'PIN'.Str::upper(Str::random(8)),
        'type' => $type,
        'target_rank' => $targetRank,
        'points' => $points,
        'price' => $price,
        'status' => 'issued',
    ]);
}

/** PIN RO siap pakai — harga sudah benar (poin × Rp 500.000), jadi lolos penjaga harga. */
function pinRo(int $points = 1): Pin
{
    return pin('ro', $points, $points * 500000);
}

/** PIN registrasi/upgrade untuk mendaftar: peringkat tujuan kosong. */
function pinDaftar(int $points = 1): Pin
{
    return pin('registrasi_upgrade', $points, $points * 600000);
}
