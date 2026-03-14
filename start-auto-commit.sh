#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PID_FILE="$REPO_DIR/.git/auto-commit.pid"
LOG_FILE="$REPO_DIR/logs/auto-commit.log"

mkdir -p "$REPO_DIR/logs"

if [[ -f "$PID_FILE" ]]; then
  PID="$(cat "$PID_FILE")"
  if kill -0 "$PID" 2>/dev/null; then
    echo "Auto-commit already running with PID $PID"
    exit 0
  fi
fi

cd "$REPO_DIR"
nohup bash "$REPO_DIR/auto-commit-loop.sh" >> "$LOG_FILE" 2>&1 &
echo $! > "$PID_FILE"
echo "Auto-commit started with PID $(cat "$PID_FILE")"
