<?php

namespace App\Models\Prognoza;

use CodeIgniter\Model;
use CodeIgniter\ConnectionInterface;

require_once(__DIR__.'/../CacheTools.php');
require_once(__DIR__.'/../MasterDataTools.php');

class FarModel extends Model
{
	use \MasterDataTools;
	use \CacheTools;
	protected $table      = 'customers_far_all';
	protected $db;
	
	protected $supplierID;
	
	function __construct()
	{
		$this->db = db_connect();
		
		$this->supplierID = $_SESSION['select-supplier'];
	}
	
	function get_far_date_min_max_years()
	{
		$sql = "SELECT min(year) AS minY, max(year) AS maxY FROM customers_far_all";
		
		$result = $this->getRow();
		if(!$result) return ['minY'=>2020, 'maxY'=>2020];

		return $result;
	}
			
	function customersFreeDaysReport($month, $year)
	{	
		$this->writeData("DROP TEMPORARY TABLE weekDays");
		$this->writeData("CREATE TEMPORARY TABLE weekDays AS
			SELECT * FROM(
			SELECT 1 AS dayIndex, 'Duminica' AS dayIndexName UNION
			SELECT 2 AS dayIndex, 'Luni' AS dayIndexName UNION
			SELECT 3 AS dayIndex, 'MMJ' AS dayIndexName UNION
			SELECT 4 AS dayIndex, 'MMJ' AS dayIndexName UNION
			SELECT 5 AS dayIndex, 'MMJ' AS dayIndexName UNION
			SELECT 6 AS dayIndex, 'Vineri' AS dayIndexName UNION
			SELECT 7 AS dayIndex, 'Sambata' AS dayIndexName) A");

		$this->writeData("DROP TEMPORARY TABLE dailyReadings");
	
		$sql = '';
		$customerIDs = $this->getValueArray("SELECT customer_id as value FROM customers_far_all WHERE year = $year AND month = $month");
		foreach($customerIDs as $customerID)
			$sql .= " SELECT fcct.customer_id,DATE(far.far_datetime) AS far_date,
			case
			when wfd.mapping IS NOT NULL then wfd.mapping
			when DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3
			ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex,
			wfdp.profile_name,
			SUM(far.far_ea) AS far_ea,
			'Date Orare' As source
			FROM forecast_customers_consumption_types fcct
			JOIN customer_far_$customerID far ON fcct.customer_id = $customerID AND MONTH(far.far_datetime) = $month AND YEAR(far.far_datetime) = $year
			LEFT JOIN working_free_days wfd ON wfd.wfd_profile_id=fcct.wfd_profile_id AND date(far.far_datetime) = wfd.free_date
			LEFT JOIN working_free_days_profiles wfdp ON fcct.wfd_profile_id = wfdp.wfd_profile_id
			GROUP BY DATE(far.far_datetime) UNION";
		$sql = substr($sql,0,-5);
		$this->writeData("CREATE TEMPORARY TABLE dailyReadings AS SELECT * FROM ($sql) A");
	
		$this->writeData("INSERT INTO dailyReadings (customer_id, far_date, dayIndex, profile_name, far_ea, source) 
			SELECT fcct.customer_id,DATE(fs.synthetics_datetime) AS far_date,
			case
			when wfd.mapping IS NOT NULL then wfd.mapping
			when DAYOFWEEK(fs.synthetics_datetime) BETWEEN 3 AND 5 THEN 3
			ELSE DAYOFWEEK(fs.synthetics_datetime) END AS dayIndex,
			wfdp.profile_name,
			SUM(fs.synthetic_ea) AS far_ea,
			'Sintetice' As source
			FROM forecast_customers_consumption_types fcct
			JOIN forecast_synthetics fs ON fs.customer_id = fcct.customer_id
			LEFT JOIN working_free_days wfd ON wfd.wfd_profile_id=fcct.wfd_profile_id AND date(fs.synthetics_datetime) = wfd.free_date
			LEFT JOIN working_free_days_profiles wfdp ON fcct.wfd_profile_id = wfdp.wfd_profile_id
			WHERE YEAR(fs.synthetics_datetime) = $year AND MONTH(fs.synthetics_datetime) = $month
			GROUP BY fs.customer_id,DATE(fs.synthetics_datetime)");
		
		$this->writeData("DROP TEMPORARY TABLE dailyResults");		
		$this->writeData("CREATE TEMPORARY TABLE dailyResults  AS
			SELECT c.customer_id, c.customer_name,dr.profile_name, dr.far_date,dr.dayIndex, dr.far_ea, M.avgDayIndex, dr.source FROM dailyReadings dr
			JOIN (SELECT customer_id, SUM(far_ea) as fea FROM dailyReadings GROUP BY customer_id) F ON F.customer_id = dr.customer_id AND fea >= 1
			JOIN (SELECT customer_id, dayIndex, AVG(far_ea) AS avgDayIndex FROM dailyReadings GROUP BY customer_id, dayIndex) M ON dr.customer_id = M.customer_id AND dr.dayIndex = M.dayIndex
			JOIN customers c ON dr.customer_id = c.customer_id
			WHERE M.avgDayIndex>0 AND dr.far_ea>0 AND (avgDayIndex>=3*dr.far_ea OR dr.far_ea>=3*avgDayIndex)  AND (avgDayIndex>=0.5 OR dr.far_ea>=0.5)");


		return $this->getTArray("SELECT R.*, wd.dayIndexName, pwd.dayIndexName AS proposedDayIndexName FROM (
			SELECT concat(res.customer_id,UNIX_TIMESTAMP(res.far_date)) AS id,res.customer_id, res.customer_name,res.profile_name, res.far_date,res.far_ea, res.dayIndex, res.avgDayIndex, res.source, case when res.avgDayIndex > res.far_ea then 1 ELSE 3 END AS proposedDayIndex, AVG(dr.far_ea) AS proposedDayIndexAverage
			FROM dailyResults res
			JOIN dailyReadings dr ON res.customer_id = dr.customer_id AND case when res.avgDayIndex > res.far_ea then 1 ELSE 3 END = dr.dayIndex
			GROUP BY res.customer_id, res.far_date
			HAVING abs(res.far_ea-AVG(dr.far_ea)) < abs(res.far_ea - res.avgDayIndex)) R
			JOIN weekDays wd ON R.dayIndex = wd.dayIndex
			JOIN weekDays pwd ON R.proposedDayIndex = pwd.dayIndex");
	}
	
	public function getCustomersInTPWithConsumptionTypes($year, $month)
	{
		$sql = "SELECT c.customer_id,c.customer_name,fct.consumption_type_name FROM customers c
				LEFT JOIN forecast_customers_consumption_types fcct ON fcct.customer_id = c.customer_id 
				LEFT JOIN forecast_consumption_types fct ON fct.fct_id = fcct.fct_id OR (fcct.fcct_id IS NULL AND fct.consumption_type_name='General')
				JOIN (SELECT ct.customer_id, MIN(sr.start_date), MAX(ct.contract_stop) from contracts ct
										  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
                                WHERE (ct.contract_stop IS NULL OR ct.contract_stop >= now())
                                GROUP BY ct.customer_id ) A on c.customer_id = A.customer_id
				JOIN customers_far_all far ON far.customer_id = c.customer_id AND far.month = $month and far.year = $year
				WHERE c.customer_status='activ'
				GROUP BY c.customer_id
				ORDER BY fct.consumption_type_name, c.customer_name";
	
		$result['data'] = $this->getArray($sql);
		$result['total'] = count($result['data']);//$query->countAllResults();
		
		return $result;
	}
	
	public function getPODsOfCustomersWithConsumption($year, $month, $customers)
	{
		if(empty($customers))
			$sql = "SELECT pod_no as pod, coalesce(county,'') as county from pods where customer_id in (SELECT ct.customer_id from contracts ct
										  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
                                WHERE (ct.contract_stop IS NULL OR ct.contract_stop >= now())
                                GROUP BY ct.customer_id )
								ORDER BY county";
		else
		{
			$customerIDs = implode(',',$customers);
			$sql = "SELECT pod_no as pod, coalesce(county,'') as county from pods where customer_id in ($customerIDs) ORDER BY county";
		}
		
		$result['data'] = $this->getArray($sql);
		$result['total'] = count($result['data']);
		
		return $result;
	}
	
	public function generateCurveForPOD($source_customer,$source_pod,$dest_customer,$dest_pod,$start_year,$start_month,$stop_year,$stop_month,$toBeValue,$valueType)
	{
		$nrModif = 0;
		if($valueType == 'Percent')
		{
			$start = strtotime("$start_year-$start_month-01");
			$end = strtotime("$stop_year-$stop_month-01");
			while($start <= $end)
			{
				$month = date('n',$start);
				$year = date('Y',$start);
				$sql = "INSERT INTO customer_far_{$dest_customer} (pod, far_datetime, far_ea)
					SELECT '$dest_pod', far_datetime,  far_ea * $toBeValue/100
					FROM customer_far_{$source_customer}
					WHERE pod = '$source_pod' and year(far_datetime) = $year and month(far_datetime) = $month
					ON DUPLICATE KEY UPDATE far_ea = VALUES(far_ea)";
				$nrModif = $this->writeData($sql);
			
				 $start = strtotime("+1 month", $start);
			}
		}
		else //start_date eq stop_date
		{
			$sql = "INSERT INTO customer_far_{$dest_customer} (pod, far_datetime, far_ea)
					SELECT '$dest_pod', far_datetime, 
					far_ea * (SELECT $toBeValue/sum(far_ea) FROM customer_far_{$source_customer} WHERE pod = '$source_pod' AND year(far_datetime) = $start_year AND month(far_datetime) = $start_month)
					FROM customer_far_{$source_customer} 
					WHERE pod = '$source_pod' AND year(far_datetime) = $start_year AND month(far_datetime) = $start_month
					ON DUPLICATE KEY UPDATE far_ea = VALUES(far_ea)";
			$nrModif = $this->writeData($sql);
		}
		
		return $nrModif.' valori adaugate/actualizate';
	}
		
	public function getFarPODEA($year, $month, $pod)
	{
		$customerID = $this->getValue("SELECT customer_id as value FROM pods WHERE pod_no = '$pod'");
		$sql = "SELECT coalesce(SUM(far_ea),0) AS ea FROM customer_far_$customerID WHERE pod = '$pod' AND YEAR(far_datetime)=$year AND MONTH(far_datetime)=$month";
		
		return $this->getRow($sql);
	}
	
	public function uploadValues($YMdate, $values, $customerID, $pod)
	{
		$sqlData='';
		$sqlDeleteData='';
		
		$dt =  \DateTime::createFromFormat('Y-n',$YMdate);
		$YMdate = $dt->format('Y-m');
		$month = $dt->format('n');
		
		$maxDays = cal_days_in_month(CAL_GREGORIAN,date('n',$dt->getTimestamp()),date('Y',$dt->getTimestamp()));
		$modifiedValues = 0;
		
		for($h=0;$h<24;$h++)
		{
			for($d = 1;$d<=$maxDays;$d++)
			{
				$dateTime = $YMdate.'-'.sprintf('%02d',$d).' '.sprintf('%02d',$h).':00:00';
				$ea = $values[$h][$d-1];
				
				if($ea !='' && $pod)
				{
					$sqlData.="('$pod', '$dateTime', $ea),";
					$modifiedValues ++;
				}
				elseif($ea == '')
				{
					$modifiedValues ++;
					$sqlDeleteData.="'$dateTime',";
				}
			}
		}
		
		
		$added = 0; $deleted = 0;
		if(!empty($sqlData)) 
		{
			$sqlData = rtrim($sqlData,',');
			$added = $this->importSQLValues($sqlData, $customerID);
		}
		
		if(!empty($sqlDeleteData)) 
		{
			$sqlDeleteData = rtrim($sqlDeleteData,',');
			$deleted = $this->deleteSQLValues($sqlDeleteData, $month, $customerID, $pod);
		}
		
		
		return $modifiedValues." valori actualizate!";
	}
	
	public function deleteSQLValues($data, $month, $customerID, $pod)
	{
		if($pod)
			$sql = "delete from customer_far_$customerID where pod = '$pod' and far_datetime in ($data)";
		else
			$sql = "delete from customer_far_$customerID where far_datetime in ($data)";
		return $this->writeData($sql);
	}
	
	public function importSQLValues($data, $customerID)
	{
		$sql = "insert into customer_far_$customerID (pod, far_datetime, far_ea) VALUES $data ON DUPLICATE KEY UPDATE far_ea=VALUES(far_ea)";
		return $this->writeData($sql);
	}
	
	
	public function getFarDataMM($consumptionType,$customersIDs,$pod,$year,$months)
	{
		if(empty($months)) return [];

		$supplierID = $this->supplierID;
		$podJOIN = "";
		if($customersIDs === null) $customersIDs = [];

		if(count($customersIDs) == 0 && $consumptionType != -1)
		{
			$sql = "SELECT c.customer_id as value FROM customers c
				LEFT JOIN forecast_customers_consumption_types fcct ON fcct.customer_id = c.customer_id
				LEFT JOIN forecast_consumption_types fct ON fct.fct_id = fcct.fct_id OR (fcct.fcct_id IS NULL AND fct.consumption_type_name='General')
				JOIN (SELECT ct.customer_id FROM contracts ct
					JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
					WHERE now()>=sr.start_date AND (ct.contract_stop IS NULL OR ct.contract_stop >= now())
					GROUP BY ct.customer_id) A ON c.customer_id = A.customer_id
				WHERE c.customer_status='activ' AND fct.fct_id = $consumptionType AND c.customer_id IN (SELECT distinct customer_id FROM customers_far_all)";
			$customersIDs = $this->getValueArray($sql);
		}
		elseif(count($customersIDs) == 0 && $consumptionType == -1)
		{
			$sql = "SELECT c.customer_id as value FROM customers c
				JOIN (SELECT ct.customer_id FROM contracts ct
					JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
					WHERE now()>=sr.start_date AND (ct.contract_stop IS NULL OR ct.contract_stop >= now())
					GROUP BY ct.customer_id) A ON c.customer_id = A.customer_id
				WHERE c.customer_status='activ' AND c.customer_id IN (SELECT distinct customer_id FROM customers_far_all)
				GROUP BY c.customer_id";
			$customersIDs = $this->getValueArray($sql);
		}

		if(count($customersIDs) == 0) return [];

		// Sort months and build a single date range covering all selected months
		sort($months);
		$rangeStart = $year.'-'.sprintf('%02d', $months[0]).'-01';
		$lastMonth  = end($months);
		$rangeEnd   = "DATE_ADD('$year-".sprintf('%02d', $lastMonth)."-01', INTERVAL 1 MONTH)";

		$podWhr = $pod ? " far.pod = '$pod' AND " : "";

		$sql = '';
		foreach($customersIDs as $customerID)
		{
			$sql .= " SELECT month(far.far_datetime) as month, hour(far.far_datetime) as hour, day(far.far_datetime) as day, sum(far.far_ea) as far_ea
				FROM customer_far_$customerID far
				WHERE {$podWhr}far.far_datetime >= '$rangeStart' AND far.far_datetime < $rangeEnd
				GROUP BY month(far.far_datetime), hour(far.far_datetime), date(far.far_datetime) UNION ALL";
		}
		$sql = substr($sql, 0, -9);
		$sql = "SELECT A.month, sum(A.far_ea) as far_ea, A.hour, A.day FROM ($sql) A GROUP BY A.month, A.hour, A.day";

		$rows = $this->getArray($sql);

		// Restructure to $result[$month] = [...] to preserve existing JS interface
		$result = [];
		foreach($months as $m) $result[$m] = [];
		foreach($rows as $row)
		{
			$m = (int)$row['month'];
			unset($row['month']);
			$result[$m][] = $row;
		}
		return $result;
	}
	
	// Like getFarDataMM but returns data grouped by POD.
	// Returns {pod: {month: [{hour, day, far_ea}]}}
	public function getFarDataMMPods($customersIDs, $year, $months)
	{
		if (empty($months) || empty($customersIDs)) return [];

		sort($months);
		$rangeStart = $year . '-' . sprintf('%02d', $months[0]) . '-01';
		$lastMonth  = end($months);
		$rangeEnd   = "DATE_ADD('$year-" . sprintf('%02d', $lastMonth) . "-01', INTERVAL 1 MONTH)";

		$sql = '';
		foreach ($customersIDs as $customerID) {
			$customerID = (int)$customerID;
			$sql .= " SELECT COALESCE(far.pod,'') as pod, month(far.far_datetime) as month,
						hour(far.far_datetime) as hour, day(far.far_datetime) as day,
						sum(far.far_ea) as far_ea
					FROM customer_far_$customerID far
					WHERE far.far_datetime >= '$rangeStart' AND far.far_datetime < $rangeEnd
					GROUP BY far.pod, month(far.far_datetime), hour(far.far_datetime), date(far.far_datetime)
					UNION ALL";
		}
		$sql = substr($sql, 0, -9);
		$sql = "SELECT A.pod, A.month, A.hour, A.day, sum(A.far_ea) as far_ea
				FROM ($sql) A
				GROUP BY A.pod, A.month, A.hour, A.day";

		$rows = $this->getArray($sql);

		// Collect all pods and months
		$allPods = array_unique(array_column($rows, 'pod'));
		$result  = [];
		foreach ($allPods as $p) {
			$result[$p] = [];
			foreach ($months as $m) $result[$p][$m] = [];
		}
		foreach ($rows as $row) {
			$p = $row['pod'];
			$m = (int)$row['month'];
			$result[$p][$m][] = ['hour' => $row['hour'], 'day' => $row['day'], 'far_ea' => $row['far_ea']];
		}
		return $result;
	}

	public function getFarData($consumptionType,$customersIDs,$pod,$year,$month)
	{
		$supplierID = $this->supplierID;
		
		$wStr='WHERE';
		
		$podJOIN = "";
		if($pod)
			$podJOIN = "JOIN pods p on p.customer_id = c.customer_id and p.pod_no = '$pod'";
			
		
		if(count($customersIDs) == 0 && $consumptionType !=-1)
		{
			$sql = "SELECT c.customer_id as value FROM customers c
				$podJOIN
				LEFT JOIN forecast_customers_consumption_types fcct ON fcct.customer_id = c.customer_id 
				LEFT JOIN forecast_consumption_types fct ON fct.fct_id = fcct.fct_id OR (fcct.fcct_id IS NULL AND fct.consumption_type_name='General')
				JOIN
				(SELECT ct.customer_id, MIN(sr.start_date), MAX(ct.contract_stop) from contracts ct
										  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
                                WHERE now()>=sr.start_date AND (ct.contract_stop IS NULL OR ct.contract_stop >= now())
                                GROUP BY ct.customer_id ) A on c.customer_id = A.customer_id
				WHERE c.customer_status='activ' AND fct.fct_id = $consumptionType AND c.customer_id IN (SELECT distinct customer_id FROM customers_far_all)";
			
			$customersIDs = $this->getValueArray($sql);
		}
		elseif(count($customersIDs) == 0 && $consumptionType ==-1)
		{
			$sql = "SELECT c.customer_id as value FROM customers c
				$podJOIN
				JOIN
				(SELECT ct.customer_id, MIN(sr.start_date), MAX(ct.contract_stop) from contracts ct
										  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
                                WHERE now()>=sr.start_date AND (ct.contract_stop IS NULL OR ct.contract_stop >= now())
                                GROUP BY ct.customer_id ) A on c.customer_id = A.customer_id
				WHERE c.customer_status='activ' AND c.customer_id IN (SELECT distinct customer_id FROM customers_far_all)
				GROUP by c.customer_id";
			
			$customersIDs = $this->getValueArray($sql);
		}
		
		if(count($customersIDs) == 0) return [];
		
		if($pod)
			$wStr .= " far.pod = '$pod' AND ";
	
		$monthStart = $year.'-'.sprintf('%02d', $month).'-01';
		$wStr .= " far.far_datetime >= '$monthStart' AND far.far_datetime < DATE_ADD('$monthStart', INTERVAL 1 MONTH)";
		
		$gStr = 'GROUP BY hour(far.far_datetime), date(far.far_datetime) ';

		$dateTime = "date_format(ar.reading_datetime,'%Y-%m-%d %H:00:00')";
	
		$sql = '';
		foreach($customersIDs as $customerID)
		{
			$sql .= " SELECT far.far_id, hour(far.far_datetime) as hour, day(far.far_datetime) as day, sum(far.far_ea) as far_ea
			from customer_far_$customerID far
			$wStr
			$gStr UNION";	
		}
		
		$sql = substr($sql, 0, -5);
		
		$sql = "SELECT sum(A.far_ea) as far_ea, A.hour, A.day FROM ($sql) A group by A.hour, A.day";
		$result = $this->getArray($sql);
		
		return $result;
	}

	public function updateCustomersProfile($customers,$year,$month)
	{
		foreach($customers as $c)
		{
			$coef = $c->invoiced_ea/$c->far_ea;
			$customerID = $c->customer_id;
			$mStart = $year.'-'.sprintf('%02d', $month).'-01';
			$sql = "UPDATE customer_far_$customerID far SET far_ea = cast($coef * far.far_ea AS DECIMAL(20,8))
			WHERE far.far_datetime >= '$mStart' AND far.far_datetime < DATE_ADD('$mStart', INTERVAL 1 MONTH)";
			$this->writeData($sql);
		}
	}
	
	//daily readings
	public function getCustomersInTPWithDailyReadings($distributorID, $year, $month)
	{
		$podJOIN = "";
		if($distributorID != -1)
			$podJOIN = "JOIN pods p on p.customer_id = c.customer_id AND p.distributor_id = $distributorID";
		
		$dt = "$year-$month-01 00:00:00";
		$sql = "SELECT c.customer_id,c.customer_name FROM customers c
				$podJOIN
				JOIN (SELECT ct.customer_id, MIN(sr.start_date), MAX(ct.contract_stop) from contracts ct
										  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
                                WHERE (ct.contract_stop IS NULL OR ct.contract_stop >= now())
                                GROUP BY ct.customer_id ) A on c.customer_id = A.customer_id
				JOIN customer_daily_readings_all cdra ON cdra.customer_id = c.customer_id AND cdra.year = $year AND cdra.month = $month
				WHERE c.customer_status='activ'
				GROUP BY c.customer_id
				ORDER BY c.customer_name";
		
		return $this->getTArray($sql);
	}
	
	public function getPODsOfCustomersWithDailyReadings($distributorID, $year, $month, $customers)
	{
		$podWhr = "";
		if($distributorID != -1)
			$podWhr = "AND distributor_id = $distributorID";
		
		if(empty($customers))
			$sql = "SELECT pod_no as pod, coalesce(county,'') as county from pods where customer_id in (SELECT ct.customer_id from contracts ct
										  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
										  JOIN customer_daily_readings_all cdra ON cdra.customer_id = ct.customer_id AND cdra.year = $year AND cdra.month = $month  
                                WHERE (ct.contract_stop IS NULL OR ct.contract_stop >= now())
                                GROUP BY ct.customer_id ) $podWhr
								ORDER BY county";
		else
		{
			$customerIDs = implode(',',$customers);
			$sql = "SELECT pod_no as pod, coalesce(county,'') as county from pods where customer_id in ($customerIDs) $podWhr ORDER BY county";
		}
		
		$result['data'] = $this->getArray($sql);
		$result['total'] = count($result['data']);
		
		return $result;
	}
	
	public function GetDailyReadingsData($intervals, $distributorID,$customersIDs,$pod,$year,$month)
	{		
		$podJOIN = "";
		if($pod)
			$podJOIN = "JOIN pods p ON p.customer_id = c.customer_id AND p.pod_no = '$pod'";

		if($distributorID != -1)
		{
			if($pod)
				$podJOIN .= " AND p.distributor_id = $distributorID";
			else
				$podJOIN = "JOIN pods p on p.customer_id = c.customer_id AND p.distributor_id = $distributorID";
		}
		
		if(count($customersIDs) == 0 && $distributorID !=-1)
		{
			$sql = "SELECT c.customer_id as value FROM customers c
				JOIN customer_daily_readings_all cdra ON cdra.customer_id = c.customer_id AND cdra.year = $year AND cdra.month = $month 
				$podJOIN
				JOIN
				(SELECT ct.customer_id, MIN(sr.start_date), MAX(ct.contract_stop) from contracts ct
										  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
                                WHERE now()>=sr.start_date AND (ct.contract_stop IS NULL OR ct.contract_stop >= now())
                                GROUP BY ct.customer_id ) A on c.customer_id = A.customer_id
				WHERE c.customer_status='activ'";
			
			$customersIDs = $this->getValueArray($sql);
		}
		elseif(count($customersIDs) == 0 && $distributorID ==-1)
		{
			$sql = "SELECT c.customer_id as value FROM customers c
				JOIN customer_daily_readings_all cdra ON cdra.customer_id = c.customer_id AND cdra.year = $year AND cdra.month = $month  
				$podJOIN
				JOIN
				(SELECT ct.customer_id, MIN(sr.start_date), MAX(ct.contract_stop) from contracts ct
										  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
                                WHERE now()>=sr.start_date AND (ct.contract_stop IS NULL OR ct.contract_stop >= now())
                                GROUP BY ct.customer_id ) A on c.customer_id = A.customer_id
				WHERE c.customer_status='activ'";
			
			$customersIDs = $this->getValueArray($sql);
		}
		
		
		$sql='';
		foreach($customersIDs as $customerID)
		{
			$wStr='WHERE';
			
			$sql .= "SELECT reading_datetime as datetime, sum(reading_ea) as ea FROM customer_daily_readings_$customerID ";

			if($pod)
				$wStr .= " pod = '$pod' AND ";
			
			if($distributorID != -1 AND empty($pod))
				$wStr .= " pod IN (SELECT pod_no FROM pods WHERE distributor_id = $distributorID) AND ";
			
			$sql .= $wStr . " year(reading_datetime) = $year AND month(reading_datetime) = $month";
			
			if($intervals == 24)
				$sql .= ' GROUP BY hour(reading_datetime), date(reading_datetime) UNION ';
			else
				$sql .= ' GROUP BY reading_datetime UNION ';
		}	
		
		if($sql != '')
		{
			if($intervals == 24)
				$sql = "SELECT datetime, sum(ea) as ea FROM (".substr($sql,0,-6).") A GROUP BY hour(datetime), day(datetime)";
			else
				$sql = "SELECT datetime, sum(ea) as ea FROM (".substr($sql,0,-6).") A GROUP BY datetime";
			
			return $this->getArray($sql);
		}
		
		return [];
	}
	
	public function GetDailyReadingsErrors($distributorID,$customersIDs,$pod,$year,$month)
	{		
		$podJOIN = "";
		if($pod)
			$podJOIN = "JOIN pods p ON p.customer_id = c.customer_id AND p.pod_no = '$pod'";

		if($distributorID != -1)
		{
			if($pod)
				$podJOIN .= " AND p.distributor_id = $distributorID";
			else
				$podJOIN = "JOIN pods p on p.customer_id = c.customer_id AND p.distributor_id = $distributorID";
		}
		
		if(count($customersIDs) == 0)
		{
			$sql = "SELECT DISTINCT fcct.customer_id AS value FROM forecast_consumption_types_options fcto
				JOIN forecast_customers_consumption_types fcct ON fcto.fct_id = fcct.fct_id
				JOIN customers c ON fcct.customer_id = c.customer_id
				JOIN (SELECT ct.customer_id FROM contracts ct
										  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
                                WHERE now()>=sr.start_date AND (ct.contract_stop IS NULL OR ct.contract_stop >= now())
                                GROUP BY ct.customer_id ) A on c.customer_id = A.customer_id
				$podJOIN
				WHERE fcto.option_name='max_months_before' AND fcto.value<3 AND c.customer_status='activ'";
			
			$customersIDs = $this->getValueArray($sql);
		}
		
		// Get result without any daily readings
		$result = [];
		$sql = "SELECT c.customer_name, 'Toate' as pod, 'Toate' as date, 96 as Intervals FROM customers c 
				LEFT JOIN customer_daily_readings_all cdra ON c.customer_id = cdra.customer_id AND cdra.year=$year AND cdra.month=$month 
				WHERE c.customer_id IN (".implode(',', $customersIDs).") AND cdra.id IS NULL";
		$result = $this->getArray($sql);
		
		//filter customers with daily readings
		$sql = "SELECT DISTINCT customer_id AS value FROM customer_daily_readings_all cdra WHERE cdra.customer_id IN (".implode(',', $customersIDs).") AND cdra.year=$year AND cdra.month=$month";
		$customersIDs = $this->getValueArray($sql);
		
		$sql = '';
		foreach($customersIDs as $customerID)
			$sql .= "SELECT MAX(day(reading_datetime)) as value FROM customer_daily_readings_$customerID WHERE MONTH(reading_datetime)=$month AND YEAR(reading_datetime) = $year UNION ";
		
		$currentMonth = date('n');
		$currentDay = date('j');
		if($sql)
		{
			$sql = "SELECT COALESCE(MAX(A.value),0) as value FROM (".substr($sql,0,-6).") A";		
			$maxDay = $this->getValue($sql);
			
			if($month == $month && $maxDay==$currentDay && $maxDay > 1)
				$maxDay--;
		}
		else 
			$maxDay = 0;
		
		// Get number of days in month
		$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

		if($maxDay > 0)
			$daysInMonth = min($maxDay, $daysInMonth);
		else
			$daysInMonth = 1;

		// Per-customer fast path: înlocuiește mega-UNION + LEFT JOIN cu month_days
		// (care făcea ~10M scanări de rând) cu 2 query-uri triviale per client:
		//   (1) lista de PODs   (2) counts per (pod, zi) cu range pe reading_datetime.
		// Sub-second per client; combinația "missing intervals" se calculează în PHP.

		$monthStart = sprintf('%04d-%02d-01', $year, $month);

		// Pre-fetch nume clienți o singură dată (înlocuiește subquery-ul per ramură).
		$customerNamesMap = [];
		if (count($customersIDs) > 0) {
			$nameRows = $this->getArray("SELECT customer_id, customer_name FROM customers
			                             WHERE customer_id IN (".implode(',', $customersIDs).")");
			foreach ($nameRows as $r)
				$customerNamesMap[$r['customer_id']] = $r['customer_name'];
		}

		foreach($customersIDs as $customerID)
		{
			$wPodSubq = '';   // pentru queryul cu lista de PODs
			$wPodCounts = ''; // pentru queryul cu counts (apare după WHERE range pe reading_datetime)

			if($pod)
			{
				$wPodSubq   = "WHERE pod = '$pod'";
				$wPodCounts = "AND pod = '$pod'";
			}
			elseif($distributorID != -1)
			{
				$wPodSubq   = "WHERE pod IN (SELECT pod_no FROM pods WHERE distributor_id = $distributorID)";
				$wPodCounts = "AND pod IN (SELECT pod_no FROM pods WHERE distributor_id = $distributorID)";
			}

			// (1) PODs cunoscute pentru acest client
			$pods = $this->getValueArray("
				SELECT DISTINCT pod FROM customer_daily_readings_{$customerID} $wPodSubq
				UNION
				SELECT DISTINCT pod FROM customer_far_{$customerID} $wPodSubq");

			if (empty($pods)) continue;

			// (2) Counts per (pod, zi) — folosește indexul (pod, reading_datetime) eficient
			$countRows = $this->getArray("
				SELECT pod, DATE(reading_datetime) AS d, COUNT(*) AS c
				FROM customer_daily_readings_{$customerID}
				WHERE reading_datetime >= '$monthStart'
				  AND reading_datetime <  DATE_ADD('$monthStart', INTERVAL 1 MONTH)
				  $wPodCounts
				GROUP BY pod, DATE(reading_datetime)");

			$existing = [];
			foreach ($countRows as $r)
				$existing[$r['pod']][$r['d']] = (int)$r['c'];

			$custName = $customerNamesMap[$customerID] ?? '';
			for ($day = 1; $day <= $daysInMonth; $day++) {
				$dStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
				foreach ($pods as $podName) {
					if (isset($existing[$podName][$dStr])) {
						$cnt = $existing[$podName][$dStr];
						if ($cnt == 96) continue;   // zi completă — sare peste
						$intervals = 96 - $cnt;
					} else {
						$intervals = 96;            // POD-ul nu are nicio citire în ziua asta
					}
					$result[] = [
						'customer_name' => $custName,
						'pod'           => $podName,
						'date'          => $dStr,
						'Intervals'     => $intervals,
					];
				}
			}
		}

		// Sortare identică cu ORDER BY A.date DESC, A.Intervals DESC din varianta veche
		usort($result, function($a, $b) {
			if ($a['date'] !== $b['date']) return strcmp($b['date'], $a['date']);
			return ((int)$b['Intervals']) - ((int)$a['Intervals']);
		});

		return $result;
	}
}