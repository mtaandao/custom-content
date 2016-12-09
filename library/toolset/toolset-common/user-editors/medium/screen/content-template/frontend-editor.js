var mnv_preview_post_container = jQuery( '.toolset-editors-select-preview-post' ),
    mnv_preview_post = jQuery( '#mnv-ct-preview-post' );

(function( $ ) {
    $( 'document' ).ready( function() {
        FLBuilder._updateLayout();
        mnv_preview_post_container.prependTo( '.fl-builder-bar-actions' );
        mnv_preview_post_container.show();
    } );

    $( window ).load( function() {
        FLBuilder._exitUrl = toolset_user_editors.mediumUrl;
    } );

    mnv_preview_post.on( 'change', function() {
        $.ajax( {
            type: 'post',
            dataType: 'json',
            url: ajaxurl,
            data: {
                action: 'set_preview_post',
                ct_id: toolset_user_editors.mediumId,
                preview_post_id: this.value,
                nonce: toolset_user_editors.nonce
            },
            complete: function() {
                FLBuilder._updateLayout();
            }
        } );
    } );
})( jQuery );