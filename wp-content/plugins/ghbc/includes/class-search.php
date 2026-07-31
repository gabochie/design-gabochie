<?php
defined('ABSPATH') || exit;

class GHBC_Search {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {}

    public function search($query, $limit = 5) {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, post_title, post_content,
                    MATCH(post_title, post_content) AGAINST(%s IN NATURAL LANGUAGE MODE) AS score
             FROM {$wpdb->posts}
             WHERE post_type = 'ghbc_chunk'
               AND post_status = 'publish'
               AND MATCH(post_title, post_content) AGAINST(%s IN NATURAL LANGUAGE MODE)
             ORDER BY score DESC
             LIMIT %d",
            $query, $query, (int)$limit
        ));

        if (empty($results)) {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT ID, post_title, post_content, 0 AS score
                 FROM {$wpdb->posts}
                 WHERE post_type = 'ghbc_chunk'
                   AND post_status = 'publish'
                   AND (
                       post_title LIKE %s
                       OR post_content LIKE %s
                   )
                 LIMIT %d",
                '%' . $wpdb->esc_like($query) . '%',
                '%' . $wpdb->esc_like($query) . '%',
                (int)$limit
            ));
        }

        $chunks = [];
        foreach ($results as $row) {
            $part     = (int) get_post_meta($row->ID, '_ghbc_part', true);
            $section  = get_post_meta($row->ID, '_ghbc_section', true);
            $clause   = get_post_meta($row->ID, '_ghbc_clause', true);
            $page_s   = (int) get_post_meta($row->ID, '_ghbc_page_start', true);
            $page_e   = (int) get_post_meta($row->ID, '_ghbc_page_end', true);

            $chunks[] = [
                'id'      => $row->ID,
                'title'   => $row->post_title,
                'content' => $row->post_content,
                'part'    => $part,
                'section' => $section,
                'clause'  => $clause,
                'pages'   => $page_s && $page_e ? "$page_s-$page_e" : (string)$page_s,
                'score'   => round($row->score, 4),
            ];
        }

        return $chunks;
    }

    public function get_by_clause($clause) {
        $parts = explode('.', $clause);
        $meta_query = ['relation' => 'AND'];

        if (!empty($parts[0])) {
            $meta_query[] = [
                'key'   => '_ghbc_part',
                'value' => (int)$parts[0],
            ];
        }
        if (!empty($parts[1])) {
            $section = $parts[0] . '.' . $parts[1];
            $meta_query[] = [
                'key'     => '_ghbc_section',
                'value'   => $section,
                'compare' => 'LIKE',
            ];
        }

        $query = new WP_Query([
            'post_type'      => 'ghbc_chunk',
            'posts_per_page' => 1,
            'meta_query'     => $meta_query,
        ]);

        if ($query->have_posts()) {
            $p = $query->posts[0];
            return [
                'id'      => $p->ID,
                'title'   => $p->post_title,
                'content' => $p->post_content,
                'part'    => (int) get_post_meta($p->ID, '_ghbc_part', true),
                'clause'  => get_post_meta($p->ID, '_ghbc_clause', true),
            ];
        }
        return null;
    }

    public function get_part($part_num) {
        $query = new WP_Query([
            'post_type'      => 'ghbc_chunk',
            'posts_per_page' => 100,
            'meta_key'       => '_ghbc_part',
            'meta_value'     => (int)$part_num,
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ]);
        return array_map(function ($p) {
            return [
                'id'      => $p->ID,
                'title'   => $p->post_title,
                'content' => $p->post_content,
                'section' => get_post_meta($p->ID, '_ghbc_section', true),
                'clause'  => get_post_meta($p->ID, '_ghbc_clause', true),
            ];
        }, $query->posts);
    }

    public function index_chunk($part, $section, $clause, $title, $content, $page_start = 0, $page_end = 0) {
        $post_id = wp_insert_post([
            'post_type'    => 'ghbc_chunk',
            'post_title'   => $title,
            'post_content' => wp_kses_post($content),
            'post_status'  => 'publish',
        ]);

        if (is_wp_error($post_id)) return false;

        update_post_meta($post_id, '_ghbc_part', (int)$part);
        update_post_meta($post_id, '_ghbc_section', $section);
        update_post_meta($post_id, '_ghbc_clause', $clause);
        update_post_meta($post_id, '_ghbc_page_start', (int)$page_start);
        update_post_meta($post_id, '_ghbc_page_end', (int)$page_end);

        return $post_id;
    }
}
