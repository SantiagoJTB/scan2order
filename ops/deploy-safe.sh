#!/usr/bin/env bash
set -eEuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKUP_DIR=""
ROLLBACK_DONE=0

rollback_on_error() {
  local exit_code=$?
  if [[ $ROLLBACK_DONE -eq 1 ]]; then
    exit $exit_code
  fi

  echo "❌ Deploy falló. Iniciando rollback automático..."
  if [[ -n "$BACKUP_DIR" && -d "$BACKUP_DIR" ]]; then
    "$ROOT_DIR/ops/restore-backup.sh" "$(basename "$BACKUP_DIR")" || true
    ROLLBACK_DONE=1
    echo "↩️ Rollback ejecutado usando backup: $BACKUP_DIR"
  else
    echo "⚠️ No hay backup para rollback automático"
  fi

  exit $exit_code
}

trap rollback_on_error ERR

cd "$ROOT_DIR"

echo "1) Backup BD + imágenes"
BACKUP_DIR="$($ROOT_DIR/ops/backup-create.sh | tail -n 1)"

echo "2) Deploy de servicios"
docker-compose up --build -d backend scheduler frontend nginx

echo "3) Ejecutar migraciones manualmente"
docker-compose exec -T backend php artisan migrate --force

echo "4) Verificación"
docker-compose exec -T backend php artisan migrate:status >/dev/null
curl -fsS "http://localhost:8080" >/dev/null

if ! docker-compose ps | grep -q "scan2order-backend"; then
  echo "Backend no está levantado"
  exit 1
fi

if ! docker-compose ps | grep -q "scan2order-nginx"; then
  echo "Nginx no está levantado"
  exit 1
fi

echo "✅ Deploy seguro completado"
echo "📌 Backup de recuperación: $BACKUP_DIR"
