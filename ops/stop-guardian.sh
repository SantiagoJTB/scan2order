#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
PID_FILE="$ROOT_DIR/.git/container-guardian.pid"

if [[ ! -f "$PID_FILE" ]]; then
  echo "Guardian no está corriendo"
  exit 0
fi

PID="$(cat "$PID_FILE")"
if kill -0 "$PID" 2>/dev/null; then
  kill "$PID"
  echo "Guardian detenido (PID $PID)"
else
  echo "Proceso $PID no estaba activo"
fi

rm -f "$PID_FILE"
