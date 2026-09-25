<?php
function dataSourceCustomers()
{
	$transport = new \Kendo\Data\DataSourceTransport();

	$read = new \Kendo\Data\DataSourceTransportRead();
	
	$read->url(site_url().'/api?subject=custom&type=call&action=getCustomersInTPWithConsumptionTypes')
		 ->contentType('application/json')
		 ->type('POST')
		 ->data(new \Kendo\JavaScriptFunction("function() {return {models:[{month:selMonth_tp+1, year: selYear_tp}]} }"));

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

function dataSourcePOD()
{
	$transport = new \Kendo\Data\DataSourceTransport();

	$read = new \Kendo\Data\DataSourceTransportRead();
	
	$read->url(site_url().'/api?subject=custom&type=call&action=getPODsOfCustomersWithConsumption')
		 ->contentType('application/json')
		 ->type('POST')
		 ->data(new \Kendo\JavaScriptFunction("function() {return {models:[{month:selMonth_tp+1, year: selYear_tp,customers:selMonth_tp+1,customerIDs:$('#mCustomers').data('kendoMultiSelect').value()}]} }"));

	$transport->read($read)
			  ->parameterMap('function(data) {
				  return kendo.stringify(data);
			   }');

	$schema = new \Kendo\Data\DataSourceSchema();
	$schema->data('data')
		   ->total('total');

	$dataSource = new \Kendo\Data\DataSource();
	
	$group = new \Kendo\Data\DataSourceGroupItem();
	$group->field('county');

	$dataSource->transport($transport)
			   ->schema($schema)
			   ->serverFiltering(false)
			   ->addGroupItem($group);
		
	return $dataSource;
}
?>

<div class="container-fluid ms-0">

 <div class="row py-1">
    <div class="col">
     <?php
		echo $data['period_filter'];
	  ?>
    </div>

 </div>

	
   
<div class="row py-1">

	<div class="col-auto align-self-center minw-100px">
	Clienti 
	</div>
	
	<div class="col-auto align-self-center">
	 <div style="width:485px">
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
			   ->deselect('mDeselect');

		if(isset($_GET['customersIDs']))
			$selectCustomers->value(explode(',',$_GET['customersIDs']));


		echo $selectCustomers->render();
	  ?>
	  </div>
	</div>
	
	<div class="col-auto align-self-center">
	 <?php
		echo $data['consumption_types'];
	  ?>
	</div>	

	<div class="col-auto align-self-center">
		<div id="dayIndexButtonGroup">

		</div>
	</div>
    
</div>

<div class="row py-1 w-100">

	<div class="col-auto align-self-center minw-100px">
	POD
	</div>
	
	<div class="col-auto align-self-center">
	 <div style="width:485px">
	 <?php
		$selectPOD = new \Kendo\UI\DropDownList('selectPod');
		$selectPOD->dataSource(dataSourcePOD())
           ->dataValueField('pod')
		   ->dataTextField('pod')
           ->optionLabel('Selectati POD-ul...')
		   ->change('podChanged')
		   ->height(500)
		   ->filter('contains');

		echo $selectPOD->render();
	  ?>
	  </div>
	</div>
	
	<div class="col-auto align-self-center">
	 <div style="width:120px">
	 <?php
		$scale = new \Kendo\UI\DropDownList('scale');
		$scale->dataSource(['Energie','Temperatura'])
			->value('Energie')
			->change('scaleChanged');
		
		echo $scale->render();
	  ?>
	  </div>
	</div>
	
	<div class="col-auto align-self-center p-0" id="tempResolutionWrapper">
		<input style="width:70px;" id="tempResolution" />
	</div>
	
	<div class="col-auto align-self-center">
		<div id="tempButtonGroup">

		</div>
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