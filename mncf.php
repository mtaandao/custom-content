<?php
/*
Plugin Name: Custom Content Types
Description: Custom Content Types defines custom content in Mtaandao. Easily create custom post types, fields and taxonomy and connect everything together.
Version: 2.2.5
License: GPLv2 or later

Types is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Types is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Types. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/

// abort if called directly
if( !function_exists( 'add_action' ) )
	die( 'Types is a Mtaandao plugin and can not be called directly.' );

// version
if( ! defined( 'TYPES_VERSION' ) )
	define( 'TYPES_VERSION', '2.2.5' );

// backward compatibility
if ( ! defined( 'MNCF_VERSION' ) )
	define( 'MNCF_VERSION', TYPES_VERSION );

// release notes
if( ! defined( 'TYPES_RELEASE_NOTES' ) )
	// Mind the end of the URL string, it contains the plugin version.
	define( 'TYPES_RELEASE_NOTES', 'https://mn-types.com/version/types-2-2-5/?utm_source=typesplugin&utm_campaign=types&utm_medium=release-notes-admin-notice&utm_term=Types%202.2.5%20release%20notes' );

/*
 * Path Constants
 */
if( ! defined( 'TYPES_ABSPATH' ) )
	define( 'TYPES_ABSPATH', dirname( __FILE__ ) );

if( ! defined( 'TYPES_RELPATH' ) )
	define( 'TYPES_RELPATH', plugins_url() . '/' . basename( TYPES_ABSPATH ) );

if( ! defined( 'TYPES_DATA' ) )
	define( 'TYPES_DATA', dirname( __FILE__ ) . '/application/data' );

/*
 * Bootstrap Types
 */
require_once( dirname( __FILE__ ) . '/application/bootstrap.php' );


//
// Activation and deactivation hooks must be defined in the main file.
//
register_deactivation_hook( __FILE__, 'mncf_deactivation_hook' );
register_activation_hook( __FILE__, 'mncf_activation_hook' );

// adds "Leave feedback" link on plugins list page
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'types_plugin_action_links' );
