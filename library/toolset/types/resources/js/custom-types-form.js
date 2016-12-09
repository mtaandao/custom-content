/**
 *
 * Post Types form JS
 *
 *
 */

jQuery(document).ready(function($){
    /**
     * setup title
     */
    var labelPostType = $('#post-body-content .js-mncf-slugize-source').val() != ''
        ? $('#post-body-content .js-mncf-slugize-source').val()
        : $('#post-body-content .js-mncf-slugize-source' ).data('anonymous-post-type');

    $('.js-mncf-singular').html( labelPostType );
    $('#post-body-content').on('keyup input cut paste', '.js-mncf-slugize-source', function() {
        $('.js-mncf-singular').html($(this).val());
    });
    /*
     * 
     * Submit form trigger
     */
    $('.mncf-types-form').submit(function(){

        /**
         * do not check builtin post types
         */
        if ( '_builtin' == jQuery('.mncf-form-submit', jQuery(this)).data('post_type_is_builtin') ) {
            return true;
        }
        /*
         * Check if singular and plural are same
         */
        if ( jQuery('#name-singular').val().length > 0 ) {
            if ( jQuery('#name-singular').val().toLowerCase() == jQuery('#name-plural').val().toLowerCase()) {
                if (jQuery('#mncf_warning_same_as_slug input[type=checkbox]').is(':checked')) {
                    return true;
                }
                jQuery('#mncf_warning_same_as_slug').fadeOut();
                alert(jQuery('#name-plural').data('mncf_warning_same_as_slug'));
                jQuery('#name-plural').after(
                    '<div class="mncf-error message updated" id="mncf_warning_same_as_slug"><p>'
                    + jQuery('#name-plural').data('mncf_warning_same_as_slug')
                    + '</p><p><input type="checkbox" name="ct[labels][ignore]" />'
                    + jQuery('#name-plural').data('mncf_warning_same_as_slug_ignore')
                    + '</p></div>'
                    ).focus().bind('click', function(){
                        jQuery('#mncf_warning_same_as_slug').fadeOut();
                    });
                mncfLoadingButtonStop();
                jQuery('html, body').animate({
                    scrollTop: 0
                }, 500);
                return false;
            }
            jQuery(this).removeClass('js-types-do-not-show-modal');
        }

        /**
         * check for reserved names and already used slugs
         */
        return jQuery( this ).mncfProveSlug();
    });
    /**
     * modal advertising
     */
    /*if(
        jQuery.isFunction(jQuery.fn.types_modal_box)) {
        jQuery('.mncf-types-form').types_modal_box();
    }
    */
    /**
     * choose icon
     */
    $( document ).on( 'click', '.js-mncf-choose-icon', function() {
        var $thiz = $(this);
        // show a spinner or something via css
        var dialog = $('<div style="display:none;height:450px;" class="mncf-dashicons"><span class="spinner"></span>'+$thiz.data('mncf-message-loading')+'</div>').appendTo('body');
        // open the dialog
        dialog.dialog({
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            },
            dialogClass: 'mncf-choose-icon mncf-ui-dialog',
            modal: true,
            minWidth: 800,
            maxHeight: .9*$(window).height(),
            title: $thiz.data('mncf-title'),
            position: { my: "center top+50", at: "center top", of: window },

        });
        // load remote content
        dialog.load(
            ajaxurl, 
            {
                action: 'mncf_edit_post_get_icons_list',
                _mnnonce: $thiz.data('mncf-nonce'),
                slug: $thiz.data('mncf-value'),
                "mncf-post-type": $thiz.data('mncf-post-type'),
            },
            function (responseText, textStatus, XMLHttpRequest) {
                $(dialog).on('keyup input cut paste', '.js-mncf-search', function() {
                    if ( '' == $(this).val() ) {
                        $('li', dialog).show();
                    } else {
                        var re = new RegExp($(this).val(), "i");
                        $('li', dialog).each(function(){
                            if ( !$(this).data('mncf-icon').match(re) ) {
                                $(this).hide();
                            } else {
                                $(this).show();
                            }
                        });
                    }
                });
                $(dialog).on('click', 'a', function() {
                    var $icon = $(this).data('mncf-icon');
                    $('#mncf-types-icon').val($icon);
                    $thiz.data('mncf-value', $icon);
                    classes = 'mncf-types-menu-image dashicons-before dashicons-'+$icon;
                    $('div.mncf-types-menu-image').removeClass().addClass(classes);
                    dialog.dialog( "close" );
                    return false;
                });
            }
            );
        //prevent the browser to follow the link
        return false;
    });
    /**
     * post types
     */
    $(document).on( 'change', '.js-mncf-relationship-checkbox', function() {
        var $value = $(this).data('mncf-value');
        var $type = $(this).data('mncf-type');

        if ( $(this).is(':checked') ) {
            $(this).parent().addClass('active');
            $('.js-mncf-relationship-checkbox').each(function(){
                if ( $value == $(this).data('mncf-value') && $type != $(this).data('mncf-type') ) {
                    $(this).attr('disabled', 'disabled').parent().addClass('disabled');
                    $(this).closest('li').attr('title', $(this).data('mncf-message-disabled'));
                }
            });
        } else {
            $(this).parent().removeClass('active');
            $('.js-mncf-relationship-checkbox').each(function(){
                if ( $value == $(this).data('mncf-value') ){
                    $(this).removeAttr('disabled').parent().removeClass('disabled');
                    $(this).closest('li').removeAttr('title');
                }
            });
        }
    });
    $('#relationship :disabled').each(function(){
        $(this).closest('li').attr( 'title', $(this).data('mncf-message-disabled'));
    });
    /**
     * choose fields
     */
    $( document ).on( 'click', '.js-mncf-edit-child-post-fields', function() {
        var $thiz = $(this);
        // show a spinner or something via css
        var dialog = $('<div style="display:none;height:450px;"><span class="spinner"></span>'+$thiz.data('mncf-message-loading')+'</div>').appendTo('body');
        /**
         * params for dialog
         */
        var dialog_data = {
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            },
            dialogClass: 'mncf-child-post-fields-dialog mncf-ui-dialog',
            modal: true,
            minWidth: 800,
            maxHeight: .9*$(window).height(),
            title: $thiz.data('mncf-title'),
            position: { my: "center top+50", at: "center top", of: window },
            buttons: [{
                text: $thiz.data('mncf-buttons-apply'),
                click: function() {
                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'mncf_edit_post_save_child_fields',
                            _mnnonce: $('#mncf-fields-save-nonce').val(),
                            parent: $('#mncf-parent').val(),
                            child: $('#mncf-child').val(),
                            current: $(':input', dialog).serialize()
                        }
                    })
                    /**
                     * close dialog
                     */
                    $( this ).dialog( "close" );
                },
                class: 'button-primary'
            }, {
                text: $thiz.data('mncf-buttons-cancel'),
                click: function() {
                    $( this ).dialog( "close" );
                },
                class: 'mncf-ui-dialog-cancel'
            }]
        };
        /**
         * remove button apply
         */
        if ( 'new' == $thiz.data('mncf-save-status') ) {
            dialog_data.buttons.shift();
            dialog_data.buttons[0].class = 'button-primary';
        }
        /**
         * open the dialog
         */
        dialog.dialog(dialog_data);
        // load remote content
        dialog.load(
            ajaxurl, 
            {
                action: 'mncf_edit_post_get_child_fields_screen',
                _mnnonce: $thiz.data('mncf-nonce'),
                parent: $thiz.data('mncf-parent'),
                child: $thiz.data('mncf-child'),
            },
            function (responseText, textStatus, XMLHttpRequest) {
                $(dialog).on('change', '.mncf-form-radio', function() {
                    if ('specific' == $(this).val()) {
                        $('#mncf-specific').slideDown();
                    } else {
                        $('#mncf-specific').slideUp();
                    }
                });
            }
        );
        //prevent the browser to follow the link
        return false;
    });
    /**
     * update groups with type
     */
    $('#field_groups').on('change', '.js-mncf-custom-fields-group', function(){ });

    /**
     * load column box
     */

    function mncf_edit_post_get_child_fields_box_message_helper() {
        var $container = $('#mncf-custom-field-message');
        if ( $('.js-mncf-custom-field-order-container li').length ) {
            $container.html($container.data('mncf-message-drag'));
        } else {
            $container.html('');
        }
    }

    var initGroupFields = 1;
    function mncf_edit_post_get_child_fields_box() {
        var currentGroups = [],
            currentFields = [],
            target = $('#custom_fields .mncf-box');

        if ( 0 == target.length )
            return;

        // current groups
        $('#field_groups .js-mncf-custom-fields-group:checked').each(function(){
            currentGroups.push( $( this ).data( 'mncf-group-id' ) );
        });

        // current fields (get them from sortables to have the right order)
        $( '.js-mncf-custom-field-order-container li[id^="mncf-custom-field"]' ).each( function() {
            currentFields.push( $( this ).attr( 'id' ).replace( 'mncf-custom-field-', '' ) );

        } );

        target.load(
            ajaxurl,
            {
                action: 'mncf_edit_post_get_fields_box',
                _mnnonce: target.data('mncf-nonce'),
                id: target.data('mncf-id'),
                type: target.data('mncf-type'),
                current_groups: currentGroups,
                current_fields: currentFields,
                init: initGroupFields
            },
            function (responseText, textStatus, XMLHttpRequest) {
                initGroupFields = 0;
                $('#custom_fields .inside .mncf-custom-field-group-container').masonry({
                    itemSelector: '.js-mncf-custom-field-group',
                    columnWidth: 250
                });
                $("#custom_fields .mncf-custom-field-order ul").sortable();
                $('.js-mncf-custom-field-group-container').on('change', 'input', function() {
                    var $key = $(this).data('mncf-key');
                    if ( $(this).is(':checked')) {
                        // only append field to sortable if it does not already exists
                        if( !$( '#custom_fields .mncf-custom-field-order ul' ).find( '#mncf-custom-field-'+$key ).length ) {
                            $('#custom_fields .mncf-custom-field-order ul').append(
                                '<li class="menu-item-handle ui-sortable-handle" id="mncf-custom-field-'+$key+'"><input type="hidden" name="ct[custom_fields]['+$key+']" value="1">'+ $('label', $(this).parent()).html()+ '</li>');
                        }

                        // check all other inputs with the same name
                        $( '[data-mncf-key=' + $(this).data( 'mncf-key' ) ).each( function() {
                            $( this ).attr( 'checked', 'checked' );
                        })
                    } else {
                        $('#mncf-custom-field-'+$key).remove();

                        // uncheck all other inputs with the same name
                        $( '[data-mncf-key=' + $(this).data( 'mncf-key' ) ).each( function() {
                            $( this ).removeAttr( 'checked' );
                        })
                    }
                    mncf_edit_post_get_child_fields_box_message_helper();
                });
                mncf_edit_post_get_child_fields_box_message_helper();
            }
        );
    }
    mncf_edit_post_get_child_fields_box();
    $('#field_groups').on( 'change', '.js-mncf-custom-fields-group', function(){
        mncf_edit_post_get_child_fields_box();
        return false;
    });

});

