#!/bin/bash
# ci-promote.sh <vX.Y.Z> — create a release tag from the QA-passed commit on main.
#
# Refuses when:
#   - the tag name is malformed
#   - the tag already exists on origin (tags are immutable)
#   - the staging QA token is missing, not "pass", or for a different commit
#
# Exit codes: 0 ok, 1 QA/state problem, 2 git problem, 64 bad argument.
set -uo pipefail
export HOME=/home/gabozvqd

TAG="${1:-}"
DEPLOY="$HOME/deploy"
CLONE="$HOME/gabochie-deploy-staging"

if ! [[ "$TAG" =~ ^v[0-9]+\.[0-9]+\.[0-9]+([.-][A-Za-z0-9]+)*$ ]]; then
  echo "invalid release tag: '$TAG' (expected vX.Y.Z)"
  exit 64
fi

if [ ! -d "$CLONE/.git" ]; then
  echo "staging clone missing at $CLONE"
  exit 2
fi

cd "$CLONE" || exit 2
git fetch origin main --quiet || {
  echo "cannot fetch origin/main"
  exit 2
}

MAIN="$(git rev-parse origin/main)"
QA_STATUS="$(sed -n 's/^STATUS=//p' "$DEPLOY/qa-state.txt" 2>/dev/null)"
QA_COMMIT="$(sed -n 's/^COMMIT=//p' "$DEPLOY/qa-state.txt" 2>/dev/null)"

echo "origin/main: $MAIN"
echo "qa status:   ${QA_STATUS:-missing}"
echo "qa commit:   ${QA_COMMIT:-missing}"

if [ "$QA_STATUS" != "pass" ]; then
  echo "REFUSED: staging QA status is '${QA_STATUS:-missing}', need 'pass'"
  exit 1
fi

if [ "${QA_COMMIT:0:8}" != "${MAIN:0:8}" ]; then
  echo "REFUSED: QA token is for ${QA_COMMIT:0:8} but main is ${MAIN:0:8}"
  exit 1
fi

if git ls-remote --exit-code --tags origin "refs/tags/$TAG" >/dev/null 2>&1; then
  echo "REFUSED: tag $TAG already exists on origin (tags are immutable)"
  exit 1
fi

git tag -f -a "$TAG" "$MAIN" -m "Release $TAG (manual promotion via GitHub Actions)" || exit 2
if ! git push origin "refs/tags/$TAG"; then
  echo "FAILED: could not push tag $TAG"
  exit 2
fi

echo "CREATED $TAG at $MAIN"
