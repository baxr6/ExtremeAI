<?php
/**
 * ExtremeAI Admin Links - CLEANED VERSION
 *
 * Creates admin menu entry for ExtremeAI module
 *
 * @category Extreme_AI
 * @package  Evo-Extreme
 * @author   Deano Welch <deano.welch@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/baxr6/
 * @since    2.0.0
 * @requires PHP 8.4 or higher
 */

global $admin_file;

if (is_mod_admin('admin')) {
    adminmenu(
        $admin_file . '.php?op=extremeai_dashboard',
        'Extreme AI',
        'extreme_ai-icon.png'
    );
}