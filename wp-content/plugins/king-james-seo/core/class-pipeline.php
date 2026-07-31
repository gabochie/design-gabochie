<?php

namespace KingJamesSEO\Core;

defined('ABSPATH') || exit;

class Pipeline {
    private const PHASES = [
        'research'    => ['label' => 'Research', 'autonomy' => 'L4'],
        'planning'    => ['label' => 'Planning', 'autonomy' => 'L4'],
        'generation'  => ['label' => 'Generation', 'autonomy' => 'L2'],
        'optimization' => ['label' => 'Optimization', 'autonomy' => 'L3'],
        'quality_check' => ['label' => 'Quality Check', 'autonomy' => 'L4'],
        'ymyl_review' => ['label' => 'YMYL Review', 'autonomy' => 'L1'],
        'human_review' => ['label' => 'Human Review', 'autonomy' => 'L1'],
        'scheduling'  => ['label' => 'Scheduling', 'autonomy' => 'L3'],
        'publishing'  => ['label' => 'Publishing', 'autonomy' => 'L2'],
    ];

    public function phases(): array {
        return self::PHASES;
    }

    public function create_run(string $name, array $module_config = []): int {
        global $wpdb;

        $wpdb->insert(
            Plugin::instance()->database()->table('kjs_pipeline_runs'),
            [
                'name'          => $name,
                'status'        => 'pending',
                'current_phase' => 'research',
                'module_config'  => maybe_serialize($module_config),
                'checkpoint_data' => maybe_serialize([]),
            ]
        );
        Plugin::instance()->logger()->log('pipeline', 'run_created', "Pipeline run created: {$name}");

        return (int) $wpdb->insert_id;
    }

    public function advance(int $run_id): ?string {
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_pipeline_runs');
        $run = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $run_id));

        if (!$run || 'completed' === $run->status || 'failed' === $run->status) {
            return null;
        }

        $phases = array_keys(self::PHASES);
        $current_idx = array_search($run->current_phase, $phases, true);

        if (false === $current_idx || $current_idx >= count($phases) - 1) {
            $wpdb->update($table, ['status' => 'completed', 'completed_at' => current_time('mysql')], ['id' => $run_id]);
            Plugin::instance()->logger()->log('pipeline', 'run_completed', "Pipeline run {$run_id} completed");
            return null;
        }

        $next_phase = $phases[$current_idx + 1];
        $wpdb->update($table, ['current_phase' => $next_phase, 'status' => 'in_progress'], ['id' => $run_id]);
        Plugin::instance()->logger()->log('pipeline', 'phase_advance', "Pipeline {$run_id} advanced to {$next_phase}");

        return $next_phase;
    }

    public function fail(int $run_id, string $reason = ''): void {
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_pipeline_runs');
        $wpdb->update($table, ['status' => 'failed', 'checkpoint_data' => maybe_serialize(['fail_reason' => $reason])], ['id' => $run_id]);
        Plugin::instance()->logger()->log('pipeline', 'run_failed', "Pipeline {$run_id} failed: {$reason}");
    }

    public function get_run(int $run_id): ?array {
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_pipeline_runs');
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $run_id), ARRAY_A);
        if ($row) {
            $row['module_config'] = maybe_unserialize($row['module_config']);
            $row['checkpoint_data'] = maybe_unserialize($row['checkpoint_data']);
        }
        return $row ?: null;
    }

    public function get_runs(array $args = []): array {
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_pipeline_runs');
        $where = ['1=1'];
        $params = [];

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }

        $limit = min(abs($args['limit'] ?? 20), 100);
        $offset = max(0, (int) ($args['offset'] ?? 0));

        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY updated_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        return (array) $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    }

    public function save_checkpoint(int $run_id, array $data): void {
        global $wpdb;
        $table = Plugin::instance()->database()->table('kjs_pipeline_runs');
        $run = $this->get_run($run_id);
        $existing = $run ? ($run['checkpoint_data'] ?: []) : [];
        $merged = array_merge($existing, $data);
        $wpdb->update($table, ['checkpoint_data' => maybe_serialize($merged)], ['id' => $run_id]);
    }
}
