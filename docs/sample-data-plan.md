# Rencana Sample Data (T0.07)

| Item | Detail |
| ---- | ------ |
| Versi | 1.0 — 6 November 2026 |
| Acuan | PRD §6.1.12, §7.1 (P-01..P-06), US-1.12..US-1.19; Architecture §4 (IN-xx), C-05 |
| Dipakai oleh | T1.14 (seeder dasar), T2.12 (seeder transaksi), T3.12 (seeder penjualan), T4.10 (demo rekonsiliasi), T3.10/T5.x/T6.x (uji performa) |

## 1. Prinsip

1. **Hanya data sintetis** (C-05): nama toko, merek, supplier, dan orang fiktif; tidak ada merek atau nama riil; tidak ada data POS atau toko live.
2. **Deterministik**: seed tetap; `php artisan migrate:fresh --seed` menghasilkan data identik di setiap run.
3. **Lewat Action, bukan insert langsung**: semua perubahan stok di seeder lewat `PostStockMovements`; setelah seeding `inventory:verify` harus 0 selisih (T2.12).
4. **Dua profil** dari satu generator: `demo` (cepat, untuk demo dan test) dan `load` (sesuai asumsi NFR, untuk uji beban).
5. File contoh POS yang dibuat di sini **sintetis dan diberi label sintetis**; tidak pernah diklaim sebagai ekspor klien (lihat M0-kickoff, ISS-001).

## 2. Volume

| Entitas | Profil `demo` | Profil `load` | Catatan |
| ------- | ------------- | ------------- | ------- |
| Lokasi | 3 | 2 | `LOC01` toko utama (semua skenario), `LOC02` toko kedua (transfer, uji isolasi), `LOC03` gudang kecil (hanya `demo`) |
| Kategori | 8 (2 level) | 12 (2 level) | |
| Merek | 10 | 25 | |
| Supplier | 8 | 25 | |
| Satuan | 4 | 4 | `pcs`, `pak`, `kg` (desimal), `liter` (desimal) |
| Produk → SKU (varian) | 120 → 300 | 3.500 → **10.000** | Sama dengan asumsi PRD (P-01, P-02, P-06) |
| Barcode | 1–2 per SKU | 1–2 per SKU | Campuran EAN-13, UPC-A, Code 128 (K-03) |
| User | 1 per role × 5 role | sama | Super Admin, Store Manager, Inventory Staff, Auditor/Viewer, POS Integrator. Password dev dibaca dari konfigurasi seeder, tidak ditulis di dokumen |
| Rentang penjualan | 14 hari | **30 hari** | Berakhir pada tanggal seeding |
| Transaksi POS / hari (`LOC01`) | 200 | **5.000** | ±3 baris per transaksi |
| Total transaksi / baris POS | 2.800 / ±8.400 | 150.000 / ±450.000 | |
| Movement ledger (kira-kira) | ±12.000 | ±500.000 | Penjualan + penerimaan, transfer, retur, adjustment |

Distribusi: penjualan mengikuti pola Pareto (±20% SKU menyumbang ±80% baris) dan ada SKU slow-moving (tanpa penjualan 30 hari) untuk dashboard (US-1.20).

## 3. Skenario sengaja

ID dipakai sebagai nama dataset/test di seeder dan feature test.

### 3.1 Integrasi POS (US-1.12..US-1.14, IN-xx)

| ID | Skenario | Data sengaja | Hasil yang diharapkan | Acuan |
| -- | -------- | ------------ | --------------------- | ----- |
| POS-01 | `unmapped_sku` | Baris dengan `external_sku` yang tidak cocok barcode, SKU, maupun mapping | Masuk discrepancy `unmapped_sku`, tidak diposting; setelah dimapping, diproses ulang dan import berikutnya otomatis | US-1.14 AC1–AC2 |
| POS-02 | `duplicate` (file) | File yang sama diunggah dua kali | Upload kedua ditolak, menunjuk batch lama; 0 movement ganda | US-1.12 AC3, IN-01 |
| POS-03 | `duplicate` (transaksi) | `external_id` yang sama muncul dua kali di satu file, dan dua kali via API | Hanya satu terposting; yang kedua dihitung duplikat | US-1.13 AC2, D-02 |
| POS-04 | `negative_stock` | Penjualan 5 pcs untuk SKU dengan `on_hand` 2 | Tetap diposting (on_hand −3) dan muncul discrepancy `negative_stock` | US-1.14 AC3 |
| POS-05 | `price_mismatch` | Harga jual di POS berbeda dari `sale_price` master melebihi toleransi | Diposting, ditandai `price_mismatch`. **Toleransi (usulan 5%) belum ditetapkan di PRD; putuskan di T3.06** | PRD §6.1.5 |
| POS-06 | `future_or_closed_hours` | Satu transaksi `occurred_at` besok, satu pukul 03:00 di luar jam operasional lokasi | Ditandai discrepancy, tidak merusak ledger | PRD §6.1.5 |
| POS-07 | `sequence_gap` | Urutan `external_id` numerik 1001–1010 dengan 1005 dan 1006 hilang | Discrepancy `sequence_gap` menyebut nomor yang hilang | PRD §6.1.5 |
| POS-08 | Cutover | Transaksi dengan `occurred_at` sebelum `cutover_at` lokasi | Diabaikan dan dicatat di ringkasan batch, tidak diposting | IN-04, MG-03 |
| POS-09 | Baris rusak | Baris dengan qty bukan angka atau tanggal tak terbaca | Dihitung `failed_rows`; batch tetap selesai | US-1.12 AC2 |
| POS-10 | Retry job | Job dihentikan di tengah chunk lalu diulang | Hasil sama, 0 movement ganda, lanjut dari `last_chunk_offset` | IN-05 |
| POS-11 | Auth API | Token salah, tanpa ability `pos:write`, melebihi rate limit | 401, 403, 429 | US-1.13 AC3 |

Cakupan jenis discrepancy: `unmapped_sku` (POS-01), `duplicate` (POS-02, POS-03), `negative_stock` (POS-04), `price_mismatch` (POS-05), `future_or_closed_hours` (POS-06), `sequence_gap` (POS-07): **6 dari 6**.

### 3.2 Stock count dan rekonsiliasi (US-1.15..US-1.19)

Threshold `LOC01` (default seeder): |qty| > 5, |%| > 5, |nilai| > Rp 500.000 (M0-kickoff #7).

| ID | Skenario | Data sengaja | Hasil yang diharapkan | Acuan |
| -- | -------- | ------------ | --------------------- | ----- |
| SC-01 | Tanpa selisih | Hitung = expected | variance 0, bukan exception | US-1.18 |
| SC-02 | Tepat di batas qty | variance = 5 | **Bukan** exception | US-1.18 AC1 |
| SC-03 | Melewati batas qty | variance = 6 | Exception | US-1.18 AC1 |
| SC-04 | Melewati batas persen | SKU desimal (kg): expected 10, hitung 9,4 (6%) dengan |qty| < 5 | Exception karena persen | PRD §6.1.5 |
| SC-05 | Melewati batas nilai | variance 2 pcs × biaya Rp 300.000 = Rp 600.000 | Exception karena nilai | PRD §6.1.5 |
| SC-06 | Penjualan saat hitung | Snapshot 20, terjual 2 setelah snapshot, hitung 18 | variance 0 | US-1.17 AC1 |
| SC-07 | Selisih lebih (overage) | Hitung > expected | variance positif → `count_adjustment` + | US-1.19 AC2 |
| SC-08 | Selisih kurang (shortage) | Hitung < expected | variance negatif → `count_adjustment` − | US-1.19 AC2 |
| SC-09 | Recount | Exception diminta hitung ulang | Baris baru dibuat, baris lama tetap tersimpan | US-1.18 AC2 |
| SC-10 | Dua penghitung | Dua user menghitung SKU berbeda di sesi yang sama | Keduanya tersimpan, nama penghitung per baris | US-1.16 AC1 |
| SC-11 | Scan berulang | Barcode yang sama dipindai 3 kali | qty bertambah 3 | US-1.16 AC2 |
| SC-12 | Segregasi tugas | Pembuat sesi mencoba approve | Ditolak | US-1.19 AC1, R-02 |
| SC-13 | Posting atomik | Satu baris gagal saat posting (mis. membuat stok negatif pada dokumen manual) | Tidak ada baris yang terposting | US-1.19 AC2 |
| SC-14 | SKU tidak dihitung | Full count, ada SKU dalam cakupan tanpa hasil hitung | **Perilaku belum ditetapkan di PRD** (dianggap 0 atau tetap tanpa variance). Putuskan di M4 sebelum T4.xx | PRD §6.1.5 |

Cakupan skenario exception: qty (SC-03), persen (SC-04), nilai (SC-05), batas tepat (SC-02), recount (SC-09): semuanya punya kasus. Semua skenario dikumpulkan di satu sesi demo `LOC01` oleh T4.10.

### 3.3 Inventaris, katalog, dan keamanan

| ID | Skenario | Hasil yang diharapkan | Acuan |
| -- | -------- | --------------------- | ----- |
| INV-01 | Penerimaan 24 pcs @ Rp 5.000 ke SKU dengan saldo dan biaya awal | `on_hand`, `balance_after`, `avg_cost` benar (angka tertulis di test) | US-1.05 AC1 |
| INV-02 | Penerimaan dengan biaya berbeda | `avg_cost` dihitung ulang (weighted average) | ADR-008 |
| INV-03 | Transfer parsial `LOC01` → `LOC02` dengan selisih terima | Selisih tercatat; kedua lokasi konsisten | US-1.06 |
| INV-04 | Retur supplier tanpa alasan/supplier | Ditolak | US-1.07 |
| INV-05 | Adjustment: submit → approve oleh Store Manager; satu ditolak | Hanya yang disetujui mengubah stok | US-1.08 |
| INV-06 | Reversal movement | Movement lawan terbentuk; tautan ke reversal | US-1.10 |
| INV-07 | Dokumen manual yang membuat stok negatif | Ditolak (berbeda dengan penjualan POS) | PRD §6.1.4 #6 |
| INV-08 | Posting bersamaan pada SKU yang sama | Saldo benar, `inventory:verify` 0 selisih | US-1.11 |
| CAT-01 | SKU `discontinued` dipakai di penerimaan | Ditolak | US-1.01 AC4 |
| CAT-02 | Barcode/SKU duplikat | Ditolak oleh constraint database | US-1.01, US-1.03 |
| CAT-03 | Import produk dengan sebagian baris tidak valid | Preview menandai baris dan alasan; file gagal bisa diunduh; import ulang tidak menggandakan | US-1.02 |
| SEC-01 | User `LOC01` mengakses ID milik `LOC02` | 404; data tidak muncul di daftar, laporan, export | US-1.23, S-06 |
| SEC-02 | Matriks 5 role × aksi sensitif | Berhak bisa, tidak berhak 403 | PRD §4.3 |
| SEC-03 | Saldo awal kedua untuk lokasi yang sama | Ditolak | PRD §8, MG-01 |

## 4. Format file POS sintetis

Generik (asumsi M0-kickoff #1). Profil `demo`: 1 file harian ±600 baris; profil `load`: file 5.000 dan 10.000 baris (P-04, T3.10).

| Kolom | Contoh | Keterangan |
| ----- | ------ | ---------- |
| `trx_id` | `1001` | Dipakai sebagai `external_id` |
| `trx_time` | `2026-11-05 09:15:42` | Waktu lokal lokasi |
| `sku_or_barcode` | `8999999000123` | Barcode atau SKU |
| `qty` | `2` | Negatif untuk retur pelanggan |
| `unit_price` | `15000` | |
| `discount` | `0` | |

Semua baris diberi header komentar/nama file `SINTETIS` dan tidak memuat data riil.

## 5. Implementasi (rencana)

| Task | Isi |
| ---- | --- |
| T1.14 | Seeder lokasi, user per role, katalog (kedua profil) |
| T2.12 | Saldo awal, penerimaan, transfer, retur, adjustment lewat Action; `inventory:verify` 0 |
| T3.12 | Generator file POS sintetis + seeder penjualan, termasuk POS-01..POS-11 |
| T4.10 | Sesi stock count demo berisi SC-01..SC-14 |
| T3.10, T5.x, T6.x | Pemakaian profil `load` untuk k6 dan uji performa laporan |

Perintah: `php artisan migrate:fresh --seed` (profil `demo`); `php artisan db:seed --class=LoadProfileSeeder` (profil `load`; nama kelas indikatif, ditetapkan di T1.14).

## 6. Cek kriteria T0.07

| Kriteria | Bukti |
| -------- | ----- |
| Setiap jenis discrepancy POS dan setiap skenario exception stock count punya minimal satu kasus | Bagian 3.1 (6 dari 6 jenis) dan 3.2 (qty, persen, nilai, batas tepat, recount) |
| Volume sama dengan asumsi NFR (10.000 SKU, 5.000 trx/hari) untuk load test | Bagian 2, profil `load` |

## 7. Hal yang masih perlu diputuskan

1. Toleransi `price_mismatch` (usulan 5%): T3.06.
2. Perilaku SKU yang tidak dihitung pada full count (SC-14): sebelum T4.xx.
