<?php
namespace App\Controllers;

include(APPPATH . 'Libraries/telerik/lib/DataSourceResultNew.php');
use DateTime;

class Api extends BaseController
{	
	public function index()
	{		
		
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			header('Content-Type: application/json');
			
			log_message('info',$_SERVER['HTTP_X_REQUESTED_WITH']);
			$session = \Config\Services::session();
			
			if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
			{
				// code here
				$phpintput = file_get_contents('php://input');
				$request = json_decode($phpintput);
				
				if( isset($request->ebsuser) && $request->ebsuser== 'ebssystem')
					$session->set('user',1);
			}
			else $request = null;
			
			if ($session->get('user') === NULL) return view('login.php');

			// Eliberează lock-ul pe fișierul de sesiune ASAP — altfel CI4 FileHandler
			// serializează toate request-urile aceluiași user (vezi RaportRealizatZilnic
			// unde 5 customCall-uri paralele se executau în coadă FIFO).
			// EXCEPȚIE: nu închide sesiunea pentru acțiunile care SCRIU în $_SESSION
			// — după session_write_close() scrierile pe $_SESSION nu se mai persistă pe disc.
			$sessionWritingActions = ['setSessionVar', 'actualChangeDataDate'];
			if (!in_array($_GET['action'] ?? '', $sessionWritingActions, true)) {
				session_write_close();
			}

			log_message('info','User '. $session->get('user').' ==> '.$_SERVER['REQUEST_URI'].' ==> '.$phpintput);
			
			$dsResult = new \DataSourceResult(config('Database')->default['DSN'],config('Database')->default['username'],config('Database')->default['password']);

			$type = '';
			$table = $_GET['subject'];
			$action = 'grid';
			$field='';
			$result=[];
			
			if(isset($_GET['type']))
				$type = $_GET['type'];
			
			if(isset($_GET['action']))
				$action = $_GET['action'];

			if(isset($_GET['field']))
				$field = $_GET['field'];
			
			if (in_array($table,['companies','purchases','sales','view_raport_monthly_data','view_raport_dezechilibru'])) 
			{
				$table = 'ach_'.$table;
			}
			elseif (!in_array($table,['consumptions','import_consumptions','pods','counties','customers','zones','distributors','suppliers_naming','working_days','working_free_days','working_free_days_profiles','invoices','invoiced_items','view_status_tobeinvoiced','view_to_be_invoiced_incl_contracts', 'service_rates','services','distributors','suppliers','contracts','service_types','view_pods_distributors',
									  'actual_data','actual_curves_variance','actual_report_diff_invoice','actual_curve_profile_mapping','actual_readings','actual_curves','actual_view_badge_readings2','import_daily_readings',
									  'forecast_consumption_types','forecast_consumption_types_options','forecast_customers_consumption_types','forecast_view_available_pods','forecast_view_raport_badges','forecast_view_far_badges','forecast_view_poddata','months_correlation',
									  'procast_regions','procast_region_counties', 'procast_view_regions','procaast_unallocated_counties','procast_prod_forecast',
									  'procast_view_transelectrica_badges','procast_view_transelectrica_pisen',
									  'weather_data',
									  'view_raport_abacus','view_raport_distribuitori'])
					&& !in_array($table,['podDialogData','contractDialogData','invoicedItemsDialogData','consumptionsDialogData','custom']))
			{
				echo json_encode("subject:".$table." not found");
				exit;
			}
			
			if($table == 'custom' && $type == 'call')
			{
				if(method_exists(__CLASS__, $action))
					$result = $this->{$action}($dsResult, $request->models);
				else 
				{
					echo json_encode("action: ".$action." not found");
					exit;
				}
			}
			elseif($action == 'dialog')
			{
				switch ($table)
				{
					case 'podDialogData':
						 if($type == 'read')
						 {
							$arr = explode('_',$request->suggested_customer_name,7);
							$n = $arr[0];
							for($i=1;$i<count($arr);$i++)
								if( strlen($n) < strlen($arr[$i]) ) $n = $arr[$i];
	
	
							$result = $dsResult->query("SELECT * FROM 
														((select customer_id, customer_city as city, customer_address as address from customers where customer_name = '".str_replace('_',' ',$request->suggested_customer_name)."' LIMIT 1)  UNION
														(select customer_id, customer_city as city, customer_address as address from customers where customer_alias = '".$request->suggested_customer_name."' LIMIT 1)  UNION 
														select customer_id, customer_city as city, customer_address as address from customers where customer_name like '%". $n ."%' or customer_alias like '%". $n ."%' LIMIT 1) A LIMIT 1");
							
							if(isset($result['data'][0]))
							{
								$result = $result['data'][0];
								$result['error'] = 0;
							}
							else
							{
								$result['msg'] = 'Vrei sa adaugi un <a href="/ebsMD/customers_management"><b>client nou</b></a> ?';
								$result['type'] = 'error';
								$result['error'] = 1;
							}
						 }							 
					break;
					case 'contractDialogData':
						 $masterDataModel = new \App\Models\MasterDataModel();
						 if($type == 'read')
						 {
							$result = $masterDataModel->readContractDialogData($request->contract_id);
							if(!isset($result))
							{
								$result['msg'] = 'Contractul nu exista sau s-a produs o eroare ?';
								$result['type'] = 'error';
								$result['error'] = 1;
							}
						 }
						 if($type == 'save')
						 {
							foreach($request as $key => $value) 							
								if($value) $request->{$key} = trim($value);
							
							
							$result = $masterDataModel->saveContractDialogData($request);
							if(!isset($result))
							{
								$result['msg'] = 'S-a produs o eroare !';
								$result['type'] = 'error';
								$result['error'] = 1;
							} 
						 }
					break;
					case 'consumptionsDialogData':
						$consumptionDataModel = new \App\Models\ConsumptionsModel();
						 if($type == 'save')
						 {
							$result = $consumptionDataModel->saveConsumptionDialogData($request->models[0]);
							if(!isset($result))
							{
								$result['msg'] = 'Consumul nu exista sau s-a produs o eroare ?';
								$result['type'] = 'error';
								$result['error'] = 1;
							}
						 }
					break;
					case 'invoicedItemsDialogData':
						 $masterDataModel = new \App\Models\MasterDataModel();
						 if($type == 'read')
						 {
							$result = $masterDataModel->readInvoicedItemsDialogData($request->invoice_id, $request->invoiced_item_id);
							if(!isset($result))
							{
								$result['msg'] = 'Factura nu exista sau s-a produs o eroare ?';
								$result['type'] = 'error';
								$result['error'] = 1;
							}
						 }
						 if($type == 'save')
						 {
							foreach($request as $key => $value) 							
								if($value) $request->{$key} = trim($value);
							
							
							$result = $masterDataModel->saveInvoicedItemsDialogData($request);
							if(!isset($result))
							{
								$result['msg'] = 'S-a produs o eroare !';
								$result['type'] = 'error';
								$result['error'] = 1;
							} 
						 }
					break;
				}
			}
			else
			{
				//grid action
				switch ($table)
				{
					default:
						if($type == 'read')
							$table = $this->replaceView($table);
						
						$columns=$dsResult->getTableColumns($table);
						$idColumn = $columns[0];
					break;
					case 'ach_purchases':
						$columns = array('transaction_id', 'supplier_id','seller_id','date_start','date_end','quantity','price');
						$idColumn = 'transaction_id';
						if($type == 'read') $table = $this->replaceView($table);
					break;
					case 'ach_sales':
						$columns = array('transaction_id', 'supplier_id','buyer_id','date_start','date_end','quantity','price');
						$idColumn = 'transaction_id';
						if($type == 'read') $table = $this->replaceView($table);
					break;	
					case 'ach_view_raport_monthly_data':
						$columns = array('id','date','supplier_name','pQuantity','pPrice','pValue','sQuantity','sPrice','sValue','PL','Diff');
						$idColumn = 'id';
					break;
					case 'ach_view_raport_dezechilibru':
						$columns = array('id','Data','ZonaLicenta','Consumator','Oras','Tip','Banda','Cantitate','PretEn','CostUnitarMediu','ValoareEnergie','CostEnergie','Balanta');
						$idColumn = 'id';
					break;
				}
				
				if (isset($request->models[0]) && ($action == 'grid' or $action == 'field'))
				{				
					foreach($request->models[0] as $key => $value) {
						if( (strpos($key, 'date_') !== FALSE || strpos($key, '_date') !== FALSE) && !empty($value) )
							$request->models[0]->{$key} = date("Y-m-d", strtotime($value));
						elseif($value && is_string($value)) $request->models[0]->{$key} = trim($value);
					}
				}
		
				/*if (isset($request->models[0]->date_start))
				{
					unset($request->models[0]->value);
					$request->models[0]->date_start = date("Y-m-d", strtotime($request->models[0]->date_start));
					$request->models[0]->date_end = date("Y-m-d", strtotime($request->models[0]->date_end));
				}*/

				if($action == 'field' && $type == 'read')
				{
					$option = '';
					if (isset($_GET['option'])) $option = $_GET['option'];
					if ($option == 'distinct') 
					{
						$columns = [$field];
						$result = $dsResult->read($table, $columns, $request,true);
					}
					else
						$result = $dsResult->read($table, $columns, $request);
				
				}
				else
				{
					switch($type) {
						case 'create':
							$this->removeViewColumns($columns);
							
							if(isset($request->models[0]) && isset($request->models[0]->supplier_id) && $request->models[0]->supplier_id == 0)
								$request->models[0]->supplier_id = $_SESSION['select-supplier'];
							
							$createAllowed = true;
							if(method_exists(__CLASS__, $table.'_before_create'))
								$createAllowed = $this->{$table.'_before_create'}($dsResult,$request->models, $columns);
							
							if($createAllowed)
								$result = $dsResult->create($table, $columns, $request->models, $idColumn);
							
							if(method_exists(__CLASS__, $table.'_after_create'))
									$this->{$table.'_after_create'}($dsResult,$request, $columns);
								
							break;
						case 'read':					
							$readAllowed = true;
							if(method_exists(__CLASS__, $table.'_before_read'))
							{
								$tResult = $this->{$table.'_before_read'}($dsResult,$request, $columns);
								$readAllowed = $tResult['readAllowed'];
								
								if(isset($tResult['result'])) $result = $tResult['result'];
							}

							if($readAllowed)
							{
								log_message("error","session write close");
								session_write_close();
								$result = $dsResult->read($table, $columns, $request);
							}
							break;
						case 'update':
							if (strpos($table, 'view') === FALSE && strpos($table, 'ach_view') === FALSE)
							{
								$this->removeViewColumns($columns);
								
								$updateAllowed = true;
								if(method_exists(__CLASS__, $table.'_before_update'))
									$updateAllowed = $this->{$table.'_before_update'}($dsResult,$request->models, $columns);
								
								if($updateAllowed)
									$result = $dsResult->update($table, $columns, $request->models, $idColumn);
							
								if(method_exists(__CLASS__, $table.'_after_update'))
									$this->{$table.'_after_update'}($dsResult,$request, $columns);
							}
							break;
						case 'destroy':
							$destroyAllowed = true;
							
							if(method_exists(__CLASS__, $table.'_before_delete'))
								$destroyAllowed = $this->{$table.'_before_delete'}($dsResult,$request->models, $columns);
							
							if($destroyAllowed)
								$result = $dsResult->destroy($table, $request->models, $idColumn);
							
							if(method_exists(__CLASS__, $table.'_after_delete'))
								$destroyAllowed = $this->{$table.'_after_delete'}($dsResult,$request->models, $columns);
							
							break;
						case 'save':
							$fileName = $_POST['fileName'];
							$contentType = $_POST['contentType'];
							$base64 = $_POST['base64'];

							$data = base64_decode($base64);

							header('Content-Type:' . $contentType);
							header('Content-Length:' . strlen($data));
							header('Content-Disposition: attachment; filename=' . $fileName);
							exit;
							break;
						case 'truncate':
							if (in_array($table, ['import_consumptions','actual_data']))
							{
								$dsResult->query("TRUNCATE TABLE " . $table);
								$result['msg'] = 'Datele au fost sterse!';
							}
							break;
						case 'review':
								if ($table == 'import_consumptions')
								{
									$importConsumptionsModel = new \App\Models\ImportConsumptionsModel();
									$result = $dsResult->query("select * from import_consumptions where consumption_id>0");
									foreach($result['data'] as $r)
									{
										$error = $importConsumptionsModel->checkReading($r['distributor_name'],$r['supplier_name'],$r['customer_name'],$r['customer_code'],$r['contract_number'],$r['consumption_location_id'],$r['pod'],$r['voltage_level_delimitation'],$r['voltage_level_measurment'],$r['invoice_start_date'],$r['invoice_end_date'],$r['reading_start_date'],$r['reading_end_date'],$r['device_serial_number'],$r['energy_type'],$r['index_old'],$r['index_new'],$r['total_consumption_ae'],$r['total_consumption_re'],$r['total_consumption_re_3x'],$r['total_consumption_mu'],$request->consumption_date, $r['source']);								
										if($error != $r['error'])
										{
											$importConsumptionsModel->updateReadingError($r['consumption_id'], $error);
										}
									}
									$result = $dsResult->query("select count(distinct consumption_id) as msg from import_consumptions where error = 'POD nou'");
									$result = $result['data'][0];
									if($result['msg'] == 0) $result['type'] = 'info';
									else $result['type'] = 'error';
								}
								elseif ($table == 'actual_data')
								{
									$result = $dsResult->query("select count(distinct data_id) as msg from actual_view_data where error <> ''");
									$result = $result['data'][0];
									if($result['msg'] == 0) $result['type'] = 'info';
									else $result['type'] = 'error';
								}
								break;
						case 'save_consumptions':
							if ($table == 'import_consumptions')
							{
								$consumptionDate = DateTime::createFromFormat('Y-m-d', $request->consumption_date)->format('Y-m-t');
										
								$importConsumptionsModel = new \App\Models\ImportConsumptionsModel();
								$affected = $importConsumptionsModel->saveConsumptions($consumptionDate);
								
								$result['msg'] = $affected. ' consumuri au fost salvate.';
								$result['type'] = 'info';
							}	
							elseif ($table == 'actual_data')
							{
								$importActualModel = new \App\Models\Realizat\ImportActualModel();
								$result = $importActualModel->saveReadings();
							}
							break;
						//echo $data;
					}
				}
			}
			
			echo json_encode($result);

			exit;
		}
		
		return view('login.php'); 
	}

	private function replaceView($table)
	{
		if($table == 'ach_purchases') $table = 'ach_view_purchases';
		elseif($table == 'ach_sales') $table = 'ach_view_sales';
		elseif($table == 'ach_companies') $table = 'ach_view_companies';
		elseif($table == 'distributors') $table = 'view_distributors';
		elseif($table == 'services') $table = 'view_services';
		elseif($table == 'service_rates') $table = 'view_service_rates';
		elseif($table == 'consumptions') $table = 'view_consumptions2';
		elseif($table == 'invoices') $table = 'view_invoices';
		elseif($table == 'invoiced_items') $table = 'view_invoiced_items';
		elseif($table == 'customers') $table = 'view_customers';
		elseif($table == 'contracts') $table = 'view_contracts';
		elseif($table == 'zones') $table = 'view_zones';
		elseif($table == 'pods') $table = 'view_pods';
		elseif($table == 'actual_data') $table = 'actual_view_data';
		elseif($table == 'actual_curves_variance') $table = 'actual_view_curves_variance';
		elseif($table == 'actual_curve_profile_mapping') $table = 'actual_view_curve_profile_mapping';
		//elseif($table == 'actual_readings') $table = 'actual_view_readings';
		elseif($table == 'actual_curves') $table = 'actual_view_curves';
		elseif($table == 'forecast_consumption_types') $table = 'forecast_view_consumption_types';
		elseif($table == 'forecast_customers_consumption_types') $table = 'forecast_view_customers_consumption_types';
		elseif($table == 'procast_region_counties') $table = 'procast_view_regions_counties';
		elseif($table == 'procast_regions') $table = 'procast_view_regions';
		elseif($table == 'months_correlation') $table = 'view_months_correlation';
		
		
		
		return $table;
	}
	
	private function removeViewColumns(&$columns)
	{
		for($i =0; $i<count($columns);)
			if($columns[$i] == 'timestamp'  || str_contains($columns[$i], 'calculated'))
				array_splice($columns, $i, 1); 
			else $i++;
	}
	
	/************************EVENT CALLS***************************/
	
	private function ach_view_purchases_before_read($dsResult, $data, &$columns)
	{
		array_push($columns,'value');
		array_push($columns,'company_order');

		// Adaugă company_order ca sort secundar (după sort-ul utilizatorului).
		// mergeSortDescriptors (modificat) pune groups primul → ORDER BY date_start, [user sort], company_order.
		$existing = isset($data->sort) ? (array)$data->sort : [];
		$already  = array_filter($existing, fn($s) => $s->field === 'company_order');
		if (empty($already)) {
			$co = new \stdClass(); $co->field = 'company_order'; $co->dir = 'asc';
			$existing[] = $co;
		}
		$data->sort = $existing;

		return ['readAllowed'=>true];
	}

	private function copyPreviousMonthPurchases($dsResult, $data)
	{
		$year  = (int)($data[0]->year  ?? 0);
		$month = (int)($data[0]->month ?? 0);

		if($year <= 0 || $month < 1 || $month > 12)
			return ['error' => "Parametri invalizi (year=$year, month=$month). Reincarcati pagina."];

		$prevMonth = isset($data[0]->srcMonth) ? (int)$data[0]->srcMonth : $month - 1;
		$prevYear  = isset($data[0]->srcYear)  ? (int)$data[0]->srcYear  : $year;
		if($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

		if($prevMonth < 1 || $prevMonth > 12 || $prevYear <= 0)
			return ['error' => "Luna sursa invalida ($prevMonth/$prevYear)."];

		$existing = $dsResult->query("SELECT COUNT(*) AS nr FROM ach_purchases WHERE YEAR(date_start)=$year AND MONTH(date_start)=$month");
		if(isset($existing['data'][0]['nr']) && $existing['data'][0]['nr'] > 0)
			return ['error' => "Luna $month/$year contine deja {$existing['data'][0]['nr']} inregistrari. Stergeti-le inainte de a copia."];

		$rows = $dsResult->query("SELECT supplier_id, seller_id, quantity, price FROM ach_purchases WHERE YEAR(date_start)=$prevYear AND MONTH(date_start)=$prevMonth");
		if(empty($rows['data']))
			return ['error' => "Nu exista inregistrari in $prevMonth/$prevYear pentru a copia."];

		$daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
		$dateStart   = sprintf('%04d-%02d-01', $year, $month);
		$dateEnd     = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

		$inserted = 0;
		foreach($rows['data'] as $row)
		{
			$sid  = (int)$row['supplier_id'];
			$seld = (int)$row['seller_id'];
			$qty  = (float)$row['quantity'];
			$prc  = (float)$row['price'];
			$dsResult->query("INSERT INTO ach_purchases (supplier_id, seller_id, date_start, date_end, quantity, price) VALUES ($sid, $seld, '$dateStart', '$dateEnd', $qty, $prc)");
			$inserted++;
		}

		return ['success' => "$inserted inregistrari copiate din $prevMonth/$prevYear in $month/$year."];
	}

	private function ach_sales_before_read($dsResult, $data, &$columns)
	{
		array_push($columns,'value');
		
		return ['readAllowed'=>true];
	}
	
	private function view_status_tobeinvoiced_before_read($dsResult, $data, &$columns)
	{
		if(isset($data->invoice_date))
		{
			$invoice_date = DateTime::createFromFormat('d/m/Y', $data->invoice_date)->format('Y-m-d');
			$dsResult->query("SET @pInvoiceDate='".$invoice_date."'");
			return ['readAllowed'=>true];
		}
		
		return ['readAllowed'=>false];
	}

	private function invoices_after_create($dsResult, $data, &$columns)
	{
		$invoicesModel = new \App\Models\InvoicesModel();
		
		//echo print_r($data); exit();
		$invoicesModel->populateInvoice($data->models[0]->invoice_id,$data->models[0]->customer_id,$data->models[0]->zone_id,$data->models[0]->contract_id,$data->models[0]->storno_no);
	}
	
	private function invoices_before_delete($dsResult, $data, &$columns)
	{
		$invoicesModel = new \App\Models\InvoicesModel();

		return ($invoicesModel->get_invoice_status($data[0]->invoice_id) == 'In pregatire');
	}
	
	private function view_status_tobeinvoiced_before_delete($dsResult, $data, &$columns)
	{
		$idList = [];
		
		if(isset($data[0]->id))
		{
			array_push($idList,$data[0]->id);
		}
		elseif (isset($data[0]->ids))
		{
			$idList = $data[0]->ids;
		}
			
		$invoicesModel = new \App\Models\InvoicesModel();
		
		//row-supplierId-customerId-contractId-zoneId
		foreach($idList as $id)
		{
			$row = explode('-',$id,5);
			if ($row !== false && count($row)==5)
				$invoicesModel->deleteConsumptionNotInvoiced($row[1],$row[2],$row[3],$row[4]);
		}
		
		return false;
	}

	private function ach_companies_before_delete($dsResult, $data, &$columns)
	{
		if($data[0]->company_name == 'Clienti') return false;
		
		return true;
	}

	private function contracts_before_delete($dsResult, $data, &$columns)
	{
		$masterDataModel = new \App\Models\MasterDataModel();
		return $masterDataModel->deleteServiceRateByContractId($data[0]->contract_id);
	}
	
	private function customers_after_update($dsResult, $data, &$columns)
	{
		$masterDataModel = new \App\Models\MasterDataModel();
		$masterDataModel->updateCustomerStatus($data->models[0]->customer_id, $data->models[0]->customer_status);
		return true;
	}

	private function pods_after_update($dsResult, $data, &$columns)
	{
		$masterDataModel = new \App\Models\MasterDataModel();
		$masterDataModel->updatePODStatus($data->models[0]->pod_no, $data->models[0]->pod_status);
		return true;
	}

	private function invoiced_items_after_update($dsResult, $data, &$columns)
	{
		if(isset($data->models[0]->invoice_id))
		{
			
			$r = $dsResult->query('SELECT COUNT(invoiced_item_no) AS no FROM invoiced_items WHERE invoice_id = '.$data->models[0]->invoice_id.' AND invoiced_item_no='.$data->models[0]->invoiced_item_no);
			
			if($r['data'][0]['no']>1)
				$dsResult->query('UPDATE invoiced_items SET invoiced_item_no = invoiced_item_no + 1 WHERE invoiced_item_no>='.$data->models[0]->invoiced_item_no.' AND invoiced_item_id !='.$data->models[0]->invoiced_item_id.' AND invoice_id = '.$data->models[0]->invoice_id);
			$dsResult->query("call updateInvoiceTotals(".$data->models[0]->invoice_id.")");
			return true;
		}
		
		return false;
	}
	
	private function invoiced_items_after_create($dsResult, $data, &$columns)
	{
		if(isset($data->models[0]->invoice_id))
		{
			$dsResult->query("call updateInvoiceTotals(".$data->models[0]->invoice_id.")");
			return true;
		}
		
		return false;
	}

	private function invoiced_items_after_delete($dsResult, $data, &$columns)
	{
		if(isset($data[0]->invoice_id))
		{
			$dsResult->query("call updateInvoiceTotals(".$data->models[0]->invoice_id.")");
			return true;
		}
		
		return false;
	}
	
	private function actual_readings_before_read($dsResult, $data, &$columns)
	{
		$tableName = 'actual_readings_'.($data->month);
	
		$wStr = " WHERE ar.supplier_id =".$data->supplierID." AND ar.year = ".$data->year;
		
		if($data->distributorID != null)
			$wStr .= " AND d.distributor_id = ".$data->distributorID;
			
		$sql = "
		SELECT A.reading_id, A.supplier_id, A.reading_datetime, A.customer_name, A.curve_name, A.curve_type, A.distributor_id, A.distributor_name, A.ea, sum(acv.consumption_ea)/1000 as consumption_ea, COUNT(distinct acv.pod) AS NoPODs, 
		group_concat(distinct acv.pod) AS POD , A.group_id, A.distributor_anre_report_index, 1 as deletable
		FROM (
		SELECT ar.reading_id AS reading_id,ar.supplier_id AS supplier_id, CONCAT(YEAR(ar.reading_datetime),'-', MONTH(ar.reading_datetime),'-01') AS reading_datetime, COALESCE(rc.customer_name,'Nespecificat') AS customer_name,ac.curve_id, ac.curve_name AS curve_name,ac.curve_type AS curve_type,d.distributor_id AS distributor_id,d.distributor_name AS distributor_name,d.distributor_anre_report_index, SUM(ar.actual_ea) AS ea, CONCAT(ar.supplier_id,'-', YEAR(ar.reading_datetime),'-', MONTH(ar.reading_datetime),'-',coalesce(ar.curve_id,0),'-', COALESCE(rc.customer_id,0)) AS group_id
		FROM (($tableName ar
		JOIN actual_curves ac ON(ar.curve_id = ac.curve_id)
		JOIN distributors d ON(ac.distributor_id = d.distributor_id))
		LEFT JOIN customers rc ON(ar.customer_id = rc.customer_id))
		$wStr
		GROUP BY ar.supplier_id, YEAR(ar.reading_datetime), MONTH(ar.reading_datetime),ar.curve_id,rc.customer_id
		) A JOIN actual_curves_variance acv ON year(acv.consumption_date) = year(A.reading_datetime) AND month(acv.consumption_date) = month(A.reading_datetime) AND acv.curve_id = A.curve_id 
		GROUP BY acv.curve_id, A.reading_datetime
		#ORDER BY A.reading_datetime,A.distributor_anre_report_index
		UNION 
		SELECT acv.acv_id, acv.supplier_id, acv.consumption_date AS reading_datetime, c.customer_name, ac.curve_name,  'necunoscuta' as curve_type, ac.distributor_id,d.distributor_name,0 AS ea, acv.consumption_ea/1000 AS consumption_ea,1 AS NoPODs, acv.pod AS POD, acv_id AS group_id, d.distributor_anre_report_index, 0 as deletable  FROM actual_curves_variance acv
		JOIN pods p ON acv.pod = p.pod_no
		JOIN customers c ON p.customer_id = c.customer_id
		JOIN actual_curves ac ON acv.curve_id = ac.curve_id
		JOIN distributors d ON ac.distributor_id = d.distributor_id
		WHERE YEAR(acv.consumption_date) = ".$data->year." and MONTH(acv.consumption_date) = ".$data->month." AND consumption_ea != 0 AND
		acv.curve_id NOT IN (SELECT curve_id FROM $tableName ar WHERE ar.curve_id=acv.curve_id)
		#ORDER BY d.distributor_anre_report_index
		";
		
		$wStr2 = "";
		if(!empty($data->quick))
		{
			$wStr2 = "WHERE (Z.customer_name like '%".$data->quick."%' OR Z.curve_name like '%".$data->quick."%' OR Z.curve_type like '%".$data->quick."%' OR Z.POD like '%".$data->quick."%')";
		}			
		
		$sql = "SELECT * FROM ($sql) Z $wStr2 order by distributor_anre_report_index";
		
		
		log_message('error',$sql);
		
		//$tResult['result'] = $dsResult->query('call actual_sp_readings("'.$sql.'")');
		$tResult['result'] = $dsResult->query($sql);
		$tResult['readAllowed']=false;
		
		return $tResult;
	}
	
	private function forecast_view_poddata_before_read($dsResult, $data, &$columns)
	{
		$month = $data->month;
		$year = $data->year;
		
		$qWhr = '';
		if(!empty($data->quick))
			$qWhr = " AND (p.pod_no like '%{$data->quick}%' OR c.customer_name like '%{$data->quick}%' OR p.county like '%{$data->quick}%') ";
		
		$tableName = 'forecast_actual_readings_'.$month;
		$lastDay = date("Y-m-t", strtotime("$year-$month-01"));
				
		$sql = "
			SELECT p.pod_id, c.supplier_id,  c.customer_id, c.customer_name, p.pod_no,p.county, SUM(co.total_consumption_ae)/1000 AS ea, (select min(consumption_date) from consumptions where pod = p.pod_no) as consumption_date FROM pods p 
			JOIN customers c ON p.customer_id = c.customer_id AND  c.customer_status = 'Activ'
			JOIN contracts ct ON p.customer_id = ct.customer_id AND (ct.contract_stop IS NULL OR ct.contract_stop >= NOW()) 
			LEFT JOIN consumptions co ON co.pod = p.pod_no AND co.consumption_date = '$lastDay'
			LEFT JOIN $tableName far ON far.pod = p.pod_no AND hour(far.far_datetime)=1 AND YEAR(far.far_datetime) < $year
			LEFT JOIN forecast_synthetics fs ON month(fs.synthetics_datetime) = $month AND hour(fs.synthetics_datetime) = 1 AND fs.customer_id = p.customer_id 
			WHERE co.consumption_id IS NOT NULL AND far.far_id IS NULL AND fs.fs_id IS NULL $qWhr
			GROUP BY p.pod_no
			ORDER by consumption_date
		";
		
		log_message('info',$sql);
		
		$tResult['result'] = $dsResult->query($sql);
		$tResult['readAllowed']=false;
		
		return $tResult;
	}
	
	private function forecast_consumption_types_before_create($dsResult, $data, &$columns)
	{
		$mdModel = new \App\Models\MasterDataModel();
		$data[0]->order = $mdModel->getMaxOrder('forecast_consumption_types')+1;
		log_message('error',print_r($data,true));
		return true;
	}
	
	private function forecast_consumption_types_after_create($dsResult, $data, &$columns)
	{
		$forecastModel = new \App\Models\Prognoza\ForecastModel();
		
		$forecastModel->setConsumptioTypeOptions($data->models[0]);
	}

	private function forecast_consumption_types_before_update($dsResult, $data, &$columns)
	{
		if(!isset($data[0]->order))
		{
			$mdModel = new \App\Models\MasterDataModel();
			$data[0]->order = $mdModel->getIDOrder('forecast_consumption_types','fct_id',$data[0]->fct_id);
		}
		
		return true;
	}
	
	private function forecast_consumption_types_after_update($dsResult, $data, &$columns)
	{
		$forecastModel = new \App\Models\Prognoza\ForecastModel();
		
		$forecastModel->setConsumptioTypeOptions($data->models[0]);
	}
	
	private function working_free_days_profiles_before_delete($dsResult, $data, &$columns)
	{
		if(isset($data[0]->wfd_profile_id))
		{
			$mdModel = new \App\Models\MasterDataModel();
			$mdModel->removeWorkingFreeDaysProfile($data[0]->wfd_profile_id);
		}
		
		return false;
	}
	
	private function working_free_days_profiles_after_create($dsResult, $data, &$columns)
	{
		if(isset($data->models[0]->sourceProfile) && isset($data->models[0]->wfd_profile_id))
		{
			$mdModel = new \App\Models\MasterDataModel();
			$mdModel->copyWorkingFreeDaysProfile($data->models[0]->sourceProfile,$data->models[0]->wfd_profile_id);
		}
		
		return false;
	}
	
	private function working_free_days_before_delete($dsResult, $data, &$columns)
	{
		if(isset($data[0]->wfd_id))
		{
			$mdModel = new \App\Models\MasterDataModel();
			$mdModel->removeWorkingFreeDays($data[0]->wfd_id, $data[0]->free_date);
		}
		
		return false;
	}
	
	/***********************  PROCAST EVENTS  *********************/
	private function procast_region_counties_before_delete($dsResult, $data, &$columns)
	{
		if($data[0]->rc_id == $data[0]->region_name)
		{
			$pcModel = new \App\Models\Prognoza\PCModel();
			$pcModel->deleteRegionByName($data[0]->region_name);

			return false;
		}

		return true;
	}
	
	/************************CUSTOM CALLS***************************/

	private function executeAlert($dsResult, $data)
	{
		$mdModel = new \App\Models\MasterDataModel();
		$mdModel->executeAlert($data[0]->alertID);

		return true;
	}
	
	private function reorder($dsResult,$data)
	{
		$mdModel = new \App\Models\MasterDataModel();
		$mdModel->reorder($data[0]->subject, $data[0]->id_field,$data[0]->direction,$data[0]->id_value);
		
		return [];
	}

	private function invoiceAll($dsResult,$data)
	{
		$idList = [];
		
		if(isset($data[0]->id))
		{
			array_push($idList,$data[0]->id);
		}
		elseif (isset($data[0]->ids))
		{
			$idList = $data[0]->ids;
		}
		
		if(!$idList || !isset($data[0]->invoice_date))
			return false;
		$invoice_date = DateTime::createFromFormat('d/m/Y', $data[0]->invoice_date)->format('Y-m-d');
		
		$invoicesModel = new \App\Models\InvoicesModel();
		
		$invoicesModel->billEverything2($idList,$invoice_date);
		//row-supplierId-customerId-contractId-zoneId
		
		return true;
	}
	
	public function bulkInvoiceDestroy($dsResult, $data)
	{
		$response = [];
		$invoicesModel = new \App\Models\InvoicesModel();
		$response['deleted_no'] = $invoicesModel->bulkDestroy($data[0]->invoiceIds); 
		return $response;		
	}

	public function bulkInvoiceRelease($dsResult, $data)
	{
		$response = [];
		$invoicesModel = new \App\Models\InvoicesModel();
		$response['invoice_nos'] = $invoicesModel->bulkRelease($data[0]->invoiceIds);
		
		$pdfInvoice = new PdfInvoice();
		foreach(array_keys($response['invoice_nos']) as $invoiceId)
			$pdfInvoice->generatePDF($invoiceId,false,true);
		
		$response['total'] = count($response['invoice_nos']);
		return $response;
	}
	
	public function releaseInvoice($dsResult, $data)
	{
		$response = [];
		$invoicesModel = new \App\Models\InvoicesModel();
		
		if (!empty($data[0]->invoiceId) && $invoicesModel->get_invoice_status($data[0]->invoiceId) == 'In pregatire') 
		{
			$pdfInvoice = new PdfInvoice();
			$invoicesModel->emiteFactura($data[0]->invoiceId);
			$pdfInvoice->generatePDF($data[0]->invoiceId,false,true);
			
			$response['invoice_no'] = $invoicesModel->get_invoice_no($data[0]->invoiceId);
		}
		
		return $response;
	}
	
	public function saveInvoiceComments($dsResult, $data)
	{
		$invoicesModel = new \App\Models\InvoicesModel();
		
		$response['ret'] = $invoicesModel->save_comments($data[0]->invoiceId,$data[0]->comments);
		
		return $response;
	}
	
	public function bulkConsumptionsDestroy($dsResult, $data)
	{
		$response = [];
		$consumptionsModel = new \App\Models\ConsumptionsModel();
		$response['deleted_no'] = $consumptionsModel->bulkDestroy($data[0]->consumptionsIDs); 
		return $response;		
	}

	private function getMinMaxYears($dsResult, $data)
	{
		$masterDataModel = new \App\Models\MasterDataModel();
		$response = $masterDataModel->get_min_max_years($data[0]->subject,$data[0]->field);	
		
		$response['minYear'] = intval($response['minY']);
		$response['maxYear'] = intval($response['maxY']);
		$response['id'] = $data[0]->field;
		
		return $response;
	}
	
	private function getMonthlyBadges($dsResult, $data)
	{	
		log_message("info","session write close");
		session_write_close();
		$result = false;
		
		if(isset($data[0]->field) && isset($data[0]->year) && isset($data[0]->subject))
		{
			$subject = $data[0]->subject;
			$field = $data[0]->field;
			$filter = '';
			
			if($subject == 'sales' || $subject == 'purchases') $subject ='ach_'.$subject;
			$subject = $this->replaceView($subject);
			if(isset($data[0]->option))
			{
				if($data[0]->option == 'tariffList') $filter = " and service_code not in ('EA','TAXOPCOMH') ";
				elseif ($data[0]->option == 'priceList') $filter = " and service_code in ('EA','TAXOPCOMH') ";  								
				elseif (substr($data[0]->option,0,13) == 'wfdProfileId:') $filter = " and wfd_profile_id = ".substr($data[0]->option,13);  
				elseif ($subject == 'weather_data') $filter = " and county_code = 'B' and layer = 'temp'"; 
				elseif ($subject == 'forecast_estimates') $field = "DISTINCT customer_id"; 				
			}
			
			$result = $dsResult->query("SELECT COUNT(".$field.") AS nr,YEAR(".$data[0]->field.") AS year ,MONTH(".$data[0]->field.") AS month FROM ".$subject." 
										WHERE YEAR(".$data[0]->field.")=".$data[0]->year.$filter."
										GROUP BY MONTH(".$data[0]->field.")");
			
			$result["id"] = $data[0]->field;
		}
		else
			return $data[0]->field;
		
		return $result;
	}

	private function getSubjectBadges($dsResult, $data)
	{	
		log_message("error","session write close");
		session_write_close();
		$result = false;
		
		if(isset($data[0]->field) && isset($data[0]->subject))
		{
			$subject = $data[0]->subject;
			$filter = '';$group='';$count = "COUNT({$data[0]->field})";
			
			if($subject == 'sales' || $subject == 'purchases') $subject ='ach_'.$subject;
			$subject = $this->replaceView($subject);
			if(isset($data[0]->option))
			{
				if($data[0]->option == 'daily_readings_distributor') $group = ", DATE(reading_datetime)";
				elseif($subject == 'weather_data') return [];
				/*if($data[0]->option == 'tariffList') $filter = " and service_code not in ('EA','TAXOPCOMH') ";
				elseif ($data[0]->option == 'priceList') $filter = " and service_code in ('EA','TAXOPCOMH') ";  								
				elseif (substr($data[0]->option,0,13) == 'wfdProfileId:') $filter = " and wfd_profile_id = ".substr($data[0]->option,13);  */
			}
			
			$result = $dsResult->query("SELECT $count AS nr FROM $subject $filter GROUP BY {$data[0]->field} $group");
			
			$result["id"] = $data[0]->field;
		}
		else
			return $data[0]->field;
		
		return $result;
	}

	private function getActiveBadges($dsResult, $data)
	{		
		log_message("error","session write close");
		session_write_close();
		
		if(isset($data[0]->field) && isset($data[0]->subject))
		{
			$subject = $data[0]->subject;
			$filter = '';
			
			$subject = $this->replaceView($subject);
			
			return $dsResult->query("SELECT COUNT(".$data[0]->field.") AS nr FROM ".$subject." GROUP BY ".$data[0]->field );
		}
		else
			return $data[0]->field;
		
		return false;
	}
	
	function cleanString($text) {
		$utf8 = array(
			'/[Ş]/u'	=>	'ş',
			'/[Â]/u'	=>	'â',
			'/[Ţ]/u'	=>	'ţ',	
			'/[Ă]/u'	=>	'ă',
			'/-/u'	=>		' ',
		);
		return preg_replace(array_keys($utf8), array_values($utf8), $text);
	}

	private function getANAFData($dsResult,$data)
	{
		if(isset($data[0]->cui))
		{
			$cui = str_replace("RO","",$data[0]->cui);
			$postfields = [];
			$url='https://webservicesp.anaf.ro:/PlatitorTvaRest/api/v8/ws/tva';
			$header = ["Content-Type: application/json"];
			$postfields[] = ['cui'=>$cui, 'data'=>date('Y-m-d')];
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; MSIE 7.01; Windows NT 5.0)");
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 130);
			curl_setopt($ch, CURLOPT_TIMEOUT, 130);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST,TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($postfields));
			$result = curl_exec($ch);
			
			$jResult = json_decode($result);
			log_message('error',print_r($jResult, true));
			
			if($jResult->message == "SUCCESS")
			{
				//remove unwanted strings
				$jResult->found[0]->adresa_domiciliu_fiscal->ddenumire_Localitate = str_replace(["MUNICIPIUL ","Mun. ","Municipiul "],"",$jResult->found[0]->adresa_domiciliu_fiscal->ddenumire_Localitate);
				$jResult->found[0]->adresa_domiciliu_fiscal->ddenumire_Strada = str_replace(["MUNICIPIUL ","Mun. ","Municipiul "],"",$jResult->found[0]->adresa_domiciliu_fiscal->ddenumire_Strada);
				
				//convert characters
				$jResult->found[0]->adresa_domiciliu_fiscal->ddenumire_Judet = str_replace(["MUNICIPIUL ","Mun. ","Municipiul "],"",ucwords(strtolower($this->cleanString($jResult->found[0]->adresa_domiciliu_fiscal->ddenumire_Judet))));
				$jResult->found[0]->adresa_domiciliu_fiscal->ddenumire_Judet = str_replace("Bistriţa Năsăud","Bistriţa-Năsăud",$jResult->found[0]->adresa_domiciliu_fiscal->ddenumire_Judet);
				$jResult->found[0]->adresa_domiciliu_fiscal->ddenumire_Judet = str_replace("Caraş Severin","Caraş-Severin",$jResult->found[0]->adresa_domiciliu_fiscal->ddenumire_Judet);
				
				if(!isset($jResult->found[0]->date_generale->nrRegCom) ||  $jResult->found[0]->date_generale->nrRegCom == '')
					$jResult->found[0]->date_generale->nrRegCom = ' - ';
				//add RO to CUI
				if($jResult->found[0]->inregistrare_scop_Tva->scpTVA == 1)
					$jResult->found[0]->date_generale->cui = "RO".$jResult->found[0]->date_generale->cui;
			}
			
			return $jResult; 
			//log_message('error',print_r($d,true));
		}
		
		return false;
	}
	
	public function getActiveCustomers($dsResult, $data)
	{
		$response = [];
		$mdModel = new \App\Models\MasterDataModel();
		
		$response = $mdModel->getActiveCustomersTArray();
		
		return $response;
	}
	
	public function setCustomerStatus($dsResult, $data)
	{
		$response = [];
		$mdModel = new \App\Models\MasterDataModel();
		
		if (!empty($data[0]->customer_id) && !empty($data[0]->customer_status)) 
		{
			$mdModel->updateCustomerStatus($data[0]->customer_id, $data[0]->customer_status);
		}
		
		return $response;
	}

	public function setServiceStatus($dsResult, $data)
	{
		$response = [];
		$mdModel = new \App\Models\MasterDataModel();
		
		if (!empty($data[0]->service_id) && !empty($data[0]->service_status)) 
		{
			$mdModel->updateServiceStatus($data[0]->service_id, $data[0]->service_status);
		}
		
		return $response;
	}

	public function setSessionVar($dsResult, $data)
	{
		$response = [];
		
		if (!empty($data[0]->sessionVar) && !empty($data[0]->value)) 
		{
			$_SESSION[$data[0]->sessionVar]  = $data[0]->value;
		}
		elseif (!empty($data[0]->sessionVar) && empty($data[0]->value))
		{
			unset($_SESSION[$data[0]->sessionVar]);
		}
		return $response;
	}	

	public function getSessionVar($dsResult, $data)
	{
		$response = [];
		
		if (!empty($data[0]->sessionVar) && !empty($data[0]->supplierID) && $data[0]->supplierID == $_SESSION['select-supplier'] && isset($_SESSION[$data[0]->sessionVar])) 
		{
			$response[$data[0]->sessionVar] = $_SESSION[$data[0]->sessionVar];
		}
		else
		{
			$response[$data[0]->sessionVar] = '';
		}
		return $response;
	}
	
	public function createWorkingFreeDays($dsResult, $data)
	{
		
		$mdModel = new \App\Models\MasterDataModel();
		$nrZile = $mdModel->createWorkingFreeDays($data[0]->wfdProfileId, $data[0]->freeDate);
		
		return ["$nrZile adaugate"];
	}
	
	public function createWorkingOneFreeDay($dsResult, $data)
	{
		$mdModel = new \App\Models\MasterDataModel();
		$nrZile = $mdModel->createWorkingFreeDays($data[0]->wfdProfileId, $data[0]->freeDate, $data[0]->name, $data[0]->mapping);
		
		return ["$nrZile adaugate/modificate"];
	}
	
	public function customersFreeDaysReport($dsResult, $data)
	{
		$farModel = new \App\Models\Prognoza\FarModel();
		$result = $farModel->customersFreeDaysReport($data[0]->month, $data[0]->year);
		
		return $result;
	}
	
	public function getFreeDays($dsResult, $data)
	{
		$response = [];
		$mdModel = new \App\Models\MasterDataModel();
		$customers = '';
		if(isset($data[0]->customers))
			$customers = $data[0]->customers;
		
		$response['freeDays'] = $mdModel->getFreeDays($data[0]->year,$data[0]->month, $customers);
		$response['month']=$data[0]->month;
		
		return $response;
	}

	public function getSunData($dsResult, $data)
	{
		$response = [];
		$mdModel = new \App\Models\MasterDataModel();
		$response['sunData'] = $mdModel->getSunData($data[0]->year,$data[0]->month);
		$response['month']=$data[0]->month;
		return $response;
	}
	
	public function getServicePriceFromInvoice($dsResult, $data)
	{
		$response = [];
		$mdModel = new \App\Models\MasterDataModel();
		
		if (!empty($data[0]->service_id) && !empty($data[0]->invoice_id) && !empty($data[0]->distributor_id)) 
			$response['service_value'] = $mdModel->getServicePriceFromInvoice($data[0]->service_id, $data[0]->invoice_id,$data[0]->distributor_id);
		
		
		return $response;
	}
	
	public function actualChangeDataDate($dsResult, $data)
	{
		$response = [];
		$importActualModel = new \App\Models\Realizat\ImportActualModel();
		
		if (!empty($data[0]->actual_data_month) && !empty($data[0]->actual_data_year)) 
		{
			$_SESSION['actual-data-date']=$data[0]->actual_data_month.'-'.$data[0]->actual_data_year;
			//$importActualModel->changeDataDate($data[0]->actual_data_month,$data[0]->actual_data_year);
		}
		
		return $response;
	}
	
	private function actual_data_before_delete($dsResult, $data, &$columns)
	{
		$importActualModel = new \App\Models\Realizat\ImportActualModel();

		$importActualModel->removeReadings($data[0]->distributor_name,$data[0]->curve_name);
		
		return false;
	}
	
	private function actual_data_before_update($dsResult, $data, &$columns)
	{
		$importActualModel = new \App\Models\Realizat\ImportActualModel();

		$importActualModel->updateReadings($data[0]->distributor_name,$data[0]->curve_name,$data[0]->year,$data[0]->month,$data[0]->new_distributor_name);
		
		return false;
	}
	
	public function bulkActualReadingsDestroy($dsResult, $data)
	{
		$response = [];
		$importActualModel = new \App\Models\Realizat\ImportActualModel();
		
		if (!empty($data[0]->acutualReadingsGroupIds)) 
		{		
			$response['deleted_no'] = $importActualModel->bulkActualReadingsDestroy2($data[0]->acutualReadingsGroupIds);
		}
		
		return $response;
	}
	
	public function bulkCurvesVarianceDestroy($dsResult, $data)
	{
		$response = [];
		$importActualModel = new \App\Models\Realizat\ImportActualModel();
		
		if (!empty($data[0]->curvesVariancesIds)) 
		{		
			$response['deleted_no'] = $importActualModel->bulkCurvesVarianceDestroy($data[0]->curvesVariancesIds);
		}
		
		return $response;
	}
	
	public function bulkCurvesDestroy($dsResult, $data)
	{
		$response = [];
		$importActualModel = new \App\Models\Realizat\ImportActualModel();
		
		if (!empty($data[0]->curvesIds)) 
		{		
			$response['deleted_no'] = $importActualModel->bulkCurvesDestroy($data[0]->curvesIds);
		}
		
		return $response;
	}
	
	
	public function getCustomerByDistributor($dsResult, $data)
	{
	
	$response = [];
	$importActualModel = new \App\Models\Realizat\ImportActualModel();
	
	$distibuitorID = $data[0]->distributor_id ?? 0;
	$date = $data[0]->date ?? '2000-01';
	
	$response = $importActualModel->getCustomerByDistributor($distibuitorID,$date);
	
	return $response;
	
	}
	
	public function getCurvesByDistributor($dsResult, $data)
	{
	
	$response = [];
	$importActualModel = new \App\Models\Realizat\ImportActualModel();
	
	$distibuitorID = $data[0]->distributor_id ?? 0;
	$date = $data[0]->date ?? '2000-01';
	$includedPODs = $data[0]->includedPODs;
	$customersIDs = $data[0]->customersIDs;
	
	$response = $importActualModel->getCurvesByDistributor($distibuitorID,$date,$includedPODs,$customersIDs );
	
	return $response;
	
	}
	
	public function getPODsByDistributorCustomer($dsResult, $data)
	{
	
	$response = [];
	$importActualModel = new \App\Models\Realizat\ImportActualModel();
	
	$distibuitorID = $data[0]->distributor_id ?? 0;
	$customersIDs = $data[0]->customersIDs ?? 0;
	$date = $data[0]->date ?? 0;
	$includedCurves = $data[0]->includedCurves;
	
	$response = $importActualModel->getPODsByDistributorCustomer($distibuitorID,$customersIDs,$date,$includedCurves);
	
	return $response;
	
	}
	
	private function actual_readings_before_delete($dsResult, $data, &$columns)
	{
		$importActualModel = new \App\Models\Realizat\ImportActualModel();
		
		$importActualModel->bulkActualReadingsDestroy2([$data[0]->group_id]);

		return false;
	}

	private function actual_exportReadingsToForecast($dsResult, $data)
	{
		$importActualModel = new \App\Models\Realizat\ImportActualModel();

		$date = \DateTime::createFromFormat('Y-n-d',$data[0]->YMdate.'-01')->getTimestamp();
		$ret = $importActualModel->exportReadingsToForecast($date,$data[0]->customers, $data[0]->distributorID, $data[0]->pods);

		return "$ret valori importate in Prognoza";
	}
	
	private function systemDeleteData($dsResult, $data)
	{
		throw new \Exception('Stergerea datelor este dezactivata pentru a preserva istoricul pe termen lung.');

		$ret = "";

		$ym = explode('-',$data[0]->date);
		$month = $ym[0];
		$year = $ym[1];

		$masterDataModel = new \App\Models\MasterDataModel();
		$masterDataModel->systemDeleteData((int)$year,(int)$month,$data[0]);

		return 'Datele au fost sterse';
	}
	
	private function getCustomersWhithoutOptions($dsResult, $data)
	{
		$forecastModel = new \App\Models\Prognoza\ForecastModel();

		return $forecastModel->getCustomersWhithoutOptions();
	}
	
	private function uploadSynthetics($dsResult, $data)
	{
		$syntheticsModel = new \App\Models\Prognoza\SyntheticsModel();
		if(!empty($data[0]->customer_id))
			return $syntheticsModel->uploadValues($data[0]->synthetic_type, $data[0]->customer_id, $data[0]->YMdate, $data[0]->values);
		else return "Selectati un client";
	}

	private function uploadEstimates($dsResult, $data)
	{
		$forecastModel = new \App\Models\Prognoza\ForecastModel();
		if(!empty($data[0]->customer_id))
			return $forecastModel->uploadValues($data[0]->customer_id, $data[0]->YMdate, $data[0]->values, $data[0]->start,$data[0]->end, $data[0]->partial);
		else 
			return $forecastModel->uploadMultiValues($data[0]->modified_data, $data[0]->YMdate, $data[0]->start,$data[0]->end);;
	}

	private function uploadTemperatures($dsResult, $data)
	{
		$temperaturesModel = new \App\Models\Prognoza\TemperaturesModel();
		return $temperaturesModel->uploadValues($data[0]->YMdate, $data[0]->values);
	}
	
	private function uploadProfile($dsResult, $data)
	{
		$farModel = new \App\Models\Prognoza\FarModel();
		
		return $farModel->uploadValues($data[0]->YMdate, $data[0]->values, $data[0]->customerID, $data[0]->pod);
	}
	
	private function getForecastReport($dsResult, $data)
	{
		$forecastModel = new \App\Models\Prognoza\ForecastModel();
		return $forecastModel->getForecastReport($data[0]->year, $data[0]->month, $data[0]->estimationType);
	}
	
	
	public function readEstimates($dsResult, $data)
	{
		$forecastModel = new \App\Models\Prognoza\ForecastModel();

		if($data[0]->viewType == 'Lunar')
			return $forecastModel->readEstimates($data[0]->month, $data[0]->year, $data[0]->customers, $data[0]->consumptionType, $data[0]->intervalType);
		elseif ($data[0]->viewType == 'Analitic')
			return $forecastModel->readEstimatesHistory2($data[0]->eStart, $data[0]->eEnd, $data[0]->customers);
		else
			return $forecastModel->readAllEstimates($data[0]->eStart, $data[0]->eEnd, $data[0]->customers, $data[0]->consumptionType, $data[0]->intervalType);
	}

	public function readEstimatVsRealizat($dsResult, $data)
	{
		$forecastModel = new \App\Models\Prognoza\ForecastModel();
		$algorithm = isset($data[0]->algorithm) ? $data[0]->algorithm : 'v1';
		return $forecastModel->readAllEstimates($data[0]->eStart, $data[0]->eEnd, $data[0]->customers, $data[0]->consumptionType, $data[0]->intervalType, $algorithm);
	}

	public function readPodEstimatVsRealizat($dsResult, $data)
	{
		$forecastModel = new \App\Models\Prognoza\ForecastModel();
		$algorithm = isset($data[0]->algorithm) ? $data[0]->algorithm : 'v1';
		return $forecastModel->readAllPodEstimates($data[0]->eStart, $data[0]->eEnd, $data[0]->customers, $data[0]->intervalType, $algorithm);
	}

	public function getFarDataByPod($dsResult, $data)
	{
		$farModel = new \App\Models\Prognoza\FarModel();
		return $farModel->getFarDataMMPods($data[0]->customers, $data[0]->year, $data[0]->month);
	}
	
		
	public function getEstimateTrend($dsResult, $data)
	{
		$response = [];
		$forecastModel = new \App\Models\Prognoza\ForecastModel();
		
		$response = $forecastModel->getEstimateTrend($data[0]->eStart,$data[0]->eEnd,$data[0]->customers,$data[0]->consumptionType);
		
		return $response;
	}
	
	public function getCustomersWithSynthetics($dsResult, $data)
	{
	
		$response = [];
		$syntheticsModel = new \App\Models\Prognoza\SyntheticsModel();
		
		$YMdate = $data[0]->YMdate;
		
		$response = $syntheticsModel->getCustomersWithSynthetics($YMdate);
		
		return $response;
	
	}
	
	public function getCustomersWithConsumptionTypes($dsResult, $data)
	{
	
		$response = [];
		$forecastModel = new \App\Models\Prognoza\ForecastModel();
		
		$response = $forecastModel->getCustomersWithConsumptionTypes($data[0]->month,$data[0]->year);
		
		return $response;
	
	}
		
	public function estimate($dsResult, $data)
	{
		$response = [];
		$forecastModel = new \App\Models\Prognoza\ForecastModel();
		
		$response = $forecastModel->estimate($data[0]->start,$data[0]->end,$data[0]->customers,$data[0]->customerType);
		
		return $response;
	}
	
	public function getDailyReadingsData($dsResult, $data)
	{
		$response = [];
		$farModel = new \App\Models\Prognoza\FarModel();
		
		$response = $farModel->GetDailyReadingsData($data[0]->intervals, $data[0]->distributorID, $data[0]->customers,$data[0]->pod,$data[0]->year,$data[0]->month);
		
		return $response;
	}
	
	public function getDailyReadingsErrors($dsResult, $data)
	{
		$response = [];
		$farModel = new \App\Models\Prognoza\FarModel();
		
		$response = $farModel->getDailyReadingsErrors($data[0]->distributorID, $data[0]->customers,$data[0]->pod,$data[0]->year,$data[0]->month);
		
		return $response;
	}
	
	public function getCustomersInTPWithDailyReadings($dsResult, $data)
	{
	
		$response = [];
		$forecastModel = new \App\Models\Prognoza\FarModel();
		
		$response = $forecastModel->getCustomersInTPWithDailyReadings($data[0]->distributorID, $data[0]->year, $data[0]->month);
		
		return $response;
	
	}
	
	public function getPODsOfCustomersWithDailyReadings($dsResult, $data)
	{
	
		$response = [];
		$forecastModel = new \App\Models\Prognoza\FarModel();
		
		$response = $forecastModel->getPODsOfCustomersWithDailyReadings($data[0]->distributorID, $data[0]->year, $data[0]->month, $data[0]->customerIDs);
		
		return $response;
	
	}

	
	public function updateCustomersProfile($dsResult, $data)
	{
		$response = [];
		$farModel = new \App\Models\Prognoza\FarModel();
		
		$farModel->updateCustomersProfile($data[0]->customers,$data[0]->year,$data[0]->month);
		
		return $response;
	}
	
	public function getCustomersInTPWithConsumptionTypes($dsResult, $data)
	{
	
		$response = [];
		$forecastModel = new \App\Models\Prognoza\FarModel();
		
		$response = $forecastModel->getCustomersInTPWithConsumptionTypes($data[0]->year, $data[0]->month);
		
		return $response;
	
	}
	
	public function getPODsOfCustomersWithConsumption($dsResult, $data)
	{
	
		$response = [];
		$forecastModel = new \App\Models\Prognoza\FarModel();
		
		$response = $forecastModel->getPODsOfCustomersWithConsumption($data[0]->year, $data[0]->month, $data[0]->customerIDs);
		
		return $response;
	
	}
	
	public function getFarPODEA($dsResult, $data)
	{
		$response = [];
		$forecastModel = new \App\Models\Prognoza\FarModel();
		
		$response = $forecastModel->getFarPODEA($data[0]->year, $data[0]->month, $data[0]->pod);
		
		return $response;

	}
	
	public function getFarData($dsResult, $data)
	{
		$response = [];
		$farModel = new \App\Models\Prognoza\FarModel();
		
		$response = $farModel->getFarDataMM($data[0]->consumption_types, $data[0]->customers,$data[0]->pod,$data[0]->year,$data[0]->month);
		
		return $response;
	}
	
	public function getAccuracyLog($dsResult, $data)
	{
		$forecastModel = new \App\Models\Prognoza\ForecastModel();
		$customers = isset($data[0]->customers) ? $data[0]->customers : [];
		$algos     = isset($data[0]->algos)     ? $data[0]->algos     : ['v1','v2'];
		return $forecastModel->getAccuracyLog($customers, $data[0]->start, $data[0]->end, $algos);
	}

	public function getAccuracyDetails($dsResult, $data)
	{
		$forecastModel = new \App\Models\Prognoza\ForecastModel();
		$result = $forecastModel->getAccuracyDetails(
			(int)$data[0]->customer_id,
			$data[0]->start,
			$data[0]->end,
			$data[0]->algorithm
		);
		return ['data' => $result, 'total' => count($result)];
	}

	public function computeAccuracyLog($dsResult, $data)
	{
		$forecastModel = new \App\Models\Prognoza\ForecastModel();
		$customers = isset($data[0]->customers) ? $data[0]->customers : [];
		if(empty($customers)) return ['error' => 'No customers specified'];
		$forecastModel->computeAccuracyLog($data[0]->start, $data[0]->end, $customers);
		return ['status' => 'ok'];
	}

	public function generateCurveForPOD($dsResult, $data)
	{
		$response = [];
		$farModel = new \App\Models\Prognoza\FarModel();
		
		$response = $farModel->generateCurveForPOD($data[0]->source_customer, $data[0]->source_pod,$data[0]->dest_customer,$data[0]->dest_pod,$data[0]->start_year,$data[0]->start_month,$data[0]->stop_year,$data[0]->stop_month, $data[0]->toBeValue,$data[0]->valueType);
		
		return $response;
	}
	
	public function generateCurveForCustomer($dsResult, $data)
	{
		$response = [];
		$synthModel = new \App\Models\Prognoza\SyntheticsModel();
		
		$response = $synthModel->generateCurveForCustomer($data[0]->source_customer, $data[0]->dest_customer,$data[0]->start_year,$data[0]->start_month,$data[0]->stop_year,$data[0]->stop_month,$data[0]->dest_start_year,$data[0]->dest_start_month,$data[0]->dest_stop_year,$data[0]->dest_stop_month, $data[0]->toBeValue,$data[0]->valueType,$data[0]->synthetic_type,$data[0]->day_correlation);
		
		return $response;
	}

	public function getSynthetics($dsResult, $data)
	{
		$response = [];
		$synthModel = new \App\Models\Prognoza\SyntheticsModel();
		
		$response = $synthModel->getSynthetics($data[0]->year,$data[0]->month, $data[0]->customer_id);
		
		return $response;
	}
	
	public function getTemperatures($dsResult, $data)
	{
		$response = [];
		$temperaturesModel = new \App\Models\Prognoza\TemperaturesModel();
		
		$response['month'] = $data[0]->month;
		$response['temperatures'] = $temperaturesModel->getTemperatures($data[0]->month,$data[0]->year);
		$response['legend'] = $temperaturesModel->createTemperatureLegend();
		
		return $response;
	}
	
	public function getFilterEstimationTypes($dsResult, $data)
	{
		$response = [];
		$forecastModel = new \App\Models\Prognoza\ForecastModel();
		
		$response = $forecastModel->getFilterEstimationTypes();
		
		return $response;
		
	}

	private function uploadMinProduction($dsResult, $data)
	{
		$proCastModel = new \App\Models\Prognoza\PCModel();
		return $proCastModel->uploadMinProduction($data[0]->values);
	}
	
	private function uploadProduction($dsResult, $data)
	{
		$proCastModel = new \App\Models\Prognoza\PCModel();
		return $proCastModel->uploadProduction(false, $data[0]->regionName, $data[0]->YMdate, $data[0]->values);
	}

	private function uploadEstimatedProduction($dsResult, $data)
	{
		$proCastModel = new \App\Models\Prognoza\PCModel();
		return $proCastModel->uploadProduction(true, $data[0]->regionName, $data[0]->YMdate, $data[0]->values);
	}

	private function uploadTypicalProduction($dsResult, $data)
	{
		$proCastModel = new \App\Models\Prognoza\PCModel();
		return $proCastModel->uploadTypicalProduction(false, $data[0]->regionName, $data[0]->month, $data[0]->values);
	}
	
	private function getTranselectricaData($dsResult, $data)
	{
		$r1 = $r2 = '';
		$year = $data[0]->year;
		$month = str_pad($data[0]->month, 2, '0', STR_PAD_LEFT);
		
		$dn = date('Yj');
		
		if(date("Ym",strtotime("+1 day")) == $year.$month)
		{
			$t = strtotime("+1 day");
			//$dt1 = date("Ymj",$t);
			$y1 = date("Y",$t);
			$m1 = date("m",$t);
		}
		else
		{
			//$dt1 = date("Ymt",strtotime("$year-$month-1"));
			$y1 = $year;
			$m1 = $month;
		}
		
		if(date("Ym",strtotime("+2 day")) == $year.$month)
		{
			$t = strtotime("+2 day");
			//$dt2 = date("Ymj",$t);
			$y2 = date("Y",$t);
			$m2 = date("m",$t);				
		}
		else
		{
			//$dt2 = date("Ymt",strtotime("$year-$month-1"));
			$y2 = $year;
			$m2 = $month;
		}
		/*
		if($month == 10 && date("m")>=10 && date("j")>=30)
			$dt1 = $dt2 = $year."1032";
		*/
		$dt1 = $dt2 = "";
		$meta = @file_get_contents("https://web.transelectrica.ro/prosumatori/storage/dateList.json?time=".time());
		
		log_message("debug",$meta);
		if(!empty($meta))
		{
			$mArray = json_decode($meta,true);
			if(isset($mArray["d1"][$year][$month])) $dt1 = $year.$month.$mArray["d1"][$year][$month];
			if(isset($mArray["d2"][$year][$month])) $dt2 = $year.$month.$mArray["d2"][$year][$month];
		}
		
		$r1 = @file_get_contents("https://web.transelectrica.ro/prosumatori/storage/d1/{$dt1}.json");
		$r2 = @file_get_contents("https://web.transelectrica.ro/prosumatori/storage/d2/{$dt2}.json");
		
		log_message("error","https://web.transelectrica.ro/prosumatori/storage/d1/{$dt1}.json");
		log_message("error","https://web.transelectrica.ro/prosumatori/storage/d2/{$dt2}.json");
		
		$r1Array = [];
		if(!empty($r1))
			$r1Array = json_decode($r1,true);
		
		if(empty($r2))
			return ["errors"=>["Nu am gasit D2 publicate in <a  href=\"#\" onclick=\"window.open('https://web.transelectrica.ro/prosumatori/storage/d2/{$dt2}.json');return false;\">$dt2</a>"]];
		
		$proCastModel = new \App\Models\Prognoza\PCModel();
		return $proCastModel->saveTranselectrica($data[0]->judete, $y1, $m1,$r1Array, $y2, $m2, json_decode($r2,true));
		
	}
		
	private function viewTranselectricaData($dsResult, $data)
	{	
		$year = $data[0]->year;
		$month = $data[0]->month;
		$countyCode = $data[0]->county_code;
				
		$proCastModel = new \App\Models\Prognoza\PCModel();
		return $proCastModel->viewTranselectricaData($year,$month,$countyCode);
	}

	private function uploadManualTranselectrica($dsResult, $data)
	{
		$year = $data[0]->year;
		$month = $data[0]->month;
		$countyCode = $data[0]->county_code;
		$data = $data[0]->data;
				
		$proCastModel = new \App\Models\Prognoza\PCModel();
		return $proCastModel->uploadManualTranselectrica($year,$month,$countyCode,$data);
	}
	
	private function uploadPISEN($dsResult, $data)
	{
		$year = $data[0]->year;
		$data = $data[0]->data;
				
		$proCastModel = new \App\Models\Prognoza\PCModel();
		return $proCastModel->uploadPISEN($year,$data);
	}
	
	private function getMeteo($dsResult, $data)
	{
		$weatherModel = new \App\Models\Prognoza\TemperaturesModel();
		session_write_close();
		return $weatherModel->getMeteo($data[0]->year,$data[0]->month);
	}
	
	private function getWeatherData($dsResult, $data)
	{
		$weatherModel = new \App\Models\Prognoza\TemperaturesModel();
		return $weatherModel->getWeatherData($data[0]->year,$data[0]->month,$data[0]->layer,$data[0]->countyCode);
	}
	
	private function downloadSky($dsResult, $data)
	{
		if($data[0]->source == 'forecast.solar') 
		{
			$skyModel = new \App\Models\Prognoza\PCModel();
			return $skyModel->downloadSky($data[0]->regionName, $data[0]->year, $data[0]->month);
		}
	}
	
	private function uploadIntervals($dsResult, $data)
	{
		$intervalsModel = new \App\Models\Prognoza\IntervalsModel();
		return $intervalsModel->uploadIntervals($data[0]->customerID,$data[0]->values);
	}
	
	private function months_correlation_before_update($dsResult, $data, &$columns)
	{
		$intervalsModel = new \App\Models\Prognoza\IntervalsModel();
		$intervalsModel->updateCorrelatedMonths($data[0]->month,$data[0]->correlated_months);
		
		return false;
	}
	
	
	private function validateDailyReadings($dsResult, $data)
	{
		$idrModel = new \App\Models\Realizat\ImportDailyReadingsModel();
		return $idrModel->validateDailyReadings();
	}
	
	private function resetImportDailyReadings($dsResult, $data)
	{
		$idrModel = new \App\Models\Realizat\ImportDailyReadingsModel();
		return $idrModel->resetImportDailyReadings();
	}
	
	private function saveDailyReadings($dsResult, $data)
	{
		$idrModel = new \App\Models\Realizat\ImportDailyReadingsModel();
		return $idrModel->saveDailyReadings();
	}
	
	private function saveDailyReadingsToForecast($dsResult, $data)
	{
		$idrModel = new \App\Models\Realizat\ImportDailyReadingsModel();
		return $idrModel->saveDailyReadingsToForecast($data[0]->distributorID,$data[0]->customerIDs,$data[0]->year,$data[0]->month);
	}
}


?>