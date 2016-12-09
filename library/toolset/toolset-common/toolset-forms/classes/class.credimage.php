<?php
require_once 'class.credfile.php';
require_once 'class.image.php';

/**
 * Description of class
 *
 * @author Srdjan
 *
 *
 */
class MNToolset_Field_Credimage extends MNToolset_Field_Credfile
{
    public function metaform()
    {
        //TODO: check if this getValidationData does not break PHP Validation _cakePHP required file.
        $validation = $this->getValidationData();
        $validation = MNToolset_Field_Image::addTypeValidation($validation);
        $this->setValidationData($validation);
        return parent::metaform();        
    }
}
