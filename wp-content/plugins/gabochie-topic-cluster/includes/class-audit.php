<?php
defined('ABSPATH') || exit;

class GTC_Audit {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function find_orphaned() {
        $all_posts = get_posts(array(
            'post_type' => array('post', 'page'),
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
        ));

        $linked_to = array();
        foreach ($all_posts as $pid) {
            $post = get_post($pid);
            if (!$post) continue;

            preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $post->post_content, $links);
            foreach ($links[1] as $link) {
                $linked_id = url_to_postid($link);
                if ($linked_id && $linked_id !== $pid) {
                    $linked_to[$linked_id] = ($linked_to[$linked_id] ?? 0) + 1;
                }
            }
        }

        $orphans = array();
        foreach ($all_posts as $pid) {
            $incoming = isset($linked_to[$pid]) ? $linked_to[$pid] : 0;
            if ($incoming === 0) {
                $orphans[] = $pid;
            }
        }

        return $orphans;
    }

    public function get_link_stats($post_id) {
        $post = get_post($post_id);
        if (!$post) return null;

        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $post->post_content, $links);
        $all = $links[1];
        $internal = 0;
        $external = 0;
        $site_url = home_url('');
        $site_domain = parse_url($site_url, PHP_URL_HOST);

        foreach ($all as $link) {
            if (empty($link) || $link === '#') continue;
            if (strpos($link, $site_url) === 0 || strpos($link, $site_domain) !== false) {
                $internal++;
            } else {
                $external++;
            }
        }

        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $post->post_content, $incoming);
        $outgoing_links = count($incoming[0]);

        $incoming_count = 0;
        $all_posts = get_posts(array('post_type' => 'any', 'posts_per_page' => -1, 'fields' => 'ids'));
        $permalink = get_permalink($post_id);
        $permalink_clean = rtrim($permalink, '/');

        foreach ($all_posts as $pid) {
            if ($pid === $post_id) continue;
            $p = get_post($pid);
            if (!$p) continue;
            if (strpos($p->post_content, $permalink_clean) !== false || strpos($p->post_content, $permalink) !== false) {
                $incoming_count++;
            }
        }

        $segment = get_post_meta($post_id, '_gtc_segment', true);
        $segment_label = $segment ? GTC_Segments::get_segment_label($segment) : 'Unassigned';

        return array(
            'post_id' => $post_id,
            'title' => $post->post_title,
            'type' => $post->post_type,
            'segment' => $segment_label,
            'segment_key' => $segment,
            'outgoing_total' => count($all),
            'outgoing_internal' => $internal,
            'outgoing_external' => $external,
            'incoming_links' => $incoming_count,
            'is_orphan' => $incoming_count === 0,
            'edit_url' => get_edit_post_link($post_id),
            'permalink' => $permalink,
        );
    }

    public function segment_report($segment_key) {
        $posts = GTC_Segments::instance()->get_posts_in_segment($segment_key);
        $total = count($posts);
        $with_internal = 0;
        $with_external = 0;
        $total_internal = 0;
        $orphans = 0;

        foreach ($posts as $p) {
            $stats = $this->get_link_stats($p->ID);
            if ($stats['outgoing_internal'] > 0) {
                $with_internal++;
                $total_internal += $stats['outgoing_internal'];
            }
            if ($stats['is_orphan']) {
                $orphans++;
            }
        }

        return array(
            'segment_key' => $segment_key,
            'segment_label' => GTC_Segments::get_segment_label($segment_key),
            'total_posts' => $total,
            'posts_with_internal_links' => $with_internal,
            'total_internal_links' => $total_internal,
            'orphan_count' => $orphans,
            'avg_internal_links' => $total > 0 ? round($total_internal / $total, 1) : 0,
        );
    }

    public function full_report() {
        $segments = GTC_Segments::get_flat();
        $report = array();
        foreach ($segments as $key => $data) {
            $report[$key] = $this->segment_report($key);
        }
        return $report;
    }

    public function ajax_full_report() {
        check_ajax_referer('gtc_ajax');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        wp_send_json_success(array('report' => $this->full_report()));
    }

    public function ajax_orphans() {
        check_ajax_referer('gtc_ajax');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        $orphans = $this->find_orphaned();
        $data = array();
        foreach ($orphans as $pid) {
            $data[] = $this->get_link_stats($pid);
        }
        wp_send_json_success(array('orphans' => $data));
    }
}

GTC_Audit::instance();
