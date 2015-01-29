<?php
/**
 * Element Password de base avec décorateur
 * @author mathieu
 *
 */
abstract class MyWS_Form_Element_Password
    extends Zend_Form_Element_Password
{
    public function __construct($spec)
    {
        parent::__construct($spec);
    }

}