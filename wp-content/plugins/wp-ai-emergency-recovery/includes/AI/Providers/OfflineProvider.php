<?php

declare(strict_types=1);

namespace WP_AI_Emergency_Recovery\AI\Providers;

use WP_AI_Emergency_Recovery\AI\ProviderInterface;

final class OfflineProvider implements ProviderInterface {
    public function getProviderName(): string {
        return 'Offline Diagnostics Engine (Rule-Based)';
    }

    public function isAvailable(): bool {
        return true;
    }

    public function diagnoseIncident(array $snapshotData, array $errorLogs): array {
        $rootCauses = [];
        $recommendedRepairs = [];

        // Rule 1: Check for Fatal Error in Plugins
        foreach ($errorLogs as $log) {
            if (str_contains($log, 'Fatal error') || str_contains($log, 'Parse error')) {
                preg_match('/in (\/wp-content\/plugins\/([^\/]+)\/)/', $log, $matches);
                $pluginSlug = $matches[2] ?? 'unknown-plugin';

                $rootCauses[] = [
                    'id' => 'rc-offline-' . uniqid(),
                    'title' => sprintf('Fatal PHP Error in Plugin "%s"', $pluginSlug),
                    'pluginOrTheme' => $pluginSlug,
                    'confidence' => 95,
                    'severity' => 'CRITICAL',
                    'evidence' => [$log],
                    'recommendedAction' => sprintf('Disable plugin "%s" via emergency recovery queue.', $pluginSlug),
                    'riskScore' => 'LOW'
                ];

                $recommendedRepairs[] = [
                    'id' => 'rep-' . uniqid(),
                    'title' => sprintf('Safely Disable Plugin "%s"', $pluginSlug),
                    'actionType' => 'disable_plugin',
                    'target' => $pluginSlug,
                    'riskScore' => 10
                ];
            }
        }

        // Rule 2: Check for .htaccess corruption
        if (!empty($snapshotData['filesystem']['htaccessWritable'])) {
            $recommendedRepairs[] = [
                'id' => 'rep-htaccess',
                'title' => 'Flush & Rebuild .htaccess Rewrite Rules',
                'actionType' => 'repair_htaccess',
                'target' => 'Core .htaccess',
                'riskScore' => 5
            ];
        }

        return [
            'mode' => 'offline',
            'summary' => 'Offline Heuristic Diagnostics: Analyzed local PHP stack trace without cloud AI dependencies.',
            'incidentGraph' => ['rootCauses' => $rootCauses],
            'recommendedRepairs' => $recommendedRepairs
        ];
    }
}
