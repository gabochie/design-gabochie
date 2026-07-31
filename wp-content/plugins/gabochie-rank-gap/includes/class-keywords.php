<?php
defined('ABSPATH') || exit;

class GRG_Keywords {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get_default_clusters() {
        return array(
            'architect_fees' => array(
                'label' => 'Architect Fees & Services',
                'keywords' => array(
                    'architect fees Ghana',
                    'how much does an architect cost in Ghana',
                    'architect fee scale Ghana ARC',
                    'architect services Ghana residential',
                    'best architect in Accra',
                    'architect consultation Ghana price',
                    'architect vs building contractor Ghana',
                    'diaspora architect Ghana',
                ),
            ),
            'building_costs' => array(
                'label' => 'Building Costs & Construction',
                'keywords' => array(
                    'cost to build a house in Ghana 2026',
                    'building cost per square meter Ghana',
                    'construction cost Ghana per m2',
                    'how much to build a 3 bedroom house in Ghana',
                    'cheap house building in Ghana',
                    'building materials prices Ghana 2026',
                    'labour cost construction Ghana',
                    'diaspora building Ghana cost',
                ),
            ),
            'permits_regulations' => array(
                'label' => 'Permits & Regulations',
                'keywords' => array(
                    'building permit Accra how long',
                    'building permit cost Ghana',
                    'MMDA building permit requirements',
                    'Ghana building code regulations',
                    'land title registration Ghana',
                    'building permit process Ghana',
                    'Ghana building regulations LI 1630',
                    'environmental permit construction Ghana',
                ),
            ),
            'qs_rates' => array(
                'label' => 'Quantity Surveying & Rates',
                'keywords' => array(
                    'quantity surveyor Ghana fees',
                    'QS rate guide Ghana',
                    'bill of quantities Ghana cost',
                    'cost estimator Ghana construction',
                    'GhIS fee scale quantity surveyors',
                    'how much does a QS cost in Ghana',
                ),
            ),
            'diaspora_building' => array(
                'label' => 'Diaspora Building',
                'keywords' => array(
                    'build house in Ghana from abroad',
                    'diaspora building Ghana guide',
                    'building in Ghana from UK',
                    'Ghana construction for diaspora',
                    'build a house in Ghana while living abroad',
                    'diaspora property investment Ghana',
                    'remotely build house Ghana',
                ),
            ),
            'property_land' => array(
                'label' => 'Property & Land',
                'keywords' => array(
                    'cost of land in Accra 2026',
                    'buy land in Ghana as foreigner',
                    'land prices Ghana per acre',
                    'rental property Ghana investment',
                    'house prices in Accra Ghana',
                    'Ghana real estate market 2026',
                    'property management Ghana fees',
                ),
            ),
            'mep_engineering' => array(
                'label' => 'MEP Engineering',
                'keywords' => array(
                    'MEP engineer Ghana fees',
                    'solar installation Ghana cost',
                    'backup generator sizing Ghana',
                    'plumbing design Ghana building',
                    'electrical engineer Ghana residential',
                    'dumsor solution Ghana architecture',
                ),
            ),
            'interior_design' => array(
                'label' => 'Interior Design',
                'keywords' => array(
                    'interior designer Ghana cost',
                    'interior design fees Accra',
                    'modern interior design Ghana',
                    'home renovation Ghana cost',
                    'interior decoration Ghana prices',
                ),
            ),
        );
    }

    public static function seed_default_clusters() {
        $clusters = get_option('grg_clusters', array());
        if (empty($clusters)) {
            update_option('grg_clusters', self::get_default_clusters(), false);
        }
    }

    public function get_clusters() {
        return get_option('grg_clusters', self::get_default_clusters());
    }

    public function save_clusters($clusters) {
        update_option('grg_clusters', $clusters, false);
    }

    public function get_all_keywords() {
        $clusters = $this->get_clusters();
        $all = array();
        foreach ($clusters as $cluster_key => $cluster) {
            $all[$cluster_key] = array(
                'label' => $cluster['label'],
                'keywords' => $cluster['keywords'],
            );
        }
        return $all;
    }

    public function get_post_keywords($post_id) {
        return get_post_meta($post_id, '_grg_keywords', true) ?: array();
    }

    public function set_post_keywords($post_id, $keywords) {
        update_post_meta($post_id, '_grg_keywords', $keywords);
    }

    public function get_rank_history($keyword) {
        global $wpdb;
        $key = sanitize_key('grg_rank_' . md5($keyword));
        $data = get_option($key, array());
        return $data;
    }

    public function record_rank($keyword, $position, $url = '') {
        $key = sanitize_key('grg_rank_' . md5($keyword));
        $history = get_option($key, array());
        $history[] = array(
            'date' => current_time('mysql'),
            'position' => $position,
            'url' => $url,
        );
        $history = array_slice($history, -100);
        update_option($key, $history, false);
    }

    public function get_latest_rank($keyword) {
        $history = $this->get_rank_history($keyword);
        if (empty($history)) return null;
        return end($history);
    }

    public function get_matched_post($keyword) {
        $all = get_posts(array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
        ));

        $best = null;
        $best_score = 0;

        foreach ($all as $pid) {
            $post = get_post($pid);
            if (!$post) continue;

            $title = strtolower($post->post_title);
            $content = strtolower($post->post_content);
            $kw = strtolower($keyword);
            $score = 0;

            if (strpos($title, $kw) !== false) $score += 50;
            $kc = substr_count($content, $kw);
            $score += min(50, $kc * 5);
            if (strpos($content, $kw) !== false && strpos($content, $kw) < 500) $score += 20;

            if ($score > $best_score) {
                $best_score = $score;
                $best = array('post_id' => $pid, 'score' => $score);
            }
        }

        return $best;
    }
}

GRG_Keywords::instance();
