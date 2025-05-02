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
 * Activation hook - Create custom table and flush rewrite rules
 */
function bve_lu_activate()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'legal_updates';
    $charset_collate = $wpdb->get_charset_collate();

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

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'bve_lu_activate');

/**
 * Get allowed categories
 */
function bve_lu_get_categories()
{
    return array(
        'commercial-taxation' => 'Commercial & Taxation',
        'litigation-adr' => 'Litigation & Alternative Dispute Resolution',
        'employment' => 'Employment',
        'intellectual-property' => 'Intellectual Property',
        'immigration-citizenship' => 'Immigration and Citizenship'
    );
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
