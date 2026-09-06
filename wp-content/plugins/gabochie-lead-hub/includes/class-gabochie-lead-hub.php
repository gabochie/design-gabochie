<?php
/**
 * Gabochie Lead Hub — core.
 *
 * Listens for `gabochie/lead/captured` (email, data) fired by every capture
 * point on gabochiedesign.com and routes each lead into the Gabochie CRM
 * tuple, tagged by source. When the CRM table is unavailable, leads are stored
 * in a backlog and replayed from the WordPress admin.
 *
 * @package Gabochie_Lead_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Single lead pipeline behind the distribution engine.
 */
class Gabochie_Lead_Hub {

	/** @var Gabochie_Lead_Hub|null */
	private static $instance = null;

	/** @var int */
	const BACKLOG_LIMIT = 500;

	/** @var int */
	const BACKLOG_FLUSH_PER_RUN = 50;

	/** @var string */
	const OPTION_BACKLOG = 'gabochie_lead_hub_backlog';

	/** @var string */
	const OPTION_STATS = 'gabochie_lead_hub_stats';

	/** @var string */
	const OPTION_SEEDED = 'gabochie_lead_hub_lists_seeded';

	/** @var string */
	const PAGE_SLUG = 'gabochie-lead-hub';

	/**
	 * Singleton accessor.
	 *
	 * @return Gabochie_Lead_Hub
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire the hooks.
	 */
	private function __construct() {
		add_action( 'gabochie/lead/captured', array( $this, 'route' ), 10, 2 );
		add_action( 'init', array( $this, 'maybe_seed_lists' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
		add_action( 'admin_init', array( $this, 'flush_backlog' ) );
	}

	/**
	 * Action wrapper so the `gabochie/lead/captured` callback returns void.
	 *
	 * @param string $email Captured email address.
	 * @param array  $data  Lead context.
	 */
	public function route( $email, $data ) {
		$this->capture( $email, is_array( $data ) ? $data : array() );
	}

	/**
	 * Activation hook — ensure the CRM table exists.
	 */
	public function activate() {
		$this->maybe_seed_lists();
	}

	/**
	 * Lead lists keyed by machine name.
	 *
	 * @return string[] key => list label.
	 */
	public static function lists() {
		return array(
			'thesis'        => 'Thesis Download',
			'readiness'     => 'Readiness Checklist',
			'onboarding'    => 'Onboarding (Start Here)',
			'homepage_lead' => 'Homepage Lead',
			'community'     => 'Community Ask',
			'register'      => 'New Registration',
			'voice'         => 'Voice Agent Call',
		);
	}

	/**
	 * Whether the Gabochie CRM store is present.
	 *
	 * @return bool
	 */
	protected function crm_ready() {
		return class_exists( 'Gabochie_Crm' ) && \Gabochie_Crm::ready();
	}

	/**
	 * Ensure the CRM table exists once per plugin version.
	 *
	 * @param bool $force Re-run even if already seeded.
	 */
	public function maybe_seed_lists( $force = false ) {
		if ( ! $this->crm_ready() ) {
			return;
		}
		if ( ! $force && GABOCHIE_LEAD_HUB_VERSION === (string) get_option( self::OPTION_SEEDED, '' ) ) {
			return;
		}

		Gabochie_Crm::ensure_schema();
		update_option( self::OPTION_SEEDED, GABOCHIE_LEAD_HUB_VERSION, false );
	}

	/**
	 * Primary entry point — fires on `gabochie/lead/captured`.
	 *
	 * @param string $email Captured email address.
	 * @param array  $data  Lead context (list, source, name, phone, journey, blocker, utm_*).
	 * @return bool True when synced to the Gabochie CRM (or queued to backlog), false on invalid input.
	 */
	public function capture( $email, $data ) {
		$email = is_string( $email ) ? sanitize_email( $email ) : '';
		if ( ! is_email( $email ) ) {
			return false;
		}
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$source = sanitize_text_field( $data['source'] ?? 'general' );
		$synced = $this->sync_contact( $email, $data );

		$this->bump_stats( $source, $synced );

		if ( ! $synced ) {
			$this->backlog_add(
				array(
					'email' => $email,
					'data'  => $data,
					'time'  => time(),
				)
			);
			return false;
		}

		do_action( 'gabochie/lead/synced', $email, $data );
		return true;
	}

	/**
	 * Create or update the Gabochie CRM contact (+ list + source/utm tags).
	 *
	 * @param string $email Sanitized email.
	 * @param array  $data  Lead context.
	 * @return bool
	 */
	protected function sync_contact( $email, array $data ) {
		if ( ! $this->crm_ready() ) {
			return false;
		}

		$list_key = sanitize_key( $data['list'] ?? '' );

		$payload = array(
			'status'       => 'subscribed',
			'contact_type' => 'subscriber',
		);

		$name = sanitize_text_field( $data['name'] ?? '' );
		if ( '' !== $name ) {
			$payload['first_name'] = $name;
		}

		$phone = sanitize_text_field( $data['phone'] ?? '' );
		if ( '' !== $phone ) {
			$payload['phone'] = $phone;
		}

		$lists = $this->lists();
		if ( isset( $lists[ $list_key ] ) ) {
			$payload['list_key'] = 'leadhub-' . $list_key;
		}

		$tags   = array();
		$source = sanitize_key( $data['source'] ?? 'general' );
		if ( '' !== $source ) {
			$payload['source'] = $source;
			$tags[]            = 'source:' . $source;
		}
		foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign' ) as $utm_key ) {
			$utm_val = sanitize_key( $data[ $utm_key ] ?? '' );
			if ( '' !== $utm_val ) {
				$tags[]                 = 'utm:' . $utm_val;
				$payload[ $utm_key ]    = $utm_val;
			}
		}
		$payload['tags'] = $tags;

		$id = Gabochie_Crm::upsert_contact( $email, $payload );
		if ( $id > 0 ) {
			return true;
		}
		$this->log_error( 'sync_contact: upsert failed for ' . $email );
		return false;
	}

	/**
	 * Persist to the backlog (CRM table unavailable or contact call failed).
	 *
	 * @param array $entry Backlog entry.
	 */
	protected function backlog_add( array $entry ) {
		$items   = $this->backlog_items();
		$items[] = $entry;
		if ( count( $items ) > self::BACKLOG_LIMIT ) {
			$items = array_slice( $items, -self::BACKLOG_LIMIT );
		}
		update_option( self::OPTION_BACKLOG, $items, false );
	}

	/**
	 * Current backlog entries.
	 *
	 * @return array
	 */
	public function backlog_items() {
		$items = get_option( self::OPTION_BACKLOG, array() );
		return is_array( $items ) ? $items : array();
	}

	/**
	 * Replay the backlog from the admin (capped per run).
	 */
	public function flush_backlog() {
		if ( ! $this->crm_ready() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$items = $this->backlog_items();
		if ( empty( $items ) ) {
			return;
		}

		$kept = array();
		$flushed = 0;
		foreach ( $items as $entry ) {
			if ( $flushed >= self::BACKLOG_FLUSH_PER_RUN ) {
				$kept[] = $entry;
				continue;
			}
			$email = is_array( $entry ) && isset( $entry['email'] ) ? sanitize_email( $entry['email'] ) : '';
			$data  = is_array( $entry ) && isset( $entry['data'] ) && is_array( $entry['data'] ) ? $entry['data'] : array();
			if ( is_email( $email ) && $this->sync_contact( $email, $data ) ) {
				$flushed++;
				$this->bump_stats( sanitize_text_field( $data['source'] ?? 'general' ), true );
			} else {
				$kept[] = $entry;
			}
		}

		update_option( self::OPTION_BACKLOG, $kept, false );
	}

	/**
	 * Track captured/synced volume per source.
	 *
	 * @param string $source Lead source key.
	 * @param bool   $synced Whether the contact reached the Gabochie CRM.
	 */
	protected function bump_stats( $source, $synced ) {
		$stats = get_option( self::OPTION_STATS, array() );
		$stats = is_array( $stats ) ? $stats : array();

		$stats['captured'][ $source ] = (int) ( $stats['captured'][ $source ] ?? 0 ) + 1;
		if ( $synced ) {
			$stats['synced'][ $source ] = (int) ( $stats['synced'][ $source ] ?? 0 ) + 1;
		}
		update_option( self::OPTION_STATS, $stats, false );
	}

	/**
	 * Totals for the dashboard.
	 *
	 * @return array
	 */
	public function get_stats() {
		$stats = get_option( self::OPTION_STATS, array() );
		return is_array( $stats ) ? $stats : array();
	}

	/**
	 * Store plugin-level errors for diagnostics.
	 *
	 * @param string $message Error text.
	 */
	protected function log_error( $message ) {
		$log  = get_option( 'gabochie_lead_hub_errors', array() );
		$log  = is_array( $log ) ? $log : array();
		$log[] = array(
			'text' => sanitize_text_field( $message ),
			'time' => gmdate( 'c' ),
		);
		if ( count( $log ) > 40 ) {
			$log = array_slice( $log, -40 );
		}
		update_option( 'gabochie_lead_hub_errors', $log, false );
	}

	/**
	 * Register the Settings → Lead Hub page.
	 */
	public function add_admin_menu() {
		add_options_page(
			'Gabochie Lead Hub',
			'Lead Hub',
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Handle admin POSTs (flush backlog / reseed lists).
	 */
	public function handle_admin_actions() {
		if ( ! isset( $_POST['gabochie_lead_hub_action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'gabochie_lead_hub_admin' );

		$action = sanitize_key( wp_unslash( $_POST['gabochie_lead_hub_action'] ) );
		if ( 'flush' === $action ) {
			$this->flush_backlog();
		} elseif ( 'reseed' === $action ) {
			delete_option( self::OPTION_SEEDED );
			$this->maybe_seed_lists( true );
		}
	}

	/**
	 * Render the admin dashboard.
	 */
	public function render_admin_page() {
		$ready     = $this->crm_ready();
		$backlog   = $this->backlog_items();
		$stats     = $this->get_stats();
		$seeded    = (string) get_option( self::OPTION_SEEDED, '' );
		$errors    = get_option( 'gabochie_lead_hub_errors', array() );
		$captured  = $stats['captured'] ?? array();
		$synced    = $stats['synced'] ?? array();
		$all_srcs  = array_unique( array_merge( array_keys( $captured ), array_keys( $synced ), array_keys( $this->lists() ) ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Gabochie Lead Hub', 'gabochie-lead-hub' ); ?></h1>

			<div style="display:flex;gap:12px;flex-wrap:wrap;margin:12px 0 20px;">
				<div style="background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:14px 18px;min-width:170px;">
					<div style="font-size:22px;font-weight:600;"><?php echo esc_html( $ready ? 'Online' : 'Standby' ); ?></div>
					<div style="color:#646970;">Gabochie CRM</div>
				</div>
				<div style="background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:14px 18px;min-width:170px;">
					<div style="font-size:22px;font-weight:600;"><?php echo esc_html( (string) count( $backlog ) ); ?></div>
					<div style="color:#646970;">Backlog</div>
				</div>
				<div style="background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:14px 18px;min-width:170px;">
					<div style="font-size:22px;font-weight:600;"><?php echo esc_html( (string) array_sum( $captured ) ); ?></div>
					<div style="color:#646970;">Captured</div>
				</div>
				<div style="background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:14px 18px;min-width:170px;">
					<div style="font-size:22px;font-weight:600;"><?php echo esc_html( (string) array_sum( $synced ) ); ?></div>
					<div style="color:#646970;">Synced</div>
				</div>
			</div>

			<form method="post" style="margin-bottom:20px;">
				<?php wp_nonce_field( 'gabochie_lead_hub_admin' ); ?>
				<input type="hidden" name="gabochie_lead_hub_action" value="flush" />
				<input type="submit" class="button button-secondary" value="<?php esc_attr_e( 'Flush backlog now', 'gabochie-lead-hub' ); ?>" />
			</form>
			<form method="post" style="margin-bottom:24px;">
				<?php wp_nonce_field( 'gabochie_lead_hub_admin' ); ?>
				<input type="hidden" name="gabochie_lead_hub_action" value="reseed" />
				<input type="submit" class="button button-link" value="<?php esc_attr_e( 'Re-verify CRM table', 'gabochie-lead-hub' ); ?>" />
			</form>

			<h2><?php esc_html_e( 'Lead lists', 'gabochie-lead-hub' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Key', 'gabochie-lead-hub' ); ?></th><th><?php esc_html_e( 'List', 'gabochie-lead-hub' ); ?></th><th><?php esc_html_e( 'Status', 'gabochie-lead-hub' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $this->lists() as $key => $label ) : ?>
					<tr>
						<td><code><?php echo esc_html( $key ); ?></code></td>
						<td><?php echo esc_html( $label ); ?></td>
						<td><?php echo $ready && '' !== $seeded ? esc_html( $seeded ) : esc_html__( 'pending (CRM table missing)', 'gabochie-lead-hub' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Captured by source', 'gabochie-lead-hub' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Source', 'gabochie-lead-hub' ); ?></th><th><?php esc_html_e( 'Captured', 'gabochie-lead-hub' ); ?></th><th><?php esc_html_e( 'Synced', 'gabochie-lead-hub' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $all_srcs as $src ) : ?>
					<tr>
						<td><?php echo esc_html( $src ); ?></td>
						<td><?php echo esc_html( (string) (int) ( $captured[ $src ] ?? 0 ) ); ?></td>
						<td><?php echo esc_html( (string) (int) ( $synced[ $src ] ?? 0 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( ! empty( $errors ) ) : ?>
				<h2><?php esc_html_e( 'Diagnostics', 'gabochie-lead-hub' ); ?></h2>
				<?php
				$diag = '';
				foreach ( (array) $errors as $err ) {
					$diag .= $err['text'] . ' @ ' . $err['time'] . "\n";
				}
				?>
				<pre style="max-height:220px;overflow:auto;background:#fff;padding:10px;border:1px solid #c3c4c7;"><?php echo esc_html( (string) $diag ); ?></pre>
			<?php endif; ?>
		</div>
		<?php
	}
}