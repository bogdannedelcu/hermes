<?php

trait CacheTools
{
	
	 public $cache=[];
	 
	 function createDictionaryFromQuery($sql, $dictionaryField, $keyField, $valueField)
	 {
		$query = $this->db->query($sql);
		$result = $query->getResultArray();

		foreach($result as $r)
		{
			$this->addToDictionary($r[$dictionaryField], [$r[$keyField]], $r[$valueField]);
			log_message('error',$r[$dictionaryField].'['.$r[$keyField].']='.$r[$valueField]);
		}
	 }
	 
	 function addToDictionary($dictionary,$keys,$value)
	 {
		 $key = implode('_',$keys);
		$this->cache[$dictionary][$key] = $value; 
	 }
	 
	 function getFromDictionary($dictionary,$keys)
	 {
		 $key = implode('_',$keys);
		if(isset($this->cache[$dictionary][$key])) 
			return $this->cache[$dictionary][$key];

		return [];
	 }
	
}

?>