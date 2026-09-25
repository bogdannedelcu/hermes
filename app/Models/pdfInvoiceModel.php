<?php

namespace App\Models;

use CodeIgniter\Model;

class PdfInvoiceModel extends Model
{
	var $invoiceItems;
	var $quantities_active;
	var $totals_active;
	var $quantities_reactive;
	var $totals_reactive;
	protected $invoiceId;
	
	function __construct($invoiceId)
	{
		$this->invoiceId = $invoiceId;
		$this->db = \Config\Database::connect();
	}
	
	function get_data($sql)
	{
		$db = \Config\Database::connect();
		$query = $db->query($sql . ' where i.invoice_id = '.$db->escape($this->invoiceId));		
		$result = $query->getRowArray();
		
		return $result;
	}
	
	function get_all_data($sql)
	{
		$db = \Config\Database::connect();
		$query = $db->query($sql . ' where i.invoice_id = '.$db->escape($this->invoiceId).' order by i.invoiced_item_no asc');		
		$result = $query->getResultArray();
		
		return $result;
	}
	
	function get_invoice_data()
	{
		$db = \Config\Database::connect();
		$query = $db->query('call getInvoiceData('.$db->escape($this->invoiceId).')');				
		$result = $query->getRowArray();
		
		return $result;
	}
	
	function get_customer_data()
	{
		return $this->get_data('SELECT c.* FROM invoices i left join customers c ON i.customer_id = c.customer_id');
	}
	
	function get_supplier_data()
	{
		return $this->get_data('SELECT s.* FROM invoices i left join suppliers s ON i.supplier_id = s.supplier_id');
	}
	
	function get_invoiced_items()
	{
		return $this->get_all_data('SELECT * FROM invoiced_items i');
	}
	
	function get_ea_quantities()
	{
		$db = \Config\Database::connect();
		$query = $db->query('call getInvoiceEAQuantities('.$this->invoiceId.')');				
		$result = $query->getResultArray();
		
		return $result;
	}
	
	function get_re_quantities()
	{
		$db = \Config\Database::connect();
		$query = $db->query('call getInvoiceREQuantities('.$this->invoiceId.')');				
		$result = $query->getResultArray();
		
		return $result;
	}
	
	private function get_var($settingVar)
	{
		$db = \Config\Database::connect();
		$query = $db->query("select setting_value from settings where setting_variable='".$settingVar."'");				
		$result = $query->getRowArray();
		
		return $result['setting_value'];		
	}
	
	function get_distributor_data()
	{
		$db = \Config\Database::connect();
		$query = $db->query('SELECT d.* FROM distributors d LEFT JOIN invoiced_items i ON i.distributor_id = d.distributor_id WHERE i.invoice_id='.$db->escape($this->invoiceId).' AND i.distributor_id is not null limit 1');
		$result = $query->getRowArray();
		
		return $result;
	}
	
	function get_invoice_annex()
	{
		return $this->get_var('annex');
	}
	
	function get_invoice_footer()
	{
		return $this->get_var('footer');
	}
	
	function get_invoice_vat()
	{
		return $this->get_var('vat');
	}
	
	function get_pod_devices()
	{
		$db = \Config\Database::connect();
		$query = $db->query('SELECT pod,device_serial_number FROM consumptions where invoice_id = '.$db->escape($this->invoiceId)." and device_serial_number<>'' GROUP BY device_serial_number ORDER BY pod");		
		$dbArray = $query->getResultArray();
	
		$ret = array();
		foreach($dbArray as $dbA)
		{
			if(empty($ret[$dbA['pod']])) 
				$ret[$dbA['pod']] = $dbA['device_serial_number'];
			else
				$ret[$dbA['pod']] .= ', '.$dbA['device_serial_number'];
		}
		
		return $ret;
	}
	
	function get_invoice_total_value()
	{/*
		$db = \Config\Database::connect();
		$builder = $db->table('invoiced_items');
		$builder->selectSum('invoiced_item_value','invoiced_item_vat');
		$builder->where('invoice_id', $this->invoiceId);
		$query = $builder->get();
		
		$db->query('SELECT sum(i.invoiced_item_value) as total_value,sum(i.invoiced_item_vat) as total_vat ,sum(i.invoiced_item_value)+sum(i.invoiced_item_vat) as total_amount FROM invoiced_items i where i.invoice_id = '.$db->escape($this->invoiceId));		
		$result = $query->getRowArray();
		

		return $result;
	*/
	}
	
	function getInvoiceFileNames($invoiceIds)
	{
		$result = [];
		
		$query = $this->db->query("SELECT concat('".WRITEPATH."invoices/',i.invoice_no,' - ',c.customer_name,'.pdf') AS fileName FROM invoices i 
							LEFT JOIN customers c ON i.customer_id=c.customer_id
							WHERE i.invoice_status='Emisa' and i.invoice_id in ($invoiceIds)");
		foreach($query->getResultArray() as $r)
		{
			array_push($result, $r['fileName']);
		}
		
		return $result;
	}
	
	
}