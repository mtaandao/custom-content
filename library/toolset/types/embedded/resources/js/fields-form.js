/*
 * Group edit page JS
 *
 * This file should be used from now on as dedicated JS for group edit page.
 * Avoid adding new functionalities to basic.js
 *
 * Thanks!
 *
 * @since Types 1.1.5
 * @autor srdjan
 *
 *
 */

jQuery(document).ready(function($){

    /**
     * bind validation checkboxes
     */
    $('#post-body-content').on('change', '.js-mncf-validation-checkbox', function() {
        next = $(this).closest('tr');

        // as there different number of hidden inputs between the checkbox
        // and the validation error message input we need to trick
        for( i = 0; i < 10; i++ ) {
            next = next.next();

            // break loop if next tr is found
            if( next.prop( 'tagName' ).toLowerCase() == 'tr' )
                break;

        }

        if( next.attr( 'type' ) == 'hidden' )
            next = next.next();

        if ( $(this).is(':checked') ) {
            $('input', next).removeAttr('disabled').focus();
        } else {
            $('input', next).attr('disabled', 'disabled');
        }
    });

    // Invoke drag on mouse enter
    $('#mncf-fields-sortable').on('mouseenter', '.js-types-sortable', function(){
        if (!$(this).parent().hasClass('ui-sortable')) {
            $(this).parent().sortable({
                revert: true,
                handle: '.js-types-sort-button',
                start: function(e, ui){
                        ui.placeholder.height(ui.item.find('.mncf-form-fieldset').height());
                    }
            });
        }
    });
    // Sort and Drag
    $('#mncf-fields-sortable').sortable({
        cursor: 'ns-resize',
        axis: 'y',
        handle: 'img.mncf-fields-form-move-field',
        forcePlaceholderSize: true,
        tolerance: 'pointer',
        start: function(e, ui){
                ui.placeholder.height(ui.item.height() + 23);
            }
    });

    $.fn.typesFieldOptionsSortable = function() {

        $( '.mncf-fields-radio-sortable, .mncf-fields-select-sortable, .mncf-fields-checkboxes-sortable', this ).sortable({
            cursor: 'ns-resize',
            axis: 'y',
            handle: '.js-types-sort-button',
            start: function(e, ui){
                ui.placeholder.height(ui.item.height() - 2);
            }
        });

        $( '.mncf-fields-checkboxes-sortable', this ).sortable({
            start: function(e, ui){
                ui.placeholder.height(ui.item.height() + 13);
            }
        });
    }

    $.fn.typesMarkExistingField = function() {

        var slug = $( '.mncf-forms-field-slug', this );

        if( slug.length && slug.val() != '' )
            slug.attr( 'data-types-existing-field', slug.val() );
    }

    $( 'body' ).typesFieldOptionsSortable();


    $('[data-mncf-type="checkbox"],[data-mncf-type=checkboxes]').each( function() {
        $(this).bind('change', function() {
            mncf_checkbox_value_zero($(this))
        });
        mncf_checkbox_value_zero($(this));
    });

    /**
     * confitonal logic button close on group edit screen
     */
    $('#conditional-logic-button-ok').live('click', function(){
        $(this).parent().slideUp('slow', function() {
            $('#conditional-logic-button-open').fadeIn();
        });
        return false;
    });

    /**
     * delete option
     */
    $(document).on('click', '.js-mncf-button-delete', function() {
        var $thiz = $(this);
        if (confirm($(this).data('message-delete-confirm'))) {
            $thiz.closest('tr').fadeOut(function(){
                $(this).remove();
                $('.'+$thiz.data('id')).fadeOut(function(){
                    $(this).remove();
                });
            });
        }
        return false;
    });
});

function mncf_checkbox_value_zero(field) {
    var passed = true;

    if (jQuery(field).hasClass('mncf-value-store-error-error')) {
        jQuery(field).prev().remove();
        jQuery(field).removeClass('mncf-value-store-error-error');
    }

    var value = jQuery(field).val();
    if (value === '') {
        passed = false;
        if (!jQuery(field).hasClass('mncf-value-store-error-error')) {
            jQuery(field).before('<div class="mncf-form-error">' + jQuery(field).data('required-message') + '</div>').addClass('mncf-value-store-error-error');
            var legend = jQuery(field).closest('div.ui-draggable').children('fieldset').children('legend');
            if ( legend.hasClass('legend-collapsed') ) {
                legend.click();
            }
            var fieldset = jQuery(field).closest('fieldset');
            if ( jQuery('legend.legend-collapsed', fieldset ) ) {
                jQuery('legend.legend-collapsed', fieldset).click();
            }
        }
        jQuery(field).focus();
    }
    if (value === '0') {
        passed = false;
        if (!jQuery(field).hasClass('mncf-value-store-error-error')) {
            jQuery(field).before('<div class="mncf-form-error">' + jQuery(field).data('required-message-0') + '</div>').addClass('mncf-value-store-error-error');
        }
        jQuery(field).focus();
    }
    return !passed;
}
