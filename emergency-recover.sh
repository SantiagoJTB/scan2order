#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

TARGET="${1:-latest}"

echo "⚠️  MODO EMERGENCIA"
echo "Este script restaurará BD + imágenes desde el backup: $TARGET"
read -r -p "Escribe RECUPERAR para continuar: " CONFIRM

if [[ "$CONFIRM" != "RECUPERAR" ]]; then
  echo "Operación cancelada"
  exit 1
fi

echo "1) Detener procesos de auto-commit (si existen)"
if [[ -x "$ROOT_DIR/stop-auto-commit.sh" ]]; then
  "$ROOT_DIR/stop-auto-commit.sh" || true
fi

echo "2) Restaurar backup"
"$ROOT_DIR/restore-backup.sh" "$TARGET"

echo "3) Verificación rápida"
docker-compose ps
docker-compose exec -T backend php artisan migrate:status >/dev/null
curl -fsS "http://localhost:8080" >/dev/null

echo "✅ Recuperación de emergencia completada"
