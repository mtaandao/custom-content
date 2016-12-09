<?php

define('MNCF_CUSTOM_POST_TYPE_VIEW',        'mncf_custom_post_type_view');
define('MNCF_CUSTOM_POST_TYPE_EDIT',        'mncf_custom_post_type_edit');
define('MNCF_CUSTOM_POST_TYPE_EDIT_OTHERS', 'mncf_custom_post_type_edit_others');

define('MNCF_CUSTOM_TAXONOMY_VIEW',         'mncf_custom_taxonomy_view');
define('MNCF_CUSTOM_TAXONOMY_EDIT',         'mncf_custom_taxonomy_edit');
define('MNCF_CUSTOM_TAXONOMY_EDIT_OTHERS',  'mncf_custom_taxonomy_edit_others');

define('MNCF_CUSTOM_FIELD_VIEW',            'mncf_custom_field_view');
define('MNCF_CUSTOM_FIELD_EDIT',            'mncf_custom_field_edit');
define('MNCF_CUSTOM_FIELD_EDIT_OTHERS',     'mncf_custom_field_edit_others');

define('MNCF_USER_META_FIELD_VIEW',         'mncf_user_meta_field_view');
define('MNCF_USER_META_FIELD_EDIT',         'mncf_user_meta_field_edit');
define('MNCF_USER_META_FIELD_EDIT_OTHERS',  'mncf_user_meta_field_edit_others');

define('MNCF_TERM_FIELD_VIEW',         'mncf_user_meta_field_view');
define('MNCF_TERM_FIELD_EDIT',         'mncf_user_meta_field_edit');
define('MNCF_TERM_FIELD_EDIT_OTHERS',  'mncf_user_meta_field_edit_others');


define('MNCF_EDIT',                         'manage_options');

/**
 * Class to Rule for Access
 *
 * @since 1.8
 *
 */
class MNCF_Roles
{
    private static $instance = null;
    private $users_settings;
    private static $users_settings_name = 'mncf_users_options';

    protected static $perms_to_pages = array();

    private function __construct() {
	    $this->users_settings = get_option( self::$users_settings_name, false );

	    add_action( 'init', array( $this, 'add_caps' ), 99 );
	    add_action( 'edit_user_profile', array( $this, 'edit_user_profile' ) );
	    add_filter( 'mncf_access_custom_capabilities', array( $this, 'mncf_access_custom_capabilities' ), 50 );
	    add_action( 'profile_update', array( $this, 'clean_the_mess_in_nonadmin_user_caps' ), 10, 1 );

    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new MNCF_Roles();
        }
        return self::$instance;
    }

    public static function edit_user_profile()
    {
        update_option(self::$users_settings_name, false);
    }

    public static function mncf_access_custom_capabilities($data)
    {
        $mn_roles['label'] = __('Types capabilities', 'mncf');
        $mn_roles['capabilities'] = self::mncf_get_capabilities();
        $data[] = $mn_roles;
        return $data;
    }

    public static final function mncf_get_capabilities() {
	    return array(

		    MNCF_CUSTOM_POST_TYPE_VIEW => __( 'View Post Types', 'mncf' ),
		    MNCF_CUSTOM_POST_TYPE_EDIT => __( 'Create and edit my Post Types', 'mncf' ),
		    MNCF_CUSTOM_POST_TYPE_EDIT_OTHERS => __( 'Edit others Post Types', 'mncf' ),

		    MNCF_CUSTOM_TAXONOMY_VIEW => __( 'View Taxonomies', 'mncf' ),
		    MNCF_CUSTOM_TAXONOMY_EDIT => __( 'Create and edit my Taxonomies', 'mncf' ),
		    MNCF_CUSTOM_TAXONOMY_EDIT_OTHERS => __( 'Edit others Taxonomies', 'mncf' ),

		    MNCF_CUSTOM_FIELD_VIEW => __( 'View Post Fields', 'mncf' ),
		    MNCF_CUSTOM_FIELD_EDIT => __( 'Create and edit my Post Fields', 'mncf' ),
		    MNCF_CUSTOM_FIELD_EDIT_OTHERS => __( 'Edit others Post Fields', 'mncf' ),

		    MNCF_USER_META_FIELD_VIEW => __( 'View User Fields', 'mncf' ),
		    MNCF_USER_META_FIELD_EDIT => __( 'Create and edit my User Fields', 'mncf' ),
		    MNCF_USER_META_FIELD_EDIT_OTHERS => __( 'Edit others User Fields', 'mncf' ),

		    MNCF_TERM_FIELD_VIEW => __( 'View Term Fields', 'mncf' ),
		    MNCF_TERM_FIELD_EDIT => __( 'Create and edit my Term Fields', 'mncf' ),
		    MNCF_TERM_FIELD_EDIT_OTHERS => __( 'Edit others Term Fields', 'mncf' ),

	    );
    }

    public static function get_cap_for_page($page)
    {
        return self::$perms_to_pages[$page] ? self::$perms_to_pages[$page] : MNCF_EDIT;
    }

    public function add_caps()
    {
        if( $this->users_settings ){
            return;
        }

        global $mn_roles;

        if ( ! isset( $mn_roles ) || ! is_object( $mn_roles ) ) {
            $mn_roles = new MN_Roles();
        }

        $mncf_capabilities = array_keys( self::mncf_get_capabilities() );

        $roles = $mn_roles->get_names();
        foreach ( $roles as $current_role => $role_name ) {
            $capability_can = apply_filters( 'mncf_capability_can', 'manage_options' );
            if ( isset( $mn_roles->roles[ $current_role ][ 'capabilities' ][ $capability_can ] ) ) {
                $role = get_role( $current_role );
                if ( isset( $role ) && is_object( $role ) ) {
                    for ( $i = 0, $caps_limit = count( $mncf_capabilities ); $i < $caps_limit; $i ++ ) {
                        if ( ! isset( $mn_roles->roles[ $current_role ][ 'capabilities' ][ $mncf_capabilities[ $i ] ] ) ) {
                            $role->add_cap( $mncf_capabilities[ $i ] );
                        }
                    }
                }
            }
        }

        //Set new caps for all Super Admins
        $super_admins = get_super_admins();
        foreach ( $super_admins as $admin ) {
            $updated_current_user = new MN_User( $admin );
            for ( $i = 0, $caps_limit = count( $mncf_capabilities ); $i < $caps_limit; $i ++ ) {
                $updated_current_user->add_cap( $mncf_capabilities[ $i ] );
            }
        }

        // We need to refresh $current_user caps to display the entire NNN menu

        // If $current_user has not been updated yet with the new capabilities,
        global $current_user;
        if ( isset( $current_user ) && isset( $current_user->ID ) ) {

            // Insert the capabilities for the current execution
            $updated_current_user = new MN_User( $current_user->ID );

            for ( $i = 0, $caps_limit = count( $mncf_capabilities ); $i < $caps_limit; $i ++ ) {
                if ( $updated_current_user->has_cap($mncf_capabilities[$i]) ) {
                    $current_user->add_cap($mncf_capabilities[$i]);
                }
            }

            // Refresh $current_user->allcaps
            $current_user->get_role_caps();
        }

        $this->users_settings = true;
        update_option(self::$users_settings_name, $this->users_settings);
    }


	/**
	 * In MNCF_Roles::add_caps() we're adding extra capabilities to superadmins.
	 *
	 * When the superadmin status is revoked, we need to take those caps back, otherwise we might create a security
	 * issue.
	 *
	 * This is a temporary workaround for types-768 until a better solution is provided.
	 *
	 * @param int|MN_User $user ID of the user or a MN_User instance that is currently being edited.
	 * @since 2.1
	 */
	public function clean_the_mess_in_nonadmin_user_caps( $user ) {
		
		if( ! $user instanceof MN_User ) {
			$user = new MN_User( $user );
			if( ! $user->exists() ) {
				return;
			}
		}

		// True if the user is network (super) admin. Also returns True if network mode is disabled and the user is an admin.
		$is_superadmin = is_super_admin( $user->ID );

		if( ! $is_superadmin ) {
			// We'll remove the extra Types capabilities. If the user has a role that adds those capabilities, nothing
			// should change for them.
			$mncf_capabilities = array_keys( self::mncf_get_capabilities() );
			foreach( $mncf_capabilities as $capability ) {
				$user->remove_cap( $capability );
			}
		}

	}


    public function disable_all_caps()
    {
        global $mn_roles;

        if ( ! isset( $mn_roles ) || ! is_object( $mn_roles ) ) {
            $mn_roles = new MN_Roles();
        }

        $mncf_capabilities = array_keys( self::mncf_get_capabilities() );

        foreach ( $mncf_capabilities as $cap ) {
            foreach (array_keys($mn_roles->roles) as $role) {
                $mn_roles->remove_cap($role, $cap);
            }
        }

        //Remove caps for all Super Admins
        $super_admins = get_super_admins();
        foreach ( $super_admins as $admin ) {
            $user = new MN_User( $admin );
            for ( $i = 0, $caps_limit = count( $mncf_capabilities ); $i < $caps_limit; $i ++ ) {
                $user->remove_cap( $mncf_capabilities[ $i ] );
            }
        }

    }

    public static function user_can_create($type = 'custom-post-type') {
	    switch ( $type ) {
		    case 'custom-post-type':
			    return current_user_can( MNCF_CUSTOM_POST_TYPE_EDIT );
		    case 'custom-taxonomy':
			    return current_user_can( MNCF_CUSTOM_TAXONOMY_EDIT );
		    case 'custom-field':
			    return current_user_can( MNCF_CUSTOM_FIELD_EDIT );
		    case 'user-meta-field':
			    return current_user_can( MNCF_USER_META_FIELD_EDIT );
		    case 'term-field':
				return current_user_can( MNCF_TERM_FIELD_EDIT );
	    }

	    return false;
    }

    public static function user_can_edit_other($type = 'custom-post-type') {
	    switch ( $type ) {
		    case 'custom-post-type':
			    return current_user_can( MNCF_CUSTOM_POST_TYPE_EDIT_OTHERS );
		    case 'custom-taxonomy':
			    return current_user_can( MNCF_CUSTOM_TAXONOMY_EDIT_OTHERS );
		    case 'custom-field':
			    return current_user_can( MNCF_CUSTOM_FIELD_EDIT_OTHERS );
		    case 'user-meta-field':
			    return current_user_can( MNCF_USER_META_FIELD_EDIT_OTHERS );
		    case 'term-field':
			    return current_user_can( MNCF_TERM_FIELD_EDIT_OTHERS );
	    }

	    return false;
    }

	/**
	 * @param string $type
	 * @param array|null $item
	 *
	 * @return bool
	 */
    public static function user_can_edit($type = 'custom-post-type', $item)
    {
        /**
         * check only for proper data
         */
        if ( !is_array($item) ) {
            return false;
        }
        /**
         * add new
         */
        switch( $type) {
        case 'custom-post-type':
        case 'custom-taxonomy':
            if ( !isset($item['slug'] ) || empty($item['slug']) ) {
                return self::user_can_create($type);
            }
            break;
        case 'custom-field':
        case 'user-meta-field':
        case 'term-field':
            if ( !isset($item['id'] ) || empty($item['id']) ) {
                return self::user_can_create($type);
            }
            break;
        }
        /**
         * if can edit other, then can edit always
         */
        if ( self::user_can_edit_other($type) ) {
            return true;
        }
        /**
         * if item has no autor or empty athor, then:
         * no! you can not edit
         */
        if ( !isset($item[MNCF_AUTHOR]) || empty($item[MNCF_AUTHOR]) ) {
            return false;
        }
        /**
         * no user - no edit
         */
        $user_id = get_current_user_id();
        if (empty($user_id) ) {
            return false;
        }
        /**
         * if author match, check can edit
         */
        return ( $item[MNCF_AUTHOR] == $user_id ) && self::user_can_create( $type );
    }

    public static function user_can_view( $type ) {
	    switch ( $type ) {
		    case 'custom-post-type':
			    return current_user_can( MNCF_CUSTOM_POST_TYPE_VIEW );
		    case 'custom-taxonomy':
			    return current_user_can( MNCF_CUSTOM_TAXONOMY_VIEW );
		    case 'custom-field':
			    return current_user_can( MNCF_CUSTOM_FIELD_VIEW );
		    case 'user-meta-field':
			    return current_user_can( MNCF_USER_META_FIELD_VIEW );
		    case 'term-field':
			    return current_user_can( MNCF_TERM_FIELD_VIEW );
	    }

	    return false;
    }

    public static function user_can_edit_custom_post_by_slug($slug)
    {
        $entries = get_option(MNCF_OPTION_NAME_CUSTOM_TYPES, array());
        if (isset($entries[$slug])) {
            return self::user_can_edit('custom-post-type', $entries[$slug]);
        }
        return false;
    }

    public static function user_can_edit_custom_taxonomy_by_slug($slug)
    {
        $entries = get_option(MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
        if (isset($entries[$slug])) {
            return self::user_can_edit('custom-taxonomy', $entries[$slug]);
        }
        $taxonomy = get_taxonomy($slug);
        if ( is_object($taxonomy) ) {
            return self::user_can_edit('custom-taxonomy', array( 'slug' => $taxonomy->name));
        }
        return false;
    }

    public static function user_can_edit_custom_field_group_by_id( $id )
    {
        $item = self::get_entry($id, 'mn-types-group');
        return self::user_can_edit('custom-field', $item);
    }


	public static function user_can_edit_term_field_group_by_id( $id )
	{
		$item = self::get_entry($id, Types_Field_Group_Term::POST_TYPE );
		return self::user_can_edit('term-field', $item);
	}


    public static function user_can_edit_usermeta_field_group_by_id( $id )
    {
        $item = self::get_entry($id, 'mn-types-user-group');
        return self::user_can_edit('user-meta-field', $item);
    }

    private static function get_entry($id, $post_type)
    {
        $args = array(
            'post__in' => array($id),
            'post_type' => $post_type,
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
        return $id;
    }
}
