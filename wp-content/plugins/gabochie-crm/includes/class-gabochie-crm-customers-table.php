<?php
/**
 * Customers list table: buyers, with what they paid and when.
 *
 * Separate from the contacts table on purpose - the row shape is different
 * (contact fields joined to completed-order aggregates) and the contacts table
 * is battle-tested, so this view stays isolated from it.
 *
 * @package Gabochie_Crm
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Renders the Customers tab.
 */
class Gabochie_Crm_Customers_Table extends WP_List_Table {

	/**
	 * Total customer rows.
	 *
	 * @var int
	 */
	public $total = 0;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'gabochie-crm-customer',
				'plural'   => 'gabochie-crm-customers',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Admin URL for this tab (preserves the Customers view).
	 *
	 * @return string
	 */
	private function list_url() {
		return admin_url( 'admin.php?page=gabochie-crm&tab=customers' );
	}

	/**
	 * Columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'email'   => __( 'Customer', 'gabochie-crm' ),
			'phone'   => __( 'Phone', 'gabochie-crm' ),
			'orders'  => __( 'Orders', 'gabochie-crm' ),
			'spend'   => __( 'Lifetime spend', 'gabochie-crm' ),
			'last_at' => __( 'Last purchase', 'gabochie-crm' ),
			'status'  => __( 'Newsletter', 'gabochie-crm' ),
		);
	}

	/**
	 * No bulk actions: this view is read-only by design.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array();
	}

	/**
	 * Not sortable - ordering by spend would need a cross-table join.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array();
	}

	/**
	 * Fallback renderer.
	 *
	 * @param array  $item        Row.
	 * @param string $column_name Column.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? esc_html( (string) $item[ $column_name ] ) : '';
	}

	/**
	 * Customer identity + edit link.
	 *
	 * @param array $item Row.
	 * @return string
	 */
	public function column_email( $item ) {
		$email = isset( $item['email'] ) ? (string) $item['email'] : '';
		if ( '' === $email ) {
			return '&mdash;';
		}

		$name = trim( (string) ( $item['first_name'] ?? '' ) . ' ' . (string) ( $item['last_name'] ?? '' ) );
		$id   = isset( $item['id'] ) ? (int) $item['id'] : 0;
		$edit = add_query_arg(
			array(
				'page' => Gabochie_Crm_Admin::PAGE,
				'edit' => $id,
				'tab'  => 'customers',
			),
			admin_url( 'admin.php' )
		);

		$html = '<strong>' . esc_html( $email ) . '</strong>';
		if ( '' !== $name ) {
			$html .= '<br /><span class="gab-crm-dim">' . esc_html( $name ) . '</span>';
		}
		$html .= sprintf(
			'<div class="row-actions"><span><a href="%s">%s</a></span></div>',
			esc_url( $edit ),
			esc_html__( 'Edit contact', 'gabochie-crm' )
		);
		return $html;
	}

	/**
	 * Order count, linking to the latest order in Woo.
	 *
	 * @param array $item Row.
	 * @return string
	 */
	public function column_orders( $item ) {
		$count = isset( $item['order_count'] ) ? (int) $item['order_count'] : 0;
		if ( $count < 1 ) {
			return '<span class="gab-crm-dim">&mdash;</span>';
		}

		$last = isset( $item['stats']['last_order'] ) ? (int) $item['stats']['last_order'] : 0;
		$html = esc_html( number_format_i18n( $count ) );

		// HPOS stores orders outside wp_posts, so get_post() would always fail
		// here. Ask Woo for the edit link instead, and fall back to the legacy
		// post-based URL only when Woo declines.
		if ( $last > 0 && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $last );
			if ( $order && $order->get_id() ) {
				$edit_url = method_exists( $order, 'get_edit_order_url' ) ? $order->get_edit_order_url() : '';
				if ( is_string( $edit_url ) && '' !== $edit_url ) {
					$html = sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), $html );
				}
			}
		}
		return $html;
	}

	/**
	 * Lifetime spend.
	 *
	 * @param array $item Row.
	 * @return string
	 */
	public function column_spend( $item ) {
		$spend    = isset( $item['spend'] ) ? (float) $item['spend'] : 0.0;
		$currency = isset( $item['stats']['currency'] ) ? (string) $item['stats']['currency'] : '';
		if ( $spend <= 0 ) {
			return '<span class="gab-crm-dim">&mdash;</span>';
		}
		return esc_html( trim( $currency . ' ' . number_format_i18n( $spend, 2 ) ) );
	}

	/**
	 * Last purchase date.
	 *
	 * @param array $item Row.
	 * @return string
	 */
	public function column_last_at( $item ) {
		$last = isset( $item['stats']['last_at'] ) ? (string) $item['stats']['last_at'] : '';
		return '' === $last ? '<span class="gab-crm-dim">&mdash;</span>' : esc_html( $last );
	}

	/**
	 * Newsletter status, so it is obvious a buyer is not auto-subscribed.
	 *
	 * @param array $item Row.
	 * @return string
	 */
	public function column_status( $item ) {
		$status = isset( $item['status'] ) ? (string) $item['status'] : '';
		$label  = array(
			Gabochie_Crm::STATUS_SUBSCRIBED   => __( 'Subscribed', 'gabochie-crm' ),
			Gabochie_Crm::STATUS_UNSUBSCRIBED => __( 'Unsubscribed', 'gabochie-crm' ),
			Gabochie_Crm::STATUS_PENDING      => __( 'Not subscribed', 'gabochie-crm' ),
		);
		if ( ! isset( $label[ $status ] ) ) {
			return '<span class="gab-crm-dim">&mdash;</span>';
		}
		$cls = Gabochie_Crm::STATUS_SUBSCRIBED === $status ? 'gab-crm-status-ok' : 'gab-crm-status-off';
		return sprintf( '<span class="gab-crm-status %s">%s</span>', esc_attr( $cls ), esc_html( $label[ $status ] ) );
	}

	/**
	 * Query and paginate.
	 */
	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), array() );

		$per_page = 25;
		$search   = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$data = Gabochie_Crm_Purchases::customers(
			array(
				'search'   => $search,
				'per_page' => $per_page,
				'page'     => $this->get_pagenum(),
			)
		);

		$this->items = $data['items'];
		$this->total = (int) $data['total'];

		$this->set_pagination_args(
			array(
				'total_items' => $this->total,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $this->total / $per_page ),
			)
		);
	}

	/**
	 * Stats strip + CSV export above the table.
	 *
	 * @param string $which Top or bottom.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$stats  = Gabochie_Crm_Purchases::order_stats();
		$people = count( $stats );
		$spend  = 0.0;
		$orders = 0;
		foreach ( $stats as $s ) {
			$spend  += (float) $s['spend'];
			$orders += (int) $s['orders'];
		}
		$currency = $people > 0 ? reset( $stats )['currency'] : '';

		$export_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'       => Gabochie_Crm_Admin::PAGE,
					'tab'        => 'customers',
					'export_csv' => 1,
				),
				admin_url( 'admin.php' )
			),
			'bulk-contacts'
		);

		?>
		<div class="gab-crm-stats">
			<span class="gab-crm-stat"><strong><?php echo esc_html( number_format_i18n( $people ) ); ?></strong> <?php esc_html_e( 'customers', 'gabochie-crm' ); ?></span>
			<span class="gab-crm-stat"><strong><?php echo esc_html( number_format_i18n( $orders ) ); ?></strong> <?php esc_html_e( 'completed orders', 'gabochie-crm' ); ?></span>
			<span class="gab-crm-stat"><strong><?php echo esc_html( trim( $currency . ' ' . number_format_i18n( $spend, 2 ) ) ); ?></strong> <?php esc_html_e( 'lifetime revenue', 'gabochie-crm' ); ?></span>
			<span class="gab-crm-stat gab-crm-stat-note"><?php esc_html_e( 'Counts completed orders only. Buyers are not added to the newsletter.', 'gabochie-crm' ); ?></span>
			<span class="gab-crm-stat-actions">
				<a class="button button-secondary" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export CSV', 'gabochie-crm' ); ?></a>
				<a class="button" href="<?php echo esc_url( $this->list_url() ); ?>"><?php esc_html_e( 'Reset', 'gabochie-crm' ); ?></a>
			</span>
		</div>
		<?php
	}

	/**
	 * Keep the Customers tab active on search + paging URLs.
	 *
	 * @param string $text     Search button label.
	 * @param string $input_id Search input id.
	 */
	public function search_box( $text, $input_id ) {
		$s = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<form method="get" class="gab-crm-search">
			<input type="hidden" name="page" value="<?php echo esc_attr( Gabochie_Crm_Admin::PAGE ); ?>" />
			<input type="hidden" name="tab" value="customers" />
			<p class="search-box">
				<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?></label>
				<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php echo esc_attr( $s ); ?>" />
				<?php submit_button( $text, '', 'search-btn', false ); ?>
			</p>
		</form>
		<?php
	}
}