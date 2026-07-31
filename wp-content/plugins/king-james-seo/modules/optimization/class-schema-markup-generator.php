<?php

namespace KingJamesSEO\Modules\Optimization;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class Schema_Markup_Generator implements Module_Interface {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'schema-markup-generator';
    }

    public function init(): void {
        add_action('wp_head', [$this, 'output_schema'], 1);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_schema_data'], 10, 2);
        add_filter('kjs_pipeline_phase_optimization', [$this, 'execute'], 30, 2);
    }

    public function add_meta_box(): void {
        add_meta_box(
            'kjs-schema',
            __('Schema Markup Generator', 'king-james-seo'),
            [$this, 'render_meta_box'],
            'post',
            'normal',
            'high'
        );
    }

    public function render_meta_box(\WP_Post $post): void {
        $schema_type = get_post_meta($post->ID, '_kjs_schema_type', true) ?: 'Article';
        wp_nonce_field('kjs_schema', 'kjs_schema_nonce');
        ?>
        <p>
            <label for="kjs-schema-type"><?php esc_html_e('Schema Type', 'king-james-seo'); ?></label>
            <select id="kjs-schema-type" name="kjs_schema_type" class="widefat">
                <?php foreach (['Article', 'FAQPage', 'HowTo', 'Product', 'Review', 'LocalBusiness'] as $type): ?>
                    <option value="<?php echo esc_attr($type); ?>" <?php selected($schema_type, $type); ?>><?php echo esc_html($type); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    public function save_schema_data(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['kjs_schema_nonce']) || !wp_verify_nonce($_POST['kjs_schema_nonce'], 'kjs_schema')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['kjs_schema_type'])) {
            update_post_meta($post_id, '_kjs_schema_type', sanitize_text_field($_POST['kjs_schema_type']));
        }
    }

    public function output_schema(): void {
        if (!is_singular('post')) {
            return;
        }

        $post_id = get_the_ID();
        $schema_type = get_post_meta($post_id, '_kjs_schema_type', true) ?: 'Article';

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => $schema_type,
            'headline' => get_the_title($post_id),
            'datePublished' => get_the_date('c', $post_id),
            'dateModified'  => get_the_modified_date('c', $post_id),
            'author' => [
                '@type' => 'Person',
                'name'  => get_the_author_meta('display_name', (int) get_post_field('post_author', $post_id)),
            ],
        ];

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }

    public function execute(array $input, int $run_id): array {
        $post_id = $input['post_id'] ?? 0;
        if (!$post_id) {
            return ['status' => 'error', 'message' => 'No post_id provided'];
        }

        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'schema_generated',
            "Schema generated for post {$post_id}",
            '',
            ['post_id' => $post_id, 'schema_type' => $input['schema_type'] ?? 'Article']
        );

        return ['status' => 'complete', 'post_id' => $post_id];
    }
}
