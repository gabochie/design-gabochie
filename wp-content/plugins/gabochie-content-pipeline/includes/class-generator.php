<?php
defined('ABSPATH') || exit;

class GCP_Generator {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function generate($topic, $options = array()) {
        $ai = class_exists('Gabochie_AI_Engine') ? Gabochie_AI_Engine::instance() : null;
        $has_ai = $ai && $ai->is_configured();
        if ($has_ai) {
            $ai->refresh_config();
        }

        $data = GCP_Data::instance();
        $ref_data = $data->format_for_prompt();

        if ($has_ai) {
            $prompt = $this->build_prompt($topic, $ref_data);
            $system = 'You are a professional Ghana AEC content writer for Gabochie Design Studio — an AI-powered residential architecture firm founded by a KNUST-trained architect. Write authoritative, genuinely useful content that helps Ghanaian property owners, diaspora investors, and AEC professionals make informed decisions. Never invent figures — use only the data provided or state "consult a professional for current rates." Format in clean HTML with proper heading hierarchy (h2/h3).';
            $response = $ai->chat(array(
                array('role' => 'user', 'content' => $prompt),
            ), $system, 0.5);
            $result = $this->parse($response, $topic);
        } else {
            $result = $this->generate_mock($topic);
        }

        if (!isset($result['title']) || empty($result['title'])) {
            $result['title'] = $topic . ': A Guide for Ghana Property Owners';
        }
        if (!isset($result['content']) || empty($result['content'])) {
            $result['content'] = $this->generate_mock($topic)['content'];
        }
        if (!isset($result['excerpt']) || empty($result['excerpt'])) {
            $result['excerpt'] = wp_trim_words(strip_tags($result['content']), 30);
        }
        if (!isset($result['keyphrase'])) {
            $result['keyphrase'] = $topic;
        }

        return $result;
    }

    public function create_draft($topic, $options = array()) {
        $result = $this->generate($topic, $options);
        if (is_wp_error($result)) {
            return $result;
        }

        $author = isset($options['author']) ? intval($options['author']) : get_current_user_id();
        $category = isset($options['category']) ? intval($options['category']) : 0;

        $post_id = wp_insert_post(array(
            'post_title'    => $result['title'],
            'post_content'  => $result['content'],
            'post_excerpt'  => $result['excerpt'],
            'post_status'   => 'needs-review',
            'post_author'   => $author,
            'post_category' => $category ? array($category) : array(),
            'tags_input'    => array('Ghana', 'AEC', $topic, 'content-pipeline'),
        ));

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        update_post_meta($post_id, '_gcp_generated', '1');
        update_post_meta($post_id, '_gcp_topic', $topic);
        update_post_meta($post_id, '_gcp_generated_date', current_time('mysql'));
        update_post_meta($post_id, '_gcp_ai_used', $result['ai_used'] ?? false);
        if (!empty($result['keyphrase'])) {
            update_post_meta($post_id, '_gcp_suggested_keyphrase', $result['keyphrase']);
            if (function_exists('gseo_set_focus_keyword')) {
                gseo_set_focus_keyword($post_id, $result['keyphrase']);
            } else {
                update_post_meta($post_id, '_gseo_focus_keyword', $result['keyphrase']);
            }
        }

        return $post_id;
    }

    public function approve($post_id, $publish = false) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'needs-review') {
            return new WP_Error('invalid_status', 'Post is not in Needs Review status.');
        }

        $new_status = $publish ? 'publish' : 'draft';
        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => $new_status,
        ));

        update_post_meta($post_id, '_gcp_approved_date', current_time('mysql'));
        update_post_meta($post_id, '_gcp_approved_by', get_current_user_id());

        return true;
    }

    public function reject($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'needs-review') {
            return new WP_Error('invalid_status', 'Post is not in Needs Review status.');
        }

        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'trash',
        ));

        update_post_meta($post_id, '_gcp_rejected_date', current_time('mysql'));
        update_post_meta($post_id, '_gcp_rejected_by', get_current_user_id());

        return true;
    }

    public function regenerate($post_id) {
        $topic = get_post_meta($post_id, '_gcp_topic', true);
        if (empty($topic)) {
            return new WP_Error('no_topic', 'No topic found for this draft.');
        }

        $result = $this->generate($topic);
        if (is_wp_error($result)) {
            return $result;
        }

        wp_update_post(array(
            'ID' => $post_id,
            'post_title'   => $result['title'],
            'post_content' => $result['content'],
            'post_excerpt' => $result['excerpt'],
        ));

        if (!empty($result['keyphrase'])) {
            update_post_meta($post_id, '_gcp_suggested_keyphrase', $result['keyphrase']);
        }
        update_post_meta($post_id, '_gcp_regenerated_date', current_time('mysql'));

        return true;
    }

    private function build_prompt($topic, $ref_data) {
        return "Write a comprehensive, authoritative article for Gabochie Design Studio about: {$topic}

{$ref_data}

Requirements:
- Title: engaging, includes topic naturally (max 70 chars, ideally 50–60)
- 800–1500 words of genuinely useful content
- Use h2 and h3 subheadings with a clear logical structure
- Include specific Ghana AEC data from the reference section above where relevant
- If the topic relates to pricing, cite real fee scales or give ranges — never make up exact figures
- End with a soft CTA to contact Gabochie Design for professional services
- Write in a professional but accessible tone — assume readers range from diaspora property investors to local homeowners to AEC peers
- Use proper HTML: <p>, <h2>, <h3>, <ul>, <li>, <strong>, <table> where helpful

Return in this exact format:
TITLE: [your title here]
KEYPHRASE: [1 focus keyphrase, 2–4 words]
EXCERPT: [1–2 sentence summary, max 160 chars]
CONTENT:
[full HTML content here]";
    }

    private function parse($response, $fallback_topic) {
        $result = array();

        if (preg_match('/TITLE:\s*(.+)/i', $response, $m)) {
            $result['title'] = trim($m[1]);
        }
        if (preg_match('/KEYPHRASE:\s*(.+)/i', $response, $m)) {
            $result['keyphrase'] = trim($m[1]);
        }
        if (preg_match('/EXCERPT:\s*(.+)/i', $response, $m)) {
            $result['excerpt'] = trim($m[1]);
        }

        $parts = explode('CONTENT:', $response);
        $result['content'] = isset($parts[1]) ? trim($parts[1]) : $response;

        if (!isset($result['title'])) {
            $result['title'] = $fallback_topic . ': A Guide for Ghana Property Owners';
        }
        $result['ai_used'] = true;

        return $result;
    }

    private function generate_mock($topic) {
        $data = GCP_Data::instance();
        return array(
            'title' => $topic . ': A Comprehensive Guide for Ghana',
            'keyphrase' => $topic,
            'excerpt' => 'Learn about ' . $topic . ' and how Gabochie Design Studio can help with your AEC project in Ghana.',
            'content' => '<p>' . $topic . ' is an important consideration for anyone building, renovating, or investing in property in Ghana. At Gabochie Design Studio, we combine professional architectural expertise (BSc Architecture, KNUST) with AI-powered tools to deliver exceptional results for our clients.</p>
<h2>Understanding ' . $topic . ' in the Ghanaian Context</h2>
<p>Ghana\'s AEC (Architecture, Engineering, Construction) industry is growing rapidly, with increasing demand from both local homeowners and the Ghanaian diaspora. Understanding ' . strtolower($topic) . ' is essential for making informed decisions about your property project.</p>
<h2>Key Considerations</h2>
<ul>
<li>Professional registration: Ensure your architect or engineer is registered with the relevant Ghanaian body (ARC, GhIS, Engineering Council)</li>
<li>Building permits: All construction requires permits from the local MMDA — factor 0.5–2% of project cost into your budget</li>
<li>Construction costs (2026): Standard residential GHS 4,500–7,500 per m² depending on specification</li>
<li>Professional fees: Architect fees range 6–12% of construction cost per ARC Ghana scale</li>
<li>Payment milestones: Industry standard is milestone-based — 30% design, 30% approvals, 30% construction, 10% completion</li>
</ul>
<h2>How Gabochie Design Studio Can Help</h2>
<p>Our team combines traditional architectural expertise with AI-powered tools to deliver faster, more cost-effective results. We specialise in serving diaspora clients who need a trusted partner for their Ghana property projects.</p>
<p><strong>Ready to discuss your project?</strong> Contact Gabochie Design Studio for a consultation. Our team is ready to help you navigate ' . strtolower($topic) . ' with professional guidance tailored to the Ghanaian market.</p>',
            'ai_used' => false,
        );
    }
}
