<?php
/**
 * Gabochie Market Research — WP-CLI commands.
 *
 * `wp gmr collect|memo|proposals|status|delete`
 *
 * @package Gabochie_Market_Research
 */

defined( 'ABSPATH' ) || exit;

/**
 * CLI commands.
 */
class Gabochie_Market_Research_Cli {


	/**
	 * @return void
	 */
	public static function register() {
		$cli = new self();
		\WP_CLI::add_command( 'gmr collect', array( $cli, 'collect' ) );
		\WP_CLI::add_command( 'gmr memo', array( $cli, 'memo' ) );
		\WP_CLI::add_command( 'gmr proposals', array( $cli, 'proposals' ) );
		\WP_CLI::add_command( 'gmr status', array( $cli, 'status' ) );
		\WP_CLI::add_command( 'gmr delete', array( $cli, 'delete' ) );
	}

	/**
	 * Collect demand signals and rebuild the niche matrix.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gmr collect
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function collect( $args, $assoc_args ) {
		$matrix = Gabochie_Market_Research::instance()->run_collect();
		WP_CLI::success( 'Collection complete: ' . count( $matrix ) . ' niches scored.' );
	}

	/**
	 * Generate the weekly LLM market memo (upserts pending proposals).
	 *
	 * ## EXAMPLES
	 *
	 *     wp gmr memo
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function memo( $args, $assoc_args ) {
		$result = Gabochie_Market_Research::instance()->run_memo();
		if ( isset( $result['error'] ) ) {
			WP_CLI::error( (string) $result['error'] );
		}
		WP_CLI::success( 'Memo generated; upserted ' . (int) $result['upserted'] . ' new proposals.' );
	}

	/**
	 * List proposals.
	 *
	 * ## OPTIONS
	 *
	 * [--status=<status>]
	 * : Filter by status (pending|approved|rejected|deferred|all). Default pending.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gmr proposals --status=all
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function proposals( $args, $assoc_args ) {
		global $wpdb;
		$filter = isset( $assoc_args['status'] ) ? sanitize_key( (string) $assoc_args['status'] ) : 'pending';
		$table  = Gabochie_Market_Research::table( Gabochie_Market_Research::TABLE_PROPOSALS );
		$where  = 'all' === $filter ? '' : $wpdb->prepare( 'WHERE status = %s', $filter );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( "SELECT * FROM {$table} {$where} ORDER BY opportunity_score DESC LIMIT 200", ARRAY_A );

		$this->table_out(
			array( 'ID', 'Status', 'Score', 'Niche', 'Title', 'Product', 'Price', 'Created' ),
			$rows
		);
	}

	/**
	 * Set a proposal's status (approve/reject/defer/pending).
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Proposal ID.
	 *
	 * <status>
	 * : approved|rejected|deferred|pending.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gmr status 7 approved
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function status( $args, $assoc_args ) {
		$id     = isset( $args[0] ) ? (int) $args[0] : 0;
		$status = isset( $args[1] ) ? sanitize_key( (string) $args[1] ) : '';
		if ( $id <= 0 || '' === $status ) {
			WP_CLI::error( 'Usage: wp gmr status <id> <approved|rejected|deferred|pending>' );
		}
		if ( ! Gabochie_Market_Research::instance()->set_proposal_status( $id, $status ) ) {
			WP_CLI::error( "Could not set proposal {$id} to {$status}." );
		}
		WP_CLI::success( "Proposal {$id} → {$status}." );
	}

	/**
	 * Delete a proposal.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Proposal ID.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gmr delete 7
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function delete( $args, $assoc_args ) {
		$id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( $id <= 0 ) {
			WP_CLI::error( 'Usage: wp gmr delete <id>' );
		}
		if ( ! Gabochie_Market_Research::instance()->delete_proposal( $id ) ) {
			WP_CLI::error( "Proposal {$id} not found." );
		}
		WP_CLI::success( "Deleted proposal {$id}." );
	}

	/**
	 * Render an aligned table via WP_CLI::log (no external table utility).
	 *
	 * @param array $headers
	 * @param array $rows
	 */
	private function table_out( array $headers, array $rows ) {
		$data = array( $headers );
		foreach ( $rows as $r ) {
			$data[] = array(
				(int) $r['id'],
				(string) $r['status'],
				(int) $r['opportunity_score'],
				(string) $r['niche'],
				(string) $r['title'],
				(string) $r['product_type'],
				(string) $r['price_hint'],
				(string) $r['created_at'],
			);
		}
		$widths = array();
		foreach ( $data[0] as $i => $h ) {
			$w = strlen( (string) $h );
			foreach ( $data as $d ) {
				$w = max( $w, strlen( (string) $d[ $i ] ) );
			}
			$widths[ $i ] = $w;
		}
		foreach ( $data as $row ) {
			$cells = array();
			foreach ( $row as $i => $cell ) {
				$cells[] = str_pad( (string) $cell, (int) $widths[ $i ] );
			}
			WP_CLI::log( '| ' . implode( ' | ', $cells ) . ' |' );
		}
	}
}
