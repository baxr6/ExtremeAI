<?php

/************************************************************************/
/* AI PROVIDER IMPLEMENTATIONS - CLEANED VERSION                      */
/* Multi-provider support for various AI services                     */
/************************************************************************/

/**
 * BASE AI PROVIDER ABSTRACT CLASS
 */
abstract class ExtremeAI_BaseProvider {
    protected $config;
    protected $name;
    protected $capabilities = [];
    
    public function __construct($config) {
        $this->config = $config;
        $this->initialize();
    }
    
    abstract protected function initialize();
    abstract public function execute($task_type, $input, $options = []);
    
    public function getName() {
        return $this->name;
    }
    
    public function getCapabilities() {
        return $this->capabilities;
    }
    
    public function supportsTask($task_type) {
        return in_array($task_type, $this->capabilities);
    }
    
    protected function getSystemPromptForTask($task_type) {
        $prompts = [
            ExtremeAI_Core::TASK_TEXT_GENERATION => 'You are an expert content creator producing high-quality, engaging text.',
            ExtremeAI_Core::TASK_CONTENT_ANALYSIS => 'You are a content analysis expert providing detailed insights and recommendations.',
            ExtremeAI_Core::TASK_SUMMARIZATION => 'You are a summarization expert creating concise, accurate summaries.',
            ExtremeAI_Core::TASK_CODE_GENERATION => 'You are a senior software developer creating clean, efficient, well-documented code.',
            ExtremeAI_Core::TASK_TRANSLATION => 'You are a professional translator maintaining meaning and tone across languages.',
            ExtremeAI_Core::TASK_SENTIMENT_ANALYSIS => 'You are a sentiment analysis engine providing a clear assessment.',
            ExtremeAI_Core::TASK_CLASSIFICATION => 'You are a content moderator and classifier.'
        ];
        
        return $prompts[$task_type] ?? 'You are a helpful AI assistant.';
    }
    
    protected function makeRequest($url, $data, $headers = []) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/json'
            ], $headers),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: {$error}");
        }
        
        $response_data = json_decode($response, true);
        
        if ($http_code !== 200) {
            $error_message = $response_data['error']['message'] ?? "HTTP Error {$http_code}";
            throw new Exception("API Error: {$error_message}");
        }
        
        return $response_data;
    }
}

/**
 * ANTHROPIC CLAUDE PROVIDER
 */
class ExtremeAI_AnthropicProvider extends ExtremeAI_BaseProvider {
    protected function initialize() {
        $this->name = 'anthropic';
        $this->capabilities = [
            ExtremeAI_Core::TASK_TEXT_GENERATION,
            ExtremeAI_Core::TASK_CONTENT_ANALYSIS,
            ExtremeAI_Core::TASK_SUMMARIZATION,
            ExtremeAI_Core::TASK_CODE_GENERATION,
            ExtremeAI_Core::TASK_QUESTION_ANSWERING,
            ExtremeAI_Core::TASK_TRANSLATION
        ];
    }
    
    public function execute($task_type, $input, $options = []) {
        if (!$this->supportsTask($task_type)) {
            throw new Exception("Task type {$task_type} not supported by Anthropic provider");
        }
        
        $headers = [
            'x-api-key: ' . $this->config['api_key'],
            'anthropic-version: 2023-06-01'
        ];
        
        $data = [
            'model' => $options['model'] ?? $this->config['model'] ?? 'claude-3-sonnet-20240229',
            'max_tokens' => $options['max_tokens'] ?? 4096,
            'temperature' => $options['temperature'] ?? 0.7,
            'messages' => []
        ];
        
        // Prepare messages based on input format
        $system_prompt = (is_array($input) && isset($input['system'])) ? $input['system'] : $this->getSystemPromptForTask($task_type);
        $user_prompt = is_array($input) ? $input['prompt'] : (string)$input;
        
        $data['system'] = $system_prompt;
        $data['messages'][] = ['role' => 'user', 'content' => $user_prompt];
        
        $response = $this->makeRequest('https://api.anthropic.com/v1/messages', $data, $headers);
        
        return [
            'success' => true,
            'content' => $response['content'][0]['text'] ?? '',
            'usage' => $response['usage'] ?? [],
            'model' => $data['model'],
            'provider' => $this->name
        ];
    }
}

/**
 * OPENAI GPT PROVIDER
 */
class ExtremeAI_OpenAIProvider extends ExtremeAI_BaseProvider {
    protected function initialize() {
        $this->name = 'openai';
        $this->capabilities = [
            ExtremeAI_Core::TASK_TEXT_GENERATION,
            ExtremeAI_Core::TASK_CONTENT_ANALYSIS,
            ExtremeAI_Core::TASK_SUMMARIZATION,
            ExtremeAI_Core::TASK_CODE_GENERATION,
            ExtremeAI_Core::TASK_IMAGE_ANALYSIS,
            ExtremeAI_Core::TASK_TRANSLATION,
            ExtremeAI_Core::TASK_SENTIMENT_ANALYSIS,
            ExtremeAI_Core::TASK_CLASSIFICATION
        ];
    }
    
    public function execute($task_type, $input, $options = []) {
        if (!$this->supportsTask($task_type)) {
            throw new Exception("Task type {$task_type} not supported by OpenAI provider");
        }
        
        $headers = [
            'Authorization: Bearer ' . $this->config['api_key']
        ];
        
        $data = [
            'model' => $options['model'] ?? $this->config['model'] ?? 'gpt-4o',
            'messages' => [],
            'max_tokens' => $options['max_tokens'] ?? 4096,
            'temperature' => $options['temperature'] ?? 0.7
        ];
        
        // Prepare messages
        $system_prompt = (is_array($input) && isset($input['system'])) ? $input['system'] : $this->getSystemPromptForTask($task_type);
        $user_prompt = is_array($input) ? $input['prompt'] : (string)$input;
        
        if (!empty($system_prompt)) {
            $data['messages'][] = ['role' => 'system', 'content' => $system_prompt];
        }
        $data['messages'][] = ['role' => 'user', 'content' => $user_prompt];
        
        $response = $this->makeRequest('https://api.openai.com/v1/chat/completions', $data, $headers);
        
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception("OpenAI API response format is invalid.");
        }
        
        return [
            'success' => true,
            'content' => $response['choices'][0]['message']['content'] ?? '',
            'usage' => $response['usage'] ?? [],
            'model' => $data['model'],
            'provider' => $this->name
        ];
    }
}

/**
 * GOOGLE GEMINI PROVIDER
 */
class ExtremeAI_GoogleProvider extends ExtremeAI_BaseProvider {
    protected function initialize() {
        $this->name = 'google';
        $this->capabilities = [
            ExtremeAI_Core::TASK_TEXT_GENERATION,
            ExtremeAI_Core::TASK_CONTENT_ANALYSIS,
            ExtremeAI_Core::TASK_IMAGE_ANALYSIS,
            ExtremeAI_Core::TASK_TRANSLATION,
            ExtremeAI_Core::TASK_SENTIMENT_ANALYSIS,
            ExtremeAI_Core::TASK_CLASSIFICATION
        ];
    }
    
    public function execute($task_type, $input, $options = []) {
        if (!$this->supportsTask($task_type)) {
            throw new Exception("Task type {$task_type} not supported by Google provider");
        }
        
        $model = $options['model'] ?? $this->config['default_model'] ?? 'gemini-pro';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $this->config['api_key'];
        
        $prompt = is_array($input) ? $input['prompt'] : (string)$input;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => $options['max_tokens'] ?? 4096,
                'temperature' => $options['temperature'] ?? 0.7
            ]
        ];
        
        $response = $this->makeRequest($url, $data);
        
        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception("Google API response format is invalid.");
        }
        
        return [
            'success' => true,
            'content' => $response['candidates'][0]['content']['parts'][0]['text'],
            'usage' => $response['usageMetadata'] ?? [],
            'model' => $model,
            'provider' => $this->name
        ];
    }
}

/**
 * OLLAMA LOCAL PROVIDER
 */
class ExtremeAI_OllamaProvider extends ExtremeAI_BaseProvider {
    protected function initialize() {
        $this->name = 'ollama';
        $this->capabilities = [
            ExtremeAI_Core::TASK_TEXT_GENERATION,
            ExtremeAI_Core::TASK_CONTENT_ANALYSIS,
            ExtremeAI_Core::TASK_SUMMARIZATION,
            ExtremeAI_Core::TASK_CODE_GENERATION,
            ExtremeAI_Core::TASK_QUESTION_ANSWERING
        ];
    }
    
    public function execute($task_type, $input, $options = []) {
        if (!$this->supportsTask($task_type)) {
            throw new Exception("Task type {$task_type} not supported by Ollama provider");
        }
        
        $base_url = $this->config['base_url'] ?? 'http://localhost:11434';
        $url = $base_url . '/api/generate';
        
        $prompt = is_array($input) ? $input['prompt'] : (string)$input;
        
        $data = [
            'model' => $options['model'] ?? $this->config['default_model'] ?? 'llama2',
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => $options['temperature'] ?? 0.7,
                'num_predict' => $options['max_tokens'] ?? 4096
            ]
        ];
        
        $response = $this->makeRequest($url, $data);
        
        if (!isset($response['response'])) {
            throw new Exception("Ollama API response format is invalid.");
        }
        
        return [
            'success' => true,
            'content' => $response['response'],
            'usage' => ['tokens' => strlen($response['response'] ?? '') / 4], // Rough estimate
            'model' => $data['model'],
            'provider' => $this->name
        ];
    }
}

/************************************************************************/
/* AI AGENT CLASSES - CLEANED VERSION                                 */
/* Autonomous agents for various tasks                                 */
/************************************************************************/

/**
 * BASE AGENT CLASS
 */
abstract class ExtremeAI_BaseAgent {
    protected $ai_core;
    protected $config;
    protected $name;
    protected $schedule;
    protected $last_run;
    
    public function __construct($ai_core, $config) {
        $this->ai_core = $ai_core;
        $this->config = $config;
        $this->schedule = $config['schedule'] ?? 'manual';
    }
    
    abstract public function run($task = null);
    
    public function getName() {
        return $this->name;
    }
    
    public function shouldRun() {
        if ($this->schedule === 'manual') {
            return false;
        }
        
        $now = time();
        $intervals = [
            'minutely' => 60,
            'hourly' => 3600,
            'daily' => 86400,
            'weekly' => 604800
        ];
        
        $interval = $intervals[$this->schedule] ?? 3600;
        
        return ($now - $this->last_run) >= $interval;
    }
    
    protected function logActivity($message) {
        global $prefix, $db;
        
        try {
            $query = "INSERT INTO {$prefix}_extreme_ai_agent_logs 
                      (agent_name, message, created) 
                      VALUES ('" . $db->sql_escapestring($this->name) . "', 
                              '" . $db->sql_escapestring($message) . "', 
                              NOW())";
            $db->sql_query($query);
        } catch (Exception $e) {
            error_log("Agent logging error: " . $e->getMessage());
        }
    }
}

/**
 * CONTENT CURATOR AGENT
 * Automatically curates and improves content
 */
class ContentCuratorAgent extends ExtremeAI_BaseAgent {
    protected $name = 'content_curator';
    
    public function run($task = null) {
        $this->logActivity("Starting content curation run");
        
        // Find content that needs improvement
        $content_to_improve = $this->findContentNeedingImprovement();
        
        $improvements = [];
        foreach ($content_to_improve as $content) {
            try {
                $improved = $this->improveContent($content);
                $improvements[] = $improved;
                
                // Apply improvements if auto-apply is enabled
                if ($this->config['auto_apply'] ?? false) {
                    $this->applyImprovement($content['id'], $improved);
                }
                
            } catch (Exception $e) {
                $this->logActivity("Error improving content ID {$content['id']}: " . $e->getMessage());
            }
        }
        
        $this->logActivity("Completed curation run. Improved " . count($improvements) . " pieces of content.");
        $this->last_run = time();
        
        return $improvements;
    }
    
    private function findContentNeedingImprovement() {
        global $prefix, $db;
        
        try {
            // Find articles with low engagement or old content
            $query = "SELECT s.sid as id, s.title, s.hometext, s.bodytext, s.time as created,
                             COALESCE(c.comments, 0) as comment_count
                      FROM {$prefix}_stories s
                      LEFT JOIN {$prefix}_stats_month c ON s.sid = c.sid
                      WHERE s.time > DATE_SUB(NOW(), INTERVAL 30 DAY)
                      AND (c.comments < 5 OR s.time < DATE_SUB(NOW(), INTERVAL 7 DAY))
                      ORDER BY c.comments ASC, s.time DESC
                      LIMIT 10";
            
            $result = $db->sql_query($query);
            $content = [];
            
            while ($row = $db->sql_fetchrow($result)) {
                $content[] = $row;
            }
            
            $db->sql_freeresult($result);
            return $content;
            
        } catch (Exception $e) {
            $this->logActivity("Error finding content: " . $e->getMessage());
            return [];
        }
    }
    
    private function improveContent($content) {
        $full_content = $content['hometext'] . ' ' . $content['bodytext'];
        
        $improvement_result = $this->ai_core->executeTask(
            ExtremeAI_Core::TASK_TEXT_GENERATION,
            [
                'prompt' => "Improve this article to make it more engaging and informative:\n\nTitle: {$content['title']}\nContent: {$full_content}",
                'system' => 'You are an expert content editor. Improve the article while maintaining its core message and factual accuracy.'
            ]
        );
        
        return [
            'original_id' => $content['id'],
            'original_title' => $content['title'],
            'improved_content' => $improvement_result['content'],
            'improvement_summary' => $this->generateImprovementSummary($full_content, $improvement_result['content'])
        ];
    }
    
    private function generateImprovementSummary($original, $improved) {
        $summary_result = $this->ai_core->executeTask(
            ExtremeAI_Core::TASK_TEXT_GENERATION,
            [
                'prompt' => "Summarize the key improvements made to this content:\n\nOriginal: {$original}\n\nImproved: {$improved}",
                'system' => 'You are an editor summarizing content improvements concisely.'
            ]
        );
        
        return $summary_result['content'];
    }
    
    private function applyImprovement($content_id, $improvement) {
        // This would update the actual content in the database
        // Implementation depends on specific requirements
        $this->logActivity("Applied improvement to content ID: {$content_id}");
    }
}

/**
 * SEO OPTIMIZER AGENT
 * Automatically optimizes content for search engines
 */
class SEOOptimizerAgent extends ExtremeAI_BaseAgent {
    protected $name = 'seo_optimizer';
    
    public function run($task = null) {
        $this->logActivity("Starting SEO optimization run");
        
        // Find content without proper SEO optimization
        $content_to_optimize = $this->findContentNeedingSEO();
        
        $optimizations = [];
        foreach ($content_to_optimize as $content) {
            try {
                $seo_data = $this->optimizeContentSEO($content);
                $optimizations[] = $seo_data;
                
                if ($this->config['auto_apply'] ?? false) {
                    $this->applySEOOptimization($content['id'], $seo_data);
                }
                
            } catch (Exception $e) {
                $this->logActivity("Error optimizing SEO for content ID {$content['id']}: " . $e->getMessage());
            }
        }
        
        $this->logActivity("Completed SEO optimization run. Optimized " . count($optimizations) . " pieces of content.");
        $this->last_run = time();
        
        return $optimizations;
    }
    
    private function findContentNeedingSEO() {
        global $prefix, $db;
        
        try {
            // Find articles without meta descriptions or with poor SEO
            $query = "SELECT s.sid as id, s.title, s.hometext, s.bodytext
                      FROM {$prefix}_stories s
                      LEFT JOIN {$prefix}_extreme_ai_seo_meta m ON s.sid = m.content_id
                      WHERE m.content_id IS NULL OR m.updated < DATE_SUB(NOW(), INTERVAL 30 DAY)
                      ORDER BY s.time DESC
                      LIMIT 20";
            
            $result = $db->sql_query($query);
            $content = [];
            
            while ($row = $db->sql_fetchrow($result)) {
                $content[] = $row;
            }
            
            $db->sql_freeresult($result);
            return $content;
            
        } catch (Exception $e) {
            $this->logActivity("Error finding SEO content: " . $e->getMessage());
            return [];
        }
    }
    
    private function optimizeContentSEO($content) {
        $full_content = $content['hometext'] . ' ' . $content['bodytext'];
        
        $seo_result = $this->ai_core->executeTask(
            ExtremeAI_Core::TASK_TEXT_GENERATION,
            [
                'prompt' => "Create SEO optimization for this content:\n\nTitle: {$content['title']}\nContent: {$full_content}\n\nProvide: 1) Optimized title (60 chars max), 2) Meta description (160 chars max), 3) 10 relevant keywords, 4) URL slug, 5) H1-H3 headings structure",
                'system' => 'You are an SEO expert creating comprehensive search engine optimization.'
            ]
        );
        
        return [
            'content_id' => $content['id'],
            'original_title' => $content['title'],
            'seo_data' => $seo_result['content'],
            'optimization_score' => $this->calculateSEOScore($content, $seo_result['content'])
        ];
    }
    
    private function calculateSEOScore($content, $seo_data) {
        // Simple SEO scoring algorithm
        $score = 0;
        
        // Check title length
        if (strlen($content['title']) <= 60) $score += 20;
        
        // Check content length
        $content_length = strlen($content['hometext'] . ' ' . $content['bodytext']);
        if ($content_length >= 300) $score += 20;
        if ($content_length >= 1000) $score += 10;
        
        // Check for headings (simple check)
        if (strpos($seo_data, 'H1') !== false) $score += 15;
        if (strpos($seo_data, 'H2') !== false) $score += 10;
        
        // Check for keywords
        if (strpos($seo_data, 'keywords') !== false) $score += 15;
        
        // Check for meta description
        if (strpos($seo_data, 'meta description') !== false) $score += 20;
        
        return min(100, $score);
    }
    
    private function applySEOOptimization($content_id, $seo_data) {
        global $prefix, $db;
        
        try {
            // Store SEO metadata
            $seo_json = json_encode($seo_data);
            
            $query = "INSERT INTO {$prefix}_extreme_ai_seo_meta 
                      (content_id, seo_data, score, updated) 
                      VALUES ('" . $db->sql_escapestring($content_id) . "', 
                              '" . $db->sql_escapestring($seo_json) . "', 
                              '" . (int)$seo_data['optimization_score'] . "', 
                              NOW())
                      ON DUPLICATE KEY UPDATE 
                      seo_data = '" . $db->sql_escapestring($seo_json) . "', 
                      score = '" . (int)$seo_data['optimization_score'] . "', 
                      updated = NOW()";
            
            $db->sql_query($query);
            $this->logActivity("Applied SEO optimization to content ID: {$content_id} (Score: {$seo_data['optimization_score']})");
            
        } catch (Exception $e) {
            $this->logActivity("Error applying SEO optimization: " . $e->getMessage());
        }
    }
}

/**
 * COMMUNITY MANAGER AGENT
 * Manages community interactions and engagement
 */
class CommunityManagerAgent extends ExtremeAI_BaseAgent {
    protected $name = 'community_manager';
    
    public function run($task = null) {
        $this->logActivity("Starting community management run");
        
        $actions = [];
        
        // Check for unanswered questions in forums
        $actions['forum_responses'] = $this->respondToForumQuestions();
        
        // Generate discussion topics
        $actions['discussion_topics'] = $this->generateDiscussionTopics();
        
        // Moderate content
        $actions['moderation'] = $this->moderateContent();
        
        $this->logActivity("Completed community management run");
        $this->last_run = time();
        
        return $actions;
    }
    
    private function respondToForumQuestions() {
        global $prefix, $db;
        
        try {
            // Find recent unanswered forum posts
            $query = "SELECT p.post_id, p.post_subject, p.post_text, p.topic_id, 
                             t.topic_title, f.forum_name
                      FROM {$prefix}_bb_posts p
                      JOIN {$prefix}_bb_topics t ON p.topic_id = t.topic_id
                      JOIN {$prefix}_bb_forums f ON t.forum_id = f.forum_id
                      WHERE p.post_time > DATE_SUB(NOW(), INTERVAL 2 HOUR)
                      AND p.post_id NOT IN (
                          SELECT DISTINCT topic_id FROM {$prefix}_bb_posts 
                          WHERE post_time > p.post_time AND topic_id = p.topic_id
                      )
                      ORDER BY p.post_time DESC
                      LIMIT 5";
            
            $result = $db->sql_query($query);
            $responses = [];
            
            while ($row = $db->sql_fetchrow($result)) {
                try {
                    $response_result = $this->ai_core->executeTask(
                        ExtremeAI_Core::TASK_TEXT_GENERATION,
                        [
                            'prompt' => "Generate a helpful forum response to this post:\n\nSubject: {$row['post_subject']}\nContent: {$row['post_text']}\nForum: {$row['forum_name']}",
                            'system' => 'You are a helpful community member providing informative, friendly responses to forum questions. Keep responses concise and actionable.'
                        ]
                    );
                    
                    $responses[] = [
                        'post_id' => $row['post_id'],
                        'topic_id' => $row['topic_id'],
                        'suggested_response' => $response_result['content']
                    ];
                    
                } catch (Exception $e) {
                    $this->logActivity("Error generating forum response for post {$row['post_id']}: " . $e->getMessage());
                }
            }
            
            $db->sql_freeresult($result);
            return $responses;
            
        } catch (Exception $e) {
            $this->logActivity("Error in forum response generation: " . $e->getMessage());
            return [];
        }
    }
    
    private function generateDiscussionTopics() {
        try {
            $topics_result = $this->ai_core->executeTask(
                ExtremeAI_Core::TASK_TEXT_GENERATION,
                [
                    'prompt' => "Generate 5 engaging discussion topics for a community forum. Topics should be current, relevant, and encourage participation.",
                    'system' => 'You are a community manager creating engaging discussion topics.'
                ]
            );
            
            return ['suggested_topics' => $topics_result['content']];
            
        } catch (Exception $e) {
            $this->logActivity("Error generating discussion topics: " . $e->getMessage());
            return ['suggested_topics' => ''];
        }
    }
    
    private function moderateContent() {
        global $prefix, $db;
        
        try {
            // Check recent posts for moderation issues
            $query = "SELECT post_id, post_text, post_subject 
                      FROM {$prefix}_bb_posts 
                      WHERE post_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                      ORDER BY post_time DESC 
                      LIMIT 10";
            
            $result = $db->sql_query($query);
            $moderation_results = [];
            
            while ($row = $db->sql_fetchrow($result)) {
                try {
                    $moderation_result = $this->ai_core->executeTask(
                        ExtremeAI_Core::TASK_CLASSIFICATION,
                        [
                            'prompt' => "Analyze this forum post for potential issues (spam, inappropriate content, etc.):\n\nSubject: {$row['post_subject']}\nContent: {$row['post_text']}\n\nRespond with: SAFE, REVIEW_NEEDED, or VIOLATION and brief explanation.",
                            'system' => 'You are a content moderator analyzing posts for community guideline violations.'
                        ]
                    );
                    
                    $moderation_results[] = [
                        'post_id' => $row['post_id'],
                        'moderation_result' => $moderation_result['content']
                    ];
                    
                } catch (Exception $e) {
                    $this->logActivity("Error moderating post {$row['post_id']}: " . $e->getMessage());
                }
            }
            
            $db->sql_freeresult($result);
            return $moderation_results;
            
        } catch (Exception $e) {
            $this->logActivity("Error in content moderation: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * ANALYTICS REPORTER AGENT
 * Generates insights and reports from site data
 */
class AnalyticsReporterAgent extends ExtremeAI_BaseAgent {
    protected $name = 'analytics_reporter';
    
    public function run($task = null) {
        $this->logActivity("Starting analytics report generation");
        
        $report = [];
        
        // Generate content performance report
        $report['content_performance'] = $this->generateContentReport();
        
        // Generate user engagement report
        $report['user_engagement'] = $this->generateEngagementReport();
        
        // Generate AI usage report
        $report['ai_usage'] = $this->generateAIUsageReport();
        
        // Generate recommendations
        $report['recommendations'] = $this->generateRecommendations($report);
        
        // Store report
        $this->storeReport($report);
        
        $this->logActivity("Completed analytics report generation");
        $this->last_run = time();
        
        return $report;
    }
    
    private function generateContentReport() {
        global $prefix, $db;
        
        try {
            $query = "SELECT 
                        COUNT(*) as total_articles,
                        AVG(LENGTH(CONCAT(hometext, ' ', bodytext))) as avg_length,
                        COUNT(CASE WHEN time > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_articles
                      FROM {$prefix}_stories 
                      WHERE time > DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $result = $db->sql_query($query);
            $stats = $db->sql_fetchrow($result);
            $db->sql_freeresult($result);
            
            // Generate AI analysis of content performance
            $analysis_result = $this->ai_core->executeTask(
                ExtremeAI_Core::TASK_CONTENT_ANALYSIS,
                [
                    'prompt' => "Analyze these content statistics and provide insights:\n\nTotal articles (30 days): {$stats['total_articles']}\nAverage length: {$stats['avg_length']} characters\nRecent articles (7 days): {$stats['recent_articles']}",
                    'system' => 'You are a content analytics expert providing actionable insights.'
                ]
            );
            
            return [
                'statistics' => $stats,
                'analysis' => $analysis_result['content']
            ];
            
        } catch (Exception $e) {
            $this->logActivity("Error generating content report: " . $e->getMessage());
            return ['statistics' => [], 'analysis' => ''];
        }
    }
    
    private function generateEngagementReport() {
        // Implementation for user engagement metrics
        return ['engagement_data' => 'Generated engagement report'];
    }
    
    private function generateAIUsageReport() {
        global $prefix, $db;
        
        try {
            $query = "SELECT 
                        provider, 
                        task_type,
                        COUNT(*) as usage_count,
                        AVG(response_time) as avg_response_time,
                        SUM(cost) as total_cost
                      FROM {$prefix}_extreme_ai_usage 
                      WHERE created > DATE_SUB(NOW(), INTERVAL 7 DAY)
                      GROUP BY provider, task_type
                      ORDER BY usage_count DESC";
            
            $result = $db->sql_query($query);
            $usage_data = [];
            
            while ($row = $db->sql_fetchrow($result)) {
                $usage_data[] = $row;
            }
            
            $db->sql_freeresult($result);
            return ['usage_statistics' => $usage_data];
            
        } catch (Exception $e) {
            $this->logActivity("Error generating AI usage report: " . $e->getMessage());
            return ['usage_statistics' => []];
        }
    }
    
    private function generateRecommendations($report_data) {
        try {
            $recommendations_result = $this->ai_core->executeTask(
                ExtremeAI_Core::TASK_TEXT_GENERATION,
                [
                    'prompt' => "Based on this site analytics data, provide 5 specific recommendations for improvement:\n\n" . json_encode($report_data, JSON_PRETTY_PRINT),
                    'system' => 'You are a website optimization consultant providing specific, actionable recommendations.'
                ]
            );
            
            return $recommendations_result['content'];
            
        } catch (Exception $e) {
            $this->logActivity("Error generating recommendations: " . $e->getMessage());
            return '';
        }
    }
    
    private function storeReport($report) {
        global $prefix, $db;
        
        try {
            $report_json = json_encode($report);
            
            $query = "INSERT INTO {$prefix}_extreme_ai_reports 
                      (report_type, report_data, created) 
                      VALUES ('weekly_analytics', 
                              '" . $db->sql_escapestring($report_json) . "', 
                              NOW())";
            $db->sql_query($query);
            
        } catch (Exception $e) {
            $this->logActivity("Error storing report: " . $e->getMessage());
        }
    }
}
?>