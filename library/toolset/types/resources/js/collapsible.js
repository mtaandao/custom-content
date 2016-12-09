jQuery(document).ready(function(){
    jQuery('.mncf-collapsible-button').live('click', function() {
        var toggleButton = jQuery(this);
        var toggleDiv = jQuery('#'+jQuery(this).attr('id')+'-toggle');
        toggleDiv.slideToggle(function(){
            if (jQuery(this).is(':visible')) {
                jQuery.get(toggleButton.attr('href')+'&hidden=0');
                toggleButton.removeClass('mncf-collapsible-button-collapsed');
            } else {
                jQuery.get(toggleButton.attr('href')+'&hidden=1');
                toggleButton.addClass('mncf-collapsible-button-collapsed');
            }
        });
        return false;
    });
    jQuery('.mncf-toggle-wrapper').each(function(){
        if (typeof mncf_collapsed != 'undefined') {
            if (jQuery.inArray(jQuery(this).attr('id'), mncf_collapsed) == -1) {
                jQuery(this).slideDown();
            } else {
                var toggleButton = jQuery('#'+jQuery(this).attr('id').replace('-toggle', ''));
                toggleButton.addClass('mncf-collapsible-button-collapsed');
            }
        } else {
            jQuery(this).slideDown();
        }
    });
});