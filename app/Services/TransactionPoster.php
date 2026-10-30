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
    ) {}

    public function post(Member $buyer, int $pinId, string $type, ?int $adminId = null): Transaction
    {
        if (! in_array($type, ['join', 'upgrade', 'ro'], true)) {
            throw new \InvalidArgumentException("Jenis transaksi tidak dikenal: {$type}");
        }

        return DB::transaction(function () use ($buyer, $pinId, $type, $adminId) {
            // Kunci baris PIN: dua permintaan bersamaan tidak bisa memakai PIN yang sama.
            $pin = Pin::whereKey($pinId)->lockForUpdate()->firstOrFail();

            if (! $pin->isUsable()) {
                throw new \RuntimeException("PIN {$pin->code} tidak bisa dipakai (status: {$pin->status}).");
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
