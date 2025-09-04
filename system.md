# ExtremeAI Complete System Documentation

## System Overview

ExtremeAI is a comprehensive AI management and integration system for Evolution CMS that provides multi-provider AI support, intelligent task routing, workflow automation, and advanced analytics. The system is built as a cleaned/refactored version targeting PHP 8.4+ with modern architecture.

## Complete File Structure

```
/home/deano/Projects/Evo_Mods/Extreme_AI/
├── admin/
│   ├── case/
│   │   └── case.extreme_ai_clean.php      # Admin request router
│   ├── links/
│   │   └── links.extreme_ai_clean.php     # Admin menu integration
│   └── modules/
│       └── extreme_ai_clean.php           # Main admin module controller
├── includes/
│   └── extreme_ai/
│       ├── classes/
│       │   ├── ExtremeAI_Core_Clean.php   # Primary system engine
│       │   ├── ExtremeAI_Database.php     # Database migration manager
│       │   └── ExtremeAI_Providers_Clean.php # AI provider implementations
│       ├── css/
│       │   ├── extreme_ai_clean.css       # Main stylesheet
│       │   ├── extreme-ai-components.css  # UI components
│       │   └── test-console.css           # Test console styles
│       ├── js/
│       │   ├── dashboard.js               # Dashboard functionality
│       │   ├── extreme-ai-core.js         # Core JavaScript library
│       │   ├── installer.js               # Installation wizard
│       │   ├── providers.js               # Provider management
│       │   ├── settings.js                # Settings interface
│       │   └── test-console.js            # AI testing console
│       ├── language/
│       │   └── lang-english.php           # Language constants
│       └── templates/
│           └── admin/
│               ├── dashboard.php          # Dashboard template
│               ├── providers.php          # Provider configuration
│               ├── settings.php           # System settings
│               ├── styles.php             # CSS styles
│               └── test_console.php       # Test console template
├── install.php                           # Complete installation system
├── upgrade.php                           # Database upgrade manager
├── README_INSTALLATION.md               # Installation documentation
└── system.md                            # This documentation
```

---

## **Core Classes and Methods**

### **ExtremeAI_Core_Clean (Primary Engine)**
**File**: `/includes/extreme_ai/classes/ExtremeAI_Core_Clean.php`

#### **Core Methods**
- `getInstance()` - Singleton pattern implementation
- `executeTask($task_type, $input, $options)` - Execute AI tasks with intelligent routing
- `selectBestProvider($task_type, $options)` - Smart provider selection algorithm
- `healthCheck()` - System health monitoring
- `listProviders()` - Get active provider list
- `generateAdvancedContent($type, $parameters)` - Multi-step content generation
- `getAdvancedAnalytics($timeframe)` - Analytics and insights
- `personalizeForUser($content, $user_data)` - AI-powered personalization
- `optimizeContentRealtime($content, $optimization_type)` - Content optimization

#### **Task Types Constants**
```php
TASK_TEXT_GENERATION = 'text_generation'
TASK_CONTENT_ANALYSIS = 'content_analysis' 
TASK_TRANSLATION = 'translation'
TASK_SUMMARIZATION = 'summarization'
TASK_CODE_GENERATION = 'code_generation'
TASK_IMAGE_ANALYSIS = 'image_analysis'
TASK_SENTIMENT_ANALYSIS = 'sentiment_analysis'
TASK_ENTITY_EXTRACTION = 'entity_extraction'
TASK_CLASSIFICATION = 'classification'
TASK_QUESTION_ANSWERING = 'question_answering'
```

#### **Provider Constants**
```php
PROVIDER_ANTHROPIC = 'anthropic'
PROVIDER_OPENAI = 'openai'
PROVIDER_GOOGLE = 'google'
PROVIDER_MISTRAL = 'mistral'
PROVIDER_COHERE = 'cohere'
PROVIDER_HUGGINGFACE = 'huggingface'
PROVIDER_OLLAMA = 'ollama'
```

### **ExtremeAI_Database (Migration Manager)**
**File**: `/includes/extreme_ai/classes/ExtremeAI_Database.php`

#### **Core Methods**
- `getCurrentVersion()` - Get database version
- `needsUpdate()` - Check if updates required
- `migrate()` - Run database migrations
- `rollback($target_version)` - Rollback to previous version
- `checkHealth()` - Database health assessment
- `cleanup($days)` - Data cleanup operations
- `exportConfig()` - Export system configuration
- `importConfig($config)` - Import configuration

### **ExtremeAI_Providers_Clean (AI Provider System)**
**File**: `/includes/extreme_ai/classes/ExtremeAI_Providers_Clean.php`

#### **Base Provider (Abstract)**
```php
abstract class ExtremeAI_BaseProvider
- initialize()
- execute($task_type, $input, $options)
- getName()
- getCapabilities()
- supportsTask($task_type)
- makeRequest($url, $data, $headers)
```

#### **Concrete Providers**
- `ExtremeAI_AnthropicProvider` - Claude API integration
- `ExtremeAI_OpenAIProvider` - GPT models support
- `ExtremeAI_GoogleProvider` - Gemini API integration  
- `ExtremeAI_OllamaProvider` - Local AI models

#### **Agent System Classes**
```php
abstract class ExtremeAI_BaseAgent
- run($task)
- getName() 
- shouldRun()
- logActivity($message)
```

#### **Specialized Agents**
- `ContentCuratorAgent` - Automatic content improvement
- `SEOOptimizerAgent` - SEO optimization automation
- `CommunityManagerAgent` - Community interaction management
- `AnalyticsReporterAgent` - Automated reporting

#### **Workflow Engine**
```php
class ExtremeAI_WorkflowEngine
- executeWorkflow($workflow_name, $input, $options)
- executeStep($step, $context)
- loadWorkflows()
```

---

## **Admin Module System**

### **Main Admin Module (`admin/modules/extreme_ai_clean.php`)**

#### **Constants**
- `EXTREME_AI_VERSION`: Current system version (2.0.0)
- `EXTREME_AI_ASSETS_URL`: Dynamic URL for assets based on server configuration

#### **Security & Utility Functions**
- `extreme_ai_include_assets()`: Loads CSS and JavaScript assets
- `extreme_ai_load_template($template_name, $data)`: Custom template loader
- `e($string)`: HTML escaping helper
- `extreme_ai_get_csrf()`: CSRF token generation
- `extreme_ai_check_csrf()`: CSRF token validation
- `extreme_ai_json_response($data, $success)`: JSON response formatter

#### **Core Integration Functions**
- `extreme_ai_get_core()`: Returns ExtremeAI_Core instance with error handling
- `extreme_ai_get_system_info()`: Retrieves system status and health information

#### **AJAX Handler**
- `extreme_ai_handle_ajax()`: Main AJAX request router
  - Handles actions: `get_stats`, `test_provider`, `get_analytics`, `get_chart_data`, `run_test`
  - Includes CSRF protection for data-modifying operations
  - Supports read-only actions without CSRF requirement

#### **Page Functions**
- `extreme_ai_dashboard()`: Main dashboard page
- `extreme_ai_providers_page()`: Provider management page
- `extreme_ai_settings_page()`: System settings page
- `extreme_ai_analytics_page()`: Analytics and reporting page
- `extreme_ai_test_console_page()`: Interactive testing console
- `extreme_ai_workflows_page()`: Workflow management page
- `extreme_ai_agents_page()`: AI agent management page

#### **Test Console Functions**
- `extreme_ai_run_console_test($provider, $taskType, $system, $prompt, $maxTokens, $temperature, $stream)`: Executes AI tests
- `extreme_ai_get_all_providers()`: Retrieves all configured providers from database

### **Admin Router (`admin/case/case.extreme_ai_clean.php`)**
Request routing for admin operations

### **Admin Menu Integration (`admin/links/links.extreme_ai_clean.php`)**
Integration with Evolution CMS admin menu system

---

## **JavaScript Architecture**

### **Core JavaScript Library (`js/extreme-ai-core.js`)**

#### **ExtremeAI_Core Class**
- **CSRF Management**: `setCsrfToken()`, `initCSRF()`
- **AJAX Handling**: `ajax(url, options)` - Enhanced fetch wrapper with CSRF
- **UI Utilities**: 
  - `showNotification(message, type, duration)` - Toast notifications
  - `showModal(content, options)` - Modal dialog system
  - `confirm(message, callback)` - Confirmation dialogs
  - `showLoading()` / `hideLoading()` - Loading indicators
- **Form Utilities**: `addCSRFToForm()`, `validateForm()`
- **Data Formatting**: 
  - `formatNumber(num)` - Number formatting with commas
  - `formatCurrency(amount, currency)` - Currency formatting
  - `formatDate(date, options)` - Date formatting
- **Utilities**: `debounce()`, `throttle()`, `escapeHtml()`

### **Dashboard JavaScript (`js/dashboard.js`)**

#### **ExtremeAI_Dashboard Class**
- Real-time dashboard updates and chart rendering
- Activity monitoring and health checks
- Auto-refresh functionality
- Usage analytics and provider performance tracking

### **Provider Management (`js/providers.js`)**

#### **ExtremeAI_Providers Class**
- Provider configuration management
- Real-time validation and testing
- Auto-save functionality with conflict detection
- Provider statistics monitoring

### **Settings Interface (`js/settings.js`)**

#### **ExtremeAI_Settings Class**
- System settings management with change detection
- Import/export functionality
- Auto-save with validation
- Configuration backup and restore

### **Test Console (`js/test-console.js`)**

#### **ExtremeAI_TestConsole Class**
- Interactive AI testing interface
- Test execution and management: `executeTest(testData)`
- Form validation: `validateForm()`, `updateTestUI(state)`
- Result display: `displayTestResult(result, responseTime, testData)`
- Test history management and sample prompt library

### **Installation Wizard (`js/installer.js`)**

#### **ExtremeAI_Installer Class**
- Step-by-step installation process
- Requirements validation
- Database setup and configuration
- Progress tracking and error handling

---

## **Templates System**

### **Dashboard Template (`templates/admin/dashboard.php`)**
- Real-time system overview and statistics
- Provider status monitoring
- Recent activity feed
- Performance metrics visualization

### **Provider Configuration (`templates/admin/providers.php`)**
- Provider setup and configuration forms
- API key management and validation
- Performance monitoring and statistics
- Provider testing interface

### **System Settings (`templates/admin/settings.php`)**
- Global system configuration
- Performance tuning options
- Security settings
- Backup and maintenance tools

### **Test Console Template (`templates/admin/test_console.php`)**

#### **Template Variables**
- `$providers`: Array of configured AI providers
- `$admin_file`: Admin file path for AJAX requests
- `$csrf_token`: Security token for form submissions

#### **Features**
- Provider selection dropdown with configuration validation
- Task type selection (Text Generation, Content Analysis, Translation, etc.)
- Configuration options (Max Tokens, Temperature, Streaming)
- Input areas for System Prompt and User Prompt
- Real-time output display with metrics
- Test history and sample prompts
- Responsive grid layout

### **Styles Template (`templates/admin/styles.php`)**
- Dynamic CSS generation
- Theme customization
- Responsive design variables

---

## **Database Schema**

### **Core Tables**

#### **extreme_ai_config**
- System and provider configuration storage
- Supports typed values (string, integer, boolean, json, array)
- Provider-specific settings organization

#### **extreme_ai_providers** 
- AI provider configurations
- API keys, endpoints, models
- Performance metrics (success rate, response time, cost tracking)
- Priority-based selection system

#### **extreme_ai_usage**
- Comprehensive usage tracking
- Token consumption and cost analysis
- Response time monitoring
- Request/response data logging

#### **extreme_ai_errors**
- Multi-level error logging (debug, info, warning, error, critical)
- Provider-specific error tracking
- Full stack trace capture

#### **extreme_ai_tasks**
- Asynchronous task queue
- Progress tracking and retry logic
- Cost estimation and actual cost tracking

#### **extreme_ai_analytics**
- Aggregated analytics by date/hour
- Provider and task type breakdown
- Performance metrics aggregation

#### **extreme_ai_workflows & extreme_ai_agents**
- Workflow automation definitions
- AI agent configurations and capabilities
- Usage statistics and performance tracking

---

## **Installation & Upgrade System**

### **Installation Script (`install.php`)**

#### **Installation Process**
1. **Requirements Check** - System validation (PHP version, extensions, permissions)
2. **Database Creation** - Table installation and initial schema
3. **Configuration Setup** - Default settings and provider templates
4. **Sample Data** - Optional providers and agents installation
5. **Completion** - System activation and verification

#### **System Requirements**
- PHP 8.4 or higher
- MySQL 5.7 or higher
- Required extensions: json, curl, mbstring, openssl
- Evolution CMS with admin access

### **Upgrade Manager (`upgrade.php`)**

#### **Upgrade Features**
- Automated database migrations
- Version compatibility checks
- Rollback capabilities
- Configuration backup and restore
- Data integrity validation

---

## **Language System**

### **English Language File (`language/lang-english.php`)**

#### **Language Constants**
- System messages and labels
- Error messages and notifications
- Admin interface text
- Help text and descriptions
- Multi-language support structure

---

## **Configuration System**

### **System Settings**
```php
'extreme_ai_debug' => false
'extreme_ai_cache_enabled' => true
'extreme_ai_cache_ttl' => 3600
'extreme_ai_max_tokens' => 4096
'extreme_ai_rate_limit' => 1000
'extreme_ai_default_timeout' => 30
'extreme_ai_max_concurrent_requests' => 10
'extreme_ai_auto_cleanup_days' => 30
'extreme_ai_log_level' => 'error'
```

### **Provider Configuration**
Each provider supports:
- Display name and branding
- API endpoint and authentication
- Model selection and parameters
- Rate limiting and timeout settings
- Priority-based selection
- Performance monitoring

---

## **AJAX API System**

### **Request Format**
- **URL**: `admin.php?op=extremeai_test_console`
- **Method**: POST
- **Content-Type**: multipart/form-data (FormData)

### **Available Actions**

#### **run_test**
Execute AI provider test

**Parameters:**
- `ajax_action`: "run_test"
- `provider`: Provider identifier (optional for auto-select)
- `task_type`: Task category
- `system`: System prompt (optional)
- `prompt`: User prompt (required)
- `max_tokens`: Token limit
- `temperature`: Creativity parameter
- `stream`: Boolean streaming flag
- `csrf_token`: Security token

**Response:**
```json
{
    "success": true,
    "data": {
        "response": "AI generated content",
        "provider_used": "anthropic",
        "tokens_used": 150,
        "response_time": 1.23,
        "cost_estimate": "$0.0012"
    }
}
```

#### **Other AJAX Actions**
- `get_stats`: System usage statistics
- `test_provider`: Provider connectivity testing
- `get_analytics`: Analytics data retrieval
- `get_chart_data`: Chart data for dashboards

---

## **Security Features**

### **Authentication & Authorization**
- Admin-only access control with `is_mod_admin()` checks
- Session-based authentication
- CSRF token protection for all data-modifying operations
- IP-based logging and monitoring

### **Data Protection**
- API key encryption and masking in displays
- SQL injection prevention via parameterized queries
- XSS protection through HTML escaping
- Input validation and sanitization

### **Monitoring & Logging**
- Comprehensive error tracking and logging
- Usage analytics and performance monitoring
- Security event logging
- Real-time health monitoring

---

## **Advanced Features**

### **Multi-Provider Failover**
- Intelligent provider selection based on task type and performance
- Automatic failover on provider errors
- Load balancing across multiple providers

### **Caching System**
- Response caching for improved performance
- TTL-based cache expiration
- Provider-specific cache strategies

### **Workflow Automation**
- Custom workflow definitions
- AI agent automation
- Scheduled task execution

### **Analytics & Reporting**
- Real-time usage analytics
- Cost tracking and optimization
- Performance monitoring
- Custom reporting capabilities

---

## **API Usage Examples**

### **Core Task Execution**
```php
$result = $extreme_ai->executeTask(
    ExtremeAI_Core::TASK_TEXT_GENERATION,
    ['prompt' => 'Generate content...'],
    ['provider' => 'anthropic', 'max_tokens' => 2000]
);
```

### **Advanced Content Generation**
```php
$suite = $extreme_ai->generateAdvancedContent('article_suite', [
    'topic' => 'AI Technology',
    'category' => 'Technology',
    'length' => 'medium',
    'audience' => 'technical'
]);
```

### **Analytics & Monitoring**
```php
$analytics = $extreme_ai->getAdvancedAnalytics('7d');
$health = $extreme_ai->healthCheck();
$providers = $extreme_ai->listProviders();
```

---

## **Development Notes**

### **Code Standards**
- PSR-12 compatible PHP formatting
- ES6+ JavaScript with class-based architecture
- Semantic HTML5 structure
- Mobile-first CSS approach

### **Extensibility**
- Plugin-ready architecture
- Template override system
- Hook-based event system
- Modular component design

### **Performance Considerations**
- Lazy loading of components
- Efficient database queries with proper indexing
- Memory management with singleton patterns
- Asset optimization and caching

---

## **Version Information**
- **Version**: 2.0.0
- **PHP Requirement**: 8.4+
- **CMS**: Evolution CMS (Nuke Evolution)
- **Author**: Deano Welch
- **License**: MIT

This comprehensive system provides a complete AI management platform with enterprise-grade features, scalability, and integration capabilities for Evolution CMS environments.