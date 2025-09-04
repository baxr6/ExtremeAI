/**
 * ExtremeAI Settings JavaScript
 * 
 * Handles settings page functionality including form validation,
 * dynamic settings management, and configuration import/export.
 * 
 * @version 2.0.0
 * @author Deano Welch
 */

class ExtremeAI_Settings {
    constructor() {
        this.originalSettings = {};
        this.hasChanges = false;
        this.adminFile = 'admin';
        
        this.init();
    }
    
    /**
     * Safe logging methods that check if ExtremeAI exists
     */
    log(...args) {
        try {
            if (window.ExtremeAI && typeof window.ExtremeAI.log === 'function') {
                window.ExtremeAI.log(...args);
            } else {
                console.log('[ExtremeAI Settings]', ...args);
            }
        } catch (e) {
            console.log('[ExtremeAI Settings]', ...args);
        }
    }
    
    error(...args) {
        try {
            if (window.ExtremeAI && typeof window.ExtremeAI.error === 'function') {
                window.ExtremeAI.error(...args);
            } else {
                console.error('[ExtremeAI Settings Error]', ...args);
            }
        } catch (e) {
            console.error('[ExtremeAI Settings Error]', ...args);
        }
    }
    
    /**
     * Initialize settings functionality
     */
    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupEventHandlers();
            this.captureOriginalSettings();
            this.initFormValidation();
            this.initTooltips();
            this.setupAutoSave();
        });
    }
    
    /**
     * Setup event handlers
     */
    setupEventHandlers() {
        // Form submission
        const settingsForm = document.querySelector('.settings-form');
        if (settingsForm) {
            settingsForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveSettings();
            });
        }
        
        // Reset to defaults button
        const resetButton = document.getElementById('reset-settings');
        if (resetButton) {
            resetButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.resetToDefaults();
            });
        }
        
        // Change detection
        const inputs = document.querySelectorAll('.settings-form input, .settings-form select, .settings-form textarea');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                this.detectChanges();
            });
            
            input.addEventListener('input', ExtremeAI.debounce(() => {
                this.detectChanges();
            }, 300));
        });
        
        // Advanced settings toggle
        const advancedToggle = document.querySelector('.advanced-settings-toggle');
        if (advancedToggle) {
            advancedToggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleAdvancedSettings();
            });
        }
        
        // Settings groups
        this.setupSettingsGroups();
        
        // Import/Export functionality
        this.setupImportExport();
        
        // Keyboard shortcuts
        this.setupKeyboardShortcuts();
    }
    
    /**
     * Capture original settings for change detection
     */
    captureOriginalSettings() {
        const form = document.querySelector('.settings-form');
        if (!form) return;
        
        const formData = new FormData(form);
        for (let [key, value] of formData.entries()) {
            this.originalSettings[key] = value;
        }
        
        // Handle checkboxes separately
        const checkboxes = form.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            this.originalSettings[checkbox.name] = checkbox.checked;
        });
    }
    
    /**
     * Detect if settings have changed
     */
    detectChanges() {
        const form = document.querySelector('.settings-form');
        if (!form) return;
        
        let hasChanges = false;
        const formData = new FormData(form);
        
        // Check regular inputs
        for (let [key, value] of formData.entries()) {
            if (this.originalSettings[key] !== value) {
                hasChanges = true;
                break;
            }
        }
        
        // Check checkboxes
        if (!hasChanges) {
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                if (this.originalSettings[checkbox.name] !== checkbox.checked) {
                    hasChanges = true;
                }
            });
        }
        
        this.hasChanges = hasChanges;
        this.updateSaveButtonState();
        this.showUnsavedChangesWarning(hasChanges);
    }
    
    /**
     * Update save button state
     */
    updateSaveButtonState() {
        const saveButton = document.querySelector('button[name="save_settings"]');
        if (!saveButton) return;
        
        if (this.hasChanges) {
            saveButton.classList.add('btn-warning');
            saveButton.classList.remove('btn-primary');
            saveButton.innerHTML = '<i class="fas fa-save"></i> Save Changes';
        } else {
            saveButton.classList.add('btn-primary');
            saveButton.classList.remove('btn-warning');
            saveButton.innerHTML = '<i class="fas fa-save"></i> Save Settings';
        }
    }
    
    /**
     * Show unsaved changes warning
     */
    showUnsavedChangesWarning(show) {
        let warning = document.querySelector('.unsaved-changes-warning');
        
        if (show && !warning) {
            warning = document.createElement('div');
            warning.className = 'unsaved-changes-warning alert alert-warning';
            warning.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                You have unsaved changes that will be lost if you leave this page.
                <button class="btn btn-sm btn-warning ml-2" onclick="ExtremeAI_Settings.instance.saveSettings()">
                    Save Now
                </button>
            `;
            
            const form = document.querySelector('.settings-form');
            if (form) {
                form.insertBefore(warning, form.firstChild);
            }
        } else if (!show && warning) {
            warning.remove();
        }
    }
    
    /**
     * Initialize form validation
     */
    initFormValidation() {
        const inputs = document.querySelectorAll('.settings-form input[type="number"]');
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateNumberInput(input);
            });
        });
        
        // Real-time validation for specific fields
        this.setupSpecificValidation();
    }
    
    /**
     * Validate number input
     */
    validateNumberInput(input) {
        const value = parseFloat(input.value);
        const min = parseFloat(input.getAttribute('min'));
        const max = parseFloat(input.getAttribute('max'));
        
        let isValid = true;
        let message = '';
        
        if (isNaN(value)) {
            isValid = false;
            message = 'Please enter a valid number';
        } else if (min !== null && value < min) {
            isValid = false;
            message = `Minimum value is ${min}`;
        } else if (max !== null && value > max) {
            isValid = false;
            message = `Maximum value is ${max}`;
        }
        
        this.setInputValidation(input, isValid, message);
        return isValid;
    }
    
    /**
     * Set input validation state
     */
    setInputValidation(input, isValid, message = '') {
        const formGroup = input.closest('.setting-group');
        const errorElement = formGroup?.querySelector('.validation-error');
        
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
                const error = document.createElement('div');
                error.className = 'validation-error text-danger mt-1';
                error.textContent = message;
                formGroup.appendChild(error);
            }
        }
    }
    
    /**
     * Setup specific field validation
     */
    setupSpecificValidation() {
        // Cache TTL validation
        const cacheTtlInput = document.querySelector('input[name="extreme_ai_cache_ttl"]');
        if (cacheTtlInput) {
            cacheTtlInput.addEventListener('input', () => {
                const value = parseInt(cacheTtlInput.value);
                if (value > 0 && value < 60) {
                    this.setInputValidation(cacheTtlInput, false, 'Cache TTL should be at least 60 seconds for optimal performance');
                } else {
                    this.setInputValidation(cacheTtlInput, true);
                }
            });
        }
        
        // Rate limit validation
        const rateLimitInput = document.querySelector('input[name="extreme_ai_rate_limit"]');
        if (rateLimitInput) {
            rateLimitInput.addEventListener('input', () => {
                const value = parseInt(rateLimitInput.value);
                if (value > 10000) {
                    this.setInputValidation(rateLimitInput, false, 'High rate limits may exceed provider quotas');
                } else {
                    this.setInputValidation(rateLimitInput, true);
                }
            });
        }
        
        // Max tokens validation
        const maxTokensInput = document.querySelector('input[name="extreme_ai_max_tokens"]');
        if (maxTokensInput) {
            maxTokensInput.addEventListener('input', () => {
                const value = parseInt(maxTokensInput.value);
                if (value > 32000) {
                    this.setInputValidation(maxTokensInput, false, 'Very high token limits may not be supported by all providers');
                } else if (value < 100) {
                    this.setInputValidation(maxTokensInput, false, 'Token limit too low for meaningful responses');
                } else {
                    this.setInputValidation(maxTokensInput, true);
                }
            });
        }
    }
    
    /**
     * Initialize tooltips
     */
    initTooltips() {
        const tooltips = document.querySelectorAll('[data-toggle="tooltip"]');
        tooltips.forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target);
            });
            
            element.addEventListener('mouseleave', (e) => {
                this.hideTooltip(e.target);
            });
        });
    }
    
    /**
     * Show tooltip
     */
    showTooltip(element) {
        const text = element.getAttribute('title') || element.getAttribute('data-title');
        if (!text) return;
        
        const tooltip = document.createElement('div');
        tooltip.className = 'eai-tooltip';
        tooltip.textContent = text;
        
        document.body.appendChild(tooltip);
        
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
        
        element._tooltip = tooltip;
        
        setTimeout(() => {
            tooltip.classList.add('show');
        }, 10);
    }
    
    /**
     * Hide tooltip
     */
    hideTooltip(element) {
        const tooltip = element._tooltip;
        if (tooltip) {
            tooltip.classList.remove('show');
            setTimeout(() => {
                if (tooltip.parentNode) {
                    tooltip.parentNode.removeChild(tooltip);
                }
            }, 200);
            element._tooltip = null;
        }
    }
    
    /**
     * Setup auto-save functionality
     */
    setupAutoSave() {
        let autoSaveTimeout;
        
        const inputs = document.querySelectorAll('.settings-form input, .settings-form select, .settings-form textarea');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(autoSaveTimeout);
                
                // Auto-save after 10 seconds of inactivity
                autoSaveTimeout = setTimeout(() => {
                    if (this.hasChanges) {
                        this.autoSave();
                    }
                }, 10000);
            });
        });
    }
    
    /**
     * Auto-save settings
     */
    async autoSave() {
        try {
            await this.saveSettings(true);
            ExtremeAI.showNotification('Settings auto-saved', 'success', 2000);
        } catch (error) {
            this.error('Auto-save failed:', error);
        }
    }
    
    /**
     * Setup settings groups (collapsible sections)
     */
    setupSettingsGroups() {
        const groupHeaders = document.querySelectorAll('.settings-section .section-header');
        groupHeaders.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.toggleSettingsGroup(header);
            });
        });
    }
    
    /**
     * Toggle settings group visibility
     */
    toggleSettingsGroup(header) {
        const section = header.closest('.settings-section');
        const content = section.querySelector('.settings-grid, .provider-settings-grid');
        
        if (!content) return;
        
        const isCollapsed = content.style.display === 'none';
        
        if (isCollapsed) {
            content.style.display = '';
            header.classList.remove('collapsed');
        } else {
            content.style.display = 'none';
            header.classList.add('collapsed');
        }
        
        // Save collapsed state
        const sectionId = section.id || header.textContent.trim().toLowerCase().replace(/\s+/g, '_');
        localStorage.setItem(`eai_settings_${sectionId}_collapsed`, !isCollapsed);
    }
    
    /**
     * Setup import/export functionality
     */
    setupImportExport() {
        // Export button
        const exportButton = document.querySelector('.export-settings');
        if (exportButton) {
            exportButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportSettings();
            });
        }
        
        // Import button
        const importButton = document.querySelector('.import-settings');
        const importFile = document.querySelector('.import-file');
        
        if (importButton && importFile) {
            importButton.addEventListener('click', (e) => {
                e.preventDefault();
                importFile.click();
            });
            
            importFile.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    this.importSettings(e.target.files[0]);
                }
            });
        }
    }
    
    /**
     * Setup keyboard shortcuts
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                this.saveSettings();
            }
            
            // Ctrl/Cmd + R to reset
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                this.resetToDefaults();
            }
        });
    }
    
    /**
     * Save settings
     */
    async saveSettings(silent = false) {
        const form = document.querySelector('.settings-form');
        if (!form) return;
        
        // Validate form
        if (!this.validateForm()) {
            if (!silent) {
                ExtremeAI.showNotification('Please fix validation errors before saving', 'error');
            }
            return;
        }
        
        const saveButton = form.querySelector('button[name="save_settings"]');
        const originalText = saveButton?.innerHTML;
        
        try {
            if (saveButton) {
                saveButton.disabled = true;
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            }
            
            const formData = new FormData(form);
            const url = form.action || window.location.href;
            
            const result = await ExtremeAI.ajax(url, {
                method: 'POST',
                body: formData
            });
            
            if (result.success) {
                this.captureOriginalSettings();
                this.hasChanges = false;
                this.updateSaveButtonState();
                this.showUnsavedChangesWarning(false);
                
                if (!silent) {
                    ExtremeAI.showNotification('Settings saved successfully!', 'success');
                }
            } else {
                throw new Error(result.error || 'Failed to save settings');
            }
            
        } catch (error) {
            this.error('Settings save failed:', error);
            if (!silent) {
                ExtremeAI.showNotification('Failed to save settings: ' + error.message, 'error');
            }
        } finally {
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.innerHTML = originalText;
            }
        }
    }
    
    /**
     * Validate entire form
     */
    validateForm() {
        const form = document.querySelector('.settings-form');
        if (!form) return true;
        
        let isValid = true;
        
        // Validate number inputs
        const numberInputs = form.querySelectorAll('input[type="number"]');
        numberInputs.forEach(input => {
            if (!this.validateNumberInput(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    /**
     * Reset settings to defaults
     */
    resetToDefaults() {
        ExtremeAI.confirm('Are you sure you want to reset all settings to default values? This action cannot be undone.', (confirmed) => {
            if (confirmed) {
                this.performReset();
            }
        });
    }
    
    /**
     * Perform settings reset
     */
    performReset() {
        const form = document.querySelector('.settings-form');
        if (!form) return;
        
        // Default values
        const defaults = {
            extreme_ai_debug: false,
            extreme_ai_cache_enabled: true,
            extreme_ai_cache_ttl: 3600,
            extreme_ai_max_tokens: 4096,
            extreme_ai_rate_limit: 1000,
            extreme_ai_default_timeout: 30,
            extreme_ai_max_concurrent_requests: 10,
            extreme_ai_auto_cleanup_days: 30,
            extreme_ai_log_level: 'error'
        };
        
        // Reset form values
        Object.keys(defaults).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = defaults[key];
                } else {
                    input.value = defaults[key];
                }
                
                // Trigger change event
                input.dispatchEvent(new Event('change'));
            }
        });
        
        this.detectChanges();
        ExtremeAI.showNotification('Settings have been reset to default values. Click "Save Settings" to apply changes.', 'warning', 8000);
    }
    
    /**
     * Toggle advanced settings
     */
    toggleAdvancedSettings() {
        const advancedSections = document.querySelectorAll('.settings-section[data-advanced="true"]');
        const toggle = document.querySelector('.advanced-settings-toggle');
        
        if (!toggle) return;
        
        const isShowing = toggle.dataset.showing === 'true';
        
        advancedSections.forEach(section => {
            if (isShowing) {
                section.style.display = 'none';
            } else {
                section.style.display = '';
            }
        });
        
        toggle.dataset.showing = !isShowing;
        toggle.innerHTML = isShowing ? 
            '<i class="fas fa-eye"></i> Show Advanced Settings' : 
            '<i class="fas fa-eye-slash"></i> Hide Advanced Settings';
    }
    
    /**
     * Export settings to JSON file
     */
    async exportSettings() {
        try {
            const url = `${this.adminFile}.php?op=extremeai_settings&ajax_action=export_settings`;
            const result = await ExtremeAI.ajax(url);
            
            if (result.success && result.data.success) {
                const settings = result.data.data;
                const filename = `extreme_ai_settings_${new Date().toISOString().slice(0, 10)}.json`;
                const blob = new Blob([JSON.stringify(settings, null, 2)], { type: 'application/json' });
                
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                ExtremeAI.showNotification('Settings exported successfully', 'success');
            } else {
                throw new Error(result.data?.error || 'Export failed');
            }
        } catch (error) {
            this.error('Settings export failed:', error);
            ExtremeAI.showNotification('Failed to export settings: ' + error.message, 'error');
        }
    }
    
    /**
     * Import settings from JSON file
     */
    async importSettings(file) {
        try {
            const text = await file.text();
            const settings = JSON.parse(text);
            
            const confirmed = await new Promise(resolve => {
                ExtremeAI.confirm('This will overwrite your current settings. Are you sure you want to continue?', resolve);
            });
            
            if (!confirmed) return;
            
            const formData = new FormData();
            formData.append('action', 'import_settings');
            formData.append('settings', JSON.stringify(settings));
            
            const url = `${this.adminFile}.php?op=extremeai_settings&ajax_action=import_settings`;
            const result = await ExtremeAI.ajax(url, {
                method: 'POST',
                body: formData
            });
            
            if (result.success && result.data.success) {
                // Reload page to reflect imported settings
                window.location.reload();
            } else {
                throw new Error(result.data?.error || 'Import failed');
            }
            
        } catch (error) {
            this.error('Settings import failed:', error);
            ExtremeAI.showNotification('Failed to import settings: ' + error.message, 'error');
        }
    }
    
    /**
     * Warn about unsaved changes before leaving
     */
    setupUnloadWarning() {
        window.addEventListener('beforeunload', (e) => {
            if (this.hasChanges) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
    }
}

// Create global instance
ExtremeAI_Settings.instance = new ExtremeAI_Settings();

// Setup unload warning
ExtremeAI_Settings.instance.setupUnloadWarning();