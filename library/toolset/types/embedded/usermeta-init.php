<?php
// Add usermeta and post fileds groups to access.
$usermeta_access = new Usermeta_Access;
$fields_access = new Post_Fields_Access;
//setlocale(LC_ALL, 'nl_NL');

/**
 * Add User Fields menus hook
 *
 * @author Gen gen.i@icanlocalize.com
 * @since Types 1.3
 *
 *
 */
function mncf_admin_menu_edit_user_fields_hook() {
    do_action( 'mncf_admin_page_init' );

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
    //Css editor
    mn_enqueue_script( 'mncf-form-codemirror',
            MNCF_RELPATH . '/resources/js/codemirror234/lib/codemirror.js',
            array('mncf-js') );
    mn_enqueue_script( 'mncf-form-codemirror-css-editor',
            MNCF_RELPATH . '/resources/js/codemirror234/mode/css/css.js',
            array('mncf-js') );
    mn_enqueue_script( 'mncf-form-codemirror-html-editor',
            MNCF_RELPATH . '/resources/js/codemirror234/mode/xml/xml.js',
            array('mncf-js') );
    mn_enqueue_script( 'mncf-form-codemirror-html-editor2',
            MNCF_RELPATH . '/resources/js/codemirror234/mode/htmlmixed/htmlmixed.js',
            array('mncf-js') );
    mn_enqueue_script( 'mncf-form-codemirror-editor-resize',
            MNCF_RELPATH . '/resources/js/jquery_ui/jquery.ui.resizable.min.js',
            array('mncf-js') );



    mn_enqueue_style( 'mncf-css-editor',
            MNCF_RELPATH . '/resources/js/codemirror234/lib/codemirror.css' );
    mn_enqueue_style( 'mncf-usermeta',
            MNCF_EMBEDDED_RES_RELPATH . '/css/usermeta.css' );

    // MAIN
    mn_enqueue_script( 'mncf-fields-form',
            MNCF_EMBEDDED_RES_RELPATH
            . '/js/fields-form.js', array('mncf-js') );

    /**
     * fields form to manipulate fields
     */
    mn_enqueue_script(
        'mncf-admin-fields-form',
        MNCF_RES_RELPATH.'/js/fields-form.js',
        array(),
        MNCF_VERSION
    );

    /*
     * Enqueue styles
     */
    mn_enqueue_style( 'mncf-scroll', MNCF_EMBEDDED_TOOLSET_RELPATH . '/toolset-common/visual-editor/res/css/scroll.css' );
    mn_enqueue_style( 'font-awesome' );

    add_action( 'admin_footer', 'mncf_admin_fields_form_js_validation' );
    require_once MNCF_INC_ABSPATH . '/fields.php';
    require_once MNCF_INC_ABSPATH . '/usermeta.php';
    require_once MNCF_INC_ABSPATH . '/fields-form.php';
    require_once MNCF_INC_ABSPATH . '/usermeta-form.php';

    require_once MNCF_INC_ABSPATH.'/classes/class.types.admin.edit.meta.fields.group.php';
    $mncf_admin = new Types_Admin_Edit_Meta_Fields_Group();
    $mncf_admin->init_admin();
    $form = $mncf_admin->form();
    mncf_form( 'mncf_form_fields', $form );

    return;

    $form = mncf_admin_usermeta_form();
    mncf_form( 'mncf_form_fields', $form );

}

/**
 * Add/Edit usermeta fields group
 *
 * @author Gen gen.i@icanlocalize.com
 * @since Types 1.3
 */
function mncf_admin_menu_edit_user_fields()
{
    $add_new = false;
    $post_type = current_filter();
    $title = __('View User Field Group', 'mncf');
    if ( isset( $_GET['group_id'] ) ) {
        $item = mncf_admin_get_user_field_group_by_id( (int) $_GET['group_id'] );
        if ( MNCF_Roles::user_can_edit('user-meta-field', $item) ) {
            $title = __( 'Edit User Field Group', 'mncf' );
            $add_new = array(
                'page' => 'mncf-edit-usermeta',
            );
        }
    } else if ( MNCF_Roles::user_can_create('user-meta-field')) {
        $title = __( 'Add New User Field Group', 'mncf' );
    }
    mncf_add_admin_header( $title, $add_new);
    $form = mncf_form( 'mncf_form_fields' );
    echo '<form method="post" action="" class="mncf-fields-form mncf-form-validate js-types-show-modal">';
    mncf_admin_screen($post_type, $form->renderForm());
    echo '</form>';
    mncf_add_admin_footer();

    return;

    $form = mncf_form( 'mncf_form_fields' );
    echo '<br /><form method="post" action="" class="mncf-fields-form '
    . 'mncf-form-validate" onsubmit="';
    echo 'if (jQuery(\'#mncf-group-name\').val() == \'' . __( 'Enter group title', 'mncf' ) . '\') { jQuery(\'#mncf-group-name\').val(\'\'); }';
    echo 'if (jQuery(\'#mncf-group-description\').val() == \'' . __( 'Enter a description for this group', 'mncf' ) . '\') { jQuery(\'#mncf-group-description\').val(\'\'); }';
    echo 'jQuery(\'.mncf-forms-set-legend\').each(function(){
        if (jQuery(this).val() == \'' . __( 'Enter field name', 'mncf' ) . '\') {
            jQuery(this).val(\'\');
        }
        if (jQuery(this).next().val() == \'' . __( 'Enter field slug', 'mncf' ) . '\') {
            jQuery(this).next().val(\'\');
        }
        if (jQuery(this).next().next().val() == \'' . __( 'Describe this field', 'mncf' ) . '\') {
            jQuery(this).next().next().val(\'\');
        }
	});';
    echo '">';
    echo $form->renderForm();
    echo '</form>';
    mncf_add_admin_footer();
}


/**
 * Usermeta groups listing
 *
 * @author Gen gen.i@icanlocalize.com
 * @since Types 1.3
 */
function mncf_usermeta_summary()
{
    mncf_add_admin_header(
        __( 'User Field Groups', 'mncf' ),
        array('page' => 'mncf-edit-usermeta'),
        __('Add New', 'mncf')
    );
    require_once MNCF_INC_ABSPATH . '/fields.php';
    require_once MNCF_INC_ABSPATH . '/usermeta.php';
    require_once MNCF_INC_ABSPATH . '/usermeta-list.php';
    $to_display = mncf_admin_fields_get_fields();
    if ( !empty( $to_display ) ) {
        add_action( 'mncf_groups_list_table_after', 'mncf_admin_promotional_text' );
    }
    mncf_admin_usermeta_list();
    mncf_add_admin_footer();
}

//Add usermeta hook when user profile loaded
add_action( 'show_user_profile', 'mncf_admin_user_profile_load_hook' );
add_action( 'edit_user_profile', 'mncf_admin_user_profile_load_hook' );

//Save usermeta hook
add_action( 'personal_options_update', 'mncf_admin_user_profile_save_hook' );
add_action( 'edit_user_profile_update', 'mncf_admin_user_profile_save_hook' );



/**
 * Add usermeta groups to post editor
 */
add_filter( 'editor_addon_menus_types', 'mncf_admin_post_add_usermeta_to_editor_js' );

/*
* #################################################
* WHAT THE HELL IS THIS - START
* #################################################
*/
add_action( 'load-post.php', '__mncf_usermeta_test', PHP_INT_MAX );
add_action( 'load-post-new.php', '__mncf_usermeta_test', PHP_INT_MAX );

function __mncf_usermeta_test()
{
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
    $field['id'] = md5( 'date' . time() );
    $here = array(basename( $_SERVER['REQUEST_URI'] ), basename( $_SERVER['SCRIPT_FILENAME'] ));
    global $post;
    // Get post_type
    if ( $post ) {
        $post_type = get_post_type( $post );
    } else if ( !empty( $_GET['post'] ) ) {
        $post_type = get_post_type( sanitize_text_field( $_GET['post'] ) );
    } else if ( !empty( $_GET['post_type'] ) ) {
        $post_type = esc_html( sanitize_text_field( $_GET['post_type'] ) );
    }
    if ( ( $here[0] == ('index.php' || 'admin')) && ( $here[1] != 'index.php') ) {

        /** This action is documented in embedded/bootstrap.php */
        $post_types_without_meta_boxes = apply_filters(
            'toolset_filter_exclude_own_post_types',
            array('view', 'view-template', 'cred-form', 'cred-user-form')
        );

        if (
            isset( $post_type )
            && in_array( $post_type, $post_types_without_meta_boxes )
        ) {
            return;
        }
        mncf_admin_post_add_to_editor( $field );
    }
}

if ( !isset( $_GET['post_type'] ) && isset( $_GET['post'] ) ) {
    $post_type = get_post_type( sanitize_text_field( $_GET['post'] ) );
} else if (
    isset( $_GET['post_type'] )
    && in_array( $_GET['post_type'], get_post_types( array('show_ui' => true) ) ) 
) {
    $post_type = sanitize_text_field( $_GET['post_type'] );
}

/*
 *
 * This is not needed for Views 1.3
 * Kept for compatibility with older versions
 */
if ( isset( $post_type ) && in_array( $post_type,
                array('view', 'view-template', 'cred-form', 'cred-user-form') ) ) {
    add_filter( 'editor_addon_menus_mnv-views',
            'mncf_admin_post_add_usermeta_to_editor_js', 20 );
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
    add_action( 'admin_footer', 'mncf_admin_post_js_validation' );
    //mncf_enqueue_scripts();
}

/*
* #################################################
* WHAT THE HELL IS THIS - END
* #################################################
*/

/**
 * Get current logged user ID
 *
 * @author Gen gen.i@icanlocalize.com
 * @since Types 1.3
 */
function mncf_usermeta_get_user( $method = '' ){
    if ( empty( $method ) ) {
        $current_user = mn_get_current_user();
        $user_id = $current_user->ID;
    }

    return $user_id;
}

/**
 * Add User Fields to editor
 *
 * @author Gen gen.i@icanlocalize.com
 * @since Types 1.3
 */
function mncf_admin_post_add_usermeta_to_editor_js( $menu, $views_callback = false ){
    global $mncf;

    $post = apply_filters( 'mncf_filter_mncf_admin_get_current_edited_post', null );
    if ( ! $post ) {
        $post = (object) array('ID' => -1);
    }

    $groups = mncf_admin_fields_get_groups( TYPES_USER_META_FIELD_GROUP_CPT_NAME );
    $user_id = mncf_usermeta_get_user();
    if ( !empty( $groups ) ) {
        $item_styles = array();
        foreach ( $groups as $group_id => $group ) {
            if ( empty( $group['is_active'] ) ) {
                continue;
            }
			$group_name = sprintf( __( '%s (Usermeta fields)', 'mncf' ) , $group['name'] );
            $fields = mncf_admin_fields_get_fields_by_group( $group['id'],
                    'slug', true, false, true, TYPES_USER_META_FIELD_GROUP_CPT_NAME,
                    'mncf-usermeta' );

            if ( !empty( $fields ) ) {
                foreach ( $fields as $field_id => $field ) {
                    // Use field class
                    $mncf->usermeta_field->set( $user_id, $field );

                    // Get field data
                    $data = (array) $mncf->usermeta_field->config;

                    // Get inherited field
                    if ( isset( $data['inherited_field_type'] ) ) {
                        $inherited_field_data = mncf_fields_type_action( $data['inherited_field_type'] );
                    }

                    $callback = 'mncfFieldsEditorCallback(\'' . $field['id']
                            . '\', \'usermeta\', ' . $post->ID . ')';

                    // Added for Views:users filter Vicon popup
                    if ( $views_callback ){
                        $callback = 'mncfFieldsEditorCallback(\'' . $field['id']
                            . '\', \'views-usermeta\', ' . $post->ID . ')';
                    }

                    $menu[$group_name][stripslashes( $field['name'] )] = array(stripslashes(mn_kses_post($field['name'])), trim( mncf_usermeta_get_shortcode( $field ),
                                '[]' ), $group_name, $callback);
                }
                /*
                 * Since Types 1.2
                 * We use field class to enqueue JS and CSS
                 */
                $mncf->usermeta_field->enqueue_script();
                $mncf->usermeta_field->enqueue_style();
            }
        }
    }

    return $menu;

}

/**
 * Calls view function for specific field type.
 *
 * @param $field_id
 * @param $params
 * @param null $content
 * @param string $code
 *
 * @return string
 *
 * @deprecated Use types_render_usermeta() instead.
 */
function types_render_usermeta_field( $field_id, $params, $content = null, $code = '' ) {
	return types_render_usermeta( $field_id, $params, $content, $code );
}

/**
 * Add fields to user profile
 */
function mncf_admin_user_profile_load_hook( $user )
{
    if ( !current_user_can( 'edit_user', $user->ID ) ) {
        return false;
    }
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields.php';
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/usermeta-post.php';
    mncf_admin_userprofile_init( $user );
}

/**
 * Add styles to admin fields groups
 */

add_action('admin_head-profile.php', 'mncf_admin_fields_usermeta_styles' );
add_action('admin_head-user-edit.php', 'mncf_admin_fields_usermeta_styles' );
add_action('admin_head-user-new.php', 'mncf_admin_fields_usermeta_styles' );

function mncf_admin_fields_usermeta_styles()
{
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields.php';
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/usermeta-post.php';
    $groups = mncf_admin_fields_get_groups( TYPES_USER_META_FIELD_GROUP_CPT_NAME );
    $content = '';

    if ( !empty( $groups ) ) {
        global $user_id;
        $user_role = false;
        if ( !empty( $user_id ) ) {
            $user_info = get_userdata($user_id);
            $user_role = isset($user_info->roles) ? array_shift($user_info->roles) : 'subscriber';
            unset($user_info);
        }
        foreach ( $groups as $group ) {
            if ( !empty($user_id) ) {
                $for_users = mncf_admin_get_groups_showfor_by_group($group['id']);
                if ( !empty($for_users) && !in_array($user_role, $for_users) ) {
                    continue;
                }
            }
            if ( empty( $group['is_active'] ) ) {
                continue;
            }
            $content .= str_replace( "}", '}'.PHP_EOL, mncf_admin_get_groups_admin_styles_by_group( $group['id'] ) );
            $content .= PHP_EOL;
        }
    }
    if ( $content ) {
        printf('<style type="text/css">%s</style>%s', $content, PHP_EOL );
    }
}

/**
 * Add fields to user profile
 */
function mncf_admin_user_profile_save_hook( $user_id )
{
    if ( !current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields.php';
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/usermeta-post.php';
    mncf_admin_userprofilesave_init( $user_id );
}

/*
 *  Register Usermeta Groups in Types Access
 *
 *
 */

class Usermeta_Access
{

    public static $user_groups = '';

    /**
     * Initialize plugin enviroment
     */
    public function __construct() {
        // setup custom capabilities
        self::$user_groups = mncf_admin_fields_get_groups(TYPES_USER_META_FIELD_GROUP_CPT_NAME);
        //If access plugin installed
        if ( function_exists( 'mncf_access_register_caps' ) ) { // integrate with Custom Content Access
            if ( !empty( self::$user_groups ) ) {
				$access_version = apply_filters( 'toolset_access_version_installed', '1.0' );
				// Since 2.1 we can define a custom tab on Access >= 2.1
				if ( version_compare( $access_version, '2.0' ) > 0 ) {
					// Add Types Fields tab
					add_filter( 'types-access-tab', array( 'Usermeta_Access', 'register_access_types_fields_tab' ) );
					//Add Usermeta Fields area
					add_filter( 'types-access-area-for-types-fields',
							array('Usermeta_Access', 'register_access_usermeta_area'),
							20, 2 );
				} else {
					//Add Usermeta Fields area
					add_filter( 'types-access-area',
							array('Usermeta_Access', 'register_access_usermeta_area'),
							10, 2 );
				}
                //Add Usermeta Fields groups
                add_filter( 'types-access-group',
                        array('Usermeta_Access', 'register_access_usermeta_groups'),
                        10, 2 );
                //Add Usermeta Fields caps to groups
                add_filter( 'types-access-cap',
                        array('Usermeta_Access', 'register_access_usermeta_caps'),
                        10, 3 );
            }
        }
    }

    // register custom CRED Frontend capabilities specific to each group
    public static function register_access_usermeta_caps( $caps, $area_id,
            $group_id )
    {
        $USERMETA_ACCESS_AREA_NAME = __( 'User Meta Fields Frontend Access', 'mncf' );
        $USERMETA_ACCESS_AREA_ID = '__USERMETA_FIELDS';
        $default_role = 'guest'; //'administrator';
        //List of caps with default permissions
        $usermeta_caps = array(
           /* array('view_own_on_site', $default_role, __( 'View own fields on site', 'mncf' )),
            array('view_others_on_site', $default_role, __( 'View others fields on site', 'mncf' )),*/
            array('view_own_in_profile', $default_role, __( 'View own fields in profile', 'mncf' )),
            array('modify_own', $default_role, __( 'Modify own fields', 'mncf' )),
                /*
                  array('view_others_in_profile',$default_role,__('View others fields in profile','mncf')),
                  array('modify_others_','administrator',__('Modify others fields','mncf')), */
        );
        if ( $area_id == $USERMETA_ACCESS_AREA_ID ) {
            $fields_groups = mncf_admin_fields_get_groups( TYPES_USER_META_FIELD_GROUP_CPT_NAME );
            if ( !empty( $fields_groups ) ) {
                foreach ( $fields_groups as $group ) {
                    $USERMETA_ACCESS_GROUP_NAME = $group['name'] . ' Access Group';
                    $USERMETA_ACCESS_GROUP_ID = '__USERMETA_FIELDS_GROUP_' . $group['slug'];
                    if ( $group_id == $USERMETA_ACCESS_GROUP_ID ) {
                        for ( $i = 0; $i < count( $usermeta_caps ); $i++ ) {
                            $caps[$usermeta_caps[$i][0] . '_' . $group['slug']] = array(
                                'cap_id' => $usermeta_caps[$i][0] . '_' . $group['slug'],
                                'title' => $usermeta_caps[$i][2],
                                'default_role' => $usermeta_caps[$i][1]
                            );
                        }
                    }
                }
            }
        }

        return $caps;
    }

    // register a new Types Access Group within Area for Usermeta Fields Groups Frontend capabilities
    public static function register_access_usermeta_groups( $groups, $id )
    {
        $USERMETA_ACCESS_AREA_NAME = __( 'User Meta Fields Frontend Access', 'mncf' );
        $USERMETA_ACCESS_AREA_ID = '__USERMETA_FIELDS';

        if ( $id == $USERMETA_ACCESS_AREA_ID ) {
            $fields_groups = mncf_admin_fields_get_groups( TYPES_USER_META_FIELD_GROUP_CPT_NAME );
            if ( !empty( $fields_groups ) ) {
                foreach ( $fields_groups as $group ) {
                    $USERMETA_ACCESS_GROUP_NAME = $group['name'];
                    //. ' User Meta Fields Access Group'
                    $USERMETA_ACCESS_GROUP_ID = '__USERMETA_FIELDS_GROUP_' . $group['slug'];
                    $groups[] = array('id' => $USERMETA_ACCESS_GROUP_ID, 'name' => '' . $USERMETA_ACCESS_GROUP_NAME);
                }
            }
        }
        return $groups;
    }
	
	/**
	* Register a custom tab on the Access Control admin page, for Types fields.
	*
	* @param $tabs
	* @return $tabs
	*
	* @since 2.1
	*/
	
	public static function register_access_types_fields_tab( $tabs ) {
		$tabs['types-fields'] = __( 'Types Fields', 'mn-cred' );
		return $tabs;
	}

    // register a new Types Access Area for Usermeta Fields Groups Frontend capabilities
    public static function register_access_usermeta_area( $areas,
            $area_type = 'usermeta' )
    {
        $USERMETA_ACCESS_AREA_NAME = __( 'User Meta Fields Access', 'mncf' );
        $USERMETA_ACCESS_AREA_ID = '__USERMETA_FIELDS';
        $areas[] = array('id' => $USERMETA_ACCESS_AREA_ID, 'name' => $USERMETA_ACCESS_AREA_NAME);
        return $areas;
    }

}

/*
 *  Register Post Fields Groups in Types Access
 *
 * @author Gen gen.i@icanlocalize.com
 * @since Types 1.3
 */

class Post_Fields_Access
{

    /**
     * Initialize plugin enviroment
     */
    public static $fields_groups = '';

    public function __construct() {
    	//Get list of groups
    	self::$fields_groups = mncf_admin_fields_get_groups();
        // setup custom capabilities
        //If access plugin installed
        if ( function_exists( 'mncf_access_register_caps' ) ) { // integrate with Types Access
            if ( !empty( self::$fields_groups ) ) {
				$access_version = apply_filters( 'toolset_access_version_installed', '1.0' );
				// Since 2.1 we can define a custom tab on Access >= 2.1
				if ( version_compare( $access_version, '2.0' ) > 0 ) {
					// Add Types Fields tab
					add_filter( 'types-access-tab', array( 'Post_Fields_Access', 'register_access_types_fields_tab' ) );
					//Add Usermeta Fields area
					add_filter( 'types-access-area-for-types-fields',
							array('Post_Fields_Access', 'register_access_fields_area'),
							10, 2 );
				} else {
					//Add Usermeta Fields area
					add_filter( 'types-access-area',
							array('Post_Fields_Access', 'register_access_fields_area'),
							10, 2 );
				}
                //Add Fields groups
                add_filter( 'types-access-group',
                        array('Post_Fields_Access', 'register_access_fields_groups'),
                        10, 2 );

                //Add Fields caps to groups
                add_filter( 'types-access-cap',
                        array('Post_Fields_Access', 'register_access_fields_caps'),
                        10, 3 );
				//}
            }
        }
    }

    // register custom CRED Frontend capabilities specific to each group
    public static function register_access_fields_caps( $caps, $area_id,
            $group_id )
    {
        $FIELDS_ACCESS_AREA_NAME = __( 'Post Custom Fields Frontend Access', 'mncf' );
        $FIELDS_ACCESS_AREA_ID = '__FIELDS';
        $default_role = 'guest'; //'administrator';
        //List of caps with default permissions
        $fields_caps = array(
            /*array('view_fields_on_site', $default_role, __( 'View Fields On Site', 'mncf' )),*/
            array('view_fields_in_edit_page', $default_role, __( 'View Fields In Edit Page', 'mncf' )),
            array('modify_fields_in_edit_page', 'author', __( 'Modify Fields In Edit Page', 'mncf' )),
        );
        if ( $area_id == $FIELDS_ACCESS_AREA_ID ) {

            if ( !empty( self::$fields_groups ) ) {
                foreach ( self::$fields_groups as $group ) {
                    $FIELDS_ACCESS_GROUP_NAME = $group['name'] . ' Access Group';
                    $FIELDS_ACCESS_GROUP_ID = '__FIELDS_GROUP_' . $group['slug'];
                    if ( $group_id == $FIELDS_ACCESS_GROUP_ID ) {
                        for ( $i = 0; $i < count( $fields_caps ); $i++ ) {
                            $caps[$fields_caps[$i][0] . '_' . $group['slug']] = array(
                                'cap_id' => $fields_caps[$i][0] . '_' . $group['slug'],
                                'title' => $fields_caps[$i][2],
                                'default_role' => $fields_caps[$i][1]
                            );
                        }
                    }
                }
            }
        }

        return $caps;
    }

    // register a new Types Access Group within Area for Post Fields Groups Frontend capabilities
    public static function register_access_fields_groups( $groups, $id )
    {
        $FIELDS_ACCESS_AREA_NAME = __( 'Post Fields Frontend Access', 'mncf' );
        $FIELDS_ACCESS_AREA_ID = '__FIELDS';

        if ( $id == $FIELDS_ACCESS_AREA_ID ) {
            if ( !empty( self::$fields_groups ) ) {
                foreach ( self::$fields_groups as $group ) {
                    $FIELDS_ACCESS_GROUP_NAME = $group['name'];
                    //. ' User Meta Fields Access Group'
                    $FIELDS_ACCESS_GROUP_ID = '__FIELDS_GROUP_' . $group['slug'];
                    $groups[] = array('id' => $FIELDS_ACCESS_GROUP_ID, 'name' => '' . $FIELDS_ACCESS_GROUP_NAME);
                }
            }
        }
        return $groups;
    }
	
	/**
	* Register a custom tab on the Access Control admin page, for Types fields.
	*
	* @param $tabs
	* @return $tabs
	*
	* @since 2.1
	*/
	
	public static function register_access_types_fields_tab( $tabs ) {
		$tabs['types-fields'] = __( 'Types Fields', 'mn-cred' );
		return $tabs;
	}

    // register a new Types Access Area for Post Fields Groups Frontend capabilities
    public static function register_access_fields_area( $areas,
            $area_type = 'usermeta' )
    {
        $FIELDS_ACCESS_AREA_NAME = __( 'Post Meta Fields Access', 'mncf' );
        $FIELDS_ACCESS_AREA_ID = '__FIELDS';
        $areas[] = array('id' => $FIELDS_ACCESS_AREA_ID, 'name' => $FIELDS_ACCESS_AREA_NAME);
        return $areas;
    }

}

add_action( 'mn_ajax_mncf_types_suggest_user', 'mncf_access_mncf_types_suggest_user_ajax' );

/**
 * Suggest user AJAX.
 *
 * @todo nonce
 * @todo auth
 */
function mncf_access_mncf_types_suggest_user_ajax()
{
    global $mndb;
    $users = '';
    $q = '%'.mntoolset_esc_like(esc_sql( trim( $_GET['q'] ) )).'%';
    $found = $mndb->get_results(
        $mndb->prepare(
            "SELECT ID, display_name, user_login 
			FROM {$mndb->users} 
			WHERE user_nicename LIKE %s 
			OR user_login LIKE %s 
			OR display_name LIKE %s 
			OR user_email LIKE %s 
			LIMIT %d",
            $q,
            $q,
            $q,
            $q,
            10
        )
    );

    if ( !empty( $found ) ) {
        foreach ( $found as $user ) {
            $users .= '<li>' . $user->user_login . '</li>';
        }
    }
    echo $users;
    die();
}

add_action('load-user-new.php', 'mncf_usermeta_add_user_screen');
function mncf_usermeta_add_user_screen() {
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/usermeta-add-user.php';
    mncf_usermeta_add_user_screen_init();
}

/**
 * Return very simple data of group
 *
 * @since 1.8.0
 *
 * @param string $group_id Group id
 * @return mixed Array if this is proper $group_id or $group_id
 */
function mncf_admin_get_user_field_group_by_id($group_id)
{
    $args = array(
        'post__in' => array($group_id),
        'post_type' => 'mn-types-user-group',
    );
    $query = new MN_Query($args);
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $data = array(
                'id' => get_the_ID(),
                MNCF_AUTHOR => get_the_author_meta('ID'),
            );
            mn_reset_postdata();
            return $data;
        }
    }
    return $group_id;
}

