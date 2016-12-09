<?php
/*
 * Custom types functions.
 */
require_once MNCF_EMBEDDED_INC_ABSPATH . '/custom-types.php';

/**
 * Returns HTML formatted AJAX activation link.
 * 
 * @param type $post_type
 * @return type 
 */
function mncf_admin_custom_types_get_ajax_activation_link($post_type) {
    return '<a href="' . admin_url('admin-ajax.php?action=mncf_ajax&amp;'
                    . 'mncf_action=activate_post_type&amp;mncf-post-type='
                    . $post_type . '&amp;mncf_ajax_update=mncf_list_ajax_response_'
                    . $post_type) . '&amp;_mnnonce='
            . mn_create_nonce('activate_post_type')
            . '" class="mncf-ajax-link" id="mncf-list-activate-'
            . $post_type . '">'
            . __('Activate', 'mncf') . '</a>';
}

/**
 * Returns HTML formatted AJAX deactivation link.
 * @param type $group_id
 * @return type 
 */
function mncf_admin_custom_types_get_ajax_deactivation_link($post_type) {
    return '<a href="' . admin_url('admin-ajax.php?action=mncf_ajax&amp;'
                    . 'mncf_action=deactivate_post_type&amp;mncf-post-type='
                    . $post_type . '&amp;mncf_ajax_update=mncf_list_ajax_response_'
                    . $post_type) . '&amp;_mnnonce='
            . mn_create_nonce('deactivate_post_type')
            . '" class="mncf-ajax-link" id="mncf-list-activate-'
            . $post_type . '">'
            . __('Deactivate', 'mncf') . '</a>';
}
