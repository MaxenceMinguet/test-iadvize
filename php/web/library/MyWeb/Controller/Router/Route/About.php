<?php
/**
 * Route pour le about.
 * /a-propos
 * @author Maxence minguet
 *
 */
class MyWeb_Controller_Router_Route_About
    extends Zend_Controller_Router_Route
{

	public function __construct()
	{
		parent::__construct(
            '/a-propos',
            array(
                'module' => 'default',
                'controller' => 'index',
                'action'     => 'about',
            )
        );
	}
}