# OPEN_QUESTIONS — Hal yang Belum Dijawab

Aturan: agent **tidak boleh menebak** pada hal yang ada di daftar ini. Kalau pekerjaan menyentuh baris di sini, berhenti dan tanya.

## A. Sudah dijawab Owner

| # | Pertanyaan | Jawaban Owner |
|---|---|---|
| A1 | Pembulatan poin | Bulat, tanpa desimal |
| A2 | RO menaikkan tier? | **Tidak.** Tetap di tier sekarang sampai bayar upgrade |
| A3 | PA dipakai untuk apa | Kualifikasi jadi Manager, dari member yang disponsori langsung |
| A4 | Pajak bonus | Tidak ada potongan pajak untuk sekarang |
| A5 | Pencairan | Transfer bank, minimum Rp 100.000 |
| A6 | Tanggal tutup buku | Ngambang dulu — belum wajib sekarang |
| A7 | Peran admin | Admin generate PIN lalu menaruh ke akun member; sisanya peran admin umum |
| A8 | Peringkat di atas Distributor | Hanya Manager. Royal Manager bukan peringkat, hanya sebutan |
| B1 | Nilai poin & tabel hadiah | 1 poin = Rp 50.000. Tabel hadiah dibiarkan — dinamis, Owner bebas ubah. Nilai rupiah tidak ditampilkan ke umum |
| B2 | Tukar poin mengurangi poin? | Tidak — ini milestone, poin tetap |
| B3 | Dua baris upgrade hasil hitungan kita | Dibiarkan seperti sekarang |
| B5 | Syarat Manager 15 atau 20 poin | **20 poin** |
| B6 | Hadiah milestone kumulatif? | **Kumulatif** — semua ambang yang dilewati dapat |

## B. Masih terbuka

| # | Pertanyaan | Kenapa penting | Status |
|---|---|---|---|
| B4 | **Tanggal tutup buku** | Tanpa ini bonus leadership bulanan & tahunan RM tidak bisa dihitung | **DITUNDA** atas keputusan Owner — tidak menghalangi tahap keanggotaan & poin |

## C. Menunggu pihak luar (Rumahweb)

| # | Pertanyaan | Status |
|---|---|---|
| C1 | Versi PHP yang tersedia | BELUM DIJAWAB |
| C2 | MySQL atau MariaDB, versi berapa | BELUM DIJAWAB |
| C3 | Akses SSH & Composer tersedia? | BELUM DIJAWAB |
| C4 | Cron job diizinkan? | BELUM DIJAWAB |
| C5 | Redis tersedia? | BELUM DIJAWAB |

Dampak: `DEPLOYMENT_PLAN.md` tidak bisa dibuat sebelum C1–C5 dijawab.

## D. Teknis, belum diputuskan

| # | Hal | Catatan |
|---|---|---|
| D1 | Login member: username, email, atau nomor HP? | **SELESAI** — username + password. Admin pakai email. Dua pintu dengan guard berbeda |
| D2 | Tampilan jaringan dibatasi 3 level — final? | **SELESAI 2 Nov** — tampilan pohon genealogi **3 tingkat**, perhitungan bonus tetap sampai 50 tingkat (DECISIONS #70) |
| D3 | Backup database Paperclip | Belum ada sama sekali (`/api/health` warning) |
| D4 | Sandbox eksekusi agent | Tidak ada; agent jalan langsung di mesin |
| D5 | Repo proyek | Belum ada kontrol versi |
| D6 | Composer & MySQL lokal | Belum terpasang di mesin pengembangan |
