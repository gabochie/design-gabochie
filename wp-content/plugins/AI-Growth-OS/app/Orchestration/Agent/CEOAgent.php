<?php

declare(strict_types=1);

namespace AIGrowthOS\Orchestration\Agent;

class CEOAgent extends BaseAgent
{
    public function __construct(\AIGrowthOS\Infrastructure\AI\Client\AIOrchestrator $ai)
    {
        parent::__construct(
            name: 'CEO',
            role: 'Chief Executive Officer',
            mission: 'Analyze all business metrics, audit results, and recommendations to provide strategic direction, prioritise growth initiatives, and generate executive summaries for the business owner.',
            ai: $ai,
        );
    }

    public function generateExecutiveSummary(array $scores, array $recommendations, array $auditResults): string
    {
        return $this->think(
            'Generate a concise executive summary of the current business state.',
            [
                'scores'          => $scores,
                'recommendations' => array_slice($recommendations, 0, 5),
                'audit_results'   => array_slice($auditResults, 0, 5),
            ]
        );
    }

    public function prioritiseRecommendations(array $recommendations): string
    {
        return $this->think(
            'Prioritise these recommendations by expected revenue impact, effort, and confidence.',
            ['recommendations' => $recommendations]
        );
    }

    public function generateWeeklyReport(array $scores, array $activities, array $highlights): string
    {
        return $this->think(
            'Generate a weekly executive report covering key metrics, activities, and highlights.',
            [
                'scores'     => $scores,
                'activities' => $activities,
                'highlights' => $highlights,
            ]
        );
    }
}
