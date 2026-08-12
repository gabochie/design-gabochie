<?php
/**
 * Plugin Name:       Gabochie Community
 * Plugin URI:        https://gabochiedesign.com
 * Description:       Public Q&A community for Gabochie Design — journey-tagged questions, AI classification and lead detection. Renders the community on the /community/ page via [gabochie_community].
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Gabochie Design
 * License:           GPL-2.0-or-later
 * Text Domain:       gabochie-community
 */

defined( 'ABSPATH' ) || exit;

define( 'GABOCHIE_COMMUNITY_VERSION', '1.0.0' );
define( 'GABOCHIE_COMMUNITY_PATH', plugin_dir_path( __FILE__ ) );
define( 'GABOCHIE_COMMUNITY_URL', plugin_dir_url( __FILE__ ) );

require_once GABOCHIE_COMMUNITY_PATH . 'includes/class-post-types.php';
require_once GABOCHIE_COMMUNITY_PATH . 'includes/class-classifier.php';
require_once GABOCHIE_COMMUNITY_PATH . 'includes/class-frontend.php';

register_activation_hook( __FILE__, array( 'Gabochie_Community_Post_Types', 'activate' ) );

Gabochie_Community_Post_Types::instance();
Gabochie_Community_Classifier::instance();
Gabochie_Community_Frontend::instance();
