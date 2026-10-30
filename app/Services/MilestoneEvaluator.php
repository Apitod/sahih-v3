<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MilestoneClaim;
use App\Models\MilestoneReward;
use App\Models\PointEntry;

/**
 * Hadiah poin bersifat kumulatif DAN berulang.
 * Setiap ambang yang terlewati jadi hak member; setelah putaran penuh
 * (setinggi ambang tertinggi, sekarang 3.500 poin) tabelnya terulang.
 * Poin TIDAK berkurang saat hadiah diklaim (BUSINESS_RULES.md §7).
 */
class MilestoneEvaluator
{
    public function totalPr(Member $member): int
    {
        return (int) PointEntry::where('member_id', $member->id)->where('kind', 'pr')->sum('delta');
    }

    /** Membuat baris klaim yang jadi hak member. Idempoten lewat unique index. */
    public function evaluate(Member $member): int
    {
        $pr = $this->totalPr($member);
        $created = 0;

        $rewards = MilestoneReward::where('is_active', true)->orderBy('threshold')->get();

        if ($rewards->isEmpty()) {
            return 0;
        }

        // Panjang satu putaran DIKUNCI di config (DECISIONS #61), tidak mengikuti ambang
        // tertinggi yang sedang aktif: mematikan satu hadiah tidak boleh mengubah
        // hitungan hadiah anggota lain.
        $cycleLength = (int) config('sahih.milestone_cycle_points', 3500);
        if ($cycleLength < 1) {
            $cycleLength = (int) $rewards->max('threshold');
        }
        $fullCycles = intdiv($pr, max(1, $cycleLength));
        $offset = $pr % max(1, $cycleLength);

        foreach ($rewards as $reward) {
            $threshold = (int) $reward->threshold;

            for ($cycle = 1; $cycle <= $fullCycles; $cycle++) {
                if ($this->claim($member, $reward, $cycle)) {
                    $created++;
                }
            }

            if ($offset >= $threshold && $this->claim($member, $reward, $fullCycles + 1)) {
                $created++;
            }
        }

        return $created;
    }

    private function claim(Member $member, MilestoneReward $reward, int $cycle): bool
    {
        return MilestoneClaim::firstOrCreate(
            ['member_id' => $member->id, 'milestone_reward_id' => $reward->id, 'cycle' => $cycle],
            ['status' => 'eligible'],
        )->wasRecentlyCreated;
    }
}
