<?php

declare(strict_types=1);

namespace AIGrowthOS;

final class Deactivator
{
    public static function deactivate(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        flush_rewrite_rules();
    }
}
