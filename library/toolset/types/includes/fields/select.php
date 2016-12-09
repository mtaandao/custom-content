<?php
/**
 * Types-field: Select
 *
 * Description: Displays a select box to the user.
 *
 * Rendering: The option title will be rendered or if set - specific value.
 * 
 * Parameters:
 * 'raw' => 'true'|'false' (display raw data stored in DB, default false)
 * 'output' => 'html' (wrap data in HTML, optional)
 * 'show_name' => 'true' (show field name before value e.g. My checkbox: $value)
 *
 * Example usage:
 * With a short code use [types field="my-select"]
 * In a theme use types_render_field("my-select", $parameters)
 * 
 */

/**
 * Form data for group form.
 * 
 * @return type 
 */
function mncf_fields_select_insert_form( $form_data = array(), $parent_name = '' ) {
    $id = 'mncf-fields-select-' . mncf_unique_id( serialize( $form_data ) );
    $form['name'] = array(
        '#type' => 'textfield',
        '#title' => __( 'Name of custom field', 'mncf' ),
        '#description' => __( 'Under this name field will be stored in DB (sanitized)', 'mncf' ),
        '#name' => 'name',
        '#attributes' => array('class' => 'mncf-forms-set-legend'),
        '#validate' => array('required' => array('value' => true)),
    );
    $form['description'] = array(
        '#type' => 'textarea',
        '#title' => __( 'Description', 'mncf' ),
        '#description' => __( 'Text that describes function to user', 'mncf' ),
        '#name' => 'description',
        '#attributes' => array('rows' => 5, 'cols' => 1),
    );
    $form['options-markup-open'] = array(
        '#type' => 'markup',
        '#title' => __( 'Options', 'mncf' ),
        '#markup' => sprintf(
            '<table class="striped mncf-fields-field-value-options"><thead><tr>'
            .'<th>&nbsp;</th>'
            .'<th class="mncf-form-options-header-title">%s</th>'
            .'<th class="mncf-form-options-header-value">%s</th>'
            .'<th class="mncf-form-options-header-default">%s</th>'
            .'<th>&nbsp;</th>'
            .'</tr></thead>'
            .'<tbody id="%s-sortable" class="mncf-fields-radio-sortable mncf-compare-unique-value-wrapper">',
            __( 'Display text', 'mncf' ),
            __( 'Custom field content', 'mncf' ),
            __( 'Default', 'mncf' ),
            esc_attr($id)
        ),
        '#pattern' => '<tr class="mncf-border-top"><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER>',
    );
    $options = !empty( $form_data['options'] ) ? $form_data['options'] : array();
    $options = !empty( $form_data['data']['options'] ) ? $form_data['data']['options'] : $options;
    if ( !empty( $options ) ) {
        foreach ( $options as $option_key => $option ) {
            if ( $option_key == 'default' ) {
                continue;
            }
            $option['key'] = $option_key;
            $option['default'] = isset( $options['default'] ) ? $options['default'] : null;
            $form = $form + mncf_fields_select_get_option( '', $option );
        }
    } else {
        $form = $form + mncf_fields_select_get_option();
    }

    /**
     * sanitize default option
     */
    if ( !isset($options['default'])) {
        $options['default'] = 'no-default';
    }

    $form['options-no-default'] = array(
        '#type' => 'radio',
        '#inline' => true,
        '#title' => __( 'No Default', 'mncf' ),
        '#name' => '[options][default]',
        '#value' => 'no-default',
        '#default_value' => isset( $options['default'] ) ? $options['default'] : 'no-default',
        '#inline' => true,
        '#pattern' => '</tbody><tfoot><tr><td>&nbsp;</td><td>&nbsp;</td><td><LABEL></td><td class="num"><ERROR><BEFORE><ELEMENT><AFTER></td><td>&nbsp;</td></tr></tfoot>',
    );

    $form['options-response-close'] = array(
        '#type' => 'markup',
        '#markup' => '</table>',
        '#pattern' => '<ERROR><BEFORE><ELEMENT><AFTER>',
    );

    if ( !empty( $options ) ) {
        $count = count( $options );
    } else {
        $count = 1;
    }

    $form['options-markup-close'] = array(
        '#type' => 'markup',
        '#markup' => '<a href="' . admin_url( 'admin-ajax.php?action=mncf_ajax&amp;mncf_action=add_select_option&amp;_mnnonce='
                . mn_create_nonce( 'add_select_option' ) . '&amp;mncf_ajax_update_add=' . $id . '-sortable&amp;parent_name=' . urlencode( $parent_name )
                . '&amp;count=' . $count )
        . '" onclick="mncfFieldsFormCountOptions(jQuery(this));"'
        . ' class="button-secondary mncf-ajax-link">'
        . __( 'Add option', 'mncf' ) . '</a>',
            '#pattern' => '<ERROR><BEFORE><ELEMENT><AFTER></td></tr>',
    );
    return $form;
}

function mncf_fields_select_get_option( $parent_name = '', $form_data = array() ) {
    $id = isset( $form_data['key'] ) ? $form_data['key'] : 'mncf-fields-select-option-'
            . mncf_unique_id( serialize( $form_data ) );
    $form = array();
    $value = isset( $_GET['count'] ) ? __( 'Option title', 'mncf' ) . ' ' . intval( $_GET['count'] ) : __( 'Option title', 'mncf' ) . ' 1';
    $value = isset( $form_data['title'] ) ? $form_data['title'] : $value;
    $form[$id . '-title'] = array(
        '#type' => 'textfield',
        '#id' => $id . '-title',
        '#name' => $parent_name . '[options][' . $id . '][title]',
        '#value' => $value,
        '#inline' => true,
        '#attributes' => array(
            'placeholder' => __('Title', 'mncf'),
        ),
        '#before' => sprintf(
            '<span class="js-types-sortable hndle"><i title="%s" class="js-types-sort-button fa fa-arrows-v"></i></span>',
            esc_attr__( 'Move this option', 'mncf')
        ),
        '#pattern' => '<tr><td class="num"><BEFORE></td><td><ELEMENT><AFTER></td>',
    );
    $value = isset( $_GET['count'] ) ? intval( $_GET['count'] ) : 1;
    $value = isset( $form_data['value'] ) ? $form_data['value'] : $value;
    $form[$id . '-value'] = array(
        '#type' => 'textfield',
        '#id' => $id . '-value',
        '#name' => $parent_name . '[options][' . $id . '][value]',
        '#value' => $value,
        '#inline' => true,
        '#attributes' => array(
            'class' => 'mncf-compare-unique-value',
            'placeholder' => __('Value', 'mncf'),
        ),
        '#pattern' => '<td><BEFORE><ELEMENT><AFTER></td>',
    );
    $form[$id . '-default'] = array(
        '#type' => 'radio',
        '#id' => $id . '-default',
        '#inline' => true,
        '#title' => __( 'Default', 'mncf' ),
        '#after' => '</div>',
        '#name' => $parent_name . '[options][default]',
        '#value' => $id,
        '#default_value' => isset( $form_data['default'] ) ? $form_data['default'] : false,
        '#pattern' => '<td class="num"><BEFORE><ELEMENT></td><td class="num"><AFTER></td></tr>',
        '#after' => sprintf(
            '<span><a href="#" class="js-mncf-button-delete" data-message-delete-confirm="%s" data-id="%s"><i title="%s" class="fa fa-trash"></i></span>',
            esc_attr__( 'Are you sure?', 'mncf' ),
            esc_attr(sprintf('%s-title-display-value-wrapper', $id)),
            esc_attr__( 'Delete this option', 'mncf' )
        ),
    );
    return $form;
}
