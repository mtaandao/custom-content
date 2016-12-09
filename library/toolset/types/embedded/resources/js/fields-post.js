jQuery(document).ready(function(){
    /*
     * 
     * 
     * 
     * This should be triggered in icl_editor_addon_plugin.js
     * TODO Why we do not have saving cookie in common?
     */
    // Set active editor
    //    window.mncfActiveEditor = false;
    jQuery('.mn-media-buttons a, .mncf-wysiwyg .editor_addon_wrapper .item, #postdivrich .editor_addon_wrapper .item').click(function(){
        /*
         * Changed to internal var
         * See icl_editor_addon_plugin.js jQuery(document).ready()
         */
        //        window.mncfActiveEditor = jQuery(this).parents('.mncf-wysiwyg, #postdivrich').find('textarea').attr('id');
        var mncfActiveEditor = jQuery(this).parents('.mncf-wysiwyg, #postdivrich').find('textarea').attr('id');
        document.cookie = "mncfActiveEditor="+mncfActiveEditor+"; expires=Monday, 31-Dec-2020 23:59:59 GMT; path="+mncf_cookiepath+"; domain="+mncf_cookiedomain+";";
    });
    
    /*
     * Generic AJAX call (link). Parameters can be used.
     */
    jQuery('.mncf-ajax-link').live('click', function(){
        var callback = mncfGetParameterByName('mncf_ajax_callback', jQuery(this).attr('href'));
        var update = mncfGetParameterByName('mncf_ajax_update', jQuery(this).attr('href'));
        var updateAdd = mncfGetParameterByName('mncf_ajax_update_add', jQuery(this).attr('href'));
        var warning = mncfGetParameterByName('mncf_warning', jQuery(this).attr('href'));
        var thisObject = jQuery(this);
        if (warning != false) {
            var answer = confirm(warning);
            if (answer == false) {
                return false;
            }
        }
        jQuery.ajax({
            url: jQuery(this).attr('href'),
            type: 'get',
            dataType: 'json',
            //            data: ,
            cache: false,
            beforeSend: function() {
                if (update != false) {
                    jQuery('#'+update).html('').show().addClass('mncf-ajax-loading-small');
                }
            },
            success: function(data) {
                if (data != null) {
                    if (typeof data.output != 'undefined') {
                        if (update != false) {
                            jQuery('#'+update).removeClass('mncf-ajax-loading-small').html(data.output);
                        }
                        if (updateAdd != false) {
                            if (data.output.length < 1) {
                                jQuery('#'+updateAdd).fadeOut();
                            }
                            jQuery('#'+updateAdd).append(data.output);
                        }
                    }
                    if (typeof data.execute != 'undefined'
                        && (typeof data.mncf_nonce_ajax_callback != 'undefined'
                            && data.mncf_nonce_ajax_callback == mncf_nonce_ajax_callback)) {
                        eval(data.execute);
                    }
                }
                if (callback != false) {
                    eval(callback+'(data, thisObject)');
                }
            }
        });
        return false;
    });
    
    jQuery('#post').submit(function(){
        
        //
        //
        //
        //
        // TODO Remove
        // Checking unique repetitive values removed
        // Types 1.2
        //
        //        var passed = true;
        //        var checkedArr = new Array();
        //        jQuery('.mncf-repetitive-wrapper').each(function(){
        //            var parent = jQuery(this);
        //            var parentID = parent.attr('id');
        //            var childParentProcessed = false;
        //            checkedArr[parentID] = new Array();
        //            parent.find('.mncf-repetitive').each(function(index, value){
        //                var toContinue = true;
        //                if (jQuery(this).hasClass('radio')) {
        //                    var childParent = jQuery(this).parents('.form-item-radios');
        //                    var childParentId = childParent.attr('id');
        //                    if (childParentProcessed != childParentId) {
        //                        var currentValue = childParent.find(':checked').val();
        //                        childParentProcessed = childParentId;
        //                    } else {
        //                        toContinue = false;
        //                    }
        //                } else {
        //                    var currentValue = jQuery(this).val();
        //                }
        //                if (toContinue) {
        //                    if (jQuery.inArray(currentValue, checkedArr[parentID]) > -1) {
        //                        passed = false;
        //                        if (jQuery(this).hasClass('mncf-repetitive-error') == false) {
        //                            jQuery(this).before('<div class="mncf-form-error-unique-value mncf-form-error">'+mncfFormRepetitiveUniqueValuesCheckText+'</div>').focus();
        //                            jQuery(this).addClass('mncf-repetitive-error');
        //                        }
        //                    }
        //                    checkedArr[parentID].push(currentValue);
        //                }
        //            });
        //        });
        //        if (passed == false) {
        //            // Bind message fade out
        //            jQuery('.mncf-repetitive').live('click', function(){
        //                jQuery(this).removeClass('mncf-repetitive-error');
        //                jQuery(this).parents('.mncf-repetitive-wrapper').find('.mncf-form-error-unique-value').fadeOut(function(){
        //                    jQuery(this).remove();
        //                });
        //            });
        //            return false;
        //        }
        jQuery('#post .mncf-cd-failed, #post .mncf-cd-group-failed').remove();
    });
    
    jQuery('.mncf-pr-save-all-link, .mncf-pr-save-ajax').live('click', function(){
        jQuery(this).parents('.mncf-pr-has-entries').find('.mncf-cd-failed').remove();
    });
    
    // Trigger conditinal check
    //
    //First make repetitive wrapper main if any found
    jQuery('.mncf-repetitive-wrapper').find('.mncf-wrap').removeClass('mncf-wrap');
    // Now show/hide wrappers
    jQuery('.mncf-cd-passed').parents('.mncf-repetitive-wrapper').show();
    jQuery('.mncf-cd-failed').parents('.mncf-repetitive-wrapper').hide();
});


/**
 * Searches for parameter inside string ('arg', 'edit.php?arg=first&arg2=sec')
 */
function mncfGetParameterByName(name, string){
    name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
    var regexS = "[\\?&]"+name+"=([^&#]*)";
    var regex = new RegExp( regexS );
    var results = regex.exec(string);
    if (results == null) {
        return false;
    } else {
        return decodeURIComponent(results[1].replace(/\+/g, " "));
    }
}

var typesPostScreen = (function($){
    previewWarningMsg = '';
    function bindChange() {
        // Bind actions according to form element type
        $(document).ready(function(){
            $('[name^="mncf["]').each(function() {
                var $this = $(this);
                if ($this.hasClass('radio') || $this.hasClass('checkbox')) {
                    $this.bind('click', previewWarningShow);
                } else if ($this.hasClass('select')) {
                    $this.bind('change', previewWarningShow);
                } else if ($this.hasClass('mncf-datepicker')) {
                    $this.bind('mncfDateBlur', previewWarningShow);
                } else {
                    $this.bind('blur', previewWarningShow);
                }
            });
            $('.js-mnt-repadd,.js-mnt-repdelete,.js-mnt-date-clear').on('click', previewWarningShow);
            $('.js-mnt-repdrag').on('mouseup', previewWarningShow);
        });
    }
    function previewWarning(header, content) {
        $(document).ready(function(){
            $('#post-preview').before('<i class="fa fa-exclamation-triangle icon-warning-sign" id="types-preview-warning" data-header="'+header+'" data-content="'+content+'"></i>');
            bindChange();
        });
    }
    function previewWarningShow() {
        $('#types-preview-warning').show().on('click', function() {
                var $this = $(this);
                $this.pointer({
                content: '<h3>' + $this.data('header') + '</h3>' + '<p>' + $this.data('content') + '</p>',
                position: { edge: "right", align: "middle", offset: "0 0"}
            }).pointer('open');
        });
    }
    return {
        previewWarning: previewWarning
    };
})(jQuery);
