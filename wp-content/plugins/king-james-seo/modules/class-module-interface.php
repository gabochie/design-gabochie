<?php

namespace KingJamesSEO\Modules;

defined('ABSPATH') || exit;

interface Module_Interface {
    public static function instance(): self;
    public function init(): void;
    public function get_module_id(): string;
}
