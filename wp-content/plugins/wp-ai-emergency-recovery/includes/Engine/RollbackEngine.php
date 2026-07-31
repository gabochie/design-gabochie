<?php

declare(strict_types=1);

namespace WP_AI_Emergency_Recovery\Engine;

final class RollbackEngine {
    private LoggingEngine $loggingEngine;

    public function __construct(LoggingEngine $loggingEngine) {
        $this->loggingEngine = $loggingEngine;
    }

    public function restoreSnapshot(string $snapshotId): bool {
        global $wpdb;

        $table = $wpdb->prefix . 'ai_recovery_snapshots';
        $row = $wpdb->get_row($wpdb->prepare("SELECT snapshot_data FROM {$table} WHERE id = %s", $snapshotId));

        if (!$row) {
            return false;
        }

        $data = json_decode($row->snapshot_data, true);
        if (!is_array($data)) {
            return false;
        }

        // Restore active plugins list
        $activeFiles = [];
        if (!empty($data['pluginsList'])) {
            foreach ($data['pluginsList'] as $plug) {
                if (($plug['status'] ?? '') === 'active') {
                    $activeFiles[] = $plug['slug'] . '/' . $plug['slug'] . '.php';
                }
            }
            update_option('active_plugins', $activeFiles);
        }

        $this->loggingEngine->log(
            'ROLLBACK',
            'Restored Site Snapshot',
            sprintf('Site state successfully restored to snapshot ID "%s"', $snapshotId)
        );

        return true;
    }
}
