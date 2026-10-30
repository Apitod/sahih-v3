<?php

namespace App\Services;

class RankResolver
{
    /** Peringkat yang didapat dari jumlah poin yang dibeli. */
    public function forPoints(int $points): string
    {
        return config('sahih.rank_by_points')[$points]
            ?? throw new \InvalidArgumentException("Jumlah poin {$points} tidak punya peringkat yang sah.");
    }

    /** Urutan peringkat, dipakai untuk membandingkan. */
    public function weight(string $rank): int
    {
        return array_search($rank, ['reseller', 'agent', 'distributor', 'manager'], true);
    }

    public function rateBp(string $rank): int
    {
        return config('sahih.rank_rate_bp')[$rank]
            ?? throw new \InvalidArgumentException("Peringkat tidak dikenal: {$rank}");
    }
}
