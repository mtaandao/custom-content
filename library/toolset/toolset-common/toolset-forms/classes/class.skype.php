<?php
/**
 *
 *
 */
require_once 'class.textfield.php';

class MNToolset_Field_Skype extends MNToolset_Field_Textfield
{
    protected $_defaults = array(
        'skypename' => '',
        'action' => 'chat',
        'color' => 'blue',
        'size' => 32,
    );

    public function init()
    {
        add_action( 'admin_footer', array($this, 'editButtonTemplate') );
        add_action( 'mn_footer', array($this, 'editButtonTemplate') );

        mn_register_script(
            'skype-uri-buttom',
            '//www.skypeassets.com/i/scom/js/skype-uri.js'
        );

        mn_register_script(
            'mntoolset-field-skype',
            MNTOOLSET_FORMS_RELPATH . '/js/skype.js',
            array('jquery', 'skype-uri-buttom'),
            MNTOOLSET_FORMS_VERSION,
            true
        );
        mn_enqueue_script( 'mntoolset-field-skype' );
        add_thickbox();
        $translation = array('title' => esc_js( __( 'Edit Skype button', 'mnv-views' ) ) );
        mn_localize_script( 'mntoolset-field-skype', 'mntSkypeData', $translation );
        $this->set_placeholder_as_attribute();
    }

    public function enqueueStyles() {

    }

    public function metaform() {
        $value = mn_parse_args( $this->getValue(), $this->_defaults );
        $attributes = $this->getAttr();
		$mnml_action = $this->getMNMLAction();		
		
        if ( isset($attributes['class'] ) ) {
            $attributes['class'] .= ' ';
        } else {
            $attributes['class'] = '';
        }
        $attributes['class'] = 'js-mnt-skypename js-mnt-cond-trigger regular-text';// What is this js-mnt-cond-trigger classname for?
        $form = array();
        $form[] = array(
            '#type' => 'textfield',
            '#title' => $this->getTitle(),
            '#description' => $this->getDescription(),
            '#name' => $this->getName() . "[skypename]",
            '#attributes' => array(),
            '#value' => $value['skypename'],
            '#validate' => $this->getValidationData(),
            '#attributes' => $attributes,
            '#repetitive' => $this->isRepetitive(),
			'mnml_action' => $mnml_action,
        );

        /**
         * action
         */
        $form[] = array(
            '#type' => 'hidden',
            '#value' => $value['action'],
            '#name' => $this->getName() . '[action]',
            '#attributes' => array('class' => 'js-mnt-skype-action'),
        );

        /**
         * color
         */
        $form[] = array(
            '#type' => 'hidden',
            '#value' => $value['color'],
            '#name' => $this->getName() . '[color]',
            '#attributes' => array('class' => 'js-mnt-skype-color'),
        );

        /**
         * size
         */
        $form[] = array(
            '#type' => 'hidden',
            '#value' => $value['size'],
            '#name' => $this->getName() . '[size]',
            '#attributes' => array('class' => 'js-mnt-skype-size'),
        );

        if (!is_admin()) {
            return $form;
        }
        $button_element = array(
            '#name' => '',
            '#type' => 'button',
            '#value' => esc_attr( __( 'Edit Skype', 'mnv-views' ) ),
            '#attributes' => array(
                'class' => 'js-mnt-skype-edit-button button button-small button-secondary',
            ),
        );
		
		if (
			is_admin()
			&& defined( 'MNML_TM_VERSION' ) 
			&& intval( $mnml_action ) === 1 
			&& function_exists( 'mncf_mnml_post_is_original' )
			&& ! mncf_mnml_post_is_original()
			&& function_exists( 'mncf_mnml_have_original' )
			&& mncf_mnml_have_original()
		) {
			$button_element['#attributes']['disabled'] = 'disabled';
		}
		
        foreach( $value as $key => $val ) {
            $button_element['#attributes']['data-'.esc_attr($key)] = $val;
        }
        $form[] = $button_element;
        return $form;
    }

    public function editButtonTemplate()
    {

        static $edit_button_template_template_already_loaded;

        if ( $edit_button_template_template_already_loaded ) {
            return;
        }

        $edit_button_template_template_already_loaded = true;

        $form = array();
        $form['full-open'] = array(
            '#type' => 'markup',
            '#markup' => '<div id="tpl-mnt-skype-edit-button" style="display:none;"><div id="mnt-skype-edit-button-popup">',
        );
        $form['preview'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '<div id="mnt-skype-edit-button-popup-preview"><p class="bold">%s</p><div id="mnt-skype-edit-button-popup-preview-button"><div id="mnt-skype-preview"></div><small style="display:none">%s</small></div><p class="description"><strong>%s</strong>: %s</p></div>',
                __('Preview of your Skype button', 'mnv-views'),
                __('*Hover over to see the menu', 'mnv-views'),
                __('Note', 'mnv-views'),
                __('Skype button background is transparent and will work on any colour backgrounds.', 'mnv-views')
            ),
        );
        $form['options-open'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="main">',
        );
        $form['skypename'] = array(
            '#type' => 'textfield',
            '#name' => 'skype[name]',
            '#attributes' => array(
                'class' => 'js-mnt-skypename-popup js-mnt-skype',
                'data-skype-field-name' => 'skypename',
            ),
            '#before' => sprintf('<h3>%s</h2>', __( 'Enter your Skype Name', 'mnv-views' )),
        );
        $form['skype-action'] = array(
            '#type' => 'checkboxes',
            '#name' => 'skype[action]',
            '#options' => array(
                'call' => array(
                    '#name' => 'skype[action][call]',
                    '#value' => 'call',
                    '#title' => __('Call', 'mnv-views'),
                    '#description' => __('Start a call with just a click.', 'mnv-views'),
                    '#default_value' => 'call',
                    '#attributes' => array(
                        'class' => 'js-mnt-skype js-mnt-skype-action js-mnt-skype-action-call',
                        'data-skype-field-name' => 'action',
                    ),
                ),
                'chat' => array(
                    '#name' => 'skype[action][chat]',
                    '#title' => __('Chat', 'mnv-views'),
                    '#value' => 'chat',
                    '#description' => __('Start the conversation with an instant message.', 'mnv-views'),
                    '#attributes' => array(
                        'class' => 'js-mnt-skype js-mnt-skype-action js-mnt-skype-action-chat',
                        'data-skype-field-name' => 'action',
                    ),
                ),
            ),
            '#before' =>  sprintf('<h3>%s</h3>', __( "Choose what you'd like your button to do", 'mnv-views' )),
        );

        $form['skype-color-header'] = array(
            '#type' => 'markup',
            '#markup' =>  sprintf('<h3>%s</h3>', __( 'Choose how you want your button to look', 'mnv-views' )),
        );

        $form['skype-color'] = array(
            '#type' => 'select',
            '#name' => 'skype[color]',
            '#options' => array(
                array(
                    '#value' => 'blue',
                    '#title' => __('Blue', 'mnv-views'),
                    '#attributes' => array(
                        'data-skype-field-name' => 'color',
                        'class' => 'js-mnt-skype',
                    ),
                ),
                array(
                    '#value' => 'white',
                    '#title' => __('White', 'mnv-views'),
                    '#attributes' => array(
                        'data-skype-field-name' => 'color',
                        'class' => 'js-mnt-skype',
                    ),
                ),
            ),
            '#default_value' => 'blue',
            '#attributes' => array(
                'class' => 'js-mnt-skype js-mnt-skype-color'
            ),
            '#inline' => true,
        );
        $form['skype-size'] = array(
            '#type' => 'select',
            '#name' => 'skype[size]',
            '#options' => array(),
            '#default_value' => 32,
            '#attributes' => array(
                'class' => 'js-mnt-skype js-mnt-skype-size'
            ),
            '#inline' => true,
        );
        foreach( array(10,12,14,16,24,32) as $size ) {
            $form['skype-size']['#options'][] = array(
                '#value' => $size,
                '#title' => sprintf('%dpx', $size),
                '#attributes' => array(
                    'data-skype-field-name' => 'size',
                    'class' => 'js-mnt-skype',
                ),
            );
        }
        $form['options-close'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
        );

        $form['submit'] = array(
            '#type' => 'button',
            '#name' => 'skype[submit]',
            '#attributes' => array(
                'class' => 'button-secondary js-mnt-close-thickbox',
            ),
            '#value' => __( 'Save', 'mnv-views' ),
        );

        $form['full-close'] = array(
            '#type' => 'markup',
            '#markup' => '</div></div>',
        );

        $theForm = new Enlimbo_Forms( __FUNCTION__ );
        $theForm->autoHandle( __FUNCTION__, $form);
        echo $theForm->renderElements($form);
    }

    public function editform( $config = null ) {

    }

    public function mediaEditor(){
        return array();
    }

}
