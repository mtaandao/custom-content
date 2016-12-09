<?php

/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function mncf_fields_textfield() {
    return array(
        'id' => 'mncf-texfield',
        'title' => __( 'Single line', 'mncf' ),
        'description' => __( 'Textfield', 'mncf' ),
        'validate' => array(
            'required' => array(
                'form-settings' => include( dirname( __FILE__ ) . '/patterns/validate/form-settings/required.php' )
            )
        ),
        'font-awesome' => 'font',
    );
}

/**
 * Meta box form.
 * 
 * @param type $field
 * @return string 
 */
function mncf_fields_textfield_meta_box_form( $field ) {
    $form = array();
    $form['name'] = array(
        '#type' => 'textfield',
        '#name' => 'mncf[' . $field['slug'] . ']',
    );
    return $form;
}
