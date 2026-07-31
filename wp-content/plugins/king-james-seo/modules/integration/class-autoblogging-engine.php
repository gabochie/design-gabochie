<?php

namespace KingJamesSEO\Modules\Integration;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;
use KingJamesSEO\Includes\AI_Provider;

defined('ABSPATH') || exit;

class Autoblogging_Engine implements Module_Interface {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'autoblogging-engine';
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'add_admin_pages'], 20);
        add_action('wp_ajax_kjs_autoblog_queue_rewrite', [$this, 'ajax_queue_rewrite']);
        add_action('wp_ajax_kjs_autoblog_schedule', [$this, 'ajax_schedule_generation']);
        add_action('wp_ajax_kjs_autoblog_get_status', [$this, 'ajax_get_status']);
        add_action('wp_ajax_kjs_autoblog_apply_rewrite', [$this, 'ajax_apply_rewrite']);
        add_action('wp_ajax_kjs_autoblog_bulk_queue', [$this, 'ajax_bulk_queue']);

        if (!wp_next_scheduled('kjs_autoblog_process')) {
            wp_schedule_event(time(), 'hourly', 'kjs_autoblog_process');
        }
        add_action('kjs_autoblog_process', [$this, 'process_scheduled_tasks']);

        add_action('wp_ajax_kjs_autoblog_run_scan', [$this, 'ajax_run_scan']);
        add_action('wp_ajax_kjs_autoblog_save_settings', [$this, 'ajax_save_settings']);
    }

    public function add_admin_pages(): void {
        add_submenu_page(
            'king-james-seo',
            __('Autoblogging Command Center', 'king-james-seo'),
            __('Autoblogging', 'king-james-seo'),
            'manage_options',
            'king-james-seo-autoblog',
            [$this, 'render_dashboard']
        );
    }

    public function render_dashboard(): void {
        if (!current_user_can('manage_options')) return;

        global $wpdb;

        $stats = [
            'total_posts'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'"),
            'rewrite_queue'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kjs_rewrite_queue WHERE status = 'queued'"),
            'rewrite_done'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kjs_rewrite_queue WHERE status = 'completed'"),
            'rewrite_failed' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kjs_rewrite_queue WHERE status = 'failed'"),
            'scheduled'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kjs_content_calendar WHERE status = 'scheduled'"),
        ];

        $gscc_stats = $wpdb->get_row("SELECT COUNT(*) as total, AVG(compliance_score) as avg_score FROM {$wpdb->prefix}kjs_gscc_audits");
        $stats['gscc_total'] = (int) ($gscc_stats->total ?? 0);
        $stats['gscc_avg'] = round((float) ($gscc_stats->avg_score ?? 0), 1);

        $critical = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kjs_gscc_audits WHERE compliance_score < 40");
        $warning = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kjs_gscc_audits WHERE compliance_score >= 40 AND compliance_score < 70");
        $stats['gscc_critical'] = (int) $critical;
        $stats['gscc_warning'] = (int) $warning;

        $queue = $wpdb->get_results(
            "SELECT q.*, p.post_title, p.post_status
             FROM {$wpdb->prefix}kjs_rewrite_queue q
             LEFT JOIN {$wpdb->posts} p ON q.post_id = p.ID
             ORDER BY FIELD(q.status, 'queued', 'completed', 'failed', 'skipped'), q.priority ASC, q.created_at DESC
             LIMIT 50"
        );

        $scheduled = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}kjs_content_calendar
             WHERE scheduled_date >= NOW()
             ORDER BY scheduled_date ASC
             LIMIT 20"
        );

        $ai_configured = AI_Provider::factory() !== null;
        $guardrails = \KingJamesSEO\Core\Guardrails::instance();
        $publishing_mode = $guardrails->get_publishing_mode();
        $humanization = $guardrails->get_humanization_settings();

        include KJS_PLUGIN_DIR . 'admin/views/autoblogging.php';
    }

    public function ajax_queue_rewrite(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) wp_die(-1);

        $post_id = (int) ($_POST['post_id'] ?? 0);
        $priority = min(5, max(1, (int) ($_POST['priority'] ?? 3)));
        $instructions = sanitize_textarea_field($_POST['instructions'] ?? '');

        if (!$post_id) wp_send_json_error(['message' => 'No post ID']);

        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}kjs_rewrite_queue WHERE post_id = %d AND status = 'queued'",
            $post_id
        ));

        if ($exists) wp_send_json_error(['message' => 'Already queued']);

        $wpdb->insert(
            $wpdb->prefix . 'kjs_rewrite_queue',
            [
                'post_id' => $post_id,
                'priority' => $priority,
                'instructions' => $instructions,
                'status' => 'queued',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ]
        );

        wp_send_json_success(['message' => 'Added to rewrite queue']);
    }

    public function ajax_bulk_queue(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) wp_die(-1);

        $threshold = min(100, max(0, (int) ($_POST['threshold'] ?? 70)));
        $limit = min(50, max(1, (int) ($_POST['limit'] ?? 10)));

        global $wpdb;
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT a.post_id, a.compliance_score, a.word_count
             FROM {$wpdb->prefix}kjs_gscc_audits a
             WHERE a.compliance_score < %d
             ORDER BY a.compliance_score ASC
             LIMIT %d",
            $threshold,
            $limit
        ));

        $queued = 0;
        foreach ($posts as $post) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}kjs_rewrite_queue WHERE post_id = %d AND status = 'queued'",
                $post->post_id
            ));
            if ($exists) continue;

            $wpdb->insert(
                $wpdb->prefix . 'kjs_rewrite_queue',
                [
                    'post_id' => $post->post_id,
                    'priority' => $post->compliance_score < 40 ? 1 : ($post->compliance_score < 70 ? 3 : 5),
                    'instructions' => "Post scores {$post->compliance_score}/100 with {$post->word_count} words. Expand to 1200+ words, improve structure and readability.",
                    'status' => 'queued',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ]
            );
            $queued++;
        }

        wp_send_json_success(['queued' => $queued, 'message' => "Queued {$queued} posts for rewrite"]);
    }

    public function ajax_apply_rewrite(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) wp_die(-1);

        $queue_id = (int) ($_POST['queue_id'] ?? 0);
        $publish = !empty($_POST['publish']);

        if (!$queue_id) wp_send_json_error(['message' => 'No queue ID']);

        global $wpdb;
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kjs_rewrite_queue WHERE id = %d AND status = 'completed'",
            $queue_id
        ));

        if (!$item || !$item->result_content) {
            wp_send_json_error(['message' => 'No rewritten content found']);
        }

        $post_data = [
            'ID' => $item->post_id,
            'post_content' => $item->result_content,
        ];

        if ($publish) {
            $post = get_post($item->post_id);
            if ($post && $post->post_status !== 'publish') {
                $post_data['post_status'] = 'publish';
            }
        }

        wp_update_post($post_data);

        $wpdb->update(
            $wpdb->prefix . 'kjs_rewrite_queue',
            ['status' => 'applied', 'updated_at' => current_time('mysql')],
            ['id' => $queue_id]
        );

        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'rewrite_applied',
            "Rewrite applied to post {$item->post_id}",
            '',
            ['queue_id' => $queue_id, 'post_id' => $item->post_id]
        );

        wp_send_json_success(['message' => 'Rewrite applied to post']);
    }

    public function ajax_schedule_generation(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) wp_die(-1);

        $topic = sanitize_text_field($_POST['topic'] ?? '');
        $frequency = sanitize_text_field($_POST['frequency'] ?? 'daily');
        $keywords = array_map('sanitize_text_field', $_POST['keywords'] ?? []);

        if (!$topic) wp_send_json_error(['message' => 'No topic']);

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'kjs_content_calendar',
            [
                'title' => $topic,
                'module' => 'autoblogging-engine',
                'status' => 'scheduled',
                'cluster' => implode(',', $keywords),
            ]
        );

        wp_send_json_success(['message' => "Scheduled '{$topic}' for {$frequency} generation"]);
    }

    public function ajax_get_status(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) wp_die(-1);

        global $wpdb;
        $stats = [
            'queue_total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kjs_rewrite_queue"),
            'queue_queued' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kjs_rewrite_queue WHERE status = 'queued'"),
            'queue_completed' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kjs_rewrite_queue WHERE status = 'completed'"),
            'queue_applied' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kjs_rewrite_queue WHERE status = 'applied'"),
            'queue_failed' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kjs_rewrite_queue WHERE status = 'failed'"),
        ];

        wp_send_json_success($stats);
    }

    public function ajax_run_scan(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) wp_die(-1);

        $gscc = \KingJamesSEO\Modules\Quality\Google_Scaled_Content_Compliance::instance();
        $results = $gscc->run_bulk_audit();

        $scores = array_column($results, 'compliance_score');
        $avg = !empty($scores) ? round(array_sum($scores) / count($scores), 1) : 0;
        $critical = count(array_filter($results, fn($r) => $r['compliance_score'] < 40));
        $warning = count(array_filter($results, fn($r) => $r['compliance_score'] >= 40 && $r['compliance_score'] < 70));

        wp_send_json_success([
            'total' => count($results),
            'avg' => $avg,
            'critical' => $critical,
            'warning' => $warning,
        ]);
    }

    public function ajax_save_settings(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) wp_die(-1);

        $guardrails = \KingJamesSEO\Core\Guardrails::instance();

        $mode = sanitize_text_field($_POST['publishing_mode'] ?? 'semi');
        $guardrails->set_publishing_mode($mode);

        $humanization = [
            'randomize_time'  => !empty($_POST['randomize_time']),
            'time_variance'   => max(1, min(6, (int) ($_POST['time_variance'] ?? 3))),
            'randomize_words' => !empty($_POST['randomize_words']),
            'min_words'       => max(300, min(5000, (int) ($_POST['min_words'] ?? 1200))),
            'max_words'       => max(500, min(10000, (int) ($_POST['max_words'] ?? 2800))),
            'skip_weekends'   => !empty($_POST['skip_weekends']),
            'skip_days'       => max(0, min(3, (int) ($_POST['skip_days'] ?? 1))),
            'max_per_day'     => max(1, min(10, (int) ($_POST['max_per_day'] ?? 2))),
            'publish_time_hour' => max(6, min(22, (int) ($_POST['publish_time_hour'] ?? 10))),
        ];

        $guardrails->save_humanization_settings($humanization);

        wp_send_json_success([
            'message' => 'Settings saved',
            'mode' => $mode,
            'humanization' => $humanization,
        ]);
    }

    public function process_scheduled_tasks(): void {
        if (!AI_Provider::factory()) return;

        global $wpdb;
        $guardrails = \KingJamesSEO\Core\Guardrails::instance();
        $publishing_mode = $guardrails->get_publishing_mode();
        $humanization = $guardrails->get_humanization_settings();

        $content_engine = \KingJamesSEO\Modules\Generation\AI_Content_Engine::instance();
        $content_engine->process_rewrite_queue();

        if ($publishing_mode === 'auto' && !$guardrails->should_publish_today()) {
            Plugin::instance()->logger()->log(
                $this->get_module_id(),
                'schedule_skip',
                "Skipping publish today (humanization: weekend/skip day/max reached)",
                '',
                ['mode' => $publishing_mode]
            );
            return;
        }

        $scheduled = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}kjs_content_calendar
             WHERE module = 'autoblogging-engine'
             AND status = 'scheduled'
             AND (scheduled_date IS NULL OR scheduled_date <= NOW())
             LIMIT 3"
        );

        foreach ($scheduled as $item) {
            $keywords = !empty($item->cluster) ? explode(',', $item->cluster) : [];
            $word_target = $guardrails->get_randomized_word_target();

            $result = $content_engine->execute_generation([
                'topic' => $item->title,
                'keywords' => $keywords,
                "instructions" => "Write a comprehensive, SEO-optimized article. Target approximately {$word_target} words. Vary sentence length and structure. Use natural, conversational transitions between sections.",
            ], 0);

            if (isset($result['status']) && $result['status'] === 'complete' && !empty($result['content'])) {
                $post_status = ($publishing_mode === 'auto') ? 'publish' : 'draft';

                if ($publishing_mode === 'auto') {
                    $publish_time = $guardrails->get_randomized_publish_time();
                    $tomorrow = new \DateTime('+1 day', wp_timezone());
                    $tomorrow->setTime(...array_map('intval', explode(':', $publish_time)));
                    $post_date = $tomorrow->format('Y-m-d H:i:s');
                    $post_date_gmt = gmdate('Y-m-d H:i:s', strtotime($post_date));
                }

                $post_data = [
                    'post_title'   => $item->title,
                    'post_content' => $result['content'],
                    'post_status'  => $post_status,
                    'post_author'  => 1,
                    'post_type'    => 'post',
                ];

                if ($publishing_mode === 'auto' && isset($post_date)) {
                    $post_data['post_date']     = $post_date;
                    $post_data['post_date_gmt'] = $post_date_gmt;
                }

                $post_id = wp_insert_post($post_data);

                $wpdb->update(
                    $wpdb->prefix . 'kjs_content_calendar',
                    [
                        'post_id'    => $post_id,
                        'status'     => ($publishing_mode === 'auto') ? 'published' : 'generated',
                        'updated_at' => current_time('mysql'),
                    ],
                    ['id' => $item->id]
                );

                Plugin::instance()->logger()->log(
                    $this->get_module_id(),
                    'auto_generated',
                    "Auto-generated post: {$item->title} ({$word_target} words, mode: {$publishing_mode})",
                    '',
                    ['post_id' => $post_id, 'calendar_id' => $item->id, 'mode' => $publishing_mode, 'words' => $word_target]
                );
            } else {
                $wpdb->update(
                    $wpdb->prefix . 'kjs_content_calendar',
                    ['status' => 'failed', 'updated_at' => current_time('mysql')],
                    ['id' => $item->id]
                );
            }
        }
    }
}
