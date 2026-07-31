<?php

namespace KingJamesSEO\Core;

defined('ABSPATH') || exit;

class Guardrails {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private const DEFAULT_LEVELS = [
        'cluster-content-authority'  => 'L2',
        'pillar-content-authority'   => 'L2',
        'one-keyword-system'         => 'L2',
        'one-niche-system'           => 'L3',
        'seo-title-engineer'         => 'L3',
        'meta-description-architect'  => 'L3',
        'schema-markup-generator'    => 'L3',
        'content-structure-optimizer' => 'L3',
        'quality-gate-auditor'       => 'L4',
        'ymyl-compliance'            => 'L1',
        'ai-detection-shield'        => 'L3',
        'content-scoring-dashboard'  => 'L4',
        'workflow-orchestrator'      => 'L4',
        'content-calendar'           => 'L3',
        'seo-tool-integration'       => 'L4',
        'api-connector'              => 'L2',
    ];

    public function set_defaults(): void {
        foreach (self::DEFAULT_LEVELS as $module => $level) {
            $option = 'kjs_autonomy_' . $module;
            if (false === get_option($option)) {
                update_option($option, $level);
            }
        }
    }

    public function get_level(string $module): string {
        return get_option('kjs_autonomy_' . $module, self::DEFAULT_LEVELS[$module] ?? 'L1');
    }

    public function set_level(string $module, string $level): bool {
        if (!in_array($level, ['L1', 'L2', 'L3', 'L4'], true)) {
            return false;
        }
        Plugin::instance()->logger()->log(
            $module,
            'autonomy_change',
            "Autonomy changed to {$level}",
            '',
            ['new_level' => $level]
        );
        return update_option('kjs_autonomy_' . $module, $level);
    }

    public function can_auto_publish(string $module): bool {
        $level = $this->get_level($module);
        return in_array($level, ['L3', 'L4'], true);
    }

    public function needs_human_review(string $module): bool {
        $level = $this->get_level($module);
        return in_array($level, ['L1', 'L2'], true);
    }

    public function get_daily_limits(string $module): array {
        return [
            'posts'    => (int) get_option('kjs_limit_posts_' . $module, 10),
            'api_calls' => (int) get_option('kjs_limit_api_' . $module, 100),
        ];
    }

    public function get_publishing_mode(): string {
        return get_option('kjs_publishing_mode', 'semi');
    }

    public function set_publishing_mode(string $mode): bool {
        if (!in_array($mode, ['auto', 'semi'], true)) return false;
        Plugin::instance()->logger()->log(
            'guardrails', 'mode_change',
            "Publishing mode changed to {$mode}", '',
            ['new_mode' => $mode]
        );
        return update_option('kjs_publishing_mode', $mode);
    }

    public function get_humanization_settings(): array {
        return [
            'randomize_time'  => (bool) get_option('kjs_hum_randomize_time', true),
            'time_variance'   => (int) get_option('kjs_hum_time_variance', 3),
            'randomize_words' => (bool) get_option('kjs_hum_randomize_words', true),
            'min_words'       => (int) get_option('kjs_hum_min_words', 1200),
            'max_words'       => (int) get_option('kjs_hum_max_words', 2800),
            'skip_weekends'   => (bool) get_option('kjs_hum_skip_weekends', false),
            'skip_days'       => (int) get_option('kjs_hum_skip_days', 1),
            'max_per_day'     => (int) get_option('kjs_hum_max_per_day', 2),
            'publish_time_hour' => (int) get_option('kjs_hum_publish_hour', 10),
        ];
    }

    public function save_humanization_settings(array $settings): bool {
        $map = [
            'randomize_time'  => 'kjs_hum_randomize_time',
            'time_variance'   => 'kjs_hum_time_variance',
            'randomize_words' => 'kjs_hum_randomize_words',
            'min_words'       => 'kjs_hum_min_words',
            'max_words'       => 'kjs_hum_max_words',
            'skip_weekends'   => 'kjs_hum_skip_weekends',
            'skip_days'       => 'kjs_hum_skip_days',
            'max_per_day'     => 'kjs_hum_max_per_day',
            'publish_time_hour' => 'kjs_hum_publish_hour',
        ];
        $ok = true;
        foreach ($map as $key => $option) {
            if (isset($settings[$key])) {
                $ok = update_option($option, $settings[$key]) && $ok;
            }
        }
        return $ok;
    }

    public function should_publish_today(): bool {
        $settings = $this->get_humanization_settings();

        if ($settings['skip_weekends']) {
            $day = (int) current_time('w');
            if ($day === 0 || $day === 6) return false;
        }

        if ($settings['skip_days'] > 0) {
            $day_of_year = (int) current_time('z');
            if (($day_of_year % 7) < $settings['skip_days']) return false;
        }

        global $wpdb;
        $today = current_time('Y-m-d');
        $published_today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'post' AND post_status = 'publish'
             AND DATE(post_date) = %s",
            $today
        ));
        if ($published_today >= $settings['max_per_day']) return false;

        return true;
    }

    public function get_randomized_publish_time(): string {
        $settings = $this->get_humanization_settings();
        $hour = $settings['publish_time_hour'];

        if ($settings['randomize_time']) {
            $variance = $settings['time_variance'];
            $offset = mt_rand(-$variance, $variance);
            $hour = max(6, min(22, $hour + $offset));
        }

        $minute = mt_rand(0, 59);
        return sprintf('%02d:%02d:00', $hour, $minute);
    }

    public function get_randomized_word_target(): int {
        $settings = $this->get_humanization_settings();

        if (!$settings['randomize_words']) {
            return (int) (($settings['min_words'] + $settings['max_words']) / 2);
        }

        return mt_rand($settings['min_words'], $settings['max_words']);
    }

    public function check_escalation(string $module, array $context = []): ?string {
        $level = $this->get_level($module);

        if (!empty($context['ymyl_detected']) && $level !== 'L1') {
            return 'ymyl_detected';
        }
        if (!empty($context['quality_below_threshold'])) {
            return 'quality_failure';
        }
        if (!empty($context['repeated_rejection']) && $context['repeated_rejection'] >= 3) {
            return 'repeated_rejection';
        }
        return null;
    }
}
