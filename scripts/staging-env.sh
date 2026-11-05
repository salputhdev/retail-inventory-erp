#!/usr/bin/env bash
# Membuat .env.staging (di-ignore git) dengan secret acak, dan sertifikat TLS self-signed lokal.
# Secret tidak pernah masuk repo (C-04); idempoten: file yang sudah ada tidak ditimpa.
set -euo pipefail
cd "$(dirname "$0")/.."

if [ ! -f .env.staging ]; then
  cp .env.staging.example .env.staging
  key="base64:$(openssl rand -base64 32)"
  pass="$(openssl rand -hex 24)"
  sed -i.bak "s|^APP_KEY=.*|APP_KEY=${key}|; s|^DB_PASSWORD=.*|DB_PASSWORD=${pass}|" .env.staging
  rm -f .env.staging.bak
  chmod 600 .env.staging
  echo "Dibuat: .env.staging (secret acak, izin 600)"
else
  echo "Ada: .env.staging (tidak ditimpa)"
fi

mkdir -p docker/nginx/certs
if [ ! -f docker/nginx/certs/staging.crt ]; then
  openssl req -x509 -newkey rsa:2048 -nodes -days 365 \
    -keyout docker/nginx/certs/staging.key -out docker/nginx/certs/staging.crt \
    -subj "/CN=localhost" -addext "subjectAltName=DNS:localhost,IP:127.0.0.1" >/dev/null 2>&1
  chmod 600 docker/nginx/certs/staging.key
  echo "Dibuat: sertifikat self-signed (localhost, 365 hari)"
else
  echo "Ada: sertifikat TLS (tidak ditimpa)"
fi
