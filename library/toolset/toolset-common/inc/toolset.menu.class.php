<?php

if ( ! defined( 'MNT_MENU' ) ) {
    define( 'MNT_MENU', true );
}

/**
 * Toolset_Menu
 *
 * Generic class for the shared menu entry for the Custom Content family.
 *
 * @since 1.9
 */

if ( ! class_exists( 'Toolset_Menu' ) ) {

    /**
     * Class to show promotion message.
     *
     * @since 1.5
     * @access  public
     */
    class Toolset_Menu {

        public $toolset_pages;

        public function __construct() {

            $this->toolset_pages = array();

            add_action( 'init',													array( &$this, 'init' ), 1 );
            add_action( 'admin_init',											array( &$this, 'admin_init' ), 1 );
            add_action( 'admin_menu',											array( &$this, 'admin_menu' ) );
            add_action( 'admin_enqueue_scripts', 								array( &$this, 'admin_enqueue_scripts' ) );

            add_filter( 'toolset_filter_register_menu_pages', 					array( &$this, 'register_debug_page_in_menu' ), 100 );
        }

        public function init() {
            $toolset_pages = array(
                'toolset-settings', 'toolset-help', 'toolset-debug-information'
            );
            $toolset_pages = apply_filters( 'toolset_filter_register_common_page_slug', $toolset_pages );
            $this->toolset_pages = $toolset_pages;
        }

        public function admin_init() {
            global $pagenow;
            if (
                $pagenow == 'admin.php'
                && isset( $_GET['page'] )
                && in_array( $_GET['page'], $this->toolset_pages )
            ) {
                $current_page = sanitize_text_field( $_GET['page'] );
                do_action( 'toolset_action_admin_init_in_toolset_page', $current_page );
            }
        }

        public function admin_menu() {
            /**
             * Ordering menu items by plugin:
             * 10: Custom Content Types
             * 20: Custom Content Access
             * 30: Custom Content Layouts
             * 40: Custom Content Views
             * 50: Custom Content CRED
             * 60: Custom Content Common - Settings, Export/Import and Help
             * 70: Custom Content Module Manager
             * 80: Custom Content Reference Sites
             * 100: Custom Content debug page
             */
            $registered_pages = apply_filters( 'toolset_filter_register_menu_pages', array() );
            if ( count( $registered_pages ) > 0 ) {

                $top_level_page_registered = false;

                while (
                    count( $registered_pages ) > 0
                    && ! $top_level_page_registered
                ) {
                    $top_level_page = array_shift( $registered_pages );
                    $top_level_page['capability'] = isset( $top_level_page['capability'] ) ? $top_level_page['capability'] : 'manage_options';
                    if ( current_user_can( $top_level_page['capability'] ) ) {
                        $hook = add_menu_page( $top_level_page['page_title'], 'Custom Content', $top_level_page['capability'], $top_level_page['slug'], $top_level_page['callback'] );
                        $this->add_menu_page_hooks( $top_level_page, $hook );
                        $top_level_page_registered = true;
                    }
                }

                if (
                    $top_level_page_registered
                    && is_array( $registered_pages )
                ) {
                    $this->add_submenu_page( $top_level_page, $top_level_page );
                    foreach ( $registered_pages as $page ) {
                        $this->add_submenu_page( $page, $top_level_page );
                    }
                }

            }
        }

        public function add_submenu_page( $page, $top_level_page ) {
            $page['capability'] = isset( $page['capability'] ) ? $page['capability'] : 'manage_options';
            $callback = isset( $page['callback'] ) ? $page['callback'] : null;
            $hook = add_submenu_page( $top_level_page['slug'], $page['page_title'], $page['menu_title'], $page['capability'], $page['slug'], $callback );
            $this->add_menu_page_hooks( $page, $hook );
        }

        public function add_menu_page_hooks( $page, $hook ) {
            global $mn_version;
            $load_action = sprintf(
                'load-%s',
                $hook
            );

            // Add the Help tab for the debug page link
            add_action( $load_action, array( $this, 'add_debug_help_tab' ) );

            if (
                ! empty( $page['load_hook'] )
                && is_callable( $page['load_hook'] )
            ) {
                add_action( $load_action, $page['load_hook'] );
            }

            if ( version_compare( $mn_version, '3.2.1', '>' ) ) {
                if (
                    ! empty( $page['contextual_help_hook'] )
                    && is_callable( $page['contextual_help_hook'] )
                ) {
                    add_action( $load_action, $page['contextual_help_hook'] );
                }
            } else {
                if ( ! empty( $page['contextual_help_legacy'] ) ) {
                    add_contextual_help( $hook, $page['contextual_help_legacy'] );
                }
            }
        }

        public function admin_enqueue_scripts() {
            global $pagenow;
            if (
                $pagenow == 'admin.php'
                && isset( $_GET['page'] )
                && in_array( $_GET['page'], $this->toolset_pages )
            ) {
                $current_page = sanitize_text_field( $_GET['page'] );
                do_action( 'toolset_enqueue_styles', array( 'toolset-common', 'toolset-notifications-css', 'font-awesome' ) );
                do_action( 'toolset_enqueue_scripts', $current_page );
            }
        }

        public function help_page() {
            // @todo add tracking data, create a utils::static method for this
            ?>
            <div class="wrap">
                <h2><?php _e( 'Custom Content Help', 'mnv-views' ) ?></h2>
                <h3 style="margin-top:3em;"><?php _e('Documentation and Support', 'mnv-views'); ?></h3>
                <ul>
                    <li>
                        <?php printf(
                            '<a target="_blank" href="http://mn-types.com/documentation/user-guides/"><strong>%s</strong></a>'.__( ' - everything you need to know about using Toolset', 'mnv-views' ),
                            __( 'User Guides', 'mnv-views')
                        ); ?>
                    </li>
                    <li>
                        <?php printf(
                            '<a target="_blank" href="http://discover-mn.com/"><strong>%s</strong></a>'.__( ' - learn to use Custom Content by experimenting with fully-functional learning sites', 'mnv-views' ),
                            __( 'Discover MN', 'mnv-views' )
                        ); ?>
                    </li>
                    <li>
                        <?php printf(
                            '<a target="_blank" href="http://mn-types.com/forums/forum/support-2/"><strong>%s</strong></a>'.__( ' - online help by support staff', 'mnv-views' ),
                            __( 'Support forum', 'mnv-views' )
                        ); ?>
                    </li>
                </ul>
                <h3 style="margin-top:3em;"><?php _e('Debug information', 'mnv-views'); ?></h3>
                <p>
                    <?php printf(
                        __( 'For retrieving debug information if asked by a support person, use the <a href="%s">debug information</a> page.', 'mnv-views' ),
                        admin_url('admin.php?page=toolset-debug-information')
                    ); ?>
                </p>
            </div>
            <?php
        }

        /**
         * Add a help tab to any page on the Custom Content menu, linking to the Custom Content debug page.
         *
         * Note that we use a hook in admin_head so the help tab here is addded after the existing ones.
         *
         * @since 2.1
         */

        public function add_debug_help_tab() {

            add_action( 'admin_head', array( $this, 'add_debug_help_tab_in_admin_head' ), 90 );

        }

        public function add_debug_help_tab_in_admin_head() {
            if (
                isset( $_GET['page'] )
                && $_GET['page'] == 'toolset-debug-information'
            ) {
                return;
            }
            $screen = get_current_screen();
            $screen->add_help_tab(
                array(
                    'id'		=> 'toolset-debug-information',
                    'title'		=> __('Custom Content Debug', 'mnv-views'),
                    'content'	=> '<p>'
                        . sprintf(
                            __( 'Need help? Grab some %1$sdebug information%2$s.', 'mnv-views' ),
                            '<a href="' . admin_url( 'admin.php?page=toolset-debug-information' ) . '">',
                            '</a>'
                        )
                        . '</p>',
                )
            );
        }

        /**
         * Register the Custom Content debug page on the Custom Content menu, on demand.
         *
         * @since 2.1
         */

        public function register_debug_page_in_menu( $pages ) {
            if (
                isset( $_GET['page'] )
                && $_GET['page'] == 'toolset-debug-information'
            ) {
                $pages[] = array(
                    'slug'			=> 'toolset-debug-information',
                    'menu_title'	=> __( 'Custom Content Debug', 'mnv-views' ),
                    'page_title'	=> __( 'Custom Content Debug', 'mnv-views' ),
                    'callback'		=> array( $this, 'debug_page' )
                );
            }
            return $pages;
        }

        public function debug_page() {
            $toolset_common_bootstrap = Toolset_Common_Bootstrap::getInstance();
            $toolset_common_sections = array(
                'toolset_debug'
            );
            $toolset_common_bootstrap->load_sections( $toolset_common_sections );
        }

    }

}