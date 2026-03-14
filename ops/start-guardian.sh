#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
PID_FILE="$ROOT_DIR/.git/container-guardian.pid"
LOG_FILE="$ROOT_DIR/logs/container-guardian.log"
MODE="${1:-long}"

mkdir -p "$ROOT_DIR/logs"

if [[ -f "$PID_FILE" ]]; then
  PID="$(cat "$PID_FILE")"
  if kill -0 "$PID" 2>/dev/null; then
    echo "Guardian ya está corriendo con PID $PID"
    exit 0
  fi
fi

cd "$ROOT_DIR"
nohup bash "$ROOT_DIR/ops/container-guardian.sh" daemon "$MODE" >> "$LOG_FILE" 2>&1 &
echo $! > "$PID_FILE"
echo "Guardian iniciado con PID $(cat "$PID_FILE") (mode=$MODE)"
