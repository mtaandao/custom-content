<?php

/**
 * User field group.
 *
 * @since 2.0
 */
class Types_Field_Group_User extends Types_Field_Group {


	const POST_TYPE = 'mn-types-user-group';


	/**
	 * @param MN_Post $field_group_post Post object representing a user field group.
	 * @throws InvalidArgumentException
	 */
	public function __construct( $field_group_post ) {
		parent::__construct( $field_group_post );
		if( self::POST_TYPE != $field_group_post->post_type ) {
			throw new InvalidArgumentException( 'incorrect post type' );
		}
	}


	/**
	 * @return MNCF_Field_Definition_Factory Field definition factory of the correct type.
	 */
	protected function get_field_definition_factory() {
		return MNCF_Field_Definition_Factory_User::get_instance();
	}


}
