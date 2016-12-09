<?php
/**
 * Types-field: Entry
 *
 * Description: Displays a select2 with entries of defined post_type
 */

/**
 * Form data for group form.
 * 
 * @return type 
 */
function mncf_fields_entry_insert_form( $form_data ) {
    $meta_type = isset($_GET['page']) && $_GET['page'] != 'mncf-edit' ? 'usermeta' : 'postmeta';
    $form = array();
    $form['name'] = array(
        '#type' => 'textfield',
        '#title' => __( 'Name of custom field', 'mncf' ),
        '#description' => __( 'Under this name field will be stored in DB (sanitized)', 'mncf' ),
        '#name' => 'name',
        '#attributes' => array(
            'class' => 'mncf-forms-set-legend',
        ),
        '#validate' => array('required' => array('value' => true)),
    );
    $form['description'] = array(
        '#type' => 'textarea',
        '#title' => __( 'Description', 'mncf' ),
        '#description' => __( 'Text that describes function to user', 'mncf' ),
        '#name' => 'description',
        '#attributes' => array('rows' => 5, 'cols' => 1),
    );
    $post_types = get_post_types( array('public' => true), 'objects' );
    usort($post_types, 'mncf_fields_entry_insert_form_sort_helper');
    $options = array();
    foreach ( $post_types as $post_type ) {
        $options[$post_type->name] = array(
                '#title' => $post_type->labels->singular_name,
                '#name' => 'post_type',
                '#value' => esc_attr($post_type->name),
                '#inline' => true,
                '#after' => '<br />',
        );
    }
    $form['post_type'] = array(
        '#type' => 'radios',
        '#default_value' => 'post',
        '#name' => 'post_type',
        '#options' => $options,
        '#inline' => true,
        '#title' => __('Select post type', 'mncf'),
    );
    return $form;
}

function mncf_fields_entry_insert_form_sort_helper($a, $b)
{
    if ( !is_object($a) || !is_object($b) ) {
        return 0;
    }
    if ( $a->labels->singular_name == $b->labels->singular_name ) {
        if ( $a->name == $b->name ) {
            return 0;
        }
        return $a->name < $b->name? -1: 1;
    }
    return $a->labels->singular_name < $b->labels->singular_name? -1: 1;
}

