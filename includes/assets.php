<?php
/**
 * Asset enqueuing for BVE Law Legal Updates plugin
 *
 * @package BVE_Law_Legal_Updates
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue frontend scripts and styles
 */
function bve_lu_enqueue_frontend_assets()
{
    global $post;

    $is_single_update = get_query_var('legal_update') && get_query_var('legal_update_category');

    if ($is_single_update || (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'bve_legal_updates'))) {
        wp_enqueue_style('bve-legal-updates', BVE_LU_PLUGIN_URL . 'assets/legal-updates.css', array(), BVE_LU_VERSION);

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
