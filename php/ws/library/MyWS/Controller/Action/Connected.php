<?php
class MyWS_Controller_Action_Connected
    extends MyWS_Controller_Action
{

    public function init()
    {
        parent::init();

        $this->view->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
        header('Content-type: application/json');
    }

}