#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUPS_DIR="$ROOT_DIR/backups"
TIMESTAMP="$(date '+%Y%m%d_%H%M%S')"
TARGET_DIR="$BACKUPS_DIR/$TIMESTAMP"

mkdir -p "$TARGET_DIR"

cd "$ROOT_DIR"

echo "📦 Creando backup en: $TARGET_DIR"

echo "🗄️  Backup de base de datos..."
docker-compose exec -T db pg_dump -U postgres --clean --if-exists --no-owner --no-privileges scan2order > "$TARGET_DIR/db.sql"

echo "🖼️  Backup de imágenes..."
mkdir -p "$ROOT_DIR/backend/storage/app/public"
tar -czf "$TARGET_DIR/storage_public.tar.gz" -C "$ROOT_DIR/backend/storage/app" public

echo "📝 Guardando metadatos..."
{
  echo "timestamp=$TIMESTAMP"
  echo "git_commit=$(git rev-parse --short HEAD 2>/dev/null || echo unknown)"
  echo "created_at=$(date '+%Y-%m-%d %H:%M:%S')"
} > "$TARGET_DIR/metadata.env"

ln -sfn "$TARGET_DIR" "$BACKUPS_DIR/latest"

echo "✅ Backup completado: $TARGET_DIR"
echo "$TARGET_DIR"
