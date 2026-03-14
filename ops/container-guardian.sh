#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$ROOT_DIR"

PROJECT_PREFIX="scan2order"
LONG_RUNNING_SERVICES=(mailpit db backend scheduler nginx)
ALL_SERVICES=(mailpit db backend scheduler frontend nginx)
INTERVAL_SECONDS="${GUARDIAN_INTERVAL_SECONDS:-30}"

container_name_for_service() {
  local service="$1"
  echo "${PROJECT_PREFIX}-${service}"
}

service_exists() {
  local target="$1"
  for svc in "${ALL_SERVICES[@]}"; do
    if [[ "$svc" == "$target" ]]; then
      return 0
    fi
  done
  return 1
}

container_state() {
  local container_name="$1"
  docker inspect -f '{{.State.Status}}' "$container_name" 2>/dev/null || echo "missing"
}

container_health() {
  local container_name="$1"
  docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' "$container_name" 2>/dev/null || echo "none"
}

ensure_service_healthy() {
  local service="$1"
  local container_name
  container_name="$(container_name_for_service "$service")"

  local state health
  state="$(container_state "$container_name")"
  health="$(container_health "$container_name")"

  if [[ "$state" == "missing" ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $service missing -> unable to auto-create container"
    return
  fi

  if [[ "$state" != "running" ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $service state=$state -> restarting"
    docker restart "$container_name" >/dev/null || docker start "$container_name" >/dev/null
    return
  fi

  if [[ "$health" == "unhealthy" ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $service health=unhealthy -> restarting"
    docker restart "$container_name" >/dev/null
    return
  fi

  echo "[$(date '+%Y-%m-%d %H:%M:%S')] $service ok (state=$state health=$health)"
}

check_services() {
  local mode="${1:-long}"
  local services=("${LONG_RUNNING_SERVICES[@]}")
  if [[ "$mode" == "all" ]]; then
    services=("${ALL_SERVICES[@]}")
  fi

  for service in "${services[@]}"; do
    local container_name state health
    container_name="$(container_name_for_service "$service")"
    state="$(container_state "$container_name")"
    health="$(container_health "$container_name")"
    echo "$service -> state=$state health=$health"
  done
}

heal_services() {
  local mode="${1:-long}"
  local services=("${LONG_RUNNING_SERVICES[@]}")
  if [[ "$mode" == "all" ]]; then
    services=("${ALL_SERVICES[@]}")
  fi

  for service in "${services[@]}"; do
    ensure_service_healthy "$service"
  done
}

restart_target() {
  local target="${1:-all}"

  if [[ "$target" == "all" ]]; then
    for service in "${ALL_SERVICES[@]}"; do
      local container_name
      container_name="$(container_name_for_service "$service")"
      if docker inspect "$container_name" >/dev/null 2>&1; then
        docker restart "$container_name" >/dev/null || true
      fi
    done
    return
  fi

  if ! service_exists "$target"; then
    echo "Servicio no reconocido: $target"
    echo "Servicios válidos: ${ALL_SERVICES[*]}"
    exit 1
  fi

  local container_name
  container_name="$(container_name_for_service "$target")"
  if ! docker inspect "$container_name" >/dev/null 2>&1; then
    echo "Contenedor no encontrado: $container_name"
    exit 1
  fi

  docker restart "$container_name"
}

daemon_loop() {
  local mode="${1:-long}"
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] Guardian daemon started (mode=$mode interval=${INTERVAL_SECONDS}s)"
  while true; do
    heal_services "$mode" || true
    sleep "$INTERVAL_SECONDS"
  done
}

usage() {
  cat <<EOF
Uso:
  ./ops/container-guardian.sh check [long|all]
  ./ops/container-guardian.sh heal [long|all]
  ./ops/container-guardian.sh restart [service|all]
  ./ops/container-guardian.sh daemon [long|all]

Notas:
  - long: servicios persistentes (mailpit db backend scheduler nginx)
  - all: incluye frontend builder (puede quedar en exited(0) tras build)
EOF
}

CMD="${1:-}"
ARG="${2:-}"

case "$CMD" in
  check)
    check_services "${ARG:-long}"
    ;;
  heal)
    heal_services "${ARG:-long}"
    ;;
  restart)
    restart_target "${ARG:-all}"
    ;;
  daemon)
    daemon_loop "${ARG:-long}"
    ;;
  *)
    usage
    exit 1
    ;;
esac
