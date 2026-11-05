# Dokumen Sahih Bodyfeed

Dokumentasi lengkap produk dan keputusan pengembangan. Sumber kanonik ada di `~/sahih-bodyfeed-docs` (mesin pengembangan) dan disalin ke sini sebagai bagian repo.

## Bacaan utama

| Berkas | Isi |
|---|---|
| `BUSINESS_RULES.md` | **Sumber kanonik semua angka & aturan bisnis.** Kalau bertentangan dengan dokumen lain, yang ini menang |
| `DECISIONS.md` | Catatan keputusan Owner berseri (#1–#73), lengkap dengan alasannya |
| `OPEN_QUESTIONS.md` | Hal yang belum diputuskan: tanggal tutup buku, pertanyaan hosting |
| `BUSINESS_GLOSSARY.md` | Istilah: PA, PR, PL, PIN, peringkat, kompresi, dll |
| `AGENT_ROLES.md` | Pembagian peran agen selama pengembangan |

## Fase pengembangan

| Berkas | Tahap |
|---|---|
| `phase-3.7-gap-analysis.md` | Analisis celah kebutuhan vs sistem lama |
| `phase-3.7b-model-probe.md` | Uji probe model AI |
| `phase-4-workflow.md` | Alur kerja agen |
| `phase-5-context-standards.md` | Standar konteks |
| `phase-6a-product-definition.md` | Definisi produk, use case, acceptance criteria |
| `phase-6b-design-system.md` | Sistem desain (warna, font, aturan anti-slop) |
| `phase-6c-architecture-data.md` | Arsitektur & desain data (14 tabel, ledger poin) |
| `phase-6d-implementation.md` | Implementasi data & logika |
| `phase-6e-login-dan-halaman-poin.md` | Login + halaman poin anggota |
| `phase-6e-panel-pin.md` | Panel admin PIN |
| `phase-6e-tukar-pin.md` | Halaman tukar PIN + celah yang ditemukan |

## Lainnya

- `v8-crosscheck.md` — pencocokan dokumen klien v8 vs aturan
- `skrip-wa-pertanyaan.md` — arsip pertanyaan WhatsApp ke pihak bisnis

## Catatan keamanan

Dokumen menyebut lokasi berkas kredensial (`~/.sahih-admin-credentials`, `~/.sahih-db-credentials`, mode 600) — itu **jalur berkas**, bukan isinya. Nilai kredensial tidak pernah masuk repo.
