/**
 * ExtremeAI Test Console JavaScript
 * Interactive testing interface for AI providers
 */

class ExtremeAI_TestConsole {
    constructor() {
        this.currentTest = null;
        this.testHistory = [];
        this.isStreaming = false;
        
        this.init();
    }
    
    async init() {
        console.log('[Test Console] Initializing...');
        
        // Wait for ExtremeAI core to be ready
        const isReady = await this.waitForExtremeAI();
        if (!isReady) {
            console.error('[Test Console] ExtremeAI core not available');
            return;
        }
        
        this.setupEventListeners();
        this.loadTestHistory();
        this.setupFormValidation();
        this.updateCharacterCount();
        
        console.log('[Test Console] Successfully initialized');
    }
    
    async waitForExtremeAI(retries = 10) {
        for (let i = 0; i < retries; i++) {
            if (window.ExtremeAI && 
                typeof window.ExtremeAI.ajax === 'function' &&
                typeof window.ExtremeAI.showNotification === 'function') {
                return true;
            }
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        return false;
    }
    
    setupEventListeners() {
        // Test configuration
        document.getElementById('test-temperature')?.addEventListener('input', (e) => {
            document.getElementById('temperature-value').textContent = e.target.value;
        });
        
        // Input actions
        document.getElementById('load-sample')?.addEventListener('click', () => this.showSamplesModal());
        document.getElementById('clear-input')?.addEventListener('click', () => this.clearInput());
        
        // Test actions
        document.getElementById('run-test')?.addEventListener('click', () => this.runTest());
        document.getElementById('stop-test')?.addEventListener('click', () => this.stopTest());
        document.getElementById('save-test')?.addEventListener('click', () => this.saveTest());
        
        // Output actions
        document.getElementById('copy-output')?.addEventListener('click', () => this.copyOutput());
        document.getElementById('export-test')?.addEventListener('click', () => this.exportTest());
        
        // History actions
        document.getElementById('clear-history')?.addEventListener('click', () => this.clearHistory());
        
        // Character counter
        document.getElementById('user-prompt')?.addEventListener('input', () => this.updateCharacterCount());
        
        // Sample modal interactions
        document.querySelectorAll('.sample-item').forEach(item => {
            item.addEventListener('click', () => this.loadSample(item));
        });
    }
    
    setupFormValidation() {
        const form = document.getElementById('test-config-form');
        if (!form) return;
        
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', () => this.validateForm());
            input.addEventListener('blur', () => this.validateInput(input));
        });
    }
    
    validateForm() {
        const prompt = document.getElementById('user-prompt')?.value.trim();
        const runButton = document.getElementById('run-test');
        
        if (runButton) {
            runButton.disabled = !prompt || this.isStreaming;
        }
        
        return !!prompt;
    }
    
    validateInput(input) {
        // Add validation feedback classes
        input.classList.remove('is-invalid', 'is-valid');
        
        if (input.hasAttribute('required') && !input.value.trim()) {
            input.classList.add('is-invalid');
        } else if (input.value.trim()) {
            input.classList.add('is-valid');
        }
    }
    
    updateCharacterCount() {
        const prompt = document.getElementById('user-prompt');
        const counter = document.getElementById('prompt-chars');
        
        if (prompt && counter) {
            const count = prompt.value.length;
            counter.textContent = count;
            
            // Color coding based on length
            counter.className = '';
            if (count > 2000) {
                counter.classList.add('text-warning');
            } else if (count > 4000) {
                counter.classList.add('text-danger');
            }
        }
        
        this.validateForm();
    }
    
    clearInput() {
        ExtremeAI.confirm('Clear all input fields?', (confirmed) => {
            if (confirmed) {
                document.getElementById('system-prompt').value = '';
                document.getElementById('user-prompt').value = '';
                this.updateCharacterCount();
                ExtremeAI.showNotification('Input cleared', 'info');
            }
        });
    }
    
    showSamplesModal() {
        const modal = document.getElementById('samples-modal');
        const overlay = document.getElementById('modal-overlay');
        
        if (modal && overlay) {
            modal.style.display = 'block';
            overlay.style.display = 'block';
        }
    }
    
    closeSamplesModal() {
        const modal = document.getElementById('samples-modal');
        const overlay = document.getElementById('modal-overlay');
        
        if (modal && overlay) {
            modal.style.display = 'none';
            overlay.style.display = 'none';
        }
    }
    
    loadSample(sampleElement) {
        const taskType = sampleElement.dataset.task;
        const promptText = sampleElement.querySelector('p').textContent;
        
        // Set task type
        const taskSelect = document.getElementById('test-task-type');
        if (taskSelect) {
            taskSelect.value = taskType;
        }
        
        // Set prompt
        const promptTextarea = document.getElementById('user-prompt');
        if (promptTextarea) {
            promptTextarea.value = promptText;
            this.updateCharacterCount();
        }
        
        this.closeSamplesModal();
        ExtremeAI.showNotification('Sample loaded', 'success');
    }
    
    async runTest() {
        if (!this.validateForm() || this.isStreaming) {
            return;
        }
        
        const testData = this.gatherTestData();
        if (!testData) {
            ExtremeAI.showNotification('Please fill in required fields', 'warning');
            return;
        }
        
        // Check if any providers are configured
        const hasConfiguredProviders = this.hasConfiguredProviders();
        if (!hasConfiguredProviders && !testData.provider) {
            ExtremeAI.showNotification('No AI providers are configured. Please configure at least one provider first.', 'error');
            return;
        }
        
        this.isStreaming = true;
        this.updateTestUI('running');
        
        const startTime = Date.now();
        
        try {
            const result = await this.executeTest(testData);
            const endTime = Date.now();
            const responseTime = endTime - startTime;
            
            this.displayTestResult(result, responseTime, testData);
            this.addToHistory(testData, result, responseTime);
            
        } catch (error) {
            const endTime = Date.now();
            const responseTime = endTime - startTime;
            
            this.displayTestError(error.message, responseTime, testData);
            console.error('[Test Console] Test failed:', error);
            
        } finally {
            this.isStreaming = false;
            this.updateTestUI('idle');
        }
    }
    
    gatherTestData() {
        const provider = document.getElementById('test-provider')?.value;
        const taskType = document.getElementById('test-task-type')?.value;
        const systemPrompt = document.getElementById('system-prompt')?.value.trim();
        const userPrompt = document.getElementById('user-prompt')?.value.trim();
        const maxTokens = parseInt(document.getElementById('test-max-tokens')?.value) || 1000;
        const temperature = parseFloat(document.getElementById('test-temperature')?.value) || 0.7;
        const stream = document.getElementById('test-stream')?.checked || false;
        
        if (!userPrompt) {
            return null;
        }
        
        return {
            provider,
            task_type: taskType,
            system: systemPrompt,
            prompt: userPrompt,
            max_tokens: maxTokens,
            temperature,
            stream
        };
    }
    
    hasConfiguredProviders() {
        if (!TestConsole.providers) return false;
        
        return Object.keys(TestConsole.providers).some(key => {
            const provider = TestConsole.providers[key];
            const isEnabled = !!(provider.enabled) || provider.enabled === '1' || provider.enabled === 1;
            const hasApiKey = !!provider.api_key || key === 'ollama';
            const hasEndpoint = !!provider.api_endpoint || !!provider.default_endpoint;
            const hasModel = !!provider.model || !!provider.default_model;
            return isEnabled && hasApiKey && hasEndpoint && hasModel;
        });
    }
    
    async executeTest(testData) {
        const url = `${TestConsole.adminFile}.php?op=extremeai_test_console`;
        
        // Prepare form data
        const formData = new FormData();
        formData.append('ajax_action', 'run_test');
        formData.append('csrf_token', TestConsole.csrfToken);
        
        // Add test parameters
        Object.keys(testData).forEach(key => {
            if (testData[key] !== null && testData[key] !== undefined) {
                formData.append(key, testData[key]);
            }
        });
        
        console.log('[Test Console] Executing test:', testData);
        console.log('[Test Console] AJAX URL:', url);
        console.log('[Test Console] FormData contents:');
        for (let [key, value] of formData.entries()) {
            console.log(`[Test Console] ${key}:`, value);
        }
        
        const result = await ExtremeAI.ajax(url, {
            method: 'POST',
            body: formData
        });
        
        console.log('[Test Console] Raw AJAX result:', result);
        return result;
    }
    
    stopTest() {
        if (!this.isStreaming) return;
        
        // Cancel any ongoing requests
        this.isStreaming = false;
        this.updateTestUI('idle');
        
        ExtremeAI.showNotification('Test stopped', 'warning');
    }
    
    updateTestUI(state) {
        const runButton = document.getElementById('run-test');
        const stopButton = document.getElementById('stop-test');
        const outputDiv = document.getElementById('test-output');
        
        switch (state) {
            case 'running':
                if (runButton) {
                    runButton.disabled = true;
                    runButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running...';
                }
                if (stopButton) {
                    stopButton.style.display = 'inline-flex';
                }
                if (outputDiv) {
                    outputDiv.classList.add('loading');
                    outputDiv.innerHTML = '<div class="loading-message">Processing your request...</div>';
                }
                break;
                
            case 'idle':
                if (runButton) {
                    runButton.disabled = false;
                    runButton.innerHTML = '<i class="fas fa-play"></i> Run Test';
                }
                if (stopButton) {
                    stopButton.style.display = 'none';
                }
                if (outputDiv) {
                    outputDiv.classList.remove('loading');
                }
                break;
        }
        
        this.validateForm();
    }
    
    displayTestResult(result, responseTime, testData) {
        const outputDiv = document.getElementById('test-output');
        const metricsDiv = document.getElementById('test-metrics');
        
        if (outputDiv) {
            outputDiv.classList.remove('error', 'loading');
            outputDiv.classList.add('success');
            
            if (result.success && result.data && result.data.success) {
                // Display the AI response
                const response = result.data.data.content || result.data.data || 'No response content';
                outputDiv.textContent = response;
            } else {
                outputDiv.classList.remove('success');
                outputDiv.classList.add('error');
                outputDiv.textContent = result.data?.error || 'Test failed with unknown error';
            }
        }
        
        // Update metrics
        this.updateTestMetrics({
            responseTime,
            provider: testData.provider || 'Auto-selected',
            status: result.success && result.data && result.data.success ? 'Success' : 'Failed',
            tokensUsed: result.data?.data?.tokens_used || 'N/A',
            cost: result.data?.data?.cost || 'N/A'
        });
        
        if (metricsDiv) {
            metricsDiv.style.display = 'block';
        }
    }
    
    displayTestError(error, responseTime, testData) {
        const outputDiv = document.getElementById('test-output');
        const metricsDiv = document.getElementById('test-metrics');
        
        if (outputDiv) {
            outputDiv.classList.remove('success', 'loading');
            outputDiv.classList.add('error');
            outputDiv.textContent = `Error: ${error}`;
        }
        
        this.updateTestMetrics({
            responseTime,
            provider: testData.provider || 'Auto-selected',
            status: 'Failed',
            tokensUsed: 'N/A',
            cost: 'N/A'
        });
        
        if (metricsDiv) {
            metricsDiv.style.display = 'block';
        }
        
        ExtremeAI.showNotification(`Test failed: ${error}`, 'error');
    }
    
    updateTestMetrics(metrics) {
        document.getElementById('metric-response-time').textContent = `${metrics.responseTime}ms`;
        document.getElementById('metric-provider').textContent = metrics.provider;
        document.getElementById('metric-tokens').textContent = metrics.tokensUsed;
        document.getElementById('metric-cost').textContent = metrics.cost;
    }
    
    saveTest() {
        const testData = this.gatherTestData();
        if (!testData) {
            ExtremeAI.showNotification('No test data to save', 'warning');
            return;
        }
        
        const outputDiv = document.getElementById('test-output');
        const output = outputDiv ? outputDiv.textContent : '';
        
        if (!output || output.includes('Run a test to see results')) {
            ExtremeAI.showNotification('No test results to save', 'warning');
            return;
        }
        
        const savedTest = {
            ...testData,
            output,
            timestamp: new Date().toISOString(),
            id: Date.now()
        };
        
        this.testHistory.unshift(savedTest);
        this.saveTestHistory();
        this.renderTestHistory();
        
        ExtremeAI.showNotification('Test saved to history', 'success');
    }
    
    copyOutput() {
        const outputDiv = document.getElementById('test-output');
        if (!outputDiv) return;
        
        const text = outputDiv.textContent;
        if (!text || text.includes('Run a test to see results')) {
            ExtremeAI.showNotification('No output to copy', 'warning');
            return;
        }
        
        navigator.clipboard.writeText(text).then(() => {
            ExtremeAI.showNotification('Output copied to clipboard', 'success');
        }).catch(err => {
            console.error('[Test Console] Copy failed:', err);
            ExtremeAI.showNotification('Failed to copy output', 'error');
        });
    }
    
    exportTest() {
        const testData = this.gatherTestData();
        const outputDiv = document.getElementById('test-output');
        const output = outputDiv ? outputDiv.textContent : '';
        
        if (!testData || !output || output.includes('Run a test to see results')) {
            ExtremeAI.showNotification('No test data to export', 'warning');
            return;
        }
        
        const exportData = {
            ...testData,
            output,
            timestamp: new Date().toISOString(),
            exported_from: 'ExtremeAI Test Console'
        };
        
        const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `extremeai-test-${Date.now()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        
        URL.revokeObjectURL(url);
        ExtremeAI.showNotification('Test exported successfully', 'success');
    }
    
    addToHistory(testData, result, responseTime) {
        const historyItem = {
            ...testData,
            result,
            responseTime,
            timestamp: new Date().toISOString(),
            id: Date.now()
        };
        
        this.testHistory.unshift(historyItem);
        
        // Keep only last 50 tests
        if (this.testHistory.length > 50) {
            this.testHistory = this.testHistory.slice(0, 50);
        }
        
        this.saveTestHistory();
        this.renderTestHistory();
    }
    
    loadTestHistory() {
        try {
            const saved = localStorage.getItem('extremeai_test_history');
            if (saved) {
                this.testHistory = JSON.parse(saved);
            }
        } catch (error) {
            console.error('[Test Console] Failed to load test history:', error);
            this.testHistory = [];
        }
        
        this.renderTestHistory();
    }
    
    saveTestHistory() {
        try {
            localStorage.setItem('extremeai_test_history', JSON.stringify(this.testHistory));
        } catch (error) {
            console.error('[Test Console] Failed to save test history:', error);
        }
    }
    
    renderTestHistory() {
        const historyDiv = document.getElementById('test-history');
        if (!historyDiv) return;
        
        if (this.testHistory.length === 0) {
            historyDiv.innerHTML = '<div class="text-muted text-center">No test history</div>';
            return;
        }
        
        const html = this.testHistory.map(item => `
            <div class="history-item" onclick="testConsole.loadFromHistory('${item.id}')">
                <div class="history-meta">
                    ${new Date(item.timestamp).toLocaleString()} • ${item.task_type}
                </div>
                <div class="history-prompt">${item.prompt.substring(0, 80)}${item.prompt.length > 80 ? '...' : ''}</div>
            </div>
        `).join('');
        
        historyDiv.innerHTML = html;
    }
    
    loadFromHistory(id) {
        const item = this.testHistory.find(h => h.id == id);
        if (!item) return;
        
        // Load test configuration
        document.getElementById('test-provider').value = item.provider || '';
        document.getElementById('test-task-type').value = item.task_type || 'text_generation';
        document.getElementById('system-prompt').value = item.system || '';
        document.getElementById('user-prompt').value = item.prompt || '';
        document.getElementById('test-max-tokens').value = item.max_tokens || 1000;
        document.getElementById('test-temperature').value = item.temperature || 0.7;
        document.getElementById('test-stream').checked = item.stream || false;
        
        // Update displays
        document.getElementById('temperature-value').textContent = item.temperature || 0.7;
        this.updateCharacterCount();
        
        ExtremeAI.showNotification('Test loaded from history', 'info');
    }
    
    clearHistory() {
        ExtremeAI.confirm('Clear all test history?', (confirmed) => {
            if (confirmed) {
                this.testHistory = [];
                this.saveTestHistory();
                this.renderTestHistory();
                ExtremeAI.showNotification('Test history cleared', 'info');
            }
        });
    }
    
    destroy() {
        // Cleanup when page unloads
        this.stopTest();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.testConsole = new ExtremeAI_TestConsole();
});

// Global functions for template compatibility
window.closeSamplesModal = () => {
    if (window.testConsole) {
        window.testConsole.closeSamplesModal();
    }
};

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.testConsole) {
        window.testConsole.destroy();
    }
});