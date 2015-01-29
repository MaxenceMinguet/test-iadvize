<?php
class Bootstrap
    extends My_Application_Bootstrap_Bootstrap
{

	public function initRoutes()
	{
		$this->bootstrap('frontController');
	}
	
}

