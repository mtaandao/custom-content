<?php


abstract class MNCF_Field_Renderer_Abstract {

	/** @var null|MNCF_Field_Instance */
	protected $field = null;

	public function __construct( $field ) {

		// todo sanitize
		$this->field = $field;
	}


	/**
	 * @param bool $echo
	 *
	 * @return string
	 */
	public abstract function render( $echo = false );

}