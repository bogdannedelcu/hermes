<?php
function evrDataSourceCustomers()
{
	$transport = new \Kendo\Data\DataSourceTransport();
	$read = new \Kendo\Data\DataSourceTransportRead();
	$read->url(site_url().'/api?subject=custom&type=call&action=getCustomersWithConsumptionTypes')
		 ->contentType('application/json')
		 ->type('POST')
		 ->data(new \Kendo\JavaScriptFunction("function() {return {models:[{month: evrDateRange ? evrDateRange.start.getMonth()+1 : new Date().getMonth()+1, year: evrDateRange ? evrDateRange.start.getFullYear() : new Date().getFullYear()}]} }"));
	$transport->read($read)->parameterMap('function(data) { return kendo.stringify(data); }');
	$schema = new \Kendo\Data\DataSourceSchema();
	$schema->data('data')->total('total');
	$dataSource = new \Kendo\Data\DataSource();
	$group = new \Kendo\Data\DataSourceGroupItem();
	$group->field('consumption_type_name');
	$dataSource->transport($transport)->schema($schema)->serverFiltering(false)->addGroupItem($group);
	return $dataSource;
}
?>

<div class="container ms-0 mw-100">

  <div class="row py-1">
    <div class="col">
      <?php echo $data['period_filter']; ?>
    </div>
  </div>

  <div class="row py-1 align-items-center">

    <div class="col-auto align-self-center minw-100px">Perioada</div>
    <div class="col-auto">
      <div id="evr_interval" class="d-flex w-400px">
        <div><input id="evr_start_date"></input></div>
        <div class="ms-1"><input id="evr_end_date"></input></div>
      </div>
    </div>

    <div class="col-auto align-self-center p-0">Interval</div>
    <div class="col-auto">
      <?php echo $data['view_interval']; ?>
    </div>

  </div>

  <div class="row py-1 align-items-center">

    <div class="col-auto align-self-center minw-100px">Clienti</div>
    <div class="col-auto">
      <div class="w-600px">
        <?php
          $selectCustomers = new \Kendo\UI\MultiSelect('evrCustomers');
          $selectCustomers->dataSource(evrDataSourceCustomers())
              ->dataTextField('customer_name')
              ->dataValueField('customer_id')
              ->autoClose(false)
              ->placeholder('Toti clientii...')
              ->filter('contains')
              ->ignoreCase(true)
              ->change('evrCustomersChanged');
          echo $selectCustomers->render();
        ?>
      </div>
    </div>

    <div class="col-auto align-self-center">Algoritmi</div>
    <div class="col-auto d-flex align-items-center gap-2" id="evr_algorithms_container">
      <!-- Populated by JS from evrAlgorithms config -->
    </div>

    <div class="col-auto ms-2">
      <button class="btn btn-outline-primary btn-sm" onclick="evrLoad()">
        <i class="fa fa-refresh"></i> Incarca
      </button>
    </div>

    <div class="col-auto ms-1">
      <button class="btn btn-outline-success btn-sm" onclick="evrEstimate()" id="evr_btn_estimate">
        <i class="fa fa-play"></i> Estimeaza
      </button>
    </div>

  </div>

</div>
