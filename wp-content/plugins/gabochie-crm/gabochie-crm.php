<?php
/**
 * Plugin Name: Gabochie CRM
 * Plugin URI:  https://gabochiedesign.com
 * Description: In-house contact store for gabochiedesign.com. Owns the gab_contacts table: the single subscriber/lead source for the newsletter, lead hub and command center. Replaces FluentCRM as the audience store with a tiny first-party table. Ships a Contacts admin (list table, edit, bulk actions), CSV export and GDPR export/erase.
 * Version:     1.1.0
 * Requires PHP: 8.0
 * Author:      Gabochie Design Studio
 * Author URI:  https://gabochiedesign.com
 * Text Domain: gabochie-crm
 */

defined( 'ABSPATH' ) || exit;

define( 'GABOCHIE_CRM_VERSION', '1.1.0' );
define( 'GABOCHIE_CRM_PATH', plugin_dir_path( __FILE__ ) );

require_once GABOCHIE_CRM_PATH . 'includes/class-gabochie-crm.php';
require_once GABOCHIE_CRM_PATH . 'functions.php';
require_once GABOCHIE_CRM_PATH . 'includes/class-gabochie-crm-export.php';
require_once GABOCHIE_CRM_PATH . 'includes/class-gabochie-crm-list-table.php';
require_once GABOCHIE_CRM_PATH . 'includes/class-gabochie-crm-admin.php';

/**
 * Boot — ensure the contact table exists so consumers can rely on it.
 */
add_action(
	'plugins_loaded',
	function () {
		Gabochie_Crm::ensure_schema();
	}
);

/**
 * Admin surface (contacts management) + GDPR export/erase + REST export.
 */
add_action(
	'admin_menu',
	static function () {
		return Gabochie_Crm_Admin::register();
	}
);
add_action( 'init', array( 'Gabochie_Crm_Export', 'register' ) );

register_activation_hook(
	__FILE__,
	function () {
		include_once GABOCHIE_CRM_PATH . 'includes/class-gabochie-crm.php';
		Gabochie_Crm::ensure_schema();
	}
);
