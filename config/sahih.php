<?php

// Semua angka di sini berasal dari PRD v3.0 (2 Nov 2026). Kalau aturan berubah,
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

    // Rantai upline untuk bonus sponsor (DECISIONS #60, PRD v3.0 §3.4):
    // dinaikkan dari 20 ke 50 tingkat atas permintaan Owner.
    // Kalau dalam 50 tingkat tidak ada peringkat lebih tinggi, sisa selisih hangus.
    'sponsor_chain_max_level' => 50,

    // Rantai upline untuk poin leadership. Kompresi berhenti di Manager pertama,
    // jadi 50 sudah lebih dari cukup.
    'leadership_chain_max_level' => 50,

    // Peringkat yang didapat dari jumlah poin yang dibeli saat mendaftar.
    'rank_by_points' => [
        1 => 'reseller',
        3 => 'agent',
        6 => 'distributor',
    ],

    // Ambang PR untuk naik peringkat otomatis (PRD v3.0 §3.3).
    // Total PR — termasuk PR dari anggota yang kita sponsori — menentukan peringkat.
    'rank_by_pr' => [
        'reseller'    => 1,
        'agent'       => 3,
        'distributor' => 6,
    ],

    // Syarat jadi Manager: sudah Distributor + 20 poin PA.
    'manager_min_pa' => 20,

    // Batas tampilan poin afiliasi di halaman anggota (Owner 10 Nov 2026).
    // Angka asli tetap dipakai syarat peringkat & Manager.
    'pa_display_cap' => 20,

    // Minimum penarikan. Dinaikkan dari 100.000 ke 120.000 karena penarikan wajib
    // kelipatan Rp40.000 (1 poin PL) — 100.000 bukan kelipatan (Owner 10 Nov 2026).
    'withdraw_min' => 120000,

    // Penarikan wajib bulat kelipatan nilai 1 poin PL supaya konversi poin tidak
    // menyisakan pecahan.
    'withdraw_multiple_of' => 40000,

    // Harga PIN repeat order (RO) per pin. Bisa diubah Owner (DECISIONS #56).
    'ro_price_per_point' => 500000,

    // Harga PIN registrasi / upgrade per pin. Bisa diubah Owner (PRD v3.0 §4).
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

    // Pencairan bonus ke anggota. DITAHAN: DECISIONS #57 dan #67 menyatakan modul
    // pencairan belum dibangun sampai aturan bonus final. Kerangka kode sudah ada,
    // tinggal set true setelah Owner mengonfirmasi aturan pencairan.
    'pencairan_aktif' => env('SAHIH_PENCAIRAN_AKTIF', false),

    // Nilai rupiah per poin leadership — internal, tidak ditampilkan ke anggota.
    'leadership_value_per_point' => 40000,

    // Royal Manager: nilai per poin PL yang diterima Manager langsung di bawahnya.
    // 2.000 = 5% dari 40.000 (BUSINESS_RULES §5). Internal.
    'royal_manager_value_per_point' => 2000,

    // Nilai 1 poin sebagai satuan hitung. Internal, tidak ditampilkan ke anggota.
    'point_unit_value' => 50000,

    // Akun admin pertama dari seeder. Dibaca lewat config supaya tetap jalan
    // setelah `php artisan config:cache` (env() langsung jadi null saat itu).
    'admin_email' => env('ADMIN_EMAIL', 'admin@sahih.local'),
    'admin_password' => env('ADMIN_PASSWORD', 'admin123'),
];
