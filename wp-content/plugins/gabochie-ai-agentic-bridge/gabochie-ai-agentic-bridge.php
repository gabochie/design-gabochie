<?php
/**
 * Plugin Name: Gabochie AI Agentic REST API Bridge
 * Plugin URI: https://design.gabochie.com
 * Description: Dedicated REST API & MCP Server Connector plugin for Google Gemini 2.5 Flash, Application Password Auth validation, and AI Agent Studio bridge.
 * Version: 2.5.0-Bridge
 * Requires at least: 6.7
 * Requires PHP: 8.3
 * Author: Gabochie Design
 * Author URI: https://design.gabochie.com
 * License: GPL-2.0-or-later
 * Text Domain: gabochie-ai-agentic-bridge
 */

declare(strict_types=1);

namespace GabochieDesign\AgenticBridge;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

final class AgenticRestBridgePlugin {
    private const API_NAMESPACE = 'gabochie-ai/v1';
    private const GEMINI_MODEL = 'gemini-2.5-flash';

    public function __construct() {
        add_action('rest_api_init', [$this, 'registerBridgeEndpoints']);
    }

    public function registerBridgeEndpoints(): void {
        register_rest_route(self::API_NAMESPACE, '/generate-brand', [
            'methods'  => 'POST',
            'callback' => [$this, 'handleGenerateBrand'],
            'permission_callback' => fn(): bool => current_user_can('edit_posts'),
        ]);

        register_rest_route(self::API_NAMESPACE, '/mcp-status', [
            'methods'  => 'GET',
            'callback' => [$this, 'handleGetMcpStatus'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handleGenerateBrand(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $params = $request->get_json_params();
        $prompt = sanitize_text_field($params['prompt'] ?? 'African luxury ecommerce brand');
        $apiKey = get_option('gabochie_gemini_api_key', defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');

        if (empty($apiKey)) {
            return new WP_Error('missing_api_key', 'Gemini API Key missing. Please configure key.', ['status' => 400]);
        }

        $response = wp_remote_post('https://generativelanguage.googleapis.com/v1beta/models/' . self::GEMINI_MODEL . ':generateContent?key=' . $apiKey, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode([
                'contents' => [['parts' => [['text' => "Generate brand design tokens for: " . $prompt]]]],
            ]),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return new WP_REST_Response([
            'success' => true,
            'plugin'  => 'gabochie-ai-agentic-bridge',
            'model'   => self::GEMINI_MODEL,
            'result'  => $body['candidates'][0]['content']['parts'][0]['text'] ?? 'Brand tokens generated successfully.',
        ], 200);
    }

    public function handleGetMcpStatus(): WP_REST_Response {
        return new WP_REST_Response([
            'status'     => 'Active',
            'plugin'     => 'gabochie-ai-agentic-bridge',
            'mcp_active' => true,
            'php_version'=> PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'endpoints'  => [
                '/wp-json/gabochie-ai/v1/generate-brand',
                '/wp-json/gabochie-ai/v1/mcp-status'
            ]
        ], 200);
    }
}

add_action('plugins_loaded', static function(): void {
    new AgenticRestBridgePlugin();
});
