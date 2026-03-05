<?php
/**
 * Admin functionality for BVE Law Legal Updates plugin
 *
 * @package BVE_Law_Legal_Updates
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu
 */
function bve_lu_admin_menu()
{
    add_menu_page(
        'Legal Updates',
        'Legal Updates',
        'manage_options',
        'bve-legal-updates',
        'bve_lu_list_page',
        'dashicons-media-document',
        30
    );

    add_submenu_page(
        'bve-legal-updates',
        'Add New Legal Update',
        'Add New',
        'manage_options',
        'bve-legal-updates-add',
        'bve_lu_add_page'
    );

    add_submenu_page(
        'bve-legal-updates',
        'Categories',
        'Categories',
        'manage_options',
        'bve-legal-updates-categories',
        'bve_lu_categories_page'
    );
}
add_action('admin_menu', 'bve_lu_admin_menu');

/**
 * List page - Display all legal updates
 */
function bve_lu_list_page()
{
    global $wpdb;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'bve_lu_delete_' . $_GET['id'])) {
            wp_die(__('Security check failed.'));
        }

        $id = intval($_GET['id']);
        $table_name = $wpdb->prefix . 'legal_updates';
        $wpdb->delete($table_name, array('id' => $id), array('%d'));

        echo '<div class="notice notice-success"><p>Legal update deleted successfully.</p></div>';
    }

    $table_name = $wpdb->prefix . 'legal_updates';
    $updates = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    $categories = bve_lu_get_categories();

    include BVE_LU_PLUGIN_DIR . 'admin/views/list-page.php';
}

/**
 * Add/Edit page - Form for creating or editing legal updates
 */
function bve_lu_add_page()
{
    global $wpdb;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $categories = bve_lu_get_categories();
    $edit_mode = false;
    $update_data = null;

    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $edit_mode = true;
        $id = intval($_GET['id']);
        $table_name = $wpdb->prefix . 'legal_updates';
        $update_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

        if (!$update_data) {
            wp_die(__('Legal update not found.'));
        }
    }

    if (isset($_POST['bve_lu_submit'])) {
        if (!isset($_POST['bve_lu_nonce']) || !wp_verify_nonce($_POST['bve_lu_nonce'], 'bve_lu_save')) {
            wp_die(__('Security check failed.'));
        }

        $category = sanitize_text_field($_POST['category']);
        $heading = sanitize_text_field($_POST['heading']);
        $content = wp_kses_post($_POST['content']);

        if (!array_key_exists($category, $categories)) {
            echo '<div class="notice notice-error"><p>Invalid category selected.</p></div>';
        } else {
            $table_name = $wpdb->prefix . 'legal_updates';
            $data = array(
                'heading' => $heading,
                'content' => $content,
                'category' => $category
            );

            if ($edit_mode && isset($_POST['update_id'])) {
                $wpdb->update(
                    $table_name,
                    $data,
                    array('id' => intval($_POST['update_id'])),
                    array('%s', '%s', '%s'),
                    array('%d')
                );
                echo '<div class="notice notice-success"><p>Legal update updated successfully. <a href="' . admin_url('admin.php?page=bve-legal-updates') . '">View all updates</a></p></div>';
                $update_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_POST['update_id'])));
            } else {
                $wpdb->insert($table_name, $data, array('%s', '%s', '%s'));
                echo '<div class="notice notice-success"><p>Legal update added successfully. <a href="' . admin_url('admin.php?page=bve-legal-updates') . '">View all updates</a></p></div>';
                $update_data = null;
                $edit_mode = false;
            }
        }
    }

    include BVE_LU_PLUGIN_DIR . 'admin/views/add-page.php';
}

/**
 * Categories CRUD page
 */
function bve_lu_categories_page()
{
    global $wpdb;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    bve_lu_ensure_categories_table();

    $table_name = $wpdb->prefix . 'legal_updates_categories';
    $edit_mode = false;
    $category_data = null;

    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'bve_lu_delete_category_' . $_GET['id'])) {
            wp_die(__('Security check failed.'));
        }

        $id = intval($_GET['id']);
        $updates_table = $wpdb->prefix . 'legal_updates';
        $category = $wpdb->get_var($wpdb->prepare("SELECT slug FROM $table_name WHERE id = %d", $id));

        if ($category) {
            $in_use = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $updates_table WHERE category = %s",
                $category
            ));

            if ($in_use > 0) {
                echo '<div class="notice notice-error"><p>Cannot delete category. It is being used by ' . $in_use . ' legal update(s).</p></div>';
            } else {
                $wpdb->delete($table_name, array('id' => $id), array('%d'));
                echo '<div class="notice notice-success"><p>Category deleted successfully.</p></div>';
            }
        }
    }

    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $edit_mode = true;
        $id = intval($_GET['id']);
        $category_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

        if (!$category_data) {
            wp_die(__('Category not found.'));
        }
    }

    if (isset($_POST['bve_lu_category_submit'])) {
        if (!isset($_POST['bve_lu_category_nonce']) || !wp_verify_nonce($_POST['bve_lu_category_nonce'], 'bve_lu_save_category')) {
            wp_die(__('Security check failed.'));
        }

        $name = sanitize_text_field($_POST['name']);
        $slug = sanitize_text_field($_POST['slug']);

        if (empty($slug)) {
            $slug = strtolower($name);
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');
        }

        if (empty($name)) {
            echo '<div class="notice notice-error"><p>Category name is required.</p></div>';
        } else {
            $data = array('name' => $name, 'slug' => $slug);

            if ($edit_mode && isset($_POST['category_id'])) {
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE slug = %s AND id != %d",
                    $slug,
                    intval($_POST['category_id'])
                ));

                if ($existing) {
                    echo '<div class="notice notice-error"><p>Category slug already exists. Please use a different slug.</p></div>';
                } else {
                    $result = $wpdb->update(
                        $table_name,
                        $data,
                        array('id' => intval($_POST['category_id'])),
                        array('%s', '%s'),
                        array('%d')
                    );

                    if ($result === false) {
                        echo '<div class="notice notice-error"><p>Error updating category: ' . $wpdb->last_error . '</p></div>';
                    } else {
                        echo '<div class="notice notice-success"><p>Category updated successfully. <a href="' . admin_url('admin.php?page=bve-legal-updates-categories') . '">View all categories</a></p></div>';
                        $category_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_POST['category_id'])));
                    }
                }
            } else {
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE slug = %s",
                    $slug
                ));

                if ($existing) {
                    echo '<div class="notice notice-error"><p>Category slug already exists. Please use a different slug.</p></div>';
                } else {
                    $result = $wpdb->insert($table_name, $data, array('%s', '%s'));

                    if ($result === false) {
                        echo '<div class="notice notice-error"><p>Error saving category: ' . $wpdb->last_error . '</p></div>';
                    } else {
                        echo '<div class="notice notice-success"><p>Category added successfully. <a href="' . admin_url('admin.php?page=bve-legal-updates-categories') . '">View all categories</a></p></div>';
                        $category_data = null;
                        $edit_mode = false;
                    }
                }
            }
        }
    }

    $categories = $wpdb->get_results("SELECT * FROM $table_name ORDER BY name ASC");
    include BVE_LU_PLUGIN_DIR . 'admin/views/categories-page.php';
}
