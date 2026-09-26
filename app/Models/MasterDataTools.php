<?php

require_once(APPPATH . 'Libraries/ebs/SQLAsyncQueue.php');
define ("coduriJudete", ['RO', 'AB', 'AG', 'AR', 'B', 'BC', 'BH', 'BN', 'BR', 'BT', 'BV', 'BZ', 'CJ', 'CL', 'CS', 'CT', 'CV', 'DB', 'DJ', 'GJ', 'GL', 'GR', 'HD', 'HR', 'IF', 'IL', 'IS', 'MH', 'MM', 'MS', 'NT', 'OT', 'PH', 'SB', 'SJ', 'SM', 'SV', 'TL', 'TM', 'TR', 'VL', 'VN', 'VS']);

trait MasterDataTools
{
	private $sqlQueue = null;

	function getSqlQueue()
	{
		if($this->sqlQueue == null) $this->sqlQueue = new \SQLAsyncQueue('ebs','1');
		
		return $this->sqlQueue;
	}
	
	function getArray($sql)
	{
		log_message('debug',$sql);
		$ret = [];
		$query = $this->db->query($sql);
		$ret = $query->getResultArray();
		
		return $ret;
	}

	function getRow($sql)
	{
		log_message('debug',$sql);		
		$ret = '';
		$query = $this->db->query($sql);
		$row = $query->getRowArray();
		
		return $row;
	}
	
	function getValue($sql)
	{
		//log_message('debug',$sql);	
		$ret = '';
		$query = $this->db->query($sql);
		$row = $query->getRowArray();
		
		if(!empty($row)) $ret = $row['value'];
		
		return $ret;
	}
	
	function getValueArray($sql)
	{
		log_message('debug',$sql);
		$ret = [];
		$query = $this->db->query($sql);
		$array = $query->getResultArray();
		
		foreach($array as $row)
			$ret[] = $row['value'];
		
		return $ret;
	}
	
	function getTArray($sql)
	{	
		log_message('debug',$sql);
		$ret = [];
		$query = $this->db->query($sql);
		$ret['data'] = $query->getResultArray();
		$ret['total'] = $query->getNumRows();
		return $ret;
	}

	function writeData($sql)
	{
		log_message('debug',substr($sql,0,65536));

		$start = microtime(true);
		$this->db->query($sql);
		$duration = microtime(true) - $start;

		if ($duration > 0.5) log_message('warning', "Query took ".number_format($duration, 3)." seconds.");

		return $this->db->affectedRows();
	}
	
	function getCompaniesArray()
	{
		return $this->getArray("select company_id as value, company_name as text from ach_companies order by company_order asc");
	}
	
	function getSuppliersArray()
	{
		return $this->getArray("select supplier_id as value, supplier_name as text from suppliers");
	}

	function getDistributorsArray()
	{
		return $this->getArray("select distributor_id as value, distributor_name as text from distributors");
	}

	function getCustomersArray($year=null, $month=null, $status='Activ')
	{
		//to do: inactive and all
		
		$year = $year ?? date("Y");
		$month = $month ?? date("m");
		
		return $this->getValueArray("SELECT c.customer_id as value from contracts ct
				  JOIN customers c ON ct.customer_id = c.customer_id AND c.customer_status = 'Activ'
				  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
				  WHERE '$year-$month-01'>=sr.start_date AND (ct.contract_stop IS NULL OR ct.contract_stop >= '$year-$month-01')
			      GROUP BY ct.customer_id ORDER by c.customer_name");
	}

	function getActiveCustomersTArray($year=null, $month=null)
	{
		$year = $year ?? date("Y");
		$month = $month ?? date("m");

		return $this->getTArray("SELECT c.customer_id, c.customer_name from contracts ct
				  JOIN customers c ON ct.customer_id = c.customer_id AND c.customer_status = 'Activ'
				  JOIN service_rates sr ON sr.contract_id = ct.contract_id AND sr.customer_id = ct.customer_id
				  WHERE '$year-$month-01'>=sr.start_date AND (ct.contract_stop IS NULL OR ct.contract_stop >= '$year-$month-01')
			      GROUP BY ct.customer_id ORDER by c.customer_name"); 
	}
	
	function getZonesArray()
	{
		return $this->getArray("select zone_id as value, zone_name as text from zones");
	}
	
	function getContractsArray()
	{
		return $this->getArray("select contract_id as value, contract_calculated_numberdate as text from contracts");
	}
	
	function getServicesArray()
	{
		return $this->getArray("select service_id as value, service_name as text from services");
	}
	
	function getPodsArray()
	{
		return $this->getArray("select pod_id as value, pod_no as text from pods");
	}
	
	function get_min_max_years($table, $field)
	{
		// excludem datele invalide (ex. 2000-00-01 generat din year=0/month=0) ca sa nu traga minimul la 2000
		$query = $this->db->query("select date_format(coalesce(min($field),now()),'%Y') AS minY,date_format(coalesce(max($field),now()),'%Y') as maxY FROM $table WHERE $field IS NOT NULL AND MONTH($field) > 0");
		$result = $query->getRowArray();
		if(!$result) return ['minY'=>date("Y"), 'maxY'=>date("Y")];

		return $result;
	}
	
	function get_min_max_years2($tables, $field)
	{
		$sql = "SELECT MIN(minY) AS minY, MAX(maxY) AS maxY FROM (";
		foreach($tables as $table)
			$sql .= "select date_format(coalesce(min($field),now()),'%Y') AS minY,date_format(coalesce(max($field),now()),'%Y') as maxY FROM $table UNION ";
		
		$sql = substr($sql,0, -6).") A";
		$query = $this->db->query($sql);
		$result = $query->getRowArray();
		if(!$result) return ['minY'=>date("Y"), 'maxY'=>date("Y")];

		return $result;
	}
	
	function get_buysell_min_max_years($table='sales')
	{
		$query = $this->db->query("select date_format(min(date_start),'%Y') AS minY,date_format(max(date_end),'%Y') as maxY FROM ach_".$table);
		$result = $query->getRowArray();
		if(!$result) return ['minY'=>date("Y"), 'maxY'=>date("Y")];

		return $result;
	}
	
	function get_service_rates_min_max_years($filter = 'tariff')
	{
		$n='';
		if ($filter == 'tariff') $n='not';
			
		$query = $this->db->query("select date_format(min(sr.start_date),'%Y') AS minY,date_format(max(sr.start_date),'%Y') as maxY FROM service_rates sr 
									join services s on sr.service_id = s.service_id where s.service_code $n IN ('EA','TAXOPCOMH')");
		$result = $query->getRowArray();
		if(!$result) return ['minY'=>date("Y"), 'maxY'=>date("Y")];

		return $result;
	}
		
	function getServicesByServiceCode($serviceCode)
	{
		return $this->getArray("select * from services where service_code = '$serviceCode'");
	}
	
	function getServiceRatesByServiceCode($contractID, $serviceCode)
	{
		return $this->getArray("SELECT sr.* FROM service_rates sr JOIN services s ON sr.service_id = s.service_id 
								WHERE sr.contract_id=$contractID AND s.service_code='$serviceCode'");
	}
	
	function getServiceRatesByContractId($contractID, $serviceID = null)
	{
		if($serviceID)
		return $this->getArray("select * from service_rates where contract_id = $contractID");
			else
		return $this->getArray("select * from service_rates where contract_id = $contractID and service_id = $serviceID");
	}
		
	function deleteServiceRateByContractId($contractID, $serviceID = null)
	{
		
		$query = $this->db->query("SELECT contract_id FROM invoices WHERE contract_id = $contractID");
		$result = $query->getRowArray();
		if(isset($result['contract_id']) && $result['contract_id'] == $contractID) return false;
		
		if($serviceID)
			$query = $this->db->query("DELETE FROM service_rates WHERE contract_id = $contractID and service_id = $serviceID");
		else
			$query = $this->db->query("DELETE FROM service_rates WHERE contract_id = $contractID");
		
		return true;
	}
	
	function getCustomerName($customerID)
	{
		if(!empty($customerID))
			return $this->getValue("select customer_name as value from customers where customer_id=$customerID");
		else
			return('Toti clientii');
	}
	
	function getDistributorNameByPODPrefix($prefix)
	{
		if(empty($prefix)) return '';
		
		return $this->getValue("SELECT distributor_name as value FROM distributors WHERE pod_prefix!='' AND '$prefix' LIKE concat(pod_prefix,'%')");
	}

	function getDistributorIdByPODPrefix($prefix)
	{
		if(empty($prefix)) return '';
		
		return $this->getValue("SELECT distributor_id as value FROM distributors WHERE pod_prefix!='' AND '$prefix' LIKE concat(pod_prefix,'%')");
	}
	
	function getTableSize($table)
	{
		$db = $this->db->database;
		$sql = "SELECT 
					round(((data_length + index_length) / 1073741824), 3) AS value 
				FROM information_schema.TABLES 
				WHERE table_schema = '$db'
					AND table_name = '$table';
				";
				
		return $this->getValue($sql);
	}
	
	function getTableHealth($table)
	{
		$diskTable = str_replace('_cache','',$table);
		$db = $this->db->database;
		$sql = "SELECT (SELECT COUNT(*)  FROM $table)-(SELECT COUNT(*)  FROM $diskTable) as value";
		
		if($this->getValue($sql) == 0) return 'OK';
		
		return 'Desincronizat';
	}
	
	function getRegionDataByName($regionName)
	{
		$sql = "SELECT * FROM procast_regions WHERE region_name={$this->db->escape($regionName)}";
		return $this->getRow($sql);
	
	}
	
	function dumpTable($tableName)
	{
		
		$columnNames = '';
		$columns = $this->getArray("DESC $tableName");
		foreach($columns as $c)
			$columnNames .= "'".$c['Field']."',";
		
		$columnNames = substr($columnNames,0,-1);
		
		if(file_exists(WRITEPATH ."debug/$tableName.csv"))
			unlink(WRITEPATH ."debug/$tableName.csv");
		
		$this->writeData('SELECT * INTO OUTFILE "'. WRITEPATH .'debug/'.$tableName.'.csv"
		FIELDS TERMINATED BY "," OPTIONALLY ENCLOSED BY "\'"
		LINES TERMINATED BY "\\n"
		FROM (
		    select '.$columnNames.'
			UNION ALL 
			SELECT * FROM '.$tableName.') A');
	}
	
	function createTableLikeSource($source,$sufix)
	{
		$tableName = $source.'_'.$sufix;
			
		if(!$this->getValue("SELECT table_name as value FROM information_schema.tables WHERE table_schema = SCHEMA() AND table_name = '$tableName' LIMIT 1"))
			$this->writeData("CREATE TABLE $tableName LIKE $source");
	
		return $tableName;
	}
	
	function isTable($tableName)
	{
		return $this->getValue("SELECT TABLE_NAME as value FROM information_schema.tables WHERE table_schema = SCHEMA() AND table_name = '$tableName' LIMIT 1");
	}
	
	function dropTable($tableName,$temporary = true)
	{
		$tableType="TABLE";
		if($temporary)
		{
			$whr = "AND table_type = 'TEMPORARY'";
			$tableType="TEMPORARY TABLE";
		}
		
		if($this->getValue("SELECT TABLE_NAME as value FROM information_schema.tables WHERE table_schema = SCHEMA() AND table_name = '$tableName' $whr LIMIT 1"))
			$this->writeData("DROP $tableType $tableName");
	}
}