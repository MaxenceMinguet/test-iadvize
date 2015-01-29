<?php

/**
 * 
 * API Post VDM
 * @author Maxence Minguet
 *
 */
class Api_PostsController
    extends MyWS_Controller_Action_Connected
{

	public function indexAction() {
		
		$author = $this->_getParam('author');
		$to = $this->_getParam('to');
		$from = $this->_getParam('from');
		
		
		$managerVdm = new DbTable_Iadvize_Vdm();
		
		if (empty($author) && empty($to) && empty($from)) {
			$data = $managerVdm->search(array("date_ws" => null));
			if (!empty($data)) {
				echo(Zend_Json::encode(array("posts" => $data, "count" => $managerVdm->count())));
				return;
			}
			echo(Zend_Json::encode(array("Error" => "Une erreur est survenue.")));
			return;
		}
		else if (!empty($author)) {
			$data = $managerVdm->elementOfAuthor($author);
			if (!empty($data)) {
				echo(Zend_Json::encode(array("posts" => $data, "count" => $managerVdm->count())));
				return;
			}
			echo(Zend_Json::encode(array("Error" => "$author : auteur introuvable.")));
			return;
		}
		else if (!empty($to) && !empty($from)) {
			$data = $managerVdm->elementToFrom($to, $from);
			if (!empty($data)) {
				echo(Zend_Json::encode(array("posts" => $data, "count" => $managerVdm->count())));
				return;
			}
			echo(Zend_Json::encode(array("Error" => "Aucun posts entre ces dates.")));
			return;
		}
		
		$data = null;
		
		echo(Zend_Json::encode(array("Error" => "Parametre incorrect.")));
	}
	
    public function idAction() {

    	$id = $this->_getParam('id');
    	
    	$managerVdm = new DbTable_Iadvize_Vdm();
        $post = $managerVdm->elementById($id);
        
        if (!empty($post)) {
        	echo(Zend_Json::encode(array("posts" => $post)));
        }
        else {
        	echo(Zend_Json::encode(array("Error" => "$id : id incorrect.")));
        }
    }   
}