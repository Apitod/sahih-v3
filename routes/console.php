<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tutup buku bonus bulanan = tanggal 1 (keputusan Owner, 8 Nov 2026).
// Tanpa --bulan, command memproses bulan lalu — tepat setelah bulan itu tutup.
// Zona waktu dipatok WITA supaya tanggal 1 tetap tanggal 1 bagi Owner, bukan UTC.
Schedule::command('sahih:proses-bonus-bulanan')->monthlyOn(1, '01:00')->timezone('Asia/Makassar');
