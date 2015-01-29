<?php
/**
 * Route pour le sitemap
 * /sitemap.xml
 * @author Maxence Minguet
 *
 */
class MyWeb_Controller_Router_Route_Sitemap 
	extends Zend_Controller_Router_Route
{
    public function __construct()
    {
        parent::__construct(
            '/sitemap.xml',
            array(
                'module'=>'default',
                'controller' => 'index',
                'action' => 'sitemap'
            )
        );
    }

}