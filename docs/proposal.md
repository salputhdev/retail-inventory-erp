# Proposal Budget & Timeline — Fase 1 (T0.08)

| Item | Detail |
| ---- | ------ |
| Versi | 1.0 — 6 November 2026 |
| Status | Usulan. Dianggap disetujui saat PR T0.08 di-merge oleh Salman (peran klien/architect simulasi). |
| Acuan | PRD §2.2 (deliverable #7), §5, §6.1.8; Architecture §10.1 |
| Konteks | **Proyek portfolio** tanpa klien nyata. Proposal ini mensimulasikan dokumen yang akan diserahkan ke klien. **Tarif dan nilai uang sengaja tidak dicantumkan** (aturan repo: tanpa tarif, nilai kontrak, atau data klien; repo ini publik). Anggaran dinyatakan dalam jam kerja efektif. |

## 1. Ringkasan

Fase 1 membangun inti inventaris untuk satu toko: katalog SKU, ledger stok, integrasi POS (file dan API), stock count dan rekonsiliasi, serta dashboard dasar.

| Ukuran | Nilai |
| ------ | ----- |
| Estimasi dasar | **356 jam** (73 task) |
| Rentang PRD | 310–400 jam |
| Cadangan (kontingensi) | 12% di atas estimasi dasar, ±43 jam. **Batas atas 399 jam**, di dalam rentang PRD |
| Durasi | **12 minggu** untuk satu developer (±30 jam efektif per minggu) |
| Konfigurasi tim | Solo: M3 dikerjakan sebelum M4, tidak paralel (ISS-004) |

## 2. Estimasi per milestone

Angka sama dengan Architecture §10.1 dan `TODO.md`.

| Milestone | Minggu | Task | Jam | Deliverable klien (PRD §2.2) | Porsi termin |
| --------- | ------ | ---- | --- | ---------------------------- | ------------ |
| M0 — Discovery & Architecture | 1 | T0.01–T0.09 | 33 | #1, #2, #7 | 9% |
| M1 — Foundation & Master Data | 2–3 | T1.01–T1.14 | 66 | #3 | 19% |
| M2 — Inventory Ledger | 4–5 | T2.01–T2.12 | 64 | #4 (bagian 1) | 18% |
| M3 — POS Integration | 6–7 | T3.01–T3.12 | 57 | — | 16% |
| M4 — Stock Count & Reconciliation | 8–9 | T4.01–T4.10 | 50 | #4 (bagian 2) | 14% |
| M5 — Dashboard & Reports | 10–11 | T5.01–T5.09 | 53 | #5 | 15% |
| M6 — Hardening & Handover | 12 | T6.01–T6.07 | 33 | #6 | 9% |
| **Total** | **12** | **73** | **356** | | **100%** |

Verifikasi konsistensi: 33 + 66 + 64 + 57 + 50 + 53 + 33 = 356 jam.

## 3. Termin pembayaran (struktur, tanpa nominal)

- Pembayaran per milestone setelah **demo dengan sample data dan sign-off architect** (PRD C-05, C-06). Porsi di tabel §2 proporsional terhadap jam milestone.
- Perubahan scope yang menambah jam diproses sebagai isu `change` dan disetujui tertulis sebelum dikerjakan; tidak ditagihkan diam-diam.
- Pemakaian kontingensi (§1) hanya dengan persetujuan tertulis.

## 4. Jadwal dan gate

| Minggu | Aktivitas utama | Gate (sign-off) |
| ------ | --------------- | --------------- |
| 1 | Kickoff, NFR, ADR, ERD, CI, staging | M0: arsitektur + rencana disetujui |
| 2–3 | Auth, RBAC, lokasi, katalog, seeder | M1: prototype database |
| 4–5 | Ledger, dokumen stok, saldo awal | M2: demo SKU tracking |
| 6–7 | Import POS, API, discrepancy | M3 (tanpa deliverable klien) |
| 8–9 | Stock count, variance, approval | M4: demo rekonsiliasi |
| 10–11 | Dashboard, laporan, export | M5: dashboard awal |
| 12 | Uji NFR, keamanan, dokumentasi, serah terima | M6: handover |

Gate milestone dan bukti: Architecture §10.2.

## 5. Asumsi

1. Fase 1 mencakup satu lokasi operasional (struktur siap multi-lokasi), ≤ 10.000 SKU aktif, ≤ 5.000 transaksi POS per hari, ≤ 30 user bersamaan (PRD §6.1.10).
2. Data POS berupa CSV/XLSX atau push API; tidak ada konektor vendor (Fase 4).
3. Costing weighted average, satu mata uang (IDR), pajak tidak dihitung sistem.
4. Repo, hosting, dan domain disediakan klien (C-03); kredensial tidak masuk repo (C-04).
5. Seluruh demo memakai sample data (C-05); koneksi ke POS atau toko live butuh otorisasi tertulis terpisah.
6. Klien/architect tersedia untuk review dan sign-off dalam 3 hari kerja setelah demo; keterlambatan menggeser jadwal.
7. Versi stack dikunci di ADR-009 (PHP 8.5, Laravel 13, Livewire 4, PostgreSQL 18).
8. Jawaban 10 pertanyaan validasi adalah asumsi di `milestones/M0-kickoff.md`; perubahan jawaban diproses sebagai `change`.

## 6. Di luar scope Fase 1

- Pengadaan/PO, batch dan kedaluwarsa, konversi satuan, FIFO (Fase 2).
- Multi-toko terpusat, PWA scanning, realtime (Fase 3).
- Konektor POS per vendor, harga terpusat, sinkron keluar (Fase 4).
- Forecasting, analitik lanjut (Fase 5).
- Integrasi akuntansi/EDI, API publik OAuth2 (Fase 6).
- Skala besar, HA, multi-tenant (Fase 7).
- Aplikasi mobile native; migrasi riwayat transaksi (hanya saldo awal); operasi produksi 24/7 dan SLA.
- Koneksi ke toko atau POS live.

## 7. Risiko yang memengaruhi budget

| Risiko | Dampak | Mitigasi |
| ------ | ------ | -------- |
| Contoh file POS tidak tersedia (ISS-001) | Template mapping hanya generik | File sintetis (T0.07); mapping disesuaikan saat file nyata ada |
| Format ekspor POS rumit (multi-baris, encoding) | Tambah jam di M3 | Kontingensi §1; opsi push API |
| Concurrency/locking ledger sulit | Tambah jam di M2 | Test paralel sejak T2.03; tingkat Hard + review |
| Keterlambatan sign-off | Jadwal bergeser | Asumsi #6; milestone berikut tidak menunggu bila tidak bergantung |
| Versi stack sangat baru (ISS-009) | Bug/ekosistem belum matang | Lockfile, CI penuh, ADR-009 |

## 8. Revisi

| Versi | Tanggal | Perubahan |
| ----- | ------- | --------- |
| 1.0 | 6 November 2026 | Versi awal |
