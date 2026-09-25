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
           min-height: 500px !important;
           max-height: 953px !important;
           }
      </style>	  
	  
<script>

window.addEventListener('gcrud.datagrid.ready', () => {
	
	if (!window.location.href.includes('view_invoiced_services')) return;
     // customerName is the fieldName of the column
	 document.querySelector('.gc-search-column[data-order-by="invoice_calculated_ea_quantity"]').style.minWidth="40px";
	 //document.querySelector('.gc-search-column[data-order-by="invoice_calculated_total"]').style.minWidth="80px";
	 document.querySelector('.gc-search-column[data-order-by="contract_id"]').style.minWidth="80px";
	 //document.querySelector('.gc-search-column[data-order-by="zone_id"]').style.minWidth="100px";
	 document.querySelector('.gc-search-column[data-order-by="invoice_status"]').style.minWidth="60px";
	  //document.querySelector('.gc-search-column[data-order-by="invoice_no"]').style.minWidth="80px";
	   //document.querySelector('.gc-search-column[data-order-by="invoice_due_date"]').style.minWidth="80px";
	   //document.querySelector('.gc-search-column[data-order-by="supplier_id"]').style.minWidth="400px";
	   //document.querySelector('.gc-search-column[data-order-by="customer_id"]').style.minWidth="400px";
});

const requestWait = async (link) => {
//	console.log("Link: "+link);
    const response = await fetch(link);
    const json = await response.json();
	document.querySelector(".gc-container .fa-refresh").click();
//	document.getElementsByClassName('fa-refresh')[0].click();
//	console.log("Invoice: "+json);
}

function hashHandler() {
	//emite factura!!
	var str = window.location.href.replace('#',''); //location.hash.replace('#','');
	requestWait(str); //await fetch(str).json();
	//console.log('The hash has changed!'+str);
}

window.addEventListener('hashchange', hashHandler, false);


$(function(){ // let all dom elements are loaded
	$(document).on('shown.bs.modal','div.modal', function () {
		
		/*
		var supplier_Id = $("select[name=supplier_id] option:selected").val();
		//alert(supplier_Id);
		$.post("<?php echo base_url();?>/Invoices/json_get_invoice_no",
		{
		  supplierId: supplier_Id
		},
		function(data,status){
			const jdata = jQuery.parseJSON(data);
			$("input[name=invoice_no]").val(jdata);
		//	alert("Data: " + jdata + "\nStatus: " + status);
		})
		*/
			
		//var button = $(event.relatedTarget) // Button that triggered the modal
		//var recipient = button.data('whatever') // Extract info from data-* attributes
		// If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
		// Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.

		var modal = $(this);
		//if (modal.find('.modal-title').text().includes("Add") == true)
		{
			//modal.find('select[name=customer_id]').prop("selectedIndex", -1);
			//modal.find('select[name=zone_id]').find('option').remove().end();
			//modal.find('select[name=customer_id] option:eq(2)').attr('selected', 'selected');
			//if (modal.find('.modal-title').text().includes("Add") == true)
			//	modal.find('select[name=customer_id]').prop('selectedIndex', 1);
			// modal.find('select[name=customer_id]').trigger('change');
			//modal.find('select[name=customer_id]').first();
		}
	});
});

$(document).on('change','input[name=invoice_date]', function(){
	
	//check open modal
	if(! $('.gc-form-operation-modal').hasClass('in')) return;
	
	$.post("<?php echo base_url();?>/Invoices/json_get_customer_data",
    {
      customerId: $("select[name=customer_id]").val()
    },
    function(data,status){
		const customer_data = jQuery.parseJSON(data);
		//alert("Data: " + customer_data.zones + "\nStatus: " + status);
		
		var date = new Date($("input[name=invoice_date]").datepicker('getDate')),
		days = parseInt(customer_data.duedays, 10);
		   
		$("label:contains('Data Scadenta')")[0].textContent = "*Data Scadenta (" + days + " zile)";
	  
        if(!isNaN(date.getTime())){
            date.setDate(date.getDate() + days);

            $("input[name=invoice_due_date]").datepicker("setDate", date);
        } else {
            //alert("Invalid Invoice Date");  
        }
	})
})

$(document).on('change','select[name=customer_id]', function(){
	//$('select[name=zone_id]').empty();
	//check open modal
	if(! $('.gc-form-operation-modal').hasClass('in')) return;
		
	$.post("<?php echo base_url();?>/Invoices/json_get_customer_data",
    {
      customerId: this.value
    },
    function(data,status){
		const customer_data = jQuery.parseJSON(data);
		//alert("Data: " + customer_data.zones + "\nStatus: " + status);
		
		var date = new Date($("input[name=invoice_date]").datepicker('getDate')),
		days = parseInt(customer_data.duedays, 10);
		   
		$("label:contains('Data Scadenta')")[0].textContent = "*Data Scadenta (" + days + " zile)";
	  
        if(!isNaN(date.getTime())){
            date.setDate(date.getDate() + days);

            $("input[name=invoice_due_date]").datepicker("setDate", date);
        } else {
            //alert("Invalid Invoice Date");  
        }
		
		//var contract_el = $("select[name=contract_id]").eq(0);
		//contract_el.first().change();
		
		var zone_el = $("select[name=zone_id]").eq(0);
		zones = customer_data.zones;
		var sel_zone = zone_el.val();
		
		//alert("Val: " + zone_el.val() + "\nText: " + zone_el.text());
		//zone_el.find('option').remove().end().append('<option value="whatever">text</option>').val('whatever');
		
		zone_el.find('option').remove().end().change();
		
		$.each(zones, function(i, value) {
            zone_el.append($('<option>').text(zones[i].zone_name).attr('value', zones[i].zone_id));
		});

		zone_el.first().change();

		/*zone_el.find('option').each(function() {
			
			var hasMatch =false;
			//alert(this.text + ' ' + this.value);
			for (var index = 0; index < zones.length; ++index) {

			 if(zones[index].zone_id == this.value){
			   hasMatch = true;
			   break;
			 }
			}
			 
			if (!hasMatch) this.remove();
		});*/
				
		//zone_el.val(sel_zone).change();
		//zone_el.first().change();
	})
})

window.addEventListener('gcrud.form.add_form', () => {
    // Your JavaScript code here
	var zone_el = $("select[name=zone_id]").eq(0);
	zone_el.find('option').remove().end().change();	
});

window.addEventListener('gcrud.form.edit_form', () => {
    // Your JavaScript code here
	var customer_el = $("select[name=customer_id]").eq(0);
	var sel_customer = customer_el.val();
	
	var zone_el = $("select[name=zone_id]").eq(0);
	var sel_zone = zone_el.val();
	
	//alert(sel_customer+"-"+sel_zone);
	
	
	$.post("<?php echo base_url();?>/Invoices/json_get_customer_data",
    {
      customerId: sel_customer
    },
    function(data,status){
		const customer_data = jQuery.parseJSON(data);
		zones = customer_data.zones;
		
		
		//alert("Val: " + zone_el.val() + "\nText: " + zone_el.text());
		//zone_el.find('option').remove().end().append('<option value="whatever">text</option>').val('whatever');
		
		zone_el.find('option').remove().end().change();
		
		$.each(zones, function(i, value) {
            zone_el.append($('<option>').text(zones[i].zone_name).attr('value', zones[i].zone_id));
		});

		zone_el.val(sel_zone).change();
	
	})
	
	
});

</script>
	
<body>
 
    <div class="px-2" style="margin-top:-10px">
		<?php echo $output; ?>
    </div>
	

<?php require_once('footer.php') ?>
</body>
</html>
