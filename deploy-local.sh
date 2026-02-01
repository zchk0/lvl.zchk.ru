#!/usr/bin/env bash
set -euo pipefail

# Локальный деплой:
#   ./deploy-local.sh
#
# Открыть:
#   http://localhost:8080
#
# Остановить:
#   docker compose -f docker-compose.local.yml down
# Лог cron контейнера:
#   docker compose -f docker-compose.local.yml logs -f --tail=200 scheduler


COMPOSE_FILE="docker-compose.local.yml"
HOST_UID="$(id -u)"
HOST_GID="$(id -g)"

die() { echo "❌ $*" >&2; exit 1; }
dc() { docker compose -f "$COMPOSE_FILE" "$@"; }

# функции вызова консольных команд под разные права
exec_app() { dc exec -T app sh -lc "$*"; }
exec_app_root() { dc exec -T -u root app sh -lc "$*"; }
exec_app_host() { dc exec -T -u "${HOST_UID}:${HOST_GID}" app sh -lc "$*"; }

command -v docker >/dev/null 2>&1 || die "Docker not found. Install Docker first."
docker compose version >/dev/null 2>&1 || die "docker compose not found. Install Docker Compose plugin first."
[[ -f "$COMPOSE_FILE" ]] || die "Missing $COMPOSE_FILE"
[[ -f docker/nginx/local.conf ]] || die "Missing docker/nginx/local.conf"

echo "[1/8] Создадим .env из .env.example"
if [[ ! -f .env ]]; then
  [[ -f .env.example ]] || die ".env is missing and .env.example not found"
  cp .env.example .env
  echo "Created .env from .env.example"
fi

# Подправим APP_URL под локал, чтобы не было https://lvl.zchk.ru на localhost
echo "[2/8] Изменим путь приложения если необходимо"
if grep -qE '^APP_URL=' .env; then
  sed -i 's|^APP_URL=.*|APP_URL=http://localhost:8080|' .env
fi

# На всякий: удобные логи в stderr в локале
if grep -qE '^LOG_CHANNEL=' .env; then
  sed -i 's|^LOG_CHANNEL=.*|LOG_CHANNEL=stderr|' .env
else
  echo 'LOG_CHANNEL=stderr' >> .env
fi

echo "[3/8] Запустим локально контейнеры"
dc up -d

echo "[4/8] Изменим доступы к директориям на которые обычно жалуется laravel..."
# exec_app_root "chmod -R 777 storage bootstrap/cache storage/logs storage/framework"

echo "[5/8] Ensuring composer dependencies..."
exec_app_host '
  if [ ! -d vendor ]; then
    echo "vendor/ not found -> running composer install..."
    composer install --no-interaction
  else
    echo "vendor/ exists -> skipping composer install."
  fi
'

# при CACHE_STORE=database сначала нужна таблица cache.
echo "[6/8] Запустим миграции..."
exec_app '
  php artisan cache:table || true
  php artisan session:table || true
  php artisan queue:table || true
'
exec_app 'php artisan migrate'

echo "[7/8] Почистим конфиги и кэш..."
exec_app '
  php artisan config:clear
  php artisan view:clear
  php artisan cache:clear
'

echo "[8/8] ✅ Проект успешно развернут локально!"
echo
echo "Open: http://localhost:8080"
echo "Logs: docker compose -f $COMPOSE_FILE logs -f --tail=200"
echo
