<?php
/**
 * Plugin Name: Gabochie Cold Outreach
 * Plugin URI:  https://design.gabochie.com
 * Description: Autonomous cold outreach to the Ghana business list (gadata) — imports contacts, routes by channel, generates bilingual direct-response messages (thesis lead magnet + free account) and sends on hard rate limits from WP-Cron. Opt-out compliant, dry-run aware.
 * Version:     0.1.0
 * Author:      Gabochie
 * Text Domain: gabochie-cold-outreach
 *
 * @package Gabochie_Cold_Outreach
 */

defined( 'ABSPATH' ) || exit;

define( 'GABOCHIE_CO_VERSION', '0.1.0' );
define( 'GABOCHIE_CO_DIR', plugin_dir_path( __FILE__ ) );
define( 'GABOCHIE_CO_URL', plugin_dir_url( __FILE__ ) );

require_once GABOCHIE_CO_DIR . 'includes/class-gabochie-co-db.php';
require_once GABOCHIE_CO_DIR . 'includes/class-gabochie-co-router.php';
require_once GABOCHIE_CO_DIR . 'includes/class-gabochie-co-messaging.php';
require_once GABOCHIE_CO_DIR . 'includes/class-gabochie-co-sender.php';
require_once GABOCHIE_CO_DIR . 'includes/class-gabochie-co-importer.php';
require_once GABOCHIE_CO_DIR . 'includes/class-gabochie-co-admin.php';
require_once GABOCHIE_CO_DIR . 'includes/class-gabochie-co-hub.php';

Gabochie_Co_Db::init();
Gabochie_Co_Sender::init();
Gabochie_Co_Hub::init();

add_action( 'plugins_loaded', array( 'Gabochie_Co_Admin', 'init' ) );

// Default dry-run ON so nothing sends until an operator explicitly arms a wave.
add_option( 'gabochie_co_mode', 'dry-run' );

/**
 * Register the outreach cron (every 5 minutes) with a hard cap sanity value.
 */
add_filter(
	'cron_schedules',
	function ( $schedules ) {
		if ( ! isset( $schedules['gabochie_co_5min'] ) ) {
			$schedules['gabochie_co_5min'] = array(
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => 'Every 5 minutes (Cold Outreach)',
			);
		}
		return $schedules;
	}
);

/**
 * Schedule the send driver on activation.
 */
function gabochie_co_activate() {
	Gabochie_Co_Db::install();
	if ( ! wp_next_scheduled( 'gabochie_co_send' ) ) {
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'gabochie_co_5min', 'gabochie_co_send' );
	}
}
register_activation_hook( __FILE__, 'gabochie_co_activate' );

/**
 * Clear the send cron on deactivation.
 */
function gabochie_co_deactivate() {
	wp_clear_scheduled_hook( 'gabochie_co_send' );
}
register_deactivation_hook( __FILE__, 'gabochie_co_deactivate' );
