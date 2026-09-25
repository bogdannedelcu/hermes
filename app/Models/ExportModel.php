<?php

namespace App\Models;

use CodeIgniter\Model;
require_once('MasterDataTools.php');

class ExportModel extends Model
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
					SET c.customer_anre_band = b.anreBand
					WHERE c.customer_anre_band = 'Auto'";
		$this->db->query($query);
	}
	
	function get_sheet1($date)
	{
		$query = $this->db->query("select * from view_raport_anre_1 where Data = ".$this->db->escape($date));
		return $query->getResultArray();
	}
	
	function get_sheet1_summary($date)
	{
		$ymd=explode('-',$date);
		$tableName = 'actual_readings_'.(int)$ymd[1];
	
		$query = $this->db->query(
		"SELECT
		vra.ZonaLicenta,
		SUM(vra.Consum) AS TotalPV,  
		(SELECT SUM(ar.actual_ea) FROM actual_curves ac JOIN $tableName ar ON ac.distributor_id = vra.distributor_id AND ar.curve_id = ac.curve_id AND year(reading_datetime)=".$ymd[0]." AND month(reading_datetime)=".$ymd[1].") as TotalCurbe,
		'0' AS Diferente1,
		0 AS TotalPre,
		'0' AS Diferente2,
		SUM(Consum) AS Cogenerare
		FROM view_raport_anre_1 vra
		WHERE DATA=".$this->db->escape($date)." 
		GROUP BY ZonaLicenta
		ORDER BY distributor_anre_report_index");
		
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
		$extraBand = "";
		if($band == "Altii")
			$extraBand = "OR c.customer_anre_band='Auto'";
		
		$sql = "SELECT count(distinct c.customer_id) AS value  FROM contracts cs
		JOIN customers c ON cs.customer_id = c.customer_id
		WHERE year(cs.contract_date) = $year AND MONTH(cs.contract_date)=$month
		AND (c.customer_anre_band={$this->db->escape($band)} $extraBand)";

		return $this->getValue($sql);
	}
	
	function get_removed_customers_no($month,$year,$band)
	{	
		$extraBand = "";
		if($band == "Altii")
			$extraBand = "OR c.customer_anre_band='Auto'";
		
		$sql = "SELECT count(distinct c.customer_id) AS value FROM contracts cs 
				JOIN customers c ON cs.customer_id = c.customer_id
				LEFT JOIN contracts cc ON c.customer_id = cc.customer_id AND (cc.contract_stop > cs.contract_stop OR cc.contract_stop IS NULL)
				WHERE year(cs.contract_stop) = $year AND MONTH(cs.contract_stop)=$month AND (c.customer_anre_band={$this->db->escape($band)} $extraBand) AND cc.contract_id IS null";
				
		return $this->getValue($sql);
	}
	
	function get_purchases($date)
	{
		$query = $this->db->query('SELECT c.company_name, p.date_start,p.date_end, p.quantity, p.price FROM ach_purchases p 
		join ach_companies c on p.seller_id = c.company_id
		where year(p.date_start) = year('.$this->db->escape($date).') and month(p.date_start) = month('.$this->db->escape($date).') and year(p.date_end) = year('.$this->db->escape($date).') and month(p.date_end) = month('.$this->db->escape($date).') 
		order by c.company_order');
		return $query->getResultArray();
	}

	function get_sales($date)
	{
		$query = $this->db->query('SELECT c.company_name, p.date_start,p.date_end, p.quantity, p.price FROM ach_sales p 
		join ach_companies c on p.buyer_id = c.company_id
		where year(p.date_start) = year('.$this->db->escape($date).') and month(p.date_start) = month('.$this->db->escape($date).') and year(p.date_end) = year('.$this->db->escape($date).') and month(p.date_end) = month('.$this->db->escape($date).') ');
		return $query->getResultArray();
	}
	
	function get_pandl_consumptions($date)
	{
		$query = $this->db->query("SELECT date_sub(i.invoice_date, INTERVAL 1 MONTH) AS date, d.distributor_name, d.distribution_zone, c.customer_anre_band AS band, c.customer_name AS customer, sum(cast(ii.invoiced_item_quantity AS DECIMAL(20,3))) AS ea,ii.invoiced_item_unit_price AS price
									FROM  invoiced_items ii 
									JOIN service_rates sr ON ii.service_rate_id = sr.service_rate_id
									JOIN services s ON sr.service_id=s.service_id
									JOIN invoices i ON ii.invoice_id = i.invoice_id
									JOIN customers c ON i.customer_id = c.customer_id
									JOIN distributors d ON ii.distributor_id = d.distributor_id
									WHERE s.service_code='EA' AND YEAR(date_sub(i.invoice_date, INTERVAL 1 MONTH))=year(".$this->db->escape($date).") AND MONTH (date_sub(i.invoice_date, INTERVAL 1 MONTH))=month(".$this->db->escape($date).")
									GROUP BY customer_name
									ORDER BY d.distributor_anre_report_index ASC, ii.invoiced_item_unit_price ASC");
		return $query->getResultArray();							
	}
	
	function get_pandl_purchases($date)
	{
		$query = $this->db->query("SELECT * FROM ach_purchases 
									WHERE year(date_start) = year(".$this->db->escape($date).") and month(date_start)=month(".$this->db->escape($date).")
									AND year(date_end) = year(".$this->db->escape($date).") and month(date_end)=month(".$this->db->escape($date).")
									ORDER BY price asc");
		return $query->getResultArray();							
	}
	
	function get_monthly_consumption($year)
	{
		$query = $this->db->query("
		SELECT c.customer_name, 
			sum(case when MONTH(i.invoice_date)=2 and YEAR(i.invoice_date)=$year then i.invoice_calculated_ea_quantity 
			ELSE 0 END) AS `1`,
			sum(case when MONTH(i.invoice_date)=3 and YEAR(i.invoice_date)=$year then i.invoice_calculated_ea_quantity 
			ELSE 0 END) AS `2`,
			sum(case when MONTH(i.invoice_date)=4 and YEAR(i.invoice_date)=$year then i.invoice_calculated_ea_quantity 
			ELSE 0 END) AS `3`,
			sum(case when MONTH(i.invoice_date)=5 and YEAR(i.invoice_date)=$year then i.invoice_calculated_ea_quantity 
			ELSE 0 END) AS `4`,
			sum(case when MONTH(i.invoice_date)=6 and YEAR(i.invoice_date)=$year then i.invoice_calculated_ea_quantity 
			ELSE 0 END) AS `5`,
			sum(case when MONTH(i.invoice_date)=7 and YEAR(i.invoice_date)=$year then i.invoice_calculated_ea_quantity 
			ELSE 0 END) AS `6`,
			sum(case when MONTH(i.invoice_date)=8 and YEAR(i.invoice_date)=$year then i.invoice_calculated_ea_quantity 
			ELSE 0 END) AS `7`,
			sum(case when MONTH(i.invoice_date)=9 and YEAR(i.invoice_date)=$year then i.invoice_calculated_ea_quantity 
			ELSE 0 END) AS `8`,
			sum(case when MONTH(i.invoice_date)=10 and YEAR(i.invoice_date)=$year then i.invoice_calculated_ea_quantity 
			ELSE 0 END) AS `9`,
			sum(case when MONTH(i.invoice_date)=11 and YEAR(i.invoice_date)=$year then i.invoice_calculated_ea_quantity 
			ELSE 0 END) AS `10`,
			sum(case when MONTH(i.invoice_date)=12 and YEAR(i.invoice_date)=$year then i.invoice_calculated_ea_quantity 
			ELSE 0 END) AS `11`,
			sum(case when MONTH(i.invoice_date)=1 and YEAR(i.invoice_date) = ($year + 1) then i.invoice_calculated_ea_quantity 
			ELSE 0 END) AS `12`
			FROM invoices i 
			JOIN customers c ON i.customer_id = c.customer_id
			WHERE EXISTS (SELECT 1 FROM contracts co WHERE c.customer_id = co.customer_id AND (co.contract_stop is null or year(co.contract_stop)>=$year))
			GROUP BY i.customer_id
			order by c.customer_name
		");
		return $query->getResultArray();	
	}
		
}