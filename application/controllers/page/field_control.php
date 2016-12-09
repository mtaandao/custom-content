<?php

/**
 * "Field Control" page controller.
 *
 * @since 2.0
 */
final class Types_Page_Field_Control extends Types_Page_Abstract {
	
	
	/**	Name of the URL parameter for the field domain. */
	const PARAM_DOMAIN = 'domain';


	// Screen options...
	const SCREEN_OPTION_PER_PAGE_NAME = 'types_field_control_fields_per_page';
	const SCREEN_OPTION_PER_PAGE_DEFAULT_VALUE = 20;


	/** @var string Current field domain. Will be populated during self::prepare(). Never access directly. */
	private $current_domain;


	private static $instance;

	
	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	private function __construct() { }


	private function __clone() { }


	/**
	 * Generate full URL to the Field Control page for given field domain.
	 *
	 * @param string $domain
	 * @return null|string The URL or null if the domain was invalid.
	 * @since 2.0
	 */
	public static function get_page_url( $domain ) {

		if( !in_array( $domain, Types_Field_Utils::get_domains() ) ) {
			return null;
		}

		return esc_url_raw(
			add_query_arg(
				array( 'page' => Types_Admin_Menu::PAGE_NAME_FIELD_CONTROL, 'domain' => $domain ),
				admin_url( 'admin.php' )
			)
		);
	}


	/**
	 * @inheritdoc
	 *
	 * Validate field domain, which must be part of the GET request.
	 *
	 * @throws InvalidArgumentException when the domain is invalid.
	 * @since 2.0
	 */
	public function prepare() {

		parent::prepare();
		
		$current_domain = $this->get_current_domain();

		// Fail on invalid domain.
		if( null == $current_domain ) {
			throw new InvalidArgumentException( 
				sprintf( 
					__( 'Invalid field domain provided. Expected one of those values: %s', 'mncf' ),
					implode( ', ', Types_Field_Utils::get_domains() )
				)
			);
		}
		
		add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ) );

		add_action( 'current_screen', array( $this, 'prepare_dialogs' ) );
		
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_title() {

		switch( $this->get_current_domain() ) {
			case Types_Field_Utils::DOMAIN_POSTS:
				return __( 'Post Field Control', 'mncf' );
			case Types_Field_Utils::DOMAIN_USERS:
				return __( 'User Field Control', 'mncf' );
			case Types_Field_Utils::DOMAIN_TERMS:
				return __( 'Term Field Control', 'mncf' );
			default:
				// will never happen
				return '';
		}
	}


	/**
	 * @inheritdoc
	 * @return callable
	 */
	public function get_render_callback() {
		return array( $this, 'render_page' );
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_page_name() {
		return Types_Admin_Menu::PAGE_NAME_FIELD_CONTROL;
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_required_capability() {
		return 'manage_options'; // todo better role/cap handling
	}


	/**
	 * @inheritdoc
	 * @return callable
	 */
	public function get_load_callback() {
		return array( $this, 'add_screen_options' );
	}
	

	/**
	 * Current field domain.
	 * 
	 * @return string|null 
	 * @since 2.0
	 */
	private function get_current_domain() {
		if( null == $this->current_domain ) {
			$this->current_domain = mncf_getget( self::PARAM_DOMAIN, null, Types_Field_Utils::get_domains() );
		}
		return $this->current_domain;
	}
	
	
	public function on_admin_enqueue_scripts() {

		$main_handle = 'types-page-field-control-main';

		// Enqueuing with the admin dependency because we need to override something !important.
		Types_Asset_Manager::get_instance()->enqueue_styles(
			array(
				'admin',
				'common',
				'font-awesome',
				'mncf-css-embedded',
				'mn-jquery-ui-dialog'
			)
		);

		mn_enqueue_style(
			$main_handle,
			TYPES_RELPATH . '/public/page/field_control/style.css'
		);

		mn_enqueue_script( 
			$main_handle,
			TYPES_RELPATH . '/public/page/field_control/main.js',
			array( 
				'jquery', 'backbone', 'headjs', 'underscore',
				Types_Asset_Manager::SCRIPT_ADJUST_MENU_LINK,
				Types_Asset_Manager::SCRIPT_KNOCKOUT,
				Types_Asset_Manager::SCRIPT_DIALOG_BOXES,
				Types_Asset_Manager::SCRIPT_UTILS
			),
			TYPES_VERSION
		);

	}


	private $twig = null;


	private function get_twig() {
		if( null == $this->twig ) {
			$loader = new Twig_Loader_Filesystem();
			$loader->addPath( TYPES_ABSPATH . '/application/views/page', 'generic_page' );
			$loader->addPath( TYPES_ABSPATH . '/application/views/page/field_control', 'field_control' );
			$this->twig = new Twig_Environment( $loader );
		}
		return $this->twig;
	}


	/**
	 * @inheritdoc
	 * 
	 * @since 2.0
	 */
	public function render_page() {

		$context = $this->build_page_context();

		echo $this->get_twig()->render( '@field_control/main.twig', $context );
	}


	/**
	 * Build the context for main poge template.
	 *
	 * That includes variables for the template as well as data to be passed to JavaScript.
	 * 
	 * @return array Page context. See the main page template for details.
	 * @since 2.0
	 */
	private function build_page_context() {

		$context = array(
			'strings' => $this->build_strings_for_twig(),
			'js_model_data' => base64_encode( mn_json_encode( $this->build_js_data() ) ),
			'assets' => array(
				'loaderOverlay' => Types_Assets::get_instance()->get_image_url( Types_Assets::IMG_AJAX_LOADER_OVERLAY )
			)
		);

		return $context;
	}


	/**
	 * Build data to be passed to JavaScript.
	 * 
	 * @return array
	 * @since 2.0
	 */
	private function build_js_data() {
		
		$field_action_name = Types_Ajax::get_instance()->get_action_js_name( Types_Ajax::CALLBACK_FIELD_CONTROL_ACTION );
		
		return array(
			'jsIncludePath' => TYPES_RELPATH . '/public/page/field_control',
			'fieldDefinitions' => $this->build_field_definitions(),
			'fieldTypeDefinitions' => Types_Field_Type_Definition_Factory::get_instance()->get_field_type_definitions(),
			'templates' => $this->build_templates(),
			'strings' => $this->build_strings_for_js(),
			'ajaxInfo' => array(
				'fieldAction' => array(
					'name' => $field_action_name,
					'nonce' => mn_create_nonce( $field_action_name )
				) 
			),
			'currentDomain' => $this->get_current_domain(),
			'groups' => $this->build_group_data(),
			'typeConversionMatrix' => Types_Field_Type_Converter::get_instance()->get_conversion_matrix(),
			'itemsPerPage' => $this->get_items_per_page_setting()
		);
		
	}


	/**
	 * Prepare field definition data, depending on current field domain, for passing to JavaScript.
	 * 
	 * @return array
	 * @since 2.0
	 */
	private function build_field_definitions() {
		
		$query_args = array(
			'filter' => 'all',
			'orderby' => 'name',
			'order' => 'asc'
		);

		$definitions = array();
		switch( $this->get_current_domain() ) {
			case Types_Field_Utils::DOMAIN_POSTS:
				$definitions = MNCF_Field_Definition_Factory_Post::get_instance()->query_definitions( $query_args );
				break;
			case Types_Field_Utils::DOMAIN_USERS:
				$definitions = MNCF_Field_Definition_Factory_User::get_instance()->query_definitions( $query_args );
				break;
			case Types_Field_Utils::DOMAIN_TERMS:
				$definitions = MNCF_Field_Definition_Factory_Term::get_instance()->query_definitions( $query_args );
				break;
		}


		$definition_data = array();
		foreach( $definitions as $definition ) {
			$definition_data[] = $definition->to_json();
		}
		
		return $definition_data;
	}
	

	/**
	 * Build array of templates that will be passed to JavaScript.
	 * 
	 * If the template file does not exist or is not readable, it will be silently omitted. 
	 * 
	 * @return array
	 * @since 2.0
	 */
	private function build_templates() {

		$template_sources = array(
			'messageDefinitionList' => 'field_control/message_definition_list.html',
			'messageMultiple' => 'field_control/message_multiple.html',
		);

		$templates = array();
		foreach( $template_sources as $template_name => $template_relpath ) {

			$template_path = TYPES_ABSPATH . '/application/views/page/' . $template_relpath;

			if( file_exists( $template_path ) ) {
				$templates[ $template_name ] = file_get_contents( $template_path );
			}
		}

		return $templates;
	}


	private function build_strings_for_twig() {

		return array(
			'column' => array(
				'name' => __( 'Field name', 'mncf' ),
				'slug' => __( 'Slug', 'mncf' ),
				'metaKey' => __( 'Meta key', 'mncf' ),
				'groups' => __( 'Field groups', 'mncf' ),
				'type' => __( 'Type' )
			),
			'rowAction' => array(
				'changeAssignment' => __( 'Change assignment', 'mncf' ),
				'changeType' => __( 'Change type', 'mncf' ),
				'delete' => __( 'Delete', 'mncf' )
			),
			'misc' => array(
				'noItemsFound' => __( 'No field definitions found.', 'mncf' ),
				'applyBulkAction' => __( 'Apply', 'mncf' ),
				'searchPlaceholder' => __( 'Search', 'mncf' ),
				'pageTitle' => $this->get_title(),
				'items' => __( 'items', 'mncf' ),
				'of' => __( 'of', 'mncf' ),
				'thisFieldIsRepeating' => __( 'This is a repeating field.', 'mncf' )
			),
			'bulkAction' => array(
				'select' => __( 'Bulk action', 'mncf' ),
				'delete' => __( 'Delete', 'mncf' ),
				'manageWithTypes' => __( 'Manage with Types', 'mncf' ),
				'stopManagingWithTypes' => __( 'Stop managing with Types', 'mncf' )
			)
		);
	}



	/**
	 * Prepare an array of strings used in JavaScript.
	 * 
	 * @return array
	 * @since 2.0
	 */
	private function build_strings_for_js() {   
		return array(
			'misc' => array(
				'notManagedByTypes' => __( 'Not managed by Types', 'mncf'),
				'undefinedAjaxError' => __( 'The action was not successful, an unknown error has happened.', 'mncf' ),
				'genericSuccess' => __( 'The action was completed successfully.', 'mncf' ),
				'fieldsAlreadyManaged' => __( 'Some of the fields you selected are already managed by Types.', 'mncf' ),
				'fieldsAlreadyUnmanaged' => __( 'Some of the fields you selected are already not managed by Types.', 'mncf' ),
				'unselectAndRetry' => __( 'Please unselect them and try again.', 'mncf' ),
				'changeAssignmentToGroups' => __( 'Change assignment to field groups for the field', 'mncf' ),
				'deleteField' => __( 'Delete field', 'mncf' ),
				'deleteFields' => __( 'Delete multiple fields', 'mncf' ),
				'cannotDeleteUnmanagedFields' => __( 'Some fields cannot be deleted because they are not managed by Types.', 'mncf' ),
				'changeFieldType' => __( 'Change field type for the field', 'mncf' ),
				'startManagingFieldsWithTypes' => __( 'Start managing fields with Types', 'mncf' ),
				'stopManagingFieldsWithTypes' => __( 'Stop managing fields with Types', 'mncf' )
			),
			'rowAction' => array(
				'manageByTypes' => array(
					'yes' => __( 'Manage with Types', 'mncf' ),
					'no' => __( 'Stop managing with Types', 'mncf' )
				),
				'changeCardinality' => array(
					'makeRepetitive' => __( 'Turn into repetitive', 'mncf' ),
					'makeSingle' => __( 'Turn into single-value', 'mncf' )
				)
			),
			'bulkAction' => array(
				'select' => __( 'Bulk action', 'mncf' ),
				'delete' => __( 'Delete', 'mncf' ),
				'manageWithTypes' => __( 'Manage with Types', 'mncf' ),
				'stopManagingWithTypes' => __( 'Stop managing with Types', 'mncf' )
			),
			'button' => array(
				'apply' => __( 'Apply', 'mncf' ),
				'cancel' => __( 'Cancel', 'mncf' ),
				'delete' => __( 'Delete', 'mncf' )
			)
		);
	}


	/**
	 * Build an array describing existing field groups within the domain.
	 * 
	 * @return array
	 * @since 2.0
	 */
	private function build_group_data() {
		$factory = Types_Field_Utils::get_group_factory_by_domain( $this->get_current_domain() );
		$groups = $factory->query_groups();

		$group_data = array();
		foreach( $groups as $group ) {
			$group_data[ $group->get_slug() ] = array(
				'slug' => $group->get_slug(),
				'displayName' => $group->get_display_name()
			);
		}

		return $group_data;
	}


	/**
	 * Display screen options on the page.
	 * 
	 * @since 2.0
	 */
	public function add_screen_options() {

		$args = array(
			'label' => __( 'Number of displayed fields', 'mncf' ),
			'default' => self::SCREEN_OPTION_PER_PAGE_DEFAULT_VALUE,
			'option' => self::SCREEN_OPTION_PER_PAGE_NAME,
		);
		add_screen_option( 'per_page', $args );

		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3);
	}


	/**
	 * Update the "per page" screen option.
	 * 
	 * @param $status
	 * @param string $option
	 * @param $value
	 * @return mixed
	 * @since 2.0
	 */
	public function set_screen_option( $status, $option, $value ) {

		if ( self::SCREEN_OPTION_PER_PAGE_NAME == $option ) {
			return $value;
		}

		return $status;

	}


	/**
	 * Value of the "items per page" setting for current page and current user.
	 * 
	 * @return int
	 * @since 2.0
	 */
	private function get_items_per_page_setting() {
		$user = get_current_user_id();
		$screen = get_current_screen();
		$option = $screen->get_option( 'per_page', 'option' );
		$per_page = get_user_meta( $user, $option, true );
		if ( empty ( $per_page ) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		return (int) $per_page;
	}


	/**
	 * Prepare assets for all dialogs that are going to be used on the page.
	 * 
	 * @since 2.0
	 */
	public function prepare_dialogs() {

		new Types_Dialog_Box(
			'types-change-assignment-dialog',
			$this->get_twig(),
			array(
				'groups' => $this->build_group_data(),
				'strings' => array(
					'noFieldGroups' => __( 'No field groups exist yet. You have to create one first.', 'mncf' )
				)
			),
			'@field_control/change_assignment_dialog.twig'
		);

		new Types_Dialog_Box(
			'types-delete-field-dialog',
			$this->get_twig(),
			array(
				'strings' => array(
					'deletingWillRemoveDefinitionAndData' => __( 'Deleting fields will remove them from all groups and delete the field data from the database as well.', 'mncf' ),
					'cannotBeUndone' => __( 'This cannot be undone!', 'mncf' ),
					'doYouReallyWantDelete' => __( 'Do you really want to delete?', 'mncf' )
				)
			),
			'@field_control/delete_dialog.twig'
		);
		
		new Types_Dialog_Box(
			'types-change-field-type-dialog',
			$this->get_twig(),
			array(
				'fieldTypeDefinitions' => Types_Field_Type_Definition_Factory::get_instance()->get_field_type_definitions(),
				'strings' => array(
					'aboutFieldTypeChanging' => __( 'Select a new type for this field.', 'mncf' ),
					'someTypesAreDisabled' => __( 'Note: Some of the field types are disabled for conversion because they\'re using a significantly different data format, which is not compatible with the current field type.', 'mncf' ),
					'potentiallyRiskyOperation' => __( 'Changing field type is a potentially risky operation. Make sure you know what you are doing.', 'mncf' ),
					'singleOrRepeatingField' => __( 'Single or repeating field', 'mncf' ),
					'repetitiveField' => __( 'Allow multiple instances of this field', 'mncf' ),
					'singleField' => __( 'This field can have only one value', 'mncf' ),
					'targetSupportsSingleOnly' => __( 'Selected field type supports only single fields.', 'mncf' ),
					'repetitiveToSingleWarning' => __( 'Changing from repeating to single field <strong>will cause partial data loss</strong> if there already are fields with multiple values stored in the database. In such case, only one of those value will be saved on update and some inconsistencies appear when displaying values of this field.', 'mncf' )
				)
			),
			'@field_control/change_type_dialog.twig'
		);
		
		
		new Types_Dialog_Box(
			'types-bulk-change-management-status-dialog',
			$this->get_twig(),
			array(
				'strings' => array(
					'youAreAboutToManageFields' => __( 'You are about to start managing these fields with Types:', 'mncf' ),
					'youAreAboutToStopManagingFields' => __( 'You are about to stop managing these fields with Types:', 'mncf' ),
					'confirmContinue' => __( 'Do you want to continue?', 'mncf' )
				)
			),
			'@field_control/bulk_change_management_status_dialog.twig'
		);
	}


	/**
	 * Get help configuration for Types_Asset_Help_Tab_Loader.
	 * 
	 * @return array
	 * @since 2.0
	 */
	public function get_help_config() {
		return array(
			'title' => $this->get_title(),
			'template' => '@help/basic.twig',
			'context' => array(
				'introductory_paragraphs' => array(
					__( 'Types plugin provides you with a powerful way to control the Post/User/Term fields.', 'mncf' ),
					__( 'On this page you can see the list of all the custom fields present in your site. Some of them were created by Types and some were not.', 'mncf' ),
					__( 'When changing changing properties of existing fields, caution is strongly advised because wrong usage can cause issues with themes, plugins and functionality connected to the applied changes.', 'mncf' )
				),
				'your_options' => __( 'You have the following options:', 'mncf' ),
				'options' => array(
					array(
						'name' => __( 'Change assignment', 'mncf' ),
						'explanation' => __( 'Change a group that the field belongs to.', 'mncf' )
					),
					array(
						'name' => __( 'Change type', 'mncf' ),
						'explanation' => __( 'Change the type of the field and change whether field can have a single value or multiple values (repetitive field).', 'mncf' )
					),
					array(
						'name' => __( 'Manage with Types', 'mncf' ),
						'explanation' => __( 'Select whether the field is under the control of Types plugin.', 'mncf' )
					),
					array(
						'name' => __( 'Delete', 'mncf' ),
						'explanation' => 
							__( 'Click to delete a field and all of its values from the database.', 'mncf' )
							. ' <strong>' . __( 'Warning: This cannot be undone.', 'mncf' ) . '</strong>'
					)
				),
				'ending_paragraphs' => array(
					__( 'Note: An asterisk (*) beside a field type marks a repeating field.', 'mncf' )
				)
			)
		);
	}

}