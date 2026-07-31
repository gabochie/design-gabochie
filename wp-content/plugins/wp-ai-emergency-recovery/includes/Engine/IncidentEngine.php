<?php

declare(strict_types=1);

namespace WP_AI_Emergency_Recovery\Engine;

use WP_AI_Emergency_Recovery\AI\Providers\GeminiProvider;
use WP_AI_Emergency_Recovery\AI\Providers\OfflineProvider;

final class IncidentEngine {
    private SnapshotEngine $snapshotEngine;
    private LoggingEngine $loggingEngine;

    public function __construct(SnapshotEngine $snapshotEngine, LoggingEngine $loggingEngine) {
        $this->snapshotEngine = $snapshotEngine;
        $this->loggingEngine = $loggingEngine;
    }

    /**
     * Analyze current system state and recent error logs
     *
     * @return array<string, mixed> Structured diagnosis report
     */
    public function analyzeIncident(): array {
        $snapshot = $this->snapshotEngine->createSnapshot();
        $apiKey = (string) get_option('wp_ai_recovery_gemini_key', '');
        
        if (!empty($apiKey)) {
            $provider = new GeminiProvider($apiKey);
        } else {
            $provider = new OfflineProvider();
        }

        $errorLogs = $snapshot['errorLogs'] ?? [];
        $diagnosis = $provider->diagnoseIncident($snapshot, $errorLogs);

        $this->loggingEngine->log(
            'AI_DECISION',
            'Incident Analysis Completed',
            sprintf('Diagnostic Engine evaluated system via provider "%s".', $provider->getProviderName()),
            ['provider' => $provider->getProviderName(), 'snapshotId' => $snapshot['id'] ?? '']
        );

        return $diagnosis;
    }

    public function getSnapshotEngine(): SnapshotEngine {
        return $this->snapshotEngine;
    }

    public function getLoggingEngine(): LoggingEngine {
        return $this->loggingEngine;
    }
}
