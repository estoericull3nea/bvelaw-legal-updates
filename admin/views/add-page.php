<?php
/**
 * Admin add/edit page view - Form for creating or editing legal updates
 *
 * @package BVE_Law_Legal_Updates
 * @var array $categories Category slug => name map
 * @var bool $edit_mode Whether in edit mode
 * @var object|null $update_data The update being edited (null when adding)
 */

if (!defined('ABSPATH')) {
    exit;
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
