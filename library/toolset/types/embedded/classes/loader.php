<?php
/**
 *
 * Loader class
 *
 *
 */

/**
 * Loader Class
 *
 * @since Types 1.2
 * @package Types
 * @subpackage Classes
 * @version 0.2
 * @category Loader
 * @author srdjan <srdjan@icanlocalize.com>
 */
class MNCF_Loader
{

    /**
     * Settings
     * @var array
     */
    private static $__settings = array();

    public static function init( $settings = array() ) {
        self::$__settings = (array) $settings;
        self::__registerScripts();
        self::__registerStyles();
        self::__toolset();
        add_action( 'admin_print_scripts', array('MNCF_Loader', 'renderJsSettings'), 5 );
		add_filter( 'the_posts', array('MNCF_Loader', 'mncf_cache_complete_postmeta') );
		add_filter( 'mncf_fields_postmeta_value_save', array( 'MNCF_Loader', 'mncf_sanitize_postmeta_values_on_save' ) );
		add_filter( 'mncf_fields_usermeta_value_save', array( 'MNCF_Loader', 'mncf_sanitize_usermeta_values_on_save' ) );
    }
	
	/**
	* Sanitize fields values on save
	*
	*/
	
	public static function mncf_sanitize_postmeta_values_on_save( $value ) {
		if (
			current_user_can( 'unfiltered_html' ) 
			&& mncf_get_settings('postmeta_unfiltered_html') != 'off'
		) {
			return $value;
		}
		if ( is_array( $value ) ) {
			// Recursion
			$value = array_map( array( 'MNCF_Loader', 'mncf_sanitize_postmeta_values_on_save' ), $value );
		} else {
			$value = mn_filter_post_kses( $value );
		}
		return $value;
	}
	
	public static function mncf_sanitize_usermeta_values_on_save( $value ) {
		if (
			current_user_can( 'unfiltered_html' ) 
			&& mncf_get_settings('usermeta_unfiltered_html') != 'off'
		) {
			return $value;
		}
		if ( is_array( $value ) ) {
			// Recursion
			$value = array_map( array( 'MNCF_Loader', 'mncf_sanitize_usermeta_values_on_save' ), $value );
		} else {
			$value = mn_filter_post_kses( $value );
		}
		return $value;
	}

    /**
     * Cache the postmeta for posts returned by a MN_Query
     *
     * @global object $mndb
     *
     */

    public static function mncf_cache_complete_postmeta( $posts ) {
		global $mndb;
		if ( !$posts )
			return $posts;
		$post_ids = array();
		$cache_group_ids = 'types_cache_ids';
		$cache_group = 'types_cache';
		foreach ( $posts as $post ) {
			$cache_key_looped_post = md5( 'post::_is_cached' . $post->ID );
			$cached_object = mn_cache_get( $cache_key_looped_post, $cache_group_ids );
			if ( false === $cached_object ) {
				$post_ids[] = intval( $post->ID );
				mn_cache_add( $cache_key_looped_post, $post->ID, $cache_group_ids );
			}
		}
		if ( count( $post_ids ) > 0 ) {
			$id_list = join( ',', $post_ids );
			$all_postmeta = $mndb->get_results( "SELECT * FROM {$mndb->postmeta} WHERE post_id IN ($id_list)", OBJECT );
			if ( !empty( $all_postmeta ) ) {
				$cache_key_keys = array();
				foreach ( $all_postmeta as $metarow ) {
					$mpid = intval($metarow->post_id);
					$mkey = $metarow->meta_key;
					$cache_key_keys[$mpid . $mkey][] = $metarow;
				}
				foreach ( $cache_key_keys as $single_meta_keys => $single_meta_values ) {
					$cache_key_looped_new = md5( 'field::_get_meta' . $single_meta_keys );
					mn_cache_add( $cache_key_looped_new, $single_meta_values, $cache_group );// Mtaandao cache
				}
			}
		}
		return $posts;
    }

    /**
     * Register scripts.
     */
    private static function __registerScripts() {
        $min = '';//MNCF_DEBUG ? '-min' : '';
        mn_register_script( 'types',
	        MNCF_EMBEDDED_RES_RELPATH . '/js/basic.js',
	        array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-tabs', 'toolset_select2'),
	        MNCF_VERSION, true );
        mn_register_script( 'types-knockout',
                MNCF_EMBEDDED_RES_RELPATH . '/js/knockout-2.2.1.js',
                array('jquery'), MNCF_VERSION, true );
        if ( !mn_script_is( 'toolset-colorbox', 'registered' ) ) {
            mn_register_script( 'toolset-colorbox',
                    MNCF_EMBEDDED_RES_RELPATH . '/js/jquery.colorbox-min.js',
                    array('jquery'), MNCF_VERSION, true );
        }
        mn_register_script( 'types-utils',
                MNCF_EMBEDDED_RES_RELPATH . "/js/utils{$min}.js", array('jquery'),
                MNCF_VERSION, true );
        mn_register_script( 'types-mn-views',
                MNCF_EMBEDDED_RES_RELPATH . '/js/mn-views.js', array('jquery'),
                MNCF_VERSION, true );
        global $pagenow;
        // Exclude on post edit screen
        if ( defined( 'MNTOOLSET_FORMS_ABSPATH' )
                && !in_array( $pagenow, array('edit.php', 'post.php', 'post-new.php') ) ) {
        mn_register_script( 'types-conditional',
                MNCF_EMBEDDED_RES_RELPATH . '/js/conditional.js',
                array('types-utils'), MNCF_VERSION, true );
        mn_register_script( 'types-validation',
                MNCF_EMBEDDED_RES_RELPATH . "/js/validation{$min}.js",
                array('jquery'), MNCF_VERSION, true );
        }
//        mn_register_script( 'types-jquery-validation',
//                MNCF_EMBEDDED_RES_RELPATH . '/js/jquery-form-validation/jquery.validate-1.11.1.min.js',
//                array('jquery'), MNCF_VERSION, true );
//        mn_register_script( 'types-jquery-validation-additional',
//                MNCF_EMBEDDED_RES_RELPATH . '/js/jquery-form-validation/additional-methods-1.11.1.min.js',
//                array('types-jquery-validation'), MNCF_VERSION, true );
//        mn_register_script( 'types-js-validation',
//                MNCF_EMBEDDED_RES_RELPATH . '/js/jquery-form-validation/types.js',
//                array('types-jquery-validation-additional'), MNCF_VERSION, true );
		mn_register_script( 'types-export-import', MNCF_EMBEDDED_RES_RELPATH . '/js/export-import.js',
                array( 'jquery' ), MNCF_VERSION, true );
		mn_register_script( 'types-settings', MNCF_EMBEDDED_RES_RELPATH . '/js/settings.js',
                array( 'jquery', 'underscore' ), MNCF_VERSION, true );
		$settings_script_texts = array(
			'mncf_settings_nonce'	=> mn_create_nonce( 'mncf_settings_nonce' )
		);
		mn_localize_script( 'types-settings', 'mncf_settings_i18n', $settings_script_texts );
    }

    /**
     * Register styles.
     */
    private static function __registerStyles() {
        mn_register_style( 'types',
                MNCF_EMBEDDED_RES_RELPATH . '/css/basic.css', array(),
                MNCF_VERSION );
        if ( !mn_style_is( 'toolset-colorbox', 'registered' ) ) {
            mn_register_style( 'toolset-colorbox',
                    MNCF_EMBEDDED_RES_RELPATH . '/css/colorbox.css', array(),
                    MNCF_VERSION );
        }
        if ( !mn_style_is( 'font-awesome', 'registered' ) ) {
            mn_register_style(
                'font-awesome',
	            MNCF_EMBEDDED_TOOLSET_RELPATH . '/toolset-common/res/lib/font-awesome/css/font-awesome.min.css',
                array(),
                '4.4.0'
            );
        }
        if ( !mn_style_is( 'toolset-dashicons', 'registered' ) ) {
            mn_register_style(
                'toolset-dashicons',
                MNCF_EMBEDDED_RES_RELPATH . '/css/dashicons.css',
                array(),
                MNCF_VERSION
            );
        }
    }

    /**
     * Returns HTML formatted output.
     *
     * @param string $view
     * @param mixed $data
     * @return string
     */
    public static function view( $view, $data = array() ) {
        $file = MNCF_EMBEDDED_ABSPATH . '/views/'
                . strtolower( strval( $view ) ) . '.php';
        if ( !file_exists( $file ) ) {
            return '<code>missing_view</code>';
        }
        ob_start();
        include $file;
        $output = ob_get_contents();
        ob_get_clean();

        return apply_filters( 'mncf_get_view', $output, $view, $data );
    }

    /**
     * Returns HTML formatted output.
     *
     * @param string $view
     * @param mixed $data
     * @return string
     */
    public static function loadView( $view ) {
        $file = MNCF_EMBEDDED_ABSPATH . '/views/'
                . strtolower( strval( $view ) ) . '.php';
        if ( !file_exists( $file ) ) {
            return new MN_Error( 'types_loader', 'missing view ' . $view );
        }
        require_once $file;
    }

    /**
     * Returns HTML formatted output.
     *
     * @param string $template
     * @param mixed $data
     * @return string
     */
    public static function template( $template, $data = array() ) {
        $file = MNCF_EMBEDDED_ABSPATH . '/views/templates/'
                . strtolower( strval( $template ) ) . '.tpl.php';
        if ( !file_exists( $file ) ) {
            return '<code>missing_template</code>';
        }
        ob_start();
        include $file;
        $output = ob_get_contents();
        ob_get_clean();

        return apply_filters( 'mncf_get_template', $output, $template, $data );
    }

    /**
     * Loads model.
     *
     * @param string $template
     * @param mixed $data
     * @return string
     */
    public static function loadModel( $model ) {
        $file = MNCF_EMBEDDED_ABSPATH . '/models/'
                . strtolower( strval( $model ) ) . '.php';
        if ( !file_exists( $file ) ) {
            return new MN_Error( 'types_loader', 'missing model ' . $model );
        }
        require_once $file;
    }

    /**
     * Loads class.
     *
     * @param string $template
     * @param mixed $data
     * @return string
     */
    public static function loadClass( $class ) {
        $file = MNCF_EMBEDDED_ABSPATH . '/classes/'
                . strtolower( strval( $class ) ) . '.php';
        if ( !file_exists( $file ) ) {
            return new MN_Error( 'types_loader', 'missing class ' . $class );
        }
        require_once $file;
    }

    /**
     * Loads include.
     *
     * @param string $template
     * @param mixed $data
     * @return string
     */
    public static function loadInclude( $name, $mode = 'embedded' ) {
        $path = $mode == 'plugin' ? MNCF_ABSPATH : MNCF_EMBEDDED_ABSPATH;
        $file = $path . '/includes/' . strtolower( strval( $name ) ) . '.php';
        if ( !file_exists( $file ) ) {
            return new MN_Error( 'types_loader', 'missing include ' . $name );
        }
        require_once $file;
    }

    /**
     * Adds JS settings.
     *
     * @staticvar array $settings
     * @param type $id
     * @param type $setting
     */
    public static function addJsSetting( $id, $setting = '' ) {
        self::$__settings[$id] = $setting;
    }

    /**
     * Renders JS settings.
     */
    public static function renderJsSettings() {
        $settings = (array) self::$__settings;
        $settings['mnnonce'] = mn_create_nonce( '_typesnonce' );
        $settings['cookiedomain'] = COOKIE_DOMAIN;
        $settings['cookiepath'] = COOKIEPATH;
        $settings['validation'] = array();
        echo '
        <script type="text/javascript">
            //<![CDATA[
            var types = ' . json_encode( $settings ) . ';
            //]]>
        </script>';
    }

    /**
     * Custom Content loading.
     */
    private static function __toolset() {
        // Views
        if ( defined( 'MNV_VERSION' ) ) {
            self::loadClass( 'mnviews' );
            MNCF_MNViews::init();
        }
    }

}
