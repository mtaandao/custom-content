var mntColorpicker = (function($) {
    function init(parent) {
        $('input.js-mnt-colorpicker').iris({
            change: function(event, ui) {
                if ( 'function' == typeof ( $(event.target).data('_bindChange') ) ) {
                    $(event.target).data('_bindChange')();
                }
                
            }
        });
        $(document).click(function (e) {
            if (!$(e.target).is("input.js-mnt-colorpicker, .iris-picker, .iris-picker-inner")) {
                $('input.js-mnt-colorpicker').iris('hide');
            }
        });
        $('input.js-mnt-colorpicker').click(function (event) {
            $('input.js-mnt-colorpicker').iris('hide');
            $(this).iris('show');
            return false;
        });
    }
    return {
        init: init
    };
})(jQuery);

jQuery(document).ready(function() {
    mntColorpicker.init('body');
});
mntCallbacks.reset.add(function(parent) {
    mntColorpicker.init(parent);
});
/**
 * add for new repetitive field
 */
mntCallbacks.addRepetitive.add(mntColorpicker.init);
