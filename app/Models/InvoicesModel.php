<?php

namespace App\Models;

use CodeIgniter\Model;
require_once('MasterDataTools.php');

class InvoicesModel extends Model
{
	use \MasterDataTools;
	protected $table = 'invoices';
	
	function __construct()
	{
		$this->db = db_connect();
	}
	
	function get_data($sql)
	{
		$query = $this->db->query($sql);		
		$result = $query->getRowArray();
		
		return $result;
	}
	
	function get_all_data($sql)
	{
		$query = $this->db->query($sql . ' where i.invoice_id = '.$db->escape($this->invoiceId));		
		$result = $query->getResultArray();
		
		return $result;
	}
	
	function get_comments($invoiceId)
	{
		$query = $this->db->query('SELECT comment FROM comments where invoice_id = '.$this->db->escape($invoiceId).' limit 1');
		$result = $query->getRowArray();
		
		if(!$result) return '';
		
		return $result['comment'];
	}
	
	function save_comments($invoiceId,$comment)
	{
		$query = $this->db->query('insert into comments (invoice_id, comment) values ('.$this->db->escape($invoiceId).','.$this->db->escape($comment).') 
									on duplicate key update comment = '.$this->db->escape($comment));

		return $this->db->affectedRows();
	}
	
	function get_invoicedate_min_max_years()
	{
		$query = $this->db->query("select date_format(min(invoice_date),'%Y') AS minY,date_format(max(invoice_date),'%Y') as maxY FROM invoices ");
		$result = $query->getRowArray();
		if(!$result) return ['minY'=>2020, 'maxY'=>2020];

		return $result;
	}
	
	function get_duedate_min_max_years()
	{
		$query = $this->db->query("select date_format(min(invoice_due_date),'%Y') AS minY,date_format(max(invoice_due_date),'%Y') as maxY FROM invoices ");
		$result = $query->getRowArray();
		if(!$result) return ['minY'=>2020, 'maxY'=>2020];
		return $result;
	}
	
	function get_consumption_invoiced_status($consumptionId)
	{
		$query = $this->db->query('select invoice_id is not null as invoiced from consumptions where consumption_id = '.$this->db->escape($consumptionId));
		$result = $query->getRowArray();
		
		
		return $result['invoiced'];
	}
	
	function get_customer_due_days($customerId)
	{
		$query = $this->db->query('select customer_invoice_due_days from customers where customer_id = '.$this->db->escape($customerId));
		$result = $query->getRowArray();
		
		if (empty($result)) return 30;
		
		return $result['customer_invoice_due_days'];
	}

	function get_supplier_services($supplierId)
	{
		$query = $this->db->query('select service_id, service_name from services where supplier_id = '.$this->db->escape($supplierId). 'order by service_print_order asc');
		$result = $query->getResultArray();
		
		return $result;
	}

	function get_customer_zones($customerId)
	{
		$query = $this->db->query('select z.zone_id,z.zone_name from zones z where z.customer_id = '.$this->db->escape($customerId));
		$result = $query->getResultArray();
		
		return $result;
	}

	function get_customer_contracts($customerId)
	{
		$query = $this->db->query("select c.contract_id, concat(c.contract_number,'/',c.contract_date) as contract_number from contracts c where c.customer_id = ".$this->db->escape($customerId));
		$result = $query->getResultArray();
		
		return $result;
	}
	
	function get_supplier_invoiceNo($supplierId)
	{
		$query = $this->db->query('SELECT getInvoiceNo('.$this->db->escape($supplierId).') as invoiceNo');
		$result = $query->getRowArray();
		
		return $result['invoiceNo'];
	}

	function get_invoice_status($invoiceId)
	{
		$result = $this->get_data('SELECT invoice_status FROM invoices where invoice_id = '.$this->db->escape($invoiceId));

		return $result['invoice_status'];
	}

	function get_invoice_no($invoiceId)
	{
		$result = $this->get_data('SELECT invoice_no FROM invoices where invoice_id = '.$this->db->escape($invoiceId));

		return $result['invoice_no'];
	}
	
	function checkStornoInvoice($customerId,$zoneId,$contractId,$stornoNo)
	{
		//verifica daca datele de facturare sunt corecte si factura nu este o stornare + nu a fost stornata deja
		$result = $this->get_data("select (select count(invoice_id) from invoices where customer_id = ".$customerId." and contract_id = ".$contractId." and zone_id = ".$zoneId." and invoice_no = ".$this->db->escape($stornoNo)." and storno_no is NULL) && (select count(invoice_id)<1 from invoices where storno_no=".$this->db->escape($stornoNo) .') as poate_fi_stornata');
		
		return ($result['poate_fi_stornata']>0);
	}
	
	function populateInvoice($invoiceId,$customerId,$zoneId,$contractId,$stornoNo)
	{
		if(empty($stornoNo))
		{
			$this->db->query("call populateInvoicedItems(".$invoiceId.",".$customerId.",".$zoneId.",".$contractId.")");
		}
		else
		{
			log_message('info',$stornoNo);
			$this->db->query("call populateStornoInvoice(".$invoiceId.",".$this->db->escape($stornoNo).")");
		}
		
		$this->db->query("call updateInvoiceTotals(".$invoiceId.")");
	}

	
	function emiteFactura($invoiceId)
	{
		$this->db->query("call releaseInvoice( ".$invoiceId.")");
	}
	
	function bulkRelease($invoiceIds)
	{
		$result = [];
		$query = $this->db->query("select invoice_id from invoices where invoice_status = 'In pregatire' and invoice_id in ($invoiceIds)");
		foreach($query->getResultArray() as $r)
		{
			$this->emiteFactura($r['invoice_id']);
			$result[$r['invoice_id']] = $this->get_invoice_no($r['invoice_id']);
		}
		return $result;
	}

	public function bulkDestroy($invoiceIds)
	{
		$this->db->query("delete from invoices where invoice_status = 'In pregatire' and invoice_id in ($invoiceIds)");
				
		return $this->db->affectedRows();
	}

	function get_customer_data()
	{
		return $this->get_data('SELECT c.* FROM invoices i left join customers c ON i.customer_id = c.customer_id');
	}

	function get_home_report($homeReport)
	{
		$query = $this->db->query('SELECT * FROM '.$homeReport);
		$result = $query->getResultArray();
		
		return $result;
	}
	
	function deleteConsumptionNotInvoiced($supplierId,$customerId,$contractId,$zoneId)
	{
		$this->db->query('delete c FROM consumptions c
		left JOIN suppliers s ON c.supplier_name = s.supplier_name
		left JOIN distributors d ON c.distributor_name = d.distributor_name
		left JOIN pods p ON c.pod = p.pod_no
		WHERE invoice_id IS NULL 
		AND p.customer_id='.$customerId.' 
		AND p.zone_id='.$zoneId.'
		AND s.supplier_id = '.$supplierId);
	}
	
	function billEverything($supplierId,$customerId,$contractId,$zoneId,$invoiceDate)
	{
		
		$this->db->transStart();
			
		$query = $this->db->query("INSERT INTO invoices(supplier_id, invoice_no, invoice_date, invoice_due_date, customer_id, contract_id,zone_id,invoice_status) VALUES(".$supplierId.",'AUTO', '".$invoiceDate."',DATE_ADD('".$invoiceDate."', INTERVAL (SELECT customer_invoice_due_days FROM customers WHERE customer_id=".$customerId.") DAY),".$customerId.",".$contractId.",".$zoneId.",'In pregatire')");
		$invoiceId = $this->db->insertID();
				
		$query = $this->db->query("SET @pInvoiceDate = '".$invoiceDate."'");

		//$this->db->query("call populateInvoicedItems(".$invoiceId.",".$customerId.",".$zoneId.",".$contractId.")");		
		$this->db->query("insert into invoiced_items (	
		`invoice_id`,
		`service_rate_id`,
		`distributor_id`,
		`invoiced_item_no`,
		`invoiced_pod_no` ,
		`invoiced_item_name`, 
		`invoiced_item_measurement_unit`,
		`invoiced_item_quantity` ,
		`invoiced_item_unit_price`, 
		`invoiced_item_value` ,
		`invoiced_item_vat`) (SELECT ".$invoiceId.",vis.service_rate_id,vis.distributor_id,row_number() over ( order by `vis`.`No`), vis.pod_no,coalesce(vis.service_name,''),vis.measurement_unit,vis.total_consumption,vis.unit_price,vis.Value,vis.VAT FROM view_to_be_invoiced_incl_contracts vis where vis.customer_id=".$customerId." AND vis.zone_id=".$zoneId." and contract_id=".$contractId." ORDER BY vis.`No`)");
				
		$this->db->query("update consumptions c join invoiced_items ii ON c.pod = ii.invoiced_pod_no SET c.invoice_id = ".$invoiceId." WHERE ii.invoice_id=".$invoiceId." AND c.invoice_id IS NULL");	
		
		$query = $this->db->query("SET @pInvoiceDate = CURRENT_DATE()"); 
		
		$this->db->transComplete();	
				
		if ($this->db->transStatus() !== FALSE)
		{
			$this->db->query("call updateInvoiceTotals(".$invoiceId.")");
			log_message('info',print_r($invoiceId, false));
		}
		else
			log_message('error',print_r($invoiceId, false));
		
		/*	
		{
			$err = $this->db->error();			
			log_message('info',print_r($err, TRUE));
		}
		sleep(2);
		$this->db->close();
		$this->db->initialize(); */
		//log_message('info',print_r($invoiceId, TRUE));
		//$result = $query->getRowArray();
		
		//return $result['numFacturi'];
	}
	
	function billEverything2($idList, $invoiceDate)
	{
		
		$benchmark = \Config\Services::timer();
		
		$_SESSION['bill-everything-status'] = 'Pregatesc datele...';
		session_write_close();
		
 		$benchmark->start('prep data');
		$this->db->query('DROP TEMPORARY TABLE IF EXISTS temp_invoiced_items');
		$this->db->query("SET @pInvoiceDate = '".$invoiceDate."'");
		$this->db->query('CREATE TEMPORARY TABLE temp_invoiced_items SELECT * FROM view_to_be_invoiced_incl_contracts');
		//$this->db->query('truncate prep_invoiced_items');
		//$this->db->query('insert into prep_invoiced_items (SELECT * FROM view_to_be_invoiced_incl_contracts)');
		$benchmark->stop('prep data');
		
		$fg = 0;$total = count($idList);
		foreach($idList as $id)
		{
			$row = explode('-',$id,5);
			if ($row !== false && count($row)==5)
			{
				$supplierId = $row[1];
				$customerId = $row[2];
				$contractId = $row[3];
				$zoneId = $row[4];
			}
			else continue;
				
			$this->db->transStart();
							
			$benchmark->start('create invoice');
			$query = $this->db->query("INSERT INTO invoices(supplier_id, invoice_no, invoice_date, invoice_due_date, customer_id, contract_id,zone_id,invoice_status) VALUES(".$supplierId.",'AUTO', '".$invoiceDate."',DATE_ADD('".$invoiceDate."', INTERVAL (SELECT customer_invoice_due_days FROM customers WHERE customer_id=".$customerId.") DAY),".$customerId.",".$contractId.",".$zoneId.",'In pregatire')");
			$invoiceId = $this->db->insertID();
			$benchmark->stop('create invoice');
			
			$benchmark->start('populate invoice');
			
			$this->db->query("insert into invoiced_items (	
			`invoice_id`,
			`service_rate_id`,
			`distributor_id`,
			`invoiced_item_no`,
			`invoiced_pod_no` ,
			`invoiced_item_name`, 
			`invoiced_item_measurement_unit`,
			`invoiced_item_quantity` ,
			`invoiced_item_unit_price`, 
			`invoiced_item_value` ,
			`invoiced_item_vat`) (SELECT ".$invoiceId.",vis.service_rate_id,vis.distributor_id,row_number() over ( order by `vis`.`No`), vis.pod_no,coalesce(vis.service_name,''),vis.measurement_unit,vis.total_consumption,vis.unit_price,vis.Value,vis.VAT FROM temp_invoiced_items vis where vis.customer_id=".$customerId." AND vis.zone_id=".$zoneId." and contract_id=".$contractId." ORDER BY vis.`No`)");
			$benchmark->stop('populate invoice');
			
			$benchmark->start('update consumptions');
			$this->db->query("update consumptions c join invoiced_items ii ON c.pod = ii.invoiced_pod_no SET c.invoice_id = ".$invoiceId." WHERE ii.invoice_id=".$invoiceId." AND c.invoice_id IS NULL");	
			
			$benchmark->stop('update consumptions');
			$this->db->transComplete();	
			$query = $this->db->query("SET @pInvoiceDate = CURRENT_DATE()"); 
			
			$benchmark->start('update totals');		
			if ($this->db->transStatus() !== FALSE)
				$this->db->query("call updateInvoiceTotals(".$invoiceId.")");
			else
				log_message('error','Eroare generare factura id:'. $invoiceId);
			
			$benchmark->stop('update totals');		
			session_start();
			$_SESSION['bill-everything-status'] = 'Facturat '.number_format($fg++/$total*100,2).'%';
			session_write_close();
			
			//log_message('error','Elapsed time prep data:'.timer()->getElapsedTime('prep data').' create invoice:'.timer()->getElapsedTime('create invoice').' populate invoice:'.timer()->getElapsedTime('populate invoice').' update consumptions:'.timer()->getElapsedTime('update consumptions').' update totals:'.timer()->getElapsedTime('update totals'));
		}
		session_start();
		$_SESSION['bill-everything-status'] = 'Finalizat, '.$fg.' facturi generate.';
		session_write_close();
	}
		
}