<?php
/**
 *
 * Admin functions
 *
 *
 */
require_once MNCF_ABSPATH.'/marketing.php';
require_once MNCF_ABSPATH.'/includes/classes/class.mncf.roles.php';
MNCF_Roles::getInstance();
/*
 * This needs to be called after main 'init' hook.
 * Main init hook calls required Types code for frontend.
 * Admin init hook only in admin area.
 *
 */
add_action( 'admin_init', 'mncf_admin_init_hook', 11 );

add_action( 'init', 'mncf_init_admin_pages' );

add_action( 'mncf_admin_page_init', 'mncf_enqueue_scripts' );

// OMG, why so early? At this point we don't even have embedded Types (with functions.php).
if ( defined( 'DOING_AJAX' ) ) {
    require_once MNCF_INC_ABSPATH . '/ajax.php';
    if ( isset($_REQUEST['action']) ) {
        switch( $_REQUEST['action']){
            /**
             * post edit screen
             */
        case 'mncf_edit_post_get_child_fields_screen':
        case 'mncf_edit_post_get_icons_list':
        case 'mncf_edit_post_save_child_fields':
            require_once MNCF_INC_ABSPATH.'/classes/class.types.admin.edit.post.type.php';
            new Types_Admin_Edit_Post_Type();
            break;
            /**
             * custom fields group edit screen
             */
        case 'mncf_ajax_filter':
        case 'mncf_edit_field_choose':
        case 'mncf_edit_field_insert':
        case 'mncf_edit_field_select':
        case 'mncf_edit_field_add_existed': {

	        require_once MNCF_INC_ABSPATH.'/classes/class.types.admin.edit.custom.fields.group.php';

	        // Be careful here. For some AJAX actions we rely on the fact that the page parameter is not set and/or
	        // that post and user fields can use the same handler (which is originally meant for post fields only).

	        // We don't have functions.php at this point, can't use mncf_getpost().
	        $current_page = isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : Types_Admin_Edit_Custom_Fields_Group::PAGE_NAME;
	        if( in_array( $current_page, array( Types_Admin_Edit_Custom_Fields_Group::PAGE_NAME, 'mncf-edit-usermeta' ) ) ) {
		        new Types_Admin_Edit_Custom_Fields_Group();
	        }

	        // For other pages, we will initialize during the 'init' hook when the autoloader is already available.
	        // At this point we don't even have access to names of the pages.
	        // See mncf_init_admin_pages().
	        break;
        }
        case 'mncf_edit_field_condition_get':
        case 'mncf_edit_field_condition_get_row':
        case 'mncf_edit_field_condition_save':
        case 'mncf_edit_custom_field_group_get':
            require_once MNCF_INC_ABSPATH.'/classes/class.types.fields.conditional.php';
            new Types_Fields_Conditional();
            break;
        case 'mncf_edit_post_get_fields_box':
            require_once MNCF_INC_ABSPATH.'/classes/class.types.admin.fields.php';
            new Types_Admin_Fields();
            break;
        }
    }
}
include_once MNCF_ABSPATH.'/includes/classes/class.mncf.marketing.messages.php';
new MNCF_Types_Marketing_Messages();

/**
 * last edit flag
 */
if ( !defined('TOOLSET_EDIT_LAST' )){
    define( 'TOOLSET_EDIT_LAST', '_toolset_edit_last');
}

/**
 * last author
 */
if ( !defined('MNCF_AUTHOR' )){
    define( 'MNCF_AUTHOR', '_mncf_author_id');
}

/**
 * admin_init hook.
 */
function mncf_admin_init_hook()
{
    mn_register_style('mncf-css-embedded', MNCF_EMBEDDED_RES_RELPATH . '/css/basic.css', array(), MNCF_VERSION );

    mn_enqueue_style('toolset-dashicons');

}


/**
 * Initialize admin pages.
 *
 * @todo This, also, needs a review very badly.
 * @since 1.9
 */
function mncf_init_admin_pages() {

	if( defined( 'DOING_AJAX' ) ) {
		$action = mncf_getpost( 'action' );
		$current_page = mncf_getpost( 'page' );

		switch( $action ) {

			case 'mncf_edit_field_select':
			case 'mncf_ajax_filter': {
				if( MNCF_Page_Edit_Termmeta::PAGE_NAME == $current_page ) {
					MNCF_Page_Edit_Termmeta::get_instance()->initialize_ajax_handler();
				}
				break;
			}
		}
	}


}


function mncf_admin_calculate_menu_page_capability( $data ) {
	$capability = array_key_exists( 'capability', $data ) ? $data['capability'] : 'manage_options';
    $mncf_capability = apply_filters( 'mncf_capability', $capability, $data, $data['slug'] );
    $mncf_capability = apply_filters( 'mncf_capability' . $data['slug'], $capability, $data, $data['slug'] );
    /**
     * allow change capability  by filter
     * full list https://goo.gl/OJYTvl
     */
    if ( isset( $data['capability_filter'] ) ) {
        $mncf_capability = apply_filters( $data['capability_filter'], $mncf_capability, $data, $data['slug'] );
    }
	return $mncf_capability;
}

function mncf_admin_calculate_menu_page_load_hook( $data ) {
	$load_hook = '';
	if ( array_key_exists( 'load_hook', $data ) ) {
		$load_hook = $data['load_hook'];
	} else if ( 
		array_key_exists( 'callback', $data ) 
		&& is_string( $data['callback' ] ) 
	) {
        $load_hook = sprintf( '%s_hook', $data['callback'] );
    }
	return $load_hook;
}


/**
 * Add legacy menu pages. 
 * 
 * This is indirectly hooked to toolset_filter_register_menu_pages through the Types_Admin_Menu controller.
 * 
 * @param $pages
 * @return mixed
 * @since 2.0
 */
function mncf_admin_toolset_register_menu_pages( $pages ) {
	if( ! apply_filters( 'types_register_pages', true ) )
		return $pages;
	
	require_once MNCF_ABSPATH . '/help.php';
	
	$current_page = '';
	if ( isset( $_GET['page'] ) ) {
	    $current_page = sanitize_text_field( $_GET['page'] );
	}
	
	$pages['mncf-cpt'] = array(
		'slug'				=> 'mncf-cpt',
        'menu_title'		=> __( 'Post Types', 'mncf' ),
        'page_title'		=> __( 'Post Types', 'mncf' ),
        'callback'  		=> 'mncf_admin_menu_summary_cpt',
        'capability_filter'	=> 'mncf_cpt_view',
        'capability'		=> MNCF_CUSTOM_POST_TYPE_VIEW,
    );
	$pages['mncf-cpt']['capability'] = mncf_admin_calculate_menu_page_capability( $pages['mncf-cpt'] );
	$pages['mncf-cpt']['load_hook'] = mncf_admin_calculate_menu_page_load_hook( $pages['mncf-cpt'] );
	$pages['mncf-cpt']['contextual_help_legacy'] = mncf_admin_help( 'mncf-cpt' );
	$pages['mncf-cpt']['contextual_help_hook'] = 'mncf_admin_help_add_tabs_load_hook';
	
	if ( $current_page == 'mncf-edit-type' ) {
		$pages['mncf-edit-type'] = array(
			'slug'				=> 'mncf-edit-type',
			'menu_title'		=> isset( $_GET['mncf-post-type'] ) ? __( 'Edit Post Type', 'mncf' ) : __( 'Add New Post Type', 'mncf' ),
			'page_title'		=> isset( $_GET['mncf-post-type'] ) ? __( 'Edit Post Type', 'mncf' ) : __( 'Add New Post Type', 'mncf' ),
			'callback'  		=> 'mncf_admin_menu_edit_type',
			'capability'		=> MNCF_CUSTOM_FIELD_EDIT,
			'load_hook'			=> 'mncf_admin_menu_edit_type_hook'
		);
		$pages['mncf-edit-type']['capability'] = mncf_admin_calculate_menu_page_capability( $pages['mncf-edit-type'] );
		$pages['mncf-edit-type']['load_hook'] = mncf_admin_calculate_menu_page_load_hook( $pages['mncf-edit-type'] );
		$pages['mncf-edit-type']['contextual_help_legacy'] = mncf_admin_help( 'mncf-edit-type' );
		$pages['mncf-edit-type']['contextual_help_hook'] = 'mncf_admin_help_add_tabs_load_hook';
	}
	
	if ( $current_page == 'mncf-view-type' ) {
		$pages['mncf-view-type'] = array(
			'slug'				=> 'mncf-view-type',
			'menu_title'		=> __( 'View Post Type', 'mncf' ),
			'page_title'		=> __( 'View Post Type', 'mncf' ),
			'callback'  		=> 'mncf_admin_menu_edit_type',
			'capability'		=> MNCF_CUSTOM_FIELD_VIEW,
			'load_hook'			=> 'mncf_admin_menu_edit_type_hook'
		);
		$pages['mncf-view-type']['capability'] = mncf_admin_calculate_menu_page_capability( $pages['mncf-view-type'] );
		$pages['mncf-view-type']['load_hook'] = mncf_admin_calculate_menu_page_load_hook( $pages['mncf-view-type'] );
		$pages['mncf-view-type']['contextual_help_legacy'] = mncf_admin_help( 'mncf-view-type' );
		$pages['mncf-view-type']['contextual_help_hook'] = 'mncf_admin_help_add_tabs_load_hook';
	}
	
	$pages['mncf-ctt'] = array(
		'slug'				=> 'mncf-ctt',
        'menu_title'		=> __( 'Taxonomies', 'mncf' ),
        'page_title'		=> __( 'Taxonomies', 'mncf' ),
        'callback'			=> 'mncf_admin_menu_summary_ctt',
        'capability_filter' => 'mncf_ctt_view',
        'capability'		=> MNCF_CUSTOM_TAXONOMY_VIEW,
    );
	$pages['mncf-ctt']['capability'] = mncf_admin_calculate_menu_page_capability( $pages['mncf-ctt'] );
	$pages['mncf-ctt']['load_hook'] = mncf_admin_calculate_menu_page_load_hook( $pages['mncf-ctt'] );
	$pages['mncf-ctt']['contextual_help_legacy'] = mncf_admin_help( 'mncf-ctt' );
	$pages['mncf-ctt']['contextual_help_hook'] = 'mncf_admin_help_add_tabs_load_hook';
	
	if ( $current_page == 'mncf-edit-tax' ) {
		$pages['mncf-edit-tax'] = array(
			'slug'				=> 'mncf-edit-tax',
			'menu_title'		=> isset( $_GET['mncf-tax'] ) ? __( 'Edit Taxonomy', 'mncf' ) : __( 'Add New Taxonomy', 'mncf' ),
			'page_title'		=> isset( $_GET['mncf-tax'] ) ? __( 'Edit Taxonomy', 'mncf' ) : __( 'Add New Taxonomy', 'mncf' ),
			'callback'  		=> 'mncf_admin_menu_edit_tax',
			'capability'		=> MNCF_CUSTOM_TAXONOMY_EDIT,
			'load_hook'			=> 'mncf_admin_menu_edit_tax_hook'
		);
		$pages['mncf-edit-tax']['capability'] = mncf_admin_calculate_menu_page_capability( $pages['mncf-edit-tax'] );
		$pages['mncf-edit-tax']['load_hook'] = mncf_admin_calculate_menu_page_load_hook( $pages['mncf-edit-tax'] );
		$pages['mncf-edit-tax']['contextual_help_legacy'] = mncf_admin_help( 'mncf-edit-tax' );
		$pages['mncf-edit-tax']['contextual_help_hook'] = 'mncf_admin_help_add_tabs_load_hook';
	}
	
	if ( $current_page == 'mncf-view-tax' ) {
		$pages['mncf-view-tax'] = array(
			'slug'				=> 'mncf-view-tax',
			'menu_title'		=> __( 'View Taxonomy', 'mncf' ),
			'page_title'		=> __( 'View Taxonomy', 'mncf' ),
			'callback'  		=> 'mncf_admin_menu_edit_tax',
			'capability'		=> MNCF_CUSTOM_TAXONOMY_VIEW,
			'load_hook'			=> 'mncf_admin_menu_edit_tax_hook'
		);
		$pages['mncf-view-tax']['capability'] = mncf_admin_calculate_menu_page_capability( $pages['mncf-view-tax'] );
		$pages['mncf-view-tax']['load_hook'] = mncf_admin_calculate_menu_page_load_hook( $pages['mncf-view-tax'] );
		$pages['mncf-view-tax']['contextual_help_legacy'] = mncf_admin_help( 'mncf-view-tax' );
		$pages['mncf-view-tax']['contextual_help_hook'] = 'mncf_admin_help_add_tabs_load_hook';
	}
	
	
	$pages['mncf-cf'] = array(
		'slug'				=> 'mncf-cf',
        'menu_title'		=> __( 'Post Fields', 'mncf' ),
        'page_title'		=> __( 'Post Fields', 'mncf' ),
        'callback'			=> 'mncf_admin_menu_summary',
        'capability_filter' => 'mncf_cf_view',
        'capability'		=> MNCF_CUSTOM_FIELD_VIEW,
    );
	$pages['mncf-cf']['capability'] = mncf_admin_calculate_menu_page_capability( $pages['mncf-cf'] );
	$pages['mncf-cf']['load_hook'] = mncf_admin_calculate_menu_page_load_hook( $pages['mncf-cf'] );
	$pages['mncf-cf']['contextual_help_legacy'] = mncf_admin_help( 'mncf-cf' );
	$pages['mncf-cf']['contextual_help_hook'] = 'mncf_admin_help_add_tabs_load_hook';
	
	if ( $current_page == 'mncf-edit' ) {
		$pages['mncf-edit'] = array(
			'slug'				=> 'mncf-edit',
			'menu_title'		=> isset( $_GET['group_id'] ) ? __( 'Edit Group', 'mncf' ) : __( 'Add New Post Field Group', 'mncf' ),
			'page_title'		=> isset( $_GET['group_id'] ) ? __( 'Edit Group', 'mncf' ) : __( 'Add New Post Field Group', 'mncf' ),
			'callback'			=> 'mncf_admin_menu_edit_fields',
			'capability'		=> MNCF_CUSTOM_FIELD_VIEW,
			'load_hook'			=> 'mncf_admin_menu_edit_fields_hook'
		);
		$pages['mncf-edit']['capability'] = mncf_admin_calculate_menu_page_capability( $pages['mncf-edit'] );
		$pages['mncf-edit']['load_hook'] = mncf_admin_calculate_menu_page_load_hook( $pages['mncf-edit'] );
		$pages['mncf-edit']['contextual_help_legacy'] = mncf_admin_help( 'mncf-edit' );
		$pages['mncf-edit']['contextual_help_hook'] = 'mncf_admin_help_add_tabs_load_hook';
	}
	
	if ( $current_page == 'mncf-view-custom-field' ) {
		$pages['mncf-view-custom-field'] = array(
			'slug'				=> 'mncf-view-custom-field',
			'menu_title'		=> __( 'View Post Field Group', 'mncf' ),
			'page_title'		=> __( 'View Post Field Group', 'mncf' ),
			'callback'			=> 'mncf_admin_menu_edit_fields',
			'capability'		=> MNCF_CUSTOM_FIELD_VIEW,
		);
		$pages['mncf-view-custom-field']['capability'] = mncf_admin_calculate_menu_page_capability( $pages['mncf-view-custom-field'] );
		$pages['mncf-view-custom-field']['load_hook'] = mncf_admin_calculate_menu_page_load_hook( $pages['mncf-view-custom-field'] );
		$pages['mncf-view-custom-field']['contextual_help_legacy'] = mncf_admin_help( 'mncf-view-custom-field' );
		$pages['mncf-view-custom-field']['contextual_help_hook'] = 'mncf_admin_help_add_tabs_load_hook';
	}

	$MNCF_Page_Listing_Termmeta = MNCF_Page_Listing_Termmeta::get_instance();
	$pages[MNCF_Page_Listing_Termmeta::PAGE_NAME] = $MNCF_Page_Listing_Termmeta->add_submenu_page();
	
	
	if ( $current_page == MNCF_Page_Edit_Termmeta::PAGE_NAME ) {
		$MNCF_Page_Edit_Termmeta = MNCF_Page_Edit_Termmeta::get_instance();
		$pages[MNCF_Page_Edit_Termmeta::PAGE_NAME] = $MNCF_Page_Edit_Termmeta->add_submenu_page();
	}
	
    $pages['mncf-um'] = array(
		'slug'				=> 'mncf-um',
        'menu_title'		=> __( 'User Fields', 'mncf' ),
        'page_title'		=> __( 'User Fields', 'mncf' ),
        'callback'			=> 'mncf_usermeta_summary',
        'capability_filter' => 'mncf_uf_view',
        'capability'		=> MNCF_USER_META_FIELD_VIEW,
    );
	$pages['mncf-um']['capability'] = mncf_admin_calculate_menu_page_capability( $pages['mncf-um'] );
	$pages['mncf-um']['load_hook'] = mncf_admin_calculate_menu_page_load_hook( $pages['mncf-um'] );
	$pages['mncf-um']['contextual_help_legacy'] = mncf_admin_help( 'mncf-um' );
	$pages['mncf-um']['contextual_help_hook'] = 'mncf_admin_help_add_tabs_load_hook';
	
	if ( $current_page == 'mncf-edit-usermeta' ) {
		$pages['mncf-edit-usermeta'] = array(
			'slug'				=> 'mncf-edit-usermeta',
			'menu_title'		=> isset( $_GET['group_id'] ) ? __( 'Edit User Field Group', 'mncf' ) : __( 'Add New User Field Group', 'mncf' ),
			'page_title'		=> isset( $_GET['group_id'] ) ? __( 'Edit User Field Group', 'mncf' ) : __( 'Add New User Field Group', 'mncf' ),
			'callback'			=> 'mncf_admin_menu_edit_user_fields',
			'capability'		=> MNCF_USER_META_FIELD_EDIT,
		);
		$pages['mncf-edit-usermeta']['capability'] = mncf_admin_calculate_menu_page_capability( $pages['mncf-edit-usermeta'] );
		$pages['mncf-edit-usermeta']['load_hook'] = mncf_admin_calculate_menu_page_load_hook( $pages['mncf-edit-usermeta'] );
		$pages['mncf-edit-usermeta']['contextual_help_legacy'] = mncf_admin_help( 'mncf-edit-usermeta' );
		$pages['mncf-edit-usermeta']['contextual_help_hook'] = 'mncf_admin_help_add_tabs_load_hook';
	}
	
	if ( $current_page == 'mncf-view-usermeta' ) {
		$pages['mncf-view-usermeta'] = array(
			'slug'				=> 'mncf-view-usermeta',
			'menu_title'		=> __( 'View User Field Group', 'mncf' ),
			'page_title'		=> __( 'View User Field Group', 'mncf' ),
			'callback'			=> 'mncf_admin_menu_edit_user_fields',
			'capability'		=> MNCF_USER_META_FIELD_VIEW,
		);
		$pages['mncf-view-usermeta']['capability'] = mncf_admin_calculate_menu_page_capability( $pages['mncf-view-usermeta'] );
		$pages['mncf-view-usermeta']['load_hook'] = mncf_admin_calculate_menu_page_load_hook( $pages['mncf-view-usermeta'] );
		$pages['mncf-view-usermeta']['contextual_help_legacy'] = mncf_admin_help( 'mncf-view-usermeta' );
		$pages['mncf-view-usermeta']['contextual_help_hook'] = 'mncf_admin_help_add_tabs_load_hook';
	}
	

	if (
        (class_exists( 'Acf') && !class_exists('acf_pro'))
        || defined( 'CPT_VERSION' ) 
    ) {
		$pages['mncf-migration'] = array(
			'slug'				=> 'mncf-migration',
			'menu_title'		=> __( 'Types Migration', 'mncf' ),
			'page_title'		=> __( 'Types Migration', 'mncf' ),
			'callback'			=> 'mncf_admin_menu_migration',
			'capability'		=> 'manage_options',
		);
		$pages['mncf-migration']['capability'] = mncf_admin_calculate_menu_page_capability( $pages['mncf-migration'] );
		$pages['mncf-migration']['load_hook'] = mncf_admin_calculate_menu_page_load_hook( $pages['mncf-migration'] );
		$pages['mncf-migration']['contextual_help_legacy'] = mncf_admin_help( 'mncf-migration' );
		$pages['mncf-migration']['contextual_help_hook'] = 'mncf_admin_help_add_tabs_load_hook';
    }
	
	if ( 'installer' == $current_page ) {
		// @todo Having a page with a slug "installer" is a direct path to a third-party plugin conflict. Just saying. Not to mention the callback funciton "installer_content", for god's sake
		$pages['installer'] = array(
			'slug'				=> 'installer',
			'menu_title'		=> __( 'Installer', 'mncf' ),
			'page_title'		=> __( 'Installer', 'mncf' ),
			'callback'			=> 'installer_content',
		);
	}

	/**
	* This used to load the Custom Content Access teaser - code has not been removed
	*
	* This also usd to hook the Installer page, added above
	*
	* @todo this is to be deletd IMHO
	*/
    //do_action( 'mncf_menu_plus' );
	
	return $pages;
}

/**
 * Menu page hook.
 */
function mncf_admin_menu_debug_information()
{
    require_once MNCF_EMBEDDED_TOOLSET_ABSPATH .'/toolset-common/debug/debug-information.php';
}


/**
 * Menu page hook.
 */
function mncf_usermeta_summary_hook()
{
    do_action( 'mncf_admin_page_init' );
    mncf_admin_load_collapsible();
    mncf_admin_page_add_options('uf',  __( 'User Fields', 'mncf' ));
}

/**
 * Menu page hook.
 */
function mncf_admin_menu_summary_hook()
{
    do_action( 'mncf_admin_page_init' );
    mncf_admin_load_collapsible();
    mncf_admin_page_add_options('cf',  __( 'Post Fields', 'mncf' ));
}

/**
 * Menu page display.
 */
function mncf_admin_menu_summary()
{
    mncf_add_admin_header( __( 'Post Field Groups', 'mncf' ), array('page'=>'mncf-edit'));
    require_once MNCF_INC_ABSPATH . '/fields.php';
    require_once MNCF_INC_ABSPATH . '/fields-list.php';
    $to_display = mncf_admin_fields_get_fields();
    if ( !empty( $to_display ) ) {
        add_action( 'mncf_groups_list_table_after', 'mncf_admin_promotional_text' );
    }
    mncf_admin_fields_list();
    mncf_add_admin_footer();
}


function mncf_admin_enqueue_group_edit_page_assets() {
	do_action( 'mncf_admin_page_init' );

	/*
	 * Enqueue scripts
	 */
	// Group filter
	mn_enqueue_script( 'mncf-filter-js',
		MNCF_EMBEDDED_RES_RELPATH
		. '/js/custom-fields-form-filter.js', array('jquery'), MNCF_VERSION );
	// Form
	mn_enqueue_script( 'mncf-form-validation',
		MNCF_EMBEDDED_RES_RELPATH . '/js/'
		. 'jquery-form-validation/jquery.validate.min.js', array('jquery'),
		MNCF_VERSION );
	mn_enqueue_script( 'mncf-form-validation-additional',
		MNCF_EMBEDDED_RES_RELPATH . '/js/'
		. 'jquery-form-validation/additional-methods.min.js',
		array('jquery'), MNCF_VERSION );
	// Scroll
	mn_enqueue_script( 'mncf-scrollbar',
		MNCF_EMBEDDED_TOOLSET_RELPATH . '/toolset-common/visual-editor/res/js/scrollbar.js',
		array('jquery') );
	mn_enqueue_script( 'mncf-mousewheel',
		MNCF_EMBEDDED_TOOLSET_RELPATH . '/toolset-common/visual-editor/res/js/mousewheel.js',
		array('mncf-scrollbar') );
	// MAIN
	mn_enqueue_script(
		'mncf-fields-form',
		MNCF_EMBEDDED_RES_RELPATH.'/js/fields-form.js',
		array( 'mncf-js' ),
		MNCF_VERSION
	);
	mn_enqueue_script(
		'mncf-admin-fields-form',
		MNCF_RES_RELPATH.'/js/fields-form.js',
		array(),
		MNCF_VERSION
	);

	/*
	 * Enqueue styles
	 */
	mn_enqueue_style( 'mncf-scroll',
		MNCF_EMBEDDED_TOOLSET_RELPATH . '/toolset-common/visual-editor/res/css/scroll.css' );

	//Css editor
	mn_enqueue_script( 'mncf-form-codemirror' ,
		MNCF_RELPATH . '/resources/js/codemirror234/lib/codemirror.js', array('mncf-js'));
	mn_enqueue_script( 'mncf-form-codemirror-css-editor' ,
		MNCF_RELPATH . '/resources/js/codemirror234/mode/css/css.js', array('mncf-js'));
	mn_enqueue_script( 'mncf-form-codemirror-html-editor' ,
		MNCF_RELPATH . '/resources/js/codemirror234/mode/xml/xml.js', array('mncf-js'));
	mn_enqueue_script( 'mncf-form-codemirror-html-editor2' ,
		MNCF_RELPATH . '/resources/js/codemirror234/mode/htmlmixed/htmlmixed.js', array('mncf-js'));
	mn_enqueue_script( 'mncf-form-codemirror-editor-resize' ,
		MNCF_RELPATH . '/resources/js/jquery_ui/jquery.ui.resizable.min.js', array('mncf-js'));

	mn_enqueue_style( 'mncf-css-editor',
		MNCF_RELPATH . '/resources/js/codemirror234/lib/codemirror.css' );
	//mn_enqueue_style( 'mncf-css-editor-resize',
	//        MNCF_RELPATH . '/resources/js/jquery_ui/jquery.ui.theme.min.css' );
	mn_enqueue_style( 'mncf-usermeta',
		MNCF_EMBEDDED_RES_RELPATH . '/css/usermeta.css' );

	mn_enqueue_style( 'font-awesome' );

	add_action( 'admin_footer', 'mncf_admin_fields_form_js_validation' );

}


/**
 * Menu page hook.
 */
function mncf_admin_menu_edit_fields_hook()
{
	mncf_admin_enqueue_group_edit_page_assets();

    require_once MNCF_INC_ABSPATH . '/fields.php';
    require_once MNCF_INC_ABSPATH . '/fields-form.php';
//    $form = mncf_admin_fields_form();
    //require_once MNCF_INC_ABSPATH.'/classes/class.types.admin.edit.custom.fields.group.php';
    $mncf_admin = new Types_Admin_Edit_Custom_Fields_Group();
    $mncf_admin->init_admin();
    $form = $mncf_admin->form();
    mncf_form( 'mncf_form_fields', $form );
}

/**
 * Menu page display.
 */
function mncf_admin_menu_edit_fields()
{
    $add_new = false;
    $post_type = current_filter();
    $title = __('View Post Field Group', 'mncf');
    if ( isset( $_GET['group_id'] ) ) {
        if ( MNCF_Roles::user_can_edit('custom-field', array('id' => (int) $_GET['group_id']))) {
            $title = __( 'Edit Post Field Group', 'mncf' );
            $add_new = array(
                'page' => 'mncf-edit',
            );
        }
    } else if ( MNCF_Roles::user_can_create('custom-field')) {
        $title = __( 'Add New Post Field Group', 'mncf' );
    }
    mncf_add_admin_header( $title, $add_new );
    mncf_mnml_warning();
    $form = mncf_form( 'mncf_form_fields' );
    echo '<form method="post" action="" class="mncf-fields-form mncf-form-validate js-types-show-modal">';
    mncf_admin_screen($post_type, $form->renderForm());
    echo '</form>';
    mncf_add_admin_footer();
}

function mncf_admin_page_add_options( $name, $label)
{
    $option = 'per_page';
    $args = array(
        'label' => $label,
        'default' => 10,
        'option' => sprintf('mncf_%s_%s', $name, $option),
    );
    add_screen_option( $option, $args );
}

function mncf_admin_menu_summary_cpt_ctt_hook()
{
    do_action( 'mncf_admin_page_init' );
    mncf_admin_load_collapsible();
    require_once MNCF_INC_ABSPATH . '/custom-types.php';
    require_once MNCF_INC_ABSPATH . '/custom-taxonomies.php';
    require_once MNCF_INC_ABSPATH . '/custom-types-taxonomies-list.php';
}

/**
 * Menu page hook.
 */
function mncf_admin_menu_summary_cpt_hook()
{
    mncf_admin_menu_summary_cpt_ctt_hook();
    mncf_admin_page_add_options('cpt',  __( 'Post Types', 'mncf' ));
}

/**
 * Menu page display.
 */
function mncf_admin_menu_summary_cpt()
{
    mncf_add_admin_header(
        __( 'Post Types', 'mncf' ),
        array('page'=>'mncf-edit-type'),
        __('Add New', 'mncf')
    );
    $to_display_posts = get_option( MNCF_OPTION_NAME_CUSTOM_TYPES, array() );
    $to_display_tax = get_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
    if ( !empty( $to_display_posts ) || !empty( $to_display_tax ) ) {
        add_action( 'mncf_types_tax_list_table_after', 'mncf_admin_promotional_text' );
    }
    mncf_admin_custom_post_types_list();
    mncf_add_admin_footer();
}

/**
 * Menu page hook.
 */
function mncf_admin_menu_summary_ctt_hook()
{
    mncf_admin_menu_summary_cpt_ctt_hook();
    mncf_admin_page_add_options('ctt',  __( 'Taxonomies', 'mncf' ));
}

/**
 * Menu page display.
 */
function mncf_admin_menu_summary_ctt()
{
    mncf_add_admin_header( __( 'Taxonomies', 'mncf' ), array('page' => 'mncf-edit-tax') );
    mncf_admin_custom_taxonomies_list();
    do_action('mncf_types_tax_list_table_after');
    mncf_add_admin_footer();
}

/**
 * Menu page hook.
 */
function mncf_admin_menu_edit_type_hook()
{
    require_once MNCF_INC_ABSPATH . '/fields.php';
    do_action( 'mncf_admin_page_init' );
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/custom-types.php';
    require_once MNCF_INC_ABSPATH . '/custom-types-form.php';
    require_once MNCF_INC_ABSPATH . '/post-relationship.php';
    mn_enqueue_script( 'mncf-custom-types-form',
            MNCF_RES_RELPATH . '/js/'
            . 'custom-types-form.js', array('jquery', 'jquery-ui-dialog', 'jquery-masonry'), MNCF_VERSION );
    mn_enqueue_script( 'mncf-form-validation',
            MNCF_RES_RELPATH . '/js/'
            . 'jquery-form-validation/jquery.validate.min.js', array('jquery'),
            MNCF_VERSION );
    mn_enqueue_script( 'mncf-form-validation-additional',
            MNCF_RES_RELPATH . '/js/'
            . 'jquery-form-validation/additional-methods.min.js',
            array('jquery'), MNCF_VERSION );
    mn_enqueue_style('mn-jquery-ui-dialog');
    add_action( 'admin_footer', 'mncf_admin_types_form_js_validation' );
    mncf_post_relationship_init();

	// New page controller script.
	$asset_manager = Types_Asset_Manager::get_instance();
	$asset_manager->enqueue_scripts( Types_Asset_Manager::SCRIPT_PAGE_EDIT_POST_TYPE );

    /**
     * add form
     */
    //    $form = mncf_admin_custom_types_form();
    require_once MNCF_INC_ABSPATH.'/classes/class.types.admin.edit.post.type.php';
    $mncf_admin = new Types_Admin_Edit_Post_Type();
    $mncf_admin->init_admin();
    $form = $mncf_admin->form();
    mncf_form( 'mncf_form_types', $form );
}

/**
 * Menu page display.
 */
function mncf_admin_menu_edit_type()
{
    $post_type = current_filter();
    $title = __('View Post Type', 'mncf');
    if ( MNCF_Roles::user_can_edit('custom-post-type', array()) ) {
        if ( isset( $_GET['mncf-post-type'] ) ) {
            $title = __( 'Edit Post Type', 'mncf' );
            /**
             * add new CPT link
             */
            $title .= sprintf(
                '<a href="%s" class="add-new-h2">%s</a>',
                esc_url(add_query_arg( 'page', 'mncf-edit-type', admin_url('admin.php'))),
                __('Add New', 'mncf')
            );
        } else {
            $title = __( 'Add New Post Type', 'mncf' );
        }
    }
    mncf_add_admin_header( $title );
    mncf_mnml_warning();
    $form = mncf_form( 'mncf_form_types' );
    echo '<form method="post" action="" class="mncf-types-form mncf-form-validate js-types-do-not-show-modal">';
    mncf_admin_screen($post_type, $form->renderForm());
    echo '</form>';
    mncf_add_admin_footer();
}

/**
 * Menu page hook.
 */
function mncf_admin_menu_edit_tax_hook()
{
    do_action( 'mncf_admin_page_init' );
    mn_enqueue_script( 'mncf-form-validation',
            MNCF_RES_RELPATH . '/js/'
            . 'jquery-form-validation/jquery.validate.min.js', array('jquery'),
            MNCF_VERSION );
    mn_enqueue_script( 'mncf-form-validation-additional',
            MNCF_RES_RELPATH . '/js/'
            . 'jquery-form-validation/additional-methods.min.js',
            array('jquery'), MNCF_VERSION );
    mn_enqueue_script( 'mncf-taxonomy-form',
        MNCF_RES_RELPATH . '/js/'
        . 'taxonomy-form.js', array( 'jquery' ), MNCF_VERSION );

	// New page controller script.
	$asset_manager = Types_Asset_Manager::get_instance();
	$asset_manager->enqueue_scripts( Types_Asset_Manager::SCRIPT_PAGE_EDIT_TAXONOMY );

    add_action( 'admin_footer', 'mncf_admin_tax_form_js_validation' );
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/custom-taxonomies.php';
    require_once MNCF_INC_ABSPATH . '/custom-taxonomies-form.php';
//    $form = mncf_admin_custom_taxonomies_form();
    require_once MNCF_INC_ABSPATH.'/classes/class.types.admin.edit.taxonomy.php';
    $mncf_admin = new Types_Admin_Edit_Taxonomy();
    $mncf_admin->init_admin();
    $form = $mncf_admin->form();
    mncf_form( 'mncf_form_tax', $form );
}

/**
 * Menu page display.
 */
function mncf_admin_menu_edit_tax()
{
    $post_type = current_filter();
    $title = __( 'View Taxonomy', 'mncf' );
    $add_new = false;
    if ( MNCF_Roles::user_can_create('custom-taxonomy') ) {
        $title = __( 'Add New Taxonomy', 'mncf' );
        if ( isset( $_GET['mncf-tax'] ) ) {
            $title = __( 'Edit Taxonomy', 'mncf' );
            $add_new = array('page' => 'mncf-edit-tax' );
        }
    }
    mncf_add_admin_header( $title, $add_new);
    mncf_mnml_warning();
    $form = mncf_form( 'mncf_form_tax' );
    echo '<form method="post" action="" class="mncf-tax-form mncf-form-validate js-types-show-modal">';
    mncf_admin_screen($post_type, $form->renderForm());
    echo '</form>';
    mncf_add_admin_footer();
}

/**
* Export and Import, hooks and admin page tab
*
* This is screaming for a controller class...
*/

add_action( 'mn_loaded', 'mncf_admin_export_on_form_submit' );

function mncf_admin_export_on_form_submit() {
	require_once MNCF_INC_ABSPATH . '/fields.php';
    require_once MNCF_INC_ABSPATH . '/import-export.php';
    if ( 
		extension_loaded( 'simplexml' ) 
		&& isset( $_POST['types_export'] )
		&& isset( $_POST['types_export_mnnonce'] )
        && mn_verify_nonce( $_POST['types_export_mnnonce'], 'mncf_export' ) 
	) {
        mncf_admin_export_data();
        die();
    }
}

add_action( 'mn_loaded', 'mncf_admin_import_on_form_submit' );

function mncf_admin_import_on_form_submit() {
	require_once MNCF_INC_ABSPATH . '/fields.php';
    require_once MNCF_INC_ABSPATH . '/import-export.php';
	global $mncf_import_messages;
	$mncf_import_messages = array();
    if ( 
		extension_loaded( 'simplexml' ) 
		&& isset( $_POST['types-import-final'] )
		&& isset( $_POST['types_import_mnnonce'] ) 
		&& mn_verify_nonce( $_POST['types_import_mnnonce'], 'mncf_import' )
	) {
        $mncf_import_messages = mncf_admin_import_final_data();
    }
}

add_action( 'admin_notices', 'mncf_admin_import_admin_notices' );

function mncf_admin_import_admin_notices() {
	global $mncf_import_messages;
	if ( count( $mncf_import_messages ) > 0 ) {
		$success_messages = mn_list_filter( $mncf_import_messages, array( 'type' => 'success' ) );
		$error_messages = mn_list_filter( $mncf_import_messages, array( 'type' => 'error' ) );
		if ( count( $success_messages ) > 0 ) {
		?>
		<div class="notice message updated is-dismissible">
				<h3><?php _e( 'Types import summary', 'mncf' ); ?></h3>
				<ul class="toolset-taglike-list">
				<?php
				foreach ( $success_messages as $message ) {
					?>
					<li><?php echo $message['content']; ?></li>
					<?php
				}
				?>
				</ul>
			</div>
		<?php
		}
		if ( count( $error_messages ) > 0 ) {
		?>
		<div class="notice message error">
				<h3><?php _e( 'Types import errors', 'mncf' ); ?></h3>
				<ul>
				<?php
				foreach ( $error_messages as $message ) {
					?>
					<li><?php echo $message['content']; ?></li>
					<?php
				}
				?>
				</ul>
			</div>
		<?php
		}
		
	}
}

add_filter( 'toolset_filter_register_export_import_section', 'mncf_toolset_register_export_import_sections' );

function mncf_toolset_register_export_import_sections( $sections ) {
	$sections['types'] = array(
		'slug'		=> 'types',
		'title'		=> __( 'Types', 'mncf' ),
		'icon'		=> '<i class="icon-types-logo ont-icon-16"></i>',
		'items'		=> array(
			'export'	=> array(
							'title'		=> __( 'Export Types data', 'mncf' ),
							'callback'	=> 'mncf_render_export_form',
						),
			'import'	=> array(
							'title'		=> __( 'Import Types data', 'mncf' ),
							'callback'	=> 'mncf_render_import_form',
						)
		)
	);
	return $sections;
}

add_action( 'toolset_enqueue_scripts', 'mncf_toolset_shared_pages_enqueue_script' );

function mncf_toolset_shared_pages_enqueue_script( $current_page ) {
	if ( $current_page == 'toolset-export-import' ) {
		mn_enqueue_script( 'types-export-import' );
	}
	if ( $current_page == 'toolset-settings' ) {
		mn_enqueue_script( 'types-settings' );
	}
}

function mncf_render_export_form() {
    require_once MNCF_INC_ABSPATH . '/fields.php';
    require_once MNCF_INC_ABSPATH . '/import-export.php';
	echo '<form method="post" action="' . admin_url('edit.php') . '" class="mncf-import-export-form '
    . 'mncf-form-validate" enctype="multipart/form-data">';
    echo mncf_admin_export_form();
    echo '</form>';
}

function mncf_render_import_form() {
    require_once MNCF_INC_ABSPATH . '/fields.php';
    require_once MNCF_INC_ABSPATH . '/import-export.php';
	echo '<form method="post" action="' . admin_url('admin.php') . '?page=toolset-export-import&tab=types" class="mncf-import-export-form '
    . 'mncf-form-validate" enctype="multipart/form-data">';
	if ( 
		isset( $_POST['types_import_mnnonce'] ) 
		&& mn_verify_nonce( $_POST['types_import_mnnonce'], 'mncf_import' ) 
		&& isset( $_POST['types-import-step'] )
	) {
		echo mncf_admin_import_confirmation_form();
	} else {
		echo mncf_admin_import_form();
	}
    echo '</form>';
}



/**
 * Menu page hook.
 */
function mncf_admin_menu_migration_hook()
{
    do_action( 'mncf_admin_page_init' );
    require_once MNCF_INC_ABSPATH . '/fields.php';
    require_once MNCF_INC_ABSPATH . '/custom-types.php';
    require_once MNCF_INC_ABSPATH . '/custom-taxonomies.php';
    require_once MNCF_INC_ABSPATH . '/migration.php';
    $form = mncf_admin_migration_form();
    mncf_form( 'mncf_form_migration', $form );
}

/**
 * Menu page display.
 */
function mncf_admin_menu_migration()
{
    mncf_add_admin_header( __( 'Migration', 'mncf' ) );
    echo '<form method="post" action="" id="mncf-migration-form" class="mncf-migration-form '
    . 'mncf-form-validate" enctype="multipart/form-data">';
    $form = mncf_form( 'mncf_form_migration' );
    echo $form->renderForm();
    echo '</form>';
    mncf_add_admin_footer();
}

add_filter( 'toolset_filter_toolset_register_settings_section', 'mncf_register_settings_custom_content_section', 20 );

function mncf_register_settings_custom_content_section( $sections ) {
	$sections['custom-content'] = array(
		'slug'	=> 'custom-content',
		'title'	=> __( 'Custom Content', 'mncf' )
	);
	return $sections;
}

add_filter( 'toolset_filter_toolset_register_settings_custom-content_section',	'mncf_admin_settings_for_images' );

function mncf_admin_settings_for_images( $sections ) {
	$settings = mncf_get_settings();
	$section_content = '';
	ob_start();
	$form['images'] = array(
		'#title' => '<h3>' . __('Images resizing', 'mncf') . '</h3>',
        '#id' => 'add_resized_images_to_library',
        '#name' => 'mncf_add_resized_images_to_library',
        '#type' => 'checkbox',
        '#label' => __('Add resized images to the media library', 'mncf'),
        '#description' => __('Types will automatically add the resized images as attachments to the media library.', 'mncf'),
        '#inline' => true,
        '#default_value' => !empty($settings['add_resized_images_to_library']),
        '#pattern' => '<TITLE><ELEMENT><LABEL><DESCRIPTION>',
    );
    $form['images_remote'] = array(
        '#id' => 'images_remote',
        '#name' => 'mncf_images_remote',
        '#type' => 'checkbox',
        '#label' => __('Allow resizing of remote images', 'mncf'),
        '#description' => __('Types will try to scale remote images.', 'mncf'),
        '#inline' => true,
        '#default_value' => !empty($settings['images_remote']),
        '#pattern' => '<ELEMENT><LABEL><DESCRIPTION>',
    );
    $form['images_remote_clear'] = array(
		'#title' => '<h3>' . __('Images caching', 'mncf') . '</h3>',
        '#id' => 'images_remote_cache_time',
        '#name' => 'mncf_images_remote_cache_time',
        '#type' => 'select',
        '#pattern' => '<TITLE>' . __('Invalidate cached images that are more than <ELEMENT> hours old.', 'mncf'),
        '#options' => array(
            __('Never', 'mncf') => '0',
            '24' => '24',
            '36' => '36',
            '48' => '48',
            '72' => '72',
        ),
        '#inline' => false,
        '#default_value' => intval($settings['images_remote_cache_time']),
    );
    $form['clear_images_cache'] = array(
        '#type' => 'button',
        '#name' => 'clear-cache-images',
        '#id' => 'clear-cache-images',
        '#attributes' => array('id' => 'clear-cache-images','class' => 'button-secondary js-mncf-settings-clear-cache-images'),
        '#value' => __('Clear Cached Images', 'mncf'),
        '#inline' => false,
        '#pattern' => '<div class="js-mncf-settings-clear-cache-images-container"><ELEMENT>',
    );
    $form['clear_images_cache_outdated'] = array(
        '#id' => 'clear-cache-images-outdated',
        '#type' => 'button',
        '#name' => 'clear-cache-images-outdated',
        '#attributes' => array('id' => 'clear-cache-images-outdated','class' => 'button-secondary js-mncf-settings-clear-cache-images-outdated'),
        '#value' => __('Clear Outdated Cached Images', 'mncf'),
        '#inline' => false,
        '#pattern' => ' <ELEMENT></div>',
    );
	$section_content = mncf_form_simple( $form );
		
	$sections['mncf-images-settings'] = array(
		'slug'		=> 'mncf-image-settings',
		'title'		=> __( 'Images', 'mncf' ),
		'content'	=> $section_content
	);
	return $sections;
}

add_action( 'mn_ajax_mncf_settings_clear_cache_images', 'mncf_settings_clear_cache_images' );

function mncf_settings_clear_cache_images() {
	if ( ! current_user_can( 'manage_options' ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'mncf' )
		);
		mn_send_json_error( $data );
	}
	if ( 
		! isset( $_POST["mnnonce"] )
		|| ! mn_verify_nonce( $_POST["mnnonce"], 'mncf_settings_nonce' ) 
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'mncf' )
		);
		mn_send_json_error( $data );
	}
	require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields/image.php';
	$cache_dir = mncf_fields_image_get_cache_directory( true );
	if ( is_mn_error( $cache_dir ) ) {
		$data = array(
			'type' => 'error',
			'message' => $cache_dir->get_error_message()
		);
		mn_send_json_error( $data );
	}
	$posted_settings = isset( $_POST['settings'] ) ? sanitize_text_field( $_POST['settings'] ) : '';
	if ( ! in_array( $posted_settings, array( 'all', 'outdated' ) ) ) {
		$data = array(
			'type' => 'error',
			'message' => __( 'Missing data', 'mncf' )
		);
		mn_send_json_error( $data );
	}
	switch ( $posted_settings ) {
		case 'all':
			mncf_fields_image_clear_cache( $cache_dir, 'all' );
			break;
		case 'outdated':
			mncf_fields_image_clear_cache( $cache_dir );
			break;
	}
	mn_send_json_success();
}

add_action( 'mn_ajax_mncf_settings_save_image_settings', 'mncf_settings_save_image_settings' );

function mncf_settings_save_image_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'mncf' )
		);
		mn_send_json_error( $data );
	}
	if ( 
		! isset( $_POST["mnnonce"] )
		|| ! mn_verify_nonce( $_POST["mnnonce"], 'mncf_settings_nonce' ) 
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'mncf' )
		);
		mn_send_json_error( $data );
	}
	$settings = mncf_get_settings();
	$keys_to_check = array( 
		'add_resized_images_to_library'	=> 'esc_html', 
		'images_remote'					=> 'intval', 
		'images_remote_cache_time'		=> 'intval' 
	);
	$posted_settings = isset( $_POST['settings'] ) ? mn_parse_args( $_POST['settings'] ) : array();
	foreach ( $keys_to_check as $key => $validation ) {
		if ( isset( $posted_settings['mncf_' . $key] ) ) {
			$settings[$key] = call_user_func( $validation, $posted_settings['mncf_' . $key] );
		} else {
			$settings[$key] = 0;
		}
	}
	update_option('mncf_settings', $settings);
	mn_send_json_success();
}

add_filter( 'toolset_filter_toolset_register_settings_custom-content_section',	'mncf_admin_settings_for_help_box', 20 );

function mncf_admin_settings_for_help_box( $sections ) {
	$settings = mncf_get_settings();
	$section_content = '';
	$form['help-box'] = array(
		'#id' => 'help_box',
		'#name' => 'mncf_help_box',
		'#type' => 'radios',
		'#options' => array(
			'all' => array(
				'#value' => 'all',
				'#title' => __("Show the help box on all custom post editing screens and on all Types create/edit pages", 'mncf')
			),
			'by_types' => array(
				'#value' => 'by_types',
				'#title' => __("Show the help box only on post types that were created by Types and on all Types create/edit pages", 'mncf')
			),
			'no' => array(
				'#value' => 'no',
				'#title' => __("Don't show the help box anywhere", 'mncf')
			),
		),
		'#inline' => true,
		'#default_value' => $settings['help_box'],
		'#pattern' => '<ELEMENT><DESCRIPTION>',
	);
	$section_content = mncf_form_simple( $form );
		
	$sections['mncf-help-box-settings'] = array(
		'slug'		=> 'mncf-help-box-settings',
		'title'		=> __( 'Help box', 'mncf' ),
		'content'	=> $section_content
	);
	return $sections;
}

add_action( 'mn_ajax_mncf_settings_save_help_box_settings', 'mncf_settings_save_help_box_settings' );

function mncf_settings_save_help_box_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'mncf' )
		);
		mn_send_json_error( $data );
	}
	if ( 
		! isset( $_POST["mnnonce"] )
		|| ! mn_verify_nonce( $_POST["mnnonce"], 'mncf_settings_nonce' ) 
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'mncf' )
		);
		mn_send_json_error( $data );
	}
	$settings = mncf_get_settings();
	$keys_to_check = array( 
		'help_box'	=> 'esc_html'
	);
	$posted_settings = isset( $_POST['settings'] ) ? mn_parse_args( $_POST['settings'] ) : array();
	foreach ( $keys_to_check as $key => $validation ) {
		if ( isset( $posted_settings['mncf_' . $key] ) ) {
			$settings[$key] = call_user_func( $validation, $posted_settings['mncf_' . $key] );
		} else {
			$settings[$key] = 0;
		}
	}
	update_option('mncf_settings', $settings);
	mn_send_json_success();
}

add_filter( 'toolset_filter_toolset_register_settings_custom-content_section',	'mncf_admin_settings_for_custom_field_metabox', 30 );

function mncf_admin_settings_for_custom_field_metabox( $sections ) {
	$settings = mncf_get_settings();
	$section_content = '';
	$form['hide_standard_custom_fields_metabox'] = array(
		'#id' => 'hide_standard_custom_fields_metabox',
		'#name' => 'mncf_hide_standard_custom_fields_metabox',
		'#type' => 'radios',
		'#options' => array(
			'all' => array(
				'#value' => 'show',
				'#title' => __('Show standard Mtaandao Custom Field Metabox', 'mncf')
			),
			'by_types' => array(
				'#value' => 'hide',
				'#title' => __('Hide standard Mtaandao Custom Field Metabox', 'mncf')
			),
		),
		'#inline' => true,
		'#default_value' => preg_match('/^(show|hide)$/', $settings['hide_standard_custom_fields_metabox'])? $settings['hide_standard_custom_fields_metabox']:'show',
		'#pattern' => '<ELEMENT><DESCRIPTION>',
	);
	$section_content = mncf_form_simple( $form );
		
	$sections['mncf-custom-field-metabox-settings'] = array(
		'slug'		=> 'mncf-custom-field-metabox-settings',
		'title'		=> __( 'Custom field metabox', 'mncf' ),
		'content'	=> $section_content
	);
	return $sections;
}

add_action( 'mn_ajax_mncf_settings_save_custom_field_metabox_settings', 'mncf_settings_save_custom_field_metabox_settings' );

function mncf_settings_save_custom_field_metabox_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'mncf' )
		);
		mn_send_json_error( $data );
	}
	if ( 
		! isset( $_POST["mnnonce"] )
		|| ! mn_verify_nonce( $_POST["mnnonce"], 'mncf_settings_nonce' ) 
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'mncf' )
		);
		mn_send_json_error( $data );
	}
	$settings = mncf_get_settings();
	$posted_settings = isset( $_POST['settings'] ) ? mn_parse_args( $_POST['settings'] ) : array();
	if ( preg_match('/^(show|hide)$/', $posted_settings['mncf_hide_standard_custom_fields_metabox'] ) ) {
		$settings['hide_standard_custom_fields_metabox'] = $posted_settings['mncf_hide_standard_custom_fields_metabox'];
	} else {
		$settings['hide_standard_custom_fields_metabox'] = 'show';
	}
	update_option('mncf_settings', $settings);
	mn_send_json_success();
}

add_filter( 'toolset_filter_toolset_register_settings_custom-content_section',	'mncf_admin_settings_for_unfiltered_html', 40 );

function mncf_admin_settings_for_unfiltered_html( $sections ) {
	$settings = mncf_get_settings();
	$section_content = '';
	$form['postmeta-unfiltered-html'] = array(
        '#id' => 'postmeta_unfiltered_html',
        '#name' => 'mncf_postmeta_unfiltered_html',
        '#type' => 'radios',
        '#title' => __('Custom fields - unfiltered HTML', 'mncf'),
        '#options' => array(
            'on' => array(
                '#value' => 'on',
                '#title' => __('Allow saving unfiltered HTML in Types custom fields for users with higher roles', 'mncf'),
            ),
            'off' => array(
                '#value' => 'off',
                '#title' => __('Disallow saving unfiltered HTML in Types custom fields for all users', 'mncf'),
            ),
        ),
        '#inline' => false,
        '#default_value' => $settings['postmeta_unfiltered_html'],
        '#pattern' => '<TITLE><ELEMENT><DESCRIPTION>',
    );
    $form['usermeta-unfiltered-html'] = array(
        '#id' => 'usermeta_unfiltered_html',
        '#name' => 'mncf_usermeta_unfiltered_html',
        '#type' => 'radios',
        '#title' => __('Usermeta fields - unfiltered HTML', 'mncf'),
        '#options' => array(
            'on' => array(
                '#value' => 'on',
                '#title' => __("Allow saving unfiltered HTML in Types usermeta fields for users with higher roles", 'mncf'),
            ),
            'off' => array(
                '#value' => 'off',
                '#title' => __("Disallow saving unfiltered HTML in Types usermeta fields for all users", 'mncf')
            ),
        ),
        '#inline' => false,
        '#default_value' => $settings['usermeta_unfiltered_html'],
        '#pattern' => '<TITLE><ELEMENT><DESCRIPTION>',
    );
	$section_content = mncf_form_simple( $form );
		
	$sections['mncf-unfiltered-html-settings'] = array(
		'slug'		=> 'mncf-unfiltered-html-settings',
		'title'		=> __( 'Saving unfiltered HTML for users with higher roles', 'mncf' ),
		'content'	=> $section_content
	);
	return $sections;
}

add_action( 'mn_ajax_mncf_settings_save_unfiltered_html_settings', 'mncf_settings_save_unfiltered_html_settings' );

function mncf_settings_save_unfiltered_html_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'mncf' )
		);
		mn_send_json_error( $data );
	}
	if ( 
		! isset( $_POST["mnnonce"] )
		|| ! mn_verify_nonce( $_POST["mnnonce"], 'mncf_settings_nonce' ) 
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'mncf' )
		);
		mn_send_json_error( $data );
	}
	$settings = mncf_get_settings();
	$keys_to_check = array( 
		'postmeta_unfiltered_html',
		'usermeta_unfiltered_html'
	);
	$posted_settings = isset( $_POST['settings'] ) ? mn_parse_args( $_POST['settings'] ) : array();
	foreach ( $keys_to_check as $key ) {
		if ( isset( $posted_settings['mncf_' . $key] ) ) {
			if ( preg_match( '/^(on|off)$/', $posted_settings['mncf_' . $key] ) ) {
				$settings[$key] = $posted_settings['mncf_' . $key];
			} else {
				$settings[$key] = 'off';
			}
		} else {
			$settings[$key] = 'off';
		}
	}
	update_option('mncf_settings', $settings);
	mn_send_json_success();
}

add_filter( 'toolset_filter_toolset_register_settings_section', 'mncf_register_settings_mnml_section', 70 );

function mncf_register_settings_mnml_section( $sections ) {
	$mnml_installed = apply_filters( 'mnml_setting', false, 'setup_complete' );
	if ( $mnml_installed ) {
		$sections['mnml'] = array(
			'slug'	=> 'mnml',
			'title'	=> __( 'MNML integration', 'mnv-views' )
		);
	}
	return $sections;
}

add_action( 'toolset_filter_toolset_register_settings_mnml_section', 'mncf_mnml_translation_options' );

function mncf_mnml_translation_options( $sections ) {
	$mnml_installed = apply_filters( 'mnml_setting', false, 'setup_complete' );
	if ( $mnml_installed ) {
		$settings = mncf_get_settings();
		$form['register_translations_on_import'] = array(
            '#id' => 'register_translations_on_import',
            '#name' => 'mncf_register_translations_on_import',
            '#type' => 'checkbox',
            '#label' => __("When importing, add texts to MNML's String Translation table", 'mncf'),
            '#inline' => true,
            '#default_value' => !empty($settings['register_translations_on_import']),
            '#pattern' => '<p><ELEMENT><LABEL><DESCRIPTION></p>',
        );
		
		$section_content = mncf_form_simple( $form );
			
		$sections['mnml-mncf'] = array(
			'slug'		=> 'mnml-mncf',
			'title'		=> __( 'Types and MNML integration', 'mncf' ),
			'content'	=> $section_content
		);
	}
	return $sections;
}

add_action( 'mn_ajax_mncf_settings_save_mnml_settings', 'mncf_settings_save_mnml_settings' );

function mncf_settings_save_mnml_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'mncf' )
		);
		mn_send_json_error( $data );
	}
	if ( 
		! isset( $_POST["mnnonce"] )
		|| ! mn_verify_nonce( $_POST["mnnonce"], 'mncf_settings_nonce' ) 
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'mncf' )
		);
		mn_send_json_error( $data );
	}
	$settings = mncf_get_settings();
	$keys_to_check = array( 
		'register_translations_on_import' => 'esc_html',
	);
	$posted_settings = isset( $_POST['settings'] ) ? mn_parse_args( $_POST['settings'] ) : array();
	foreach ( $keys_to_check as $key => $validation ) {
		if ( isset( $posted_settings['mncf_' . $key] ) ) {
			$settings[$key] = call_user_func( $validation, $posted_settings['mncf_' . $key] );
		} else {
			$settings[$key] = 0;
		}
	}
	update_option('mncf_settings', $settings);
	mn_send_json_success();
}

/**
 * Adds typical header on admin pages.
 *
 * @param string $title
 * @param string $icon_id Custom icon
 * @return string
 */
function mncf_add_admin_header($title, $add_new = false, $add_new_title = false)
{
    echo '<div class="wrap">';
    echo '<h1>', $title;
    if ( !$add_new_title ) {
        $add_new_title = __('Add New', 'mncf');
    }
    if ( is_array($add_new) && isset($add_new['page']) ) {
        $add_button = false;
        /**
         * check user can?
         */
        switch($add_new['page']) {
	        case 'mncf-edit-type':
		        $add_button = MNCF_Roles::user_can_create( 'custom-post-type' );
		        break;
	        case 'mncf-edit-tax':
		        $add_button = MNCF_Roles::user_can_create( 'custom-taxonomy' );
		        break;
	        case 'mncf-edit':
		        $add_button = MNCF_Roles::user_can_create( 'custom-field' );
		        break;
	        case 'mncf-edit-usermeta':
		        $add_button = MNCF_Roles::user_can_create( 'user-meta-field' );
		        break;
	        case MNCF_Page_Edit_Termmeta::PAGE_NAME:
		        $add_button = MNCF_Roles::user_can_create( 'term-field' );
		        break;
        }
        if ( $add_button ) {
            printf(
                ' <a href="%s" class="add-new-h2">%s</a>',
                esc_url(add_query_arg( $add_new, admin_url('admin.php'))),
                $add_new_title
            );
        }
    }
    echo '</h2>';
    $current_page = sanitize_text_field( $_GET['page'] );
    do_action( 'mncf_admin_header' );
    do_action( 'mncf_admin_header_' . $current_page );
}

/**
 * Adds footer on admin pages.
 *
 * <b>Strongly recomended</b> if mncf_add_admin_header() is called before.
 * Otherwise invalid HTML formatting will occur.
 */
function mncf_add_admin_footer()
{
    $current_page = sanitize_text_field( $_GET['page'] );
	do_action( 'mncf_admin_footer_' . $current_page );
    do_action( 'mncf_admin_footer' );
    echo '</div>';
}

/**
 * Returns HTML formatted 'widefat' table.
 *
 * @param type $ID
 * @param type $header
 * @param type $rows
 * @param type $empty_message
 */
function mncf_admin_widefat_table( $ID, $header, $rows = array(), $empty_message = 'No results' )
{
    if ( 'No results' == $empty_message ) {
        $empty_message = __('No results', 'mncf');
    }
    $head = '';
    $footer = '';
    foreach ( $header as $key => $value ) {
        $head .= '<th id="mncf-table-' . $key . '">' . $value . '</th>' . "\r\n";
        $footer .= '<th>' . $value . '</th>' . "\r\n";
    }
    echo '<table id="' . $ID . '" class="widefat" cellspacing="0">
            <thead>
                <tr>
                  ' . $head . '
                </tr>
            </thead>
            <tfoot>
                <tr>
                  ' . $footer . '
                </tr>
            </tfoot>
            <tbody>
              ';
    $row = '';
    if ( empty( $rows ) ) {
        echo '<tr><td colspan="' . count( $header ) . '">' . $empty_message
        . '</td></tr>';
    } else {
        $i = 0;
        foreach ( $rows as $row ) {
            $classes = array();
            if ( $i++%2 ) {
                $classes[] =  'alternate';
            }
            if ( isset($row['status']) && 'inactive' == $row['status'] ) {
                $classes[] = sprintf('status-%s', $row['status']);
            };
            printf('<tr class="%s">', implode(' ', $classes ));
            foreach ( $row as $column_name => $column_value ) {
                if ( preg_match( '/^(status|raw_name)$/', $column_name )) {
                    continue;
                }
                echo '<td class="mncf-table-column-' . $column_name . '">';
                echo $column_value;
                echo '</td>' . "\r\n";
            }
            echo '</tr>' . "\r\n";
        }
    }
    echo '
            </tbody>
          </table>' . "\r\n";
}

/**
 * Admin tabs.
 *
 * @param type $tabs
 * @param type $page
 * @param type $default
 * @param type $current
 * @return string
 */
function mncf_admin_tabs($tabs, $page, $default = '', $current = '')
{
    if ( empty( $current ) && isset( $_GET['tab'] ) ) {
        $current = sanitize_text_field( $_GET['tab'] );
    } else {
        $current = $default;
    }
    $output = '<h2 class="nav-tab-wrapper">';
    foreach ( $tabs as $tab => $name ) {
        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
        $output .= "<a class='nav-tab$class' href='?page=$page&tab=$tab'>$name</a>";
    }
    $output .= '</h2>';
    return $output;
}

/**
 * Saves open fieldsets.
 *
 * @param type $action
 * @param type $fieldset
 */
function mncf_admin_form_fieldset_save_toggle($action, $fieldset)
{
    $data = get_user_meta( get_current_user_id(), 'mncf-form-fieldsets-toggle',
            true );
    if ( $action == 'open' ) {
        $data[$fieldset] = 1;
    } elseif ( $action == 'close' ) {
        unset( $data[$fieldset] );
    }
    update_user_meta( get_current_user_id(), 'mncf-form-fieldsets-toggle', $data );
}

/**
 * Check if fieldset is saved as open.
 *
 * @param type $fieldset
 */
function mncf_admin_form_fieldset_is_collapsed($fieldset)
{
    $data = get_user_meta( get_current_user_id(), 'mncf-form-fieldsets-toggle',
            true );
    if ( empty( $data ) ) {
        return true;
    }
    return array_key_exists( $fieldset, $data ) ? false : true;
}

/**
 * Adds help on admin pages.
 *
 * @param type $contextual_help
 * @param type $screen_id
 * @param type $screen
 * @return type
 */
function mncf_admin_plugin_help($hook, $page)
{
    global $mn_version;
    $call = false;
    $contextual_help = '';
    $page = $page;
    if ( isset( $page ) && isset( $_GET['page'] ) && $_GET['page'] == $page ) {
        switch ( $page ) {
            case 'mncf-cf':
                $call = 'custom_fields';
                break;

            case 'mncf-cpt':
                $call = 'post_types_list';
                break;

            case 'mncf-ctt':
                $call = 'custom_taxonomies_list';
                break;

            case 'mncf-edit':
                $call = 'edit_group';
                break;

            case 'mncf-edit-type':
                $call = 'edit_type';
                break;

            case 'mncf-edit-tax':
                $call = 'edit_tax';
                break;

            case 'mncf':
                $call = 'mncf';
                break;

            case 'mncf-um':
                $call = 'user_fields_list';
                break;

            case 'mncf-edit-usermeta':
                $call = 'user_fields_edit';
                break;
        }
    }
    if ( $call ) {
        require_once MNCF_ABSPATH . '/help.php';
        // MN 3.3 changes
        if ( version_compare( $mn_version, '3.2.1', '>' ) ) {
            mncf_admin_help_add_tabs($call, $hook, $contextual_help);
        } else {
            $contextual_help = mncf_admin_help( $call, $contextual_help );
            add_contextual_help( $hook, $contextual_help );
        }
    }
}

/**
 * Promo texts
 *
 * @todo Move!
 */
function mncf_admin_promotional_text()
{
    $promo_tabs = get_option( '_mncf_promo_tabs', false );
    // random selection every one hour
    if ( $promo_tabs ) {
        $time = time();
        $time_check = intval( $promo_tabs['time'] ) + 60 * 60;
        if ( $time > $time_check ) {
            $selected = mt_rand( 0, 3 );
            $promo_tabs['selected'] = $selected;
            $promo_tabs['time'] = $time;
            update_option( '_mncf_promo_tabs', $promo_tabs );
        } else {
            $selected = $promo_tabs['selected'];
        }
    } else {
        $promo_tabs = array();
        $selected = mt_rand( 0, 3 );
        $promo_tabs['selected'] = $selected;
        $promo_tabs['time'] = time();
        update_option( '_mncf_promo_tabs', $promo_tabs );
    }
}

/**
 * Collapsible scripts.
 */
function mncf_admin_load_collapsible()
{
    mn_enqueue_script( 'mncf-collapsible',
            MNCF_RES_RELPATH . '/js/collapsible.js', array('jquery'),
            MNCF_VERSION );
    mn_enqueue_style( 'mncf-collapsible',
            MNCF_RES_RELPATH . '/css/collapsible.css', array(), MNCF_VERSION );
    $option = get_option( 'mncf_toggle', array() );
    if ( !empty( $option ) ) {
        $setting = 'new Array("' . implode( '", "', array_keys( $option ) ) . '")';
        mncf_admin_add_js_settings( 'mncf_collapsed', $setting );
    }
}

/**
 * Various delete/deactivate content actions.
 *
 * @param type $type
 * @param type $arg
 * @param type $action
 */
function mncf_admin_deactivate_content($type, $arg, $action = 'delete')
{
    switch ( $type ) {
        case 'post_type':
            // Clean tax relations
            if ( $action == 'delete' ) {
                $custom = get_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
                foreach ( $custom as $post_type => $data ) {
                    if ( empty( $data['supports'] ) ) {
                        continue;
                    }
                    if ( array_key_exists( $arg, $data['supports'] ) ) {
                        unset( $custom[$post_type]['supports'][$arg] );
                        $custom[$post_type][TOOLSET_EDIT_LAST] = time();
                    }
                }
                update_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom );
            }
            break;

        case 'taxonomy':
            // Clean post relations
            if ( $action == 'delete' ) {
                $custom = get_option( MNCF_OPTION_NAME_CUSTOM_TYPES, array() );
                foreach ( $custom as $post_type => $data ) {
                    if ( empty( $data['taxonomies'] ) ) {
                        continue;
                    }
                    if ( array_key_exists( $arg, $data['taxonomies'] ) ) {
                        unset( $custom[$post_type]['taxonomies'][$arg] );
                        $custom[$post_type][TOOLSET_EDIT_LAST] = time();
                    }
                }
                update_option( MNCF_OPTION_NAME_CUSTOM_TYPES, $custom );
            }
            break;

        default:
            break;
    }
}

/**
 * Loads teasers.
 *
 * @param type $teasers
 */
function mncf_admin_load_teasers($teasers)
{
    foreach ( $teasers as $teaser ) {
        $file = MNCF_ABSPATH . '/plus/' . $teaser;
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
}

/**
 * Get temporary directory
 *
 * @return
 */

function mncf_get_temporary_directory()
{
    $dir = sys_get_temp_dir();
    if ( !empty( $dir ) && is_dir( $dir ) && is_writable( $dir ) ) {
        return $dir;
    }
    $dir = mn_upload_dir();
    $dir = $dir['basedir'];
    return $dir;
}

/**
 * add types configuration to debug
 */

function mncf_get_extra_debug_info($extra_debug)
{
    $extra_debug['types'] = mncf_get_settings();
    return $extra_debug;
}

add_filter( 'icl_get_extra_debug_info', 'mncf_get_extra_debug_info' );

/**
 * sort helper for tables
 */
function mncf_usort_reorder($a,$b)
{
    $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'title'; //If no sort, default to title
    $order = (!empty($_REQUEST['order'])) ? sanitize_text_field( $_REQUEST['order'] ) : 'asc'; //If no order, default to asc
    if ( ! in_array( $order, array( 'asc', 'desc' ) ) ) {
        $order = 'asc';
    }
    if ('title' == $orderby || !isset($a[$orderby])) {
        $orderby = 'slug';
    }
    /**
     * sort by slug if sort field is the same
     */
    if ( $a[$orderby] == $b[$orderby] ) {
        $orderby = 'slug';
    }
    $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
    return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
}

add_filter('set-screen-option', 'mncf_table_set_option', 10, 3);
function mncf_table_set_option($status, $option, $value)
{
      return $value;
}

function mncf_admin_screen( $post_type, $form_output = '')
{
?>
<div id="poststuff">
    <div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
<?php echo $form_output; ?>
        <div id="postbox-container-1" class="postbox-container">
            <?php do_meta_boxes($post_type, 'side', null); ?>
        </div>
        <div id="postbox-container-2" class="postbox-container">
<?php
    do_meta_boxes($post_type, 'normal', null);
    do_meta_boxes($post_type, 'advanced', null);
?>
        </div>
    </div>
</div>
<?php
}
