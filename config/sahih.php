<?php

// Semua angka di sini berasal dari BUSINESS_RULES.md. Kalau aturan berubah,
// ubah di sini dan di dokumen — bukan di dalam kode lain.
return [
    // Tarif bonus sponsor per peringkat, dalam basis point (1500 = 15%).
    'rank_rate_bp' => [
        'reseller'    => 1500,
        'agent'       => 1800,
        'distributor' => 2000,
        'manager'     => 2000, // DECISIONS #53: tarif bonus sponsor Manager = 20%.
    ],

    // Batas total bonus sponsor per transaksi: 20%.
    'sponsor_cap_bp' => 2000,

    // Peringkat yang didapat dari jumlah poin yang dibeli saat mendaftar.
    'rank_by_points' => [
        1 => 'reseller',
        3 => 'agent',
        6 => 'distributor',
    ],

    // Syarat jadi Manager: sudah Distributor + 20 poin PA.
    'manager_min_pa' => 20,

    // Minimum penarikan.
    'withdraw_min' => 100000,

    // Harga PIN repeat order (RO) per poin mengikat (DECISIONS #56).
    'ro_price_per_point' => 500000,

    // Harga poin paket pendaftaran (BUSINESS_RULES.md §1–§2): 1 poin Rp 600.000.
    'registration_price_per_point' => 600000,

    // Poin paket per peringkat. Dipakai menghitung harga PIN upgrade (DECISIONS #59).
    // Manager memakai paket Distributor: Manager bukan paket, tapi syarat otomatis (#58).
    'points_by_rank' => [
        'reseller' => 1,
        'agent' => 3,
        'distributor' => 6,
        'manager' => 6,
    ],

    // Panjang satu putaran hadiah milestone, dikunci di 3.500 poin (DECISIONS #61).
    // Tidak mengikuti ambang tertinggi yang sedang aktif: mematikan hadiah 3.500
    // tidak boleh mengubah hitungan hadiah anggota.
    'milestone_cycle_points' => 3500,
];
