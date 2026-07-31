<?php

declare(strict_types=1);

namespace AIGrowthOS\Orchestration\Agent;

class SEOAgent extends BaseAgent
{
    public function __construct(\AIGrowthOS\Infrastructure\AI\Client\AIOrchestrator $ai)
    {
        parent::__construct(
            name: 'SEO Optimiser',
            role: 'Search Engine Optimisation Specialist',
            mission: 'Analyse and improve search engine visibility, optimise content for organic traffic, and keep pace with algorithm changes.',
            ai: $ai,
        );
    }

    public function analyseOnPageSeo(string $url, string $content): string
    {
        return $this->think(
            'Analyse on-page SEO for this URL.',
            ['url' => $url, 'content' => substr($content, 0, 3000)]
        );
    }

    public function suggestKeywords(string $niche, string $topic): string
    {
        return $this->think(
            'Suggest keyword opportunities with search intent analysis.',
            ['niche' => $niche, 'topic' => $topic]
        );
    }

    public function optimiseContent(string $content, string $targetKeyword): string
    {
        return $this->think(
            "Optimise this content for the keyword: $targetKeyword",
            ['content' => substr($content, 0, 3000)]
        );
    }
}
