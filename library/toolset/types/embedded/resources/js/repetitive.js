/* 
 * Repetitive fields JS.
 * 
 * Used on post edit pages.
 * 
 * @since Types 1.2
 */


jQuery(document).ready(function(){
    jQuery('.mncf-repetitive-add').click(function(){
        
        var field_id = mncfGetParameterByName('field_id_md5', jQuery(this).attr('href'));
        var query = jQuery(this).attr('href').replace('http://'+window.location.host+window.ajaxurl+'?', '') + '&count='+eval('window.mncf_repetitive_count_'+field_id);
        var num = eval('window.mncf_repetitive_count_'+field_id);
        var wrapper = jQuery(this).parents('.mncf-repetitive-wrapper');
        var update = wrapper.find('.mncf-repetitive-response');

        jQuery.ajax({
            url: jQuery(this).attr('href'),
            type: 'post',
            dataType: 'json',
            data: query+'&'+jQuery('[name^="mncf"]').serialize(),
            cache: false,
            beforeSend: function() {
                update.prepend('<div class="mncf-ajax-loading-small"></div>');
            },
            success: function(data) {
                if (data != null) {
                    wrapper.find('.mncf-repetitive-sortable-wrapper').append(data.output);
                }
                update.find('.mncf-ajax-loading-small').fadeOut(function(){
                    jQuery(this).remove();
                });
                
                
                /*
                 *
                 *
                 *
                 * I think we do not need this anymore
                 */
                eval('window.mncf_repetitive_count_'+field_id+' += 1;');
            }
        });
        return false;
    });
    jQuery('.mncf-repetitive-delete').live('click', function(){
        
        var wrapper = jQuery(this).parents('.mncf-repetitive-sortable-wrapper');
        
        // Do not allow all fields to be deleted
        if (wrapper.find('.mncf-repetitive-drag-and-drop').length < 2) {
            alert(window.mncf_repetitive_last_warning);
            return false;
        }
        
        var warning = mncfGetParameterByName('mncf_warning', jQuery(this).attr('href'));
        if (warning != false) {
            var answer = confirm(warning);
            if (answer == false) {
                return false;
            }
        }
        var update = jQuery(this).parent().parent().find('.mncf-repetitive-response');
        var object = jQuery(this);
        var vars = jQuery(this).attr('href').replace(window.ajaxurl+'?', '');
        var field_id = mncfGetParameterByName('field_id_md5', jQuery(this).attr('href'));
        
        // New field
        if (jQuery(this).hasClass('mncf-repetitive-delete-new')) {
            object.parents('.mncf-repetitive-drag-and-drop').fadeOut(function(){
                jQuery(this).remove();
            });
        } else {
            jQuery.ajax({
                url: jQuery(this).attr('href'),
                type: 'post',
                dataType: 'json',
                data: vars+'&'+jQuery(this).parent().parent().find(':input').serialize(),
                cache: false,
                beforeSend: function() {
                    update.append('<div class="mncf-ajax-loading-small"></div>');
                },
                success: function(data) {
                    object.parents('.mncf-repetitive-drag-and-drop').fadeOut(function(){
                        jQuery(this).remove();
                    });
                }
            });
        }
        return false;
    });
    jQuery('.mncf-repetitive-sortable-wrapper').sortable({
        revert: true,
        handle: '.mncf-repetitive-drag',
        //        containment: 'parent'
        axis: "y"
    });
});