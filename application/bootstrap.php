<?php

/*
 * Autoloader
 */
require_once( TYPES_ABSPATH . '/library/toolset/autoloader/autoloader.php' );

$autoloader = Toolset_Autoloader::get_instance();

$autoloader->add_paths(
	'Types',
	array(
		TYPES_ABSPATH . '/application/controllers',
		TYPES_ABSPATH . '/application/models',
	)
);

$autoloader->add_path( 'Toolset', TYPES_ABSPATH . '/library/toolset' );


/*
 * Load old Types
 */
if( ! defined( 'MNCF_RELPATH' ) ) {
	define( 'MNCF_RELPATH', TYPES_RELPATH . '/library/toolset/types' );
}

if( ! defined( 'MNCF_EMBEDDED_TOOLSET_ABSPATH' ) ) {
	define( 'MNCF_EMBEDDED_TOOLSET_ABSPATH', TYPES_ABSPATH . '/library/toolset' );
}

if( ! defined( 'MNCF_EMBEDDED_TOOLSET_RELPATH') ) {
	define( 'MNCF_EMBEDDED_TOOLSET_RELPATH', TYPES_RELPATH . '/library/toolset' );
}

if( ! defined( 'MNTOOLSET_COMMON_PATH' ) ) {
	define( 'MNTOOLSET_COMMON_PATH', TYPES_ABSPATH . '/library/toolset/toolset-common' );
}

if ( !defined( 'EDITOR_ADDON_RELPATH' ) ) {
	define( 'EDITOR_ADDON_RELPATH', MNCF_EMBEDDED_TOOLSET_RELPATH . '/toolset-common/visual-editor' );
}

// installer
$installer = TYPES_ABSPATH . '/library/otgs/installer/loader.php';
if ( file_exists( $installer ) ) {
	/** @noinspection PhpIncludeInspection */
	include_once $installer;
	if ( function_exists( 'MN_Installer_Setup' ) ) {
		MN_Installer_Setup(
			$mn_installer_instance,
			array(
				'plugins_install_tab' => '1',
				'repositories_include' => array('toolset', 'mnml')
			)
		);
	}
}


// Get new functions.php
require_once( dirname( __FILE__ ) . '/functions.php' );

// Initialize legacy code
require_once( dirname( __FILE__ ) . '/../library/toolset/types/mncf.php' );

// Public API
require_once( dirname( __FILE__ ) . '/api.php' );

// Handle embedded plugin mode
Types_Embedded::initialize();

// Jumpstart new Types
Types_Main::initialize();