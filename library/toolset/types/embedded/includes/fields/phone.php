<?php
/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function mncf_fields_phone() {

    return array(
        'id' => 'mncf-phone',
        'title' => __('Phone', 'mncf'),
        'description' => __('Phone', 'mncf'),
        'validate' => array(
            'required' => array(
                'form-settings' => include( dirname( __FILE__ ) . '/patterns/validate/form-settings/required.php' )
            )
        ),
        'inherited_field_type' => 'textfield',
        'font-awesome' => 'phone',
    );
}
