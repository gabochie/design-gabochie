<?php

declare(strict_types=1);

namespace AIGrowthOS\SelfImprovement;

use AIGrowthOS\Infrastructure\AI\Client\AIOrchestrator;
use AIGrowthOS\Infrastructure\Logging\Logger;

class SelfImprovementEngine
{
    public function __construct(
        private readonly AIOrchestrator $ai,
    ) {}

    public function analyseAndSuggest(): array
    {
        Logger::info('Self-improvement analysis started');

        $suggestions = [];

        try {
            $response = $this->ai->complete([
                ['role' => 'system', 'content' => 'You are a self-improvement engine for AI Growth OS. Analyse the system and suggest improvements to the plugin itself.'],
                ['role' => 'user', 'content' => $this->buildSystemAnalysisPrompt()],
            ], ['temperature' => 0.4]);

            $result = json_decode($response->getContent(), true);
            if ($result && is_array($result)) {
                $suggestions = $result;
            }
        } catch (\Throwable $e) {
            Logger::error('Self-improvement analysis failed', ['error' => $e->getMessage()]);
        }

        return $suggestions;
    }

    private function buildSystemAnalysisPrompt(): string
    {
        global $wpdb;

        $version = get_option('ai_growth_os_version', 'unknown');
        $agentCount = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ai_growth_os_agents");
        $auditCount = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ai_growth_os_audit_runs");
        $recCount = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ai_growth_os_recommendations WHERE status = 'draft'");
        $wpVersion = get_bloginfo('version');
        $plugins = get_option('active_plugins', []);
        $theme = wp_get_theme()->get('Name');

        return json_encode([
            'system' => [
                'plugin_version'    => $version,
                'wordpress_version' => $wpVersion,
                'active_plugins'    => count($plugins),
                'theme'             => $theme,
            ],
            'usage' => [
                'agents'             => $agentCount,
                'total_audits'       => $auditCount,
                'pending_recommendations' => $recCount,
            ],
            'task' => 'Analyse the AI Growth OS system and suggest improvements. Return JSON array of objects with title, description, category, and priority fields.',
        ]);
    }
}
