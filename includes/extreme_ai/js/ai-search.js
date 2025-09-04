/**
 * ExtremeAI Search JavaScript
 *
 * Interactive AI-powered search functionality with real-time suggestions,
 * query expansion, and intelligent result ranking.
 *
 * @version 2.0.0
 * @author Deano Welch
 */

class ExtremeAI_Search {
    constructor() {
        this.searchForm = null;
        this.searchInput = null;
        this.searchResults = null;
        this.searchLoading = null;
        this.searchStatus = null;
        this.suggestionsContainer = null;
        
        this.currentQuery = '';
        this.searchTimeout = null;
        this.suggestionsTimeout = null;
        this.searchHistory = [];
        this.maxHistoryItems = 10;
        
        this.isSearching = false;
        this.suggestionsVisible = false;
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }
    
    async init() {
        // Wait for ExtremeAI core to be available
        const coreReady = await this.waitForExtremeAI();
        if (!coreReady) {
            console.error('[AI Search] ExtremeAI core not available');
            return;
        }
        
        this.setupElements();
        this.setupEventListeners();
        this.loadSearchHistory();
        
        console.log('[AI Search] Initialized successfully');
    }
    
    async waitForExtremeAI(retries = 20) {
        for (let i = 0; i < retries; i++) {
            if (window.ExtremeAI && typeof window.ExtremeAI.ajax === 'function') {
                return true;
            }
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        return false;
    }
    
    setupElements() {
        this.searchForm = document.getElementById('ai-search-form');
        this.searchInput = document.getElementById('search-query');
        this.searchResults = document.getElementById('search-results');
        this.searchLoading = document.getElementById('search-loading');
        this.searchStatus = document.getElementById('search-status');
        this.suggestionsContainer = document.getElementById('search-suggestions');
        
        if (!this.searchForm || !this.searchInput) {
            console.error('[AI Search] Required elements not found');
            return;
        }
        
        console.log('[AI Search] Elements initialized');
    }
    
    setupEventListeners() {
        // Search form submission
        this.searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.performSearch();
        });
        
        // Real-time search suggestions
        this.searchInput.addEventListener('input', () => {
            this.handleSearchInput();
        });
        
        // Keyboard navigation for suggestions
        this.searchInput.addEventListener('keydown', (e) => {
            this.handleKeyboardNavigation(e);
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-input-container')) {
                this.hideSuggestions();
            }
        });
        
        // Search history
        const clearHistoryBtn = document.getElementById('clear-history');
        if (clearHistoryBtn) {
            clearHistoryBtn.addEventListener('click', () => {
                this.clearSearchHistory();
            });
        }
        
        // Search option changes
        const expandQueryCheckbox = document.getElementById('expand-query');
        if (expandQueryCheckbox) {
            expandQueryCheckbox.addEventListener('change', () => {
                if (this.currentQuery) {
                    this.performSearch();
                }
            });
        }
    }
    
    handleSearchInput() {
        const query = this.searchInput.value.trim();
        
        // Clear previous timeout
        if (this.suggestionsTimeout) {
            clearTimeout(this.suggestionsTimeout);
        }
        
        if (query.length < 2) {
            this.hideSuggestions();
            return;
        }
        
        // Debounce suggestions
        this.suggestionsTimeout = setTimeout(() => {
            this.getSuggestions(query);
        }, 300);
    }
    
    async getSuggestions(query) {
        if (!AISearch.suggestionsEnabled || query.length < 2) {
            return;
        }
        
        try {
            const url = `${AISearch.adminFile}.php?op=extremeai_search`;
            const result = await ExtremeAI.ajax(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }, `ajax_action=search_suggestions&q=${encodeURIComponent(query)}&limit=5`);
            
            if (result.success && result.data && result.data.success) {
                this.displaySuggestions(result.data.suggestions);
            }
        } catch (error) {
            console.error('[AI Search] Suggestions error:', error);
        }
    }
    
    displaySuggestions(suggestions) {
        if (!suggestions || suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        const html = suggestions.map(suggestion => 
            `<div class="suggestion-item" data-suggestion="${ExtremeAI.escapeHtml(suggestion)}">${ExtremeAI.escapeHtml(suggestion)}</div>`
        ).join('');
        
        this.suggestionsContainer.innerHTML = html;
        this.suggestionsContainer.style.display = 'block';
        this.suggestionsVisible = true;
        
        // Add click handlers
        this.suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                this.searchInput.value = item.dataset.suggestion;
                this.hideSuggestions();
                this.performSearch();
            });
        });
    }
    
    hideSuggestions() {
        if (this.suggestionsContainer) {
            this.suggestionsContainer.style.display = 'none';
            this.suggestionsVisible = false;
        }
    }
    
    handleKeyboardNavigation(e) {
        if (!this.suggestionsVisible) return;
        
        const suggestions = this.suggestionsContainer.querySelectorAll('.suggestion-item');
        const highlighted = this.suggestionsContainer.querySelector('.suggestion-item.highlighted');
        let currentIndex = highlighted ? Array.from(suggestions).indexOf(highlighted) : -1;
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                currentIndex = Math.min(currentIndex + 1, suggestions.length - 1);
                this.highlightSuggestion(suggestions, currentIndex);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                currentIndex = Math.max(currentIndex - 1, 0);
                this.highlightSuggestion(suggestions, currentIndex);
                break;
                
            case 'Enter':
                if (highlighted) {
                    e.preventDefault();
                    this.searchInput.value = highlighted.dataset.suggestion;
                    this.hideSuggestions();
                    this.performSearch();
                }
                break;
                
            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }
    
    highlightSuggestion(suggestions, index) {
        suggestions.forEach(item => item.classList.remove('highlighted'));
        if (suggestions[index]) {
            suggestions[index].classList.add('highlighted');
        }
    }
    
    async performSearch() {
        const query = this.searchInput.value.trim();
        if (!query || this.isSearching) {
            return;
        }
        
        this.currentQuery = query;
        this.isSearching = true;
        
        // Hide suggestions
        this.hideSuggestions();
        
        // Show loading state
        this.showLoading();
        
        // Add to search history
        this.addToSearchHistory(query);
        
        try {
            // Collect form data
            const formData = new FormData(this.searchForm);
            formData.append('ajax_action', 'ai_search');
            
            console.log('[AI Search] Performing search for:', query);
            
            const url = `${AISearch.adminFile}.php?op=extremeai_search`;
            const result = await ExtremeAI.ajax(url, {
                method: 'POST',
                body: formData
            });
            
            console.log('[AI Search] Search result:', result);
            
            if (result.success && result.data) {
                this.displayResults(result.data);
            } else {
                this.displayError(result.error || 'Search failed');
            }
            
        } catch (error) {
            console.error('[AI Search] Search error:', error);
            this.displayError('Search request failed');
        } finally {
            this.hideLoading();
            this.isSearching = false;
        }
    }
    
    showLoading() {
        if (this.searchLoading) {
            this.searchLoading.style.display = 'block';
        }
        if (this.searchResults) {
            this.searchResults.style.display = 'none';
        }
        if (this.searchStatus) {
            this.searchStatus.style.display = 'none';
        }
        
        // Animate loading steps
        const steps = ['expand', 'search', 'rank'];
        steps.forEach((step, index) => {
            setTimeout(() => {
                const stepElement = document.querySelector(`[data-step="${step}"]`);
                if (stepElement) {
                    stepElement.classList.add('active');
                }
            }, index * 500);
        });
    }
    
    hideLoading() {
        if (this.searchLoading) {
            this.searchLoading.style.display = 'none';
        }
        
        // Reset loading steps
        document.querySelectorAll('.step.active').forEach(step => {
            step.classList.remove('active');
        });
    }
    
    displayResults(data) {
        if (this.searchResults) {
            this.searchResults.style.display = 'block';
        }
        
        // Show search status
        if (this.searchStatus && data.success) {
            this.displaySearchStatus(data);
        }
        
        if (!data.success) {
            this.displayError(data.error);
            return;
        }
        
        const results = data.results || [];
        
        if (results.length === 0) {
            this.displayNoResults();
            return;
        }
        
        const html = results.map(result => this.renderResultItem(result)).join('');
        this.searchResults.innerHTML = html;
        this.searchResults.classList.add('fade-in');
    }
    
    displaySearchStatus(data) {
        const resultsCount = document.getElementById('results-count');
        const responseTime = document.getElementById('response-time');
        const queryExpansion = document.getElementById('query-expansion');
        
        if (resultsCount) {
            resultsCount.textContent = data.total_found || 0;
        }
        
        if (responseTime && data.response_time) {
            responseTime.textContent = `in ${data.response_time}s`;
        }
        
        if (queryExpansion && data.expanded_query && data.expanded_query !== data.original_query) {
            queryExpansion.textContent = `Expanded: ${data.expanded_query}`;
            queryExpansion.style.display = 'inline-block';
        } else if (queryExpansion) {
            queryExpansion.style.display = 'none';
        }
        
        this.searchStatus.style.display = 'block';
    }
    
    renderResultItem(result) {
        const typeClass = result.type.replace('_', '');
        const typeLabel = result.type.replace('_', ' ').toUpperCase();
        const scorePercentage = Math.min(100, (result.final_score || 0) / 100 * 100);
        
        return `
            <div class="result-item slide-up">
                <div class="result-header">
                    <div>
                        <h3 class="result-title">
                            <a href="${result.url}" target="_blank">${ExtremeAI.escapeHtml(result.title)}</a>
                        </h3>
                        <div class="result-meta">
                            <span class="result-type ${typeClass}">${typeLabel}</span>
                            <span><i class="fas fa-calendar"></i> ${this.formatDate(result.date)}</span>
                            <span><i class="fas fa-user"></i> ${ExtremeAI.escapeHtml(result.author)}</span>
                            ${result.category ? `<span><i class="fas fa-folder"></i> ${ExtremeAI.escapeHtml(result.category)}</span>` : ''}
                            ${result.views > 0 ? `<span><i class="fas fa-eye"></i> ${ExtremeAI.formatNumber(result.views)}</span>` : ''}
                        </div>
                    </div>
                </div>
                <div class="result-content">
                    ${ExtremeAI.escapeHtml(result.content)}
                </div>
                <div class="result-score">
                    <span>Relevance:</span>
                    <div class="score-bar">
                        <div class="score-fill" style="width: ${scorePercentage}%"></div>
                    </div>
                    <span>${Math.round(scorePercentage)}%</span>
                </div>
            </div>
        `;
    }
    
    displayNoResults() {
        this.searchResults.innerHTML = `
            <div class="search-placeholder">
                <div class="placeholder-content">
                    <i class="fas fa-search fa-3x"></i>
                    <h3>No Results Found</h3>
                    <p>We couldn't find any content matching your search query.</p>
                    <div class="search-suggestions-help">
                        <h4>Try:</h4>
                        <ul>
                            <li>Using different keywords</li>
                            <li>Checking your spelling</li>
                            <li>Using more general terms</li>
                            <li>Enabling AI query expansion</li>
                        </ul>
                    </div>
                </div>
            </div>
        `;
        this.searchResults.style.display = 'block';
    }
    
    displayError(message) {
        this.searchResults.innerHTML = `
            <div class="search-placeholder">
                <div class="placeholder-content">
                    <i class="fas fa-exclamation-triangle fa-3x" style="color: #e74c3c;"></i>
                    <h3>Search Error</h3>
                    <p>${ExtremeAI.escapeHtml(message)}</p>
                </div>
            </div>
        `;
        this.searchResults.style.display = 'block';
        
        ExtremeAI.showNotification(message, 'error');
    }
    
    formatDate(timestamp) {
        if (!timestamp) return 'Unknown';
        
        const date = new Date(timestamp * 1000);
        const now = new Date();
        const diffMs = now - date;
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) return 'Today';
        if (diffDays === 1) return 'Yesterday';
        if (diffDays < 7) return `${diffDays} days ago`;
        if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
        if (diffDays < 365) return `${Math.floor(diffDays / 30)} months ago`;
        
        return date.toLocaleDateString();
    }
    
    // Search History Management
    addToSearchHistory(query) {
        if (!AISearch.historyEnabled || !query) return;
        
        // Remove if already exists
        this.searchHistory = this.searchHistory.filter(item => item !== query);
        
        // Add to beginning
        this.searchHistory.unshift(query);
        
        // Limit history size
        this.searchHistory = this.searchHistory.slice(0, this.maxHistoryItems);
        
        // Save to localStorage
        this.saveSearchHistory();
        
        // Update UI
        this.updateSearchHistoryUI();
    }
    
    loadSearchHistory() {
        if (!AISearch.historyEnabled) return;
        
        try {
            const saved = localStorage.getItem('extreme_ai_search_history');
            if (saved) {
                this.searchHistory = JSON.parse(saved);
            }
        } catch (error) {
            console.error('[AI Search] Failed to load search history:', error);
        }
        
        this.updateSearchHistoryUI();
    }
    
    saveSearchHistory() {
        if (!AISearch.historyEnabled) return;
        
        try {
            localStorage.setItem('extreme_ai_search_history', JSON.stringify(this.searchHistory));
        } catch (error) {
            console.error('[AI Search] Failed to save search history:', error);
        }
    }
    
    clearSearchHistory() {
        this.searchHistory = [];
        this.saveSearchHistory();
        this.updateSearchHistoryUI();
        ExtremeAI.showNotification('Search history cleared', 'success');
    }
    
    updateSearchHistoryUI() {
        const historyList = document.getElementById('search-history-list');
        const historyContainer = document.querySelector('.search-history');
        
        if (!historyList || !historyContainer) return;
        
        if (this.searchHistory.length === 0) {
            historyContainer.style.display = 'none';
            return;
        }
        
        const html = this.searchHistory.map(query => 
            `<div class="history-item" data-query="${ExtremeAI.escapeHtml(query)}">${ExtremeAI.escapeHtml(query)}</div>`
        ).join('');
        
        historyList.innerHTML = html;
        historyContainer.style.display = 'block';
        
        // Add click handlers
        historyList.querySelectorAll('.history-item').forEach(item => {
            item.addEventListener('click', () => {
                this.searchInput.value = item.dataset.query;
                this.performSearch();
            });
        });
    }
}

// Initialize AI Search when page loads
new ExtremeAI_Search();