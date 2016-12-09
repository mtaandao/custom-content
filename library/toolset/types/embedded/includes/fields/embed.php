<?php

/**
 * Register data (called automatically).
 *
 * @return type
 */
function mncf_fields_embed() {
    return array(
        'id' => 'mncf-embed',
        'title' => __( 'Embedded Media', 'mncf' ),
        'description' => __( 'Embedded Media', 'mncf' ),
        'validate' => array(
            'required' => array(
                'form-settings' => include( dirname( __FILE__ ) . '/patterns/validate/form-settings/required.php' )
            ),
            'url' => array(
                'forced' => true,
                'form-settings' => include( dirname( __FILE__ ) . '/patterns/validate/form-settings/url.php' )
            )
        ),
        'mn_version' => '3.6',
        'font-awesome' => 'code',
    );
}

/**
 * Meta box form.
 *
 * @param type $field
 * @return string
 */
function mncf_fields_embed_meta_box_form( $field ) {
    $form = array();
    $form['name'] = array(
        '#type' => 'textfield',
        '#name' => 'mncf[' . $field['slug'] . ']',
    );
    return $form;
}

/**
 * View function.
 *
 * @global type $mn_embed
 * @param type $field
 * @return string
 */
function mncf_fields_embed_view( $params ) {
    global $mn_embed;
    $value = $params['field_value'];
    if ( empty( $value ) ) {
        return '__mncf_skip_empty';
    }
    list($default_width, $default_height) = mncf_media_size();
    $url = trim( strval( $value ) );
    if ( !types_validate( 'url', $url ) ) {
        return '__mncf_skip_empty';
    }
    $width = !empty( $params['width'] ) ? intval( $params['width'] ) : $default_width;
    $height = !empty( $params['height'] ) ? intval( $params['height'] ) : $default_height;

    $shortcode = '[embed width="' . $width . '" height="' . $height . '"]' . $url . '[/embed]';
    $output = $mn_embed->run_shortcode( $shortcode );
    if ( empty( $output ) ) {
        return '__mncf_skip_empty';
    }
    return $output;
}

/**
 * Editor callback form.
 *
 * @global object $mndb
 */
function mncf_fields_embed_editor_callback( $field, $data, $meta_type, $post ) {

    // Get attachment
    $attachment_id = false;
    if ( !empty( $post->ID ) ) {
        $file = get_post_meta( $post->ID,
                mncf_types_get_meta_prefix( $field ) . $field['slug'], true );
        if ( empty( $file ) ) {
            $user_id = mncf_usermeta_get_user();
            $file = get_user_meta( $user_id,
                    mncf_types_get_meta_prefix( $field ) . $field['slug'], true );
        }
        if ( !empty( $file ) ) {
            // Get attachment by guid
            global $mndb;
            $attachment_id = $mndb->get_var(
                $mndb->prepare(
                    "SELECT ID FROM {$mndb->posts} WHERE post_type = 'attachment' AND guid=%s",
                    $file
                )
            );
        }
    }

    // Set data
    $data['attachment_id'] = $attachment_id;
    $data['file'] = !empty($file) ? $file : '';

    return array(
        'supports' => array(),
        'tabs' => array(
            'display' => array(
                'menu_title' => __( 'Display options', 'mncf' ),
                'title' => __( 'Display options for this field:', 'mncf' ),
                'content' => MNCF_Loader::template( 'editor-modal-embed', $data ),
            )
        ),
        'settings' => $data,
    );
}

/**
 * Editor callback form submit.
 */
function mncf_fields_embed_editor_submit( $data, $field, $context ) {
    $add = '';
    if ( !empty( $data['width'] ) ) {
        $add .= " width=\"{$data['width']}\"";
    }
    if ( !empty( $data['height'] ) ) {
        $add .= " height=\"{$data['height']}\"";
    }
    if ( $context == 'usermeta' ) {
        $add .= mncf_get_usermeta_form_addon_submit();
        $shortcode = mncf_usermeta_get_shortcode( $field, $add );
	} elseif ( $context == 'termmeta' ) {
        $add .= mncf_get_termmeta_form_addon_submit();
        $shortcode = mncf_termmeta_get_shortcode( $field, $add );
    } else {
        $shortcode = mncf_fields_get_shortcode( $field, $add );
    }

    return $shortcode;
}
