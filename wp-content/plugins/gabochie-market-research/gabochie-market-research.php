<?php
/**
 * Plugin Name:       Gabochie Market Research
 * Plugin URI:        https://gabochiedesign.com
 * Description:       Demand intelligence engine: ingests internal signals (search, community, RSS, engagement), scores niches, generates a weekly market memo with AI-ranked proposals, and provides an approval inbox for human-in-the-loop decisions.
 * Version:           1.0.0
 * Author:            Gabochie Design
 * Text Domain:       gabochie-market-research
 * Domain Path:       /languages
 * Requires PHP:      7.4
 *
 * @package Gabochie_Market_Research
 */

defined( 'ABSPATH' ) || exit;

define( 'GABOCHIE_RESEARCH_VERSION', '1.0.0' );
define( 'GABOCHIE_RESEARCH_FILE', __FILE__ );
define( 'GABOCHIE_RESEARCH_PATH', plugin_dir_path( __FILE__ ) );

require_once GABOCHIE_RESEARCH_PATH . 'includes/class-gabochie-market-research.php';
require_once GABOCHIE_RESEARCH_PATH . 'includes/class-gabochie-market-research-ai.php';
require_once GABOCHIE_RESEARCH_PATH . 'includes/class-gabochie-market-research-admin.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once GABOCHIE_RESEARCH_PATH . 'includes/class-gabochie-market-research-cli.php';
}

/**
 * Activation: create tables + schedule crons (idempotent).
 */
function gmr_activate() {
	Gabochie_Market_Research::instance()->ensure_schema();
	Gabochie_Market_Research::instance()->ensure_search_log_table();
	Gabochie_Market_Research::instance()->ensure_crons();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'gmr_activate' );

/**
 * Wire singletons on plugins_loaded.
 */
add_action(
	'plugins_loaded',
	static function () {
		Gabochie_Market_Research::instance();
		Gabochie_Market_Research_Admin::instance();
		if ( defined( 'WP_CLI' ) ) {
			Gabochie_Market_Research_Cli::register();
		}
	},
	20
);
