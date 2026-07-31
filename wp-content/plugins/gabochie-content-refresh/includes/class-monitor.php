<?php
defined('ABSPATH') || exit;

class GCR_Monitor {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'schedule_cron'));
        add_action('gcr_weekly_scan', array($this, 'scan_posts'));
        add_filter('cron_schedules', array($this, 'add_cron_schedule'));
        add_action('wp_ajax_gcr_scan', array($this, 'ajax_scan'));
        add_action('wp_ajax_gcr_refresh', array($this, 'ajax_refresh'));
    }

    public function add_cron_schedule($schedules) {
        $schedules['gcr_weekly'] = array(
            'interval' => 604800,
            'display' => 'Once Weekly',
        );
        return $schedules;
    }

    public function schedule_cron() {
        if (!wp_next_scheduled('gcr_weekly_scan')) {
            wp_schedule_event(time(), 'gcr_weekly', 'gcr_weekly_scan');
        }
    }

    public function scan_posts() {
        $posts = get_posts(array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
        ));

        foreach ($posts as $post_id) {
            $status = 'fresh';
            $post_date = get_post_time('U', true, $post_id);
            $modified = get_post_modified_time('U', true, $post_id);
            $now = current_time('timestamp');
            $age_days = ($now - $post_date) / DAY_IN_SECONDS;
            $stale_days = ($now - $modified) / DAY_IN_SECONDS;

            if ($age_days > 182) {
                $status = 'stale';
            }

            if ($stale_days > 90 && $status === 'fresh') {
                $status = 'stale';
            }

            $last_score = get_post_meta($post_id, '_gcr_last_score', true);
            $current_score = get_post_meta($post_id, '_gseo_geo_score', true);

            if ($last_score && $current_score !== '' && $current_score !== false) {
                $drop = intval($last_score) - intval($current_score);
                if ($drop > 10) {
                    $status = 'critical';
                } elseif ($drop > 0) {
                    $status = 'decaying';
                }
            }

            if ($current_score !== '' && $current_score !== false) {
                update_post_meta($post_id, '_gcr_last_score', intval($current_score));
            }

            update_post_meta($post_id, '_gcr_status', $status);
            update_post_meta($post_id, '_gcr_last_scan', current_time('mysql'));
        }

        update_option('gcr_last_scan', current_time('mysql'));
    }

    public function get_queue() {
        return get_posts(array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
            'meta_query' => array(
                'relation' => 'OR',
                array('key' => '_gcr_status', 'value' => 'stale'),
                array('key' => '_gcr_status', 'value' => 'decaying'),
                array('key' => '_gcr_status', 'value' => 'critical'),
            ),
        ));
    }

    public function mark_refreshed($post_id) {
        update_post_meta($post_id, '_gcr_status', 'fresh');
        update_post_meta($post_id, '_gcr_refreshed', current_time('mysql'));
    }

    public function ajax_scan() {
        check_ajax_referer('gcr_ajax');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }

        $this->scan_posts();
        $queue = $this->get_queue();

        wp_send_json_success(array(
            'count' => count($queue),
            'message' => 'Scan completed.',
        ));
    }

    public function ajax_refresh() {
        check_ajax_referer('gcr_ajax');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(array('message' => 'No post ID.'));
        }

        $this->mark_refreshed($post_id);

        wp_send_json_success(array(
            'message' => 'Post marked as refreshed.',
        ));
    }
}

GCR_Monitor::instance();
