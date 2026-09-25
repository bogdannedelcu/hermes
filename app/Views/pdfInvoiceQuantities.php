
	<div class="page_break"></div>
	<div style="font-family:Helvetica;">
	<h3>Anexa cantitati energie activa</h1>
	</div>
	
  	<table id="invoice-items" style="font-size:10px;font-family:DejaVu Sans;table-layout: fixed;width:100%;">
	<tr  bgcolor='#D6D6D6'>
		<th style="width: 25%;">POD</th>
		<th style="width: 50%;">Descriere</th>
		<th>Cantitate<br>energie activa<br>[MWh]</th>
		<th>Contravaloare<br>facturata<br>energie activa<br>(taxe incluse) [LEI]</th>
	</tr>
	<?php
	$eaTotal=0;$invoicedTotal=0;	
	foreach ($eaQuantities as &$item) {
		
		$eaTotal+=(float)$item['invoiced_item_quantity'];	$invoicedTotal+=(float)$item['invoiced_value'];
		
		//formateaza cantitati la 3 minim zecimale
		$up_nod = strrchr($item['invoiced_item_quantity'], ".") === false ? 0 : strlen(strrchr($item['invoiced_item_quantity'], "."))-1;
		if ($up_nod < 3) $item['invoiced_item_quantity'] = number_format($item['invoiced_item_quantity'], 3, '.', ',');
		
		echo '<tr><td style="text-align: left;">'.$item['invoiced_pod_no'].'</td><td>'.$item['invoiced_item_name'].'</td><td style="text-align: center;">'.$item['invoiced_item_quantity'].'</td><td style="text-align: right;">'.number_format($item['invoiced_value'], 2, '.', ',').'</td></tr>';
	}
	?>
	
	<tr>
	<td colspan="2"><b>Total</b>
	<td style="text-align: center;"><b><?=number_format($eaTotal, 3, '.', ',')?>
	<td style="text-align: right;"><b><?=number_format($invoicedTotal, 2, '.', ',')?>		
	</tr>
	
	</table>
	
	<?php if (count($reQuantities)>5) echo '<div class="page_break"></div>';?>
	
	<div style="font-family:Helvetica;">
	<h3>Anexa cantitati energie reactiva</h1>
	</div>
	
	<table id="invoice-items" style="font-size:10px;font-family:DejaVu Sans;table-layout: fixed;width:100%;">
	<tr bgcolor='#D6D6D6'>
		<th style="width: 25%;">&nbsp;POD&nbsp;</th>
		<th style="text-align: center;">ERI<br>[kVArh]</th>
		<th style="text-align: center;">ERC<br>[kVArh]</th>
		<th style="text-align: center;">ERI&nbsp;3X<br>[kVArh]</th>
		<th style="text-align: center;">ERC&nbsp;3X<br>[kVArh]</th>
		<th style="text-align: center;width: 15%;">Cantitate totala<br>energie reactiva<br>[kVArh]</th>
		<th style="text-align: center;width: 15%;">Contravaloare facturata<br>energie reactiva<br>(taxe incluse)<br>[LEI]</th>
	</tr>
	<?php
	$eriTotal=0;$ercTotal=0;$eri_x3Total=0;$erc_x3Total=0;$qTotal=0;$invoicedTotal=0;
	foreach ($reQuantities as &$item) {
		echo '<tr><td style="text-align: left;">'.$item['invoiced_pod_no'].'</td><td style="text-align: center;">'.number_format($item['ERI'], 3, '.', ',').'</td><td style="text-align: center;">'.number_format($item['ERC'], 3, '.', ',').'</td><td style="text-align: center;">'.number_format($item['ERI_X3'], 3, '.', ',').'</td><td style="text-align: center;">'.number_format($item['ERC_X3'], 3, '.', ',').'</td><td style="text-align: center;">'.number_format($item['invoiced_quantity'], 3, '.', ',').'</td><td style="text-align: right;">'.number_format($item['invoiced_value'], 2, '.', ',').'</td></tr>';
		$eriTotal+=(float)$item['ERI'];$ercTotal+=(float)$item['ERC'];$eri_x3Total+=(float)$item['ERI_X3'];$erc_x3Total+=(float)$item['ERC_X3'];$qTotal+=(float)$item['invoiced_quantity'];$invoicedTotal+=(float)$item['invoiced_value'];
	}
	?>

	<tr>
		<td><b>Total</b>
		<td style="text-align: center;"><b><?=number_format($eriTotal, 3, '.', ',')?>
		<td style="text-align: center;"><b><?=number_format($ercTotal, 3, '.', ',')?>		
		<td style="text-align: center;"><b><?=number_format($eri_x3Total, 3, '.', ',')?>
		<td style="text-align: center;"><b><?=number_format($erc_x3Total, 3, '.', ',')?>
		<td style="text-align: center;"><b><?=number_format($qTotal, 3, '.', ',')?>
		<td style="text-align: right;"><b><?=number_format($invoicedTotal, 2, '.', ',')?>
	</tr>
	</table>
