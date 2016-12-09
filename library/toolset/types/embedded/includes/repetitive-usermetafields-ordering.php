<?php
/*
 * Ordering repetitive fields.
 * 
 * @todo sorting option.
 * @todo drag-and-drop.
 * @todo CSS adjustment
 * @todo move buttons and inserting new field
 * 
 * @since Types 1.1.3.2 and MN 3.4.2 (3.5 RC)
 */

// Add buttons
//'mncf_post_edit_field';

/**
 * HTML formatted output for 'Add Another Field'.
 * 
 * @param type $field
 * @param type $post
 * @return string 
 */
function mncf_repetitive_add_another_umbutton( $field, $user_id ) {

    global $mncf;

    $title = mncf_translate( "field {$field['id']} name", $field['name'] );
    $button = '<a href="'
            . admin_url( 'admin-ajax.php?action=mncf_ajax'
                    . '&amp;mncf_action=um_repetitive_add'
                    . '&amp;_mnnonce=' . mn_create_nonce( 'um_repetitive_add' ) )
            . '&amp;field_id=' . $field['id'] . '&amp;field_id_md5='
            . md5( $field['id'] )
            . '&amp;user_id=' . $user_id
            . '" class="mncf-repetitive-add button-primary">'
            . sprintf( __( 'Add Another %s', 'mncf' ), $title ) . '</a>';
    return $button;
}

/**
 * HTML formatted output for 'Delete Field'.
 * 
 * @param type $field
 * @param type $post
 * @return string 
 */
function mncf_repetitive_delete_umbutton( $field, $user_id, $meta_id ) {

    // TODO MNML move Add repetitive control buttons if not copied by MNML
    if ( mncf_mnml_is_translated_profile_page( $field ) ) {
        return '';
    }

    // Let's cache calls
    static $cache = array();
    if ( empty( $field ) ) {
        $field = array();
    }
    if ( empty( $user_id ) ) {
        $post = array();
    }
    $cache_key = md5( serialize( (array) $field ) . $meta_id . serialize( (array) $user_id ) );

    // Return cached if there
    if ( isset( $cache[$cache_key] ) ) {
        return $cache[$cache_key];
   }

    // If post is new show different delete button
    if ( empty( $user_id ) ) {
        $cache[$cache_key] = mncf_repetitive_delete_new_umbutton( $field, $user_id );
        return $cache[$cache_key];
    }

    // Regular delete button
    $button = '';
    $title = mncf_translate( "field {$field['id']} name", $field['name'] );
    /*
     * No need for old_value anymore.
     * Use meta_id.
     */
    $button .= '&nbsp;<a href="'
            . admin_url( 'admin-ajax.php?action=mncf_ajax'
                    . '&amp;mncf_action=um_repetitive_delete'
                    . '&amp;_mnnonce=' . mn_create_nonce( 'um_repetitive_delete' )
                    . '&amp;user_id=' . $user_id . '&amp;field_id='
                    . $field['id'] . '&amp;meta_id='
                    . $meta_id )
            . '&amp;mncf_warning=' . __( 'Are you sure?', 'mncf' )
            . '&amp;field_id_md5='
            . md5( $field['id'] )
            . '" class="mncf-repetitive-delete button-secondary">'
            . sprintf( __( 'Delete %s', 'mncf' ), $title ) . '</a>';


    // Cache it
    $cache[$cache_key] = $button;
    return $button;
}

/**
 * HTML formatted output for NEW 'Delete Field'.
 * 
 * @param type $field
 * @param type $post
 * @return string 
 */
function mncf_repetitive_delete_new_umbutton( $field, $post ) {
    $button = '&nbsp;<a href="javascript:void(0);"'
            . ' class="mncf-repetitive-delete mncf-repetitive-delete-new button-secondary">'
            . __( 'Delete Field', 'mncf' ) . '</a>';
    return $button;
}

/**
 * HTML formatted repetitive form.
 * 
 * Add this for each field processed.
 * 
 * @uses hook 'mncf_post_edit_field'
 * @param type $field
 * @return string 
 */
function mncf_repetitive_umform( $field, $user_id ) {
    // Add repetitive control buttons if not copied by MNML
    // TODO MNML move
    if ( mncf_mnml_is_translated_profile_page( $field ) ) {
        return '';
    }
    $repetitive_form = '';
    $repetitive_form .= '<div class="mncf-repetitive-buttons">';
    $repetitive_form .= mncf_repetitive_add_another_umbutton( $field, $user_id );
    $repetitive_form .= '</div><div style="clear:both;"></div>';
    return $repetitive_form;
}
