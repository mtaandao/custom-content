/**
 *
 * Taxonomies form JS
 *
 *
 */

jQuery( document ).ready( function( $ ) {
    $( '.mncf-tax-form' ).on( 'submit', function() {
        return $( this ).mncfProveSlug();
    } );
} );
