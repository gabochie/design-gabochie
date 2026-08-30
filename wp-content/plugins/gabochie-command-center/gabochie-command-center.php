<?php
/**
 * Plugin Name: Gabochie Command Center
 * Description: Revenue Command Center v2 — coordinated crews (Marketing, Sales, After-Sales, RevenueOps), live funnel/KPI engine vs the GH₵1M/yr target, orchestrator task log + ops digest, and human-in-the-loop approvals.
 * Version: 2.0.0
 * Author: Gabochie Design
 * License: GPL-2.0+
 * Text Domain: gabochie-command-center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-gabochie-cc-funnel.php';
require_once __DIR__ . '/includes/class-gabochie-cc-agents.php';
require_once __DIR__ . '/includes/class-gabochie-cc-orchestrator.php';

/**
 * Gabochie Command Center core.
 */
class Gabochie_Command_Center {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '2.0.0';

	/**
	 * Approvals option key.
	 *
	 * @var string
	 */
	const OPTION_APPROVALS = 'gabochie_cc_approvals';

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	const NONCE = 'gabochie_cc_nonce';

	/**
	 * Hook everything.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'wp_ajax_gabochie_cc_status', array( __CLASS__, 'ajax_status' ) );
		add_action( 'wp_ajax_gabochie_cc_approval_toggle', array( __CLASS__, 'ajax_approval_toggle' ) );
		add_action( 'init', array( __CLASS__, 'schedule_digest' ) );

		Gabochie_CC_Orchestrator::init();
	}

	/**
	 * Ensure the twice-daily RevenueOps digest is scheduled.
	 */
	public static function schedule_digest() {
		if ( ! wp_next_scheduled( Gabochie_CC_Orchestrator::CRON ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'gabochie_cc_twice_daily', Gabochie_CC_Orchestrator::CRON );
		}
	}

	/**
	 * Deploy log dir (filterable).
	 *
	 * @return string
	 */
	public static function deploy_dir() {
		return apply_filters( 'gabochie_cc_deploy_dir', '/home/gabozvqd/deploy' );
	}

	/**
	 * Register the admin menu.
	 */
	public static function menu() {
		add_menu_page(
			'Command Center',
			'Command Center',
			'manage_options',
			'gabochie-command-center',
			array( __CLASS__, 'render' ),
			'dashicons-dashboard',
			2
		);
	}

	/**
	 * Can we shell out?
	 *
	 * @return bool
	 */
	public static function shell_ok() {
		if ( ! function_exists( 'shell_exec' ) ) {
			return false;
		}
		$disabled = array_map( 'trim', explode( ',', ini_get( 'disable_functions' ) ) );
		return ! in_array( 'shell_exec', $disabled, true ) && ! in_array( 'exec', $disabled, true );
	}

	/**
	 * Run a shell command, trimmed output.
	 *
	 * @param string $cmd Command.
	 * @return string
	 */
	public static function run( $cmd ) {
		$out = shell_exec( $cmd . ' 2>&1' );
		return is_string( $out ) ? trim( $out ) : '';
	}

	/**
	 * Is an endpoint 200?
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public static function endpoint_ok( $path ) {
		$key    = 'gabochie_cc_ep_' . md5( $path );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return $cached;
		}
		$res = wp_remote_get(
			home_url( $path ),
			array(
				'timeout'     => 8,
				'sslverify'   => false,
				'redirection' => 2,
			)
		);
		$ok  = ! is_wp_error( $res ) && 200 === (int) wp_remote_retrieve_response_code( $res );
		set_transient( $key, $ok, 120 );
		return $ok;
	}

	/**
	 * WP environment type.
	 *
	 * @return string
	 */
	public static function env_type() {
		if ( function_exists( 'wp_get_environment_type' ) ) {
			return wp_get_environment_type();
		}
		return 'unknown';
	}

	/**
	 * Payments state (gateway env + orders flowing).
	 *
	 * @return array
	 */
	public static function payments_state() {
		global $wpdb;
		$state = array(
			'expresspay_env' => 'n/a',
			'pmpro_gateway'  => '',
			'pmpro_env'      => 'n/a',
			'orders'         => 0,
		);
		$ep    = get_option( 'woocommerce_expresspay_gateway_settings', array() );
		if ( is_array( $ep ) ) {
			$state['expresspay_env'] = ! empty( $ep['environment'] ) ? $ep['environment'] : 'production';
		}
		$state['pmpro_gateway'] = (string) get_option( 'pmpro_gateway', '' );
		$state['pmpro_env']     = (string) get_option( 'pmpro_gateway_environment', 'sandbox' );
		$woo_table              = $wpdb->prefix . 'wc_orders';
		$woo_exists             = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $woo_table ) );
		if ( $woo_exists ) {
			$state['orders'] = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %1$s WHERE status NOT IN (%2$s,%3$s)',
					$woo_table,
					'auto-draft',
					'checkout-draft'
				)
			);
		} else {
			$state['orders'] = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_status IN ('wc-completed','wc-processing','wc-on-hold','publish')"
			);
		}
		return $state;
	}

	/**
	 * AI/LLM state.
	 *
	 * @return array
	 */
	public static function ai_state() {
		$provider  = get_option( 'ghbc_llm_provider', '' );
		$key       = get_option( 'ghbc_llm_key', '' );
		$fallbacks = get_option( 'ghbc_llm_fallbacks', array() );
		$n_fb      = is_array( $fallbacks ) ? count( $fallbacks ) : 0;
		$valid     = false;
		if ( $provider && 'ollama' !== $provider ) {
			$valid = ! empty( $key ) && ! ( 'claude' === $provider && 0 !== strpos( $key, 'sk-ant-' ) );
		}
		if ( 'ollama' === $provider ) {
			$valid = true;
		}
		return array(
			'provider'  => $provider ? $provider : 'none',
			'has_key'   => (bool) $key,
			'valid'     => $valid,
			'fallbacks' => $n_fb,
		);
	}

	/**
	 * Active member count.
	 *
	 * @return int
	 */
	public static function members_count() {
		global $wpdb;
		$t = $wpdb->prefix . 'pmpro_memberships_users';
		if ( ! Gabochie_CC_Funnel::table_exists( $t ) ) {
			return 0;
		}
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status = 'active'" );
	}

	/**
	 * Any security plugin active?
	 *
	 * @return bool
	 */
	public static function has_security_plugin() {
		$sec = array( 'wordfence', 'all-in-one-wp-security', 'solid-security', 'limit-login-attempts-reloaded', 'google-authenticator', 'two-factor', 'wp-2fa', 'ithemes-security' );
		foreach ( (array) get_option( 'active_plugins', array() ) as $p ) {
			foreach ( $sec as $s ) {
				if ( false !== stripos( $p, $s ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Last SMOKE TEST line from deploy.log.
	 *
	 * @return array
	 */
	public static function last_smoke() {
		$f = self::deploy_dir() . '/deploy.log';
		if ( ! is_readable( $f ) ) {
			return array(
				'found' => false,
				'line'  => 'deploy.log not readable from web',
			);
		}
		$lines = file( $f );
		if ( ! $lines ) {
			return array(
				'found' => false,
				'line'  => 'deploy.log unreadable',
			);
		}
		for ( $i = count( $lines ) - 1; $i >= 0; $i-- ) {
			if ( false !== strpos( $lines[ $i ], 'SMOKE TEST' ) ) {
				return array(
					'found' => true,
					'line'  => trim( $lines[ $i ] ),
				);
			}
		}
		return array(
			'found' => false,
			'line'  => 'no SMOKE TEST line yet',
		);
	}

	/**
	 * Tail of a deploy log file.
	 *
	 * @param string $file File name.
	 * @param int    $lines Lines to read.
	 * @return string|null
	 */
	public static function log_tail( $file, $lines = 12 ) {
		$f = self::deploy_dir() . '/' . $file;
		if ( ! is_readable( $f ) ) {
			return null;
		}
		$all = file( $f );
		if ( ! $all ) {
			return null;
		}
		return implode( '', array_slice( $all, -$lines ) );
	}

	/**
	 * Live deploy commit hash.
	 *
	 * @return string
	 */
	public static function live_commit() {
		if ( ! self::shell_ok() ) {
			return 'n/a';
		}
		$dir = '/home/gabozvqd/gabochie-deploy';
		$out = self::run( 'git -C ' . escapeshellarg( $dir ) . ' log -1 --format=%h 2>/dev/null' );
		return $out ? $out : 'n/a';
	}

	/**
	 * QA checklist progress.
	 *
	 * @return array
	 */
	public static function qa_progress() {
		$counts = array(
			'gates'   => array( 0, 0 ),
			'roadmap' => array( 0, 0 ),
		);
		foreach ( array(
			'gates'   => 'gabochie_qa_gates',
			'roadmap' => 'gabochie_qa_roadmap',
		) as $key => $opt ) {
			$items = get_option( $opt, array() );
			if ( ! is_array( $items ) ) {
				$items = array();
			}
			$done = 0;
			foreach ( $items as $i ) {
				if ( ! empty( $i['done'] ) ) {
					++$done;
				}
			}
			$counts[ $key ] = array( $done, count( $items ) );
		}
		return $counts;
	}

	/**
	 * Derived (auto) approval items.
	 *
	 * @return array
	 */
	public static function approvals_derived() {
		$items = array();
		$p     = self::payments_state();
		if ( 'sandbox' === $p['expresspay_env'] || 'sandbox' === $p['pmpro_env'] ) {
			$items[] = array(
				'id'       => 'der-pay',
				'severity' => 'high',
				'text'     => 'Payments are in sandbox — switch ExpressPay/PMPro to live credentials so customers can actually pay.',
			);
		}
		if ( empty( $p['pmpro_gateway'] ) ) {
			$items[] = array(
				'id'       => 'der-pmpro',
				'severity' => 'high',
				'text'     => 'PMPro payment gateway is not set — memberships cannot be charged.',
			);
		}
		$ai = self::ai_state();
		if ( ! $ai['valid'] ) {
			$items[] = array(
				'id'       => 'der-ai',
				'severity' => 'high',
				'text'     => 'AI/LLM key is missing or invalid for provider "' . $ai['provider'] . '" — add a valid key in GhBC Settings and Test Connection.',
			);
		}
		return $items;
	}

	/**
	 * Persisted approvals (seeded with defaults on first read).
	 *
	 * @return array
	 */
	public static function approvals_persisted() {
		$items = get_option( self::OPTION_APPROVALS );
		if ( ! is_array( $items ) ) {
			$items = self::approvals_defaults();
			update_option( self::OPTION_APPROVALS, $items, false );
			return $items;
		}
		$have = array();
		foreach ( $items as $i ) {
			if ( isset( $i['id'] ) ) {
				$have[ $i['id'] ] = true;
			}
		}
		$added = false;
		foreach ( self::approvals_defaults() as $d ) {
			if ( ! empty( $have[ $d['id'] ] ) ) {
				continue;
			}
			$items[] = $d;
			$added   = true;
		}
		if ( $added ) {
			update_option( self::OPTION_APPROVALS, $items, false );
		}
		return $items;
	}

	/**
	 * Standing approval defaults.
	 *
	 * @return array
	 */
	public static function approvals_defaults() {
		return array(
			array(
				'id'   => 'ap-expresspay',
				'text' => 'ExpressPay live merchant keys obtained (or explicitly confirmed sandbox for now).',
				'done' => 0,
			),
			array(
				'id'   => 'ap-prices',
				'text' => 'Price list confirmed: membership levels + digital product prices for launch.',
				'done' => 0,
			),
			array(
				'id'   => 'ap-ghbc-key',
				'text' => 'GhBC Claude API key added and Test Connection passes.',
				'done' => 0,
			),
			array(
				'id'   => 'ap-brevo',
				'text' => 'Brevo API key added so email/forms can send.',
				'done' => 0,
			),
			array(
				'id'   => 'ap-content',
				'text' => 'Content source of truth decided (live DB vs new build content).',
				'done' => 0,
			),
			array(
				'id'   => 'ap-plugins',
				'text' => 'Decision on the 7 missing old-live plugins (reinstall or confirm skip).',
				'done' => 0,
			),
		);
	}

	/**
	 * Approvals HTML (auto derived + persisted).
	 *
	 * @return string
	 */
	public static function approvals_html() {
		$out     = '';
		$derived = self::approvals_derived();
		foreach ( $derived as $i ) {
			$color = 'high' === $i['severity'] ? '#d63638' : '#dba617';
			$out  .= '<div style="display:flex;gap:10px;padding:10px 12px;border:1px solid #c3c4c7;border-left:4px solid ' . $color . ';background:#fff;margin-bottom:8px;border-radius:4px;align-items:flex-start;">'
				. '<span style="font-weight:600;color:' . $color . ';white-space:nowrap;font-size:12px;text-transform:uppercase;">Auto</span>'
				. '<div style="flex:1;">' . esc_html( $i['text'] ) . '</div></div>';
		}
		$out .= '<div id="gcc-approvals">';
		foreach ( self::approvals_persisted() as $i ) {
			$out .= self::approval_item_html( $i );
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * One approval row.
	 *
	 * @param array $i Item.
	 * @return string
	 */
	public static function approval_item_html( $i ) {
		$cid     = esc_attr( $i['id'] );
		$ctext   = esc_html( $i['text'] );
		$cls     = ! empty( $i['done'] ) ? 'gcc-done' : '';
		$checked = ! empty( $i['done'] ) ? 'checked' : '';
		return '<label class="gcc-item ' . $cls . '" style="display:flex;gap:8px;align-items:flex-start;padding:7px 4px;border-bottom:1px solid #f0f0f0;">'
			. '<input type="checkbox" data-id="' . $cid . '" ' . $checked . ' style="margin-top:2px;">'
			. '<span style="flex:1;">' . $ctext . '</span></label>';
	}

	/**
	 * Legacy situation card grid.
	 *
	 * @return string
	 */
	public static function status_cards_html() {
		$hs       = self::health_score();
		$pay      = self::payments_state();
		$ai       = self::ai_state();
		$smoke    = self::last_smoke();
		$qa       = self::qa_progress();
		$smoke_ok = $smoke['found'] && false !== stripos( $smoke['line'], 'PASS' );
		$commit   = self::live_commit();
		$pay_ok   = 'sandbox' !== $pay['expresspay_env'] && 'sandbox' !== $pay['pmpro_env'] && ! empty( $pay['pmpro_gateway'] );
		$cards    = array(
			array(
				't' => 'Mission Health',
				'v' => $hs['score'] . '/100',
				's' => $hs['label'][1],
				'c' => $hs['label'][0],
				'd' => count( $hs['checks'] ) . ' systems checked',
			),
			array(
				't' => 'Payments',
				'v' => $pay['orders'] . ' orders',
				's' => $pay_ok ? 'Live' : 'Sandbox',
				'c' => $pay_ok ? '#0a7d32' : '#dba617',
				'd' => 'ExpressPay ' . $pay['expresspay_env'] . ' | PMPro ' . $pay['pmpro_env'],
			),
			array(
				't' => 'AI Agents',
				'v' => ucfirst( $ai['provider'] ),
				's' => $ai['valid'] ? 'Ready' : 'Needs key',
				'c' => $ai['valid'] ? '#0a7d32' : '#d63638',
				'd' => ( $ai['fallbacks'] ? $ai['fallbacks'] . ' fallback(s)' : 'no fallbacks' ),
			),
			array(
				't' => 'Deploy & QA',
				'v' => $commit,
				's' => $smoke_ok ? 'Smoke PASS' : ( $smoke['found'] ? 'Smoke FAIL' : 'No log' ),
				'c' => $smoke_ok ? '#0a7d32' : '#8c8f94',
				'd' => 'Gates ' . $qa['gates'][0] . '/' . $qa['gates'][1] . ' | Roadmap ' . $qa['roadmap'][0] . '/' . $qa['roadmap'][1],
			),
			array(
				't' => 'Environment',
				'v' => self::env_type(),
				's' => 'WP ' . get_bloginfo( 'version' ),
				'c' => '#2271b1',
				'd' => 'PHP ' . PHP_VERSION . ' | DB ' . self::db_size_mb() . ' MB',
			),
			array(
				't' => 'Members',
				'v' => (string) self::members_count(),
				's' => 'active',
				'c' => '#2271b1',
				'd' => 'PMPro memberships',
			),
		);
		$out      = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:12px;">';
		foreach ( $cards as $c ) {
			$out .= '<div style="border:1px solid #c3c4c7;border-top:4px solid ' . $c['c'] . ';background:#fff;border-radius:6px;padding:14px;">'
				. '<div style="color:#8c8f94;font-size:11px;text-transform:uppercase;letter-spacing:.05em;">' . esc_html( $c['t'] ) . '</div>'
				. '<div style="font-size:22px;font-weight:700;margin:4px 0;">' . esc_html( $c['v'] ) . '</div>'
				. '<div style="font-weight:600;color:' . $c['c'] . ';font-size:12px;">' . esc_html( $c['s'] ) . '</div>'
				. '<div style="color:#50575e;font-size:12px;margin-top:6px;">' . esc_html( $c['d'] ) . '</div></div>';
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * DB size in MB.
	 *
	 * @return float
	 */
	public static function db_size_mb() {
		global $wpdb;
		$size = $wpdb->get_var(
			'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) FROM information_schema.TABLES WHERE table_schema = DATABASE()'
		);
		return is_numeric( $size ) ? (float) $size : 0;
	}

	/**
	 * QA progress HTML.
	 *
	 * @return string
	 */
	public static function qa_html() {
		$qa     = self::qa_progress();
		$bar    = static function ( $done, $total, $color ) {
			$pct = $total ? (int) round( 100 * $done / $total ) : 0;
			return '<div style="background:#e5e5e5;border-radius:99px;height:10px;max-width:420px;margin-top:6px;"><div style="background:' . $color . ';height:10px;border-radius:99px;width:' . $pct . '%;"></div></div>';
		};
		$smoke  = self::last_smoke();
		$commit = self::live_commit();
		$out    = '<table class="widefat striped" style="max-width:760px;"><tbody>';
		$out   .= '<tr><th style="width:34%;">Live deploy commit</th><td><code>' . esc_html( $commit ) . '</code></td></tr>';
		$out   .= '<tr><th>Last smoke test</th><td>' . esc_html( $smoke['line'] ) . '</td></tr>';
		$out   .= '<tr><th>Release QA — 12 gates</th><td>' . esc_html( $qa['gates'][0] . ' / ' . $qa['gates'][1] ) . $bar( $qa['gates'][0], $qa['gates'][1], '#2271b1' ) . '</td></tr>';
		$out   .= '<tr><th>QA roadmap (Phases 1–10)</th><td>' . esc_html( $qa['roadmap'][0] . ' / ' . $qa['roadmap'][1] ) . $bar( $qa['roadmap'][0], $qa['roadmap'][1], '#46b450' ) . '</td></tr>';
		$out   .= '</tbody></table>';
		return $out;
	}

	/**
	 * Logs section HTML.
	 *
	 * @return string
	 */
	public static function logs_html() {
		$out   = '<h3>Deploy log</h3>';
		$tail  = self::log_tail( 'deploy.log' );
		$out  .= $tail
			? '<pre style="background:#0d1117;color:#c9d1d9;padding:12px;border-radius:6px;max-height:260px;overflow:auto;font-size:12px;">' . esc_html( $tail ) . '</pre>'
			: '<p class="description">deploy.log is not readable from the web server. Deploy details stay server-side at <code>~/deploy/deploy.log</code>.</p>';
		$out  .= '<h3>Promote log</h3>';
		$ptail = self::log_tail( 'promote.log' );
		$out  .= $ptail
			? '<pre style="background:#0d1117;color:#c9d1d9;padding:12px;border-radius:6px;max-height:200px;overflow:auto;font-size:12px;">' . esc_html( $ptail ) . '</pre>'
			: '<p class="description">promote.log is not readable from the web server (Go Live pipeline on hold).</p>';
		return $out;
	}

	/**
	 * Recommendations delegate (Go Live plugin).
	 *
	 * @return array
	 */
	public static function recs() {
		if ( class_exists( 'Gabochie_Go_Live' ) && method_exists( 'Gabochie_Go_Live', 'recommendations' ) ) {
			return Gabochie_Go_Live::recommendations();
		}
		return array();
	}

	/**
	 * Recommendations HTML.
	 *
	 * @return string
	 */
	public static function recs_html() {
		$recs = self::recs();
		if ( empty( $recs ) ) {
			return '<div class="notice notice-success" style="max-width:760px;"><p><strong>All checks passed.</strong> No open recommendations.</p></div>';
		}
		if ( class_exists( 'Gabochie_Go_Live' ) && method_exists( 'Gabochie_Go_Live', 'recs_html' ) ) {
			return Gabochie_Go_Live::recs_html( $recs );
		}
		$sev = array(
			'error'   => '#d63638',
			'warning' => '#dba617',
			'info'    => '#2271b1',
		);
		$out = '<div style="max-width:760px;">';
		foreach ( $recs as $r ) {
			$c    = isset( $sev[ $r['severity'] ] ) ? $sev[ $r['severity'] ] : $sev['info'];
			$out .= '<div style="display:flex;gap:10px;padding:10px 12px;border:1px solid #c3c4c7;border-left:4px solid ' . $c . ';background:#fff;margin-bottom:8px;border-radius:4px;align-items:flex-start;">'
				. '<strong style="color:' . $c . ';">' . esc_html( $r['title'] ) . '</strong>'
				. '<div style="flex:1;color:#50575e;">' . esc_html( $r['detail'] ) . '</div></div>';
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * Healthy? (endpoint sweep + serivce checks).
	 *
	 * @return array
	 */
	public static function health_score() {
		$score  = 0;
		$checks = array();
		foreach ( array( '/', '/wp-login.php', '/wp-json/' ) as $p ) {
			$ok = self::endpoint_ok( $p );
			if ( $ok ) {
				$score += 10;
			}
			$checks[] = array( $p, $ok );
		}
		$pay            = self::payments_state();
		$pay_ok         = 'sandbox' !== $pay['expresspay_env'] && 'sandbox' !== $pay['pmpro_env'] && ! empty( $pay['pmpro_gateway'] );
		$score         += $pay_ok ? 20 : 5;
		$checks[]       = array( 'Payments live', $pay_ok );
		$ai             = self::ai_state();
		$score         += $ai['valid'] ? 15 : 3;
		$checks[]       = array( 'AI key valid', $ai['valid'] );
		$has_sec        = self::has_security_plugin();
		$score         += $has_sec ? 10 : 2;
		$checks[]       = array( 'Security plugin', $has_sec );
		$smoke          = self::last_smoke();
		$smoke_ok       = $smoke['found'] && false !== stripos( $smoke['line'], 'PASS' );
		$score         += $smoke_ok ? 15 : 0;
		$checks[]       = array( 'Last smoke PASS', $smoke_ok );
		$orders_flowing = self::payments_state()['orders'] > 0;
		$score         += $orders_flowing ? 10 : 2;
		$checks[]       = array( 'Orders flowing', $orders_flowing );
		if ( $score >= 80 ) {
			$label = array( '#0a7d32', 'Excellent' );
		} elseif ( $score >= 60 ) {
			$label = array( '#46b450', 'Good' );
		} elseif ( $score >= 40 ) {
			$label = array( '#dba617', 'Fair' );
		} else {
			$label = array( '#d63638', 'Critical' );
		}
		return array(
			'score'  => $score,
			'label'  => $label,
			'checks' => $checks,
		);
	}

	/**
	 * Legacy agents HTML (kept as part of situation overview).
	 *
	 * @return string
	 */
	public static function agents_html() {
		$pay          = self::payments_state();
		$ai           = self::ai_state();
		$smoke        = self::last_smoke();
		$qa           = self::qa_progress();
		$pay_ok       = 'sandbox' !== $pay['expresspay_env'] && 'sandbox' !== $pay['pmpro_env'] && ! empty( $pay['pmpro_gateway'] );
		$ai_ok        = $ai['valid'];
		$smoke_ok     = $smoke['found'] && false !== stripos( $smoke['line'], 'PASS' );
		$endpoints_ok = self::endpoint_ok( '/' ) && self::endpoint_ok( '/wp-login.php' ) && self::endpoint_ok( '/wp-json/' );
		$members      = self::members_count();
		$light        = static function ( $ok ) {
			if ( true === $ok ) {
				return array( '#46b450', 'On mission' );
			}
			if ( false === $ok ) {
				return array( '#d63638', 'Needs attention' );
			}
			return array( '#8c8f94', 'Standby' );
		};
		$agents       = array(
			array(
				'icon'  => 'dashicons-megaphone',
				'name'  => 'CMO',
				'role'  => 'Content, SEO & campaigns',
				'task'  => 'AI content pipeline awaiting go-ahead; draft + human review + publish.',
				'state' => 'production' !== self::env_type() ? array( '#8c8f94', 'Standby' ) : array( '#dba617', 'Setup' ),
				'gate'  => 'Publish approval',
			),
			array(
				'icon'  => 'dashicons-email-alt',
				'name'  => 'SDR',
				'role'  => 'Lead qualification',
				'task'  => 'Captures leads + runs nurture sequences (FluentCRM) once Brevo key is set.',
				'state' => array( '#8c8f94', 'Standby' ),
				'gate'  => 'Soft — notify only',
			),
			array(
				'icon'  => 'dashicons-media-text',
				'name'  => 'Closer',
				'role'  => 'Proposals & quotes',
				'task'  => $pay_ok ? 'Ready to draft proposals from intake.' : 'Blocked — payments still sandbox.',
				'state' => $pay_ok ? array( '#46b450', 'Ready' ) : array( '#d63638', 'Blocked' ),
				'gate'  => 'Approve every quote',
			),
			array(
				'icon'  => 'dashicons-building',
				'name'  => 'Delivery',
				'role'  => 'Architect — GhBC, BOQ, feasibility',
				'task'  => $ai_ok ? 'LLM stack ready for compliance/BOQ work.' : 'Needs a valid AI key to run GhBC tools.',
				'state' => $ai_ok ? array( '#46b450', 'Ready' ) : array( '#d63638', 'Blocked' ),
				'gate'  => 'Approve every deliverable',
			),
			array(
				'icon'  => 'dashicons-sos',
				'name'  => 'CS',
				'role'  => 'Support & retention',
				'task'  => 'Answers questions, escalates to you on exceptions.',
				'state' => array( '#8c8f94', 'Standby' ),
				'gate'  => 'Escalation to human',
			),
			array(
				'icon'  => 'dashicons-chart-line',
				'name'  => 'Ops',
				'role'  => 'Back-office & reporting',
				'task'  => 'Orders: ' . $pay['orders'] . ' | Active members: ' . $members . '. Weekly ops brief once wired.',
				'state' => $pay['orders'] > 0 ? array( '#46b450', 'Active' ) : array( '#8c8f94', 'Standby' ),
				'gate'  => 'Money movement',
			),
			array(
				'icon'  => 'dashicons-admin-tools',
				'name'  => 'QA/DevOps',
				'role'  => 'Test, monitor & deploy',
				'task'  => 'Smoke: ' . ( $smoke['found'] ? ( $smoke_ok ? 'PASS' : 'FAIL' ) : 'not logged' ) . ' | Gates: ' . $qa['gates'][0] . '/' . $qa['gates'][1] . ' | Roadmap: ' . $qa['roadmap'][0] . '/' . $qa['roadmap'][1],
				'state' => $smoke_ok ? array( '#46b450', 'Green' ) : ( $smoke['found'] ? array( '#d63638', 'Needs attention' ) : array( '#8c8f94', 'No log yet' ) ),
				'gate'  => 'Deploy to live',
			),
			array(
				'icon'  => 'dashicons-networking',
				'name'  => 'Orchestrator',
				'role'  => 'Routes tasks, owns escalation',
				'task'  => 'Overall mission: ' . ( $endpoints_ok ? 'site online' : 'site offline' ) . ' — routes work to specialists.',
				'state' => $endpoints_ok ? array( '#46b450', 'Healthy' ) : array( '#d63638', 'Critical' ),
				'gate'  => 'Exceptions & money',
			),
		);
		$out          = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;">';
		foreach ( $agents as $a ) {
			$c    = $a['state'][0];
			$out .= '<div style="border:1px solid #c3c4c7;border-top:4px solid ' . $c . ';background:#fff;border-radius:6px;padding:14px;">'
				. '<div style="display:flex;align-items:center;gap:10px;"><span class="dashicons ' . esc_attr( $a['icon'] ) . '" style="color:' . $c . ';font-size:28px;width:28px;height:28px;"></span>'
				. '<div style="flex:1;"><strong style="font-size:15px;">' . esc_html( $a['name'] ) . '</strong>'
				. '<div style="color:#50575e;font-size:12px;">' . esc_html( $a['role'] ) . '</div></div>'
				. '<span style="background:' . $c . ';color:#fff;font-size:10px;font-weight:600;text-transform:uppercase;padding:2px 8px;border-radius:99px;white-space:nowrap;">' . esc_html( $a['state'][1] ) . '</span></div>'
				. '<p style="margin:10px 0 4px;color:#3c434a;font-size:13px;">' . esc_html( $a['task'] ) . '</p>'
				. '<div style="font-size:11px;color:#8c8f94;text-transform:uppercase;letter-spacing:.03em;">Human gate: ' . esc_html( $a['gate'] ) . '</div>'
				. '</div>';
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * Tab list.
	 *
	 * @return array
	 */
	public static function tabs() {
		return array(
			'overview'     => array( 'Overview', array( __CLASS__, 'render_overview' ) ),
			'revenue'      => array( 'Revenue &amp; Funnel', array( 'Gabochie_CC_Funnel', 'render_revenue_tab' ) ),
			'agents'       => array( 'Crews &amp; Agents', array( __CLASS__, 'render_agents_tab' ) ),
			'orchestrator' => array( 'Orchestrator &amp; Tasks', array( 'Gabochie_CC_Orchestrator', 'render_orchestrator_tab' ) ),
		);
	}

	/**
	 * Render the whole page (tabs + active tab).
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_GET['recalc'] ) && '1' === $_GET['recalc'] ) {
			Gabochie_CC_Funnel::metrics( true ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
		$tabs = self::tabs();
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'overview';
		}
		$active_url = admin_url( 'admin.php?page=gabochie-command-center' );
		?>
		<div class="wrap">
			<h1 style="display:flex;align-items:center;gap:10px;">
				<span class="dashicons dashicons-dashboard" style="color:#2271b1;"></span>
				Gabochie Command Center
				<span style="margin-left:auto;font-size:12px;color:#50575e;">v<?php echo esc_html( self::VERSION ); ?> · target GH₵1M/yr</span>
			</h1>
			<p>Revenue Command Center — crews from social strategy to the dollar, wired to live funnel data with human gates on everything client-facing and money-bearing.</p>
			<nav class="nav-tab-wrapper">
				<?php
				foreach ( $tabs as $key => $t ) {
					$cls = $tab === $key ? ' nav-tab-active' : '';
					$url = $active_url . '&tab=' . $key;
					echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . esc_attr( $cls ) . '">' . $t[0] . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</nav>
			<div style="margin-top:16px;">
				<?php
				call_user_func( $tabs[ $tab ][1] );
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Overview tab (legacy situation board + JS refresh).
	 */
	public static function render_overview() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$ajax_url = admin_url( 'admin-ajax.php' );
		$nonce    = wp_create_nonce( self::NONCE );
		?>
		<h2>Situation Overview <button class="button button-small" id="gcc-refresh">Refresh</button></h2>
		<div id="gcc-cards"><?php echo self::status_cards_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

		<h2>Legacy Agents</h2>
		<p class="description">The v1 situation board. The v2 operating model lives under Crews &amp; Agents.</p>
		<div id="gcc-agents"><?php echo self::agents_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

		<h2>DevOps &amp; QA Team</h2>
		<div id="gcc-qa"><?php echo self::qa_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

		<h2>Approvals — Human in the Loop</h2>
		<p class="description">Auto items are derived from live state. Tick the standing decisions you have made so the crew records them.</p>
		<div id="gcc-approvals"><?php echo self::approvals_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

		<h2>Alerts &amp; Smart Recommendations</h2>
		<div id="gcc-recs"><?php echo self::recs_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

		<h2>Server Logs</h2>
		<div id="gcc-logs"><?php echo self::logs_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

		<style>.gcc-item.gcc-done span{text-decoration:line-through;color:#8c8f94;}</style>
		<script>
		(function ($) {
			var CC = {
				ajaxUrl: <?php echo wp_json_encode( $ajax_url ); ?>,
				nonce: <?php echo wp_json_encode( $nonce ); ?>
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
			$(document).on('change', '#gcc-approvals input', function () { toggleApproval($(this)); });
			load();
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Crews & Agents tab (v2 operating model).
	 */
	public static function render_agents_tab() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<h2>Crews &amp; Agents</h2>';
		echo '<p class="description">Four crews own the funnel end-to-end. Every agent has tools, a human gate, and a live state colour. '
			. '<strong style="color:#d63638;">Red</strong> = blocked · <strong style="color:#dba617;">amber</strong> = needs setup · <strong style="color:#8c8f94;">grey</strong> = standby · <strong style="color:#46b450;">green</strong> = on mission.</p>';
		echo Gabochie_CC_Agents::crews_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * AJAX status payload (overview tab).
	 */
	public static function ajax_status() {
		check_ajax_referer( self::NONCE, '_wpnonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}
		wp_send_json_success(
			array(
				'cards'     => self::status_cards_html(),
				'agents'    => self::agents_html(),
				'qa'        => self::qa_html(),
				'approvals' => self::approvals_html(),
				'recs'      => self::recs_html(),
				'logs'      => self::logs_html(),
			)
		);
	}

	/**
	 * AJAX approval toggle.
	 */
	public static function ajax_approval_toggle() {
		check_ajax_referer( self::NONCE, '_wpnonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}
		$id    = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		$done  = ! empty( $_POST['done'] ) ? 1 : 0;
		$items = self::approvals_persisted();
		foreach ( $items as &$i ) {
			if ( $i['id'] === $id ) {
				$i['done'] = $done;
				update_option( self::OPTION_APPROVALS, $items, false );
				wp_send_json_success( array( 'ok' => true ) );
				return;
			}
		}
		wp_send_json_error( array( 'message' => 'Approval item not found' ), 404 );
	}
}

Gabochie_Command_Center::init();