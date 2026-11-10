# Pembaruan Sahih Bodyfeed — 8–9 November 2026

Disusun: 9 Nov 2026, 18:13 WITA (CST, UTC+8).
HEAD lokal & remote: `897d390`. Suite terakhir: 315 passed, 4 failed (kegagalan
hanya di test PIN gratis yang belum jadi — lihat "Belum selesai" di bawah).

---

## 1. Fix 500 saat penarikan sebagian bonus leadership

Gejala: "pas aku coba penarikan server error".
Akar masalah: penarikan sebagian memecah baris bonus lewat
`$entry->replicate()`. Baris sisa ikut menyalin kunci periode
(`kind + available_at`), lalu kena unique index
`bonus_entries_periode_unik` → error 500

```
Duplicate entry '76-leadership-2026-10-01 00:00:00'
```

Perbaikan (commits `7ee4a19`, `f1b1ba4`, `c2e0e7a`):

- Migrasi `2026_11_08_000002_tandai_baris_pecahan_bonus`: kolom `split_dari_id`
  nullable; `periode_kunci` dibuat ulang bersyarat — hanya terisi untuk baris
  asli (`split_dari_id IS NULL`), jadi baris pecahan bebas dari unique index.
  Cabang SQLite juga ditangani.
- `SaldoController::tarik` mengisi `split_dari_id` pada baris sisa, dan
  `replicate(['periode_kunci', 'withdrawal_id'])` agar kolom generated tidak
  ikut tersalin.
- Test `SaldoTest.php` "penarikan sebagian memecah bonus leadership tanpa
  bentrok kunci periode". RED dibuktikan via `git stash` pada controller.

## 2. Penanda pencairan menunggu di admin (transfer manual)

Keputusan pemilik: transfer tetap manual; yang penting ada tanda di admin.

- Badge jumlah `withdrawal` status `requested` di nav admin — desktop
  (`data-menunggu`) dan mobile, label menu "Cair".
- Banner kuning di `/admin/pencairan` (`data-menunggu-total`): "N permintaan
  menunggu diproses. Transfer manual dulu sesuai nomor rekening, baru tandai
  Paid."
- Keduanya dihitung per render (`ponytail:`), hilang otomatis saat tidak ada
  `requested`.

Alur status tetap: `requested → approved → paid`, atau `rejected` (catatan
wajib, bonus kembali `available`). Test: `SaldoTest.php` "admin dapat tanda
jumlah penarikan yang menunggu diproses" (butuh `approved` dulu sebelum
`paid`). Catatan jujur: tidak ada notifikasi email/WA; `paid` tidak bisa
dibatalkan lewat UI.

## 3. Fix 419 PAGE EXPIRED di pendaftaran prod

Dua penyebab ditemukan dari luar (bukti: `Max-Age=7200`, dan
`www.sahihofficial.id` menjawab 200 sendiri):

1. `SESSION_LIFETIME=120` → cookie sesi cuma 2 jam.
2. Form di `www.*` mem-POST ke host absolut `sahihofficial.id` → cookie sesi
   terpecah antar-host.

Perbaikan (`c5d0f7c`, `7aa9552`, `18bed0d`; 312 pass saat itu):

- `SecurityHeaders.php` me-redirect `www.*` → 301 ke domain utama, dibangun
  dari `preg_replace('#://www\.#', '://', $request->fullUrl())`.
- `bootstrap/app.php`: render callback 419 khusus route `daftar.store`
  menangkap `HttpExceptionInterface` berstatus 419 (bukan
  `TokenMismatchException` — Laravel sudah mengonversinya lebih dulu di
  `Handler::prepareException()`), redirect balik ke form/banda dengan pesan
  sesi. Route lain tetap 419.
- Prod: `www.sahihofficial.id/daftar` → 301; POST token basi → 302 ke form.
  Disarankan `SESSION_LIFETIME=720` (12 jam) agar konsisten dengan
  `Max-Age=43200`.

## 4. CRUD anggota untuk admin (hapus / soft delete)

- Migrasi `2026_11_08_000003_tambah_soft_delete_anggota` (`deleted_at`) +
  `SoftDeletes` di `Member`.
- `DELETE /admin/anggota/{anggota}` — **ditolak** kalau akun punya poin,
  bonus, pencairan, klaim hadiah, atau downline. Pesan error mengarahkan ke
  Status akun → Dibekukan. Akun "bersih" bisa dihapus.
- Tombol di `/admin/anggota/{id}/edit` (konfirmasi JS) + toast status di
  `/admin/anggota/index`. Audit log `anggota.hapus` (tanpa data sensitif).
- Test: `tests/Feature/AdminAnggotaHapusTest.php` (3 test).

## 5. Perintah `sahih:diagnosa` untuk cek data produksi

cPanel mematikan `proc_open`/`shell_exec`, jadi `php artisan tinker` tidak
bisa dipakai di prod. Ditambah `app/Console/Commands/DiagnosaSahih.php`:
`php artisan sahih:diagnosa` mencetak total anggota, manager, Royal Manager,
transaksi, agregat poin/bonus/pencairan per kind, nilai aturan aktif, dan 5
migrasi terakhir.

## 6. Verifikasi pembonusan di dev lokal

Seeder `SkenarioRoyalManagerSeeder` (data uji, 18 member, 4 transaksi RO):

| Jenis | Hasil | Validasi |
|---|---|---|
| Leadership | RM Rp2.000.000, mgr1 Rp4.800.000, mgr2 Rp3.200.000 | 50/120/80 PL × Rp40.000 ✓ |
| Royal Manager | Rp400.000 (RM) | 200 PL manager langsung × Rp2.000 ✓ |
| Sponsor+differential | agen111 Rp108.000, dist11 Rp12.000 | 600.000 × 18%/2%, total = 20% ✓ |

Diagnosa lokal: 1 royal manager, 4 baris bonus, transaksi join Rp600.000
tanpa selisih antara total bonus baris dan `sponsor_bonus_total`.

## 7. Konfigurasi prod yang perlu dipegang

- `SAHIH_PENCAIRAN_AKTIF=true` di `.env` prod agar halaman `/tarik`
  mengizinkan pengajuan pencairan.
- `SESSION_LIFETIME=720` bila ingin sesi 12 jam konsisten dengan cookie.
- `bash deploy.sh` tetap cara deploy resmi; butuh `git pull origin master`
  bila output "Already up to date" padahal HEAD belum `18bed0d`/`897d390`.

---

## Belum selesai — PIN registrasi gratis (penyimpanan lokal saja)

Keputusan pemilik: admin bisa menerbitkan PIN gratis (0 poin, Rp0) dengan
rank target yang bisa dipilih (reseller/agent/distributor). Member berbayar
tidak bisa memilih sponsor yang belum punya poin; sponsor pada umumnya tetap
dapat bonus sponsor. Hanya admin yang boleh menerbitkan PIN gratis.

File yang sudah ada di working tree (belum di-commit, belum di-push):

- `database/migrations/2026_11_09_000001_tambah_is_free_ke_pins.php`
  — kolom `is_free` boolean di `pins`.
- `tests/Feature/PinGratisTest.php` — 4 test (semua merah):
  1. admin bisa buat PIN gratis dengan rank target
  2. daftar pakai PIN gratis → rank target, 0 poin, sponsor NULL
  3. tidak bisa pilih sponsor yang belum punya poin
  4. bisa pilih sponsor yang sudah punya poin
- `app/Http/Controllers/Admin/PinController.php` — satu-satunya perubahan:
  validasi `'is_free' => ['nullable', 'boolean']`.

Yang masih harus dikerjakan:

- [ ] Validasi `is_free` di `PinController`: paksa `points=0, price=0`,
      `type=registrasi_upgrade`, izin `target_rank` untuk PIN gratis; simpan
      flag + audit.
- [ ] Form `/admin/pin/create`: checkbox "PIN Gratis" + dropdown rank target.
- [ ] `RegistrasiController`: untuk PIN gratis, rank member = `target_rank`,
      `sponsor_id` tetap NULL; tolak sponsor dengan `totalPa() === 0`.
- [ ] `TransactionPoster`: lewati bonus sponsor bila `amount = 0` (PIN gratis).
- [ ] Hijauakan 4 test, suite penuh, commit per-file, push.

## Verifikasi

- Suite penuh terakhir: **315 passed (1559 assertions), 19,33 dtk** +
  4 failed (PinGratisTest, disengaja merah).
- Prod terakhir terlihat di `0c67de5`; `897d390` sudah di-push lokal tapi
  belum dikonfirmasi ter-deploy di prod.
- Kredensial prod yang sempat terkirim di chat (`DB_USERNAME/DB_PASSWORD`)
  tidak dicatat di file ini; **ganti sandi DB prod** dan jangan kirim
  password ke chat lagi.
