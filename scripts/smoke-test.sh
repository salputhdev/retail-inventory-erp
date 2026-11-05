#!/usr/bin/env bash
# Smoke test staging (CD-05, Architecture §7.2). Pemakaian: scripts/smoke-test.sh [https://host:port] [http://host:port]
# Pemeriksaan yang butuh fitur yang belum ada (login, dashboard, Horizon) ditandai SKIP dan wajib diaktifkan di M1/M3.
set -uo pipefail
HTTPS="${1:-https://localhost:8443}"
HTTP="${2:-http://localhost:8080}"
fail=0
ok()   { printf '  \033[32mOK\033[0m    %s\n' "$1"; }
bad()  { printf '  \033[31mGAGAL\033[0m %s\n' "$1"; fail=1; }
skip() { printf '  \033[33mSKIP\033[0m  %s\n' "$1"; }
check() { if eval "$2"; then ok "$1"; else bad "$1"; fi; }

hdr="$(curl -sk -D - -o /dev/null --max-time 15 "$HTTPS/")"
body="$(curl -sk --max-time 15 "$HTTPS/")"

echo "Smoke test: $HTTPS"
check "GET /up = 200 (health check)"            '[ "$(curl -sk -o /dev/null -w "%{http_code}" --max-time 15 "$HTTPS/up")" = 200 ]'
check "GET / = 200 dan memuat halaman aplikasi" 'echo "$hdr" | head -1 | grep -q " 200" && echo "$body" | grep -q "Retail Inventory Platform"'
check "HTTP dialihkan ke HTTPS (301)"           '[ "$(curl -s -o /dev/null -w "%{http_code}" --max-time 15 "$HTTP/")" = 301 ]'
check "HSTS aktif"                              'echo "$hdr" | grep -qi "^strict-transport-security: max-age=[0-9]\{7,\}"'
check "X-Content-Type-Options: nosniff"         'echo "$hdr" | grep -qi "^x-content-type-options: nosniff"'
check "X-Frame-Options ada"                     'echo "$hdr" | grep -qi "^x-frame-options:"'
check "Content-Security-Policy ada"             'echo "$hdr" | grep -qi "^content-security-policy:"'
check "Versi server/PHP tidak dibocorkan"       '! echo "$hdr" | grep -qiE "^(x-powered-by:|server: .*[0-9])"'
check "Debug mati (tanpa stack trace di 404)"   '! curl -sk --max-time 15 "$HTTPS/tidak-ada-halaman" | grep -qiE "stack trace|vendor/laravel|Whoops"'
check "Dotfile tidak bisa diunduh (/.env)"      '[ "$(curl -sk -o /dev/null -w "%{http_code}" --max-time 15 "$HTTPS/.env")" != 200 ]'

host="${HTTPS#https://}"
check "TLS 1.2+ diterima"                       'echo | openssl s_client -connect "$host" -tls1_2 2>/dev/null | grep -q "BEGIN CERTIFICATE"'
check "TLS 1.0/1.1 ditolak"                     '! echo | openssl s_client -connect "$host" -tls1_1 2>/dev/null | grep -q "BEGIN CERTIFICATE"'

cookies="$(curl -sk -D - -o /dev/null --max-time 15 "$HTTPS/" | grep -i "^set-cookie:" || true)"
if [ -n "$cookies" ]; then
  check "Cookie: Secure + HttpOnly + SameSite"  'echo "$cookies" | grep -qi "secure" && echo "$cookies" | grep -qi "httponly" && echo "$cookies" | grep -qi "samesite"'
else
  skip "Cookie sesi belum diterbitkan di halaman awal (cek lagi setelah login ada, M1)"
fi
skip "Login dan dashboard (M1/M5)"
skip "Horizon aktif (M3)"

echo
if [ "$fail" -eq 0 ]; then echo "Smoke test LULUS"; else echo "Smoke test GAGAL"; fi
exit "$fail"
