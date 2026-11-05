# M0 — Notulen Kickoff & Validasi Pertanyaan Fase 1 (T0.01)

| Item | Detail |
| ---- | ------ |
| Tanggal | 6 November 2026 |
| Konteks | **Proyek portfolio.** Tidak ada klien, NDA, atau data bisnis nyata. Kickoff ini adalah simulasi: jawaban di bawah adalah **asumsi tertulis** yang dipilih agar konsisten dengan PRD v0.2 dan Architecture v0.1, bukan jawaban klien nyata. |
| Peserta (peran) | Developer (Salman) · Klien simulasi (pemilik toko) · Systems architect simulasi (reviewer) |
| Dokumen acuan | `PRD-retail-inventory.md` §6.1.11, §9, §13 · `Architecture.md` §10.3 (T0.01) |

## 1. Agenda

1. Tujuan dan ruang lingkup Fase 1 (inti inventaris satu toko): konfirmasi in/out of scope (PRD §6.1).
2. Validasi 10 pertanyaan Fase 1 (bagian 2).
3. Target NFR dan keputusan teknis yang perlu disepakati di T0.02.
4. Prasyarat: akun repo, hosting, contoh data (bagian 4).
5. Jadwal milestone M0–M6 dan jadwal demo (Architecture §10.1).

## 2. Jawaban 10 pertanyaan validasi (PRD §6.1.11)

Status: **Asumsi simulasi** = jawaban ditetapkan sebagai asumsi kerja; berubah hanya lewat isu `change`.

| # | Pertanyaan | Jawaban (asumsi simulasi) | Dampak | Status |
| - | ---------- | ------------------------- | ------ | ------ |
| 1 | POS apa (vendor, versi)? API resmi? | POS generik tanpa API publik; ekspor transaksi harian **CSV/XLSX**. Kolom dipetakan lewat `pos_column_mappings`. Dorongan via REST API tetap disediakan (IN-06). | T3.03: template mapping generik; konektor per vendor ditunda ke Fase 4. | Asumsi simulasi |
| 2 | Jumlah SKU dan transaksi harian? | **10.000 SKU**, **5.000 transaksi/hari**, ±3 baris/transaksi, 1 toko. | Dasar target NFR dan volume sample data (T0.07, load test). | Asumsi simulasi (sama dengan NFR PRD §7.1) |
| 3 | Atribut varian? | **Ukuran, warna, kemasan**; atribut lain bebas lewat `variant_attributes`. | Skema katalog T1.09. | Asumsi simulasi |
| 4 | Konversi satuan sejak Fase 1? | **Tidak**. Satu satuan dasar per SKU di Fase 1; konversi (karton → pcs) di Fase 2 bersama pengadaan. | Tidak ada tabel konversi di Fase 1. | Asumsi simulasi |
| 5 | Costing: weighted average atau FIFO? | **Weighted average** di Fase 1; FIFO lewat `cost_layers` di Fase 2. | ADR-008. | Asumsi simulasi |
| 6 | Batch/kedaluwarsa dimajukan dari Fase 2? | **Tidak**. Tetap Fase 2 (out of scope Fase 1, PRD §6.1). | Tidak ada perubahan scope. | Asumsi simulasi |
| 7 | Frekuensi stock count dan threshold variance? | Stock count **bulanan** penuh + count siklus mingguan. Threshold default per lokasi: **\|qty\| > 5**, **\|%\| > 5**, **\|nilai\| > Rp 500.000**; bisa diubah Store Manager. | T4.xx (exception queue); angka default di seeder. | Asumsi simulasi |
| 8 | Preferensi hosting? | **VPS** (Hostinger) untuk staging/demo; PostgreSQL dan Redis di host yang sama. Produksi nyata di luar lingkup portfolio. | ADR-006 (usulan di T0.02); T0.09. | Asumsi simulasi |
| 9 | Data apa yang dimigrasi, format apa? | **Saldo awal per SKU per lokasi** (qty + biaya) lewat **CSV**; tidak ada migrasi riwayat transaksi. Cutover timestamp per lokasi. | T2.11, PRD §8. | Asumsi simulasi |
| 10 | Perangkat input staff? | **PC** untuk admin; **tablet ≥ 768 px** dan **scanner HID (mode keyboard)** untuk penerimaan dan stock count. Tanpa aplikasi mobile native. | NFR K-02, K-03; UI responsif (Architecture §6). | Asumsi simulasi |

### Pertanyaan scoping lain (PRD §9, dan isu terbuka)

| Isu | Keputusan | Status |
| --- | --------- | ------ |
| ISS-004 Konfigurasi tim | **Solo**: M3 (POS) dikerjakan sebelum M4 (stock count), tidak paralel. | Diputuskan |
| ISS-005 Akuntansi dan EDI | Tidak relevan untuk Fase 1; wajib dijawab sebelum Fase 6. | Tetap terbuka |
| ISS-006 Mata uang dan pajak | **Satu mata uang (IDR)**; pajak tidak dihitung sistem. | Diputuskan |

## 3. Dampak ke scope

**Tidak ada perubahan scope.** Seluruh asumsi di atas sama dengan default PRD v0.2 dan Architecture v0.1, jadi PRD tidak dinaikkan versinya. Bila asumsi berubah di kemudian hari, buka isu bertipe `change` dan naikkan versi PRD.

## 4. Prasyarat dan contoh data

| Prasyarat | Status |
| --------- | ------ |
| NDA (C-01) | Tidak berlaku: proyek portfolio tanpa klien nyata. |
| Akun repo klien (C-03) | Disimulasikan dengan organisasi GitHub `salputhdev` (lihat T0.03). |
| Contoh file export POS | **Tidak ada file nyata.** Risiko: template mapping T3.03 hanya generik. Mitigasi: file sintetis dibuat di T0.07 (sample data) dan diberi label *sintetis*; tidak diklaim sebagai file klien. |

## 5. Tindak lanjut

| Tindakan | PIC | Target |
| -------- | --- | ------ |
| Kesepakatan NFR dan ADR awal (T0.02) | Developer | M0 |
| Rencana sample data (T0.07) | Developer | M0 |
| Proposal budget dan timeline (T0.08) | Developer | M0 |
| Staging di VPS (T0.09), menunggu ADR-006 | Developer | M0 |
