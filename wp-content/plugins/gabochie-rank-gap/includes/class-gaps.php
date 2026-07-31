<?php
defined('ABSPATH') || exit;

class GRG_Gaps {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function analyze_gaps() {
        $kw = GRG_Keywords::instance();
        $clusters = $kw->get_clusters();
        $gaps = array();

        foreach ($clusters as $cluster_key => $cluster) {
            $covered = 0;
            $uncovered = 0;
            $details = array();

            foreach ($cluster['keywords'] as $keyword) {
                $match = $kw->get_matched_post($keyword);
                $latest_rank = $kw->get_latest_rank($keyword);

                $is_covered = $match && $match['score'] >= 50;
                if ($is_covered) {
                    $covered++;
                } else {
                    $uncovered++;
                }

                $details[] = array(
                    'keyword' => $keyword,
                    'is_covered' => $is_covered,
                    'match' => $match,
                    'rank' => $latest_rank,
                    'priority' => $this->get_priority($keyword, $is_covered),
                );
            }

            $total = $covered + $uncovered;
            $gaps[$cluster_key] = array(
                'label' => $cluster['label'],
                'total_keywords' => $total,
                'covered' => $covered,
                'gaps' => $uncovered,
                'coverage_percent' => $total > 0 ? round($covered / $total * 100) : 0,
                'details' => $details,
                'priority' => $this->get_cluster_priority($covered, $uncovered),
            );
        }

        return $gaps;
    }

    private function get_priority($keyword, $is_covered) {
        if ($is_covered) return 'done';

        $kw_lower = strtolower($keyword);
        $high_value = array('cost', 'price', 'fee', 'how much', 'rate', 'per square', 'diaspora', 'guide');
        foreach ($high_value as $term) {
            if (strpos($kw_lower, $term) !== false) {
                return 'high';
            }
        }
        return 'medium';
    }

    private function get_cluster_priority($covered, $uncovered) {
        $ratio = $uncovered / max(1, $covered + $uncovered);
        if ($ratio >= 0.6) return 'high';
        if ($ratio >= 0.3) return 'medium';
        return 'low';
    }

    public function get_competitor_keywords() {
        $competitor_keywords = array(
            'Ghana building cost per m2 2026' => array('source' => 'Competitor AEC blogs'),
            'how to get building permit in Accra' => array('source' => 'Property websites'),
            'cheapest way to build a house in Ghana' => array('source' => 'DIY forums, YouTube'),
            'architect vs contractor Ghana' => array('source' => 'Competitor architect sites'),
            'land title registration cost Ghana 2026' => array('source' => 'Legal sites, Lands Commission blog'),
            'Ghana housing deficit 2026 statistics' => array('source' => 'Government reports, news'),
            'sustainable building materials Ghana' => array('source' => 'Green building blogs'),
            'smart home technology Ghana cost' => array('source' => 'Tech publications'),
            'Ghana rental yield 2026' => array('source' => 'Property investment sites'),
            'construction loan Ghana requirements' => array('source' => 'Banking/finance sites'),
        );

        $results = array();
        foreach ($competitor_keywords as $keyword => $info) {
            $match = GRG_Keywords::instance()->get_matched_post($keyword);
            $results[] = array(
                'keyword' => $keyword,
                'source' => $info['source'],
                'we_cover' => $match && $match['score'] >= 50,
                'match_score' => $match ? $match['score'] : 0,
                'matched_post' => $match ? get_the_title($match['post_id']) : null,
            );
        }

        return $results;
    }

    public function get_priority_content_targets($limit = 20) {
        $gaps = $this->analyze_gaps();
        $targets = array();

        foreach ($gaps as $cluster_key => $cluster) {
            foreach ($cluster['details'] as $detail) {
                if (!$detail['is_covered']) {
                    $targets[] = array(
                        'cluster' => $cluster['label'],
                        'cluster_key' => $cluster_key,
                        'keyword' => $detail['keyword'],
                        'priority' => $detail['priority'],
                    );
                }
            }
        }

        usort($targets, function ($a, $b) {
            $order = array('high' => 0, 'medium' => 1, 'low' => 2, 'done' => 3);
            return $order[$a['priority']] - $order[$b['priority']];
        });

        return array_slice($targets, 0, $limit);
    }
}

GRG_Gaps::instance();
