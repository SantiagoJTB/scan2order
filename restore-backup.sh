#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUPS_DIR="$ROOT_DIR/backups"
TARGET="${1:-latest}"

if [[ "$TARGET" == "latest" ]]; then
  TARGET_DIR="$BACKUPS_DIR/latest"
else
  TARGET_DIR="$BACKUPS_DIR/$TARGET"
fi

if [[ ! -e "$TARGET_DIR" ]]; then
  echo "❌ Backup no encontrado: $TARGET_DIR"
  exit 1
fi

if [[ ! -f "$TARGET_DIR/db.sql" ]]; then
  echo "❌ Falta archivo de base de datos: $TARGET_DIR/db.sql"
  exit 1
fi

if [[ ! -f "$TARGET_DIR/storage_public.tar.gz" ]]; then
  echo "❌ Falta archivo de imágenes: $TARGET_DIR/storage_public.tar.gz"
  exit 1
fi

cd "$ROOT_DIR"

echo "♻️ Restaurando desde: $TARGET_DIR"

echo "🛑 Deteniendo servicios de app (manteniendo DB)..."
docker-compose stop backend scheduler frontend nginx >/dev/null 2>&1 || true

echo "🗄️ Restaurando base de datos..."
docker-compose up -d db >/dev/null
cat "$TARGET_DIR/db.sql" | docker-compose exec -T db psql -v ON_ERROR_STOP=1 -U postgres -d scan2order >/dev/null

echo "🖼️ Restaurando imágenes..."
rm -rf "$ROOT_DIR/backend/storage/app/public"
mkdir -p "$ROOT_DIR/backend/storage/app"
tar -xzf "$TARGET_DIR/storage_public.tar.gz" -C "$ROOT_DIR/backend/storage/app"

echo "🚀 Levantando servicios..."
docker-compose up -d backend scheduler frontend nginx >/dev/null

echo "✅ Restore completado"
