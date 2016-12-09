<?php
/*
 * Numeric field.
 */

// Filters
add_filter( 'mncf_fields_type_numeric_value_save',
        'mncf_fields_numeric_value_save_filter', 10, 3 );
add_filter( 'mncf_fields_type_numeric_value_display',
        'mncf_fields_type_numeric_value_display_by_locale', 10, 3 );
add_filter( 'mncf_fields_numeric_meta_box_form_value_display',
        'mncf_fields_numeric_meta_box_form_value_display_by_locale', 10, 3 );

/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function mncf_fields_numeric() {

    return array(
        'id' => 'mncf-numeric',
        'title' => __( 'Number', 'mncf' ),
        'description' => __( 'Number', 'mncf' ),
        'validate' => array(
            'required' => array(
                'form-settings' => include( dirname( __FILE__ ) . '/patterns/validate/form-settings/required.php' ),
            ),
            'number' => array(
                'forced' => true,
                'form-settings' => include( dirname( __FILE__ ) . '/patterns/validate/form-settings/numeric.php' )
            )
        ),
        'inherited_field_type' => 'textfield',
        'meta_key_type' => 'NUMERIC',
        'meta_box_js' => array('mncf_field_number_validation_fix' => array(
            'inline' => 'mncf_field_number_validation_fix')
        ),
        'font-awesome' => 'calculator',
    );
}

function mncf_fields_numeric_meta_box_form_value_display_by_locale( $field ){
    $locale = localeconv();
    if ( $locale['decimal_point'] != '.' ) {
        $field['#value'] = str_replace( '.', ',', $field['#value'] );
    }
    return $field;
}

/**
 * mncf_fields_numeric_value_save_filter
 *
 * if decimal_point = comma, replace point to comma.
 */
function mncf_fields_type_numeric_value_display_by_locale( $val ){
    $locale = localeconv();
    if ( $locale['decimal_point'] != '.' ) {
        $val = str_replace( '.', ',', $val );
    }
    return $val;
}

/**
 * mncf_fields_numeric_value_save_filter
 *
 * if decimal_point = comma, replace comma to point.
 */
function mncf_fields_numeric_value_save_filter( $val ){
    $locale = localeconv();
    if ( $locale['decimal_point'] != '.' ) {
        $val = str_replace( ',', '.', $val );
    }
    return $val;
}

/**
 * mncf_field_number_validation_fix
 *
 * Fix JS validation for field:numeric. Allow comma validation 
 */
function mncf_field_number_validation_fix(){
    $locale = localeconv();
    if ( $locale['decimal_point'] != '.' ) {
        mn_enqueue_script( 'mncf-numeric',
                MNCF_EMBEDDED_RES_RELPATH
                . '/js/numeric_fix.js', array('jquery'), MNCF_VERSION );
    }
}

/**
 * Editor callback form.
 */
function mncf_fields_numeric_editor_callback( $field, $settings ) {
    return array(
        'supports' => array('style'),
        'tabs' => array(
            'display' => array(
                'menu_title' => __( 'Display options', 'mncf' ),
                'title' => __( 'Display options for this field:', 'mncf' ),
                'content' => MNCF_Loader::template( 'editor-modal-numeric',
                        $settings ),
            )
        )
    );
}

/**
 * Editor callback form submit.
 */
function mncf_fields_numeric_editor_submit( $data, $field, $context ) {
    $add = '';
    if ( !empty( $data['format'] ) ) {
        $add .= ' format="' . strval( $data['format'] ) . '"';
    }
    if ( $context == 'usermeta' ) {
        $add .= mncf_get_usermeta_form_addon_submit();
        $shortcode = mncf_usermeta_get_shortcode( $field, $add );
	} elseif ( $context == 'termmeta' ) {
        $add .= mncf_get_termmeta_form_addon_submit();
        $shortcode = mncf_termmeta_get_shortcode( $field, $add );
    } else {
        $shortcode = mncf_fields_get_shortcode( $field, $add );
    }
    return $shortcode;
}

/**
 * View function.
 * 
 * @param type $params 
 */
function mncf_fields_numeric_view( $params ) {
    $output = '';
    if ( !empty( $params['format'] ) ) {
        $patterns = array('/FIELD_NAME/', '/FIELD_VALUE/');
        $replacements = array($params['field']['name'], $params['field_value']);
        $output = preg_replace( $patterns, $replacements, $params['format'] );
        $output = sprintf( $output, $params['field_value'] );
    } else {
        $output = $params['field_value'];
    }
    return $output;
}
