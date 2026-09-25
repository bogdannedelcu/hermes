<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Factura No.<?=$invoiceData['invoice_no']?></title>
  <link rel="stylesheet" type="text/css" href="<?php echo base_url().'/css/pdfInvoice.css';?>">
</head>

<body>
  <div style="font-family:Courier;">

	<table id="header" style="font-family:Courier;">
	<tr>
	<td style="padding-right:50px;">
		<?php	
		$base64='';
		$path = base_url().'/logo/'.rawurlencode($supplierData['supplier_logo']);
		$file_headers = @get_headers($path);
		//if(file_exists($path))
		//if (stripos($file_headers[0],"404 Not Found") >0  || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7],"404 Not Found") > 0))
		{
			$arrContextOptions=array(
				"ssl"=>array(
					"verify_peer"=>false,
					"verify_peer_name"=>false,
				),
			);  

			$type = pathinfo($path, PATHINFO_EXTENSION);
			$data = file_get_contents($path,false, stream_context_create($arrContextOptions));
			$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
		}
		?>
		<img src="<?php echo $base64?>" width="150" height="150"/>
	<td>
	<td style="padding-left:198px;padding-right:20px;padding-bottom:20px;">
		<table id="summary" style="margin-top:30px;border: 1px solid black;">
		<?php 
			if ($invoiceData['invoice_status'] != 'In pregatire')
				echo '<tr><td style="padding-right:5px;"><b>Factura fiscala</b></td><td>'.$invoiceData['invoice_no'].'</td></tr>';
			else
				echo '<tr><td style="padding-right:5px; color:#FF0000"><del><b>Factura fiscala</b></del></td><td style="color:#FF0000"><del>'.$invoiceData['invoice_no'].'</del></td></tr>';
		?>
		<tr><td style="padding-right:20px;"><b>Data</b></td><td><?=$invoiceData['invoice_date']?></td></tr>
		<tr><td><b>Total</b></td><td><?=number_format($invoiceData['total_amount'], 2, '.', ',').' lei'?></td></tr>
		<tr><td style="padding-right:20px;"><b>Data Scadentei</b></td><td><?=$invoiceData['invoice_due_date']?></td></tr>
		<tr><td style="padding-right:5px;"><b>Cota TVA</b></td><td><?=$vat?>%</td></tr>
	</table>
	</td>
	<tr>
	</table>

	
	<table id="companies" style="font-size:12px;font-family:DejaVu Sans;table-layout: fixed;width:100%;">
	<tr>
	<td style="width: 55%;">
	<b>Furnizor:</b><?=$supplierData['supplier_name']?><br>
	<b>Nr.Reg.Com:</b><?=$supplierData['supplier_registration_number']?><br>
	<b>CUI:</b><?=$supplierData['supplier_vat_code']?><br>
	<b>Licenta:</b><?=$supplierData['supplier_license']?><br>
	<b>Localitate:</b><?=$supplierData['supplier_city']?><br>
	<b>Adresa:</b><?=$supplierData['supplier_address']?><br>
	
	<?php if(!empty($supplierData['supplier_office'])): ?>
		<b>Punct de lucru:</b><?=$supplierData['supplier_office']?><br>
	<?php endif;?>
	
	<b>Banca:</b><?=$supplierData['supplier_bank']?><br>
	<b>Cont:</b><?=$supplierData['supplier_bank_account']?><br>
	<?php if(!empty($supplierData['supplier_bank2'])): ?>
			<b>Banca:</b><?=$supplierData['supplier_bank2']?><br>
			<b>Cont:</b><?=$supplierData['supplier_bank_account2']?><br>			
	<?php endif;?>

	<b>Telefon:</b><?=$supplierData['supplier_phone']?><br>
	<?php if(!empty($supplierData['supplier_fax'])): ?>	
	<b>Fax:</b><?=$supplierData['supplier_fax']?><br>
	<?php endif;?>
		
	<b>Email:</b><?=$supplierData['supplier_email']?><br>
	<b>Program:</b><?=$supplierData['supplier_program']?>
	<td>
	<b>Cumparator:</b><?=$customerData['customer_name']?><br>
	<b>Nr.Reg.Com:</b><?=$customerData['customer_registration_number']?><br>
	<b>CUI:</b><?=$customerData['customer_vat_code']?><br>
	<b>Localitate:</b><?=$customerData['customer_city']?><br>
	<b>Adresa:</b><?=$customerData['customer_address']?><br>
	<b>Banca:</b><?=$customerData['customer_bank']?><br>
	<b>Cont:</b><?=$customerData['customer_bank_account']?><br>
	<b>Contract:</b><?=$invoiceData['contract']?><br>
	</tr>
    </table>

	<table id="invoice-items" style="font-size:10px;font-family:DejaVu Sans;table-layout: fixed;width:100%;">
	<tr bgcolor='#D6D6D6'>
		<th>Nr.</th>
		<th style="width: 50%;">Denumire produse sau servicii</th>
		<th>U.M.</th>
		<th>Cantitate</th>
		<th>Pret Unitar</th>
		<th>Valoare</th>
		<th>Valoare TVA</th>
	</tr>
	<?php
	$last_pod = '';
	foreach ($invoicedItems as &$item) {
		
		//afiseaza titlu pod, device_sn
		if($item['invoiced_pod_no'] != $last_pod)
		{
			if(empty($pod_devices[$item['invoiced_pod_no']]))
				echo '<tr><td style="text-align: left;" colspan="7"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;POD:'.$item['invoiced_pod_no'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b></td></tr>';
			else
				echo '<tr><td style="text-align: left;" colspan="7"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;POD:'.$item['invoiced_pod_no'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Cod contor: '.$pod_devices[$item['invoiced_pod_no']].'</b></td></tr>';
			$last_pod = $item['invoiced_pod_no'];
		}
		
		//formateaza unit price la 2 minim zecimale
		$up_nod = strrchr($item['invoiced_item_unit_price'], ".") === false ? 0 : strlen(strrchr($item['invoiced_item_unit_price'], "."))-1;
		if ($up_nod < 2) $item['invoiced_item_unit_price'] = number_format($item['invoiced_item_unit_price'], 2, '.', ',');
		
		//formateaza cantitati la 3 minim zecimale
		//if($item['invoiced_item_measurement_unit'] == 'MWh')
		{
			$up_nod = strrchr($item['invoiced_item_quantity'], ".") === false ? 0 : strlen(strrchr($item['invoiced_item_quantity'], "."))-1;
			if ($up_nod < 3) $item['invoiced_item_quantity'] = number_format($item['invoiced_item_quantity'], 3, '.', ',');
		}
		
		echo '<tr><td style="text-align: center;">'.$item['invoiced_item_no'].'</td><td>'.$item['invoiced_item_name'].'</td><td style="text-align: center;">'.$item['invoiced_item_measurement_unit'].'</td><td style="text-align: center;">'.$item['invoiced_item_quantity'].'</td><td style="text-align: right;">'.$item['invoiced_item_unit_price'].'</td><td style="text-align: right;">'.number_format($item['invoiced_item_value'], 2, '.', ',').'</td><td style="text-align: right;">'.number_format($item['invoiced_item_vat'], 2, '.', ',').'</td></tr>';
	}
	?>
	<tr>
	<td colspan="5">Total</td>
	<td style="text-align: right;"><?= number_format($invoiceData['total_value'], 2, '.', ',')?></td>
	<td style="text-align: right;"><?= number_format($invoiceData['total_vat'], 2, '.', ',')?></td>
	</tr>
	<tr>
	<td colspan="5"><b>Total Factura</b></td>
	<td colspan="2" style="text-align: center;"><b><?= number_format($invoiceData['total_amount'], 2, '.', ',').' lei'?></b></td>
	</tr>
	</table>
	
	<div style="font-size:10px;font-family:DejaVu Sans;">
		<?=$invoice_footer;?>
	</div>
	
  </div>
	
	
	<?php if (count($eaQuantities) > 2 || count($reQuantities) > 2) require_once('pdfInvoiceQuantities.php') ?>
		
	<?php require_once('pdfInvoiceAnnex.php') ?>

</body>

</html>
