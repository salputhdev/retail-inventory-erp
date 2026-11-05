# M0 — Kesepakatan Target NFR Fase 1 (T0.02)

Sumber: `PRD-retail-inventory.md` §7.1. Angka **tidak diubah**; kolom status dan catatan ditambahkan di sini agar dokumen kesepakatan (PRD) tidak diedit tanpa persetujuan.

Persetujuan: proyek portfolio tanpa architect klien; Salman menyetujui (berperan sebagai architect) dengan me-merge PR T0.02. Hingga di-merge, semua baris berstatus usulan.

Legenda status: **Disepakati** = target dipakai apa adanya. **Disepakati dengan catatan** = angka sama, tetapi cara pembuktian atau cakupannya dibatasi (lihat catatan).

| ID | Kategori | Target | Verifikasi | Status | Catatan |
| -- | -------- | ------ | ---------- | ------ | ------- |
| P-01 | Performa | p95 ≤ 1,0 dtk; dashboard p95 ≤ 2,0 dtk | Load test (k6) di staging | Disepakati dengan catatan | Diukur dengan k6 di staging pada sample data penuh (T0.07). |
| P-02 | Performa | p95 ≤ 500 ms | Load test endpoint pencarian | Disepakati |  |
| P-03 | Performa | p95 ≤ 2 dtk | Load test API | Disepakati |  |
| P-04 | Performa | ≤ 5 menit, memori worker ≤ 512 MB | Test dengan file sintetis | Disepakati |  |
| P-05 | Performa | ≤ 60 dtk | Feature test + pengukuran | Disepakati |  |
| P-06 | Performa | Tampil ≤ 5 dtk; laporan yang lebih besar otomatis lewat queue ≤ 10 menit | Test dengan sample data penuh | Disepakati |  |
| P-07 | Performa | Tertunda ≤ 5 menit dari transaksi terakhir | Test refresh materialized view | Disepakati |  |
| D-01 | Integritas data | 0 selisih; dicek harian oleh `inventory:verify`, gagal → alert | Scheduled job + concurrency test | Disepakati |  |
| D-02 | Integritas data | 0 movement ganda pada pengiriman ulang | Feature test | Disepakati |  |
| A-01 | Ketersediaan | ≥ 99,5% per bulan; maintenance terjadwal di luar jam operasional | Uptime monitor | Disepakati dengan catatan | Dipantau dengan uptime monitor eksternal pada `/up`; angka 99,5% adalah target produksi, bukan SLA staging. |
| A-02 | Ketersediaan | POS mengirim ulang; idempotensi menjamin tidak ada duplikat | Uji kirim ulang setelah downtime | Disepakati |  |
| B-01 | Backup & DR | ≤ 15 menit (backup harian + point-in-time recovery) | Uji restore | Disepakati dengan catatan | Berlaku penuh untuk produksi nyata. Staging portfolio: backup + uji restore dibuktikan sekali (runbook), tanpa jaminan RPO 15 menit (ADR-006). |
| B-02 | Backup & DR | ≤ 4 jam | Uji restore end-to-end | Disepakati dengan catatan | Sama dengan B-01: dibuktikan lewat uji restore di staging, bukan SLA. |
| B-03 | Backup & DR | Retensi ≥ 30 hari; uji restore sebelum go-live lalu setiap kuartal | Laporan uji restore | Disepakati dengan catatan | Sama dengan B-01; retensi 30 hari diterapkan di konfigurasi backup VPS. |
| S-01 | Keamanan | TLS 1.2+, HSTS, cookie `Secure` + `HttpOnly` + `SameSite`, CSRF aktif | Scan konfigurasi | Disepakati |  |
| S-02 | Keamanan | 0 temuan high/critical OWASP Top 10 saat rilis | OWASP ZAP + review manual | Disepakati dengan catatan | OWASP ZAP dijalankan di M6 terhadap staging. |
| S-03 | Keamanan | Password ≥ 12 karakter + cek password bocor; 2FA wajib untuk Super Admin & Store Manager; lockout setelah 5 gagal / 15 menit | Feature test | Disepakati |  |
| S-04 | Keamanan | Idle timeout 30 menit; absolute timeout 12 jam | Feature test | Disepakati |  |
| S-05 | Keamanan | 0 kerentanan high/critical dari `composer audit` & `npm audit` saat rilis | CI pipeline | Disepakati |  |
| S-06 | Keamanan | 0 kebocoran data antar lokasi di UI, API, laporan, dan export | Test akses silang (US-1.23) | Disepakati |  |
| L-01 | Audit | ≥ 1 tahun (atau sesuai kebijakan klien), tidak bisa diubah dari aplikasi | Review konfigurasi | Disepakati |  |
| K-01 | Kompatibilitas | 2 versi terbaru Chrome, Edge, Firefox, Safari | Test lintas browser (Dusk/Playwright) | Disepakati dengan catatan | Diuji dengan Pest browser (Chromium/Firefox/WebKit via Playwright); Edge setara Chromium (ADR-007). |
| K-02 | Kompatibilitas | Desktop ≥ 1280 px; layar stock count & cek stok bisa dipakai di tablet ≥ 768 px | Test responsif | Disepakati |  |
| K-03 | Kompatibilitas | Scanner USB/Bluetooth mode keyboard (HID); EAN-13, UPC-A, Code 128 | Uji perangkat | Disepakati |  |
| Q-01 | Maintainability | Coverage modul inti ≥ 80%; Larastan level ≥ 6; Pint lulus | CI pipeline | Disepakati |  |
| Q-02 | Maintainability | ERD, API docs (OpenAPI), runbook deploy & restore diperbarui setiap milestone | Review sign-off milestone | Disepakati |  |
| O-01 | Observability | Error terpusat; alert bila job gagal, antrian queue > 10 menit, atau `inventory:verify` menemukan selisih | Uji alert sebelum go-live | Disepakati dengan catatan | Error tracker: Sentry (free tier) atau setara; alert dikirim ke email developer. |
| I-01 | Lokalisasi | Simpan `timestamptz` (UTC), tampilkan sesuai zona waktu lokasi; format angka & mata uang bisa dikonfigurasi | Feature test | Disepakati |  |

Total: 28 target; 8 dengan catatan; 0 angka diubah.
