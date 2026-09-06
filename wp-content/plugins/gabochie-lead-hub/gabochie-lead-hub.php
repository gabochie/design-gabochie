<?php
/**
 * Plugin Name: Gabochie Lead Hub
 * Plugin URI:  https://gabochiedesign.com
 * Description: Single lead pipeline for gabochiedesign.com. Every capture point fires the `gabochie/lead/captured` action; this plugin routes each lead into the Gabochie CRM table (tagged by source), safely waits when the CRM is unavailable, and replays the backlog.
 * Version:     1.1.0
 * Requires PHP: 8.0
 * Author:      Gabochie Design Studio
 * Author URI:  https://gabochiedesign.com
 * Text Domain: gabochie-lead-hub
 */

defined( 'ABSPATH' ) || exit;

define( 'GABOCHIE_LEAD_HUB_VERSION', '1.1.0' );
define( 'GABOCHIE_LEAD_HUB_PATH', plugin_dir_path( __FILE__ ) );

require_once GABOCHIE_LEAD_HUB_PATH . 'includes/class-gabochie-lead-hub.php';
require_once GABOCHIE_LEAD_HUB_PATH . 'functions.php';

/**
 * Boot the lead hub once plugins are loaded (the CRM may boot alongside).
 */
add_action(
	'plugins_loaded',
	function () {
		Gabochie_Lead_Hub::instance();
	}
);

register_activation_hook(
	__FILE__,
	function () {
		require_once GABOCHIE_LEAD_HUB_PATH . 'includes/class-gabochie-lead-hub.php';
		Gabochie_Lead_Hub::instance()->activate();
	}
);
