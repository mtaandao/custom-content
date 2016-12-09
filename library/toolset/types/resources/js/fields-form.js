/**
 * fields edit
 */
jQuery(document).ready(function($){

    /**
     * Store all current used field slugs
     * @type {Array}
     */
    var allFieldSlugs = [];
    $.ajax({
            url: ajaxurl,
            method: "POST",
            dataType: 'json',
            data: {
                group_id: $( 'input[name="mncf[group][id]"]' ).val(),
                action: 'mncf_get_all_field_slugs_except_current_group',
                return: 'ajax-json'
            }
        })
        .done(function( slugs ){
            if( slugs.length ) {
                $.merge( allFieldSlugs, slugs );
            }
        });

    /**
     * function to update currently selected conditions
     * in the description of "Where to Include These Fields" box
     */
    function update_fields() {
        var msgAll = $( '.mncf-fields-group-conditions-description' ),
            msgCondNone = $( '.js-mncf-fields-group-conditions-none' ),
            msgCondSet = $( '.js-mncf-fields-group-conditions-set' ),
            msgCondAll = $( '.js-mncf-fields-group-conditions-condition' ),

            conditions = {
                'postTypes' : {
                    'description' : $( '.js-mncf-fields-group-conditions-post-types' ),
                    'inputsIDs' : 'mncf-form-groups-support-post-type-',
                    'activeConditionsLabels' : []
                },

                'terms' : {
                    'description' : $( '.js-mncf-fields-group-conditions-terms' ),
                    'inputsIDs' : 'mncf-form-groups-support-tax-',
                    'activeConditionsLabels' : []
                },

                'templates' : {
                    'description' : $( '.js-mncf-fields-group-conditions-templates' ),
                    'inputsIDs' : 'mncf-form-groups-support-templates-',
                    'activeConditionsLabels' : []
                },

                'data-dependencies' : {
                    'description' : $( '.js-mncf-fields-group-conditions-data-dependencies' ),
                    'activeConditionsLabels' : []
                },

                taxonomies : {
                    description: $( '.js-mncf-fields-group-conditions-taxonomies' ),
                    inputsIDs: 'mncf-form-groups-support-taxonomy-',
                    activeConditionsLabels: []
                }
            },
            conditionsCount = 0,
            uiDialog = $( '.mncf-filter-dialog' );

        // reset
        msgAll.hide();
        msgCondAll.find( 'span' ).html( '' );

        // update hidden inputs if dialog is open
        if( uiDialog.length ) {
            // reset all hidden inputs
            $( '[id^=mncf-form-groups-support-]' ).val( '' );
            $( '[id^=mncf-form-groups-support-tax]' ).remove();

            $( 'input[type=checkbox]:checked', uiDialog ).each( function() {
                // taxonomies are the only not using a prefix ('tax' is inside name)
                if( $( this ).data( 'mncf-prefix' ) == '' ) {
                    $( '<input/>' ).attr( {
                        type: 'hidden',
                        id: 'mncf-form-groups-support-' + $( this ).attr( 'name' ),
                        name: 'mncf[group][taxonomies][' + $( this ).attr( 'data-mncf-taxonomy-slug' ) + '][' + $( this ).attr( 'data-mncf-value' ) + ']',
                        'data-mncf-label': $( this ).attr( 'data-mncf-name' ),
                        value: $( this ).attr( 'data-mncf-value' ),
                    } ).appendTo( '.mncf-conditions-container' );
                // taxonomies on term fields
                } else if( $( this ).data( 'mncf-prefix' ) == 'taxonomy-'  ) {
                    $( '<input/>' ).attr( {
                        type: 'hidden',
                        id: 'mncf-form-groups-support-taxonomy-' + $( this ).attr( 'name' ),
                        name: 'mncf[group][taxonomies][' + $( this ).attr( 'data-mncf-value' ) + ']',
                        'data-mncf-label': $( this ).attr( 'data-mncf-name' ),
                        value: $( this ).attr( 'data-mncf-value' ),
                        class: 'js-mncf-filter-support-taxonomy mncf-form-hidden form-hidden hidden',
                    } ).appendTo( '.mncf-conditions-container' );
                } else {
                    var id = '#mncf-form-groups-support-' + $( this ).data( 'mncf-prefix' ) + $( this ).attr( 'name' );
                    var value = $( this ).data( 'mncf-value' );
                    $( id ).val( value );
                }
            } );
        }

        // get all active conditions
        $.each( conditions, function( id, condition ) {
            if( id == 'data-dependencies' ) {
                $( '.js-mncf-filter-container .js-mncf-condition-preview li' ).each( function() {
                    conditionsCount++;
                    conditions[id]['activeConditionsLabels'].push( '<br />' + $( this ).html() );
                } );
            } else {
                var selector = 'input[id^=' + condition.inputsIDs + ']';
                $( selector ).filter( function() {
                    return this.value && this.value != '0';
                } ).each( function() {
                    conditionsCount++;
                    var label = $( this ).data( 'mncf-label' );
                    conditions[id]['activeConditionsLabels'].push( label );
                })
            }
        });

        // show box description depending of conditions count
        if( conditionsCount > 0 ) {
            msgCondSet.show();
            $.each( conditions, function( id, condition ) {
                if( condition['activeConditionsLabels'].length ) {
                    condition['description'].show().find( 'span' ).html( condition['activeConditionsLabels'].join( ', ' ) );
                }
            } );
        } else {
            msgCondNone.show();
        }

        // show association option when there is more than one condition added
        if( conditionsCount > 1 ) {
            $( '#mncf-fields-form-filters-association-form' ).show();
        } else {
            $( '#mncf-fields-form-filters-association-form' ).hide();
        }

    }

    update_fields();

    /**
     * remove field link
     */
    $(document).on('click', '.js-mncf-field-remove', function() {
        if ( confirm($(this).data('message-confirm')) ) {
            $(this).closest('.postbox').slideUp(function(){
                $(this).remove();
                if ( 1 > $('#post-body-content .js-mncf-fields .postbox').length ) {
                    $( '.js-mncf-fields-add-new-last, .js-mncf-second-submit-container' ).addClass( 'hidden' );
                }
            });
        }
        return false;
    });
    /**
     * change field type
     */
    $(document).on('change', '.js-mncf-fields-type', function(){
        $('.js-mncf-fields-type-message').remove();
        $(this).parent().append('<div class="js-mncf-fields-type-message updated settings-error notice"><p>'+$(this).data('message-after-change')+'</p></div>');
        $('tbody tr', $(this).closest('table')).each(function(){
            if ( !$(this).hasClass('js-mncf-fields-typeproof') ) {
                $(this).hide();
            }
        });
    });
    /**
     * choose filter
     */
    $( document ).on( 'click', '.js-mncf-filter-container .js-mncf-filter-button-edit', function() {
        var thiz = $(this);
        // show a spinner or something via css
        var dialog = $('<div style="display:none;height:450px;" class="mncf-filter-contant"><span class="spinner"></span>'+thiz.data('mncf-message-loading')+'</div>').appendTo('body');
        // open the dialog
        dialog.dialog({
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            },
            dialogClass: 'mncf-filter-dialog mncf-ui-dialog',
            closeText: false,
            modal: true,
            minWidth: 810,
            maxHeight: .9*$(window).height(),
            title: thiz.data('mncf-dialog-title'),
            position: { my: "center top+50", at: "center top", of: window },
            buttons: [{
                text: thiz.data('mncf-buttons-apply'),
                click: function() {

                    var currentOpenDialog = $( this ).closest( '.mncf-filter-dialog' ).length ? $( this ).closest( '.mncf-filter-dialog' ) : $( this ).closest( '.mncf-conditions-dialog' ),
                        groupConditions,
                        fieldNonce,
                        fieldName,
                        fieldGroupId,
                        fieldMetaType,
                        extraMetaField = jQuery( '#data-dependant-meta', currentOpenDialog );

                    if( extraMetaField.length ) {
                        groupConditions = ( extraMetaField.data( 'mncf-action' ) == 'mncf_edit_field_condition_get' ) ? 0 : 1;
                        fieldName = extraMetaField.data( 'mncf-id' );
                        fieldGroupId = extraMetaField.data( 'mncf-group-id' );
                        fieldMetaType = extraMetaField.data( 'mncf-meta-type' );
                        fieldNonce = extraMetaField.data( 'mncf-buttons-apply-nonce' );
                    } else {
                        groupConditions = ( thiz.data( 'mncf-action' ) == 'mncf_edit_field_condition_get' ) ? 0 : 1;
                        fieldName = thiz.data( 'mncf-id' );
                        fieldGroupId = thiz.data( 'mncf-group-id' );
                        fieldMetaType = thiz.data( 'mncf-meta-type' );
                        fieldNonce = thiz.data('mncf-buttons-apply-nonce');
                    }
                    /**
                     * show selected values
                     */
                    //$('.js-mncf-filter-ajax-response', thiz.closest('.js-mncf-filter-container')).html(affected);

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'mncf_edit_field_condition_save',
                            _mnnonce: fieldNonce,
                            id: fieldName,
                            group_conditions: groupConditions,
                            group_id: fieldGroupId,
                            meta_type: fieldMetaType,
                            conditions: $( 'form', currentOpenDialog ).serialize()
                        }
                    })
                        .done(function(html){
                            var conditionsPreview, button;

                            if( groupConditions == 1 ) {
                                conditionsPreview = $( '.js-mncf-filter-container .js-mncf-condition-preview' );
                                button = $('.js-mncf-filter-container .js-mncf-condition-button-edit');
                            } else {
                                conditionsPreview = $('#types-custom-field-'+$thiz.data('mncf-id')+' .js-mncf-condition-preview');
                                button = $('#types-custom-field-'+$thiz.data('mncf-id')+' .js-mncf-condition-button-edit');
                            }

                            // updated field conditions
                            conditionsPreview.html( html );

                            // update button label
                            if( html == '' ) {
                                button.html( button.data( 'mncf-label-set-conditions' ) );
                            } else {
                                button.html( button.data( 'mncf-label-edit-condition' ) );
                            }

                            // close dialog
                            update_fields();
                            dialog.dialog( "close" );
                        });
                },
                class: 'button-primary'
            }, {
                text: thiz.data('mncf-buttons-cancel'),
                click: function() {
                    $( this ).dialog( "close" );
                },
                class: 'mncf-ui-dialog-cancel'
            }]
        });
        // load remote content
        var $current = [];
        var allFields = $( 'form.mncf-fields-form input[name^=mncf\\[group\\]][value!=""]' ).serialize();

        $(thiz.data('mncf-field-to-clear-class'), thiz.closest('.inside')).each(function(){
            if ( $(this).val() ) {
                $current.push($(this).val());
            }
        });

        var current_page = thiz.data('mncf-page');
        if( undefined == current_page ) {
            current_page = 'mncf-edit';
        }

        dialog.load(
            ajaxurl,
            {
                method: 'post',
                action: 'mncf_ajax_filter',
                _mnnonce: thiz.data('mncf-nonce'),
                id: thiz.data('mncf-id'),
                type: thiz.data('mncf-type'),
                page: current_page,
                current: $current,
                all_fields: allFields
            },
            function (responseText, textStatus, XMLHttpRequest) {
                // tabs
                var menu = $( '.mncf-tabs-menu' ).detach();

                menu.appendTo( ".mncf-filter-dialog .ui-widget-header" );

                $(".mncf-tabs-menu span").click(function(event) {
                    event.preventDefault();

                    $(this).parent().addClass("mncf-tabs-menu-current");
                    $(this).parent().siblings().removeClass("mncf-tabs-menu-current");
                    var tab = $(this).data("open-tab");
                    $(".mncf-tabs > div").not(tab).css("display", "none");
                    $(tab).fadeIn();
                });

                mncfAddPostboxToggles();
                $(dialog).on('click', 'a[data-mncf-icon]', function() {
                    var $icon = $(this).data('mncf-icon');
                    $('#mncf-types-icon').val($icon);
                    classes = 'mncf-types-menu-image dashicons-before dashicons-'+$icon;
                    $('div.mncf-types-menu-image').removeClass().addClass(classes);
                    dialog.dialog( "close" );
                    return false;
                });
                /**
                 * bind search taxonomies
                 */
                $(dialog).on('keyup input cut paste', '.js-mncf-taxonomy-search', function() {
                    var $parent = $(this).closest('.inside');
                    if ( '' == $(this).val() ) {
                        $('li', $parent).show();
                    } else {
                        var re = new RegExp($(this).val(), "i");
                        $('li input', $parent).each(function(){
                            if (
                                    false
                                    || $(this).data('mncf-slug').match(re)
                                    || $(this).data('mncf-name').match(re)
                               ) {
                                $(this).parent().show();
                            } else {
                                $(this).parent().hide();
                            }
                        });
                    }
                });

                /**
                 * Data Dependant
                 */
                $(dialog).on('click', '.js-mncf-condition-button-add-row', function() {
                    var button = $( this );
                    button.attr( 'disabled', 'disabled' );

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'mncf_edit_field_condition_get_row',
                            _mnnonce: $( this ).data('mncf-nonce'),
                            id: $(this).data('mncf-id'),
                            group_id: $( this ).data( 'mncf-group-id' ),
                            meta_type: $( this ).data('mncf-meta-type')
                        }
                    })
                        .done(function(html){
                            button.removeAttr( 'disabled' );
                            $('.js-mncf-fields-conditions', $(dialog)).append(html);

                            var receiveError = $('.js-mncf-fields-conditions', $(dialog) ).find( '.js-mncf-received-error' );

                            if( receiveError.length ) {
                                button.remove();
                            } else {
                                $( dialog ).on( 'click', '.js-mncf-custom-field-remove', function() {
                                    return mncf_conditional_remove_row( $( this ) );
                                } );
                                mncf_setup_conditions();
                                $( dialog ).on( 'change', '.js-mncf-cd-field', function() {
                                    mncf_setup_conditions();
                                } );
                            }
                        });
                    return false;
                });
                $(dialog).on('click', '.js-mncf-custom-field-remove', function() {
                    return mncf_conditional_remove_row($(this));
                });
                /**
                 * bind to switch logic mode
                 */
                $(dialog).on('click', '.js-mncf-condition-button-display-logic', function() {
                    var $container = $(this).closest('form');
                    if ( 'advance-logic' == $(this).data('mncf-custom-logic') ) {
                        $('.js-mncf-simple-logic', $container).show();
                        $('.js-mncf-advance-logic', $container).hide();
                        $(this).data('mncf-custom-logic', 'simple-logic');
                        $(this).html($(this).data('mncf-content-advanced'));
                        $('.js-mncf-condition-custom-use', $container).val(0);
                    } else {
                        $('.js-mncf-simple-logic', $container).hide();
                        $('.js-mncf-advance-logic', $container).show();
                        $(this).data('mncf-custom-logic', 'advance-logic');
                        $(this).html($(this).data('mncf-content-simple'));
                        mncf_conditional_create_summary(this, $container);
                        $('.js-mncf-condition-custom-use', $container).val(1);
                    }
                    return false;
                });
            }
        );
        //prevent the browser to follow the link
        return false;
    });
    /**
     * add new - choose field
     */
    $( document ).on( 'click', '.js-mncf-fields-add-new', function() {
        var $thiz = $(this);
        var $current;
        // show a spinner or something via css
        var dialog = $('<div style="display:none;height:450px;" class="mncf-choose-field"><span class="spinner"></span>'+$thiz.data('mncf-message-loading')+'</div>').appendTo('body');
        // open the dialog
        dialog.dialog({
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            },
            closeText: false,
            modal: true,
            minWidth: 810,
            maxHeight: .9*$(window).height(),
            title: $thiz.data('mncf-dialog-title'),
            position: { my: "center top+50", at: "center top", of: window }
        });
        // load remote content
        var $current = [];
        $($thiz.data('mncf-field-to-clear-class'), $thiz.closest('.inside')).each(function(){
            if ( $(this).val() ) {
                $current.push($(this).val());
            }
        });
        $('#post-body-content .postbox .js-mncf-slugize').each(function(){
            if ( $(this).val() ) {
                $current.push($(this).val());
            }
        });

        // top or bottom "add new field" clicked
        var position = $thiz.hasClass( 'js-mncf-fields-add-new-last' )
            ? 'bottom'
            : 'top';

        function add_field_to_fields_list( html ) {

            var newField;

            if( position == 'top' ) {
                $( '#post-body-content .js-mncf-fields' ).prepend( html );
                newField = $( '#post-body-content .js-mncf-fields .postbox' ).first();
            } else {
                $( '#post-body-content .js-mncf-fields .js-mncf-fields-add-new-last' ).before( html );
                newField = $( '#post-body-content .js-mncf-fields .postbox' ).last();
            }

            $( 'html, body' ).animate( {
                scrollTop: newField.offset().top - 50
            }, 1000 );

            dialog.dialog( 'close' );

            mncfBindAutoCreateSlugs();
            mncfAddPostboxToggles();

            newField.typesFieldOptionsSortable();
            newField.typesMarkExistingField();

            // show bottom "Add new field" and "Save Group Fields" buttons
            $( '.js-mncf-fields-add-new, .js-mncf-second-submit-container' ).removeClass( 'hidden' );
            mncf_setup_conditions();
        }

        // This can be mncf-postmeta, mncf-usermeta or mncf-termmeta.
        var fieldKind = $thiz.data('mncf-type');

        dialog.load(
            ajaxurl,
            {
                action: 'mncf_edit_field_choose',
                _mnnonce: $thiz.data('mncf-nonce'),
                id: $thiz.data('mncf-id'),
                type: fieldKind,
                current: $current
            },
            function (responseText, textStatus, XMLHttpRequest) {
                var $fields = '';
                var $dialog =  $(this).closest('.ui-dialog-content')
                /**
                 * choose new field
                 */
                $(dialog).on('click', 'button.js-mncf-field-button-insert', function() {
                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'mncf_edit_field_insert',
                            _mnnonce: $('#mncf-fields-add-nonce').val(),
                            type: $(this).data('mncf-field-type'),
                            field_kind: fieldKind
                        }
                    })
                    .done(function(html){
                        add_field_to_fields_list( html );
                    });
                });
                /**
                 * choose from existed fields
                 */
                $(dialog).on('click', '.js-mncf-switch-to-exists', function() {

                    var current_page = $thiz.data('mncf-page');
                    if( undefined == current_page ) {
                        current_page = 'mncf-edit';
                    }

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'mncf_edit_field_select',
                            _mnnonce: $('#mncf-fields-add-nonce').val(),
                            id: $thiz.data('mncf-id'),
                            type: $thiz.data('mncf-type'),
                            current: $current,
                            page: current_page,
                            a:'c'
                        }
                    })
                    .done(function(html){
                        $fields = $dialog.html();
                        $dialog.html(html);
                        $(dialog).on('click', '.js-mncf-switch-to-new', function() {
                            $dialog.html($fields);
                            return false;
                        });
                        /**
                         * filter
                         */
                        $(dialog).on('keyup input cut paste', '.js-mncf-fields-search', function() {
                            if ( '' == $(this).val() ) {
                                $('.js-mncf-field-button-use-existed', dialog).show();
                            } else {
                                var re = new RegExp($(this).val(), "i");
                                $('.js-mncf-field-button-use-existed', dialog).each(function(){
                                    if (
                                        false
                                        || $(this).data('mncf-field-id').match(re)
                                        || $(this).data('mncf-field-type').match(re)
                                        || $('span', $(this)).html().match(re)
                                    ) {
                                        $(this).show();
                                    } else {
                                        $(this).hide();
                                    }
                                });
                            }
                        });
                        /**
                         * choose exist field
                         */
                        $(dialog).on('click', 'button.js-mncf-field-button-use-existed', function() {
                            $.ajax({
                                url: ajaxurl,
                                method: "POST",
                                data: {
                                    action: 'mncf_edit_field_add_existed',
                                    id: $(this).data('mncf-field-id'),
                                    type: $(this).data('mncf-type'),
                                    _mnnonce: $('#mncf-fields-add-nonce').val()
                                }
                            })
                            .done(function(html){
                                add_field_to_fields_list( html );
                            });
                        });
                    });

                });
            }
        );
        //prevent the browser to follow the link
        return false;
    });
    /**
     * update box fifle by field name
     */
    $('.mncf-forms-set-legend').live('keyup', function(){
        var val = $(this).val();
        if ( val ) {
            val = val.replace(/</, '&lt;');
            val = val.replace(/>/, '&gt;');
            val = val.replace(/'/, '&#39;');
            val = val.replace(/"/, '&quot;');
        }
        $(this).parents('.postbox').find('.mncf-legend-update').html(val);
    });

    // Check radio and select if same values
    // Check checkbox has a value to store
    $('.mncf-fields-form').submit(function(){
        mncfLoadingButton();
        var passed = true;
        var checkedArr = new Array();
        $('.mncf-compare-unique-value-wrapper').each(function(index){
            var childID = $(this).attr('id');
            checkedArr[childID] = new Array();
            $(this).find('.mncf-compare-unique-value').each(function(index, value){
                var parentID = $(this).parents('.mncf-compare-unique-value-wrapper').first().attr('id');
                var currentValue = $(this).val();
                if (currentValue != ''
                    && $.inArray(currentValue, checkedArr[parentID]) > -1) {

                    var fieldContainer = $(this).parents( '.postbox' );

                    // open fields container if closed
                    if( fieldContainer.hasClass( 'closed' ) ) {
                        fieldContainer.find( '.hndle' ).trigger( 'click.postboxes' );
                    }

                    passed = false;

                    $('#'+parentID).children('.mncf-form-error-unique-value').remove();

                    // make sure error msg is only applied ounce
                    if( ! $('#'+parentID).find( '.mncf-form-error' ).length ) {
                        if( document.getElementById( parentID ).tagName == 'TBODY' ) {
                            $('#'+parentID).append('<tr><td colspan="5"><div class="mncf-form-error-unique-value mncf-form-error">'+mncfFormUniqueValuesCheckText+'</div><td></tr>');
                        } else {
                            $('#'+parentID).append('<div class="mncf-form-error-unique-value mncf-form-error">'+mncfFormUniqueValuesCheckText+'</div>');
                        }
                    }

                    $(this).parents('fieldset').children('.fieldset-wrapper').slideDown();
                    $(this).focus();
                }

                checkedArr[parentID].push(currentValue);
            });
        });
        if (passed == false) {
            // Bind message fade out
            $('.mncf-compare-unique-value').live('keyup', function(){
                $(this).parents('.mncf-compare-unique-value-wrapper').find('.mncf-form-error-unique-value').fadeOut(function(){
                    $(this).remove();
                });
            });
            mncf_fields_form_submit_failed();
            return false;
        }
        // Check field names unique
        passed = true;
        checkedArr = new Array();
        $('.mncf-forms-field-name').each(function(index){
            var currentValue = $(this).val().toLowerCase();
            if (currentValue != ''
                && $.inArray(currentValue, checkedArr) > -1) {
                passed = false;

                // apply error msg to all fields with the same name
                $( '.mncf-forms-field-name' ).each( function() {
                    if( $( this ).val().toLowerCase() == currentValue ) {
                        if (!$(this).hasClass('mncf-name-checked-error')) {
                            $(this).before('<div class="mncf-form-error-unique-value mncf-form-error">'+mncfFormUniqueNamesCheckText+'</div>').addClass('mncf-name-checked-error');
                        }
                    };

                    // scroll to last expanded postbox with this issue
                    if( $( this ).closest( '.postbox' ).find('.handlediv' ).attr('aria-expanded') == 'true' ) {
                        $( this ).parents( 'fieldset' ).children('.fieldset-wrapper').slideDown();
                        $( this ).first().focus();
                    }
                } );

            }
            checkedArr.push(currentValue);
        });
        if (passed == false) {
            // Bind message fade out
            $('.mncf-forms-field-name').live('keyup', function(){
                $(this).removeClass('mncf-name-checked-error').prev('.mncf-form-error-unique-value').fadeOut(function(){
                    $(this).remove();
                });
            });
            mncf_fields_form_submit_failed();
            return false;
        }

        // Check field slugs unique
        passed = true;
        checkedArr = [];
        $.merge( checkedArr, allFieldSlugs );
        /**
         * first fill array with defined, but unused fields
         */
        $('#mncf-form-groups-user-fields .mncf-fields-add-ajax-link:visible').each(function(){
            checkedArr.push($(this).data('slug'));
        });
        $('.mncf-forms-field-slug').each(function(index){

            // skip for "existing fields" if no change in input slug
            if( $( this ).data( 'types-existing-field' ) && $( this ).data( 'types-existing-field' ) == $( this ).val() )
                return true;

            var currentValue = $(this).val().toLowerCase();
            if (currentValue != ''
                && $.inArray(currentValue, checkedArr) > -1) {
                passed = false;

                // apply error msg to all fields with the same slug
                $( '.mncf-forms-field-slug' ).each( function() {
                   if( $( this ).val() == currentValue ) {
                       if (!$(this).hasClass('mncf-slug-checked-error')) {
                           $(this).before('<div class="mncf-form-error-unique-value mncf-form-error">'+mncfFormUniqueSlugsCheckText+'</div>').addClass('mncf-slug-checked-error');
                       }
                   };

                   // scroll to last expanded postbox with this issue
                   if( $( this ).closest( '.postbox' ).find('.handlediv' ).attr('aria-expanded') == 'true' ) {
                       $( this ).parents( 'fieldset' ).children('.fieldset-wrapper').slideDown();
                       $( this ).first().focus();
                   }
                } );
            }
            checkedArr.push(currentValue);
        });

        // Conditional check
        if (mncfConditionalFormDateCheck() == false) {
            mncf_fields_form_submit_failed();
            return false;
        }

        // check to make sure checkboxes have a value to save.
        $('[data-mncf-type=checkbox],[data-mncf-type=checkboxes]').each(function () {
            if (mncf_checkbox_value_zero(this)) {
                passed = false;
            }
        });

        if (passed == false) {
            // Bind message fade out
            $('.mncf-forms-field-slug').live('keyup', function(){
                $(this).removeClass('mncf-slug-checked-error').prev('.mncf-form-error-unique-value').fadeOut(function(){
                    $(this).remove();
                });
            });
            mncf_fields_form_submit_failed();
            return false;
        }

        /**
         * modal advertising dialog is shown on this event
         */
        $( document ).trigger( 'js-mncf-event-types-show-modal' );
    } );
});

/**
 * on form submit fail
 */
function mncf_fields_form_submit_failed() {
    mncfLoadingButtonStop();
    mncf_highlight_first_error();
}

/**
 * scroll to first issue
 */
function mncf_highlight_first_error() {
    var $ = jQuery,
        firstError = $( '.mncf-form-error' ).first(),
        postBox = firstError.closest( '.postbox' );


    if( postBox.hasClass( 'closed' ) ) {
        postBox.removeClass( 'closed' );
        postBox.find( '.handlediv' ).attr( 'aria-expanded', 'true' );
    }

    firstError.next( 'input' ).focus();
}

/**
 * remove row
 */
function mncf_conditional_remove_row(element)
{
    element.closest('tr').remove();
    mncf_setup_conditions();
    return false;
}
/**
 * Create advance logic
 */
function mncf_conditional_create_summary(button, parent)
{
    if ( jQuery('.js-mncf-advance-logic textarea', parent).val() ) {
        return;
    }
    var condition = '';
    var skip = true;
    parent = jQuery(button).closest('form');
    jQuery('.mncf-cd-entry', parent).each(function(){
        if (!skip) {
            condition += jQuery('.js-mncf-simple-logic', parent).find('input[type=radio]:checked').val() + ' ';
        }
        skip = false;

        var field = jQuery(this).find('.js-mncf-cd-field :selected');

        condition += '($(' + jQuery(this).find('.js-mncf-cd-field').val() + ')';

        // We need to translate from currently supported "simple" to "advanced" syntax. Ironically, the advanced one
        // currently supports only a subset of comparison operators.
        //
        // While we're at it, we translate all operators to their "text-only" equivalents because that's what they're
        // going to be sanitized into anyway.
        var comparisonOperator = jQuery(this).find('.js-mncf-cd-operation').val();
        switch(comparisonOperator) {
            case '=':
            case '===':
                comparisonOperator = 'eq';
                break;
            case '>':
                comparisonOperator = 'gt';
                break;
            case '>=':
                comparisonOperator = 'gte';
                break;
            case '<':
                comparisonOperator = 'lt';
                break;
            case '<=':
                comparisonOperator = 'lte';
                break;
            case '<>':
            case '!==':
                comparisonOperator = 'ne';
                break;
        }

        condition += ' ' + comparisonOperator;
        // Date
        if (field.hasClass('mncf-conditional-select-date')) {
            var date = jQuery(this).find('.mncf-custom-field-date');
            var month = date.children(':first');
            var mm = month.val();
            var jj = month.next().val();
            var aa = month.next().next().val();
            condition += ' DATE(' + jj + ',' + mm + ',' + aa + ')) ';
        } else {
            condition += ' ' + jQuery(this).find('.js-mncf-cd-value').val() + ') ';
        }
    });
    jQuery('.js-mncf-advance-logic textarea', parent).val(condition);
}

/**
 * check condition methods
 */
function mncf_setup_conditions()
{
    /**
     * move button "Add another condition" to mid if there is no condition
     */
    var dialog = jQuery( '.mncf-filter-dialog' ).length ? jQuery( '.mncf-filter-dialog' ) : jQuery( '.mncf-conditions-dialog' ),
        btnAddCondition = jQuery('.js-mncf-condition-button-add-row', dialog );

    if( 0 == jQuery('.js-mncf-fields-conditions tr', dialog ).length ) {
        btnAddCondition.html( btnAddCondition.data( 'mncf-label-add-condition' ) );
        btnAddCondition.addClass( 'mncf-block-center' ).removeClass( 'alignright' );
    } else {
        btnAddCondition.html( btnAddCondition.data( 'mncf-label-add-another-condition' ) );
        btnAddCondition.addClass( 'alignright' ).removeClass( 'mncf-block-center' );
    }

    /**
     * checked condition method
     */
    if ( 1 < jQuery('.js-mncf-fields-conditions tr', dialog ).length ) {
        jQuery('.mncf-cd-relation.simple-logic').show();
    } else {
        jQuery('.mncf-cd-relation.simple-logic').hide();
    }
    /**
     * bind select
     */
    jQuery('.js-mncf-cd-field').on('change', function() {
        if ( jQuery(this).val() ) {
            jQuery('.js-mncf-cd-operation, .js-mncf-cd-value', jQuery(this).closest('tr')).removeAttr('disabled');
        } else {
            jQuery('.js-mncf-cd-operation, .js-mncf-cd-value', jQuery(this).closest('tr')).attr('disabled', 'disabled');
        }
    });
}

function mncfAddPostboxToggles()
{
    jQuery('.postbox .hndle, .postbox .handlediv').unbind('click.postboxes');
    postboxes.add_postbox_toggles();
}

/**
 * fixes for dialogs
 */
( function( $ ) {
    // on dialogopen
    $( document ).on( 'dialogopen', '.ui-dialog', function( e, ui ) {
        // normalize primary buttons
        $( 'button.button-primary, button.mncf-ui-dialog-cancel' )
            .blur()
            .addClass( 'button' )
            .removeClass( 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only' );
    } );

    // resize
    var resizeTimeout;
    $( window ).on( 'resize scroll', function() {
        clearTimeout( resizeTimeout );
        resizeTimeout = setTimeout( dialogResize, 200 );
    } );

    function dialogResize() {
        $( '.ui-dialog' ).each( function() {
            $( this ).css( {
                'maxWidth': '100%',
                'top': $( window ).scrollTop() + 50 + 'px',
                'left': ( $( 'body' ).innerWidth() - $( this ).outerWidth() ) / 2 + 'px'
            } );
        } );
    }


    /**
     * choose condition
     */
    $( document ).on( 'click', '.js-mncf-condition-button-edit', function() {
        var $thiz = $(this);
        // show a spinner or something via css
        var dialog = $('<div style="display:none;height:450px;"><span class="spinner"></span>'+$thiz.data('mncf-message-loading')+'</div>').appendTo('body');
        // open the dialog
        dialog.dialog({
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            },
            dialogClass: 'mncf-conditions-dialog mncf-ui-dialog',
            closeText: false,
            modal: true,
            minWidth: 810,
            maxHeight: .9*$(window).height(),
            title: $thiz.data('mncf-dialog-title'),
            position: { my: "center top+50", at: "center top", of: window },
            buttons: [{
                text: $thiz.data('mncf-buttons-apply'),
                click: function() {
                    var groupConditions = ( $thiz.data( 'mncf-action' ) == 'mncf_edit_field_condition_get' ) ? 0 : 1;

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'mncf_edit_field_condition_save',
                            _mnnonce: $thiz.data('mncf-buttons-apply-nonce'),
                            id: $thiz.data('mncf-id'),
                            group_conditions: groupConditions,
                            group_id: $thiz.data( 'mncf-group-id' ),
                            meta_type: $thiz.data('mncf-meta-type'),
                            conditions: $('form', $(this).closest('.mncf-conditions-dialog')).serialize()
                        }
                    })
                        .done(function(html){

                            var conditionsPreview, button;

                            if( groupConditions == 1 ) {
                                conditionsPreview = $( '.js-mncf-filter-container .js-mncf-condition-preview' );
                                button = $('.js-mncf-filter-container .js-mncf-condition-button-edit');
                            } else {
                                conditionsPreview = $('#types-custom-field-'+$thiz.data('mncf-id')+' .js-mncf-condition-preview');
                                button = $('#types-custom-field-'+$thiz.data('mncf-id')+' .js-mncf-condition-button-edit');
                            }

                            // updated field conditions
                            conditionsPreview.html( html );

                            // update button label
                            if( html == '' ) {
                                button.html( button.data( 'mncf-label-set-conditions' ) );
                            } else {
                                button.html( button.data( 'mncf-label-edit-condition' ) );
                            }

                            // close dialog
                            dialog.dialog( "close" );
                        });
                    return false;
                },
                class: 'button-primary'
            }, {
                text: $thiz.data('mncf-buttons-cancel'),
                click: function() {
                    /**
                     * close dialog
                     */
                    $( this ).dialog( "close" );
                },
                class: 'mncf-ui-dialog-cancel'
            }]
        });
        /**
         * load dialog content
         */
        dialog.load(
            ajaxurl,
            {
                action: $thiz.data('mncf-action'),
                _mnnonce: $thiz.data('mncf-nonce'),
                id: $thiz.data('mncf-id'),
                group: $thiz.data('mncf-group'),
                group_id: $thiz.data('mncf-group-id'),
            },
            function (responseText, textStatus, XMLHttpRequest) {
                $(dialog).on('click', '.js-mncf-condition-button-add-row', function() {
                    var button = $( this );
                    button.attr( 'disabled', 'disabled' );

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'mncf_edit_field_condition_get_row',
                            _mnnonce: $(this).data('mncf-nonce'),
                            id: $(this).data('mncf-id'),
                            group_id: $( this ).data( 'mncf-group-id' ),
                            meta_type: $(this).data('mncf-meta-type')
                        }
                    })
                        .done(function(html){
                            button.removeAttr( 'disabled' );
                            $('.js-mncf-fields-conditions', $(dialog)).append(html);

                            var receiveError = $('.js-mncf-fields-conditions', $(dialog) ).find( '.js-mncf-received-error' );

                            if( receiveError.length ) {
                                button.remove();
                            } else {
                                $(dialog).on('click', '.js-mncf-custom-field-remove', function() {
                                    return mncf_conditional_remove_row($(this));
                                });
                                mncf_setup_conditions();
                                $(dialog).on('change', '.js-mncf-cd-field', function() {
                                    mncf_setup_conditions();
                                });
                            }

                        });
                    return false;
                });
                $(dialog).on('click', '.js-mncf-custom-field-remove', function() {
                    return mncf_conditional_remove_row($(this));
                });
                /**
                 * bind to switch logic mode
                 */
                $(dialog).on('click', '.js-mncf-condition-button-display-logic', function() {
                    var $container = $(this).closest('form');
                    if ( 'advance-logic' == $(this).data('mncf-custom-logic') ) {
                        $('.js-mncf-simple-logic', $container).show();
                        $('.js-mncf-advance-logic', $container).hide();
                        $(this).data('mncf-custom-logic', 'simple-logic');
                        $(this).html($(this).data('mncf-content-advanced'));
                        $('.js-mncf-condition-custom-use', $container).val(0);
                    } else {
                        $('.js-mncf-simple-logic', $container).hide();
                        $('.js-mncf-advance-logic', $container).show();
                        $(this).data('mncf-custom-logic', 'advance-logic');
                        $(this).html($(this).data('mncf-content-simple'));
                        mncf_conditional_create_summary(this, $container);
                        $('.js-mncf-condition-custom-use', $container).val(1);
                    }
                    return false;
                });
            }
        );
    });
} )( jQuery );
