<?php
/*
 * Custom taxonomies functions.
 */
require_once MNCF_EMBEDDED_INC_ABSPATH . '/custom-taxonomies.php';

/**
 * Returns HTML formatted AJAX activation link.
 * 
 * @param type $taxonomy
 * @return type 
 */
function mncf_admin_custom_taxonomies_get_ajax_activation_link($taxonomy) {
    return '<a href="' . admin_url('admin-ajax.php?action=mncf_ajax'
                    . '&amp;mncf_action=activate_taxonomy&amp;mncf-tax='
                    . $taxonomy . '&amp;mncf_ajax_update=mncf_list_ajax_response_'
                    . $taxonomy) . '&amp;_mnnonce='
            . mn_create_nonce('activate_taxonomy')
            . '" class="mncf-ajax-link" id="mncf-list-activate-'
            . $taxonomy . '">'
            . __('Activate', 'mncf') . '</a>';
}

/**
 * Returns HTML formatted AJAX deactivation link.
 * 
 * @param type $taxonomy
 * @return type 
 */
function mncf_admin_custom_taxonomies_get_ajax_deactivation_link($taxonomy) {
    return '<a href="' . admin_url('admin-ajax.php?action=mncf_ajax&amp;'
                    . 'mncf_action=deactivate_taxonomy&amp;mncf-tax='
                    . $taxonomy . '&amp;mncf_ajax_update=mncf_list_ajax_response_'
                    . $taxonomy) . '&amp;_mnnonce='
            . mn_create_nonce('deactivate_taxonomy')
            . '" class="mncf-ajax-link" id="mncf-list-activate-'
            . $taxonomy . '">'
            . __('Deactivate', 'mncf') . '</a>';
}
