<?php

/**
 * Handles processing POST data from a toolset-forms form and updates a single field.
 *
 * @since 1.9
 */
final class MNCF_Field_Data_Saver {


	/** @var MNCF_Field_Instance */
	private $field;


	/** @var string */
	private $form_id;


	/**
	 * MNCF_Field_Data_Saver constructor.
	 *
	 * @param MNCF_Field_Instance $field_instance Field that should be updated.
	 * @param string $form_id ID attribute of the form element that is being read from.
	 * @throws InvalidArgumentException
	 */
	public function __construct( $field_instance, $form_id ) {
		if( ! $field_instance instanceof MNCF_Field_Instance ) {
			throw new InvalidArgumentException( 'Invalid field instance (must be an field of existing object).' );
		}

		$this->field = $field_instance;
		$this->form_id = $form_id;
	}


	private $field_values = null;


	/**
	 * Read the field values from $_POST.
	 *
	 * @return array Values in the "intermediate" format (see MNCF_Field_DataMapper_Abstract). For non-repetitive values,
	 *     it will be an array with a single item.
	 */
	private function read_field_values() {

		if( null == $this->field_values ) {
			$definition = $this->field->get_definition();

			$form_data = mncf_ensarr( mncf_getpost( 'mncf' ) );

			$values = mncf_getarr( $form_data, $definition->get_slug() );

			// Handle single fields.
			if ( ! $definition->get_is_repetitive() ) {
				$values = array( $values );
			}

			// Map POST values to intermediate format.
			$this->field_values = array();
			$data_mapper = $definition->get_data_mapper();
			foreach( $values as $value ) {
				$this->field_values[] = $data_mapper->post_to_intermediate( $value, $form_data );
			}
		}

		return mncf_ensarr( $this->field_values );
	}


	/**
	 * @return array Array of true and MN_Error, one for each field value.
	 */
	public function validate_field_data() {

		$field_config = Types_Field_Utils::get_toolset_forms_field_config( $this->field );

		$this->toggle_adding_field_names_to_error_messages( false );

		$values = $this->read_field_values();
		$results = array();
		foreach( $values as $key => $value ) {
			$results[ $key ] = $this->validate_single_field_value( $field_config, $value );
		}

		$this->toggle_adding_field_names_to_error_messages( true );

		return $results;
	}


	private function is_all_field_data_valid() {
		$validation_results = $this->validate_field_data();
		foreach( $validation_results as $validation_result ) {
			if( $validation_result instanceof MN_Error ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Sets if toolset-forms will be adding field name to error messages (that will be returned as MN_Error objects).
	 *
	 * Default is to add field names, make sure you return to this state afterwards.
	 *
	 * @param bool $add_field_name True if field name should be added to error messages.
	 */
	private function toggle_adding_field_names_to_error_messages( $add_field_name ) {
		if( false == $add_field_name ) {
			add_filter( 'toolset_common_validation_add_field_name_to_error', '__return_false' );
		} else {
			remove_filter( 'toolset_common_validation_add_field_name_to_error', '__return_false' );
		}
	}


	/**
	 * @param $field_config
	 * @param $value
	 *
	 * @return true|MN_Error
	 */
	private function validate_single_field_value( $field_config, $value ) {
		return mntoolset_form_validate_field( $this->form_id, $field_config, $value );
	}


	/**
	 * Update field value if it is valid.
	 *
	 * @return bool|MN_Error True on success, false or MN_Error on failure.
	 */
	public function save_field_data() {

		if( !$this->is_all_field_data_valid() ) {
			return new MN_Error( 'Attempt to save a field with invalid value.' );
		}

		$values = $this->read_field_values();

		$is_success = $this->field->update_all_values( $values );

		return $is_success;

	}


}