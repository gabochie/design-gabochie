<?php
defined('ABSPATH') || exit;

class GCP_Queue {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array(__CLASS__, 'register_status'));
        add_filter('display_post_states', array($this, 'display_state'), 10, 2);
        add_action('admin_action_gcp_approve', array($this, 'handle_approve'));
        add_action('admin_action_gcp_approve_publish', array($this, 'handle_approve_publish'));
        add_action('admin_action_gcp_reject', array($this, 'handle_reject'));
        add_action('admin_action_gcp_regenerate', array($this, 'handle_regenerate'));
        add_filter('bulk_actions-edit-post', array($this, 'bulk_actions'));
        add_filter('handle_bulk_actions-edit-post', array($this, 'handle_bulk'), 10, 3);
        add_filter('wp_count_posts', array($this, 'count_posts'), 10, 2);
    }

    public static function register_status() {
        register_post_status('needs-review', array(
            'label'                     => _x('Needs Review', 'post status', 'gabochie-content-pipeline'),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Needs Review <span class="count">(%s)</span>', 'Needs Review <span class="count">(%s)</span>', 'gabochie-content-pipeline'),
        ));
    }

    public function display_state($states, $post) {
        if ($post->post_status === 'needs-review') {
            $states[] = __('Needs Review', 'gabochie-content-pipeline');
        }
        return $states;
    }

    public function get_queue($args = array()) {
        $defaults = array(
            'post_type'      => 'post',
            'post_status'    => 'needs-review',
            'posts_per_page' => 50,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );
        $args = wp_parse_args($args, $defaults);
        return get_posts($args);
    }

    public function get_count() {
        $counts = wp_count_posts('post');
        return isset($counts->{'needs-review'}) ? intval($counts->{'needs-review'}) : 0;
    }

    public function count_posts($counts, $type) {
        if ($type !== 'post') {
            return $counts;
        }
        global $wpdb;
        $needs_review = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = %s AND post_status = 'needs-review'",
                $type
            )
        );
        if (is_object($counts)) {
            $counts->{'needs-review'} = intval($needs_review);
        }
        return $counts;
    }

    public function handle_approve() {
        if (!isset($_GET['post']) || !current_user_can('edit_posts')) {
            wp_die('Unauthorized.');
        }
        check_admin_referer('gcp_approve_' . $_GET['post']);

        $post_id = intval($_GET['post']);
        $generator = GCP_Generator::instance();
        $result = $generator->approve($post_id, false);

        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }

        wp_redirect(admin_url('admin.php?page=gcp-queue&approved=1'));
        exit;
    }

    public function handle_approve_publish() {
        if (!isset($_GET['post']) || !current_user_can('publish_posts')) {
            wp_die('Unauthorized.');
        }
        check_admin_referer('gcp_approve_publish_' . $_GET['post']);

        $post_id = intval($_GET['post']);
        $generator = GCP_Generator::instance();
        $result = $generator->approve($post_id, true);

        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }

        wp_redirect(admin_url('admin.php?page=gcp-queue&published=1'));
        exit;
    }

    public function handle_reject() {
        if (!isset($_GET['post']) || !current_user_can('edit_posts')) {
            wp_die('Unauthorized.');
        }
        check_admin_referer('gcp_reject_' . $_GET['post']);

        $post_id = intval($_GET['post']);
        $generator = GCP_Generator::instance();
        $result = $generator->reject($post_id);

        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }

        wp_redirect(admin_url('admin.php?page=gcp-queue&rejected=1'));
        exit;
    }

    public function handle_regenerate() {
        if (!isset($_GET['post']) || !current_user_can('edit_posts')) {
            wp_die('Unauthorized.');
        }
        check_admin_referer('gcp_regenerate_' . $_GET['post']);

        $post_id = intval($_GET['post']);
        $generator = GCP_Generator::instance();
        $result = $generator->regenerate($post_id);

        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }

        wp_redirect(admin_url('admin.php?page=gcp-queue&regenerated=1'));
        exit;
    }

    public function bulk_actions($actions) {
        $actions['gcp_bulk_approve'] = __('Approve (to Draft)', 'gabochie-content-pipeline');
        $actions['gcp_bulk_approve_publish'] = __('Approve & Publish', 'gabochie-content-pipeline');
        $actions['gcp_bulk_reject'] = __('Reject (Trash)', 'gabochie-content-pipeline');
        return $actions;
    }

    public function handle_bulk($redirect, $doaction, $post_ids) {
        if (!in_array($doaction, array('gcp_bulk_approve', 'gcp_bulk_approve_publish', 'gcp_bulk_reject'))) {
            return $redirect;
        }
        if (!current_user_can('edit_posts')) {
            return $redirect;
        }

        $generator = GCP_Generator::instance();
        $count = 0;

        foreach ($post_ids as $post_id) {
            if ($doaction === 'gcp_bulk_reject') {
                $result = $generator->reject(intval($post_id));
            } elseif ($doaction === 'gcp_bulk_approve_publish') {
                $result = $generator->approve(intval($post_id), true);
            } else {
                $result = $generator->approve(intval($post_id), false);
            }
            if (!is_wp_error($result)) {
                $count++;
            }
        }

        $redirect = add_query_arg(
            array('gcp_bulk_count' => $count, 'gcp_bulk_action' => $doaction),
            $redirect
        );
        return $redirect;
    }
}

GCP_Queue::instance();
