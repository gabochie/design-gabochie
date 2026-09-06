<?php
/**
 * Gabochie Cold Outreach — importer.
 *
 * CSV → Gabochie CRM (audience) + local tracking table (send state).
 * Exposes a `wp gabochie-co import` CLI command with dry-run support.
 *
 * Expected CSV columns (any order): name|biz, phone, email, website,
 * category, region, country. A `lite` mode inserts only the contact table.
 *
 * @package Gabochie_Cold_Outreach
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'gabochie-co', 'Gabochie_Co_Importer' );
}

/**
 * CSV importer + Gabochie CRM sync.
 */
class Gabochie_Co_Importer {

	/** @var string */
	const LIST_SLUG = 'leadhub-cold';

	/** @var string */
	const LIST_TITLE = 'Cold Outreach (gadata)';

	/**
	 * wp gabochie-co import <file> [--dry-run] [--list=<slug>] [--limit=<n>]
	 *
	 * @param array $args Positional args.
	 * @param array $assoc Named args.
	 */
	public function import( $args, $assoc ) {
		$file = isset( $args[0] ) ? $args[0] : '';
		if ( '' === $file || ! is_file( $file ) ) {
			WP_CLI::error( "CSV not found: {$file}" );
		}

		$dry   = isset( $assoc['dry-run'] ) && $assoc['dry-run'];
		$limit = isset( $assoc['limit'] ) && (int) $assoc['limit'] > 0 ? (int) $assoc['limit'] : 50;
		$list  = isset( $assoc['list'] ) && '' !== $assoc['list'] ? sanitize_key( $assoc['list'] ) : self::LIST_SLUG;

		Gabochie_Co_Db::install();
		$crm_ready = class_exists( 'Gabochie_Crm' );

		$rows = $this->parse_csv( $file );
		$rows = array_slice( $rows, 0, $limit );

		$imported        = 0;
		$failed          = 0;
		$crm_ok          = 0;
		$channel_map     = array();
		$route_map       = array();
		$referral_source = 'gadata';

		foreach ( $rows as $r ) {
			$segment = Gabochie_Co_Router::classify( $r['category'] ?? '' );
			$channel = Gabochie_Co_Router::channel(
				array(
					'phone' => $r['phone'] ?? '',
					'email' => $r['email'] ?? '',
				)
			);

			// Gabochie CRM sync (skip in dry-run).
			if ( ! $dry && $crm_ready && is_email( $r['email'] ?? '' ) ) {
				if ( $this->sync_crm( $r, $list ) ) {
					++$crm_ok;
				}
			}

			$referral_source = sanitize_key( $r['referral_source'] ?? 'gadata' );

			$contact_id = $dry ? 0 : Gabochie_Co_Db::upsert_contact(
				array(
					'email'           => $r['email'] ?? '',
					'phone'           => Gabochie_Co_Router::normalize_phone( $r['phone'] ?? '' ),
					'biz'             => $r['name'] ?? ( $r['biz'] ?? '' ),
					'category'        => $r['category'] ?? '',
					'region'          => $r['region'] ?? '',
					'country'         => $r['country'] ?? '',
					'website'         => $r['website'] ?? '',
					'channel'         => 'skip' === $channel ? 'skip' : $channel,
					'segment'         => $segment,
					'referral_source' => $referral_source,
					'source_note'     => $r['source_note'] ?? '',
				)
			);

			// Route (client vs affiliate) via segmentation rules.
			$rule                        = Gabochie_Co_Router::route(
				array(
					'id'       => $contact_id,
					'segment'  => $segment,
					'category' => $r['category'] ?? '',
				)
			);
			$route_map[ $rule['route'] ] = ( $route_map[ $rule['route'] ] ?? 0 ) + 1;

			// Surface the routing decision to the Command Center (SDR/affiliate).
			if ( ! $dry ) {
				do_action(
					'gabochie_co_routed',
					array(
						'biz'   => $r['name'] ?? ( $r['biz'] ?? '' ),
						'email' => $r['email'] ?? '',
						'phone' => $r['phone'] ?? '',
					),
					$rule['route']
				);
			}

			// Convert routed-affiliate rows into invite records when source is a referral.
			if ( ! $dry && $contact_id && 'affiliate' === $rule['route'] ) {
				Gabochie_Co_Db::upsert_affiliate(
					array(
						'contact_id' => $contact_id,
						'name'       => $r['name'] ?? ( $r['biz'] ?? '' ),
						'email'      => $r['email'] ?? '',
						'phone'      => Gabochie_Co_Router::normalize_phone( $r['phone'] ?? '' ),
						'region'     => $r['region'] ?? '',
						'channel'    => 'skip' === $channel ? '' : $channel,
					)
				);
			}

			$channel_map[ $channel ] = ( $channel_map[ $channel ] ?? 0 ) + 1;
			if ( $dry || $contact_id ) {
				++$imported;
			} else {
				++$failed;
			}
		}

		$summary = array(
			'file'            => $file,
			'dry_run'         => (bool) $dry,
			'limit'           => $limit,
			'imported'        => $imported,
			'failed'          => $failed,
			'crm_ok'          => $crm_ok,
			'channels'        => $channel_map,
			'routes'          => $route_map,
			'referral_source' => $referral_source,
		);
		do_action( 'gabochie_co_wave_started', (int) $imported, $dry ? 'dry-run' : 'live' );
		WP_CLI::success( json_encode( $summary, JSON_PRETTY_PRINT ) );
	}

	/**
	 * wp gabochie-co requeue [--channel=wa] [--limit=<n>]
	 *
	 * Flip `deferred` rows back to `imported` so a later wave can reach them.
	 * Once the channel override changes (e.g. WhatsApp goes live), run this to
	 * let the scheduler pick those contacts up again.
	 *
	 * @param array $args Positional args.
	 * @param array $assoc Named args.
	 */
	public function requeue( $args, $assoc ) {
		$channel = isset( $assoc['channel'] ) ? sanitize_key( $assoc['channel'] ) : '';
		$limit   = isset( $assoc['limit'] ) && (int) $assoc['limit'] > 0 ? (int) $assoc['limit'] : 500;

		Gabochie_Co_Db::install();
		global $wpdb;
		$table = Gabochie_Co_Db::table( Gabochie_Co_Db::TABLE );

		$where = 'status = %s';
		$args2 = array( 'deferred' );
		if ( '' !== $channel ) {
			$where  .= ' AND channel = %s';
			$args2[] = $channel;
		}
		$where  .= ' ORDER BY id ASC LIMIT %d';
		$args2[] = max( 1, (int) $limit );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$rows = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . $table . ' WHERE ' . $where, $args2 ) );
		if ( empty( $rows ) ) {
			WP_CLI::success( 'No deferred rows to requeue.' );
			return;
		}
		$n = 0;
		foreach ( $rows as $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ok = $wpdb->update( $table, array( 'status' => 'imported' ), array( 'id' => (int) $id ) );
			if ( false !== $ok ) {
				++$n;
			}
		}
		WP_CLI::success( "Requeued {$n} deferred contact(s)." );
	}

	/**
	 * Parse a CSV into associative rows (header-driven).
	 *
	 * @param string $file Path.
	 * @return array
	 */
	protected function parse_csv( $file ) {
		$out    = array();
		$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $handle ) {
			return $out;
		}
		$header = fgetcsv( $handle );
		if ( false === $header ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			return $out;
		}
		$header = array_map( 'strtolower', $header );
		while ( ( $row = fgetcsv( $handle ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition
			$assoc = array();
			foreach ( $header as $i => $col ) {
				$assoc[ $col ] = isset( $row[ $i ] ) ? $row[ $i ] : '';
			}
			$out[] = $assoc;
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return $out;
	}

	/**
	 * Sync a row to the Gabochie CRM as a cold-outreach contact.
	 *
	 * @param array  $r    Contact row.
	 * @param string $list List key.
	 * @return bool
	 */
	protected function sync_crm( array $r, $list ) {
		if ( ! class_exists( 'Gabochie_Crm' ) ) {
			return false;
		}
		$id = Gabochie_Crm::upsert_contact(
			$r['email'] ?? '',
			array(
				'status'       => 'subscribed',
				'contact_type' => 'subscriber',
				'list_key'     => '' !== $list ? $list : self::LIST_SLUG,
				'tags'         => array( 'source:gadata', 'cold' ),
				'first_name'   => sanitize_text_field( $r['name'] ?? '' ),
				'phone'        => sanitize_text_field( $r['phone'] ?? '' ),
			)
		);
		return $id > 0;
	}
}
