<?php
/**
 * Frontend shortcode view - Tabbed legal updates display
 *
 * @package BVE_Law_Legal_Updates
 * @var array $categories Category slug => name map
 */

if (!defined('ABSPATH')) {
    exit;
}
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
