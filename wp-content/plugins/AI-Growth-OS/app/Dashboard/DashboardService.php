<?php

declare(strict_types=1);

namespace AIGrowthOS\Dashboard;

use AIGrowthOS\Domain\Entity\Score;
use AIGrowthOS\Infrastructure\Logging\Logger;

class DashboardService
{
    public function getScores(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_growth_os_scores';

        $results = $wpdb->get_results(
            "SELECT s1.* FROM $table s1
             INNER JOIN (
                 SELECT type, MAX(calculated_at) AS max_calc
                 FROM $table GROUP BY type
             ) s2 ON s1.type = s2.type AND s1.calculated_at = s2.max_calc",
            ARRAY_A
        );

        return array_map(fn($row) => (new Score(
            id: (int) $row['id'],
            type: $row['type'],
            value: (float) $row['value'],
            previousValue: $row['previous_value'] ? (float) $row['previous_value'] : null,
            calculatedAt: new \DateTimeImmutable($row['calculated_at']),
        ))->toArray(), $results ?: []);
    }

    public function getRecommendations(int $limit = 10): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_growth_os_recommendations';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE status != 'implemented' ORDER BY priority_score DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        ) ?: [];
    }

    public function getRecentAudits(int $limit = 5): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_growth_os_audit_runs';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        ) ?: [];
    }

    public function getMetrics(): array
    {
        return [
            'scores'          => $this->getScores(),
            'recommendations' => $this->getRecommendations(),
            'recent_audits'   => $this->getRecentAudits(),
        ];
    }
}
