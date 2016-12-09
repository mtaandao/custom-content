<?php

require_once MNCF_INC_ABSPATH . '/classes/class.types.admin.edit.fields.php';

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
 *
 * @return type Description.
 */
class Types_Admin_Edit_Custom_Fields_Group extends Types_Admin_Edit_Fields {

	const PAGE_NAME = 'mncf-edit';

    public function __construct()
    {
        parent::__construct();
        $this->get_id = 'group_id';
        add_action('mn_ajax_mncf_ajax_filter', array($this, 'ajax_filter_dialog'));
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
	 *
	 * @return type Description.
	 */
	public function init_admin() {
		$this->post_type = TYPES_CUSTOM_FIELD_GROUP_CPT_NAME;
		$this->init_hooks();
		$this->boxes = array(
			'submitdiv'   => array(
				'callback' => array($this, 'box_submitdiv'),
				'title'    => __( 'Save', 'mncf' ),
				'default'  => 'side',
				'priority' => 'high',
			),
			/* 'types_where' => array(
				'callback' => array($this, 'sidebar_group_conditions'),
				'title'    => __( 'Where to include this Field Group', 'mncf' ),
				'default'  => 'side',
			), */
		);

		/** Admin styles **/
		$this->current_user_can_edit = MNCF_Roles::user_can_create( 'custom-field' );

		if( defined( 'TYPES_USE_STYLING_EDITOR' )
		    && TYPES_USE_STYLING_EDITOR
		    && $this->current_user_can_edit) {
			$this->boxes['types_styling_editor'] = array(
				'callback' => array( $this, 'types_styling_editor' ),
				'title' => __( 'Fields Styling Editor' ),
				'default'  => 'normal',
			);
		}
		$this->boxes = apply_filters( 'mncf_meta_box_order_defaults', $this->boxes, $this->post_type );
		$this->boxes = apply_filters( 'mncf_meta_box_custom_field', $this->boxes, $this->post_type );

		mn_enqueue_script( __CLASS__, MNCF_RES_RELPATH . '/js/' . 'taxonomy-form.js', array(
			'jquery',
			'jquery-ui-dialog',
			'jquery-ui-tabs'
		), MNCF_VERSION );
		mn_enqueue_style( 'mn-jquery-ui-dialog' );

		mncf_admin_add_js_settings( 'mncfFormAlertOnlyPreview', sprintf( "'%s'", __( 'Sorry, but this is only preview!', 'mncf' ) ) );
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
	 *
	 * @return type Description.
	 */
	public function form() {
		$this->save();

		$this->current_user_can_edit = MNCF_Roles::user_can_create( 'custom-field' );

		// If it's update, get data
		// Note (by christian 3 June 2016): "Update" means: we're on group edit page and not on creating a new one.
		if( isset( $_REQUEST[ $this->get_id ] ) ) {
			$this->update = mncf_admin_fields_get_group( intval( $_REQUEST[ $this->get_id ] ) );
			if( empty( $this->update ) ) {
				$this->update = false;
				mncf_admin_message( sprintf( __( "Group with ID %d do not exist", 'mncf' ), intval( $_REQUEST[ $this->get_id ] ) ) );
			} else {
				$this->current_user_can_edit = MNCF_Roles::user_can_edit( 'custom-field', $this->update );
				$this->update['fields']      = mncf_admin_fields_get_fields_by_group( sanitize_text_field( $_REQUEST[ $this->get_id ] ), 'slug', false, true );
				$this->update['post_types']  = mncf_admin_get_post_types_by_group( sanitize_text_field( $_REQUEST[ $this->get_id ] ) );
				$this->update['taxonomies']  = mncf_admin_get_taxonomies_by_group( sanitize_text_field( $_REQUEST[ $this->get_id ] ) );
				$this->update['templates']   = mncf_admin_get_templates_by_group( sanitize_text_field( $_REQUEST[ $this->get_id ] ) );
				if( defined( 'TYPES_USE_STYLING_EDITOR' ) && TYPES_USE_STYLING_EDITOR ) {
					$this->update['admin_styles'] = mncf_admin_get_groups_admin_styles_by_group( sanitize_text_field( $_REQUEST[ $this->get_id ] ) );
				}
			}
		}

		/**
		 * sanitize id
		 */
		if( ! isset( $this->update['id'] ) ) {
			$this->update['id'] = 0;
		}

		/**
		 * setup meta type
		 */
		$this->update['meta_type'] = 'custom_fields_group';

		/**
		 * copy update to ct
		 */
		$this->ct = $this->update;

		$form = $this->prepare_screen();

		$form['_mnnonce_mncf'] = array(
			'#type'   => 'markup',
			'#markup' => mn_nonce_field( 'mncf_form_fields', '_mnnonce_mncf', true, false ),
		);

		/**
		 * nonce depend on group id
		 */
		$form[ '_mnnonce_' . $this->post_type ] = array(
			'#type'   => 'markup',
			'#markup' => mn_nonce_field( $this->get_nonce_action( $this->update['id'] ), 'mncf_save_group_nonce', true, false ),
		);

		$form['form-open']     = array(
			'#type'   => 'markup',
			'#markup' => sprintf( '<div id="post-body-content" class="%s">', $this->current_user_can_edit
				? ''
				: 'mncf-types-read-only' ),
		);
		$form[ $this->get_id ] = array(
			'#type'  => 'hidden',
			'#name'  => 'mncf[group][id]',
			'#value' => $this->update['id'],
		);

		$form['table-1-open'] = array(
			'#type'   => 'markup',
			'#markup' => '<table id="mncf-types-form-name-table" class="mncf-types-form-table widefat js-mncf-slugize-container"><thead><tr><th colspan="2">' . __( 'Name and description', 'mncf' ) . '</th></tr></thead><tbody>',
		);
		$table_row            = '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>';
		$form['title']        = array(
			'#title'      => sprintf( '%s <b>(%s)</b>', __( 'Name', 'mncf' ), __( 'required', 'mncf' ) ),
			'#type'       => 'textfield',
			'#name'       => 'mncf[group][name]',
			'#id'         => 'mncf-group-name',
			'#value'      => $this->update['id']
				? $this->update['name']
				: '',
			'#inline'     => true,
			'#attributes' => array(
				'class'       => 'large-text',
				'placeholder' => __( 'Enter Field Group name', 'mncf' ),
			),
			'#validate'   => array(
				'required' => array(
					'value' => true,
				),
			),
			'#pattern'    => $table_row,
		);
		$form['description']  = array(
			'#title'      => __( 'Description', 'mncf' ),
			'#type'       => 'textarea',
			'#id'         => 'mncf-group-description',
			'#name'       => 'mncf[group][description]',
			'#value'      => $this->update['id']
				? $this->update['description']
				: '',
			'#attributes' => array(
				'placeholder' => __( 'Enter Field Group description', 'mncf' ),
				'class'       => 'hidden js-mncf-description',
			),
			'#pattern'    => $table_row,
			'#after'      => sprintf( '<a class="js-mncf-toggle-description hidden" href="#">%s</a>', __( 'Add description', 'mncf' ) ),
			'#inline'     => true,
		);

		$form['table-1-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</tbody></table>',
		);

		/**
		 * Where to include these field group
		 */

		$form['table-2-open'] = array(
			'#type'   => 'markup',
			'#markup' => '<table class="mncf-types-form-table mncf-where-to-include widefat"><thead><tr><th colspan="2">' . __( 'Where to include this Field Group', 'mncf' ) . '</th></tr></thead><tbody>',
		);

		$form['table-2-content'] = array(
			'#type'   => 'markup',
			'#markup' => '<tr><td>'.$this->sidebar_group_conditions().'</td></tr>',
		);

		$form['table-2-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</tbody></table>',
		);


		/**
		 * fields
		 */
		$form += $this->fields();

		$form['form-close'] = array(
			'#type'    => 'markup',
			'#markup'  => '</div>',
			'_builtin' => true,
		);

		/**
		 * setup common setting for forms
		 */
		$form = $this->common_form_setup( $form );

		/**
		 * return form if current_user_can edit
		 */
		if( $this->current_user_can_edit ) {
			return $form;
		}

		return mncf_admin_common_only_show( $form );
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
	 *
	 * @return type Description.
	 */
	public function sidebar_group_conditions() {
		global $mncf;

		// if not saved yet, print message and abort
		if( $this->update['id'] === 0 ) {
			return $this->print_notice( __( 'Please save first, then you can select where to display this Field Group.', 'mncf' ), 'no-wrap', false );
		}

		// supported post types
		$post_types          = get_post_types( '', 'objects' );
		$currently_supported = array();
		$form_types          = array();

		foreach( $post_types as $post_type_slug => $post_type ) {
			// skip if post type should
			if( ! $this->show_post_type_in_ui( $post_type, $post_type_slug ) )
				continue;

			// add hidden value field
			$form_types[ $post_type_slug ] = array(
				'#type'       => 'hidden',
				'#name'       => 'mncf[group][supports][' . $post_type_slug . ']',
				'#id'         => 'mncf-form-groups-support-post-type-' . $post_type_slug,
				'#attributes' => array(
					'data-mncf-label' => $post_type->labels->name
				),
				'#value'      => '',
				'#inline'     => true,
			);
			/**
			 * updated?
			 */
			if( $this->update && ! empty( $this->update['post_types'] ) && in_array( $post_type_slug, $this->update['post_types'] ) ) {

				$form_types[ $post_type_slug ]['#value'] = $post_type_slug;
				$currently_supported[]                   = $post_type->labels->singular_name;
			}
		}
		sort( $currently_supported );

		$tax_currently_supported = array();
		$form_tax                = array();
		
		if(
			isset( $this->update['taxonomies'] )
			&& is_array( $this->update['taxonomies'] )
			&& ! empty( $this->update['taxonomies'] )
		) {
			foreach( $this->update['taxonomies'] as $taxonomy_slug => $taxonomy ) {
				foreach( $taxonomy as $key => $term ) {
					$tax_currently_supported[ $term['term_taxonomy_id'] ] = $term['name'];
					$form_tax[ $term['term_taxonomy_id'] ] = array(
						'#type'       => 'hidden',
						'#name'       => 'mncf[group][taxonomies][' . $taxonomy_slug . '][' . $term['term_taxonomy_id'] . ']',
						'#id'         => 'mncf-form-groups-support-tax-' . $term['term_taxonomy_id'],
						'#attributes' => array(
							'data-mncf-label' => $term['name']
						),
						'#value'      => $term['term_taxonomy_id'],
						'#inline'     => true,
					);
				}
			}
		}

		/*
		 * Taxonomies

		$taxonomies              = apply_filters( 'mncf_group_form_filter_taxonomies', get_taxonomies( '', 'objects' ) );
		$tax_currently_supported = array();
		$form_tax                = array();

		// hidden fields
		foreach( $taxonomies as $category_slug => $category ) {

			// system taxes to skip
			$skip_categories = array(
				'nav_menu',
				'link_category',
				'post_format'
			);

			if( in_array( $category_slug, $skip_categories ) )
				continue;


			// get all terms of tax
			$terms = apply_filters( 'mncf_group_form_filter_terms', get_terms( $category_slug, array('hide_empty' => false) ) );

			// skip if tax has no terms
			if( empty( $terms ) )
				continue;

			foreach( $terms as $term ) {
				$checked = 0;
				if( $this->update && ! empty( $this->update['taxonomies'] ) && array_key_exists( $category_slug, $this->update['taxonomies'] ) ) {
					if( array_key_exists( $term->term_taxonomy_id, $this->update['taxonomies'][ $category_slug ] ) ) {
						$checked                                            = 1;
						$tax_currently_supported[ $term->term_taxonomy_id ] = $term->name;
					}
				}
				
				error_log( 'update-taxonomies ' . print_r( $this->update['taxonomies'], true ) );
				$form_tax[ $term->term_taxonomy_id ] = array(
					'#type'       => 'hidden',
					'#name'       => 'mncf[group][taxonomies][' . $category_slug . '][' . $term->term_taxonomy_id . ']',
					'#id'         => 'mncf-form-groups-support-tax-' . $term->term_taxonomy_id,
					'#attributes' => array(
						'data-mncf-label' => $term->name
					),
					'#value'      => preg_match( '#"' . preg_quote( $term->slug, '#' ) . '"#i', json_encode( isset( $this->update['taxonomies'] )
						? $this->update['taxonomies']
						: '' ) )
						? $term->term_taxonomy_id
						: '',
					'#inline'     => true,
				);
			}

		}*/


		/**
		 * Filter templates
		 */
		$templates       = get_page_templates();
		$templates_views = get_posts( array(
			'post_type'   => 'view-template',
			'numberposts' => - 1,
			'status'      => 'publish',
		) );
		$form_templates  = array();

		/**
		 * Sanitize
		 */
		if( ! isset( $this->ct['templates'] ) ) {
			$this->ct['templates'] = array();
		}

		/**
		 * options
		 */
		$form_templates['default-template'] = array(
			'#type'       => 'hidden',
			'#value'      => in_array( 'default', $this->ct['templates'] )
				? 'default'
				: '',
			'#name'       => 'mncf[group][templates][]',
			'#inline'     => true,
			'#attributes' => array(
				'data-mncf-label' => __( 'Default Template', 'mncf' ),
			),
			'#id'         => 'mncf-form-groups-support-templates-default',
		);
		foreach( $templates as $template_name => $template_filename ) {
			$form_templates[ $template_filename ] = array(
				'#type'       => 'hidden',
				'#value'      => in_array( $template_filename, $this->ct['templates'] )
					? $template_filename
					: '',
				'#name'       => 'mncf[group][templates][]',
				'#inline'     => true,
				'#attributes' => array(
					'data-mncf-label' => $template_name,
				),
				'#id'         => sprintf( 'mncf-form-groups-support-templates-%s', sanitize_title_with_dashes( $template_filename ) ),
			);
		}
		foreach( $templates_views as $template_view ) {
			$form_templates[ $template_view->post_name ]    = array(
				'#type'   => 'hidden',
				'#value'  => in_array( $template_view->ID, $this->ct['templates'] )
					? $template_view->ID
					: '',
				'#name'   => 'mncf[group][templates][]',
				'#attributes' => array(
					'data-mncf-label' => $template_view->post_title,
				),
				'#inline' => true,
				'#id'     => sprintf( 'mncf-form-groups-support-templates-%d', $template_view->ID ),
			);
			$templates_view_list_text[ $template_view->ID ] = $template_view->post_title;
		}


		$text = '';
		if( ! empty( $this->update['templates'] ) ) {
			$text      = array();
			$templates = array_flip( $templates );
			foreach( $this->update['templates'] as $template ) {
				if( $template == 'default' ) {
					$template = __( 'Default Template', 'mncf' );
				} else if( strpos( $template, '.php' ) !== false ) {
					$template = $templates[ $template ];
				} else {
					$template = sprintf( __( 'Content Template %s', 'mncf' ), $templates_view_list_text[ $template ] );
				}
				$text[] = $template;
			}
			$text = implode( ', ', $text );
		} else {
			$text = __( 'Not Selected', 'mncf' );
		}

		// start form
		$form = array();

		// container for better styling
		$form['where-to-include-inner-container'] = array(
			'#type'   => 'markup',
			'#markup' => '<div class="mncf-where-to-include-inner"><div class="mncf-conditions-container">'
		);

		// Description: no conditions set so far
		$form['supports-msg-conditions-none'] = array(
			'#type'   => 'markup',
			'#markup' => sprintf( '<p class="mncf-fields-group-conditions-description ' . 'js-mncf-fields-group-conditions-none">%s</p>', __( 'By default <b>this group of fields</b> will appear when editing <b>all content.</b><br /><br />Select specific Post Types, Terms, Templates or set Data-dependent filters to limit the fields to specific locations and/or conditions in the Mtaandao admin.', 'mncf' ) ),
		);

		// Description: conditions set
		$form['supports-msg-conditions'] = array(
			'#type'   => 'markup',
			'#markup' => sprintf( '<p class="mncf-fields-group-conditions-description ' . 'js-mncf-fields-group-conditions-set">%s</p>', __( 'This Post Field Group is used with:', 'mncf' ) ),
		);

		// Description: Post Types set
		$form['supports-msg-conditions-post-types'] = array(
			'#type'   => 'markup',
			'#markup' => sprintf( '<p class="mncf-fields-group-conditions-description ' . 'js-mncf-fields-group-conditions-condition ' . 'js-mncf-fields-group-conditions-post-types">' . '%s <span></span></p>', __( 'Post Type(s):', 'mncf' ) ),
		);

		// Description: Terms set
		$form['supports-msg-conditions-terms'] = array(
			'#type'   => 'markup',
			'#markup' => sprintf( '<p class="mncf-fields-group-conditions-description ' . 'js-mncf-fields-group-conditions-condition ' . 'js-mncf-fields-group-conditions-terms">' . '%s <span></span></p>', __( 'Term(s):', 'mncf' ) ),
		);

		// Description: Templates set
		$form['supports-msg-conditions-templates'] = array(
			'#type'   => 'markup',
			'#markup' => sprintf( '<p class="mncf-fields-group-conditions-description ' . 'js-mncf-fields-group-conditions-condition ' . 'js-mncf-fields-group-conditions-templates">' . '%s <span></span></p>', __( 'Template(s):', 'mncf' ) ),
		);

		// Description: Data dependencies set
		$form['supports-msg-conditions-data-dependencies'] = array(
			'#type'   => 'markup',
			'#markup' => sprintf( '<p class="mncf-fields-group-conditions-description ' . 'js-mncf-fields-group-conditions-condition ' . 'js-mncf-fields-group-conditions-data-dependencies">' . '%s <span></span></p>', __( 'Additional condition(s):', 'mncf' ) ),
		);

		/**
		 * Join filter forms
		 */
		// Types
		$form += $form_types;

		// Terms
		$form += $form_tax;

		// Templates
		$form += $form_templates;

		// Data Dependencies
		$form['hide-data-dependencies-open']  = array(
			'#type'   => 'markup',
			'#markup' => '<div style="display:none;">'
		);
		$additional_filters                   = apply_filters( 'mncf_fields_form_additional_filters', array(), $this->update );
		$form                                 = $form + $additional_filters;
		$form['hide-data-dependencies-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</div>'
		);

		$form['conditions-container-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</div>'
		);

		// Edit Button
		$form['edit-button-container'] = array(
			'#type'   => 'markup',
			'#markup' => '<div class="mncf-edit-button-container">'
		);
		$form += $this->filter_wrap( 'mncf-filter-dialog-edit', array(
			'data-mncf-buttons-apply'   => esc_attr__( 'Apply', 'mncf' ),
			'data-mncf-buttons-cancel'  => esc_attr__( 'Cancel', 'mncf' ),
			'data-mncf-dialog-title'    => esc_attr__( 'Where to use this Field Group', 'mncf' ),
			'data-mncf-field-prefix'    => esc_attr( 'mncf-form-groups-support-' ),
			'data-mncf-id'              => esc_attr( $this->update['id'] ),
			'data-mncf-message-any'     => esc_attr__( 'Not Selected', 'mncf' ),
			'data-mncf-message-loading' => esc_attr__( 'Please Wait, Loading…', 'mncf' ),
		), true );
		$form['where-to-include-inner-container-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</div></div>' // also close for 'edit-button-container'
		);

		// Filter Association
		if( $this->current_user_can_edit ) {
			$count = 0;
			$count += ! empty( $this->update['post_types'] ) ? 1 : 0;
			$count += ! empty( $this->update['taxonomies'] ) ? 1 : 0;
			$count += ! empty( $this->update['templates'] ) ? 1 : 0;
			$display = $count > 1 ? '' : ' style="display:none;"';

			$form['filters_association'] = array(
				'#title'         => '<b>' . __( 'Use Field Group:', 'mncf' ) . '</b>',
				'#type'          => 'radios',
				'#name'          => 'mncf[group][filters_association]',
				'#id'            => 'mncf-fields-form-filters-association',
				'#options-after' => '',
				'#options'       => array(
					__( 'when <b>ANY</b> condition is met', 'mncf' )   => 'any',
					__( 'when <b>ALL</b> conditions are met', 'mncf' ) => 'all',
				),
				'#default_value' => ! empty( $this->update['filters_association'] )
					? $this->update['filters_association']
					: 'any',
				'#inline'        => true,
				'#before'        => '<div id="mncf-fields-form-filters-association-form"' . $display . '>',
				'#after'         => '<div id="mncf-fields-form-filters-association-summary" ' . 'style="font-style:italic;clear:both;"></div></div>',
			);
			// settings
			/*
			$settings = array(
				'mncf_filters_association_or' => __( 'This group will appear on %pt% edit pages where content belongs to Taxonomy: %tx% or Content Template is: %vt%', 'mncf' ),

				'mncf_filters_association_and' => __( 'This group will appear on %pt% edit pages where content belongs to Taxonomy: %tx% and Content Template is: %vt%', 'mncf' ),
				'mncf_filters_association_all_pages' => __( 'all', 'mncf' ),
				'mncf_filters_association_all_taxonomies' => __( 'any', 'mncf' ),
				'mncf_filters_association_all_templates' => __( 'any', 'mncf' ),
			);
			$form['filters_association']['#after'] .= sprintf(
				'<script type="text/javascript">mncf_settings = %s;</script>',
				json_encode($settings)
			);
			*/
		}

		/**
		 * setup common setting for forms
		 */
		$form = $this->common_form_setup( $form );

		/**
		 * render form
		 */
		$form = mncf_form( __FUNCTION__, $form );
		return $form->renderForm();
	}

	public function types_styling_editor() {
		$form = $this->add_admin_style( array() );

		$form = mncf_form( __FUNCTION__, $form );
		echo $form->renderForm();
	}

	/**
	 * deprecated
	 */
	private function add_admin_style( $form ) {

		$admin_styles_value = $preview_profile = $edit_profile = '';

		if( isset( $this->update['admin_styles'] ) ) {
			$admin_styles_value = $this->update['admin_styles'];
		}
		$temp = '';

		if( $this->update ) {
			require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields.php';
			// require_once MNCF_EMBEDDED_INC_ABSPATH . '/usermeta.php';
			require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
			require_once MNCF_EMBEDDED_INC_ABSPATH . '/usermeta-post.php';
			//Get sample post
			$post = query_posts( 'posts_per_page=1' );


			if( ! empty( $post ) && count( $post ) != '' ) {
				$post = $post[0];
			}
			$preview_profile = mncf_admin_post_meta_box_preview( $post, $this->update, 1 );
			$group           = $this->update;
			$group['fields'] = mncf_admin_post_process_fields( $post, $group['fields'], true, false );
			$edit_profile    = mncf_admin_post_meta_box( $post, $group, 1, true );
			add_action( 'admin_enqueue_scripts', 'mncf_admin_fields_form_fix_styles', PHP_INT_MAX );
		}

		$temp[] = array(
			'#type'          => 'radio',
			'#suffix'        => '<br />',
			'#value'         => 'edit_mode',
			'#title'         => 'Edit mode',
			'#name'          => 'mncf[group][preview]',
			'#default_value' => '',
			'#before'        => '<div class="mncf-admin-css-preview-style-edit">',
			'#inline'        => true,
			'#attributes'    => array('onclick' => 'changePreviewHtml(\'editmode\')', 'checked' => 'checked')
		);

		$temp[] = array(
			'#type'          => 'radio',
			'#title'         => 'Read Only',
			'#name'          => 'mncf[group][preview]',
			'#default_value' => '',
			'#after'         => '</div>',
			'#inline'        => true,
			'#attributes'    => array('onclick' => 'changePreviewHtml(\'readonly\')')
		);

		$temp[] = array(
			'#type'   => 'textarea',
			'#name'   => 'mncf[group][admin_html_preview]',
			'#inline' => true,
			'#id'     => 'mncf-form-groups-admin-html-preview',
			'#before' => '<h3>Field group HTML</h3>'
		);

		$temp[] = array(
			'#type'          => 'textarea',
			'#name'          => 'mncf[group][admin_styles]',
			'#inline'        => true,
			'#value'         => $admin_styles_value,
			'#default_value' => '',
			'#id'            => 'mncf-form-groups-css-fields-editor',
			'#after'         => '
                <div class="mncf-update-preview-btn"><input type="button" value="Update preview" onclick="mncfPreviewHtml()" style="float:right;" class="button-secondary"></div>
                <h3>' . __( 'Field group preview', 'mncf' ) . '</h3>
                <div id="mncf-update-preview-div">Preview here</div>
                <script type="text/javascript">
var mncfReadOnly = ' . json_encode( base64_encode( $preview_profile ) ) . ';
var mncfEditMode = ' . json_encode( base64_encode( $edit_profile ) ) . ';
var mncfDefaultCss = ' . json_encode( base64_encode( $admin_styles_value ) ) . ';
        </script>
        ',
			'#before'        => sprintf( '<h3>%s</h3>', __( 'Your CSS', 'mncf' ) ),
		);

		$admin_styles                                                       = _mncf_filter_wrap( 'admin_styles', __( 'Admin styles for fields:', 'mncf' ), '', '', $temp, __( 'Open style editor', 'mncf' ) );
		$form[ 'p_wrap_1_' . mncf_unique_id( serialize( $admin_styles ) ) ] = array(
			'#type'   => 'markup',
			'#markup' => '<p class="mncf-filter-wrap">',
		);
		$form                                                               = $form + $admin_styles;

		return $form;
	}


	/**
	 * Get description of tabs that will be displayed on the filter dialog.
	 *
	 * @return array[]
	 */
	protected function get_tabs_for_filter_dialog() {
		$tabs = array(
			'post-types'     => array(
				'title' => __( 'Post Types', 'mncf' ),
			),
			'taxonomies'     => array(
				'title' => __( 'Taxonomies', 'mncf' ),
			),
			'templates'      => array(
				'title' => __( 'Templates', 'mncf' )
			),
			'data-dependant' => array(
				'title' => __( 'Data-dependant', 'mncf' )
			)
		);

		return $tabs;
	}

	/**
	 * Summary.
	 *
	 * Description.
	 *
	 * @since x.x.x
	 * @access (for functions: only use if private)
	 *
	 * @param $filter
	 * @param $form
	 */
	protected function form_add_filter_dialog( $filter, &$form ) {
		global $mncf;
		switch( $filter ) {
			/**
			 * post types
			 */
			case 'post-types':
				$form['post-types-description'] = array(
					'#type'   => 'markup',
					'#markup' => '<p class="description js-mncf-description">' . __( 'Select specific Post Types that you want to use with this Field Group:', 'mncf' ) . '</p>'
				);
				$form['post-types-ul-open']     = array(
					'#type'   => 'markup',
					'#markup' => '<ul>',
				);

				$currently_supported = mncf_admin_get_post_types_by_group( sanitize_text_field( $_REQUEST['id'] ) );

				$post_types = get_post_types( array('show_ui' => true), 'objects' );
				ksort( $post_types );
				foreach( $post_types as $post_type_slug => $post_type ) {
					if( in_array( $post_type_slug, $mncf->excluded_post_types ) ) {
						continue;
					}
					$form[ 'option_' . $post_type_slug ] = array(
						'#name'          => esc_attr( $post_type_slug ),
						'#type'          => 'checkbox',
						'#value'         => 1,
						'#default_value' => $this->ajax_filter_default_value( $post_type_slug, $currently_supported, 'post-type' ),
						'#inline'        => true,
						'#before'        => '<li>',
						'#after'         => '</li>',
						'#title'         => $post_type->label,
						'#attributes'    => array(
							'data-mncf-value'  => esc_attr( $post_type_slug ),
							'data-mncf-prefix' => 'post-type-'
						),
					);
				}
				$form['post-types-ul-close'] = array(
					'#type'   => 'markup',
					'#markup' => '</ul><br class="clear" />',
				);
				break;

			/**
			 * taxonomies
			 */
			case 'taxonomies':
				$form['taxonomies-description'] = array(
					'#type'   => 'markup',
					'#markup' => '<p class="description js-mncf-description">' . __( 'Select ' . 'specific Terms from Taxonomies below that you want to use with this Field Group:', 'mncf' ) . '</p>'
				);

				include_once MNCF_INC_ABSPATH . '/fields.php';
				$currently_supported = mncf_admin_get_taxonomies_by_group( $_REQUEST['id'] );
				$taxonomies          = apply_filters( 'mncf_group_form_filter_taxonomies', get_taxonomies( '', 'objects' ) );
				$taxonomies_settings = get_option( MNCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );

				$form['taxonomies-div-open'] = array(
					'#type'   => 'markup',
					'#markup' => '<div id="poststuff" class="meta-box-sortables">',
				);
				foreach( $taxonomies as $category_slug => $category ) {
					if( $category_slug == 'nav_menu' || $category_slug == 'link_category' || $category_slug == 'post_format' || ( isset( $taxonomies_settings[ $category_slug ]['disabled'] ) && $taxonomies_settings[ $category_slug ]['disabled'] == 1 ) || empty( $category->labels->name ) ) {
						continue;
					}

					$terms = apply_filters( 'mncf_group_form_filter_terms', get_terms( $category_slug, array('hide_empty' => false) ) );
					if( empty( $terms ) ) {
						continue;
					}

					$form_tax                               = array();
					$form_tax[ $category_slug . '-search' ] = array(
						'#type'       => 'textfield',
						'#name'       => $category_slug . '-search',
						'#attributes' => array(
							'class'       => 'widefat js-mncf-taxonomy-search',
							'placeholder' => esc_attr__( 'Search', 'mncf' ),
						),
					);
					foreach( $terms as $term ) {
						$form_tax[ $term->term_taxonomy_id ] = array(
							'#type'          => 'checkbox',
							'#name'          => esc_attr( sprintf( 'tax-%d', $term->term_taxonomy_id ) ),
							'#value'         => 1,
							'#inline'        => true,
							'#before'        => '<li>',
							'#after'         => '</li>',
							'#title'         => $term->name,
							'#default_value' => $this->ajax_filter_default_value( $term->term_taxonomy_id, $currently_supported, 'taxonomy', $category_slug ),
							'#attributes'    => array(
								'data-mncf-value'  => esc_attr( $term->term_taxonomy_id ),
								'data-mncf-slug'   => esc_attr( $term->slug ),
								'data-mncf-name'   => esc_attr( $term->name ),
								'data-mncf-taxonomy-slug' => esc_attr( $category_slug ),
								'data-mncf-prefix' => ''
							),
						);
					}
					$form += $this->ajax_filter_add_box( $category_slug, $category->labels->name, $form_tax );
				}
				$form['taxonomies-div-close'] = array(
					'#type'   => 'markup',
					'#markup' => '</div>',
				);
				break;

			/**
			 * templates
			 */
			case 'templates':
				$form['templates-description'] = array(
					'#type'   => 'markup',
					'#markup' => '<p class="description js-mncf-description">' . __( 'Select specific Template that you want to use with this Field Group:', 'mncf' ) . '</p>'
				);

				$form['templates-ul-open'] = array(
					'#type'   => 'markup',
					'#markup' => '<ul>',
				);
				include_once MNCF_INC_ABSPATH . '/fields.php';
				$currently_supported      = mncf_admin_get_templates_by_group( sanitize_text_field( $_REQUEST['id'] ) );
				$templates                = get_page_templates();
				$templates_views          = get_posts( array(
					'post_type'   => 'view-template',
					'numberposts' => - 1,
					'status'      => 'publish',
				) );
				$form['default-template'] = array(
					'#type'          => 'checkbox',
					'#default_value' => $this->ajax_filter_default_value( 'default', $currently_supported, 'template' ),
					'#name'          => 'default',
					'#value'         => 1,
					'#inline'        => true,
					'#title'         => __( 'Default', 'mncf' ),
					'#before'        => '<li>',
					'#after'         => '</li>',
					'#attributes'    => array(
						'data-mncf-value'  => esc_attr( 'default' ),
						'data-mncf-prefix' => 'templates-'
					),
				);
				foreach( $templates as $template_name => $template_filename ) {
					$form[ $template_filename ] = array(
						'#type'          => 'checkbox',
						'#default_value' => $this->ajax_filter_default_value( $template_filename, $currently_supported, 'template' ),
						'#value'         => 1,
						'#inline'        => true,
						'#title'         => $template_name,
						'#name'          => sanitize_title_with_dashes( $template_filename ),
						'#before'        => '<li>',
						'#after'         => '</li>',
						'#attributes'    => array(
							'data-mncf-value'  => esc_attr( $template_filename ),
							'data-mncf-prefix' => 'templates-'
						),
					);
				}
				foreach( $templates_views as $template_view ) {
					$form[ $template_view->post_name ] = array(
						'#type'          => 'checkbox',
						'#value'         => 1,
						'#default_value' => $this->ajax_filter_default_value( $template_view->ID, $currently_supported, 'template' ),
						'#inline'        => true,
						'#title'         => apply_filters( 'the_title', $template_view->post_title ),
						'#name'          => $template_view->ID,
						'#before'        => '<li>',
						'#after'         => '</li>',
						'#attributes'    => array(
							'data-mncf-value' => esc_attr( $template_view->ID ),
							'data-mncf-prefix' => 'templates-'
						),
					);
				}
				$form['templates-ul-close'] = array(
					'#type'   => 'markup',
					'#markup' => '</ul><br class="clear" />',
				);
				break;

			/**
			 * data dependant
			 */
			case 'data-dependant':
				require_once MNCF_INC_ABSPATH . '/classes/class.types.fields.conditional.php';
				$data_dependant = new Types_Fields_Conditional();
				$form += $data_dependant->group_condition_get( true );
				break;
		}
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
	 *
	 * @return type Description.
	 */
	private function ajax_filter_add_box( $slug, $title, $data ) {
		$form = array(
			$slug . '-begin' => array(
				'#type'   => 'markup',
				'#markup' => sprintf( '<div class="postbox"><div class="handlediv" title="%s"><br></div><h3 class=""><span>%s</span></h3><div class="inside"><ul>', esc_attr__( 'Click to toggle', 'mncf' ), $title )
			)
		);
		$form += $data;
		$form[ $slug . '-end' ] = array(
			'#type'   => 'markup',
			'#markup' => '</ul><br class="clear" /></div></div>',
		);

		return $form;
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
	 * @param string $value Description.
	 * @param array $currently_supported Optional. Description.
	 * @param boolean|string $type Optional. Description.
	 * @param boolean|string $type_category Optional. Description.
	 *
	 * @return type Description.
	 */
	private function ajax_filter_default_value(
		$value, $currently_supported = array(), $type = false, $type_category = false
	) {
		if( $type && isset( $_REQUEST['all_fields'] ) && is_array( $_REQUEST['all_fields'] ) ) {
			switch( $type ) {
				case 'post-type':
					if( isset( $_REQUEST['all_fields']['mncf']['group']['supports'] ) && in_array( $value, $_REQUEST['all_fields']['mncf']['group']['supports'] ) ) {
						return true;
					}
					break;
				case 'taxonomy':
					if( $type_category && isset( $_REQUEST['all_fields']['mncf']['group']['taxonomies'][ $type_category ] ) && in_array( $value, $_REQUEST['all_fields']['mncf']['group']['taxonomies'][ $type_category ] ) ) {
						return true;
					}
					break;
				case 'template':
					if( isset( $_REQUEST['all_fields']['mncf']['group']['templates'] ) && in_array( $value, $_REQUEST['all_fields']['mncf']['group']['templates'] ) ) {
						return true;
					}
					break;
			}
			// not selected
			return false;
		}

		if( isset( $_REQUEST['current'] ) ) {
			if( is_array( $_REQUEST['current'] ) && in_array( $value, $_REQUEST['current'] ) ) {
				return true;
			}
		} else if( $currently_supported && ! empty( $currently_supported ) && in_array( $value, $currently_supported ) ) {
			return true;
		}

		return false;
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
	 *
	 * @return type Description.
	 */
	protected function save() {
		// abort if no post data
		if( ! isset( $_POST['mncf'] ) )
			return;

		// abort when no group id isset
		if( ! isset( $_POST['mncf']['group']['id'] ) )
			$this->verification_failed_and_die( 1 );

		// nonce verification
		$nonce_name = $this->get_nonce_action( $_POST['mncf']['group']['id'] );
		if( ! mn_verify_nonce( $_REQUEST['mncf_save_group_nonce'], $nonce_name ) )
			$this->verification_failed_and_die( 2 );

		// get group_id
		$group_id = mncf_admin_fields_save_group( $_POST['mncf']['group'], TYPES_CUSTOM_FIELD_GROUP_CPT_NAME, 'custom' );

		// abort if does not exist
		if( empty( $group_id ) )
			return;

		$_REQUEST[ $this->get_id ] = $group_id;

		// save
		$this->save_group_fields( $group_id );
		$this->save_condition_post_types( $group_id );
		$this->save_condition_templates( $group_id );
		$this->save_condition_taxonomies( $group_id );

		do_action( 'types_fields_group_saved', $group_id );
		do_action( 'types_fields_group_post_saved', $group_id );

		// do not use these hooks anymore
		do_action( 'mncf_fields_group_saved', $group_id );
		do_action( 'mncf_postmeta_fields_group_saved', $group_id );

		// redirect
		$args = array(
			'page'        => 'mncf-edit',
			$this->get_id => $group_id
		);

		if( isset( $_GET['ref'] ) )
			$args['ref'] = $_GET['ref'];

		mn_safe_redirect( esc_url_raw( add_query_arg( $args, admin_url( 'admin.php' ) ) ) );

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
	 *
	 * @return type Description.
	 */
	private function save_group_fields( $group_id ) {
		if( empty( $_POST['mncf']['fields'] ) ) {
			delete_post_meta( $group_id, '_mn_types_group_fields' );

			return;
		}
		$fields = array();

		// First check all fields
		foreach( $_POST['mncf']['fields'] as $key => $field ) {
			$field = mncf_sanitize_field( $field );
			$field = apply_filters( 'mncf_field_pre_save', $field );
			if( ! empty( $field['is_new'] ) ) {
				// Check name and slug
				if( mncf_types_cf_under_control( 'check_exists', sanitize_title( $field['name'] ) ) ) {
					$this->triggerError();
					mncf_admin_message( sprintf( __( 'Field with name "%s" already exists', 'mncf' ), $field['name'] ), 'error' );

					return $form;
				}
				if( isset( $field['slug'] ) && mncf_types_cf_under_control( 'check_exists', sanitize_title( $field['slug'] ) ) ) {
					$this->triggerError();
					mncf_admin_message( sprintf( __( 'Field with slug "%s" already exists', 'mncf' ), $field['slug'] ), 'error' );

					return $form;
				}
			}
			$field['submit-key'] = $key;
			// Field ID and slug are same thing
			$field_id = mncf_admin_fields_save_field( $field );
			if( is_mn_error( $field_id ) ) {
				$this->triggerError();
				mncf_admin_message( $field_id->get_error_message(), 'error' );

				return;
			}
			if( ! empty( $field_id ) ) {
				$fields[] = $field_id;
			}
			// MNML
			/** @var string $field_id */
			if( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '<' ) ) {
				if( function_exists( 'mnml_cf_translation_preferences_store' ) ) {
					$real_custom_field_name = mncf_types_get_meta_prefix( mncf_admin_fields_get_field( $field_id ) ) . $field_id;
					mnml_cf_translation_preferences_store( $key, $real_custom_field_name );
				}
			}
		}
		mncf_admin_fields_save_group_fields( $group_id, $fields );
	}

	/**
	 * @param $post_type
	 * @param $post_type_slug
	 * @param $mncf
	 *
	 * @return bool
	 */
	private function show_post_type_in_ui( $post_type, $post_type_slug ) {
		global $mncf;

		return $post_type->show_ui && ! in_array( $post_type_slug, $mncf->excluded_post_types );
	}

	/**
	 * Summary.
	 *
	 * Description.
	 *
	 * @since x.x.x
	 * @access (for functions: only use if private)
	 *
	 * @param $group_id
	 */
	private function save_condition_post_types( $group_id ) {
		$post_types = isset( $_POST['mncf']['group']['supports'] )
			? $_POST['mncf']['group']['supports']
			: array();
		mncf_admin_fields_save_group_post_types( $group_id, $post_types );
	}

	/**
	 * @param $group_id
	 */
	private function save_condition_taxonomies( $group_id ) {
		$post_taxonomies = isset( $_POST['mncf']['group']['taxonomies'] )
			? $_POST['mncf']['group']['taxonomies']
			: array();

		$taxonomies = array();
		foreach( $post_taxonomies as $taxonomy ) {
			foreach( $taxonomy as $tax => $term ) {
				if( ! empty( $term ) )
					$taxonomies[] = $term;
			}
		}

		mncf_admin_fields_save_group_terms( $group_id, $taxonomies );
	}

	/**
	 * @param $group_id
	 */
	private function save_condition_templates( $group_id ) {
		$post_templates = (
			isset( $_POST['mncf']['group']['templates'] )
			&& ! empty( $_POST['mncf']['group']['templates'] )
		)
			? $_POST['mncf']['group']['templates']
			: array();

		mncf_admin_fields_save_group_templates( $group_id, $post_templates );
	}
}

