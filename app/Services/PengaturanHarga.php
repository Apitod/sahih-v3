<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Angka yang boleh diubah Owner tanpa menyentuh kode (PRD v3.0 §3.1).
 *
 * Nilai disimpan di tabel `settings`, dengan config/sahih.php sebagai nilai awal.
 * Kalau barisnya belum ada, config yang dipakai — jadi aplikasi tetap jalan
 * sebelum Owner pernah mengubah apa pun.
 *
 * Yang disimpan di sini HANYA angka rupiah dan ambang. Aturan alur (siapa dapat
 * apa) tetap di kode, karena itu bukan angka.
 */
class PengaturanHarga
{
    /** Kunci yang boleh diubah lewat panel, beserta kunci config asalnya. */
    public const BOLEH_DIUBAH = [
        'harga_pin_registrasi' => 'registration_price_per_point',
        'harga_pin_ro' => 'ro_price_per_point',
        'nilai_poin_leadership' => 'leadership_value_per_point',
        'syarat_manager_pa' => 'manager_min_pa',
        'putaran_hadiah' => 'milestone_cycle_points',
    ];

    /** Nilai sekarang: dari tabel settings kalau ada, kalau tidak dari config. */
    public function ambil(string $kunci): int
    {
        $configKey = self::BOLEH_DIUBAH[$kunci] ?? null;

        if ($configKey === null) {
            throw new \InvalidArgumentException("Kunci pengaturan tidak dikenal: {$kunci}");
        }

        $baris = Setting::where('key', $kunci)->first();

        if ($baris !== null) {
            $nilai = $baris->value;

            // Kolom value di-cast array; kalau isinya skalar, ambil apa adanya.
            if (is_array($nilai)) {
                $nilai = $nilai['nilai'] ?? null;
            }

            if ($nilai !== null && $nilai !== '') {
                return (int) $nilai;
            }
        }

        return (int) config("sahih.{$configKey}");
    }

    /** Semua nilai sekaligus, untuk ditampilkan di panel. */
    public function semua(): array
    {
        $out = [];

        foreach (array_keys(self::BOLEH_DIUBAH) as $kunci) {
            $out[$kunci] = $this->ambil($kunci);
        }

        return $out;
    }

    /** Simpan nilai baru. Hanya menerima kunci yang ada di daftar putih. */
    public function simpan(string $kunci, int $nilai, ?int $adminId = null): void
    {
        if (! array_key_exists($kunci, self::BOLEH_DIUBAH)) {
            throw new \InvalidArgumentException("Kunci pengaturan tidak dikenal: {$kunci}");
        }

        if ($nilai < 0) {
            throw new \InvalidArgumentException('Nilai tidak boleh negatif.');
        }

        Setting::updateOrCreate(
            ['key' => $kunci],
            ['value' => ['nilai' => $nilai], 'updated_by' => $adminId],
        );
    }
}
