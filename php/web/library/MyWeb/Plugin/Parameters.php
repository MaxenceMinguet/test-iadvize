<?php
class MyWeb_Plugin_Parameters extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$layout = Zend_Layout::getMvcInstance();
		
		$account = null;
	}
}