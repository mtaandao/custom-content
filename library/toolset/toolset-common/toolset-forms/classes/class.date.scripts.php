<?php

class MNToolset_Field_Date_Scripts
{

    public static $_supported_date_formats = array(
        'F j, Y', //December 23, 2011
        'Y/m/d', // 2011/12/23
        'm/d/Y', // 12/23/2011
        'd/m/Y', // 23/22/2011
        'd/m/y', // 23/22/11
    );

    public $_supported_date_formats_text = array(
        'F j, Y' => 'Month dd, yyyy',
        'Y/m/d' => 'yyyy/mm/dd',
        'm/d/Y' => 'mm/dd/yyyy',
        'd/m/Y' => 'dd/mm/yyyy',
        'd/m/y' => 'dd/mm/yy',
    );

    // 15/10/1582 00:00 - 31/12/3000 23:59
    protected static $_mintimestamp = -12219292800;
    protected static $_maxtimestamp =  32535215940;

    public function __construct()
    {
        global $pagenow;

	    $is_frontend = ( !is_admin() );

	    $current_admin_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : null;
	    $field_group_edit_pages = array( 'mncf-edit-usermeta', 'mncf-edit', 'mncf-termmeta-edit' );
	    $is_types_edit_page = in_array( $current_admin_page, $field_group_edit_pages );

	    $backend_field_edit_pages = array(
		    'profile.php', 'post-new.php', 'user-edit.php', 'user-new.php', 'post.php', 'admin-ajax.php',
		    'edit-tags.php', 'term.php'
	    );
	    $is_edit_page = ( is_admin() && in_array( $pagenow, $backend_field_edit_pages ) );

	    /**
	     * Allows for overriding the conditions for enqueuing scripts for date field.
	     *
	     * @param bool $enqueue_scripts If true, the scripts will be enqueued disregarding other conditions.
	     */
	    $is_activated_by_filter = apply_filters( 'toolset_forms_enqueue_date_scripts', false );

        if ( $is_frontend || $is_types_edit_page || $is_edit_page || $is_activated_by_filter ) {
            add_action( 'admin_enqueue_scripts', array( $this,'date_enqueue_scripts' ) );
            if ( defined('CRED_FE_VERSION')) {
                add_action( 'mn_enqueue_scripts', array( $this, 'date_enqueue_scripts' ) );
            }
        }
        $this->localization_slug = false;
    }

    public function date_enqueue_scripts()
    {
        /**
         * prevent load scripts on custom field group edit screen
         */
        if ( is_admin() ) {
            $screen = get_current_screen();
            if ( 'types_page_mncf-edit' == $screen->id ) {
                return;
            }
        }
        /**
         * styles
         */
        mn_register_style(
            'mntoolset-field-datepicker',
            MNTOOLSET_FORMS_RELPATH . '/css/mnt-jquery-ui/jquery-ui-1.11.4.custom.css',
            array(),
            '1.11.4'
        );

        /**
         * check first is mntoolset-forms registered?
         */
        if (!mn_script_is('mntoolset-forms', 'registered')) {
            mn_register_script(
                'mntoolset-forms',
                MNTOOLSET_FORMS_RELPATH . '/js/main.js',
                array('jquery', 'underscore', 'suggest'),
                MNTOOLSET_FORMS_VERSION,
                true
            );
        }

        /**
         * scripts
         */
        mn_register_script(
            'mntoolset-field-date',
            MNTOOLSET_FORMS_RELPATH . '/js/date.js',
            array('jquery-ui-datepicker', 'mntoolset-forms'),
            MNTOOLSET_FORMS_VERSION,
            true
        );
        // Localize datepicker
        if ( in_array( self::getDateFormat(), self::$_supported_date_formats ) ) {
            /*
            $locale = str_replace( '_', '-', strtolower( get_locale() ) );
            $file = MNTOOLSET_FORMS_ABSPATH . '/js/i18n/jquery.ui.datepicker-' . $locale . '.js';
            if ( file_exists( $file ) ) {
                mn_register_script(
                    'mntoolset-field-date-localized',
                    MNTOOLSET_FORMS_RELPATH . '/js/i18n/jquery.ui.datepicker-' . $locale . '.js',
                    array('jquery-ui-datepicker'),
                    MNTOOLSET_FORMS_VERSION,
                    true
                );
            }
            */
            $lang = get_locale();
            $lang = str_replace('_', '-', $lang);
            // TODO integrate this with MNML lang
            if ( file_exists( MNTOOLSET_FORMS_ABSPATH . '/js/i18n/jquery.ui.datepicker-' . $lang . '.js' ) ) {
                if ( !mn_script_is( 'jquery-ui-datepicker-local-' . $lang, 'registered' ) ) {
                    mn_register_script( 'jquery-ui-datepicker-local-' . $lang, MNTOOLSET_FORMS_RELPATH . '/js/i18n/jquery.ui.datepicker-' . $lang . '.js', array('jquery-ui-core', 'jquery', 'jquery-ui-datepicker'), MNTOOLSET_FORMS_VERSION, true );
                    $this->localization_slug = $lang;
                }
            } else {
                $lang = substr($lang, 0, 2);
                if ( file_exists( MNTOOLSET_FORMS_ABSPATH . '/js/i18n/jquery.ui.datepicker-' . $lang . '.js' ) ) {
                    if ( !mn_script_is( 'jquery-ui-datepicker-local-' . $lang, 'registered' ) ) {
                        mn_register_script( 'jquery-ui-datepicker-local-' . $lang, MNTOOLSET_FORMS_RELPATH . '/js/i18n/jquery.ui.datepicker-' . $lang . '.js', array('jquery-ui-core', 'jquery', 'jquery-ui-datepicker'), MNTOOLSET_FORMS_VERSION, true );
                        $this->localization_slug = $lang;
                    }
                }
            }
        }
        /**
         * styles
         */
        mn_enqueue_style( 'mntoolset-field-datepicker' );
        /**
         * scripts
         */
        mn_enqueue_script( 'mntoolset-field-date' );
        $date_format = self::getDateFormat();
        $js_date_format = $this->_convertPhpToJs( $date_format );
        $calendar_image = MNTOOLSET_FORMS_RELPATH . '/images/calendar.gif';
        $calendar_image = apply_filters( 'mntoolset_filter_mntoolset_calendar_image', $calendar_image );
        $calendar_image_readonly = MNTOOLSET_FORMS_RELPATH . '/images/calendar-readonly.gif';
        $calendar_image_readonly = apply_filters( 'mntoolset_filter_mntoolset_calendar_image_readonly', $calendar_image_readonly );
        $js_data = array(
            'buttonImage' => $calendar_image,
            'buttonText' => __( 'Select date', 'mnv-views' ),
            'dateFormat' => $js_date_format,
            'dateFormatPhp' => $date_format,
            'dateFormatNote' => esc_js( sprintf( __( 'Input format: %s', 'mnv-views' ), $date_format ) ),
            'yearMin' => intval( self::timetodate( self::$_mintimestamp, 'Y' ) ) + 1,
            'yearMax' => self::timetodate( self::$_maxtimestamp, 'Y' ),
            'ajaxurl' => admin_url('admin-ajax.php', null),
            'readonly' => esc_js( __( 'This is a read-only date input', 'mnv-views' ) ),
            'readonly_image' => $calendar_image_readonly,
        );
        mn_localize_script( 'mntoolset-field-date', 'mntDateData', $js_data );
        if ( $this->localization_slug && !mn_script_is( 'jquery-ui-datepicker-local-' . $this->localization_slug ) ) {
            mn_enqueue_script( 'jquery-ui-datepicker-local-' . $this->localization_slug );
        }
    }

    protected function _convertPhpToJs( $date_format )
    {
        $date_format = str_replace( 'd', 'dd', $date_format );
        $date_format = str_replace( 'j', 'd', $date_format );
        $date_format = str_replace( 'l', 'DD', $date_format );
        $date_format = str_replace( 'm', 'mm', $date_format );
        $date_format = str_replace( 'n', 'm', $date_format );
        $date_format = str_replace( 'F', 'MM', $date_format );
        $date_format = str_replace( 'y', 'y', $date_format );
        $date_format = str_replace( 'Y', 'yy', $date_format );

        return $date_format;
    }

    public static function getDateFormat() {
        $date_format = get_option( 'date_format' );
        if ( !in_array( $date_format, self::$_supported_date_formats ) ) {
            $date_format = 'F j, Y';
        }
        return $date_format;
    }

    public static function timetodate( $timestamp, $format = null )
    {
        if ( is_null( $format ) ) {
            $format = self::getDateFormat();
        }
        return self::_isTimestampInRange( $timestamp ) ? @adodb_date( $format, $timestamp ) : false;
    }

    public static function _isTimestampInRange( $timestamp )
    {
        return self::$_mintimestamp <= $timestamp && $timestamp <= self::$_maxtimestamp;
    }
}