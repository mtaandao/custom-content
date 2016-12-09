<?php
/**
 * Types-field: Checkboxes
 *
 * Description: Displays a checkbox to the user. Checkboxes can be
 * used to get binary, yes/no responses from a user.
 *
 * Rendering: The "Value to stored" for the checkbox the front end
 * if the checkbox is checked or 'Selected'|'Not selected' HTML
 * will be rendered. If 'Selected'|'Not selected' HTML is not specified then
 * nothing is rendered.
 * 
 * Parameters:
 * 'raw' => 'true'|'false' (display raw data stored in DB, default false)
 * 'output' => 'html' (wrap data in HTML, optional)
 * 'show_name' => 'true' (show field name before value e.g. My checkbox: $value)
 *
 * Example usage:
 * With a short code use [types field="my-checkboxes"]
 * In a theme use types_render_field("my-checkboxes", $parameters)
 * 
 */

/**
 * Form data for group form.
 * 
 * @return type 
 */
function mncf_fields_checkboxes_insert_form( $form_data, $parent_name = '' ) {
    $meta_type = isset($_GET['page']) && $_GET['page'] != 'mncf-edit' ? 'usermeta' : 'postmeta';
    $id = 'mncf-fields-checkboxes-' . mncf_unique_id( serialize( $form_data ) . $parent_name );
    $form = array();
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
    $cb_migrate_save = !empty( $form_data['slug'] ) ? 'mncfCbSaveEmptyMigrate(jQuery(this), \'' . $form_data['slug'] . '\', \'\', \'' . mn_create_nonce( 'cb_save_empty_migrate' ) . '\', \'save_check\', \'' . $meta_type . '\');' : '';
    $cb_migrate_do_not_save = !empty( $form_data['slug'] ) ? 'mncfCbSaveEmptyMigrate(jQuery(this), \'' . $form_data['slug'] . '\', \'\', \'' . mn_create_nonce( 'cb_save_empty_migrate' ) . '\', \'do_not_save_check\', \'' . $meta_type . '\');' : '';
    $update_response = !empty( $form_data['slug'] ) ? '<div id="mncf-cb-save-empty-migrate-response-'
            . $form_data['slug'] . '" class="mncf-cb-save-empty-migrate-response"></div>' : '<div class="mncf-cb-save-empty-migrate-response"></div>';
    $form['save_empty'] = array(
        '#title' => __('Save option', 'mncf'),
        '#type' => 'radios',
        '#name' => 'save_empty',
        '#default_value' => !empty( $form_data['data']['save_empty'] ) ? $form_data['data']['save_empty'] : 'no',
        '#options' => array(
            'yes' => array(
                '#title' => __( 'When unchecked, save 0 to the database', 'mncf' ),
                '#value' => 'yes',
                '#attributes' => array('class' => 'mncf-cb-save-empty-migrate', 'onclick' => $cb_migrate_save),
                '#pattern' => '<li><ELEMENT><LABEL></li>',
                '#inline' => true,
            ),
            'no' => array(
                '#title' => __( "When unchecked, don't save anything to the database", 'mncf' ),
                '#value' => 'no',
                '#attributes' => array('class' => 'mncf-cb-save-empty-migrate', 'onclick' => $cb_migrate_do_not_save),
                '#pattern' => '<li><ELEMENT><LABEL></li>',
                '#inline' => true,
            ),
        ),
        '#after' => $update_response,
        '#inline' => true,
        '#before' => '<ul>',
        '#after' => '</ul>',
        '#pattern' => '<tr class="mncf-border-top"><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>',
    );
    $form['options-markup-open'] = array(
        '#title' => __('Checkboxes', 'mncf'),
        '#type' => 'markup',
        '#markup' => '<div id="' . $id . '-sortable" class="mncf-fields-checkboxes-sortable mncf-compare-unique-value-wrapper">',
        '#pattern' => '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER>',
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
            $form_option = mncf_fields_checkboxes_get_option( $parent_name, $option, $form_data );
            $existing_options[array_shift( $form_option )] = $option;
            $form = $form + $form_option;
        }
    } else {
        $form_option = mncf_fields_checkboxes_get_option( $parent_name, array(), $form_data );
        $existing_options[array_shift( $form_option )] = array();
        $form = $form + $form_option;
    }
    $form['options-markup-close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
        '#pattern' => '<ERROR><BEFORE><ELEMENT><AFTER>',
    );

    if ( !empty( $options ) ) {
        $count = count( $options );
    } else {
        $count = 1;
    }

    $form['options-add-option'] = array(
        '#type' => 'markup',
        '#markup' => '<a href="'
        . admin_url( 'admin-ajax.php?action=mncf_ajax&amp;mncf_action=add_checkboxes_option&amp;_mnnonce='
        . mn_create_nonce( 'add_checkboxes_option' ) . '&amp;mncf_ajax_update_add='
        . $id . '-sortable&amp;parent_name=' . urlencode( $parent_name )
        . '&amp;count=' . $count)
        . '" onclick="mncfFieldsFormCountOptions(jQuery(this));"'
        . ' class="button-secondary mncf-ajax-link">'
        . __( 'Add option', 'mncf' ) . '</a>',
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
function mncf_fields_checkboxes_get_option( $parent_name = '', $form_data = array(), $field = array() ) {
    $id = isset( $form_data['key'] ) ? $form_data['key'] : 'mncf-fields-checkboxes-option-' . mncf_unique_id( serialize( $form_data ) . $parent_name );
    $form = array();
    $count = isset( $_GET['count'] ) ? intval( $_GET['count'] ) : 1;
    $title = isset( $_GET['count'] ) ? __( 'Checkbox title', 'mncf' ) . ' ' . intval( $_GET['count'] ) : __( 'Checkbox title', 'mncf' ) . ' 1';
    $title = isset( $form_data['title'] ) ? $form_data['title'] : $title;
    $form[$id . '-id'] = $id;
    $form[$id . '-drag'] = array(
        '#type' => 'markup',
        '#markup' => '<div class="js-types-sortable mncf-fields-checkboxes-draggable"><div class="mncf-checkboxes-drag"><img src="'
        . MNCF_RES_RELPATH
        . '/images/move.png" class="js-types-sort-button mncf-fields-form-checkboxes-move-field" alt="'
        . __( 'Move this option', 'mncf' ) . '" /><img src="'
        . MNCF_RES_RELPATH . '/images/delete.png"'
        . ' class="mncf-fields-checkboxes-delete-option mncf-pointer"'
        . ' onclick="if (confirm(\'' . __( 'Are you sure?', 'mncf' )
        . '\')) { jQuery(this).parent().fadeOut().next().fadeOut(function(){jQuery(this).remove(); '
        . '}); }"'
        . 'alt="' . __( 'Delete this checkbox', 'mncf' ) . '" /></div>',
        '#pattern' => '<ELEMENT>',
    );
    $form[$id] = array(
        '#type' => 'fieldset',
        '#title' => $title,
        '#collapsed' => isset( $form_data['key'] ) ? true : false,
        '#collapsible' => true,
        '#pattern' => '<ELEMENT><dl>',
    );
    $form[$id]['title'] = array(
        '#type' => 'textfield',
        '#title' => __( 'Title', 'mncf' ),
        '#id' => $id . '-title',
        '#name' => $parent_name . '[options][' . $id . '][title]',
        '#value' => $title,
        '#inline' => true,
        '#attributes' => array(
            'class' => 'mncf-form-groups-check-update-title-display-value',
        ),
        '#pattern' => '<dt><LABEL></dt><dd><ERROR><BEFORE><ELEMENT><AFTER></dd>',
    );
    $form[$id]['value'] = array(
        '#type' => 'textfield',
        '#title' => __( 'Value to store', 'mncf' ),
        '#name' => $parent_name . '[options][' . $id . '][set_value]',
        '#value' => isset( $form_data['set_value'] ) ? $form_data['set_value'] : 1,
        '#attributes' => array(
            'data-mncf-type' => 'checkbox',
            'data-required-message-0' => __("This value can't be zero", 'mncf'),
            'data-required-message' => __("Please enter a value", 'mncf')
        ),
        '#pattern' => '<dt><LABEL></dt><dd><ERROR><BEFORE><ELEMENT><AFTER></dd>',
    );
    if ( isset($_GET['page']) && $_GET['page'] == 'mncf-edit' ) {
        $form[$id]['checked'] = array(
            '#id' => 'checkboxes-' . mncf_unique_id( serialize( $form_data ) . $parent_name ),
            '#type' => 'checkbox',
            '#title' => __( 'Set checked by default (on new post)?', 'mncf' ),
            '#name' => $parent_name . '[options][' . $id . '][checked]',
            '#default_value' => !empty( $form_data['checked'] ) ? 1 : 0,
            '#inline' => true,
            '#pattern' => '</dl><p><ELEMENT><LABEL></p>',
        );
    }
    $form[$id]['display'] = array(
        '#type' => 'radios',
        '#default_value' => !empty( $form_data['display'] ) ? $form_data['display'] : 'db',
        '#name' => $parent_name . '[options][' . $id . '][display]',
        '#options' => array(
            'display_from_db' => array(
                '#title' => __( 'Display the value of this field from the database', 'mncf' ),
                '#name' => $parent_name . '[options][' . $id . '][display]',
                '#value' => 'db',
                '#inline' => true,
                '#before' => '<li>',
                '#after' => '</li>',
            ),
            'display_values' => array(
                '#title' => __( 'Show one of these two values:', 'mncf' ),
                '#name' => $parent_name . '[options][' . $id . '][display]',
                '#value' => 'value',
                '#inline' => true,
                '#before' => '<li>',
                '#after' => '</li>',
            ),
        ),
        '#inline' => true,
        '#pattern' => '<LABEL><ul><ELEMENT></ul><dl>',
    );
    $form[$id]['display-value'] = array(
        '#type' => 'textfield',
        '#title' => __( 'Not selected:', 'mncf' ),
        '#name' => $parent_name . '[options][' . $id . '][display_value_not_selected]',
        '#value' => isset( $form_data['display_value_not_selected'] ) ? $form_data['display_value_not_selected'] : '',
        '#inline' => true,
        '#attributes' => array(
            'placeholder' => __('Enter not selected value', 'mncf'),
        ),
        '#pattern' => '<dt><LABEL></dt><dd><ERROR><BEFORE><ELEMENT><AFTER></dd>',
    );
    $form[$id]['display-value-2'] = array(
        '#type' => 'textfield',
        '#title' => __( 'Selected:', 'mncf' ),
        '#name' => $parent_name . '[options][' . $id . '][display_value_selected]',
        '#value' => isset( $form_data['display_value_selected'] ) ? $form_data['display_value_selected'] : '',
        '#attributes' => array(
            'placeholder' => __('Enter selected value', 'mncf'),
        ),
        '#pattern' => '<dt><LABEL></dt><dd><ERROR><BEFORE><ELEMENT><AFTER></dd>',
    );
    $form[$id . 'drag-close'] = array(
        '#type' => 'markup',
        '#markup' => '</dl></div>',
        '#pattern' => '<ELEMENT>',
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
function mncf_fields_checkboxes_get_option_alt_text( $id, $parent_name = '', $form_data = array() ) {
    $form = array();
    $title = sprintf(
        '%s %d',
        __( 'Checkbox title', 'mncf' ),
        intval( isset($_GET['count'])? $_GET['count']: 1)
    );
    $title = isset( $form_data['title'] ) ? $form_data['title'] : $title;
    $value = isset( $_GET['count'] ) ? intval( $_GET['count'] ) : 1;
    $value = isset( $form_data['value'] ) ? $form_data['value'] : $value;
    $value = isset( $form_data['display_value'] ) ? $form_data['display_value'] : $value;
    $form = array(
        '#type' => 'textfield',
        '#id' => $id . '-title-display-value',
        '#name' => $parent_name . '[options][' . $id . '][display_value]',
        '#title' => $title,
        '#value' => $value,
        '#inline' => true,
        '#before' => '<div id="' . $id . '-title-display-value-wrapper">',
        '#after' => '</div>',
    );
    return $form;
}
