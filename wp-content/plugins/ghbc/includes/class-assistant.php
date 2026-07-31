<?php
defined('ABSPATH') || exit;

class GHBC_Assistant {
    private static $instance = null;
    private $llm;
    private $tools;
    private $max_tool_rounds = 10;

    public static function instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        $this->llm   = GHBC_LLM::instance();
        $this->tools = GHBC_Tools::instance();
    }

    public function chat($query, $history = [], $product = 'compliance') {
        if (!$this->llm->is_ready()) {
            return [
                'success' => false,
                'text'    => 'LLM not configured. Admin must set API key in GhBC Settings.',
            ];
        }

        $system = $this->get_system_prompt($product);
        $tool_schemas = $this->tools->get_tool_schemas();

        $messages = [];
        foreach (array_slice($history, -10) as $msg) {
            $messages[] = [
                'role'    => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['content'],
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $query];

        for ($round = 0; $round < $this->max_tool_rounds; $round++) {
            $result = $this->llm->complete($system, $messages, $tool_schemas);

            if (!$result['success']) {
                return $result;
            }

            $messages[] = [
                'role'    => 'assistant',
                'content' => $result['text'] ?: '',
            ];

            if (empty($result['tool_calls'])) {
                $this->log_query($query, $result['text'], $result['usage']);
                return [
                    'success' => true,
                    'text'    => $result['text'],
                    'usage'   => $result['usage'],
                ];
            }

            foreach ($result['tool_calls'] as $tc) {
                $tool_name  = $tc['name'] ?? $tc['function']['name'] ?? '';
                $tool_args  = json_decode($tc['input'] ?? $tc['function']['arguments'] ?? '{}', true) ?: [];
                $tool_result = $this->tools->execute($tool_name, $tool_args);
                $messages[] = $this->llm->tool_call_to_message($tc, $tool_result);
            }
        }

        $final = $this->llm->complete($system, $messages);
        $this->log_query($query, $final['text'] ?? '', $final['usage'] ?? []);
        return [
            'success' => true,
            'text'    => ($final['text'] ?? '') ?: 'Completed analysis. See results above.',
            'usage'   => $final['usage'] ?? [],
        ];
    }

    private function get_system_prompt($product) {
        $base = "You are the Ghana Building Code GS 1207:2018 AI Assistant. "
              . "You answer questions ONLY based on the Ghana Building Code provided in your context. "
              . "Always cite the specific Part, Section, and Clause number. "
              . "If you don't know or the code doesn't cover it, say so. "
              . "Use the tools to search the code and perform calculations before answering. "
              . "Format responses with clear citations: Part X, Section X.X.X, Clause X.X.X. "
              . "Include page references when available. "
              . "End with: 'This is advisory. Always verify with a qualified professional.'";

        $product_prompts = [
            'compliance' => $base,
            'exam' => "You are the Ghana Building Code Exam Preparation Assistant. "
                    . "Generate multiple-choice questions (4 options, one correct) from the code. "
                    . "Scoring: +1 for correct, -0.25 for incorrect. "
                    . "After answers, explain why the correct answer is right and cite the clause. "
                    . "Track the user's score and identify weak areas.",
            'small_building' => "You are the Ghana Building Code Small Buildings Assistant (Part 35). "
                             . "Guide users through the Part 35 qualification check: "
                             . "max 2 storeys, max 200m\u00b2, max 6m, simple occupancy. "
                             . "If qualified, run the simplified checklist. If not, escalate to full compliance checker.",
        ];

        return $product_prompts[$product] ?? $base;
    }

    private function log_query($query, $response, $usage) {
        global $wpdb;
        $user_id = get_current_user_id();
        $tokens = 0;
        if (!empty($usage)) {
            $tokens = ($usage['input_tokens'] ?? 0) + ($usage['output_tokens'] ?? 0);
            if ($usage['total_tokens'] ?? 0) $tokens = $usage['total_tokens'];
        }
        $wpdb->insert($wpdb->prefix . 'ghbc_queries', [
            'user_id'     => $user_id ?: 0,
            'query'       => $query,
            'response'    => $response,
            'tokens_used' => $tokens,
            'created_at'  => current_time('mysql'),
        ]);
    }
}
