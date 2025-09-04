<?php
/**
 * ExtremeAI Admin Case Handler - CLEANED VERSION
 *
 * Routes admin requests to the main ExtremeAI admin module
 *
 * @category Extreme_AI
 * @package  Evo-Extreme
 * @author   Deano Welch <deano.welch@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/baxr6/
 * @since    2.0.0
 * @requires PHP 8.4 or higher
 */

if (!defined('ADMIN_FILE')) {
    die('Access Denied!');
}

switch($op) {
    case "extremeai_dashboard":
    case "extremeai_providers":
    case "extremeai_settings":
    case "extremeai_test_console":
    case "extremeai_analytics":
    case "extremeai_workflows":
    case "extremeai_agents":
        include("admin/modules/extreme_ai_clean.php");  // FIXED: Use clean version
        break;
}
?>