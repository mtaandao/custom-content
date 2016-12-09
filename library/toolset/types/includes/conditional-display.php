<?php
/*
 * Conditional display code.
 */
require_once MNCF_EMBEDDED_ABSPATH . '/includes/conditional-display.php';

add_filter( 'mncf_form_field', 'mncf_cd_form_field_filter', 10, 2 );
add_filter( 'mncf_field_pre_save', 'mncf_cd_field_pre_save_filter' );
add_filter( 'mncf_group_pre_save', 'mncf_cd_group_pre_save_filter' );
add_filter( 'mncf_fields_form_additional_filters', 'mncf_cd_fields_form_additional_filters', 10, 2 );
add_action( 'mncf_save_group', 'mncf_cd_save_group_action' );

/**
 * Filters group field form.
 *
 * @param type $form
 * @param type $data
 * @return type
 */
function mncf_cd_form_field_filter( $form, $data )
{
    if ( defined( 'DOING_AJAX' ) && isset( $_SERVER['HTTP_REFERER'] ) ) {
        parse_str( $_SERVER['HTTP_REFERER'], $vars );
    } else if ( isset( $_GET['group_id'] ) ) {
        $vars = array();
        $vars['group_id'] = sanitize_text_field( $_GET['group_id'] );
    }
    /**
     * no group_id
     * new group or new field
     */
    if (!isset( $data['group_id'] ) ) {
        return $form + array(
            'cd_not_available' => array(
                '#type' => 'notice',
                '#title' => __( 'Conditional display', 'mncf' ),
                '#markup' => __( 'You will be able to set conditional field display once this group is saved.', 'mncf' ),
                '#pattern' => '<tr class="mncf-border-top"><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER>',
            ),
        );
    }
    /**
     * Sanitize form
     */
    if ( !is_array($form) ) {
        $form = array();
    }
    $form = $form + mncf_cd_admin_form_filter( $form, $data );
    return $form;
}

/**
 * Group pre-save filter.
 *
 * @param array $data
 * @return array
 */
function mncf_cd_group_pre_save_filter( $data ) {
    return mncf_cd_field_pre_save_filter( $data );
}

/**
 * Field pre-save filter.
 *
 * @param array $data
 * @return array
 */
function mncf_cd_field_pre_save_filter( $data ) {
    if ( empty( $data['conditional_display'] ) ) {
        $data['conditional_display'] = array();
    } else if ( !empty( $data['conditional_display']['conditions'] ) ) {
        foreach ( $data['conditional_display']['conditions'] as $k => $condition ) {
            if ( !array_key_exists( 'field', $condition ) ) {
                continue;
            }
            $field = mncf_admin_fields_get_field( $condition['field'] );
            if ( !empty( $field ) ) {
                // Date conversions
                if ( $field['type'] == 'date'
                        && isset( $condition['date'] )
                        && isset( $condition['month'] )
                        && isset( $condition['year'] )
                ) {
                    $time = adodb_mktime( 0, 0, 0, $condition['month'], $condition['date'], $condition['year'] );
                    if ( mncf_fields_date_timestamp_is_valid( $time ) ) {
                        $condition['value'] = $time;
                    }
                }
                if ( isset( $condition['date'] ) && isset( $condition['month'] ) && isset( $condition['year'] )
                ) {
                    unset( $condition['date'], $condition['month'], $condition['year'] );
                }
                $data['conditional_display']['conditions'][$k] = $condition;
            }
        }
    }
    return $data;
}

/**
 * Conditional display form.
 *
 * @param array $form form data
 * @param array $data
 * @param bool|mixed $group
 *
 * @return array
 */
function mncf_cd_admin_form_filter( $form, $data, $group = false )
{
    $meta_type = isset($data['meta_type'])? $data['meta_type']:'postmeta';

    if ( $group ) {
        $name = 'mncf[group][conditional_display]';
    } else {
        $name = 'mncf[fields][' . $data['id'] . '][conditional_display]';
    }

    // Count
    if ( !empty( $data['data']['conditional_display']['conditions'] ) ) {
        $conditions = $data['data']['conditional_display']['conditions'];
        $count = count( $conditions );
        $_count_txt = $count;
    } else {
        $_count_txt = '';
        $count = 1;
    }

    /**
     * state of conditional display custom use
     */
    $use_custom_logic = false;
    if (
        true
        && array_key_exists( 'data', $data )
        && is_array( $data['data'] )
        && array_key_exists( 'conditional_display', $data['data'] )
        && is_array( $data['data']['conditional_display'] )
        && array_key_exists( 'custom_use', $data['data']['conditional_display'] )
        && !empty( $data['data']['conditional_display']['custom_use'] )
    ) {
        $use_custom_logic = true;
    }

    if ( $group ) {
        $form['group-open'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="mncf-filter-container js-mncf-filter-container">',
        );
    }

    /**
     * custom use
     */
    $form['cd-custom_use'] = array(
        '#type' => 'hidden',
        '#name' => 'custom_use',
        '#default_value' => isset( $data['data']['conditional_display']['custom_use'] ),
        '#attributes' => array(
            'class' => 'conditional-display-custom-use',
            'mncf-field' => '#form',
            'mncf-field-name' => 'custom_use',
        ),
        '#value' => $use_custom_logic,
    );


    if ( $group ) {
        $form['cd-open'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '<h3>%s</h3>',
                __('Data-dependent display filters', 'mncf')
            ),
        );
        $form['cd-open-description'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '<p class="description">%s</p>',
                __('Specify additional filters that control this Field Group\'s display, based on values of Post Fields.', 'mncf')
            ),
        );
    } else {

	    // We will display a message that this functionality is not supported.

	    switch( $meta_type ) {
		    case 'usermeta':
			    $message = __('Conditional display is not supported for User fields.', 'mncf');
			    break;
		    case 'termmeta':
			    $message = __('Conditional display is not supported for Term fields.', 'mncf');
			    break;
		    default:
			    $message = '';
			    break;
	    }

        $form['cd-open'] = array(
            '#type' => 'markup',
            '#title' => __( 'Conditional display', 'mncf' ),
            '#markup' => $message,
            '#inline' => true,
            '#pattern' => '<tr class="mncf-border-top"><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER>',
        );
    }

    $current = mncf_conditional_get_curent($data);
    if ( empty( $current ) && !isset($data['id'])) {
        $form['alert'] = array(
            '#type' => 'notice',
            '#markup' => __( 'Please save first, before you can edit the conditional display.', 'mncf' ),
        );
    } else {
        $add_edit_condition_button = false;
        $action = $nonce = '';
        $classes = array('js-mncf-condition-button-edit');
        switch( $meta_type ) {
        case 'postmeta':
            $action = 'mncf_edit_field_condition_get';
            $nonce = mn_create_nonce('mncf-conditional-get-'.$data['id']);
            $add_edit_condition_button = true;
            break;
        case 'custom_fields_group':
            $action = 'mncf_edit_custom_field_group_get';
            $nonce = mn_create_nonce('mncf-conditional-get-'.$data['group_id']);
            $add_edit_condition_button = true;
            $classes[] = 'alignright';
            break;
        }


        if ( $add_edit_condition_button ) {
            $form['cd-button'] = array(
                '#name' => 'cd-button',
                '#type' => 'button',
                '#before' => sprintf(
                    '<span class="js-mncf-condition-preview">%s</span><span class="js-mncf-condition-data"></span>',
                    $current
                ),
                '#value' => empty( $current ) ? __( 'Set condition(s)', 'mncf' ) : __( 'Edit condition(s)', 'mncf' ),
                '#attributes' => array(
                    'class' => implode(' ', $classes),
                    'data-mncf-meta-type' => $meta_type,
                    'data-mncf-action' => $action,
                    'data-mncf-id' => $data['id'],
                    'data-mncf-group-id' => isset($data['group_id'])? $data['group_id']:0,
                    'data-mncf-group' => $group,
                    'data-mncf-nonce' => $nonce,
                    'data-mncf-buttons-apply-nonce' => mn_create_nonce('mncf-conditional-apply-'.$data['group_id']),
                    'data-mncf-buttons-apply' => esc_attr__('Apply', 'mncf'),
                    'data-mncf-buttons-cancel' => esc_attr__('Cancel', 'mncf'),
                    'data-mncf-dialog-title' => esc_attr__('Data-dependent display filters', 'mncf'),
                    'data-mncf-message-loading' => esc_attr__('Please Wait, Loading…', 'mncf'),
                    'data-mncf-label-set-conditions' => __( 'Set condition(s)', 'mncf' ),
                    'data-mncf-label-edit-condition' => __( 'Edit condition(s)', 'mncf' )
                ),
            );
        }
    }

    if ( $group ) {
        $form['cd-close'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
        );
    } else {
        $form['cd-close'] = array(
            '#type' => 'markup',
            '#markup' => '',
            '#inline' => true,
            '#pattern' => '</td></tr>',
        );
    }

    return $form;
}

/**
 * Single condition form elements.
 *
 * @param type $data
 * @param type $condition
 * @param type $key
 * @return string
 */
function mncf_cd_admin_form_single_filter( $data, $condition, $key = null, $group = false, $force_multi = false )
{
    global $mncf;

    $name = 'mncf[group][conditional_display]';
    if ( !$group ) {
        $name = 'mncf[fields][' . $data['id'] . '][conditional_display]';
    }
    $group_id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : false;

    /*
     *
     *
     * TODO Review this allowing fields from same group as conditional (self loop)
     * I do not remember allowing fields from same group as conditional (self loop)
     * on Group Fields edit screen.
     */
    $fields = mncf_admin_fields_get_fields(true, false, true);
    ksort( $fields, SORT_STRING );

    if ( $group ) {
        $_distinct = mncf_admin_fields_get_fields_by_group( $group_id );
        foreach ( $_distinct as $_field_id => $_field ) {
            if ( isset( $fields[$_field_id] ) ) {
                unset( $fields[$_field_id] );
            }
        }
    }
    $options = array();

    $ignore_field_type_array = array(
        'audio',
        'checkboxes',
        'embed',
        'file',
        'image',
        'video',
        'wysiwyg',
    );

    $flag_repetitive = false;
    foreach ( $fields as $field_id => $field ) {
        if ( !$group && isset( $data['id'] ) && $data['id'] == $field_id ) {
            continue;
        }
        // WE DO NOT ALLOW repetitive fields to be compared.
        if ( mncf_admin_is_repetitive( $field ) ) {
            $flag_repetitive = true;
            continue;
        }
        /**
         * Skip some files
         */
        if ( in_array( $field['type'], $ignore_field_type_array ) ) {
            continue;
        }
        /**
         * build options
         */
        $options[$field_id] = array(
            '#value' => $field_id,
            '#title' => stripslashes( $field['name'] ),
            '#attributes' => array('class' => 'mncf-conditional-select-' . $field['type']),
        );
    }
    /**
     * add placeholder
     */
    if ( !empty($options) ) {
        array_unshift(
            $options,
            array(
                '#title' => __('Select Custom Field', 'mncf'),
                '#value' => '',
            )
        );
    }
    /*
     * Special case
     * https://icanlocalize.basecamphq.com/projects/7393061-mn-views/todo_items/153565054/comments
     *
     * When field is new and only one diff field in list - that
     * means one field is saved but other not yet.
     */
    $is_new = isset( $data['id'] ) && isset( $fields[$data['id']] ) ? false : true;
    $special_stop = false;
    if ( $is_new ) {
        if ( count( $options ) == 1 ) {
            $special_stop = true;
        }
    }
    /*
     * This means all fields are repetitive and no one left to compare with.
     * WE DO NOT ALLOW repetitive fields to be compared.
     */
    if ( empty( $options ) && $flag_repetitive ) {
        return array(
            'cd' => array(
                '#type' => 'markup',
                '#markup' => '<p class="js-mncf-received-error mncf-error">' . __( 'Conditional display is only working based on non-repeating fields. All fields in this group are repeating, so you cannot set their display based on other fields.', 'mncf' ) . '</p>' . mncf_conditional_disable_add_js( $data['id'] ),
            )
        );
    } else {
        if ( empty( $options ) || $special_stop ) {
            $error_message = $group
                ? __( 'You will be able to set conditional field display when you save more fields in other Field Groups.', 'mncf' )
                : __( 'You will be able to set conditional field display when you save more fields.', 'mncf' );

            return array(
                'cd' => array(
                    '#type' => 'markup',
                    '#markup' => '<p class="js-mncf-received-error mncf-error">' . $error_message . '</p>',
                )
            );
        }
    }
    $id = !is_null( $key ) ? $key : strval( 'condition_' . mncf_unique_id( serialize( $data ) . serialize( $condition ) . $key . $group ) );
    $form = array();
    $form['cd']['row-open-field_' . $id] = array(
        '#type' => 'markup',
        '#markup' => '<tr class="mncf-cd-entry">',
    );
    $form['cd']['field_' . $id] = array(
        '#type' => 'select',
        '#name' => $name . '[conditions][' . $id . '][field]',
        '#options' => $options,
        '#inline' => true,
        '#default_value' => isset( $condition['field'] ) ? $condition['field'] : null,
        '#before' => '<td>',
        '#after' => '</td>',
        '#attributes' => array(
            'class' => 'js-mncf-cd-field',
        ),
    );
    $form['cd']['operation_' . $id] = array(
        '#type' => 'select',
        '#name' => $name . '[conditions][' . $id . '][operation]',
        '#options' => array_flip( mncf_cd_admin_operations() ),
        '#inline' => true,
        '#default_value' => isset( $condition['operation'] ) ? $condition['operation'] : null,
        '#before' => '<td>',
        '#after' => '</td>',
        '#attributes' => array(
            'class' => 'js-mncf-cd-operation',
        ),
    );
    $form['cd']['value_' . $id] = array(
        '#type' => 'textfield',
        '#name' => $name . '[conditions][' . $id . '][value]',
        '#inline' => true,
        '#value' => isset( $condition['value'] ) ? $condition['value'] : '',
        '#attributes' => array(
            'class' => 'js-mncf-cd-value',
        ),
        '#before' => '<td>',
    );
    /**
     * disable for new
     */
    if ( !isset($condition['field']) || empty($condition['field'])) {
        $form['cd']['operation_' . $id]['#attributes']['disabled'] = true;
        $form['cd']['value_' . $id]['#attributes']['disabled'] = true;
    }
    /*
     *
     * Adjust for date
     */

    $visibility = 'hidden';
    if (
        true
        && isset($condition['field'])
        && isset( $fields[$condition['field']] )
        && isset( $fields[$condition['field']]['type'] )
        && 'date' == $fields[$condition['field']]['type']
        && !empty( $condition['value'] )
    ) {
        MNCF_Loader::loadInclude( 'fields/date/functions.php' );
        $timestamp = mncf_fields_date_convert_datepicker_to_timestamp( $condition['value'] );
        if ( $timestamp !== false ) {
            $date_value = adodb_date( 'd', $timestamp ) . ',' . adodb_date( 'm', $timestamp ) . ',' . adodb_date( 'Y', $timestamp );
            $date_function = 'date';
        } else if ( mncf_fields_date_timestamp_is_valid( $condition['value'] ) ) {
            $date_value = adodb_date( 'd', $condition['value'] ) . ',' . adodb_date( 'm', $condition['value'] ) . ',' . adodb_date( 'Y', $condition['value'] );
            $date_function = 'date';
        }
        $form['cd']['value_' . $id]['#attributes']['class'] .= ' hidden';;
        $visibility = 'show';
    }
    if ( empty( $date_value ) ) {
        $date_value = '';
        $date_function = false;
    }
    $form['cd']['value_date_' . $id] = array(
        '#type' => 'markup',
        '#markup' => mncf_conditional_add_date_controls( $date_function, $date_value, $name . '[conditions][' . $id . ']', $visibility ),
        '#attributes' => array('class' => 'js-mncf-cd-value-date'),
        '#after' => '</td>',
    );
    $form['cd']['remove_' . $id] = array(
        '#type' => 'markup',
        '#markup' => sprintf(
            '<a href="#" class="js-mncf-custom-field-remove"><i class="dashicons dashicons-no"></i><span>%s</span></a>',
            __( 'Remove condition', 'mncf' )
        ),
        '#pattern' => '<td><ELEMENT></td>',
    );
    $form['cd']['row-close-field_' . $id] = array(
        '#type' => 'markup',
        '#markup' => '</tr>',
    );
    return $form['cd'];
}

/**
 * Group coditional display filter.
 *
 * @param type $filters
 * @param type $update
 * @return type
 */
function mncf_cd_fields_form_additional_filters( $filters, $update ) {
    $data = array();
    $data['id'] = !empty( $update ) && isset($update['name'])? $update['name'] : mncf_unique_id( serialize( $filters ) );
    $data['group_id'] = $update['id'];
    if ( $update ) {
        $data['data']['conditional_display'] = maybe_unserialize( get_post_meta( $update['id'],
                        '_mncf_conditional_display', true ) );
    } else {
        $data['data']['conditional_display'] = array();
    }
    $data['meta_type'] = isset( $update['meta_type'] )? $update['meta_type']:'unknown';
    $filters = $filters + mncf_cd_admin_form_filter( array(), $data, true );
    return $filters;
}

/**
 * Save group action hook.
 *
 * @param type $group
 */
function mncf_cd_save_group_action( $group )
{
    if ( !empty( $group['conditional_display']) && is_array($group['conditional_display']) ) {
        $group['conditional_display'] = sanitize_text_field_recursively($group['conditional_display']);
        update_post_meta( $group['id'], '_mncf_conditional_display', $group['conditional_display'] );
    } elseif (isset($group['id'])) {
        delete_post_meta( $group['id'], '_mncf_conditional_display' );
    }
}

/**
 * Triggers disabling 'Add Condition' button.
 * @param type $id
 * @return string
 */
function mncf_conditional_disable_add_js( $id ) {
    $js = '';
    $js .= '<script type="text/javascript">
        jQuery(document).ready(function(){mncfDisableAddCondition(\''
            . strtolower( $id ) . '\'); });
    </script>
';
    return $js;
}

/**
 * Date select form for Group edit screen.
 *
 * @global type $mn_locale
 * @param type $function
 * @param type $value
 * @param type $name
 * @param string $visibility paramter decide about should we hide date inputs
 * @return string
 *
 */
function mncf_conditional_add_date_controls( $function, $value, $name, $visibility = 'hidden' )
{
    global $mn_locale;
    if ( $function == 'date' ) {
        $date_parts = explode( ',', $value );
        $time_adj = adodb_mktime( 0, 0, 0, $date_parts[1], $date_parts[0], $date_parts[2] );
    } else {
        $time_adj = current_time( 'timestamp' );
    }
    $jj = adodb_gmdate( 'd', $time_adj );
    $mm = adodb_gmdate( 'm', $time_adj );
    $aa = adodb_gmdate( 'Y', $time_adj );
    $output = sprintf(
        '<div class="mncf-custom-field-date %s">',
        'hidden' == $visibility? 'hidden':''
    );
    $month = "<select name=\"" . $name . '[month]' . "\" >";
    for ( $i = 1; $i < 13; $i = $i + 1 ) {
        $monthnum = zeroise( $i, 2 );
        $month .= '<option value="' . $monthnum . '"';
        if ( $i == $mm )
            $month .= ' selected="selected"';
        $month .= '>' . $monthnum . '-'
                . $mn_locale->get_month_abbrev( $mn_locale->get_month( $i ) )
                . '</option>';
    }
    $month .= '</select>';
    $day = '<input name="' . $name . '[date]" type="text" value="' . $jj . '" size="2" maxlength="2" autocomplete="off" />';
    $year = '<input name="' . $name . '[year]" type="text" value="' . $aa . '" size="4" maxlength="4" autocomplete="off" />';
    $output .= sprintf( __( '%1$s%2$s, %3$s', 'mncf' ), $month, $day, $year );
    $output .= '<div class="mncf_custom_field_invalid_date mncf-form-error"><p>' . __( 'Please enter a valid date here', 'mncf' ) . '</p></div>';
    $output .= '</div>';
    return $output;
}

function mncf_conditional_get_curent($data)
{
    /**
     * state of conditional display custom use
     */
    if (
        true
        && array_key_exists( 'data', $data )
        && is_array( $data['data'] )
        && array_key_exists( 'conditional_display', $data['data'] )
        && is_array( $data['data']['conditional_display'] )
        && array_key_exists( 'custom_use', $data['data']['conditional_display'] )
        && !empty( $data['data']['conditional_display']['custom_use'] )
    ) {
        return '<ul><li>' . __( 'Custom logic', 'mncf' ) . '</li></ul>';
    }
    $current = '';
    if (
        true
        && isset( $data['data']['conditional_display'])
        && isset( $data['data']['conditional_display']['conditions'])
        && !empty( $data['data']['conditional_display']['conditions'])
    ) {
        $convert = mncf_cd_admin_operations();
        $all_types_fields = get_option( 'mncf-fields', array() );
        $current .= '<ul>';
        foreach( $data['data']['conditional_display']['conditions'] as $condition ) {
            if (
                false
                || !isset($condition['field'])
                || !isset($all_types_fields[$condition['field']])
            ) {
                continue;
            }
            $operation = __('unknown', 'mncf');
            if (
                true
                && isset($condition['operation'])
                && isset($convert[$condition['operation']])
            ) {
                $operation = $convert[$condition['operation']];
            }
            $value = isset($condition['value'])? $condition['value']:'';
            /**
             * handle date field
             */
            if (
                true
                && empty($value)
                && isset($all_types_fields[$condition['field']])
                && isset($all_types_fields[$condition['field']]['type'])
                && 'date' == $all_types_fields[$condition['field']]['type']
            ) {
                $value = sprintf(
                    __( '%1$s%2$s, %3$s', 'mncf' ),
                    $condition['month'],
                    $condition['date'],
                    $condition['year']
                );
                $value = date(
                    get_option( 'date_format' ),
                    mktime( 0, 0, 0, $condition['date'], $condition['month'], $condition['year'] )
                );
            }
            
            /* remove operators description */
            $operation = preg_replace( '#\([^)]+\)#', '', $operation );

            $current .= sprintf(
                '<li><span>%s %s %s</span></li>',
                esc_html($all_types_fields[$condition['field']]['name']),
                esc_html($operation),
                esc_html($value)
            );
        }
        $current .= '</ul>';
    }
    return $current;
}

