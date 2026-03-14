#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$ROOT_DIR"

PROJECT_PREFIX="scan2order"
LONG_RUNNING_SERVICES=(mailpit db backend scheduler nginx)
ALL_SERVICES=(mailpit db backend scheduler frontend nginx)
INTERVAL_SECONDS="${GUARDIAN_INTERVAL_SECONDS:-30}"
RESTART_COOLDOWN_SECONDS="${GUARDIAN_RESTART_COOLDOWN_SECONDS:-120}"
MAX_RECOVERY_ATTEMPTS="${GUARDIAN_MAX_RECOVERY_ATTEMPTS:-3}"
MAX_CYCLE_FAILURES="${GUARDIAN_MAX_CYCLE_FAILURES:-4}"
LOCK_FILE="$ROOT_DIR/.git/container-guardian.lock"

declare -A SERVICE_RECOVERY_COUNT
declare -A SERVICE_LAST_RECOVERY_TS

timestamp_now() {
  date +%s
}

log_line() {
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

cleanup_lock() {
  if [[ -f "$LOCK_FILE" ]] && [[ "$(cat "$LOCK_FILE" 2>/dev/null || true)" == "$$" ]]; then
    rm -f "$LOCK_FILE"
  fi
}

acquire_lock() {
  if [[ -f "$LOCK_FILE" ]]; then
    local existing_pid
    existing_pid="$(cat "$LOCK_FILE" 2>/dev/null || true)"
    if [[ -n "$existing_pid" ]] && kill -0 "$existing_pid" 2>/dev/null; then
      log_line "Guardian ya activo con PID $existing_pid. Abortando para evitar bucle duplicado."
      exit 1
    fi
    rm -f "$LOCK_FILE"
  fi

  echo "$$" > "$LOCK_FILE"
  trap cleanup_lock EXIT INT TERM
}

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
    log_line "$service missing -> unable to auto-create container"
    return 1
  fi

  if [[ "$state" != "running" ]]; then
    if ! attempt_recovery "$service" "$container_name" "state=$state"; then
      return 1
    fi
    return 0
  fi

  if [[ "$health" == "unhealthy" ]]; then
    if ! attempt_recovery "$service" "$container_name" "health=unhealthy"; then
      return 1
    fi
    return 0
  fi

  log_line "$service ok (state=$state health=$health)"
  return 0
}

attempt_recovery() {
  local service="$1"
  local container_name="$2"
  local reason="$3"
  local now last_ts attempts

  now="$(timestamp_now)"
  last_ts="${SERVICE_LAST_RECOVERY_TS[$service]:-0}"
  attempts="${SERVICE_RECOVERY_COUNT[$service]:-0}"

  if (( attempts >= MAX_RECOVERY_ATTEMPTS )); then
    log_line "$service $reason -> recovery limit reached ($attempts/$MAX_RECOVERY_ATTEMPTS), skipping"
    return 1
  fi

  if (( last_ts > 0 && (now - last_ts) < RESTART_COOLDOWN_SECONDS )); then
    log_line "$service $reason -> cooldown active ($((${RESTART_COOLDOWN_SECONDS} - (now - last_ts)))s left), skipping"
    return 1
  fi

  SERVICE_LAST_RECOVERY_TS[$service]="$now"
  SERVICE_RECOVERY_COUNT[$service]="$((attempts + 1))"

  log_line "$service $reason -> recovery attempt ${SERVICE_RECOVERY_COUNT[$service]}/$MAX_RECOVERY_ATTEMPTS"
  if docker restart "$container_name" >/dev/null 2>&1 || docker start "$container_name" >/dev/null 2>&1; then
    return 0
  fi

  return 1
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
  local failures=0
  if [[ "$mode" == "all" ]]; then
    services=("${ALL_SERVICES[@]}")
  fi

  for service in "${services[@]}"; do
    if ! ensure_service_healthy "$service"; then
      failures=$((failures + 1))
    fi
  done

  if (( failures > 0 )); then
    return 1
  fi

  return 0
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
  local cycle_failures=0

  acquire_lock
  log_line "Guardian daemon started (mode=$mode interval=${INTERVAL_SECONDS}s)"
  while true; do
    if heal_services "$mode"; then
      cycle_failures=0
    else
      cycle_failures=$((cycle_failures + 1))
      log_line "Guardian cycle failure ${cycle_failures}/${MAX_CYCLE_FAILURES}"

      if (( cycle_failures >= MAX_CYCLE_FAILURES )); then
        log_line "Guardian fail-safe disconnect: too many failures, daemon stopping to avoid loop/blocking"
        exit 1
      fi
    fi

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
