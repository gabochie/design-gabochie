<?php

declare(strict_types=1);

namespace AIGrowthOS\Orchestration\Agent;

class CopywritingAgent extends BaseAgent
{
    public function __construct(\AIGrowthOS\Infrastructure\AI\Client\AIOrchestrator $ai)
    {
        parent::__construct(
            name: 'Copywriter',
            role: 'Conversion Copywriting Specialist',
            mission: 'Analyse and improve all website copy to maximise conversions, strengthen brand messaging, and enhance customer trust.',
            ai: $ai,
        );
    }

    public function analyseCopy(string $content, string $context): string
    {
        return $this->think(
            'Analyse copy effectiveness and suggest improvements.',
            ['content' => substr($content, 0, 3000), 'context' => $context]
        );
    }

    public function improveCTA(string $currentCta, string $pageContext): string
    {
        return $this->think(
            'Suggest improved call-to-action for better conversion.',
            ['current_cta' => $currentCta, 'page_context' => $pageContext]
        );
    }

    public function generateLandingPageCopy(string $product, string $audience): string
    {
        return $this->think(
            'Generate high-converting landing page copy.',
            ['product' => $product, 'target_audience' => $audience]
        );
    }
}
