<?php
class MyWS_Controller_Helper_SendJson extends Zend_Controller_Action_Helper_Abstract
{
    public function direct($data, $replaceNull = true)
    {
        $processingNullByString = function(&$item, $key) {
            if ($item === null) {
                $item = '';
            }
            //delete spaces
            //$item = trim($item);
        };

        if (!empty($data)) {
	        if($replaceNull === true){
	            array_walk_recursive($data, $processingNullByString);
	        }
        }

        Zend_Controller_Action_HelperBroker::getStaticHelper('json')->sendJson($data);
    }
}