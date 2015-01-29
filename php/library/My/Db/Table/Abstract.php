<?php
/**
 * Iadviaze
 * @package    common
 * @subpackage My_Db_Table
 */
/**
 * @package common
 * @subpackage My_Db_Table
 */

abstract class My_Db_Table_Abstract extends Zend_Db_Table_Abstract
{

    protected $_schema = "iadvize";

    /**
     * Retourne tous les résultats correspondants au critère passé en option
     * @param array $options
     * @return Ambigous <multitype:, multitype:mixed Ambigous <string, boolean, mixed> >
     */
    public function search(array $options = array())
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        return $db->fetchAll($this->searchSelect($options));
    }

    /**
     * Requête de recherche générique dans une table
     */
    public function searchSelect(array $options = array())
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $select = $db->select()
        ->from($this->_name)
        ->order('name');

        return $select;
    }

    /**
     * Surcharge permettant de gérer le cas ou la sequence se trouve dans un autre schema.
     * Pdo/Abstract ajoutera un _seq a la fin de la sequence, ce qui risque de faire sequence_seq_seq
     * @param  string $key The specific info part to return OPTIONAL
     * @return mixed
     * @throws Zend_Db_Table_Exception
     */
    public function info($key = null)
    {

        $return = parent::info($key);

        if ($key == parent::SEQUENCE) {
            return preg_replace('#_seq#', '', $return);
        }

        return $return;
    }
    /**
     * Retourne le nombre d'éléments dans la table
     */
    public function count()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $select = $db->select();

        $colFrom[] = 'count(*)';
        $select->from($this->_name, $colFrom, $this->_schema);
        return $db->fetchOne($select);
    }

    /**
     * Vérifie l'existence d'une clé et d'une valeur associée dans le tableau
     * d'options.
     * @param string $key clé à rechercher
     * @param array $options tableau d'options
     * @return boolean existance de la clé et d'une valeur
     */
    protected function keyExists($key, $options)
    {
    	return array_key_exists($key, $options) && !is_null($options[$key]);
    }

    public function getName()
    {
        return $this->_name;
    }
}