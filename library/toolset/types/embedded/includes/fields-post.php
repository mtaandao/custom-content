<?php

/*
 * Edit post page functions
 *
 *
 *
 * Core file with stable and working functions.
 * Please add hooks if adjustment needed, do not add any more new code here.
 *
 * Consider this file half-locked since Types 1.2
 */

// Include conditional field code
require_once MNCF_EMBEDDED_ABSPATH . '/includes/conditional-display.php';

/**
 * Init functions for post edit pages.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @param type $post
 */
function mncf_admin_post_init( $post ) {
    add_action( 'admin_footer', 'mncf_admin_fields_postfields_styles' );
    mncf_admin_add_js_settings( 'mncf_nonce_toggle_group',
            '\'' . mn_create_nonce( 'group_form_collapsed' ) . '\'' );
    mncf_admin_add_js_settings( 'mncf_nonce_toggle_fieldset',
            '\'' . mn_create_nonce( 'form_fieldset_toggle' ) . '\'' );

    // Get post_type
    $post_type = mncf_admin_get_edited_post_type( $post );

    /*
     *
     * This is left to maintain compatibility with older versions
     * TODO Remove
     */
    // Add items to View dropdown
    if ( in_array( $post_type, array('view', 'view-template', 'cred-form', 'cred-user-form') ) ) {
        add_filter( 'editor_addon_menus_mnv-views',
                'mncf_admin_post_editor_addon_menus_filter' );
        add_action( 'admin_footer', 'mncf_admin_post_js_validation' );
        mncf_enqueue_scripts();
        mn_enqueue_script( 'toolset-colorbox' );
        mn_enqueue_style( 'toolset-colorbox' );
    }
    // Never show on 'Views' and 'Content Templates'
    if ( in_array( $post_type, array('view', 'view-template') ) ) {
        return false;
    }

    /**
     * remove custom field Mtaandao metabox
     */
    if ( 'hide' == mncf_get_settings('hide_standard_custom_fields_metabox') ) {
        foreach( array( 'normal', 'advanced', 'side' ) as $context) {
            remove_meta_box('postcustom', $post_type, $context );
        }
    }

    // Are Types active?
    $mncf_active = false;

    // Get groups
    $groups = mncf_admin_post_get_post_groups_fields( $post );

    foreach ( $groups as $group ) {

        $only_preview = '';

        //If Access plugin activated
        if ( function_exists( 'mncf_access_register_caps' ) ) {
            //If user can't view own profile fields
            if ( !current_user_can( 'view_fields_in_edit_page_' . $group['slug'] ) ) {
                continue;
            }
            //If user can modify current group in own profile
            if ( !current_user_can( 'modify_fields_in_edit_page_' . $group['slug'] ) ) {
                $only_preview = 1;
            }
        }
        if ( !empty( $group['fields'] ) && empty( $only_preview ) ) {
            $mncf_active = true;
            break;
        }
    }

    // Activate scripts
    if ( $mncf_active ) {
        add_action( 'admin_head', 'mncf_post_preview_warning' );
        mncf_edit_post_screen_scripts();
    }

    // Add validation
    add_action( 'admin_footer', 'mncf_admin_post_js_validation' );

    /*
     * TODO Review
     * This is forced because of various Child cases
     * and when field are rendered via AJAX but not registered yet.
     *
     * Basically all fields that require additional JS should be added here.
     *
     * This is a must for now.
     * These fields need init JS in various cases.
     */
    mncf_field_enqueue_scripts( 'date' );
    mncf_field_enqueue_scripts( 'image' );
    mncf_field_enqueue_scripts( 'file' );
    mncf_field_enqueue_scripts( 'skype' );
    mncf_field_enqueue_scripts( 'numeric' );

    do_action( 'mncf_admin_post_init', $post_type, $post, $groups, $mncf_active );
}

/**
 * Add meta boxes.
 *
 * @param type $post_type
 * @param type $post
 * @return boolean
 */
function mncf_add_meta_boxes( $post_type, $post )
{

    $post_types_without_meta_boxes = array(
        'view',
        'view-template',
        'acf-field-group',
        'acf'
    );

    /**
     * Do not add metaboxes
     *
     * Filter allow to add post types which are immune to mncf_add_meta_boxes()
     * function.
     *
     * @since 1.9.0
     *
     * @param array $post_types array of post types slugs
     */
    $post_types_without_meta_boxes = apply_filters(
        'mncf_exclude_meta_boxes_on_post_type',
        $post_types_without_meta_boxes
    );

    /** This action is documented in embedded/bootstrap.php */
    $post_types_without_meta_boxes = apply_filters(
        'toolset_filter_exclude_own_post_types',
        $post_types_without_meta_boxes
    );

    /**
     * check
     */
    if ( !empty($post_types_without_meta_boxes) && in_array( $post_type, $post_types_without_meta_boxes ) ) {
        return false;
    }

    //  Fix for empty $post (tabify)
    if ( empty( $post->ID ) ) {
        $post = get_default_post_to_edit( $post_type, false );
    }

    // Get groups
    $groups = mncf_admin_post_get_post_groups_fields( $post );

    foreach ( $groups as $group ) {

        $only_preview = '';

        //If Access plugin activated
        if ( function_exists( 'mncf_access_register_caps' ) ) {
            //If user can't view own profile fields
            if ( !current_user_can( 'view_fields_in_edit_page_' . $group['slug'] ) ) {
                continue;
            }
            //If user can modify current group in own profile
            if ( !current_user_can( 'modify_fields_in_edit_page_' . $group['slug'] ) ) {
                $only_preview = 1;
            }
        }

        // Process fields
        if ( !empty( $group['fields'] ) && empty( $only_preview ) ) {
            if ( defined( 'MNTOOLSET_FORMS_VERSION' ) ) {
                $errors = get_post_meta( $post->ID, '__mncf-invalid-fields', true );
                $group['html'] = array();
                foreach ( $group['fields'] as $field ) {
                    mncf_admin_post_add_to_editor( $field );
	                // Note that the $single argument defaults to false, ergo $meta will be allways an array of metadata,
	                // even for single fields.
                    $meta = get_post_meta( $post->ID, $field['meta_key'] );
                    $config = mntoolset_form_filter_types_field( $field, $post->ID );
                    if ( $errors ) {
                        $config['validate'] = true;
                    }
                    if ( $field['type'] == 'wysiwyg' ) {
                        $group['html'][] = array('config' => $config, 'meta' => $meta);
                    } else {
                        $group['html'][] = mntoolset_form_field( 'post',
                                $config, $meta );
                    }
                }
                // OLD
                delete_post_meta( $post->ID, 'mncf-invalid-fields' );
                delete_post_meta( $post->ID, '__mncf-invalid-fields' );
            } else {
                // Process fields
                $group['fields'] = mncf_admin_post_process_fields( $post,
                        $group['fields'], true );
            }
        }

        // Check if hidden
        if ( !isset( $group['__show_meta_box'] ) || $group['__show_meta_box'] != false ) {

            $field_group = Types_Field_Group_Post_Factory::load( $group['slug'] );

            // Skip field groups that can't be loaded
            if( null !== $field_group ) {

                $group_mnml = new Types_Wpml_Field_Group( $field_group );

                // Add meta boxes
                if ( empty( $only_preview ) ) {
                    add_meta_box( "mncf-group-{$group['slug']}",
                        $group_mnml->translate_name(),
                        'mncf_admin_post_meta_box',
                        $post_type, $group['meta_box_context'], 'high', $group );
                } else {
                    add_meta_box( "mncf-group-{$group['slug']}",
                        $group_mnml->translate_name(),
                        'mncf_admin_post_meta_box_preview', $post_type,
                        $group['meta_box_context'], 'high', $group );
                }

            }
        }
    }
}

/**
 * Renders meta box content (preview).
 *
 *
 *
 * @param type $post
 * @param type $group
 */
function mncf_admin_post_meta_box_preview( $post, $group, $echo = '' ){

    require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields.php';
    require_once MNCF_EMBEDDED_ABSPATH . '/frontend.php';
    global $mncf;

    if ( isset( $group['args'] ) ) {
        $fields = $group['args']['fields'];
    } else {
        $fields = $group['fields'];
    }
    if ( isset( $group['slug'] ) ) {
        $slug = $group['slug'];
        $name = $group['name'];
    } else {
        $slug = $group['id'];
        $name = $group['title'];
    }
    /**
     * fake post object if need
     */
    $post = mncf_admin_create_fake_post_if_need($post);

    $group_output = '';
    if ( !empty( $echo ) ) {
        $group_output = '<h3>This Preview generated for latest post "' . $post->post_title . '"</h3>';
        $group_output .= '<div id="test-field-group" class="postbox " >
<h3 class=\'hndle\'><span>' . $name . '</span></h3>
<div class="inside">';
    }

    $group_output .= '<table class="form-table fields-preview fields-preview-' . $slug . '">
<tbody>' . "\n\n";

    $content = $code = '';
    if( !empty( $fields ) ) {
        foreach ( $fields as $field ) {
            $html = '';
            $params['separator'] = ', ';
            if ( mncf_admin_is_repetitive( $field ) ) {
                $mncf->repeater->set( $post, $field );
                $_meta = $mncf->repeater->_get_meta();
                if ( isset( $_meta['custom_order'] ) ) {
                    $meta = $_meta['custom_order'];
                } else {
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
                        if ( !empty( $params['field_value'] ) ) {
                            $html = types_render_field_single( $field, $params,
                                $content, $code, $meta_id );
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
                            if ( !empty( $params['field_value'] ) ) {
                                $temp_output = types_render_field_single( $field,
                                    $params, $content, $code, $temp_key );
                            }
                            if ( !empty( $temp_output ) ) {
                                $output[] = $temp_output;
                            }
                        }
                        if ( !empty( $output ) && isset( $params['separator'] ) ) {
                            $output = implode( html_entity_decode( $params['separator'] ),
                                $output );
                        } else if ( !empty( $output ) ) {
                            $output = implode( '', $output );
                        }
                    } else {
                        // Make sure indexed right
                        $_index = 0;
                        foreach ( $meta as $temp_key => $temp_value ) {
                            if ( $_index == $index ) {
                                $params['field_value'] = $temp_value;
                                if ( !empty( $params['field_value'] ) ) {
                                    $output = types_render_field_single( $field,
                                        $params, $content, $code, $temp_key );
                                }
                            }
                            $_index++;
                        }
                    }
                    $html = $output;
                }
            } else {
                $params['field_value'] = get_post_meta( $post->ID,
                    mncf_types_get_meta_prefix( $field ) . $field['slug'], true );

                //  $html = types_render_field_single( $field, $params, $content, $code );
                if ( !empty( $params['field_value'] ) && $field['type'] != 'date' ) {
                    $html = types_render_field_single( $field, $params, $content,
                        $code );
                }
                if ( $field['type'] == 'date' && !empty( $params['field_value'] ) ) {
                    $html = types_render_field_single( $field, $params, $content,
                        $code );
                    if ( $field['data']['date_and_time'] == 'and_time' ) {
                        $html .= ' ' . date( "H", $params['field_value'] ) . ':' . date( "i",
                                $params['field_value'] );
                    }
                }
            }

            // API filter
            $mncf->field->set( $post, $field );
            $field_value = $mncf->field->html( $html, array() );
            $group_output .= '<tr class="mncf-profile-field-line-' . $field['slug'] . '">
	<td scope="row">' . $field['name'] . '</td>
    <td>' . $field_value . '</td>
</tr>' . "\n\n";
        }
    }

    $group_output .= "\n\n</tbody>
</table>";
    if ( empty( $echo ) ) {
        echo $group_output;
    } else {
        $group_output .= "\n\n</div></div>";
        return $group_output;
    }




}

/**
 * Renders meta box content.
 *
 * @param $post
 * @param array $group
 * @param string $echo
 * @param bool $open_style_editor if true use code for open style editor when edit group
 *
 * @return string
 */
function mncf_admin_post_meta_box( $post, $group, $echo = '', $open_style_editor = false )
{

    $field_group = Types_Field_Group_Post_Factory::load( $group['args']['slug'] );
    // todo Handle null $field_group.
    $group_mnml = new Types_Wpml_Field_Group( $field_group );

    if (
        false === $open_style_editor
        && defined( 'MNTOOLSET_FORMS_VERSION' )
    ) {
        if ( isset( $group['args']['html'] ) ) {
            /**
             * show group description
             */
            if ( array_key_exists('description', $group['args'] ) && !empty($group['args']['description'])) {
                echo '<div class="mncf-meta-box-description">';
                echo mnautop( $group_mnml->translate_description() );
                echo '</div>';
            }
            foreach ( $group['args']['html'] as $field ) {
                echo is_array( $field ) ? mntoolset_form_field( 'post',
                    $field['config'], $field['meta'] ) : $field;
            }
        }
        return;
    }



    global $mncf;
    /**
     * fake post object if need
     */
    $post = mncf_admin_create_fake_post_if_need($post);

    static $nonce_added = false;
    $group_output = '';
    if ( !isset( $group['title'] ) ) {
        $temp = $group;
        $group = '';
        $group['args'] = $temp;
        $group['id'] = $temp['slug'];
        $group['title'] = $temp['name'];
        $name = $temp['name'];
    }
    if ( !empty( $echo ) ) {
        $group_output = '<h3>This Preview generated for latest post "' . $post->post_title . '"</h3>' . "\n" .
            '<!-- Previous lines visible only in Admin Style Editor.-->' . "\n\n";
        $group_output .= '<div id="mncf-group-' . $group['id'] . '" class="postbox " >
            <h3 class=\'hndle\'><span>' . $name . '</span></h3>
            <div class="inside">' . "\n";
    }


    /*
     * TODO Document where this is used
     */
    if ( !$nonce_added && empty( $echo ) ) {
        $nonce_action = 'update-' . $post->post_type . '_' . $post->ID;
        mn_nonce_field( $nonce_action, '_mncf_post_mnnonce' );
        $nonce_added = true;
    }
    $group_output .= "\n\n" . '<div id="mncf-group-metabox-id-' . $group['args']['slug'] . '">' . "\n";
    /*
     * TODO Move to Conditional code
     *
     * This is already checked. Use hook to add wrapper DIVS and apply CSS.
     */
    if ( !empty( $group['args']['_conditional_display'] ) ) {
        if ( $group['args']['_conditional_display'] == 'failed' ) {
            $group_output .= '<div class="mncf-cd-group mncf-cd-group-failed" style="display:none;">';
        } else {
            $group_output .= '<div class="mncf-cd-group mncf-cd-group-passed">';
        }
    }


    /*
     * TODO Move this into Field code
     * Process fields
     */
    if ( !empty( $group['args']['fields'] ) ) {
        // Display description
        if ( !empty( $group['args']['description'] ) ) {
            $group_output .= '<div class="mncf-meta-box-description">'
                . mnautop( $group_mnml->translate_description() ) . '</div>';
        }
        foreach ( $group['args']['fields'] as $field_slug => $field ) {
            if ( empty( $field ) || !is_array( $field ) ) {
                continue;
            }

            $field = $mncf->field->_parse_cf_form_element( $field );

            if ( isset( $field['mncf-type'] ) ) { // May be ignored
                $field = apply_filters( 'mncf_fields_' . $field['mncf-type'] . '_meta_box_form_value_display',
                    $field );
            }

            if ( !isset( $field['#id'] ) ) {
                $field['#id'] = mncf_unique_id( serialize( $field ) );
            }
            // Render form elements
            if (
                mncf_compare_mn_version()
                && array_key_exists( '#type', $field )
                && 'wysiwyg' == $field['#type']
                && !isset( $field['#attributes']['disabled'] )
            ) {
                //                if ( isset( $field['#attributes']['disabled'] ) ) {
                //                    $field['#editor_settings']['tinymce'] = false;
                //                    $field['#editor_settings']['teeny'] = false;
                //                    $field['#editor_settings']['media_buttons'] = false;
                //                    $field['#editor_settings']['quicktags'] = false;
                //                    $field['#editor_settings']['dfw'] = false;
                //                }
                // Especially for WYSIWYG
                $group_output .= '<div class="mncf-wysiwyg">';
                $group_output .= '<div id="mncf-textarea-textarea-wrapper" class="form-item form-item-textarea mncf-form-item mncf-form-item-textarea">';
                $group_output .= isset( $field['#before'] ) ? $field['#before'] : '';
                $group_output .= '<label class="mncf-form-label mncf-form-textarea-label">'
                    . stripslashes( $field['#title'] ) . '</label>';
                $group_output .= '<div class="description mncf-form-description mncf-form-description-textarea description-textarea">
                    ' . mnautop( $field['#description'] ) . '</div>';
                ob_start();
                mn_editor( $field['#value'], $field['#id'],
                    $field['#editor_settings'] );
                $group_output .= ob_get_clean() . "\n\n";
                $field['slug'] = str_replace( MNCF_META_PREFIX . 'wysiwyg-', '',
                    $field_slug );
                $field['type'] = 'wysiwyg';
                $group_output .= '</div>';
                $group_output .= isset( $field['#after'] ) ? $field['#after'] : '';
                $group_output .= '</div>';
            } else {
                if (
                    array_key_exists( '#type', $field )
                    && 'wysiwyg' == $field['#type']
                ) {
                    $field['#type'] = 'textarea';
                }
                if ( !empty( $echo ) ) {
                    $field['#validate'] = '';
                }
                $group_output .= mncf_form_simple( array($field['#id'] => $field) );
            }
            do_action( 'mncf_fields_' . $field_slug . '_meta_box_form', $field );
            if ( isset( $field['mncf-type'] ) ) { // May be ignored
                do_action( 'mncf_fields_' . $field['mncf-type'] . '_meta_box_form', $field );
            }
        }
    }

    /*
     * TODO Move to Conditional code
     *
     * This is already checked. Use hook to add wrapper DIVS and apply CSS.
     */
    if ( !empty( $group['args']['_conditional_display'] ) ) {
        $group_output .= '</div>';
    }

    $group_output .= '</div>';

    if ( !empty( $echo ) ) {
        $group_output .= "\n\n</div></div>";
        return $group_output;
    } else {
        echo $group_output;
    }
}

/**
 * The save_post hook.
 *
 * @param int $post_ID
 * @param MN_Post $post
 * @since unknown
 */
function mncf_admin_post_save_post_hook( $post_ID, $post ) {

	// Apparently, some things are *slightly* different when saving a child post in the Post relationships metabox.
	// Ugly hack that will go away with m2m.
	//
	// Note: The types_updating_child_post filter is needed for a situation when user clicks on the Update button
	// for the parent post. In that case we don't get enough information to determine if a child post is being updated.
	$is_child_post_update = (
		( 'mncf_ajax' == mncf_getget( 'action' ) && 'pr_save_child_post' == mncf_getget( 'mncf_action' ) )
		|| apply_filters( 'types_updating_child_post', false )
	);

	global $mncf;
	$errors = false;

	// Do not save post if is type of
	if ( in_array( $post->post_type,
		array(
			'revision',
			'view',
			'view-template',
			'cred-form',
			'cred-user-form',
			'nav_menu_item',
			'mediapage'
		) ) )
	{
		return;
	}

	$_post_mncf = array();
	if ( ! empty( $_POST['mncf'] ) ) {
		$_post_mncf = $_POST['mncf'];
	}

	// handle checkbox
	if ( array_key_exists( '_mntoolset_checkbox', $_POST ) && is_array( $_POST['_mntoolset_checkbox'] ) ) {
		foreach ( $_POST['_mntoolset_checkbox'] as $key => $field_value ) {
			$field_slug = preg_replace( '/^mncf\-/', '', $key );
			if ( array_key_exists( $field_slug, $_post_mncf ) ) {
				continue;
			}
			$_post_mncf[ $field_slug ] = false;
		}
	}

	// handle radios
	if ( array_key_exists( '_mntoolset_radios', $_POST ) && is_array( $_POST['_mntoolset_radios'] ) ) {
		foreach ( $_POST['_mntoolset_radios'] as $key => $field_value ) {
			$field_slug = preg_replace( '/^mncf\-/', '', $key );
			if ( array_key_exists( $field_slug, $_post_mncf ) ) {
				continue;
			}
			$_post_mncf[ $field_slug ] = false;
		}
	}


	if ( count( $_post_mncf ) ) {
		$add_error_message = true;
		if ( isset( $_POST['post_id'] ) && $_POST['post_id'] != $post_ID ) {
			$add_error_message = false;
		}

		// check some attachment to delete
		$delete_attachments = apply_filters( 'mncf_delete_attachments', true );
		$images_to_delete = array();
		if ( $delete_attachments && isset( $_POST['mncf'] ) && array_key_exists( 'delete-image', $_POST['mncf'] ) && is_array( $_POST['mncf']['delete-image'] ) ) {
			foreach ( $_POST['mncf']['delete-image'] as $image ) {
				$images_to_delete[ $image ] = 1;
			}
		}
		foreach ( $_post_mncf as $field_slug => $field_value ) {
			// Get field by slug
			$field = mncf_fields_get_field_by_slug( $field_slug );
			if ( empty( $field ) ) {
				continue;
			}
			// Skip copied fields
			if ( isset( $_POST['mncf_repetitive_copy'][ $field['slug'] ] ) ) {
				continue;
			}

			// This is (apparently) expected for repetitive fields...
			if ( $is_child_post_update && types_is_repetitive( $field ) ) {
				$field_value = array( $field_value );
			}

			$_field_value = ! types_is_repetitive( $field ) ? array( $field_value ) : $field_value;

			// Set config
			$config = mntoolset_form_filter_types_field( $field, $post_ID, $_post_mncf );

			// remove from images_to_delete if user add again
			if ( $delete_attachments && 'image' == $config['type'] ) {
				$images = $_field_value;
				if ( ! is_array( $images ) ) {
					$images = array( $images );
				}
				foreach ( $images as $image ) {
					if ( array_key_exists( $image, $images_to_delete ) ) {
						unset( $images_to_delete[ $image ] );
					}
				}
			}

			// add filter to remove field name from error message
			// This filter is toolset-common/toolset-forms/classes/class.validation.php
			add_filter( 'toolset_common_validation_add_field_name_to_error', '__return_false', 1234, 1 );
			foreach ( $_field_value as $_k => $_val ) {
				// Check if valid
				$validation = mntoolset_form_validate_field( 'post', $config, $_val );
				$conditional = mntoolset_form_conditional_check( $config );
				$not_valid = is_mn_error( $validation ) || ! $conditional;
				if ( $add_error_message && is_mn_error( $validation ) ) {
					$errors = true;
					$_errors = $validation->get_error_data();
					$_msg = sprintf( __( 'Field "%s" not updated:', 'mncf' ), $field['name'] );
					mncf_admin_message_store( $_msg . ' ' . implode( ', ', $_errors ), 'error' );
				}
				if ( $not_valid ) {
					if ( types_is_repetitive( $field ) ) {
						unset( $field_value[ $_k ] );
					} else {
						break;
					}
				}
			}
			remove_filter( 'toolset_common_validation_add_field_name_to_error', '__return_false', 1234, 1 );
			// Save field
			if ( types_is_repetitive( $field ) ) {
				$mncf->repeater->set( $post_ID, $field );
				$mncf->repeater->save( $field_value );
			} else if ( ! $not_valid ) {
				$mncf->field->set( $post_ID, $field );
				$mncf->field->save( $field_value );
			}
			do_action( 'mncf_post_field_saved', $post_ID, $field );
			// TODO Move to checkboxes
			if ( $field['type'] == 'checkboxes' ) {
				if ( ! empty( $field['data']['options'] ) ) {
					$update_data = array();
					foreach ( $field['data']['options'] as $option_id => $option_data ) {
						if ( ! isset( $_POST['mncf'][ $field['id'] ][ $option_id ] ) ) {
							if ( isset( $field['data']['save_empty'] ) && $field['data']['save_empty'] == 'yes' ) {
								$update_data[ $option_id ] = 0;
							}
						} else {
							$update_data[ $option_id ] = $_POST['mncf'][ $field['id'] ][ $option_id ];
						}
					}
					update_post_meta( $post_ID, $field['meta_key'], $update_data );
				}
			}
		}

		// delete images
		if ( $delete_attachments && ! empty( $images_to_delete ) ) {
			$args = array(
				'post_parent' => $post_ID,
				'posts_per_page' => - 1,
			);
			$children_array = get_children( $args );
			foreach ( $children_array as $child ) {
				if ( ! array_key_exists( $child->guid, $images_to_delete ) ) {
					continue;
				}
				mn_delete_attachment( $child->ID );
			}
		}

	}
	if ( $errors ) {
		update_post_meta( $post_ID, '__mncf-invalid-fields', true );
	}
	do_action( 'mncf_post_saved', $post_ID );

	return;
}


/**
 *
 * Only for attachments, only default checkboxes!
 *
 * @internal breakpoint
 * @param type $post_ID
 * @param type $post
 */
function mncf_admin_post_add_attachment_hook( $post_ID, $post )
{
    global $mncf;
    /**
     * Basic check: only attachment
     */
    if ( 'attachment' !=  $post->post_type ) {
        return false;
    }

    /**
     * Get all groups connected to this $post
     */
    $groups = mncf_admin_post_get_post_groups_fields( $post );
    if ( empty( $groups ) ) {
        return false;
    }
    $all_fields = array();
    $_not_valid = array();
    $_error = false;


    /**
     * Loop over each group
     *
     * TODO Document this
     * Connect 'mncf-invalid-fields' with all fields
     */
    foreach ( $groups as $group ) {
        if ( isset( $group['fields'] ) ) {
            // Process fields
            $fields = mncf_admin_post_process_fields( $post, $group['fields'],
                true, false, 'validation' );
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
    }
    // Set fields
    foreach ( $all_fields as $k => $v ) {
        // only Types field
        if ( empty( $v['mncf-id'] ) ) {
            continue;
        }
        $_temp = new MNCF_Field();
        $_temp->set( $mncf->post, $v['mncf-id'] );
        $all_fields[$k]['_field'] = $_temp;
    }
    foreach ( $_not_valid as $k => $v ) {
        // only Types field
        if ( empty( $v['mncf-id'] ) ) {
            continue;
        }
        $_temp = new MNCF_Field();
        $_temp->set( $mncf->post, $v['mncf-id'] );
        $_not_valid[$k]['_field'] = $_temp;
    }

    /**
     * Process all checkbox fields
     */

    foreach ( $all_fields as $field ) {
        /**
         * only checkbox
         */
        if ( !isset( $field['#type'] ) || 'checkbox' != $field['#type'] ) {
            continue;
        }
        $field_data = mncf_admin_fields_get_field( $field['mncf-id'] );
        /**
         * check is checked for new!
         */
        $checked = array_key_exists( 'checked', $field_data['data'] ) && $field_data['data']['checked'];
        /**
         * do not save empty and not checked? fine, go away...
         */
        if ( 'no' == $field_data['data']['save_empty'] && !$checked ) {
            continue;
        }
        /**
         * all other just save...
         */
        $update_data = 0;
        if ( $checked ) {
            $update_data = $field_data['data']['set_value'];
        }
        add_post_meta( $post_ID, mncf_types_get_meta_prefix( $field ) . $field['mncf-slug'], $update_data );
    }
    do_action( 'mncf_attachement_add', $post_ID );
}
/**
 * Renders JS validation script.
 */
function mncf_admin_post_js_validation() {
    mncf_form_render_js_validation( '#post' );
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
function mncf_admin_post_process_fields( $post = false, $fields = array(),
        $use_cache = true, $add_to_editor = true, $context = 'group' ) {

    global $mncf;

    $mncf->field->use_cache = $use_cache;
    $mncf->field->add_to_editor = $add_to_editor;
    $mncf->repeater->use_cache = $use_cache;
    $mncf->repeater->add_to_editor = $add_to_editor;

    // Get cached
    static $cache = array();
    $cache_key = !empty( $post->ID ) ? $post->ID . md5( serialize( $fields ) ) : false;
    if ( $use_cache && $cache_key && isset( $cache[$cache_key] ) ) {
        return $cache[$cache_key];
    }

    $fields_processed = array();

    // Get invalid fields (if submitted)
    $invalid_fields = array();
    if ( !empty( $post->ID ) ) {
        $invalid_fields = get_post_meta( $post->ID, 'mncf-invalid-fields', true );
        delete_post_meta( $post->ID, 'mncf-invalid-fields' );

        /*
         *
         * Add it to global $mncf
         * From now on take it there.
         */
        $mncf->field->invalid_fields = $invalid_fields;
    }

    // TODO MNML Get MNML original fields
    $original_cf = array();
    if ( function_exists( 'mnml_get_copied_fields_for_post_edit' ) && !mncf_mnml_post_is_original( $post ) ) {
        $__fields_slugs = array();
        foreach ( $fields as $_f ) {
            $__fields_slugs[] = $_f['meta_key'];
        }
        $original_cf = mnml_get_copied_fields_for_post_edit( $__fields_slugs );
    }

    if( !empty( $fields ) ) {
        foreach( $fields as $field ) {

            // Repetitive fields
            if( mncf_admin_is_repetitive( $field ) && $context != 'post_relationship' ) {
                // First check if repetitive fields are copied using MNML
                /*
				 * TODO All MNML specific code needs moving to
				 * /embedded/includes/mnml.php
				 *
				 * @since Types 1.2
				 */
                // TODO MNML move
                if( ! empty( $original_cf['fields'] ) && in_array( mncf_types_get_meta_prefix( $field ) . $field['slug'],
                        $original_cf['fields'] )
                ) {
                    /*
					 * See if repeater can handle copied fields
					 */
                    $mncf->repeater->set( get_post( $original_cf['original_post_id'] ),
                        $field );
                    $fields_processed = $fields_processed + $mncf->repeater->get_fields_form();
                } else {
                    // Set repeater
                    /*
					 *
					 *
					 * @since Types 1.2
					 * Now we're using repeater class to handle repetitive forms.
					 * Main change is - use form from $field_meta_box_form() without
					 * re-processing form elements.
					 *
					 * Field should pass form as array:
					 * 'my_checkbox' => array('#type' => 'checkbox' ...),
					 * 'my_textfield' => array('#type' => 'textfield' ...),
					 *
					 * In form it should set values to be stored.
					 * Use hooks to adjust saved data.
					 */
                    $mncf->repeater->set( $post, $field );
                    $fields_processed = $fields_processed + $mncf->repeater->get_fields_form();
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
				 * Non-repetitive fields
				 */
            } else {

                /*
				 * meta_form will be treated as normal form.
				 * See if any obstacles prevent us from using completed
				 * form from config files.
				 *
				 * Main change is - use form from $field_meta_box_form() without
				 * re-processing form elements.
				 *
				 * Field should pass form as array:
				 * 'my_checkbox' => array('#type' => 'checkbox' ...),
				 * 'my_textfield' => array('#type' => 'textfield' ...),
				 *
				 * In form it should set values to be stored.
				 * Use hooks to adjust saved data.
				 */
                $mncf->field->set( $post, $field );

                // TODO MNML move Check if repetitive field is copied using MNML
                if( ! empty( $original_cf['fields'] ) ) {
                    if( in_array( $mncf->field->slug, $original_cf['fields'] ) ) {
                        // Switch to parent post
                        $mncf->field->set( get_post( $original_cf['original_post_id'] ),
                            $field );
                    }
                }
                /*
				 * From Types 1.2 use complete form setup
				 */
                $fields_processed = $fields_processed + $mncf->field->_get_meta_form();
            }
        }
    }

    // Cache results
    if ( $cache_key ) {
        $cache[$cache_key] = $fields_processed;
    }

    return $fields_processed;
}

/**
 * Processes single field.
 *
 * Since Types 1.2 this function changed. It handles single form element.
 * Form element is already fetched, also meta values using class MNCF_Field.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @todo gradually remove usage of inherited fields
 * @todo Cleanup
 *
 * @staticvar array $repetitive_started
 * @param type $field_object
 * @return mixed boolean|array
 */
function mncf_admin_post_process_field( $field_object ) {

    /*
     * Since Types 1.2
     * All data we need is stored in global $mncf
     */
    global $mncf;
    $post = $mncf->post;
    $field = (array) $field_object->cf;
    $context = $field_object->context;
    $invalid_fields = $mncf->field->invalid_fields;

    if ( !empty( $field ) ) {
        /*
         * Set Unique ID
         */
        $field_id = 'mncf-' . $field['type'] . '-' . $field['slug'] . '-'
                . mncf_unique_id( serialize( $field_object->__current_form_element ) );

        /*
         * Get inherited field
         *
         * TODO Deprecated
         *
         * Since Types 1.2 we encourage developers to completely define fields.
         */
        $inherited_field_data = false;
        if ( isset( $field_object->config->inherited_field_type ) ) {
            $_allowed = array(
                'image' => 'file',
                'numeric' => 'textfield',
                'email' => 'textfield',
                'phone' => 'textfield',
                'url' => 'textfield',
            );
            if ( !array_key_exists( $field_object->cf['type'], $_allowed ) ) {
//                _deprecated_argument( 'inherited_field_type', '1.2',
//                        'Since Types 1.2 we encourage developers to completely define fields' );
            }
            $inherited_field_data = mncf_fields_type_action( $field_object->config->inherited_field_type );
        }



        /*
         * CHECKPOINT
         * APPLY FILTERS
         *
         *
         * Moved to MNCF_Field
         * Field value should be already filtered
         *
         * Explanation:
         * When MNCF_Field::set() is called, all these properties are set.
         * MNCF_Field::$cf['value']
         * MNCF_Field::$__meta (single value from DB)
         * MNCF_Field::$meta (single or multiple values if single/repetitive)
         *
         * TODO Make sure value is already filtered and not overwritten
         */

        /*
         * Set generic values
         *
         * FUTURE BREAKPOINT
         * Since Types 1.2 we do not encourage relying on generic field data.
         * Only core fields should use this.
         *
         * TODO Open 3rd party fields dir
         */
        $_element = array(
            '#type' => isset( $field_object->config->inherited_field_type ) ? $field_object->config->inherited_field_type : $field['type'],
            '#id' => $field_id,
            '#title' => $field['name'],
            '#name' => 'mncf[' . $field['slug'] . ']',
            '#value' => isset( $field['value'] ) ? $field['value'] : '',
            'mncf-id' => $field['id'],
            'mncf-slug' => $field['slug'],
            'mncf-type' => $field['type'],
        );

        /*
         * TODO Add explanation about creating duplicated fields
         *
         * NOT USED YET
         *
         * Explain users that fields are added if slug is changed
         */
        mncf_admin_add_js_settings( 'mncfFieldNewInstanceWarning',
                __( 'If you change slug, new field will be created', 'mncf' ) );

        /*
         * Merge with default element
         *
         * Deprecated from Types 1.2
         * Only core fields use this.
         */
        $element = array_merge( $_element, $field_object->__current_form_element );


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
         * TODO From this point code should be simplified.
         */

        if ( isset( $field['description_extra'] ) ) {
            $element['#description'] .= mnautop( $field['description_extra'] );
        }

        // Set atributes #1
        if ( isset( $field['disable'] ) ) {
            $field['#disable'] = $field['disable'];
        }
        if ( !empty( $field['disable'] ) ) {
            $field['#attributes']['disabled'] = 'disabled';
        }
        if ( !empty( $field['readonly'] ) ) {
            $field['#attributes']['readonly'] = 'readonly';
        }

        // Format description
        if ( !empty( $element['#description'] ) ) {
            $element['#description'] = mnautop( $element['#description'] );
        }

        // Set validation element
        if ( isset( $field['data']['validate'] ) ) {
            /*
             *
             *
             * TODO First two check are not needed anymore
             */
            // If array has more than one field - see which one is marked
            if ( $field_object->__multiple && isset( $element['#_validate_this'] ) ) {
                $element['#validate'] = $field['data']['validate'];
            } else if ( !$field_object->__multiple ) {
                $element['#validate'] = $field['data']['validate'];
            }
        }

        // Set atributes #2 (override)
        if ( isset( $field['disable'] ) ) {
            $element['#disable'] = $field['disable'];
        }
        if ( !empty( $field['disable'] ) ) {
            $element['#attributes']['disabled'] = 'disabled';
        }
        if ( !empty( $field['readonly'] ) ) {
            $element['#attributes']['readonly'] = 'readonly';
            if ( !empty( $element['#options'] ) ) {
                foreach ( $element['#options'] as $key => $option ) {
                    if ( !is_array( $option ) ) {
                        $element['#options'][$key] = array(
                            '#title' => $key,
                            '#value' => $option,
                        );
                    }
                    $element['#options'][$key]['#attributes']['readonly'] = 'readonly';
                    if ( $element['#type'] == 'select' ) {
                        $element['#options'][$key]['#attributes']['disabled'] = 'disabled';
                    }
                }
            }
            if ( $element['#type'] == 'select' ) {
                $element['#attributes']['disabled'] = 'disabled';
            }
        }

        // Check if it was invalid on submit and add error message
        if ( $post && !empty( $invalid_fields ) ) {
            if ( isset( $invalid_fields[$element['#id']]['#error'] ) ) {
                $element['#error'] = $invalid_fields[$element['#id']]['#error'];
            }
        }

        // TODO MNML move Set MNML locked icon
        if ( mncf_mnml_field_is_copied( $field ) ) {
            $element['#title'] .= '<img src="' . MNCF_EMBEDDED_RES_RELPATH . '/images/locked.png" alt="'
                    . __( 'This field is locked for editing because MNML will copy its value from the original language.', 'mncf' ) . '" title="'
                    . __( 'This field is locked for editing because MNML will copy its value from the original language.', 'mncf' ) . '" style="position:relative;left:2px;top:2px;" />';
        }

        // Add repetitive class
        // TODO MNML move
        if ( types_is_repetitive( $field ) && $context != 'post_relationship' && mncf_mnml_field_is_copied( $field ) ) {
            if ( !empty( $element['#options'] ) && $element['#type'] != 'select' ) {
                foreach ( $element['#options'] as $temp_key => $temp_value ) {
                    $element['#options'][$temp_key]['#attributes']['class'] = isset( $element['#attributes']['class'] ) ? $element['#attributes']['class'] . ' mncf-repetitive' : 'mncf-repetitive';
                }
            } else {
                $element['#attributes']['class'] = isset( $element['#attributes']['class'] ) ? $element['#attributes']['class'] . ' mncf-repetitive' : 'mncf-repetitive';
            }
            /*
             *
             *
             * Since Types 1.2 we allow same field values
             *
             * TODO Remove
             *
             * mncf_admin_add_js_settings('mncfFormRepetitiveUniqueValuesCheckText',
              '\'' . __('Warning: same values set', 'mncf') . '\'');
             */
        }

        // Set read-only if copied by MNML
        // TODO MNML Move this to separate MNML code and use only hooks 1.1.5
        if ( mncf_mnml_field_is_copied( $field ) ) {
            if ( isset( $element['#options'] ) ) {
                foreach ( $element['#options'] as $temp_key => $temp_value ) {
                    if ( isset( $temp_value['#attributes'] ) ) {
                        $element['#options'][$temp_key]['#attributes']['readonly'] = 'readonly';
                    } else {
                        $element['#options'][$temp_key]['#attributes'] = array('readonly' => 'readonly');
                    }
                }
            }
            if ( $field['type'] == 'select' ) {
                if ( isset( $element['#attributes'] ) ) {
                    $element['#attributes']['disabled'] = 'disabled';
                } else {
                    $element['#attributes'] = array('disabled' => 'disabled');
                }
            } else {
                if ( isset( $element['#attributes'] ) ) {
                    $element['#attributes']['readonly'] = 'readonly';
                } else {
                    $element['#attributes'] = array('readonly' => 'readonly');
                }
            }
        }

        // Final filter for disabled if readonly
        if ( isset( $element['#attributes']['readonly'] ) || isset( $element['#attributes']['disabled'] ) ) {
            if ( types_is_repetitive( $field ) ) {
                $element['#name'] .= '[]';
            }
            if ( $field['type'] == 'checkboxes' ) {
                if ( isset( $element['#options'] ) ) {
                    foreach ( $element['#options'] as $temp_key => $temp_value ) {
                        $value = isset( $temp_value['#default_value'] ) ? $temp_value['#default_value'] : $temp_value['#value'];
                        $_after = "<input type=\"hidden\" name=\"{$temp_value['#name']}\" value=\"{$value}\" />";
                        $temp_value['#after'] = isset( $temp_value['#after'] ) ? $temp_value['#after'] . $_after : $_after;
                        $temp_value['#name'] = "mncf-disabled[{$field['id']}_{$temp_value['#id']}]";
                        $temp_value['#attributes']['disabled'] = 'disabled';
                        $element['#options'][$temp_key] = $temp_value;
                    }
                }
            } else if ( in_array( $element['#type'],
                            array('checkbox', 'checkboxes', 'radios') ) ) {
                if ( isset( $element['#options'] ) ) {
                    foreach ( $element['#options'] as $temp_key => $temp_value ) {
                        $element['#options'][$temp_key]['#attributes']['disabled'] = 'disabled';
                    }
                }
                $value = isset( $element['#default_value'] ) ? $element['#default_value'] : $element['#value'];
                $_after = "<input type=\"hidden\" name=\"{$element['#name']}\" value=\"{$value}\" />";
                $element['#after'] = isset( $element['#after'] ) ? $element['#after'] . $_after : $_after;
                $element['#attributes']['disabled'] = 'disabled';
                $element['#name'] = "mncf-disabled[{$field['id']}_{$element['#id']}]";
            } else {
                $element['#attributes']['disabled'] = 'disabled';
                if ( is_array( $element['#value'] ) ) {//$field['type'] == 'skype' ) {
                    $element['#value'] = array_shift( $element['#value'] );
                }
                $value = htmlentities( $element['#value'] );
                $_after = "<input type=\"hidden\" name=\"{$element['#name']}\" value=\"{$value}\" />";
                $element['#after'] = isset( $element['#after'] ) ? $element['#after'] . $_after : $_after;
                $element['#name'] = "mncf-disabled[{$field['id']}_{$element['#id']}]";
            }
        }
        return array('field' => $field, 'element' => $element);
    }
    return false;
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
function mncf_admin_post_get_post_groups_fields( $post = false, $context = 'group' )
{
    // Get post_type
    /*
     *
     *
     * Since MN 3.5 | Types 1.2
     * Looks like $post is altered with get_post_type()
     * We do not want that if post is already set
     */
    if ( !empty( $post->post_type ) ) {
        $post_type = $post->post_type;
    } else if ( !empty( $post ) ) {
        $post_type = get_post_type( $post );
    } else {
        if ( !isset( $_GET['post_type'] ) ) {
            $post_type = 'post';
        } else if ( in_array( $_GET['post_type'], get_post_types( array('show_ui' => true) ) ) ) {
            $post_type = sanitize_text_field( $_GET['post_type'] );
        } else {
            $post_type = 'post';
        }
    }

    // Get post terms
    $support_terms = false;
    if ( !empty( $post ) ) {
        $post->_mncf_post_terms = array();
        $taxonomies = get_taxonomies( '', 'objects' );
        if ( !empty( $taxonomies ) ) {
            foreach ( $taxonomies as $tax_slug => $tax ) {
                $temp_tax = get_taxonomy( $tax_slug );
                if ( !in_array( $post_type, $temp_tax->object_type ) ) {
                    continue;
                }
                $support_terms = true;
                $terms = mn_get_post_terms( $post->ID, $tax_slug,
                        array('fields' => 'all') );
                foreach ( $terms as $term_id ) {
                    $post->_mncf_post_terms[] = $term_id->term_taxonomy_id;
                }
            }
        }
    }

    // Get post template
    if ( empty( $post ) ) {
        $post = new stdClass();
        $post->_mncf_post_template = false;
        $post->_mncf_post_views_template = false;
    } else {
        $post->_mncf_post_template = get_post_meta( $post->ID, '_mn_page_template', true );
        $post->_mncf_post_views_template = get_post_meta( $post->ID, '_views_template', true );
    }

    if ( empty( $post->_mncf_post_terms ) ) {
        $post->_mncf_post_terms = array();
    }

    // Filter groups
    $groups = array();
    $groups_all = apply_filters( 'mncf_post_groups_all', mncf_admin_fields_get_groups(), $post, $context );
    foreach ( $groups_all as $temp_key => $temp_group ) {
        if ( empty( $temp_group['is_active'] ) ) {
            unset( $groups_all[$temp_key] );
            continue;
        }
        // Get filters
        // Post Types
        $groups_all[$temp_key]['_mn_types_group_post_types'] = explode( ',',
                trim( get_post_meta( $temp_group['id'],
                                '_mn_types_group_post_types', true ), ',' ) );

        // Taxonomies
        $groups_all[$temp_key]['_mn_types_group_terms'] = explode( ',',
                trim( get_post_meta( $temp_group['id'], '_mn_types_group_terms',
                                true ), ',' ) );

        // Templates
        $groups_all[$temp_key]['_mn_types_group_templates'] = explode( ',',
                trim( get_post_meta( $temp_group['id'],
                                '_mn_types_group_templates', true ), ',' ) );

        // Data-Dependant
        $groups_all[$temp_key]['_mncf_conditional_display'] =
            get_post_meta( $temp_group['id'], '_mncf_conditional_display' );

        $post_type_filter = $groups_all[$temp_key]['_mn_types_group_post_types'][0] == 'all'
                            || empty( $groups_all[$temp_key]['_mn_types_group_post_types'][0] )
            ? -1
            : 0;
        $taxonomy_filter = $groups_all[$temp_key]['_mn_types_group_terms'][0] == 'all'
                            || empty( $groups_all[$temp_key]['_mn_types_group_terms'][0] )
            ? -1
            : 0;
        $template_filter = $groups_all[$temp_key]['_mn_types_group_templates'][0] == 'all'
                           || empty( $groups_all[$temp_key]['_mn_types_group_templates'][0] )
            ? -1
            : 0;

        $groups_all[$temp_key] = apply_filters( 'mncf_post_group_filter_settings', $groups_all[$temp_key], $post, $context, $post->_mncf_post_terms );

        // See if post type matches
        if ( $post_type_filter == 0 && in_array( $post_type,
                $groups_all[$temp_key]['_mn_types_group_post_types'] ) ) {
            $post_type_filter = 1;
        }

        // See if terms match
        if ( $taxonomy_filter == 0 ) {
            foreach ( $post->_mncf_post_terms as $temp_post_term ) {
                if ( in_array( $temp_post_term,
                    $groups_all[$temp_key]['_mn_types_group_terms'] ) ) {
                    $taxonomy_filter = 1;
                }
            }
        }

        // See if template match
        if ( $template_filter == 0 ) {
            if ( (!empty( $post->_mncf_post_template ) && in_array( $post->_mncf_post_template,
                        $groups_all[$temp_key]['_mn_types_group_templates'] )) || (!empty( $post->_mncf_post_views_template ) && in_array( $post->_mncf_post_views_template,
                        $groups_all[$temp_key]['_mn_types_group_templates'] )) ) {
                $template_filter = 1;
            }
        }

        /**
         * if ALL must met
         */
        if( isset( $groups_all[$temp_key]['filters_association'] )
            && $groups_all[$temp_key]['filters_association'] == 'all' ) {

            // if no conditions are set, do not display the group
            // - this is important because ounce filter association is set to "all"
            //   it's not unset even if the user removes all conditions
            if( $post_type_filter == 0
                && $template_filter == 0
                && $taxonomy_filter == -1
                && empty( $groups_all[$temp_key]['_mncf_conditional_display'] ) ) {
                unset( $groups_all[$temp_key] );

            // there are conditions set
            } else {
                // post types
                // accepted if none isset || one has to match (proofed above)
                if( empty( $groups_all[$temp_key]['_mn_types_group_post_types'][0] ) )
                    $post_type_filter = 1;

                // taxonomies
                // none isset || all have to match
                if( empty( $groups_all[$temp_key]['_mn_types_group_terms'][0] )
                    || $groups_all[$temp_key]['_mn_types_group_terms'][0] == 'all' ) {
                    $taxonomy_filter = 1;
                } else {
                    $taxonomy_filter = 1;
                    // check all terms which need to be active
                    foreach ( $groups_all[$temp_key]['_mn_types_group_terms'] as $term_has_to_match ) {
                        // break on first term not active
                        if( ! in_array( $term_has_to_match, $post->_mncf_post_terms ) ) {
                            $taxonomy_filter = 0;
                            break;
                        }
                    }
                }

                // templates
                // one has to match  (proofed above) || none isset
                if( empty( $groups_all[$temp_key]['_mn_types_group_templates'][0] ) )
                    $template_filter = 1;
            }
        }

        // Filter by association
        if ( empty( $groups_all[$temp_key]['filters_association'] ) ) {
            $groups_all[$temp_key]['filters_association'] = 'any';
        }
        // If context is post_relationship allow all groups that match post type
        if ( $context == 'post_relationships_header' ) {
            $groups_all[$temp_key]['filters_association'] = 'any';
        }

        if ( $post_type_filter == -1 && $taxonomy_filter == -1 && $template_filter == -1 ) {
            $passed = 1;
        } else if ( $groups_all[$temp_key]['filters_association'] == 'any' ) {
            $passed = $post_type_filter == 1 || $taxonomy_filter == 1 || $template_filter == 1;
        } else {
            $passed = $post_type_filter != 0 && $taxonomy_filter != 0 && $template_filter != 0;
        }
        if ( !$passed ) {
            unset( $groups_all[$temp_key] );
        } else {
            $groups_all[$temp_key]['fields'] = mncf_admin_fields_get_fields_by_group( $temp_group['id'],
                    'slug', true, false, true );
        }
    }
    $groups = apply_filters( 'mncf_post_groups', $groups_all, $post, $context );
    return $groups;
}

/**
 * Stores fields for editor menu.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @staticvar array $fields
 * @param type $field
 * @return array
 */
function mncf_admin_post_add_to_editor( $field ) {
    static $fields = array();
    if ( $field == 'get' ) {
        return $fields;
    }
    if (
		empty( $fields )
		&& ! (
			apply_filters( 'toolset_is_views_available', false )
			|| apply_filters( 'toolset_is_views_embedded_available', false )
		)
	) {
        add_action( 'admin_enqueue_scripts', 'mncf_admin_post_add_to_editor_js' );
    }
    $fields[$field['id']] = $field;
}

/**
 * Renders JS for editor menu.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @return type
 */
function mncf_admin_post_add_to_editor_js() {

    // since 1.3 we do not use global $post
    $post = mncf_admin_get_edited_post();
    if ( empty( $post ) ) {
        $post = (object) array('ID' => -1);
        $post->post_type = false;
    }

    $fields = mncf_admin_post_add_to_editor( 'get' );
    $groups = mncf_admin_post_get_post_groups_fields( $post );


    if ( empty( $fields ) || empty( $groups ) ) {
        /**
         * check user fields too, but only when CF are empty
         */
        include_once dirname(__FILE__).'/usermeta-post.php';
        $groups = mncf_admin_usermeta_get_groups_fields();
        if ( empty( $groups ) ) {
            return false;
        }
    }
    $editor_addon = new Editor_addon( 'types',
            __( 'Insert Types Shortcode', 'mncf' ),
            MNCF_EMBEDDED_RES_RELPATH . '/js/types_editor_plugin.js',
            '', true, 'icon-types-logo ont-icon-18 ont-color-gray' );

    foreach ( $groups as $group ) {
        if ( empty( $group['fields'] ) ) {
            continue;
        }
        foreach ( $group['fields'] as $group_field_id => $group_field ) {
            if ( !isset( $fields[$group_field_id] ) ) {
                continue;
            }
            $field = $fields[$group_field_id];
            $callback = 'mncfFieldsEditorCallback(\'' . $field['id']
                    . '\', \'postmeta\', ' . $post->ID . ')';

            $editor_addon->add_insert_shortcode_menu( stripslashes( $field['name'] ),
                    trim( mncf_fields_get_shortcode( $field ), '[]' ),
                    $group['name'], $callback );
        }
    }
}

/**
 * Adds items to view dropdown.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @todo Remove (to MNCF_MNViews)
 *
 * @param type $menu
 * @return type
 */
function mncf_admin_post_editor_addon_menus_filter( $menu ) {

    global $mncf;

    $post = mncf_admin_get_edited_post();
    if ( empty( $post ) ) {
        $post = (object) array('ID' => -1);
    }

    $groups = mncf_admin_fields_get_groups( TYPES_CUSTOM_FIELD_GROUP_CPT_NAME, 'group_active' );
    $all_post_types = implode( ' ', get_post_types( array('public' => true) ) );
    $add = array();
    if ( !empty( $groups ) ) {
        // $group_id is blank therefore not equal to $group['id']
        // use array for item key and CSS class
        $item_styles = array();

        foreach ( $groups as $group_id => $group ) {
            $fields = mncf_admin_fields_get_fields_by_group( $group['id'],
                    'slug', true, false, true );
            if ( !empty( $fields ) ) {
                // code from Types used here without breaking the flow
                // get post types list for every group or apply all
                $post_types = get_post_meta( $group['id'],
                        '_mn_types_group_post_types', true );
                if ( $post_types == 'all' ) {
                    $post_types = $all_post_types;
                }
                $post_types = trim( str_replace( ',', ' ', $post_types ) );
                $item_styles[$group['name']] = $post_types;

                foreach ( $fields as $field_id => $field ) {

                    // Use field class
                    $mncf->field->set( $mncf->post, $field );

                    // Get field data
                    $data = (array) $mncf->field->config;
                    // Get inherited field
                    if ( isset( $data['inherited_field_type'] ) ) {
                        $inherited_field_data = mncf_fields_type_action( $data['inherited_field_type'] );
                    }

                    $callback = 'mncfFieldsEditorCallback(\'' . $field['id']
                            . '\', \'postmeta\', ' . $post->ID . ')';

                    $menu[$group['name']][stripslashes( $field['name'] )] = array(
                        stripslashes( $field['name'] ), trim( mncf_fields_get_shortcode( $field ),
                                '[]' ), $group['name'], $callback);

                    /*
                     * Since Types 1.2
                     * We use field class to enqueue JS and CSS
                     */
                    $mncf->field->enqueue_script();
                    $mncf->field->enqueue_style();
                }
            }
        }
    }

    return $menu;
}

function mncf_admin_post_marketing_displaying_custom_content() {
    $displaying_custom_content = include( MNCF_ABSPATH . '/marketing/displaying-custom-content/title-content.php' );
    echo $displaying_custom_content['content'];
}

function mncf_post_preview_warning() {
    $post = mncf_admin_get_edited_post();
    // Add preview warning
    if ( isset( $post->post_status ) && !in_array( $post->post_status,
                    array('auto-draft', 'draft') ) && !in_array( $post->post_type,
                    array('cred', 'view', 'view-template') ) ) {
        mn_enqueue_style( 'mn-pointer' );
        mn_enqueue_script( 'mn-pointer' );

        ?><script type="text/javascript">
            if ( "undefined" != typeof typesPostScreen ) {
                typesPostScreen.previewWarning(
                    '<?php esc_attr_e( 'Preview warning', 'mncf' ); ?>',
                    '<?php echo esc_attr( sprintf( __( 'Custom field changes cannot be previewed until %s is updated', 'mncf' ), $post->post_type ) ); ?>');
            }
</script><?php
    }
}

/**
 * Create fake post object.
 *
 * Create fake post object if in system is no post.
 *
 * @param mixed $post
 * @return object
 *
 */
function mncf_admin_create_fake_post_if_need($post)
{
    if ( !is_object( $post ) ) {
        $post = new stdClass();
        $post->post_title = 'Lorem Ipsum';
        $post->ID = 0;
    }
    return $post;
}
