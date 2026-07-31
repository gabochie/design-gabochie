<?php

declare(strict_types=1);

namespace AIGrowthOS\Audit;

use AIGrowthOS\Domain\Entity\AuditRun;
use AIGrowthOS\Domain\Entity\Recommendation;
use AIGrowthOS\Domain\Event\AuditCompleted;
use AIGrowthOS\Infrastructure\AI\Client\AIOrchestrator;
use AIGrowthOS\Infrastructure\Logging\Logger;
use AIGrowthOS\Orchestration\Event\EventBus;

class AuditService
{
    public function __construct(
        private readonly AIOrchestrator $ai,
        private readonly EventBus $eventBus,
    ) {}

    public function runFullAudit(): AuditRun
    {
        $audit = $this->createAuditRun('full');

        try {
            $audit->start();
            $this->updateAuditRun($audit);

            $results = array_merge(
                $this->auditBusinessModel(),
                $this->auditSeo(),
                $this->auditPerformance(),
                $this->auditSecurity(),
                $this->auditAccessibility(),
                $this->auditCopywriting(),
                $this->auditWooCommerce(),
                $this->auditUX(),
            );

            $audit->complete($results);
            $this->updateAuditRun($audit);

            $this->eventBus->dispatch(new AuditCompleted(
                auditRunId: $audit->getId(),
                auditType: 'full',
                findingsCount: count($results),
            ));

            Logger::info('Full audit completed', ['findings' => count($results)]);
        } catch (\Throwable $e) {
            $audit->fail();
            $this->updateAuditRun($audit);
            Logger::error('Audit failed', ['error' => $e->getMessage()]);
        }

        return $audit;
    }

    public function auditBusinessModel(): array
    {
        return $this->runAiAudit('Business Model', [
            'Analyse the business model, pricing, offers, and positioning.',
            'Identify revenue bottlenecks, missing offers, and pricing optimisation opportunities.',
            'Check subscription and membership models.',
        ]);
    }

    public function auditSeo(): array
    {
        return $this->runAiAudit('SEO', [
            'Analyse on-page SEO, meta tags, headings, and content structure.',
            'Check schema markup and structured data.',
            'Review sitemap and robots.txt.',
            'Analyse internal linking structure.',
        ]);
    }

    public function auditPerformance(): array
    {
        return $this->runAiAudit('Performance', [
            'Analyse page load speed and Core Web Vitals.',
            'Check image optimisation, caching, and CDN usage.',
            'Review script and style loading (render-blocking).',
            'Check database query performance.',
        ]);
    }

    public function auditSecurity(): array
    {
        return $this->runAiAudit('Security', [
            'Check for outdated plugins and themes.',
            'Review user roles and permissions.',
            'Analyse login security and rate limiting.',
            'Check SSL, headers, and security configurations.',
        ]);
    }

    public function auditAccessibility(): array
    {
        return $this->runAiAudit('Accessibility', [
            'Analyse WCAG compliance (contrast, alt text, ARIA labels).',
            'Check keyboard navigation.',
            'Review heading hierarchy.',
            'Check form labels and error messages.',
        ]);
    }

    public function auditCopywriting(): array
    {
        return $this->runAiAudit('Copywriting', [
            'Analyse homepage copy, value proposition, and CTAs.',
            'Check brand messaging consistency.',
            'Review landing page effectiveness.',
            'Analyse trust signals and social proof.',
        ]);
    }

    public function auditWooCommerce(): array
    {
        return $this->runAiAudit('WooCommerce', [
            'Check product page optimisation.',
            'Analyse checkout flow and abandonment points.',
            'Review upselling and cross-selling opportunities.',
            'Check subscription and membership setup.',
        ]);
    }

    public function auditUX(): array
    {
        return $this->runAiAudit('UX', [
            'Analyse navigation structure and user flow.',
            'Check form usability and conversion paths.',
            'Review mobile responsiveness.',
            'Analyse customer journey and friction points.',
        ]);
    }

    private function runAiAudit(string $category, array $questions): array
    {
        $findings = [];
        $siteUrl = get_site_url();
        $siteName = get_bloginfo('name');

        foreach ($questions as $question) {
            try {
                $response = $this->ai->complete([
                    ['role' => 'system', 'content' => "You are an expert $category auditor. Analyse the website and provide specific, actionable findings with severity (critical, high, medium, low) and a score from 0-100."],
                    ['role' => 'user', 'content' => "Website: $siteName ($siteUrl)\n\nTask: $question\n\nReturn JSON: {\"title\": string, \"description\": string, \"severity\": \"critical\"|\"high\"|\"medium\"|\"low\", \"score\": float}"],
                ], ['temperature' => 0.2]);

                $result = json_decode($response->getContent(), true);
                if ($result && isset($result['title'])) {
                    $findings[] = array_merge($result, ['category' => $category]);
                }
            } catch (\Throwable $e) {
                Logger::warning("Audit question failed", ['category' => $category, 'error' => $e->getMessage()]);
            }
        }

        return $findings;
    }

    private function createAuditRun(string $type): AuditRun
    {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'ai_growth_os_audit_runs',
            ['type' => $type, 'status' => 'pending'],
            ['%s', '%s']
        );
        return new AuditRun((int) $wpdb->insert_id, $type);
    }

    private function updateAuditRun(AuditRun $audit): void
    {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'ai_growth_os_audit_runs',
            $audit->toArray(),
            ['id' => $audit->getId()],
        );
    }
}
