<div class ="container ms-0"> 
<div class='row py-1'>
	<div class='col-auto'>
	<div class="container ms-0">
		<div class='row py-1'>
			<div class='col-auto align-self-center minw-120px'>
			Client
			</div>
			<div class='col-auto fw-bold' id='customer_name'>
			<?=$data['customer_name']?>
			</div>
		</div> 

		<div class='row py-1'>
			<div class='col-auto align-self-center minw-120px'>
			Total
			</div>
			<div class='col-auto minw-120px fw-bold'  id='total_amount'>
			<?=$data['total_amount']?> LEI
			</div>

			<div class='col-auto align-self-left minw-120px'>
			EA
			</div>
			<div class='col-auto fw-bold'  id='total_ea'>
			<?=$data['total_ea']?> MWh
			</div>
			
		</div>

		<div class='row py-1'>
			<div class='col-auto align-self-center minw-120px'>
			Lot
			</div>
			<div class='col-auto minw-120px fw-bold'  id='zone_name'>
			<?=$data['zone_name']?>
			</div>

			<div class='col-auto align-self-center minw-120px'>
			Contract
			</div>
			<div class='col-auto fw-bold'  id='contract'>
			<?=$data['contract']?>
			</div>
		</div> 

		<div class='row py-1'>
			<div class='col-auto align-self-center minw-120px'>
			Data Facturarii
			</div>
			<div class='col-auto minw-120px fw-bold'  id='invoice_date'>
			<?=$data['invoice_date']?>
			</div>
			
			<div class='col-auto align-self-center minw-120px'>
			Data Scadentei
			</div>
			<div class='col-auto fw-bold'  id='invoice_due_date'>
			<?=$data['invoice_due_date']?>
			</div>
		</div> 



		<div class='row py-1'>
			<div class='col-auto align-self-center minw-120px'>
			Storno
			</div>
			<div class='col-auto minw-120px fw-bold' id='storno_no'>
			<?=$data['storno_no']?>
			</div>

			<div class='col-auto align-self-center minw-120px'>
			Status
			</div>
			<div class='col-auto fw-bold'  id='invoice_status'>
			<?=$data['invoice_status']?>
			</div>
		</div>
	</div>
	</div>
	<div class='col-auto w-50'>
	<textarea class='w-100 h-100' id='comments' placeholder="Observatii..." required data-required-msg="Observatii" data-max-msg="Enter value between 1 and 200" ><?=$data['comments']?></textarea>
	</div>
</div>	
</div>
<script>
$('#comments').bind('input propertychange', function() {

var data = {
			models: [
					 {
						invoiceId :<?=$data['invoice_id']?>,
						comments: $("#comments").val()
					 }
					]
			};
							
	var jqxhr = $.post({
						url: window.location.origin+'/api?subject=custom&type=call&action=saveInvoiceComments',
						data: JSON.stringify(data),
						contentType: 'application/json; charset=utf-8'})
				.done(function(response) {
					if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
					
						$('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');
						return false;
					}
					else
					{
						if(response.ret<1)
							$('#staticNotification').data('kendoNotification').show('Nu pot salva comentariile...', 'error');
					}
				})
				.fail(function() {				
					//probleme de retea
					$('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
					return false;
				});

});
</script>