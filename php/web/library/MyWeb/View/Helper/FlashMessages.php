<?php
class MyWeb_View_Helper_FlashMessages
	extends Zend_View_Helper_Abstract
{
	public function flashMessages()
	{
	    try {
    		$helper = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
    		return $helper->getMessages();
	    } catch (Exception $e) {
	        return array();
	    }
	}
	
}