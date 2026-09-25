<?php

namespace App\Models;

use CodeIgniter\Model;
require_once('MasterDataTools.php');


class MasterDataModel extends Model
{
	use \MasterDataTools;
	
	protected $table   = 'dummy';
	
	function __construct()
	{
		$this->db = db_connect();
	}
	
	function executeAlert($alertID, $styled = true)
	{
		$sql = "SELECT * FROM alerts WHERE alert_id=$alertID AND status='Activ'";
		$alert = $this->getRow($sql);
		
		$report = $this->getArray($alert['query']);
		$formatedReport = "";
		$subject = "";
		
		if($styled)
		{
			$formatedReport = "
			<style>
			.alertSubject {
			}

			.alertTable {
				border-collapse: collapse;
				border: 1px solid black;
			}

			.alertHeaderTR {
				background-color: GhostWhite; 
				font-weight: bold;
			}
			
			.alertHeaderTD {
				border: 1px solid black;
				padding:5px;
			}
			
			.alertTD {
				border: 1px solid black;
				padding:3px;
			}
			
			</style>	
			";
		}
		
		if(!empty($report))
		{
			//send email
			$to      = $alert['destination'];
			$subject = str_replace("{count}",count($report), $alert['subject']);
			
			$header = false;
			$formatedReport .= "<table class='alertTable'>";
			foreach($report as $r)
			{
				if(!$header)
				{
					$formatedReport.="<tr class='alertHeaderTR'>";
					foreach ($r as $key => $value) { $formatedReport.="<td class='alertHeaderTD'>$key</td>";}
					$formatedReport.="</tr>";
					$header = true;
				}
				
				$formatedReport.="<tr class='alertTR'>";
				foreach ($r as $key => $value) { $formatedReport.="<td class='alertTD'>$value</td>";}
				$formatedReport.="</tr>";
			}
			$formatedReport .= "</table>";
			
			if($alert['type'] == 'Email')
			{
				$message = "
				<html>
				<body>
				  $formatedReport
				</body>
				</html>
				";
					
				$headers = 	'MIME-Version: 1.0' . "\r\n" .
							'Content-type: text/html; charset=utf-8' . "\r\n" .
							'From: <no-reply@sfee.ro>' . "\r\n";
				mail($to, $subject, $message, $headers);
			}
		}
		else
			log_message("error","Alerta {$alert['name']} nu are date de transmis!");
		
		return ['subject'=>$subject, 'formatedReport'=>$formatedReport];
	}
	
	function reorder($table, $idField, $direction, $id)
	{
		if($direction=='up')
		{
			$sql = "SELECT `$idField`, `order` FROM $table WHERE `order` > (SELECT `order` FROM $table WHERE $idField = $id) ORDER BY `order` ASC LIMIT 1";			
		}
		else
			$sql = "SELECT `$idField`, `order` FROM $table WHERE `order` < (SELECT `order` FROM $table WHERE `$idField` = $id) ORDER BY `order` DESC LIMIT 1";
		
		$data = $this->getRow($sql);
		if(empty($data)) return false;
		
		$sql = "UPDATE $table SET `order` = (SELECT `order` FROM $table where `$idField` = $id) WHERE `$idField` = {$data["$idField"]}";
		$this->writeData($sql);
		
		$sql = "UPDATE $table SET `order` = {$data['order']} WHERE `$idField` = $id";
		$this->writeData($sql);
		
		return true;
	}

	function getMaxOrder($table)
	{
		$sql = "SELECT MAX(`order`) as value FROM $table";
		
		return $this->getValue($sql);
	}
	
	function getIDOrder($table,$idField, $id)
	{
		$sql = "SELECT `order` as value FROM $table where $idField = $id";
		
		return $this->getValue($sql);
	}

	function updateServiceStatus($serviceID, $status)
	{
		//dezactiveaza client
		$query = "UPDATE services s SET s.service_status = ".$this->db->escape($status)." where service_id = ".$this->db->escape($serviceID);
		$this->db->query($query);
	}
	
	function updatePODStatus($pod, $status)
	{
		$sqlQueue = $this->getSqlQueue();
					
		if($status == "Inactiv")
		{
			//arhiveaza profilele orare din prognoza
			$sqlQueue->sendUniqueItem([
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_1 WHERE pod = ".$this->db->escape($pod),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_2 WHERE pod = ".$this->db->escape($pod),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_3 WHERE pod = ".$this->db->escape($pod),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_4 WHERE pod = ".$this->db->escape($pod),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_5 WHERE pod = ".$this->db->escape($pod),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_6 WHERE pod = ".$this->db->escape($pod),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_7 WHERE pod = ".$this->db->escape($pod),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_8 WHERE pod = ".$this->db->escape($pod),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_9 WHERE pod = ".$this->db->escape($pod),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_10 WHERE pod = ".$this->db->escape($pod),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_11 WHERE pod = ".$this->db->escape($pod),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_12 WHERE pod = ".$this->db->escape($pod),
			"DELETE FROM forecast_actual_readings_1 WHERE pod = ".$this->db->escape($pod),
			"DELETE FROM forecast_actual_readings_2 WHERE pod = ".$this->db->escape($pod),
			"DELETE FROM forecast_actual_readings_3 WHERE pod = ".$this->db->escape($pod),
			"DELETE FROM forecast_actual_readings_4 WHERE pod = ".$this->db->escape($pod),
			"DELETE FROM forecast_actual_readings_5 WHERE pod = ".$this->db->escape($pod),
			"DELETE FROM forecast_actual_readings_6 WHERE pod = ".$this->db->escape($pod),
			"DELETE FROM forecast_actual_readings_7 WHERE pod = ".$this->db->escape($pod),
			"DELETE FROM forecast_actual_readings_8 WHERE pod = ".$this->db->escape($pod),
			"DELETE FROM forecast_actual_readings_9 WHERE pod = ".$this->db->escape($pod),
			"DELETE FROM forecast_actual_readings_10 WHERE pod = ".$this->db->escape($pod),
			"DELETE FROM forecast_actual_readings_11 WHERE pod = ".$this->db->escape($pod),
			"DELETE FROM forecast_actual_readings_12 WHERE pod = ".$this->db->escape($pod)
									],'forecast_archive_readings');
		}
		else
		{
			//dez-arhiveaza profilele orare din prognoza
			$sqlQueue->sendUniqueItem([
			"INSERT IGNORE INTO forecast_actual_readings_1 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE pod = ".$this->db->escape($pod)." and month(far_datetime)=1",
			"INSERT IGNORE INTO forecast_actual_readings_2 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE pod = ".$this->db->escape($pod)." and month(far_datetime)=2",
			"INSERT IGNORE INTO forecast_actual_readings_3 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE pod = ".$this->db->escape($pod)." and month(far_datetime)=3",
			"INSERT IGNORE INTO forecast_actual_readings_4 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE pod = ".$this->db->escape($pod)." and month(far_datetime)=4",
			"INSERT IGNORE INTO forecast_actual_readings_5 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE pod = ".$this->db->escape($pod)." and month(far_datetime)=5",
			"INSERT IGNORE INTO forecast_actual_readings_6 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE pod = ".$this->db->escape($pod)." and month(far_datetime)=6",
			"INSERT IGNORE INTO forecast_actual_readings_7 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE pod = ".$this->db->escape($pod)." and month(far_datetime)=7",
			"INSERT IGNORE INTO forecast_actual_readings_8 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE pod = ".$this->db->escape($pod)." and month(far_datetime)=8",
			"INSERT IGNORE INTO forecast_actual_readings_9 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE pod = ".$this->db->escape($pod)." and month(far_datetime)=9",
			"INSERT IGNORE INTO forecast_actual_readings_10 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE pod = ".$this->db->escape($pod)." and month(far_datetime)=10",
			"INSERT IGNORE INTO forecast_actual_readings_11 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE pod = ".$this->db->escape($pod)." and month(far_datetime)=11",
			"INSERT IGNORE INTO forecast_actual_readings_12 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE pod = ".$this->db->escape($pod)." and month(far_datetime)=12",
			"DELETE FROM forecast_actual_readings_archive WHERE pod = ".$this->db->escape($pod)
									],'forecast_archive_readings');
		}
	}
	
	function updateCustomerStatus($customerID, $status)
	{
		//dezactiveaza client
		$query = "UPDATE customers c SET c.customer_status = ".$this->db->escape($status)." where customer_id = ".$this->db->escape($customerID);
		$this->db->query($query);
		
		if($status == "Inactiv")
		{
			//opreste contract cu data de azi daca este cazul
			$query = "UPDATE contracts c SET c.contract_stop = now() where customer_id = ".$this->db->escape($customerID). " and c.contract_stop is null";
			$this->db->query($query);
			
			//sterge consumurile neimportate
			$query = "DELETE from import_consumptions where pod in (select pod_no from pods where customer_id = ".$this->db->escape($customerID).")";
			$this->db->query($query);
			
			//arhiveaza consumurile
			$sqlQueue = $this->getSqlQueue();
			
			$sqlQueue->sendUniqueItem([
			"INSERT IGNORE INTO consumptions_archive (distributor_name,supplier_name,customer_name,customer_code,contract_number,consumption_location_id,pod,voltage_level_delimitation,voltage_level_measurment,invoice_start_date,invoice_end_date,reading_start_date,reading_end_date,device_serial_number,energy_type,index_old,index_new,total_consumption_ae,total_consumption_re,total_consumption_re_3x,total_consumption_mu,curve_name,curve_profile,source,invoice_id,consumption_date
			) 
			SELECT distributor_name,supplier_name,customer_name,customer_code,contract_number,consumption_location_id,pod,voltage_level_delimitation,voltage_level_measurment,invoice_start_date,invoice_end_date,reading_start_date,reading_end_date,device_serial_number,energy_type,index_old,index_new,total_consumption_ae,total_consumption_re,total_consumption_re_3x,total_consumption_mu,curve_name,curve_profile,source,invoice_id,consumption_date
			FROM consumptions WHERE pod in (SELECT pod_no FROM pods where customer_id = ".$this->db->escape($customerID).")",
			"DELETE FROM consumptions WHERE pod in (SELECT pod_no FROM pods where customer_id = ".$this->db->escape($customerID).")",
			//"DELETE from invoices where invoice_status = 'In pregatire' and customer_id = ".$this->db->escape($customerID), //sterge facturile neemise
			"DELETE from consumptions where invoice_id is null and pod in (select pod_no from pods where customer_id = ".$this->db->escape($customerID).")"//sterge consumurile nefacturate
			
									],'archive_consumptions');
					
			//arhiveaza datele orare
			$sqlQueue->sendUniqueItem([
			"INSERT IGNORE INTO actual_readings_archive (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_1 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO actual_readings_archive (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_2 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO actual_readings_archive (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_3 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO actual_readings_archive (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_4 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO actual_readings_archive (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_5 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO actual_readings_archive (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_6 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO actual_readings_archive (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_7 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO actual_readings_archive (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_8 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO actual_readings_archive (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_9 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO actual_readings_archive (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_10 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO actual_readings_archive (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_11 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO actual_readings_archive (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_12 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM actual_readings_1 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM actual_readings_2 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM actual_readings_3 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM actual_readings_4 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM actual_readings_5 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM actual_readings_6 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM actual_readings_7 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM actual_readings_8 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM actual_readings_9 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM actual_readings_10 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM actual_readings_11 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM actual_readings_12 WHERE customer_id = ".$this->db->escape($customerID)
									],'archive_readings');
			
			//arhiveaza profilele orare din prognoza
			$sqlQueue->sendUniqueItem([
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_1 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_2 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_3 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_4 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_5 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_6 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_7 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_8 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_9 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_10 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_11 WHERE customer_id = ".$this->db->escape($customerID),
			"INSERT IGNORE INTO forecast_actual_readings_archive (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_12 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM forecast_actual_readings_1 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM forecast_actual_readings_2 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM forecast_actual_readings_3 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM forecast_actual_readings_4 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM forecast_actual_readings_5 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM forecast_actual_readings_6 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM forecast_actual_readings_7 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM forecast_actual_readings_8 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM forecast_actual_readings_9 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM forecast_actual_readings_10 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM forecast_actual_readings_11 WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM forecast_actual_readings_12 WHERE customer_id = ".$this->db->escape($customerID)
									],'forecast_archive_readings');
									
			$sqlQueue->sendUniqueItem([
			"INSERT IGNORE INTO forecast_estimates_archive (supplier_id, customer_id, forecast_datetime, forecast_ea) SELECT supplier_id, customer_id, forecast_datetime, forecast_ea from forecast_estimates WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM forecast_estimates WHERE customer_id = ".$this->db->escape($customerID)
									],'forecast_estimates_archive');
		}
		else
		{
			$sqlQueue = $this->getSqlQueue();
			
			$sqlQueue->sendUniqueItem([
			"INSERT IGNORE INTO consumptions (distributor_name,supplier_name,customer_name,customer_code,contract_number,consumption_location_id,pod,voltage_level_delimitation,voltage_level_measurment,invoice_start_date,invoice_end_date,reading_start_date,reading_end_date,device_serial_number,energy_type,index_old,index_new,total_consumption_ae,total_consumption_re,total_consumption_re_3x,total_consumption_mu,curve_name,curve_profile,source,invoice_id,consumption_date
			)
			SELECT distributor_name,supplier_name,customer_name,customer_code,contract_number,consumption_location_id,pod,voltage_level_delimitation,voltage_level_measurment,invoice_start_date,invoice_end_date,reading_start_date,reading_end_date,device_serial_number,energy_type,index_old,index_new,total_consumption_ae,total_consumption_re,total_consumption_re_3x,total_consumption_mu,curve_name,curve_profile,source,invoice_id,consumption_date
			FROM consumptions_archive WHERE pod in (SELECT pod_no FROM pods where customer_id = ".$this->db->escape($customerID).")",
			"DELETE FROM consumptions_archive WHERE pod in (SELECT pod_no FROM pods where customer_id = ".$this->db->escape($customerID).")"
									],'archive_consumptions');
			
			//dez-arhiveaza datele orare
			$sqlQueue->sendUniqueItem([
			"INSERT IGNORE INTO actual_readings_1 (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(reading_datetime)=1",
			"INSERT IGNORE INTO actual_readings_2 (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(reading_datetime)=2",
			"INSERT IGNORE INTO actual_readings_3 (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(reading_datetime)=3",
			"INSERT IGNORE INTO actual_readings_4 (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(reading_datetime)=4",
			"INSERT IGNORE INTO actual_readings_5 (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(reading_datetime)=5",
			"INSERT IGNORE INTO actual_readings_6 (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(reading_datetime)=6",
			"INSERT IGNORE INTO actual_readings_7 (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(reading_datetime)=7",
			"INSERT IGNORE INTO actual_readings_8 (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(reading_datetime)=8",
			"INSERT IGNORE INTO actual_readings_9 (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(reading_datetime)=9",
			"INSERT IGNORE INTO actual_readings_10 (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(reading_datetime)=10",
			"INSERT IGNORE INTO actual_readings_11 (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(reading_datetime)=11",
			"INSERT IGNORE INTO actual_readings_12 (supplier_id,reading_datetime, customer_id, curve_id, actual_ea) SELECT supplier_id,reading_datetime, customer_id, curve_id, actual_ea from actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(reading_datetime)=12",
			"DELETE FROM actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)
									],'archive_readings');
			
			//dez-arhiveaza profilele orare din prognoza
			$sqlQueue->sendUniqueItem([
			"INSERT IGNORE INTO forecast_actual_readings_1 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(far_datetime)=1",
			"INSERT IGNORE INTO forecast_actual_readings_2 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(far_datetime)=2",
			"INSERT IGNORE INTO forecast_actual_readings_3 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(far_datetime)=3",
			"INSERT IGNORE INTO forecast_actual_readings_4 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(far_datetime)=4",
			"INSERT IGNORE INTO forecast_actual_readings_5 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(far_datetime)=5",
			"INSERT IGNORE INTO forecast_actual_readings_6 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(far_datetime)=6",
			"INSERT IGNORE INTO forecast_actual_readings_7 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(far_datetime)=7",
			"INSERT IGNORE INTO forecast_actual_readings_8 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(far_datetime)=8",
			"INSERT IGNORE INTO forecast_actual_readings_9 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(far_datetime)=9",
			"INSERT IGNORE INTO forecast_actual_readings_10 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(far_datetime)=10",
			"INSERT IGNORE INTO forecast_actual_readings_11 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(far_datetime)=11",
			"INSERT IGNORE INTO forecast_actual_readings_12 (supplier_id,far_datetime, customer_id, pod, far_ea) SELECT supplier_id,far_datetime, customer_id, pod, far_ea from forecast_actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)." and month(far_datetime)=12",
			"DELETE FROM forecast_actual_readings_archive WHERE customer_id = ".$this->db->escape($customerID)
									],'forecast_archive_readings');
			
			$sqlQueue->sendUniqueItem([
			"INSERT IGNORE INTO forecast_estimates (supplier_id, customer_id, forecast_datetime, forecast_ea) SELECT supplier_id, customer_id, forecast_datetime, forecast_ea from forecast_estimates_archive WHERE customer_id = ".$this->db->escape($customerID),
			"DELETE FROM forecast_estimates_archive WHERE customer_id = ".$this->db->escape($customerID)
									],'forecast_estimates_archive');
		}
	}
	
	function readContractDialogData($contractID)
	{
									
		$sql = "select * from view_contracts where contract_id = ".$this->db->escape($contractID);
		$query = $this->db->query($sql);
		$dialogContractData = $query->getRowArray();
							
		return $dialogContractData;
	}
	
	function saveContractDialogData($request)
	{
		
		$result['msg'] = 'Eroare la salvarea datelor !';
		$result['type'] = 'error';
		
		if((!empty($request->price_energy) || !empty($request->price_component)) && empty($request->contract_start_date)) return $result;
		
		$sParams = (array)$request;
		
		//save data at contract level	
		if( empty($request->contract_id))  /*new contract*/
		{
			$sql = "insert into contracts (supplier_id, customer_id, contract_number, contract_date, contract_stop, contract_component_type) values (:supplier_id:, :customer_id:, :contract_number:, :contract_date:, :contract_stop_date:, :contract_component_type:)";
			$query = $this->db->query($sql,$sParams);
			if($this->db->affectedRows() > 0)
			{
				$result['msg'] = 'Contractul nr '.$request->contract_number.'/'.$request->contract_date.' a fost adaugat.';
				$result['type'] = 'info';
			}
			$contractID = $this->db->insertID();
			
			//save data at service_rates level
			if(!empty($request->price_energy))
			{
				$eaServices = $this->getServicesByServiceCode("EA");
				if(count($eaServices)!=1)
				{
					$result['msg'] .= "Este necesar sa introduceti preturile manual.";
					return $result;
				}
				
				if( empty($request->contract_id) && !empty($contractID))
				{
					$sql = "insert into service_rates (supplier_id, service_id, customer_id, contract_id, service_value, start_date) values (?,?,?,?,?,?)";
					$query = $this->db->query($sql,[$request->supplier_id, $eaServices[0]['service_id'],$request->customer_id,$contractID,$request->price_energy,$request->contract_start_date]);
				}
			}
			
			if(!empty($request->price_component) && $request->contract_component_type != 'Fara')
			{
				$compServices = $this->getServicesByServiceCode("TAXOPCOMH");
				if(count($compServices)!=2)
				{
					$result['msg'] .= "Este necesar sa introduceti componenta manual.";
					return $result;
				}
				
				if($request->contract_component_type == 'Pret fix')
				{
					foreach($compServices as $c)
						if(str_contains($c['service_name'], 'fix'))
						{
							$compServiceID = $c['service_id'];
							break;
						}
				}
				else
					foreach($compServices as $c)
						if(str_contains($c['service_name'], 'orar'))
						{
							$compServiceID = $c['service_id'];
							break;
						}
				
				$sql = "insert into service_rates (supplier_id, service_id, customer_id, contract_id, service_value, start_date) values (?,?,?,?,?,?)";
				$query = $this->db->query($sql,[$request->supplier_id, $compServiceID,$request->customer_id,$contractID,$request->price_component,$request->contract_start_date]);
			}				
		}
		else /*update existing contract*/
		{
			//save data at contract level
			$sql = "update contracts set supplier_id = :supplier_id:, customer_id=:customer_id:, contract_number=:contract_number:, contract_date=:contract_date:, contract_stop=:contract_stop_date:, contract_component_type=:contract_component_type:  where contract_id = :contract_id:";
			$query = $this->db->query($sql,$sParams);
			
			//if($this->db->affectedRows() > 0) allways 0??
			{
				$result['msg'] = 'Contractul nr '.$request->contract_number.'/'.$request->contract_date.' a fost actualizat.';
				$result['type'] = 'info';
			}	
		
			//save data at service_rates level
			if(!empty($request->price_energy))
			{

				$serviceRates = $this->getServiceRatesByServiceCode($request->contract_id,'EA');
				if(count($serviceRates) == 0 ) //contract fara pret
				{
					$eaServices = $this->getServicesByServiceCode("EA");
					if(count($eaServices)!=1)
					{
						$result['msg'] .= "Este necesar sa introduceti preturile manual.";
						return $result;
					}
					
					$sql = "insert into service_rates (supplier_id, service_id, customer_id, contract_id, service_value, start_date) values (?,?,?,?,?,?)";
					$query = $this->db->query($sql,[$request->supplier_id, $eaServices[0]['service_id'],$request->customer_id,$request->contract_id,$request->price_energy,$request->contract_start_date]);	
				}
				elseif(count($serviceRates) ==1 ) //contract cu un pret
				{
					$sql = "update service_rates set service_value = ?, start_date = ? where service_rate_id = ?";
					$query = $this->db->query($sql,[$request->price_energy,$request->contract_start_date, $serviceRates[0]['service_rate_id']]);	
				}
				else	//contract cu mai multe preturi
				{
					$result['msg'] .= "Este necesar sa actualizati preturile manual.";
					return $result;
				}
			}
			
			if($request->contract_component_type == 'Fara')
			{
				//delete service rate
				$compServices = $this->getServicesByServiceCode("TAXOPCOMH");	
				if(count($compServices)!=2) //prea multe servicii de tip componenta
				{
					$result['msg'] .= "Este necesar sa introduceti componenta manual.";
					return $result;
				}
				
				
				$delResult = $this->deleteServiceRateByContractId($request->contract_id,$compServices[0]['service_id']) + $this->deleteServiceRateByContractId($request->contract_id,$compServices[1]['service_id']);
				if($delResult != 2) //contract cu facturi existente
				{
					$result['msg'] .= "Nu se poate sterge componenta deoarece s-a facturat deja.";
					$result['type'] = 'warning';
					return $result;
				}
			}
			elseif(!empty($request->price_component))
			{
				$compServices = $this->getServicesByServiceCode("TAXOPCOMH"); 
				if(count($compServices)!=2)
				{
					$result['msg'] .= "Este necesar sa actualizati componenta manual.";
					$result['type'] = 'warning';
					return $result;
				}
				
				if($request->contract_component_type == 'Pret fix') 
				{
					foreach($compServices as $c)
						if(str_contains($c['service_name'], 'fix'))
						{
							$compServiceID = $c['service_id'];
							break;
						}
				}
				else
					foreach($compServices as $c)
						if(str_contains($c['service_name'], 'orar'))
						{
							$compServiceID = $c['service_id'];
							break;
						}
				
				$compServiceRates = $this->getServiceRatesByServiceCode($request->contract_id,'TAXOPCOMH');
				if(count($compServiceRates)>1) //prea multe preturi per componenta
				{
					$result['msg'] .= "Este necesar sa actualizati componenta manual.";
					$result['type'] = 'warning';
					return $result;
				}		
				
				if(count($compServiceRates) == 0)
				{
					$sql = "insert into service_rates (supplier_id, service_id, customer_id, contract_id, service_value, start_date) values (?, ?, ?, ?, ?, ?)";
					$query = $this->db->query($sql,[$request->supplier_id, $compServiceID,$request->customer_id,$request->contract_id,$request->price_component,$request->contract_start_date]);
				}
				else
				{
					$sql = "update service_rates set supplier_id =?, service_id =?, customer_id=?, contract_id=?, service_value=?, start_date=? where service_rate_id = ?";
					$query = $this->db->query($sql,[$request->supplier_id, $compServiceID,$request->customer_id,$request->contract_id,$request->price_component,$request->contract_start_date,$compServiceRates[0]['service_rate_id']]);
				}
			}	
		}
						
		return $result;
	}


	function readInvoicedItemsDialogData($invoiceID, $invoicedItemID)
	{
		if (empty($invoicedItemID))
			$sql = "SELECT coalesce(ii.invoiced_item_no,0)+1 AS invoiced_item_no, coalesce(ii.invoiced_pod_no,(SELECT pod_no FROM pods WHERE customer_id = i.customer_id LIMIT 1)) AS invoiced_pod_no, coalesce(d.distributor_name,(SELECT distributor_name FROM distributors WHERE distributor_id=1)) AS distributor_name, coalesce(d.distributor_id,1) AS distributor_id, i.customer_id, i.invoice_date, s.service_name, s.service_measurment_unit, sr.service_value, coalesce(ii.invoiced_item_quantity,0) AS invoiced_item_quantity, st.setting_value AS vat_value, sr.service_rate_id
			 FROM invoices i 
			JOIN services s ON s.service_code='EA' AND s.supplier_id = i.supplier_id AND s.service_status='Activ'
			JOIN service_rates sr ON sr.service_id = s.service_id AND sr.supplier_id = i.supplier_id AND sr.customer_id = i.customer_id
			JOIN settings st ON st.setting_variable = 'VAT'
			LEFT JOIN invoiced_items ii ON ii.invoice_id = i.invoice_id
			left JOIN distributors d ON d.distributor_id = ii.distributor_id
			WHERE 
			i.invoice_id=".$this->db->escape($invoiceID)." AND 
			( ii.invoiced_item_no = (SELECT max(invoiced_item_no) FROM invoiced_items WHERE invoice_id=".$this->db->escape($invoiceID).") OR 0 = (SELECT COUNT(invoiced_item_no) FROM invoiced_items WHERE invoice_id=".$this->db->escape($invoiceID)."))
			ORDER BY sr.start_date desc
			LIMIT 1";
		else
			$sql = "SELECT ii.*, d.distributor_name,s.service_name, s.service_measurment_unit,sr.service_value,st.setting_value AS vat_value FROM invoiced_items ii 
					join distributors d on ii.distributor_id=d.distributor_id 
					JOIN service_rates sr ON ii.service_rate_id=sr.service_rate_id
					JOIN services s ON sr.service_id=s.service_id
					JOIN settings st ON st.setting_variable = 'VAT'
					WHERE ii.invoiced_item_id=".$this->db->escape($invoicedItemID);
				
		$query = $this->db->query($sql);
		$invoicedItemsData = $query->getRowArray();
							
		return $invoicedItemsData;
	}

	

	function saveInvoicedItemsDialogData($request)
	{
		//not used		
		return false;
	}	
	
	function getServicePrice($serviceID, $date, $customerID = null, $contractID = null, $zoneID = null, $podID = null, $distributorID = null)
	{
		$sql =
		"SELECT service_value FROM service_rates sr 
		WHERE 
		(sr.customer_id= :customerID: 		OR sr.customer_id IS NULL) AND 
		(sr.contract_id= :contractID: 		OR sr.contract_id IS NULL) AND
		(sr.zone_id = :zoneID:				OR sr.zone_id IS NULL) AND
		(sr.pod_id = :podID: 				OR sr.pod_id IS NULL) AND
		(sr.distributor_id = :distributorID:	OR sr.distributor_id IS NULL) AND
		sr.service_id = :serviceID: AND
		sr.start_date < :date:
		ORDER BY sr.start_date DESC
		LIMIT 1";
		$query = $this->db->query($sql,[
		'serviceID' => $serviceID,
		'date' => $date,
		'customerID' => $customerID,
		'contractID' => $contractID,
		'zoneID' => $zoneID,
		'podID' => $podID,
		'distributorID' => $distributorID,
		]);
		$price = $query->getRowArray();
		
		if(!empty($price)) return $price['service_value'];
		
		return 0;
	}

	function getServicePriceFromInvoice($serviceID, $invoiceID, $distributorID = null)
	{
		$sql = 	"SELECT customer_id, contract_id, zone_id, invoice_date FROM invoices where invoice_id = ? ";
		$query = $this->db->query($sql,$invoiceID);
		$invoiceData = $query->getRowArray();

		$price = $this->getServicePrice($serviceID, $invoiceData['invoice_date'],$invoiceData['customer_id'],$invoiceData['contract_id'],$invoiceData['zone_id'],null,$distributorID);
					
		return $price;
	}
	
	private function countWorkigDays($year,$month)
	{
		$firstDay = strtotime("$year-$month-01");
		$lastDay = strtotime("last day of $year-$month");

		$workingDays = 0;

		for ($currentDay = $firstDay; $currentDay <= $lastDay; $currentDay = strtotime('+1 day', $currentDay)) {
			$dayOfWeek = date('N', $currentDay);

			if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
				// Day is a working day (Monday to Friday)
				$workingDays++;
			}
		}

		return $workingDays;
	}
	
	function createWorkingFreeDays($wfdProfileId, $freeDate, $name=null, $mapping=null)
	{	
		$d = \DateTime::createFromFormat("Y-m-d",$freeDate);
		
		if(empty($name))
		{
			$y = $d->format("Y");
			
			$sql = "INSERT IGNORE INTO working_free_days (wfd_profile_id, name, free_date, mapping) VALUES
					($wfdProfileId,'Anul Nou', '$y-01-01',1),
					($wfdProfileId,'Anul Nou', '$y-01-02',1),
					($wfdProfileId,'Boboteaza', '$y-01-06',(SELECT CASE WHEN DAYOFWEEK('$y-01-06') = 7 THEN 7 ELSE 1 END) ),
					($wfdProfileId,'Sfantul Ioan Botezatorul', '$y-01-07',(SELECT CASE WHEN DAYOFWEEK('$y-01-07') = 7 THEN 7 ELSE 1 END)),
					($wfdProfileId,'Ziua Unirii Principatelor Române', '$y-01-24',(SELECT CASE WHEN DAYOFWEEK('$y-01-24') = 7 THEN 7 ELSE 1 END)),
					($wfdProfileId,'Vinerea Mare',DATE_SUB('$freeDate', INTERVAL 2 DAY),1),
					($wfdProfileId,'Paște ortodox','$freeDate',1),
					($wfdProfileId,'Paște ortodox',DATE_ADD('$freeDate', INTERVAL 1 DAY),1),
					($wfdProfileId,'Ziua Muncii', '$y-05-01',1),
					($wfdProfileId,'Ziua Copilului', '$y-06-01',(SELECT CASE WHEN DAYOFWEEK('$y-06-01') = 7 THEN 7 ELSE 1 END)),
					($wfdProfileId,'Rusalii',DATE_ADD('$freeDate', INTERVAL 49 DAY),1),
					($wfdProfileId,'Rusalii',DATE_ADD('$freeDate', INTERVAL 50 DAY),1),
					($wfdProfileId,'Adormirea Maicii Domnului', '$y-08-15',(SELECT CASE WHEN DAYOFWEEK('$y-08-15') = 7 THEN 7 ELSE 1 END)),
					($wfdProfileId,'Sfântul Andrei', '$y-11-30',(SELECT CASE WHEN DAYOFWEEK('$y-11-30') = 7 THEN 7 ELSE 1 END)),
					($wfdProfileId,'Ziua Națională a României', '$y-12-01',(SELECT CASE WHEN DAYOFWEEK('$y-12-01') = 7 THEN 7 ELSE 1 END)),
					($wfdProfileId,'Crăciunul', '$y-12-25',1),
					($wfdProfileId,'Crăciunul', '$y-12-26',1)";
		}
		else
		{
			$y = $d->format("Y-m-d");
			if($mapping == null) $mapping = 'null';
			$sql = "INSERT IGNORE INTO working_free_days (wfd_profile_id, name, free_date, mapping) VALUES
					($wfdProfileId,'$name', '$y',$mapping)
					ON DUPLICATE KEY UPDATE name = VALUES(name), mapping = VALUES(mapping)";
			
			$y =  $d->format("Y");
			
		}
		
		
		$ret = $this->writeData($sql);
		if($ret == 2) $ret = 1; //on key update issue

		/* do not update working days automatically
		$sql = "INSERT INTO working_days (year,month,working_days) VALUES "; 		
		for($m=1;$m<=12;$m++)
		{
			$wd = $this->countWorkigDays($y,$m) - $this->getValue("SELECT coalesce(SUM(1),0) as value from working_free_days where DAYOFWEEK(free_date) BETWEEN 2 AND 6 AND year(free_date)=$y AND month(free_date)=$m");
			$sql .= "($y,$m,$wd),";
		}
	
		$sql = substr($sql, 0, -1);
		
		$sql .= ' ON DUPLICATE KEY UPDATE working_days = VALUES(working_days)';
		
		$this->writeData($sql);
		*/
		
		return $ret;
	}
	
	function removeWorkingFreeDays($wfid, $freeDay)
	{	
		$sql = "DELETE FROM working_free_days where wfd_id = $wfid";
		$this->writeData($sql);
		
		/* do not update working days automatically
		$sql = "UPDATE working_days SET working_days = working_days - 1 where year = year('$freeDay') and month = month('$freeDay')";
		log_message('error',$sql);
		$this->writeData($sql);
		*/
	}

	function removeWorkingFreeDaysProfile($wfdp)
	{	
		$sql = "DELETE FROM working_free_days where wfd_profile_id = $wfdp";
		$this->writeData($sql);
		
		$sql = "UPDATE forecast_customers_consumption_types set wfd_profile_id = (SELECT wfd_profile_id working_free_days_profiles where profile_name='General' and supplier_id = {$_SESSION['select-supplier']} limit 1) where wfd_profile_id = $wfdp";
		$this->writeData($sql);
		
		$sql = "DELETE FROM working_free_days_profiles where wfd_profile_id = $wfdp";
		$this->writeData($sql);
		
		/* do not update working days automatically */

	}
	
	function copyWorkingFreeDaysProfile($sourceProfileID,$destProfileID)
	{
		$sql = "INSERT IGNORE INTO working_free_days (wfd_profile_id, free_date, name, mapping) SELECT $destProfileID,free_date, name, mapping FROM working_free_days WHERE wfd_profile_id = $sourceProfileID";
		
		$this->writeData($sql);
		/* do not update working days automatically */
	}

	function getFreeDays($year, $month, $customers)
	{
		$freeDays = [];
		$firstDay = strtotime("$year-$month-01");
		$lastDay = strtotime("last day of $year-$month");

		for ($currentDay = $firstDay; $currentDay <= $lastDay; $currentDay = strtotime('+1 day', $currentDay)) {
			$dayOfWeek = date('N', $currentDay);
			
			//log_message('error',$dayOfWeek);
			if ($dayOfWeek >=6) {
				// Day is a free day (Sun, Sat)
				$freeDays[]=(int)date('j', $currentDay);
			}
		}
		
		if(empty($customers))
			$sql = "SELECT DAY(free_date) as freeDay FROM working_free_days WHERE YEAR(free_date)=$year AND MONTH(free_date)=$month";
		else
			$sql = "SELECT DAY(wfd.free_date) as freeDay 
			FROM working_free_days wfd 
			WHERE YEAR(wfd.free_date)=$year AND MONTH(wfd.free_date)=$month AND 
			wfd.wfd_profile_id IN 
			(SELECT distinct coalesce(wfd_profile_id,
				(SELECT wfd_profile_id from working_free_days_profiles WHERE profile_name='General' AND supplier_id = {$_SESSION['select-supplier']} LIMIT 1))
			FROM forecast_customers_consumption_types fcct WHERE customer_id IN ($customers))";
		$holydays = $this->getArray($sql);
		
		foreach($holydays as $h)
			if(!in_array((int)$h['freeDay'],$freeDays)) $freeDays[]=(int)$h['freeDay'];
		
		return $freeDays;
	}
	
	private function getLocalHour($timestamp)
	{
		$utc_date = new \DateTime('now', new \DateTimeZone('UTC'));

			$utc_date->setTimestamp($timestamp);
			$utc_date->setTimeZone(new \DateTimeZone('Europe/Bucharest'));
			return $utc_date->format('G'); // output: 2011-04-26 10:45 PM
	}
	
	function getSunData($year, $month)
	{
		$sunData = [];
		$firstDay = strtotime("$year-$month-01");
		$lastDay = strtotime("last day of $year-$month");
	
		for ($currentDay = $firstDay; $currentDay <= $lastDay; $currentDay = strtotime('+1 day', $currentDay)) {
			
			$sun_info = date_sun_info($currentDay, 45.8344167,24.9944197);
			
			
			$sunriseH = $this->getLocalHour($sun_info['sunrise']);
			$sunsetH = $this->getLocalHour($sun_info['sunset']);

			$sunData[]=["day"=>(int)date('j', $currentDay), "sunrise"=>$sunriseH, "sunset"=>$sunsetH];
		}
		
		return $sunData;
	}
	
	function systemDeleteData($year,$month,$options)
	{
		$sqlQueue = $this->getSqlQueue();
		
		$invSQLs = [];
		$readSQLs = [];
		$curvesArray = [];
		
		$waitIDs = [];
		$waitIDsFinal = [];
		
		$tableName = 'actual_readings_'.$month;

		
		if($options->invoices)
		{
			if($month < 12) 
			{
				$imonth = $month+1;
				$iyear = $year;
			}
			else
			{
				$imonth = 1;
				$iyear = $year+1;
			}
			
			$invSQLs[] = "delete from invoices where year(invoice_date) = $iyear and month(invoice_date) = $imonth and supplier_id = ".$_SESSION['select-supplier'];
		}
		
		if($options->consumptions)
		{
			$invSQLs[] = "delete consumptions from consumptions join suppliers on consumptions.supplier_name = suppliers.supplier_name where year(consumptions.consumption_date)=$year and month(consumptions.consumption_date)=$month and suppliers.supplier_id = ".$_SESSION['select-supplier'];
		}
		
		if($options->consumptions || $options->invoices)
			$waitIDsFinal[] = $sqlQueue->sendItem($invSQLs);
		
		if($options->actual_readings)
		{
			$sql = "delete from $tableName where year(reading_datetime) = $year and supplier_id = ".$_SESSION['select-supplier'];
			$waitIDs[] = $sqlQueue->sendItem([$sql]);

			$sql = "delete from forecast_$tableName where year(far_datetime) = $year and month(far_datetime)=$month and supplier_id = ".$_SESSION['select-supplier'];
			$waitIDs[] = $sqlQueue->sendItem([$sql]);
		}
		
		if($options->curves_variance)
		{
			$curvesArray[]="delete from actual_curves_variance where year(consumption_date) = $year and month(consumption_date)=$month and supplier_id = ".$_SESSION['select-supplier'];
		}
		
		if($options->curves)
		{
			$curvesArray[] = "delete actual_curves from actual_curves left join actual_curves_variance on actual_curves.curve_id = actual_curves_variance.curve_id left join $tableName acr on actual_curves.curve_id = acr.curve_id where actual_curves_variance.curve_id is null and acr.curve_id is null and actual_curves.supplier_id = ".$_SESSION['select-supplier'];
		}	
		
		if($options->curves_variance || $options->curves)
		{
			$sqlQueue->waitFor($waitIDs);
			$waitIDsFinal[] = $sqlQueue->sendItem($curvesArray);
		}
		
		$sqlQueue->waitFor($waitIDsFinal);
	}
}