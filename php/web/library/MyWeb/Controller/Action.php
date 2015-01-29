<?php
class MyWeb_Controller_Action
    extends Zend_Controller_Action
{

    public function init()
    {
        parent::init();
        
        $this->view->addScriptPath(APPLICATION_PATH.'/modules/default/layouts/');
        
        $this->view->addHelperPath(APPLICATION_PATH.'/../library/MyWeb/View/Helper/', 'MyWeb_View_Helper');
    }        
}