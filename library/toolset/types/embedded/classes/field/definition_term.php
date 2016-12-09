<?php

/**
 * Field definition for term fields.
 *
 * @since 1.9
 */
class MNCF_Field_Definition_Term extends MNCF_Field_Definition {


	/**
	 * Get an accessor for a specific field instance.
	 *
	 * @param MNCF_Field_Instance $field_instance Instance of the field the accessor should access.
	 * @return MNCF_Field_Accessor_Termmeta_Field
	 */
	public function get_accessor( $field_instance ) {
		return new MNCF_Field_Accessor_Termmeta_Field(
			$field_instance->get_object_id(),
			$this->get_meta_key(),
			$this->get_is_repetitive(),
			$field_instance
		);
	}
	

	/**
	 * Delete all field values!
	 *
	 * @return bool
	 */
	public function delete_all_fields() {
		global $mndb;

		$meta_key = $this->get_meta_key();

		$termmeta_records = $mndb->get_results(
			$mndb->prepare(
				"SELECT term_id FROM $mndb->termmeta WHERE meta_key = %s",
				$meta_key
			)
		);

		// Delete one by one because we (probably) want all the MN hooks to fire.
		foreach ( $termmeta_records as $termmeta ) {
			delete_term_meta( $termmeta->term_id, $meta_key );
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
			'domain' => 'terms'
		);
		
		return array_merge( $object_data, $additions );
	}
	
}