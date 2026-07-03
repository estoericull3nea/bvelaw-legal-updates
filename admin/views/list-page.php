<?php
/**
 * Admin list page view - Display all legal updates
 *
 * @package BVE_Law_Legal_Updates
 * @var array $updates List of legal updates
 * @var array $categories Category slug => name map
 */

if (!defined('ABSPATH')) {
    exit;
}
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
                            <a href="<?php echo admin_url('admin.php?page=bve-legal-updates-add&action=edit&id=' . $update->id); ?>">Edit</a>
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
