<?php

/**
 * Term Field Groups listing page.
 */
final class MNCF_Page_Listing_Termmeta extends MNCF_Page_Listing_Abstract {


	const PAGE_NAME = 'mncf-termmeta-listing';

	const BULK_ACTION_NONCE = 'mncf-termmeta-listing-bulk-action-nonce';

	const SCREEN_OPTION_PER_PAGE_DEFAULT_VALUE = 10;

	const SCREEN_OPTION_PER_PAGE_NAME = 'mncf_termmeta_listing_per_page';
	
	public function add_submenu_page() {
		$page = array(
			'slug'						=> self::PAGE_NAME,
			'menu_title'				=> __( 'Term Fields', 'mncf' ),
			'page_title'				=> __( 'Term Fields', 'mncf' ),
			'callback'					=> array( $this, 'page_handler' ),
			'load_hook'					=> array( $this, 'add_screen_options' ),
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


	public function page_handler() {

		do_action( 'mncf_admin_page_init' );

		mncf_admin_page_add_options('uf',  __( 'User Fields', 'mncf' ));
		//$this->add_screen_options();


		// Without this, the Activate/Deactivate link doesn't work properly (why?)
		mncf_admin_load_collapsible();

		mncf_admin_page_add_options('tf',  __( 'Term Fields', 'mncf' ));

		mncf_add_admin_header( __( 'Term Field Groups', 'mncf' ), array( 'page' => MNCF_Page_Edit_Termmeta::PAGE_NAME ) );

		require_once MNCF_INC_ABSPATH . '/fields.php';
		// require_once MNCF_INC_ABSPATH . '/fields-list.php';

		$list_table = new MNCF_Page_Listing_Termmeta_Table();

		$list_table->prepare_items();

		if( !$list_table->has_items() ) {
			add_action( 'mncf_groups_list_table_after', 'mncf_admin_promotional_text' );
		}

		?>
		<form id="cf-filter" method="post">
			<!-- For plugins, we also need to ensure that the form posts back to our current page -->
			<input type="hidden" name="page" value="<?php echo self::PAGE_NAME; ?>"/>

			<?php

			$list_table->search_box( __( 'Search Term Field Groups', 'mncf' ), 'search_id' );
			$list_table->display();

			mn_nonce_field( self::BULK_ACTION_NONCE );
			?>
		</form>
		<?php
		do_action( 'mncf_groups_list_table_after' );

		$this->show_term_field_control_box();

		mncf_add_admin_footer();
	}

	protected function get_page_name() {
		return self::PAGE_NAME;
	}


	/**
	 * Show box with a link to Term Field Control page.
	 */
	function show_term_field_control_box() {
		$form = array();

		$form['table-1-open'] = array(
			'#type' => 'markup',
			'#markup' => '<table class="mncf-types-form-table widefat js-mncf-slugize-container"><thead><tr><th>' . __( 'Term Field Control', 'mncf' ) . '</th></tr></thead><tbody>',
			'_builtin' => true,
		);
		$form['table-row-1-open'] = array(
			'#type' => 'markup',
			'#markup' => '<tr><td>',
			'_builtin' => true,
		);

		$form['table-row-1-content-1'] = array(
			'#type' => 'markup',
			'#markup' => '<p>' . __( 'You can control Term Fields by removing them from the groups, changing type or just deleting.', 'mncf' ),
			'_builtin' => true,
		);

		$form['table-row-1-content-2'] = array(
			'#type' => 'markup',
			'#markup' => sprintf(
				' <a class="button" href="%s">%s</a></p>',
				Types_Page_Field_Control::get_page_url( Types_Field_Utils::DOMAIN_TERMS ),
				__( 'Term Field Control', 'mncf' )
			),
			'_builtin' => true,
		);

		$form['table-row-1-close'] = array(
			'#type' => 'markup',
			'#markup' => '</td></tr>',
			'_builtin' => true,
		);
		$form['table-1-close'] = array(
			'#type' => 'markup',
			'#markup' => '</tbody></table>',
			'_builtin' => true,
		);
		$form = mncf_form( self::PAGE_NAME . '-field-control-box', $form );
		echo $form->renderForm();

	}


	public function add_screen_options() {

		$args = array(
			'label' => __( 'Term Fields', 'mncf' ),
			'default' => self::SCREEN_OPTION_PER_PAGE_DEFAULT_VALUE,
			'option' => self::SCREEN_OPTION_PER_PAGE_NAME,
		);
		add_screen_option( 'per_page', $args );

		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3);
	}


	function set_screen_option($status, $option, $value) {

		if ( self::SCREEN_OPTION_PER_PAGE_NAME == $option ) {
			return $value;
		}

		return $status;

	}
	
	public function add_contextual_help() {
		
		$screen = get_current_screen();
	
		if ( is_null( $screen ) ) {
			return;
		}
		
		$args = array(
			'title'		=> __( 'Term Field Groups', 'mncf' ),
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
				 .__("Types plugin organizes Term Fields in groups. Once you create a group, you can add the fields to it and control to what content it belongs.", 'mncf')
				 .PHP_EOL
				 .PHP_EOL
				 .__("On this page you can see your current Term Field groups, as well as information about which taxonomies they are attached to, and whether they are active or not.", 'mncf')
				 . sprintf('<h3>%s</h3>', __('You have the following options:', 'mncf'))
				 .'<dl>'
				 .'<dt>'.__('Add New', 'mncf').'</dt>'
				 .'<dd>'.__('Use this to add a new Term Field Group', 'mncf').'</dd>'
				 .'<dt>'.__('Edit', 'mncf').'</dt>'
				 .'<dd>'.__('Click to edit the Term Field Group', 'mncf').'</dd>'
				 .'<dt>'.__('Activate', 'mncf').'</dt>'
				 .'<dd>'.__('Click to activate a Term Field Group', 'mncf').'</dd>'
				 .'<dt>'.__('Deactivate', 'mncf').'</dt>'
				 .'<dd>'.__('Click to deactivate a Term Field Group (this can be re-activated at a later date)', 'mncf').'</dd>'
				 .'<dt>'.__('Delete', 'mncf').'</dt>'
				 .'<dd>'.__('Click to delete a Term Field Group.', 'mncf')
				 .' '
				 .sprintf('<strong>%s</strong>', __('Warning: This cannot be undone.', 'mncf'))
				 .'</dd>'
				 .'</dl>'
		;
		
		return mnautop( $contextual_help );
		
	}

}