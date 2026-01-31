#!/usr/bin/env bash
set -euo pipefail

# Usage:
#   ./deploy.sh you@example.com lvl.zchk.ru

EMAIL="${1:-}"
DOMAIN="${2:-}"

if [[ -z "$EMAIL" || -z "$DOMAIN" ]]; then
  echo "Usage: $0 <email> <domain>"
  exit 1
fi

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker not found. Install Docker first."
  exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
  echo "docker compose not found. Install Docker Compose plugin first."
  exit 1
fi

# Папки и файлы для certbot/nginx
mkdir -p letsencrypt certbot/www docker/nginx

# Стартуем nginx в HTTP-режиме (ACME challenge)
if [[ ! -f docker/nginx/http.conf ]]; then
  echo "Missing docker/nginx/http.conf"
  exit 1
fi
if [[ ! -f docker/nginx/https.conf ]]; then
  echo "Missing docker/nginx/https.conf"
  exit 1
fi

# 1) HTTP mode for ACME challenge
cp -f docker/nginx/http.conf docker/nginx/default.conf
echo "[1/6] Starting containers in HTTP mode..."
docker compose up -d

# 2) Issue certificate if absent
if [[ ! -f "letsencrypt/live/$DOMAIN/fullchain.pem" ]]; then
  echo "[2/6] Issuing Let's Encrypt certificate for $DOMAIN..."
  docker compose run --rm certbot certonly \
    --webroot -w /var/www/certbot \
    -d "$DOMAIN" \
    --email "$EMAIL" \
    --agree-tos --no-eff-email
else
  echo "[2/6] Certificate already exists for $DOMAIN (skipping issue)."
fi

# 3) Switch to HTTPS config
echo "[3/6] Switching Nginx to HTTPS config..."
cp -f docker/nginx/https.conf docker/nginx/default.conf
docker compose restart nginx

# 4) Prepare SQLite file
echo "[4/6] Preparing SQLite database file..."
docker compose exec -T app sh -lc \
  "mkdir -p database && test -f database/database.sqlite || touch database/database.sqlite"

# 5) Generate APP_KEY only if it's empty
echo "[5/6] Ensuring APP_KEY exists (only if empty)..."
docker compose exec -T app sh -lc '
  # 1) If .env exists and has non-empty APP_KEY -> skip
  if [ -f .env ]; then
    KEY="$(grep -E "^APP_KEY=" .env | head -n1 | cut -d= -f2- || true)"
    if [ -n "$KEY" ] && [ "$KEY" != "null" ]; then
      echo "APP_KEY already set in .env (skipping)."
      exit 0
    fi
  fi

  # 2) Otherwise try reading from env (container env var)
  if [ -n "${APP_KEY:-}" ] && [ "${APP_KEY:-}" != "null" ]; then
    echo "APP_KEY already set via container environment (skipping)."
    exit 0
  fi

  # 3) If empty -> generate
  echo "APP_KEY is empty, generating..."
  php artisan key:generate --force
'

# 6) Миграции и таблицы для database drivers (sessions/cache/queue)
echo "[6/6] Running migrations..."
docker compose exec -T app sh -lc 'php artisan migrate --force || true'

docker compose exec -T app sh -lc 'php artisan session:table >/dev/null 2>&1 || true'
docker compose exec -T app sh -lc 'php artisan cache:table >/dev/null 2>&1 || true'
docker compose exec -T app sh -lc 'php artisan queue:table >/dev/null 2>&1 || true'
docker compose exec -T app sh -lc 'php artisan migrate --force || true'

echo
echo "✅ Deploy done!"
echo "Open: https://$DOMAIN"
echo
echo "Manual cert renew:"
echo "  docker compose run --rm certbot renew && docker compose restart nginx"
