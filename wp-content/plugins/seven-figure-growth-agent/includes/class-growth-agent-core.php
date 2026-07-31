<?php
class Growth_Agent_Core {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_api_hooks();
        $this->define_cron_hooks();
    }

    private function load_dependencies() {
        require_once GROWTH_AGENT_PATH . 'includes/class-growth-agent-db.php';
        require_once GROWTH_AGENT_PATH . 'includes/class-growth-agent-security.php';
        require_once GROWTH_AGENT_PATH . 'includes/class-growth-agent-rest-api.php';
        require_once GROWTH_AGENT_PATH . 'includes/class-growth-agent-cron.php';
        require_once GROWTH_AGENT_PATH . 'ai/providers/class-provider-abstraction.php';
        require_once GROWTH_AGENT_PATH . 'admin/class-growth-agent-admin.php';
    }

    private function define_admin_hooks() {
        if (is_admin()) {
            $admin = new Growth_Agent_Admin();
            add_action('admin_menu', array($admin, 'add_plugin_admin_menu'));
            add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles_and_scripts'));
        }
    }

    private function define_api_hooks() {
        $api = new Growth_Agent_Rest_Api();
        add_action('rest_api_init', array($api, 'register_routes'));
    }

    private function define_cron_hooks() {
        $cron = new Growth_Agent_Cron();
        add_action('growth_agent_daily_cron', array($cron, 'execute_daily_analysis'));
        add_action('growth_agent_weekly_cron', array($cron, 'execute_weekly_review'));
    }

    public function run() {
        // Core execution loop
    }

    public static function activate() {
        require_once GROWTH_AGENT_PATH . 'includes/class-growth-agent-db.php';
        Growth_Agent_DB::create_tables();

        require_once GROWTH_AGENT_PATH . 'includes/class-growth-agent-cron.php';
        Growth_Agent_Cron::schedule_events();

        update_option('growth_agent_db_version', GROWTH_AGENT_DB_VERSION);
    }

    public static function deactivate() {
        require_once GROWTH_AGENT_PATH . 'includes/class-growth-agent-cron.php';
        Growth_Agent_Cron::unschedule_events();
    }
}
