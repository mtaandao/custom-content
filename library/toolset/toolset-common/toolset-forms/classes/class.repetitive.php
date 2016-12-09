<?php
/*
 * Repetitive controller
 *
 *
 * If field is repetitive
 * - queues repetitive CSS and JS
 * - renders JS templates in admin footer
 */
class MNToolset_Forms_Repetitive
{
    private $__templates = array();

    function __construct(){
        // Register
        mn_register_script( 'mntoolset-forms-repetitive',
                MNTOOLSET_FORMS_RELPATH . '/js/repetitive.js',
                array('jquery', 'jquery-ui-sortable', 'underscore'), MNTOOLSET_FORMS_VERSION,
                true );
//        mn_register_style( 'mntoolset-forms-repetitive', '' );
        // Render settings
        add_action( 'admin_footer', array($this, 'renderTemplates') );
        add_action( 'mn_footer', array($this, 'renderTemplates') );

        mn_enqueue_script( 'mntoolset-forms-repetitive' );
		
	}

    function add( $config, $html ) {
        if ( !empty( $config['repetitive'] ) ) {
            $this->__templates[$config['id']] = $html;
        }
    }

    function renderTemplates() {
        foreach ( $this->__templates as $id => $template ) {
            echo '<script type="text/html" id="tpl-mnt-field-' . $id . '">'
            . $template . '</script>';
        }
    }
}
