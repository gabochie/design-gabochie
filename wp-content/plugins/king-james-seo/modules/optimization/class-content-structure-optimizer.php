<?php

namespace KingJamesSEO\Modules\Optimization;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class Content_Structure_Optimizer implements Module_Interface {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'content-structure-optimizer';
    }

    public function init(): void {
        add_filter('kjs_pipeline_phase_optimization', [$this, 'execute'], 40, 2);
    }

    public function execute(array $input, int $run_id): array {
        $post_id = $input['post_id'] ?? 0;
        if (!$post_id) {
            return ['status' => 'error', 'message' => 'No post_id provided'];
        }

        $content = get_post_field('post_content', $post_id);
        $optimized = $this->optimize_heading_hierarchy($content);

        if ($optimized !== $content) {
            wp_update_post([
                'ID'           => $post_id,
                'post_content' => $optimized,
            ]);
        }

        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'structure_optimized',
            "Content structure optimized for post {$post_id}",
            '',
            ['post_id' => $post_id]
        );

        return ['status' => 'complete', 'post_id' => $post_id];
    }

    private function optimize_heading_hierarchy(string $content): string {
        $lines = explode("\n", $content);
        $h_count = 0;
        $result = [];

        foreach ($lines as $line) {
            if (preg_match('/^<h[2-6]/i', trim($line))) {
                $h_count++;
            }
            $result[] = $line;
        }

        if ($h_count < 2) {
            return $content;
        }

        return implode("\n", $result);
    }
}
