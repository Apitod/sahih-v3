<?php

use App\Models\Pin;
use App\Models\Transaction;
use App\Models\BonusEntry;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

function buatPin(string $type = 'ro'): Pin
{
    static $n = 0;
    $n++;
    return Pin::create([
        'code' => 'PIN' . $n,
        'type' => $type === 'ro' ? 'ro' : 'registrasi_upgrade',
        'points' => 1,
        'price' => 500000,
        'status' => 'used',
    ]);
}

function buatTrxRo($member): Transaction
{
    $pin = buatPin('ro');
    return Transaction::create([
        'code' => 'RO-' . $pin->code,
        'type' => 'ro',
        'member_id' => $member->id,
        'pin_id' => $pin->id,
        'points' => 1,
        'amount' => 500000,
        'posted_at' => now(),
    ]);
}

test('proses bonus bulanan hitung PL Manager', function () {
    $manager = member('manager', null, ['is_manager' => true]);
    buatTrxRo($manager);

    givePoints($manager, 'pl', 10, '2026-10-15 10:00:00');

    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10']);

    $bonus = BonusEntry::where('member_id', $manager->id)->where('kind', 'leadership')->first();

    expect($bonus)->not->toBeNull()
        ->and($bonus->amount)->toBe(10 * 40000);
});

test('proses bonus bulanan skip non-Manager', function () {
    $reseller = member('reseller');

    givePoints($reseller, 'pl', 10, '2026-10-15 10:00:00');

    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10']);

    expect(BonusEntry::where('member_id', $reseller->id)->where('kind', 'leadership')->count())->toBe(0);
});

test('proses bonus bulanan skip Manager belum RO', function () {
    $manager = member('manager', null, ['is_manager' => true]);
    // tidak ada transaksi RO

    givePoints($manager, 'pl', 10, '2026-10-15 10:00:00');

    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10']);

    expect(BonusEntry::where('member_id', $manager->id)->where('kind', 'leadership')->count())->toBe(0);
});

test('proses bonus bulanan idempoten', function () {
    $manager = member('manager', null, ['is_manager' => true]);
    buatTrxRo($manager);

    givePoints($manager, 'pl', 10, '2026-10-15 10:00:00');

    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10']);
    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10']);

    expect(BonusEntry::where('member_id', $manager->id)->where('kind', 'leadership')->count())->toBe(1);
});

test('proses bonus RM tahunan hitung PL downline Manager', function () {
    $rm = member('manager', null, ['is_manager' => true]);
    $downlineManager = member('manager', $rm, ['is_manager' => true]);

    givePoints($downlineManager, 'pl', 100, '2026-05-01 10:00:00');

    Artisan::call('sahih:proses-bonus-rm-tahunan', ['--tahun' => '2026']);

    $bonus = BonusEntry::where('member_id', $rm->id)->where('kind', 'rm')->first();

    expect($bonus)->not->toBeNull()
        ->and($bonus->amount)->toBe(100 * 2000);
});

test('proses bonus RM skip yang tidak punya downline Manager', function () {
    $manager = member('manager', null, ['is_manager' => true]);
    $downlineReseller = member('reseller', $manager);

    givePoints($downlineReseller, 'pl', 50, '2026-05-01 10:00:00');

    Artisan::call('sahih:proses-bonus-rm-tahunan', ['--tahun' => '2026']);

    expect(BonusEntry::where('member_id', $manager->id)->where('kind', 'rm')->count())->toBe(0);
});

test('proses bonus RM compression antar RM', function () {
    $rm1 = member('manager', null, ['is_manager' => true]);
    $rm2 = member('manager', $rm1, ['is_manager' => true]);
    $rm3 = member('manager', $rm2, ['is_manager' => true]);

    givePoints($rm3, 'pl', 50, '2026-05-01 10:00:00');

    Artisan::call('sahih:proses-bonus-rm-tahunan', ['--tahun' => '2026']);

    // rm2 dapat dari rm3 (downline langsungnya)
    expect(BonusEntry::where('member_id', $rm2->id)->where('kind', 'rm')->first()->amount)
        ->toBe(50 * 2000);

    // rm1 TIDAK dapat dari rm3 karena compression (rm2 sudah RM, batas di situ)
    expect(BonusEntry::where('member_id', $rm1->id)->where('kind', 'rm')->count())->toBe(0);
});

test('proses bonus RM idempoten', function () {
    $rm = member('manager', null, ['is_manager' => true]);
    $downlineManager = member('manager', $rm, ['is_manager' => true]);

    givePoints($downlineManager, 'pl', 100, '2026-05-01 10:00:00');

    Artisan::call('sahih:proses-bonus-rm-tahunan', ['--tahun' => '2026']);
    Artisan::call('sahih:proses-bonus-rm-tahunan', ['--tahun' => '2026']);

    expect(BonusEntry::where('member_id', $rm->id)->where('kind', 'rm')->count())->toBe(1);
});

// ---------------------------------------------------------------- kunci periode di level DB

test('database menolak bonus leadership kedua untuk member dan bulan yang sama', function () {
    $m = member('manager', null, ['is_manager' => true]);

    $buat = fn () => BonusEntry::create([
        'member_id' => $m->id, 'kind' => 'leadership', 'amount' => 400000,
        'status' => 'available', 'available_at' => '2026-10-01 00:00:00',
    ]);

    $buat();

    expect($buat)->toThrow(UniqueConstraintViolationException::class);
});

test('database menolak bonus rm kedua untuk member dan tahun yang sama', function () {
    $m = member('manager', null, ['is_manager' => true]);

    $buat = fn () => BonusEntry::create([
        'member_id' => $m->id, 'kind' => 'rm', 'amount' => 2000,
        'status' => 'available', 'available_at' => '2026-01-01 00:00:00',
    ]);

    $buat();

    expect($buat)->toThrow(UniqueConstraintViolationException::class);
});

test('kunci periode tidak mengganggu bonus sponsor yang tanggalnya sama', function () {
    $m = member('reseller');

    foreach ([50000, 25000] as $amount) {
        BonusEntry::create([
            'member_id' => $m->id, 'kind' => 'sponsor', 'amount' => $amount,
            'status' => 'available', 'available_at' => '2026-10-01 00:00:00',
        ]);
    }

    expect(BonusEntry::where('member_id', $m->id)->where('kind', 'sponsor')->count())->toBe(2);
});

/**
 * Kolom generated periode_kunci pernah dibuat CHAR(7) — terlalu pendek untuk
 * 'leadership' (10 karakter). MariaDB menolak setiap insert dengan "String data,
 * right truncated" sementara sqlite diam saja, sehingga bug ini hanya ketahuan
 * saat dijalankan di DB sungguhan.
 */
test('kolom kunci periode cukup panjang untuk semua jenis bonus yang dikunci', function () {
    // sqlite tidak punya information_schema dan tidak membatasi panjang VARCHAR.
    if (DB::getDriverName() !== 'mysql') {
        expect(true)->toBeTrue();

        return;
    }

    $panjang = DB::selectOne(
        'SELECT CHARACTER_MAXIMUM_LENGTH AS p FROM information_schema.COLUMNS
         WHERE TABLE_NAME = ? AND COLUMN_NAME = ?',
        ['bonus_entries', 'periode_kunci']
    );

    expect($panjang)->not->toBeNull()
        ->and((int) $panjang->p)->toBeGreaterThanOrEqual(strlen('leadership'));
});

// ---------------------------------------------------------------- hitung ulang periode

test('tanpa hitung-ulang, poin PL yang masuk belakangan tidak terbayar', function () {
    $manager = member('manager', null, ['is_manager' => true]);
    buatTrxRo($manager);

    givePoints($manager, 'pl', 10, '2026-10-05 10:00:00');
    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10']);

    // Poin susulan setelah proses pertama.
    givePoints($manager, 'pl', 5, '2026-10-20 10:00:00');
    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10']);

    expect(BonusEntry::where('member_id', $manager->id)->where('kind', 'leadership')->sum('amount'))
        ->toBe(10 * 40000);
});

test('hitung-ulang menambah selisih poin PL susulan tanpa membayar dua kali', function () {
    $manager = member('manager', null, ['is_manager' => true]);
    buatTrxRo($manager);

    givePoints($manager, 'pl', 10, '2026-10-05 10:00:00');
    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10']);

    givePoints($manager, 'pl', 5, '2026-10-20 10:00:00');
    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10', '--hitung-ulang' => true]);

    $bonus = BonusEntry::where('member_id', $manager->id)->where('kind', 'leadership')->get();

    expect($bonus)->toHaveCount(1)
        ->and((int) $bonus->first()->amount)->toBe(15 * 40000);
});

test('hitung-ulang tidak menambah apa pun kalau poin tidak berubah', function () {
    $manager = member('manager', null, ['is_manager' => true]);
    buatTrxRo($manager);

    givePoints($manager, 'pl', 10, '2026-10-05 10:00:00');
    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10']);
    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10', '--hitung-ulang' => true]);

    expect(BonusEntry::where('member_id', $manager->id)->where('kind', 'leadership')->sum('amount'))
        ->toBe(10 * 40000);
});

test('hitung-ulang tidak menyentuh bonus yang sudah ditarik anggota', function () {
    $manager = member('manager', null, ['is_manager' => true]);
    buatTrxRo($manager);

    givePoints($manager, 'pl', 10, '2026-10-05 10:00:00');
    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10']);

    BonusEntry::where('member_id', $manager->id)->where('kind', 'leadership')
        ->update(['status' => 'withdrawn']);

    givePoints($manager, 'pl', 5, '2026-10-20 10:00:00');
    Artisan::call('sahih:proses-bonus-bulanan', ['--bulan' => '2026-10', '--hitung-ulang' => true]);

    expect(BonusEntry::where('member_id', $manager->id)->where('kind', 'leadership')->sum('amount'))
        ->toBe(10 * 40000);
});
