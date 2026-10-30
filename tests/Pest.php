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

function pin(string $type = 'registration', int $points = 1, int $price = 600000): Pin
{
    return Pin::create([
        'code' => 'PIN'.Str::upper(Str::random(8)),
        'type' => $type,
        'points' => $points,
        'price' => $price,
        'status' => 'issued',
    ]);
}
