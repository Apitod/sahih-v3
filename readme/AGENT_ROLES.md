# AGENT_ROLES — Peran & Batas Wewenang

## 1. Agent yang ada sekarang

| Nama | Peran Paperclip | Model | Tugas | ID |
|---|---|---|---|---|
| `apitod` | `pm` | `free` | Klasifikasi permintaan, pecah tugas, tentukan specialist, konsolidasi laporan, eskalasi ke Owner | `88910702-55df-4080-8de3-eccf02ca7e15` |
| `implementer` | `engineer` | `code` | Menulis & menjalankan kode, migrasi, perbaikan | `8c8231db-d36c-47b5-998d-814fa9155eba` |
| `qa` | `qa` | `free` | Verifikasi acceptance criteria lewat bukti nyata; menolak klaim tanpa bukti | `c1134c73-372f-4f0a-9fe4-b0cb23293c3b` |

Semua pakai adapter `hermes_local`. Company: `SAH` (`7adae314-a61d-47f0-91c4-a0b5ce872b52`).

## 2. Agent yang belum dibuat

| Peran | Kapan perlu | Alasan belum dibuat |
|---|---|---|
| `designer` | Saat masuk pekerjaan tampilan | Belum ada pekerjaan UI |
| `security` | Sebelum menyentuh login, data pribadi, atau produksi | Belum sampai tahap itu |
| `devops` | Saat deploy ke Rumahweb | Menunggu jawaban hosting |
| `architect` | Saat desain database | Bisa dirangkap `implementer` untuk sekarang |

Membuat agent baru **butuh persetujuan Owner**.

## 3. Siapa boleh apa

| Tindakan | PM | Implementer | QA | Owner |
|---|---|---|---|---|
| Mengubah aturan bisnis / angka uang | ✗ | ✗ | ✗ | ✓ |
| Mengubah acceptance criteria | ✗ | ✗ | ✗ | ✓ |
| Memutuskan rencana kerja rutin | ✓ | ✗ | ✗ | ✓ |
| Menulis & menjalankan kode | ✗ | ✓ | ✗ | ✓ |
| Menilai pekerjaan berkode | ✓ (konsolidasi) | ✗ (karya sendiri) | ✓ | ✓ |
| Meloloskan gate approval | ✗ | ✗ | ✗ | ✓ |
| Menutup tugas jadi `done` | ✓ setelah VERIFIED | ✗ | ✗ | ✓ |
| Eskalasi ke Owner | ✓ | ✓ (lewat PM) | ✓ (lewat PM) | — |

Aturan keras:
1. **Implementer tidak boleh me-review kode yang dia tulis sendiri.** Pakai `reviewPolicy: not_creator`.
2. **QA tidak boleh meloloskan tugas tanpa bukti** berupa perintah yang dijalankan + outputnya.
3. **PM tidak boleh mengubah angka bisnis** — hanya Owner.
4. Kalau dua agent berbeda pendapat: PM memutuskan. Kalau menyangkut uang, keamanan, atau lingkup → **eskalasi ke Owner**.

## 4. Model per peran

| Peran | Model | Alasan |
|---|---|---|
| PM, QA, agent lain | `free` | Tercepat (1,4 detik), cukup untuk analisis & verifikasi |
| Eksekutor kode | `code` | Tercepat untuk kode (1,7 detik), terbukti menjalankan PHP |

Cadangan kalau `code` penuh: `cx/gpt-5.6-terra` atau `cbai/kimi-k2.7`. Verifikator cadangan: `cbai/glm-5.1`.

## 5. Serah-terima wajib 12 field

Task ID · pengirim · penerima · tujuan · konteks · input/referensi · dependensi · deliverable · acceptance criteria · risiko · pertanyaan/asumsi · status.

Tanpa 12 field ini, serah-terima dianggap tidak sah dan PM wajib mengembalikan.
