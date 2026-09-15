<?php
/**
 * Gabochie Command Center — orchestrator.
 *
 * The event bus. Listens for the real funnel signals this system already
 * produces (lead captured, membership activity, WooCommerce orders), persists
 * tasks for the assigned crews, and ships the daily RevenueOps digest so the
 * operator sees the revenue story without logging in.
 *
 * @package Gabochie_Command_Center
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orchestrator: hooks, task log, digest.
 */
class Gabochie_CC_Orchestrator {

	/** @var string */
	const TABLE = 'gcc_tasks';

	/** @var string */
	const OPT_STATUS = 'gabochie_cc_status';

	/** @var string */
	const CRON = 'gabochie_cc_digest_hourly';

	/** @var string */
	const ACTION = 'gabochie_cc_test_event';

	/** @var array Agent→task kind mapping. */
	const AGENT_TASK = array(
		'nurture_agent'        => 'nurture',
		'sdr'                  => 'qualify',
		'membership_converter' => 'convert',
		'cs_support'           => 'support',
		'retention'            => 'retain',
		'upsell'               => 'upsell',
		'payments_agent'       => 'payments',
		'closer'               => 'close',
	);

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'gabochie/lead/captured', array( __CLASS__, 'on_lead_captured' ), 10, 5 );
		add_action( 'pmpro_after_change_membership_level', array( __CLASS__, 'on_membership_change' ), 10, 3 );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'on_order_completed' ) );
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'on_order_completed' ) );
		add_action( self::CRON, array( __CLASS__, 'digest_hourly' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'schedule' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'admin_post_test' ) );
		add_action( 'admin_notices', array( __CLASS__, 'run_idle' ) );
	}

	/**
	 * Add a "twice daily" schedule.
	 *
	 * @param array $schedules Cron schedules.
	 * @return array
	 */
	public static function schedule( $schedules ) {
		$schedules['gabochie_cc_twice_daily'] = array(
			'interval' => 12 * HOUR_IN_SECONDS,
			'display'  => 'Twice daily (Command Center)',
		);
		return $schedules;
	}

	/**
	 * Task log rows (latest first).
	 *
	 * @param int $limit Max rows.
	 * @return array
	 */
	public static function tasks( $limit = 20 ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i ORDER BY id DESC LIMIT %d',
				$table,
				( $limit > 0 && $limit <= 200 ) ? $limit : 20
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Full task table name (escaped).
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Create/upgrade the tasks table.
	 */
	public static function maybe_create_table() {
		global $wpdb;
		$table = self::table_name();
		if ( Gabochie_CC_Funnel::table_exists( $table ) ) {
			return;
		}
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			time datetime NOT NULL,
			agent varchar(40) NOT NULL,
			kind varchar(40) NOT NULL,
			event varchar(40) NOT NULL,
			severity varchar(10) NOT NULL DEFAULT 'info',
			summary varchar(255) NOT NULL,
			meta_text text NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			handled tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY agent (agent),
			KEY handled (handled)
		) $charset;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		dbDelta( apply_filters( 'gabochie_cc_tasks_schema', $sql ) );
	}

	/**
	 * Insert a task.
	 *
	 * @param string $agent   Agent id.
	 * @param string $kind    Task kind.
	 * @param string $event   Event that raised it.
	 * @param string $summary Short task line.
	 * @param string $meta    Optional context text.
	 * @param int    $user_id Optional user id.
	 * @param string $severity info|warning|error.
	 * @return int Task id or 0.
	 */
	public static function add_task( $agent, $kind, $event, $summary, $meta = '', $user_id = 0, $severity = 'info' ) {
		global $wpdb;
		self::maybe_create_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ok = $wpdb->insert(
			self::table_name(),
			array(
				'time'      => current_time( 'mysql' ),
				'agent'     => substr( (string) $agent, 0, 40 ),
				'kind'      => substr( (string) $kind, 0, 40 ),
				'event'     => substr( (string) $event, 0, 40 ),
				'severity'  => in_array( $severity, array( 'info', 'warning', 'error' ), true ) ? $severity : 'info',
				'summary'   => substr( (string) $summary, 0, 255 ),
				'meta_text' => substr( (string) $meta, 0, 2000 ),
				'user_id'   => (int) $user_id,
				'handled'   => 0,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
		);
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Sleep-or-fire idle tasks from the backlog (already-activated events).
	 */
	public static function run_idle() {
		if ( wp_doing_cron() ) {
			return;
		}
		self::maybe_create_table();
	}

	/**
	 * Lead captured → SDR qualify (soft, notify).
	 *
	 * The ecosystem fires `gabochie/lead/captured` with `($email, $data)`; an
	 * older positional form `($email, $list_slug, $source, $name, $phone)` is
	 * tolerated for compatibility.
	 *
	 * @param string $email Email.
	 * @param mixed  $data  Lead context array (list, source, name, phone).
	 */
	public static function on_lead_captured( $email, $data = array() ) {
		if ( ! is_array( $data ) ) {
			$args = func_get_args();
			$data = array(
				'list'   => isset( $args[1] ) ? $args[1] : '',
				'source' => isset( $args[2] ) ? $args[2] : '',
				'name'   => isset( $args[3] ) ? $args[3] : '',
				'phone'  => isset( $args[4] ) ? $args[4] : '',
			);
		}
		$list_slug = sanitize_text_field( $data['list'] ?? '' );
		$source    = sanitize_text_field( $data['source'] ?? '' );
		$name      = sanitize_text_field( $data['name'] ?? '' );
		$phone     = sanitize_text_field( $data['phone'] ?? '' );

		$user = get_user_by( 'email', $email );
		self::add_task(
			'sdr',
			'qualify',
			'lead_captured',
			'New ' . $list_slug . ' lead' . ( $source ? ' via ' . $source : '' ) . ' — qualify within the hour',
			'Email: ' . $email . ( $name ? ' | Name: ' . $name : '' ) . ( $phone ? ' | Phone: ' . $phone : '' ),
			$user ? (int) $user->ID : 0
		);
	}

	/**
	 * Membership level change → converter/retention/upsell.
	 *
	 * @param int    $level_id New level id.
	 * @param int    $user_id  User id.
	 * @param int    $cancel   Cancel flag.
	 * @param bool   $start    Whether this is a level grant (not cancellation).
	 * @param string $event    Event descriptor.
	 */
	private static function membership_event( $level_id, $user_id, $cancel, $start, $event ) {
		$paid = in_array( (int) $level_id, array( 2, 3 ), true );
		if ( $cancel ) {
			$user = get_userdata( $user_id );
			$name = $user ? $user->display_name : 'user ' . $user_id;
			self::add_task(
				'retention',
				'retain',
				$event,
				'Cancelled/expired level ' . $level_id . ' — win-back sequence for ' . $name,
				'Send win-back offer; check churn reason.',
				$user_id,
				'warning'
			);
			return;
		}
		if ( $start && $paid ) {
			self::add_task(
				'membership_converter',
				'convert',
				$event,
				'New paid member (level ' . $level_id . ') — kick off onboarding + welcome',
				'Onboard: deliver the level benefits, schedule check-in, set up upsell path.',
				$user_id
			);
		}
	}

	/**
	 * PMPro membership change (hook args vary by version).
	 *
	 * @param int   $level_id New level id.
	 * @param int   $user_id  User id.
	 * @param mixed $cancel   Cancel flag.
	 */
	public static function on_membership_change( $level_id, $user_id, $cancel ) {
		$cancel = func_num_args() >= 3 ? (int) $cancel : 0;
		$args   = func_get_args();
		$start  = true;
		if ( isset( $args[3] ) ) {
			$start = 0 === (int) $cancel && (int) $level_id > 0;
		}
		self::membership_event( $level_id, $user_id, $cancel, $start, 'pmpro_level_change' );
	}

	/**
	 * WooCommerce order completed → payments/retention.
	 *
	 * @param int $order_id Order id.
	 */
	public static function on_order_completed( $order_id ) {
		$order_id = (int) $order_id;
		$user_id  = 0;
		if ( function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$user_id = (int) $order->get_user_id();
				$total   = $order->get_total();
			}
		}
		self::add_task(
			'payments_agent',
			'payments',
			'order_completed',
			'Order #' . $order_id . ' completed' . ( isset( $total ) ? ' — ₵' . number_format( (float) $total, 2 ) : '' ),
			'Confirm payment received, reconcile, trigger fulfilment.',
			$user_id
		);
	}

	/**
	 * Daily RevenueOps digest (email = first implementation).
	 */
	public static function digest_hourly() {
		$kpi = Gabochie_CC_Funnel::metrics( true );

		$to    = (string) get_option( 'admin_email', '' );
		$lines = array(
			'Gabochie Command Center — RevenueOps digest',
			'',
			'MRR: ₵' . number_format( (float) $kpi['mrr'] ) . ' (target ₵' . number_format( Gabochie_CC_Funnel::targets()['mrr'] ) . '/mo)',
			'Active paid clients: ' . (int) $kpi['members_paid'] . ' (target ≈18 new/mo)',
			'Leads captured: ' . (int) $kpi['leads_total'] . ' total · ' . (int) $kpi['leads_30d'] . ' last 30 days',
			'Qualified: ' . (int) $kpi['qualified'] . ' · Engaged (30d): ' . (int) $kpi['engaged_30d'],
			'Revenue to date: ₵' . number_format( (float) $kpi['revenue_total'] ),
			'Open, commanding actions: ' . count( Gabochie_CC_Funnel::actions( $kpi ) ) . '.',
			'',
			'Open tasks: ' . self::open_task_count() . '. Dashboard: ' . admin_url( 'admin.php?page=gabochie-command-center' ),
		);

		$lines = apply_filters( 'gabochie_cc_digest_lines', $lines, $kpi );

		$subject = '[Gabochie Ops] Revenue digest — MRR ₵' . number_format( (float) $kpi['mrr'] );
		wp_mail( $to, $subject, implode( "\n", $lines ) );
	}

	/**
	 * Count open tasks.
	 *
	 * @return int
	 */
	public static function open_task_count() {
		global $wpdb;
		self::maybe_create_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE handled = 0', self::table_name() )
		);
	}

	/**
	 * Admin-post test trigger (fires the lead event → task row).
	 */
	public static function admin_post_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- manual admin trigger.
		if ( isset( $_GET['_gcc'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'gabochie_cc_test_event' ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			do_action(
				'gabochie/lead/captured',
				'ops-test@' . wp_parse_url( home_url(), PHP_URL_HOST ),
				array(
					'list'   => 'test',
					'source' => 'admin-post',
					'name'   => 'Ops Test',
				)
			);
		}
		wp_safe_redirect( admin_url( 'admin.php?page=gabochie-command-center&tab=tasks' ) );
		exit;
	}

	/**
	 * Test-event button URL.
	 *
	 * @return string
	 */
	public static function test_event_url() {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::ACTION . '&_gcc=1' ),
			'gabochie_cc_test_event'
		);
	}

	/**
	 * Tasks tab HTML.
	 */
	public static function render_tasks_tab() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$rows = self::tasks( 25 );
		if ( empty( $rows ) ) {
			echo '<div class="notice notice-info"><p>No tasks logged yet. Fire the test event to see the orchestrator in action, or capture a real lead.</p></div>';
		} else {
			$out = '<table class="widefat striped" style="max-width:980px;"><thead><tr>'
				. '<th>ID</th><th>Time</th><th>Agent</th><th>Kind</th><th>Event</th><th>Task</th><th>Severity</th><th>Handled</th></tr></thead><tbody>';
			foreach ( $rows as $r ) {
				$out .= '<tr><td>' . (int) $r['id'] . '</td>'
					. '<td>' . esc_html( $r['time'] ) . '</td>'
					. '<td><strong>' . esc_html( $r['agent'] ) . '</strong></td>'
					. '<td>' . esc_html( $r['kind'] ) . '</td>'
					. '<td>' . esc_html( $r['event'] ) . '</td>'
					. '<td>' . esc_html( $r['summary'] )
					. ( '' !== $r['meta_text'] ? '<div style="color:#8c8f94;font-size:11px;">' . esc_html( $r['meta_text'] ) . '</div>' : '' ) . '</td>'
					. '<td>' . esc_html( $r['severity'] ) . '</td>'
					. '<td>' . ( (int) $r['handled'] ? 'Yes' : 'Open' ) . '</td></tr>';
			}
			$out .= '</tbody></table>';
			echo $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '<p><a class="button" href="' . esc_url( self::test_event_url() ) . '">Fire test lead event</a> <span class="description" style="margin-left:8px;">Runs through gabochie/lead/captured → SDR task row.</span></p>';
	}

	/**
	 * Orchestrator tab HTML (event bus + schedule + task feed).
	 */
	public static function render_orchestrator_tab() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$next = wp_next_scheduled( self::CRON );
		echo '<h2>Event hooks</h2>';
		echo '<table class="widefat striped" style="max-width:760px;"><tbody>'
			. '<tr><th>lead captured</th><td><code>gabochie/lead/captured</code> → SDR qualify task</td></tr>'
			. '<tr><th>membership change</th><td><code>pmpro_after_change_membership_level</code> → convert / retain tasks</td></tr>'
			. '<tr><th>order completed</th><td><code>woocommerce_order_status_completed</code> / <code>woocommerce_payment_complete</code> → payments task</td></tr>'
			. '<tr><th>digest</th><td><code>' . esc_html( self::CRON ) . '</code> → RevenueOps email'
			. ( $next ? ' · next at ' . esc_html( gmdate( 'Y-m-d H:i e', $next ) ) : ' · <strong style="color:#d63638;">not scheduled</strong>' ) . '</td></tr>'
			. '</tbody></table>';
		echo '<p class="description">All events also call <code>maybe_create_table()</code> so the task log self-heals if the deploy ever wipes a table.</p>';
		echo '<h2>Task log</h2>';
		self::render_tasks_tab();
	}
}
