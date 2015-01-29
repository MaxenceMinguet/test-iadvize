<?php

class MyWeb_Plugin_Layout extends Zend_Controller_Plugin_Abstract  {

    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $abstract) {

        $layout = Zend_Layout::getMvcInstance();
        $layout->setLayout('layout')
            ->setLayoutPath(APPLICATION_PATH.'/modules/'.$abstract->getModuleName().'/layouts');
    }
}