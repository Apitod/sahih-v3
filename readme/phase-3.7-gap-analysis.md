# Phase 3.7 — Gap Analysis Paperclip vs Kebutuhan Orkestrasi Smart Store

Status: **DRAFT untuk persetujuan Razan**
Basis bukti: instance Paperclip lokal (port 3100, PGlite `~/.paperclip`), company `SAH`, 3 run nyata.
Label: VERIFIED = diuji langsung dan berhasil/gagal terbukti; INFERRED = ada di kode/endpoint tapi belum diuji alur penuh; NOT VERIFIED = belum diuji sama sekali.

## A. Matriks 5 kolom

| # | Requirement | Paperclip Support | Evidence | Gap | Recommended Action |
|---|---|---|---|---|---|
| 1 | Orkestrasi tim multi-agent dengan peran berbeda | Ya — role enum `ceo,cto,cmo,cfo,security,engineer,designer,pm,qa,devops,researcher,general` | VERIFIED: `POST /companies/:id/agents` membuat `implementer` (engineer) & `apitod` (pm) | Tidak ada role khusus "business analyst"; harus pakai `pm`/`researcher` | Pakai `pm` untuk aturan bisnis, `engineer` untuk kode. Tidak perlu kustom. |
| 2 | Penjadwalan agent otomatis (heartbeat) | Ya — heartbeat engine + wake on assign | VERIFIED: 3 run terbentuk otomatis setelah issue di-assign (11:39, 11:41, 11:43 UTC) | Interval & pemicu harus dipahami; wake tidak jalan bila status issue `blocked` | Biarkan default; dokumentasikan aturan "issue harus `todo` agar agent bangun". |
| 3 | Pemberian tugas & handoff antar agent | Ya — issue + komentar + assignee | VERIFIED: agent membaca `heartbeat-context` (200), menulis komentar, `PATCH` issue | Handoff 12-field belum dipaksakan otomatis | Validator handoff belum dibangun (sesuai keputusan: tunggu terbukti perlu). |
| 4 | Agent membaca konteks tugas | Ya — `GET /issues/:id/heartbeat-context` | VERIFIED: muncul di log run sukses | — | — |
| 5 | Penutupan tugas otomatis | Ya — agent mengubah status sendiri | VERIFIED: SAH-3 → `done`, SAH-4 → `done` tanpa campur tangan manusia | — | — |
| 6 | Identitas & otorisasi agent | Ya — JWT lokal per run | VERIFIED: `PAPERCLIP_AGENT_JWT_SECRET` wajib; tanpa itu run mati (`runtime connection tools could not be delivered`). Dengan secret: `/agents/me` 200, `PATCH` issue 200 | Secret **tidak punya default** dan hanya `WARN` (tidak gagal keras) → agent tampak jalan tapi buta | Dokumentasikan sebagai prasyarat wajib; pertimbangkan startup check yang gagal keras. |
| 7 | Kontrol biaya per agent | Ya — budget policy + hard stop | VERIFIED: `unpricedUsagePolicy: block` → `hard_stop`; `allow` → `ok` | Model gateway tanpa harga → semua usage `unpriced`, token tercatat 0 | `allow` untuk agent Hermes; isi tabel harga bila ingin angka biaya nyata. |
| 8 | Pemilihan model per peran | Ya — `adapterConfig.model` per agent | VERIFIED: `auto` (default) **ditolak** provider custom → `adapter_failed`; `free` & `code` berhasil | Default `auto` tidak kompatibel dengan gateway 9-router | Set eksplisit: `code` untuk eksekutor kode, `free` untuk PM/agent lain. |
| 9 | Skill / prosedur kerja | Ya untuk adapter lokal (`supportsSkills: true`) | VERIFIED (katalog): `hermes_local`, `claude_local`, `codex_local`, `gemini_local`, `kimi_local`, `opencode_local`, `pi_local`, `grok_local` = true; `hermes_gateway`, `http`, `process` = false | Skill belum benar-benar dimuat agent dalam uji | Uji 1 issue yang mewajibkan pemakaian skill sebelum produksi. |
| 10 | Instruction bundle per agent | Ya — `AGENTS.md` terkelola | VERIFIED: `instructionsFilePath` ada di `adapterConfig`, mode `managed` | — | Isi `AGENTS.md` per peran saat Phase 5. |
| 11 | Review & approval berlapis | Endpoint ada | INFERRED: `GET /companies/:id/approvals` → 200; alur 3 lapis belum dijalankan | Belum diuji end-to-end | Uji setelah ada 2 agent aktif (pemberi & pemeriksa). |
| 12 | Audit trail / activity log | Ya | VERIFIED: `GET /issues/:id/activity` → 200; log server mencatat tiap `PATCH`/`GET` agent | — | — |
| 13 | Dokumen / work product per issue | Ya | VERIFIED: `GET /issues/:id/documents` → 200 | — | Simpan PRD & laporan sebagai dokumen issue di Phase 5. |
| 14 | Rutinitas terjadwal (cron) | Ya | INFERRED: `GET /companies/:id/routines` → 200; belum ada routine dibuat | Belum diuji | Buat 1 routine uji (mis. laporan harian) sebelum dipakai. |
| 15 | Persistensi data | Ya — PGlite embedded | VERIFIED: data bertahan setelah server restart (company & agent utuh, 410 migrasi). **Tapi** `/api/health` melaporkan `databaseBackup.status: warning`, `database_backup_missing` — folder backup kosong, `latestBackup: null` | Tidak ada backup otomatis; bila `~/.paperclip` rusak, seluruh riwayat tugas hilang | Aktifkan backup (atau salin `~/.paperclip` berkala) sebelum dipakai serius. Jangan andalkan retensi bawaan. |
| 16 | Isolasi antar perusahaan | Ya — semua route ber-scope `companyId` | INFERRED: struktur route & auth ber-scope; belum diuji 2 company | Belum diuji | Uji 1 company kedua bila ingin yakin. |
| 17 | Adapter gateway (agent remote) | Ada tapi tidak layak pakai di setup ini | VERIFIED GAGAL: `hermes_gateway` → `harness_test_failed` berulang tiap menit; `supportsLocalAgentJwt: false`, `supportsSkills: false` | Tidak lolos verifikasi harness; tidak bisa baca identitas sendiri | **Tinggalkan.** Pakai `hermes_local` untuk semua agent. |
| 18 | Sandbox eksekusi kode | Tidak untuk adapter yang dipakai | VERIFIED: `hermes_local` `supportsSandbox` tidak true; agent menjalankan PHP langsung di host | Eksekusi kode = host, tanpa isolasi | Untuk proyek ini dapat diterima (lokal, satu mesin); jangan jalankan agent di server produksi. |
| 19 | Dukungan stack Laravel | Sebagian | VERIFIED: PHP 8.3.6, Node 26, npm, git ADA; **composer, MySQL/MariaDB, sqlite3 TIDAK ADA** | Composer & MySQL belum ada → `composer install`/migrasi belum bisa | Pasang composer + MySQL lokal sebelum Phase 5 (butuh persetujuan). |
| 20 | Dashboard pemantauan | Ya | VERIFIED: `GET /companies/:id/dashboard` → 200; UI di port 3100 | `GET /companies/:id/costs` → 404 (path biaya berbeda) | Pakai UI, bukan endpoint costs. |

## B. Verdict keseluruhan

**SUFFICIENT WITH CONFIGURATION** — untuk peran *control plane* (mengatur siapa mengerjakan apa, mencatat hasil, menjaga jejak audit), Paperclip sudah cukup dan sudah terbukti bekerja end-to-end di mesin ini.

Syarat konfigurasi yang wajib dipenuhi (semua sudah diuji):

1. `PAPERCLIP_AGENT_JWT_SECRET` diset saat server start (dari file `chmod 600`).
2. Budget policy per agent: `unpricedUsagePolicy: allow`.
3. `adapterConfig.model` diset eksplisit — `code` untuk eksekutor, `free` untuk lainnya.
4. Semua agent memakai `hermes_local` (bukan `hermes_gateway`).
5. Issue harus berstatus `todo` saat di-assign agar heartbeat membangunkan agent.

Yang **tidak** perlu dibangun sendiri: manajer tugas kustom, penjadwal kustom, audit log kustom, penyimpanan dokumen kustom.

Yang **masih perlu** (bukan bagian Paperclip): composer + MySQL lokal, dan keputusan model per peran.

## C. Rekomendasi lanjut

1. Setujui verdict di atas → Phase 3 ditutup.
2. Ganti `apitod` yang macet (loop `harness_test_failed`) dengan agent PM baru ber-`hermes_local` + `free`.
3. Phase 4–8 tetap sesuai rencana; Paperclip dipakai sebagai papan kerja, bukan bagian aplikasi yang dikirim ke hosting.
