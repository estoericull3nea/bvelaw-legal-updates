<?php
/**
 * Plugin Name: BVE Law Legal Updates
 * Description: Admin-only CRUD for Legal Updates with frontend tabbed display
 * Version: 1.0.0
 * Author: BVE Law
 * Text Domain: bve-legal-updates
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BVE_LU_VERSION', '1.0.0');
define('BVE_LU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BVE_LU_PLUGIN_URL', plugin_dir_url(__FILE__));

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
register_activation_hook(__FILE__, 'bve_lu_activate');

/**
 * Get allowed categories from database
 */
function bve_lu_get_categories()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'legal_updates_categories';
    $categories = $wpdb->get_results("SELECT slug, name FROM $table_name ORDER BY name ASC");

    $result = array();
    foreach ($categories as $cat) {
        $result[$cat->slug] = $cat->name;
    }

    return $result;
}

/**
 * Generate slug from heading
 */
function bve_lu_generate_slug($heading)
{
    // Convert to lowercase
    $slug = strtolower($heading);

    // Replace spaces and special characters with hyphens
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');

    // Limit length
    if (strlen($slug) > 100) {
        $slug = substr($slug, 0, 100);
        $slug = rtrim($slug, '-');
    }

    return $slug;
}

/**
 * Get permalink for a legal update based on category and heading
 */
function bve_lu_get_permalink($category, $heading)
{
    $category_slug = $category;
    $slug = bve_lu_generate_slug($heading);
    return home_url('/legal-updates/' . $category_slug . '/' . $slug . '/');
}

/**
 * Parse URL and display single update page (runs early, no rewrite rules needed)
 */
function bve_lu_parse_request()
{
    // Get the current request URI
    $request_uri = $_SERVER['REQUEST_URI'];

    // Remove query string if present
    if (strpos($request_uri, '?') !== false) {
        $request_uri = substr($request_uri, 0, strpos($request_uri, '?'));
    }

    // Get site path (in case WP is in a subdirectory)
    $home_path = parse_url(home_url(), PHP_URL_PATH);
    if ($home_path) {
        $request_uri = preg_replace('#^' . preg_quote($home_path, '#') . '#', '', $request_uri);
    }

    // Clean up the URI
    $request_uri = trim($request_uri, '/');

    // Check if this is a legal-updates URL
    if (preg_match('#^legal-updates/([^/]+)/([^/]+)/?$#', $request_uri, $matches)) {
        $category_slug = sanitize_text_field($matches[1]);
        $update_slug = sanitize_text_field($matches[2]);

        global $wpdb;
        $table_name = $wpdb->prefix . 'legal_updates';

        // Get update by category and slug
        $updates = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE category = %s",
            $category_slug
        ));

        $update = null;
        foreach ($updates as $u) {
            $slug = bve_lu_generate_slug($u->heading);
            if ($slug === $update_slug) {
                $update = $u;
                break;
            }
        }

        if ($update) {
            // Set 200 status
            status_header(200);

            // Prevent WordPress from showing 404
            add_action('wp', function () {
                global $wp_query;
                $wp_query->is_404 = false;
            });

            $categories = bve_lu_get_categories();
            $formatted_date = date('F j, Y', strtotime($update->created_at));
            $category_name = $categories[$update->category] ?? $update->category;

            // Back link to legal updates page
            $back_url = home_url('/legal-updates/');

            // Output the page
            ?>
            <!DOCTYPE html>
            <html <?php language_attributes(); ?>>

            <head>
                <meta charset="<?php bloginfo('charset'); ?>">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title><?php echo esc_html($update->heading); ?> - <?php bloginfo('name'); ?></title>
                <link rel="stylesheet" href="<?php echo BVE_LU_PLUGIN_URL . 'assets/legal-updates.css?ver=' . BVE_LU_VERSION; ?>">
                <?php wp_head(); ?>
            </head>

            <body <?php body_class('bve-lu-single-page'); ?>>
                <?php
                if (function_exists('wp_body_open')) {
                    wp_body_open();
                }
                ?>
                <div class="bve-lu-single-hero">
                    <div class="bve-lu-single-hero-overlay"></div>
                    <div class="bve-lu-single-hero-content">
                        <h1 class="bve-lu-single-hero-heading"><?php echo esc_html($update->heading); ?></h1>
                    </div>
                </div>
                <div class="bve-lu-single-wrapper">
                    <a href="<?php echo esc_url($back_url); ?>" class="bve-lu-back-link">← Back to Legal Updates</a>
                    <article class="bve-lu-single">
                        <div class="bve-lu-single-header">
                            <span class="bve-lu-single-category"><?php echo esc_html($category_name); ?></span>
                            <time class="bve-lu-single-date"
                                datetime="<?php echo esc_attr(date('Y-m-d', strtotime($update->created_at))); ?>"><?php echo esc_html($formatted_date); ?></time>
                        </div>
                        <div class="bve-lu-single-content">
                            <?php echo wp_kses_post($update->content); ?>
                        </div>
                    </article>
                </div>
                <?php wp_footer(); ?>
            </body>

            </html>
            <?php
            exit;
        }
    }
}
add_action('init', 'bve_lu_parse_request', 1);

/**
 * Set page title for single update
 */
function bve_lu_single_update_title($title)
{
    $update_slug = get_query_var('legal_update');
    $category_slug = get_query_var('legal_update_category');

    if ($update_slug && $category_slug) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'legal_updates';

        $updates = $wpdb->get_results($wpdb->prepare(
            "SELECT heading FROM $table_name WHERE category = %s",
            $category_slug
        ));

        foreach ($updates as $u) {
            $slug = bve_lu_generate_slug($u->heading);
            if ($slug === $update_slug) {
                return esc_html($u->heading) . ' - Legal Update';
            }
        }
    }

    return $title;
}
add_filter('wp_title', 'bve_lu_single_update_title', 10, 1);
add_filter('document_title_parts', function ($title_parts) {
    $update_slug = get_query_var('legal_update');
    $category_slug = get_query_var('legal_update_category');

    if ($update_slug && $category_slug) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'legal_updates';

        $updates = $wpdb->get_results($wpdb->prepare(
            "SELECT heading FROM $table_name WHERE category = %s",
            $category_slug
        ));

        foreach ($updates as $u) {
            $slug = bve_lu_generate_slug($u->heading);
            if ($slug === $update_slug) {
                $title_parts['title'] = esc_html($u->heading);
                $title_parts['site'] = get_bloginfo('name');
                break;
            }
        }
    }

    return $title_parts;
});

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

    // Handle delete action
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

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Legal Updates</h1>
        <a href="<?php echo admin_url('admin.php?page=bve-legal-updates-add'); ?>" class="page-title-action">Add New</a>
        <hr class="wp-header-end">

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Heading</th>
                    <th style="width: 200px;">Category</th>
                    <th style="width: 150px;">Created Date</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($updates)): ?>
                    <tr>
                        <td colspan="5">No legal updates found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($updates as $update): ?>
                        <tr>
                            <td><?php echo esc_html($update->id); ?></td>
                            <td><?php echo esc_html($update->heading); ?></td>
                            <td><?php echo esc_html($categories[$update->category] ?? $update->category); ?></td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($update->created_at))); ?></td>
                            <td>
                                <a
                                    href="<?php echo admin_url('admin.php?page=bve-legal-updates-add&action=edit&id=' . $update->id); ?>">Edit</a>
                                |
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=bve-legal-updates&action=delete&id=' . $update->id), 'bve_lu_delete_' . $update->id); ?>"
                                    onclick="return confirm('Are you sure you want to delete this legal update?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
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

    // Check if editing
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $edit_mode = true;
        $id = intval($_GET['id']);
        $table_name = $wpdb->prefix . 'legal_updates';
        $update_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

        if (!$update_data) {
            wp_die(__('Legal update not found.'));
        }
    }

    // Handle form submission
    if (isset($_POST['bve_lu_submit'])) {
        if (!isset($_POST['bve_lu_nonce']) || !wp_verify_nonce($_POST['bve_lu_nonce'], 'bve_lu_save')) {
            wp_die(__('Security check failed.'));
        }

        $category = sanitize_text_field($_POST['category']);
        $heading = sanitize_text_field($_POST['heading']);
        $content = wp_kses_post($_POST['content']);

        // Validate category
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
                // Update existing
                $wpdb->update(
                    $table_name,
                    $data,
                    array('id' => intval($_POST['update_id'])),
                    array('%s', '%s', '%s'),
                    array('%d')
                );
                echo '<div class="notice notice-success"><p>Legal update updated successfully. <a href="' . admin_url('admin.php?page=bve-legal-updates') . '">View all updates</a></p></div>';

                // Refresh data
                $update_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_POST['update_id'])));
            } else {
                // Insert new
                $wpdb->insert($table_name, $data, array('%s', '%s', '%s'));
                echo '<div class="notice notice-success"><p>Legal update added successfully. <a href="' . admin_url('admin.php?page=bve-legal-updates') . '">View all updates</a></p></div>';

                // Clear form
                $update_data = null;
                $edit_mode = false;
            }
        }
    }

    ?>
    <div class="wrap">
        <h1><?php echo $edit_mode ? 'Edit Legal Update' : 'Add New Legal Update'; ?></h1>

        <form method="post" action="">
            <?php wp_nonce_field('bve_lu_save', 'bve_lu_nonce'); ?>
            <?php if ($edit_mode && $update_data): ?>
                <input type="hidden" name="update_id" value="<?php echo esc_attr($update_data->id); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="category">Category *</label></th>
                    <td>
                        <select name="category" id="category" class="regular-text" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($update_data->category ?? '', $key); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="heading">Heading *</label></th>
                    <td>
                        <input type="text" name="heading" id="heading" class="regular-text"
                            value="<?php echo esc_attr($update_data->heading ?? ''); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="content">Content *</label></th>
                    <td>
                        <?php
                        wp_editor(
                            $update_data->content ?? '',
                            'content',
                            array(
                                'textarea_name' => 'content',
                                'textarea_rows' => 15,
                                'media_buttons' => true,
                                'teeny' => false
                            )
                        );
                        ?>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="bve_lu_submit" class="button button-primary"
                    value="<?php echo $edit_mode ? 'Update Legal Update' : 'Save Legal Update'; ?>">
                <a href="<?php echo admin_url('admin.php?page=bve-legal-updates'); ?>" class="button">Cancel</a>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Ensure categories table exists
 */
function bve_lu_ensure_categories_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'legal_updates_categories';

    // Check if table exists
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

        // Insert default categories if table was just created
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

/**
 * Categories CRUD page
 */
function bve_lu_categories_page()
{
    global $wpdb;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Ensure table exists
    bve_lu_ensure_categories_table();

    $table_name = $wpdb->prefix . 'legal_updates_categories';
    $edit_mode = false;
    $category_data = null;

    // Handle delete action
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'bve_lu_delete_category_' . $_GET['id'])) {
            wp_die(__('Security check failed.'));
        }

        $id = intval($_GET['id']);

        // Check if category is being used
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

    // Check if editing
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $edit_mode = true;
        $id = intval($_GET['id']);
        $category_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

        if (!$category_data) {
            wp_die(__('Category not found.'));
        }
    }

    // Handle form submission
    if (isset($_POST['bve_lu_category_submit'])) {
        if (!isset($_POST['bve_lu_category_nonce']) || !wp_verify_nonce($_POST['bve_lu_category_nonce'], 'bve_lu_save_category')) {
            wp_die(__('Security check failed.'));
        }

        $name = sanitize_text_field($_POST['name']);
        $slug = sanitize_text_field($_POST['slug']);

        // Generate slug if empty
        if (empty($slug)) {
            $slug = strtolower($name);
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');
        }

        // Validate
        if (empty($name)) {
            echo '<div class="notice notice-error"><p>Category name is required.</p></div>';
        } else {
            $data = array(
                'name' => $name,
                'slug' => $slug
            );

            if ($edit_mode && isset($_POST['category_id'])) {
                // Check if slug already exists (excluding current)
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE slug = %s AND id != %d",
                    $slug,
                    intval($_POST['category_id'])
                ));

                if ($existing) {
                    echo '<div class="notice notice-error"><p>Category slug already exists. Please use a different slug.</p></div>';
                } else {
                    // Update existing
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

                        // Refresh data
                        $category_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_POST['category_id'])));
                    }
                }
            } else {
                // Check if slug already exists
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE slug = %s",
                    $slug
                ));

                if ($existing) {
                    echo '<div class="notice notice-error"><p>Category slug already exists. Please use a different slug.</p></div>';
                } else {
                    // Insert new
                    $result = $wpdb->insert($table_name, $data, array('%s', '%s'));

                    if ($result === false) {
                        echo '<div class="notice notice-error"><p>Error saving category: ' . $wpdb->last_error . '</p></div>';
                    } else {
                        echo '<div class="notice notice-success"><p>Category added successfully. <a href="' . admin_url('admin.php?page=bve-legal-updates-categories') . '">View all categories</a></p></div>';

                        // Clear form
                        $category_data = null;
                        $edit_mode = false;
                    }
                }
            }
        }
    }

    // Get all categories
    $categories = $wpdb->get_results("SELECT * FROM $table_name ORDER BY name ASC");

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo $edit_mode ? 'Edit Category' : 'Categories'; ?></h1>
        <?php if (!$edit_mode): ?>
            <a href="<?php echo admin_url('admin.php?page=bve-legal-updates-categories&action=add'); ?>"
                class="page-title-action">Add New</a>
        <?php endif; ?>
        <hr class="wp-header-end">

        <?php if ($edit_mode || (isset($_GET['action']) && $_GET['action'] === 'add')): ?>
            <!-- Add/Edit Form -->
            <form method="post"
                action="<?php echo admin_url('admin.php?page=bve-legal-updates-categories' . ($edit_mode ? '&action=edit&id=' . intval($_GET['id']) : '&action=add')); ?>">
                <?php wp_nonce_field('bve_lu_save_category', 'bve_lu_category_nonce'); ?>
                <?php if ($edit_mode && $category_data): ?>
                    <input type="hidden" name="category_id" value="<?php echo esc_attr($category_data->id); ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="name">Category Name *</label></th>
                        <td>
                            <input type="text" name="name" id="name" class="regular-text"
                                value="<?php echo esc_attr($category_data->name ?? ''); ?>" required>
                            <p class="description">The display name for this category.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slug">Category Slug</label></th>
                        <td>
                            <input type="text" name="slug" id="slug" class="regular-text"
                                value="<?php echo esc_attr($category_data->slug ?? ''); ?>">
                            <p class="description">URL-friendly version of the name. Leave empty to auto-generate from name.</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="bve_lu_category_submit" class="button button-primary"
                        value="<?php echo $edit_mode ? 'Update Category' : 'Add Category'; ?>">
                    <a href="<?php echo admin_url('admin.php?page=bve-legal-updates-categories'); ?>" class="button">Cancel</a>
                </p>
            </form>
        <?php else: ?>
            <!-- List View -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Name</th>
                        <th style="width: 200px;">Slug</th>
                        <th style="width: 150px;">Created Date</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5">No categories found. <a
                                    href="<?php echo admin_url('admin.php?page=bve-legal-updates-categories&action=add'); ?>">Add
                                    your first category</a>.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo esc_html($cat->id); ?></td>
                                <td><strong><?php echo esc_html($cat->name); ?></strong></td>
                                <td><code><?php echo esc_html($cat->slug); ?></code></td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($cat->created_at))); ?></td>
                                <td>
                                    <a
                                        href="<?php echo admin_url('admin.php?page=bve-legal-updates-categories&action=edit&id=' . $cat->id); ?>">Edit</a>
                                    |
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=bve-legal-updates-categories&action=delete&id=' . $cat->id), 'bve_lu_delete_category_' . $cat->id); ?>"
                                        onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Generate summary from content
 */
function bve_lu_generate_summary($content, $length = 150)
{
    // Strip HTML tags
    $text = wp_strip_all_tags($content);

    // Remove extra whitespace
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);

    // If content is shorter than length, return as is
    if (strlen($text) <= $length) {
        return $text;
    }

    // Truncate to length
    $summary = substr($text, 0, $length);

    // Find last space to avoid cutting words
    $last_space = strrpos($summary, ' ');
    if ($last_space !== false) {
        $summary = substr($summary, 0, $last_space);
    }

    return $summary . '...';
}

/**
 * Shortcode: [bve_legal_updates]
 */
function bve_lu_shortcode($atts)
{
    $categories = bve_lu_get_categories();

    ob_start();
    ?>
    <div class="bve-legal-updates-wrapper">
        <div class="bve-lu-tabs">
            <?php $first = true; ?>
            <?php foreach ($categories as $key => $label): ?>
                <button class="bve-lu-tab <?php echo $first ? 'active' : ''; ?>" data-category="<?php echo esc_attr($key); ?>">
                    <?php echo esc_html($label); ?>
                </button>
                <?php $first = false; ?>
            <?php endforeach; ?>
        </div>

        <div class="bve-lu-content">
            <div class="bve-lu-loading" style="display: none;">Loading...</div>
            <div class="bve-lu-updates"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('bve_legal_updates', 'bve_lu_shortcode');

/**
 * AJAX handler for loading updates by category
 */
function bve_lu_ajax_get_updates()
{
    check_ajax_referer('bve_lu_ajax', 'nonce');

    global $wpdb;

    $category = sanitize_text_field($_POST['category']);
    $categories = bve_lu_get_categories();

    // Validate category
    if (!array_key_exists($category, $categories)) {
        wp_send_json_error('Invalid category');
    }

    $table_name = $wpdb->prefix . 'legal_updates';
    $updates = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE category = %s ORDER BY created_at DESC",
        $category
    ));

    ob_start();

    if (empty($updates)) {
        echo '<p class="bve-lu-no-updates">No updates available in this category.</p>';
    } else {
        foreach ($updates as $update) {
            $summary = bve_lu_generate_summary($update->content, 150);
            $formatted_date = date('F j, Y', strtotime($update->created_at));
            $content_text = wp_strip_all_tags($update->content);
            $content_text = preg_replace('/\s+/', ' ', $content_text);
            $content_text = trim($content_text);
            $has_more = strlen($content_text) > 150;
            $permalink = bve_lu_get_permalink($update->category, $update->heading);
            ?>
            <div class="bve-lu-update-item">
                <h3 class="bve-lu-heading"><?php echo esc_html($update->heading); ?></h3>
                <div class="bve-lu-date"><?php echo esc_html($formatted_date); ?></div>
                <div class="bve-lu-summary"><?php echo esc_html($summary); ?></div>
                <?php if ($has_more): ?>
                    <a href="<?php echo esc_url($permalink); ?>" class="bve-lu-read-more" target="_blank" rel="noopener noreferrer">Read
                        More</a>
                <?php endif; ?>
            </div>
            <?php
        }
    }

    $html = ob_get_clean();
    wp_send_json_success($html);
}
add_action('wp_ajax_bve_lu_get_updates', 'bve_lu_ajax_get_updates');
add_action('wp_ajax_nopriv_bve_lu_get_updates', 'bve_lu_ajax_get_updates');

/**
 * Enqueue frontend scripts and styles
 */
function bve_lu_enqueue_frontend_assets()
{
    global $post;

    // Enqueue if shortcode is present or if viewing single update
    $is_single_update = get_query_var('legal_update') && get_query_var('legal_update_category');

    if ($is_single_update || (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'bve_legal_updates'))) {
        wp_enqueue_style('bve-legal-updates', BVE_LU_PLUGIN_URL . 'assets/legal-updates.css', array(), BVE_LU_VERSION);

        // Only enqueue script if shortcode is present (not needed for single page)
        if (!$is_single_update && is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'bve_legal_updates')) {
            wp_enqueue_script('bve-legal-updates', BVE_LU_PLUGIN_URL . 'assets/legal-updates.js', array('jquery'), BVE_LU_VERSION, true);

            wp_localize_script('bve-legal-updates', 'bveLegalUpdates', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bve_lu_ajax')
            ));
        }
    }
}
add_action('wp_enqueue_scripts', 'bve_lu_enqueue_frontend_assets');
