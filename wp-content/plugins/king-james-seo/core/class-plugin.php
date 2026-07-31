<?php

namespace KingJamesSEO\Core;

defined('ABSPATH') || exit;

class Plugin {
    private static ?Plugin $instance = null;
    private Module_Manager $module_manager;
    private Database $database;
    private Logger $logger;
    private Guardrails $guardrails;
    private Pipeline $pipeline;
    private bool $initialized = false;

    public static function instance(): Plugin {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->database       = new Database();
        $this->logger         = new Logger();
        $this->guardrails     = new Guardrails();
        $this->pipeline       = new Pipeline();
        $this->module_manager = new Module_Manager();

        add_action('plugins_loaded', [$this, 'init'], 0);
    }

    public function init(): void {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        $this->database->create_tables();
        $this->load_textdomain();
        $this->module_manager->register_all();
        $this->module_manager->init_active();

        if (is_admin()) {
            \KingJamesSEO\Admin\Admin_Ajax::init();
            new \KingJamesSEO\Admin\Admin();
        }

        $public = \KingJamesSEO\Public_\Public_Handler::instance();
        $public->init();
    }

    public static function activate(): void {
        $instance = self::instance();
        $instance->database->create_tables();
        $instance->guardrails->set_defaults();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public function database(): Database {
        return $this->database;
    }

    public function logger(): Logger {
        return $this->logger;
    }

    public function guardrails(): Guardrails {
        return $this->guardrails;
    }

    public function pipeline(): Pipeline {
        return $this->pipeline;
    }

    public function module_manager(): Module_Manager {
        return $this->module_manager;
    }

    private function load_textdomain(): void {
        load_plugin_textdomain(
            'king-james-seo',
            false,
            dirname(KJS_PLUGIN_BASENAME) . '/languages'
        );
    }
}
