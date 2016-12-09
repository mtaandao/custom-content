<?php

class MNCF_MNViews
{

    /**
     * Init called from MNCF_Loader.
     */
    public static function init() {
		add_action( 'load-post.php', array( 'MNCF_MNViews', 'mncf_mnv_admin_post_add_postmeta_usermeta_to_editor_js') );
		add_action( 'load-post-new.php', array( 'MNCF_MNViews', 'mncf_mnv_admin_post_add_postmeta_usermeta_to_editor_js') );
        add_action( 'views_edit_screen', array('MNCF_MNViews', 'editScreenInit') );
        add_action( 'layouts_edit_screen', array('MNCF_MNViews', 'editScreenInit') );
		add_action( 'current_screen', array( 'MNCF_MNViews', 'include_types_fields_on_views_dialog_on_demand' ) );
        add_action( 'views_ct_inline_editor', array('MNCF_MNViews', 'addEditorDropdownFilter') );
		add_action( 'mnv_action_mnv_add_types_postmeta_usermeta_to_editor_menus', array('MNCF_MNViews', 'addEditorDropdownFilter') );
    }
	
	/**
	* Include the Types custom fields and usermeta fields, along with the needed scripts to mange them, in the Fields and Views dialog, on demand
	*/
	
	public static function include_types_fields_on_views_dialog_on_demand( $current_screen ) {
		
		/**
		* mncf_filter_force_include_types_fields_on_views_dialog
		*
		* Force include the Types fields and usermeta fields as groups on the Fields and Views popup.
		* This adds assets as well as menu items.
		* Note that this happens on current_screen so this filter need to be added before that.
		*
		* @param (bool) Whether to include those items or not.
		* @param $current_screen (object) The current MN_Screen object.
		*
		* @since 1.7
		*/
		$force_include_types_in_fields_and_views_dialog = apply_filters( 'mncf_filter_force_include_types_fields_on_views_dialog', false, $current_screen );
		
		if ( $force_include_types_in_fields_and_views_dialog ) {
			self::editScreenInit();
		}
	}

    /**
     * Actions for Views edit screens.
     */
    public static function editScreenInit() {
		if ( ! mn_script_is( 'types', 'enqueued' ) ) {
			mn_enqueue_script( 'types' );
		}
		if ( ! mn_script_is( 'types-mn-views', 'enqueued' ) ) {
			mn_enqueue_script( 'types-mn-views' );
		}
		if ( ! mn_script_is( 'toolset-colorbox', 'enqueued' ) ) {
			mn_enqueue_script( 'toolset-colorbox' );
		}
		if ( ! mn_style_is( 'toolset-colorbox', 'enqueued' ) ) {
			mn_enqueue_style( 'toolset-colorbox' );
		}
        self::addEditorDropdownFilter();
    }

    /**
     * Adds filtering editor dropdown items.
     */
    public static function addEditorDropdownFilter() {
        add_filter( 'editor_addon_menus_mnv-views',
                array('MNCF_MNViews', 'editorDropdownFilter') );
        add_filter( 'editor_addon_menus_mnv-views',
                'mncf_admin_post_add_usermeta_to_editor_js', 20 );
    }
	
	public static function mncf_mnv_admin_post_add_postmeta_usermeta_to_editor_js() {
		add_action( 'mnv_action_mnv_add_types_postmeta_to_editor', array( 'MNCF_MNViews', 'mncf_admin_post_add_postmeta_to_editor_on_demand' ) );
		add_action( 'mnv_action_mnv_add_types_post_usermeta_to_editor', array( 'MNCF_MNViews', 'mncf_admin_post_add_usermeta_to_editor_on_demand') );
	}
	
	public static function mncf_admin_post_add_postmeta_to_editor_on_demand( $editor ) {
		add_action( 'admin_footer', 'mncf_admin_post_js_validation' );
		mncf_enqueue_scripts();
        mn_enqueue_script( 'toolset-colorbox' );
        mn_enqueue_style( 'toolset-colorbox' );
		
		$current_post = apply_filters( 'mncf_filter_mncf_admin_get_current_edited_post', null );
		if ( empty( $current_post ) ) {
			$current_post = (object) array('ID' => -1);
		}

		$fields = mncf_admin_post_add_to_editor( 'get' );
		$groups = mncf_admin_post_get_post_groups_fields( $current_post );
		if ( 
			! empty( $fields ) 
			&& ! empty( $groups ) 
		) {
			foreach ( $groups as $group ) {
				if ( empty( $group['fields'] ) ) {
					continue;
				}
				foreach ( $group['fields'] as $group_field_id => $group_field ) {
					if ( isset( $fields[$group_field_id] ) ) {
						$field = $fields[$group_field_id];
						$callback = 'mncfFieldsEditorCallback(\'' . $field['id'] . '\', \'postmeta\', ' . $current_post->ID . ')';
						$editor->add_insert_shortcode_menu( 
							stripslashes( $field['name'] ),
							trim( mncf_fields_get_shortcode( $field ), '[]' ),
							$group['name'],
							$callback 
						);
					}
				}
			}
		}
	}
	
	public static function mncf_admin_post_add_usermeta_to_editor_on_demand() {
		add_action( 'admin_footer', 'mncf_admin_post_js_validation' );
		mncf_enqueue_scripts();
        mn_enqueue_script( 'toolset-colorbox' );
        mn_enqueue_style( 'toolset-colorbox' );
		add_filter( 'editor_addon_menus_mnv-views', 'mncf_admin_post_add_usermeta_to_editor_js' );
	}

    /**
     * Adds items to view dropdown.
     * 
     * @param type $items
     * @return type 
     */
    public static function editorDropdownFilter( $menu ) {
		$post = apply_filters( 'mncf_filter_mncf_admin_get_current_edited_post', null );
        if ( ! $post ) {
            $post = (object) array('ID' => -1);
        }
        $groups = mncf_admin_fields_get_groups( TYPES_CUSTOM_FIELD_GROUP_CPT_NAME, 'group_active' );
        $all_post_types = implode( ' ',
                get_post_types( array('public' => true) ) );
        $add = array();
        if ( !empty( $groups ) ) {
            // $group_id is blank therefore not equal to $group['id']
            // use array for item key and CSS class
            $item_styles = array();

            foreach ( $groups as $group ) {
                $fields = mncf_admin_fields_get_fields_by_group( $group['id'],
                        'slug', true, false, true );
                if ( !empty( $fields ) ) {
                    // code from Types used here without breaking the flow
                    // get post types list for every group or apply all
                    $post_types = get_post_meta( $group['id'],
                            '_mn_types_group_post_types', true );
                    if ( $post_types == 'all' ) {
                        $post_types = $all_post_types;
                    }
                    $post_types = trim( str_replace( ',', ' ', $post_types ) );
                    $item_styles[$group['name']] = $post_types;

                    foreach ( $fields as $field ) {
                        $callback = 'mncfFieldsEditorCallback(\'' . $field['id']
                                . '\', \'postmeta\', ' . $post->ID . ')';
                        $menu[$group['name']][stripslashes( $field['name'] )] = array(stripslashes( $field['name'] ), trim( mncf_fields_get_shortcode( $field ),
                                    '[]' ), $group['name'], $callback);
                        // TODO Remove - it's not post edit screen (meta box JS and CSS)
                        MNCF_Fields::enqueueScript( $field['type'] );
                        MNCF_Fields::enqueueStyle( $field['type'] );
                    }
                }
            }
        }
        return $menu;
    }

}
