<?php

namespace App\Models\Realizat;

use CodeIgniter\Model;
use CodeIgniter\ConnectionInterface;

require_once(__DIR__.'/../CacheTools.php');
require_once(__DIR__.'/../MasterDataTools.php');

class ImportActualModel extends Model
{
	use \MasterDataTools;
	use \CacheTools;
	protected $table      = 'actual_data';
	protected $db;
	
	private $checkData = [];
	
	function __construct()
	{
		$this->db = db_connect();
		
		//preload pods
		if(!isset($this->checkData['pods'])) 
			$this->checkData['pods'] = $this->loadPods();
		
		$this->createDictionaryFromQuery("SELECT CONCAT('profiles_',d.distributor_name) AS profileDictionary, replace(trim(acpm.readings_profile_name),'_','') AS keyField, replace(trim(acpm.consumption_profile_name),'_','')  as valueField 
											FROM actual_curve_profile_mapping acpm 
											JOIN distributors d ON acpm.distributor_id = d.distributor_id where acpm.supplier_id = ".$_SESSION['select-supplier'],
											'profileDictionary','keyField','valueField');
	}
	
	function async_import_actual_data($sqlValues)
	{

		$sql = 'INSERT IGNORE INTO actual_data (supplier_id, distributor_name, reading_datetime, curve_name, curve_type, actual_ea, customer_name, customer_code,customer_id,curve_id, error) VALUES ' . $sqlValues;
		$sqlQueue = $this->getSqlQueue();
		$id = $sqlQueue->sendItem([$sql]);
		
		return $id;
	}
	
	function import_actual_data($sqlValues)
	{

		$this->db->query('INSERT IGNORE INTO actual_data (supplier_id, distributor_name, reading_datetime, curve_name, curve_type, actual_ea, customer_name, customer_code,customer_id,curve_id, error) VALUES ' . $sqlValues );
		
		return $this->db->affectedRows();
	}
		
	function checkEnelCurveData($distributor_name,$curve_name,$reading_datetime)		
	{
		//to do: check supplier id!
		$cn = $this->getFromDictionary('profiles_'.$distributor_name,[$curve_name]);
		if(!empty($cn)) 
			$curve_name = $cn;
		
		$result = ['error' => 'Adauga Client', 'customer_name' => 'Nespecificat','customer_id'=>NULL,'curve_id'=>0,'curve_type'=>'sintetica'];
		$dt = explode(' ',$reading_datetime);
		$ymd = explode('-',$dt[0]);
		$curveData = $this->getFromDictionary($distributor_name,[$curve_name]);
		
		if(empty($curveData))
		{
			$sql = 'SELECT c.customer_name,c.customer_id,p.pod_no,acv.curve_id,consumption_date FROM actual_curves_variance acv 
						JOIN actual_curves ac ON acv.curve_id = ac.curve_id 
						LEFT JOIN pods p ON acv.pod = p.pod_no
						LEFT JOIN customers c ON p.customer_id = c.customer_id
						JOIN distributors d ON ac.distributor_id = d.distributor_id
						WHERE d.distributor_name = '.$this->db->escape($distributor_name).' and ac.curve_name = '.$this->db->escape($curve_name).' AND year(consumption_date) = '.$this->db->escape($ymd[0]).' AND month(consumption_date) = '.$this->db->escape($ymd[1]).' order by customer_name';
			
			//log_message('error',$sql);
			$query = $this->db->query($sql);
			$qresult = $query->getResultArray();
			
			$numRows = $query->getNumRows();
			if($numRows == 1)
			{
				if(!empty($qresult[0]['customer_name']))
				{
					$result['customer_name'] = $qresult[0]['customer_name'];
					$result['customer_id'] = $qresult[0]['customer_id'];
					$result['curve_id'] = $qresult[0]['curve_id'];
					$result['error'] = '';
				}
				
					
				$result['curve_type'] = 'masurata';

				$this->addToDictionary($distributor_name,[$curve_name],$result);
			
			}
			elseif($numRows > 1)
			{
				$missingCustomers = 0;
				$PODsNo = 0;
				$customersNo = 0;
				$pCustomer='#';
				foreach($qresult as $r)
				{
					$PODsNo++;
					if(empty($r['customer_name'])) $missingCustomers++;
					if($pCustomer!=$r['customer_name']) 
					{
						$pCustomer=$r['customer_name'];
						$customersNo++;
					}
				}
			
				$result['curve_id'] = $qresult[0]['curve_id'];
			
			if($customersNo == 1 && $missingCustomers==0) {$result['customer_name'] = $qresult[0]['customer_name'] . ' / '. $PODsNo.' PODuri';$result['error'] = '';$result['customer_id'] = $qresult[0]['customer_id'];}
			elseif($customersNo > 1 && $missingCustomers==0) {$result['customer_name'] = $customersNo. ' clienti /'. $PODsNo.' PODuri';$result['error'] = '';}
			elseif($customersNo >= 1 && $missingCustomers>0) {$result['customer_name'] = $customersNo. ' clienti? /'. $PODsNo.' PODuri';$result['error'] = 'Adauga '.$missingCustomers.'? clienti';}
					
								
				$this->addToDictionary($distributor_name,[$curve_name],$result);
			}
			else
			{
				$result['curve_id'] = 0;
				$result['customer_name'] = 'Nespecificat';
				$result['error'] = 'Curba Necunoscuta. Lipsesc consumuri?';
				
				$this->addToDictionary($distributor_name,[$curve_name],$result);
			}
		}
		else
		{
			$result = $curveData;
		}
		
		/*$enelData = $this->getEnelCurveData($distributor_name,$curve_name,$dt[0]);
		$curve_type = $enelData['curve_type'];
		$customer_name = $enelData['customer_name'];
		$customer_code = $enelData['customer_code'];*/
		return $result;
	}
	
	function checkCurveData($distributor_name,$curve_name,$reading_datetime, $curve_type, $customer_name, $customer_code)
	{
		//to do: check supplier id!
		$cn = $this->getFromDictionary('profiles_'.$distributor_name,[$curve_name]);
		if(!empty($cn)) 
			$curve_name = $cn;
		
		$result = ['error' => 'Adauga Client', 'customer_name' => $customer_name,'customer_id'=>NULL,'curve_id'=>0];
		$dt = explode('-',$reading_datetime);
		
		if(in_array($distributor_name, ['DELGAZ GRID S.A.','DEER Transilvania Sud'])) $result['error'] = 'Adauga POD'; //cod client = POD pt Moldova si curba = pod pt TS
							
		if($curve_type == 'sintetica')
		{
			if ( (in_array($distributor_name,['DEER Transilvania Nord','DEER Transilvania Sud','DELGAZ GRID S.A.','DISTRIBUTIE ENERGIE OLTENIA S.A.']) && $curve_type == 'sintetica') || str_contains($distributor_name,'E-DISTRIBUTIE') )
					$result['customer_name'] = 'Nespecificat';
			
			if($curve_type == 'sintetica')
			{
				
				if($result['customer_name'] == 'Nespecificat')
					$r = $this->getFromDictionary('sintetica',[$distributor_name,$curve_name]);
				else
					$r = $this->getFromDictionary('sintetica',[$distributor_name,$customer_code,$dt[0],$dt[1]]);
				
				if(!empty($r)) $qresult = $r;
				else{
					
					log_message('error',implode('_',[$distributor_name,$curve_name]));
					
					if($result['customer_name'] == 'Nespecificat')
						$query = $this->db->query('SELECT \'Nespecificat\' as customer_name, ac.curve_id, NULL as customer_id FROM actual_curves ac  
												JOIN distributors d on ac.distributor_id = d.distributor_id
												where d.distributor_name='.$this->db->escape($distributor_name).'
												AND ac.curve_name = '.$this->db->escape($curve_name).' LIMIT 1');					
					else
						$query = $this->db->query('SELECT cu.customer_name,ac.curve_id, cu.customer_id FROM consumptions c  
												left JOIN pods p ON c.pod = p.pod_no
												LEFT JOIN customers cu ON p.customer_id = cu.customer_id
												left JOIN actual_curves ac on ac.curve_type=\'sintetica\'
												LEFT JOIN actual_curves_variance acv ON acv.pod = p.pod_no
												WHERE c.customer_code='.$this->db->escape($customer_code).' AND c.distributor_name='.$this->db->escape($distributor_name).
												' and year(acv.consumption_date) ='.$dt[0].' AND month(acv.consumption_date) ='.$dt[1].'
												LIMIT 1');

					$qresult = $query->getRowArray();
					
					if($result['customer_name'] == 'Nespecificat')
					{
						if(empty($qresult))
						{	$result['error'] = 'Adauga consumuri';						
							$this->addToDictionary('sintetica',[$distributor_name,$curve_name],$result);
						}
						else
							$this->addToDictionary('sintetica',[$distributor_name,$curve_name],$qresult);
					}
					else
					{
						if(empty($qresult)) 
							$this->addToDictionary('sintetica',[$distributor_name,$customer_code,$dt[0],$dt[1]],$result);
						else
							$this->addToDictionary('sintetica',[$distributor_name,$customer_code,$dt[0],$dt[1]],$qresult);
					}
					
					log_message('error',print_r($result,true));
				}
			}
			
		}
		elseif($curve_type == 'masurata')
		{
			$r = $this->getFromDictionary('masurata',[$distributor_name,$curve_name,$dt[0],$dt[1]]);
			
			if(!empty($r)) $qresult = $r;
			else{
				$sql = 'SELECT coalesce(cu.customer_name,\''.$customer_name.'\') as customer_name,c.curve_id,cu.customer_id FROM actual_curves_variance acv 
												LEFT JOIN actual_curves c ON acv.curve_id = c.curve_id
												LEFT JOIN distributors d ON c.distributor_id = c.distributor_id
												LEFT JOIN pods p ON acv.pod = p.pod_no
												LEFT JOIN customers cu ON p.customer_id = cu.customer_id
												WHERE 
												d.distributor_name = '.$this->db->escape($distributor_name).' AND
												c.curve_name ='.$this->db->escape(strval($curve_name)).' AND
												year(acv.consumption_date) = '.$dt[0].' AND MONTH(acv.consumption_date) ='.$dt[1].'
												LIMIT 1';
				$query = $this->db->query($sql);

				$qresult = $query->getRowArray();
				
				if(empty($qresult))
				{
					log_message('error',$sql);
					$result['error'] = 'Adauga consumuri';						
					$this->addToDictionary('masurata',[$distributor_name,$curve_name,$dt[0],$dt[1]],$result);
				}
				else
					$this->addToDictionary('masurata',[$distributor_name,$curve_name,$dt[0],$dt[1]],$qresult);
			}
		}
		
		if(!empty($qresult))
		{
			$result = $qresult;
			
			if ((in_array($distributor_name,['DEER Transilvania Nord','DEER Transilvania Sud','DELGAZ GRID S.A.','DISTRIBUTIE ENERGIE OLTENIA S.A.']) && $curve_type == 'sintetica') || str_contains($distributor_name,'E-DISTRIBUTIE'))
			{
					$result['customer_name'] = 'Nespecificat';
					if(!isset($result['error'])) $result['error'] = '';
			}			
			else
			{
				
				if(empty($qresult['customer_id']))
				{
					if($distributor_name == 'DISTRIBUTIE ENERGIE OLTENIA S.A.')
						$result['error'] = 'Adauga Client';
					else
						$result['error'] = 'Adauga POD';
				}
				elseif (!isset($qresult['error'])) 
					$result['error'] = '';
			}
		}
			
		return $result;
	}
	
	function cleanZeroConsumption()
	{
		$query = $this->db->query("DELETE ad FROM actual_data ad
				JOIN 
				(
				SELECT SUM(ad.actual_ea) AS ea,ad.distributor_name, YEAR(reading_datetime) AS year,MONTH(reading_datetime) AS month ,curve_name FROM actual_data ad
				GROUP BY ad.distributor_name, YEAR(reading_datetime),MONTH(reading_datetime),curve_name
				HAVING SUM(ad.actual_ea) = 0 ) A
				ON ad.distributor_name = A.distributor_name AND ad.curve_name = A.curve_name AND YEAR(ad.reading_datetime) = A.year AND MONTH(ad.reading_datetime) = A.month");
	}
	
	function saveReadings(){

		if(!empty($_SESSION['actual-data-date']))
		{
			$ymd = $_SESSION['actual-data-date'].'-1';
		}		
		else
			$ymd=date("m-Y", strtotime("-1 months")).'-1';
		
		log_message('error',$ymd);
		$date = \DateTime::createFromFormat('m-Y-j',$ymd)->getTimestamp();
		
		$tableName = "actual_readings_".\DateTime::createFromFormat('m-Y-j',$ymd)->format('n');
		
		$this->db->query("insert ignore into $tableName (supplier_id, reading_datetime,customer_id,curve_id,actual_ea) select ".$_SESSION['select-supplier'].", reading_datetime,customer_id,curve_id,sum(actual_ea) from actual_data where error = '' and curve_id is not null group by supplier_id, reading_datetime, curve_id");
		
		$affected = $this->db->affectedRows();
				
		$this->db->query("delete from actual_data where supplier_id= ".$_SESSION['select-supplier']." and error = '' and curve_id is not null");		

		
		$this->exportReadingsToForecast($date,[], 0, []);
		
		
		$query = $this->db->query("select count(distinct data_id) as msg from actual_view_data where error <> ''");
		
		$qresult = $query->getRowArray();
		
		if($qresult['msg'] == 0) 
			$result['type'] = 'info';
		else 
			$result['type'] = 'error';
		
		$result['msg'] = $affected . ' citiri adaugate, ' . $qresult['msg'] . ' erori';
		
		return $result;
	}
	
	function removeReadings($DistributorName,$CurveName)
	{
		$this->writeData("DELETE FROM actual_data WHERE supplier_id = ".$_SESSION['select-supplier']." AND distributor_name = ".$this->db->escape($DistributorName)." AND curve_name = ".$this->db->escape($CurveName));
	}

	function updateReadings($DistributorName,$CurveName,$year,$month, $newDistributorName)
	{
		$sql = 'SELECT c.customer_name,c.customer_id,p.pod_no,acv.curve_id,consumption_date FROM actual_curves_variance acv 
				JOIN actual_curves ac ON acv.curve_id = ac.curve_id 
				LEFT JOIN pods p ON acv.pod = p.pod_no
				LEFT JOIN customers c ON p.customer_id = c.customer_id
				JOIN distributors d ON ac.distributor_id = d.distributor_id
				WHERE d.distributor_name = '.$this->db->escape($newDistributorName).' and ac.curve_name = '.$this->db->escape($CurveName).' AND year(consumption_date) = '.$this->db->escape($year).' AND month(consumption_date) = '.$this->db->escape($month).' order by customer_name';
		
		$query = $this->db->query($sql);
		$qresult = $query->getResultArray();	
		$numRows = $query->getNumRows();
		
		$result=[];
		$result['customer_id'] = null;
		if($numRows == 1)
		{
			if(!empty($qresult[0]['customer_name']))
			{
				$result['customer_name'] = $qresult[0]['customer_name'];
				$result['customer_id'] = $qresult[0]['customer_id'];
				$result['curve_id'] = $qresult[0]['curve_id'];
				$result['error'] = '';
			}
			
				
			$result['curve_type'] = 'masurata';			
		}
		elseif($numRows > 1)
		{
			$missingCustomers = 0;
			$PODsNo = 0;
			$customersNo = 0;
			$pCustomer='#';
			foreach($qresult as $r)
			{
				$PODsNo++;
				if(empty($r['customer_name'])) $missingCustomers++;
				if($pCustomer!=$r['customer_name']) 
				{
					$pCustomer=$r['customer_name'];
					$customersNo++;
				}
			}
		
			$result['curve_id'] = $qresult[0]['curve_id'];
		
		if($customersNo == 1 && $missingCustomers==0) {$result['customer_name'] = $qresult[0]['customer_name'] . ' / '. $PODsNo.' PODuri';$result['error'] = '';$result['customer_id'] = $qresult[0]['customer_id'];}
		elseif($customersNo > 1 && $missingCustomers==0) {$result['customer_name'] = $customersNo. ' clienti /'. $PODsNo.' PODuri';$result['error'] = '';}
		elseif($customersNo >= 1 && $missingCustomers>0) {$result['customer_name'] = $customersNo. ' clienti? /'. $PODsNo.' PODuri';$result['error'] = 'Adauga '.$missingCustomers.'? clienti';}
		}
		else
		{
			$result['curve_id'] = 0;
			$result['customer_name'] = 'Nespecificat';
			$result['error'] = 'Curba Necunoscuta. Lipsesc consumuri?';

		}
	
		$this->writeData("UPDATE actual_data SET 
		distributor_name = ".$this->db->escape($newDistributorName).",
		curve_id = ".$this->db->escape($result['curve_id']).",
		customer_name = ".$this->db->escape($result['customer_name']).",
		error = ".$this->db->escape($result['error'])."
		WHERE supplier_id = ".$_SESSION['select-supplier']." AND distributor_name = ".$this->db->escape($DistributorName)." AND curve_name = ".$this->db->escape($CurveName));
	}
	
	function get_actualreading_date_min_max_years()
	{
		$query = $this->db->query("
			SELECT MIN(minY) AS minY, MAX(maxY) AS maxY FROM (
			select date_format(min(reading_datetime),'%Y') AS minY,date_format(max(reading_datetime),'%Y') as maxY FROM actual_readings_1 UNION
			select date_format(min(reading_datetime),'%Y') AS minY,date_format(max(reading_datetime),'%Y') as maxY FROM actual_readings_2 UNION
			select date_format(min(reading_datetime),'%Y') AS minY,date_format(max(reading_datetime),'%Y') as maxY FROM actual_readings_3 UNION
			select date_format(min(reading_datetime),'%Y') AS minY,date_format(max(reading_datetime),'%Y') as maxY FROM actual_readings_4 UNION
			select date_format(min(reading_datetime),'%Y') AS minY,date_format(max(reading_datetime),'%Y') as maxY FROM actual_readings_5 UNION
			select date_format(min(reading_datetime),'%Y') AS minY,date_format(max(reading_datetime),'%Y') as maxY FROM actual_readings_6 UNION
			select date_format(min(reading_datetime),'%Y') AS minY,date_format(max(reading_datetime),'%Y') as maxY FROM actual_readings_7 UNION
			select date_format(min(reading_datetime),'%Y') AS minY,date_format(max(reading_datetime),'%Y') as maxY FROM actual_readings_8 UNION
			select date_format(min(reading_datetime),'%Y') AS minY,date_format(max(reading_datetime),'%Y') as maxY FROM actual_readings_9 UNION
			select date_format(min(reading_datetime),'%Y') AS minY,date_format(max(reading_datetime),'%Y') as maxY FROM actual_readings_10 UNION
			select date_format(min(reading_datetime),'%Y') AS minY,date_format(max(reading_datetime),'%Y') as maxY FROM actual_readings_11 UNION
			select date_format(min(reading_datetime),'%Y') AS minY,date_format(max(reading_datetime),'%Y') as maxY FROM actual_readings_12) A
		");
		$result = $query->getRowArray();
		if(!$result) return ['minY'=>2020, 'maxY'=>2020];

		return $result;
	}
	
	function get_actualconsumption_date_min_max_years()
	{
		$query = $this->db->query("select coalesce(date_format(min(consumption_date),'%Y'), year(now())) AS minY, coalesce(date_format(max(consumption_date),'%Y'), year(now())) as maxY FROM actual_curves_variance ");
		$result = $query->getRowArray();
		if(!$result) return ['minY'=>2020, 'maxY'=>2020];

		return $result;
	}
	
	function getcustomersNames($customersIDs,$distributorID)
	{
		$wStr='';
		$jStr='';
		
		if(count($customersIDs)>0 && !empty($customersIDs[0]))
			$wStr = ' where c.customer_id in ('.implode(',',$customersIDs).')';
		else return ['Toti Clientii'];
			
		if($distributorID > 0)
		{
			$jStr = ' join pods p on p.customer_id = c.customer_id and p.distributor_id = '.$distributorID.' ';
		}
		
		$query = $this->db->query("select c.customer_name from customers c $jStr $wStr group by c.customer_id");
		$result = $query->getResultArray();
		
		$customerNames = [];
		foreach($result as $r)
			array_push($customerNames, $r['customer_name']);	
		
		return $customerNames;
	}
	
	function isDuplicated($distributor_name,$curve_name,$reading_datetime)
	{
		
		$rd = explode('-',$reading_datetime);
		$tableName = 'actual_readings_'.(int)$rd[1];
		
		if(!empty($this->getFromDictionary($distributor_name.'_exists',[$curve_name,$rd[0],$rd[1]]))) return true;
		if(!empty($this->getFromDictionary($distributor_name.'_new',[$curve_name,$rd[0],$rd[1]]))) return false;
		
		$sql = "(SELECT ar.reading_id from $tableName ar join actual_curves ac on ar.curve_id = ac.curve_id join distributors d on ac.distributor_id = d.distributor_id where 
				d.distributor_name = ".$this->db->escape($distributor_name)." and ac.curve_name = ".$this->db->escape($curve_name)." and year(ar.reading_datetime) = ".$rd[0]." and month(ar.reading_datetime) = ".$rd[1]. " limit 1) union
				(SELECT ad.data_id FROM actual_data ad WHERE ad.distributor_name = ".$this->db->escape($distributor_name)." and ad.curve_name = ".$this->db->escape($curve_name)." and year(ad.reading_datetime) = ".$rd[0]." and month(ad.reading_datetime) = ".$rd[1]." limit 1)";
				
		$query = $this->db->query($sql);
		$result = $query->getResultArray();
		
		if(empty($result))
		{
			$this->addToDictionary($distributor_name.'_new',[$curve_name,$rd[0],$rd[1]],true);
			return false;
		}
		else
		{
			$this->addToDictionary($distributor_name.'_exists',[$curve_name,$rd[0],$rd[1]],true);
			return true;
		}
	}
	
	function getCurvesByDistributor($distributorID,$date, $inclPODs, $inclCustomers)
	{
		$ym = explode('-',$date);
		$year = $ym[0];
		$month = $ym[1];
		$wStr = '';
		
		$tableName = 'actual_readings_'.(int)$month;
		
		if(!empty($includedPODs))
			$includedPODs = str_replace(",","','",$includedPODs);
		else if(!empty($inclCustomers))
			$inclPODs = implode("','",$this->getPODsByDistributorCustomer($distributorID,$inclCustomers,$date, '', true));
		
		if(!empty($inclPODs))
		{
			$wStr = " JOIN actual_curves_variance acv ON ac.curve_id = acv.curve_id AND year(acv.consumption_date) = YEAR(ar.reading_datetime) AND MONTH(acv.consumption_date) = MONTH(ar.reading_datetime) AND acv.POD IN ('$inclPODs')";
			$wStr .= " JOIN pods p ON acv.pod = p.pod_no  AND p.customer_id IN ($inclCustomers)";
		}
		
		if(is_numeric($distributorID) && $distributorID >0)
			$sql = "SELECT distinct ac.curve_name, ac.curve_id FROM actual_curves ac
									    JOIN $tableName ar ON ac.curve_id = ar.curve_id
										$wStr
										WHERE ac.distributor_id=$distributorID AND ar.reading_datetime='$year-$month-01 00:00:00' order by ac.curve_name";
		else
			$sql = "SELECT distinct ac.curve_name, ac.curve_id FROM actual_curves ac
									    JOIN $tableName ar ON ac.curve_id = ar.curve_id
										$wStr
										WHERE ar.reading_datetime='$year-$month-01 00:00:00' order by curve_name";
		
		log_message('error',$sql);
		$query = $this->db->query($sql);
		$result['data'] = $query->getResultArray();
		$result['total'] = 0;//$query->countAllResults();
				
		return $result;
	}
	
	function getCustomerByDistributor($distributorID,$date)
	{
		$ym    = explode('-',$date);
		$year  = intval($ym[0]);
		$month = intval($ym[1]);
		$tbl   = 'actual_readings_'.$month;   // partiție lunară (1..12), aceeași ca raportul afișat

		// Sursa = curba orară AFIȘATĂ în acest raport (actual_readings), NU consumptions.
		// Astfel dropdown-ul de clienți reflectă exact datele din grilă. Filtrul pe
		// distribuitor se face prin curve -> actual_curves.distributor_id (ca în raport).
		if(is_numeric($distributorID) && $distributorID > 0)
			$query = $this->db->query("SELECT c.customer_id, c.customer_name FROM customers c
										JOIN (SELECT DISTINCT ar.customer_id
										        FROM $tbl ar
										        JOIN actual_curves ac ON ac.curve_id = ar.curve_id
										       WHERE ar.year = $year AND ac.distributor_id = $distributorID) t
										  ON t.customer_id = c.customer_id
										ORDER BY c.customer_name");
		else
			$query = $this->db->query("SELECT c.customer_id, c.customer_name FROM customers c
										JOIN (SELECT DISTINCT customer_id
										        FROM $tbl WHERE year = $year) t
										  ON t.customer_id = c.customer_id
										ORDER BY c.customer_name");

		$result['data'] = $query->getResultArray();
		$result['total'] = 0;//$query->countAllResults();

		return $result;
	}
	
	function getPODsByDistributorCustomer($distributorID,$customersIDs,$date,$includedCurves,$flat=false)
	{
		$result = [];
		$wStr = '';
		
		if(is_numeric($distributorID) && $distributorID >0)
			$wStr.= " ac.distributor_id = $distributorID and ";	
			//$wStr.= " p.distributor_id = $distributorID and ";
			
		
		if(!empty($customersIDs))
		{

			/*$sql = "SELECT DISTINCT(p.pod_no) FROM customers c 
											JOIN pods p ON c.customer_id = p.customer_id
											WHERE $wStr c.customer_id in ($customersIDs)
											ORDER BY c.customer_name");*/

			$ym = explode('-',$date);
			$year = $ym[0];
			$month = $ym[1];
			
			$tableName = 'actual_readings_'.(int)$month;
			
			if(!empty($includedCurves))
			{
				$wStr .= " ar.curve_id IN ($includedCurves) AND ";
			}
			
			$sql = "SELECT DISTINCT pod AS pod_no
			FROM actual_curves_variance acv
			JOIN pods p on acv.pod = p.pod_no
			WHERE acv.supplier_id = ".$_SESSION['select-supplier']." 
			AND curve_id IN (SELECT DISTINCT ar.curve_id FROM $tableName ar JOIN actual_curves ac ON ar.curve_id = ac.curve_id WHERE $wStr YEAR(ar.reading_datetime)=$year AND MONTH(ar.reading_datetime)=$month)
			AND year(acv.consumption_date) = $year and month(acv.consumption_date) = $month
			and p.customer_id in ($customersIDs)";		
			
			log_message("error",$sql);
			$query = $this->db->query($sql);
			
			if($flat)
			{
				foreach($query->getResultArray() as $r)
					array_push($result,$r['pod_no']);
				
				return $result;
				
			}
			
			$result['data'] = $query->getResultArray();
			$result['total'] = 0;//$query->countAllResults();
		}
		else
		{
			$result['data'] = [];
			$result['total'] = 0;
		}
		return $result;
	}
	
	
	function getRaportOrarEnel($date,$customersIDs,$distributorID,$includedPODs,$includedCurves, $qmin)
	{
		
		$tableName = 'actual_readings_'.date('n', $date);
		$wStr='where';
		

		if($distributorID > 0)
			$wStr .= ' d.distributor_id = '.$distributorID.' and ';
		
		$wStr2 = '';
		
		if(count($includedPODs)>0 && !empty($includedPODs[0]))
			$wStr2 = "('".implode("','",$includedPODs)."')";
			
		
		$wStr .= ' year(reading_datetime) = '.date('Y', $date).' and month(reading_datetime) = '.date('n', $date);
		
		if(count($customersIDs)>0 && !empty($customersIDs[0]))
			$wStr .= ' AND ac.curve_id IN (SELECT distinct(curve_id) FROM actual_curves_variance acvv JOIN pods ON acvv.pod = pods.pod_no WHERE pods.customer_id IN ('.implode(',',$customersIDs).'))';
		
		if(count($includedCurves)>0 && !empty($includedCurves[0]))
			$wStr .= " AND ac.curve_id IN ('".implode("','",$includedCurves)."')";
		
		if($qmin)
		{
			$gStr1 = ' ( 4 * HOUR( A.reading_datetime ) + FLOOR( MINUTE( A.reading_datetime ) / 15 )) ';
			$gStr2 = ' ( 4 * HOUR( X.reading_datetime ) + FLOOR( MINUTE( X.reading_datetime ) / 15 )) ';
			$oMin = 'minute(X.reading_datetime),';
			$dateTime = 'X.reading_datetime';
		}
		else
		{
			$gStr1 = ' hour(A.reading_datetime) ';
			$gStr2 = ' hour(X.reading_datetime) ';
			$oMin = '';
			$dateTime = "date_format(X.reading_datetime,'%Y-%m-%d %H:00:00')";
		}
		
				$qStr = "SELECT X.reading_id, X.distributor_id, X.distributor_name, X.supplier_id, $dateTime as reading_datetime, X.customer_id, X.curve_id, GROUP_CONCAT(pod SEPARATOR ', ') AS PODs, SUM(X.ea) AS ea FROM (
				
				SELECT A.*,p.customer_id, SUM(cast(A.actual_ea as decimal(20,8)))*A.PODCoef as ea FROM
                (
                        SELECT d.distributor_id, d.distributor_name AS distributor_name,ar.reading_id AS reading_id,ar.supplier_id AS supplier_id,
                        ar.reading_datetime AS reading_datetime, ar.curve_id AS curve_id,
                        ar.actual_ea,C.pod, C.PODCoef
                        from $tableName ar
                        join actual_curves ac on ar.curve_id = ac.curve_id
                        join distributors d on ac.distributor_id = d.distributor_id
                        JOIN (
									SELECT acv.pod, acv.curve_id, acv.consumption_ea as PODea, CC.Cea, cast(consumption_ea/CC.Cea as decimal(20,8)) AS PODCoef, acv.consumption_date
									FROM actual_curves_variance acv #consum per POD / CURBA
									JOIN (
											#consum total per CURBA
											SELECT c.curve_id, c.consumption_date, SUM(CAST(c.consumption_ea as DECIMAL(20,3))) AS Cea
											FROM actual_curves_variance c where year(c.consumption_date)=".date('Y', $date)." AND MONTH(c.consumption_date)=".date('n', $date)."
											group BY c.curve_id, c.consumption_date

										) CC ON CC.curve_id = acv.curve_id AND CC.consumption_date = acv.consumption_date
									WHERE acv.pod IN $wStr2 and year(acv.consumption_date)=".date('Y', $date)." AND MONTH(acv.consumption_date)=".date('n', $date)."	
								 ) C on C.curve_id = ac.curve_id
                        $wStr 
                ) A
			      JOIN pods p ON A.pod = p.pod_no
                  group BY A.curve_id, A.pod, cast(A.reading_datetime as date), $gStr1
				  
                ) X group BY cast(X.reading_datetime as date), $gStr2
				
				order by hour(X.reading_datetime),  $oMin  cast(X.reading_datetime as DATE)";
		
		log_message('error', $qStr);
		
		$query = $this->db->query($qStr);
		$result = $query->getResultArray();
		
		return $result;
	}
	
	function getRaportOrar($date,$customersIDs,$distributorID,$includedPODs,$includedCurves, $qmin)
	{
		$tableName = 'actual_readings_'.date('n', $date);
		
		$wStr='where';
		
		if(count($customersIDs)>0 && !empty($customersIDs[0]))
			$wStr .= ' customer_id in ('.implode(',',$customersIDs).') and ';
		
		if($distributorID > 0)
			$wStr .= ' d.distributor_id = '.$distributorID.' and ';
		
		if(count($includedCurves)>0 && !empty($includedCurves[0]))
			$wStr .= " ac.curve_id IN ('".implode("','",$includedCurves)."') AND";
		
		$wStr2 = '';
		
		if(count($includedPODs)>0 && !empty($includedPODs[0]))
			$wStr2 = " where A.PODs in ('".implode("','",$includedPODs)."') ";
			
		
		$monthStart = date('Y-m-01', $date);
		$wStr .= " reading_datetime >= '$monthStart' AND reading_datetime < DATE_ADD('$monthStart', INTERVAL 1 MONTH)";

		if($qmin)
		{
			$gStr = ' ( 4 * HOUR(ar.reading_datetime) + FLOOR( MINUTE(ar.reading_datetime) / 15 )) ';
			$oMin = 'minute(ar.reading_datetime),';
			$dateTime = 'ar.reading_datetime';
		}
		else
		{
			$gStr = ' hour(ar.reading_datetime) ';
			$oMin = '';
			$dateTime = "date_format(ar.reading_datetime,'%Y-%m-%d %H:00:00')";
		}

		// JOIN-urile la actual_curves și distributors sunt necesare doar când avem
		// filtru pe distributor sau pe curve_id; altfel sunt eq_ref × ~2M loops fără
		// efect asupra rezultatului. Le adăugăm condițional.
		$needsJoins = ($distributorID > 0)
			|| (count($includedCurves) > 0 && !empty($includedCurves[0]));
		$joinSql = $needsJoins
			? "JOIN actual_curves ac ON ar.curve_id = ac.curve_id
			   JOIN distributors d ON ac.distributor_id = d.distributor_id"
			: "";

		// Agregare push-down: GROUP BY direct pe rândurile sursă, fără wrap exterior.
		// Reduce filesort-ul de la ~2M rânduri (toată luna) la ~744 grupuri (24h × 31z).
		// Controllerul folosește doar `reading_datetime` și `ea` din rezultat; restul
		// coloanelor au fost dead-code în acest path (wrap-ul pe $wStr2 era unreachable).
		$qStr = "SELECT $dateTime AS reading_datetime,
		                SUM(ar.actual_ea) AS ea
		         FROM $tableName ar
		         $joinSql
		         $wStr
		         GROUP BY CAST(ar.reading_datetime AS DATE), $gStr
		         ORDER BY hour(ar.reading_datetime), $oMin CAST(ar.reading_datetime AS DATE)";
		
		return $this->getArray($qStr);
	}
	
	function exportReadingsToForecast($date,$customersIDs,$distributorID,$includedPODs)
	{
		$tableName = 'actual_readings_'.date('n', $date);
		
		$wStr='where';
		$pJOIN = '';
		
		if($distributorID > 0)
		{
			$wStr .= " d.distributor_id = $distributorID AND ";
			$pJOIN .= " AND p.distributor_id = $distributorID";
		}
		
		$wStr2 = '';

		if(count($includedPODs)>0 && !empty($includedPODs[0]))
		{
			$wStr2 = "acv.pod IN ('".implode("','",$includedPODs)."') and";
			$pJOIN .= " AND p.pod_no IN ('".implode("','",$includedPODs)."')";
		}
			
		
		$wStr .= ' year(reading_datetime) = '.date('Y', $date).' and month(reading_datetime) = '.date('n', $date);
		
		if(count($customersIDs)>0 && !empty($customersIDs[0]))
			$wStr .= ' AND ac.curve_id IN (SELECT distinct(curve_id) FROM actual_curves_variance acvv JOIN pods ON acvv.pod = pods.pod_no WHERE pods.customer_id IN ('.implode(',',$customersIDs).'))';
		else
		{
			$customersIDs = $this->getValueArray("
			SELECT distinct p.customer_id as value FROM actual_curves_variance c 
			JOIN pods p ON c.pod = p.pod_no $pJOIN
			WHERE year(c.consumption_date)=".date('Y', $date)." AND MONTH(c.consumption_date)=".date('n', $date));
		}
		
		
		$sqlQueue = $this->getSqlQueue();
		$nfIDs=[];
		
		//save to customer far
		foreach ($customersIDs as $customerID)
		{
			$this->createTableLikeSource("customer_far",$customerID);
			
			$qStr = "INSERT IGNORE INTO customer_far_$customerID (pod, far_datetime,far_ea) 
					SELECT Z.pod, Z.reading_datetime, sum(ea) as actual_ea FROM (
						SELECT A.*,p.customer_id, SUM(cast(A.actual_ea as decimal(20,8)))*A.PODCoef as ea FROM
						(
								SELECT d.distributor_id, d.distributor_name AS distributor_name,ar.reading_id AS reading_id,ar.supplier_id AS supplier_id,
								ar.reading_datetime AS reading_datetime, ar.curve_id AS curve_id,
								ar.actual_ea,C.pod, C.PODCoef
								from $tableName ar
								join actual_curves ac on ar.curve_id = ac.curve_id
								join distributors d on ac.distributor_id = d.distributor_id
								JOIN (
											SELECT acv.pod, acv.curve_id, acv.consumption_ea as PODea, CC.Cea, cast(consumption_ea/CC.Cea as decimal(20,8)) AS PODCoef, acv.consumption_date
											FROM actual_curves_variance acv #consum per POD / CURBA
											JOIN pods pp ON acv.pod = pp.pod_no AND pp.customer_id = $customerID
											JOIN (
													#consum total per CURBA
													SELECT c.curve_id, c.consumption_date, SUM(CAST(c.consumption_ea as DECIMAL(20,3))) AS Cea
													FROM actual_curves_variance c where year(c.consumption_date)=".date('Y', $date)." AND MONTH(c.consumption_date)=".date('n', $date)."
													group BY c.curve_id, c.consumption_date

												) CC ON CC.curve_id = acv.curve_id AND CC.consumption_date = acv.consumption_date
											WHERE $wStr2 year(acv.consumption_date)=".date('Y', $date)." AND MONTH(acv.consumption_date)=".date('n', $date)."	
										 ) C on C.curve_id = ac.curve_id
								$wStr
						) A
						  JOIN pods p ON A.pod = p.pod_no AND p.customer_id = $customerID
						  group BY A.curve_id, A.pod, A.reading_datetime
					) Z group BY Z.pod, Z.reading_datetime 
					ON DUPLICATE KEY UPDATE far_ea = VALUES(far_ea)";
					
			$nfIDs[] = $sqlQueue->sendUniqueItem([$qStr],"customer_far_$customerID");
			
			$sql = "INSERT INTO customers_far_all (customer_id, year, month) 
					SELECT pp.customer_id, year(acv.consumption_date), MONTH(acv.consumption_date)
					FROM actual_curves_variance acv
					JOIN pods pp ON acv.pod = pp.pod_no AND pp.customer_id = $customerID
					WHERE  year(acv.consumption_date)=".date('Y', $date)." AND MONTH(acv.consumption_date)=".date('n', $date)." LIMIT 1";
			$nfIDs[] = $sqlQueue->sendUniqueItem([$sql],"customers_far_all");
		}
		
		//wait data import
		$sqlQueue->waitFor($nfIDs);		
	}
	
	function checkForecastData($date,$customersIDs,$distributorID,$includedPODs)
	{
		$tableName = 'actual_readings_'.date('n', $date);
		
		$dt = date('Y-m-d 00:00:00', $date);
		
		$wStr='';
		
		if($distributorID > 0)
			$wStr .= ' and ac.distributor_id = '.$distributorID;
		
		if(count($includedPODs)>0 && !empty($includedPODs[0]))
			$wStr = " and acv.pod IN ('".implode("','",$includedPODs)."')";
		
		if(count($customersIDs)>0 && !empty($customersIDs[0]))
			$wStr .= ' AND ac.curve_id IN (SELECT distinct(curve_id) FROM actual_curves_variance acvv JOIN pods ON acvv.pod = pods.pod_no WHERE pods.customer_id IN ('.implode(',',$customersIDs).'))';
		
		$sql = "SELECT COUNT(distinct p.customer_id)=(SELECT COUNT(DISTINCT cfa.customer_id) FROM customers_far_all cfa JOIN customers c ON cfa.customer_id = c.customer_id and c.customer_status = 'Activ' WHERE cfa.year=".date('Y', $date)." AND cfa.month =".date('n', $date).") AS value 
				FROM $tableName ar 
				JOIN actual_curves ac ON ar.curve_id = ac.curve_id 
				JOIN actual_curves_variance acv ON acv.curve_id = ar.curve_id AND YEAR(ar.reading_datetime) = YEAR(acv.consumption_date) AND MONTH(ar.reading_datetime) = month(acv.consumption_date) AND acv.consumption_ea != 0
				JOIN pods p ON acv.pod = p.pod_no
				JOIN customers c ON p.customer_id = c.customer_id and c.customer_status = 'Activ'
				WHERE ar.reading_datetime = '$dt' $wStr";
		
		log_message("debug",$sql);
		$result = $this->getValue($sql);
		
		if($result == 1) return 'text-success';
		
		return 'text-danger';
	}
	
	//cache pods - replace with redis!
	function loadPods()
	{
		$query = $this->db->query("SELECT pod_no, customer_name from pods JOIN customers ON pods.customer_id = customers.customer_id");
		$result = [];
		
		foreach($query->getResultArray() as $p)
			$result[$p['pod_no']] = $p['customer_name'];
		
		return $result;
	}
	
	function hasNewData()
	{
		$query = $this->query("select data_id from actual_data limit 1");
		if(empty($query->getRowArray())) return false;
		
		return true;
	}

	function bulkActualReadingsDestroy2($acutualReadingsGroupIds)
	{
		$gIDs = explode('-',$acutualReadingsGroupIds[0]);	
		$month = $gIDs[2];
		$tableName = 'actual_readings_'.$month;
		$baseSql = "delete from $tableName where ";
		
		$lastCondition='';
		$curveList='';
		$affected = 0;

		$qIDs=[];
		$sqlQueue = $this->getSqlQueue();
		$affected = $this->getValue("select count(reading_id) as value from $tableName");
		
		foreach($acutualReadingsGroupIds as $groupIDs)
		{
			$gIDs = explode('-',$groupIDs);	
			//supplier_id, year, month, curve_id, customer_id
			
			if(count($gIDs)!=5 || $gIDs[0] != $_SESSION['select-supplier']) continue;
				
			$condition = 'supplier_id = '.$gIDs[0].' and year = '.$gIDs[1];
			
			if($lastCondition == '') $lastCondition = $condition;
			
			if($condition != $lastCondition)
			{
				$curveList = substr($curveList,0,-1);
				$sql = $baseSql . $lastCondition. ' and curve_id in ('.$curveList.')';
				
				$qIDs[] = $sqlQueue->sendItem([$sql]);
			
				$lastCondition = $condition;
				$curveList = '';
			}
			
			$curveList .= $gIDs[3].',';
		}
		
		$curveList = substr($curveList,0,-1);
		$sql = $baseSql . $lastCondition. ' and curve_id in ('.$curveList.')';
		
		$qIDs[] = $sqlQueue->sendItem([$sql]);
		
		$sqlQueue->waitFor($qIDs);
		
		return $affected - $this->getValue("select count(reading_id) as value from $tableName");
	}
	
	function bulkCurvesVarianceDestroy($curvesVariancesIds)
	{
		$cids = implode(',',$curvesVariancesIds);
		$sql = "delete from actual_curves_variance where acv_id in ($cids)";
		
		$this->db->query($sql);
		
		return $this->db->affectedRows();
	}
	
	
	function bulkCurvesDestroy($curvesIds)
	{
		$cids = implode(',',$curvesIds);
		$sql = "delete from actual_curves where curve_id in ($cids)";
		
		$this->db->query($sql);
		
		return $this->db->affectedRows();
	}
	
}