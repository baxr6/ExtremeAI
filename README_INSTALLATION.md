# ExtremeAI Installation Guide

Complete installation and setup guide for the ExtremeAI system on Nuke-Evolution Xtreme.

## 📋 System Requirements

### Minimum Requirements
- **PHP**: 8.0 or higher (8.4+ recommended)
- **MySQL**: 5.7 or higher (8.0+ recommended)  
- **Nuke-Evolution Xtreme**: Latest version
- **Web Server**: Apache/Nginx with mod_rewrite
- **Memory**: 256MB PHP memory limit minimum (512MB recommended)
- **Storage**: 50MB free disk space

### Required PHP Extensions
- `json` - JSON processing
- `curl` - HTTP requests to AI providers
- `mbstring` - Multi-byte string handling
- `openssl` - Secure communications

### Optional but Recommended
- `redis` - For advanced caching
- `opcache` - PHP opcode caching
- `gd` or `imagick` - Image processing

## 🚀 Installation Methods

### Method 1: Web Installer (Recommended)

1. **Upload Files**
   ```bash
   # Extract ExtremeAI to your Nuke-Evolution root directory
   unzip extreme_ai_2.0.zip
   cd your-nuke-site/
   ```

2. **Set Permissions**
   ```bash
   chmod 755 includes/extreme_ai/
   chmod 755 includes/extreme_ai/classes/
   chmod 755 includes/extreme_ai/templates/
   chmod 644 *.php
   ```

3. **Run Web Installer**
   - Navigate to `http://yoursite.com/install.php`
   - Follow the step-by-step installation wizard
   - The installer will:
     - Check system requirements
     - Create database tables
     - Install default configuration
     - Set up sample providers and agents

4. **Complete Setup**
   - Access admin panel: `http://yoursite.com/admin.php?op=extremeai_dashboard`
   - Configure your AI providers
   - Test connections

### Method 2: Manual Installation

1. **Database Setup**
   ```sql
   -- Run this SQL in your database
   -- (See database_schema.sql for complete schema)
   
   -- Create configuration table
   CREATE TABLE `nuke_extreme_ai_config` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `provider` varchar(50) NOT NULL DEFAULT 'system',
     `key` varchar(100) NOT NULL,
     `value` text,
     `description` varchar(255) DEFAULT NULL,
     `type` enum('string','integer','boolean','json','array') DEFAULT 'string',
     `created` datetime DEFAULT CURRENT_TIMESTAMP,
     `updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     PRIMARY KEY (`id`),
     UNIQUE KEY `provider_key` (`provider`,`key`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
   
   -- Continue with other tables...
   ```

2. **Configuration Files**
   ```php
   // Add to your config.php (optional)
   define('EXTREME_AI_DEBUG', false);
   define('EXTREME_AI_CACHE_TTL', 3600);
   define('EXTREME_AI_MAX_TOKENS', 4096);
   ```

3. **Admin Links**
   ```php
   // Add to admin/links/links.extreme_ai_clean.php
   $admin_file_ary = array(
       "extremeai_dashboard" => "Dashboard",
       "extremeai_providers" => "AI Providers", 
       "extremeai_settings" => "Settings",
       "extremeai_test_console" => "Test Console",
       "extremeai_analytics" => "Analytics"
   );
   ```

## ⚙️ Configuration

### 1. Basic System Settings

Access: `Admin Panel → ExtremeAI → Settings`

| Setting | Default | Description |
|---------|---------|-------------|
| Debug Mode | Disabled | Enable detailed logging |
| Cache TTL | 3600s | Response cache duration |
| Max Tokens | 4096 | Token limit per request |
| Rate Limit | 1000/hour | Requests per hour limit |
| Timeout | 30s | API request timeout |

### 2. AI Provider Configuration

Access: `Admin Panel → ExtremeAI → AI Providers`

#### OpenAI Setup
```
Provider: OpenAI GPT
API Key: sk-your-openai-api-key-here
Endpoint: https://api.openai.com/v1/chat/completions
Model: gpt-4o
```

#### Anthropic Claude Setup
```
Provider: Anthropic Claude  
API Key: sk-ant-your-anthropic-key-here
Endpoint: https://api.anthropic.com/v1/messages
Model: claude-3-haiku-20240307
```

#### Google Gemini Setup
```
Provider: Google Gemini
API Key: your-google-api-key-here
Endpoint: https://generativelanguage.googleapis.com/v1beta/models/
Model: gemini-pro
```

#### Local Ollama Setup
```
Provider: Ollama Local
API Key: (not required)
Endpoint: http://localhost:11434
Model: llama2
```

### 3. Template System Configuration

ExtremeAI uses a modern template system that integrates with your existing theme:

```php
// Templates are automatically loaded from:
// includes/extreme_ai/templates/admin/
// 
// Available templates:
// - dashboard.php - Admin dashboard
// - settings.php  - System settings
// - providers.php - Provider management  
// - styles.php    - CSS styling
```

## 🧪 Testing Installation

### 1. System Health Check
```bash
# Navigate to upgrade script
http://yoursite.com/upgrade.php
# Click "Run Health Check"
```

### 2. Provider Testing
1. Go to `Admin → ExtremeAI → AI Providers`
2. Configure a provider (add API key)
3. Click "Test Connection"
4. Verify successful response

### 3. Basic API Test
```php
// Test in a PHP file
require_once 'includes/extreme_ai/classes/ExtremeAI_Core_Clean.php';

try {
    $ai = ExtremeAI_Core::getInstance();
    $response = $ai->executeTask(
        ExtremeAI_Core::TASK_TEXT_GENERATION,
        ['prompt' => 'Hello, world!']
    );
    
    echo "Success: " . $response['response'];
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## 🗄️ Database Schema

### Core Tables Created

| Table | Purpose |
|-------|---------|
| `extreme_ai_config` | System configuration |
| `extreme_ai_providers` | AI provider settings |
| `extreme_ai_usage` | Usage tracking and analytics |
| `extreme_ai_errors` | Error logging |
| `extreme_ai_tasks` | Task queue management |
| `extreme_ai_analytics` | Aggregated statistics |
| `extreme_ai_workflows` | Automated workflows |
| `extreme_ai_agents` | AI agent definitions |

### Storage Requirements

| Component | Initial Size | Growth Rate |
|-----------|--------------|-------------|
| Core Tables | ~2MB | Minimal |
| Usage Logs | ~10KB | ~1MB/1000 requests |
| Error Logs | ~5KB | ~500KB/1000 errors |
| Analytics | ~1MB | ~100KB/month |

## 🔧 Troubleshooting

### Common Installation Issues

#### "Access Denied" Error
```bash
# Check file permissions
chmod 755 includes/extreme_ai/
chmod 644 install.php
```

#### Database Connection Failed
```php
// Verify database credentials in mainfile.php
$dbhost = "localhost";
$dbuname = "your_db_user";
$dbpass = "your_db_password";
$dbname = "your_db_name";
```

#### Missing PHP Extensions
```bash
# Install required extensions (Ubuntu/Debian)
sudo apt-get install php8.4-curl php8.4-json php8.4-mbstring php8.4-openssl

# Install required extensions (CentOS/RHEL)
sudo yum install php-curl php-json php-mbstring php-openssl
```

#### Template System Issues
1. Verify `includes/templates-evo.php` exists
2. Check `get_template_part()` function is available
3. Ensure template files have correct permissions

### Performance Optimization

#### Enable Caching
```php
// In system settings
Cache Enabled: Yes
Cache TTL: 3600 seconds (1 hour)
```

#### Database Optimization
```sql
-- Regular cleanup (run monthly)
DELETE FROM nuke_extreme_ai_usage WHERE created < DATE_SUB(NOW(), INTERVAL 90 DAY);
DELETE FROM nuke_extreme_ai_errors WHERE created < DATE_SUB(NOW(), INTERVAL 30 DAY);
OPTIMIZE TABLE nuke_extreme_ai_usage;
```

#### PHP Configuration
```ini
; Recommended PHP settings
memory_limit = 512M
max_execution_time = 60
upload_max_filesize = 10M
post_max_size = 10M
```

## 🔄 Upgrading

### Automatic Upgrade
1. Upload new files
2. Visit `http://yoursite.com/upgrade.php`
3. Click "Run Migrations"
4. Verify system health

### Manual Upgrade
1. Backup your database
2. Update files
3. Run migration SQL scripts
4. Clear caches
5. Test functionality

### Rollback Process
```bash
# If upgrade fails, use the upgrade script
http://yoursite.com/upgrade.php
# Select "Rollback" section
# Choose target version
# Confirm rollback
```

## 🗑️ Uninstallation

### Complete Removal
1. Go to `http://yoursite.com/install.php`
2. Scroll to bottom "Uninstall" section
3. Type `DELETE_ALL_DATA` to confirm
4. Click "Uninstall ExtremeAI"

### Manual Removal
```sql
-- Remove database tables
DROP TABLE IF EXISTS nuke_extreme_ai_config;
DROP TABLE IF EXISTS nuke_extreme_ai_providers;
DROP TABLE IF EXISTS nuke_extreme_ai_usage;
DROP TABLE IF EXISTS nuke_extreme_ai_errors;
DROP TABLE IF EXISTS nuke_extreme_ai_tasks;
DROP TABLE IF EXISTS nuke_extreme_ai_analytics;
DROP TABLE IF EXISTS nuke_extreme_ai_workflows;
DROP TABLE IF EXISTS nuke_extreme_ai_agents;
```

```bash
# Remove files
rm -rf includes/extreme_ai/
rm -f admin/modules/extreme_ai_clean.php
rm -f admin/case/case.extreme_ai_clean.php
rm -f admin/links/links.extreme_ai_clean.php
rm -f install.php
rm -f upgrade.php
```

## 📞 Support

### Getting Help
- **Documentation**: Full docs at `/docs/` directory
- **GitHub Issues**: Report bugs and feature requests
- **Community Forum**: Get help from other users
- **Discord Support**: Real-time chat support

### Debugging Tips
1. Enable debug mode in settings
2. Check error logs: `Admin → ExtremeAI → Analytics → Errors`
3. Run health check: `upgrade.php → Health Check`
4. Test individual providers
5. Check PHP error logs

### Log Locations
- **ExtremeAI Logs**: Database table `extreme_ai_errors`
- **PHP Logs**: `/var/log/php/error.log` (varies by system)
- **Web Server Logs**: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`

---

## 🎉 Quick Start

After successful installation:

1. **Configure Providers** → Add your API keys
2. **Test Connections** → Verify everything works  
3. **Create Content** → Start using AI features
4. **Monitor Usage** → Check analytics dashboard
5. **Optimize Settings** → Tune performance

Welcome to ExtremeAI! 🚀