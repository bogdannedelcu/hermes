<?php require_once('header.php') ?>


<?php 
foreach($css_files as $file): ?>
	<link type="text/css" rel="stylesheet" href="<?php echo $file; ?>" />
<?php endforeach; ?>

<?php foreach($js_files as $file): ?>
	<script src="<?php echo $file; ?>"></script>
<?php endforeach; ?>

 <style type="text/css">
           .gc-modal-body {
           min-height: 400px !important;
           max-height: 500px !important;
           }
      </style>	
<script>

window.addEventListener('gcrud.datagrid.ready', () => {
	
	if (!window.location.href.includes('view_invoiced_services')) return;
     // customerName is the fieldName of the column
	 document.querySelector('.gc-search-column[data-order-by="vat"]').style.minWidth="80px";
	 document.querySelector('.gc-search-column[data-order-by="unit_price"]').style.minWidth="80px";
	 document.querySelector('.gc-search-column[data-order-by="contract_id"]').style.minWidth="80px";
	 document.querySelector('.gc-search-column[data-order-by="zone_name"]').style.minWidth="80px";
	 document.querySelector('.gc-search-column[data-order-by="value"]').style.minWidth="80px";
	 document.querySelector('.gc-search-column[data-order-by="total_consumption"]').style.minWidth="80px";
	 document.querySelector('.gc-search-column[data-order-by="measurement_unit"]').style.minWidth="80px";
	 document.querySelector('.gc-search-column[data-order-by="customer_name"]').style.minWidth="250px";
	 document.querySelector('.gc-search-column[data-order-by="service_name"]').style.minWidth="350px";
	 document.querySelector('.gc-search-column[data-order-by="supplier_name"]').style.minWidth="300px";
});

window.addEventListener('gcrud.form.add_form', () => {
    // Your JavaScript code here
	if (!window.location.href.includes('service_rates_management')) return;
	
	var contract_el = $("select[name=contract_id]").eq(0);
	contract_el.find('option').remove().end().change();	
	
	$.post("<?php echo base_url();?>/Invoices/json_get_supplier_data",
    {
      supplierId: $("select[name=supplier_id]").eq(0).val()
    },
    function(data,status){
		const supplier_data = jQuery.parseJSON(data);
		
		var service_el = $("select[name=service_id]").eq(0);
		services = supplier_data.services;
		
		service_el.find('option').remove().end().change();
		
		$.each(services, function(i, value) {
            service_el.append($('<option>').text(services[i].service_name).attr('value', services[i].service_id));
		});

		service_el.first().change();

	})
	
});

window.addEventListener('gcrud.form.edit_form', () => {
    // Your JavaScript code here
	
	//reset password
	$(".gc-modal-body :input[name='password']").val('');
	
	if (!window.location.href.includes('service_rates_management')) return;
	var customer_el = $("select[name=customer_id]").eq(0);
	var sel_customer = customer_el.val();
	
	var contract_el = $("select[name=contract_id]").eq(0);
	var sel_contract = contract_el.val();
	
	
	//alert(sel_customer+"-"+sel_contract);
	
	
	$.post("<?php echo base_url();?>/Invoices/json_get_customer_data",
    {
      customerId: sel_customer
    },
    function(data,status){
		const customer_data = jQuery.parseJSON(data);
		contracts = customer_data.contracts;
		
		
		//alert("Val: " + zone_el.val() + "\nText: " + zone_el.text());
		//zone_el.find('option').remove().end().append('<option value="whatever">text</option>').val('whatever');
		
		contract_el.find('option').remove().end().change();
		
		$.each(contracts, function(i, value) {
            contract_el.append($('<option>').text(contracts[i].contract_number).attr('value', contracts[i].contract_id));
		});

		contract_el.val(sel_contract).change();
		
	
	})
	
		
	var service_el = $("select[name=service_id]").eq(0);
	var sel_service = service_el.val();
	
	$.post("<?php echo base_url();?>/Invoices/json_get_supplier_data",
    {
      supplierId: $("select[name=supplier_id]").eq(0).val()
    },
    function(data,status){
		const supplier_data = jQuery.parseJSON(data);
		services = supplier_data.services;
		
		service_el.find('option').remove().end().change();
		
		$.each(services, function(i, value) {
            service_el.append($('<option>').text(services[i].service_name).attr('value', services[i].service_id));
		});

		service_el.val(sel_service).change();
	})
	
	
});

$(document).on('change','select[name=supplier_id]', function(){
	
	//check open modal
	if(! $('.gc-form-operation-modal').hasClass('in')) return;
	
	if (!window.location.href.includes('service_rates_management')) return;
	
	
	$.post("<?php echo base_url();?>/Invoices/json_get_supplier_data",
    {
      supplierId: this.value
    },
    function(data,status){
		const supplier_data = jQuery.parseJSON(data);
		
		var service_el = $("select[name=service_id]").eq(0);
		services = supplier_data.services;
		
		service_el.find('option').remove().end().change();
		
		$.each(services, function(i, value) {
            service_el.append($('<option>').text(services[i].service_name).attr('value', services[i].service_id));
		});

		service_el.first().change();

	})
})

$(document).on('change','select[name=customer_id]', function(){
	
	//check open modal
	if(! $('.gc-form-operation-modal').hasClass('in')) return;
	
	//$('select[name=zone_id]').empty();
	if (!window.location.href.includes('service_rates_management')) return;
	
	
	$.post("<?php echo base_url();?>/Invoices/json_get_customer_data",
    {
      customerId: this.value
    },
    function(data,status){
		const customer_data = jQuery.parseJSON(data);
		//alert("Data: " + customer_data.zones + "\nStatus: " + status);
		
		var contract_el = $("select[name=contract_id]").eq(0);
		contracts = customer_data.contracts;
		
		//alert("Val: " + zone_el.val() + "\nText: " + zone_el.text());
		//zone_el.find('option').remove().end().append('<option value="whatever">text</option>').val('whatever');
		
		contract_el.find('option').remove().end().change();
		
		$.each(contracts, function(i, value) {
            contract_el.append($('<option>').text(contracts[i].contract_number).attr('value', contracts[i].contract_id));
		});

		contract_el.first().change();
	
	})
})

//CKEDITOR.config.width ='950px';
//CKEDITOR.config.height='600px';
CKEDITOR.config.startupMode = 'source';
</script>

    <div style="padding: 10px">
		<?php echo $output; ?>
    </div>
	

<?php require_once('footer.php') ?>
</body>
</html>
