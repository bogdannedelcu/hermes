<?php

namespace App\Models;

use CodeIgniter\Model;

class BillEverythingModel 
{
	protected $db;
	
	function __construct()
	{
		log_message("info","connect");
		$this->db = db_connect();
		
		//$this->_db = \Config\Database::connect();
	}
	
	function get_data($sql)
	{
		$query = $this->db->query($sql);		
		$result = $query->getRowArray();
		
		return $result;
	}
	
	function billEverything($supplierId,$customerId,$contractId,$zoneId,$invoiceDate)
	{
		//$this->db = db_connect();
		$sql = 'call billEverything('.$supplierId.",".$customerId.",".$contractId.",".$zoneId.",'".$invoiceDate."')";

		log_message('info',print_r($sql, TRUE));
		if (!$query = $this->db->query($sql))
		{
			$err = $this->db->error();			
			log_message('info',print_r($err, TRUE));
		}
		sleep(2);
		$this->db->close();
		$this->db->initialize(); 
		log_message('info',print_r($sql, TRUE));
		//$result = $query->getRowArray();
		
		//return $result['numFacturi'];
	}
	

	function getSuppliers()
	{

		$query = $this->db->query('select supplier_id, supplier_name from suppliers');				
		$result = $query->getResultArray();
		
		//log_message('info',print_r($result, TRUE));
		return $result;
	}		
}