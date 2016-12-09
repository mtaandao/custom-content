<?php

/**
 * Returns HTML formatted AJAX activation link for usermeta.
 * 
 * @param type $group_id
 * @return type 
 */
function mncf_admin_usermeta_get_ajax_activation_link($group_id)
{
    return sprintf(
        '<a href="%s" class="mncf-ajax-link" id="mncf-list-activate-%d">%s</a>',
        mncf_admin_usermeta_get_ajax_link('activate', $group_id),
        $group_id,
        __('Activate', 'mncf')
    );
}

/**
 * Returns HTML formatted AJAX deactivation link for usermeta.
 * @param type $group_id
 * @return type 
 */
function mncf_admin_usermeta_get_ajax_deactivation_link($group_id) {
    return sprintf(
        '<a href="%s" class="mncf-ajax-link" id="mncf-list-activate-%d">%s</a>',
        mncf_admin_usermeta_get_ajax_link('deactivate', $group_id),
        $group_id,
        __('Deactivate', 'mncf')
    );
}

/**
 * Helper function to build url
 *
 * @param string $status status of action
 * @param int $group_id group id
 * @return string link for Activate/Deactivate action
 */
function mncf_admin_usermeta_get_ajax_link($status, $group_id)
{
    /**
     * sanitize status
     */
    if ( !preg_match('/^(de)?activate$/', $status ) ) {
        return '#wrong-status';
    }
    /**
     * sanitize group_id
     */
    if ( !is_numeric($group_id) ) {
        return '#wrong-group_id';
    }
    /**
     * build link
     */
    return esc_url(
        add_query_arg(
            array(
                'action' => 'mncf_ajax',
                'mncf_action' => $status.'_user_group',
                'group_id' => $group_id,
                'mncf_ajax_update' => 'mncf_list_ajax_response_' . $group_id,
                '_mnnonce' => '' . mn_create_nonce($status.'_user_group'),
            ),
            admin_url('admin-ajax.php')
        )
    );
}
