<?php
/**
 * Plugin Name: Gabochie Command Center
 * Description: Mission control for the Gabochie autonomous company — live status, agentic DevOps/QA team, and human-in-the-loop approvals.
 * Version: 0.1.0
 * Author: Gabochie Design
 * License: GPL-2.0+
 * Text Domain: gabochie-command-center
 */

if (!defined('ABSPATH')) exit;

class Gabochie_Command_Center {

    const VERSION = '0.1.0';
    const OPTION_APPROVALS = 'gabochie_cc_approvals';
    const NONCE = 'gabochie_cc_nonce';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('wp_ajax_gabochie_cc_status', array(__CLASS__, 'ajax_status'));
        add_action('wp_ajax_gabochie_cc_approval_toggle', array(__CLASS__, 'ajax_approval_toggle'));
    }

    public static function deploy_dir() {
        return apply_filters('gabochie_cc_deploy_dir', '/home/gabozvqd/deploy');
    }

    public static function menu() {
        add_menu_page(
            'Command Center',
            'Command Center',
            'manage_options',
            'gabochie-command-center',
            array(__CLASS__, 'render'),
            'dashicons-dashboard',
            2
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

    public static function endpoint_ok($path) {
        $key = 'gabochie_cc_ep_' . md5($path);
        $cached = get_transient($key);
        if (false !== $cached) return $cached;
        $res = wp_remote_get(home_url($path), array('timeout' => 8, 'sslverify' => false, 'redirection' => 2));
        $ok = !is_wp_error($res) && (int) wp_remote_retrieve_response_code($res) === 200;
        set_transient($key, $ok, 120);
        return $ok;
    }

    public static function env_type() {
        if (function_exists('wp_get_environment_type')) {
            return wp_get_environment_type();
        }
        return 'unknown';
    }

    public static function payments_state() {
        $state = array(
            'expresspay_env' => 'n/a',
            'pmpro_gateway' => '',
            'pmpro_env' => 'n/a',
            'orders' => 0,
        );
        $ep = get_option('woocommerce_expresspay_gateway_settings', array());
        if (is_array($ep)) {
            $state['expresspay_env'] = (isset($ep['environment']) && $ep['environment']) ? $ep['environment'] : 'production';
        }
        $state['pmpro_gateway'] = (string) get_option('pmpro_gateway', '');
        $state['pmpro_env'] = (string) get_option('pmpro_gateway_environment', 'sandbox');
        global $wpdb;
        $woo_table = $wpdb->prefix . 'wc_orders';
        $woo_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $woo_table));
        if ($woo_exists) {
            $state['orders'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wc_orders WHERE status NOT IN ('auto-draft','checkout-draft')");
        } else {
            $state['orders'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='shop_order' AND post_status IN ('wc-completed','wc-processing','wc-on-hold','publish')");
        }
        return $state;
    }

    public static function ai_state() {
        $provider = get_option('ghbc_llm_provider', '');
        $key = get_option('ghbc_llm_key', '');
        $fallbacks = get_option('ghbc_llm_fallbacks', array());
        $n_fb = is_array($fallbacks) ? count($fallbacks) : 0;
        $valid = false;
        if ($provider && $provider !== 'ollama') {
            $valid = !empty($key) && !($provider === 'claude' && strpos($key, 'sk-ant-') !== 0);
        }
        if ($provider === 'ollama') {
            $valid = true;
        }
        return array('provider' => $provider ?: 'none', 'has_key' => (bool) $key, 'valid' => $valid, 'fallbacks' => $n_fb);
    }

    public static function members_count() {
        global $wpdb;
        $t = $wpdb->prefix . 'pmpro_memberships_users';
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t));
        if (!$exists) return 0;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'active'");
    }

    public static function has_security_plugin() {
        $sec = array('wordfence', 'all-in-one-wp-security', 'solid-security', 'limit-login-attempts-reloaded', 'google-authenticator', 'two-factor', 'wp-2fa', 'ithemes-security');
        foreach ((array) get_option('active_plugins', array()) as $p) {
            foreach ($sec as $s) {
                if (stripos($p, $s) !== false) return true;
            }
        }
        return false;
    }

    public static function last_smoke() {
        $f = self::deploy_dir() . '/deploy.log';
        if (!is_readable($f)) return array('found' => false, 'line' => 'deploy.log not readable from web');
        $lines = @file($f);
        if (!$lines) return array('found' => false, 'line' => 'deploy.log unreadable');
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (strpos($lines[$i], 'SMOKE TEST') !== false) {
                return array('found' => true, 'line' => trim($lines[$i]));
            }
        }
        return array('found' => false, 'line' => 'no SMOKE TEST line yet');
    }

    public static function log_tail($file, $lines = 12) {
        $f = self::deploy_dir() . '/' . $file;
        if (!is_readable($f)) return null;
        $all = @file($f);
        if (!$all) return null;
        $slice = array_slice($all, -$lines);
        return implode('', $slice);
    }

    public static function live_commit() {
        if (!self::shell_ok()) return 'n/a';
        $dir = '/home/gabozvqd/gabochie-deploy';
        $out = self::run('git -C ' . escapeshellarg($dir) . ' log -1 --format=%h 2>/dev/null');
        return $out ?: 'n/a';
    }

    public static function qa_progress() {
        $counts = array('gates' => array(0, 0), 'roadmap' => array(0, 0));
        foreach (array('gates' => 'gabochie_qa_gates', 'roadmap' => 'gabochie_qa_roadmap') as $key => $opt) {
            $items = get_option($opt, array());
            if (!is_array($items)) $items = array();
            $done = 0;
            foreach ($items as $i) {
                if (!empty($i['done'])) $done++;
            }
            $counts[$key] = array($done, count($items));
        }
        return $counts;
    }

    public static function approvals_derived() {
        $items = array();
        $p = self::payments_state();
        if ($p['expresspay_env'] === 'sandbox' || $p['pmpro_env'] === 'sandbox') {
            $items[] = array('id' => 'der-pay', 'severity' => 'high', 'text' => 'Payments are in sandbox — switch ExpressPay/PMPro to live credentials so customers can actually pay.');
        }
        if (empty($p['pmpro_gateway'])) {
            $items[] = array('id' => 'der-pmpro', 'severity' => 'high', 'text' => 'PMPro payment gateway is not set — memberships cannot be charged.');
        }
        $ai = self::ai_state();
        if (!$ai['valid']) {
            $items[] = array('id' => 'der-ai', 'severity' => 'high', 'text' => 'AI/LLM key is missing or invalid for provider "' . $ai['provider'] . '" — add a valid key in GhBC Settings and Test Connection.');
        }
        return $items;
    }

    public static function approvals_persisted() {
        $items = get_option(self::OPTION_APPROVALS);
        if (!is_array($items)) {
            $items = self::approvals_defaults();
            update_option(self::OPTION_APPROVALS, $items, false);
            return $items;
        }
        $have = array();
        foreach ($items as $i) {
            if (isset($i['id'])) $have[$i['id']] = true;
        }
        $added = false;
        foreach (self::approvals_defaults() as $d) {
            if (!empty($have[$d['id']])) continue;
            $items[] = $d;
            $added = true;
        }
        if ($added) update_option(self::OPTION_APPROVALS, $items, false);
        return $items;
    }

    public static function approvals_defaults() {
        return array(
            array('id' => 'ap-expresspay', 'text' => 'ExpressPay live merchant keys obtained (or explicitly confirmed sandbox for now).', 'done' => 0),
            array('id' => 'ap-prices', 'text' => 'Price list confirmed: membership levels + digital product prices for launch.', 'done' => 0),
            array('id' => 'ap-ghbc-key', 'text' => 'GhBC Claude API key added and Test Connection passes.', 'done' => 0),
            array('id' => 'ap-brevo', 'text' => 'Brevo API key added so email/forms can send.', 'done' => 0),
            array('id' => 'ap-content', 'text' => 'Content source of truth decided (live DB vs new build content).', 'done' => 0),
            array('id' => 'ap-plugins', 'text' => 'Decision on the 7 missing old-live plugins (reinstall or confirm skip).', 'done' => 0),
        );
    }

    public static function approvals_html() {
        $out = '';
        $derived = self::approvals_derived();
        foreach ($derived as $i) {
            $color = $i['severity'] === 'high' ? '#d63638' : '#dba617';
            $out .= '<div style="display:flex;gap:10px;padding:10px 12px;border:1px solid #c3c4c7;border-left:4px solid ' . $color . ';background:#fff;margin-bottom:8px;border-radius:4px;align-items:flex-start;">'
                  . '<span style="font-weight:600;color:' . $color . ';white-space:nowrap;font-size:12px;text-transform:uppercase;">Auto</span>'
                  . '<div style="flex:1;">' . esc_html($i['text']) . '</div></div>';
        }
        $out .= '<div id="gcc-approvals">';
        foreach (self::approvals_persisted() as $i) {
            $out .= self::approval_item_html($i);
        }
        $out .= '</div>';
        return $out;
    }

    public static function approval_item_html($i) {
        $cid = esc_attr($i['id']);
        $ctext = esc_html($i['text']);
        $cls = !empty($i['done']) ? 'gcc-done' : '';
        $checked = !empty($i['done']) ? 'checked' : '';
        return '<label class="gcc-item ' . $cls . '" style="display:flex;gap:8px;align-items:flex-start;padding:7px 4px;border-bottom:1px solid #f0f0f0;">'
             . '<input type="checkbox" data-id="' . $cid . '" ' . $checked . ' style="margin-top:2px;">'
             . '<span style="flex:1;">' . $ctext . '</span></label>';
    }

    public static function agents() {
        $pay = self::payments_state();
        $ai = self::ai_state();
        $smoke = self::last_smoke();
        $qa = self::qa_progress();
        $pay_ok = ($pay['expresspay_env'] !== 'sandbox' && $pay['pmpro_env'] !== 'sandbox' && !empty($pay['pmpro_gateway']));
        $ai_ok = $ai['valid'];
        $smoke_ok = $smoke['found'] && stripos($smoke['line'], 'PASS') !== false;
        $endpoints_ok = self::endpoint_ok('/') && self::endpoint_ok('/wp-login.php') && self::endpoint_ok('/wp-json/');
        $members = self::members_count();

        $light = function ($ok) {
            if ($ok === true) return array('#46b450', 'On mission');
            if ($ok === false) return array('#d63638', 'Needs attention');
            return array('#8c8f94', 'Standby');
        };

        $agents = array(
            array('icon' => 'dashicons-megaphone', 'name' => 'CMO', 'role' => 'Content, SEO & campaigns',
                'task' => 'AI content pipeline awaiting go-ahead; draft + human review + publish.',
                'state' => self::env_type() !== 'production' ? array('#8c8f94', 'Standby') : array('#dba617', 'Setup'),
                'gate' => 'Publish approval'),
            array('icon' => 'dashicons-email-alt', 'name' => 'SDR', 'role' => 'Lead qualification',
                'task' => 'Captures leads + runs nurture sequences (FluentCRM) once Brevo key is set.',
                'state' => array('#8c8f94', 'Standby'),
                'gate' => 'Soft — notify only'),
            array('icon' => 'dashicons-media-text', 'name' => 'Closer', 'role' => 'Proposals & quotes',
                'task' => $pay_ok ? 'Ready to draft proposals from intake.' : 'Blocked — payments still sandbox.',
                'state' => $pay_ok ? array('#46b450', 'Ready') : array('#d63638', 'Blocked'),
                'gate' => 'Approve every quote'),
            array('icon' => 'dashicons-building', 'name' => 'Delivery', 'role' => 'Architect — GhBC, BOQ, feasibility',
                'task' => $ai_ok ? 'LLM stack ready for compliance/BOQ work.' : 'Needs a valid AI key to run GhBC tools.',
                'state' => $ai_ok ? array('#46b450', 'Ready') : array('#d63638', 'Blocked'),
                'gate' => 'Approve every deliverable'),
            array('icon' => 'dashicons-sos', 'name' => 'CS', 'role' => 'Support & retention',
                'task' => 'Answers questions, escalates to you on exceptions.',
                'state' => array('#8c8f94', 'Standby'),
                'gate' => 'Escalation to human'),
            array('icon' => 'dashicons-chart-line', 'name' => 'Ops', 'role' => 'Back-office & reporting',
                'task' => 'Orders: ' . $pay['orders'] . ' | Active members: ' . $members . '. Weekly ops brief once wired.',
                'state' => $pay['orders'] > 0 ? array('#46b450', 'Active') : array('#8c8f94', 'Standby'),
                'gate' => 'Money movement'),
            array('icon' => 'dashicons-admin-tools', 'name' => 'QA/DevOps', 'role' => 'Test, monitor & deploy',
                'task' => 'Smoke: ' . ($smoke['found'] ? ($smoke_ok ? 'PASS' : 'FAIL') : 'not logged') . ' | Gates: ' . $qa['gates'][0] . '/' . $qa['gates'][1] . ' | Roadmap: ' . $qa['roadmap'][0] . '/' . $qa['roadmap'][1],
                'state' => $smoke_ok ? array('#46b450', 'Green') : ($smoke['found'] ? array('#d63638', 'Needs attention') : array('#8c8f94', 'No log yet')),
                'gate' => 'Deploy to live'),
            array('icon' => 'dashicons-networking', 'name' => 'Orchestrator', 'role' => 'Routes tasks, owns escalation',
                'task' => 'Overall mission: ' . ($endpoints_ok ? 'site online' : 'site offline') . ' — routes work to specialists.',
                'state' => $endpoints_ok ? array('#46b450', 'Healthy') : array('#d63638', 'Critical'),
                'gate' => 'Exceptions & money'),
        );
        return $agents;
    }

    public static function agents_html() {
        $out = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;">';
        foreach (self::agents() as $a) {
            $c = $a['state'][0];
            $out .= '<div style="border:1px solid #c3c4c7;border-top:4px solid ' . $c . ';background:#fff;border-radius:6px;padding:14px;">'
                  . '<div style="display:flex;align-items:center;gap:10px;"><span class="dashicons ' . esc_attr($a['icon']) . '" style="color:' . $c . ';font-size:28px;width:28px;height:28px;"></span>'
                  . '<div style="flex:1;"><strong style="font-size:15px;">' . esc_html($a['name']) . '</strong>'
                  . '<div style="color:#50575e;font-size:12px;">' . esc_html($a['role']) . '</div></div>'
                  . '<span style="background:' . $c . ';color:#fff;font-size:10px;font-weight:600;text-transform:uppercase;padding:2px 8px;border-radius:99px;white-space:nowrap;">' . esc_html($a['state'][1]) . '</span></div>'
                  . '<p style="margin:10px 0 4px;color:#3c434a;font-size:13px;">' . esc_html($a['task']) . '</p>'
                  . '<div style="font-size:11px;color:#8c8f94;text-transform:uppercase;letter-spacing:.03em;">Human gate: ' . esc_html($a['gate']) . '</div>'
                  . '</div>';
        }
        $out .= '</div>';
        return $out;
    }

    public static function health_score() {
        $score = 0;
        $checks = array();
        $e = array('/', '/wp-login.php', '/wp-json/');
        foreach ($e as $p) {
            $ok = self::endpoint_ok($p);
            if ($ok) $score += 10;
            $checks[] = array($p, $ok);
        }
        $pay = self::payments_state();
        $pay_ok = ($pay['expresspay_env'] !== 'sandbox' && $pay['pmpro_env'] !== 'sandbox' && !empty($pay['pmpro_gateway']));
        if ($pay_ok) { $score += 20; } else { $score += 5; }
        $checks[] = array('Payments live', $pay_ok);
        $ai = self::ai_state();
        if ($ai['valid']) { $score += 15; } else { $score += 3; }
        $checks[] = array('AI key valid', $ai['valid']);
        if (self::has_security_plugin()) { $score += 10; } else { $score += 2; }
        $checks[] = array('Security plugin', self::has_security_plugin());
        $smoke = self::last_smoke();
        $smoke_ok = $smoke['found'] && stripos($smoke['line'], 'PASS') !== false;
        if ($smoke_ok) { $score += 15; } else { $score += 0; }
        $checks[] = array('Last smoke PASS', $smoke_ok);
        if (self::payments_state()['orders'] > 0) { $score += 10; } else { $score += 2; }
        $checks[] = array('Orders flowing', self::payments_state()['orders'] > 0);
        if ($score >= 80) $label = array('#0a7d32', 'Excellent');
        elseif ($score >= 60) $label = array('#46b450', 'Good');
        elseif ($score >= 40) $label = array('#dba617', 'Fair');
        else $label = array('#d63638', 'Critical');
        return array('score' => $score, 'label' => $label, 'checks' => $checks);
    }

    public static function status_cards_html() {
        $hs = self::health_score();
        $pay = self::payments_state();
        $ai = self::ai_state();
        $smoke = self::last_smoke();
        $qa = self::qa_progress();
        $smoke_ok = $smoke['found'] && stripos($smoke['line'], 'PASS') !== false;
        $commit = self::live_commit();
        $cards = array(
            array('t' => 'Mission Health', 'v' => $hs['score'] . '/100', 's' => $hs['label'][1], 'c' => $hs['label'][0], 'd' => count($hs['checks']) . ' systems checked'),
            array('t' => 'Payments', 'v' => $pay['orders'] . ' orders', 's' => $pay_ok = ($pay['expresspay_env'] !== 'sandbox' && $pay['pmpro_env'] !== 'sandbox' && !empty($pay['pmpro_gateway'])) ? 'Live' : 'Sandbox', 'c' => $pay_ok ? '#0a7d32' : '#dba617', 'd' => 'ExpressPay ' . $pay['expresspay_env'] . ' | PMPro ' . $pay['pmpro_env']),
            array('t' => 'AI Agents', 'v' => ucfirst($ai['provider']), 's' => $ai['valid'] ? 'Ready' : 'Needs key', 'c' => $ai['valid'] ? '#0a7d32' : '#d63638', 'd' => ($ai['fallbacks'] ? $ai['fallbacks'] . ' fallback(s)' : 'no fallbacks')),
            array('t' => 'Deploy & QA', 'v' => $commit, 's' => $smoke_ok ? 'Smoke PASS' : ($smoke['found'] ? 'Smoke FAIL' : 'No log'), 'c' => $smoke_ok ? '#0a7d32' : '#8c8f94', 'd' => 'Gates ' . $qa['gates'][0] . '/' . $qa['gates'][1] . ' | Roadmap ' . $qa['roadmap'][0] . '/' . $qa['roadmap'][1]),
            array('t' => 'Environment', 'v' => self::env_type(), 's' => 'WP ' . get_bloginfo('version'), 'c' => '#2271b1', 'd' => 'PHP ' . PHP_VERSION . ' | DB ' . self::db_size_mb() . ' MB'),
            array('t' => 'Members', 'v' => (string) self::members_count(), 's' => 'active', 'c' => '#2271b1', 'd' => 'PMPro memberships'),
        );
        $out = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:12px;">';
        foreach ($cards as $c) {
            $out .= '<div style="border:1px solid #c3c4c7;border-top:4px solid ' . $c['c'] . ';background:#fff;border-radius:6px;padding:14px;">'
                  . '<div style="color:#8c8f94;font-size:11px;text-transform:uppercase;letter-spacing:.05em;">' . esc_html($c['t']) . '</div>'
                  . '<div style="font-size:22px;font-weight:700;margin:4px 0;">' . esc_html($c['v']) . '</div>'
                  . '<div style="font-weight:600;color:' . $c['c'] . ';font-size:12px;">' . esc_html($c['s']) . '</div>'
                  . '<div style="color:#50575e;font-size:12px;margin-top:6px;">' . esc_html($c['d']) . '</div></div>';
        }
        $out .= '</div>';
        return $out;
    }

    public static function db_size_mb() {
        global $wpdb;
        $size = $wpdb->get_var("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) FROM information_schema.TABLES WHERE table_schema = DATABASE()");
        return is_numeric($size) ? $size : 0;
    }

    public static function qa_html() {
        $qa = self::qa_progress();
        $bar = function ($done, $total, $color) {
            $pct = $total ? (int) round(100 * $done / $total) : 0;
            return '<div style="background:#e5e5e5;border-radius:99px;height:10px;max-width:420px;margin-top:6px;"><div style="background:' . $color . ';height:10px;border-radius:99px;width:' . $pct . '%;"></div></div>';
        };
        $smoke = self::last_smoke();
        $commit = self::live_commit();
        $out = '<table class="widefat striped" style="max-width:760px;"><tbody>';
        $out .= '<tr><th style="width:34%;">Live deploy commit</th><td><code>' . esc_html($commit) . '</code></td></tr>';
        $out .= '<tr><th>Last smoke test</th><td>' . esc_html($smoke['line']) . '</td></tr>';
        $out .= '<tr><th>Release QA — 12 gates</th><td>' . esc_html($qa['gates'][0] . ' / ' . $qa['gates'][1]) . $bar($qa['gates'][0], $qa['gates'][1], '#2271b1') . '</td></tr>';
        $out .= '<tr><th>QA roadmap (Phases 1–10)</th><td>' . esc_html($qa['roadmap'][0] . ' / ' . $qa['roadmap'][1]) . $bar($qa['roadmap'][0], $qa['roadmap'][1], '#46b450') . '</td></tr>';
        $out .= '</tbody></table>';
        return $out;
    }

    public static function logs_html() {
        $out = '<h3>Deploy log</h3>';
        $tail = self::log_tail('deploy.log');
        $out .= $tail
            ? '<pre style="background:#0d1117;color:#c9d1d9;padding:12px;border-radius:6px;max-height:260px;overflow:auto;font-size:12px;">' . esc_html($tail) . '</pre>'
            : '<p class="description">deploy.log is not readable from the web server. Deploy details stay server-side at <code>~/deploy/deploy.log</code>.</p>';
        $out .= '<h3>Promote log</h3>';
        $ptail = self::log_tail('promote.log');
        $out .= $ptail
            ? '<pre style="background:#0d1117;color:#c9d1d9;padding:12px;border-radius:6px;max-height:200px;overflow:auto;font-size:12px;">' . esc_html($ptail) . '</pre>'
            : '<p class="description">promote.log is not readable from the web server (Go Live pipeline on hold).</p>';
        return $out;
    }

    public static function recs() {
        if (class_exists('Gabochie_Go_Live') && method_exists('Gabochie_Go_Live', 'recommendations')) {
            return Gabochie_Go_Live::recommendations();
        }
        return array();
    }

    public static function recs_html() {
        $recs = self::recs();
        if (empty($recs)) {
            return '<div class="notice notice-success" style="max-width:760px;"><p><strong>All checks passed.</strong> No open recommendations.</p></div>';
        }
        if (class_exists('Gabochie_Go_Live') && method_exists('Gabochie_Go_Live', 'recs_html')) {
            return Gabochie_Go_Live::recs_html($recs);
        }
        $sev = array('error' => '#d63638', 'warning' => '#dba617', 'info' => '#2271b1');
        $out = '<div style="max-width:760px;">';
        foreach ($recs as $r) {
            $c = isset($sev[$r['severity']]) ? $sev[$r['severity']] : $sev['info'];
            $out .= '<div style="display:flex;gap:10px;padding:10px 12px;border:1px solid #c3c4c7;border-left:4px solid ' . $c . ';background:#fff;margin-bottom:8px;border-radius:4px;align-items:flex-start;">'
                  . '<strong style="color:' . $c . ';">' . esc_html($r['title']) . '</strong>'
                  . '<div style="flex:1;color:#50575e;">' . esc_html($r['detail']) . '</div></div>';
        }
        $out .= '</div>';
        return $out;
    }

    public static function render() {
        if (!current_user_can('manage_options')) return;
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce(self::NONCE);
        ?>
        <div class="wrap">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <span class="dashicons dashicons-dashboard" style="color:#2271b1;"></span>
                Gabochie Command Center
                <span id="gcc-score-pill" class="button button-small" style="margin-left:auto;"></span>
            </h1>
            <p>Mission control for the autonomous company — agent status, DevOps/QA health, and the human-in-the-loop approvals queue. Drafted by agents, approved by you.</p>

            <h2>Situation Overview <button class="button button-small" id="gcc-refresh">Refresh</button></h2>
            <div id="gcc-cards"><?php echo self::status_cards_html(); ?></div>

            <h2>Company Agents</h2>
            <p class="description">Each agent has a tool surface and a human gate. Drafted = autonomous; everything client-facing and money-bearing needs your approval.</p>
            <div id="gcc-agents"><?php echo self::agents_html(); ?></div>

            <h2>DevOps &amp; QA Team</h2>
            <div id="gcc-qa"><?php echo self::qa_html(); ?></div>

            <h2>Approvals — Human in the Loop</h2>
            <p class="description">Auto items are derived from live state. Tick the standing decisions you have made so the crew records them.</p>
            <div id="gcc-approvals"><?php echo self::approvals_html(); ?></div>

            <h2>Alerts &amp; Smart Recommendations</h2>
            <div id="gcc-recs"><?php echo self::recs_html(); ?></div>

            <h2>Server Logs</h2>
            <div id="gcc-logs"><?php echo self::logs_html(); ?></div>

            <style>.gcc-item.gcc-done span{text-decoration:line-through;color:#8c8f94;}</style>
            <script>
            (function ($) {
                var CC = {
                    ajaxUrl: <?php echo wp_json_encode($ajax_url); ?>,
                    nonce: <?php echo wp_json_encode($nonce); ?>
                };
                function load() {
                    $.post(CC.ajaxUrl, { action: 'gabochie_cc_status', _wpnonce: CC.nonce }, function (r) {
                        if (!r || !r.success || !r.data) return;
                        if (r.data.cards) $('#gcc-cards').html(r.data.cards);
                        if (r.data.agents) $('#gcc-agents').html(r.data.agents);
                        if (r.data.qa) $('#gcc-qa').html(r.data.qa);
                        if (r.data.approvals) $('#gcc-approvals').html(r.data.approvals);
                        if (r.data.recs) $('#gcc-recs').html(r.data.recs);
                        if (r.data.logs) $('#gcc-logs').html(r.data.logs);
                        if (r.data.score !== undefined) $('#gcc-score-pill').text('Health: ' + r.data.score + '/100');
                        $('#gcc-agents input, #gcc-approvals input').off('change').on('change', function () {
                            if ($(this).closest('#gcc-approvals').length) { toggleApproval($(this)); }
                        });
                    });
                }
                function toggleApproval(cb) {
                    cb.prop('disabled', true);
                    $.post(CC.ajaxUrl, { action: 'gabochie_cc_approval_toggle', _wpnonce: CC.nonce, id: cb.data('id'), done: cb.is(':checked') ? 1 : 0 }, function () {
                        load();
                    }).fail(function () { cb.prop('disabled', false); });
                }
                $('#gcc-refresh').on('click', function (e) { e.preventDefault(); load(); });
                window.addEventListener('storage', function (ev) {
                    if (ev.key === 'gol-touch') load();
                });
                window.addEventListener('focus', load);
                setInterval(load, 30000);
                load();
            })(jQuery);
            </script>
        </div>
        <?php
    }

    public static function ajax_status() {
        check_ajax_referer(self::NONCE, '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'), 403);
        $hs = self::health_score();
        wp_send_json_success(array(
            'cards' => self::status_cards_html(),
            'agents' => self::agents_html(),
            'qa' => self::qa_html(),
            'approvals' => self::approvals_html(),
            'recs' => self::recs_html(),
            'logs' => self::logs_html(),
            'score' => $hs['score'],
        ));
    }

    public static function ajax_approval_toggle() {
        check_ajax_referer(self::NONCE, '_wpnonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'), 403);
        $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        $done = !empty($_POST['done']) ? 1 : 0;
        $items = self::approvals_persisted();
        foreach ($items as &$i) {
            if ($i['id'] === $id) {
                $i['done'] = $done;
                update_option(self::OPTION_APPROVALS, $items, false);
                wp_send_json_success(array('ok' => true));
                return;
            }
        }
        wp_send_json_error(array('message' => 'Approval item not found'), 404);
    }
}

Gabochie_Command_Center::init();
