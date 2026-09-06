<?php
/**
 * Plugin Name: Gabochie Go Live
 * Description: Push staging (wp.gabochiedesign.com) to production (gabochiedesign.com) via the server-side promote.sh engine. Staging site only.
 * Version: 1.3.0
 * Author: Gabochie Design
 * License: GPL-2.0+
 * Text Domain: gabochie-go-live
 */

if (!defined('ABSPATH')) exit;

class Gabochie_Go_Live {

    const VERSION = '1.3.0';

    public static function deploy_dir() {
        return apply_filters('gabochie_go_live_deploy_dir', '/home/gabozvqd/deploy');
    }

    public static function is_staging() {
        $home = home_url();
        if (strpos($home, 'wp.gabochiedesign.com') !== false) {
            return true;
        }
        if (function_exists('wp_get_environment_type')) {
            return wp_get_environment_type() === 'staging';
        }
        return false;
    }

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('wp_ajax_gabochie_go_live_launch', array(__CLASS__, 'ajax_launch'));
        add_action('wp_ajax_gabochie_go_live_status', array(__CLASS__, 'ajax_status'));
        add_action('wp_ajax_gabochie_checklist_get', array(__CLASS__, 'ajax_checklist_get'));
        add_action('wp_ajax_gabochie_checklist_toggle', array(__CLASS__, 'ajax_checklist_toggle'));
        add_action('wp_ajax_gabochie_checklist_add', array(__CLASS__, 'ajax_checklist_add'));
        add_action('wp_ajax_gabochie_checklist_delete', array(__CLASS__, 'ajax_checklist_delete'));
        add_action('wp_ajax_gabochie_recs_get', array(__CLASS__, 'ajax_recs_get'));
        add_action('wp_ajax_gabochie_compare_get', array(__CLASS__, 'ajax_compare_get'));
        add_action('wp_ajax_gabochie_compare_probe', array(__CLASS__, 'ajax_compare_probe'));
        add_action('wp_ajax_gabochie_score_refresh', array(__CLASS__, 'ajax_score_refresh'));
    }

    public static function menu() {
        add_menu_page(
            'Go Live',
            'Go Live',
            'manage_options',
            'gabochie-go-live',
            array(__CLASS__, 'render'),
            'dashicons-migrate',
            3
        );
        add_submenu_page(
            'gabochie-go-live',
            'Release QA',
            'Release QA',
            'manage_options',
            'gabochie-go-live-qa',
            array(__CLASS__, 'render_qa')
        );
        add_submenu_page(
            'gabochie-go-live',
            'Staging vs Live',
            'Comparison',
            'manage_options',
            'gabochie-go-live-compare',
            array(__CLASS__, 'render_compare')
        );
        add_submenu_page(
            'gabochie-go-live',
            'Release Score',
            'Release Score',
            'manage_options',
            'gabochie-go-live-score',
            array(__CLASS__, 'render_score')
        );
    }

    public static function shell_ok() {
        if (!function_exists('shell_exec')) return false;
        $disabled = array_map('trim', explode(',', ini_get('disable_functions')));
        return !in_array('shell_exec', $disabled, true) && !in_array('exec', $disabled, true);
    }

    public static function run($cmd) {
        $out = shell_exec($cmd . ' 2>&1');
        return is_string($out) ? trim($out) : '';
    }

    public static function read_status() {
        $dir = self::deploy_dir();
        $status = array('status' => 'idle', 'message' => 'No promote has run yet.', 'finished' => '');
        $lock = $dir . '/promote.lock';
        if (file_exists($lock)) {
            return array('status' => 'running', 'message' => 'Go Live is running...', 'finished' => '', 'since' => filemtime($lock));
        }
        $f = $dir . '/promote.status';
        if (file_exists($f)) {
            $json = @file_get_contents($f);
            $decoded = $json ? json_decode($json, true) : null;
            if (is_array($decoded)) {
                $status = $decoded;
            } elseif (is_string($json)) {
                $status = array('status' => 'unknown', 'message' => trim($json), 'finished' => '');
            }
        }
        return $status;
    }

    public static function last_staging_smoke() {
        $f = self::deploy_dir() . '/deploy.log';
        if (!file_exists($f)) return 'no deploy.log yet';
        $lines = @file($f);
        if (!$lines) return 'deploy.log unreadable';
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (strpos($lines[$i], 'SMOKE TEST') !== false) {
                return trim($lines[$i]);
            }
        }
        return 'no SMOKE TEST line yet';
    }

    public static function staging_commit() {
        $dir = self::deploy_dir();
        return self::run('git --git-dir=' . escapeshellarg($dir . '/gabochiedesign.git') . ' rev-parse --short main 2>/dev/null') ?: 'n/a';
    }

    public static function checklist_option($list) {
        if ($list === 'precheck') return 'gabochie_precheck_list';
        if ($list === 'qa') return 'gabochie_qa_roadmap';
        if ($list === 'gates') return 'gabochie_qa_gates';
        return 'gabochie_launch_checklist';
    }

    public static function checklist_get($list = 'launch') {
        $opt = self::checklist_option($list);
        $items = get_option($opt);
        if (!is_array($items)) {
            $items = self::checklist_defaults($list);
            update_option($opt, $items, false);
            return $items;
        }
        $have = array();
        foreach ($items as $i) {
            if (isset($i['id'])) $have[$i['id']] = true;
        }
        $added = false;
        foreach (self::checklist_defaults($list) as $d) {
            if (isset($d['id']) && empty($have[$d['id']])) {
                $items[] = $d;
                $added = true;
            }
        }
        if ($added) update_option($opt, $items, false);
        return $items;
    }

    public static function checklist_defaults($list = 'launch') {
        if ($list === 'qa') {
            return array(
                array('id' => 'qa-1-lint', 'text' => 'Phase 1 — Code Quality: PHP lint sweep (php -l) over tracked plugins + theme; no parse/fatal errors.', 'done' => 0),
                array('id' => 'qa-1-wpcs', 'text' => 'Phase 1 — Code Quality: PHPCS with WordPress Coding Standards (naming, formatting, escaping, sanitization, security).', 'done' => 0),
                array('id' => 'qa-1-phpstan', 'text' => 'Phase 1 — Code Quality: PHPStan static analysis (undefined vars, bad returns, unreachable code).', 'done' => 0),
                array('id' => 'qa-2-unit', 'text' => 'Phase 2 — Unit Tests: PHPUnit for our plugins (calculate_total, create_invoice, send_email equivalents).', 'done' => 0),
                array('id' => 'qa-2-integration', 'text' => 'Phase 2 — Integration Tests: plugin ↔ DB ↔ WooCommerce ↔ email ↔ API chains.', 'done' => 0),
                array('id' => 'qa-2-functional', 'text' => 'Phase 2 — WP Functional Tests: create/edit/save/publish/delete post (WordPress Testing Framework).', 'done' => 0),
                array('id' => 'qa-2-theme', 'text' => 'Phase 2 — Theme Tests: template loading, single, archive, search, 404, category, tags, pagination.', 'done' => 0),
                array('id' => 'qa-2-gutenberg', 'text' => 'Phase 2 — Gutenberg Tests: custom blocks, editor, block rendering, reusable blocks.', 'done' => 0),
                array('id' => 'qa-2-compat', 'text' => 'Phase 2 — Plugin Compatibility: WooCommerce, Yoast, Elementor/Kadence, contact forms, caching plugins.', 'done' => 0),
                array('id' => 'qa-2-phpver', 'text' => 'Phase 2 — PHP Version Compatibility: confirm 8.1/8.2/8.3 behavior (server is 8.3.31).', 'done' => 0),
                array('id' => 'qa-2-dbmig', 'text' => 'Phase 2 — Database Migration Tests: schema updates, search-replace correctness, rollback.', 'done' => 0),
                array('id' => 'qa-3-security', 'text' => 'Phase 3 — Security Tests: SQLi/XSS/CSRF/escaping audit, capability checks, role matrix (admin/editor/author/subscriber/guest).', 'done' => 0),
                array('id' => 'qa-3-sanitize', 'text' => 'Phase 3 — Sanitization & Escaping Tests: sanitize_text_field/email/intval; esc_url/esc_html/esc_attr on output.', 'done' => 0),
                array('id' => 'qa-3-rest', 'text' => 'Phase 3 — REST API Tests: endpoints, auth, permissions, validation.', 'done' => 0),
                array('id' => 'qa-3-ajax', 'text' => 'Phase 3 — AJAX Tests: admin-ajax, nonces, responses, timeouts.', 'done' => 0),
                array('id' => 'qa-4-perf', 'text' => 'Phase 4 — Performance Tests: page load, TTFB, queries, memory, object cache/OPcache.', 'done' => 0),
                array('id' => 'qa-4-query', 'text' => 'Phase 4 — Database Query Tests: Query Monitor for duplicate/slow/N+1 queries, missing indexes.', 'done' => 0),
                array('id' => 'qa-4-leaks', 'text' => 'Phase 4 — Memory Leak Tests: memory usage, long-running requests, recursion.', 'done' => 0),
                array('id' => 'qa-4-errors', 'text' => 'Phase 4 — Error Log Tests: WP_DEBUG/WP_DEBUG_LOG; 0 warnings, notices, fatals, deprecated.', 'done' => 0),
                array('id' => 'qa-5-browser', 'text' => 'Phase 5 — Browser Tests: Chrome, Firefox, Safari, Edge, mobile browsers.', 'done' => 0),
                array('id' => 'qa-5-responsive', 'text' => 'Phase 5 — Responsive Tests: desktop, tablet, phone, landscape, portrait.', 'done' => 0),
                array('id' => 'qa-5-a11y', 'text' => 'Phase 5 — Accessibility Tests: WCAG, keyboard nav, screen readers, contrast, alt text, focus order, ARIA.', 'done' => 0),
                array('id' => 'qa-5-seo', 'text' => 'Phase 5 — SEO Tests: meta, Open Graph, JSON-LD, robots.txt, sitemap, canonical.', 'done' => 0),
                array('id' => 'qa-5-links', 'text' => 'Phase 5 — Link Tests: broken links, redirects, 404s, menus, breadcrumbs.', 'done' => 0),
                array('id' => 'qa-5-images', 'text' => 'Phase 5 — Image Tests: lazy loading, responsive images, WebP/AVIF, compression, sizes.', 'done' => 0),
                array('id' => 'qa-5-assets', 'text' => 'Phase 5 — Asset Tests: CSS, JS, fonts, minification, bundling, cache busting.', 'done' => 0),
                array('id' => 'qa-5-build', 'text' => 'Phase 5 — Build Tests: npm install/build/prod with no errors (if any frontend build exists).', 'done' => 0),
                array('id' => 'qa-5-js', 'text' => 'Phase 5 — JavaScript Tests: Jest/Vitest for components, utilities, frontend logic.', 'done' => 0),
                array('id' => 'qa-6-e2e', 'text' => 'Phase 6 — E2E Tests (Playwright/Cypress): homepage → register → login → purchase → checkout → payment → logout.', 'done' => 0),
                array('id' => 'qa-6-woo', 'text' => 'Phase 6 — WooCommerce Tests: cart, coupons, taxes, shipping, payment, refunds, emails, inventory, orders.', 'done' => 0),
                array('id' => 'qa-6-pmpro', 'text' => 'Phase 6 — PMPro Tests: signup → membership level assignment → login redirect → billing.', 'done' => 0),
                array('id' => 'qa-6-ghbc', 'text' => 'Phase 6 — GhBC Tests: test connection (Claude), chat round-trip, form submission.', 'done' => 0),
                array('id' => 'qa-6-multisite', 'text' => 'Phase 6 — Multisite Tests (if applicable): site creation, user roles, network activation.', 'done' => 0),
                array('id' => 'qa-6-i18n', 'text' => 'Phase 6 — Localization Tests: translation, RTL, language switching.', 'done' => 0),
                array('id' => 'qa-7-backup', 'text' => 'Phase 7 — Backup & Restore Tests: backup, restore, DB import, media integrity.', 'done' => 0),
                array('id' => 'qa-7-deploy', 'text' => 'Phase 7 — Deployment Simulation: git pull, cache clear, activate/deactivate plugin, rollback.', 'done' => 0),
                array('id' => 'qa-7-regression', 'text' => 'Phase 7 — Regression Tests: forms submit, menus work, login/logout, templates render.', 'done' => 0),
                array('id' => 'qa-8-smoke', 'text' => 'Phase 8 — Manual Smoke Test (final walkthrough): homepage, nav, login/logout, forms, search, admin, media upload, plugin toggle, business workflows.', 'done' => 0),
                array('id' => 'qa-8-uat', 'text' => 'Phase 8 — UAT: stakeholder + business sign-off on staging before promotion.', 'done' => 0),
                array('id' => 'qa-9-preprod', 'text' => 'Phase 9 — Pre-Production: production backup, rollback package, maintenance window, monitoring, deployment approval, release tag.', 'done' => 0),
                array('id' => 'qa-10-post', 'text' => 'Phase 10 — Production: deploy, DB migration, clear caches, warm cache, health check, smoke test, verify payments/forms/login/search/APIs.', 'done' => 0),
                array('id' => 'qa-10-monitor', 'text' => 'Phase 10 — Post-Production: real-time monitoring (errors, performance, security, uptime), analytics verification, backup verification, customer feedback → bug tickets.', 'done' => 0),
            );
        }
        if ($list === 'gates') {
            return array(
                array('id' => 'gate-1', 'text' => 'Gate 1 — Code compiles successfully (PHP lint, no parse/fatal errors).', 'done' => 0),
                array('id' => 'gate-2', 'text' => 'Gate 2 — Static analysis passes (PHPStan).', 'done' => 0),
                array('id' => 'gate-3', 'text' => 'Gate 3 — Coding standards pass (PHPCS/WPCS).', 'done' => 0),
                array('id' => 'gate-4', 'text' => 'Gate 4 — Unit tests pass (100% required tests).', 'done' => 0),
                array('id' => 'gate-5', 'text' => 'Gate 5 — Integration tests pass.', 'done' => 0),
                array('id' => 'gate-6', 'text' => 'Gate 6 — E2E tests pass (Playwright/Cypress).', 'done' => 0),
                array('id' => 'gate-7', 'text' => 'Gate 7 — No critical security findings.', 'done' => 0),
                array('id' => 'gate-8', 'text' => 'Gate 8 — Performance budget met.', 'done' => 0),
                array('id' => 'gate-9', 'text' => 'Gate 9 — Accessibility requirements met.', 'done' => 0),
                array('id' => 'gate-10', 'text' => 'Gate 10 — UAT approved.', 'done' => 0),
                array('id' => 'gate-11', 'text' => 'Gate 11 — Production deployment approved.', 'done' => 0),
                array('id' => 'gate-12', 'text' => 'Gate 12 — Post-deployment health checks pass.', 'done' => 0),
            );
        }
        if ($list === 'precheck') {
            return array(
                array('id' => 'ps-general', 'text' => 'General Settings: site title/tagline, timezone (Africa/Accra), date & time format.', 'done' => 0),
                array('id' => 'ps-permalinks', 'text' => 'Permalinks: pretty (Post name) enabled — no plain query-string URLs.', 'done' => 0),
                array('id' => 'ps-woo', 'text' => 'WooCommerce Settings: store address, currency (GHS), tax & unit preferences.', 'done' => 0),
                array('id' => 'ps-woopay', 'text' => 'WooCommerce payments: enable/configure gateways (Stripe / MTN MoMo) and run a test checkout.', 'done' => 0),
                array('id' => 'ps-wooshipping', 'text' => 'WooCommerce shipping zones & methods configured.', 'done' => 0),
                array('id' => 'ps-pmpro', 'text' => 'PMPro membership levels: name, price, billing cycle, currency (GHS).', 'done' => 0),
                array('id' => 'ps-pmpropay', 'text' => 'PMPro payment settings: gateway, test/live mode, checkout page.', 'done' => 0),
                array('id' => 'ps-ghbc', 'text' => 'GhBC LLM: API key verified via Test Connection (provider is Claude).', 'done' => 0),
                array('id' => 'ps-email', 'text' => 'Email: from name/address and SMTP/transactional mail (gabochie-email/Brevo) configured + test email delivered.', 'done' => 0),
                array('id' => 'ps-seo', 'text' => 'SEO: Yoast site meta, social profiles, sitemap, robots.txt verified.', 'done' => 0),
                array('id' => 'ps-analytics', 'text' => 'Analytics: Site Kit / Google Analytics / Search Console connected.', 'done' => 0),
                array('id' => 'ps-push', 'text' => 'Web push: WonderPush configured + test notification sent.', 'done' => 0),
                array('id' => 'ps-cache', 'text' => 'LiteSpeed Cache: purge + verify caching works on staging.', 'done' => 0),
                array('id' => 'ps-backup', 'text' => 'Backups: full staging backup/DB export saved before Go Live.', 'done' => 0),
                array('id' => 'ps-security', 'text' => 'Security: strong admin passwords, two-factor, login protection.', 'done' => 0),
                array('id' => 'ps-dns', 'text' => 'DNS/Email deliverability: SPF/DKIM for no-reply@gabochiedesign.com in cPanel.', 'done' => 0),
                array('id' => 'cpanel-db', 'text' => 'cPanel: create DB gabozvqd_gaboch_live + user gabozvqd_gaboch_live in MySQL Databases, grant ALL PRIVILEGES — preflight blocks until done.', 'done' => 0),
                array('id' => 'cpanel-mailbox', 'text' => 'cPanel: create email account no-reply@gabochiedesign.com so transactional mail has a real mailbox.', 'done' => 0),
                array('id' => 'cpanel-dns', 'text' => 'cPanel: add SPF + DKIM + DMARC records for gabochiedesign.com (SPF include server178.web-hosting.com).', 'done' => 0),
                array('id' => 'cpanel-ssl', 'text' => 'cPanel: confirm AutoSSL/HTTPS is active for gabochiedesign.com.', 'done' => 0),
                array('id' => 'cpanel-php', 'text' => 'cPanel: confirm PHP 8.3 version on the live docroot matches staging.', 'done' => 0),
                array('id' => 'cpanel-backup', 'text' => 'cPanel: take a full account backup before Go Live.', 'done' => 0),
            );
        }
        return array(
            array('id' => 'cleanup', 'text' => 'Staging cleaned to professional standard: 50 pages (dupes/test/drafts deleted), blogname, from-email, canonical My Account page.', 'done' => 1),
            array('id' => 'ghbc', 'text' => 'GhBC providers updated (Claude/OpenAI/Gemini/Mistral/Grok/Ollama, LM Studio removed) — deployed as commit f9cbdc0f.', 'done' => 1),
            array('id' => 'maint', 'text' => 'Maintenance-mode plugin deactivated on staging so it cannot auto-activate on live.', 'done' => 1),
            array('id' => 'db', 'text' => 'Create live DB gabozvqd_gaboch_live + user gabozvqd_gaboch_live in cPanel (MySQL Databases) — preflight blocks until done.', 'done' => 0),
            array('id' => 'ghbckey', 'text' => 'Verify GhBC API key: GhBC Settings → LLM Settings → Test Connection (provider is Claude).', 'done' => 0),
            array('id' => 'spf', 'text' => 'Optional: configure SPF/DKIM for no-reply@gabochiedesign.com so PMPro/transactional email delivers.', 'done' => 0),
            array('id' => 'dry', 'text' => 'Run Go Live → Dry run and review the preflight report.', 'done' => 0),
            array('id' => 'golive', 'text' => 'Run Go Live now (backs up live files + DB, syncs staging DB + code + uploads, smoke-tests live).', 'done' => 0),
            array('id' => 'verify', 'text' => 'Verify live smoke test passed and homepage/blog images load.', 'done' => 0),
            array('id' => 'tempadmin', 'text' => 'Delete temporary admin gabochie-test from staging after launch.', 'done' => 0),
            array('id' => 'agents', 'text' => 'Update AGENTS.md with production status once live is verified.', 'done' => 0),
        );
    }

    public static function checklist_save($items, $list = 'launch') {
        update_option(self::checklist_option($list), $items, false);
    }

    public static function checklist_render_html($list = 'launch') {
        $items = self::checklist_get($list);
        $done = 0;
        foreach ($items as $i) {
            if (!empty($i['done'])) $done++;
        }
        $total = count($items);
        $pct = $total ? (int) round(100 * $done / $total) : 0;
        $list_attr = esc_attr($list);

        if ($list === 'qa') {
            return self::render_qa_roadmap_html($items, $done, $total, $pct);
        }

        $out = '<div class="gol-check-progress"><span>Progress: ' . $done . ' / ' . $total . ' (' . $pct . '%)</span>'
             . '<div style="background:#e5e5e5;border-radius:99px;height:10px;margin-top:6px;max-width:520px;"><div style="background:#46b450;height:10px;border-radius:99px;width:' . $pct . '%;"></div></div></div>';
        $out .= '<div id="gol-' . $list_attr . '-items">';
        foreach ($items as $i) {
            $out .= self::checklist_item_html($i, $list_attr);
        }
        $out .= '</div>';
        return $out;
    }

    public static function render_qa_roadmap_html($items, $done, $total, $pct) {
        $groups = array();
        foreach ($items as $i) {
            $g = 'Other';
            if (preg_match('/^qa-(\d+)-/', $i['id'], $m)) {
                $n = (int) $m[1];
                $names = array(
                    1 => 'Phase 1 — Code Quality',
                    2 => 'Phase 2 — Unit / Integration / Functional',
                    3 => 'Phase 3 — Security',
                    4 => 'Phase 4 — Performance & Logs',
                    5 => 'Phase 5 — Browser, Responsive, Accessibility, SEO',
                    6 => 'Phase 6 — E2E, WooCommerce, PMPro, GhBC',
                    7 => 'Phase 7 — Backup, Deployment, Regression',
                    8 => 'Phase 8 — Manual Smoke + UAT',
                    9 => 'Phase 9 — Pre-Production',
                    10 => 'Phase 10 — Production & Post-Production',
                );
                $g = isset($names[$n]) ? $names[$n] : 'Other';
            }
            if (!isset($groups[$g])) $groups[$g] = array();
            $groups[$g][] = $i;
        }
        $out = '<div class="gol-check-progress"><span>Roadmap progress: ' . $done . ' / ' . $total . ' checks (' . $pct . '%)</span>'
             . '<div style="background:#e5e5e5;border-radius:99px;height:10px;margin-top:6px;max-width:520px;"><div style="background:#46b450;height:10px;border-radius:99px;width:' . $pct . '%;"></div></div></div>';
        $out .= '<div id="gol-qa-items">';
        $order = 1;
        foreach ($groups as $g => $gitems) {
            $gdone = 0;
            foreach ($gitems as $i) {
                if (!empty($i['done'])) $gdone++;
            }
            $gpct = $gitems ? (int) round(100 * $gdone / count($gitems)) : 0;
            $out .= '<h4 style="margin:18px 0 4px;font-size:14px;">' . esc_html($g)
                  . ' <span style="font-weight:400;color:#8c8f94;font-size:12px;">— ' . $gdone . ' / ' . count($gitems) . ' (' . $gpct . '%)</span></h4>';
            foreach ($gitems as $i) {
                $out .= self::checklist_item_html($i, 'qa');
            }
        }
        $out .= '</div>';
        return $out;
    }

    public static function checklist_item_html($i, $list_attr) {
        $cid = esc_attr($i['id']);
        $ctext = esc_html($i['text']);
        $cls = !empty($i['done']) ? 'gol-done' : '';
        $checked = !empty($i['done']) ? 'checked' : '';
        return '<label class="gol-item ' . $cls . '" style="display:flex;gap:8px;align-items:flex-start;padding:6px 4px;border-bottom:1px solid #f0f0f0;max-width:720px;">'
             . '<input type="checkbox" data-id="' . $cid . '" ' . $checked . ' style="margin-top:2px;">'
             . '<span style="flex:1;">' . $ctext . '</span>'
             . '<a href="#" class="gol-del" data-list="' . $list_attr . '" data-id="' . $cid . '" title="Remove item" style="text-decoration:none;color:#b32d2e;">&#10005;</a>'
             . '</label>';
    }

    public static function recommendations() {
        global $wpdb;
        $recs = array();

        $provider = get_option('ghbc_llm_provider', '');
        $key = get_option('ghbc_llm_key', '');
        if ($provider && $provider !== 'ollama') {
            if (empty($key)) {
                $recs[] = array(
                    'severity' => 'error',
                    'title' => 'GhBC LLM API key is missing',
                    'detail' => 'No API key stored for provider "' . $provider . '". Test Connection will fail and the chatbot will not work.',
                    'action' => admin_url('admin.php?page=ghbc-settings'),
                    'action_label' => 'Open GhBC Settings',
                );
            } elseif ($provider === 'claude' && strpos($key, 'sk-ant-') !== 0) {
                $recs[] = array(
                    'severity' => 'error',
                    'title' => 'GhBC Claude API key looks invalid',
                    'detail' => 'Stored key does not start with "sk-ant-", so Anthropic rejects it (server test returned "invalid x-api-key"). Paste a real Anthropic key.',
                    'action' => admin_url('admin.php?page=ghbc-settings'),
                    'action_label' => 'Open GhBC Settings',
                );
            }
        }

        $status = self::read_status();
        if ($status['status'] === 'preflight-fail') {
            $recs[] = array(
                'severity' => 'error',
                'title' => 'Live database not created yet',
                'detail' => 'promote.sh preflight fails: create DB gabozvqd_gaboch_live + user in cPanel > MySQL Databases, grant ALL PRIVILEGES, then run Dry run again.',
            );
        }

        $addr = get_option('woocommerce_store_address', '');
        if (empty($addr)) {
            $recs[] = array(
                'severity' => 'warning',
                'title' => 'WooCommerce store address is empty',
                'detail' => 'Set your store address + city in WooCommerce > Settings > General so invoices and tax baselines are correct.',
                'action' => admin_url('admin.php?page=wc-settings&tab=general'),
                'action_label' => 'Open WooCommerce Settings',
            );
        }
        if (get_option('woocommerce_calc_taxes') !== 'yes') {
            $recs[] = array(
                'severity' => 'info',
                'title' => 'WooCommerce taxes are off',
                'detail' => 'GHS store with taxes disabled. Enable tax calculation and add Ghana rates (VAT 15% + levies) if you charge tax.',
                'action' => admin_url('admin.php?page=wc-settings&tab=tax'),
                'action_label' => 'Open tax settings',
            );
        }

        $ep = get_option('woocommerce_expresspay_gateway_settings', array());
        if (!empty($ep['enabled']) && ($ep['enabled'] === 'yes' || $ep['enabled'] === true)) {
            $env = isset($ep['environment']) ? $ep['environment'] : 'production';
            if ($env === 'sandbox') {
                $recs[] = array(
                    'severity' => 'warning',
                    'title' => 'ExpressPay gateway is in sandbox mode',
                    'detail' => 'Payments are test-only. Switch to production credentials before launch or customers cannot pay you.',
                    'action' => admin_url('admin.php?page=wc-settings&tab=checkout&section=expresspay_gateway'),
                    'action_label' => 'Configure ExpressPay',
                );
            }
        }

        $levels = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pmpro_membership_levels");
        if ($levels === 0) {
            $recs[] = array(
                'severity' => 'error',
                'title' => 'PMPro has no membership levels',
                'detail' => 'No levels exist. Visitors cannot join. Create at least one level (price + billing cycle in GHS) before launch.',
                'action' => admin_url('admin.php?page=pmpro-membershiplevels'),
                'action_label' => 'Add PMPro levels',
            );
        }
        if (empty(get_option('pmpro_gateway', ''))) {
            $recs[] = array(
                'severity' => 'warning',
                'title' => 'PMPro payment gateway not set',
                'detail' => 'No gateway configured in PMPro Settings. Memberships cannot be paid for.',
                'action' => admin_url('admin.php?page=pmpro-paymentsettings'),
                'action_label' => 'Open PMPro payment settings',
            );
        }

        $host = parse_url(home_url(), PHP_URL_HOST);
        $apex = $host;
        if (substr_count((string) $host, '.') > 1) {
            $parts = explode('.', $host);
            array_shift($parts);
            $apex = implode('.', $parts);
        }
        $mx = function_exists('dns_get_record') ? @dns_get_record($apex, DNS_MX) : false;
        $zoho = false;
        if (is_array($mx)) {
            foreach ($mx as $m) {
                $t = isset($m['target']) ? $m['target'] : (isset($m['exchange']) ? $m['exchange'] : '');
                if (stripos($t, 'zoho') !== false) $zoho = true;
            }
        }
        if ($zoho) {
            $recs[] = array(
                'severity' => 'info',
                'title' => 'Email for gabochiedesign.com is hosted on Zoho',
                'detail' => 'MX points to Zoho, not cPanel. Create no-reply@gabochiedesign.com as a Zoho mailbox (or a transactional service like Brevo) and keep SPF/DKIM on Zoho + Cloudflare. A cPanel mailbox will not receive mail.',
            );
        }

        if (empty(get_option('gabochie_email_api_key', ''))) {
            $recs[] = array(
                'severity' => 'warning',
                'title' => 'Brevo API key is not set',
                'detail' => 'gabochie-email is the transactional sender, but no Brevo API key is saved, so forms and transactional email cannot send.',
                'action' => admin_url('options-general.php?page=gabochie-email'),
                'action_label' => 'Open Gabochie Email settings',
            );
        }

        if (!is_dir(WP_PLUGIN_DIR . '/litespeed-cache')) {
            $recs[] = array(
                'severity' => 'info',
                'title' => 'No LiteSpeed Cache plugin installed',
                'detail' => 'Caching is server-level LiteSpeed + Cloudflare (x-turbo-charged-by: LiteSpeed). The "purge cache" checklist item is not applicable without the plugin.',
            );
        }

        $modules = get_option('googlesitekit_active_modules', false);
        if (!$modules) {
            $recs[] = array(
                'severity' => 'info',
                'title' => 'Site Kit is not connected',
                'detail' => 'Google Analytics / Search Console are not linked. Connect Site Kit (needs a Google login) before or right after launch.',
                'action' => admin_url('admin.php?page=googlesitekit-splash'),
                'action_label' => 'Set up Site Kit',
            );
        }

        $sec_plugins = array('wordfence', 'all-in-one-wp-security', 'solid-security', 'limit-login-attempts-reloaded', 'google-authenticator', 'two-factor', 'wp-2fa');
        $active = get_option('active_plugins', array());
        $has_sec = false;
        foreach ($sec_plugins as $slug) {
            foreach ($active as $p) {
                if (stripos($p, $slug) !== false) { $has_sec = true; break 2; }
            }
        }
        if (!$has_sec) {
            $recs[] = array(
                'severity' => 'warning',
                'title' => 'No security or two-factor plugin active',
                'detail' => 'No login-protection/2FA plugin found. Add a security plugin (e.g. 2FA + limit-login) and use strong admin passwords before launch.',
                'action' => admin_url('plugin-install.php?s=two%20factor&tab=search&type=term'),
                'action_label' => 'Browse 2FA plugins',
            );
        }

        $smoke = self::last_staging_smoke();
        if (stripos($smoke, 'PASS') === false) {
            $recs[] = array(
                'severity' => 'error',
                'title' => 'Last staging smoke test did not pass',
                'detail' => 'promote.sh will refuse to go live until the latest deploy log shows "SMOKE TEST: PASS". Push the latest code again.',
            );
        }

        return $recs;
    }

    public static function recs_html($recs) {
        if (empty($recs)) {
            return '<div class="notice notice-success" style="max-width:720px;"><p><strong>All checks passed.</strong> No open recommendations.</p></div>';
        }
        $sev = array(
            'error'   => array('#d63638', 'Error'),
            'warning' => array('#dba617', 'Warning'),
            'info'    => array('#2271b1', 'Note'),
        );
        $out = '<div style="max-width:720px;">';
        foreach ($recs as $r) {
            $s = isset($sev[$r['severity']]) ? $sev[$r['severity']] : $sev['info'];
            $out .= '<div style="display:flex;gap:10px;padding:10px 12px;border:1px solid #c3c4c7;border-left:4px solid ' . $s[0] . ';background:#fff;margin-bottom:8px;border-radius:4px;align-items:flex-start;">'
                  . '<span style="font-weight:600;color:' . $s[0] . ';white-space:nowrap;font-size:12px;text-transform:uppercase;margin-top:2px;">' . $s[1] . '</span>'
                  . '<div style="flex:1;"><strong>' . esc_html($r['title']) . '</strong><div style="color:#50575e;margin-top:2px;">' . esc_html($r['detail']) . '</div>';
            if (!empty($r['action'])) {
                $out .= '<div style="margin-top:6px;"><a class="button button-small" href="' . esc_url($r['action']) . '" target="_blank" rel="noopener">' . esc_html($r['action_label']) . '</a></div>';
            }
            $out .= '</div></div>';
        }
        $out .= '</div>';
        return $out;
    }

    public static function render_recommendations_html() {
        return self::recs_html(self::recommendations());
    }

    public static function tabs($current = 'golive') {
        $tabs = array(
            'golive'  => array('Go Live', admin_url('admin.php?page=gabochie-go-live')),
            'compare' => array('Comparison', admin_url('admin.php?page=gabochie-go-live-compare')),
            'qa'      => array('Release QA', admin_url('admin.php?page=gabochie-go-live-qa')),
            'score'   => array('Release Score', admin_url('admin.php?page=gabochie-go-live-score')),
        );
        $out = '<h2 class="nav-tab-wrapper" style="border-bottom:1px solid #c3c4c7;margin-bottom:14px;">';
        foreach ($tabs as $key => $t) {
            $cls = $key === $current ? 'nav-tab nav-tab-active' : 'nav-tab';
            $out .= '<a class="' . $cls . '" href="' . esc_url($t[1]) . '">' . esc_html($t[0]) . '</a>';
        }
        $out .= '</h2>';
        return $out;
    }

    public static function shared_js() {
        return '/* Gabochie Go Live shared state (dynamic tabs) */'
            . 'window.GOLC = (function ($) {'
            . 'var api = { ajaxUrl: ' . wp_json_encode(admin_url('admin-ajax.php')) . ', nonce: ' . wp_json_encode(wp_create_nonce('gabochie_go_live_nonce')) . ' };'
            . 'function touch(){ try { localStorage.setItem("gol-touch", String(Date.now())); } catch (e) {} }'
            . 'function loadRecs(){ var $b = $("#gol-recs"); if (!$b.length) return; $.post(api.ajaxUrl, {action: "gabochie_recs_get", _wpnonce: api.nonce}, function (r) { if (r && r.success && r.data && r.data.html) $b.html(r.data.html); }); }'
            . 'function loadCmp(){ var $t = $("#gol-cmp"); if (!$t.length) return; $.post(api.ajaxUrl, {action: "gabochie_compare_get", _wpnonce: api.nonce}, function (r) { if (r && r.success && r.data) { if (r.data.table) $t.html(r.data.table); if (r.data.recs) $("#gol-recs").html(r.data.recs); } }); }'
            . 'function runProbe(){ var $o = $("#gol-probe-out"); if (!$o.length) return; $o.text("running\u2026"); $.post(api.ajaxUrl, {action: "gabochie_compare_probe", _wpnonce: api.nonce}, function (r) { $o.text(r && r.success && r.data ? r.data.output : "probe failed"); }); }'
            . '$(function () {'
            . 'if ($("#gol-recs-refresh").length) $("#gol-recs-refresh").on("click", function (e) { e.preventDefault(); loadRecs(); });'
            . 'if ($("#gol-cmp-refresh").length) $("#gol-cmp-refresh").on("click", function (e) { e.preventDefault(); loadCmp(); });'
            . 'if ($("#gol-probe").length) $("#gol-probe").on("click", function (e) { e.preventDefault(); runProbe(); });'
            . 'window.addEventListener("storage", function (ev) { if (ev.key === "gol-touch") { if (window.__golReloadAll) window.__golReloadAll(); else loadRecs(); } });'
            . 'window.addEventListener("focus", function () { loadRecs(); });'
            . 'setInterval(loadRecs, 30000);'
            . '});'
            . 'return { touch: touch, loadRecs: loadRecs };'
            . '})(jQuery);';
    }

    public static function http_json($url) {
        $res = wp_remote_get($url, array('timeout' => 20, 'sslverify' => false, 'redirection' => 3));
        if (is_wp_error($res)) return null;
        $code = (int) wp_remote_retrieve_response_code($res);
        if ($code < 200 || $code >= 300) return null;
        $body = wp_remote_retrieve_body($res);
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    public static function rest_total($url) {
        $res = wp_remote_get($url, array('timeout' => 20, 'sslverify' => false, 'redirection' => 3));
        if (is_wp_error($res)) return false;
        $code = (int) wp_remote_retrieve_response_code($res);
        if ($code < 200 || $code >= 300) return false;
        $h = wp_remote_retrieve_headers($res);
        try {
            $tot = isset($h['x-wp-total']) ? $h['x-wp-total'] : null;
        } catch (\Exception $e) {
            $tot = null;
        }
        if (is_array($tot)) $tot = reset($tot);
        return is_numeric($tot) ? (int) $tot : null;
    }

    public static function page_title($url) {
        $res = wp_remote_get($url, array('timeout' => 20, 'sslverify' => false, 'redirection' => 5));
        if (is_wp_error($res)) return 'fetch failed';
        $body = wp_remote_retrieve_body($res);
        if (preg_match('/<title>(.*?)<\/title>/is', $body, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        }
        return 'no <title>';
    }

    public static function compare_data() {
        $live_url = 'https://gabochiedesign.com';
        $rows = array();

        $idx = self::http_json($live_url . '/wp-json/');
        $rows[] = array('Site name', get_bloginfo('name'), is_array($idx) && isset($idx['name']) ? $idx['name'] : 'n/a');
        $rows[] = array('Tagline', get_bloginfo('description'), is_array($idx) && isset($idx['description']) ? $idx['description'] : 'n/a');

        $pages_live = self::rest_total($live_url . '/wp-json/wp/v2/pages?per_page=1&status=publish');
        $posts_live = self::rest_total($live_url . '/wp-json/wp/v2/posts?per_page=1&status=publish');
        $media_live = self::rest_total($live_url . '/wp-json/wp/v2/media?per_page=1');

        global $wpdb;
        $pages_stage = (int) wp_count_posts('page')->publish;
        $posts_stage = (int) wp_count_posts('post')->publish;
        $media_stage = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='attachment' AND post_status NOT IN ('trash','auto-draft')");

        $rows[] = array('Published pages', $pages_stage, is_int($pages_live) ? $pages_live : 'n/a');
        $rows[] = array('Published posts', $posts_stage, is_int($posts_live) ? $posts_live : 'n/a');
        $rows[] = array('Media files', $media_stage, is_int($media_live) ? $media_live : 'n/a');

        $rows[] = array('Homepage title', self::page_title(home_url('/')), self::page_title($live_url . '/'));

        return $rows;
    }

    public static function comparison_recommendations() {
        $recs = array();
        $map = array();
        foreach (self::compare_data() as $r) {
            $map[$r[0]] = array($r[1], $r[2]);
        }
        $check = function ($metric) use ($map) {
            if (!isset($map[$metric])) return false;
            $l = $map[$metric][1];
            if ($l === 'n/a' || $l === 'fetch failed' || $l === 'no <title>') return false;
            return (string) $map[$metric][0] !== (string) $map[$metric][1];
        };

        if ($check('Site name')) {
            $recs[] = array(
                'severity' => 'info',
                'title' => 'Site name differs between staging and live',
                'detail' => 'Staging: "' . $map['Site name'][0] . '" vs live: "' . $map['Site name'][1] . '". After Go Live, live will use the staging name.',
            );
        }
        if ($check('Tagline')) {
            $recs[] = array(
                'severity' => 'info',
                'title' => 'Tagline differs (intentional)',
                'detail' => 'Staging: "' . $map['Tagline'][0] . '" vs live: "' . $map['Tagline'][1] . '". Left to the owner to confirm before launch.',
            );
        }
        if ($check('Published pages')) {
            $recs[] = array(
                'severity' => 'warning',
                'title' => 'Page counts differ',
                'detail' => 'Staging has ' . $map['Published pages'][0] . ' published pages; live has ' . $map['Published pages'][1] . '. The cleaned staging set will replace the live set on Go Live.',
            );
        }
        if ($check('Published posts')) {
            $recs[] = array(
                'severity' => 'info',
                'title' => 'Post counts differ',
                'detail' => 'Staging ' . $map['Published posts'][0] . ' vs live ' . $map['Published posts'][1] . '. Staging is the content source of truth.',
            );
        }
        if ($check('Media files')) {
            $recs[] = array(
                'severity' => 'info',
                'title' => 'Media counts differ',
                'detail' => 'Staging ' . $map['Media files'][0] . ' vs live ' . $map['Media files'][1] . '. Uploads are copied staging&rarr;live additively, so live keeps any extra files.',
            );
        }
        if ($check('Homepage title')) {
            $recs[] = array(
                'severity' => 'warning',
                'title' => 'Homepage title differs',
                'detail' => 'Staging: "' . $map['Homepage title'][0] . '" vs live: "' . $map['Homepage title'][1] . '". Confirm this is expected before launch.',
            );
        }
        return $recs;
    }

    public static function render_compare_html() {
        $rows = self::compare_data();
        $out = '<table class="widefat striped" style="max-width:760px;"><thead><tr><th style="width:32%;">Metric</th><th>Staging</th><th>Live</th><th style="width:14%;">Status</th></tr></thead><tbody>';
        $same = 0;
        $diff = 0;
        foreach ($rows as $r) {
            list($metric, $s, $l) = $r;
            $equal = (string) $s === (string) $l;
            $cls = $equal ? '#0a7d32' : '#dba617';
            $badge = $equal ? 'Match' : 'Differs';
            if ($equal) $same++; else $diff++;
            $out .= '<tr><td><strong>' . esc_html($metric) . '</strong></td><td>' . esc_html((string) $s) . '</td><td>' . esc_html((string) $l) . '</td><td><span style="color:' . $cls . ';font-weight:600;">' . $badge . '</span></td></tr>';
        }
        $out .= '</tbody></table>';
        $out .= '<p class="description">' . $same . ' match, ' . $diff . ' differ. Staging is the source of truth for content &mdash; after Go Live, live will reflect the staging value for each "Differs" row. The tagline difference is intentional (left to the owner).</p>';
        return $out;
    }

    public static function render_compare() {
        if (!current_user_can('manage_options')) return;
        $staging_url = home_url();
        $live_url = 'https://gabochiedesign.com';
        ?>
        <div class="wrap">
            <?php echo self::tabs('compare'); ?>
            <h1>Staging vs Live &mdash; Comparison</h1>
            <p>Read-only comparison between staging (<code><?php echo esc_html($staging_url); ?></code>) and production (<code><?php echo esc_html($live_url); ?></code>). The matrix probes public REST + HTML endpoints; nothing is written. Staging is the content source of truth &mdash; on Go Live the live DB/code is replaced with staging&rsquo;s.</p>

            <h2>Smart Recommendations <button class="button button-small" id="gol-recs-refresh">Refresh</button></h2>
            <p class="description">General health checks plus staging-vs-live divergences. Fix Errors/Warnings before the push.</p>
            <div id="gol-recs">
                <?php echo self::recs_html(array_merge(self::recommendations(), self::comparison_recommendations())); ?>
            </div>

            <h2>Comparison matrix <button class="button button-small" id="gol-cmp-refresh">Refresh</button></h2>
            <div id="gol-cmp">
                <?php echo self::render_compare_html(); ?>
            </div>

            <h2>Full server probe <button class="button button-small" id="gol-probe">Run review-probe.sh</button></h2>
            <p class="description">Read-only shell probe (server-side <code>~/deploy/review-probe.sh</code>) that compares DBs, plugins and uploads directly. Runs on demand; output is informational.</p>
            <pre id="gol-probe-out" style="background:#0d1117;color:#c9d1d9;padding:12px;border-radius:6px;max-height:480px;overflow:auto;font-size:12px;">Click "Run" to load the full probe output.</pre>
        </div>

        <script><?php echo self::shared_js(); ?></script>
        <?php
    }

    public static function ajax_recs_get() {
        check_ajax_referer('gabochie_go_live_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'), 403);
        wp_send_json_success(array('html' => self::render_recommendations_html()));
    }

    public static function ajax_compare_get() {
        check_ajax_referer('gabochie_go_live_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'), 403);
        wp_send_json_success(array(
            'table' => self::render_compare_html(),
            'recs' => self::recs_html(array_merge(self::recommendations(), self::comparison_recommendations())),
        ));
    }

    public static function ajax_compare_probe() {
        check_ajax_referer('gabochie_go_live_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'), 403);
        $out = 'Shell probe not available (shell_exec disabled).';
        if (self::shell_ok()) {
            $script = self::deploy_dir() . '/review-probe.sh';
            if (file_exists($script)) {
                $out = self::run('PATH=/usr/local/sbin:/usr/local/bin:/usr/bin:/bin bash ' . escapeshellarg($script));
            } else {
                $out = 'review-probe.sh not found at ' . $script;
            }
        }
        wp_send_json_success(array('output' => $out));
    }

    public static function render_qa() {
        if (!current_user_can('manage_options')) return;

        $staging_url = home_url();
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('gabochie_go_live_nonce');
        $commit = self::staging_commit();
        $smoke = self::last_staging_smoke();
        ?>
        <div class="wrap">
            <?php echo self::tabs('qa'); ?>
            <h1>Release QA &mdash; Enterprise-Grade Test Roadmap</h1>
            <p>Comprehensive testing plan mirrored from <code>enterpris grade testing.txt</code>, for <strong><?php echo esc_html($staging_url); ?></strong> before promotion to production (<code>https://gabochiedesign.com</code>). Work the phases in order (1&rarr;10), then confirm each quality gate passes before running Go Live.</p>

            <table class="widefat striped" style="max-width:720px;">
                <tbody>
                    <tr><th>Deployed staging commit</th><td><code><?php echo esc_html($commit); ?></code></td></tr>
                    <tr><th>Last staging smoke test</th><td><?php echo esc_html($smoke); ?></td></tr>
                    <tr><th>Auto-deploy runner</th><td><code>app\_dev-tools\deploy\deploy.ps1</code> (push + smoke test)</td></tr>
                </tbody>
            </table>

            <h2>Smart Recommendations <button class="button button-small" id="gol-recs-refresh">Refresh</button></h2>
            <p class="description">Auto-generated from the current staging state. Fix the Errors and Warnings below, then work the roadmap and gates.</p>
            <div id="gol-recs">
                <?php echo self::render_recommendations_html(); ?>
            </div>

            <h2>12 Quality Gates</h2>
            <p class="description">Nothing promotes to the next stage unless the current gate passes. Gates 1&ndash;9 are automated/test layers; Gate 10 requires stakeholder approval; Gate 11 is your manual approval to push; Gate 12 verifies post-deploy health.</p>
            <div id="gol-gates">
                <?php echo self::checklist_render_html('gates'); ?>
            </div>

            <h2>Chronological Test Roadmap</h2>
            <p class="description">Phase 1&rarr;10 in the order the spec prescribes, ending with production + post-production. Check items off as each test is run. The phases are grouped below in chronological order.</p>
            <div id="gol-qa">
                <?php echo self::checklist_render_html('qa'); ?>
            </div>
        </div>

        <style>.gol-item.gol-done span{text-decoration:line-through;color:#8c8f94;}</style>
        <script>
        (function ($) {
            var GOL = {
                ajaxUrl: <?php echo wp_json_encode($ajax_url); ?>,
                nonce: <?php echo wp_json_encode($nonce); ?>
            };

            function loadList(list) {
                $.post(GOL.ajaxUrl, { action: 'gabochie_checklist_get', _wpnonce: GOL.nonce, list: list }, function (r) {
                    if (!r || !r.success || !r.data || !r.data.html) return;
                    $('#gol-' + list).html(r.data.html);
                    $('#gol-' + list + ' input[type=checkbox]').on('change', function () { toggleItem(list, this); });
                    $('#gol-' + list + ' .gol-del').on('click', delItem);
                }).fail(function () {});
            }

            function toggleItem(list, el) {
                var cb = $(el), id = cb.data('id');
                cb.prop('disabled', true);
                $.post(GOL.ajaxUrl, { action: 'gabochie_checklist_toggle', _wpnonce: GOL.nonce, list: list, id: id, done: cb.is(':checked') ? 1 : 0 }, function (r) {
                    loadList(list);
                    try { localStorage.setItem('gol-touch', String(Date.now())); } catch (e) {}
                }).fail(function () { cb.prop('disabled', false); });
            }

            function delItem(e) {
                e.preventDefault();
                var list = $(this).data('list') || 'qa';
                var id = $(this).data('id');
                if (!window.confirm('Remove this checklist item?')) return;
                $.post(GOL.ajaxUrl, { action: 'gabochie_checklist_delete', _wpnonce: GOL.nonce, list: list, id: id }, function (r) {
                    loadList(list);
                    try { localStorage.setItem('gol-touch', String(Date.now())); } catch (e2) {}
                });
            }

            loadList('gates');
            loadList('qa');
            window.__golReloadAll = function () { loadList('gates'); loadList('qa'); };
        })(jQuery);
        </script>
        <script><?php echo self::shared_js(); ?></script>
        <?php
    }

    public static function render() {
        if (!current_user_can('manage_options')) return;

        if (!self::is_staging()) {
            echo '<div class="wrap"><h1>Go Live</h1><div class="notice notice-warning"><p>This button belongs on the <strong>staging</strong> site (wp.gabochiedesign.com). It has been disabled here.</p></div></div>';
            return;
        }

        if (!self::shell_ok()) {
            echo '<div class="wrap"><h1>Go Live</h1><div class="notice notice-error"><p>shell_exec is disabled on this server, so the Go Live button cannot run. Ask the host to enable it.</p></div></div>';
            return;
        }

        $status = self::read_status();
        $smoke = self::last_staging_smoke();
        $commit = self::staging_commit();
        $staging_url = home_url();
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('gabochie_go_live_nonce');
        $gol_dir = self::deploy_dir();
        ?>
        <div class="wrap">
            <?php echo self::tabs('golive'); ?>
            <h1>Go Live &mdash; Push Staging to Production</h1>
            <p>Copies <strong>staging</strong> (<code><?php echo esc_html($staging_url); ?></code>) to <strong>production</strong> (<code>https://gabochiedesign.com</code>): code + database, with backups and an automatic smoke test.</p>

            <table class="widefat striped" style="max-width:720px;">
                <tbody>
                    <tr><th>Deployed staging commit</th><td><code><?php echo esc_html($commit); ?></code></td></tr>
                    <tr><th>Last staging smoke test</th><td><?php echo esc_html($smoke); ?></td></tr>
                    <tr><th>Last promote result</th><td id="gol-status"><?php echo esc_html($status['status'] . ' — ' . $status['message']); ?></td></tr>
                </tbody>
            </table>

            <style>.gol-item.gol-done span{text-decoration:line-through;color:#8c8f94;}</style>
            <h2>Release QA <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=gabochie-go-live-qa')); ?>" style="margin-left:8px;">Open Test Roadmap &amp; Quality Gates</a></h2>
            <p class="description">Enterprise-grade test roadmap (Phases 1&ndash;10) and the 12 quality gates. Work them in order before the push.</p>
            <h2>Smart Recommendations <button class="button button-small" id="gol-recs-refresh">Refresh</button></h2>
            <p class="description">Auto-generated from the current staging state. Fix the Errors and Warnings below, then work the Pre-Sync Configuration checklist.</p>
            <div id="gol-recs">
                <?php echo self::render_recommendations_html(); ?>
            </div>

            <h2>Pre-Sync Configuration</h2>
            <p class="description">Settings to finish on staging before the push so live goes out fully configured (WooCommerce, PMPro, GhBC, email, SEO, analytics, cache, security…). Check items off as you complete them; add your own with the box below.</p>
            <div id="gol-precheck">
                <?php echo self::checklist_render_html('precheck'); ?>
                <p style="margin-top:10px;">
                    <input type="text" id="gol-new-precheck" placeholder="Add a pre-sync config item…" style="width:420px;max-width:100%;">
                    <button class="button" id="gol-add-precheck">Add</button>
                </p>
            </div>

            <h2>Launch Checklist</h2>
            <p class="description">Recommendations and steps for a clean launch. Check items off as you complete them; add your own with the box below.</p>
            <div id="gol-launch">
                <?php echo self::checklist_render_html('launch'); ?>
                <p style="margin-top:10px;">
                    <input type="text" id="gol-new-launch" placeholder="Add a checklist item…" style="width:420px;max-width:100%;">
                    <button class="button" id="gol-add-launch">Add</button>
                </p>
            </div>

            <h2>Actions</h2>
            <p>
                <button class="button" id="gol-dry">Dry run (preflight check)</button>
                <button class="button button-primary" id="gol-real">Go Live now</button>
            </p>
            <p class="description">"Go Live now" backs up the current production site (files + database + wp-config), syncs the staging database into the new production DB, deploys the code, then smoke-tests the live site. It refuses to run unless the last staging smoke test passed.</p>

            <h2>Promote log (tail)</h2>
            <pre id="gol-log" style="background:#0d1117;color:#c9d1d9;padding:12px;border-radius:6px;max-height:380px;overflow:auto;font-size:12px;">loading…</pre>
        </div>

        <script>
        (function ($) {
            var GOL = {
                ajaxUrl: <?php echo wp_json_encode($ajax_url); ?>,
                nonce: <?php echo wp_json_encode($nonce); ?>,
                dir: <?php echo wp_json_encode($gol_dir); ?>,
                busy: false,
                poller: null
            };

            function refresh() {
                $.post(GOL.ajaxUrl, { action: 'gabochie_go_live_status', _wpnonce: GOL.nonce }, function (r) {
                    if (!r || !r.success || !r.data) return;
                    var s = r.data.status || {};
                    var msg = (s.status || 'unknown') + ' — ' + (s.message || '');
                    $('#gol-status').text(msg);
                    if (r.data.log) $('#gol-log').text(r.data.log);
                }).fail(function () {});
            }

            function loadChecklist(list) {
                $.post(GOL.ajaxUrl, { action: 'gabochie_checklist_get', _wpnonce: GOL.nonce, list: list }, function (r) {
                    if (!r || !r.success || !r.data || !r.data.html) return;
                    $('#gol-' + list).html(r.data.html + '<p style="margin-top:10px;"><input type="text" id="gol-new-' + list + '" placeholder="Add a checklist item…" style="width:420px;max-width:100%;"><button class="button" id="gol-add-' + list + '">Add</button></p>');
                    $('#gol-add-' + list).on('click', function () { addItem(list); });
                    $('#gol-new-' + list).on('keydown', function (e) { if (e.which === 13) addItem(list); });
                    $('#gol-' + list + '-items input[type=checkbox]').on('change', function () { toggleItem(list, this); });
                    $('#gol-' + list + '-items .gol-del').on('click', delItem);
                }).fail(function () {});
            }

            function toggleItem(list, el) {
                var cb = $(el), id = cb.data('id');
                cb.prop('disabled', true);
                $.post(GOL.ajaxUrl, { action: 'gabochie_checklist_toggle', _wpnonce: GOL.nonce, list: list, id: id, done: cb.is(':checked') ? 1 : 0 }, function (r) {
                    loadChecklist(list);
                    try { localStorage.setItem('gol-touch', String(Date.now())); } catch (e) {}
                }).fail(function () { cb.prop('disabled', false); });
            }

            function addItem(list) {
                var text = $('#gol-new-' + list).val().trim();
                if (!text) return;
                $('#gol-new-' + list).val('');
                $.post(GOL.ajaxUrl, { action: 'gabochie_checklist_add', _wpnonce: GOL.nonce, list: list, text: text }, function (r) {
                    loadChecklist(list);
                    try { localStorage.setItem('gol-touch', String(Date.now())); } catch (e) {}
                });
            }

            function delItem(e) {
                e.preventDefault();
                var list = $(this).data('list') || 'launch';
                var id = $(this).data('id');
                if (!window.confirm('Remove this checklist item?')) return;
                $.post(GOL.ajaxUrl, { action: 'gabochie_checklist_delete', _wpnonce: GOL.nonce, list: list, id: id }, function (r) {
                    loadChecklist(list);
                    try { localStorage.setItem('gol-touch', String(Date.now())); } catch (e2) {}
                });
            }

            function launch(mode) {
                if (GOL.busy) return;
                GOL.busy = true;
                $('#gol-dry, #gol-real').prop('disabled', true);
                refresh();
                $.post(GOL.ajaxUrl, { action: 'gabochie_go_live_launch', _wpnonce: GOL.nonce, mode: mode }, function (r) {
                    if (!r || !r.success || !r.data) {
                        $('#gol-status').text('Failed to launch: ' + (r && r.data && r.data.message ? r.data.message : 'unknown'));
                        GOL.busy = false;
                        $('#gol-dry, #gol-real').prop('disabled', false);
                        return;
                    }
                    $('#gol-status').text('running — ' + (r.data.message || 'Go Live started'));
                    GOL.poller = setInterval(function () {
                        $.post(GOL.ajaxUrl, { action: 'gabochie_go_live_status', _wpnonce: GOL.nonce }, function (rr) {
                            if (!rr || !rr.success || !rr.data) return;
                            var s = rr.data.status || {};
                            if (rr.data.log) $('#gol-log').text(rr.data.log);
                            if (s.status === 'running') {
                                $('#gol-status').text('running — ' + (s.message || 'Go Live in progress…'));
                                return;
                            }
                            clearInterval(GOL.poller);
                            GOL.poller = null;
                            $('#gol-status').text((s.status || 'done') + ' — ' + (s.message || ''));
                            GOL.busy = false;
                            $('#gol-dry, #gol-real').prop('disabled', false);
                            if (s.status === 'done') $('#gol-status').css('color', '#0a7d32');
                            else $('#gol-status').css('color', '#b32d2e');
                        });
                    }, 3000);
                }).fail(function () {
                    GOL.busy = false;
                    $('#gol-dry, #gol-real').prop('disabled', false);
                });
            }

            $('#gol-dry').on('click', function () { launch('dry'); });
            $('#gol-real').on('click', function () {
                if (!window.confirm('This pushes staging code + database to gabochiedesign.com (with backups). Continue?')) return;
                launch('real');
            });
            loadChecklist('launch');
            loadChecklist('precheck');
            refresh();
            setInterval(refresh, 15000);
            window.__golReloadAll = function () { refresh(); loadChecklist('launch'); loadChecklist('precheck'); };
        })(jQuery);
        </script>
        <script><?php echo self::shared_js(); ?></script>
        <?php
    }

    public static function ajax_launch() {
        check_ajax_referer('gabochie_go_live_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'), 403);
        if (!self::shell_ok()) wp_send_json_error(array('message' => 'shell_exec disabled'));

        $mode = isset($_POST['mode']) ? sanitize_key($_POST['mode']) : 'real';
        if ($mode !== 'dry' && $mode !== 'real') wp_send_json_error(array('message' => 'Bad mode'));

        $script = self::deploy_dir() . '/promote.sh';
        if (!file_exists($script)) wp_send_json_error(array('message' => 'promote.sh not found at ' . $script));

        $arg = $mode === 'dry' ? '1' : '0';
        $cmd = 'nohup bash ' . escapeshellarg($script) . ' ' . $arg . ' </dev/null >/dev/null 2>&1 & echo $!';
        $pid = self::run($cmd);

        wp_send_json_success(array(
            'message' => $mode === 'dry' ? 'Preflight check started.' : 'Go Live started.',
            'pid' => $pid,
        ));
    }

    public static function ajax_status() {
        check_ajax_referer('gabochie_go_live_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'), 403);

        $dir = self::deploy_dir();
        $log = '';
        $log_file = $dir . '/promote.log';
        if (file_exists($log_file)) {
            $lines = @file($log_file);
            if ($lines) {
                $tail = array_slice($lines, -25);
                $log = implode('', $tail);
            }
        }
        wp_send_json_success(array(
            'status' => self::read_status(),
            'log' => $log,
        ));
    }

    public static function requested_list() {
        $list = isset($_POST['list']) ? sanitize_key($_POST['list']) : 'launch';
        $allowed = array('launch', 'precheck', 'qa', 'gates');
        return in_array($list, $allowed, true) ? $list : 'launch';
    }

    public static function ajax_checklist_get() {
        check_ajax_referer('gabochie_go_live_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'), 403);
        wp_send_json_success(array('html' => self::checklist_render_html(self::requested_list())));
    }

    public static function ajax_checklist_toggle() {
        check_ajax_referer('gabochie_go_live_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'), 403);
        $list = self::requested_list();
        $id = sanitize_key($_POST['id'] ?? '');
        $done = !empty($_POST['done']) ? 1 : 0;
        $items = self::checklist_get($list);
        foreach ($items as $k => $i) {
            if ($i['id'] === $id) {
                $items[$k]['done'] = $done;
                self::checklist_save($items, $list);
                wp_send_json_success(array('html' => self::checklist_render_html($list)));
            }
        }
        wp_send_json_error(array('message' => 'Item not found'));
    }

    public static function ajax_checklist_add() {
        check_ajax_referer('gabochie_go_live_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'), 403);
        $list = self::requested_list();
        $text = trim(sanitize_text_field($_POST['text'] ?? ''));
        if ($text === '') wp_send_json_error(array('message' => 'Empty item'));
        $items = self::checklist_get($list);
        $items[] = array('id' => uniqid('item-'), 'text' => $text, 'done' => 0);
        self::checklist_save($items, $list);
        wp_send_json_success(array('html' => self::checklist_render_html($list)));
    }

    public static function ajax_checklist_delete() {
        check_ajax_referer('gabochie_go_live_nonce', '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'), 403);
        $list = self::requested_list();
        $id = sanitize_key($_POST['id'] ?? '');
        $items = self::checklist_get($list);
        foreach ($items as $k => $i) {
            if ($i['id'] === $id) {
                unset($items[$k]);
                self::checklist_save(array_values($items), $list);
                wp_send_json_success(array('html' => self::checklist_render_html($list)));
            }
        }
        wp_send_json_error(array('message' => 'Item not found'));
    }

    /* =====================================================================
     * RELEASE SCORE DASHBOARD
     * =================================================================== */

    public static function score_data() {
        $data = array(
            'qa_gate'     => array('status' => 'unknown', 'commit' => '', 'time' => ''),
            'deploy'      => array('last_line' => '', 'commit' => ''),
            'lighthouse'  => array('performance' => 0, 'accessibility' => 0, 'seo' => 0, 'best_practices' => 0),
            'e2e'         => array('pass' => 0, 'fail' => 0, 'skip' => 0),
            'lint'        => array('errors' => 0, 'warnings' => 0),
            'secret_scan' => array('status' => 'unknown', 'hits' => 0),
            'qa_suite'    => array('pass' => 0, 'fail' => 0, 'warn' => 0),
        );

        // QA gate state
        $gate_file = self::deploy_dir() . '/qa-state.txt';
        if (is_readable($gate_file)) {
            $gate = parse_ini_string(file_get_contents($gate_file));
            if (is_array($gate)) {
                $data['qa_gate'] = array(
                    'status' => $gate['STATUS'] ?? 'unknown',
                    'commit' => $gate['COMMIT'] ?? '',
                    'time'   => $gate['TIME'] ?? '',
                );
            }
        }

        // Deploy log (last meaningful lines)
        $log_file = self::deploy_dir() . '/deploy-staging.log';
        if (is_readable($log_file)) {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines) {
                $last = '';
                for ($i = count($lines) - 1; $i >= 0; $i--) {
                    if (preg_match('/RESULT:|deploy ok|FAIL/', $lines[$i])) {
                        $last = $lines[$i];
                        break;
                    }
                }
                if (!$last) $last = end($lines);
                $data['deploy']['last_line'] = $last;
                if (preg_match('/commit\s+(\w{7,})/', $last, $m)) {
                    $data['deploy']['commit'] = $m[1];
                }
            }
        }

        // QA suite results from deploy log
        if (is_readable($log_file)) {
            $log = file_get_contents($log_file);
            if (preg_match('/RESULT:\s*(\d+)\s*pass,\s*(\d+)\s*fail,\s*(\d+)\s*warn/', $log, $m)) {
                $data['qa_suite'] = array('pass' => (int)$m[1], 'fail' => (int)$m[2], 'warn' => (int)$m[3]);
            }
        }

        // Lighthouse scores (read from server-side JSON if available)
        $lh_file = self::deploy_dir() . '/lighthouse-scores.json';
        if (is_readable($lh_file)) {
            $lh = json_decode(file_get_contents($lh_file), true);
            if (is_array($lh)) {
                $data['lighthouse'] = array(
                    'performance'    => $lh['performance'] ?? 0,
                    'accessibility'  => $lh['accessibility'] ?? 0,
                    'seo'            => $lh['seo'] ?? 0,
                    'best_practices' => $lh['best_practices'] ?? 0,
                );
            }
        }

        // E2E results
        $e2e_file = self::deploy_dir() . '/e2e-results.json';
        if (is_readable($e2e_file)) {
            $e2e = json_decode(file_get_contents($e2e_file), true);
            if (is_array($e2e)) {
                $data['e2e'] = array(
                    'pass' => $e2e['pass'] ?? 0,
                    'fail' => $e2e['fail'] ?? 0,
                    'skip' => $e2e['skip'] ?? 0,
                );
            }
        }

        // Lint results
        $lint_file = self::deploy_dir() . '/lint-results.json';
        if (is_readable($lint_file)) {
            $lint = json_decode(file_get_contents($lint_file), true);
            if (is_array($lint)) {
                $data['lint'] = array(
                    'errors'   => $lint['errors'] ?? 0,
                    'warnings' => $lint['warnings'] ?? 0,
                );
            }
        }

        // Secret scan
        $scan_file = self::deploy_dir() . '/secret-scan-results.json';
        if (is_readable($scan_file)) {
            $scan = json_decode(file_get_contents($scan_file), true);
            if (is_array($scan)) {
                $data['secret_scan'] = array(
                    'status' => $scan['status'] ?? 'unknown',
                    'hits'   => (int)($scan['hits'] ?? 0),
                );
            }
        }

        return $data;
    }

    public static function score_grade($score) {
        if ($score >= 90) return array('A', '#22c55e');
        if ($score >= 70) return array('B', '#84cc16');
        if ($score >= 50) return array('C', '#eab308');
        return array('F', '#ef4444');
    }

    public static function render_score() {
        if (!current_user_can('manage_options')) return;
        $commit = self::staging_commit();
        $data = self::score_data();

        // Compute overall score
        $weights = array('qa_gate' => 25, 'lighthouse' => 25, 'e2e' => 20, 'lint' => 15, 'secret_scan' => 10, 'qa_suite' => 5);
        $component_scores = array();

        // QA gate: 100 if pass, 0 otherwise
        $component_scores['qa_gate'] = ($data['qa_gate']['status'] === 'pass') ? 100 : 0;

        // Lighthouse: average of 4 categories
        $lh = $data['lighthouse'];
        $component_scores['lighthouse'] = ($lh['performance'] + $lh['accessibility'] + $lh['seo'] + $lh['best_practices']) / 4;

        // E2E: pass / (pass + fail + skip) * 100
        $e2e_total = $data['e2e']['pass'] + $data['e2e']['fail'] + $data['e2e']['skip'];
        $component_scores['e2e'] = ($e2e_total > 0) ? round($data['e2e']['pass'] / $e2e_total * 100) : 0;

        // Lint: 100 if no errors
        $component_scores['lint'] = ($data['lint']['errors'] === 0) ? 100 : max(0, 100 - $data['lint']['errors'] * 10);

        // Secret scan: 100 if pass
        $component_scores['secret_scan'] = ($data['secret_scan']['status'] === 'pass') ? 100 : 0;

        // QA suite: pass / total * 100
        $qa_total = $data['qa_suite']['pass'] + $data['qa_suite']['fail'] + $data['qa_suite']['warn'];
        $component_scores['qa_suite'] = ($qa_total > 0) ? round($data['qa_suite']['pass'] / $qa_total * 100) : 0;

        $overall = 0;
        foreach ($weights as $k => $w) {
            $overall += ($component_scores[$k] ?? 0) * $w / 100;
        }
        $overall = round($overall);
        list($grade, $grade_color) = self::score_grade($overall);

        echo '<div class="wrap">';
        echo self::tabs('score');
        echo '<h1>Release Score Dashboard</h1>';
        echo '<p>Commit: <code>' . esc_html($commit) . '</code> &mdash; ';
        echo 'QA Gate: <strong style="color:' . esc_attr($data['qa_gate']['status'] === 'pass' ? '#22c55e' : '#ef4444') . '">' . esc_html(strtoupper($data['qa_gate']['status'])) . '</strong>';
        if ($data['qa_gate']['time']) {
            echo ' &mdash; ' . esc_html($data['qa_gate']['time']);
        }
        echo '</p>';

        // Overall score card
        echo '<div style="display:flex;gap:2rem;align-items:center;margin:20px 0 30px;padding:28px 32px;background:#fff;border:1px solid #c3c4c7;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.04);">';
        echo '<div style="font-size:4rem;font-weight:700;color:' . esc_attr($grade_color) . ';line-height:1;">' . esc_html($overall) . '</div>';
        echo '<div>';
        echo '<div style="font-size:1.6rem;font-weight:600;color:' . esc_attr($grade_color) . '">Grade ' . esc_html($grade) . '</div>';
        echo '<div style="color:#646970;margin-top:4px;">Overall release readiness score</div>';
        echo '</div>';
        echo '</div>';

        // Component scores grid
        echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:24px;">';

        $components = array(
            'qa_gate'      => array('QA Gate', 'Pass/fail from qa.sh gate check'),
            'lighthouse'   => array('Lighthouse', 'Avg of Performance/Accessibility/SEO/Best Practices'),
            'e2e'          => array('E2E Tests', 'Playwright test pass rate'),
            'qa_suite'     => array('QA Suite', 'Server-side qa.sh pass/fail/warn'),
            'lint'         => array('Lint (PHP)', 'PHPCS + PHPStan error count'),
            'secret_scan'  => array('Secret Scan', 'Secret scanning results'),
        );

        foreach ($components as $key => $info) {
            $score = $component_scores[$key];
            list($g, $gc) = self::score_grade($score);
            $weight = $weights[$key];
            echo '<div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;">';
            echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">';
            echo '<strong style="font-size:0.95rem;">' . esc_html($info[0]) . '</strong>';
            echo '<span style="background:' . esc_attr($gc) . ';color:#fff;padding:2px 10px;border-radius:12px;font-size:0.8rem;font-weight:600;">' . esc_html($score) . '%</span>';
            echo '</div>';
            echo '<div style="font-size:0.78rem;color:#646970;margin-bottom:10px;">' . esc_html($info[1]) . '</div>';
            // Progress bar
            echo '<div style="height:6px;background:#e2e4e9;border-radius:3px;overflow:hidden;">';
            echo '<div style="height:100%;width:' . esc_attr($score) . '%;background:' . esc_attr($gc) . ';border-radius:3px;transition:width 0.3s;"></div>';
            echo '</div>';
            echo '<div style="font-size:0.7rem;color:#8c8f9a;margin-top:6px;">Weight: ' . esc_html($weight) . '%</div>';
            echo '</div>';
        }
        echo '</div>';

        // Detail panels
        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">';

        // Lighthouse panel
        echo '<div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;">';
        echo '<h3 style="margin:0 0 12px;font-size:1rem;">Lighthouse Scores</h3>';
        foreach ($data['lighthouse'] as $cat => $val) {
            list($g, $gc) = self::score_grade($val);
            $label = ucwords(str_replace('_', ' ', $cat));
            echo '<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f0;">';
            echo '<span>' . esc_html($label) . '</span>';
            echo '<span style="color:' . esc_attr($gc) . ';font-weight:600;">' . esc_html($val) . '</span>';
            echo '</div>';
        }
        echo '</div>';

        // E2E panel
        echo '<div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;">';
        echo '<h3 style="margin:0 0 12px;font-size:1rem;">E2E Test Results</h3>';
        $e2e = $data['e2e'];
        echo '<div style="display:flex;gap:20px;">';
        echo '<div style="text-align:center;"><div style="font-size:1.8rem;font-weight:700;color:#22c55e;">' . esc_html($e2e['pass']) . '</div><div style="font-size:0.75rem;color:#646970;">Passed</div></div>';
        echo '<div style="text-align:center;"><div style="font-size:1.8rem;font-weight:700;color:#ef4444;">' . esc_html($e2e['fail']) . '</div><div style="font-size:0.75rem;color:#646970;">Failed</div></div>';
        echo '<div style="text-align:center;"><div style="font-size:1.8rem;font-weight:700;color:#8c8f9a;">' . esc_html($e2e['skip']) . '</div><div style="font-size:0.75rem;color:#646970;">Skipped</div></div>';
        echo '</div>';
        echo '</div>';

        // QA suite panel
        echo '<div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;">';
        echo '<h3 style="margin:0 0 12px;font-size:1rem;">QA Suite (qa.sh)</h3>';
        $qa = $data['qa_suite'];
        echo '<div style="display:flex;gap:20px;">';
        echo '<div style="text-align:center;"><div style="font-size:1.8rem;font-weight:700;color:#22c55e;">' . esc_html($qa['pass']) . '</div><div style="font-size:0.75rem;color:#646970;">Pass</div></div>';
        echo '<div style="text-align:center;"><div style="font-size:1.8rem;font-weight:700;color:#ef4444;">' . esc_html($qa['fail']) . '</div><div style="font-size:0.75rem;color:#646970;">Fail</div></div>';
        echo '<div style="text-align:center;"><div style="font-size:1.8rem;font-weight:700;color:#eab308;">' . esc_html($qa['warn']) . '</div><div style="font-size:0.75rem;color:#646970;">Warn</div></div>';
        echo '</div>';
        echo '</div>';

        // Lint + Secret scan panel
        echo '<div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;">';
        echo '<h3 style="margin:0 0 12px;font-size:1rem;">Lint & Security</h3>';
        echo '<div style="display:flex;gap:24px;">';
        echo '<div><div style="font-size:0.78rem;color:#646970;">PHP Lint Errors</div><div style="font-size:1.4rem;font-weight:700;color:' . ($data['lint']['errors'] > 0 ? '#ef4444' : '#22c55e') . ';">' . esc_html($data['lint']['errors']) . '</div></div>';
        echo '<div><div style="font-size:0.78rem;color:#646970;">Secret Scan</div><div style="font-size:1.4rem;font-weight:700;color:' . ($data['secret_scan']['status'] === 'pass' ? '#22c55e' : '#ef4444') . ';">' . esc_html(strtoupper($data['secret_scan']['status'])) . '</div></div>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // grid

        // Refresh button
        echo '<div style="margin-top:20px;text-align:center;">';
        echo '<button id="gol-score-refresh" class="button button-secondary">Refresh Score Data</button>';
        echo '</div>';

        echo '</div>'; // wrap

        // Inline JS
        echo '<script>';
        echo '(function(){';
        echo 'var api={ajaxUrl:' . wp_json_encode(admin_url('admin-ajax.php')) . ',nonce:' . wp_json_encode(wp_create_nonce('gabochie_go_live_nonce')) . '};';
        echo 'document.getElementById("gol-score-refresh").addEventListener("click",function(e){e.preventDefault();location.reload();});';
        echo '})();';
        echo '</script>';
    }

    public static function ajax_score_refresh() {
        check_ajax_referer('gabochie_go_live_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();
        wp_send_json_success(array('data' => self::score_data()));
    }
}

Gabochie_Go_Live::init();
