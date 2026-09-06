<?php
/**
 * Plugin Name:       Gabochie Newsletter
 * Plugin URI:        https://gabochiedesign.com
 * Description:       Emails newsletter subscribers (Gabochie CRM table) as soon as a blog post is published. Each send is routed through gabochie-email/Brevo with a per-recipient one-click unsubscribe link, daily/run caps, an admin test-send, and a send log.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Gabochie Design
 * License:           GPL-2.0-or-later
 * Text Domain:       gabochie-newsletter
 */

defined( 'ABSPATH' ) || exit;

define( 'GABOCHIE_NEWSLETTER_VERSION', '1.0.0' );
define( 'GABOCHIE_NEWSLETTER_PATH', plugin_dir_path( __FILE__ ) );

require_once GABOCHIE_NEWSLETTER_PATH . 'includes/class-gabochie-newsletter.php';

add_action(
	'plugins_loaded',
	function () {
		Gabochie_Newsletter::instance();
	}
);

register_activation_hook(
	__FILE__,
	function () {
		require_once GABOCHIE_NEWSLETTER_PATH . 'includes/class-gabochie-newsletter.php';
		Gabochie_Newsletter::instance()->activate();
	}
);
