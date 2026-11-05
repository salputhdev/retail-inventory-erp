# Runbook — Staging Lokal Prod-like (T0.09)

Staging dijalankan lokal di Docker (image produksi, Nginx + PHP-FPM, TLS). Keputusan: VPS dilewati (ADR-006, pembaruan 6 November 2026).

## Prasyarat
Docker Desktop berjalan. Pada macOS, helper kredensial Docker harus ada di PATH (skrip menambahkannya otomatis: `/Applications/Docker.app/Contents/Resources/bin`).

## Perintah

| Perintah | Fungsi |
| -------- | ------ |
| `make staging-env` | Membuat `.env.staging` (secret acak, izin 600) dan sertifikat self-signed. Tidak menimpa yang sudah ada. |
| `make staging-deploy` | Deploy dari `main` (CD-03): build, up, migrasi, cache, recreate, smoke test. Menolak bila bukan di `main`, tertinggal dari `origin/main`, atau ada perubahan tracked belum di-commit. |
| `make staging-smoke` | Smoke test saja (CD-05). |
| `make staging-watch` | Deploy otomatis: memantau `origin/main` tiap 60 detik dan men-deploy commit baru. |
| `make staging-logs` / `make staging-down` | Log / hentikan (data tetap di volume `pgdata`). |

Alamat: https://localhost:8443 (HTTP :8080 dialihkan ke HTTPS). Browser akan memperingatkan sertifikat self-signed; itu wajar untuk lokal.

## Smoke test (CD-05)
Memeriksa `/up`, halaman utama, redirect HTTPS, HSTS, header keamanan (nosniff, X-Frame-Options, CSP), tidak ada kebocoran versi atau stack trace, `/.env` tidak terunduh, TLS 1.2+ saja, dan flag cookie. Login, dashboard, dan Horizon ditandai SKIP dan **wajib diaktifkan** saat fitur itu ada (M1/M3).

## Secret (C-04)
`.env.staging` dan `docker/nginx/certs/` di-ignore git. Contoh tanpa secret: `.env.staging.example`. Di server nyata, secret hanya ada di environment server.

## Rollback
Checkout commit sebelumnya di `main`, lalu `make staging-deploy` dengan `ALLOW_BRANCH=1` bila perlu. Migrasi memakai pola expand → migrate → contract (CD-02), jadi kode lama tetap kompatibel dengan skema baru.

## Backup dan restore (uji manual)
```bash
# backup
docker compose -f compose.staging.yml --env-file .env.staging exec -T pgsql sh -c 'pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB"' > backup.sql
# restore ke database kosong
docker compose -f compose.staging.yml --env-file .env.staging exec -T pgsql sh -c 'psql -U "$POSTGRES_USER" "$POSTGRES_DB"' < backup.sql
```
Backup dan uji restore yang diwajibkan B-01..B-03 hanya berlaku penuh di produksi nyata.

## Batasan dan deviasi dari Architecture §10.3 (T0.09)
| Kriteria asli | Kenyataan di staging lokal |
| ------------- | -------------------------- |
| Merge ke `main` ter-deploy otomatis ke server staging | Deploy otomatis lewat `make staging-watch` (polling) di mesin lokal; belum diuji end-to-end dengan merge nyata. Tidak ada CD GitHub Actions ke server. |
| Skor SSL Labs minimal A | Tidak dapat diukur (host lokal, sertifikat self-signed). Konfigurasi setara: TLS 1.2/1.3, HSTS, tanpa protokol lama. |
| Secret hanya di environment server | Terpenuhi: hanya di `.env.staging` yang di-ignore git. |
| Server di akun klien | Tidak berlaku (portfolio). |
