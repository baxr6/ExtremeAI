/**
 * ExtremeAI Installer JavaScript
 * Handles the web-based installation wizard functionality
 */

class ExtremeAIInstaller {
    constructor() {
        this.currentStep = 1;
        this.totalSteps = 4;
        this.requirements = {};
        this.dbConfig = {};
        this.installProgress = 0;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.checkRequirements();
        this.updateStepIndicator();
    }
    
    bindEvents() {
        // Step navigation
        document.querySelectorAll('.next-step').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.nextStep();
            });
        });
        
        document.querySelectorAll('.prev-step').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.prevStep();
            });
        });
        
        // Database test connection
        const testDbBtn = document.getElementById('test-db-connection');
        if (testDbBtn) {
            testDbBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.testDatabaseConnection();
            });
        }
        
        // Form validation
        document.querySelectorAll('input[required]').forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
        
        // Installation start
        const startInstallBtn = document.getElementById('start-installation');
        if (startInstallBtn) {
            startInstallBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.startInstallation();
            });
        }
    }
    
    checkRequirements() {
        const requirementsData = window.installationRequirements || {};
        this.requirements = requirementsData;
        
        this.updateRequirementsDisplay();
    }
    
    updateRequirementsDisplay() {
        const container = document.getElementById('requirements-list');
        if (!container) return;
        
        let allPassed = true;
        
        Object.entries(this.requirements).forEach(([key, requirement]) => {
            const item = container.querySelector(`[data-requirement="${key}"]`);
            if (!item) return;
            
            const status = item.querySelector('.requirement-status');
            const icon = status.querySelector('i');
            
            if (requirement.passed) {
                status.className = 'requirement-status passed';
                icon.className = 'fas fa-check-circle';
                status.title = 'Requirement met';
            } else {
                status.className = 'requirement-status failed';
                icon.className = 'fas fa-times-circle';
                status.title = requirement.message || 'Requirement not met';
                allPassed = false;
            }
        });
        
        // Update continue button state
        const continueBtn = document.querySelector('.next-step');
        if (continueBtn) {
            continueBtn.disabled = !allPassed;
            if (!allPassed) {
                continueBtn.innerHTML = '<i class="fas fa-times"></i> Requirements Not Met';
                continueBtn.classList.add('disabled');
            } else {
                continueBtn.innerHTML = '<i class="fas fa-arrow-right"></i> Continue';
                continueBtn.classList.remove('disabled');
            }
        }
    }
    
    nextStep() {
        if (!this.validateCurrentStep()) {
            return;
        }
        
        if (this.currentStep < this.totalSteps) {
            this.currentStep++;
            this.showStep(this.currentStep);
            this.updateStepIndicator();
        }
    }
    
    prevStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.showStep(this.currentStep);
            this.updateStepIndicator();
        }
    }
    
    showStep(stepNumber) {
        // Hide all steps
        document.querySelectorAll('.install-step').forEach(step => {
            step.style.display = 'none';
        });
        
        // Show current step
        const currentStepEl = document.getElementById(`step-${stepNumber}`);
        if (currentStepEl) {
            currentStepEl.style.display = 'block';
            
            // Scroll to top
            window.scrollTo(0, 0);
            
            // Focus on first input if available
            const firstInput = currentStepEl.querySelector('input, select, textarea');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    }
    
    updateStepIndicator() {
        const indicators = document.querySelectorAll('.step-indicator .step');
        indicators.forEach((indicator, index) => {
            const stepNum = index + 1;
            indicator.classList.remove('active', 'completed');
            
            if (stepNum < this.currentStep) {
                indicator.classList.add('completed');
            } else if (stepNum === this.currentStep) {
                indicator.classList.add('active');
            }
        });
        
        // Update progress bar
        const progress = ((this.currentStep - 1) / (this.totalSteps - 1)) * 100;
        const progressBar = document.querySelector('.progress-fill');
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
        }
    }
    
    validateCurrentStep() {
        const currentStepEl = document.getElementById(`step-${this.currentStep}`);
        if (!currentStepEl) return true;
        
        const requiredFields = currentStepEl.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        // Additional step-specific validation
        if (this.currentStep === 2) {
            return this.validateDatabaseStep();
        }
        
        return isValid;
    }
    
    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';
        
        // Required validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            message = 'This field is required';
        }
        
        // Type-specific validation
        if (value && field.type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                message = 'Please enter a valid email address';
            }
        }
        
        if (value && field.type === 'url') {
            try {
                new URL(value);
            } catch {
                isValid = false;
                message = 'Please enter a valid URL';
            }
        }
        
        // Database specific validation
        if (field.name === 'db_host' && value) {
            this.dbConfig.host = value;
        } else if (field.name === 'db_name' && value) {
            this.dbConfig.name = value;
        } else if (field.name === 'db_user' && value) {
            this.dbConfig.user = value;
        } else if (field.name === 'db_pass') {
            this.dbConfig.pass = value;
        }
        
        // Show/hide error
        this.showFieldError(field, isValid ? '' : message);
        
        return isValid;
    }
    
    showFieldError(field, message) {
        this.clearFieldError(field);
        
        if (message) {
            field.classList.add('error');
            
            const errorEl = document.createElement('div');
            errorEl.className = 'field-error';
            errorEl.textContent = message;
            
            field.parentNode.appendChild(errorEl);
        }
    }
    
    clearFieldError(field) {
        field.classList.remove('error');
        
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }
    
    validateDatabaseStep() {
        // Check if database connection test was successful
        const testResult = document.querySelector('.db-test-result.success');
        if (!testResult) {
            this.showNotification('Please test the database connection first', 'warning');
            return false;
        }
        
        return true;
    }
    
    testDatabaseConnection() {
        const testBtn = document.getElementById('test-db-connection');
        const resultContainer = document.getElementById('db-test-result');
        
        if (!testBtn || !resultContainer) return;
        
        // Show loading state
        testBtn.disabled = true;
        testBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
        resultContainer.innerHTML = '<div class="db-test-result testing"><i class="fas fa-spinner fa-spin"></i> Testing database connection...</div>';
        
        // Gather database config
        const formData = new FormData();
        formData.append('action', 'test_db_connection');
        formData.append('db_host', document.querySelector('input[name="db_host"]').value);
        formData.append('db_name', document.querySelector('input[name="db_name"]').value);
        formData.append('db_user', document.querySelector('input[name="db_user"]').value);
        formData.append('db_pass', document.querySelector('input[name="db_pass"]').value);
        formData.append('db_prefix', document.querySelector('input[name="db_prefix"]').value || 'nuke_');
        
        fetch('install.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultContainer.innerHTML = `
                    <div class="db-test-result success">
                        <i class="fas fa-check-circle"></i>
                        <strong>Connection Successful!</strong>
                        <p>${data.message}</p>
                    </div>
                `;
            } else {
                resultContainer.innerHTML = `
                    <div class="db-test-result error">
                        <i class="fas fa-times-circle"></i>
                        <strong>Connection Failed</strong>
                        <p>${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            resultContainer.innerHTML = `
                <div class="db-test-result error">
                    <i class="fas fa-times-circle"></i>
                    <strong>Connection Error</strong>
                    <p>Failed to test database connection: ${error.message}</p>
                </div>
            `;
        })
        .finally(() => {
            testBtn.disabled = false;
            testBtn.innerHTML = '<i class="fas fa-database"></i> Test Connection';
        });
    }
    
    startInstallation() {
        const startBtn = document.getElementById('start-installation');
        const progressContainer = document.getElementById('installation-progress');
        const progressBar = progressContainer.querySelector('.install-progress-fill');
        const progressText = progressContainer.querySelector('.install-progress-text');
        const logContainer = document.getElementById('installation-log');
        
        if (!startBtn) return;
        
        // Hide start button and show progress
        startBtn.style.display = 'none';
        progressContainer.style.display = 'block';
        logContainer.style.display = 'block';
        
        // Gather all form data
        const formData = new FormData();
        formData.append('action', 'install');
        
        // Add all form fields
        document.querySelectorAll('input, select, textarea').forEach(field => {
            if (field.name && field.value) {
                if (field.type === 'checkbox') {
                    formData.append(field.name, field.checked ? '1' : '0');
                } else {
                    formData.append(field.name, field.value);
                }
            }
        });
        
        this.runInstallation(formData, progressBar, progressText, logContainer);
    }
    
    runInstallation(formData, progressBar, progressText, logContainer) {
        fetch('install.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.handleInstallationSuccess(data, progressBar, progressText, logContainer);
            } else {
                this.handleInstallationError(data, logContainer);
            }
        })
        .catch(error => {
            this.handleInstallationError({
                message: `Installation failed: ${error.message}`,
                details: error.stack
            }, logContainer);
        });
    }
    
    handleInstallationSuccess(data, progressBar, progressText, logContainer) {
        // Update progress to 100%
        progressBar.style.width = '100%';
        progressText.textContent = 'Installation completed successfully!';
        
        // Show success log entries
        if (data.log) {
            data.log.forEach(entry => {
                this.addLogEntry(logContainer, entry.message, entry.type || 'info');
            });
        }
        
        // Show completion message
        setTimeout(() => {
            const completionDiv = document.createElement('div');
            completionDiv.className = 'installation-complete';
            completionDiv.innerHTML = `
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <h3>Installation Complete!</h3>
                    <p>ExtremeAI has been successfully installed.</p>
                    
                    <div class="next-steps">
                        <h4>Next Steps:</h4>
                        <ul>
                            <li>Configure your AI providers in the admin panel</li>
                            <li>Set up your API keys and test connections</li>
                            <li>Review system settings and performance options</li>
                            <li>Start using ExtremeAI in your content!</li>
                        </ul>
                    </div>
                    
                    <div class="completion-actions">
                        <a href="admin.php?op=extremeai_dashboard" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt"></i> Go to Admin Dashboard
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-home"></i> Visit Site
                        </a>
                    </div>
                </div>
            `;
            
            logContainer.appendChild(completionDiv);
            
            // Scroll to completion message
            completionDiv.scrollIntoView({ behavior: 'smooth' });
            
        }, 1000);
    }
    
    handleInstallationError(data, logContainer) {
        this.addLogEntry(logContainer, data.message || 'Unknown error occurred', 'error');
        
        if (data.details) {
            this.addLogEntry(logContainer, `Details: ${data.details}`, 'error');
        }
        
        // Show retry option
        const retryDiv = document.createElement('div');
        retryDiv.className = 'installation-error';
        retryDiv.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Installation Failed</h3>
                <p>There was an error during installation. Please check the log above for details.</p>
                
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-redo"></i> Start Over
                </button>
            </div>
        `;
        
        logContainer.appendChild(retryDiv);
    }
    
    addLogEntry(container, message, type = 'info') {
        const entry = document.createElement('div');
        entry.className = `log-entry log-${type}`;
        
        const timestamp = new Date().toLocaleTimeString();
        const icon = this.getLogIcon(type);
        
        entry.innerHTML = `
            <span class="log-time">${timestamp}</span>
            <i class="log-icon ${icon}"></i>
            <span class="log-message">${message}</span>
        `;
        
        container.appendChild(entry);
        
        // Auto-scroll to latest entry
        entry.scrollIntoView({ behavior: 'smooth', block: 'end' });
    }
    
    getLogIcon(type) {
        const icons = {
            'info': 'fas fa-info-circle',
            'success': 'fas fa-check-circle',
            'warning': 'fas fa-exclamation-triangle',
            'error': 'fas fa-times-circle'
        };
        
        return icons[type] || icons.info;
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="${this.getLogIcon(type)}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
    }
}

// Initialize installer when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.extreme-ai-installer')) {
        window.installer = new ExtremeAIInstaller();
    }
});