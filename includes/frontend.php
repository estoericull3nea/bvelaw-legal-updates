<?php
/**
 * Frontend functionality for BVE Law Legal Updates plugin
 *
 * @package BVE_Law_Legal_Updates
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Parse URL and display single update page (runs early, no rewrite rules needed)
 */
function bve_lu_parse_request()
{
    $request_uri = $_SERVER['REQUEST_URI'];

    if (strpos($request_uri, '?') !== false) {
        $request_uri = substr($request_uri, 0, strpos($request_uri, '?'));
    }

    $home_path = parse_url(home_url(), PHP_URL_PATH);
    if ($home_path) {
        $request_uri = preg_replace('#^' . preg_quote($home_path, '#') . '#', '', $request_uri);
    }

    $request_uri = trim($request_uri, '/');

    if (preg_match('#^legal-updates/([^/]+)/([^/]+)/?$#', $request_uri, $matches)) {
        $category_slug = sanitize_text_field($matches[1]);
        $update_slug = sanitize_text_field($matches[2]);

        global $wpdb;
        $table_name = $wpdb->prefix . 'legal_updates';

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
            status_header(200);

            add_action('wp', function () {
                global $wp_query;
                $wp_query->is_404 = false;
            });

            $categories = bve_lu_get_categories();
            $formatted_date = date('F j, Y', strtotime($update->created_at));
            $category_name = $categories[$update->category] ?? $update->category;
            $back_url = home_url('/legal-updates/');

            include BVE_LU_PLUGIN_DIR . 'frontend/views/single-update.php';
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
 * Shortcode: [bve_legal_updates]
 */
function bve_lu_shortcode($atts)
{
    $categories = bve_lu_get_categories();

    ob_start();
    include BVE_LU_PLUGIN_DIR . 'frontend/views/shortcode.php';
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
