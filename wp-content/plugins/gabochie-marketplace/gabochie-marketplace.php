<?php
/**
 * Plugin Name:       Gabochie Marketplace
 * Plugin URI:        https://gabochiedesign.com
 * Description:       Property + development marketplace for Ghana: curated listings with AI-generated property intelligence reports, enquiry funnel to the CRM + Command Center.
 * Version:           1.0.0
 * Author:            Gabochie Design
 * Text Domain:       gabochie-marketplace
 * Domain Path:       /languages
 * Requires PHP:      7.4
 *
 * @package Gabochie_Marketplace
 */

defined( 'ABSPATH' ) || exit;

define( 'GABOCHIE_MARKETPLACE_VERSION', '1.0.0' );
define( 'GABOCHIE_MARKETPLACE_FILE', __FILE__ );
define( 'GABOCHIE_MARKETPLACE_PATH', plugin_dir_path( __FILE__ ) );

require_once GABOCHIE_MARKETPLACE_PATH . 'includes/class-gabochie-marketplace.php';
require_once GABOCHIE_MARKETPLACE_PATH . 'includes/class-gabochie-marketplace-ai.php';
require_once GABOCHIE_MARKETPLACE_PATH . 'includes/class-gabochie-marketplace-list-table.php';
require_once GABOCHIE_MARKETPLACE_PATH . 'includes/class-gabochie-marketplace-admin.php';
require_once GABOCHIE_MARKETPLACE_PATH . 'includes/class-gabochie-marketplace-enquiry.php';
require_once GABOCHIE_MARKETPLACE_PATH . 'includes/class-gabochie-marketplace-cli.php';

/**
 * Activation: seed taxonomies + sample listings + schedule the intelligence
 * refresh + create the /marketplace/ page (all idempotent).
 */
function gmp_activate() {
	$core = Gabochie_Marketplace::instance();
	$core->register_cpt();
	$core->maybe_install();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'gmp_activate' );

/**
 * Wire the singletons.
 */
add_action(
	'plugins_loaded',
	static function () {
		Gabochie_Marketplace::instance();
		Gabochie_Marketplace_AI::instance();
		Gabochie_Marketplace_Admin::instance();
		Gabochie_Marketplace_Enquiry::instance();
		if ( defined( 'WP_CLI' ) ) {
			Gabochie_Marketplace_Cli::register();
		}
	},
	20
);
