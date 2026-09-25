<?php

namespace App\Models;

use CodeIgniter\Model;
require_once('MasterDataTools.php');


class BuysellModel extends Model
{
	use \MasterDataTools;
	function __construct()
	{
		$this->db = db_connect();
	}
	
	function updateCustomerBand()
	{
		$query = "UPDATE customers c 
					JOIN  (
						SELECT i.customer_id, greatest(SUM(i.invoice_calculated_ea_quantity),0) AS ea from invoices i
						WHERE date_format(i.invoice_date,'%Y-%m') = (SELECT MAX(date_format(invoice_date,'%Y-%m')) FROM invoices WHERE customer_id=i.customer_id)
						GROUP BY i.customer_id) A ON c.customer_id = A.customer_id
					JOIN anreBand b ON A.ea*12 BETWEEN b.minConsumption and b.maxConsumption
					SET c.customer_anre_band = b.anreBand";
		$this->db->query($query);
	}
	
	function get_sheet1($date)
	{
		$query = $this->db->query("select * from view_raport_anre_1 where Data = ".$this->db->escape($date));
		return $query->getResultArray();
	}
	
	function get_sheet1_summary($date)
	{
		$query = $this->db->query("SELECT 
									ZonaLicenta,
									SUM(Consum) AS TotalPV,  
									SUM(Consum) AS TotalCurbe,
									'0' AS Diferente1,
									SUM(Consum) AS TotalPre,
									'0' AS Diferente2,
									SUM(Consum) AS Cogenerare  
									FROM view_raport_anre_1
									WHERE DATA=".$this->db->escape($date).
									"GROUP BY ZonaLicenta 
									");
		$result = $query->getRowArray();
		
		return $query->getResultArray();
	}

	function get_sheet3($date)
	{
		$query = $this->db->query("select * from view_raport_anre_2 where Data = ".$this->db->escape($date));
		return $query->getResultArray();
	}
	
	function get_customers_band($banda,$data)
	{
		$query = $this->db->query("SELECT COUNT(DISTINCT Consumator) as No FROM view_raport_anre_2 WHERE Banda=".$this->db->escape($banda)." AND Data=".$this->db->escape($data));
		$result = $query->getRowArray();
		
		return $result['No'];
	}
	
	function get_all_bands()
	{
		$query = $this->db->query("select * from anreBand order by minConsumption asc");
		return $query->getResultArray();
	}
	
	function get_new_customers_no($month,$year,$band)
	{	
		$month+=1;
		if($month>12) 
		{
			$month = 1;
			$year +=1;
		}
		
		$prevYear = $year;
		$prevMonth = $month-1;
		
		if($prevMonth<1) 
		{
			$prevMonth=12;
			$prevYear = $year-1;
		}
		
		$query = $this->db->query('SELECT COUNT(distinct i.customer_id) as No FROM invoices i 
		JOIN customers c on i.customer_id = c.customer_id and c.customer_anre_band='.$this->db->escape($band).'
		WHERE month(i.invoice_date)='.$this->db->escape($month).' AND year(i.invoice_date)='.$this->db->escape($year).' AND 
		i.customer_id NOT IN (SELECT distinct customer_id FROM invoices
		WHERE month(invoice_date)='.$this->db->escape($prevMonth).' AND year(invoice_date)='.$this->db->escape($prevYear).')' 
		);
		
		$result = $query->getRowArray();
		return $result['No'];
	}
	
	function get_removed_customers_no($month,$year,$band)
	{	
		$month+=1;
		if($month>12) 
		{
			$month = 1;
			$year +=1;
		}
		
		$prevYear = $year;
		$prevMonth = $month-1;
		
		if($prevMonth<1) 
		{
			$prevMonth=12;
			$prevYear = $year-1;
		}
		
		$query = $this->db->query('SELECT COUNT(distinct i.customer_id) as No FROM invoices i 
		JOIN customers c on i.customer_id = c.customer_id and c.customer_anre_band='.$this->db->escape($band).'
		WHERE month(i.invoice_date)='.$this->db->escape($prevMonth).' AND year(i.invoice_date)='.$this->db->escape($prevYear).' AND 
		i.customer_id NOT IN (SELECT distinct customer_id FROM invoices
		WHERE month(invoice_date)='.$this->db->escape($month).' AND year(invoice_date)='.$this->db->escape($year).')' 
		);
				
		$result = $query->getRowArray();
		return $result['No'];
	}		
}