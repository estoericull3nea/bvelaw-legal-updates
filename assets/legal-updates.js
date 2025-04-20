jQuery(document).ready(function($) {
    'use strict';
    
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
     * Load updates via AJAX
     */
    function loadUpdates(category) {
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
