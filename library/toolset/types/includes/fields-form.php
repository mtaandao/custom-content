<?php
/*
 * Fields and groups form functions.
 *
 *
 */
require_once MNCF_EMBEDDED_ABSPATH . '/classes/validate.php';
require_once MNCF_ABSPATH . '/includes/conditional-display.php';

global $mn_version;
$mncf_button_style = '';
$mncf_button_style30 = '';

if ( version_compare( $mn_version, '3.5', '<' ) ) {
    $mncf_button_style = 'style="line-height: 35px;"';
    $mncf_button_style30 = 'style="line-height: 30px;"';
}

/**
 * Generates form data.
 * 
 * @deprecated Possibly deprecated, no usage found in Types. Possibly identical code in Types_Admin_Edit_Fields::get_field_form_data()
 */
function mncf_admin_fields_form()
{
    /**
     * include common functions
     */
    include_once dirname(__FILE__).'/common-functions.php';

    mncf_admin_add_js_settings( 'mncf_nonce_toggle_group',
            '\'' . mn_create_nonce( 'group_form_collapsed' ) . '\'' );
    mncf_admin_add_js_settings( 'mncf_nonce_toggle_fieldset',
            '\'' . mn_create_nonce( 'form_fieldset_toggle' ) . '\'' );
    $default = array();

    global $mncf_button_style;
    global $mncf_button_style30;

    global $mncf;


    $form = array();


    $form['#form']['callback'] = array('mncf_admin_save_fields_groups_submit');

    // Form sidebars

    if ( $current_user_can_edit ) {

        $form['open-sidebar'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="mncf-form-fields-align-right">',
        );
        // Set help icon
        $form['help-icon'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="mncf-admin-fields-help"><img src="' . MNCF_EMBEDDED_TOOLSET_RELPATH
            . '/toolset-common/res/images/question.png" style="position:relative;top:2px;" />&nbsp;<a href="' . Types_Helper_Url::get_url( 'using-post-fields', 'fields-editor', 'fields-help', Types_Helper_Url::UTM_MEDIUM_HELP ) . '" target="_blank">'
            . __( 'Custom fields help', 'mncf' ) . '</a></div>',
            );
        $form['submit2'] = array(
            '#type' => 'submit',
            '#name' => 'save',
            '#value' => __( 'Save', 'mncf' ),
            '#attributes' => array('class' => 'button-primary mncf-disabled-on-submit'),
        );
        $form['fields'] = array(
            '#type' => 'fieldset',
            '#title' => __( 'Available fields', 'mncf' ),
        );

        // Get field types
        $fields_registered = mncf_admin_fields_get_available_types();
        foreach ( $fields_registered as $filename => $data ) {
            $form['fields'][basename( $filename, '.php' )] = array(
                '#type' => 'markup',
                '#markup' => '<a href="' . admin_url( 'admin-ajax.php'
                . '?action=mncf_ajax&amp;mncf_action=fields_insert'
                . '&amp;field=' . basename( $filename, '.php' )
                . '&amp;page=mncf-edit' )
                . '&amp;_mnnonce=' . mn_create_nonce( 'fields_insert' ) . '" '
                . 'class="mncf-fields-add-ajax-link button-secondary">' . $data['title'] . '</a> ',
            );
            // Process JS
            if ( !empty( $data['group_form_js'] ) ) {
                foreach ( $data['group_form_js'] as $handle => $script ) {
                    if ( isset( $script['inline'] ) ) {
                        add_action( 'admin_footer', $script['inline'] );
                        continue;
                    }
                    $deps = !empty( $script['deps'] ) ? $script['deps'] : array();
                    $in_footer = !empty( $script['in_footer'] ) ? $script['in_footer'] : false;
                    mn_register_script( $handle, $script['src'], $deps,
                        MNCF_VERSION, $in_footer );
                    mn_enqueue_script( $handle );
                }
            }

            // Process CSS
            if ( !empty( $data['group_form_css'] ) ) {
                foreach ( $data['group_form_css'] as $handle => $script ) {
                    if ( isset( $script['src'] ) ) {
                        $deps = !empty( $script['deps'] ) ? $script['deps'] : array();
                        mn_enqueue_style( $handle, $script['src'], $deps,
                            MNCF_VERSION );
                    } else if ( isset( $script['inline'] ) ) {
                        add_action( 'admin_head', $script['inline'] );
                    }
                }
            }
        }

        // Get fields created by user
        $fields = mncf_admin_fields_get_fields( true, true );
        if ( !empty( $fields ) ) {
            $form['fields-existing'] = array(
                '#type' => 'fieldset',
                '#title' => __( 'User created fields', 'mncf' ),
                '#id' => 'mncf-form-groups-user-fields',
            );
            foreach ( $fields as $key => $field ) {
                if ( isset( $update['fields'] ) && array_key_exists( $key,
                    $update['fields'] ) ) {
                        continue;
                    }
                if ( !empty( $field['data']['removed_from_history'] ) ) {
                    continue;
                }
                $form['fields-existing'][$key] = array(
                    '#type' => 'markup',
                    '#markup' => '<div id="mncf-user-created-fields-wrapper-' . $field['id'] . '" style="float:left; margin-right: 10px;"><a href="' . admin_url( 'admin-ajax.php'
                    . '?action=mncf_ajax'
                    . '&amp;mncf_action=fields_insert_existing'
                    . '&amp;page=mncf-edit'
                    . '&amp;field=' . $field['id'] ) . '&amp;_mnnonce='
                    . mn_create_nonce( 'fields_insert_existing' ) . '" '
                    . 'class="mncf-fields-add-ajax-link button-secondary" onclick="jQuery(this).parent().fadeOut();" '
                    . ' data-slug="' . $field['id'] . '">'
                    . htmlspecialchars( stripslashes( $field['name'] ) ) . '</a>'
                    . '<a href="' . admin_url( 'admin-ajax.php'
                    . '?action=mncf_ajax'
                    . '&amp;mncf_action=remove_from_history'
                    . '&amp;field_id=' . $field['id'] ) . '&amp;_mnnonce='
                    . mn_create_nonce( 'remove_from_history' ) . '&amp;mncf_warning='
                    . sprintf( __( 'Are you sure that you want to remove field %s from history?', 'mncf' ),
                    htmlspecialchars( stripslashes( $field['name'] ) ) )
                    . '&amp;mncf_ajax_update=mncf-user-created-fields-wrapper-'
                    . $field['id'] . '" title="'
                    . sprintf( __( 'Remove field %s', 'mncf' ),
                        htmlspecialchars( stripslashes( $field['name'] ) ) )
                        . '" class="mncf-ajax-link"><img src="'
                        . MNCF_RES_RELPATH
                        . '/images/delete-2.png" style="postion:absolute;margin-top:5px;margin-left:-4px;" /></a></div>',
                    );
            }
        }
        $form['close-sidebar'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
        );

    }
    // Group data

    $form['open-main'] = array(
        '#type' => 'markup',
        '#markup' => '<div id="mncf-form-fields-main" class="mncf-form-fields-main">',
    );


    /**
     * Now starting form
     */

    /** End admin Styles * */
    // Group fields

    $form['fields_title'] = array(
        '#type' => 'markup',
        '#markup' => '<h2>' . __( 'Fields', 'mncf' ) . '</h2>',
    );
    $show_under_title = true;

    $form['ajax-response-open'] = array(
        '#type' => 'markup',
        '#markup' => '<div id="mncf-fields-sortable" class="ui-sortable">',
    );

    // If it's update, display existing fields
    $existing_fields = array();
    if ( $update && isset( $update['fields'] ) ) {
        foreach ( $update['fields'] as $slug => $field ) {
            $field['submitted_key'] = $slug;
            $field['group_id'] = $update['id'];
            $form_field = mncf_fields_get_field_form_data( $field['type'],
                    $field );
            if ( is_array( $form_field ) ) {
                $form['draggable-open-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '<div class="ui-draggable">'
                );
                $form = $form + $form_field;
                $form['draggable-close-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '</div>'
                );
            }
            $existing_fields[] = $slug;
            $show_under_title = false;
        }
    }
    // Any new fields submitted but failed? (Don't double it)
    if ( !empty( $_POST['mncf']['fields'] ) ) {
        foreach ( $_POST['mncf']['fields'] as $key => $field ) {
            if ( in_array( $key, $existing_fields ) ) {
                continue;
            }
            $field['submitted_key'] = $key;
            $form_field = mncf_fields_get_field_form_data( $field['type'],
                    $field );
            if ( is_array( $form_field ) ) {
                $form['draggable-open-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '<div class="ui-draggable">'
                );
                $form = $form + $form_field;
                $form['draggable-close-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '</div>'
                );
            }
        }
        $show_under_title = false;
    }
    $form['ajax-response-close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>' . '<div id="mncf-ajax-response"></div>',
    );

    if ( $show_under_title ) {
        $form['fields_title']['#markup'] = $form['fields_title']['#markup']
                . '<div id="mncf-fields-under-title">'
                . __( 'There are no fields in this group. To add a field, click on the field buttons at the right.', 'mncf' )
                . '</div>';
    }

    // If update, create ID field
    if ( $update ) {
        $form['group_id'] = array(
            '#type' => 'hidden',
            '#name' => 'group_id',
            '#value' => $update['id'],
            '#forced_value' => true,
        );
    }

    $form['submit'] = array(
        '#type' => 'submit',
        '#name' => 'save',
        '#value' => __( 'Save', 'mncf' ),
        '#attributes' => array('class' => 'button-primary mncf-disabled-on-submit'),
    );

    // Close main div
    $form['close-sidebar'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
    );

    $form = apply_filters( 'mncf_form_fields', $form, $update );

    // Add JS settings
    mncf_admin_add_js_settings( 'mncfFormUniqueValuesCheckText',
            '\'' . __( 'Warning: same values selected', 'mncf' ) . '\'' );
    mncf_admin_add_js_settings( 'mncfFormUniqueNamesCheckText',
            '\'' . __( 'Warning: field name already used', 'mncf' ) . '\'' );
    mncf_admin_add_js_settings( 'mncfFormUniqueSlugsCheckText',
            '\'' . __( 'Warning: field slug already used', 'mncf' ) . '\'' );

    mncf_admin_add_js_settings( 'mncfFormAlertOnlyPreview', sprintf( "'%s'", __( 'Sorry, but this is only preview!', 'mncf' ) ) );

    $form['form-close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
    );

    /**
     * return form if current_user_can edit
     */
    if ( $current_user_can_edit) {
        return $form;
    }

    return mncf_admin_common_only_show($form);
}

/**
 * Dynamically adds new field on AJAX call.
 *
 * @param type $form_data
 */
function mncf_fields_insert_ajax( $form_data = array() ) {
    echo mncf_fields_get_field_form( sanitize_text_field( $_GET['field'] ) );
}

/**
 * Dynamically adds existing field on AJAX call.
 *
 * @param type $form_data
 */
function mncf_fields_insert_existing_ajax() {
    $field = mncf_admin_fields_get_field( sanitize_text_field( $_GET['field'] ), false, true );
    if ( !empty( $field ) ) {
        echo mncf_fields_get_field_form( $field['type'], $field );
    } else {
        echo '<div>' . __( "Requested field don't exist", 'mncf' ) . '</div>';
    }
}

/**
 * Returns HTML formatted field form (draggable).
 *
 * @param type $type
 * @param type $form_data
 * @return type
 */
function mncf_fields_get_field_form( $type, $form_data = array() ) {
    $form = mncf_fields_get_field_form_data( $type, $form_data );
    if ( $form ) {
        $return = '<div class="ui-draggable">'
                . mncf_form_simple( $form )
                . '</div>';

        /**
         * add extra condition check if this is checkbox
         */
        foreach( $form as $key => $value ) {
            if (
                !array_key_exists('value', $value )
                || !array_key_exists('#attributes', $value['value'] )
                || !array_key_exists('data-mncf-type', $value['value']['#attributes'] )
                || 'checkbox' != $value['value']['#attributes']['data-mncf-type']
            ) {
                continue;
            }
            echo '<script type="text/javascript">';
            printf('jQuery(document).ready(function($){mncf_checkbox_value_zero(jQuery(\'[name="%s"]\'));});', $value['value']['#name'] );
            echo '</script>';
        }

        return $return;
    }
    return '<div>' . __( 'Wrong field requested', 'mncf' ) . '</div>';
}

/**
 * Processes field form data.
 *
 * @param type $type
 * @param type $form_data
 * @return type
 */
function mncf_fields_get_field_form_data( $type, $form_data = array() ) {

    // Get field type data
    $field_data = mncf_fields_type_action( $type );

    if ( !empty( $field_data ) ) {
        $form = array();

        // Set right ID if existing field
        if ( isset( $form_data['submitted_key'] ) ) {
            $id = $form_data['submitted_key'];
        } else {
            $id = $type . '-' . rand();
        }

        // Sanitize
        $form_data = mncf_sanitize_field( $form_data );

        // Set remove link
        $remove_link = isset( $form_data['group_id'] ) ? admin_url( 'admin-ajax.php?'
                        . 'mncf_ajax_callback=mncfFieldsFormDeleteElement&amp;mncf_warning='
                        . __( 'Are you sure?', 'mncf' )
                        . '&amp;action=mncf_ajax&amp;mncf_action=remove_field_from_group'
                        . '&amp;group_id=' . intval( $form_data['group_id'] )
                        . '&amp;field_id=' . $form_data['id'] )
                . '&amp;_mnnonce=' . mn_create_nonce( 'remove_field_from_group' ) : admin_url( 'admin-ajax.php?'
                        . 'mncf_ajax_callback=mncfFieldsFormDeleteElement&amp;mncf_warning='
                        . __( 'Are you sure?', 'mncf' )
                        . '&amp;action=mncf_ajax&amp;mncf_action=remove_field_from_group' )
                . '&amp;_mnnonce=' . mn_create_nonce( 'remove_field_from_group' );

        /**
         * Set move button
         */
        $form['mncf-' . $id . '-control'] = array(
            '#type' => 'markup',
            '#markup' => '<img src="' . MNCF_RES_RELPATH
            . '/images/move.png" class="mncf-fields-form-move-field" alt="'
            . __( 'Move this field', 'mncf' ) . '" /><a href="'
            . $remove_link . '" '
            . 'class="mncf-form-fields-delete mncf-ajax-link">'
            . '<img src="' . MNCF_RES_RELPATH . '/images/delete-2.png" alt="'
            . __( 'Delete this field', 'mncf' ) . '" /></a>',
        );

        // Set fieldset

        $collapsed = mncf_admin_fields_form_fieldset_is_collapsed( 'fieldset-' . $id );
        // Set collapsed on AJAX call (insert)
        $collapsed = defined( 'DOING_AJAX' ) ? false : $collapsed;

        // Set title
        $title = !empty( $form_data['name'] ) ? $form_data['name'] : __( 'Untitled', 'mncf' );
        $title = '<span class="mncf-legend-update">' . $title . '</span> - '
                . sprintf( __( '%s field', 'mncf' ), $field_data['title'] );

        // Do not display on Usermeta Group edit screen
        if ( !isset( $_GET['page'] ) || $_GET['page'] != 'mncf-edit-usermeta' ) {
            if ( !empty( $form_data['data']['conditional_display']['conditions'] ) ) {
                $title .= ' ' . __( '(conditional)', 'mncf' );
            }
        }

        $form['mncf-' . $id] = array(
            '#type' => 'fieldset',
            '#title' => $title,
            '#id' => 'fieldset-' . $id,
            '#collapsible' => true,
            '#collapsed' => $collapsed,
            '#attributes' => array(
                'class' => 'js-mncf-slugize-container',
            ),
        );

        // Get init data
        $field_init_data = mncf_fields_type_action( $type );

        // See if field inherits some other
        $inherited_field_data = false;
        if ( isset( $field_init_data['inherited_field_type'] ) ) {
            $inherited_field_data = mncf_fields_type_action( $field_init_data['inherited_field_type'] );
        }

        $form_field = array();

        // Force name and description
        $form_field['name'] = array(
            '#type' => 'textfield',
            '#name' => 'name',
            '#attributes' => array(
                'class' => 'mncf-forms-set-legend mncf-forms-field-name js-mncf-slugize-source',
                'style' => 'width:100%;margin:10px 0 10px 0;',
                'placeholder' => __( 'Enter field name', 'mncf' ),
            ),
            '#validate' => array('required' => array('value' => true)),
            '#inline' => true,
        );
        $form_field['slug'] = array(
            '#type' => 'textfield',
            '#name' => 'slug',
            '#attributes' => array(
                'class' => 'mncf-forms-field-slug js-mncf-slugize',
                'style' => 'width:100%;margin:0 0 10px 0;',
                'maxlength' => 255,
                'placeholder' => __( 'Enter field slug', 'mncf' ),
            ),
            '#validate' => array('nospecialchars' => array('value' => true)),
            '#inline' => true,
        );

        // If insert form callback is not provided, use generic form data
        if ( function_exists( 'mncf_fields_' . $type . '_insert_form' ) ) {
            $form_field_temp = call_user_func( 'mncf_fields_' . $type
                    . '_insert_form', $form_data,
                    'mncf[fields]['
                    . $id . ']' );
            if ( is_array( $form_field_temp ) ) {
                unset( $form_field_temp['name'], $form_field_temp['slug'] );
                $form_field = $form_field + $form_field_temp;
            }
        }

        $form_field['description'] = array(
            '#type' => 'textarea',
            '#name' => 'description',
            '#attributes' => array(
                'rows' => 5,
                'cols' => 1,
                'style' => 'margin:0 0 10px 0;',
                'placeholder' => __( 'Describe this field', 'mncf' ),
            ),
            '#inline' => true,
        );

        /**
         * add placeholder field
         */
            switch($type)
            {
            case 'audio':
            case 'colorpicker':
            case 'date':
            case 'email':
            case 'embed':
            case 'file':
            case 'image':
            case 'numeric':
            case 'phone':
            case 'skype':
            case 'textarea':
            case 'textfield':
            case 'url':
            case 'video':
                $form_field['placeholder'] = array(
                    '#type' => 'textfield',
                    '#name' => 'placeholder',
                    '#inline' => true,
                    '#title' => __( 'Placeholder', 'mncf' ),
                    '#attributes' => array(
                        'style' => 'width:100%;margin:0 0 10px 0;',
                        'placeholder' =>  __('Enter placeholder', 'mncf'),
                    ),
                );
                break;
            }

        /**
         * add default value
         */
            switch($type)
            {
            case 'audio':
            case 'email':
            case 'embed':
            case 'file':
            case 'image':
            case 'numeric':
            case 'phone':
            case 'textfield':
            case 'url':
            case 'video':
                $form_field['user_default_value'] = array(
                    '#type' => 'textfield',
                    '#name' => 'user_default_value',
                    '#inline' => true,
                    '#title' => __( 'Default Value', 'mncf' ),
                    '#attributes' => array(
                        'style' => 'width:100%;margin:0 0 10px 0;',
                        'placeholder' =>  __('Enter default value', 'mncf'),
                    ),
                );
                break;
            case 'textarea':
            case 'wysiwyg':
                $form_field['user_default_value'] = array(
                    '#type' => 'textarea',
                    '#name' => 'user_default_value',
                    '#inline' => true,
                    '#title' => __( 'Default Value', 'mncf' ),
                    '#attributes' => array(
                        'style' => 'width:100%;margin:0 0 10px 0;',
                        'placeholder' =>  __('Enter default value', 'mncf'),
                    ),
                );
                break;
            }
            switch($type)
            {
            case 'audio':
            case 'file':
            case 'image':
            case 'embed':
            case 'url':
            case 'video':
                $form_field['user_default_value']['#validate'] = array('url'=>array());
                break;
            case 'email':
                $form_field['user_default_value']['#validate'] = array('email'=>array());
                break;
            case 'numeric':
                $form_field['user_default_value']['#validate'] = array('number'=>array());
                break;
            }

        if ( mncf_admin_can_be_repetitive( $type ) ) {

	        // We need to set the "repetitive" setting to a string '0' or '1', not numbers, because it will be used
	        // again later in this method (which I'm not going to refactor now) and because the form renderer
	        // is oversensitive.
	        $is_repetitive_as_string = ( 1 == mncf_getnest( $form_data, array( 'data', 'repetitive' ), '0' ) ) ? '1' : '0';
	        if( !array_key_exists( 'data', $form_data ) || !is_array( $form_data['data'] ) ) {
		        $form_data['data'] = array();
	        }
	        $form_data['data']['repetitive'] = $is_repetitive_as_string;

            $temp_warning_message = '';
            $form_field['repetitive'] = array(
                '#type' => 'radios',
                '#name' => 'repetitive',
                '#title' => __( 'Single or repeating field?', 'mncf' ),
                '#options' => array(
                    'repeat' => array(
                        '#title' => __( 'Allow multiple-instances of this field', 'mncf' ),
                        '#value' => '1',
                        '#attributes' => array('onclick' => 'jQuery(this).parent().parent().find(\'.mncf-cd-warning\').hide(); jQuery(this).parent().find(\'.mncf-cd-repetitive-warning\').show();'),
                    ),
                    'norepeat' => array(
                        '#title' => __( 'This field can have only one value', 'mncf' ),
                        '#value' => '0',
                        '#attributes' => array('onclick' => 'jQuery(this).parent().parent().find(\'.mncf-cd-warning\').show(); jQuery(this).parent().find(\'.mncf-cd-repetitive-warning\').hide();'),
                    ),
                ),
                '#default_value' => $is_repetitive_as_string,
                '#after' => mncf_admin_is_repetitive( $form_data ) ? '<div class="mncf-message mncf-cd-warning mncf-error" style="display:none;"><p>' . __( "There may be multiple instances of this field already. When you switch back to single-field mode, all values of this field will be updated when it's edited.", 'mncf' ) . '</p></div>' . $temp_warning_message : $temp_warning_message,
            );
        }

        // Process all form fields
        foreach ( $form_field as $k => $field ) {
            $form['mncf-' . $id][$k] = $field;
            // Check if nested
            if ( isset( $field['#name'] ) && strpos( $field['#name'], '[' ) === false ) {
                $form['mncf-' . $id][$k]['#name'] = 'mncf[fields]['
                        . $id . '][' . $field['#name'] . ']';
            } else if ( isset( $field['#name'] ) ) {
                $form['mncf-' . $id][$k]['#name'] = 'mncf[fields]['
                        . $id . ']' . $field['#name'];
            }
            if ( !isset( $field['#id'] ) ) {
                $form['mncf-' . $id][$k]['#id'] = $type . '-'
                        . $field['#type'] . '-' . rand();
            }
            if ( isset( $field['#name'] ) && isset( $form_data[$field['#name']] ) ) {
                $form['mncf-'
                        . $id][$k]['#value'] = $form_data[$field['#name']];
                $form['mncf-'
                        . $id][$k]['#default_value'] = $form_data[$field['#name']];
                // Check if it's in 'data'
            } else if ( isset( $field['#name'] ) && isset( $form_data['data'][$field['#name']] ) ) {
                $form['mncf-'
                        . $id][$k]['#value'] = $form_data['data'][$field['#name']];
                $form['mncf-'
                        . $id][$k]['#default_value'] = $form_data['data'][$field['#name']];
            }
        }

        // Set type
        $form['mncf-' . $id]['type'] = array(
            '#type' => 'hidden',
            '#name' => 'mncf[fields][' . $id . '][type]',
            '#value' => $type,
            '#id' => $id . '-type',
        );

        // Add validation box
        $form_validate = mncf_admin_fields_form_validation( 'mncf[fields]['
                . $id . '][validate]', call_user_func( 'mncf_fields_' . $type ),
                $form_data );
        foreach ( $form_validate as $k => $v ) {
            $form['mncf-' . $id][$k] = $v;
        }

        /**
         * MNML Translation Preferences
         *
         * only for post meta
         *
         */
        if (
            isset($form_data['meta_type'])
            && 'postmeta' == $form_data['meta_type']
            && function_exists( 'mnml_cf_translation_preferences' )
        ) {
            $custom_field = !empty( $form_data['slug'] ) ? mncf_types_get_meta_prefix( $form_data ) . $form_data['slug'] : false;
            $suppress_errors = $custom_field == false ? true : false;
            $translatable = array('textfield', 'textarea', 'wysiwyg');
            $action = in_array( $type, $translatable ) ? 'translate' : 'copy';
            $form['mncf-' . $id]['mnml-preferences'] = array(
                '#type' => 'fieldset',
                '#title' => __( 'Translation preferences', 'mncf' ),
                '#collapsed' => true,
            );
            $mnml_prefs = mnml_cf_translation_preferences( $id,
                        $custom_field, 'mncf', false, $action, false,
                        $suppress_errors );
            $mnml_prefs = str_replace('<span style="color:#FF0000;">', '<span class="mncf-form-error">', $mnml_prefs);
            $form['mncf-' . $id]['mnml-preferences']['form'] = array(
                '#type' => 'markup',
                '#markup' => $mnml_prefs,
            );
        }

        if ( empty( $form_data ) || isset( $form_data['is_new'] ) ) {
            $form['mncf-' . $id]['is_new'] = array(
                '#type' => 'hidden',
                '#name' => 'mncf[fields][' . $id . '][is_new]',
                '#value' => '1',
                '#attributes' => array(
                    'class' => 'mncf-is-new',
                ),
            );
        }
        $form_data['id'] = $id;
        $form['mncf-' . $id] = apply_filters( 'mncf_form_field',
                $form['mncf-' . $id], $form_data );
        return $form;
    }
    return false;
}

/**
 * Adds validation box.
 *
 * @param type $name
 * @param string $field
 * @param type $form_data
 * @return type
 */
function mncf_admin_fields_form_validation( $name, $field, $form_data = array() ) {
    $form = array();

    if ( isset( $field['validate'] ) ) {

        $form['validate-table-open'] = array(
            '#type' => 'markup',
            '#markup' => '<table class="mncf-fields-field-value-options" '
            . 'cellspacing="0" cellpadding="0"><thead><tr><td>'
            . __( 'Validation', 'mncf' ) . '</td><td>' . __( 'Error message', 'mncf' )
            . '</td></tr></thead><tbody>',
        );

        // Process methods
        foreach ( $field['validate'] as $k => $method ) {

            // Set additional method data
            if ( is_array( $method ) ) {
                $form_data['data']['validate'][$k]['method_data'] = $method;
                $method = $k;
            }

            if ( !Wpcf_Validate::canValidate( $method )
                    || !Wpcf_Validate::hasForm( $method ) ) {
                continue;
            }

            $form['validate-tr-' . $method] = array(
                '#type' => 'markup',
                '#markup' => '<tr><td>',
            );

            // Get method form data
            if ( Wpcf_Validate::canValidate( $method )
                    && Wpcf_Validate::hasForm( $method ) ) {

                $field['#name'] = $name . '[' . $method . ']';
                $form_validate = call_user_func_array(
                        array('Wpcf_Validate', $method . '_form'),
                        array(
                    $field,
                    isset( $form_data['data']['validate'][$method] ) ? $form_data['data']['validate'][$method] : array()
                        )
                );

                // Set unique IDs
                foreach ( $form_validate as $key => $element ) {
                    if ( isset( $element['#type'] ) ) {
                        $form_validate[$key]['#id'] = $element['#type'] . '-'
                                . mncf_unique_id( serialize( $element ) );
                    }
                    if ( isset( $element['#name'] ) && strpos( $element['#name'],
                                    '[message]' ) !== FALSE ) {
                        $before = '</td><td>';
                        $after = '</td></tr>';
                        $form_validate[$key]['#before'] = isset( $element['#before'] ) ? $element['#before'] . $before : $before;
                        $form_validate[$key]['#after'] = isset( $element['#after'] ) ? $element['#after'] . $after : $after;
                    }
                }

                // Join
                $form = $form + $form_validate;
            }
        }
        $form['validate-table-close'] = array(
            '#type' => 'markup',
            '#markup' => '</tbody></table>',
        );
    }

    return $form;
}

/**
 * Adds JS validation script.
 */
function mncf_admin_fields_form_js_validation() {
    mncf_form_render_js_validation();
}

/**
 * Saves open fieldsets.
 *
 * @param type $action
 * @param type $fieldset
 * @param type $group_id
 */
function mncf_admin_fields_form_save_open_fieldset( $action, $fieldset,
        $group_id = false ) {
    $data = get_user_meta( get_current_user_id(), 'mncf-group-form-toggle', true );
    if ( $group_id && $action == 'open' ) {
        $data[intval( $group_id )][$fieldset] = 1;
    } else if ( $group_id && $action == 'close' ) {
        $group_id = intval( $group_id );
        if ( isset( $data[$group_id][$fieldset] ) ) {
            unset( $data[$group_id][$fieldset] );
        }
    } else if ( $action == 'open' ) {
        $data[-1][$fieldset] = 1;
    } else if ( $action == 'close' ) {
        if ( isset( $data[-1][$fieldset] ) ) {
            unset( $data[-1][$fieldset] );
        }
    }
    update_user_meta( get_current_user_id(), 'mncf-group-form-toggle', $data );
}

/**
 * Saves open fieldsets.
 *
 * @param type $action
 * @param type $fieldset
 * @param type $group_id
 */
function mncf_admin_fields_form_fieldset_is_collapsed( $fieldset ) {
    if ( isset( $_REQUEST['group_id'] ) ) {
        $group_id = intval( $_REQUEST['group_id'] );
    } else {
        $group_id = -1;
    }
    $data = get_user_meta( get_current_user_id(), 'mncf-group-form-toggle', true );
    if ( !isset( $data[$group_id] ) ) {
        return true;
    }
    return array_key_exists( $fieldset, $data[$group_id] ) ? false : true;
}

/**
 * Adds 'Edit' and 'Cancel' buttons, expandable div.
 *
 * @todo REMOVE THIS - Since Types 1.2 we do not need it
 *
 * @param type $id
 * @param type $element
 * @param type $title
 * @param type $list
 * @param type $empty_txt
 * @return string
 */
function mncf_admin_fields_form_nested_elements( $id, $element, $title, $list,
        $empty_txt ) {
    global $mncf_button_style;
    global $mncf_button_style30;
    $form = array();
    $form = $element;
    $id = strtolower( strval( $id ) );

    $form['#before'] = '<span id="mncf-group-form-update-' . $id . '-ajax-response"'
            . ' style="font-style:italic;font-weight:bold;display:inline-block;">'
            . esc_html( $title ) . ' ' . $list . '</span>'
            . '&nbsp;&nbsp;<a href="javascript:void(0);" ' . $mncf_button_style30 . ' '
            . ' class="button-secondary" onclick="'
            . 'window.mncf' . ucfirst( $id ) . 'Text = new Array(); window.mncfFormGroups' . ucfirst( $id ) . 'State = new Array(); '
            . 'jQuery(this).next().slideToggle()'
            . '.find(\'.checkbox\').each(function(index){'
            . 'if (jQuery(this).is(\':checked\')) { '
            . 'window.mncf' . ucfirst( $id ) . 'Text.push(jQuery(this).next().html()); '
            . 'window.mncfFormGroups' . ucfirst( $id ) . 'State.push(jQuery(this).attr(\'id\'));'
            . '}'
            . '});'
            . ' jQuery(this).css(\'visibility\', \'hidden\');">'
            . __( 'Edit', 'mncf' ) . '</a>' . '<div class="hidden" id="mncf-form-fields-' . $id . '">';

    $form['#after'] = '<a href="javascript:void(0);" ' . $mncf_button_style . ' '
            . 'class="button-primary mncf-groups-form-ajax-update-' . $id . '-ok"'
            . ' onclick="">'
            . __( 'OK', 'mncf' ) . '</a>&nbsp;'
            . '<a href="javascript:void(0);" ' . $mncf_button_style . ' '
            . 'class="button-secondary mncf-groups-form-ajax-update-' . $id . '-cancel"'
            . ' onclick="">'
            . __( 'Cancel', 'mncf' ) . '</a>' . '</div></div>';

    return $form;
}

/*
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 * From here add revised code
 */

/**
 *
 * Use this to show filter item
 *
 * @since Types 1.2
 * @global type $mncf_button_style
 * @global type $mncf_button_style30
 * @param type $id
 * @param type $txt
 * @param type $txt_empty
 * @param type $e
 * @return string
 */
function _mncf_filter_wrap( $id, $title, $txt, $txt_empty, $e, $edit_button = '' ) {

    global $mncf_button_style;
    global $mncf_button_style30;

    $form = array();
    $unique_id = mncf_unique_id( serialize( func_get_args() ) );
    $query = 'jQuery(this), \'' . esc_js( $id ) . '\', \'' . esc_js( $title )
        . '\', \'' . esc_js( $txt ) . '\', \'' . esc_js( $txt_empty ) . '\'';

    $group = array(
        'id' => isset($_REQUEST['group_id'])? intval($_REQUEST['group_id']):0,
    );

    $current_user_can_edit = MNCF_Roles::user_can_edit('custom-field', $group);

    if ( empty( $edit_button ) ) {
        $edit = __( 'View', 'mncf' );
        if ( $current_user_can_edit ) {
            $edit = __( 'Edit', 'mncf' );
        }
    } else {
        $edit = $edit_button;
    }
    /*
     *
     * Title and Edit button
     */
    $form['filter_' . $unique_id . '_wrapper'] = array(
        '#type' => 'markup',
        '#markup' => '<span class="mncf-filter-ajax-response"'
        . ' style="font-style:italic;font-weight:bold;display:inline-block;">'
        . $title . ' ' . $txt . '</span>'
        . '&nbsp;&nbsp;<a href="javascript:void(0);" ' . $mncf_button_style30 . ' '
        . ' class="button-secondary mncf-form-filter-edit" onclick="mncfFilterEditClick('
        . $query . ');">'
        . $edit . '</a><div class="hidden" id="mncf-form-fields-' . $id . '">',
    );

    /**
     * Form element as param
     * It may be single element or array of elements
     * Simply check if array has #type - indicates it is a form item
     */
    if ( isset( $e['#type'] ) ) {
        $form['filter_' . $unique_id . '_items'] = $e;
    } else {
        /*
         * If array of elements just join
         */
        $form = $form + (array) $e;
    }

    /**
     * OK button
     */
    if ( $current_user_can_edit ) {
        $form['filter_' . $unique_id . '_ok'] = array(
            '#type' => 'markup',
            '#markup' => '<a href="javascript:void(0);" ' . $mncf_button_style . ' '
            . 'class="button-primary  mncf-form-filter-ok mncf-groups-form-ajax-update-'
            . $id . '-ok"'
            . ' onclick="mncfFilterOkClick('
            . $query . ');">'
            . __( 'OK', 'mncf' ) . '</a>&nbsp;',
            );
    }

    /**
     * Cancel button
     */
    $button_cancel_text = __( 'Close', 'mncf' );
    if ( $current_user_can_edit ) {
        $button_cancel_text = __( 'Cancel', 'mncf' );
    }
    $form['filter_' . $unique_id . '_cancel'] = array(
        '#type' => 'markup',
        '#markup' => sprintf(
            '<a href="javascript:void(0);" %s class="button-secondary mncf-form-filter-cancel mncf-groups-form-ajax-update-%s-cancel" onclick="mncfFilterCancelClick(%s);">%s</a>',
            $mncf_button_style,
            $id,
            $query,
            $button_cancel_text
        ),
    );

    /**
     * Close wrapper
     */
    $form['filter_' . $unique_id . 'wrapper_close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
    );

    return $form;
}
