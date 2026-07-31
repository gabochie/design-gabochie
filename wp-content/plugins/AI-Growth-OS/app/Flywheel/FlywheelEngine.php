<?php

declare(strict_types=1);

namespace AIGrowthOS\Flywheel;

use AIGrowthOS\Infrastructure\AI\Client\AIOrchestrator;
use AIGrowthOS\Infrastructure\Logging\Logger;

class FlywheelEngine
{
    public function __construct(
        private readonly AIOrchestrator $ai,
    ) {}

    public function analyseFlywheel(): array
    {
        Logger::info('Flywheel analysis started');

        return [
            'customer_satisfaction' => $this->estimateSatisfaction(),
            'referral_potential'    => $this->estimateReferralPotential(),
            'loyalty_score'         => $this->calculateLoyaltyScore(),
            'virality_potential'    => $this->estimateVirality(),
            'recommendations'       => $this->generateRecommendations(),
        ];
    }

    private function estimateSatisfaction(): array
    {
        try {
            $siteUrl = get_site_url();
            $response = $this->ai->complete([
                ['role' => 'system', 'content' => 'Analyse customer satisfaction signals from the website content and structure.'],
                ['role' => 'user', 'content' => "Website: $siteUrl\n\nAnalyse trust signals, testimonials, reviews, support quality indicators, and community engagement.\n\nReturn JSON with score (0-100) and insights."],
            ], ['temperature' => 0.3]);

            return json_decode($response->getContent(), true) ?? ['score' => 50, 'insights' => []];
        } catch (\Throwable $e) {
            return ['score' => 0, 'error' => $e->getMessage()];
        }
    }

    private function estimateReferralPotential(): array
    {
        return [
            'has_referral_program' => $this->hasActivePlugin('affiliate'),
            'estimated_potential'  => 'medium',
            'suggestions'          => [
                'Implement a customer referral program',
                'Add social sharing to post-purchase flow',
                'Create affiliate recruitment pages',
            ],
        ];
    }

    private function calculateLoyaltyScore(): array
    {
        return [
            'score'       => 50,
            'factors'     => [
                'has_membership'  => $this->hasActivePlugin('membership'),
                'has_subscription' => $this->hasActivePlugin('subscription'),
                'has_rewards'     => $this->hasActivePlugin('rewards'),
            ],
        ];
    }

    private function estimateVirality(): array
    {
        return [
            'potential'     => 'medium',
            'sharing_mechanics' => [
                'social_sharing'      => true,
                'referral_loops'      => false,
                'user_generated_content' => false,
            ],
        ];
    }

    private function generateRecommendations(): array
    {
        return [
            'Implement customer referral program with incentives',
            'Add post-purchase review requests',
            'Create a loyalty/rewards membership tier',
            'Enable user-generated content campaigns',
            'Build community features (forums, groups)',
        ];
    }

    private function hasActivePlugin(string $type): bool
    {
        $plugins = get_option('active_plugins', []);
        $keywords = [
            'affiliate'    => ['affiliate', 'affiliatewp', 'easy-affiliate'],
            'membership'   => ['member', 'membership', 'paid-member'],
            'subscription' => ['subscription', 'woocommerce-subscription'],
            'rewards'      => ['reward', 'loyalty', 'points'],
        ];

        $keywordsToCheck = $keywords[$type] ?? [$type];
        foreach ($plugins as $plugin) {
            foreach ($keywordsToCheck as $kw) {
                if (str_contains($plugin, $kw)) {
                    return true;
                }
            }
        }
        return false;
    }
}
