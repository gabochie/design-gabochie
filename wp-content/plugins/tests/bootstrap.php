<?php
/**
 * PHPUnit bootstrap for the BuildSuite Pro (BOQ Construction Suite) plugin.
 *
 * These are unit tests: they must run without a WordPress install. The plugin
 * files exit unless ABSPATH is defined and call a handful of WordPress helpers,
 * so this bootstrap defines the minimum surface those code paths need. Anything
 * that touches the database or HTTP is out of scope for this suite.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__, 4) . '/');
}

if (!defined('BOQ_SUITE_VERSION')) {
    define('BOQ_SUITE_VERSION', '1.0.0');
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return $text;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim((string) $str);
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value) {
        return $value;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        /** @var string */
        public $code;

        /** @var string */
        public $message;

        public function __construct($code = '', $message = '') {
            $this->code    = $code;
            $this->message = $message;
        }

        public function get_error_code() {
            return $this->code;
        }

        public function get_error_message() {
            return $this->message;
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

require_once dirname(__DIR__) . '/includes/modules/class-module-boq-importer.php';
