<?php
abstract class MyWeb_Form_Element_Select
    extends Zend_Form_Element_Select
{
    public function __construct($spec)
    {
        parent::__construct($spec);
        $this->addPrefixPath(
        	'MyWeb_Form_Decorator',
        	'MyWeb/Form/Decorator/',
       		'decorator'
        );
        $this->setDecorators(array('Select'));
    }

}