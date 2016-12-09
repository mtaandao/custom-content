<?php
/*
 * Conditional display embedded code.
 */

/*
 * Post page filters
 */
add_filter( 'mncf_post_edit_field', 'mncf_cd_post_edit_field_filter', 10, 4 );
add_filter( 'mncf_post_groups', 'mncf_cd_post_groups_filter', 10, 3 );

/*
 *
 * These hooks check if conditional failed
 * but form allowed to be saved
 * Since Types 1.2
 */
add_filter( 'mncf_post_form_error', 'mncf_conditional_post_form_error_filter',
        10, 2 );


/*
 * Logger
 */
if ( !function_exists( 'mnlogger' ) ) {
    require_once MNCF_EMBEDDED_TOOLSET_ABSPATH . '/toolset-common/mnlogger.php';
}
if ( !function_exists( 'mnv_filter_parse_date' ) ) {
    require_once MNCF_EMBEDDED_TOOLSET_ABSPATH . '/toolset-common/mnv-filter-date-embedded.php';
}

require_once MNTOOLSET_FORMS_ABSPATH . '/classes/class.conditional.php';
require_once MNTOOLSET_FORMS_ABSPATH . '/classes/class.types.php';

/**
 * Filters groups on post edit page.
 *
 * @param type $groups
 * @param type $post
 * @return type
 */
function mncf_cd_post_groups_filter( $groups, $post, $context ) {
    if ( $context != 'group' ) {
        return $groups;
    }


    foreach ( $groups as $key => &$group ) {

        $conditions = null;
        if (
            array_key_exists( 'conditional_display', $group )
            && array_key_exists( 'conditions', $group['conditional_display'] )
        ) {
            $conditions = $group['conditional_display'];
        } else {
            $conditions = get_post_meta( $group['id'], '_mncf_conditional_display', true );
        }

        if ( !empty( $conditions['conditions'] )
             || ( isset( $conditions['custom_use'] )
                  && $conditions['custom_use'] == 1
                  && isset( $conditions['custom'] )
                  && !empty( $conditions['custom'] )
             )
        ) {
            $meta_box_id = "mncf-group-{$group['slug']}";
            $prefix = 'mncf-';
            $suffix = '';

            $cond = array();
            $cond_values = array();
            if (isset( $post->ID ) && $post->ID) {
                $cond_values = get_post_custom( $post->ID );
            }
            $_cond_values = array();

            foreach ( $cond_values as $k => $v ) {
                $v = maybe_unserialize( $v[0] );
                /**
                 * if data is too complex - skip it
                 */
                if ( is_array( $v) ) {
                    $v = array_shift($v);
                    if ( is_array($v) ) {
                        continue;
                    }
                    $v = strval( $v);
                }
                $_cond_values[$k . $suffix] = $v;
            }
            unset( $cond_values );
            $cond = array();
            if ( !empty( $conditions['custom_use'] ) ) {
                if ( !empty( $conditions['custom'] ) ) {
                    $custom = MNToolset_Types::getCustomConditional($conditions['custom']);
                    $passed = MNToolset_Forms_Conditional::evaluateCustom($custom['custom'], $_cond_values);
                    $cond = array(
                        'custom' => $custom['custom'],
                        'custom_use' => true
                    );
                }
            } else {
                $cond = array(
                    'relation' => $conditions['relation'],
                    'conditions' => array(),
                    'values' => $_cond_values,
                );
                foreach ( $conditions['conditions'] as $d ) {
                    $c_field = types_get_field( $d['field'] );
                    if ( !empty( $c_field ) ) {
                        $_c = array(
                            'id' => mncf_types_get_meta_prefix( $c_field ) . $d['field'] . $suffix,
                            'type' => $c_field['type'],
                            'operator' => $d['operation'],
                            'args' => array($d['value']),
                        );
                        $cond['conditions'][] = $_c;
                    }
                }
                $passed = mntoolset_form_conditional_check( array( 'conditional' => $cond ) );
            }
            $data = array(
                'id' => $meta_box_id,
                'conditional' => $cond,
            );
            mntoolset_form_add_conditional( 'post', $data );
            if ( !isset( $passed ) || !$passed ) {
                $group['_conditional_display'] = 'failed';
            } else {
                $group['_conditional_display'] = 'passed';
            }
        }
    }
    return $groups;
}

/**
 * Checks if there is conditional display.
 *
 * This function filters all fields that appear in form.
 * It checks if field is Check Trigger or Conditional.
 * Since Types 1.2 this functin is simplified and should stay that way.
 * It's important core action.
 *
 *
 * @param type $element
 * @param type $field
 * @param type $post
 * @return type
 */
function mncf_cd_post_edit_field_filter( $element, $field, $post,
        $context = 'group' ) {

    // Do not use on repetitive
    if ( defined( 'DOING_AJAX' ) && $context == 'repetitive' ) {
        return $element;
    }

    // Use only with postmeta
    if ( $field['meta_type'] != 'postmeta' ) {
        return $element;
    }

    global $mncf;

    /*
     *
     *
     * Since Types 1.2
     * Automatically evaluates MNCF_Conditional::set()
     * Evaluation moved to MNCF_Conditional::evaluate()
     */
    if ( $mncf->conditional->is_conditional( $field )
            || $mncf->conditional->is_trigger( $field ) ) {

        mncf_conditional_add_js();
        $mncf->conditional->set( $post, $field );

        /*
         * Check if field is check trigger and wrap it
         * (add CSS class 'mncf-conditonal-check-trigger')
         */
        if ( $mncf->conditional->is_trigger( $field ) ) {
            $element = $mncf->conditional->wrap_trigger( $element );
        }

        /*
         * If conditional
         */
        if ( $mncf->conditional->is_conditional( $field ) ) {
            $element = $mncf->conditional->wrap( $element );
        }
    }

    return $element;
}

/**
 * Operations.
 *
 * @return type
 */
function mncf_cd_admin_operations() {
    return array(
        '='     => '= (' . __( 'equal to', 'mncf' ) . ')',
        '>'     => '> (' . __( 'larger than', 'mncf' ) . ')',
        '<'     => '< (' . __( 'less than', 'mncf' ) . ')',
        '>='    => '>= (' . __( 'larger or equal to', 'mncf' ) . ')',
        '<='    => '<= (' . __( 'less or equal to', 'mncf' ) . ')',
        '==='   => '=== (' . __( 'identical to', 'mncf' ) . ')',
        '<>'    => '!= (' . __( 'not identical to', 'mncf' ) . ')',
        '!=='   => '!== (' . __( 'strictly not equal', 'mncf' ) . ')',
//        'between' => __('between', 'mncf'),
    );
}

/**
 * Compares values.
 *
 * @param type $operation
 * @return type
 */
function mncf_cd_admin_compare( $operation ) {
    $args = func_get_args();
    switch ( $operation ) {
        case '=':
            return $args[1] == $args[2];
            break;

        case '>':
            return intval( $args[1] ) > intval( $args[2] );
            break;

        case '>=':
            return intval( $args[1] ) >= intval( $args[2] );
            break;

        case '<':
            return intval( $args[1] ) < intval( $args[2] );
            break;

        case '<=':
            return intval( $args[1] ) <= intval( $args[2] );
            break;

        case '===':
            return $args[1] === $args[2];
            break;

        case '!==':
            return $args[1] !== $args[2];
            break;

        case '<>':
            return $args[1] <> $args[2];
            break;

        case 'between':
            return intval( $args[1] ) > intval( $args[2] ) && intval( $args[1] ) < intval( $args[3] );
            break;

        default:
            break;
    }
    return true;
}

/**
 * Setsa all JS.
 */
function mncf_conditional_add_js() {
    mncf_cd_add_field_js();
}

/**
 * JS for fields AJAX.
 */
function mncf_cd_add_field_js() {
    global $mncf;
    $mncf->conditional->add_js();
}


/**
 * Passes $_POST values for AJAX call.
 *
 * @todo still used by group.
 *
 * @param type $null
 * @param type $object_id
 * @param type $meta_key
 * @param type $single
 * @return type
 */
function mncf_cd_meta_ajax_validation_filter( $null, $object_id, $meta_key, $single )
{
    $meta_key = str_replace( 'mncf-', '', $meta_key );
    $field = mncf_admin_fields_get_field( $meta_key );
    $value = !empty( $field ) && isset( $_POST['mncf'][$meta_key] ) ? $_POST['mncf'][$meta_key] : '';
    /**
     * be sure do not return string if array is expected!
     */
    if ( !$single && !is_array($value) ) {
        return array($value);
    }
    return $value;
}

/**
 * Post form error filter.
 *
 * Leave element as not_valid (it will prevent saving) just remove warning.
 *
 * @global type $mncf
 * @param type $_error
 * @param type $_not_valid
 * @return boolean
 */
function mncf_conditional_post_form_error_filter( $_error, $_not_valid ) {
    if ( !empty( $_not_valid ) ) {

        global $mncf;

        $count = 0;
        $count_non_conditional = 0;
        $error_conditional = false;

        foreach ( $_not_valid as $f ) {
            $field = $f['_field'];
            /*
             * Here we add simple check
             *
             * TODO Improve this check
             * We can not tell for sure if it failed except to again check
             * conditionals
             */
            // See if field is conditional
            if ( isset( $field->cf['data']['conditional_display'] ) ) {

                // Use Conditional class
                $test = new MNCF_Conditional();
                $test->set( $mncf->post, $field->cf );

                // See if evaluated right
                $passed = $test->evaluate();

                // If evaluated FALSE that means error is expected
                if ( $passed ) {
                    $error_conditional = true;
                }

                // Count it
                $count++;
            } else {
                $count_non_conditional++;
            }
            /*
             * If non-conditional fields are not valid - return $_error TRUE
             * If at least one conditional failed - return FALSE
             */
            if ( $count_non_conditional > 0 ) {
                return true;
            }
            return $error_conditional;
        }
    }
    return $_error;
}
