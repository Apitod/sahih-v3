<?php

use App\Models\Admin;
use App\Models\MilestoneReward;

beforeEach(function () {
    $this->admin = Admin::create([
        'name' => 'Admin Test',
        'email' => 'admin@test.local',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'is_active' => true,
    ]);
});

test('admin bisa lihat daftar hadiah milestone', function () {
    MilestoneReward::create([
        'threshold' => 100,
        'name' => 'Hadiah Bronze',
        'value_internal' => 100000,
        'is_active' => true,
    ]);
    MilestoneReward::create([
        'threshold' => 500,
        'name' => 'Hadiah Gold',
        'value_internal' => 500000,
        'is_active' => false,
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->get('/admin/hadiah');

    $response->assertOk();
    $response->assertSee('Hadiah Bronze');
    $response->assertSee('Hadiah Gold');
    $response->assertSee('100');
    $response->assertSee('500');
});

test('admin bisa buka form edit hadiah', function () {
    $reward = MilestoneReward::create([
        'threshold' => 100,
        'name' => 'Hadiah Bronze',
        'value_internal' => 250000,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->get("/admin/hadiah/{$reward->id}/edit");

    $response->assertOk();
    $response->assertSee('Hadiah Bronze');
    $response->assertSee('250000');
    $response->assertSee('100');
});

test('admin bisa update nama dan value_internal hadiah', function () {
    $reward = MilestoneReward::create([
        'threshold' => 100,
        'name' => 'Lama',
        'value_internal' => 100000,
        'is_active' => false,
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->post("/admin/hadiah/{$reward->id}", [
            'name' => 'Baru',
            'value_internal' => 200000,
            'is_active' => '1',
        ]);

    $response->assertRedirect();
    
    $reward->refresh();
    expect($reward->name)->toBe('Baru');
    expect($reward->value_internal)->toBe(200000);
    expect($reward->is_active)->toBeTrue();
    expect($reward->threshold)->toBe(100);
});

test('threshold tidak bisa diubah lewat update', function () {
    $reward = MilestoneReward::create([
        'threshold' => 100,
        'name' => 'Hadiah',
        'value_internal' => 100000,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin, 'admin')
        ->post("/admin/hadiah/{$reward->id}", [
            'name' => 'Hadiah',
            'value_internal' => 100000,
            'threshold' => 999,
            'is_active' => '1',
        ]);

    expect($reward->fresh()->threshold)->toBe(100);
});

test('admin bisa tambah hadiah baru dengan threshold unik', function () {
    $response = $this->actingAs($this->admin, 'admin')
        ->post('/admin/hadiah', [
            'threshold' => 300,
            'name' => 'Hadiah Silver',
            'value_internal' => 500000,
            'is_active' => '1',
        ]);

    $response->assertRedirect();
    
    $reward = MilestoneReward::where('threshold', 300)->first();
    expect($reward)->not->toBeNull();
    expect($reward->name)->toBe('Hadiah Silver');
    expect($reward->value_internal)->toBe(500000);
});

test('store ditolak jika threshold sudah ada', function () {
    MilestoneReward::create(['threshold' => 300, 'name' => 'Ada', 'value_internal' => 100000]);

    $response = $this->actingAs($this->admin, 'admin')
        ->post('/admin/hadiah', [
            'threshold' => 300,
            'name' => 'Duplikat',
            'value_internal' => 100000,
            'is_active' => '1',
        ]);

    $response->assertSessionHasErrors('threshold');
});

test('update gagal validasi jika name kosong', function () {
    $reward = MilestoneReward::create([
        'threshold' => 100,
        'name' => 'Hadiah',
        'value_internal' => 100000,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->post("/admin/hadiah/{$reward->id}", [
            'name' => '',
            'value_internal' => 100000,
            'is_active' => '1',
        ]);

    $response->assertSessionHasErrors('name');
});
