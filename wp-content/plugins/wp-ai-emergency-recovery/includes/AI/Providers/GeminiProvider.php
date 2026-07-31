<?php

declare(strict_types=1);

namespace WP_AI_Emergency_Recovery\AI\Providers;

use WP_AI_Emergency_Recovery\AI\ProviderInterface;

final class GeminiProvider implements ProviderInterface {
    private string $apiKey;
    private string $model;

    public function __construct(string $apiKey, string $model = 'gemini-3.6-flash') {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function getProviderName(): string {
        return 'Google Gemini AI';
    }

    public function isAvailable(): bool {
        return !empty($this->apiKey);
    }

    public function diagnoseIncident(array $snapshotData, array $errorLogs): array {
        if (!$this->isAvailable()) {
            return (new OfflineProvider())->diagnoseIncident($snapshotData, $errorLogs);
        }

        $url = sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s', $this->model, $this->apiKey);

        $prompt = "You are a Senior WordPress Core Site Reliability Engineer. Analyze this WordPress incident snapshot and PHP error logs:

";
        $prompt .= "SNAPSHOT: " . wp_json_encode($snapshotData) . "

";
        $prompt .= "LOGS: " . implode("
", $errorLogs) . "

";
        $prompt .= "Return JSON with rootCauses, confidence scores, severity, evidence, and safe recommended repair actions.";

        $response = wp_remote_post($url, [
            'timeout' => 20,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode([
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json'
                ]
            ])
        ]);

        if (is_wp_error($response)) {
            // Fallback to offline provider on network failure
            return (new OfflineProvider())->diagnoseIncident($snapshotData, $errorLogs);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $jsonText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($jsonText) {
            $parsed = json_decode($jsonText, true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        return (new OfflineProvider())->diagnoseIncident($snapshotData, $errorLogs);
    }
}
