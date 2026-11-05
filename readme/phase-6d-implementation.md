# Phase 6 — Stage D: Kerangka & Pembuktian Aturan Uang

Status: **SELESAI untuk lingkup yang disetujui** (kerangka proyek, migrasi tabel, uji kasus baku).
Belum ada tampilan. Belum ada login. Itu Stage E.

Lokasi: `/home/ubuntu/sahih-smart-store`

---

## 1. Apa yang berdiri sekarang

| Bagian | Isi |
|---|---|
| Kerangka | Laravel 12.70.2, Livewire 4.4.8, Tailwind 4, Lucide, Pest 4.7.8 |
| Database | MariaDB 10.11.14, port **3307**, `sahih_smart_store` + `sahih_smart_store_test` |
| Migrasi | 15 tabel, semua jalan di MySQL sungguhan (bukan SQLite) |
| Model | 12: Member, Admin, Pin, Transaction, PointEntry, BonusEntry, RankHistory, MilestoneReward, MilestoneClaim, Withdrawal, AuditLog, Setting |
| Logika | 3 layanan: `CommissionCalculator`, `TransactionPoster`, `MilestoneEvaluator` + `RankResolver` |
| Konfigurasi | `config/sahih.php` — tarif, batas 20%, syarat Manager, minimum tarik |
| Uji | **21 uji, 447 asersi, semua lolos** |

Uji dijalankan di MySQL, bukan SQLite. Alasannya: `UNIQUE(pin_id)`, foreign key, dan `SELECT ... FOR UPDATE` adalah bagian dari yang saya klaim benar — di SQLite semuanya jadi lain.

---

## 2. Empat kasus baku — hasil sungguhan dari uji

| Kasus | Yang diharapkan dokumen | Yang keluar dari kode |
|---|---|---|
| Rantai lengkap | 90.000 + 18.000 + 12.000 = 120.000 | **sama** |
| Peringkat tengah lebih rendah | 108.000 + 0 + 12.000 = 120.000 | **sama** |
| Tidak ada peringkat lebih tinggi | 90.000 + 0 = 90.000 | **sama** |
| Distributor mengajak Agent | 360.000 | **sama** |

Bukan "seharusnya benar" — ini angka yang keluar dari `./vendor/bin/pest`.

## 3. Yang ikut dibuktikan

- PIN sama dipakai dua kali → ditolak, transaksi tetap satu, PIN tetap `used`.
- Rantai 200 kombinasi acak (1–6 level) → total bonus **tidak pernah** lewat 20%.
- Daftar tanpa upline → bonus Rp 0, tidak error.
- Daftar mencatat PA **dan** PR ke sponsor langsung; RO hanya PR.
- RO 10 poin pada Reseller → peringkat tetap Reseller.
- PA tembus 20 + sudah Distributor → otomatis Manager, tercatat di riwayat peringkat.
- PA 20 tapi masih Reseller → **tidak** jadi Manager (syarat dua-duanya).
- Ambang hadiah terlewati → klaim tercatat, poin **tidak berkurang**.
- Saldo hanya menghitung bonus berstatus cair.
- Hadiah terulang: 7.000 poin → 10 klaim (dua putaran); 3.520 poin → 5 klaim putaran satu + 2 klaim putaran dua.
- Upgrade Reseller→Distributor (PIN Rp 3.000.000) dan Agent→Distributor (PIN Rp 1.800.000) mengubah peringkat; PIN upgrade tanpa peringkat tujuan ditolak; Manager tidak turun peringkat.

---

## 4. Penyimpangan dari daftar yang Anda setujui

Tiga, semuanya karena versi yang saya sebut tidak bisa dipasang hari ini:

| Disetujui | Terpasang | Alasan |
|---|---|---|
| `laravel/framework` 11 | **12.70.2** | Semua Laravel 11 diblokir Composer: 8 pengumuman keamanan terbuka (PKSA-b41t-wbzk-9q51 dan lainnya). Memaksa 11 berarti memasang kerangka yang sudah diketahui celahnya, atau mematikan pemeriksaan keamanan. Saya pilih versi aman. |
| `livewire/livewire` 3 | **4.4.8** | Ikut Laravel 12; Livewire 3 tidak mendukungnya. |
| `pestphp/pest` 3 | **4.7.8** | Pest 3 menuntut PHPUnit 11, kerangka Laravel 12 menuntut PHPUnit 12. Batas `phpunit/phpunit` di `composer.json` saya naikkan ke `^12.5` — satu-satunya perubahan batas paket, dan hanya untuk alat uji. |

Tidak ada paket lain di luar daftar. Tidak ada paket MLM, tidak ada paket analitik.

## 5. Satu kesalahan saya, dan apa yang saya lakukan

Waktu menulis konfigurasi database, skrip saya mencetak isi `.env` untuk memastikan barisnya benar — dan ikut mencetak **password database**. Password itu sekarang ada di riwayat percakapan ini.

Yang saya lakukan: password diganti seketika, koneksi diuji ulang dengan password baru, `.env` dan `~/.sahih-db-credentials` diperbarui. Password lama sudah tidak berlaku. Yang saya lakukan salah — seharusnya mencetak nama kolomnya saja, bukan nilainya.

## 6. Yang belum ada (jangan dianggap sudah)

| Hal | Status |
|---|---|
| Login anggota & admin | belum — Stage E |
| Tampilan apa pun | belum |
| Panel admin (buat PIN, proses klaim, penarikan) | belum |
| Alur penarikan uang | tabelnya ada, logikanya belum |
| Bonus leadership bulanan | belum — menunggu tanggal tutup buku (A6) |
| Pemetaan PIN upgrade → peringkat | **selesai** — peringkat tujuan melekat di PIN (§7b) |
| Halaman jaringan | belum |

## 7. Dua hal yang tadinya menggantung — sudah diputuskan Owner

**a. Hadiah berulang — TERULANG.** Ternyata jawabannya sudah ada di `BUSINESS_RULES.md` §8 sejak awal ("hitungan mulai dari nol lagi"); saya yang salah baca dan menandainya UNKNOWN. Kode sudah diperbaiki: tiap putaran penuh 3.500 poin membuka lima hadiah baru. 7.000 poin = 10 klaim; 3.520 poin = 5 + 2.

**b. PIN upgrade — membawa peringkat tujuan.** Nominal dan poin diatur **admin** saat membuat PIN, bukan ditanam di kode. Kolom `pins.target_rank` ditambahkan; PIN upgrade tanpa peringkat tujuan **ditolak** sebelum transaksi terbentuk. Contoh yang diketahui: Reseller→Distributor Rp 3.000.000, Agent→Distributor Rp 1.800.000. Upgrade tidak menurunkan peringkat.

---

## Gerbang Stage D

Yang terbukti: mesin uangnya. Empat kasus baku keluar persis, batas 20% tidak bisa ditembus, PIN tidak bisa dobel, poin tidak bisa hilang karena klaim.

Yang belum: apa pun yang bisa dilihat atau diklik orang.

Sebelum Stage E (login + tampilan), saya perlu jawaban §7a dan §7b — dua-duanya menyentuh data yang sudah berjalan, jadi lebih murah diputuskan sekarang daripada setelah ada anggota sungguhan.
