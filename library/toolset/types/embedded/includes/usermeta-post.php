<?php
/*
 * Edit post page functions
 *
 * Core file with stable and working functions.
 * Please add hooks if adjustment needed, do not add any more new code here.
 *
 * Consider this file half-locked since Types 1.1.4
 */

// Include conditional field code
require_once MNCF_EMBEDDED_ABSPATH . '/includes/conditional-display.php';

/**
 * Init functions for User profile edit pages.
*/
function mncf_admin_userprofile_init($user_id){
	global $mncf;
	if ( !is_object($user_id) ){
		$user_id = new stdClass();
		$user_id->ID = 0;
	}
	$current_user_roles = isset( $user_id->roles ) ? $user_id->roles : array( 'subscriber' );
	$current_user_roles = array_values( $current_user_roles );
	$user_role = array_shift( $current_user_roles );
	$groups = mncf_admin_usermeta_get_groups_fields();
	$mncf_active = false;
    $profile_only_preview = '';

    foreach ( $groups as $group ) {
        if ( !empty( $group['fields'] ) ) {
            $mncf_active = true;
			$for_users = mncf_admin_get_groups_showfor_by_group($group['id']);
			$profile_only_preview = '';
			if ( count($for_users) != 0){
				if ( !in_array($user_role,$for_users)){
					continue;
				}
				else{
					//If Access plugin activated
					if (function_exists('mncf_access_register_caps')){

						//If user can't view own profile fields
						if (!current_user_can('view_own_in_profile_' . $group['slug'])){
							continue;
						}
						//If user can modify current group in own profile
						if (!current_user_can('modify_own_' . $group['slug'])){
							$profile_only_preview = 1;
						}


					}
				}
			}
            else{
                 if (function_exists('mncf_access_register_caps')){
                     if (!current_user_can('view_own_in_profile_' . $group['slug'])){
                       continue;
                     }
                     if (!current_user_can('modify_own_' . $group['slug'])){
                        $profile_only_preview = 1;
                     }
                  }
            }

            // Process fields
			if ( empty($profile_only_preview) ){

				$group_mnml = new Types_Wpml_Field_Group( Types_Field_Group_User_Factory::load( $group['slug'] ) );

                if ( defined( 'MNTOOLSET_FORMS_VERSION' ) ) {
                    $errors = get_user_meta( $user_id->ID, '__mncf-invalid-fields',
                            true );
                    // OLD
                    delete_post_meta( $user_id->ID, 'mncf-invalid-fields' );
                    delete_post_meta( $user_id->ID, '__mncf-invalid-fields' );
                    if ( empty( $group['fields'] ) ) continue;

                    $output = '<div class="mncf-group-area mncf-group-area_'
                    . $group['slug'] . '">' . "\n\n" . '<h3>'
                    . $group_mnml->translate_name() . '</h3>' . "\n\n";

                    if ( !empty( $group['description'] ) ) {
                        $output .= '<span>' . mnautop( $group_mnml->translate_description() )
                                . '</span>' . "\n\n";
                    }

                    $output .= '<div class="mncf-profile-field-line">' . "\n\n";

                    foreach ( $group['fields'] as $field ) {
                        $config = mntoolset_form_filter_types_field( $field,
                                $user_id->ID );

                        $config = array_map( 'fix_fields_config_output_for_display', $config);

                        $meta = get_user_meta( $user_id->ID, $field['meta_key'] );
                        if ( $errors ) {
                            $config['validate'] = true;
                        }
                        if ( isset( $config['validation']['required'] ) ) {
                            $config['title'] .= '&#42;';
                        }
                        $config['_title'] = $config['title'];
                        $output .= '
<div class="mncf-profile-field-line">
	<div class="mncf-profile-line-left">
        ' . $config['title'] . '
    </div>
	<div class="mncf-profile-line-right">
    ';
                        $description = false;
                        if ( !empty($config['description'])) {
                            $description = sprintf(
                                '<span class="description">%s</span>',
                                $config['description']
                            );
                        }
                        $config['title'] = $config['description'] = '';
                        $form_name = $user_id->ID? 'your-profile':'createuser';
                        $output .= mntoolset_form_field( $form_name, $config, $meta );
                        if ( $description ) {
                            $output .= $description;
                        }
                        $output .= '
    </div>
</div>';
                    }

                    $output .= '</div></div>';
                    echo $output;
                } else {
                    $group['fields'] = mncf_admin_usermeta_process_fields( $user_id,
                            $group['fields'], true );
                    mncf_admin_render_fields( $group, $user_id );
                }
			}
			else{
				// Render profile fields (text only)
				mncf_usermeta_preview_profile( $user_id, $group );
			}
        }

	}



    // Activate scripts
    if ( $mncf_active ) {
		mn_enqueue_script( 'mncf-fields-post',
                MNCF_EMBEDDED_RES_RELPATH . '/js/fields-post.js',
                array('jquery'), MNCF_VERSION );
        mn_enqueue_script( 'mncf-form-validation',
                MNCF_EMBEDDED_RES_RELPATH . '/js/'
                . 'jquery-form-validation/jquery.validate.min.js',
                array('jquery'), MNCF_VERSION );
        mn_enqueue_script( 'mncf-form-validation-additional',
                MNCF_EMBEDDED_RES_RELPATH . '/js/'
                . 'jquery-form-validation/additional-methods.min.js',
                array('jquery'), MNCF_VERSION );
        mn_enqueue_style( 'mncf-css-embedded',
                MNCF_EMBEDDED_RES_RELPATH . '/css/basic.css', array(),
                MNCF_VERSION );
        mn_enqueue_style( 'mncf-fields-post',
                MNCF_EMBEDDED_RES_RELPATH . '/css/fields-post.css',
                array('mncf-css-embedded'), MNCF_VERSION );
		mn_enqueue_style( 'mncf-usermeta',
                MNCF_EMBEDDED_RES_RELPATH . '/css/usermeta.css',
                array('mncf-css-embedded'), MNCF_VERSION );
        mncf_enqueue_scripts();
		mncf_field_enqueue_scripts( 'date' );
		mncf_field_enqueue_scripts( 'image' );
		mncf_field_enqueue_scripts( 'file' );
		mncf_field_enqueue_scripts( 'skype' );
		mncf_field_enqueue_scripts( 'numeric' );
        add_action( 'admin_footer', 'mncf_admin_profile_js_validation' );
    }
}

function fix_fields_config_output_for_display($match)
{
    if( gettype($match) === 'string' )
    {
        $match = stripcslashes( $match );
    }
    return $match;
}

/*
* Show user fields values in profile
* $user_id = array, $group = array
*/
function mncf_usermeta_preview_profile( $user_id, $group, $echo = ''){
	if ( is_object($user_id) ){
		$user_id = $user_id->ID;
	}
	require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields.php';
	require_once MNCF_EMBEDDED_ABSPATH . '/frontend.php';
	global $mncf;
	//print_r($group);exit;
	$fields = $group['fields'];
	$group_mnml = new Types_Wpml_Field_Group( Types_Field_Group_User_Factory::load( $group['slug'] ) );

	$group_output = '<div class="mncf-group-area mncf-group-area-' . $group['slug'] . '">' . "\n\n";
	$group_output .=  '<h3 class="mncf-group-header-'. $group['slug'] .'">'. $group_mnml->translate_name() .'</h3>'. "\n\n";


	foreach ( $fields as $field ) {
		$html = '';
		$params['post_type'] = TYPES_USER_META_FIELD_GROUP_CPT_NAME;
		$params['option_name'] = 'mncf-usermeta';
		$params['separator'] = ', ';
		if ( mncf_admin_is_repetitive( $field ) ) {
        $mncf->usermeta_repeater->set( $user_id, $field );
        $_meta = $mncf->usermeta_repeater->_get_meta();
        if ( isset( $_meta['custom_order'] )){
			$meta = $_meta['custom_order'];
		}
		else{
			$meta = array();
		}
		$content = $code = '';
		// Sometimes if meta is empty - array(0 => '') is returned
        if ( (count( $meta ) == 1 ) ) {
            $meta_id = key( $meta );
            $_temp = array_shift( $meta );
            if (!is_array($_temp) && strval( $_temp ) == '' ) {

            } else {
                $params['field_value'] = $_temp;
                if ( !empty($params['field_value']) ){
				$html = types_render_field_single( $field, $params, $content,
                                $code, $meta_id );
				}
            }
        } else if ( !empty( $meta ) ) {
            $output = '';

            if ( isset( $params['index'] ) ) {
                $index = $params['index'];
            } else {
                $index = '';
            }

            // Allow mnv-for-each shortcode to set the index
            $index = apply_filters( 'mnv-for-each-index', $index );

            if ( $index === '' ) {
                $output = array();
                foreach ( $meta as $temp_key => $temp_value ) {
                    $params['field_value'] = $temp_value;
                    if ( !empty($params['field_value']) ){
						$temp_output = types_render_field_single( $field, $params,
								$content, $code, $temp_key );
					}
                    if ( !empty( $temp_output ) ) {
                        $output[] = $temp_output;
                    }
                }
                if ( !empty( $output ) && isset( $params['separator'] ) ) {
                    $output = implode( html_entity_decode( $params['separator'] ),
                            $output );
                } else if ( !empty( $output ) ) {
                    $output = implode( ' ', $output );
                }
            } else {
                // Make sure indexed right
                $_index = 0;
                foreach ( $meta as $temp_key => $temp_value ) {
                    if ( $_index == $index ) {
                        $params['field_value'] = $temp_value;
						if ( !empty($params['field_value']) ){
                        $output = types_render_field_single( $field, $params,
                                        $content, $code, $temp_key );
						}
                    }
                    $_index++;
                }
            }
            $html = $output;
        }
		} else {

			$params['field_value'] = get_user_meta( $user_id,
					mncf_types_get_meta_prefix( $field ) . $field['slug'], true );

			if ( !empty($params['field_value']) && $field['type'] != 'date' ){
				$html = types_render_field_single( $field, $params );
			}
			if ( $field['type'] == 'date' && !empty($params['field_value']) ){
				$html = types_render_field_single( $field, $params );
				if ($field['data']['date_and_time'] == 'and_time'){
					$html .= ' ' . date("H", $params['field_value']) . ':' . date("i", $params['field_value']);
				}
			}
		}

		// API filter
		$mncf->usermeta_field->set( $user_id, $field );
		$field_value = $mncf->usermeta_field->html( $html, $params );
$group_output .= '<div class="mncf-profile-field-line mncf-profile-field-line-'. $field['slug'] .'">
		<div class="mncf-profile-line-left">
		<b>'. $field['name'] .'</b>
		</div>
		<div class="mncf-profile-line-right">
		'. $field_value .'
		</div>
</div>' . "\n\n";


	}
	$group_output .= "\n\n</div>";
	if ( empty($echo) ){
		echo $group_output;
	}else{
		return $group_output;
	}

}

/*
* Set fomr ID to JS validation
*/
function mncf_admin_profile_js_validation(){
    mncf_form_render_js_validation( '#your-profile' );
}


/*
* Save user profile custom fields
*/
function mncf_admin_userprofilesave_init($user_id){

    if ( defined( 'MNTOOLSET_FORMS_VERSION' ) ) {

        global $mncf;
        $errors = false;

        /**
         * check checkbox type fields to delete or save empty if needed
         */
        $groups = mncf_admin_usermeta_get_groups_fields();
        foreach ( $groups as $group ) {
            if ( !array_key_exists( 'fields', $group ) || empty( $group['fields'] ) ) {
                continue;
            }
            foreach( $group['fields'] as $field ) {
                switch ( $field['type'] ) {
                case 'checkboxes':
                    if (
                        !array_key_exists('mncf', $_POST)
                        || !array_key_exists( $field['slug'], $_POST['mncf'] )
                    ) {
                        delete_user_meta($user_id, $field['meta_key']);
                    }
                    break;
                case 'checkbox':
                    if (
                        !array_key_exists('mncf', $_POST)
                        || !array_key_exists( $field['slug'], $_POST['mncf'] )
                    ) {
                        if ( 'yes' == $field['data']['save_empty'] ) {
                            $_POST['mncf'][$field['slug']] = 0;
                        } else {
                            delete_user_meta($user_id, $field['meta_key']);
                        }
                    }
                    break;
                }
            }
        }

        // Save meta fields
        if ( !empty( $_POST['mncf'] ) ) {
            foreach ( $_POST['mncf'] as $field_slug => $field_value ) {
                // Get field by slug
                $field = mncf_fields_get_field_by_slug( $field_slug, 'mncf-usermeta' );
                if ( empty( $field ) ) {
                    continue;
                }
                // Skip copied fields
                if ( isset( $_POST['mncf_repetitive_copy'][$field['slug']] ) ) {
                    continue;
                }
                $_field_value = !types_is_repetitive( $field ) ? array($field_value) : $field_value;
                // Set config
                $config = mntoolset_form_filter_types_field( $field, $user_id );
                foreach ( $_field_value as $_k => $_val ) {
                    // Check if valid
                    $valid = mntoolset_form_validate_field( 'your-profile', $config,
                            $_val );
                    if ( is_mn_error( $valid ) ) {
                        $errors = true;
                        $_errors = $valid->get_error_data();
                        $_msg = sprintf( __( 'Field "%s" not updated:', 'mncf' ),
                                $field['name'] );
                        mncf_admin_message_store( $_msg . ' ' . implode( ', ',
                                        $_errors ), 'error' );
                        if ( types_is_repetitive( $field ) ) {
                            unset( $field_value[$_k] );
                        } else {
                            break;
                        }
                    }
                }
                // Save field
                if ( types_is_repetitive( $field ) ) {
                    $mncf->usermeta_repeater->set( $user_id, $field );
                    $mncf->usermeta_repeater->save( $field_value );
                } else {
                    $mncf->usermeta_field->set( $user_id, $field );
                    $mncf->usermeta_field->usermeta_save( $field_value );
                }

                do_action( 'mncf_user_field_saved', $user_id, $field );

                // TODO Move to checkboxes

                if ( $field['type'] == 'checkboxes' ) {
                    $field_data = mncf_admin_fields_get_field( $field['id'], false, false, false, 'mncf-usermeta' );
                    if ( !empty( $field_data['data']['options'] ) ) {
                        $update_data = array();
                        foreach ( $field_data['data']['options'] as $option_id => $option_data ) {
                            if ( !isset( $_POST['mncf'][$field['id']][$option_id] ) ) {
                                if ( isset( $field_data['data']['save_empty'] ) && $field_data['data']['save_empty'] == 'yes' ) {
                                    $update_data[$option_id] = 0;
                                }
                            } else {
                                $update_data[$option_id] = $_POST['mncf'][$field['id']][$option_id];
                            }
                        }
                        update_user_meta( $user_id, $field['meta_key'], $update_data );
                    }
                }
            }
        }
        if ( $errors ) {
            update_post_meta( $user_id, '__mncf-invalid-fields', true );
        }
        do_action( 'mncf_user_saved', $user_id );
        return;
    }

	global $mncf;

	$all_fields = array();
	$_not_valid = array();
	$_error = false;
	$error = '';

	$groups = $groups = mncf_admin_usermeta_get_groups_fields();
    if ( empty( $groups ) ) {
        return false;
    }

	foreach ( $groups as $group ) {
        // Process fields

        $fields = mncf_admin_usermeta_process_fields( $user_id , $group['fields'], true,
                false, 'validation' );
        // Validate fields
        $form = mncf_form_simple_validate( $fields );

        $all_fields = $all_fields + $fields;

        // Collect all not valid fields
        if ( $form->isError() ) {
            $_error = true; // Set error only to true
            $_not_valid = array_merge( $_not_valid,
                    (array) $form->get_not_valid() );
        }
    }

	// Set fields
    foreach ( $all_fields as $k => $v ) {
        // only Types field
        if ( empty( $v['mncf-id'] ) ) {
            continue;
        }
        $_temp = new MNCF_Usermeta_Field();
        $_temp->set( $user_id, $v['mncf-id'] );
        $all_fields[$k]['_field'] = $_temp;
    }
	foreach ( $_not_valid as $k => $v ) {
        // only Types field
        if ( empty( $v['mncf-id'] ) ) {
            continue;
        }
        $_temp = new MNCF_Usermeta_Field();
        $_temp->set( $user_id, $v['mncf-id'] );
        $_not_valid[$k]['_field'] = $_temp;
    }

    $not_valid = apply_filters( 'mncf_post_form_not_valid', $_not_valid,
            $_error, $all_fields );


    // Notify user about error
    if ( $error ) {
        mncf_admin_message_store(
                __( 'Please check your input data', 'mncf' ), 'error' );
    }

    /*
     * Save invalid elements so user can be informed after redirect.
     */
    if ( !empty( $not_valid ) ) {
        update_user_meta( $user_id, 'mncf-invalid-fields', $not_valid );
    }


	if ( !empty( $_POST['mncf'] ) ) {
        foreach ( $_POST['mncf'] as $field_slug => $field_value ) {

			$field = mncf_fields_get_field_by_slug( $field_slug, 'mncf-usermeta' );
			if ( empty( $field ) ) {
                continue;
            }


			$mncf->usermeta_field->set( $user_id, $field );
			if ( isset( $_POST['mncf_repetitive_copy'][$field['slug']] ) ) {
                continue;
            }

			if ( isset( $_POST['__mncf_repetitive'][$mncf->usermeta_field->slug] ) ) {
                 $mncf->usermeta_repeater->set( $user_id, $field );
                $mncf->usermeta_repeater->save();
            } else {
                 $mncf->usermeta_field->usermeta_save();
            }

            do_action('mncf_post_field_saved', '', $field);



		}//end foreach

	}//end if

	foreach ( $all_fields as $field ) {
		if ( !isset( $field['#type'] ) ) {
            continue;
        }
		if ( $field['#type'] == 'checkbox') {
            $field_data = mncf_admin_fields_get_field( $field['mncf-id'], false,
                    false, false, 'mncf-usermeta' );
			if ( !isset( $_POST['mncf'][$field['mncf-slug']] ) ){
				if ( isset( $field_data['data']['save_empty'] )
                    && $field_data['data']['save_empty'] == 'yes' ) {
						update_user_meta($user_id, mncf_types_get_meta_prefix( $field ) . $field['mncf-slug'], 0);
				}
				else{
					delete_user_meta($user_id, mncf_types_get_meta_prefix( $field ) . $field['mncf-slug']);
				}
			}
		}
        if ( $field['#type'] == 'checkboxes' ) {
            $field_data = mncf_admin_fields_get_field( $field['mncf-id'], false,
                    false, false, 'mncf-usermeta' );
            if ( !empty( $field_data['data']['options'] ) ) {
                $update_data = array();
                foreach ( $field_data['data']['options'] as $option_id => $option_data ) {
                    if ( !isset( $_POST['mncf'][$field['mncf-slug']][$option_id] ) ) {
                        if ( isset( $field_data['data']['save_empty'] ) && $field_data['data']['save_empty'] == 'yes' ) {
                            $update_data[$option_id] = 0;
                        }
                    } else {
                        $update_data[$option_id] = $_POST['mncf'][$field['mncf-slug']][$option_id];
                    }
                }
                update_user_meta( $user_id,
                        mncf_types_get_meta_prefix( $field ) . $field['mncf-slug'],
                        $update_data );
            }
        }
	}


}


/*
* Render user profile form fields
*/
function mncf_admin_render_fields( $group, $user_id, $echo = '') {

	global $mncf;
	$group_mnml = new Types_Wpml_Field_Group( Types_Field_Group_User_Factory::load( $group['slug'] ) );

	$output = '<div class="mncf-group-area mncf-group-area_' . $group['slug'] . '">' . "\n\n";
	$output .= '<h3>'. $group_mnml->translate_name() .'</h3>' . "\n\n";
	if ( !empty( $group['fields'] ) ) {
        // Display description
        if ( !empty( $group['description'] ) ) {
            $output .= '<span>'
            . mnautop( $group_mnml->translate_description() ) . '</span>' . "\n\n";
        }

		$output .=  '<div class="mncf-profile-field-line">' . "\n\n";
        foreach ( $group['fields'] as $field_slug => $field ) {
            if ( empty( $field ) || !is_array( $field ) ) {
                continue;
            }
			$field = $mncf->usermeta_field->_parse_cf_form_element( $field );

            if ( !isset( $field['#id'] ) ) {
                $field['#id'] = mncf_unique_id( serialize( $field ) );
            }
			if ( isset( $field['mncf-type'] ) ) { // May be ignored
                $field = apply_filters( 'mncf_fields_' . $field['mncf-type'] . '_meta_box_form_value_display', $field );
            }
            // Render form elements
            if ( mncf_compare_mn_version() && $field['#type'] == 'wysiwyg' ) {
				$field['#editor_settings']['media_buttons'] = '';
				if ( !empty($echo) ){
					$field['#editor_settings']['mnautop'] = true;
				}
                // Especially for WYSIWYG
                $output .=  "\n".'<div class="mncf-profile-field-line">' . "\n\n";
				$output .= '<div class="mncf-wysiwyg">' . "\n\n";
                $output .=  '<div id="mncf-textarea-textarea-wrapper" class="form-item form-item-textarea mncf-form-item mncf-form-item-textarea">' . "\n\n";
                $output .=  isset( $field['#before'] ) ? $field['#before'] : '';
                $output .=  '<label class="mncf-form-label mncf-form-textarea-label">' . $field['#title'] . '</label>' . "\n\n";
                $output .=  '<div class="description mncf-form-description mncf-form-description-textarea description-textarea">' . "\n\n" .
					 mnautop( $field['#description'] ) . '</div>' . "\n\n";
                ob_start();
				mn_editor( $field['#value'], $field['#id'],
                        $field['#editor_settings'] );
				$output .= ob_get_clean() . "\n\n";
                $field['slug'] = str_replace( MNCF_META_PREFIX . 'wysiwyg-', '',
                        $field_slug );
                $field['type'] = 'wysiwyg';
                $output .=  '</div>' . "\n\n";
                $output .=  isset( $field['#after'] ) ? $field['#after'] : '';
                $output .=  '</div>' . "\n\n";
				$output .= '</div>' . "\n\n";
            }
			else {
                if ( $field['#type'] == 'wysiwyg' ) {
                    $field['#type'] = 'textarea';
                }
				$field['#pattern'] = "\n".'<div class="mncf-profile-field-line">
	<div class="mncf-profile-line-left">
		<LABEL><DESCRIPTION>
	</div>
	<div class="mncf-profile-line-right"><BEFORE><ERROR><PREFIX><ELEMENT><SUFFIX><AFTER></div>
</div>' . "\n\n";

				if ( isset( $field['#name'] ) && ( strpos($field['#name'], '[hour]') !== false || strpos($field['#name'], '[minute]') !== false ) ){
					if ( isset($field['#attributes']) && $field['#attributes']['class'] == 'mncf-repetitive'){
						$field['#pattern'] = (strpos($field['#name'], '[hour]') !== false)?__( 'Hour', 'mncf' ):__( 'Minute', 'mncf' );
						$field['#pattern'] .= '<LABEL><DESCRIPTION><ERROR><PREFIX><ELEMENT><SUFFIX><AFTER>' . "\n\n";
					}
					else{
						if (strpos($field['#name'],'[hour]')!== false){
							$field['#pattern'] = "\n".'<div class="mncf-profile-field-line">
	<div class="mncf-profile-line-left">&nbsp;&nbsp;&nbsp;&nbsp;'.__( 'Time', 'mncf' ).'</div>
	<div class="mncf-profile-line-right">
	<LABEL><DESCRIPTION><ERROR><PREFIX><ELEMENT><SUFFIX><AFTER>' . "\n";
						}
						else{
							$field['#pattern'] = "\n".'
	<LABEL><DESCRIPTION><ERROR><PREFIX><ELEMENT><SUFFIX><AFTER></div>
</div>' . "\n\n";
						}

					}

				}

				if ( !empty($echo) ){
					$field['#validate'] = '';
				}
                $output .=  mncf_form_simple( array($field['#id'] => $field) );

            }


        }
		$output .=  '</div>';
    }

    /*
     * TODO Move to Conditional code
     *
     * This is already checked. Use hook to add wrapper DIVS and apply CSS.
     */
    if ( !empty( $group['_conditional_display'] ) ) {
        $output .=  '</div>';
    }
	$output .= "\n\n" . '</div>';
	if ( !empty($echo) ){
		return $output;
	}
	else{
		echo $output;
	}
}

/**
 * Gets all groups and fields for post.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @param type $post_ID
 * @return type
 */
function mncf_admin_usermeta_get_groups_fields()
{
    mncf_enqueue_scripts();
    $post = array();
    // Filter groups
    $groups = array();

    $groups_all =  mncf_admin_fields_get_groups(TYPES_USER_META_FIELD_GROUP_CPT_NAME);

    foreach ( $groups_all as $temp_key => $temp_group ) {
        if ( empty( $temp_group['is_active'] ) ) {
            unset( $groups_all[$temp_key] );
            continue;
        }
        $passed = 1;
        if ( !$passed ) {
            unset( $groups_all[$temp_key] );
        } else {
            $groups_all[$temp_key]['fields'] = mncf_admin_fields_get_fields_by_group( $temp_group['id'],
                'slug', true, false, true, TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'mncf-usermeta');
        }
    }
    $groups = $groups_all;
    return $groups;
}


/**
 * Creates form elements.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @param type $post
 * @param type $fields
 * @return type
 */
function mncf_admin_usermeta_process_fields( $user_id, $fields = array(),
        $use_cache = true, $add_to_editor = true, $context = 'group' ) {

    global $mncf;

    $mncf->usermeta_field->use_cache = $use_cache;
    $mncf->usermeta_field->add_to_editor = $add_to_editor;
    $mncf->usermeta_repeater->use_cache = $use_cache;
    $mncf->usermeta_repeater->add_to_editor = $add_to_editor;


	if( is_object( $user_id ) ){
		$user_id = $user_id->ID;
	}

	// Get cached
    static $cache = array();
    $cache_key = !empty( $user_id ) ? $user_id . md5( serialize( $fields ) ) : false;
    if ( $use_cache && $cache_key && isset( $cache[$cache_key] ) ) {
        return $cache[$cache_key];
    }

    $fields_processed = array();
	$invalid_fields = array();



    foreach ( $fields as $field ) {

		if ( !empty( $user_id ) ) {
			$invalid_fields = update_user_meta( $user_id, 'mncf-invalid-fields', true );
			delete_user_meta( $user_id, 'mncf-invalid-fields' );
			$mncf->usermeta_field->invalid_fields = $invalid_fields;
   		}
        // Repetitive fields
        if ( mncf_admin_is_repetitive( $field ) && $context != 'post_relationship' ) {
            	$mncf->usermeta_repeater->set( $user_id, $field );
                $fields_processed = $fields_processed + $mncf->usermeta_repeater->get_fields_form(1);

        } else {


            $mncf->usermeta_field->set( $user_id, $field );


            /*
             * From Types 1.2 use complete form setup
             */
            $fields_processed = $fields_processed + $mncf->usermeta_field->_get_meta_form();
        }
    }

    // Cache results
    if ( $cache_key ) {
        $cache[$cache_key] = $fields_processed;
    }

    return $fields_processed;
}
