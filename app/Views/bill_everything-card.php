<?php

$datePicker = new \Kendo\UI\DatePicker('DatePicker');
$datePicker->value($data['invoicing_date']);
$datePicker->start('month');
$datePicker->depth('month');
$datePicker->format('dd-MM-yyyy');
$datePicker->change('invoiceDateChanged');
?>

<input type="hidden" name="__RequestVerificationToken" value="<?=bin2hex(random_bytes(32));?>" />
<div class="container">
  <div class="row align-items-start">
    <div class="col-auto align-self-center">
      <?php echo $datePicker->render();?>
    </div>
    <div class="col align-self-center">
      <button class="k-button  k-button-rectangle k-rounded-md k-button-solid-primary k-button-solid-base" onclick="invoiceSelection()"><i class="fa-solid fa-file-invoice"></i>Factureaza Selectia</button>
    </div>
    <div class="col align-self-center">
      <button class="k-button  k-button-rectangle k-rounded-md k-button-solid-error k-button-solid-base text-nowrap" onclick="deleteConsumptionNotInvoiced()"><i class="fa-solid fa-trash-can"></i>Sterge Consumul Selectat</button>
    </div>
  </div>
</div>

<script>

function getInvoiceStatus()
{
	var data = {
	models: [
			 {
				sessionVar :'bill-everything-status',
				supplierID : supplierID
			 }
			]
	};
	
	var jqxhr = $.post({
						url: window.location.origin+'/api?subject=custom&type=call&action=getSessionVar',
						data: JSON.stringify(data),
						contentType: 'application/json; charset=utf-8'})
				.done(function(response) {
					if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
					
						$('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');
						return false;
					}
					else
					{
						$('#staticNotification').data('kendoNotification').hide();
						$('#staticNotification').data('kendoNotification').show(response['bill-everything-status'], 'info');

					}
				})
				.fail(function() {
					
					kendo.ui.progress($(document.body), false);
					
					//probleme de retea
					$('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
					return false;
				});
				
}

 
function invoiceSelection(item) {

	if (item === undefined)
	{
		var ids = [];
		$("#grid").data("kendoGrid").selectedKeyNames().forEach(function(e){
						if (e!="null" && $("#grid").data("kendoGrid").dataSource.get(e).error == 'Factura noua') ids.push(e);
					});
		if(ids.length < 1) return;
		
		var data = {
			models: [
					 {
						ids : ids,
						invoice_date: kendo.toString($("#DatePicker").data("kendoDatePicker").value(), "dd/MM/yyyy")
					 }
					]
		};
		
	}
	else
	{
		if ($("#grid").data("kendoGrid").dataSource.get(item).error != 'Factura noua' ) return;
		
		var data = {
			models: [
					 {
						id :item,
						invoice_date: kendo.toString($("#DatePicker").data("kendoDatePicker").value(), "dd/MM/yyyy")
					 }
					]
		};
	}

	var refreshIntervalId = setInterval(getInvoiceStatus, 1000);
	kendo.ui.progress($(document.body), true);
	
	var jqxhr = $.post({
			url: window.location.origin+"/api?subject=custom&type=call&action=invoiceAll",
			data: JSON.stringify(data),
			contentType: "application/json; charset=utf-8"})
	.done(function(response) {
		if (typeof response !== "undefined" && typeof response.errors !== "undefined") {
		
			$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
			return false;
		}
		else
		{
			if(item === undefined)
			{
				ids.forEach( function(e) {					
					dr = $('#grid').data("kendoGrid").dataSource.get(e);
					delete $('#grid').data("kendoGrid")._selectedIds[e];
					$('#grid').data("kendoGrid").dataSource.remove(dr);
				});
				//$('#grid').data("kendoGrid").dataSource.sync();
			}
			else
			{
				/*
				var deleted = false;
				$("#grid").data("kendoGrid").selectedKeyNames().forEach( function(e) {
					if (e == item)
					{
						dr = $('#grid').data("kendoGrid").dataSource.get(e);
						delete $('#grid').data("kendoGrid")._selectedIds[e];
						$('#grid').data("kendoGrid").dataSource.remove(dr);
						deleted = true;
					}
				});
				
				if(!deleted)
				{
					dr = $('#grid').data("kendoGrid").dataSource.get(item);
					$('#grid').data("kendoGrid").dataSource.remove(dr);
					//$('#grid').data("kendoGrid").dataSource.sync();
				}
				*/
				 $("#grid").data("kendoGrid").clearSelection();
				$("#grid").data("kendoGrid").dataSource.read();
			}
		}
		getInvoiceStatus();
		kendo.ui.progress($(document.body), false);
		clearInterval(refreshIntervalId);
	})
	.fail(function() {
		
		kendo.ui.progress($(document.body), false);
		clearInterval(refreshIntervalId);
		
		//probleme de retea
		$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
		return false;
	});


}

function deleteConsumptionNotInvoiced() {

	if ($("#grid").data("kendoGrid").selectedKeyNames().length < 1) return;
	
	if (!confirm("Sigur doriti sa stergeti consumurile selectate?")) return; 
	
	var data = {
		models: [
				 {
					ids :$("#grid").data("kendoGrid").selectedKeyNames()
				 }
				]
	};
	
	var jqxhr = $.post({
			url: window.location.origin+"/api?subject=view_status_tobeinvoiced&type=destroy",
			data: JSON.stringify(data),
			contentType: "application/json; charset=utf-8"})
	.done(function(response) {
		if (typeof response !== "undefined" && typeof response.errors !== "undefined") {
		
			$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
			return false;
		}
		else
		{
			$("#grid").data("kendoGrid").selectedKeyNames().forEach( function(e) {
					dr = $('#grid').data("kendoGrid").dataSource.get(e);
					delete $('#grid').data("kendoGrid")._selectedIds[e];
					$('#grid').data("kendoGrid").dataSource.remove(dr);
				});
		}
	})
	.fail(function() {
		//probleme de retea
		$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
		return false;
	});
}

function invoiceDateChanged(e) {
		$("#grid").data("kendoGrid").clearSelection();
		$('#grid').data("kendoGrid").dataSource.read();
		//window.location.href = location.protocol + "//" + location.host + location.pathname+"?date="+this.value().getFullYear() + "-"+(this.value().getMonth()+1);
	}

function readInvoiceDate(e) {
		return {invoice_date: kendo.toString($("#DatePicker").data("kendoDatePicker").value(), "dd/MM/yyyy")};
	}

</script>