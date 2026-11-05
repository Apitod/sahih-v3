# DECISIONS — Catatan Keputusan

Keputusan yang sudah diambil, beserta alasannya. Kalau ada yang berubah, **tambah baris baru**, jangan hapus yang lama — supaya jejaknya kelihatan.

| # | Keputusan | Alasan | Diputuskan oleh | Status |
|---|---|---|---|---|
| 1 | Cakupan tahap awal: **keanggotaan & poin saja** | Sistem lama sudah jalan; yang paling menyakitkan adalah hitung poin & bonus | Owner | FINAL |
| 2 | Techstack **Laravel + Blade + Livewire + MySQL** | Owner yang menentukan | Owner | FINAL |
| 3 | Hosting **Rumahweb**, sistem baru di **subdomain** | Sistem lama tetap jalan sampai baru terbukti | Owner | FINAL |
| 4 | Sistem lama **tidak dihapus** sebelum sistem baru terbukti | Risiko kehilangan data & penjualan | Owner | FINAL |
| 5 | Pembayaran lewat **PIN dari admin**, tanpa payment gateway | Semua transaksi sudah lewat WA + transfer manual | Owner | FINAL |
| 6 | **Tidak ada** bonus generasi (uang) | Menyederhanakan hitungan | Owner | FINAL |
| 7 | **Tidak ada** turun peringkat | Menjaga keadilan member lama | Owner | FINAL |
| 8 | PIN **tanpa masa kedaluwarsa** | Member sudah bayar | Owner | FINAL |
| 9 | Syarat pencairan leadership: **cukup pernah RO sekali** | Tidak memberatkan | Owner | FINAL |
| 10 | Bonus sponsor **cair harian** dari tanggal transaksi | Uang cepat kembali ke member | Owner | FINAL |
| 11 | Paperclip dipakai sebagai **control plane**, **tidak** dipasang di shared hosting | Shared hosting tidak bisa jalankan Node/PGlite | Owner + analisis | FINAL |
| 12 | Model per peran: **PM & agent lain → `free`**, **eksekutor kode → `code`** | Dua model tercepat & terbukti end-to-end | Owner | FINAL |
| 13 | Model `auto` **ditinggalkan** | `DEFAULT_MODEL="auto"` tidak valid di gateway 9-router → run gagal | Teknis (VERIFIED) | FINAL |
| 14 | **39 model mati dihapus** dari config Hermes | Sudah diuji: error permanen (404 / tidak didukung / upstream menolak) | Owner | FINAL |
| 15 | **9 model grup B dipertahankan** | Hanya kena limit harian, bisa pulih | Teknis | FINAL |
| 16 | Semua agent pakai adapter **`hermes_local`** | `hermes_gateway` gagal uji lingkungan secara permanen | Teknis (VERIFIED) | FINAL |
| 17 | **QA agent dibuat** (model `free`) | Implementer tidak boleh menilai pekerjaannya sendiri | Owner | FINAL |
| 18 | Approval Owner pakai `reviewPolicy: human_only` | Hanya Owner yang bisa meloloskan | Desain Phase 4 | MENUNGGU KONFIRMASI |
| 19 | Review kode pakai `reviewPolicy: not_creator` | Reviewer bukan pembuat | Desain Phase 4 | MENUNGGU KONFIRMASI |
| 20 | Status papan + label `state:*` untuk 12 state workflow | Paperclip hanya punya 7 status | Desain Phase 4 | MENUNGGU KONFIRMASI |
| 21 | Tugas hanya `done` kalau ada acceptance criteria + bukti | Direktif Phase 4 | Owner (direktif) | FINAL |
| 22 | `BUSINESS_RULES.md` jadi sumber kanonik angka | Angka tersebar di banyak tempat | Desain Phase 5 | MENUNGGU KONFIRMASI |
| 23 | Poin dihitung **bulat**, tanpa desimal | Menghindari selisih pembulatan | Owner | FINAL |
| 24 | **Tidak ada** potongan pajak bonus (untuk sekarang) | Keputusan Owner | Owner | FINAL |
| 25 | Pencairan **transfer bank**, minimum **Rp 100.000** | Usulan disetujui Owner | Owner | FINAL |
| 26 | Tanggal tutup buku **dibiarkan ngambang** | Belum perlu sekarang | Owner | DITUNDA |
| 27 | Admin **generate PIN** lalu menaruhnya ke akun member; sisanya peran admin umum | Alur PIN manual | Owner | FINAL |
| 28 | Peringkat berhenti di **Manager**; Royal Manager **bukan peringkat**, hanya sebutan | Sesuai brosur | Owner | FINAL |
| 29 | **PA** = kualifikasi Manager (20 poin); **PR** = tukar hadiah; poin RO & upgrade masuk **PR** | Definisi Pak Agung 30 Okt 05:03 | Owner | FINAL |
| 30 | **RO tidak menambah syarat peringkat** — tier tetap sampai member bayar upgrade | Owner menyatakannya dua kali dengan kalimat sama | Owner | FINAL |
| 31 | Nilai **1 poin = Rp 50.000** | Angka resmi di brosur & penjelasan Pak Agung | Owner | FINAL |
| 32 | Tabel hadiah **dinamis** — Owner bebas mengubah reward kapan saja; tidak dihitung dari Rp 50.000/poin | Hadiah itu daftar produk, bukan konversi uang | Owner | FINAL |
| 33 | **Nilai rupiah hadiah tidak ditampilkan** ke member; yang tampil hanya poin & nama hadiah | Menghindari member menghitung sendiri | Owner | FINAL |
| 34 | **Poin tidak berkurang** saat hadiah diambil — sistem **milestone**, bukan penukaran | Ambang 5/20/75/500/3.500 = pencapaian | Owner | FINAL |
| 35 | Dua baris upgrade hasil hitungan kita (Rp 3.000.000 & Rp 1.800.000) **dipakai apa adanya** | Owner: "iya gitu aja" | Owner | FINAL (label INFERRED tetap) |
| 36 | Poin **RO masuk PR**, bukan PA | Transkrip Pak Agung: "PR bisa dari RO pribadi, bisa dari sponsor pribadi, bisa dari new agent pribadi, termasuk dari upgrade" | Sumber bisnis | FINAL |
| 37 | Syarat Manager = **20 poin** dari member yang disponsori langsung | Owner: "iya 20". Angka 15 di rekaman lisan tidak dipakai | Owner | FINAL |
| 38 | Hadiah milestone **kumulatif** — semua ambang yang dilewati dapat hadiahnya | Owner: "sudah dapat semua lah kan dilewati" | Owner | FINAL |
| 39 | Halaman jaringan hanya menampilkan **nama + peringkat**, tanpa omzet/poin bawahan | Owner 30 Okt | Owner | FINAL |
| 40 | **3 akun pengelola**: 2 Admin + 1 Superadmin | Owner 30 Okt | Owner | FINAL |
| 41 | Identitas visual mengikuti **sahihofficial.id** — palet, font, logo diambil dari sana | Owner 30 Okt: "referensi ambil dari sini... agar tidak ai slop" | Owner | FINAL |
| 42 | Tombol utama **amber `#F0A04B` dengan teks arang** (bukan teks putih) | Kontras terukur 2,14:1 kalau putih — gagal | Owner + pengukuran | FINAL |
| 43 | Ikon **Lucide** self-host; font **self-host** (Outfit + Playfair Display) | Owner: "sesuaikan" | Saya, atas delegasi | FINAL |
| 44 | Logo hanya PNG 500×500 — dipakai apa adanya, **tidak digambar ulang** | Owner: "ga ada" (vektor) | Owner | FINAL |
| 45 | **Landing page ditunda**; v1.0 aplikasi anggota + panel admin saja | Owner: "nanti aja jangan dulu" | Owner | FINAL |
| 46 | **Kode referral opsional** saat daftar. Kalau diisi tapi tidak ditemukan → ditolak; kalau kosong → tanpa upline, bonus sponsor Rp 0 | Owner: "untuk kode referal itu jadikan opsional aja" | Owner | FINAL |
| 47 | **Mulai dari nol** — tidak ada migrasi data dari sistem lama | Owner: "ini aku mau buat fresh new" | Owner | FINAL |
| 48 | Hosting **belum diputuskan**, tidak menghambat Stage C/D | Owner: "masih blum aku putuskan mau hosting dimana blum perlu sekarang kan?" | Owner | FINAL |
| 49 | **Manager otomatis** saat PA ≥ 20 dan sudah Distributor | Owner: "iya" | Owner | FINAL |
| 50 | Rantai sponsor ditelusuri di PHP (loop), bukan recursive CTE | Supaya tidak bergantung versi MySQL hosting yang belum diputuskan | Saya, atas delegasi | FINAL |
| 51 | Alat pengembangan terpasang: Composer 2.11.0, MariaDB 10.11 di **port 3307** (3306 dipakai proyek lain, tidak disentuh) | Owner: "boleh" | Owner | FINAL |

## Keputusan yang masih terbuka

Lihat `OPEN_QUESTIONS.md`. Yang tersisa: tanggal tutup buku (ditunda Owner) dan 5 pertanyaan Rumahweb.

## #51 — Hadiah milestone terulang tiap 3.500 poin
**Keputusan (Owner, 30 Okt):** "terulang". Setelah 3.500 poin, hitungan mulai dari nol dan seluruh tabel hadiah jadi hak member lagi. Tiap putaran penuh membuka lima hadiah baru; putaran berjalan sebagian hanya membuka ambang yang sudah terlewati.
**Konsekuensi teknis:** `milestone_claims` menyimpan `cycle`; panjang putaran = ambang tertinggi yang aktif (sekarang 3.500), jadi ikut berubah kalau Owner mengubah tabel hadiah. Poin tetap tidak berkurang.
**Status:** terpasang & teruji.

## #52 — PIN upgrade membawa peringkat tujuan; nominal diatur admin
**Keputusan (Owner, 30 Okt):** nominal PIN diatur admin, bukan sistem. Untuk upgrade: Reseller→Distributor Rp 3.000.000, Agent→Distributor Rp 1.800.000.
**Konsekuensi teknis:** kolom `pins.target_rank` (nullable). PIN upgrade tanpa `target_rank` ditolak sebelum transaksi terbentuk. Nominal & poin tidak ditanam di kode — hanya jadi contoh di dokumen. Upgrade tidak menurunkan peringkat.
**Status:** terpasang & teruji.

## #53 — Tarif bonus sponsor Manager = 20%
**Keputusan (Owner, 30 Okt):** "iya" atas usul 20%.
**Alasan:** Manager adalah Distributor yang sudah memenuhi syarat tambahan (20 PA), bukan peringkat di atas Distributor dalam hal tarif. 20% juga sama dengan plafon per transaksi (§1, §3 butir 4).
**Status:** sudah jadi perilaku kode (`config/sahih.php:11`). Sebelum ini angka tersebut tidak punya dasar dokumen — sekarang punya.

## #54 — PIN upgrade membayar bonus sponsor
**Keputusan (Owner, 30 Okt):** "iya".
**Dasar:** §6 hanya melarang bonus sponsor untuk RO; §11 tidak melarang untuk upgrade. Perhitungannya sama seperti join: 15/18/20% mengikuti peringkat upline, plafon 20% per transaksi.
**Status:** sudah jadi perilaku kode.

## #55 — PIN upgrade ke peringkat sama atau lebih rendah ditolak
**Keputusan (Owner, 30 Okt):** "iya" (tolak).
**Alasan:** member tidak butuh PIN ke peringkat yang sudah dilewati; kalau transaksi tetap diposting, dia membayar tanpa mendapat manfaat.
**Konsekuensi:** PIN tidak hangus (`status` tetap `issued`), anggota melihat pesan galat. §11 baris 163 ("tetap Manager") ditafsirkan sebagai jaminan peringkat tidak pernah turun, bukan perintah agar transaksi tetap jalan.
**Status:** sudah jadi perilaku kode; dikunci uji `PinEdgeCaseTest.php` kasus 5.

## #56 — Harga RO Rp 500.000/poin MENGIKAT
**Keputusan (Owner, 30 Okt):** "iya mengikat".
**Isi:** harga PIN RO tidak boleh ditentukan bebas. Nominal PIN RO wajib **Rp 500.000 × jumlah poin** (§6). PIN RO dengan nominal lain harus ditolak saat dibuat, bukan diterima lalu dipersoalkan belakangan.
**Konsekuensi teknis:** validasi di panel admin saat PIN dibuat (`Admin/PinController::store`) — `type=ro` wajib `price === 500000 × points`. Angka 500.000 disimpan di config, tidak ditanam di dua tempat.
**Catatan data lama:** PIN RO yang sudah terlanjur dibuat dengan harga lain tetap bisa ditukar (tidak dibatalkan). Yang berubah hanya pembuatan PIN baru.
**Status:** dicatat 30 Okt, penegakan belum terpasang — antre di SAH-16.

## #57 — Poin leadership dicatat sejak sekarang, pencairan tetap ditunda
**Keputusan (Owner, 30 Okt):** "sesuai hasil wawancara, brainstorming, diskusi, dan dokumen sebelumnya".
**Isi:** ledger `pl` mulai diisi untuk pertumbuhan jaringan sesuai §4 dan §7. Pembayaran Rp 40.000/poin tetap menunggu tanggal tutup buku (A6).
**Alasan mencatat sekarang:** poin dihitung dari ledger; transaksi yang lewat sebelum modulnya ada tidak bisa diisi mundur.
**Batas:** hanya mencatat. Tidak ada pencairan, tidak ada tabel baru.

## #58 — PIN upgrade ke Manager tidak dijual
**Keputusan (Owner, 30 Okt):** "ga usah karena status manager itu otomatis kalau udah sesuai syaratnya".
**Isi:** Manager bukan peringkat yang dibeli. Tidak ada PIN upgrade menuju Manager. Manager muncul sendiri begitu anggota Distributor mencapai 20 poin PA (§4, #49).
**Konsekuensi teknis:** peringkat tujuan Manager ditolak saat PIN dibuat (`Admin/PinController::store`) dan ditolak lagi di `TransactionPoster::post()` kalau PIN lama terlanjur ada. Pilihan Manager dihapus dari formulir.
**Status:** terpasang, diuji (`AdminPinTest`, `PostingIntegrityTest`).

## #59 — Harga PIN upgrade = selisih harga paket
**Keputusan (Owner, 30 Okt):** "berdasarkan dari statusnya sekarang ditambah selisih harga upgrade yang dituju, semisal dari reseller mau jadi agent, bayar 1.200.000".
**Isi:** harga PIN upgrade = **harga paket tujuan − harga paket sekarang**, dengan harga paket = Rp 600.000 × poin paket (§2).
**Turunan yang cocok dengan dokumen:** Reseller→Agent Rp 1.200.000 · Agent→Distributor Rp 1.800.000 · Reseller→Distributor Rp 3.000.000 (sama dengan angka §11 yang sudah tercatat).
**Konsekuensi teknis:** PIN upgrade wajib menyebut pemegangnya saat dibuat, karena harga bergantung pada peringkat pemegang. Harga diperiksa dua kali: saat PIN dibuat dan saat ditukar (kalau peringkat pemegang berubah di antaranya, PIN ditolak dan harus diterbitkan ulang). Angka di `config/sahih.php` (`registration_price_per_point`, `points_by_rank`), perhitungan di `App\Services\PackagePrice`.
**Status:** terpasang, diuji (`AdminPinTest`, `RoPriceTest`, `AuditFixTest`).

## #60 — Rantai bonus sponsor tetap dibatasi 20 tingkat
**Keputusan (Owner, 30 Okt):** "yaudah oke gitu aja".
**Isi:** batas penelusuran 20 tingkat di `Member::uplineRanks()` dipertahankan. Bukan aturan bisnis, hanya pengaman teknis; efeknya nol karena total bonus per transaksi sudah dibatasi 20% (`sahih.sponsor_cap_bp`).
**Status:** tidak ada perubahan kode.

## #61 — Putaran hadiah dikunci di 3.500 poin
**Keputusan (Owner, 30 Okt):** "iya di kunci meskipun itu juga masih kurang kemungkinan orang sampai di tahap poin setinggi itu".
**Isi:** panjang satu putaran hadiah **selalu 3.500 poin**, tidak mengikuti ambang tertinggi yang sedang aktif. Menonaktifkan hadiah 3.500 di panel admin tidak mengubah hitungan hadiah anggota (dulu putaran ikut turun ke 500 dan klaim bisa membengkak 4×).
**Konsekuensi teknis:** angka di `config/sahih.php` (`milestone_cycle_points`), dipakai `MilestoneEvaluator::evaluate()`.
**Status:** terpasang, diuji (`AuditFixTest`).

## #62 — Poin leadership berhenti di anggota yang sudah Manager (kompresi)
**Keputusan (Owner, 30 Okt):** "Selama Member yang itu belum manager kita masih dapat hak dari struktur jaringan nya".
**Isi:** hak leadership mengalir ke atas selama anggota di bawahnya **belum Manager**. Begitu ada anggota di bawah yang sudah Manager, hak dari struktur anggota itu berhenti (sama dengan baris "Compression — Berhenti kalau member di bawah sudah jadi Manager" di §4).
**Turunan:** untuk transaksi anggota X, poin leadership diberikan ke upline level 1, 2, 3, … **sampai dan termasuk Manager pertama** yang ditemui, lalu berhenti.
**Nilai (Owner, 30 Okt, jawaban "a"):** **sebesar poin transaksinya**. Downline RO 3 poin → tiap upline dalam rantai dapat 3 poin leadership (Rp 120.000 per poin Rp 40.000). Join paket 3 poin → 3 poin leadership juga. Bukan 1 poin per kejadian.
**Catatan:** pembeli sendiri tidak dapat poin leadership dari transaksinya; RO tetap memberi poin leadership walau RO tidak membayar bonus sponsor (§6).
**Status:** terpasang di `TransactionPoster::awardLeadership()`, diuji (`LeadershipPointTest`, 4 uji).

---

## #63 — Batas percobaan masuk & tukar PIN
**Keputusan (Owner, 2 Nov):** "iya" (menyetujui angka yang diusulkan).
**Isi:** percobaan masuk **30 per menit** per IP, tukar PIN **60 per menit** per IP. Angka ini kompromi: batas lama 10/menit per IP memblokir anggota yang berada di satu kantor dengan IP sama.
**Catatan:** PIN tetap 12 karakter acak, jadi menebak kode tidak masuk akal. Pembatasan per akun juga tetap ada di controller login.
**Status:** terpasang di `routes/web.php`, diuji (`PerbaikanAuditTest`).

## #64 — Putaran hadiah 3.500 poin (dikonfirmasi ulang)
**Keputusan (Owner, 2 Nov):** "putaran hadiah 3.500".
**Isi:** menegaskan #51 dan #61. Setelah 3.500 poin, hitungan mulai dari nol lagi dan seluruh tangga hadiah terbuka sekali lagi.
**Catatan:** transkrip wawancara Pak Agung sempat menyebut "3.400 poin" — angka itu **tidak dipakai**.
**Status:** terpasang (`MilestoneEvaluator`), diuji (`AturanV3Test`).

## #65 — Poin RO masuk PR **dan** PA
**Keputusan (Owner, 2 Nov):** "poin ro — 1. iya bisa".
**Isi:** pembeli RO menerima **PR + PA** sekaligus, sebesar pin yang dibeli. Sponsor langsung hanya menerima **PR** dari RO.
**Alasan:** sebelumnya #36 hanya menyebut PR. Owner memutuskan PA juga diberikan.
**Konsekuensi yang disadari dan disetujui Owner (pilihan "c"):** karena RO memberi PA, **Manager bisa dicapai tanpa mensponsori siapa pun**. Contoh: RO 20 pin × Rp 500.000 = Rp 10.000.000 → 20 PR (jadi Distributor) + 20 PA (jadi Manager).
**Status:** terpasang di `TransactionPoster::post()`, diuji (`AturanV3Test`, `RedemptionTest`, `PostingIntegrityTest`).

## #66 — Hadiah ambang 20 poin = Voucher
**Keputusan (Owner, 1 Nov):** "voucher".
**Isi:** tangga hadiah menjadi Produk (5) · **Voucher (20)** · Handphone (75) · Motor (500) · Mobil (3.500). Sebelumnya baris 20 tertulis "Produk" — itu salah salin dari poster.
**Status:** terpasang di `DatabaseSeeder::hadiah()`, data dev sudah dikoreksi.

## #67 — Syarat pencairan leadership: cukup pernah RO sekali
**Keputusan (Owner, 1 Nov):** "cukup pernah ro sekali".
**Isi:** menegaskan #9. Kalimat poster "RO bulan berjalan ATAU sponsori minimal 1 Reseller" **tidak dipakai**.
**Status:** aturan tercatat; modul pencairan belum dibangun (menunggu #26 tanggal tutup buku).

## #68 — Naik peringkat otomatis dari total PR
**Keputusan (Owner, 2 Nov):** "sistem upgrade bukan semata mata untuk sekedar beli paket yang selisih. Tapi berdasarkan POIN REWARD (PR) saja. Reseller 1 poin, agent 3 poin, distributor 6 poin. Jadi kalau semisal poinnya sudah mencukupi maka status nya akan langsung berubah. Tapi tetap simpan sistem pin upgrade."
**Isi:** dua jalan naik peringkat berlaku bersamaan:
1. **Otomatis dari total PR** — ambang 1 / 3 / 6 PR. PR dari anggota yang disponsori **ikut dihitung** (dikonfirmasi Owner: "yes betul termasuk juga pr downline").
2. **PIN upgrade** — bayar per pin, naik tanpa menunggu PR cukup.
**Catatan:** Manager tetap tidak lewat PR; syaratnya Distributor + 20 PA (#37, #49).
**Konsekuensi yang disadari dan disetujui Owner (pilihan "c"):** karena harga PIN RO (Rp 500.000/pin) lebih murah daripada PIN registrasi/upgrade (Rp 600.000/pin), dan RO juga menghasilkan PR, **PIN upgrade tidak akan pernah dipakai orang**. Dibiarkan.
**Status:** terpasang di `TransactionPoster::promoteByPr()`, diuji (`AturanV3Test`, `ProgresPeringkatTest`).

## #69 — Poin leadership hanya untuk Manager, sisanya hangus
**Keputusan (Owner, 2 Nov):** "pokoknya pl ini tidak bakal dihitung atau bakal terbuang/hangus selama dia belum jadi manager. namun ketika dia menyentuh manager baru bisa".
**Isi:**
- PL **hanya dicatat** untuk anggota yang sudah Manager. Non-Manager tidak dicatat sama sekali — tidak ditabung, tidak dihitung di kemudian hari.
- Manager yang melakukan **RO sendiri** ikut menerima PL dari transaksinya (dikonfirmasi Owner: "iya betul dapat 2 poin juga").
- PL dari jaringan bawah tetap masuk.
**Turunan:** menimpa kalimat lama di §4 "Poin sebelum jadi Manager: terkumpul, tapi baru terbuka setelah jadi Manager" — sekarang **hangus**, bukan terkumpul.
**Status:** terpasang di `TransactionPoster::awardLeadership()`, diuji (`LeadershipPointTest` 7 uji, `AturanV3Test`).

## #70 — Rantai bonus sponsor 50 tingkat, sisa selisih hangus
**Keputusan (Owner, 2 Nov):** "ga usah kasih batas kalau memang perlu kasih sampai 50 aja" dan "tidak masuk ke siapa pun".
**Isi:** rantai upline ditelusuri maksimal **50 tingkat** (dari sebelumnya 20). Kalau di dalam 50 tingkat tidak ada peringkat lebih tinggi, **sisa selisih hangus** — tidak masuk ke siapa pun, termasuk perusahaan.
**Catatan Owner:** "aku yakin karena pasti ada beberapa user yang ngajak user lain dan dia jadi distributor atau lebih tinggi dari dia disitulah rantainya terputus".
**Status:** terpasang (`config/sahih.php` → `sponsor_chain_max_level`), diuji (`AturanV3Test`).

## #71 — Hadiah di atas 3.500 poin dinilai dari total PR
**Keputusan (Owner, 2 Nov):** "ketika udah diatas 3500 ke ulang ke 0 lagi".
**Isi:** dua kelompok hadiah diperlakukan berbeda:
- Ambang **≤ 3.500**: dinilai per putaran. Tiap 3.500 poin, tangga terbuka lagi.
- Ambang **> 3.500**: dinilai langsung dari total PR (ambang 10.000 dapat sekali saat 10.000, dua kali saat 20.000). Sebelumnya hadiah seperti ini **diblokir total**.
**Status:** terpasang di `MilestoneEvaluator::evaluate()`, diuji (`AturanV3Test`).

## #72 — Harga & ambang bisa diubah Owner lewat panel
**Keputusan (Owner, 2 Nov):** "Owner — semua di atas, plus mengubah daftar hadiah dan angka aturan dan harga per 1 poin".
**Isi:** halaman `/admin/pengaturan`, khusus **Superadmin**. Yang bisa diubah: harga per pin registrasi/upgrade, harga per pin RO, nilai poin leadership, syarat PA Manager, putaran hadiah. Tersimpan di tabel `settings`, dengan `config/sahih.php` sebagai nilai awal.
**Catatan:** nilai rupiah bersifat rahasia dan tidak pernah ditampilkan ke anggota.
**Status:** terpasang (`PengaturanHarga`, `PengaturanController`), diuji (`PengaturanHargaTest` 9 uji, `PengaturanHalamanTest` 7 uji).

## #73 — Dua jenis PIN
**Keputusan (Owner, 2 Nov):** "sekarang jadi 2 jenis pin, pin registrasi&upgrade, dan pin RO. Pin registrasi&upgrade itu 1 pin bernilai 600.000 (bisa jadi berubah) ... Pin ro itu bernilai 500.000 dan aturannya tetap sama seperti sebelumnya".
**Isi:** dari tiga jenis menjadi **dua**: PIN registrasi/upgrade dan PIN RO. Harga dihitung **per pin**.
**Contoh:** daftar Agent (3 pin) = 3 × Rp 600.000 = Rp 1.800.000. Upgrade Reseller → Agent (2 pin) = 2 × Rp 600.000 = Rp 1.200.000.
**Status:** tercatat sebagai aturan. Penggabungan jenis di kode **belum dikerjakan** (masih tiga jenis: `registration`, `upgrade`, `ro`).
