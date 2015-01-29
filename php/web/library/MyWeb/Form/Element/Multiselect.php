<?php
abstract class MyWeb_Form_Element_Multiselect
    extends Zend_Form_Element_Multiselect
{
    public function __construct($spec)
    {
        parent::__construct($spec);
        $this->setDecorators(array( 'ViewHelper', 'Errors', 'Label'));
    }

}