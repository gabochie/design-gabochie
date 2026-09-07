<?php
/**
 * Gabochie CRM — CSV export + WordPress personal-data integration.
 *
 * Provides filtered CSV export (admin action + REST endpoint) and registers
 * the gab_contacts table into the WordPress GDPR personal-data exporter and
 * eraser, so "Export my data" / "Erase my data" requests honour CRM rows.
 *
 * @package Gabochie_Crm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Export helpers + GDPR registration.
 */
class Gabochie_Crm_Export {


	/**
 * @var array<string> CSV column order.
*/
	const COLUMNS = array(
		'email',
		'first_name',
		'last_name',
		'phone',
		'status',
		'contact_type',
		'list_key',
		'source',
		'tags',
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'created_at',
		'updated_at',
		'last_engaged_at',
	);

	/**
	 * Wire the hooks.
	 */
	public static function register() {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_route' ) );
	}

	/**
	 * Register the GDPR exporter.
	 *
	 * @param  array $exporters Registered exporters.
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters['gabochie-crm'] = array(
			'exporter_friendly_name' => __( 'Gabochie CRM Contacts', 'gabochie-crm' ),
			'callback'               => array( __CLASS__, 'gdpr_export' ),
		);
		return $exporters;
	}

	/**
	 * Register the GDPR eraser.
	 *
	 * @param  array $erasers Registered erasers.
	 * @return array
	 */
	public static function register_eraser( $erasers ) {
		$erasers['gabochie-crm'] = array(
			'eraser_friendly_name' => __( 'Gabochie CRM Contacts', 'gabochie-crm' ),
			'callback'             => array( __CLASS__, 'gdpr_erase' ),
		);
		return $erasers;
	}

	/**
	 * GDPR export callback — emit every gab_contacts row for one email.
	 *
	 * @param  string $email_address Requested address.
	 * @param  int    $page          Page for chunking.
	 * @return array{data:array,done:bool}
	 */
	public static function gdpr_export( $email_address, $page = 1 ) {
		$page = max( 1, (int) $page );
		global $wpdb;
		if ( ! Gabochie_Crm::ready() ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}
		$rows         = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE email = %s LIMIT %d OFFSET %d',
				Gabochie_Crm::table(),
				sanitize_email( strtolower( trim( $email_address ) ) ),
				50,
				( $page - 1 ) * 50
			),
			ARRAY_A
		);
		$export_items = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$group_id    = 'gabochie-crm';
				$group_label = __( 'Gabochie CRM Contacts', 'gabochie-crm' );
				$item_data   = array();
				foreach ( self::COLUMNS as $col ) {
					$item_data[] = array(
						'name'  => $col,
						'value' => isset( $row[ $col ] ) ? (string) $row[ $col ] : '',
					);
				}
				$export_items[] = array(
					'group_id'    => $group_id,
					'group_label' => $group_label,
					'item_id'     => 'gabochie-crm-' . (int) $row['id'],
					'data'        => $item_data,
				);
			}
		}
		return array(
			'data' => $export_items,
			'done' => true,
		);
	}

	/**
	 * GDPR erasure callback — hard-delete the matching gab_contacts row.
	 *
	 * @param  string $email_address Requested address.
	 * @param  int    $page          Page for chunking.
	 * @return array{items_removed:int,items_retained:bool,messages:array,done:bool}
	 */
	public static function gdpr_erase( $email_address, $page = 1 ) {
		$email = sanitize_email( strtolower( trim( $email_address ) ) );
		global $wpdb;
		if ( '' === $email || ! is_email( $email ) || ! Gabochie_Crm::ready() ) {
			return array(
				'items_removed'  => 0,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}
		$deleted = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( 'DELETE FROM %i WHERE email = %s', Gabochie_Crm::table(), $email )
		);
		$removed = is_int( $deleted ) && $deleted > 0 ? $deleted : 0;
		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => $removed > 0
			? array( sprintf( __( 'Deleted %d contact row(s) from the Gabochie CRM store.', 'gabochie-crm' ), $removed ) )
			: array(),
			'done'           => true,
		);
	}

	/**
	 * REST route: GET /wp-json/gabochie-crm/v1/export?… — a CSV file of the
	 * current filter set. Admins only.
	 */
	public static function register_rest_route() {
		register_rest_route(
			'gabochie-crm/v1',
			'/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_export' ),
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * REST export callback — stream a CSV to the caller.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function rest_export( $request ) {
		$rows = self::rows_for_filters(
			array(
				'search'   => sanitize_text_field( (string) $request->get_param( 'search' ) ),
				'status'   => sanitize_key( (string) $request->get_param( 'status' ) ),
				'source'   => sanitize_key( (string) $request->get_param( 'source' ) ),
				'list_key' => sanitize_key( (string) $request->get_param( 'list_key' ) ),
				'tag'      => sanitize_text_field( (string) $request->get_param( 'tag' ) ),
			)
		);
		self::stream_csv( $rows );
	}

	/**
	 * Sniff the current admin filter set out of the query string and stream.
	 *
	 * @return never
	 */
	public static function admin_export() {
		self::stream_csv(
			self::rows_for_filters(
				array(
					'search'   => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
					'status'   => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
					'source'   => isset( $_GET['source'] ) ? sanitize_key( wp_unslash( $_GET['source'] ) ) : '',
					'list_key' => isset( $_GET['list_key'] ) ? sanitize_key( wp_unslash( $_GET['list_key'] ) ) : '',
					'tag'      => isset( $_GET['tag'] ) ? sanitize_text_field( wp_unslash( $_GET['tag'] ) ) : '',
				)
			)
		);
	}

	/**
	 * Stream an array of gab_contacts rows as a CSV download. Always exits.
	 *
	 * @param  array $rows Rows (flat associative arrays).
	 * @return never
	 */
	public static function stream_csv( array $rows ) {
		$filename = 'gabochie-crm-contacts-' . gmdate( 'Ymd-His' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );

		$fh = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- stream wrapper, correct here.
     // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fwrite( $fh, "\xEF\xBB\xBF" ); // UTF-8 BOM so Excel reads accents correctly.
		fputcsv( $fh, self::COLUMNS ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv

		foreach ( $rows as $row ) {
			$line = array();
			foreach ( self::COLUMNS as $col ) {
				$line[] = isset( $row[ $col ] ) ? (string) $row[ $col ] : '';
			}
			fputcsv( $fh, $line ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
		}

     // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $fh );
		exit;
	}

	/**
	 * Fetch raw rows for a filter set (max 5k, id ASC).
	 *
	 * @param  array $filters search/status/source/list_key/tag.
	 * @return array
	 */
	public static function rows_for_filters( array $filters ) {
		$result = Gabochie_Crm::search_contacts(
			array_merge(
				$filters,
				array(
					'per_page' => 5000,
					'page'     => 1,
					'orderby'  => 'id',
					'order'    => 'ASC',
				)
			)
		);
		return isset( $result['items'] ) ? $result['items'] : array();
	}

	/**
	 * Stream a CSV of specific contact ids.
	 *
	 * @param array $ids Contact row ids.
	 */
	public static function stream_contact_ids( array $ids ) {
		$ids  = array_values(
			array_filter(
				array_map( 'absint', $ids ),
				static function ( $id ) {
					return $id > 0;
				}
			)
		);
		$rows = array();
		foreach ( $ids as $id ) {
			$row = Gabochie_Crm::get_contact_by_id( $id );
			if ( $row ) {
				$rows[] = $row;
			}
		}
		self::stream_csv( $rows );
	}
}
