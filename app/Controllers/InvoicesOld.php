<?php 

namespace App\Controllers;

require_once('tools.php');
include(APPPATH . 'Libraries/GroceryCrudEnterprise/autoload.php');
use CodeIgniter\Database\Query;

use GroceryCrud\Core\GroceryCrud;

class InvoicesOld extends BaseController
{
	
	private $data = [
        'title'   => 'EBS',
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
		$this->data['title'] = "Facturi ";

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
		$crud->unsetAddFields(['invoice_calculated_total','invoice_calculated_ea_quantity','invoice_calculated_ea_unit_price','invoice_calculated_distributor_id','invoice_status','timestamp']);
		$crud->unsetEditFields(['invoice_calculated_total','invoice_calculated_ea_quantity','invoice_calculated_ea_unit_price','invoice_calculated_distributor_id','timestamp','storno_no']);

		$crud->columns(['supplier_id','customer_id','contract_id','zone_id','invoice_no','invoice_date','invoice_due_date','invoice_calculated_total','invoice_calculated_ea_quantity','invoice_status']);
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
		$crud->defaultOrdering('invoice_date','desc');
		
		
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
	
	public function emiteFactura($invoiceId = NULL)
	{
		
		
		$jreq = ($invoiceId == NULL);
				
		if ($jreq)
		{
			$request = $this->request;
			$invoiceId = $request->getVar('invoiceId');
		}
				
		if ($invoiceId === NULL or $this->invoicesModel->get_invoice_status($invoiceId) != 'In pregatire') 
			$jret = false;
		else
		{
			$this->invoicesModel->emiteFactura($invoiceId);
			$this->pdfInvoice->generatePDF($invoiceId,false,true);
			$jret = true;
		}
		
		if ($jreq)
			{
				header('Content-Type: application/json');
				echo json_encode( $jret );
				exit(0);
			}
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
	
    private function _invoicesOutput($output = null) {
        if (isset($output->isJSONResponse) && $output->isJSONResponse) {
			header('Content-Type: application/json; charset=utf-8');
			echo $output->output;
			exit;
        }

		$output->data =$this->data;
		
        return view('invoicesOld.php', (array)$output);
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

