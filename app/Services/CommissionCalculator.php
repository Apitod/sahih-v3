<?php

namespace App\Services;

/**
 * Membagi bonus sponsor sepanjang rantai upline.
 *
 * Aturan (BUSINESS_RULES.md §3):
 *  1. Pengajak dapat tarif peringkatnya sendiri.
 *  2. Upline di atasnya hanya dapat selisih dengan persen yang sudah dibayar.
 *  3. Peringkat sama/lebih rendah dapat 0, selisih lanjut ke atas (compression).
 *  4. Total tidak pernah melebihi 20%.
 *
 * Pembulatan ke bawah per penerima, sehingga total selalu <= 20%.
 */
class CommissionCalculator
{
    public function __construct(private RankResolver $ranks) {}

    /**
     * @param  array<int, string>  $uplineRanks  peringkat upline, level 1 = sponsor langsung
     * @return array<int, array{level:int, rank:string, percent_bp:int, amount:int}>
     */
    public function distribute(int $amount, array $uplineRanks): array
    {
        $cap = (int) config('sahih.sponsor_cap_bp');
        $paid = 0;
        $out = [];
        $level = 0;

        foreach ($uplineRanks as $rank) {
            $level++;
            $rate = $this->ranks->rateBp($rank);

            if ($rate <= $paid) {
                $out[] = ['level' => $level, 'rank' => $rank, 'percent_bp' => 0, 'amount' => 0];
                continue;
            }

            $delta = $rate - $paid;
            $share = intdiv($amount * $delta, 10000);

            $out[] = ['level' => $level, 'rank' => $rank, 'percent_bp' => $delta, 'amount' => $share];
            $paid = $rate;

            if ($paid >= $cap) {
                break;
            }
        }

        return $out;
    }

    public function total(array $allocations): int
    {
        return array_sum(array_column($allocations, 'amount'));
    }
}
