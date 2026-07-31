<?php

namespace KingJamesSEO\Modules\Generation;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class Cluster_Content_Authority implements Module_Interface {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'cluster-content-authority';
    }

    public function init(): void {
        add_filter('kjs_pipeline_phase_generation', [$this, 'execute'], 10, 2);
    }

    public function execute(array $input, int $run_id): array {
        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'generation_started',
            'Cluster article generation initiated',
            '',
            ['run_id' => $run_id, 'input' => $input]
        );
        return [
            'status'  => 'draft',
            'content' => '',
            'score'   => 0,
            'run_id'  => $run_id,
        ];
    }
}
