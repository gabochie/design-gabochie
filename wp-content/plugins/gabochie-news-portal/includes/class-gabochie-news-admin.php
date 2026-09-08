<?php
/**
 * Gabochie News Portal — admin.
 *
 * Editorial queue (WP_List_Table), sources registry editor, AI model setting and
 * fetch/error controls under a top-level "News Portal" menu. All state-changing
 * requests run on admin_init with nonces; the page itself renders the tabs.
 *
 * @package Gabochie_News_Portal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings/queue admin class.
 */
class Gabochie_News_Admin {

	const PAGE = 'gabochie-news-portal';

	/** @var Gabochie_News_Admin|null */
	private static $instance = null;

	/** @return Gabochie_News_Admin */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire the admin hooks.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_get_action' ) );
		add_action( 'admin_init', array( $this, 'handle_post' ) );
		add_action( 'admin_init', array( $this, 'handle_bulk' ) );
		add_action( 'admin_post_gnp_save_sources', array( $this, 'handle_sources_form' ) );
		add_action( 'admin_post_gnp_delete_source', array( $this, 'handle_delete_source' ) );
		add_action( 'admin_post_gnp_save_settings', array( $this, 'handle_settings_form' ) );
	}

	/**
	 * Top-level menu.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'News Portal', 'gabochie-news-portal' ),
			__( 'News Portal', 'gabochie-news-portal' ),
			'edit_posts',
			self::PAGE,
			array( $this, 'render_page' ),
			'dashicons-megaphone',
			4
		);
	}

	/**
	 * Redirect-with-notice helper.
	 *
	 * @param string $message
	 * @param string $tab
	 */
	private function redirect_notice( $message, $tab = 'queue' ) {
		set_transient( 'gnp_admin_notice', $message, 60 );
		$url = add_query_arg(
			array(
				'page' => self::PAGE,
				'tab'  => $tab,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Row-action + "fetch now" GET handlers.
	 */
	public function handle_get_action() {
		if ( ! isset( $_GET['page'], $_GET['action'] ) || self::PAGE !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$action = sanitize_key( wp_unslash( $_GET['action'] ) );

		if ( 'gnp_fetch' === $action ) {
			$row = $this->handle_fetch_now();
		} else {
			$row = $this->handle_single_get_action( $action );
		}
		if ( ! empty( $row['msg'] ) ) {
			$this->redirect_notice( $row['msg'], isset( $row['tab'] ) ? $row['tab'] : 'queue' );
		}
	}

	/**
	 * Single-item GET actions with per-item nonce.
	 *
	 * @param string $action
	 * @return array
	 */
	private function handle_single_get_action( $action ) {
		$allowed = array( 'gnp_publish', 'gnp_take', 'gnp_approve', 'gnp_delete' );
		if ( ! in_array( $action, $allowed, true ) ) {
			return array();
		}
		$id  = isset( $_GET['item'] ) ? (int) wp_unslash( $_GET['item'] ) : 0; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$row = get_post( $id );
		if ( ! $row || Gabochie_News_Portal::CPT !== $row->post_type ) {
			return array( 'msg' => __( 'That news item no longer exists.', 'gabochie-news-portal' ) );
		}
		check_admin_referer( 'gnp_item_' . $id, '_wpnonce' );

		switch ( $action ) {
			case 'gnp_publish':
				$new = 'draft' === $row->post_status ? 'publish' : 'draft';
				$res = wp_update_post(
					array(
						'ID'          => $id,
						'post_status' => $new,
					)
				);
				$msg = is_wp_error( $res ) ? $res->get_error_message() : sprintf(
					// translators: %s = published|moved back to draft.
					__( 'News item %s.', 'gabochie-news-portal' ),
					'publish' === $new ? __( 'published', 'gabochie-news-portal' ) : __( 'moved back to draft', 'gabochie-news-portal' )
				);
				$tab = 'publish' === $new ? 'queue&status=publish' : 'queue';
				return array(
					'msg' => $msg,
					'tab' => $tab,
				);

			case 'gnp_take':
				$take = Gabochie_News_AI::instance()->draft_take( $id );
				if ( false === $take ) {
					return array( 'msg' => __( 'Take generation failed — see News Portal sources tab for errors.', 'gabochie-news-portal' ) );
				}
				return array( 'msg' => __( 'Take drafted — review and approve it for the site to render it.', 'gabochie-news-portal' ) );

			case 'gnp_approve':
				if ( '' === (string) get_post_meta( $id, Gabochie_News_Portal::META_TAKE, true ) ) {
					return array( 'msg' => __( 'Generate a take first.', 'gabochie-news-portal' ) );
				}
				Gabochie_News_AI::instance()->set_take_status( $id, Gabochie_News_Portal::TAKE_APPROVED );
				return array( 'msg' => __( 'Take approved — it now renders on the site.', 'gabochie-news-portal' ) );

			case 'gnp_delete':
				wp_delete_post( $id, true );
				return array( 'msg' => __( 'News item deleted.', 'gabochie-news-portal' ) );

			default:
				return array();
		}
	}

	/**
	 * "Fetch now" — run ingestion for every enabled source.
	 *
	 * @return array
	 */
	private function handle_fetch_now() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array( 'msg' => __( 'Fetch requires the manage_options capability.', 'gabochie-news-portal' ) );
		}
		check_admin_referer( 'gnp_fetch' );
		$results = Gabochie_News_Portal::instance()->fetch_all();

		$added = 0;
		$fails = 0;
		foreach ( $results as $r ) {
			if ( empty( $r['ok'] ) ) {
				++$fails;
			}
			$added += (int) $r['added'];
		}
		$msg = sprintf(
			// translators: %1$d added, %2$d failed sources.
			__( 'Fetch complete: %1$d new draft(s) ingested, %2$d source(s) failed (see sources tab).', 'gabochie-news-portal' ),
			(int) $added,
			(int) $fails
		);
		return array( 'msg' => $msg );
	}

	/**
	 * Bulk actions posted from the queue.
	 */
	public function handle_bulk() {
		if ( self::PAGE !== ( isset( $_POST['page'] ) ? sanitize_key( wp_unslash( $_POST['page'] ) ) : '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		$action = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
		$ids    = isset( $_POST['news_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['news_ids'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( '' === $action || empty( $ids ) ) {
			return;
		}
		check_admin_referer( 'bulk-news_items' );

		$count = 0;
		foreach ( $ids as $pid ) {
			if ( ! $this->apply_bulk( $action, $pid ) ) {
				continue;
			}
			++$count;
		}
		$this->redirect_notice(
			sprintf(
				/* translators: %d = item count. */
				__( 'Bulk %1$s applied to %2$d item(s).', 'gabochie-news-portal' ),
				str_replace( 'gnp_bulk_', '', $action ),
				(int) $count
			)
		);
	}

	/**
	 * Apply one bulk action to one item.
	 *
	 * @param string $action
	 * @param int    $pid
	 * @return bool
	 */
	private function apply_bulk( $action, $pid ) {
		switch ( $action ) {
			case 'gnp_bulk_publish':
				return ! is_wp_error(
					wp_update_post(
						array(
							'ID'          => $pid,
							'post_status' => 'publish',
						)
					)
				);
			case 'gnp_bulk_take':
				return false !== Gabochie_News_AI::instance()->draft_take( $pid );
			case 'gnp_bulk_approve':
				if ( '' === (string) get_post_meta( $pid, Gabochie_News_Portal::META_TAKE, true ) ) {
					return false;
				}
				Gabochie_News_AI::instance()->set_take_status( $pid, Gabochie_News_Portal::TAKE_APPROVED );
				return true;
			case 'gnp_bulk_delete':
				return false !== wp_delete_post( $pid, true );
		}
		return false;
	}

	/**
	 * Sources POST form (admin-post).
	 */
	public function handle_sources_form() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( esc_html__( 'Not allowed.', 'gabochie-news-portal' ) );
		}
		check_admin_referer( 'gnp_sources_form' );

		$portal  = Gabochie_News_Portal::instance();
		$sources = $portal->sources();

		$enabled = isset( $_POST['enabled'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['enabled'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$trusted = isset( $_POST['trusted'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['trusted'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$ai_take = isset( $_POST['ai_take'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['ai_take'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		foreach ( $sources as $slug => $source ) {
			$sources[ $slug ]['enabled'] = in_array( $slug, $enabled, true ) ? '1' : '0';
			$sources[ $slug ]['trusted'] = in_array( $slug, $trusted, true ) || ! empty( $source['trusted'] );
			$sources[ $slug ]['ai_take'] = in_array( $slug, $ai_take, true );
		}

		$portal->save_sources( $sources );
		$this->redirect_notice( __( 'Sources saved.', 'gabochie-news-portal' ), 'sources' );
	}

	/**
	 * Delete one source (admin-post).
	 */
	public function handle_delete_source() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( esc_html__( 'Not allowed.', 'gabochie-news-portal' ) );
		}
		$slug = isset( $_GET['source'] ) ? sanitize_key( wp_unslash( $_GET['source'] ) ) : '';
		if ( '' === $slug ) {
			$this->redirect_notice( __( 'No source given.', 'gabochie-news-portal' ), 'sources' );
		}
		check_admin_referer( 'gnp_delete_source_' . $slug );

		$portal  = Gabochie_News_Portal::instance();
		$sources = $portal->sources();
		unset( $sources[ $slug ] );
		$portal->save_sources( $sources );
		$this->redirect_notice( __( 'Source deleted.', 'gabochie-news-portal' ), 'sources' );
	}

	/**
	 * Settings form (admin-post).
	 */
	public function handle_settings_form() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( esc_html__( 'Not allowed.', 'gabochie-news-portal' ) );
		}
		check_admin_referer( 'gnp_settings_form' );
		if ( isset( $_POST['gnp_ai_model'] ) ) {
			update_option( Gabochie_News_AI::OPT_MODEL, sanitize_text_field( wp_unslash( $_POST['gnp_ai_model'] ) ) );
		}
		$this->redirect_notice( __( 'Settings saved.', 'gabochie-news-portal' ), 'sources' );
	}

	/**
	 * POST handlers (placeholder hooks above handle the rest).
	 */
	public function handle_post() {}

	/**
	 * Render the page — tabs: Queue, Sources.
	 */
	public function render_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'gabochie-news-portal' ) );
		}
		$notice = get_transient( 'gnp_admin_notice' );
		if ( $notice ) {
			delete_transient( 'gnp_admin_notice' );
		}
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'queue'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $tab, array( 'queue', 'sources' ), true ) ) {
			$tab = 'queue';
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Gabochie News Portal', 'gabochie-news-portal' ); ?></h1>
			<?php if ( $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper">
				<a class="nav-tab<?php echo 'queue' === $tab ? ' nav-tab-active' : ''; ?>" href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'page' => self::PAGE,
							'tab'  => 'queue',
						),
						admin_url( 'admin.php' )
					)
				);
				?>
									"><?php esc_html_e( 'Queue', 'gabochie-news-portal' ); ?></a>
				<a class="nav-tab<?php echo 'sources' === $tab ? ' nav-tab-active' : ''; ?>" href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'page' => self::PAGE,
							'tab'  => 'sources',
						),
						admin_url( 'admin.php' )
					)
				);
				?>
									"><?php esc_html_e( 'Sources & Settings', 'gabochie-news-portal' ); ?></a>
			</nav>

			<?php if ( 'queue' === $tab ) : ?>
				<?php $this->render_queue(); ?>
			<?php else : ?>
				<?php $this->render_sources(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Editorial queue tab.
	 */
	private function render_queue() {
		$fetch_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'   => self::PAGE,
					'tab'    => 'queue',
					'action' => 'gnp_fetch',
				),
				admin_url( 'admin.php' )
			),
			'gnp_fetch'
		);
		$last      = get_option( Gabochie_News_Portal::OPT_LAST, '' );
		?>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( $fetch_url ); ?>"><?php esc_html_e( 'Fetch now', 'gabochie-news-portal' ); ?></a>
			<?php
			if ( '' !== $last ) {
				echo esc_html(
					sprintf(
					/* translators: %s = last fetch time. */
						__( 'Last fetch: %s (daily catch-up at 05:45).', 'gabochie-news-portal' ),
						gmdate( 'Y-m-d H:i', strtotime( $last ) )
					)
				);
			}
			?>
		</p>
		<form method="post">
			<?php
			$table = new Gabochie_News_List_Table();
			$table->prepare_items();
			$table->display();
			?>
		</form>
		<?php
	}

	/**
	 * Sources + settings tab.
	 */
	private function render_sources() {
		$portal  = Gabochie_News_Portal::instance();
		$sources = $portal->sources();
		$errors  = $portal->recent_errors();
		?>
		<h2><?php esc_html_e( 'RSS Sources', 'gabochie-news-portal' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'gnp_sources_form' ); ?>
			<input type="hidden" name="action" value="gnp_save_sources" />
			<table class="widefat striped">
				<thead>
					<tr>
						<th class="check-column"></th>
						<th><?php esc_html_e( 'Source', 'gabochie-news-portal' ); ?></th>
						<th><?php esc_html_e( 'Feed URL', 'gabochie-news-portal' ); ?></th>
						<th><?php esc_html_e( 'AI take', 'gabochie-news-portal' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $sources as $slug => $source ) : ?>
					<tr>
						<th class="check-column">
							<input type="checkbox" name="enabled[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( '1', (string) $source['enabled'] ); ?> />
						</th>
						<td>
							<strong><?php echo esc_html( $source['name'] ); ?></strong>
							<?php
							if ( ! empty( $source['trusted'] ) ) :
								?>
								<span class="dashicons dashicons-yes-alt" title="<?php esc_attr_e( 'Trusted', 'gabochie-news-portal' ); ?>"></span><?php endif; ?>
							<input type="hidden" name="trusted[]" value="<?php echo esc_attr( $slug ); ?>" />
						</td>
						<td><a href="<?php echo esc_url( $source['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $source['url'] ); ?></a></td>
						<td><input type="checkbox" name="ai_take[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( ! empty( $source['ai_take'] ) ); ?> /></td>
						<td>
							<a class="submitdelete" href="
							<?php
							echo esc_url(
								wp_nonce_url(
									add_query_arg(
										array(
											'action' => 'gnp_delete_source',
											'source' => $slug,
										),
										admin_url( 'admin-post.php' )
									),
									'gnp_delete_source_' . $slug
								)
							);
							?>
															"><?php esc_html_e( 'Delete', 'gabochie-news-portal' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p><?php esc_html_e( 'Uncheck a source to pause it without deleting. The full list is managed by the plugin data/sources.json on first activation.', 'gabochie-news-portal' ); ?></p>
			<?php submit_button( __( 'Save sources', 'gabochie-news-portal' ), 'primary', 'submit' ); ?>
		</form>

		<hr />
		<h2><?php esc_html_e( 'AI Brief Settings', 'gabochie-news-portal' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'gnp_settings_form' ); ?>
			<input type="hidden" name="action" value="gnp_save_settings" />
			<p>
				<label for="gnp_ai_model"><?php esc_html_e( 'Builtin Gemini fallback model', 'gabochie-news-portal' ); ?>:</label>
				<select name="gnp_ai_model" id="gnp_ai_model">
					<?php
					$current = (string) get_option( Gabochie_News_AI::OPT_MODEL, 'gemini-3.5-flash' );
					foreach ( array( 'gemini-3.5-flash', 'gemini-flash-latest', 'gemini-2.5-pro' ) as $m ) {
						echo '<option value="' . esc_attr( $m ) . '" ' . selected( $current, $m, false ) . '>' . esc_html( $m ) . '</option>';
					}
					?>
				</select>
			</p>
			<p class="description"><?php esc_html_e( 'Takes reuse the auto-blog AI chain first and fall back to this builtin Gemini model reading the shared ghbc_llm_key.', 'gabochie-news-portal' ); ?></p>
			<?php submit_button( __( 'Save settings', 'gabochie-news-portal' ), 'primary', 'submit' ); ?>
		</form>

		<?php if ( ! empty( $errors ) ) : ?>
			<hr />
			<h2><?php esc_html_e( 'Recent errors (last 20)', 'gabochie-news-portal' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Time', 'gabochie-news-portal' ); ?></th><th><?php esc_html_e( 'Error', 'gabochie-news-portal' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $errors as $e ) : ?>
					<tr><td><?php echo esc_html( $e['time'] ); ?></td><td><code><?php echo esc_html( $e['error'] ); ?></code></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}
}