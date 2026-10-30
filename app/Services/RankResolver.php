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
        $weight = array_search($rank, ['reseller', 'agent', 'distributor', 'manager'], true);

        // Dulu mengembalikan false untuk peringkat tak dikenal, lalu perbandingan
        // longgar menganggapnya peringkat terendah. Sekarang ditolak terang-terangan.
        if ($weight === false) {
            throw new \InvalidArgumentException("Peringkat tidak dikenal: {$rank}");
        }

        return $weight;
    }

    public function rateBp(string $rank): int
    {
        return config('sahih.rank_rate_bp')[$rank]
            ?? throw new \InvalidArgumentException("Peringkat tidak dikenal: {$rank}");
    }
}
