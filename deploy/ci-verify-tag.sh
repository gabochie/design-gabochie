#!/bin/bash
# ci-verify-tag.sh <tag> — wait for deploy-live.sh to finish handling <tag>.
#
# Exit codes:
#   0  deploy ok
#   2  blocked by the QA gate
#   3  tag could not be checked out
#   4  timed out
#  64  invalid tag argument
set -uo pipefail
export HOME=/home/gabozvqd

TAG="${1:-}"
LOG="$HOME/deploy/deploy-live.log"

if ! [[ "$TAG" =~ ^(v[0-9][A-Za-z0-9._-]*|auto-[0-9]{8}-[0-9]{6})$ ]]; then
  echo "invalid tag: $TAG"
  exit 64
fi

# 90 x 8s = 12 minutes.
for _ in $(seq 1 90); do
  if grep -qF "deploy ok $TAG" "$LOG" 2>/dev/null; then
    echo "RESULT=deployed"
    exit 0
  fi
  if grep -qF "LIVE DEPLOY BLOCKED for $TAG" "$LOG" 2>/dev/null; then
    echo "RESULT=blocked"
    exit 2
  fi
  if grep -qF "FAIL: checkout $TAG" "$LOG" 2>/dev/null; then
    echo "RESULT=checkout-failed"
    exit 3
  fi
  sleep 8
done

echo "RESULT=timeout"
tail -40 "$LOG" 2>/dev/null || true
exit 4
