<?php

require_once MNCF_INC_ABSPATH . '/classes/class.types.admin.page.php';
include_once MNCF_INC_ABSPATH.'/common-functions.php';

/**
 * Summary.
 *
 * Description.
 *
 * @since x.x.x
 * @access (for functions: only use if private)
 *
 * @see Function/method/class relied on
 * @link URL
 * @global type $varname Description.
 * @global type $varname Description.
 *
 * @param type $var Description.
 * @param type $var Optional. Description.
 * @return type Description.
 */
class Types_Admin_Edit_Post_Type extends Types_Admin_Page
{
    private $fields;

    public function __construct()
    {
        add_action('mn_ajax_mncf_edit_post_get_child_fields_screen', array($this, 'prepare_field_select_screen'));
        add_action('mn_ajax_mncf_edit_post_get_icons_list', array($this, 'get_icons_list'));
        add_action('mn_ajax_mncf_edit_post_save_child_fields', array($this, 'save_child_fields'));
        add_action('mn_ajax_mncf_edit_post_save_custom_fields_groups', array($this, 'save_custom_fields_groups'));
        add_filter('types_get_post_type_slug_from_request', array($this, 'get_post_type_slug_from_request'));
    }

    public function init_admin()
    {
        if ( is_admin() ) {
            include_once MNCF_INC_ABSPATH.'/classes/class.types.admin.fields.php';
            $this->fields = new Types_Admin_Fields();
        }

        $this->init_hooks();
        $this->get_id = 'mncf-post-type';

        $this->post_type = 'post_type';

        $this->boxes = array(
            'submitdiv' => array(
                'callback' => array($this, 'box_submitdiv'),
                'title' => __('Save', 'mncf'),
                'default' => 'side',
                'priority' => 'core',
            ),
            'types_labels' => array(
                'callback' => array($this, 'box_labels'),
                'title' => __('Labels', 'mncf'),
                'default' => 'normal',
                'post_types' => 'custom',
                'priority' => 'core',
            ),
            'types_taxonomies' => array(
                'callback' => array($this, 'box_taxonomies'),
                'title' => __('Taxonomies to be used with <i class="js-mncf-singular"></i>', 'mncf'),
                'default' => 'normal',
                'priority' => 'core',
            ),
            'types_display_sections' => array(
                'callback' => array($this, 'box_display_sections'),
                'title' => __('Sections to display when editing <i class="js-mncf-singular"></i>', 'mncf'),
                'default' => 'normal',
                'priority' => 'low',
                'post_types' => 'custom',
            ),
            'types_options' => array(
                'callback' => array($this, 'box_options'),
                'title' => __('Options', 'mncf'),
                'default' => 'normal',
                'post_types' => 'custom',
                'priority' => 'low',
            ),
        );

        /**
         * Summary.
         *
         * Description.
         *
         * @since x.x.x
         *
         * @param type  $var Description.
         * @param array $args {
         *     Short description about this hash.
         *
         *     @type type $var Description.
         *     @type type $var Description.
         * }
         * @param type  $var Description.
         */
        $this->boxes = apply_filters('mncf_meta_box_order_defaults', $this->boxes, $this->post_type);

        /**
         * Summary.
         *
         * Description.
         *
         * @since x.x.x
         *
         * @param type  $var Description.
         * @param array $args {
         *     Short description about this hash.
         *
         *     @type type $var Description.
         *     @type type $var Description.
         * }
         * @param type  $var Description.
         */
        $this->boxes = apply_filters('mncf_meta_box_post_type', $this->boxes);

        /**
         * MNML integration
         */
        if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
            $this->boxes['mnml'] = array(
                'callback' => array($this, 'mnml_box'),
                'title'    => __( 'Translation', 'mncf' ),
                'default'  => 'normal',
                'priority' => 'low',
            );
        }

        /** This action is documented in includes/classes/class.types.admin.page.php  */
        add_action('mncf_closedpostboxes', array($this, 'closedpostboxes'));

    }

    /**
     * Add/edit form
     */
    public function form()
    {
        $this->save();

	    // Flush rewrite rules if we're asked to do so.
	    //
	    // This must be done after all post types and taxonomies are registered, and they can be registered properly
	    // only on 'init'. So after making changes, we need to reload the page and THEN flush.
	    if( '1' == mncf_getget( 'flush', '0' ) ) {
		    flush_rewrite_rules();
	    }


        global $mncf;

        $id = false;
        $update = false;

        if ( isset( $_GET[$this->get_id] ) ) {
            $id = sanitize_text_field( $_GET[$this->get_id] );
        } elseif ( isset( $_POST[$this->get_id] ) ) {
            $id = sanitize_text_field( $_POST[$this->get_id] );
        }

        /**
         * get current post type
         */
        require_once MNCF_INC_ABSPATH.'/classes/class.types.admin.post-type.php';
        $mncf_post_type = new Types_Admin_Post_Type();
        $custom_post_type = $mncf_post_type->get_post_type($id);
        if (empty($custom_post_type)) {
            mncf_admin_message( __( 'Please save new Post Type first.', 'mncf' ), 'error' );
            die;
        }
        $this->ct = $custom_post_type;

        $current_user_can_edit = MNCF_Roles::user_can_edit('custom-post-type', $this->ct);

        /**
         * sanitize _builtin
         */
        if ( !isset($this->ct['_builtin']) ) {
            $this->ct['_builtin'] = false;
        }

        /**
         * fix taxonomies assigment for builitin post types
         */
        if ( $this->ct['_builtin']) {
            $taxonomies = get_taxonomies( '', 'objects' );
            foreach( $taxonomies as $slug => $tax ) {
                foreach( $tax->object_type as $post_slug ) {
                    if ( $this->ct['slug'] == $post_slug) {
                        $this->ct['taxonomies'][$slug] = 1;
                    }
                }
            }
        }

        $form = $this->prepare_screen();

        if ( $current_user_can_edit && $this->ct['update'] ) {
            $form['id'] = array(
                '#type' => 'hidden',
                '#value' => $id,
                '#name' => 'ct[mncf-post-type]',
                '_builtin' => true,
            );

	        $form['slug_conflict_check_nonce'] = array(
		        '#type' => 'hidden',
		        '#value' => mn_create_nonce( Types_Ajax::CALLBACK_CHECK_SLUG_CONFLICTS ),
		        '#name' => 'types_check_slug_conflicts_nonce',
		        '_builtin' => true,
	        );
	        
            /**
             * update Taxonomy too
             */
            $custom_taxonomies = get_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
            foreach( $custom_taxonomies as $slug => $data ) {
                if ( !array_key_exists('supports', $data)) {
                    continue;
                }
                if ( !array_key_exists($id, $data['supports']) ) {
                    continue;
                }
                if (
                    array_key_exists('taxonomies', $this->ct)
                    && array_key_exists($slug, $this->ct['taxonomies'])
                ) {
                    continue;
                }
                unset($custom_taxonomies[$slug]['supports'][$id]);
            }
            update_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom_taxonomies);
        }

        /*
         * menu icon
         */
        switch( $this->ct['slug'] ) {
            case 'page':
                $menu_icon = 'admin-page';
                break;
            case 'attachment':
                $menu_icon = 'admin-media';
                break;
            default:
                $menu_icon = isset( $this->ct['icon']) && !empty($this->ct['icon']) ? $this->ct['icon'] : 'admin-post';
                break;
        }

        /**
         * post icon field
         */
        $form['icon'] = array(
            '#type' => 'hidden',
            '#name' => 'ct[icon]',
            '#value' => $menu_icon,
            '#id' => 'mncf-types-icon',
        );

        $form['form-open'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '<div id="post-body-content" class="%s">',
                $current_user_can_edit? '':'mncf-types-read-only'
            ),
            '_builtin' => true,
        );

        $form['table-1-open'] = array(
            '#type' => 'markup',
            '#markup' => '<table id="mncf-types-form-name-table" class="mncf-types-form-table widefat js-mncf-slugize-container"><thead><tr><th colspan="2">' . __( 'Name and description', 'mncf' ) . '</th></tr></thead><tbody>',
            '_builtin' => true,
        );
        $table_row = '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>';
        $form['name'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[labels][name]',
            '#title' => __( 'Name plural', 'mncf' ) . ' (<strong>' . __( 'required', 'mncf' ) . '</strong>)',
            '#description' => '<strong>' . __( 'Enter in plural!', 'mncf' )
            . '.',
            '#value' => isset( $this->ct['labels']['name'] ) ? $this->ct['labels']['name'] : '',
            '#validate' => array(
                'required' => array('value' => 'true'),
            ),
            '#pattern' => $table_row,
            '#inline' => true,
            '#id' => 'name-plural',
            '#attributes' => array(
                'data-mncf_warning_same_as_slug' => $mncf->post_types->message( 'warning_singular_plural_match' ),
                'data-mncf_warning_same_as_slug_ignore' => $mncf->post_types->message( 'warning_singular_plural_match_ignore' ),
                'placeholder' => __('Enter Post Type name plural', 'mncf' ),
                'class' => 'large-text',
            ),
            '_builtin' => true,
        );
        $form['name-singular'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[labels][singular_name]',
            '#title' => __( 'Name singular', 'mncf' ) . ' (<strong>' . __( 'required', 'mncf' ) . '</strong>)',
            '#description' => '<strong>' . __( 'Enter in singular!', 'mncf' )
            . '</strong><br />'
            . '.',
            '#value' => isset( $this->ct['labels']['singular_name'] ) ? $this->ct['labels']['singular_name'] : '',
            '#validate' => array(
                'required' => array('value' => 'true'),
            ),
            '#pattern' => $table_row,
            '#inline' => true,
            '#id' => 'name-singular',
            '#attributes' => array(
                'placeholder' => __('Enter Post Type name singular', 'mncf' ),
                'class' => 'js-mncf-slugize-source large-text',
                'data-anonymous-post-type' => __( 'this Post Type', 'types' ),
            ),
            '_builtin' => true,
        );

        /**
         * IF isset $_POST['slug'] it means form is not submitted
         */
        $attributes = array();
        if ( !empty( $_POST['ct']['slug'] ) ) {
            $reserved = mncf_is_reserved_name( sanitize_text_field( $_POST['ct']['slug'] ), 'post_type' );
            if ( is_mn_error( $reserved ) ) {
                $attributes = array(
                    'class' => 'mncf-form-error',
                    'onclick' => 'jQuery(this).removeClass(\'mncf-form-error\');'
                );
            }
        }

        $form['slug'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[slug]',
            '#title' => __( 'Slug', 'mncf' ) . ' (<strong>' . __( 'required', 'mncf' ) . '</strong>)',
            '#value' => isset( $this->ct['slug'] ) ? $this->ct['slug'] : '',
            '#pattern' => $table_row,
            '#inline' => true,
            '#validate' => array(
                'required' => array('value' => 'true'),
                'nospecialchars' => array('value' => 'true'),
                'maxlength' => array('value' => '20'),
            ),
            '#attributes' => $attributes + array(
                'maxlength' => '20',
                'placeholder' => __('Enter Post Type slug', 'mncf' ),
                'class' => 'js-mncf-slugize large-text',
            ),
            '#id' => 'slug',
            '_builtin' => true,
        );

        // disable for inbuilt
        if ( $this->ct['_builtin'] ) {
            $form['slug']['#disable'] = 1;
            $form['slug']['#pattern'] = '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><DESCRIPTION><AFTER></td></tr>';
            $form['slug']['#description'] = __('This option is not available for built-in post types.', 'mncf');
        }

        $form['description'] = array(
            '#type' => 'textarea',
            '#name' => 'ct[description]',
            '#title' => __( 'Description', 'mncf' ),
            '#value' => isset( $this->ct['description'] ) ? $this->ct['description'] : '',
            '#attributes' => array(
                'rows' => 4,
                'cols' => 60,
                'placeholder' => __('Enter Post Type description', 'mncf' ),
                'class' => 'hidden js-mncf-description',
            ),
            '#pattern' => $table_row,
            '#inline' => true,
            '#after' => sprintf(
                '<a class="js-mncf-toggle-description hidden" href="#">%s</a>',
                __('Add description', 'mncf')
            ),
        );
        /**
         * icons only for version 3.8 up
         */
        global $mn_version;
        if ( version_compare( '3.8', $mn_version ) < 1 ) {
            $form['choose-icon'] = array(
                '#name' => 'choose-icon',
                '#type' => 'button',
                '#value' => esc_attr__('Change icon', 'mncf'),
                '#inline' => true,
                '#title' => __('Icon', 'mncf'),
                '#pattern' => '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><DESCRIPTION><AFTER></td></tr>',
                '#attributes' => array(
                    'data-mncf-nonce' => mn_create_nonce('post-type-dashicons-list'),
                    'data-mncf-post-type' => esc_attr($this->ct['slug']),
                    'data-mncf-message-loading' => esc_attr__('Please Wait, Loading…', 'mncf'),
                    'data-mncf-title' => esc_attr__('Choose icon', 'mncf'),
                    'data-mncf-cancel' => esc_attr__('Cancel', 'mncf'),
                    'data-mncf-value' => esc_attr($menu_icon),
                    'class' => 'js-mncf-choose-icon',
                ),
                '#before' => sprintf(
                    '<div class="mncf-types-menu-image dashicons-before dashicons-%s"><br></div>',
                    esc_attr($menu_icon)
                ),
            );
            /**
             * clear ability to change for builitin post types
             */
            if ( $this->ct['_builtin'] ) {
                $form['choose-icon']['#disable'] = 1;
                $form['choose-icon']['#description'] = __('This option is not available for built-in post types.', 'mncf');
            }
        }
        $form['table-1-close'] = array(
            '#type' => 'markup',
            '#markup' => '</tbody></table>',
            '_builtin' => true,
        );

        $form['box-1-close'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
            '_builtin' => true,
        );

        /**
         * return form if current_user_can edit
         */
        if ( $current_user_can_edit) {
            return $form;
        }

        return mncf_admin_common_only_show($form);
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     * @access (for functions: only use if private)
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description.
     * @return type Description.
     */
    function box_submitdiv()
    {
        $button_text = __( 'Save Post Type', 'mncf' );

        $form = array();

        $form['visibility-begin'] = array(
            '#type' => 'markup',
            '#markup' => ' <div class="misc-pub-section misc-pub-visibility" id="visibility">',
            '_builtin' => true,
        );

        $form['visibility-status'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '%s: <span id="post-visibility-display">%s</span>',
                __('Status', 'mncf'),
                (isset( $this->ct['public'] ) && strval( $this->ct['public'] ) == 'hidden') ? __('Draft', 'mncf'):__('Published', 'mncf')
            ),
            '_builtin' => true,
        );

        $form['visibility-choose-begin'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                ' <a href="#visibility" class="edit-visibility hide-if-no-js"><span aria-hidden="true">%s</span> <span class="screen-reader-text">%s</span></a>',
                __('Edit', 'mncf'),
                __('Edit status', 'mncf')
            ),
            '_builtin' => true,
        );

        $form['visibility-edit-begin'] = array(
            '#type' => 'markup',
            '#markup' => '<div id="post-visibility-select" class="hide-if-js">',
            '_builtin' => true,
        );

        $form['visibility-choose-public'] = array(
            '#type' => 'radios',
            '#name' => 'ct[public]',
            '#options' => array(
                sprintf(
                    '<span class="title">%s</span>',
                    __('Published', 'mncf')
                ) => 'public',
                sprintf(
                    '<span class="title">%s</span> <span class="description">(%s)</span>',
                    __('Draft', 'mncf'),
                    __('not visible in admin menus, no user-interface to administrate posts, not queryable on front-end', 'mncf')
                ) => 'hidden',
            ),
            '#default_value' => (isset( $this->ct['public'] ) && strval( $this->ct['public'] ) == 'hidden') ? 'hidden' : 'public',
            '#inline' => true,
        );

        $form['mncf-types-form-visiblity-toggle-open'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '<div id="mncf-types-form-visiblity-toggle" %s>',
                (isset( $this->ct['public'] ) && strval( $this->ct['public'] ) == 'hidden') ? ' class="hidden"' : ''
            ),
        );

        $menu_positions = array(
            'menu-dashboard'    => 2,
            'menu-posts'        => 5,
            'menu-media'        => 10,
            'menu-pages'        => 20,
            'menu-comments'     => 25,
            'menu-appearance'   => 60,
            'menu-plugins'      => 65,
            'menu-users'        => 70,
            'menu-tools'        => 75,
            'menu-settings'     => 80
        );

        $menu_position = 2;
        $options = array(
            __('--- not set ---') => ''
        );

        foreach( $GLOBALS['menu'] as $menu_item ) {
            // skip menu separators
            if( empty( $menu_item[0] ) || $menu_item[4] == 'mn-menu-separator' )
                continue;

            // update menu position
            if( array_key_exists( $menu_item[5], $menu_positions ) )
                $menu_position = $menu_positions[$menu_item[5]];

            $option_name = strip_tags( preg_replace( '#<([a-z]+).*?>.*?</\\1>#uis', '', $menu_item[0] ) );

            // don't show current cpt in list "Admin Menu position after:"
            if( 'edit.php?post_type=' . $this->ct['slug'] == $menu_item[2] )
                continue;

            // add menu item to options
            $options[$option_name] = $menu_position . '--mncf-add-menu-after--' . $menu_item[2];
        }

        $form['mncf-types-form-visiblity-toggle-close'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
        );

        $form['visibility-edit-end'] = array(
            '#type' => 'markup',
            '#markup' => '<p>
 <a href="#visibility" class="save-post-visibility hide-if-no-js button">OK</a>
 <a href="#visibility" class="cancel-post-visibility hide-if-no-js button-cancel">Cancel</a>
</p>
</div>',
            '_builtin' => true,
        );

        $form['visibility-end'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
            '_builtin' => true,
        );

        /**
         * admin menu position
         */
        $form['menu_position'] = array(
            '#type' => 'select',
            '#name' => 'ct[menu_position]',
            '#title' => __( 'Admin Menu position after: ', 'mncf' ),
            '#default_value' => isset( $this->ct['menu_position'] ) ? $this->ct['menu_position'] : '',
            // '#validate' => array('number' => array('value' => true)),
            '#inline' => true,
            '#pattern' => '<BEFORE><p><LABEL><ELEMENT><ERROR></p><AFTER>',
            '#options' => $options,
            '#before' => '<div class="misc-pub-section">',
            '#after' => '</div>',
            '#attributes' => array(
                'class' => 'js-mncf-menu-position-after widefat',
                'data-mncf-menu-position' => isset( $this->ct['menu_position'] ) ? $this->ct['menu_position'] : ''
            ),
        );
        /**
         * dashboard glance option to show counters on admin dashbord widget
         */
        if( $this->ct['slug'] != 'post' && $this->ct['slug'] != 'page' ) {
            $form['dashboard_glance'] = array(
                '#type' => 'checkbox',
                '#before' => '<div class="misc-pub-section">',
                '#after' => '</div>',
                '#name' => 'ct[dashboard_glance]',
                '#title' => __( 'Show number of entries on "At a Glance" admin widget.', 'mncf' ),
                '#default_value' => !empty( $this->ct['dashboard_glance'] ),
            );
        }

        $form = $this->submitdiv($button_text, $form);

        $form = mncf_form(__FUNCTION__, $form);
        echo $form->renderForm();
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     * @access (for functions: only use if private)
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description.
     * @return type Description.
     */
    public function box_options()
    {
        $form = array();
        $form['rewrite-enabled'] = array(
            '#type' => 'checkbox',
            '#title' => __( 'Rewrite', 'mncf' ),
            '#name' => 'ct[rewrite][enabled]',
            '#description' => __( 'Rewrite permalinks with this format. False to prevent rewrite. Default: true and use post type as slug.', 'mncf' ),
            '#default_value' => !empty( $this->ct['rewrite']['enabled'] ),
            '#inline' => true,
        );
        $form['rewrite-custom'] = array(
            '#type' => 'radios',
            '#name' => 'ct[rewrite][custom]',
            '#options' => array(
                __( 'Use the normal Mtaandao URL logic', 'mncf' ) => 'normal',
                __( 'Use a custom URL format', 'mncf' ) => 'custom',
            ),
            '#default_value' => empty( $this->ct['rewrite']['custom'] ) || $this->ct['rewrite']['custom'] != 'custom' ? 'normal' : 'custom',
            '#inline' => true,
            '#after' => '<br />',
        );
        $hidden = empty( $this->ct['rewrite']['custom'] ) || $this->ct['rewrite']['custom'] != 'custom' ? ' class="hidden"' : '';
        $form['rewrite-slug'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[rewrite][slug]',
            '#description' => __( 'Optional.', 'mncf' ) . ' ' . __( "Prepend posts with this slug - defaults to post type's name.", 'mncf' ),
            '#value' => isset( $this->ct['rewrite']['slug'] ) ? $this->ct['rewrite']['slug'] : '',
            '#inline' => true,
            '#before' => '<div id="mncf-types-form-rewrite-toggle"' . $hidden . '>',
            '#after' => '</div>',
            '#validate' => array('rewriteslug' => array('value' => 'true')),
            '#attributes' => array(
                'class' => 'widefat',
            ),
        );
        $form['rewrite-with_front'] = array(
            '#type' => 'checkbox',
            '#title' => __( 'Allow permalinks to be prepended with front base', 'mncf' ),
            '#name' => 'ct[rewrite][with_front]',
            '#description' => __( 'Example: if your permalink structure is /blog/, then your links will be: false->/news/, true->/blog/news/.', 'mncf' ) . ' ' . __( 'Defaults to true.', 'mncf' ),
            '#default_value' => !empty( $this->ct['rewrite']['with_front'] ),
            '#inline' => true,
        );
        $form['rewrite-feeds'] = array(
            '#type' => 'checkbox',
            '#name' => 'ct[rewrite][feeds]',
            '#title' => __( 'Feeds', 'mncf' ),
            '#description' => __( 'Defaults to has_archive value.', 'mncf' ),
            '#default_value' => !empty( $this->ct['rewrite']['feeds'] ),
            '#value' => 1,
            '#inline' => true,
        );
        $form['rewrite-pages'] = array(
            '#type' => 'checkbox',
            '#name' => 'ct[rewrite][pages]',
            '#title' => __( 'Pages', 'mncf' ),
            '#description' => __( 'Defaults to true.', 'mncf' ),
            '#default_value' => !empty( $this->ct['rewrite']['pages'] ),
            '#value' => 1,
            '#inline' => true,
        );
        $show_in_menu_page = isset( $this->ct['show_in_menu_page'] ) ? $this->ct['show_in_menu_page'] : '';
        $hidden = !empty( $this->ct['show_in_menu'] ) ? '' : ' class="hidden"';

        $has_archive_slug = isset( $this->ct['has_archive_slug'] ) ? $this->ct['has_archive_slug'] : '';
        $has_archive_slug_show = empty( $this->ct['has_archive'] )? ' class="hidden"':'';

        $form['vars'] = array(
            '#type' => 'checkboxes',
            '#name' => 'ct[vars]',
            '#inline' => true,
            '#options' => array(
                'has_archive' => array(
                    '#name' => 'ct[has_archive]',
                    '#default_value' => !empty( $this->ct['has_archive'] ),
                    '#title' => __( 'has_archive', 'mncf' ),
                    '#description' => __( 'Allow to have custom archive slug for CPT.', 'mncf' ) . '<br />' . __( 'Default: not set.', 'mncf' ),
                    '#inline' => true,
                    '#after' => '<div id="mncf-types-form-has_archive-toggle"' . $has_archive_slug_show . '><input type="text" name="ct[has_archive_slug]" class="regular-text" value="' . $has_archive_slug . '" /><div class="description mncf-form-description mncf-form-description-checkbox description-checkbox">' . __( 'Optional.', 'mncf' ) . ' ' . __( 'Default is value of rewrite or CPT slug.', 'mncf' ) . '</div></div>',
                ),
                'show_in_menu' => array(
                    '#name' => 'ct[show_in_menu]',
                    '#default_value' => !empty( $this->ct['show_in_menu'] ),
                    '#title' => __( 'show_in_menu', 'mncf' ),
                    '#description' => __( 'Whether to show the post type in the admin menu and where to show that menu. Note that show_ui must be true.', 'mncf' ) . '<br />' . __( 'Default: null.', 'mncf' ),
                    '#after' => '<div id="mncf-types-form-showinmenu-toggle"' . $hidden . '><input type="text" name="ct[show_in_menu_page]" class="regular-text" value="' . $show_in_menu_page . '" /><div class="description mncf-form-description mncf-form-description-checkbox description-checkbox">' . __( 'Optional.', 'mncf' ) . ' ' . __( "Top level page like 'tools.php' or 'edit.php?post_type=page'", 'mncf' ) . '</div></div>',
                    '#inline' => true,
                ),
                'show_ui' => array(
                    '#name' => 'ct[show_ui]',
                    '#default_value' => !empty( $this->ct['show_ui'] ),
                    '#title' => __( 'show_ui', 'mncf' ),
                    '#description' => __( 'Generate a default UI for managing this post type.', 'mncf' ) . '<br />' . __( 'Default: value of public argument.', 'mncf' ),
                    '#inline' => true,
                ),
                'publicly_queryable' => array(
                    '#name' => 'ct[publicly_queryable]',
                    '#default_value' => !empty( $this->ct['publicly_queryable'] ),
                    '#title' => __( 'publicly_queryable', 'mncf' ),
                    '#description' => __( 'Whether post_type queries can be performed from the front end.', 'mncf' ) . '<br />' . __( 'Default: value of public argument.', 'mncf' ),
                    '#inline' => true,
                ),
                'exclude_from_search' => array(
                    '#name' => 'ct[exclude_from_search]',
                    '#default_value' => !empty( $this->ct['exclude_from_search'] ),
                    '#title' => __( 'exclude_from_search', 'mncf' ),
                    '#description' => __( 'Whether to exclude posts with this post type from search results.', 'mncf' ) . '<br />' . __( 'Default: value of the opposite of the public argument.', 'mncf' ),
                    '#inline' => true,
                ),
                'hierarchical' => array(
                    '#name' => 'ct[hierarchical]',
                    '#default_value' => !empty( $this->ct['hierarchical'] ),
                    '#title' => __( 'hierarchical', 'mncf' ),
                    '#description' => __( 'Whether the post type is hierarchical. Allows Parent to be specified.', 'mncf' ) . '<br />' . __( 'Default: false.', 'mncf' ),
                    '#inline' => true,
                ),
                'can_export' => array(
                    '#name' => 'ct[can_export]',
                    '#default_value' => !empty( $this->ct['can_export'] ),
                    '#title' => __( 'can_export', 'mncf' ),
                    '#description' => __( 'Can this post_type be exported.', 'mncf' ) . '<br />' . __( 'Default: true.', 'mncf' ),
                    '#inline' => true,
                ),
                'show_in_nav_menus' => array(
                    '#name' => 'ct[show_in_nav_menus]',
                    '#default_value' => !empty( $this->ct['show_in_nav_menus'] ),
                    '#title' => __( 'show_in_nav_menus', 'mncf' ),
                    '#description' => __( 'Whether post_type is available for selection in navigation menus.', 'mncf' ) . '<br />' . __( 'Default: value of public argument.', 'mncf' ),
                    '#inline' => true,
                ),
            ),
        );
        $query_var = isset( $this->ct['query_var'] ) ? $this->ct['query_var'] : '';
        $hidden = !empty( $this->ct['query_var_enabled'] ) ? '' : ' class="hidden"';
        $form['query_var'] = array(
            '#type' => 'checkbox',
            '#name' => 'ct[query_var_enabled]',
            '#title' => 'query_var',
            '#description' => __( 'Disable to prevent queries like "mysite.com/?post_type=example". Enable to use queries like "mysite.com/?post_type=example". Enable and set a value to use queries like "mysite.com/?query_var_value=example"', 'mncf' ) . '<br />' . __( 'Default: true - set to $post_type.', 'mncf' ),
            '#default_value' => !empty( $this->ct['query_var_enabled'] ),
            '#after' => '<div id="mncf-types-form-queryvar-toggle"' . $hidden . '><input type="text" name="ct[query_var]" value="' . $query_var . '" class="regular-text" /><div class="description mncf-form-description mncf-form-description-checkbox description-checkbox">' . __( 'Optional', 'mncf' ) . '. ' . __( 'String to customize query var', 'mncf' ) . '</div></div>',
            '#inline' => true,
        );
        $form['permalink_epmask'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[permalink_epmask]',
            '#title' => __( 'Permalink epmask', 'mncf' ),
            '#description' => sprintf( __( 'Default value EP_PERMALINK. More info here %s.', 'mncf' ),
            '<a href="http://core.trac.mtaandao.org/ticket/12605" target="_blank">link</a>' ),
            '#value' => isset( $this->ct['permalink_epmask'] ) ? $this->ct['permalink_epmask'] : '',
            '#inline' => true,
        );

        $form['show_in_rest'] = array(
            '#type' => 'checkbox',
            '#name' => 'ct[show_in_rest]',
            '#default_value' => !empty( $this->ct['show_in_rest'] ),
            '#title' => __( 'show_in_rest', 'mncf' ),
            '#description' => __( 'Whether to expose this post type in the REST API.', 'mncf' ) . '<br />' . __( 'Default: false.', 'mncf' ),
            '#inline' => true,
        );

        $form['rest_base'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[rest_base]',
            '#title' => __( 'Rest Base', 'mncf' ),
            '#description' => __( 'The base slug that this post type will use when accessed using the REST API.', 'mncf' ) . '<br />' . __( 'Default: $post_type.', 'mncf' ),
            '#value' => isset( $this->ct['rest_base'] ) ? $this->ct['rest_base'] : '',
            '#inline' => true,
        );

        $form = mncf_form(__FUNCTION__, $form);
        echo $form->renderForm();
    }

    /**
     * post type properites
     */
    public function box_display_sections()
    {
        $form = array();
        $options = array(
            'title' => array(
                '#name' => 'ct[supports][title]',
                '#default_value' => !empty( $this->ct['supports']['title'] ),
                '#title' => __( 'Title', 'mncf' ),
                '#description' => __( 'Text input field to create a post title.', 'mncf' ),
                '#inline' => true,
                '#id' => 'mncf-supports-title',
            ),
            'editor' => array(
                '#name' => 'ct[supports][editor]',
                '#default_value' => !empty( $this->ct['supports']['editor'] ),
                '#title' => __( 'Editor', 'mncf' ),
                '#description' => __( 'Content input box for writing.', 'mncf' ),
                '#inline' => true,
                '#id' => 'mncf-supports-editor',
            ),
            'comments' => array(
                '#name' => 'ct[supports][comments]',
                '#default_value' => !empty( $this->ct['supports']['comments'] ),
                '#title' => __( 'Comments', 'mncf' ),
                '#description' => __( 'Ability to turn comments on/off.', 'mncf' ),
                '#inline' => true,
            ),
            'trackbacks' => array(
                '#name' => 'ct[supports][trackbacks]',
                '#default_value' => !empty( $this->ct['supports']['trackbacks'] ),
                '#title' => __( 'Trackbacks', 'mncf' ),
                '#description' => __( 'Ability to turn trackbacks and pingbacks on/off.', 'mncf' ),
                '#inline' => true,
            ),
            'revisions' => array(
                '#name' => 'ct[supports][revisions]',
                '#default_value' => !empty( $this->ct['supports']['revisions'] ),
                '#title' => __( 'Revisions', 'mncf' ),
                '#description' => __( 'Allows revisions to be made of your post.', 'mncf' ),
                '#inline' => true,
            ),
            'author' => array(
                '#name' => 'ct[supports][author]',
                '#default_value' => !empty( $this->ct['supports']['author'] ),
                '#title' => __( 'Author', 'mncf' ),
                '#description' => __( 'Displays a dropdown menu for changing the post author.', 'mncf' ),
                '#inline' => true,
            ),
            'excerpt' => array(
                '#name' => 'ct[supports][excerpt]',
                '#default_value' => !empty( $this->ct['supports']['excerpt'] ),
                '#title' => __( 'Excerpt', 'mncf' ),
                '#description' => __( 'A text area for writing a custom excerpt.', 'mncf' ),
                '#inline' => true,
            ),
            'thumbnail' => array(
                '#name' => 'ct[supports][thumbnail]',
                '#default_value' => !empty( $this->ct['supports']['thumbnail'] ),
                '#title' => __( 'Thumbnail', 'mncf' ),
                '#description' => __( "Allows to upload a 'featured image' to the post (a.k.a. 'thumbnail').", 'mncf' ),
                '#inline' => true,
            ),
            'custom-fields' => array(
                '#name' => 'ct[supports][custom-fields]',
                '#default_value' => !empty( $this->ct['supports']['custom-fields'] ),
                '#title' => __( 'Custom Fields', 'mncf' ),
                '#description' => __( "The native Mtaandao custom post fields list. If you don't select this, Types post fields will still display.", 'mncf' ),
                '#inline' => true,
            ),
            'page-attributes' => array(
                '#name' => 'ct[supports][page-attributes]',
                '#default_value' => !empty( $this->ct['supports']['page-attributes'] ),
                '#title' => __( 'Page Attributes', 'mncf' ),
                '#description' => __( 'Menu order and page parent (only available for hierarchical posts).', 'mncf' ),
                '#inline' => true,
            ),
            'post-formats' => array(
                '#name' => 'ct[supports][post-formats]',
                '#default_value' => !empty( $this->ct['supports']['post-formats'] ),
                '#title' => __( 'Post Formats', 'mncf' ),
                '#description' => __( 'A selector for the format to use for the post.', 'mncf' ),
                '#inline' => true,
            ),
        );
        $form['supports'] = array(
            '#type' => 'checkboxes',
            '#options' => $options,
            '#name' => 'ct[supports]',
            '#inline' => true,
        );
        $form = mncf_form(__FUNCTION__, $form);
        echo $form->renderForm();
    }

    /**
     * Labels
     */
    public function box_labels()
    {
        $form = array();
        $labels = array(
            'add_new' => array(
                'title' => __( 'Add New', 'mncf' ),
                'description' => __( 'The add new text. The default is Add New for both hierarchical and non-hierarchical types.', 'mncf' ),
                'label' => __('Add New', 'mncf'),
            ),
            'add_new_item' => array(
                'title' => __( 'Add New %s', 'mncf' ),
                'description' => __( 'The add new item text. Default is Add New Post/Add New Page.', 'mncf' ),
                'label' => __('Add New Item', 'mncf'),
            ),
            'edit_item' => array(
                'title' => __( 'Edit %s', 'mncf' ),
                'description' => __( 'The edit item text. Default is Edit Post/Edit Page.', 'mncf' ),
                'label' => __('Edit Item', 'mncf'),
            ),
            'new_item' => array(
                'title' => __( 'New %s', 'mncf' ),
                'description' => __( 'The new item text. Default is New Post/New Page.', 'mncf' ),
                'label' => __('New Item', 'mncf'),
            ),
            'view_item' => array(
                'title' => __( 'View %s', 'mncf' ),
                'description' => __( 'The view item text. Default is View Post/View Page.', 'mncf' ),
                'label' => __('View Item', 'mncf'),
            ),
            'search_items' => array(
                'title' => __( 'Search %s', 'mncf' ),
                'description' => __( 'The search items text. Default is Search Posts/Search Pages.', 'mncf' ),
                'label' => __('Search Items', 'mncf'),
            ),
            'not_found' => array(
                'title' => __( 'No %s found', 'mncf' ),
                'description' => __( 'The not found text. Default is No posts found/No pages found.', 'mncf' ),
                'label' => __('Not Found', 'mncf'),
            ),
            'not_found_in_trash' => array(
                'title' => __( 'No %s found in Trash', 'mncf' ),
                'description' => __( 'The not found in trash text. Default is No posts found in Trash/No pages found in Trash.', 'mncf' ),
                'label' => __('Not Found In Trash', 'mncf'),
            ),
            'parent_item_colon' => array(
                'title' => __( 'Parent text', 'mncf' ),
                'description' => __( "The parent text. This string isn't used on non-hierarchical types. In hierarchical ones the default is Parent Page.", 'mncf' ),
                'label' => __('Parent Description', 'mncf'),
            ),
            'all_items' => array(
                'title' => __( 'All items', 'mncf' ),
                'description' => __( 'The all items text used in the menu. Default is the Name label.', 'mncf' ),
                'label' => __('All Items', 'mncf'),
            ),
            'enter_title_here' => array(
                'title' => __( 'Enter title here', 'mncf' ),
                'description' => __( 'The text used as placeholder of post title. Default is the "Enter title here".', 'mncf' ),
                'label' => __('Enter title here', 'mncf'),
                'default_value' => __('Enter title here', 'mncf'),
                'force_if_empty' => true,
            ),
        );
        $form['table-open'] = array(
            '#type' => 'markup',
            '#markup' => '<table class="mncf-types-form-table widefat striped fixed"><tbody>',
            '_builtin' => true,
        );
        foreach ( $labels as $name => $data ) {
            /**
             * get value
             */
            $value = empty($this->ct['slug'])? $data['title']:(isset( $this->ct['labels'][$name] ) ? $this->ct['labels'][$name] : '');
            /**
             * force if empty
             */
            if (
                true
                && empty($value)
                && isset($data['force_if_empty'])
                && isset($data['default_value'])
                && $data['force_if_empty']
            ) {
                $value = $data['default_value'];
            }
            $form['labels-' . $name] = array(
                '#type' => 'textfield',
                '#name' => 'ct[labels][' . $name . ']',
                '#title' => $data['label'],
                '#description' => $data['description'],
                '#value' => $value,
                '#inline' => true,
                '#pattern' => '<tr><td><LABEL></td><td><ELEMENT><DESCRIPTION></td></tr>',
                '#attributes' => array(
                    'class' => 'widefat',
                ),
            );
        }
        $form['table-close'] = array(
            '#type' => 'markup',
            '#markup' => '</tbody></table>',
            '_builtin' => true,
        );
        $form = mncf_form(__FUNCTION__, $form);
        echo $form->renderForm();
    }

    /**
     * Taxonomies
     */
    public function box_taxonomies()
    {
        $form = array();
        $taxonomies = get_taxonomies( '', 'objects' );
        $options = array();

        foreach ( $taxonomies as $category_slug => $category ) {
            if (
                false
                || $category_slug == 'nav_menu'
                || $category_slug == 'link_category'
                || $category_slug == 'post_format'
            ) {
                continue;
            }
            $options[$category_slug] = array(
                '#name' => 'ct[taxonomies][' . $category_slug . ']',
                '#title' => $category->labels->name,
                '#default_value' => !empty( $this->ct['taxonomies'][$category_slug] ),
                '#inline' => true,
                '#before' => '<li>',
                '#after' => '</li>',
            );
            $options[$category_slug]['_builtin'] = $category->_builtin;
            /* if ( $this->ct['_builtin'] && $category->_builtin ) {
                $options[$category_slug]['#attributes'] = array(
                    'disabled' => 'disabled',
                );
            } */
        }

        $form['taxonomies'] = array(
            '#type' => 'checkboxes',
            '#options' => $options,
            '#name' => 'ct[taxonomies]',
            '#inline' => true,
            '#before' => '<ul class="mncf-list">',
            '#after' => '</ul>',
            '_builtin' => true,
        );
        $form = mncf_form(__FUNCTION__, $form);
        echo $form->renderForm();
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     * @access (for functions: only use if private)
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description.
     * @return type Description.
     */
    private function save()
    {
        global $mncf;

        if ( !isset( $_POST['ct'] ) ) {
            return false;
        }
        $data = $_POST['ct'];
        $update = false;

        // Sanitize data
        $data['labels']['name'] = isset( $data['labels']['name'] )
            ? sanitize_text_field( $data['labels']['name'] )
            : '';

        $data['labels']['singular_name'] = isset( $data['labels']['singular_name'] )
            ? sanitize_text_field( $data['labels']['singular_name'] )
            : '';

        if (
            empty( $data['labels']['name'] )
            || empty( $data['labels']['singular_name'] )
        ) {
            mncf_admin_message( __( 'Please set post type name', 'mncf' ), 'error' );
            return false;
        }

        if ( isset( $data[$this->get_id] ) ) {
            $update = true;
            $data[$this->get_id] = sanitize_title( $data[$this->get_id] );
        } else {
            $data[$this->get_id] = null;
        }
        if ( isset( $data['slug'] ) ) {
            $data['slug'] = sanitize_title( $data['slug'] );
        } elseif(
            $_GET['mncf-post-type'] == 'post'
            || $_GET['mncf-post-type'] == 'page'
            || $_GET['mncf-post-type'] == 'attachment'
        ) {
            $data['slug'] = sanitize_text_field( $_GET['mncf-post-type'] );
        } else {
            $data['slug'] = null;
        }
        if ( isset( $data['rewrite']['slug'] ) ) {
            $data['rewrite']['slug'] = remove_accents( $data['rewrite']['slug'] );
            $data['rewrite']['slug'] = strtolower( $data['rewrite']['slug'] );
            $data['rewrite']['slug'] = trim( $data['rewrite']['slug'] );
        }
        $data['_builtin'] = false;

        // Set post type name
        $post_type = null;
        if ( !empty( $data['slug'] ) ) {
            $post_type = $data['slug'];
        } elseif ( !empty( $data[$this->get_id] ) ) {
            $post_type = $data[$this->get_id];
        } elseif ( !empty( $data['labels']['singular_name'] ) ) {
            $post_type = sanitize_title( $data['labels']['singular_name'] );
        }

        if ( empty( $post_type ) ) {
            mncf_admin_message( __( 'Please set post type name', 'mncf' ), 'error' );
            return false;
        }

        $data['slug'] = $post_type;
        $custom_types = get_option( MNCF_OPTION_NAME_CUSTOM_TYPES, array() );
        $protected_data_check = array();

        if ( mncf_is_builtin_post_types($data['slug']) ) {
            $data['_builtin'] = true;
            $update = true;
        } else {
            // Check reserved name
            $reserved = mncf_is_reserved_name( $post_type, 'post_type' );
            if ( is_mn_error( $reserved ) ) {
                mncf_admin_message( $reserved->get_error_message(), 'error' );
                return false;
            }

            // Check overwriting
            if ( ( !array_key_exists( $this->get_id, $data ) || $data[$this->get_id] != $post_type ) && array_key_exists( $post_type, $custom_types ) ) {
                mncf_admin_message( __( 'Post Type already exists', 'mncf' ), 'error' );
                return false;
            }

            /*
             * Since Types 1.2
             * We do not allow plural and singular names to be same.
             */
            if ( $mncf->post_types->check_singular_plural_match( $data ) ) {
                mncf_admin_message( $mncf->post_types->message( 'warning_singular_plural_match' ), 'error' );
                return false;
            }

            // Check if renaming then rename all post entries and delete old type
            if ( !empty( $data[$this->get_id] )
                && $data[$this->get_id] != $post_type ) {
                    global $mndb;
                    $mndb->update( $mndb->posts, array('post_type' => $post_type),
                        array('post_type' => $data[$this->get_id]), array('%s'),
                        array('%s')
                    );

                    /**
                     * update post meta "_mn_types_group_post_types"
                     */
                    $sql = $mndb->prepare(
                        sprintf(
                            'select meta_id, meta_value from %s where meta_key = %%s',
                            $mndb->postmeta
                        ),
                        '_mn_types_group_post_types'
                    );
                    $all_meta = $mndb->get_results($sql, OBJECT_K);
                    $re = sprintf( '/,%s,/', $data[$this->get_id] );
                    foreach( $all_meta as $meta ) {
                        if ( !preg_match( $re, $meta->meta_value ) ) {
                            continue;
                        }
                        $mndb->update(
                            $mndb->postmeta,
                            array(
                                'meta_value' => preg_replace( $re, ','.$post_type.',', $meta->meta_value ),
                            ),
                            array(
                                'meta_id' => $meta->meta_id,
                            ),
                            array( '%s' ),
                            array( '%d' )
                        );
                    }

                    /**
                     * update _mncf_belongs_{$data[$this->get_id]}_id
                     */
                    $mndb->update(
                        $mndb->postmeta,
                        array(
                            'meta_key' => sprintf( '_mncf_belongs_%s_id', $post_type ),
                        ),
                        array(
                            'meta_key' => sprintf( '_mncf_belongs_%s_id', $data[$this->get_id] ),
                        ),
                        array( '%s' ),
                        array( '%s' )
                    );

                    /**
                     * update options "mnv_options"
                     */
                    $mnv_options = get_option( 'mnv_options', true );
                    if ( is_array( $mnv_options ) ) {
                        $re = sprintf( '/(views_template_(archive_)?for_)%s/', $data[$this->get_id] );
                        foreach( $mnv_options as $key => $value ) {
                            if ( !preg_match( $re, $key ) ) {
                                continue;
                            }
                            unset($mnv_options[$key]);
                            $key = preg_replace( $re, "$1".$post_type, $key );
                            $mnv_options[$key] = $value;
                        }
                        update_option( 'mnv_options', $mnv_options );
                    }

                    /**
                     * update option "mncf-custom-taxonomies"
                     */
                    $mncf_custom_taxonomies = get_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, true );
                    if ( is_array( $mncf_custom_taxonomies ) ) {
                        $update_mncf_custom_taxonomies = false;
                        foreach( $mncf_custom_taxonomies as $key => $value ) {
                            if ( array_key_exists( 'supports', $value ) && array_key_exists( $data[$this->get_id], $value['supports'] ) ) {
                                unset( $mncf_custom_taxonomies[$key]['supports'][$data[$this->get_id]] );
                                $update_mncf_custom_taxonomies = true;
                            }
                        }
                        if ( $update_mncf_custom_taxonomies ) {
                            update_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, $mncf_custom_taxonomies );
                        }
                    }

                    // Sync action
                    do_action( 'mncf_post_type_renamed', $post_type, $data[$this->get_id] );

                    // Set protected data
                    $protected_data_check = $custom_types[$data[$this->get_id]];
                    // Delete old type
                    unset( $custom_types[$data[$this->get_id]] );
                    $data[$this->get_id] = $post_type;
                } else {
                    // Set protected data
                    $protected_data_check = !empty( $custom_types[$post_type] ) ? $custom_types[$post_type] : array();
                }

            // Check if active
            if ( isset( $custom_types[$post_type]['disabled'] ) ) {
                $data['disabled'] = $custom_types[$post_type]['disabled'];
            }
        }

        // Sync taxes with custom taxes
        $taxes = get_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );

        foreach ( $taxes as $id => $tax ) {
            if ( isset( $data['taxonomies'] ) && !empty( $data['taxonomies'] ) && array_key_exists( $id, $data['taxonomies'] ) ) {
                $taxes[$id]['supports'][$data['slug']] = 1;
            } else {
                if( isset( $taxes[$id]['supports'][$data['slug']] ) )
                    unset( $taxes[$id]['supports'][$data['slug']] );
            }
        }

        update_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, $taxes );

        // Preserve protected data
        foreach ( $protected_data_check as $key => $value ) {
            if ( strpos( $key, '_' ) !== 0 ) {
                unset( $protected_data_check[$key] );
            }
        }

        /**
         * save custom field group
         */
        /* removed types-608
        $post_to_groups = isset($_POST['ct']['custom-field-group'])?$_POST['ct']['custom-field-group']:array();
        $groups = $this->fields->get_groups_with_post_types();
        foreach( $groups as $group) {
            $post_types_to_save = $group['_mn_types_group_post_types'];
            // save
            if ( array_key_exists($group['id'], $post_to_groups)) {
                $post_types_to_save[] = $data['slug'];
            } else {
                if(($key = array_search($data['slug'], $post_types_to_save)) !== false) {
                    unset($post_types_to_save[$key]);
                }
                if (
                    false
                    || empty($post_types_to_save)
                    || (
                        true
                        && 1 == sizeof($post_types_to_save)
                        && 'all' == current($post_types_to_save)
                    )
                ) {
                    $post_types_to_save = array();
                    foreach( get_post_types() as $key => $value ) {
                        if ( $data['slug'] == $value) {
                            continue;
                        }
                        if ( in_array($value, $mncf->excluded_post_types) ) {
                            continue;
                        }
                        $post_types_to_save[] = $value;
                    }
                }
            }
            mncf_admin_fields_save_group_post_types($group['id'], $post_types_to_save);
        }
        */

        /**
         * set last edit time
         */
        $data[TOOLSET_EDIT_LAST] = time();

        /**
         * set last edit author
         */

        $data[MNCF_AUTHOR] = get_current_user_id();

        /**
         * add builid in
         */
        if ( $data['_builtin'] && !isset( $protected_data_check[$data['slug']])) {
            $protected_data_check[$data['slug']] = array();
        }

        // Merging protected data
        $custom_types[$post_type] = array_merge( $protected_data_check, $data );

        update_option( MNCF_OPTION_NAME_CUSTOM_TYPES, $custom_types );

        // MNML register strings
        if ( !$data['_builtin'] ) {
            mncf_custom_types_register_translation( $post_type, $data );
        }

        // success message
        $msg = $update
            ? __( 'Post Type saved.', 'mncf' )
            : __( 'New Post Type created.', 'mncf' );

        mncf_admin_message_store(
            $msg,
            'updated notice notice-success is-dismissible'
        );

	    flush_rewrite_rules();

        if ( !$data['_builtin'] ) {
            do_action( 'mncf_custom_types_save', $data );
        }

        // Redirect
        mn_safe_redirect(
            esc_url_raw(
                add_query_arg(
                    array(
                        'page' => 'mncf-edit-type',
                        $this->get_id => $post_type,
                        'mncf-message' => 'view',
	                    // Flush rewrite rules after reload
	                    'flush' => '1'
                    ),
                    admin_url( 'admin.php' )
                )
            )
        );
        die();
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     * @access (for functions: only use if private)
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description.
     * @return type Description.
     */
    public function closedpostboxes( $screen_base )
    {
        if ( 'toolset_page_mncf-edit-type' != $screen_base ) {
            return;
        }
        $option_name = sprintf('closedpostboxes_%s', $screen_base);
        $closedpostboxes = get_user_meta(get_current_user_id(), $option_name);
        if ( !empty($closedpostboxes) ) {
            return;
        }
        $closedpostboxes[] = 'types_labels';
        $closedpostboxes[] = 'types_options';
        update_user_option( get_current_user_id(), $option_name, $closedpostboxes, true);
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     * @access (for functions: only use if private)
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description.
     * @return type Description.
     */
    public function prepare_field_select_screen()
    {
        /**
         * check nonce
         */
        if (
            0
            || !isset($_REQUEST['_mnnonce'])
            || !isset($_REQUEST['parent'])
            || !isset($_REQUEST['child'])
            || !mn_verify_nonce($_REQUEST['_mnnonce'], $this->get_nonce('child-post-fields', $_REQUEST['parent'], $_REQUEST['child']))
        ) {
            $this->verification_failed_and_die();
        }
        $parent = $_REQUEST['parent'];
        $child = $_REQUEST['child'];

        $post_type_parent = get_post_type_object( $parent );
        $post_type_child = get_post_type_object( $child );

        if ( null == $post_type_parent || null == $post_type_child ) {
            die( __( 'Wrong post types', 'mncf' ) );
        }
        $relationships = get_option( 'mncf_post_relationship', array() );
        if ( !isset( $relationships[$parent][$child] ) ) {
            $this->print_notice_and_die(
                __( 'Please save Post Type first to edit these fields.', 'mncf' )
            );
        }
        $repetitive_warning_markup = array();
        $data = $relationships[$parent][$child];

        $form = array();
        $form['repetitive_warning_markup'] = $repetitive_warning_markup;
        $form['select'] = array(
            '#type' => 'radios',
            '#name' => 'fields_setting',
            '#options' => array(
                __( 'Title, all custom fields and parents', 'mncf' ) => 'all_cf',
                __( 'Do not show management options for this post type', 'mncf' ) => 'only_list',
                __( 'All fields, including the standard post fields', 'mncf' ) => 'all_cf_standard',
                __( 'Specific fields', 'mncf' ) => 'specific',
            ),
            '#attributes' => array(
                'display' => 'ul',
            ),
            '#default_value' => empty( $data['fields_setting'] ) ? 'all_cf' : $data['fields_setting'],
        );
        /**
         * check default, to avoid missing configuration
         */
        if ( !in_array($form['select']['#default_value'], $form['select']['#options']) ) {
            $form['select']['#default_value'] = 'all_cf';
        }
        /**
         * Specific options
         */
        $groups = mncf_admin_get_groups_by_post_type( $child );
        $options_cf = array();
        $repetitive_warning = false;
        $repetitive_warning_txt = __( 'Repeating fields should not be used in child posts. Types will update all field values.', 'mncf' );
        foreach ( $groups as $group ) {
            $fields = mncf_admin_fields_get_fields_by_group( $group['id'] );
            foreach ( $fields as $key => $cf ) {
                $__key = mncf_types_cf_under_control( 'check_outsider', $key ) ? $key : MNCF_META_PREFIX . $key;
                $options_cf[$__key] = array(
                    '#title' => $cf['name'],
                    '#name' => 'fields[' . $__key . ']',
                    '#default_value' => isset( $data['fields'][$__key] ) ? 1 : 0,
                    '#inline' => true,
                    '#before' => '<li>',
                    '#after' => '</li>',
                );
                // Repetitive warning
                if ( mncf_admin_is_repetitive( $cf ) ) {
                    if ( !$repetitive_warning ) {
                        $repetitive_warning_markup = array(
                            '#type' => 'markup',
                            '#markup' => '<div class="message error" style="display:none;" id="mncf-repetitive-warning"><p>' . $repetitive_warning_txt . '</p></div>',
                        );
                    }
                    $repetitive_warning = true;
                    $options_cf[$__key]['#after'] = !isset( $data['fields'][$__key] ) ? '<div class="message error" style="display:none;"><p>' : '<div class="message error"><p>';
                    $options_cf[$__key]['#after'] .= $repetitive_warning_txt;
                    $options_cf[$__key]['#after'] .= '</p></div></li>';
                    $options_cf[$__key]['#attributes'] = array(
                        'onclick' => 'jQuery(this).parent().find(\'.message\').toggle();',
                        'disabled' => 'disabled',
                    );
                }
            }
        }

        /**
         * build options for "Specific fields"
         */
        $options = array();
        /**
         * check and add built-in properites
         */
        require_once MNCF_INC_ABSPATH . '/post-relationship.php';
        $supports= mncf_post_relationship_get_supported_fields_by_post_type($child);
        foreach ( $supports as $child_field_key => $child_field_data ) {
            $options[$child_field_data['name']] = array(
                '#title' => $child_field_data['title'],
                '#name' => sprintf('fields[%s]', $child_field_data['name']),
                '#default_value' => isset( $data['fields'][$child_field_data['name']] ) ? 1 : 0,
                '#inline' => true,
                '#before' => '<li>',
                '#after' => '</li>',
            );
        }

        /**
         * add custom fields
         */
        $options = $options + $options_cf;
        $temp_belongs = mncf_pr_admin_get_belongs( $child );
        foreach ( $temp_belongs as $temp_parent => $temp_data ) {
            if ( $temp_parent == $parent ) {
                continue;
            }
            $temp_parent_type = get_post_type_object( $temp_parent );
            $options[$temp_parent] = array(
                '#title' => $temp_parent_type->label,
                '#name' => 'fields[_mncf_pr_parents][' . $temp_parent . ']',
                '#default_value' => isset( $data['fields']['_mncf_pr_parents'][$temp_parent] ) ? 1 : 0,
                '#inline' => true,
                '#before' => '<li>',
                '#after' => '</li>',
            );
        }
        /**
         * remove "Specific fields" if there is no fields
         */
        if ( empty($options) ) {
            unset($form['select']['#options'][__('Specific fields', 'mncf')]);
            if ('specific' == $form['select']['#default_value']) {
                $form['select']['#default_value'] = 'all_cf';
            }
        }

        // Taxonomies
        $taxonomies = get_object_taxonomies( $post_type_child->name, 'objects' );
        if ( !empty( $taxonomies ) ) {
            foreach ( $taxonomies as $tax_id => $taxonomy ) {
                $options[$tax_id] = array(
                    '#title' => sprintf( __('Taxonomy - %s', 'mncf'), $taxonomy->label ),
                    '#name' => 'fields[_mncf_pr_taxonomies][' . $tax_id . ']',
                    '#default_value' => isset( $data['fields']['_mncf_pr_taxonomies'][$tax_id] ) ? 1 : 0,
                    '#inline' => true,
                    '#before' => '<li>',
                    '#after' => '</li>',
                );
            }
        }

        $form['specific'] = array(
            '#type' => 'checkboxes',
            '#name' => 'fields',
            '#options' => $options,
            '#default_value' => isset( $data['fields'] ),
            '#before' => sprintf(
                '<ul id="mncf-specific" class="%s">',
                'specific' == $form['select']['#default_value']? '':'hidden'
            ),
            '#after' => '</ul>',
        );
        $form['nonce'] = array(
            '#type' => 'hidden',
            '#value' => mn_create_nonce($this->get_nonce('child-post-fields-save', $parent, $child)),
            '#name' => 'mncf-fields-save-nonce',
            '#id' => 'mncf-fields-save-nonce',
        );
        $form['parent'] = array(
            '#type' => 'hidden',
            '#value' => esc_attr($parent),
            '#name' => 'mncf-parent',
            '#id' => 'mncf-parent',
        );
        $form['child'] = array(
            '#type' => 'hidden',
            '#value' => esc_attr($child),
            '#name' => 'mncf-child',
            '#id' => 'mncf-child',
        );
        echo mncf_form_simple( $form );
        die;
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     * @access (for functions: only use if private)
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description.
     * @return type Description.
     */
    public function save_child_fields()
    {
        /**
         * check nonce
         */
        if (
            0
            || !isset($_REQUEST['_mnnonce'])
            || !isset($_REQUEST['current'])
            || !isset($_REQUEST['parent'])
            || !isset($_REQUEST['child'])
            || !mn_verify_nonce($_REQUEST['_mnnonce'], $this->get_nonce('child-post-fields-save', $_REQUEST['parent'], $_REQUEST['child']))
        ) {
            $this->verification_failed_and_die();
        }
        $parent = $_REQUEST['parent'];
        $child = $_REQUEST['child'];
        $fields = array();
        parse_str($_REQUEST['current'], $fields);

        $relationships = get_option( 'mncf_post_relationship', array() );
        $relationships[$parent][$child]['fields_setting'] = sanitize_text_field( $fields['fields_setting'] );
        /**
         * sanitize
         */
        require_once MNCF_INC_ABSPATH . '/post-relationship.php';
        $relationships[$parent][$child]['fields'] = array();
        if (  isset( $fields['fields'] ) && is_array($fields['fields'])) {
            $allowed_keys = mncf_post_relationship_get_specific_fields_keys($child);
            foreach( $fields['fields'] as $key => $value ) {

                // other parent cpts
                if ( '_mncf_pr_parents' == $key ) {
                    $relationships[$parent][$child]['fields'][$key] = array();
                    foreach( array_keys($value) as $parents) {
                        $relationships[$parent][$child]['fields'][$key][$parents] = 1;
                    }
                }

                /**
                 * sanitize Taxonomy
                 */
                if ( '_mncf_pr_taxonomies' == $key ) {
                    if ( is_array($value) ) {
                        $relationships[$parent][$child]['fields'][$key] = array();
                        foreach( array_keys($value) as $taxonomy) {
                            $taxonomy = get_taxonomy($taxonomy);
                            if ( is_object($taxonomy) ) {
                                $relationships[$parent][$child]['fields'][$key][$taxonomy->name] = 1;
                            }
                        }
                    }
                    continue;
                }
                if ( array_key_exists( $key, $allowed_keys) ) {
                    $relationships[$parent][$child]['fields'][$key] = 1;
                }
            }
        }
        update_option( 'mncf_post_relationship', $relationships );
        die;
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     * @access (for functions: only use if private)
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description.
     * @return type Description.
     */
    public function get_icons_list()
    {
        /**
         * check nonce
         */
        if (
            0
            || !isset($_REQUEST['_mnnonce'])
            || !mn_verify_nonce($_REQUEST['_mnnonce'], $this->get_nonce('post-type-dashicons-list'))
        ) {
            $this->verification_failed_and_die();
        }
        $icons = array(
            'admin-appearance' => __('appearance', 'wcpf'),
            'admin-collapse' => __('collapse', 'wcpf'),
            'admin-comments' => __('comments', 'wcpf'),
            'admin-generic' => __('generic', 'wcpf'),
            'admin-home' => __('home', 'wcpf'),
            'admin-links' => __('links', 'wcpf'),
            'admin-media' => __('media', 'wcpf'),
            'admin-network' => __('network', 'wcpf'),
            'admin-page' => __('page', 'wcpf'),
            'admin-plugins' => __('plugins', 'wcpf'),
            'admin-post' => __('post', 'wcpf'),
            'admin-settings' => __('settings', 'wcpf'),
            'admin-site' => __('site', 'wcpf'),
            'admin-tools' => __('tools', 'wcpf'),
            'admin-users' => __('users', 'wcpf'),
            'album' => __('album', 'wcpf'),
            'align-center' => __('align center', 'wcpf'),
            'align-left' => __('align left', 'wcpf'),
            'align-none' => __('align none', 'wcpf'),
            'align-right' => __('align right', 'wcpf'),
            'analytics' => __('analytics', 'wcpf'),
            'archive' => __('archive', 'wcpf'),
            'arrow-down-alt2' => __('down alt2', 'wcpf'),
            'arrow-down-alt' => __('down alt', 'wcpf'),
            'arrow-down' => __('down', 'wcpf'),
            'arrow-left-alt2' => __('left alt2', 'wcpf'),
            'arrow-left-alt' => __('left alt', 'wcpf'),
            'arrow-left' => __('left', 'wcpf'),
            'arrow-right-alt2' => __('right alt2', 'wcpf'),
            'arrow-right-alt' => __('right alt', 'wcpf'),
            'arrow-right' => __('right', 'wcpf'),
            'arrow-up-alt2' => __('up alt2', 'wcpf'),
            'arrow-up-alt' => __('up alt', 'wcpf'),
            'arrow-up' => __('up', 'wcpf'),
            'art' => __('art', 'wcpf'),
            'awards' => __('awards', 'wcpf'),
            'backup' => __('backup', 'wcpf'),
            'book-alt' => __('book alt', 'wcpf'),
            'book' => __('book', 'wcpf'),
            'building' => __('building', 'wcpf'),
            'businessman' => __('businessman', 'wcpf'),
            'calendar-alt' => __('calendar alt', 'wcpf'),
            'calendar' => __('calendar', 'wcpf'),
            'camera' => __('camera', 'wcpf'),
            'carrot' => __('carrot', 'wcpf'),
            'cart' => __('cart', 'wcpf'),
            'category' => __('category', 'wcpf'),
            'chart-area' => __('chart area', 'wcpf'),
            'chart-bar' => __('chart bar', 'wcpf'),
            'chart-line' => __('chart line', 'wcpf'),
            'chart-pie' => __('chart pie', 'wcpf'),
            'clipboard' => __('clipboard', 'wcpf'),
            'clock' => __('clock', 'wcpf'),
            'cloud' => __('cloud', 'wcpf'),
            'controls-back' => __('back', 'wcpf'),
            'controls-forward' => __('forward', 'wcpf'),
            'controls-pause' => __('pause', 'wcpf'),
            'controls-play' => __('play', 'wcpf'),
            'controls-repeat' => __('repeat', 'wcpf'),
            'controls-skipback' => __('skip back', 'wcpf'),
            'controls-skipforward' => __('skip forward', 'wcpf'),
            'controls-volumeoff' => __('volume off', 'wcpf'),
            'controls-volumeon' => __('volume on', 'wcpf'),
            'dashboard' => __('dashboard', 'wcpf'),
            'desktop' => __('desktop', 'wcpf'),
            'dismiss' => __('dismiss', 'wcpf'),
            'download' => __('download', 'wcpf'),
            'editor-aligncenter' => __('align center', 'wcpf'),
            'editor-alignleft' => __('align left', 'wcpf'),
            'editor-alignright' => __('align right', 'wcpf'),
            'editor-bold' => __('bold', 'wcpf'),
            'editor-break' => __('break', 'wcpf'),
            'editor-code' => __('code', 'wcpf'),
            'editor-contract' => __('contract', 'wcpf'),
            'editor-customchar' => __('custom char', 'wcpf'),
            'editor-distractionfree' => __('distraction free', 'wcpf'),
            'editor-expand' => __('expand', 'wcpf'),
            'editor-help' => __('help', 'wcpf'),
            'editor-indent' => __('indent', 'wcpf'),
            'editor-insertmore' => __('insert more', 'wcpf'),
            'editor-italic' => __('italic', 'wcpf'),
            'editor-justify' => __('justify', 'wcpf'),
            'editor-kitchensink' => __('kitchen sink', 'wcpf'),
            'editor-ol' => __('ol', 'wcpf'),
            'editor-outdent' => __('outdent', 'wcpf'),
            'editor-paragraph' => __('paragraph', 'wcpf'),
            'editor-paste-text' => __('paste text', 'wcpf'),
            'editor-paste-word' => __('paste word', 'wcpf'),
            'editor-quote' => __('quote', 'wcpf'),
            'editor-removeformatting' => __('remove formatting', 'wcpf'),
            'editor-rtl' => __('rtl', 'wcpf'),
            'editor-spellcheck' => __('spellcheck', 'wcpf'),
            'editor-strikethrough' => __('strike through', 'wcpf'),
            'editor-textcolor' => __('text color', 'wcpf'),
            'editor-ul' => __('ul', 'wcpf'),
            'editor-underline' => __('underline', 'wcpf'),
            'editor-unlink' => __('unlink', 'wcpf'),
            'editor-video' => __('video', 'wcpf'),
            'edit' => __('edit', 'wcpf'),
            'email-alt' => __('email alt', 'wcpf'),
            'email' => __('email', 'wcpf'),
            'excerpt-view' => __('excerpt view', 'wcpf'),

	        // because https://core.trac.mtaandao.org/ticket/30832
	        // but we don't have to offer it at all, actually it looks deprecated
            // 'exerpt-view' => __('excerpt view', 'wcpf'),

            'external' => __('external', 'wcpf'),
            'facebook-alt' => __('facebook alt', 'wcpf'),
            'facebook' => __('facebook', 'wcpf'),
            'feedback' => __('feedback', 'wcpf'),
            'flag' => __('flag', 'wcpf'),
            'format-aside' => __('aside', 'wcpf'),
            'format-audio' => __('audio', 'wcpf'),
            'format-chat' => __('chat', 'wcpf'),
            'format-gallery' => __('gallery', 'wcpf'),
            'format-image' => __('image', 'wcpf'),
            'format-links' => __('links', 'wcpf'),
            'format-quote' => __('quote', 'wcpf'),
            'format-standard' => __('standard', 'wcpf'),
            'format-status' => __('status', 'wcpf'),
            'format-video' => __('video', 'wcpf'),
            'forms' => __('forms', 'wcpf'),
            'googleplus' => __('google plus', 'wcpf'),
            'grid-view' => __('grid view', 'wcpf'),
            'groups' => __('groups', 'wcpf'),
            'hammer' => __('hammer', 'wcpf'),
            'heart' => __('heart', 'wcpf'),
            'id-alt' => __('id alt', 'wcpf'),
            'id' => __('id', 'wcpf'),
            'images-alt2' => __('images alt2', 'wcpf'),
            'images-alt' => __('images alt', 'wcpf'),
            'image-crop' => __('image crop', 'wcpf'),
            'image-flip-horizontal' => __('image flip horizontal', 'wcpf'),
            'image-flip-vertical' => __('image flip vertical', 'wcpf'),
            'image-rotate-left' => __('image rotate left', 'wcpf'),
            'image-rotate-right' => __('image rotate right', 'wcpf'),
            'index-card' => __('index card', 'wcpf'),
            'info' => __('info', 'wcpf'),
            'leftright' => __('left right', 'wcpf'),
            'lightbulb' => __('light bulb', 'wcpf'),
            'list-view' => __('list view', 'wcpf'),
            'location-alt' => __('location alt', 'wcpf'),
            'location' => __('location', 'wcpf'),
            'lock' => __('lock', 'wcpf'),
            'marker' => __('marker', 'wcpf'),
            'media-archive' => __('media archive', 'wcpf'),
            'media-audio' => __('media audio', 'wcpf'),
            'media-code' => __('media code', 'wcpf'),
            'media-default' => __('media default', 'wcpf'),
            'media-document' => __('media document', 'wcpf'),
            'media-interactive' => __('media interactive', 'wcpf'),
            'media-spreadsheet' => __('media spreadsheet', 'wcpf'),
            'media-text' => __('media text', 'wcpf'),
            'media-video' => __('media video', 'wcpf'),
            'megaphone' => __('megaphone', 'wcpf'),
            'menu' => __('menu', 'wcpf'),
            'microphone' => __('microphone', 'wcpf'),
            'migrate' => __('migrate', 'wcpf'),
            'minus' => __('minus', 'wcpf'),
            'money' => __('money', 'wcpf'),
            'nametag' => __('name tag', 'wcpf'),
            'networking' => __('networking', 'wcpf'),
            'no-alt' => __('no alt', 'wcpf'),
            'no' => __('no', 'wcpf'),
            'palmtree' => __('palm tree', 'wcpf'),
            'performance' => __('performance', 'wcpf'),
            'phone' => __('phone', 'wcpf'),
            'playlist-audio' => __('playlist audio', 'wcpf'),
            'playlist-video' => __('playlist video', 'wcpf'),
            'plus-alt' => __('plus alt', 'wcpf'),
            'plus' => __('plus', 'wcpf'),
            'portfolio' => __('portfolio', 'wcpf'),
            'post-status' => __('post status', 'wcpf'),
            'post-trash' => __('post trash', 'wcpf'),
            'pressthis' => __('press this', 'wcpf'),
            'products' => __('products', 'wcpf'),
            'randomize' => __('randomize', 'wcpf'),
            'redo' => __('redo', 'wcpf'),
            'rss' => __('rss', 'wcpf'),
            'schedule' => __('schedule', 'wcpf'),
            'screenoptions' => __('screen options', 'wcpf'),
            'search' => __('search', 'wcpf'),
            'share1' => __('share1', 'wcpf'),
            'share-alt2' => __('share alt2', 'wcpf'),
            'share-alt' => __('share alt', 'wcpf'),
            'share' => __('share', 'wcpf'),
            'shield-alt' => __('shield alt', 'wcpf'),
            'shield' => __('shield', 'wcpf'),
            'slides' => __('slides', 'wcpf'),
            'smartphone' => __('smartphone', 'wcpf'),
            'smiley' => __('smiley', 'wcpf'),
            'sort' => __('sort', 'wcpf'),
            'sos' => __('sos', 'wcpf'),
            'star-empty' => __('star empty', 'wcpf'),
            'star-filled' => __('star filled', 'wcpf'),
            'star-half' => __('star half', 'wcpf'),
            'store' => __('store', 'wcpf'),
            'tablet' => __('tablet', 'wcpf'),
            'tagcloud' => __('tag cloud', 'wcpf'),
            'tag' => __('tag', 'wcpf'),
            'testimonial' => __('testimonial', 'wcpf'),
            'text' => __('text', 'wcpf'),
            'tickets-alt' => __('tickets alt', 'wcpf'),
            'tickets' => __('tickets', 'wcpf'),
            'translation' => __('translation', 'wcpf'),
            'trash' => __('trash', 'wcpf'),
            'twitter' => __('twitter', 'wcpf'),
            'undo' => __('undo', 'wcpf'),
            'universal-access-alt' => __('universal access alt', 'wcpf'),
            'universal-access' => __('universal access', 'wcpf'),
            'update' => __('update', 'wcpf'),
            'upload' => __('upload', 'wcpf'),
            'vault' => __('vault', 'wcpf'),
            'video-alt2' => __('video alt2', 'wcpf'),
            'video-alt3' => __('video alt3', 'wcpf'),
            'video-alt' => __('video alt', 'wcpf'),
            'visibility' => __('visibility', 'wcpf'),
            'welcome-add-page' => __('add page', 'wcpf'),
            'welcome-comments' => __('comments', 'wcpf'),
            'welcome-edit-page' => __('edit page', 'wcpf'),
            'welcome-learn-more' => __('learn more', 'wcpf'),
            'welcome-view-site' => __('view site', 'wcpf'),
            'welcome-widgets-menus' => __('widgets menus', 'wcpf'),
            'welcome-write-blog' => __('write blog', 'wcpf'),
            'mtaandao-alt' => __('mtaandao alt', 'wcpf'),
            'mtaandao' => __('mtaandao', 'wcpf'),
            'yes' => __('yes', 'wcpf'),
        );
        printf(
            '<p><input type="text" class="js-mncf-search large-text" placeholder="%s" /</p>',
            esc_attr__('Search', 'mncf')
        );
        $current = isset($_REQUEST['slug']) && is_string($_REQUEST['slug'])? $_REQUEST['slug']:'';
        echo '<ul>';
        foreach ( $icons as $slug => $title ) {
            printf(
                '<li data-mncf-icon="%s" class="%s"><a href="#" data-mncf-icon="%s"><span class="dashicons-before dashicons-%s">%s</span></a></li>',
                esc_attr($slug),
                $current == $slug? 'selected':'',
                esc_attr($slug),
                esc_attr($slug),
                $title
            );
        }
        echo '</ul>';
        die;
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     * @access (for functions: only use if private)
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description.
     * @return type Description.
     */
    public function get_post_type_slug_from_request()
    {
        if ( !isset($_GET['mncf-post-type']) ) {
            return '';
        }

        // get current post type
        require_once MNCF_INC_ABSPATH.'/classes/class.types.admin.post-type.php';
        $mncf_post_type = new Types_Admin_Post_Type();
		$get_post_type_slug_from_request = sanitize_text_field( $_GET['mncf-post-type'] );
        $custom_post_type = $mncf_post_type->get_post_type($get_post_type_slug_from_request);
        if ( isset($custom_post_type['slug']) ) {
            return $custom_post_type['slug'];
        }
        return '';
    }

	/**
	 * Render content of the MNML post type translation box.
	 *
	 * The box contains information about translatability of the post type, or a notice if the post type
	 * wasn't saved yet.
	 *
	 * Relies on mnml_custom_post_translation_options() which uses the mncf-edit-type GET parameter to determine
	 * the post type slug.
	 *
	 * @since unknown
	 */
    public function mnml_box()
    {
        if ( !function_exists('mnml_custom_post_translation_options') ) {
            _e('Somethng wrong!', 'mncf');
            return;
        }

	    $post_type_slug = $this->get_post_type_slug_from_request();

	    $is_add_new_page = empty( $post_type_slug );

	    if( $is_add_new_page ) {

		    printf(
			    '<div class="notice notice-success below-h2"><p>%s</p></div>',
			    __( 'You will be able to make this post type translatable once it is saved.', 'mncf' )
		    );

	    } else {
		    echo mnml_custom_post_translation_options();
	    }
    }
}

