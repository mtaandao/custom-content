<?php

/**
 * Register data (called automatically).
 * 
 * @return array field definition
 */
function mncf_fields_entry() {
    return array(
        'id' => 'mncf-entry',
        'title' => __( 'Entry', 'mncf' ),
        'description' => __( 'Entry', 'mncf' ),
        'validate' => array(
            'required' => array(
                'form-settings' => include( dirname( __FILE__ ) . '/patterns/validate/form-settings/required.php' )
            )
        ),
        'font-awesome' => 'file-text-o',
    );
}

/**
 * Meta box form.
 * 
 * @param type $field
 * @return string 
 */
function mncf_fields_entry_meta_box_form( $field ) {
    $form = array();
    $form['name'] = array(
        '#type' => 'textfield',
        '#name' => 'mncf[' . $field['slug'] . ']',
    );
    return $form;
}

/**
 * Editor callback form.
 */
function mncf_fields_entry_editor_callback($field, $settings)
{
    $post_type  = get_post_type_object( $field['data']['post_type'] );
    if ( null == $post_type ) {
        return;
    }

    $data = mncf_fields_entry_get_options();

    foreach( $data['options'] as $key => $field_data ) {
        if ( mncf_fields_entry_check_is_available( $field['data']['post_type'], $field_data ) ) {
            continue;
        }
        unset($data['options'][$key]);
    }

    return array(
        'supports' => array('style'),
        'tabs' => array(
            'display' => array(
                'menu_title' => __( 'Display options', 'mncf' ),
                'title' => __( 'Display options for this field:', 'mncf' ),
                'content' => MNCF_Loader::template( 'editor-modal-entry', $data),
            )
        ),
    );
}

/**
 * Editor callback form submit.
 */
function mncf_fields_entry_editor_submit($data, $field, $context)
{
    $shortcode = '';
    if (
        isset($data['display'])
        && preg_match('/^post-[\-a-z]+$/', $data['display'])
    ) {
        $add = sprintf(' display="%s"', $data['display']);
        if ( $context == 'usermeta' ) {
            $shortcode = mncf_usermeta_get_shortcode( $field, $add );
        } else {
            $shortcode = mncf_fields_get_shortcode( $field, $add );
        }
    }
    return $shortcode;
}

/**
 * View function.
 *
 * @param type $params
 */
function mncf_fields_entry_view($params)
{
    if (
        !isset($params['field'])
        || !isset($params['display'])
        || !isset($params['field_value'])
        || empty($params['field_value'])
    ) {
        return '__mncf_skip_empty';
    }
    /**
     * use cache
     */
    static $mncf_fields_entry_view_cache;
    if (
        isset($mncf_fields_entry_view_cache[$params['field']['id']])
        && isset($mncf_fields_entry_view_cache[$params['field']['id']][$params['display']])
    ) {
        return $mncf_fields_entry_view_cache[$params['field']['id']][$params['display']];
    }
    $post = get_post($params['field_value']);
    $data = mncf_fields_entry_get_options();
    foreach( $data['options'] as $key => $field_data ) {
        if ( mncf_fields_entry_check_is_available( $post->post_type, $field_data ) ) {
            $value = '__mncf_skip_empty';
            switch( $key ) {
            case 'post-title':
                $value = apply_filters('post_title', $post->post_title);
                break;
            case 'post-link':
                $value = sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    esc_attr(get_permalink($post)),
                    esc_attr(apply_filters('post_title', $post->post_title)),
                    apply_filters('post_title', $post->post_title)
                );
                break;
            case 'post-url':
                $value = get_permalink($post);
                break;
            case 'post-body':
                $value = apply_filters('the_content', $post->post_content);
                break;
            case 'post-excerpt':
                $value = apply_filters('the_excerpt', $post->post_excerpt);
                break;
            case 'post-date':
                $value = get_the_date(null, $post->ID);
                break;
            case 'post-author':
                $value = get_the_author_meta('display_name', $post->author_id);
                break;
            case 'post-featured-image':
                $value = get_the_post_thumbnail($post->ID);
                break;
            case 'post-slug':
                $value = $post->post_name;
                break;
            case 'post-type':
                $value = $post->post_type;
                break;
            case 'post-status':
                $value = $post->post_status;
                break;
            case 'post-class':
                $value = implode(' ', get_post_class('', $post->ID));
                break;
            case 'post-edit-link':
                $value = get_edit_post_link($post->ID);
                break;
            default:
                $value = $key;
            }
            $mncf_fields_entry_view_cache[$params['field_value']][$key] = $value;
        } else {
            d(array($post->post_type, $key, $field_data));
           $mncf_fields_entry_view_cache[$params['field_value']][$key] = '__mncf_skip_empty';
        }
    }
    return $mncf_fields_entry_view_cache[$params['field_value']][$params['display']];
}

function mncf_fields_entry_check_is_available($post_type, $field)
{
    /**
     * remove some option if certain post type do not supports it
     */
    if( isset( $field['support_field'] ) ) {
        if ( !post_type_supports( $post_type, $field['support_field'])) {
            return false;
        }
    }
    /**
     * remove some option if certain post type definition not match
     */
    if( isset( $field['post_type'] ) ) {
        $post_type = get_post_type_object($post_type);
        if (
            !isset($post_type->$field['post_type'])
            || empty($post_type->$field['post_type'])
        ) {
            return false;
        }
    }
    return true;
}

function mncf_fields_entry_get_options()
{
    return array(
        'options' => array(
            'post-title' => array(
                'support_field' => 'title',
                'label' => __('Post title', 'mncf'),
            ),
            'post-link' => array(
                'support_field' => 'title',
                'post_type' => 'public',
                'label' => __('Post title with a link', 'mncf'),
            ),
            'post-url' => array(
                'post_type' => 'public',
                'label' => __('Post URL', 'mncf'),
            ),
            'post-body' => array(
                'support_field' => 'editor',
                'label' => __('Post body', 'mncf'),
            ),
            'post-excerpt' => array(
                'support_field' => 'excerpt',
                'label' => __('Post excerpt', 'mncf'),
            ),
            'post-date' => array(
                'label' => __('Post date', 'mncf'),
            ),
            'post-author' => array(
                'support_field' => 'author',
                'label' => __('Post author', 'mncf'),
            ),
            'post-featured-image' => array(
                'support_field' => 'thumbnail',
                'label' => __('Post featured image', 'mncf'),
            ),
            'post-id' => array(
                'label' => __('Post ID', 'mncf'),
            ),
            'post-slug' => array(
                'label' => __('Post slug', 'mncf'),
            ),
            'post-type' => array(
                'label' => __('Post type', 'mncf'),
            ),
            'post-format' => array(
                'support_field' => 'post-formats',
                'label' => __('Post format', 'mncf'),
            ),
            'post-status' => array(
                'label' => __('Post status', 'mncf'),
            ),
            'post-comments-number' => array(
                'support_field' => 'comments',
                'label' => __('Post comments number', 'mncf'),
            ),
            'post-class' => array(
                'label' => __('Post class', 'mncf'),
            ),
            'post-edit-link' => array(
                'label' => __('Post edit link', 'mncf'),
            ),
        ),
        'default' => 'post-title',
    );
}

