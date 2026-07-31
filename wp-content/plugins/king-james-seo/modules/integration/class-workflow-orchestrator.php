<?php

namespace KingJamesSEO\Modules\Integration;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class Workflow_Orchestrator implements Module_Interface {
    private static ?self $instance = null;

    private const PHASES = [
        'research',
        'planning',
        'generation',
        'optimization',
        'quality_check',
        'ymyl_review',
        'human_review',
        'scheduling',
        'publishing',
    ];

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'workflow-orchestrator';
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'add_orchestrator_page'], 15);
    }

    public function add_orchestrator_page(): void {
        add_submenu_page(
            'king-james-seo',
            __('Workflow Orchestrator', 'king-james-seo'),
            __('Workflows', 'king-james-seo'),
            'manage_options',
            'kjs-workflows',
            [$this, 'render_dashboard']
        );
    }

    public function render_dashboard(): void {
        $phases = self::PHASES;
        $active_runs = Plugin::instance()->pipeline()->get_runs(['limit' => 20]);
        include KJS_PLUGIN_DIR . 'admin/views/workflows.php';
    }

    public function start_pipeline(string $name, array $config = []): int {
        $run_id = Plugin::instance()->pipeline()->create_run($name, $config);

        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'pipeline_started',
            "Pipeline '{$name}' started (run {$run_id})",
            '',
            ['run_id' => $run_id, 'config' => $config]
        );

        return $run_id;
    }

    public function execute_phase(string $phase, array $input, int $run_id): array {
        $result = apply_filters("kjs_pipeline_phase_{$phase}", $input, $run_id);

        Plugin::instance()->pipeline()->save_checkpoint($run_id, [
            $phase => [
                'status' => $result['status'] ?? 'unknown',
                'time'   => current_time('mysql'),
            ],
        ]);

        return $result;
    }
}
