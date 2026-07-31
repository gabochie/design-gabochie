<?php

declare(strict_types=1);

namespace WP_AI_Emergency_Recovery\AI;

interface ProviderInterface {
    public function getProviderName(): string;
    
    /**
     * Analyze incident data and return structured JSON diagnosis
     * 
     * @param array<string, mixed> $snapshotData
     * @param array<string> $errorLogs
     * @return array<string, mixed>
     */
    public function diagnoseIncident(array $snapshotData, array $errorLogs): array;

    public function isAvailable(): bool;
}
