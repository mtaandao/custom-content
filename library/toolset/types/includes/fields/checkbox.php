<?php
/**
 * Types-field: Checkbox
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
 * 'state' => 'checked' or 'unchecked' (display the content of the shortcode depending on the state)
 *
 * Example usage:
 * With a short code use [types field="my-checkbox"]
 * In a theme use types_render_field("my-checkbox", $parameters)
 *
 * Link:
 * <a href="http://mn-types.com/documentation/functions/checkbox/">Types checkbox custom field</a>
 * 
 */

/**
 * Form data for group form.
 * 
 * @return type 
 */
function mncf_fields_checkbox_insert_form( $form_data ) {
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
    $form['value'] = array(
        '#type' => 'textfield',
        '#title' => __( 'Value to store', 'mncf' ),
        '#name' => 'set_value',
        '#value' => 1,
        '#attributes' => array(
            'data-mncf-type' => 'checkbox',
            'data-required-message-0' => __("This value can't be zero", 'mncf'),
            'data-required-message' => __("Please enter a value", 'mncf')
        ),
        '#inline' => true,
        '#pattern' => '<tr class="mncf-border-top"><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>',
    );
    $cb_migrate_save = !empty( $form_data['slug'] ) ? "mncfCbSaveEmptyMigrate(jQuery(this), '{$form_data['slug']}', '', '" . mn_create_nonce( 'cb_save_empty_migrate' ) . "', 'save_check', '{$meta_type}');" : '';
    $cb_migrate_do_not_save = !empty( $form_data['slug'] ) ? "mncfCbSaveEmptyMigrate(jQuery(this), '{$form_data['slug']}', '', '" . mn_create_nonce( 'cb_save_empty_migrate' ) . "', 'do_not_save_check', '{$meta_type}');" : '';
    $update_response = !empty( $form_data['slug'] ) ? "<div id='mncf-cb-save-empty-migrate-response-{$form_data['slug']}' class='mncf-cb-save-empty-migrate-response'></div>" : '<div class="mncf-cb-save-empty-migrate-response"></div>';
    $form['save_empty'] = array(
        '#title' => __('Save option', 'mncf'),
        '#type' => 'radios',
        '#name' => 'save_empty',
        '#default_value' => !empty( $form_data['data']['save_empty'] ) ? $form_data['data']['save_empty'] : 'no',
        '#options' => array(
            'yes' => array(
                '#title' => __( 'save 0 to the database', 'mncf' ),
                '#value' => 'yes',
                '#attributes' => array('class' => 'mncf-cb-save-empty-migrate', 'onclick' => $cb_migrate_save),
                '#inline' => true,
                '#before' => '<li>',
                '#after' => '</li>',
            ),
            'no' => array(
                '#title' => __( "don't save anything to the database", 'mncf' ),
                '#value' => 'no',
                '#attributes' => array('class' => 'mncf-cb-save-empty-migrate', 'onclick' => $cb_migrate_do_not_save),
                '#inline' => true,
                '#before' => '<li>',
                '#after' => '</li>',
            ),
        ),
        '#description' => '<strong>' . __( 'When unchecked:', 'mncf' ) . '</strong>',
        '#after' => $update_response,
        '#inline' => true,
        '#before' => '<ul>',
        '#after' => '</ul>',
    );
    if ( isset($_GET['page']) && $_GET['page'] == 'mncf-edit' ) {
        $form['checked'] = array(
            '#type' => 'checkbox',
            '#title' => __( 'Set checked by default (on new post)?', 'mncf' ),
            '#name' => 'checked',
            '#default_value' => !empty( $form_data['data']['checked'] ) ? 1 : 0,
            '#inline' => true,
        );
    }
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
                '#before' => '<li>',
                '#after' => '</li>',
            ),
            'display_values' => array(
                '#title' => __( 'Show one of these two values:', 'mncf' ),
                '#name' => 'display',
                '#value' => 'value',
                '#inline' => true,
                '#before' => '<li>',
                '#after' => '</li>',
            ),
        ),
        '#inline' => true,
        '#before' => '<ul>',
        '#after' => '</ul>',
        '#pattern' => '<tr class="mncf-border-top"><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER>',
    );
    $form['display-open'] = array(
        '#type' => 'markup',
        '#markup' => '<div>',
        '#pattern' => '<ERROR><BEFORE><ELEMENT><AFTER><dl>',
    );
    $form['display-value-1'] = array(
        '#type' => 'textfield',
        '#title' => __( 'Not selected:', 'mncf' ),
        '#name' => 'display_value_not_selected',
        '#value' => '',
        '#inline' => true,
        '#attributes' => array(
            'placeholder' => __('Enter not selected value', 'mncf'),
        ),
        '#inline' => true,
        '#pattern' => '<dt><LABEL></dt><dd><ERROR><BEFORE><ELEMENT><AFTER></dd>',
    );
    $form['display-value-2'] = array(
        '#type' => 'textfield',
        '#title' => __( 'Selected:', 'mncf' ),
        '#name' => 'display_value_selected',
        '#value' => '',
        '#attributes' => array(
            'placeholder' => __('Enter selected value', 'mncf'),
        ),
        '#inline' => true,
        '#pattern' => '<dt><LABEL></dt><dd><ERROR><BEFORE><ELEMENT><AFTER></dd>',
    );
    $form['display-close'] = array(
        '#type' => 'markup',
        '#markup' => '</dl></div>',
        '#pattern' => '<ERROR><BEFORE><ELEMENT><AFTER></td></tr>',
    );
    return $form;
}
