<?php
/**
 * Plugin Name: BVE Law Legal Updates
 * Description: Admin-only CRUD for Legal Updates with frontend tabbed display
 * Version: 1.1.0
 * Author: Ericson Palisoc
 * Text Domain: bve-legal-updates
 *
 * @package BVE_Law_Legal_Updates
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BVE_LU_VERSION', '1.0.0');
define('BVE_LU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BVE_LU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BVE_LU_MAIN_FILE', __FILE__);

/**
 * Load plugin modules
 */
function bve_lu_load_plugin()
{
    // Helpers (required by other modules)
    require_once BVE_LU_PLUGIN_DIR . 'includes/helpers.php';

    // Activation / Database
    require_once BVE_LU_PLUGIN_DIR . 'includes/activator.php';

    // Admin (backend)
    require_once BVE_LU_PLUGIN_DIR . 'includes/admin.php';

    // Frontend (shortcode, single page, AJAX)
    require_once BVE_LU_PLUGIN_DIR . 'includes/frontend.php';

    // Assets (enqueue)
    require_once BVE_LU_PLUGIN_DIR . 'includes/assets.php';
}

bve_lu_load_plugin();
