<?php
function dataSourceCustomers()
{
	$transport = new \Kendo\Data\DataSourceTransport();

	$read = new \Kendo\Data\DataSourceTransportRead();
	
	$read->url(site_url().'/api?subject=custom&type=call&action=getCustomerByDistributor')
		 ->contentType('application/json')
		 ->type('POST')
		 ->data(new \Kendo\JavaScriptFunction("function() {return {models:[{distributor_id: dId[$('#select-distributor').data('kendoButtonGroup').current().index()],date:selYear_tp + '-'+(selMonth_tp+1)}]} }"));

	$transport->read($read)
			  ->parameterMap('function(data) {
				  return kendo.stringify(data);
			   }');

	$schema = new \Kendo\Data\DataSourceSchema();
	$schema->data('data')
		   ->total('total');

	$dataSource = new \Kendo\Data\DataSource();
	
	$dataSource->transport($transport)
			   ->schema($schema)
			   ->serverFiltering(false);
		
	return $dataSource;
}


function dataSourcePODs()
{
	$transport = new \Kendo\Data\DataSourceTransport();

	$read = new \Kendo\Data\DataSourceTransportRead();
	
	$read->url(site_url().'/api?subject=custom&type=call&action=getPODsByDistributorCustomer')
		 ->contentType('application/json')
		 ->type('POST')
		 ->data(new \Kendo\JavaScriptFunction("function() { 
		 							let urlParams = new URLSearchParams(window.location.search);
									if ($('#mCustomers').data('kendoMultiSelect') === undefined)
									{
										if(urlParams.has('customersIDs'))
											mCustomersIDs = urlParams.get('customersIDs');
										else
											mCustomersIDs = '';
									}
									else
										mCustomersIDs = $('#mCustomers').data('kendoMultiSelect').value().join(',');
									
										if(urlParams.has('includedCurves'))
										includedCurves = urlParams.get('includedCurves');
												else
										includedCurves = '';
									
										return {models:[{
													distributor_id: dId[$('#select-distributor').data('kendoButtonGroup').current().index()],
													customersIDs: mCustomersIDs, date:selYear_tp + '-'+(selMonth_tp+1),
													includedCurves: includedCurves
													}]} }"
									));

	$transport->read($read)
			  ->parameterMap('function(data) {
				  return kendo.stringify(data);
			   }');

	$schema = new \Kendo\Data\DataSourceSchema();
	$schema->data('data')
		   ->total('total');

	$dataSource = new \Kendo\Data\DataSource();
	
	$dataSource->transport($transport)
			   ->schema($schema)
			   ->serverFiltering(false);
		
	return $dataSource;
}

function dataSourceCurves()
{
	$transport = new \Kendo\Data\DataSourceTransport();

	$read = new \Kendo\Data\DataSourceTransportRead();
	
	$read->url(site_url().'/api?subject=custom&type=call&action=getCurvesByDistributor')
		 ->contentType('application/json')
		 ->type('POST')
		 ->data(new \Kendo\JavaScriptFunction("function() {
															let urlParams = new URLSearchParams(window.location.search);
															if(urlParams.has('customersIDs'))
																mCustomersIDs = urlParams.get('customersIDs');
															else
																mCustomersIDs = '';

															if(urlParams.has('includedPODs'))
																includedPODs = urlParams.get('includedPODs');
															else
																includedPODs = '';
															
															return {models:[{
																			distributor_id: dId[$('#select-distributor').data('kendoButtonGroup').current().index()],
																			date:selYear_tp + '-'+(selMonth_tp+1),
																			includedPODs:includedPODs,
																			customersIDs:mCustomersIDs
																			}]} 
															}"));

	$transport->read($read)
			  ->parameterMap('function(data) {
				  return kendo.stringify(data);
			   }');

	$schema = new \Kendo\Data\DataSourceSchema();
	$schema->data('data')
		   ->total('total');

	$dataSource = new \Kendo\Data\DataSource();
	
	$dataSource->transport($transport)
			   ->schema($schema)
			   ->serverFiltering(false);
		
	return $dataSource;
}

?>

<div class="container ms-0">

 <div class="row py-1">

    <div class="col">
     <?php
		echo $data['period_filter'];
	  ?>
    </div>
	
	<div class="col-auto align-left maxw-100px">
	<?php echo $data['type_filter'];?>
	</div>
	
  </div>
 
  
  <div class="row py-1">
    <div class="col-auto align-self-center minw-100px">
    Distribuitor 
    </div>
    <div class="col">
     <?php
		echo $data['distributor_filter'];
	  ?>
    </div>
	
  </div>
  
    <div class="row py-1">
    <div class="col-auto align-self-center minw-100px">
      Clienti
    </div>
    <div class="col">
     <?php

		$selectCustomers = new \Kendo\UI\MultiSelect('mCustomers');
		$selectCustomers->dataSource(dataSourceCustomers())
			   ->dataTextField('customer_name')
			   ->dataValueField('customer_id')
			   ->autoClose(false)
			   ->placeholder('Selecteaza clientii...')
			   ->filter('contains')
			   ->ignoreCase(true)
			   ->close('queryReport')
			   ->open('mCOpen')
			   ->deselect('mCDeselect');

		if(isset($_GET['customersIDs']))
			$selectCustomers->value(explode(',',$_GET['customersIDs']));


		echo $selectCustomers->render();


	?>
    </div>
	<div class="col">
		<div id="colCurves" class="w-100 d-inline-flex position-absolute">
			<div class="align-self-center me-4">Curbe</div>
			<?php

			$selectCurves = new \Kendo\UI\MultiSelect('mCurves');
			$selectCurves->dataSource(dataSourceCurves())
				   ->dataTextField('curve_name')
				   ->dataValueField('curve_id')
				   ->autoClose(false)
				   ->placeholder('Selecteaza curbe...')
				   ->filter('contains')
				   ->ignoreCase(true)
				   ->close('queryReport')
				   ->open('mCurveOpen')
				   ->deselect('mCurveDeselect')
				   ->dataBound(new \Kendo\JavaScriptFunction('function(e) {
					   
						let values = [];
						let urlParams = new URLSearchParams(window.location.search);
						
						if(urlParams.has("includedCurves") && urlParams.get("includedCurves").length>0) 
							values = urlParams.get("includedCurves").split(",");
						else
							values = $.map(e.sender.dataSource.data(), function(dataItem) {
									  return dataItem.curve_id;
									});
						prevCurveList = values.join(",");
						e.sender.value(values);
					   }'));

			if(isset($_GET['includedCurves']))
				$selectCurves->value(explode(',',$_GET['includedCurves']));


			echo $selectCurves->render();
			?>	
		</div>
	</div>
  </div>

    <div class="row py-1">
    <div class="col-auto align-self-center minw-100px">
      POD
    </div>
    <div class="col">
     <?php

		$selectPODs = new \Kendo\UI\MultiSelect('mPODs');
		$selectPODs->dataSource(dataSourcePODs())
			   ->dataTextField('pod_no')
			   ->dataValueField('pod_no')
			   ->autoClose(false)
			   ->placeholder('Selecteaza pod...')
			   ->filter('contains')
			   ->ignoreCase(true)
			   ->close('queryReport')
			   ->open('mPOpen')
			   ->deselect('mPDeselect')
			   ->dataBound(new \Kendo\JavaScriptFunction('function(e) {
				   
				    let values = [];
					let urlParams = new URLSearchParams(window.location.search);
				    
					if(urlParams.has("includedPODs") && urlParams.get("includedPODs").length>0) 
						values = urlParams.get("includedPODs").split(",");
					else
					    values = $.map(e.sender.dataSource.data(), function(dataItem) {
								  return dataItem.pod_no;
								});
					prevPODSList = values.join(",");
					e.sender.value(values);
				   }'));
		
		echo $selectPODs->render();


	?>
    </div>
	
	<div class="col"> </div>
	
  </div>
  
</div>

<script>

var mCurveStatus = false;
var prevCurveList;
function mCurveOpen()
{
	mCurveStatus = true;
}

function mCurveDeselect()
{
	if(!mCurveStatus)
		$("#mCurves").data("kendoMultiSelect").open();
}

var mcStatus = false;
var prevClientList;
function mCOpen() {
	mcStatus = true;
}

function mCDeselect() {
	if(!mcStatus)
		$("#mCustomers").data("kendoMultiSelect").open();
}

var mpStatus = false;
var prevPODSList;
function mPOpen() {
	mpStatus = true;
}

function mPDeselect() {
	if(!mpStatus)
		$("#mPODs").data("kendoMultiSelect").open();
}

 $( document ).ready(function() {
	
	prevClientList = $("#mCustomers").data("kendoMultiSelect").value().join(',');
	
	$(".minw-120px").addClass("minw-100px").removeClass("minw-120px");
 
 });

function queryReport(e)
{
	let elementChanged= '';
	
	if (mCurveStatus && prevCurveList != $("#mCurves").data("kendoMultiSelect").value().join(',')) elementChanged = 'curves'; 
	else if (mpStatus && prevPODSList != $("#mPODs").data("kendoMultiSelect").value().join(',')) elementChanged = 'pods'; 
	else if (mcStatus && prevClientList != $("#mCustomers").data("kendoMultiSelect").value().join(',') ) elementChanged = 'customers'; 
	else if (e.sender.options.name == 'ButtonGroup')  elementChanged = 'distributor'; 
	
	let selCurves = '';
	if(elementChanged == 'curves')
		selCurves = $("#mCurves").data("kendoMultiSelect").value().join(',');
		
	
	if ( elementChanged != '' )
	{
		let p_distributor = '';
		let idx = $('#select-distributor').data('kendoButtonGroup').current().index();
		if( idx != -1 ) p_distributor = dId[idx];
		
		if (prevClientList != $("#mCustomers").data("kendoMultiSelect").value().join(','))
			$("#mPODs").data("kendoMultiSelect").value([]);
			
		
		window.location.href = location.protocol + "//" + location.host + location.pathname+"?date="+selYear_tp + "-"+(selMonth_tp+1)+"&customersIDs="+$("#mCustomers").data("kendoMultiSelect").value().join(',')+"&distributor_id="+p_distributor + "&includedPODs="+$("#mPODs").data("kendoMultiSelect").value().join(',')+ "&includedCurves="+selCurves+'&type_filer='+$('#select-activ').data('kendoButtonGroup').current().index()+'&elementChanged='+elementChanged;
	}
}

</script>