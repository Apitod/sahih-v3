# PHASE 5 — KONTEKS PROYEK & STANDAR TEKNIS

Prinsip: **jangan buat semua dokumen**. Buat yang benar-benar dipakai, satu sumber kanonik per jenis informasi.

## 1. Inventaris: apa yang sudah ada

| Artefak | Ada? | Lokasi | Isi |
|---|---|---|---|
| Dokumen klien | ADA | `Sistem-dan-Fitur-Smart-Store-v8.docx` (20 tabel) | Cakupan keanggotaan & poin, bahasa awam |
| PRD | ADA (draf) | `PRD-Smart-Store-Sahih-Bodyfeed-v0.1.docx` | Versi teknis awal |
| Alur kerja | ADA | `phase-4-workflow.md` | 12 state, syarat transisi, bukti |
| Analisis kapabilitas | ADA | `phase-3.7-gap-analysis.md` | Matriks 20 requirement |
| Uji model | ADA | `phase-3.7b-model-probe.md` | 75 → 36 model |
| Daftar pertanyaan ke hosting | ADA | `skrip-wa-pertanyaan.md` | 5 pertanyaan Rumahweb |
| Papan tugas | ADA | Paperclip issues | SAH-1..SAH-5 |

## 2. Inventaris: yang belum ada, dan putusannya

| Artefak | Putusan | Alasan |
|---|---|---|
| `BUSINESS_RULES.md` | **BUAT SEKARANG** | Angka uang tersebar di docx + chat. Wajib satu sumber kanonik |
| `DECISIONS.md` | **BUAT SEKARANG** | Keputusan (harga, bonus, techstack) hanya ada di riwayat chat — hilang kalau sesi hilang |
| `OPEN_QUESTIONS.md` | **BUAT SEKARANG** | Ada ~11 hal yang masih UNKNOWN; tanpa daftar ini agent akan menebak |
| `AGENT_ROLES.md` | **BUAT SEKARANG** | Data sudah lengkap: peran, model, batas wewenang |
| `BUSINESS_GLOSSARY.md` | **BUAT SEKARANG** | Istilah poin/PL/RO/RM/PIN dipakai di semua dokumen; salah tafsir = salah hitung |
| `TEST_STRATEGY.md` | BUAT di Phase 6 | QA baru dibuat; butuh daftar kriteria dulu |
| `SECURITY_POLICY.md` | BUAT sebelum kode produksi | Menyangkut data pribadi member & PIN |
| `DATA_MODEL.md` | BUAT di Phase 6 | Butuh BUSINESS_RULES final dulu |
| `ARCHITECTURE.md` | BUAT di Phase 6 | Techstack sudah diputuskan, tapi detail belum |
| `REQUIREMENTS.md` | Gabung ke PRD | PRD v0.1 sudah jadi cangkangnya — jangan bikin file kedua |
| `PROJECT_BRIEF.md` | **TIDAK PERLU** | Ringkasannya sudah jadi bagian awal PRD |
| `DESIGN_SYSTEM.md` | TUNDA | Butuh referensi brand dari Owner (belum ada) |
| `API_CONTRACTS.md` | TUNDA | Belum ada endpoint yang disepakati |
| `DEPLOYMENT_PLAN.md` | TUNDA | Terblokir jawaban Rumahweb (PHP, MySQL, SSH, cron, Redis) |
| `CHANGELOG.md` | TUNDA | Belum ada rilis |
| `TASK_BOARD` | **TIDAK PERLU file** | Sudah ada di Paperclip |

Ringkas: **5 dokumen dibuat sekarang**, 4 menyusul di Phase 6, 4 ditunda, 2 tidak perlu.

## 3. Aturan sumber kanonik

| Jenis informasi | Sumber kanonik | Turunan (tidak boleh jadi acuan) |
|---|---|---|
| Angka uang, persen, syarat kualifikasi | `BUSINESS_RULES.md` | docx klien, PRD, slide, chat |
| Keputusan yang sudah diambil | `DECISIONS.md` | ringkasan chat |
| Hal yang belum diketahui | `OPEN_QUESTIONS.md` | asumsi agent |
| Peran agent & batas wewenang | `AGENT_ROLES.md` | konfigurasi Paperclip (config bisa berubah) |
| Definisi istilah | `BUSINESS_GLOSSARY.md` | — |
| Alur kerja & state | `phase-4-workflow.md` | — |

**Aturan ubah angka:** kalau ada perubahan, ubah `BUSINESS_RULES.md` **dulu**, baru regenerate docx klien. Tidak boleh sebaliknya. Kalau docx dan BUSINESS_RULES berbeda, yang menang BUSINESS_RULES sampai docx diperbarui.

## 4. Kondisi teknis hari ini

| Hal | Nilai | Label |
|---|---|---|
| Framework | Laravel + Blade + Livewire | VERIFIED (keputusan Owner) |
| Database | MySQL | VERIFIED (keputusan Owner) |
| Hosting | Rumahweb (shared) | VERIFIED (keputusan Owner) |
| PHP di mesin ini | 8.3.6 | VERIFIED (`php -v`) |
| Composer | TIDAK ADA | VERIFIED |
| MySQL/MariaDB lokal | TIDAK ADA | VERIFIED |
| Node / npm / git | 26 / ada / ada | VERIFIED |
| Kontrol versi proyek | **belum ada repo** | VERIFIED |
| Sandbox eksekusi agent | TIDAK ADA | VERIFIED |
| Backup database Paperclip | TIDAK ADA | VERIFIED (`/api/health` warning) |

Catatan: mesin ini bukan tempat produksi. Pengembangan lokal butuh Composer + MySQL dulu, atau langsung di subdomain hosting (tergantung jawaban Rumahweb).

## 5. 11 hal arsitektur yang wajib dijawab sebelum kode

Ini daftar periksa, bukan izin membangun.

| # | Hal | Kondisi |
|---|---|---|
| 1 | Autentikasi & otorisasi member | **UNKNOWN** — login pakai email, nomor HP, atau username? |
| 2 | Relasi member–sponsor | SEBAGIAN — sponsor mendaftarkan; belum diputuskan apakah 1 sponsor selamanya |
| 3 | Pembelian paket | VERIFIED — 3 paket (1/3/6 poin) |
| 4 | Upgrade akun | VERIFIED — tanpa turun peringkat; selisih dihitung |
| 5 | Repeat order | **UNKNOWN** — apakah RO menambah poin peringkat atau hanya poin transaksi? |
| 6 | Catatan transaksi poin | **UNKNOWN** — butuh skema; belum ada keputusan pembulatan/desimal |
| 7 | Bonus sponsor | VERIFIED — selisih peringkat, dibayar harian |
| 8 | Bonus leadership | VERIFIED — Rp40.000/poin, bulanan, dari Manager langsung di bawah |
| 9 | Penukaran reward | **UNKNOWN** — minimum & metode pencairan belum diputuskan |
| 10 | Audit log | **UNKNOWN** — apa saja yang wajib dicatat belum ditentukan |
| 11 | Alur admin | **UNKNOWN** — peran admin belum ditetapkan |

## 6. Yang harus diputuskan Owner sebelum Phase 6 bisa jalan penuh

Dari daftar di atas, 6 hal memblokir desain database:
1. Login member pakai apa.
2. RO menambah poin peringkat atau tidak.
3. Pembulatan poin (bulat, 2 desimal, atau pecahan).
4. Pencairan reward: minimum & metode.
5. Peran admin & apa yang boleh dilakukan.
6. Pajak atas bonus (dipotong atau tidak).

Tanpa 6 jawaban ini, `DATA_MODEL.md` hanya bisa dibuat setengah.
