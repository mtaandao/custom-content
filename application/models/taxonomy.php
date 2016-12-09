<?php

/**
 * Class Types_Taxonomy
 *
 * FIXME please document this!
 */
class Types_Taxonomy {

	protected $mn_taxonomy;

	protected $name;

	public function __construct( $taxonomy ) {
		if( is_object( $taxonomy ) && isset( $taxonomy->name ) ) {
			$this->mn_taxonomy = $taxonomy;
			$this->name        = $taxonomy->name;
		} else {
			$this->name = $taxonomy;
			$registered = get_post_type_object( $taxonomy );

			if( $registered )
				$this->mn_taxonomy = $registered;
		}
	}

	public function __isset( $property ) {
		if( $this->mn_taxonomy === null )
			return false;

		if( ! property_exists( $this->mn_taxonomy, 'labels' ) )
			return false;

		if( ! property_exists( $this->mn_taxonomy->labels, $property ) )
			return false;

		return true;
	}

	public function __get( $property ) {
		if( ! $this->__isset( $property ) )
			return false;

		return $this->mn_taxonomy->labels->$property;
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
		return admin_url() . 'admin.php?page=mncf-edit-tax&mncf-tax=' . $this->get_name();
	}
}