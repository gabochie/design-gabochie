<?php
/**
 * Purchase capture: turn WooCommerce orders into CRM contacts.
 *
 * Wires `woocommerce_order_status_completed` to gabochie_crm so every settled
 * sale lands in the contact store as contact_type=customer, tagged `purchaser`
 * plus a per-order `order:<id>` marker.
 *
 * Design rules:
 *  - Completed only. Processing/refunded orders are NOT conversions, so the
 *    command-center "Won" numbers stay honest.
 *  - Money never lives in tags. normalize_tags() strips every character outside
 *    [a-z0-9_:-], which would silently eat the decimal point in an amount, and
 *    it would also let two sources disagree about revenue. Order totals are
 *    read back from WooCommerce, which is the source of truth.
 *  - Newsletter status is never touched for an existing contact, and a brand
 *    new buyer is inserted as `pending`, never `subscribed` - a purchase is not
 *    a marketing opt-in. upsert_contact() defaults new rows to subscribed, so
 *    passing status explicitly here is load-bearing, not decoration.
 *  - Attribution is preserved: source is only written when the contact is new.
 *
 * @package Gabochie_Crm
 */

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce purchase -> CRM bridge.
 */
class Gabochie_Crm_Purchases {

	/**
	 * Tag marking a contact as a paying customer.
	 */
	const TAG_BUYER = 'purchaser';

	/**
	 * contact_type used for buyers.
	 */
	const TYPE_CUSTOMER = 'customer';

	/**
	 * source recorded for contacts this class creates from scratch.
	 */
	const SOURCE = 'woocommerce';

	/**
	 * Hook the capture in. No-op when WooCommerce is absent.
	 */
	public static function register() {
		if ( ! self::woo_active() ) {
			return;
		}
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'on_order_completed' ), 20, 1 );
	}

	/**
	 * Is WooCommerce loaded and is our own table ready?
	 *
	 * @return bool
	 */
	public static function woo_active() {
		return class_exists( 'WooCommerce' ) && class_exists( 'Gabochie_Crm' ) && Gabochie_Crm::ready();
	}

	/**
	 * Capture one completed order as a customer.
	 *
	 * @param int $order_id WooCommerce order id.
	 */
	public static function on_order_completed( $order_id ) {
		$order = self::get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$email = self::normalize_email( $order->get_billing_email() );
		if ( '' === $email ) {
			return;
		}

		// Refunded / cancelled never reach this hook, but guard the total so a
		// zero-value or fully-discounted order cannot register as a sale.
		$total = (float) $order->get_total();
		if ( $total <= 0 ) {
			return;
		}

		$marker = 'order:' . (int) $order->get_id();
		$before = Gabochie_Crm::get_contact_by_email( $email );
		$is_new = empty( $before );

		// Idempotency: Woo can fire this again (bulk status change, retry), and
		// the order marker is the only durable record that we already counted it.
		if ( $before && self::has_tag( (string) $before['tags'], $marker ) ) {
			return;
		}

		$data = array(
			'contact_type' => self::TYPE_CUSTOMER,
			'tags'         => self::merge_tags(
				$before ? (string) $before['tags'] : '',
				array( self::TAG_BUYER, $marker )
			),
		);

		// Only pass fields we actually have: upsert_contact() overwrites any
		// field present in $data, so an empty string would erase stored data.
		foreach ( array(
			'first_name' => 'get_billing_first_name',
			'last_name'  => 'get_billing_last_name',
			'phone'      => 'get_billing_phone',
		) as $field => $getter ) {
			$value = sanitize_text_field( (string) $order->{$getter}() );
			if ( '' !== $value ) {
				$data[ $field ] = $value;
			}
		}

		if ( $is_new ) {
			// Load-bearing: without this a new contact defaults to subscribed and
			// starts receiving broadcast email.
			$data['status'] = Gabochie_Crm::STATUS_PENDING;
			$data['source'] = self::SOURCE;
		}

		$id = Gabochie_Crm::upsert_contact( $email, $data );
		if ( ! $id ) {
			return;
		}

		self::log( 'captured', $order->get_id(), $email, $total );
	}

	/**
	 * Canonical buyer email key.
	 *
	 * Woo stores whatever case the customer typed, but the CRM lowercases every
	 * address on read (upsert_contact()/get_contact_by_email()). Aggregating on
	 * the raw value would therefore split one buyer into two rows - one carrying
	 * the order totals and one tagged-but-empty - so every key must be lowercase.
	 *
	 * @param string $email Raw email.
	 * @return string Lowercased, sanitized email, or '' when unusable.
	 */
	private static function normalize_email( $email ) {
		$email = strtolower( trim( sanitize_email( (string) $email ) ) );
		return ( '' !== $email && is_email( $email ) ) ? $email : '';
	}

	/**
	 * Does a stored tag CSV already contain this exact tag?
	 *
	 * @param string $tags Stored CSV.
	 * @param string $tag  Tag to look for.
	 * @return bool
	 */
	private static function has_tag( $tags, $tag ) {
		if ( '' === $tags ) {
			return false;
		}
		$needle = strtolower( $tag );
		foreach ( explode( ',', $tags ) as $t ) {
			if ( strtolower( trim( $t ) ) === $needle ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Union existing tags with new ones, order-preserving.
	 *
	 * @param string $existing Stored CSV.
	 * @param array  $add     Tags to add.
	 * @return string CSV.
	 */
	private static function merge_tags( $existing, array $add ) {
		$merged = array();
		if ( '' !== trim( (string) $existing ) ) {
			$merged = array_map( 'trim', explode( ',', (string) $existing ) );
		}
		foreach ( $add as $tag ) {
			$merged[] = $tag;
		}
		return Gabochie_Crm::normalize_tags(
			array_values(
				array_filter(
					$merged,
					static function ( $t ) {
						return '' !== trim( (string) $t );
					}
				)
			)
		);
	}

	/**
	 * Aggregate completed orders per billing email.
	 *
	 * HPOS first (this install has it enabled), with a posts-table fallback so
	 * the tab still works if storage is ever switched back.
	 *
	 * @return array email => stats.
	 */
	public static function order_stats() {
		global $wpdb;

		$stats = array();
		$table = $wpdb->prefix . 'wc_orders';

		if ( self::table_exists( $table ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT billing_email, COUNT(*) AS orders, SUM(total_amount) AS spend, MIN(date_created_gmt) AS first_at, MAX(date_created_gmt) AS last_at, MAX(id) AS last_order, MAX(currency) AS currency
					 FROM %i WHERE status = %s AND type = %s AND billing_email <> %s
					 GROUP BY billing_email',
					$table,
					'wc-completed',
					'shop_order',
					''
				),
				ARRAY_A
			);
		} else {
			$rows = self::legacy_order_stats();
		}

		foreach ( (array) $rows as $r ) {
			$email = self::normalize_email( $r['billing_email'] );
			if ( '' === $email ) {
				continue;
			}
			$stats[ $email ] = array(
				'orders'     => (int) $r['orders'],
				'spend'      => (float) $r['spend'],
				'currency'   => (string) $r['currency'],
				'first_at'   => self::local_date( (string) $r['first_at'] ),
				'last_at'    => self::local_date( (string) $r['last_at'] ),
				'last_order' => (int) $r['last_order'],
			);
		}

		return $stats;
	}

	/**
	 * Completed-order aggregates for installs still storing orders as posts.
	 *
	 * @return array
	 */
	private static function legacy_order_stats() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
				'shop_order',
				'wc-completed'
			)
		);
		if ( ! $ids ) {
			return array();
		}

		// Legacy rows need WooCommerce itself to resolve totals and dates
		// reliably, and the volume is tiny, so read through the CRUD layer
		// instead of re-deriving money maths in SQL.
		$stats = array();
		foreach ( $ids as $id ) {
			$order = self::get_order( $id );
			if ( ! $order ) {
				continue;
			}
			$email = self::normalize_email( $order->get_billing_email() );
			if ( '' === $email ) {
				continue;
			}
			if ( ! isset( $stats[ $email ] ) ) {
				$stats[ $email ] = array(
					'orders'     => 0,
					'spend'      => 0.0,
					'currency'   => $order->get_currency(),
					'first_at'   => '',
					'last_at'    => '',
					'last_order' => 0,
				);
			}
			$created = (string) $order->get_date_created();
			++$stats[ $email ]['orders'];
			$stats[ $email ]['spend'] += (float) $order->get_total();
			if ( '' === $stats[ $email ]['first_at'] || $created < $stats[ $email ]['first_at'] ) {
				$stats[ $email ]['first_at'] = $created;
			}
			if ( $created > $stats[ $email ]['last_at'] ) {
				$stats[ $email ]['last_at']    = $created;
				$stats[ $email ]['last_order'] = (int) $order->get_id();
			}
		}

		return $stats;
	}

	/**
	 * Customer rows for the admin tab: CRM contacts joined to order totals.
	 *
	 * @param array $args search, per_page, page.
	 * @return array{total:int,items:array}
	 */
	public static function customers( array $args = array() ) {
		$per_page = isset( $args['per_page'] ) ? max( 1, (int) $args['per_page'] ) : 25;
		$page     = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
		$search   = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$needle   = '' !== $search ? strtolower( $search ) : '';

		// Completed orders are the source of truth for "is a customer": the
		// `purchaser` tag is only a marker we write, so a contact whose tag was
		// lost, or whose order total was zero, must still be listed.
		$rows = array();
		foreach ( self::order_stats() as $email => $stat ) {
			$rows[ $email ] = array(
				'email' => $email,
				'stat'  => $stat,
			);
		}

		// Union in anyone we tagged as a buyer but have no order row for, so the
		// tab never silently drops a paying customer.
		$tagged = Gabochie_Crm::search_contacts(
			array(
				'tag'      => self::TAG_BUYER,
				'per_page' => 200,
			)
		);
		foreach ( (array) $tagged['items'] as $contact ) {
			$email = (string) $contact['email'];
			if ( ! isset( $rows[ $email ] ) ) {
				$rows[ $email ] = array(
					'email' => $email,
					'stat'  => self::empty_stats(),
				);
			}
		}

		// Most recent activity first.
		uasort(
			$rows,
			static function ( $a, $b ) {
				return strcmp( (string) $b['stat']['last_at'], (string) $a['stat']['last_at'] );
			}
		);

		if ( '' !== $needle ) {
			$rows = array_filter(
				$rows,
				static function ( $row ) use ( $needle ) {
					$haystack = $row['email'];
					$contact  = Gabochie_Crm::get_contact_by_email( $row['email'] );
					if ( $contact ) {
						$haystack .= ' ' . $contact['first_name'] . ' ' . $contact['last_name'] . ' ' . $contact['phone'];
					}
					return false !== strpos( strtolower( $haystack ), $needle );
				}
			);
		}

		$total  = count( $rows );
		$slice  = array_slice( $rows, ( $page - 1 ) * $per_page, $per_page, true );
		$items  = array();
		foreach ( $slice as $email => $row ) {
			$contact = Gabochie_Crm::get_contact_by_email( $email );
			$base    = is_array( $contact ) ? $contact : array(
				'id'           => 0,
				'email'        => $email,
				'first_name'   => '',
				'last_name'    => '',
				'phone'        => '',
				'status'       => '',
				'source'       => '',
				'contact_type' => '',
				'tags'         => '',
				'created_at'   => '',
			);
			$stat              = $row['stat'];
			$base['stats']     = $stat;
			$base['order_count'] = $stat['orders'];
			$base['spend']     = $stat['spend'];
			$items[]           = $base;
		}

		return array(
			'total' => (int) $total,
			'items' => $items,
		);
	}

	/**
	 * Zeroed stat shape for a tagged buyer with no order row.
	 *
	 * @return array
	 */
	private static function empty_stats() {
		return array(
			'orders'     => 0,
			'spend'      => 0.0,
			'currency'   => '',
			'first_at'   => '',
			'last_at'    => '',
			'last_order' => 0,
		);
	}

	/**
	 * Flat customer rows for CSV export.
	 *
	 * @param string $search Optional search term.
	 * @return array
	 */
	public static function customers_for_export( $search = '' ) {
		$out  = array();
		$page = 1;
		do {
			$res           = self::customers(
				array(
					'search'   => $search,
					'per_page' => 200,
					'page'     => $page,
				)
			);
			$items         = $res['items'];
			$items_on_page = count( $items );
			foreach ( $items as $row ) {
				$out[] = $row;
			}
			++$page;
		} while ( $items_on_page > 0 && $page < 50 );

		return $out;
	}

	/**
	 * Load an order through WooCommerce, tolerating bad ids.
	 *
	 * @param int $order_id Order id.
	 * @return WC_Order|null
	 */
	private static function get_order( $order_id ) {
		$order_id = (int) $order_id;
		if ( $order_id <= 0 || ! function_exists( 'wc_get_order' ) ) {
			return null;
		}
		$order = wc_get_order( $order_id );
		return ( $order && is_a( $order, 'WC_Order' ) ) ? $order : null;
	}

	/**
	 * Convert a stored date to site-local display form.
	 *
	 * @param string $gmt GMT datetime.
	 * @return string
	 */
	private static function local_date( $gmt ) {
		$gmt = trim( (string) $gmt );
		if ( '' === $gmt || '0000-00-00 00:00:00' === $gmt ) {
			return '';
		}
		$local = get_date_from_gmt( $gmt );
		return ( false === $local || null === $local ) ? $gmt : (string) $local;
	}

	/**
	 * Does this table exist?
	 *
	 * @param string $table Table name.
	 * @return bool
	 */
	private static function table_exists( $table ) {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Emit a capture line for debugging only.
	 *
	 * Deliberately never writes a file: WP_CONTENT_DIR sits inside the webroot
	 * and only litespeed/debug/*.log is blocked by .htaccess, so a log holding
	 * buyer emails and order totals would be world-readable. Capture state is
	 * already durable in gab_contacts (the order: tags).
	 *
	 * @param string $action   Action name.
	 * @param int    $order_id Order id.
	 * @param string $email    Buyer email.
	 * @param float  $total    Order total.
	 */
	private static function log( $action, $order_id, $email, $total ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				sprintf(
					'[gabochie-crm] %s order=%d email=%s total=%.2f',
					$action,
					(int) $order_id,
					$email,
					(float) $total
				)
			);
		}
	}
}
