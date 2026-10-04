<?php
/**
 * Plugin Name:       Gabochie Anti-Spam
 * Plugin URI:        https://gabochiedesign.com
 * Description:       In-house Akismet replacement for Gabochie Design. Hybrid protection: honeypot + heuristic rules + optional AI second opinion (GhBC LLM) on borderline submissions. Covers comments and the lead-capture/onboarding AJAX forms, with a reviewable spam log.
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Gabochie Design
 * License:           GPL-2.0-or-later
 * Text Domain:       gabochie-antispam
 */

defined( 'ABSPATH' ) || exit;

define( 'GABOCHIE_ANTISPAM_VERSION', '1.0.1' );
define( 'GABOCHIE_ANTISPAM_PATH', plugin_dir_path( __FILE__ ) );
define( 'GABOCHIE_ANTISPAM_URL', plugin_dir_url( __FILE__ ) );
define( 'GABOCHIE_ANTISPAM_TABLE', 'gabochie_antispam' );

require_once GABOCHIE_ANTISPAM_PATH . 'includes/class-db.php';
require_once GABOCHIE_ANTISPAM_PATH . 'includes/class-rules.php';
require_once GABOCHIE_ANTISPAM_PATH . 'includes/class-ai.php';
require_once GABOCHIE_ANTISPAM_PATH . 'includes/class-hooks.php';
require_once GABOCHIE_ANTISPAM_PATH . 'includes/class-admin.php';

register_activation_hook( __FILE__, array( 'Gabochie_Antispam_DB', 'activate' ) );

Gabochie_Antispam_DB::instance();
Gabochie_Antispam_Rules::instance();
Gabochie_Antispam_AI::instance();
Gabochie_Antispam_Hooks::instance();
Gabochie_Antispam_Admin::instance();

/**
 * Convenience accessor for plugin settings.
 */
function gabochie_antispam_settings() {
    return get_option( 'gabochie_antispam_settings', Gabochie_Antispam_DB::default_settings() );
}