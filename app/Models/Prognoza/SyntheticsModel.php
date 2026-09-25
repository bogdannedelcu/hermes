<?php

namespace App\Models\Prognoza;

use CodeIgniter\Model;
use CodeIgniter\ConnectionInterface;

require_once(__DIR__.'/../CacheTools.php');
require_once(__DIR__.'/../MasterDataTools.php');

class SyntheticsModel extends Model
{
	use \MasterDataTools;
	use \CacheTools;
	protected $table      = 'forecast_temperatures';
	protected $db;
	
	function __construct()
	{
		$this->db = db_connect();
	}
	
	public function getSynthetics($month, $year, $customerID = null)
	{
		$supplierID = $_SESSION['select-supplier'];
		
		if($customerID == null && isset($_SESSION['select-customer']))
			$customerID = $_SESSION['select-customer'];
		
		if($customerID != null)
			$sql = "select * from forecast_synthetics fc where year(synthetics_datetime)=$year and month(synthetics_datetime)=$month and customer_id=$customerID and supplier_id=$supplierID order by hour(synthetics_datetime), day(synthetics_datetime)";
		else
			$sql = "select fs_id, supplier_id, 0 as customer_id, synthetics_datetime, sum(synthetic_ea) as synthetic_ea from forecast_synthetics fc where year(synthetics_datetime)=$year and month(synthetics_datetime)=$month and supplier_id=$supplierID group by hour(synthetics_datetime), day(synthetics_datetime) order by hour(synthetics_datetime), day(synthetics_datetime) ";
		
		log_message('error',$sql);
		return $this->getArray($sql);
	}
	
	public function getSyntheticsType($month, $year, $customerID = null)
	{
		$synthType = '';

		if($customerID == null && isset($_SESSION['select-customer']))
			$customerID = $_SESSION['select-customer'];

		if($customerID != null)
		{
			$sql = "select synthetic_type as value from forecast_synthetics_type fst where year=$year and month=$month and customer_id=$customerID";
			$synthType = $this->getValue($sql);
		}

		if(empty($synthType)) $synthType = 'absolut';

		return $synthType;
	}
	
	public function uploadValues($syntheticType, $customerID, $YMdate, $values)
	{
		$sqlData='';
		$sqlDeleteData='';
		
		$supplierID = $_SESSION['select-supplier'];
		$dt =  \DateTime::createFromFormat('Y-n-d',$YMdate.'-01');
		$YMdate = $dt->format('Y-m');
		$maxDays = cal_days_in_month(CAL_GREGORIAN,date('n',$dt->getTimestamp()),date('Y',$dt->getTimestamp()));
		
		$year = $dt->format('Y');
		$month = $dt->format('n');
		
		for($h=0;$h<24;$h++)
		{
			for($d = 1;$d<=$maxDays;$d++)
			{
				$dateTime = $YMdate.'-'.sprintf('%02d',$d).' '.sprintf('%02d',$h).':00:00';
				$ea = $values[$h][$d-1];
				
				if($ea!='')
					$sqlData.="($supplierID, $customerID, '$dateTime', $ea),";
				else
					$sqlDeleteData.="(supplier_id = $supplierID and customer_id = $customerID and synthetics_datetime = '$dateTime') or";
			}
		}
		
		$added = 0; $deleted = 0;
		if(!empty($sqlData)) 
		{
			$sqlData = substr_replace($sqlData,'',-1);
			$added = $this->importSQLValues($sqlData);
			
			$sql = "INSERT INTO forecast_synthetics_type (customer_id, year, month, synthetic_type) VALUES ($customerID, $year, $month, '$syntheticType')
					ON DUPLICATE KEY UPDATE synthetic_type = VALUES(synthetic_type)";
			$this->writeData($sql);
		}
		
		if(!empty($sqlDeleteData)) 
		{
			$sqlDeleteData = substr_replace($sqlDeleteData,'',-2);
			$deleted = $this->deleteSQLValues($sqlDeleteData);
						
			$sql = "DELETE FROM forecast_synthetics_type where customer_id = $customerID and year = $year and month = $month";
			$this->writeData($sql);
		}
				
		return 24*$maxDays." valori actualizate!";
	}
	
	public function importSQLValues($data)
	{
		$sql = "insert into forecast_synthetics (supplier_id, customer_id, synthetics_datetime, synthetic_ea) VALUES $data ON DUPLICATE KEY UPDATE synthetic_ea=VALUES(synthetic_ea)";
		log_message("error",$sql);
		return $this->writeData($sql);
	}
	
	public function deleteSQLValues($data)
	{
		$sql = "delete from forecast_synthetics where $data";
		log_message("error",$sql);
		return $this->writeData($sql);
	}
	
	public function getCustomersWithSynthetics($YMdate)
	{
		$dt = explode('-',$YMdate);
		$year = $dt[0];
		$month = $dt[1];
		
		$currentYear = date("Y");
		
		$sql = "SELECT * FROM
				(
				SELECT c.customer_id, c.customer_name, case when fc.synthetics_datetime IS NULL then 'Fara Sintetice' ELSE 'Cu Sintetice' END AS status FROM 
				customers c
				LEFT JOIN
				forecast_synthetics fc ON c.customer_id = fc.customer_id AND  (YEAR(fc.synthetics_datetime)=$year AND MONTH(fc.synthetics_datetime)=$month)
				where c.customer_status = 'Activ' 
				GROUP BY c.customer_id,YEAR(fc.synthetics_datetime),MONTH(fc.synthetics_datetime)
				) A
				JOIN (SELECT ct.customer_id, MIN(sr.start_date), MAX(ct.contract_stop) from contracts ct
										  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
                                WHERE (ct.contract_stop IS NULL OR DATE_SUB(ct.contract_stop, INTERVAL 1 YEAR) >= '$year-$month-01')
                                GROUP BY ct.customer_id ) B ON A.customer_id = B.customer_id
				ORDER by A.status DESC, A.customer_name";
		log_message("error",$sql);
		$result['data'] = $this->getArray($sql);
		$result['total'] = count($result['data']);//$query->countAllResults();
		
		return $result;
	}
	
	public function generateCurveForCustomer($source_customer,$dest_customer,$start_year,$start_month,$stop_year,$stop_month,$dest_start_year,$dest_start_month,$dest_stop_year,$dest_stop_month,$toBeValue,$valueType,$syntheticType,$dayCorrelation)
	{
		$supplierID = $_SESSION['select-supplier'];
		$nrModif = 0;
		
		$start = strtotime("$start_year-$start_month-01");
		$end = strtotime("$stop_year-$stop_month-01");
		
		$dest_start = strtotime("$dest_start_year-$dest_start_month-01");
		$dest_end = strtotime("$dest_stop_year-$dest_stop_month-01");
		
		//log_message('error','dest_start:'.$dest_start);
		//log_message('error','start:'.$start);
			
		$diff_days = ceil(abs($dest_start - $start)/86400) * (($dest_start > $start ? 1 : -1));
		
		if($dayCorrelation)
		{
			$idw = date('w', $start);
			$dest_idw = date('w', $dest_start);
			$diff_idw = $dest_idw - $idw;		
			//log_message('error','diff_idw:'.$diff_idw);
		}
		else		
			$diff_idw = 0;
		//log_message('error','diff_days:'.$diff_days);
		
		if($valueType == 'Percent')
			$newValue = "$toBeValue/100";
		else
			$newValue = "1";
		
		while($start <= $end)
		{
			$month = date('n',$start);
			$year = date('Y',$start);

			$dest_month = date('n',$dest_start);
			$dest_year = date('Y',$dest_start);
			
			$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
			$dest_daysInMonth = cal_days_in_month(CAL_GREGORIAN, $dest_month, $dest_year);
			
			$extraLook = '';
							
			if($diff_days == 0) 
			{
				//$destDate = "concat($dest_year,'-',$dest_month,'-',DATE_FORMAT(far_datetime,'%d %H:%i:%s'))";
				$destDate = 'far_datetime';
			}
			else
			{
				if($diff_days > 0) //from past to future
				{
					$destFarDateTime = "DATE_ADD(far_datetime, INTERVAL $diff_days DAY)";
				}
				elseif($diff_days < 0) //from future to past
				{
					$destFarDateTime = "DATE_SUB(far_datetime, INTERVAL ".abs($diff_days)." DAY)";
				}
				
				if ($diff_idw == 0) $destDate = $destFarDateTime;
				elseif ($diff_idw <= -4) {$destDate = "DATE_SUB($destFarDateTime, INTERVAL ".(7+$diff_idw)." DAY)";$extraLook='fwd';} //ok
				elseif ($diff_idw <= -1) {$destDate = "DATE_ADD($destFarDateTime, INTERVAL ".abs($diff_idw)." DAY)";$extraLook='back';} //ok
				elseif ($diff_idw >= 4) {$destDate = "DATE_ADD($destFarDateTime, INTERVAL ".(7-$diff_idw)." DAY)";$extraLook='back';} //ok
				elseif ($diff_idw >= 1) {$destDate = "DATE_SUB($destFarDateTime, INTERVAL ".$diff_idw." DAY)";$extraLook='fwd';} //ok
			}
			
			$sql = "DELETE FROM forecast_synthetics where customer_id = $dest_customer and year(synthetics_datetime) = $dest_year and month(synthetics_datetime) = $dest_month";
			$this->writeData($sql);
			
			$sql = "INSERT INTO forecast_synthetics (supplier_id, customer_id, synthetics_datetime, synthetic_ea)
				SELECT supplier_id, $dest_customer, $destDate, 
				sum(far_ea) * $newValue
				FROM forecast_actual_readings_$month 
				WHERE customer_id = $source_customer AND supplier_id = {$supplierID} and year(far_datetime) = $year and month($destDate)=$dest_month
				GROUP BY supplier_id, customer_id, far_datetime
				ON DUPLICATE KEY UPDATE synthetic_ea = VALUES(synthetic_ea)";
			$nrModif += $this->writeData($sql);
			
			if($extraLook=='back')
			{
				if($month == 1) $oMonth = 12;
				else $oMonth = $month-1;
				
				$sql = "INSERT INTO forecast_synthetics (supplier_id, customer_id, synthetics_datetime, synthetic_ea)
					SELECT supplier_id, $dest_customer, $destDate, 
					sum(far_ea) * $newValue
					FROM forecast_actual_readings_$oMonth 
					WHERE customer_id = $source_customer AND supplier_id = {$supplierID} and year(far_datetime) = $year and month($destDate)=$dest_month
					GROUP BY supplier_id, customer_id, far_datetime
					ON DUPLICATE KEY UPDATE synthetic_ea = VALUES(synthetic_ea)";
				$nrModif += $this->writeData($sql);
			}
			
			if($extraLook=='fwd' || $month == 2)
			{
				if($month == 12) $oMonth = 1;
				else $oMonth = $month+1;
				
				$sql = "INSERT INTO forecast_synthetics (supplier_id, customer_id, synthetics_datetime, synthetic_ea)
					SELECT supplier_id, $dest_customer, $destDate, 
					sum(far_ea) * $newValue
					FROM forecast_actual_readings_$oMonth 
					WHERE customer_id = $source_customer AND supplier_id = {$supplierID} and year(far_datetime) = $year and month($destDate)=$dest_month
					GROUP BY supplier_id, customer_id, far_datetime
					ON DUPLICATE KEY UPDATE synthetic_ea = VALUES(synthetic_ea)";
				$nrModif += $this->writeData($sql);
			}	
			
			$sql = "INSERT INTO forecast_synthetics_type (customer_id, year, month, synthetic_type) VALUES ($dest_customer, $dest_year, $dest_month, '$syntheticType')
			ON DUPLICATE KEY UPDATE synthetic_type = VALUES(synthetic_type)";
			$this->writeData($sql);
			
			//compute absolute values
			if($valueType != 'Percent')
			{
				$sql = "UPDATE forecast_synthetics fs 
						SET fs.synthetic_ea = fs.synthetic_ea*(SELECT $toBeValue/sum(synthetic_ea) FROM forecast_synthetics WHERE supplier_id = {$supplierID} AND customer_id = $source_customer AND year(synthetics_datetime) = {$dest_year} and month(synthetics_datetime) =  {$dest_month} GROUP BY supplier_id, customer_id)
						WHERE fs.customer_id = $dest_customer AND fs.supplier_id = {$supplierID} AND year(fs.synthetics_datetime) = {$dest_year} AND month(fs.synthetics_datetime) = {$dest_month}";
				$this->writeData($sql);
			}
			
			$start = strtotime("+1 month", $start);
			$dest_start = strtotime("+1 month", $dest_start);
			}
		
		/*else //start_date eq stop_date
		{
			
			$start = strtotime("$start_year-$start_month-01");
			$month = date('n',$start);
			$year = date('Y',$start);

			$dest_start = strtotime("$dest_start_year-$dest_start_month-01");
			$dest_month = date('n',$dest_start);
			$dest_year = date('Y',$dest_start);
			
			$destDate = "concat($dest_year,'-',$dest_month,'-',DATE_FORMAT(far_datetime,'%d %H:%i:%s'))";
			
			$sql = "DELETE FROM forecast_synthetics where customer_id = $dest_customer and year(synthetics_datetime) = $dest_year and month(synthetics_datetime) = $dest_month";
			$this->writeData($sql);
				
			$sql = "INSERT INTO forecast_synthetics (supplier_id, customer_id, synthetics_datetime, synthetic_ea)
					SELECT supplier_id, $dest_customer, $destDate, 
					sum(far_ea) * (SELECT $toBeValue/sum(far_ea) FROM forecast_actual_readings_$start_month WHERE supplier_id = {$supplierID} AND customer_id = $source_customer AND year(far_datetime) = $start_year GROUP BY supplier_id, customer_id)
					FROM forecast_actual_readings_$start_month 
					WHERE customer_id = $source_customer AND supplier_id = {$supplierID} and year(far_datetime) = $start_year and month($destDate)=$dest_month
					GROUP BY supplier_id, customer_id, far_datetime
					ON DUPLICATE KEY UPDATE synthetic_ea = VALUES(synthetic_ea)";
			$nrModif = $this->writeData($sql);
		
			$sql = "INSERT INTO forecast_synthetics_type (customer_id, year, month, synthetic_type) VALUES ($dest_customer, $dest_year, $dest_month, '$syntheticType')
				ON DUPLICATE KEY UPDATE synthetic_type = VALUES(synthetic_type)";
			$this->writeData($sql);
		}*/
				
		//cleanup
		$this->writeData("DELETE FROM forecast_synthetics WHERE synthetics_datetime = '0000-00-00 00:00:00'");
		return $nrModif.' valori adaugate/actualizate';
	}
}