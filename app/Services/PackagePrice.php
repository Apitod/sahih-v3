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
    public function __construct(private PengaturanHarga $harga) {}

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
        return $this->pointsForRank($rank) * $this->harga->ambil('harga_pin_registrasi');
    }

    /** Harga PIN upgrade dari peringkat sekarang ke peringkat tujuan. */
    public function upgradePrice(string $from, string $to): int
    {
        return $this->priceForRank($to) - $this->priceForRank($from);
    }
}
