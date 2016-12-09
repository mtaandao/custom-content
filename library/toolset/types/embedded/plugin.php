<?php

add_action( 'plugins_loaded', 'mncf_embedded_load_or_deactivate' );

function mncf_embedded_load_or_deactivate()
{
    if ( function_exists('mncf_activation_hook') ) {
        add_action( 'admin_init', 'mncf_embedded_deactivate' );
        add_action( 'admin_notices', 'mncf_embedded_deactivate_notice' );
    } else {
        require_once 'types.php';
    }
}

/**
 * mncf_embedded_deactivate
 *
 * Deactivate this plugin
 *
 * @since 1.6.2
 */

function mncf_embedded_deactivate()
{
    $plugin = plugin_basename( __FILE__ );
    deactivate_plugins( $plugin );
}

/**
 * mncf_embedded_deactivate_notice
 *
 * Deactivate notice for this plugin
 *
 * @since 1.6.2
 */

function mncf_embedded_deactivate_notice()
{
?>
    <div class="error">
        <p>
            <?php _e( 'Types Embedded was <strong>deactivated</strong>! You are already running the complete Types plugin, so this one is not needed anymore.', 'mncf' ); ?>
        </p>
    </div>
<?php
}
