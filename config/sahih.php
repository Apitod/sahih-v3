<?php

// Semua angka di sini berasal dari BUSINESS_RULES.md. Kalau aturan berubah,
// ubah di sini dan di dokumen — bukan di dalam kode lain.
return [
    // Tarif bonus sponsor per peringkat, dalam basis point (1500 = 15%).
    'rank_rate_bp' => [
        'reseller'    => 1500,
        'agent'       => 1800,
        'distributor' => 2000,
        'manager'     => 2000,
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
];
