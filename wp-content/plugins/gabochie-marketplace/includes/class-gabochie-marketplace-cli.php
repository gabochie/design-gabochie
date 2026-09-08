<?php
/**
 * Gabochie Marketplace — WP-CLI commands.
 *
 * @package Gabochie_Marketplace
 */

defined('ABSPATH') || exit;

if (! defined('WP_CLI') ) {
    return;
}

/**
 * Marketplace CLI.
 */
class Gabochie_Marketplace_Cli
{

    /**
     * Register the `gmp` command group with instance command callables.
     *
     * @return void
     */
    public static function register()
    {
        $cli = new self();
        \WP_CLI::add_command('gmp list', array( $cli, 'list_items' ));
        \WP_CLI::add_command('gmp create', array( $cli, 'create' ));
        \WP_CLI::add_command('gmp intelligence', array( $cli, 'intelligence' ));
        \WP_CLI::add_command('gmp seed', array( $cli, 'seed' ));
        \WP_CLI::add_command('gmp stats', array( $cli, 'stats' ));
    }

    /**
     * List all listings.
     *
     * <id>
     *   Optional listing ID to filter to.
     *
     * ## EXAMPLES
     *
     *     wp gmp list
     *     wp gmp list 12
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function list_items( $args, $assoc_args )  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    {
        $query = array(
        'post_type'      => Gabochie_Marketplace::CPT,
        'post_status'    => 'any',
        'posts_per_page' => 100,
        'orderby'        => 'date',
        'order'          => 'DESC',
        );
        if (! empty($args[0]) ) {
            $query['p'] = (int) $args[0];
        }
        $rows = array();
        foreach ( get_posts($query) as $p ) {
            $rows[] = array(
            'ID'     => $p->ID,
            'title'  => $p->post_title,
            'type'   => Gabochie_Marketplace::get_type_slug($p->ID),
            'status' => Gabochie_Marketplace::get_status_slug($p->ID),
            'price'  => 'GHS ' . number_format((float) get_post_meta($p->ID, Gabochie_Marketplace::META_PRICE, true)),
            'intel'  => Gabochie_Marketplace::has_intelligence($p->ID) ? 'yes' : 'no',
            );
        }
        \WP_CLI\Utils\format_items('table', $rows, array( 'ID', 'title', 'type', 'status', 'price', 'intel' ));
    }

    /**
     * Create a listing.
     *
     * ## OPTIONS
     *
     * --title=<title>
     * --type=<type>
     * --status=<status>
     * --price=<price>
     * --location=<location>
     * [--size=<m2>]
     * [--bedrooms=<n>]
     * [--bathrooms=<n>]
     * [--content=<html>]
     *
     * ## EXAMPLES
     *
     *     wp gmp create --title="2-Acre Plot" --type=land --status=for-sale --price=1200000 --location="East Legon" --size=8094
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function create( $args, $assoc_args )  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    {
        $title    = isset($assoc_args['title']) ? sanitize_text_field($assoc_args['title']) : '';
        $type     = isset($assoc_args['type']) ? sanitize_key($assoc_args['type']) : '';
        $status   = isset($assoc_args['status']) ? sanitize_key($assoc_args['status']) : '';
        $price    = isset($assoc_args['price']) ? (float) $assoc_args['price'] : 0;
        $location = isset($assoc_args['location']) ? sanitize_text_field($assoc_args['location']) : '';

        if ('' === $title || '' === $type || '' === $status ) {
            \WP_CLI::error('title, type and status are required.');
        }
        if (! term_exists($type, Gabochie_Marketplace::TAX_TYPE) ) {
            \WP_CLI::error("Unknown type: {$type}");
        }
        if (! term_exists($status, Gabochie_Marketplace::TAX_STATUS) ) {
            \WP_CLI::error("Unknown status: {$status}");
        }

        $id = wp_insert_post(
            array(
            'post_type'    => Gabochie_Marketplace::CPT,
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_content' => isset($assoc_args['content']) ? wp_kses_post($assoc_args['content']) : '',
            )
        );
        if (is_wp_error($id) ) {
            \WP_CLI::error($id->get_error_message());
        }
        $id = (int) $id;

        update_post_meta($id, Gabochie_Marketplace::META_PRICE, $price);
        update_post_meta($id, Gabochie_Marketplace::META_LOCATION, $location);
        update_post_meta($id, Gabochie_Marketplace::META_SIZE_M2, isset($assoc_args['size']) ? (float) $assoc_args['size'] : 0);
        update_post_meta($id, Gabochie_Marketplace::META_BEDROOMS, isset($assoc_args['bedrooms']) ? (int) $assoc_args['bedrooms'] : 0);
        update_post_meta($id, Gabochie_Marketplace::META_BATHROOMS, isset($assoc_args['bathrooms']) ? (int) $assoc_args['bathrooms'] : 0);
        wp_set_object_terms($id, $type, Gabochie_Marketplace::TAX_TYPE);
        wp_set_object_terms($id, $status, Gabochie_Marketplace::TAX_STATUS);

        \WP_CLI::success("Created listing {$id}");
    }

    /**
     * Generate an intelligence report for a listing.
     *
     * <id>
     *   Listing post ID.
     *
     * ## OPTIONS
     *
     * <id>
     *
     * ## EXAMPLES
     *
     *     wp gmp intelligence 12
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function intelligence( $args, $assoc_args )  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    {
        if (empty($args[0]) ) {
            \WP_CLI::error('Listing ID is required.');
        }
        $id = (int) $args[0];
        $ok = Gabochie_Marketplace_AI::instance()->generate_report($id);
        if (! $ok ) {
            \WP_CLI::error('Failed to generate intelligence report.');
        }
        \WP_CLI::success('Intelligence report generated (provider: ' . Gabochie_Marketplace_AI::instance()->last_source() . ').');
    }

    /**
     * Import the shipped sample listings.
     *
     * ## EXAMPLES
     *
     *     wp gmp seed
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function seed( $args, $assoc_args )  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    {
        $count = Gabochie_Marketplace::instance()->seed_sample_listings();
        \WP_CLI::success("Seeded {$count} sample listing(s).");
    }

    /**
     * Show listing counts by type/status.
     *
     * ## EXAMPLES
     *
     *     wp gmp stats
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function stats( $args, $assoc_args )  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    {
        $types = array();
        foreach ( Gabochie_Marketplace::type_terms() as $term ) {
            $types[] = array( 'type' => $term->name, 'count' => $term->count );
        }
        \WP_CLI\Utils\format_items('table', $types, array( 'type', 'count' ));
    }
}
