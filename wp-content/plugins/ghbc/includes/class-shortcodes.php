<?php
defined('ABSPATH') || exit;

class GHBC_Shortcodes {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('ghbc_chat', [$this, 'render_chat']);
        add_shortcode('ghbc_exam', [$this, 'render_exam']);
        add_shortcode('ghbc_small_building', [$this, 'render_small_building']);
        add_shortcode('ghbc_fire_safety', [$this, 'render_fire_safety']);
        add_shortcode('ghbc_tools', [$this, 'render_tools_hub']);
        add_shortcode('ghbc_travel_distance', [$this, 'render_travel_distance']);
        add_shortcode('ghbc_exit_width', [$this, 'render_exit_width']);
        add_shortcode('ghbc_fire_rating', [$this, 'render_fire_rating']);
        add_shortcode('ghbc_sprinkler_check', [$this, 'render_sprinkler_check']);
        add_shortcode('ghbc_code_search', [$this, 'render_code_search']);
        add_shortcode('ghbc_clause_lookup', [$this, 'render_clause_lookup']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        global $post;
        if (is_admin() || !$post) return;
        $shortcodes = ['ghbc_chat','ghbc_exam','ghbc_small_building','ghbc_fire_safety','ghbc_tools','ghbc_travel_distance','ghbc_exit_width','ghbc_fire_rating','ghbc_sprinkler_check','ghbc_code_search','ghbc_clause_lookup'];
        $found = false;
        foreach ($shortcodes as $tag) {
            if (has_shortcode($post->post_content, $tag) || strpos($post->post_content, "[$tag") !== false) {
                $found = true;
                break;
            }
        }
        if (!$found) return;
        $this->do_enqueue();
    }

    private function do_enqueue() {
        wp_enqueue_style('ghbc', GHBC_URL . 'assets/css/ghbc.css', [], GHBC_VERSION);
        wp_enqueue_script('ghbc', GHBC_URL . 'assets/js/ghbc.js', ['jquery'], GHBC_VERSION, true);
        wp_localize_script('ghbc', 'ghbc_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ghbc_nonce'),
        ]);
    }

    public function render_chat($atts) {
        $product = $atts['product'] ?? 'compliance';
        ob_start();
        include GHBC_PATH . 'templates/chat-ui.php';
        return ob_get_clean();
    }

    public function render_exam() {
        ob_start();
        include GHBC_PATH . 'templates/exam-ui.php';
        return ob_get_clean();
    }

    public function render_small_building() {
        ob_start();
        include GHBC_PATH . 'templates/small-bldg-ui.php';
        return ob_get_clean();
    }

    public function render_fire_safety() {
        ob_start();
        include GHBC_PATH . 'templates/fire-safety-ui.php';
        return ob_get_clean();
    }

    public function render_tools_hub() {
        ob_start();
        include GHBC_PATH . 'templates/tools-hub-ui.php';
        return ob_get_clean();
    }

    public function render_travel_distance() {
        ob_start();
        include GHBC_PATH . 'templates/travel-distance-ui.php';
        return ob_get_clean();
    }

    public function render_exit_width() {
        ob_start();
        include GHBC_PATH . 'templates/exit-width-ui.php';
        return ob_get_clean();
    }

    public function render_fire_rating() {
        ob_start();
        include GHBC_PATH . 'templates/fire-rating-ui.php';
        return ob_get_clean();
    }

    public function render_sprinkler_check() {
        ob_start();
        include GHBC_PATH . 'templates/sprinkler-check-ui.php';
        return ob_get_clean();
    }

    public function render_code_search() {
        ob_start();
        include GHBC_PATH . 'templates/code-search-ui.php';
        return ob_get_clean();
    }

    public function render_clause_lookup() {
        ob_start();
        include GHBC_PATH . 'templates/clause-lookup-ui.php';
        return ob_get_clean();
    }

}
