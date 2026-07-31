<?php

declare(strict_types=1);

namespace AIGrowthOS\Observability;

use AIGrowthOS\Infrastructure\Logging\Logger;

class MetricsCollector
{
    public function record(string $name, float $value, array $labels = []): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'ai_growth_os_metrics',
            [
                'metric_name'  => $name,
                'metric_value' => $value,
                'labels'       => !empty($labels) ? wp_json_encode($labels) : null,
                'recorded_at'  => current_time('mysql'),
            ],
            ['%s', '%f', '%s', '%s']
        );
    }

    public function recordTiming(string $name, float $durationMs, array $labels = []): void
    {
        $this->record("{$name}_duration", $durationMs, $labels);
    }

    public function increment(string $name, int $by = 1, array $labels = []): void
    {
        $this->record($name, $by, $labels);
    }

    public function getMetricHistory(string $name, int $limit = 100): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_growth_os_metrics';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE metric_name = %s ORDER BY recorded_at DESC LIMIT %d",
                $name,
                $limit
            ),
            ARRAY_A
        ) ?: [];
    }

    public function getAverage(string $name, string $since = '-24 hours'): float
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_growth_os_metrics';
        $sinceDate = date('Y-m-d H:i:s', strtotime($since));

        return (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(metric_value) FROM $table WHERE metric_name = %s AND recorded_at >= %s",
                $name,
                $sinceDate
            )
        );
    }
}
