<?php
/**
 * Gabochie Market Research — admin UI.
 *
 * Top-level "Market Research" menu with four tabs: Inbox (the human-in-the-loop
 * proposals — approve/reject/defer/delete), Signals (last collected matrix +
 * recent site searches + RSS feed manager), Run (collect now / generate memo /
 * send digest) and Settings (enable, AI model, digest email). Every mutating
 * action carries a nonce and capability check.
 *
 * @package Gabochie_Market_Research
 */

defined( 'ABSPATH' ) || exit;

/**
 * Market research admin.
 */
class Gabochie_Market_Research_Admin {


	const PAGE = 'gabochie-market-research';

	/**
	 * @var Gabochie_Market_Research_Admin|null
	 */
	private static $instance = null;

	/**
	 * @return Gabochie_Market_Research_Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_get_action' ) );
		add_action( 'admin_post_gmr_collect', array( $this, 'handle_collect' ) );
		add_action( 'admin_post_gmr_memo', array( $this, 'handle_memo' ) );
		add_action( 'admin_post_gmr_digest', array( $this, 'handle_digest' ) );
		add_action( 'admin_post_gmr_settings', array( $this, 'handle_settings' ) );
	}

	/**
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Market Research', 'gabochie-market-research' ),
			__( 'Market Research', 'gabochie-market-research' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render' ),
			'dashicons-chart-area',
			5
		);
		// Schedule an idempotent first collect shortly after activation so the
		// Signals tab never renders empty tables in a confusing way.
		if ( ! wp_next_scheduled( Gabochie_Market_Research::HOOK_COLLECT ) ) {
			Gabochie_Market_Research::instance()->ensure_crons();
		}
	}

	/**
	 * @return void
	 */
	public function render() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( (string) $_GET['tab'] ) : 'inbox';
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Gabochie Market Research', 'gabochie-market-research' ) . '</h1>';
		$this->render_tabs( $tab );
		if ( 'signals' === $tab ) {
			$this->render_signals();
		} elseif ( 'run' === $tab ) {
			$this->render_run();
		} elseif ( 'settings' === $tab ) {
			$this->render_settings();
		} else {
			$this->render_inbox();
		}
		echo '</div>';
	}

	/**
	 * @param string $current @return void
	 */
	private function render_tabs( $current ) {
		$base = admin_url( 'admin.php?page=' . self::PAGE );
		$tabs = array(
			'inbox'    => __( 'Inbox', 'gabochie-market-research' ),
			'signals'  => __( 'Signals', 'gabochie-market-research' ),
			'run'      => __( 'Run', 'gabochie-market-research' ),
			'settings' => __( 'Settings', 'gabochie-market-research' ),
		);
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $slug => $label ) {
			printf(
				'<a class="nav-tab %s" href="%s">%s</a>',
				$slug === $current ? 'nav-tab-active' : '',
				esc_url( $base . '&tab=' . $slug ),
				esc_html( $label )
			);
		}
		echo '</h2>';
	}

	/**
	 * Inbox tab: pending/decided proposals with per-row action links.
	 *
	 * @return void
	 */
	private function render_inbox() {
		global $wpdb;
		$filter  = isset( $_GET['status'] ) ? sanitize_key( (string) $_GET['status'] ) : 'pending';
		$allowed = array( 'pending', 'approved', 'rejected', 'deferred', 'all' );
		if ( ! in_array( $filter, $allowed, true ) ) {
			$filter = 'pending';
		}
		$table = Gabochie_Market_Research::table( Gabochie_Market_Research::TABLE_PROPOSALS );
		$where = 'all' === $filter ? '' : $wpdb->prepare( 'WHERE status = %s', $filter );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( "SELECT * FROM {$table} {$where} ORDER BY opportunity_score DESC, created_at DESC LIMIT 200", ARRAY_A );

		$filters = array(
			'pending'  => __( 'Pending', 'gabochie-market-research' ),
			'approved' => __( 'Approved', 'gabochie-market-research' ),
			'rejected' => __( 'Rejected', 'gabochie-market-research' ),
			'deferred' => __( 'Deferred', 'gabochie-market-research' ),
			'all'      => __( 'All', 'gabochie-market-research' ),
		);
		$base    = admin_url( 'admin.php?page=' . self::PAGE . '&tab=inbox' );
		echo '<ul class="subsubsub">';
		foreach ( $filters as $slug => $label ) {
			printf(
				'<li><a href="%s"%s>%s</a></li> ',
				esc_url( $base . '&status=' . $slug ),
				$slug === $filter ? ' class="current"' : '',
				esc_html( $label )
			);
		}
		echo '</ul><div class="clear"></div>';

		echo '<table class="widefat striped">';
		echo '<thead><tr><th>' . esc_html__( 'Score', 'gabochie-market-research' ) . '</th>'
			. '<th>' . esc_html__( 'Proposal', 'gabochie-market-research' ) . '</th>'
			. '<th>' . esc_html__( 'Product', 'gabochie-market-research' ) . '</th>'
			. '<th>' . esc_html__( 'Price', 'gabochie-market-research' ) . '</th>'
			. '<th>' . esc_html__( 'Created', 'gabochie-market-research' ) . '</th>'
			. '<th>' . esc_html__( 'Actions', 'gabochie-market-research' ) . '</th></tr></thead><tbody>';

		if ( empty( $rows ) ) {
			echo '<tr><td colspan="6">' . esc_html__( 'No proposals yet. Run the collection + memo from the Run tab (or wait for the weekly memo cron).', 'gabochie-market-research' ) . '</td></tr>';
		}
		foreach ( $rows as $row ) {
			$links = array();
			foreach ( array( 'approved', 'rejected', 'deferred', 'pending' ) as $target ) {
				$links[] = sprintf(
					'<a href="%s">%s</a>',
					esc_url(
						add_query_arg(
							array(
								'page'     => self::PAGE,
								'tab'      => 'inbox',
								'action'   => 'gmr_status',
								'id'       => (int) $row['id'],
								'status'   => $target,
								'_wpnonce' => wp_create_nonce( 'gmr_status_' . (int) $row['id'] ),
							),
							admin_url( 'admin.php' )
						)
					),
					esc_html( ucfirst( $target ) )
				);
			}
			$links[] = sprintf(
				'<a href="%s" style="color:#b32d2e">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'page'     => self::PAGE,
							'tab'      => 'inbox',
							'action'   => 'gmr_delete',
							'id'       => (int) $row['id'],
							'_wpnonce' => wp_create_nonce( 'gmr_delete_' . (int) $row['id'] ),
						),
						admin_url( 'admin.php' )
					)
				),
				esc_html__( 'Delete', 'gabochie-market-research' )
			);

			echo '<tr>';
			echo '<td><strong>' . (int) $row['opportunity_score'] . '</strong></td>';
			echo '<td><strong>' . esc_html( $row['title'] ) . '</strong><br />'
				. '<span class="description">' . esc_html( $row['niche'] ) . ' — ' . esc_html( $row['summary'] ) . '</span></td>';
			echo '<td>' . esc_html( $row['product_type'] ) . '</td>';
			echo '<td>' . esc_html( $row['price_hint'] ) . '</td>';
			echo '<td>' . esc_html( $row['created_at'] ) . '</td>';
			echo '<td>' . wp_kses_post( implode( ' | ', $links ) ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Signals tab: last collected matrix + recent searches + RSS feed manager.
	 *
	 * @return void
	 */
	private function render_signals() {
		global $wpdb;
		$last = get_option( Gabochie_Market_Research::OPT_LAST_SIGNALS, array() );

		echo '<h2>' . esc_html__( 'Last collected opportunity matrix', 'gabochie-market-research' ) . '</h2>';
		if ( empty( $last['niches'] ) ) {
			echo '<p>' . esc_html__( 'Nothing collected yet — run collection from the Run tab.', 'gabochie-market-research' ) . '</p>';
		} else {
			echo '<p class="description">' . esc_html__( 'Generated: ', 'gabochie-market-research' ) . esc_html( $last['generated_at'] )
				. ' · ' . esc_html__( 'window (days): ', 'gabochie-market-research' ) . (int) $last['window_days'] . '</p>';
			echo '<table class="widefat striped"><thead><tr>'
				. '<th>' . esc_html__( 'Niche', 'gabochie-market-research' ) . '</th>'
				. '<th>' . esc_html__( 'Score', 'gabochie-market-research' ) . '</th>'
				. '<th>' . esc_html__( 'Questions', 'gabochie-market-research' ) . '</th>'
				. '<th>' . esc_html__( 'Searches', 'gabochie-market-research' ) . '</th>'
				. '<th>' . esc_html__( 'Enquiries', 'gabochie-market-research' ) . '</th>'
				. '<th>' . esc_html__( 'Engaged', 'gabochie-market-research' ) . '</th>'
				. '<th>' . esc_html__( 'RSS', 'gabochie-market-research' ) . '</th>'
				. '<th>' . esc_html__( 'Coverage', 'gabochie-market-research' ) . '</th>'
				. '</tr></thead><tbody>';
			foreach ( $last['niches'] as $slug => $row ) {
				printf(
					'<tr><td>%s</td><td><strong>%d</strong></td><td>%d</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td></tr>',
					esc_html( (string) $row['label'] . ' (' . $slug . ')' ),
					(int) $row['score'],
					(int) $row['questions'],
					(int) $row['searches'],
					(int) $row['enquiries'],
					(int) $row['engaged'],
					(int) $row['rss'],
					(int) $row['coverage']
				);
			}
			echo '</tbody></table>';
		}

		$table = Gabochie_Market_Research::table( Gabochie_Market_Research::TABLE_SEARCH );
		echo '<h2>' . esc_html__( 'Recent site searches', 'gabochie-market-research' ) . '</h2>';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( "SELECT term, hits, created_at FROM {$table} ORDER BY created_at DESC LIMIT 25" );
		if ( empty( $rows ) ) {
			echo '<p>' . esc_html__( 'No internal searches logged yet (they accumulate as visitors use the site search).', 'gabochie-market-research' ) . '</p>';
		} else {
			echo '<table class="widefat striped"><thead><tr>'
				. '<th>' . esc_html__( 'Term', 'gabochie-market-research' ) . '</th>'
				. '<th>' . esc_html__( 'Hits', 'gabochie-market-research' ) . '</th>'
				. '<th>' . esc_html__( 'First seen', 'gabochie-market-research' ) . '</th></tr></thead><tbody>';
			foreach ( $rows as $row ) {
				printf( '<tr><td>%s</td><td>%d</td><td>%s</td></tr>', esc_html( $row->term ), (int) $row->hits, esc_html( $row->created_at ) );
			}
			echo '</tbody></table>';
		}
	}

	/**
	 * Run tab: collect / memo / digest buttons + last memo.
	 *
	 * @return void
	 */
	private function render_run() {
		echo '<h2>' . esc_html__( 'Generator', 'gabochie-market-research' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'gmr_run' );
		echo '<input type="hidden" name="action" value="gmr_collect" />';
		submit_button( __( 'Collect signals now', 'gabochie-market-research' ), 'primary', 'submit', false );
		echo '</form>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:8px">';
		wp_nonce_field( 'gmr_run' );
		echo '<input type="hidden" name="action" value="gmr_memo" />';
		submit_button( __( 'Generate weekly memo (LLM)', 'gabochie-market-research' ), 'secondary', 'submit', false );
		echo '</form>';

		echo '<h2>' . esc_html__( 'Last memo', 'gabochie-market-research' ) . '</h2>';
		$memo = get_option( Gabochie_Market_Research::OPT_LAST_MEMO, array() );
		$at   = get_option( Gabochie_Market_Research::OPT_MEMO_AT, '' );
		if ( empty( $memo['summary'] ) ) {
			echo '<p>' . esc_html__( 'No memo generated yet.', 'gabochie-market-research' ) . '</p>';
		} else {
			echo '<blockquote class="gmr-memo">' . esc_html( $at ) . '<br />' . esc_html( $memo['summary'] ) . '</blockquote>';

			$opps = get_option( Gabochie_Market_Research::OPT_LAST_MEMO, array() );
			$opps = isset( $opps['opportunities'] ) ? $opps['opportunities'] : array();
			if ( ! empty( $opps ) ) {
				echo '<h3>' . esc_html__( 'Opportunities', 'gabochie-market-research' ) . '</h3><ul>';
				foreach ( $opps as $op ) {
					printf(
						'<li><strong>%s</strong> (%s, %s, score %d) — %s</li>',
						esc_html( isset( $op['title'] ) ? $op['title'] : '' ),
						esc_html( isset( $op['product_type'] ) ? $op['product_type'] : '' ),
						esc_html( isset( $op['pricing_hint'] ) ? $op['pricing_hint'] : '' ),
						( isset( $op['opportunity_score'] ) ? (int) $op['opportunity_score'] : 0 ),
						esc_html( isset( $op['rationale'] ) ? $op['rationale'] : '' )
					);
				}
				echo '</ul>';
			}
		}

		$to = (string) get_option( Gabochie_Market_Research::OPT_DIGEST_EMAIL, get_option( 'admin_email' ) );
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:12px">';
		wp_nonce_field( 'gmr_run' );
		echo '<input type="hidden" name="action" value="gmr_digest" />';
		echo '<p class="description">' . esc_html__( 'Digest email: ', 'gabochie-market-research' ) . esc_html( $to ) . '</p>';
		submit_button( __( 'Send digest email now', 'gabochie-market-research' ), 'secondary', 'submit', false );
		echo '</form>';
	}

	/**
	 * Settings tab.
	 *
	 * @return void
	 */
	private function render_settings() {
		$enabled = (string) get_option( Gabochie_Market_Research::OPT_ENABLED, '1' );
		$model   = (string) get_option( Gabochie_Market_Research::OPT_AI_MODEL, 'gemini-3.5-flash' );
		$email   = (string) get_option( Gabochie_Market_Research::OPT_DIGEST_EMAIL, get_option( 'admin_email' ) );
		$feeds   = (array) get_option( Gabochie_Market_Research::OPT_FEEDS, array() );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'gmr_settings_form' ); ?>
			<input type="hidden" name="action" value="gmr_settings" />
			<table class="form-table" role="presentation">
				<tr>
					<th><label for="gmr_enabled"><?php esc_html_e( 'Enable engine', 'gabochie-market-research' ); ?></label></th>
					<td>
						<select name="gmr_enabled" id="gmr_enabled">
							<option value="1" <?php selected( $enabled, '1' ); ?>><?php esc_html_e( 'Enabled', 'gabochie-market-research' ); ?></option>
							<option value="0" <?php selected( $enabled, '0' ); ?>><?php esc_html_e( 'Disabled', 'gabochie-market-research' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Disables the weekly memo cron (collection may continue).', 'gabochie-market-research' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="gmr_ai_model"><?php esc_html_e( 'Gemini model (builtin)', 'gabochie-market-research' ); ?></label></th>
					<td>
						<select name="gmr_ai_model" id="gmr_ai_model">
							<option value="gemini-3.5-flash" <?php selected( $model, 'gemini-3.5-flash' ); ?>>gemini-3.5-flash</option>
							<option value="gemini-flash-latest" <?php selected( $model, 'gemini-flash-latest' ); ?>>gemini-flash-latest</option>
							<option value="gemini-2.5-pro" <?php selected( $model, 'gemini-2.5-pro' ); ?>>gemini-2.5-pro</option>
						</select>
						<p class="description"><?php esc_html_e( 'Builtin fallback caller. Auto-blog + GhBC providers are tried first.', 'gabochie-market-research' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="gmr_digest_email"><?php esc_html_e( 'Weekly digest email', 'gabochie-market-research' ); ?></label></th>
					<td><input type="email" name="gmr_digest_email" id="gmr_digest_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="gmr_feeds"><?php esc_html_e( 'RSS feed sources (one per line)', 'gabochie-market-research' ); ?></label></th>
					<td><textarea name="gmr_feeds" id="gmr_feeds" rows="4" class="large-text"><?php echo esc_textarea( implode( "\n", $feeds ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'External pulse: headlines matched against niche keywords for the RSS signal.', 'gabochie-market-research' ); ?></p>
					</td>
				</tr>
			</table>
		<?php submit_button( __( 'Save Settings', 'gabochie-market-research' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'Recent errors', 'gabochie-market-research' ); ?></h2>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Time', 'gabochie-market-research' ); ?></th><th><?php esc_html_e( 'Error', 'gabochie-market-research' ); ?></th></tr></thead>
			<tbody>
		<?php
		$log = get_option( Gabochie_Market_Research::OPT_ERRORS, array() );
		if ( empty( $log ) ) {
			echo '<tr><td colspan="2">' . esc_html__( 'No errors recorded.', 'gabochie-market-research' ) . '</td></tr>';
		}
		foreach ( array_reverse( $log ) as $row ) {
			printf( '<tr><td>%s</td><td>%s</td></tr>', esc_html( $row['time'] ), esc_html( $row['error'] ) );
		}
		?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * GET row actions (status/delete) guarded by per-row nonces.
	 *
	 * @return void
	 */
	public function handle_get_action() {
		if ( empty( $_GET['page'] ) || self::PAGE !== $_GET['page'] ) {
			return;
		}
		$action = isset( $_GET['action'] ) ? sanitize_key( (string) $_GET['action'] ) : '';
		$id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		if ( $id <= 0 ) {
			return;
		}

		if ( 'gmr_status' === $action ) {
			check_admin_referer( 'gmr_status_' . $id );
			if ( current_user_can( 'manage_options' ) ) {
				$status = isset( $_GET['status'] ) ? sanitize_key( (string) $_GET['status'] ) : 'pending';
				Gabochie_Market_Research::instance()->set_proposal_status( $id, $status );
			}
		} elseif ( 'gmr_delete' === $action ) {
			check_admin_referer( 'gmr_delete_' . $id );
			if ( current_user_can( 'manage_options' ) ) {
				Gabochie_Market_Research::instance()->delete_proposal( $id );
			}
		} else {
			return;
		}
		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => self::PAGE,
					'tab'  => 'inbox',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * @return void
	 */
	public function handle_collect() {
		check_admin_referer( 'gmr_run' );
		if ( current_user_can( 'manage_options' ) ) {
			Gabochie_Market_Research::instance()->run_collect();
		}
		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => self::PAGE,
					'tab'  => 'signals',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * @return void
	 */
	public function handle_memo() {
		check_admin_referer( 'gmr_run' );
		if ( current_user_can( 'manage_options' ) ) {
			Gabochie_Market_Research::instance()->run_memo();
		}
		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => self::PAGE,
					'tab'  => 'run',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * @return void
	 */
	public function handle_digest() {
		check_admin_referer( 'gmr_run' );
		$to   = (string) get_option( Gabochie_Market_Research::OPT_DIGEST_EMAIL, get_option( 'admin_email' ) );
		$memo = get_option( Gabochie_Market_Research::OPT_LAST_MEMO, array() );
		$body = isset( $memo['summary'] ) ? (string) $memo['summary'] : 'No memo generated yet.';
		wp_mail(
			$to,
			'[Gabochie Market Research] Digest',
			'<h2>Market memo</h2><p>' . esc_html( $body ) . '</p>',
			array( 'Content-Type: text/html; charset=UTF-8' )
		);
		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => self::PAGE,
					'tab'  => 'run',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * @return void
	 */
	public function handle_settings() {
		check_admin_referer( 'gmr_settings_form' );
		if ( current_user_can( 'manage_options' ) ) {
			$enabled = isset( $_POST['gmr_enabled'] ) ? sanitize_key( (string) $_POST['gmr_enabled'] ) : '1';
			$model   = isset( $_POST['gmr_ai_model'] ) ? sanitize_key( (string) $_POST['gmr_ai_model'] ) : 'gemini-3.5-flash';
			$email   = isset( $_POST['gmr_digest_email'] ) ? sanitize_email( wp_unslash( (string) $_POST['gmr_digest_email'] ) ) : get_option( 'admin_email' );
			$feeds   = array();
			if ( isset( $_POST['gmr_feeds'] ) ) {
				$lines = preg_split( '/\r\n|\r|\n/', sanitize_textarea_field( wp_unslash( $_POST['gmr_feeds'] ) ) );
				foreach ( (array) $lines as $line ) {
					$line = trim( esc_url_raw( (string) $line ) );
					if ( '' !== $line ) {
						$feeds[] = $line;
					}
				}
			}
			update_option( Gabochie_Market_Research::OPT_ENABLED, $enabled );
			update_option( Gabochie_Market_Research::OPT_AI_MODEL, $model );
			update_option( Gabochie_Market_Research::OPT_DIGEST_EMAIL, $email );
			update_option( Gabochie_Market_Research::OPT_FEEDS, $feeds );

			if ( '0' === $enabled && wp_next_scheduled( Gabochie_Market_Research::HOOK_MEMO ) ) {
				wp_clear_scheduled_hook( Gabochie_Market_Research::HOOK_MEMO );
			} elseif ( '1' === $enabled ) {
				Gabochie_Market_Research::instance()->ensure_crons();
			}
		}
		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => self::PAGE,
					'tab'  => 'settings',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}