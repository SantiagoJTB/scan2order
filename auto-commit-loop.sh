#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BRANCH="${AUTO_COMMIT_BRANCH:-master}"
INTERVAL_SECONDS="${AUTO_COMMIT_INTERVAL_SECONDS:-1200}"
LOG_DIR="$REPO_DIR/logs"

mkdir -p "$LOG_DIR"
cd "$REPO_DIR"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Auto-commit loop started on branch $BRANCH" 

while true; do
  if [[ -n "$(git status --porcelain)" ]]; then
    git add -A

    if ! git diff --cached --quiet; then
      MESSAGE="chore: auto-save $(date '+%Y-%m-%d %H:%M:%S')"
      echo "[$(date '+%Y-%m-%d %H:%M:%S')] Committing changes: $MESSAGE"
      git commit -m "$MESSAGE"
      git push origin "$BRANCH"
    fi
  fi

  sleep "$INTERVAL_SECONDS"
done
