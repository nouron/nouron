#!/usr/bin/env bash
# Pre-merge checklist: blocks mcp__github__merge_pull_request unless
# CHANGELOG.md has an entry for today.

set -euo pipefail

CHANGELOG="$(git rev-parse --show-toplevel)/CHANGELOG.md"
TODAY=$(date +%Y-%m-%d)

missing=()

# 1. CHANGELOG must have an entry for today
if ! grep -q "^## ${TODAY}" "$CHANGELOG" 2>/dev/null; then
  missing+=("CHANGELOG.md hat keinen Eintrag für heute (## ${TODAY})")
fi

if [ ${#missing[@]} -eq 0 ]; then
  echo '{"continue": true}'
  exit 0
fi

# Build the block message
msg="MERGE BLOCKIERT — folgende Punkte fehlen noch:"$'\n'
for item in "${missing[@]}"; do
  msg+="  • ${item}"$'\n'
done
msg+=$'\n'"Bitte erledigen und danach nochmal mergen."

printf '%s' "$msg" >&2
printf '{"continue": false, "stopReason": "%s"}' \
  "$(echo "$msg" | tr '"' "'" | tr '\n' ' ')"
exit 1
