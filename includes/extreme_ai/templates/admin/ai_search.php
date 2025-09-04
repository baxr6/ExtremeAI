<?php
/**
 * ExtremeAI Search Template
 *
 * AI-powered search interface with query expansion and intelligent ranking.
 *
 * @var string $admin_file Admin file path
 * @var string $csrf_token CSRF token for form security
 */

defined('NUKE_EVO') || exit;
?>

<div class="extreme-ai-search">
    <?php extreme_ai_admin_menu(); ?>
    
    <div class="search-header">
        <h1><i class="fas fa-search"></i> AI-Powered Search</h1>
        <p class="lead">Intelligent search with AI query expansion and semantic understanding</p>
    </div>

    <div class="search-interface">
        <div class="search-form-container">
            <form id="ai-search-form" class="search-form">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>" />
                
                <div class="search-input-wrapper">
                    <div class="search-input-container">
                        <input type="text" id="search-query" name="query" class="search-input" 
                               placeholder="Enter your search query..." autocomplete="off" />
                        <button type="submit" class="search-button" id="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                        <div id="search-suggestions" class="search-suggestions" style="display: none;"></div>
                    </div>
                </div>
                
                <div class="search-options">
                    <div class="search-options-row">
                        <div class="option-group">
                            <label>Content Types:</label>
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="content_types" value="stories" checked>
                                    <span>News & Articles</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="content_types" value="forum_posts" checked>
                                    <span>Forum Posts</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="content_types" value="pages" checked>
                                    <span>Static Pages</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="option-group">
                            <label for="result-limit">Results per page:</label>
                            <select id="result-limit" name="limit">
                                <option value="10">10</option>
                                <option value="20" selected>20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="search-options-row">
                        <div class="option-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="expand-query" name="expand_query" checked>
                                <span>AI Query Expansion</span>
                                <small>Use AI to expand search with related terms</small>
                            </label>
                        </div>
                        
                        <div class="option-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="boost-recent" name="boost_recent" checked>
                                <span>Boost Recent Content</span>
                                <small>Prioritize newer content in results</small>
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="search-results-container">
            <div id="search-status" class="search-status" style="display: none;">
                <div class="search-stats">
                    <span id="results-count">0</span> results found 
                    <span id="response-time"></span>
                    <span id="query-expansion" class="query-expansion"></span>
                </div>
            </div>

            <div id="search-results" class="search-results">
                <div class="search-placeholder">
                    <div class="placeholder-content">
                        <i class="fas fa-brain fa-3x"></i>
                        <h3>AI-Powered Search</h3>
                        <p>Enter a search query above to find content across your site with intelligent AI assistance.</p>
                        
                        <div class="search-features">
                            <div class="feature">
                                <i class="fas fa-magic"></i>
                                <h4>Query Expansion</h4>
                                <p>AI automatically adds synonyms and related terms</p>
                            </div>
                            <div class="feature">
                                <i class="fas fa-sort-amount-down"></i>
                                <h4>Smart Ranking</h4>
                                <p>Results ranked by relevance and recency</p>
                            </div>
                            <div class="feature">
                                <i class="fas fa-layer-group"></i>
                                <h4>Multi-Content</h4>
                                <p>Search across news, forums, and pages</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="search-loading" class="search-loading" style="display: none;">
                <div class="loading-content">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>AI is processing your search...</p>
                    <div class="loading-steps">
                        <div class="step" data-step="expand">Expanding query with AI</div>
                        <div class="step" data-step="search">Searching content</div>
                        <div class="step" data-step="rank">Ranking results</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search History -->
    <div class="search-history" style="display: none;">
        <div class="history-header">
            <h3><i class="fas fa-history"></i> Recent Searches</h3>
            <button id="clear-history" class="btn btn-sm btn-outline-secondary">Clear History</button>
        </div>
        <div id="search-history-list" class="history-list">
            <!-- History items loaded via JavaScript -->
        </div>
    </div>
</div>

<script>
// Configure AI Search
const AISearch = {
    adminFile: '<?php echo e($admin_file); ?>',
    csrfToken: '<?php echo e($csrf_token); ?>',
    suggestionsEnabled: true,
    historyEnabled: true
};

console.log('[AI Search] Configuration loaded:', AISearch);
</script>