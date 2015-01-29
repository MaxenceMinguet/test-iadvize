<?php
class My_Application_Bootstrap_Bootstrap
    extends Zend_Application_Bootstrap_Bootstrap
{

	protected function _initAutoload()
	{
		$config = new Zend_Config($this->getOptions());
		Zend_Registry::set('config', $config);
	}
	
    /**
     * Init translation for Zend validators
     */
    protected function _initTranslation()
    {
        $translator = new Zend_Translate(
            array(
                'adapter' => 'array',
                'content' => APPLICATION_PATH.'/../../resources/languages',
                'locale'  => 'fr_FR',
                'scan' => Zend_Translate::LOCALE_DIRECTORY
            )
        );
        Zend_Validate_Abstract::setDefaultTranslator($translator);
    }
}