<?php
/**
 * ExtremeAI Admin Module - CLEANED VERSION
 *
 * Complete administration interface for the ExtremeAI system with full integration
 * to ExtremeAI_Core_Clean and provider management.
 *
 * @category Extreme_AI
 * @package  Evo-Extreme
 * @author   Deano Welch <deano.welch@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/baxr6/
 * @since    2.0.0
 * @requires PHP 8.4 or higher
 */

if (!defined('ADMIN_FILE')) {
    die('Illegal File Access');
}

// Define ExtremeAI constants
if (!defined('EXTREME_AI_VERSION')) define('EXTREME_AI_VERSION', '2.0.0');
if (!defined('EXTREME_AI_ASSETS_URL')) {
    // Calculate the assets URL based on the include path
    $base_url = rtrim($_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']), '/');
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    define('EXTREME_AI_ASSETS_URL', $protocol . $base_url . '/includes/extreme_ai');
}

global $prefix, $db, $admin_file, $currentlang;

// Debug: Log ALL requests to this file
error_log("ExtremeAI Admin Module: Request received - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("ExtremeAI Admin Module: GET: " . json_encode($_GET));  
error_log("ExtremeAI Admin Module: POST: " . json_encode($_POST));
error_log("ExtremeAI Admin Module: Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
error_log("ExtremeAI Admin Module: Raw POST data: " . file_get_contents('php://input'));

// Additional debugging for FormData detection
if (!empty($_POST['ajax_action'])) {
    error_log("ExtremeAI Admin Module: ✅ POST ajax_action found: " . $_POST['ajax_action']);
} else {
    error_log("ExtremeAI Admin Module: ❌ No POST ajax_action found");
}

if (!empty($_GET['ajax_action'])) {
    error_log("ExtremeAI Admin Module: ✅ GET ajax_action found: " . $_GET['ajax_action']);
} else {
    error_log("ExtremeAI Admin Module: ❌ No GET ajax_action found");
}

// Check for admin permissions
if (!is_mod_admin()) {
    echo "Access Denied";
    exit;
}

// Get the operation parameter
$op = $_GET['op'] ?? '';

// Include the language file
if (file_exists(NUKE_INCLUDE_DIR . 'extreme_ai/language/lang-' . $currentlang . '.php')) {
    include_once NUKE_INCLUDE_DIR . 'extreme_ai/language/lang-' . $currentlang . '.php';
} else {
    include_once NUKE_INCLUDE_DIR . 'extreme_ai/language/lang-english.php';
}

// Include core classes - CLEANED VERSION
require_once NUKE_INCLUDE_DIR . 'extreme_ai/classes/ExtremeAI_Core_Clean.php';

/** ------------------------------------------------------------------
 * CSRF and Security helpers
 * ------------------------------------------------------------------ */
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Include Evolution template system if not already loaded
if (!function_exists('get_template_part')) {
    require_once NUKE_BASE_DIR . 'includes/templates-evo.php';
}

/**
 * HTML escaping helper function
 */
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Include common ExtremeAI assets (CSS and JS)
 */
function extreme_ai_include_assets() {
    // Main stylesheet
    evo_include_style(
        'extreme_ai-stylesheet',
        'includes/extreme_ai/css/extreme_ai_clean.css',
        time()
    );
    
    // UI components stylesheet
    evo_include_style(
        'extreme_ai-components-stylesheet',
        'includes/extreme_ai/css/extreme-ai-components.css',
        time()
    );
    
    // Core JavaScript library - must load in header before other scripts
    evo_include_script(
        'extreme_ai-core-script',
        'includes/extreme_ai/js/extreme-ai-core.js',
        time(),
        false // Load in header before other scripts
    );
}

/**
 * Custom template loader for ExtremeAI admin templates
 */
function extreme_ai_load_template($template_name, $data = []) {
    // Extract data array to variables
    if (is_array($data)) {
        extract($data, EXTR_SKIP);
    }
    
    $template_path = NUKE_BASE_DIR . 'includes/extreme_ai/templates/admin/' . $template_name . '.php';
    
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo "<div style='color: red; padding: 20px;'>ExtremeAI Template Error: {$template_name} not found</div>";
        error_log("ExtremeAI: Template not found: {$template_path}");
    }
}

function extreme_ai_get_csrf(): string
{
    if (empty($_SESSION['extreme_ai_csrf'])) {
        $_SESSION['extreme_ai_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['extreme_ai_csrf'];
}

function extreme_ai_check_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    $session_token = $_SESSION['extreme_ai_csrf'] ?? '';
    
    error_log("ExtremeAI CSRF: Received token: '$token', Session token: '$session_token'");
    
    $valid = (is_string($token) && hash_equals($session_token, $token));
    error_log("ExtremeAI CSRF: Valid: " . ($valid ? 'yes' : 'no'));
    
    return $valid;
}


function extreme_ai_json_response($data, $success = true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'data' => $data]);
    exit;
}

/** ------------------------------------------------------------------
 * Core Integration Functions
 * ------------------------------------------------------------------ */
function extreme_ai_get_core()
{
    try {
        return ExtremeAI_Core::getInstance();
    } catch (Exception $e) {
        error_log("ExtremeAI Core initialization failed: " . $e->getMessage());
        return null;
    }
}

function extreme_ai_get_system_info(): array
{
    $core = extreme_ai_get_core();
    
    $info = [
        'version' => defined('EXTREME_AI_VERSION') ? EXTREME_AI_VERSION : '2.0.0',
        'debug' => defined('EXTREME_AI_DEBUG') ? (EXTREME_AI_DEBUG ? 'Enabled' : 'Disabled') : 'Disabled',
        'providers' => [],
        'health' => 'Unknown',
        'core_loaded' => $core !== null
    ];

    if ($core) {
        try {
            $info['providers'] = $core->listProviders();
            $info['health'] = $core->healthCheck();
        } catch (Exception $e) {
            $info['health'] = 'Error: ' . $e->getMessage();
        }
    }

    return $info;
}

/** ------------------------------------------------------------------
 * AJAX Handler
 * ------------------------------------------------------------------ */
function extreme_ai_handle_ajax() {
    // Debug logging
    error_log("ExtremeAI AJAX: extreme_ai_handle_ajax function called");
    error_log("ExtremeAI AJAX: POST data: " . json_encode($_POST));
    error_log("ExtremeAI AJAX: GET data: " . json_encode($_GET));
    
    $action = $_POST['ajax_action'] ?? $_GET['ajax_action'] ?? '';
    if (!$action) {
        error_log("ExtremeAI AJAX: No action provided");
        extreme_ai_json_response('No action provided', false);
    }
    
    // Only check CSRF for actions that modify data (POST actions)
    $read_only_actions = ['check_provider_status', 'get_provider_stats', 'get_stats', 'get_analytics', 'get_recent_activity', 'get_dashboard_stats', 'get_recent_errors', 'health_check', 'get_chart_data'];
    $requires_csrf = !in_array($action, $read_only_actions);
    
    if ($requires_csrf && !extreme_ai_check_csrf()) {
        error_log("ExtremeAI AJAX: CSRF validation failed for action: '$action'");
        extreme_ai_json_response('Invalid request - CSRF validation failed', false);
    }
    
    error_log("ExtremeAI AJAX: Processing action: $action [v2]");
    $core = extreme_ai_get_core();

    if (!$core) {
        extreme_ai_json_response('AI Core not available', false);
    }

    switch ($action) {
        case 'get_stats':
            extreme_ai_json_response(extreme_ai_get_usage_stats());
            break;
            
        case 'test_provider':
            $provider = $_POST['provider'] ?? '';
            $prompt = $_POST['prompt'] ?? 'Test message';
            $test_result = extreme_ai_test_provider($provider, $prompt);
            
            // Structure the response for the JavaScript client
            if (isset($test_result['error'])) {
                // Send error response - extreme_ai_json_response will wrap this
                extreme_ai_json_response([
                    'success' => false,
                    'error' => $test_result['error']
                ], false);
            } else {
                // Send success response - extreme_ai_json_response will wrap this  
                extreme_ai_json_response([
                    'success' => true,
                    'data' => $test_result,
                    'tokens_used' => $test_result['tokens_used'] ?? 'N/A'
                ], true);
            }
            break;
            
        case 'get_analytics':
            $timeframe = $_POST['timeframe'] ?? '24h';
            extreme_ai_json_response(extreme_ai_get_analytics($timeframe));
            break;
            
        case 'save_provider_config':
            $provider = $_POST['provider'] ?? '';
            $config_raw = $_POST['config'] ?? '{}';
            
            // Handle both JSON string and array formats
            if (is_string($config_raw)) {
                $config = json_decode($config_raw, true) ?: [];
            } else {
                $config = (array)$config_raw;
            }
            
            extreme_ai_json_response(extreme_ai_save_provider_config($provider, $config));
            break;
            
        case 'get_recent_activity':
            extreme_ai_json_response(extreme_ai_get_recent_activity());
            break;
            
        case 'get_dashboard_stats':
            extreme_ai_json_response(extreme_ai_get_dashboard_stats());
            break;
            
        case 'get_recent_errors':
            extreme_ai_json_response(extreme_ai_get_recent_errors());
            break;
            
        case 'health_check':
            extreme_ai_json_response(extreme_ai_health_check());
            break;
            
        case 'get_chart_data':
            $type = $_GET['type'] ?? 'usage';
            extreme_ai_json_response(extreme_ai_get_chart_data($type));
            break;
            
        case 'run_test':
            // Debug logging
            error_log("ExtremeAI Test Console: Starting test");
            error_log("ExtremeAI Test Console: POST data: " . json_encode($_POST));
            
            // Set JSON header immediately
            header('Content-Type: application/json');
            
            $provider = $_POST['provider'] ?? null;
            $taskType = $_POST['task_type'] ?? 'text_generation';
            $system = $_POST['system'] ?? '';
            $prompt = $_POST['prompt'] ?? '';
            $maxTokens = (int)($_POST['max_tokens'] ?? 1000);
            $temperature = (float)($_POST['temperature'] ?? 0.7);
            $stream = isset($_POST['stream']) ? (bool)$_POST['stream'] : false;
            
            error_log("ExtremeAI Test Console: Parsed - Provider: '$provider', Task: '$taskType', Prompt: '" . substr($prompt, 0, 100) . "'...");
            
            if (empty($prompt)) {
                error_log("ExtremeAI Test Console: Error - Empty prompt");
                echo json_encode([
                    'success' => false,
                    'error' => 'Prompt is required'
                ]);
                exit;
            }
            
            try {
                $test_result = extreme_ai_run_console_test($provider, $taskType, $system, $prompt, $maxTokens, $temperature, $stream);
                error_log("ExtremeAI Test Console: Test result: " . json_encode($test_result));
                
                if (isset($test_result['error'])) {
                    error_log("ExtremeAI Test Console: Test failed with error: " . $test_result['error']);
                    echo json_encode([
                        'success' => false,
                        'error' => $test_result['error']
                    ]);
                } else {
                    error_log("ExtremeAI Test Console: Test succeeded");
                    echo json_encode([
                        'success' => true,
                        'data' => $test_result
                    ]);
                }
            } catch (Exception $e) {
                error_log("ExtremeAI Test Console: Exception caught: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'error' => 'Exception: ' . $e->getMessage()
                ]);
            }
            exit;
            break;
            
        default:
            extreme_ai_json_response('Unknown action', false);
    }
}

/** ------------------------------------------------------------------
 * Data Functions with improved error handling
 * ------------------------------------------------------------------ */
function extreme_ai_get_usage_stats() {
    global $prefix, $db;
    
    $stats = [
        'requests_today' => 0,
        'avg_response_time' => 0,
        'costs_today' => 0,
        'content_generated' => 0,
        'error_rate' => 0
    ];
    
    try {
        // Get today's usage statistics with proper error handling
        $result = $db->sql_query("SELECT 
            COUNT(*) as requests_today,
            AVG(response_time) as avg_response_time,
            SUM(cost) as costs_today,
            COUNT(CASE WHEN task_type = 'text_generation' THEN 1 END) as content_generated
            FROM {$prefix}_extreme_ai_usage 
            WHERE DATE(created) = CURDATE()");
        
        if ($row = $db->sql_fetchrow($result)) {
            $stats = array_merge($stats, [
                'requests_today' => (int)$row['requests_today'],
                'avg_response_time' => round((float)$row['avg_response_time'], 2),
                'costs_today' => round((float)$row['costs_today'], 2),
                'content_generated' => (int)$row['content_generated']
            ]);
        }
        $db->sql_freeresult($result);
        
        // Get error rate with proper resource cleanup
        $error_result = $db->sql_query("SELECT COUNT(*) as errors 
            FROM {$prefix}_extreme_ai_errors 
            WHERE DATE(created) = CURDATE()");
        
        if ($error_row = $db->sql_fetchrow($error_result)) {
            $total_requests = $stats['requests_today'];
            $errors = (int)$error_row['errors'];
            $stats['error_rate'] = $total_requests > 0 ? round(($errors / $total_requests) * 100, 1) : 0;
        }
        $db->sql_freeresult($error_result);
        
    } catch (Exception $e) {
        error_log("Error getting usage stats: " . $e->getMessage());
    }
    
    return $stats;
}

function extreme_ai_get_analytics($timeframe = '24h') {
    $core = extreme_ai_get_core();
    if (!$core) {
        return ['error' => 'Core not available'];
    }
    
    try {
        return $core->getAdvancedAnalytics($timeframe);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function extreme_ai_test_provider($provider, $prompt) {
    $core = extreme_ai_get_core();
    if (!$core) {
        return ['error' => 'Core not available'];
    }
    
    try {
        $options = [];
        if (!empty($provider)) {
            $options['provider'] = $provider;
        }
        
        $result = $core->executeTask(
            ExtremeAI_Core::TASK_TEXT_GENERATION,
            ['prompt' => $prompt],
            $options
        );
        
        return $result;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function extreme_ai_run_console_test($provider, $taskType, $system, $prompt, $maxTokens, $temperature, $stream) {
    error_log("ExtremeAI Console Test: Starting function with provider='$provider', task='$taskType'");
    
    $core = extreme_ai_get_core();
    if (!$core) {
        error_log("ExtremeAI Console Test: AI Core not available");
        return ['error' => 'AI Core not available'];
    }
    
    error_log("ExtremeAI Console Test: AI Core loaded successfully");
    
    try {
        // Prepare input for the AI task
        $input = ['prompt' => $prompt];
        
        // Add system prompt if provided
        if (!empty($system)) {
            $input['system'] = $system;
            error_log("ExtremeAI Console Test: Added system prompt");
        }
        
        // Prepare options
        $options = [
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'stream' => $stream
        ];
        
        // Add provider if specified
        if (!empty($provider)) {
            $options['provider'] = $provider;
            error_log("ExtremeAI Console Test: Using specific provider: $provider");
        } else {
            error_log("ExtremeAI Console Test: Using auto-selected provider");
        }
        
        error_log("ExtremeAI Console Test: Calling core->executeTask with input: " . json_encode($input));
        error_log("ExtremeAI Console Test: Options: " . json_encode($options));
        
        // Execute the AI task
        $result = $core->executeTask($taskType, $input, $options);
        
        error_log("ExtremeAI Console Test: executeTask returned: " . json_encode($result));
        
        // Check if result contains an error
        if (isset($result['error'])) {
            error_log("ExtremeAI Console Test: Result contains error: " . $result['error']);
            return ['error' => $result['error']];
        }
        
        // Return successful result
        $final_result = [
            'success' => true,
            'content' => $result['content'] ?? $result,
            'provider_used' => $result['provider_used'] ?? $provider ?? 'auto-selected',
            'tokens_used' => $result['tokens_used'] ?? 'N/A',
            'cost' => $result['cost'] ?? 'N/A',
            'task_type' => $taskType,
            'response_time' => $result['response_time'] ?? null
        ];
        
        error_log("ExtremeAI Console Test: Returning final result: " . json_encode($final_result));
        return $final_result;
        
    } catch (Exception $e) {
        error_log("ExtremeAI Console Test: Exception in function: " . $e->getMessage());
        error_log("ExtremeAI Console Test: Exception trace: " . $e->getTraceAsString());
        return ['error' => $e->getMessage()];
    }
}

function extreme_ai_get_all_providers() {
    global $prefix, $db;
    
    $providers = [];
    
    try {
        // Get all provider configurations from the providers table
        $sql = "SELECT * FROM {$prefix}_extreme_ai_providers ORDER BY priority ASC, name ASC";
        error_log("ExtremeAI: Loading providers with SQL: $sql");
        
        $result = $db->sql_query($sql);
        
        $row_count = 0;
        while ($row = $db->sql_fetchrow($result)) {
            $provider_key = $row['name'];
            
            // Map database fields to expected format
            $providers[$provider_key] = [
                'name' => $row['display_name'] ?: $row['name'],
                'display_name' => $row['display_name'] ?: ucfirst($row['name']),
                'api_key' => $row['api_key'],
                'api_endpoint' => $row['api_endpoint'],
                'model' => $row['model'],
                'enabled' => (bool)$row['enabled'],
                'priority' => (int)$row['priority'],
                'rate_limit' => (int)$row['rate_limit'],
                'timeout' => (int)$row['timeout'],
                'settings' => $row['settings'] ? json_decode($row['settings'], true) : [],
                'last_used' => $row['last_used'],
                'total_requests' => (int)$row['total_requests'],
                'total_cost' => (float)$row['total_cost'],
                'success_rate' => (float)$row['success_rate'],
                'avg_response_time' => (float)$row['avg_response_time'],
                'created' => $row['created'],
                'updated' => $row['updated']
            ];
            
            $row_count++;
            error_log("ExtremeAI: Loaded provider - Name: {$provider_key}, Enabled: " . ($row['enabled'] ? 'yes' : 'no') . ", Has API Key: " . (!empty($row['api_key']) ? 'yes' : 'no'));
        }
        
        error_log("ExtremeAI: Total providers loaded: $row_count");
        
        // Add default provider definitions for any missing providers
        $default_providers = [
            'anthropic' => [
                'name' => 'Anthropic Claude',
                'display_name' => 'Anthropic Claude',
                'icon' => 'fas fa-robot',
                'default_endpoint' => 'https://api.anthropic.com/v1/messages',
                'default_model' => 'claude-3-sonnet-20240229',
                'enabled' => false
            ],
            'openai' => [
                'name' => 'OpenAI GPT',
                'display_name' => 'OpenAI GPT',
                'icon' => 'fas fa-brain',
                'default_endpoint' => 'https://api.openai.com/v1/chat/completions',
                'default_model' => 'gpt-3.5-turbo',
                'enabled' => false
            ],
            'google' => [
                'name' => 'Google Gemini',
                'display_name' => 'Google Gemini',
                'icon' => 'fab fa-google',
                'default_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
                'default_model' => 'gemini-pro',
                'enabled' => false
            ],
            'ollama' => [
                'name' => 'Ollama (Local)',
                'display_name' => 'Ollama (Local)',
                'icon' => 'fas fa-server',
                'default_endpoint' => 'http://localhost:11434/api/generate',
                'default_model' => 'llama2',
                'enabled' => false
            ]
        ];
        
        // Add any missing default providers
        foreach ($default_providers as $key => $defaults) {
            if (!isset($providers[$key])) {
                $providers[$key] = $defaults;
            } else {
                // Add icon and default values to existing providers
                $providers[$key]['icon'] = $defaults['icon'];
                if (empty($providers[$key]['api_endpoint'])) {
                    $providers[$key]['default_endpoint'] = $defaults['default_endpoint'];
                }
                if (empty($providers[$key]['model'])) {
                    $providers[$key]['default_model'] = $defaults['default_model'];
                }
            }
        }
        
        error_log("ExtremeAI: Final providers array: " . json_encode($providers));
        
    } catch (Exception $e) {
        error_log("ExtremeAI: Failed to load providers: " . $e->getMessage());
        $providers = [];
    }
    
    return $providers;
}

function extreme_ai_save_provider_config($provider, $config) {
    global $prefix, $db;
    
    // Debug logging
    error_log("ExtremeAI: Saving provider config - Provider: $provider, Config: " . json_encode($config));
    
    try {
        $config_json = json_encode($config);
        
        $now = date('Y-m-d H:i:s');
        $query = "INSERT INTO {$prefix}_extreme_ai_providers 
            (name, display_name, api_key, api_endpoint, model, enabled, settings, updated) 
            VALUES (
                '" . $db->sql_escapestring($provider) . "',
                '" . $db->sql_escapestring($config['display_name'] ?? $provider) . "',
                '" . $db->sql_escapestring($config['api_key'] ?? '') . "',
                '" . $db->sql_escapestring($config['api_endpoint'] ?? '') . "',
                '" . $db->sql_escapestring($config['model'] ?? '') . "',
                " . (int)($config['enabled'] ?? 0) . ",
                '" . $db->sql_escapestring($config_json) . "',
                '" . $now . "'
            ) ON DUPLICATE KEY UPDATE
                display_name = '" . $db->sql_escapestring($config['display_name'] ?? $provider) . "',
                api_key = '" . $db->sql_escapestring($config['api_key'] ?? '') . "',
                api_endpoint = '" . $db->sql_escapestring($config['api_endpoint'] ?? '') . "',
                model = '" . $db->sql_escapestring($config['model'] ?? '') . "',
                enabled = " . (int)($config['enabled'] ?? 0) . ",
                settings = '" . $db->sql_escapestring($config_json) . "',
                updated = '" . $now . "'";
        
        $db->sql_query($query);
        return ['message' => 'Configuration saved successfully'];
        
    } catch (Exception $e) {
        return ['error' => 'Failed to save configuration: ' . $e->getMessage()];
    }
}

function extreme_ai_get_recent_activity() {
    global $prefix, $db;
    
    try {
        $result = $db->sql_query("SELECT * FROM {$prefix}_extreme_ai_logs 
                                  WHERE level IN ('info', 'warning', 'error') 
                                  ORDER BY timestamp DESC 
                                  LIMIT 10");
        
        $activities = [];
        while ($row = $db->sql_fetchrow($result)) {
            $activities[] = [
                'timestamp' => $row['timestamp'],
                'level' => $row['level'],
                'message' => $row['message'],
                'provider' => $row['provider'] ?? 'system'
            ];
        }
        
        return ['success' => true, 'data' => $activities];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function extreme_ai_get_dashboard_stats() {
    global $prefix, $db;
    
    try {
        // Get total requests today
        $today_requests = 0;
        $result = $db->sql_query("SELECT COUNT(*) as count FROM {$prefix}_extreme_ai_logs 
                                  WHERE DATE(timestamp) = CURDATE() AND level = 'info'");
        if ($row = $db->sql_fetchrow($result)) {
            $today_requests = (int)$row['count'];
        }
        
        // Get total errors today
        $today_errors = 0;
        $result = $db->sql_query("SELECT COUNT(*) as count FROM {$prefix}_extreme_ai_logs 
                                  WHERE DATE(timestamp) = CURDATE() AND level = 'error'");
        if ($row = $db->sql_fetchrow($result)) {
            $today_errors = (int)$row['count'];
        }
        
        // Get active providers count
        $active_providers = 0;
        $result = $db->sql_query("SELECT COUNT(*) as count FROM {$prefix}_extreme_ai_providers 
                                  WHERE enabled = 1");
        if ($row = $db->sql_fetchrow($result)) {
            $active_providers = (int)$row['count'];
        }
        
        // System status
        $system_status = 'healthy';
        if ($today_errors > 10) {
            $system_status = 'warning';
        }
        if ($today_errors > 50) {
            $system_status = 'critical';
        }
        
        return [
            'success' => true,
            'data' => [
                'requests_today' => $today_requests,
                'errors_today' => $today_errors,
                'active_providers' => $active_providers,
                'system_status' => $system_status,
                'uptime' => '99.9%'
            ]
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function extreme_ai_get_recent_errors() {
    global $prefix, $db;
    
    try {
        $result = $db->sql_query("SELECT * FROM {$prefix}_extreme_ai_logs 
                                  WHERE level = 'error' 
                                  ORDER BY timestamp DESC 
                                  LIMIT 5");
        
        $errors = [];
        while ($row = $db->sql_fetchrow($result)) {
            $errors[] = [
                'timestamp' => $row['timestamp'],
                'message' => $row['message'],
                'provider' => $row['provider'] ?? 'system',
                'details' => $row['details'] ?? ''
            ];
        }
        
        return ['success' => true, 'data' => $errors];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function extreme_ai_health_check() {
    global $prefix, $db;
    
    try {
        $checks = [];
        
        // Database connectivity
        $checks['database'] = [
            'status' => 'healthy',
            'message' => 'Database connection active'
        ];
        
        // Check if core tables exist
        $tables = [
            "{$prefix}_extreme_ai_config",
            "{$prefix}_extreme_ai_providers", 
            "{$prefix}_extreme_ai_logs"
        ];
        
        foreach ($tables as $table) {
            $result = $db->sql_query("SHOW TABLES LIKE '$table'");
            if ($db->sql_numrows($result) === 0) {
                $checks['database'] = [
                    'status' => 'error',
                    'message' => "Missing table: $table"
                ];
                break;
            }
        }
        
        // Check active providers
        $result = $db->sql_query("SELECT COUNT(*) as count FROM {$prefix}_extreme_ai_providers WHERE enabled = 1");
        $active_providers = 0;
        if ($row = $db->sql_fetchrow($result)) {
            $active_providers = (int)$row['count'];
        }
        
        $checks['providers'] = [
            'status' => $active_providers > 0 ? 'healthy' : 'warning',
            'message' => "$active_providers active providers"
        ];
        
        // Check recent errors
        $result = $db->sql_query("SELECT COUNT(*) as count FROM {$prefix}_extreme_ai_logs 
                                  WHERE level = 'error' AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $recent_errors = 0;
        if ($row = $db->sql_fetchrow($result)) {
            $recent_errors = (int)$row['count'];
        }
        
        $checks['errors'] = [
            'status' => $recent_errors < 5 ? 'healthy' : ($recent_errors < 20 ? 'warning' : 'error'),
            'message' => "$recent_errors errors in the last hour"
        ];
        
        return ['success' => true, 'data' => $checks];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function extreme_ai_get_chart_data($type = 'usage') {
    global $prefix, $db;
    
    try {
        $data = [];
        $labels = [];
        
        if ($type === 'usage') {
            // Get usage data for the last 7 days
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('M j', strtotime($date));
                
                $result = $db->sql_query("SELECT COUNT(*) as count FROM {$prefix}_extreme_ai_logs 
                                          WHERE DATE(timestamp) = '$date' AND level = 'info'");
                $count = 0;
                if ($row = $db->sql_fetchrow($result)) {
                    $count = (int)$row['count'];
                }
                $data[] = $count;
            }
        } elseif ($type === 'errors') {
            // Get error data for the last 7 days
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('M j', strtotime($date));
                
                $result = $db->sql_query("SELECT COUNT(*) as count FROM {$prefix}_extreme_ai_logs 
                                          WHERE DATE(timestamp) = '$date' AND level = 'error'");
                $count = 0;
                if ($row = $db->sql_fetchrow($result)) {
                    $count = (int)$row['count'];
                }
                $data[] = $count;
            }
        }
        
        return [
            'success' => true,
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => ucfirst($type),
                        'data' => $data,
                        'borderColor' => $type === 'usage' ? '#4f46e5' : '#ef4444',
                        'backgroundColor' => $type === 'usage' ? 'rgba(79, 70, 229, 0.1)' : 'rgba(239, 68, 68, 0.1)',
                        'tension' => 0.1
                    ]
                ]
            ]
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function extreme_ai_get_system_settings() {
    global $prefix, $db;
    
    $defaults = [
        'extreme_ai_version' => '2.0.0',
        'extreme_ai_debug' => false,
        'extreme_ai_cache_ttl' => 3600,
        'extreme_ai_max_tokens' => 4096,
        'extreme_ai_rate_limit' => 1000,
        'extreme_ai_default_timeout' => 30,
        'extreme_ai_max_concurrent_requests' => 10,
        'extreme_ai_cache_enabled' => true,
        'extreme_ai_auto_cleanup_days' => 30,
        'extreme_ai_log_level' => 'error'
    ];
    
    $settings = $defaults;
    
    try {
        // Use existing nuke_extreme_ai_config table structure
        $result = $db->sql_query("SELECT `key`, value FROM {$prefix}_extreme_ai_config 
                                  WHERE provider = 'system'
                                  ORDER BY `key`");
        
        while ($row = $db->sql_fetchrow($result)) {
            $key = $row['key'];
            $value = $row['value'];
            
            // Auto-detect and cast types based on default values
            if (isset($defaults[$key])) {
                if (is_bool($defaults[$key])) {
                    $value = (bool)$value;
                } elseif (is_int($defaults[$key])) {
                    $value = (int)$value;
                } elseif (is_float($defaults[$key])) {
                    $value = (float)$value;
                }
            }
            
            $settings[$key] = $value;
        }
        $db->sql_freeresult($result);
        
    } catch (Exception $e) {
        error_log("Error loading system settings: " . $e->getMessage());
    }
    
    return $settings;
}

function extreme_ai_save_system_settings($settings) {
    global $prefix, $db;
    
    try {
        foreach ($settings as $key => $value) {
            // Convert boolean to string for storage
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif (is_array($value)) {
                $value = json_encode($value);
            }
            
            // Use existing nuke_extreme_ai_config table structure
            $query = "INSERT INTO {$prefix}_extreme_ai_config 
                      (provider, `key`, value, created, updated) 
                      VALUES (
                          'system',
                          '" . $db->sql_escapestring($key) . "',
                          '" . $db->sql_escapestring($value) . "',
                          NOW(),
                          NOW()
                      ) ON DUPLICATE KEY UPDATE
                          value = '" . $db->sql_escapestring($value) . "',
                          updated = NOW()";
            
            $db->sql_query($query);
        }
        
        return ['message' => 'Settings saved successfully'];
        
    } catch (Exception $e) {
        return ['error' => 'Failed to save settings: ' . $e->getMessage()];
    }
}

/** ------------------------------------------------------------------
 * UI Functions (unchanged from original but with better error handling)
 * ------------------------------------------------------------------ */
function extreme_ai_admin_menu() {
    global $admin_file;
    
    OpenTable();
    echo '<div class="extreme-ai-nav" style="text-align:center; margin-bottom: 20px;">';
    
    $items = [
        'extremeai_dashboard' => 'Dashboard',
        'extremeai_providers' => 'AI Providers',
        'extremeai_settings' => 'Settings',
        'extremeai_test_console' => 'Test Console',
        'extremeai_analytics' => 'Analytics',
        'extremeai_workflows' => 'Workflows',
        'extremeai_agents' => 'AI Agents'
    ];
    
    $current_op = $_GET['op'] ?? '';
    
    $parts = [];
    foreach ($items as $op_value => $label) {
        $active = ($current_op === $op_value) ? ' class="active"' : '';
        $parts[] = '<a href="' . e("$admin_file.php?op=$op_value") . '"' . $active . '>' . e($label) . '</a>';
    }
    
    echo '<div class="nav-buttons">' . implode(' | ', $parts) . '</div>';
    echo '</div>';
    CloseTable();
}

function extreme_ai_dashboard() {
    global $admin_file;
    
    // Include stylesheets and scripts
    extreme_ai_include_assets();
    
    // Load dashboard specific JavaScript - after core
    evo_include_script(
        'extreme_ai-dashboard-script',
        'includes/extreme_ai/js/dashboard.js',
        time(),
        true // Load in footer after core
    );
    
    include_once NUKE_BASE_DIR . 'header.php';
    title('ExtremeAI Administration - Dashboard');
    
    $info = extreme_ai_get_system_info();
    $stats = extreme_ai_get_usage_stats();
    
    OpenTable();
    
    // Include styles using template system
    extreme_ai_load_template('styles');
    
    // Render dashboard template using template system
    extreme_ai_load_template('dashboard', [
        'system_info' => $info,
        'usage_stats' => $stats,
        'admin_file' => $admin_file,
        'csrf_token' => extreme_ai_get_csrf()
    ]);
    
    CloseTable();
    include_once NUKE_BASE_DIR . 'footer.php';
}

function extreme_ai_providers_page() {
    global $admin_file, $prefix, $db;
    
    // Include stylesheets and scripts
    extreme_ai_include_assets();
    
    // Load providers specific JavaScript - after core
    evo_include_script(
        'extreme_ai-providers-script',
        'includes/extreme_ai/js/providers.js',
        time(),
        true // Load in footer after core
    );
    
    include_once NUKE_BASE_DIR . 'header.php';
    title('ExtremeAI Administration - AI Providers');
    
    $message = null;
    
    // Handle form submissions
    if ($_POST && extreme_ai_check_csrf()) {
        if (isset($_POST['save_provider'])) {
            $provider = $_POST['provider_name'];
            $config = [
                'display_name' => $_POST['display_name'],
                'api_key' => $_POST['api_key'],
                'api_endpoint' => $_POST['api_endpoint'],
                'model' => $_POST['model'],
                'enabled' => isset($_POST['enabled']) ? 1 : 0
            ];
            
            $result = extreme_ai_save_provider_config($provider, $config);
            
            if (isset($result['error'])) {
                $message = ['type' => 'error', 'text' => $result['error']];
            } else {
                $message = ['type' => 'success', 'text' => 'Provider configuration saved successfully!'];
            }
        }
    }
    
    // Get existing provider configurations with proper error handling
    $providers_config = [];
    try {
        $result = $db->sql_query("SELECT * FROM {$prefix}_extreme_ai_providers");
        while ($row = $db->sql_fetchrow($result)) {
            $providers_config[$row['name']] = $row;
        }
        $db->sql_freeresult($result);
    } catch (Exception $e) {
        // Table might not exist yet - log error but continue
        error_log("Provider config query failed: " . $e->getMessage());
    }
    
    // Define available providers
    $available_providers = [
        'anthropic' => [
            'name' => 'Anthropic Claude',
            'icon' => 'fas fa-brain',
            'default_endpoint' => 'https://api.anthropic.com/v1/messages',
            'default_model' => 'claude-3-haiku-20240307'
        ],
        'openai' => [
            'name' => 'OpenAI GPT',
            'icon' => 'fas fa-robot',
            'default_endpoint' => 'https://api.openai.com/v1/chat/completions',
            'default_model' => 'gpt-4o'
        ],
        'google' => [
            'name' => 'Google Gemini',
            'icon' => 'fab fa-google',
            'default_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/',
            'default_model' => 'gemini-pro'
        ],
        'ollama' => [
            'name' => 'Ollama Local',
            'icon' => 'fas fa-server',
            'default_endpoint' => 'http://localhost:11434',
            'default_model' => 'llama2'
        ]
    ];
    
    OpenTable();
    
    // Include styles using template system
    extreme_ai_load_template('styles');
    
    // Render providers template using template system
    extreme_ai_load_template('providers', [
        'providers_config' => $providers_config,
        'available_providers' => $available_providers,
        'message' => $message,
        'admin_file' => $admin_file,
        'csrf_token' => extreme_ai_get_csrf()
    ]);
    
    CloseTable();
    include_once NUKE_BASE_DIR . 'footer.php';
}

function extreme_ai_settings_page() {
    global $admin_file, $prefix, $db;
    
    // Include stylesheets and scripts
    extreme_ai_include_assets();
    
    // Load settings specific JavaScript - after core
    evo_include_script(
        'extreme_ai-settings-script',
        'includes/extreme_ai/js/settings.js',
        time(),
        true // Load in footer after core
    );
    
    include_once NUKE_BASE_DIR . 'header.php';
    title('ExtremeAI Administration - Settings');
    
    $message = null;
    
    // Handle form submission
    if ($_POST && extreme_ai_check_csrf()) {
        if (isset($_POST['save_settings'])) {
            $settings = [];
            
            // Process form data
            $settings['extreme_ai_debug'] = isset($_POST['extreme_ai_debug']);
            $settings['extreme_ai_cache_ttl'] = (int)($_POST['extreme_ai_cache_ttl'] ?? 3600);
            $settings['extreme_ai_max_tokens'] = (int)($_POST['extreme_ai_max_tokens'] ?? 4096);
            $settings['extreme_ai_rate_limit'] = (int)($_POST['extreme_ai_rate_limit'] ?? 1000);
            $settings['extreme_ai_default_timeout'] = (int)($_POST['extreme_ai_default_timeout'] ?? 30);
            $settings['extreme_ai_max_concurrent_requests'] = (int)($_POST['extreme_ai_max_concurrent_requests'] ?? 10);
            $settings['extreme_ai_cache_enabled'] = isset($_POST['extreme_ai_cache_enabled']);
            $settings['extreme_ai_auto_cleanup_days'] = (int)($_POST['extreme_ai_auto_cleanup_days'] ?? 30);
            $settings['extreme_ai_log_level'] = $_POST['extreme_ai_log_level'] ?? 'error';
            
            $result = extreme_ai_save_system_settings($settings);
            
            if (isset($result['error'])) {
                $message = ['type' => 'error', 'text' => $result['error']];
            } else {
                $message = ['type' => 'success', 'text' => 'Settings saved successfully!'];
            }
        }
    }
    
    // Load current settings
    $settings = extreme_ai_get_system_settings();
    
    // Get additional settings from other providers in the config table
    $additional_settings = [];
    try {
        $additional_result = $db->sql_query("SELECT provider, `key`, value 
                                           FROM {$prefix}_extreme_ai_config 
                                           WHERE provider != 'system' 
                                           ORDER BY provider, `key`");
        
        while ($row = $db->sql_fetchrow($additional_result)) {
            $additional_settings[$row['provider']][$row['key']] = $row['value'];
        }
        $db->sql_freeresult($additional_result);
    } catch (Exception $e) {
        // Handle error silently for now
    }
    
    OpenTable();
    
    // Include styles using template system
    extreme_ai_load_template('styles');
    
    // Render settings template using template system
    extreme_ai_load_template('settings', [
        'settings' => $settings,
        'additional_settings' => $additional_settings,
        'message' => $message,
        'admin_file' => $admin_file,
        'csrf_token' => extreme_ai_get_csrf()
    ]);
    
    CloseTable();
    include_once NUKE_BASE_DIR . 'footer.php';
}

function extreme_ai_analytics_page() {
    global $admin_file;
    
    extreme_ai_include_assets();
    
    include_once NUKE_BASE_DIR . 'header.php';
    title('ExtremeAI Administration - Analytics');
    
    extreme_ai_admin_menu();
    OpenTable();
    echo "<div style='text-align: center; padding: 50px;'>";
    echo "<h2><i class='fas fa-chart-line'></i> Analytics Dashboard</h2>";
    echo "<p>Advanced analytics and reporting features coming soon!</p>";
    echo "<p><a href='$admin_file.php?op=extremeai_dashboard' class='btn btn-primary'>Return to Dashboard</a></p>";
    echo "</div>";
    CloseTable();
    include_once NUKE_BASE_DIR . 'footer.php';
}

function extreme_ai_test_console_page() {
    global $admin_file;
    
    // Debug: Check if we're in an AJAX request that should have been caught earlier
    if (!empty($_POST['ajax_action']) || !empty($_GET['ajax_action'])) {
        echo "<!-- DEBUG: AJAX request detected in page render - this shouldn't happen! -->";
        echo "<!-- POST ajax_action: " . ($_POST['ajax_action'] ?? 'none') . " -->";
        echo "<!-- GET ajax_action: " . ($_GET['ajax_action'] ?? 'none') . " -->";
        echo "<!-- POST data: " . json_encode($_POST) . " -->";
        
        // Instead of rendering the page, handle the AJAX request here
        extreme_ai_handle_ajax();
        exit;
    }
    
    extreme_ai_include_assets();
    
    // Include test console specific assets
    evo_include_style('extreme-ai-test-console', EXTREME_AI_ASSETS_URL . '/css/test-console.css');
    evo_include_script('extreme-ai-test-console', EXTREME_AI_ASSETS_URL . '/js/test-console.js', 'footer');
    
    include_once NUKE_BASE_DIR . 'header.php';
    title('ExtremeAI Administration - Test Console');
    
    // Load provider configurations for the template
    $providers = extreme_ai_get_all_providers();
    $csrf_token = extreme_ai_get_csrf();
    
    // Debug provider loading
    error_log("ExtremeAI Test Console: Loaded providers: " . json_encode($providers));
    foreach ($providers as $key => $provider) {
        $is_enabled = !empty($provider['enabled']);
        $has_api_key = !empty($provider['api_key']) || $key === 'ollama';
        $has_endpoint = !empty($provider['api_endpoint']) || !empty($provider['default_endpoint']);
        $has_model = !empty($provider['model']) || !empty($provider['default_model']);
        $is_configured = $is_enabled && $has_api_key && $has_endpoint && $has_model;
        
        error_log("ExtremeAI Test Console: Provider $key - enabled: " . ($is_enabled ? 'yes' : 'no') . 
                  ", has_api_key: " . ($has_api_key ? 'yes' : 'no') . 
                  ", has_endpoint: " . ($has_endpoint ? 'yes' : 'no') . 
                  ", has_model: " . ($has_model ? 'yes' : 'no') . 
                  ", configured: " . ($is_configured ? 'yes' : 'no'));
    }
    
    // Load the test console template
    extreme_ai_load_template('test_console', [
        'providers' => $providers,
        'admin_file' => $admin_file,
        'csrf_token' => $csrf_token
    ]);
    
    include_once NUKE_BASE_DIR . 'footer.php';
}

function extreme_ai_workflows_page() {
    global $admin_file;
    
    extreme_ai_include_assets();
    
    include_once NUKE_BASE_DIR . 'header.php';
    title('ExtremeAI Administration - Workflows');
    
    extreme_ai_admin_menu();
    OpenTable();
    echo "<div style='text-align: center; padding: 50px;'>";
    echo "<h2><i class='fas fa-project-diagram'></i> AI Workflows</h2>";
    echo "<p>Automated workflow management coming soon!</p>";
    echo "<p><a href='$admin_file.php?op=extremeai_dashboard' class='btn btn-primary'>Return to Dashboard</a></p>";
    echo "</div>";
    CloseTable();
    include_once NUKE_BASE_DIR . 'footer.php';
}

function extreme_ai_agents_page() {
    global $admin_file;
    
    extreme_ai_include_assets();
    
    include_once NUKE_BASE_DIR . 'header.php';
    title('ExtremeAI Administration - AI Agents');
    
    extreme_ai_admin_menu();
    OpenTable();
    echo "<div style='text-align: center; padding: 50px;'>";
    echo "<h2><i class='fas fa-robot'></i> AI Agents</h2>";
    echo "<p>AI agent management coming soon!</p>";
    echo "<p><a href='$admin_file.php?op=extremeai_dashboard' class='btn btn-primary'>Return to Dashboard</a></p>";
    echo "</div>";
    CloseTable();
    include_once NUKE_BASE_DIR . 'footer.php';
}

// Handle AJAX requests (for status checks and stats only, not form submission)
if (isset($_GET['ajax_action']) || isset($_POST['ajax_action'])) {
    error_log("ExtremeAI: AJAX request detected - GET action: " . ($_GET['ajax_action'] ?? 'none') . ", POST action: " . ($_POST['ajax_action'] ?? 'none'));
    extreme_ai_handle_ajax();
}

// Include stylesheets and scripts - CLEANED VERSION
// OLD CODE DISABLED - now handled by extreme_ai_main()
if (false && is_admin()) {
    // Main stylesheet
    evo_include_style(
        'extreme_ai-stylesheet',
        'includes/extreme_ai/css/extreme_ai_clean.css',  // FIXED: Use clean CSS
        time()
    );
    
    // UI components stylesheet
    evo_include_style(
        'extreme_ai-components-stylesheet',
        'includes/extreme_ai/css/extreme-ai-components.css',
        time()
    );
    
    // Core JavaScript library - must load in header before other scripts
    evo_include_script(
        'extreme_ai-core-script',
        'includes/extreme_ai/js/extreme-ai-core.js',
        time(),
        false // Load in header before other scripts
    );
    
    // Note: Page-specific JavaScript files are loaded in their respective page functions

    include_once NUKE_BASE_DIR . 'header.php';

    title('ExtremeAI Administration');

    // Navigation menu
    extreme_ai_admin_menu();

    // Get the current action from the URL
    $op = $_GET['op'] ?? 'extremeai_dashboard';

    // Handle different actions
    switch ($op) {
        case 'extremeai_dashboard':
        default:
            extreme_ai_dashboard();
            break;
            
        case 'extremeai_providers':
            extreme_ai_providers_page();
            break;
            
        case 'extremeai_settings':
            extreme_ai_settings_page();
            break;
            
        // Additional cases would continue here...
        // case 'extremeai_test_console':
        // case 'extremeai_analytics':
        // case 'extremeai_workflows':
        // case 'extremeai_agents':
    }

    include_once NUKE_BASE_DIR . 'footer.php';
} 
// OLD ERROR HANDLING DISABLED - admin check now done at top of file
/* else {
    include NUKE_BASE_DIR . 'header.php';
    GraphicAdmin();
    OpenTable();
    echo "<center><strong>ERROR</strong><br />
    <br />You don't have permission to access this area.</center>";
    CloseTable();
    include NUKE_BASE_DIR . 'footer.php';
} */


// Main admin module switch statement
switch($op) {
    case "extremeai":
    case "extremeai_dashboard":
    default:
        extreme_ai_dashboard();
        break;
        
    case "extremeai_providers":
        extreme_ai_providers_page();
        break;
        
    case "extremeai_settings":
        extreme_ai_settings_page();
        break;
        
    case "extremeai_analytics":
        extreme_ai_analytics_page();
        break;
        
    case "extremeai_test_console":
        // Debug: Always log when this case is hit
        error_log("ExtremeAI Test Console: extremeai_test_console case hit");
        error_log("ExtremeAI Test Console: GET params: " . json_encode($_GET));
        error_log("ExtremeAI Test Console: POST params: " . json_encode($_POST));
        error_log("ExtremeAI Test Console: REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        
        // Check for AJAX requests first - Enhanced debugging
        $ajax_get = isset($_GET['ajax_action']);
        $ajax_post = isset($_POST['ajax_action']);
        error_log("ExtremeAI Test Console: AJAX GET check: " . ($ajax_get ? 'true' : 'false'));
        error_log("ExtremeAI Test Console: AJAX POST check: " . ($ajax_post ? 'true' : 'false'));
        error_log("ExtremeAI Test Console: GET ajax_action value: " . ($_GET['ajax_action'] ?? 'null'));
        error_log("ExtremeAI Test Console: POST ajax_action value: " . ($_POST['ajax_action'] ?? 'null'));
        
        if ($ajax_get || $ajax_post) {
            error_log("ExtremeAI Test Console: AJAX request detected in test_console operation");
            error_log("ExtremeAI Test Console: Calling extreme_ai_handle_ajax()");
            extreme_ai_handle_ajax();
            exit; // Make sure we don't continue to render the page
        } else {
            error_log("ExtremeAI Test Console: No AJAX action detected, rendering page");
        }
        extreme_ai_test_console_page();
        break;
        
    case "extremeai_workflows":
        extreme_ai_workflows_page();
        break;
        
    case "extremeai_agents":
        extreme_ai_agents_page();
        break;
}
?>