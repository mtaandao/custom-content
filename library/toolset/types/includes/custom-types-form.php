<?php
/**
 *
 * Custom types form
 *
 *
 */

/**
 * Adds JS validation script.
 */
function mncf_admin_types_form_js_validation()
{
    mncf_form_render_js_validation();
}

/**
 * Submit function
 *
 * @global object $mndb
 *
 */
function mncf_admin_custom_types_form_submit($form)
{
    global $mncf;

    if ( !isset( $_POST['ct'] ) ) {
        return false;
    }
    $data = $_POST['ct'];
    $update = false;

    // Sanitize data
    if ( isset( $data['mncf-post-type'] ) ) {
        $update = true;
        $data['mncf-post-type'] = sanitize_title( $data['mncf-post-type'] );
    } else {
        $data['mncf-post-type'] = null;
    }
    if ( isset( $data['slug'] ) ) {
        $data['slug'] = sanitize_title( $data['slug'] );
    } else {
        $data['slug'] = null;
    }
    if ( isset( $data['rewrite']['slug'] ) ) {
        $data['rewrite']['slug'] = remove_accents( $data['rewrite']['slug'] );
        $data['rewrite']['slug'] = strtolower( $data['rewrite']['slug'] );
        $data['rewrite']['slug'] = trim( $data['rewrite']['slug'] );
    }
    $data['_builtin'] = false;


    // Set post type name
    $post_type = null;
    if ( !empty( $data['slug'] ) ) {
        $post_type = $data['slug'];
    } elseif ( !empty( $data['mncf-post-type'] ) ) {
        $post_type = $data['mncf-post-type'];
    } elseif ( !empty( $data['labels']['singular_name'] ) ) {
        $post_type = sanitize_title( $data['labels']['singular_name'] );
    }

    if ( empty( $post_type ) ) {
        mncf_admin_message( __( 'Please set post type name', 'mncf' ), 'error' );
        return false;
    }

    $data['slug'] = $post_type;
    $custom_types = get_option( MNCF_OPTION_NAME_CUSTOM_TYPES, array() );
    $protected_data_check = array();

    if ( mncf_is_builtin_post_types($data['slug']) ) {
        $data['_builtin'] = true;
    } else {
        // Check reserved name
        $reserved = mncf_is_reserved_name( $post_type, 'post_type' );
        if ( is_mn_error( $reserved ) ) {
            mncf_admin_message( $reserved->get_error_message(), 'error' );
            return false;
        }

        // Check overwriting
        if ( ( !array_key_exists( 'mncf-post-type', $data ) || $data['mncf-post-type'] != $post_type ) && array_key_exists( $post_type, $custom_types ) ) {
            mncf_admin_message( __( 'Post Type already exists', 'mncf' ), 'error' );
            return false;
        }

        /*
         * Since Types 1.2
         * We do not allow plural and singular names to be same.
         */
        if ( $mncf->post_types->check_singular_plural_match( $data ) ) {
            mncf_admin_message( $mncf->post_types->message( 'warning_singular_plural_match' ), 'error' );
            return false;
        }

        // Check if renaming then rename all post entries and delete old type
        if ( !empty( $data['mncf-post-type'] )
            && $data['mncf-post-type'] != $post_type ) {
                global $mndb;
                $mndb->update( $mndb->posts, array('post_type' => $post_type),
                    array('post_type' => $data['mncf-post-type']), array('%s'),
                    array('%s')
                );

                /**
                 * update post meta "_mn_types_group_post_types"
                 */
                $sql = $mndb->prepare(
                    sprintf(
                        'select meta_id, meta_value from %s where meta_key = %%s',
                        $mndb->postmeta
                    ),
                    '_mn_types_group_post_types'
                );
                $all_meta = $mndb->get_results($sql, OBJECT_K);
                $re = sprintf( '/,%s,/', $data['mncf-post-type'] );
                foreach( $all_meta as $meta ) {
                    if ( !preg_match( $re, $meta->meta_value ) ) {
                        continue;
                    }
                    $mndb->update(
                        $mndb->postmeta,
                        array(
                            'meta_value' => preg_replace( $re, ','.$post_type.',', $meta->meta_value ),
                        ),
                        array(
                            'meta_id' => $meta->meta_id,
                        ),
                        array( '%s' ),
                        array( '%d' )
                    );
                }

                /**
                 * update _mncf_belongs_{$data['mncf-post-type']}_id
                 */
                $mndb->update(
                    $mndb->postmeta,
                    array(
                        'meta_key' => sprintf( '_mncf_belongs_%s_id', $post_type ),
                    ),
                    array(
                        'meta_key' => sprintf( '_mncf_belongs_%s_id', $data['mncf-post-type'] ),
                    ),
                    array( '%s' ),
                    array( '%s' )
                );

                /**
                 * update options "mnv_options"
                 */
                $mnv_options = get_option( 'mnv_options', true );
                if ( is_array( $mnv_options ) ) {
                    $re = sprintf( '/(views_template_(archive_)?for_)%s/', $data['mncf-post-type'] );
                    foreach( $mnv_options as $key => $value ) {
                        if ( !preg_match( $re, $key ) ) {
                            continue;
                        }
                        unset($mnv_options[$key]);
                        $key = preg_replace( $re, "$1".$post_type, $key );
                        $mnv_options[$key] = $value;
                    }
                    update_option( 'mnv_options', $mnv_options );
                }

                /**
                 * update option "mncf-custom-taxonomies"
                 */
                $mncf_custom_taxonomies = get_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, true );
                if ( is_array( $mncf_custom_taxonomies ) ) {
                    $update_mncf_custom_taxonomies = false;
                    foreach( $mncf_custom_taxonomies as $key => $value ) {
                        if ( array_key_exists( 'supports', $value ) && array_key_exists( $data['mncf-post-type'], $value['supports'] ) ) {
                            unset( $mncf_custom_taxonomies[$key]['supports'][$data['mncf-post-type']] );
                            $update_mncf_custom_taxonomies = true;
                        }
                    }
                    if ( $update_mncf_custom_taxonomies ) {
                        update_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, $mncf_custom_taxonomies );
                    }
                }

                // Sync action
                do_action( 'mncf_post_type_renamed', $post_type, $data['mncf-post-type'] );

                // Set protected data
                $protected_data_check = $custom_types[$data['mncf-post-type']];
                // Delete old type
                unset( $custom_types[$data['mncf-post-type']] );
                $data['mncf-post-type'] = $post_type;
            } else {
                // Set protected data
                $protected_data_check = !empty( $custom_types[$post_type] ) ? $custom_types[$post_type] : array();
            }

        // Check if active
        if ( isset( $custom_types[$post_type]['disabled'] ) ) {
            $data['disabled'] = $custom_types[$post_type]['disabled'];
        }
    }

    // Sync taxes with custom taxes
    if ( !empty( $data['taxonomies'] ) ) {
        $taxes = get_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
        foreach ( $taxes as $id => $tax ) {
            if ( array_key_exists( $id, $data['taxonomies'] ) ) {
                $taxes[$id]['supports'][$data['slug']] = 1;
            } else {
                unset( $taxes[$id]['supports'][$data['slug']] );
            }
        }
        update_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, $taxes );
    }

    // Preserve protected data
    foreach ( $protected_data_check as $key => $value ) {
        if ( strpos( $key, '_' ) !== 0 ) {
            unset( $protected_data_check[$key] );
        }
    }

    /**
     * set last edit time
     */
    $data[TOOLSET_EDIT_LAST] = time();

    /**
     * set last edit author
     */

    $data[MNCF_AUTHOR] = get_current_user_id();

    /**
     * add builid in
     */
    if ( $data['_builtin'] && !isset( $protected_data_check[$data['slug']])) {
        $protected_data_check[$data['slug']] = array();
    }

    // Merging protected data
    $custom_types[$post_type] = array_merge( $protected_data_check, $data );

    update_option( MNCF_OPTION_NAME_CUSTOM_TYPES, $custom_types );

    // MNML register strings
    if ( !$data['_builtin'] ) {
        mncf_custom_types_register_translation( $post_type, $data );
    }

    /**
     * success message
     */
    mncf_admin_message_store(
        apply_filters(
            'types_message_custom_post_type_saved',
            __( 'Post Type saved', 'mncf' ),
            $data,
            $update
        ),
        'custom'
    );

    if ( !$data['_builtin'] ) {
        // Flush rewrite rules
        flush_rewrite_rules();

        do_action( 'mncf_custom_types_save', $data );
    }

    // Redirect
    mn_safe_redirect(
        esc_url_raw(
            add_query_arg(
                array(
                    'page' => 'mncf-edit-type',
                    'mncf-post-type' => $post_type,
                    'mncf-rewrite' => 1,
                    'mncf-message' => 'view',
                ),
                admin_url( 'admin.php' )
            )
        )
    );
    die();
}

