<?php
defined('ABSPATH') || exit;

class Gabochie_AI_Engine {
    private static $instance = null;
    private $provider;
    private $api_key;
    private $model;
    private $max_tokens;
    private $log = array();

    const CLAUDE_API = 'https://api.anthropic.com/v1/messages';
    const OPENAI_API = 'https://api.openai.com/v1/chat/completions';

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->provider   = get_option('gs_ai_provider', 'claude');
        $this->api_key    = get_option('gs_ai_api_key', '');
        $this->model      = get_option('gs_ai_model', 'claude-sonnet-4-20250514');
        $this->max_tokens = (int) get_option('gs_ai_max_tokens', 4096);
    }

    public function is_configured() {
        return !empty($this->api_key);
    }

    public function refresh_config() {
        $this->provider   = get_option('gs_ai_provider', 'claude');
        $this->api_key    = get_option('gs_ai_api_key', '');
        $this->model      = get_option('gs_ai_model', 'claude-sonnet-4-20250514');
        $this->max_tokens = (int) get_option('gs_ai_max_tokens', 4096);
    }

    public function chat($messages, $system_prompt = '', $temperature = 0.7) {
        if (!$this->is_configured()) {
            return $this->mock_chat_response($messages);
        }
        if ($this->provider === 'claude') {
            return $this->claude_chat($messages, $system_prompt, $temperature);
        }
        return $this->openai_chat($messages, $system_prompt, $temperature);
    }

    public function generate($prompt, $system_prompt = '', $temperature = 0.7) {
        $messages = array(array('role' => 'user', 'content' => $prompt));
        return $this->chat($messages, $system_prompt, $temperature);
    }

    public function generate_structured($prompt, $system_prompt = '', $temperature = 0.3) {
        $response = $this->generate(
            $prompt . "\n\nRespond with valid JSON only. No markdown. No explanation.",
            $system_prompt,
            $temperature
        );
        $json = $this->extract_json($response);
        if ($json) {
            return json_decode($json, true);
        }
        return null;
    }

    public function test_connection() {
        if (!$this->is_configured()) {
            return array('success' => false, 'message' => 'No API key configured.');
        }
        try {
            $response = $this->chat(
                array(array('role' => 'user', 'content' => 'Respond with exactly: OK')),
                'You are a helpful assistant.',
                0.3
            );
            if (stripos($response, 'OK') !== false) {
                return array('success' => true, 'message' => 'Connection successful. API key is valid.');
            }
            return array('success' => true, 'message' => 'Connected but unexpected response.');
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    private function claude_chat($messages, $system_prompt, $temperature) {
        $body = array(
            'model'       => $this->model,
            'max_tokens'  => $this->max_tokens,
            'temperature' => $temperature,
            'messages'    => $messages,
        );
        if (!empty($system_prompt)) {
            $body['system'] = $system_prompt;
        }

        $args = array(
            'headers' => array(
                'x-api-key'         => $this->api_key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ),
            'body'    => wp_json_encode($body),
            'timeout' => 60,
        );

        $response = wp_remote_post(self::CLAUDE_API, $args);
        return $this->handle_response($response, 'claude');
    }

    private function openai_chat($messages, $system_prompt, $temperature) {
        if (!empty($system_prompt)) {
            array_unshift($messages, array('role' => 'system', 'content' => $system_prompt));
        }
        $body = array(
            'model'       => $this->model,
            'max_tokens'  => $this->max_tokens,
            'temperature' => $temperature,
            'messages'    => $messages,
        );

        $args = array(
            'headers' => array(
                'authorization' => 'Bearer ' . $this->api_key,
                'content-type'  => 'application/json',
            ),
            'body'    => wp_json_encode($body),
            'timeout' => 60,
        );

        $response = wp_remote_post(self::OPENAI_API, $args);
        return $this->handle_response($response, 'openai');
    }

    private function handle_response($response, $provider) {
        if (is_wp_error($response)) {
            $this->log[] = $response->get_error_message();
            return 'I apologize, but there was a connection error. Please try again later.';
        }
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code !== 200) {
            $this->log[] = "API error ($code): $body";
            $error_data = json_decode($body, true);
            $error_msg = isset($error_data['error']['message']) ? $error_data['error']['message'] : "API returned status $code";
            return "I apologize, but there was an error: $error_msg";
        }

        $data = json_decode($body, true);
        if (!$data) {
            $this->log[] = 'Invalid JSON response';
            return 'I apologize, but I received an invalid response. Please try again.';
        }

        if ($provider === 'claude') {
            return $data['content'][0]['text'] ?? '';
        }
        return $data['choices'][0]['message']['content'] ?? '';
    }

    private function extract_json($text) {
        $text = trim($text);
        if (($text[0] ?? '') === '{' && ($text[-1] ?? '') === '}') {
            return $text;
        }
        if (preg_match('/\{[^{}]*\}/s', $text, $m)) {
            return $m[0];
        }
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $text, $m)) {
            $c = trim($m[1]);
            if (($c[0] ?? '') === '{') return $c;
        }
        return null;
    }

    public function get_log() {
        return $this->log;
    }

    public function get_config() {
        return array(
            'provider'   => $this->provider,
            'model'      => $this->model,
            'max_tokens' => $this->max_tokens,
            'configured' => $this->is_configured(),
        );
    }

    private function mock_chat_response($messages) {
        $last = end($messages);
        $msg = strtolower($last['content'] ?? '');
        if (strpos($msg, 'price') !== false || strpos($msg, 'cost') !== false || strpos($msg, 'how much') !== false) {
            return "Our pricing is project-based:\n\n<strong>Concept Design</strong> — from \$800\n<strong>Full Architectural Plans</strong> — from \$2,500\n<strong>Interior Design Package</strong> — from \$1,800\n<strong>Project Management</strong> — 8-12% of build cost\n\nConfigure your AI API key in Settings → Gabochie SaaS → AI Document Engine to enable AI-powered responses.";
        }
        if (strpos($msg, 'service') !== false || strpos($msg, 'offer') !== false) {
            return "We offer: Architectural Design, Interior Design, 3D Visualization, Project Management, Cost Calculator, and Diaspora Support.\n\nConfigure your AI API key for personalized responses.";
        }
        if (strpos($msg, 'hello') !== false || strpos($msg, 'hi') !== false) {
            return "Hello! Welcome to Gabochie Design. I'm your AI assistant. Configure an AI API key in Settings → Gabochie SaaS → AI Document Engine to unlock my full intelligence.";
        }
        return "Thanks for your message! I'm running in demo mode. To unlock AI-powered responses, add your API key in Settings → Gabochie SaaS → AI Document Engine (Anthropic Claude or OpenAI GPT-4o).";
    }
}
