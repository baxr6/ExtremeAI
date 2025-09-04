<?php
/**
 * ExtremeAI Search Block
 *
 * Provides a frontend search block with AI-powered query expansion
 * that can be embedded in themes or used as a standalone block.
 *
 * @category Extreme_AI
 * @package  Evo-Extreme
 * @author   Deano Welch <deano.welch@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version  2.0.0
 */

defined('NUKE_EVO') || exit;

// Include the search class
require_once NUKE_INCLUDE_DIR . 'extreme_ai/classes/ExtremeAI_Search.php';

/**
 * Display the ExtremeAI Search block
 */
function block_extreme_ai_search($title = 'AI Search') {
    global $db, $prefix;
    
    // Check if ExtremeAI is available
    if (!function_exists('extreme_ai_get_core')) {
        return '';
    }
    
    $content = '
    <div class="extreme-ai-search-block" id="ai-search-block">
        <style>
        .extreme-ai-search-block {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .search-block-form {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }
        .search-block-input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease;
        }
        .search-block-input:focus {
            border-color: #667eea;
        }
        .search-block-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            transition: transform 0.2s ease;
        }
        .search-block-btn:hover {
            transform: translateY(-1px);
        }
        .search-block-results {
            display: none;
            max-height: 400px;
            overflow-y: auto;
        }
        .search-result-item {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: 8px;
        }
        .search-result-item:last-child {
            border-bottom: none;
        }
        .search-result-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .search-result-title a {
            color: #2c3e50;
            text-decoration: none;
        }
        .search-result-title a:hover {
            color: #667eea;
        }
        .search-result-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .search-result-snippet {
            font-size: 13px;
            color: #555;
            line-height: 1.4;
        }
        .search-toggle {
            text-align: center;
            margin-top: 10px;
        }
        .search-toggle-btn {
            background: none;
            border: 1px solid #667eea;
            color: #667eea;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
        }
        .search-toggle-btn:hover {
            background: #667eea;
            color: white;
        }
        .search-options {
            font-size: 12px;
            margin-bottom: 10px;
        }
        .search-options label {
            margin-right: 15px;
            cursor: pointer;
        }
        .search-loading {
            text-align: center;
            padding: 20px;
            display: none;
        }
        .search-loading i {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        </style>
        
        <form class="search-block-form" id="ai-search-block-form">
            <input type="text" class="search-block-input" id="ai-search-input" 
                   placeholder="Search with AI..." autocomplete="off" />
            <button type="submit" class="search-block-btn" id="ai-search-btn">
                <i class="fas fa-search"></i>
            </button>
        </form>
        
        <div class="search-options" style="display: none;" id="search-options">
            <label><input type="checkbox" id="expand-query-block" checked> AI Expansion</label>
            <label><input type="checkbox" id="boost-recent-block" checked> Recent Content</label>
        </div>
        
        <div class="search-loading" id="search-block-loading">
            <i class="fas fa-spinner"></i> Searching...
        </div>
        
        <div class="search-block-results" id="search-block-results">
            <!-- Results will be loaded here -->
        </div>
        
        <div class="search-toggle">
            <button class="search-toggle-btn" id="search-options-toggle">Advanced Options</button>
        </div>
    </div>
    
    <script>
    // Simple search block functionality
    (function() {
        const form = document.getElementById("ai-search-block-form");
        const input = document.getElementById("ai-search-input");
        const results = document.getElementById("search-block-results");
        const loading = document.getElementById("search-block-loading");
        const optionsToggle = document.getElementById("search-options-toggle");
        const options = document.getElementById("search-options");
        
        let optionsVisible = false;
        
        // Toggle advanced options
        optionsToggle.addEventListener("click", function(e) {
            e.preventDefault();
            optionsVisible = !optionsVisible;
            options.style.display = optionsVisible ? "block" : "none";
            optionsToggle.textContent = optionsVisible ? "Hide Options" : "Advanced Options";
        });
        
        // Search form submission
        form.addEventListener("submit", function(e) {
            e.preventDefault();
            performBlockSearch();
        });
        
        async function performBlockSearch() {
            const query = input.value.trim();
            if (!query) return;
            
            // Show loading
            loading.style.display = "block";
            results.style.display = "none";
            
            try {
                const formData = new FormData();
                formData.append("ajax_action", "ai_search");
                formData.append("query", query);
                formData.append("limit", "5");
                formData.append("expand_query", document.getElementById("expand-query-block").checked ? "1" : "0");
                formData.append("boost_recent", document.getElementById("boost-recent-block").checked ? "1" : "0");
                formData.append("content_types[]", "stories");
                formData.append("content_types[]", "forum_posts");
                formData.append("content_types[]", "pages");
                
                const response = await fetch("admin.php?op=extremeai_search", {
                    method: "POST",
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.results) {
                    displayBlockResults(data.results);
                } else {
                    results.innerHTML = "<p>No results found.</p>";
                    results.style.display = "block";
                }
                
            } catch (error) {
                console.error("Search error:", error);
                results.innerHTML = "<p>Search failed. Please try again.</p>";
                results.style.display = "block";
            } finally {
                loading.style.display = "none";
            }
        }
        
        function displayBlockResults(searchResults) {
            if (!searchResults.length) {
                results.innerHTML = "<p>No results found.</p>";
            } else {
                const html = searchResults.map(result => `
                    <div class="search-result-item">
                        <div class="search-result-title">
                            <a href="${result.url}">${result.title}</a>
                        </div>
                        <div class="search-result-meta">
                            ${result.type} • ${result.author} • ${formatDate(result.date)}
                        </div>
                        <div class="search-result-snippet">
                            ${result.content}
                        </div>
                    </div>
                `).join("");
                results.innerHTML = html;
            }
            results.style.display = "block";
        }
        
        function formatDate(timestamp) {
            const date = new Date(timestamp * 1000);
            const now = new Date();
            const diffMs = now - date;
            const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) return "Today";
            if (diffDays === 1) return "Yesterday";
            if (diffDays < 7) return `${diffDays} days ago`;
            
            return date.toLocaleDateString();
        }
    })();
    </script>';
    
    return $content;
}

/**
 * ExtremeAI Search Widget for easy embedding
 */
function extreme_ai_search_widget($options = []) {
    $defaults = [
        'title' => 'AI Search',
        'placeholder' => 'Search with AI...',
        'results_limit' => 5,
        'show_options' => true,
        'compact' => false
    ];
    
    $options = array_merge($defaults, $options);
    
    return block_extreme_ai_search($options['title']);
}

/**
 * Hook for Nuke Evolution blocks system
 */
function blockfunc_extreme_ai_search($options) {
    return block_extreme_ai_search($options['title'] ?? 'AI Search');
}
?>