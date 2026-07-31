<?php
defined('ABSPATH') || exit;

class GHBC_LLM {
    private static $instance = null;
    private $provider;
    private $api_key;
    private $model;
    private $base_url;

    public static function instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        $this->provider = get_option('ghbc_llm_provider', 'claude');
        $this->api_key  = get_option('ghbc_llm_key', '');
        $this->model    = get_option('ghbc_llm_model', 'claude-sonnet-4-20250514');
        $this->base_url = get_option('ghbc_llm_base_url', 'http://localhost:11434');
    }

    public function is_ready() {
        if (in_array($this->provider, ['ollama', 'lmstudio'])) {
            return !empty($this->model);
        }
        return !empty($this->api_key);
    }

    public function complete($system_prompt, $messages, $tools = []) {
        if (!$this->is_ready()) {
            $is_local = in_array($this->provider, ['ollama', 'lmstudio']);
            return $this->error($is_local ? 'LLM not configured. Set a model name in GhBC Settings.' : 'LLM not configured. Set API key in GhBC Settings.');
        }
        return match ($this->provider) {
            'openai'   => $this->call_openai($system_prompt, $messages, $tools),
            'gemini'   => $this->call_gemini($system_prompt, $messages, $tools),
            'ollama',
            'lmstudio' => $this->call_local_openai($system_prompt, $messages, $tools),
            default    => $this->call_claude($system_prompt, $messages, $tools),
        };
    }

    private function call_local_openai($system, $messages, $tools) {
        $body = [
            'model'       => $this->model,
            'messages'    => array_merge(
                [['role' => 'system', 'content' => $system]],
                $messages
            ),
            'max_tokens'  => 4096,
            'stream'      => false,
        ];
        if (!empty($tools)) {
            $body['tools'] = $tools;
        }

        $url = trailingslashit($this->base_url) . 'v1/chat/completions';
        $headers = ['content-type' => 'application/json'];
        if (!empty($this->api_key)) {
            $headers['authorization'] = 'Bearer ' . $this->api_key;
        }

        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => wp_json_encode($body),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            return $this->error('Local API error: ' . $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $choice = $data['choices'][0] ?? [];
        $msg = $choice['message'] ?? [];

        return [
            'success'    => true,
            'text'       => $msg['content'] ?? '',
            'tool_calls' => $msg['tool_calls'] ?? [],
            'usage'      => $data['usage'] ?? [],
            'raw'        => $data,
        ];
    }

    private function call_claude($system, $messages, $tools) {
        $body = [
            'model'      => $this->model,
            'system'     => $system,
            'messages'   => $messages,
            'max_tokens' => 4096,
        ];
        if (!empty($tools)) {
            $body['tools'] = $tools;
        }

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key'         => $this->api_key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'body'    => wp_json_encode($body),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            return $this->error('Claude API error: ' . $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!empty($data['error'])) {
            return $this->error('Claude API: ' . ($data['error']['message'] ?? 'Unknown error'));
        }
        if (empty($data['content'])) {
            return $this->error('Claude returned an empty response.');
        }

        $text = '';
        $tool_calls = [];
        foreach ($data['content'] as $block) {
            if ($block['type'] === 'text') {
                $text .= $block['text'];
            }
            if ($block['type'] === 'tool_use') {
                $tool_calls[] = $block;
            }
        }

        return [
            'success'    => true,
            'text'       => $text,
            'tool_calls' => $tool_calls,
            'usage'      => $data['usage'] ?? [],
            'raw'        => $data,
        ];
    }

    private function call_openai($system, $messages, $tools) {
        $body = [
            'model'       => $this->model,
            'messages'    => array_merge(
                [['role' => 'system', 'content' => $system]],
                $messages
            ),
            'max_tokens'  => 4096,
        ];
        if (!empty($tools)) {
            $body['tools'] = $tools;
        }

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'authorization' => 'Bearer ' . $this->api_key,
                'content-type'  => 'application/json',
            ],
            'body'    => wp_json_encode($body),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            return $this->error('OpenAI API error: ' . $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $choice = $data['choices'][0] ?? [];
        $msg = $choice['message'] ?? [];

        return [
            'success'    => true,
            'text'       => $msg['content'] ?? '',
            'tool_calls' => $msg['tool_calls'] ?? [],
            'usage'      => $data['usage'] ?? [],
            'raw'        => $data,
        ];
    }

    private function call_gemini($system, $messages, $tools) {
        $contents = [];
        foreach ($messages as $msg) {
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            $text = is_string($msg['content']) ? $msg['content'] : '';
            if (!empty($text)) {
                $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
            }
        }

        $body = [
            'system_instruction' => ['parts' => [['text' => $system]]],
            'contents'           => $contents,
            'generationConfig'   => ['maxOutputTokens' => 4096],
        ];

        if (!empty($tools)) {
            $declarations = [];
            foreach ($tools as $t) {
                $declarations[] = [
                    'name'        => $t['name'],
                    'description' => $t['description'] ?? '',
                    'parameters'  => $t['input_schema'] ?? $t['parameters'] ?? [],
                ];
            }
            $body['tools'] = [['function_declarations' => $declarations]];
        }

        $response = wp_remote_post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->api_key}",
            [
                'headers' => ['content-type' => 'application/json'],
                'body'    => wp_json_encode($body),
                'timeout' => 60,
            ]
        );

        if (is_wp_error($response)) {
            return $this->error('Gemini API error: ' . $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($data['error'])) {
            return $this->error('Gemini API: ' . ($data['error']['message'] ?? 'Unknown error'));
        }

        $candidate = $data['candidates'][0]['content'] ?? [];
        $parts = $candidate['parts'] ?? [];

        $text = '';
        $tool_calls = [];
        foreach ($parts as $part) {
            if (!empty($part['text'])) {
                $text .= $part['text'];
            }
            if (!empty($part['functionCall'])) {
                $fc = $part['functionCall'];
                $tool_calls[] = [
                    'id'          => $fc['name'] . '_' . uniqid(),
                    'type'        => 'function',
                    'name'        => $fc['name'],
                    'input'       => $fc['args'] ?? '{}',
                    'function'    => [
                        'name'      => $fc['name'],
                        'arguments' => wp_json_encode($fc['args'] ?? (object)[]),
                    ],
                ];
            }
        }

        return [
            'success'    => true,
            'text'       => $text,
            'tool_calls' => $tool_calls,
            'usage'      => [],
            'raw'        => $data,
        ];
    }

    public function tool_call_to_message($tool_call, $result) {
        if (in_array($this->provider, ['openai', 'ollama', 'lmstudio'])) {
            return [
                'role'       => 'tool',
                'tool_call_id' => $tool_call['id'],
                'content'    => is_string($result) ? $result : wp_json_encode($result),
            ];
        }
        if ($this->provider === 'gemini') {
            $name = $tool_call['name'] ?? $tool_call['function']['name'] ?? '';
            $content = is_string($result) ? $result : wp_json_encode($result);
            return [
                'role'    => 'function',
                'parts'   => [
                    ['functionResponse' => ['name' => $name, 'response' => ['content' => $content]]],
                ],
            ];
        }
        return [
            'role'    => 'user',
            'content' => [
                [
                    'type'      => 'tool_result',
                    'tool_use_id' => $tool_call['id'],
                    'content'   => is_string($result) ? $result : wp_json_encode($result),
                ],
            ],
        ];
    }

    private function error($msg) {
        return ['success' => false, 'text' => $msg, 'tool_calls' => [], 'usage' => []];
    }
}
