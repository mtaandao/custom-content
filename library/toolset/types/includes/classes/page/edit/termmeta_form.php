<?php

/**
 * Handles rendering of the form content on the Edit Term Fields Group page.
 *
 * Based on legacy code, it is basically just a modified version of Types_Admin_Edit_Custom_Fields_Group.
 * I still struggle to understand what it does exactly - consider it a temporary solution.
 *
 * @since 1.9
 */
final class MNCF_Page_Edit_Termmeta_Form extends Types_Admin_Edit_Fields {


	/** @var null|Types_Field_Group_Term Currently edited field group. */
	private $field_group = null;


	public function __construct() {
		parent::__construct();

		$this->get_id = 'group_id';
		$this->type = MNCF_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION;

		add_action('mn_ajax_mncf_ajax_filter', array($this, 'ajax_filter_dialog'));
	}


	public function init_admin()
	{
		$this->post_type = Types_Field_Group_Term::POST_TYPE;

		$this->init_hooks();

		$this->boxes = array(
			'submitdiv' => array(
				'callback' => array($this, 'box_submitdiv'),
				'title' => __('Save', 'mncf'),
				'default' => 'side',
				'priority' => 'high',
			),
			/*
			'types_where' => array(
				'callback' => array($this, 'box_where'),
				'title' => __('Where to include this Field Group', 'mncf'),
				'default' => 'side',
			),
			*/
		);
		$this->boxes = apply_filters('mncf_meta_box_order_defaults', $this->boxes, $this->post_type);
		$this->boxes = apply_filters('mncf_meta_box_custom_field', $this->boxes, $this->post_type);

		// This should have been defined as a dependency somewhere.
		mn_enqueue_script( 'jquery-ui-dialog' );
		mn_enqueue_style('mn-jquery-ui-dialog');
	}


	/**
	 * Get the purpose of the page that is being displayed, depending on provided data and user capabilities.
	 *
	 * @return string 'add'|'edit'|'view'. Note that 'edit' is also returned when the new group is about to be created,
	 * but it doesn't exist yet (has no ID).
	 */
	public function get_page_purpose() {

		$role_type = 'term-field';
		$group_id = (int) mncf_getget( 'group_id' );
		$is_group_specified = ( 0 !=  $group_id );

		if( $is_group_specified ) {
			if( MNCF_Roles::user_can_edit( $role_type, array( 'id' => $group_id ) ) ) {
				$purpose = 'edit';
			} else {
				$purpose = 'view';
			}
		} else {
			if( $this->is_there_something_to_save() ) {
				if( MNCF_Roles::user_can_create( $role_type ) ) {
					// We're creating a group now, the page will be used for editing it.
					$purpose = 'edit';
				} else {
					$purpose = 'view';
				}
			} else if( MNCF_Roles::user_can_create( $role_type ) ) {
				$purpose = 'add';
			} else {
				$purpose = 'view'; // Invalid state
			}
		}

		return $purpose;
	}


	/**
	 * Obtain ID of current field group by any means necessary.
	 *
	 * Tries to grab the ID from (a) cache, (b) _POST argument during AJAX call, (c) generally used _REQUEST argument with ID.
	 *
	 * @return int Current field group ID or zero if not found.
	 */
	private function get_field_group_id() {
		if( null != $this->field_group ) {
			return $this->field_group->get_id();
		} elseif( mncf_getpost( 'action' ) == 'mncf_ajax_filter' ) {
			return (int) mncf_getpost( 'id' );
		} elseif( isset( $_REQUEST[ $this->get_id ] ) ) {
			return (int) $_REQUEST[ $this->get_id ];
		} else {
			return 0;
		}
	}


	private function load_field_group( $field_group_id ) {
		return Types_Field_Group_Term_Factory::load( $field_group_id );
	}


	private function get_field_group() {
		if( null == $this->field_group ) {
			$this->field_group = $this->load_field_group( $this->get_field_group_id() );
		}
		return $this->field_group;
	}


	/**
	 * Initialize and render the form.
	 *
	 * Determine if existing field group is being edited or if we're creating a new one.
	 * If we're reloading the edit page after clicking Save button, save changes to database.
	 * Generate an array with form field definitions (setup the form).
	 * Fill $this->update with field group data.
	 *
	 * @return array
	 */
	public function form()
	{
		$this->save();

		$this->current_user_can_edit = MNCF_Roles::user_can_create('term-field');

		$field_group_id = (int) mncf_getarr( $_REQUEST, $this->get_id, 0 );

		// If it's update, get data
		if ( 0 != $field_group_id ) {

			$this->update = mncf_admin_fields_get_group( $field_group_id, Types_Field_Group_Term::POST_TYPE );

			if ( null == $this->get_field_group() ) {

				$this->update = false;
				mncf_admin_message( sprintf( __( "Group with ID %d do not exist", 'mncf' ), $field_group_id ) );

			} else {
				$this->current_user_can_edit = MNCF_Roles::user_can_edit( 'custom-field', $this->update );

				$this->update['fields'] = mncf_admin_fields_get_fields_by_group(
					$field_group_id, 'slug', false, true, false,
					Types_Field_Group_Term::POST_TYPE,
					MNCF_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION
				);
			}
		}

		// sanitize id
		$this->update['id'] = $this->get_field_group_id();

		// copy update to ct... dafuq is "ct"?
		$this->ct = $this->update;

		$form = $this->prepare_screen();

		$form['_mnnonce_mncf'] = array(
			'#type' => 'markup',
			'#markup' => mn_nonce_field('mncf_form_fields', '_mnnonce_mncf', true, false),
		);


		// nonce depend on group id
		$nonce_name = $this->get_nonce_action($this->update['id']);
		$form['_mnnonce_'.$this->post_type] = array(
			'#type' => 'markup',
			'#markup' => mn_nonce_field(
				$nonce_name,
				'mncf_save_group_nonce',
				true,
				false
			),
		);

		$form['form-open'] = array(
			'#type' => 'markup',
			'#markup' => sprintf(
				'<div id="post-body-content" class="%s">',
				$this->current_user_can_edit? '':'mncf-types-read-only'
			),
		);

		$form[ $this->get_id ]  = array(
			'#type' => 'hidden',
			'#name' => 'mncf[group][id]',
			'#value' => $this->update['id'],
		);

		$form['table-1-open'] = array(
			'#type' => 'markup',
			'#markup' => '<table id="mncf-types-form-name-table" class="mncf-types-form-table widefat js-mncf-slugize-container"><thead><tr><th colspan="2">' . __( 'Name and description', 'mncf' ) . '</th></tr></thead><tbody>',
		);
		$table_row = '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>';
		$form['title'] = array(
			'#title' => sprintf(
				'%s <b>(%s)</b>',
				__( 'Name', 'mncf' ),
				__( 'required', 'mncf' )
			),
			'#type' => 'textfield',
			'#name' => 'mncf[group][name]',
			'#id' => 'mncf-group-name',
			'#value' => $this->update['id'] ? $this->update['name']:'',
			'#inline' => true,
			'#attributes' => array(
				'class' => 'large-text',
				'placeholder' => __( 'Enter Field Group name', 'mncf' ),
			),
			'#validate' => array(
				'required' => array(
					'value' => true,
				),
			),
			'#pattern' => $table_row,
		);
		$form['description'] = array(
			'#title' => __( 'Description', 'mncf' ),
			'#type' => 'textarea',
			'#id' => 'mncf-group-description',
			'#name' => 'mncf[group][description]',
			'#value' => $this->update['id'] ? $this->update['description']:'',
			'#attributes' => array(
				'placeholder' =>  __( 'Enter Field Group description', 'mncf' ),
				'class' => 'hidden js-mncf-description',
			),
			'#pattern' => $table_row,
			'#after' => sprintf(
				'<a class="js-mncf-toggle-description hidden" href="#">%s</a>',
				__('Add description', 'mncf')
			),
			'#inline' => true,
		);

		$form['table-1-close'] = array(
			'#type' => 'markup',
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
			'#markup' => '<tr><td>'.$this->box_where().'</td></tr>',
		);

		$form['table-2-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</tbody></table>',
		);

		$form += $this->fields();

		$form['form-close'] = array(
			'#type' => 'markup',
			'#markup' => '</div>',
			'_builtin' => true,
		);

		// setup common setting for forms
		$form = $this->common_form_setup($form);

		if ( $this->current_user_can_edit) {
			return $form;
		}

		return mncf_admin_common_only_show($form);
	}


	private function get_relevant_taxonomy_slugs() {
		$taxonomy_slugs = apply_filters( 'mncf_group_form_filter_taxonomy_slugs', get_taxonomies() );
		return array_diff(
			array_unique( mncf_ensarr( $taxonomy_slugs ) ),
			array( 'nav_menu', 'link_category', 'post_format' )
		);
	}


	/**
	 * Render content of a metabox for associating the field group with taxonomies.
	 */
	public function box_where() {

		// Filter taxonomies

		$taxonomy_slugs = $this->get_relevant_taxonomy_slugs();
		$currently_supported_taxonomy_slugs = ( $this->get_field_group_id() != 0 ? $this->field_group->get_associated_taxonomies() : array() );

		$fields_to_clear_class = 'js-mncf-filter-support-taxonomy';

		$form_tax = array();
		foreach ( $taxonomy_slugs as $taxonomy_slug ) {

			$form_tax[ $taxonomy_slug ] = array(
				'#type' => 'hidden',
				'#name' => sprintf( 'mncf[group][taxonomies][%s]', esc_attr( $taxonomy_slug ) ),
				'#id' => 'mncf-form-groups-support-taxonomy-' . $taxonomy_slug,
				'#attributes' => array(
					'class' => $fields_to_clear_class,
					'data-mncf-label' => Types_Utils::taxonomy_slug_to_label( $taxonomy_slug )
				),
				'#value' => ( in_array( $taxonomy_slug, $currently_supported_taxonomy_slugs ) ) ? $taxonomy_slug : '',
				'#inline' => true
			);
		}

		// Edit Button
		$form_tax['edit-button-container'] = array(
			'#type'   => 'markup',
			'#markup' => '<div class="mncf-edit-button-container">'
		);

		// generate wrapper and button
		$form_tax += $this->filter_wrap(
			'mncf-filter-dialog-edit',
			array(
				'data-mncf-buttons-apply' => esc_attr__( 'Apply', 'mncf' ),
				'data-mncf-buttons-cancel' => esc_attr__( 'Cancel', 'mncf' ),
				'data-mncf-dialog-title' => esc_attr__( 'Where to use this Field Group', 'mncf' ),
				'data-mncf-field-prefix' => esc_attr( 'mncf-form-groups-support-taxonomy-' ),
				'data-mncf-field-to-clear-class' => esc_attr( '.' . $fields_to_clear_class ),
				'data-mncf-id' => esc_attr( $this->update['id'] ),
				'data-mncf-message-any' => esc_attr__( 'None', 'mncf' ),
				'data-mncf-message-loading' => esc_attr__( 'Please wait, Loading…', 'mncf' ),
			),
			true,
			false
		);

		$form = array();

		// container for better styling
		$form['where-to-include-inner-container'] = array(
			'#type'   => 'markup',
			'#markup' => '<div class="mncf-where-to-include-inner"><div class="mncf-conditions-container">'
		);

		// Now starting form
		$form['supports-table-open'] = array(
			'#type' => 'markup',
			'#markup' => sprintf(
				'<p class="mncf-fields-group-conditions-description js-mncf-fields-group-conditions-none">%s</p>',
				__( 'By default <b>this group of fields</b> will appear when editing <b>all terms from all Taxonomies.</b><br /><br />Select specific Taxonomies to use these fields with.', 'mncf' )
			),
		);

		// Description: Terms set
		$form['supports-msg-conditions-taxonomies'] = array(
			'#type'   => 'markup',
			'#markup' => sprintf(
				'<p class="mncf-fields-group-conditions-description js-mncf-fields-group-conditions-condition js-mncf-fields-group-conditions-taxonomies">%s <span></span></p>',
				__( 'This Term Field Group is used with the following Taxonomies:', 'mncf' )
			),
		);

		$form['conditions-container-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</div>'
		);


		// Terms
		$form = $form + $form_tax;

		$form['where-to-include-inner-container-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</div></div>' // also close for 'edit-button-container'
		);

		// setup common setting for forms
		$form = $this->common_form_setup( $form );

		// render form
		$form = mncf_form( __FUNCTION__, $form );
		return $form->renderForm();
	}


	private function ajax_filter_default_value($value, $currently_supported = array(), $type = false) {
		if( $type && isset( $_REQUEST['all_fields'] ) && is_array( $_REQUEST['all_fields'] ) ) {
			switch( $type ) {
				case 'taxonomies-for-termmeta':
					$selected_taxonomies = mncf_ensarr( mncf_getnest( $_REQUEST, array( 'all_fields', 'mncf', 'group', 'taxonomies' ) ) );
					if( in_array( $value, array_keys( $selected_taxonomies ) ) && true == $selected_taxonomies[ $value ] ) {
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


	protected function is_there_something_to_save() {
		$mncf_data = mncf_getpost( 'mncf', null );
		return ( null != $mncf_data );
	}


	/**
	 * Save field group data from $_POST to database when the form is submitted.
	 */
	protected function save() {

		if( !$this->is_there_something_to_save() ) {
			return;
		}

		$mncf_data = mncf_getpost( 'mncf', null );

		// check incoming $_POST data
		$group_id = mncf_getnest( $_POST, array( 'mncf', 'group', 'id' ), null );
		if ( null === $group_id ) { // probably can be 0, which is valid
			$this->verification_failed_and_die( 1 );
		}

		// nonce verification
		$nonce_name = $this->get_nonce_action( $group_id );
		$nonce = mncf_getpost( 'mncf_save_group_nonce' );
		if ( ! mn_verify_nonce( $nonce, $nonce_name ) ) {
			$this->verification_failed_and_die( 2 );
		}

		// save group data to the database (sanitizing there)
		$group_id = mncf_admin_fields_save_group( mncf_getarr( $mncf_data, 'group', array() ), Types_Field_Group_Term::POST_TYPE, 'term' );
		$field_group = $this->load_field_group( $group_id );

		if ( null == $field_group ) {
			return;
		}

		// Why are we doing this?!
		$_REQUEST[ $this->get_id ] = $group_id;

		// save taxonomies; sanitized on a lower level before saving to the database
		$taxonomies_post = mncf_getnest( $mncf_data, array( 'group', 'taxonomies' ), array() );
		$field_group->update_associated_taxonomies( $taxonomies_post );

		$this->save_filter_fields($group_id, mncf_getarr( $mncf_data, 'fields', array() ));

		do_action( 'types_fields_group_saved', $group_id );
		do_action( 'types_fields_group_term_saved', $group_id );

		// Redirect to edit page so we stay on it even if user reloads it
		// and to present admin notices
		mn_safe_redirect(
			esc_url_raw(
				add_query_arg(
					array( 'page' => MNCF_Page_Edit_Termmeta::PAGE_NAME, $this->get_id => $group_id ),
					admin_url( 'admin.php' )
				)
			)
		);

		die();
	}


	private function save_filter_fields( $group_id, $fields_data )
	{

		if ( empty( $fields_data ) ) {
			delete_post_meta( $group_id, '_mn_types_group_fields' );
			return;
		}

		$fields = array();

		// First check all fields
		foreach ( $fields_data as $field_key => $field ) {

			$field = mncf_sanitize_field($field);
			$field = apply_filters( 'mncf_field_pre_save', $field );

			if ( !empty( $field['is_new'] ) ) {

				// Check name and slug
				if ( mncf_types_cf_under_control(
					'check_exists',
					sanitize_title( $field['name'] ),
					Types_Field_Group_Term::POST_TYPE,
					MNCF_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION
				) ) {
					$this->triggerError();
					mncf_admin_message( sprintf( __( 'Field with name "%s" already exists', 'mncf' ), $field['name'] ), 'error' );
					return;
				}

				if ( isset( $field['slug'] )
					&& mncf_types_cf_under_control(
						'check_exists',
						sanitize_title( $field['slug'] ),
						Types_Field_Group_Term::POST_TYPE,
						MNCF_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION
					)
				) {
					$this->triggerError();
					mncf_admin_message( sprintf( __( 'Field with slug "%s" already exists', 'mncf' ), $field['slug'] ), 'error' );
					return;
				}
			}

			$field['submit-key'] = sanitize_text_field( $field_key );

			// Field ID and slug are same thing
			$field_slug = mncf_admin_fields_save_field(
				$field,
				Types_Field_Group_Term::POST_TYPE,
				MNCF_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION
			);


			if ( is_mn_error( $field_slug ) ) {
				$this->triggerError();
				mncf_admin_message( $field_slug->get_error_message(), 'error' );
				return;
			}


			if ( !empty( $field_slug ) ) {
				$fields[] = $field_slug;
			}


			// MNML
			if ( defined('ICL_SITEPRESS_VERSION') && version_compare ( ICL_SITEPRESS_VERSION, '3.2', '<' ) ) {
				if ( function_exists( 'mnml_cf_translation_preferences_store' ) ) {
					$real_custom_field_name = mncf_types_get_meta_prefix(
						mncf_admin_fields_get_field( $field_slug, false, false, false, MNCF_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION )
					) . $field_slug;
					mnml_cf_translation_preferences_store( $field_key, $real_custom_field_name );
				}
			}
		}

		mncf_admin_fields_save_group_fields(
			$group_id, $fields, false,
			Types_Field_Group_Term::POST_TYPE,
			MNCF_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION
		);
	}


	/**
	 * Update the "form" data for the filter dialog.
	 *
	 * @param string $filter Filter name. Only 'taxonomies-for-meta' is supported here.
	 * @param array $form Form data that will be modified.
	 */
	protected function form_add_filter_dialog( $filter, &$form ) {

		switch( $filter ) {
			case 'taxonomies-for-termmeta':
				include_once MNCF_INC_ABSPATH . '/fields.php'; // Oh dear god, why?

				$taxonomy_slugs = $this->get_relevant_taxonomy_slugs();
				ksort( $taxonomy_slugs );

				$field_group = $this->get_field_group();
				// Can be null when creating new field group
				$currently_supported_taxonomy_slugs = ( null == $field_group ) ? array() : $field_group->get_associated_taxonomies();

				// Setup the form
				$form += $this->add_description(
					// String below is split in two to prevent PHPStorm from detecting it as a MySQL query.
					__( 'Select ' . 'specific Taxonomies that you want to use with this Field Group:', 'mncf' )
				);

				$form['ul-begin'] = array(
					'#type' => 'markup',
					'#markup' => '<ul>',
				);

				// Add a checkbox for each taxonomy
				foreach ( $taxonomy_slugs as $taxonomy_slug ) {
					$label = Types_Utils::taxonomy_slug_to_label( $taxonomy_slug );
					$form[ $taxonomy_slug ] = array(
						'#name' => esc_attr( $taxonomy_slug ),
						'#type' => 'checkbox',
						'#value' => 1,
						'#default_value' => $this->ajax_filter_default_value( $taxonomy_slug, $currently_supported_taxonomy_slugs, 'taxonomies-for-termmeta' ),
						'#inline' => true,
						'#before' => '<li>',
						'#after' => '</li>',
						'#title' => $label,
						'#attributes' => array(
							'data-mncf-value' => esc_attr( $taxonomy_slug ),
							'data-mncf-name' => $label,
							'data-mncf-prefix' => 'taxonomy-'
						),
					);
				}

				$form['ul-end'] = array(
					'#type' => 'markup',
					'#markup' => '</ul><br class="clear" />',
				);
				break;
		}

	}


	/**
	 * Get description of tabs that will be displayed on the filter dialog.
	 *
	 * @return array[]
	 */
	protected function get_tabs_for_filter_dialog() {
		$tabs = array(
			'taxonomies-for-termmeta' => array(
				'title' => __( 'Taxonomies', 'mncf' ),
			)
		);

		return $tabs;

	}

}