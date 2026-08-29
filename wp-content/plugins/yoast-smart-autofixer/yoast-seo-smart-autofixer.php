<?php
/**
 * Plugin Name: Yoast SEO Smart Auto-Fixer Agent
 * Plugin URI: https://techpulse-insights.com/yoast-smart-autofixer
 * Description: Full-spectrum Yoast SEO command center — auto-fixes meta, title, slug, OG/Twitter, schema, and more. Responds to every Yoast recommendation.
 * Version: 4.0.0
 * Author: TechPulse AI Studio
 * License: GPLv2 or later
 * Text Domain: yoast-smart-autofixer
 */

if (!defined('ABSPATH')) exit;

class YoastSmartAutoFixer {

    private static $instance = null;
    private $post_types = array('post', 'page');
    private $per_page = 15;
    private $version = '4.0.0';

    // Feature flags
    private $feature_image_gen = false;
    private $feature_alt_text = false;
    private $feature_breadcrumb = false;
    private $feature_faq_schema = false;
    private $feature_local_seo = false;
    private $feature_saas_dash = false;
    private $feature_content_brief = false;

    private static $provider_defs = array(
        'openai' => array(
            'name' => 'OpenAI',
            'icon' => 'dashicons-networking',
            'format' => 'openai',
            'endpoint' => 'https://api.openai.com/v1/chat/completions',
            'models' => array(
                'gpt-4o-mini'       => 'GPT-4o Mini (fast, cheap)',
                'gpt-4o'            => 'GPT-4o (best quality)',
                'gpt-3.5-turbo'     => 'GPT-3.5 Turbo (fastest)',
            ),
            'key_placeholder' => 'sk-...',
            'key_pattern' => '/^sk-/',
        ),
        'anthropic' => array(
            'name' => 'Anthropic (Claude)',
            'icon' => 'dashicons-format-chat',
            'format' => 'anthropic',
            'endpoint' => 'https://api.anthropic.com/v1/messages',
            'models' => array(
                'claude-3-haiku-20240307'   => 'Claude 3 Haiku (fastest, $0.25/M)',
                'claude-sonnet-4-20250514'  => 'Claude Sonnet 4 (best quality)',
            ),
            'key_placeholder' => 'sk-ant-...',
            'key_pattern' => '/^sk-ant-/',
        ),
        'groq' => array(
            'name' => 'Groq',
            'icon' => 'dashicons-lightning',
            'format' => 'openai',
            'endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
            'models' => array(
                'llama-3.3-70b-versatile'  => 'Llama 3.3 70B (best)',
                'mixtral-8x7b-32768'       => 'Mixtral 8x7B (balanced)',
                'gemma2-9b-it'             => 'Gemma 2 9B (fastest)',
                'llama-3.1-8b-instant'     => 'Llama 3.1 8B (instant)',
            ),
            'key_placeholder' => 'gsk_...',
            'key_pattern' => '/^gsk_/',
        ),
        'deepseek' => array(
            'name' => 'DeepSeek',
            'icon' => 'dashicons-performance',
            'format' => 'openai',
            'endpoint' => 'https://api.deepseek.com/chat/completions',
            'models' => array(
                'deepseek-chat'     => 'DeepSeek Chat (best)',
                'deepseek-reasoner' => 'DeepSeek Reasoner (analysis)',
            ),
            'key_placeholder' => 'sk-...',
            'key_pattern' => '/^sk-/',
        ),
        'openrouter' => array(
            'name' => 'OpenRouter',
            'icon' => 'dashicons-admin-links',
            'format' => 'openai',
            'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
            'models' => array(
                'meta-llama/llama-3.3-70b-instruct:free'   => 'Llama 3.3 70B (free)',
                'mistralai/mistral-7b-instruct:free'        => 'Mistral 7B (free)',
                'google/gemma-2-9b-it:free'                 => 'Gemma 2 9B (free)',
                'meta-llama/llama-3.1-8b-instruct:free'     => 'Llama 3.1 8B (free)',
                'anthropic/claude-3-haiku'                   => 'Claude 3 Haiku (paid)',
                'openai/gpt-4o-mini'                         => 'GPT-4o Mini (paid)',
            ),
            'key_placeholder' => 'sk-or-...',
            'key_pattern' => '/^sk-or-/',
            'extra_headers' => array('HTTP-Referer' => 'https://gabochiedesign.com', 'X-Title' => 'Yoast Auto-Fixer'),
        ),
        'gemini' => array(
            'name' => 'Google Gemini',
            'icon' => 'dashicons-google',
            'format' => 'gemini',
            'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
            'models' => array(
                'gemini-2.0-flash'       => 'Gemini 2.0 Flash (fastest, free tier)',
                'gemini-2.5-flash'       => 'Gemini 2.5 Flash (thinking, free tier)',
                'gemini-2.5-pro'         => 'Gemini 2.5 Pro (best quality)',
                'gemini-1.5-flash'       => 'Gemini 1.5 Flash (fast, cheap)',
                'gemini-1.5-pro'         => 'Gemini 1.5 Pro (balanced)',
            ),
            'key_placeholder' => 'AIza...',
            'key_pattern' => '/^AIza/',
        ),
        'omniroute' => array(
            'name' => 'OmniRoute (AI Gateway)',
            'icon' => 'dashicons-admin-links',
            'format' => 'openai',
            'endpoint' => 'https://ai.gabochiedesign.com/v1/chat/completions',
            'models' => array(
                'auto/best-free'    => 'Best Free (auto-routes, free)',
                'auto/best-fast'    => 'Best Fast (auto-routes)',
                'auto/cheap'        => 'Cheap (auto-routes)',
                'auto/chat'         => 'Chat (auto-routes)',
                'auto/coding'       => 'Coding (auto-routes)',
                'auto/smart'        => 'Smart (auto-routes)',
                'free-stack'        => 'Free Stack (free)',
            ),
            'key_placeholder' => 'sk-...',
            'key_pattern' => '/^sk-/',
        ),
    );

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'register_admin_menus'));
        add_action('save_post', array($this, 'auto_fix_on_save'), 10, 3);
        add_action('admin_notices', array($this, 'admin_notice'));

        add_action('wp_ajax_yaf_preview', array($this, 'ajax_preview'));
        add_action('wp_ajax_yaf_fix_single', array($this, 'ajax_fix_single'));
        add_action('wp_ajax_yaf_fix_bulk', array($this, 'ajax_fix_bulk'));
        add_action('wp_ajax_yaf_fix_all', array($this, 'ajax_fix_all'));
        add_action('wp_ajax_yaf_fix_issue', array($this, 'ajax_fix_issue'));
        add_action('wp_ajax_yaf_fix_all_issues', array($this, 'ajax_fix_all_issues'));
        add_action('wp_ajax_yaf_undo', array($this, 'ajax_undo'));
        add_action('wp_ajax_yaf_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_yaf_get_history', array($this, 'ajax_get_history'));
        add_action('wp_ajax_yaf_generate_ai', array($this, 'ajax_generate_ai'));
        add_action('wp_ajax_yaf_test_provider', array($this, 'ajax_test_provider'));
        add_action('wp_ajax_yaf_get_analysis', array($this, 'ajax_get_analysis'));
        add_action('wp_ajax_yaf_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_yaf_get_gscc_stats', array($this, 'ajax_get_gscc_stats'));
        add_action('wp_ajax_yaf_get_settings', array($this, 'ajax_get_settings'));
        add_action('wp_ajax_yaf_get_cpts', array($this, 'ajax_get_cpts'));
        add_action('wp_ajax_yaf_generate_featured_image', array($this, 'ajax_generate_featured_image'));
        add_action('wp_ajax_yaf_fix_bulk_async', array($this, 'ajax_fix_bulk_async'));
        add_action('wp_ajax_yaf_fix_bulk_progress', array($this, 'ajax_fix_bulk_progress'));
        add_action('wp_ajax_yaf_generate_content_brief', array($this, 'ajax_generate_content_brief'));
        add_action('wp_ajax_yaf_get_saas_stats', array($this, 'ajax_get_saas_stats'));
        add_action('wp_ajax_yaf_suggest_links', array($this, 'ajax_suggest_links'));
        add_action('wp_ajax_yaf_generate_alt_text', array($this, 'ajax_generate_alt_text'));

        // Cron hooks
        add_action('yaf_weekly_reanalysis', array($this, 'cron_reanalysis'));
        add_action('yaf_batch_process', array($this, 'cron_batch_process'));

        // Frontend shortcode for SaaS dashboard
        add_shortcode('yaf_seo_dashboard', array($this, 'render_saas_dashboard'));

        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // Schedule cron if not already
        if (!wp_next_scheduled('yaf_weekly_reanalysis')) {
            wp_schedule_event(time(), 'weekly', 'yaf_weekly_reanalysis');
        }

        $this->load_feature_flags();
        $this->migrate_old_key();
    }

    /* ─────────────────────── MENUS ─────────────────────── */

    public function register_admin_menus() {
        add_menu_page('Yoast Auto-Fixer', 'Auto-Fixer', 'manage_options', 'yoast-smart-autofixer', array($this, 'render_dashboard'), 'dashicons-superhero', 26);
        add_options_page('Auto-Fixer Settings', 'Auto-Fixer', 'manage_options', 'yoast-smart-autofixer-settings', array($this, 'render_settings_page'));
        if (defined('WPSEO_VERSION')) {
            add_submenu_page('wpseo_dashboard', 'Smart Auto-Fixer', 'Smart Auto-Fixer', 'manage_options', 'yoast-smart-autofixer-seo', array($this, 'render_dashboard'));
        }
    }

    /* ─────────────────────── ASSETS ─────────────────────── */

    public function enqueue_assets($hook) {
        if (strpos($hook, 'yoast-smart-autofixer') === false && strpos($hook, 'yoast-smart-autofixer-settings') === false) return;

        wp_enqueue_style('yaf-admin', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), $this->version);
        wp_enqueue_script('yaf-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), $this->version, true);
        wp_localize_script('yaf-admin', 'YAF', array(
            'ajax'  => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yaf_nonce'),
            'site'  => get_site_url(),
            'features' => array(
                'image_gen' => $this->feature_image_gen,
                'alt_text' => $this->feature_alt_text,
                'breadcrumb' => $this->feature_breadcrumb,
                'faq_schema' => $this->feature_faq_schema,
                'local_seo' => $this->feature_local_seo,
                'saas_dash' => $this->feature_saas_dash,
                'content_brief' => $this->feature_content_brief,
            ),
            'current_user_id' => get_current_user_id(),
        ));
    }

    /* ═══════════════════════════════════════════════════════
       ANALYSIS ENGINE — Checks every Yoast recommendation
       (Delegates to analyze_post which includes GSCC checks)
       ═══════════════════════════════════════════════════════ */

    // analyze_post is now defined in the new features section
    // with GSCC compliance suggestions built in

    /* ─── Content helpers ─── */

    private function get_first_paragraph($content) {
        $blocks = preg_split('/<\/p>/i', $content, 2);
        if (!empty($blocks[0])) return wp_strip_all_tags($blocks[0]);
        $lines = explode("\n", wp_strip_all_tags($content));
        return !empty($lines[0]) ? $lines[0] : '';
    }

    private function get_subheadings($content) {
        $headings = array();
        preg_match_all('/<h[2-6][^>]*>(.*?)<\/h[2-6]>/i', $content, $matches);
        if (!empty($matches[1])) {
            $headings = array_map('wp_strip_all_tags', $matches[1]);
        }
        return $headings;
    }

    private function count_internal_links($content) {
        $count = 0;
        $home = parse_url(get_site_url(), PHP_URL_HOST);
        preg_match_all('/<a\s[^>]*href=["\']([^"\']*)["\']/i', $content, $matches);
        foreach ($matches[1] as $url) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host === $home || empty($host)) $count++;
        }
        return $count;
    }

    private function count_external_links($content) {
        $count = 0;
        $home = parse_url(get_site_url(), PHP_URL_HOST);
        preg_match_all('/<a\s[^>]*href=["\']([^"\']*)["\']/i', $content, $matches);
        foreach ($matches[1] as $url) {
            if (strpos($url, '#') === 0) continue;
            $host = parse_url($url, PHP_URL_HOST);
            if (!empty($host) && $host !== $home) $count++;
        }
        return $count;
    }

    private function transition_word_percentage($text) {
        $transitions = array('however','therefore','moreover','furthermore','additionally','consequently','meanwhile','nevertheless','thus','hence','also','besides','indeed','similarly','conversely','instead','otherwise','likewise','still','finally','next','then','subsequently','accordingly','for example','for instance','in addition','on the other hand','as a result','in fact','in contrast','by contrast','alternatively');
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($sentences) == 0) return 0;
        $with_transitions = 0;
        foreach ($sentences as $s) {
            $lower = strtolower(trim($s));
            foreach ($transitions as $t) {
                if (strpos($lower, $t) === 0) { $with_transitions++; break; }
            }
        }
        return ($with_transitions / count($sentences)) * 100;
    }

    private function passive_voice_percentage($text) {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($sentences) == 0) return 0;
        $passive = 0;
        foreach ($sentences as $s) {
            if (preg_match('/\b(is|are|was|were|be|been|being)\s+\w+ed\b/i', trim($s))) $passive++;
        }
        return ($passive / count($sentences)) * 100;
    }

    private function long_sentence_percentage($text) {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($sentences) == 0) return 0;
        $long = 0;
        foreach ($sentences as $s) {
            if (str_word_count(trim($s)) > 20) $long++;
        }
        return ($long / count($sentences)) * 100;
    }

    private function slugify($text) {
        return sanitize_title($text);
    }

    /* ═══════════════════════════════════════════════════════
       FIX ENGINE — Acts on every fixable recommendation
       ═══════════════════════════════════════════════════════ */

    /* ─── Social / Twitter fix ─── */

    private function fix_social_meta($post_id, $post, $focuskw) {
        $og_title = get_post_meta($post_id, '_yoast_wpseo_opengraph-title', true);
        $og_desc  = get_post_meta($post_id, '_yoast_wpseo_opengraph-description', true);

        if (empty($og_title)) {
            $this->apply_field($post_id, '_yoast_wpseo_opengraph-title', $post->post_title);
        }
        if (empty($og_desc)) {
            $existing = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
            if (!empty($existing)) {
                $this->apply_field($post_id, '_yoast_wpseo_opengraph-description', $existing);
            } else {
                $desc = $this->generate_meta($post->post_title, $post->post_content, $post->post_type, $focuskw);
                $this->apply_field($post_id, '_yoast_wpseo_opengraph-description', $desc);
            }
        }
    }

    private function fix_twitter_meta($post_id, $post, $focuskw) {
        $tw_title = get_post_meta($post_id, '_yoast_wpseo_twitter-title', true);
        $tw_desc  = get_post_meta($post_id, '_yoast_wpseo_twitter-description', true);

        if (empty($tw_title)) {
            $this->apply_field($post_id, '_yoast_wpseo_twitter-title', $post->post_title);
        }
        if (empty($tw_desc)) {
            $existing = get_post_meta($post_id, '_yoast_wpseo_opengraph-description', true);
            if (!empty($existing)) {
                $this->apply_field($post_id, '_yoast_wpseo_twitter-description', $existing);
            } else {
                $desc = $this->generate_meta($post->post_title, $post->post_content, $post->post_type, $focuskw);
                $this->apply_field($post_id, '_yoast_wpseo_twitter-description', $desc);
            }
        }
    }

    /* ─── New Fix Methods: SEO Technical ─── */

    private function fix_seo_title_length($post_id, $post, $focuskw) {
        $seo_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
        $new_title = $this->generate_seo_title($post->post_title, $focuskw);
        if (strlen($new_title) < 30) {
            $site_name = get_bloginfo('name');
            $sep = ' | ';
            $max_base = 30 - strlen($sep . $site_name);
            if ($max_base > 5) {
                $base = !empty($focuskw) ? ucfirst($focuskw) : $post->post_title;
                $new_title = substr($base, 0, $max_base) . $sep . $site_name;
            } else {
                $new_title = substr($post->post_title, 0, 57) . '...';
            }
        }
        if (strlen($new_title) > 60) {
            $site_name = get_bloginfo('name');
            $sep = ' | ';
            $max_base = 60 - strlen($sep . $site_name);
            $base = !empty($focuskw) && stripos($post->post_title, $focuskw) === false
                ? $focuskw . ' - ' . $post->post_title
                : $post->post_title;
            $new_title = substr($base, 0, max($max_base - 3, 10)) . '...' . $sep . $site_name;
        }
        $this->apply_field($post_id, '_yoast_wpseo_title', $new_title);
        return 1;
    }

    private function fix_slug($post_id, $post, $focuskw) {
        $new_slug = $this->slugify($focuskw);
        if (!empty($new_slug) && $new_slug !== $post->post_name) {
            wp_update_post(array('ID' => $post_id, 'post_name' => $new_slug));
            return 1;
        }
        return 0;
    }

    /* ─── New Fix Methods: Content ─── */

    private function fix_kw_intro($post_id, $post, $focuskw) {
        $content = $post->post_content;
        $plain = wp_strip_all_tags($content);
        $sentences = preg_split('/(?<=[.!?])\s+/', $plain, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($sentences)) return 0;

        $first = $sentences[0];
        if (stripos($first, $focuskw) !== false) return 0;

        $ai_result = $this->generate_ai_content_fix($first, $focuskw, 'kw_intro');
        if ($ai_result && $ai_result !== $first) {
            $new_content = str_replace($first, $ai_result, $content);
            if ($new_content !== $content) {
                wp_update_post(array('ID' => $post_id, 'post_content' => $new_content));
                $this->log_change($post_id, 'post_content', '[first paragraph]', '[AI rewrote intro with keyphrase]');
                return 1;
            }
        }
        return 0;
    }

    private function fix_kw_subheadings($post_id, $post, $focuskw) {
        $content = $post->post_content;
        preg_match_all('/<h[2-6][^>]*>(.*?)<\/h[2-6]>/i', $content, $matches, PREG_SET_ORDER);
        if (empty($matches)) return 0;

        foreach ($matches as $m) {
            $heading_text = wp_strip_all_tags($m[1]);
            if (stripos($heading_text, $focuskw) === false) {
                $ai_result = $this->generate_ai_content_fix($heading_text, $focuskw, 'kw_subheadings');
                if ($ai_result && $ai_result !== $heading_text) {
                    $safe = esc_html(wp_strip_all_tags($ai_result));
                    $new_tag = str_replace($m[1], $safe, $m[0]);
                    $new_content = str_replace($m[0], $new_tag, $content);
                    if ($new_content !== $content) {
                        wp_update_post(array('ID' => $post_id, 'post_content' => $new_content));
                        $this->log_change($post_id, 'post_content', '[subheading]', '[AI added keyphrase to subheading]');
                        return 1;
                    }
                }
                break;
            }
        }
        return 0;
    }

    private function fix_add_subheadings($post_id, $post, $focuskw) {
        $content = $post->post_content;
        $plain = wp_strip_all_tags($content);
        $ai_result = $this->generate_ai_content_fix($plain, $focuskw, 'add_subheadings');
        if (!$ai_result) return 0;

        $lines = array_filter(explode("\n", trim($ai_result)));
        $h2s = array();
        foreach ($lines as $line) {
            $line = trim($line, "# \t\n\r\0\x0B");
            if (!empty($line) && strlen($line) > 3 && strlen($line) < 100) {
                $h2s[] = $line;
            }
        }
        if (empty($h2s)) return 0;

        $paragraphs = preg_split('/<\/p>/i', $content);
        if (count($paragraphs) < 4) return 0;

        $insert_every = max(2, intdiv(count($paragraphs), count($h2s)));
        $h2_index = 0;
        $new_paragraphs = array();
        foreach ($paragraphs as $i => $p) {
            $new_paragraphs[] = $p;
            if ($h2_index < count($h2s) && ($i + 1) % $insert_every === 0 && ($i + 1) < count($paragraphs)) {
                $new_paragraphs[] = "\n<h2>" . esc_html($h2s[$h2_index]) . "</h2>";
                $h2_index++;
            }
        }
        $new_content = implode("</p>", $new_paragraphs);
        if ($new_content !== $content) {
            wp_update_post(array('ID' => $post_id, 'post_content' => $new_content));
            $this->log_change($post_id, 'post_content', '[no subheadings]', '[AI added ' . count($h2s) . ' subheadings]');
            return 1;
        }
        return 0;
    }

    private function fix_internal_links($post_id, $post, $focuskw) {
        $content = $post->post_content;
        $title = $post->post_title;
        $ai_result = $this->generate_ai_content_fix(wp_strip_all_tags($content), $focuskw, 'internal_links');
        if (!$ai_result) return 0;

        $links = $this->parse_link_suggestions($ai_result);
        if (empty($links)) return 0;

        $added = 0;
        $last_p_tag = '</p>';
        foreach ($links as $link) {
            if (empty($link['url']) || empty($link['anchor'])) continue;
            $anchor = esc_html($link['anchor']);
            $href = esc_url($link['url']);
            if (strpos($href, home_url()) === false && strpos($href, '/') === 0) {
                $href = home_url($href);
            }
            if (strpos($href, home_url()) !== false) {
                $link_html = ' <a href="' . $href . '">' . $anchor . '</a>';
                $insert_pos = strrpos($content, $last_p_tag);
                if ($insert_pos !== false) {
                    $content = substr_replace($content, $link_html . "\n" . $last_p_tag, $insert_pos + strlen($last_p_tag), 0);
                    $added++;
                    if ($added >= 2) break;
                }
            }
        }
        if ($added > 0) {
            wp_update_post(array('ID' => $post_id, 'post_content' => $content));
            $this->log_change($post_id, 'post_content', '[no internal links]', '[AI added ' . $added . ' internal links]');
            return 1;
        }
        return 0;
    }

    private function fix_external_links($post_id, $post, $focuskw) {
        $content = $post->post_content;
        $topic = !empty($focuskw) ? $focuskw : $post->post_title;
        $ai_result = $this->generate_ai_content_fix(wp_strip_all_tags($content), $topic, 'external_links');
        if (!$ai_result) return 0;

        $links = $this->parse_link_suggestions($ai_result);
        if (empty($links)) return 0;

        $added = 0;
        $last_p_tag = '</p>';
        foreach ($links as $link) {
            if (empty($link['url']) || empty($link['anchor'])) continue;
            $href = esc_url($link['url']);
            $anchor = esc_html($link['anchor']);
            $home_host = parse_url(home_url(), PHP_URL_HOST);
            $link_host = parse_url($href, PHP_URL_HOST);
            if (!empty($link_host) && $link_host !== $home_host) {
                $link_html = ' <a href="' . $href . '" target="_blank" rel="noopener">' . $anchor . '</a>';
                $insert_pos = strrpos($content, $last_p_tag);
                if ($insert_pos !== false) {
                    $content = substr_replace($content, $link_html . "\n" . $last_p_tag, $insert_pos + strlen($last_p_tag), 0);
                    $added++;
                    if ($added >= 2) break;
                }
            }
        }
        if ($added > 0) {
            wp_update_post(array('ID' => $post_id, 'post_content' => $content));
            $this->log_change($post_id, 'post_content', '[no external links]', '[AI added ' . $added . ' external links]');
            return 1;
        }
        return 0;
    }

    /* ─── New Fix Methods: Readability ─── */

    private function fix_transitions($post_id, $post, $focuskw) {
        $plain = wp_strip_all_tags($post->post_content);
        $sentences = preg_split('/(?<=[.!?])\s+/', $plain, -1, PREG_SPLIT_NO_EMPTY);
        $no_transition = array();
        foreach ($sentences as $s) {
            $lower = strtolower(trim($s));
            $has_transition = false;
            $transitions = array('however','therefore','moreover','furthermore','additionally','consequently','meanwhile','nevertheless','thus','hence','also','besides','indeed','similarly','conversely','instead','otherwise','likewise','still','finally','next','then','subsequently','accordingly','for example','for instance','in addition','on the other hand','as a result','in fact','in contrast','by contrast','alternatively');
            foreach ($transitions as $t) {
                if (strpos($lower, $t) === 0) { $has_transition = true; break; }
            }
            if (!$has_transition && strlen(trim($s)) > 20) {
                $no_transition[] = $s;
            }
        }
        if (empty($no_transition)) return 0;

        $sample = array_slice($no_transition, 0, 8);
        $ai_result = $this->generate_ai_content_fix(implode(" ", $sample), $focuskw, 'transitions');
        if (!$ai_result) return 0;

        $improved = preg_split('/(?<=[.!?])\s+/', $ai_result, -1, PREG_SPLIT_NO_EMPTY);
        if (count($improved) < count($sample)) return 0;

        $new_content = $post->post_content;
        $replaced = 0;
        for ($i = 0; $i < min(count($sample), count($improved)); $i++) {
            if (trim($sample[$i]) !== trim($improved[$i])) {
                $new_content = str_replace($sample[$i], trim($improved[$i]), $new_content);
                $replaced++;
            }
        }
        if ($replaced > 0) {
            wp_update_post(array('ID' => $post_id, 'post_content' => $new_content));
            $this->log_change($post_id, 'post_content', '[few transitions]', '[AI added transitions to ' . $replaced . ' sentences]');
            return 1;
        }
        return 0;
    }

    private function fix_passive_voice($post_id, $post, $focuskw) {
        $plain = wp_strip_all_tags($post->post_content);
        $sentences = preg_split('/(?<=[.!?])\s+/', $plain, -1, PREG_SPLIT_NO_EMPTY);
        $passive = array();
        foreach ($sentences as $s) {
            if (preg_match('/\b(is|are|was|were|be|been|being)\s+\w+ed\b/i', trim($s))) {
                $passive[] = $s;
            }
        }
        if (empty($passive)) return 0;

        $sample = array_slice($passive, 0, 6);
        $ai_result = $this->generate_ai_content_fix(implode(" ", $sample), $focuskw, 'passive_to_active');
        if (!$ai_result) return 0;

        $improved = preg_split('/(?<=[.!?])\s+/', $ai_result, -1, PREG_SPLIT_NO_EMPTY);
        $new_content = $post->post_content;
        $replaced = 0;
        for ($i = 0; $i < min(count($sample), count($improved)); $i++) {
            if (trim($sample[$i]) !== trim($improved[$i])) {
                $new_content = str_replace($sample[$i], trim($improved[$i]), $new_content);
                $replaced++;
            }
        }
        if ($replaced > 0) {
            wp_update_post(array('ID' => $post_id, 'post_content' => $new_content));
            $this->log_change($post_id, 'post_content', '[passive voice]', '[AI rewrote ' . $replaced . ' passive sentences to active]');
            return 1;
        }
        return 0;
    }

    private function fix_long_sentences($post_id, $post, $focuskw) {
        $plain = wp_strip_all_tags($post->post_content);
        $sentences = preg_split('/(?<=[.!?])\s+/', $plain, -1, PREG_SPLIT_NO_EMPTY);
        $long = array();
        foreach ($sentences as $s) {
            if (str_word_count(trim($s)) > 20) {
                $long[] = $s;
            }
        }
        if (empty($long)) return 0;

        $sample = array_slice($long, 0, 5);
        $ai_result = $this->generate_ai_content_fix(implode(" ", $sample), $focuskw, 'split_sentences');
        if (!$ai_result) return 0;

        $improved = preg_split('/(?<=[.!?])\s+/', $ai_result, -1, PREG_SPLIT_NO_EMPTY);
        $new_content = $post->post_content;
        $replaced = 0;
        for ($i = 0; $i < min(count($sample), count($improved)); $i++) {
            if (trim($sample[$i]) !== trim($improved[$i])) {
                $new_content = str_replace($sample[$i], trim($improved[$i]), $new_content);
                $replaced++;
            }
        }
        if ($replaced > 0) {
            wp_update_post(array('ID' => $post_id, 'post_content' => $new_content));
            $this->log_change($post_id, 'post_content', '[long sentences]', '[AI split ' . $replaced . ' long sentences]');
            return 1;
        }
        return 0;
    }

    /* ─── New Fix Methods: Social Images ─── */

    private function fix_og_image($post_id, $post, $focuskw) {
        $image = $this->get_first_content_image($post->post_content);
        if (empty($image)) {
            $image = $this->get_default_og_image();
        }
        if (!empty($image)) {
            $this->apply_field($post_id, '_yoast_wpseo_opengraph-image', $image);
            return 1;
        }
        return 0;
    }

    private function fix_tw_image($post_id, $post, $focuskw) {
        $image = $this->get_first_content_image($post->post_content);
        if (empty($image)) {
            $image = $this->get_default_og_image();
        }
        if (!empty($image)) {
            $this->apply_field($post_id, '_yoast_wpseo_twitter-image', $image);
            return 1;
        }
        return 0;
    }

    /* ─── AI Content Fix Generator ─── */

    private function generate_ai_content_fix($text, $context, $fix_type) {
        $active = $this->get_active_providers();
        if (!empty($active)) {
            $prompt = $this->build_content_fix_prompt($text, $context, $fix_type);
            foreach ($active as $key => $cfg) {
                $def = self::$provider_defs[$key];
                $result = false;
                if ($def['format'] === 'anthropic') {
                    $result = $this->call_anthropic($prompt, $cfg['model'], $cfg['key']);
                } elseif ($def['format'] === 'gemini') {
                    $result = $this->call_gemini($prompt, $cfg['model'], $cfg['key']);
                } else {
                    $result = $this->call_ai_provider($prompt, $def['endpoint'], $cfg['model'], $cfg['key'], isset($def['extra_headers']) ? $def['extra_headers'] : array());
                }
                if ($result) return $result;
            }
        }
        return $this->template_content_fix($text, $context, $fix_type);
    }

    private function build_content_fix_prompt($text, $context, $fix_type) {
        $text_short = substr($text, 0, 800);
        switch ($fix_type) {
            case 'kw_intro':
                return "Rewrite this paragraph to naturally include the keyphrase \"$context\" at least once. Keep the same meaning and tone. Return ONLY the rewritten paragraph, nothing else.\n\nParagraph: $text_short";
            case 'kw_subheadings':
                return "Rewrite this subheading to naturally include the keyphrase \"$context\". Keep it concise (under 70 characters). Return ONLY the new subheading text, nothing else.\n\nSubheading: $text_short";
            case 'add_subheadings':
                return "Analyze this content and generate 3-5 relevant H2 subheadings that break it into logical sections. Return ONLY the subheadings, one per line, no numbering or bullet points.\n\nContent: $text_short";
            case 'transitions':
                return "Add appropriate transition words (however, moreover, furthermore, additionally, consequently, meanwhile, therefore, etc.) to the beginning of these sentences to improve flow. Return ONLY the improved sentences separated by spaces.\n\nSentences: $text_short";
            case 'passive_to_active':
                return "Rewrite these sentences from passive voice to active voice. Keep the same meaning. Return ONLY the improved sentences separated by spaces.\n\nSentences: $text_short";
            case 'split_sentences':
                return "Split these long sentences into shorter ones (under 20 words each). Keep the same meaning. Return ONLY the improved sentences separated by spaces.\n\nSentences: $text_short";
            case 'internal_links':
                return "Based on this content from gabochiedesign.com, suggest exactly 2 relevant internal pages to link to. For each, provide a URL path and natural anchor text. Format: [URL] anchor text\n\nContent: $text_short\n\nSuggested pages (use real site paths like /services/, /portfolio/, /about/, /contact/, /blog/, or specific service pages):";
            case 'external_links':
                return "Based on this content about \"$context\", suggest exactly 2 authoritative external sources to link to for credibility. For each, provide a URL and natural anchor text. Format: [URL] anchor text\n\nContent: $text_short:";
            default:
                return $text_short;
        }
    }

    private function template_content_fix($text, $context, $fix_type) {
        // No AI provider configured: never rewrite content with templates.
        // Returning the text unchanged (or empty, where the caller requires
        // a suggestion) makes the fix a no-op instead of baking junk like
        // "{keyword} essentials" headings into post_content.
        switch ($fix_type) {
            case 'add_subheadings':
            case 'internal_links':
            case 'external_links':
                return '';
            case 'kw_intro':
            case 'kw_subheadings':
            case 'transitions':
            case 'passive_to_active':
            case 'split_sentences':
            default:
                return $text;
        }
    }

    private function parse_link_suggestions($ai_text) {
        $links = array();
        preg_match_all('/\[([^\]]+)\]\s*(.+)/', $ai_text, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $links[] = array('url' => trim($m[1]), 'anchor' => trim($m[2]));
        }
        return $links;
    }

    /* ─── Image Helpers ─── */

    private function get_first_content_image($content) {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $m)) {
            $src = $m[1];
            if (strpos($src, 'http') === 0) return $src;
            if (strpos($src, '/') === 0) return home_url($src);
            return home_url('/' . $src);
        }
        return '';
    }

    private function get_default_og_image() {
        $custom = get_option('wpseo_social', array());
        if (!empty($custom['og_default_image'])) return $custom['og_default_image'];

        $upload_dir = wp_get_upload_dir();
        $fallback_names = array('default-og-image.jpg', 'og-default.jpg', 'site-image.jpg', 'default-social.jpg');
        foreach ($fallback_names as $name) {
            $path = $upload_dir['basedir'] . '/' . $name;
            if (file_exists($path)) return $upload_dir['baseurl'] . '/' . $name;
        }

        $custom_logo = get_theme_mod('custom_logo');
        if ($custom_logo) return $custom_logo;

        return '';
    }

    /* ─── Apply + History ─── */

    private function apply_field($post_id, $key, $value) {
        $old = get_post_meta($post_id, $key, true);
        update_post_meta($post_id, $key, sanitize_text_field($value));
        $this->log_change($post_id, $key, $old, $value);
    }

    private function recalculate_score($post_id) {
        $metadesc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        $seo_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
        $focuskw = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        $score = 50;

        if (!empty($metadesc) && strlen($metadesc) >= 120 && strlen($metadesc) <= 155) $score += 15;
        if (!empty($focuskw) && stripos($metadesc, $focuskw) !== false) $score += 10;
        if (!empty($seo_title)) $score += 10;
        if (!empty($focuskw) && stripos($seo_title, $focuskw) !== false) $score += 10;
        if (!empty(get_post_meta($post_id, '_yoast_wpseo_opengraph-title', true))) $score += 3;
        if (!empty(get_post_meta($post_id, '_yoast_wpseo_canonical', true))) $score += 2;

        update_post_meta($post_id, '_yoast_wpseo_linkdex', min($score, 98));
    }

    /* ─── AI Provider Cascade ─── */

    private function get_providers() {
        $saved = get_option('yaf_providers', array());
        $providers = array();
        foreach (self::$provider_defs as $key => $def) {
            $cfg = isset($saved[$key]) ? $saved[$key] : array();
            $providers[$key] = array(
                'enabled' => !empty($cfg['enabled']),
                'key'     => isset($cfg['key']) ? $cfg['key'] : '',
                'model'   => isset($cfg['model']) ? $cfg['model'] : array_key_first($def['models']),
                'priority'=> isset($cfg['priority']) ? intval($cfg['priority']) : 99,
            );
        }
        uasort($providers, function($a, $b) { return $a['priority'] - $b['priority']; });
        return $providers;
    }

    private function get_active_providers() {
        $providers = $this->get_providers();
        $active = array();
        foreach ($providers as $key => $cfg) {
            if ($cfg['enabled'] && !empty($cfg['key'])) {
                $active[$key] = $cfg;
            }
        }
        return $active;
    }

    private function generate_meta($title, $content, $type = 'post', $focuskw = '') {
        $tone = get_option('yaf_tone', 'engaging');
        if (empty($focuskw)) $focuskw = $title;

        $active = $this->get_active_providers();
        if (!empty($active)) {
            $result = $this->cascade_ai($title, $focuskw, $tone, $active);
            if ($result) return $result;
        }
        // No AI provider: extract a factual description from the content
        // instead of baking a marketing template into Yoast fields.
        return $this->extractive_meta($content, $title);
    }

    private function extractive_meta($content, $fallback = '') {
        $text = preg_replace('/\[[^\]]+\]/', '', (string) $content);
        $text = wp_strip_all_tags($text);
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if ($text === '') $text = trim((string) $fallback);
        if ($text === '') return '';
        if (strlen($text) <= 155) return $text;
        $cut = substr($text, 0, 155);
        $space = strrpos($cut, ' ');
        if ($space > 80) return substr($cut, 0, $space);
        return substr($text, 0, 152) . '...';
    }

    private function cascade_ai($title, $kw, $tone, $providers) {
        $prompt = $this->build_prompt($title, $kw, $tone);

        foreach ($providers as $key => $cfg) {
            $def = self::$provider_defs[$key];
            $endpoint = $def['endpoint'];
            $model = $cfg['model'];
            $api_key = $cfg['key'];
            $format = $def['format'];
            $extra_headers = isset($def['extra_headers']) ? $def['extra_headers'] : array();

            if ($format === 'anthropic') {
                $result = $this->call_anthropic($prompt, $model, $api_key);
            } elseif ($format === 'gemini') {
                $result = $this->call_gemini($prompt, $model, $api_key);
            } else {
                $result = $this->call_ai_provider($prompt, $endpoint, $model, $api_key, $extra_headers);
            }
            if ($result) return $result;
        }
        return false;
    }

    private function build_prompt($title, $kw, $tone) {
        $tone_map = array(
            'engaging' => 'engaging, click-worthy, curiosity-driven',
            'professional' => 'professional, authoritative, corporate',
            'high-converting' => 'conversion-focused, action-driven, persuasive',
        );
        return "Generate a single SEO meta description for a page titled: \"$title\"\n"
            . "Focus keyphrase: $kw\n"
            . "Tone: " . ($tone_map[$tone] ?? $tone_map['engaging']) . "\n"
            . "Rules:\n- Maximum 155 characters\n- Start with the focus keyphrase or title\n"
            . "- Include a clear value proposition or CTA\n- Be specific, not generic\n"
            . "- Return ONLY the meta description text, nothing else\n";
    }

    private function call_ai_provider($prompt, $endpoint, $model, $api_key, $extra_headers = array()) {
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        );
        foreach ($extra_headers as $k => $v) {
            $headers[$k] = $v;
        }

        $response = wp_remote_post($endpoint, array(
            'headers' => $headers,
            'body' => wp_json_encode(array(
                'model' => $model,
                'messages' => array(array('role' => 'user', 'content' => $prompt)),
                'max_tokens' => 200,
                'temperature' => 0.7,
            )),
            'timeout' => 20,
        ));

        if (is_wp_error($response)) return false;

        $code = wp_remote_retrieve_response_code($response);
        if ($code === 429 || $code === 402 || $code === 401 || $code === 403) return false;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['choices'][0]['message']['content'])) return false;

        $meta = trim($body['choices'][0]['message']['content']);
        if (strlen($meta) > 155) $meta = substr($meta, 0, 152) . '...';
        return $meta;
    }

    private function call_anthropic($prompt, $model, $api_key) {
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'model'      => $model,
                'max_tokens' => 200,
                'messages'   => array(array('role' => 'user', 'content' => $prompt)),
            )),
            'timeout' => 20,
        ));

        if (is_wp_error($response)) return false;

        $code = wp_remote_retrieve_response_code($response);
        if ($code === 429 || $code === 402 || $code === 401 || $code === 403) return false;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['content'][0]['text'])) return false;

        $meta = trim($body['content'][0]['text']);
        if (strlen($meta) > 155) $meta = substr($meta, 0, 152) . '...';
        return $meta;
    }

    private function call_gemini($prompt, $model, $api_key) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;

        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode(array(
                'contents' => array(array('parts' => array(array('text' => $prompt)))),
                'generationConfig' => array('maxOutputTokens' => 200, 'temperature' => 0.7),
            )),
            'timeout' => 20,
        ));

        if (is_wp_error($response)) return false;

        $code = wp_remote_retrieve_response_code($response);
        if ($code === 429 || $code === 402 || $code === 403) return false;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['candidates'][0]['content']['parts'][0]['text'])) return false;

        $meta = trim($body['candidates'][0]['content']['parts'][0]['text']);
        if (strlen($meta) > 155) $meta = substr($meta, 0, 152) . '...';
        return $meta;
    }

    /* ─── Legacy OpenAI method (kept for backward compat) ─── */

    private function call_openai($title, $kw, $tone, $api_key) {
        $prompt = $this->build_prompt($title, $kw, $tone);
        return $this->call_ai_provider(
            $prompt,
            'https://api.openai.com/v1/chat/completions',
            get_option('yaf_openai_model', 'gpt-4o-mini'),
            $api_key
        );
    }

    private function generate_seo_title($post_title, $focuskw) {
        $site_name = get_bloginfo('name');
        $sep = ' | ';
        $base = $post_title;
        if (!empty($focuskw) && stripos($base, $focuskw) === false) {
            $base = $focuskw . ' - ' . $base;
        }
        $full = $base . $sep . $site_name;
        if (strlen($full) > 60) {
            $max_title = 60 - strlen($sep . $site_name);
            $base = substr($base, 0, $max_title - 3) . '...';
            $full = $base . $sep . $site_name;
        }
        return $full;
    }

    /* ─── History ─── */

    private function log_change($post_id, $field, $old, $new) {
        $history = get_option('yaf_history', array());
        array_unshift($history, array(
            'post_id' => $post_id,
            'title' => get_the_title($post_id),
            'field' => $field,
            'old' => $old ?: '[empty]',
            'new' => $new,
            'time' => current_time('mysql'),
            'user' => wp_get_current_user()->display_name,
        ));
        if (count($history) > 100) $history = array_slice($history, 0, 100);
        update_option('yaf_history', $history);
    }

    /* ═══════════════════════════════════════════════════════
       AUTO-FIX ON SAVE
       ═══════════════════════════════════════════════════════ */

    public function auto_fix_on_save($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (!in_array($post->post_type, $this->get_post_types())) return;
        if (get_option('yaf_auto_fix_on_save', 1) != 1) return;

        $this->fix_all_fixable($post_id);
    }

    /* ═══════════════════════════════════════════════════════
       AJAX HANDLERS
       ═══════════════════════════════════════════════════════ */

    public function ajax_get_stats() {
        check_ajax_referer('yaf_nonce', 'nonce');
        $query = new WP_Query(array(
            'post_type' => $this->get_post_types(),
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));

        $total = $query->found_posts;
        $optimized = 0;
        $needs_fix = 0;
        $type_counts = array('post' => 0, 'page' => 0);
        $issue_counts = array('seo' => 0, 'content' => 0, 'readability' => 0, 'social' => 0, 'technical' => 0);

        foreach ($query->posts as $post) {
            $type_counts[$post->post_type] = ($type_counts[$post->post_type] ?? 0) + 1;
            $issues = $this->analyze_post($post->ID);
            $fixable = array_filter($issues, function($i) { return $i['fixable']; });
            if (empty($fixable)) {
                $optimized++;
            } else {
                $needs_fix++;
            }
            foreach ($issues as $issue) {
                $issue_counts[$issue['type']] = ($issue_counts[$issue['type']] ?? 0) + 1;
            }
        }

        $coverage = $total > 0 ? round(($optimized / $total) * 100) : 100;

        wp_send_json_success(array(
            'total' => $total,
            'optimized' => $optimized,
            'needs_fix' => $needs_fix,
            'coverage' => $coverage,
            'types' => $type_counts,
            'issues' => $issue_counts,
        ));
    }

    public function ajax_get_gscc_stats() {
        check_ajax_referer('yaf_nonce', 'nonce');

        if (!class_exists('KingJamesSEO\Modules\Quality\Google_Scaled_Content_Compliance')) {
            wp_send_json_success(array(
                'available' => false,
                'avg_score' => 0,
                'compliant' => 0,
                'non_compliant' => 0,
                'total' => 0,
            ));
            return;
        }

        $gscc = \KingJamesSEO\Modules\Quality\Google_Scaled_Content_Compliance::instance();

        $query = new WP_Query(array(
            'post_type' => $this->get_post_types(),
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));

        $total = 0;
        $sum_score = 0;
        $compliant = 0;
        $non_compliant = 0;

        foreach ($query->posts as $post) {
            $content = $post->post_content;
            if (empty($content)) continue;

            $result = $gscc->audit_content($content, $post->post_title);
            $score = $result['compliance_score'];
            $sum_score += $score;
            $total++;

            if ($score >= 70) {
                $compliant++;
            } else {
                $non_compliant++;
            }
        }

        $avg_score = $total > 0 ? round($sum_score / $total) : 0;

        wp_send_json_success(array(
            'available' => true,
            'avg_score' => $avg_score,
            'compliant' => $compliant,
            'non_compliant' => $non_compliant,
            'total' => $total,
        ));
    }

    public function ajax_get_analysis() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $page = max(1, intval($_POST['page'] ?? 1));
        $filter = sanitize_text_field($_POST['filter'] ?? 'all');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $type_filter = sanitize_text_field($_POST['type_filter'] ?? '');

        $args = array(
            'post_type' => $this->get_post_types(),
            'posts_per_page' => $this->per_page,
            'paged' => $page,
            'post_status' => 'publish',
        );

        if (!empty($search)) {
            $args['s'] = $search;
        }
        if (!empty($type_filter) && in_array($type_filter, $this->get_post_types())) {
            $args['post_type'] = array($type_filter);
        }

        $query = new WP_Query($args);
        $results = array();

        $gscc = null;
        if (class_exists('KingJamesSEO\Modules\Quality\Google_Scaled_Content_Compliance')) {
            $gscc = \KingJamesSEO\Modules\Quality\Google_Scaled_Content_Compliance::instance();
        }

        foreach ($query->posts as $post) {
            $issues = $this->analyze_post($post->ID);
            $fixable_count = count(array_filter($issues, function($i) { return $i['fixable']; }));
            $metadesc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
            $score = intval(get_post_meta($post->ID, '_yoast_wpseo_linkdex', true));
            $readability = intval(get_post_meta($post->ID, '_yoast_wpseo_content_score', true));

            $gscc_score = 0;
            if ($gscc && !empty($post->post_content)) {
                $gscc_result = $gscc->audit_content($post->post_content, $post->post_title);
                $gscc_score = $gscc_result['compliance_score'];
            }

            $item = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $post->post_type,
                'metadesc' => $metadesc ?: '',
                'score' => $score,
                'readability' => $readability,
                'gscc_score' => $gscc_score,
                'issues' => $issues,
                'fixable_count' => $fixable_count,
                'edit_url' => get_edit_post_link($post->ID, 'raw'),
            );

            if ($filter === 'missing' && $fixable_count == 0) continue;
            if ($filter === 'optimized' && $fixable_count > 0) continue;
            if ($filter === 'post' && $post->post_type !== 'post') continue;
            if ($filter === 'page' && $post->post_type !== 'page') continue;

            $results[] = $item;
        }

        wp_send_json_success(array(
            'posts' => $results,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page,
        ));
    }

    public function ajax_fix_single() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $post_id = intval($_POST['post_id'] ?? 0);
        $fixed = $this->fix_all_fixable($post_id);
        wp_send_json_success(array('post_id' => $post_id, 'fixed' => $fixed));
    }

    public function ajax_fix_bulk() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $ids = array_map('intval', $_POST['post_ids'] ?? array());
        $total_fixed = 0;
        foreach ($ids as $id) {
            $total_fixed += $this->fix_all_fixable($id);
        }
        wp_send_json_success(array('fixed' => $total_fixed));
    }

    public function ajax_fix_all() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $query = new WP_Query(array(
            'post_type' => $this->get_post_types(),
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));

        $total_fixed = 0;
        foreach ($query->posts as $post) {
            $total_fixed += $this->fix_all_fixable($post->ID);
        }
        wp_send_json_success(array('fixed' => $total_fixed));
    }

    public function ajax_fix_issue() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $post_id = intval($_POST['post_id'] ?? 0);
        $issue_id = sanitize_text_field($_POST['issue_id'] ?? '');
        $result = $this->fix_single_issue($post_id, $issue_id);
        wp_send_json_success(array('result' => $result, 'post_id' => $post_id, 'issue_id' => $issue_id));
    }

    public function ajax_fix_all_issues() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $post_id = intval($_POST['post_id'] ?? 0);
        $fixed = $this->fix_all_fixable($post_id);
        wp_send_json_success(array('fixed' => $fixed, 'post_id' => $post_id));
    }

    public function ajax_undo() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $history = get_option('yaf_history', array());
        if (empty($history)) wp_send_json_error('Nothing to undo');

        $last = array_shift($history);
        if ($last['old'] === '[empty]') {
            delete_post_meta($last['post_id'], $last['field']);
        } else {
            update_post_meta($last['post_id'], $last['field'], $last['old']);
        }
        update_option('yaf_history', $history);
        wp_send_json_success(array('undone' => $last));
    }

    // ajax_save_settings is now defined in the new features section with CPT + feature flags

    public function ajax_get_history() {
        check_ajax_referer('yaf_nonce', 'nonce');
        $history = get_option('yaf_history', array());
        wp_send_json_success(array('history' => array_slice($history, 0, 30)));
    }

    public function ajax_generate_ai() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $active = $this->get_active_providers();
        if (empty($active)) wp_send_json_error('No AI providers configured. Add an API key in Settings.');

        $post_id = intval($_POST['post_id'] ?? 0);
        $post = get_post($post_id);
        if (!$post) wp_send_json_error('Post not found');

        $tone = get_option('yaf_tone', 'engaging');
        $focuskw = get_post_meta($post_id, '_yoast_wpseo_focuskw', true) ?: $post->post_title;
        $meta = $this->cascade_ai($post->post_title, $focuskw, $tone, $active);
        if (!$meta) wp_send_json_error('AI generation failed across all providers');

        $this->apply_field($post_id, '_yoast_wpseo_metadesc', $meta);
        wp_send_json_success(array('meta' => $meta));
    }

    public function ajax_test_provider() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $key = sanitize_text_field($_POST['key'] ?? '');
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        if (empty($key) || empty($provider)) wp_send_json_error('Missing key or provider');
        if (!isset(self::$provider_defs[$provider])) wp_send_json_error('Unknown provider');

        $def = self::$provider_defs[$provider];
        $model = array_key_first($def['models']);
        $extra = isset($def['extra_headers']) ? $def['extra_headers'] : array();

        if ($def['format'] === 'anthropic') {
            $result = $this->call_anthropic("Say 'OK' and nothing else.", $model, $key);
        } elseif ($def['format'] === 'gemini') {
            $result = $this->call_gemini("Say 'OK' and nothing else.", $model, $key);
        } else {
            $result = $this->call_ai_provider(
                "Say 'OK' and nothing else.",
                $def['endpoint'],
                $model,
                $key,
                $extra
            );
        }

        if ($result) {
            wp_send_json_success(array('message' => $def['name'] . ' connection successful!', 'model' => $model));
        } else {
            wp_send_json_error($def['name'] . ' connection failed. Check your API key.');
        }
    }

    /* ═══════════════════════════════════════════════════════
       ADMIN NOTICE
       ═══════════════════════════════════════════════════════ */

    public function admin_notice() {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, array('dashboard', 'posts', 'pages'))) return;
        $url = admin_url('admin.php?page=yoast-smart-autofixer');
        echo '<div class="notice notice-info is-dismissible" style="border-left-color:#a0429f;">';
        echo "<p><span class='dashicons dashicons-superhero' style='color:#a0429f;'></span> <strong>Auto-Fixer:</strong> Full SEO command center active. <a href='$url'>Open Dashboard</a></p></div>";
    }

    /* ═══════════════════════════════════════════════════════
       DASHBOARD
       ═══════════════════════════════════════════════════════ */

    public function render_dashboard() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div id="yaf-app" class="wrap">
            <div class="yaf-header">
                <div class="yaf-header-left">
                    <span class="dashicons dashicons-superhero yaf-logo"></span>
                    <div>
                        <h1>Auto-Fixer Dashboard</h1>
                        <p class="yaf-subtitle">Full-spectrum Yoast SEO command center</p>
                    </div>
                </div>
                <div class="yaf-header-right">
                    <button id="yaf-btn-settings" class="button"><span class="dashicons dashicons-admin-settings"></span> Settings</button>
                </div>
            </div>

            <div class="yaf-stats-grid" id="yaf-stats">
                <div class="yaf-stat-card yaf-stat-total">
                    <div class="yaf-stat-icon"><span class="dashicons dashicons-admin-post"></span></div>
                    <div class="yaf-stat-info"><span class="yaf-stat-num" id="yaf-total">-</span><span class="yaf-stat-label">Total Content</span></div>
                </div>
                <div class="yaf-stat-card yaf-stat-good">
                    <div class="yaf-stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                    <div class="yaf-stat-info"><span class="yaf-stat-num" id="yaf-optimized">-</span><span class="yaf-stat-label">Fully Optimized</span></div>
                </div>
                <div class="yaf-stat-card yaf-stat-bad">
                    <div class="yaf-stat-icon"><span class="dashicons dashicons-warning"></span></div>
                    <div class="yaf-stat-info"><span class="yaf-stat-num" id="yaf-missing">-</span><span class="yaf-stat-label">Needs Attention</span></div>
                </div>
                <div class="yaf-stat-card yaf-stat-gscc">
                    <div class="yaf-stat-icon"><span class="dashicons dashicons-shield"></span></div>
                    <div class="yaf-stat-info"><span class="yaf-stat-num" id="yaf-gscc-score">-</span><span class="yaf-stat-label">Avg GSCC Score</span></div>
                </div>
                <div class="yaf-stat-card yaf-stat-gscc-good">
                    <div class="yaf-stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                    <div class="yaf-stat-info"><span class="yaf-stat-num" id="yaf-gscc-compliant">-</span><span class="yaf-stat-label">GSCC Compliant</span></div>
                </div>
                <div class="yaf-stat-card yaf-stat-gscc-bad">
                    <div class="yaf-stat-icon"><span class="dashicons dashicons-universal-access-alt"></span></div>
                    <div class="yaf-stat-info"><span class="yaf-stat-num" id="yaf-gscc-fail">-</span><span class="yaf-stat-label">GSCC Non-Compliant</span></div>
                </div>
                <div class="yaf-stat-card yaf-stat-score">
                    <div class="yaf-stat-icon"><span class="dashicons dashicons-chart-bar"></span></div>
                    <div class="yaf-stat-info"><span class="yaf-stat-num" id="yaf-coverage">-</span><span class="yaf-stat-label">Coverage</span></div>
                </div>
            </div>

            <div class="yaf-progress-wrap">
                <div class="yaf-progress-bar"><div class="yaf-progress-fill" id="yaf-progress-fill"></div></div>
                <span class="yaf-progress-text" id="yaf-progress-text">0%</span>
            </div>

            <?php $reanalysis = get_option('yaf_last_reanalysis', array()); ?>
            <?php if (!empty($reanalysis) && isset($reanalysis['time'])): ?>
            <div class="yaf-reanalysis-notice">
                <span class="dashicons dashicons-chart-area" style="color:#1967d2;"></span>
                <strong>Weekly Reanalysis:</strong> Last checked <?php echo human_time_diff(strtotime($reanalysis['time']), current_time('timestamp')); ?> ago —
                <?php echo intval($reanalysis['total']); ?> posts, <?php echo intval($reanalysis['optimized']); ?> optimized (<?php echo intval($reanalysis['coverage']); ?>% coverage),
                <?php echo intval($reanalysis['issues_found']); ?> issues found.
            </div>
            <?php endif; ?>

            <div class="yaf-issues-bar" id="yaf-issues-bar">
                <div class="yaf-issue-pill yaf-pill-seo" data-type="seo"><span class="dashicons dashicons-search"></span> SEO: <strong id="yaf-issues-seo">0</strong></div>
                <div class="yaf-issue-pill yaf-pill-content" data-type="content"><span class="dashicons dashicons-edit"></span> Content: <strong id="yaf-issues-content">0</strong></div>
                <div class="yaf-issue-pill yaf-pill-readability" data-type="readability"><span class="dashicons dashicons-book"></span> Readability: <strong id="yaf-issues-readability">0</strong></div>
                <div class="yaf-issue-pill yaf-pill-social" data-type="social"><span class="dashicons dashicons-share"></span> Social: <strong id="yaf-issues-social">0</strong></div>
                <div class="yaf-issue-pill yaf-pill-technical" data-type="technical"><span class="dashicons dashicons-admin-generic"></span> Technical: <strong id="yaf-issues-technical">0</strong></div>
                <div class="yaf-issue-pill yaf-pill-gscc" data-type="gscc"><span class="dashicons dashicons-shield"></span> GSCC: <strong id="yaf-issues-gscc">0</strong> non-compliant</div>
            </div>

            <div class="yaf-toolbar">
                <div class="yaf-toolbar-left">
                    <div class="yaf-filters">
                        <button class="yaf-filter active" data-filter="all">All</button>
                        <button class="yaf-filter" data-filter="post">Posts</button>
                        <button class="yaf-filter" data-filter="page">Pages</button>
                        <button class="yaf-filter" data-filter="missing">Needs Fix</button>
                        <button class="yaf-filter" data-filter="optimized">Optimized</button>
                    </div>
                </div>
                <div class="yaf-toolbar-right">
                    <input type="text" id="yaf-search" placeholder="Search posts..." class="yaf-search-input">
                    <button id="yaf-btn-fix-all" class="button button-primary yaf-btn-primary"><span class="dashicons dashicons-superhero"></span> Fix All</button>
                    <button id="yaf-btn-fix-selected" class="button" disabled><span class="dashicons dashicons-yes"></span> Fix Selected</button>
                    <button id="yaf-btn-undo" class="button" title="Undo last change"><span class="dashicons dashicons-undo"></span></button>
                    <button id="yaf-btn-history" class="button" title="Change history"><span class="dashicons dashicons-history"></span></button>
                    <button id="yaf-btn-brief" class="button" title="Generate content brief"><span class="dashicons dashicons-welcome-write-blog"></span> Brief</button>
                </div>
            </div>

            <div class="yaf-table-wrap">
                <table class="wp-list-table widefat fixed striped yaf-table">
                    <thead>
                        <tr>
                            <th class="yaf-col-check"><input type="checkbox" id="yaf-check-all"></th>
                            <th class="yaf-col-title">Title & Type</th>
                            <th class="yaf-col-meta">Meta Description</th>
                            <th class="yaf-col-issues">Issues</th>
                            <th class="yaf-col-score">SEO</th>
                            <th class="yaf-col-score">Read.</th>
                            <th class="yaf-col-score yaf-col-gscc">GSCC</th>
                            <th class="yaf-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="yaf-tbody">
                        <tr><td colspan="8" class="yaf-loading"><span class="spinner is-active"></span> Analyzing all posts & pages...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="yaf-pagination" id="yaf-pagination"></div>
        </div>

        <!-- Issue Detail Modal -->
        <div id="yaf-issue-modal" class="yaf-modal" style="display:none;">
            <div class="yaf-modal-overlay"></div>
            <div class="yaf-modal-content yaf-modal-wide">
                <div class="yaf-modal-header">
                    <h2 id="yaf-issue-modal-title">Issues for: <span></span></h2>
                    <button class="yaf-modal-close">&times;</button>
                </div>
                <div class="yaf-modal-body">
                    <div id="yaf-issue-list"></div>
                </div>
                <div class="yaf-modal-footer">
                    <button id="yaf-fix-all-modal" class="button button-primary yaf-btn-primary"><span class="dashicons dashicons-superhero"></span> Fix All Auto-Fixable</button>
                    <button class="button yaf-modal-close">Close</button>
                </div>
            </div>
        </div>

        <!-- Preview Modal with Extended OG/Twitter Preview -->
        <div id="yaf-preview-modal" class="yaf-modal" style="display:none;">
            <div class="yaf-modal-overlay"></div>
            <div class="yaf-modal-content yaf-modal-wide">
                <div class="yaf-modal-header">
                    <h2>Content Preview</h2>
                    <button class="yaf-modal-close">&times;</button>
                </div>
                <div class="yaf-modal-body">
                    <div class="yaf-preview-tabs" style="display:flex;gap:0;margin-bottom:12px;border-bottom:2px solid #e0e0e0;">
                        <button class="yaf-preview-tab active button" data-type="google" style="border-radius:4px 4px 0 0;border-bottom:none;">Google SERP</button>
                        <button class="yaf-preview-tab button" data-type="og" style="border-radius:4px 4px 0 0;border-bottom:none;">Open Graph</button>
                        <button class="yaf-preview-tab button" data-type="twitter" style="border-radius:4px 4px 0 0;border-bottom:none;">Twitter Card</button>
                    </div>

                    <div id="yaf-preview-pane-google" class="yaf-preview-pane">
                        <div class="yaf-preview-google">
                            <div class="yaf-preview-url" id="yaf-preview-url"></div>
                            <div class="yaf-preview-title" id="yaf-preview-title"></div>
                            <div class="yaf-preview-meta" id="yaf-preview-desc"></div>
                        </div>
                        <div class="yaf-preview-details">
                            <div class="yaf-preview-row"><span>Characters:</span> <span id="yaf-preview-chars"></span></div>
                            <div class="yaf-preview-row"><span>Current:</span> <span id="yaf-preview-existing" class="yaf-preview-existing"></span></div>
                        </div>
                    </div>

                    <div id="yaf-preview-pane-og" class="yaf-preview-pane" style="display:none;">
                        <div class="yaf-preview-google">
                            <div style="background:#f0f0f1;padding:12px;border-radius:8px;margin-bottom:8px;text-align:center;">
                                <span class="dashicons dashicons-share" style="font-size:32px;width:32px;height:32px;color:#a0429f;"></span>
                            </div>
                            <div class="yaf-preview-details">
                                <div class="yaf-preview-row"><span>OG Title:</span> <span id="yaf-preview-og-title" style="font-weight:600;"></span></div>
                                <div class="yaf-preview-row"><span>OG Description:</span> <span id="yaf-preview-og-desc"></span></div>
                            </div>
                        </div>
                    </div>

                    <div id="yaf-preview-pane-twitter" class="yaf-preview-pane" style="display:none;">
                        <div class="yaf-preview-google">
                            <div style="background:#e8f0fe;padding:12px;border-radius:8px;margin-bottom:8px;text-align:center;">
                                <span class="dashicons dashicons-twitter" style="font-size:32px;width:32px;height:32px;color:#1da1f2;"></span>
                            </div>
                            <div class="yaf-preview-details">
                                <div class="yaf-preview-row"><span>Twitter Title:</span> <span id="yaf-preview-tw-title" style="font-weight:600;"></span></div>
                                <div class="yaf-preview-row"><span>Twitter Description:</span> <span id="yaf-preview-tw-desc"></span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="yaf-modal-footer">
                    <button id="yaf-preview-apply" class="button button-primary yaf-btn-primary">Apply This Meta</button>
                    <button id="yaf-preview-ai" class="button"><span class="dashicons dashicons-superhero"></span> Regenerate AI</button>
                    <button class="button yaf-modal-close">Cancel</button>
                </div>
            </div>
        </div>

        <!-- History Modal -->
        <div id="yaf-history-modal" class="yaf-modal" style="display:none;">
            <div class="yaf-modal-overlay"></div>
            <div class="yaf-modal-content">
                <div class="yaf-modal-header">
                    <h2>Change History</h2>
                    <button class="yaf-modal-close">&times;</button>
                </div>
                <div class="yaf-modal-body">
                    <div id="yaf-history-list"></div>
                </div>
            </div>
        </div>

        <!-- Settings Modal -->
        <div id="yaf-settings-modal" class="yaf-modal" style="display:none;">
            <div class="yaf-modal-overlay"></div>
            <div class="yaf-modal-content yaf-modal-wide">
                <div class="yaf-modal-header">
                    <h2>Auto-Fixer Settings</h2>
                    <button class="yaf-modal-close">&times;</button>
                </div>
                <div class="yaf-modal-body">
                    <div class="yaf-settings-grid">
                        <div class="yaf-settings-section">
                            <h3>Content Generation</h3>
                            <div class="yaf-field">
                                <label>Meta Description Tone</label>
                                <select id="yaf-set-tone">
                                    <option value="engaging">Engaging & Click-Worthy</option>
                                    <option value="professional">Professional Corporate</option>
                                    <option value="high-converting">High-Converting CTA</option>
                                </select>
                            </div>
                            <div class="yaf-field">
                                <label>Target Character Length</label>
                                <input type="number" id="yaf-set-length" min="120" max="155" value="155">
                            </div>
                            <div class="yaf-field">
                                <label><input type="checkbox" id="yaf-set-auto-save"> Auto-fix all fields on post save</label>
                            </div>
                        </div>
                        <div class="yaf-settings-section">
                            <h3>Supported Post Types</h3>
                            <p class="description" style="margin:-8px 0 10px;">Select which content types to analyze and fix.</p>
                            <div id="yaf-cpt-list">
                                <p class="description">Loading...</p>
                            </div>
                        </div>
                        <div class="yaf-settings-section" style="grid-column:1/-1;">
                            <h3>Enhanced Features</h3>
                            <p class="description" style="margin:-8px 0 10px;">Enable additional AI-powered SEO enhancements.</p>
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
                                <label style="display:flex;align-items:center;gap:6px;padding:8px;border:1px solid #e0e0e0;border-radius:6px;cursor:pointer;">
                                    <input type="checkbox" id="yaf-feature-image-gen"> <span class="dashicons dashicons-format-image"></span> Featured Image Gen
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;padding:8px;border:1px solid #e0e0e0;border-radius:6px;cursor:pointer;">
                                    <input type="checkbox" id="yaf-feature-alt-text"> <span class="dashicons dashicons-media-text"></span> Alt Text SEO
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;padding:8px;border:1px solid #e0e0e0;border-radius:6px;cursor:pointer;">
                                    <input type="checkbox" id="yaf-feature-breadcrumb"> <span class="dashicons dashicons-admin-links"></span> Breadcrumb Schema
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;padding:8px;border:1px solid #e0e0e0;border-radius:6px;cursor:pointer;">
                                    <input type="checkbox" id="yaf-feature-faq-schema"> <span class="dashicons dashicons-editor-help"></span> FAQ/HowTo Schema
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;padding:8px;border:1px solid #e0e0e0;border-radius:6px;cursor:pointer;">
                                    <input type="checkbox" id="yaf-feature-local-seo"> <span class="dashicons dashicons-building"></span> Local SEO Pack
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;padding:8px;border:1px solid #e0e0e0;border-radius:6px;cursor:pointer;">
                                    <input type="checkbox" id="yaf-feature-saas-dash"> <span class="dashicons dashicons-groups"></span> SaaS Member Dashboard
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;padding:8px;border:1px solid #e0e0e0;border-radius:6px;cursor:pointer;">
                                    <input type="checkbox" id="yaf-feature-content-brief"> <span class="dashicons dashicons-welcome-write-blog"></span> Content Brief Gen
                                </label>
                            </div>
                        </div>
                        <div class="yaf-settings-section yaf-providers-section">
                            <h3>AI Providers — Cascade Fallback</h3>
                            <p class="description" style="margin-bottom:12px;">Configure providers in priority order. When one fails or runs out of quota, the next is tried automatically. If all fail, smart templates are used.</p>
                            <?php
                            $saved = get_option('yaf_providers', array());
                            $provider_list = array(
                                'openai'     => array('name' => 'OpenAI',           'icon' => 'dashicons-networking',    'ph' => 'sk-...'),
                                'anthropic'  => array('name' => 'Anthropic (Claude)', 'icon' => 'dashicons-format-chat',   'ph' => 'sk-ant-...'),
                                'gemini'     => array('name' => 'Google Gemini',     'icon' => 'dashicons-google',        'ph' => 'AIza...'),
                                'groq'       => array('name' => 'Groq',             'icon' => 'dashicons-lightning',     'ph' => 'gsk_...'),
                                'deepseek'   => array('name' => 'DeepSeek',         'icon' => 'dashicons-performance',   'ph' => 'sk-...'),
                                'openrouter' => array('name' => 'OpenRouter',       'icon' => 'dashicons-admin-links',   'ph' => 'sk-or-...'),
                            );
                            $priority = 1;
                            foreach ($provider_list as $pk => $pinfo):
                                $def = self::$provider_defs[$pk];
                                $cfg = isset($saved[$pk]) ? $saved[$pk] : array();
                                $enabled = !empty($cfg['enabled']);
                                $key = isset($cfg['key']) ? $cfg['key'] : '';
                                $model = isset($cfg['model']) ? $cfg['model'] : array_key_first($def['models']);
                                $prio = isset($cfg['priority']) ? intval($cfg['priority']) : $priority;
                                $priority++;
                            ?>
                            <div class="yaf-provider-card<?php echo $enabled ? ' yaf-provider-active' : ''; ?>" data-provider="<?php echo esc_attr($pk); ?>">
                                <div class="yaf-provider-header">
                                    <label class="yaf-provider-toggle">
                                        <input type="checkbox" name="yaf_p_enabled[<?php echo esc_attr($pk); ?>]" value="1" <?php checked($enabled); ?> class="yaf-provider-enabled">
                                        <span class="yaf-provider-name"><span class="dashicons <?php echo esc_attr($def['icon']); ?>"></span> <?php echo esc_html($pinfo['name']); ?></span>
                                    </label>
                                    <span class="yaf-provider-status <?php echo !empty($key) ? 'yaf-status-ok' : 'yaf-status-off'; ?>">
                                        <?php echo !empty($key) ? 'Configured' : 'Not set'; ?>
                                    </span>
                                    <input type="hidden" name="yaf_p_priority[<?php echo esc_attr($pk); ?>]" value="<?php echo esc_attr($prio); ?>" class="yaf-provider-priority">
                                </div>
                                <div class="yaf-provider-body" <?php echo $enabled ? '' : 'style="display:none;"'; ?>>
                                    <div class="yaf-field">
                                        <label>API Key</label>
                                        <div class="yaf-provider-key-row">
                                            <input type="password" name="yaf_p_key[<?php echo esc_attr($pk); ?>]" value="<?php echo esc_attr($key); ?>" placeholder="<?php echo esc_attr($pinfo['ph']); ?>" class="regular-text yaf-provider-key">
                                            <button type="button" class="button yaf-test-btn" data-provider="<?php echo esc_attr($pk); ?>">Test</button>
                                        </div>
                                    </div>
                                    <div class="yaf-field">
                                        <label>Model</label>
                                        <select name="yaf_p_model[<?php echo esc_attr($pk); ?>]" class="yaf-provider-model">
                                            <?php foreach ($def['models'] as $mk => $ml): ?>
                                            <option value="<?php echo esc_attr($mk); ?>" <?php selected($model, $mk); ?>><?php echo esc_html($ml); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="yaf-modal-footer">
                    <button id="yaf-save-settings" class="button button-primary yaf-btn-primary">Save Settings</button>
                    <button class="button yaf-modal-close">Cancel</button>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="wrap"><h1>Auto-Fixer Settings</h1><p>Configure from the <a href="' . admin_url('admin.php?page=yoast-smart-autofixer') . '">main dashboard</a>.</p></div>';
    }

    /* ─── Migration: old single key → multi-provider ─── */

    private function migrate_old_key() {
        $providers = get_option('yaf_providers', null);
        if ($providers !== null) return;

        $old_key = get_option('yaf_openai_key', '');
        $old_model = get_option('yaf_openai_model', 'gpt-4o-mini');

        $providers = array();
        foreach (array_keys(self::$provider_defs) as $k) {
            $providers[$k] = array('enabled' => 0, 'key' => '', 'model' => '', 'priority' => 99);
        }

        if (!empty($old_key)) {
            $providers['openai'] = array(
                'enabled'  => 1,
                'key'      => $old_key,
                'model'    => $old_model,
                'priority' => 1,
            );
        }

        update_option('yaf_providers', $providers);
    }

    /* ─── Feature Flags ─── */

    private function load_feature_flags() {
        $this->post_types = get_option('yaf_post_types', array('post', 'page'));
        $this->feature_image_gen = get_option('yaf_feature_image_gen', 0);
        $this->feature_alt_text = get_option('yaf_feature_alt_text', 0);
        $this->feature_breadcrumb = get_option('yaf_feature_breadcrumb', 0);
        $this->feature_faq_schema = get_option('yaf_feature_faq_schema', 0);
        $this->feature_local_seo = get_option('yaf_feature_local_seo', 0);
        $this->feature_saas_dash = get_option('yaf_feature_saas_dash', 0);
        $this->feature_content_brief = get_option('yaf_feature_content_brief', 0);
    }

    /* ─── Ajax: Get Settings (fix for get_opt() bug) ─── */

    public function ajax_get_settings() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $saved = get_option('yaf_providers', array());
        $provider_configs = array();
        foreach (self::$provider_defs as $pk => $def) {
            $cfg = isset($saved[$pk]) ? $saved[$pk] : array();
            $provider_configs[$pk] = array(
                'enabled' => !empty($cfg['enabled']) ? 1 : 0,
                'key' => isset($cfg['key']) ? $cfg['key'] : '',
                'model' => isset($cfg['model']) ? $cfg['model'] : array_key_first($def['models']),
                'priority' => isset($cfg['priority']) ? intval($cfg['priority']) : 99,
            );
        }

        wp_send_json_success(array(
            'tone' => get_option('yaf_tone', 'engaging'),
            'target_length' => intval(get_option('yaf_target_length', 155)),
            'enforce_kw' => intval(get_option('yaf_enforce_kw', 1)),
            'auto_fix_on_save' => intval(get_option('yaf_auto_fix_on_save', 1)),
            'providers' => $provider_configs,
            'post_types' => get_option('yaf_post_types', array('post', 'page')),
            'feature_image_gen' => intval(get_option('yaf_feature_image_gen', 0)),
            'feature_alt_text' => intval(get_option('yaf_feature_alt_text', 0)),
            'feature_breadcrumb' => intval(get_option('yaf_feature_breadcrumb', 0)),
            'feature_faq_schema' => intval(get_option('yaf_feature_faq_schema', 0)),
            'feature_local_seo' => intval(get_option('yaf_feature_local_seo', 0)),
            'feature_saas_dash' => intval(get_option('yaf_feature_saas_dash', 0)),
            'feature_content_brief' => intval(get_option('yaf_feature_content_brief', 0)),
        ));
    }

    /* ─── Ajax: Get available CPTs for settings ─── */

    public function ajax_get_cpts() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $cpts = get_post_types(array('public' => true), 'objects');
        $options = array();
        foreach ($cpts as $cpt) {
            $options[] = array(
                'name' => $cpt->name,
                'label' => $cpt->label,
            );
        }
        wp_send_json_success(array('cpts' => $options));
    }

    /* ─── Update ajax_save_settings to include new fields ─── */

    public function ajax_save_settings() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        update_option('yaf_tone', sanitize_text_field($_POST['tone'] ?? 'engaging'));
        update_option('yaf_target_length', intval($_POST['target_length'] ?? 155));
        update_option('yaf_enforce_kw', intval($_POST['enforce_kw'] ?? 1));
        update_option('yaf_auto_fix_on_save', intval($_POST['auto_fix_on_save'] ?? 1));

        // Legacy OpenAI fields
        update_option('yaf_openai_key', sanitize_text_field($_POST['openai_key'] ?? ''));
        update_option('yaf_openai_model', sanitize_text_field($_POST['openai_model'] ?? 'gpt-4o-mini'));

        // Multi-provider configs
        $providers = array();
        $provider_keys = array('openai', 'anthropic', 'gemini', 'groq', 'deepseek', 'openrouter');
        foreach ($provider_keys as $pk) {
            $providers[$pk] = array(
                'enabled' => intval($_POST['yaf_p_enabled'][$pk] ?? 0),
                'key'     => sanitize_text_field($_POST['yaf_p_key'][$pk] ?? ''),
                'model'   => sanitize_text_field($_POST['yaf_p_model'][$pk] ?? ''),
                'priority'=> intval($_POST['yaf_p_priority'][$pk] ?? 99),
            );
        }
        update_option('yaf_providers', $providers);

        // CPT config
        $post_types = isset($_POST['post_types']) ? array_map('sanitize_text_field', (array)$_POST['post_types']) : array('post', 'page');
        if (empty($post_types)) $post_types = array('post', 'page');
        update_option('yaf_post_types', $post_types);

        // Feature flags
        update_option('yaf_feature_image_gen', intval($_POST['feature_image_gen'] ?? 0));
        update_option('yaf_feature_alt_text', intval($_POST['feature_alt_text'] ?? 0));
        update_option('yaf_feature_breadcrumb', intval($_POST['feature_breadcrumb'] ?? 0));
        update_option('yaf_feature_faq_schema', intval($_POST['feature_faq_schema'] ?? 0));
        update_option('yaf_feature_local_seo', intval($_POST['feature_local_seo'] ?? 0));
        update_option('yaf_feature_saas_dash', intval($_POST['feature_saas_dash'] ?? 0));
        update_option('yaf_feature_content_brief', intval($_POST['feature_content_brief'] ?? 0));

        $this->load_feature_flags();

        wp_send_json_success(array('message' => 'Settings saved'));
    }

    /* ─── FEATURE: Custom Post Type Support ─── */

    public function get_post_types() {
        return apply_filters('yaf_post_types', $this->post_types);
    }

    /* ─── FEATURE: AI Featured Image Generation ─── */

    public function ajax_generate_featured_image() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $post_id = intval($_POST['post_id'] ?? 0);
        $post = get_post($post_id);
        if (!$post) wp_send_json_error('Post not found');

        $result = $this->generate_featured_image($post_id);
        if ($result) {
            wp_send_json_success(array('image_url' => $result, 'post_id' => $post_id));
        }
        wp_send_json_error('Failed to generate featured image');
    }

    private function generate_featured_image($post_id) {
        $post = get_post($post_id);
        if (!$post) return false;
        if (has_post_thumbnail($post_id)) return wp_get_attachment_url(get_post_thumbnail_id($post_id));

        $focuskw = get_post_meta($post_id, '_yoast_wpseo_focuskw', true) ?: $post->post_title;

        // Try to get first image from content
        $content_image = $this->get_first_content_image($post->post_content);
        if ($content_image) {
            $attachment_id = $this->upload_image_from_url($content_image, $post_id);
            if ($attachment_id) {
                set_post_thumbnail($post_id, $attachment_id);
                return wp_get_attachment_url($attachment_id);
            }
        }

        // Try AI image generation via OpenRouter (Stability AI / DALL-E)
        $active = $this->get_active_providers();
        if (!empty($active) && isset($active['openrouter'])) {
            $cfg = $active['openrouter'];
            $prompt = "Generate a professional, high-quality featured image for a blog post titled: \"{$post->post_title}\" focused on: {$focuskw}. Style: modern, clean, suitable for an architecture/design website. Return ONLY the image URL, nothing else.";

            $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $cfg['key'],
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => home_url(),
                    'X-Title' => 'Yoast Auto-Fixer',
                ),
                'body' => wp_json_encode(array(
                    'model' => 'meta-llama/llama-3.2-11b-vision-instruct:free',
                    'messages' => array(array('role' => 'user', 'content' => $prompt)),
                    'max_tokens' => 500,
                )),
                'timeout' => 30,
            ));

            if (!is_wp_error($response)) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $image_url = $body['choices'][0]['message']['content'] ?? '';
                $image_url = trim($image_url);
                if (!empty($image_url) && filter_var($image_url, FILTER_VALIDATE_URL)) {
                    $attachment_id = $this->upload_image_from_url($image_url, $post_id);
                    if ($attachment_id) {
                        set_post_thumbnail($post_id, $attachment_id);
                        return wp_get_attachment_url($attachment_id);
                    }
                }
            }
        }

        return false;
    }

    private function upload_image_from_url($url, $post_id = 0) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_sideload_image($url, $post_id, null, 'id');
        if (is_wp_error($attachment_id)) return false;
        return $attachment_id;
    }

    /* ─── FEATURE: Image Alt Text & Filename SEO ─── */

    public function ajax_generate_alt_text() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $post_id = intval($_POST['post_id'] ?? 0);
        $post = get_post($post_id);
        if (!$post) wp_send_json_error('Post not found');

        $focuskw = get_post_meta($post_id, '_yoast_wpseo_focuskw', true) ?: $post->post_title;
        $updated = $this->fix_image_alt_text($post_id, $post, $focuskw);
        wp_send_json_success(array('updated_images' => $updated));
    }

    private function fix_image_alt_text($post_id, $post, $focuskw) {
        $content = $post->post_content;
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $imgs, PREG_SET_ORDER);
        if (empty($imgs)) return 0;

        $updated = 0;
        foreach ($imgs as $img) {
            $src = $img[1];

            // Check if alt text exists
            if (preg_match('/alt=["\']([^"\']*)["\']/i', $img[0], $alt_m)) {
                $alt = trim($alt_m[1]);
                if (!empty($alt)) continue;
            }

            // Generate alt text via AI
            $alt_text = $this->generate_alt_text_for_image($src, $focuskw, $post->post_title);
            if (empty($alt_text)) {
                $alt_text = $focuskw . ' - ' . $post->post_title;
            }

            $alt_text = esc_attr($alt_text);
            if (preg_match('/alt=["\']([^"\']*)["\']/i', $img[0])) {
                $new_img = preg_replace('/alt=["\'][^"\']*["\']/', 'alt="' . $alt_text . '"', $img[0]);
            } else {
                $new_img = str_replace('<img', '<img alt="' . $alt_text . '"', $img[0]);
            }

            $content = str_replace($img[0], $new_img, $content);
            $updated++;
        }

        if ($updated > 0) {
            wp_update_post(array('ID' => $post_id, 'post_content' => $content));
            $this->log_change($post_id, 'post_content', '[images]', '[AI set alt text for ' . $updated . ' images]');
        }

        return $updated;
    }

    private function generate_alt_text_for_image($image_url, $context, $title) {
        $active = $this->get_active_providers();
        if (empty($active)) return '';

        $prompt = "Generate a descriptive, SEO-friendly alt text (under 125 characters) for an image on a page titled \"$title\" with context: $context. Describe what the image likely shows based on the context. Return ONLY the alt text, nothing else.";

        foreach ($active as $key => $cfg) {
            $def = self::$provider_defs[$key];
            $result = false;
            if ($def['format'] === 'anthropic') {
                $result = $this->call_anthropic($prompt, $cfg['model'], $cfg['key']);
            } elseif ($def['format'] === 'gemini') {
                $result = $this->call_gemini($prompt, $cfg['model'], $cfg['key']);
            } else {
                $result = $this->call_ai_provider($prompt, $def['endpoint'], $cfg['model'], $cfg['key'], isset($def['extra_headers']) ? $def['extra_headers'] : array());
            }
            if ($result) return substr($result, 0, 125);
        }

        return '';
    }

    /* ─── FEATURE: Async Bulk Processing ─── */

    public function ajax_fix_bulk_async() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $ids = isset($_POST['post_ids']) ? array_map('intval', (array)$_POST['post_ids']) : array();
        if (empty($ids)) {
            $query = new WP_Query(array(
                'post_type' => $this->get_post_types(),
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'fields' => 'ids',
            ));
            $ids = $query->posts;
        }

        $batch_size = 5;
        update_option('yaf_batch_queue', $ids);
        update_option('yaf_batch_total', count($ids));
        update_option('yaf_batch_processed', 0);
        update_option('yaf_batch_fixed', 0);
        update_option('yaf_batch_status', 'running');

        // Process first batch immediately
        $this->process_batch($ids, 0, $batch_size);

        wp_send_json_success(array(
            'total' => count($ids),
            'batch_size' => $batch_size,
            'message' => count($ids) . ' posts queued for processing',
        ));
    }

    public function ajax_fix_bulk_progress() {
        check_ajax_referer('yaf_nonce', 'nonce');
        $total = intval(get_option('yaf_batch_total', 0));
        $processed = intval(get_option('yaf_batch_processed', 0));
        $fixed = intval(get_option('yaf_batch_fixed', 0));
        $status = get_option('yaf_batch_status', 'idle');

        wp_send_json_success(array(
            'total' => $total,
            'processed' => $processed,
            'fixed' => $fixed,
            'status' => $status,
            'percent' => $total > 0 ? round(($processed / $total) * 100) : 0,
        ));
    }

    public function cron_batch_process() {
        $ids = get_option('yaf_batch_queue', array());
        $processed = intval(get_option('yaf_batch_processed', 0));
        $status = get_option('yaf_batch_status', 'idle');

        if ($status !== 'running' || empty($ids)) return;

        $batch_size = 5;
        $this->process_batch($ids, $processed, $batch_size);
    }

    private function process_batch($ids, $offset, $batch_size) {
        $batch = array_slice($ids, $offset, $batch_size);
        if (empty($batch)) {
            update_option('yaf_batch_status', 'complete');
            return;
        }

        $fixed = intval(get_option('yaf_batch_fixed', 0));
        foreach ($batch as $id) {
            $fixed += $this->fix_all_fixable($id);
        }

        $processed = $offset + count($batch);
        update_option('yaf_batch_processed', $processed);
        update_option('yaf_batch_fixed', $fixed);

        if ($processed >= count($ids)) {
            update_option('yaf_batch_status', 'complete');
        } else {
            // Schedule next batch
            wp_schedule_single_event(time() + 2, 'yaf_batch_process');
        }
    }

    /* ─── FEATURE: Internal Linking Engine ─── */

    public function ajax_suggest_links() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $post_id = intval($_POST['post_id'] ?? 0);
        $post = get_post($post_id);
        if (!$post) wp_send_json_error('Post not found');

        $focuskw = get_post_meta($post_id, '_yoast_wpseo_focuskw', true) ?: $post->post_title;
        $suggestions = $this->suggest_internal_links($post_id, $focuskw);

        wp_send_json_success(array('links' => $suggestions));
    }

    private function suggest_internal_links($post_id, $focuskw) {
        $suggestions = array();

        // Find related posts by category/tag similarity
        $cats = wp_get_post_categories($post_id, array('fields' => 'ids'));
        $tags = wp_get_post_tags($post_id, array('fields' => 'ids'));

        $related = new WP_Query(array(
            'post_type' => $this->get_post_types(),
            'posts_per_page' => 5,
            'post__not_in' => array($post_id),
            'post_status' => 'publish',
            'category__in' => $cats,
            'tag__in' => $tags,
            'orderby' => 'rand',
        ));

        foreach ($related->posts as $r) {
            $suggestions[] = array(
                'id' => $r->ID,
                'title' => $r->post_title,
                'url' => get_permalink($r->ID),
                'reason' => 'Related by category/tag',
            );
        }

        // Also search by keyphrase in title
        if (!empty($focuskw)) {
            $kw_posts = new WP_Query(array(
                'post_type' => $this->get_post_types(),
                'posts_per_page' => 3,
                'post__not_in' => array_merge(array($post_id), wp_list_pluck($related->posts, 'ID')),
                'post_status' => 'publish',
                's' => $focuskw,
            ));

            foreach ($kw_posts->posts as $r) {
                $suggestions[] = array(
                    'id' => $r->ID,
                    'title' => $r->post_title,
                    'url' => get_permalink($r->ID),
                    'reason' => 'Matching keyphrase "' . $focuskw . '"',
                );
            }
        }

        return array_slice($suggestions, 0, 5);
    }

    // Override internal link fix to use smart suggestions
    private function fix_internal_links_smart($post_id, $post, $focuskw) {
        $content = $post->post_content;
        $suggestions = $this->suggest_internal_links($post_id, $focuskw);
        if (empty($suggestions)) return 0;

        $added = 0;
        foreach ($suggestions as $link) {
            if (empty($link['url']) || empty($link['title'])) continue;
            $anchor = esc_html($link['title']);
            $href = esc_url($link['url']);
            $link_html = ' <a href="' . $href . '">' . $anchor . '</a>';
            $last_p_tag = '</p>';
            $insert_pos = strrpos($content, $last_p_tag);
            if ($insert_pos !== false) {
                $content = substr_replace($content, $link_html . "\n" . $last_p_tag, $insert_pos + strlen($last_p_tag), 0);
                $added++;
                if ($added >= 2) break;
            }
        }

        if ($added > 0) {
            wp_update_post(array('ID' => $post_id, 'post_content' => $content));
            $this->log_change($post_id, 'post_content', '[no internal links]', '[Smart linking: added ' . $added . ' internal links]');
            return 1;
        }
        return 0;
    }

    /* ─── FEATURE: Local SEO Pack ─── */

    private function apply_local_seo_schema($post_id) {
        if (!$this->feature_local_seo) return;

        $schema_pt = get_post_meta($post_id, '_yoast_wpseo_schema_page_type', true);
        $schema_at = get_post_meta($post_id, '_yoast_wpseo_schema_article_type', true);

        // If on the contact/about page, set LocalBusiness
        $post = get_post($post_id);
        if (!$post) return;

        $title = strtolower($post->post_title);
        $slug = strtolower($post->post_name);

        $local_pages = array('contact', 'about', 'contact us', 'about us', 'our studio', 'location');
        $is_local_page = false;
        foreach ($local_pages as $lp) {
            if (strpos($title, $lp) !== false || strpos($slug, $lp) !== false) {
                $is_local_page = true;
                break;
            }
        }

        if ($is_local_page && empty($schema_pt)) {
            update_post_meta($post_id, '_yoast_wpseo_schema_page_type', 'LocalBusiness');
            $this->log_change($post_id, '_yoast_wpseo_schema_page_type', '[empty]', 'LocalBusiness');
        }

        // Add review schema for portfolio/project posts
        $project_types = array('client_project', 'portfolio', 'project');
        if (in_array($post->post_type, $project_types) && empty($schema_at)) {
            update_post_meta($post_id, '_yoast_wpseo_schema_article_type', 'Review');
            $this->log_change($post_id, '_yoast_wpseo_schema_article_type', '[empty]', 'Review');
        }
    }

    /* ─── FEATURE: FAQ & HowTo Schema ─── */

    private function apply_faq_schema($post_id) {
        if (!$this->feature_faq_schema) return;

        $post = get_post($post_id);
        if (!$post) return;

        $content = $post->post_content;
        $plain = wp_strip_all_tags($content, true);

        // Detect FAQ pattern (Q: / A: or Question: / Answer:)
        $has_faq = preg_match('/\b(FAQ|Frequently Asked Questions|Q\s*:|Question\s*:)/i', $plain);
        if (!$has_faq) return;

        // Set schema to FAQPage
        $current = get_post_meta($post_id, '_yoast_wpseo_schema_page_type', true);
        if (empty($current)) {
            update_post_meta($post_id, '_yoast_wpseo_schema_page_type', 'FAQPage');
            $this->log_change($post_id, '_yoast_wpseo_schema_page_type', '[empty]', 'FAQPage');
        }

        // Detect HowTo pattern (step-by-step instructions)
        $has_howto = preg_match('/\b(How to|Step \d|Instructions|Follow these steps)/i', $plain);
        if ($has_howto && empty(get_post_meta($post_id, '_yoast_wpseo_schema_article_type', true))) {
            update_post_meta($post_id, '_yoast_wpseo_schema_article_type', 'HowTo');
            $this->log_change($post_id, '_yoast_wpseo_schema_article_type', '[empty]', 'HowTo');
        }
    }

    /* ─── FEATURE: Extended SERP Preview ─── */

    public function ajax_preview() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $post_id = intval($_POST['post_id'] ?? 0);
        $post = get_post($post_id);
        if (!$post) wp_send_json_error('Post not found');

        $focuskw = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        $meta = $this->generate_meta($post->post_title, $post->post_content, $post->post_type, $focuskw ?: $post->post_title);
        $existing = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        $seo_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
        if (empty($seo_title)) $seo_title = $this->generate_seo_title($post->post_title, $focuskw ?: $post->post_title);

        // Extended preview data
        $og_title = get_post_meta($post_id, '_yoast_wpseo_opengraph-title', true) ?: $post->post_title;
        $og_desc = get_post_meta($post_id, '_yoast_wpseo_opengraph-description', true) ?: $meta;
        $og_image = get_post_meta($post_id, '_yoast_wpseo_opengraph-image', true);
        $tw_title = get_post_meta($post_id, '_yoast_wpseo_twitter-title', true) ?: $post->post_title;
        $tw_desc = get_post_meta($post_id, '_yoast_wpseo_twitter-description', true) ?: $meta;

        wp_send_json_success(array(
            'preview' => $meta,
            'existing' => $existing ?: '[empty]',
            'seo_title' => $seo_title,
            'title' => $post->post_title,
            'url' => get_permalink($post_id),
            'chars' => strlen($meta),
            'og_title' => $og_title,
            'og_desc' => $og_desc,
            'og_image' => $og_image,
            'tw_title' => $tw_title,
            'tw_desc' => $tw_desc,
        ));
    }

    /* ─── FEATURE: Content Brief Generator ─── */

    public function ajax_generate_content_brief() {
        check_ajax_referer('yaf_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $keyword = sanitize_text_field($_POST['keyword'] ?? '');
        if (empty($keyword)) wp_send_json_error('Keyword is required');

        $brief = $this->generate_content_brief($keyword);
        wp_send_json_success(array('brief' => $brief));
    }

    private function generate_content_brief($keyword) {
        $active = $this->get_active_providers();
        if (!empty($active)) {
            $prompt = "You are an SEO content strategist. Generate a comprehensive content brief for the keyword: \"$keyword\". "
                . "Include:\n1. Target audience (who is searching for this?)\n2. Search intent (informational/commercial/transactional)\n"
                . "3. Suggested title (SEO-optimized, under 60 chars)\n4. Meta description (under 155 chars)\n"
                . "5. Outline with 5-7 H2 headings\n6. Related keywords/LSI terms (5-10)\n"
                . "7. Internal link suggestions (pages on gabochiedesign.com to link to)\n"
                . "8. External link suggestions (authoritative sites to reference)\n"
                . "9. Tone recommendations\n10. Target word count\n\n"
                . "Format with clear section headers. Be specific to the architecture/design industry if applicable.";

            foreach ($active as $key => $cfg) {
                $def = self::$provider_defs[$key];
                $result = false;
                if ($def['format'] === 'anthropic') {
                    $result = $this->call_anthropic($prompt, $cfg['model'], $cfg['key']);
                } elseif ($def['format'] === 'gemini') {
                    $result = $this->call_gemini($prompt, $cfg['model'], $cfg['key']);
                } else {
                    $result = $this->call_ai_provider($prompt, $def['endpoint'], $cfg['model'], $cfg['key'], isset($def['extra_headers']) ? $def['extra_headers'] : array());
                }
                if ($result) return $result;
            }
        }

        // Template fallback
        return "# Content Brief: $keyword\n\n"
            . "## Target Audience\nProfessionals and decision-makers interested in $keyword\n\n"
            . "## Search Intent\nInformational / Commercial\n\n"
            . "## Suggested Title\n$keyword — Complete Guide for Professionals\n\n"
            . "## Meta Description\nDiscover everything you need to know about $keyword. Expert insights, practical tips, and proven strategies.\n\n"
            . "## Outline\n1. Introduction to $keyword\n2. Why $keyword Matters\n3. Key Benefits of $keyword\n4. How to Get Started with $keyword\n5. Common Challenges and Solutions\n6. Frequently Asked Questions\n7. Conclusion and Next Steps\n\n"
            . "## Related Keywords\n- $keyword guide, $keyword tips, $keyword best practices, $keyword for professionals, $keyword cost\n\n"
            . "## Internal Links\n- /services/\n- /portfolio/\n- /about/\n\n"
            . "## Tone\nProfessional, authoritative, accessible\n\n"
            . "## Target Word Count\n1,500-2,000 words\n";
    }

    /* ─── FEATURE: SaaS Member SEO Dashboard ─── */

    public function render_saas_dashboard() {
        if (!is_user_logged_in()) return '<p>Please <a href="' . wp_login_url() . '">log in</a> to view your SEO dashboard.</p>';

        $user_id = get_current_user_id();
        $user = wp_get_current_user();

        ob_start();
        ?>
        <div class="yaf-saas-dashboard" style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Oxygen,Ubuntu,sans-serif;max-width:800px;margin:0 auto;">
            <div style="background:linear-gradient(135deg,#a0429f,#6ec1a0);color:#fff;padding:24px;border-radius:12px;margin-bottom:20px;">
                <h2 style="margin:0 0 4px;font-size:20px;">Your SEO Dashboard</h2>
                <p style="margin:0;opacity:.9;">Track your content performance and get AI-powered improvement suggestions.</p>
            </div>

            <?php
            $user_posts = new WP_Query(array(
                'author' => $user_id,
                'post_type' => $this->get_post_types(),
                'posts_per_page' => -1,
                'post_status' => 'publish',
            ));

            $total = $user_posts->found_posts;
            $optimized = 0;
            $total_score = 0;
            $improvements = array();

            foreach ($user_posts->posts as $post) {
                $issues = $this->analyze_post($post->ID);
                $fixable = array_filter($issues, function($i) { return $i['fixable']; });
                if (empty($fixable)) $optimized++;
                $score = intval(get_post_meta($post->ID, '_yoast_wpseo_linkdex', true));
                $total_score += $score;

                if (!empty($fixable)) {
                    $improvements[] = array(
                        'title' => $post->post_title,
                        'edit_url' => get_edit_post_link($post->ID, 'raw'),
                        'issues' => count($fixable),
                        'score' => $score,
                    );
                }
            }

            $avg_score = $total > 0 ? round($total_score / $total) : 0;
            $coverage = $total > 0 ? round(($optimized / $total) * 100) : 0;
            ?>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;">
                <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:16px;text-align:center;">
                    <div style="font-size:28px;font-weight:700;color:#a0429f;"><?php echo $total; ?></div>
                    <div style="font-size:12px;color:#646970;text-transform:uppercase;font-weight:600;">Your Posts</div>
                </div>
                <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:16px;text-align:center;">
                    <div style="font-size:28px;font-weight:700;color:#137333;"><?php echo $avg_score; ?></div>
                    <div style="font-size:12px;color:#646970;text-transform:uppercase;font-weight:600;">Avg SEO Score</div>
                </div>
                <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:16px;text-align:center;">
                    <div style="font-size:28px;font-weight:700;color:#3b5ccc;"><?php echo $coverage; ?>%</div>
                    <div style="font-size:12px;color:#646970;text-transform:uppercase;font-weight:600;">Optimized</div>
                </div>
            </div>

            <?php if (!empty($improvements)): ?>
            <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:16px;margin-bottom:20px;">
                <h3 style="margin:0 0 12px;font-size:15px;">Suggested Improvements</h3>
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead><tr style="border-bottom:2px solid #e0e0e0;">
                        <th style="text-align:left;padding:8px 4px;">Post</th>
                        <th style="text-align:center;padding:8px 4px;">Issues</th>
                        <th style="text-align:center;padding:8px 4px;">Score</th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($improvements as $imp): ?>
                        <tr style="border-bottom:1px solid #f0f0f1;">
                            <td style="padding:8px 4px;"><a href="<?php echo esc_url($imp['edit_url']); ?>" target="_blank"><?php echo esc_html($imp['title']); ?></a></td>
                            <td style="text-align:center;padding:8px 4px;color:#c5221f;font-weight:600;"><?php echo $imp['issues']; ?> issues</td>
                            <td style="text-align:center;padding:8px 4px;"><?php echo $imp['score']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="background:#e6f4ea;border:1px solid #137333;border-radius:8px;padding:16px;text-align:center;color:#137333;">
                All your published content is fully optimized! Great work.
            </div>
            <?php endif; ?>

            <p style="font-size:12px;color:#646970;text-align:center;">Powered by Yoast SEO Smart Auto-Fixer</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_get_saas_stats() {
        check_ajax_referer('yaf_nonce', 'nonce');

        $user_id = intval($_POST['user_id'] ?? get_current_user_id());
        $user_posts = new WP_Query(array(
            'author' => $user_id,
            'post_type' => $this->get_post_types(),
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));

        $total = $user_posts->found_posts;
        $optimized = 0;
        $total_score = 0;

        foreach ($user_posts->posts as $post) {
            $issues = $this->analyze_post($post->ID);
            $fixable = array_filter($issues, function($i) { return $i['fixable']; });
            if (empty($fixable)) $optimized++;
            $score = intval(get_post_meta($post->ID, '_yoast_wpseo_linkdex', true));
            $total_score += $score;
        }

        wp_send_json_success(array(
            'total' => $total,
            'optimized' => $optimized,
            'avg_score' => $total > 0 ? round($total_score / $total) : 0,
        ));
    }

    /* ─── FEATURE: Breadcrumb Schema Optimization ─── */

    private function apply_breadcrumb_schema($post_id) {
        if (!$this->feature_breadcrumb) return;
        if (!defined('WPSEO_VERSION')) return;

        // Ensure Yoast breadcrumbs are enabled
        $breadcrumbs_enabled = get_option('wpseo_titles', array());
        if (empty($breadcrumbs_enabled['breadcrumbs-enable'])) {
            // We just flag this — user needs to enable in Yoast settings
            update_option('yaf_breadcrumb_notice', 1);
        }

        // Set breadcrumb separator
        $wpseo_titles = get_option('wpseo_titles', array());
        if (!isset($wpseo_titles['breadcrumbs-sep'])) {
            $wpseo_titles['breadcrumbs-sep'] = '›';
            update_option('wpseo_titles', $wpseo_titles);
        }
    }

    /* ─── FEATURE: Scheduled Re-analysis (Weekly Cron) ─── */

    public function cron_reanalysis() {
        $query = new WP_Query(array(
            'post_type' => $this->get_post_types(),
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));

        $total = $query->found_posts;
        $optimized = 0;
        $issues_found = 0;

        foreach ($query->posts as $post) {
            $issues = $this->analyze_post($post->ID);
            $fixable = array_filter($issues, function($i) { return $i['fixable']; });
            if (empty($fixable)) {
                $optimized++;
            } else {
                $issues_found += count($fixable);
            }
        }

        // Store report for dashboard
        update_option('yaf_last_reanalysis', array(
            'time' => current_time('mysql'),
            'total' => $total,
            'optimized' => $optimized,
            'issues_found' => $issues_found,
            'coverage' => $total > 0 ? round(($optimized / $total) * 100) : 100,
        ));

        // Send admin notification if issues found
        if ($issues_found > 0) {
            update_option('yaf_reanalysis_alert', array(
                'issues' => $issues_found,
                'posts' => $total - $optimized,
                'time' => current_time('mysql'),
            ));
        }
    }

    /* ─── FEATURE: GSCC Compliance Suggestions ─── */

    public function analyze_post($post_id) {
        $post = get_post($post_id);
        if (!$post) return array();

        $issues = $this->analyze_post_base($post_id);

        // Add GSCC compliance suggestions if available
        if (class_exists('KingJamesSEO\Modules\Quality\Google_Scaled_Content_Compliance')) {
            $gscc = \KingJamesSEO\Modules\Quality\Google_Scaled_Content_Compliance::instance();
            if (!empty($post->post_content)) {
                $gscc_result = $gscc->audit_content($post->post_content, $post->post_title);
                $gscc_score = $gscc_result['compliance_score'];

                if ($gscc_score < 70) {
                    $suggestions = $this->generate_gscc_suggestions($gscc_result);
                    foreach ($suggestions as $s) {
                        $issues[] = $s;
                    }
                }
            }
        }

        return $issues;
    }

    private function analyze_post_base($post_id) {
        // Original analyze_post logic renamed
        $post = get_post($post_id);
        if (!$post) return array();

        $issues = array();
        $content = $post->post_content;
        $title = $post->post_title;
        $plain = wp_strip_all_tags($content);
        $word_count = str_word_count($plain);

        $focuskw   = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        $seo_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
        $metadesc  = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        $linkdex   = intval(get_post_meta($post_id, '_yoast_wpseo_linkdex', true));
        $content_score = intval(get_post_meta($post_id, '_yoast_wpseo_content_score', true));
        $canonical = get_post_meta($post_id, '_yoast_wpseo_canonical', true);
        $og_title  = get_post_meta($post_id, '_yoast_wpseo_opengraph-title', true);
        $og_desc   = get_post_meta($post_id, '_yoast_wpseo_opengraph-description', true);
        $og_image  = get_post_meta($post_id, '_yoast_wpseo_opengraph-image', true);
        $tw_title  = get_post_meta($post_id, '_yoast_wpseo_twitter-title', true);
        $tw_desc   = get_post_meta($post_id, '_yoast_wpseo_twitter-description', true);
        $tw_image  = get_post_meta($post_id, '_yoast_wpseo_twitter-image', true);
        $schema_pt = get_post_meta($post_id, '_yoast_wpseo_schema_page_type', true);
        $schema_at = get_post_meta($post_id, '_yoast_wpseo_schema_article_type', true);
        $slug = $post->post_name;

        $is_empty_meta = empty($metadesc) || strpos($metadesc, 'Just another WordPress') !== false;
        $is_empty_title = empty($seo_title);

        // Check for featured image
        if (!has_post_thumbnail($post_id)) {
            $issues[] = array(
                'id' => 'no_featured_image',
                'type' => 'technical',
                'severity' => 'info',
                'title' => 'No featured image set',
                'description' => 'Featured images improve social sharing and visual appeal in archives.',
                'fixable' => $this->feature_image_gen,
                'fix_action' => 'fix_featured_image',
                'category' => 'technical',
            );
        }

        // Check image alt text
        if ($this->feature_alt_text) {
            preg_match_all('/<img[^>]*>/i', $content, $img_tags);
            $images_missing_alt = 0;
            foreach ($img_tags[0] as $img) {
                if (!preg_match('/alt=["\'][^"\']+["\']/i', $img)) {
                    $images_missing_alt++;
                }
            }
            if ($images_missing_alt > 0) {
                $issues[] = array(
                    'id' => 'missing_alt_text',
                    'type' => 'seo',
                    'severity' => 'warning',
                    'title' => $images_missing_alt . ' image(s) missing alt text',
                    'description' => 'Alt text improves accessibility and image SEO.',
                    'fixable' => true,
                    'fix_action' => 'fix_alt_text',
                    'category' => 'seo',
                );
            }
        }

        // Local SEO check
        if ($this->feature_local_seo) {
            $local_pages = array('contact', 'about', 'contact us', 'about us');
            $title_lower = strtolower($title);
            foreach ($local_pages as $lp) {
                if (strpos($title_lower, $lp) !== false && empty($schema_pt)) {
                    $issues[] = array(
                        'id' => 'missing_local_schema',
                        'type' => 'technical',
                        'severity' => 'info',
                        'title' => 'LocalBusiness schema not set',
                        'description' => 'This page should use LocalBusiness schema for local SEO.',
                        'fixable' => true,
                        'fix_action' => 'fix_local_schema',
                        'category' => 'technical',
                    );
                    break;
                }
            }
        }

        // FAQ schema check
        if ($this->feature_faq_schema) {
            if (preg_match('/\b(FAQ|Frequently Asked Questions|Q\s*:|Question\s*:)/i', $plain) && $schema_pt !== 'FAQPage') {
                $issues[] = array(
                    'id' => 'missing_faq_schema',
                    'type' => 'technical',
                    'severity' => 'info',
                    'title' => 'FAQ schema not set',
                    'description' => 'This page contains FAQ content but lacks FAQ schema markup.',
                    'fixable' => true,
                    'fix_action' => 'fix_faq_schema',
                    'category' => 'technical',
                );
            }
        }

        /* ── 1. SEO: Focus Keyphrase ── */
        if (empty($focuskw)) {
            $issues[] = array(
                'id' => 'no_focus_kw',
                'type' => 'seo',
                'severity' => 'error',
                'title' => 'No focus keyphrase set',
                'description' => 'Set a focus keyphrase in the Yoast sidebar to enable SEO analysis.',
                'fixable' => false,
                'fix_action' => 'manual',
                'edit_url' => get_edit_post_link($post_id, 'raw'),
            );
        }

        /* ── 2. SEO: Meta Description ── */
        if ($is_empty_meta) {
            $issues[] = array(
                'id' => 'missing_metadesc',
                'type' => 'seo',
                'severity' => 'error',
                'title' => 'Missing meta description',
                'description' => 'No meta description is set. Search engines will auto-generate one from your content.',
                'fixable' => true,
                'fix_action' => 'generate_meta',
                'category' => 'meta',
            );
        } elseif (strlen($metadesc) < 120) {
            $issues[] = array(
                'id' => 'metadesc_short',
                'type' => 'seo',
                'severity' => 'warning',
                'title' => 'Meta description too short',
                'description' => sprintf('Meta description is %d characters. Aim for 120-155.', strlen($metadesc)),
                'fixable' => true,
                'fix_action' => 'generate_meta',
                'category' => 'meta',
            );
        } elseif (strlen($metadesc) > 156) {
            $issues[] = array(
                'id' => 'metadesc_long',
                'type' => 'seo',
                'severity' => 'warning',
                'title' => 'Meta description too long',
                'description' => sprintf('Meta description is %d characters. It may be truncated in search results.', strlen($metadesc)),
                'fixable' => true,
                'fix_action' => 'generate_meta',
                'category' => 'meta',
            );
        }

        if (!empty($focuskw) && !$is_empty_meta && stripos($metadesc, $focuskw) === false) {
            $issues[] = array(
                'id' => 'kw_not_in_metadesc',
                'type' => 'seo',
                'severity' => 'warning',
                'title' => 'Keyphrase not in meta description',
                'description' => 'The focus keyphrase "' . esc_html($focuskw) . '" does not appear in the meta description.',
                'fixable' => true,
                'fix_action' => 'generate_meta',
                'category' => 'meta',
            );
        }

        /* ── 3. SEO: SEO Title ── */
        if ($is_empty_title) {
            $issues[] = array(
                'id' => 'missing_seo_title',
                'type' => 'seo',
                'severity' => 'error',
                'title' => 'Missing SEO title',
                'description' => 'No custom SEO title is set. Yoast will use the post title as fallback.',
                'fixable' => true,
                'fix_action' => 'generate_seo_title',
                'category' => 'title',
            );
        } else {
            $title_len = strlen($seo_title);
            if ($title_len < 30) {
                $issues[] = array(
                    'id' => 'seo_title_short',
                    'type' => 'seo',
                    'severity' => 'warning',
                    'title' => 'SEO title too short',
                    'description' => sprintf('SEO title is %d characters. Aim for 30-60 characters.', $title_len),
                    'fixable' => true,
                    'fix_action' => 'fix_seo_title_length',
                    'category' => 'title',
                );
            } elseif ($title_len > 60) {
                $issues[] = array(
                    'id' => 'seo_title_long',
                    'type' => 'seo',
                    'severity' => 'warning',
                    'title' => 'SEO title too long',
                    'description' => sprintf('SEO title is %d characters. It may be truncated in search results.', $title_len),
                    'fixable' => true,
                    'fix_action' => 'fix_seo_title_length',
                    'category' => 'title',
                );
            }
        }

        if (!empty($focuskw) && !$is_empty_title && stripos($seo_title, $focuskw) === false) {
            $issues[] = array(
                'id' => 'kw_not_in_title',
                'type' => 'seo',
                'severity' => 'warning',
                'title' => 'Keyphrase not in SEO title',
                'description' => 'The focus keyphrase "' . esc_html($focuskw) . '" does not appear in the SEO title.',
                'fixable' => true,
                'fix_action' => 'generate_seo_title',
                'category' => 'title',
            );
        }

        /* ── 4. SEO: Slug ── */
        if (!empty($focuskw) && !empty($slug) && stripos($slug, $this->slugify($focuskw)) === false) {
            $issues[] = array(
                'id' => 'kw_not_in_slug',
                'type' => 'seo',
                'severity' => 'info',
                'title' => 'Keyphrase not in URL slug',
                'description' => 'The focus keyphrase "' . esc_html($focuskw) . '" does not appear in the URL.',
                'fixable' => true,
                'fix_action' => 'fix_slug',
                'category' => 'seo',
            );
        }

        /* ── 5. Content: Text Length ── */
        if ($word_count < 300) {
            $issues[] = array(
                'id' => 'text_short',
                'type' => 'content',
                'severity' => 'warning',
                'title' => 'Content too short',
                'description' => sprintf('Post has %d words. Aim for 300+ words for SEO.', $word_count),
                'fixable' => false,
                'fix_action' => 'manual',
                'edit_url' => get_edit_post_link($post_id, 'raw'),
            );
        }

        /* ── 6. Content: Keyphrase in Introduction ── */
        if (!empty($focuskw)) {
            $first_para = $this->get_first_paragraph($content);
            if (stripos($first_para, $focuskw) === false) {
                $issues[] = array(
                    'id' => 'kw_not_in_intro',
                    'type' => 'content',
                    'severity' => 'info',
                    'title' => 'Keyphrase not in introduction',
                    'description' => 'The focus keyphrase does not appear in the first paragraph.',
                    'fixable' => true,
                    'fix_action' => 'fix_kw_intro',
                    'category' => 'content',
                );
            }
        }

        /* ── 7. Content: Keyphrase in Subheadings ── */
        if (!empty($focuskw)) {
            $subheadings = $this->get_subheadings($content);
            $kw_in_sub = false;
            foreach ($subheadings as $sh) {
                if (stripos($sh, $focuskw) !== false) { $kw_in_sub = true; break; }
            }
            if (!$kw_in_sub && count($subheadings) > 0) {
                $issues[] = array(
                    'id' => 'kw_not_in_subheadings',
                    'type' => 'content',
                    'severity' => 'info',
                    'title' => 'Keyphrase not in subheadings',
                    'description' => 'The focus keyphrase does not appear in any subheading (H2/H3).',
                    'fixable' => true,
                    'fix_action' => 'fix_kw_subheadings',
                    'category' => 'content',
                );
            }
        }

        /* ── 8. Content: Internal Links ── */
        $internal = $this->count_internal_links($content);
        if ($internal == 0) {
            $issues[] = array(
                'id' => 'no_internal_links',
                'type' => 'content',
                'severity' => 'warning',
                'title' => 'No internal links',
                'description' => 'This post has no links to other pages on your site.',
                'fixable' => true,
                'fix_action' => 'fix_internal_links',
                'category' => 'content',
            );
        }

        /* ── 9. Content: External Links ── */
        $external = $this->count_external_links($content);
        if ($external == 0) {
            $issues[] = array(
                'id' => 'no_external_links',
                'type' => 'content',
                'severity' => 'info',
                'title' => 'No external links',
                'description' => 'Adding links to authoritative sources can boost credibility.',
                'fixable' => true,
                'fix_action' => 'fix_external_links',
                'category' => 'content',
            );
        }

        /* ── 10. Content: Subheadings ── */
        if (count($subheadings ?? array()) == 0 && $word_count > 300) {
            $issues[] = array(
                'id' => 'no_subheadings',
                'type' => 'content',
                'severity' => 'info',
                'title' => 'No subheadings found',
                'description' => 'Long-form content benefits from H2/H3 subheadings for structure.',
                'fixable' => true,
                'fix_action' => 'fix_add_subheadings',
                'category' => 'content',
            );
        }

        /* ── 11. Readability: Transition Words ── */
        $transition_pct = $this->transition_word_percentage($plain);
        if ($transition_pct < 25 && $word_count > 300) {
            $issues[] = array(
                'id' => 'few_transitions',
                'type' => 'readability',
                'severity' => 'info',
                'title' => 'Few transition words',
                'description' => sprintf('Only %.0f%% of sentences use transition words. Aim for 25%%+.', $transition_pct),
                'fixable' => true,
                'fix_action' => 'fix_transitions',
                'category' => 'readability',
            );
        }

        /* ── 12. Readability: Passive Voice ── */
        $passive_pct = $this->passive_voice_percentage($plain);
        if ($passive_pct > 10) {
            $issues[] = array(
                'id' => 'passive_voice',
                'type' => 'readability',
                'severity' => 'info',
                'title' => 'Too much passive voice',
                'description' => sprintf('%.0f%% of sentences use passive voice. Aim for under 10%%.', $passive_pct),
                'fixable' => true,
                'fix_action' => 'fix_passive_voice',
                'category' => 'readability',
            );
        }

        /* ── 13. Readability: Sentence Length ── */
        $long_sent_pct = $this->long_sentence_percentage($plain);
        if ($long_sent_pct > 25) {
            $issues[] = array(
                'id' => 'long_sentences',
                'type' => 'readability',
                'severity' => 'info',
                'title' => 'Sentences too long',
                'description' => sprintf('%.0f%% of sentences are over 20 words. Break them up for readability.', $long_sent_pct),
                'fixable' => true,
                'fix_action' => 'fix_long_sentences',
                'category' => 'readability',
            );
        }

        /* ── 14. Social: Open Graph ── */
        if (empty($og_title)) {
            $issues[] = array(
                'id' => 'missing_og_title',
                'type' => 'social',
                'severity' => 'warning',
                'title' => 'Missing OG title',
                'description' => 'No Open Graph title set for social media sharing.',
                'fixable' => true,
                'fix_action' => 'fix_og',
                'category' => 'social',
            );
        }
        if (empty($og_desc)) {
            $issues[] = array(
                'id' => 'missing_og_desc',
                'type' => 'social',
                'severity' => 'warning',
                'title' => 'Missing OG description',
                'description' => 'No Open Graph description set for social media sharing.',
                'fixable' => true,
                'fix_action' => 'fix_og',
                'category' => 'social',
            );
        }
        if (empty($og_image)) {
            $issues[] = array(
                'id' => 'missing_og_image',
                'type' => 'social',
                'severity' => 'warning',
                'title' => 'Missing OG image',
                'description' => 'No Open Graph image set. Social shares will have no preview image.',
                'fixable' => true,
                'fix_action' => 'fix_og_image',
                'category' => 'social',
            );
        }

        /* ── 15. Social: Twitter Cards ── */
        if (empty($tw_title)) {
            $issues[] = array(
                'id' => 'missing_tw_title',
                'type' => 'social',
                'severity' => 'info',
                'title' => 'Missing Twitter title',
                'description' => 'No Twitter card title set.',
                'fixable' => true,
                'fix_action' => 'fix_twitter',
                'category' => 'social',
            );
        }
        if (empty($tw_desc)) {
            $issues[] = array(
                'id' => 'missing_tw_desc',
                'type' => 'social',
                'severity' => 'info',
                'title' => 'Missing Twitter description',
                'description' => 'No Twitter card description set.',
                'fixable' => true,
                'fix_action' => 'fix_twitter',
                'category' => 'social',
            );
        }
        if (empty($tw_image)) {
            $issues[] = array(
                'id' => 'missing_tw_image',
                'type' => 'social',
                'severity' => 'info',
                'title' => 'Missing Twitter image',
                'description' => 'No Twitter card image set.',
                'fixable' => true,
                'fix_action' => 'fix_tw_image',
                'category' => 'social',
            );
        }

        /* ── 16. Technical: Canonical ── */
        if (empty($canonical)) {
            $issues[] = array(
                'id' => 'missing_canonical',
                'type' => 'technical',
                'severity' => 'info',
                'title' => 'No canonical URL set',
                'description' => 'Yoast auto-generates canonicals, but custom ones can prevent duplicate content.',
                'fixable' => true,
                'fix_action' => 'fix_canonical',
                'category' => 'technical',
            );
        }

        /* ── 17. Technical: Schema ── */
        if (empty($schema_pt) && empty($schema_at)) {
            $issues[] = array(
                'id' => 'missing_schema',
                'type' => 'technical',
                'severity' => 'info',
                'title' => 'No schema type set',
                'description' => 'Set a schema page type and article type for rich results.',
                'fixable' => true,
                'fix_action' => 'fix_schema',
                'category' => 'technical',
            );
        }

        /* ── 18. Score: SEO ── */
        if ($linkdex > 0 && $linkdex < 41) {
            $issues[] = array(
                'id' => 'low_seo_score',
                'type' => 'seo',
                'severity' => 'error',
                'title' => 'SEO score is bad (' . $linkdex . '/100)',
                'description' => 'The overall SEO score needs significant improvement.',
                'fixable' => false,
                'fix_action' => 'manual',
                'edit_url' => get_edit_post_link($post_id, 'raw'),
            );
        }

        return $issues;
    }

    private function generate_gscc_suggestions($gscc_result) {
        $suggestions = array();
        $score = $gscc_result['compliance_score'];

        if ($score < 50) {
            $suggestions[] = array(
                'id' => 'gscc_low_quality',
                'type' => 'content',
                'severity' => 'error',
                'title' => 'GSCC: Low content quality (' . $score . '/100)',
                'description' => 'GSCC indicates this content may not meet Google\'s quality standards. Add original research, expert quotes, or data.',
                'fixable' => false,
                'fix_action' => 'manual',
                'category' => 'content',
            );
        } elseif ($score < 70) {
            $missing = array();
            if (empty($gscc_result['has_author'])) $missing[] = 'author attribution';
            if (empty($gscc_result['has_citations'])) $missing[] = 'citations/references';
            if (empty($gscc_result['has_recency'])) $missing[] = 'recent updates';

            $desc = 'GSCC score is ' . $score . '/70. ';
            if (!empty($missing)) {
                $desc .= 'Missing: ' . implode(', ', $missing) . '. ';
            }
            $desc .= 'Add these to improve compliance.';

            $suggestions[] = array(
                'id' => 'gscc_needs_improvement',
                'type' => 'content',
                'severity' => 'warning',
                'title' => 'GSCC: Needs improvement (' . $score . '/70)',
                'description' => $desc,
                'fixable' => false,
                'fix_action' => 'manual',
                'category' => 'content',
            );
        }

        return $suggestions;
    }

    /* ─── Update fix_all_fixable to handle new issue types ─── */

    public function fix_all_fixable($post_id) {
        $post = get_post($post_id);
        if (!$post) return 0;

        $fixed = 0;
        $focuskw = get_post_meta($post_id, '_yoast_wpseo_focuskw', true) ?: $post->post_title;

        // Fix featured image
        if ($this->feature_image_gen && !has_post_thumbnail($post_id)) {
            if ($this->generate_featured_image($post_id)) {
                $fixed++;
            }
        }

        // Fix alt text
        if ($this->feature_alt_text) {
            $fixed += $this->fix_image_alt_text($post_id, $post, $focuskw);
        }

        // Fix meta description
        $metadesc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        if (empty($metadesc) || strpos($metadesc, 'Just another WordPress') !== false || strlen($metadesc) < 120 || strlen($metadesc) > 156) {
            $meta = $this->generate_meta($post->post_title, $post->post_content, $post->post_type, $focuskw);
            $this->apply_field($post_id, '_yoast_wpseo_metadesc', $meta);
            $fixed++;
        }

        // Fix SEO title
        $seo_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
        if (empty($seo_title) || stripos($seo_title, $focuskw) === false) {
            $new_title = $this->generate_seo_title($post->post_title, $focuskw);
            $this->apply_field($post_id, '_yoast_wpseo_title', $new_title);
            $fixed++;
        }

        // Fix OG
        $this->fix_social_meta($post_id, $post, $focuskw);

        // Fix Twitter
        $this->fix_twitter_meta($post_id, $post, $focuskw);

        // Fix canonical
        if (empty(get_post_meta($post_id, '_yoast_wpseo_canonical', true))) {
            $this->apply_field($post_id, '_yoast_wpseo_canonical', get_permalink($post_id));
            $fixed++;
        }

        // Fix schema
        if (empty(get_post_meta($post_id, '_yoast_wpseo_schema_page_type', true))) {
            $schema_pt = ($post->post_type === 'page') ? 'WebPage' : 'Article';
            $this->apply_field($post_id, '_yoast_wpseo_schema_page_type', $schema_pt);
            $fixed++;
        }
        if (empty(get_post_meta($post_id, '_yoast_wpseo_schema_article_type', true))) {
            $this->apply_field($post_id, '_yoast_wpseo_schema_article_type', 'Article');
            $fixed++;
        }

        // Apply Local SEO schema if enabled
        $this->apply_local_seo_schema($post_id);

        // Apply FAQ schema if enabled
        $this->apply_faq_schema($post_id);

        // Apply breadcrumb schema if enabled
        $this->apply_breadcrumb_schema($post_id);

        // Fix SEO title length (short or long)
        $seo_title_len = strlen(get_post_meta($post_id, '_yoast_wpseo_title', true));
        if (!empty(get_post_meta($post_id, '_yoast_wpseo_title', true)) && ($seo_title_len < 30 || $seo_title_len > 60)) {
            $fixed += $this->fix_seo_title_length($post_id, $post, $focuskw);
        }

        // Fix slug
        $slug = $post->post_name;
        if (!empty($focuskw) && !empty($slug) && stripos($slug, $this->slugify($focuskw)) === false) {
            $fixed += $this->fix_slug($post_id, $post, $focuskw);
        }

        // Fix OG image
        if (empty(get_post_meta($post_id, '_yoast_wpseo_opengraph-image', true))) {
            $fixed += $this->fix_og_image($post_id, $post, $focuskw);
        }

        // Fix Twitter image
        if (empty(get_post_meta($post_id, '_yoast_wpseo_twitter-image', true))) {
            $fixed += $this->fix_tw_image($post_id, $post, $focuskw);
        }

        // Content fixes (require fresh post data after potential wp_update_post)
        $post = get_post($post_id);
        if ($post) {
            $content = $post->post_content;
            $plain = wp_strip_all_tags($content);
            $word_count = str_word_count($plain);

            // Fix keyphrase in intro
            if (!empty($focuskw)) {
                $first_para = $this->get_first_paragraph($content);
                if (stripos($first_para, $focuskw) === false) {
                    $fixed += $this->fix_kw_intro($post_id, $post, $focuskw);
                }
            }

            // Fix keyphrase in subheadings
            if (!empty($focuskw)) {
                preg_match_all('/<h[2-6][^>]*>(.*?)<\/h[2-6]>/i', $content, $sh_matches);
                $kw_in_sub = false;
                if (!empty($sh_matches[1])) {
                    foreach ($sh_matches[1] as $sh) {
                        if (stripos($sh, $focuskw) !== false) { $kw_in_sub = true; break; }
                    }
                }
                if (!$kw_in_sub && count($sh_matches[1] ?? array()) > 0) {
                    $fixed += $this->fix_kw_subheadings($post_id, $post, $focuskw);
                }
            }

            // Fix no subheadings
            preg_match_all('/<h[2-6]/i', $content, $h_count);
            if (count($h_count[0]) == 0 && $word_count > 300) {
                $fixed += $this->fix_add_subheadings($post_id, $post, $focuskw);
            }

            // Fix few transitions
            $transition_pct = $this->transition_word_percentage($plain);
            if ($transition_pct < 25 && $word_count > 300) {
                $fixed += $this->fix_transitions($post_id, $post, $focuskw);
            }

            // Fix passive voice
            $passive_pct = $this->passive_voice_percentage($plain);
            if ($passive_pct > 10) {
                $fixed += $this->fix_passive_voice($post_id, $post, $focuskw);
            }

            // Fix long sentences
            $long_sent_pct = $this->long_sentence_percentage($plain);
            if ($long_sent_pct > 25) {
                $fixed += $this->fix_long_sentences($post_id, $post, $focuskw);
            }

            // Fix internal links (smart)
            $home = parse_url(get_site_url(), PHP_URL_HOST);
            preg_match_all('/<a\s[^>]*href=["\']([^"\']*)["\']/i', $content, $link_matches);
            $has_internal = false;
            foreach ($link_matches[1] ?? array() as $url) {
                $host = parse_url($url, PHP_URL_HOST);
                if ($host === $home || empty($host)) { $has_internal = true; break; }
            }
            if (!$has_internal) {
                $fixed += $this->fix_internal_links_smart($post_id, $post, $focuskw);
            }

            // Fix external links
            $has_external = false;
            foreach ($link_matches[1] ?? array() as $url) {
                if (strpos($url, '#') === 0) continue;
                $host = parse_url($url, PHP_URL_HOST);
                if (!empty($host) && $host !== $home) { $has_external = true; break; }
            }
            if (!$has_external) {
                $fixed += $this->fix_external_links($post_id, $post, $focuskw);
            }
        }

        // Update SEO score
        $this->recalculate_score($post_id);

        return $fixed;
    }

    /* ─── Update fix_single_issue to handle new issue types ─── */

    public function fix_single_issue($post_id, $issue_id) {
        $post = get_post($post_id);
        if (!$post) return false;

        $focuskw = get_post_meta($post_id, '_yoast_wpseo_focuskw', true) ?: $post->post_title;

        switch ($issue_id) {
            case 'missing_metadesc':
            case 'metadesc_short':
            case 'metadesc_long':
            case 'kw_not_in_metadesc':
                $meta = $this->generate_meta($post->post_title, $post->post_content, $post->post_type, $focuskw);
                $this->apply_field($post_id, '_yoast_wpseo_metadesc', $meta);
                return true;

            case 'missing_seo_title':
            case 'kw_not_in_title':
                $title = $this->generate_seo_title($post->post_title, $focuskw);
                $this->apply_field($post_id, '_yoast_wpseo_title', $title);
                return true;

            case 'missing_og_title':
            case 'missing_og_desc':
                $this->fix_social_meta($post_id, $post, $focuskw);
                return true;

            case 'missing_tw_title':
            case 'missing_tw_desc':
                $this->fix_twitter_meta($post_id, $post, $focuskw);
                return true;

            case 'missing_canonical':
                $this->apply_field($post_id, '_yoast_wpseo_canonical', get_permalink($post_id));
                return true;

            case 'missing_schema':
                $schema_pt = ($post->post_type === 'page') ? 'WebPage' : 'Article';
                $this->apply_field($post_id, '_yoast_wpseo_schema_page_type', $schema_pt);
                $this->apply_field($post_id, '_yoast_wpseo_schema_article_type', 'Article');
                return true;

            case 'missing_local_schema':
                $this->apply_local_seo_schema($post_id);
                return true;

            case 'missing_faq_schema':
                $this->apply_faq_schema($post_id);
                return true;

            case 'missing_alt_text':
                $this->fix_image_alt_text($post_id, $post, $focuskw);
                return true;

            case 'no_featured_image':
                return $this->generate_featured_image($post_id) ? true : false;

            case 'seo_title_short':
            case 'seo_title_long':
                $this->fix_seo_title_length($post_id, $post, $focuskw);
                return true;

            case 'kw_not_in_slug':
                $this->fix_slug($post_id, $post, $focuskw);
                return true;

            case 'kw_not_in_intro':
                $this->fix_kw_intro($post_id, $post, $focuskw);
                return true;

            case 'kw_not_in_subheadings':
                $this->fix_kw_subheadings($post_id, $post, $focuskw);
                return true;

            case 'no_subheadings':
                $this->fix_add_subheadings($post_id, $post, $focuskw);
                return true;

            case 'few_transitions':
                $this->fix_transitions($post_id, $post, $focuskw);
                return true;

            case 'passive_voice':
                $this->fix_passive_voice($post_id, $post, $focuskw);
                return true;

            case 'long_sentences':
                $this->fix_long_sentences($post_id, $post, $focuskw);
                return true;

            case 'missing_og_image':
                $this->fix_og_image($post_id, $post, $focuskw);
                return true;

            case 'missing_tw_image':
                $this->fix_tw_image($post_id, $post, $focuskw);
                return true;

            case 'no_internal_links':
                $this->fix_internal_links_smart($post_id, $post, $focuskw);
                return true;

            case 'no_external_links':
                $this->fix_external_links($post_id, $post, $focuskw);
                return true;

            default:
                return false;
        }
    }
}

add_action('plugins_loaded', array('YoastSmartAutoFixer', 'get_instance'));

// Register weekly cron schedule
add_filter('cron_schedules', function($schedules) {
    $schedules['weekly'] = array(
        'interval' => 604800,
        'display' => __('Once Weekly'),
    );
    return $schedules;
});
