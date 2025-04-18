# Quick Start Guide - BVE Law Legal Updates

## Installation Steps

1. **Upload Plugin**
   ```
   /wp-content/plugins/bve-law-legal-updates/
   ```

2. **Activate**
   - Go to WordPress Admin > Plugins
   - Find "BVE Law Legal Updates"
   - Click "Activate"

3. **Verify Database Table**
   - Table `wp_legal_updates` should be created automatically
   - Check via phpMyAdmin or database tool if needed

## Testing the Plugin

### Test 1: Add a Legal Update

1. Go to **Legal Updates > Add New**
2. Fill in the form:
   - Category: Commercial & Taxation
   - Heading: "New Tax Regulation 2024"
   - Content: "The government has announced new tax regulations effective..."
3. Click **Save Legal Update**
4. Verify success message appears
5. Go to **Legal Updates** list - confirm it appears

### Test 2: Edit a Legal Update

1. Go to **Legal Updates** list
2. Click **Edit** on the update you just created
3. Change the heading to "Updated Tax Regulation 2024"
4. Click **Update Legal Update**
5. Verify the change in the list

### Test 3: Delete a Legal Update

1. Go to **Legal Updates** list
2. Click **Delete** on an update
3. Confirm the deletion popup
4. Verify the update is removed from the list

### Test 4: Frontend Display

1. Create a new page: **Pages > Add New**
2. Title: "Legal Updates"
3. Add shortcode to content:
   ```
   [bve_legal_updates]
   ```
4. Publish the page
5. View the page on frontend
6. Verify:
   - Tabs are displayed
   - "Commercial & Taxation" tab is active by default
   - Updates load when clicking different tabs
   - No page refresh when switching tabs

### Test 5: Multiple Categories

1. Add updates to different categories:
   - Add 2 updates to "Commercial & Taxation"
   - Add 1 update to "Employment"
   - Add 1 update to "Litigation & Alternative Dispute Resolution"
2. Go to frontend page
3. Click each tab and verify correct updates appear

### Test 6: Security Tests

**Unauthorized Access:**
1. Log out or use a non-admin account
2. Try to access: `/wp-admin/admin.php?page=bve-legal-updates`
3. Should see "You do not have sufficient permissions"

**Direct Delete Attempt:**
1. Try accessing delete URL without nonce
2. Should see "Security check failed"

### Test 7: Responsive Design

1. View frontend page on desktop
2. View on tablet (768px width)
3. View on mobile (375px width)
4. Verify tabs and content display properly on all sizes

## Sample Data for Testing

### Commercial & Taxation
**Heading:** "Corporate Tax Rate Changes 2024"
**Content:** "Effective January 1, 2024, corporate tax rates have been adjusted. Small businesses with annual revenue under $500,000 will benefit from a reduced rate of 12%, down from 15%."

### Litigation & ADR
**Heading:** "New Arbitration Procedures"
**Content:** "The Commercial Arbitration Act has been amended to streamline dispute resolution processes. Parties can now opt for expedited arbitration in cases involving amounts under $100,000."

### Employment
**Heading:** "Minimum Wage Increase"
**Content:** "The minimum wage has been increased to $15.50 per hour, effective March 1, 2024. Employers must update their payroll systems accordingly."

### Intellectual Property
**Heading:** "Patent Filing Deadlines Extended"
**Content:** "Due to backlog issues, the patent office has extended filing deadlines by 60 days for all provisional applications submitted in Q1 2024."

### Immigration and Citizenship
**Heading:** "Work Permit Processing Times"
**Content:** "Current processing times for work permit applications have decreased to an average of 45 days, down from the previous 90-day timeframe."

## Troubleshooting

### Updates Don't Load on Frontend

**Check:**
1. Is the shortcode spelled correctly? `[bve_legal_updates]`
2. View page source - are CSS/JS files loaded?
3. Check browser console for JavaScript errors
4. Verify WordPress jQuery is loaded

**Solution:**
- Clear browser cache
- Try different browser
- Check if other plugins conflict
- Verify assets folder exists and files are readable

### Can't See Admin Menu

**Check:**
1. Are you logged in as Administrator?
2. Does your user have `manage_options` capability?

**Solution:**
- Log in with admin account
- Check user role permissions

### Database Table Not Created

**Check:**
1. phpMyAdmin or database tool for `wp_legal_updates` table
2. WordPress database prefix (might not be `wp_`)

**Solution:**
- Deactivate and reactivate plugin
- Manually run the CREATE TABLE SQL from README.md
- Check for database errors in WordPress debug log

### AJAX Not Working

**Check:**
1. Browser console for errors
2. Network tab in browser dev tools
3. Check if nonce is being passed

**Solution:**
- Clear all caches (browser, WordPress, CDN)
- Disable other plugins temporarily to test for conflicts
- Verify AJAX URL is correct (should be `/wp-admin/admin-ajax.php`)

## Performance Notes

- Plugin loads assets ONLY when shortcode is present
- AJAX requests are optimized for speed
- Database queries use proper indexing
- No external dependencies

## Next Steps

After installation and testing:

1. **Add Real Content**: Replace test data with actual legal updates
2. **Style Customization**: Adjust CSS to match your site theme
3. **Create Navigation**: Add link to legal updates page in site menu
4. **Set Permissions**: Ensure only appropriate users have admin access
5. **Regular Updates**: Keep adding new legal updates to keep content fresh

## Shortcode Usage Examples

**Basic:**
```
[bve_legal_updates]
```

**In Page Builder:**
Most page builders support shortcode blocks. Add a shortcode block and paste `[bve_legal_updates]`

**In Widget:**
If your theme supports widget areas, you can add the shortcode widget and use `[bve_legal_updates]`

**In Template:**
```php
<?php echo do_shortcode('[bve_legal_updates]'); ?>
```

## Common Questions

**Q: Can I change the category names?**
A: Yes, edit the `bve_lu_get_categories()` function in the main plugin file. Note that existing data uses category slugs, not names.

**Q: Can I add more categories?**
A: Yes, add them to the `bve_lu_get_categories()` array. Existing data will not be affected.

**Q: How do I export/backup the data?**
A: Export the `wp_legal_updates` table using phpMyAdmin or your database tool.

**Q: Can non-admins view the updates?**
A: Yes! The frontend display is public. Only admin CRUD operations require `manage_options` capability.

**Q: Does it work with page builders?**
A: Yes, the shortcode works with Elementor, Divi, Gutenberg, and other page builders.

## Support Checklist

Before requesting support:

- [ ] Plugin is activated
- [ ] Database table exists
- [ ] User has admin permissions
- [ ] Shortcode is spelled correctly
- [ ] Browser cache cleared
- [ ] JavaScript console checked for errors
- [ ] WordPress and PHP meet minimum requirements
- [ ] No conflicting plugins active

---

**Plugin Version:** 1.0.0  
**Last Updated:** 2024
