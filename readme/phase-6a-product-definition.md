# Phase 6 — Stage A: Definisi Produk

Status: **USULAN — menunggu persetujuan Owner.** Belum ada kode, belum ada keputusan arsitektur.
Acuan angka: `BUSINESS_RULES.md` (kanonik). Kalau file ini berbeda dengan itu, `BUSINESS_RULES.md` yang menang.

Fase sebelumnya: Phase 4 (`phase-4-workflow.md`), Phase 5 (`phase-5-context-standards.md`), Phase 6 Stage A = file ini.

---

## 1. Tujuan website

| # | Tujuan | Kenapa | Ukuran berhasil |
|---|---|---|---|
| T1 | Mengganti pencatatan manual keanggotaan & poin | Sekarang poin, jaringan, dan bonus dihitung orang | Semua anggota punya catatan poin yang bisa dilihat sendiri |
| T2 | Membuat peringkat & poin tidak bisa dikarang | Peringkat hanya naik lewat PIN upgrade yang dibayar | Tidak ada perubahan peringkat tanpa transaksi PIN |
| T3 | Menghitung bonus sponsor otomatis | Hitungan selisih persen rawan salah kalau manual | 4 contoh baku §3 `BUSINESS_RULES.md` keluar persis |
| T4 | Menunjukkan kemajuan anggota (milestone hadiah) | Anggota tahu "kurang berapa poin lagi" | Halaman poin menampilkan ambang berikutnya |
| T5 | Memberi Owner laporan yang bisa dipercaya | Keputusan bisnis butuh angka | Laporan bisa ditelusuri sampai transaksi asal |

Yang **bukan** tujuan v1.0: toko online, pembayaran online, aplikasi mobile.

---

## 2. Peran pengguna

| Peran | Siapa | Boleh | Tidak boleh |
|---|---|---|---|
| **Pengunjung** | Belum punya akun | Lihat penjelasan paket & harga | Daftar tanpa PIN registrasi |
| **Anggota** | Reseller / Agent / Distributor / Manager | Lihat poin & peringkat sendiri, jaringan, riwayat bonus, saldo, input PIN, ajukan klaim hadiah | Lihat data anggota lain di luar jaringannya; ubah poin sendiri |
| **Admin** | **2 orang** kantor yang sekarang bikin PIN | Buat PIN (registrasi/upgrade/RO), kelola anggota, koreksi data, proses klaim hadiah, proses penarikan | Ubah aturan bonus; hapus riwayat transaksi |
| **Superadmin** | **1 orang** (Razan) | Semua yang Admin boleh + ubah daftar hadiah, ubah tarif/konfigurasi, kelola akun admin, lihat semua laporan | — |
| **Penjadwal (sistem)** | Bukan manusia | Hitung bonus harian, tandai milestone tercapai, susun rekap bulanan | Mengubah data yang sudah terposting |

**DIPUTUSKAN Owner (30 Okt):** 2 akun Admin + 1 akun Superadmin. Sisa yang belum jelas: siapa dua orang admin itu, dan apakah Pak Agung termasuk di dalamnya.

Catatan soal peringkat: Reseller → Agent → Distributor → **Manager**. Royal Manager **bukan peringkat**, hanya sebutan untuk Manager yang punya Manager di bawahnya.

---

## 3. Lingkup rilis awal (v1.0)

### Masuk v1.0

| # | Fitur | Sumber |
|---|---|---|
| 1 | Registrasi dengan PIN registrasi + kode referral | §9, §10 |
| 2 | Peringkat otomatis dari jumlah poin (1/3/6) | §2 |
| 3 | Pencatatan tiga jenis poin terpisah: PA, PR, PL | §7 |
| 4 | Bonus sponsor + selisih berjenjang, batas 20%, compression | §3 |
| 5 | Upgrade peringkat lewat PIN upgrade (3 baris) | §11 |
| 6 | Repeat Order lewat PIN RO, poin bebas | §6 |
| 7 | Milestone hadiah PR — kumulatif, poin tidak berkurang | §8 |
| 8 | Halaman anggota: poin, peringkat, ambang berikutnya, riwayat | T4 |
| 9 | Struktur jaringan (3 level tampil) | §12 no.10 |
| 10 | Riwayat bonus + saldo + ajukan penarikan (min Rp 100.000) | §1, A5 |
| 11 | Panel admin: buat PIN, kelola anggota, proses klaim & penarikan | §9, A7 |

### Tidak masuk v1.0

| Fitur | Alasan | Kapan |
|---|---|---|
| Bonus leadership bulanan (Rp 40.000/poin) | Butuh tanggal tutup buku — masih ditunda Owner (A6) | v1.1 setelah A6 diputuskan |
| Bonus tahunan Royal Manager | Sama, butuh periode akumulasi setahun | v1.2 |
| Pembayaran online / payment gateway | Semua lewat PIN dari admin (§9) | Tidak direncanakan |
| Upload bukti transfer | Tidak dipakai (§9) | Tidak direncanakan |
| Toko online / katalog produk | Di luar cakupan keanggotaan & poin | Belum dijadwalkan |
| Aplikasi mobile | Web responsif cukup | Belum dijadwalkan |
| Cetak invoice / PDF | Belum diminta | Belum dijadwalkan |

Kalau daftar "masuk v1.0" ini terlalu banyak untuk rilis pertama, yang bisa dipotong tanpa merusak aturan bisnis: nomor 9 (jaringan) dan 10 (penarikan) — keduanya bisa nyusul tanpa mengubah perhitungan.

---

## 4. Perjalanan pengguna

**J1 — Daftar anggota baru**
Calon anggota hubungi admin → transfer → admin buat PIN registrasi → anggota buka form daftar, isi username/nama/password/telepon/alamat + PIN + kode referral (**opsional**) → sistem cek PIN dan, kalau diisi, kode referral → akun aktif, peringkat sesuai poin, poin PA & PR tercatat, bonus sponsor terposting ke upline.
Kalau kode referral kosong: akun tetap aktif, tanpa upline, bonus sponsor Rp 0. Kalau diisi tapi salah: ditolak.

**J2 — Upgrade peringkat**
Anggota buka menu upgrade → pilih tujuan (Agent/Distributor) → lihat tambahan poin & harga → beli PIN upgrade ke admin → input PIN → peringkat naik, poin PR bertambah, selisih bonus terposting.
Aturan mati: peringkat **tidak** naik karena RO, hanya karena PIN upgrade (A2).

**J3 — Repeat Order**
Anggota pilih jumlah poin RO → beli PIN RO → input PIN → 1 poin pribadi + 1 poin sponsor, tanpa bonus uang, PL tetap jalan.

**J4 — Lihat kemajuan poin**
Anggota buka halaman poin → lihat total PR, ambang yang sudah lewat, ambang berikutnya, hadiah yang jadi haknya.
Yang tampil: poin + nama hadiah. **Nilai rupiah tidak tampil.**

**J5 — Klaim hadiah**
Anggota lihat hadiah yang sudah jadi haknya → ajukan klaim → admin verifikasi → tandai "sudah diserahkan" → poin tetap utuh.
Aturan: kumulatif — lewat 3.500 poin berarti semua ambang dapat (B6).

**J6 — Lihat jaringan**
Anggota buka struktur jaringan → lihat 3 level ke bawah: **nama dan peringkat saja**. Omzet, poin, dan bonus anggota di bawahnya **tidak ditampilkan** (keputusan Owner 30 Okt).

**J7 — Lihat & tarik bonus**
Anggota buka riwayat → lihat bonus sponsor per transaksi (siapa, berapa persen, berapa rupiah) → saldo → ajukan penarikan (min Rp 100.000) → admin transfer manual → tandai lunas.

**J8 — Admin membuat PIN**
Admin terima transfer → buat PIN sesuai jenis & jumlah → PIN masuk ke akun anggota → anggota input sendiri.
PIN tidak ada masa kedaluwarsa.

**J9 — Owner mengubah daftar hadiah**
Owner buka pengaturan hadiah → ubah ambang/nama hadiah → berlaku untuk milestone berikutnya, riwayat lama tidak berubah.

---

## 5. Kriteria penerimaan

Dinyatakan lulus hanya kalau bisa ditunjukkan buktinya. "Sudah dikerjakan" tanpa bukti = belum lulus.

| Kode | Kriteria | Cara uji |
|---|---|---|
| AC-01 | Registrasi dengan PIN valid + kode referral valid menghasilkan akun dengan peringkat sesuai poin | Uji 3 paket: 1, 3, 6 poin |
| AC-01b | Registrasi **tanpa** kode referral tetap berhasil: akun aktif, tanpa upline, bonus sponsor Rp 0, poin PA & PR tercatat | Daftar tanpa kode referral |
| AC-02 | PIN tidak valid / sudah dipakai / kode referral diisi tapi tidak ditemukan → ditolak, tidak ada akun tercipta | Uji tiap kasus, cek tidak ada baris baru |
| AC-03 | Bonus sponsor rantai lengkap R→A→D keluar Rp 90.000 + Rp 18.000 + Rp 12.000 = Rp 120.000 | Kasus baku §3 #1 |
| AC-04 | Peringkat tengah lebih rendah: Agent 18% (Rp 108.000) + Reseller Rp 0 + Distributor Rp 12.000 = Rp 120.000 | Kasus baku §3 #2 |
| AC-05 | Tidak ada peringkat lebih tinggi: Rp 90.000 + Rp 0 = Rp 90.000 | Kasus baku §3 #3 |
| AC-06 | Distributor mengajak Agent: Rp 360.000, total tepat 20% | Kasus baku §3 #4 |
| AC-07 | Total bonus satu transaksi tidak pernah > 20% | Uji rantai panjang 10 level |
| AC-08 | RO tidak mengubah peringkat | RO 10 poin pada Reseller → tetap Reseller |
| AC-09 | PA hanya bertambah dari member baru disponsori langsung; PR bertambah dari join, upgrade, dan RO | Bandingkan kedua angka setelah tiap transaksi |
| AC-10 | Milestone kumulatif: member 3.500 poin punya hak atas 5, 20, 75, 500, dan 3.500 | Cek daftar hak, bukan hanya yang tertinggi |
| AC-11 | Poin tidak berkurang setelah hadiah diklaim | Bandingkan total PR sebelum & sesudah |
| AC-12 | Halaman anggota tidak menampilkan nilai rupiah hadiah | Periksa HTML/teks halaman |
| AC-13 | Anggota tidak bisa melihat data di luar jaringannya, dan halaman jaringan hanya menampilkan nama + peringkat | Coba akses ID anggota lain langsung; periksa tidak ada kolom omzet/poin bawahan |
| AC-14 | Anggota tidak bisa mengubah poin/peringkat sendiri | Coba kirim request ubah data |
| AC-15 | Penarikan di bawah Rp 100.000 ditolak | Uji Rp 99.999 |
| AC-16 | Semua transaksi uang & poin punya jejak audit (siapa, kapan, dari transaksi mana) | Telusuri satu transaksi sampai asal |
| AC-17 | Angka di layar cocok dengan hitungan manual untuk 4 kasus baku | Rekap tertulis berdampingan |
| AC-18 | Hanya 3 akun pengelola bisa masuk panel admin; Admin tidak bisa menambah/menghapus akun Admin lain | Uji login & uji tambah admin dari akun Admin |

---

## 6. Keputusan bisnis yang belum selesai

| # | Hal | Status | Menghambat? |
|---|---|---|---|
| 1 | Tanggal tutup buku | **DITUNDA** Owner (A6) | Hanya bonus leadership & RM — tidak menghambat v1.0 |
| 7 | Data anggota lama | **SELESAI** — mulai dari nol (fresh), tidak ada migrasi | — |
| 8 | Hosting | **BELUM DIPUTUSKAN** — tidak perlu sekarang | Menghambat Stage F saja |
| 2 | Visibilitas jaringan | **SELESAI** — nama & peringkat saja, tanpa omzet/poin | — |
| 3 | Jumlah akun pengelola | **SELESAI** — 2 Admin + 1 Superadmin | Sisa: siapa orangnya |
| 4 | Identitas visual: logo, warna, font | **SELESAI** — diambil dari sahihofficial.id, lihat `phase-6b-design-system.md` | — |
| 5 | Jawaban Rumahweb (PHP, MySQL, SSH, cron, Redis) | **MENUNGGU** | Menghambat Stage F, bukan Stage B–D |
| 6 | Batas minimal penarikan sudah ada (Rp 100.000); berapa lama proses transfer | **PERLU KEPUTUSAN** (kecil) | Teks di halaman penarikan |

---

## 7. Yang saya butuhkan sebelum Stage B

Stage B (design system) minta "review the existing brand references" — dan direktif melarang saya mengarang identitas merek. Jadi:

1. **Logo** — file apa pun (PNG/JPG/PDF), atau bilang kalau belum ada.
2. **Warna** — kalau ada panduan; kalau tidak, saya usulkan 2–3 palet dan Anda pilih.
3. **Font** — kalau ada; kalau tidak, saya pakai font sistem yang aman.
4. **Referensi yang sudah dipakai** — kemasan produk, feed Instagram, brosur, atau screenshot sistem lama. Brosur yang Anda kirim bisa jadi acuan warna.

Kalau semuanya belum ada, Stage B tetap bisa jalan dengan pendekatan: saya usulkan dulu, Anda koreksi — tapi hasilnya akan generik sampai ada bahan asli.

---

## Gerbang Stage A

Stage A selesai kalau Anda setuju dengan:
- daftar peran (§2),
- pembagian masuk/tidak masuk v1.0 (§3),
- 17 kriteria penerimaan (§5).

Nomor 2, 3, dan 4 sudah diputuskan Owner 30 Okt. Sisa: nomor 1 (tutup buku, ditunda) dan nomor 5 (Rumahweb, menunggu jawaban).

Stage B sudah dikerjakan: lihat `phase-6b-design-system.md`.
