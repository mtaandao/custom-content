<?php

// make sure that MNCF_VERSION in embedded/bootstrap.php is the same!
if ( ! defined( 'MNCF_VERSION' ) )
    define( 'MNCF_VERSION', TYPES_VERSION );

define( 'MNCF_REPOSITORY', 'http://api.mn-types.com/' );

define( 'MNCF_ABSPATH', dirname( __FILE__ ) );

if( ! defined( 'MNCF_RELPATH' ) )
    define( 'MNCF_RELPATH', plugins_url() . '/' . basename( MNCF_ABSPATH ) );

define( 'MNCF_INC_ABSPATH', MNCF_ABSPATH . '/includes' );
define( 'MNCF_INC_RELPATH', MNCF_RELPATH . '/includes' );
define( 'MNCF_RES_ABSPATH', MNCF_ABSPATH . '/resources' );
define( 'MNCF_RES_RELPATH', MNCF_RELPATH . '/resources' );

if( ! defined( 'MNCF_EMBEDDED_TOOLSET_ABSPATH' ) )
    define( 'MNCF_EMBEDDED_TOOLSET_ABSPATH' , MNCF_EMBEDDED_ABSPATH . '/toolset' );

if( ! defined( 'MNCF_EMBEDDED_TOOLSET_RELPATH'))
    define( 'MNCF_EMBEDDED_TOOLSET_RELPATH', MNCF_EMBEDDED_RELPATH . '/toolset' );


require_once MNCF_INC_ABSPATH . '/constants.php';
/*
 * Since Types 1.2 we load all embedded code without conflicts
 */
require_once MNCF_ABSPATH . '/embedded/types.php';

require_once MNCF_EMBEDDED_TOOLSET_ABSPATH . '/onthego-resources/loader.php';
onthego_initialize( MNCF_EMBEDDED_TOOLSET_ABSPATH . '/onthego-resources/',
    MNCF_EMBEDDED_TOOLSET_RELPATH . '/onthego-resources/' );
	
require MNCF_EMBEDDED_TOOLSET_ABSPATH . '/toolset-common/loader.php';
toolset_common_initialize( MNCF_EMBEDDED_TOOLSET_ABSPATH . '/toolset-common/', 
	MNCF_EMBEDDED_TOOLSET_RELPATH . '/toolset-common/' );

// Plugin mode only hooks
add_action( 'plugins_loaded', 'mncf_init' );

// init hook for module manager
add_action( 'init', 'mncf_mn_init' );


add_action( 'after_setup_theme', 'mncf_initialize_autoloader_full', 20 );

/**
 * Configure autoloader also for full Types (it has been loaded by embedded Types by now).
 */
function mncf_initialize_autoloader_full() {
	MNCF_Autoloader::get_instance()->add_path( MNCF_INC_ABSPATH . '/classes' );
}

/**
 * Deactivation hook.
 *
 * Reset some of data.
 */
function mncf_deactivation_hook()
{
    // Delete messages
    delete_option( 'mncf-messages' );
    delete_option( 'MNCF_VERSION' );
    /**
     * check site kind and if do not exist, delete types_show_on_activate
     */
    if ( !get_option('types-site-kind') ) {
        delete_option('types_show_on_activate');
    }
}

/**
 * Activation hook.
 *
 * Reset some of data.
 * 
 * @deprecated
 */
function mncf_activation_hook()
{
    $version = get_option('MNCF_VERSION');
    if ( empty($version) ) {
        $version = 0;
        add_option('MNCF_VERSION', 0, null, 'no');
    }
    if ( version_compare($version, MNCF_VERSION) < 0 ) {
        update_option('MNCF_VERSION', MNCF_VERSION);
    }
    if( 0 == version_compare(MNCF_VERSION, '1.6.5')) {
        add_option('types_show_on_activate', 'show', null, 'no');
        if ( get_option('types-site-kind') ) {
            update_option('types_show_on_activate', 'hide');
        }
    }
}

/**
 * Main init hook.
 */
function mncf_init()
{
    if ( !defined( 'EDITOR_ADDON_RELPATH' ) ) {
        define( 'EDITOR_ADDON_RELPATH', MNCF_EMBEDDED_TOOLSET_RELPATH . '/toolset-common/visual-editor' );
    }

    if ( is_admin() ) {
        require_once MNCF_ABSPATH . '/admin.php';
    }
    /**
     * remove unused option
     */
    $version_from_db = get_option('mncf-version', 0);
    if ( version_compare(MNCF_VERSION, $version_from_db) > 0 ) {
        delete_option('mncf-survey-2014-09');
        update_option('mncf-version', MNCF_VERSION);
    }
}

//Render Installer packages
function installer_content()
{
    echo '<div class="wrap">';
    $config['repository'] = array(); // required
    MN_Installer_Show_Products($config);
    echo "</div>";
}

/**
 * MN Main init hook.
 */
function mncf_mn_init()
{
    if ( is_admin() ) {
        require_once MNCF_ABSPATH . '/admin.php';
    }
}



function ajax_mncf_is_reserved_name() {

    // slug
    $name = isset( $_POST['slug'] )
        ? sanitize_text_field( $_POST['slug'] )
        : '';

    // context
    $context = isset( $_POST['context'] )
        ? sanitize_text_field( $_POST['context'] )
        : false;

    // check also page slugs
    $check_pages = isset( $_POST['check_pages'] ) && $_POST['check_pages'] == false
        ? false
        : true;

    // slug pre save
    if( isset( $_POST['slugPreSave'] )
        && $_POST['slugPreSave'] !== 0 ) {

        // for taxonomy
        if( $context == 'taxonomy' )
            $_POST['ct']['mncf-tax'] = sanitize_text_field( $_POST['slugPreSave'] );

        // for post_type
        if( $context == 'post_type' )
            $_POST['ct']['mncf-post-type'] = sanitize_text_field( $_POST['slugPreSave'] );
    }

    if( $context == 'post_type' || $context == 'taxonomy' ) {
        $used_reserved = mncf_is_reserved_name( $name, $context, $check_pages );

        if( $used_reserved ) {
            die( json_encode( array( 'already_in_use' => 1 ) ) );
        }
    }

    // die( json_encode( $_POST ) );
    die( json_encode( array( 'already_in_use' => 0 ) ) );
}

add_action( 'mn_ajax_mncf_get_forbidden_names', 'ajax_mncf_is_reserved_name' );

/**
 * Checks if name is reserved.
 *
 * @param type $name
 * @return type
 */
function mncf_is_reserved_name($name, $context, $check_pages = true)
{
    $name = strval( $name );
    /*
     *
     * If name is empty string skip page cause there might be some pages without name
     */
    if ( $check_pages && !empty( $name ) ) {
        global $mndb;
        $page = $mndb->get_var(
            $mndb->prepare(
                "SELECT ID FROM $mndb->posts WHERE post_name = %s AND post_type='page'",
                sanitize_title( $name )
            )
        );
        if ( !empty( $page ) ) {
            return new MN_Error( 'mncf_reserved_name', __( 'You cannot use this slug because there is already a page by that name. Please choose a different slug.',
                                    'mncf' ) );
        }
    }

    // Add custom types
    $custom_types = get_option(MNCF_OPTION_NAME_CUSTOM_TYPES, array() );
    $post_types = get_post_types();
    if ( !empty( $custom_types ) ) {
        $custom_types = array_keys( $custom_types );
        $post_types = array_merge( array_combine( $custom_types, $custom_types ),
                $post_types );
    }
    // Unset to avoid checking itself
    /* Note: This will unset any post type with the same slug, so it's possible to overwrite it
    if ( $context == 'post_type' && isset( $post_types[$name] ) ) {
        unset( $post_types[$name] );
    }
    */
    // abort test...
    if( $context == 'post_type' // ... for post type ...
        && isset( $_POST['ct']['mncf-post-type'] ) // ... if it's an already saved taxonomy ...
        && $_POST['ct']['mncf-post-type'] == $name // ... and the slug didn't changed.
    ) {
        return false;
    }

    // Add taxonomies
    $custom_taxonomies = (array) get_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
    $taxonomies = get_taxonomies();
    if ( !empty( $custom_taxonomies ) ) {
        $custom_taxonomies = array_keys( $custom_taxonomies );
        $taxonomies = array_merge( array_combine( $custom_taxonomies,
                        $custom_taxonomies ), $taxonomies );
    }

    // Unset to avoid checking itself
    /* Note: This will unset any taxonomy with the same slug, so it's possible to overwrite it
    if ( $context == 'taxonomy' && isset( $taxonomies[$name] ) ) {
        unset( $taxonomies[$name] );
    }
    */

    // abort test...
    if( $context == 'taxonomy' // ... for taxonomy ...
        && isset( $_POST['ct']['mncf-tax'] ) // ... if it's an already saved taxonomy ...
        && $_POST['ct']['mncf-tax'] == $name // ... and the slug didn't changed.
    ) {
        return false;
    }

    $reserved_names = mncf_reserved_names();
    $reserved = array_merge( array_combine( $reserved_names, $reserved_names ),
            array_merge( $post_types, $taxonomies ) );

    return in_array( $name, $reserved ) ? new MN_Error( 'mncf_reserved_name', __( 'You cannot use this slug because it is a reserved word, used by Mtaandao. Please choose a different slug.',
                            'mncf' ) ) : false;
}

/**
 * Reserved names.
 *
 * @return type
 */
function mncf_reserved_names()
{
    $reserved = array(
        'action',
        'attachment',
        'attachment_id',
        'author',
        'author_name',
        'calendar',
        'cat',
        'category',
        'category__and',
        'category__in',
        'category_name',
        'category__not_in',
        'comments_per_page',
        'comments_popup',
        'cpage',
        'day',
        'debug',
        'error',
        'exact',
        'feed',
        'field',
        'fields',
        'format',
        'hour',
        'lang',
        'link_category',
        'm',
        'minute',
        'mode',
        'monthnum',
        'more',
        'name',
        'nav_menu',
        'nopaging',
        'offset',
        'order',
        'orderby',
        'p',
        'page',
        'paged',
        'page_id',
        'pagename',
        'parent',
        'pb',
        'perm',
        'post',
        'post_format',
        'post__in',
        'post_mime_type',
        'post__not_in',
        'posts',
        'posts_per_archive_page',
        'posts_per_page',
        'post_status',
        'post_tag',
        'post_type',
        'preview',
        'robots',
        's',
        'search',
        'second',
        'sentence',
        'shomnosts',
        'static',
        'subpost',
        'subpost_id',
        'tag',
        'tag__and',
        'tag_id',
        'tag__in',
        'tag__not_in',
        'tag_slug__and',
        'tag_slug__in',
        'taxonomy',
        'tb',
        'term',
        'type',
        'w',
        'withcomments',
        'withoutcomments',
        'year',
    );

    return apply_filters( 'mncf_reserved_names', $reserved );
}

add_action( 'icl_pro_translation_saved', 'mncf_fix_translated_post_relationships' );

function mncf_fix_translated_post_relationships($post_id)
{
    require_once MNCF_EMBEDDED_ABSPATH . '/includes/post-relationship.php';
    mncf_post_relationship_set_translated_parent( $post_id );
    mncf_post_relationship_set_translated_children( $post_id );
}

// this is for testing promotional message
// set MNCF_PAYED true in your mn-config
if ( !defined( 'MNCF_PAYED' ) )
    define( 'MNCF_PAYED', true );

if( ! function_exists( 'mncf_is_client' ) ) {
    /**
     * Check if user is a client, who bought Toolset
     * @return bool
     */
    function mncf_is_client() {

        // for testing
        if( ! MNCF_PAYED )
            return false;

        // check db stored value
        if( get_option( 'mncf-is-client' ) ) {
            $settings = mncf_get_settings( 'help_box' );

            // prioritise settings if available
            if( $settings ) {
                switch( $settings ) {
                    case 'by_types':
                    case 'all':
                        return false;
                    case 'no':
                        return true;
                }
            }

            $is_client = get_option( 'mncf-is-client' );

            // client
            if( $is_client === 'yes' )
                return true;

            // user
            return false;
        }

        // no db stored value
        // make sure get_plugins() is available
        if ( ! function_exists( 'get_plugins' ) )
            require_once ABSPATH . 'admin/includes/plugin.php';

        // all plugins
        $plugins = get_plugins();

        // check each plugin
        foreach( $plugins as $plugin ) {
            // skip plugin that is not created by us
            if( $plugin['Author'] != 'OnTheGoSystems' )
                continue;

            // check for toolset plugin and not embedded = user bought toolset
            if( preg_match( "#(access|cred|layouts|module manager|views)#i", $plugin['Name'] )
                && ! preg_match( '#embedded#i', $plugin['Name'] ) ) {
                add_option( 'mncf-is-client', 'yes' );

                // set settings "help box" ounce to none
                $settings = get_option( 'mncf_settings', array() );
                $settings['help_box'] = 'no';
                update_option( 'mncf_settings', $settings );

                return true;
            }
        }

        // if script comes to this point we have no option "mncf-is-client" set
        // and also no bought toolset plugin
        add_option( 'mncf-is-client', 'no' );
        return false;
    }
}

/**
 * On plugin activation clear option "mncf-is-client"
 */
if( ! function_exists( 'mncf_clear_option_is_client' ) ) {
    function mncf_clear_option_is_client() {
        $option_is_client = get_option( 'mncf-is-client' );
        if( $option_is_client == 'no' ) {
            delete_option( 'mncf-is-client' );
        }

    }
}

add_action( 'activated_plugin', 'mncf_clear_option_is_client' );


// Make sure this runs after mncf_init_custom_types_taxonomies() so that our custom taxonomies and post types are
// already registered at that point. See types-676.
add_action( 'init', 'mncf_upgrade_stored_taxonomies_with_builtin', apply_filters( 'mncf_init_custom_types_taxonomies', 10 ) + 100 );

/**
 * Make sure in built taxonomies are stored.
 *
 * This is an upgrade routine for Types older than 1.9. The code will run only once.
 *
 * @since 1.9
 */
function mncf_upgrade_stored_taxonomies_with_builtin() {
	$stored_taxonomies = get_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );

	if( empty( $stored_taxonomies ) || !isset( $stored_taxonomies['category'] ) || !isset( $stored_taxonomies['post_tag'] ) ) {
		
		$taxonomies = Types_Utils::object_to_array_deep( get_taxonomies( array( 'public' => true, '_builtin' => true ), 'objects' ) );

		if( isset( $taxonomies['post_format'] ) )
			unset( $taxonomies['post_format'] );

		foreach( $taxonomies as $slug => $settings ) {
			if( isset( $stored_taxonomies[$slug] ) )
				continue;

			$taxonomies[$slug]['slug'] = $slug;
			foreach( $settings['object_type'] as $support ) {
				$taxonomies[$slug]['supports'][$support] = 1;
			}

			$stored_taxonomies[$slug] = $taxonomies[$slug];
		}

		update_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, $stored_taxonomies );
	}
}

add_action( 'mn_ajax_types_notice_dismiss_permanent', 'types_ajax_notice_dismiss_permanent' );

function types_ajax_notice_dismiss_permanent() {
    if ( ! isset( $_POST['types_notice_dismiss_permanent'] ) || ! preg_match( '/^[A-Za-z0-9_-]+$/', $_POST['types_notice_dismiss_permanent'] ) )
        return;

    $user_dismissed_notices = get_user_meta( get_current_user_id(), '_types_notice_dismiss_permanent', true )
        ? get_user_meta( get_current_user_id(), '_types_notice_dismiss_permanent', true )
        : array();

    $user_dismissed_notices[] = sanitize_text_field( $_POST['types_notice_dismiss_permanent'] );
    update_user_meta( get_current_user_id(), '_types_notice_dismiss_permanent', $user_dismissed_notices );
}

function types_plugin_action_links ( $links ) {
    $feedback = array(
        '<a id="types-leave-feedback-trigger" href="https://www.surveymonkey.com/r/types-uninstall" target="_blank">' . __( 'Leave feedback', 'mncf' ) . '</a>',
    );
    return array_merge( $links, $feedback );
}

add_action( 'load-plugins.php', 'types_ask_for_feedback_on_deactivation' );

function types_ask_for_feedback_on_deactivation() {
    // abort if message was shown in the last 90 days
    $user_dismissed_notices = get_user_meta( get_current_user_id(), '_types_feedback_dont_show_until', true );
    if( $user_dismissed_notices && current_time( 'timestamp' ) < $user_dismissed_notices )
        return;

    add_action( 'admin_footer', 'types_feedback_on_deactivation_dialog' );
    add_action( 'admin_enqueue_scripts', 'types_feedback_on_deactivation_scripts' );

    function types_feedback_on_deactivation_dialog() { ?>
        <div id="types-feedback" style="display:none;width:500px;">
        <div class="types-message-icon" style="float: left; margin: 2px 0 0 0; padding: 0 15px 0 0;">
            <?php //<span class="icon-toolset-logo"></span> ?>
            <span class="icon-types-logo ont-icon-64" style="color: #f05a29;""></span>
        </div>

        <div style="margin-top: 8px;">
            <p>
                <?php _e( "Do you have a minute to tell us why you're removing Types?", 'mncf' ); ?>
            </p>

            <a id="types-leave-feedback-dialog-survey-link" class="button-primary types-button types-external-link" style="margin-right: 8px;" target="_blank"
               href="https://www.surveymonkey.com/r/types-uninstall">
                <?php _e( 'Leave feedback', 'mncf' ); ?>
            </a>
            <a id="types-leave-feedback-dialog-survey-link-cancel" class="button-secondary" target="_blank"
               href="javascript:void(0);">
                <?php _e( 'Skip feedback', 'mncf' ); ?>
            </a>
        </div>

        <br style="clear:both;" />
        </div>
    <?php }
    function types_feedback_on_deactivation_scripts() {
        mn_enqueue_script(
            'types-feedback-on-deactivation',
            TYPES_RELPATH . '/public/js/feedback-on-deactivation.js',
            array( 'jquery-ui-dialog' ),
            TYPES_VERSION,
            true
        );

        mn_enqueue_style(
            'types-information',
            TYPES_RELPATH . '/public/css/information.css',
            array( 'mn-jquery-ui-dialog' ),
            TYPES_VERSION
        );
    }
}

add_action( 'mn_ajax_types_feedback_dont_show_for_90_days', 'types_feedback_dont_show_for_90_days' );

function types_feedback_dont_show_for_90_days() {
    $in_90_days = strtotime( '+90 days', current_time( 'timestamp' ) );
    update_user_meta( get_current_user_id(), '_types_feedback_dont_show_until', $in_90_days );
}