<?php
abstract class MyWS_Form_Element_Checkbox
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
    }

}