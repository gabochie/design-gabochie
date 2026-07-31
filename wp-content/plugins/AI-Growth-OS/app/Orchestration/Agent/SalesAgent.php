<?php

declare(strict_types=1);

namespace AIGrowthOS\Orchestration\Agent;

class SalesAgent extends BaseAgent
{
    public function __construct(\AIGrowthOS\Infrastructure\AI\Client\AIOrchestrator $ai)
    {
        parent::__construct(
            name: 'Sales Optimiser',
            role: 'Sales Optimisation Specialist',
            mission: 'Analyse sales processes, pricing strategies, and checkout flows to maximise revenue and customer lifetime value.',
            ai: $ai,
        );
    }

    public function analysePricing(array $products, array $competitors): string
    {
        return $this->think(
            'Analyse pricing strategy and recommend optimisations.',
            ['products' => $products, 'competitors' => $competitors]
        );
    }

    public function optimiseCheckout(array $checkoutData): string
    {
        return $this->think(
            'Analyse checkout flow and suggest improvements to reduce abandonment.',
            ['checkout_data' => $checkoutData]
        );
    }

    public function identifyUpsellOpportunities(array $customerData): string
    {
        return $this->think(
            'Identify upsell and cross-sell opportunities based on customer data.',
            ['customer_data' => $customerData]
        );
    }
}
