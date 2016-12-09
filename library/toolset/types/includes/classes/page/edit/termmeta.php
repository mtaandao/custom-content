<?php

/**
 * Edit Term Field Group page handler.
 *
 * This is a wrapper around an implementation taken from the legacy code. All of it needs complete refactoring.
 *
 * @since 1.9
 */
final class MNCF_Page_Edit_Termmeta extends MNCF_Page_Abstract {

	const PAGE_NAME = 'mncf-termmeta-edit';

	/**
	 * Name of the form rendered by mncf_form.
	 */
	const FORM_NAME = 'mncf_form_termmeta_fields';


	/**
	 * @return MNCF_Page_Edit_Termmeta
	 */
	public static function get_instance() {
		return parent::get_instance();
	}
	
	
	public function add_submenu_page() {
		$page = array(
			'slug'						=> self::PAGE_NAME,
			'menu_title'				=> $this->get_menu_title(),
			'page_title'				=> $this->get_menu_title(),
			'callback'					=> array( $this, 'page_handler' ),
			'load_hook'					=> array( $this, 'load_hook' ),
			'capability'				=> MNCF_TERM_FIELD_EDIT,
			'contextual_help_legacy'	=> $this->get_contextual_help_legacy(),
			'contextual_help_hook'		=> array( $this, 'add_contextual_help' )
		);
		$capability = $page['capability'];
		$mncf_capability = apply_filters( 'mncf_capability', $capability, $page, $page['slug'] );
		$mncf_capability = apply_filters( 'mncf_capability' . $page['slug'], $capability, $page, $page['slug'] );
		$page['capability'] = $mncf_capability;
		return $page;
	}
	
	public function load_hook() {
		
		$this->prepare_form_maybe_redirect();
		
		mncf_admin_enqueue_group_edit_page_assets();
		
	}


	public function initialize_ajax_handler() {
		new MNCF_Page_Edit_Termmeta_Form();
	}


	public function get_menu_title() {
		return __( 'Edit Term Field Group', 'mncf' );
	}


	public function get_page_title( $purpose = 'edit' ) {
		switch ( $purpose ) {
			case 'add':
				return __( 'Add New Term Field Group', 'mncf' );
			case 'view':
				return __( 'View Term Field Group', 'mncf' );
			default:
				return __( 'Edit Term Field Group', 'mncf' );
		}
	}


	public function page_handler() {

		// Following code taken from the legacy parts. Needs refactoring.

		// By now we expect that prepare_form_maybe_redirect() was already called. If not, something went terribly wrong.
		if( null == $this->mncf_admin ) {
			return;
		}

		// Well this doesn't look right.
		$post_type = current_filter();

		// Start rendering the page.

		// Header and title
		$page_purpose = $this->mncf_admin->get_page_purpose();
		$add_new_button = ( 'edit' == $page_purpose ) ? array( 'page' => self::PAGE_NAME ) : false;
		mncf_add_admin_header( $this->get_page_title( $page_purpose ), $add_new_button );

		// Display MNML admin notices if there are any.
		mncf_mnml_warning();

		// Transform the form data into an Enlimbo form
		$form = mncf_form( self::FORM_NAME, $this->form );

		// Dark magic happens here.
		echo '<form method="post" action="" class="mncf-fields-form mncf-form-validate js-types-show-modal">';
		mncf_admin_screen( $post_type, $form->renderForm() );
		echo '</form>';

		mncf_add_admin_footer();

	}

	/**
	 * @var null|array
	 */
	private $form = null;

	/**
	 * @var MNCF_Page_Edit_Termmeta_Form
	 */
	private $mncf_admin = null;


	/**
	 * Prepare the form data.
	 *
	 * That includes saving, which may also include redirecting to the edit page with newly created group's ID
	 * in a GET parameter.
	 */
	public function prepare_form_maybe_redirect() {

		// Following code taken from the legacy parts. Needs refactoring.

		require_once MNCF_INC_ABSPATH . '/fields.php';
		require_once MNCF_INC_ABSPATH . '/fields-form.php';
		require_once MNCF_INC_ABSPATH . '/classes/class.types.admin.edit.custom.fields.group.php';

		$mncf_admin = new MNCF_Page_Edit_Termmeta_Form();
		$mncf_admin->init_admin();

		$this->form = $mncf_admin->form();
		$this->mncf_admin = $mncf_admin;
	}
	
	public function add_contextual_help() {
		
		$screen = get_current_screen();
	
		if ( is_null( $screen ) ) {
			return;
		}
		
		$args = array(
			'title'		=> __( 'Term Field Group', 'mncf' ),
			'id'		=> 'mncf',
			'content'	=> $this->get_contextual_help_legacy(),
			'callback'	=> false,
		);
		$screen->add_help_tab( $args );

		/**
		 * Need Help section for a bit advertising
		 *
		 * @note this is available because in mncf_admin_toolset_register_menu_pages we are requiring once MNCF_ABSPATH . '/help.php', mind that when refactoring
		 */
		$args = array(
			'title'		=> __( 'Need More Help?', 'mncf' ),
			'id'		=> 'custom_fields_group-need-help',
			'content'	=> mncf_admin_help( 'need-more-help' ),
			'callback'	=> false,
		);
		$screen->add_help_tab( $args );
		
	}
	
	
	public function get_contextual_help_legacy() {
		
		$contextual_help = ''
			.__('This is the edit page for your Term Field Groups.', 'mncf')
			.PHP_EOL
			.PHP_EOL
			. __('On this page you can create and edit your groups. To create a group, do the following:', 'mncf')
			.'<ol><li>'
			. __('Add a Title.', 'mncf')
			.'</li><li>'
			. __('Choose where to display your group. You can attach this to any taxonomy.', 'mncf')
			.'</li><li>'
			. __('To add a field, click on "Add New Field" and choose the field you desire. This will be added to your Term Field Group.', 'mncf')
			.'</li><li>'
			. __('Add information about your Term Field.', 'mncf')
			.'</li></ol>'
			.'<h3>' . __('Tips', 'mncf') .'</h3>'
			.'<ul><li>'
			. __('To ensure a user fills out a field, check Required in Validation section.', 'mncf')
			.'</li><li>'
			. __('Once you have created a field, it will be saved for future use under "Choose from previously created fields" of "Add New Field" dialog.', 'mncf')
			.'</li><li>'
			. __('You can drag and drop the order of your term fields.', 'mncf')
			.'</li></ul>';
			
		return mnautop( $contextual_help );
	}

}