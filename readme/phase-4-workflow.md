# PHASE 4 — DESAIN ALUR KERJA OPERASIONAL

Cakupan: keanggotaan & poin Smart Store (Sahih Bodyfeed).
Basis: status & fitur Paperclip yang sudah diverifikasi dari source code, bukan asumsi.

## 1. Status yang benar-benar tersedia di Paperclip

| Item | Nilai | Label |
|---|---|---|
| Status issue | `backlog`, `todo`, `in_progress`, `in_review`, `done`, `blocked`, `cancelled` (7) | VERIFIED (`packages/shared/src/constants.ts:190`) |
| Kebijakan review | `anyone`, `not_creator`, `human_only` | VERIFIED (`constants.ts`, `routes/issues.ts:6315`) |
| Label issue | array string, maks 20 label, tiap label maks 48 karakter | VERIFIED (`validators/issue.ts:1270`) |
| Mode kerja | `standard`, `ask`, `planning`, `skill_test` | VERIFIED (`constants.ts:207`) |
| Dokumen issue | endpoint `/issues/:id/documents` → 200 | VERIFIED |
| Activity log | endpoint `/issues/:id/activity` → 200 | VERIFIED |

Konsekuensi: 7 status tidak cukup untuk 12 state direktif. **Solusi: status = posisi papan, label `state:*` = state workflow.** Tidak perlu custom development.

## 2. Peta 12 state → Paperclip

| State direktif | Status Paperclip | Label | Penanggung jawab | Siapa boleh mengubah |
|---|---|---|---|---|
| INTAKE | `backlog` | `state:intake` | PM | Owner, PM |
| NEEDS_CLARIFICATION | `blocked` | `state:needs_clarification` | PM | PM (setelah jawaban Owner) |
| PLANNING | `in_progress` | `state:planning` | PM | PM |
| WAITING_FOR_OWNER_APPROVAL | `in_review` + `reviewPolicy: human_only` | `state:waiting_owner_approval` | PM | **hanya Owner** |
| READY | `todo` | `state:ready` | Agent pelaksana | PM |
| IN_PROGRESS | `in_progress` | `state:in_progress` | Agent pelaksana | Agent pelaksana, PM |
| BLOCKED | `blocked` | `state:blocked` | Agent pelaksana | Agent pelaksana, PM |
| IN_REVIEW | `in_review` + `reviewPolicy: not_creator` | `state:in_review` | Reviewer (QA/agent lain) | Reviewer |
| CHANGES_REQUESTED | `todo` | `state:changes_requested` | Agent pelaksana | Reviewer |
| VERIFIED | `in_review` | `state:verified` | PM (konsolidasi) | PM |
| COMPLETED | `done` | `state:completed` | — | PM |
| FAILED | `cancelled` | `state:failed` | PM | PM |

Catatan: Paperclip tidak punya status `failed`. Dipetakan ke `cancelled` + label `state:failed` + komentar alasan. Ini adaptasi, bukan fitur bawaan (VERIFIED negatif).

## 3. Syarat pindah state (kondisi pasti)

| Transisi | Syarat masuk | Bukti yang wajib ada |
|---|---|---|
| INTAKE → PLANNING | Permintaan jelas, ada tujuan & batas | Permintaan tertulis di deskripsi issue |
| INTAKE → NEEDS_CLARIFICATION | Ada pertanyaan yang jawabannya hanya Owner | Daftar pertanyaan + alasan kenapa memblokir |
| NEEDS_CLARIFICATION → PLANNING | Semua pertanyaan terjawab | Jawaban Owner tercatat sebagai komentar |
| PLANNING → WAITING_FOR_OWNER_APPROVAL | Rencana kerja + daftar agent + acceptance criteria lengkap | Dokumen rencana di `/documents` atau komentar |
| PLANNING → READY | Pekerjaan rutin di dalam requirement yang sudah disetujui | Rujukan ke persetujuan sebelumnya |
| WAITING_FOR_OWNER_APPROVAL → READY | Owner menyatakan setuju | Komentar persetujuan eksplisit dari Owner |
| WAITING_FOR_OWNER_APPROVAL → NEEDS_CLARIFICATION | Owner minta revisi rencana | Komentar revisi |
| READY → IN_PROGRESS | Agent sudah mulai, dependensi selesai | Komentar "mulai" + daftar dependensi yang sudah beres |
| IN_PROGRESS → IN_REVIEW | Deliverable jadi + self-check | Daftar file yang diubah, perintah yang dijalankan, outputnya, hasil uji |
| IN_PROGRESS → BLOCKED | Ada penghalang di luar kendali agent | Penjelasan penghalang + apa yang sudah dicoba |
| BLOCKED → READY / IN_PROGRESS | Penghalang hilang | Bukti penghalang sudah teratasi |
| IN_REVIEW → VERIFIED | Semua acceptance criteria terbukti terpenuhi | Hasil uji reviewer, bukan klaim penulis |
| IN_REVIEW → CHANGES_REQUESTED | Ada kriteria belum terpenuhi | Daftar temuan per kriteria |
| CHANGES_REQUESTED → IN_PROGRESS | Agent mulai perbaikan | Komentar "mulai perbaikan" |
| VERIFIED → COMPLETED | PM sudah merekonsiliasi + tidak ada sisa temuan | Laporan konsolidasi PM |
| state apa pun → FAILED | Pekerjaan tidak mungkin diselesaikan atau dibatalkan | Alasan + dampak + apa yang sudah dicoba |

**Aturan mati:** tugas **tidak boleh** jadi `done` hanya karena agent sudah menulis teks atau kode. Wajib ada acceptance criteria terpenuhi + bukti. Ini sesuai direktif Phase 4 dan sudah diuji: run SAH-3 & SAH-4 berhasil karena agent benar-benar menjalankan perintah dan melaporkan hasilnya.

## 4. Siklus 15 langkah → siapa melakukan apa

| # | Langkah direktif | Pelaku | State |
|---|---|---|---|
| 1 | Owner mengajukan permintaan | Owner | INTAKE |
| 2 | PM mengklasifikasi permintaan | PM | INTAKE |
| 3 | PM mengumpulkan konteks + skill yang disetujui | PM | PLANNING |
| 4 | PM menentukan specialist yang perlu | PM | PLANNING |
| 5 | PM memecah jadi work item terbatas | PM | PLANNING |
| 6 | PM menetapkan dependensi | PM | PLANNING |
| 7 | Agent memeriksa kondisi proyek sebelum mengubah | Agent pelaksana | IN_PROGRESS |
| 8 | Agent mengerjakan tugas yang disetujui | Agent pelaksana | IN_PROGRESS |
| 9 | Agent terkait me-review & menguji | Reviewer | IN_REVIEW |
| 10 | Security & QA verifikasi acceptance criteria | QA / security | IN_REVIEW |
| 11 | PM merekonsiliasi hasil | PM | VERIFIED |
| 12 | PM menyajikan laporan gabungan | PM | VERIFIED |
| 13 | Owner setuju / tolak / minta revisi | **Owner** | VERIFIED → COMPLETED / CHANGES_REQUESTED |
| 14 | Perubahan disetujui digabung / dirilis | Agent pelaksana | COMPLETED |
| 15 | Konteks proyek + catatan keputusan diperbarui | PM | COMPLETED |

Setiap serah-terima memakai 12 field wajib: Task ID, pengirim, penerima, tujuan, konteks, input/referensi, dependensi, deliverable, acceptance criteria, risiko, pertanyaan/asumsi, status.

## 5. Aturan memilih specialist paling kecil

| Jenis pekerjaan | Agent minimal | Alasan |
|---|---|---|
| Aturan bisnis, dokumen, analisis | PM saja | Tidak menyentuh kode |
| Teks/UI halaman | designer + implementer + QA | Tampilan perlu review visual & uji |
| Query, migrasi, perhitungan poin | implementer + QA | Uang & data → wajib uji silang |
| Login, hak akses, data pribadi | + security | Risiko kebocoran |
| Deploy, backup, konfigurasi server | + devops | Menyentuh produksi |

Saat ini yang ada baru 2 agent: `implementer` (engineer, model `code`) dan `apitod` (pm, model `free`). **designer, QA, security, devops belum dibuat.** Membuatnya butuh persetujuan (REQUIRES APPROVAL).

## 6. Pengaman agar tidak berputar-putar

| Aturan | Nilai | Alasan |
|---|---|---|
| Putaran revisi maksimum | 3 kali | Setelah itu PM eskalasi ke Owner |
| Menunggu jawaban Owner | tanpa batas waktu, tapi ditandai `state:needs_clarification` | Jangan menebak |
| Agent mengubah acceptance criteria | dilarang | Hanya Owner/PM yang boleh |
| Dua agent berbeda pendapat | PM memutuskan; kalau menyangkut uang/keamanan/lingkup → eskalasi Owner | Sesuai Step 3.5–3.6 |
| Satu issue = satu work item terbatas | wajib | Supaya bukti bisa dinilai |
| Review oleh pembuat sendiri | dilarang untuk pekerjaan berkode | Pakai `reviewPolicy: not_creator` |

## 7. Yang belum bisa dijalankan hari ini

| Hal | Status | Catatan |
|---|---|---|
| Gate persetujuan Owner | BISA | `reviewPolicy: human_only` sudah ada |
| Review bukan oleh pembuat | BISA | `not_creator` sudah ada |
| Bukti di dalam issue | BISA | komentar + `/documents` |
| Agent QA / security / designer | BELUM ADA | butuh persetujuan pembuatan |
| Notifikasi otomatis ke Owner | BELUM DIUJI | perlu diuji di Phase 6 |
| Sandbox eksekusi | TIDAK ADA | agent jalan langsung di mesin ini (risiko) |
