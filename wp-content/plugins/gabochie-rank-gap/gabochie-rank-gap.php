<?php
/**
 * Plugin Name:  Gabochie Rank Gap Tracker
 * Plugin URI:   https://gabochiedesign.com
 * Description:  Keyword rank tracker + content-gap finder vs. Ghana AEC competitors. Tracks rankings for target keyword clusters and identifies topics competitors cover that you don't. Feeds INT-01 ContentPipeline with real content targets.
 * Version:      1.0.0
 * Requires PHP: 8.0
 * Author:       Gabochie Design Studio
 * Author URI:   https://gabochiedesign.com
 * Text Domain:  gabochie-rank-gap
 */

defined('ABSPATH') || exit;

define('GRG_VERSION', '1.0.0');
define('GRG_PATH', plugin_dir_path(__FILE__));
define('GRG_URL', plugin_dir_url(__FILE__));

require_once GRG_PATH . 'includes/class-keywords.php';
require_once GRG_PATH . 'includes/class-gaps.php';
require_once GRG_PATH . 'includes/class-admin.php';

register_activation_hook(__FILE__, function () {
    require_once GRG_PATH . 'includes/class-keywords.php';
    GRG_Keywords::seed_default_clusters();
});
