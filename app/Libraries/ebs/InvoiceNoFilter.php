<?php
class InvoiceNoFilter 
{
	private $autocomplete;
	
	function __construct($invoiceNo=null, $textField='invoice_no_search', $valueField='invoice_id', $subject='invoices')
	{	
		$dataSource = $this->dataSource($subject);
		$this->autocomplete =  new \Kendo\UI\AutoComplete('select-invoiceNo');
		$this->autocomplete->dataSource($dataSource)
           ->dataTextField($textField)
		   ->filter('contains')
           ->placeholder('Nr factura...')
		   ->autoWidth(true)
		   ->change('invoiceNoChanged')
		   ->select('invoiceNoSelected');

		$request = \Config\Services::request();
		$invoiceId = $request->getVar('invoiceId');
		if(!empty($invoiceId))
		{
			$pdfInvoiceModel = new \App\Models\pdfInvoiceModel($invoiceId);
			$invoiceNo = $pdfInvoiceModel->get_invoice_data()['invoice_no'];
		}
		
		$this->autocomplete->value($invoiceNo);
	}

	function dataSource($subject)
	{
		$transport = new \Kendo\Data\DataSourceTransport();

		$read = new \Kendo\Data\DataSourceTransportRead();
		
		$read->url(site_url().'/api?subject='.$subject.'&type=read')
			 ->contentType('application/json')
			 ->type('POST');

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
				   ->serverFiltering(true);
			
		return $dataSource;
	}

	function render()
	{
		return 
		"<div class='container'>
		  <div class='col-row align-items-start'>
			<div class='col-auto minw-400px'><b>".
			  $this->autocomplete->render().
			"</b></div>
		  </div>
		</div>".
		
		"<script>
		var DataItemIndex = 0;
		function invoiceNoSelected(e){
			if (e.item == null) return;
			DataItemIndex = e.item.index();
		}
		function invoiceNoChanged(e){
		
			window.location.replace(window.location.origin + '/index.php/invoices/invoiced_items?invoiceId=' + $('#select-invoiceNo').data('kendoAutoComplete').dataSource._data[DataItemIndex].invoice_id);
		}
		</script>";
		
	}
}

?>