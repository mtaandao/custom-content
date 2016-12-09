<?php

/**
 * With Custom Content Dashboard we need the ability to redirect back to the Dashboard if the user came from the Dashboard
 * for this we introduced the mncf_ref parameter
 *
 * @return array
 * @since 2.1
 */
function mncf_ajax_group_delete_redirect() {

    // on group edit page
    if( isset( $_GET['mncf_ref'] ) ) {

        $group_type = get_post_type( $_GET['group_id'] );

        if( $_GET['mncf_ref'] == 'dashboard' ) {
            $redirect = admin_url( 'admin.php?page=toolset-dashboard' );
        } else {
            switch( $group_type ) {
                case 'mn-types-group':
                    $redirect = admin_url( 'admin.php?page=mncf-cf' );
                    break;
                case 'mn-types-term-group':
                    $redirect = admin_url( 'admin.php?page=mncf-termmeta-listing' );
                    break;
                case 'mn-types-user-group':
                    $redirect = admin_url( 'admin.php?page=mncf-um' );
                    break;
            }
        }

        return array(
                'output'                   => '',
                'execute'                  => 'redirect',
                'mncf_redirect'            => $redirect,
                'mncf_nonce_ajax_callback' => mn_create_nonce( 'execute' ),
            );
    // on listing page
    } else {
        return array(
            'output' => '',
            'execute' => 'reload',
            'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
        );
    }
}

/**
 * All AJAX calls go here.
 *
 * @global object $mndb
 *
 */
function mncf_ajax()
{
    /**
     * check nounce
     */
    if ( !(isset($_REQUEST['_mnnonce']) && mn_verify_nonce($_REQUEST['_mnnonce'], $_REQUEST['mncf_action']))) {
        die();
    }
    require_once MNCF_INC_ABSPATH.'/classes/class.mncf.roles.php';

    /**
     * check permissions
     */
    switch ($_REQUEST['mncf_action']) {
    case 'deactivate_post_type':
    case 'activate_post_type':
    case 'delete_post_type':
    case 'duplicate_post_type':
        $post_type = mncf_ajax_helper_get_post_type();
        if ( empty($post_type) ) {
            mncf_ajax_helper_print_error_and_die();
        }
        if ( !MNCF_Roles::user_can_edit_custom_post_by_slug($post_type)) {
            mncf_ajax_helper_verification_failed_and_die();
        }
        break;
    case 'taxonomy_duplicate':
    case 'deactivate_taxonomy':
    case 'activate_taxonomy':
    case 'delete_taxonomy':
        $custom_taxonomy = mncf_ajax_helper_get_taxonomy();
        if ( empty($custom_taxonomy) ) {
            mncf_ajax_helper_print_error_and_die();
        }
        if ( !MNCF_Roles::user_can_edit_custom_taxonomy_by_slug($custom_taxonomy)) {
            mncf_ajax_helper_verification_failed_and_die();
        }
        break;
    case 'deactivate_group':
    case 'activate_group':
    case 'delete_group':
        if (!isset($_GET['group_id']) || empty($_GET['group_id'])) {
            mncf_ajax_helper_print_error_and_die();
        }
        if ( !MNCF_Roles::user_can_edit_custom_field_group_by_id($_GET['group_id'])) {
            mncf_ajax_helper_verification_failed_and_die();
        }
        break;
    case 'deactivate_user_group':
    case 'activate_user_group':
    case 'delete_usermeta_group':
        if (!isset($_GET['group_id']) || empty($_GET['group_id'])) {
            mncf_ajax_helper_print_error_and_die();
        }
        if ( !MNCF_Roles::user_can_edit_usermeta_field_group_by_id($_GET['group_id'])) {
            mncf_ajax_helper_verification_failed_and_die();
        }
        break;
    case 'deactivate_term_group':
    case 'activate_term_group':
    case 'delete_term_group':
	    if (!isset($_GET['group_id']) || empty($_GET['group_id'])) {
		    mncf_ajax_helper_print_error_and_die();
	    }
	    if ( !MNCF_Roles::user_can_edit_term_field_group_by_id($_GET['group_id'])) {
		    mncf_ajax_helper_verification_failed_and_die();
	    }
	    break;
    case 'usermeta_delete':
    case 'delete_usermeta':
    case 'remove_from_history2':
    case 'usermeta_insert_existing':
    case 'fields_insert':
    case 'fields_insert_existing':
    case 'remove_field_from_group':
    case 'add_radio_option':
    case 'add_select_option':
    case 'add_checkboxes_option':
    case 'group_form_collapsed':
    case 'form_fieldset_toggle':
    case 'fields_delete':
    case 'delete_field':
    case 'remove_from_history':
    case 'add_condition':
    case 'pt_edit_fields':
    case 'toggle':
    case 'cb_save_empty_migrate':
        if ( !current_user_can('manage_options') ) {
            mncf_ajax_helper_verification_failed_and_die();
        }
        /**
         * do not check actions from other places
         */
        break;
    default:
        return;
    }

    /**
     * do actions
     */
    switch ($_REQUEST['mncf_action']) {
        /* User meta actions*/

    case 'usermeta_delete':
    case 'delete_usermeta':
        require_once MNCF_INC_ABSPATH . '/fields.php';
        if (isset($_GET['field_id'])) {
            $field_id = sanitize_text_field( $_GET['field_id'] );
            mncf_admin_fields_delete_field($field_id,TYPES_USER_META_FIELD_GROUP_CPT_NAME,'mncf-usermeta');
        }
        if (isset($_GET['field'])) {
            $field = sanitize_text_field( $_GET['field'] );
            mncf_admin_fields_delete_field($field,TYPES_USER_META_FIELD_GROUP_CPT_NAME,'mncf-usermeta');
        }
        echo json_encode(array(
            'output' => ''
        ));
        break;

    case 'remove_from_history2':
        require_once MNCF_INC_ABSPATH . '/fields.php';
        $fields = mncf_admin_fields_get_fields( true, true,false,'mncf-usermeta');
        if (isset($_GET['field_id']) && isset($fields[$_GET['field_id']])) {
            $fields[$_GET['field_id']]['data']['removed_from_history'] = 1;
            mncf_admin_fields_save_fields($fields, true, 'mncf-usermeta');
        }
        echo json_encode(array(
            'output' => ''
        ));
        break;

    case 'deactivate_user_group':
        require_once MNCF_INC_ABSPATH . '/fields.php';
        require_once MNCF_INC_ABSPATH . '/usermeta.php';
        $success = mncf_admin_fields_deactivate_group(intval($_GET['group_id']), TYPES_USER_META_FIELD_GROUP_CPT_NAME);
        if ($success) {
            echo json_encode(
                array(
                    'output' => __('Group deactivated', 'mncf'),
                    'execute' => 'reload',
                    'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
                )
            );
        } else {
            mncf_ajax_helper_print_error_and_die();
            die;
        }
        break;

    case 'activate_user_group':
        require_once MNCF_INC_ABSPATH . '/fields.php';
        require_once MNCF_INC_ABSPATH . '/usermeta.php';
        $success = mncf_admin_fields_activate_group(intval($_GET['group_id']), TYPES_USER_META_FIELD_GROUP_CPT_NAME);
        if ($success) {
            echo json_encode(
                array(
                    'output' => __('Group activated', 'mncf'),
                    'execute' => 'reload',
                    'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
                )
            );
        } else {
            mncf_ajax_helper_print_error_and_die();
            die;
        }
        break;

    case 'delete_usermeta_group':
        require_once MNCF_INC_ABSPATH . '/fields.php';
        require_once MNCF_INC_ABSPATH . '/usermeta.php';

        $redirect = mncf_ajax_group_delete_redirect();

        mncf_admin_fields_delete_group(intval($_GET['group_id']), TYPES_USER_META_FIELD_GROUP_CPT_NAME);
        echo json_encode( $redirect );

        break;

    case 'usermeta_insert_existing':
        require_once MNCF_INC_ABSPATH . '/fields.php';
        require_once MNCF_INC_ABSPATH . '/fields-form.php';
        require_once MNCF_INC_ABSPATH . '/usermeta-form.php';
        mncf_usermeta_insert_existing_ajax();
        mncf_form_render_js_validation();
        break;
        /* End Usertmeta actions*/

    case 'fields_insert':
        require_once MNCF_INC_ABSPATH . '/fields.php';
        require_once MNCF_INC_ABSPATH . '/fields-form.php';
        mncf_fields_insert_ajax();
        mncf_form_render_js_validation();
        break;

    case 'fields_insert_existing':
        require_once MNCF_INC_ABSPATH . '/fields.php';
        require_once MNCF_INC_ABSPATH . '/fields-form.php';
        mncf_fields_insert_existing_ajax();
        mncf_form_render_js_validation();
        break;

    case 'remove_field_from_group':
        require_once MNCF_INC_ABSPATH . '/fields.php';
        if (isset($_GET['group_id']) && isset($_GET['field_id'])) {
            mncf_admin_fields_remove_field_from_group(intval($_GET['group_id']),
                sanitize_text_field($_GET['field_id']));
        }
        break;

    case 'deactivate_group':
        require_once MNCF_INC_ABSPATH . '/fields.php';
        $success = mncf_admin_fields_deactivate_group(intval($_GET['group_id']));
        if ($success) {
            echo json_encode(
                array(
                    'output' => __('Group deactivated', 'mncf'),
                    'execute' => 'reload',
                    'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
                )
            );
        } else {
            mncf_ajax_helper_print_error_and_die();
        }
        break;

    case 'activate_group':
        require_once MNCF_INC_ABSPATH . '/fields.php';
        $success = mncf_admin_fields_activate_group(intval($_GET['group_id']));
        if ($success) {
            echo json_encode(
                array(
                    'output' => __('Group activated', 'mncf'),
                    'execute' => 'reload',
                    'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
                )
            );
        } else {
            mncf_ajax_helper_print_error_and_die();
        }
        break;

    case 'delete_group':
        require_once MNCF_INC_ABSPATH . '/fields.php';

        $redirect = mncf_ajax_group_delete_redirect();
        mncf_admin_fields_delete_group(intval($_GET['group_id']));

        echo json_encode( $redirect );
        break;

	    case 'deactivate_term_group':
		    require_once MNCF_INC_ABSPATH . '/fields.php';
		    $success = mncf_admin_fields_deactivate_group(intval($_GET['group_id']), Types_Field_Group_Term::POST_TYPE);
		    if ($success) {
			    echo json_encode(
				    array(
					    'output' => __('Group deactivated', 'mncf'),
					    'execute' => 'reload',
					    'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
				    )
			    );
		    } else {
			    mncf_ajax_helper_print_error_and_die();
		    }
		    break;

	    case 'activate_term_group':
		    require_once MNCF_INC_ABSPATH . '/fields.php';
		    $success = mncf_admin_fields_activate_group(intval($_GET['group_id']), Types_Field_Group_Term::POST_TYPE);
		    if ($success) {
			    echo json_encode(
				    array(
					    'output' => __('Group activated', 'mncf'),
					    'execute' => 'reload',
					    'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
				    )
			    );
		    } else {
			    mncf_ajax_helper_print_error_and_die();
		    }
		    break;

	    case 'delete_term_group':
		    require_once MNCF_INC_ABSPATH . '/fields.php';

            $redirect = mncf_ajax_group_delete_redirect();
		    mncf_admin_fields_delete_group(intval($_GET['group_id']), Types_Field_Group_Term::POST_TYPE);

            echo json_encode( $redirect );

		    break;

	    case 'deactivate_post_type':
        $post_type = mncf_ajax_helper_get_post_type();
        if ( empty($post_type) ) {
            mncf_ajax_helper_print_error_and_die();
        }
        $custom_types = get_option(MNCF_OPTION_NAME_CUSTOM_TYPES, array());
        $custom_types[$post_type]['disabled'] = 1;
        $custom_types[$post_type][TOOLSET_EDIT_LAST] = time();
        update_option(MNCF_OPTION_NAME_CUSTOM_TYPES, $custom_types);
        echo json_encode(
            array(
                'output' => __('Post Type deactivated', 'mncf'),
                'execute' => 'reload',
                'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
            )
        );
        break;

    case 'activate_post_type':
        $post_type = mncf_ajax_helper_get_post_type();
        if ( empty($post_type) ) {
            mncf_ajax_helper_print_error_and_die();
        }
        $custom_types = get_option(MNCF_OPTION_NAME_CUSTOM_TYPES, array());
        unset($custom_types[$post_type]['disabled']);
        $custom_types[$post_type][TOOLSET_EDIT_LAST] = time();
        update_option(MNCF_OPTION_NAME_CUSTOM_TYPES, $custom_types);
        echo json_encode(
            array (
                'output' => __('Post Type activated', 'mncf'),
                'execute' => 'reload',
                'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
            )
        );
        break;

    case 'delete_post_type':
        $post_type = mncf_ajax_helper_get_post_type();
        if ( empty($post_type) ) {
            mncf_ajax_helper_print_error_and_die();
        }
        $post_types = get_option(MNCF_OPTION_NAME_CUSTOM_TYPES, array());

        /**
         * Delete relation between custom posts types
         *
         * Filter allow to delete all custom fields used to make
         * a relation between posts.
         *
         * @since 1.6.4
         *
         * @param bool   $delete True or false flag to delete relationships.
         * @param string $var Currently deleted post type.
         */
        if ( apply_filters('mncf_delete_relation_meta', false, $post_type) ) {
            global $mndb;
            $mndb->delete(
                $mndb->postmeta,
                array( 'meta_key' => sprintf( '_mncf_belongs_%s_id', $post_type ) ),
                array( '%s' )
            );
        }

        unset($post_types[$post_type]);
        /**
         * remove post relation
         */
        foreach ( array_keys($post_types) as $post_type ) {
            if ( array_key_exists( 'post_relationship', $post_types[$post_type] ) ) {
                /**
                 * remove "has" relation
                 */
                if (
                    array_key_exists( 'has', $post_types[$post_type]['post_relationship'] )
                    && array_key_exists( $post_type, $post_types[$post_type]['post_relationship']['has'] )
                ) {
                    unset($post_types[$post_type]['post_relationship']['has'][$post_type]);
                    $post_types[$post_type][TOOLSET_EDIT_LAST] = time();
                }
                /**
                 * remove "belongs" relation
                 */
                if (
                    array_key_exists( 'belongs', $post_types[$post_type]['post_relationship'] )
                    && array_key_exists( $post_type, $post_types[$post_type]['post_relationship']['belongs'] )
                ) {
                    unset($post_types[$post_type]['post_relationship']['belongs'][$post_type]);
                    $post_types[$post_type][TOOLSET_EDIT_LAST] = time();
                }
            }
        }
        update_option(MNCF_OPTION_NAME_CUSTOM_TYPES, $post_types);
        mncf_admin_deactivate_content('post_type', $post_type);
        echo json_encode(
            array(
                'output' => '',
                'execute' => 'reload',
                'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
            )
        );
        break;

    case 'duplicate_post_type':
        $post_type = mncf_ajax_helper_get_post_type();
        if ( empty($post_type) ) {
            mncf_ajax_helper_print_error_and_die();
        }

        $post_types = get_option(MNCF_OPTION_NAME_CUSTOM_TYPES, array());

        $i = 0;
        $key = false;
        do {
            $key = sprintf($post_type.'-%d',++$i);
        } while( isset($post_types[$key]) );
        if ( $key ) {
            /**
             * duplicate post type
             */
            $post_types[$key] = $post_types[$post_type];
            /**
             * update some options
             */
            $post_types[$key]['labels']['name'] .= sprintf(' (%d)', $i);
            $post_types[$key]['labels']['singular_name'] .= sprintf(' (%d)', $i);
            $post_types[$key]['slug'] = $key;
            $post_types[$key]['__types_id'] = $key;

            /**
             * update post types
             */
            update_option(MNCF_OPTION_NAME_CUSTOM_TYPES, $post_types);

            /**
             * update custom taxonomies too
             */
            $custom_taxonomies = get_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
            foreach( $custom_taxonomies as $taxonomy_key => $taxonomy_data) {
                if (
                    isset( $taxonomy_data['supports']) 
                    && isset( $taxonomy_data['supports'][$post_type])
                ) {
                    $custom_taxonomies[$taxonomy_key]['supports'][$key] = 1;
                }
            }
            update_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom_taxonomies);

            echo json_encode(array(
                'execute' => 'reload',
                'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
            ));
        }
        break;

    case 'taxonomy_duplicate':
        $custom_taxonomy = mncf_ajax_helper_get_taxonomy();
        if ( empty($custom_taxonomy) ) {
            mncf_ajax_helper_print_error_and_die();
        }
        $custom_taxonomies = get_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
        $i = 0;
        $key = false;
        do {
            $key = sprintf($custom_taxonomy.'-%d',++$i);
        } while( isset($custom_taxonomies[$key]) );
        if ( $key ) {
            /**
             * duplicate custom taxonomies
             */
            $custom_taxonomies[$key] = $custom_taxonomies[$custom_taxonomy];

            /**
             * update some options
             */
            $custom_taxonomies[$key]['labels']['name'] .= sprintf(' (%d)', $i);
            $custom_taxonomies[$key]['labels']['singular_name'] .= sprintf(' (%d)', $i);
            $custom_taxonomies[$key]['slug'] = $key;
            $custom_taxonomies[$key]['id'] = $key;
            $custom_taxonomies[$key]['__types_id'] = $key;

            /**
             * update custom taxonomies
             */
            update_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom_taxonomies);

            /**
             * update post types
             */
            if (
                isset( $custom_taxonomies[$key]['supports'] )
                && is_array($custom_taxonomies[$key]['supports'])
                && !empty($custom_taxonomies[$key]['supports'])
            ) {
                $custom_types = get_option(MNCF_OPTION_NAME_CUSTOM_TYPES, array());
                foreach( array_keys($custom_taxonomies[$key]['supports']) as $custom_type ) {
                    /**
                     * avoid to create fake CPT from old data
                     */
                    if ( !isset($custom_types[$custom_type])) {
                        continue;
                    }
                    if ( !isset($custom_types[$custom_type]['taxonomies']) ) {
                        $custom_types[$custom_type]['taxonomies'] = array();
                    }
                    $custom_types[$custom_type]['taxonomies'][$key] = 1;
                }

                /**
                 * update post types
                 */
                update_option(MNCF_OPTION_NAME_CUSTOM_TYPES, $custom_types);
            }
            echo json_encode(array(
                'execute' => 'reload',
                'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
            ));
        }
        break;

    case 'deactivate_taxonomy':
        $custom_taxonomy = mncf_ajax_helper_get_taxonomy();
        if ( empty($custom_taxonomy) ) {
            mncf_ajax_helper_print_error_and_die();
        }
        $custom_taxonomies = get_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
        if (!isset($custom_taxonomies[$custom_taxonomy]) || !isset( $custom_taxonomies[$custom_taxonomy]['supports'] ) ) {
            $taxonomy = get_taxonomy($custom_taxonomy);
            $supports = array();
            if (is_object($taxonomy )) {
                if( ! empty( $taxonomy->object_type ) ) {
                    foreach( $taxonomy->object_type as $support_post ) {
                        $supports[$support_post] = 1;
                    }
                }
                $custom_taxonomies[$custom_taxonomy] = array(
                    'slug' => $taxonomy->name,
                    'labels' => array(
                        'singular_name' => $taxonomy->labels->singular_name,
                        'name' => $taxonomy->labels->name,
                    ),
                    'supports' => $supports,
                    'type' => '_builtin',
                    '_builtin' => true,
                );
            } else {
                mncf_ajax_helper_print_error_and_die();
            }
        }
        if ( mncf_is_builtin_taxonomy($custom_taxonomy) ) {
            $custom_taxonomies[$custom_taxonomy]['type'] = '_builtin';
            $custom_taxonomies[$custom_taxonomy]['_builtin'] = true;
        }
        $custom_taxonomies[$custom_taxonomy]['disabled'] = 1;
        $custom_taxonomies[$custom_taxonomy][TOOLSET_EDIT_LAST] = time();
        update_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom_taxonomies);
        echo json_encode(
            array(
                'output' => __('Taxonomy deactivated', 'mncf'),
                'execute' => 'reload',
                'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
            )
        );
        break;

    case 'activate_taxonomy':
        $custom_taxonomy = mncf_ajax_helper_get_taxonomy();
        if ( empty($custom_taxonomy) ) {
            mncf_ajax_helper_print_error_and_die();
        }
        $custom_taxonomies = get_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
        if (!isset($custom_taxonomies[$custom_taxonomy])) {
            $taxonomy = get_taxonomy($custom_taxonomy);
            if (is_object($taxonomy )) {
                $custom_taxonomies[$custom_taxonomy] = array(
                    'slug' => $taxonomy->name,
                    'labels' => array(
                        'singular_name' => $taxonomy->labels->singular_name,
                    ),
                    'type' => '_builtin',
                    '_builtin' => true,
                );
            } else {
                mncf_ajax_helper_print_error_and_die();
            }
        }
        if ( mncf_is_builtin_taxonomy($custom_taxonomy) ) {
            $custom_taxonomies[$custom_taxonomy]['type'] = '_builtin';
            $custom_taxonomies[$custom_taxonomy]['_builtin'] = true;
        }
        $custom_taxonomies[$custom_taxonomy]['disabled'] = 0;
        $custom_taxonomies[$custom_taxonomy][TOOLSET_EDIT_LAST] = time();
        update_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom_taxonomies);
        echo json_encode(
            array (
                'output' => __('Taxonomy activated', 'mncf'),
                'execute' => 'reload',
                'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
            )
        );
        break;

    case 'delete_taxonomy':
        $custom_taxonomy = mncf_ajax_helper_get_taxonomy();
        if ( empty($custom_taxonomy) ) {
            mncf_ajax_helper_print_error_and_die();
        }
        $custom_taxonomies = get_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
        unset($custom_taxonomies[$custom_taxonomy]);
        update_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom_taxonomies);
        mncf_admin_deactivate_content('taxonomy', $custom_taxonomy);

        // on group edit page
        if( isset( $_GET['mncf_ref'] ) ) {

            $redirect = $_GET['mncf_ref'] == 'dashboard'
                ? admin_url( 'admin.php?page=toolset-dashboard' )
                : admin_url( 'admin.php?page=mncf-ctt' );

            echo json_encode(
                array(
                    'output' => '',
                    'execute' => 'redirect',
                    'mncf_redirect' => $redirect,
                    'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
                )
            );
            // on listing page
        } else {
            echo json_encode(
                array(
                    'output' => '',
                    'execute' => 'reload',
                    'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
                )
            );
        }


        break;

    case 'add_radio_option':
        /**
         * check required data
         */
        if (
            !isset($_GET['parent_name'] )
            || !isset($_GET['mncf_ajax_update_add'] )
        ) {
            mncf_ajax_helper_print_error_and_die();
        }
        require_once MNCF_INC_ABSPATH . '/fields/radio.php';
        $value = esc_html(urldecode($_GET['parent_name']));
        $element = mncf_fields_radio_get_option($value);
        $id = array_shift($element);
        $element_txt = mncf_fields_radio_get_option_alt_text($id, $value);
        echo json_encode(
            array(
                'output' => mncf_form_simple($element),
                'execute' => 'append',
                'append_target' => '#mncf-form-groups-radio-ajax-response-'. esc_html(urldecode($_GET['mncf_ajax_update_add'])),
                'append_value' => trim(preg_replace('/[\r\n]+/', '', mncf_form_simple($element_txt))),
                'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
            )
        );
        break;

    case 'add_select_option':
        /**
         * check required data
         */
        if ( !isset($_GET['parent_name'] )) {
            mncf_ajax_helper_print_error_and_die();
        }
        require_once MNCF_INC_ABSPATH . '/fields/select.php';
        $element = mncf_fields_select_get_option(esc_html(urldecode($_GET['parent_name'])));
        echo json_encode(array(
            'output' => mncf_form_simple($element)
        ));
        break;

    case 'add_checkboxes_option':
        /**
         * check required data
         */
        if ( !isset($_GET['parent_name'] )) {
            mncf_ajax_helper_print_error_and_die();
        }
        require_once MNCF_INC_ABSPATH . '/fields/checkboxes.php';
        $value = esc_html(urldecode($_GET['parent_name']));
        $element = mncf_fields_checkboxes_get_option($value);
        $id = array_shift($element);
        $element_txt = mncf_fields_checkboxes_get_option_alt_text($id, $value);
        echo json_encode(array(
            'output' => mncf_form_simple($element),
            'mncf_nonce_ajax_callback' => mn_create_nonce('execute'),
        ));
        break;

    case 'group_form_collapsed':
        require_once MNCF_INC_ABSPATH . '/fields-form.php';
        $group_id = sanitize_text_field($_GET['group_id']);
        $action = sanitize_text_field($_GET['toggle']);
        $fieldset = sanitize_text_field($_GET['id']);
        mncf_admin_fields_form_save_open_fieldset($action, $fieldset,
            $group_id);
        break;

    case 'form_fieldset_toggle':
        $action = sanitize_text_field($_GET['toggle']);
        $fieldset = sanitize_text_field($_GET['id']);
        mncf_admin_form_fieldset_save_toggle($action, $fieldset);
        break;

    case 'fields_delete':
    case 'delete_field':
        require_once MNCF_INC_ABSPATH . '/fields.php';
        if (isset($_GET['field_id'])) {
            mncf_admin_fields_delete_field(sanitize_text_field($_GET['field_id']));
        }
        if (isset($_GET['field'])) {
            mncf_admin_fields_delete_field(sanitize_text_field($_GET['field']));
        }
        echo json_encode(array(
            'output' => ''
        ));
        break;

    case 'remove_from_history':
        require_once MNCF_INC_ABSPATH . '/fields.php';
        $fields = mncf_admin_fields_get_fields();
        if (isset($_GET['field_id']) && isset($fields[$_GET['field_id']])) {
            $fields[$_GET['field_id']]['data']['removed_from_history'] = 1;
            mncf_admin_fields_save_fields($fields, true);
        }
        echo json_encode(array(
            'output' => ''
        ));
        break;

    case 'add_condition':
        require_once MNCF_INC_ABSPATH . '/fields.php';
        require_once MNCF_ABSPATH . '/includes/conditional-display.php';
        if (!empty($_GET['field']) || !empty($_GET['group'])) {
            $data = array();
            if (isset($_GET['group'])) {
                $output = mncf_form_simple(mncf_cd_admin_form_single_filter(array(),
                    array(), null, true));
                echo json_encode(array(
                    'output' => $output,
                ));
            } else {
                $data['id'] = str_replace('_conditional_display', '',
                    sanitize_text_field($_GET['field']));
                $output = mncf_form_simple(mncf_cd_admin_form_single_filter($data,
                    array(), null, false));
                if (!empty($data['id'])) {
                    echo json_encode(array(
                        'output' => $output,
                    ));
                } else {
                    mncf_ajax_helper_print_error_and_die();
                }
            }
        } else {
            mncf_ajax_helper_print_error_and_die();
        }
        break;

    case 'pt_edit_fields':
        if (!empty($_GET['parent']) && !empty($_GET['child'])) {
            require_once MNCF_INC_ABSPATH . '/fields.php';
            require_once MNCF_INC_ABSPATH . '/post-relationship.php';
            mncf_pr_admin_edit_fields(sanitize_text_field($_GET['parent']), sanitize_text_field($_GET['child']));
        }
        break;

    case 'toggle':
        $option = get_option('mncf_toggle', array());
        $hidden = isset($_GET['hidden']) ? (bool) $_GET['hidden'] : 1;
        $div = esc_html(strval($_GET['div']));
        if (!$hidden) {
            unset($option[$div]);
        } else {
            $option[$div] = 1;
        }
        update_option('mncf_toggle', $option);
        break;

    case 'cb_save_empty_migrate':
        $output = sprintf(
            '<span style="color:red;">%s</div>',
            __('Migration process is not yet finished - please save group first, then change settings of this field.', 'mncf')
        );
        if (isset($_GET['field']) && isset($_GET['subaction'])) {
            require_once MNCF_INC_ABSPATH . '/fields.php';
            $option = $_GET['meta_type'] == 'usermeta' ? 'mncf-usermeta' : 'mncf-fields';
            $meta_type = sanitize_text_field($_GET['meta_type']);
            $field = mncf_admin_fields_get_field(sanitize_text_field($_GET['field']), false, false,
                false, $option);

            $_txt_updates = $meta_type == 'usermeta' ? __( '%d users require update', 'mncf' ) : __( '%d posts require update', 'mncf' );
            $_txt_no_updates = $meta_type == 'usermeta' ? __('No users require update', 'mncf') : __('No posts require update', 'mncf');
            $_txt_updated = $meta_type == 'usermeta' ? __('Users updated', 'mncf') : __('Posts updated', 'mncf');

            if (!empty($field)) {
                if ($_GET['subaction'] == 'save_check'
                    || $_GET['subaction'] == 'do_not_save_check') {
                        if ($field['type'] == 'checkbox') {
                            $posts = mncf_admin_fields_checkbox_migrate_empty_check($field,
                                $_GET['subaction']);
                        } else if ($field['type'] == 'checkboxes') {
                            $posts = mncf_admin_fields_checkboxes_migrate_empty_check($field,
                                $_GET['subaction']);
                        }
                        if (!empty($posts)) {
                            $output = '<div class="message updated"><p>'
                                . sprintf($_txt_updates, count($posts)) . '&nbsp;'
                                . '<a href="javascript:void(0);" class="button-primary" onclick="'
                                . 'mncfCbSaveEmptyMigrate(jQuery(this).parent().parent().parent(), \''
                                . sanitize_text_field($_GET['field']) . '\', '
                                . count($posts) . ', \''
                                . mn_create_nonce('cb_save_empty_migrate') . '\', \'';
                            $output .= $_GET['subaction'] == 'save_check' ? 'save' : 'do_not_save';
                            $output .= '\', \'' . $meta_type . '\');'
                                . '">'
                                . __('Update', 'mncf') . '</a>' . '</p></div>';
                        } else {
                            $output = '<div class="message updated"><p><em>'
                                . $_txt_no_updates . '</em></p></div>';
                        }
                    } else if ($_GET['subaction'] == 'save'
                        || $_GET['subaction'] == 'do_not_save') {
                            if ($field['type'] == 'checkbox') {
                                $posts = mncf_admin_fields_checkbox_migrate_empty($field,
                                    $_GET['subaction']);
                            } else if ($field['type'] == 'checkboxes') {
                                $posts = mncf_admin_fields_checkboxes_migrate_empty($field,
                                    $_GET['subaction']);
                            }
                            if (isset($posts['offset'])) {
                                if (!isset($_GET['total'])) {
                                    $output = '<span style="color:red;">'.__('Error occured', 'mncf').'</div>';
                                } else {
                                    $output = '<script type="text/javascript">mncfCbMigrateStep('
                                        . intval($_GET['total']) . ','
                                        . $posts['offset'] . ','
                                        . '\'' . sanitize_text_field($_GET['field']) . '\','
                                        . '\'' . mn_create_nonce('cb_save_empty_migrate')
                                        . '\', \'' . $meta_type . '\');</script>'
                                        . number_format($posts['offset'])
                                        . '/' . number_format(intval($_GET['total']))
                                        . '<div class="mncf-ajax-loading-small"></div>';
                                }
                            } else {
                                $output = sprintf(
                                    '<div class="message updated"><p>%s</p></div>',
                                    $_txt_updated
                                );
                            }
                        }
            }
        }
        echo json_encode(array(
            'output' => $output,
        ));
        break;

    default:
        break;
    }
    die();
}

function mncf_ajax_helper_get_taxonomy()
{
    if (!isset($_GET['mncf-tax']) || empty($_GET['mncf-tax'])) {
        return false;
    }
    $taxonomy = $_GET['mncf-tax'];
    require_once MNCF_INC_ABSPATH . '/custom-taxonomies.php';
    $custom_taxonomies = get_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
    if (
        isset($custom_taxonomies[$taxonomy])
        && isset($custom_taxonomies[$taxonomy]['slug'])
    ) {
        return $custom_taxonomies[$taxonomy]['slug'];
    }
    $taxonomy = get_taxonomy($taxonomy);
    if ( is_object($taxonomy) ) {
        return $taxonomy->name;
    }
    return false;
}

function mncf_ajax_helper_get_post_type()
{
    if (!isset($_REQUEST['mncf-post-type']) || empty($_REQUEST['mncf-post-type'])) {
        return false;
    }
    require_once MNCF_INC_ABSPATH . '/custom-types.php';
    $custom_types = get_option(MNCF_OPTION_NAME_CUSTOM_TYPES, array());
    if (
        isset($custom_types[$_REQUEST['mncf-post-type']])
        && isset($custom_types[$_REQUEST['mncf-post-type']]['slug'])
    ) {
        return $custom_types[$_REQUEST['mncf-post-type']]['slug'];
    }
    return false;
}

function mncf_ajax_helper_print_error_and_die()
{
    echo json_encode(array(
        'output' => __('Missing required data.', 'mncf'),
    ));
    die;
}

function mncf_ajax_helper_verification_failed_and_die()
{
    echo json_encode(array(
        'output' => __('Verification failed.', 'mncf'),
    ));
    die;
}
