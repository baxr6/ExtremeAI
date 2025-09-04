<?php
/**
 * ExtremeAI Database Migration and Management Class
 *
 * Handles database schema updates, migrations, and version management.
 *
 * @category Extreme_AI
 * @package  Evo-Extreme
 * @author   Deano Welch <deano.welch@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/baxr6/
 * @since    2.0.0
 * @requires PHP 8.0 or higher
 */

defined('NUKE_EVO') || exit;

class ExtremeAI_Database
{
    private $db;
    private $prefix;
    private $current_version;
    private $target_version;
    private $migrations = [];
    
    public function __construct()
    {
        global $db, $prefix;
        $this->db = $db;
        $this->prefix = $prefix;
        $this->current_version = $this->getCurrentVersion();
        $this->target_version = EXTREME_AI_VERSION ?? '2.0.0';
        $this->loadMigrations();
    }
    
    /**
     * Get current database version
     */
    public function getCurrentVersion()
    {
        try {
            $result = $this->db->sql_query(
                "SELECT `value` FROM `{$this->prefix}_extreme_ai_config` 
                 WHERE `provider` = 'system' AND `key` = 'extreme_ai_version'"
            );
            
            if ($row = $this->db->sql_fetchrow($result)) {
                $this->db->sql_freeresult($result);
                return $row['value'];
            }
            
            $this->db->sql_freeresult($result);
            return '0.0.0';
        } catch (Exception $e) {
            return '0.0.0';
        }
    }
    
    /**
     * Check if database needs updates
     */
    public function needsUpdate()
    {
        return version_compare($this->current_version, $this->target_version, '<');
    }
    
    /**
     * Get list of pending migrations
     */
    public function getPendingMigrations()
    {
        $pending = [];
        
        foreach ($this->migrations as $version => $migration) {
            if (version_compare($this->current_version, $version, '<')) {
                $pending[$version] = $migration;
            }
        }
        
        ksort($pending, SORT_VERSION_COMPARE);
        return $pending;
    }
    
    /**
     * Run database migrations
     */
    public function migrate()
    {
        $pending = $this->getPendingMigrations();
        $results = [];
        
        foreach ($pending as $version => $migration) {
            try {
                $this->db->sql_query("START TRANSACTION");
                
                // Run migration
                if (isset($migration['up'])) {
                    foreach ($migration['up'] as $sql) {
                        $this->db->sql_query($sql);
                    }
                }
                
                // Update version
                $this->updateVersion($version);
                
                $this->db->sql_query("COMMIT");
                
                $results[$version] = [
                    'success' => true,
                    'message' => $migration['description'] ?? "Migration to version {$version}"
                ];
                
                $this->current_version = $version;
                
            } catch (Exception $e) {
                $this->db->sql_query("ROLLBACK");
                
                $results[$version] = [
                    'success' => false,
                    'message' => "Migration failed: " . $e->getMessage()
                ];
                
                // Stop on first failure
                break;
            }
        }
        
        return $results;
    }
    
    /**
     * Rollback to previous version
     */
    public function rollback($target_version)
    {
        $migrations = array_reverse($this->migrations, true);
        $results = [];
        
        foreach ($migrations as $version => $migration) {
            if (version_compare($version, $this->current_version, '<=') && 
                version_compare($version, $target_version, '>')) {
                
                try {
                    $this->db->sql_query("START TRANSACTION");
                    
                    // Run rollback
                    if (isset($migration['down'])) {
                        foreach ($migration['down'] as $sql) {
                            $this->db->sql_query($sql);
                        }
                    }
                    
                    $this->db->sql_query("COMMIT");
                    
                    $results[$version] = [
                        'success' => true,
                        'message' => "Rolled back from version {$version}"
                    ];
                    
                } catch (Exception $e) {
                    $this->db->sql_query("ROLLBACK");
                    
                    $results[$version] = [
                        'success' => false,
                        'message' => "Rollback failed: " . $e->getMessage()
                    ];
                    
                    break;
                }
            }
        }
        
        $this->updateVersion($target_version);
        return $results;
    }
    
    /**
     * Update version in database
     */
    private function updateVersion($version)
    {
        $version = addslashes($version);
        $sql = "INSERT INTO `{$this->prefix}_extreme_ai_config` 
                (`provider`, `key`, `value`) 
                VALUES ('system', 'extreme_ai_version', '{$version}')
                ON DUPLICATE KEY UPDATE 
                `value` = '{$version}', 
                `updated` = CURRENT_TIMESTAMP";
        
        $this->db->sql_query($sql);
    }
    
    /**
     * Load migration definitions
     */
    private function loadMigrations()
    {
        $this->migrations = [
            '2.0.0' => [
                'description' => 'Initial ExtremeAI 2.0.0 database schema',
                'up' => $this->getMigration_2_0_0_up(),
                'down' => $this->getMigration_2_0_0_down()
            ],
            '2.0.1' => [
                'description' => 'Add performance indexes and optimize tables',
                'up' => $this->getMigration_2_0_1_up(),
                'down' => $this->getMigration_2_0_1_down()
            ],
            '2.1.0' => [
                'description' => 'Add conversation history and context management',
                'up' => $this->getMigration_2_1_0_up(),
                'down' => $this->getMigration_2_1_0_down()
            ]
        ];
    }
    
    /**
     * Migration 2.0.0 - Initial schema
     */
    private function getMigration_2_0_0_up()
    {
        return [
            // Config table
            "CREATE TABLE IF NOT EXISTS `{$this->prefix}_extreme_ai_config` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Providers table
            "CREATE TABLE IF NOT EXISTS `{$this->prefix}_extreme_ai_providers` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Usage table
            "CREATE TABLE IF NOT EXISTS `{$this->prefix}_extreme_ai_usage` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Continue with other tables...
        ];
    }
    
    private function getMigration_2_0_0_down()
    {
        return [
            "DROP TABLE IF EXISTS `{$this->prefix}_extreme_ai_config`",
            "DROP TABLE IF EXISTS `{$this->prefix}_extreme_ai_providers`",
            "DROP TABLE IF EXISTS `{$this->prefix}_extreme_ai_usage`",
            "DROP TABLE IF EXISTS `{$this->prefix}_extreme_ai_errors`",
            "DROP TABLE IF EXISTS `{$this->prefix}_extreme_ai_tasks`",
            "DROP TABLE IF EXISTS `{$this->prefix}_extreme_ai_analytics`",
            "DROP TABLE IF EXISTS `{$this->prefix}_extreme_ai_workflows`",
            "DROP TABLE IF EXISTS `{$this->prefix}_extreme_ai_agents`"
        ];
    }
    
    /**
     * Migration 2.0.1 - Performance optimizations
     */
    private function getMigration_2_0_1_up()
    {
        return [
            // Add composite indexes for better query performance
            "ALTER TABLE `{$this->prefix}_extreme_ai_usage` 
             ADD INDEX `idx_provider_created` (`provider`, `created`)",
            
            "ALTER TABLE `{$this->prefix}_extreme_ai_usage` 
             ADD INDEX `idx_user_task_created` (`user_id`, `task_type`, `created`)",
            
            // Add full-text search for error messages
            "ALTER TABLE `{$this->prefix}_extreme_ai_errors` 
             ADD FULLTEXT(`message`)",
            
            // Optimize provider stats queries
            "ALTER TABLE `{$this->prefix}_extreme_ai_providers` 
             ADD INDEX `idx_enabled_priority` (`enabled`, `priority`)",
        ];
    }
    
    private function getMigration_2_0_1_down()
    {
        return [
            "ALTER TABLE `{$this->prefix}_extreme_ai_usage` 
             DROP INDEX `idx_provider_created`",
            
            "ALTER TABLE `{$this->prefix}_extreme_ai_usage` 
             DROP INDEX `idx_user_task_created`",
            
            "ALTER TABLE `{$this->prefix}_extreme_ai_errors` 
             DROP INDEX `message`",
            
            "ALTER TABLE `{$this->prefix}_extreme_ai_providers` 
             DROP INDEX `idx_enabled_priority`"
        ];
    }
    
    /**
     * Migration 2.1.0 - Conversation management
     */
    private function getMigration_2_1_0_up()
    {
        return [
            // Conversations table
            "CREATE TABLE IF NOT EXISTS `{$this->prefix}_extreme_ai_conversations` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `uuid` varchar(36) NOT NULL,
                `title` varchar(255) DEFAULT NULL,
                `user_id` int(11) NOT NULL,
                `agent_id` int(11) DEFAULT NULL,
                `provider` varchar(50) DEFAULT NULL,
                `model` varchar(100) DEFAULT NULL,
                `context` json DEFAULT NULL,
                `message_count` int(11) DEFAULT 0,
                `total_tokens` bigint(20) DEFAULT 0,
                `total_cost` decimal(10,4) DEFAULT 0.0000,
                `last_activity` datetime DEFAULT CURRENT_TIMESTAMP,
                `created` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uuid` (`uuid`),
                INDEX `idx_user_id` (`user_id`),
                INDEX `idx_agent_id` (`agent_id`),
                INDEX `idx_last_activity` (`last_activity`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Messages table
            "CREATE TABLE IF NOT EXISTS `{$this->prefix}_extreme_ai_messages` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `conversation_id` bigint(20) NOT NULL,
                `role` enum('user','assistant','system') NOT NULL,
                `content` longtext NOT NULL,
                `metadata` json DEFAULT NULL,
                `tokens_used` int(11) DEFAULT 0,
                `cost` decimal(8,4) DEFAULT 0.0000,
                `created` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_conversation_id` (`conversation_id`),
                INDEX `idx_role` (`role`),
                INDEX `idx_created` (`created`),
                FOREIGN KEY (`conversation_id`) REFERENCES `{$this->prefix}_extreme_ai_conversations`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
    }
    
    private function getMigration_2_1_0_down()
    {
        return [
            "DROP TABLE IF EXISTS `{$this->prefix}_extreme_ai_messages`",
            "DROP TABLE IF EXISTS `{$this->prefix}_extreme_ai_conversations`"
        ];
    }
    
    /**
     * Check database health
     */
    public function checkHealth()
    {
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'recommendations' => []
        ];
        
        try {
            // Check table existence
            $required_tables = [
                'extreme_ai_config',
                'extreme_ai_providers',
                'extreme_ai_usage',
                'extreme_ai_errors',
                'extreme_ai_tasks',
                'extreme_ai_analytics',
                'extreme_ai_workflows',
                'extreme_ai_agents'
            ];
            
            foreach ($required_tables as $table) {
                $result = $this->db->sql_query("SHOW TABLES LIKE '{$this->prefix}_{$table}'");
                if (!$this->db->sql_fetchrow($result)) {
                    $health['issues'][] = "Missing table: {$this->prefix}_{$table}";
                    $health['status'] = 'unhealthy';
                }
                $this->db->sql_freeresult($result);
            }
            
            // Check for large tables
            $result = $this->db->sql_query("
                SELECT table_name, 
                       ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                       table_rows
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE() 
                AND table_name LIKE '{$this->prefix}_extreme_ai_%'
                ORDER BY size_mb DESC
            ");
            
            while ($row = $this->db->sql_fetchrow($result)) {
                if ($row['size_mb'] > 100) {
                    $health['recommendations'][] = "Consider archiving old data in {$row['table_name']} ({$row['size_mb']} MB)";
                }
                
                if (strpos($row['table_name'], 'usage') && $row['table_rows'] > 100000) {
                    $health['recommendations'][] = "Usage table has {$row['table_rows']} rows - consider cleanup";
                }
            }
            $this->db->sql_freeresult($result);
            
            // Check for errors
            $error_count = $this->db->sql_query("
                SELECT COUNT(*) as count 
                FROM `{$this->prefix}_extreme_ai_errors` 
                WHERE created > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            if ($error_row = $this->db->sql_fetchrow($error_count)) {
                if ($error_row['count'] > 100) {
                    $health['issues'][] = "High error rate: {$error_row['count']} errors in last 24 hours";
                    $health['status'] = 'warning';
                }
            }
            $this->db->sql_freeresult($error_count);
            
        } catch (Exception $e) {
            $health['status'] = 'unhealthy';
            $health['issues'][] = "Health check failed: " . $e->getMessage();
        }
        
        return $health;
    }
    
    /**
     * Cleanup old data
     */
    public function cleanup($days = 30)
    {
        $results = [];
        
        try {
            // Clean old usage records
            $usage_result = $this->db->sql_query("
                DELETE FROM `{$this->prefix}_extreme_ai_usage` 
                WHERE created < DATE_SUB(NOW(), INTERVAL {$days} DAY)
            ");
            $results['usage_cleaned'] = $this->db->sql_affectedrows();
            
            // Clean old error logs
            $error_result = $this->db->sql_query("
                DELETE FROM `{$this->prefix}_extreme_ai_errors` 
                WHERE created < DATE_SUB(NOW(), INTERVAL {$days} DAY)
            ");
            $results['errors_cleaned'] = $this->db->sql_affectedrows();
            
            // Clean completed tasks
            $task_result = $this->db->sql_query("
                DELETE FROM `{$this->prefix}_extreme_ai_tasks` 
                WHERE status IN ('completed', 'failed', 'cancelled') 
                AND completed_at < DATE_SUB(NOW(), INTERVAL {$days} DAY)
            ");
            $results['tasks_cleaned'] = $this->db->sql_affectedrows();
            
            // Optimize tables
            $tables = [
                'extreme_ai_usage',
                'extreme_ai_errors', 
                'extreme_ai_tasks',
                'extreme_ai_analytics'
            ];
            
            foreach ($tables as $table) {
                $this->db->sql_query("OPTIMIZE TABLE `{$this->prefix}_{$table}`");
            }
            
            $results['tables_optimized'] = count($tables);
            $results['success'] = true;
            
        } catch (Exception $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Export configuration
     */
    public function exportConfig()
    {
        $config = [];
        
        try {
            $result = $this->db->sql_query("
                SELECT provider, `key`, value, type 
                FROM `{$this->prefix}_extreme_ai_config` 
                ORDER BY provider, `key`
            ");
            
            while ($row = $this->db->sql_fetchrow($result)) {
                $config[$row['provider']][$row['key']] = [
                    'value' => $row['value'],
                    'type' => $row['type']
                ];
            }
            $this->db->sql_freeresult($result);
            
        } catch (Exception $e) {
            throw new Exception("Failed to export configuration: " . $e->getMessage());
        }
        
        return $config;
    }
    
    /**
     * Import configuration
     */
    public function importConfig($config)
    {
        $imported = 0;
        
        try {
            $this->db->sql_query("START TRANSACTION");
            
            foreach ($config as $provider => $settings) {
                foreach ($settings as $key => $data) {
                    $provider_safe = addslashes($provider);
                    $key_safe = addslashes($key);
                    $value_safe = addslashes($data['value']);
                    $type_safe = addslashes($data['type']);
                    
                    $sql = "INSERT INTO `{$this->prefix}_extreme_ai_config` 
                            (`provider`, `key`, `value`, `type`) 
                            VALUES ('$provider_safe', '$key_safe', '$value_safe', '$type_safe')
                            ON DUPLICATE KEY UPDATE 
                            `value` = '$value_safe', 
                            `type` = '$type_safe'";
                    
                    $this->db->sql_query($sql);
                    $imported++;
                }
            }
            
            $this->db->sql_query("COMMIT");
            
        } catch (Exception $e) {
            $this->db->sql_query("ROLLBACK");
            throw new Exception("Failed to import configuration: " . $e->getMessage());
        }
        
        return $imported;
    }
}
?>