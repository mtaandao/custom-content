<?php

/**
 * Register data (called automatically).
 *
 * @return type
 */
function mncf_fields_wysiwyg() {
    $settings = array(
        'id' => 'mncf-wysiwyg',
        'title' => __( 'WYSIWYG', 'mncf' ),
        'description' => __( 'WYSIWYG editor', 'mncf' ),
        'meta_box_css' => array(
            'mncf-fields-wysiwyg' => array(
                'inline' => 'mncf_fields_wysiwyg_css',
            ),
        ),
        'mn_version' => '3.3',
        'font-awesome' => 'list-alt',
    );
    return $settings;
}

/**
 * Meta box form.
 *
 * @param type $field
 * @return array
 */
function mncf_fields_wysiwyg_meta_box_form( $field, $f ) {

    if ( isset( $f->context ) && $f->context == 'relationship' ) {
        $form = array(
            '#type' => 'textarea',
            '#attributes' => array('class' => 'mncf-textarea'),
        );
    } else {
        $set = array(
            'mnautop' => true, // use mnautop?
            'media_buttons' => true, // show insert/upload button(s)
            'textarea_name' => 'mncf[' . $field['id'] . ']', // set the textarea name to something different, square brackets [] can be used here
            'textarea_rows' => get_option( 'default_post_edit_rows', 10 ), // rows="..."
            'tabindex' => '',
            'editor_css' => '', // intended for extra styles for both visual and HTML editors buttons, needs to include the <style> tags, can use "scoped".
            'editor_class' => 'mncf-wysiwyg', // add extra class(es) to the editor textarea
            'teeny' => false, // output the minimal editor config used in Press This
            'dfw' => false, // replace the default fullscreen with DFW (needs specific DOM elements and css)
            'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
            'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
        );
        $form = array(
            '#type' => 'wysiwyg',
            '#attributes' => array('class' => 'mncf-wysiwyg'),
            '#editor_settings' => $set,
        );
    }

    return $form;
}

/**
 * CSS for styling TinyMCE Editor.
 */
function mncf_fields_wysiwyg_css() {
    global $mn_version;

    ?>
    <style type="text/css">
        .mncf-wysiwyg iframe, .mncf-wysiwyg .mceIframeContainer {
            background-color: #FFFFFF !important;
        }
        .mncf-wysiwyg table {
            border: 1px solid #DFDFDF !important;
        }
        .mncf-media-buttons {
            margin-bottom: 10px;
        }
        .mncf-media-buttons a {
            margin-left: 5px;
            text-decoration: none;
        }
        .mncf-wysiwyg-switcher {
            float: right;
            margin-top: -24px;
            padding: 0;
        }
        .mncf-wysiwyg-switcher a {
            padding: 10px;
            line-height: 25px;
            text-decoration: none;
            color: #000000;
            border: 1px solid #DFDFDF !important;
            border-bottom: none !important;
            background-color: #E8E8E8;
            margin-left: 2px;
        }
        <?php
// MN 3.3 changes
        if ( version_compare( $mn_version, '3.2.1', '<=' ) ) {

            ?>
            .mncf-wysiwyg .mceResize {
                margin-top: -25px !important;
            }
            <?php
        }

        ?>
    </style>
    <?php
}

/**
 * View function.
 *
 * @param type $params
 * @return type
 */
function mncf_fields_wysiwyg_view( $params ) {
    $output = '';
    if ( !empty( $params['style'] ) || !empty( $params['class'] ) ) {
        $output .= '<div';
        if ( !empty( $params['style'] ) ) {
            $output .= ' style="' . $params['style'] . '"';
        }
        if ( !empty( $params['class'] ) ) {
            $output .= ' class="' . $params['class'] . '"';
        }
        $output .= '>';
    }

    remove_shortcode('playlist', 'mn_playlist_shortcode');

    $content = stripslashes( $params['field_value'] );

    if ( isset( $params['suppress_filters'] ) && $params['suppress_filters'] == 'true' ) {
        $the_content_filters = array(
            'mntexturize', 'convert_smilies', 'convert_chars', 'mnautop',
            'shortcode_unautop', 'prepend_attachment', 'capital_P_dangit', 'do_shortcode');
        foreach ($the_content_filters as $func) {
            if (  function_exists( $func ) ) {
                $content = call_user_func($func, $content);
            }
        }
        $output .= $content;
    } else {
        $filter_state = new MNCF_MN_filter_state( 'the_content' );
        $output .= apply_filters( 'the_content', $content );
        
        if  ((!(strpos( $output, "&amp;#91;") === false)) && (!( strpos( $output, "&amp;#93;") === false)) && (!(strpos( $output, "<pre") === false)) ) {
        	global $SyntaxHighlighter;
        	if ( isset( $SyntaxHighlighter ) ) {
        		if ( is_object( $SyntaxHighlighter ) ) {
        			//This is a syntax higlighting content
        			$output = str_replace("&amp;#91;", "[", $output);
        			$output = str_replace("&amp;#93;", "]", $output);
        		}
        	}
        }
        
        $filter_state->restore( );
    }

    if ( preg_match_all('/\[playlist[^\]]+\]/', $output, $matches ) ) {
        foreach( $matches[0] as $one ) {
            $one = preg_replace('/\[/', '\\[', $one);
            $one = preg_replace('/\]/', '\\]', $one);
            $re = '/'.$one.'/';
            $one = preg_replace('/\&\#(8221|8243);/', '\'', $one);
            $output = preg_replace($re, $one, $output);
        }
    }
    add_shortcode( 'playlist', 'mn_playlist_shortcode' );

    if ( !empty( $params['style'] ) || !empty( $params['class'] ) ) {
        $output .= '</div>';
    }
    return $output;
}

/**
 * Used for recording a current item of the callbacks in $mn_filter[ $tag ] and restoring it
 * after applying a filter recursively.
 *
 * Workaround for https://core.trac.mtaandao.org/ticket/17817.
 *
 * From Mtaandao 4.7 above, this does nothing.
 *
 * @since 1.9.1
 * @deprecated No longer needed since Mtaandao 4.7
 */
class MNCF_MN_filter_state {

    private $current_index;
    private $tag;
	private $is_disabled = false;

    public function __construct( $tag ) {

	    global $mn_version;

	    if( version_compare( $mn_version, '4.6.9', '>' ) ) {
		    $this->is_disabled = true;
		    return;
	    }

	    global $mn_filter;

        $this->tag = $tag;

        if ( isset( $mn_filter[$tag] ) ) {
            $this->current_index = current($mn_filter[$tag]);
        }
    }

    public function restore( ) {

	    if( $this->is_disabled ) {
		    return;
	    }

        global $mn_filter;

        if ( isset( $mn_filter[$this->tag] ) && $this->current_index ) {
            reset($mn_filter[$this->tag]);
            while ( $this->current_index && current($mn_filter[$this->tag]) && $this->current_index != current($mn_filter[$this->tag]) ) {
                next( $mn_filter[$this->tag] );
            }
        }

    }

}