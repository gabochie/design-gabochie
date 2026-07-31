<?php

namespace KingJamesSEO\Modules\Quality;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class AI_Detection_Shield implements Module_Interface {
    private static ?self $instance = null;

    private const AI_TELLS = [
        'delve', 'delve into', 'navigate', 'navigate the', 'landscape', 'robust', 'leveraging',
        'cutting-edge', 'cutting edge', 'transformative', 'paradigm', 'paradigm shift',
        'seamless', 'seamlessly', 'holistic', 'holistically', 'utilize', 'utilizing',
        'arguably', 'essentially', 'essentially a', 'in essence', 'it is worth noting',
        'it should be noted', 'it is important to', 'in the realm of', 'in the world of',
    ];

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'ai-detection-shield';
    }

    public function init(): void {
        add_filter('kjs_pipeline_phase_quality_check', [$this, 'execute_sweep'], 20, 2);
    }

    public function execute_sweep(array $input, int $run_id): array {
        $content = $input['content'] ?? '';
        $post_id = $input['post_id'] ?? 0;

        $findings = $this->scan($content);
        $humanized = $content;

        if (!empty($findings)) {
            $humanized = $this->humanize($content);
        }

        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'sweep_complete',
            "AI detection sweep for post {$post_id}: " . count($findings) . ' tells found',
            '',
            ['post_id' => $post_id, 'tells_found' => $findings]
        );

        return [
            'status'       => empty($findings) ? 'clean' : 'humanized',
            'tells_found'  => $findings,
            'content'      => $humanized,
            'post_id'      => $post_id,
        ];
    }

    public function scan(string $content): array {
        $findings = [];
        $stripped = strip_tags($content);

        foreach (self::AI_TELLS as $tell) {
            $count = 0;
            $pos = 0;
            while (($pos = stripos($stripped, $tell, $pos)) !== false) {
                $count++;
                $pos += strlen($tell);
            }
            if ($count > 0) {
                $findings[] = ['phrase' => $tell, 'count' => $count];
            }
        }

        return $findings;
    }

    private function humanize(string $content): string {
        $replacements = [
            'delve' => 'explore',
            'navigate' => 'handle',
            'robust' => 'strong',
            'leveraging' => 'using',
            'cutting-edge' => 'advanced',
            'transformative' => 'powerful',
            'paradigm' => 'approach',
            'seamless' => 'smooth',
            'holistic' => 'complete',
            'utilize' => 'use',
            'arguably' => '',
            'essentially' => '',
            'it is worth noting' => '',
            'it should be noted' => '',
            'it is important to' => '',
            'in the realm of' => 'in',
            'in the world of' => 'in',
        ];

        foreach ($replacements as $ai => $human) {
            $content = str_ireplace($ai, $human, $content);
        }

        $content = preg_replace('/\s+/', ' ', $content);
        return trim($content);
    }
}
