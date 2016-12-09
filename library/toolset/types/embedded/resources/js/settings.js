var ToolsetTypes = ToolsetTypes || {};

ToolsetTypes.SettingsScreen = function( $ ) {
	
	var self = this;
	
	/**
	* Images
	*/
	
	$( document ).on( 'click', '.js-mncf-settings-clear-cache-images', function() {
		var thiz = $( this ),
		thiz_container = thiz.closest( '.js-mncf-settings-clear-cache-images-container' ),
		spinnerContainer = $( '<div class="toolset-spinner ajax-loader">' ).appendTo( thiz_container ).show();
		thiz.prop('disabled', true );
		self.save_settings_section( 'mncf_settings_clear_cache_images', 'all' )
			.done( function( response ) {
				if ( response.success ) {
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			})
			.always( function() {
				spinnerContainer.remove();
				thiz.prop('disabled', false );
			});
	});
	
	$( document ).on( 'click', '.js-mncf-settings-clear-cache-images-outdated', function() {
		var thiz = $( this ),
		thiz_container = thiz.closest( '.js-mncf-settings-clear-cache-images-container' ),
		spinnerContainer = $( '<div class="toolset-spinner ajax-loader">' ).appendTo( thiz_container ).show();
		thiz.prop('disabled', true );
		self.save_settings_section( 'mncf_settings_clear_cache_images', 'outdated' )
			.done( function( response ) {
				if ( response.success ) {
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			})
			.always( function() {
				spinnerContainer.remove();
				thiz.prop('disabled', false );
			});
	});
	
	self.mncf_image_state = $( '.js-toolset-mncf-image-settings input, .js-toolset-mncf-image-settings select' ).serialize();
	
	$( document ).on( 'change', '.js-toolset-mncf-image-settings input, .js-toolset-mncf-image-settings select', function() {
		if ( self.mncf_image_state != $( '.js-toolset-mncf-image-settings input, .js-toolset-mncf-image-settings select' ).serialize() ) {
			self.mncf_image_options_debounce_update();
		}
	});
	
	self.save_mncf_image_options = function() {
		var data = $( '.js-toolset-mncf-image-settings input, .js-toolset-mncf-image-settings select' ).serialize();
		self.save_settings_section( 'mncf_settings_save_image_settings', data )
			.done( function( response ) {
				if ( response.success ) {
					self.mncf_image_state = $( '.js-toolset-mncf-image-settings input, .js-toolset-mncf-image-settings select' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});
		
	};
	
	self.mncf_image_options_debounce_update = _.debounce( self.save_mncf_image_options, 1000 );
	
	/**
	* Help box
	*/
	
	self.mncf_help_box_state = $( '.js-toolset-mncf-help-box-settings input' ).serialize();
	
	$( document ).on( 'change', '.js-toolset-mncf-help-box-settings input', function() {
		if ( self.mncf_help_box_state != $( '.js-toolset-mncf-help-box-settings input' ).serialize() ) {
			self.mncf_help_box_options_debounce_update();
		}
	});
	
	self.save_mncf_help_box_options = function() {
		var data = $( '.js-toolset-mncf-help-box-settings input' ).serialize();
		self.save_settings_section( 'mncf_settings_save_help_box_settings', data )
			.done( function( response ) {
				if ( response.success ) {
					self.mncf_help_box_state = $( '.js-toolset-mncf-help-box-settings input' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});
		
	};
	
	self.mncf_help_box_options_debounce_update = _.debounce( self.save_mncf_help_box_options, 1000 );
	
	/**
	* Custom field metabox
	*/
	
	self.mncf_custom_field_metabox_state = $( '.js-toolset-mncf-custom-field-metabox-settings input' ).serialize();
	
	$( document ).on( 'change', '.js-toolset-mncf-custom-field-metabox-settings input', function() {
		if ( self.mncf_custom_field_metabox_state != $( '.js-toolset-mncf-custom-field-metabox-settings input' ).serialize() ) {
			self.mncf_custom_field_metabox_options_debounce_update();
		}
	});
	
	self.save_mncf_custom_field_metabox_options = function() {
		var data = $( '.js-toolset-mncf-custom-field-metabox-settings input' ).serialize();
		self.save_settings_section( 'mncf_settings_save_custom_field_metabox_settings', data )
			.done( function( response ) {
				if ( response.success ) {
					self.mncf_custom_field_metabox_state = $( '.js-toolset-mncf-custom-field-metabox-settings input' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});
		
	};
	
	self.mncf_custom_field_metabox_options_debounce_update = _.debounce( self.save_mncf_custom_field_metabox_options, 1000 );
	
	/**
	* Unfiltered HTML
	*/
	
	self.mncf_unfiltered_html_state = $( '.js-toolset-mncf-unfiltered-html-settings input' ).serialize();
	
	$( document ).on( 'change', '.js-toolset-mncf-unfiltered-html-settings input', function() {
		if ( self.mncf_unfiltered_html_state !=  $( '.js-toolset-mncf-unfiltered-html-settings input' ).serialize() ) {
			self.mncf_unfiltered_html_options_debounce_update();
		}
	});
	
	self.save_mncf_unfiltered_html_options = function() {
		var data = $( '.js-toolset-mncf-unfiltered-html-settings input' ).serialize();
		self.save_settings_section( 'mncf_settings_save_unfiltered_html_settings', data )
			.done( function( response ) {
				if ( response.success ) {
					self.mncf_unfiltered_html_state = $( '.js-toolset-mncf-unfiltered-html-settings input' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});
		
	};
	
	self.mncf_unfiltered_html_options_debounce_update = _.debounce( self.save_mncf_unfiltered_html_options, 1000 );
	
	/**
	* MNML
	*/
	
	self.mncf_mnml_state = $( '.js-toolset-mnml-mncf input' ).serialize();
	
	$( document ).on( 'change', '.js-toolset-mnml-mncf input', function() {
		if ( self.mncf_mnml_state !=  $( '.js-toolset-mnml-mncf input' ).serialize() ) {
			self.mncf_mnml_options_debounce_update();
		}
	});
	
	self.save_mncf_mnml_options = function() {
		var data = $( '.js-toolset-mnml-mncf input' ).serialize();
		self.save_settings_section( 'mncf_settings_save_mnml_settings', data )
			.done( function( response ) {
				if ( response.success ) {
					self.mncf_mnml_state = $( '.js-toolset-mnml-mncf input' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});
		
	};
	
	self.mncf_mnml_options_debounce_update = _.debounce( self.save_mncf_mnml_options, 1000 );
	
	/**
	* Helper method for saving settings
	*/
	
	self.save_settings_section = function( save_action, save_data ) {
		var data = {
			action:			save_action,
			settings:		save_data,
			mnnonce:		mncf_settings_i18n.mncf_settings_nonce
		};
		$( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );
		return $.ajax({
			url:		ajaxurl,
			data:		data,
			type:		"POST",
			dataType:	"json"
		});
	};
	
	self.init = function() {
		
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
    ToolsetTypes.settings_screen = new ToolsetTypes.SettingsScreen( $ );
});