<?php
/**
 *
 *
 */
require_once "class.field_factory.php";
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class
 *
 * @author Franko
 */
class MNToolset_Field_Textfield extends FieldFactory
{
    public function metaform()
    {
        $metaform = array();
        $metaform[] = array(
            '#type'			=> 'textfield',
            '#title'		=> $this->getTitle(),
            '#description'	=> $this->getDescription(),
            '#name'			=> $this->getName(),
            '#value'		=> $this->getValue(),
            '#validate'		=> $this->getValidationData(),
            '#repetitive'	=> $this->isRepetitive(),
            '#attributes'	=> $this->getAttr(),
            'mnml_action'	=> $this->getMNMLAction(),
        );
        return $metaform;
    }

}
