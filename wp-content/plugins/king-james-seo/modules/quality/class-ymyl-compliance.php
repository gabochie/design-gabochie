<?php

namespace KingJamesSEO\Modules\Quality;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class YMYL_Compliance implements Module_Interface {
    private static ?self $instance = null;

    private const YMYL_TOPICS = [
        'health'  => ['symptom', 'treatment', 'diagnosis', 'medical', 'disease', 'prescription', 'dosage', 'therapy'],
        'finance' => ['investment', 'loan', 'mortgage', 'credit', 'tax', 'retirement', 'insurance', 'stock'],
        'legal'   => ['attorney', 'lawsuit', 'legal', 'court', 'divorce', 'contract', 'liability'],
        'safety'  => ['safety', 'recall', 'warning', 'hazard', 'emergency'],
    ];

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'ymyl-compliance';
    }

    public function init(): void {
        add_action('save_post', [$this, 'check_post_on_save'], 10, 2);
        add_filter('kjs_pipeline_phase_ymyl_review', [$this, 'execute_review'], 10, 2);
        add_action('post_submitbox_misc_actions', [$this, 'render_ymyl_status']);
    }

    public function check_post_on_save(int $post_id, \WP_Post $post): void {
        if ('post' !== $post->post_type) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $result = $this->analyze($post->post_title . ' ' . $post->post_content);
        if ($result['is_ymyl']) {
            update_post_meta($post_id, '_kjs_ymyl_topic', $result['topic_type']);
            update_post_meta($post_id, '_kjs_ymyl_requires_review', '1');

            Plugin::instance()->logger()->log(
                $this->get_module_id(),
                'ymyl_detected',
                "YMYL topic detected in post {$post_id}: {$result['topic_type']}",
                '',
                ['post_id' => $post_id, 'topic_type' => $result['topic_type'], 'terms' => $result['matched_terms']]
            );
        }
    }

    public function execute_review(array $input, int $run_id): array {
        $post_id = $input['post_id'] ?? 0;
        $content = $input['content'] ?? '';

        $result = $this->analyze($content);
        $passed = !$result['is_ymyl'];

        global $wpdb;
        $wpdb->insert(
            Plugin::instance()->database()->table('kjs_ymyl_audits'),
            [
                'post_id'    => $post_id,
                'topic_type' => $result['topic_type'],
                'grade'      => $passed ? 'A' : 'B',
                'findings'   => maybe_serialize($result),
                'passed'     => (int) $passed,
            ]
        );

        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'review_complete',
            "YMYL review for post {$post_id}: " . ($passed ? 'passed' : 'flagged'),
            '',
            ['post_id' => $post_id, 'result' => $result]
        );

        return [
            'status'     => $passed ? 'pass' : 'review_required',
            'is_ymyl'    => $result['is_ymyl'],
            'topic_type' => $result['topic_type'],
            'post_id'    => $post_id,
        ];
    }

    public function render_ymyl_status(): void {
        global $post;
        if (!$post || 'post' !== $post->post_type) {
            return;
        }
        $requires = get_post_meta($post->ID, '_kjs_ymyl_requires_review', true);
        if ($requires) {
            echo '<div class="misc-pub-section" style="border-top:1px solid #ccd0d4; color:#dc3232;">';
            echo '<strong>' . esc_html__('YMYL Review Required', 'king-james-seo') . '</strong>';
            echo '</div>';
        }
    }

    public function analyze(string $text): array {
        $lower = strtolower($text);
        $matches = [];

        foreach (self::YMYL_TOPICS as $type => $terms) {
            foreach ($terms as $term) {
                if (strpos($lower, $term) !== false) {
                    $matches[$type][] = $term;
                }
            }
        }

        return [
            'is_ymyl'      => !empty($matches),
            'topic_type'   => !empty($matches) ? implode(', ', array_keys($matches)) : '',
            'matched_terms' => $matches,
        ];
    }
}
