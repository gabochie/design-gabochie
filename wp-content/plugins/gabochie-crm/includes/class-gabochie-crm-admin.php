<?php
/**
 * Gabochie CRM — wp-admin contacts management.
 *
 * Renders a Contacts list table (search, filters, pagination, bulk actions),
 * a per-contact edit form, a Newsletter audience view (stats + add-contact
 * form + list table defaulting to the subscribed filter), CSV export, and
 * single/bulk actions. All admin mutations are nonce-guarded and gated on
 * manage_options.
 *
 * @package Gabochie_Crm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin page controller.
 */
class Gabochie_Crm_Admin {


	const PAGE = 'gabochie-crm';

	/**
	 * Wire hooks.
	 */
	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_process_actions' ) );
		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Register the top-level Contacts menu + screen options.
	 */
	public static function register_menu() {
		$hook = add_menu_page(
			__( 'Contacts', 'gabochie-crm' ),
			__( 'Contacts', 'gabochie-crm' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' ),
			'dashicons-groups',
			26
		);
		add_action(
			"load-{$hook}",
			static function () {
				add_screen_option(
					'per_page',
					array(
						'default' => 25,
						'option'  => 'gab_crm_contacts_per_page',
					)
				);
			}
		);
	}

	/**
	 * Persist the per-page screen option.
	 *
	 * @param  mixed  $status Unused status.
	 * @param  string $option Option name.
	 * @param  mixed  $value  Submitted value.
	 * @return mixed
	 */
	public static function set_screen_option( $status, $option, $value ) {
		if ( 'gab_crm_contacts_per_page' === $option ) {
			return max( 1, min( 500, (int) $value ) );
		}
		return $status;
	}

	/**
	 * Admin CSS.
	 *
	 * @param string $hook Current admin screen.
	 */
	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'toplevel_page_' . self::PAGE ) ) {
			return;
		}
		wp_enqueue_style(
			'gab-crm-admin',
			plugins_url( 'assets/css/crm-admin.css', __DIR__ ),
			array(),
			GABOCHIE_CRM_VERSION
		);
	}

	/**
	 * Process single/bulk actions + exports. Runs on admin_init so
	 * redirects/exports happen before any HTML output.
	 */
	public static function maybe_process_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::PAGE !== $page ) {
			return;
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'contacts'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'newsletter' !== $tab ) {
			$tab = 'contacts';
		}

		// CSV export (list-table tablenav button).
		if ( isset( $_GET['export_csv'] ) && check_admin_referer( 'bulk-contacts' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			Gabochie_Crm_Export::admin_export();
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $action && isset( $_GET['action2'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action = sanitize_key( wp_unslash( $_GET['action2'] ) );
		}

		if ( '' === $action || '-1' === $action ) {
			return;
		}

		// Single row delete (row-action link, per-id nonce).
		if ( 'gab_crm_delete' === $action ) {
			$id    = isset( $_GET['contact'] ) ? (int) wp_unslash( $_GET['contact'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( $id > 0 && wp_verify_nonce( $nonce, 'gab_crm_delete_' . $id ) ) {
				$deleted = Gabochie_Crm::delete_contact( $id );
				$args    = array(
					'page'    => self::PAGE,
					'deleted' => $deleted ? '1' : '0',
				);
				if ( 'newsletter' === $tab ) {
					$args['tab'] = 'newsletter';
				}
				wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
				exit;
			}
			return;
		}

		// Bulk actions from the list table.
		$bulk_actions = array(
			'set_subscribed',
			'set_unsubscribed',
			'set_pending',
			'add_tags',
			'remove_tags',
			'export_selected',
			'delete',
		);
		if ( in_array( $action, $bulk_actions, true ) && check_admin_referer( 'bulk-contacts' ) ) {
			$ids = isset( $_GET['contact_ids'] ) && is_array( $_GET['contact_ids'] )
			? array_map( 'absint', wp_unslash( $_GET['contact_ids'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			: array();
			if ( empty( $ids ) ) {
				return;
			}

			$notice = 'bulk-none';
			$count  = 0;
			switch ( $action ) {
				case 'set_subscribed':
				case 'set_unsubscribed':
				case 'set_pending':
					$count  = Gabochie_Crm::bulk_set_status( $ids, str_replace( 'set_', '', $action ) );
					$notice = 'bulk-status';
					if ( 'newsletter' === $tab && in_array( $action, array( 'set_subscribed', 'set_unsubscribed' ), true ) ) {
						self::sync_newsletter_map( $ids, 'set_subscribed' === $action ? 'subscribe' : 'unsubscribe' );
					}
					break;
				case 'add_tags':
				case 'remove_tags':
					$tags   = isset( $_GET['bulk_tags'] ) ? sanitize_text_field( wp_unslash( $_GET['bulk_tags'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$count  = 'add_tags' === $action ? Gabochie_Crm::bulk_add_tags( $ids, $tags ) : Gabochie_Crm::bulk_remove_tags( $ids, $tags );
					$notice = 'bulk-tags';
					break;
				case 'delete':
					$count = 0;
					foreach ( $ids as $id ) {
						if ( Gabochie_Crm::delete_contact( $id ) ) {
							++$count;
						}
					}
					$notice = 'bulk-deleted';
					break;
			}

			$export_ids = 'export_selected' === $action ? $ids : array();
			if ( ! empty( $export_ids ) ) {
				Gabochie_Crm_Export::stream_contact_ids( $export_ids );
			}

			$redirect_args = array(
				'page'  => self::PAGE,
				$notice => $count,
			);
			if ( 'newsletter' === $tab ) {
				$redirect_args['tab'] = 'newsletter';
			}

			wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
			exit;
		}

		// Single contact edit save (edit form, per-id nonce).
		if ( 'gab_crm_update' === $action ) {
			$id    = isset( $_GET['contact'] ) ? (int) wp_unslash( $_GET['contact'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( $id > 0 && wp_verify_nonce( $nonce, 'gab_crm_update_' . $id ) ) {
				$data          = array(
					'first_name'   => isset( $_GET['first_name'] ) ? sanitize_text_field( wp_unslash( $_GET['first_name'] ) ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'last_name'    => isset( $_GET['last_name'] ) ? sanitize_text_field( wp_unslash( $_GET['last_name'] ) ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'phone'        => isset( $_GET['phone'] ) ? sanitize_text_field( wp_unslash( $_GET['phone'] ) ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'status'       => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'contact_type' => isset( $_GET['contact_type'] ) ? sanitize_key( wp_unslash( $_GET['contact_type'] ) ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'list_key'     => isset( $_GET['list_key'] ) ? sanitize_key( wp_unslash( $_GET['list_key'] ) ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'source'       => isset( $_GET['source'] ) ? sanitize_key( wp_unslash( $_GET['source'] ) ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'tags'         => isset( $_GET['tags'] ) ? wp_unslash( $_GET['tags'] ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				);
				$updated       = Gabochie_Crm::update_contact( $id, $data );
				$redirect_args = array(
					'page'    => self::PAGE,
					'updated' => $updated ? '1' : '0',
					'edit'    => $id,
				);
				if ( 'newsletter' === $tab ) {
					$redirect_args['tab'] = 'newsletter';
				}
				wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// Newsletter: add-contact form (GET, per-form nonce).
		if ( 'gab_nl_add' === $action && check_admin_referer( 'gab-nl-add' ) ) {
			$url   = add_query_arg(
				array(
					'page' => self::PAGE,
					'tab'  => 'newsletter',
				),
				admin_url( 'admin.php' )
			);
			$email = strtolower( trim( sanitize_email( wp_unslash( $_GET['email'] ?? '' ) ) ) );
			if ( '' === $email || ! is_email( $email ) ) {
				wp_safe_redirect( add_query_arg( 'nl-status', 'bad-email', $url ) );
				exit;
			}
			$first = isset( $_GET['first_name'] ) ? sanitize_text_field( wp_unslash( $_GET['first_name'] ) ) : '';
			$last  = isset( $_GET['last_name'] ) ? sanitize_text_field( wp_unslash( $_GET['last_name'] ) ) : '';
			$tags  = isset( $_GET['tags'] ) ? sanitize_text_field( wp_unslash( $_GET['tags'] ) ) : '';
			$id    = Gabochie_Crm::upsert_contact(
				$email,
				array(
					'first_name' => '' === $first ? null : $first,
					'last_name'  => '' === $last ? null : $last,
					'status'     => Gabochie_Crm::STATUS_SUBSCRIBED,
					'source'     => 'newsletter-admin',
					'list_key'   => 'newsletter-subscribe',
					'tags'       => '' === $tags ? null : $tags,
				)
			);
			if ( $id > 0 ) {
				if ( class_exists( 'Gabochie_Newsletter' ) ) {
					Gabochie_Newsletter::instance()->admin_subscribe( $email );
				}
				wp_safe_redirect( add_query_arg( 'nl-status', 'added', $url ) );
			} else {
				wp_safe_redirect( add_query_arg( 'nl-status', 'save-failed', $url ) );
			}
			exit;
		}
	}

	/**
	 * Render the admin page (edit form, newsletter view or list table).
	 */
	public static function render_page() {
		$tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'contacts'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab     = 'newsletter' === $tab ? 'newsletter' : 'contacts';
		$edit_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Contacts', 'gabochie-crm' ) . '</h1>';
		self::render_tabs( $tab );
		self::render_notices();
		if ( $edit_id > 0 ) {
			self::render_edit( $edit_id, $tab );
			echo '</div>';
			return;
		}
		if ( 'newsletter' === $tab ) {
			self::render_newsletter();
			echo '</div>';
			return;
		}
		self::render_list();
		echo '</div>';
	}

	/**
	 * Nav tabs for the Contacts / Newsletter views.
	 *
	 * @param string $current Active tab key.
	 */
	private static function render_tabs( $current ) {
		$tabs = array(
			'contacts'   => __( 'Contacts', 'gabochie-crm' ),
			'newsletter' => __( 'Newsletter', 'gabochie-crm' ),
		);
		echo '<nav class="nav-tab-wrapper wp-clearfix">';
		foreach ( $tabs as $key => $label ) {
			$url = add_query_arg(
				array(
					'page' => self::PAGE,
					'tab'  => $key,
				),
				admin_url( 'admin.php' )
			);
			$cls = 'nav-tab' . ( $key === $current ? ' nav-tab-active' : '' );
			echo '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</nav>';
		echo '<hr class="wp-header-end">';
	}

	/**
	 * Render admin notices from query flags.
	 */
	private static function render_notices() {
		$deleted = isset( $_GET['deleted'] ) ? sanitize_text_field( wp_unslash( $_GET['deleted'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$updated = isset( $_GET['updated'] ) ? sanitize_text_field( wp_unslash( $_GET['updated'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '1' === $deleted ) {
			echo '<div id="message" class="notice notice-success is-dismissible"><p>' . esc_html__( 'Contact deleted.', 'gabochie-crm' ) . '</p></div>';
		}
		if ( '1' === $updated ) {
			echo '<div id="message" class="notice notice-success is-dismissible"><p>' . esc_html__( 'Contact updated.', 'gabochie-crm' ) . '</p></div>';
		}
		if ( isset( $_GET['bulk-status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$n = max( 0, (int) $_GET['bulk-status'] );
			echo '<div id="message" class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( _n( '%d contact status updated.', '%d contacts status updated.', $n, 'gabochie-crm' ), $n ) ) . '</p></div>';
		}
		if ( isset( $_GET['bulk-tags'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$n = max( 0, (int) $_GET['bulk-tags'] );
			echo '<div id="message" class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( _n( '%d contact tags updated.', '%d contacts tags updated.', $n, 'gabochie-crm' ), $n ) ) . '</p></div>';
		}
		if ( isset( $_GET['bulk-deleted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$n = max( 0, (int) $_GET['bulk-deleted'] );
			echo '<div id="message" class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( _n( '%d contact deleted.', '%d contacts deleted.', $n, 'gabochie-crm' ), $n ) ) . '</p></div>';
		}
		if ( isset( $_GET['bulk-none'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div id="message" class="notice notice-warning is-dismissible"><p>' . esc_html__( 'No contacts selected for that action.', 'gabochie-crm' ) . '</p></div>';
		}
		if ( isset( $_GET['nl-status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$nls  = sanitize_key( wp_unslash( $_GET['nl-status'] ) );
			$msgs = array(
				'added'       => __( 'Contact added to the newsletter.', 'gabochie-crm' ),
				'bad-email'   => __( 'Please enter a valid email address.', 'gabochie-crm' ),
				'save-failed' => __( 'Could not save the contact. Please try again.', 'gabochie-crm' ),
			);
			$type = 'bad-email' === $nls ? 'warning' : 'success';
			if ( isset( $msgs[ $nls ] ) ) {
				echo '<div id="message" class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $msgs[ $nls ] ) . '</p></div>';
			}
		}
	}

	/**
	 * Render the list table view.
	 */
	private static function render_list() {
		$table = new Gabochie_Crm_List_Table();
		$table->prepare_items();
		?>
		<form method="get" id="gab-crm-filter">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE ); ?>">
		<?php $table->search_box( __( 'Search contacts', 'gabochie-crm' ), 'gab-crm-search' ); ?>
		<?php $table->display(); ?>
		</form>
		<?php
	}

	/**
	 * Render the newsletter view: audience stats + add-contact form + list
	 * table that defaults to the subscribed filter (who actually gets emailed).
	 */
	private static function render_newsletter() {
		$stats = self::newsletter_stats();
		$table = new Gabochie_Crm_List_Table(
			array(
				'tab'            => 'newsletter',
				'default_status' => 'subscribed',
			)
		);
		$table->prepare_items();
		?>
		<div class="gab-crm-nl-stats">
			<p><?php echo esc_html( self::newsletter_stats_summary( $stats ) ); ?></p>
		</div>

		<div class="gab-crm-nl-add">
			<h2><?php esc_html_e( 'Add contact to the newsletter', 'gabochie-crm' ); ?></h2>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE ); ?>">
				<input type="hidden" name="tab" value="newsletter">
				<input type="hidden" name="action" value="gab_nl_add">
			<?php wp_nonce_field( 'gab-nl-add' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="gab-nl-add-email"><?php esc_html_e( 'Email', 'gabochie-crm' ); ?> *</label></th>
						<td><input type="email" class="regular-text" id="gab-nl-add-email" name="email" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="gab-nl-add-first"><?php esc_html_e( 'First name', 'gabochie-crm' ); ?></label></th>
						<td><input type="text" class="regular-text" id="gab-nl-add-first" name="first_name"></td>
					</tr>
					<tr>
						<th scope="row"><label for="gab-nl-add-last"><?php esc_html_e( 'Last name', 'gabochie-crm' ); ?></label></th>
						<td><input type="text" class="regular-text" id="gab-nl-add-last" name="last_name"></td>
					</tr>
					<tr>
						<th scope="row"><label for="gab-nl-add-tags"><?php esc_html_e( 'Tags', 'gabochie-crm' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" id="gab-nl-add-tags" name="tags" placeholder="<?php esc_attr_e( 'e.g. newsletter-manual', 'gabochie-crm' ); ?>">
							<p class="description"><?php esc_html_e( 'New contacts are stored as status subscribed, source newsletter-admin, list newsletter-subscribe, and will be emailed on the next publish.', 'gabochie-crm' ); ?></p>
						</td>
					</tr>
				</table>
			<?php submit_button( __( 'Add contact', 'gabochie-crm' ), 'primary', 'gab-nl-add-btn', false ); ?>
			</form>
		</div>

		<form method="get" id="gab-crm-filter">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE ); ?>">
			<input type="hidden" name="tab" value="newsletter">
		<?php $table->search_box( __( 'Search newsletter', 'gabochie-crm' ), 'gab-nl-search' ); ?>
		<?php $table->display(); ?>
		</form>
		<?php
	}

	/**
	 * Human-readable newsletter audience line.
	 *
	 * @param  array $stats From newsletter_stats().
	 * @return string
	 */
	private static function newsletter_stats_summary( array $stats ) {
		return sprintf(
			/* translators: 1 = audience to email, 2 = subscribed total, 3 = do-not-email map size */
			__( 'Newsletter audience: %1$d of %2$d subscribed contacts to email (%3$d on the do-not-email map are skipped).', 'gabochie-crm' ),
			(int) $stats['audience'],
			(int) $stats['subscribed'],
			(int) $stats['unsub_map']
		);
	}

	/**
	 * Newsletter audience stats: subscribed count, do-not-email map size and
	 * the true email-able audience (subscribed and not on the map).
	 *
	 * @return array{subscribed:int,unsub_map:int,audience:int}
	 */
	private static function newsletter_stats() {
		$subscribed = Gabochie_Crm::count_contacts( Gabochie_Crm::STATUS_SUBSCRIBED );
		$map        = class_exists( 'Gabochie_Newsletter' ) ? Gabochie_Newsletter::do_not_email_map() : array();
		$audience   = $subscribed;
		if ( ! empty( $map ) && Gabochie_Crm::ready() ) {
			global $wpdb;
			$emails       = array_keys( $map );
			$placeholders = implode( ',', array_fill( 0, count( $emails ), '%s' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$mapped   = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM %i WHERE status = %s AND email IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					Gabochie_Crm::table(),
					Gabochie_Crm::STATUS_SUBSCRIBED,
					...$emails
				)
			);
			$audience = max( 0, $subscribed - $mapped );
		}
		return array(
			'subscribed' => (int) $subscribed,
			'unsub_map'  => count( $map ),
			'audience'   => (int) $audience,
		);
	}

	/**
	 * Keep the newsletter do-not-email map in sync with a status change made
	 * from the newsletter tab.
	 *
	 * @param array  $ids  Contact ids.
	 * @param string $mode 'subscribe' clears the map entry, 'unsubscribe' adds it.
	 */
	private static function sync_newsletter_map( array $ids, $mode ) {
		if ( ! class_exists( 'Gabochie_Newsletter' ) ) {
			return;
		}
		$nl = Gabochie_Newsletter::instance();
		foreach ( array_unique( array_map( 'absint', $ids ) ) as $id ) {
			$row = Gabochie_Crm::get_contact_by_id( $id );
			if ( ! $row || empty( $row['email'] ) ) {
				continue;
			}
			if ( 'unsubscribe' === $mode ) {
				$nl->admin_unsubscribe( $row['email'] );
			} else {
				$nl->admin_subscribe( $row['email'] );
			}
		}
	}

	/**
	 * Render the single-contact edit form.
	 *
	 * @param int    $id  Contact id.
	 * @param string $tab Current admin tab.
	 */
	private static function render_edit( $id, $tab = 'contacts' ) {
		$row = Gabochie_Crm::get_contact_by_id( $id );
		if ( ! $row ) {
			echo '<p>' . esc_html__( 'Contact not found.', 'gabochie-crm' ) . '</p>';
			return;
		}
		$back = 'newsletter' === $tab
			? admin_url( 'admin.php?page=' . self::PAGE . '&tab=newsletter' )
			: admin_url( 'admin.php?page=' . self::PAGE );
		$url  = add_query_arg(
			array(
				'action'   => 'gab_crm_update',
				'contact'  => $id,
				'_wpnonce' => wp_create_nonce( 'gab_crm_update_' . $id ),
			),
			admin_url( 'admin.php?page=' . self::PAGE )
		);
		?>
		<p><a href="<?php echo esc_url( $back ); ?>">&larr; <?php esc_html_e( 'Back to list', 'gabochie-crm' ); ?></a></p>
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE ); ?>">
			<?php if ( 'newsletter' === $tab ) : ?>
				<input type="hidden" name="tab" value="newsletter">
			<?php endif; ?>
			<input type="hidden" name="action" value="gab_crm_update">
			<input type="hidden" name="contact" value="<?php echo esc_attr( (string) $id ); ?>">
		<?php wp_nonce_field( 'gab_crm_update_' . $id ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="gab-email"><?php esc_html_e( 'Email', 'gabochie-crm' ); ?></label></th>
					<td><input type="email" class="regular-text" id="gab-email" value="<?php echo esc_attr( $row['email'] ); ?>" readonly disabled></td>
				</tr>
				<tr>
					<th scope="row"><label for="gab-first"><?php esc_html_e( 'First name', 'gabochie-crm' ); ?></label></th>
					<td><input type="text" class="regular-text" id="gab-first" name="first_name" value="<?php echo esc_attr( $row['first_name'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="gab-last"><?php esc_html_e( 'Last name', 'gabochie-crm' ); ?></label></th>
					<td><input type="text" class="regular-text" id="gab-last" name="last_name" value="<?php echo esc_attr( $row['last_name'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="gab-phone"><?php esc_html_e( 'Phone', 'gabochie-crm' ); ?></label></th>
					<td><input type="text" class="regular-text" id="gab-phone" name="phone" value="<?php echo esc_attr( $row['phone'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="gab-status"><?php esc_html_e( 'Status', 'gabochie-crm' ); ?></label></th>
					<td>
						<select name="status" id="gab-status">
		<?php foreach ( array( Gabochie_Crm::STATUS_SUBSCRIBED, Gabochie_Crm::STATUS_UNSUBSCRIBED, Gabochie_Crm::STATUS_PENDING ) as $s ) : ?>
							<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $row['status'], $s ); ?>><?php echo esc_html( $s ); ?></option>
		<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="gab-type"><?php esc_html_e( 'Contact type', 'gabochie-crm' ); ?></label></th>
					<td><input type="text" class="regular-text" id="gab-type" name="contact_type" value="<?php echo esc_attr( (string) $row['contact_type'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="gab-list"><?php esc_html_e( 'List key', 'gabochie-crm' ); ?></label></th>
					<td><input type="text" class="regular-text" id="gab-list" name="list_key" value="<?php echo esc_attr( $row['list_key'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="gab-source"><?php esc_html_e( 'Source', 'gabochie-crm' ); ?></label></th>
					<td><input type="text" class="regular-text" id="gab-source" name="source" value="<?php echo esc_attr( $row['source'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="gab-tags"><?php esc_html_e( 'Tags', 'gabochie-crm' ); ?></label></th>
					<td><input type="text" class="regular-text" id="gab-tags" name="tags" value="<?php echo esc_attr( $row['tags'] ); ?>"><p class="description"><?php esc_html_e( 'Whitespace, commas or colons separate tags; only letters, numbers, underscores and dashes are kept.', 'gabochie-crm' ); ?></p></td>
				</tr>
			</table>
		<?php submit_button( __( 'Save contact', 'gabochie-crm' ) ); ?>
		</form>
		<?php
	}
}