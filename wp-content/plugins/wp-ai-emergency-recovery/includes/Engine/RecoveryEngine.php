<?php

declare(strict_types=1);

namespace WP_AI_Emergency_Recovery\Engine;

final class RecoveryEngine {
    private SnapshotEngine $snapshotEngine;
    private RollbackEngine $rollbackEngine;
    private LoggingEngine $loggingEngine;

    public function __construct(SnapshotEngine $snapshotEngine, RollbackEngine $rollbackEngine, LoggingEngine $loggingEngine) {
        $this->snapshotEngine = $snapshotEngine;
        $this->rollbackEngine = $rollbackEngine;
        $this->loggingEngine = $loggingEngine;
    }

    public function executeRepair(string $actionType, string $target): array {
        // Step 1: Create Pre-Repair Safety Snapshot
        $snapshot = $this->snapshotEngine->createSnapshot();

        $success = false;
        $message = '';

        switch ($actionType) {
            case 'disable_plugin':
                $success = $this->disablePlugin($target);
                $message = sprintf('Disabled plugin "%s" successfully.', $target);
                break;

            case 'repair_htaccess':
                $success = $this->repairHtaccess();
                $message = 'Default WordPress .htaccess rewrite rules restored.';
                break;

            case 'flush_rewrites':
                flush_rewrite_rules();
                $success = true;
                $message = 'WordPress rewrite rules flushed successfully.';
                break;

            case 'clear_cache':
                wp_cache_flush();
                $success = true;
                $message = 'Object cache and transients flushed.';
                break;

            default:
                return ['success' => false, 'message' => 'Unsupported repair action type.'];
        }

        // Step 2: Audit Logging
        $this->loggingEngine->log(
            'REPAIR',
            'Executed Emergency Repair',
            $message,
            ['actionType' => $actionType, 'target' => $target, 'snapshotId' => $snapshot['id']]
        );

        return [
            'success' => $success,
            'message' => $message,
            'snapshotId' => $snapshot['id']
        ];
    }

    private function disablePlugin(string $targetSlug): bool {
        $activePlugins = (array) get_option('active_plugins', []);
        $updated = [];
        foreach ($activePlugins as $file) {
            if (!str_contains($file, $targetSlug)) {
                $updated[] = $file;
            }
        }
        return update_option('active_plugins', $updated);
    }

    private function repairHtaccess(): bool {
        $htaccessPath = ABSPATH . '.htaccess';
        $rules = "# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
";
        return file_put_contents($htaccessPath, $rules) !== false;
    }
}
