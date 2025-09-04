<?php
/**
 * ExtremeAI Search Engine - AI-Powered Query Expansion
 *
 * Provides intelligent search capabilities using AI to expand queries
 * with synonyms, context, and semantic understanding before hitting the database.
 *
 * @category Extreme_AI
 * @package  Evo-Extreme
 * @author   Deano Welch <deano.welch@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version  2.0.0
 */

defined('NUKE_EVO') || exit;

class ExtremeAI_Search {
    
    private $db;
    private $prefix;
    private $core;
    private $cache_ttl = 3600; // 1 hour cache for expanded queries
    private $max_results = 50;
    
    public function __construct() {
        global $db, $prefix;
        $this->db = $db;
        $this->prefix = $prefix;
        $this->core = extreme_ai_get_core();
    }
    
    /**
     * Main search function with AI query expansion
     */
    public function search($query, $options = []) {
        $start_time = microtime(true);
        
        // Default options
        $options = array_merge([
            'content_types' => ['stories', 'forum_posts', 'pages'],
            'limit' => 20,
            'expand_query' => true,
            'boost_recent' => true,
            'min_score' => 0.1
        ], $options);
        
        try {
            // Step 1: Expand query using AI if enabled
            $expanded_query = $options['expand_query'] ? 
                $this->expandQuery($query) : $query;
            
            // Step 2: Search across content types
            $results = [];
            foreach ($options['content_types'] as $content_type) {
                $type_results = $this->searchContentType(
                    $content_type, 
                    $query, 
                    $expanded_query, 
                    $options
                );
                $results = array_merge($results, $type_results);
            }
            
            // Step 3: Score and rank results
            $results = $this->scoreResults($results, $query, $options);
            
            // Step 4: Apply filters and limits
            $results = array_slice($results, 0, $options['limit']);
            
            $response_time = microtime(true) - $start_time;
            
            return [
                'success' => true,
                'results' => $results,
                'total_found' => count($results),
                'original_query' => $query,
                'expanded_query' => $expanded_query,
                'response_time' => round($response_time, 3),
                'search_types' => $options['content_types']
            ];
            
        } catch (Exception $e) {
            error_log("ExtremeAI Search Error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Search failed: ' . $e->getMessage(),
                'fallback_results' => $this->fallbackSearch($query, $options)
            ];
        }
    }
    
    /**
     * Use AI to expand query with synonyms and context
     */
    private function expandQuery($query) {
        // Check cache first
        $cache_key = 'ai_search_expand_' . md5($query);
        $cached = $this->getFromCache($cache_key);
        if ($cached) {
            return $cached;
        }
        
        if (!$this->core) {
            return $query; // Fallback to original query
        }
        
        $prompt = "Expand this search query to include relevant synonyms, related terms, and context that would help find relevant content. Return only the expanded query terms separated by spaces, no explanations:\n\nOriginal query: \"$query\"";
        
        try {
            $result = $this->core->executeTask(
                'text_generation',
                ['prompt' => $prompt],
                [
                    'max_tokens' => 100,
                    'temperature' => 0.3, // Lower temperature for more focused results
                    'provider' => null // Auto-select best provider
                ]
            );
            
            if ($result['success'] && !empty($result['response'])) {
                $expanded = trim($result['response']);
                // Clean up the response - remove quotes, newlines, etc.
                $expanded = preg_replace('/["\n\r]/', '', $expanded);
                $expanded = preg_replace('/\s+/', ' ', $expanded);
                
                // Combine original query with expansion
                $final_query = $query . ' ' . $expanded;
                
                // Cache the result
                $this->saveToCache($cache_key, $final_query);
                
                error_log("ExtremeAI Search: Query '$query' expanded to '$final_query'");
                return $final_query;
            }
        } catch (Exception $e) {
            error_log("ExtremeAI Search: Query expansion failed - " . $e->getMessage());
        }
        
        return $query; // Fallback to original query
    }
    
    /**
     * Search specific content type
     */
    private function searchContentType($type, $original_query, $expanded_query, $options) {
        switch ($type) {
            case 'stories':
                return $this->searchStories($original_query, $expanded_query, $options);
            case 'forum_posts':
                return $this->searchForumPosts($original_query, $expanded_query, $options);
            case 'pages':
                return $this->searchPages($original_query, $expanded_query, $options);
            default:
                return [];
        }
    }
    
    /**
     * Search news/articles in nuke_stories
     */
    private function searchStories($original_query, $expanded_query, $options) {
        $search_terms = $this->prepareSearchTerms($expanded_query);
        
        // Build MATCH AGAINST query for full-text search
        $match_fields = "title, hometext, bodytext, notes";
        $where_conditions = [];
        $params = [];
        
        // Full-text search if available
        if ($this->hasFullTextIndex('stories')) {
            $where_conditions[] = "MATCH($match_fields) AGAINST (? IN BOOLEAN MODE)";
            $params[] = $this->buildBooleanQuery($search_terms);
        } else {
            // Fallback to LIKE search
            foreach ($search_terms as $term) {
                $where_conditions[] = "(title LIKE ? OR hometext LIKE ? OR bodytext LIKE ?)";
                $params[] = "%$term%";
                $params[] = "%$term%";
                $params[] = "%$term%";
            }
        }
        
        // Additional filters
        $where_conditions[] = "active = 1";
        if ($options['boost_recent']) {
            // Only content from last 2 years by default
            $where_conditions[] = "time > " . (time() - (2 * 365 * 24 * 3600));
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT sid, catid, title, hometext, bodytext, time, topic, 
                       informant, counter, notes,
                       CASE 
                         WHEN title LIKE ? THEN 100
                         WHEN hometext LIKE ? THEN 50
                         ELSE 10
                       END as base_score
                FROM {$this->prefix}_stories 
                WHERE $where_clause 
                ORDER BY base_score DESC, time DESC 
                LIMIT {$options['limit']}";
        
        // Add title matching parameters
        array_unshift($params, "%$original_query%", "%$original_query%");
        
        $results = [];
        $result = $this->db->sql_query($sql);
        
        while ($row = $this->db->sql_fetchrow($result)) {
            $content = $row['title'] . ' ' . $row['hometext'] . ' ' . $row['bodytext'];
            
            $results[] = [
                'id' => $row['sid'],
                'type' => 'story',
                'title' => $row['title'],
                'content' => $this->truncateContent($content, 200),
                'url' => "modules.php?name=News&file=article&sid=" . $row['sid'],
                'date' => $row['time'],
                'author' => $row['informant'],
                'category' => $row['topic'],
                'views' => $row['counter'],
                'base_score' => $row['base_score'],
                'relevance_score' => $this->calculateRelevance($content, $original_query)
            ];
        }
        
        return $results;
    }
    
    /**
     * Search forum posts
     */
    private function searchForumPosts($original_query, $expanded_query, $options) {
        $search_terms = $this->prepareSearchTerms($expanded_query);
        
        $where_conditions = [];
        $params = [];
        
        // Search in post text and subject
        foreach ($search_terms as $term) {
            $where_conditions[] = "(post_subject LIKE ? OR post_text LIKE ?)";
            $params[] = "%$term%";
            $params[] = "%$term%";
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT p.post_id, p.topic_id, p.forum_id, p.post_subject, 
                       p.post_text, p.post_time, p.poster_id, u.username,
                       t.topic_title, f.forum_name,
                       CASE 
                         WHEN p.post_subject LIKE ? THEN 100
                         ELSE 10
                       END as base_score
                FROM {$this->prefix}_bb_posts p
                LEFT JOIN {$this->prefix}_users u ON p.poster_id = u.user_id
                LEFT JOIN {$this->prefix}_bb_topics t ON p.topic_id = t.topic_id
                LEFT JOIN {$this->prefix}_bb_forums f ON p.forum_id = f.forum_id
                WHERE $where_clause
                ORDER BY base_score DESC, p.post_time DESC 
                LIMIT {$options['limit']}";
        
        // Add subject matching parameter
        array_unshift($params, "%$original_query%");
        
        $results = [];
        $result = $this->db->sql_query($sql);
        
        while ($row = $this->db->sql_fetchrow($result)) {
            $content = $row['post_subject'] . ' ' . $row['post_text'];
            
            $results[] = [
                'id' => $row['post_id'],
                'type' => 'forum_post',
                'title' => $row['post_subject'] ?: 'Re: ' . $row['topic_title'],
                'content' => $this->truncateContent($row['post_text'], 200),
                'url' => "modules.php?name=Forums&file=viewtopic&t=" . $row['topic_id'] . "&highlight=" . urlencode($original_query),
                'date' => $row['post_time'],
                'author' => $row['username'],
                'category' => $row['forum_name'],
                'views' => 0,
                'base_score' => $row['base_score'],
                'relevance_score' => $this->calculateRelevance($content, $original_query)
            ];
        }
        
        return $results;
    }
    
    /**
     * Search static pages
     */
    private function searchPages($original_query, $expanded_query, $options) {
        $search_terms = $this->prepareSearchTerms($expanded_query);
        
        $where_conditions = [];
        $params = [];
        
        // Search in page content
        foreach ($search_terms as $term) {
            $where_conditions[] = "(content LIKE ? OR title LIKE ?)";
            $params[] = "%$term%";
            $params[] = "%$term%";
        }
        
        $where_conditions[] = "active = 1";
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT pid, title, content, date, 
                       CASE 
                         WHEN title LIKE ? THEN 100
                         ELSE 10
                       END as base_score
                FROM {$this->prefix}_pages 
                WHERE $where_clause 
                ORDER BY base_score DESC, date DESC 
                LIMIT {$options['limit']}";
        
        // Add title matching parameter
        array_unshift($params, "%$original_query%");
        
        $results = [];
        $result = $this->db->sql_query($sql);
        
        while ($row = $this->db->sql_fetchrow($result)) {
            $content = $row['title'] . ' ' . $row['content'];
            
            $results[] = [
                'id' => $row['pid'],
                'type' => 'page',
                'title' => $row['title'],
                'content' => $this->truncateContent(strip_tags($row['content']), 200),
                'url' => "modules.php?name=Content&pa=showpage&pid=" . $row['pid'],
                'date' => strtotime($row['date']),
                'author' => 'Admin',
                'category' => 'Pages',
                'views' => 0,
                'base_score' => $row['base_score'],
                'relevance_score' => $this->calculateRelevance($content, $original_query)
            ];
        }
        
        return $results;
    }
    
    /**
     * Score and rank results
     */
    private function scoreResults($results, $query, $options) {
        foreach ($results as &$result) {
            $score = $result['base_score'] + $result['relevance_score'];
            
            // Boost recent content if enabled
            if ($options['boost_recent']) {
                $age_days = (time() - $result['date']) / (24 * 3600);
                $recency_boost = max(0, (365 - $age_days) / 365 * 20);
                $score += $recency_boost;
            }
            
            // Boost by view count
            if ($result['views'] > 0) {
                $score += log($result['views'] + 1) * 5;
            }
            
            $result['final_score'] = $score;
        }
        
        // Sort by final score
        usort($results, function($a, $b) {
            return $b['final_score'] - $a['final_score'];
        });
        
        // Filter by minimum score
        return array_filter($results, function($result) use ($options) {
            return $result['final_score'] >= $options['min_score'];
        });
    }
    
    /**
     * Calculate relevance score based on term frequency and position
     */
    private function calculateRelevance($content, $query) {
        $content_lower = strtolower($content);
        $query_lower = strtolower($query);
        $terms = explode(' ', $query_lower);
        
        $score = 0;
        foreach ($terms as $term) {
            $term = trim($term);
            if (empty($term)) continue;
            
            // Count occurrences
            $count = substr_count($content_lower, $term);
            $score += $count * 10;
            
            // Boost if term appears at beginning
            if (strpos($content_lower, $term) < 100) {
                $score += 5;
            }
        }
        
        return $score;
    }
    
    /**
     * Prepare search terms from expanded query
     */
    private function prepareSearchTerms($query) {
        // Remove common stop words
        $stop_words = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        
        $terms = explode(' ', strtolower($query));
        $terms = array_filter($terms, function($term) use ($stop_words) {
            $term = trim($term);
            return !empty($term) && strlen($term) > 2 && !in_array($term, $stop_words);
        });
        
        return array_unique($terms);
    }
    
    /**
     * Build boolean query for MATCH AGAINST
     */
    private function buildBooleanQuery($terms) {
        $boolean_terms = [];
        foreach ($terms as $term) {
            $boolean_terms[] = "+$term";
        }
        return implode(' ', $boolean_terms);
    }
    
    /**
     * Check if full-text index exists
     */
    private function hasFullTextIndex($table) {
        // For simplicity, assume no full-text index
        // In production, you might check SHOW INDEX FROM table
        return false;
    }
    
    /**
     * Truncate content for display
     */
    private function truncateContent($content, $length = 200) {
        $content = strip_tags($content);
        if (strlen($content) <= $length) {
            return $content;
        }
        
        return substr($content, 0, $length) . '...';
    }
    
    /**
     * Fallback search without AI expansion
     */
    private function fallbackSearch($query, $options) {
        return $this->search($query, array_merge($options, ['expand_query' => false]));
    }
    
    /**
     * Simple caching methods
     */
    private function getFromCache($key) {
        // Simple file-based cache
        $cache_file = NUKE_BASE_DIR . 'cache/extreme_ai_search_' . md5($key) . '.cache';
        
        if (file_exists($cache_file) && (time() - filemtime($cache_file) < $this->cache_ttl)) {
            return unserialize(file_get_contents($cache_file));
        }
        
        return null;
    }
    
    private function saveToCache($key, $data) {
        $cache_file = NUKE_BASE_DIR . 'cache/extreme_ai_search_' . md5($key) . '.cache';
        file_put_contents($cache_file, serialize($data));
    }
    
    /**
     * Get search suggestions using AI
     */
    public function getSearchSuggestions($partial_query, $limit = 5) {
        if (!$this->core || strlen($partial_query) < 2) {
            return [];
        }
        
        $prompt = "Based on this partial search query, suggest $limit related complete search terms that would be useful for searching a website. Return only the search terms, one per line:\n\nPartial query: \"$partial_query\"";
        
        try {
            $result = $this->core->executeTask(
                'text_generation',
                ['prompt' => $prompt],
                [
                    'max_tokens' => 50,
                    'temperature' => 0.5
                ]
            );
            
            if ($result['success'] && !empty($result['response'])) {
                $suggestions = array_filter(
                    explode("\n", trim($result['response'])),
                    function($line) {
                        return !empty(trim($line));
                    }
                );
                
                return array_slice($suggestions, 0, $limit);
            }
        } catch (Exception $e) {
            error_log("ExtremeAI Search Suggestions Error: " . $e->getMessage());
        }
        
        return [];
    }
}