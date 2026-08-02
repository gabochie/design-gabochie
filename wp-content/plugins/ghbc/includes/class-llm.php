<?php
defined('ABSPATH') || exit;

class GHBC_LLM {
    private static $instance = null;
    private $provider;
    private $api_key;
    private $model;
    private $base_url;
    private $active = null;

    public static function instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        $this->apply_config($this->load_primary());
    }

    private function load_primary() {
        return $this->normalize_cfg([
            'provider' => get_option('ghbc_llm_provider', 'claude'),
            'key'      => get_option('ghbc_llm_key', ''),
            'model'    => get_option('ghbc_llm_model', 'claude-sonnet-4-20250514'),
            'base_url' => get_option('ghbc_llm_base_url', 'http://localhost:11434'),
        ]);
    }

    private function normalize_cfg($f) {
        return [
            'provider' => (string) ($f['provider'] ?? 'claude'),
            'api_key'  => (string) ($f['key'] ?? $f['api_key'] ?? ''),
            'model'    => (string) ($f['model'] ?? ''),
            'base_url' => (string) ($f['base_url'] ?? 'http://localhost:11434'),
        ];
    }

    private function same_cfg($a, $b) {
        return $a['provider'] === $b['provider']
            && $a['model'] === $b['model']
            && $a['api_key'] === $b['api_key'];
    }

    private function in_chain($chain, $cfg) {
        foreach ($chain as $c) {
            if ($this->same_cfg($c, $cfg)) return true;
        }
        return false;
    }

    /**
     * Ordered provider chain: last-known-good provider first (stickiness keeps
     * tool-message formats consistent inside a multi-round tool loop), then the
     * primary provider, then any configured fallbacks.
     */
    private function build_chain() {
        $candidates = [$this->load_primary()];
        $fallbacks = get_option('ghbc_llm_fallbacks', []);
        if (is_array($fallbacks)) {
            foreach ($fallbacks as $f) {
                if (!is_array($f) || empty($f['provider'])) continue;
                $cfg = $this->normalize_cfg($f);
                if (!$this->in_chain($candidates, $cfg)) $candidates[] = $cfg;
            }
        }

        $chain = $candidates;
        if ($this->active && $this->in_chain($candidates, $this->active)) {
            $chain = [$this->active];
            foreach ($candidates as $c) {
                if (!$this->same_cfg($c, $this->active)) $chain[] = $c;
            }
        }
        return $chain;
    }

    private function apply_config($cfg) {
        $this->provider = $cfg['provider'];
        $this->api_key  = $cfg['api_key'];
        $this->model    = $cfg['model'];
        $this->base_url = $cfg['base_url'];
    }

    private function current_config() {
        return [
            'provider' => $this->provider,
            'api_key'  => $this->api_key,
            'model'    => $this->model,
            'base_url' => $this->base_url,
        ];
    }

    private function cfg_ready($cfg) {
        if ($cfg['provider'] === 'ollama') {
            return !empty($cfg['model']);
        }
        return !empty($cfg['api_key']);
    }

    public function is_ready() {
        foreach ($this->build_chain() as $cfg) {
            if ($this->cfg_ready($cfg)) return true;
        }
        return false;
    }

    public function configure($provider = null, $key = null, $model = null, $base_url = null) {
        $this->active = null;
        if ($provider !== null) $this->provider = $provider;
        if ($key !== null)     $this->api_key   = $key;
        if ($model !== null)   $this->model     = $model;
        if ($base_url !== null) $this->base_url = $base_url;
    }

    /**
     * @param bool $ignore_fallback When true only the current provider is tried
     *                              (used by the Test Connection button).
     */
    public function complete($system_prompt, $messages, $tools = [], $ignore_fallback = false) {
        $chain = $ignore_fallback ? [$this->current_config()] : $this->build_chain();
        $errors = [];
        foreach ($chain as $cfg) {
            $this->apply_config($cfg);
            if (!$this->cfg_ready($cfg)) {
                $errors[] = $cfg['provider'] . ': not configured';
                continue;
            }
            $result = $this->call_provider($system_prompt, $messages, $tools);
            if (!empty($result['success']) && (!empty(trim((string) $result['text'])) || !empty($result['tool_calls']))) {
                $this->active = $cfg;
                return $result;
            }
            $errors[] = $cfg['provider'] . ': ' . ($result['text'] ?? 'empty response');
        }
        $this->apply_config($this->load_primary());
        return $this->error('All LLM providers failed. Tried: ' . implode(' | ', $errors));
    }

    private function call_provider($system, $messages, $tools) {
        return match ($this->provider) {
            'openai'     => $this->call_openai($system, $messages, $tools),
            'openrouter' => $this->call_openrouter($system, $messages, $tools),
            'mistral'    => $this->call_mistral($system, $messages, $tools),
            'grok'       => $this->call_grok($system, $messages, $tools),
            'gemini'     => $this->call_gemini($system, $messages, $tools),
            'ollama'     => $this->call_local_openai($system, $messages, $tools),
            default      => $this->call_claude($system, $messages, $tools),
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

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($code >= 400) {
            $msg = $data['error']['message'] ?? wp_remote_retrieve_body($response);
            return $this->error('HTTP ' . $code . ': ' . (is_string($msg) ? $msg : wp_json_encode($msg)));
        }
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

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($code >= 400 || !empty($data['error'])) {
            return $this->error('Claude API: ' . ($data['error']['message'] ?? ('HTTP ' . $code)));
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
        return $this->call_openai_compatible($system, $messages, $tools, 'https://api.openai.com/v1/chat/completions');
    }

    private function call_openrouter($system, $messages, $tools) {
        return $this->call_openai_compatible($system, $messages, $tools, 'https://openrouter.ai/api/v1/chat/completions', [
            'HTTP-Referer' => home_url(),
            'X-Title'      => get_bloginfo('name'),
        ]);
    }

    private function call_mistral($system, $messages, $tools) {
        return $this->call_openai_compatible($system, $messages, $tools, 'https://api.mistral.ai/v1/chat/completions');
    }

    private function call_grok($system, $messages, $tools) {
        return $this->call_openai_compatible($system, $messages, $tools, 'https://api.x.ai/v1/chat/completions');
    }

    private function call_openai_compatible($system, $messages, $tools, $endpoint, $extra_headers = []) {
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

        $headers = [
            'authorization' => 'Bearer ' . $this->api_key,
            'content-type'  => 'application/json',
        ] + $extra_headers;

        $response = wp_remote_post($endpoint, [
            'headers' => $headers,
            'body'    => wp_json_encode($body),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            return $this->error('API error: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($code >= 400) {
            $msg = $data['error']['message'] ?? wp_remote_retrieve_body($response);
            return $this->error('HTTP ' . $code . ': ' . (is_string($msg) ? $msg : wp_json_encode($msg)));
        }
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

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 400 || !empty($data['error'])) {
            return $this->error('Gemini API: ' . ($data['error']['message'] ?? ('HTTP ' . $code)));
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
        if (in_array($this->provider, ['openai', 'openrouter', 'mistral', 'grok', 'ollama'])) {
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
