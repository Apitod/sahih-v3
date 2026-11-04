# Smart Store Sahih

Aplikasi web untuk mengelola keanggotaan, penjualan paket (PIN), poin, bonus, dan
hadiah milestone sebuah jaringan reseller/distribusi. Dibangun dengan
**Laravel 12**, **Livewire 4**, **Tailwind CSS 4**, dan **Vite**.

Aplikasi punya dua jenis pengguna dengan area terpisah:

- **Anggota** — login dengan `username`, melihat poin, menukar PIN (upgrade / repeat order), dan melihat hadiah.
- **Admin** — login dengan `email`, menerbitkan & membatalkan PIN, dan memantau transaksi.

> Aturan bisnis (harga paket, tarif bonus, syarat peringkat, hadiah milestone)
> berpusat di [`config/sahih.php`](config/sahih.php). Ubah di situ, bukan di dalam
> controller.

---

## Daftar Isi

- [Fitur](#fitur)
- [Peran (Role) & Hak Akses](#peran-role--hak-akses)
- [Akun Bawaan (Seeder)](#akun-bawaan-seeder)
- [Kebutuhan Sistem](#kebutuhan-sistem)
- [Cara Setup](#cara-setup)
- [Menjalankan Aplikasi](#menjalankan-aplikasi)
- [Perintah Berguna](#perintah-berguna)
- [Halaman & Rute](#halaman--rute)
- [Menjalankan Test](#menjalankan-test)
- [Struktur Direktori Penting](#struktur-direktori-penting)
- [Keamanan](#keamanan)

---

## Fitur

### Area Anggota
- **Login** dengan `username` + password (rate limit 10 percobaan/menit per username+IP).
- **Beranda** — total poin PA / PR / PL (dihitung dari ledger `point_entries`, bukan kolom cache),
  10 riwayat poin terakhir, hadiah milestone yang diklaim, dan info sponsor.
- **Tukar PIN** — menukar PIN **upgrade** (menaikkan peringkat) atau **RO** (repeat order).
  PIN registrasi sengaja ditolak di sini karena dipakai admin saat mendaftarkan anggota baru.
- Akun berstatus `suspended` otomatis ditolak saat login (middleware `active.member`).

### Area Admin
- **Dashboard** — jumlah anggota, jumlah PIN belum dipakai, jumlah transaksi, 8 transaksi terakhir.
- **Kelola PIN**
  - Terbitkan PIN dengan dua jenis: `registrasi_upgrade` dan `ro` (1–50 sekaligus).
    Satu PIN `registrasi_upgrade` bisa dipakai untuk **mendaftarkan anggota baru**
    (tanpa peringkat tujuan) atau **menaikkan peringkat** (dengan peringkat tujuan).
  - **Username wajib diketik dua kali** saat menerbitkan PIN, supaya tidak salah pemegang
    (PIN salah pemegang = salah harga). Username yang belum terdaftar hanya boleh untuk
    PIN pendaftaran; untuk naik peringkat anggotanya harus sudah punya akun.
  - Kode 12 karakter memakai alfabet tanpa huruf ambigu (`O/0`, `I/1`, `L`) agar bisa dibacakan lewat telepon.
  - **Harga dihitung sistem, bukan diketik admin** (angka per pin dari `/admin/pengaturan`):
    - PIN `registrasi_upgrade` = jumlah pin × Rp 600.000.
    - PIN `ro` = jumlah pin × Rp 500.000.
    - Harga yang tidak sesuai hitungan ditolak, baik saat PIN dibuat maupun saat dipakai.
  - **Batalkan (void)** PIN yang belum dipakai, dengan alasan wajib.
  - **PIN pendaftaran** (tanpa peringkat tujuan) dijual untuk mendaftarkan anggota baru.
    Alur registrasi anggota lewat web belum dibangun — status `belum sampai tahap itu`.
    PIN-nya tetap sah dan tidak kedaluwarsa, dipakai begitu alurnya ada.
- Semua aksi admin tercatat di tabel `audit_logs` (aktor, aksi, before/after, IP).

### Domain / Aturan Bisnis
- **Peringkat anggota** (naik): `reseller → agent → distributor → manager`.
  Naik **otomatis dari total Poin Reward (PR)**: 1 PR = Reseller, 3 PR = Agent, 6 PR = Distributor.
  PR dari anggota yang kita sponsori ikut dihitung. Selain itu tetap bisa naik lewat **PIN upgrade**.
  Manager **tidak dijual lewat PIN** — otomatis didapat setelah Distributor + 20 poin PA.
- **Tiga jenis poin**: PA (syarat Manager), PR (hadiah + peringkat), PL (bonus leadership).
  **RO memberi PR + PA** ke pembeli; sponsor hanya dapat PR dari RO.
- **Poin leadership** hanya dicatat untuk **Manager**. Sebelum jadi Manager, PL hangus —
  tidak ditabung dan tidak dihitung di kemudian hari. Manager yang RO sendiri ikut dapat PL.
- **Bonus sponsor** per peringkat: Reseller 15%, Agent 18%, Distributor 20%, Manager 20%,
  dengan batas total 20% per transaksi, rantai ditelusuri sampai **50 tingkat**.
  Sisa selisih yang tidak terbayar hangus (tidak masuk ke siapa pun).
- **Hadiah milestone** dengan ambang 5 / 20 / 75 / 500 / 3.500 poin, berulang per siklus 3.500 poin.
  Hadiah yang ambangnya di atas 3.500 dinilai dari total PR (tidak lagi diblokir).
- **Harga & ambang bisa diubah Owner** lewat `/admin/pengaturan` (khusus Superadmin):
  harga per pin registrasi/upgrade, harga per pin RO, nilai poin leadership, syarat PA Manager,
  dan putaran hadiah. Tersimpan di tabel `settings`, dengan `config/sahih.php` sebagai nilai awal.
- **Nilai rupiah tidak pernah ditampilkan ke anggota** — halaman anggota hanya menampilkan
  angka poin dan nama hadiah.
- **Penarikan (withdrawal)** dengan minimum Rp 100.000.

---

## Peran (Role) & Hak Akses

Aplikasi memakai **dua guard terpisah**, sehingga sesi admin dan anggota tidak pernah tercampur.

| Peran | Model | Login (field) | Guard | Akses |
|---|---|---|---|---|
| **Anggota** | `App\Models\Member` | `username` + password | `web` | Beranda, tukar PIN, riwayat poin |
| **Admin** | `App\Models\Admin` | `email` + password | `admin` | Dashboard, kelola PIN, batalkan PIN |
| Admin `superadmin` | `App\Models\Admin` | `email` + password | `admin` | Sama seperti admin (kolom `role`) |

Catatan role:
- **Member peringkat** (`member.rank`): `reseller`, `agent`, `distributor`, `manager`.
- **Member status** (`member.status`): `active` (bisa login) atau `suspended` (ditolak, diarahkan kembali ke login).
- **Admin role** (`admin.role`): `admin` atau `superadmin`.
- **Admin aktif** (`admin.is_active`): jika `false`, login ditolak.

Rute dilindungi middleware:
- `auth:web` + `active.member` untuk area anggota.
- `auth:admin` + `active.admin` untuk area admin.

---

## Akun Bawaan (Seeder)

Jalankan `php artisan db:seed` untuk membuat akun contoh dari `DatabaseSeeder`.

| Peran | Login | Password | Keterangan |
|---|---|---|---|
| **Anggota (demo)** | `demo` | `demo12345` | Anggota contoh, peringkat Reseller, sudah punya 3 poin PR + 1 poin PA |
| **Anggota (sponsor)** | `sponsor` | `sponsor123` | Sponsor dari `demo` |
| **Admin** | `admin@sahih.local` | `admin123` | Role `superadmin` |

> ⚠️ **Password di atas hanya untuk pengembangan lokal.** Seeder aslinya membuat
> password **acak** untuk admin, lalu menuliskannya ke `~/.sahih-admin-credentials`
> (izin `600`). Instalasi ini sudah disesuaikan agar memakai password tetap supaya
> mudah dipakai. **Ganti sebelum deploy ke produksi.**

Untuk reset password kapan saja:

```bash
# Admin
php artisan tinker --execute="\App\Models\Admin::where('email','admin@sahih.local')->update(['password'=>bcrypt('password-baru')]);"

# Anggota
php artisan tinker --execute="\App\Models\Member::where('username','sponsor')->update(['password'=>bcrypt('password-baru')]);"
```

Seeder juga mengisi **5 hadiah milestone** (5, 20, 75, 500, 3.500) pada `milestone_rewards`.

---

## Kebutuhan Sistem

| Kebutuhan | Versi |
|---|---|
| PHP | ≥ 8.2 (diuji dengan 8.3) |
| Composer | 2.x |
| Node.js | ≥ 20 (diuji dengan 22) |
| NPM | 10.x |
| Database | SQLite (default) atau MySQL/MariaDB |

### Ekstensi PHP wajib

```bash
php -m | grep -E "pdo_sqlite|sqlite3|mbstring|openssl|tokenizer|xml|ctype|fileinfo|curl"
```

Untuk database SQLite, **`pdo_sqlite` wajib terpasang**. Sebelum memastikan,
cek cepat:

```bash
php -r "print_r(PDO::getAvailableDrivers());"
```

Harus muncul `sqlite` di dalam `Array`. Jika kosong / hanya `PDO` tanpa driver:

```bash
# Debian / Ubuntu / Linux Mint
sudo apt update && sudo apt install -y php8.3-sqlite3

# Cek nama paket sesuai versi PHP
apt-cache search php | grep sqlite
```

Untuk MySQL, gunakan `php8.3-mysql` (`pdo_mysql`) dan buat database dulu.

---

## Cara Setup

### 1. Masuk ke direktori proyek

```bash
cd /home/apitod/codingan/sahih/sahih-v3
```

### 2. Pasang dependensi PHP & Node

```bash
composer install
npm install
```

### 3. Siapkan file `.env` & application key

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Konfigurasi database

**Opsi A — SQLite (paling cepat, default proyek):**

```bash
touch database/database.sqlite
```

Pastikan `.env` berisi:

```dotenv
DB_CONNECTION=sqlite
```

**Opsi B — MySQL / MariaDB:**

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sahih
DB_USERNAME=root
DB_PASSWORD=
```

Buat databasenya lebih dulu, misalnya:

```bash
mysql -u root -p -e "CREATE DATABASE sahih CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 5. Jalankan migrasi & seeder

```bash
php artisan migrate
php artisan db:seed
```

Perintah ini membuat tabel, akun `demo` + `sponsor` + admin, dan daftar hadiah.
Kredensial admin ditulis ke `~/.sahih-admin-credentials`.

### 6. Build aset frontend

```bash
npm run build
```

### Setup cepat (satu perintah)

`composer.json` menyediakan skrip `setup` yang menjalankan langkah 2–6 di atas:

```bash
composer setup
```

Skrip ini **tidak** membuat `database/database.sqlite` secara otomatis pada semua
kasus — kalau migrasi gagal dengan error "database does not exist", jalankan
`touch database/database.sqlite` lalu `php artisan migrate --seed`.

---

## Menjalankan Aplikasi

### Mode sederhana (satu proses)

```bash
php artisan serve
```

Buka <http://127.0.0.1:8000>. Aplikasi akan mengarahkan ke `/masuk`.

> Jalankan `npm run build` lebih dulu, atau jalankan `npm run dev` di terminal
> terpisah agar perubahan CSS/JS langsung terlihat.

### Mode pengembangan penuh (server + queue + log + Vite)

```bash
composer dev
```

Menjalankan bersamaan: `php artisan serve`, `queue:listen`, `pail` (log), dan `npm run dev`.
Hentikan semua dengan `Ctrl+C`.

---

## Perintah Berguna

```bash
php artisan migrate:fresh --seed   # Reset total database + isi ulang data contoh
php artisan migrate:status         # Cek status migrasi
php artisan route:list             # Daftar semua rute
php artisan tinker                 # REPL untuk eksperimen
php artisan config:clear           # Bersihkan cache config setelah ubah .env
./vendor/bin/pint                  # Rapikan format kode PHP
```

---

## Halaman & Rute

| Rute | Nama | Akses | Keterangan |
|---|---|---|---|
| `/` | — | publik | Redirect ke beranda atau login |
| `/masuk` | `login` | tamu | Login anggota (username) |
| `POST /masuk` | `login.store` | tamu | Proses login anggota |
| `POST /keluar` | `logout` | publik | Logout semua guard |
| `/beranda` | `beranda` | anggota | Dashboard anggota |
| `/tukar` | `tukar` | anggota | Halaman tukar PIN |
| `POST /tukar` | `tukar.store` | anggota | Proses penukaran PIN |
| `/admin/masuk` | `admin.login` | tamu | Login admin (email) |
| `POST /admin/masuk` | `admin.login.store` | tamu | Proses login admin |
| `POST /admin/keluar` | `admin.logout` | publik | Logout admin |
| `/admin` | `admin.beranda` | admin | Dashboard admin |
| `/admin/pin` | `admin.pin.index` | admin | Daftar & filter PIN |
| `/admin/pin/baru` | `admin.pin.create` | admin | Form terbitkan PIN |
| `POST /admin/pin` | `admin.pin.store` | admin | Simpan PIN baru |
| `POST /admin/pin/{pin}/batalkan` | `admin.pin.void` | admin | Batalkan PIN |

Endpoint health check: `GET /up`.

---

## Menjalankan Test

Test memakai **Pest** dan berjalan di **SQLite in-memory** (lihat `phpunit.xml`),
jadi tidak perlu database terpisah.

```bash
php artisan test
# atau
./vendor/bin/pest
```

Status saat ini: **125 test lulus**.

```bash
./vendor/bin/pest --filter=CommissionRulesTest   # jalankan satu file
```

---

## Struktur Direktori Penting

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/                  # Login anggota & admin
│   │   ├── Admin/                 # Fitur admin (PIN)
│   │   ├── AdminDashboardController.php
│   │   └── MemberDashboardController.php
│   └── Middleware/                # active.member, active.admin, SecurityHeaders
├── Models/                        # Member, Admin, Pin, Transaction, PointEntry, ...
└── Services/
    ├── TransactionPoster.php      # Posting transaksi + poin + bonus (atomik)
    ├── CommissionCalculator.php   # Hitung bonus sponsor & differential
    ├── RankResolver.php           # Bobot & perpindahan peringkat
    ├── PackagePrice.php           # Harga paket & selisih harga upgrade
    └── MilestoneEvaluator.php     # Evaluasi hadiah milestone per siklus

config/sahih.php                   # SEMUA angka aturan bisnis
database/migrations/               # 16 migrasi (members, pins, transactions, ...)
database/seeders/DatabaseSeeder.php
routes/web.php
resources/views/                   # Blade: auth, beranda, tukar, admin/
```

### Tabel utama

| Tabel | Isi |
|---|---|
| `members` | Anggota: username, peringkat, sponsor, cache poin |
| `admins` | Admin: email, role, status aktif |
| `pins` | PIN registrasi/upgrade/RO beserta status & harga |
| `transactions` | Transaksi hasil pemakaian PIN |
| `point_entries` | Ledger poin PA/PR/PL (sumber kebenaran) |
| `bonus_entries` | Bonus sponsor/differential/leadership/rm |
| `rank_histories` | Riwayat perubahan peringkat |
| `milestone_rewards` / `milestone_claims` | Hadiah & klaimnya |
| `withdrawals` | Permintaan penarikan |
| `audit_logs` | Jejak audit aksi admin/sistem |
| `settings` | Konfigurasi dinamis (key/value JSON) |

---

## Keamanan

- Password di-hash dengan **bcrypt** (12 rounds di produksi, 4 di test).
- **Dua guard terpisah** — login admin tidak pernah dianggap login anggota.
- **Rate limiting** pada login anggota & admin (10 percobaan/menit per username+IP),
  serta pada penukaran PIN (60/menit per IP — beberapa anggota bisa satu kantor/IP).
- **CSRF** aktif pada semua form POST (token Laravel).
- Akun `suspended` / admin tidak aktif langsung ditolak meski kredensial benar.
- Semua aksi admin tercatat di `audit_logs` beserta IP.
- Password admin hasil seeder ditulis ke berkas berizin `600`, tidak dicetak ke layar.

---

## Troubleshooting

| Gejala | Penyebab & Solusi |
|---|---|
| `could not find driver (Connection: sqlite)` | Ekstensi `pdo_sqlite` belum terpasang. Lihat [Ekstensi PHP wajib](#ekstensi-php-wajib). |
| `Database file at path [...] does not exist` | Jalankan `touch database/database.sqlite`. |
| Halaman tampil tanpa CSS | Belum build aset. Jalankan `npm run build` atau `npm run dev`. |
| `Vite manifest not found` | Sama seperti di atas — build aset dulu. |
| Perubahan `.env` tidak terbaca | `php artisan config:clear`. |
| Test gagal dengan error MySQL | Pastikan `phpunit.xml` memakai `DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:`. |
| Lupa password admin | Ambil dari `~/.sahih-admin-credentials`, atau reset via `php artisan tinker` (lihat [Akun Bawaan](#akun-bawaan-seeder)). |

---

## Lisensi

Aplikasi ini dibangun di atas [Laravel](https://laravel.com), yang merupakan
perangkat lunak open-source di bawah lisensi [MIT](https://opensource.org/licenses/MIT).
