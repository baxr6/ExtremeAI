<?php
/**
 * ExtremeAI Admin Providers Template
 *
 * Template for the AI providers configuration page.
 *
 * @var array $providers_config Existing provider configurations
 * @var array $available_providers Available provider definitions
 * @var string $admin_file Admin file path
 * @var string $csrf_token CSRF token for form security
 */

defined('NUKE_EVO') || exit;
?>

<div class="extreme-ai-providers">
    <?php extreme_ai_admin_menu(); ?>
    
    <div class="providers-header">
        <h1><i class="fas fa-plug"></i> AI Provider Management</h1>
        <p class="lead">Configure and manage your AI service providers</p>
    </div>

    <div class="providers-grid">
        <?php foreach ($available_providers as $provider_key => $provider_info): 
            $config = $providers_config[$provider_key] ?? [];
            $is_configured = !empty($config['api_key']) || $provider_key === 'ollama';
            $is_enabled = !empty($config['enabled']);
        ?>
        
        <div class="provider-card <?php echo $is_enabled ? 'enabled' : ''; ?>" data-provider="<?php echo e($provider_key); ?>">
            <div class="provider-header">
                <div class="provider-info">
                    <div class="provider-icon">
                        <i class="<?php echo e($provider_info['icon']); ?>"></i>
                    </div>
                    <div class="provider-details">
                        <h3><?php echo e($provider_info['name']); ?></h3>
                        <span class="status-badge <?php echo $is_configured ? 'configured' : 'not-configured'; ?>">
                            <?php echo $is_configured ? 'Configured' : 'Not Configured'; ?>
                        </span>
                        <?php if ($is_enabled): ?>
                        <span class="enabled-badge">Active</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="provider-actions">
                    <button class="btn btn-sm btn-outline toggle-config" 
                            onclick="toggleProviderConfig('<?php echo e($provider_key); ?>')">
                        <i class="fas fa-cog"></i> Configure
                    </button>
                    <?php if ($is_configured): ?>
                    <button class="btn btn-sm btn-success test-provider" 
                            onclick="testProvider('<?php echo e($provider_key); ?>')">
                        <i class="fas fa-vial"></i> Test
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="provider-config-section" id="config-<?php echo e($provider_key); ?>" style="display: none;">
                <form method="post" action="<?php echo e($admin_file); ?>.php?op=extremeai_providers" class="provider-form">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>" />
                    <input type="hidden" name="provider_name" value="<?php echo e($provider_key); ?>" />
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <span class="label-text">Display Name</span>
                                <input type="text" name="display_name" 
                                       value="<?php echo e($config['display_name'] ?? $provider_info['name']); ?>" 
                                       class="form-input" />
                                <span class="label-help">Friendly name for this provider</span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <span class="label-text">API Key</span>
                                <input type="password" name="api_key" 
                                       value="<?php echo e($config['api_key'] ?? ''); ?>" 
                                       class="form-input" 
                                       <?php echo $provider_key !== 'ollama' ? 'required' : ''; ?> />
                                <span class="label-help">
                                    <?php if ($provider_key === 'ollama'): ?>
                                        Not required for local Ollama installation
                                    <?php else: ?>
                                        Your API key from <?php echo e($provider_info['name']); ?>
                                    <?php endif; ?>
                                </span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <span class="label-text">API Endpoint</span>
                                <input type="url" name="api_endpoint" 
                                       value="<?php echo e($config['api_endpoint'] ?? $provider_info['default_endpoint']); ?>" 
                                       class="form-input" required />
                                <span class="label-help">API endpoint URL</span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <span class="label-text">Default Model</span>
                                <input type="text" name="model" 
                                       value="<?php echo e($config['model'] ?? $provider_info['default_model']); ?>" 
                                       class="form-input" required />
                                <span class="label-help">Model identifier to use by default</span>
                            </label>
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="checkbox-label">
                                <input type="checkbox" name="enabled" value="1" 
                                       <?php echo $is_enabled ? 'checked' : ''; ?> />
                                <span class="checkbox-text">Enable this provider</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="save_provider" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Configuration
                        </button>
                        <button type="button" onclick="resetProviderForm('<?php echo e($provider_key); ?>')" 
                                class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                        <button type="button" onclick="toggleProviderConfig('<?php echo e($provider_key); ?>')" 
                                class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
            
            <?php if ($is_configured): ?>
            <div class="provider-stats" id="stats-<?php echo e($provider_key); ?>">
                <div class="stats-loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading provider statistics...
                </div>
                <div class="stats-content" style="display: none;">
                    <!-- Stats loaded via AJAX -->
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php endforeach; ?>
    </div>
    
    <!-- Provider Test Modal -->
    <div id="test-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Test AI Provider</h3>
                <button class="modal-close" onclick="closeTestModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="test-form">
                    <label>Test Prompt:</label>
                    <textarea id="test-prompt" rows="3" placeholder="Enter a test prompt...">Hello, can you respond to confirm the connection is working?</textarea>
                    
                    <div class="test-actions">
                        <button id="run-test" class="btn btn-primary" onclick="runProviderTest()">
                            <i class="fas fa-play"></i> Run Test
                        </button>
                        <button class="btn btn-secondary" onclick="closeTestModal()">Cancel</button>
                    </div>
                </div>
                
                <div id="test-results" class="test-results" style="display: none;">
                    <h4>Test Results:</h4>
                    <div id="test-output"></div>
                    <div id="test-metrics" class="test-metrics"></div>
                </div>
            </div>
        </div>
    </div>
    <div id="modal-overlay" class="modal-overlay" style="display: none;" onclick="closeTestModal()"></div>
</div>

<!-- Providers JavaScript is loaded from external file -->
<script>
// Set admin file path and CSRF token for providers.js
let configRetries = 0;
function setExtremeAIConfig() {
    configRetries++;
    
    // More comprehensive check for ExtremeAI readiness
    if (typeof ExtremeAI !== 'undefined' && 
        ExtremeAI !== null &&
        typeof ExtremeAI === 'object' &&
        typeof ExtremeAI.setCsrfToken === 'function') {
        
        ExtremeAI.adminFile = '<?php echo e($admin_file); ?>';
        ExtremeAI.setCsrfToken('<?php echo e($csrf_token); ?>');
        console.log('[Providers] ExtremeAI configured successfully after', configRetries, 'attempts');
        return;
    }
    
    if (configRetries < 50) { // Limit retries to 5 seconds
        console.log('[Providers] ExtremeAI not ready yet, retrying...', configRetries, '/50');
        console.log('[Providers] Debug - ExtremeAI exists:', typeof ExtremeAI !== 'undefined');
        console.log('[Providers] Debug - setCsrfToken exists:', typeof ExtremeAI !== 'undefined' ? typeof ExtremeAI.setCsrfToken : 'N/A');
        if (typeof ExtremeAI !== 'undefined') {
            console.log('[Providers] Debug - ExtremeAI type:', typeof ExtremeAI);
            console.log('[Providers] Debug - ExtremeAI constructor:', ExtremeAI.constructor?.name);
            console.log('[Providers] Debug - Available methods:', Object.getOwnPropertyNames(ExtremeAI));
            console.log('[Providers] Debug - Prototype methods:', Object.getOwnPropertyNames(Object.getPrototypeOf(ExtremeAI)));
        }
        setTimeout(setExtremeAIConfig, 100);
    } else {
        console.error('[Providers] ExtremeAI failed to load after 50 retries, giving up');
        console.log('[Providers] Final state - ExtremeAI:', typeof ExtremeAI !== 'undefined' ? ExtremeAI : 'undefined');
    }
}

// Call immediately and retry if needed
setExtremeAIConfig();
</script>