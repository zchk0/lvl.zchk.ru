#!/usr/bin/env bash
set -euo pipefail

# Запуск:
#   ./deploy-local.sh
# 
# Остановка:
#   docker compose -f docker-compose.local.yml down
#
# Откроется:
#   http://localhost:8080

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker not found. Install Docker first."
  exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
  echo "docker compose not found. Install Docker Compose plugin first."
  exit 1
fi

mkdir -p docker/nginx
if [[ ! -f docker/nginx/local.conf ]]; then
  echo "Missing docker/nginx/local.conf (create it from the template)."
  exit 1
fi

echo "[1/6] Starting local containers..."
docker compose -f docker-compose.local.yml up -d

echo "[2/6] Preparing SQLite file..."
docker compose -f docker-compose.local.yml exec -T app sh -lc \
  "mkdir -p database && test -f database/database.sqlite || touch database/database.sqlite"

# Composer (если vendor нет — ставим зависимости)
echo "[3/6] Ensuring composer dependencies..."
docker compose -f docker-compose.local.yml exec -T app sh -lc '
  if [ ! -d vendor ]; then
    echo "vendor/ not found -> running composer install..."
    composer install --no-interaction
  else
    echo "vendor/ exists -> skipping composer install."
  fi
'

# APP_KEY только если пустой
echo "[4/6] Ensuring APP_KEY exists (only if empty)..."
docker compose -f docker-compose.local.yml exec -T app sh -lc '
  if [ -f .env ]; then
    KEY="$(grep -E "^APP_KEY=" .env | head -n1 | cut -d= -f2- || true)"
    if [ -n "$KEY" ] && [ "$KEY" != "null" ]; then
      echo "APP_KEY already set in .env (skipping)."
      exit 0
    fi
  fi

  if [ -n "${APP_KEY:-}" ] && [ "${APP_KEY:-}" != "null" ]; then
    echo "APP_KEY already set via container environment (skipping)."
    exit 0
  fi

  echo "APP_KEY is empty, generating..."
  php artisan key:generate
'

echo "[5/6] Running migrations..."
docker compose -f docker-compose.local.yml exec -T app sh -lc 'php artisan migrate || true'

# Таблицы под database sessions/cache/queue (у тебя они включены)
echo "[6/6] Ensuring database tables for sessions/cache/queue..."
docker compose -f docker-compose.local.yml exec -T app sh -lc 'php artisan session:table >/dev/null 2>&1 || true'
docker compose -f docker-compose.local.yml exec -T app sh -lc 'php artisan cache:table >/dev/null 2>&1 || true'
docker compose -f docker-compose.local.yml exec -T app sh -lc 'php artisan queue:table >/dev/null 2>&1 || true'
docker compose -f docker-compose.local.yml exec -T app sh -lc 'php artisan migrate || true'

echo
echo "✅ Local is up!"
echo "Open: http://localhost:8080"
