#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PID_FILE="$REPO_DIR/.git/auto-commit.pid"

if [[ ! -f "$PID_FILE" ]]; then
  echo "Auto-commit is not running"
  exit 0
fi

PID="$(cat "$PID_FILE")"
if kill -0 "$PID" 2>/dev/null; then
  kill "$PID"
  echo "Stopped auto-commit PID $PID"
else
  echo "Process $PID was not running"
fi

rm -f "$PID_FILE"
