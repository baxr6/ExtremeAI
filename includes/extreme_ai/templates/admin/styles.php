<?php
/**
 * ExtremeAI Admin Styles Template
 *
 * CSS styles for the ExtremeAI admin interface.
 * This template is included in admin pages to provide consistent styling.
 */

defined('NUKE_EVO') || exit;
?>

<style>
/* ===== EXTREME AI ADMIN STYLES ===== */

/* Main container */
.extreme-ai-dashboard,
.extreme-ai-settings,
.extreme-ai-providers,
.extreme-ai-analytics {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

/* Headers */
.dashboard-header,
.settings-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.dashboard-header h1,
.settings-header h1 {
    color: #2c3e50;
    font-size: 2.5em;
    margin-bottom: 10px;
    font-weight: 300;
}

.dashboard-header .lead,
.settings-header .lead {
    color: #6c757d;
    font-size: 1.2em;
    margin: 0;
}

/* Status Cards Grid */
.status-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.status-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.status-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.status-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.2);
}

.status-card .card-header h3 {
    margin: 0;
    font-size: 1.1em;
    font-weight: 500;
}

.status-card .card-header i {
    margin-right: 8px;
}

.status-card .card-body {
    padding: 20px;
}

/* System Info Card */
.system-info .info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.system-info .info-row:last-child {
    border-bottom: none;
}

.system-info .label {
    font-weight: 500;
    color: #495057;
}

.system-info .value {
    font-weight: 600;
}

.status-enabled {
    color: #28a745 !important;
}

.status-disabled {
    color: #6c757d !important;
}

.status-error {
    color: #dc3545 !important;
}

/* Usage Stats Card */
.usage-stats .card-body {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.stat-item {
    text-align: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.stat-number {
    font-size: 1.8em;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9em;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Providers List */
.providers-list {
    max-height: 200px;
    overflow-y: auto;
}

.provider-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.provider-item:last-child {
    border-bottom: none;
}

.provider-name {
    font-weight: 500;
    color: #495057;
}

.provider-status {
    font-size: 0.85em;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 12px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.provider-status.status-enabled {
    background: #d4edda;
    color: #155724;
}

.provider-status.status-disabled {
    background: #f8d7da;
    color: #721c24;
}

.no-providers {
    text-align: center;
    color: #6c757d;
    font-style: italic;
    margin: 20px 0;
}

/* Quick Actions */
.action-buttons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px;
    background: #f8f9fa;
    color: #495057;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.action-btn:hover {
    background: #e9ecef;
    color: #2c3e50;
    text-decoration: none;
    border-color: #dee2e6;
}

.action-btn i {
    margin-right: 8px;
    font-size: 1.1em;
}

.action-btn.providers:hover {
    border-color: #007bff;
    color: #007bff;
}

.action-btn.test:hover {
    border-color: #28a745;
    color: #28a745;
}

.action-btn.analytics:hover {
    border-color: #ffc107;
    color: #856404;
}

.action-btn.settings:hover {
    border-color: #6c757d;
    color: #6c757d;
}

/* Recent Activity Section */
.recent-activity-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-top: 30px;
}

.section-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
}

.section-header h2 {
    margin: 0;
    font-size: 1.5em;
    font-weight: 400;
}

.section-header i {
    margin-right: 10px;
}

.activity-content {
    padding: 20px;
}

.loader {
    text-align: center;
    color: #6c757d;
    padding: 40px;
}

.loader i {
    margin-right: 10px;
}

.activity-list {
    max-height: 300px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f8f9fa;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-time {
    font-size: 0.85em;
    color: #6c757d;
    width: 120px;
    flex-shrink: 0;
}

.activity-description {
    flex-grow: 1;
    margin: 0 15px;
    color: #495057;
}

.activity-status {
    font-size: 0.8em;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
    text-transform: uppercase;
}

.activity-status.success {
    background: #d4edda;
    color: #155724;
}

.activity-status.error {
    background: #f8d7da;
    color: #721c24;
}

.activity-status.warning {
    background: #fff3cd;
    color: #856404;
}

/* Settings Form Styles */
.settings-form {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.settings-section {
    border-bottom: 1px solid #e9ecef;
}

.settings-section:last-child {
    border-bottom: none;
}

.settings-section .section-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
}

.settings-section .section-header h2 {
    margin: 0 0 5px 0;
    font-size: 1.3em;
    font-weight: 400;
}

.settings-section .section-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95em;
}

.settings-section .section-header i {
    margin-right: 10px;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 25px;
}

.setting-group {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 20px;
    transition: background-color 0.2s ease;
}

.setting-group:hover {
    background: #e9ecef;
}

.setting-label {
    display: block;
    cursor: pointer;
}

.setting-title {
    display: block;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    font-size: 1.05em;
}

.setting-description {
    display: block;
    color: #6c757d;
    font-size: 0.9em;
    margin-top: 5px;
    line-height: 1.4;
}

.setting-input,
.setting-select {
    width: 100%;
    padding: 10px;
    border: 2px solid #dee2e6;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.2s ease;
    margin-top: 8px;
}

.setting-input:focus,
.setting-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Checkbox styling */
input[type="checkbox"] {
    width: auto !important;
    margin-right: 10px;
    transform: scale(1.2);
}

/* Provider Settings */
.provider-settings {
    margin-bottom: 25px;
    padding: 0 25px 25px 25px;
}

.provider-settings h3 {
    color: #495057;
    font-size: 1.2em;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 1px solid #dee2e6;
}

.provider-settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

/* Form Actions */
.form-actions {
    padding: 25px;
    background: #f8f9fa;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 12px 20px;
    font-weight: 500;
    text-decoration: none;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
}

.btn i {
    margin-right: 8px;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
    color: white;
    text-decoration: none;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
    color: white;
    text-decoration: none;
}

.btn-outline {
    background: transparent;
    color: #495057;
    border: 2px solid #dee2e6;
}

.btn-outline:hover {
    background: #e9ecef;
    color: #2c3e50;
    text-decoration: none;
}

/* Settings Help */
.settings-help {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-top: 30px;
    overflow: hidden;
}

.help-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 20px;
}

.help-header h3 {
    margin: 0;
    font-size: 1.3em;
    font-weight: 400;
}

.help-header i {
    margin-right: 10px;
}

.help-content {
    padding: 25px;
}

.help-item {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f8f9fa;
    line-height: 1.6;
}

.help-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.help-item strong {
    color: #2c3e50;
    display: block;
    margin-bottom: 5px;
}

/* Alert Messages */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 6px;
    font-weight: 500;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Providers Management Styles */
.extreme-ai-providers {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.providers-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.providers-header h1 {
    color: #2c3e50;
    font-size: 2.5em;
    margin-bottom: 10px;
    font-weight: 300;
}

.providers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.provider-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.provider-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.provider-card.enabled {
    border-color: #28a745;
    background: linear-gradient(135deg, #f8fff8 0%, #ffffff 100%);
}

.provider-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.provider-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.provider-icon i {
    font-size: 2.5em;
    color: rgba(255,255,255,0.9);
}

.provider-details h3 {
    margin: 0 0 8px 0;
    font-size: 1.3em;
    font-weight: 500;
}

.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.8em;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.configured {
    background: rgba(40,167,69,0.2);
    color: #155724;
    border: 1px solid rgba(40,167,69,0.3);
}

.status-badge.not-configured {
    background: rgba(220,53,69,0.2);
    color: #721c24;
    border: 1px solid rgba(220,53,69,0.3);
}

.enabled-badge {
    display: inline-block;
    margin-left: 8px;
    padding: 2px 8px;
    background: rgba(40,167,69,0.2);
    color: #155724;
    border-radius: 10px;
    font-size: 0.75em;
    font-weight: 600;
    text-transform: uppercase;
}

.provider-actions {
    display: flex;
    gap: 8px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.85em;
}

.provider-config-section {
    border-top: 1px solid #e9ecef;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
    }
    to {
        opacity: 1;
        max-height: 500px;
    }
}

.provider-form {
    padding: 25px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    display: block;
}

.label-text {
    display: block;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
    font-size: 0.95em;
}

.form-input {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: white;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.label-help {
    display: block;
    color: #6c757d;
    font-size: 0.85em;
    margin-top: 3px;
    line-height: 1.4;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 6px;
    transition: background-color 0.2s ease;
}

.checkbox-label:hover {
    background: #e9ecef;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 10px;
    transform: scale(1.2);
}

.checkbox-text {
    font-weight: 500;
    color: #2c3e50;
}

.provider-stats {
    border-top: 1px solid #e9ecef;
    padding: 20px;
    background: #f8f9fa;
}

.provider-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.provider-stats .stat-item {
    text-align: center;
    padding: 12px;
    background: white;
    border-radius: 6px;
    border: 1px solid #dee2e6;
}

.provider-stats .stat-value {
    display: block;
    font-size: 1.4em;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 3px;
}

.provider-stats .stat-label {
    font-size: 0.8em;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Test Modal Styles */
.modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1000;
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow: hidden;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.3em;
    font-weight: 500;
}

.modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5em;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s ease;
}

.modal-close:hover {
    background: rgba(255,255,255,0.2);
}

.modal-body {
    padding: 25px;
}

.test-form textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    resize: vertical;
    font-family: inherit;
    font-size: 14px;
    margin-bottom: 15px;
}

.test-form textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.test-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.test-results {
    border-top: 2px solid #e9ecef;
    padding-top: 20px;
}

.test-results h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.test-success,
.test-error {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.test-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
}

.test-success h5 {
    color: #155724;
    margin: 0 0 10px 0;
}

.test-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
}

.test-error h5 {
    color: #721c24;
    margin: 0 0 10px 0;
}

.response-text,
.error-text {
    font-family: 'Courier New', monospace;
    background: rgba(0,0,0,0.05);
    padding: 10px;
    border-radius: 4px;
    white-space: pre-wrap;
    word-break: break-word;
}

.test-metrics {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.test-metrics .metric {
    padding: 5px 10px;
    background: #e9ecef;
    border-radius: 15px;
    font-size: 0.85em;
    font-weight: 500;
    color: #495057;
}

/* Navigation Menu */
.extreme-ai-nav {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.nav-buttons {
    padding: 20px;
}

.nav-buttons a {
    color: #495057;
    text-decoration: none;
    font-weight: 500;
    padding: 8px 0;
    margin: 0 15px;
    border-bottom: 2px solid transparent;
    transition: all 0.2s ease;
}

.nav-buttons a:hover,
.nav-buttons a.active {
    color: #667eea;
    border-bottom-color: #667eea;
    text-decoration: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    .status-cards-grid {
        grid-template-columns: 1fr;
    }
    
    .settings-grid {
        grid-template-columns: 1fr;
        padding: 15px;
    }
    
    .action-buttons {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
    }
    
    .usage-stats .card-body {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .extreme-ai-dashboard,
    .extreme-ai-settings {
        padding: 10px;
    }
    
    .dashboard-header h1,
    .settings-header h1 {
        font-size: 2em;
    }
    
    .nav-buttons a {
        display: block;
        margin: 5px 0;
        text-align: center;
        padding: 10px;
    }
}

/* Dark mode support (if theme supports it) */
@media (prefers-color-scheme: dark) {
    .status-card,
    .settings-form,
    .recent-activity-section,
    .settings-help,
    .extreme-ai-nav {
        background: #2c3e50;
        color: #ecf0f1;
    }
    
    .system-info .label,
    .provider-name {
        color: #bdc3c7;
    }
    
    .stat-number {
        color: #ecf0f1;
    }
    
    .setting-group {
        background: #34495e;
    }
    
    .setting-group:hover {
        background: #3e5168;
    }
    
    .setting-input,
    .setting-select {
        background: #34495e;
        color: #ecf0f1;
        border-color: #4a5f73;
    }
}
</style>