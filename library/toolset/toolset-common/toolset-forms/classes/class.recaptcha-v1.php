<?php
require_once 'class.textfield.php';

/**
 * Description of class
 *
 * @author Srdjan
 */
class MNToolset_Field_Recaptcha extends MNToolset_Field_Textfield
{
    private $pubkey = '';
    private $privkey = '';
    private $settings;
    
    public function init() {          
        require_once ( MNTOOLSET_FORMS_ABSPATH."/js/recaptcha-php-1.11/recaptchalib.php");
        
        //$settings_model = CRED_Loader::get('MODEL/Settings');
        //$this->settings = $settings_model->getSettings();        
        $attr = $this->getAttr();
        $this->pubkey = isset($attr['public_key']) ? $attr['public_key'] : '';
        $this->privkey = isset($attr['private_key']) ? $attr['private_key'] : '';

        mn_register_script( 'mnt-cred-recaptcha',
                MNTOOLSET_FORMS_RELPATH . '/js/recaptcha-php-1.11/recaptcha_ajax.js',
                array('mntoolset-forms'), MNTOOLSET_FORMS_VERSION, true );
		mn_enqueue_script( 'mnt-cred-recaptcha' );
    }

    public static function registerStyles() {
    }

    public function enqueueScripts() {
        
    }

    public function enqueueStyles() {        
    }

    public function metaform() {        
        $form = array();
		
	$capture = '';
        if ($this->pubkey || !is_admin()) {
            try {
                $capture = recaptcha_get_html($this->pubkey,null,is_ssl());
            } catch(Exception $e ) {
                //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/188424989/comments
                if ( current_user_can( 'manage_options' ) ) {
                    $id_field = $this->getId();
                    $text = 'Caught exception: '.  $e->getMessage();
                    $capture = "<label id=\"lbl_$id_field\" class=\"mnt-form-error\">$text</label><div style=\"clear:both;\"></div>";                    
                }
                //###########################################################################################
            }
        }

        $form[] = array(
            '#type' => 'textfield',
            '#title' => '',
            '#name' => '_recaptcha',
            '#value' => '',
            '#attributes' => array( 'style' => 'display:none;'),
            '#before' => $capture
        );
        
        return $form;
    }
}
