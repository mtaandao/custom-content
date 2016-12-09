<?php

function mntoolset_form( $form_id, $config = array() ){
	/** @var MNToolset_Forms_Bootstrap $mntoolset_forms */
	global $mntoolset_forms;
    $html = $mntoolset_forms->form( $form_id, $config );
    return apply_filters( 'mntoolset_form', $html, $config );
}

function mntoolset_form_field( $form_id, $config, $value = array() ){
    /** @var MNToolset_Forms_Bootstrap $mntoolset_forms */
	global $mntoolset_forms;
    $html = $mntoolset_forms->field( $form_id, $config, $value );
    return apply_filters( 'mntoolset_fieldform', $html, $config, $form_id );
}

//function mntoolset_form_field_edit( $form_id, $config ){
//    global $mntoolset_forms;
//    $html = $mntoolset_forms->fieldEdit( $form_id, $config );
//    return apply_filters( 'mntoolset_fieldform_edit', $html, $config, $form_id );
//}

function mntoolset_form_validate_field( $form_id, $config, $value ){
	/** @var MNToolset_Forms_Bootstrap $mntoolset_forms */
	global $mntoolset_forms;
    return $mntoolset_forms->validate_field( $form_id, $config, $value );
}

function mntoolset_form_conditional_check( $config ){
	/** @var MNToolset_Forms_Bootstrap $mntoolset_forms */
	global $mntoolset_forms;
    return $mntoolset_forms->checkConditional( $config );
}

function mntoolset_form_add_conditional( $form_id, $config ){
	/** @var MNToolset_Forms_Bootstrap $mntoolset_forms */
	global $mntoolset_forms;
    $mntoolset_forms->addConditional( $form_id, $config );
}

function mntoolset_form_filter_types_field( $field, $post_id = null, $_post_mncf = array() ){
	/** @var MNToolset_Forms_Bootstrap $mntoolset_forms */
	global $mntoolset_forms;
    return $mntoolset_forms->filterTypesField( $field, $post_id, $_post_mncf );
}

function mntoolset_form_field_add_filters( $type ){
	/** @var MNToolset_Forms_Bootstrap $mntoolset_forms */
	global $mntoolset_forms;
    $mntoolset_forms->addFieldFilters( $type );
}

function mntoolset_form_get_conditional_data( $post_id ){
	/** @var MNToolset_Forms_Bootstrap $mntoolset_forms */
	global $mntoolset_forms;
    return $mntoolset_forms->getConditionalData( $post_id );
}

function mntoolset_strtotime( $date, $format = null ){
	/** @var MNToolset_Forms_Bootstrap $mntoolset_forms */
	global $mntoolset_forms;
    return $mntoolset_forms->strtotime( $date, $format );
}

function mntoolset_timetodate( $timestamp, $format = null ){
	/** @var MNToolset_Forms_Bootstrap $mntoolset_forms */
	global $mntoolset_forms;
    return $mntoolset_forms->timetodate( $timestamp, $format );
}

/**
 * mntoolset_esc_like
 *
 * In Mtaandao 4.0, like_escape() was deprecated, due to incorrect
 * documentation and improper sanitization leading to a history of misuse
 * To maintain compatibility with versions of MN before 4.0, we duplicate the
 * logic of the replacement, mndb::esc_like()
 *
 * @see mndb::esc_like() for more details on proper use.
 *
 * @global object $mndb
 *
 * @param string $like The raw text to be escaped.
 * @return string Text in the form of a LIKE phrase. Not SQL safe. Run through
 *                mndb::prepare() before use.
 */
function mntoolset_esc_like( $like )
{
    global $mndb;
    if ( method_exists( $mndb, 'esc_like' ) ) {
        return $mndb->esc_like( $like );
    }
    if ( version_compare( get_bloginfo('version'), '4' ) < 0 ) {
        return like_escape( $like );
    }
    return addcslashes( $like, '_%\\' );
}

