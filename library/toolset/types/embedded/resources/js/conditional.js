/**
 * Loop through each check trigger field
 * (marked with .mncf-conditional-trigger
 */
function mncfConditionalInit(selector) {
    /**
     * and bind to logic switcher
     */
    mncfConditionalLogiButtonsBindClick();
    /**
     * check state
     */
    jQuery('.conditional-display-custom-use').each(function(){
        mncfConditionalLogic(jQuery(this));
    });
}

function mncfConditionalLogiButtonsBindClick()
{
    jQuery('.mncf-cd-display-logic-button').each(function(){
        if ( jQuery(this).val() ) {
            return; // this is jQuery "continue"
        }
        jQuery(this).bind('click',function(){
            mncfConditionalLogicButton(jQuery(this), true);
        });
        mncfConditionalLogicButton(jQuery(this), false);
    });
}

function mncfConditionalLogicButton(button, changeState)
{
    parent = jQuery(button).closest('.mncf-cd-fieldset');
    el = jQuery('.conditional-display-custom-use', parent);
    if ( changeState ) {
        el.val(parseInt(el.val())?0:1);
    }
    mncfConditionalLogic(el);
}

/**
 * Disables 'Add Condition' field.
 */
function mncfDisableAddCondition(id) {
    jQuery('#mncf_conditional_add_condition_field_'+id)
    .attr('disabled', 'disabled').unbind('click')
    .removeClass('mncf-ajax-link').attr('onclick', '');
}

/**
 * Trigger JS
 * TODO Check if obsolete
 * /
jQuery(document).ready(function(){
    jQuery('.mncf-cd-fieldset, #mncf-cd-group').each(function(){
        if (jQuery(this).find('.mncf-cd-entry').length > 1) {
            jQuery(this).find('.toggle-cd').show();
            jQuery(this).find('.mncf-cd-relation').show();
        }
    });
});

/**
 * Create conditional statement
 */
function mncfCdCreateSummary(id)
{
    var condition = '';
    var skip = true;
    parent = jQuery('#'+id).closest('.mncf-cd-fieldset');
    jQuery('.mncf-cd-entry', parent).each(function(){
        if (!skip) {
            condition += jQuery(this).parent().parent().find('input[type=radio]:checked').val() + ' ';
        }
        skip = false;
        //                }
        var field = jQuery(this).find('.mncf-cd-field :selected');

        condition += '($(' + jQuery(this).find('.mncf-cd-field').val() + ')';
        condition += ' ' + jQuery(this).find('.mncf-cd-operation').val();
        // Date
        if (field.hasClass('mncf-conditional-select-date')) {
            var date = jQuery(this).find('.mncf-custom-field-date');
            var month = date.children(':first');
            var mm = month.val();
            var jj = month.next().val();
            var aa = month.next().next().val();
            condition += ' DATE(' + jj + ',' + mm + ',' + aa + ')) ';
        } else {
            condition += ' ' + jQuery(this).find('.mncf-cd-value').val() + ') ';
        }
    });
    jQuery('#'+id).val(condition);
}

function mncfConditionalLogic(el)
{
    var parentFieldSet = el.closest('.mncf-cd-fieldset'),
        buttonDisplay = jQuery('input.mncf-cd-display-logic-button', parentFieldSet);

    buttonDisplay.val(buttonDisplay.data('mncf-custom-logic-simple'));

    if ( parseInt(el.val()) ) {
        jQuery('.simple-logic', parentFieldSet).hide();
        jQuery('.area-toggle-cd', parentFieldSet).show();
        if ( parseInt( buttonDisplay.data('mncf-custom-logic-change') ) ) {
            mncfCdCreateSummary(buttonDisplay.data('mncf-custom-summary'));
        }
    } else {
        buttonDisplay.val(buttonDisplay.data('mncf-custom-logic-customize'));
        /**
         * turn on future change
         */
        buttonDisplay.data('mncf-custom-logic-change', 1);
        jQuery('.area-toggle-cd',parentFieldSet).hide();
        jQuery('.simple-logic',parentFieldSet).show();
        if (jQuery('.mncf-cd-entry', parentFieldSet).length) {
            if (jQuery('.mncf-cd-entries', parentFieldSet).length > 1) {
                jQuery('.mncf-cd-relation', parentFieldSet).show();
            } else {
                jQuery('.mncf-cd-relation', parentFieldSet).hide();
            }
        } else {
            jQuery('.area-toggle-cd', parentFieldSet).hide();
            jQuery('.mncf-cd-relation', parentFieldSet).hide();
        }
    }
    /**
     * handle "Data-dependent display filters" for groups
     */
    if ( 'mncf-cd-group' == parentFieldSet.attr('id') ) {
        jQuery('span.count', parentFieldSet.closest('td')).html( '('+ jQuery('span.count', parentFieldSet.closest('td')).data('mncf-custom-logic') +')');
    if ( parseInt(el.val()) ) {
    } else {
        jQuery('span.count', parentFieldSet.closest('td')).html('('+jQuery('.mncf-cd-entry', parentFieldSet).length+')');
    }
    }
}

/**
 * Add New Condition AJAX call
 */
function mncfCdAddCondition(object, isGroup) {
    var wrapper = isGroup ? object.parents('#mncf-cd-group') : object.parents('.mncf-cd-fieldset');
    if (wrapper.find('.mncf-cd-entry').length > 0) {
        wrapper.find('input.mncf-cd-display-logic-button').show();
        if (wrapper.find('.mncf-cd-entry').length > 1) {
            wrapper.find('.mncf-cd-relation').show();
        } else {
            wrapper.find('.mncf-cd-relation').hide();
        }
    }
    var url = object.attr('href')+'&count='+wrapper.find('input[type=hidden].mncf-cd-count').val();
    if (isGroup) {
        url += '&group=1';
    } else {
        url += '&field='+wrapper.attr('id');
    }
    jQuery.get(url, function(data) {
        if (typeof data.output != 'undefined') {
            var condition = jQuery(data.output);
            wrapper.find('.mncf-cd-entries').append(condition);
            var count = wrapper.find('input[type=hidden].mncf-cd-count').val();
            wrapper.find('input[type=hidden].mncf-cd-count').val(parseInt(count)+1);
            mncfConditionalFormDateToggle(condition.find('.mncf-cd-field'));
        }
    }, "json");

    /**
     * handle "Data-dependent display filters" for groups
     */
    if ( 'mncf-cd-group' == wrapper.attr('id') ) {
        jQuery('span.count', wrapper.closest('td')).html('('+(parseInt(jQuery('.mncf-cd-entry', wrapper).length)+1)+')');
    }

}

/**
 * Init Date conditional form check.
 */
function mncfConditionalFormDateInit()
{
    jQuery('#mncf-form-fields-main').on('change', '.mncf-cd-field', function(){
        mncfConditionalFormDateToggle(jQuery(this));
    }).find('.mncf-cd-field').each(function(){
        mncfConditionalFormDateToggle(jQuery(this));
    });
}

/**
 * Toggles input textfield to date inputs on Group edit screen.
 */
function mncfConditionalFormDateToggle(object) {
    var show = object.find(':selected').hasClass('mncf-conditional-select-date');
    var parent = object.parent();
    var select = parent.find('.mncf-cd-operation');
    if (show) {
        parent.find('.mncf-cd-value').hide();
        parent.find('.mncf-custom-field-date').show();
        select.find("option[value='==='], option[value='!==']").attr('disabled', 'disabled');
        var selected = select.find(':selected').val()
        if (selected == '===') {
            select.val('=').trigger('click');
        } else if (selected == '!==') {
            select.val('<>').trigger('click');
        }
    } else {
        parent.find('.mncf-cd-value').show();
        parent.find('.mncf-custom-field-date').hide();
        select.find("option[value='==='], option[value='!=='], option[value='<>']")
        .removeAttr('disabled');
    }
}

/**
 * Checks if Date is valid on Group edit screen.
 */
function mncfConditionalFormDateCheck() {
    var is_ok = true;
    jQuery('.mncf-custom-field-date').each(function(index) {
        var field = jQuery(this).parent().find('.mncf-cd-field :selected');
        if (field.hasClass('mncf-conditional-select-date')) {
            var month = jQuery(this).children(':first');
            var mm = month.val();
            var jj = month.next().val();
            var aa = month.next().next().val();
            var newD = new Date( aa, mm - 1, jj);

            if ( newD.getFullYear() != aa || (1 + newD.getMonth()) != mm || newD.getDate() != jj) {
                jQuery(this).parent().find('.mncf_custom_field_invalid_date').show();
                jQuery(this).parents('fieldset').children('.fieldset-wrapper').slideDown();
                is_ok = false;
            } else {
                jQuery(this).parent().find('.mncf_custom_field_invalid_date').hide();
            }
        }
    });
    return is_ok;
}

/*
 * TODO Not used?
 */
window.mncfConditional = new Array();
window.mncfConditionalPassed = new Array();
window.mncfConditionalHiddenFailed = new Array();
/*
 * Conditional JS.
 */
jQuery(document).ready(function(){
    // Trigger main func
    mncfConditionalInit();
    // Form edit screen
    mncfConditionalFormDateInit();
});

