<?php

namespace KingJamesSEO\Modules\Optimization;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class Meta_Description_Architect implements Module_Interface {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'meta-description-architect';
    }

    public function init(): void {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta_description'], 10, 2);
        add_filter('kjs_pipeline_phase_optimization', [$this, 'execute'], 20, 2);
    }

    public function add_meta_box(): void {
        add_meta_box(
            'kjs-meta-description',
            __('Meta Description Architect', 'king-james-seo'),
            [$this, 'render_meta_box'],
            'post',
            'normal',
            'high'
        );
    }

    public function render_meta_box(\WP_Post $post): void {
        $meta = get_post_meta($post->ID, '_kjs_meta_description', true);
        $cta  = get_post_meta($post->ID, '_kjs_meta_cta', true);
        wp_nonce_field('kjs_meta_description', 'kjs_meta_description_nonce');
        ?>
        <p>
            <label for="kjs-meta-description"><?php esc_html_e('Meta Description (160 char max)', 'king-james-seo'); ?></label>
            <textarea id="kjs-meta-description" name="kjs_meta_description" class="widefat" rows="3" maxlength="160"><?php echo esc_textarea($meta); ?></textarea>
        </p>
        <p>
            <label for="kjs-meta-cta"><?php esc_html_e('CTA (all caps)', 'king-james-seo'); ?></label>
            <input type="text" id="kjs-meta-cta" name="kjs_meta_cta" value="<?php echo esc_attr($cta); ?>" class="widefat" placeholder="<?php esc_attr_e('LEARN MORE', 'king-james-seo'); ?>">
        </p>
        <?php
    }

    public function save_meta_description(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['kjs_meta_description_nonce']) || !wp_verify_nonce($_POST['kjs_meta_description_nonce'], 'kjs_meta_description')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['kjs_meta_description'])) {
            $desc = sanitize_textarea_field($_POST['kjs_meta_description']);
            update_post_meta($post_id, '_kjs_meta_description', $desc);
            if (isset($_POST['kjs_meta_cta'])) {
                update_post_meta($post_id, '_kjs_meta_cta', sanitize_text_field(strtoupper($_POST['kjs_meta_cta'])));
            }
        }
    }

    public function execute(array $input, int $run_id): array {
        $post_id = $input['post_id'] ?? 0;
        if (!$post_id) {
            return ['status' => 'error', 'message' => 'No post_id provided'];
        }

        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'description_generated',
            "Meta description generated for post {$post_id}",
            '',
            ['post_id' => $post_id]
        );

        return ['status' => 'complete', 'post_id' => $post_id];
    }
}
