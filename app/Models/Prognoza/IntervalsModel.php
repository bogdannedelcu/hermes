<?php

namespace App\Models\Prognoza;

use CodeIgniter\Model;
use CodeIgniter\ConnectionInterface;

require_once(__DIR__.'/../CacheTools.php');
require_once(__DIR__.'/../MasterDataTools.php');

class IntervalsModel extends Model
{
	use \MasterDataTools;
	use \CacheTools;
	protected $table      = 'forecast_intervals';
	protected $db;
	protected $supplierID;
	
	function __construct()
	{
		$this->db = db_connect();
		$this->supplierID = $_SESSION['select-supplier'];
	}
	
	public function getIntervalData($customerID)
	{
		if($customerID=='') $customerID = 0;
		$sql = "select * from forecast_intervals fi where customer_id=$customerID and supplier_id = {$this->supplierID}";
		return $this->getArray($sql);
	}
	
	public function uploadIntervals($customerID, $values)
	{
		if($customerID=='') $customerID = 0;
		
		$sqlData='';
				
		for($h=0;$h<24;$h++)
		{
			for($m = 1;$m<=12;$m++)
			{
				$iType = $values[$h][$m-1];
				if($iType != 'Z' && $iType != 'N') $iType = 'N';
				$sqlData.="({$this->supplierID}, $customerID, $m, $h, '$iType'),";
			}
		}
		
		
		if(!empty($sqlData)) 
		{
			$sqlData = substr_replace($sqlData,'',-1);
			$added = $this->importMinProdSQLValues($sqlData);
		}
		
		return (24*12)." valori actualizate!";
	}
	
	public function importMinProdSQLValues($data)
	{
		$table = 'forecast_intervals';
		
		$sql = "insert into $table (supplier_id, customer_id, month, hour, interval_type) VALUES $data ON DUPLICATE KEY UPDATE interval_type=VALUES(interval_type)";
		return $this->writeData($sql);
	}
	
	public function updateCorrelatedMonths($month,$correlated_months)
	{
		$sql = "delete from months_correlation where month = $month";
		$this->writeData($sql);
		
		foreach($correlated_months as $cm)
		{
			$sql = "insert ignore into months_correlation (month, correlated_month) VALUES ($month, $cm)";
			$this->writeData($sql);
		}
	}
}