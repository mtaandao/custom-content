<?php

/**
 * FIXME please document this!
 */
class Types_Post_Type {

	/** @var null|MN_Post_Type */
	protected $mn_post_type;

	protected $name;

	protected $field_groups;
	protected $taxonomies;

	public function __construct( $post_type ) {
		if( is_object( $post_type ) && isset( $post_type->name ) ) {
			$this->mn_post_type = $post_type;
			$this->name = $post_type->name;
		} else {
			$this->name = $post_type;
			$registered = get_post_type_object( $post_type );

			if( $registered ) {
				$this->mn_post_type = $registered;
			}
		}
	}

	public function __isset( $property ) {
		if( $this->mn_post_type === null )
			return false;

		if( ! property_exists( $this->mn_post_type, 'labels' ) )
			return false;

		if( ! property_exists( $this->mn_post_type->labels, $property ) )
			return false;

		return true;
	}

	public function __get( $property ) {
		if( ! $this->__isset( $property ) )
			return false;

		return $this->mn_post_type->labels->$property;
	}

	public function get_name() {
		return $this->name;
	}

	/**
	 * Get the backend edit link.
	 *
	 * @return string
	 * @since 2.1
	 */
	public function get_edit_link() {
		return admin_url() . 'admin.php?page=mncf-edit-type&mncf-post-type=' . $this->get_name();
	}

	/**
	 * Register Post Type
	 */

	/**
	 * Assigned Field Groups
	 */
	private function fetch_field_groups() {
		global $mndb;
		$sql = 'SELECT post_id FROM ' .$mndb->postmeta . '
                    WHERE meta_key="_mn_types_group_post_types"
                    AND (meta_value LIKE "%' . $this->name . '%" OR meta_value="all" OR meta_value REGEXP "^[,]+$")
                    ORDER BY post_id ASC';
		$post_ids = $mndb->get_col( $sql );

		$this->field_groups = array();
		foreach( $post_ids as $id ) {
			$field_group = Types_Field_Group_Post_Factory::load( $id );

			if( $field_group )
				$this->field_groups[] = $field_group;
		}
	}

	public function get_field_groups() {
		if( $this->field_groups == null )
			$this->fetch_field_groups();

		return $this->field_groups;
	}

	/**
	 * Assigned Taxonomies
	 */
	private function fetch_taxonomies() {
		$taxonomies = array();
		$all_taxonomies = get_taxonomies( '', 'objects' );

		foreach( $all_taxonomies as $tax ) {
			if( in_array( $this->get_name(), $tax->object_type ) )
				$taxonomies[] = new Types_Taxonomy( $tax );
		}

		$this->taxonomies = $taxonomies;
	}

	public function get_taxonomies() {
		if( $this->taxonomies == null )
			$this->fetch_taxonomies();

		return $this->taxonomies;
	}

	/**
	 * Assigned Templates
	 */

	/**
	 * Assigned Archives
	 */

	/**
	 * Assigned Views
	 */

	/**
	 * Assigned Forms
	 */
}