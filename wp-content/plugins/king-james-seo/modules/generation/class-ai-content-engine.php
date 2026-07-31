<?php

namespace KingJamesSEO\Modules\Generation;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;
use KingJamesSEO\Includes\AI_Provider;

defined('ABSPATH') || exit;

class AI_Content_Engine implements Module_Interface {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'ai-content-engine';
    }

    public function init(): void {
        add_filter('kjs_pipeline_phase_generation', [$this, 'execute_generation'], 10, 2);
        add_action('kjs_content_rewrite_queue', [$this, 'process_rewrite_queue']);
        add_action('wp_ajax_kjs_ai_generate', [$this, 'ajax_generate']);
        add_action('wp_ajax_kjs_ai_rewrite', [$this, 'ajax_rewrite']);
        add_action('wp_ajax_kjs_ai_rewrite_thin_posts', [$this, 'ajax_rewrite_thin_posts']);
    }

    public function execute_generation(array $input, int $run_id): array {
        $provider = AI_Provider::factory();
        if (!$provider) {
            return ['status' => 'error', 'message' => 'No AI provider configured. Go to King James SEO > Settings to set up an API key.'];
        }

        $topic = $input['topic'] ?? '';
        $keywords = $input['keywords'] ?? [];
        $instructions = $input['instructions'] ?? 'Write a comprehensive, well-researched article.';

        if (!$topic) {
            return ['status' => 'error', 'message' => 'No topic provided'];
        }

        $kw_str = !empty($keywords) ? 'Target keywords: ' . implode(', ', $keywords) . "\n" : '';

        $prompt = "Write a high-quality blog post on the following topic:\n\nTopic: {$topic}\n{$kw_str}Instructions: {$instructions}\n\n"
            . "Requirements:\n"
            . "- Minimum 1200 words\n"
            . "- Use proper heading hierarchy (H2, H3)\n"
            . "- Include an introduction and conclusion\n"
            . "- Write in a natural, human tone — avoid AI tells like 'delve', 'navigate', 'robust', 'cutting-edge'\n"
            . "- Use short paragraphs and clear language\n"
            . "- Include relevant examples and practical advice\n"
            . "- Format with proper HTML tags (h2, h3, p, ul, ol, li, strong)";

        $content = $provider->generate($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 4096,
            'system' => 'You are a professional SEO content writer. Write content that ranks well in search engines while being genuinely useful to readers. Write in a natural, conversational tone.',
        ]);

        if (strpos($content, '// Error:') === 0) {
            return ['status' => 'error', 'message' => substr($content, 3)];
        }

        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'content_generated',
            "Content generated on topic: {$topic}",
            '',
            ['run_id' => $run_id, 'topic' => $topic, 'length' => strlen($content)]
        );

        return [
            'status' => 'complete',
            'content' => $content,
            'topic' => $topic,
            'run_id' => $run_id,
        ];
    }

    public function ajax_generate(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) wp_die(-1);

        $topic = sanitize_text_field($_POST['topic'] ?? '');
        $keywords = array_map('sanitize_text_field', $_POST['keywords'] ?? []);
        $instructions = sanitize_textarea_field($_POST['instructions'] ?? '');

        if (!$topic) wp_send_json_error(['message' => 'No topic provided']);

        $result = $this->execute_generation([
            'topic' => $topic,
            'keywords' => $keywords,
            'instructions' => $instructions,
        ], 0);

        wp_send_json_success($result);
    }

    public function ajax_rewrite(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) wp_die(-1);

        $post_id = (int) ($_POST['post_id'] ?? 0);
        $instructions = sanitize_textarea_field($_POST['instructions'] ?? 'Expand and improve this content. Make it more comprehensive, add practical details, improve readability. Target 1000+ words.');
        $title_override = sanitize_text_field($_POST['title'] ?? '');

        if (!$post_id) wp_send_json_error(['message' => 'No post ID provided']);

        $post = get_post($post_id);
        if (!$post) wp_send_json_error(['message' => 'Post not found']);

        $provider = AI_Provider::factory();
        if (!$provider) wp_send_json_error(['message' => 'No AI provider configured']);

        $title = $title_override ?: $post->post_title;
        $content = $post->post_content;

        $full_instructions = "Title: {$title}\n\n{$instructions}";

        $rewritten = $provider->rewrite($content, $full_instructions, [
            'temperature' => 0.5,
            'max_tokens' => 4096,
            'system' => 'You are a professional content editor specializing in SEO content improvement. Expand thin content, add value, fix readability issues, and improve structure while keeping the original voice and facts.',
        ]);

        if (strpos($rewritten, '// Error:') === 0) {
            wp_send_json_error(['message' => substr($rewritten, 3)]);
        }

        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'content_rewritten',
            "Content rewritten for post {$post_id}: {$title}",
            '',
            ['post_id' => $post_id, 'original_length' => strlen($content), 'new_length' => strlen($rewritten)]
        );

        wp_send_json_success([
            'content' => $rewritten,
            'title' => $title,
            'post_id' => $post_id,
            'original_length' => strlen($content),
            'new_length' => strlen($rewritten),
            'original_words' => str_word_count(wp_strip_all_tags($content)),
            'new_words' => str_word_count(wp_strip_all_tags($rewritten)),
        ]);
    }

    public function ajax_rewrite_thin_posts(): void {
        check_ajax_referer('kjs_admin_nonce');
        if (!current_user_can('manage_options')) wp_die(-1);

        $limit = min((int) ($_POST['limit'] ?? 5), 20);
        $threshold = (int) ($_POST['threshold'] ?? 70);

        global $wpdb;
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT a.post_id, a.compliance_score, a.word_count, a.issues_found,
                    p.post_title, p.post_content, p.ID
             FROM {$wpdb->prefix}kjs_gscc_audits a
             LEFT JOIN {$wpdb->posts} p ON a.post_id = p.ID
             WHERE a.compliance_score < %d
             ORDER BY a.compliance_score ASC
             LIMIT %d",
            $threshold,
            $limit
        ));

        if (empty($posts)) {
            wp_send_json_success(['rewritten' => 0, 'message' => 'No thin posts found below threshold']);
        }

        $provider = AI_Provider::factory();
        if (!$provider) wp_send_json_error(['message' => 'No AI provider configured']);

        $results = [];
        foreach ($posts as $post) {
            if (empty($post->post_content)) continue;

            $instructions = "This post scores {$post->compliance_score}/100 for Google Scaled Content Compliance with {$post->issues_found} issues and only {$post->word_count} words. "
                . "Expand it to at least 1200 words. Add practical details, examples, and structure it well with headings. "
                . "Fix readability issues. Keep the original topic and facts intact.";

            $rewritten = $provider->rewrite($post->post_content, $instructions, [
                'temperature' => 0.5,
                'max_tokens' => 4096,
                'system' => 'You are a professional content editor. Your job is to expand thin, low-quality content into comprehensive, high-value articles.',
            ]);

            if (strpos($rewritten, '// Error:') === 0) {
                $results[] = [
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'error' => substr($rewritten, 3),
                ];
                continue;
            }

            $results[] = [
                'post_id' => $post->ID,
                'title' => $post->post_title,
                'original_words' => str_word_count(wp_strip_all_tags($post->post_content)),
                'new_words' => str_word_count(wp_strip_all_tags($rewritten)),
                'content' => $rewritten,
            ];

            Plugin::instance()->logger()->log(
                $this->get_module_id(),
                'thin_content_rewritten',
                "Thin post rewritten: {$post->post_title} ({$post->word_count}w -> " . str_word_count(wp_strip_all_tags($rewritten)) . "w)",
                '',
                ['post_id' => $post->ID]
            );
        }

        $rewritten_count = count(array_filter($results, fn($r) => empty($r['error'])));

        wp_send_json_success([
            'rewritten' => $rewritten_count,
            'total' => count($results),
            'results' => $results,
            'message' => "Rewrote {$rewritten_count} of " . count($results) . " thin posts",
        ]);
    }

    public function process_rewrite_queue(): void {
        $provider = AI_Provider::factory();
        if (!$provider) return;

        global $wpdb;
        $queue = $wpdb->get_results($wpdb->prepare(
            "SELECT q.*, p.post_content, p.post_title
             FROM {$wpdb->prefix}kjs_rewrite_queue q
             LEFT JOIN {$wpdb->posts} p ON q.post_id = p.ID
             WHERE q.status = 'queued'
             ORDER BY q.priority ASC, q.created_at ASC
             LIMIT %d",
            5
        ));

        foreach ($queue as $item) {
            if (empty($item->post_content)) {
                $wpdb->update(
                    $wpdb->prefix . 'kjs_rewrite_queue',
                    ['status' => 'skipped', 'updated_at' => current_time('mysql')],
                    ['id' => $item->id]
                );
                continue;
            }

            $instructions = $item->instructions ?: "Expand this content. Target 1200+ words. Improve structure, readability, and depth.";

            $rewritten = $provider->rewrite($item->post_content, $instructions, [
                'temperature' => 0.5,
                'max_tokens' => 4096,
            ]);

            if (strpos($rewritten, '// Error:') === 0) {
                $wpdb->update(
                    $wpdb->prefix . 'kjs_rewrite_queue',
                    ['status' => 'failed', 'result_log' => substr($rewritten, 3), 'updated_at' => current_time('mysql')],
                    ['id' => $item->id]
                );
                continue;
            }

            $wpdb->update(
                $wpdb->prefix . 'kjs_rewrite_queue',
                [
                    'status' => 'completed',
                    'result_content' => $rewritten,
                    'updated_at' => current_time('mysql'),
                ],
                ['id' => $item->id]
            );
        }
    }
}
