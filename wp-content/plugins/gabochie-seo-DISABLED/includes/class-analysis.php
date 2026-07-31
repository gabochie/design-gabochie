<?php
defined('ABSPATH') || exit;

class Gabochie_SEO_Analysis {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_gseo_bulk_analysis', array($this, 'ajax_bulk_analysis'));
        add_filter('manage_posts_columns', array($this, 'add_seo_columns'));
        add_action('manage_posts_custom_column', array($this, 'render_seo_columns'), 10, 2);
        add_filter('manage_pages_columns', array($this, 'add_seo_columns'));
        add_action('manage_pages_custom_column', array($this, 'render_seo_columns'), 10, 2);
    }

    public function analyze_post($post_id) {
        $post = get_post($post_id);
        if (!$post) return false;

        $content = strip_tags($post->post_content);
        $word_count = str_word_count($content);
        $keyword = get_post_meta($post_id, '_gseo_focus_keyword', true);
        $keyword_count = $keyword ? substr_count(strtolower($content), strtolower($keyword)) : 0;
        $heading_count = preg_match_all('/<h[1-6][^>]*>/i', $post->post_content);
        $has_meta_desc = !empty(get_post_meta($post_id, '_gseo_description', true));
        $has_featured = has_post_thumbnail($post_id);
        $has_keyword = !empty($keyword);
        $content_length = strlen($content);
        $keyword_in_title = $keyword ? strpos(strtolower($post->post_title), strtolower($keyword)) !== false : false;

        $score = 0;
        $checks = array();

        if ($word_count >= 1000) { $score += 15; $checks['word_count'] = true; }
        elseif ($word_count >= 300) { $score += 8; $checks['word_count'] = 'partial'; }
        if ($keyword_count >= 3) { $score += 15; $checks['keyword_density'] = true; }
        elseif ($keyword_count >= 1) { $score += 5; $checks['keyword_density'] = 'partial'; }
        if ($heading_count >= 3) { $score += 10; $checks['headings'] = true; }
        elseif ($heading_count >= 1) { $score += 4; $checks['headings'] = 'partial'; }
        if ($has_featured) { $score += 10; $checks['featured'] = true; }
        if ($has_meta_desc) { $score += 15; $checks['meta_desc'] = true; }
        if ($has_keyword) { $score += 10; $checks['keyword_set'] = true; }
        if ($keyword_in_title) { $score += 15; $checks['keyword_title'] = true; }
        if ($content_length >= 300) { $score += 10; $checks['content_length'] = true; }

        $grade = $score >= 80 ? 'good' : ($score >= 50 ? 'ok' : 'bad');
        $label = $score >= 80 ? 'Good' : ($score >= 50 ? 'Needs work' : 'Poor');

        return array(
            'score' => $score,
            'grade' => $grade,
            'label' => $label,
            'word_count' => $word_count,
            'keyword_count' => $keyword_count,
            'heading_count' => $heading_count,
            'checks' => $checks,
        );
    }

    public function add_seo_columns($columns) {
        $columns['gseo_score'] = 'SEO Score';
        return $columns;
    }

    public function render_seo_columns($column, $post_id) {
        if ($column !== 'gseo_score') return;
        $analysis = get_transient('gseo_analysis_' . $post_id);
        if (!$analysis) {
            $analysis = $this->analyze_post($post_id);
            set_transient('gseo_analysis_' . $post_id, $analysis, DAY_IN_SECONDS);
        }
        if (!$analysis) return;
        $s = $analysis['score'];
        $g = $analysis['grade'];
        $color = $g === 'good' ? '#2d8f5e' : ($g === 'ok' ? '#996800' : '#d63638');
        $bg = $g === 'good' ? '#edfaef' : ($g === 'ok' ? '#fcf9e8' : '#fcf0f1');
        echo '<span style="display:inline-block;padding:2px 10px;border-radius:12px;font-weight:600;font-size:12px;color:' . $color . ';background:' . $bg . ';">' . $s . '/100</span>';
    }

    public function ajax_bulk_analysis() {
        check_ajax_referer('gseo_ajax');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', (array)$_POST['post_ids']) : array();
        $results = array();
        foreach ($post_ids as $id) {
            $analysis = $this->analyze_post($id);
            if ($analysis) {
                $results[$id] = $analysis;
                set_transient('gseo_analysis_' . $id, $analysis, DAY_IN_SECONDS);
            }
        }
        wp_send_json_success($results);
    }

    public function clear_cache($post_id) {
        delete_transient('gseo_analysis_' . $post_id);
    }
}

Gabochie_SEO_Analysis::instance();
