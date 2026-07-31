<?php

declare(strict_types=1);

namespace AIGrowthOS\Infrastructure\Logging;

final class Logger
{
    private const TABLE = 'ai_growth_os_logs';

    private static ?string $traceId = null;

    public static function getTraceId(): string
    {
        if (self::$traceId === null) {
            self::$traceId = bin2hex(random_bytes(16));
        }
        return self::$traceId;
    }

    public static function emergency(string $message, array $context = []): void
    {
        self::log('emergency', $message, $context);
    }

    public static function alert(string $message, array $context = []): void
    {
        self::log('alert', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log('critical', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function notice(string $message, array $context = []): void
    {
        self::log('notice', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    private static function log(string $level, string $message, array $context = []): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . self::TABLE,
            [
                'level'     => $level,
                'message'   => $message,
                'context'   => wp_json_encode($context),
                'trace_id'  => self::getTraceId(),
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );

        // Also send to WP debug log in dev
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[AI Growth OS] [$level] $message " . wp_json_encode($context));
        }
    }
}
