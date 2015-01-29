<?php
abstract class MyWS_Form_Element_Textarea
    extends Zend_Form_Element_Textarea
{

    public function init()
    {
        parent::init();
        $this->setAttrib("ROWS", 5);
    }

}