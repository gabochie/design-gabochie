<?php
/**
 * Plugin Name: Gabochie CRM
 * Plugin URI:  https://gabochiedesign.com
 * Description: In-house contact store for gabochiedesign.com. Owns the gab_contacts table: the single subscriber/lead source for the newsletter, lead hub and command center. Replaces FluentCRM as the audience store with a tiny first-party table.
 * Version:     1.0.0
 * Requires PHP: 8.0
 * Author:      Gabochie Design Studio
 * Author URI:  https://gabochiedesign.com
 * Text Domain: gabochie-crm
 */

defined( 'ABSPATH' ) || exit;

define( 'GABOCHIE_CRM_VERSION', '1.0.0' );
define( 'GABOCHIE_CRM_PATH', plugin_dir_path( __FILE__ ) );

require_once GABOCHIE_CRM_PATH . 'includes/class-gabochie-crm.php';
require_once GABOCHIE_CRM_PATH . 'functions.php';

/**
 * Boot — ensure the contact table exists so consumers can rely on it.
 */
add_action(
	'plugins_loaded',
	function () {
		Gabochie_Crm::ensure_schema();
	}
);

register_activation_hook(
	__FILE__,
	function () {
		require_once GABOCHIE_CRM_PATH . 'includes/class-gabochie-crm.php';
		Gabochie_Crm::ensure_schema();
	}
);