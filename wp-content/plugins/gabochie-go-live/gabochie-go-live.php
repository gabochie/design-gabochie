<?php
/**
 * Plugin Name: Gabochie Go Live
 * Description: Push staging (wp.gabochiedesign.com) to production (gabochiedesign.com) via the server-side promote.sh engine. Staging site only.
 * Version: 1.0.0
 * Author: Gabochie Design
 * License: GPL-2.0+
 * Text Domain: gabochie-go-live
 */

if (!defined('ABSPATH')) exit;

class Gabochie_Go_Live {

    const VERSION = '1.0.0';

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
}

Gabochie_Go_Live::init();
