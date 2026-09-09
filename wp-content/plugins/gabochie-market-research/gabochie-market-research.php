<?php
/**
 * Plugin Name:       Gabochie Market Intelligence
 * Plugin URI:        https://gabochiedesign.com
 * Description:       Autonomous market research + human-in-the-loop approval inbox. Collects demand signals (site search, community questions, captured enquiries, content pulse, audience engagement), scores opportunity niches weekly, drafts a market memo via the shared AI chain, and holds proposed products/offers for sign-off.
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
 * Activation: create tables, schedule the collectors + memo cron, flush
 * rewrite rules (all idempotent).
 */
function gmr_activate() {
	Gabochie_Market_Research::instance()->maybe_install();
	flush_rewrite_rules();
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