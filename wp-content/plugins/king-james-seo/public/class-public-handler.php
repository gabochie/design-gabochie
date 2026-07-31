<?php

namespace KingJamesSEO\Public_;

use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class Public_Handler {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(): void {
        if (is_singular('post')) {
            $post_id = get_the_ID();
            $schema_type = get_post_meta($post_id, '_kjs_schema_type', true);
            if ($schema_type || get_post_meta($post_id, '_kjs_seo_title', true)) {
                wp_enqueue_style('kjs-public', KJS_PLUGIN_URL . 'assets/css/public.css', [], KJS_VERSION);
            }
        }
    }
}
