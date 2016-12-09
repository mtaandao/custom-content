<?php

class Types_Helper_Create_Wordpress_Archive {

	/**
	 * Creates a Mtaandao Archive for a given post type
	 *
	 * @param $type
	 * @param bool|string $name Name for the Mtaandao Archive
	 *
	 * @return bool
	 * @since 2.0
	 */
	public function for_post( $type, $name = false ) {

		// check dependencies
		if( ! $this->needed_components_loaded() )
			return false;

		global $MNV_settings;
		$option = sanitize_text_field( sprintf( 'view_cpt_%s', $type ) );

		// for type 'post'
		if( $type == 'post' ) {
			$name = __( 'Archive for Home/Blog', 'types' );
			$option = 'view_home-blog-page';
		}

		// already has an archive
		if( isset( $MNV_settings[$option] ) && is_numeric( $MNV_settings[$option] ) && $MNV_settings[$option] > 0 )
			return $MNV_settings[$option];

		// set name if not given
		if( ! $name ) {
			$type_object = get_post_type_object( $type );
			$name = sprintf( __( 'Archive for %s', 'types' ), $type_object->labels->name );
		}

		$name = $this->validate_name( $name );

		if( ! $name )
			return false;

		$archive = MNV_Mtaandao_Archive::create( $name, array() );
		$archive_post = get_post( $archive->id );

		if( $archive_post === null )
			return false;

		$MNV_settings[$option] = $archive_post->ID;
		$MNV_settings->save();

		return $archive_post->ID;
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
			|| ! class_exists( 'MNV_Mtaandao_Archive' )
			|| ! method_exists( 'MNV_Mtaandao_Archive', 'create' )
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
		$name_exists = get_page_by_title( html_entity_decode( $name ), OBJECT, 'view' );

		if( $name_exists ) {
			$name = $id > 1 ? rtrim( rtrim( $name, $id - 1 ) ) : $name;
			return $this->validate_name( $name . ' ' . $id, $id + 1 );
		}

		return $name;
	}

}
