#!/bin/bash
# run-qa.sh - QA gate runner: runs qa.sh against STAGING and records the result in
# qa-state.txt (the deploy token consumed by deploy-live.sh).
#   Usage: bash run-qa.sh [commit]       (commit = deployed HEAD sha; optional)
#   Run by deploy-staging.sh after rsync. Can also be run manually:
#     bash ~/deploy/run-qa.sh            # QA now, record state for whatever commit
#   Logs go to ~/deploy/qa-staging.log (via qa.sh) plus this script's own output.
set -uo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$DIR/deploy-common.sh"

LOG="$DIR/qa-run.log"
log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG"; }

COMMIT="${1:-$(git -C /home/gabozvqd/gabochie-deploy-staging rev-parse HEAD 2>/dev/null || echo unknown)}"

log "gate: running staging QA suite (commit $COMMIT)"
qa_gate_mark needs-qa "$COMMIT"

# 600s hard cap so a wedged QA run can never hold the deploy lock forever.
if timeout 600 bash "$DIR/qa.sh" --target=staging; then
  qa_gate_mark pass "$COMMIT"
  log "gate: STAGING QA PASS @ $COMMIT - deploy token issued"
  exit 0
else
  rc=$?
  qa_gate_mark fail "$COMMIT"
  log "gate: STAGING QA FAIL (rc=$rc) @ $COMMIT - live deploys BLOCKED until QA passes"
  exit 1
fi
