<?php
/**
 * Plugin Name:       Gabochie Email
 * Plugin URI:        https://design.gabochie.com
 * Description:       In-house transactional email delivery for Gabochie Design. Routes wp_mail through the Brevo Transactional API (SMTP deliverability, no local send), with an automatic fallback to the core wp_mail()/PHPMailer path when Brevo is unavailable or misconfigured. HTML messages are delivered as authored HTML (Content-Type: text/html). API key is stored as a WordPress option and is editable from Settings → Gabochie Email.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Gabochie Design
 * License:           GPL-2.0-or-later
 * Text Domain:       gabochie-email
 */

defined( 'ABSPATH' ) || exit;

define( 'GABOCHIE_EMAIL_VERSION', '1.1.0' );
define( 'GABOCHIE_EMAIL_PATH', plugin_dir_path( __FILE__ ) );
define( 'GABOCHIE_EMAIL_OPTION', 'gabochie_email_api_key' );
define( 'GABOCHIE_EMAIL_API', 'https://api.brevo.com/v3' );

require_once GABOCHIE_EMAIL_PATH . 'includes/class-gabochie-email.php';

Gabochie_Email::instance();
