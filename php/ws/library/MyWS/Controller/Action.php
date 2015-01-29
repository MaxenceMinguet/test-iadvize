<?php
class MyWS_Controller_Action
    extends Zend_Controller_Action
{

    public function init()
    {
        parent::init();
        
        $this->view->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
        header('Content-type: application/json');
    }

    /**
     * Check if a form is POST and valid.
     * @param Zend_Form $form
     * @return boolean
     */
    public function isValidForm(&$form)
    {
        return $this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost());
    }

    protected function checkPost()
    {
        if (!$this->getRequest()->isPost()) {
            $this->getResponse()->setHttpResponseCode(405);
            echo(Zend_Json::encode("Method is not POST"));
            exit();
        }
    }

    protected function checkForm(&$form)
    {
        if (!$form->isValid($this->getRequest()->getPost())) {
            $this->getResponse()->setHttpResponseCode(412);
            $errors = array_filter($form->getErrors());
            
            if(isset($errors['email']) && in_array('recordFound', $errors['email'])) {
            	echo(Zend_Json::encode(array('code' => 413, 'errors' => $errors)));
            }
            else {
            	echo(Zend_Json::encode(array('code' => 412, 'errors' => $errors)));
            }
            
            exit();
        }
    }

}