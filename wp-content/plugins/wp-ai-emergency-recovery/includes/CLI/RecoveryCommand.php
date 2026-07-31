<?php

declare(strict_types=1);

namespace WP_AI_Emergency_Recovery\CLI;

use WP_CLI;
use WP_AI_Emergency_Recovery\Core\Plugin;

final class RecoveryCommand {
    public static function register(): void {
        WP_CLI::add_command('ai-recovery scan', [self::class, 'scan']);
        WP_CLI::add_command('ai-recovery fix', [self::class, 'fix']);
    }

    public function scan(): void {
        WP_CLI::log('Starting AI Autonomous Incident Scan...');
        $engine = Plugin::getInstance()->getSnapshotEngine();
        $snapshot = $engine->createSnapshot();

        WP_CLI::success(sprintf('Snapshot created: %s', $snapshot['id']));
        WP_CLI::log(sprintf('Active Plugins: %d | Theme: %s', $snapshot['activePluginsCount'], $snapshot['activeTheme']['name']));
    }

    public function fix(array $args, array $assocArgs): void {
        $target = $assocArgs['target'] ?? '';
        $action = $assocArgs['action'] ?? 'disable_plugin';

        WP_CLI::log(sprintf('Executing recovery action "%s" on "%s"...', $action, $target));
        $res = Plugin::getInstance()->getRecoveryEngine()->executeRepair($action, $target);

        if ($res['success']) {
            WP_CLI::success($res['message']);
        } else {
            WP_CLI::error('Recovery action failed.');
        }
    }
}
