
var mntSkype = (function($) {
    var $parent, $skypename, $preview, $fields;
    var $popup = $('#tpl-mnt-skype-edit-button > div');
    function init() {
        $('body').on('click', '.js-mnt-skype-edit-button', function() {
            $parent = $(this).parents('.js-mnt-field-item');
            $skypename = $('.js-mnt-skypename', $parent);
            $preview = $('.js-mnt-skype-preview', $parent);
            $('.js-mnt-skypename-popup', $popup).val($skypename.val());
            tb_show(mntSkypeData.title, "#TB_inline?inlineId=tpl-mnt-skype-edit-button&height=500&width=600", "");
            $('.js-mnt-skype', $popup).on("change", function(){
                mntSkype.preview($popup, this);
            });
            $('.js-mnt-skype', $popup).on("keyup", function(){
                mntSkype.preview($popup, this);
            });
            mntSkype.preview($popup, this, 'init');
        });
        $('#mnt-skype-edit-button-popup').on('click', '.js-mnt-close-thickbox', function() {
            var button = $('.js-mnt-skype-edit-button', $parent);
            var $extra_skype_data = {};
            $skypename.val($('.js-mnt-skypename-popup', $popup).val());
            $('.js-mnt-skype', $popup).each(function() {
                var $field_name = $(this).data('skype-field-name');
                var $val = $(this).val();
                if ( $field_name ) {
                    switch($(this).data('mnt-type')) {
                        case 'checkbox':
                            if ( $(this).is(':checked') ) {
                                $('.js-mnt-skype-'+$field_name, $parent).val($val);
                                button.data($field_name, $val);
                            }
                            break;
                        case 'option':
                            if ( $(this).is(':selected') ) {
                                $('.js-mnt-skype-'+$field_name, $parent).val($val);
                                button.data($field_name, $val);
                            }
                            break;
                        case 'textfield':
                            $('.js-mnt-skype-'+$field_name, $parent).val($val);
                            button.data($field_name, $val);
                            break;
                    }
                }
            });
            /**
             * fix data for action
             */
            if ( 1 < $('.js-mnt-skype-action:checked', $popup).length ) {
                $('.js-mnt-skype-action', $popup).val('dropdown');;
                $(this).data('action', 'dropdown');
            }
            tb_remove();
        });
    }
    function preview($popup, object, mode) {
        var $object = $(object);
        /**
         * be sure, that at lest one action is on
         */
        if ( 'checkbox' == $object.attr('type') ) {
            if ( 0 == $('.js-mnt-skype-action:checked', $popup).length ) {
                $('.js-mnt-skype-action', $popup).each(function() {
                    if ( this != object ) {
                        $(this).attr('checked', 'checked');
                    }
                });
            }
        }

        /**
         * participants
         */
        var $button = $('#mnt-skype-edit-button-popup-preview-button');
        $('#mnt-skype-preview', $button).html('');
        var participants = $('.js-mnt-skypename-popup', $popup).val();

        /**
         * setup values
         */
        if ( 'undefined' != typeof mode && 'init' == mode ) {
            if ( value = $object.data('size') ) {
                $('.js-mnt-skype-size option', $popup).removeAttr('selected');
                $('.js-mnt-skype-size [value='+value+']', $popup).attr('selected', 'selected');
            }
            if ( value = $object.data('color') ) {
                $('.js-mnt-skype-color option', $popup).removeAttr('selected');
                $('.js-mnt-skype-color [value='+value+']', $popup).attr('selected', 'selected');
            }
            if ( value = $object.data('action') ) {
                switch(value) {
                    case 'dropdown':
                        $('.js-mnt-skype-action', $popup).attr('checked', 'checked');
                        break;
                    case 'chat':
                    case 'call':
                        $('.js-mnt-skype-action', $popup).removeAttr('checked');
                        $('.js-mnt-skype-action-'+value, $popup).attr('checked', 'checked');
                        break;
                    default:
                        $('.js-mnt-skype-action', $popup).removeAttr('checked');
                        $('.js-mnt-skype-action-call', $popup).attr('checked', 'checked');
                        break;
                }
            }
        }
        /**
         * skypename
         */
        var skypename = "dropdown";
        if ($('.js-mnt-skype-action:checked', $popup).length < 2 ) {
            skypename = $('.js-mnt-skype-action:checked', $popup).val();
        }
        /**
         * Skype.ui
         */
        if ( participants.length > 2 ) {
            if ( 'object' == typeof Skype) {
                data = {
                    name: skypename,
                    element: "mnt-skype-preview",
                    participants: [participants],
                    imageSize: parseInt($('.js-mnt-skype-size option:selected', $popup).val()),
                    imageColor: $('.js-mnt-skype-color option:selected', $popup).val()
                }
                /**
                 * show tooltip
                 */
                if ( 'dropdown' == data.name ) {
                    $('small', $button).show();
                } else {
                    $('small', $button).hide();
                }
                /**
                 * change parent background to see skype in white
                 */
                if ( 'white' == data.imageColor) {
                    $button.addClass('dark-background');

                } else {
                    $button.removeClass('dark-background');
                }
                Skype.ui(data);
            }
        }
    }
    return {
        init: init,
        preview: preview
    };
})(jQuery);

jQuery(document).ready(mntSkype.init);
