<?php
/**
 * Plugin Name:       Gabochie Market Research
 * Plugin URI:        https://gabochiedesign.com
 * Description:       Demand intelligence for the Gabochie revenue engine: collects first-party demand signals (site search, community questions, enquiries, feed pulse), scores niche opportunities, and drafts a weekly market memo with approval-inbox proposals for the human in the loop.
 * Version:           1.0.0
 * Author:            Gabochie Design
 * Text Domain:       gabochie-market-research
 * Domain Path:       /languages
 * Requires PHP:      7.4
 *
 * @package Gabochie_Market_Research
 */

defined( 'ABSPATH' ) || exit;

define( 'GABOCHIE_MARKET_RESEARCH_VERSION', '1.0.0' );
define( 'GABOCHIE_MARKET_RESEARCH_FILE', __FILE__ );
define( 'GABOCHIE_MARKET_RESEARCH_PATH', plugin_dir_path( __FILE__ ) );

require_once GABOCHIE_MARKET_RESEARCH_PATH . 'includes/class-gabochie-market-research.php';
require_once GABOCHIE_MARKET_RESEARCH_PATH . 'includes/class-gabochie-market-research-ai.php';
require_once GABOCHIE_MARKET_RESEARCH_PATH . 'includes/class-gabochie-market-research-admin.php';
require_once GABOCHIE_MARKET_RESEARCH_PATH . 'includes/class-gabochie-market-research-cli.php';

/**
 * Activation: create tables + schedule the daily signal collector and the
 * weekly market-memo cron (all idempotent).
 */
function gmr_activate() {
	$core = Gabochie_Market_Research::instance();
	$core->ensure_schema();
	$core->ensure_crons();
}
register_activation_hook( __FILE__, 'gmr_activate' );

/**
 * Wire the singletons.
 */
add_action(
	'plugins_loaded',
	static function () {
		Gabochie_Market_Research::instance();
		Gabochie_Market_Research_AI::instance();
		Gabochie_Market_Research_Admin::instance();
		if ( defined( 'WP_CLI' ) ) {
			Gabochie_Market_Research_Cli::register();
		}
	},
	20
);