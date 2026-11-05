# Retail Inventory Platform

> **Proyek portfolio.** Repo ini dikerjakan seolah-olah untuk klien (ada PRD, arsitektur, task per milestone,
> dan aturan kerja), tetapi tidak ada klien, NDA, atau data bisnis nyata di baliknya. Semua data adalah sampel.

Laravel 13 · Livewire 4 · Alpine · Bootstrap 5.3 (Vite + Sass) · PostgreSQL 18 · Redis.
Dokumen acuan ada di `docs/`; aturan kerja di `CLAUDE.md`, `WORKFLOW.md`, dan `MODEL-POLICY.md`.

## Setup lokal

Prasyarat: Docker.

```bash
make setup      # build, install, migrate
```

Aplikasi: http://localhost:8000 · Mailpit: http://localhost:8025

| Perintah | Fungsi |
| -------- | ------ |
| `make test` | Pest (PostgreSQL asli) |
| `make lint` | Pint |
| `make stan` | Larastan level 6 |
| `make up` / `make down` | Jalankan / hentikan container |

## Tanpa Docker

PHP ≥ 8.5, Composer, Node, PostgreSQL 18 dan Redis lokal. Salin `.env.example` ke `.env`,
atur `DB_*`, lalu `composer install && php artisan key:generate && php artisan migrate && npm install && npm run build`.
Test memakai database `retail_inventory_test` (lihat `phpunit.xml`; user/password bisa di-override lewat environment).

## Struktur

Modular monolith: kode domain di `app/Domain/{Catalog,Inventory,Pos,Reconciliation,Reporting,Identity}`
(lihat `docs/Architecture.md` §2). Jangan menaruh secret di repo.
