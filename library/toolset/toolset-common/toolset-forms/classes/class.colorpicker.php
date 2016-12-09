<?php
/**
 *
 *
 */
require_once 'class.field_factory.php';

/**
 * Description of class
 *
 * @author Srdjan
 */
class MNToolset_Field_Colorpicker extends FieldFactory
{
    public function init()
    {
        if ( !is_admin() ) {
            mn_enqueue_style( 'mn-color-picker' );
            mn_enqueue_script(
                'iris',
                admin_url( 'js/iris.min.js' ),
                array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ),
                false,
                1
            );
            mn_enqueue_script(
                'mn-color-picker',
                admin_url( 'js/color-picker.min.js' ),
                array( 'iris' ),
                false,
                1
            );
            $colorpicker_l10n = array(
                'clear' => __( 'Clear' ),
                'defaultString' => __( 'Default', 'mnv-views' ),
                'pick' => __( 'Select', 'mnv-views' )." Color"
            );
            mn_localize_script( 'mn-color-picker', 'mnColorPickerL10n', $colorpicker_l10n );
        }
        mn_register_script(
            'mntoolset-field-colorpicker',
            MNTOOLSET_FORMS_RELPATH . '/js/colorpicker.js',
            array('iris'),
            MNTOOLSET_FORMS_VERSION,
            true
        );
        mn_enqueue_script( 'mntoolset-field-colorpicker' );
        $this->set_placeholder_as_attribute();
    }

    static public function registerScripts()
    {
    }

    public function enqueueScripts()
    {

    }

    public function addTypeValidation($validation) {
        $validation['hexadecimal'] = array(
            'args' => array(
                'hexadecimal'
            ),
            'message' => __('Please use a valid hexadecimal value.', 'mnv-views' ),
        );
        return $validation;
    }

    public function metaform()
    {
        $validation = $this->getValidationData();
        $validation = $this->addTypeValidation($validation);
        $this->setValidationData($validation);

        $attributes = $this->getAttr();
        if ( isset($attributes['class'] ) ) {
            $attributes['class'] .= ' ';
        } else {
            $attributes['class'] = '';
        }
        $attributes['class'] = 'js-mnt-colorpicker';

        $form = array();
        $form['name'] = array(
            '#type'			=> 'textfield',
            '#title'		=> $this->getTitle(),
            '#description'	=> $this->getDescription(),
            '#value'		=> $this->getValue(),
            '#name'			=> $this->getName(),
            '#attributes'	=> $attributes,
            '#validate'		=> $validation,
            '#after'		=> '',
            '#repetitive'	=> $this->isRepetitive(),
			'mnml_action'	=> $this->getMNMLAction(),
        );
        return $form;
    }

    public static function filterValidationValue($value)
    {
        if ( isset( $value['datepicker'] ) ) {
            return $value['datepicker'];
        }
        return $value;
    }
}
