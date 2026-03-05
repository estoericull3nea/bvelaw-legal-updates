<?php
/**
 * Helper functions for BVE Law Legal Updates plugin
 *
 * @package BVE_Law_Legal_Updates
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get allowed categories from database
 *
 * @return array<string, string> Associative array of slug => name
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
 *
 * @param string $heading The heading text
 * @return string URL-safe slug
 */
function bve_lu_generate_slug($heading)
{
    $slug = strtolower($heading);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');

    if (strlen($slug) > 100) {
        $slug = substr($slug, 0, 100);
        $slug = rtrim($slug, '-');
    }

    return $slug;
}

/**
 * Get permalink for a legal update based on category and heading
 *
 * @param string $category Category slug
 * @param string $heading Update heading
 * @return string Full URL
 */
function bve_lu_get_permalink($category, $heading)
{
    $category_slug = $category;
    $slug = bve_lu_generate_slug($heading);
    return home_url('/legal-updates/' . $category_slug . '/' . $slug . '/');
}

/**
 * Generate summary from content
 *
 * @param string $content HTML content
 * @param int $length Max character length
 * @return string Plain text summary
 */
function bve_lu_generate_summary($content, $length = 150)
{
    $text = wp_strip_all_tags($content);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);

    if (strlen($text) <= $length) {
        return $text;
    }

    $summary = substr($text, 0, $length);
    $last_space = strrpos($summary, ' ');
    if ($last_space !== false) {
        $summary = substr($summary, 0, $last_space);
    }

    return $summary . '...';
}
