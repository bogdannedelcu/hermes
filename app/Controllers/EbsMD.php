<?php 

namespace App\Controllers;

use CodeIgniter\Database\Query;
use DateTime;
use DateInterval;

require_once('tools.php');
require_once('GridTools.php');
require_once(APPPATH . 'Libraries/ebs/TimePeriodFilter.php');
require_once(APPPATH . 'Libraries/ebs/DistributorFilter.php');
require_once(APPPATH . 'Libraries/ebs/ActiveFilter.php');

class EbsMD extends BaseController
{
	use \GridTools;
	private $data = [
        'title'   => 'Ebs',
		'div-card' => 'card-master',
		'menu' => 'masterdata'];
	private $masterDataModel;
	
	function __construct() {	
		checkAuth();
	}
	
	public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {

        parent::initController($request, $response, $logger);

        // Load the model
        $this->masterDataModel = new \App\Models\MasterDataModel();
    }
	
	
	public function services_management(){
		
		$transport = $this->createGridTransport('services');

		$schema = $this->createGridSchema(['service_id','supplier_id','service_name','energy_type','voltage_level','service_pod_type','service_print_order',
											'service_code','service_measurment_unit','service_type_id','service_status','supplier_name','service_type'],
										  ['number','number','string','string','string','string','number',
										  'string','string','number','string','string','string'],
										   /*['supplier_id' => $suppliers[0]['value']]*/);
				
		$dataSource = $this->createDataSource($transport,$schema);
		
		
		$column1 = $this->createGridColumn('supplier_name','Furnizor',150);				
		$column2 = $this->createGridColumn('service_name','Serviciu',150);
		$column3 = $this->createGridColumn('energy_type','Tip Energie',100);
		$column4 = $this->createGridColumn('voltage_level','Nivel Tensiune',100);
		$column5 = $this->createGridColumn('service_pod_type','Tip POD',100);
		$column6 = $this->createGridColumn('service_print_order','Ordinea de tiparire',100);
		$column7 = $this->createGridColumn('service_code','Cod Serviciu',100);
		$column8 = $this->createGridColumn('service_measurment_unit','UM',100);
		$column9 = $this->createGridColumn('service_type','Tip Serviciu',100);
		$column10 = $this->createGridColumn('service_status','Status Serviciu',100);
		
		
		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true);/*
			 ->edit('function(e){	if(e.model.isNew() && selYear_start_date > -1 && selMonth_start_date > -1)
													{
														let y = selYear_start_date;
														let m = selMonth_start_date  + 1;
														let d = new Date(y, m, 0);
														e.model.set("start_date", y+"-"+m+"-1");
													}
															
								}');*/

		$this->addCustomCommand('editS','k-icon k-i-pencil','serviceDialog');
		$this->addCustomCommand('status','k-icon k-i-minus-outline','setServiceStatus');
		$this->addCustomOperation('addS','Adaugă','k-icon k-i-plus');
		$this->setGridEditable('popup', false, false, true, false, false);

		$AFilter = new \ActiveFilter('service_status','services');
		
		$this->data['active_filter'] = $AFilter->render();		

		$this->data['title'] = "Servicii";
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		return view('ebsMD',$data);
	}
	
	public function service_prices()
	{    
	
		$transport = $this->createGridTransport('service_rates');

		$schema = $this->createGridSchema(['service_rate_id','supplier_id','service_id','distributor_id','customer_id','contract_id','zone_id',
										   'pod_id','service_value','start_date','service_code',
										   'deletable','distributor_name','pod_no','zone_name','customer_name','supplier_name','service_name','contract_calculated_numberdate'],
										  ['number','string','string','string','string','string','string',
										   'string','number','date','string',
										   'number','string','string','string','string','string','string','string'],
										   /*['supplier_id' => $suppliers[0]]*/);
				
		$this->gridFields['distributor_id']->nullable(true);
		$this->gridFields['distributor_id']->validation(array('required' => false));
		$this->gridFields['customer_id']->nullable(true);
		$this->gridFields['customer_id']->validation(array('required' => true));		
		$this->gridFields['contract_id']->nullable(true);
		$this->gridFields['contract_id']->validation(array('required' => true));		
		$this->gridFields['zone_id']->nullable(true);
		$this->gridFields['zone_id']->validation(array('required' => false));
		$this->gridFields['pod_id']->nullable(true);
		$this->gridFields['pod_id']->validation(array('required' => false));
		
		$dataSource = $this->createDataSource($transport,$schema);		
		
		$dS = $this->masterDataModel->get_service_rates_min_max_years('prices');
		$filterItem1 = $this->createNowFilter('start_date',isset($_SESSION['select-customer']),$dS['maxY']);
		$filterItem2 = $this->createFilter('service_type_id','in',[3]);
		$filterItem = $this->combineFilters($filterItem1, $filterItem2, 'and');

		$dataSource->addFilterItem($filterItem);
		if(isset($_SESSION['select-customer']))
		{
			$filterItemCustomer = $this->createFilter('customer_id','eq',[$_SESSION['select-customer']]);
			$dataSource->addFilterItem($filterItemCustomer);
		}
		
		$column1 = $this->createGridColumn('supplier_name','Furnizor',150);
				
		$column2 = $this->createGridColumn('distributor_name','Distribuitor',150);
		$column2->hidden(true);
		
		$column3 = $this->createGridColumn('service_name','Serviciu',150);
		
		$column4 = $this->createGridColumn('customer_name','Client',200);
		
		$column5 = $this->createGridColumn('contract_calculated_numberdate','Contract',100);
		
		$column6 = $this->createGridColumn('zone_name','Lot',150);
		
		$column7 = $this->createGridColumn('pod_no','Pod',150);

		$column8 = $this->createGridColumn('service_value','Pret [Lei]',100);
		$column8->template("#=kendo.toString(data.service_value)#");
		
		$column9 = $this->createGridColumn('start_date','Data Start',100);	
		
		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true)
			 ->edit('function(e){	if(e.model.isNew() && selYear_start_date > -1 && selMonth_start_date > -1)
													{
														let y = selYear_start_date;
														let m = selMonth_start_date  + 1;
														let d = new Date(y, m, 0);
														e.model.set("start_date", y+"-"+m+"-1");
													}							
								}');/*
			 ->dataBinding('function(e) { 
											
												 var gFilter = $("#grid").data("kendoGrid").options.dataSource.filter[0].filters[1].filters;
												  for(i=0;i<gFilter.length;i++)
													filters.push({ field: gFilter[i].field, operator: gFilter[i].operator, value: gFilter[i].value });			
											
											  }');*/
				 
		$this->addCustomCommand('editT','k-icon k-i-pencil','PreturiDialog');
		$this->addCustomOperation('addT','Adaugă','k-icon k-i-plus');
		$this->setGridEditable('inline', false, false, true, false, false);
				

		$this->data['title'] = "Preturi";
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		
		if(isset($_SESSION['select-customer'])) $selDate = null;
		else $selDate = "now";
		$SDFilter = new \TimePeriodFilter('start_date','service_rates','Data start',$dS['minY'],$dS['maxY'],$selDate);
		$SDFilter->setOption('priceList');
		
		$this->data['output'] = $this->grid->render();
		$this->data['start_date_filter'] = $SDFilter->render();
		
		$data['data'] = $this->data;
				
		return view('ebsMD',$data);
		
	}
	
	public function service_tariffs()
	{    	
		$transport = $this->createGridTransport('service_rates');

		$schema = $this->createGridSchema(['service_rate_id','supplier_id','service_id','distributor_id','customer_id','contract_id','zone_id',
										   'pod_id','service_value','start_date','service_code',
										   'deletable','distributor_name','pod_no','zone_name','customer_name','supplier_name','service_name','contract_calculated_numberdate'],
										  ['number','string','string','string','string','string','string',
										   'string','number','date','string',
										   'number','string','string','string','string','string','string','string'],
										   /*['supplier_id' => $suppliers[0]['value']]*/);
		
		$this->gridFields['distributor_id']->nullable(true);
		$this->gridFields['distributor_id']->validation(array('required' => false));
		$this->gridFields['pod_id']->nullable(true);
		$this->gridFields['pod_id']->validation(array('required' => false));
		$this->gridFields['customer_id']->nullable(true);
		$this->gridFields['customer_id']->validation(array('required' => false));
		$this->gridFields['contract_id']->nullable(true);
		$this->gridFields['contract_id']->validation(array('required' => false));
		$this->gridFields['zone_id']->nullable(true);
		$this->gridFields['zone_id']->validation(array('required' => false));
		
		$dataSource = $this->createDataSource($transport,$schema);
		
		$dS = $this->masterDataModel->get_service_rates_min_max_years('tariff');
		$filterItem1 = $this->createNowFilter('start_date',false,$dS['maxY']);
		$filterItem2 = $this->createFilter('service_type_id','not in',[3,4]);
		
		$filterItem = $this->combineFilters($filterItem1, $filterItem2, 'and');
		$dataSource->addFilterItem($filterItem);

		
		$column1 = $this->createGridColumn('supplier_name','Furnizor',150);
				
		$column2 = $this->createGridColumn('distributor_name','Distribuitor',150);
		$column2->template("#=data.distributor_name ?? 'General' #");
		
		$column3 = $this->createGridColumn('service_name','Serviciu',150);

		$column4 = $this->createGridColumn('customer_name','Client',150);
		$column4->hidden(true);
		
		$column5 = $this->createGridColumn('contract_calculated_numberdate','Contract',150);
		$column5->hidden(true);
		
		$column6 = $this->createGridColumn('zone_name','Lot',150);
		$column6->hidden(true);
		
		$column7 = $this->createGridColumn('pod_no','Pod',150);
		$column7->hidden(true);
		
		$column8 = $this->createGridColumn('service_value','Pret [Lei]',100);
		$column8->template("#=kendo.toString(data.service_value)#");
		
		$column9 = $this->createGridColumn('start_date','Data Start',100);	
		
		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true)
			 ->edit('function(e){	if(e.model.isNew() && selYear_start_date > -1 && selMonth_start_date > -1)
													{
														let y = selYear_start_date;
														let m = selMonth_start_date  + 1;
														let d = new Date(y, m, 0);
														e.model.set("start_date", y+"-"+m+"-1");
													}
															
								}');

		$this->addCustomCommand('editT','k-icon k-i-pencil','TarifeDialog');
		$this->addCustomOperation('addT','Adaugă','k-icon k-i-plus');
		$this->setGridEditable('inline', false, false, true, false, false);
		
				

		$this->data['title'] = "Tarife";
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		
		$SDFilter = new \TimePeriodFilter('start_date','service_rates','Data start',$dS['minY'],$dS['maxY']);
		$SDFilter->setOption('tariffList');
		
		$DFilter = new \DistributorFilter('distributor_id',true);
		
		$this->data['output'] = $this->grid->render();
		$this->data['start_date_filter'] = $SDFilter->render();
		$this->data['distributor_filter'] = $DFilter->render();
		
		$data['data'] = $this->data;
				
		return view('ebsMD',$data);
		
	}
	
	public function customers_management()
	{
		$transport = $this->createGridTransport('customers');

		$schema = $this->createGridSchema(['customer_id','supplier_id','customer_name','customer_registration_number','customer_vat_code','customer_city','customer_county','customer_address',
											'customer_bank','customer_bank_account','customer_invoice_due_days','customer_status','customer_anre_band','supplier_name','last_invoice'],
										  ['number','string','string','string','string','string','string','string',
										  'string','string','number','string','string','string','string'],
										   /*['supplier_id' => $suppliers[0]['value']]*/);
				
		$dataSource = $this->createDataSource($transport,$schema);
		$session = \Config\Services::session();
		if(isset($_SESSION['select-customer']))
		{
			$filterItem = $this->createFilter('customer_id','eq',[$_SESSION['select-customer']]);
			$dataSource->addFilterItem($filterItem);
		}
	
		$column1 = $this->createGridColumn('supplier_name','Furnizor',150);				
		$column2 = $this->createGridColumn('customer_name','Client',150);
		$column3 = $this->createGridColumn('customer_registration_number','Reg.Com.',100);
		$column4 = $this->createGridColumn('customer_vat_code','CUI',70);
		$column5 = $this->createGridColumn('customer_county','Judet',70);
		$column6 = $this->createGridColumn('customer_city','Oras',100);
		$column7 = $this->createGridColumn('customer_bank','Banca',100);
		$column8 = $this->createGridColumn('customer_invoice_due_days','Scadenta (zile)',50);
		$column9 = $this->createGridColumn('last_invoice','Ultima Factura',180);
		$column10 = $this->createGridColumn('customer_anre_band','Banda Consum',50);
		$column11 = $this->createGridColumn('customer_status','Status',50);

		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true);/*
			 ->edit('function(e){	if(e.model.isNew() && selYear_start_date > -1 && selMonth_start_date > -1)
													{
														let y = selYear_start_date;
														let m = selMonth_start_date  + 1;
														let d = new Date(y, m, 0);
														e.model.set("start_date", y+"-"+m+"-1");
													}
															
								}');*/

		$this->addCustomCommand('status','k-icon k-i-minus-outline','setCustomerStatus');
		$this->addCustomCommand('editC','k-icon k-i-pencil','CustomerDialog');
		$this->addCustomOperation('addC','Adaugă','k-icon k-i-plus');
		$this->setGridEditable('popup', false, false, true, false, false);

		$AFilter = new \ActiveFilter('customer_status','customers');
		
		$this->data['active_filter'] = $AFilter->render();		

		$this->data['title'] = "Clienti";
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		return view('ebsMD',$data);
	}
	
	public function contracts_management()
	{
		
		$transport = $this->createGridTransport('contracts');

		$schema = $this->createGridSchema(['contract_id','supplier_name','customer_name','contract_calculated_numberdate','contract_start_date','contract_stop_date','price_energy','contract_component_type',
											'price_component','customer_invoice_due_days','contract_status'],
										  ['number','string','string','string','date','date','number','string',
										  'number','number','string'],
										   /*['supplier_id' => $suppliers[0]['value']]*/);
				
		$dataSource = $this->createDataSource($transport,$schema);
		/*$filterItem1 = $this->createNowFilter('contract_start_date');
		$filterItem2 = $this->createNowFilter('contract_stop_date',true);
		$filterItem = $this->combineFilters($filterItem1,$filterItem2,'and');*/
		
		$dContract = $this->masterDataModel->get_min_max_years('contracts','contract_date');
		$dStop = $this->masterDataModel->get_min_max_years('contracts','contract_stop');
		
		$filterItem = $this->createNowFilter('contract_date',true);
		$dataSource->addFilterItem($filterItem);
		
		if(isset($_SESSION['select-customer'])) 
		{
			$selDate = null;
			$filterItemSD = $this->createNowFilter('contract_filter_stop_date',true);
		}
		else 
		{
			$selDate = "now";
			$filterItemSD = $this->createNowFilter('contract_filter_stop_date',isset($_SESSION['select-customer']),date('Y'));
		}
		
		$dataSource->addFilterItem($filterItemSD);
		
		if(isset($_SESSION['select-customer']))
		{
			$filterItemCustomer = $this->createFilter('customer_id','eq',[$_SESSION['select-customer']]);
			$dataSource->addFilterItem($filterItemCustomer);
		}
		
	
		$column1 = $this->createGridColumn('supplier_name','Furnizor',150);	
		$column2 = $this->createGridColumn('customer_name','Client',150);
		$column3 = $this->createGridColumn('contract_calculated_numberdate','Contract',100);
		$column5 = $this->createGridColumn('contract_start_date','Data Start',70);
		$column6 = $this->createGridColumn('contract_stop_date','Data Inchidere',70);
		$column7 = $this->createGridColumn('price_energy','Pret Energie',70);
		$column8 = $this->createGridColumn('contract_component_type','Tip Componenta',70);
		$column9 = $this->createGridColumn('price_component','Pret Componenta',70);
		$column10 = $this->createGridColumn('customer_invoice_due_days','Scadenta (zile)',50);
		$column11 = $this->createGridColumn('contract_status','Status',50);

		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true);/*
			 ->edit('function(e){	if(e.model.isNew() && selYear_start_date > -1 && selMonth_start_date > -1)
													{
														let y = selYear_start_date;
														let m = selMonth_start_date  + 1;
														let d = new Date(y, m, 0);
														e.model.set("start_date", y+"-"+m+"-1");
													}
															
								}');*/

		//neimplentat $this->addCustomCommand('status','k-icon k-i-minus-outline','setContractStatus');
		
		$this->addCustomCommand('editContract','k-icon k-i-pencil','ContractDialog');
		$this->addCustomOperation('addContract','Adaugă','k-icon k-i-plus');
		$this->setGridEditable('popup', false, false, true, false, false);

		$AFilter = new \ActiveFilter('contract_status','contracts');
		$this->data['active_filter'] = $AFilter->render();		
		
		$CDFilter = new \TimePeriodFilter('contract_date','contracts','Data contract',$dContract['minY'],$dContract['maxY'],null);
		$this->data['contract_date_filter'] = $CDFilter->render();

		
/*		$dStart = $this->masterDataModel->get_service_rates_min_max_years('prices');
		$SDFilter = new \TimePeriodFilter('contract_start_date','contracts','Data start',$dStart['minY'],$dStart['maxY']);
		$this->data['start_date_filter'] = $SDFilter->render();
*/
		$SDFilter = new \TimePeriodFilter('contract_filter_stop_date','contracts','Data stop',$dStop['minY'],$dStop['maxY'],$selDate);
		$this->data['contract_stop_date_filter'] = $SDFilter->render();


		$this->data['title'] = "Contracte";
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		return view('ebsMD',$data);	    
	}
	
	
	public function pods_management()
	{
		$transport = $this->createGridTransport('pods');

		$schema = $this->createGridSchema(['pod_id','supplier_id','customer_id','customer_name','pod_no','address','city','county','zone_name','supplier_name','customer_status'],
										  ['number','number','number','string','string','string','string','string','string','string','string'],
										   /*['supplier_id' => $suppliers[0]['value']]*/);
				
		$dataSource = $this->createDataSource($transport,$schema);
		$dataSource = $this->createDataSource($transport,$schema);
		$session = \Config\Services::session();
		if(isset($_SESSION['select-customer']))
		{
			$filterItem = $this->createFilter('customer_id','eq',[$_SESSION['select-customer']]);
			$dataSource->addFilterItem($filterItem);
		}
		
		$column1 = $this->createGridColumn('supplier_name','Furnizor',150);				
		$column2 = $this->createGridColumn('customer_name','Client',150);
		$column3 = $this->createGridColumn('pod_no','POD',150);
		$column6 = $this->createGridColumn('pod_type','Tip',100);
		$column6 = $this->createGridColumn('zone_name','Lot',100);
		$column4 = $this->createGridColumn('county','Judet',100);
		$column4 = $this->createGridColumn('city','Localitate',100);
		$column5 = $this->createGridColumn('address','Adresa',200);
		//$column7 = $this->createGridColumn('customer_status','Status Client',100);
		$column8 = $this->createGridColumn('pod_status','POD Status',100);

		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true);/*
			 ->edit('function(e){	if(e.model.isNew() && selYear_start_date > -1 && selMonth_start_date > -1)
													{
														let y = selYear_start_date;
														let m = selMonth_start_date  + 1;
														let d = new Date(y, m, 0);
														e.model.set("start_date", y+"-"+m+"-1");
													}
															
								}');*/

		$this->addCustomCommand('editP','k-icon k-i-pencil','podDialog');
		$this->addCustomOperation('addP','Adaugă','k-icon k-i-plus');
		$this->setGridEditable('popup', false, false, true, false, false);

		$AFilter = new \ActiveFilter('customer_status','zones');
		
		$this->data['active_filter'] = $AFilter->render();		

		$this->data['title'] = "POD-uri";
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		return view('ebsMD',$data);
	}

	public function zones_management()
	{		
		$transport = $this->createGridTransport('zones');

		$schema = $this->createGridSchema(['zone_id','supplier_id','customer_id','customer_name','zone_name','supplier_name','customer_status'],
										  ['number','number','number','string','string','string','string'],
										   /*['supplier_id' => $suppliers[0]['value']]*/);
				
		$dataSource = $this->createDataSource($transport,$schema);
		$session = \Config\Services::session();
		if(isset($_SESSION['select-customer']))
		{
			$filterItem = $this->createFilter('customer_id','eq',[$_SESSION['select-customer']]);
			$dataSource->addFilterItem($filterItem);
		}
		
		$column1 = $this->createGridColumn('supplier_name','Furnizor',150);				
		$column2 = $this->createGridColumn('customer_name','Client',150);
		$column3 = $this->createGridColumn('zone_name','Lot',100);
		$column11 = $this->createGridColumn('customer_status','Status Client',450);

		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true);/*
			 ->edit('function(e){	if(e.model.isNew() && selYear_start_date > -1 && selMonth_start_date > -1)
													{
														let y = selYear_start_date;
														let m = selMonth_start_date  + 1;
														let d = new Date(y, m, 0);
														e.model.set("start_date", y+"-"+m+"-1");
													}
															
								}');*/

		$this->addCustomCommand('editZ','k-icon k-i-pencil','ZoneDialog');
		$this->addCustomOperation('addZ','Adaugă','k-icon k-i-plus');
		$this->setGridEditable('popup', false, false, true, false, false);

		$AFilter = new \ActiveFilter('customer_status','zones');
		
		$this->data['active_filter'] = $AFilter->render();		

		$this->data['title'] = "Loturi Clienti";
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		return view('ebsMD',$data);
	}


	public function distributors_management()
	{

		$transport = $this->createGridTransport('distributors');

		$schema = $this->createGridSchema(['distributor_id','distributor_name','distributor_emergency_phone','distributor_order_anre','distributor_anre_report_index'],
										  ['number','string','string','string','number'],
										  [],['distributor_name']
										   );
		$sortItem = $this->createSortItem('distributor_anre_report_index','asc');
		$dataSource = $this->createDataSource($transport,$schema);
		$dataSource->addSortItem($sortItem);
		
		$column1 = $this->createGridColumn('distributor_name','Distribuitor',150);				
		$column2 = $this->createGridColumn('distributor_emergency_phone','Tel Urgente',150);
		$column3 = $this->createGridColumn('distributor_order_anre','Ordin ANRE',100);
		$column11 = $this->createGridColumn('distributor_anre_report_index','Index in Raport ANRE',150);

		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true);/*
			 ->edit('function(e){	if(e.model.isNew() && selYear_start_date > -1 && selMonth_start_date > -1)
													{
														let y = selYear_start_date;
														let m = selMonth_start_date  + 1;
														let d = new Date(y, m, 0);
														e.model.set("start_date", y+"-"+m+"-1");
													}
															
								}');*/

		$this->setGridEditable('inline', false, true, false, false, false);

		$AFilter = new \ActiveFilter('customer_status','zones');
		
		$this->data['active_filter'] = $AFilter->render();		

		$this->data['title'] = "Distribuitori";
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		return view('ebsMD',$data);
	}
	
	public function suppliers_management()
	{
		$transport = $this->createGridTransport('suppliers');

		$schema = $this->createGridSchema(['supplier_id','supplier_name','supplier_registration_number','supplier_vat_code','supplier_license','supplier_city','supplier_address',
										   'supplier_office','supplier_bank','supplier_bank_account','supplier_bank2','supplier_bank_account2','supplier_phone','supplier_fax',
										   'supplier_email','supplier_program','supplier_invoice_series','supplier_logo','supplier_invoice_no'],
										  ['number','string','string','string','string','string','string',
											'string','string','string','string','string','string','string',
											'string','string','string','string','number'],
										  [],['supplier_logo']
										   );

		$dataSource = $this->createDataSource($transport,$schema);
		
		$column17 = $this->createGridColumn('supplier_logo', '&nbsp;',74);
		$column17->template("<div class='border-pill' style='background-image: url(/logo/#:data.supplier_id#.png);background-size: contain;width:64px;height:64px;'>");
		$column1 = $this->createGridColumn('supplier_name', 'Nume Furnizor',150);
		$column2 = $this->createGridColumn('supplier_registration_number', 'Nr Reg Com',120);
		$column3 = $this->createGridColumn('supplier_vat_code', 'CUI',120);
		$column4 = $this->createGridColumn('supplier_city', 'Oras',100);
		$column5 = $this->createGridColumn('supplier_address', 'Adresa',150);
		$column6 = $this->createGridColumn('supplier_bank', 'Banca',120);
		$column7 = $this->createGridColumn('supplier_bank_account', 'IBAN',150);
		$column8 = $this->createGridColumn('supplier_bank2', 'Banca2',120);
		$column9 = $this->createGridColumn('supplier_bank_account2', 'IBAN2',150);
		$column10 = $this->createGridColumn('supplier_license', 'Nr/Data Licenta',120);
		$column11 = $this->createGridColumn('supplier_phone', 'Telefon',120);
		$column12 = $this->createGridColumn('supplier_fax', 'Fax',120);
		$column13 = $this->createGridColumn('supplier_email', 'Email',120);
		$column14 = $this->createGridColumn('supplier_program', 'Program',80);
		$column15 = $this->createGridColumn('supplier_invoice_series', 'Serie Factura',80);
		$column16 = $this->createGridColumn('supplier_invoice_no', 'Nr Factura',80);



		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true);/*
			 ->edit('function(e){	if(e.model.isNew() && selYear_start_date > -1 && selMonth_start_date > -1)
													{
														let y = selYear_start_date;
														let m = selMonth_start_date  + 1;
														let d = new Date(y, m, 0);
														e.model.set("start_date", y+"-"+m+"-1");
													}
															
								}');*/

		$this->setGridEditable('inline', false, true, false, false, false);

		$this->data['title'] = "Furnizori";
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		return view('ebsMD',$data);
/*
		$crud->setFieldUpload('supplier_logo', 'logo', base_url() . '/logo');
		
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
		]);*/
	}
	
	public function raport()
	{
		$this->data['title'] = "Raport";		
		$this->data['div-card'] = 'card-raport';	
		$this->data['menu'] = "rapoarte";
		
		$request = $this->request;
		$view = $request->getVar('view');
		
		if($view !== null)
		{
			$this->data['title'] = ucfirst(str_replace('view_raport_','', $view));
			

			$transport = $this->createGridTransport($view);

			$schema = $this->createGridSchema();
					
			$dataSource = $this->createDataSource($transport,$schema);
			
			for($i=1; $i<count($this->columns);$i++)
			{
				$col = $this->createGridColumn($this->columns[$i],str_replace('_',' ',$this->columns[$i]));
				if($this->columnTypes[$i] == 'date')
				{
					$col->template("#= data.".$this->columns[$i]." ? kendo.format('{0:dd/MM/yyyy}',data.".$this->columns[$i].") : '' #");
					$col->groupHeaderTemplate("#=kendo.format('{0:dd-MM-yyyy}',data.value)#");
				}
			}

			$this->grid->height(550)
				 ->dataSource($dataSource)
				 ->resizable(true);

			$this->setGridEditable('popup', false, false, false, false, false);

			$this->createGridMenu();
			$this->setGridScrollable('infinite');
	
		}
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		return view('ebsMD',$data);
		
	}

	public function working_days() {
		
		$transport = $this->createGridTransport('working_days');

		$schema = $this->createGridSchema(['working_days_id','year','month','working_days'],
										  ['number','number','number','number'],['year'=>date("Y"),'month'=>date("m"),'working_days'=>21]
										  );

		$sortItem1 = $this->createSortItem('year','desc');
		$sortItem2 = $this->createSortItem('month','desc');
		$dataSource = $this->createDataSource($transport,$schema);
		$dataSource->addSortItem($sortItem1);
		$dataSource->addSortItem($sortItem2);
		
		$column1 = $this->createGridColumn('year','An',150);				
		$column2 = $this->createGridColumn('month','Luna',150);
		$column3 = $this->createGridColumn('working_days','Nr. zile lucratoare',100);
		
		$sortable = new \Kendo\UI\GridSortable();
		$sortable->mode('multiple')
		->showIndexes(true)
		->allowUnsort(true);

		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true)
			 ->edit('function(e){	
									$("#year").data("kendoNumericTextBox").options.decimals=0;
									$("#year").data("kendoNumericTextBox").options.format="#";
									$("#year").data("kendoNumericTextBox").value(e.model.year);
									$("#month").data("kendoNumericTextBox").options.decimals=0;
									$("#month").data("kendoNumericTextBox").options.format="#";
									$("#month").data("kendoNumericTextBox").value(e.model.month);
									$("#working_days").data("kendoNumericTextBox").options.decimals=0;
									$("#working_days").data("kendoNumericTextBox").options.format="#";
									$("#working_days").data("kendoNumericTextBox").value(e.model.working_days);
					
								}');

		$this->setGridEditable('inline', true, true, false, false, false);
	
		$this->data['title'] = "Zile lucratoare";
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		$this->grid->sortable($sortable); // multisort
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		return view('ebsMD',$data);
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
		
		$suppliers = $this->masterDataModel->getSuppliersArray();
		$distributors = $this->masterDataModel->getDistributorsArray();

		$transport = $this->createGridTransport('suppliers_naming');

		$schema = $this->createGridSchema(['supplier_naming_id','supplier_id','distributor_id','rep_name','pv_name'],
										  ['number','number','number','string','string'],[],['supplier_id','distributor_id']
										   );
		$filterItem = new \Kendo\Data\DataSourceFilterItem();
		$filterItem->field('supplier_id');
		$filterItem->operator('eq');
		$filterItem->value($_SESSION['select-supplier']);
		
		$dataSource = $this->createDataSource($transport,$schema);
		$dataSource->addFilterItem($filterItem);
		$dataSource->serverFiltering(true);
				
		$column2 = $this->createGridColumn('distributor_id','Distribuitor',150);
		$column2->values($distributors);

		$column3 = $this->createGridColumn('pv_name', 'Denumire Furnizor/Proces Verbal',200);
		$column4 = $this->createGridColumn('rep_name', 'Denumire Furnizor/Raport',200);

		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true);/*
			 ->edit('function(e){	if(e.model.isNew() && selYear_start_date > -1 && selMonth_start_date > -1)
													{
														let y = selYear_start_date;
														let m = selMonth_start_date  + 1;
														let d = new Date(y, m, 0);
														e.model.set("start_date", y+"-"+m+"-1");
													}
															
								}');*/

		$this->setGridEditable('inline', false, true, false, false, false);

		$this->data['title'] = "Denumiri Furnizori";
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		return view('ebsMD',$data);

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


}

