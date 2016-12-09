<?php
/*
 * Add user screen functions.
 * Included on add_action('load-user-new.php') hook.
 */
add_action( 'in_admin_footer', 'mncf_usermeta_add_user_templates' );
add_action( 'user_register', 'mncf_usermets_add_user_submit' );

/**
 * Renders templates on bottom of screen.
 */
function mncf_usermeta_add_user_templates() {

    ?>
    <script type="text/html" id="tpl-mncf-usermeta-add-user">
        <?php mncf_admin_userprofile_init( -1 ); ?>
    </script>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#createuser .submit').prepend($('#tpl-mncf-usermeta-add-user').html());
        });
    </script>
    <?php
    mncf_form_render_js_validation( '#createuser' );
}

/**
 * Hooks to 'user_register'
 * @param type $user_id
 */
function mncf_usermets_add_user_submit( $user_id ) {
    if ( isset( $_POST['mncf'] ) ) {
        mncf_admin_userprofilesave_init( $user_id );
    }
}

/**
 * Init function.
 */
function mncf_usermeta_add_user_screen_init() {
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/usermeta-post.php';
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
    remove_action( 'admin_footer', 'mncf_admin_profile_js_validation' );
}
