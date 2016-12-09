<?php

/**
 * Renders inline JS.
 * TODO this seems DEPRECATED and not used anymore, need to check (although I do not know where)
 */
function mncf_fields_date_meta_box_js_inline() {

    $date_format = mncf_get_date_format();
    $date_format = _mncf_date_convert_mn_to_js( $date_format );

    $date_format_note = '<span style="margin-left:10px"><i>' . esc_js( sprintf( __( 'Input format: %s', 'mncf' ), mncf_get_date_format_text() ) ) . '</i></span>';
    $year_range = fields_date_timestamp_neg_supported() ? '1902:2037' : '1970:2037';

    ?>
    <script type="text/javascript">
        //<![CDATA[
        jQuery(document).ready(function(){
            mncfFieldsDateInit('');
        });
        function mncfFieldsDateInit(div) {
            if (jQuery.isFunction(jQuery.fn.datepicker)) {
                jQuery(div+' .mncf-datepicker').each(function(index) {
                    if (!jQuery(this).is(':disabled') && !jQuery(this).hasClass('hasDatepicker')) {
                        jQuery(this).datepicker({
                            showOn: "button",
                            buttonImage: "<?php echo MNCF_EMBEDDED_RES_RELPATH; ?>/images/calendar.gif",
                            buttonImageOnly: true,
                            buttonText: "<?php
    _e( 'Select date', 'mncf' );

    ?>",
                            dateFormat: "<?php echo $date_format; ?>",
                            altFormat: "<?php echo $date_format; ?>",
                            changeMonth: true,
                            changeYear: true,
                            yearRange: "<?php echo $year_range; ?>",
                            onSelect: function(dateText, inst) {
                                jQuery(this).trigger('mncfDateBlur');
                            }
                        });
                        jQuery(this).next().after('<?php echo $date_format_note; ?>');
                        // Wrap in CSS Scope
                        jQuery("#ui-datepicker-div").each(function(){
                            if (!jQuery(this).hasClass('mncf-jquery-ui-wrapped')) {
                                jQuery(this).wrap('<div class="mncf-jquery-ui" />')
                                .addClass('mncf-jquery-ui-wrapped');
                            }
                        });
                    }
                });
            }
        }
        //]]>
    </script>
    <?php
}

/**
 * AJAX window JS.
 */
function mncf_fields_date_editor_form_script() {

    ?>
    <script type="text/javascript">
        // <![CDATA[
        jQuery(document).ready(function(){
            jQuery('input[name|="mncf[style]"]').change(function(){
                if (jQuery(this).val() == 'text') {
                    jQuery('#mncf-toggle').slideDown();
                } else {
                    jQuery('#mncf-toggle').slideUp();
                }
            });
            if (jQuery('input[name="mncf[style]"]:checked').val() == 'text') {
                jQuery('#mncf-toggle').show();
            }
        });
        // ]]>
    </script>
    <?php
}
