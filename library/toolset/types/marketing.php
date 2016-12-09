<?php
/*
 * Add here marketing messages
 * 
 * Hooks used
 */

add_action('mncf_admin_page_init', 'mncf_marketing_init');

/**
 * Enqueue styles and scripts
 */
function mncf_marketing_init() {
    mn_enqueue_style('mncf-marketing-congrats',
            MNCF_RELPATH . '/marketing/congrats-post-types/style.css', array(),
            MNCF_VERSION);
    mn_enqueue_script('mncf-marketing-congrats',
            MNCF_RELPATH . '/marketing/congrats-post-types/js/jquery.mncfnotif.js',
            array('jquery'), MNCF_VERSION);
}

add_filter('types_message_custom_post_type_saved',
        'types_marketing_message_custom_post_type_saved', 10, 3);

add_filter('types_message_custom_taxonomy_saved',
        'types_marketing_message_custom_taxonomy_saved', 10, 3);

add_filter('types_message_custom_fields_saved',
        'types_marketing_message_custom_fields_saved', 10, 3);

add_filter('types_message_term_fields_saved',
        'types_marketing_message_term_fields_saved', 10, 3);
		
add_filter('types_message_usermeta_saved',
        'types_marketing_message_usermeta_saved', 10, 3);			

/*
 * 
 * 
 * 
 * Hooks per page
 */

function types_marketing_message_custom_post_type_saved($message, $data, $update) {
    if( isset( $data['labels']['name'] ) ) {
        $title = $data['labels']['name'];
        $type  = 'post_type';
        ob_start();
        include MNCF_ABSPATH . '/marketing/congrats-post-types/index.php';
        $message = ob_get_contents();
        ob_end_clean();

        return $message;
    }
    return '';
}

function types_marketing_message_custom_taxonomy_saved($message, $data, $update) {
    if( isset( $data['labels']['singular_name'] ) ) {
        $title = $data['labels']['singular_name'];
        $type = 'taxonomy';
        ob_start();
        include MNCF_ABSPATH . '/marketing/congrats-post-types/index.php';
        $message = ob_get_contents();
        ob_end_clean();
        return $message;
    }
    return '';
}

function types_marketing_message_custom_fields_saved($message, $title, $update) {
    $type = 'fields';
    ob_start();
    include MNCF_ABSPATH . '/marketing/congrats-post-types/index.php';
    $message = ob_get_contents();
    ob_end_clean();
    return $message;
}
function types_marketing_message_term_fields_saved($message, $title, $update) {
    $type = 'term';
    ob_start();
    include MNCF_ABSPATH . '/marketing/congrats-post-types/index.php';
    $message = ob_get_contents();
    ob_end_clean();
    return $message;
}

function types_marketing_message_usermeta_saved($message, $title, $update) {
    $type = 'usermeta';
    ob_start();
    include MNCF_ABSPATH . '/marketing/congrats-post-types/index.php';
    $message = ob_get_contents();
    ob_end_clean();
    return $message;
}

