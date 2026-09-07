<?php
/**
 * Gabochie CRM — contacts list table.
 *
 * WP_List_Table subclass rendering the gab_contacts rows in wp-admin with
 * search, filters, pagination, bulk actions (status, tags, delete, export).
 *
 * @package Gabochie_Crm
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Contacts list table.
 */
class Gabochie_Crm_List_Table extends WP_List_Table {


	/**
	 * Active admin tab ('' = Contacts, 'newsletter' = Newsletter list).
	 *
	 * @var string
	 */
	public $tab = '';

	/**
	 * Status filter applied when the request carries none.
	 *
	 * @var string
	 */
	public $default_status = '';

	/**
	 * Constructor.
	 *
	 * @param array $args {
	 *                    Optional view configuration.
	 *
	 * @type string $tab            Admin sub-tab shown alongside this table.
	 * @type string $default_status Status filter that applies without a request value.
	 * }
	 */
	public function __construct( array $args = array() ) {
		parent::__construct(
			array(
				'singular' => 'contact',
				'plural'   => 'contacts',
				'ajax'     => false,
			)
		);
		$this->tab            = isset( $args['tab'] ) ? sanitize_key( (string) $args['tab'] ) : '';
		$this->default_status = isset( $args['default_status'] ) ? sanitize_key( (string) $args['default_status'] ) : '';
	}

	/**
	 * Base admin URL for this list (keeps the active tab when set).
	 *
	 * @return string
	 */
	private function list_url() {
		return '' !== $this->tab
			? admin_url( 'admin.php?page=gabochie-crm&tab=' . urlencode( $this->tab ) ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode -- query value, safe here.
			: admin_url( 'admin.php?page=gabochie-crm' );
	}

	/**
	 * Column defaults.
	 *
	 * @param  array  $item        Row.
	 * @param  string $column_name Column key.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		$value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
		return esc_html( (string) $value );
	}

	/**
	 * Checkbox column for bulk actions.
	 *
	 * @param  array $item Row.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="contact_ids[]" value="%d" />',
			(int) $item['id']
		);
	}

	/**
	 * Email column: primary row link + row actions.
	 *
	 * @param  array $item Row.
	 * @return string
	 */
	public function column_email( $item ) {
		$id    = (int) $item['id'];
		$email = esc_html( $item['email'] );

		$actions           = array();
		$actions['view']   = sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( array( 'view' => $id ), wp_get_referer() ? remove_query_arg( array( 'view', 'edit' ) ) : $this->list_url() ) ),
			esc_html__( 'View', 'gabochie-crm' )
		);
		$actions['edit']   = sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( array( 'edit' => $id ), $this->list_url() ) ),
			esc_html__( 'Edit', 'gabochie-crm' )
		);
		$actions['delete'] = sprintf(
			'<a class="submitdelete" href="%s">%s</a>',
			esc_url(
				add_query_arg(
					array(
						'action'   => 'gab_crm_delete',
						'contact'  => $id,
						'_wpnonce' => wp_create_nonce( 'gab_crm_delete_' . $id ),
					),
					$this->list_url()
				)
			),
			esc_html__( 'Delete', 'gabochie-crm' )
		);

		return '<strong>' . $email . '</strong>' . $this->row_actions( $actions );
	}

	/**
	 * Tags column: clickable pills.
	 *
	 * @param  array $item Row.
	 * @return string
	 */
	public function column_tags( $item ) {
		$tags = isset( $item['tags'] ) ? (string) $item['tags'] : '';
		if ( '' === $tags ) {
			return '&mdash;';
		}
		$out   = array();
		$parts = explode( ',', $tags );
		foreach ( $parts as $t ) {
			$t = trim( $t );
			if ( '' === $t ) {
				continue;
			}
			$url   = add_query_arg(
				array( 'tag' => rawurlencode( $t ) ),
				$this->list_url()
			);
			$out[] = '<a class="gab-crm-tag" href="' . esc_url( $url ) . '" title="Filter by this tag">' . esc_html( $t ) . '</a>';
		}
		return implode( ' ', $out );
	}

	/**
	 * Status column with a colored badge.
	 *
	 * @param  array $item Row.
	 * @return string
	 */
	public function column_status( $item ) {
		$status = isset( $item['status'] ) ? (string) $item['status'] : '';
		$label  = strtoupper( $status );
		return '<span class="gab-crm-status gab-crm-status--' . esc_attr( $status ) . '">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'              => '<input type="checkbox" />',
			'id'              => 'ID',
			'email'           => __( 'Email', 'gabochie-crm' ),
			'first_name'      => __( 'First name', 'gabochie-crm' ),
			'last_name'       => __( 'Last name', 'gabochie-crm' ),
			'status'          => __( 'Status', 'gabochie-crm' ),
			'source'          => __( 'Source', 'gabochie-crm' ),
			'list_key'        => __( 'List', 'gabochie-crm' ),
			'tags'            => __( 'Tags', 'gabochie-crm' ),
			'last_engaged_at' => __( 'Last engaged', 'gabochie-crm' ),
			'created_at'      => __( 'Created', 'gabochie-crm' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'id'              => array( 'id', true ),
			'email'           => array( 'email', false ),
			'status'          => array( 'status', false ),
			'source'          => array( 'source', false ),
			'created_at'      => array( 'created_at', false ),
			'last_engaged_at' => array( 'last_engaged_at', false ),
		);
	}

	/**
	 * Bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'set_subscribed'   => __( 'Set status: Subscribed', 'gabochie-crm' ),
			'set_unsubscribed' => __( 'Set status: Unsubscribed', 'gabochie-crm' ),
			'set_pending'      => __( 'Set status: Pending', 'gabochie-crm' ),
			'add_tags'         => __( 'Add tags', 'gabochie-crm' ),
			'remove_tags'      => __( 'Remove tags', 'gabochie-crm' ),
			'export_selected'  => __( 'Export selected', 'gabochie-crm' ),
			'delete'           => __( 'Delete', 'gabochie-crm' ),
		);
	}

	/**
	 * Prepare items.
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = $this->get_items_per_page( 'gab_crm_contacts_per_page', 25 );

		$orderby   = isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'id';
		$raw_order = isset( $_REQUEST['order'] ) ? sanitize_key( wp_unslash( $_REQUEST['order'] ) ) : 'desc';
		$order     = ( 'asc' === $raw_order ) ? 'ASC' : 'DESC';

		$args = array(
			'search'   => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '',
			'status'   => isset( $_REQUEST['status'] ) ? sanitize_key( wp_unslash( $_REQUEST['status'] ) ) : $this->default_status,
			'source'   => isset( $_REQUEST['source'] ) ? sanitize_key( wp_unslash( $_REQUEST['source'] ) ) : '',
			'list_key' => isset( $_REQUEST['list_key'] ) ? sanitize_key( wp_unslash( $_REQUEST['list_key'] ) ) : '',
			'tag'      => isset( $_REQUEST['tag'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tag'] ) ) : '',
			'orderby'  => $orderby,
			'order'    => $order,
			'per_page' => $per_page,
			'page'     => $this->get_pagenum(),
		);

		$result = Gabochie_Crm::search_contacts( $args );

		$this->items = $result['items'];

		$this->set_pagination_args(
			array(
				'total_items' => (int) $result['total'],
				'total_pages' => (int) ceil( $result['total'] / max( 1, $per_page ) ),
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Render extra table navigation: filters dropdowns + export button.
	 *
	 * @param string $which Which nav bar (top/bottom).
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		$current_status = isset( $_REQUEST['status'] ) ? sanitize_key( wp_unslash( $_REQUEST['status'] ) ) : $this->default_status;
		$current_source = isset( $_REQUEST['source'] ) ? sanitize_key( wp_unslash( $_REQUEST['source'] ) ) : '';
		$current_list   = isset( $_REQUEST['list_key'] ) ? sanitize_key( wp_unslash( $_REQUEST['list_key'] ) ) : '';
		$current_tag    = isset( $_REQUEST['tag'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tag'] ) ) : '';
		?>
		<div class="alignleft actions">
			<select name="status">
				<option value=""><?php esc_html_e( 'All statuses', 'gabochie-crm' ); ?></option>
				<option value="subscribed" <?php selected( $current_status, 'subscribed' ); ?>><?php esc_html_e( 'Subscribed', 'gabochie-crm' ); ?></option>
				<option value="unsubscribed" <?php selected( $current_status, 'unsubscribed' ); ?>><?php esc_html_e( 'Unsubscribed', 'gabochie-crm' ); ?></option>
				<option value="pending" <?php selected( $current_status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'gabochie-crm' ); ?></option>
			</select>
			<select name="source">
				<option value=""><?php esc_html_e( 'All sources', 'gabochie-crm' ); ?></option>
		<?php foreach ( Gabochie_Crm::get_unique_sources() as $src ) : ?>
					<option value="<?php echo esc_attr( $src ); ?>" <?php selected( $current_source, $src ); ?>><?php echo esc_html( $src ); ?></option>
		<?php endforeach; ?>
			</select>
			<select name="list_key">
				<option value=""><?php esc_html_e( 'All lists', 'gabochie-crm' ); ?></option>
		<?php foreach ( Gabochie_Crm::get_unique_list_keys() as $lk ) : ?>
					<option value="<?php echo esc_attr( $lk ); ?>" <?php selected( $current_list, $lk ); ?>><?php echo esc_html( $lk ); ?></option>
		<?php endforeach; ?>
			</select>
		<?php
		if ( '' !== $current_tag ) {
			?>
				<input type="hidden" name="tag" value="<?php echo esc_attr( $current_tag ); ?>">
			<?php
		}
		?>
		<?php submit_button( __( 'Filter', 'gabochie-crm' ), '', 'filter_action', false ); ?>
		<?php
		submit_button(
			__( 'Export CSV', 'gabochie-crm' ),
			'',
			'export_csv',
			false,
			array( 'id' => 'gab-crm-export' )
		);
		?>
		<?php if ( '' !== $current_tag ) : ?>
				<a class="button" href="<?php echo esc_url( $this->list_url() ); ?>"><?php esc_html_e( 'Clear tag filter', 'gabochie-crm' ); ?></a>
		<?php endif; ?>
			<span class="gab-crm-bulk-tags">
				<label for="gab-crm-bulk-tags"><?php esc_html_e( 'Tags:', 'gabochie-crm' ); ?></label>
				<input type="text" id="gab-crm-bulk-tags" name="bulk_tags" value="" placeholder="<?php esc_attr_e( 'e.g. hot-lead, source:gadata', 'gabochie-crm' ); ?>" style="width:220px">
			</span>
		</div>
		<?php
	}

	/**
	 * Search box (core behaviour, plus keeps the active admin tab).
	 *
	 * @param string $text     Button label.
	 * @param string $input_id Search field id.
	 */
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$input_id = $input_id . '-search-input';
		?>
		<form class="search-box wp-clearfix" method="get">
			<input type="hidden" name="page" value="gabochie-crm">
			<?php
			if ( '' !== $this->tab ) {
				echo '<input type="hidden" name="tab" value="' . esc_attr( $this->tab ) . '">';
			}
			?>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s"
				value="<?php _admin_search_query(); ?>" placeholder="<?php echo esc_attr( $text ); ?>" />
			<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
		</form>
		<?php
	}
}