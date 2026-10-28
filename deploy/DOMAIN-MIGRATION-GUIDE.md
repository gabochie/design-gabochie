# Domain migration guide — gabochiedesign.com → design.gabochie.com

Step-by-step runbook for the 2026-10 domain migration. Anything marked ✅ is
already done and recorded for posterity; the remaining steps are the ones you
click/run yourself.

## Target architecture

| Role | URL | Notes |
| --- | --- | --- |
| Live (primary) | https://design.gabochie.com | Alias of `~/public_html` |
| Staging | https://wp.design.gabochie.com | Docroot `~/wp.design.gabochie.com` |
| Old domain | https://gabochiedesign.com | Renewed; 301s everything to live |
| SaaS tools | https://tools.gabochie.com | Subdomain family on gabochie.com |
| AI endpoint | https://ai.gabochie.com | LLM endpoint (yoast-smart-autofixer) |
| Emails | `@design.gabochie.com` / `support@gabochie.com` | Deploy + site contacts |

## Step 1 — Renew the old domain ✅

Renewed at Namecheap. Required so the 301 redirect and Google equity survive.

## Step 2 — cPanel subdomains + DNS ✅

1. cPanel (host `gabozvqd`, server `server178.web-hosting.com`):
   - `design.gabochie.com` → **alias sharing `/home/gabozvqd/public_html`**
   - `wp.design.gabochie.com` → docroot `/home/gabozvqd/wp.design.gabochie.com`
2. Cloudflare DNS (gabochie.com zone): A records for both hostnames →
   `68.65.120.206`. Proxy state per step 2c below.

## Step 3 — Server migration ✅ (executed 2026-10-27)

Runbook: `deploy/migrate-domain.sh` (dry-run then `--apply`). What it did:

- DB dump → `~/deploy/backups/pre-domain-20261027-160028.sql`
- Live DB: 2,376 + 63 + 1,240 (Yoast indexables) replacements; staging DB 1,828
- Staging docroot populated at `~/wp.design.gabochie.com`
- `.htaccess` 301 redirect block prepended BEFORE WordPress rules (block
  placed after WP's `[L]` never fires). Backup: `.htaccess.pre-domain-migration`
- Live + staging code files hot-patched (76 files) — identical to the repo
  mapping in commit `912286a2`
- LiteSpeed cache store (`~/lscache`) wiped and repopulated with new-domain HTML

Lessons baked into the runbook for next time:

- Yoast serves canonical/og tags from `wpor_yoast_indexable` — a DB replace
  must include it (`--all-tables`) or SEO tags stay on the old domain.
- `wp search-replace` on `https://…` misses scheme-less strings — run a bare
  domain catch-all pass too.

## Step 4 — Point GitHub webhook at the new domain  ✅ (2026-10-28)

Repointed in the GitHub UI; latest delivery shows 200 on the new domain.

1. Repo → Settings → Webhooks → edit the existing hook.
2. Payload URL: `https://design.gabochie.com/webhook.php` (path unchanged).
3. Leave the secret as-is; save; the redeliver test should return **200**.

A 301 turns webhook POSTs into GETs, so the POST must hit the final hostname.

## Step 5 — AutoSSL for wp.design.gabochie.com  ✅ (2026-10-27)

AutoSSL run via cPanel; both hostnames serve valid certs. Note: `wp.design`
stays DNS-only (grey cloud) in Cloudflare permanently — free Universal SSL
does not cover second-level subdomains.

## Step 6 — Push the migration commits  ✅ (2026-10-27)

Commits waiting locally: `912286a2` (90-file migration) and `545c576b`
(runbook hardening).

```bash
git push origin main          # staging deploy + QA fires automatically
```

- QA runs against `https://wp.design.gabochie.com` (live and passing).
- Auto-promote fires ~15 min after QA pass → live deploy converges the server
  with the hot-patched state.
- Watch: `bash ~/deploy/watchdog.sh --status`, `cat ~/deploy/qa-state.txt`,
  `tail -f ~/deploy/deploy-live.log`.

## Step 7 — Verify production  ✅ (2026-10-27/28)

Smoke test 15/15 PASS; external checks all pass (200 + new canonical, 301
chains, sitemap 200). Full pipeline ran end-to-end: staging QA PASS →
auto-promote → live deploy `auto-20261027-194351` → smoke test PASS.

```bash
bash ~/deploy/lib/smoke-test.sh live
bash ~/deploy/healthcheck.sh --human
```

External spot-checks (all should pass):

- `https://design.gabochie.com/` → 200, canonical = new domain
- `https://gabochiedesign.com/<any path>` → 301 → same path on design
- `https://design.gabochie.com/sitemap_index.xml` → 200
- Search Console: submit the new sitemap; expect re-crawl over weeks

## Step 8 — Email SPF for the new sender domain  ⬜ WHEN CONVENIENT

Transactional sends (`no-reply@design.gabochie.com`) need SPF/DKIM:

- Zoho (or the mail provider) → add/verify the design.gabochie.com sending
  domain or send under the gabochie.com apex.
- DNS TXT for `design.gabochie.com`: `v=spf1 include:<provider> ~all`.
- The gabochie-go-live plugin checklist tracks these items.

## Step 9 — Cleanup  ✅ (2026-10-28)

Old staging docroot `~/wp.gabochiedesign.com` (278 MB) deleted. Rollback
insurance that remains: the pre-migration DB dump and `.htaccess` backups in
`~/deploy/backups/`.

Optional: after search engines absorb the 301s (months), the old domain can
lapse — redirect equity depends on keeping it active meanwhile.

## Rollback (if anything regressed)

- Code: `bash ~/deploy/rollback-live.sh --dry-run` then `rollback-live.sh`
- DB: `wp db import ~/deploy/backups/pre-domain-20261027-160028.sql`
- Redirect: the 301 block now ships inside the repo `.htaccess` (top of file);
  timestamped copies of every intermediate state are in `~/deploy/backups/`
