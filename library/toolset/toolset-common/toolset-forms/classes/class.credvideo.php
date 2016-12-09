<?php
require_once 'class.credfile.php';
require_once 'class.video.php';

/**
 * Description of class
 *
 * @author Srdjan
 *
 *
 */
class MNToolset_Field_Credvideo extends MNToolset_Field_Credfile
{
    protected $_settings = array('min_mn_version' => '3.6');

    public function metaform()
    {
        //TODO: check if this getValidationData does not break PHP Validation _cakePHP required file.
        $validation = $this->getValidationData();
        $validation = MNToolset_Field_Video::addTypeValidation($validation);
        $this->setValidationData($validation);
        return parent::metaform();        
    }
}
