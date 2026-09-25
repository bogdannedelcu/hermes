<?php
function dataSourceCustomers()
{
	$transport = new \Kendo\Data\DataSourceTransport();

	$read = new \Kendo\Data\DataSourceTransportRead();
	
	$read->url(site_url().'/api?subject=custom&type=call&action=getCustomersWithConsumptionTypes')
		 ->contentType('application/json')
		 ->type('POST')
		 ->data(new \Kendo\JavaScriptFunction("function() {return {models:[{month:prevDateRange.start.getMonth()+1, year: prevDateRange.start.getFullYear()}]} }"));

	$transport->read($read)
			  ->parameterMap('function(data) {
				  return kendo.stringify(data);
			   }');

	$schema = new \Kendo\Data\DataSourceSchema();
	$schema->data('data')
		   ->total('total');

	$dataSource = new \Kendo\Data\DataSource();
	
	$group = new \Kendo\Data\DataSourceGroupItem();
	$group->field('consumption_type_name');

	$dataSource->transport($transport)
			   ->schema($schema)
			   ->serverFiltering(false)
			   ->addGroupItem($group);
		
	return $dataSource;
}
?>

<div class="container ms-0  mw-100">

 <div class="row py-1">
    <div class="col">
     <?php
		echo $data['period_filter'];
	  ?>
    </div>
</div>
	
 <div class="row py-1">
	
    <div class="col-auto align-self-center minw-100px">
    Estimare 
    </div>
    <div class="col-auto">
		<div id="interval" class="d-flex w-400px">
			<div><input id="estimation_start_date"></input></div>
			<div class="ms-1"><input id="estimation_end_date"></input></div>
		</div>
    </div>
	<div class="col p-0" style="max-width:240px"></div>  
	<div class="col-auto align-self-center p-0">
     Vizualizare
    </div>	
    <div class="col-auto">
     <?php
		echo $data['view_types'];
	  ?>
    </div>	
	<div class="col-auto align-self-center p-0" id="interval_label">
     Interval
    </div>	
	<div class="col-auto">
     <?php
		echo $data['view_interval'];
	  ?>
    </div>	
    <div class="col-auto align-self-center ps-1 pe-0" id="zoom_label">
     Zoom
    </div>	
    <div class="col-auto">
     <?php
		echo $data['zoom_types'];
	  ?>
    </div>	
	<div class="col-auto align-self-center ps-1 pe-0" id="extra_info_label">
     Analiza
    </div>
    <div class="col-auto" id="extra_info">
     <?php
		$switch = new \Kendo\UI\SwitchButton('extraInfo');
		$switch->change('onExtraInfoChange');
		echo $switch->render();
	  ?>
    </div>		
  </div>
  
   <div class="row py-1">

    <div class="col-auto align-self-center minw-100px">
    Clienti 
    </div>
    <div class="col-auto align-self-center ">
	 <div class="w-600px align-self-center">
     <?php
		$selectCustomers = new \Kendo\UI\MultiSelect('mCustomers');
		$selectCustomers->dataSource(dataSourceCustomers())
			   ->dataTextField('customer_name')
			   ->dataValueField('customer_id')
			   ->autoClose(false)
			   ->placeholder('Toti clientii...')
			   ->filter('contains')
			   ->ignoreCase(true)
			   ->close('mClose')
			   //->open('mOpen')
			   ->select('mSelect')
			   ->change('mChange')
			   ->deselect('mDeselect');

		if(isset($_GET['customersIDs']))
			$selectCustomers->value(explode(',',$_GET['customersIDs']));


		echo $selectCustomers->render();
	  ?>
	  </div>
    </div>
    <div class="col">
     <?php
		echo $data['consumption_types'];
	  ?>
    </div>	


  </div>
  
  
</div>

<script>

 $( document ).ready(function() {
	
	$(".minw-120px").addClass("minw-100px").removeClass("minw-120px");
 
 });


function queryReport(e)
{
	window.location.href = location.protocol + "//" + location.host + location.pathname+"?date="+selYear_tp + "-"+(selMonth_tp+1)+"&customerID="+$('#select-customer').data('kendoDropDownList').value();
}

</script>