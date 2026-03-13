#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

echo "[1/7] Production preflight"
php artisan production:check --strict

echo "[2/7] Maintenance mode ON"
php artisan down || true

echo "[3/7] Install dependencies"
composer install --no-dev --optimize-autoloader

echo "[4/7] Run migrations"
php artisan migrate --force

echo "[5/7] Cache config/routes/views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[6/7] Quick health commands"
php artisan security:prune || true
php artisan about

echo "[7/7] Maintenance mode OFF"
php artisan up

echo "Release steps completed."
