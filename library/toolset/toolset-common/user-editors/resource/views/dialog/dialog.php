<?php

if( ! interface_exists( 'Toolset_User_Editors_Resource_Interface', false ) )
	require_once( TOOLSET_COMMON_PATH . '/user-editors/resource/interface.php' );

class Toolset_User_Editors_Resource_Views_Dialog
	implements Toolset_User_Editors_Resource_Interface {
	
	private static $instance;
	private $loaded;

	private function __construct(){}
	private function __clone(){}

	/**
	 * @return Toolset_User_Editors_Resource_Views_Dialog
	 */
	public static function getInstance() {
		if( self::$instance === null )
			self::$instance = new self;

		return self::$instance;
	}

	private function isLoaded() {
		$this->loaded = true;
	}

	public function load() {
		// abort on admin screen or if already loaded
		if( is_admin() || $this->loaded !== null )
			return;

		// this allows Views "Fields and Views" dialogs to load without a post type
		add_filter( 'mnv_filter_dialog_for_editors_requires_post', '__return_false' );
		add_filter( 'mnv_render_dialogs_on_frontend', '__return_true' );
		require_once( MNV_PATH . '/inc/classes/shortcodes/selector/frontend.php' );

		add_action( 'init', array( $this, '_actionRegisterViewsButton') );
		add_action( 'mn_enqueue_scripts', array( $this, '_actionScriptsAndStyles' ) );

		// woocommerce views
		if( function_exists( 'wcviews_shortcodes_gui_init' )
		    && function_exists( 'wcviews_shortcodes_gui_js_init' ) ) {
			add_action( 'init', 'wcviews_shortcodes_gui_init' );
			add_action( 'mn_head', 'wcviews_shortcodes_gui_js_init' );
		}

		$this->isLoaded();
	}

	public function _actionRegisterViewsButton() {

		if( ! class_exists( 'MN_Views' ) ) {
			remove_action( 'mn_enqueue_scripts', array( $this, '_actionScriptsAndStyles' ) );
			$this->isLoaded();
			return;
		}

		$view = new MN_Views();
		$view->mnv_register_assets();
		$view->add_dialog_to_editors();
	}

	public function _actionScriptsAndStyles() {
		if ( ! mn_script_is( 'views-shortcodes-gui-script' ) ) {
			mn_enqueue_script( 'views-shortcodes-gui-script' );
		}
		if ( ! mn_script_is( 'jquery-ui-resizable' ) ) {
			mn_enqueue_script('jquery-ui-resizable');
		}
		if ( ! mn_style_is( 'views-admin-css' ) ) {
			mn_enqueue_style( 'views-admin-css' );
		}

		if ( ! mn_script_is( 'views-codemirror-conf-script' ) ) {
			mn_enqueue_script( 'views-codemirror-conf-script' );
		}
		if ( ! mn_style_is( 'toolset-meta-html-codemirror-css' ) ) {
			mn_enqueue_style( 'toolset-meta-html-codemirror-css' );
		}
		if ( ! mn_script_is( 'views-embedded-script' ) ) {
			mn_enqueue_script( 'views-embedded-script' );
		}
		if ( ! mn_script_is( 'views-utils-script' ) ) {
			mn_enqueue_script( 'views-utils-script' );
		}

		mn_enqueue_style(
			'toolset-user-editors-ressource-views-dialog',
			TOOLSET_COMMON_URL . '/user-editors/resource/views/dialog/dialog.css',
			array(),
			TOOLSET_COMMON_VERSION
		);
	}
}