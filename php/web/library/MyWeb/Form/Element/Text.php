<?php
abstract class MyWeb_Form_Element_Text
    extends Zend_Form_Element_Text
{
    public function __construct($spec)
    {
        parent::__construct($spec);
        $this->addPrefixPath(
        	'MyWeb_Form_Decorator',
        	'MyWeb/Form/Decorator/',
       		'decorator'
        );
        $this->setDecorators(array('Text'));
    }

}