<?php
/**
 * Met les 200 derniers posts VDM ou les met à jour dans la base de donnée
 *
 */

/**
 *
 * @package batch
 */
class My_Batch_PutLastPostVdm extends My_Batch_Abstract implements My_Batch_Interface {
	/**
	 * corps du batch
	 */
	public function run() {

		$this->ajoutTrace('PutLastPostVdm');
		
		/* Recherche des 200 derniers post VDM */
		$result = array();
		$pageNumber = 0;
		$numberContent = 0;
		while ($numberContent <= 200) {
			$page = file_get_contents( 'http://www.viedemerde.fr/?page='.$pageNumber );
			$numberContent += preg_match_all( "/<div class=\"post article\" .*?class=\"tooltips t_twitter/s", $page, $match);
			$result = array_merge($result, $match[0]);
			$pageNumber++;
		}
		 
		/* Extraction des données pour chaque post */
		$post = array();
		foreach ($result as $div) {
			/* Contenu*/
			$contentFull = '';
			preg_match_all( "/ class=\"fmllink\">.*?<\/a></s", $div, $content);
			if (!empty($content[0])) {
				if (!empty($content[0][0])) {
					preg_match("/ class=\"fmllink\">(.*)<\/a></s", $content[0][0], $contentFirst);
					if (!empty($contentFirst[1])) {
						$contentFull = $contentFirst[1];
					}
				}
				if (!empty($content[0][1])) {
					preg_match("/ class=\"fmllink\">(.*)<\/a></s", $content[0][1], $contentSecond);
					if (!empty($contentSecond[1])) {
						$contentFull .= $contentSecond[1];
					}
				}
				if (!empty($content[0][2])) {
					preg_match("/ class=\"fmllink\">(.*)<\/a></s", $content[0][2], $contentThird);
					if (!empty($contentThird[1])) {
						$contentFull .= $contentThird[1];
					}
				}
			}
			 
			/* Date */
			preg_match( "/[0-9]+\/[0-9]+\/[0-9]+/", $div, $date);
			if (!empty($date[0])) {
				$date = explode('/', $date[0]);
				if (!empty($date[0]) && !empty($date[1]) && !empty($date[2])) {
					$date = new Zend_Date(array('day' => $date[0],
							'month' => $date[1],
							'year' => $date[2]
					));
					$date = $date->get(Zend_Date::W3C);
				}
			}
			else {
				$date = '';
			}
				
			/* Auteur */
			preg_match( "/<\/a> - par (.*) <\/p><\/div>/s", $div, $author);
			if (!empty($author[1])) {
				$author = $author[1];
			}
			else {
				$author = '';
			}
			 
			/* Id */
			preg_match( "/<div class=\"post article\" id=\"(.*)\"><p><a href=\"/", $div, $id);
			if (!empty($id[1])) {
				$id = $id[1];
			}
			else {
				$id = 0;
			}
			 
			$post[] = array('content' => $contentFull,
					'date_post' => $date,
					'author' => $author,
					'id' => intval($id)
			);
		}
		
		/* On les met en lien avec la base de donnée */
		$numberContent = 0;
		$managerVdm = new DbTable_Iadvize_Vdm();
		foreach ($post as $data) {
			if ($data['id'] != 0 && !$managerVdm->alreadyExist($data['id']) && $numberContent < 200) {
				$managerVdm->insert($data);
				if ($managerVdm->count() >= 201) {
					$managerVdm->deleteLastElement();
				}
			}
			else {
				break;
			}
			$numberContent++;
		}
	}
	
	
	
	/**
	 * retourne la liste des parametres pour le batch
	 * @return array tableau au format Zend_GetOpt
	 */
	public function getParams() {
		return array();
	}
	/**
	 * retourne le nom du batch
	 * @return string
	 */
	public function getNomBatch() {
		return __CLASS__;
	}
	
	/**
	 * retourne le nom d'alerte à remonter
	 */
	public function getService() {
		return 'Batch_PutLastPostVdm';
	}
}