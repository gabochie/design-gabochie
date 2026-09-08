<?php
/**
 * Gabochie News Portal — WP-CLI commands.
 *
 * @package Gabochie_News_Portal
 */

defined( 'ABSPATH' ) || exit;

/**
 * WP-CLI command class.
 */
class Gabochie_News_Cli {


	/**
	 * Register the `wp gnp` commands (call only under WP-CLI).
	 */
	public static function register() {
		$cli = new self();
		WP_CLI::add_command( 'gnp fetch', array( $cli, 'fetch' ) );
		WP_CLI::add_command( 'gnp take', array( $cli, 'take' ) );
		WP_CLI::add_command( 'gnp digest', array( $cli, 'digest' ) );
		WP_CLI::add_command( 'gnp sources', array( $cli, 'sources' ) );
		WP_CLI::add_command( 'gnp source', array( $cli, 'source' ) );
	}

	/**
	 * Ingest every enabled RSS source into the draft queue.
	 *
	 * ## EXAMPLES
	 *     wp gnp fetch
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function fetch( $args, $assoc_args ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		$portal = Gabochie_News_Portal::instance();
		$result = $portal->fetch_all();

		$added = 0;
		foreach ( $result as $slug => $r ) {
			if ( ! empty( $r['error'] ) ) {
				WP_CLI::warning( $slug . ': ' . $r['error'] );
			} else {
				WP_CLI::log(
					sprintf(
						'%s: %d new, %d dup, %d skipped (out of %d items)',
						$slug,
						(int) $r['added'],
						(int) $r['dup'],
						(int) $r['hidden'],
						(int) $r['items']
					)
				);
			}
			$added += (int) $r['added'];
		}
		WP_CLI::success( sprintf( '%d new draft(s) ingested into the review queue.', $added ) );
	}

	/**
	 * Generate (or regenerate) the Gabochie take for a news item.
	 *
	 * ## OPTIONS
	 * <id>
	 * : News post ID.
	 *
	 * [--approve]
	 * : Immediately approve the take (default: leave as draft).
	 *
	 * ## EXAMPLES
	 *     wp gnp take 123
	 *     wp gnp take 123 --approve
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function take( $args, $assoc_args ) {
		$id   = (int) ( isset( $args[0] ) ? $args[0] : 0 );
		$post = get_post( $id );
		if ( ! $post || Gabochie_News_Portal::CPT !== $post->post_type ) {
			WP_CLI::error( 'No news item with ID ' . $id . '.' );
		}
		$take = Gabochie_News_AI::instance()->draft_take( $id );
		if ( false === $take ) {
			WP_CLI::error( 'Take generation failed — check output of `wp gnp sources` / the errors option.' );
		}
		if ( ! empty( $assoc_args['approve'] ) ) {
			Gabochie_News_AI::instance()->set_take_status( $id, Gabochie_News_Portal::TAKE_APPROVED );
		}
		WP_CLI::success(
			'Take drafted (provider: ' . Gabochie_News_AI::instance()->last_source() . ').'
			. ( ! empty( $assoc_args['approve'] ) ? ' Approved.' : '' )
		);
		WP_CLI::log( $take );
	}

	/**
	 * Send the daily news digest now (reads the newsletter send chain).
	 *
	 * ## OPTIONS
	 * [--dry-run]
	 * : Print what would be sent.
	 *
	 * ## EXAMPLES
	 *     wp gnp digest --dry-run
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function digest( $args, $assoc_args ) {
		if ( ! class_exists( 'Gabochie_Newsletter' ) ) {
			WP_CLI::error( 'gabochie-newsletter v1.4+ is required for the digest.' );
		}
		$res = Gabochie_Newsletter::instance()->send_digest( ! empty( $assoc_args['dry-run'] ) );
		WP_CLI::success(
			sprintf(
				'Digest to %d subscriber(s) (%d news item(s)); sent=%s errors=%d.',
				(int) ( isset( $res['subscribers'] ) ? $res['subscribers'] : 0 ),
				(int) ( isset( $res['items'] ) ? $res['items'] : 0 ),
				! empty( $res['sent'] ) ? 'yes' : 'no',
				(int) ( isset( $res['errors'] ) ? $res['errors'] : 0 )
			)
		);
	}

	/**
	 * List source registry entries.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function sources( $args, $assoc_args ) {
		$sources = Gabochie_News_Portal::instance()->sources();
		$rows    = array_map(
			static function ( $s ) {
				return array(
					'slug'    => (string) $s['slug'],
					'name'    => (string) $s['name'],
					'url'     => (string) $s['url'],
					'enabled' => '1' === (string) $s['enabled'] ? 'yes' : 'no',
					'take'    => ! empty( $s['ai_take'] ) ? 'yes' : 'no',
				);
			},
			array_values( $sources )
		);
		if ( empty( $rows ) ) {
			WP_CLI::log( 'No sources registered yet.' );
			return;
		}
		$headers = array( 'slug', 'name', 'url', 'enabled', 'take' );
		$widths  = array();
		foreach ( $headers as $h ) {
			$widths[ $h ] = max( strlen( $h ), max( array_map( 'strlen', wp_list_pluck( $rows, $h ) ) ) );
		}
		WP_CLI::log(
			implode(
				'  ',
				array_map(
					static function ( $h ) use ( $widths ) {
						return str_pad( $h, $widths[ $h ] );
					},
					$headers
				)
			)
		);
		foreach ( $rows as $row ) {
			WP_CLI::log(
				implode(
					'  ',
					array_map(
						static function ( $h ) use ( $row, $widths ) {
							return str_pad( (string) $row[ $h ], $widths[ $h ] );
						},
						$headers
					)
				)
			);
		}
	}

	/**
	 * Add or delete a source.
	 *
	 * ## OPTIONS
	 * <action>
	 * : add|delete.
	 *
	 * [--slug=<slug>]
	 * [--name=<name>]
	 * [--url=<url>]
	 * [--trusted]
	 * [--no-take]
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function source( $args, $assoc_args ) {
		$action  = isset( $args[0] ) ? $args[0] : '';
		$portal  = Gabochie_News_Portal::instance();
		$sources = $portal->sources();

		if ( 'add' === $action ) {
			$slug = isset( $assoc_args['slug'] ) ? sanitize_key( $assoc_args['slug'] ) : '';
			$name = isset( $assoc_args['name'] ) ? sanitize_text_field( $assoc_args['name'] ) : '';
			$url  = isset( $assoc_args['url'] ) ? esc_url_raw( $assoc_args['url'] ) : '';
			if ( '' === $slug || '' === $name || '' === $url ) {
				WP_CLI::error( '--slug, --name and --url are required.' );
			}
			$sources[ $slug ] = array(
				'slug'    => $slug,
				'name'    => $name,
				'url'     => $url,
				'trusted' => ! empty( $assoc_args['trusted'] ),
				'ai_take' => ! isset( $assoc_args['no-take'] ),
				'enabled' => '1',
				'added'   => current_time( 'mysql' ),
			);
			$portal->save_sources( $sources );
			WP_CLI::success( 'Source "' . $slug . '" added.' );
			return;
		}
		if ( 'delete' === $action ) {
			$slug = isset( $assoc_args['slug'] ) ? sanitize_key( $assoc_args['slug'] ) : '';
			if ( ! isset( $sources[ $slug ] ) ) {
				WP_CLI::error( 'Unknown source "' . $slug . '".' );
			}
			unset( $sources[ $slug ] );
			$portal->save_sources( $sources );
			WP_CLI::success( 'Source "' . $slug . '" deleted.' );
			return;
		}
		WP_CLI::error( 'Action must be add or delete.' );
	}
}
