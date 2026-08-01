<?php
/**
 * Plugin Name: Gabochie Go Live
 * Description: Push staging (wp.gabochiedesign.com) to production (gabochiedesign.com) via the server-side promote.sh engine. Staging site only.
 * Version: 1.1.0
 * Author: Gabochie Design
 * License: GPL-2.0+
 * Text Domain: gabochie-go-live
 */

if (!defined('ABSPATH')) exit;

class Gabochie_Go_Live {

    const VERSION = '1.1.0';

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
        return $list === 'precheck' ? 'gabochie_precheck_list' : 'gabochie_launch_checklist';
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
                array('id' => 'ps-email', 'text' => 'Email: from name/address and SMTP/transactional mail (FluentCRM/Brevo) configured + test email delivered.', 'done' => 0),
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
        $out = '<div class="gol-check-progress"><span>Progress: ' . $done . ' / ' . $total . ' (' . $pct . '%)</span>'
             . '<div style="background:#e5e5e5;border-radius:99px;height:10px;margin-top:6px;max-width:520px;"><div style="background:#46b450;height:10px;border-radius:99px;width:' . $pct . '%;"></div></div></div>';
        $out .= '<div id="gol-' . $list_attr . '-items">';
        foreach ($items as $i) {
            $cid = esc_attr($i['id']);
            $ctext = esc_html($i['text']);
            $cls = !empty($i['done']) ? 'gol-done' : '';
            $checked = !empty($i['done']) ? 'checked' : '';
            $out .= '<label class="gol-item ' . $cls . '" style="display:flex;gap:8px;align-items:flex-start;padding:6px 4px;border-bottom:1px solid #f0f0f0;max-width:720px;">'
                  . '<input type="checkbox" data-id="' . $cid . '" ' . $checked . ' style="margin-top:2px;">'
                  . '<span style="flex:1;">' . $ctext . '</span>'
                  . '<a href="#" class="gol-del" data-list="' . $list_attr . '" data-id="' . $cid . '" title="Remove item" style="text-decoration:none;color:#b32d2e;">&#10005;</a>'
                  . '</label>';
        }
        $out .= '</div>';
        return $out;
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
                }).fail(function () { cb.prop('disabled', false); });
            }

            function addItem(list) {
                var text = $('#gol-new-' + list).val().trim();
                if (!text) return;
                $('#gol-new-' + list).val('');
                $.post(GOL.ajaxUrl, { action: 'gabochie_checklist_add', _wpnonce: GOL.nonce, list: list, text: text }, function (r) {
                    loadChecklist(list);
                });
            }

            function delItem(e) {
                e.preventDefault();
                var list = $(this).data('list') || 'launch';
                var id = $(this).data('id');
                if (!window.confirm('Remove this checklist item?')) return;
                $.post(GOL.ajaxUrl, { action: 'gabochie_checklist_delete', _wpnonce: GOL.nonce, list: list, id: id }, function (r) {
                    loadChecklist(list);
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
        })(jQuery);
        </script>
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
        return $list === 'precheck' ? 'precheck' : 'launch';
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
}

Gabochie_Go_Live::init();
