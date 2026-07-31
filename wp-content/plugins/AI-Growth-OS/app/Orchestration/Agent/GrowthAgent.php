<?php

declare(strict_types=1);

namespace AIGrowthOS\Orchestration\Agent;

class GrowthAgent extends BaseAgent
{
    public function __construct(\AIGrowthOS\Infrastructure\AI\Client\AIOrchestrator $ai)
    {
        parent::__construct(
            name: 'Growth Strategist',
            role: 'Growth Marketing Strategist',
            mission: 'Identify growth opportunities, optimise conversion funnels, and recommend data-driven growth strategies.',
            ai: $ai,
        );
    }

    public function identifyGrowthOpportunities(array $analytics): string
    {
        return $this->think(
            'Analyse website analytics and identify top growth opportunities.',
            ['analytics' => $analytics]
        );
    }

    public function optimiseFunnel(string $funnelStage, array $data): string
    {
        return $this->think(
            "Optimise the $funnelStage stage of the conversion funnel.",
            ['data' => $data]
        );
    }

    public function suggestABTests(): string
    {
        return $this->think(
            'Suggest A/B test ideas to improve conversion rates based on current site analysis.',
            []
        );
    }
}
