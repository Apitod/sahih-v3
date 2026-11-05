# Phase 6 — Stage E (bagian 3): Penukaran PIN

Status: **jalan dan sudah dicoba di server hidup**. Ini titik di mana semua bagian mulai tersambung.

---

## 1. Alamat

`/tukar` — halaman anggota untuk memasukkan kode PIN. Ada juga daftar PIN yang dititipkan ke akunnya dan riwayat transaksinya sendiri.

## 2. Yang terjadi saat PIN dipakai

Satu tombol, tapi di belakangnya jalan berurutan dalam **satu database transaction** — kalau satu langkah gagal, semuanya batal, tidak ada setengah jadi:

1. PIN dikunci (`lockForUpdate`) supaya dua permintaan bersamaan tidak bisa memakai PIN yang sama
2. Diperiksa: ada, belum dipakai, tidak dibatalkan, bukan milik orang lain, arah upgrade benar
3. Transaksi dicatat (kode `TRX-…`, nilai, poin, waktu)
4. PIN ditandai terpakai beserta siapa dan kapan
5. Peringkat naik kalau PIN-nya PIN upgrade
6. Poin masuk ke sponsor lewat ledger
7. Bonus sponsor dibagi sepanjang rantai upline
8. Hadiah milestone dievaluasi
9. Jejak audit ditulis

Semua langkah 3–9 itu mesin yang sudah diuji di Stage D. Halaman ini hanya menambah pintu masuknya.

## 3. Penolakan yang sudah diatur

| Keadaan | Yang terjadi |
|---|---|
| Kode tidak dikenal | "Kode PIN tidak dikenal. Periksa lagi hurufnya." |
| PIN sudah dipakai | ditolak |
| PIN dibatalkan admin | ditolak |
| PIN dititipkan ke anggota lain | ditolak |
| PIN registrasi dipakai anggota lama | ditolak, diarahkan ke admin |
| PIN upgrade tanpa peringkat tujuan | ditolak |
| PIN upgrade ke peringkat yang sudah dilewati | ditolak, **PIN tetap utuh** |

Poin terakhir itu penting: kalau anggota Distributor memakai PIN "naik ke Agent", PIN tidak hangus. Sistem menolak sebelum memakai apa pun.

## 4. Kode yang diketik bebas

Huruf kecil, spasi di depan-belakang, tanda hubung — dibersihkan dulu sebelum dicocokkan. Anggota tidak perlu mengetik persis.

## 5. Uji

**58 uji, 587 asersi, semua lolos** (naik dari 45). Tiga belas uji baru: pemakaian PIN repeat order, penulisan kode bebas, penolakan PIN terpakai, PIN dibatalkan, PIN milik orang lain, PIN registrasi, kenaikan peringkat lewat upgrade, penolakan upgrade mundur, upgrade tanpa tujuan, kode tak dikenal, kode kosong, tamu, dan perhitungan bonus sponsor.

## 6. Sudah dicoba sungguhan

Lewat server hidup, bukan hanya uji otomatis:

- Admin membuat PIN repeat order 2 poin Rp1.200.000 untuk username `demo` → kode `JJSXQ8KRCFZH`
- `demo` mengetik kode itu dalam huruf kecil di `/tukar`
- Hasil: PIN jadi `used`, transaksi `TRX-PHMGLJ12BPXT` tercatat, sponsor dapat 2 poin PR, bonus sponsor Rp240.000 (20% × Rp1.200.000)

Angka bonus itu cocok dengan tarif Distributor 20% di `BUSINESS_RULES.md`, bukan tebakan.

## 7. Celah yang saya lihat

**Pendaftaran anggota baru belum ada di web.** PIN registrasi sengaja saya tolak di halaman tukar, karena PIN registrasi seharusnya dipakai saat anggota itu lahir, bukan sesudahnya. Jadi untuk sekarang anggota baru masih dibuat manual. Ini bagian berikutnya.

**Anggota belum punya halaman klaim hadiah.** Hadiahnya sudah muncul di beranda sebagai hak, tapi belum ada tombol mengajukan.

## Gerbang

Alur uang sudah utuh: PIN dibuat admin → dipakai anggota → transaksi, poin, bonus, peringkat, hadiah tercatat. Yang belum: kelahiran anggota baru lewat web, dan klaim hadiah.
