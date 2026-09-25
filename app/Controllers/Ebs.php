<?php 

namespace App\Controllers;

require_once('tools.php');
include(APPPATH . 'Libraries/GroceryCrudEnterprise/autoload.php');

use GroceryCrud\Core\GroceryCrud;

class Ebs extends BaseController
{
	private $data = [
        'title'   => 'Ebs',
		'menu' => 'masterdata'];
	private $invoicesModel;
	
	function __construct() {	
		checkAuth();
	}
	
	public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {

        parent::initController($request, $response, $logger);

        // Load the model
        $this->invoicesModel = new \App\Models\InvoicesModel();
    }
	
	public function pods_management()
	{
		$this->data['title'] = "POD-uri";

	    $crud = $this->_getGroceryCrudEnterprise();

	    $crud->setSkin('bootstrap-v4');
	    $crud->setTable('pods');
	    $crud->setSubject('POD');
		$crud->setRelation('customer_id','customers','customer_name');
		$crud->setRelation('zone_id','zones','zone_name');
	    $crud->setDependentRelation('zone_id','customer_id','customer_id');
		
		
		$crud->displayAs('customer_id', 'Nume Client');
		$crud->displayAs('zone_id', 'Lot Client');
		$crud->displayAs('pod_type', 'Tip Pod');
		$crud->displayAs('city', 'Oras');
		$crud->displayAs('address', 'Adresa');
		$crud->displayAs('pod_no', 'POD');
		$crud->requiredFields(['city','address','pod_no','customer_id','zone_id']);
		
		$crud->callbackAddForm(function ($data) {
		// At this example we assume that 
		// the reference id always starts with 0098_  
		// and hence we are adding it as a default  
		// value instead of empty

		/*$data['customer_id'] = 388;
		$data['zone_id'] = 15;
		$data['city'] = "Default";*/
		
		
		return $data;
		});
		
		$output = $crud->render();
		
		return $this->_ebsOutput($output);
	}

	public function zones_management()
	{
		$this->data['title'] = "Loturi Clienti";
	    
		$crud = $this->_getGroceryCrudEnterprise();

	    
	    $crud->setTable('zones');
	    $crud->setSubject('Lot');
		$crud->setRelation('customer_id','customers','customer_name');
		$crud->displayAs('customer_id', 'Nume Client');
		$crud->displayAs('zone_name', 'Lot Client');
		$crud->requiredFields(['customer_id','zone_name']);
		$crud->setClone();
		
		$output = $crud->render();
		
		return $this->_ebsOutput($output);
	}


	public function distributors_management()
	{
		$this->data['title'] = "Distribuitori";
				
	    $crud = $this->_getGroceryCrudEnterprise();

	    
	    $crud->setTable('distributors');
	    $crud->setSubject('Distribuitor');
	    $crud->displayAs('distributor_name', 'Nume Distribuitor');
		$crud->displayAs('distributor_emergency_phone', 'Tel Urgente');
		$crud->displayAs('distributor_order_anre', 'Ordin ANRE');
		$crud->displayAs('distributor_anre_report_index', 'Index in Raport ANRE');
		$crud->requiredFields(['distributor_emergency_phone','distributor_order_anre']);
		$crud->readOnlyEditFields(['distributor_name']);
		//$crud->unsetEdit();
		$crud->unsetAdd();
        $crud->unsetDelete();
		
		$output = $crud->render();

		return $this->_ebsOutput($output);
	}
	
	public function suppliers_management()
	{
		$this->data['title'] = "Furnizori";
				
	    $crud = $this->_getGroceryCrudEnterprise();
		log_message('info',base_url() . '/logo');
	    
	    $crud->setTable('suppliers');
	    $crud->setSubject('Furnizor');
		$crud->setFieldUpload('supplier_logo', 'logo', base_url() . '/logo');
		
		$crud->displayAs('supplier_name', 'Nume Furnizor');
		$crud->displayAs('supplier_registration_number', 'Nr Reg Com');
		$crud->displayAs('supplier_vat_code', 'CUI');
		$crud->displayAs('supplier_city', 'Oras');
		$crud->displayAs('supplier_address', 'Adresa');
		$crud->displayAs('supplier_bank', 'Banca');
		$crud->displayAs('supplier_bank_account', 'IBAN');
		$crud->displayAs('supplier_bank2', 'Banca2');
		$crud->displayAs('supplier_bank_account2', 'IBAN2');
		
		$crud->displayAs('supplier_license', 'Nr/Data Licenta');
		
		$crud->displayAs('supplier_phone', 'Telefon');
		$crud->displayAs('supplier_fax', 'Fax');
		$crud->displayAs('supplier_email', 'Email');
		$crud->displayAs('supplier_program', 'Program');
		$crud->displayAs('supplier_invoice_series', 'Serie Factura');
		$crud->displayAs('supplier_invoice_no', 'Nr Factura');
		$crud->displayAs('supplier_logo', 'Logo');
		
		$crud->requiredFields([
			'supplier_name',
			'supplier_registration_number',
			'supplier_vat_code',
			'supplier_city',
			'supplier_address', 
			'supplier_bank', 
			'supplier_bank_account',
			'supplier_license',
			'supplier_phone',
			'supplier_fax', 
			'supplier_email', 
			'supplier_program', 
			'supplier_invoice_series', 
			'supplier_invoice_no'
		]);

	    $output = $crud->render();

		return $this->_ebsOutput($output);
	}
	
	public function consumptions_management()
	{
		$this->data['menu']='consumuri';
		$this->data['title'] = "Editare Consumuri";
	    
		$crud = $this->_getGroceryCrudEnterprise();

	    
	    $crud->setTable('consumptions');
	    $crud->setSubject('Consum');
		
		$crud->setRelation('invoice_id','invoices','invoice_no');
		$crud->displayAs('invoice_id', 'Numar Factura');
		
		$crud->displayAs('distributor_name','Distribuitor');
		$crud->displayAs('supplier_name','Furnizor');
		$crud->displayAs('customer_name','Client');
		$crud->displayAs('customer_code','Cod Client');
		$crud->displayAs('contract_number','Nr Contract');
		$crud->displayAs('consumption_location_id','Id loc consum');
		$crud->displayAs('pod','POD');
		$crud->displayAs('voltage_level_delimitation','Nivel Tensiune Delim');
		$crud->displayAs('voltage_level_measurment','Nivel Tensiune Masurat');
		$crud->displayAs('invoice_start_date','Data Factura Start');
		$crud->displayAs('invoice_end_date','Data Factura Sfarsit');
		$crud->displayAs('reading_start_date','Citire Data Start');
		$crud->displayAs('reading_end_date','Citire Dara Sfarsit');
		$crud->displayAs('device_serial_number','Serie Aparat Masura');
		$crud->displayAs('energy_type','Tip Energie');
		$crud->displayAs('index_old','Index vechi');
		$crud->displayAs('index_new','Index Nou');
		$crud->displayAs('total_consumption_ae','Consum Energie Activa');
		$crud->displayAs('total_consumption_re','Consum Energie Reactiva');
		$crud->displayAs('total_consumption_re_3x','Consum Energie Reactiva x3');
		$crud->displayAs('total_consumption_mu','UM');
		$crud->displayAs('source','Sursa');
		$crud->setClone();
		
		$crud->requiredFields([
		'distributor_name',
		'supplier_name',
		'customer_name',
		'customer_code',
		'pod',
		'voltage_level_delimitation',
		'voltage_level_measurment',
		'invoice_start_date',
		'invoice_end_date',
		'reading_start_date',
		'reading_end_date',
		'energy_type',
		'index_old',
		'index_new',
		'total_consumption_ae',
		'total_consumption_re',
		'total_consumption_re_3x',
		'total_consumption_mu'
		]);
		
		//$crud->readOnlyEditFields(['invoice_id']);
		$crud->unsetAddFields(['invoice_id','timestamp']);
		$crud->unsetEditFields(['invoice_id','timestamp']);
				
		$crud->callbackEditForm(function ($data) {
			// At this example we assume that 
			// the reference id always starts with 0098_  
			// and hence we are adding it as a default  
			// value instead of empty

			$data['source'] = 'manual';			
			return $data;
		});

		$crud->callbackAddForm(function ($data) {
			// At this example we assume that 
			// the reference id always starts with 0098_  
			// and hence we are adding it as a default  
			// value instead of empty

			$data['source'] = 'manual';			
			return $data;
		});
		
		$crud->callbackBeforeUpdate(function ($stateParameters) {
		  // Custom error messages are only available on Grocery CRUD Enterprise
				
			$consumptionStatus = $this->invoicesModel->get_consumption_invoiced_status($stateParameters->primaryKeyValue);
			if ($consumptionStatus)
			{
				$errorMessage = new \GroceryCrud\Core\Error\ErrorMessage();
				return $errorMessage->setMessage("Consumurile facturate nu se pot sterge sau edita!\n");
			}
					
		   return $stateParameters;
		});
		
		$crud->callbackBeforeDelete(function ($stateParameters) {
		  // Custom error messages are only available on Grocery CRUD Enterprise
			
			$consumptionStatus = $this->invoicesModel->get_consumption_invoiced_status($stateParameters->primaryKeyValue);
			if ($consumptionStatus)
			{
				$errorMessage = new \GroceryCrud\Core\Error\ErrorMessage();
				return $errorMessage->setMessage("Consumurile facturate nu se pot sterge sau edita!\n");
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
				if ($this->invoicesModel->get_consumption_invoiced_status($key))
				{
				  $errorMessage = new \GroceryCrud\Core\Error\ErrorMessage();
				  return $errorMessage->setMessage("Consumurile facturate nu se pot sterge sau edita!\n");
				}
				
			return $stateParameters;
		});
		  

		
	    $output = $crud->render();

		return $this->_ebsOutput($output);
	}
	
	public function contracts_management()
	{
		$this->data['title'] = "Contracte";
	    
		$crud = $this->_getGroceryCrudEnterprise();
	    
	    $crud->setTable('contracts');
		$crud->setRelation('supplier_id','suppliers','supplier_name');
		$crud->setRelation('customer_id','customers','customer_name');
		$crud->setDependentRelation('customer_id','supplier_id','supplier_id');
		//$crud->setRelationNtoN('loturi','contracts_zones','zones','contract_id','zone_id','zone_name'/*,null,['z.customer_id'=>427]*/);
	    //$crud->setDependentRelation('zone_id','customer_id','customer_id');
				
		$crud->setSubject('Contract');
		$crud->requiredFields([
		'supplier_id',
		'customer_id',
		'contract_number',
//		'contract_start_date',
		'contract_component_type'
		]);
		$crud->unsetColumns(['contract_calculated_numberdate']);
		$crud->unsetAddFields(['contract_calculated_numberdate']);
		$crud->unsetEditFields(['contract_calculated_numberdate']);
		
		$crud->displayAs('supplier_id','Furnizor');
		$crud->displayAs('customer_id','Client');
		$crud->displayAs('contract_number','Numar Contract');
		$crud->displayAs('contract_date','Data Start Contract');
		$crud->displayAs('contract_stop','Data Inchidere Contract');
		
		//$crud->displayAs('contract_start_date','Data Start');
		//$crud->displayAs('contract_end_date','Data Sfarsit');
		$crud->displayAs('contract_component_type','Tip Componenta');

		/*$crud->callbackAddForm(function ($data) {
			// At this example we assume that 
			// the reference id always starts with 0098_  
			// and hence we are adding it as a default  
			// value instead of empty

			$data['supplier_id'] = 1;
			//$data['customer_id'] = 1;
			
			return $data;
		});*/
		
	    $output = $crud->render();

		return $this->_ebsOutput($output);
	}
	
	public function services_management(){
		$this->data['title'] = "Servicii";
	    
		$crud = $this->_getGroceryCrudEnterprise();
	    
	    $crud->setTable('services');
		$crud->setRelation('supplier_id','suppliers','supplier_name');
		$crud->setRelation('service_type_id','service_types','service_type');
		
	    $crud->setSubject('Serviciu');
		$crud->requiredFields([
		'supplier_id',
		'service_name',
		'energy_type',
		'voltage_level',
		'service_pod_type',
		'service_print_order',
		'service_code',
		'service_type_id'
		]);
		
		$crud->displayAs('supplier_id','Furnizor');
		$crud->displayAs('service_name','Nume Serviciu');
		$crud->displayAs('energy_type','Tip Energie');
		$crud->displayAs('voltage_level','Nivel Tensiune');
		$crud->displayAs('service_pod_type','Tip POD');
		$crud->displayAs('service_measurment_unit','UM');
		$crud->displayAs('service_code','Cod Serviciu');
		$crud->displayAs('service_print_order','Ordinea de tiparire');
		$crud->displayAs('service_type_id','Tip Serviciu');
		$crud->defaultOrdering('service_print_order','asc');
		$crud->setClone();
		
		$crud->defaultOrdering('service_print_order', 'asc');
		
	    $output = $crud->render();

		return $this->_ebsOutput($output);
	}
	
	public function service_rates_management()
	{
		$this->data['title'] = "Preturi";
	    
		$crud = $this->_getGroceryCrudEnterprise();

	    
	    $crud->setTable('service_rates');
	    $crud->setSubject('Pret');
		
		$crud->setRelation('service_id','services','service_name');
		$crud->setRelation('distributor_id','distributors','distributor_name'/*,['customer_status ? ' => 'Active']*/);
		$crud->setRelation('customer_id','customers','customer_name'/*,['customer_status ? ' => 'Active']*/);
		$crud->setRelation('supplier_id','suppliers','supplier_name');
		$crud->setRelation('contract_id','contracts','{contract_number} / {contract_date}'/*, ['supplier_id' => '1']*/);
		$crud->setRelation('zone_id','zones','zone_name');
		$crud->setRelation('pod_id','pods','pod_no');
		
		$crud->setDependentRelation('customer_id','supplier_id','supplier_id');
		//$crud->setDependentRelation('contract_id','customer_id','customer_id');
		$crud->setDependentRelation('zone_id','customer_id','customer_id');
		$crud->setDependentRelation('pod_id','zone_id','zone_id');
		
		$crud->displayAs('supplier_id', 'Furnizor');
		$crud->displayAs('service_id', 'Serviciu');
		$crud->displayAs('distributor_id', 'Distribuitor');
		$crud->displayAs('customer_id', 'Client');
		$crud->displayAs('contract_id', 'Contract');
		$crud->displayAs('zone_id', 'Lot');
		$crud->displayAs('pod_id', 'POD');
		$crud->displayAs('service_value', 'Pret');
		$crud->displayAs('start_date', 'Data Start');
		$crud->defaultOrdering([
		   'customer_id' => 'ASC',
		   'contract_id' => 'ASC',
		   'zone_id' => 'ASC',
		   'pod_id' => 'ASC',
		   'start_date' => 'DESC'
			]);
		
		$crud->setClone();

		$crud->requiredFields([
		'service_id',
		'supplier_id',
		'service_value',
		'start_date'
		]);
		
		
		$crud->callbackAddForm(function ($data) {
		// At this example we assume that 
		// the reference id always starts with 0098_  
		// and hence we are adding it as a default  
		// value instead of empty

		$data['supplier_id'] = 1;
		$data['customer_id'] = 1; //536;
		$data['contract_id'] = 1; //536;
		$data['zone_id'] = 1; //536;
		$data['pod_id'] = 1; //536;
		return $data;
		});
		

		$crud->callbackBeforeUpdate(function ($stateParameters) {
			// Your code here
			
			if(empty($stateParameters->data['customer_id']))
				$stateParameters->data['contract_id'] = NULL;
			
			return $stateParameters;
		});
		
		$crud->callbackBeforeInsert(function ($stateParameters) {
			// Your code here
			
			if(empty($stateParameters->data['customer_id']))
				$stateParameters->data['contract_id'] = NULL;
			
			return $stateParameters;
		});
		
		$output = $crud->render();
		
		return $this->_ebsOutput($output);
	}

	public function customers_management()
	{
		$this->data['title'] = "Clienti";
	    
		$crud = $this->_getGroceryCrudEnterprise();

	    
	    $crud->setTable('customers');
		$crud->setRelation('supplier_id','suppliers','supplier_name');
		
		//filled on import data. used only on add pod search
		$crud->fieldType('customer_alias', 'invisible');
		
	    $crud->setSubject('Client');
		$crud->displayAs('supplier_id', 'Furnizor');
		$crud->displayAs('customer_name', 'Nume Client');
		$crud->displayAs('customer_registration_number', 'Nr Reg Com');
		$crud->displayAs('customer_vat_code', 'CUI');
		$crud->displayAs('customer_county', 'Judet');
		$crud->displayAs('customer_city', 'Oras');
		$crud->displayAs('customer_address', 'Adresa');
		$crud->displayAs('customer_bank', 'Banca');
		$crud->displayAs('customer_bank_account', 'IBAN');
		$crud->displayAs('customer_contract', 'Nr/Data Contract');
		$crud->displayAs('customer_invoice_due_days', 'Zile scadenta');
		$crud->displayAs('customer_anre_band', 'Banda Consum ANRE');
		
		$crud->requiredFields(['supplier_id','customer_name','customer_registration_number','customer_vat_code','customer_city','customer_address','customer_bank','customer_bank_account','customer_invoice_due_days']);
		$crud->defaultOrdering('customer_name', 'asc');

		
	    $output = $crud->render();

		return $this->_ebsOutput($output);
	}
		
	public function view_consumed_services()
	{
		$this->data['menu']='consumuri';
		$this->data['title'] = "Raport Cantitati";
				
	    $crud = $this->_getGroceryCrudEnterprise();

		//$crud->setCsrfTokenName(csrf_token());
        //$crud->setCsrfTokenValue(csrf_hash());
		
	    $crud->setTable('view_consumptions');
	    $crud->setSubject('Service');
		$crud->setPrimaryKey('id','view_consumptions');
		$crud->displayAs('supplier_name', 'Furnizor');
		$crud->displayAs('distributor_name', 'Distribuitor');
		$crud->displayAs('customer_name', 'Client');
		$crud->displayAs('invoice_no', 'Nr Factura');
		$crud->displayAs('invoice_start_date', 'Data Facturare');
		$crud->displayAs('city', 'Oras');
		$crud->displayAs('address', 'Adresa');
		$crud->displayAs('pod', 'POD');
		$crud->displayAs('energy_type', 'Tip Energie');
		$crud->displayAs('voltage_level', 'Nivel Tensiune');
		$crud->displayAs('total_consumption', 'Consum total');
		$crud->displayAs('measurement_unit', 'UM');
		
		
		$crud->unsetColumns(['id','supplier_id','distributor_id','customer_id'],'view_consumptions');
        $crud->unsetAdd();
        $crud->unsetDelete();
		$crud->unsetEdit();
		$output = $crud->render();
				
		return $this->_ebsOutput($output);
	}

	public function view_invoiced_services()
	{
		$this->data['title'] = "Servicii Nefacturate";
				
	    $crud = $this->_getGroceryCrudEnterprise();

	
	    $crud->setTable('view_to_be_invoiced');
		$crud->setRelation('contract_id','contracts','{contract_number}/{contract_date}');
	    $crud->setSubject('Serviciu');
		$crud->setPrimaryKey('id','view_to_be_invoiced');
		$crud->columns(['supplier_name','customer_name','contract_id','zone_name','pod_no','service_name','measurement_unit','total_consumption','unit_price','invoice_start_date','value','vat','service_code']);
		$crud->unsetColumns(['id','No','distributor_id','distributor_name','customer_id','zone_id','service_id','service_rate_id','supplier_id','vat_per','service_print_order'],'view_to_be_invoiced');
        $crud->unsetAdd();
        $crud->unsetDelete();
		$crud->unsetEdit();

		$crud->displayAs('No', 'Nr.');
		$crud->displayAs('contract_id', 'Contract');
		$crud->displayAs('supplier_name', 'Furnizor');
		$crud->displayAs('distributor_name', 'Distribuitor');
		$crud->displayAs('zone_name', 'Lot');
		$crud->displayAs('pod_no', 'POD');
		$crud->displayAs('service_name', 'Serviciu');
		$crud->displayAs('customer_name', 'Client');
		$crud->displayAs('measurement_unit', 'UM');
		$crud->displayAs('total_consumption', 'Cantitate');
		$crud->displayAs('unit_price', 'Pret Unitar');
		$crud->displayAs('invoice_start_date', 'Data facturare start');
		$crud->displayAs('value', 'Valoare');
		$crud->displayAs('vat', 'TVA');
		
		
		//$crud->fieldType('service_name', 'text');
		//$crud->callbackColumn('service_name', function ($value, $row) {
		//	if (!empty($value)) {
		//		return wordwrap($value, 46, "<br>", true);
		//	} 
		//});
		
		$this->data['menu']='facturi';
		$output = $crud->render();
				
		return $this->_ebsOutput($output);
	}

	public function invoiced_items()
	{
		$request = $this->request;
		$invoiceId = $request->getVar('invoiceId');
		
		$pdfInvoiceModel = new \App\Models\pdfInvoiceModel($invoiceId);
		
		$invoiceData = $pdfInvoiceModel->get_invoice_data();
		$customerData = $pdfInvoiceModel->get_customer_data();
		

		$this->data['title'] = 'Invoice No. '.$invoiceData['invoice_no'].' / '.$invoiceData['invoice_date'].' / '.$customerData['customer_name'];
		$this->data['title_link'] = base_url().'/index.php/PdfInvoice?invoiceId='.$invoiceId;
	    
		$crud = $this->_getGroceryCrudEnterprise();

	    
	    $crud->setTable('invoiced_items');
	    $crud->setSubject('Item');
		$crud->setRelation('invoice_id','invoices','{invoice_id} - {invoice_no}');
		$crud->setRelation('service_rate_id','service_rates','{service_rate_id} - {service_value} / {start_date}');
		$crud->setRelation('distributor_id','distributors','distributor_name');
		//$crud->setRelation('invoiced_pod_no','pods','pod_no');
	    $crud->displayAs('invoice_id', 'Invoice No');
		$crud->where(['invoice_id' => $invoiceId]);
		$crud->unsetColumns(['invoice_id','service_rate_id','distributor_id']);
		
		$crud->readOnlyEditFields(['invoice_id', 'service_rate_id','distributor_id']);
		$crud->readOnlyAddFields(['invoice_id']);
		
		$crud->displayAs('invoice_id', 'Id - Nr');
		$crud->displayAs('invoiced_item_no', 'Nr.Crt.');
		$crud->displayAs('distributor_id', 'Distribuitor');
		$crud->displayAs('service_rate_id', 'Id - Pret / Data');
		$crud->displayAs('invoiced_pod_no', 'POD');
		$crud->displayAs('invoiced_item_name', 'Serviciu');
		$crud->displayAs('invoiced_item_measurement_unit', 'UM');
		$crud->displayAs('invoiced_item_quantity', 'Cantitate');
		$crud->displayAs('invoiced_item_unit_price', 'Pret Unitar');
		$crud->displayAs('invoiced_item_value', 'Valoare');
		$crud->displayAs('invoiced_item_vat', 'TVA');
		
		if($invoiceData['invoice_status'] != 'In pregatire')
		{
			$crud->unsetAdd();
			$crud->unsetDelete();
			$crud->unsetEdit();
		}
		else
			$crud->setClone();
		
		$crud->callbackAddForm(function ($data) {

		$request = $this->request;
		$invoiceId = $request->getVar('invoiceId');
		$data['invoice_id'] = $invoiceId;

		return $data;
	});
	
		$output = $crud->render();
		
		return $this->_ebsOutput($output);
	}
	
	public function raport()
	{
		$this->data['title'] = "Raport";		
	    $crud = $this->_getGroceryCrudEnterprise();
		
		$request = $this->request;
		$view = $request->getVar('view');
		
		if($view !== null)
		{
			$this->data['title'] = ucfirst(str_replace('view_raport_','', $view));
			
			$crud->setTable($view);
			$crud->setSubject('Raport');
			$crud->setPrimaryKey('id',$view);

			$crud->unsetAdd();
			$crud->unsetDelete();
			$crud->unsetEdit();
			
/*			$crud->where([
				'Data_factura >= ?' => '2021-08-01',
				'Data_factura <= ?' => '2021-08-15',
			]);*/

			$output = $crud->render();
		}
		
		$this->data['menu']='rapoarte';
		return $this->_ebsOutput($output);
	}

	public function working_days() {
			    		
		$this->data['title'] = "Zile lucratoare";
				
	    $crud = $this->_getGroceryCrudEnterprise();

	    $crud->setTable('working_days');
	    $crud->setSubject('Zile lucratoare');
		$crud->displayAs('year', 'An');
		$crud->displayAs('month', 'Luna');
		$crud->displayAs('working_days', 'Zile Lucratoare');
	
		$crud->requiredFields([
		'year',
		'month',
		'working_days',
		]);

		$crud->setClone();
		$output = $crud->render();
				
		return $this->_ebsOutput($output);
	}
	
	public function settings_management() {
			    		
		$this->data['title'] = "Editare Setari";
				
	    $crud = $this->_getGroceryCrudEnterprise();

	    $crud->setTable('settings');
	    $crud->setSubject('Variabila');
		$crud->columns(['setting_description', 'setting_value']);
		$crud->readOnlyEditFields(['setting_variable','setting_description','setting_type']);
		$crud->setTexteditor(['setting_value']);		
		
		$crud->unsetAdd();
        $crud->unsetDelete();
	
		$output = $crud->render();
				
		return $this->_ebsOutput($output);
	}
	
	public function naming_management() {
			    		
		$this->data['title'] = "Editare Denumiri Furnizori";
		
	    $crud = $this->_getGroceryCrudEnterprise();

	    $crud->setTable('suppliers_naming');
	    $crud->setSubject('Denumire Furnizor');
		
		$crud->setRelation('supplier_id','suppliers','supplier_name');
		$crud->setRelation('distributor_id','distributors','distributor_name');
		
		$crud->displayAs('supplier_id', 'Furnizor');
		$crud->displayAs('distributor_id', 'Distribuitor');
		$crud->displayAs('pv_name', 'Denumire Furnizor/Proces Verbal');
		$crud->displayAs('rep_name', 'Denumire Furnizor/Raport');
		
		$output = $crud->render();
				
		return $this->_ebsOutput($output);
	}
	
	public function users() {
		
		$this->data['title'] = "Editare Utilizatori";
		
	    $crud = $this->_getGroceryCrudEnterprise();

	    $crud->setTable('users');
	    $crud->setSubject('Utilizatori');
				
		$crud->displayAs('user_id', 'ID Utilizator');
		$crud->displayAs('user', 'Utilizator');
		$crud->displayAs('user_name', 'Nume Prenume');
		$crud->displayAs('user_role', 'Rol');
		$crud->displayAs('password', 'Parola');
		
		$crud->columns(['user', 'user_name','user_role']);
		
		$crud->callbackBeforeUpdate(function ($stateParameters) {
		  // Custom error messages are only available on Grocery CRUD Enterprise
			if(empty($stateParameters->data['password']))
				unset($stateParameters->data['password']);
			else
				$stateParameters->data['password'] = md5($stateParameters->data['password']);
			
			return $stateParameters;
		});
		
		$output = $crud->render();
				
		return $this->_ebsOutput($output);
	}

    private function _ebsOutput($output = null) {
        if (isset($output->isJSONResponse) && $output->isJSONResponse) {
			header('Content-Type: application/json; charset=utf-8');
			echo $output->output;
			exit;
        }

		$output->data =$this->data;
		
        return view('ebs.php', (array)$output);
    }

    private function _getDbData() {
        $db = (new \Config\Database())->default;
        return [
            'adapter' => [
                'driver' => 'Pdo_Mysql',
                'host'     => $db['hostname'],
                'database' => $db['database'],
                'username' => $db['username'],
                'password' => $db['password'],
                'charset' => 'utf8'
            ]
        ];
    }
	
    private function _getGroceryCrudEnterprise($bootstrap = true, $jquery = true) {
        $db = $this->_getDbData();
        $config = (new \Config\GroceryCrudEnterprise())->getDefaultConfig();

        $groceryCrud = new GroceryCrud($config, $db);
		$groceryCrud->setLanguage('Romanian');
        return $groceryCrud;
    }
}

