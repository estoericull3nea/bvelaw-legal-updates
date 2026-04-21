jQuery(document).ready(function($) {
    'use strict';
    
    // Cache object to store loaded category content
    var categoryCache = {};
    
    // Load first tab on page load
    var $tabs = $('.bve-lu-tab');
    var $content = $('.bve-lu-updates');
    var $loading = $('.bve-lu-loading');
    
    if ($tabs.length > 0) {
        // Load the first (default) tab
        var defaultCategory = $tabs.first().data('category');
        loadUpdates(defaultCategory);
    }
    
    // Tab click handler
    $tabs.on('click', function(e) {
        e.preventDefault();
        
        var $tab = $(this);
        var category = $tab.data('category');
        
        // Update active state
        $tabs.removeClass('active');
        $tab.addClass('active');
        
        // Load updates for this category
        loadUpdates(category);
    });
    
    /**
     * Load updates via AJAX (with caching)
     */
    function loadUpdates(category) {
        // Check if content is already cached
        if (categoryCache[category]) {
            // Use cached content - no loading needed
            $content.html(categoryCache[category]);
            return;
        }
        
        // Show loading
        $loading.show();
        $content.html('');
        
        $.ajax({
            url: bveLegalUpdates.ajax_url,
            type: 'POST',
            data: {
                action: 'bve_lu_get_updates',
                category: category,
                nonce: bveLegalUpdates.nonce
            },
            success: function(response) {
                $loading.hide();
                
                if (response.success) {
                    // Store in cache
                    categoryCache[category] = response.data;
                    $content.html(response.data);
                } else {
                    $content.html('<p class="bve-lu-error">Error loading updates. Please try again.</p>');
                }
            },
            error: function() {
                $loading.hide();
                $content.html('<p class="bve-lu-error">Error loading updates. Please try again.</p>');
            }
        });
    }
});
