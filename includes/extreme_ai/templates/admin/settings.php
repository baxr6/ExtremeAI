<?php
/**
 * ExtremeAI Admin Settings Template
 *
 * Template for the system settings page.
 *
 * @var array $settings Current system settings
 * @var array $additional_settings Additional provider settings
 * @var array $message Flash message (if any)
 * @var string $admin_file Admin file path
 * @var string $csrf_token CSRF token for form security
 */

defined('NUKE_EVO') || exit;
?>

<div class="extreme-ai-settings">
    <?php extreme_ai_admin_menu(); ?>
    
    <div class="settings-header">
        <h1><i class="fas fa-cogs"></i> ExtremeAI System Settings</h1>
        <p class="lead">Configure core system parameters and behavior</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message['type'] === 'error' ? 'danger' : 'success'; ?>">
        <?php echo e($message['text']); ?>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo e($admin_file); ?>.php?op=extremeai_settings" class="settings-form">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>" />

        <!-- Core Settings Section -->
        <div class="settings-section">
            <div class="section-header">
                <h2><i class="fas fa-microchip"></i> Core Settings</h2>
                <p>Basic system configuration and behavior settings</p>
            </div>
            
            <div class="settings-grid">
                <div class="setting-group">
                    <label class="setting-label">
                        <input type="checkbox" name="extreme_ai_debug" value="1" 
                               <?php echo $settings['extreme_ai_debug'] ? 'checked' : ''; ?> />
                        <span class="setting-title">Debug Mode</span>
                        <span class="setting-description">Enable detailed logging and error reporting</span>
                    </label>
                </div>

                <div class="setting-group">
                    <label class="setting-label">
                        <input type="checkbox" name="extreme_ai_cache_enabled" value="1" 
                               <?php echo $settings['extreme_ai_cache_enabled'] ? 'checked' : ''; ?> />
                        <span class="setting-title">Enable Caching</span>
                        <span class="setting-description">Cache API responses to improve performance</span>
                    </label>
                </div>

                <div class="setting-group">
                    <label class="setting-label">
                        <span class="setting-title">Cache TTL (seconds)</span>
                        <input type="number" name="extreme_ai_cache_ttl" 
                               value="<?php echo e($settings['extreme_ai_cache_ttl']); ?>" 
                               min="60" max="86400" class="setting-input" />
                        <span class="setting-description">How long to cache responses (60-86400 seconds)</span>
                    </label>
                </div>

                <div class="setting-group">
                    <label class="setting-label">
                        <span class="setting-title">Auto Cleanup Days</span>
                        <input type="number" name="extreme_ai_auto_cleanup_days" 
                               value="<?php echo e($settings['extreme_ai_auto_cleanup_days']); ?>" 
                               min="1" max="365" class="setting-input" />
                        <span class="setting-description">Automatically clean up logs older than X days</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Performance Settings Section -->
        <div class="settings-section">
            <div class="section-header">
                <h2><i class="fas fa-tachometer-alt"></i> Performance Settings</h2>
                <p>Configure performance limits and optimization settings</p>
            </div>
            
            <div class="settings-grid">
                <div class="setting-group">
                    <label class="setting-label">
                        <span class="setting-title">Max Tokens per Request</span>
                        <input type="number" name="extreme_ai_max_tokens" 
                               value="<?php echo e($settings['extreme_ai_max_tokens']); ?>" 
                               min="100" max="32000" class="setting-input" />
                        <span class="setting-description">Maximum tokens allowed per AI request</span>
                    </label>
                </div>

                <div class="setting-group">
                    <label class="setting-label">
                        <span class="setting-title">Request Rate Limit</span>
                        <input type="number" name="extreme_ai_rate_limit" 
                               value="<?php echo e($settings['extreme_ai_rate_limit']); ?>" 
                               min="10" max="10000" class="setting-input" />
                        <span class="setting-description">Maximum requests per hour</span>
                    </label>
                </div>

                <div class="setting-group">
                    <label class="setting-label">
                        <span class="setting-title">Default Timeout (seconds)</span>
                        <input type="number" name="extreme_ai_default_timeout" 
                               value="<?php echo e($settings['extreme_ai_default_timeout']); ?>" 
                               min="5" max="300" class="setting-input" />
                        <span class="setting-description">Default timeout for API requests</span>
                    </label>
                </div>

                <div class="setting-group">
                    <label class="setting-label">
                        <span class="setting-title">Max Concurrent Requests</span>
                        <input type="number" name="extreme_ai_max_concurrent_requests" 
                               value="<?php echo e($settings['extreme_ai_max_concurrent_requests']); ?>" 
                               min="1" max="50" class="setting-input" />
                        <span class="setting-description">Maximum simultaneous API requests</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Logging Settings Section -->
        <div class="settings-section">
            <div class="section-header">
                <h2><i class="fas fa-file-alt"></i> Logging Settings</h2>
                <p>Configure system logging and monitoring options</p>
            </div>
            
            <div class="settings-grid">
                <div class="setting-group">
                    <label class="setting-label">
                        <span class="setting-title">Log Level</span>
                        <select name="extreme_ai_log_level" class="setting-select">
                            <option value="error" <?php echo $settings['extreme_ai_log_level'] === 'error' ? 'selected' : ''; ?>>
                                Error Only
                            </option>
                            <option value="warning" <?php echo $settings['extreme_ai_log_level'] === 'warning' ? 'selected' : ''; ?>>
                                Warnings & Errors
                            </option>
                            <option value="info" <?php echo $settings['extreme_ai_log_level'] === 'info' ? 'selected' : ''; ?>>
                                Info, Warnings & Errors
                            </option>
                            <option value="debug" <?php echo $settings['extreme_ai_log_level'] === 'debug' ? 'selected' : ''; ?>>
                                All (Debug Mode)
                            </option>
                        </select>
                        <span class="setting-description">What level of events to log</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Additional Provider Settings -->
        <?php if (!empty($additional_settings)): ?>
        <div class="settings-section">
            <div class="section-header">
                <h2><i class="fas fa-plug"></i> Provider-Specific Settings</h2>
                <p>Additional configuration options from individual providers</p>
            </div>
            
            <?php foreach ($additional_settings as $provider => $provider_settings): ?>
            <div class="provider-settings">
                <h3><?php echo e(ucfirst($provider)); ?> Settings</h3>
                <div class="provider-settings-grid">
                    <?php foreach ($provider_settings as $key => $value): ?>
                    <div class="setting-group">
                        <label class="setting-label">
                            <span class="setting-title"><?php echo e(ucwords(str_replace('_', ' ', $key))); ?></span>
                            <input type="text" name="provider_<?php echo e($provider); ?>_<?php echo e($key); ?>" 
                                   value="<?php echo e($value); ?>" class="setting-input" readonly />
                            <span class="setting-description">Managed by <?php echo e($provider); ?> provider</span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" name="save_settings" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Settings
            </button>
            <button type="button" id="reset-settings" class="btn btn-secondary">
                <i class="fas fa-undo"></i> Reset to Defaults
            </button>
            <a href="<?php echo e($admin_file); ?>.php?op=extremeai_dashboard" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </form>

    <!-- Settings Help -->
    <div class="settings-help">
        <div class="help-header">
            <h3><i class="fas fa-question-circle"></i> Settings Help</h3>
        </div>
        <div class="help-content">
            <div class="help-item">
                <strong>Debug Mode:</strong> 
                Enables detailed logging and error reporting. Useful for troubleshooting but may impact performance.
            </div>
            <div class="help-item">
                <strong>Cache Settings:</strong> 
                Caching improves performance by storing API responses. Adjust TTL based on how often your content changes.
            </div>
            <div class="help-item">
                <strong>Rate Limiting:</strong> 
                Protects your API keys from overuse and helps manage costs. Set based on your provider limits.
            </div>
            <div class="help-item">
                <strong>Timeouts:</strong> 
                Longer timeouts allow for complex requests but may slow down your site if providers are unresponsive.
            </div>
        </div>
    </div>
</div>

<!-- Settings JavaScript is loaded from external file -->
<script>
// Set admin file path and CSRF token for settings.js
let configRetries = 0;
function setExtremeAIConfig() {
    configRetries++;
    
    if (typeof ExtremeAI !== 'undefined' && ExtremeAI.setCsrfToken) {
        ExtremeAI.adminFile = '<?php echo e($admin_file); ?>';
        ExtremeAI.setCsrfToken('<?php echo e($csrf_token); ?>');
        console.log('[Settings] ExtremeAI configured successfully after', configRetries, 'attempts');
        return;
    }
    
    if (configRetries < 50) { // Limit retries to 5 seconds
        console.log('[Settings] ExtremeAI not ready yet, retrying...', configRetries, '/50');
        setTimeout(setExtremeAIConfig, 100);
    } else {
        console.error('[Settings] ExtremeAI failed to load after 50 retries, giving up');
    }
}

// Call immediately and retry if needed
setExtremeAIConfig();
</script>