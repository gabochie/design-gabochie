<?php

namespace KingJamesSEO\Modules\Integration;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class SEO_Tool_Integration implements Module_Interface {
    private static ?self $instance = null;

    private array $cache = [];

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'seo-tool-integration';
    }

    public function init(): void {
        add_filter('kjs_pipeline_phase_research', [$this, 'enrich_research'], 5, 2);
    }

    public function enrich_research(array $input, int $run_id): array {
        $keyword = $input['keyword'] ?? '';

        if ($keyword) {
            $metrics = $this->get_keyword_metrics($keyword);
            Plugin::instance()->logger()->log(
                $this->get_module_id(),
                'research_enriched',
                "Research enriched for keyword: {$keyword}",
                '',
                ['keyword' => $keyword, 'metrics' => $metrics]
            );
            return array_merge($input, ['keyword_metrics' => $metrics]);
        }

        return $input;
    }

    public function get_keyword_metrics(string $keyword): array {
        $cache_key = 'kjs_seo_' . md5($keyword);
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        $metrics = [
            'keyword'      => $keyword,
            'volume'       => $this->estimate_volume($keyword),
            'difficulty'   => $this->estimate_difficulty($keyword),
            'cpc'          => $this->estimate_cpc($keyword),
            'source'       => 'estimated',
            'cached_at'    => current_time('mysql'),
        ];

        set_transient($cache_key, $metrics, HOUR_IN_SECONDS * 6);
        return $metrics;
    }

    private function estimate_volume(string $keyword): int {
        $len = strlen($keyword);
        if ($len < 10) {
            return rand(100, 1000);
        }
        if ($len < 30) {
            return rand(50, 500);
        }
        return rand(10, 100);
    }

    private function estimate_difficulty(string $keyword): int {
        return rand(20, 80);
    }

    private function estimate_cpc(string $keyword): float {
        return round(rand(50, 500) / 100, 2);
    }

    public function get_people_also_ask(string $keyword): array {
        return [
            "What is {$keyword}?",
            "How does {$keyword} work?",
            "Why is {$keyword} important?",
            "Benefits of {$keyword}",
        ];
    }
}
