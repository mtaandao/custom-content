<?php
/**
 *
 *
 */
/**
 * Register data (called automatically).
 *
 * @return type
 */
function mncf_fields_textarea()
{
    return array(
        'id' => 'mncf-textarea',
        'title' => __('Multiple lines', 'mncf'),
        'description' => __('Textarea', 'mncf'),
        'validate' => array(
            'required' => array(
                'form-settings' => include( dirname( __FILE__ ) . '/patterns/validate/form-settings/required.php' )
            )
        ),
        'font-awesome' => 'align-justify',
    );
}

/**
 * Meta box form.
 *
 * @param type $field
 * @return string
 */
function mncf_fields_textarea_meta_box_form($field)
{
    $form = array();
    $form['name'] = array(
        '#type' => 'textarea',
        '#name' => 'mncf[' . $field['slug'] . ']',
    );
    return $form;
}

/**
 * Formats display data.
 */
function mncf_fields_textarea_view($params)
{
    return mnautop($params['field_value']);
}
