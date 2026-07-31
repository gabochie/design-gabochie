<?php

declare(strict_types=1);

namespace AIGrowthOS;

use AIGrowthOS\Infrastructure\Persistence\Schema\SchemaManager;

final class Activator
{
    public static function activate(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        (new SchemaManager())->createTables();

        add_option('ai_growth_os_version', AI_GROWTH_OS_VERSION);
        add_option('ai_growth_os_activated_at', current_time('mysql'));

        flush_rewrite_rules();
    }
}
