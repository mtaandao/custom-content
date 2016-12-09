jQuery(function(){
	if( jQuery('.mncf-notif .mncf-notif-dropdown').length > 0 ) {

		jQuery('.mncf-notif a.mncf-button.show').click(function(){
			if ( jQuery('.mncf-notif .mncf-notif-dropdown').is(':hidden') ) {
				jQuery(this).slideUp(200);
				jQuery('.mncf-notif .mncf-notif-dropdown').slideDown(200);
			}
		});

		jQuery('.mncf-notif a.mncf-button.hide').click(function(){
			if ( jQuery(".mncf-notif .mncf-notif-dropdown").is(':visible') ) {
				jQuery('.mncf-notif a.mncf-button.show').slideDown(200);
				jQuery('.mncf-notif .mncf-notif-dropdown').slideUp(200);
                jQuery('.mncf-notif a.mncf-button.show').show();
			}
		});
	}
});