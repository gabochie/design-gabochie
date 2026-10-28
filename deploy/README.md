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
deploy-staging.sh  ->  rsync main into ~/wp.design.gabochie.com
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
| `monitor.sh` | **Continuous** 5-min health monitor: JSONL history, Prometheus textfile, transition-based alerts (email/webhook) with cooldown, sanitized public status JSON, optional TSDB push. |
| `rollback-live.sh` | Code-only rollback to the previous (or a named) tag, with snapshot + smoke test. |
| `backup.sh` | Nightly DB + uploads backup, 7-day retention. |
| `healthcheck.sh` | Read-only health probe. Outputs JSON (default), human summary (`--human`), or Prometheus metrics (`--prometheus`). |
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
bash ~/deploy/monitor.sh --status
cat ~/deploy/qa-state.txt
bash ~/deploy/deploy-common.sh && auto_promote_status   # source, don't execute

# Cancel the pending production promotion
touch ~/deploy/skip-live

# Verify production right now
bash ~/deploy/lib/smoke-test.sh live
bash ~/deploy/healthcheck.sh --human
bash ~/deploy/healthcheck.sh --prometheus

# Roll production back one tag
bash ~/deploy/rollback-live.sh --dry-run
bash ~/deploy/rollback-live.sh

# Back up now
bash ~/deploy/backup.sh
```

## Continuous monitoring & observability

Add these to cPanel cron (run as `gabozvqd`):

```cron
# Auto-promote + staging catch-up
*/5 * * * * /bin/bash /home/gabozvqd/deploy/watchdog.sh >> /home/gabozvqd/deploy/watchdog.log 2>&1
# Continuous health, alerts, metrics history
*/5 * * * * /bin/bash /home/gabozvqd/deploy/monitor.sh >> /home/gabozvqd/deploy/monitor.log 2>&1
```

### Outputs

| Path | Purpose |
| --- | --- |
| `~/deploy/metrics/health.jsonl` | Rolling JSONL sample history (rotated at ~5 MB, ~30 days+ retained). |
| `~/deploy/metrics/health.prom` | Prometheus textfile for `node_exporter` textfile collector or scrapers. |
| `~/deploy/metrics/state.json` | Last-run state, failing checks, alert history. |
| `~/deploy/metrics/status.json` | Sanitised status (read by tooling). |
| `~/wp.design.gabochie.com/health-status.json` | Public sanitised health snapshot for dashboards/external probes. |

### Alerting

- **Transition-based**: Emails + webhook alerts only on new criticals or recovery (no spam).
- **Cooldown**: Persists a critical set; re-alerts at most every `ALERT_REPEAT_SECONDS` (default 21600s / 6h).
- **Email**: Uses `DEPLOY_NOTIFY_EMAIL` / `mail()` via `deploy-common.sh`. Server secrets live outside the docroot.
- **Webhook**: Set `ALERT_WEBHOOK_URL` in `~/deploy/alert.env` (Slack-compatible `{"text": "..."}`, mode 600). Also configurable via GitHub secret for the Actions uptime workflow.

```bash
# ~/deploy/alert.env
ALERT_WEBHOOK_URL=https://hooks.slack.com/services/...
```

### Push metrics to a TSDB

Set `~/deploy/metrics.env` (mode 600) to push snapshots to an external collector:

| Format | URL example | Notes |
| --- | --- | --- |
| `graphite` | `https://graphite-host:port/metrics` or Grafana Cloud Graphite endpoint | Posts plaintext `gabochie.health.* <value> <epoch>`. Add `METRICS_PUSH_AUTH="Authorization: Basic <base64>"`. |
| `influx` | InfluxDB write endpoint | Line protocol to `health` measurement. |
| `json` | Any webhook/collector | Posts `~/deploy/metrics/status.json`. |
| `prometheus` | Prometheus pushgateway-compatible endpoint | Sends `health.prom` contents as `text/plain`. |

```bash
# ~/deploy/metrics.env
METRICS_PUSH_URL=https://example.com/metrics
METRICS_PUSH_FORMAT=graphite
METRICS_PUSH_AUTH="Authorization: Basic dGVzdDp0ZXN0"
```

### SLOs / SLIs

| SLI | Target (proposed) | Source |
| --- | --- | --- |
| HTTP availability (live + staging) | >= 99.9% (monthly) | `health.jsonl` + uptime probes |
| 24h rolling availability | >= 99.5% | `gabochie_health_availability{window="24h"}` / `status.json` |
| 7d rolling availability | >= 99.9% | `gabochie_health_availability{window="7d"}` |
| Backup freshness | < 36h | `backup_age_hours` (critical) |
| Staging QA gate coverage | 100% of auto-promotes | `qa_gate_check` (fail-closed) |

Error budget alerting and burn-rate pages are the natural next step once history accumulates. The GitHub Actions `Continuous Healthcheck` workflow also probes from outside the host every 10 minutes to detect total server/egress failure (which on-server cron cannot do).

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
