<?php

namespace KingJamesSEO\Admin;

use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class Admin_Ajax {
    public static function init(): void {
        add_action('wp_ajax_kjs_toggle_module', [self::class, 'toggle_module']);
        add_action('wp_ajax_kjs_set_autonomy', [self::class, 'set_autonomy']);
        add_action('wp_ajax_kjs_create_pipeline', [self::class, 'create_pipeline']);
        add_action('wp_ajax_kjs_advance_pipeline', [self::class, 'advance_pipeline']);
        add_action('wp_ajax_kjs_fail_pipeline', [self::class, 'fail_pipeline']);
        add_action('wp_ajax_kjs_gscc_audit_single', [self::class, 'gscc_audit_single']);
        add_action('wp_ajax_kjs_gscc_audit_all', [self::class, 'gscc_audit_all']);
        add_action('wp_ajax_kjs_create_post_from_ai', [self::class, 'create_post_from_ai']);
        add_action('wp_ajax_kjs_queue_and_apply_rewrite', [self::class, 'queue_and_apply_rewrite']);
    }

    public static function toggle_module(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }

        $module = sanitize_text_field($_POST['module'] ?? '');
        $enable = !empty($_POST['enable']);

        if ($enable) {
            Plugin::instance()->module_manager()->enable($module);
        } else {
            Plugin::instance()->module_manager()->disable($module);
        }

        wp_send_json_success(['module' => $module, 'enabled' => $enable]);
    }

    public static function set_autonomy(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }

        $module = sanitize_text_field($_POST['module'] ?? '');
        $level = sanitize_text_field($_POST['level'] ?? '');

        $result = Plugin::instance()->guardrails()->set_level($module, $level);
        if ($result) {
            wp_send_json_success(['module' => $module, 'level' => $level]);
        }
        wp_send_json_error(['message' => 'Invalid level']);
    }

    public static function create_pipeline(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }

        $name = sanitize_text_field($_POST['name'] ?? 'Untitled Pipeline');
        $config = isset($_POST['config']) ? (array) $_POST['config'] : [];

        $run_id = Plugin::instance()->pipeline()->create_run($name, $config);
        wp_send_json_success(['run_id' => $run_id]);
    }

    public static function advance_pipeline(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }

        $run_id = (int) ($_POST['run_id'] ?? 0);
        $phase = Plugin::instance()->pipeline()->advance($run_id);
        wp_send_json_success(['run_id' => $run_id, 'next_phase' => $phase]);
    }

    public static function fail_pipeline(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }

        $run_id = (int) ($_POST['run_id'] ?? 0);
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        Plugin::instance()->pipeline()->fail($run_id, $reason);
        wp_send_json_success(['run_id' => $run_id]);
    }

    public static function gscc_audit_single(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }

        $post_id = (int) ($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(['message' => 'Invalid post ID']);
        }

        $module = \KingJamesSEO\Modules\Quality\Google_Scaled_Content_Compliance::instance();
        $result = $module->audit_post($post_id);

        wp_send_json_success($result);
    }

    public static function gscc_audit_all(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }

        $module = \KingJamesSEO\Modules\Quality\Google_Scaled_Content_Compliance::instance();
        $results = $module->run_bulk_audit();

        $scores = array_column($results, 'compliance_score');
        $avg = !empty($scores) ? array_sum($scores) / count($scores) : 0;

        wp_send_json_success([
            'total'   => count($results),
            'average' => round($avg, 1),
        ]);
    }

    public static function create_post_from_ai(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) wp_die(-1);

        $title = sanitize_text_field($_POST['title'] ?? 'AI Generated Post');
        $content = wp_kses_post($_POST['content'] ?? '');

        if (!$content) wp_send_json_error(['message' => 'No content provided']);

        $post_id = wp_insert_post([
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'draft',
            'post_author' => get_current_user_id(),
            'post_type' => 'post',
        ]);

        if (is_wp_error($post_id)) {
            wp_send_json_error(['message' => $post_id->get_error_message()]);
        }

        Plugin::instance()->logger()->log(
            'ai-content-engine',
            'post_created',
            "AI-generated post saved as draft: {$title}",
            '',
            ['post_id' => $post_id]
        );

        wp_send_json_success(['post_id' => $post_id, 'edit_url' => get_edit_post_link($post_id)]);
    }

    public static function queue_and_apply_rewrite(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) wp_die(-1);

        $post_id = (int) ($_POST['post_id'] ?? 0);
        $content = wp_kses_post($_POST['content'] ?? '');

        if (!$post_id || !$content) wp_send_json_error(['message' => 'Missing post ID or content']);

        wp_update_post([
            'ID' => $post_id,
            'post_content' => $content,
        ]);

        Plugin::instance()->logger()->log(
            'ai-content-engine',
            'rewrite_applied_direct',
            "Rewrite applied directly to post {$post_id}",
            '',
            ['post_id' => $post_id]
        );

        wp_send_json_success(['post_id' => $post_id]);
    }
}
