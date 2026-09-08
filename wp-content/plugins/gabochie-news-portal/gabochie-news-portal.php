<?php
/**
 * Plugin Name:       Gabochie News Portal
 * Plugin URI:        https://gabochiedesign.com
 * Description:       Curated AEC / built-environment / property news for Ghana with editorial "Gabochie take" briefs and a daily digest feeding the newsletter.
 * Version:           1.0.0
 * Author:            Gabochie Design
 * Text Domain:       gabochie-news-portal
 * Domain Path:       /languages
 * Requires PHP:      7.4
 *
 * @package Gabochie_News_Portal
 */

defined( 'ABSPATH' ) || exit;

define( 'GABOCHIE_NEWS_VERSION', '1.0.0' );
define( 'GABOCHIE_NEWS_FILE', __FILE__ );
define( 'GABOCHIE_NEWS_PATH', plugin_dir_path( __FILE__ ) );

require_once GABOCHIE_NEWS_PATH . 'includes/class-gabochie-news-portal.php';
require_once GABOCHIE_NEWS_PATH . 'includes/class-gabochie-news-ai.php';
require_once GABOCHIE_NEWS_PATH . 'includes/class-gabochie-news-list-table.php';
require_once GABOCHIE_NEWS_PATH . 'includes/class-gabochie-news-admin.php';
require_once GABOCHIE_NEWS_PATH . 'includes/class-gabochie-news-cli.php';

/**
 * Activation: seed sources + categories, schedule the daily fetch, create the
 * /news/ page (all idempotent).
 */
function gnp_activate() {
	Gabochie_News_Portal::instance()->maybe_install();
}
register_activation_hook( __FILE__, 'gnp_activate' );

/**
 * Wire the singletons.
 */
add_action(
	'plugins_loaded',
	static function () {
		Gabochie_News_Portal::instance();
		Gabochie_News_Admin::instance();
		if ( defined( 'WP_CLI' ) ) {
			Gabochie_News_Cli::register();
		}
	},
	20
);
