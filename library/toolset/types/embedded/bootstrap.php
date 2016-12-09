<?php
/*
 * Bootstrap code.
 *
 * Types plugin or embedded code is initialized here.
 * Here is determined if code is used as plugin or embedded code.
 *
 * @since Types 1.2
 *
 *
 */

// Main functions
require_once dirname( __FILE__ ) . '/functions.php';

/*
 *
 *
 * If MNCF_VERSION is not defined - we're running embedded code
 */
if ( !defined( 'MNCF_VERSION' ) ) {
    // Mark that!
    define( 'MNCF_RUNNING_EMBEDDED', true );
    require_once dirname( __FILE__ ) . '/classes/loader.php';
}

/*
 *
 * Forced priority
 */
if ( !defined( 'TYPES_INIT_PRIORITY' ) ) {
    // Early start ( some plugins use 'init' with priority 0 ).
    define( 'TYPES_INIT_PRIORITY', -1 );
}

/**
 * custom filed groups - post type
 */
define('TYPES_CUSTOM_FIELD_GROUP_CPT_NAME', 'mn-types-group');

/**
 * user meta filed groups - post type
 */
define('TYPES_USER_META_FIELD_GROUP_CPT_NAME', 'mn-types-user-group');

/**
 * user meta filed groups - post type
 */
define('TYPES_TERM_META_FIELD_GROUP_CPT_NAME', 'mn-types-term-group');

/**
 * default capability
 */

define('TYPES_CAPABILITY', 'manage_options');

/**
 * last author
 */
if ( !defined('MNCF_AUTHOR' )){
    define( 'MNCF_AUTHOR', '_mncf_author_id');
}

/*
 *
 * Init
 */
add_action( 'init', 'mncf_embedded_init', TYPES_INIT_PRIORITY );

/**
 * register_post_type & register_taxonomy - must be with default pririty to 
 * handle defult taxonomies
 */
/**
 * Priotity for mncf_init_custom_types_taxonomies()
 *
 * Priotity for function mncf_init_custom_types_taxonomies() in init MN
 * action..
 *
 */
add_action( 'init', 'mncf_init_custom_types_taxonomies', apply_filters('mncf_init_custom_types_taxonomies', 10));

/*
 *
 *
 * Define necessary constants
 */
define( 'MNCF_EMBEDDED_ABSPATH', dirname( __FILE__ ) );
define( 'MNCF_EMBEDDED_INC_ABSPATH', MNCF_EMBEDDED_ABSPATH . '/includes' );
define( 'MNCF_EMBEDDED_RES_ABSPATH', MNCF_EMBEDDED_ABSPATH . '/resources' );

/*
 *
 * Always set DEBUG as false
 */
if ( !defined( 'MNCF_DEBUG' ) ) {
    define( 'MNCF_DEBUG', false );
}
if ( !defined( 'TYPES_DEBUG' ) ) {
    define( 'TYPES_DEBUG', false );
}

/*
 *
 * Register theme options
 */
mncf_embedded_after_setup_theme_hook();

/*
 *
 *
 * Set $mncf global var as generic class
 */
$GLOBALS['mncf'] = new stdClass();



/**
 * Initialize the autoloader (for newer parts of code).
 */
function mncf_initialize_autoloader_embedded() {
	require_once MNCF_EMBEDDED_INC_ABSPATH . '/autoloader.php';
	$autoloader = MNCF_Autoloader::get_instance();
	$autoloader->add_prefix( 'MNCF' );

	// This will trigger the loading mechanism for legacy classes.
	$autoloader->add_prefix( 'Types' );
	$autoloader->add_prefix( 'MNToolset' );

	$autoloader->add_path( MNCF_EMBEDDED_ABSPATH . '/classes' );
}

mncf_initialize_autoloader_embedded();


/**
 * Main init hook.
 *
 * All rest of init processes are continued here.
 * Sets locale, constants, includes...
 *
 * @todo Make sure plugin AND embedded code are calling this function on 'init'
 * @todo Test priorities
 */
function mncf_embedded_init() {

    global $types_instances, $mn_current_filter;

    // Record hook
    $types_instances['hook'] = $mn_current_filter;
    $types_instances['init_queued'] = '#' . did_action( 'init' );
    $types_instances['init_priority'] = TYPES_INIT_PRIORITY;
    $types_instances['forced_embedded'] = defined( 'TYPES_LOAD_EMBEDDED' ) && TYPES_LOAD_EMBEDDED;
	
	// Localization
	new Toolset_Localization( 'mncf', MNCF_EMBEDDED_ABSPATH . '/locale', 'types-%s' );
	
	// Custom Content Forms
	if ( ! defined( 'MNTOOLSET_FORMS_VERSION' ) ) {
		$toolset_common_bootstrap = Toolset_Common_Bootstrap::getInstance();
		$toolset_common_sections = array(
			'toolset_forms'
		);
		$toolset_common_bootstrap->load_sections( $toolset_common_sections );
	}

    // Loader
    require_once MNCF_EMBEDDED_ABSPATH . '/classes/loader.php';

    do_action( 'mncf_before_init' );
    do_action( 'types_before_init' );

    // Define necessary constants if plugin is not present
    // This ones are skipped if used as embedded code!
    if ( !defined( 'MNCF_VERSION' ) ) {
        define( 'MNCF_VERSION', '1.9' );
        define( 'MNCF_META_PREFIX', 'mncf-' );
    }

    // If forced embedded mode use path to __FILE__
    if ( ( defined( 'TYPES_LOAD_EMBEDDED' ) && TYPES_LOAD_EMBEDDED )
        || !defined('MNCF_RELPATH') ) {
        define( 'MNCF_EMBEDDED_RELPATH', mncf_get_file_url( __FILE__, false ) );
    } else {
        define( 'MNCF_EMBEDDED_RELPATH', MNCF_RELPATH . '/embedded' );
    }

    // Define embedded paths
    define( 'MNCF_EMBEDDED_INC_RELPATH', MNCF_EMBEDDED_RELPATH . '/includes' );
    define( 'MNCF_EMBEDDED_RES_RELPATH', MNCF_EMBEDDED_RELPATH . '/resources' );

    // TODO INCLUDES!
    //
    // Please add all required includes here
    // Since Types 1.2 we can consider existing code as core.
    // All new functionalities should be added as includes HERE
    // and marked with @since Types $version.
    //
    // Thanks!
    //

    // Basic
    /*
     *
     * Mind class extensions queue
     */
    require_once MNCF_EMBEDDED_ABSPATH . '/classes/fields.php';
    require_once MNCF_EMBEDDED_ABSPATH . '/classes/field.php';
    require_once MNCF_EMBEDDED_ABSPATH . '/classes/usermeta_field.php'; // Added by Gen, usermeta fields class

    // Repeater
    require_once MNCF_EMBEDDED_ABSPATH . '/classes/repeater.php';
    require_once MNCF_EMBEDDED_ABSPATH . '/classes/usermeta_repeater.php'; // Added by Gen, usermeta repeater class
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/repetitive-fields-ordering.php';
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/repetitive-usermetafields-ordering.php';

    // Relationship
    require_once MNCF_EMBEDDED_ABSPATH . '/classes/relationship.php';

    // Conditional
    require_once MNCF_EMBEDDED_ABSPATH . '/classes/conditional.php';

    // API
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/api.php';

    // Validation
    require_once MNCF_EMBEDDED_ABSPATH . '/classes/validation.php';

    // Post Types
    require_once MNCF_EMBEDDED_ABSPATH . '/classes/class.mncf-post-types.php';

    // Import Export
    require_once MNCF_EMBEDDED_ABSPATH . '/classes/class.mncf-import-export.php';

    // Module manager
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/module-manager.php';

    // MNML specific code
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/mnml.php';

    // CRED specific code.
    if ( defined( 'CRED_FE_VERSION' ) ) {
        require_once MNCF_EMBEDDED_INC_ABSPATH . '/cred.php';
    }

    /*
     *
     *
     * TODO This is a must for now.
     * See if any fields need to be loaded.
     *
     * 1. Checkboxes - may be missing when submitted
     */
    require_once MNCF_EMBEDDED_INC_ABSPATH . '/fields/checkbox.php';


    /*
     *
     *
     * Use this call to load basic scripts and styles if necesary
     * mncf_enqueue_scripts();
     */
    require_once MNCF_EMBEDDED_ABSPATH . '/usermeta-init.php';
    // Include frontend or admin code
    if ( is_admin() ) {

        require_once MNCF_EMBEDDED_ABSPATH . '/admin.php';

        /*
         * TODO Check if called twice
         *
         * Watch this! This is actually called twice everytime
         * in both modes (plugin or embedded)
         */
        mncf_embedded_admin_init_hook();
    } else {
        require_once MNCF_EMBEDDED_ABSPATH . '/frontend.php';
    }

    global $mncf;

    // TODO since Types 1.2 Continue adding new functionalities HERE
    /*
     * Consider code already there as core.
     * Use hooks to add new functionalities
     *
     * Introduced new global object $mncf
     * Holds useful objects like:
     * $mncf->field - Field object (base item object)
     * $mncf->repeater - Repetitive field object
     */

    // Set debugging
    if ( !defined( 'MNCF_DEBUG' ) ) {
        define( 'MNCF_DEBUG', false );
    } else if ( MNCF_DEBUG ) {
        mn_enqueue_script( 'jquery' );
    }
    $mncf->debug = new stdClass();
    require MNCF_EMBEDDED_INC_ABSPATH . '/debug.php';
    add_action( 'mn_footer', 'mncf_debug', PHP_INT_MAX);
    add_action( 'admin_footer', 'mncf_debug', PHP_INT_MAX);

    // Set field object
    $mncf->field = new MNCF_Field();

    // Set fields object
    $mncf->fields = new MNCF_Fields();

    // Set usermeta field object
    $mncf->usermeta_field = new MNCF_Usermeta_Field();
	
	// Set termmeta field object
	$mncf->termmeta_field = new MNCF_Termmeta_Field();
	
	// Set repeater object
    $mncf->repeater = new MNCF_Repeater();

    // Set usermeta repeater object
    $mncf->usermeta_repeater = new MNCF_Usermeta_Repeater();
	
	// Set termmeta repeater object
	$mncf->termmeta_repeater = new MNCF_Termmeta_Repeater();

    // Set relationship object
    $mncf->relationship = new MNCF_Relationship();

    // Set conditional object
    $mncf->conditional = new MNCF_Conditional();

    // Set validate object
    $mncf->validation = new MNCF_Validation();

    // Set import export objects
    $mncf->import = new MNCF_Import_Export();
    $mncf->export = new MNCF_Import_Export();

    // Set post object
    $mncf->post = new stdClass();

    // Set post types object
    $mncf->post_types = new MNCF_Post_Types();

    // Define exceptions - privileged plugins and their data
    $mncf->toolset_post_types = array(
        'view', 'view-template', 'cred-form', 'cred-user-form'
    );
    // 'attachment' = Media
    //
    $mncf->excluded_post_types = array(
        'cred-form',
        'cred-user-form',
        'dd_layouts',
        'deprecated_log',
        'mediapage',
        'nav_menu_item',
        'revision',
        'view',
        'view-template',
        'mn-types-group',
        'mn-types-user-group',
	    'mn-types-term-group',
	    'acf-field-group',
	    'acf'
    );

    /**
     * Filter that allows to add own post types which will be not used in Custom Content plugins.
     *
     * @param string[] $post_types array of post type slugs.
     * @since 1.9
     */
    $mncf->excluded_post_types = apply_filters( 'toolset_filter_exclude_own_post_types', $mncf->excluded_post_types );

    // Init loader
    MNCF_Loader::init();

    /*
     * TODO Check why we enabled this
     *
     * I think because of CRED or Views using Types admin functions on frontend
     * Does this need review?
     */
    if ( defined( 'DOING_AJAX' ) ) {
        require_once MNCF_EMBEDDED_ABSPATH . '/frontend.php';
    }

    // Check if import/export request is going on
    mncf_embedded_check_import();

	// Initialize (new) parts of the GUI.
	// Btw. current_screen is being fired during admin_init.
	add_action( 'current_screen', 'mncf_initialize_admin_gui' );

    do_action( 'types_after_init' );
    do_action( 'mncf_after_init' );
}


/**
 * Initialize parts of GUI depending on current screen.
 *
 * @since 1.9
 */
function mncf_initialize_admin_gui() {

	$screen = get_current_screen();

	// Should be always true.
	if( $screen instanceof MN_Screen ) {
		if( in_array( $screen->base, array( 'edit-tags', 'term' ) ) ) {
			MNCF_GUI_Term_Field_Editing::initialize();
		}
	}
}
