<?php

use App\Models\PointEntry;

/**
 * Perintah penyelarasan peringkat lama (PRD v3.0 §3.3).
 * Hanya menaikkan, tidak pernah menurunkan. Default tidak menyimpan.
 */

function poinUji(\App\Models\Member $m, string $kind, int $n): void
{
    PointEntry::create([
        'member_id' => $m->id,
        'kind' => $kind,
        'delta' => $n,
        'source_type' => 'test',
        'note' => 'uji',
    ]);
}

it('dry-run tidak mengubah peringkat', function () {
    $m = member('reseller');
    poinUji($m, 'pr', 6);

    $this->artisan('sahih:selaraskan-peringkat')
        ->expectsOutputToContain($m->username)
        ->assertSuccessful();

    expect($m->fresh()->rank)->toBe('reseller');
});

it('dengan --jalankan peringkat naik sesuai PR', function () {
    $m = member('reseller');
    poinUji($m, 'pr', 6);

    $this->artisan('sahih:selaraskan-peringkat', ['--jalankan' => true])
        ->assertSuccessful();

    expect($m->fresh()->rank)->toBe('distributor');
});

it('peringkat tidak pernah diturunkan walau PR kurang', function () {
    $m = member('distributor');   // PR 0
    $this->artisan('sahih:selaraskan-peringkat', ['--jalankan' => true])->assertSuccessful();

    expect($m->fresh()->rank)->toBe('distributor');
});

it('kenaikan dari penyelarasan tercatat di riwayat', function () {
    $m = member('reseller');
    poinUji($m, 'pr', 3);

    $this->artisan('sahih:selaraskan-peringkat', ['--jalankan' => true])->assertSuccessful();

    $log = \App\Models\RankHistory::where('member_id', $m->id)->first();

    expect($log)->not->toBeNull()
        ->and($log->to_rank)->toBe('agent')
        ->and($log->note)->toContain('PRD v3.0');
});

it('Manager tidak ikut diubah', function () {
    $m = member('manager', null, ['is_manager' => true]);

    $this->artisan('sahih:selaraskan-peringkat', ['--jalankan' => true])->assertSuccessful();

    expect($m->fresh()->rank)->toBe('manager');
});
