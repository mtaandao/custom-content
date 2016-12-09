<?php

/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function mncf_fields_colorpicker() {
    return array(
        'id' => 'mncf-colorpicker',
        'title' => __( 'Colorpicker', 'mncf' ),
        'description' => __( 'Colorpicker', 'mncf' ),
        'validate' => array(
            'required' => array(
                'form-settings' => include( dirname( __FILE__ ) . '/patterns/validate/form-settings/required.php' )
            )
        ),
        'meta_box_js' => array(
            'mncf-jquery-fields-colorpicker' => array(
                'inline' => 'mncf_fields_colorpicker_render_js',
            ),
        ),
        'font-awesome' => 'eyedropper',
    );
}

/**
 * Meta box form.
 * 
 * @param type $field
 * @return string 
 */
function mncf_fields_colorpicker_meta_box_form( $field ) {
    $form = array();
    $form['name'] = array(
        '#type' => 'textfield',
        '#name' => 'mncf[' . $field['slug'] . ']',
        '#attributes' => array('class' => 'js-types-colorpicker', 'style' => 'width:100px;'),
        '#after' => '',
    );
    mncf_fields_colorpicker_enqueue_scripts();
	//By Gen: changed minimal version from 3.4 to 3.5, because colorbox not works in 3.4.2
    if ( mncf_compare_mn_version( '3.5', '<' ) ) {
        $form['name']['#after'] .= '<a href="#" class="button-secondary js-types-pickcolor">' . __( 'Pick color', 'mncf' ) . '</a><div class="js-types-cp-preview types-cp-preview" style="background-color:' . $field['value'] . '"></div>';
        mn_enqueue_script( 'farbtastic' );
        mn_enqueue_style( 'farbtastic' );
    } else {
        mn_enqueue_script( 'mn-color-picker' );
        mn_enqueue_style( 'mn-color-picker' );
        if ( defined( 'DOING_AJAX' ) ) {
            $form['name']['#after'] .= '<script type="text/javascript">typesPickColor.init();</script>';
        }
    }
    return $form;
}

function mncf_fields_colorpicker_enqueue_scripts() {
	//By Gen: changed minimal version from 3.4 to 3.5, because colorbox not works in 3.4.2
    if ( mncf_compare_mn_version( '3.5', '<' ) ) {
        mn_enqueue_script( 'farbtastic' );
        mn_enqueue_style( 'farbtastic' );
    } else {
        mn_enqueue_script( 'mn-color-picker' );
        mn_enqueue_style( 'mn-color-picker' );
    }
}

function mncf_fields_colorpicker_render_js() {
	//By Gen: changed minimal version from 3.4 to 3.5, because colorbox not works in 3.4.2
    if ( mncf_compare_mn_version( '3.5', '<' ) ) {
        mncf_fields_colorpicker_js_farbtastic();
    } else {
        mncf_fields_colorpicker_js();
    }
}

/**
 * Colorpicker JS.
 */
function mncf_fields_colorpicker_js() {

    ?>
    <script type="text/javascript">
        /* <![CDATA[ */
        var typesPickColor = (function($) {
            function init() {
                if ($.isFunction($.fn.mnColorPicker)) {
                    $('.js-types-colorpicker').each(function(){
                        $(this).not(':disabled').mnColorPicker();
                    });
                }
            }
            return {init: init}
        })(jQuery);
        (function($) {
            $(document).ready(function() {
                typesPickColor.init();
            });
        })(jQuery);
        /* ]]> */
    </script>
    <?php
}

/**
 * Pre MN 3.5 JS.
 */
function mncf_fields_colorpicker_js_farbtastic() {

    ?>
    <div id="types-color-picker" style="display:none; background-color: #FFF; width:220px; padding: 10px;"></div>
    <script type="text/javascript">
        /* <![CDATA[ */
        var farbtasticTypes;
        var typesPickColor = (function($) {
            var el;
            function set(color) {
                el.parent().find('.js-types-cp-preview').css('background-color', color)
                        .parent().find('.js-types-colorpicker').val(color);
                toggleButton();
            }
            function show(element) {
                el = element;
                var offset = el.offset();
                farbtasticTypes.setColor(el.parent().find('.js-types-colorpicker').val());
                $('#types-color-picker').toggle().offset({left: offset.left, top: Math.round(offset.top + 25)});
                toggleButton();
            }
            function toggleButton() {
                $('.js-types-pickcolor').text('<?php echo esc_js( __( 'Pick color', 'mncf' ) ); ?>');
                el.text($('#types-color-picker').is(':visible') ? '<?php echo esc_js( __( 'Done', 'mncf' ) ); ?>' : '<?php echo esc_js( __( 'Pick color', 'mncf' ) ); ?>');
            }
            return {set: set, show: show}
        })(jQuery);
        (function($) {
            if ($.isFunction($.fn.farbtastic)) {
            $(document).ready(function() {
                $('#post').on('click', '.js-types-pickcolor', function(e) {
                    e.preventDefault();
                    typesPickColor.show($(this));
                    return false;
                });
                farbtasticTypes = $.farbtastic('#types-color-picker', typesPickColor.set);
            });
            }
        })(jQuery);
        /* ]]> */
    </script>
    <?php
}

/**
 * View function
 * 
 * @param type $params
 * @return string
 */
function mncf_fields_colorpicker_view( $params ) {
    if ( empty( $params['field_value'] ) || strpos( $params['field_value'], '#' ) !== 0 || !( strlen( $params['field_value'] ) == 4 || strlen( $params['field_value'] ) == 7 ) ) {
        return '__mncf_skip_empty';
    }
    return $params['field_value'];
}
