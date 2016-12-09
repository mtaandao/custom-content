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
function mncf_repetitive_add_another_button( $field, $post ) {

    global $mncf;

    $title = mncf_translate( "field {$field['id']} name", $field['name'] );
    $button = '<a href="'
            . admin_url( 'admin-ajax.php?action=mncf_ajax'
                    . '&amp;mncf_action=repetitive_add'
                    . '&amp;_mnnonce=' . mn_create_nonce( 'repetitive_add' ) )
            . '&amp;field_id=' . $field['id'] . '&amp;field_id_md5='
            . md5( $field['id'] )
            . '&amp;post_id=' . $post->ID
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
function mncf_repetitive_delete_button( $field, $post, $meta_id ) {

    // TODO MNML move Add repetitive control buttons if not copied by MNML
    if ( mncf_mnml_field_is_copied( $field ) ) {
        return '';
    }

    // Let's cache calls
    static $cache = array();
    if ( empty( $field ) ) {
        $field = array();
    }
    if ( empty( $post ) ) {
        $post = array();
    }
    $cache_key = md5( serialize( (array) $field ) . $meta_id . serialize( (array) $post ) );

    // Return cached if there
    if ( isset( $cache[$cache_key] ) ) {
        return $cache[$cache_key];
    }

    // If post is new show different delete button
    if ( empty( $post->ID ) ) {
        $cache[$cache_key] = mncf_repetitive_delete_new_button( $field, $post );
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
                    . '&amp;mncf_action=repetitive_delete'
                    . '&amp;_mnnonce=' . mn_create_nonce( 'repetitive_delete' )
                    . '&amp;post_id=' . $post->ID . '&amp;field_id='
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
function mncf_repetitive_delete_new_button( $field, $post ) {
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
function mncf_repetitive_form( $field, $post ) {
    // TODO MNML move
    // Add repetitive control buttons if not copied by MNML
    if ( mncf_mnml_field_is_copied( $field ) ) {
        return '<div style="clear:both;"></div>';
    }
    $repetitive_form = '';
    $repetitive_form .= '<div class="mncf-repetitive-buttons">';
    $repetitive_form .= mncf_repetitive_add_another_button( $field, $post );
    $repetitive_form .= '</div><div style="clear:both;"></div>';
    return $repetitive_form;
}

/**
 * Returns HTML formatted drag button.
 * 
 * @param type $field
 * @param type $post
 * @return string 
 */
function mncf_repetitive_drag_button( $field, $post ) {
    // TODO MNML move
    if ( mncf_mnml_field_is_copied( $field ) ) {
        return '';
    }
    return '<div class="mncf-repetitive-drag">&nbsp;</div>';
}