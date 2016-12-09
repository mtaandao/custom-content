<?php

/**
 * Types_Helper_Condition_Views_Active
 *
 * @since 2.0
 */
class Types_Helper_Condition_Views_Active extends Types_Helper_Condition {

	public function valid() {
		if( defined( 'MNV_VERSION' ) )
			return true;

		return false;
	}

}