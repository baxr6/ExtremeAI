<?php
/**
 * ExtremeAI Database Upgrade Script
 *
 * Handles database schema updates and migrations between versions.
 *
 * @category Extreme_AI
 * @package  Evo-Extreme
 * @author   Deano Welch <deano.welch@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/baxr6/
 * @since    2.0.0
 * @requires PHP 8.0 or higher
 */

define('EXTREME_AI_UPGRADE', true);
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
session_start();
if (!is_admin()) {
    die('Access Denied: Admin privileges required for upgrades.');
}

// Include the database migration class
require_once NUKE_INCLUDE_DIR . 'extreme_ai/classes/ExtremeAI_Database.php';

/**
 * ExtremeAI Upgrade Manager
 */
class ExtremeAI_Upgrader
{
    private $db_manager;
    private $errors = [];
    private $warnings = [];
    private $success_messages = [];
    
    public function __construct()
    {
        $this->db_manager = new ExtremeAI_Database();
    }
    
    /**
     * Run the upgrade process
     */
    public function run()
    {
        if ($_POST && isset($_POST['action'])) {
            $this->handleAction($_POST['action']);
        }
        
        $this->renderPage();
    }
    
    /**
     * Handle form actions
     */
    private function handleAction($action)
    {
        switch ($action) {
            case 'check_status':
                $this->checkUpgradeStatus();
                break;
            case 'run_migrations':
                $this->runMigrations();
                break;
            case 'rollback':
                $this->runRollback($_POST['target_version'] ?? '');
                break;
            case 'health_check':
                $this->runHealthCheck();
                break;
            case 'cleanup_data':
                $this->cleanupData($_POST['cleanup_days'] ?? 30);
                break;
            case 'export_config':
                $this->exportConfiguration();
                break;
            case 'import_config':
                $this->importConfiguration();
                break;
        }
    }
    
    /**
     * Check upgrade status
     */
    private function checkUpgradeStatus()
    {
        $current = $this->db_manager->getCurrentVersion();
        $needs_update = $this->db_manager->needsUpdate();
        
        if ($needs_update) {
            $pending = $this->db_manager->getPendingMigrations();
            $this->warnings[] = "Database version {$current} is outdated. " . count($pending) . " migrations pending.";
        } else {
            $this->success_messages[] = "Database is up to date (version {$current}).";
        }
    }
    
    /**
     * Run database migrations
     */
    private function runMigrations()
    {
        try {
            $results = $this->db_manager->migrate();
            
            foreach ($results as $version => $result) {
                if ($result['success']) {
                    $this->success_messages[] = "✓ {$result['message']}";
                } else {
                    $this->errors[] = "✗ Migration to {$version} failed: {$result['message']}";
                }
            }
            
            if (empty($this->errors)) {
                $this->success_messages[] = "All migrations completed successfully!";
            }
            
        } catch (Exception $e) {
            $this->errors[] = "Migration process failed: " . $e->getMessage();
        }
    }
    
    /**
     * Run rollback to previous version
     */
    private function runRollback($target_version)
    {
        if (empty($target_version)) {
            $this->errors[] = "Target version required for rollback.";
            return;
        }
        
        try {
            $results = $this->db_manager->rollback($target_version);
            
            foreach ($results as $version => $result) {
                if ($result['success']) {
                    $this->success_messages[] = "✓ {$result['message']}";
                } else {
                    $this->errors[] = "✗ Rollback from {$version} failed: {$result['message']}";
                }
            }
            
            if (empty($this->errors)) {
                $this->success_messages[] = "Rollback to version {$target_version} completed successfully!";
            }
            
        } catch (Exception $e) {
            $this->errors[] = "Rollback process failed: " . $e->getMessage();
        }
    }
    
    /**
     * Run database health check
     */
    private function runHealthCheck()
    {
        try {
            $health = $this->db_manager->checkHealth();
            
            switch ($health['status']) {
                case 'healthy':
                    $this->success_messages[] = "✓ Database health check passed!";
                    break;
                case 'warning':
                    $this->warnings[] = "⚠ Database health check completed with warnings.";
                    break;
                case 'unhealthy':
                    $this->errors[] = "✗ Database health check failed!";
                    break;
            }
            
            foreach ($health['issues'] as $issue) {
                $this->errors[] = "Issue: " . $issue;
            }
            
            foreach ($health['recommendations'] as $recommendation) {
                $this->warnings[] = "Recommendation: " . $recommendation;
            }
            
        } catch (Exception $e) {
            $this->errors[] = "Health check failed: " . $e->getMessage();
        }
    }
    
    /**
     * Cleanup old data
     */
    private function cleanupData($days)
    {
        try {
            $results = $this->db_manager->cleanup((int)$days);
            
            if ($results['success']) {
                $this->success_messages[] = "Data cleanup completed successfully:";
                $this->success_messages[] = "- Cleaned {$results['usage_cleaned']} usage records";
                $this->success_messages[] = "- Cleaned {$results['errors_cleaned']} error logs";
                $this->success_messages[] = "- Cleaned {$results['tasks_cleaned']} completed tasks";
                $this->success_messages[] = "- Optimized {$results['tables_optimized']} tables";
            } else {
                $this->errors[] = "Data cleanup failed: " . $results['error'];
            }
            
        } catch (Exception $e) {
            $this->errors[] = "Cleanup process failed: " . $e->getMessage();
        }
    }
    
    /**
     * Export configuration
     */
    private function exportConfiguration()
    {
        try {
            $config = $this->db_manager->exportConfig();
            
            $filename = 'extreme_ai_config_' . date('Y-m-d_H-i-s') . '.json';
            $json_data = json_encode($config, JSON_PRETTY_PRINT);
            
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Content-Length: ' . strlen($json_data));
            
            echo $json_data;
            exit;
            
        } catch (Exception $e) {
            $this->errors[] = "Configuration export failed: " . $e->getMessage();
        }
    }
    
    /**
     * Import configuration
     */
    private function importConfiguration()
    {
        if (!isset($_FILES['config_file'])) {
            $this->errors[] = "No configuration file uploaded.";
            return;
        }
        
        $file = $_FILES['config_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = "File upload error: " . $file['error'];
            return;
        }
        
        try {
            $json_data = file_get_contents($file['tmp_name']);
            $config = json_decode($json_data, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->errors[] = "Invalid JSON file: " . json_last_error_msg();
                return;
            }
            
            $imported = $this->db_manager->importConfig($config);
            $this->success_messages[] = "Configuration imported successfully! {$imported} settings updated.";
            
        } catch (Exception $e) {
            $this->errors[] = "Configuration import failed: " . $e->getMessage();
        }
    }
    
    /**
     * Render the upgrade page
     */
    private function renderPage()
    {
        $current_version = $this->db_manager->getCurrentVersion();
        $needs_update = $this->db_manager->needsUpdate();
        $pending_migrations = $this->db_manager->getPendingMigrations();
        
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>ExtremeAI Database Upgrade</title>
            <style>
                <?php $this->renderCSS(); ?>
            </style>
        </head>
        <body>
            <div class="upgrade-container">
                <header class="upgrade-header">
                    <div class="logo">
                        <i class="fas fa-database"></i>
                        <h1>ExtremeAI Database Upgrade</h1>
                    </div>
                    <div class="version-info">
                        <span class="current-version">Current: v<?php echo $current_version; ?></span>
                        <?php if (defined('EXTREME_AI_VERSION')): ?>
                        <span class="target-version">Target: v<?php echo EXTREME_AI_VERSION; ?></span>
                        <?php endif; ?>
                    </div>
                </header>
                
                <div class="upgrade-content">
                    <?php $this->renderMessages(); ?>
                    
                    <div class="upgrade-sections">
                        <!-- Status Section -->
                        <div class="upgrade-section">
                            <div class="section-header">
                                <h2><i class="fas fa-info-circle"></i> System Status</h2>
                            </div>
                            <div class="section-content">
                                <div class="status-grid">
                                    <div class="status-item">
                                        <div class="status-label">Database Version</div>
                                        <div class="status-value"><?php echo $current_version; ?></div>
                                    </div>
                                    <div class="status-item">
                                        <div class="status-label">Update Required</div>
                                        <div class="status-value <?php echo $needs_update ? 'warning' : 'success'; ?>">
                                            <?php echo $needs_update ? 'Yes' : 'No'; ?>
                                        </div>
                                    </div>
                                    <div class="status-item">
                                        <div class="status-label">Pending Migrations</div>
                                        <div class="status-value"><?php echo count($pending_migrations); ?></div>
                                    </div>
                                </div>
                                
                                <form method="post" class="action-form">
                                    <input type="hidden" name="action" value="check_status">
                                    <button type="submit" class="btn btn-secondary">
                                        <i class="fas fa-refresh"></i> Refresh Status
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <?php if ($needs_update): ?>
                        <!-- Migration Section -->
                        <div class="upgrade-section">
                            <div class="section-header">
                                <h2><i class="fas fa-arrow-up"></i> Database Migration</h2>
                            </div>
                            <div class="section-content">
                                <p>The following migrations are pending:</p>
                                
                                <div class="migration-list">
                                    <?php foreach ($pending_migrations as $version => $migration): ?>
                                    <div class="migration-item">
                                        <div class="migration-version">v<?php echo $version; ?></div>
                                        <div class="migration-description">
                                            <?php echo $migration['description'] ?? 'No description'; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <form method="post" class="action-form" onsubmit="return confirm('Run database migrations? This action cannot be undone.')">
                                    <input type="hidden" name="action" value="run_migrations">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-play"></i> Run Migrations
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Health Check Section -->
                        <div class="upgrade-section">
                            <div class="section-header">
                                <h2><i class="fas fa-heartbeat"></i> Database Health</h2>
                            </div>
                            <div class="section-content">
                                <p>Check your database health and get recommendations for optimization.</p>
                                
                                <form method="post" class="action-form">
                                    <input type="hidden" name="action" value="health_check">
                                    <button type="submit" class="btn btn-info">
                                        <i class="fas fa-stethoscope"></i> Run Health Check
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Maintenance Section -->
                        <div class="upgrade-section">
                            <div class="section-header">
                                <h2><i class="fas fa-broom"></i> Database Maintenance</h2>
                            </div>
                            <div class="section-content">
                                <p>Clean up old data and optimize database performance.</p>
                                
                                <form method="post" class="action-form">
                                    <input type="hidden" name="action" value="cleanup_data">
                                    <div class="form-group">
                                        <label>Remove data older than:</label>
                                        <select name="cleanup_days">
                                            <option value="7">7 days</option>
                                            <option value="30" selected>30 days</option>
                                            <option value="90">90 days</option>
                                            <option value="365">1 year</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-warning" onclick="return confirm('Clean up old data? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Clean Up Data
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Configuration Section -->
                        <div class="upgrade-section">
                            <div class="section-header">
                                <h2><i class="fas fa-cog"></i> Configuration Management</h2>
                            </div>
                            <div class="section-content">
                                <p>Backup or restore your ExtremeAI configuration.</p>
                                
                                <div class="config-actions">
                                    <form method="post" class="action-form inline">
                                        <input type="hidden" name="action" value="export_config">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-download"></i> Export Configuration
                                        </button>
                                    </form>
                                    
                                    <form method="post" enctype="multipart/form-data" class="action-form inline">
                                        <input type="hidden" name="action" value="import_config">
                                        <input type="file" name="config_file" accept=".json" required>
                                        <button type="submit" class="btn btn-primary" onclick="return confirm('Import configuration? This will overwrite existing settings.')">
                                            <i class="fas fa-upload"></i> Import Configuration
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Rollback Section (Advanced) -->
                        <?php if ($current_version !== '2.0.0'): ?>
                        <div class="upgrade-section danger-section">
                            <div class="section-header">
                                <h2><i class="fas fa-undo"></i> Rollback (Advanced)</h2>
                            </div>
                            <div class="section-content">
                                <div class="warning-message">
                                    <strong>Warning:</strong> Rolling back can cause data loss. Only use if instructed by support.
                                </div>
                                
                                <form method="post" class="action-form">
                                    <input type="hidden" name="action" value="rollback">
                                    <div class="form-group">
                                        <label>Rollback to version:</label>
                                        <select name="target_version" required>
                                            <option value="">Select version...</option>
                                            <option value="2.0.0">2.0.0</option>
                                            <option value="2.0.1">2.0.1</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('This will rollback your database and may cause data loss. Are you sure?')">
                                        <i class="fas fa-exclamation-triangle"></i> Rollback Database
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <footer class="upgrade-footer">
                    <p>
                        <a href="admin.php?op=extremeai_dashboard">&larr; Back to ExtremeAI Admin</a> |
                        <a href="admin.php">Admin Panel</a> |
                        <a href="index.php">Home</a>
                    </p>
                </footer>
            </div>
        </body>
        </html>
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
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .upgrade-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        
        .upgrade-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
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
            font-size: 1.8em;
            font-weight: 400;
        }
        
        .version-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
            text-align: right;
        }
        
        .current-version,
        .target-version {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.9em;
        }
        
        .upgrade-content {
            padding: 40px;
        }
        
        .upgrade-sections {
            display: grid;
            gap: 30px;
        }
        
        .upgrade-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid #e9ecef;
        }
        
        .danger-section {
            border-color: #dc3545;
        }
        
        .section-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .danger-section .section-header {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        }
        
        .section-header h2 {
            color: #495057;
            font-size: 1.4em;
            font-weight: 500;
        }
        
        .danger-section .section-header h2 {
            color: #721c24;
        }
        
        .section-content {
            padding: 25px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .status-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        
        .status-label {
            font-size: 0.9em;
            color: #6c757d;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-value {
            font-size: 1.4em;
            font-weight: 600;
            color: #495057;
        }
        
        .status-value.success {
            color: #28a745;
        }
        
        .status-value.warning {
            color: #ffc107;
        }
        
        .status-value.error {
            color: #dc3545;
        }
        
        .migration-list {
            margin: 20px 0;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        
        .migration-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .migration-item:last-child {
            border-bottom: none;
        }
        
        .migration-version {
            background: #007bff;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
            min-width: 60px;
            text-align: center;
        }
        
        .migration-description {
            color: #495057;
            flex-grow: 1;
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
        
        .warning-message {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .action-form {
            margin: 20px 0;
        }
        
        .action-form.inline {
            display: inline-block;
            margin: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }
        
        .form-group select,
        .form-group input[type="file"] {
            padding: 8px 12px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .config-actions {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: end;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
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
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
        }
        
        .upgrade-footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }
        
        .upgrade-footer a {
            color: #007bff;
            text-decoration: none;
        }
        
        .upgrade-footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .upgrade-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .upgrade-content {
                padding: 20px;
            }
            
            .status-grid {
                grid-template-columns: 1fr;
            }
            
            .config-actions {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
            }
        }
        <?php
    }
}

// Run the upgrader
$upgrader = new ExtremeAI_Upgrader();
$upgrader->run();
?>