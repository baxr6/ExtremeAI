<?php
/**
 * ExtremeAI Admin Dashboard Template
 *
 * Template for the main dashboard page showing system info and usage stats.
 *
 * @var array $system_info System information array
 * @var array $usage_stats Usage statistics array  
 * @var string $admin_file Admin file path
 * @var string $csrf_token CSRF token for form security
 */

defined('NUKE_EVO') || exit;
?>

<div class="extreme-ai-dashboard">
    <?php extreme_ai_admin_menu(); ?>
    
    <div class="dashboard-header">
        <h1><i class="fas fa-brain"></i> ExtremeAI Dashboard</h1>
        <p class="lead">Complete AI management and monitoring system</p>
    </div>

    <!-- System Status Cards -->
    <div class="status-cards-grid">
        <div class="status-card system-info">
            <div class="card-header">
                <h3><i class="fas fa-info-circle"></i> System Information</h3>
            </div>
            <div class="card-body">
                <div class="info-row">
                    <span class="label">Version:</span>
                    <span class="value"><?php echo e($system_info['version']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Debug Mode:</span>
                    <span class="value <?php echo $system_info['debug'] === 'Enabled' ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo e($system_info['debug']); ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="label">Core Status:</span>
                    <span class="value <?php echo $system_info['core_loaded'] ? 'status-enabled' : 'status-error'; ?>">
                        <?php echo $system_info['core_loaded'] ? 'Loaded' : 'Error'; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="label">Health Check:</span>
                    <span class="value <?php echo str_contains(strtolower($system_info['health']), 'error') ? 'status-error' : 'status-enabled'; ?>">
                        <?php echo e($system_info['health']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="status-card usage-stats">
            <div class="card-header">
                <h3><i class="fas fa-chart-line"></i> Today's Usage</h3>
            </div>
            <div class="card-body">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($usage_stats['requests_today']); ?></div>
                    <div class="stat-label">Requests</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $usage_stats['avg_response_time']; ?>s</div>
                    <div class="stat-label">Avg Response Time</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">$<?php echo number_format($usage_stats['costs_today'], 2); ?></div>
                    <div class="stat-label">Cost</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $usage_stats['error_rate']; ?>%</div>
                    <div class="stat-label">Error Rate</div>
                </div>
            </div>
        </div>

        <div class="status-card providers-status">
            <div class="card-header">
                <h3><i class="fas fa-plug"></i> AI Providers</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($system_info['providers'])): ?>
                    <div class="providers-list">
                        <?php foreach ($system_info['providers'] as $provider => $status): ?>
                        <div class="provider-item">
                            <span class="provider-name"><?php echo e(ucfirst($provider)); ?></span>
                            <span class="provider-status <?php echo $status ? 'status-enabled' : 'status-disabled'; ?>">
                                <?php echo $status ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-providers">No providers configured</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="status-card quick-actions">
            <div class="card-header">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="action-buttons">
                    <a href="<?php echo e($admin_file); ?>.php?op=extremeai_providers" class="action-btn providers">
                        <i class="fas fa-cogs"></i>
                        Configure Providers
                    </a>
                    <a href="<?php echo e($admin_file); ?>.php?op=extremeai_test_console" class="action-btn test">
                        <i class="fas fa-vial"></i>
                        Test Console
                    </a>
                    <a href="<?php echo e($admin_file); ?>.php?op=extremeai_analytics" class="action-btn analytics">
                        <i class="fas fa-analytics"></i>
                        View Analytics
                    </a>
                    <a href="<?php echo e($admin_file); ?>.php?op=extremeai_settings" class="action-btn settings">
                        <i class="fas fa-wrench"></i>
                        System Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="recent-activity-section">
        <div class="section-header">
            <h2><i class="fas fa-history"></i> Recent Activity</h2>
        </div>
        <div class="activity-content">
            <div id="recent-activity-loader" class="loader">
                <i class="fas fa-spinner fa-spin"></i> Loading recent activity...
            </div>
            <div id="recent-activity-content" style="display: none;">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Dashboard JavaScript is loaded from external file -->
<script>
// Set admin file path and CSRF token for dashboard.js
let configRetries = 0;
function setExtremeAIConfig() {
    configRetries++;
    
    if (typeof ExtremeAI !== 'undefined' && ExtremeAI.setCsrfToken) {
        ExtremeAI.adminFile = '<?php echo e($admin_file); ?>';
        ExtremeAI.setCsrfToken('<?php echo e($csrf_token); ?>');
        console.log('[Dashboard] ExtremeAI configured successfully after', configRetries, 'attempts');
        return;
    }
    
    if (configRetries < 50) { // Limit retries to 5 seconds
        console.log('[Dashboard] ExtremeAI not ready yet, retrying...', configRetries, '/50');
        console.log('[Dashboard] Debug - ExtremeAI exists:', typeof ExtremeAI !== 'undefined');
        console.log('[Dashboard] Debug - setCsrfToken exists:', typeof ExtremeAI !== 'undefined' ? typeof ExtremeAI.setCsrfToken : 'N/A');
        if (typeof ExtremeAI !== 'undefined') {
            console.log('[Dashboard] Debug - ExtremeAI object:', ExtremeAI);
            console.log('[Dashboard] Debug - ExtremeAI methods:', Object.getOwnPropertyNames(ExtremeAI));
        }
        setTimeout(setExtremeAIConfig, 100);
    } else {
        console.error('[Dashboard] ExtremeAI failed to load after 50 retries, giving up');
        console.log('[Dashboard] Final state - ExtremeAI exists:', typeof ExtremeAI !== 'undefined');
        if (typeof ExtremeAI !== 'undefined') {
            console.log('[Dashboard] Final state - ExtremeAI object:', ExtremeAI);
        }
    }
}

// Call immediately and retry if needed
setExtremeAIConfig();
</script>