<?php
abstract class MyWeb_Form_Element_Checkbox
    extends Zend_Form_Element_Checkbox
{

    public function __construct($spec)
    {
        parent::__construct($spec);
        $this->setOptions(
            array(
                'checkedValue' => 'true',
                'uncheckedValue' => 'false'
            )
        );
        $this->addPrefixPath(
        	'MyWeb_Form_Decorator',
        	'MyWeb/Form/Decorator/',
       		'decorator'
        );
        $this->setDecorators(array('Checkbox'));
    }

}