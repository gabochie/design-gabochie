<?php
defined('ABSPATH') || exit;

class Gabochie_Auto_Generator {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_gab_ai_generate', array($this, 'ajax_ai_generate'));
        add_action('wp_ajax_gab_test_generate', array($this, 'ajax_test_generate'));
        add_action('wp_ajax_gab_get_topics', array($this, 'ajax_get_topics'));
    }

    public function generate_post($topic = '', $options = array()) {
        $ai = class_exists('Gabochie_AI_Engine') ? Gabochie_AI_Engine::instance() : null;
        $has_ai = $ai && $ai->is_configured();
        if ($has_ai) {
            $ai->refresh_config();
        }

        $category = isset($options['category']) ? intval($options['category']) : intval(get_option('gab_category', 0));
        $author = isset($options['author']) ? intval($options['author']) : intval(get_option('gab_author', 1));
        $status = isset($options['status']) ? $options['status'] : get_option('gab_status', 'draft');

        if (empty($topic)) {
            $topics = get_option('gab_topics', "Ghana architecture trends\nSustainable building in Ghana");
            $topics_list = array_filter(array_map('trim', explode("\n", $topics)));
            if (empty($topics_list)) {
                return new WP_Error('no_topics', 'No topics configured.');
            }
            $topic = $topics_list[array_rand($topics_list)];
        }

        if ($has_ai) {
            $prompt = $this->build_ai_prompt($topic);
            $system = 'You are a professional content writer for Gabochie Design, an AI-powered residential architecture firm in Ghana. Write authoritative, helpful content for Ghana AEC (architecture, engineering, construction) industry. Use proper Ghanaian context (cedi prices, local regulations, diaspora perspective). Format in clean HTML with h2/h3 headings.';
            $response = $ai->chat(array(
                array('role' => 'user', 'content' => $prompt),
            ), $system, 0.6);
            $content = $this->parse_ai_response($response, $topic);
            $title = $this->extract_title($response, $topic);
            $excerpt = $this->extract_excerpt($content);
        } else {
            $title = $topic . ': A Comprehensive Guide for Ghana';
            $content = $this->generate_mock_content($topic);
            $excerpt = 'Learn about ' . $topic . ' and how Gabochie Design can help with your project in Ghana.';
        }

        $post_id = wp_insert_post(array(
            'post_title'    => $title,
            'post_content'  => $content,
            'post_excerpt'  => $excerpt,
            'post_status'   => $status,
            'post_author'   => $author,
            'post_category' => array($category),
            'tags_input'    => array('Ghana', 'AEC', $topic, 'architecture', 'construction'),
        ));

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        update_post_meta($post_id, '_gab_auto_generated', '1');
        update_post_meta($post_id, '_gab_topic', $topic);
        update_post_meta($post_id, '_gab_generated_date', current_time('mysql'));

        if ($has_ai) {
            $suggested_keyword = $ai->generate(
                "Suggest ONE focus keyphrase (max 4 words) for a Ghana AEC article titled: $title",
                'You are a Ghana AEC SEO specialist.',
                0.3
            );
            if ($suggested_keyword) {
                update_post_meta($post_id, '_gseo_focus_keyword', trim($suggested_keyword));
            }
        }

        return $post_id;
    }

    private function build_ai_prompt($topic) {
        return "Write a comprehensive, authoritative blog post for Gabochie Design about: {$topic}.

Requirements:
- Title: engaging, includes topic naturally (max 70 chars)
- 800-1500 words
- Include practical, actionable advice for Ghana AEC context
- Use h2 and h3 subheadings
- Include at least one relevant statistic or data point
- End with a call-to-action to contact Gabochie Design for professional services
- Use proper HTML formatting (<p>, <h2>, <h3>, <ul>, <li>, <strong>)
- Write in a professional but accessible tone

Return in this exact format:
TITLE: [your title here]
CONTENT:
[full HTML content here]";
    }

    private function parse_ai_response($response, $fallback_topic) {
        if (empty($response)) {
            return $this->generate_mock_content($fallback_topic);
        }
        $parts = explode('CONTENT:', $response);
        return isset($parts[1]) ? trim($parts[1]) : $response;
    }

    private function extract_title($response, $fallback_topic) {
        if (preg_match('/TITLE:\s*(.+)/i', $response, $m)) {
            return trim($m[1]);
        }
        return $fallback_topic . ': A Guide for Ghana Property Owners';
    }

    private function extract_excerpt($content) {
        $text = strip_tags($content);
        return wp_trim_words($text, 30);
    }

    private function generate_mock_content($topic) {
        return '<p>' . $topic . ' is an important consideration for anyone building or renovating in Ghana. At Gabochie Design, we specialize in helping clients navigate this process with expert guidance and AI-powered tools.</p>
<h2>Understanding ' . $topic . ' in Ghana</h2>
<p>Ghana\'s construction industry is growing rapidly, and ' . strtolower($topic) . ' plays a crucial role in ensuring quality outcomes. Whether you are building from overseas or locally, understanding the key factors will save you time and money.</p>
<h2>How Gabochie Design Can Help</h2>
<p>Our team combines traditional architectural expertise with AI-powered tools to deliver exceptional results. From initial consultation to project completion, we guide you through every step.</p>
<h3>Key Benefits</h3>
<ul>
<li>AI-accelerated design process</li>
<li>Local market expertise across Ghana</li>
<li>Diaspora-friendly service model</li>
<li>Sustainable design practices</li>
<li>Transparent cost management</li>
</ul>
<p><strong>Ready to start your project?</strong> Contact Gabochie Design today for a consultation. Our team is ready to bring your vision to life with professional architectural services tailored to the Ghanaian market.</p>';
    }

    public function ajax_ai_generate() {
        check_ajax_referer('gab_ajax');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        $topic = sanitize_text_field($_POST['topic'] ?? '');
        if (empty($topic)) {
            wp_send_json_error(array('message' => 'Topic is required.'));
        }
        $options = array(
            'category' => intval($_POST['category'] ?? 0),
            'author' => intval($_POST['author'] ?? 1),
            'status' => sanitize_text_field($_POST['status'] ?? 'draft'),
        );
        $result = $this->generate_post($topic, $options);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success(array(
            'post_id' => $result,
            'edit_url' => get_edit_post_link($result, 'raw'),
            'message' => 'Post generated successfully.',
        ));
    }

    public function ajax_test_generate() {
        check_ajax_referer('gab_ajax');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }
        $topic = isset($_POST['topic']) ? sanitize_text_field($_POST['topic']) : 'Test topic';
        $result = $this->generate_post($topic, array('status' => 'draft'));
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success(array(
            'post_id' => $result,
            'edit_url' => get_edit_post_link($result, 'raw'),
            'message' => 'Test post generated as draft.',
        ));
    }

    public function ajax_get_topics() {
        check_ajax_referer('gab_ajax');
        $topics = get_option('gab_topics', "Ghana architecture trends\nSustainable building in Ghana");
        $list = array_filter(array_map('trim', explode("\n", $topics)));
        wp_send_json_success(array('topics' => array_values($list)));
    }

    public function get_topic_list() {
        $topics = get_option('gab_topics', "Ghana architecture trends\nSustainable building in Ghana");
        return array_filter(array_map('trim', explode("\n", $topics)));
    }
}

Gabochie_Auto_Generator::instance();
