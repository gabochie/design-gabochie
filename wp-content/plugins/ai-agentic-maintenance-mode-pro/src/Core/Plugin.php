<?php
namespace AIAgenticMaintenancePro\Core;

class Plugin {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run() {
        ( new BypassHandler() )->init();
        ( new AdminMenu() )->init();
        ( new \AIAgenticMaintenancePro\API\RestApi() )->init();
    }
}
