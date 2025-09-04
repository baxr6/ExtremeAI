<?php
/**
 * ExtremeAI Admin Index Evo Extreme - CLEANED VERSION
 *
 * Handles communication with multiple AI providers including Anthropic's Claude API,
 * managing rate limits, content moderation, and usage statistics.
 *
 * @category Extreme_AI
 * @package  Evo-Extreme
 * @author   Deano Welch <deano.welch@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/baxr6/
 * @since    2.0.0
 * @requires PHP 8.4 or higher
 */

if (!defined('NUKE_FILE')) {
    die("You can't access this file directly...");
}

require_once(dirname(__FILE__) . '/ExtremeAI_Providers_Clean.php');

// Core AI System Configuration - Now loaded from database
if (!defined('EXTREME_AI_VERSION')) define('EXTREME_AI_VERSION', '2.0.0');

/**
 * Load system settings from database with fallback to defaults
 */
function extreme_ai_load_settings() {
    global $prefix, $db;
    
    $defaults = [
        'EXTREME_AI_DEBUG' => false,
        'EXTREME_AI_CACHE_TTL' => 3600,
        'EXTREME_AI_MAX_TOKENS' => 4096,
        'EXTREME_AI_RATE_LIMIT' => 1000
    ];
    
    try {
        $result = $db->sql_query("SELECT `key`, value 
                                  FROM {$prefix}_extreme_ai_config 
                                  WHERE provider = 'system'
                                  AND `key` IN ('extreme_ai_debug', 'extreme_ai_cache_ttl', 'extreme_ai_max_tokens', 'extreme_ai_rate_limit')");
        
        while ($row = $db->sql_fetchrow($result)) {
            $key = strtoupper($row['key']);
            $value = $row['value'];
            
            // Cast to appropriate type based on defaults
            if (isset($defaults[$key])) {
                if (is_bool($defaults[$key])) {
                    $value = (bool)$value;
                } elseif (is_int($defaults[$key])) {
                    $value = (int)$value;
                } elseif (is_float($defaults[$key])) {
                    $value = (float)$value;
                }
            }
            
            if (!defined($key)) {
                define($key, $value);
            }
        }
        $db->sql_freeresult($result);
        
    } catch (Exception $e) {
        // Table might not exist yet - use defaults
        error_log("Settings loading failed, using defaults: " . $e->getMessage());
    }
    
    // Define any missing constants with defaults
    foreach ($defaults as $key => $default_value) {
        if (!defined($key)) {
            define($key, $default_value);
        }
    }
}

// Load settings immediately
extreme_ai_load_settings();

/**
 * EXTREME AI CORE ENGINE
 * Multi-provider AI integration system with improved error handling and consistency
 */
class ExtremeAI_Core {
    
    private static $instance = null;
    private $providers = [];
    private $config = [];
    private $cache = [];
    private $usage_stats = [];
    private $workflow_engine = null;
    private $agent_manager = null;
    
    // Supported AI Providers
    const PROVIDER_ANTHROPIC = 'anthropic';
    const PROVIDER_OPENAI = 'openai';
    const PROVIDER_GOOGLE = 'google';
    const PROVIDER_MISTRAL = 'mistral';
    const PROVIDER_COHERE = 'cohere';
    const PROVIDER_HUGGINGFACE = 'huggingface';
    const PROVIDER_OLLAMA = 'ollama'; // Local AI
    
    // AI Task Types
    const TASK_TEXT_GENERATION = 'text_generation';
    const TASK_CONTENT_ANALYSIS = 'content_analysis';
    const TASK_TRANSLATION = 'translation';
    const TASK_SUMMARIZATION = 'summarization';
    const TASK_CODE_GENERATION = 'code_generation';
    const TASK_IMAGE_ANALYSIS = 'image_analysis';
    const TASK_SENTIMENT_ANALYSIS = 'sentiment_analysis';
    const TASK_ENTITY_EXTRACTION = 'entity_extraction';
    const TASK_CLASSIFICATION = 'classification';
    const TASK_QUESTION_ANSWERING = 'question_answering';
    
    private function __construct() {
        // Register autoloader
        ExtremeAI_Autoloader::register();
        $this->loadConfiguration();
        $this->initializeProviders();
        $this->workflow_engine = new ExtremeAI_WorkflowEngine($this);
        $this->agent_manager = new ExtremeAI_AgentManager($this);
        
        if (EXTREME_AI_DEBUG) {
            $this->debugFullStatus();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Debug system status (only in debug mode)
     */
    public function debugFullStatus() {
        if (!EXTREME_AI_DEBUG) return;
        
        error_log("=== EXTREME AI DEBUG STATUS ===");
        error_log("Total providers loaded: " . count($this->providers));
        error_log("Provider names: " . implode(', ', array_keys($this->providers)));
        
        foreach ($this->providers as $name => $provider) {
            error_log("--- Provider: $name ---");
            error_log("Capabilities: " . implode(', ', $provider->getCapabilities()));
            error_log("Supports text_generation: " . ($provider->supportsTask(self::TASK_TEXT_GENERATION) ? 'Yes' : 'No'));
            error_log("Is healthy: " . ($this->isProviderHealthy($name) ? 'Yes' : 'No'));
        }
        
        $selected = $this->selectBestProvider(self::TASK_TEXT_GENERATION);
        error_log("Selected provider: " . ($selected ? $selected->getName() : 'NULL'));
        error_log("=== END DEBUG ===");
    }
    
    /**
     * Load system configuration with proper error handling
     */
    private function loadConfiguration() {
        global $prefix, $db;
        
        try {
            $result = $db->sql_query("SELECT * FROM {$prefix}_extreme_ai_config");
            while ($row = $db->sql_fetchrow($result)) {
                $this->config[$row['provider']][$row['key']] = $row['value'];
            }
            $db->sql_freeresult($result);
        } catch (Exception $e) {
            $this->logError('system', 'config_load', "Failed to load configuration: " . $e->getMessage());
        }
        
        $this->setDefaultConfigs();
    }
    
    /**
     * Parses a timeframe string into a valid SQL INTERVAL clause.
     *
     * @param string $timeframe The timeframe string (e.g., '24h', '7d', '30d').
     * @return string The SQL INTERVAL clause.
     * @throws Exception If the timeframe is invalid.
     */
    private function parseTimeframe($timeframe) {
        $timeframes = [
            '24h' => 'INTERVAL 24 HOUR',
            '7d'  => 'INTERVAL 7 DAY',
            '30d' => 'INTERVAL 30 DAY',
            '90d' => 'INTERVAL 90 DAY',
            '1y'  => 'INTERVAL 1 YEAR'
        ];
        
        if (!isset($timeframes[$timeframe])) {
            throw new Exception("Invalid timeframe specified: {$timeframe}");
        }
        
        return $timeframes[$timeframe];
    }

    /**
     * Initialize AI Providers with better error handling
     */
    private function initializeProviders(): void {
        $providers = [
            'anthropic' => ExtremeAI_AnthropicProvider::class,
            'openai'    => ExtremeAI_OpenAIProvider::class,
            'google'    => ExtremeAI_GoogleProvider::class,
            'ollama'    => ExtremeAI_OllamaProvider::class,
        ];

        foreach ($providers as $name => $class) {
            if (EXTREME_AI_DEBUG) {
                error_log("Checking provider: $name");
            }
            
            if ($this->isProviderEnabled($name)) {
                if (EXTREME_AI_DEBUG) {
                    error_log("Provider $name is enabled");
                }
                
                $config = $this->getProviderConfig($name);
                if (!empty($config)) {
                    try {
                        $this->providers[$name] = new $class($config);
                        if (EXTREME_AI_DEBUG) {
                            error_log("Successfully initialized $name");
                        }
                    } catch (Exception $e) {
                        $this->logError($name, 'initialization', $e->getMessage());
                    }
                }
            }
        }
    }
    
    /**
     * Get the list of initialized AI providers.
     *
     * @return array
     */
    public function getProviders() {
        return $this->providers;
    }
    
    /**
     * Execute AI task with intelligent provider selection
     */
    public function executeTask($task_type, $input, $options = []) {
        $start_time = microtime(true);
        
        // Select best provider for task
        $provider = $this->selectBestProvider($task_type, $options);
        if (!$provider) {
            return $this->createErrorResponse('No suitable provider available');
        }
        
        // Check cache
        if (!isset($options['bypass_cache']) || !$options['bypass_cache']) {
            $cached = $this->getCachedResponse($task_type, $input, $options);
            if ($cached) {
                return $cached;
            }
        }
        
        try {
            // Execute task
            $response = $provider->execute($task_type, $input, $options);
            
            // Cache response
            $this->cacheResponse($task_type, $input, $options, $response);
            
            // Log usage
            $this->logUsage($provider->getName(), $task_type, microtime(true) - $start_time, $response);
            
            return $response;
            
        } catch (Exception $e) {
            $this->logError($provider->getName(), $task_type, $e->getMessage());
            
            // Try fallback provider
            return $this->executeWithFallback($task_type, $input, $options, $provider->getName());
        }
    }
    
    /**
     * Smart provider selection based on task type and performance
     */
    private function selectBestProvider($task_type, $options = []) {
        // Check if specific provider requested
        if (isset($options['provider']) && isset($this->providers[$options['provider']])) {
            return $this->providers[$options['provider']];
        }
        
        // Task-specific provider preferences
        $task_preferences = [
            self::TASK_TEXT_GENERATION => [self::PROVIDER_ANTHROPIC, self::PROVIDER_OPENAI],
            self::TASK_CODE_GENERATION => [self::PROVIDER_ANTHROPIC, self::PROVIDER_OPENAI],
            self::TASK_CONTENT_ANALYSIS => [self::PROVIDER_ANTHROPIC, self::PROVIDER_GOOGLE],
            self::TASK_TRANSLATION => [self::PROVIDER_GOOGLE, self::PROVIDER_OPENAI],
            self::TASK_IMAGE_ANALYSIS => [self::PROVIDER_GOOGLE, self::PROVIDER_OPENAI],
            self::TASK_SENTIMENT_ANALYSIS => [self::PROVIDER_GOOGLE, self::PROVIDER_HUGGINGFACE],
        ];
        
        $preferred = $task_preferences[$task_type] ?? array_keys($this->providers);
        
        // Select based on performance and availability
        foreach ($preferred as $provider_name) {
            if (isset($this->providers[$provider_name]) && $this->isProviderHealthy($provider_name)) {
                return $this->providers[$provider_name];
            }
        }
        
        // Return any available provider
        return !empty($this->providers) ? reset($this->providers) : null;
    }
    
    /**
     * Public wrapper to list active provider names.
     *
     * @return array
     */
    public function listProviders(): array {
        return array_keys($this->getProviders());
    }

    /**
     * Run health check across all providers.
     *
     * @return string
     */
    public function healthCheck(): string {
        $providers = $this->getProviders();
        if (empty($providers)) {
            return 'No providers initialized';
        }

        foreach ($providers as $name => $provider) {
            if (!$this->isProviderHealthy($name)) {
                return "Provider {$name} reported unhealthy";
            }
        }

        return 'OK';
    }

    /**
     * Advanced content generation with multi-step processing
     */
    public function generateAdvancedContent($type, $parameters) {
        switch ($type) {
            case 'article_suite':
                return $this->generateArticleSuite($parameters);
            case 'seo_content':
                return $this->generateSEOContent($parameters);
            case 'multilingual_content':
                return $this->generateMultilingualContent($parameters);
            case 'interactive_content':
                return $this->generateInteractiveContent($parameters);
            case 'personalized_content':
                return $this->generatePersonalizedContent($parameters);
            default:
                return $this->createErrorResponse('Unknown content type: ' . $type);
        }
    }
    
    /**
     * Generate complete article suite (article + meta + images + tags)
     */
    private function generateArticleSuite($params) {
        $topic = $params['topic'] ?? '';
        $category = $params['category'] ?? '';
        $length = $params['length'] ?? 'medium';
        $audience = $params['audience'] ?? 'general';
        
        $suite = [];
        
        // 1. Generate main article
        $article_result = $this->executeTask(self::TASK_TEXT_GENERATION, [
            'prompt' => "Write a comprehensive {$length} article about '{$topic}' for {$audience} audience in {$category} category",
            'system' => 'You are an expert content writer creating engaging, informative articles with proper structure and SEO optimization.'
        ]);
        $suite['article'] = $article_result['content'] ?? '';
        
        // 2. Generate SEO metadata
        $meta_result = $this->executeTask(self::TASK_TEXT_GENERATION, [
            'prompt' => "Generate SEO metadata for an article about '{$topic}': meta title (60 chars), meta description (160 chars), focus keyword, and 10 relevant tags",
            'system' => 'You are an SEO expert creating optimized metadata.'
        ]);
        $suite['seo_meta'] = $meta_result['content'] ?? '';
        
        // 3. Generate social media versions
        $social_result = $this->executeTask(self::TASK_TEXT_GENERATION, [
            'prompt' => "Create social media posts about '{$topic}': LinkedIn post (1300 chars), Twitter thread (5 tweets), Facebook post (400 words), Instagram caption (2200 chars)",
            'system' => 'You are a social media expert creating engaging posts for different platforms.'
        ]);
        $suite['social_media'] = $social_result['content'] ?? '';
        
        // 4. Generate image suggestions and alt text
        $image_result = $this->executeTask(self::TASK_TEXT_GENERATION, [
            'prompt' => "Suggest 5 relevant images for an article about '{$topic}' with detailed descriptions and alt text for each",
            'system' => 'You are a visual content strategist suggesting appropriate imagery.'
        ]);
        $suite['images'] = $image_result['content'] ?? '';
        
        // 5. Content analysis and recommendations
        $analysis_result = $this->executeTask(self::TASK_CONTENT_ANALYSIS, $suite['article']);
        $suite['analysis'] = $analysis_result;
        
        return $suite;
    }
    
    /**
     * Real-time content optimization
     */
    public function optimizeContentRealtime($content, $optimization_type) {
        switch ($optimization_type) {
            case 'seo':
                return $this->optimizeForSEO($content);
            case 'readability':
                return $this->optimizeReadability($content);
            case 'engagement':
                return $this->optimizeEngagement($content);
            case 'accessibility':
                return $this->optimizeAccessibility($content);
            case 'performance':
                return $this->optimizePerformance($content);
            default:
                return $this->optimizeAll($content);
        }
    }
    
    /**
     * AI-powered user personalization
     */
    public function personalizeForUser($content, $user_data) {
        $personalization_prompt = "Personalize this content for a user with these characteristics: ";
        $personalization_prompt .= "Location: " . ($user_data['location'] ?? 'Unknown') . ", ";
        $personalization_prompt .= "Interests: " . implode(', ', $user_data['interests'] ?? []) . ", ";
        $personalization_prompt .= "Reading level: " . ($user_data['reading_level'] ?? 'intermediate') . ", ";
        $personalization_prompt .= "Previous interactions: " . ($user_data['interaction_history'] ?? 'none');
        $personalization_prompt .= "\n\nContent to personalize:\n" . $content;
        
        return $this->executeTask(self::TASK_TEXT_GENERATION, [
            'prompt' => $personalization_prompt,
            'system' => 'You are a personalization expert creating tailored content experiences.'
        ]);
    }
    
    /**
     * Advanced analytics and insights with proper error handling
     */
    public function getAdvancedAnalytics(string $timeframe = '24h'): array {
        global $prefix, $db;

        $hours_map = ['24h' => 24, '7d' => 168, '30d' => 720, '90d' => 2160, '1y' => 8760];
        $hours = $hours_map[$timeframe] ?? 24;

        $analytics = ['usage' => [], 'performance' => [], 'costs' => [], 'content_types' => [], 'engagement' => []];

        try {
            $usage_query = "
                SELECT provider, task_type, COUNT(*) AS requests,
                       AVG(response_time) AS avg_response_time,
                       AVG(tokens_used) AS avg_tokens,
                       SUM(cost) AS total_cost
                FROM {$prefix}_extreme_ai_usage
                WHERE created >= NOW() - INTERVAL {$hours} HOUR
                GROUP BY provider, task_type
                ORDER BY requests DESC
            ";

            $result = $db->sql_query($usage_query);
            while ($row = $db->sql_fetchrow($result)) {
                $analytics['usage'][] = [
                    'provider' => $row['provider'],
                    'task_type' => $row['task_type'],
                    'requests' => (int)$row['requests'],
                    'avg_response_time' => round((float)$row['avg_response_time'], 2),
                    'avg_tokens' => round((float)$row['avg_tokens'], 2),
                    'total_cost' => round((float)$row['total_cost'], 6),
                ];
            }
            $db->sql_freeresult($result);

        } catch (Exception $e) {
            $this->logError('system', 'analytics', "Analytics Error: {$e->getMessage()}");
        }

        return $analytics;
    }
    
    /**
     * Error handling and fallback mechanisms
     */
    private function executeWithFallback($task_type, $input, $options, $failed_provider) {
        $available_providers = array_keys($this->providers);
        $remaining_providers = array_diff($available_providers, [$failed_provider]);
        
        foreach ($remaining_providers as $provider_name) {
            if ($this->isProviderHealthy($provider_name)) {
                try {
                    $provider = $this->providers[$provider_name];
                    $response = $provider->execute($task_type, $input, $options);
                    
                    // Log successful fallback
                    $this->logInfo("Fallback successful: {$failed_provider} -> {$provider_name}");
                    
                    return $response;
                    
                } catch (Exception $e) {
                    $this->logError($provider_name, $task_type, $e->getMessage());
                    continue;
                }
            }
        }
        
        return $this->createErrorResponse('All providers failed for task: ' . $task_type);
    }
    
    /**
     * Returns the error rate (%) for a given provider.
     *
     * @param string $provider
     * @param int $hours
     * @return float
     */
    public function getProviderErrorRate(string $provider, int $hours = 24): float {
        global $prefix, $db;

        $hours = max(1, (int)$hours);

        try {
            // Count errors
            $error_query = "
                SELECT COUNT(*) AS error_count
                FROM {$prefix}_extreme_ai_errors
                WHERE provider = '" . $db->sql_escapestring($provider) . "'
                  AND created >= NOW() - INTERVAL {$hours} HOUR
            ";
            $result = $db->sql_query($error_query);
            $row = $db->sql_fetchrow($result);
            $error_count = (int)($row['error_count'] ?? 0);
            $db->sql_freeresult($result);

            // Count total requests
            $total_query = "
                SELECT COUNT(*) AS total
                FROM {$prefix}_extreme_ai_usage
                WHERE provider = '" . $db->sql_escapestring($provider) . "'
                  AND created >= NOW() - INTERVAL {$hours} HOUR
            ";
            $result = $db->sql_query($total_query);
            $row = $db->sql_fetchrow($result);
            $total = max(1, (int)($row['total'] ?? 0)); // avoid division by zero
            $db->sql_freeresult($result);

            return ($error_count / $total) * 100;
            
        } catch (Exception $e) {
            $this->logError($provider, 'error_rate_calculation', $e->getMessage());
            return 100.0; // Assume worst case on error
        }
    }

    /**
     * Returns the average response time (in seconds) for a given provider
     *
     * @param string $provider
     * @param int $hours Optional: lookback period in hours
     * @return float Average response time
     */
    public function getProviderResponseTime(string $provider, int $hours = 24): float {
        global $prefix, $db;
        $hours = max(1, (int)$hours);

        try {
            $query = "
                SELECT AVG(response_time) AS avg_response
                FROM {$prefix}_extreme_ai_usage
                WHERE provider = '" . $db->sql_escapestring($provider) . "'
                  AND created >= NOW() - INTERVAL {$hours} HOUR
            ";
            $result = $db->sql_query($query);
            $row = $db->sql_fetchrow($result);
            $avg_response = (float)($row['avg_response'] ?? 0);
            $db->sql_freeresult($result);
            
            return $avg_response;
            
        } catch (Exception $e) {
            $this->logError($provider, 'response_time_calculation', $e->getMessage());
            return 999.0; // Assume worst case on error
        }
    }

    /**
     * Health check for providers with improved error handling
     */
    private function isProviderHealthy(string $provider_name): bool {
        if (!isset($this->providers[$provider_name])) {
            if (EXTREME_AI_DEBUG) {
                error_log("DEBUG: Provider $provider_name not found in providers array");
            }
            return false;
        }

        $error_rate = $this->getProviderErrorRate($provider_name, 1);
        if (EXTREME_AI_DEBUG) {
            error_log("DEBUG: Error rate for $provider_name: $error_rate%");
        }
        
        if ($error_rate > 50) {
            if (EXTREME_AI_DEBUG) {
                error_log("DEBUG: Provider $provider_name rejected - high error rate ($error_rate%)");
            }
            return false;
        }

        $avg_response_time = $this->getProviderResponseTime($provider_name, 1);
        if (EXTREME_AI_DEBUG) {
            error_log("DEBUG: Response time for $provider_name: {$avg_response_time}s");
        }
        
        if ($avg_response_time > 30) {
            if (EXTREME_AI_DEBUG) {
                error_log("DEBUG: Provider $provider_name rejected - slow response ({$avg_response_time}s)");
            }
            return false;
        }

        if (EXTREME_AI_DEBUG) {
            error_log("DEBUG: Provider $provider_name passed health check");
        }
        return true;
    }
     
    /**
     * Intelligent caching system with proper error handling
     */
    private function getCachedResponse($task_type, $input, $options) {
        $cache_key = $this->generateCacheKey($task_type, $input, $options);
        
        global $prefix, $db;
        
        try {
            $query = "SELECT response, created FROM {$prefix}_extreme_ai_cache 
                      WHERE cache_key = '" . $db->sql_escapestring($cache_key) . "' 
                      AND created > DATE_SUB(NOW(), INTERVAL " . EXTREME_AI_CACHE_TTL . " SECOND)";
            $result = $db->sql_query($query);
            
            if ($db->sql_numrows($result) > 0) {
                $row = $db->sql_fetchrow($result);
                $db->sql_freeresult($result);
                return json_decode($row['response'], true);
            }
            
            $db->sql_freeresult($result);
        } catch (Exception $e) {
            $this->logError('system', 'cache_retrieve', $e->getMessage());
        }
        
        return null;
    }
    
    private function cacheResponse($task_type, $input, $options, $response) {
        $cache_key = $this->generateCacheKey($task_type, $input, $options);
        $response_json = json_encode($response);
        
        global $prefix, $db;
        
        try {
            $query = "INSERT INTO {$prefix}_extreme_ai_cache 
                      (cache_key, task_type, response, created) 
                      VALUES ('" . $db->sql_escapestring($cache_key) . "', 
                              '" . $db->sql_escapestring($task_type) . "', 
                              '" . $db->sql_escapestring($response_json) . "', 
                              NOW())
                      ON DUPLICATE KEY UPDATE 
                      response = '" . $db->sql_escapestring($response_json) . "', 
                      created = NOW()";
            $db->sql_query($query);
        } catch (Exception $e) {
            $this->logError('system', 'cache_store', $e->getMessage());
        }
    }
    
    /**
     * Usage logging and analytics with improved error handling
     */
    private function logUsage(string $provider, string $task_type, float $response_time, $response) {
        global $prefix, $db;

        try {
            $tokens_used = $this->estimateTokenUsage($response);
            $cost = $this->calculateCost($provider, $tokens_used);

            $request_data = json_encode($response['request'] ?? '');
            $response_data = json_encode($response);

            $query = "
                INSERT INTO {$prefix}_extreme_ai_usage
                    (provider, task_type, response_time, tokens_used, cost, request_data, response_data, created)
                VALUES
                    ('" . $db->sql_escapestring($provider) . "', 
                     '" . $db->sql_escapestring($task_type) . "', 
                     '" . (float)$response_time . "', 
                     '" . (int)$tokens_used . "', 
                     '" . (float)$cost . "', 
                     '" . $db->sql_escapestring($request_data) . "', 
                     '" . $db->sql_escapestring($response_data) . "', 
                     NOW())
            ";
            $db->sql_query($query);
        } catch (Exception $e) {
            $this->logError($provider, 'usage_logging', $e->getMessage());
        }
    }
    
    /**
     * Utility methods
     */
    private function generateCacheKey($task_type, $input, $options) {
        return md5($task_type . serialize($input) . serialize($options));
    }
    
    private function estimateTokenUsage($response) {
        if (is_array($response) && isset($response['usage']['tokens'])) {
            return $response['usage']['tokens'];
        }
        
        // Rough estimation: 1 token ≈ 4 characters
        $content = is_array($response) ? json_encode($response) : (string)$response;
        return ceil(strlen($content) / 4);
    }
    
    private function calculateCost($provider, $tokens) {
        $pricing = [
            self::PROVIDER_ANTHROPIC => ['input' => 0.00001, 'output' => 0.00003],
            self::PROVIDER_OPENAI => ['input' => 0.00001, 'output' => 0.00002],
            self::PROVIDER_GOOGLE => ['input' => 0.000001, 'output' => 0.000002],
            self::PROVIDER_OLLAMA => ['input' => 0, 'output' => 0], // Local is free
        ];
        
        if (!isset($pricing[$provider])) {
            return 0;
        }
        
        // Rough estimate assuming 70% output tokens
        $input_tokens = $tokens * 0.3;
        $output_tokens = $tokens * 0.7;
        
        return ($input_tokens * $pricing[$provider]['input']) + 
               ($output_tokens * $pricing[$provider]['output']);
    }
    
    private function createErrorResponse($message) {
        return [
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function logError($provider, $task_type, $message) {
        if (EXTREME_AI_DEBUG) {
            error_log("Extreme AI Error [{$provider}][{$task_type}]: {$message}");
        }
        
        global $prefix, $db;
        
        try {
            $query = "INSERT INTO {$prefix}_extreme_ai_errors 
                      (provider, task_type, error_message, created) 
                      VALUES ('" . $db->sql_escapestring($provider) . "', 
                              '" . $db->sql_escapestring($task_type) . "', 
                              '" . $db->sql_escapestring($message) . "', 
                              NOW())";
            $db->sql_query($query);
        } catch (Exception $e) {
            error_log("Failed to log error to database: " . $e->getMessage());
        }
    }
    
    private function logInfo($message) {
        if (EXTREME_AI_DEBUG) {
            error_log("Extreme AI Info: {$message}");
        }
    }
    
    /**
     * Provider configuration methods with improved error handling
     */
    private function isProviderEnabled(string $provider_name): bool {
        global $db, $prefix;

        try {
            $sql = "SELECT enabled FROM {$prefix}_extreme_ai_providers 
                    WHERE name = '" . $db->sql_escapestring($provider_name) . "' LIMIT 1";
            $result = $db->sql_query($sql);

            if ($row = $db->sql_fetchrow($result)) {
                $enabled = (bool)$row['enabled'];
                $db->sql_freeresult($result);
                return $enabled;
            }
            
            $db->sql_freeresult($result);
        } catch (Exception $e) {
            $this->logError($provider_name, 'provider_enabled_check', $e->getMessage());
        }

        return false;
    }
    
    private function getProviderConfig(string $provider_name): array {
        global $db, $prefix;

        try {
            $sql = "SELECT * FROM {$prefix}_extreme_ai_providers 
                    WHERE name = '" . $db->sql_escapestring($provider_name) . "' LIMIT 1";
            $result = $db->sql_query($sql);

            if ($row = $db->sql_fetchrow($result)) {
                $config = [
                    'api_endpoint' => $row['api_endpoint'],
                    'api_key'      => $row['api_key'],
                    'model'        => $row['model'],
                    'capabilities' => json_decode($row['capabilities'] ?? '[]', true) ?: [],
                    'settings'     => json_decode($row['settings'] ?? '[]', true) ?: [],
                ];
                $db->sql_freeresult($result);
                return $config;
            }
            
            $db->sql_freeresult($result);
        } catch (Exception $e) {
            $this->logError($provider_name, 'provider_config_load', $e->getMessage());
        }
        
        return [];
    }
    
    private function setDefaultConfigs() {
        // Load system settings from database
        $system_settings = $this->getSystemSettings();
        
        // Set default configurations if not present
        $defaults = [
            'system' => [
                'max_concurrent_requests' => $system_settings['extreme_ai_max_concurrent_requests'] ?? 10,
                'default_timeout' => $system_settings['extreme_ai_default_timeout'] ?? 30,
                'cache_enabled' => $system_settings['extreme_ai_cache_enabled'] ?? true,
                'debug_mode' => $system_settings['extreme_ai_debug'] ?? false
            ]
        ];
        
        foreach ($defaults as $section => $settings) {
            foreach ($settings as $key => $value) {
                if (!isset($this->config[$section][$key])) {
                    $this->config[$section][$key] = $value;
                }
            }
        }
    }
    
    /**
     * Get system settings from database
     */
    public function getSystemSettings() {
        global $prefix, $db;
        
        $defaults = [
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
            $result = $db->sql_query("SELECT `key`, value 
                                      FROM {$prefix}_extreme_ai_config 
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
            $this->logError('system', 'settings_load', "Failed to load settings: " . $e->getMessage());
        }
        
        return $settings;
    }
    
    /**
     * Get a specific setting value
     */
    public function getSetting($key, $default = null) {
        $settings = $this->getSystemSettings();
        return $settings[$key] ?? $default;
    }
}

/**
 * WORKFLOW ENGINE
 * Handles multi-step AI workflows
 */
class ExtremeAI_WorkflowEngine {
    private $ai_core;
    private $workflows = [];
    
    public function __construct($ai_core) {
        $this->ai_core = $ai_core;
        $this->loadWorkflows();
    }
    
    /**
     * Execute predefined workflow
     */
    public function executeWorkflow($workflow_name, $input, $options = []) {
        if (!isset($this->workflows[$workflow_name])) {
            throw new Exception("Workflow not found: {$workflow_name}");
        }
        
        $workflow = $this->workflows[$workflow_name];
        $context = ['input' => $input, 'options' => $options, 'results' => []];
        
        foreach ($workflow['steps'] as $step) {
            $result = $this->executeStep($step, $context);
            $context['results'][$step['name']] = $result;
            
            // Check for conditional execution
            if (isset($step['condition']) && !$this->evaluateCondition($step['condition'], $context)) {
                break;
            }
        }
        
        return $this->formatWorkflowResult($workflow, $context);
    }
    
    private function executeStep($step, $context) {
        $input = $this->prepareStepInput($step, $context);
        return $this->ai_core->executeTask($step['task_type'], $input, $step['options'] ?? []);
    }
    
    /**
     * Load predefined workflows
     */
    private function loadWorkflows() {
        $this->workflows = [
            'content_creation_pipeline' => [
                'name' => 'Complete Content Creation Pipeline',
                'steps' => [
                    [
                        'name' => 'research',
                        'task_type' => ExtremeAI_Core::TASK_TEXT_GENERATION,
                        'options' => ['system' => 'Research expert'],
                    ],
                    [
                        'name' => 'outline',
                        'task_type' => ExtremeAI_Core::TASK_TEXT_GENERATION,
                        'options' => ['system' => 'Content strategist'],
                    ],
                    [
                        'name' => 'write',
                        'task_type' => ExtremeAI_Core::TASK_TEXT_GENERATION,
                        'options' => ['system' => 'Expert writer'],
                    ],
                    [
                        'name' => 'optimize',
                        'task_type' => ExtremeAI_Core::TASK_CONTENT_ANALYSIS,
                    ],
                    [
                        'name' => 'finalize',
                        'task_type' => ExtremeAI_Core::TASK_TEXT_GENERATION,
                        'options' => ['system' => 'Editor'],
                    ]
                ]
            ],
            
            'seo_optimization_pipeline' => [
                'name' => 'Complete SEO Optimization',
                'steps' => [
                    [
                        'name' => 'keyword_research',
                        'task_type' => ExtremeAI_Core::TASK_TEXT_GENERATION,
                    ],
                    [
                        'name' => 'content_analysis',
                        'task_type' => ExtremeAI_Core::TASK_CONTENT_ANALYSIS,
                    ],
                    [
                        'name' => 'optimization',
                        'task_type' => ExtremeAI_Core::TASK_TEXT_GENERATION,
                    ],
                    [
                        'name' => 'meta_generation',
                        'task_type' => ExtremeAI_Core::TASK_TEXT_GENERATION,
                    ]
                ]
            ]
        ];
    }
    
    private function prepareStepInput($step, $context) {
        // Prepare input based on step requirements and previous results
        switch ($step['name']) {
            case 'research':
                return ['prompt' => "Research topic: " . ($context['input']['topic'] ?? '')];
            case 'outline':
                $research = $context['results']['research']['content'] ?? '';
                return ['prompt' => "Create outline based on research: " . $research];
            default:
                return $context['input'];
        }
    }
    
    private function evaluateCondition($condition, $context) {
        // Simple condition evaluation - can be expanded
        return true;
    }
    
    private function formatWorkflowResult($workflow, $context) {
        return [
            'workflow' => $workflow['name'],
            'results' => $context['results'],
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

/**
 * AGENT MANAGER
 * Manages autonomous AI agents
 */
class ExtremeAI_AgentManager {
    private $ai_core;
    private $agents = [];
    
    public function __construct($ai_core) {
        $this->ai_core = $ai_core;
        $this->initializeAgents();
    }
    
    /**
     * Create and manage AI agents
     */
    public function createAgent($type, $config) {
        $agent_id = uniqid('agent_');
        
        switch ($type) {
            case 'content_curator':
                $this->agents[$agent_id] = new ContentCuratorAgent($this->ai_core, $config);
                break;
            case 'seo_optimizer':
                $this->agents[$agent_id] = new SEOOptimizerAgent($this->ai_core, $config);
                break;
            case 'community_manager':
                $this->agents[$agent_id] = new CommunityManagerAgent($this->ai_core, $config);
                break;
            case 'analytics_reporter':
                $this->agents[$agent_id] = new AnalyticsReporterAgent($this->ai_core, $config);
                break;
        }
        
        return $agent_id;
    }
    
    public function getAgent($agent_id) {
        return $this->agents[$agent_id] ?? null;
    }
    
    public function runAgent($agent_id, $task = null) {
        $agent = $this->getAgent($agent_id);
        if ($agent) {
            return $agent->run($task);
        }
        return false;
    }
    
    private function initializeAgents() {
        // Initialize default system agents
        $this->createAgent('content_curator', ['schedule' => 'daily']);
        $this->createAgent('seo_optimizer', ['schedule' => 'hourly']);
        $this->createAgent('analytics_reporter', ['schedule' => 'weekly']);
    }
}

/**
 * AUTOLOADER
 * Handles automatic class loading
 */
class ExtremeAI_Autoloader {
    private static $paths = [];
    
    public static function register() {
        spl_autoload_register(['ExtremeAI_Autoloader', 'load']);
        
        // Add include paths
        self::$paths[] = NUKE_INCLUDE_DIR . '/extreme_ai/classes/';
    }
    
    public static function load($class) {
        // Only load ExtremeAI classes
        if (strpos($class, 'ExtremeAI_') !== 0) {
            return;
        }
        
        foreach (self::$paths as $path) {
            $file = $path . $class . '.php';
            if (file_exists($file)) {
                require_once($file);
                return;
            }
        }
    }
}

// Initialize the system
$extreme_ai = ExtremeAI_Core::getInstance();

?>