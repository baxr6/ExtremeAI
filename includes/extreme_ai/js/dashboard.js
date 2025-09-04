/**
 * ExtremeAI Dashboard JavaScript
 * 
 * Handles dashboard-specific functionality including real-time updates,
 * activity loading, and interactive dashboard widgets.
 * 
 * @version 2.0.0
 * @author Deano Welch
 */

class ExtremeAI_Dashboard {
    constructor() {
        this.refreshInterval = null;
        this.refreshRate = 30000; // 30 seconds
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
                console.log('[ExtremeAI Dashboard]', ...args);
            }
        } catch (e) {
            console.log('[ExtremeAI Dashboard]', ...args);
        }
    }
    
    error(...args) {
        try {
            if (window.ExtremeAI && typeof window.ExtremeAI.error === 'function') {
                window.ExtremeAI.error(...args);
            } else {
                console.error('[ExtremeAI Dashboard Error]', ...args);
            }
        } catch (e) {
            console.error('[ExtremeAI Dashboard Error]', ...args);
        }
    }
    
    /**
     * Initialize dashboard
     */
    init() {
        document.addEventListener('DOMContentLoaded', async () => {
            // Wait for ExtremeAI to be fully ready
            const isReady = await this.waitForExtremeAI();
            if (isReady) {
                this.setupEventHandlers();
                this.loadRecentActivity();
                this.startAutoRefresh();
                this.initWidgets();
            } else {
                console.error('[Dashboard] Failed to initialize - ExtremeAI not available');
            }
        });
    }
    
    /**
     * Safe logging methods that check if ExtremeAI exists
     */
    log(...args) {
        try {
            if (window.ExtremeAI && typeof window.ExtremeAI.log === 'function') {
                window.ExtremeAI.log(...args);
            } else {
                console.log('[ExtremeAI Dashboard]', ...args);
            }
        } catch (e) {
            console.log('[ExtremeAI Dashboard]', ...args);
        }
    }
    
    error(...args) {
        try {
            if (window.ExtremeAI && typeof window.ExtremeAI.error === 'function') {
                window.ExtremeAI.error(...args);
            } else {
                console.error('[ExtremeAI Dashboard Error]', ...args);
            }
        } catch (e) {
            console.error('[ExtremeAI Dashboard Error]', ...args);
        }
    }

    /**
     * Wait for ExtremeAI to be ready with all required methods
     */
    async waitForExtremeAI(retries = 50) {
        for (let i = 0; i < retries; i++) {
            if (window.ExtremeAI && 
                typeof window.ExtremeAI.ajax === 'function' &&
                typeof window.ExtremeAI.showNotification === 'function' &&
                typeof window.ExtremeAI.debounce === 'function') {
                return true;
            }
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        console.error('[ExtremeAI Dashboard] ExtremeAI core not ready after retries');
        return false;
    }
    
    /**
     * Setup event handlers
     */
    setupEventHandlers() {
        // Refresh button
        const refreshBtn = document.querySelector('.refresh-dashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.refreshDashboard();
            });
        }
        
        // Auto-refresh toggle
        const autoRefreshToggle = document.querySelector('.auto-refresh-toggle');
        if (autoRefreshToggle) {
            autoRefreshToggle.addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.startAutoRefresh();
                } else {
                    this.stopAutoRefresh();
                }
            });
        }
        
        // Refresh rate selector
        const refreshRateSelect = document.querySelector('.refresh-rate-select');
        if (refreshRateSelect) {
            refreshRateSelect.addEventListener('change', (e) => {
                this.refreshRate = parseInt(e.target.value) * 1000;
                if (this.refreshInterval) {
                    this.stopAutoRefresh();
                    this.startAutoRefresh();
                }
            });
        }
    }
    
    /**
     * Initialize dashboard widgets
     */
    initWidgets() {
        this.initStatusCards();
        this.initCharts();
        this.initActivityFeed();
    }
    
    /**
     * Initialize status cards
     */
    initStatusCards() {
        const statusCards = document.querySelectorAll('.status-card');
        statusCards.forEach(card => {
            card.addEventListener('click', () => {
                const action = card.dataset.action;
                if (action) {
                    this.handleStatusCardClick(action);
                }
            });
        });
    }
    
    /**
     * Handle status card clicks
     */
    handleStatusCardClick(action) {
        switch (action) {
            case 'view_usage':
                window.location.href = `${this.adminFile}?op=extremeai_analytics`;
                break;
            case 'manage_providers':
                window.location.href = `${this.adminFile}?op=extremeai_providers`;
                break;
            case 'view_errors':
                this.showErrorsModal();
                break;
            case 'system_health':
                this.showHealthModal();
                break;
        }
    }
    
    /**
     * Initialize charts
     */
    initCharts() {
        // Usage trend chart
        this.initUsageChart();
        
        // Provider performance chart
        this.initProviderChart();
        
        // Cost trend chart
        this.initCostChart();
    }
    
    /**
     * Initialize usage trend chart
     */
    initUsageChart() {
        const chartContainer = document.getElementById('usage-trend-chart');
        if (!chartContainer) return;
        
        this.loadChartData('usage_trend')
            .then(data => {
                this.renderLineChart(chartContainer, data, {
                    title: 'Usage Trend (Last 7 Days)',
                    color: '#007bff'
                });
            })
            .catch(error => {
                this.error('Failed to load usage chart:', error);
                chartContainer.innerHTML = '<p class="chart-error">Failed to load chart data</p>';
            });
    }
    
    /**
     * Initialize provider performance chart
     */
    initProviderChart() {
        const chartContainer = document.getElementById('provider-performance-chart');
        if (!chartContainer) return;
        
        this.loadChartData('provider_performance')
            .then(data => {
                this.renderBarChart(chartContainer, data, {
                    title: 'Provider Performance',
                    colors: ['#28a745', '#17a2b8', '#ffc107', '#dc3545']
                });
            })
            .catch(error => {
                this.error('Failed to load provider chart:', error);
                chartContainer.innerHTML = '<p class="chart-error">Failed to load chart data</p>';
            });
    }
    
    /**
     * Initialize cost trend chart
     */
    initCostChart() {
        const chartContainer = document.getElementById('cost-trend-chart');
        if (!chartContainer) return;
        
        this.loadChartData('cost_trend')
            .then(data => {
                this.renderLineChart(chartContainer, data, {
                    title: 'Cost Trend (Last 30 Days)',
                    color: '#dc3545',
                    format: 'currency'
                });
            })
            .catch(error => {
                this.error('Failed to load cost chart:', error);
                chartContainer.innerHTML = '<p class="chart-error">Failed to load chart data</p>';
            });
    }
    
    /**
     * Load chart data via AJAX
     */
    async loadChartData(type) {
        const url = `${this.adminFile}.php?op=extremeai_dashboard&ajax_action=get_chart_data&type=${type}`;
        const result = await ExtremeAI.ajax(url);
        
        if (result.success && result.data.success) {
            return result.data.data;
        } else {
            throw new Error(result.data?.error || 'Failed to load chart data');
        }
    }
    
    /**
     * Render line chart (simple SVG implementation)
     */
    renderLineChart(container, data, options = {}) {
        if (!data || !data.length) {
            container.innerHTML = '<p class="chart-empty">No data available</p>';
            return;
        }
        
        const width = 300;
        const height = 200;
        const padding = 40;
        
        const maxValue = Math.max(...data.map(d => d.value));
        const minValue = Math.min(...data.map(d => d.value));
        const range = maxValue - minValue || 1;
        
        let svg = `<svg width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">`;
        
        // Grid lines
        for (let i = 0; i <= 5; i++) {
            const y = padding + (height - 2 * padding) * (i / 5);
            svg += `<line x1="${padding}" y1="${y}" x2="${width - padding}" y2="${y}" stroke="#e9ecef" stroke-width="1"/>`;
        }
        
        // Data line
        let path = '';
        data.forEach((point, index) => {
            const x = padding + (width - 2 * padding) * (index / (data.length - 1));
            const y = height - padding - ((point.value - minValue) / range) * (height - 2 * padding);
            
            if (index === 0) {
                path += `M ${x} ${y}`;
            } else {
                path += ` L ${x} ${y}`;
            }
        });
        
        svg += `<path d="${path}" stroke="${options.color || '#007bff'}" stroke-width="2" fill="none"/>`;
        
        // Data points
        data.forEach((point, index) => {
            const x = padding + (width - 2 * padding) * (index / (data.length - 1));
            const y = height - padding - ((point.value - minValue) / range) * (height - 2 * padding);
            
            svg += `<circle cx="${x}" cy="${y}" r="4" fill="${options.color || '#007bff'}">`;
            svg += `<title>${point.label}: ${this.formatValue(point.value, options.format)}</title>`;
            svg += `</circle>`;
        });
        
        svg += '</svg>';
        
        container.innerHTML = `
            <div class="chart-header">
                <h4>${options.title || 'Chart'}</h4>
            </div>
            <div class="chart-content">${svg}</div>
        `;
    }
    
    /**
     * Render bar chart (simple SVG implementation)
     */
    renderBarChart(container, data, options = {}) {
        if (!data || !data.length) {
            container.innerHTML = '<p class="chart-empty">No data available</p>';
            return;
        }
        
        const width = 300;
        const height = 200;
        const padding = 40;
        const barWidth = (width - 2 * padding) / data.length - 10;
        
        const maxValue = Math.max(...data.map(d => d.value));
        
        let svg = `<svg width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">`;
        
        // Bars
        data.forEach((point, index) => {
            const x = padding + index * ((width - 2 * padding) / data.length);
            const barHeight = (point.value / maxValue) * (height - 2 * padding);
            const y = height - padding - barHeight;
            
            const color = options.colors?.[index % options.colors.length] || '#007bff';
            
            svg += `<rect x="${x}" y="${y}" width="${barWidth}" height="${barHeight}" fill="${color}">`;
            svg += `<title>${point.label}: ${this.formatValue(point.value, options.format)}</title>`;
            svg += `</rect>`;
        });
        
        svg += '</svg>';
        
        container.innerHTML = `
            <div class="chart-header">
                <h4>${options.title || 'Chart'}</h4>
            </div>
            <div class="chart-content">${svg}</div>
        `;
    }
    
    /**
     * Format chart value
     */
    formatValue(value, format) {
        switch (format) {
            case 'currency':
                return ExtremeAI.formatCurrency(value);
            case 'percentage':
                return `${value}%`;
            default:
                return ExtremeAI.formatNumber(value);
        }
    }
    
    /**
     * Initialize activity feed
     */
    initActivityFeed() {
        const activityFeed = document.getElementById('activity-feed');
        if (activityFeed) {
            // Real-time activity updates
            this.startActivityPolling();
        }
    }
    
    /**
     * Start activity polling
     */
    startActivityPolling() {
        // Poll for new activities every 10 seconds
        this.activityInterval = setInterval(() => {
            this.loadRecentActivity(true);
        }, 10000);
    }
    
    /**
     * Load recent activity
     */
    async loadRecentActivity(silent = false) {
        const loader = document.getElementById('recent-activity-loader');
        const content = document.getElementById('recent-activity-content');
        
        if (!silent && loader) {
            loader.style.display = 'block';
        }
        if (!silent && content) {
            content.style.display = 'none';
        }
        
        try {
            // Wait for ExtremeAI to be ready
            const isReady = await this.waitForExtremeAI();
            if (!isReady) {
                throw new Error('ExtremeAI core not available');
            }
            
            const url = `${this.adminFile}.php?op=extremeai_dashboard&ajax_action=get_recent_activity`;
            const result = await ExtremeAI.ajax(url);
            
            if (result.success && result.data.success) {
                const activities = result.data.data;
                const formattedContent = this.formatActivityData(activities);
                
                if (content) {
                    content.innerHTML = formattedContent;
                    content.style.display = 'block';
                }
                
                if (loader) {
                    loader.style.display = 'none';
                }
            } else {
                throw new Error(result.data?.error || 'Failed to load activity');
            }
        } catch (error) {
            this.error('Failed to load recent activity:', error);
            
            if (content) {
                content.innerHTML = '<p class="no-activity">Failed to load recent activity.</p>';
                content.style.display = 'block';
            }
            
            if (loader) {
                loader.style.display = 'none';
            }
        }
    }
    
    /**
     * Format activity data for display
     */
    formatActivityData(activities) {
        if (!activities || activities.length === 0) {
            return '<p class="no-activity">No recent activity found.</p>';
        }
        
        let html = '<div class="activity-list">';
        
        activities.forEach(activity => {
            const statusClass = this.getActivityStatusClass(activity.status);
            const icon = this.getActivityIcon(activity.type);
            const timeAgo = this.getTimeAgo(activity.timestamp);
            
            html += `
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-description">${ExtremeAI.escapeHtml(activity.description)}</div>
                        <div class="activity-meta">
                            <span class="activity-time">${timeAgo}</span>
                            <span class="activity-status ${statusClass}">${activity.status}</span>
                            ${activity.provider ? `<span class="activity-provider">${activity.provider}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }
    
    /**
     * Get activity status CSS class
     */
    getActivityStatusClass(status) {
        const statusMap = {
            'success': 'status-success',
            'error': 'status-error',
            'warning': 'status-warning',
            'info': 'status-info'
        };
        return statusMap[status?.toLowerCase()] || 'status-info';
    }
    
    /**
     * Get activity icon based on type
     */
    getActivityIcon(type) {
        const iconMap = {
            'request': 'fa-paper-plane',
            'error': 'fa-exclamation-triangle',
            'provider': 'fa-plug',
            'user': 'fa-user',
            'system': 'fa-cog',
            'task': 'fa-tasks'
        };
        return iconMap[type?.toLowerCase()] || 'fa-info-circle';
    }
    
    /**
     * Get human-readable time ago
     */
    getTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diffInSeconds = Math.floor((now - time) / 1000);
        
        if (diffInSeconds < 60) {
            return 'Just now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        }
    }
    
    /**
     * Show errors modal
     */
    async showErrorsModal() {
        const modal = ExtremeAI.showModal('Loading errors...', {
            title: 'Recent Errors'
        });
        
        try {
            const url = `${this.adminFile}.php?op=extremeai_dashboard&ajax_action=get_recent_errors`;
            const result = await ExtremeAI.ajax(url);
            
            if (result.success && result.data.success) {
                const errors = result.data.data;
                const content = this.formatErrorsData(errors);
                modal.querySelector('.eai-modal-body').innerHTML = content;
            } else {
                throw new Error(result.data?.error || 'Failed to load errors');
            }
        } catch (error) {
            modal.querySelector('.eai-modal-body').innerHTML = `<p class="error">Failed to load errors: ${error.message}</p>`;
        }
    }
    
    /**
     * Format errors data
     */
    formatErrorsData(errors) {
        if (!errors || errors.length === 0) {
            return '<p>No recent errors found.</p>';
        }
        
        let html = '<div class="errors-list">';
        errors.forEach(error => {
            html += `
                <div class="error-item">
                    <div class="error-level error-level-${error.level}">${error.level}</div>
                    <div class="error-message">${ExtremeAI.escapeHtml(error.message)}</div>
                    <div class="error-time">${ExtremeAI.formatDate(error.created)}</div>
                </div>
            `;
        });
        html += '</div>';
        
        return html;
    }
    
    /**
     * Show health modal
     */
    async showHealthModal() {
        const modal = ExtremeAI.showModal('Running health check...', {
            title: 'System Health'
        });
        
        try {
            const url = `${this.adminFile}.php?op=extremeai_dashboard&ajax_action=health_check`;
            const result = await ExtremeAI.ajax(url);
            
            if (result.success && result.data.success) {
                const health = result.data.data;
                const content = this.formatHealthData(health);
                modal.querySelector('.eai-modal-body').innerHTML = content;
            } else {
                throw new Error(result.data?.error || 'Health check failed');
            }
        } catch (error) {
            modal.querySelector('.eai-modal-body').innerHTML = `<p class="error">Health check failed: ${error.message}</p>`;
        }
    }
    
    /**
     * Format health data
     */
    formatHealthData(health) {
        const statusClass = health.status === 'healthy' ? 'success' : 
                           health.status === 'warning' ? 'warning' : 'error';
        
        let html = `
            <div class="health-status health-status-${statusClass}">
                <h4>Overall Status: ${health.status.toUpperCase()}</h4>
            </div>
        `;
        
        if (health.issues && health.issues.length > 0) {
            html += '<div class="health-issues"><h5>Issues:</h5><ul>';
            health.issues.forEach(issue => {
                html += `<li class="health-issue">${ExtremeAI.escapeHtml(issue)}</li>`;
            });
            html += '</ul></div>';
        }
        
        if (health.recommendations && health.recommendations.length > 0) {
            html += '<div class="health-recommendations"><h5>Recommendations:</h5><ul>';
            health.recommendations.forEach(rec => {
                html += `<li class="health-recommendation">${ExtremeAI.escapeHtml(rec)}</li>`;
            });
            html += '</ul></div>';
        }
        
        return html;
    }
    
    /**
     * Start auto-refresh
     */
    startAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        this.refreshInterval = setInterval(() => {
            this.refreshDashboard(true);
        }, this.refreshRate);
        
        this.log('Auto-refresh started:', this.refreshRate + 'ms');
    }
    
    /**
     * Stop auto-refresh
     */
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
        
        if (this.activityInterval) {
            clearInterval(this.activityInterval);
            this.activityInterval = null;
        }
        
        this.log('Auto-refresh stopped');
    }
    
    /**
     * Refresh entire dashboard
     */
    async refreshDashboard(silent = false) {
        if (!silent) {
            ExtremeAI.showNotification('Refreshing dashboard...', 'info', 2000);
        }
        
        try {
            // Refresh activity feed
            await this.loadRecentActivity(silent);
            
            // Refresh charts if they exist
            this.initCharts();
            
            // Update status cards
            await this.updateStatusCards();
            
            if (!silent) {
                ExtremeAI.showNotification('Dashboard refreshed successfully', 'success', 3000);
            }
            
        } catch (error) {
            this.error('Dashboard refresh failed:', error);
            if (!silent) {
                ExtremeAI.showNotification('Failed to refresh dashboard', 'error', 5000);
            }
        }
    }
    
    /**
     * Update status cards with fresh data
     */
    async updateStatusCards() {
        try {
            const url = `${this.adminFile}.php?op=extremeai_dashboard&ajax_action=get_dashboard_stats`;
            const result = await ExtremeAI.ajax(url);
            
            if (result.success && result.data.success) {
                const stats = result.data.data;
                this.updateStatusCardValues(stats);
            }
        } catch (error) {
            this.error('Failed to update status cards:', error);
        }
    }
    
    /**
     * Update status card values
     */
    updateStatusCardValues(stats) {
        // Update requests today
        const requestsEl = document.querySelector('.stat-requests .stat-number');
        if (requestsEl && stats.requests_today !== undefined) {
            requestsEl.textContent = ExtremeAI.formatNumber(stats.requests_today);
        }
        
        // Update response time
        const responseTimeEl = document.querySelector('.stat-response-time .stat-number');
        if (responseTimeEl && stats.avg_response_time !== undefined) {
            responseTimeEl.textContent = stats.avg_response_time + 's';
        }
        
        // Update cost
        const costEl = document.querySelector('.stat-cost .stat-number');
        if (costEl && stats.costs_today !== undefined) {
            costEl.textContent = ExtremeAI.formatCurrency(stats.costs_today);
        }
        
        // Update error rate
        const errorRateEl = document.querySelector('.stat-error-rate .stat-number');
        if (errorRateEl && stats.error_rate !== undefined) {
            errorRateEl.textContent = stats.error_rate + '%';
        }
    }
    
    /**
     * Cleanup when leaving page
     */
    destroy() {
        this.stopAutoRefresh();
    }
}

// Initialize dashboard when DOM is ready
const dashboard = new ExtremeAI_Dashboard();

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    dashboard.destroy();
});