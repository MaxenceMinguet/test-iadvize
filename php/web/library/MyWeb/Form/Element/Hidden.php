<?php
abstract class MyWeb_Form_Element_Hidden
    extends Zend_Form_Element_Hidden
{
    public function __construct($spec)
    {
        parent::__construct($spec);
        $this->removeDecorator('Errors');
        $this->removeDecorator('Label');
        $this->removeDecorator('HtmlTag');
    }
}