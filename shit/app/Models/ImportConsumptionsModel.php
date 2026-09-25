<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\ConnectionInterface;

require_once('MasterDataTools.php');

class ImportConsumptionsModel extends Model
{
	use \MasterDataTools;
		
	protected $table      = 'consumptions';
	protected $db;
	
	private $checkData = [];
	private $supplierID;
	
	function __construct()
	{
		
		$this->supplierID = $_SESSION['select-supplier'];
		
		$this->db = db_connect();
		
		//preload pods
		if(!isset($this->checkData['pods'])) 
			$this->checkData['pods'] = $this->loadPods();
		
		if(!isset($this->checkData['distributors'])) 
			$this->checkData['distributors'] = $this->loadDistributors();
		
		$this->checkData['curves']=[];
		$this->checkData['curvesVariance']=[];
	
	}
	
	function get_data($sql)
	{
		$query = $this->db->query($sql);		
		$result = $query->getRowArray();
		
		return $result;
	}
	
	function get_all_data($sql)
	{
		$query = $db->query($sql);		
		$result = $query->getResultArray();
		
		return $result;
	}
	
	function import_consumptions($sqlValues)
	{
		//$this->db->query('INSERT INTO consumptions (distributor_name, supplier_name, customer_name, customer_code, contract_number, consumption_location_id, pod, voltage_level_delimitation, voltage_level_measurment, invoice_start_date, invoice_end_date, reading_start_date, reading_end_date, device_serial_number, energy_type, index_old, index_new, total_consumption_ae, total_consumption_re, total_consumption_re_3x, total_consumption_mu,source) VALUES ' . $sqlValues .' ON DUPLICATE KEY UPDATE source = VALUES(source)');
		//$this->db->query('INSERT IGNORE INTO consumptions (distributor_name, supplier_name, customer_name, customer_code, contract_number, consumption_location_id, pod, voltage_level_delimitation, voltage_level_measurment, invoice_start_date, invoice_end_date, reading_start_date, reading_end_date, device_serial_number, energy_type, index_old, index_new, total_consumption_ae, total_consumption_re, total_consumption_re_3x, total_consumption_mu,source) VALUES ' . $sqlValues );
		
		$this->db->query('INSERT IGNORE INTO import_consumptions (distributor_name, supplier_name, customer_name, customer_code, contract_number, consumption_location_id, pod, voltage_level_delimitation, voltage_level_measurment, invoice_start_date, invoice_end_date, reading_start_date, reading_end_date, device_serial_number, energy_type, index_old, index_new, total_consumption_ae, total_consumption_re, total_consumption_re_3x, total_consumption_mu, curve_name, curve_profile, consumption_date, source, extra_key, error) VALUES ' . $sqlValues );
		
			
		$ret = $this->db->affectedRows();
		
		$this->cleanup_totals();
	
		return $ret;
	}
	
	function cleanup_totals()
	{
		//avoid mariadb delete group bug
		
		$this->db->query("DROP TEMPORARY TABLE IF EXISTS PODS");
		$this->db->query("CREATE TEMPORARY TABLE PODS SELECT A.pod FROM
			( SELECT ic.pod,sum(ic.total_consumption_ae) AS ea, ic.consumption_date from  import_consumptions ic 
			GROUP BY ic.pod,ic.consumption_date) A
			JOIN 
			( SELECT c.pod,sum(c.total_consumption_ae) AS ea, c.consumption_date from  consumptions c 
			GROUP BY c.pod,c.consumption_date) B ON A.consumption_date = B.Consumption_date AND A.pod = B.pod AND A.ea = B.ea");
		$this->db->query("DELETE import_consumptions FROM import_consumptions JOIN PODS ON import_consumptions.pod = PODS.pod and import_consumptions.energy_type = 'EA'");
			
		$this->db->query("DROP TEMPORARY TABLE IF EXISTS PODS");
		$this->db->query("CREATE TEMPORARY TABLE PODS SELECT A.pod FROM 
			( SELECT ic.pod,sum(ic.total_consumption_re) AS re,sum(ic.total_consumption_re_3x) AS re_3x, ic.consumption_date from  import_consumptions ic
			WHERE ic.energy_type='ERC' GROUP BY ic.pod,ic.consumption_date) A
			JOIN 
			( SELECT c.pod,sum(c.total_consumption_re) AS re,sum(c.total_consumption_re_3x) AS re_3x, c.consumption_date from  consumptions c 
			WHERE c.energy_type='ERC' GROUP BY c.pod,c.consumption_date) B ON A.consumption_date = B.Consumption_date AND A.pod = B.pod AND A.re = B.re AND A.re_3x = B.re_3x");
		$this->db->query("DELETE import_consumptions FROM import_consumptions JOIN PODS ON import_consumptions.pod = PODS.pod and import_consumptions.energy_type = 'ERC'");

		$this->db->query("DROP TEMPORARY TABLE IF EXISTS PODS");
		$this->db->query("CREATE TEMPORARY TABLE PODS SELECT A.pod FROM 
			( SELECT ic.pod,sum(ic.total_consumption_re) AS re,sum(ic.total_consumption_re_3x) AS re_3x, ic.consumption_date from  import_consumptions ic
			WHERE ic.energy_type='ERI' GROUP BY ic.pod,ic.consumption_date) A
			JOIN 
			( SELECT c.pod,sum(c.total_consumption_re) AS re,sum(c.total_consumption_re_3x) AS re_3x, c.consumption_date from  consumptions c 
			WHERE c.energy_type='ERI' GROUP BY c.pod,c.consumption_date) B ON A.consumption_date = B.Consumption_date AND A.pod = B.pod AND A.re = B.re AND A.re_3x = B.re_3x");
		$this->db->query("DELETE import_consumptions FROM import_consumptions JOIN PODS ON import_consumptions.pod = PODS.pod and import_consumptions.energy_type = 'ERI'");
			
		$this->db->query("
			DELETE FROM import_consumptions WHERE error not IN ('Consum nou','POD nou')
			");
	}
	
	function saveConsumptions($consumptioDate)
	{
		//update aliases
		$this->writeData("
		UPDATE customers c, import_consumptions i, pods p 
		SET c.customer_alias = i.customer_name
		WHERE
		i.pod = p.pod_no and p.customer_id = c.customer_id AND c.customer_alias IS NULL 
		and i.ERROR = 'Consum nou'");
						
		//save to consumptions
		$affected = $this->writeData("insert ignore into consumptions (distributor_name, supplier_name, customer_name, customer_code, contract_number, consumption_location_id, pod, voltage_level_delimitation, voltage_level_measurment, invoice_start_date, invoice_end_date, reading_start_date, reading_end_date, device_serial_number, energy_type, index_old, index_new, total_consumption_ae, total_consumption_re, total_consumption_re_3x, total_consumption_mu, curve_name, curve_profile, consumption_date,source)
			select distributor_name, supplier_name, customer_name, customer_code, contract_number, consumption_location_id, pod, voltage_level_delimitation, voltage_level_measurment, invoice_start_date, invoice_end_date, reading_start_date, reading_end_date, device_serial_number, energy_type, index_old, index_new, total_consumption_ae, total_consumption_re, total_consumption_re_3x, total_consumption_mu, curve_name, curve_profile, consumption_date,source from import_consumptions where error like 'Consum%'");
		
		//clean up import
		$this->writeData("delete from import_consumptions where error IN ('Consum nou','Ignora: Zero')");
				
		//update distributors
		$this->writeData("
		UPDATE pods up
		SET distributor_id = COALESCE (
		(SELECT d.distributor_id FROM consumptions c
		JOIN pods p ON p.pod_no = c.pod
		JOIN distributors d ON c.distributor_name = d.distributor_name
		WHERE p.pod_no = up.pod_no
		LIMIT 1),0)
		WHERE up.distributor_id = 0");
		
		//update curve consumption
		$this->writeData("
		UPDATE actual_curves_variance acv
		JOIN (SELECT acv.acv_id, SUM(c.total_consumption_ae) as ea FROM consumptions c
		JOIN actual_curves ac ON c.curve_name = ac.curve_name
		JOIN actual_curves_variance acv ON ac.curve_id = acv.curve_id AND c.consumption_date = acv.consumption_date AND c.pod = acv.pod AND c.consumption_date = '$consumptioDate'
		GROUP BY acv.acv_id ) A ON acv.acv_id = A.acv_id 
		SET acv.consumption_ea = A.ea");
		
		return $affected;
	}
	
	function import_cleanup_xlsx()
	{
		$this->db->query('call importCleanupExcel()');
		
		return $this->db->affectedRows();
	}
	
	function import_check_duplicates_xlsx_in_pdf($pod, $energy_type,$ae,$re,$re_3x)
	{
		return 0;
		$query = $this->db->query("SELECT COUNT(c.consumption_id) as dup FROM consumptions c WHERE c.pod='".$pod."' AND c.energy_type='".$energy_type."' AND c.total_consumption_ae='".$ae."' AND c.total_consumption_re='".$re."' AND c.total_consumption_re_3x='".$re_3x."' AND SOURCE='pdf' AND TIMESTAMPDIFF(DAY,c.timestamp,NOW()) < (select setting_value from settings where setting_variable='days_xlsx_pdf_duplicates') LIMIT 1");
		$result = $query->getRowArray();
		
		return $result['dup'] > 0;
	}
	
	function get_pod_device_by_location_id($consumptionLocationId, $distributorName)
	{
		$query = $this->db->query('select pod,device_serial_number from consumptions where consumption_location_id = '.$this->db->escape($consumptionLocationId).' and distributor_name = ' .$this->db->escape($distributorName) ." and device_serial_number !='' order by consumption_id desc limit 1");
		$result = $query->getRowArray();
		
		if (empty($result)) return ['pod'=>'','device_serial_number'=>''];
		
		return $result;
	}
	
	function get_supplier_name($supplierName)
	{
		$query = $this->db->query("SELECT s.supplier_name FROM suppliers_naming sn JOIN suppliers s ON sn.supplier_id=s.supplier_id WHERE sn.rep_name LIKE ".$this->db->escape($supplierName)." OR sn.pv_name LIKE ".$this->db->escape($supplierName)." LIMIT 1");
		$result = $query->getRowArray();

		if (empty($result)) return $supplierName;
		
		return $result['supplier_name'];
	}
	
	function hasNewPods()
	{
		$query = $this->query("select consumption_id from import_consumptions where error='POD nou' limit 1");
		if(empty($query->getRowArray())) return false;
		
		return true;
	}
	
	function hasNewConsumptions()
	{
		$query = $this->query("select consumption_id from import_consumptions where error = 'POD nou' or error='Consum nou' limit 1");
		if(empty($query->getRowArray())) return false;
		
		return true;
	}

	function countConsumptions()
	{
		$query = $this->query("select count(consumption_id) as c from import_consumptions where error = 'POD nou' or error like 'Consum %'");
		$result = $query->getRowArray();
		if(empty($result)) return 0;
		
		return $result['c'];
	}
	

	function isDuplicate($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,$source)
	{
		$query = $this->db->query('select consumption_id from consumptions where 
									distributor_name = '.$this->db->escape($distributor_name).' and 
									supplier_name = '.$this->db->escape($supplier_name).' and
									pod = '.$this->db->escape($pod).' and
									voltage_level_measurment = '.$this->db->escape($voltage_level_measurment).' and
									invoice_end_date = '.$this->db->escape($invoice_end_date).' and
									reading_start_date = '.$this->db->escape($reading_start_date).' and
									reading_end_date = '.$this->db->escape($reading_end_date).' and
									device_serial_number = '.$this->db->escape($device_serial_number).' and
									energy_type = '.$this->db->escape($energy_type).' and
									index_old = '.$this->db->escape($index_old).' and
									index_new = '.$this->db->escape($index_new).' and
									total_consumption_ae = '.$this->db->escape($total_consumption_ae).' and
									total_consumption_re = '.$this->db->escape($total_consumption_re).' and
									total_consumption_re_3x = '.$this->db->escape($total_consumption_re_3x).' and
									total_consumption_mu = '.$this->db->escape($total_consumption_mu).' and
									consumption_location_id = '.$this->db->escape($consumption_location_id)
									);
		$result = $query->getRowArray();
		
		return $result;
	}

	function isInvoiced($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,$consumption_date,$source)
	{
		$query = $this->db->query('select invoice_id from consumptions where 
									distributor_name = '.$this->db->escape($distributor_name).' and 
									supplier_name = '.$this->db->escape($supplier_name).' and
									pod = '.$this->db->escape($pod).' and
									voltage_level_measurment = '.$this->db->escape($voltage_level_measurment).' and
									invoice_end_date = '.$this->db->escape($invoice_end_date).' and
									consumption_date = '.$this->db->escape($consumption_date).' and
									device_serial_number = '.$this->db->escape($device_serial_number).' and
									energy_type = '.$this->db->escape($energy_type).' and
									total_consumption_mu = '.$this->db->escape($total_consumption_mu).' and
									consumption_location_id = '.$this->db->escape($consumption_location_id).' and
									source = "pdf" and 
									invoice_id is not null limit 1'
									);
									
		/*if($pod == 'RO001E141183812' ) log_message('error','select invoice_id from consumptions where 
									distributor_name = '.$this->db->escape($distributor_name).' and 
									supplier_name = '.$this->db->escape($supplier_name).' and
									pod = '.$this->db->escape($pod).' and
									voltage_level_measurment = '.$this->db->escape($voltage_level_measurment).' and
									invoice_end_date = '.$this->db->escape($invoice_end_date).' and
									consumption_date = '.$this->db->escape($consumption_date).' and
									device_serial_number = '.$this->db->escape($device_serial_number).' and
									energy_type = '.$this->db->escape($energy_type).' and
									total_consumption_mu = '.$this->db->escape($total_consumption_mu).' and
									consumption_location_id = '.$this->db->escape($consumption_location_id).' and
									invoice_id is not null limit 1');*/
		$result = $query->getRowArray();
		
		if(empty($result)) return 0;
		
		return $result['invoice_id'];
	}

	function checkReading($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,$consumption_date, $source)
	{
		$error = "Consum nou";
		if(in_array($energy_type,['EAP'])) 
			$error = "Ignora: Productie";
		elseif(!isset($this->checkData['pods'][$pod]))
			$error = "POD nou";
		elseif( (floatval($total_consumption_ae) + floatval($total_consumption_re) + floatval($total_consumption_re_3x) ) == 0)
			$error = "Ignora: Zero";
		//elseif($this->isDuplicate($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,$source))
		//	$error = "Ignora: Duplicat";
		elseif(($invoiceID = $this->isInvoiced($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,$consumption_date,$source)))
			$error = "Ignora: Consum facturat ".$invoiceID;
			
		return $error;
	}
	
	function updateReadingError($consumption_id, $error)
	{
		$query = $this->db->query('update import_consumptions set error = '.$this->db->escape($error).' where consumption_id = '.$this->db->escape($consumption_id));
	}
	
	function saveCurve($curveName,$curveType,$distributor_id)
	{
		$curveID = $this->getCurveByData($curveName,$curveType,$distributor_id);
		
		if($curveID > 0) return $curveID;
		
		$query = $this->db->query("SELECT curve_id FROM actual_curves where supplier_id = ".$this->supplierID." and distributor_id = $distributor_id and curve_name = ".$this->db->escape(strval($curveName)));
		$result = $query->getRowArray();
		
		if (!empty($result))
		{	
			$this->setCurveByData($curveName,$curveType,$distributor_id,$result['curve_id']);
			return $result['curve_id'];
		}
		
		$this->db->query("INSERT INTO actual_curves (supplier_id, distributor_id, curve_name, curve_type) values (".$this->supplierID.", $distributor_id, ".$this->db->escape(strval($curveName)).", '$curveType')");
		
		$curveID = $this->db->insertID();
		$this->setCurveByData($curveName,$curveType,$distributor_id,$curveID);
		return $curveID;
	}

	function saveCurveVariance($consumption_date, $distributorName, $pod, $consumption_profile, $curve_name)
	{
		if(empty($curve_name))
		{
			$curve_name = $consumption_profile;
			if(empty($curve_name)) $curve_name = 'Sintetica';
			
			$curve_type = 'sintetica';
		}
		elseif(!empty($consumption_profile))
			$curve_type = 'sintetica';
		else
			$curve_type = 'masurata';
		
		$curveID = $this->getCurveVarianceByData($consumption_date,$pod,$curve_name);
		if($curveID > 0) return $curveID;
	
		$query = $this->db->query("SELECT acv.curve_id FROM actual_curves_variance acv 
									JOIN actual_curves ac ON acv.curve_id = ac.curve_id AND ac.curve_name = ".$this->db->escape($curve_name)."
									WHERE acv.supplier_id = ".$this->supplierID." AND acv.consumption_date = ".$this->db->escape($consumption_date)." AND acv.pod = ".$this->db->escape($pod));
		$result = $query->getRowArray();
		
		if (!empty($result))
		{	
			$this->setCurveVarianceByData($consumption_date,$pod,$curve_name,$result['curve_id']);
			log_message('error', $curve_name.'='.$result['curve_id']);
			return $result['curve_id'];
		}
		
		$curveID = $this->saveCurve($curve_name,$curve_type,$this->checkData['distributors'][$distributorName]);
		if(empty($curveID)) return -10;
		
		$query = $this->db->query("INSERT INTO actual_curves_variance (supplier_id, consumption_date, pod, curve_id, consumption_profile) VALUES (".$this->supplierID.", ".$this->db->escape($consumption_date).", '$pod', $curveID,". $this->db->escape(strval($consumption_profile)). ")");
		$this->setCurveVarianceByData($consumption_date,$pod,$curve_name,$curveID);
		
		return $curveID;
	}
	
	function savePodDevLoc($pod,$pod_dev_loc)
	{
		if(empty($pod_dev_loc) || empty($pod)) return;
		$this->db->query("UPDATE pods SET pod_dev_loc = ".$this->db->escape($pod_dev_loc)." WHERE pod_no = ".$this->db->escape($pod)." LIMIT 1");
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
	
	function getCustomerName($pod, $default = null)
	{
		if(isset($this->checkData['pods'][$pod]))
			return $this->checkData['pods'][$pod];
		
		return $default;
	}
	
	function loadDistributors()
	{
		$query = $this->db->query("SELECT distributor_id, distributor_name from distributors");
		$result = [];
		
		foreach($query->getResultArray() as $p)
			$result[$p['distributor_name']] = $p['distributor_id'];
		
		return $result;
	}
	
	function getCurveByData($curveName,$curveType,$distributor_id){
		
		$key = $this->supplierID.'-'.$curveName.'-'.$curveType.'-'.$distributor_id;
		if(isset($this->checkData['curves'][$key]))
			return $this->checkData['curves'][$key];
		
		return 0;
	}
	
	function setCurveByData($curveName,$curveType,$distributor_id,$curve_id){
		
		$key = $this->supplierID.'-'.$curveName.'-'.$curveType.'-'.$distributor_id;
		if(!isset($this->checkData['curves'][$key]))
			$this->checkData['curves'][$key] = $curve_id;
	}
	
	function getCurveVarianceByData($invoice_date,$pod,$curve_name){
	
		$key = $this->supplierID.'-'.$invoice_date.'-'.$pod.'-'.$curve_name;
		if(isset($this->checkData['curvesVariance'][$key]))
			return $this->checkData['curvesVariance'][$key];
	
		return 0;
	}
	
	function setCurveVarianceByData($invoice_date,$pod,$curve_name,$curveID){
		
		$key = $this->supplierID.'-'.$invoice_date.'-'.$pod.'-'.$curve_name;
		if(!isset($this->checkData['curvesVariance'][$key]))
			$this->checkData['curvesVariance'][$key] = $curveID;
	}
	/*end cache*/	
}