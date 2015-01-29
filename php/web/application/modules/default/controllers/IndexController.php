<?php

class IndexController extends MyWeb_Controller_Action
{

	/**
	 * Page d'accueil
	 */
    public function indexAction()
    { 	
    	/* On affiche les posts contenu en base de donnée */
    	 
    	 $managerVdm = new DbTable_Iadvize_Vdm();
    	 $data = $managerVdm->search();
    	 
    	 $this->view->data = $data;
    }

    /**
     * Page a propos
     */
    public function aboutAction()
    {
    }
    
    /**
     * Page d'index des site maps
     */
    public function sitemapAction()
    {
        $this->getResponse()->setHeader('Content-type', 'application/xml');

        $this->_helper->layout()->disableLayout();
    }
}

