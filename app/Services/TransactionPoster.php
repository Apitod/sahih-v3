<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BonusEntry;
use App\Models\Member;
use App\Models\Pin;
use App\Models\PointEntry;
use App\Models\RankHistory;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Satu-satunya pintu masuk transaksi uang.
 *
 * Semua tulisan terjadi dalam satu database transaction: transaksi, PIN,
 * poin, bonus, peringkat, klaim, audit. Gagal satu = batal semua.
 */
class TransactionPoster
{
    public function __construct(
        private CommissionCalculator $commission,
        private RankResolver $ranks,
        private MilestoneEvaluator $milestones,
        private PackagePrice $packagePrice,
    ) {}

    public function post(Member $buyer, int $pinId, string $type, ?int $adminId = null): Transaction
    {
        if (! in_array($type, ['join', 'upgrade', 'ro'], true)) {
            throw new \InvalidArgumentException("Jenis transaksi tidak dikenal: {$type}");
        }

        return DB::transaction(function () use ($buyer, $pinId, $type, $adminId) {
            $buyer = Member::whereKey($buyer->id)->lockForUpdate()->firstOrFail();

            if ($buyer->status !== 'active') {
                throw new \RuntimeException('Akun ini sedang dibekukan. Hubungi admin.');
            }

            // Kunci baris PIN: dua permintaan bersamaan tidak bisa memakai PIN yang sama.
            $pin = Pin::whereKey($pinId)->lockForUpdate()->firstOrFail();

            if (! $pin->isUsable()) {
                throw new \RuntimeException("PIN {$pin->code} tidak bisa dipakai (status: {$pin->status}).");
            }

            // Penjaga kepemilikan: PIN harus milik pembeli (kalau punya pemilik).
            if ($pin->assigned_member_id !== null && $pin->assigned_member_id !== $buyer->id) {
                throw new \RuntimeException("PIN {$pin->code} bukan untuk akun ini.");
            }

            // Penjaga jenis: jenis PIN harus cocok dengan jenis transaksi.
            $expectedTypes = [
                'registration' => 'join',
                'upgrade' => 'upgrade',
                'ro' => 'ro',
            ];
            if (($expectedTypes[$pin->type] ?? null) !== $type) {
                throw new \RuntimeException("Jenis PIN {$pin->type} tidak cocok untuk transaksi {$type}.");
            }

            // Penjaga arah peringkat: upgrade tidak boleh ke peringkat sama atau lebih rendah (keputusan #55).
            if ($type === 'upgrade') {
                if ($pin->target_rank === null) {
                    throw new \RuntimeException("PIN {$pin->code} tidak punya peringkat tujuan — tidak bisa dipakai untuk upgrade.");
                }

                // Manager tidak dijual (DECISIONS #58): peringkat ini otomatis setelah Distributor + 20 poin PA.
                if ($pin->target_rank === 'manager') {
                    throw new \RuntimeException('Manager tidak bisa dibeli lewat PIN upgrade — Manager otomatis setelah Distributor + 20 poin PA.');
                }

                if ($this->ranks->weight($pin->target_rank) <= $this->ranks->weight($buyer->rank)) {
                    throw new \RuntimeException("PIN upgrade ke peringkat {$pin->target_rank} ditolak untuk akun yang sudah {$buyer->rank}.");
                }

                // Harga PIN upgrade = harga paket tujuan − harga paket sekarang (DECISIONS #59).
                // Diperiksa lagi di sini supaya PIN yang terbit sebelum peringkat pembeli
                // berubah tidak dipakai dengan harga lama.
                $expectedPrice = $this->packagePrice->upgradePrice($buyer->rank, $pin->target_rank);

                if ((int) $pin->price !== $expectedPrice) {
                    throw new \RuntimeException("Harga PIN {$pin->code} tidak sesuai untuk upgrade {$buyer->rank} ke {$pin->target_rank}.");
                }
            }

            // Satu anggota hanya boleh punya satu transaksi join. PIN registrasi kedua
            // akan menggandakan poin pribadi dan PA sponsor untuk orang yang sama.
            if ($type === 'join' && Transaction::where('member_id', $buyer->id)->where('type', 'join')->exists()) {
                throw new \RuntimeException('Akun ini sudah terdaftar. PIN registrasi hanya bisa dipakai sekali per anggota.');
            }

            $now = now();

            $trx = Transaction::create([
                'code' => 'TRX-'.Str::upper(Str::random(12)),
                'type' => $type,
                'member_id' => $buyer->id,
                'pin_id' => $pin->id,
                'points' => $pin->points,
                'amount' => $pin->price,
                'sponsor_bonus_total' => 0,
                'posted_at' => $now,
                'created_by' => $adminId,
            ]);

            $pin->update([
                'status' => 'used',
                'used_by_member_id' => $buyer->id,
                'used_at' => $now,
            ]);

            // Peringkat pembeli: saat mendaftar dari poin, saat upgrade dari target PIN.
            if ($type === 'join') {
                $this->applyRank($buyer, $this->ranks->forPoints($pin->points), $trx);
            }

            if ($type === 'upgrade') {
                if ($pin->target_rank === null) {
                    throw new \RuntimeException("PIN {$pin->code} tidak punya peringkat tujuan — tidak bisa dipakai untuk upgrade.");
                }
                $this->applyRank($buyer, $pin->target_rank, $trx);
            }

            // Poin pribadi pembeli: selalu PR, tidak pernah PA (BUSINESS_RULES.md §7).
            $this->addPoints($buyer, ['pr'], (int) $pin->points, $trx);

            // Manager otomatis untuk pembeli setelah upgrade (DECISIONS #49).
            if ($type === 'upgrade') {
                $this->promoteIfQualified($buyer->fresh(), $trx);
            }

            // Poin untuk sponsor langsung: join = PA + PR, RO/upgrade = PR saja.
            $sponsor = $buyer->sponsor;
            if ($sponsor !== null) {
                $kinds = $type === 'join' ? ['pa', 'pr'] : ['pr'];
                $this->addPoints($sponsor, $kinds, (int) $pin->points, $trx);
                $this->promoteIfQualified($sponsor->fresh(), $trx);
            }

            // Bonus sponsor sepanjang rantai upline — hanya join & upgrade.
            // RO tidak membayar bonus sponsor (BUSINESS_RULES.md §6).
            $total = 0;
            if ($type !== 'ro') {
                $allocations = $this->commission->distribute((int) $pin->price, $buyer->uplineRanks());
                $cursor = $buyer->sponsor;

                foreach ($allocations as $allocation) {
                    if ($cursor === null) {
                        break;
                    }

                    if ($allocation['amount'] > 0) {
                        BonusEntry::create([
                            'member_id' => $cursor->id,
                            'kind' => 'sponsor',
                            'amount' => $allocation['amount'],
                            'percent_bp' => $allocation['percent_bp'],
                            'level' => $allocation['level'],
                            'source_transaction_id' => $trx->id,
                            // Bonus sponsor cair harian (BUSINESS_RULES.md §3 poin 5).
                            'status' => 'available',
                            'available_at' => $now,
                        ]);
                        $total += $allocation['amount'];
                    }

                    $cursor = $cursor->sponsor;
                }
            }

            $trx->update(['sponsor_bonus_total' => $total]);

            // Poin leadership (BUSINESS_RULES.md §4, DECISIONS #62): sebesar poin
            // transaksi, ke setiap upline sampai Manager pertama, lalu berhenti.
            $this->awardLeadership($buyer, (int) $pin->points, $trx);

            // Hadiah milestone dinilai dari total PR (BUSINESS_RULES.md §8), dan PR
            // termasuk RO pribadi (§7). Pembeli menilai tangganya sendiri, sponsor
            // menilai poin yang baru masuk. Klaim idempoten lewat unique index, jadi
            // pemanggilan untuk orang yang sama tidak menghasilkan klaim dobel.
            $this->milestones->evaluate($buyer->fresh());

            if ($sponsor !== null) {
                $this->milestones->evaluate($sponsor->fresh());
            }

            AuditLog::create([
                'actor_type' => $adminId === null ? 'member' : 'admin',
                'actor_id' => $adminId ?? $buyer->id,
                'action' => 'transaction.posted',
                'subject_type' => Transaction::class,
                'subject_id' => $trx->id,
                'after' => [
                    'type' => $type,
                    'pin' => $pin->code,
                    'amount' => $pin->price,
                    'points' => $pin->points,
                    'bonus_total' => $total,
                ],
            ]);

            return $trx->fresh();
        });
    }

    /**
     * Poin leadership (BUSINESS_RULES.md §4, DECISIONS #62).
     *
     * Sebesar poin transaksi, diberikan ke upline level 1, 2, 3, … sampai dan
     * termasuk Manager pertama, lalu berhenti (kompresi). Kalau pembelinya sendiri
     * sudah Manager, tidak ada yang dapat — hak dari struktur itu sudah tertutup.
     */
    private function awardLeadership(Member $buyer, int $points, Transaction $trx): void
    {
        if ($points <= 0 || $buyer->rank === 'manager') {
            return;
        }

        $cursor = $buyer->sponsor;
        $level = 0;

        while ($cursor !== null && $level < 20) {
            $level++;

            PointEntry::create([
                'member_id' => $cursor->id,
                'kind' => 'pl',
                'delta' => $points,
                'source_type' => 'transaction',
                'source_id' => $trx->id,
                'note' => "Poin leadership level {$level} dari transaksi {$trx->code}",
            ]);

            $cursor->point_pl = (int) $cursor->point_pl + $points;
            $cursor->save();

            if ($cursor->rank === 'manager') {
                break;   // Manager pertama: hak dari struktur ini berhenti di sini
            }

            $cursor = $cursor->sponsor;
        }
    }

    private function addPoints(Member $member, array $kinds, int $points, Transaction $trx): void
    {
        foreach ($kinds as $kind) {
            PointEntry::create([
                'member_id' => $member->id,
                'kind' => $kind,
                'delta' => $points,
                'source_type' => 'transaction',
                'source_id' => $trx->id,
                'note' => "Poin {$kind} dari transaksi {$trx->code}",
            ]);

            $column = "point_{$kind}";
            $member->{$column} = (int) $member->{$column} + $points;
        }

        $member->save();
    }

    private function applyRank(Member $member, string $rank, Transaction $trx): void
    {
        if ($this->ranks->weight($rank) <= $this->ranks->weight($member->rank)) {
            return;   // tidak ada penurunan peringkat
        }

        $from = $member->rank;
        $member->rank = $rank;
        $member->save();

        RankHistory::create([
            'member_id' => $member->id,
            'from_rank' => $from,
            'to_rank' => $rank,
            'transaction_id' => $trx->id,
            'note' => 'Peringkat dari poin pembelian',
        ]);
    }

    /** Manager otomatis: sudah Distributor + 20 poin PA (DECISIONS #49). */
    private function promoteIfQualified(Member $member, Transaction $trx): void
    {
        if ($member->rank === 'manager') {
            if (! $member->is_manager || $member->manager_since === null) {
                $member->is_manager = true;
                $member->manager_since ??= now();
                $member->save();
            }

            return;
        }

        if ($member->is_manager || $member->rank !== 'distributor') {
            return;
        }

        if ($member->pointTotal('pa') < (int) config('sahih.manager_min_pa')) {
            return;
        }

        $member->rank = 'manager';
        $member->is_manager = true;
        $member->manager_since = now();
        $member->save();

        RankHistory::create([
            'member_id' => $member->id,
            'from_rank' => 'distributor',
            'to_rank' => 'manager',
            'transaction_id' => $trx->id,
            'note' => 'Otomatis: PA mencapai syarat Manager',
        ]);
    }
}
