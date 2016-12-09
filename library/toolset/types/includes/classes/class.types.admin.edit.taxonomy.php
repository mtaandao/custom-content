<?php

require_once MNCF_INC_ABSPATH . '/classes/class.types.admin.page.php';
require_once MNCF_INC_ABSPATH.'/classes/class.types.admin.taxonomies.php';
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
class Types_Admin_Edit_Taxonomy extends Types_Admin_Page
{

    public function __construct()
    {
        $this->taxonomies = new Types_Admin_Taxonomies();
    }

    public function init_admin()
    {
        $this->init_hooks();

        $this->get_id = 'mncf-tax';

        $this->post_type = 'taxonomy';
        $this->boxes = array(
            'types_labels' => array(
                'callback' => array($this, 'box_labels'),
                'title' => __('Labels', 'mncf'),
                'default' => 'normal',
                'post_types' => 'custom',
            ),
            'types_taxonomy_type' => array(
                'callback' => array($this, 'box_taxonomy_type'),
                'title' => __('Taxonomy type', 'mncf'),
                'default' => 'normal',
                'post_types' => 'custom',
            ),
            'types_taxonomies' => array(
                'callback' => array($this, 'box_post_types'),
                'title' => __('Post Types to be used with this Taxonomy', 'mncf'),
                'default' => 'normal',
            ),
            'types_options' => array(
                'callback' => array($this, 'box_options'),
                'title' => __('Options', 'mncf'),
                'default' => 'advanced',
                'post_types' => 'custom',
            ),

            'submitdiv' => array(
                'callback' => array($this, 'box_submitdiv'),
                'title' => __('Save', 'mncf'),
                'default' => 'side',
                'priority' => 'core',
            ),
        );
        $this->boxes = apply_filters('mncf_meta_box_order_defaults', $this->boxes, 'taxonomy');
        $this->boxes = apply_filters('mncf_meta_box_taxonomy', $this->boxes);

        /** This action is documented in includes/classes/class.types.admin.page.php  */
        add_action('mncf_closedpostboxes', array($this, 'closedpostboxes'));
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
        $taxonomies = array();

        if ( isset( $_GET[$this->get_id] ) ) {
            $id = sanitize_text_field( $_GET[$this->get_id] );
        } elseif ( isset( $_POST[$this->get_id] ) ) {
            $id = sanitize_text_field( $_POST[$this->get_id] );
        }

        if ( $id ) {
            $taxonomies = $this->taxonomies->get();
            if ( isset( $taxonomies[$id] ) ) {
                $this->ct = $taxonomies[$id];
                $update = true;
            } else {
                mncf_admin_message( __( 'Wrong Taxonomy specified.', 'mncf' ), 'error' );
                return false;
            }
        } else {
            $this->ct = mncf_custom_taxonomies_default();
        }

        $current_user_can_edit = MNCF_Roles::user_can_edit('custom-taxonomy', $this->ct);

        /**
         * sanitize _builtin
         */
        if ( !isset($this->ct['_builtin']) ) {
            $this->ct['_builtin'] = false;
        }

        $form = $this->prepare_screen();

        if ( $current_user_can_edit && $update ) {
            $form['id'] = array(
                '#type' => 'hidden',
                '#value' => $id,
                '#name' => 'ct[mncf-tax]',
            );
	        
	        $form['slug_conflict_check_nonce'] = array(
		        '#type' => 'hidden',
		        '#value' => mn_create_nonce( Types_Ajax::CALLBACK_CHECK_SLUG_CONFLICTS ),
		        '#name' => 'types_check_slug_conflicts_nonce',
		        '_builtin' => true,
	        );

        }

        /**
         * post icon field
         */
        $menu_icon = isset( $this->ct['icon']) && !empty($this->ct['icon']) ? $this->ct['icon'] : 'admin-post';
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
        );
        $table_row = '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>';

        $form['name'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[labels][name]',
            '#title' => __( 'Name plural', 'mncf' ) . ' (<strong>' . __( 'required', 'mncf' ) . '</strong>)',
            '#description' => '<strong>' . __( 'Enter in plural!', 'mncf' ) . '.',
            '#value' =>  isset( $this->ct['labels']['name'] ) ? $this->ct['labels']['name']:'',
            '#validate' => array(
                'required' => array('value' => true),
                'maxlength' => array('value' => 30),
            ),
            '#pattern' => $table_row,
            '#inline' => true,
            '#attributes' => array(
                'placeholder' => __('Enter Taxonomy name plural','mncf'),
                'class' => 'widefat',
            ),
        );

        $form['name-singular'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[labels][singular_name]',
            '#title' => __( 'Name singular', 'mncf' ) . ' (<strong>' . __( 'required', 'mncf' ) . '</strong>)',
            '#description' => '<strong>' . __( 'Enter in singular!', 'mncf' ) . '</strong><br />' . '.',
            '#value' => isset( $this->ct['labels']['singular_name'] ) ? $this->ct['labels']['singular_name']:'',
            '#validate' => array(
                'required' => array('value' => true),
                'maxlength' => array('value' => 30),
            ),
            '#pattern' => $table_row,
            '#inline' => true,
            '#attributes' => array(
                'placeholder' => __('Enter Taxonomy name singular','mncf'),
                'class' => 'widefat js-mncf-slugize-source',
            ),
        );

        /*
         *
         * IF isset $_POST['slug'] it means form is not submitted
         */
        $attributes = array();
        if ( !empty( $_POST['ct']['slug'] ) ) {
            $reserved = mncf_is_reserved_name( sanitize_text_field( $_POST['ct']['slug'] ), 'taxonomy' );
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
            '#description' => '<strong>' . __( 'Enter in singular!', 'mncf' )
            . '</strong><br />' . __( 'Machine readable name.', 'mncf' )
            . '<br />' . __( 'If not provided - will be created from singular name.', 'mncf' ) . '<br />',
                '#value' => isset( $this->ct['slug'] ) ? $this->ct['slug'] : '',
                '#pattern' => $table_row,
                '#inline' => true,
                '#validate' => array(
                    'required' => array('value' => true),
                    'nospecialchars' => array('value' => true),
                    'maxlength' => array('value' => 30),
                ),
                '#attributes' => $attributes + array(
                    'maxlength' => '30',
                    'placeholder' => __('Enter Taxonomy slug','mncf'),
                    'class' => 'widefat js-mncf-slugize',
                ),
            );
        $form['description'] = array(
            '#type' => 'textarea',
            '#name' => 'ct[description]',
            '#title' => __( 'Description', 'mncf' ),
            '#value' => isset( $this->ct['description'] ) ? $this->ct['description'] : '',
            '#attributes' => array(
                'rows' => 4,
                'cols' => 60,
                'placeholder' => __('Enter Taxonomy description','mncf'),
                'class' => 'hidden js-mncf-description',
            ),
            '#pattern' => $table_row,
            '#inline' => true,
            '#after' => ( $this->ct['_builtin'] )
                ? __( 'This is built-in Mtaandao Taxonomy.', 'mncf' )
                : sprintf(
                    '<a class="js-mncf-toggle-description hidden" href="#">%s</a>',
                    __('Add description', 'mncf')
                ),
        );
        $form['table-1-close'] = array(
            '#type' => 'markup',
            '#markup' => '</tbody></table>',
        );

        $form['box-1-close'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
            '_builtin' => true,
        );

        if ( $this->ct['_builtin']) {
            $form['name']['#attributes']['readonly'] = 'readonly';
            $form['name-singular']['#attributes']['readonly'] = 'readonly';
            $form['slug']['#attributes']['readonly'] = 'readonly';
            $form['description']['#attributes']['readonly'] = 'readonly';
        }

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
                    __('not visible in admin menus, no user-interface to administrate taxonomy, not queryable on front-end', 'mncf')
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
        $button_text = __( 'Save Taxonomy', 'mncf' );
        $form = $this->submitdiv( $button_text, $form, 'custom-taxonomy', $this->ct['_builtin'] );
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
            '#force_boolean' => true,
            '#title' => __( 'Rewrite', 'mncf' ),
            '#name' => 'ct[rewrite][enabled]',
            '#description' => __( 'Rewrite permalinks with this format. Default will use $taxonomy as query var.', 'mncf' ),
            '#default_value' => !empty( $this->ct['rewrite']['enabled'] ),
            '#inline' => true,
        );
        $hidden = empty( $this->ct['rewrite']['enabled'] ) ? ' class="hidden"' : '';
        $form['rewrite-slug'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[rewrite][slug]',
            '#title' => __( 'Replace taxonomy slug with this', 'mncf' ),
            '#description' => __( 'Optional', 'mncf' ) . '. ' . __( 'Replace taxonomy slug with this - defaults to taxonomy slug.', 'mncf' ),
            '#value' => isset( $this->ct['rewrite']['slug'] ) ? $this->ct['rewrite']['slug'] : '',
            '#inline' => true,
            '#before' => '<div id="mncf-types-form-rewrite-toggle"' . $hidden . '>',
            '#after' => '</div>',
            '#validate' => array('rewriteslug' => array('value' => 'true')),
            '#attributes' => array(
                'class' => 'regular-text',
            ),
        );
        $form['rewrite-with_front'] = array(
            '#type' => 'checkbox',
            '#force_boolean' => true,
            '#title' => __( 'Allow permalinks to be prepended with front base', 'mncf' ),
            '#name' => 'ct[rewrite][with_front]',
            '#description' => __( 'Defaults to true.', 'mncf' ),
            '#default_value' => !empty( $this->ct['rewrite']['with_front'] ),
            '#inline' => true,
        );
        $form['rewrite-hierarchical'] = array(
            '#type' => 'checkbox',
            '#name' => 'ct[rewrite][hierarchical]',
            '#title' => __( 'Hierarchical URLs', 'mncf' ),
            '#description' => sprintf( __( 'True or false allow hierarchical urls (implemented in %sVersion 3.1%s).', 'mncf' ), '<a href="http://codex.mtaandao.org/Version_3.1" title="Version 3.1" target="_blank">', '</a>' ),
            '#default_value' => !empty( $this->ct['rewrite']['hierarchical'] ),
            '#inline' => true,
        );
        $form['vars'] = array(
            '#type' => 'checkboxes',
            '#name' => 'ct[advanced]',
            '#inline' => true,
            '#options' => array(
                'show_ui' => array(
                    '#name' => 'ct[show_ui]',
                    '#default_value' => !empty( $this->ct['show_ui'] ),
                    '#title' => __( 'show_ui', 'mncf' ),
                    '#description' => __( 'Whether to generate a default UI for managing this taxonomy.', 'mncf' ) . '<br />' . __( 'Default: if not set, defaults to value of public argument.', 'mncf' ),
                    '#inline' => true,
                ),
                'show_in_nav_menus' => array(
                    '#name' => 'ct[show_in_nav_menus]',
                    '#default_value' => !empty( $this->ct['show_in_nav_menus'] ),
                    '#title' => __( 'show_in_nav_menus', 'mncf' ),
                    '#description' => __( 'True makes this taxonomy available for selection in navigation menus.', 'mncf' ) . '<br />' . __( 'Default: if not set, defaults to value of public argument.', 'mncf' ),
                    '#inline' => true,
                ),
                'show_tagcloud' => array(
                    '#name' => 'ct[show_tagcloud]',
                    '#default_value' => !empty( $this->ct['show_tagcloud'] ),
                    '#title' => __( 'show_tagcloud', 'mncf' ),
                    '#description' => __( 'Whether to allow the Tag Cloud widget to use this taxonomy.', 'mncf' ) . '<br />' . __( 'Default: if not set, defaults to value of show_ui argument.', 'mncf' ),
                    '#inline' => true,
                ),
            ),
        );
        if ( mncf_compare_mn_version( '3.5', '>=' )) {
            $form['vars']['#options']['show_admin_column'] = array(
                '#name' => 'ct[show_admin_column]',
                '#default_value' => !empty( $this->ct['show_admin_column'] ),
                '#title' => __( 'show_admin_column', 'mncf' ),
                '#description' => __( 'Whether to allow automatic creation of taxonomy columns on associated post-types.', 'mncf' ) . '<br />' . __( 'Default: false.', 'mncf' ),
                '#inline' => true,
            );
        }
        $query_var = isset( $this->ct['query_var'] ) ? $this->ct['query_var'] : '';
        $hidden = !empty( $this->ct['query_var_enabled'] ) ? '' : ' class="hidden"';
        $form['query_var'] = array(
            '#type' => 'checkbox',
            '#name' => 'ct[query_var_enabled]',
            '#title' => 'query_var',
            '#description' => __( 'Disable to prevent queries like "mysite.com/?taxonomy=example". Enable to use queries like "mysite.com/?taxonomy=example". Enable and set a value to use queries like "mysite.com/?query_var_value=example"', 'mncf' ) . '<br />' . __( 'Default: true - set to $taxonomy.', 'mncf' ),
            '#default_value' => !empty( $this->ct['query_var_enabled'] ),
            '#after' => '<div id="mncf-types-form-queryvar-toggle"' . $hidden . '><input type="text" name="ct[query_var]" value="' . $query_var . '" class="regular-text mncf-form-textfield form-textfield textfield" /><div class="description mncf-form-description mncf-form-description-checkbox description-checkbox">' . __( 'Optional', 'mncf' ) . '. ' . __( 'String to customize query var', 'mncf' ) . '</div></div>',
            '#inline' => true,
        );
        $form['update_count_callback'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[update_count_callback]',
            '#title' => 'update_count_callback', 'mncf',
            '#description' => __( 'Function name that will be called to update the count of an associated $object_type, such as post, is updated.', 'mncf' ) . '<br />' . __( 'Default: None.', 'mncf' ),
            '#value' => !empty( $this->ct['update_count_callback'] ) ? $this->ct['update_count_callback'] : '',
            '#inline' => true,
            '#attributes' => array(
                'class' => 'regular-text',
            ),
        );

        $form['meta_box_cb-header'] = array(
            '#type' => 'markup',
            '#markup' => sprintf('<h3>%s</h3>', __('Meta box callback function', 'mncf')),
        );
        $form['meta_box_cb-disabled'] = array(
            '#type' => 'checkbox',
            '#force_boolean' => true,
            '#title' => __( 'Hide taxonomy meta box.', 'mncf' ),
            '#name' => 'ct[meta_box_cb][disabled]',
            '#default_value' => !empty( $this->ct['meta_box_cb']['disabled'] ),
            '#inline' => true,
            '#description' => __( 'If you disable this, there will be no metabox on entry edit screen.', 'mncf' ),
        );
        $hidden = empty( $this->ct['meta_box_cb']['disabled'] ) ? '':' class="hidden"';
        $form['meta_box_cb'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[meta_box_cb][callback]',
            '#title' => __('meta_box_cb', 'mncf'),
            '#description' => __( 'Provide a callback function name for the meta box display.', 'mncf' ) . '<br />' . __( 'Default: None.', 'mncf' ),
            '#value' => !empty( $this->ct['meta_box_cb']['callback']) ? $this->ct['meta_box_cb']['callback'] : '',
            '#inline' => true,
            '#before' => '<div id="mncf-types-form-meta_box_cb-toggle"' . $hidden . '>',
            '#after' => '</div>',
            '#attributes' => array(
                'class' => 'regular-text',
            ),
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
    public function box_labels()
    {
        $labels = array(
            'search_items' => array(
                'title' => __( 'Search %s', 'mncf' ),
                'description' => __( "The search items text. Default is __( 'Search Tags' ) or __( 'Search Categories' ).", 'mncf' ),
                'label' => __('Search Items', 'mncf'),
            ),
            'popular_items' => array(
                'title' => __( 'Popular %s', 'mncf' ),
                'description' => __( "The popular items text. Default is __( 'Popular Tags' ) or null.", 'mncf' ),
                'label' => __('Popular Items', 'mncf'),
            ),
            'all_items' => array(
                'title' => __( 'All %s', 'mncf' ),
                'description' => __( "The all items text. Default is __( 'All Tags' ) or __( 'All Categories' ).", 'mncf' ),
                'label' => __('All Items', 'mncf'),
            ),
            'parent_item' => array(
                'title' => __( 'Parent %s', 'mncf' ),
                'description' => __( "The parent item text. This string is not used on non-hierarchical taxonomies such as post tags. Default is null or __( 'Parent Category' ).", 'mncf' ),
                'label' => __('Parent Item', 'mncf'),
            ),
            'parent_item_colon' => array(
                'title' => __( 'Parent %s:', 'mncf' ),
                'description' => __( "The same as parent_item, but with colon : in the end null, __( 'Parent Category:' ).", 'mncf' ),
                'label' => __('Parent Item with colon', 'mncf'),
            ),
            'edit_item' => array(
                'title' => __( 'Edit %s', 'mncf' ),
                'description' => __( "The edit item text. Default is __( 'Edit Tag' ) or __( 'Edit Category' ).", 'mncf' ),
                'label' => __('Edit Item', 'mncf'),
            ),
            'update_item' => array(
                'title' => __( 'Update %s', 'mncf' ),
                'description' => __( "The update item text. Default is __( 'Update Tag' ) or __( 'Update Category' ).", 'mncf' ),
                'label' => __('Update Item', 'mncf'),
            ),
            'add_new_item' => array(
                'title' => __( 'Add New %s', 'mncf' ),
                'description' => __( "The add new item text. Default is __( 'Add New Tag' ) or __( 'Add New Category' ).", 'mncf' ),
                'label' => __('Add New Item', 'mncf'),
            ),
            'new_item_name' => array(
                'title' => __( 'New %s Name', 'mncf' ),
                'description' => __( "The new item name text. Default is __( 'New Tag Name' ) or __( 'New Category Name' ).", 'mncf' ),
                'label' => __('New Item Name', 'mncf'),
            ),
            'separate_items_with_commas' => array(
                'title' => __( 'Separate %s with commas', 'mncf' ),
                'description' => __( "The separate item with commas text used in the taxonomy meta box. This string isn't used on hierarchical taxonomies. Default is __( 'Separate tags with commas' ), or null.", 'mncf' ),
                'label' => __('Separate Items', 'mncf'),
            ),
            'add_or_remove_items' => array(
                'title' => __( 'Add or remove %s', 'mncf' ),
                'description' => __( "the add or remove items text used in the meta box when JavaScript is disabled. This string isn't used on hierarchical taxonomies. Default is __( 'Add or remove tags' ) or null.", 'mncf' ),
                'label' => __('Add or remove', 'mncf'),
            ),
            'choose_from_most_used' => array(
                'title' => __( 'Choose from the most used %s', 'mncf' ),
                'description' => __( "The choose from most used text used in the taxonomy meta box. This string isn't used on hierarchical taxonomies. Default is __( 'Choose from the most used tags' ) or null.", 'mncf' ),
                'label' => __('Most Used', 'mncf'),
            ),
            'menu_name' => array(
                'title' => __( 'Menu Name', 'mncf' ),
                'description' => __( "The menu name text. This string is the name to give menu items. Defaults to value of name.", 'mncf' ),
                'label' => __('Menu Name', 'mncf'),
            ),
        );

        $form = array();
        $form['table-1-open'] = array(
            '#type' => 'markup',
            '#markup' => '<table class="mncf-types-form-table widefat striped fixed"><tbody>',
        );
        foreach ( $labels as $name => $label ) {
            $form['labels-' . $name] = array(
                '#type' => 'textfield',
                '#name' => 'ct[labels][' . $name . ']',
                '#title' => $label['label'],
                '#description' => $label['description'],
                '#value' => isset( $this->ct['labels'][$name] ) ? mn_kses_post($this->ct['labels'][$name]):'',
                '#inline' => true,
                '#pattern' => '<tr><td><LABEL></td><td><ELEMENT><DESCRIPTION></td></tr>',
                '#attributes' => array(
                    'class' => 'widefat',
                ),
            );
        }
        $form['table-1-close'] = array(
            '#type' => 'markup',
            '#markup' => '</tbody></table>',
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
    public function box_post_types()
    {
        global $mncf;
        $form = array();
        $post_types = get_post_types( '', 'objects' );
        $options = array();

        $supported = $this->taxonomies->get_post_types_supported_by_taxonomy($this->ct['slug']);

        foreach ( $post_types as $post_type_slug => $post_type ) {
            if ( in_array( $post_type_slug, $mncf->excluded_post_types ) || !$post_type->show_ui ) {
                continue;
            }
            $options[$post_type_slug] = array(
                '#name' => 'ct[supports][' . $post_type_slug . ']',
                '#title' => $post_type->labels->name,
                '#default_value' =>
                    in_array( $post_type_slug, $supported )
                    || array_key_exists( $post_type_slug, $supported )
                    || ( isset( $_GET['assign_type'] ) && $_GET['assign_type'] == $post_type_slug ),
                '#inline' => true,
                '#before' => '<li>',
                '#after' => '</li>',
            );
        }

        $options = $this->sort_by_title($options);

        $form['types'] = array(
            '#type' => 'checkboxes',
            '#options' => $options,
            '#name' => 'ct[supports]',
            '#inline' => true,
            '#before' => '<ul class="mncf-list">',
            '#after' => '</ul>',
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
    public function box_taxonomy_type()
    {
        $form = array();
        $form['make-hierarchical'] = array(
            '#type' => 'radios',
            '#name' => 'ct[hierarchical]',
            '#default_value' => (empty( $this->ct['hierarchical'] ) || $this->ct['hierarchical'] == 'hierarchical') ? 'hierarchical' : 'flat',
            '#inline' => true,
            '#options' => array(
                sprintf(
                    '<b>%s</b> - %s',
                    __('Hierarchical', 'mncf'),
                    __('like post categories, with parent / children relationship and checkboxes to select taxonomy', 'mncf' )
                ) => 'hierarchical',
                sprintf(
                    '<b>%s</b> - %s',
                    __('Flat', 'mncf'),
                    __( 'like post tags, with a text input to enter terms', 'mncf' )
                ) => 'flat',
            ),
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
            mncf_admin_message( __( 'Please set taxonomy name', 'mncf' ), 'error' );
            return false;
        }

        if ( isset( $data[$this->get_id] ) ) {
            $update = true;
            $data[$this->get_id] = sanitize_title( $data[$this->get_id] );
        }
        if ( isset( $data['slug'] ) ) {
            $data['slug'] = sanitize_title( $data['slug'] );
        }
        if ( isset( $data['rewrite']['slug'] ) ) {
            $data['rewrite']['slug'] = remove_accents( $data['rewrite']['slug'] );
            $data['rewrite']['slug'] = strtolower( $data['rewrite']['slug'] );
            $data['rewrite']['slug'] = trim( $data['rewrite']['slug'] );
        }

        // Set tax name
        $tax = '';
        if ( !empty( $data['slug'] ) ) {
            $tax = $data['slug'];
        } else if ( !empty( $data[$this->get_id] ) ) {
            $tax = $data[$this->get_id];
        } else if ( !empty( $data['labels']['singular_name'] ) ) {
            $tax = sanitize_title( $data['labels']['singular_name'] );
        }

        if ( empty( $tax ) ) {
            mncf_admin_message( __( 'Please set taxonomy name', 'mncf' ), 'error' );
            return false;
        }

        if ( empty( $data['labels']['singular_name'] ) ) {
            $data['labels']['singular_name'] = $tax;
        }

        $data['slug'] = $tax;
        $taxonomies = $this->taxonomies->get();

        /**
         * is built-in?
         */
        $tax_is_built_in = mncf_is_builtin_taxonomy($tax);

        // Check reserved name
        $reserved = mncf_is_reserved_name( $tax, 'taxonomy' ) && !$tax_is_built_in;
        if ( is_mn_error( $reserved ) ) {
            mncf_admin_message( $reserved->get_error_message(), 'error' );
            return false;
        }

        // Check if exists
        if ( $update && !array_key_exists( $data[$this->get_id], $taxonomies ) ) {
            mncf_admin_message( __( "Taxonomy do not exist", 'mncf' ), 'error' );
            return false;
        }

        // Check overwriting
        if ( !$update && array_key_exists( $tax, $taxonomies ) ) {
            /**
             * set last edit author
             */

            $data[MNCF_AUTHOR] = get_current_user_id();

            mncf_admin_message( __( 'Taxonomy already exists', 'mncf' ), 'error' );
            return false;
        }

        // Check if our tax overwrites some tax outside
        $tax_exists = get_taxonomy( $tax );
        if ( !$update && !empty( $tax_exists ) ) {
            mncf_admin_message( __( 'Taxonomy already exists', 'mncf' ), 'error' );
            return false;
        }

        // Check if renaming
        if ( !$tax_is_built_in && $update && $data[$this->get_id] != $tax ) {
            global $mndb;
            $mndb->update(
                $mndb->term_taxonomy,
                array(
                    'taxonomy' => esc_sql($tax)
                ),
                array(
                    'taxonomy' => esc_sql($data[$this->get_id]),
                ),
                array('%s'),
                array('%s')
            );
            // Sync action
            do_action( 'mncf_taxonomy_renamed', $tax, $data[$this->get_id] );
            // Delete old type
            unset( $taxonomies[$data[$this->get_id]] );
        }

        // Check if active
        if ( isset( $taxonomies[$tax]['disabled'] ) ) {
            $data['disabled'] = $taxonomies[$tax]['disabled'];
        }

        /**
         * Sync with post types
         */
        $post_types = get_option(MNCF_OPTION_NAME_CUSTOM_TYPES, array() );
        foreach ( $post_types as $id => $type ) {
            if ( !empty( $data['supports'] ) && array_key_exists( $id, $data['supports'] ) ) {
                if ( empty($post_types[$id]['taxonomies'][$data['slug']]) ) {
                    $post_types[$id][TOOLSET_EDIT_LAST] = time();
                }
                $post_types[$id]['taxonomies'][$data['slug']] = 1;
            } else {
                if ( !empty($post_types[$id]['taxonomies'][$data['slug']]) ) {
                    $post_types[$id][TOOLSET_EDIT_LAST] = time();
                }
                unset( $post_types[$id]['taxonomies'][$data['slug']] );
            }
        }
        update_option(MNCF_OPTION_NAME_CUSTOM_TYPES, $post_types);

        /**
         * fix built-in
         */
        if ($tax_is_built_in) {
            $data['_builtin'] = true;
            unset($data['icon']);

            // make sure default labels are used for the built-in taxonomies
            // for the case a smart user enables disabled="disabled" inputs
            $data['labels'] = $taxonomies[$tax]['labels'];

            unset($data['mncf-tax']);
        }

        $taxonomies[$tax] = $data;
        $taxonomies[$tax][TOOLSET_EDIT_LAST] = time();

        // set last edit author
        $taxonomies[$tax][MNCF_AUTHOR] = get_current_user_id();

        foreach( $taxonomies as $id => $taxonomy ) {
            // make sure "supports" field is saved for ALL taxonomies
            if( !isset( $taxonomy['supports'] ) && isset( $taxonomy['object_type'] ) ) {
                if( !empty( $taxonomy['object_type'] ) ) {
                    foreach( $taxonomy['object_type'] as $supported_post ) {
                        $taxonomy['supports'][$supported_post] = 1;
                    }
                }
            }

            // make sure "slug" field is set
            if( !isset( $taxonomy['slug'] ) )
                $taxonomy['slug'] = isset( $taxonomy['name'] )
                    ? $taxonomy['name']
                    : $id;

            // make sure "name" field is set
            if( !isset( $taxonomy['name'] ) )
                $taxonomy['name'] = isset( $taxonomy['slug '] );

            // make sure "supports" field is set
            if( !isset( $taxonomy['supports'] ) )
                $taxonomy['supports'] = array();


            $taxonomies[$id] = $taxonomy;
        }

        /**
         * save
         */
        update_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, $taxonomies );

        // MNML register strings
        mncf_custom_taxonimies_register_translation( $tax, $data );

        $msg = $update
            ? __( 'Taxonomy saved.', 'mncf' )
            : __( 'New Taxonomy created.', 'mncf' );

        mncf_admin_message_store(
            $msg,
            'updated notice notice-success is-dismissible'
        );

        // Flush rewrite rules
        flush_rewrite_rules();

        $args = array(
            'page' => 'mncf-edit-tax',
            $this->get_id => $tax,
            'mncf-message' => get_user_option('types-modal'),
            'flush' => 1
        );
        
        if( isset( $_GET['ref'] ) )
            $args['ref'] = $_GET['ref'];

        // Redirect
        mn_safe_redirect(
            esc_url_raw(
                add_query_arg(
                    $args,
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
        if ( 'toolset_page_mncf-edit-tax' != $screen_base ) {
            return;
        }
        $option_name = sprintf('closedpostboxes_%s', $screen_base);
        $closedpostboxes = get_user_meta(get_current_user_id(), $option_name);
        if ( !empty($closedpostboxes) ) {
            return;
        }
        $closedpostboxes[] = 'types_options';
        $closedpostboxes[] = 'types_labels';
        update_user_option( get_current_user_id(), $option_name, $closedpostboxes, true);
    }

}

