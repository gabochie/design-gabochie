<?php

declare(strict_types=1);

namespace WP_AI_Emergency_Recovery\Engine;

final class ValidationEngine {
    public function runValidationSuite(): array {
        $homeUrl = home_url('/');
        $restUrl = rest_url('wp/v2/posts');

        $checks = [
            $this->checkEndpoint('Homepage Load', $homeUrl),
            $this->checkEndpoint('REST API Root', $restUrl),
            $this->checkEndpoint('WP Login Page', wp_login_url())
        ];

        $allPassed = true;
        foreach ($checks as $check) {
            if ($check['status'] !== 'passed') {
                $allPassed = false;
                break;
            }
        }

        return [
            'allPassed' => $allPassed,
            'checks' => $checks
        ];
    }

    private function checkEndpoint(string $name, string $url): array {
        $response = wp_remote_get($url, ['timeout' => 5, 'sslverify' => false]);

        if (is_wp_error($response)) {
            return [
                'name' => $name,
                'endpoint' => $url,
                'status' => 'failed',
                'httpCode' => 0,
                'message' => $response->get_error_message()
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $status = ($code >= 200 && $code < 400) ? 'passed' : 'failed';

        return [
            'name' => $name,
            'endpoint' => $url,
            'status' => $status,
            'httpCode' => $code,
            'message' => sprintf('HTTP status %d returned.', $code)
        ];
    }
}
