# Phase 6 — Stage C: Arsitektur & Desain Data

Status: **USULAN — menunggu persetujuan Owner.** Belum ada kode, belum ada migrasi dijalankan.
Acuan angka: `BUSINESS_RULES.md`. Acuan tampilan: `phase-6b-design-system.md`. Acuan lingkup: `phase-6a-product-definition.md`.

Stack sudah diputuskan Owner: **Laravel + Blade + Livewire + MySQL**, hosting Rumahweb, subdomain.

---

## 1. Evaluasi stack — apa yang sudah terbukti, apa yang belum

| Komponen | Kondisi | Label |
|---|---|---|
| PHP | 8.3.6 + ekstensi Laravel lengkap (mbstring, dom, curl, zip, bcmath, intl, gd, pdo_mysql, sqlite3) | VERIFIED — dipasang 30 Okt atas izin Owner |
| **Composer** | **2.11.0** di `/usr/local/bin/composer` | VERIFIED — dipasang 30 Okt atas izin Owner |
| **MariaDB lokal** | **10.11.14** di **port 3307**, database `sahih_smart_store`, kredensial di `~/.sahih-db-credentials` (chmod 600) | VERIFIED — uji tulis/baca PDO lolos |
| Laravel | Belum dipasang — menunggu persetujuan daftar paket | VERIFIED (belum ada) |
| Node & npm | v26.12.1 / 12.0.2 | VERIFIED — cukup untuk build aset |
| Hosting Rumahweb | 5 pertanyaan belum dijawab (PHP version, MySQL, SSH, Composer, cron) | UNKNOWN |
| Git | 2.43 | VERIFIED |

**Kesimpulan:** stack lengkap dan siap. Rincian pemasangan 30 Okt:

- **Port 3307, bukan 3306.** Di mesin ini sudah ada container `mysql:8.0` milik proyek lain (`thesa_ai_db`) yang memakai 3306. Saya tidak menyentuhnya — tidak membuat database di dalamnya, tidak membaca isinya. MariaDB untuk proyek ini jalan terpisah di 3307 dengan datadir sendiri (`/etc/mysql/mariadb.conf.d/99-sahih-dev.cnf`).
- Ekstensi PHP yang kurang (mbstring, dom, curl, zip, bcmath, intl, gd, pdo_mysql) dipasang lewat paket `php8.3-*` resmi Ubuntu.
- Kredensial database dibuat acak, disimpan di `~/.sahih-db-credentials` (chmod 600), **tidak pernah dicetak dan tidak masuk repo**.

Yang belum ada: Laravel itu sendiri. Daftar paketnya menunggu persetujuan (§9).

---

## 2. Model domain

14 tabel. Nama mengikuti konvensi Laravel (snake_case, jamak). Semua uang **integer rupiah**, semua poin **integer**, tidak ada `float` di mana pun.

### 2.1 `members` — akun anggota

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `username` | varchar(30) UNIQUE | dipakai sebagai kode referral |
| `name`, `phone`, `address` | varchar | |
| `password` | varchar | hash |
| `sponsor_id` | bigint FK → members, **NULL diizinkan** | upline langsung. NULL = mendaftar tanpa kode referral (diizinkan Owner 30 Okt) |
| `rank` | enum(reseller, agent, distributor, manager) | peringkat sekarang |
| `is_manager` | bool | turunan, untuk query cepat |
| `manager_since` | timestamp NULL | |
| `point_pa`, `point_pr`, `point_pl` | int default 0 | **cache** dari ledger, bukan sumber kebenaran |
| `status` | enum(active, suspended) | |
| `joined_at` | timestamp | |
| timestamps | | |

Index: `sponsor_id`, `rank`, `status`. Tidak ada kolom saldo uang di sini — saldo dihitung dari `bonus_entries`.

### 2.2 `admins` — akun pengelola

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id`, `name`, `email` UNIQUE, `password` | | |
| `role` | enum(admin, superadmin) | 2 admin + 1 superadmin |
| `is_active` | bool | |
| timestamps | | |

Tabel terpisah dari `members`, dengan guard autentikasi sendiri. Alasan di ADR-2.

### 2.3 `pins` — PIN

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | | |
| `code` | varchar(16) UNIQUE | yang diinput anggota |
| `type` | enum(registration, upgrade, ro) | |
| `points` | int | 1/3/6 untuk registrasi; 2/5/3 untuk upgrade; bebas untuk RO |
| `price` | int | rupiah yang dibayar ke admin |
| `status` | enum(issued, used, void) | **tidak ada kedaluwarsa** |
| `issued_by` | FK → admins | |
| `assigned_member_id` | FK → members NULL | PIN sudah ditempel ke akun siapa |
| `used_by_member_id` | FK → members NULL | |
| `used_at` | timestamp NULL | |
| timestamps | | |

### 2.4 `transactions` — satu baris = satu kejadian uang

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | | |
| `code` | varchar UNIQUE | nomor transaksi yang dibaca manusia |
| `type` | enum(join, upgrade, ro) | |
| `member_id` | FK → members | pelaku |
| `pin_id` | FK → pins **UNIQUE** | **kunci anti-dobel** |
| `points` | int | poin yang dibeli |
| `amount` | int | rupiah |
| `sponsor_bonus_total` | int | total bonus yang dibagikan (≤ 20%). **0 kalau pelaku tidak punya upline** |
| `posted_at` | timestamp | |
| `created_by` | FK → admins NULL | NULL kalau diposting anggota sendiri |
| timestamps | | |

`UNIQUE(pin_id)` membuat satu PIN mustahil dipakai dua kali, bahkan kalau anggota menekan tombol dua kali atau koneksi putus lalu diulang.

### 2.5 `point_entries` — ledger poin (hanya ditambah)

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | | |
| `member_id` | FK | penerima poin |
| `kind` | enum(pa, pr, pl) | tiga jenis, tidak bisa saling tukar |
| `delta` | int | positif; negatif hanya untuk koreksi |
| `source_type` | varchar | `transaction`, `correction`, `migration` |
| `source_id` | bigint | id transaksi asal |
| `note` | varchar NULL | alasan, wajib untuk koreksi |
| `created_at` | timestamp | |

Index: `(member_id, kind)`, `(source_type, source_id)`. **Tidak ada `updated_at`** — baris tidak pernah diubah.

### 2.6 `bonus_entries` — ledger bonus (hanya ditambah)

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | | |
| `member_id` | FK | penerima |
| `kind` | enum(sponsor, differential, leadership, rm) | |
| `amount` | int | rupiah |
| `percent_bp` | int NULL | basis point: 1500 = 15%. NULL untuk leadership |
| `level` | int NULL | jarak dari pelaku |
| `source_transaction_id` | FK → transactions NULL | |
| `status` | enum(pending, available, withdrawn, void) | |
| `available_at` | timestamp NULL | sponsor: langsung; leadership: awal bulan |
| `created_at` | timestamp | |

### 2.7 `rank_histories`

`id`, `member_id`, `from_rank`, `to_rank`, `transaction_id` NULL, `note`, `created_at`. Menjawab "sejak kapan dia Distributor" tanpa menebak.

### 2.8 `milestone_rewards` — konfigurasi hadiah

`id`, `threshold` int, `name` varchar, `value_internal` int NULL, `is_active` bool, `sort` int, timestamps.
Diisi 5 ambang awal (5/20/75/500/3.500). **Dinamis** — Owner bisa ubah kapan saja (§8 `BUSINESS_RULES.md`). `value_internal` tidak pernah dikirim ke tampilan anggota.

### 2.9 `milestone_claims`

`id`, `member_id`, `milestone_reward_id`, `cycle` int default 1, `status` enum(eligible, requested, handed, rejected), `requested_at`, `handed_at`, `handled_by`, `note`.
**UNIQUE(member_id, milestone_reward_id, cycle)** — inilah yang membuat hadiah kumulatif tetap tidak bisa diklaim dua kali, dan tetap bisa terulang setelah 3.500 poin (cycle 2).

### 2.10 `withdrawals`

`id`, `member_id`, `amount` int, `bank_name`, `account_number`, `account_holder`, `status` enum(requested, approved, paid, rejected), `requested_at`, `processed_by`, `processed_at`, `note`, `proof_ref` NULL.
Aturan: `amount ≥ 100000`, dan `amount ≤ saldo tersedia`.

### 2.11 `audit_logs`

`id`, `actor_type` enum(member, admin, system), `actor_id`, `action`, `subject_type`, `subject_id`, `before` json NULL, `after` json NULL, `ip`, `created_at`.

### 2.12 `settings`

`key` UNIQUE, `value` json, `updated_by`, `updated_at`. Isi awal: harga poin daftar, harga poin RO, tarif peringkat, minimum penarikan, aturan milestone aktif.
Semua angka yang bisa berubah tanpa mengubah kode ada di sini — bukan tersebar di file.

### 2.13–2.14 Tabel bawaan Laravel

`sessions`, `password_reset_tokens`, `jobs`, `failed_jobs`. Tidak dirancang khusus, dipakai apa adanya.

---

## 3. Siapa boleh menulis apa

| Data | Penulis | Pembaca | Tidak boleh |
|---|---|---|---|
| Profil anggota (nama, telepon, alamat, password) | anggota itu sendiri | anggota itu, admin | anggota lain |
| Poin (PA/PR/PL) | **sistem saja**, lewat transaksi terposting | anggota sendiri, admin | anggota, admin, bahkan superadmin — tidak ada jalur edit poin |
| Peringkat | sistem, dari transaksi PIN upgrade | semua yang berhak | diubah manual |
| PIN | admin (buat), anggota (pakai) | admin, pemilik PIN | anggota membuat PIN sendiri |
| Bonus | sistem, saat transaksi diposting | penerima, admin | diubah nominalnya |
| Penarikan | anggota (ajukan), admin (proses) | anggota, admin | admin mengubah nominal pengajuan |
| Konfigurasi (harga, tarif, hadiah) | **superadmin** | semua | admin |
| Akun admin | **superadmin** | superadmin | admin |
| Ledger apa pun | **hanya ditambah** | — | UPDATE/DELETE oleh siapa pun, termasuk superadmin |

Koreksi data tidak dilakukan dengan mengedit baris. Koreksi = entri balik (`delta` negatif atau `bonus_entries` bertanda `void`) dengan `note` wajib dan tercatat di `audit_logs`. Dengan begitu pertanyaan "kenapa poin saya berubah" selalu ada jawabannya.

---

## 4. Izin per peran

| Kemampuan | Anggota | Admin | Superadmin |
|---|---|---|---|
| Lihat profil sendiri | ✓ | ✓ | ✓ |
| Ubah profil sendiri | ✓ | ✓ | ✓ |
| Lihat poin & riwayat sendiri | ✓ | ✓ | ✓ |
| Lihat jaringan (nama + peringkat) | ✓ | ✓ | ✓ |
| Input PIN | ✓ | ✓ | ✓ |
| Ajukan klaim hadiah | ✓ | ✓ | ✓ |
| Ajukan penarikan | ✓ | ✓ | ✓ |
| Cari anggota mana pun | ✗ | ✓ | ✓ |
| Buat / batalkan PIN | ✗ | ✓ | ✓ |
| Proses klaim hadiah | ✗ | ✓ | ✓ |
| Proses penarikan | ✗ | ✓ | ✓ |
| Lihat semua transaksi | ✗ | ✓ | ✓ |
| Koreksi dengan entri balik | ✗ | ✓ | ✓ |
| Ubah harga / tarif / daftar hadiah | ✗ | ✗ | ✓ |
| Kelola akun admin | ✗ | ✗ | ✓ |
| Lihat audit log | ✗ | ✗ | ✓ |

Ditegakkan di dua lapis: middleware rute + policy per aksi. Bukan hanya menyembunyikan tombol.

---

## 5. Integritas transaksi — 12 aturan yang tidak boleh dilanggar

1. Satu transaksi = satu database transaction. `transactions`, `point_entries`, `bonus_entries`, `rank_histories`, dan pembaruan cache commit bersama. Gagal satu → batal semua.
2. `UNIQUE(pin_id)` di `transactions` — pertahanan terakhir terhadap dobel posting.
3. PIN diambil dengan `SELECT ... FOR UPDATE` sebelum diposting, lalu status diubah ke `used` dalam transaksi yang sama.
4. Baris penerima bonus dikunci (`FOR UPDATE`) sebelum saldonya disentuh, supaya dua transaksi bersamaan tidak saling menimpa.
5. Ledger **append-only**. Tidak ada UPDATE, tidak ada DELETE. Koreksi = entri balik.
6. Saldo & total poin dihitung dari `SUM(ledger)`. Kolom cache (`members.point_*`) hanya untuk kecepatan dan **selalu bisa dibangun ulang** lewat perintah `points:rebuild`.
7. Semua uang integer rupiah. Persen disimpan basis point (`1500` = 15%). Tidak ada `float` dalam perhitungan uang.
8. Bonus dibulatkan ke bawah ke rupiah penuh per penerima. Sisa pembulatan tidak dibagikan — total tetap ≤ 20% dari nilai transaksi.
9. Milestone dinilai dari `SUM(point_entries where kind=pr)` setelah commit, bukan dari angka yang dikirim browser.
10. Penarikan hanya boleh dari bonus berstatus `available`; saldo dikunci saat pengajuan supaya tidak bisa ditarik dua kali.
11. Setiap aksi tulis pengelola masuk `audit_logs`: siapa, kapan, sebelum, sesudah, IP.
12. Job terjadwal idempoten: kunci unik `(job_type, period)` supaya menjalankan ulang tidak menggandakan hasil.

**Uji wajib sebelum dianggap jadi** — empat kasus baku §3 `BUSINESS_RULES.md` harus keluar persis: Rp 120.000 / Rp 120.000 / Rp 90.000 / Rp 360.000. Plus: RO 10 poin pada Reseller tetap Reseller; klaim hadiah tidak mengurangi poin; PIN sama dipakai dua kali ditolak.

---

## 6. Migrasi & backup

**Migrasi**
- Semua perubahan skema lewat Laravel migration, satu arah, urut. Migrasi yang sudah jalan tidak pernah diedit — perbaikan = migrasi baru.
- Seeder hanya untuk data acuan: `settings` awal, 5 `milestone_rewards`, akun superadmin pertama. Password superadmin dibuat saat instalasi, **tidak ditulis di repo**.
- Sebelum migrasi di server: backup wajib, dan dicatat di `CHANGELOG` internal.

**Backup**
- `mysqldump` harian 02.00 WITA, simpan **7 harian + 4 mingguan**.
- Salinan diunduh keluar hosting (bukan hanya di server yang sama) — backup di server yang sama bukan backup.
- Uji restore **sebulan sekali**, dicatat hasilnya. Backup yang belum pernah diuji restore belum terbukti.
- Akses backup hanya superadmin.

**Data lama**
**Tidak ada migrasi.** Owner memutuskan mulai dari nol (30 Okt). Artinya tidak perlu pemetaan poin/sponsor/riwayat dari sistem lama, dan sistem lama tidak disentuh. Kalau nanti ternyata ada data yang harus dibawa, itu pekerjaan terpisah dengan desain sendiri.

---

## 7. Keputusan arsitektur (ADR)

| # | Keputusan | Alasan | Alternatif yang ditolak |
|---|---|---|---|
| 1 | Tabel `members` dan `admins` terpisah, dua guard | Salah konfigurasi policy pada satu tabel campuran bisa membuat anggota biasa masuk panel admin | Satu tabel `users` + kolom role |
| 2 | Poin disimpan sebagai ledger, bukan kolom saldo | Harus bisa menjawab "poin ini dari mana"; kolom saldo tidak bisa diaudit | Kolom `point` yang di-`increment` |
| 3 | Uang integer rupiah, persen basis point | Tidak ada galat pembulatan; aturan A1 bilang poin bulat | `decimal`/`float` |
| 4 | Satu PIN = satu transaksi, dijaga `UNIQUE` | Dobel posting adalah risiko paling mahal di sistem komisi | Cek di kode saja |
| 5 | Hadiah milestone pakai `cycle` | Hadiah berulang tiap 3.500 poin tetap tidak bisa diklaim dua kali | Tanpa kolom `cycle` |
| 6 | Koreksi lewat entri balik, bukan edit | Riwayat harus utuh; audit jadi mungkin | Edit langsung + log |
| 7 | Konfigurasi angka di tabel `settings` | Owner bisa ubah harga/hadiah tanpa deploy | Konstanta di kode |
| 8 | Hak akses dua lapis (rute + policy) | Menyembunyikan tombol bukan keamanan | Cek di Blade saja |
| 9 | Tanpa REST API publik di v1.0 | Semua interaksi lewat Livewire; API publik menambah permukaan serang tanpa kebutuhan | API + SPA |
| 10 | Job terjadwal idempoten | Penjadwal bisa jalan dua kali (retry, deploy) | Asumsi jalan sekali |
| 11 | Rantai sponsor ditelusuri **satu per satu di PHP**, bukan recursive CTE | Versi MySQL di hosting belum diketahui; loop naik satu level jalan di MySQL 5.7 maupun 8. Kedalaman rantai kecil karena bonus dibatasi 20% | Recursive CTE (butuh MySQL 8) |
| 12 | `sponsor_id` boleh NULL | Kode referral opsional (keputusan Owner). Tanpa upline, transaksi tetap sah dengan bonus sponsor Rp 0 | Mewajibkan akun "pusat" sebagai sponsornya |

---

## 8. Yang perlu diputuskan sebelum Stage D

| # | Hal | Usul saya | Kenapa penting |
|---|---|---|---|
| 1 | Izin pasang Composer + MariaDB lokal | — | **SELESAI** — Owner: "boleh". Terpasang & teruji 30 Okt |
| 2 | Anggota pertama / kode referral | **SELESAI** — kode referral **opsional**, jadi tidak perlu akun "pusat". Tanpa referral: tanpa upline, bonus sponsor Rp 0 | — |
| 3 | Data anggota lama | **SELESAI** — mulai dari nol, tanpa migrasi data | Tidak ada tahap migrasi data |
| 4 | Versi MySQL hosting | **BELUM PERLU** — hosting belum diputuskan. Rantai sponsor ditelusuri di PHP (ADR-11), jadi tidak bergantung versi MySQL | — |
| 5 | Manager otomatis? | **SELESAI** — otomatis saat PA ≥ 20 dan sudah Distributor, tercatat di `rank_histories` | — |
| 6 | Tanpa pajak, poin bulat, minimum tarik Rp 100.000 | Sudah sesuai `BUSINESS_RULES.md` | — |

---

## 9. Daftar paket yang akan dipasang (menunggu izin)

| Paket | Versi | Untuk apa | Bisa dilepas? |
|---|---|---|---|
| `laravel/framework` | 11.x | kerangka aplikasi | tidak — inti |
| `livewire/livewire` | 3.x | halaman reaktif tanpa menulis JavaScript | ya, tapi semua tampilan ditulis dengan ini |
| `tailwindcss` (npm) | 4.x | CSS sesuai token Stage B | ya |
| `lucide` (npm) | terbaru | set ikon (keputusan Owner) | ya |
| `pestphp/pest` | 3.x | uji otomatis 4 kasus baku + 12 aturan integritas | ya |

Tidak ada paket lain di luar ini. Tidak ada paket komisi/MLM pihak ketiga — semua perhitungan ditulis sendiri supaya bisa diperiksa terhadap `BUSINESS_RULES.md`. Tidak ada paket analitik, tidak ada paket yang menghubungi server luar saat aplikasi jalan.

## Gerbang Stage C

Stage C selesai kalau Anda setuju: 14 tabel §2, aturan kepemilikan §3, matriks izin §4, dan 12 aturan integritas §5.

Semua nomor di §8 sudah dijawab. Alat pengembangan terpasang dan teruji. Yang tersisa sebelum Stage D: **persetujuan daftar paket** di §9 — karena direktif melarang saya memasang paket tanpa izin, dan Laravel belum terpasang.
