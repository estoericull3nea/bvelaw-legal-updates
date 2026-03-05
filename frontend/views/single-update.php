<?php
/**
 * Frontend single update page view
 *
 * @package BVE_Law_Legal_Updates
 * @var object $update The legal update object
 * @var array $categories Category slug => name map
 * @var string $formatted_date Formatted date string
 * @var string $category_name Display name for category
 * @var string $back_url URL to legal updates listing
 */

if (!defined('ABSPATH')) {
    exit;
}
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
