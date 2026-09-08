<?php
/**
 * Gabochie News Portal — editorial review queue.
 *
 * WP_List_Table subclass showing all ingested news items (status draft by
 * default) with publishing, take-generation and delete actions. This is the
 * only gate between "curated from a feed" and "live on /news/".
 *
 * @package Gabochie_News_Portal
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Review queue list table.
 */
class Gabochie_News_List_Table extends WP_List_Table {


	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'news_item',
				'plural'   => 'news_items',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Base admin URL for the queue.
	 *
	 * @return string
	 */
	private function list_url() {
		return admin_url( 'admin.php?page=gabochie-news-portal' );
	}

	/**
	 * @param  array  $item        Row.
	 * @param  string $column_name Column key.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		$value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
		return esc_html( (string) $value );
	}

	/**
	 * @param  array $item Row.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="news_ids[]" value="%d" />',
			(int) $item['id']
		);
	}

	/**
	 * Title column: row link + actions.
	 *
	 * @param  array $item Row.
	 * @return string
	 */
	public function column_title( $item ) {
		$id     = (int) $item['id'];
		$status = (string) $item['post_status'];
		$link   = $this->list_url();

		$actions            = array();
		$actions['edit']    = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'post.php?post=' . $id . '&action=edit' ) ),
			esc_html__( 'Edit', 'gabochie-news-portal' )
		);
		$actions['publish'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url(
				add_query_arg(
					array(
						'action'   => 'gnp_publish',
						'item'     => $id,
						'_wpnonce' => wp_create_nonce( 'gnp_item_' . $id ),
					),
					$link
				)
			),
			'draft' === $status ? esc_html__( 'Publish', 'gabochie-news-portal' ) : esc_html__( 'Re-draft', 'gabochie-news-portal' )
		);
		$actions['take']    = sprintf(
			'<a href="%s">%s</a>',
			esc_url(
				add_query_arg(
					array(
						'action'   => 'gnp_take',
						'item'     => $id,
						'_wpnonce' => wp_create_nonce( 'gnp_item_' . $id ),
					),
					$link
				)
			),
			esc_html__( 'Generate take', 'gabochie-news-portal' )
		);
		$actions['view']    = sprintf(
			'<a href="%s" target="_blank" rel="noopener">%s</a>',
			esc_url( get_permalink( $id ) ),
			esc_html__( 'View', 'gabochie-news-portal' )
		);
		$actions['delete']  = sprintf(
			'<a class="submitdelete" href="%s">%s</a>',
			esc_url(
				add_query_arg(
					array(
						'action'   => 'gnp_delete',
						'item'     => $id,
						'_wpnonce' => wp_create_nonce( 'gnp_item_' . $id ),
					),
					$link
				)
			),
			esc_html__( 'Delete', 'gabochie-news-portal' )
		);

		$status_badge = 'draft' === $status ? ' <span class="post-state">(' . esc_html__( 'draft', 'gabochie-news-portal' ) . ')</span>' : '';
		return '<strong>' . esc_html( (string) $item['title'] ) . '</strong>' . $status_badge . $this->row_actions( $actions );
	}

	/**
	 * @param  array $item Row.
	 * @return string
	 */
	public function column_source( $item ) {
		$name = isset( $item['source'] ) ? (string) $item['source'] : '';
		if ( '' === $name ) {
			return '&mdash;';
		}
		$url = add_query_arg( array( 'source' => rawurlencode( $name ) ), $this->list_url() );
		return '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
	}

	/**
	 * @param  array $item Row.
	 * @return string
	 */
	public function column_category( $item ) {
		$cat = isset( $item['category'] ) ? (string) $item['category'] : '';
		return '' === $cat ? '&mdash;' : esc_html( str_replace( '-', ' ', $cat ) );
	}

	/**
	 * @param  array $item Row.
	 * @return string
	 */
	public function column_excerpt( $item ) {
		$excerpt = isset( $item['excerpt'] ) ? (string) $item['excerpt'] : '';
		return wp_trim_words( $excerpt, 18, '…' );
	}

	/**
	 * @param  array $item Row.
	 * @return string
	 */
	public function column_take( $item ) {
		$status = isset( $item['take_status'] ) ? (string) $item['take_status'] : '';
		if ( '' === $status ) {
			return '&mdash;';
		}
		$map = array(
			Gabochie_News_Portal::TAKE_DRAFT    => array( 'Draft', 'notice-warning' ),
			Gabochie_News_Portal::TAKE_APPROVED => array( 'Approved', 'notice-success' ),
			Gabochie_News_Portal::TAKE_REJECTED => array( 'Rejected', '' ),
		);
		$row = isset( $map[ $status ] ) ? $map[ $status ] : array( $status, '' );
		return '<span class="' . esc_attr( $row[1] ) . '">' . esc_html( (string) $row[0] ) . '</span>';
	}

	/**
	 * @param  array $item Row.
	 * @return string
	 */
	public function column_fetched( $item ) {
		$d = isset( $item['fetched'] ) ? (string) $item['fetched'] : '';
		return '' === $d ? '&mdash;' : esc_html( $d );
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'       => '<input type="checkbox" />',
			'title'    => __( 'Headline', 'gabochie-news-portal' ),
			'source'   => __( 'Source', 'gabochie-news-portal' ),
			'category' => __( 'Category', 'gabochie-news-portal' ),
			'excerpt'  => __( 'Excerpt', 'gabochie-news-portal' ),
			'take'     => __( 'Take', 'gabochie-news-portal' ),
			'fetched'  => __( 'Ingested', 'gabochie-news-portal' ),
		);
	}

	/**
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'gnp_bulk_publish' => __( 'Publish', 'gabochie-news-portal' ),
			'gnp_bulk_take'    => __( 'Generate takes', 'gabochie-news-portal' ),
			'gnp_bulk_approve' => __( 'Approve takes', 'gabochie-news-portal' ),
			'gnp_bulk_delete'  => __( 'Delete', 'gabochie-news-portal' ),
		);
	}

	/**
	 * Query the review queue.
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page    = $this->get_items_per_page( 'gnp_queue_per_page', 25 );
		$search      = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$source      = isset( $_REQUEST['source'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['source'] ) ) : '';
		$cat         = isset( $_REQUEST['category'] ) ? sanitize_key( wp_unslash( $_REQUEST['category'] ) ) : '';
		$take_filter = isset( $_REQUEST['take'] ) ? sanitize_key( wp_unslash( $_REQUEST['take'] ) ) : '';
		$status      = isset( $_REQUEST['status'] ) ? sanitize_key( wp_unslash( $_REQUEST['status'] ) ) : 'draft';

		$args = array(
			'post_type'      => Gabochie_News_Portal::CPT,
			'post_status'    => array( 'draft', 'publish', 'pending' ),
			'posts_per_page' => $per_page,
			'paged'          => $this->get_pagenum(),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		if ( '' !== $status && 'all' !== $status ) {
			$args['post_status'] = $status;
		}
		if ( '' !== $search ) {
			$args['s'] = $search;
		}
		if ( '' !== $cat ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => Gabochie_News_Portal::TAX,
					'field'    => 'slug',
					'terms'    => $cat,
				),
			);
		}

		$query = new WP_Query( $args );
		$rows  = array();
		foreach ( $query->posts as $p ) {
			$terms    = get_the_terms( $p->ID, Gabochie_News_Portal::TAX );
			$cat_slug = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '';
			$rows[]   = array(
				'id'          => (int) $p->ID,
				'post_status' => $p->post_status,
				'title'       => $p->post_title,
				'source'      => (string) get_post_meta( $p->ID, Gabochie_News_Portal::META_SOURCE_TXT, true ),
				'category'    => $cat_slug,
				'excerpt'     => (string) $p->post_excerpt,
				'take_status' => (string) get_post_meta( $p->ID, Gabochie_News_Portal::META_TAKE_STAT, true ),
				'fetched'     => (string) get_post_meta( $p->ID, Gabochie_News_Portal::META_FETCHED, true ),
			);
		}

		$this->items = $rows;

		$this->set_pagination_args(
			array(
				'total_items' => (int) $query->found_posts,
				'total_pages' => (int) $query->max_num_pages,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Filter dropdowns above the queue.
	 *
	 * @param string $which Which nav bar (top/bottom).
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		$portal         = Gabochie_News_Portal::instance();
		$current_source = isset( $_REQUEST['source'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['source'] ) ) : '';
		$current_status = isset( $_REQUEST['status'] ) ? sanitize_key( wp_unslash( $_REQUEST['status'] ) ) : 'draft';
		$current_take   = isset( $_REQUEST['take'] ) ? sanitize_key( wp_unslash( $_REQUEST['take'] ) ) : '';
		?>
		<div class="alignleft actions">
			<select name="status">
				<option value="draft" <?php selected( $current_status, 'draft' ); ?>><?php esc_html_e( 'Queue (drafts)', 'gabochie-news-portal' ); ?></option>
				<option value="publish" <?php selected( $current_status, 'publish' ); ?>><?php esc_html_e( 'Published', 'gabochie-news-portal' ); ?></option>
				<option value="all" <?php selected( $current_status, 'all' ); ?>><?php esc_html_e( 'All', 'gabochie-news-portal' ); ?></option>
			</select>
			<select name="source">
				<option value=""><?php esc_html_e( 'All sources', 'gabochie-news-portal' ); ?></option>
			<?php foreach ( $portal->sources() as $src ) : ?>
					<option value="<?php echo esc_attr( $src['name'] ); ?>" <?php selected( $current_source, $src['name'] ); ?>><?php echo esc_html( $src['name'] ); ?></option>
			<?php endforeach; ?>
			</select>
			<select name="take">
				<option value=""><?php esc_html_e( 'Any take', 'gabochie-news-portal' ); ?></option>
				<option value="none" <?php selected( $current_take, 'none' ); ?>><?php esc_html_e( 'No take yet', 'gabochie-news-portal' ); ?></option>
				<option value="draft" <?php selected( $current_take, 'draft' ); ?>><?php esc_html_e( 'Take draft', 'gabochie-news-portal' ); ?></option>
				<option value="approved" <?php selected( $current_take, 'approved' ); ?>><?php esc_html_e( 'Take approved', 'gabochie-news-portal' ); ?></option>
			</select>
			<?php submit_button( __( 'Filter', 'gabochie-news-portal' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * Search box (core behaviour).
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
			<input type="hidden" name="page" value="gabochie-news-portal">
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s"
				value="<?php _admin_search_query(); ?>" placeholder="<?php echo esc_attr( $text ); ?>" />
			<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
		</form>
		<?php
	}
}