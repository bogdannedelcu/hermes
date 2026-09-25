<?php 

namespace App\Controllers;

require_once('tools.php');
require_once('GridTools.php');

use GroceryCrud\Core\GroceryCrud;
require_once(APPPATH . 'Libraries/ebs/TimePeriodFilter.php');
require_once(APPPATH . 'Libraries/ebs/DistributorFilter.php');
require_once(APPPATH . 'Libraries/ebs/ActiveFilter.php');

class Invoices extends BaseController
{
	use \GridTools;
	private $data = [
        'title'   => 'Facturi',
		'div-card' => 'card-invoices',
		'menu' => 'facturi'];
	
	protected $pdfInvoice;	
    protected $invoicesModel;

	
	function __construct() {	
		checkAuth();

	}
	
    /**
     * Initializer 
     */
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {

        parent::initController($request, $response, $logger);

        // Load the model
        $this->invoicesModel = new \App\Models\InvoicesModel();
		$this->pdfInvoice = new PdfInvoice(); // Create an instance
    }
	
	public function index()
	{
		
		$transport = $this->createGridTransport('invoices');

		$schema = $this->createGridSchema([
		'invoice_id','supplier_id','invoice_calculated_distributor_id','customer_id','contract_id','zone_id','invoice_no',
		'invoice_date','invoice_due_date','invoice_calculated_ea_quantity','invoice_calculated_ea_unit_price','invoice_calculated_total','invoice_status'],
		['number','string','string','string','string','string','string',
		'date','date','number','number','number','string']);
		
	  	$sortItem = $this->createSortItem('invoice_date');
		
		$invoiceCount = new \Kendo\Data\DataSourceAggregateItem();
		$invoiceCount->field("invoice_no")
                 ->aggregate("count");
		
		$eaQuantity = new \Kendo\Data\DataSourceAggregateItem();
		$eaQuantity->field("invoice_calculated_ea_quantity")
                ->aggregate("sum");
				
		$invoiceTotal = new \Kendo\Data\DataSourceAggregateItem();
		$invoiceTotal->field("invoice_calculated_total")
                ->aggregate("sum");
				 
		$dataSource = $this->createDataSource($transport,$schema);
	
		$idY = $this->invoicesModel->get_invoicedate_min_max_years();
		$ddY = $this->invoicesModel->get_duedate_min_max_years();

		$filterItem1 = $this->createNowFilter('invoice_date', false, $idY['maxY']);
		$filterItem2 = $this->createNowFilter('invoice_due_date',true);
		$filterItem = $this->combineFilters($filterItem1,$filterItem2,'and');
		
		$dataSource->addAggregateItem($invoiceCount)
				   ->addAggregateItem($eaQuantity)
				   ->addAggregateItem($invoiceTotal)
				   ->addSortItem($sortItem)
				   ->addFilterItem($filterItem);
					
		$column1 = $this->createGridColumn('supplier_name','Furnizor',100);
		$column2 = $this->createGridColumn('distributor_name','Distribuitor',100);
		$column2->template("#=data.distributor_name ?? 'General' #");
				
		$column3 = $this->createGridColumn('customer_name','Client',150);
		$column4 = $this->createGridColumn('contract_calculated_numberdate','Contract',100);
		$column5 = $this->createGridColumn('zone_name','Lot',50);
	
		$column6 = $this->createGridColumn('invoice_no','Nr factura',70);
		$column6->footerTemplate('#=count# facturi');
		
		$column7 = $this->createGridColumn('invoice_date','Data factura',70);
		$column8 = $this->createGridColumn('invoice_due_date','Scadenta',70);
		
		$column9 = $this->createGridColumn('invoice_calculated_ea_quantity','Cantitate EA [MWh]',70);
		$column9->template("#=kendo.format('{0:N3}',data.invoice_calculated_ea_quantity)#");
		$column9->footerTemplate("#=kendo.format('{0:N3}',sum)# MWh");

		$column10 = $this->createGridColumn('invoice_calculated_ea_unit_price','Pret EA [Lei]',70);
		$column10->template("#=kendo.format('{0:N2}',data.invoice_calculated_ea_unit_price)#");
		
		$column11 = $this->createGridColumn('invoice_calculated_total','Total Facturat [Lei]',70);
		$column11->template("#=kendo.format('{0:N2}',data.invoice_calculated_total)#");
		$column11->footerTemplate("#=kendo.format('{0:N2}',sum)# Lei");
		
		$column12=$this->createGridColumn('invoice_status','Stare',70);
		
		$this->grid->height(550)
				 ->dataSource($dataSource)
				 ->resizable(true);
		
		$this->addCustomOperation('addI','Adaugă','k-icon k-i-plus');
		$this->addCustomCommand('editD','k-icon k-i-pencil','InvoiceDialog');
		$this->addCustomCommand('release','k-icon k-i-check-circle','releaseInvoice');
		$this->addCustomCommand('invoice','k-icon k-i-file-pdf','viewInvoicePDF');
		$column = $this->setGridEditable('inline', false, false, true, false, true);
		$column->headerTemplate('<button type="button" class="bulkRelease k-state-disabled k-button k-button-md k-button-rectangle k-rounded-md k-button-solid k-button-solid-base k-icon-button" onclick="bulkRelease()"><span class="k-icon k-i-check-circle k-button-icon"></span></button>
								<button type="button" class="bulkPdfDownload k-state-disabled k-button k-button-md k-button-rectangle k-rounded-md k-button-solid k-button-solid-base k-icon-button" onclick="bulkPDFDownload()"><span class="k-icon k-i-file-pdf k-button-icon"></span></button>
								<button type="button" class="bulkDestroy k-state-disabled k-grid-delete k-button k-button-md k-button-rectangle k-rounded-md k-button-solid k-button-solid-base k-icon-button" onclick="bulkDestroy()"><span class="k-icon k-i-close k-button-icon"></span></button>');
		
		$this->grid->change('onChange');
		$this->grid->dataBound('function(e){if (newInvoice){InvoiceDialog();newInvoice = false;}}');
		
		
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
			
		$AFilter = new \ActiveFilter('invoice_status','invoices','Status',['In pregatire','Emisa','Arhivata']);
		$IDFilter = new \TimePeriodFilter('invoice_date','invoices','Data Factura',$idY['minY'],$idY['maxY']);
		$DDFilter = new \TimePeriodFilter('invoice_due_date','invoices','Data Scadenta',$ddY['minY'],$ddY['maxY'], null);
		$DFilter = new \DistributorFilter('invoice_calculated_distributor_id',true);
		
		$this->data['active_filter'] = $AFilter->render();	
		
		$this->data['output'] = $this->grid->render();
		$this->data['invoice_date_filter'] = $IDFilter->render();
		$this->data['due_date_filter'] = $DDFilter->render();
		$this->data['distributor_filter'] = $DFilter->render();
		
		$data['data'] = $this->data;
		return view('invoices',$data);
	}
	
	public function invoiced_items()
	{
		$request = $this->request;
		$invoiceId = $request->getVar('invoiceId');
		
		$pdfInvoiceModel = new \App\Models\pdfInvoiceModel($invoiceId);
		$invoiceData = $pdfInvoiceModel->get_invoice_data();
		
		$transport = $this->createGridTransport('invoiced_items');

		$schema = $this->createGridSchema(['invoiced_item_id','invoice_id','service_rate_id','distributor_id','invoiced_item_no','invoiced_pod_no','invoiced_item_name',
											'invoiced_item_measurement_unit','invoiced_item_quantity','invoiced_item_unit_price','invoiced_item_value','invoiced_item_vat'],
										  ['number','number','number','number','number','string','string',
										  'string','number','number','number','number'],
										   /*['supplier_id' => $suppliers[0]['value']]*/);
				
		
		$dataSource = $this->createDataSource($transport,$schema);
		
		$filterItem = $this->createFilter('invoice_id','eq',[$invoiceId]);
		$sortItem = $this->createSortItem('invoiced_item_no','asc');
		
		$dataSource->addFilterItem($filterItem)
					->addSortItem($sortItem);
		
		$column1 = $this->createGridColumn('invoiced_item_no','Nr.',80);				
		$column2 = $this->createGridColumn('invoiced_pod_no','POD',150);
		$column3 = $this->createGridColumn('invoiced_item_name','Serviciu',100);
		$column4 = $this->createGridColumn('invoiced_item_measurement_unit','UM',100);
		$column5 = $this->createGridColumn('invoiced_item_quantity','Cantitate',100);
		$column6 = $this->createGridColumn('invoiced_item_unit_price','Pret Unitar',100);
		$column7 = $this->createGridColumn('invoiced_item_value','Valoare',100);
		$column8 = $this->createGridColumn('invoiced_item_vat','TVA',100);
		
		
		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true)
			 ->remove('function(e) {location.reload(); }');/*
			 ->edit('function(e){	if(e.model.isNew() && selYear_start_date > -1 && selMonth_start_date > -1)
													{
														let y = selYear_start_date;
														let m = selMonth_start_date  + 1;
														let d = new Date(y, m, 0);
														e.model.set("start_date", y+"-"+m+"-1");
													}
															
								}');*/

		if($invoiceData['invoice_status'] == 'In pregatire')
		{
			$this->addCustomCommand('editII','k-icon k-i-pencil','invoicedItemsDialog');
			$this->addCustomOperation('addII','Adaugă','k-icon k-i-plus');
			$this->setGridEditable('popup', false, false, true, false, false);
		}
		
		
		$this->data['title'] = "Factura No.";
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		$this->data['output'] = $this->grid->render();		
		$this->data['comments'] = $this->invoicesModel->get_comments($invoiceId);
		
		$data['data'] = array_merge($this->data,$invoiceData);
			
		return view('invoiced_items',$data);
	}

	public function to_be_invoiced()
	{
		$this->data['title'] = 'Energie Nefacturata';
		
		$transport = $this->createGridTransport('view_to_be_invoiced_incl_contracts');

		$schema = $this->createGridSchema([
		'No','supplier_name','supplier_id','customer_name','contract_number','zone_name','pod_no',
		'service_name','measurement_unit','total_consumption','unit_price','value','vat','service_code'],
		['int','string','number','string','string','string','string',
		'string','string','number','number','number','number','string']);
		
	  	$sortItem = $this->createSortItem('No','asc');
				 
		$dataSource = $this->createDataSource($transport,$schema);
	
		$filterItem = $this->createFilter('supplier_id','eq',[$_SESSION['select-supplier']]);
		
		$dataSource->addSortItem($sortItem)
				   ->addFilterItem($filterItem);
				
		$column3 = $this->createGridColumn('customer_name','Client',100);
		$column4 = $this->createGridColumn('contract_number','Contract',100);
		$column5 = $this->createGridColumn('zone_name','Lot',50);
	
		$column6 = $this->createGridColumn('pod_no','Pod',100);
		
		$column7 = $this->createGridColumn('service_name','Serviciu',150);
		$column8 = $this->createGridColumn('measurement_unit','UM',50);
		$column18 = $this->createGridColumn('total_consumption','Cantitate',50);
		
		$column9 = $this->createGridColumn('unit_price','Pret Unitar',70);
		//$column9->template("#=kendo.format('{0:N2}',data.unit_price)#");

		$column10 = $this->createGridColumn('value','Valoare',70);
		$column10->template("#=kendo.format('{0:N2}',data.value)#");
		
		$column11 = $this->createGridColumn('vat','TVA',70);
		$column11->template("#=kendo.format('{0:N2}',data.vat)#");
		
		$column12=$this->createGridColumn('service_code','Cod',70);
		
		$this->grid->height(550)
				 ->dataSource($dataSource)
				 ->resizable(true);
		
		$column = $this->setGridEditable('inline', false, false, false, false, false);
				
		$this->createGridMenu();

		
		$this->data['output'] = $this->grid->render();

		
		$data['data'] = $this->data;
		return view('to_be_invoiced',$data);
	}

	public function indexOLD()
	{
		$this->data['title'] = "Facturi";

	    $crud = $this->_getGroceryCrudEnterprise();

	    $crud->setTable('invoices');
	    $crud->setSubject('Factura');
		$crud->setRelation('supplier_id','suppliers','supplier_name');
		$crud->setRelation('customer_id','customers','customer_name');
		$crud->setRelation('contract_id','contracts','{contract_number}/{contract_date}');
		//$crud->uniqueFields(['invoice_no']);
		$crud->requiredFields(['invoice_no', 'invoice_date', 'invoice_due_date','supplier_id','customer_id','zone_id']);
		//$crud->readOnlyAddFields(['invoice_status','invoice_calculated_total','invoice_calculated_ea_quantity']);
		//$crud->readOnlyEditFields(['invoice_calculated_total','invoice_calculated_ea_quantity']);
		$crud->unsetAddFields(['invoice_calculated_total','invoice_calculated_ea_quantity','invoice_status','timestamp']);
		$crud->unsetEditFields(['invoice_calculated_total','invoice_calculated_ea_quantity','timestamp','storno_no']);

		$crud->columns(['supplier_id','customer_id','contract_id','zone_id','invoice_no','invoice_due_date','invoice_calculated_total','invoice_calculated_ea_quantity','invoice_status']);
		//$crud->fieldType('invoice_date', 'invisible');
		
		//$crud->setRelationNtoN('zones', 'film_actor', 'actor', 'film_id', 'actor_id', 'fullname');
		$crud->setRelation('zone_id','zones','zone_name');
	    
		$crud->displayAs('supplier_id', 'Furnizor');
		$crud->displayAs('customer_id', 'Nume Client');
		$crud->displayAs('contract_id', 'Contract');
		$crud->displayAs('zone_id', 'Lot Client');
		$crud->displayAs('invoice_no', 'Nr Factura');
		$crud->displayAs('invoice_date', 'Data Factura');
		$crud->displayAs('invoice_due_date', 'Data Scadenta');
		$crud->displayAs('invoice_calculated_total', 'Valoare Totala');
		$crud->displayAs('invoice_calculated_ea_quantity', 'EA');
		$crud->displayAs('invoice_status', 'Stare');
	    $crud->setDependentRelation('customer_id','supplier_id','supplier_id');
		$crud->setDependentRelation('contract_id','customer_id','customer_id');
		//$crud->setDependentRelation('zone_id','customer_id','customer_id');
		$crud->defaultOrdering('timestamp','desc');
		
		
		$crud->callbackColumn('invoice_no', function ($value, $row) {
			return "<a href='" . site_url('ebs/invoiced_items?invoiceId=' . $row->invoice_id) . "'>$value</a>";
		});


		$crud->setActionButton('PDF', 'fa fa-file-pdf-o', function ($row) {
			return site_url('PdfInvoice?').'?invoiceId='.$row->invoice_id;
			//return '?invoiceId=' . $row->invoice_id;
		}, false);
		
		$crud->setActionButton('Emite', 'fa fa-check-circle-o', function ($row) {
			return '#/emiteFactura?invoiceId='.$row->invoice_id;
			//return '?invoiceId=' . $row->invoice_id;
		}, false);

		$crud->callbackBeforeDelete(function ($stateParameters) {
		  // Custom error messages are only available on Grocery CRUD Enterprise
		  if ($this->invoicesModel->get_invoice_status($stateParameters->primaryKeyValue) != 'In pregatire')
		  {
			  $errorMessage = new \GroceryCrud\Core\Error\ErrorMessage();
			  return $errorMessage->setMessage("Facturile emise nu se pot sterge sau edita!\n");
		  }
		  
		   return $stateParameters;
		});


		$crud->callbackBeforeInsert(function ($stateParameters) {
			if (!empty($stateParameters->data['storno_no'])) 
			{
				  if(!$this->invoicesModel->checkStornoInvoice($stateParameters->data['customer_id'],$stateParameters->data['zone_id'],$stateParameters->data['contract_id'],$stateParameters->data['storno_no']))
				  {
					  // The error message as a return parameter is only available at Enterprise version
					  $errorMessage = new \GroceryCrud\Core\Error\ErrorMessage();
					  return $errorMessage->setMessage("Datele pentru stornarea facturii ".$stateParameters->data['storno_no']." nu sunt corecte!\n");
				  }
			}

			return $stateParameters;
		});

		$crud->callbackBeforeUpdate(function ($stateParameters) {
		  // Custom error messages are only available on Grocery CRUD Enterprise
		
			$invoiceStatus = $this->invoicesModel->get_invoice_status($stateParameters->primaryKeyValue);
				
			if ($invoiceStatus == 'In pregatire' && $stateParameters->data['invoice_status'] == 'Emisa')
			{
				$this->emiteFactura($stateParameters->primaryKeyValue);
				if ($stateParameters->data['invoice_no'] == 'AUTO') unset($stateParameters->data['invoice_no']);
			}

			if ($invoiceStatus != 'In pregatire' && $stateParameters->data['invoice_status'] != 'In pregatire')
			{
				$errorMessage = new \GroceryCrud\Core\Error\ErrorMessage();
				return $errorMessage->setMessage("Facturile emise nu se pot sterge sau edita!\n");
			}

		  
		   return $stateParameters;
		});
		
		$crud->callbackBeforeDeleteMultiple(function ($stateParameters) {

			//print_r($stateParameters); // An example for debugging purposes!
			/** This will export something like this: 
				stdClass Object
				(
					[primaryKeys] => Array
						(
							[0] => 198
							[1] => 204
						)

				)
			**/
			
			foreach ($stateParameters->primaryKeys as $key)
				if ($this->invoicesModel->get_invoice_status($key) != 'In pregatire')
				{
				  $errorMessage = new \GroceryCrud\Core\Error\ErrorMessage();
				  return $errorMessage->setMessage("Facturile emise nu se pot sterge sau edita!\n");
				}
				
			return $stateParameters;
		});

	
		$crud->callbackAddForm(function ($data) {
			// At this example we assume that 
			// the reference id always starts with 0098_  
			// and hence we are adding it as a default  
			// value instead of empty

			$data['invoice_date'] = date("d/m/Y");
			$data['invoice_due_date'] = date('d/m/Y', strtotime(' +'. $this->invoicesModel->get_customer_due_days(1).' day'));
			$data['supplier_id'] = 1;
			$data['invoice_no'] = 'AUTO'; //$this->invoicesModel->get_supplier_invoiceNo($data['supplier_id']);
			$data['customer_id'] = 1; //536;
			$data['contract_id'] = 1; //536;
			//$data['zone_id'] = 1; //536;
			return $data;
		});

		$crud->callbackAfterInsert(function ($stateParameters) {
			/* $stateParameters will be an object with the below structure:
			 * (object)[
			 *      'data' => [ 
			 *            // Your inserted data
			 *            'customer_fist_name' => 'John',
			 *            'customer_last_name' => 'Smith',
			 *            ... 
			 *       ],
			 *      'insertId' => '123456'
			 * ]
			 */

			// Your custom code goes here. 
			
			log_message("info",print_r($stateParameters,true));		
			$this->invoicesModel->populateInvoice($stateParameters->insertId,$stateParameters->data['customer_id'],$stateParameters->data['zone_id'],$stateParameters->data['contract_id'],$stateParameters->data['storno_no']);
			
			return $stateParameters;
		});
			
		$output = $crud->render();
		$state = $crud->getState();
		$state_info = $crud->getStateInfo();
		if($state == 'edit')
		{
			$primary_key = $state_info->primary_key;
			//Do your awesome coding here. 
		}
		
		/*ob_start();
		var_dump($output);
		log_message('info', ob_get_clean());
		*/
		
		return $this->_invoicesOutput($output);
	}
	

	public function json_get_supplier_data()
	{
		$request = $this->request;
		$supplierId = $request->getVar('supplierId');
		$services = $this->invoicesModel->get_supplier_services($supplierId);
		$jdata['services']= $services;
		
		header('Content-Type: application/json');
		echo json_encode( $jdata );
	}
	
	public function json_get_customer_data()
	{
		$request = $this->request;
		$customerId = $request->getVar('customerId');
		$duedays = $this->invoicesModel->get_customer_due_days($customerId);
		$zones = $this->invoicesModel->get_customer_zones($customerId);
		$contracts = $this->invoicesModel->get_customer_contracts($customerId);
		$jdata['duedays']= $duedays;
		$jdata['zones']= $zones;
		$jdata['contracts']= $contracts;
		
		header('Content-Type: application/json');
		echo json_encode( $jdata );
	}
	
	public function json_get_zones()
	{
		$request = $this->request;
		$customerId = $request->getVar('customerId');
		$duedays = $this->invoicesModel->get_customer_due_days($customerId);
		
		header('Content-Type: application/json');
		echo json_encode( $duedays );
	}
	
	public function json_get_invoice_no()
	{
		$request = $this->request;
		$supplierId = $request->getVar('supplierId');
		$invoiceNo = $this->invoicesModel->get_supplier_invoiceNo($supplierId);
		
		header('Content-Type: application/json');
		echo json_encode( $invoiceNo );
	}
	
	public function json_home_data()
	{
		//https://ebs.cloudromania.ro/index.php/Invoices/json_home_data?homeReport=view_raport_released_invoices
		$request = $this->request;
		$homeReport = $request->getVar('homeReport');
		$reportData = $this->invoicesModel->get_home_report($homeReport);
		
		header('Content-Type: application/json');
		echo json_encode( $reportData );
	}
	

}