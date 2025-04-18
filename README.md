# BVE Law Legal Updates - WordPress Plugin

A simple, secure WordPress plugin for managing and displaying legal updates with category-based tabbed navigation.

## Features

- **Admin CRUD Interface**: Complete Create, Read, Update, Delete functionality for legal updates
- **Custom Database Table**: Uses a dedicated table (not custom post types)
- **Category System**: 5 fixed categories for organizing updates
- **Tabbed Frontend Display**: AJAX-powered tabs for smooth category switching
- **Security First**: Nonces, capability checks, and proper sanitization throughout
- **Responsive Design**: Mobile-friendly layout

## Fixed Categories

1. Commercial & Taxation
2. Litigation & Alternative Dispute Resolution
3. Employment
4. Intellectual Property
5. Immigration and Citizenship

## Installation

1. Download the `bve-law-legal-updates` folder
2. Upload to `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. The custom database table will be created automatically on activation

## Database Schema

The plugin creates a custom table: `{wp_prefix}legal_updates`

```sql
CREATE TABLE wp_legal_updates (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    heading VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY category (category),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Admin Usage

### Accessing the Admin Panel

1. Navigate to **Legal Updates** in the WordPress admin menu
2. Only users with `manage_options` capability (Administrators) can access

### Adding a New Legal Update

1. Click **Legal Updates > Add New**
2. Select a **Category** from the dropdown
3. Enter a **Heading** (title for the update)
4. Add **Content** using the WYSIWYG editor
5. Click **Save Legal Update**

### Editing a Legal Update

1. Go to **Legal Updates** list page
2. Click **Edit** next to the update you want to modify
3. Make your changes
4. Click **Update Legal Update**

### Deleting a Legal Update

1. Go to **Legal Updates** list page
2. Click **Delete** next to the update
3. Confirm the deletion in the popup

## Frontend Usage

### Shortcode

Display legal updates on any page or post using the shortcode:

```
[bve_legal_updates]
```

### Example Usage

1. Create a new page: **Legal Updates**
2. Add the shortcode: `[bve_legal_updates]`
3. Publish the page
4. The page will display tabs for each category
5. Users can click tabs to view updates in each category

### Frontend Features

- **Default Tab**: "Commercial & Taxation" loads by default
- **AJAX Loading**: Updates load without page refresh
- **Responsive**: Works on desktop, tablet, and mobile devices
- **Clean Design**: Professional styling that matches most themes

## File Structure

```
bve-law-legal-updates/
├── bve-law-legal-updates.php    # Main plugin file
├── assets/
│   ├── legal-updates.js         # Frontend JavaScript
│   └── legal-updates.css        # Frontend styles
└── README.md                     # Documentation
```

## Security Features

### Capability Checks
- All admin functions require `manage_options` capability
- Unauthorized users are blocked from accessing CRUD operations

### Nonces
- Add/Edit forms use nonces: `bve_lu_save`
- Delete actions use unique nonces: `bve_lu_delete_{id}`
- AJAX requests use nonces: `bve_lu_ajax`

### Data Sanitization
- **Heading**: `sanitize_text_field()`
- **Content**: `wp_kses_post()` (allows safe HTML)
- **Category**: Validated against allowed list

### Data Escaping
- **Admin Output**: `esc_html()`, `esc_attr()`
- **Frontend Output**: `esc_html()`, `esc_attr()`, `wp_kses_post()`

### SQL Security
- All database queries use `$wpdb->prepare()` for safe parameter binding

## Technical Details

### Database Functions

**Insert New Update:**
```php
$wpdb->insert(
    $table_name,
    array(
        'heading' => $heading,
        'content' => $content,
        'category' => $category
    ),
    array('%s', '%s', '%s')
);
```

**Update Existing:**
```php
$wpdb->update(
    $table_name,
    $data,
    array('id' => $id),
    array('%s', '%s', '%s'),
    array('%d')
);
```

**Delete:**
```php
$wpdb->delete(
    $table_name,
    array('id' => $id),
    array('%d')
);
```

### AJAX Handler

The plugin includes an AJAX handler for loading updates by category:

**Action Hook:** `bve_lu_get_updates`
**Registered for:** Logged-in and non-logged-in users

**Request Format:**
```javascript
{
    action: 'bve_lu_get_updates',
    category: 'commercial-taxation',
    nonce: '...'
}
```

**Response Format:**
```javascript
{
    success: true,
    data: '<div class="bve-lu-update-item">...</div>'
}
```

### Asset Enqueuing

Frontend assets are conditionally loaded only when the shortcode is present:

```php
if (has_shortcode($post->post_content, 'bve_legal_updates')) {
    wp_enqueue_style('bve-legal-updates', ...);
    wp_enqueue_script('bve-legal-updates', ...);
}
```

## Customization

### Modifying Styles

Edit `/assets/legal-updates.css` to customize:
- Tab colors and fonts
- Update item styling
- Responsive breakpoints
- Spacing and padding

### Changing Categories

To modify categories, edit the `bve_lu_get_categories()` function in the main plugin file:

```php
function bve_lu_get_categories() {
    return array(
        'category-slug' => 'Category Display Name',
        // Add more categories
    );
}
```

**Note:** Changing categories after data exists may require migration.

## Browser Compatibility

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- MySQL 5.6 or higher

## Support

For issues or questions:
1. Check the WordPress debug log
2. Verify database table was created
3. Ensure user has `manage_options` capability
4. Clear browser cache after updates

## Version History

**1.0.0** (Initial Release)
- Complete CRUD functionality
- Custom database table
- Frontend tabbed display with AJAX
- Security hardening
- Responsive design

## License

This plugin is provided as-is for BVE Law's internal use.

## Credits

Developed for BVE Law
