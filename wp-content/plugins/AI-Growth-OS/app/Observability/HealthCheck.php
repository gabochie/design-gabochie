<?php

declare(strict_types=1);

namespace AIGrowthOS\Observability;

class HealthCheck
{
    public function run(): array
    {
        return [
            'status'   => $this->allOk() ? 'healthy' : 'degraded',
            'checks'   => [
                'database'   => $this->checkDatabase(),
                'ai_providers' => $this->checkAIProviders(),
                'cache'      => $this->checkCache(),
                'cron'       => $this->checkCron(),
            ],
            'timestamp' => current_time('mysql'),
        ];
    }

    private function allOk(): bool
    {
        $checks = $this->run();
        foreach ($checks['checks'] as $check) {
            if ($check['status'] !== 'ok') {
                return false;
            }
        }
        return true;
    }

    private function checkDatabase(): array
    {
        global $wpdb;
        try {
            $wpdb->get_var("SELECT 1 FROM {$wpdb->prefix}ai_growth_os_agents LIMIT 1");
            return ['status' => 'ok', 'message' => 'Database connected'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkAIProviders(): array
    {
        $available = [];
        $providers = ['openai', 'anthropic', 'gemini', 'ollama', 'openrouter'];

        foreach ($providers as $provider) {
            $const = 'AI_GROWTH_OS_' . strtoupper($provider) . '_KEY';
            if (defined($const) && constant($const) !== '') {
                $available[] = $provider;
            }
        }

        if (defined('AI_GROWTH_OS_OLLAMA_URL') && AI_GROWTH_OS_OLLAMA_URL !== '') {
            $available[] = 'ollama';
        }

        return [
            'status'    => count($available) > 0 ? 'ok' : 'warning',
            'providers' => $available,
            'message'   => count($available) > 0
                ? 'AI providers configured: ' . implode(', ', $available)
                : 'No AI providers configured',
        ];
    }

    private function checkCache(): array
    {
        $testKey = 'ai_growth_os_health_test';
        $testValue = 'ok';

        wp_cache_set($testKey, $testValue, 'ai-growth-os');
        $retrieved = wp_cache_get($testKey, 'ai-growth-os');

        wp_cache_delete($testKey, 'ai-growth-os');

        return [
            'status'  => $retrieved === $testValue ? 'ok' : 'warning',
            'message' => $retrieved === $testValue ? 'Object cache working' : 'Object cache not available',
        ];
    }

    private function checkCron(): array
    {
        $cron = _get_cron_array();
        $hasAgoEvents = false;

        foreach ($cron as $timestamp => $events) {
            foreach ($events as $hook => $event) {
                if (str_starts_with($hook, 'ai_growth_os_')) {
                    $hasAgoEvents = true;
                    break 2;
                }
            }
        }

        return [
            'status'  => 'ok',
            'message' => $hasAgoEvents ? 'Cron events registered' : 'No cron events yet',
        ];
    }
}
