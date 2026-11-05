# Phase 6 — Stage B: Design System

Status: **USULAN — menunggu persetujuan Owner.** Belum ada kode.
Sumber: `https://sahihofficial.id/` dibaca 30 Okt 2026 — nilai diambil dari `tailwind.config` yang tertanam di halaman + computed style elemen yang benar-benar tampil. Jadi ini **bukan karangan**: setiap warna dan font di bawah ada asalnya.
Aset tersimpan: `brand/logo-sahih.png` (500×500) dan `brand/produk-sahih.png` (305×430).

---

## 1. Token merek — apa adanya dari situs

### Warna

| Token | Hex | Nama | Di situs dipakai untuk |
|---|---|---|---|
| `brand-1` | `#FCE7C8` | Krem | latar section, hero gradient |
| `brand-2` | `#B1C29E` | Sage | ikon, aksen lembut, border |
| `brand-3` | `#FADA7A` | Kuning mentega | sorotan, badge |
| `brand-4` | `#F0A04B` | Amber | tombol login, ikon aktif, scrollbar |
| `brand-dark` | `#2D3436` | Arang | semua teks utama, latar footer |
| — | `#FFF9F2` | Putih hangat | ujung gradient, latar bersih |
| — | `#84994F` | Sage gelap | tombol `btn-brand` |
| — | `#6D7F42` | Sage gelap hover | hover `btn-brand` |

Abu netral yang sudah dipakai situs: `#4B5563` (teks sekunder), `#6B7280`, `#9CA3AF` (teks samar), `#D1D5DB` (garis).

Gradient hero di situs: `linear-gradient(135deg, #FCE7C8 0%, #FFF9F2 100%)`.

### Font

| Peran | Keluarga | Bobot yang dimuat |
|---|---|---|
| Judul (serif) | **Playfair Display** | 400, 700, 900, italic 400 |
| Semua UI (sans) | **Outfit** | 300, 400, 500, 600, 700, 800 |

URL asli situs:
`https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&display=swap`

### Logo

`brand/logo-sahih.png` — 500×500, RGBA, dipakai juga sebagai favicon.
**Hanya ada versi PNG.** Saya tidak akan menggambar ulang logonya (risiko salah bentuk). Kalau ada file vektor (SVG/AI/EPS), kirim — kalau tidak, PNG dipakai apa adanya.

### Ikon

Situs memakai Line Awesome 1.3.0 dari CDN. Untuk aplikasi saya **tidak** menyarankan itu (CDN eksternal, ikon tidak konsisten ukurannya). Usul: satu set SVG yang di-host sendiri. Ini butuh persetujuan (§9).

---

## 2. Kontras — masalah nyata di situs, dan cara kita perbaiki

Saya hitung rasio kontras WCAG semua pasangan yang dipakai situs. Hasilnya:

| Pasangan | Rasio | Status |
|---|---|---|
| `#2D3436` teks di putih | 12,68:1 | ✅ aman |
| `#2D3436` teks di krem `#FCE7C8` | 10,51:1 | ✅ aman |
| `#4B5563` teks di putih | 7,56:1 | ✅ aman |
| `#F0A04B` teks di `#2D3436` | 5,94:1 | ✅ aman |
| **`#FFFFFF` teks di tombol `#F0A04B`** | **2,14:1** | ❌ **gagal** — ini yang dipakai tombol Login situs |
| **`#9CA3AF` teks di putih** | **2,54:1** | ❌ gagal untuk teks normal |
| **`#B1C29E` teks di putih** | **1,90:1** | ❌ gagal |
| `#84994F` teks di putih | 3,16:1 | ⚠️ hanya untuk teks besar/ikon |
| `#6D7F42` teks di putih | 4,41:1 | ⚠️ nyaris, belum lolos |

**Keputusan desain yang saya ambil dari temuan ini:**

- Tombol utama: isi `#F0A04B`, **teks `#2D3436`** (5,94:1). Bukan teks putih. Tetap amber, tetap merek, tapi terbaca.
- Teks sekunder pakai `#4B5563` (7,56:1), bukan `#9CA3AF`.
- Sage `#B1C29E` hanya untuk latar/ikon/border, **tidak pernah** untuk teks di putih.
- Teks status hijau pakai turunan gelap `#3F6212` (7,08:1). Merah `#B91C1C` (6,47:1). Kuning-tua `#92400E` (7,09:1).

Aplikasi anggota dipakai berjam-jam di HP terang. Kontras bukan kemewahan — kalau angka bonus susah dibaca, orang salah paham.

---

## 3. Tipografi

Prinsip: **serif untuk kesan, sans untuk informasi.** Playfair di judul halaman dan angka besar; Outfit di semua label, tabel, tombol, isian.

| Peran | Font | Ukuran | Bobot | Catatan |
|---|---|---|---|---|
| Judul halaman | Playfair | 32px / 2rem | 700 | satu per halaman |
| Judul bagian | Outfit | 20px | 600 | bukan serif — biar dashboard tidak berat |
| Judul kartu | Outfit | 16px | 600 | |
| Isi | Outfit | 15px | 400 | line-height 1,6 |
| Label form | Outfit | 13px | 500 | selalu di atas input |
| Teks bantu | Outfit | 13px | 400 | warna `#4B5563` |
| Angka besar (poin/saldo) | Outfit | 32–44px | 700 | `font-variant-numeric: tabular-nums` |
| Label kecil / badge | Outfit | 11–12px | 700 | huruf besar, spasi 1,2px — ciri situs |

Aturan mati: **Playfair tidak boleh** muncul di tabel, label, tombol, atau angka di dalam tabel. Kalau serif dipakai di mana-mana, kesan premiumnya hilang dan berubah jadi norak.

---

## 4. Bentuk, jarak, bayangan

| Hal | Nilai | Alasan |
|---|---|---|
| Radius tombol | `9999px` (pill) | ciri khas situs — dipertahankan |
| Radius kartu | `16px` | situs pakai 40px; terlalu bulat untuk layar data padat |
| Radius input | `12px` | |
| Radius badge | `9999px` | |
| Jarak dasar | 4 / 8 / 12 / 16 / 24 / 32 / 48 | kelipatan 4 |
| Garis | `1px solid rgba(177,194,158,0.35)` | persis border kartu situs |
| Bayangan | `0 1px 2px rgba(45,52,54,.06), 0 8px 24px -12px rgba(45,52,54,.18)` | satu level saja, tanpa glow |
| Tinggi baris tabel | 44px | dashboard padat |
| Target sentuh | minimal 44×44px | HP |

Situs memakai `box-shadow` amber berpendar di tombol. Di aplikasi saya buang — pendaran amber di 20 tombol sekaligus bikin layar terasa murah.

### Yang sengaja TIDAK dibawa dari situs

| Elemen situs | Kenapa dibuang |
|---|---|
| `backdrop-blur` + `bg-white/50` (kartu kaca) | Teks di atas latar tembus pandang susah dibaca di HP terang; ini gaya landing, bukan aplikasi data |
| Radius kartu 40px | Terlalu bulat untuk layar yang isinya tabel & angka — memakan ruang, mengurangi kepadatan |
| Pendaran bayangan amber | 20 tombol berpendar sekaligus = layar terasa murah, bukan premium |
| Jarak antar-bagian ala hero (py-20 s/d py-32) | Di dashboard artinya 1 layar hanya memuat 2 kartu |
| Animasi `floating` | Ikon yang mengapung tidak punya arti di aplikasi transaksi |
| Ikon Line Awesome dari CDN | Campuran ikon isi & garis, ukuran tidak seragam, dan bergantung server pihak lain |
| Emoji di judul halaman (situs memakai 🐴 di title) | Tidak ada gunanya di aplikasi; bikin tab browser tidak profesional |

Yang **dipertahankan** karena itu ciri mereknya: palet 5 warna, pasangan Playfair + Outfit, tombol pill, border sage tipis, dan latar krem untuk area sorotan.

---

## 5. Komponen v1.0

| Komponen | Varian / isi | Catatan perilaku |
|---|---|---|
| Tombol | primer (amber), sekunder (garis), hantu, bahaya; ukuran sm/md/lg | keadaan: normal, hover, fokus, memuat, nonaktif |
| Input teks | dengan label, teks bantu, pesan galat | galat selalu di bawah, warna `#B91C1C` |
| Input PIN | 6 kotak terpisah, tempel sekaligus bisa | auto-lanjut antar kotak |
| Pilihan paket | 3 kartu (1/3/6 poin) | harga + peringkat + tarif bonus terlihat |
| Kartu statistik | angka besar + label + delta | angka tabular, rata kanan |
| Tabel | header lengket, urut, angka rata kanan | < 640px jadi kartu bertumpuk |
| Badge peringkat | Reseller / Agent / Distributor / Manager | warna beda per peringkat, kontras ≥ 4,5 |
| Tangga milestone | 5 ambang, tanda "sudah lewat" | **tanpa angka rupiah** |
| Linimasa riwayat | transaksi, poin, bonus | satu baris = satu kejadian, ada tautan ke asal |
| Modal | konfirmasi, form singkat | fokus masuk ke elemen pertama, Esc menutup |
| Toast | sukses / galat / info | 4 detik, bisa ditutup |
| Empty state | ikon + satu kalimat + satu aksi | jujur, tidak memakai data palsu |
| Navigasi | sidebar (≥1024px) → bottom nav (HP) | maksimal 5 item di bottom nav |
| Tab | untuk sub-halaman anggota | |
| Peringatan | info / peringatan / bahaya | |

---

## 6. Aturan anti "AI slop" — ini yang paling menentukan

Daftar larangan, bukan selera. Semua bisa diperiksa di review.

1. **Tidak ada gradien ungu-biru.** Palet hanya token §1. Tidak ada warna baru tanpa persetujuan.
2. **Tidak ada glassmorphism.** Situs memakai `bg-white/50 backdrop-blur` — itu untuk landing, bukan aplikasi data. Kartu aplikasi: putih solid.
3. **Tidak ada emoji sebagai ikon.** Tidak ada ✨🚀🎯 di judul, tombol, atau notifikasi.
4. **Tidak ada tiga kartu "Cepat • Aman • Terpercaya"** dengan ikon generik. Kalau tidak ada isi nyata, jangan bikin section.
5. **Tidak ada data palsu.** Grafik kosong ya kosong, dengan tulisan "Belum ada transaksi". Tidak ada angka karangan supaya layar terlihat penuh.
6. **Tidak ada teks contoh.** Semua label Bahasa Indonesia yang benar ("Poin Reward", bukan "Reward Points" atau "Lorem").
7. **Serif hanya di judul halaman dan angka besar.** Bukan di tabel, label, atau tombol.
8. **Satu aksen amber per layar.** Tombol utama satu. Sisanya netral. Kalau semua tombol amber, tidak ada yang utama.
9. **Angka selalu format Indonesia**: `Rp 1.200.000`, poin bulat tanpa desimal, rata kanan di kolom angka, `tabular-nums`.
10. **Satu set ikon saja**, garis 1,5px, ukuran 16/20/24. Tidak campur ikon isi dan garis.
11. **Animasi hanya transisi 150–200ms** untuk hover/fokus/buka-tutup. Tidak ada parallax, tidak ada animasi masuk beruntun di dashboard.
12. **Kepadatan dashboard ≠ kepadatan landing.** Landing boleh lapang; dashboard harus padat dan cepat dipindai.
13. **Jangan bikin ilustrasi orang/kartun/3D.** Kalau butuh gambar, pakai foto produk asli (`brand/produk-sahih.png`).
14. **Teks rata kiri**, bukan rata tengah, kecuali halaman kosong dan angka di kartu statistik.

---

## 7. Aksesibilitas

- Kontras teks ≥ 4,5:1; ikon besar ≥ 3:1.
- Fokus keyboard selalu terlihat: `outline: 2px solid #F0A04B; outline-offset: 2px`.
- Label form selalu ada — placeholder bukan pengganti label.
- Bahasa halaman `lang="id"`.
- Tabel: `<th scope>`, angka rata kanan, satuan disebut di header.
- Status tidak boleh dibedakan hanya lewat warna — selalu ada teks ("Lunas", "Menunggu").
- Semua aksi bisa dilakukan tanpa mouse.

---

## 8. Responsif

| Lebar | Perilaku |
|---|---|
| < 640px | satu kolom, tabel → kartu, sidebar → bottom nav, tombol selebar layar |
| 640–1023px | dua kolom, sidebar jadi ikon saja |
| ≥ 1024px | sidebar penuh + area isi maksimal 1200px |

Angka besar (poin, saldo) tetap terbaca tanpa perlu zoom di HP 360px.

---

## 9. Yang perlu keputusan sebelum Stage C

| # | Hal | Keputusan Owner (30 Okt) |
|---|---|---|
| 1 | Tombol utama | **Amber** dengan teks arang `#2D3436` |
| 2 | Set ikon | "Sesuaikan" → **Lucide**, SVG di-host sendiri, lisensi ISC |
| 3 | Font | "Sesuaikan" → **self-host** (Outfit + Playfair Display, woff2) |
| 4 | Logo vektor | **Tidak ada** → PNG 500×500 dipakai apa adanya, tidak digambar ulang |
| 5 | Landing page | **Nanti saja, jangan dulu** — v1.0 aplikasi saja |

---

## Gerbang Stage B — LEWAT

Kelima hal di §9 sudah diputuskan Owner 30 Okt. Stage B selesai. Lanjut ke `phase-6c-architecture-data.md`.
