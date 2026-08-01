<?php
defined('ABSPATH') || exit;

class GHBC_Ajax {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_ghbc_chat', [$this, 'ajax_chat']);
        add_action('wp_ajax_nopriv_ghbc_chat', [$this, 'ajax_chat']);
        add_action('wp_ajax_ghbc_exam_question', [$this, 'ajax_exam_question']);
        add_action('wp_ajax_ghbc_exam_answer', [$this, 'ajax_exam_answer']);
        add_action('wp_ajax_ghbc_small_building_check', [$this, 'ajax_small_building_check']);
        add_action('wp_ajax_ghbc_fire_safety', [$this, 'ajax_fire_safety']);
        add_action('wp_ajax_nopriv_ghbc_fire_safety', [$this, 'ajax_fire_safety']);
        add_action('wp_ajax_ghbc_travel_distance', [$this, 'ajax_travel_distance']);
        add_action('wp_ajax_nopriv_ghbc_travel_distance', [$this, 'ajax_travel_distance']);
        add_action('wp_ajax_ghbc_exit_width', [$this, 'ajax_exit_width']);
        add_action('wp_ajax_nopriv_ghbc_exit_width', [$this, 'ajax_exit_width']);
        add_action('wp_ajax_ghbc_fire_rating', [$this, 'ajax_fire_rating']);
        add_action('wp_ajax_nopriv_ghbc_fire_rating', [$this, 'ajax_fire_rating']);
        add_action('wp_ajax_ghbc_sprinkler_check', [$this, 'ajax_sprinkler_check']);
        add_action('wp_ajax_nopriv_ghbc_sprinkler_check', [$this, 'ajax_sprinkler_check']);
        add_action('wp_ajax_ghbc_code_search', [$this, 'ajax_code_search']);
        add_action('wp_ajax_nopriv_ghbc_code_search', [$this, 'ajax_code_search']);
        add_action('wp_ajax_ghbc_clause_lookup', [$this, 'ajax_clause_lookup']);
        add_action('wp_ajax_nopriv_ghbc_clause_lookup', [$this, 'ajax_clause_lookup']);
        add_action('wp_ajax_ghbc_test_connection', [$this, 'ajax_test_connection']);
    }

    public function ajax_chat() {
        $this->verify_nonce();

        if (!apply_filters('ghbc_user_can_query', true, get_current_user_id())) {
            wp_send_json_error(['message' => 'Query limit reached. Upgrade your plan.']);
        }

        $query   = sanitize_text_field($_POST['query'] ?? '');
        $product = sanitize_text_field($_POST['product'] ?? 'compliance');
        $history = json_decode(stripslashes($_POST['history'] ?? '[]'), true) ?: [];

        if (empty($query)) {
            wp_send_json_error(['message' => 'Query is required.']);
        }

        $assistant = GHBC_Assistant::instance();
        $result = $assistant->chat($query, $history, $product);

        if ($result['success']) {
            wp_send_json_success([
                'response' => $result['text'],
                'usage'    => $result['usage'],
            ]);
        }
        wp_send_json_error(['message' => $result['text']]);
    }

    public function ajax_exam_question() {
        $this->verify_nonce();

        $assistant = GHBC_Assistant::instance();
        $result = $assistant->chat('Generate a single multiple-choice question (4 options) from the Ghana Building Code. Return JSON with: question, options (A/B/C/D), correct, clause, explanation.', []);

        $json = $this->extract_json($result['text'] ?? '');
        if ($json) {
            wp_send_json_success($json);
        }
        wp_send_json_success([
            'question'    => $result['text'],
            'options'     => ['A' => 'Option A', 'B' => 'Option B', 'C' => 'Option C', 'D' => 'Option D'],
            'correct'     => 'A',
            'explanation' => 'See above.',
        ]);
    }

    public function ajax_exam_answer() {
        $this->verify_nonce();
        $correct = sanitize_text_field($_POST['correct'] ?? '');
        $answer  = sanitize_text_field($_POST['answer'] ?? '');
        $explanation = sanitize_text_field($_POST['explanation'] ?? '');

        $is_correct = strtoupper($correct) === strtoupper($answer);
        wp_send_json_success([
            'correct'     => $is_correct,
            'explanation' => $explanation,
            'points'      => $is_correct ? 1 : -0.25,
        ]);
    }

    public function ajax_small_building_check() {
        $this->verify_nonce();

        $data = [
            'storeys'   => (int)($_POST['storeys'] ?? 0),
            'area'      => (float)($_POST['area'] ?? 0),
            'height'    => (float)($_POST['height'] ?? 0),
            'occupancy' => sanitize_text_field($_POST['occupancy'] ?? ''),
        ];

        $tools = GHBC_Tools::instance();
        $result = $tools->execute('check_small_building', $data);

        $assistant = GHBC_Assistant::instance();
        $llm_result = $assistant->chat(
            "A user's building: storeys={$data['storeys']}, area={$data['area']}m\u00b2, height={$data['height']}m, occupancy={$data['occupancy']}. "
            . "Part 35 check result: $result. "
            . "Explain in simple terms what this means for the user and what they should do next.",
            [],
            'small_building'
        );

        wp_send_json_success([
            'qualifies'   => strpos($result, 'YES') === 0,
            'raw_result'  => $result,
            'explanation' => $llm_result['text'] ?? $result,
        ]);
    }

    public function ajax_fire_safety() {
        $this->verify_nonce();

        $occupancy  = sanitize_text_field($_POST['occupancy'] ?? 'B');
        $height     = sanitize_text_field($_POST['height'] ?? 'up to 2 storeys');
        $area       = (float)($_POST['area'] ?? 0);
        $storeys    = (int)($_POST['storeys'] ?? 1);
        $sprinklered = !empty($_POST['sprinklered']);
        $basement   = !empty($_POST['basement']);

        $tools = GHBC_Tools::instance();
        $rating_results = [];
        $elements = ['structural_frame', 'bearing_walls', 'floors', 'roofs'];
        foreach ($elements as $el) {
            $rating_results[$el] = $tools->execute('calculate_fire_rating', [
                'occupancy'   => $occupancy,
                'height'      => $height,
                'sprinklered' => $sprinklered,
                'type'        => $el,
            ]);
        }

        $sprinkler_req = $tools->execute('check_sprinkler_requirement', [
            'occupancy'   => $occupancy,
            'total_area'  => $area,
            'storeys'     => $storeys,
            'has_basement' => $basement,
        ]);

        $travel = $tools->execute('calculate_travel_distance', [
            'occupancy'   => $occupancy,
            'sprinklered' => $sprinklered,
        ]);

        $llm = GHBC_LLM::instance();
        $llm_summary = '';
        if ($llm->is_ready()) {
            $assistant = GHBC_Assistant::instance();
            $result = $assistant->chat(
                "A user checked fire safety for a Group $occupancy building, $storeys storeys, {$area}m\u00b2. "
                . "Fire ratings: {$rating_results['structural_frame']} | {$rating_results['bearing_walls']} | {$rating_results['floors']} | {$rating_results['roofs']}. "
                . "Sprinkler check: $sprinkler_req. Travel distance: $travel. "
                . "Summarize in simple terms what the user needs to know and any code references.",
                [],
                'compliance'
            );
            if (!empty($result['success']) && !empty($result['text'])) {
                $llm_summary = $result['text'];
            }
        }

        wp_send_json_success([
            'ratings'        => $rating_results,
            'sprinkler'      => $sprinkler_req,
            'travel'         => $travel,
            'llm_summary'    => $llm_summary,
        ]);
    }

    public function ajax_travel_distance() {
        $this->verify_nonce();
        $occupancy  = sanitize_text_field($_POST['occupancy'] ?? 'B');
        $sprinklered = !empty($_POST['sprinklered']);

        $tools = GHBC_Tools::instance();
        $result = $tools->execute('calculate_travel_distance', [
            'occupancy'   => $occupancy,
            'sprinklered' => $sprinklered,
        ]);

        $occ_short = substr($occupancy, 0, 1);
        $distances = [
            'A' => ['ns' => 45, 's' => 60],
            'B' => ['ns' => 60, 's' => 75],
            'E' => ['ns' => 45, 's' => 60],
            'F' => ['ns' => 60, 's' => 75],
            'H' => ['ns' => 30, 's' => 45],
            'I' => ['ns' => 30, 's' => 45],
            'M' => ['ns' => 45, 's' => 60],
            'R' => ['ns' => 45, 's' => 60],
            'S' => ['ns' => 60, 's' => 75],
        ];
        $entry = $distances[$occ_short] ?? ['ns' => 45, 's' => 60];
        $max = $sprinklered ? $entry['s'] : $entry['ns'];

        wp_send_json_success([
            'raw_result'  => $result,
            'distance'    => $max,
            'occupancy'   => $occupancy,
            'sprinklered' => $sprinklered,
        ]);
    }

    public function ajax_exit_width() {
        $this->verify_nonce();
        $occupants = (int)($_POST['occupants'] ?? 0);
        $type      = sanitize_text_field($_POST['type'] ?? 'stairs');

        $tools = GHBC_Tools::instance();
        $result = $tools->execute('calculate_exit_width', [
            'occupant_load' => $occupants,
            'exit_type'      => $type,
        ]);

        $factor = $type === 'stairs' ? 0.2 : ($type === 'doors' ? 0.15 : 0.1);
        $width_mm = ceil($occupants * $factor);
        $min_exits = ceil($occupants / 500);

        wp_send_json_success([
            'raw_result'   => $result,
            'width_mm'     => $width_mm,
            'width_m'      => $width_mm / 1000,
            'occupants'    => $occupants,
            'type'         => $type,
            'min_exits'    => $min_exits,
        ]);
    }

    public function ajax_fire_rating() {
        $this->verify_nonce();
        $occupancy  = sanitize_text_field($_POST['occupancy'] ?? 'B');
        $height     = sanitize_text_field($_POST['height'] ?? 'up to 2 storeys');
        $element    = sanitize_text_field($_POST['element'] ?? 'structural_frame');
        $sprinklered = !empty($_POST['sprinklered']);

        $tools = GHBC_Tools::instance();
        $result = $tools->execute('calculate_fire_rating', [
            'occupancy'   => $occupancy,
            'height'      => $height,
            'sprinklered' => $sprinklered,
            'type'        => $element,
        ]);

        $ratings = [
            'A-1' => ['structural_frame' => 3, 'bearing_walls' => 3, 'floors' => 2, 'roofs' => 1.5],
            'A-2' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1],
            'A-3' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1],
            'B'   => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1],
            'E'   => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1],
            'F-1' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1],
            'F-2' => ['structural_frame' => 1, 'bearing_walls' => 1, 'floors' => 1, 'roofs' => 0.5],
            'H'   => ['structural_frame' => 4, 'bearing_walls' => 4, 'floors' => 3, 'roofs' => 2],
            'I-1' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1],
            'I-2' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1],
            'I-3' => ['structural_frame' => 3, 'bearing_walls' => 3, 'floors' => 2, 'roofs' => 1.5],
            'M'   => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1],
            'R-1' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1],
            'R-2' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1],
            'R-3' => ['structural_frame' => 1, 'bearing_walls' => 1, 'floors' => 1, 'roofs' => 0.5],
            'R-4' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1],
            'S-1' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1],
            'S-2' => ['structural_frame' => 1, 'bearing_walls' => 1, 'floors' => 1, 'roofs' => 0.5],
            'U'   => ['structural_frame' => 1, 'bearing_walls' => 1, 'floors' => 1, 'roofs' => 0.5],
        ];
        $entry = $ratings[$occupancy] ?? $ratings['B'];
        $hours = $entry[$element] ?? 1;
        if ($sprinklered && $hours > 1) {
            $hours = max(1, $hours - 1);
        }
        $el_labels = ['structural_frame' => 'Structural Frame', 'bearing_walls' => 'Bearing Walls', 'floors' => 'Floors', 'roofs' => 'Roofs'];

        wp_send_json_success([
            'raw_result'    => $result,
            'occupancy'     => $occupancy,
            'height'        => $height,
            'element'       => $element,
            'element_label' => $el_labels[$element],
            'rating_hours'  => $hours,
            'sprinklered'   => $sprinklered,
        ]);
    }

    public function ajax_sprinkler_check() {
        $this->verify_nonce();
        $occupancy = sanitize_text_field($_POST['occupancy'] ?? 'B');
        $area      = (float)($_POST['area'] ?? 0);
        $storeys   = (int)($_POST['storeys'] ?? 1);
        $basement  = !empty($_POST['basement']);

        $tools = GHBC_Tools::instance();
        $result = $tools->execute('check_sprinkler_requirement', [
            'occupancy'   => $occupancy,
            'total_area'  => $area,
            'storeys'     => $storeys,
            'has_basement' => $basement,
        ]);

        $required = strpos($result, 'REQUIRED') !== false;

        wp_send_json_success([
            'raw_result' => $result,
            'required'   => $required,
            'occupancy'  => $occupancy,
        ]);
    }

    public function ajax_code_search() {
        $this->verify_nonce();
        $query = sanitize_text_field($_POST['query'] ?? '');
        if (empty($query)) {
            wp_send_json_error(['message' => 'Search query is required.']);
        }

        $search = GHBC_Search::instance();
        $results = $search->search($query);

        wp_send_json_success([
            'results' => $results,
            'count'   => count($results),
        ]);
    }

    public function ajax_clause_lookup() {
        $this->verify_nonce();
        $clause = sanitize_text_field($_POST['clause'] ?? '');
        if (empty($clause)) {
            wp_send_json_error(['message' => 'Clause reference is required.']);
        }

        $search = GHBC_Search::instance();
        $result = $search->get_by_clause($clause);

        if (!$result) {
            wp_send_json_error(['message' => "Clause $clause not found."]);
        }

        wp_send_json_success([
            'clause'  => $result['clause'],
            'part'    => $result['part'],
            'title'   => $result['title'],
            'content' => $result['content'],
        ]);
    }

    public function ajax_test_connection() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ghbc_test_nonce')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $model    = sanitize_text_field($_POST['model'] ?? '');
        $key      = sanitize_text_field($_POST['key'] ?? '');
        $base_url = untrailingslashit(sanitize_text_field($_POST['base_url'] ?? ''));

        if ($provider === 'ollama') {
            $url = ($base_url ?: 'http://localhost:11434') . '/v1/chat/completions';
            $body = [
                'model'    => $model ?: 'llama3.2',
                'messages' => [['role' => 'user', 'content' => 'Reply with just the word: ok']],
                'stream'   => false,
            ];
            $headers = ['content-type' => 'application/json'];
            $response = wp_remote_post($url, [
                'headers' => $headers,
                'body'    => wp_json_encode($body),
                'timeout' => 15,
            ]);
        } else {
            $llm = GHBC_LLM::instance();
            $llm->configure($provider, $key, $model, $base_url);
            $result = $llm->complete(
                'You are a test assistant.',
                [['role' => 'user', 'content' => 'Reply with just the word: ok']]
            );
            if ($result['success']) {
                wp_send_json_success(['message' => 'Connected. Response: ' . substr($result['text'], 0, 100)]);
            }
            wp_send_json_error(['message' => $result['text']]);
        }

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Connection failed: ' . $response->get_error_message()]);
        }
        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($code >= 400 || !empty($data['error'])) {
            $msg = $data['error']['message'] ?? wp_remote_retrieve_body($response);
            wp_send_json_error(['message' => "HTTP $code: " . substr($msg, 0, 150)]);
        }
        wp_send_json_success(['message' => 'Connected successfully!']);
    }

    private function verify_nonce() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ghbc_nonce')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
    }

    private function extract_json($text) {
        preg_match('/\{.*\}/s', $text, $matches);
        if (empty($matches)) return null;
        $data = json_decode($matches[0], true);
        return $data && isset($data['question'], $data['options']) ? $data : null;
    }
}
