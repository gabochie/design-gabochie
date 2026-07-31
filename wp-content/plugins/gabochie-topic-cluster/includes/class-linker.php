<?php
defined('ABSPATH') || exit;

class GTC_Linker {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_gtc_suggest_links', array($this, 'ajax_suggest'));
        add_action('wp_ajax_gtc_apply_link', array($this, 'ajax_apply'));
        add_action('wp_ajax_gtc_bulk_suggest', array($this, 'ajax_bulk_suggest'));
    }

    public function suggest_links($post_id, $max = 10) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') return array();

        $segment = get_post_meta($post_id, '_gtc_segment', true);
        if (empty($segment)) return array();

        $content_lower = strtolower($post->post_content);
        $title_lower = strtolower($post->post_title);

        $segment_posts = GTC_Segments::instance()->get_posts_in_segment($segment);
        $candidates = array();

        foreach ($segment_posts as $sp) {
            if ($sp->ID === $post_id) continue;

            $already_linked = $this->is_already_linked($post_id, $sp->ID);
            if ($already_linked) continue;

            $relevance = $this->calculate_relevance($post, $sp);
            if ($relevance > 0) {
                $candidates[] = array(
                    'post_id' => $sp->ID,
                    'title' => $sp->post_title,
                    'url' => get_permalink($sp->ID),
                    'relevance' => $relevance,
                    'context' => $this->find_link_context($sp, $content_lower),
                );
            }
        }

        usort($candidates, function ($a, $b) {
            return $b['relevance'] - $a['relevance'];
        });

        return array_slice($candidates, 0, $max);
    }

    public function suggest_cross_segment_links($post_id, $max = 5) {
        $post = get_post($post_id);
        if (!$post) return array();

        $segment = get_post_meta($post_id, '_gtc_segment', true);
        if (empty($segment)) return array();

        $flat = GTC_Segments::get_flat();
        $current_portfolio = isset($flat[$segment]) ? $flat[$segment]['portfolio'] : '';

        $all_posts = get_posts(array(
            'post_type' => array('post', 'page'),
            'posts_per_page' => 50,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
        ));

        $candidates = array();
        $content_lower = strtolower($post->post_content);

        foreach ($all_posts as $sp) {
            if ($sp->ID === $post_id) continue;
            $sp_segment = get_post_meta($sp->ID, '_gtc_segment', true);
            if (empty($sp_segment) || $sp_segment === $segment) continue;

            $sp_portfolio = isset($flat[$sp_segment]) ? $flat[$sp_segment]['portfolio'] : '';
            if ($sp_portfolio !== $current_portfolio) continue;

            if ($this->is_already_linked($post_id, $sp->ID)) continue;

            $relevance = $this->calculate_relevance($post, $sp);
            if ($relevance > 10) {
                $candidates[] = array(
                    'post_id' => $sp->ID,
                    'title' => $sp->post_title,
                    'url' => get_permalink($sp->ID),
                    'relevance' => $relevance,
                    'context' => $this->find_link_context($sp, $content_lower),
                    'type' => 'cross-segment',
                );
            }
        }

        usort($candidates, function ($a, $b) {
            return $b['relevance'] - $a['relevance'];
        });

        return array_slice($candidates, 0, $max);
    }

    private function calculate_relevance($source_post, $target_post) {
        $source_text = strtolower($source_post->post_title . ' ' . $source_post->post_content);
        $target_text = strtolower($target_post->post_title);
        $target_content = strtolower($target_post->post_content);
        $score = 0;

        $source_words = array_unique(str_word_count($source_text, 1));
        $target_words = str_word_count($target_text . ' ' . $target_content, 1);

        $shared = array_intersect($source_words, array_unique($target_words));
        $common = array_count_values(array_map('strtolower', $target_words));

        foreach ($shared as $word) {
            if (strlen($word) <= 3) continue;
            $freq = isset($common[$word]) ? $common[$word] : 1;
            $score += $freq;

            $stop_words = array('this', 'that', 'with', 'from', 'have', 'been', 'will', 'their', 'what', 'which', 'about', 'your', 'would', 'there', 'should', 'could');
            if (in_array($word, $stop_words)) {
                $score -= $freq * 0.5;
            }
        }

        if (strpos($source_text, strtolower($target_post->post_title)) !== false) {
            $score += 20;
        }

        return max(0, $score);
    }

    private function find_link_context($target_post, $source_content_lower) {
        $title_words = explode(' ', strtolower($target_post->post_title));
        foreach ($title_words as $word) {
            if (strlen($word) <= 4) continue;
            if (strpos($source_content_lower, $word) !== false) {
                return $word;
            }
        }
        return '';
    }

    private function is_already_linked($source_id, $target_id) {
        $source = get_post($source_id);
        if (!$source) return true;

        $target_url = get_permalink($target_id);
        $target_url_clean = rtrim($target_url, '/');
        $target_url_secure = str_replace(array('http://', 'https://'), 'https://', $target_url_clean);
        $domain = parse_url($target_url, PHP_URL_HOST);
        $domain_pattern = preg_quote($domain, '/');

        $count = 0;
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $source->post_content, $links);
        foreach ($links[1] as $link) {
            $link = rtrim($link, '/');
            $link_secure = str_replace(array('http://', 'https://'), 'https://', $link);
            if ($link_secure === $target_url_secure) {
                $count++;
            }
            if (preg_match('/' . $domain_pattern . '/i', $link) && basename($link) === basename($target_url)) {
                $count++;
            }
        }

        return $count > 0;
    }

    public function apply_link($source_id, $target_id, $anchor_text = '') {
        $source = get_post($source_id);
        $target = get_post($target_id);
        if (!$source || !$target) {
            return new WP_Error('not_found', 'Post not found.');
        }

        if (empty($anchor_text)) {
            $anchor_text = $target->post_title;
        }

        $url = get_permalink($target_id);
        $link_html = '<a href="' . esc_url($url) . '">' . esc_html($anchor_text) . '</a>';

        $content = $source->post_content;
        $pos = strpos($content, $anchor_text);

        if ($pos === false) {
            $paragraphs = explode('</p>', $content);
            if (!empty($paragraphs)) {
                $mid = intval(count($paragraphs) / 2);
                $paragraphs[$mid] = $paragraphs[$mid] . "\n\n<p>" . $link_html . '</p>';
                $content = implode('</p>', $paragraphs);
            }
        } else {
            $before = substr($content, 0, $pos);
            $after = substr($content, $pos + strlen($anchor_text));
            $content = $before . $link_html . $after;
        }

        $updated = wp_update_post(array(
            'ID' => $source_id,
            'post_content' => $content,
        ));

        if (is_wp_error($updated)) return $updated;

        $suggestions = get_post_meta($source_id, '_gtc_suggestions', true) ?: array();
        $suggestions[] = array(
            'target_id' => $target_id,
            'anchor' => $anchor_text,
            'applied' => current_time('mysql'),
        );
        update_post_meta($source_id, '_gtc_suggestions', $suggestions);

        return true;
    }

    public function ajax_suggest() {
        check_ajax_referer('gtc_ajax');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $suggestions = $this->suggest_links($post_id);
        $cross = $this->suggest_cross_segment_links($post_id);

        wp_send_json_success(array(
            'within_segment' => $suggestions,
            'cross_segment' => $cross,
        ));
    }

    public function ajax_apply() {
        check_ajax_referer('gtc_ajax');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }

        $source_id = intval($_POST['source_id'] ?? 0);
        $target_id = intval($_POST['target_id'] ?? 0);
        $anchor = sanitize_text_field($_POST['anchor'] ?? '');

        $result = $this->apply_link($source_id, $target_id, $anchor);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'Link applied.'));
    }

    public function ajax_bulk_suggest() {
        check_ajax_referer('gtc_ajax');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }

        $post_ids = isset($_POST['post_ids']) ? array_map('intval', (array)$_POST['post_ids']) : array();
        $results = array();
        foreach ($post_ids as $pid) {
            $results[$pid] = array(
                'within' => $this->suggest_links($pid, 3),
                'cross' => $this->suggest_cross_segment_links($pid, 2),
            );
        }

        wp_send_json_success($results);
    }
}

GTC_Linker::instance();
