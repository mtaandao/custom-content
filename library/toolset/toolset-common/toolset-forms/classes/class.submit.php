<?php
/**
 *
 *
 */
require_once 'class.textfield.php';

class MNToolset_Field_Submit extends MNToolset_Field_Textfield
{

    public function metaform()
    {
        $attributes = $this->getAttr();

        $metaform = array();
        $metaform[] = array(
            '#type' => 'submit',
            '#title' => $this->getTitle(),
            '#description' => $this->getDescription(),
            '#name' => $this->getName(),
            '#value' => esc_attr(__($this->getValue(), 'mnv-views')),
            '#validate' => $this->getValidationData(),
            '#attributes' => array(
                'class' => '',
            ),
        );
        if (array_key_exists( 'class', $attributes )) {
            $metaform[0]['#attributes']['class'] = $attributes['class'];
        }
        if ( array_key_exists( 'use_bootstrap', $this->_data ) && $this->_data['use_bootstrap'] ) {
            $metaform[0]['#attributes']['class'] .= ' btn btn-primary';
        }
        $this->set_metaform($metaform);
        return $metaform;
    }

}
