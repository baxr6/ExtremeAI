/**
 * ExtremeAI Core JavaScript Library
 * 
 * Core functionality for ExtremeAI admin interface including AJAX requests,
 * utility functions, and common UI interactions.
 * 
 * @version 2.0.0
 * @author Deano Welch
 */

class ExtremeAI_Core {
    constructor() {
        this.baseUrl = window.location.origin;
        this.adminFile = 'admin';
        this.csrfToken = null;
        this.debug = false;
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }
    
    /**
     * Initialize the library
     */
    init() {
        this.initCSRF();
        this.initGlobalHandlers();
        this.log('ExtremeAI Core initialized');
    }
    
    /**
     * Initialize CSRF token from meta tag or form
     */
    initCSRF() {
        // Try to get from direct assignment first (set by templates)
        if (this.csrfToken) {
            return;
        }
        
        // Try to get from global ExtremeAI object
        if (window.ExtremeAI && window.ExtremeAI.csrfToken) {
            this.csrfToken = window.ExtremeAI.csrfToken;
            return;
        }
        
        // Try to get CSRF from meta tag
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            this.csrfToken = csrfMeta.getAttribute('content');
            return;
        }
        
        // Try to get CSRF from hidden input
        const csrfInput = document.querySelector('input[name="csrf_token"]');
        if (csrfInput) {
            this.csrfToken = csrfInput.value;
            return;
        }
        
        // If not found, try again after a short delay (template might not be rendered yet)
        if (!this.csrfRetryCount) {
            this.csrfRetryCount = 0;
        }
        
        if (this.csrfRetryCount < 10) { // Increased retries
            this.csrfRetryCount++;
            setTimeout(() => this.initCSRF(), 200); // Increased delay
            return;
        }
        
        // Don't warn if we have no forms that need CSRF - just log for debugging
        this.log('CSRF token not found after retries - may not be needed for this page');
    }
    
    /**
     * Manually set CSRF token (called from templates)
     */
    setCsrfToken(token) {
        this.csrfToken = token;
        this.csrfRetryCount = 999; // Stop retries since we have the token
        this.log('CSRF token set manually');
    }
    
    /**
     * Initialize global event handlers
     */
    initGlobalHandlers() {
        // Global escape key handler for modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
        
        // Global click handler for modal overlays
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                this.closeAllModals();
            }
        });
        
        // Global form submission handler for CSRF
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.tagName === 'FORM' && !form.querySelector('[name="csrf_token"]') && this.csrfToken) {
                this.addCSRFToForm(form);
            }
        });
    }
    
    /**
     * Add CSRF token to form
     */
    addCSRFToForm(form) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = this.csrfToken;
        form.appendChild(csrfInput);
    }
    
    /**
     * Make AJAX request
     */
    async ajax(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };
        
        const config = { ...defaultOptions, ...options };
        
        // Add CSRF token to POST requests
        if (config.method === 'POST' && this.csrfToken) {
            if (config.body instanceof FormData) {
                config.body.append('csrf_token', this.csrfToken);
            } else if (typeof config.body === 'string') {
                config.body += (config.body ? '&' : '') + 'csrf_token=' + encodeURIComponent(this.csrfToken);
            } else if (config.body) {
                config.body.csrf_token = this.csrfToken;
                config.body = new URLSearchParams(config.body).toString();
            }
        }
        
        try {
            this.log('AJAX Request:', url, config);
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                data = await response.text();
            }
            
            this.log('AJAX Response:', data);
            return { success: true, data, response };
            
        } catch (error) {
            this.error('AJAX Error:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * Show loading indicator
     */
    showLoading(element, text = 'Loading...') {
        if (typeof element === 'string') {
            element = document.getElementById(element);
        }
        
        if (!element) return;
        
        const loader = document.createElement('div');
        loader.className = 'eai-loading';
        loader.innerHTML = `
            <div class="eai-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <span>${text}</span>
            </div>
        `;
        
        element.style.position = 'relative';
        element.appendChild(loader);
        
        return loader;
    }
    
    /**
     * Hide loading indicator
     */
    hideLoading(element) {
        if (typeof element === 'string') {
            element = document.getElementById(element);
        }
        
        if (!element) return;
        
        const loader = element.querySelector('.eai-loading');
        if (loader) {
            loader.remove();
        }
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `eai-notification eai-notification-${type}`;
        notification.innerHTML = `
            <div class="eai-notification-content">
                <i class="fas ${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
                <button class="eai-notification-close">&times;</button>
            </div>
        `;
        
        // Add to container or create one
        let container = document.querySelector('.eai-notifications');
        if (!container) {
            container = document.createElement('div');
            container.className = 'eai-notifications';
            document.body.appendChild(container);
        }
        
        container.appendChild(notification);
        
        // Auto-remove after duration
        if (duration > 0) {
            setTimeout(() => {
                this.removeNotification(notification);
            }, duration);
        }
        
        // Close button handler
        const closeBtn = notification.querySelector('.eai-notification-close');
        closeBtn.addEventListener('click', () => {
            this.removeNotification(notification);
        });
        
        // Animate in
        requestAnimationFrame(() => {
            notification.classList.add('eai-notification-show');
        });
        
        return notification;
    }
    
    /**
     * Remove notification
     */
    removeNotification(notification) {
        notification.classList.remove('eai-notification-show');
        notification.classList.add('eai-notification-hide');
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
    
    /**
     * Get notification icon based on type
     */
    getNotificationIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }
    
    /**
     * Show modal
     */
    showModal(content, options = {}) {
        const modal = document.createElement('div');
        modal.className = 'eai-modal';
        modal.innerHTML = `
            <div class="eai-modal-overlay"></div>
            <div class="eai-modal-container">
                <div class="eai-modal-header">
                    <h3>${options.title || 'ExtremeAI'}</h3>
                    <button class="eai-modal-close">&times;</button>
                </div>
                <div class="eai-modal-body">
                    ${content}
                </div>
                ${options.footer ? `<div class="eai-modal-footer">${options.footer}</div>` : ''}
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Event handlers
        const closeBtn = modal.querySelector('.eai-modal-close');
        const overlay = modal.querySelector('.eai-modal-overlay');
        
        const closeModal = () => {
            modal.classList.add('eai-modal-closing');
            setTimeout(() => {
                if (modal.parentNode) {
                    modal.parentNode.removeChild(modal);
                }
            }, 300);
        };
        
        closeBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', closeModal);
        
        // Show modal
        requestAnimationFrame(() => {
            modal.classList.add('eai-modal-show');
        });
        
        return modal;
    }
    
    /**
     * Close all modals
     */
    closeAllModals() {
        const modals = document.querySelectorAll('.eai-modal, .modal');
        modals.forEach(modal => {
            const closeBtn = modal.querySelector('.eai-modal-close, .modal-close');
            if (closeBtn) {
                closeBtn.click();
            } else {
                modal.style.display = 'none';
            }
        });
    }
    
    /**
     * Confirm dialog
     */
    confirm(message, callback) {
        const modal = this.showModal(`
            <p>${message}</p>
        `, {
            title: 'Confirmation',
            footer: `
                <button class="btn btn-primary eai-confirm-yes">Yes</button>
                <button class="btn btn-secondary eai-confirm-no">No</button>
            `
        });
        
        const yesBtn = modal.querySelector('.eai-confirm-yes');
        const noBtn = modal.querySelector('.eai-confirm-no');
        
        yesBtn.addEventListener('click', () => {
            modal.querySelector('.eai-modal-close').click();
            callback(true);
        });
        
        noBtn.addEventListener('click', () => {
            modal.querySelector('.eai-modal-close').click();
            callback(false);
        });
        
        return modal;
    }
    
    /**
     * Format number with commas
     */
    formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    /**
     * Format currency
     */
    formatCurrency(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }
    
    /**
     * Format date
     */
    formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        
        return new Intl.DateTimeFormat('en-US', { ...defaultOptions, ...options }).format(new Date(date));
    }
    
    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Debounce function
     */
    debounce(func, wait, immediate = false) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func(...args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func(...args);
        };
    }
    
    /**
     * Throttle function
     */
    throttle(func, limit) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    /**
     * Logging functions
     */
    log(...args) {
        if (this.debug) {
            console.log('[ExtremeAI]', ...args);
        }
    }
    
    warn(...args) {
        console.warn('[ExtremeAI Warning]', ...args);
    }
    
    error(...args) {
        console.error('[ExtremeAI Error]', ...args);
    }
    
    /**
     * Enable debug mode
     */
    enableDebug() {
        this.debug = true;
        this.log('Debug mode enabled');
    }
    
    /**
     * Disable debug mode
     */
    disableDebug() {
        this.debug = false;
    }
}

// Create global instance
try {
    window.ExtremeAI = new ExtremeAI_Core();
    console.log('[ExtremeAI Core] Successfully initialized with methods:', Object.getOwnPropertyNames(ExtremeAI_Core.prototype));
    console.log('[ExtremeAI Core] Instance methods available:', {
        ajax: typeof window.ExtremeAI.ajax,
        setCsrfToken: typeof window.ExtremeAI.setCsrfToken,
        showNotification: typeof window.ExtremeAI.showNotification,
        debounce: typeof window.ExtremeAI.debounce
    });
} catch (error) {
    console.error('[ExtremeAI Core] Failed to initialize:', error);
}

// Helper function to ensure ExtremeAI is ready
if (window.ExtremeAI) {
    window.ExtremeAI.ready = function(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    };
    console.log('[ExtremeAI Core] Helper functions added');
    console.log('[ExtremeAI Core] Final verification - ajax method exists:', typeof window.ExtremeAI.ajax === 'function');
    console.log('[ExtremeAI Core] Final verification - setCsrfToken method exists:', typeof window.ExtremeAI.setCsrfToken === 'function');
    
    // Additional verification for debugging
    console.log('[ExtremeAI Core] window.ExtremeAI type:', typeof window.ExtremeAI);
    console.log('[ExtremeAI Core] window.ExtremeAI constructor:', window.ExtremeAI.constructor.name);
    console.log('[ExtremeAI Core] Available instance methods:', Object.getOwnPropertyNames(window.ExtremeAI));
    
    // Test that methods are actually callable
    if (typeof window.ExtremeAI.ajax === 'function') {
        console.log('[ExtremeAI Core] ✅ ajax method is callable');
    } else {
        console.error('[ExtremeAI Core] ❌ ajax method is NOT callable');
    }
    
    if (typeof window.ExtremeAI.setCsrfToken === 'function') {
        console.log('[ExtremeAI Core] ✅ setCsrfToken method is callable');
    } else {
        console.error('[ExtremeAI Core] ❌ setCsrfToken method is NOT callable');
    }
} else {
    console.error('[ExtremeAI Core] Failed to add helper functions - ExtremeAI object missing');
}

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ExtremeAI_Core;
}