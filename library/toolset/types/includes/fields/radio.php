<?php
/**
 * Types-field: Radio
 *
 * Description: Displays a radio selection to the user.
 *
 * Rendering: The option title will be rendered or if set - specific value.
 * 
 * Parameters:
 * 'raw' => 'true'|'false' (display raw data stored in DB, default false)
 * 'output' => 'html' (wrap data in HTML, optional)
 * 'show_name' => 'true' (show field name before value e.g. My checkbox: $value)
 *
 * Example usage:
 * With a short code use [types field="my-radios"]
 * In a theme use types_render_field("my-radios", $parameters)
 * 
 */

/**
 * Form data for group form.
 * 
 * @return type 
 */
function mncf_fields_radio_insert_form( $form_data = array(), $parent_name = '' ) {
    $id = 'mncf-fields-radio-' . mncf_unique_id( serialize( $form_data ) );
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

    $existing_options = array();
    $options = !empty( $form_data['options'] ) ? $form_data['options'] : array();
    $options = !empty( $form_data['data']['options'] ) ? $form_data['data']['options'] : $options;
    if ( !empty( $options ) ) {
        foreach ( $options as $option_key => $option ) {
            if ( $option_key == 'default' ) {
                continue;
            }
            $option['key'] = $option_key;
            $option['default'] = isset( $options['default'] ) ? $options['default'] : null;
            $form_option = mncf_fields_radio_get_option( '', $option );
            $existing_options[array_shift( $form_option )] = $option;
            $form = $form + $form_option;
        }
    } else {
        $form_option = mncf_fields_radio_get_option();
        $existing_options[array_shift( $form_option )] = array();
        $form = $form + $form_option;
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
        '#default_value' => isset( $options['default'] ) ? $options['default'] : null,
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
        '#markup' => '<a href="'
        . admin_url( 'admin-ajax.php?action=mncf_ajax&amp;mncf_action=add_radio_option&amp;_mnnonce='
                . mn_create_nonce( 'add_radio_option' ) . '&amp;mncf_ajax_update_add='
                . $id . '-sortable&amp;parent_name=' . urlencode( $parent_name )
                . '&amp;count=' . $count )
        . '" onclick="mncfFieldsFormCountOptions(jQuery(this));"'
        . ' class="button-secondary mncf-ajax-link">'
        . __( 'Add option', 'mncf' ) . '</a>',
            '#pattern' => '<ERROR><BEFORE><ELEMENT><AFTER></td></tr>',
            '#inline' => true,
    );

    $form['display'] = array(
        '#title' => __('Value to show', 'mncf'),
        '#type' => 'radios',
        '#default_value' => 'db',
        '#name' => 'display',
        '#options' => array(
            'display_from_db' => array(
                '#title' => __( 'Display the value of this field from the database', 'mncf' ),
                '#name' => 'display',
                '#value' => 'db',
                '#inline' => true,
                '#after' => '<br />'
            ),
            'display_values' => array(
                '#title' => __( 'Show one of these values:', 'mncf' ),
                '#name' => 'display',
                '#value' => 'value',
                '#inline' => true,
            ),
        ),
        '#inline' => true,
        '#pattern' => '<tr class="mncf-border-top"><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER>',
    );
    $form['display-open'] = array(
        '#type' => 'markup',
        '#markup' => sprintf(
            '<div id="mncf-form-groups-radio-ajax-response-%s-sortable">',
            esc_attr($id)
        ),
        '#pattern' => '<ERROR><BEFORE><ELEMENT><AFTER><dl>',
    );
    if ( !empty( $existing_options ) ) {
        foreach ( $existing_options as $option_id => $option_form_data ) {
            $form_option = mncf_fields_radio_get_option_alt_text( $option_id, '', $option_form_data );
            $form = $form + $form_option;
        }
    }
    $form['display-close'] = array(
        '#type' => 'markup',
        '#markup' => '</dl></div>',
        '#pattern' => '<ERROR><BEFORE><ELEMENT><AFTER></td></tr>',
    );
    return $form;
}

/**
 * Returns form data for radio.
 * 
 * @param type $parent_name Used for AJAX adding options
 * @param type $form_data
 * @return type 
 */
function mncf_fields_radio_get_option( $parent_name = '', $form_data = array() ) {
    $id = isset( $form_data['key'] ) ? $form_data['key'] : 'mncf-fields-radio-option-' . mncf_unique_id( serialize( $form_data ) );
    $form = array();
    $value = isset( $_GET['count'] ) ? __( 'Option title', 'mncf' ) . ' ' . intval( $_GET['count'] ) : __( 'Option title', 'mncf' ) . ' 1';
    $value = isset( $form_data['title'] ) ? $form_data['title'] : $value;
    $form[$id . '-id'] = $id;
    $form[$id . '-title'] = array(
        '#type' => 'textfield',
        '#id' => $id . '-title',
        '#name' => $parent_name . '[options][' . $id . '][title]',
        '#value' => $value,
        '#inline' => true,
        '#attributes' => array(
            'class' => 'widefat mncf-form-groups-radio-update-title-display-value',
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
        '#name' => $parent_name . '[options][default]',
        '#value' => $id,
        '#default_value' => isset( $form_data['default'] ) ? $form_data['default'] : '',
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

/**
 * Returns form data for radio.
 * 
 * @param type $parent_name Used for AJAX adding options
 * @param type $form_data
 * @return type 
 */
function mncf_fields_radio_get_option_alt_text( $id, $parent_name = '',
        $form_data = array() ) {
    $form = array();
    $title = isset( $_GET['count'] ) ? __( 'Option title', 'mncf' ) . ' ' . intval( $_GET['count'] ) : __( 'Option title', 'mncf' ) . ' 1';
    $title = isset( $form_data['title'] ) ? $form_data['title'] : $title;
    $value = isset( $_GET['count'] ) ? intval( $_GET['count'] ) : 1;
    $value = isset( $form_data['value'] ) ? $form_data['value'] : $value;
    $value = isset( $form_data['display_value'] ) ? $form_data['display_value'] : $value;
    $form[$id . '-display-value'] = array(
        '#type' => 'textfield',
        '#id' => $id . '-title-display-value',
        '#name' => $parent_name . '[options][' . $id . '][display_value]',
        '#title' => $title,
        '#value' => $value,
        '#inline' => true,
        '#before' => esc_attr(sprintf('%s-title-display-value-wrapper', $id)),
        '#attributes' => array(
            'placeholder' => __('Value to display', 'mncf'),
        ),
        '#pattern' => '<dt class="<BEFORE>"><LABEL></dt><dd class="<BEFORE>"><ERROR><ELEMENT><AFTER></dd>',
    );
    return $form;
}
