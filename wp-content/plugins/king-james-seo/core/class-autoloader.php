<?php

namespace KingJamesSEO\Core;

defined('ABSPATH') || exit;

class Autoloader {
    private static array $prefixes = [];

    public static function register(): void {
        spl_autoload_register([self::class, 'autoload']);
    }

    public static function add_prefix(string $namespace, string $base_dir): void {
        self::$prefixes[rtrim($namespace, '\\') . '\\'] = rtrim($base_dir, '/\\') . '/';
    }

    public static function autoload(string $class): void {
        foreach (self::$prefixes as $prefix => $base_dir) {
            if (strpos($class, $prefix) === 0) {
                $relative = substr($class, strlen($prefix));
                $parts = explode('\\', $relative);
                $class_name = array_pop($parts);
                $subdir = !empty($parts) ? implode('/', $parts) . '/' : '';
                $snake = self::camel_to_snake($class_name);

                $variants = [
                    $base_dir . $subdir . 'class-' . $snake . '.php',
                    $base_dir . $subdir . 'interface-' . $snake . '.php',
                    $base_dir . $subdir . 'trait-' . $snake . '.php',
                    $base_dir . $subdir . $snake . '.php',
                ];

                foreach ($variants as $file) {
                    if (file_exists($file)) {
                        require_once $file;
                        return;
                    }
                }
            }
        }
    }

    private static function camel_to_snake(string $name): string {
        $name = str_replace('_', '-', $name);
        $name = preg_replace('/([a-z])([A-Z])/', '$1-$2', $name);
        $name = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1-$2', $name);
        $name = preg_replace('/-+/', '-', $name);
        return strtolower(trim($name, '-'));
    }
}

Autoloader::add_prefix('KingJamesSEO\\Core\\', KJS_PLUGIN_DIR . 'core');
Autoloader::add_prefix('KingJamesSEO\\Admin\\', KJS_PLUGIN_DIR . 'admin');
Autoloader::add_prefix('KingJamesSEO\\Modules\\', KJS_PLUGIN_DIR . 'modules');
Autoloader::add_prefix('KingJamesSEO\\Public_\\', KJS_PLUGIN_DIR . 'public');
Autoloader::add_prefix('KingJamesSEO\\Includes\\', KJS_PLUGIN_DIR . 'includes');
