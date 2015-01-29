<?php
abstract class MyWeb_Form_Decorator_Abstract
    extends Zend_Form_Decorator_Abstract
{
    protected function buildLabel()
    {
        $element = $this->getElement();
        $label = $element->getLabel();
        if ($translator = $element->getTranslator()) {
            $label = $translator->translate($label);
        }
        if ($element->isRequired()) {
            $label .= '*';
        }
        return $label;
    }

}