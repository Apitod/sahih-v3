# Cek Silang: Dokumen Klien v8 vs BUSINESS_RULES

> **Diperbarui 30 Okt:** tiga temuan di bawah sudah diputuskan Owner. Yang berubah untuk v9 ada di bagian akhir file ini.

Tanggal: 30 Oktober 2026
Sumber: `Sistem-dan-Fitur-Smart-Store-v8.pdf` (208 baris teks, 20 tabel) — dikirim Owner.
Pembanding: `BUSINESS_RULES.md`

## Hasil per bagian

| Bagian v8 | Isi | Cocok dengan BUSINESS_RULES? |
|---|---|---|
| 1. Paket | 1/3/6 poin, Rp 600rb/1,8jt/3,6jt, 15/18/20% | ✅ cocok |
| 1. Nilai tukar poin | "Rp 50.000" | 🔴 **BENTROK** dengan §7 v8 sendiri |
| 2. Cara bonus dibagi | tarif sendiri + selisih + compression + batas 20% | ✅ cocok |
| 2. Contoh 1–4 | 90k+18k+12k; 108k+0+12k; 90k+0; 360k | ✅ cocok persis |
| 2. Ritme | cair harian, saldo bisa ditarik hari itu | ✅ cocok (v8 lebih rinci) |
| 3. Leadership | Rp 40.000/poin, 20 poin, hangus, pernah RO sekali, bulanan, tanpa turun peringkat | ✅ cocok |
| 3. Compression | berhenti kalau bawahan sudah Manager | ✅ cocok |
| 4. Royal Manager | Rp 2.000/poin (5%), dari PL Manager langsung, tahunan, compression antar RM | ✅ cocok |
| 4. Tabel ritme | harian / bulanan / tahunan | ✅ cocok |
| 5. RO | Rp 500.000/poin, 1+1 poin, tanpa bonus uang, PL jalan, bebas jumlah, PIN RO | ✅ cocok |
| 6. Tiga jenis poin | PA/PR/PL, 3 PA dan 3 PR terpisah | ✅ cocok |
| 6. PA | "Syarat naik peringkat" | 🟡 kabur — tidak menyebut 20 poin |
| 6. Poin RO masuk mana | **tidak disebut** | 🟡 lubang (ini B2) |
| 7. Penukaran | 5/20/75/500/3500 poin | ✅ cocok angkanya |
| 8. PIN | 5 langkah, 3 jenis, tanpa kedaluwarsa, tanpa upload bukti | ✅ cocok |
| 9. Form registrasi | username, nama, password, referral, telepon, alamat; tanpa KTP | ✅ cocok |
| 9. Menu upgrade | 3 baris, 2 baris terakhir ditandai hitungan sendiri | 🟡 masih terbuka (B3) |
| 9. Struktur jaringan | tampil 3 level, hitung tanpa batas | ✅ cocok |
| 10. Catatan | kosong | — |

## Tiga hal yang harus dibereskan sebelum dikirim ke Pak Agung

**1. Nilai tukar poin masih bentrok di dalam dokumen itu sendiri.**
Bagian 1: "Nilai tukar poin jadi hadiah **Rp 50.000**".
Bagian 7: 5 poin = Rp 250.000 (berarti Rp 50.000/poin ✓), tapi 20 poin = Rp 500.000 (berarti **Rp 25.000**/poin ✗), 500 poin = Rp 20 juta (**Rp 40.000**/poin ✗).
Kalau Pak Agung membaca dua bagian ini, dia akan bingung. Pilih satu:
- (a) hapus kalimat "Rp 50.000" di bagian 1 → tabel hadiah yang berlaku, atau
- (b) pertahankan Rp 50.000/poin → tabel hadiah harus dikoreksi jadi 5/20/75/500/3500 × Rp 50.000 = 250rb/1jt/3,75jt/25jt/175jt.

**2. Dua baris upgrade masih berlabel "hitungan kami sendiri".**
Reseller→Distributor Rp 3.000.000 dan Agent→Distributor Rp 1.800.000. Ini wajar ditanyakan, tapi kalau mau dikirim sebagai dokumen final, lebih rapi kalau angkanya sudah dipastikan dulu.

**3. Poin dari RO masuk ke mana, belum ditulis.**
Bagian 6 hanya bilang poin join dan poin upgrade masuk PR. Poin RO tidak disebut. Ini pertanyaan yang pasti muncul — dan ini juga yang bikin A2/B2 belum tuntas.

## Dua tambahan kecil yang saya sarankan

- Bagian 6: tulis PA dipakai untuk **kualifikasi Manager 20 poin**, bukan cuma "syarat naik peringkat". Sekarang hubungan PA ↔ 20 poin harus disimpulkan sendiri oleh pembaca.
- Bagian 5: "Bonus generasi" disebut tidak ada, tapi tidak pernah dijelaskan apa itu. Satu baris keterangan akan menutup pertanyaan.

## Yang ada di BUSINESS_RULES tapi belum ada di v8

Ini bukan salah v8 — memang keputusan operasional yang belum perlu masuk dokumen klien:

| Hal | Nilai | Perlu masuk v8? |
|---|---|---|
| Pajak bonus | tidak ada potongan | tidak |
| Pencairan | transfer bank, minimum Rp 100.000 | mungkin, kalau ada menu tarik saldo |
| Tanggal tutup buku | belum diputuskan | tidak, tapi menghambat sistem |
| Peran admin | generate PIN + kelola member | tidak |

## Yang berubah untuk v9 (keputusan Owner 30 Oktober)

| Bagian v8 | Sekarang | Jadi |
|---|---|---|
| 1 | "1 Point = Rp 50.000" | **tetap** |
| 6 | PR disebut dari join & upgrade | tambah **RO** sebagai sumber PR |
| 7 | "Penukaran Point" + kolom rupiah | ganti judul jadi **"Hadiah Poin"**, hapus kolom rupiah, tambah kalimat: poin **tidak berkurang**, ini tangga pencapaian |
| 3 | "Distributor + 20 poin" | **tetap** — Owner memastikan 20 |
| 9 | dua baris upgrade berlabel hitungan sendiri | **tetap** |
| 9 (form) | kode referral "Ya" (wajib) | jadi **opsional** — Owner 30 Okt |
