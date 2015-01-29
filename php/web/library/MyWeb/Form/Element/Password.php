<?php
/**
 * Element Password de base avec décorateur
 * @author mathieu
 *
 */
abstract class MyWeb_Form_Element_Password
    extends Zend_Form_Element_Password
{
    public function __construct($spec)
    {
        parent::__construct($spec);
        $this->addPrefixPath(
        	'MyWeb_Form_Decorator',
        	'MyWeb/Form/Decorator/',
       		'decorator'
        );
        $this->setDecorators(array('Password'));
    }

}