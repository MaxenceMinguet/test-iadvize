<?php
class DbTable_Iadvize_Vdm extends My_Db_Table_Abstract {

    protected $_name = "vdm";

    public function searchSelect(array $options = array()) {

        $db = My_Db_Table_Abstract::getDefaultAdapter();
        $select = $db->select();

        $colFrom = array();
        $colFrom[] = "id_vdm";
        $colFrom[] = "id";
        if ($this->keyExists("date_ws", $options)) {
        	$colFrom[] = "date_post AS date";
        }
        else {
        	$colFrom[] = "date_post";
        }
        $colFrom[] = "author";
        $colFrom[] = "content";

       $select->from($this->_name, $colFrom, $this->_schema);
       
       $select->limit(200);
        
       $select->order($this->_name.'.id DESC');
        
       return $select;
    }
    
    
    /**
     * Retourne true si ce post exist déjà sinon false
     * 
     * @param integer $id
     * @return boolean:
     */
    public function alreadyExist($id)
    {
    	$db = My_Db_Table_Abstract::getDefaultAdapter();
    	 
    	$sql ="
    			SELECT id FROM public.vdm WHERE id = $id::integer
    			";
    	$items = $db->query($sql)->fetch();
        	 
    	if ($items != null) {
    		return true;
    	}
    	
		return false;
    }

    
    /**
     * Supprime le dernier élément (id le plus bas)
     *
     */
    public function deleteLastElement()
    {
    	$db = My_Db_Table_Abstract::getDefaultAdapter();
    
    	$sql ="
    		DELETE FROM public.vdm WHERE id_vdm IN (
			SELECT MIN(id_vdm) FROM public.vdm )
    	";
    	$db->query($sql)->fetch();
    }
    
    
    /**
     * Renvoie les posts du même auteur
     *
     *@param string $author
     *@return array|null  renvoie null si l'auteur n'existe pas
     */
    public function elementOfAuthor($author)
    {
    	$db = My_Db_Table_Abstract::getDefaultAdapter();

    	
    	$sql ="
    			SELECT id_vdm, id, date_post AS date, content, author FROM public.vdm
				WHERE author = '$author'
    	";
    	return ($db->query($sql)->fetchAll());   	   	
    }
    
    
    /**
     * Renvoie les posts entre deux dates
     *
     *@param string $to
     *@param string $from
     *@return array|null  renvoie null si aucun post n'est trouvé
     */
    public function elementToFrom($to, $from)
    {
    	$db = My_Db_Table_Abstract::getDefaultAdapter();
    
    	 
    	$sql ="
    	SELECT id_vdm, id, date_post AS date, content, author FROM public.vdm
    	WHERE date_post BETWEEN '$from' AND  '$to'
    	";
    	return ($db->query($sql)->fetchAll());
    }
    
    
    /**
     * Renvoie le post suivant son id
     *
     *@param integer $id
     *@return array|null  renvoie null si aucun post n'est trouvé
     */
    public function elementById($id)
    {
    	$db = My_Db_Table_Abstract::getDefaultAdapter();
    
    
    	$sql ="
    	SELECT id_vdm, id, date_post AS date, content, author FROM public.vdm
    	WHERE id = $id::integer
    	";
    	return ($db->query($sql)->fetchAll());
    }
}