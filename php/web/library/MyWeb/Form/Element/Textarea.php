<?php
abstract class MyWeb_Form_Element_Textarea
    extends Zend_Form_Element_Textarea
{

    public function init()
    {
        parent::init();
        $this->setAttrib("ROWS", 5);
        $this->addPrefixPath(
        	'MyWeb_Form_Decorator',
        	'MyWeb/Form/Decorator/',
       		'decorator'
        );
        $this->setDecorators(array('Textarea'));
    }

}