<?php
/**
 * Activation and database setup for BVE Law Legal Updates plugin
 *
 * @package BVE_Law_Legal_Updates
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activation hook - Create custom tables
 */
function bve_lu_activate()
{
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Create legal_updates table
    $table_name = $wpdb->prefix . 'legal_updates';
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        heading VARCHAR(255) NOT NULL,
        content LONGTEXT NOT NULL,
        category VARCHAR(100) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY category (category),
        KEY created_at (created_at)
    ) $charset_collate;";
    dbDelta($sql);

    // Create categories table
    $categories_table = $wpdb->prefix . 'legal_updates_categories';
    $sql_categories = "CREATE TABLE $categories_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        slug VARCHAR(100) NOT NULL,
        name VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug)
    ) $charset_collate;";
    dbDelta($sql_categories);

    // Insert default categories if table is empty
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $categories_table");
    if ($count == 0) {
        $default_categories = array(
            array('slug' => 'commercial-taxation', 'name' => 'Commercial & Taxation'),
            array('slug' => 'litigation-adr', 'name' => 'Litigation & Alternative Dispute Resolution'),
            array('slug' => 'employment', 'name' => 'Employment'),
            array('slug' => 'intellectual-property', 'name' => 'Intellectual Property'),
            array('slug' => 'immigration-citizenship', 'name' => 'Immigration and Citizenship')
        );
        foreach ($default_categories as $cat) {
            $wpdb->insert($categories_table, $cat, array('%s', '%s'));
        }
    }
}
register_activation_hook(BVE_LU_MAIN_FILE, 'bve_lu_activate');

/**
 * Ensure categories table exists
 */
function bve_lu_ensure_categories_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'legal_updates_categories';

    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

    if (!$table_exists) {
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(100) NOT NULL,
            name VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        dbDelta($sql);

        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count == 0) {
            $default_categories = array(
                array('slug' => 'commercial-taxation', 'name' => 'Commercial & Taxation'),
                array('slug' => 'litigation-adr', 'name' => 'Litigation & Alternative Dispute Resolution'),
                array('slug' => 'employment', 'name' => 'Employment'),
                array('slug' => 'intellectual-property', 'name' => 'Intellectual Property'),
                array('slug' => 'immigration-citizenship', 'name' => 'Immigration and Citizenship')
            );
            foreach ($default_categories as $cat) {
                $wpdb->insert($table_name, $cat, array('%s', '%s'));
            }
        }
    }
}
