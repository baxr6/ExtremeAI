<?php
/**
 * ExtremeAI Admin Index Evo Extreme
 *
 * Handles communication with Anthropic's Claude API, including sending messages,
 * managing rate limits, content moderation, and usage statistics.
 *
 * @category Extreme_AI
 * @package  Evo-Extreme
 * @author   Deano Welch <deano.welch@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/baxr6/
 * @since    1.0.0
 * @requires PHP 8.4 or higher
 */


if (!defined('ADMIN_FILE')) {
    die("You can't access this file directly...");
}

if (!defined('_EXTREME_AI_ADMIN_TITLE')) define('_EXTREME_AI_ADMIN_TITLE', 'ExtremeAI Control Panel');
if (!defined('_EXTREME_AI_DASHBOARD')) define('_EXTREME_AI_DASHBOARD', 'Dashboard');
if (!defined('_EXTREME_AI_SETTINGS')) define('_EXTREME_AI_SETTINGS', 'Settings');
if (!defined('_EXTREME_AI_TEST_CONSOLE')) define('_EXTREME_AI_TEST_CONSOLE', 'Test Console');
if (!defined('_EXTREME_AI_SAVE')) define('_EXTREME_AI_SAVE', 'Save');
if (!defined('_EXTREME_AI_SUBMIT')) define('_EXTREME_AI_SUBMIT', 'Submit');
if (!defined('_EXTREME_AI_PROVIDER')) define('_EXTREME_AI_PROVIDER', 'Provider');
if (!defined('_EXTREME_AI_PROMPT')) define('_EXTREME_AI_PROMPT', 'Prompt');
if (!defined('_EXTREME_AI_RESULT')) define('_EXTREME_AI_RESULT', 'Result');
if (!defined('_EXTREME_AI_VERSION')) define('_EXTREME_AI_VERSION', 'Version');
if (!defined('_EXTREME_AI_DEBUG')) define('_EXTREME_AI_DEBUG', 'Debug');
if (!defined('_EXTREME_AI_STATUS')) define('_EXTREME_AI_STATUS', 'Status');
if (!defined('_EXTREME_AI_ACTIVE_PROVIDERS')) define('_EXTREME_AI_ACTIVE_PROVIDERS', 'Active Providers');
if (!defined('_EXTREME_AI_HEALTHCHECK')) define('_EXTREME_AI_HEALTHCHECK', 'Health Check');


?>