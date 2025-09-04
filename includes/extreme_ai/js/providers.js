/**
 * ExtremeAI Providers JavaScript
 * 
 * Handles provider management functionality including configuration,
 * testing, statistics monitoring, and provider interactions.
 * 
 * @version 2.0.0
 * @author Deano Welch
 */

class ExtremeAI_Providers {
    constructor() {
        this.currentTestProvider = null;
        this.statsRefreshInterval = null;
        this.adminFile = 'admin';
        this.testTimeout = 30000; // 30 seconds
        
        this.init();
    }
    
    /**
     * Wait for ExtremeAI to be ready with all required methods
     */
    async waitForExtremeAI(retries = 10) {
        for (let i = 0; i < retries; i++) {
            if (window.ExtremeAI && 
                typeof window.ExtremeAI.ajax === 'function' &&
                typeof window.ExtremeAI.showNotification === 'function' &&
                typeof window.ExtremeAI.debounce === 'function') {
                return true;
            }
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        console.error('[ExtremeAI Providers] ExtremeAI core not ready after retries');
        return false;
    }

    /**
     * Fallback debounce function for when ExtremeAI isn't ready
     */
    debounce(func, wait) {
        if (window.ExtremeAI && typeof window.ExtremeAI.debounce === 'function') {
            return window.ExtremeAI.debounce(func, wait);
        }
        
        // Fallback implementation
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Safe logging methods that check if ExtremeAI exists
     */
    log(...args) {
        try {
            if (window.ExtremeAI && typeof window.ExtremeAI.log === 'function') {
                window.ExtremeAI.log(...args);
            } else {
                console.log('[ExtremeAI Providers]', ...args);
            }
        } catch (e) {
            console.log('[ExtremeAI Providers]', ...args);
        }
    }
    
    error(...args) {
        try {
            if (window.ExtremeAI && typeof window.ExtremeAI.error === 'function') {
                window.ExtremeAI.error(...args);
            } else {
                console.error('[ExtremeAI Providers Error]', ...args);
            }
        } catch (e) {
            console.error('[ExtremeAI Providers Error]', ...args);
        }
    }
    
    /**
     * Initialize providers functionality
     */
    init() {
        document.addEventListener('DOMContentLoaded', async () => {
            // Wait for ExtremeAI to be ready before initializing
            const isReady = await this.waitForExtremeAI();
            if (isReady) {
                // Self-configure if needed
                if (!ExtremeAI.adminFile) {
                    ExtremeAI.adminFile = 'admin'; // Default fallback
                    console.log('[Providers] Self-configured ExtremeAI.adminFile');
                }
                
                // Sync local adminFile with ExtremeAI instance
                this.adminFile = ExtremeAI.adminFile;
                
                this.setupEventHandlers();
                this.loadAllProviderStats();
                this.startStatsRefresh();
                this.initProviderCards();
                this.setupFormValidation();
                console.log('[Providers] Successfully initialized with ExtremeAI');
            } else {
                console.error('[Providers] Failed to initialize - ExtremeAI not available');
            }
        });
    }
    
    /**
     * Setup event handlers
     */
    setupEventHandlers() {
        // Provider configuration toggles
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('toggle-config')) {
                const provider = e.target.dataset.provider;
                if (provider) {
                    this.toggleProviderConfig(provider);
                }
            }
            
            // Test provider buttons
            if (e.target.classList.contains('test-provider')) {
                const provider = e.target.dataset.provider;
                if (provider) {
                    this.testProvider(provider);
                }
            }
            
            // Provider card actions
            if (e.target.closest('.provider-card')) {
                this.handleProviderCardClick(e);
            }
        });
        
        // Form submissions - Use normal form submission instead of AJAX
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('provider-form')) {
                console.log('[Providers] Allowing normal form submission for provider configuration');
                // Let the form submit normally - no AJAX
            }
        });
        
        // Modal close handlers
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeTestModal();
            }
        });
        
        // Auto-save for provider forms
        this.setupAutoSave();
        
        // Provider status monitoring
        this.setupStatusMonitoring();
    }
    
    /**
     * Initialize provider cards
     */
    initProviderCards() {
        const cards = document.querySelectorAll('.provider-card');
        cards.forEach(card => {
            this.initProviderCard(card);
        });
    }
    
    /**
     * Initialize individual provider card
     */
    initProviderCard(card) {
        const provider = card.dataset.provider;
        if (!provider) return;
        
        // Add hover effects
        card.addEventListener('mouseenter', () => {
            card.classList.add('card-hover');
        });
        
        card.addEventListener('mouseleave', () => {
            card.classList.remove('card-hover');
        });
        
        // Initialize configuration form
        const form = card.querySelector('.provider-form');
        if (form) {
            this.initProviderForm(form, provider);
        }
        
        // Initialize stats section
        const statsSection = card.querySelector('.provider-stats');
        if (statsSection) {
            this.initProviderStats(statsSection, provider);
        }
    }
    
    /**
     * Initialize provider stats section
     */
    initProviderStats(statsSection, provider) {
        // Initialize stats loading
        this.loadProviderStats(provider);
        
        // Set up refresh interval if needed
        if (!this.statsRefreshInterval) {
            this.statsRefreshInterval = setInterval(() => {
                this.loadAllProviderStats();
            }, 60000); // Refresh every minute
        }
    }
    
    /**
     * Initialize provider form
     */
    initProviderForm(form, provider) {
        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateProviderInput(input, provider);
            });
            
            input.addEventListener('input', this.debounce(() => {
                this.validateProviderInput(input, provider);
                this.updateProviderPreview(provider);
            }, 300));
        });
        
        // API key masking
        const apiKeyInput = form.querySelector('input[name="api_key"]');
        if (apiKeyInput) {
            this.setupApiKeyMasking(apiKeyInput);
        }
        
        // Endpoint validation
        const endpointInput = form.querySelector('input[name="api_endpoint"]');
        if (endpointInput) {
            this.setupEndpointValidation(endpointInput);
        }
    }
    
    /**
     * Setup API key input masking
     */
    setupApiKeyMasking(input) {
        let isRevealed = false;
        
        // Add reveal/hide button
        const wrapper = document.createElement('div');
        wrapper.className = 'input-wrapper';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        const toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.className = 'btn btn-sm btn-outline toggle-api-key';
        toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
        toggleButton.title = 'Show/Hide API Key';
        wrapper.appendChild(toggleButton);
        
        // Initially mask the key if it exists
        if (input.value && input.value.length > 8) {
            const maskedValue = input.value.substring(0, 4) + '•'.repeat(input.value.length - 8) + input.value.slice(-4);
            input.setAttribute('data-original', input.value);
            input.value = maskedValue;
        }
        
        toggleButton.addEventListener('click', () => {
            if (isRevealed) {
                // Hide
                const original = input.getAttribute('data-original');
                if (original) {
                    const masked = original.substring(0, 4) + '•'.repeat(original.length - 8) + original.slice(-4);
                    input.value = masked;
                }
                toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
                isRevealed = false;
            } else {
                // Reveal
                const original = input.getAttribute('data-original');
                if (original) {
                    input.value = original;
                }
                toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
                isRevealed = true;
            }
        });
        
        // Update original value on input
        input.addEventListener('input', () => {
            if (isRevealed) {
                input.setAttribute('data-original', input.value);
            }
        });
        
        // Ensure original value is submitted
        input.form.addEventListener('submit', () => {
            const original = input.getAttribute('data-original');
            if (original && !isRevealed) {
                input.value = original;
            }
        });
    }
    
    /**
     * Setup endpoint URL validation
     */
    setupEndpointValidation(input) {
        input.addEventListener('blur', () => {
            const url = input.value.trim();
            if (url && !this.isValidUrl(url)) {
                this.setInputValidation(input, false, 'Please enter a valid URL');
            } else {
                this.setInputValidation(input, true);
            }
        });
    }
    
    /**
     * Validate URL format
     */
    isValidUrl(string) {
        try {
            const url = new URL(string);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch (_) {
            return false;
        }
    }
    
    /**
     * Validate provider input
     */
    validateProviderInput(input, provider) {
        const name = input.name;
        const value = input.value.trim();
        
        let isValid = true;
        let message = '';
        
        switch (name) {
            case 'display_name':
                if (!value) {
                    isValid = false;
                    message = 'Display name is required';
                } else if (value.length > 100) {
                    isValid = false;
                    message = 'Display name must be 100 characters or less';
                }
                break;
                
            case 'api_key':
                if (provider !== 'ollama' && !value) {
                    isValid = false;
                    message = 'API key is required';
                } else if (value && value.length < 8) {
                    isValid = false;
                    message = 'API key appears to be too short';
                }
                break;
                
            case 'api_endpoint':
                if (!value) {
                    isValid = false;
                    message = 'API endpoint is required';
                } else if (!this.isValidUrl(value)) {
                    isValid = false;
                    message = 'Please enter a valid URL';
                }
                break;
                
            case 'model':
                if (!value) {
                    isValid = false;
                    message = 'Model name is required';
                }
                break;
        }
        
        this.setInputValidation(input, isValid, message);
        return isValid;
    }
    
    /**
     * Set input validation state
     */
    setInputValidation(input, isValid, message = '') {
        const formGroup = input.closest('.form-group');
        let errorElement = formGroup?.querySelector('.validation-error');
        
        if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            if (errorElement) {
                errorElement.remove();
            }
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            
            if (!errorElement && message) {
                errorElement = document.createElement('div');
                errorElement.className = 'validation-error text-danger mt-1';
                errorElement.textContent = message;
                formGroup.appendChild(errorElement);
            } else if (errorElement && message) {
                errorElement.textContent = message;
            }
        }
    }
    
    /**
     * Setup form validation
     */
    setupFormValidation() {
        const forms = document.querySelectorAll('.provider-form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateProviderForm(form)) {
                    e.preventDefault();
                    ExtremeAI.showNotification('Please fix validation errors before saving', 'error');
                }
            });
        });
    }
    
    /**
     * Validate entire provider form
     */
    validateProviderForm(form) {
        const provider = form.closest('[data-provider]')?.dataset.provider;
        if (!provider) return false;
        
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        
        inputs.forEach(input => {
            if (!this.validateProviderInput(input, provider)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    /**
     * Setup auto-save functionality
     */
    setupAutoSave() {
        let autoSaveTimeouts = new Map();
        
        document.addEventListener('input', (e) => {
            const form = e.target.closest('.provider-form');
            if (form) {
                const provider = form.closest('[data-provider]')?.dataset.provider;
                if (provider) {
                    // Clear existing timeout
                    if (autoSaveTimeouts.has(provider)) {
                        clearTimeout(autoSaveTimeouts.get(provider));
                    }
                    
                    // Set new timeout
                    const timeout = setTimeout(() => {
                        this.autoSaveProvider(form, provider);
                    }, 5000); // Auto-save after 5 seconds of inactivity
                    
                    autoSaveTimeouts.set(provider, timeout);
                }
            }
        });
    }
    
    /**
     * Auto-save provider configuration
     */
    async autoSaveProvider(form, provider) {
        if (!this.validateProviderForm(form)) {
            return;
        }
        
        try {
            await this.saveProviderConfiguration(form, true);
            const card = form.closest('.provider-card');
            if (card) {
                this.showAutoSaveIndicator(card);
            }
        } catch (error) {
            this.error('Auto-save failed for provider:', provider, error);
        }
    }
    
    /**
     * Show auto-save indicator
     */
    showAutoSaveIndicator(card) {
        let indicator = card.querySelector('.auto-save-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'auto-save-indicator';
            indicator.innerHTML = '<i class="fas fa-check-circle"></i> Auto-saved';
            card.appendChild(indicator);
        }
        
        indicator.style.display = 'block';
        
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 3000);
    }
    
    /**
     * Setup status monitoring
     */
    setupStatusMonitoring() {
        // Check provider status periodically
        setInterval(() => {
            this.checkProviderStatuses();
        }, 60000); // Every minute
    }
    
    /**
     * Check all provider statuses
     */
    async checkProviderStatuses() {
        const cards = document.querySelectorAll('.provider-card[data-provider]');
        const promises = [];
        
        cards.forEach(card => {
            const provider = card.dataset.provider;
            const isEnabled = card.classList.contains('enabled');
            
            if (isEnabled) {
                promises.push(this.checkProviderStatus(provider));
            }
        });
        
        try {
            await Promise.all(promises);
        } catch (error) {
            this.error('Status check failed:', error);
        }
    }
    
    /**
     * Check individual provider status
     */
    async checkProviderStatus(provider) {
        try {
            // Wait for ExtremeAI to be ready
            const isReady = await this.waitForExtremeAI();
            if (!isReady) {
                this.error(`ExtremeAI.ajax not available for status check of ${provider}`);
                return;
            }
            
            const url = `${this.adminFile}.php?op=extremeai_providers&ajax_action=check_provider_status&provider=${provider}`;
            const result = await ExtremeAI.ajax(url);
            
            if (result.success && result.data.success) {
                this.updateProviderStatus(provider, result.data.data);
            }
        } catch (error) {
            this.error(`Status check failed for ${provider}:`, error);
        }
    }
    
    /**
     * Update provider status indicator
     */
    updateProviderStatus(provider, status) {
        const card = document.querySelector(`.provider-card[data-provider="${provider}"]`);
        if (!card) return;
        
        let statusIndicator = card.querySelector('.status-indicator');
        if (!statusIndicator) {
            statusIndicator = document.createElement('div');
            statusIndicator.className = 'status-indicator';
            card.querySelector('.provider-header').appendChild(statusIndicator);
        }
        
        statusIndicator.className = `status-indicator status-${status.status}`;
        statusIndicator.title = `Status: ${status.message || status.status}`;
        
        // Update last checked time
        const lastChecked = card.querySelector('.last-checked');
        if (lastChecked) {
            lastChecked.textContent = `Last checked: ${new Date().toLocaleTimeString()}`;
        }
    }
    
    /**
     * Toggle provider configuration
     */
    toggleProviderConfig(provider) {
        const configSection = document.getElementById('config-' + provider);
        if (!configSection) return;
        
        const isVisible = configSection.style.display !== 'none';
        
        // Hide all other config sections
        document.querySelectorAll('.provider-config-section').forEach(section => {
            if (section !== configSection) {
                section.style.display = 'none';
            }
        });
        
        // Toggle this section
        if (isVisible) {
            configSection.style.display = 'none';
        } else {
            configSection.style.display = 'block';
            
            // Scroll to the form
            configSection.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'nearest' 
            });
            
            // Focus first input
            const firstInput = configSection.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 300);
            }
        }
    }
    
    /**
     * Save provider configuration
     */
    async saveProviderConfiguration(form, silent = false) {
        console.log('[Providers] saveProviderConfiguration called with form:', form);
        
        const card = form.closest('.provider-card');
        const provider = card?.dataset.provider;
        
        console.log('[Providers] Found card:', card);
        console.log('[Providers] Found provider:', provider);
        
        if (!provider) {
            console.error('[Providers] Provider not found!');
            throw new Error('Provider not found');
        }
        
        const saveButton = form.querySelector('button[name="save_provider"]');
        const originalText = saveButton?.innerHTML;
        
        try {
            if (saveButton) {
                saveButton.disabled = true;
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            }
            
            // Wait for ExtremeAI to be ready
            const isReady = await this.waitForExtremeAI();
            if (!isReady) {
                throw new Error('ExtremeAI core not available');
            }
            
            // Extract form data and format for AJAX endpoint
            const formData = new FormData(form);
            const config = {
                display_name: formData.get('display_name'),
                api_key: formData.get('api_key'),
                api_endpoint: formData.get('api_endpoint'),
                model: formData.get('model'),
                enabled: formData.has('enabled') ? 1 : 0
            };
            
            // Create AJAX request data
            const ajaxData = new FormData();
            ajaxData.append('ajax_action', 'save_provider_config');
            ajaxData.append('provider', provider);
            ajaxData.append('config', JSON.stringify(config));
            ajaxData.append('csrf_token', formData.get('csrf_token'));
            
            const url = `${this.adminFile}.php?op=extremeai_providers`;
            
            console.log('[Providers] Submitting AJAX request to:', url);
            console.log('[Providers] AJAX data:', {
                provider: provider,
                config: config,
                ajax_action: 'save_provider_config'
            });
            
            const result = await ExtremeAI.ajax(url, {
                method: 'POST',
                body: ajaxData
            });
            
            console.log('[Providers] AJAX response:', result);
            
            if (result.success) {
                // Update card state
                this.updateProviderCard(card, formData);
                
                if (!silent) {
                    ExtremeAI.showNotification(`${provider} configuration saved successfully!`, 'success');
                }
                
                // Refresh stats
                this.loadProviderStats(provider);
                
            } else {
                throw new Error(result.error || 'Failed to save configuration');
            }
            
        } catch (error) {
            this.error('Provider configuration save failed:', error);
            if (!silent) {
                ExtremeAI.showNotification('Failed to save configuration: ' + error.message, 'error');
            }
            throw error;
        } finally {
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.innerHTML = originalText;
            }
        }
    }
    
    /**
     * Update provider card after save
     */
    updateProviderCard(card, formData) {
        const enabled = formData.get('enabled') === '1';
        const displayName = formData.get('display_name');
        const apiKey = formData.get('api_key');
        
        // Update enabled state
        if (enabled) {
            card.classList.add('enabled');
        } else {
            card.classList.remove('enabled');
        }
        
        // Update display name
        const nameElement = card.querySelector('.provider-details h3');
        if (nameElement && displayName) {
            nameElement.textContent = displayName;
        }
        
        // Update configured status
        const isConfigured = !!apiKey || card.dataset.provider === 'ollama';
        const statusBadge = card.querySelector('.status-badge');
        if (statusBadge) {
            statusBadge.textContent = isConfigured ? 'Configured' : 'Not Configured';
            statusBadge.className = `status-badge ${isConfigured ? 'configured' : 'not-configured'}`;
        }
        
        // Show/hide enabled badge
        let enabledBadge = card.querySelector('.enabled-badge');
        if (enabled && !enabledBadge) {
            enabledBadge = document.createElement('span');
            enabledBadge.className = 'enabled-badge';
            enabledBadge.textContent = 'Active';
            card.querySelector('.provider-details').appendChild(enabledBadge);
        } else if (!enabled && enabledBadge) {
            enabledBadge.remove();
        }
    }
    
    /**
     * Test provider connection
     */
    testProvider(provider) {
        this.currentTestProvider = provider;
        
        const modal = document.getElementById('test-modal');
        const overlay = document.getElementById('modal-overlay');
        
        if (modal && overlay) {
            modal.style.display = 'block';
            overlay.style.display = 'block';
            
            // Reset modal content
            const resultsDiv = document.getElementById('test-results');
            if (resultsDiv) {
                resultsDiv.style.display = 'none';
            }
            
            const promptTextarea = document.getElementById('test-prompt');
            if (promptTextarea) {
                promptTextarea.focus();
            }
        }
    }
    
    /**
     * Close test modal
     */
    closeTestModal() {
        const modal = document.getElementById('test-modal');
        const overlay = document.getElementById('modal-overlay');
        
        if (modal) modal.style.display = 'none';
        if (overlay) overlay.style.display = 'none';
        
        this.currentTestProvider = null;
    }
    
    /**
     * Run provider test
     */
    async runProviderTest() {
        if (!this.currentTestProvider) return;
        
        const promptTextarea = document.getElementById('test-prompt');
        const runButton = document.getElementById('run-test');
        const resultsDiv = document.getElementById('test-results');
        const outputDiv = document.getElementById('test-output');
        const metricsDiv = document.getElementById('test-metrics');
        
        const prompt = promptTextarea?.value.trim();
        if (!prompt) {
            ExtremeAI.showNotification('Please enter a test prompt', 'warning');
            return;
        }
        
        const originalText = runButton?.innerHTML;
        const startTime = Date.now();
        
        try {
            if (runButton) {
                runButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
                runButton.disabled = true;
            }
            
            // Wait for ExtremeAI to be ready
            const isReady = await this.waitForExtremeAI();
            if (!isReady) {
                throw new Error('ExtremeAI core not available for testing');
            }
            
            const formData = new FormData();
            formData.append('provider', this.currentTestProvider);
            formData.append('prompt', prompt);
            
            const url = `${this.adminFile}.php?op=extremeai_providers&ajax_action=test_provider`;
            
            // Set timeout for the request
            const timeoutPromise = new Promise((_, reject) => {
                setTimeout(() => reject(new Error('Test timeout')), this.testTimeout);
            });
            
            const testPromise = ExtremeAI.ajax(url, {
                method: 'POST',
                body: formData
            });
            
            const result = await Promise.race([testPromise, timeoutPromise]);
            const endTime = Date.now();
            const responseTime = endTime - startTime;
            
            // Show results
            if (resultsDiv) resultsDiv.style.display = 'block';
            
            if (result.success && result.data.success) {
                this.showTestSuccess(outputDiv, result.data.data);
            } else {
                this.showTestError(outputDiv, result.data?.error || 'Unknown error occurred');
            }
            
            this.showTestMetrics(metricsDiv, {
                responseTime,
                provider: this.currentTestProvider,
                status: result.success && result.data.success ? 'Success' : 'Failed',
                tokensUsed: result.data?.data?.tokens_used || 'N/A'
            });
            
        } catch (error) {
            const endTime = Date.now();
            const responseTime = endTime - startTime;
            
            if (resultsDiv) resultsDiv.style.display = 'block';
            this.showTestError(outputDiv, error.message);
            this.showTestMetrics(metricsDiv, {
                responseTime,
                provider: this.currentTestProvider,
                status: 'Error',
                error: error.message
            });
            
        } finally {
            if (runButton) {
                runButton.innerHTML = originalText;
                runButton.disabled = false;
            }
        }
    }
    
    /**
     * Show test success result
     */
    showTestSuccess(container, data) {
        if (!container) return;
        
        const response = data.response || data.content || 'Test completed successfully';
        
        container.innerHTML = `
            <div class="test-success">
                <h5>✅ Test Successful</h5>
                <div class="response-text">${ExtremeAI.escapeHtml(response)}</div>
            </div>
        `;
    }
    
    /**
     * Show test error result
     */
    showTestError(container, error) {
        if (!container) return;
        
        container.innerHTML = `
            <div class="test-error">
                <h5>❌ Test Failed</h5>
                <div class="error-text">${ExtremeAI.escapeHtml(error)}</div>
            </div>
        `;
    }
    
    /**
     * Show test metrics
     */
    showTestMetrics(container, metrics) {
        if (!container) return;
        
        const metricsHtml = Object.keys(metrics).map(key => {
            const value = metrics[key];
            const label = key.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase());
            
            return `<span class="metric">${label}: ${value}</span>`;
        }).join('');
        
        container.innerHTML = `
            <div class="metrics">
                ${metricsHtml}
            </div>
        `;
    }
    
    /**
     * Reset provider form to defaults
     */
    resetProviderForm(provider) {
        const form = document.querySelector(`#config-${provider} form`);
        if (!form) return;
        
        ExtremeAI.confirm('Reset form to default values?', (confirmed) => {
            if (confirmed) {
                // Get default values from data attributes or predefined defaults
                const defaults = this.getProviderDefaults(provider);
                
                Object.keys(defaults).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        if (input.type === 'checkbox') {
                            input.checked = defaults[key];
                        } else {
                            input.value = defaults[key];
                        }
                        
                        // Trigger validation
                        input.dispatchEvent(new Event('blur'));
                    }
                });
                
                ExtremeAI.showNotification('Form reset to default values', 'info');
            }
        });
    }
    
    /**
     * Get provider default values
     */
    getProviderDefaults(provider) {
        const defaults = {
            anthropic: {
                display_name: 'Anthropic Claude',
                api_endpoint: 'https://api.anthropic.com/v1/messages',
                model: 'claude-3-haiku-20240307',
                enabled: false
            },
            openai: {
                display_name: 'OpenAI GPT',
                api_endpoint: 'https://api.openai.com/v1/chat/completions',
                model: 'gpt-4o',
                enabled: false
            },
            google: {
                display_name: 'Google Gemini',
                api_endpoint: 'https://generativelanguage.googleapis.com/v1beta/models/',
                model: 'gemini-pro',
                enabled: false
            },
            ollama: {
                display_name: 'Ollama Local',
                api_endpoint: 'http://localhost:11434',
                model: 'llama2',
                enabled: false
            }
        };
        
        return defaults[provider] || {};
    }
    
    /**
     * Load all provider statistics
     */
    loadAllProviderStats() {
        const cards = document.querySelectorAll('[data-provider]');
        cards.forEach(card => {
            const provider = card.dataset.provider;
            const statsSection = document.getElementById('stats-' + provider);
            if (statsSection) {
                this.loadProviderStats(provider);
            }
        });
    }
    
    /**
     * Load statistics for a specific provider
     */
    async loadProviderStats(provider) {
        const statsSection = document.getElementById('stats-' + provider);
        if (!statsSection) return;
        
        const loadingDiv = statsSection.querySelector('.stats-loading');
        const contentDiv = statsSection.querySelector('.stats-content');
        
        try {
            if (loadingDiv) loadingDiv.style.display = 'block';
            if (contentDiv) contentDiv.style.display = 'none';
            
            // Wait for ExtremeAI to be ready
            const isReady = await this.waitForExtremeAI();
            if (!isReady) {
                throw new Error('ExtremeAI core not available');
            }
            
            const url = `${this.adminFile}.php?op=extremeai_providers&ajax_action=get_provider_stats&provider=${provider}`;
            const result = await ExtremeAI.ajax(url);
            
            if (result.success && result.data.success) {
                const stats = result.data.data;
                const formattedStats = this.formatProviderStats(stats);
                
                if (contentDiv) {
                    contentDiv.innerHTML = formattedStats;
                    contentDiv.style.display = 'block';
                }
            } else {
                throw new Error(result.data?.error || 'Failed to load stats');
            }
            
        } catch (error) {
            this.error(`Failed to load stats for ${provider}:`, error);
            if (contentDiv) {
                contentDiv.innerHTML = '<p class="stats-error">Failed to load statistics</p>';
                contentDiv.style.display = 'block';
            }
        } finally {
            if (loadingDiv) {
                loadingDiv.style.display = 'none';
            }
        }
    }
    
    /**
     * Format provider statistics for display
     */
    formatProviderStats(stats) {
        if (!stats) {
            return '<p class="no-stats">No statistics available</p>';
        }
        
        return `
            <div class="provider-stats-grid">
                <div class="stat-item">
                    <span class="stat-value">${ExtremeAI.formatNumber(stats.requests_today || 0)}</span>
                    <span class="stat-label">Requests Today</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">${(stats.avg_response_time || 0)}ms</span>
                    <span class="stat-label">Avg Response</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">${ExtremeAI.formatCurrency(stats.cost_today || 0)}</span>
                    <span class="stat-label">Cost Today</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">${(stats.success_rate || 100)}%</span>
                    <span class="stat-label">Success Rate</span>
                </div>
            </div>
        `;
    }
    
    /**
     * Start periodic stats refresh
     */
    startStatsRefresh() {
        // Refresh stats every minute
        this.statsRefreshInterval = setInterval(() => {
            this.loadAllProviderStats();
        }, 60000);
    }
    
    /**
     * Stop stats refresh
     */
    stopStatsRefresh() {
        if (this.statsRefreshInterval) {
            clearInterval(this.statsRefreshInterval);
            this.statsRefreshInterval = null;
        }
    }
    
    /**
     * Handle provider card clicks
     */
    handleProviderCardClick(e) {
        // Add click handling for various card elements
        const target = e.target;
        
        if (target.classList.contains('provider-card') && !target.closest('form, button, .provider-actions')) {
            // Clicking the card itself toggles configuration
            const provider = target.dataset.provider;
            if (provider) {
                this.toggleProviderConfig(provider);
            }
        }
    }
    
    /**
     * Update provider preview (real-time preview of settings)
     */
    updateProviderPreview(provider) {
        const form = document.querySelector(`#config-${provider} .provider-form`);
        if (!form) return;
        
        const formData = new FormData(form);
        const previewSection = document.querySelector(`#config-${provider} .provider-preview`);
        
        if (previewSection) {
            // Update preview with current form values
            previewSection.innerHTML = this.generateProviderPreview(formData, provider);
        }
    }
    
    /**
     * Generate provider preview HTML
     */
    generateProviderPreview(formData, provider) {
        const displayName = formData.get('display_name') || provider;
        const endpoint = formData.get('api_endpoint') || '';
        const model = formData.get('model') || '';
        const enabled = formData.get('enabled') === '1';
        
        return `
            <div class="preview-card">
                <h5>${ExtremeAI.escapeHtml(displayName)}</h5>
                <div class="preview-details">
                    <div class="preview-item">
                        <strong>Endpoint:</strong> <code>${ExtremeAI.escapeHtml(endpoint)}</code>
                    </div>
                    <div class="preview-item">
                        <strong>Model:</strong> <code>${ExtremeAI.escapeHtml(model)}</code>
                    </div>
                    <div class="preview-item">
                        <strong>Status:</strong> 
                        <span class="badge ${enabled ? 'badge-success' : 'badge-secondary'}">
                            ${enabled ? 'Enabled' : 'Disabled'}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Cleanup when leaving page
     */
    destroy() {
        this.stopStatsRefresh();
        this.closeTestModal();
    }
}

// Create global instance and expose methods
window.ExtremeAI_Providers = ExtremeAI_Providers;
const providers = new ExtremeAI_Providers();

// Expose global methods for template compatibility
window.toggleProviderConfig = (provider) => providers.toggleProviderConfig(provider);
window.testProvider = (provider) => providers.testProvider(provider);
window.runProviderTest = () => providers.runProviderTest();
window.closeTestModal = () => providers.closeTestModal();
window.resetProviderForm = (provider) => providers.resetProviderForm(provider);

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    providers.destroy();
});