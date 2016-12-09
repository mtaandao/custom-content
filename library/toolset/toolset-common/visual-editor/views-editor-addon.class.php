<?php
if ( file_exists( dirname(__FILE__) . '/editor-addon-generic.class.php') && !class_exists( 'MNV_Editor_addon' )  ) {

    require_once( dirname(__FILE__) . '/editor-addon-generic.class.php' );

    class MNV_Editor_addon extends Editor_addon_generic {
	
		static $footer_dialogs = '';
		static $footer_dialogs_types = array();
		static $footer_dialogs_added = false;
			
		public function __construct( $name, $button_text, $plugin_js_url, $media_button_image = '', $print_button = true, $icon_class = '' ) {
			parent::__construct( $name, $button_text, $plugin_js_url, $media_button_image, $print_button, $icon_class );
			if (
				$print_button 
				&& (
					$media_button_image != '' 
					|| $icon_class != ''
				)
			) {
				global $mn_version;
				if ( version_compare( $mn_version, '3.1.4', '>' ) ) {
					add_action( 'media_buttons', array( $this, 'add_fields_views_button' ), 10, 2 );
				} else {
					add_action( 'media_buttons_context', array( $this, 'add_fields_views_button' ), 10, 2 );
				}
			}
			
			add_action( 'admin_footer', array( $this, 'print_footer_dialogs' ) );

			if( apply_filters( 'mnv_render_dialogs_on_frontend', false ) )
				add_action( 'mn_footer', array( $this, 'print_footer_dialogs' ) );
		}
	
		function print_footer_dialogs() {
			if ( 
				! empty( self::$footer_dialogs ) 
				&& ! self::$footer_dialogs_added
			) {
				?>
				<div class="js-mnv-fields-views-footer-dialogs" style="display:none">
					<?php 
					echo self::$footer_dialogs; 
					self::$footer_dialogs_added = true;
					?>
				</div>
				<?php
			}
		}
	
	
		// always available dialog
		public function render_shortcodes_wrapper_dialogs( $target = 'posts', $text_area = '' ) {
			
			$existing_dialogs = self::$footer_dialogs_types;
			if ( in_array( $target, $existing_dialogs ) ) {
				return '';
			}
			
			global $post;
				
			$post_id = 0;
			if (
				is_object( $post ) 
				&& isset( $post->ID) 
			) {
				$post_id = $post->ID;
			}

			// Apply filters
			$this->items = apply_filters( 'editor_addon_items_' . $this->name, $this->items );
			
			$menus = array();

			if ( $this->items ) {
				foreach ( $this->items as $item ) {
					// $item = array($text, $shortcode, $menu, $function_name = '');
					if ( ! isset( $menus[ $item[2] ] ) ) {
						$menus[ $item[2] ] = array();
					}
					$menus[ $item[2] ][ $item[0] ] = $item;
				}
			}


			// Apply filters
			$menus = apply_filters( 'editor_addon_menus_' . $this->name, $menus );//echo '<pre style="text-align:left">';print_r($menus);echo '</pre>';

			// Sort menus
			if ( is_array( $menus ) ) {
				$menus = $this->sort_menus( $menus );
			}
				
			$dialog_links = array();
			$dialog_content = '';
			foreach ( $menus as $menu_key => $menu_data ) {
				$dialog_links[] = '<li data-id="' . md5( $menu_key ) . '" class="editor-addon-top-link" data-editor_addon_target="editor-addon-link-' . md5( $menu_key ) . '">' . $menu_key . ' </li>';

				$post_field_section_classname = ( $menu_key == __('Post field', 'mnv-views') ) ? ' js-mnv-shortcode-gui-group-list-post-field-section' : '';

				$dialog_content .= '<div class="group"><h4 data-id="' . md5( $menu_key ) . '" class="group-title  editor-addon-link-' . md5( $menu_key ) . '-target">' . $menu_key . "</h4>";
				$dialog_content .= '<ul class="mnv-shortcode-gui-group-list js-mnv-shortcode-gui-group-list' . $post_field_section_classname . '" data-editor="' . esc_attr( $text_area ) . '">';
				foreach ( $menu_data as $menu_item_title => $menu_item_data ) {
					if (
						isset( $menu_item_data[0] ) 
						&& ! is_array( $menu_item_data[0] ) 
						&& 'css' != $menu_item_title // For some reason Types fields bleed this css entry everywhere! Related to the editor_addon_menus_ filter
					) {
						if ( $menu_item_data[3] != '' ) {
							$dialog_content .= sprintf(
								'<li class="item button button-small" onclick="%s; return false;" data-post-id="%d">%s</li>', 
								$menu_item_data[3], 
								$post_id, 
								$menu_item_data[0]
							);
						} else {
							$short_code_name = $menu_item_data[1];
							$short_code = '[' . $short_code_name . ']';
							$link_text = $menu_item_data[0];
							// @todo there is a problem here, as we were using insert_b64_shortcode_to_editor needs the text_area ID. used on Types and some Views shortcodes...
							// By now it seems to work OK, and Types without Views gets this on its side, so maybe we are OK
							$dialog_content .= '<li class="item button button-small" onclick="MNViews.shortcodes_gui.insert_shortcode_with_no_attributes(\'' . esc_attr( $short_code_name ) . '\', \'' . esc_attr( $short_code ) . '\'); return false;">' . $link_text . "</li>";
						}
					}
				}
				$dialog_content .= '</ul>';
				$dialog_content .= '</div>';
			}

			$direct_links = implode( '', $dialog_links );
			$dropdown_class = 'js-mnv-fields-and-views-dialog-for-' . $target;

			// add search box
			$searchbar = $this->get_search_bar( $target );

			// generate output content
			$out = '
			<div class="mnv-fields-and-views-dialog mnv-editor_addon_dropdown '. $dropdown_class .'" id="mnv-editor_addon_dropdown_' . rand() . '">'
				. '<div class="mnv-editor_addon_dropdown_content editor_addon_dropdown_content js-mnv-fields-views-dialog-content">'
						. apply_filters( 'editor_addon_dropdown_top_message_' . $this->name, '' ) 
						. $searchbar
						. '<div class="direct-links-desc"><ul class="direct-links"><li class="direct-links-label">' . __( 'Jump to:', 'mnv-views' ) . '</li>' . $direct_links . '</ul></div>'
						. $dialog_content
						. apply_filters( 'editor_addon_dropdown_bottom_message' . $this->name, '' ) 
						. '
				</div>
			</div>';
			
			$existing_dialogs[] = $target;
			self::$footer_dialogs_types = $existing_dialogs;
			self::$footer_dialogs .= $out;
			
			return $out;
		}

		/**
		* get_fields_list
		*
		* This is used in the Loop Wizard, so watch out
		*
		*/
			
		function get_fields_list() {
			return apply_filters( 'toolset_editor_addon_post_fields_list', $this->items );
		}

        /**
         * Adding a "V" button to the menu
         * @param string $context
         * @param string $text_area
		 * -----------------------
         * @param boolean $standard_v
		 * @param $add_views
         */
		function add_form_button( $context, $text_area = '', $standard_v = true, $add_views = false, $codemirror_button = false ) {
			return;
		}
		
		function add_fields_views_button( $context, $text_area = '', $codemirror_button = false, $target = 'posts' ) {
				/**
				 * turn off button
				 */

			if ( ! apply_filters( 'toolset_editor_add_form_buttons', true ) ) {
				return;
			}
			
			global $mn_version;

			if ( 
				empty( $context ) 
				&& $text_area == ''
			) {
				return;
			}
			// MN 3.3 changes ($context arg is actually a editor ID now)
			if (
				version_compare( $mn_version, '3.1.4', '>' ) 
				&& ! empty( $context )
			) {
				$text_area = $context;
			}
						
			$icon_class = 'js-mnv-fields-and-views-in-toolbar';
			$button_label = __( 'Fields and Views', 'mnv-views' );
			   
			// Codemirror (new layout) button
			if ( $codemirror_button ) {
				$addon_button = '<button class="'. $icon_class .' button-secondary" data-editor="' . esc_attr( $text_area ) . '">'.'<i class="icon-views-logo ont-icon-18"></i><span class="button-label">'. __('Fields and Views', 'mnv-views') .'</span></button>';
			} else if ( '' !== $this->media_button_image ) {
				$addon_button = '<span class="button '. $icon_class .'" data-editor="' . esc_attr( $text_area ) . '"><img src="' . $this->media_button_image . '" />' . $button_label . '</span>';
			} else if ( '' !== $this->icon_class ) {
				$addon_button = '<span class="button '. $icon_class .'" data-editor="' . esc_attr( $text_area ) . '"><i class="'.$this->icon_class.'"></i><span class="button-label">' . $button_label . '</span></span>';
			}
						
			$this->render_shortcodes_wrapper_dialogs( $target, $text_area );
			
			// MN 3.3 changes
			if ( version_compare( $mn_version, '3.1.4', '>' ) ) {
				echo apply_filters( 'mnv_add_media_buttons', $addon_button );
			} else {
				return apply_filters( 'mnv_add_media_buttons', $context . $addon_button );
			}
			
		}

        /**
         *
         * Sort menus (and menu content) in an alphabetical order
         *
         * Still, keep Basic and Taxonomy on the top and Other Fields at the bottom
         *
         * @param array $menu menu reference
         */
		function sort_menus( $menus ) {
			// keep main references if set (not set on every screen)
			$menu_temp = array();
			$menu_names = array(
				__( 'MNML', 'mnv-views' ),
				__( 'User View', 'mnv-views' ),
				__( 'Taxonomy View', 'mnv-views' ),
				__( 'Post View', 'mnv-views' ),
				__( 'View', 'mnv-views' ),
				__( 'Post field', 'mnv-views' ),
				__( 'User basic data', 'mnv-views' ),
				__( 'Content Template', 'mnv-views' ),
				__( 'Taxonomy', 'mnv-views' ),
				__( 'Basic', 'mnv-views' )
			);
				
			$menus_sorted_first = array();
			$menus_sorted_last = array();
			$menus_sorted = array();

			$menus_on_top = array(
				__( 'Basic', 'mnv-views' ),
				__( 'Taxonomy', 'mnv-views' ),
				__( 'Content Template', 'mnv-views' ),
				__( 'User basic data', 'mnv-views' )
			);

			$menus_on_bottom = array(
				__( 'Post field', 'mnv-views' ),
				__( 'View', 'mnv-views' ),
				__( 'Post View', 'mnv-views' ),
				__( 'Taxonomy View', 'mnv-views' ),
				__( 'User View', 'mnv-views' ),
				__( 'MNML', 'mnv-views' )
			);

			$menus_keys = array_keys( $menus );

			foreach ( $menus_keys as $mk ) {
				if ( in_array( $mk, $menus_on_top ) ) {
					$menus_sorted_first[$mk] = $menus[$mk];
					unset( $menus[$mk] );
				} else if ( in_array( $mk, $menus_on_bottom ) ) {
					$menus_sorted_last[$mk] = $menus[$mk];
					unset( $menus[$mk] );
				}
			}

			$menus_sorted = array_merge( $menus_sorted_first, $menus, $menus_sorted_last );

			return $menus_sorted;
				
		 
		}

        function get_search_bar( $target = 'posts' ) {
            $searchbar  = '<div class="searchbar">';
            $searchbar .=   '<label for="searchbar-input-for-' . esc_attr( $target ) . '">' . __( 'Search', 'mnv-views' ) . ': </label>';
            $searchbar .=   '<input id="searchbar-input-for-' . esc_attr( $target ) . '" type="text" class="search_field" onkeyup="mnv_on_search_filter(this)" />';
            $searchbar .= '</div>';
            return $searchbar;
        }

    }

    /**
     * Renders JS for inserting shortcode from thickbox popup to editor.
     *
     * @param type $shortcode
	 * maybe DEPRECATED ???
     */
    if ( ! function_exists('editor_admin_popup_insert_shortcode_js') ) {
        function editor_admin_popup_insert_shortcode_js( $shortcode ) { // Types now uses ColorBox, it's not used in Views anymore. Maybe DEPRECATED
            ?>
            <script type="text/javascript">
                //<![CDATA[
                // Close popup
                window.parent.jQuery('#TB_closeWindowButton').trigger('click');
                // Check if there is custom handler
                if (window.parent.mncfFieldsEditorCallback_redirect) {
                    eval(window.parent.mncfFieldsEditorCallback_redirect['function'] + '(\'<?php echo esc_js( $shortcode ); ?>\', window.parent.mncfFieldsEditorCallback_redirect[\'params\'])');
                } else {
                    // Use default handler
                    window.parent.icl_editor.insert('<?php echo $shortcode; ?>');
                }
                //]]>
            </script>
            <?php
        }
    }

}

