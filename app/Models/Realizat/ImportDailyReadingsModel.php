<?php

namespace App\Models\Realizat;

use CodeIgniter\Model;
use CodeIgniter\ConnectionInterface;

require_once(__DIR__.'/../CacheTools.php');
require_once(__DIR__.'/../MasterDataTools.php');

class ImportDailyReadingsModel extends Model
{
	use \MasterDataTools;
	use \CacheTools;
	protected $table      = 'import_daily_readings';
	protected $db;

	
	function __construct()
	{
		$this->db = db_connect();	
	}
	
	
	function async_import_daily_readings_data($pod, $sqlValues)
	{
		$tableName = $this->createTableLikeSource("import_daily_readings_data",strtolower($pod));

		$sql = "INSERT IGNORE INTO $tableName (reading_datetime, reading_ea) VALUES $sqlValues ON DUPLICATE KEY UPDATE reading_ea=VALUES(reading_ea)";
		$sqlQueue = $this->getSqlQueue();
		$id = $sqlQueue->sendUniqueItem([$sql],$tableName);
		
		return $id;
	}

	function import_daily_readings_pod($pod, $distributorID, $county = null, $city = null, $address=null)
	{
		$this->writeData("INSERT IGNORE INTO import_daily_readings (pod, distributor_id, county, city, address) VALUES ('$pod',$distributorID,".$this->db->escape($county).",".$this->db->escape($city).",".$this->db->escape($address).")");
	}

	// Marcheaza o eroare de validare (ex. cifra de control gresita) pe POD-ul din grid.
	// Erorile care incep cu 'Total' sunt pastrate de validateDailyReadings la reincarcarea grilei.
	function setDailyReadingsError($pod, $error)
	{
		$this->writeData("UPDATE import_daily_readings SET error = ".$this->db->escape($error)." WHERE pod = ".$this->db->escape($pod));
	}
		
	function validateDailyReadings()
	{
		//update meta data
		$this->writeData("UPDATE import_daily_readings idr 
							JOIN pods p ON  idr.pod = p.pod_no
							JOIN customers c ON p.customer_id = c.customer_id
							SET  idr.customer_id = c.customer_id, idr.customer_name = c.customer_name, idr.county = p.county, idr.city = p.city, idr.address = p.address, idr.error = IF(idr.error LIKE 'Total%', idr.error, NULL)");
		
		$this->writeData("UPDATE import_daily_readings idr 
							LEFT JOIN pods p ON  idr.pod = p.pod_no 
							SET idr.error = 'POD Nou', idr.customer_name='Necunoscut'
							WHERE p.pod_no IS NULL");

		$data = $this->getValueArray("SELECT lower(pod) as value FROM import_daily_readings");
		
		foreach($data as $pod)
		{
			$this->writeData("UPDATE import_daily_readings idr JOIN 
							(SELECT min(import_daily_readings_data_$pod.reading_datetime) AS interval_min,
							max(import_daily_readings_data_$pod.reading_datetime) AS interval_max,
							sum(import_daily_readings_data_$pod.reading_ea) as ea FROM
							import_daily_readings_data_$pod) A ON idr.pod = '$pod'
							SET idr.interval_min = A.interval_min, idr.interval_max = A.interval_max, idr.ea = A.ea");
		}	

		return $this->getTArray("SELECT idr.*, d.distributor_name FROM import_daily_readings idr 
									JOIN distributors d on idr.distributor_id = d.distributor_id 
									ORDER BY idr.error DESC, idr.customer_name ASC, idr.pod");
	}
	
	function resetImportDailyReadings()
	{
		$data = $this->getValueArray("SELECT lower(pod) as value FROM import_daily_readings");
		
		foreach($data as $pod)
		{
			$this->writeData("DROP TABLE import_daily_readings_data_$pod");
		}	
		
		$this->writeData("TRUNCATE TABLE import_daily_readings");
		
		return "";
	}
	
	function saveDailyReadingsToForecast($distributorID, $customerIDs, $year, $month)
	{
		if(empty($customerIDs))
			$customerIDs = $this->getValueArray("SELECT customer_id as value FROM customer_daily_readings_all cdra WHERE month=$month AND year = $year");
		
		$sqlQueue = $this->getSqlQueue();
		$fqIDs=[];
		foreach($customerIDs as $customerID)
		{
			// insert into far aggregate
			$asql = "INSERT IGNORE INTO customers_far_all (customer_id, `year`, `month`) VALUES ($customerID, $year, $month)";
			$fqIDs[] = $sqlQueue->sendUniqueItem([$asql],"customers_far_all");
			
			$asql = "INSERT IGNORE INTO customer_far_$customerID (pod, far_datetime, far_ea)
					SELECT pod, reading_datetime, reading_ea FROM customer_daily_readings_$customerID WHERE year(reading_datetime) = $year and month(reading_datetime) = $month";
			$fqIDs[] = $sqlQueue->sendUniqueItem([$asql],"customers_far_all");
		}
		
		//wait data import for daily readings
		$sqlQueue->waitFor($fqIDs);
		
		return "Datele a ".count($customerIDs)." clienti au fost transferate!";
	}
	
	function saveDailyReadings()
	{
		$data = $this->getArray("SELECT pod,customer_id FROM import_daily_readings WHERE error IS NULL ORDER BY customer_id,pod");
		
		$sqlQueue = $this->getSqlQueue();
		$customerID = ''; $sql=[]; $qIDs=[]; $fqIDs=[]; $tableName=''; 
		$newFarSql = []; $newFarTableName = ''; $nfIDs=[];
				 
		foreach($data as $row)
		{
			$sourceDataTable = 'import_daily_readings_data_'.strtolower($row['pod']);
			
			if($customerID != $row['customer_id'])
			{
				if(!empty($customerID))
				{
					$qIDs[] = $sqlQueue->sendUniqueItem($sql,$tableName);
					$nfIDs[] = $sqlQueue->sendUniqueItem($newFarSql,$newFarTableName);
				}
				
				$customerID = $row['customer_id']; 
				$sql=[]; $tableName = "customer_daily_readings_$customerID"; $this->createTableLikeSource("customer_daily_readings",$customerID);				
				$newFarSql=[];$newFarTableName = "customer_far_$customerID"; $this->createTableLikeSource("customer_far",$customerID);
			}
			
			//transfer data to forecast
			if(!$this->isTable($sourceDataTable)) continue;
			
			$months = $this->getValueArray("SELECT distinct month(reading_datetime) as value from $sourceDataTable");
						
			foreach($months as $m)
			{
				// insert into daily readings aggregate				
				$asql = "INSERT IGNORE INTO customer_daily_readings_all (customer_id, `year`, `month`)
						 SELECT $customerID, YEAR(reading_datetime), MONTH(reading_datetime) FROM $sourceDataTable
						 WHERE reading_datetime IS NOT NULL AND MONTH(reading_datetime) > 0
						 GROUP BY YEAR(reading_datetime),MONTH(reading_datetime)";
				$fqIDs[] = $sqlQueue->sendUniqueItem([$asql],"customer_daily_readings_all");

				// insert into far aggregate
				$asql = "INSERT IGNORE INTO customers_far_all (customer_id, `year`, `month`)
						 SELECT $customerID, YEAR(reading_datetime), MONTH(reading_datetime) FROM $sourceDataTable
						 WHERE reading_datetime IS NOT NULL AND MONTH(reading_datetime) > 0
						 GROUP BY YEAR(reading_datetime),MONTH(reading_datetime)";
				$fqIDs[] = $sqlQueue->sendUniqueItem([$asql],"customers_far_all");
			}
			
			
			//transfer data to customer daily readings
			$sql[] = "INSERT IGNORE INTO $tableName (pod, reading_datetime, reading_ea) SELECT '{$row['pod']}', reading_datetime, reading_ea FROM $sourceDataTable ON DUPLICATE KEY UPDATE reading_ea=VALUES(reading_ea)";
			
			//transfer data to customer far
			$newFarSql[] = "INSERT IGNORE INTO $newFarTableName (pod, far_datetime, far_ea) SELECT '{$row['pod']}', reading_datetime, reading_ea FROM $sourceDataTable ON DUPLICATE KEY UPDATE far_ea=VALUES(far_ea)";	
		}
		
		//last customer
		if(!empty($customerID) && !empty($sql))
		{
			$qIDs[] = $sqlQueue->sendUniqueItem($sql,$tableName);
			$nfIDs[] = $sqlQueue->sendUniqueItem($newFarSql,$newFarTableName);
		}
		
		//wait data import for daily readings
		$sqlQueue->waitFor($fqIDs);

		//wait data import for far
		$sqlQueue->waitFor($nfIDs);
		
		//wait data import for customers
		$sqlQueue->waitFor($qIDs);
		
		//cleanup
		$qIDs = [];

		$sql = "DELETE FROM import_daily_readings WHERE error IS NULL";
		$qIDs[] = $sqlQueue->sendUniqueItem([$sql],'import_daily_readings');		
		foreach($data as $row)
		{
			$sourceDataTable = 'import_daily_readings_data_'.strtolower($row['pod']);
			$sql = "DROP TABLE $sourceDataTable";
			$qIDs[] = $sqlQueue->sendUniqueItem([$sql],$sourceDataTable);
		}
		//wait cleanup
		$sqlQueue->waitFor($qIDs);							
		
		return "";		
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
		$ym = explode('-',$date);
		$year = $ym[0];
		$month = $ym[1];
		
		if(is_numeric($distributorID) && $distributorID >0)
			$query = $this->db->query("SELECT c.customer_id, c.customer_name FROM customers c 
										JOIN pods p ON c.customer_id = p.customer_id
										WHERE p.distributor_id = $distributorID
										AND p.pod_no IN (SELECT distinct co.pod FROM consumptions co where year(co.consumption_date)=$year AND MONTH(co.consumption_date)=$month)		
										GROUP BY c.customer_id ORDER BY c.customer_name");
		else
			$query = $this->db->query("SELECT c.customer_id, c.customer_name FROM customers c
										JOIN pods p on c.customer_id = p.customer_id
										WHERE p.pod_no IN (SELECT distinct co.pod FROM consumptions co where year(co.consumption_date)=$year AND MONTH(co.consumption_date)=$month)		
										GROUP BY c.customer_id ORDER BY c.customer_name");
	
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
			
		
		$wStr .= ' year(reading_datetime) = '.date('Y', $date).' and month(reading_datetime) = '.date('n', $date);
		
		if($qmin)
		{
			$gStr = ' ( 4 * HOUR( reading_datetime ) + FLOOR( MINUTE( reading_datetime ) / 15 )) ';
			$oMin = 'minute(A.reading_datetime),';
			$dateTime = 'reading_datetime';
		}
		else
		{
			$gStr = ' hour(A.reading_datetime) ';
			$oMin = '';
			$dateTime = "date_format(ar.reading_datetime,'%Y-%m-%d %H:00:00')";
		}
		
		$qStr = "SELECT A.*, SUM(A.actual_ea) AS ea FROM 
		(
			SELECT d.distributor_id, d.distributor_name AS distributor_name,ar.reading_id AS reading_id,ar.supplier_id AS supplier_id,
			$dateTime AS reading_datetime,ar.customer_id AS customer_id,ar.curve_id AS curve_id,
			ar.actual_ea,
				(SELECT GROUP_CONCAT(DISTINCT pod SEPARATOR ', ') FROM actual_curves_variance acv WHERE acv.supplier_id = ar.supplier_id AND acv.curve_id = ar.curve_id 
				AND year(acv.consumption_date) = year(ar.reading_datetime) AND month(acv.consumption_date) = month(ar.reading_datetime) group by acv.curve_id) AS PODs
			from $tableName ar 
			join actual_curves ac on ar.curve_id = ac.curve_id
			join distributors d on ac.distributor_id = d.distributor_id
			$wStr
		) A	
		$wStr2 
		group by cast(A.reading_datetime as date),
		$gStr 
		order by hour(A.reading_datetime), $oMin cast(A.reading_datetime as date)";
		
		return $this->getArray($qStr);
	}
	
	function exportReadingsToForecast($date,$customersIDs,$distributorID,$includedPODs)
	{
		
		$tableName = 'actual_readings_'.date('n', $date);
		
		$wStr='where';
		
		if($distributorID > 0)
			$wStr .= ' d.distributor_id = '.$distributorID.' and ';
		
		$wStr2 = '';

		if(count($includedPODs)>0 && !empty($includedPODs[0]))
			$wStr2 = "acv.pod IN ('".implode("','",$includedPODs)."') and";
			
		
		$wStr .= ' year(reading_datetime) = '.date('Y', $date).' and month(reading_datetime) = '.date('n', $date);
		
		if(count($customersIDs)>0 && !empty($customersIDs[0]))
			$wStr .= ' AND ac.curve_id IN (SELECT distinct(curve_id) FROM actual_curves_variance acvv JOIN pods ON acvv.pod = pods.pod_no WHERE pods.customer_id IN ('.implode(',',$customersIDs).'))';
				
		$qStr = "INSERT IGNORE INTO forecast_$tableName (supplier_id, customer_id, pod, far_datetime, far_ea) 
			
				SELECT Z.supplier_id, Z.customer_id, Z.pod, Z.reading_datetime, sum(ea) FROM (
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
										WHERE $wStr2 year(acv.consumption_date)=".date('Y', $date)." AND MONTH(acv.consumption_date)=".date('n', $date)."	
									 ) C on C.curve_id = ac.curve_id
							$wStr 
					) A
					  JOIN pods p ON A.pod = p.pod_no
					  group BY A.curve_id, A.pod, cast(A.reading_datetime as date), hour(A.reading_datetime)
				) Z group BY Z.pod, cast(Z.reading_datetime as date), hour(Z.reading_datetime)
				  
				ON DUPLICATE KEY UPDATE far_ea=VALUES(far_ea)
				";
		
		//log_message('error', $qStr);
		
		$this->writeData($qStr);
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
		
		$sql = "SELECT COALESCE(min(case when far.far_id is NULL then FALSE ELSE TRUE END), FALSE) AS value 
				FROM $tableName ar 
				JOIN actual_curves ac ON ar.curve_id = ac.curve_id 
				JOIN actual_curves_variance acv ON acv.curve_id = ar.curve_id AND YEAR(ar.reading_datetime) = YEAR(acv.consumption_date) AND MONTH(ar.reading_datetime) = month(acv.consumption_date) AND acv.consumption_ea != 0
				LEFT JOIN forecast_$tableName far ON ar.reading_datetime = far.far_datetime AND acv.pod = far.pod
				WHERE ar.reading_datetime = '$dt' $wStr";
		
		log_message('error',$sql);
		
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

	function getPodByDevLoc($pod_dev_loc)
	{
		return $this->getValue("SELECT pod_no as value FROM pods WHERE pod_dev_loc = ".$this->db->escape($pod_dev_loc)." limit 1");
	}
	
}