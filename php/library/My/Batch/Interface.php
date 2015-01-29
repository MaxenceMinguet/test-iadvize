<?php
/**
 * interface à utiliser pour toute création de batch
 */

/**
 * @package batch
 */
interface My_Batch_Interface 
{
	/**
	 * ensemble des tâches à executer 
	 * les ensembles des instructions à réaliser par le batch sont codés ici
	 */
	
	public function run();
	/**
	 * retourne l'aide de la classe batch pour le mode CLI
	 * @return string
	 */
	public function getHelp();
	
	/**
	 * ensemble des paramètres possible pour le batch
	 * @return array la syntaxe est celle des paramètres de la classe Zend_Getopt
	 */
	public function getParams();

	/**
	 * nom du service à prevenir à la fin de l'excution de script
	 */
	public function getService();
	
	/**
	 * retourne le nom du batch
	 * return __CLASS__; mettre juste ce bout de code.
	 * @return string
	 */
	public function getNomBatch();

}