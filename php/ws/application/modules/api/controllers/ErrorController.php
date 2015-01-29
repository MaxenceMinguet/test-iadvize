<?php

class ApiV1_ErrorController
    extends Zend_Controller_Action
{

    public function errorAction()
    {

        $errors = $this->_getParam('error_handler');
        var_export($errors->exception->getMessage());
        exit;

        $errors = $this->_getParam('error_handler');

        if (!$errors || !$errors instanceof ArrayObject) {
            $message = 'You have reached the __constructerror page';
            $this->getResponse()->setHttpResponseCode(500);
            echo(Zend_Json::encode($message));
            return;
        }

        $message = "";

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $priority = Zend_Log::NOTICE;
                $message = 'Page not found';
                break;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                $priority = Zend_Log::CRIT;
                $message = 'Application error';
                break;
        }

        //*
        echo(Zend_Json::encode($message));
        /*/
        $this->view->message = $message;
        //*/

        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->log($message, $priority, $errors->exception);
            $log->log('Request Parameters', $priority, $errors->request->getParams());
        }

        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $errors->exception;
        }

        $this->view->request   = $errors->request;
    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }


}

