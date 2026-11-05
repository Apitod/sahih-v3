# Phase 6 — Stage E (bagian 1): Login & Halaman Poin

Status: **jalan dan bisa dibuka**. Ini bagian pertama Stage E; panel admin masih ringkasan saja.

---

## 1. Yang bisa dilakukan sekarang

| Alamat | Untuk siapa | Isi |
|---|---|---|
| `/masuk` | anggota | login pakai username + password |
| `/beranda` | anggota (wajib login) | poin, riwayat, hadiah |
| `/admin/masuk` | admin | login pakai email |
| `/admin` | admin (wajib login) | jumlah anggota, PIN, transaksi |

Dua pintu terpisah. Anggota yang membuka `/admin` dikirim ke login admin, bukan login anggota — dan login anggota **tidak** memberi akses admin, karena guardnya berbeda (`web` vs `admin`).

## 2. Halaman poin

Tiga angka di atas: Poin Afiliasi, Poin Reward, Poin Leadership. **Semuanya dibaca dari ledger**, bukan dari kolom cache di tabel `members`. Ada uji khusus yang mengosongkan kolom cache lalu memastikan angkanya tetap benar — supaya kalau nanti ada yang salah tulis, halaman ini tidak ikut bohong.

Di bawahnya: riwayat poin (10 terakhir, dengan catatan asalnya) dan daftar hadiah yang sudah jadi hak anggota (nama hadiah + ambang + putaran + status).

**Nilai rupiah hadiah tidak muncul di halaman mana pun.** Yang tampil hanya nama hadiah dan ambang poinnya, sesuai keputusan Anda.

## 3. Tampilan

Token dari `phase-6b-design-system.md`: latar krem `#FFF9F2`, tinta `#2D3436`, tombol amber `#F0A04B` dengan teks tinta (kontras 5,94:1), hijau `#84994F` hanya untuk penanda peringkat — bukan tombol. Sudut 8px, tanpa kaca/blur, tanpa bayangan menyala, tanpa emoji di judul.

**Satu yang belum sesuai:** font Playfair Display dan Outfit belum dipasang, jadi sementara jatuh ke Georgia dan font sistem. Bentuk hurufnya beda dari rencana. Perlu izin mengunduh berkas font (2 keluarga, ~200 KB) untuk membereskannya.

## 4. Keamanan yang sudah diperiksa

- Password disimpan sebagai hash, tidak pernah ditampilkan kembali.
- Anggota berstatus `suspended` ditolak masuk, dengan pesan yang tidak membocorkan apakah username-nya ada.
- Sesi diperbarui saat login (cegah session fixation).
- Akun admin pertama dibuat dengan password acak 20 karakter, ditulis ke `~/.sahih-admin-credentials` mode 600 — **tidak dicetak ke layar**.

## 5. Uji

**31 uji, 484 asersi, semua lolos.** Termasuk: tamu diarahkan ke login, password salah ditolak, anggota beku ditolak, anggota tidak bisa masuk admin, admin nonaktif ditolak, halaman poin membaca ledger, keluar mengakhiri sesi.

## 6. Cara mencoba

Server pengembangan jalan di port 8000, hanya lewat jaringan pribadi (tailnet), bukan internet.

- Anggota contoh: `demo` / `demo12345` (data contoh, bukan anggota sungguhan)
- Admin: `admin@sahih.local`, password di `~/.sahih-admin-credentials`

Ini server pengembangan, bukan produksi: kalau mesin mati, alamatnya mati. Hosting tetap ditunda sampai Stage F.

## 7. Belum ada

| Hal | Status |
|---|---|
| Panel admin: buat PIN, proses klaim, catat penarikan | belum |
| Pendaftaran anggota sendiri lewat web | belum — masih lewat admin |
| Halaman jaringan (downline) | belum |
| Ganti password | belum |
| Halaman klaim hadiah (anggota mengajukan) | belum |

## Gerbang

Login dan halaman poin sudah bisa dipakai dan diperiksa. Berikutnya saya sarankan **panel admin untuk membuat PIN** — karena tanpa itu, tidak ada anggota baru yang bisa didaftarkan, dan seluruh alur uang bergantung pada PIN.
