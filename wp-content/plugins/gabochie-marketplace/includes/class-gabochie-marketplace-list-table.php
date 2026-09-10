<?php
/**
 * Gabochie Marketplace — listings manager.
 *
 * WP_List_Table subclass showing all gd_property listings with type/status/
 * price/location and intelligence status, plus publish/intelligence/feature/
 * delete actions.
 *
 * @package Gabochie_Marketplace
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Listings list table.
 */
class Gabochie_Marketplace_List_Table extends WP_List_Table {


	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'property',
				'plural'   => 'properties',
				'ajax'     => false,
			)
		);
	}

	/**
	 * @return string
	 */
	private function list_url() {
		return admin_url( 'admin.php?page=gabochie-marketplace' );
	}

	/**
	 * @param  array  $item
	 * @param  string $column_name
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		$value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
		return esc_html( (string) $value );
	}

	/**
	 * @param array $item @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="property_ids[]" value="%d" />', (int) $item['id'] );
	}

	/**
	 * @param  array $item
	 * @return string
	 */
	public function column_title( $item ) {
		$id     = (int) $item['id'];
		$status = (string) $item['post_status'];
		$link   = $this->list_url();

		$title = sprintf(
			'<a class="row-title" href="%s">%s</a>%s',
			esc_url( admin_url( 'post.php?post=' . $id . '&action=edit' ) ),
			esc_html( $item['title'] ),
			'draft' === $status ? ' <span class="post-state">— Draft</span>' : ''
		);

		$actions                 = array();
		$actions['edit']         = '<a href="' . esc_url( admin_url( 'post.php?post=' . $id . '&action=edit' ) ) . '">' . esc_html__( 'Edit', 'gabochie-marketplace' ) . '</a>';
		$actions['publish']      = '<a href="' . esc_url(
			add_query_arg(
				array(
					'action'   => 'gmp_publish',
					'item'     => $id,
					'_wpnonce' => wp_create_nonce( 'gmp_item_' . $id ),
				),
				$link
			)
		) . '">' . ( 'draft' === $status ? esc_html__( 'Publish', 'gabochie-marketplace' ) : esc_html__( 'Re-draft', 'gabochie-marketplace' ) ) . '</a>';
		$actions['intelligence'] = '<a href="' . esc_url(
			add_query_arg(
				array(
					'action'   => 'gmp_intelligence',
					'item'     => $id,
					'_wpnonce' => wp_create_nonce( 'gmp_item_' . $id ),
				),
				$link
			)
		) . '">' . esc_html__( 'Generate Intelligence', 'gabochie-marketplace' ) . '</a>';
		$actions['feature']      = '<a href="' . esc_url(
			add_query_arg(
				array(
					'action'   => 'gmp_feature',
					'item'     => $id,
					'_wpnonce' => wp_create_nonce( 'gmp_item_' . $id ),
				),
				$link
			)
		) . '">' . ( '1' === (string) get_post_meta( $id, Gabochie_Marketplace::META_FEATURED, true ) ? esc_html__( 'Unfeature', 'gabochie-marketplace' ) : esc_html__( 'Feature', 'gabochie-marketplace' ) ) . '</a>';
		$actions['view']         = '<a href="' . esc_url( get_permalink( $id ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View', 'gabochie-marketplace' ) . '</a>';
		$actions['delete']       = '<a class="submitdelete" href="' . esc_url(
			add_query_arg(
				array(
					'action'   => 'gmp_delete',
					'item'     => $id,
					'_wpnonce' => wp_create_nonce( 'gmp_item_' . $id ),
				),
				$link
			)
		) . '">' . esc_html__( 'Delete', 'gabochie-marketplace' ) . '</a>';

		return $title . $this->row_actions( $actions );
	}

	/**
	 * @param  array $item
	 * @return string
	 */
	public function column_type( $item ) {
		$slug = (string) ( $item['_type_slug'] ?? '' );
		return $slug ? esc_html( ucwords( str_replace( '-', ' ', $slug ) ) ) : '—';
	}

	/**
	 * @param  array $item
	 * @return string
	 */
	public function column_status( $item ) {
		$slug = (string) ( $item['_status_slug'] ?? '' );
		return $slug ? esc_html( ucwords( str_replace( '-', ' ', $slug ) ) ) : '—';
	}

	/**
	 * @param  array $item
	 * @return string
	 */
	public function column_price( $item ) {
		$price = (float) get_post_meta( (int) $item['id'], Gabochie_Marketplace::META_PRICE, true );
		return $price > 0 ? esc_html( 'GHS ' . number_format( $price ) ) : '—';
	}

	/**
	 * @param  array $item
	 * @return string
	 */
	public function column_intelligence( $item ) {
		$at = (string) get_post_meta( (int) $item['id'], Gabochie_Marketplace::META_INTEL_AT, true );
		if ( '' !== $at ) {
			return '<span style="color:#0a7d33;">Generated</span>';
		}
		return '<span style="color:#b86a00;">Missing</span>';
	}

	/**
	 * @param  array $item
	 * @return string
	 */
	public function column_featured( $item ) {
		return '1' === (string) get_post_meta( (int) $item['id'], Gabochie_Marketplace::META_FEATURED, true ) ? '★' : '';
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'           => '<input type="checkbox" />',
			'title'        => __( 'Listing', 'gabochie-marketplace' ),
			'type'         => __( 'Type', 'gabochie-marketplace' ),
			'status'       => __( 'Status', 'gabochie-marketplace' ),
			'price'        => __( 'Price', 'gabochie-marketplace' ),
			'intelligence' => __( 'Intelligence', 'gabochie-marketplace' ),
			'featured'     => __( '★', 'gabochie-marketplace' ),
			'date'         => __( 'Added', 'gabochie-marketplace' ),
		);
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'title' => array( 'title', false ),
			'date'  => array( 'date', true ),
		);
	}

	/**
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'gmp_bulk_publish'      => __( 'Publish', 'gabochie-marketplace' ),
			'gmp_bulk_draft'        => __( 'Re-draft', 'gabochie-marketplace' ),
			'gmp_bulk_intelligence' => __( 'Generate Intelligence', 'gabochie-marketplace' ),
			'gmp_bulk_delete'       => __( 'Delete', 'gabochie-marketplace' ),
		);
	}

	/**
	 * @return void
	 */
	public function prepare_items() {
		$per_page = 25;
		$current  = (int) $this->get_pagenum();
		$orderby  = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'date';
		$order    = isset( $_GET['order'] ) && 'asc' === sanitize_key( wp_unslash( $_GET['order'] ) ) ? 'ASC' : 'DESC';

		$args = array(
			'post_type'      => Gabochie_Marketplace::CPT,
			'post_status'    => 'any',
			'posts_per_page' => $per_page,
			'paged'          => $current,
			'orderby'        => $orderby,
			'order'          => $order,
		);

		if ( ! empty( $_GET['type'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => Gabochie_Marketplace::TAX_TYPE,
				'field'    => 'slug',
				'terms'    => sanitize_key( (string) $_GET['type'] ),
			);
		}
		if ( ! empty( $_GET['intel'] ) && 'missing' === $_GET['intel'] ) {
			$args['meta_query'][] = array(
				'key'     => Gabochie_Marketplace::META_INTEL,
				'compare' => 'NOT EXISTS',
			);
		}

		$q = new WP_Query( $args );

		$items = array();
		foreach ( $q->posts as $p ) {
			$items[] = array(
				'id'           => $p->ID,
				'title'        => $p->post_title,
				'post_status'  => $p->post_status,
				'_type_slug'   => Gabochie_Marketplace::get_type_slug( $p->ID ),
				'_status_slug' => Gabochie_Marketplace::get_status_slug( $p->ID ),
			);
		}

		$this->items = $items;

		$this->set_pagination_args(
			array(
				'total_items' => (int) $q->found_posts,
				'per_page'    => $per_page,
				'total_pages' => (int) $q->max_num_pages,
			)
		);
	}

	/**
	 * Extra toolbar: type filter + missing-intelligence filter.
	 *
	 * @param string $which
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		$base    = $this->list_url();
		$types   = Gabochie_Marketplace::type_terms();
		$current = isset( $_GET['type'] ) ? sanitize_key( (string) $_GET['type'] ) : '';
		echo '<div class="alignleft actions">';
		echo '<select name="type">';
		echo '<option value="">' . esc_html__( 'All types', 'gabochie-marketplace' ) . '</option>';
		foreach ( $types as $term ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $term->slug ),
				selected( $current, $term->slug, false ),
				esc_html( $term->name )
			);
		}
		echo '</select>';
		printf(
			'<select name="intel"><option value="">%s</option><option value="missing" %s>%s</option></select>',
			esc_html__( 'All intelligence', 'gabochie-marketplace' ),
			( isset( $_GET['intel'] ) && 'missing' === $_GET['intel'] ) ? 'selected' : '',
			esc_html__( 'Missing only', 'gabochie-marketplace' )
		);
		submit_button( __( 'Filter', 'gabochie-marketplace' ), '', 'filter_action', false );
		echo '</div>';
		echo '<input type="hidden" name="page" value="gabochie-marketplace" />';
		echo '<input type="hidden" name="tab" value="listings" />';

		$link = add_query_arg(
			array(
				'action'   => 'gmp_seed',
				'_wpnonce' => wp_create_nonce( 'gmp_seed' ),
			),
			$base
		);
		echo '<div style="float:right;"><a href="' . esc_url( $link ) . '" class="button">' . esc_html__( 'Import sample listings', 'gabochie-marketplace' ) . '</a></div>';
	}
}
