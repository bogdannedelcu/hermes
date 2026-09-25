<?php

namespace App\Models\Prognoza;

use CodeIgniter\Model;
use CodeIgniter\ConnectionInterface;

require_once(__DIR__.'/../CacheTools.php');
require_once(__DIR__.'/../MasterDataTools.php');

class PCModel extends Model
{
	use \MasterDataTools;
	use \CacheTools;
	protected $table      = 'prodcast_regions';
	protected $db;
	
	protected $supplierID;
	
	function __construct()
	{
		$this->db = db_connect();
		
		$this->supplierID = $_SESSION['select-supplier'];
	}
	
	function deleteRegionByName($regionName)
	{
		$sql = "DELETE from procast_regions where region_name={$this->db->escape($regionName)} and supplier_id = {$this->supplierID}";
		log_message('info',$sql);
		$this->writeData($sql);
	}
	
	public function uploadMinProduction($values)
	{
		$sqlData='';
		
		for($h=0;$h<24;$h++)
		{
			for($m = 1;$m<=12;$m++)
			{
				$ea = $values[$h][$m-1];
				
				if($ea=='') $ea = 0;
				
				$sqlData.="({$this->supplierID}, $m, $h, $ea),";
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
		$table = 'procast_minimum_production';
		
		$sql = "insert into $table (supplier_id, month, hour, ea) VALUES $data ON DUPLICATE KEY UPDATE ea=VALUES(ea)";
		return $this->writeData($sql);
	}
	
	public function uploadProduction($estimated, $regionName, $YMdate, $values)
	{
		$sqlData='';
		$sqlDeleteData='';
		$regionID = $this->getRegionID($regionName);
		
		$dt =  \DateTime::createFromFormat('Y-n',$YMdate);
		$YMdate = $dt->format('Y-m');
		$maxDays = cal_days_in_month(CAL_GREGORIAN,date('n',$dt->getTimestamp()),date('Y',$dt->getTimestamp()));
		
		for($h=0;$h<24;$h++)
		{
			for($d = 1;$d<=$maxDays;$d++)
			{
				$dateTime = $YMdate.'-'.sprintf('%02d',$d).' '.sprintf('%02d',$h).':00:00';
				$ea = $values[$h][$d-1];
				
				if($ea!='')
					$sqlData.="({$this->supplierID}, $regionID, '$dateTime', $ea),";
				else
					$sqlDeleteData.="'$dateTime',";
			}
		}
		
		
		$added = 0; $deleted = 0;
		if(!empty($sqlData)) 
		{
			$sqlData = substr_replace($sqlData,'',-1);
			$added = $this->importSQLValues($estimated, $sqlData);
		}
		
		if(!empty($sqlDeleteData)) 
		{
			$sqlDeleteData = rtrim($sqlDeleteData,',');
			$deleted = $this->deleteSQLValues($estimated, $regionID, $sqlDeleteData);
		}
		
		
		return 24*$maxDays." valori actualizate!";
	}
	
	public function uploadTypicalProduction($estimated, $regionName, $month, $values)
	{
		$sqlData='';
		$sqlDeleteData='';
		$regionID = $this->getRegionID($regionName);
		
		$dt =  \DateTime::createFromFormat('Y-n-d',"2024-$month-01");
		$YMdate = $dt->format('Y-m');
		$maxDays = cal_days_in_month(CAL_GREGORIAN,date('n',$dt->getTimestamp()),date('Y',$dt->getTimestamp()));
		
		for($h=0;$h<24;$h++)
		{
			for($d = 1;$d<=$maxDays;$d++)
			{
				$ea = $values[$h][$d-1];
				
				if($ea=='') $ea = 0;
				
				$sqlData.="({$this->supplierID}, $regionID, $month, $d, $h, $ea),";
			}
		}
		
		
		$added = 0; $deleted = 0;
		if(!empty($sqlData)) 
		{
			$sqlData = substr_replace($sqlData,'',-1);
			$added = $this->importTypicalSQLValues($estimated, $sqlData);
		}
		
		return 24*$maxDays." valori actualizate!";
	}

	public function importTypicalSQLValues($estimated, $data)
	{
		$table = 'procast_prod_typical';
		
		$sql = "insert into $table (supplier_id, region_id, month, day, hour, ea) VALUES $data ON DUPLICATE KEY UPDATE ea=VALUES(ea)";
		return $this->writeData($sql);
	}

	public function importSQLValues($estimated, $data)
	{
		$table = 'procast_prod_forecast';
		
		$sql = "insert into $table (supplier_id, region_id, prod_datetime, ea) VALUES $data ON DUPLICATE KEY UPDATE ea=VALUES(ea)";
		return $this->writeData($sql);
	}

	public function deleteSQLValues($estimated, $regionID, $data)
	{
		$table = 'procast_prod_forecast';
		
		$sql = "delete from $table where supplier_id = {$this->supplierID} AND region_id = $regionID AND prod_datetime in ($data)";
		return $this->writeData($sql);
	}
	
	
	public function getProCastRegions()
	{
		return $this->getArray("SELECT region_id AS value ,region_name AS text FROM procast_regions  WHERE supplier_id={$this->supplierID} ORDER BY region_name");
	}
	
	public function getEstimatedProduction($regionID, $month, $year)
	{
		$sql = "select * from procast_prod_forecast ps where supplier_id = {$this->supplierID} and region_id = $regionID and year(prod_datetime)=$year and month(prod_datetime)=$month order by hour(prod_datetime), day(prod_datetime)";
		//log_message('error',$sql);
		return $this->getArray($sql);
	}

	public function getTypicalProduction($regionID, $month)
	{
		$sql = "select * from procast_prod_typical ps where supplier_id = {$this->supplierID} and region_id=$regionID and month=$month order by hour, day";
		//log_message('error',$sql);
		return $this->getArray($sql);
	}
	
	public function getRegionID($regionName)
	{
		$sql = "SELECT region_id as value FROM procast_regions pr WHERE pr.supplier_id = {$this->supplierID} and pr.region_name = {$this->db->escape($regionName)}";
		return $this->getValue($sql);
	}
	
	public function getMinProduction()
	{
		$sql = "SELECT month, hour, ea FROM procast_minimum_production ORDER BY hour, month";
		return $this->getArray($sql);
	}
	
	public function downloadSky($regionName, $year, $month)
	{
		/*
		$regionData = $this->getRegionDataByName($regionName);
		
		require_once(APPPATH . 'Libraries/ebs/ForecastSolar.php');
		$fSolar = new \ForecastSolar($regionData['rlat'],$regionData['rlon'],$regionData['rdec'],$regionData['raz'],$regionData['rkwp']);
		*/
	}
	
	/*TRANSELELCTRICA*/
	public function saveTranselectrica($judete, $y1, $m1, $forecast1, $y2, $m2, $forecast2)
	{
		$f1Len = count($forecast1);
		$f2Len = cal_days_in_month(CAL_GREGORIAN, $m2, $y2);//count($forecast2);
		
		$sqlData = '';
		
		$d2Start = date('j')+1;
		if(empty($forecast1))
			$d2Start = 0;
			
		//log_message("debug",print_r($forecast2,true));
		for($d = $d2Start;$d<=$f2Len;$d++)
		{
			
			$day = str_pad($d+1, 2, '0', STR_PAD_LEFT);
			if(!isset($forecast2[$day])) continue;
			
			for($j=0;$j<43;$j++)
			{
				$jud = $judete[$j];
				if($jud == 'senGraph') $jud = 'RO';
				
				for($h=0;$h<24;$h++)
					if(isset($forecast2[$day]))
						$sqlData .= "('$y2-$m2-$day $h:00:00','{$jud}','d2',{$forecast2[$day][$j][$h]}),";
			}
			log_message("debug","Imported d2: $y2-$m2-$day");
		}
		
		for($d = 0;$d<$f1Len;$d++)
		{
			$day = str_pad($d+1, 2, '0', STR_PAD_LEFT);
			if(isset($forecast1[$day]))
			{
				$source = "d1";
				$forecast = $forecast1[$day];
			}
			elseif(isset($forecast2[$day]))
			{
				log_message("debug","Missing d1, replace with d2: $y2-$m2-$day");
				$source = "d2";
				$forecast = $forecast2[$day];
				$f1Len++;
			}
			else
			{
				log_message("debug","Missing d1 and d2: $y2-$m2-$day");
				$f1Len++;
				if($f1Len>31)
					return "Lipsa date, eroare import valori incarcate de la Transelectrica";
				continue;
			}
			
			for($j=0;$j<43;$j++)
			{
				$jud = $judete[$j];
				if($jud == 'senGraph') $jud = 'RO';
				
				for($h=0;$h<24;$h++)
				{
					$sqlData .= "('$y1-$m1-$day $h:00:00','{$jud}','$source',{$forecast[$j][$h]}),";
				}
			}
			log_message("debug","Imported $source: $y1-$m1-$day");
		}
				
		$sqlData = rtrim($sqlData, ",");
	
	
	
		//cleanup d2
		//$this->writeData("DELETE FROM procast_transelectrica WHERE forecast_type = 'd2' AND DATE(forecast_datetime)<date(DATE_ADD(NOW(), INTERVAL 2 DAY))");
		//$this->writeData("DELETE FROM procast_transelectrica WHERE forecast_type = 'd2'");
		$imported = 0;
		if($sqlData)
		{
			$sql = "INSERT INTO procast_transelectrica (forecast_datetime,county_code,forecast_type,forecast_ea) VALUES $sqlData ON DUPLICATE KEY UPDATE forecast_type = values(forecast_type)";
			
			$imported = $this->writeData($sql);
		}
		//cleanup
		//	$this->writeData("DELETE FROM procast_transelectrica WHERE forecast_type = 'd2' AND DATE(forecast_datetime)<date(DATE_ADD(NOW(), INTERVAL 2 DAY))");
		
		return "$imported valori incarcate de la Transelectrica";
	}
	
	public function viewTranselectricaData($year,$month,$countyCode)
	{
		$sql = "SELECT forecast_datetime, forecast_ea, county_code FROM procast_transelectrica  WHERE county_code = '$countyCode' AND YEAR(forecast_datetime)=$year AND MONTH(forecast_datetime) = $month ORDER BY HOUR(forecast_datetime), DAY(forecast_datetime)";
		
		return $this->getTArray($sql);
	}
	
	public function uploadManualTranselectrica($year,$month,$countyCode,$data)
	{
		$sqlValues = '';
		$deleteSqlValues = '';
		for($h=0;$h<=23;$h++)
			for($d=0;$d<count($data[$h]);$d++)
			{	
				$day=$d+1;
				if($data[$h][$d] == "")
				{
					$deleteSqlValues .= "(forecast_datetime = '$year-$month-$day $h:00:00' AND county_code='$countyCode') OR ";
					continue;
				}
				
				$ea = $data[$h][$d];
				$sqlValues .= "('$year-$month-$day $h:00:00', '$countyCode','m', $ea),";
			}
			
		if(!empty($sqlValues))
		{
			$sqlValues = rtrim($sqlValues, ",");
			$sql = "INSERT INTO procast_transelectrica (forecast_datetime, county_code, forecast_type, forecast_ea) VALUES $sqlValues ON DUPLICATE KEY UPDATE forecast_ea = VALUES(forecast_ea), forecast_type = VALUES(forecast_type)";
			$this->writeData($sql)." valori actualizate";
		}
		
		if(!empty($deleteSqlValues))
		{
			$deleteSqlValues = rtrim($deleteSqlValues," OR ");
			$sql = "DELETE FROM procast_transelectrica WHERE $deleteSqlValues";
			$this->writeData($sql);
		}
		
		return "Datele au fost incarcate";
	}
	
	public function uploadPISEN($year,$data)
	{
		$sqlValues = '';
		$deleteSqlValues = '';
		for($m=1;$m<=12;$m++)
			for($j=0;$j<43;$j++)
			{
				$judet = coduriJudete[$j];
				
				if($data[$j][$m-1] == "")
				{
					$deleteSqlValues .= "(county_code = '$judet' AND pisen_date = '$year-$m-1') OR ";
					continue;
				}
				
				$ea = $data[$j][$m-1];
				$sqlValues .= "('$judet','$year-$m-1',$ea),";
			}
		
		$sqlValues = rtrim($sqlValues, ",");
		$sql = "INSERT INTO procast_transelectrica_pisen (county_code, pisen_date, pisen_ea) VALUES $sqlValues ON DUPLICATE KEY UPDATE pisen_ea = VALUES(pisen_ea)";
		$this->writeData($sql)." valori actualizate";
		
		if(!empty($sqlValues))
		{
			$deleteSqlValues = rtrim($deleteSqlValues," OR ");
			$sql = "DELETE FROM procast_transelectrica_pisen WHERE $deleteSqlValues";
			$this->writeData($sql);
		}
		
		return "Datele au fost incarcate";
	}
	
	public function isProductionDuplicate($regionID, $date, $ea)
	{
		$sql = "select ppp_id as value from procast_prod_forecast where supplier_id = {$this->supplierID} AND region_id = $regionID AND prod_datetime = '$date' AND ea = $ea";
		
		return !empty($this->getValue($sql));
	}
	
	public function importProductionSQLValues($data)
	{
		$sql = "CREATE TEMPORARY TABLE temp_prod LIKE procast_prod_forecast";
		$this->writeData($sql);
		
		$sql = "insert into temp_prod (supplier_id, region_id, prod_datetime, ea) VALUES $data ON DUPLICATE KEY UPDATE ea=VALUES(ea)";
		$this->writeData($sql);
		
		/*ignore duplicates.
		$sql = "select count(*) as value from procast_prod_forecast ppf join 
				(select * from temp_prod group by date(prod_datetime), hour(prod_datetime)) T 
				ON ppf.supplier_id = T.supplier_id AND ppf.region_id = T.region_id AND ppf.prod_datetime = T.prod_datetime AND ppf.ea = T.ea";
		
		$duplicate = $this->getValue($sql);
		if($duplicate > 0) return "$duplicate valori duplicate!";
		*/
		
		$sql = "insert into procast_prod_forecast (supplier_id, region_id, prod_datetime, ea) select supplier_id, region_id, prod_datetime, avg(ea) from temp_prod group by date(prod_datetime), hour(prod_datetime)  ON DUPLICATE KEY UPDATE ea=VALUES(ea)";
		$adaugate = $this->writeData($sql);
	
		return "$adaugate valori adaugate!";
	}
	
}