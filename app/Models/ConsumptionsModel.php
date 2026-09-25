<?php

namespace App\Models;

use CodeIgniter\Model;
require_once('MasterDataTools.php');

class ConsumptionsModel extends Model
{
	use \MasterDataTools;
	protected $table = 'invoices';
	
	function __construct()
	{
		$this->db = db_connect();
	}
		
	function get_consumptions_min_max_years()
	{
		$query = $this->db->query("select date_format(min(consumption_date),'%Y') AS minY,date_format(max(consumption_date),'%Y') as maxY FROM consumptions ");
		$result = $query->getRowArray();
		if(!$result) return ['minY'=>2020, 'maxY'=>2020];

		return $result;
	}
	
	function get_importdate_min_max_years()
	{
		$query = $this->db->query("select date_format(min(timestamp),'%Y') AS minY,date_format(max(timestamp),'%Y') as maxY FROM consumptions ");
		$result = $query->getRowArray();
		if(!$result) return ['minY'=>2020, 'maxY'=>2020];
		return $result;
	}
	
	public function bulkDestroy($consumptionsIds)
	{
		$this->db->query("delete from consumptions where (invoice_id is null or invoice_id = 0) and consumption_id in ($consumptionsIds)");
				
		return $this->db->affectedRows();
	}
	
	public function deleteConsumptions($year, $month)
	{
		$sql = "delete consumptions from consumptions join suppliers on consumptions.supplier_name = suppliers.supplier_name where year(consumptions.consumption_date)=$year and month(consumptions.consumption_date)=$month and suppliers.supplier_id = ".$_SESSION['select-supplier'];
		
		$this->db->query($sql);
				
		return $this->db->affectedRows();
	}
	
	public function saveConsumptionDialogData($request)
	{
		if(isset($request->consumption_id))
		{
			//update		
			$this->db->query('update consumptions set invoice_start_date = '.$this->db->escape($request->consumption_date).',invoice_end_date = '.$this->db->escape($request->consumption_date).',consumption_date = '.$this->db->escape($request->consumption_date).', energy_type = '.$this->db->escape($request->energy_type).', total_consumption_re = '.$request->total_consumption_re.', total_consumption_re_3x = '.$request->total_consumption_re_3x.', total_consumption_ae = '.$request->total_consumption_ae.', total_consumption_mu = '.$this->db->escape($request->total_consumption_mu).', source = \'manual\' where consumption_id = '.$request->consumption_id);
			return $this->db->affectedRows();
		}
		else 
		{
			//get consumption info
			$query = $this->db->query('select c.distributor_name, c.customer_name, c.supplier_name, c.voltage_level_measurment,pod from consumptions c join suppliers s on c.supplier_name = s.supplier_name where s.supplier_id = '.$request->supplier_id.' and pod = '.$this->db->escape($request->pod).' limit 1');
			$rq = $query->getRowArray();
			if(empty($rq))
			{
				$result['msg'] = 'Nu pot adauga consum pentru ca POD-ul nu are un consum initial importat!';
				$result['type'] = 'error';
				$result['error'] = 2;
				return $result;
			}
			//create
			$this->db->query('insert into consumptions (distributor_name,supplier_name,customer_name,consumption_location_id, pod, voltage_level_measurment, 
			invoice_start_date, invoice_end_date, reading_start_date, reading_end_date,device_serial_number,energy_type,index_old, index_new, 
			total_consumption_ae,total_consumption_re,total_consumption_re_3x,total_consumption_mu, consumption_date, source) values('.
			$this->db->escape($rq['distributor_name']).','.$this->db->escape($rq['supplier_name']).','.$this->db->escape($rq['customer_name']).','.$this->db->escape($rq['pod']).','.$this->db->escape($rq['pod']).','.$this->db->escape($rq['voltage_level_measurment'])
			.','.$this->db->escape($request->consumption_date).','.$this->db->escape($request->consumption_date).','.$this->db->escape($request->consumption_date).','.$this->db->escape($request->consumption_date).','.time().','.$this->db->escape($request->energy_type).',0,0,'
			.$request->total_consumption_ae.','.$request->total_consumption_re.','.$request->total_consumption_re_3x.','.$this->db->escape($request->total_consumption_mu).','.$this->db->escape($request->consumption_date).',\'manual\')');
			
			return $this->db->affectedRows();
		}
	}
}