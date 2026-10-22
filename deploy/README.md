# Deploy engine

This directory is the versioned source of truth for the scripts that run on the
web host in `~/deploy`. Before this directory existed the pipeline existed only
on the server, so it could not be reviewed, tested, or rebuilt after host loss.

## Pipeline

```
git push origin main
        |
        v
GitHub webhook (push)  ->  public_html/webhook.php   [HMAC-SHA256 verified]
        |
        v
deploy-staging.sh  ->  rsync main into ~/wp.gabochiedesign.com
        |
        v
run-qa.sh  ->  qa.sh (37 checks)  ->  qa-state.txt  STATUS=pass COMMIT=<sha>
        |
        +--> [skip-live] in commit message?  -> stop
        |
        v
auto-promote deadline: now + 15 min (state file .auto-promote, FIRE_AT=<epoch>)
        |
        v  (fired by the background timer, or by watchdog.sh from cron if cPanel
        |   killed that process - the deadline is what matters, not the process)
git tag auto-<timestamp> <sha>  ->  git push origin <tag>
        |
        v
GitHub webhook (tag push)  ->  deploy-live.sh <tag>
        |
        v
qa_gate_check <tag commit>   fail-closed: refuses unless qa-state.txt has
        |                    STATUS=pass for EXACTLY that commit
        v
rsync tag into ~/public_html  ->  smoke test  ->  deploy-status.json
```

Key properties:

- **Staging is automatic.** Every push to `main` deploys staging and runs QA.
- **Production is gated.** A live deploy only runs for a `v*` or `auto-*` tag
  whose commit has a passing staging QA token.
- **15-minute human window.** A promotion can be cancelled with
  `touch ~/deploy/skip-live` or by pushing another commit to `main`.
- **No database in the tag path.** Tag deploys are code-only, so a rollback is
  simply a checkout of the previous tag.
- **Fail closed.** Missing/stale/failed QA state blocks production.

## Files

| File | Purpose |
| --- | --- |
| `webhook.php` | GitHub push receiver. Verifies HMAC, routes `main` to staging and tags to live. Reads the secret from `~/deploy/webhook-secret.env`. |
| `deploy-staging.sh` | Fetches `main`, rsyncs to the staging docroot, runs QA, sets the promotion deadline. |
| `deploy-live.sh` | Deploys a tag to `public_html` after the QA-token check. |
| `deploy-common.sh` | QA gate, promotion deadline, status JSON, notifications. |
| `run-qa.sh` | Runs `qa.sh` against staging and writes `qa-state.txt`. |
| `qa.sh` | 37-check read-only QA suite (HTTP, REST, SEO, security headers, perf). |
| `watchdog.sh` | Cron every 5 min: fires a due promotion, catches staging up to `main`. |
| `rollback-live.sh` | Code-only rollback to the previous (or a named) tag, with snapshot + smoke test. |
| `backup.sh` | Nightly DB + uploads backup, 7-day retention. |
| `healthcheck.sh` | Read-only JSON health probe used by the nightly workflow. |
| `promote.sh` | **Launch-only** staging→live code **and database** promotion. Do not run after launch; it overwrites production data. |
| `review-probe.sh` | Read-only probe of staging vs live content. |
| `lib/smoke-test.sh` | Post-deploy HTTP smoke test. |
| `sync-to-server.sh` | Installs these files into `~/deploy` (backup + syntax check + drift detection). |
| `secrets/*.env.example` | Required server-side secrets. Real files are ignored by git. |

## Install / update the server scripts

```bash
# Check whether the server has drifted from this directory
./deploy/sync-to-server.sh --check

# Install (backs up the previous versions to ~/deploy/backups/scripts-<ts>/)
./deploy/sync-to-server.sh

# Also install the webhook receiver into the live docroot
./deploy/sync-to-server.sh --webhook
```

`sync-to-server.sh` never copies `*.env` files and never touches the database.

## Secrets

All four files live in `~/deploy` with mode `600`:

| File | Contents |
| --- | --- |
| `live.env` | `LIVE_DB_*`, `LIVE_URL`, `LIVE_DOCROOT`, `LIVE_SSH_*` |
| `staging.env` | `STAGING_DB_*`, `STAGING_URL`, `STAGING_DOCROOT`, `STAGING_SSH_*` |
| `deploy-secret.env` | `DEPLOY_STATUS_SECRET` (dashboard/deploy-status endpoint) |
| `webhook-secret.env` | `GABOCHIE_WEBHOOK_SECRET` (GitHub webhook HMAC) |

Rotate either shared secret with `openssl rand -hex 32`. When rotating the
webhook secret, update the GitHub webhook configuration in the same minute or
pushes will be rejected with `403`.

## Operating it

```bash
# Where are we?
bash ~/deploy/watchdog.sh --status
cat ~/deploy/qa-state.txt
bash ~/deploy/deploy-common.sh && auto_promote_status   # source, don't execute

# Cancel the pending production promotion
touch ~/deploy/skip-live

# Verify production right now
bash ~/deploy/lib/smoke-test.sh live
bash ~/deploy/healthcheck.sh --human

# Roll production back one tag
bash ~/deploy/rollback-live.sh --dry-run
bash ~/deploy/rollback-live.sh

# Back up now
bash ~/deploy/backup.sh
```

## Known failure modes

- **cPanel reaps long-lived background processes.** The 15-minute promotion used
  to depend on a `nohup sleep` surviving. It did not, and the old watchdog
  re-armed the timer every 5 minutes, so the deadline was pushed back forever
  and releases stranded on staging. Fixed: the deadline (`FIRE_AT`) is stored on
  disk and any cron tick can fire it.
- **`wp` is not on the non-interactive PATH.** Scripts export
  `/usr/local/sbin:/usr/local/bin:/usr/bin:/bin` before calling `wp`.
- **A tag must match the QA commit.** `qa_gate_check` compares the first 8 chars
  of the commit; a mis-pointed tag blocks the deploy by design.
- **`rsync --delete` respects keeper files.** `wp-config.php`, uploads, cache,
  `.well-known/`, `wp-admin/`, `wp-includes/` and the root WordPress PHP files
  are excluded so a deploy cannot wipe host-managed files.
