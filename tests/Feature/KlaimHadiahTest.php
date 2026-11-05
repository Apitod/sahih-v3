<?php

use App\Models\Admin;
use App\Models\Member;
use App\Models\MilestoneClaim;
use App\Models\MilestoneReward;
use Illuminate\Support\Facades\Hash;

/**
 * Klaim hadiah milestone (BUSINESS_RULES §8, DECISIONS #34).
 *
 * Poin tidak berkurang saat hadiah diklaim — hadiah jadi hak begitu ambang
 * dilewati. Klaim lewat admin: anggota mengajukan, admin menyerahkan.
 */

function rewardHadiah(int $threshold, string $nama): MilestoneReward
{
    return MilestoneReward::create([
        'threshold' => $threshold, 'name' => $nama, 'value_internal' => 1000,
        'is_active' => true, 'sort' => $threshold,
    ]);
}

function adminKlaim(): Admin
{
    return Admin::create([
        'name' => 'Admin Klaim',
        'email' => 'admin.klaim.'.uniqid().'@uji.local',
        'password' => Hash::make('rahasia'),
        'role' => 'admin',
        'is_active' => true,
    ]);
}

// ---------------------------------------------------------------- anggota: melihat & mengajukan

it('anggota melihat hadiah yang jadi hak dan bisa mengajukan klaim', function () {
    $r = rewardHadiah(5, 'Produk');
    $m = member('reseller');
    \App\Models\PointEntry::create([
        'member_id' => $m->id, 'kind' => 'pr', 'delta' => 6,
        'source_type' => 'manual', 'note' => 'uji',
    ]);

    app(\App\Services\MilestoneEvaluator::class)->evaluate($m->fresh());

    $this->actingAs($m, 'web')
        ->post('/hadiah/klaim', ['claim_id' => MilestoneClaim::first()->id])
        ->assertSessionHasNoErrors();

    $klaim = MilestoneClaim::first()->fresh();
    expect(MilestoneClaim::where('member_id', $m->id)->where('status', 'requested')->count())->toBe(1)
        ->and($klaim->requested_at)->not->toBeNull();
});

it('anggota tidak bisa mengajukan klaim milik orang lain', function () {
    $r = rewardHadiah(5, 'Produk');
    $pemilik = member('reseller');
    givePoints($pemilik, 'pr', 5);
    app(\App\Services\MilestoneEvaluator::class)->evaluate($pemilik->fresh());

    $orangLain = member('reseller');

    $this->actingAs($orangLain, 'web')
        ->post('/hadiah/klaim', ['claim_id' => MilestoneClaim::first()->id])
        ->assertSessionHasErrors();

    expect(MilestoneClaim::first()->status)->toBe('eligible');
});

it('anggota tidak bisa mengajukan klaim yang sudah diserahkan', function () {
    $r = rewardHadiah(5, 'Produk');
    $m = member('reseller');
    givePoints($m, 'pr', 5);
    app(\App\Services\MilestoneEvaluator::class)->evaluate($m->fresh());

    $klaim = MilestoneClaim::first();
    $admin = adminKlaim();
    $klaim->update(['status' => 'handed', 'handed_at' => now(), 'handled_by' => $admin->id]);

    $this->actingAs($m, 'web')
        ->post('/hadiah/klaim', ['claim_id' => $klaim->id])
        ->assertSessionHasErrors();

    expect($klaim->fresh()->status)->toBe('handed');
});

// ---------------------------------------------------------------- admin: menyerahkan

it('admin melihat daftar klaim dan bisa menyerahkan dengan catatan', function () {
    $r = rewardHadiah(5, 'Produk');
    $m = member('reseller');
    givePoints($m, 'pr', 5);
    app(\App\Services\MilestoneEvaluator::class)->evaluate($m->fresh());
    MilestoneClaim::first()->update(['status' => 'requested', 'requested_at' => now()]);

    $admin = adminKlaim();

    $this->actingAs($admin, 'admin')
        ->post("/admin/klaim/{$r->id}/serahkan", ['note' => 'Produk diambil di kantor'])
        ->assertRedirect('/admin/klaim');

    $klaim = MilestoneClaim::first()->fresh();
    expect($klaim->status)->toBe('handed')
        ->and($klaim->handed_at)->not->toBeNull()
        ->and($klaim->handled_by)->toBe($admin->id)
        ->and($klaim->note)->toBe('Produk diambil di kantor');
});

it('halaman admin klaim tidak membocorkan nilai rupiah hadiah', function () {
    $r = rewardHadiah(5, 'Produk');
    $m = member('reseller');
    givePoints($m, 'pr', 5);
    app(\App\Services\MilestoneEvaluator::class)->evaluate($m->fresh());

    $this->actingAs(adminKlaim(), 'admin')
        ->get('/admin/klaim')
        ->assertOk()
        ->assertSee($m->username)
        ->assertSee('Produk')
        ->assertDontSee('1.000');
});

it('beranda menampilkan tombol Ajukan untuk hadiah yang jadi hak', function () {
    $r = rewardHadiah(5, 'Produk');
    $m = member('reseller');
    givePoints($m, 'pr', 5);
    app(\App\Services\MilestoneEvaluator::class)->evaluate($m->fresh());

    $this->actingAs($m, 'web')
        ->get('/beranda')
        ->assertOk()
        ->assertSee('Ajukan');

    // Setelah diajukan, tombol hilang dan status berubah.
    $this->post('/hadiah/klaim', ['claim_id' => MilestoneClaim::first()->id]);
    $this->get('/beranda')
        ->assertOk()
        ->assertDontSee('Ajukan')
        ->assertSee('sedang diproses');
});
