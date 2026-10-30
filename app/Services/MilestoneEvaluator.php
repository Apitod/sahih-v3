<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MilestoneClaim;
use App\Models\MilestoneReward;
use App\Models\PointEntry;

/**
 * Hadiah poin bersifat kumulatif: setiap ambang yang terlewati jadi hak member.
 * Poin TIDAK berkurang saat hadiah diklaim (BUSINESS_RULES.md §7).
 *
 * Kolom `cycle` disediakan untuk kemungkinan hadiah berulang, tapi untuk
 * sekarang selalu 1: apakah hadiah terulang setelah 3.500 poin belum
 * diputuskan Owner, jadi belum diimplementasikan (dicatat sebagai UNKNOWN).
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

        foreach ($rewards as $reward) {
            if ($pr < (int) $reward->threshold) {
                continue;
            }

            $claim = MilestoneClaim::firstOrCreate(
                ['member_id' => $member->id, 'milestone_reward_id' => $reward->id, 'cycle' => 1],
                ['status' => 'eligible'],
            );

            if ($claim->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }
}
