<?php
/**
 * Plugin Name:       Gabochie Market Research
 * Plugin URI:        https://gabochiedesign.com
 * Description:       Demand intelligence + human-in-the-loop approval inbox. Collects real demand signals (community questions, site search, enquiries, news pulse, content gaps), scores opportunity niches, drafts a weekly market memo via the LLM chain, and stages product/niche proposals for human sign-off before anything ships.
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