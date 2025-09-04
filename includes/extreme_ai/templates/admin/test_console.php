<?php
/**
 * ExtremeAI Test Console Template
 *
 * Interactive testing interface for AI providers.
 *
 * @var array $providers Available provider configurations
 * @var string $admin_file Admin file path
 * @var string $csrf_token CSRF token for form security
 */

defined('NUKE_EVO') || exit;
?>

<div class="extreme-ai-test-console">
    <?php extreme_ai_admin_menu(); ?>
    
    <div class="test-console-header">
        <h1><i class="fas fa-terminal"></i> AI Test Console</h1>
        <p class="lead">Interactive testing environment for AI providers</p>
    </div>

    <div class="test-console-layout">
        <!-- Provider Selection & Settings -->
        <div class="test-console-sidebar">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> Test Configuration</h3>
                </div>
                <div class="card-body">
                    <form id="test-config-form">
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>" />
                        
                        <div class="form-group">
                            <label for="test-provider">AI Provider</label>
                            <select id="test-provider" name="provider" class="form-control">
                                <option value="">Auto-select best provider</option>
                                <?php 
                                $configured_count = 0;
                                foreach ($providers as $key => $provider): 
                                    // Check if provider is both enabled and has required configuration
                                    $is_enabled = !empty($provider['enabled']) || $provider['enabled'] === '1' || $provider['enabled'] === 1;
                                    $has_api_key = !empty($provider['api_key']) || $key === 'ollama'; // Ollama doesn't need API key
                                    $has_endpoint = !empty($provider['api_endpoint']) || !empty($provider['default_endpoint']);
                                    $has_model = !empty($provider['model']) || !empty($provider['default_model']);
                                    
                                    $is_configured = $is_enabled && $has_api_key && $has_endpoint && $has_model;
                                    
                                    if ($is_configured): 
                                        $configured_count++;
                                ?>
                                        <option value="<?php echo e($key); ?>">
                                            <?php echo e($provider['display_name'] ?? $provider['name'] ?? ucfirst($key)); ?>
                                            <?php if ($key === 'ollama'): ?> (Local)<?php endif; ?>
                                        </option>
                                <?php 
                                    endif;
                                endforeach; 
                                
                                // If no providers configured, show a message
                                if ($configured_count === 0): ?>
                                    <option value="" disabled>No providers configured - Please configure providers first</option>
                                <?php endif; ?>
                            </select>
                            <?php if ($configured_count > 0): ?>
                                <small class="form-text text-success"><?php echo $configured_count; ?> provider(s) available</small>
                            <?php else: ?>
                                <small class="form-text text-warning">
                                    <a href="admin.php?op=extremeai_providers">Configure providers</a> to enable testing
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="test-task-type">Task Type</label>
                            <select id="test-task-type" name="task_type" class="form-control">
                                <option value="text_generation">Text Generation</option>
                                <option value="content_analysis">Content Analysis</option>
                                <option value="translation">Translation</option>
                                <option value="summarization">Summarization</option>
                                <option value="code_generation">Code Generation</option>
                                <option value="sentiment_analysis">Sentiment Analysis</option>
                                <option value="classification">Classification</option>
                                <option value="question_answering">Q&A</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="test-max-tokens">Max Tokens</label>
                            <input type="number" id="test-max-tokens" name="max_tokens" class="form-control" 
                                   value="1000" min="1" max="4096">
                        </div>
                        
                        <div class="form-group">
                            <label for="test-temperature">Temperature</label>
                            <input type="range" id="test-temperature" name="temperature" class="form-range" 
                                   min="0" max="2" step="0.1" value="0.7">
                            <small class="form-text">Current: <span id="temperature-value">0.7</span></small>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="test-stream" name="stream" class="form-check-input">
                                <label for="test-stream" class="form-check-label">Stream Response</label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Test History -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Recent Tests</h3>
                    <button id="clear-history" class="btn btn-sm btn-outline-secondary">Clear</button>
                </div>
                <div class="card-body">
                    <div id="test-history" class="test-history">
                        <!-- Test history loaded via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Test Interface -->
        <div class="test-console-main">
            <div class="test-input-section">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-edit"></i> Input</h3>
                        <div class="input-actions">
                            <button id="load-sample" class="btn btn-sm btn-outline-secondary">Load Sample</button>
                            <button id="clear-input" class="btn btn-sm btn-outline-secondary">Clear</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="system-prompt">System Prompt (Optional)</label>
                            <textarea id="system-prompt" name="system" class="form-control" rows="2"
                                      placeholder="Enter system instructions..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="user-prompt">User Prompt</label>
                            <textarea id="user-prompt" name="prompt" class="form-control" rows="6"
                                      placeholder="Enter your prompt here..." required></textarea>
                            <small class="form-text">
                                <span id="prompt-chars">0</span> characters
                            </small>
                        </div>
                        
                        <div class="test-actions">
                            <button id="run-test" class="btn btn-primary">
                                <i class="fas fa-play"></i> Run Test
                            </button>
                            <button id="stop-test" class="btn btn-danger" style="display: none;">
                                <i class="fas fa-stop"></i> Stop
                            </button>
                            <button id="save-test" class="btn btn-outline-secondary">
                                <i class="fas fa-save"></i> Save Test
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="test-output-section">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-terminal"></i> Output</h3>
                        <div class="output-actions">
                            <button id="copy-output" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                            <button id="export-test" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="test-output" class="test-output">
                            <div class="output-placeholder">
                                <i class="fas fa-play-circle fa-3x"></i>
                                <p>Run a test to see results here</p>
                            </div>
                        </div>
                        
                        <!-- Test Metrics -->
                        <div id="test-metrics" class="test-metrics" style="display: none;">
                            <div class="metrics-grid">
                                <div class="metric">
                                    <label>Response Time</label>
                                    <span id="metric-response-time">-</span>
                                </div>
                                <div class="metric">
                                    <label>Provider</label>
                                    <span id="metric-provider">-</span>
                                </div>
                                <div class="metric">
                                    <label>Tokens Used</label>
                                    <span id="metric-tokens">-</span>
                                </div>
                                <div class="metric">
                                    <label>Cost</label>
                                    <span id="metric-cost">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sample Prompts Modal -->
    <div id="samples-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Sample Prompts</h3>
                <button class="modal-close" onclick="closeSamplesModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="samples-grid">
                    <div class="sample-item" data-task="text_generation">
                        <h4>Creative Writing</h4>
                        <p>Write a short story about a robot discovering emotions</p>
                    </div>
                    <div class="sample-item" data-task="code_generation">
                        <h4>Code Generation</h4>
                        <p>Create a Python function to calculate fibonacci numbers with memoization</p>
                    </div>
                    <div class="sample-item" data-task="content_analysis">
                        <h4>Content Analysis</h4>
                        <p>Analyze the sentiment and key themes in this text: [Your content here]</p>
                    </div>
                    <div class="sample-item" data-task="translation">
                        <h4>Translation</h4>
                        <p>Translate the following English text to French: "Hello, how are you today?"</p>
                    </div>
                    <div class="sample-item" data-task="summarization">
                        <h4>Summarization</h4>
                        <p>Summarize this article in 3 key points: [Your article text here]</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="modal-overlay" class="modal-overlay" style="display: none;" onclick="closeSamplesModal()"></div>
</div>

<!-- Test Console JavaScript will be loaded separately -->
<script>
// Set up configuration for test console
const TestConsole = {
    adminFile: '<?php echo e($admin_file); ?>',
    csrfToken: '<?php echo e($csrf_token); ?>',
    providers: <?php echo json_encode($providers); ?>
};

// Debug: Log provider information
console.log('[Test Console] Loaded providers:', TestConsole.providers);
console.log('[Test Console] Provider count:', Object.keys(TestConsole.providers).length);

// Debug: Check which providers are configured
Object.keys(TestConsole.providers).forEach(key => {
    const provider = TestConsole.providers[key];
    const isEnabled = !!(provider.enabled) || provider.enabled === '1' || provider.enabled === 1;
    const hasApiKey = !!provider.api_key || key === 'ollama';
    const hasEndpoint = !!provider.api_endpoint || !!provider.default_endpoint;
    const hasModel = !!provider.model || !!provider.default_model;
    const isConfigured = isEnabled && hasApiKey && hasEndpoint && hasModel;
    
    console.log(`[Test Console] Provider ${key}:`, {
        enabled: isEnabled,
        hasApiKey: hasApiKey,
        hasEndpoint: hasEndpoint,
        hasModel: hasModel,
        configured: isConfigured,
        provider: provider
    });
    
    // Show the raw provider data to see what fields exist
    console.log(`[Test Console] Raw ${key} data:`, provider);
    console.log(`[Test Console] ${key} enabled field value:`, provider.enabled, typeof provider.enabled);
    console.log(`[Test Console] ${key} api_key field value:`, provider.api_key, typeof provider.api_key);
});
</script>