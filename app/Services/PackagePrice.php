<?php

namespace App\Services;

/**
 * Harga paket per peringkat, dipakai untuk PIN registrasi dan PIN upgrade.
 *
 * Aturan Owner (DECISIONS #59): harga PIN upgrade = harga paket tujuan
 * dikurangi harga paket yang sekarang. Contoh Reseller -> Agent:
 * Rp 1.800.000 - Rp 600.000 = Rp 1.200.000.
 *
 * Manager bukan paket yang dijual (DECISIONS #58: Manager otomatis setelah
 * Distributor + 20 poin PA), jadi harganya memakai paket Distributor.
 */
class PackagePrice
{
    /** Poin paket untuk sebuah peringkat. */
    public function pointsForRank(string $rank): int
    {
        $peta = (array) config('sahih.points_by_rank', []);

        if (! array_key_exists($rank, $peta)) {
            throw new \InvalidArgumentException("Peringkat tidak dikenal: {$rank}");
        }

        return (int) $peta[$rank];
    }

    /** Harga paket sebuah peringkat dalam rupiah. */
    public function priceForRank(string $rank): int
    {
        return $this->pointsForRank($rank) * (int) config('sahih.registration_price_per_point');
    }

    /** Harga PIN upgrade dari peringkat sekarang ke peringkat tujuan. */
    public function upgradePrice(string $from, string $to): int
    {
        return $this->priceForRank($to) - $this->priceForRank($from);
    }
}
