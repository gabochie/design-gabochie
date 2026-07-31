<?php

declare(strict_types=1);

namespace AIGrowthOS\Infrastructure\Security;

final class PluginSecurity
{
    public static function verifyNonce(string $nonce, string $action): bool
    {
        return wp_verify_nonce($nonce, $action) !== false;
    }

    public static function currentUserCan(string $capability): bool
    {
        return current_user_can($capability);
    }

    public static function requireAdmin(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized.', 403);
        }
    }

    public static function sanitizeInput(mixed $input, string $type = 'text'): mixed
    {
        return match ($type) {
            'text'     => sanitize_text_field((string) $input),
            'textarea' => sanitize_textarea_field((string) $input),
            'email'    => sanitize_email((string) $input),
            'url'      => esc_url_raw((string) $input),
            'key'      => sanitize_key((string) $input),
            'html'     => wp_kses_post((string) $input),
            'int'      => absint($input),
            'float'    => floatval($input),
            default    => $input,
        };
    }
}
