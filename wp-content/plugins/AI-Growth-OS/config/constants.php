<?php

declare(strict_types=1);

/**
 * AI Growth OS — Configuration Constants
 *
 * Define these in your wp-config.php file to configure the plugin.
 *
 * // Required: At least one AI provider API key
 * define('AI_GROWTH_OS_OPENAI_KEY', 'sk-...');
 * define('AI_GROWTH_OS_ANTHROPIC_KEY', 'sk-ant-...');
 * define('AI_GROWTH_OS_GEMINI_KEY', 'AIza...');
 * define('AI_GROWTH_OS_OPENROUTER_KEY', 'sk-or-...');
 *
 * // Optional: Ollama local setup
 * define('AI_GROWTH_OS_OLLAMA_URL', 'http://localhost:11434');
 *
 * // Optional: Plugin behaviour
 * define('AI_GROWTH_OS_DELETE_DATA_ON_UNINSTALL', true);
 * define('AI_GROWTH_OS_AUTONOMOUS_MODE', false);
 * define('AI_GROWTH_OS_LOG_LEVEL', 'debug');  // emergency|alert|critical|error|warning|notice|info|debug
 */

// Default settings
defined('AI_GROWTH_OS_LOG_LEVEL') || define('AI_GROWTH_OS_LOG_LEVEL', 'warning');
defined('AI_GROWTH_OS_AUTONOMOUS_MODE') || define('AI_GROWTH_OS_AUTONOMOUS_MODE', false);
defined('AI_GROWTH_OS_DELETE_DATA_ON_UNINSTALL') || define('AI_GROWTH_OS_DELETE_DATA_ON_UNINSTALL', false);
