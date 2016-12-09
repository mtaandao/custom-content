<?php
/**
 *
 *
 */

/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function mncf_fields_video() {
    return array(
        'id' => 'mncf-video',
        'title' => __( 'Video', 'mncf' ),
        'description' => __( 'Video', 'mncf' ),
        'mn_version' => '3.6',
        'inherited_field_type' => 'file',
        'validate' => array(
            'required' => array(
                'form-settings' => include( dirname( __FILE__ ) . '/patterns/validate/form-settings/required.php' )
            )
        ),
        'font-awesome' => 'video-camera',
    );
}

/**
 * View function.
 * 
 * @global type $mn_embed
 * @global object $mndb
 *
 * @param type $field
 * @return string
 */
function mncf_fields_video_view( $params ) {
    if ( is_string( $params['field_value'] ) ) {
        $params['field_value'] = stripslashes( $params['field_value'] );
    }
    $value = $params['field_value'];
    if ( empty( $value ) ) {
        return '__mncf_skip_empty';
    }
    list($default_width, $default_height) = mncf_media_size();
    $url = trim( strval( $value ) );
    $width = !empty( $params['width'] ) ? intval( $params['width'] ) : $default_width;
    $height = !empty( $params['height'] ) ? intval( $params['height'] ) : $default_height;
    $add = '';
    if ( !empty( $params['poster'] ) ) {
        $add .=" poster=\"{$params['poster']}\"";
    }
    if ( !empty( $params['loop'] ) ) {
        $add .= " loop=\"{$params['loop']}\"";
    }
    if ( !empty( $params['autoplay'] ) ) {
        $add .=" autoplay=\"{$params['autoplay']}\"";
    }
    if ( !empty( $params['preload'] ) ) {
        $add .=" preload=\"{$params['preload']}\"";
    }
    
    $shortcode = "[video width=\"{$width}\" height=\"{$height}\" src=\"{$url}\"{$add}]";
    $output = do_shortcode( $shortcode );
    if ( empty( $output ) ) {
        return '__mncf_skip_empty';
    }
    return $output;
}

/**
 * Editor callback form.
 */
function mncf_fields_video_editor_callback( $field, $data, $meta_type, $post ) {

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
    $data['file'] = !empty( $file ) ? $file : '';

    return array(
        'supports' => array(),
        'tabs' => array(
            'display' => array(
                'menu_title' => __( 'Display options', 'mncf' ),
                'title' => __( 'Display options for this field:', 'mncf' ),
                'content' => MNCF_Loader::template( 'editor-modal-video', $data ),
            )
        ),
        'settings' => $data,
    );
}

/**
 * Editor callback form submit.
 */
function mncf_fields_video_editor_submit( $data, $field, $context ) {
    $add = '';
    if ( !empty( $data['width'] ) ) {
        $add .= " width=\"{$data['width']}\"";
    }
    if ( !empty( $data['height'] ) ) {
        $add .= " height=\"{$data['height']}\"";
    }
    if ( !empty( $data['poster'] ) ) {
        $add .=" poster=\"{$data['poster']}\"";
    }
    if ( !empty( $data['loop'] ) ) {
        $add .= " loop=\"{$data['loop']}\"";
    }
    if ( !empty( $data['autoplay'] ) ) {
        $add .=" autoplay=\"{$data['autoplay']}\"";
    }
    if ( !empty( $data['preload'] ) ) {
        $add .=" preload=\"{$data['preload']}\"";
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
