<?php
/**
 * Gabochie Cold Outreach — admin.
 *
 * Dashboard: mode toggle (dry-run/live), wave size, per-channel caps,
 * opt-out list, import trigger, and a send-history view.
 *
 * @package Gabochie_Cold_Outreach
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin UI.
 */
class Gabochie_Co_Admin {

	/** @var string */
	const PAGE = 'gabochie-cold-outreach';

	/**
	 * Wire admin hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_gabochie_co_controls', array( __CLASS__, 'save_controls' ) );
	}

	/**
	 * Register the dashboard page.
	 */
	public static function menu() {
		add_menu_page(
			'Cold Outreach',
			'Cold Outreach',
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render' ),
			'dashicons-megaphone',
			56
		);
	}

	/**
	 * Handle the dashboard form (nonce-guarded).
	 */
	public static function save_controls() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		check_admin_referer( 'gabochie_co_controls' );

		$mode = isset( $_POST['mode'] ) && 'live' === $_POST['mode'] ? 'live' : 'dry-run';
		$wave = isset( $_POST['wave'] ) ? max( 1, (int) $_POST['wave'] ) : 10;
		$chan = isset( $_POST['channel'] ) && in_array( $_POST['channel'], array( 'email', 'wa' ), true ) ? $_POST['channel'] : 'auto';

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_option( 'gabochie_co_mode', $mode, false );
		update_option( 'gabochie_co_wave', $wave, false );
		update_option( 'gabochie_co_channel', $chan, false );
		do_action( 'gabochie_co_wave_started', $wave, $mode );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['wa_optout'] ) && '' !== trim( wp_unslash( $_POST['wa_optout'] ) ) ) {
			$wa_id = sanitize_text_field( wp_unslash( $_POST['wa_optout'] ) );
			Gabochie_Co_Db::opt_out( $wa_id, 'wa' );
			do_action( 'gabochie_co_opted_out', $wa_id, 'wa' );
		}
		if ( isset( $_POST['email_optout'] ) && is_email( wp_unslash( $_POST['email_optout'] ) ) ) {
			$em_id = sanitize_email( wp_unslash( $_POST['email_optout'] ) );
			Gabochie_Co_Db::opt_out( $em_id, 'email' );
			do_action( 'gabochie_co_opted_out', $em_id, 'email' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&saved=1' ) );
		exit;
	}

	/**
	 * Render the dashboard.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$mode       = get_option( 'gabochie_co_mode', 'dry-run' );
		$wave       = (int) get_option( 'gabochie_co_wave', 10 );
		$channel    = (string) get_option( 'gabochie_co_channel', 'auto' );
		$wa_cap     = Gabochie_Co_Sender::cap( 'wa' );
		$email_cap  = Gabochie_Co_Sender::cap( 'email' );
		$wa_used    = Gabochie_Co_Sender::used_today( 'wa' );
		$email_used = Gabochie_Co_Sender::used_today( 'email' );
		$wa_next    = wp_next_scheduled( Gabochie_Co_Sender::CRON );

		$counts = array();
		foreach ( Gabochie_Co_Db::STATUS as $st => $label ) {
			$counts[ $st ] = Gabochie_Co_Db::count( array( "status = '" . esc_sql( $st ) . "'" ) );
		}
		$total_optouts = Gabochie_Co_Db::count( array( '' ) );
		?>
		<div class="wrap">
			<h1>Gabochie Cold Outreach</h1>
			<div class="notice notice-<?php echo 'live' === $mode ? 'warning' : 'info'; ?>">
				<p><strong>Mode: <?php echo 'live' === $mode ? 'LIVE (sends real messages)' : 'DRY-RUN (no messages sent)'; ?></strong></p>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'gabochie_co_controls' ); ?>
				<input type="hidden" name="action" value="gabochie_co_controls" />

				<h2>Controls</h2>
				<table class="form-table">
					<tr>
						<th>Mode</th>
						<td>
							<label><input type="radio" name="mode" value="dry-run" <?php checked( 'dry-run', $mode ); ?> /> Dry-run</label><br/>
							<label><input type="radio" name="mode" value="live" <?php checked( 'live', $mode ); ?> /> Live</label>
						</td>
					</tr>
					<tr>
						<th>Wave size (per pass)</th>
						<td><input type="number" name="wave" value="<?php echo esc_attr( (string) $wave ); ?>" min="1" max="100" /></td>
					</tr>
					<tr>
						<th>Channel</th>
						<td>
							<label><input type="radio" name="channel" value="auto" <?php checked( 'auto', $channel ); ?> /> Auto (WA then email)</label><br/>
							<label><input type="radio" name="channel" value="email" <?php checked( 'email', $channel ); ?> /> Email only</label><br/>
							<label><input type="radio" name="channel" value="wa" <?php checked( 'wa', $channel ); ?> /> WhatsApp only</label>
						</td>
					</tr>
					<tr>
						<th>Daily caps</th>
						<td>WA: <?php echo (int) $wa_used; ?>/<?php echo (int) $wa_cap; ?> &middot; Email: <?php echo (int) $email_used; ?>/<?php echo (int) $email_cap; ?>
							<?php if ( $wa_next ) : ?>
								&middot; next cron: <?php echo esc_html( gmdate( 'Y-m-d H:i', $wa_next ) ); ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>Add opt-out (WA number)</th>
						<td><input type="text" name="wa_optout" placeholder="233..." /></td>
					</tr>
					<tr>
						<th>Add opt-out (email)</th>
						<td><input type="text" name="email_optout" placeholder="name@biz.com" /></td>
					</tr>
				</table>
				<?php submit_button( 'Save controls' ); ?>
			</form>

			<h2>Pipeline</h2>
			<table class="widefat striped" style="max-width:640px;">
				<thead><tr><th>Status</th><th>Count</th></tr></thead>
				<tbody>
				<?php foreach ( $counts as $st => $n ) : ?>
					<tr><td><?php echo esc_html( Gabochie_Co_Db::STATUS[ $st ] ); ?></td><td><?php echo (int) $n; ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description">Import contacts from the WP-CLI command:
				<code>wp gabochie-co import /path/to/gadata.csv --limit=50</code>
				Use <code>--dry-run</code> to preview without writing.
			</p>
		</div>
		<?php
	}
}
