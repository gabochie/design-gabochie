<?php

namespace KingJamesSEO\Modules\Quality;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class Quality_Gate_Auditor implements Module_Interface {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'quality-gate-auditor';
    }

    public function init(): void {
        add_filter('kjs_pipeline_phase_quality_check', [$this, 'execute_audit'], 10, 2);
        add_action('save_post', [$this, 'audit_on_save'], 99, 2);
    }

    public function execute_audit(array $input, int $run_id): array {
        $post_id = $input['post_id'] ?? 0;
        $content = $input['content'] ?? '';

        $score = $this->calculate_score($content);
        $disqualifiers = $this->check_disqualifiers($content);
        $passed = $score >= 104 && empty($disqualifiers);

        global $wpdb;
        $wpdb->insert(
            Plugin::instance()->database()->table('kjs_content_scores'),
            [
                'post_id'         => $post_id,
                'total_score'     => $score,
                'category_scores' => maybe_serialize($this->get_category_breakdown($content)),
                'passed_threshold' => (int) $passed,
            ]
        );

        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'audit_complete',
            "Quality audit for post {$post_id}: score {$score}, passed " . ($passed ? 'yes' : 'no'),
            '',
            ['post_id' => $post_id, 'score' => $score, 'disqualifiers' => $disqualifiers]
        );

        if (!$passed) {
            Plugin::instance()->guardrails()->check_escalation($this->get_module_id(), ['quality_below_threshold' => true]);
        }

        return [
            'status'        => $passed ? 'pass' : 'fail',
            'score'         => $score,
            'disqualifiers' => $disqualifiers,
            'passed'        => $passed,
            'post_id'       => $post_id,
        ];
    }

    public function audit_on_save(int $post_id, \WP_Post $post): void {
        if ('post' !== $post->post_type || 'publish' !== $post->post_status) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        $this->execute_audit(['post_id' => $post_id, 'content' => $post->post_content], 0);
    }

    private function calculate_score(string $content): float {
        $score = 70.0;
        $word_count = str_word_count(strip_tags($content));
        if ($word_count > 1500) {
            $score += 10;
        }
        if ($word_count > 2500) {
            $score += 10;
        }
        if (preg_match('/<h2[^>]*>/i', $content)) {
            $score += 5;
        }
        if (preg_match('/<h3[^>]*>/i', $content)) {
            $score += 5;
        }
        if (preg_match('/<ul[^>]*>/i', $content) || preg_match('/<ol[^>]*>/i', $content)) {
            $score += 4;
        }
        return $score;
    }

    private function check_disqualifiers(string $content): array {
        $disqualifiers = [];
        $banned_phrases = [
            'in today\'s digital landscape',
            'in today\'s fast-paced world',
            'it is what it is',
            'at the end of the day',
            'game-changer',
            'revolutionary',
            'unlock your potential',
            'think outside the box',
            'synergy',
            'leverage',
        ];

        $stripped = strip_tags($content);
        foreach ($banned_phrases as $phrase) {
            if (stripos($stripped, $phrase) !== false) {
                $disqualifiers[] = "Contains banned phrase: \"{$phrase}\"";
            }
        }

        return $disqualifiers;
    }

    private function get_category_breakdown(string $content): array {
        return [
            'word_count'       => str_word_count(strip_tags($content)),
            'has_headings'     => (int) preg_match('/<h[2-3][^>]*>/i', $content),
            'has_lists'        => (int) (preg_match('/<ul[^>]*>/i', $content) || preg_match('/<ol[^>]*>/i', $content)),
            'has_images'       => (int) preg_match('/<img[^>]*>/i', $content),
            'disqualifier_count' => count($this->check_disqualifiers($content)),
        ];
    }
}
