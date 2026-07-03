<?php
/**
 * Admin categories page view - List and add/edit categories
 *
 * @package BVE_Law_Legal_Updates
 * @var array $categories List of category objects
 * @var bool $edit_mode Whether in edit mode
 * @var object|null $category_data The category being edited (null when adding)
 */

if (!defined('ABSPATH')) {
    exit;
}
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
                                <a href="<?php echo admin_url('admin.php?page=bve-legal-updates-categories&action=edit&id=' . $cat->id); ?>">Edit</a>
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
