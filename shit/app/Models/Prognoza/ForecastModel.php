<?php

namespace App\Models\Prognoza;

use CodeIgniter\Model;
use CodeIgniter\ConnectionInterface;

require_once(__DIR__.'/../CacheTools.php');
require_once(__DIR__.'/../MasterDataTools.php');

define("corelare_data", 0);
define("corelare_zi_saptamana", 1);

class ForecastModel extends Model
{
	use \MasterDataTools;
	use \CacheTools;
	
	protected $table      = 'forecast_consumption_types';
	protected $db;
	
	protected $supplierID;
	
	public $errorCollection = [];
	protected $rawDataCounter;
	
	protected $regionError = [];
	
	protected $correlatedMonths;
	
	function __construct()
	{
		$this->db = db_connect();
		
		$this->supplierID = $_SESSION['select-supplier'];
	}
	
	public function setConsumptioTypeOptions($data)
	{
		
		if(isset($data->fct_id))
		{
			$sql = 'delete from forecast_consumption_types_options where fct_id = '.$data->fct_id;
			$this->writeData($sql);
		}

		$sql = 'INSERT INTO forecast_consumption_types_options (fct_id, option_name, value) VALUES 
															   ('.$data->fct_id.',"simple",'.(int)$data->simple.'), 
															   ('.$data->fct_id.',"last_day",'.(int)$data->last_day.'),
															   ('.$data->fct_id.',"intervalZ",'.(int)$data->intervalZ.'),
															   ('.$data->fct_id.',"intervalN",'.(int)$data->intervalN.'),
															   ('.$data->fct_id.',"manual",'.(int)$data->manual.'), 
															   ('.$data->fct_id.',"temperature",'.(int)$data->temperature.'), 
															   ('.$data->fct_id.',"temperature_source",'.$this->db->escape($data->temperature_source).'),
															   ('.$data->fct_id.',"temperature_margin",'.(float)$data->temperature_margin.'),
															   ('.$data->fct_id.',"temperature_margin_max",'.(float)$data->temperature_margin_max.'),
															   ('.$data->fct_id.',"temperature_selected_value_type",'.$this->db->escape($data->temperature_selected_value_type).'),
															   ('.$data->fct_id.',"days_margin",'.(float)$data->days_margin.'),
															   ('.$data->fct_id.',"days_margin_max",'.(float)$data->days_margin_max.'),
															   ('.$data->fct_id.',"last_dayT",'.(int)$data->last_dayT.'),
															   ('.$data->fct_id.',"prosumator",'.(int)$data->prosumator.'), 
															   ('.$data->fct_id.',"prod_margin",'.(float)$data->prod_margin.'),
															   ('.$data->fct_id.',"prod_margin_max",'.(float)$data->prod_margin_max.'),
															   ('.$data->fct_id.',"prod_days_margin",'.(float)$data->prod_days_margin.'),
															   ('.$data->fct_id.',"prod_days_margin_max",'.(float)$data->prod_days_margin_max.'),
															   ('.$data->fct_id.',"prod_alt_estimate",'.(float)$data->prod_alt_estimate.'),
															   ('.$data->fct_id.',"prod_estimation_type","'.$data->prod_estimation_type.'"),
															   ('.$data->fct_id.',"outliers",'.(int)$data->outliers.'),
															   ('.$data->fct_id.',"outliers_radius",'.(int)$data->outliers_radius.'),
															   ('.$data->fct_id.',"outliers_size",'.(float)$data->outliers_size.'),
															   ('.$data->fct_id.',"outliers_interval","'.$data->outliers_interval.'"),
															   ('.$data->fct_id.',"max_months_before","'.$data->max_months_before.'"),
															   ('.$data->fct_id.',"ignore_synthetics","'.$data->ignore_synthetics.'"),
															   ('.$data->fct_id.',"nc_temperature_margin",'.(float)$data->nc_temperature_margin.'),
															   ('.$data->fct_id.',"nc_interval_marginZ",'.(float)$data->nc_interval_marginZ.'),
															   ('.$data->fct_id.',"nc_selected_value_type","'.$data->nc_selected_value_type.'"),
															   ('.$data->fct_id.',"nc_interval_marginN",'.(float)$data->nc_interval_marginN.')';
		return $this->writeData($sql);
	}
	
	public function getCustomersWhithoutOptions()
	{
		$sql = "SELECT c.customer_name AS value FROM customers c
				JOIN contracts ct ON c.customer_id = ct.customer_id AND c.customer_status = 'Activ' AND (ct.contract_stop IS NULL OR ct.contract_stop > CURDATE())
				LEFT JOIN forecast_customers_consumption_types fcct ON fcct.customer_id = c.customer_id
				WHERE fcct.fcct_id IS null
				GROUP by c.customer_id";
		return $this->getValueArray($sql);
	}
	
	private function getNumberOfDays($eStart, $eEnd)
	{
		$dStart = \DateTime::createFromFormat('Y-m-d',$eStart);
		$dEnd = \DateTime::createFromFormat('Y-m-d',$eEnd);
		return $dEnd->diff($dStart)->days + 1;
	}
	
	public function getFilterConsumptionTypes()
	{
		return $this->getArray('SELECT fct_id AS value ,consumption_type_name AS text FROM forecast_consumption_types  WHERE supplier_id=1 ORDER BY `order`');
	}
	
	//replace getFilterConsumptionTypes
	public function getFilterEstimationTypes()
	{
		return $this->getValueArray('SELECT consumption_type_name AS value FROM forecast_consumption_types  WHERE supplier_id=1 ORDER BY `order`');
	}
	
	public function getCustomersWithConsumptionTypes($month, $year)
	{
		$sql = "SELECT c.customer_id,c.customer_name,concat(fct.order,' ',fct.consumption_type_name) as consumption_type_name FROM customers c
				JOIN  (SELECT ct.customer_id, MIN(ct.contract_date), MAX(ct.contract_stop) from contracts ct
				WHERE LAST_DAY('$year-$month-01')>=ct.contract_date AND (ct.contract_stop IS NULL OR ct.contract_stop >= DATE('$year-$month-01'))
				GROUP BY ct.customer_id ) B ON c.customer_id = B.customer_id
				LEFT JOIN forecast_customers_consumption_types fcct ON fcct.customer_id = c.customer_id 
				LEFT JOIN forecast_consumption_types fct ON (fct.fct_id = fcct.fct_id AND fcct.pod='') OR (fcct.fcct_id IS NULL AND fct.consumption_type_name='General')
				WHERE c.customer_status='activ'
				GROUP BY c.customer_id
				ORDER BY fct.order, c.customer_name";
		
		$result['data'] = $this->getArray($sql);
		$result['total'] = count($result['data']);//$query->countAllResults();
		
		return $result;
	}
	
	public function uploadMultiValues($modifiedData, $YMdate, $startDate,$endDate)
	{	
		$supplierID = $this->supplierID;
		$customersList = "('".implode("','",$modifiedData->customers_name)."')";
		$begin = \DateTime::createFromFormat('Y-m-d',$startDate);
		$end = \DateTime::createFromFormat('Y-m-d',$endDate);
		$ret = 0;
		
		for($idx=0;$idx<$modifiedData->length;$idx++)
		{
			$c = $modifiedData->customers_name[$idx];
			$customerID = $this->getValue("select customer_id as value from customers where customer_name='$c'");
			$dt = \DateTime::createFromFormat('Y-m-d',$startDate)->modify($modifiedData->day_interval[$idx].' day')->format('Y-m-d');
			
			$sql = 'INSERT INTO forecast_estimates (supplier_id, customer_id, forecast_datetime, forecast_ea) VALUES';
			
			$removeFromManualMode = true;
			for($h=0;$h<24;$h++)
			{
				if(is_numeric($modifiedData->values[$idx][$h]))	
				{
					$ea = $modifiedData->values[$idx][$h];
					
					for($m = 0;$m<=45;$m+=15)
					{
						$dthm = $dt.' '.str_pad($h, 2, '0', STR_PAD_LEFT).':'.str_pad($m, 2, '0', STR_PAD_LEFT).':00';
						$sql .= " ($supplierID, $customerID, '$dthm',$ea/4),";
					}
					
					$removeFromManualMode = false;
				}	
			}
			
			if($removeFromManualMode)
			{
				$this->writeData('DELETE FROM forecast_customers_manual_estimates WHERE supplier_id = '.$this->supplierID.' AND customer_id = '.$customerID.' AND date="'.$dt.'"');
				$this->writeData('DELETE FROM forecast_estimates WHERE supplier_id = '.$this->supplierID.' AND customer_id = '.$customerID.' AND date(forecast_datetime)="'.$dt.'"');
			}
			else
			{
				$this->writeData('insert ignore into forecast_customers_manual_estimates (supplier_id, customer_id, date) VALUES ('.$this->supplierID.','.$customerID.',"'.$dt.'")');
				$sql = substr_replace($sql ,"",-1) . ' ON DUPLICATE KEY UPDATE forecast_ea=VALUES(forecast_ea)';
				$ret += $this->writeData($sql);				
			}
		}	
		
		return ($modifiedData->length * 24) . " valori actualizate!";
	}
	
	public function uploadValues($customerID, $YMdate, $values, $startDate, $endDate, $partial)
	{
		$sqlData='';
		$sqlDeleteData='';
		
		$supplierID = $this->supplierID;
		$dt =  \DateTime::createFromFormat('!Y-n',$YMdate);
		$YMdate = $dt->format('Y-m');		
		
		if($partial)
		{
			$sd = explode('-',$startDate)[2];
			$ed = explode('-',$endDate)[2];
			$maxDays = $ed;
		}
		else
		{			
			$sd = 1;
			$maxDays = cal_days_in_month(CAL_GREGORIAN,date('n',$dt->getTimestamp()),date('Y',$dt->getTimestamp()));
		}
		
		$now = new \DateTime();
		
		$actualizate = 0; $deletedDays=[]; $addedDays=[];
		for($h=0;$h<24;$h++)
		{
			for($d = $sd;$d<=$maxDays;$d++)
			{
				if(in_array($d, $deletedDays)) continue;
				$dateTime = $YMdate.'-'.sprintf('%02d',$d).' '.sprintf('%02d',$h).':00:00';
				
				$fdt = \DateTime::createFromFormat('Y-m-d H:i:s',$dateTime);
				if($fdt<=$now) continue;
				
				$actualizate++;
				$ea = $values[$h][$d-$sd];
				
				if(is_numeric($ea))
				{
					for($m = 0;$m<=45;$m+=15)
					{
						$dthm = $YMdate.'-'.sprintf('%02d',$d).' '.str_pad($h, 2, '0', STR_PAD_LEFT).':'.str_pad($m, 2, '0', STR_PAD_LEFT).':00';
						$sqlData .= " ($supplierID, $customerID, '$dthm',$ea/4),";
					}
					
					if(!in_array($d,$addedDays))
					{
						$this->writeData('INSERT IGNORE INTO forecast_customers_manual_estimates (supplier_id, customer_id, date) VALUES ('.$this->supplierID.','.$customerID.',"'.$YMdate.'-'.sprintf('%02d',$d).'")');
						$addedDays[]=$d;
					}
				}
				else
				{
					if (!in_array($d, $deletedDays)) {
						$deletedDays[] = $d;;
					}
				}
			}
		}
		
		foreach($deletedDays as $d)
		{
			$this->writeData('DELETE FROM forecast_customers_manual_estimates WHERE supplier_id = '.$this->supplierID.' AND customer_id = '.$customerID.' AND date="'.$YMdate.'-'.sprintf('%02d',$d).'"');
			$this->writeData('DELETE FROM forecast_estimates WHERE supplier_id = '.$this->supplierID.' AND customer_id = '.$customerID.' AND date(forecast_datetime)="'.$YMdate.'-'.sprintf('%02d',$d).'"');
		}
		
		$ret = 0;
		if(!empty($sqlData)) 
		{
			$sqlData = substr_replace($sqlData,'',-1);
			$ret += $this->importSQLValues($sqlData);
		}
		
		return $actualizate ." valori actualizate!";
	}
	
	public function importSQLValues($data)
	{
		$sql = "insert into forecast_estimates (supplier_id, customer_id, forecast_datetime, forecast_ea) VALUES $data ON DUPLICATE KEY UPDATE forecast_ea=VALUES(forecast_ea)";

		return $this->writeData($sql);
	}
	
	public function deleteSQLValues($data)
	{
		$sql = "delete from forecast_estimates where $data";

		return $this->writeData($sql);
	}
	
	public function readEstimates($month, $year, $customers, $consumptionType,$intervalType)
	{
		$supplierID = $this->supplierID;
		$customerIDs = implode(',', array_map('intval', $customers));
		
		$cWhere="";
		if($consumptionType > 0)
		{
			$cWhere = "AND (fc.customer_id IN (SELECT fcct.customer_id FROM forecast_customers_consumption_types fcct where fcct.fct_id = $consumptionType)
							OR (fc.customer_id NOT IN (SELECT fcct.customer_id FROM forecast_customers_consumption_types fcct where fcct.fct_id = $consumptionType) AND $consumptionType = 2)
							)";
		}
		
		if($intervalType == "15min")
		{
			$dateGroup = "forecast_datetime";
			$minOrder = "MINUTE(forecast_datetime),";
		}
		else
		{
			$dateGroup = "HOUR(forecast_datetime), DAY(forecast_datetime)";
			$minOrder = "";
		}
		
		if(!empty($customers))
		{
			$sql = "SELECT DAY(fc.forecast_datetime) as day, HOUR(fc.forecast_datetime) as hour, minute(fc.forecast_datetime) as minute, SUM(forecast_ea) AS forecast_ea FROM forecast_estimates fc 
					WHERE year(forecast_datetime)=$year AND MONTH(forecast_datetime)=$month AND customer_id IN ($customerIDs) AND supplier_id=$supplierID 
					GROUP BY $dateGroup ORDER BY HOUR(forecast_datetime), $minOrder DAY(forecast_datetime)";
		}
		else
			$sql = "select day(fc.forecast_datetime) as day, hour(fc.forecast_datetime) as hour ,minute(fc.forecast_datetime) as minute, sum(forecast_ea) as forecast_ea from forecast_estimates fc  
			 JOIN (SELECT ct.customer_id, MIN(sr.start_date), MAX(ct.contract_stop) from contracts ct
				  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
				  WHERE '$year-$month-01'>=sr.start_date AND (ct.contract_stop IS NULL OR ct.contract_stop >= '$year-$month-01')
			      GROUP BY ct.customer_id ) A ON A.customer_id = fc.customer_id
				  where year(forecast_datetime)=$year and month(forecast_datetime)=$month and supplier_id=$supplierID
				  $cWhere
			group by $dateGroup order by hour(forecast_datetime), $minOrder day(forecast_datetime)";
		
		return $this->getArray($sql);
	}
	
	private function isManualEstimate($customerID, $date)
	{
		$sql = "SELECT coalesce(fcme_id,0)>0 as value from forecast_customers_manual_estimates where customer_id = $customerID and date = '$date'";
		return $this->getValue($sql);
	}
	
	public function readEstimatesHistory2($eStart, $eEnd, $customers)
	{
		$supplierID = $this->supplierID;
		if(empty($customers) || count($customers)>1) return [];//empty set
		
		$m = \DateTime::createFromFormat('Y-m-d',$eStart)->format('n');
		
		$customerID = (int)$customers[0];

		$sql = "SELECT c.customer_name, fct.consumption_type_name FROM customers c
				LEFT JOIN forecast_customers_consumption_types fcct ON fcct.customer_id = c.customer_id
				LEFT JOIN forecast_consumption_types fct ON fcct.fct_id = fct.fct_id OR (fcct.fct_id IS NULL AND fct.consumption_type_name = 'General')
				WHERE c.customer_id = $customerID";

		$result = $this->getRow($sql);
		
		$ret['customer_name'] = $result['customer_name'];
		$ret['consumption_type'] = $result['consumption_type_name'];

		$this->prepareEATables($eStart, $eEnd, $customers);
		$this->prepareTempTable($eStart, $eEnd, $customers);
		
		$estimationOptions = $this->getEstimationOptions($customerID, $eStart, $eEnd , false);
		//$hv = $this->selectHistoryValues($eStart, $eEnd, $estimationOptions, '',true);
		
		if($estimationOptions->manual)
			$estimationOptions = $this->assignEstimateToCustomer('General',$customerID, $eStart, $eEnd,false);

			$ret['history']=[];
			$ret['history_interval']=[];
			$ret['history_type'] = '';
			
			$pods = $this->getCustomersPODs($customerID);
			
			$exclPODs='';
			foreach($pods as $pod)
			{
				
				if(isset($estimationOptions->{$pod}))
				{
					$hv = $this->selectHistoryValues($eStart, $eEnd, $estimationOptions->{$pod}, '', true);
										
					if(gettype($hv) =='array')
					{
						$this->insertEstimatedPODValues($hv['estimatedSQLValues'],true);
						$this->insertHistoryValues($hv['historySQLValues']);
					}
					else
						continue;
					
					$exclPODs.="'$pod',";
				}  
			}
			
			$exclPODs = rtrim($exclPODs, ',');
			
			$hv = $this->selectHistoryValues($eStart, $eEnd, $estimationOptions, $exclPODs,true);
			
		if(gettype($hv) =='array')
		{
			$this->insertEstimatedPODValues($hv['estimatedSQLValues'],true);
			$this->insertHistoryValues($hv['historySQLValues']);
			
			$ret['history_type'] = $hv['historyType'];
		}

		$this->filterEstimatedPODValues($eStart, $eEnd, true);		
		$ret['history']=$this->getArray("SELECT far_datetime, sum(far_ea) as far_ea FROM forecast_temp_history fh group by date(fh.far_datetime), hour(fh.far_datetime)");
		
		$ret['history_interval']=$this->getArray("SELECT date(far.far_datetime) as date, sum(far.far_ea) as ea FROM customer_far_$customerID far WHERE date(far.far_datetime) IN ( SELECT DISTINCT date(far_datetime) from forecast_temp_history ) group by date(far.far_datetime) ORDER BY date(far.far_datetime)");

		$ret['pods'] = $this->getValueArray("SELECT distinct(pod) as value FROM forecast_temp_pods_estimates where pod <> '' order by pod");
		foreach($ret['pods'] as $p)
		{
			$ret[$p]['estimates'] = $this->getArray("SELECT forecast_datetime, estimation_type, sum(forecast_ea) as forecast_ea FROM forecast_temp_pods_estimates fh where pod='$p' group by date(fh.forecast_datetime), hour(fh.forecast_datetime)");
			$ret[$p]['county'] = $this->getValue("SELECT county as value from pods where pod_no = '$p'");
			
			$podEstimationOptions = $this->getEstimationOptions($customerID, $eStart, $eEnd , false);
			$podEstimationOptions->pod = $p;
			
			if($podEstimationOptions->manual)
				$podEstimationOptions = $this->assignEstimateToCustomer('General',$customerID, $eStart, $eEnd,false);
			
			$hvPOD = $this->selectHistoryValues($eStart, $eEnd, $podEstimationOptions, '', true);
			
			if(!empty($hvPOD))
			{
				$ret[$p]['history'] = $this->getArray("SELECT * FROM ({$hvPOD['historySQLValues']}) X");
				$ret[$p]['history_interval']=$this->getArray("SELECT date(far.far_datetime) as date, sum(far.far_ea) as ea FROM customer_far_$customerID far 
				WHERE far.pod = '$p' AND date(far.far_datetime) IN ( SELECT distinct date(X.far_datetime) FROM ({$hvPOD['historySQLValues']}) X )
				GROUP BY date(far.far_datetime)");
				/*$ret[$p]['history_interval']=$this->getArray("SELECT date(X.far_datetime) as date, coalesce(sum(far.far_ea), sum(X.far_ea)) as ea FROM ({$hvPOD['historySQLValues']}) X 
															  LEFT JOIN forecast_aggregate_actual_readings far ON date(X.far_datetime) = date(far.far_datetime) AND far.pod = '$p' 
															  group by date(X.far_datetime)");*/
			}
			else
			{
				$ret[$p]['history'] = [];
				$ret[$p]['history_interval']=[];
			}
		
		}
		
		$this->insertEstimatedValues($eStart, $eEnd, true);	
		$sql = "select fc.forecast_datetime, sum(coalesce(forecast_ea,0)) as forecast_ea from forecast_estimates fc where CAST(forecast_datetime AS DATE) BETWEEN ".$this->db->escape($eStart)." AND ".$this->db->escape($eEnd)." AND customer_id = $customerID AND supplier_id=$supplierID group by hour(forecast_datetime), day(forecast_datetime)  order by hour(forecast_datetime), day(forecast_datetime)";
		$ret['estimates']=$this->getArray($sql);
		
		$ret['manual_estimate'] = $estimationOptions->manual || $this->isManualEstimate($customerID, $eStart);

		
		return $ret;
	}
	
	public function readAllEstimates($eStart, $eEnd, $customers,$consumptionTypeID, $intervalType)
	{
		$supplierID = $this->supplierID;
		$customerIDs = implode(',', array_map('intval', $customers));
		
		$generalID = $this->getValue("SELECT fct_id as value FROM forecast_consumption_types WHERE consumption_type_name = 'General' AND supplier_id = $supplierID");
		
		$cWhr = '';
		if($consumptionTypeID == $generalID)
		{
			$joinCT = ' LEFT JOIN forecast_customers_consumption_types fcct on (fcct.customer_id = c.customer_id OR fcct.fct_id IS NULL) ';
			$cWhr = " AND ( fcct.fct_id = $consumptionTypeID OR fcct.fct_id IS NULL) ";
		}
		else if($consumptionTypeID != -1)
			$joinCT = ' JOIN forecast_customers_consumption_types fcct on fcct.customer_id = c.customer_id and fcct.fct_id = '.$consumptionTypeID;
		else
			$joinCT = '';
		
		
		if(!empty($customerIDs))
		{
			$cWhr .= " AND c.customer_id IN ($customerIDs)";
		}
		
		
		if($intervalType == "15min")
		{
			$dateGroup = "fe.forecast_datetime";
			$minOrder = "MINUTE(fe.forecast_datetime),";
		}
		else
		{
			$dateGroup = "DATE(fe.forecast_datetime), HOUR(fe.forecast_datetime)";
			$minOrder = "";
		}	
		
		
		$farStart = \DateTime::createFromFormat('Y-m-d',$eStart)->format('Y-m-d');
		$farEnd = \DateTime::createFromFormat('Y-m-d',$eEnd)->format('Y-m-d');
		$intervalSize = \DateTime::createFromFormat('Y-m-d',$farStart)->diff(\DateTime::createFromFormat('Y-m-d',$farEnd))->format("%a") + 1;
		
		$sql = "SELECT c.customer_id, c.customer_name,fe.forecast_datetime, sum(fe.forecast_ea) as forecast_ea FROM customers c
				JOIN forecast_view_customer_order vco on c.customer_id = vco.customer_id
			    JOIN (SELECT ct.customer_id, MIN(sr.start_date), MAX(ct.contract_stop) from contracts ct
				  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
				  WHERE '$eStart'>=sr.start_date AND (ct.contract_stop IS NULL OR ct.contract_stop >= '$eStart')
			      GROUP BY ct.customer_id ) A ON A.customer_id = c.customer_id
				LEFT JOIN forecast_estimates fe ON fe.customer_id = c.customer_id AND DATE(fe.forecast_datetime) BETWEEN '$eStart' AND '$eEnd'
				$joinCT
				WHERE c.customer_status = 'Activ' AND c.supplier_id = $supplierID $cWhr
				GROUP BY c.customer_id, $dateGroup
				ORDER BY HOUR(fe.forecast_datetime),{$minOrder}vco.fctOrder,c.customer_name, DAY(fe.forecast_datetime)";
		
		$ret['data'] = $this->getArray($sql);
		
		$sql = "SELECT c.customer_id as value FROM customers c 
		JOIN forecast_view_customer_order vco on c.customer_id = vco.customer_id
		JOIN (SELECT ct.customer_id, MIN(sr.start_date), MAX(ct.contract_stop) from contracts ct
				  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
				  WHERE '$eStart'>=sr.start_date AND (ct.contract_stop IS NULL OR ct.contract_stop >= '$eStart')
				  GROUP BY ct.customer_id ) A ON A.customer_id = c.customer_id
		$joinCT
		WHERE c.supplier_id = $supplierID  AND c.customer_status = 'Activ' $cWhr
		order by vco.fctOrder,c.customer_name";
		
		$ret['intervalSize'] = $intervalSize;
		$ret['customers'] = $this->getValueArray($sql);
		return $ret;
	}
	
	public function convertTo15()
	{
	}
	
	private function getForecastOption($customerID,$option)
	{
		$supplierID = $this->supplierID;
		$sql = "SELECT fcto.value FROM forecast_consumption_types_options fcto JOIN 
				( 
					SELECT c.customer_id, coalesce(fct.fct_id,(SELECT f.fct_id FROM forecast_consumption_types f WHERE f.consumption_type_name = 'General' AND f.supplier_id = $supplierID)) as fct_id FROM customers c 
					LEFT JOIN forecast_customers_consumption_types fcct ON c.customer_id = fcct.customer_id
					LEFT JOIN forecast_consumption_types fct ON fcct.fct_id = fct.fct_id and fct.supplier_id = 1
					WHERE c.supplier_id = $supplierID AND c.customer_id=".$this->db->escape($customerID)."
				) A
				ON fcto.fct_id = A.fct_id AND option_name=".$this->db->escape($option);

		return $this->getValue($sql);
	}
	
	private function getExcludedDays($estimationOptions, $field = 'tdates.dt')
	{
		if(count($estimationOptions->excludedDays) > 1)
			return " AND date($field) NOT IN ('".implode("','",$estimationOptions->excludedDays)."')";
		if(count($estimationOptions->excludedDays) == 1)
			return " AND date($field) <> '".$estimationOptions->excludedDays[0]."'";
		
		return "";
	}
	
	private function getExcludedHours($estimationOptions, $field = 'tdates.datetime')
	{
		if(isset($estimationOptions->hourly_interval))
			return " AND HOUR($field) IN ({$estimationOptions->hourly_interval})";
		else
			return "";
	}
	
	private function getDateRangeWhere($customerID, $supplierID, $eStart, $eEnd, $marginDays, $format = 'all')
	{
		//todo migration?
		if($format == 'synthetics' || $format == 'lastYear')
		{
			$minY = \DateTime::createFromFormat('Y-m-d',$eStart)->modify("-1 year")->modify("-$marginDays days")->format("Y");
			$maxY = \DateTime::createFromFormat('Y-m-d',$eEnd)->modify("-1 year")->modify("$marginDays days")->format("Y");
		}
		else
		{
			$minMaxYears = $this->getArray("SELECT MIN(year(far.far_datetime)) as minY, Max(year(far.far_datetime)) as maxY FROM customer_far_$customerID far");
		
			if(empty($minMaxYears[0]['minY']))
				log_message('info',"Nu au date istorice agregate: ".$customerID);
			
			$minY = $minMaxYears[0]['minY'] ?? (date("Y")-2);
			$maxY = $minMaxYears[0]['maxY'] ?? date("Y");
		}	
		/*do not use future intervals
		$eMaxY = (int)substr($eEnd,0,4);
		if($maxY >= $eMaxY) $maxY = $eMaxY-1;
		if($minY > $maxY) $minY = $maxY;
		*/
		
		$rangeWhere = '';
		
		$dmStart = substr($eStart,4);
		$dmEnd = substr($eEnd,4);
		
		//$maxDate = \DateTime::createFromFormat('Y-m-d',$eStart)->modify("-1 days");
				
		for($y = $minY; $y<=$maxY; $y++)
		{
			$farStartDate = \DateTime::createFromFormat('Y-m-d',$y.$dmStart)->modify("-$marginDays days");
			//if($farStartDate > $maxDate) break;
			
			$farEndDate = \DateTime::createFromFormat('Y-m-d',$y.$dmEnd)->modify("$marginDays days");
			//if($farEndDate > $maxDate) $farEndDate = $maxDate;
			
			$farStart = $farStartDate->format('Y-m-d');
			$farEnd = $farEndDate->format('Y-m-d');
			if($format == 'synthetics')
				$rangeWhere .=" fs.dt BETWEEN '$farStart' AND '$farEnd' OR";
			else
				$rangeWhere .=" far.dt BETWEEN '$farStart' AND '$farEnd' OR";
		}
		
		if(!empty($rangeWhere)) $rangeWhere = substr($rangeWhere,0,-2);
		
		return '('.$rangeWhere.')';
	}
	

	private function getAllActiveCustomers($eStart, $eEnd, $customerType='')
	{			
		$dt = explode('-',$eStart);
		$year = $dt[0];
		$month = $dt[1];

		$cJoin = '';
		if(!empty($customerType) && $customerType != -1)
		{
			$customerType = (int)$customerType;
			$cJoin = "LEFT JOIN forecast_customers_consumption_types fcct ON fcct.customer_id = c.customer_id
					  JOIN forecast_consumption_types fct ON fct.fct_id = fcct.fct_id AND fct.fct_id=$customerType OR (fcct.fcct_id IS NULL AND fct.consumption_type_name='General' AND fct.fct_id=$customerType)";
		}

		$sql = "SELECT c.customer_id as value FROM customers c
				JOIN  (SELECT ct.customer_id, MIN(sr.start_date), MAX(ct.contract_stop) from contracts ct
						  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
				WHERE '$year-$month-01'>=sr.start_date AND (ct.contract_stop IS NULL OR ct.contract_stop >= '$year-$month-01')
				GROUP BY ct.customer_id) B ON c.customer_id = B.customer_id
				$cJoin
				WHERE c.customer_status='Activ' AND c.supplier_id = " .$this->supplierID;
	
		return $this->getValueArray($sql);
	}
	
	private function getCustomerName($customerID)
	{
		$sql = 'select customer_name as value from customers where customer_id = '.$customerID;;
	
		return $this->getValue($sql);
	}
	
	private function getCustomersPODs($customerID)
	{
		$sql = 'select pod_no as value from pods where pod_status = "Activ" and customer_id='.$customerID;
	
		return $this->getValueArray($sql);
	}
	
	private function getCustomerWFDProfile($customerID, $pod='')
	{
		$sql = "SELECT coalesce(fcct.wfd_profile_id,(SELECT wfd_profile_id FROM working_free_days_profiles WHERE supplier_id={$this->supplierID} and profile_name='General')) AS value FROM customers c 
		LEFT JOIN forecast_customers_consumption_types fcct 
		ON c.customer_id = fcct.customer_id AND fcct.pod = '$pod'
		WHERE c.customer_id = $customerID";
		
		return $this->getValue($sql);
	}
	
	private function getEstimationOptions($customerID, $start, $end, $excludeManual = true)
	{
		$excludedDays = [];
		if($excludeManual)
		{
			$sql = "SELECT date as value FROM forecast_customers_manual_estimates WHERE customer_id = $customerID AND date BETWEEN '$start' AND '$end'"; 
			
			$excludedDays = $this->getValueArray($sql);
		}
	
		$sql = "SELECT coalesce(fcct.pod,'') AS pod, fcto.option_name, fcto.value FROM customers c
                                LEFT JOIN forecast_customers_consumption_types fcct ON fcct.customer_id = c.customer_id
                                JOIN forecast_consumption_types fct ON fct.fct_id = fcct.fct_id OR (fcct.fcct_id IS NULL AND fct.consumption_type_name='General')
                                JOIN forecast_consumption_types_options fcto ON fct.fct_id = fcto.fct_id
                                WHERE c.customer_id =$customerID
				UNION
				SELECT '' AS pod, fcto.option_name, fcto.value FROM customers c
										LEFT JOIN forecast_customers_consumption_types fcct ON fcct.customer_id = c.customer_id AND fcct.pod = ''
										JOIN forecast_consumption_types fct ON fcct.fcct_id IS NULL AND fct.consumption_type_name='General'
										JOIN forecast_consumption_types_options fcto ON fct.fct_id = fcto.fct_id
										WHERE c.customer_id =$customerID";
								
		$qResult = $this->getArray($sql);
		
		$ret = (object)['customer'=>$customerID];
		foreach($qResult as $r)
		{
			if(!empty($r['pod']))
			{
				if(!isset($ret->{$r['pod']})) 
				{
					$ret->{$r['pod']} = (object)['pod'=>$r['pod'],'customer'=>$customerID];
					$ret->{$r['pod']}->wfdProfile = $this->getCustomerWFDProfile($customerID, $r['pod']);
					$ret->{$r['pod']}->excludedDays = $excludedDays;
				}
				
				$ret->{$r['pod']}->{$r['option_name']} = $r['value'];
			}
			else
				$ret->{$r['option_name']} = $r['value'];
			
		}
		
		$ret->wfdProfile = $this->getCustomerWFDProfile($customerID);
		$ret->excludedDays = $excludedDays;
			
		return  $ret;
	}
	
	private function selectNewPODValues($eStart, $eEnd, $estimationOptions, $history = false)
	{		
		//todo
		//if(!$estimationOptions->temperature) 
		//{
			if($history)
			{
				$ret['estimatedSQLValues'] = "";
				
				$ret['historySQLValues'] = "";
				$ret['historyInterval'] = "";

				$ret['historyType'] = "istorice";			
				//log_message('error',print_r($ret,true));
				return $ret;
			}
			else
				return "";
		//}
		
		if(count($estimationOptions->excludedDays) == 1 && $estimationOptions->excludedDays[0] == $eStart && $estimationOptions->excludedDays[0] == $eEnd && !$history)
		{
			log_message('info', "Estimare blocata de utilizator pentru clientul {$estimationOptions->customer}");
			return "";
		}

		
		log_message('info', print_r($estimationOptions, true));	
	
		/*1. PREPARE RAW DATA FILTERS*/
		$supplierID = $this->supplierID;
		$customerID = $estimationOptions->customer;
		
		log_message('warning',"Customer $customerID, selectNewPODValues!");
		
		if(isset($estimationOptions->pod)) 
		{
			//$selPOD = "'".$estimationOptions->pod."',";
			$podWhere = "AND far.pod = '".$estimationOptions->pod."'";
			//$groupBY = 'fc.pod,';
		}
		else 
		{
			//$selPOD = "'' as pod,";
			$podWhere = '';
			//$groupBY = '';
		}
		
		$exclPODs = '';
		if(!empty($excludePODs)) 
			$exclPODs = "AND far.pod not in ($excludePODs)";
		
		$exclDays = $this->getExcludedDays($estimationOptions,'A.datetime');

		if($estimationOptions->temperature_source != "temperature")
		{
			$tJOIN = "weather_data wd ON far.dh = wd.dh AND wd.county_code = co.county_code AND wd.layer = '{$estimationOptions->temperature_source}'";
			$tSEL = "wd.value";
		}
		else
		{
			$tJOIN = "forecast_temperatures ft ON far.dh = ft.dh";
			$tSEL = "ft.temperature";
		}
				
		/*2. CREATE RAW DATA TABLE*/
		$months = implode(',',$this->correlatedMonths);
		$minDate = \DateTime::createFromFormat('Y-m-d',$eStart)->modify('-13 month')->format('Y-m-d');
		
		$this->dropTable("rawdata");		
		$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS rawdata
		(
		`far_id` INT UNSIGNED NOT NULL,
		`pod` VARCHAR(40) NOT NULL,
		`far_datetime` DATETIME NOT NULL,
		`far_ea` DECIMAL(20,8) NOT NULL,
		`dh` VARCHAR(14) NOT NULL,
		`dt` DATE NOT NULL,
		`interval` INT NOT NULL,
		`customer_id` INT NOT NULL,
		`supplier_id` INT NOT NULL,
		`county_code` varchar(3) DEFAULT 'B',
		`{$estimationOptions->temperature_source}` DECIMAL(20,2) NOT NULL,
		`dayIndex` INT NOT NULL,
		`sunriseH` INT NOT NULL,
		`sunsetH` INT NOT NULL,
		`daylight` INT NOT NULL,
		PRIMARY KEY (`far_id`) USING BTREE,
		INDEX idx_pod (`pod`) USING BTREE,
		INDEX idx_county_code (`county_code`) USING BTREE,
		INDEX idx_dayIndex (`dayIndex`) USING BTREE,
		INDEX idx_{$estimationOptions->temperature_source} (`{$estimationOptions->temperature_source}`) USING BTREE,
		INDEX idx_datetime (`far_datetime`) USING BTREE,
		INDEX idx_dh (`dh`) USING BTREE,
		INDEX idx_dt (`dt`) USING BTREE,
		INDEX idx_interval (`interval`) USING BTREE,
		INDEX idx_sunriseH (`sunriseH`) USING BTREE,
		INDEX idx_sunsetH (`sunsetH`) USING BTREE,
		INDEX idx_daylight (`daylight`) USING BTREE
		)";
		$this->writeData($sql);

	
		$sql = "INSERT INTO rawdata 
		SELECT far.*, $customerID as customer_id, 1 as supplier_id, coalesce(co.county_code,'B') as county_code ,$tSEL as {$estimationOptions->temperature_source}, 
		case
		when wfd.mapping IS NOT NULL then wfd.mapping
		when DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3
		ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex,
		fs.sunriseH,fs.sunsetH,
		case when HOUR(far.far_datetime) >= fs.sunriseH AND HOUR(far.far_datetime) <= fs.sunsetH then 1 ELSE 0 END AS daylight
		FROM customer_far_$customerID far
		LEFT JOIN pods p on far.pod = p.pod_no
		LEFT JOIN counties co on p.county = co.county
		LEFT JOIN $tJOIN
		JOIN forecast_sun fs ON date(far.far_datetime) = fs.date
		LEFT JOIN working_free_days wfd ON wfd.wfd_profile_id={$estimationOptions->wfdProfile} AND date(far.far_datetime) = wfd.free_date
		WHERE far.dt > '$minDate' AND month(far.far_datetime) IN ($months) $podWhere $exclPODs
		GROUP BY far.pod, far.far_datetime";
	
		$this->writeData($sql);

		
		/*2. CREATE NEW RAW DATA TABLE*/
		#filter data & interval calculate average	
		$this->dropTable("newRawData");
		$sql = "CREATE TEMPORARY TABLE newRawData AS
				SELECT
				far.supplier_id,
				far.customer_id,
				far.pod,
				far.far_datetime,
				far.daylight AS far_daylight,
				CASE WHEN far.daylight = 0 AND hour(far.far_datetime) > 12 THEN hour(far.far_datetime)-24 ELSE hour(far.far_datetime) END as far_hour, 
				far.{$estimationOptions->temperature_source} as far_{$estimationOptions->temperature_source},
				tdates.datetime,
				tdates.daylight,
				CASE WHEN tdates.daylight = 0 AND hour(tdates.datetime) > 12 THEN hour(tdates.datetime)-24 ELSE hour(tdates.datetime) END as hour,
				tdates.{$estimationOptions->temperature_source},
				far.dayIndex,
				far.far_ea
				FROM tdates_{$estimationOptions->wfdProfile} AS tdates JOIN
				rawdata far ON
				 far.dayIndex = tdates.dayIndex AND far.county_code = tdates.county_code AND 
				ABS(far.{$estimationOptions->temperature_source} - tdates.{$estimationOptions->temperature_source})<={$estimationOptions->nc_temperature_margin} AND
				/*far.daylight = tdates.daylight AND*/
				( (far.daylight = 1 AND ABS(hour(far.far_datetime) - hour(tdates.datetime)) <= {$estimationOptions->nc_interval_marginZ}) OR 
				  (far.daylight = 0 AND ABS(CASE WHEN hour(far.far_datetime) > 12 THEN hour(far.far_datetime)-24 ELSE hour(far.far_datetime) END - CASE WHEN hour(tdates.datetime) > 12 THEN hour(tdates.datetime)-24 ELSE hour(tdates.datetime) END) <= {$estimationOptions->nc_interval_marginN}) )";
		$this->writeData($sql);

		log_message('debug','newRawData size:'.$this->getValue("SELECT COUNT(*) as value FROM newRawData"));
		/*2. CREATE NEW RAW DATA TABLE*/
		#select value closed to the hour interval
		
		$this->dropTable("newRawDataH");
		$sql = "CREATE TEMPORARY TABLE newRawDataH AS
		SELECT A.*, B.minH FROM 
		(SELECT * FROM newRawData) A
		JOIN 
		(SELECT datetime, MIN(ABS(nrd.far_hour-nrd.hour)) AS minH FROM newRawData nrd
		GROUP BY nrd.datetime) B ON A.datetime = B.datetime AND ABS(A.far_hour-A.hour) = B.minH";
		$this->writeData($sql);
	
		/*3. CREATE NEW RAW DATA TABLE*/
		#calculate ea average for the hour interval

		$this->dropTable("newRawDataHA");
		$sql = "CREATE TEMPORARY TABLE newRawDataHA AS
		SELECT A.*,B.med_ea, ABS(B.med_ea - A.far_ea) as deviation FROM 
		(SELECT * FROM newRawDataH) A
		JOIN 
		(SELECT pod, datetime, AVG(far_ea) AS med_ea FROM newRawDataH
		GROUP BY pod, datetime) B ON A.pod = B.pod AND A.datetime = B.datetime";
		$this->writeData($sql);
	
		/*4. CREATE NEW RAW DATA TABLE*/
		#select values closed to range

		//value selection function(MIN(DEV), MIN(far_ea) / MAX(DEV))
		$MM = $estimationOptions->nc_selected_value_type ?? 'MIN';
		$MMjoin = '';
		
		if($MM == 'MIN') {$MM = 'MIN(far_ea) as minEA';$MMjoin='A.far_ea = B.minEA';}
		else if ($MM == 'MAX') {$MM = 'MAX(far_ea) as maxEA';$MMjoin='A.far_ea = B.maxEA';}
		else if ($MM == 'MINDEV') {$MM = 'MIN(deviation)  AS mmDEV';$MMjoin='A.deviation = B.mmDEV';}

		$this->dropTable("newRawDataDEV");		
		$sql = "CREATE TEMPORARY TABLE newRawDataDEV AS
		SELECT A.* FROM 
		(SELECT * FROM newRawDataHA) A
		JOIN
		(SELECT nrd.pod, nrd.datetime, $MM FROM newRawDataHA nrd
		GROUP BY nrd.pod,nrd.datetime) B ON A.pod = B.pod AND A.datetime = B.datetime AND $MMjoin";
		$this->writeData($sql);
	
		/*
		$this->dumpTable('newRawData');	
		$this->dumpTable('newRawDataH');	
		$this->dumpTable('newRawDataHA');
		$this->dumpTable('newRawDataDEV');
		*/
		
		#final result: select min value closed to average
		$sql = "select 
		$supplierID,
		$customerID,
		A.pod,
		A.datetime,
		'temperatura-nc',
		A.far_ea
		FROM
		(SELECT * FROM newRawDataDEV) A
		LEFT JOIN newRawDataDEV B
		ON A.pod = B.pod AND A.datetime = B.datetime AND A.far_ea > B.far_ea
		WHERE B.far_ea IS NULL $exclDays";

		if($history)
		{
			$ret['estimatedSQLValues'] = $sql;
			
			$ret['historySQLValues'] = "";
			$ret['historyInterval'] = "";

			$ret['historyType'] = "istorice";			
			//log_message('error',print_r($ret,true));
			return $ret;
		}
		
		return $sql;
	}
	
	private function selectSupplementaryHistoryValues($eStart, $eEnd, $estimationOptions, $history = false)
	{
		return '';
		/*
		if(!$this->hasSynthetics($eStart, $eEnd, $estimationOptions, '','suplimentar')) return '';
		
		log_message("error", "Estimare cu sintetice suplimentare");
		if($estimationOptions->prosumator)
			$sql = $this->selectProsumatorSyntheticHistoryValues($eStart, $eEnd, $estimationOptions, '', $history);
		elseif($estimationOptions->temperature || $estimationOptions->manual)
			$sql = $this->selectTemperatureSyntheticHistoryValues($eStart, $eEnd, $estimationOptions, '', $history);
		elseif($estimationOptions->simple)
			$sql = $this->selectSimpleSyntheticHistoryValues($eStart, $eEnd, $estimationOptions, '', $history);		
		else 
			$sql = "";
		
		return $sql;*/
	}
	
	
	private function selectHistoryValues($eStart, $eEnd, $estimationOptions, $exclPODs, $history = false)
	{
		log_message("info",'excludedPODS:'.$exclPODs);
		if(isset($estimationOptions->pod))
			log_message("info", "Estimare la nivel de pod");
		else
			log_message("info", "Estimare la nivel de client");
		
		if(count($estimationOptions->excludedDays) == 1 && $estimationOptions->excludedDays[0] == $eStart && $estimationOptions->excludedDays[0] == $eEnd && !$history)
		{
			log_message('info', "Estimare blocata de utilizator pentru clientul {$estimationOptions->customer}");
			return "";
		}
		
		if($estimationOptions->simple)
			$sql = $this->selectSimpleHistoryValues($eStart, $eEnd, $estimationOptions, $exclPODs, $history);
		elseif($estimationOptions->temperature)
			$sql = $this->selectTemperatureHistoryValues($eStart, $eEnd, $estimationOptions, $exclPODs, $history);
		elseif($estimationOptions->prosumator)
			$sql = $this->selectProsumatorHistoryValues($eStart, $eEnd, $estimationOptions, $exclPODs, $history);
		elseif($estimationOptions->manual)
			$sql="";
		
		return $sql;
	}
			
	private function selectSimpleHistoryValues($eStart, $eEnd, $estimationOptions, $excludePODs, $history = false)
	{
		/*
			1. OPTION: FILTER OUTLIERS AT CUSTOMER LEVEL PER YEARLY INTERVAL
			2. CALCULATE TOTAL CONSUMPTION FOR PODS PER YEARLY INTERVAL
			3. CALCULATE ANUAL AVERAGE CONSUMPTION FOR CUSTOMER
		*/
		log_message('debug', print_r($estimationOptions, true));	
		$customerID = $estimationOptions->customer;
		
		$marginDays = 0;
		if($estimationOptions->outliers)
			$marginDays = $estimationOptions->outliers_radius;
		
		//sursa: REALIZAT (checked)
		//selecteaza media pe zile din ultimii ani fara sa considere zilele libere sau temperatura
		
		$rawStart = \DateTime::createFromFormat('Y-m-d',$eStart)->format('Y-m-01');
		$rawEnd = \DateTime::createFromFormat('Y-m-d',$eEnd)->format('Y-m-t');

		if($estimationOptions->intervalZ != corelare_data || $estimationOptions->intervalN != corelare_data)
		{
			if($this->hasSynthetics($eStart, $eEnd, $estimationOptions, $excludePODs, 'absolut'))
				$synthIntervalWhr = $this->getDateRangeWhere($customerID,$this->supplierID, $rawStart, $rawEnd, $marginDays, true);
			else
				$rangeDays = $this->getDateRangeWhere($customerID,$this->supplierID, $rawStart, $rawEnd, $marginDays);
		}
		else
		{
			if($this->hasSynthetics($eStart, $eEnd, $estimationOptions, $excludePODs, 'absolut'))
				$synthIntervalWhr = $this->getDateRangeWhere($customerID,$this->supplierID, $rawStart, $rawEnd, $marginDays, true);
			else
				$rangeDays = $this->getDateRangeWhere($customerID,$this->supplierID, $eStart, $eEnd, $marginDays);
		}
		
		if(isset($estimationOptions->pod)) 
		{
			$selPOD = "'".$estimationOptions->pod."',";
			$podWhere = "AND far.pod = '".$estimationOptions->pod."'";
		}
		else 
		{
			$selPOD = "'' as pod,";
			$podWhere = '';
		}
		
		$exclPODs = '';
		if(!empty($excludePODs))
			$exclPODs = "AND far.pod not in ($excludePODs)";

		$maxMonths = '2000-01-01';
		$maxMonthsWhr = "";
		if($estimationOptions->max_months_before)
		{
			$mxd = \DateTime::createFromFormat('Y-m-d',substr($eStart,0,-2).'01');
			$maxMonths = $mxd->modify("-{$estimationOptions->max_months_before} month")->format('Y-m-d');
			$maxMonthsWhr = " AND far.dt >= '$maxMonths' ";
		}

		$this->writeData('DROP TEMPORARY TABLE tSimpleValues');
		
		if($this->hasSynthetics($eStart, $eEnd, $estimationOptions, $excludePODs, 'absolut'))
		{
			$sql = "CREATE TEMPORARY TABLE tSimpleValues SELECT 1 as supplier_id,
				   $customerID as customer_id,
				   far.pod,
				   far.far_datetime,
				   case 
				   when wfd.mapping IS NOT NULL then wfd.mapping
				   when DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3 
				   ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex ,
				   SUM(far.far_ea) AS far_ea
				   FROM
					( SELECT 
					ROW_NUMBER() OVER (ORDER BY synthetics_datetime) AS far_id,
					'sintetic' AS pod,
					DATE_ADD(fs.synthetics_datetime, INTERVAL I.minute MINUTE) AS far_datetime,
					fs.synthetic_ea / 4 AS far_ea,
					concat(fs.dt,' ',hour(`synthetics_datetime`)) as dh,							
					fs.dt as dt,
					floor((hour(fs.synthetics_datetime) * 60 + I.minute) / 15) as `interval`
					FROM forecast_synthetics fs
					JOIN 
					 (SELECT 0 AS minute
					  UNION SELECT 15
					  UNION SELECT 30
					  UNION SELECT 45) I
					ON fs.customer_id = $customerID 
					WHERE fs.dt > '$maxMonths' AND $synthIntervalWhr AND fs.dt<'$eStart'
					) far
					LEFT JOIN working_free_days wfd ON wfd.wfd_profile_id={$estimationOptions->wfdProfile} AND date(far.far_datetime) = wfd.free_date
					GROUP BY far.pod, far.far_datetime";
			$this->writeData($sql);			
		}
		else
		{
			$sql = "CREATE TEMPORARY TABLE tSimpleValues SELECT 1 as supplier_id,
					$customerID as customer_id,
					far.pod,
					far.far_datetime,
					case 
					when wfd.mapping IS NOT NULL then wfd.mapping
					when DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3 
					ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex ,
					SUM(far.far_ea) AS far_ea
					FROM customer_far_$customerID far
					LEFT JOIN working_free_days wfd ON wfd.wfd_profile_id={$estimationOptions->wfdProfile} AND date(far.far_datetime) = wfd.free_date
					WHERE year(far.far_datetime) < year('$eStart') $podWhere $exclPODs $maxMonthsWhr AND ($rangeDays)
					GROUP BY far.pod, far.far_datetime";
					$this->writeData($sql);
		}
		
		$estimationOptions->outliers = false; //todo
		if($estimationOptions->outliers)
		{		
			$sql = "SELECT * FROM tSimpleValues";
			
			$data = $this->excludeOutliers($estimationOptions, $eStart, $eEnd, $this->getArray($sql));
			
			if(!empty($data))
			{
				$values = '';
				foreach($data as $d)
				{
					$sql = "UPDATE tSimpleValues SET far_ea = ".$d['far_ea']." WHERE far_datetime = '".$d['far_datetime']."'";
					$this->writeData($sql);
				}
			}
		}
		
		$exclDays = $this->getExcludedDays($estimationOptions);
		$exclHours = $this->getExcludedHours($estimationOptions);

		
		if($estimationOptions->intervalZ == corelare_data)
			$joinZ = " month(A.far_datetime) = month(tdates.datetime) AND day(A.far_datetime) = day(tdates.datetime) AND hour(A.far_datetime) = hour(tdates.datetime) AND fi.interval_type = 'Z' ";
		else
			$joinZ = " A.dayIndex = tdates.dayIndex AND hour(A.far_datetime) = hour(tdates.datetime) AND fi.interval_type = 'Z' ";
		
		if($estimationOptions->intervalN == corelare_data)
			$joinN = " month(A.far_datetime) = month(tdates.datetime) AND day(A.far_datetime) = day(tdates.datetime) AND hour(A.far_datetime) = hour(tdates.datetime) AND fi.interval_type = 'N' ";
		else
			$joinN = " A.dayIndex = tdates.dayIndex AND hour(A.far_datetime) = hour(tdates.datetime) AND fi.interval_type = 'N' ";
				
		$sql = "SELECT A.supplier_id,
					   A.customer_id,
					   A.pod,
					   tdates.datetime,
					   'simpla',
					   CAST(AVG(A.far_ea) AS DECIMAL(20, 8)) AS far_ea
				FROM tdates_{$estimationOptions->wfdProfile} AS tdates
				JOIN forecast_intervals fi
				ON tdates.county_code='B' AND fi.hour = hour(tdates.datetime) and fi.month = month(tdates.datetime) and fi.customer_id = coalesce((SELECT customer_id FROM forecast_intervals WHERE customer_id = $customerID LIMIT 1),0)
				JOIN  tSimpleValues A
				ON $joinZ
					$exclDays $exclHours
				GROUP BY A.customer_id, A.pod, tdates.datetime 
				UNION
				SELECT A.supplier_id,
					   A.customer_id,
					   A.pod,
					   tdates.datetime,
					   'simpla',
					   CAST(AVG(A.far_ea) AS DECIMAL(20, 8)) AS far_ea
				FROM tdates_{$estimationOptions->wfdProfile} AS tdates
				JOIN forecast_intervals fi
				ON tdates.county_code='B' AND fi.hour = hour(tdates.datetime) and fi.month = month(tdates.datetime) and fi.customer_id = coalesce((SELECT customer_id FROM forecast_intervals WHERE customer_id = $customerID LIMIT 1),0)
				JOIN  tSimpleValues A
				ON $joinN
					$exclDays $exclHours
				GROUP BY A.customer_id, A.pod, tdates.datetime";
				
		if($history)
		{
			$ret['estimatedSQLValues'] = $sql;
			
			$ret['historySQLValues'] = "SELECT
						A.far_datetime,
						CAST(SUM(A.far_ea) AS DECIMAL(20, 8)) AS far_ea
						FROM tdates_{$estimationOptions->wfdProfile} AS tdates
						JOIN forecast_intervals fi
						ON tdates.county_code='B' AND fi.hour = hour(tdates.datetime) and fi.month = month(tdates.datetime) and fi.customer_id = coalesce((SELECT customer_id FROM forecast_intervals WHERE customer_id = $customerID LIMIT 1),0)
						JOIN  tSimpleValues A
						ON $joinZ
							$exclDays $exclHours
						GROUP BY hour(A.far_datetime), date(A.far_datetime) 
						UNION
						SELECT
						A.far_datetime,
						CAST(SUM(A.far_ea) AS DECIMAL(20, 8)) AS far_ea
						FROM tdates_{$estimationOptions->wfdProfile} AS tdates
						JOIN forecast_intervals fi
						ON tdates.county_code='B' AND fi.hour = hour(tdates.datetime) and fi.month = month(tdates.datetime) and fi.customer_id = coalesce((SELECT customer_id FROM forecast_intervals WHERE customer_id = $customerID LIMIT 1),0)
						JOIN  tSimpleValues A
						ON $joinN
							$exclDays $exclHours
						GROUP BY hour(A.far_datetime), date(A.far_datetime)";
						//ORDER BY hour(A.far_datetime), date(A.far_datetime)" ;

			$ret['historyInterval'] = "SELECT
						date(A.far_datetime) as value
						FROM tdates_{$estimationOptions->wfdProfile} AS tdates
						JOIN  tSimpleValues A
						ON tdates.county_code='B' AND month(A.far_datetime) = month(tdates.datetime) AND day(A.far_datetime) = day(tdates.datetime) AND hour(A.far_datetime) = hour(tdates.datetime)
							$exclDays $exclHours
						GROUP BY date(A.far_datetime) ";
						//ORDER BY A.far_datetime" ;
			
			$ret['historyType'] = "istorice";
			//log_message('error',print_r($ret,true));
			return $ret;
		}
		
		return $sql;
	}
	
	private function countTemperatures($start, $end)
	{
		$sql = "SELECT count(*) as value FROM forecast_temperatures WHERE temperature_datetime BETWEEN '$start' AND '$end'";
		log_message('debug',$sql);
		return $this->getValue("SELECT count(*) as value FROM forecast_temperatures WHERE temperature_datetime BETWEEN '$start' AND '$end'");
	}
	
	private function getTemperatures($start, $end)
	{
		return $this->getArray("SELECT * FROM forecast_temperatures WHERE temperature_datetime BETWEEN '$start' AND '$end'");
	}
	
	private function excludeOutliers($estimationOptions, $eStart, $eEnd, $data)
	{		
		$outliersInterval = explode('-',trim($estimationOptions->outliers_interval));
		$oIStart = (int)$outliersInterval[0];
		$oIEnd = (int)$outliersInterval[1];
		$emptyPOD = "' ',";
		
		$outliers = [];
		
		$dLen = count($data);
		for($idx = 0;$idx<$dLen;$idx++)
		{
			$farDate = \DateTime::createFromFormat('Y-m-d H:i:s',$data[$idx]['far_datetime']);
			$farStart = \DateTime::createFromFormat('Y-m-d H:i:s',substr($data[$idx]['far_datetime'],0,4).substr($eStart,4).' 00:00:00');
			$farEnd = \DateTime::createFromFormat('Y-m-d H:i:s',substr($data[$idx]['far_datetime'],0,4).substr($eEnd,4).' 23:00:00');
			
			if($farStart<=$farDate && $farEnd>=$farDate)
			{
				//log_message('error',$idx.':'.print_r($data[$idx], true));
				
				$h = (int)$farDate->format('H');
				if($oIStart <= $oIEnd)
				{
					if ( $h < $oIStart || $h > $oIEnd ) //outside outliers check interval
					{
						continue;
					}
				}
				else
				{
					if ( $h < $oIStart && $h > $oIEnd ) //outside outliers check interval
					{
						continue;
					}	
				}
				
				$valid = false;
				$numChecks = 0;
				$sumChecks = 0;
				
				for($day=-$estimationOptions->outliers_radius;$day<=$estimationOptions->outliers_radius;$day++)
				{
					for($hour=-$estimationOptions->outliers_radius;$hour<=$estimationOptions->outliers_radius;$hour++)
					{
						$checkIdx = $idx - 24*$day+$hour;
						if(isset($data[$checkIdx]))
						{										
							//outlier need to be higher than ALL others
							if($data[$idx]['far_datetime'] != $data[$checkIdx]['far_datetime'] &&  abs($data[$idx]['far_ea']) < abs($data[$checkIdx]['far_ea'])*$estimationOptions->outliers_size)
							{
								$valid = true;

							}
							elseif($data[$idx]['far_datetime'] != $data[$checkIdx]['far_datetime'])
							{
								$numChecks++;
								$sumChecks+=$data[$checkIdx]['far_ea'];								
							}
						}
						/* known issue: topleft & bottomright extremes
							else
							log_message('error',"CheckIdx Error:$checkIdx");
						*/
					}
				}
				
				if($numChecks>0)
				{	
					if(!$valid)
					{
						log_message('info','Outlier:'.print_r($data[$idx], true).'=>'.$sumChecks/$numChecks);
						$data[$idx]['far_ea'] = $sumChecks/$numChecks;
						$outliers[] = $data[$idx];
					}
				}
			}
		}
		
		return $outliers;
	}
	
	private function selectTemperatureHistoryValues($eStart, $eEnd, $estimationOptions, $excludePODs, $history = false)
	{
		/* 
			DAY MAPPING
			
			Luni, Vineri, Sambata, Duminica => unice
			Marti, Miecuri, Joi => compatibile
			
			Craciun => Duminica
			Paste => Duminica
			Rusalii => Duminica
			
		*/

		/*1. PREPARE RAW DATA FILTERS*/
		
		log_message('info', print_r($estimationOptions, true));	
		
		$supplierID = $this->supplierID;
		$customerID = $estimationOptions->customer;
		

		$marginDays = 0;
		if($estimationOptions->outliers)
			$marginDays = $estimationOptions->outliers_radius;
		

		if($estimationOptions->days_margin == 0)
		{
			$rawStart = \DateTime::createFromFormat('Y-m-d',$eStart)->format('Y-m-01');
			$rawEnd = \DateTime::createFromFormat('Y-m-d',$eEnd)->format('Y-m-t');
		
			$intervalWhr = $this->getDateRangeWhere($customerID,$supplierID, $rawStart, $rawEnd, $marginDays);
			$intervalMinWhr = $intervalWhr;
			
			$rawDataIntervalStart = \DateTime::createFromFormat('Y-m-d',$rawStart)->modify("-$marginDays days")->format('Y-m-d');
			$rawDataIntervalEnd = \DateTime::createFromFormat('Y-m-d',$rawEnd)->modify("$marginDays days")->format('Y-m-d');
		}
		else
		{
			//max interval
			$marginDaysMax = $marginDays + $estimationOptions->days_margin_max;
			$intervalWhr = $this->getDateRangeWhere($customerID,$supplierID, $eStart, $eEnd, $marginDaysMax);
			
			//min interval
			$marginDaysMin = $marginDays + $estimationOptions->days_margin;
			$intervalMinWhr = $this->getDateRangeWhere($customerID,$supplierID, $eStart, $eEnd, $marginDaysMin);
			
			$rawDataIntervalStart = \DateTime::createFromFormat('Y-m-d',$eStart)->modify("-$marginDaysMax days")->format('Y-m-d');
			$rawDataIntervalEnd = \DateTime::createFromFormat('Y-m-d',$eEnd)->modify("$marginDaysMax days")->format('Y-m-d');
		}

		
		//$intervalWhr = "(( month(far.far_datetime)= MONTH('$rawDataIntervalStart') AND DAY (far.far_datetime) >= DAY('$rawDataIntervalStart') ) AND
		//		( month(far.far_datetime)= MONTH('$rawDataIntervalEnd') AND DAY (far.far_datetime) <= DAY('$rawDataIntervalEnd') ))";
		
		
		
		if(isset($estimationOptions->pod)) 
		{
			//$selPOD = "'".$estimationOptions->pod."',";
			$podWhere = "AND far.pod = '".$estimationOptions->pod."'";
			//$groupBY = 'fc.pod,';
		}
		else 
		{
			//$selPOD = "'' as pod,";
			$podWhere = '';
			//$groupBY = '';
		}
		
		$exclPODs = '';
		if(!empty($excludePODs)) 
			$exclPODs = "AND far.pod not in ($excludePODs)";
		
		
		$maxMonthsWhr = "";
		if($estimationOptions->max_months_before)
		{
			$mxd = \DateTime::createFromFormat('Y-m-d',substr($eStart,0,-2).'01');
			$maxMonths = $mxd->modify("-{$estimationOptions->max_months_before} month")->format('Y-m-d');
			$maxMonthsWhr = " AND far.dt >= '$maxMonths' ";
		}
		/*3. CHECK FOR TEMPERATURES */

		$tempCheckEnd = min($rawDataIntervalEnd, $eEnd);
		$intervalHours = (int)((\DateTime::createFromFormat('Y-m-d H:i:s', $tempCheckEnd.' 23:00:00')->getTimestamp() - \DateTime::createFromFormat('Y-m-d H:i:s', $rawDataIntervalStart.' 00:00:00')->getTimestamp()) / 3600) + 1;

		if($estimationOptions->temperature_source != "temperature")
			$tempCount = (int)$this->getValue("SELECT count(*) as value FROM weather_data WHERE layer = '{$estimationOptions->temperature_source}' AND county_code='B' AND weather_datetime BETWEEN '$rawDataIntervalStart 00:00:00' AND '$tempCheckEnd 23:00:00'");
		else
			$tempCount = (int)$this->countTemperatures($rawDataIntervalStart.' 00:00:00', $tempCheckEnd.' 23:00:00');

		if($tempCount < $intervalHours)
		{
			$this->errorCollection[] = "Nu exista temperaturi ({$estimationOptions->temperature_source}) pentru intervalul istoric {$rawDataIntervalStart} - {$tempCheckEnd}!";
			return '';
		}

		$rawDataTable = "rawdata_".$this->rawDataCounter;
		/*2. CREATE RAW DATA TABLE*/
		$sql = "DROP TEMPORARY TABLE $rawDataTable";
		$this->writeData($sql);		
		
		if($estimationOptions->temperature_source != "temperature")
		{
			$tJOIN = "weather_data wd ON far.dh = wd.dh AND wd.county_code = co.county_code AND wd.layer = '{$estimationOptions->temperature_source}'";
			$tSEL = "wd.value";
		}
		else
		{
			$tJOIN = "forecast_temperatures ft ON far.dh = ft.dh";
			$tSEL = "ft.temperature";
		}
		
		
		$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS $rawDataTable
		(
		`far_id` INT UNSIGNED NOT NULL,
		`pod` VARCHAR(40) NOT NULL,
		`far_datetime` DATETIME NOT NULL,
		`far_ea` DECIMAL(20,8) NOT NULL,
		`dh` VARCHAR(14) NOT NULL,
		`dt` DATE NOT NULL,
		`interval` INT NOT NULL,
		`customer_id` INT NOT NULL,
		`supplier_id` INT NOT NULL,
		`county_code` varchar(3) DEFAULT 'B',
		`{$estimationOptions->temperature_source}` DECIMAL(20,2) NOT NULL,
		`dayIndex` INT NOT NULL,
		PRIMARY KEY (`far_id`) USING BTREE,
		INDEX idx_pod (`pod`) USING BTREE,
		INDEX idx_county_code (`county_code`) USING BTREE,
		INDEX idx_dayIndex (`dayIndex`) USING BTREE,
		INDEX idx_{$estimationOptions->temperature_source} (`{$estimationOptions->temperature_source}`) USING BTREE,
		INDEX idx_datetime (`far_datetime`) USING BTREE,
		INDEX idx_dh (`dh`) USING BTREE,
		INDEX idx_dt (`dt`) USING BTREE,
		INDEX idx_interval (`interval`) USING BTREE
		)";
		$this->writeData($sql);
		
		if($this->hasSynthetics($eStart, $eEnd, $estimationOptions, $excludePODs, 'absolut'))
		{
			$rawStart = \DateTime::createFromFormat('Y-m-d',$eStart)->format('Y-m-01');
			$rawEnd = \DateTime::createFromFormat('Y-m-d',$eEnd)->format('Y-m-t');	
			$synthIntervalWhr = $this->getDateRangeWhere($customerID,$supplierID, $rawStart, $rawEnd, $marginDays, 'synthetics');
		
			$smxd = \DateTime::createFromFormat('Y-m-d',substr($eStart,0,-2).'01');
			$synthMaxMonths = $smxd->modify("-13 month")->format('Y-m-d');
				
			$rawStart = \DateTime::createFromFormat('Y-m-d',$eStart)->format('Y-m-01');
			$rawEnd = \DateTime::createFromFormat('Y-m-d',$eEnd)->format('Y-m-t');
			$intervalWhr = $this->getDateRangeWhere($customerID,$supplierID, $rawStart, $rawEnd, $marginDays,'lastYear');
			$intervalMinWhr = $intervalWhr;
		
			$sql = "INSERT INTO $rawDataTable
					SELECT far.*, $customerID as customer_id, 1 as supplier_id, 'B' as county_code ,$tSEL as {$estimationOptions->temperature_source}, 
					case 
					when wfd.mapping IS NOT NULL then wfd.mapping
					when DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3 
					ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex  
					FROM 
					( SELECT 
						ROW_NUMBER() OVER (ORDER BY synthetics_datetime) AS far_id,
							'sintetic' AS pod,
							DATE_ADD(fs.synthetics_datetime, INTERVAL I.minute MINUTE) AS far_datetime,
							fs.synthetic_ea / 4 AS far_ea,
							concat(fs.dt,' ',hour(`synthetics_datetime`)) as dh,							
							fs.dt,
							floor((hour(fs.synthetics_datetime) * 60 + I.minute) / 15) as `interval`
						FROM forecast_synthetics fs
						JOIN 
						 (SELECT 0 AS minute
						  UNION SELECT 15
						  UNION SELECT 30
						  UNION SELECT 45) I
						ON fs.customer_id = $customerID 
						WHERE fs.dt > '$synthMaxMonths' AND $synthIntervalWhr AND fs.dt<'$eStart'
						) far
					JOIN counties co ON co.county_code = 'B'
					LEFT JOIN $tJOIN
					LEFT JOIN working_free_days wfd ON wfd.wfd_profile_id={$estimationOptions->wfdProfile} AND far.dt = wfd.free_date
					GROUP BY far.pod, far.far_datetime";

			$this->writeData($sql);			
		}
		else
		{
			$sql = "INSERT INTO $rawDataTable
					SELECT far.*, $customerID as customer_id, 1 as supplier_id, coalesce(co.county_code,'B') as county_code ,$tSEL as {$estimationOptions->temperature_source}, 
					case 
					when wfd.mapping IS NOT NULL then wfd.mapping
					when DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3 
					ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex  
					FROM customer_far_$customerID far
					LEFT JOIN pods p on far.pod = p.pod_no
					LEFT JOIN counties co on p.county = co.county
					LEFT JOIN $tJOIN
					LEFT JOIN working_free_days wfd ON wfd.wfd_profile_id={$estimationOptions->wfdProfile} AND far.dt = wfd.free_date
					WHERE far.dt<'$eStart' AND $intervalWhr $podWhere $exclPODs $maxMonthsWhr
					GROUP BY far.pod, far.far_datetime";

			$this->writeData($sql);
			
			if($this->hasSynthetics($eStart, $eEnd, $estimationOptions, $excludePODs, 'suplimentar'))
			{
				$rawStart = \DateTime::createFromFormat('Y-m-d',$eStart)->format('Y-m-01');
				$rawEnd = \DateTime::createFromFormat('Y-m-d',$eEnd)->format('Y-m-t');	
				$synthIntervalWhr = $this->getDateRangeWhere($customerID,$supplierID, $rawStart, $rawEnd, $marginDays, 'synthetics');
				
				$smxd = \DateTime::createFromFormat('Y-m-d',substr($eStart,0,-2).'01');
				$synthMaxMonths = $smxd->modify("-13 month")->format('Y-m-d');
			
				$maxId = $this->getValue("SELECT max(far_id) as value FROM $rawDataTable");
				
				$sql = "INSERT INTO $rawDataTable
						SELECT far.*, $customerID as customer_id, 1 as supplier_id, 'B' as county_code ,$tSEL as {$estimationOptions->temperature_source}, 
						case 
						when wfd.mapping IS NOT NULL then wfd.mapping
						when DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3 
						ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex  
						FROM 
						( SELECT 
							$maxId + ROW_NUMBER() OVER (ORDER BY synthetics_datetime) AS far_id,
							'sintetic' AS pod,
							DATE_ADD(fs.synthetics_datetime, INTERVAL I.minute MINUTE) AS far_datetime,
							fs.synthetic_ea / 4 AS far_ea,
							concat(cast(`synthetics_datetime` as date),' ',hour(`synthetics_datetime`)) as dh,							
							DATE(fs.synthetics_datetime) as dt,
							floor((hour(fs.synthetics_datetime) * 60 + I.minute) / 15) as `interval`
							FROM forecast_synthetics fs
							JOIN 
							 (SELECT 0 AS minute
							  UNION SELECT 15
							  UNION SELECT 30
							  UNION SELECT 45) I
							ON fs.customer_id = $customerID 
							WHERE date(fs.synthetics_datetime) > '$synthMaxMonths' AND $synthIntervalWhr AND date(fs.synthetics_datetime)<'$eStart'
							) far
						JOIN counties co ON co.county_code = 'B'						
						LEFT JOIN $tJOIN
						LEFT JOIN working_free_days wfd ON wfd.wfd_profile_id={$estimationOptions->wfdProfile} AND far.dt = wfd.free_date
						GROUP BY far.pod, far.far_datetime";

				$this->writeData($sql);	
			}
		}
		//$this->dumpTable($rawDataTable);	
		//$this->dumpTable("tdates_{$estimationOptions->wfdProfile}");	
		/*3. EXCLUDE OUTLIERS */
		
		log_message("debug","selected PODs:".print_r($this->getValueArray("SELECT distinct pod as value FROM $rawDataTable"),true));
		$estimationOptions->outliers = false; //todo! 
		if($estimationOptions->outliers)
		{	
			$whrOutliarPOD = '';
			if(isset($estimationOptions->pod))
				$whrOutliarPOD = "AND pod = '{$estimationOptions->pod}'";
			
			$sql = "SELECT distinct pod as value FROM $rawDataTable WHERE pod IN 
					(SELECT pod FROM (
					SELECT pod, SUM(far_ea) AS ea FROM $rawDataTable 
					WHERE customer_id = $customerID $whrOutliarPOD
					GROUP BY pod, DATE(far_datetime) ) A
					GROUP BY pod
					HAVING MIN(ea)>0.24)";
			$podList = $this->getValueArray($sql);
				
			if(empty($podList))
				log_message("error","No POD selected for Outliars");
			else
				log_message("error","Outliats PODs:".print_r($podList, true));
			
			foreach($podList as $pod)
			{
				$sql = "SELECT * FROM $rawDataTable WHERE pod = '$pod' ORDER BY far_datetime";
				
				$data = $this->excludeOutliers($estimationOptions, $eStart, $eEnd, $this->getArray($sql));
				
				if(!empty($data))
				{
					$values = '';
					foreach($data as $d)
					{
						$sql = "UPDATE $rawDataTable SET far_ea = {$d['far_ea']} WHERE pod = '$pod' AND far_datetime = '{$d['far_datetime']}'";
						$this->writeData($sql);
					}
				}
			}
		}
		
		/*4. CHECK FOR INCL / EXCL DATA */
					
		$exclDays = $this->getExcludedDays($estimationOptions);
		$exclHours = $this->getExcludedHours($estimationOptions);
		
		
		/*5. RETURN RESULT */
		$COMP = $estimationOptions->temperature_selected_value_type;
		
		$temperatureMargin = $estimationOptions->temperature_margin ?? 0;
		$temperatureMarginMax = $estimationOptions->temperature_margin_max ?? 0;
		$sql = "SELECT 
				$supplierID,
				$customerID,
				P.pod,
				tdates.datetime,
				'temperatura',
				CAST(SUM(COALESCE(A.far_ea,B.far_ea)) AS DECIMAL(20, 8)) AS far_ea
				FROM tdates_{$estimationOptions->wfdProfile} AS tdates
				JOIN (SELECT DISTINCT pod FROM $rawDataTable far) P
				LEFT JOIN  
				(			
				SELECT 
					far.supplier_id,
					far.customer_id,
					far.pod,
					far.county_code,
					far.far_datetime,
					tdates.datetime,
					far.dayIndex,
					$COMP(CAST(far.far_ea AS DECIMAL(20, 8))) AS far_ea 
				FROM $rawDataTable far
				JOIN tdates_{$estimationOptions->wfdProfile} AS tdates ON
				far.county_code = tdates.county_code AND far.dayIndex = tdates.dayIndex AND far.interval = tdates.interval AND
				ABS(far.{$estimationOptions->temperature_source} - tdates.{$estimationOptions->temperature_source})<=$temperatureMargin
				AND $intervalMinWhr $exclDays $exclHours $podWhere
				GROUP BY far.pod, tdates.datetime ) A ON tdates.datetime = A.datetime AND tdates.county_code = A.county_code AND P.pod = A.pod
				LEFT JOIN  
				(			
				SELECT 
					far.supplier_id,
					far.customer_id,
					far.pod,
					far.county_code,
					far.far_datetime,
					tdates.datetime,
					far.dayIndex,
					$COMP(CAST(far.far_ea AS DECIMAL(20, 8))) AS far_ea
				FROM $rawDataTable far
				JOIN tdates_{$estimationOptions->wfdProfile} AS tdates ON 
				far.county_code = tdates.county_code AND far.dayIndex = tdates.dayIndex  AND far.interval = tdates.interval AND
				ABS(far.{$estimationOptions->temperature_source} - tdates.{$estimationOptions->temperature_source})<=$temperatureMarginMax
				$exclDays $exclHours $podWhere
				GROUP BY far.pod, tdates.datetime ) B ON tdates.datetime = B.datetime AND tdates.county_code = B.county_code AND P.pod = B.pod AND A.datetime is null
				WHERE coalesce(A.pod,B.pod) is not null
				GROUP BY tdates.datetime, P.pod";

		if($history)
		{
			$ret['estimatedSQLValues'] = $sql;
			
			$ret['historySQLValues'] = "
				SELECT 
				coalesce(A.far_datetime,B.far_datetime) as far_datetime,
				coalesce(A.far_ea,B.far_ea) as far_ea
				FROM tdates_{$estimationOptions->wfdProfile} AS tdates
				LEFT JOIN
				(SELECT
					far.far_datetime AS far_datetime,
					cast(SUM(far.far_ea) AS DECIMAL(20, 8))  AS far_ea,
					far.county_code,
					tdates.datetime
					FROM tdates_{$estimationOptions->wfdProfile} AS tdates
					JOIN $rawDataTable far ON
					far.county_code = tdates.county_code AND far.dayIndex = tdates.dayIndex  AND far.interval = tdates.interval AND
					ABS(far.{$estimationOptions->temperature_source} - tdates.{$estimationOptions->temperature_source})<=$temperatureMargin
					AND $intervalMinWhr $exclDays $exclHours $podWhere
					GROUP BY hour(far.far_datetime), date(far.far_datetime)) A ON tdates.datetime = A.datetime AND tdates.county_code = A.county_code
				LEFT JOIN
				(SELECT
					far.far_datetime AS far_datetime,
					cast(SUM(far.far_ea) AS DECIMAL(20, 8)) AS far_ea ,
					far.county_code,
					tdates.datetime
					FROM tdates_{$estimationOptions->wfdProfile} AS tdates
					JOIN $rawDataTable far ON
					far.county_code = tdates.county_code AND far.dayIndex = tdates.dayIndex  AND far.interval = tdates.interval AND
					ABS(far.{$estimationOptions->temperature_source} - tdates.{$estimationOptions->temperature_source})<=$temperatureMarginMax
					$exclDays $exclHours $podWhere
					GROUP BY HOUR(far.far_datetime), DATE(far.far_datetime) ) B ON tdates.datetime = B.datetime  AND tdates.county_code = B.county_code AND A.datetime is null
				where coalesce(A.far_datetime,B.far_datetime) is not null";
				//ORDER BY HOUR(coalesce(A.far_datetime,B.far_datetime)), DATE(coalesce(A.far_datetime,B.far_datetime))";

			$ret['historyInterval'] = "
				SELECT distinct(date(far_datetime)) as value FROM (
				 SELECT
					far.far_datetime AS far_datetime
					FROM tdates_{$estimationOptions->wfdProfile} AS tdates
					JOIN $rawDataTable far ON
					far.county_code = tdates.county_code AND far.dayIndex = tdates.dayIndex AND far.interval = tdates.interval AND
					ABS(far.{$estimationOptions->temperature_source} - tdates.{$estimationOptions->temperature_source})<=$temperatureMargin
					AND $intervalMinWhr $exclDays $exclHours $podWhere
					GROUP BY hour(far.far_datetime), date(far.far_datetime)
				union
				 SELECT
					far.far_datetime AS far_datetime
					FROM tdates_{$estimationOptions->wfdProfile} AS tdates
					JOIN $rawDataTable far ON
					far.county_code = tdates.county_code AND far.dayIndex = tdates.dayIndex  AND far.interval = tdates.interval AND
					ABS(far.{$estimationOptions->temperature_source} - tdates.{$estimationOptions->temperature_source})<=$temperatureMarginMax
					$exclDays $exclHours $podWhere
					GROUP BY HOUR(far.far_datetime), DATE(far.far_datetime) ) A";
					//ORDER BY HOUR(A.far_datetime), DATE(A.far_datetime)" ;
			
			$ret['historyType'] = "istorice";			
			//log_message('error',print_r($ret,true));
			return $ret;
		}
		
		return $sql;
	}
	
	private function selectProsumatorHistoryValues($eStart, $eEnd, $estimationOptions, $excludePODs, $history = false)
	{
		/* 
			DAY MAPPING
			
			Luni, Vineri, Sambata, Duminica => unice
			Marti, Miecuri, Joi => compatibile
			
			Craciun => Duminica
			Paste => Duminica
			Rusalii => Duminica
			
		*/

		/*1. PREPARE RAW DATA FILTERS*/
		
		log_message('info', print_r($estimationOptions, true));	

		$supplierID = $this->supplierID;
		$customerID = $estimationOptions->customer;
		

		$marginDays = 0;	//outliers not needed	
		
		if($estimationOptions->prod_days_margin == 0)
		{
			$rawStart = \DateTime::createFromFormat('Y-m-d',$eStart)->format('Y-m-01');
			$rawEnd = \DateTime::createFromFormat('Y-m-d',$eEnd)->format('Y-m-t');
		
			$intervalWhr = $this->getDateRangeWhere($customerID,$supplierID, $rawStart, $rawEnd, $marginDays);
			$intervalMinWhr = $intervalWhr;
			
			$rawDataIntervalStart = \DateTime::createFromFormat('Y-m-d',$rawStart)->modify("-$marginDays days")->format('Y-m-d');
			$rawDataIntervalEnd = \DateTime::createFromFormat('Y-m-d',$rawEnd)->modify("$marginDays days")->format('Y-m-d');
		}
		else
		{
			//max interval
			$marginDaysMax = $marginDays + $estimationOptions->prod_days_margin_max;
			$intervalWhr = $this->getDateRangeWhere($customerID,$supplierID, $eStart, $eEnd, $marginDaysMax);
			
			//min interval
			$marginDaysMin = $marginDays + $estimationOptions->prod_days_margin;
			$intervalMinWhr = $this->getDateRangeWhere($customerID,$supplierID, $eStart, $eEnd, $marginDaysMin);
			
			$rawDataIntervalStart = \DateTime::createFromFormat('Y-m-d',$eStart)->modify("-$marginDaysMax days")->format('Y-m-d');
			$rawDataIntervalEnd = \DateTime::createFromFormat('Y-m-d',$eEnd)->modify("$marginDaysMax days")->format('Y-m-d');
		}
		
		//$intervalWhr = "(( month(far.far_datetime)= MONTH('$rawDataIntervalStart') AND DAY (far.far_datetime) >= DAY('$rawDataIntervalStart') ) AND
		//		( month(far.far_datetime)= MONTH('$rawDataIntervalEnd') AND DAY (far.far_datetime) <= DAY('$rawDataIntervalEnd') ))";
		
		
		$podOption = '';
		if(isset($estimationOptions->pod)) 
		{
			//$selPOD = "'".$estimationOptions->pod."',";
			$podWhere = "AND far.pod = '".$estimationOptions->pod."'";
			$podOption = $estimationOptions->pod;
			//$groupBY = 'fc.pod,';
		}
		else 
		{
			//$selPOD = "'' as pod,";
			$podWhere = '';
			//$groupBY = '';
		}
		
		$exclPODs = '';
		if(!empty($excludePODs)) 
			$exclPODs = "AND far.pod not in ($excludePODs)";
		
		$maxMonthsWhr = "";
		if($estimationOptions->max_months_before)
		{
			$mxd = \DateTime::createFromFormat('Y-m-d',substr($eStart,0,-2).'01');
			$maxMonths = $mxd->modify("-{$estimationOptions->max_months_before} month")->format('Y-m-d');
			$maxMonthsWhr = " AND far.dt >= '$maxMonths' ";
		}

		/*3. CHECK FOR TEMPERATURES */

		$tempCheckEnd = min($rawDataIntervalEnd, $eEnd);
		$intervalHours = (int)((\DateTime::createFromFormat('Y-m-d H:i:s', $tempCheckEnd.' 23:00:00')->getTimestamp() - \DateTime::createFromFormat('Y-m-d H:i:s', $rawDataIntervalStart.' 00:00:00')->getTimestamp()) / 3600) + 1;

		if($estimationOptions->temperature_source != "temperature")
			$tempCount = (int)$this->getValue("SELECT count(*) as value FROM weather_data WHERE layer = '{$estimationOptions->temperature_source}' AND county_code='B' AND weather_datetime BETWEEN '$rawDataIntervalStart 00:00:00' AND '$tempCheckEnd 23:00:00'");
		else
			$tempCount = (int)$this->countTemperatures($rawDataIntervalStart.' 00:00:00', $tempCheckEnd.' 23:00:00');

		if($tempCount < $intervalHours)
		{
			$this->errorCollection[] = "Nu exista temperaturi ({$estimationOptions->temperature_source}) pentru intervalul istoric {$rawDataIntervalStart} - {$tempCheckEnd}!";
			return '';
		}

		/*2. CREATE RAW DATA TABLE*/
		$this->dropTable("readata");		
		
		if($this->hasSynthetics($eStart, $eEnd, $estimationOptions, $excludePODs, 'absolut'))
		{
			$synthIntervalWhr = str_replace('far.dt','date(fs.synthetics_datetime)',$intervalWhr);
			$sql = "CREATE TEMPORARY TABLE readata 
					(PRIMARY KEY (far_id),
					INDEX region_id_key (region_id),
					INDEX far_datetime_key (far_datetime),
					INDEX pod_key (pod))
					SELECT $customerID as customer_id, 1 as supplier_id, far.far_id, far.pod, CAST(CONCAT(far.dh,':00:00') AS datetime) AS far_datetime, sum(far_ea) as far_ea, far.dt, pp.region_id, pp.ea as prod_ea, case when ppt.ea=0 then 0 else pp.ea / ppt.ea end as far_typical_per,
					case 
					when wfd.mapping IS NOT NULL then wfd.mapping
					when DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3 
					ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex  
					FROM 
					( SELECT 
						ROW_NUMBER() OVER (ORDER BY synthetics_datetime) AS far_id,
						'sintetic' AS pod,
						DATE_ADD(fs.synthetics_datetime, INTERVAL I.minute MINUTE) AS far_datetime,
						fs.synthetic_ea / 4 AS far_ea,
						concat(cast(`synthetics_datetime` as date),' ',hour(`synthetics_datetime`)) as dh,							
						DATE(fs.synthetics_datetime) as dt,
						floor((hour(fs.synthetics_datetime) * 60 + I.minute) / 15) as `interval`
						FROM forecast_synthetics fs
						JOIN 
						 (SELECT 0 AS minute
						  UNION SELECT 15
						  UNION SELECT 30
						  UNION SELECT 45) I
						ON fs.customer_id = $customerID 
						WHERE date(fs.synthetics_datetime) > '$maxMonths' AND $synthIntervalWhr AND date(fs.synthetics_datetime)<'$eStart'
						) far
					JOIN customers c ON c.customer_id = $customerID
					LEFT JOIN pods p ON far.pod = p.pod_no 
					JOIN procast_region_counties prc ON p.county = prc.county OR (p.county IS NULL AND prc.county = c.customer_county)
					JOIN procast_prod_forecast pp ON far.dh = pp.dh AND prc.region_id = pp.region_id
					JOIN procast_minimum_production pmp ON month(far.far_datetime) = pmp.month AND hour(far.far_datetime) = pmp.hour AND pp.ea >= pmp.ea
					JOIN procast_prod_typical ppt on month(far.far_datetime) = ppt.month AND day(far.far_datetime) = ppt.day AND hour(far.far_datetime) = ppt.hour
					LEFT JOIN working_free_days wfd ON  wfd.wfd_profile_id={$estimationOptions->wfdProfile} AND date(far.far_datetime) = wfd.free_date
					GROUP BY far.pod, far.dh";

			$this->writeData($sql);			
		}
		else
		{
			$sql = "CREATE TEMPORARY TABLE readata 
					(PRIMARY KEY (far_id),
					INDEX region_id_key (region_id),
					INDEX far_datetime_key (far_datetime),
					INDEX pod_key (pod))
					SELECT $customerID as customer_id, 1 as supplier_id, far.far_id, far.pod, CAST(CONCAT(far.dh,':00:00') AS datetime) AS far_datetime, sum(far_ea) as far_ea, far.dt, pp.region_id, pp.ea as prod_ea, case when ppt.ea=0 then 0 else pp.ea / ppt.ea end as far_typical_per,
					case 
					when wfd.mapping IS NOT NULL then wfd.mapping
					when DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3 
					ELSE DAYOFWEEK(far.far_datetime) END AS dayIndex  
					FROM customer_far_$customerID far
					JOIN customers c ON c.customer_id = $customerID
					LEFT JOIN pods p ON far.pod = p.pod_no 
					JOIN procast_region_counties prc ON p.county = prc.county OR (p.county IS NULL AND prc.county = c.customer_county)
					JOIN procast_prod_forecast pp ON far.dh = pp.dh AND prc.region_id = pp.region_id
					JOIN procast_minimum_production pmp ON month(far.far_datetime) = pmp.month AND hour(far.far_datetime) = pmp.hour AND pp.ea >= pmp.ea
					JOIN procast_prod_typical ppt on month(far.far_datetime) = ppt.month AND day(far.far_datetime) = ppt.day AND hour(far.far_datetime) = ppt.hour
					LEFT JOIN working_free_days wfd ON  wfd.wfd_profile_id={$estimationOptions->wfdProfile} AND date(far.far_datetime) = wfd.free_date
					WHERE far.dt<'$eStart' AND $intervalWhr $podWhere $exclPODs $maxMonthsWhr
					GROUP BY far.pod, far.dh";

			$this->writeData($sql);
		}
		/*4. CHECK FOR DATA ??*/
					
		$exclDays = $this->getExcludedDays($estimationOptions,'teadates.datetime');
		
		/*5. RETURN RESULT */
		$prodMargin = $estimationOptions->prod_margin ?? 0;
		$prodMarginMax = $estimationOptions->prod_margin_max ?? 0;
		
		if($estimationOptions->prod_estimation_type == "AVG" || $estimationOptions->prod_estimation_type == "MIN" || $estimationOptions->prod_estimation_type == "MAX")
		{
			$sql = "SELECT 
					$supplierID, 
					$customerID, 
					A.pod, 
					DATE_ADD(A.datetime, INTERVAL I.minute MINUTE) as datetime,
					'prosumator', 
					A.far_ea / 4 as far_ea
					FROM (
					SELECT P.pod, teadates.datetime, CAST(SUM(COALESCE(A.far_ea,B.far_ea)) AS DECIMAL(20, 8)) AS far_ea
					FROM teadates_{$estimationOptions->wfdProfile} AS teadates
					JOIN (SELECT DISTINCT pod FROM readata far) P
					LEFT JOIN  
					(			
					SELECT 
						far.supplier_id,
						far.customer_id,
						far.pod,
						far.far_datetime,
						teadates.datetime,
						far.dayIndex,
						{$estimationOptions->prod_estimation_type}(CAST(far.far_ea AS DECIMAL(20, 8))) AS far_ea 
					FROM readata far
					JOIN teadates_{$estimationOptions->wfdProfile} AS teadates ON far.region_id = teadates.region_id AND
					far.dayIndex = teadates.dayIndex AND HOUR(far.far_datetime) = HOUR(teadates.datetime) AND
					ABS(far.prod_ea - teadates.ea)<=$prodMargin
					AND $intervalMinWhr $exclDays
					GROUP BY far.pod, teadates.datetime ) A ON teadates.datetime = A.datetime AND P.pod = A.pod
					LEFT JOIN  
					(			
					SELECT 
						far.supplier_id,
						far.customer_id,
						far.pod,
						far.far_datetime,
						teadates.datetime,
						far.dayIndex,
						{$estimationOptions->prod_estimation_type}(CAST(far.far_ea AS DECIMAL(20, 8))) AS far_ea
					FROM readata far
					JOIN teadates_{$estimationOptions->wfdProfile} AS teadates ON far.region_id = teadates.region_id AND
					far.dayIndex = teadates.dayIndex AND HOUR(far.far_datetime) = HOUR(teadates.datetime) AND
					ABS(far.prod_ea - teadates.ea)<=$prodMarginMax
					$exclDays
					GROUP BY far.pod, teadates.datetime ) B ON teadates.datetime = B.datetime AND P.pod = B.pod AND A.datetime is null
					WHERE teadates.ea >= teadates.minEA
					GROUP BY teadates.datetime, P.pod ) A
					JOIN 
					 (SELECT 0 AS minute
					  UNION SELECT 15
					  UNION SELECT 30
					  UNION SELECT 45) I ON TRUE";
		}
		else
		{
			$sql = "SELECT 
					$supplierID, 
					$customerID, 
					A.pod, 
					DATE_ADD(A.datetime, INTERVAL I.minute MINUTE) as datetime,
					'prosumator', 
					A.far_ea / 4 as far_ea
					FROM (
					SELECT D.pod, teadates.datetime,'prosumator', CAST(SUM(D.far_ea) AS DECIMAL(20, 8)) AS far_ea
				FROM teadates_{$estimationOptions->wfdProfile} AS teadates				
				JOIN (SELECT teadates.datetime, P.pod,	(SELECT
														far.far_ea
														FROM readata far
														WHERE far.pod = P.pod AND far.region_id = teadates.region_id AND far.dayIndex = teadates.dayIndex AND HOUR(far.far_datetime) = HOUR(teadates.datetime) 
														AND teadates.fcast_typical_per> 0 AND far.far_typical_per>0 
														AND $intervalMinWhr $exclDays
														ORDER BY ABS(teadates.fcast_typical_per - far.far_typical_per) ASC, far.far_ea ASC LIMIT 1
														) AS far_ea
						FROM teadates_{$estimationOptions->wfdProfile} AS teadates
						JOIN (SELECT DISTINCT pod, region_id FROM readata) P ON P.region_id = teadates.region_id ) D ON teadates.datetime = D.datetime
				WHERE teadates.ea >= teadates.minEA AND D.far_ea IS NOT null
				GROUP BY teadates.datetime, D.pod ) A
				JOIN 
				 (SELECT 0 AS minute
				  UNION SELECT 15
				  UNION SELECT 30
				  UNION SELECT 45) I ON TRUE";	
		}
		
		if($history)
		{
			$ret['estimatedSQLValues'] = $sql;
			
			if ($estimationOptions->prod_estimation_type == "AVG" || $estimationOptions->prod_estimation_type == "MIN" || $estimationOptions->prod_estimation_type == "MAX")
			{
				$ret['historySQLValues'] = "
					SELECT 
					coalesce(A.far_datetime,B.far_datetime) as far_datetime,
					coalesce(A.far_ea,B.far_ea) as far_ea
					FROM teadates_{$estimationOptions->wfdProfile} AS teadates
					LEFT JOIN
					(SELECT
						far.far_datetime AS far_datetime,
						cast(SUM(far.far_ea) AS DECIMAL(20, 8))  AS far_ea,
						teadates.datetime
						FROM teadates_{$estimationOptions->wfdProfile} AS teadates
						JOIN readata far ON far.region_id = teadates.region_id AND
						teadates.ea >= teadates.minEA AND far.dayIndex = teadates.dayIndex AND HOUR(far.far_datetime) = HOUR(teadates.datetime) AND
						ABS(far.prod_ea - teadates.ea)<=$prodMargin
						AND $intervalMinWhr $exclDays
						GROUP BY hour(far.far_datetime), date(far.far_datetime)) A ON teadates.datetime = A.datetime
					LEFT JOIN
					(SELECT
						far.far_datetime AS far_datetime,
						cast(SUM(far.far_ea) AS DECIMAL(20, 8)) AS far_ea ,
						teadates.datetime
						FROM teadates_{$estimationOptions->wfdProfile} AS teadates
						JOIN readata far ON far.region_id = teadates.region_id AND
						teadates.ea >= teadates.minEA AND far.dayIndex = teadates.dayIndex AND HOUR(far.far_datetime) = HOUR(teadates.datetime) AND
						ABS(far.prod_ea - teadates.ea)<=$prodMarginMax
						$exclDays
						GROUP BY HOUR(far.far_datetime), DATE(far.far_datetime) ) B ON teadates.datetime = B.datetime AND A.datetime is null
					where teadates.ea >= teadates.minEA AND coalesce(A.far_datetime,B.far_datetime) is not null";
					//ORDER BY HOUR(coalesce(A.far_datetime,B.far_datetime)), DATE(coalesce(A.far_datetime,B.far_datetime))";
			
				$ret['historyInterval'] = "
					SELECT distinct(date(far_datetime)) as value FROM (
					 SELECT
						far.far_datetime AS far_datetime
						FROM teadates_{$estimationOptions->wfdProfile} AS teadates
						JOIN readata far ON far.region_id = teadates.region_id AND
						teadates.ea >= teadates.minEA AND far.dayIndex = teadates.dayIndex AND HOUR(far.far_datetime) = HOUR(teadates.datetime) AND
						ABS(far.prod_ea - teadates.ea)<=$prodMargin
						AND $intervalMinWhr $exclDays
						GROUP BY hour(far.far_datetime), date(far.far_datetime)
					union
					 SELECT
						far.far_datetime AS far_datetime
						FROM teadates_{$estimationOptions->wfdProfile} AS teadates
						JOIN readata far ON far.region_id = teadates.region_id AND
						teadates.ea >= teadates.minEA AND far.dayIndex = teadates.dayIndex AND HOUR(far.far_datetime) = HOUR(teadates.datetime) AND
						ABS(far.prod_ea - teadates.ea)<=$prodMarginMax
						$exclDays
						GROUP BY HOUR(far.far_datetime), DATE(far.far_datetime) ) A";
						//ORDER BY HOUR(A.far_datetime), DATE(A.far_datetime)" ;
						
									//process alt estimate	
					$tSql = "SELECT distinct hour(datetime) as value FROM teadates_{$estimationOptions->wfdProfile} AS teadates where ea < teadates.minEA UNION
							 SELECT distinct hour(datetime) as value from ($sql) X WHERE X.far_ea IS null";
			}
			else
			{
				$ret['historySQLValues'] = "
					SELECT far_datetime, far_ea FROM teadates_{$estimationOptions->wfdProfile} AS teadates
					JOIN readata far ON far.region_id = teadates.region_id AND far.dayIndex = teadates.dayIndex AND HOUR(far.far_datetime) = HOUR(teadates.datetime) AND
					teadates.fcast_typical_per> 0 AND far.far_typical_per>0 AND teadates.ea >= teadates.minEA
					AND $intervalMinWhr $exclDays";
					
				$ret['historyInterval'] = "
					SELECT distinct(date(far_datetime)) FROM teadates_{$estimationOptions->wfdProfile} AS teadates
					JOIN readata far ON far.region_id = teadates.region_id AND far.dayIndex = teadates.dayIndex AND HOUR(far.far_datetime) = HOUR(teadates.datetime) AND 
					teadates.fcast_typical_per> 0 AND far.far_typical_per>0 AND teadates.ea >= teadates.minEA
					AND $intervalMinWhr $exclDays";
			
			
				//process alt estimate	
				$tSql = "SELECT distinct hour(teadates.datetime) as value from teadates_{$estimationOptions->wfdProfile} AS teadates LEFT JOIN ($sql) X ON teadates.datetime = X.datetime WHERE X.datetime IS NULL OR teadates.ea < teadates.minEA ";
				
			}
			
			$estimationOptions = $this->assignEstimateIDToCustomer($estimationOptions->prod_alt_estimate,$estimationOptions);
			$estimationOptions->hourly_interval = implode(",",$this->getValueArray($tSql));
					
			$ret['historyType'] = "istorice";			
			
			

			
			
			$hv = $this->selectHistoryValues($eStart, $eEnd, $estimationOptions, $excludePODs,true);
			
			if(!empty($hv))
			{
				$ret['estimatedSQLValues'] = $ret['estimatedSQLValues']." UNION ".$hv['estimatedSQLValues'];
				$ret['historySQLValues'] = $ret['historySQLValues']." UNION ".$hv['historySQLValues'];
				$ret['historyInterval'] = $ret['historyInterval']." UNION ".$hv['historyInterval'];
			}
			//log_message('error',print_r($ret,true));
			
			return $ret;
		}
		else
		{
			//process alt estimate	
			$tSQL = "SELECT hour(t.datetime) as hour, X.pod from teadates_{$estimationOptions->wfdProfile} t LEFT JOIN ($sql) X ON date(t.datetime)=date(X.datetime) AND HOUR(t.datetime)=hour(X.datetime) WHERE X.far_ea IS NULL GROUP BY X.pod,hour(t.datetime)";
			log_message("error",$tSQL);
			$altPODs = $this->getArray($tSQL);
			
			$interval = [];
			$pod = "";
			foreach($altPODs as $ap)
			{
				if($ap['pod']!=$pod)
				{
					if(!empty($interval))
					{
						$estimationOptions2 = $this->assignEstimateIDToCustomer($estimationOptions->prod_alt_estimate,$estimationOptions);
						$estimationOptions2->hourly_interval = implode(",",$interval);
						if(!empty($pod))
							$estimationOptions2->pod = $pod;
						elseif (isset($estimationOptions2->pod))
							unset($estimationOptions2->pod);
						
						$this->rawDataCounter++;
						$altSql = $this->selectHistoryValues($eStart, $eEnd, $estimationOptions2, $excludePODs);
						
						if(!empty($altSql)) $sql .= " UNION ".$altSql;
					}
					
					$pod = $ap['pod'];
					$interval = [];
				}
				
				$interval[] = $ap['hour'];
			}
			
			if(!empty($interval))
			{
				$estimationOptions2 = $this->assignEstimateIDToCustomer($estimationOptions->prod_alt_estimate,$estimationOptions);
				$estimationOptions2->hourly_interval = implode(",",$interval);
				if(!empty($pod))
					$estimationOptions2->pod = $pod;
				elseif (isset($estimationOptions2->pod)) 
					unset($estimationOptions2->pod);
				
				$this->rawDataCounter++;
				$altSql = $this->selectHistoryValues($eStart, $eEnd, $estimationOptions2, $excludePODs);
				
				if(!empty($altSql)) $sql .= " UNION ".$altSql;
			}
			
			/*$tSql = "SELECT distinct hour(datetime) as value from teadates where ea < teadates.minEA UNION
					 SELECT distinct hour(datetime) as value from ($sql) X WHERE X.far_ea IS null";
					 
			log_message("error",$tSql);
			$estimationOptions = $this->assignEstimateIDToCustomer($estimationOptions->prod_alt_estimate,$estimationOptions);
			$estimationOptions->hourly_interval = implode(",",$this->getValueArray($tSql));
			
			$altSql = $this->selectHistoryValues($eStart, $eEnd, $estimationOptions, $excludePODs);
			
			if(!empty($altSql)) $sql .= " UNION ".$altSql;
			*/
		}
		
		
		
		return $sql;
	}
	
	private function hasSynthetics($eStart, $eEnd, $estimationOptions, $exclPODs, $synthType='absolut')
	{
		
		if($estimationOptions->ignore_synthetics && $synthType == 'absolut') return false;
		
		$sql = '';
		
		/*
			1. COPY CONSUMPTIONS AT CUSTOMER LEVEL FROM SYNTHETICS TO FORECAST
		*/
		
		//log_message('error', print_r($estimationOptions, true));	
		$customerID = $estimationOptions->customer;
		
		$marginDays = 0;
		
		//sursa: SYNTHETICS
		//selecteaza datele pe zile din ultimul an fara sa considere zilele libere sau temperatura
		
		/*$sql = "SELECT fs.fs_id as value
				FROM tdates
				JOIN  forecast_synthetics fs
				ON month(fs.synthetics_datetime) = month(tdates.datetime) AND day(fs.synthetics_datetime) = day(tdates.datetime) AND hour(fs.synthetics_datetime) = hour(tdates.datetime) and fs.customer_id = $customerID
				GROUP BY fs.customer_id, tdates.datetime limit 1";
		
		return !empty($this->getValue($sql));
		*/
		$_synthDate = \DateTime::createFromFormat('Y-m-d',$eStart);
		$synthYear = $_synthDate->format('Y')-1;
		$synthMonth = $_synthDate->format('m');
		
		$sql = "SELECT fs.fst_id as value
				FROM forecast_synthetics_type fs
				WHERE fs.month = $synthMonth AND fs.year = $synthYear AND fs.customer_id = $customerID AND fs.synthetic_type = '$synthType'";
				
		return !empty($this->getValue($sql));
	}
		
	private function insertHistoryValues($sqlValues)
	{
		$this->writeData('CREATE TEMPORARY TABLE IF NOT EXISTS forecast_temp_history LIKE forecast_history');
		$sql = "INSERT INTO forecast_temp_history (far_datetime, far_ea) " . $sqlValues;
		return $this->writeData($sql);
	}
	
	private function insertEstimatedPODValues($sqlValues, $temp=false)
	{
		if(empty($sqlValues)) return 0;
		
		if($temp)
		{
			$this->writeData("CREATE TEMPORARY TABLE IF NOT EXISTS forecast_temp_pods_estimates LIKE forecast_pods_estimates");
			$sql = "INSERT INTO forecast_temp_pods_estimates (supplier_id, customer_id, pod, forecast_datetime, estimation_type, forecast_ea) " . $sqlValues ." ON DUPLICATE KEY UPDATE forecast_ea=VALUES(forecast_ea)";
		}
		else
			$sql = "INSERT INTO forecast_pods_estimates (supplier_id, customer_id, pod, forecast_datetime, estimation_type, forecast_ea) " . $sqlValues ." ON DUPLICATE KEY UPDATE forecast_ea=VALUES(forecast_ea)";
		
		return $this->writeData($sql);
	}
	
	private function filterEstimatedPODValues($eStart, $eEnd, $temp=false, $customerIDs = null)
	{
		if($temp)
			$tableName = "forecast_temp_pods_estimates";
		else
			$tableName = "forecast_pods_estimates";
		
		$sql = "SELECT CONCAT_WS(' / ','Filtrat',c.customer_name, fpe.pod, DATE(fpe.forecast_datetime), SUM(fpe.forecast_ea), 
				case when c.customer_status != 'Activ' then 'Client inactiv' 
				when cct.contract_id IS NULL then 'Contract client inchis'
				when sr.service_rate_id IS NOT NULL AND pct.contract_id IS NULL then 'Contract POD inchis' END) AS value
				FROM $tableName fpe
				JOIN customers c ON fpe.customer_id = c.customer_id
                LEFT JOIN contracts cct ON fpe.customer_id = cct.customer_id AND 
					DATE(fpe.forecast_datetime)>=cct.contract_date AND (cct.contract_stop IS NULL OR cct.contract_stop >= DATE(fpe.forecast_datetime))
				LEFT JOIN pods p ON fpe.pod = p.pod_no
				LEFT JOIN service_rates sr ON sr.pod_id = p.pod_id
				LEFT JOIN contracts pct ON sr.contract_id = pct.contract_id
					AND DATE(fpe.forecast_datetime)>=pct.contract_date AND (pct.contract_stop IS NULL OR pct.contract_stop >= DATE(fpe.forecast_datetime))
				WHERE date(fpe.forecast_datetime) BETWEEN '$eStart' AND '$eEnd'
				AND (c.customer_status != 'Activ' OR cct.contract_id IS NULL OR (sr.service_rate_id IS NOT NULL AND pct.contract_id IS NULL))
				GROUP BY fpe.customer_id, fpe.pod, DATE(fpe.forecast_datetime)
				ORDER BY c.customer_name, fpe.pod";
		
		$result = $this->getValueArray($sql);
		
		if(!empty($result))
		{
			$sql = "DELETE fpe FROM $tableName fpe 
                  JOIN customers c ON fpe.customer_id = c.customer_id
                  LEFT JOIN contracts cct
                  ON fpe.customer_id = cct.customer_id AND 
						DATE(fpe.forecast_datetime)>=cct.contract_date AND (cct.contract_stop IS NULL OR cct.contract_stop >= DATE(fpe.forecast_datetime))
						LEFT JOIN pods p ON fpe.pod = p.pod_no
						LEFT JOIN service_rates sr ON sr.pod_id = p.pod_id
						LEFT JOIN contracts pct ON sr.contract_id = pct.contract_id
                            AND DATE(fpe.forecast_datetime)>=pct.contract_date AND (pct.contract_stop IS NULL OR pct.contract_stop >= DATE(fpe.forecast_datetime))
                                WHERE date(fpe.forecast_datetime) BETWEEN '$eStart' AND '$eEnd'
                                AND (c.customer_status != 'Activ' OR cct.contract_id IS NULL OR (sr.service_rate_id IS NOT NULL AND pct.contract_id IS NULL))";
			
			$this->writeData($sql);
		}

		return $result;
				
	}
	
	private function insertEstimatedValues($eStart, $eEnd, $temp=false, $customerIDs = null)
	{	
		$cWhr = "";
		if($customerIDs != NULL)
			$cWhr .= " customer_id IN (".implode(',', array_map('intval', $customerIDs)).") AND ";
		
		if($temp)
		{
			//$this->writeData("TRUNCATE forecast_history");		
			//$sql = "INSERT INTO forecast_history SELECT * FROM forecast_temp_history";
			//$this->writeData($sql);
			
			$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS forecast_temp_estimates AS SELECT forecast_datetime, cast(greatest(sum(forecast_ea),0) AS DECIMAL(20,8)) as forecast_ea FROM forecast_temp_pods_estimates
				WHERE $cWhr date(forecast_datetime) BETWEEN '$eStart' AND '$eEnd' and forecast_ea is not null GROUP BY customer_id, forecast_datetime";
		}
		else
			$sql = "INSERT INTO forecast_estimates (supplier_id, customer_id, forecast_datetime, forecast_ea) SELECT supplier_id, customer_id, forecast_datetime, cast(greatest(sum(forecast_ea),0) AS DECIMAL(20,8)) as forecast_ea FROM forecast_pods_estimates
				WHERE $cWhr date(forecast_datetime) BETWEEN '$eStart' AND '$eEnd' and forecast_ea is not null GROUP BY customer_id, forecast_datetime
				ON DUPLICATE KEY UPDATE forecast_ea=VALUES(forecast_ea)";
		
		return $this->writeData($sql);
	}
	
	private function prepareCorrelatedMonths($eStart, $eEnd)
	{
		$estimatedMonth = substr($eStart,5,2);
		$this->correlatedMonths = $this->correlated_months = $this->getValueArray("SELECT correlated_month as value FROM months_correlation WHERE month = $estimatedMonth");
		log_message("info","Lunile corelate:".implode(',	',$this->correlatedMonths));
	}
	
	private function prepareSunTable($eStart, $eEnd)
	{
		$masterDataModel = new \App\Models\MasterDataModel();
		 
		$year = substr($eStart,0,4);
		$estimatedMonth = substr($eStart,5,2);
		$res = $this->getValue("SELECT date as value FROM forecast_sun WHERE date = '$eStart'");
		if(empty($res))
		{
			log_message("error","Insert sun data for $eStart");
			$sqlValues = '';
			$sunData = $masterDataModel->getSunData($year,$estimatedMonth);
			foreach($sunData as $sd)
			{
				$sqlValues .= "('$year-$estimatedMonth-{$sd['day']}', {$sd['sunrise']}, {$sd['sunset']}),";
			}
			
			$sqlValues = rtrim($sqlValues,",");
			$this->writeData("INSERT IGNORE INTO forecast_sun (date, sunriseH, sunsetH) VALUES $sqlValues");
		}
		
		foreach($this->correlatedMonths as $cMonth)
		{
			$cYear = $year;
			if($cMonth >= $estimatedMonth) $cYear--;
			
			$res = $this->getValue("SELECT date as value FROM forecast_sun WHERE date = '$cYear-$cMonth-01'");
			if(empty($res))
			{
				log_message("error","Insert sun data for $cYear-$cMonth");
				$sqlValues = '';
				$sunData = $masterDataModel->getSunData($cYear,$cMonth);
				foreach($sunData as $sd)
				{
					$sqlValues .= "('$cYear-$cMonth-{$sd['day']}', {$sd['sunrise']}, {$sd['sunset']}),";
				}
				
				$sqlValues = rtrim($sqlValues,",");
				$this->writeData("INSERT IGNORE INTO forecast_sun (date, sunriseH, sunsetH) VALUES $sqlValues");
			}
		}
	}
	
	private function prepareTempTable($eStart, $eEnd, $customers)
	{
		/*
			1. CREATE TEMPORARY DATES TABLE (MEMORY)
			2. COPY READINGS IN TEMPORARY (MEMORY)
		*/
		
		$farStart = \DateTime::createFromFormat('Y-m-d H:i:s',$eStart.' 00:00:00');
		$farEnd = \DateTime::createFromFormat('Y-m-d H:i:s',$eEnd.' 23:45:00');
		$interval = $farEnd->diff($farStart);
		$values = '';
		
		while($farStart<=$farEnd)
		{
			$values.="('".$farStart->format('Y-m-d H:i:s')."'),";
			$farStart->add(new \DateInterval('PT15M'));
		}
		
		$values = substr($values,0,-1);
		
		$sql = "SELECT MAX(counter) as value FROM 
				(SELECT count(*) as counter FROM forecast_temperatures WHERE date(temperature_datetime) BETWEEN '$eStart' AND '$eEnd' UNION
				SELECT count(*) as counter FROM weather_data WHERE layer = 'temp' AND county_code='B' AND date(weather_datetime) BETWEEN '$eStart' AND '$eEnd' UNION
				SELECT count(*) as counter FROM weather_data WHERE layer = 'feelslike' AND county_code='B' AND date(weather_datetime) BETWEEN '$eStart' AND '$eEnd') X";
		log_message('debug',$sql);
		if($this->getValue($sql) != ($interval->days*24+$interval->h+1)) $this->errorCollection[]="Lipsa prognoza meteo/temperaturi in perioada $eStart - $eEnd";
		
		$sql = "SELECT wfd_profile_id as value FROM working_free_days_profiles WHERE supplier_id={$this->supplierID}";
		$wfdProfiles = $this->getValueArray($sql);
		
		
		$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS `tdates`
		(`datetime` DATETIME NOT NULL,
		`dh` VARCHAR(14) GENERATED ALWAYS AS (CONCAT(DATE(`datetime`), ' ', HOUR(`datetime`))) STORED,
		`dt` DATE GENERATED ALWAYS AS (DATE(`datetime`)) STORED,
		`interval` INT GENERATED ALWAYS AS (floor((hour(`datetime`) * 60 + minute(`datetime`)) / 15)) STORED,
		PRIMARY KEY (`datetime`) USING BTREE,
		INDEX idx_dh (`dh`) USING BTREE,
		INDEX idx_dt (`dt`) USING BTREE,
		INDEX idx_interval (`interval`) USING BTREE
		)";
		$this->writeData($sql);
		
		$sql = "INSERT IGNORE INTO tdates (datetime) VALUES $values";
		$this->writeData($sql);
			
		foreach($wfdProfiles as $wfdProfile)
		{
			$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS `tdates_$wfdProfile` (
					`datetime` DATETIME NOT NULL, 
					dayIndex INT NULL,
					county_code varchar(3) DEFAULT 'B',
					temperature decimal(20,2) NULL,
					temp decimal(20,2) NULL,
					feelslike decimal(20,2) NULL,
					sunriseH INT NULL,
					sunsetH INT NULL,
					daylight INT NULL,
					dh varchar(14) NULL,
					`dt` DATE NULL,
					`interval` INT NULL,
					PRIMARY KEY (`datetime`, `county_code`) USING BTREE ,
					INDEX `dayIndex` (`dayIndex`) USING BTREE,
					INDEX `county_code` (`county_code`) USING BTREE,
					INDEX idx_dh (`dh`) USING BTREE,
					INDEX idx_dt (`dt`) USING BTREE,
					INDEX idx_interval (`interval`) USING BTREE
					) ENGINE=memory";
			$this->writeData($sql);
			
			$sql = "INSERT INTO tdates_$wfdProfile (`datetime`,dayIndex, county_code, temperature, temp, feelslike, sunriseH, sunsetH, daylight, dh, dt, `interval`)
					SELECT `datetime`, 
					case 
					when wfd.mapping IS NOT NULL then wfd.mapping
					when DAYOFWEEK(tdates.datetime) BETWEEN 3 AND 5 THEN 3 
					ELSE DAYOFWEEK(tdates.datetime) END as dayIndex,
					co.county_code,
					ft.temperature,
					wdt.value as temp,
					wdf.value as feelslike,
					fs.sunriseH,
					fs.sunsetH,
					case when hour(tdates.datetime)>=fs.sunriseH AND hour(tdates.datetime)<=fs.sunsetH THEN 1 ELSE 0 END as daylight,
					tdates.dh,
					tdates.dt,
					tdates.interval
					FROM tdates
					JOIN counties co
					LEFT JOIN working_free_days wfd ON tdates.dt = wfd.free_date and wfd.wfd_profile_id = $wfdProfile
					LEFT JOIN forecast_temperatures ft ON tdates.dh = ft.dh
					LEFT JOIN weather_data wdt ON wdt.layer = 'temp' AND wdt.county_code=co.county_code AND tdates.dh = wdt.dh 
					LEFT JOIN weather_data wdf ON wdf.layer = 'feelslike' AND wdf.county_code=co.county_code AND tdates.dh = wdf.dh
					JOIN forecast_sun fs on tdates.dt = fs.date
					";
			$this->writeData($sql);
		}
	}
	
	private function prepareEATables($eStart, $eEnd, $customers)
	{
		/*
			1. CREATE TEMPORARY DATES TABLE (MEMORY)
			2. COPY FORECAST IN TEMPORARY (MEMORY). Region Included!
		*/

		$customerIDs = implode(',', array_map('intval', $customers));
		
		$farStart = \DateTime::createFromFormat('Y-m-d H:i:s',$eStart.' 00:00:00');
		$farEnd = \DateTime::createFromFormat('Y-m-d H:i:s',$eEnd.' 23:00:00');
		$interval = $farEnd->diff($farStart);
		/*$values = '';
		
		while($farStart<=$farEnd)
		{
			$values.="('".$farStart->format('Y-m-d H:i:s')."'),";
			$farStart->add(new \DateInterval('PT1H'));
		}
		
		$values = substr($values,0,-1);*/
		
		$sql = "
		SELECT c.customer_name, p.customer_id, group_concat(distinct p.pod_no) AS pods, group_concat(distinct p.county) AS pods_counties, prc.region_id, pr.region_name, case when prc.region_id is not null then COUNT(DISTINCT prf.prod_datetime) ELSE 0 end AS intervalSize
		FROM pods p
		JOIN customers c ON p.customer_id = c.customer_id
		LEFT JOIN procast_region_counties prc ON p.county = prc.county
		LEFT JOIN procast_regions pr ON prc.region_id = pr.region_id
		LEFT JOIN procast_prod_forecast prf ON prf.region_id = prc.region_id AND date(prf.prod_datetime) BETWEEN '$eStart' AND '$eEnd'
		JOIN forecast_customers_consumption_types fcct ON (p.customer_id = fcct.customer_id AND fcct.pod ='') OR p.pod_no = fcct.pod
		JOIN forecast_consumption_types fct ON fcct.fct_id = fct.fct_id
		JOIN forecast_consumption_types_options fcto ON fct.fct_id = fcto.fct_id AND option_name = 'prosumator' AND fcto.value = 1
		WHERE c.supplier_id = 1 and c.customer_status = 'activ' and c.customer_id in ($customerIDs)
		GROUP BY c.customer_id, p.county, prc.region_id";

		$customerRegions = $this->getArray($sql);
		foreach($customerRegions as $cR)
		{
			if(empty($cR['region_id']))
				$this->errorCollection[]="Judetele {$cR['pods_counties']} ce apartin de {$cR['customer_name']} / {$cR['pods']} nu au regiunea alocata.";	
			
			if($cR['intervalSize'] != ($interval->days*24+$interval->h+1))
			{
				if(!isset($this->regionError[$cR['region_name']]))
				{
					$this->regionError[$cR['region_name']] = true;
					$this->errorCollection[]="Lipsa productie estimata in perioada $eStart - $eEnd, {$cR['region_name']}";	
				}
			}
		}
		
		
		$sql = "SELECT wfd_profile_id as value FROM working_free_days_profiles WHERE supplier_id={$this->supplierID}";
		$wfdProfiles = $this->getValueArray($sql);
		
		foreach($wfdProfiles as $wfdProfile)
		{
			$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS `teadates_$wfdProfile` 
					(	`datetime` DATETIME NOT NULL, 
						dayIndex INT NULL, 
						region_id INT NULL, 
						ea decimal(20,8) NULL,
						minEA decimal(20,3) DEFAULT '0.000',
						fcast_typical_per decimal(20,8) DEFAULT '0.00000000',
						PRIMARY KEY (`datetime`, region_id) USING BTREE ,
						INDEX `dayIndex` (`dayIndex`) USING BTREE,
						INDEX `region_id` (`region_id`) USING BTREE
					)
					SELECT prf.prod_datetime as datetime, case 
					when wfd.mapping IS NOT NULL then wfd.mapping
					when DAYOFWEEK(prf.prod_datetime) BETWEEN 3 AND 5 THEN 3 
					ELSE DAYOFWEEK(prf.prod_datetime) END AS dayIndex,
					prf.region_id, prf.ea, pmp.ea as minEA, case when ppt.ea=0 then 0 else cast(prf.ea / ppt.ea AS DECIMAL(20,8)) end as fcast_typical_per
					FROM procast_prod_forecast prf
					JOIN procast_minimum_production pmp ON month(prf.prod_datetime) = pmp.month and hour(prf.prod_datetime) = pmp.hour
					JOIN procast_prod_typical ppt ON ppt.region_id = prf.region_id and month(prf.prod_datetime) = ppt.month and day(prf.prod_datetime) = ppt.day and hour(prf.prod_datetime) = ppt.hour
					LEFT JOIN working_free_days wfd ON wfd.wfd_profile_id = $wfdProfile AND date(prf.prod_datetime) = wfd.free_date
					WHERE date(prf.prod_datetime) BETWEEN '$eStart' AND '$eEnd'";
				
			$this->writeData($sql);
		}
	}
	
	function assignEstimateToCustomer($estimateName, $customerID, $start, $end, $excludeManual = true)
	{
		
		$sql = "SELECT fcto.option_name, fcto.value FROM
				forecast_consumption_types fct
				JOIN forecast_consumption_types_options fcto ON fct.fct_id = fcto.fct_id AND fct.consumption_type_name='$estimateName'";
						
		$qResult = $this->getArray($sql);
		
		$ret = (object)['customer'=>$customerID];
		foreach($qResult as $r)
			$ret->{$r['option_name']} = $r['value'];
		
		$excludedDays = [];
		if($excludeManual)
		{
			$sql = "SELECT date as value FROM forecast_customers_manual_estimates WHERE customer_id = $customerID AND date BETWEEN '$start' AND '$end'"; 	
			$excludedDays = $this->getValueArray($sql);
		}
		
		$ret->excludedDays = $excludedDays;
		$ret->wfdProfile = $this->getCustomerWFDProfile($customerID);
		
		return  $ret;
	}

	function assignEstimateIDToCustomer($estimateID, $estimationOptions)
	{
		
		$sql = "SELECT fcto.option_name, fcto.value FROM
				forecast_consumption_types fct
				JOIN forecast_consumption_types_options fcto ON fct.fct_id = fcto.fct_id AND fct.fct_id = $estimateID";
						
		$qResult = $this->getArray($sql);
		
		$ret = (object)['customer'=>$estimationOptions->customer];
		foreach($qResult as $r)
			$ret->{$r['option_name']} = $r['value'];
		
		$ret->excludedDays = $estimationOptions->excludedDays;
				
		if(isset($estimationOptions->pod))
		{
			$ret->pod = $estimationOptions->pod;
			$ret->wfdProfile = $this->getCustomerWFDProfile($estimationOptions->customer,$estimationOptions->pod);
		}
		else
			$ret->wfdProfile = $this->getCustomerWFDProfile($estimationOptions->customer);	
		
		return  $ret;
	}
	
	function hasEstimates($customerID, $eStart, $eEnd)
	{
		$sql = "SELECT count(estimate_id)=(datediff('$eEnd','$eStart')+1) as value FROM (
						SELECT estimate_id FROM forecast_estimates fe
						WHERE fe.customer_id = $customerID AND fe.supplier_id = ".$this->supplierID." AND date(fe.forecast_datetime) BETWEEN '$eStart' AND '$eEnd'
						group by date(fe.forecast_datetime)) Z";

		return $this->getValue($sql);
	}
	
	function estimate($eStart, $eEnd, $customers, $customerType)
	{	
		log_message("info","Estimates started...");
		$benchmark = \Config\Services::timer();

		if(empty($customers))
			$customers = $this->getAllActiveCustomers($eStart, $eEnd, $customerType);
		
		$benchmark->start('prepare initial tables');
		$this->prepareEATables($eStart, $eEnd, $customers);
		$this->prepareTempTable($eStart, $eEnd, $customers);
		$this->prepareCorrelatedMonths($eStart,$eEnd);
		$this->prepareSunTable($eStart, $eEnd, $customers);
		$benchmark->stop('prepare initial tables');
			
		$estimatedCustomers = [];

		foreach($customers as $customerID)
		{
			$this->createTableLikeSource("customer_far",$customerID);//to be remove after deployment and move to create customer!
			$this->rawDataCounter = 0;

			$estimationOptions = $this->getEstimationOptions($customerID, $eStart, $eEnd );
			if($estimationOptions->manual && !$this->hasEstimates($customerID, $eStart, $eEnd))
				$estimationOptions = $this->assignEstimateToCustomer('General',$customerID, $eStart, $eEnd);

			if($estimationOptions->manual) continue;

			$pods = $this->getCustomersPODs($customerID);

			/*clean customer estimates*/
			$this->writeData("DELETE FROM forecast_pods_estimates WHERE customer_id=$customerID AND date(forecast_datetime) BETWEEN '$eStart' AND '$eEnd'");

			$exclDays = $this->getExcludedDays($estimationOptions,'forecast_datetime');
			$this->writeData("DELETE FROM forecast_estimates WHERE customer_id=$customerID AND date(forecast_datetime) BETWEEN '$eStart' AND '$eEnd' $exclDays");

			$exclPODs='';
			foreach($pods as $pod)
			{
				$this->rawDataCounter = 0;
				if(isset($estimationOptions->{$pod}))
				{
					log_message("info",'excludedPOD:'.$pod);
					$sqlValues = $this->selectHistoryValues($eStart, $eEnd, $estimationOptions->{$pod}, '');
					if(empty($sqlValues)) continue;

					$ret = $this->insertEstimatedPODValues($sqlValues);

					if($ret == 0)
					{
						log_message("info","POD-ul $pod nu are estimari.");
						$newPODValues = $this->selectNewPODValues($eStart, $eEnd, $estimationOptions);
						$ret = $this->insertEstimatedPODValues($newPODValues);
					}

					//check number of values
					$estimationIntervalSize = $this->getNumberOfDays($eStart, $eEnd) * 96;
					$checkSQL = "SELECT pod as value FROM ($sqlValues) X WHERE X.far_ea IS NOT NULL GROUP BY pod HAVING count(HOUR(DATETIME)) != $estimationIntervalSize";
					$podErrors = $this->getValueArray($checkSQL);
					foreach($podErrors as $p)
						$this->errorCollection[] = $this->getCustomerName($customerID).':'.$p.': Estimare incompleta. Date insuficiente in istoric sau intervalul de selectie temperaturi/energie este prea ingust.';


					$exclPODs.="'$pod',";
				}
			}

			$exclPODs = rtrim($exclPODs, ',');

			$benchmark->start('selectHistoryValues');
			$sqlValues = $this->selectHistoryValues($eStart, $eEnd, $estimationOptions, $exclPODs);
			$benchmark->stop('selectHistoryValues');

			if(empty($sqlValues))
			{
				if($this->error) return $this->error;
				else continue;
			}

			//log_message("error",$sqlValues);
			$ret = $this->insertEstimatedPODValues($sqlValues);

			if(!$this->hasSynthetics($eStart, $eEnd, $estimationOptions, $exclPODs))
			{
				//check number of values
				$estimationIntervalSize = $this->getNumberOfDays($eStart, $eEnd) * 96;
				$checkSQL = "SELECT pod as value FROM ($sqlValues) X WHERE X.far_ea IS NOT NULL GROUP BY pod HAVING count(HOUR(DATETIME)) != $estimationIntervalSize";
				$podErrors = $this->getValueArray($checkSQL);
				foreach($podErrors as $p)
					$this->errorCollection[] = $this->getCustomerName($customerID).':'.$p.': Estimare incompleta. Date insuficiente in istoric sau intervalul de selectie temperaturi/energie este prea ingust.';			
			}
				
			if($ret == 0)
			{
				log_message("info","Clientul $customerID nu are estimari.");
				$newPODValues = $this->selectNewPODValues($eStart, $eEnd, $estimationOptions);
				$ret = $this->insertEstimatedPODValues($newPODValues);
			}
				
			if(!$estimationOptions->manual)
				$estimatedCustomers[]=$customerID;
		}
		
		if(!empty($estimatedCustomers))
		{
			
			$filterResult = $this->filterEstimatedPODValues($eStart, $eEnd, false, $estimatedCustomers);
			$this->errorCollection = array_merge($this->errorCollection, $filterResult);
			
			$estimationIntervalSize = $this->getNumberOfDays($eStart, $eEnd) * 96;
				
			$eV = $this->insertEstimatedValues($eStart, $eEnd, false, $estimatedCustomers);
			$sql = "SELECT c.customer_name as value FROM customers c 
					LEFT JOIN forecast_estimates fe ON fe.customer_id = c.customer_id AND date(fe.forecast_datetime) BETWEEN '$eStart' AND '$eEnd' 
					WHERE c.customer_id IN (".implode(',', array_map('intval', $estimatedCustomers)).")
					GROUP BY c.customer_id
					HAVING count(fe.estimate_id) <> $estimationIntervalSize";
					
			$unestimated = $this->getValueArray($sql);
			foreach($unestimated as $u)
				$this->errorCollection[] = "$u: Lipsa date istorice/sintetice sau temperaturi lipsa in istoric";
					
			$this->errorCollection[] = $eV." valori estimate";
		}
		else
			$this->errorCollection[] = "0 valori estimate";
		
		$timers = $benchmark->getTimers();
		log_message('info',print_r($timers,true));
		
		log_message("info","Estimates finalised...");	
		return implode('<br>',$this->errorCollection);
	}
	
	function getEstimateTrend($eStart, $eEnd, $customers, $customerType)
	{
		$year = substr($eStart,0,4);
		$month = (int)substr($eStart,5,2);
		$prevYear = $year - 1;
		
		if(empty($customers))
			$customers = $this->getAllActiveCustomers($eStart, $eEnd, $customerType);
		
		if(empty($customers))
		{
			$cWhr = ''; 
			$fcWhr = '';
		}
		else
		{
			$cWhr = "AND c.customer_id IN (". implode(',', array_map('intval', $customers)) .")";
			$fcWhr = "AND far.customer_id IN (". implode(',', array_map('intval', $customers)) .")";
		}
		
		
		
		
		$sql = "SELECT c.customer_name, A.*, AVG(F.far_ea) AS avg_far_ea, forecast_ea/AVG(F.far_ea)*100-100 AS change_ea_per, AVG(ft.temperature)-AVG(F.temperature) AS chenge_temp FROM (
		SELECT fe.customer_id, SUM(fe.forecast_ea) AS forecast_ea, DATE(fe.forecast_datetime) AS forecast_date, case 
									   when wfd.mapping IS NOT NULL then wfd.mapping
									   when DAYOFWEEK(fe.forecast_datetime) BETWEEN 3 AND 5 THEN 3 
									   ELSE DAYOFWEEK(fe.forecast_datetime) END AS dayIndex
		FROM forecast_estimates fe 
		LEFT JOIN working_free_days wfd ON 
			wfd.wfd_profile_id=(SELECT fcct.wfd_profile_id from forecast_customers_consumption_types fcct WHERE fcct.customer_id = fe.customer_id AND fcct.POD = '' LIMIT 1) 
			AND date(fe.forecast_ea) = wfd.free_date
		WHERE DATE(fe.forecast_datetime) BETWEEN '$eStart' AND '$eEnd'
		GROUP BY fe.customer_id, DATE(fe.forecast_datetime) ) A
		JOIN
		(SELECT far.customer_id, date(far.far_datetime) AS far_date, sum(far.far_ea) AS far_ea,fft.temperature, case 
			when fwfd.mapping IS NOT NULL then fwfd.mapping
			when DAYOFWEEK(far.far_datetime) BETWEEN 3 AND 5 THEN 3 
			ELSE DAYOFWEEK(far.far_datetime) END AS fdayIndex, far.pod
		 FROM forecast_actual_readings_{$month} far
		 LEFT JOIN working_free_days fwfd ON 
			fwfd.wfd_profile_id=(SELECT fcct.wfd_profile_id from forecast_customers_consumption_types fcct WHERE fcct.customer_id = far.customer_id AND fcct.POD = '' LIMIT 1)
			AND date(far.far_datetime) = fwfd.free_date
		 JOIN forecast_temperatures fft ON far.far_datetime = fft.temperature_datetime
		 WHERE YEAR(far.far_datetime)=$prevYear $fcWhr
		 GROUP BY far.customer_id, far_date
		 ) F ON F.customer_id = A.customer_id AND year(F.far_date) = YEAR(A.forecast_date)-1 AND F.fdayIndex = A.dayIndex
		JOIN forecast_temperatures ft ON date(ft.temperature_datetime) = F.far_date
		JOIN customers c ON A.customer_id = c.customer_id $cWhr
		GROUP BY A.customer_id, A.forecast_date, A.dayIndex
		ORDER BY c.customer_name, A.forecast_date";
		
		return $this->getArray($sql);
	}
	
	function getForecastReport($year, $month, $estimationType)
	{
		$date = \DateTime::createFromFormat("!Y-m", "$year-$month");
		$date->modify('+1 month');

		$invoiceYear = $date->format("Y");
		$invoiceMonth = $date->format("m");

		$date->modify('-2 month');
		$synthYear = $date->format("Y");
		$synthMonth = $date->format("m");

		$customerIDs = $this->getCustomersArray($year, $month);
	
		$eWhr = '';
		if($estimationType != -1)
		{
			$eWhr = "AND coalesce(fct.consumption_type_name,'General') = '$estimationType'";
		}
		
		$sql = "";
		foreach($customerIDs as $customerID)
		{
			if($this->isTable("customer_far_$customerID")) 
				$farSQL = "(SELECT CAST(SUM(far.far_ea) AS DECIMAL(20,3)) FROM customer_far_$customerID far WHERE year(far.far_datetime) = $year AND MONTH(far.far_datetime) = $month) AS far_ea, \n";
			else
				$farSQL = "null as far_ea, \n";
			
			
			$synthSQL = "";
			//$synthSQL = "(SELECT SUM(fs.synthetic_ea) FROM forecast_synthetics fs WHERE fs.customer_id = $customerID AND year(fs.synthetics_datetime) = $synthYear AND MONTH(fs.synthetics_datetime) = $synthMonth) AS synthetic_ea,";
					
			$sql .= "SELECT c.customer_id, c.customer_name,coalesce(fct.consumption_type_name,'General') as consumptionType,
			(SELECT CAST(SUM(fe.forecast_ea) AS DECIMAL(20,3)) FROM forecast_estimates fe WHERE fe.customer_id = $customerID AND year(fe.forecast_datetime) = $year AND MONTH(fe.forecast_datetime) = $month) AS forecast_ea,
			$farSQL
			$synthSQL
			(SELECT cast(SUM(ii.invoiced_item_quantity) as decimal(20,3)) FROM invoices i
			JOIN invoiced_items ii ON i.invoice_id = ii.invoice_id
			JOIN service_rates sr ON ii.service_rate_id = sr.service_rate_id
			JOIN services s ON sr.service_id = s.service_id
			WHERE i.customer_id=$customerID AND s.service_code='EA' AND YEAR(i.invoice_date)=$invoiceYear AND MONTH(i.invoice_date)=$invoiceMonth ) AS invoiced_ea
			FROM customers c
			LEFT JOIN forecast_customers_consumption_types fcct ON fcct.customer_id = c.customer_id AND fcct.pod = ''
			LEFT JOIN forecast_consumption_types fct ON fct.fct_id = fcct.fct_id 
			WHERE c.customer_id = $customerID $eWhr UNION ";
		}
		
		$sql = substr($sql,0,-6);
	
		return $this->getTArray($sql);
	}
	
}