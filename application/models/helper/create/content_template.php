<?php

/**
 * Types_Helper_Create_Content_Template
 *
 * @since 2.0
 */
class Types_Helper_Create_Content_Template {

	/**
	 * Creates a content template for a given post type
	 *
	 * @param $type
	 * @param bool|string $name Name for the Content Template
	 *
	 * @return bool
	 * @since 2.0
	 */
	public function for_post( $type, $name = false ) {

		// abort if any needed dependency is not available
		if( ! $this->needed_components_loaded() )
			return false;

		global $MNV_settings;

		// option key for Views Content Templates is "views_template_for_{post-type-name}"
		$option = sanitize_text_field( sprintf( 'views_template_for_%s', $type ) );

		// already has an content template
		if( isset( $MNV_settings[$option] ) && is_numeric( $MNV_settings[$option] ) && $MNV_settings[$option] > 0 )
			return $MNV_settings[$option];

		// create name if not given
		if( ! $name ) {
			$type_object = get_post_type_object( $type );
			$name = sprintf( __( 'Template for %s', 'types' ), $type_object->labels->name );
		}

		$name = $this->validate_name( $name );

		// abort if name not valid (shouldn't happen, see validate_name())
		if( ! $name )
			return false;

		// create template
		$ct = MNV_Content_Template::create( $name );
		$ct_post = get_post( $ct->id );

		if( $ct_post === null )
			return false;

		$MNV_settings[$option] = $ct_post->ID;
		$MNV_settings->save();

		// get all posts of post type to assign the new content template
		$posts = get_posts( 'post_type=' . $type . '&post_status=any&posts_per_page=-1&fields=ids' );

		foreach( $posts as $id ) {
			$ct = get_post_meta( $id, '_views_template', true );

			// only assign if there is not already an assigned content template
			if( empty( $ct ) )
				update_post_meta( $id, '_views_template', $ct_post->ID );

		}

		return $ct_post->ID;
	}

	/**
	 * Checks all dependencies
	 *
	 * @return bool
	 * @since 2.0
	 */
	private function needed_components_loaded( ) {
		global $MNV_settings;
		if(
			! is_object( $MNV_settings )
			|| ! class_exists( 'MNV_Content_Template' )
			|| ! method_exists( 'MNV_Content_Template', 'create' )
		) return false;

		return true;
	}


	/**
	 * Will proof if given name is already in use.
	 * If so it adds an running number until name is available
	 *
	 * @param $name
	 * @param int $id | should not manually added
	 *
	 * @return string
	 * @since 2.0
	 */
	private function validate_name( $name, $id = 1 ) {
		$name_exists = get_page_by_title( html_entity_decode( $name ), OBJECT, 'view-template' );

		if( $name_exists ) {
			$name = $id > 1 ? rtrim( rtrim( $name, $id - 1 ) ) : $name;
			return $this->validate_name( $name . ' ' . $id, $id + 1 );
		}

		return $name;
	}

}
