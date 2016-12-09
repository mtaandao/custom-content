<?php

/**
* ########################################
* Common MNML compatibility
* ########################################
*/

if ( defined( 'MNT_MNML_COMPATIBILITY' ) ) {
    return; 
}

define( 'MNT_MNML_COMPATIBILITY', true );

if ( ! class_exists( 'Toolset_MNML_Compatibility' ) ) {
	class Toolset_MNML_Compatibility {
		
		function __construct() {
			add_action( 'init', array( $this, 'stub_mnml_add_shortcode' ), 100 );
		}
		
		// @todo check in another way, against a global is not our best option
		// Check with Andrea
		function stub_mnml_add_shortcode() {
			global $MNML_String_Translation;
			if ( ! isset( $MNML_String_Translation ) ) {
				// MNML string translation is not active
				// Add our own do nothing shortcode
				add_shortcode( 'mnml-string', array( $this, 'stub_mnml_string_shortcode' ) );

			}
		}
		
		function stub_mnml_string_shortcode( $atts, $value ) {
			// return un-processed.
			return do_shortcode($value);
		}

	}

}