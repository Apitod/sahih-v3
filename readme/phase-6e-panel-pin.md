# Phase 6 — Stage E (bagian 2): Panel Admin PIN

Status: **jalan**. Admin sudah bisa membuat PIN, menyaring, dan membatalkan.

---

## 1. Alamat

| Alamat | Isi |
|---|---|
| `/admin/pin` | daftar PIN + saringan + pembatalan |
| `/admin/pin/baru` | formulir pembuatan PIN |

Hanya bisa dibuka admin yang sudah masuk. Anggota yang mencoba membukanya dikirim ke login admin.

## 2. Yang bisa diisi admin

Saat membuat PIN, admin menentukan sendiri: **jenis** (registrasi / upgrade / repeat order), **jumlah poin**, **harga**, **berapa kode sekaligus** (1–50), dan **username pemegang** (boleh kosong).

Tidak ada nominal atau poin yang ditanam di kode. Harga 600.000 dan poin 1 di formulir hanya nilai awal yang bisa diganti — bukan aturan.

Untuk **PIN upgrade**, kolom peringkat tujuan wajib diisi. Kalau kosong, sistem menolak dengan pesan "PIN upgrade harus menyebut peringkat tujuan". Ini yang mencegah PIN upgrade tanpa arah, sesuai keputusan Anda soal Rp 3.000.000 Reseller→Distributor dan Rp 1.800.000 Agent→Distributor.

## 3. Kode PIN

12 karakter, dibuat acak, dan **huruf yang mudah tertukar dibuang** — tidak ada O, 0, I, 1, atau L. Jadi kalau anggota menelepon membacakan PIN-nya, tidak ada "itu nol atau O?". Ada uji yang memeriksa 20 kode sekaligus untuk memastikan tidak ada huruf terlarang yang lolos.

Sistem juga memastikan tidak ada dua PIN dengan kode sama, dan permintaan 5 kode sekaligus menghasilkan 5 kode berbeda.

## 4. Pembatalan

PIN yang belum dipakai bisa dibatalkan, tapi **wajib menyebut alasan**. PIN yang sudah dipakai tidak bisa dibatalkan — tombolnya tidak muncul, dan kalau dipaksa lewat jalur lain tetap ditolak.

## 5. Jejak audit

Setiap PIN yang dibuat dan setiap pembatalan meninggalkan catatan di `audit_logs`: siapa adminnya, apa yang dibuat, nilai sebelum dan sesudah, alasan pembatalan, dan alamat IP. Jadi kalau nanti ada PIN yang muncul entah dari mana, ada jejaknya.

## 6. Uji

**45 uji, 542 asersi, semua lolos** (naik dari 31 uji). Empat belas uji baru khusus PIN: pembuatan tunggal, pembuatan banyak sekaligus, keunikan kode, larangan huruf tertukar, kewajiban peringkat tujuan untuk upgrade, penolakan penempatan PIN ke anggota beku, jejak audit, pembatalan beserta alasannya, penolakan pembatalan PIN terpakai, dan penolakan akses anggota biasa.

## 7. Sudah dicoba di server hidup

Bukan cuma lulus uji: saya login sebagai admin lewat server yang jalan, membuat 2 PIN, dan keduanya muncul di tabel dengan kode `BNJNBUHG68TQ` dan `6X2TZHUWTSKF`. Dua PIN itu masih ada di database sebagai contoh — silakan dibatalkan sendiri kalau mau mencoba tombolnya.

## 8. Belum ada

| Hal | Status |
|---|---|
| Menukar PIN jadi anggota baru | belum — ini bagian berikutnya |
| Menukar PIN upgrade / repeat order | belum |
| Klaim hadiah oleh anggota | belum |
| Catat penarikan | belum |
| Ganti password | belum |

## Gerbang

PIN sudah bisa dibuat dan dikelola. Langkah berikutnya yang paling masuk akal: **penukaran PIN** — anggota memasukkan kode, sistem memeriksa PIN sah dan belum dipakai, lalu mencatat transaksi, poin, dan bonus sponsor lewat mesin yang sudah diuji di Stage D. Di situ semua bagian mulai tersambung.
