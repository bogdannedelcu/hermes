<?php 

namespace App\Controllers;

require_once('tools.php');
require_once('GridTools.php');

use GroceryCrud\Core\GroceryCrud;
require_once(APPPATH . 'Libraries/ebs/TimePeriodFilter.php');
require_once(APPPATH . 'Libraries/ebs/DistributorFilter.php');
require_once(APPPATH . 'Libraries/ebs/ActiveFilter.php');

class Consumptions extends BaseController
{
	use \GridTools;
	private $data = [
        'title'   => 'Consumuri',
		'div-card' => 'card-consumptions',
		'menu' => 'consumuri'];
	
    protected $consumptionsModel;

	
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
        $this->consumptionsModel = new \App\Models\ConsumptionsModel();
    }
	
	public function index()
	{
			
		$transport = $this->createGridTransport('consumptions');

		$schema = $this->createGridSchema([
		'consumption_id','distributor_name','supplier_name','customer_name','customer_code','contract_number','consumption_location_id',
		'pod','voltage_level_delimitation','voltage_level_measurment','invoice_start_date','invoice_end_date','reading_start_date','reading_end_date',
		'device_serial_number','energy_type','index_old','index_new','total_consumption_ae','total_consumption_re','total_consumption_re_3x',
		'total_consumption_mu',	'curve_name', 'curve_profile', 'source','invoice_id','DataFacturarii','consumption_date','timestamp'],
		['number','string','string','string','string','string','string',
		'string','string','string','date','date','date','date',
		'string','string','number','number','number','number','number',
		'string','string','string','string','number','date','date','date']);
		
	  	$sortItem = $this->createSortItem('customer_name');
			
		$eaQuantity = new \Kendo\Data\DataSourceAggregateItem();
		$eaQuantity->field("total_consumption_ae")
                ->aggregate("sum");
						 
		$dataSource = $this->createDataSource($transport,$schema);
		
		$idY = $this->consumptionsModel->get_consumptions_min_max_years();
		$ddY = $this->consumptionsModel->get_importdate_min_max_years();
		
		$filterItem1 = $this->createNowFilter('consumption_date',false,$idY['maxY']);
		//$filterItem2 = $this->createNowFilter('timestamp',true);
		//$filterItem = $this->combineFilters($filterItem1,$filterItem2,'and');
		
		$sum1 = new \Kendo\Data\DataSourceAggregateItem();
		$sum1->field('total_consumption_ae')
			 ->aggregate("sum");

		$sum2 = new \Kendo\Data\DataSourceAggregateItem();
		$sum2->field('total_consumption_re')
			 ->aggregate("sum");

		$sum3 = new \Kendo\Data\DataSourceAggregateItem();
		$sum3->field('total_consumption_re_3x')
			 ->aggregate("sum");
		
		$group = new \Kendo\Data\DataSourceGroupItem();
		$group->field('customer_name')
				->addAggregate($sum1)
				->addAggregate($sum2)
				->addAggregate($sum3);
				
		$dataSource
				   ->addSortItem($sortItem)
				   ->addFilterItem($filterItem1)
				   ->addAggregateItem($sum1)
				   ->addAggregateItem($sum2)
				   ->addAggregateItem($sum3);
					
		$column1 = $this->createGridColumn('distributor_name','Distribuitor',100);
				
		$column2 = $this->createGridColumn('customer_name','Client',100);
		$column2->groupHeaderTemplate("#=data.value# EA:<span class='text-danger'>#=kendo.format('{0:N3}', aggregates.total_consumption_ae.sum / 1000)# MWh</span> 
		ER:<span class='text-success'>#=kendo.format('{0:N0}', aggregates.total_consumption_re.sum)#</span> 
		ER_3X:<span class='text-primary'>#=kendo.format('{0:N0}',aggregates.total_consumption_re_3x.sum)#</span>");
		
		$column3 = $this->createGridColumn('customer_code','Cod Client',100);
		$column3->hidden(true);
		$column4 = $this->createGridColumn('pod','Pod',100);	
		$column14 = $this->createGridColumn('consumption_location_id','Id loc consum',100);	
		$column14->hidden(true);
		$column5 = $this->createGridColumn('invoice_start_date','Data factura start',70);
		$column5->hidden(true);
		$column6 = $this->createGridColumn('invoice_end_date','Data factura stop',70);
		
		$column15 = $this->createGridColumn('reading_start_date','Data citire start',70);
		$column15->hidden(true);
		$column16 = $this->createGridColumn('reading_end_date','Data citire stop',70);
		$column16->hidden(true);
		
		$column16 = $this->createGridColumn('voltage_level_delimitation','Nivel Tens Del',70);
		$column16->hidden(true);
		$column17 = $this->createGridColumn('voltage_level_measurment','Nivel Tens Mas',70);
		$column18 = $this->createGridColumn('energy_type','Energie',70);
		
		
		$column19 = $this->createGridColumn('device_serial_number','Serie Aparat Masura',70);
		$column19->hidden(true);
		$column20 = $this->createGridColumn('index_old','Index vechi',70);
		$column20->hidden(true);
		$column21 = $this->createGridColumn('index_new','Index Nou',70);
		$column21->hidden(true);
		
		$column22 = $this->createGridColumn('curve_name','Curba Consum',70);
		//$column22->hidden(true);
		$column23 = $this->createGridColumn('curve_profile','Profilul Curbei',70);
		//$column23->hidden(true);
		
		$column7 = $this->createGridColumn('total_consumption_ae','EA [KWh]',70);		
		$column7->template("#=kendo.format('{0:N0}',data.total_consumption_ae)#")
				->aggregates('sum');
		$column7->footerTemplate("#=kendo.format('{0:N3}',sum/1000)# MWh");
		$column8 = $this->createGridColumn('total_consumption_re','ER',70)
				->aggregates('sum');
		
		$column9 = $this->createGridColumn('total_consumption_re_3x','ER x3',70)
				->aggregates('sum');
				
		$column10 = $this->createGridColumn('total_consumption_mu','UM',70);
		$column11 = $this->createGridColumn('source','Sursa',70);
		$column25 = $this->createGridColumn('invoicing_date','Data Facturarii',70);
		$column25 = $this->createGridColumn('invoice_no','Nr Factura',70);
		$column12 = $this->createGridColumn('consumption_date','Data Consum',70);
		$column12->template("#=kendo.toString(data.consumption_date, 'yyyy-MM')#"); 
	
		$this->grid->height(550)
				 ->dataSource($dataSource)
				 ->resizable(true);
		
		$this->addCustomOperation('addI','Adaugă','k-icon k-i-plus');
		$this->addCustomCommand('editD','k-icon k-i-pencil','ConsumptionDialog');
		$column = $this->setGridEditable('inline', false, false, true, false, true);
		$column->headerTemplate('<button type="button" class="bulkDestroy k-state-disabled k-grid-delete k-button k-button-md k-button-rectangle k-rounded-md k-button-solid k-button-solid-base k-icon-button" onclick="bulkDestroy()"><span class="k-icon k-i-close k-button-icon"></span></button>');
		
		$this->grid->change('onChange');
		
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
			
		$AFilter = new \ActiveFilter('Facturat','consumptions','Status',['Facturat','Nefacturat']);
		$IDFilter = new \TimePeriodFilter('consumption_date','consumptions','Data Consum',$idY['minY'],$idY['maxY']);
		//$IMFilter = new \TimePeriodFilter('timestamp','consumptions','Data Import',$ddY['minY'],$ddY['maxY'],null);
		$DFilter = new \DistributorFilter('distributor_id',true);
		
	
		$this->data['output'] = $this->grid->render();
		$this->data['consumption_date_filter'] = $IDFilter->render();
		//$this->data['import_date_filter'] = $IMFilter->render();
		$this->data['distributor_filter'] = $DFilter->render();
		$this->data['active_filter'] = $AFilter->render();
		
		$data['data'] = $this->data;
		return view('consumptions',$data);
	}
	
}