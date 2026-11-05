# TODO — Retail Inventory Platform

> **Pelacak progres sekaligus memori project.** File ini adalah satu-satunya tempat yang mencatat *sudah sampai mana* pekerjaan.
> Rencana (apa yang harus dikerjakan dan kriteria selesainya) ada di [`docs/Architecture.md`](docs/Architecture.md) §10; kebutuhan ada di [`docs/PRD-retail-inventory.md`](docs/PRD-retail-inventory.md).
> Terakhir diperbarui: **2026-11-05** · Rencana acuan: PRD v0.2, Architecture v0.1

---

## 1. Cara pakai (baca dulu)

**Awal sesi kerja** — baca §2 (ringkasan), §3 (handoff), §4 (siap dikerjakan), dan isu `open` di §8. Jangan memulai dari nol.

**Mengerjakan task**
1. Pilih task dari §4. Ubah statusnya ke `🔄 doing` (maksimal 3 task `doing` sekaligus).
2. Buka detailnya di `docs/Architecture.md` (cari `#### T1.04`). Baca output, dependensi, dan checklist **Selesai jika**.
3. Kerjakan di branch `task/T1.04-rbac`, PR menyebut ID task. Perubahan `TODO.md` ikut di PR yang sama.
4. Task boleh `✅ done` hanya jika **seluruh** butir "Selesai jika" terpenuhi **dan** Definition of Done global (Architecture §12) terpenuhi. Isi kolom `Kriteria` (`n/n`), `Aktual` (jam), `Selesai` (tanggal), lalu jalankan `php scripts/todo-summary.php`.
5. Task macet → `⛔ blocked`, buat isu di §8, tulis ID isu di kolom `Catatan`.

**Status task**

| Status | Arti |
| ------ | ---- |
| ⬜ todo | Belum dimulai |
| 🔄 doing | Sedang dikerjakan |
| 👀 review | PR terbuka atau menunggu review architect |
| ✅ done | Semua kriteria + DoD global terpenuhi, sudah ter-merge dan ter-deploy ke staging |
| ⛔ blocked | Tidak bisa lanjut; wajib ada isu terkait |

**Aturan**
- Jangan menghapus baris, mengganti ID, atau mengubah kolom `Est`. Estimasi baru → catat di `Catatan` dan buka isu bertipe `change`.
- Perubahan scope → isu `change`, naikkan versi PRD, catat di §11 (Log).
- Milestone ditutup lewat gate (§6): demo di staging + sign-off tertulis architect.
- Ringkasan (§2) dan daftar siap-dikerjakan (§4) dibuat otomatis oleh `scripts/todo-summary.php`. Jangan diedit tangan; edit tabel task lalu jalankan script. `php scripts/todo-summary.php --check` memeriksa sinkronisasi dan melaporkan pelanggaran aturan (task `done` dengan kriteria belum penuh, task dimulai sebelum dependensinya selesai).
- **Repo ini milik klien (C-03).** Jangan menulis tarif, nilai kontrak, kredensial, atau data klien di file ini. Catatan pribadi developer ditaruh di luar repo.

---

## 2. Ringkasan progres

<!-- progress:start -->
| Milestone | Task selesai | Jam selesai / estimasi | Jam aktual | Kemajuan (berbobot jam) | Status |
| --------- | ------------ | ---------------------- | ---------- | ----------------------- | ------ |
| M0 — Discovery & Architecture | 0/9 | 0/33 | — | ░░░░░░░░░░ 0% | 🔄 berjalan |
| M1 — Foundation & Master Data | 0/14 | 0/66 | — | ░░░░░░░░░░ 0% | ⬜ belum mulai |
| M2 — Inventory Ledger | 0/12 | 0/64 | — | ░░░░░░░░░░ 0% | ⬜ belum mulai |
| M3 — POS Integration | 0/12 | 0/57 | — | ░░░░░░░░░░ 0% | ⬜ belum mulai |
| M4 — Stock Count & Reconciliation | 0/10 | 0/50 | — | ░░░░░░░░░░ 0% | ⬜ belum mulai |
| M5 — Dashboard & Reports | 0/9 | 0/53 | — | ░░░░░░░░░░ 0% | ⬜ belum mulai |
| M6 — Hardening & Handover | 0/7 | 0/33 | — | ░░░░░░░░░░ 0% | ⬜ belum mulai |
| **Total** | **0/73** | **0/356** | **—** | **░░░░░░░░░░ 0%** | |

Status task: ⬜ 64 todo · 🔄 0 doing · 👀 9 review · ✅ 0 done · ⛔ 0 blocked  |  Kriteria terpenuhi: 22/172  |  Isu terbuka: 4 (high: 0, menunggu pihak lain: 0)
<!-- progress:end -->

---

## 3. Handoff sesi terakhir

*Tulis ulang di akhir setiap sesi kerja. Maksimal 10 baris.*

| Item | Isi |
| ---- | --- |
| Tanggal | 2026-11-06 |
| Posisi | T0.04 `review`: kerangka Laravel 13 terbaru terverifikasi lokal dan di Docker (`make setup` dari salinan bersih 3 mnt 4 dtk, `make test`, modal di browser). Pest, Pint, Larastan, build Vite hijau. |
| Berikutnya | Salman me-review dan menandatangani `docs/milestones/M0.md` §7 (gate M0). Setelah itu M1: mulai T1.01 (value object Quantity & Money), T1.02, T1.03 dari `main`, satu PR per task. |
| Perlu diingat | T0.04 dimulai sebelum T0.02/T0.03 (ISS-009), jadi `todo-summary --check` sengaja gagal sampai keduanya selesai. Versi terbaru belum disetujui architect (ADR-009). Di Mac ini `docker-credential-desktop` belum ada di PATH: tambahkan `/Applications/Docker.app/Contents/Resources/bin`. Test selalu lewat `make test` (DB `_test`). |
| Menunggu klien | Vendor POS + contoh file (ISS-001), pilihan hosting (ISS-002), sumber data migrasi (ISS-003). |

---

## 4. Siap dikerjakan

*Task `todo` yang semua dependensi task-nya sudah `done`. Dibuat otomatis.*

<!-- ready:start -->
_Tidak ada task yang siap (semua selesai atau terhalang dependensi)._
<!-- ready:end -->

---

## 5. Task Fase 1

Kolom: `Est` = estimasi jam (dari Architecture §10), `Aktual` = jam nyata, `Kriteria` = butir "Selesai jika" yang sudah terpenuhi / total, `Bergantung` = prasyarat.
Total: **73 task · 356 jam** (rentang PRD 310–400 jam). Detail tiap task: `docs/Architecture.md` §10.3–§10.9.


### M0 — Discovery & Architecture (minggu 1 · 9 task · 33 jam)

| ID | Task | Est | Aktual | Kriteria | Bergantung | Status | Selesai | Catatan |
| -- | ---- | --- | ------ | -------- | ---------- | ------ | ------- | ------- |
| T0.01 | Kickoff & validasi pertanyaan Fase 1 | 3 | — | 3/3 | NDA (C-01) | 👀 review | — | Simulasi portfolio: 10 jawaban = asumsi tertulis di `docs/milestones/M0-kickoff.md`; scope tidak berubah (PRD tetap v0.2); contoh file POS tidak ada, risiko dicatat (sintetis di T0.07) |
| T0.02 | Kesepakatan target NFR & ADR awal | 4 | — | 3/3 | T0.01 | 👀 review | — | 28 NFR disepakati (6 dengan catatan, 0 angka diubah) di `docs/milestones/M0-nfr.md`; ADR-001..009 di `docs/adr/`; versi stack tercatat di ADR-009. Persetujuan = merge PR oleh Salman (architect simulasi) |
| T0.03 | Repository di akun klien | 2 | — | 3/3 | Akun klien (C-03) | 👀 review | — | Catatan resmi di `docs/milestones/M0.md` §4: kriteria 1 (kolaborator) N/A, deviasi D4 diterima Salman; kriteria 2 dan 3 terpenuhi (proteksi `main`, template PR) |
| T0.04 | Kerangka project & environment lokal | 5 | — | 3/3 | T0.02, T0.03 | 👀 review | — | Dimulai atas perintah Salman sebelum T0.02/T0.03 (ISS-009). Bukti: `make setup` dari salinan bersih 3 mnt 4 dtk; `make test` hijau; modal lolos di browser tanpa pesan console |
| T0.05 | Pipeline CI | 4 | — | 3/3 | T0.04 | 👀 review | — | Bukti: PR uji (#3, ditutup) membuat Pint/Larastan/Pest merah; `npm audit` keluar 1 untuk paket rentan (uji lokal di salinan); artefak `coverage` terunggah; durasi CI ±40 dtk (batas 10 mnt) |
| T0.06 | Dokumen arsitektur & ERD v1 | 4 | — | 2/2 | T0.02 | 👀 review | — | ERD v1 di `docs/erd/` (50 tabel/view; 42 tabel PRD §6.1.6 tervalidasi otomatis + 4 framework + 4 tambahan yang diusulkan masuk PRD). Architecture naik ke v0.2 (diff header saja; butuh persetujuan). Tidak ada komentar architect (simulasi) |
| T0.07 | Rencana sample data | 2 | — | 2/2 | T0.01 | 👀 review | — | `docs/sample-data-plan.md`: 2 profil (demo/load 10.000 SKU, 5.000 trx/hari), 11 skenario POS (6/6 jenis discrepancy), 14 skenario stock count, 14 skenario inventaris/katalog/keamanan. 2 hal terbuka (toleransi price_mismatch, SKU tak dihitung) |
| T0.08 | Proposal budget & timeline | 3 | — | 2/2 | T0.01, T0.02 | 👀 review | — | `docs/proposal.md`: 356 jam = §10.1 (diverifikasi), kontingensi 12% (batas atas 399 jam), termin sebagai persentase. Tarif/nominal tidak dicantumkan (aturan repo, repo publik) |
| T0.09 | Staging & deploy otomatis | 6 | — | 1/3 | T0.05, ADR-006 | 👀 review | — | Staging lokal prod-like (VPS dilewati, ADR-006). `make staging-deploy`: smoke test CD-05 lulus (13 OK, 2 SKIP). Kriteria 1 dan 2 disesuaikan (deviasi D1–D3 di M0.md, ISS-013); kriteria 3 (secret) terpenuhi |

### M1 — Foundation & Master Data (minggu 2–3 · 14 task · 66 jam)

| ID | Task | Est | Aktual | Kriteria | Bergantung | Status | Selesai | Catatan |
| -- | ---- | --- | ------ | -------- | ---------- | ------ | ------- | ------- |
| T1.01 | Value object Quantity & Money | 3 | — | 0/3 | T0.04 | ⬜ todo | — |  |
| T1.02 | Lokasi & scope lokasi | 5 | — | 0/4 | T0.04 | ⬜ todo | — |  |
| T1.03 | Autentikasi | 6 | — | 0/3 | T0.04 | ⬜ todo | — |  |
| T1.04 | RBAC & segregasi tugas | 6 | — | 0/3 | T1.02 | ⬜ todo | — |  |
| T1.05 | 2FA wajib | 4 | — | 0/2 | T1.03, T1.04 | ⬜ todo | — |  |
| T1.06 | Shell UI & komponen Bootstrap | 6 | — | 0/3 | T0.04, T1.04 | ⬜ todo | — |  |
| T1.07 | Fondasi audit log | 5 | — | 0/3 | T0.04 | ⬜ todo | — |  |
| T1.08 | Administrasi user, role & lokasi | 5 | — | 0/3 | T1.04, T1.06 | ⬜ todo | — |  |
| T1.09 | Skema katalog | 5 | — | 0/3 | T0.06, T1.01 | ⬜ todo | — |  |
| T1.10 | Produk & varian | 6 | — | 0/2 | T1.06, T1.09 | ⬜ todo | — |  |
| T1.11 | Barcode & pencarian scanner | 4 | — | 0/3 | T1.10 | ⬜ todo | — |  |
| T1.12 | Import massal produk | 6 | — | 0/3 | T1.10 | ⬜ todo | — |  |
| T1.13 | Reorder point per lokasi | 2 | — | 0/2 | T1.10 | ⬜ todo | — |  |
| T1.14 | Seeder dasar | 3 | — | 0/3 | T0.07, T1.09 | ⬜ todo | — |  |

### M2 — Inventory Ledger (minggu 4–5 · 12 task · 64 jam)

| ID | Task | Est | Aktual | Kriteria | Bergantung | Status | Selesai | Catatan |
| -- | ---- | --- | ------ | -------- | ---------- | ------ | ------- | ------- |
| T2.01 | Skema ledger | 5 | — | 0/3 | T1.09 | ⬜ todo | — |  |
| T2.02 | Action posting & reversal | 8 | — | 0/4 | T2.01 | ⬜ todo | — |  |
| T2.03 | Integritas & concurrency | 6 | — | 0/3 | T2.02 | ⬜ todo | — |  |
| T2.04 | Kerangka dokumen stok | 4 | — | 0/3 | T1.04, T1.07, T2.02 | ⬜ todo | — |  |
| T2.05 | Penerimaan barang | 6 | — | 0/2 | T2.04, T1.11 | ⬜ todo | — |  |
| T2.06 | Transfer antar lokasi | 7 | — | 0/2 | T2.04 | ⬜ todo | — |  |
| T2.07 | Retur pelanggan & supplier | 4 | — | 0/1 | T2.04 | ⬜ todo | — |  |
| T2.08 | Adjustment dengan approval | 6 | — | 0/2 | T2.04 | ⬜ todo | — |  |
| T2.09 | Layar reversal | 3 | — | 0/2 | T2.02, T2.04 | ⬜ todo | — |  |
| T2.10 | Kartu stok & saldo per tanggal | 6 | — | 0/2 | T2.02 | ⬜ todo | — |  |
| T2.11 | Dokumen saldo awal | 6 | — | 0/3 | T2.04, T1.12 | ⬜ todo | — | Format data migrasi belum diketahui (ISS-003). |
| T2.12 | Seeder transaksi & demo SKU tracking | 3 | — | 0/3 | T2.05–T2.11 | ⬜ todo | — |  |

### M3 — POS Integration (minggu 6–7 · 12 task · 57 jam)

| ID | Task | Est | Aktual | Kriteria | Bergantung | Status | Selesai | Catatan |
| -- | ---- | --- | ------ | -------- | ---------- | ------ | ------- | ------- |
| T3.01 | Skema POS | 4 | — | 0/2 | T2.01 | ⬜ todo | — |  |
| T3.02 | Sumber POS & token API | 4 | — | 0/3 | T3.01, T1.04 | ⬜ todo | — |  |
| T3.03 | Template mapping kolom | 5 | — | 0/2 | T3.01 | ⬜ todo | — | Butuh contoh file POS (ISS-001). |
| T3.04 | Upload file & pembuatan batch | 4 | — | 0/2 | T3.03 | ⬜ todo | — |  |
| T3.05 | Job pemrosesan batch | 10 | — | 0/3 | T3.04, T2.02 | ⬜ todo | — |  |
| T3.06 | Detektor discrepancy | 6 | — | 0/2 | T3.05 | ⬜ todo | — |  |
| T3.07 | API push & status batch | 5 | — | 0/3 | T3.02, T3.05 | ⬜ todo | — |  |
| T3.08 | Antrian discrepancy & mapping SKU | 6 | — | 0/2 | T3.06 | ⬜ todo | — |  |
| T3.09 | Aturan cutover timestamp | 2 | — | 0/1 | T2.11, T3.05 | ⬜ todo | — |  |
| T3.10 | Uji performa POS | 4 | — | 0/2 | T3.05, T3.07 | ⬜ todo | — |  |
| T3.11 | Dokumentasi API | 3 | — | 0/2 | T3.07 | ⬜ todo | — |  |
| T3.12 | Seeder penjualan & skenario POS | 4 | — | 0/2 | T3.06, T2.12 | ⬜ todo | — |  |

### M4 — Stock Count & Reconciliation (minggu 8–9 · 10 task · 50 jam)

| ID | Task | Est | Aktual | Kriteria | Bergantung | Status | Selesai | Catatan |
| -- | ---- | --- | ------ | -------- | ---------- | ------ | ------- | ------- |
| T4.01 | Skema stock count | 4 | — | 0/2 | T2.01 | ⬜ todo | — |  |
| T4.02 | State machine sesi | 4 | — | 0/2 | T4.01, T2.04 | ⬜ todo | — |  |
| T4.03 | Pembuatan sesi & snapshot | 6 | — | 0/2 | T4.02 | ⬜ todo | — |  |
| T4.04 | Input hasil hitung | 8 | — | 0/3 | T4.03, T1.11 | ⬜ todo | — |  |
| T4.05 | Penyesuaian expected qty | 5 | — | 0/2 | T4.04, T2.10 | ⬜ todo | — |  |
| T4.06 | Variance & exception | 5 | — | 0/2 | T4.05 | ⬜ todo | — |  |
| T4.07 | Review exception | 6 | — | 0/2 | T4.06 | ⬜ todo | — |  |
| T4.08 | Approval & posting atomik | 5 | — | 0/2 | T4.07, T2.02, T1.04 | ⬜ todo | — |  |
| T4.09 | Laporan rekonsiliasi | 4 | — | 0/2 | T4.08 | ⬜ todo | — |  |
| T4.10 | Skenario demo rekonsiliasi | 3 | — | 0/2 | T4.09, T3.12 | ⬜ todo | — |  |

### M5 — Dashboard & Reports (minggu 10–11 · 9 task · 53 jam)

| ID | Task | Est | Aktual | Kriteria | Bergantung | Status | Selesai | Catatan |
| -- | ---- | --- | ------ | -------- | ---------- | ------ | ------- | ------- |
| T5.01 | Materialized view & refresh | 6 | — | 0/2 | T2.01 | ⬜ todo | — |  |
| T5.02 | Dashboard manajemen | 8 | — | 0/2 | T5.01, T1.13, T3.06, T4.06 | ⬜ todo | — |  |
| T5.03 | Grafik pergerakan stok | 4 | — | 0/2 | T5.01 | ⬜ todo | — |  |
| T5.04 | Kerangka laporan | 5 | — | 0/2 | T1.06, T1.02 | ⬜ todo | — |  |
| T5.05 | Delapan laporan Fase 1 | 12 | — | 0/2 | T5.04 | ⬜ todo | — |  |
| T5.06 | Export | 5 | — | 0/2 | T5.05 | ⬜ todo | — |  |
| T5.07 | Laporan berat via queue | 5 | — | 0/2 | T5.06 | ⬜ todo | — |  |
| T5.08 | Penampil audit log | 4 | — | 0/2 | T1.07, T5.04 | ⬜ todo | — |  |
| T5.09 | Tuning performa dashboard & laporan | 4 | — | 0/2 | T5.02, T5.05 | ⬜ todo | — |  |

### M6 — Hardening & Handover (minggu 12 · 7 task · 33 jam)

| ID | Task | Est | Aktual | Kriteria | Bergantung | Status | Selesai | Catatan |
| -- | ---- | --- | ------ | -------- | ---------- | ------ | ------- | ------- |
| T6.01 | Uji beban NFR lengkap | 6 | — | 0/1 | M5 selesai | ⬜ todo | — |  |
| T6.02 | Uji keamanan | 5 | — | 0/2 | M5 selesai | ⬜ todo | — |  |
| T6.03 | Uji browser & perangkat | 4 | — | 0/1 | M5 selesai | ⬜ todo | — |  |
| T6.04 | Production, backup & monitoring | 6 | — | 0/3 | T0.09, ADR-006 | ⬜ todo | — | Menunggu pilihan hosting (ISS-002). |
| T6.05 | Rehearsal migrasi | 4 | — | 0/2 | T2.11, T3.09 | ⬜ todo | — | Format data migrasi belum diketahui (ISS-003). |
| T6.06 | Dokumentasi teknis | 6 | — | 0/2 | T6.01–T6.05 | ⬜ todo | — |  |
| T6.07 | Serah terima & sign-off Fase 1 | 2 | — | 0/2 | T6.06 | ⬜ todo | — |  |

---

## 6. Gate milestone

Milestone selesai jika semua task `done`, demo di **staging** dengan sample data sudah dilakukan ke klien dan architect, sign-off tertulis tersimpan di `docs/milestones/M{n}.md`, dan isu severity high dari review architect sudah beres atau dijadwalkan dengan persetujuan tertulis (Architecture §10.2).

| Milestone | Semua task done | Demo staging | Sign-off architect | Isu high beres | Tanggal sign-off |
| --------- | --------------- | ------------ | ------------------ | -------------- | ---------------- |
| M0 — Discovery & Architecture | ⬜ | ⬜ | ⬜ | ⬜ | — |
| M1 — Foundation & Master Data | ⬜ | ⬜ | ⬜ | ⬜ | — |
| M2 — Inventory Ledger | ⬜ | ⬜ | ⬜ | ⬜ | — |
| M3 — POS Integration | ⬜ | ⬜ | ⬜ | ⬜ | — |
| M4 — Stock Count & Reconciliation | ⬜ | ⬜ | ⬜ | ⬜ | — |
| M5 — Dashboard & Reports | ⬜ | ⬜ | ⬜ | ⬜ | — |
| M6 — Hardening & Handover | ⬜ | ⬜ | ⬜ | ⬜ | — |

### Deliverable awal klien (PRD §2.2)

| # | Deliverable | Milestone | Task kunci | Status | Catatan |
| - | ----------- | --------- | ---------- | ------ | ------- |
| 1 | Proposed software development architecture | M0 | T0.02, T0.06 | 🔄 doing | Draf Architecture v0.1 sudah ada; belum direview architect |
| 2 | Detailed development plan & milestone schedule | M0 | T0.08 | 🔄 doing | Draf rencana ada di Architecture §10; jadwal final menunggu T0.01 |
| 3 | Functional inventory database prototype | M1 | T1.09, T1.14 | ⬜ todo | |
| 4 | Demo SKU tracking & inventory reconciliation | M2 + M4 | T2.12, T4.10 | ⬜ todo | |
| 5 | Preliminary management dashboard | M5 | T5.02 | ⬜ todo | |
| 6 | Technical documentation & source-code handover | M6 | T6.06, T6.07 | ⬜ todo | |
| 7 | Development budget & implementation timeline | M0 | T0.08 | ⬜ todo | |

---

## 7. Fase 2–7 (paket kerja)

Belum dimulai. Setiap fase diawali sesi perencanaan (±1 minggu) yang memecah paket kerja jadi task bergaya §5 dan menambah tabelnya di file ini. Syarat masuk dan kriteria selesai tiap WP: `docs/Architecture.md` §11.

| Fase | Estimasi | Status | Syarat masuk |
| ---- | -------- | ------ | ------------ |
| Fase 2 — Procurement, Supplier & Perishables | 280–360 jam | ⬜ belum mulai | Fase 1 sign-off; jawaban scoping PRD §9 relevan |
| Fase 3 — Multi-location, Warehouse & Mobile | 380–480 jam | ⬜ belum mulai | Fase 2 sign-off |
| Fase 4 — Pricing, Promotion & Deep POS | 220–300 jam | ⬜ belum mulai | Fase 3 sign-off; vendor POS dikonfirmasi (ISS-001); otorisasi tertulis C-05 untuk koneksi POS live |
| Fase 5 — Analytics, Forecasting & Loss Prevention | 380–500 jam | ⬜ belum mulai | Fase 4 sign-off; ±8–12 minggu data live |
| Fase 6 — Omnichannel & Financial/EDI | 380–500 jam | ⬜ belum mulai | Fase 5 sign-off; software akuntansi & kebutuhan EDI diketahui (ISS-005) |
| Fase 7 — Enterprise Scale & Advanced Tech | 300–450 jam | ⬜ belum mulai | Fase 6 sign-off; item dipilih sesuai kebutuhan klien |

| WP | Paket kerja | Fase | Status |
| -- | ----------- | ---- | ------ |
| WP2.1 | Supplier management & scorecard (FR2.1) | Fase 2 | ⬜ todo |
| WP2.2 | Purchase Order & approval bertingkat (FR2.2) | Fase 2 | ⬜ todo |
| WP2.3 | Penerimaan vs PO, three-way match, RTV (FR2.3) | Fase 2 | ⬜ todo |
| WP2.4 | Auto-reorder berbasis aturan (FR2.4) | Fase 2 | ⬜ todo |
| WP2.5 | Batch, expiry & FEFO (FR2.5) | Fase 2 | ⬜ todo |
| WP2.6 | Costing lanjutan & konversi satuan (FR2.6) | Fase 2 | ⬜ todo |
| WP2.7 | Hardening & sign-off fase | Fase 2 | ⬜ todo |
| WP3.1 | Hierarki lokasi & onboarding toko (FR3.1) | Fase 3 | ⬜ todo |
| WP3.2 | Gudang, bin, putaway, picking (FR3.2) | Fase 3 | ⬜ todo |
| WP3.3 | Replenishment DC → toko & alokasi (FR3.2) | Fase 3 | ⬜ todo |
| WP3.4 | PWA scanning offline (FR3.3) | Fase 3 | ⬜ todo |
| WP3.5 | Cycle count terjadwal berbasis ABC (FR3.4) | Fase 3 | ⬜ todo |
| WP3.6 | Label & barcode (FR3.5) | Fase 3 | ⬜ todo |
| WP3.7 | Uji skala & sign-off | Fase 3 | ⬜ todo |
| WP4.1 | Price book & penjadwalan harga (FR4.1) | Fase 4 | ⬜ todo |
| WP4.2 | Promosi & markdown (FR4.2) | Fase 4 | ⬜ todo |
| WP4.3 | Label rak (FR4.3) | Fase 4 | ⬜ todo |
| WP4.4 | Kontrak konektor, outbox & simulator | Fase 4 | ⬜ todo |
| WP4.5 | Konektor vendor POS klien (FR4.4) | Fase 4 | ⬜ todo |
| WP4.6 | Event POS berisiko (FR4.5) | Fase 4 | ⬜ todo |
| WP5.1 | Skema reporting & ETL | Fase 5 | ⬜ todo |
| WP5.2 | KPI retail (FR5.1) | Fase 5 | ⬜ todo |
| WP5.3 | Analisis ABC/XYZ & aging (FR5.2) | Fase 5 | ⬜ todo |
| WP5.4 | Forecast service (FR5.3) | Fase 5 | ⬜ todo |
| WP5.5 | Replenishment berbasis forecast (FR5.4) | Fase 5 | ⬜ todo |
| WP5.6 | Loss prevention (FR5.5) | Fase 5 | ⬜ todo |
| WP5.7 | Report builder & konektor BI (FR5.6) | Fase 5 | ⬜ todo |
| WP6.1 | ATP, reservasi & sinkron kanal (FR6.1) | Fase 6 | ⬜ todo |
| WP6.2 | BOPIS, ship-from-store & order routing | Fase 6 | ⬜ todo |
| WP6.3 | Integrasi akuntansi & period close (FR6.2) | Fase 6 | ⬜ todo |
| WP6.4 | EDI & portal supplier (FR6.3, FR6.4) | Fase 6 | ⬜ todo |
| WP6.5 | API publik, OAuth2, webhook, SSO (FR6.5) | Fase 6 | ⬜ todo |
| WP7.1 | Multi-tenant / franchise (FR7.1) | Fase 7 | ⬜ todo |
| WP7.2 | RFID (FR7.2) | Fase 7 | ⬜ todo |
| WP7.3 | IoT & cold chain (FR7.3) | Fase 7 | ⬜ todo |
| WP7.4 | AI features (FR7.4) | Fase 7 | ⬜ todo |
| WP7.5 | ESL (FR7.5) | Fase 7 | ⬜ todo |
| WP7.6 | Skala & keandalan (FR7.6) | Fase 7 | ⬜ todo |
| WP7.7 | Compliance & governance (FR7.7) | Fase 7 | ⬜ todo |

---

## 8. Issue tracker

Jenis: `bug` · `question` (butuh jawaban klien/architect) · `risk` · `change` (perubahan scope/estimasi/rencana) · `debt`.
Severity: `high` (menghambat milestone) · `med` · `low`. Status: `open` · `doing` · `waiting` (menunggu pihak lain) · `closed`.
ID berurutan (ISS-001, ISS-002, …), tidak dipakai ulang. Isu yang selesai diubah ke `closed` dan diberi tanggal, bukan dihapus.

| ID | Jenis | Judul | Sev | Status | Terkait | PIC | Dibuka | Ditutup | Catatan |
| -- | ----- | ----- | --- | ------ | ------- | --- | ------ | ------- | ------- |
| ISS-001 | question | Vendor/versi POS belum diketahui dan belum ada contoh file export | high | closed | T0.01, T0.07, T3.03 | Klien | 2026-11-05 | 2026-11-06 | Diputuskan sebagai asumsi simulasi: POS generik, ekspor CSV/XLSX (M0-kickoff.md #1) |
| ISS-002 | question | Pilihan hosting belum ada (ADR-006) | high | closed | T0.09, T6.04 | Klien | 2026-11-05 | 2026-11-06 | Diputuskan sebagai asumsi simulasi: VPS Hostinger untuk staging (M0-kickoff.md #8); ADR-006 di T0.02 |
| ISS-003 | question | Sumber dan format data migrasi belum diketahui | med | closed | T2.11, T6.05 | Klien | 2026-11-05 | 2026-11-06 | Diputuskan sebagai asumsi simulasi: hanya saldo awal CSV, tanpa riwayat transaksi (M0-kickoff.md #9) |
| ISS-004 | question | Konfigurasi tim: solo/kecil atau paralel | low | closed | M3, M4 | Developer | 2026-11-05 | 2026-11-06 | Diputuskan: solo, M3 sebelum M4 (M0-kickoff.md) |
| ISS-005 | question | Software akuntansi dan kebutuhan EDI klien belum diketahui | low | open | WP6.3, WP6.4 | Klien | 2026-11-05 | — | PRD §13.5. Tidak menghambat Fase 1; wajib dijawab sebelum Fase 6 |
| ISS-006 | question | Konfirmasi asumsi: satu mata uang, pajak tidak dihitung di sistem | low | closed | T1.01, PRD §6.1 | Klien | 2026-11-05 | 2026-11-06 | Diputuskan: satu mata uang IDR, pajak tidak dihitung (M0-kickoff.md) |
| ISS-007 | change | Bagian PRD "Penting" belum ditulis: metrik sukses produk, rencana UAT/rilis, daftar layar, notifikasi, glosarium | low | open | PRD | Developer | 2026-11-05 | — | Ditawarkan saat review PRD; tunggu keputusan |
| ISS-008 | debt | Catatan paralel M3/M4 di Architecture §10.1 tidak cocok dengan dependensi T4.10 → T3.12 | low | closed | T4.10 | Developer | 2026-11-05 | 2026-11-05 | Catatan §10.1 diperbaiki: T4.10 dikerjakan paling akhir di M4 |
| ISS-009 | risk | T0.04 dimulai sebelum T0.02 (ADR-009 penguncian versi) dan T0.03 (repo klien) selesai; stack dipasang di versi terbaru atas perintah Salman | med | open | T0.04, T0.02, T0.03 | Developer | 2026-11-06 | — | Versi terpasang: Laravel 13.40, Livewire 4.4, Pest 5.3, Larastan 3.14, Bootstrap 5.3.8, Vite 8.3, PHP 8.5, PostgreSQL 18. Harus disetujui architect di ADR-009; bila ditolak, turunkan versi |
| ISS-013 | change | T0.09 dilakukan sebagai staging lokal, bukan server: kriteria deploy otomatis ke server dan SSL Labs ≥ A disesuaikan (D1–D3) | med | open | T0.09, ADR-006 | Salman | 2026-11-06 | — | Diputuskan Salman (VPS dilewati: host situs live, 1 vCPU). Terima deviasi saat sign-off M0; hosting produksi nyata = ADR baru |
| ISS-010 | debt | Docker lokal dan modal Bootstrap di browser belum diuji | med | closed | T0.04 | Developer | 2026-11-06 | 2026-11-06 | `make setup` dari salinan bersih berhasil; modal buka/tutup lolos sebelum dan sesudah re-render Livewire, console bersih. Temuan yang diperbaiki: PECL redis gagal di PHP 8.5, `artisan serve` membuang env Compose, port 5432 bentrok, test sempat menyentuh DB dev (kini dijaga `TestCase` + `make test`) |
| ISS-011 | risk | Repo di akun GitHub pribadi (salputh/retail-inventory-platform), bukan organisasi klien | low | closed | T0.03, C-03 | Developer | 2026-11-06 | 2026-11-06 | Ditutup: ini proyek portfolio tanpa klien nyata; repo dipublikasikan setelah dipindai (tanpa secret/data klien). T0.03 tetap `todo` sebagai simulasi proses |
| ISS-012 | bug | GitHub Actions tidak bisa berjalan di akun salputh: billing akun terkunci | high | closed | T0.05 | Salman | 2026-11-06 | 2026-11-06 | Diselesaikan dengan memindahkan repo ke organisasi salputhdev (billing terpisah): Actions berjalan. Repo kini salputhdev/retail-inventory-erp |

### Pertanyaan validasi Fase 1 (PRD §6.1.11)

Dijawab di T0.01. Jawaban yang mengubah scope → isu `change` + versi PRD naik.

| # | Pertanyaan | Memengaruhi | Status | Jawaban | Tanggal |
| - | ---------- | ----------- | ------ | ------- | ------- |
| 1 | POS apa yang dipakai (vendor & versi)? Ada API resmi? | T0.07, T3.03, T3.07 | ⬜ open | | |
| 2 | Jumlah SKU dan rata-rata transaksi harian saat ini? | T0.07, T6.01 | ⬜ open | | |
| 3 | Atribut varian yang dipakai (ukuran, warna, kemasan, …)? | T1.09, T1.10 | ⬜ open | | |
| 4 | Perlu konversi satuan (mis. karton → pcs) sejak Fase 1? | Scope Fase 1, WP2.6 | ⬜ open | | |
| 5 | Metode costing: weighted average atau FIFO? | ADR-008, T2.02 | ⬜ open | | |
| 6 | Pelacakan batch/kedaluwarsa dimajukan dari Fase 2? | Scope Fase 1, WP2.5 | ⬜ open | | |
| 7 | Frekuensi stock count dan threshold variance yang wajar? | T4.06, T4.07 | ⬜ open | | |
| 8 | Preferensi hosting (AWS, GCP, Azure, VPS)? | ADR-006, T0.09 | ⬜ open | | |
| 9 | Data apa yang dimigrasi dari sistem lama, format apa? | T2.11, T6.05 | ⬜ open | | |
| 10 | Perangkat input staff (PC, tablet, barcode scanner)? | T1.06, T1.11 | ⬜ open | | |

Risiko proyek yang sudah dikenali ada di PRD §11; risiko baru dicatat sebagai isu bertipe `risk` di atas.

---

## 9. Keputusan (ADR)

Teks lengkap di `docs/adr/` (dibuat di T0.02). Status di bawah disalin dari Architecture §9; perbarui saat berubah.

| ADR | Keputusan | Status | Tanggal diputuskan |
| --- | --------- | ------ | ------------------ |
| ADR-001 | Modular monolith Laravel, bukan microservice | Disetujui (simulasi; berlaku saat PR T0.02 di-merge) | 2026-11-06 |
| ADR-002 | Ledger immutable + `stock_levels` sebagai cache | Disetujui (simulasi; berlaku saat PR T0.02 di-merge) | 2026-11-06 |
| ADR-003 | Scope lokasi: pivot `location_user` + global scope + Policy (bukan fitur *teams* Spatie) | Disetujui (simulasi; berlaku saat PR T0.02 di-merge) | 2026-11-06 |
| ADR-004 | UI Livewire + Bootstrap; aturan widget JS Bootstrap (§6) | Disetujui (PRD v0.2) | 2026-11-05 (PRD v0.2) |
| ADR-005 | Redis + Horizon untuk queue, cache, session | Disetujui (simulasi; berlaku saat PR T0.02 di-merge) | 2026-11-06 |
| ADR-006 | Hosting, backup & PITR (managed PostgreSQL vs VPS + WAL-G/pgBackRest) | Usulan: VPS Hostinger; keputusan akhir di T0.09 (tenggat awal M1) | — |
| ADR-007 | Alat browser testing (Pest browser/Playwright vs Dusk) | Disetujui (simulasi; berlaku saat PR T0.02 di-merge) | 2026-11-06 |
| ADR-008 | Costing weighted average di Fase 1; FIFO lewat `cost_layers` di Fase 2 | Disetujui (simulasi; berlaku saat PR T0.02 di-merge) | 2026-11-06 |
| ADR-009 | Versi Laravel, PHP, Livewire, Bootstrap, PostgreSQL dikunci saat M0 | Disetujui (simulasi; berlaku saat PR T0.02 di-merge) | 2026-11-06 |
| ADR-010 | Outbox pattern untuk sinkron keluar (Fase 4+) | Ditunda ke Fase 4 | — |
| ADR-011 | Kontrak forecast service (Fase 5) | Ditunda ke Fase 5 | — |
| ADR-012 | Strategi multi-tenant (Fase 7) | Ditunda ke Fase 7 | — |

---

## 10. Konteks tetap (memori project)

Hal yang berlaku sepanjang project dan tidak boleh dilupakan antar sesi.

| Topik | Isi |
| ----- | --- |
| Produk | Platform manajemen inventaris retail, dibangun bertahap 7 fase; sekarang Fase 1 (±12 minggu, 356 jam) |
| Stack | Laravel (PHP 8.3+) · Livewire · Alpine.js · Bootstrap (Sass via Vite) · PostgreSQL 16+ · Redis + Horizon · Pest · Larastan · Pint. Python/FastAPI hanya di Fase 5 |
| Arsitektur | Modular monolith: `app/Domain/{Catalog,Inventory,Pos,Reconciliation,Reporting,Identity}` |
| Inti data | Ledger `stock_movements` append-only; `stock_levels` hanya cache; stok berubah **hanya** lewat `PostStockMovement(s)` |
| Kontrak | Rahasia & kepemilikan di klien (C-01–C-04); demo hanya pakai sample data, tidak boleh menyentuh toko/POS live tanpa otorisasi tertulis (C-05); architect independen dari klien menandatangani tiap milestone (C-06) |
| Dokumen | `docs/PRD-retail-inventory.md` (kebutuhan) · `docs/Architecture.md` (rencana & aturan AR/IN/CD) · `docs/adr/` · `docs/erd/` · `docs/milestones/` · file ini (progres) |
| Konvensi ID | `T{m}.{nn}` task Fase 1 · `WP{f}.{n}` paket kerja Fase 2–7 · `US-1.xx` user story · `ISS-nnn` isu · `ADR-nnn` keputusan · `C-0x` batasan kontrak · `AR/IN/CD-0x` aturan arsitektur |
| Model & verifikasi | Klasifikasi kesulitan (Easy/Medium/Hard) dan risiko (R1–R4) per task; aturan di `MODEL-POLICY.md`. Hasilnya dicatat di §12 |
| Perintah | `php artisan test` · `./vendor/bin/pint` · `./vendor/bin/phpstan analyse` · `php artisan inventory:verify` · `php scripts/todo-summary.php` |

---

## 11. Log perubahan

Satu baris per kejadian penting: task selesai, keputusan, perubahan scope, jawaban klien. Terbaru di atas.

| Tanggal | Kejadian | Terkait |
| ------- | -------- | ------- |
| 2026-11-06 | T0.09 (staging lokal) terverifikasi: smoke test lulus, modal OK di HTTPS+CSP; T0.03 catatan resmi (deviasi D4); `docs/milestones/M0.md` siap sign-off | T0.09, T0.03, ISS-013 |
| 2026-11-06 | T0.08: proposal budget & timeline (jam per milestone, termin %, asumsi, di luar scope) tanpa tarif | T0.08 |
| 2026-11-06 | T0.07: rencana sample data (2 profil, 39 skenario); 2 keputusan ditunda ke T3.06 dan M4 | T0.07 |
| 2026-11-06 | T0.06: ERD v1 dibuat (50 tabel/view, diagram Mermaid per kelompok); Architecture v0.1 → v0.2 | T0.06 |
| 2026-11-06 | T0.02: 28 target NFR disepakati (tanpa perubahan angka), ADR-001..009 ditulis (ADR-006 usulan VPS, keputusan di T0.09) | T0.02, ADR-001..009 |
| 2026-11-06 | T0.01 (simulasi): 10 pertanyaan validasi dijawab sebagai asumsi tertulis; ISS-001/002/003/004/006 ditutup; scope tidak berubah | T0.01 |
| 2026-11-06 | Proteksi branch `main` di salputhdev/retail-inventory-erp: wajib 5 check CI + 1 review, branch harus up-to-date, force-push/hapus ditolak | T0.03, CD-01 |
| 2026-11-06 | T0.05 terbukti: CI hijau di kode bersih (±40 dtk), merah untuk pelanggaran Pint/Larastan/test, coverage jadi artefak; naik ke `review`. Perbaikan di CI: tests/Unit ditambahkan, Vite dimatikan di test | T0.05 |
| 2026-11-06 | Repo dipindah ke organisasi salputhdev/retail-inventory-erp; Actions berjalan (ISS-012 closed). CI pertama: Pest gagal karena folder tests/Unit tidak ada | T0.05, ISS-012 |
| 2026-11-06 | T0.05: workflow CI ditulis; semua run GitHub Actions `startup_failure` (ISS-012), task `blocked` | T0.05, ISS-012 |
| 2026-11-06 | Repo sementara dibuat di akun pribadi; PR #1 (T0.04) dibuka. Pekerjaan berikutnya menunggu PR #1 di-merge | ISS-011, T0.04 |
| 2026-11-06 | T0.04 diverifikasi di Docker dan browser; naik ke `review` menunggu keputusan Salman | T0.04, ISS-010 |
| 2026-11-06 | T0.04 dimulai: skeleton Laravel 13 + Livewire 4 + Bootstrap (Sass) + Pest + Larastan, struktur `app/Domain/*`, Docker compose, Makefile, halaman contoh | T0.04, ISS-009, ISS-010 |
| 2026-11-06 | `MODEL-POLICY.md` dibuat: router model + effort menurut kesulitan, verifikasi menurut risiko, batas eskalasi, pengukuran (§12) | — |
| 2026-11-05 | `TODO.md` dibuat dari 73 task Architecture v0.1; total 356 jam diverifikasi ulang dari seluruh task | — |
| 2026-11-05 | Architecture v0.1 selesai (arsitektur §1–9, rencana Fase 1, WP Fase 2–7, DoD global) | ADR-001–012 |
| 2026-11-05 | PRD v0.2: gap kritis ditambahkan, UI diganti ke Livewire + Bootstrap, user story Fase 1 lengkap (25 US) | ADR-004 |

---

## 12. Log model

Satu baris per task yang selesai (atau dihentikan). Dipakai untuk mengukur apakah aturan di `MODEL-POLICY.md` efektif (§10 di sana); jangan diisi dengan angka yang tidak diketahui.

Kolom: `Risiko` R1–R4 · `Awal`/`Akhir` tingkat model (`easy`, `medium`, `hard`, `hard+` untuk xhigh) · `Percobaan` jumlah percobaan sampai lulus · `Lulus` (`ya`/`tidak`) verifikasi sesuai risiko · `Manusia` (`ya`/`tidak`) butuh bantuan Salman di luar persetujuan rutin · `Catatan` pemakaian kuota bila dicek lewat `/usage`.

<!-- modellog:start -->
| Task | Risiko | Awal | Akhir | Percobaan | Lulus | Manusia | Catatan |
| ---- | ------ | ---- | ----- | --------- | ----- | ------- | ------- |
<!-- modellog:end -->
