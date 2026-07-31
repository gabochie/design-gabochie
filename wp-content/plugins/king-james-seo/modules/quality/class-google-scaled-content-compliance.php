<?php

namespace KingJamesSEO\Modules\Quality;

use KingJamesSEO\Modules\Module_Interface;
use KingJamesSEO\Core\Plugin;

defined('ABSPATH') || exit;

class Google_Scaled_Content_Compliance implements Module_Interface {
    private static ?self $instance = null;

    private const THIN_CONTENT_WORDS = 300;
    private const IDEAL_MIN_WORDS = 800;
    private const KEYWORD_STUFFING_THRESHOLD = 0.03;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_module_id(): string {
        return 'google-scaled-content-compliance';
    }

    public function init(): void {
        add_action('kjs_gscc_bulk_audit', [$this, 'run_bulk_audit']);
    }

    public function audit_content(string $content, string $title = ''): array {
        $plain = strip_tags($content);

        $checks = [
            'thin_content'         => $this->check_thin_content($plain),
            'auto_generated_tone'  => $this->check_auto_generated($plain),
            'keyword_stuffing'     => $this->check_keyword_stuffing($plain, $title),
            'template_content'     => $this->check_template_content($plain),
            'structure_issues'     => $this->check_structure($content),
            'no_citations'         => $this->check_citations($plain),
            'low_readability'      => $this->check_readability($plain),
            'placeholder_content'  => $this->check_placeholders($plain),
        ];

        $issues = array_filter($checks, fn($c) => !empty($c['flagged']));
        $score = $this->calculate_compliance_score($checks, $plain);
        $recommendations = $this->generate_recommendations($checks, (object)[
            'ID'           => 0,
            'post_title'   => $title,
            'post_content' => $content,
        ]);

        return [
            'compliance_score' => $score,
            'issues'           => array_values($issues),
            'recommendations'  => $recommendations,
            'word_count'       => str_word_count($plain),
        ];
    }

    public function run_bulk_audit(): array {
        $posts = get_posts([
            'post_type'      => 'any',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        $results = [];
        foreach ($posts as $post_id) {
            $results[$post_id] = $this->audit_post($post_id);
        }

        return $results;
    }

    public function audit_post(int $post_id): array {
        $post = get_post($post_id);
        if (!$post || 'publish' !== $post->post_status) {
            return ['post_id' => $post_id, 'error' => 'Not a published post'];
        }

        $content = $post->post_content;
        $title = $post->post_title;
        $plain = strip_tags($content);

        $checks = [
            'thin_content'         => $this->check_thin_content($plain),
            'auto_generated_tone'  => $this->check_auto_generated($plain),
            'keyword_stuffing'     => $this->check_keyword_stuffing($plain, $title),
            'template_content'     => $this->check_template_content($plain),
            'structure_issues'     => $this->check_structure($content),
            'no_citations'         => $this->check_citations($plain),
            'low_readability'      => $this->check_readability($plain),
            'placeholder_content'  => $this->check_placeholders($plain),
        ];

        $issues = array_filter($checks, fn($c) => !empty($c['flagged']));
        $score = $this->calculate_compliance_score($checks, $plain);
        $recommendations = $this->generate_recommendations($checks, $post);

        global $wpdb;

        $wpdb->replace(
            Plugin::instance()->database()->table('kjs_gscc_audits'),
            [
                'post_id'            => $post_id,
                'compliance_score'   => $score,
                'issues_found'       => count($issues),
                'word_count'         => str_word_count($plain),
                'check_results'      => wp_json_encode($checks),
                'audited_at'         => current_time('mysql'),
            ]
        );

        $wpdb->delete(
            Plugin::instance()->database()->table('kjs_gscc_recommendations'),
            ['post_id' => $post_id]
        );

        foreach ($recommendations as $rec) {
            $wpdb->insert(
                Plugin::instance()->database()->table('kjs_gscc_recommendations'),
                [
                    'post_id'       => $post_id,
                    'issue_type'    => $rec['issue_type'],
                    'severity'      => $rec['severity'],
                    'description'   => $rec['description'],
                    'recommendation' => $rec['recommendation'],
                    'created_at'    => current_time('mysql'),
                ]
            );
        }

        Plugin::instance()->logger()->log(
            $this->get_module_id(),
            'audit_complete',
            "Post {$post_id}: score {$score}, " . count($issues) . " issues",
            '',
            ['post_id' => $post_id, 'score' => $score, 'issues' => count($issues)]
        );

        return [
            'post_id'          => $post_id,
            'compliance_score' => $score,
            'issues'           => array_values($issues),
            'recommendations'  => $recommendations,
        ];
    }

    private function check_thin_content(string $plain): array {
        $words = str_word_count($plain);
        return [
            'flagged' => $words < self::IDEAL_MIN_WORDS,
            'detail'  => "{$words} words",
            'severity' => $words < self::THIN_CONTENT_WORDS ? 'critical' : 'warning',
        ];
    }

    private function check_auto_generated(string $plain): array {
        $patterns = [
            'in (today\'s|this) (digital|modern|fast-paced) (world|age|landscape)',
            'it is (important|essential|crucial) to note',
            'when it comes to',
            'in order to',
            'there are (many|several|numerous)',
            'as we all know',
            'it goes without saying',
            'needless to say',
        ];

        $matches = 0;
        foreach ($patterns as $pattern) {
            $matches += preg_match_all('/' . $pattern . '/i', $plain);
        }

        return [
            'flagged' => $matches > 0,
            'detail'  => "{$matches} auto-generated pattern(s) detected",
            'severity' => $matches > 3 ? 'critical' : 'warning',
            'match_count' => $matches,
        ];
    }

    private function check_keyword_stuffing(string $plain, string $title): array {
        $words = str_word_count($plain);
        if ($words < 50) {
            return ['flagged' => false];
        }

        $word_freq = array_count_values(str_word_count(strtolower($plain), 1));
        arsort($word_freq);

        $title_words = str_word_count(strtolower($title), 1);
        $stuffing = [];

        foreach ($title_words as $tw) {
            if (strlen($tw) < 3) continue;
            $freq = $word_freq[$tw] ?? 0;
            $density = $freq / $words;
            if ($density > self::KEYWORD_STUFFING_THRESHOLD) {
                $stuffing[] = "{$tw}: {$freq} times ({$density})";
            }
        }

        return [
            'flagged' => !empty($stuffing),
            'detail'  => !empty($stuffing) ? implode('; ', $stuffing) : '',
            'severity' => 'warning',
        ];
    }

    private function check_template_content(string $plain): array {
        $templates = [
            'edit this section',
            'this is a sample',
            'content coming soon',
            'placeholder',
            'add your content here',
            'insert text here',
            'your content goes here',
            'this section is intentionally blank',
        ];

        $found = [];
        foreach ($templates as $phrase) {
            if (stripos($plain, $phrase) !== false) {
                $found[] = $phrase;
            }
        }

        return [
            'flagged'  => !empty($found),
            'detail'   => !empty($found) ? 'Found: ' . implode(', ', $found) : '',
            'severity' => 'critical',
        ];
    }

    private function check_structure(string $content): array {
        $issues = [];
        if (!preg_match('/<h[2-6][^>]*>/i', $content)) {
            $issues[] = 'No subheadings (H2-H6) found';
        }
        if (!preg_match('/<img[^>]*>/i', $content) && strlen(strip_tags($content)) > 500) {
            $issues[] = 'Content over 500 chars but no images';
        }
        if (!preg_match('/<ul[^>]*>/i', $content) && !preg_match('/<ol[^>]*>/i', $content)) {
            if (strlen(strip_tags($content)) > 1000) {
                $issues[] = 'No lists in long-form content';
            }
        }
        if (!preg_match('/<p[^>]*>/i', $content) && strlen($content) > 200) {
            $issues[] = 'No paragraph tags';
        }

        return [
            'flagged'  => !empty($issues),
            'detail'   => !empty($issues) ? implode('; ', $issues) : '',
            'severity' => 'warning',
        ];
    }

    private function check_citations(string $plain): array {
        $has_citations = (bool) preg_match('/(?:source|reference|citation|according to|study|research|data|found that|reported|published)/i', $plain);
        $has_links = false;

        $words = str_word_count($plain);
        if ($words > 500 && !$has_citations) {
            return [
                'flagged'  => true,
                'detail'   => 'Long-form content without sources or citations',
                'severity' => 'warning',
            ];
        }

        return ['flagged' => false];
    }

    private function check_readability(string $plain): array {
        $words = str_word_count($plain);
        if ($words < 50) return ['flagged' => false];

        $sentences = preg_split('/[.!?]+/', $plain);
        $sentences = array_filter($sentences, fn($s) => strlen(trim($s)) > 0);
        $sentence_count = count($sentences);

        if ($sentence_count === 0) return ['flagged' => false];

        $avg_words_per_sentence = $words / $sentence_count;
        $avg_syllables = $this->avg_syllables($plain);

        $score = 206.835 - 1.015 * $avg_words_per_sentence - 84.6 * $avg_syllables;

        return [
            'flagged' => $score < 40,
            'detail'  => "Flesch score: {$score}",
            'severity' => $score < 30 ? 'critical' : 'warning',
        ];
    }

    private function check_placeholders(string $plain): array {
        $placeholders = [
            'lorem ipsum',
            'xxx',
            'test content',
            'sample text',
            'todo',
            'fix this',
            'add link',
            'insert image',
        ];

        $found = [];
        foreach ($placeholders as $p) {
            if (stripos($plain, $p) !== false) {
                $found[] = $p;
            }
        }

        return [
            'flagged'  => !empty($found),
            'detail'   => !empty($found) ? 'Placeholder text: ' . implode(', ', $found) : '',
            'severity' => 'critical',
        ];
    }

    private function calculate_compliance_score(array $checks, string $plain): int {
        $score = 100;

        $penalties = [
            'thin_content'        => ['critical' => 30, 'warning' => 15],
            'auto_generated_tone' => ['critical' => 25, 'warning' => 10],
            'keyword_stuffing'    => ['critical' => 20, 'warning' => 10],
            'template_content'    => ['critical' => 40, 'warning' => 20],
            'structure_issues'    => ['critical' => 20, 'warning' => 10],
            'no_citations'        => ['critical' => 15, 'warning' => 8],
            'low_readability'     => ['critical' => 20, 'warning' => 10],
            'placeholder_content' => ['critical' => 50, 'warning' => 25],
        ];

        foreach ($checks as $key => $result) {
            if (!empty($result['flagged']) && isset($penalties[$key][$result['severity']])) {
                $score -= $penalties[$key][$result['severity']];
            }
        }

        $words = str_word_count($plain);
        if ($words > 2000) $score += 5;
        if ($words > 3000) $score += 5;

        return max(0, min(100, $score));
    }

    private function generate_recommendations(array $checks, object $post): array {
        $recommendations = [];
        $edit_url = !empty($post->ID) ? get_edit_post_link($post->ID) : null;

        if (!empty($checks['thin_content']['flagged'])) {
            $words = str_word_count(strip_tags($post->post_content));
            $rec = "Expand content from {$words} words to at least " . self::IDEAL_MIN_WORDS . " words. ";
            $rec .= "Add original research, case studies, examples, or expert quotes to increase depth.";
            $recommendations[] = [
                'issue_type'    => 'thin_content',
                'severity'      => $checks['thin_content']['severity'],
                'description'   => "Content is too short ({$words} words). Google may flag sub-800-word pages as low-value at scale.",
                'recommendation' => $rec,
                'edit_url'      => $edit_url,
            ];
        }

        if (!empty($checks['auto_generated_tone']['flagged'])) {
            $count = $checks['auto_generated_tone']['match_count'] ?? 0;
            $recommendations[] = [
                'issue_type'    => 'auto_generated_tone',
                'severity'      => $checks['auto_generated_tone']['severity'],
                'description'   => "{$count} auto-generated patterns detected. AI-generated or templated phrasing reduces trust signals.",
                'recommendation' => "Rewrite detected phrases with original, specific language. Add data, examples, or opinions unique to your brand. Target < 2 generic phrases per 1000 words.",
                'edit_url'      => $edit_url,
            ];
        }

        if (!empty($checks['keyword_stuffing']['flagged'])) {
            $recommendations[] = [
                'issue_type'    => 'keyword_stuffing',
                'severity'      => $checks['keyword_stuffing']['severity'],
                'description'   => 'Keywords from the title appear at high density in the body: ' . $checks['keyword_stuffing']['detail'],
                'recommendation' => "Reduce keyword frequency to natural density (1-2%). Use synonyms, pronouns, and related terms. Spread keyword usage naturally throughout the content.",
                'edit_url'      => $edit_url,
            ];
        }

        if (!empty($checks['template_content']['flagged'])) {
            $recommendations[] = [
                'issue_type'    => 'template_content',
                'severity'      => $checks['template_content']['severity'],
                'description'   => 'Template/placeholder language found: ' . $checks['template_content']['detail'],
                'recommendation' => "Replace all template placeholder text with unique, original content customized for this page's topic. Every page should have a unique introduction and conclusion.",
                'edit_url'      => $edit_url,
            ];
        }

        if (!empty($checks['structure_issues']['flagged'])) {
            $recommendations[] = [
                'issue_type'    => 'structure_issues',
                'severity'      => $checks['structure_issues']['severity'],
                'description'   => $checks['structure_issues']['detail'],
                'recommendation' => "Add descriptive H2/H3 subheadings every 300 words. Include images with descriptive alt text. Use bullet points or numbered lists for scannable content structure.",
                'edit_url'      => $edit_url,
            ];
        }

        if (!empty($checks['no_citations']['flagged'])) {
            $recommendations[] = [
                'issue_type'    => 'no_citations',
                'severity'      => $checks['no_citations']['severity'],
                'description'   => 'Long-form content lacks sources, references, or citations.',
                'recommendation' => "Add citations to authoritative sources (Google E-E-A-T signal). Include links to research, statistics, expert opinions, or official documentation to support claims.",
                'edit_url'      => $edit_url,
            ];
        }

        if (!empty($checks['low_readability']['flagged'])) {
            $recommendations[] = [
                'issue_type'    => 'low_readability',
                'severity'      => $checks['low_readability']['severity'],
                'description'   => $checks['low_readability']['detail'] . ' — hard to read for general audience.',
                'recommendation' => "Shorten sentences (target < 20 words). Use simpler vocabulary. Break long paragraphs into 2-3 sentence chunks. Add transitional phrases for flow.",
                'edit_url'      => $edit_url,
            ];
        }

        if (!empty($checks['placeholder_content']['flagged'])) {
            $recommendations[] = [
                'issue_type'    => 'placeholder_content',
                'severity'      => $checks['placeholder_content']['severity'],
                'description'   => 'Placeholder or test content found: ' . $checks['placeholder_content']['detail'],
                'recommendation' => "Remove all placeholder text and replace with final, quality content. Unfinished pages are a strong negative signal for Google's scaled content policies.",
                'edit_url'      => $edit_url,
            ];
        }

        return $recommendations;
    }

    private function avg_syllables(string $text): float {
        $words = str_word_count(strtolower($text), 1);
        if (empty($words)) return 0;

        $total = 0;
        foreach ($words as $word) {
            $syllables = preg_match_all('/[aeiouy]+/i', $word);
            $total += max(1, $syllables);
        }
        return $total / count($words);
    }
}
