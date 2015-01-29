<?php
/**
 * Route
 * @author Maxence Minguet
 *
 */
class Bootstrap
    extends My_Application_Bootstrap_Bootstrap
{

    protected function _initRoutes() {

        $this->bootstrap('frontController');
        $frontController = $this->getResource('frontController');
        $router = $frontController->getRouter();

        /* Module : default */
        $router->addRoute('about', new MyWeb_Controller_Router_Route_About());
        $router->addRoute('sitemap', new MyWeb_Controller_Router_Route_Sitemap());
    }

}

