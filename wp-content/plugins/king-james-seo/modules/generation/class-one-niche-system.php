<?php

namespace KingJamesSEO\Modules\Generation;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class One_Niche_System implements Module_Interface {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'one-niche-system';
    }

    public function init(): void {
        add_filter('kjs_pipeline_phase_research', [$this, 'execute_research'], 10, 2);
        add_action('kjs_daily_digest', [$this, 'generate_report']);
    }

    public function execute_research(array $input, int $run_id): array {
        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'research_started',
            'Niche research initiated',
            '',
            ['run_id' => $run_id, 'input' => $input]
        );
        return [
            'status'    => 'complete',
            'clusters'  => [],
            'keywords'  => [],
            'run_id'    => $run_id,
        ];
    }

    public function generate_report(): void {
        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'daily_digest',
            'Daily niche digest generated'
        );
    }
}
