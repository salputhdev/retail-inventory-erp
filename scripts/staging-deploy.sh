#!/usr/bin/env bash
# Deploy staging lokal dari main (CD-03): build -> up -> migrate -> cache -> smoke test (CD-05).
# Berhenti bila bukan di main atau tertinggal dari origin/main, kecuali ALLOW_BRANCH=1 (hanya untuk uji).
set -euo pipefail
cd "$(dirname "$0")/.."
export PATH="/Applications/Docker.app/Contents/Resources/bin:$PATH"   # credential helper Docker Desktop (macOS)

branch="$(git rev-parse --abbrev-ref HEAD)"
if [ "${ALLOW_BRANCH:-0}" != "1" ]; then
  [ "$branch" = "main" ] || { echo "Ditolak: deploy hanya dari main (sekarang: $branch)"; exit 1; }
  git fetch -q origin main
  [ "$(git rev-parse HEAD)" = "$(git rev-parse origin/main)" ] || { echo "Ditolak: main lokal tidak sama dengan origin/main (git pull dulu)"; exit 1; }
fi
git diff --quiet HEAD -- || { echo "Ditolak: ada perubahan tracked yang belum di-commit"; exit 1; }

scripts/staging-env.sh
C="docker compose -f compose.staging.yml --env-file .env.staging"

echo "==> Build image ($(git rev-parse --short HEAD))"
$C build
echo "==> Jalankan database dan cache"
$C up -d pgsql redis
echo "==> Jalankan aplikasi"
$C up -d app web
echo "==> Migrasi (CD-03)"
for i in $(seq 1 20); do $C exec -T app php artisan migrate --force && break || sleep 3; done
echo "==> Cache konfigurasi"
$C exec -T app php artisan config:cache
$C exec -T app php artisan route:cache
$C exec -T app php artisan view:cache
echo "==> Muat ulang PHP-FPM (recreate)"
$C up -d --force-recreate app web
sleep 4
echo "==> Smoke test (CD-05)"
scripts/smoke-test.sh https://localhost:8443 http://localhost:8080
