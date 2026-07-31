<?php
defined('ABSPATH') || exit;

class GAR_Tracker {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_footer', array($this, 'diagnostic_output'));
        add_action('wp_ajax_gar_record_stats', array($this, 'ajax_record_stats'));
        add_action('wp_ajax_gar_get_stats', array($this, 'ajax_get_stats'));
    }

    public function get_placement_config() {
        $default = array(
            'positions' => array('after_title', 'after_content', 'sidebar'),
            'count' => 3,
            'lazy_load' => true,
        );
        return get_option('gar_placement_config', $default);
    }

    public function save_placement_config($config) {
        update_option('gar_placement_config', $config, false);
    }

    public function diagnostic_output() {
        if (!current_user_can('manage_options')) return;
        $config = $this->get_placement_config();
        ?>
        <div id="gar-diagnostic" style="display:none;" data-config='<?php echo esc_attr(wp_json_encode($config)); ?>'></div>
        <?php
    }

    public function ajax_record_stats() {
        check_ajax_referer('gar_ajax');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $rpm = floatval($_POST['rpm'] ?? 0);
        $impressions = intval($_POST['impressions'] ?? 0);
        $clicks = intval($_POST['clicks'] ?? 0);

        if (!$post_id) {
            wp_send_json_error(array('message' => 'No post ID.'));
        }

        $history = get_post_meta($post_id, '_gar_rpm_history', true) ?: array();
        $history[] = array(
            'rpm' => $rpm,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'date' => current_time('mysql'),
        );
        $history = array_slice($history, -50);
        update_post_meta($post_id, '_gar_rpm_history', $history);

        update_post_meta($post_id, '_gar_rpm', $rpm);
        update_post_meta($post_id, '_gar_impressions', $impressions);
        update_post_meta($post_id, '_gar_clicks', $clicks);

        wp_send_json_success(array('message' => 'Stats recorded.'));
    }

    public function ajax_get_stats() {
        check_ajax_referer('gar_ajax');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }

        $posts = get_posts(array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
        ));

        $total_rpm = 0;
        $total_impressions = 0;
        $total_clicks = 0;
        $count = 0;

        foreach ($posts as $pid) {
            $rpm = get_post_meta($pid, '_gar_rpm', true);
            $impressions = get_post_meta($pid, '_gar_impressions', true);
            $clicks = get_post_meta($pid, '_gar_clicks', true);
            if ($rpm !== '' && $rpm !== false) {
                $total_rpm += floatval($rpm);
                $total_impressions += intval($impressions);
                $total_clicks += intval($clicks);
                $count++;
            }
        }

        $avg_rpm = $count > 0 ? round($total_rpm / $count, 2) : 0;
        $ctr = $total_impressions > 0 ? round($total_clicks / $total_impressions * 100, 2) : 0;

        $log = get_option('gar_test_log', array());

        wp_send_json_success(array(
            'avg_rpm' => $avg_rpm,
            'total_impressions' => $total_impressions,
            'total_clicks' => $total_clicks,
            'ctr' => $ctr,
            'tracked_posts' => $count,
            'log' => $log,
        ));
    }
}

GAR_Tracker::instance();
