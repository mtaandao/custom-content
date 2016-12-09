var mntDate = (function ($) {
    var _tempConditions, _tempField;
    function init(parent) {
        if ($.isFunction($.fn.datepicker)) {
            $('input.js-mnt-date', $(parent)).each(function (index) {
                //removed !$(this).is(':disabled') && 
                //cred-64
                if (/*!$(this).is(':disabled') &&*/ !$(this).hasClass('hasDatepicker')) {
                    a = mntDate.add($(this));
                    //a.next().after('<span style="margin-left:10px"><i>' + mntDateData.dateFormatNote + '</i></span>').data( 'dateFormatNote', true );
                }
            });
        }

        $(document).on('click', '.js-mnt-date-clear', function () {
            var thiz = $(this), thiz_close, el, el_aux, el_select;
            if (thiz.closest('.js-mnt-field-item').length > 0) {
                thiz_close = thiz.closest('.js-mnt-field-item');
                el_aux = thiz_close.find('.js-mnt-date-auxiliar');
                el = thiz_close.find('.js-mnt-date');
                el_select = thiz_close.find('select');
            } else if (thiz.closest('.mnt-repctl').length > 0) {
                thiz_close = thiz.closest('.mnt-repctl');
                el_aux = thiz_close.find('.js-mnt-date-auxiliar');
                el = thiz_close.find('.js-mnt-date');
                el_select = thiz_close.find('select');
            } else if (thiz.closest('.js-mnt-field-items').length > 0) {
                thiz_close = thiz.closest('.js-mnt-field-items');
                el_aux = thiz_close.find('.js-mnt-date-auxiliar');
                el = thiz_close.find('.js-mnt-date');
                el_select = thiz_close.find('select');
            } else {
                // This should be an empty object, but as we use the variable later we need to set it
                el_aux = thiz.closest('.js-mnt-field-items');
                el = thiz.closest('.js-mnt-date');
                el_select = thiz.closest('select');
            }
            //Added trigger('mntDateSelect'); fix trigger validation and condition on click of clear
            el_aux.val('').trigger('change').trigger('mntDateSelect');
            el.val('');
            el_select.val('0');
            thiz.hide();
            
        });
    }

    function add(el)
    {
        // Before anything, return if this is readonly
        if (el.hasClass('js-mnv-date-readonly')) {
            if (!el.hasClass('js-mnv-date-readonly-added')) {
                el.addClass('js-mnv-date-readonly-added').after('<img src="' + mntDateData.readonly_image + '" alt="' + mntDateData.readonly + '" title="' + mntDateData.readonly + '" class="ui-datepicker-readonly" />');
            }
            return;
        }
        // First, a hacky hack: make the id of each el unique, because the way they are produced on repetitive date fields does not ensure it
        var rand_number = 1 + Math.floor(Math.random() * 150),
                old_id = el.attr('id');
        el.attr('id', old_id + '-' + rand_number);
        // Walk along, nothing to see here...
        return el.datepicker({
            onSelect: function (dateText, inst) {
                //	The el_aux element depends on the scenario: backend or frontend
                var el_close, el_aux, el_clear;
                el.val('');
                if (el.closest('.js-mnt-field-item').length > 0) {
                    el_close = el.closest('.js-mnt-field-item');
                    el_aux = el_close.find('.js-mnt-date-auxiliar');
                    el_clear = el_close.find('.js-mnt-date-clear');
                } else if (el.closest('.mnt-repctl').length > 0) {
                    el_close = el.closest('.mnt-repctl');
                    el_aux = el_close.find('.js-mnt-date-auxiliar');
                    el_clear = el_close.find('.js-mnt-date-clear');
                } else if (el.closest('.js-mnt-field-items').length > 0) {
                    el_close = el.closest('.js-mnt-field-items');
                    el_aux = el_close.find('.js-mnt-date-auxiliar');
                    el_clear = el_close.find('.js-mnt-date-clear');
                } else {
                    // This should be an empty object, but as we use the variable later we need to set it
                    el_aux = el.closest('.js-mnt-field-items');
                    el_clear = el.closest('.js-mnt-date-clear');
                }
                var data = 'date=' + dateText;
                data += '&date-format=' + mntDateData.dateFormatPhp;
                data += '&action=mnt_localize_extended_date';
                                
                $.post(mntDateData.ajaxurl, data, function (response) {
                    response = $.parseJSON(response);
                    if (el_aux.length > 0) {
                        el_aux.val(response['timestamp']).trigger('mntDateSelect');
                    }
                    el.val(response['display']);
                    el_clear.show();
                    
                    //Fix adding remove label on date
                    el.prev('label.mnt-form-error').remove();
                });
                //el.trigger('mntDateSelect');
            },
            showOn: "both",
            buttonImage: mntDateData.buttonImage,
            buttonImageOnly: true,
            buttonText: mntDateData.buttonText,
            dateFormat: 'ddmmyy',
            //dateFormat: mntDateData.dateFormat,
            //altFormat: mntDateData.dateFormat,
            changeMonth: true,
            changeYear: true,
            yearRange: mntDateData.yearMin + ':' + mntDateData.yearMax,
            beforeShow: function(input) { }
        });
    }

    function ajaxConditional(formID, conditions, field) {
        _tempConditions = conditions;
        _tempField = field;
        mntCallbacks.conditionalCheck.add(mntDate.ajaxCheck);
    }
    function ajaxCheck(formID) {
        mntCallbacks.conditionalCheck.remove(mntDate.ajaxCheck);
        mntCond.ajaxCheck(formID, _tempField, _tempConditions);
    }
    function ignoreConditional(val) {
        if ('' == val) {
            return '__ignore_negative';
        }
        return val;
        //return Date.parse(val);
    }
    function bindConditionalChange($trigger, func, formID) {
        $trigger.on('mntDateSelect', func);
        //var lazy = _.debounce(func, 1000);
        //$trigger.on('keyup', lazy);
        return false;
    }
    function triggerAjax(func) {
        if ($(this).val().length >= mntDateData.dateFormatPhp.length)
            func();
    }
    return {
        init: init,
        add: add,
        ajaxConditional: ajaxConditional,
        ajaxCheck: ajaxCheck,
        ignoreConditional: ignoreConditional,
        bindConditionalChange: bindConditionalChange,
        triggerAjax: triggerAjax
    };
})(jQuery);

jQuery(document).ready(function () {
    mntDate.init('body');
    //fixing unknown Srdjan error
    jQuery('.ui-datepicker-inline').hide();
});

if ('undefined' != typeof (mntCallbacks)) {
    mntCallbacks.reset.add(function (parent) {
        mntDate.init(parent);
    });
    mntCallbacks.addRepetitive.add(mntDate.init);
}

//add_action('conditional_check_date', mntDate.ajaxConditional, 10, 3);
if ('function' == typeof (add_filter)) {
    add_filter('conditional_value_date', mntDate.ignoreConditional, 10, 1);
}
if ('function' == typeof (add_action)) {
    add_action('conditional_trigger_bind_date', mntDate.bindConditionalChange, 10, 3);
}
