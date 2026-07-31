<?php

namespace KingJamesSEO\Modules\Optimization;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class SEO_Title_Engineer implements Module_Interface {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'seo-title-engineer';
    }

    public function init(): void {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_title_data'], 10, 2);
        add_filter('kjs_pipeline_phase_optimization', [$this, 'execute'], 10, 2);
    }

    public function add_meta_box(): void {
        add_meta_box(
            'kjs-seo-title',
            __('SEO Title Engineer', 'king-james-seo'),
            [$this, 'render_meta_box'],
            'post',
            'normal',
            'high'
        );
    }

    public function render_meta_box(\WP_Post $post): void {
        $seo_title = get_post_meta($post->ID, '_kjs_seo_title', true);
        $h1_title  = get_post_meta($post->ID, '_kjs_h1_title', true);
        wp_nonce_field('kjs_seo_title', 'kjs_seo_title_nonce');
        ?>
        <p>
            <label for="kjs-h1-title"><?php esc_html_e('H1 Title', 'king-james-seo'); ?></label>
            <input type="text" id="kjs-h1-title" name="kjs_h1_title" value="<?php echo esc_attr($h1_title ?: $post->post_title); ?>" class="widefat">
        </p>
        <p>
            <label for="kjs-seo-title"><?php esc_html_e('SEO Title (60 char max)', 'king-james-seo'); ?></label>
            <input type="text" id="kjs-seo-title" name="kjs_seo_title" value="<?php echo esc_attr($seo_title); ?>" class="widefat" maxlength="60">
        </p>
        <?php
    }

    public function save_title_data(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['kjs_seo_title_nonce']) || !wp_verify_nonce($_POST['kjs_seo_title_nonce'], 'kjs_seo_title')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['kjs_seo_title'])) {
            update_post_meta($post_id, '_kjs_seo_title', sanitize_text_field($_POST['kjs_seo_title']));
        }
        if (isset($_POST['kjs_h1_title'])) {
            update_post_meta($post_id, '_kjs_h1_title', sanitize_text_field($_POST['kjs_h1_title']));
        }
    }

    public function execute(array $input, int $run_id): array {
        $post_id = $input['post_id'] ?? 0;
        if (!$post_id) {
            return ['status' => 'error', 'message' => 'No post_id provided'];
        }

        $title = $input['title'] ?? get_the_title($post_id);
        if (strlen($title) > 60) {
            $title = substr($title, 0, 57) . '...';
        }

        update_post_meta($post_id, '_kjs_seo_title', $title);

        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'title_generated',
            "SEO title generated for post {$post_id}",
            '',
            ['post_id' => $post_id, 'title' => $title]
        );

        return ['status' => 'complete', 'post_id' => $post_id, 'seo_title' => $title];
    }
}
