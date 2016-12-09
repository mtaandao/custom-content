<?php

/**
 * User field definition.
 *
 * Warning: This is implemented only partially - the field accessor is missing, a dummy one is returned instead.
 *
 * @since 2.0
 */
final class MNCF_Field_Definition_User extends MNCF_Field_Definition {

	/**
	 * Get an accessor for a specific field instance.
	 *
	 * @param MNCF_Field_Instance $field_instance Instance of the field the accessor should access.
	 * @return MNCF_Field_Accessor_Abstract
	 */
	public function get_accessor( $field_instance ) {
		return new MNCF_Field_Accessor_Dummy();
	}


	/**
	 * Delete all field values!
	 *
	 * @return bool
	 */
	public function delete_all_fields() {
		global $mndb;

		$meta_key = $this->get_meta_key();

		$usermeta_records = $mndb->get_results(
			$mndb->prepare(
				"SELECT user_id FROM $mndb->usermeta WHERE meta_key = %s",
				$meta_key
			)
		);

		// Delete one by one because we (probably) want all the MN hooks to fire.
		foreach ( $usermeta_records as $usermeta ) {
			delete_user_meta( $usermeta->user_id, $meta_key );
		}

		return true;
	}
	

	/**
	 * @inheritdoc
	 *
	 * Adds properties: domain
	 *
	 * @return array
	 * @since 2.0
	 */
	public function to_json() {
		$object_data = parent::to_json();

		$additions = array(
			'domain' => 'users'
		);

		return array_merge( $object_data, $additions );
	}
	
}