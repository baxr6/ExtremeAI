<?php
/**
 * ExtremeAI Installation Script
 *
 * Complete installation and setup system for ExtremeAI.
 * This script creates all necessary database tables, configuration files,
 * and initializes the system with default settings.
 *
 * @category Extreme_AI
 * @package  Evo-Extreme
 * @author   Deano Welch <deano.welch@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/baxr6/
 * @since    2.0.0
 * @requires PHP 8.4 or higher
 */

define('EXTREME_AI_INSTALLER', true);
define('NUKE_FILE', true);

// Include mainfile for database connection and core functions
if (file_exists('mainfile.php')) {
    require_once 'mainfile.php';
} elseif (file_exists('../mainfile.php')) {
    require_once '../mainfile.php';
} else {
    die('Error: Cannot find mainfile.php. Please ensure this script is in the correct directory.');
}

// Ensure we have admin access
if (!defined('ADMIN_FILE') && !defined('EXTREME_AI_INSTALLER')) {
    session_start();
    if (!is_admin()) {
        die('Access Denied: Admin privileges required for installation.');
    }
}

// Installation configuration
define('EXTREME_AI_VERSION', '2.0.0');
define('EXTREME_AI_BUILD', '20241204');
define('EXTREME_AI_MIN_PHP_VERSION', '8.0');
define('EXTREME_AI_MIN_MYSQL_VERSION', '5.7');

/**
 * ExtremeAI Installation Class
 */
class ExtremeAI_Installer
{
    private $db;
    private $prefix;
    private $errors = [];
    private $warnings = [];
    private $success_messages = [];
    private $step = 1;
    
    public function __construct()
    {
        global $db, $prefix;
        $this->db = $db;
        $this->prefix = $prefix;
        $this->step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
    }
    
    /**
     * Run the installation process
     */
    public function run()
    {
        $this->checkRequirements();
        
        if ($_POST && isset($_POST['action'])) {
            $this->handleAction($_POST['action']);
        }
        
        $this->renderPage();
    }
    
    /**
     * Check system requirements
     */
    private function checkRequirements()
    {
        // PHP Version Check
        if (version_compare(PHP_VERSION, EXTREME_AI_MIN_PHP_VERSION, '<')) {
            $this->errors[] = "PHP version " . EXTREME_AI_MIN_PHP_VERSION . " or higher is required. Current version: " . PHP_VERSION;
        }
        
        // MySQL Version Check
        try {
            $version_result = $this->db->sql_query("SELECT VERSION() as version");
            $version_row = $this->db->sql_fetchrow($version_result);
            $mysql_version = $version_row['version'];
            
            if (version_compare($mysql_version, EXTREME_AI_MIN_MYSQL_VERSION, '<')) {
                $this->errors[] = "MySQL version " . EXTREME_AI_MIN_MYSQL_VERSION . " or higher is required. Current version: " . $mysql_version;
            }
            $this->db->sql_freeresult($version_result);
        } catch (Exception $e) {
            $this->errors[] = "Cannot determine MySQL version: " . $e->getMessage();
        }
        
        // Required Extensions
        $required_extensions = ['json', 'curl', 'mbstring', 'openssl'];
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                $this->errors[] = "Required PHP extension missing: " . $extension;
            }
        }
        
        // Directory Permissions
        $directories = [
            'includes/extreme_ai',
            'includes/extreme_ai/classes',
            'includes/extreme_ai/templates',
            'includes/extreme_ai/css',
            'includes/extreme_ai/js',
            'includes/extreme_ai/language'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    $this->errors[] = "Cannot create directory: " . $dir;
                }
            } elseif (!is_writable($dir)) {
                $this->warnings[] = "Directory not writable: " . $dir;
            }
        }
    }
    
    /**
     * Handle form actions
     */
    private function handleAction($action)
    {
        switch ($action) {
            case 'install_database':
                $this->installDatabase();
                break;
            case 'install_config':
                $this->installConfiguration();
                break;
            case 'install_sample_data':
                $this->installSampleData();
                break;
            case 'complete_installation':
                $this->completeInstallation();
                break;
            case 'uninstall':
                $this->uninstallSystem();
                break;
        }
    }
    
    /**
     * Install database tables
     */
    private function installDatabase()
    {
        try {
            // Create main configuration table
            $this->createConfigTable();
            $this->success_messages[] = "Configuration table created";
            
            // Create providers table
            $this->createProvidersTable();
            $this->success_messages[] = "Providers table created";
            
            // Create usage tracking table
            $this->createUsageTable();
            $this->success_messages[] = "Usage tracking table created";
            
            // Create errors/logs table
            $this->createErrorsTable();
            $this->success_messages[] = "Errors/logs table created";
            
            // Create tasks queue table
            $this->createTasksTable();
            $this->success_messages[] = "Tasks queue table created";
            
            // Create analytics table
            $this->createAnalyticsTable();
            $this->success_messages[] = "Analytics table created";
            
            // Create workflows table
            $this->createWorkflowsTable();
            $this->success_messages[] = "Workflows table created";
            
            // Create agents table
            $this->createAgentsTable();
            $this->success_messages[] = "Agents table created";
            
            $this->success_messages[] = "All database tables created successfully!";
            
            // Also install default configuration immediately after table creation
            $this->installConfiguration();
            
        } catch (Exception $e) {
            $this->errors[] = "Database installation failed: " . $e->getMessage();
        }
    }
    
    /**
     * Create configuration table
     */
    private function createConfigTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}_extreme_ai_config` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `provider` varchar(50) NOT NULL DEFAULT 'system',
            `key` varchar(100) NOT NULL,
            `value` text,
            `description` varchar(255) DEFAULT NULL,
            `type` enum('string','integer','boolean','json','array') DEFAULT 'string',
            `created` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `provider_key` (`provider`,`key`),
            INDEX `idx_provider` (`provider`),
            INDEX `idx_key` (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->sql_query($sql);
    }
    
    /**
     * Create providers table
     */
    private function createProvidersTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}_extreme_ai_providers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL,
            `display_name` varchar(100) NOT NULL,
            `api_key` varchar(255) DEFAULT NULL,
            `api_endpoint` varchar(255) NOT NULL,
            `model` varchar(100) DEFAULT NULL,
            `enabled` tinyint(1) DEFAULT 0,
            `priority` int(11) DEFAULT 100,
            `rate_limit` int(11) DEFAULT 1000,
            `timeout` int(11) DEFAULT 30,
            `settings` json DEFAULT NULL,
            `last_used` datetime DEFAULT NULL,
            `total_requests` bigint(20) DEFAULT 0,
            `total_cost` decimal(10,4) DEFAULT 0.0000,
            `success_rate` decimal(5,2) DEFAULT 100.00,
            `avg_response_time` decimal(8,3) DEFAULT 0.000,
            `created` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`),
            INDEX `idx_enabled` (`enabled`),
            INDEX `idx_priority` (`priority`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->sql_query($sql);
    }
    
    /**
     * Create usage tracking table
     */
    private function createUsageTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}_extreme_ai_usage` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `provider` varchar(50) NOT NULL,
            `model` varchar(100) DEFAULT NULL,
            `task_type` varchar(50) NOT NULL,
            `user_id` int(11) DEFAULT NULL,
            `session_id` varchar(64) DEFAULT NULL,
            `request_data` json DEFAULT NULL,
            `response_data` json DEFAULT NULL,
            `tokens_used` int(11) DEFAULT 0,
            `cost` decimal(8,4) DEFAULT 0.0000,
            `response_time` decimal(8,3) DEFAULT 0.000,
            `status` enum('success','error','timeout','cancelled') DEFAULT 'success',
            `error_message` text DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` varchar(255) DEFAULT NULL,
            `created` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_provider` (`provider`),
            INDEX `idx_task_type` (`task_type`),
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_created` (`created`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->sql_query($sql);
    }
    
    /**
     * Create errors/logs table
     */
    private function createErrorsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}_extreme_ai_errors` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `level` enum('debug','info','warning','error','critical') DEFAULT 'error',
            `message` text NOT NULL,
            `context` json DEFAULT NULL,
            `provider` varchar(50) DEFAULT NULL,
            `task_type` varchar(50) DEFAULT NULL,
            `user_id` int(11) DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `file` varchar(255) DEFAULT NULL,
            `line` int(11) DEFAULT NULL,
            `trace` longtext DEFAULT NULL,
            `created` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_level` (`level`),
            INDEX `idx_provider` (`provider`),
            INDEX `idx_created` (`created`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->sql_query($sql);
    }
    
    /**
     * Create tasks queue table
     */
    private function createTasksTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}_extreme_ai_tasks` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `uuid` varchar(36) NOT NULL,
            `type` varchar(50) NOT NULL,
            `priority` int(11) DEFAULT 100,
            `status` enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
            `provider` varchar(50) DEFAULT NULL,
            `model` varchar(100) DEFAULT NULL,
            `input_data` json NOT NULL,
            `output_data` json DEFAULT NULL,
            `options` json DEFAULT NULL,
            `attempts` int(11) DEFAULT 0,
            `max_attempts` int(11) DEFAULT 3,
            `user_id` int(11) DEFAULT NULL,
            `progress` decimal(5,2) DEFAULT 0.00,
            `estimated_cost` decimal(8,4) DEFAULT 0.0000,
            `actual_cost` decimal(8,4) DEFAULT 0.0000,
            `started_at` datetime DEFAULT NULL,
            `completed_at` datetime DEFAULT NULL,
            `created` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uuid` (`uuid`),
            INDEX `idx_status` (`status`),
            INDEX `idx_priority` (`priority`),
            INDEX `idx_type` (`type`),
            INDEX `idx_provider` (`provider`),
            INDEX `idx_created` (`created`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->sql_query($sql);
    }
    
    /**
     * Create analytics table
     */
    private function createAnalyticsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}_extreme_ai_analytics` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `date` date NOT NULL,
            `hour` tinyint(2) DEFAULT NULL,
            `provider` varchar(50) NOT NULL,
            `task_type` varchar(50) NOT NULL,
            `requests_count` int(11) DEFAULT 0,
            `success_count` int(11) DEFAULT 0,
            `error_count` int(11) DEFAULT 0,
            `total_tokens` bigint(20) DEFAULT 0,
            `total_cost` decimal(10,4) DEFAULT 0.0000,
            `avg_response_time` decimal(8,3) DEFAULT 0.000,
            `unique_users` int(11) DEFAULT 0,
            `created` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `analytics_unique` (`date`,`hour`,`provider`,`task_type`),
            INDEX `idx_date` (`date`),
            INDEX `idx_provider` (`provider`),
            INDEX `idx_task_type` (`task_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->sql_query($sql);
    }
    
    /**
     * Create workflows table
     */
    private function createWorkflowsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}_extreme_ai_workflows` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `trigger_type` varchar(50) NOT NULL,
            `trigger_config` json DEFAULT NULL,
            `steps` json NOT NULL,
            `enabled` tinyint(1) DEFAULT 1,
            `created_by` int(11) NOT NULL,
            `total_runs` int(11) DEFAULT 0,
            `successful_runs` int(11) DEFAULT 0,
            `last_run` datetime DEFAULT NULL,
            `created` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_enabled` (`enabled`),
            INDEX `idx_trigger_type` (`trigger_type`),
            INDEX `idx_created_by` (`created_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->sql_query($sql);
    }
    
    /**
     * Create agents table
     */
    private function createAgentsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}_extreme_ai_agents` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `type` varchar(50) NOT NULL,
            `personality` text DEFAULT NULL,
            `instructions` text DEFAULT NULL,
            `capabilities` json DEFAULT NULL,
            `preferred_provider` varchar(50) DEFAULT NULL,
            `preferred_model` varchar(100) DEFAULT NULL,
            `settings` json DEFAULT NULL,
            `enabled` tinyint(1) DEFAULT 1,
            `created_by` int(11) NOT NULL,
            `total_interactions` int(11) DEFAULT 0,
            `last_used` datetime DEFAULT NULL,
            `created` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`),
            INDEX `idx_type` (`type`),
            INDEX `idx_enabled` (`enabled`),
            INDEX `idx_created_by` (`created_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->sql_query($sql);
    }
    
    /**
     * Install default configuration
     */
    private function installConfiguration()
    {
        try {
            // First, verify the table exists
            $table_check = $this->db->sql_query("SHOW TABLES LIKE '{$this->prefix}_extreme_ai_config'");
            $table_exists = $this->db->sql_fetchrow($table_check);
            $this->db->sql_freeresult($table_check);
            
            if (!$table_exists) {
                throw new Exception("Configuration table {$this->prefix}_extreme_ai_config does not exist. Tables must be created first.");
            }
            
            $this->success_messages[] = "Configuration table {$this->prefix}_extreme_ai_config verified.";
            
            // System configuration
            $system_config = [
                'extreme_ai_version' => EXTREME_AI_VERSION,
                'extreme_ai_build' => EXTREME_AI_BUILD,
                'extreme_ai_debug' => 'false',
                'extreme_ai_cache_enabled' => 'true',
                'extreme_ai_cache_ttl' => '3600',
                'extreme_ai_max_tokens' => '4096',
                'extreme_ai_rate_limit' => '1000',
                'extreme_ai_default_timeout' => '30',
                'extreme_ai_max_concurrent_requests' => '10',
                'extreme_ai_auto_cleanup_days' => '30',
                'extreme_ai_log_level' => 'error',
                'extreme_ai_installation_date' => date('Y-m-d H:i:s'),
                'extreme_ai_installation_id' => uniqid('eai_', true)
            ];
            
            foreach ($system_config as $key => $value) {
                $this->insertConfig('system', $key, $value);
                $this->success_messages[] = "Inserted config: {$key} = {$value}";
            }
            
            $this->success_messages[] = "Default configuration installed successfully!";
            
            // Only set step if not called automatically from installDatabase
            if ($this->step < 3) {
                $this->step = 3;
            }
            
        } catch (Exception $e) {
            $this->errors[] = "Configuration installation failed: " . $e->getMessage();
        }
    }
    
    /**
     * Install sample data
     */
    private function installSampleData()
    {
        try {
            // Sample providers
            $providers = [
                [
                    'name' => 'openai',
                    'display_name' => 'OpenAI GPT',
                    'api_endpoint' => 'https://api.openai.com/v1/chat/completions',
                    'model' => 'gpt-4o',
                    'enabled' => 0,
                    'priority' => 100,
                    'settings' => json_encode(['temperature' => 0.7, 'max_tokens' => 4096])
                ],
                [
                    'name' => 'anthropic',
                    'display_name' => 'Anthropic Claude',
                    'api_endpoint' => 'https://api.anthropic.com/v1/messages',
                    'model' => 'claude-3-haiku-20240307',
                    'enabled' => 0,
                    'priority' => 90,
                    'settings' => json_encode(['temperature' => 0.7, 'max_tokens' => 4096])
                ],
                [
                    'name' => 'google',
                    'display_name' => 'Google Gemini',
                    'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/',
                    'model' => 'gemini-pro',
                    'enabled' => 0,
                    'priority' => 80,
                    'settings' => json_encode(['temperature' => 0.7])
                ],
                [
                    'name' => 'ollama',
                    'display_name' => 'Ollama Local',
                    'api_endpoint' => 'http://localhost:11434',
                    'model' => 'llama2',
                    'enabled' => 0,
                    'priority' => 70,
                    'settings' => json_encode(['temperature' => 0.7])
                ]
            ];
            
            foreach ($providers as $provider) {
                $this->insertProvider($provider);
            }
            
            // Sample agents
            $agents = [
                [
                    'name' => 'Content Assistant',
                    'description' => 'AI assistant specialized in content creation and editing',
                    'type' => 'content_creator',
                    'personality' => 'Professional, creative, and detail-oriented',
                    'instructions' => 'Help users create high-quality content including articles, blog posts, and marketing materials.',
                    'capabilities' => json_encode(['writing', 'editing', 'proofreading', 'seo_optimization']),
                    'created_by' => 1
                ],
                [
                    'name' => 'Code Helper',
                    'description' => 'Programming assistant for development tasks',
                    'type' => 'developer',
                    'personality' => 'Technical, precise, and helpful',
                    'instructions' => 'Assist with coding tasks, debugging, code reviews, and technical documentation.',
                    'capabilities' => json_encode(['coding', 'debugging', 'code_review', 'documentation']),
                    'created_by' => 1
                ],
                [
                    'name' => 'Customer Support',
                    'description' => 'Customer service AI for handling inquiries',
                    'type' => 'support',
                    'personality' => 'Friendly, patient, and solution-oriented',
                    'instructions' => 'Provide excellent customer support by answering questions and resolving issues.',
                    'capabilities' => json_encode(['customer_service', 'troubleshooting', 'faq', 'escalation']),
                    'created_by' => 1
                ]
            ];
            
            foreach ($agents as $agent) {
                $this->insertAgent($agent);
            }
            
            $this->success_messages[] = "Sample data installed successfully!";
            $this->step = 4;
            
        } catch (Exception $e) {
            $this->errors[] = "Sample data installation failed: " . $e->getMessage();
        }
    }
    
    /**
     * Complete installation
     */
    private function completeInstallation()
    {
        try {
            // Create installation marker
            $install_data = [
                'version' => EXTREME_AI_VERSION,
                'build' => EXTREME_AI_BUILD,
                'installed_at' => date('Y-m-d H:i:s'),
                'installer_version' => '1.0.0'
            ];
            
            file_put_contents(
                'includes/extreme_ai/.installed', 
                json_encode($install_data, JSON_PRETTY_PRINT)
            );
            
            // Update system config
            $this->insertConfig('system', 'extreme_ai_installed', 'true');
            $this->insertConfig('system', 'extreme_ai_installation_completed', date('Y-m-d H:i:s'));
            
            $this->success_messages[] = "ExtremeAI installation completed successfully!";
            $this->success_messages[] = "You can now access the admin panel to configure your AI providers.";
            $this->step = 5;
            
        } catch (Exception $e) {
            $this->errors[] = "Installation completion failed: " . $e->getMessage();
        }
    }
    
    /**
     * Uninstall system
     */
    private function uninstallSystem()
    {
        if (!isset($_POST['confirm_uninstall']) || $_POST['confirm_uninstall'] !== 'DELETE_ALL_DATA') {
            $this->errors[] = "Uninstall confirmation required. Type 'DELETE_ALL_DATA' to confirm.";
            return;
        }
        
        try {
            // Drop all tables
            $tables = [
                'extreme_ai_config',
                'extreme_ai_providers', 
                'extreme_ai_usage',
                'extreme_ai_errors',
                'extreme_ai_tasks',
                'extreme_ai_analytics',
                'extreme_ai_workflows',
                'extreme_ai_agents'
            ];
            
            foreach ($tables as $table) {
                $this->db->sql_query("DROP TABLE IF EXISTS `{$this->prefix}_{$table}`");
            }
            
            // Remove installation marker
            if (file_exists('includes/extreme_ai/.installed')) {
                unlink('includes/extreme_ai/.installed');
            }
            
            $this->success_messages[] = "ExtremeAI has been completely uninstalled.";
            $this->success_messages[] = "All data has been removed from the database.";
            
        } catch (Exception $e) {
            $this->errors[] = "Uninstall failed: " . $e->getMessage();
        }
    }
    
    /**
     * Helper functions
     */
    private function insertConfig($provider, $key, $value, $description = null, $type = 'string')
    {
        $provider = addslashes($provider);
        $key = addslashes($key);
        $value = addslashes($value);
        $description = $description ? addslashes($description) : 'NULL';
        $type = addslashes($type);
        
        $sql = "INSERT INTO `{$this->prefix}_extreme_ai_config` 
                (`provider`, `key`, `value`, `description`, `type`) 
                VALUES ('$provider', '$key', '$value', " . ($description === 'NULL' ? 'NULL' : "'$description'") . ", '$type')
                ON DUPLICATE KEY UPDATE 
                `value` = '$value', 
                `description` = " . ($description === 'NULL' ? 'NULL' : "'$description'") . ",
                `type` = '$type'";
        
        $this->db->sql_query($sql);
    }
    
    private function insertProvider($data)
    {
        $name = addslashes($data['name']);
        $display_name = addslashes($data['display_name']);
        $api_endpoint = addslashes($data['api_endpoint']);
        $model = addslashes($data['model']);
        $enabled = (int)$data['enabled'];
        $priority = (int)$data['priority'];
        $settings = addslashes($data['settings']);
        
        $sql = "INSERT IGNORE INTO `{$this->prefix}_extreme_ai_providers` 
                (`name`, `display_name`, `api_endpoint`, `model`, `enabled`, `priority`, `settings`) 
                VALUES ('$name', '$display_name', '$api_endpoint', '$model', $enabled, $priority, '$settings')";
        
        $this->db->sql_query($sql);
    }
    
    private function insertAgent($data)
    {
        $name = addslashes($data['name']);
        $description = addslashes($data['description']);
        $type = addslashes($data['type']);
        $personality = addslashes($data['personality']);
        $instructions = addslashes($data['instructions']);
        $capabilities = addslashes($data['capabilities']);
        $created_by = (int)$data['created_by'];
        
        $sql = "INSERT IGNORE INTO `{$this->prefix}_extreme_ai_agents` 
                (`name`, `description`, `type`, `personality`, `instructions`, `capabilities`, `created_by`) 
                VALUES ('$name', '$description', '$type', '$personality', '$instructions', '$capabilities', $created_by)";
        
        $this->db->sql_query($sql);
    }
    
    /**
     * Check if system is already installed
     */
    private function isInstalled()
    {
        return file_exists('includes/extreme_ai/.installed');
    }
    
    /**
     * Render the installation page
     */
    private function renderPage()
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>ExtremeAI Installation</title>
            <style>
                <?php $this->renderCSS(); ?>
            </style>
        </head>
        <body>
            <div class="installer-container">
                <header class="installer-header">
                    <div class="logo">
                        <i class="fas fa-brain"></i>
                        <h1>ExtremeAI</h1>
                    </div>
                    <div class="version">Version <?php echo EXTREME_AI_VERSION; ?></div>
                </header>
                
                <div class="installer-content">
                    <?php $this->renderStep(); ?>
                </div>
                
                <footer class="installer-footer">
                    <p>&copy; <?php echo date('Y'); ?> ExtremeAI. All rights reserved.</p>
                </footer>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Render current installation step
     */
    private function renderStep()
    {
        if ($this->isInstalled() && $this->step < 5) {
            $this->renderAlreadyInstalled();
            return;
        }
        
        switch ($this->step) {
            case 1:
                $this->renderStepRequirements();
                break;
            case 2:
                $this->renderStepConfiguration();
                break;
            case 3:
                $this->renderStepSampleData();
                break;
            case 4:
                $this->renderStepCompletion();
                break;
            case 5:
                $this->renderStepFinished();
                break;
            default:
                $this->renderStepRequirements();
        }
    }
    
    /**
     * Render requirements check step
     */
    private function renderStepRequirements()
    {
        ?>
        <div class="step-container">
            <div class="step-header">
                <h2>System Requirements Check</h2>
                <div class="step-indicator">Step 1 of 4</div>
            </div>
            
            <?php $this->renderMessages(); ?>
            
            <div class="requirements-grid">
                <div class="requirement-item">
                    <h3>PHP Version</h3>
                    <div class="requirement-status <?php echo version_compare(PHP_VERSION, EXTREME_AI_MIN_PHP_VERSION, '>=') ? 'success' : 'error'; ?>">
                        Current: <?php echo PHP_VERSION; ?> | Required: <?php echo EXTREME_AI_MIN_PHP_VERSION; ?>+
                    </div>
                </div>
                
                <div class="requirement-item">
                    <h3>Database Connection</h3>
                    <div class="requirement-status <?php echo $this->db ? 'success' : 'error'; ?>">
                        <?php echo $this->db ? 'Connected' : 'Failed'; ?>
                    </div>
                </div>
                
                <div class="requirement-item">
                    <h3>Required Extensions</h3>
                    <?php
                    $extensions = ['json', 'curl', 'mbstring', 'openssl'];
                    foreach ($extensions as $ext) {
                        $loaded = extension_loaded($ext);
                        echo "<div class='requirement-status " . ($loaded ? 'success' : 'error') . "'>";
                        echo $ext . ": " . ($loaded ? 'Loaded' : 'Missing');
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
            
            <?php if (empty($this->errors)): ?>
            <form method="post" class="step-form">
                <input type="hidden" name="action" value="install_database">
                <button type="submit" class="btn btn-primary">
                    Install Database Tables
                </button>
            </form>
            <?php else: ?>
            <div class="error-message">
                Please resolve the above errors before continuing.
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render configuration step
     */
    private function renderStepConfiguration()
    {
        ?>
        <div class="step-container">
            <div class="step-header">
                <h2>System Configuration</h2>
                <div class="step-indicator">Step 2 of 4</div>
            </div>
            
            <?php $this->renderMessages(); ?>
            
            <p>Database tables have been created successfully. Now installing default configuration...</p>
            
            <form method="post" class="step-form">
                <input type="hidden" name="action" value="install_config">
                <button type="submit" class="btn btn-primary">
                    Install Configuration
                </button>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render sample data step
     */
    private function renderStepSampleData()
    {
        ?>
        <div class="step-container">
            <div class="step-header">
                <h2>Sample Data Installation</h2>
                <div class="step-indicator">Step 3 of 4</div>
            </div>
            
            <?php $this->renderMessages(); ?>
            
            <p>Configuration installed successfully. You can now install sample providers and agents to get started quickly.</p>
            
            <div class="sample-data-info">
                <h3>What will be installed:</h3>
                <ul>
                    <li>4 Pre-configured AI providers (OpenAI, Anthropic, Google, Ollama)</li>
                    <li>3 Sample AI agents (Content Assistant, Code Helper, Customer Support)</li>
                    <li>Default workflow templates</li>
                </ul>
            </div>
            
            <form method="post" class="step-form">
                <input type="hidden" name="action" value="install_sample_data">
                <button type="submit" class="btn btn-primary">
                    Install Sample Data
                </button>
                <button type="submit" name="action" value="complete_installation" class="btn btn-secondary">
                    Skip Sample Data
                </button>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render completion step
     */
    private function renderStepCompletion()
    {
        ?>
        <div class="step-container">
            <div class="step-header">
                <h2>Complete Installation</h2>
                <div class="step-indicator">Step 4 of 4</div>
            </div>
            
            <?php $this->renderMessages(); ?>
            
            <p>Sample data installed successfully. Ready to complete the installation.</p>
            
            <form method="post" class="step-form">
                <input type="hidden" name="action" value="complete_installation">
                <button type="submit" class="btn btn-success">
                    Complete Installation
                </button>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render finished step
     */
    private function renderStepFinished()
    {
        ?>
        <div class="step-container">
            <div class="step-header">
                <h2>Installation Complete!</h2>
                <div class="step-indicator">Finished</div>
            </div>
            
            <?php $this->renderMessages(); ?>
            
            <div class="completion-info">
                <h3>Next Steps:</h3>
                <ol>
                    <li>Configure your AI providers in the admin panel</li>
                    <li>Add your API keys for the providers you want to use</li>
                    <li>Test the connections to ensure everything is working</li>
                    <li>Start using ExtremeAI in your applications!</li>
                </ol>
                
                <div class="action-buttons">
                    <a href="admin.php?op=extremeai_dashboard" class="btn btn-primary">
                        Go to Admin Panel
                    </a>
                    <a href="admin.php?op=extremeai_providers" class="btn btn-secondary">
                        Configure Providers
                    </a>
                </div>
            </div>
            
            <!-- Uninstall Section -->
            <div class="uninstall-section">
                <h3>Uninstall ExtremeAI</h3>
                <p class="warning">This will completely remove ExtremeAI and all its data from your system.</p>
                
                <form method="post" class="uninstall-form" onsubmit="return confirmUninstall()">
                    <input type="hidden" name="action" value="uninstall">
                    <label>
                        Type <strong>DELETE_ALL_DATA</strong> to confirm:
                        <input type="text" name="confirm_uninstall" placeholder="DELETE_ALL_DATA">
                    </label>
                    <button type="submit" class="btn btn-danger">
                        Uninstall ExtremeAI
                    </button>
                </form>
            </div>
        </div>
        
        <script>
        function confirmUninstall() {
            return confirm('Are you absolutely sure you want to uninstall ExtremeAI? This action cannot be undone!');
        }
        </script>
        <?php
    }
    
    /**
     * Render already installed message
     */
    private function renderAlreadyInstalled()
    {
        ?>
        <div class="step-container">
            <div class="step-header">
                <h2>Already Installed</h2>
            </div>
            
            <div class="info-message">
                ExtremeAI is already installed on this system.
            </div>
            
            <div class="action-buttons">
                <a href="admin.php?op=extremeai_dashboard" class="btn btn-primary">
                    Go to Admin Panel
                </a>
                <a href="?step=5" class="btn btn-secondary">
                    Manage Installation
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render messages
     */
    private function renderMessages()
    {
        foreach ($this->errors as $error) {
            echo "<div class='message error'><i class='fas fa-exclamation-circle'></i> {$error}</div>";
        }
        
        foreach ($this->warnings as $warning) {
            echo "<div class='message warning'><i class='fas fa-exclamation-triangle'></i> {$warning}</div>";
        }
        
        foreach ($this->success_messages as $success) {
            echo "<div class='message success'><i class='fas fa-check-circle'></i> {$success}</div>";
        }
    }
    
    /**
     * Render CSS styles
     */
    private function renderCSS()
    {
        ?>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .installer-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .installer-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo i {
            font-size: 2.5em;
        }
        
        .logo h1 {
            font-size: 2em;
            font-weight: 300;
        }
        
        .version {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
        }
        
        .installer-content {
            padding: 40px;
        }
        
        .step-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .step-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .step-header h2 {
            color: #2c3e50;
            font-size: 1.8em;
            font-weight: 400;
        }
        
        .step-indicator {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #b8daff;
        }
        
        .requirements-grid {
            display: grid;
            gap: 20px;
            margin: 30px 0;
        }
        
        .requirement-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .requirement-item h3 {
            margin-bottom: 10px;
            color: #495057;
        }
        
        .requirement-status {
            font-weight: 500;
            padding: 5px 0;
        }
        
        .requirement-status.success {
            color: #28a745;
        }
        
        .requirement-status.error {
            color: #dc3545;
        }
        
        .step-form {
            text-align: center;
            margin: 30px 0;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            cursor: pointer;
            margin: 0 10px;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #1e7e34;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .sample-data-info {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .sample-data-info h3 {
            margin-bottom: 15px;
            color: #0c5460;
        }
        
        .sample-data-info ul {
            list-style-position: inside;
            color: #0c5460;
        }
        
        .completion-info {
            background: #d4edda;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .completion-info h3 {
            margin-bottom: 15px;
            color: #155724;
        }
        
        .completion-info ol {
            margin: 15px 0;
            padding-left: 20px;
            color: #155724;
        }
        
        .action-buttons {
            margin: 25px 0;
            text-align: center;
        }
        
        .uninstall-section {
            background: #f8d7da;
            padding: 20px;
            border-radius: 8px;
            margin-top: 40px;
            border: 1px solid #f5c6cb;
        }
        
        .uninstall-section h3 {
            color: #721c24;
            margin-bottom: 10px;
        }
        
        .uninstall-section .warning {
            color: #721c24;
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .uninstall-form label {
            display: block;
            margin-bottom: 15px;
            color: #721c24;
        }
        
        .uninstall-form input {
            display: block;
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        
        .installer-footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .installer-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .installer-content {
                padding: 20px;
            }
            
            .step-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .btn {
                display: block;
                margin: 10px 0;
            }
        }
        <?php
    }
}

// Run the installer
$installer = new ExtremeAI_Installer();
$installer->run();
?>