var mnv_open_shortcode_dialog = jQuery( '.js-mnv-fields-and-views-in-adminbar' ),
    mnv_add_shortcode_to,
    mnv_add_shortcode_to_parent;

(function( $ ) {
    $( 'document' ).ready( function() {
        $.each( toolset_for_any_input, function( key, input ) {
            $( 'body' ).on( 'focus', input.stringSelector, function() {
                mnv_add_shortcode_to_parent = $( this ).closest( input.stringParentSelector );
                mnv_add_shortcode_to_parent.css( 'position', 'relative' );
                mnv_open_shortcode_dialog.appendTo( mnv_add_shortcode_to_parent );
                mnv_open_shortcode_dialog.css( 'display', 'block' );
                mnv_add_shortcode_to = $( this );
            } );
        } );
    } );
})( jQuery );