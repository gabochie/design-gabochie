<?php

declare(strict_types=1);

namespace AIGrowthOS\ReverseEngineering;

use AIGrowthOS\Infrastructure\AI\Client\AIOrchestrator;
use AIGrowthOS\Infrastructure\Logging\Logger;

class WebsiteAnalyzer
{
    public function __construct(
        private readonly AIOrchestrator $ai,
    ) {}

    public function analyseAll(): array
    {
        Logger::info('Reverse engineering website analysis started');

        return [
            'business_logic'    => $this->reverseEngineerBusinessLogic(),
            'revenue_bottlenecks' => $this->findRevenueBottlenecks(),
            'ux_issues'         => $this->findUXIssues(),
            'technical_debt'    => $this->assessTechnicalDebt(),
            'revenue_opportunities' => $this->findRevenueOpportunities(),
            'blind_spots'       => $this->findBlindSpots(),
        ];
    }

    private function reverseEngineerBusinessLogic(): array
    {
        try {
            $siteUrl = get_site_url();
            $response = $this->ai->complete([
                ['role' => 'system', 'content' => 'Reverse engineer the business model and logic from the website. Identify how value is created, delivered, and captured.'],
                ['role' => 'user', 'content' => "Website: $siteUrl\n\nAnalyse:\n1. Value proposition\n2. Revenue model\n3. Customer segments\n4. Key partnerships\n5. Cost structure\n\nReturn JSON with findings and confidence."],
            ], ['temperature' => 0.3]);

            return json_decode($response->getContent(), true) ?? ['business_model' => 'unknown'];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function findRevenueBottlenecks(): array
    {
        return [
            'checkout_abandonment' => 'Check checkout flow for friction points',
            'pricing_optimisation' => 'Review pricing page for clarity and urgency',
            'missing_upsells'      => 'Identify missing upsell opportunities',
        ];
    }

    private function findUXIssues(): array
    {
        return [
            'navigation'   => 'Analyse navigation structure and information architecture',
            'mobile_ux'    => 'Check mobile responsiveness and touch targets',
            'forms'        => 'Review form length and field requirements',
            'page_speed'   => 'Analyse Core Web Vitals and loading performance',
        ];
    }

    private function assessTechnicalDebt(): array
    {
        global $wpdb;

        $pluginCount = count(get_option('active_plugins', []));
        $theme = wp_get_theme();
        $phpVersion = PHP_VERSION;
        $wpVersion = get_bloginfo('version');
        $dbQueries = $wpdb->num_queries;

        return [
            'plugin_count'      => $pluginCount,
            'theme'             => $theme->get('Name') . ' ' . $theme->get('Version'),
            'php_version'       => $phpVersion,
            'wordpress_version' => $wpVersion,
            'estimated_technical_debt' => 'medium',
            'suggestions'       => [
                'Remove unused plugins',
                'Update PHP to latest supported version',
                'Audit and optimise database queries',
                'Implement object caching',
            ],
        ];
    }

    private function findRevenueOpportunities(): array
    {
        return [
            'pricing_optimisation' => 'Analyse competitor pricing and adjust',
            'landing_pages'        => 'Create targeted landing pages for key segments',
            'email_capture'        => 'Add exit-intent popups for lead capture',
            'subscription_model'   => 'Consider recurring revenue models',
        ];
    }

    private function findBlindSpots(): array
    {
        return [
            'competitor_analysis'   => 'Regular competitor monitoring not active',
            'customer_feedback'     => 'Systematic customer feedback collection not in place',
            'market_positioning'    => 'Market positioning not regularly reviewed',
            'content_strategy'      => 'Content strategy not data-driven',
            'ai_optimisation'       => 'LLM discoverability not tracked',
        ];
    }
}
