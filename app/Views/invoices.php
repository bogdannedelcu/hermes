<?php 
require_once('header.php'); 
require_once('dialogs.php');

echo $data['output'];
invoiceDialog();

require_once('footer.php');
?>
<script src="/telerik/js/jszip.min.js"></script>
<style>
body {
	overflow:hidden;
}
</style>

<script>
var newInvoice = false;

$( document ).ready(function() {
	if (window.location.href.includes('#Create'))
		newInvoice = true;

	$("#newInvoice").bind("click",function(){InvoiceDialog();});
})

function viewInvoicePDF(id)
{
	window.open(window.location.origin + "/index.php/PdfInvoice?invoiceId=" + id,'_blank');
}

function releaseInvoice(id)
{
	var data = {
			models: [
					 {
						invoiceId :id
					 }
					]
			};
							
	var jqxhr = $.post({
						url: window.location.origin+'/api?subject=custom&type=call&action=releaseInvoice',
						data: JSON.stringify(data),
						contentType: 'application/json; charset=utf-8'})
				.done(function(response) {
					if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
					
						$('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');
						return false;
					}
					else
					{
						if(response.invoice_no !== 'undefined')
						{
							let d = $("#grid").data("kendoGrid").dataSource._data;
							for(i=0;i<d.length;i++)
							{
								if (d[i].invoice_id == id)
								{
									
									$("[data-uid="+d[i].uid+"] > td.k-command-cell > button.k-grid-editD").hide();
									$("[data-uid="+d[i].uid+"] > td.k-command-cell > button.k-grid-delete").hide();
									$("[data-uid="+d[i].uid+"] > td.k-command-cell > button.k-grid-release").hide();
									$("[data-uid="+d[i].uid+"] > td.k-command-cell > button.k-grid-invoice").addClass('ms-0');
									$("[data-uid="+d[i].uid+"] > td")[7].innerText= response.invoice_no;
									$("[data-uid="+d[i].uid+"] > td")[13].innerText= 'Emisa';
									d[i].invoice_no = response.invoice_no;
									d[i].invoice_status = 'Emisa';
									$('#staticNotification').data('kendoNotification').show('Factura '+response.invoice_no+' a fost emisa.', 'info');
									break;
								}
							}
							
							//$("#grid").data("kendoGrid").dataSource.read();
							getBadges_active();
						}
							
						
					}
				})
				.fail(function() {				
					//probleme de retea
					$('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
					return false;
				});
}

  function onChange(e) {
			let selection = this.selectedKeyNames();
			
			if(selection.length > 0)
			{
				let d = this.dataSource._data;
				let ffound = false;
				let i = 0;
				while(i++<selection.length && !ffound)
					for(j=0;j<d.length;j++)
						if(d[j].invoice_id == selection[i-1] && d[j].invoice_status=='In pregatire')
						{
							ffound = true;
							break;
						}
				
				$(".bulkPdfDownload").removeClass('k-state-disabled');
				if(ffound)
				{
					$(".bulkDestroy").removeClass('k-state-disabled');
					$(".bulkRelease").removeClass('k-state-disabled');
				}
				else
				{
					$(".bulkDestroy").addClass('k-state-disabled');
					$(".bulkRelease").addClass('k-state-disabled');
				}
			}else
			{
				$(".bulkDestroy").addClass('k-state-disabled');
				$(".bulkRelease").addClass('k-state-disabled');
				$(".bulkPdfDownload").addClass('k-state-disabled');
				$("#grid").data("kendoGrid")._selectedIds=[];
			}
			
			
			
            console.log("The selected product ids are: [" + this.selectedKeyNames().join(", ") + "]");
        };
		
  function bulkPDFDownload() {
		
		window.open(window.location.origin + "/index.php/PdfInvoice/downloadPDFs?invoiceIds=" + $("#grid").data("kendoGrid").selectedKeyNames().join(", "),'_blank');
	}

  function bulkRelease() {

	grid = $("#grid").data("kendoGrid");
	
	if (confirm("Sigur doriti sa emiteti " + grid.selectedKeyNames().length + " facturi? \n\n Doar facturile in pregatire vor fi emise.") != true) {
	  return;
	}
	
	kendo.ui.progress($(document.body), true);
	var facturi='';
	
	var data = {
			models: [
					 {
						invoiceIds :grid.selectedKeyNames().join(", ")
					 }
					]
			};
							
	var jqxhr = $.post({
						url: window.location.origin+'/api?subject=custom&type=call&action=bulkInvoiceRelease',
						data: JSON.stringify(data),
						contentType: 'application/json; charset=utf-8'})
				.done(function(response) {
					if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
					
						$('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');
						return false;
					}
					else
					{
						if(response.invoice_nos !== 'undefined')
						{
							let d = grid.dataSource._data;
							for(let invoice_id in response.invoice_nos)
							{
								for(let i=0;i<d.length;i++)
								{
									if(d[i].invoice_id == invoice_id)
									{
										$("[data-uid="+d[i].uid+"] > td.k-command-cell > button.k-grid-editD").hide();
										$("[data-uid="+d[i].uid+"] > td.k-command-cell > button.k-grid-delete").hide();
										$("[data-uid="+d[i].uid+"] > td.k-command-cell > button.k-grid-release").hide();
										$("[data-uid="+d[i].uid+"] > td.k-command-cell > button.k-grid-invoice").addClass('ms-0');
										$("[data-uid="+d[i].uid+"] > td")[7].innerText= response.invoice_nos[invoice_id];
										$("[data-uid="+d[i].uid+"] > td")[13].innerText= 'Emisa';
										d[i].invoice_no = response.invoice_nos[invoice_id];
										d[i].invoice_status = 'Emisa';
									
										facturi += response.invoice_nos[invoice_id] + ', ';
										break;
									}																
								}								
							}

							getBadges_active();
							$('#staticNotification').data('kendoNotification').show('Facturile ' + facturi.slice(0,-2) + ' au fost emise.', 'info');
							//$("#grid").data("kendoGrid").dataSource.read();
						}
							
						
					}
				
					kendo.ui.progress($(document.body), false);
				})
				.fail(function() {				
					//probleme de retea
					$('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
					kendo.ui.progress($(document.body), false);
					return false;
				});			

	}
	
  function bulkDestroy() {

	var grid = $("#grid").data("kendoGrid");
	
	if (confirm("Sigur doriti sa stergeti " + grid.selectedKeyNames().length + " facturi? \n\n Doar facturile in pregatire vor fi sterse.") != true) {
	  return;
	}

	var data = {
			models: [
					 {
						invoiceIds :grid.selectedKeyNames().join(", ")
					 }
					]
			};
							
	var jqxhr = $.post({
						url: window.location.origin+'/api?subject=custom&type=call&action=bulkInvoiceDestroy',
						data: JSON.stringify(data),
						contentType: 'application/json; charset=utf-8'})
				.done(function(response) {
					if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
					
						$('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');
						return false;
					}
					else
					{
						if(response.deleted_no !== 'undefined')
						{
							$('#staticNotification').data('kendoNotification').show(response.deleted_no +' facturi au fost sterse!', 'info');
							
							updateBadges_invoice_date();
							updateBadges_invoice_due_date();
							getBadges_active();
							 
							grid.clearSelection();
							//grid._selectedIds=[];
							$("#grid").data("kendoGrid").dataSource.read();
						}
							
						
					}
				})
				.fail(function() {				
					//probleme de retea
					$('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
					return false;
				});
				
	}
</script>

</body>
</html>
