# BUSINESS_RULES — Sumber Kanonik

**Ini satu-satunya acuan angka.** Dokumen klien `.docx`, PRD, dan chat adalah turunan. Kalau berbeda, yang menang file ini sampai dokumen turunan diperbarui.

Label: VERIFIED = sudah dikonfirmasi Owner / tertulis di dokumen yang disetujui · INFERRED = hitungan turunan, belum dikonfirmasi · CONFLICT = dua sumber berbeda · UNKNOWN = belum ada jawaban.

Dokumen klien terakhir: `Sistem-dan-Fitur-Smart-Store-v8` (dikirim Owner 30 Okt 2026). Hasil pencocokan ada di `v8-crosscheck.md`. Tiga hal yang tadinya menggantung sudah diputuskan Owner 30 Okt (nilai poin, dua baris upgrade, jenis poin dari RO) — lihat §13.

## 1. Nilai dasar

| Hal | Nilai | Label |
|---|---|---|
| Harga poin saat daftar | Rp 600.000 / poin | VERIFIED |
| Harga poin saat RO | Rp 500.000 / poin | VERIFIED |
| Nilai 1 poin leadership | Rp 40.000 | VERIFIED |
| Nilai 1 poin untuk Royal Manager | Rp 2.000 (5% dari Rp 40.000) | VERIFIED |
| Nilai 1 poin | Rp 50.000 | VERIFIED — nilai satuan, **bukan** harga hadiah (§8) |
| Batas total bonus sponsor per transaksi | 20% | VERIFIED |

## 2. Paket, peringkat, dan bonus sponsor

| Poin | Harga | Peringkat | Tarif bonus sponsor |
|---|---|---|---|
| 1 | Rp 600.000 | Reseller | 15% |
| 3 | Rp 1.800.000 | Agent | 18% |
| 6 | Rp 3.600.000 | Distributor | 20% |

Tidak ada paket lain. Yang dipilih saat daftar adalah **jumlah poin**, bukan nama paket. Peringkat otomatis mengikuti jumlah poin.

Poin masuk ke **dua orang** dengan jumlah sama: yang mendaftar dan yang mengajak.

## 3. Cara bonus sponsor dibagi

Aturan:
1. Yang mengajak dapat **tarif peringkatnya sendiri**.
2. Setiap orang di atasnya hanya dapat **selisih persen** peringkatnya dengan persen yang sudah dibayarkan di bawahnya.
3. Kalau peringkat di atasnya **sama atau lebih rendah**, dia dapat Rp 0 dan selisihnya lanjut ke atas (compression).
4. Total tidak pernah melebihi 20%.
5. Rantai ditelusuri maksimal **50 tingkat** (DECISIONS #70, Owner 2 Nov — sebelumnya 20). Kalau di dalam 50 tingkat tidak ada peringkat lebih tinggi, **sisa selisih hangus** — tidak masuk ke siapa pun, termasuk perusahaan.
6. Bonus sponsor dan selisih **cair harian**, dari tanggal transaksi. Saldo langsung bisa ditarik hari itu juga, tidak menunggu akhir bulan.

Contoh baku (wajib dipakai untuk uji):

| Kasus | Rincian | Total |
|---|---|---|
| Rantai lengkap | Reseller 15% (Rp 90.000) + Agent 3% (Rp 18.000) + Distributor 2% (Rp 12.000) | Rp 120.000 |
| Peringkat tengah lebih rendah | Agent 18% (Rp 108.000) + Reseller dilewati (Rp 0) + Distributor 2% (Rp 12.000) | Rp 120.000 |
| Tidak ada peringkat lebih tinggi | Reseller 15% (Rp 90.000) + Reseller berikutnya (Rp 0) → dicari terus sampai 50 tingkat, sisanya hangus | Rp 90.000 |
| Distributor mengajak Agent | Distributor 20% (Rp 360.000) | Rp 360.000 |
| Reseller mengajak Reseller | Keduanya 15% → **tidak ada selisih sama sekali**. Yang dicari ke atas adalah peringkat lebih tinggi (Agent 18% / Distributor 20%) | Rp 90.000 |

## 4. Bonus leadership

| Hal | Ketentuan |
|---|---|
| Nilai | Rp 40.000 / poin |
| Sumber poin | Pendaftaran agen baru, upgrade, dan RO di jaringan bawah |
| Sumber tambahan | **RO pribadi Manager itu sendiri** ikut menghasilkan PL (DECISIONS #69, Owner 2 Nov) |
| Siapa yang dapat | **Hanya Manager.** Bukan Manager → tidak dapat (DECISIONS #69) |
| Sebelum jadi Manager | **Hangus.** Tidak ditabung, tidak dihitung di kemudian hari (DECISIONS #69 — menimpa aturan lama "terkumpul lalu terbuka") |
| Syarat jadi Manager | Sudah Distributor + **20 poin** dari orang yang diajak langsung |
| Kalau tidak pernah jadi Manager | Poin **hangus** |
| Syarat pencairan | Cukup **pernah RO minimal sekali** (tidak harus tiap bulan) — DECISIONS #67 |
| Ritme bayar | **Bulanan** |
| Turun peringkat | **Tidak ada** — sekali Manager, tetap Manager |
| Compression | Berhenti kalau member di bawah sudah jadi Manager |

Contoh: 100 poin leadership = Rp 4.000.000.

## 5. Bonus tahunan Royal Manager

Royal Manager **bukan peringkat baru** — sebutan untuk Manager yang punya Manager langsung di bawahnya.

| Hal | Ketentuan |
|---|---|
| Nilai | Rp 2.000 / poin |
| Dihitung dari | Poin leadership Manager langsung di bawahnya |
| Ritme bayar | Setahun sekali, dari akumulasi |
| Compression | Berhenti antar Royal Manager |
| Poin RO | Tetap masuk ke sponsor |

Contoh: Manager di bawah dapat 100 poin leadership setahun → RM dapat Rp 200.000.

## 6. Repeat Order (RO)

| Hal | Ketentuan |
|---|---|
| Harga | Rp 500.000 / pin (bisa diubah Owner, DECISIONS #72) |
| Poin didapat pembeli | **1 PR + 1 PA** per pin (DECISIONS #65, Owner 2 Nov) |
| Poin didapat sponsor | 1 PR per pin |
| Bonus sponsor (uang) | Tidak ada |
| Bonus generasi (uang) | Tidak ada |
| Poin leadership | Tetap jalan |
| Jumlah pin | Bebas, dipilih sendiri |
| Peringkat | **Bisa naik otomatis** kalau total PR cukup (DECISIONS #68) |
| Cara bayar | Beli PIN RO dari admin |

**Konsekuensi yang disadari dan disetujui Owner (pilihan "c", 2 Nov):** karena RO memberi PA, **Manager bisa dicapai tanpa mensponsori siapa pun**. Contoh: RO 20 pin × Rp 500.000 = Rp 10.000.000 → 20 PR (jadi Distributor) + 20 PA (jadi Manager).

## 7. Tiga jenis poin — tidak bisa saling tukar

| Jenis | Dapat dari | Gunanya |
|---|---|---|
| PA — Poin Affiliate | Member baru yang disponsori langsung, **dan RO sendiri** (DECISIONS #65) | **Kualifikasi jadi Manager** (kumpul 20 poin). Tidak ada guna lain |
| PR — Poin Reward | Semua sumber selain kualifikasi: join pribadi, upgrade akun, **RO pribadi**, dan member yang disponsori langsung (join, upgrade, RO-nya) | Hadiah milestone (§8) **dan penentu peringkat** (DECISIONS #68) |
| PL — Poin Leadership | Pendaftaran agen baru, upgrade, dan RO dari jaringan bawah, **plus RO pribadi Manager itu sendiri**. **Hanya untuk Manager** | Dibayar Rp 40.000/poin |

PA dan PR dihitung **terpisah**, bukan dibagi. Contoh: sponsori 1 Agent (3 poin) → dapat 3 PA **dan** 3 PR.

**Peringkat ditentukan total PR** (DECISIONS #68): 1 PR = Reseller, 3 PR = Agent, 6 PR = Distributor. PR dari anggota yang disponsori ikut dihitung. Manager tidak lewat PR — syaratnya Distributor + 20 PA.

**PL hanya dicatat untuk Manager** (DECISIONS #69). Sebelum jadi Manager, poin leadership **hangus** — tidak ditabung dan tidak dihitung di kemudian hari. Ini menimpa aturan lama "terkumpul lalu terbuka".

## 8. Hadiah poin (PR) — milestone, bukan tukar

Poin **tidak berkurang** saat hadiah diambil. Ini tangga pencapaian: begitu total poin menembus ambang, hadiahnya jadi hak member, dan poinnya tetap utuh untuk ambang berikutnya.

| Ambang poin | Hadiah | Nilai internal (tidak ditampilkan ke member) |
|---|---|---|
| 5 | Produk | Rp 250.000 |
| 20 | **Voucher** | Rp 500.000 |
| 75 | Handphone | Rp 2.000.000 |
| 500 | Motor / senilai | Rp 20.000.000 |
| 3.500 | Mobil | Rp 150.000.000 |

- Yang tampil ke member **hanya jumlah poin dan nama hadiah**. Nilai rupiah disimpan internal.
- Daftar hadiah dan nilainya **dinamis** — Owner bebas mengubah kapan saja, tanpa mengubah aturan poin.
- 1 poin bernilai **Rp 50.000** sebagai satuan. Harga hadiah **tidak** dihitung dari perkalian itu; dua hal terpisah.
- **Kumulatif**: setiap ambang yang dilewati menghasilkan hadiah. Member yang mencapai 3.500 poin sudah melewati 5, 20, 75, dan 500 — semuanya jadi hak dia.
- **Berulang**: setelah 3.500 poin, hitungan mulai dari nol lagi dan seluruh tabel hadiah jadi hak member sekali lagi. Tiap putaran penuh 3.500 poin membuka lima hadiah baru. Dikonfirmasi Owner 2 Nov (DECISIONS #64).
- **Hadiah di atas 3.500** (kalau Owner menambahkannya) dinilai langsung dari total PR, bukan per putaran: ambang 10.000 dapat sekali saat 10.000, dua kali saat 20.000 (DECISIONS #71). Sebelumnya hadiah seperti ini diblokir total.
- Klaim lewat admin: member ke kantor, tunjukkan bukti, terima **produk** (bukan transfer).

## 9. PIN — dua jenis

Tidak ada pembayaran di dalam sistem. Semua lewat admin.

| Jenis PIN | Harga | Dipakai untuk |
|---|---|---|
| PIN registrasi / upgrade | Rp 600.000 / pin | Daftar anggota baru, dan naik peringkat |
| PIN RO | Rp 500.000 / pin | Repeat order |

**DECISIONS #73 (Owner 2 Nov):** jenis PIN disederhanakan dari tiga menjadi **dua**. Harga dihitung **per pin**, dan **Owner bisa mengubahnya** lewat `/admin/pengaturan` (DECISIONS #72).

Contoh: daftar Agent (3 pin) = 3 × Rp 600.000 = Rp 1.800.000. Upgrade Reseller → Agent (2 pin) = 2 × Rp 600.000 = Rp 1.200.000.

Alur: anggota hubungi admin via WA → transfer ke admin → admin buat PIN → PIN masuk ke akun → anggota input PIN → transaksi terposting.

**Nominal dan jumlah pin tiap PIN diatur admin saat membuat PIN** — bukan angka yang ditanam di sistem. PIN upgrade wajib menyebut peringkat tujuan; tanpa itu ditolak sistem.

- PIN **tidak ada masa kedaluwarsa**.
- Tidak ada upload bukti transfer.
- Daftar butuh **PIN registrasi**. Kode referral (username sponsor) **opsional** — keputusan Owner 30 Okt.
- Kalau kode referral **diisi tapi tidak ditemukan** → pendaftaran ditolak dengan pesan jelas. Tidak boleh diam-diam masuk jaringan orang lain (INFERRED — ini tafsir aman dari peringatan asli).
- Kalau kode referral **kosong** → anggota terdaftar tanpa upline: tidak muncul di jaringan siapa pun, dan transaksi itu **tidak membagikan bonus sponsor** (total Rp 0). Poin PA & PR anggota tetap tercatat.

**Catatan pelaksanaan:** penggabungan jenis PIN di kode **belum dikerjakan** — saat ini masih tiga jenis (`registration`, `upgrade`, `ro`). Aturannya sudah dicatat di sini sebagai acuan.

## 10. Form registrasi

| Kolom | Dipakai |
|---|---|
| Username | Ya |
| Nama | Ya |
| Password | Ya |
| Kode referral (username sponsor) | **Opsional** (keputusan Owner 30 Okt) |
| Nomor telepon | Ya |
| Alamat | Ya |
| Nomor KTP | **Tidak** |

## 11. Upgrade peringkat

Ada **dua jalan** naik peringkat, dan keduanya berlaku bersamaan (DECISIONS #68, Owner 2 Nov).

**Jalan 1 — otomatis dari total PR.** Ambang: 1 PR = Reseller, 3 PR = Agent, 6 PR = Distributor. PR dari anggota yang disponsori **ikut dihitung**. Begitu total PR mencapai ambang, status langsung berubah tanpa bayar apa pun.

**Jalan 2 — PIN upgrade.** Bayar per pin, naik tanpa menunggu PR cukup.

| Dari | Ke | Tambah pin | Bayar (per pin) | Label |
|---|---|---|---|---|
| Reseller (1) | Agent (3) | 2 | 2 × Rp 600.000 = Rp 1.200.000 | VERIFIED |
| Reseller (1) | Distributor (6) | 5 | 5 × Rp 600.000 = Rp 3.000.000 | **VERIFIED** — Owner 30 Okt |
| Agent (3) | Distributor (6) | 3 | 3 × Rp 600.000 = Rp 1.800.000 | **VERIFIED** — Owner 30 Okt |
| Distributor (6) | Manager | — | Tidak dijual | Otomatis setelah 20 PA |

Peringkat tujuan melekat pada PIN, bukan pada jenis transaksi: admin membuat PIN dengan nominal, jumlah pin, dan peringkat tujuan. Sistem tidak menebak peringkat dari besaran bayar. Upgrade tidak pernah menurunkan peringkat — member Manager yang memakai PIN ke Distributor tetap Manager.

**Konsekuensi yang disadari dan disetujui Owner (pilihan "c", 2 Nov):** karena harga PIN RO (Rp 500.000/pin) lebih murah daripada PIN registrasi/upgrade (Rp 600.000/pin), dan RO juga menghasilkan PR, **PIN upgrade tidak akan pernah dipakai orang**. Dibiarkan sebagai jalan naik tanpa menunggu poin.

Dua baris upgrade tadinya INFERRED karena angkanya hasil hitungan Rp 600.000/pin. Owner menyebut angkanya sendiri pada 30 Okt ("3.000.000" dan "1,8 juta"), jadi sekarang VERIFIED. Yang tetap tidak boleh ditebak: peringkat tujuan sebuah PIN upgrade — itu pilihan admin saat membuat PIN, bukan turunan nominal.

## 12. Konflik dan lubang yang harus ditutup

| # | Hal | Kondisi | Dampak kalau dibiarkan |
|---|---|---|---|
| 1 | Nilai tukar poin | **SELESAI** — 1 poin = Rp 50.000 (satuan). Tabel hadiah dinamis, bukan konversi rupiah | — |
| 2 | PA untuk apa | **SELESAI** — kualifikasi Manager, dari member yang disponsori langsung | — |
| 3 | Pembulatan poin | **SELESAI** — bulat | — |
| 4 | Pajak bonus | **SELESAI** — tidak ada potongan | — |
| 5 | Minimum & metode pencairan | **SELESAI** — transfer bank, minimum Rp 100.000 | — |
| 6 | Tanggal tutup buku | **DITUNDA** Owner | Bonus leadership bulanan & tahunan RM belum bisa dihitung |
| 7 | Peran admin | **SELESAI** — admin generate PIN + kelola member | — |
| 8 | RO menambah poin peringkat? | **DIUBAH 2 Nov** — dulu "tidak". Sekarang **ya, lewat total PR**: RO memberi PR, dan PR menentukan peringkat (DECISIONS #68). Tier tetap tidak turun | Anggota bisa naik peringkat dari RO |
| 9 | Peringkat di atas Distributor | **SELESAI** — Manager; Royal Manager cuma sebutan | — |
| 10 | Visibilitas jaringan | SEBAGIAN: tampilan 3 level, hitungan tanpa batas | Perlu keputusan final |
| 11 | Syarat Manager: 15 atau 20 poin? | **SELESAI** — Owner memutuskan **20 poin**. Catatan: rekaman lisan Pak Agung menyebut 15, angka itu tidak dipakai | — |
| 12 | Hadiah milestone kumulatif? | **SELESAI** — **kumulatif**. Setiap ambang yang dilewati menghasilkan hadiah; sampai 3.500 poin berarti semua ambang terlewati dan semua hadiahnya jadi hak member | Hadiah dihitung per ambang, bukan hanya ambang tertinggi |

## 13. Jawaban Owner — 30 Oktober 2026

| Kode | Pertanyaan | Jawaban Owner | Akibatnya |
|---|---|---|---|
| A1 | Pembulatan poin | **Bulat** (tanpa desimal) | Kolom poin pakai bilangan bulat |
| A4 | Pajak bonus | **Tidak ada potongan pajak** untuk sekarang | Tidak ada kolom pajak di perhitungan |
| A5 | Pencairan | **Boleh** — transfer bank, minimum Rp 100.000 | Modul penarikan bisa dibuat |
| A6 | Tanggal tutup buku | **Dibiarkan ngambang** untuk sekarang | Modul bonus bulanan ditunda; belum ada yang menagih periode |
| A7 | Peran admin | Admin **generate PIN** dan menaruhnya ke akun member; sisanya peran admin umum | Admin = pembuat PIN + kelola member |
| A8 | Peringkat di atas Distributor | Hanya **Manager**. Royal Manager **bukan peringkat**, cuma sebutan | Struktur peringkat: Reseller → Agent → Distributor → Manager |
| A2 | RO menambah syarat peringkat? | **Tidak.** "Tetap di status/tier-nya sampai dia bayar upgrade." Poin RO dan upgrade masuk **PR** | RO tidak bisa dipakai naik peringkat tanpa bayar |
| A3 | PA untuk apa | **Kualifikasi Manager**, dari member yang disponsori langsung | PA tidak bisa ditukar hadiah |
| B1 | Nilai poin & tabel hadiah | 1 poin = **Rp 50.000**. Tabel hadiah **dibiarkan** apa adanya — sifatnya dinamis, Owner bebas mengubah reward. Nilai rupiah hadiah **jangan ditampilkan** ke umum, cukup poin | Bukan konflik lagi: hadiah = daftar dinamis, bukan hasil konversi |
| B2 | Tukar poin mengurangi poin? | **Tidak.** Ini **milestone** — sudah 5 poin dapat hadiah, poinnya tetap | Poin = penghitung yang naik terus, bukan saldo yang dibelanjakan |
| B3 | Dua baris upgrade (Rp 3.000.000 & Rp 1.800.000) | **Dibiarkan** seperti sekarang | Angka dipakai, label INFERRED tetap |
| B5 | Syarat Manager 15 atau 20 poin | **20 poin** | Selesai, tidak ada konflik lagi |
| B6 | Hadiah milestone kumulatif? | **Kumulatif** — semua ambang yang dilewati dapat | Tabel hadiah dihitung per ambang |

Catatan A2: sudah final — Owner menyatakannya dua kali dengan kalimat sama ("tetap di tier-nya sampai bayar upgrade").

Catatan A6: tanpa tanggal tutup buku, bonus leadership bulanan dan bonus tahunan RM **tidak bisa dihitung**. Untuk tahap keanggotaan & poin ini belum menghalangi.

---

## 14. Perubahan Owner — 2 November 2026

Ringkasan semua yang berubah pada tanggal ini, beserta nomor keputusannya. Bagian ini **menimpa** aturan lama yang bertentangan di atas.

| Hal | Sebelum | Sesudah | Keputusan |
|---|---|---|---|
| Poin RO | PR saja | **PR + PA** | #65 |
| Naik peringkat | Hanya lewat PIN upgrade | **Otomatis dari total PR** (1/3/6), PIN upgrade tetap ada | #68 |
| PR yang dihitung untuk peringkat | — | **Termasuk PR dari anggota yang disponsori** | #68 |
| PL untuk non-Manager | Dicatat, "terbuka" setelah jadi Manager | **Hangus.** Hanya Manager yang dicatat | #69 |
| PL dari RO pribadi | Tidak ada | **Manager yang RO sendiri ikut dapat PL** | #69 |
| Rantai bonus sponsor | 20 tingkat | **50 tingkat** | #70 |
| Sisa selisih tidak terbayar | Berhenti di tarif tertinggi | **Hangus**, tidak ke siapa pun | #70 |
| Hadiah > 3.500 | Diblokir total | **Dinilai dari total PR** | #71 |
| Hadiah ambang 20 | Produk | **Voucher** | #66 |
| Jenis PIN | 3 (registrasi, upgrade, RO) | **2** (registrasi/upgrade, RO) | #73 |
| Harga PIN | Nominal bebas per PIN | **Per pin**, diatur Owner lewat panel | #72, #73 |
| Halaman anggota | Poin, riwayat, hadiah | **+ asal-usul poin, cek bonus, pohon genealogi, status downline** | #72 |
| Admin | Buat/batalkan PIN | **+ edit profil anggota** | #72 |
| Owner | Ubah hadiah & aturan | **+ ubah harga per pin** | #72 |

**Dua konsekuensi yang disadari dan disetujui Owner (pilihan "c"):**
1. **Manager bisa dicapai lewat RO saja.** RO 20 pin × Rp 500.000 = Rp 10.000.000 → 20 PR + 20 PA → langsung Manager tanpa mensponsori siapa pun.
2. **PIN upgrade tidak akan pernah dipakai.** Harga PIN RO (Rp 500.000/pin) lebih murah daripada PIN registrasi/upgrade (Rp 600.000/pin), dan RO juga menghasilkan PR. Dibiarkan sebagai jalan naik tanpa menunggu poin.

**Status pelaksanaan di kode (2 Nov):** semua di atas **sudah terpasang dan diuji** (177 uji lulus), **kecuali** penggabungan jenis PIN menjadi dua — kode masih memakai tiga jenis (`registration`, `upgrade`, `ro`). Harga per pin sudah bisa diubah Owner lewat `/admin/pengaturan`.
