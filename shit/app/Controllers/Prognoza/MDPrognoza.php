<?php 
namespace App\Controllers\Prognoza;
use App\Controllers\BaseController;

//use PhpOffice\PhpSpreadsheet\Spreadsheet;
//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use CodeIgniter\Database\Query;
use DateTime;
use DateInterval;
use IntlDateFormatter;

require_once(__DIR__ .'/../tools.php');
require_once(__DIR__ .'/../GridTools.php');

require_once(APPPATH . 'Libraries/ebs/TimePeriodFilter.php');
require_once(APPPATH . 'Libraries/ebs/DistributorFilter.php');
require_once(APPPATH . 'Libraries/ebs/ActiveFilter.php');
require_once(APPPATH . 'Libraries/ebs/TypesFilter.php');

class MDPrognoza extends BaseController
{
	use \GridTools;
	private $data = [
        'title'   => '',
		'menu' => 'prognoza',
		'msg' => '',
		'output' => '',
		'upload' =>'',
		'div-card' => ''];
	
	protected $temperaturesModel;	
	protected $intervalsModel;	
	protected $syntheticsModel;
	protected $farModel;
	protected $sqlData;
	
	protected $tColors;
	protected $tColorsSize;
	protected $tCoef;

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
		$this->temperaturesModel = new \App\Models\Prognoza\TemperaturesModel();
        $this->syntheticsModel = new \App\Models\Prognoza\SyntheticsModel();
		$this->farModel = new \App\Models\Prognoza\FarModel();
		$this->forecastModel = new \App\Models\Prognoza\ForecastModel();
		$this->intervalsModel = new \App\Models\Prognoza\IntervalsModel();
		
    }

	public function index()
	{
	}
	
	public function convertTo15()
	{
		$this->forecastModel->convertTo15();
	}
	
	public function CustomerFreeDays() {
		
		$transport = $this->createGridTransport('working_free_days');

		$schema = $this->createGridSchema(['wfd_id','name','free_date','mapping'],
										  ['number','string','date','number']
										  );

		$sortItem = $this->createSortItem('free_date','desc');
		$dataSource = $this->createDataSource($transport,$schema);
		$dataSource->addSortItem($sortItem);
		
		$column1 = $this->createGridColumn('name','Denumire',150);				
		$column2 = $this->createGridColumn('free_date','Data',150);
		$column2->template('#=translateDate(kendo.toString(free_date,"dddd, dd/MM/yyyy"))#');
		
		$column3 = $this->createGridColumn('mapping','Corelare',150);
		$column3->template('#=window["weekDays"][(data.mapping ?? 0)]#');
		
		$sortable = new \Kendo\UI\GridSortable();
		$sortable->mode('multiple')
		->showIndexes(true)
		->allowUnsort(true);

		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true)
			 ->dataBound('function(e){	
							$("button.k-grid-addWFD").unbind().bind("click",function(){FreeDays();});
							$("button.k-grid-addFDAY").unbind().bind("click",function(){OneFreeDay();});
								}');

		//$this->addCustomCommand('editWFD','k-icon k-i-pencil','FreeDays');
		$this->addCustomOperation('addFDAY','Adaugă Zi','k-icon k-i-plus');
		$this->addCustomOperation('addWFD','Adaugă An','k-icon k-i-plus');
		$this->addCustomCommand('editZ','k-icon k-i-pencil','OneFreeDay');
		$this->setGridEditable('popup', false, false, true, false, false);
		$this->data['jsFiles'] = ['tools.js','inaFilterPeriod.js','prognoza/md_free_days.js'];
		$this->data['title'] = "Zile Libere";
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		$this->grid->sortable($sortable); // multisort
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		return view('prognoza/content',$data);
	}
	
	public function CustomersFreeDaysReport() {
		
		$this->data['jsFiles'] = ['tools.js','inaFilterPeriod.js','inaGrid.js','prognoza/customersFreeDaysReport.js'];
		$this->data['title'] = "Verifica Zile Libere";
		
		$this->data['output'] = '<div id="grid_wrapper"/>';		
		$data['data'] = $this->data;
				
		return view('prognoza/content',$data);
	}
	
	public function ForecastSettings()
	{
		$this->data['title'] = 'Setari Prognoza';
		$this->data['jsFiles'] = ['tools.js','inaDS.js','inaGrid.js','prognoza/forecastSettings.js'];
		
		$this->data['output'] = '<div id="grid_wrapper"/>';	
		$data['data'] = $this->data;
		
		return view('prognoza/content',$data);
	}
	
	public function CustomersProfile()
	{	
		$this->data['title'] = "Profil Consumatori";
		$this->data['jsFiles'] = ['tools.js','inaDS.js','inaFilterButtons.js','inaGrid.js','prognoza/customersProfilesReport.js'];
		
		$this->data['output'] = '<div id="grid_wrapper"/>';		
		$data['data'] = $this->data;

		return view('prognoza/content',$data);
	}
	
	public function Synthetics()
	{
		require_once(APPPATH . 'Libraries/ebs/FastExcel.php');
	
		$session = \Config\Services::session();
		if ($session->get('user') === NULL) return view('login.php'); 
		
		include(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php');
		
		$type = $_GET['type_filer'] ?? 0;
		
		if(isset($_GET['date']))
		{
			$this->data['date'] = \DateTime::createFromFormat('Y-n-d',$_GET['date'].'-01')->getTimestamp();
			$YMdate = $_GET['date'];
		}
		else
		{
			$this->data['date'] = strtotime("now  -1 year");//strtotime("last day of previous month");
			$YMdate = date('Y-n',$this->data['date']);
		}
		
		$spreadsheet = new \Kendo\UI\Spreadsheet('spreadsheet');
		$spreadsheet->attr('style', 'width: 100%;');

		$cl = new \FastExcel($null, 'Sintetice');
		$cl->setFrozen(3,0);
		$spreadsheet->addSheet($cl->getSheet());
		
		$daysHeader = ['Interval'];
		$valueType = ['Text'];
		$alignment = ['center'];
		$colWidth =[60];
		$maxDays=cal_days_in_month(CAL_GREGORIAN,date('n',$this->data['date']),date('Y',$this->data['date']));
		
		$weekDaysHeader = [''];
		$formatter = new \IntlDateFormatter('ro_RO', IntlDateFormatter::LONG, IntlDateFormatter::NONE,null,null,"eeeee");
		
		for($d=1;$d<=$maxDays;$d++)
		{
			$daysHeader[$d] = $d;
			$valueTypes[$d] = '#,###0.000';
			$alignment[$d] = 'center';
			$colWidth[$d] = 40;
			$tdate = \DateTime::createFromFormat('Y-n-j',$YMdate.'-'.$d)->getTimestamp();
			$weekDaysHeader[$d] = substr(ucfirst($formatter->format($tdate)),0,1);
		}

		$cl->setColumnsWidth($colWidth);
		$formatter = new \IntlDateFormatter('ro_RO', IntlDateFormatter::LONG, IntlDateFormatter::NONE,null,null,"MMM-yyyy");
		$cl->addTitle($this->syntheticsModel->getCustomerName($_GET['customerID'] ?? $_SESSION['select-customer'] ?? 0)." / Sintetice ".ucfirst($formatter->format($this->data['date'])),$maxDays+1	,18);
		
		$cl->addHeader($weekDaysHeader,"black","rgb(167,214,255)",18,14);		
		$cl->addHeader($daysHeader,"black","rgb(167,214,255)",18,14);
		$cl->setRowValueTypes($valueTypes);
		$cl->setRowAlignment($alignment);
		
		
		$td = explode('-',$YMdate);
		$gData = $this->syntheticsModel->getSynthetics($td[1],$td[0]);
		$synthType = $this->syntheticsModel->getSyntheticsType($td[1],$td[0]);
		
		$minV = 1000;
		$maxV = 0;
		
		foreach($gData as $c)
		{
			if($c['synthetic_ea'] < $minV)
				$minV = $c['synthetic_ea'];
			
			if($c['synthetic_ea'] > $maxV)
				$maxV = $c['synthetic_ea'];
		}
		$colorCoef = 255/(($maxV-$minV) == 0 ? 0.000001 : ($maxV-$minV));
		
		$rowValueTypes = array();
		$rowData = array();
		
		$rowValueTypes[0] = 'time';
		for($d=1;$d<$maxDays;$d++)
			$rowValueTypes[$d] = '#,###0.000';
		
		$cl->setRowValueTypes($rowValueTypes);
		$i=0;
		
		for($h=0;$h<24;$h++)
		{
			$rowData=array();
			
			$rowData[0] = sprintf("%d", $h+1);//.':00:00';
			$rowAttr[0]['background']="#ffffff";
			$rowAttr[0]['color']="#000000";
			$rowAttr[0]['textAlign']='right';
			$rowAttr[0]['bold']=true;
			
			for($d=1;$d<=$maxDays;$d++)
			{
				$xDate = $td[0].'-'.str_pad($td[1], 2, "0", STR_PAD_LEFT).'-'.sprintf("%02d", $d).' '.sprintf("%02d", $h).':00:00';
				if(isset($gData[$i]) && $gData[$i]['synthetics_datetime'] == $xDate)
				{
					$rowData[$d] = floatval($gData[$i++]['synthetic_ea']);
					$rowAttr[$d]['background']=$this->computeColor($rowData[$d],$colorCoef,$minV);
					$rowAttr[$d]['color']="#000000";
				}
				else
				{
					$rowData[$d] = '';
					$rowAttr[$d]['background']='#FFFFFF';
					$rowAttr[$d]['color']="#000000";					
				}
			}
			
			$cl->addRow($rowData,'black','white',$rowAttr);
		}
		
		if(count($rowData)>0)	
		{
			$cl->addTotal(-1,1);			
			$cl->addRow(['=SUM(B'.$cl->getCurrentRow().':AF'.$cl->getCurrentRow().')'],'black','white',[['format'=>'#,###0.000','background'=>'#a7d6ff','color'=>'#000000','textAlign'=>'center','bold'=>true]]);
		}
			

		$cl->mergeCells();
		
		$mY = $this->syntheticsModel->get_min_max_years('forecast_synthetics','synthetics_datetime');

		$PFilter = new \TimePeriodFilter('tp','raport_orar','Perioana',min($mY['minY'],date('Y')-1),date('Y')-1+(date('m')=='12'),date('Y F',$this->data['date']));
		$PFilter->setcallJSFunction("queryReport");
		$this->data['period_filter'] = $PFilter->render();
		
		
		require_once(APPPATH . 'Libraries/ebs/CustomerFilter.php');

		/*$filter = new \Kendo\Data\DataSourceFilterItem();
		$filter->field('customer_status');
		$filter->operator('eq');
		$filter->value('Activ');*/
		
		$customerFilter = new \CustomerFilter('customer_name', 'customer_id', 'customers',"queryReport",null,$this->customerSynthDataSource($YMdate));
		$this->data['customer_filter'] = $customerFilter->render();
		
		$this->data['synthetic_type'] = $synthType;
		$this->data['title'] = "Sintetice";
		$this->data['output'] = $spreadsheet->render();
		$this->data['jsFiles'] = ['tools.js','prognoza/synthetics.js'];
		
		$data['data'] = $this->data;
		
		return view('prognoza/synthetics',$data);
	}
	
	public function Profiles()
	{
		$mY = $this->farModel->get_min_max_years('forecast_view_far_badges','tp');
		$PFilter = new \TimePeriodFilter('tp','forecast_view_far_badges','Perioana',$mY['minY'],$mY['maxY'],'now','single', false);
		$PFilter->setcallJSFunction("tpChanged");
		$this->data['period_filter'] = $PFilter->render();
	
		$forecastModel = new \App\Models\Prognoza\ForecastModel();
		$consumption_types = new \TypesFilter('consumption_types',$forecastModel->getFilterConsumptionTypes());
		$this->data['consumption_types'] = $consumption_types->render();
		
		$this->data['output'] = '<div id="spreadsheet" class="w-100"></div>';
		$this->data['jsFiles'] = ['tools.js','tools_excel.js','prognoza/profiles.js'];
		
		$this->data['title'] = 'Date Orare / Profile Consum';
		$data['data'] = $this->data;
		
		return view('prognoza/content',$data);
	}
	
	public function PrognosisReport()
	{
			
		$this->data['output'] = '<div id="grid_wrapper"/>';		
		$this->data['jsFiles'] = ['tools.js','inaDS.js','inaFilterButtons.js','inaFilterPeriod.js','inaGrid.js','prognoza/prognosisreport.js'];
		
		$this->data['title'] = 'Raport Prognoza';
		$data['data'] = $this->data;
		return view('prognoza/content',$data);
	}
	
	public function PODData()
	{
		$this->data['title'] = 'Raport Date POD';
		
		$transport = $this->createGridTransport('forecast_view_poddata');
		$transport->parameterMap('function(data) {
			
					  if(selMonth_report_date == -1)
						data.month = (new Date()).getMonth()+1;
					  else
						data.month = selMonth_report_date + 1;
					
					  if(selYear_report_date == -1)
					    data.year = (new Date().getFullYear());
					  else
						  data.year = selYear_report_date;
					  
					  data.quick = $(".k-searchbox input").val();
					  data.supplierID = supplierID;

					  return kendo.stringify(data);
				  }');
				  
		$schema = $this->createGridSchema([
		'pod_id','supplier_id','customer_name','pod_no','county','ea'],
		['number','number','string','string','string','number']);
		
				
		$dataSource = new \Kendo\Data\DataSource();
		
		$dataSource->transport($transport)
				   ->pageSize(1000)
				   ->batch(true)
				   ->schema($schema);
		
		$filterItem1 = $this->createNowFilter('report_date',false);
		
		$filterItemS = new \Kendo\Data\DataSourceFilterItem();
		$filterItemS->field('supplier_id');
		$filterItemS->operator('eq');
		$filterItemS->value($_SESSION['select-supplier']);
		
		$filterItem = $this->combineFilters($filterItem1,$filterItemS,'and');		
		
		$dataSource->addFilterItem($filterItem);
		
		$dataSource->serverFiltering(true);
		$dataSource->serverPaging(false);
		$dataSource->serverSorting(false);
		
		$mY = $this->syntheticsModel->get_min_max_years('forecast_view_raport_badges','report_date');
		
		
		
		$column2 = $this->createGridColumn('customer_name','Client',280);
		$column3 = $this->createGridColumn('pod_no','POD',280);
		$column3 = $this->createGridColumn('county','Judet',200);
		$column3 = $this->createGridColumn('ea','Consum [MWh]',200);
		$column3->template("#=kendo.format('{0:N3}',data.ea)#");
		$column3 = $this->createGridColumn('consumption_date','Data Primului Consum',200);
		
		
		$this->grid->height(550)
				 ->dataSource($dataSource)
				 ->resizable(true);
		
		$column = $this->setGridEditable('popup', false, false, false, false, false);
	
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		$IDFilter = new \TimePeriodFilter('report_date','forecast_view_raport_badges_ignore','Data',$mY['minY']+1,$mY['maxY']);
		
		$this->data['jsFiles'] = ['tools.js'];
		$this->data['period_filter'] = $IDFilter->render();
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		
		return view('prognoza/content',$data);
	}
	
	private function customerSynthDataSource($YMdate)
	{
		$transport = new \Kendo\Data\DataSourceTransport();

		$read = new \Kendo\Data\DataSourceTransportRead();
		
		$read->url(site_url().'/api?subject=custom&type=call&action=getCustomersWithSynthetics')
			 ->contentType('application/json')
			 ->type('POST')
			 ->data(new \Kendo\JavaScriptFunction("function() {
						const searchParams = new URLSearchParams(location.search);
						let year = -1;
						let month = -1;
						if(searchParams.has('date'))
						{
							let da = searchParams.get('date').split('-');
							year = da[0];	
							month=da[1];
						}
					
						if(year == -1) year = new Date().getFullYear()-1;
						if(month == -1) {month = new Date().getMonth(); month+=1;}
						
						return {models:[{ YMdate:year + '-'+month}]} 
						}"));

		$transport->read($read)
				  ->parameterMap("function(data) {
					  return kendo.stringify(data);
				   }");

		$schema = new \Kendo\Data\DataSourceSchema();
		$schema->data('data')
			   ->total('total');

		$group = new \Kendo\Data\DataSourceGroupItem();
		$group->field('status');
		$group->dir('desc');

		$dataSource = new \Kendo\Data\DataSource();
		
		$dataSource->transport($transport)
				   ->schema($schema)
				   ->serverFiltering(false)
				   ->addGroupItem($group);
		
		return $dataSource;
	}
	
	private function customerIntervalsDataSource()
	{
		$transport = new \Kendo\Data\DataSourceTransport();

		$read = new \Kendo\Data\DataSourceTransportRead();
		
		$read->url(site_url().'/api?subject=custom&type=call&action=getActiveCustomers')
			 ->contentType('application/json')
			 ->type('POST')
			 ->data(new \Kendo\JavaScriptFunction("function() {		
			return {models:[{}]} 
			}"));

		$transport->read($read)
				  ->parameterMap("function(data) {
					  return kendo.stringify(data);
				   }");

		$schema = new \Kendo\Data\DataSourceSchema();
		$schema->data('data')
			   ->total('total');

		$dataSource = new \Kendo\Data\DataSource();
		
		$dataSource->transport($transport)
				   ->schema($schema)
				   ->serverFiltering(false);
		
		return $dataSource;
	}
	
	public function Months()
	{
		$this->data['title'] = 'Corelare Luni';
		
		$transport = $this->createGridTransport('months_correlation');
		$transport->parameterMap('function(data) {
			
					  $("button.k-grid-addMonthC").bind("click",function(){MonthCorrelationDialog();});
					  data.quick = $(".k-searchbox input").val();
					  data.supplierID = supplierID;
					  
					  return kendo.stringify(data);
				  }');
				  
		$schema = $this->createGridSchema([
		'mc_id','month','month_name','correlated_month','correlated_month_name'],
		['number','number','string','number','string']);
		
				
		$dataSource = new \Kendo\Data\DataSource();
		
		$dataSource->transport($transport)
				   ->pageSize(1000)
				   ->batch(true)
				   ->schema($schema);
		
		$dataSource->serverFiltering(true);
		$dataSource->serverPaging(false);
		$dataSource->serverSorting(false);
		
		$column1 = $this->createGridColumn('month','Index',50);
		$column1 = $this->createGridColumn('month_name','Luna',200);
		$column2 = $this->createGridColumn('correlated_month_name','Luna Compatibila',200);
	

		$this->grid->height(550)
				 ->dataSource($dataSource)
				 ->resizable(true);
		
		$this->addCustomOperation('addMonthC','Adaugă/Editează','k-icon k-i-plus');
		$column = $this->setGridEditable('popup', false, false, true, false, false);
		
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		
		$this->data['jsFiles'] = ['tools.js','prognoza/month_correlation.js'];
		
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		
		return view('prognoza/content',$data);
	}
			
	public function Intervals()
	{
		require_once(APPPATH . 'Libraries/ebs/FastExcel.php');
	
		$session = \Config\Services::session();
		if ($session->get('user') === NULL) return view('login.php'); 
		
		include(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php');
		
		$customerID = $_GET['customerID'] ?? 0;
		
		if(isset($_GET['customerID']))
			$this->data['customerID'] = $customerID;
		else
			$this->data['customerID'] = NULL;
		
		$spreadsheet = new \Kendo\UI\Spreadsheet('spreadsheet');
		$spreadsheet->attr('style', 'width: 100%;');
		
		$cl = new \FastExcel($null, 'Intervale Orare');
		$cl->setFrozen(2,0);
		$spreadsheet->addSheet($cl->getSheet());
		$spreadsheet->changing('onChanging');
		
		$monthsHeader = ['Interval'];
		$valueType = ['Text'];
		$alignment = ['left'];
		$colWidth =[120];
		
		$weekDaysHeader = [''];
		$formatter = new \IntlDateFormatter('ro_RO', IntlDateFormatter::LONG, IntlDateFormatter::NONE,null,null,"eeeee");		
		
		for($m=1;$m<=12;$m++)
		{
			$monthsHeader[$m] = date('M', strtotime("2024-$m-14"));;
			$valueTypes[$m] = '#,###0.000';
			$alignment[$m] = 'center';
			$colWidth[$m] = 40;
			$tdate = \DateTime::createFromFormat('Y-n-j',"2024-$m-15")->getTimestamp();
			$weekDaysHeader[$m] = substr(ucfirst($formatter->format($tdate)),0,1);
		}

		$cl->setColumnsWidth($colWidth);
		$formatter = new \IntlDateFormatter('ro_RO', IntlDateFormatter::LONG, IntlDateFormatter::NONE,null,null,"MMMM-yyyy");
		$cl->addTitle("Tipuri Intervale Orare",13,14)->height(20);
		
		$cl->addHeader($monthsHeader,"black","rgb(167,214,255)",18,14);
		$cl->setRowValueTypes($valueTypes);
		$cl->setRowAlignment($alignment);
		
		$gData = $this->intervalsModel->getIntervalData($customerID);
		
		if(count($gData) == 288 || empty($gData))
		{
			$rowValueTypes = array();
			$rowData = array();
			
			
			$rowValueTypes[0] = '#';
			for($d=1;$d<=12;$d++)
				$rowValueTypes[$d] = '#';
			
			$cl->setRowValueTypes($rowValueTypes);
			$i=0;
			
			for($h=0;$h<24;$h++)
			{
				$rowData=array();
				
				$rowData[0] = $h+1;
				$rowAttr[0]['background']="#ffffff";
				$rowAttr[0]['color']="#000000";
				$rowAttr[0]['textAlign']="right";
				$rowAttr[0]['bold']=true;
				
				for($d=1;$d<=12;$d++)
				{
					if(isset($gData[$i]['interval_type']))
					{
						$rowData[$d] = $gData[$i++]['interval_type'];
						if($rowData[$d] == 'Z') $bColor = "#00ff00";
						else $bColor = "#ff0000";
						$rowAttr[$d]['background']=$bColor;
						$rowAttr[$d]['color']="#000000";
						$rowAttr[$d]['bold']=true;
					}
				}
				
				$cl->addRow($rowData,'black','white',$rowAttr);
			}
						
			$cl->mergeCells();
		}
		
		require_once(APPPATH . 'Libraries/ebs/CustomerFilter.php');

		/*$filter = new \Kendo\Data\DataSourceFilterItem();
		$filter->field('customer_status');
		$filter->operator('eq');
		$filter->value('Activ');*/
		
		$customerFilter = new \CustomerFilter('customer_name', 'customer_id', 'customers',"queryReport",null,$this->customerIntervalsDataSource(),'General');
		$this->data['customer_filter'] = $customerFilter->render();
		
		$this->data['title'] = "Intervale Orare";
		$this->data['output'] = $spreadsheet->render();
		$this->data['jsFiles'] = ['tools.js','prognoza/intervals.js'];
		
		$data['data'] = $this->data;
		
		return view('prognoza/content',$data);
	}
	
	public function Temperatures()
	{
		
		require_once(APPPATH . 'Libraries/ebs/FastExcel.php');
	
		$session = \Config\Services::session();
		if ($session->get('user') === NULL) return view('login.php'); 
		
		include(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php');
		
		$type = $_GET['type_filer'] ?? 0;
		
		if(isset($_GET['date']))
		{
			$this->data['date'] = \DateTime::createFromFormat('Y-n-d',$_GET['date'].'-01')->getTimestamp();
			$YMdate = $_GET['date'];
		}
		else
		{
			$this->data['date'] = strtotime("now");//strtotime("last day of previous month");
			$YMdate = date('Y-n',$this->data['date']);
		}
		
		log_message('error',$YMdate);
				
		$spreadsheet = new \Kendo\UI\Spreadsheet('spreadsheet');
		$spreadsheet->attr('style', 'width: 100%;');
		
		$cl = new \FastExcel($null, 'Temperaturi');
		$cl->setFrozen(3,0);
		$spreadsheet->addSheet($cl->getSheet());
		
		$daysHeader = ['Interval'];
		$valueType = ['Text'];
		$alignment = ['left'];
		$colWidth =[60];
		$maxDays=cal_days_in_month(CAL_GREGORIAN,date('n',$this->data['date']),date('Y',$this->data['date']));
		
		$weekDaysHeader = [''];
		$formatter = new \IntlDateFormatter('ro_RO', IntlDateFormatter::LONG, IntlDateFormatter::NONE,null,null,"eeeee");
		
		for($d=1;$d<=$maxDays;$d++)
		{
			$daysHeader[$d] = $d;
			$valueTypes[$d] = '#,###0.000';
			$alignment[$d] = 'center';
			$colWidth[$d] = 40;
			$tdate = \DateTime::createFromFormat('Y-n-j',$YMdate.'-'.$d)->getTimestamp();
			$weekDaysHeader[$d] = substr(ucfirst($formatter->format($tdate)),0,1);
		}

		$cl->setColumnsWidth($colWidth);
		$formatter = new \IntlDateFormatter('ro_RO', IntlDateFormatter::LONG, IntlDateFormatter::NONE,null,null,"MMMM-yyyy");
		$cl->addTitle("Temperaturi - ".ucfirst($formatter->format($this->data['date'])),$maxDays+1	,18);
		
		$cl->addHeader($weekDaysHeader,"black","rgb(167,214,255)",18,14);		
		$cl->addHeader($daysHeader,"black","rgb(167,214,255)",18,14);
		$cl->setRowValueTypes($valueTypes);
		$cl->setRowAlignment($alignment);
		
		
		$td = explode('-',$YMdate);
		$gData = $this->temperaturesModel->getTemperatures($td[1],$td[0]);
		
		$rowValueTypes = array();
		$rowData = array();
		
		$rowValueTypes[0] = 'time';
		for($d=1;$d<$maxDays;$d++)
			$rowValueTypes[$d] = '#,##0.00';
		
		$cl->setRowValueTypes($rowValueTypes);
		$i=0;
		
		for($h=0;$h<24;$h++)
		{
			$rowData=array();
			
			$rowData[0] = sprintf("%d", $h+1);//.':00:00';
			$rowAttr[0]['background']="#ffffff";
			$rowAttr[0]['color']="#000000";
			$rowAttr[0]['textAlign']='right';
			$rowAttr[0]['bold']=true;
			
			for($d=1;$d<=$maxDays;$d++)
			{
				$xDate = $td[0].'-'.str_pad($td[1], 2, "0", STR_PAD_LEFT).'-'.sprintf("%02d", $d).' '.sprintf("%02d", $h).':00:00';
				if(isset($gData[$i]) && $gData[$i]['temperature_datetime'] == $xDate)
				{
					$rowData[$d] = floatval($gData[$i++]['temperature']);
					$rowAttr[$d]['background']=$this->computerTColor($rowData[$d]);
					$rowAttr[$d]['color']="#000000";
				}
				else
				{
					$rowData[$d] = '';
					$rowAttr[$d]['background']='#FFFFFF';
					$rowAttr[$d]['color']="#000000";					
				}
			}
			
			$cl->addRow($rowData,'black','white',$rowAttr);
		}
		
		if(count($rowData)>0)	
		{
			$minRow = ['Min'];
			$avgRow = ['Avg'];
			$maxRow = ['Max'];
			for($d=1;$d<=$maxDays;$d++)
			{
				$col = $cl->ColumnNumberToColumnName($d);
				$minRow[] = '=MIN('.$col.'4:'.$col.'27)';
				$avgRow[] = '=IFERROR(AVERAGE('.$col.'4:'.$col.'27),0)';
				$maxRow[] = '=MAX('.$col.'4:'.$col.'27)';				
			}
			
			$lastRow = $cl->getCurrentRow();
			$cl->addRow($minRow);
			$cl->addRow($avgRow);
			$cl->addRow($maxRow);
			
			$cl->addRow(['=AVERAGE(B4:'.$cl->ColumnNumberToColumnName($maxDays).'27)'],'black','white',[['format'=>'#,##0.00','background'=>'#a7d6ff','color'=>'#000000','textAlign'=>'center','bold'=>true]]);
		}
			

		$cl->mergeCells();
		
		
		//upload
		$files=['.xlsx','.txt'];
		$upload = new \Kendo\UI\Upload('files[]');
		$upload->async(array(
				'saveUrl' => site_url().'/Prognoza/MDPrognoza/upload',
				'removeUrl' => site_url().'/Prognoza/MDPrognoza/remove',
				'autoUpload' => true,
				'removeField' => 'fileNames[]'
			   ))
			   ->validation(array(
				'allowedExtensions'=>$files,
				'maxFileSize' => 20971520)) //20MB
			   //->showFileList(true)
			   //->dropZone('.dropZone')
			   ->success('onSuccess')
			   ->complete('onComplete')
			   //->select('onSelect')
			   //->cancel('onError')
			    ->upload('onUpload')
			   ->multiple(true);
		$this->data['upload'] = $upload->render();
		
		$mY = $this->temperaturesModel->get_min_max_years('forecast_temperatures','temperature_datetime');

		$PFilter = new \TimePeriodFilter('tp','raport_orar','Perioana',$mY['minY']-1,$mY['maxY']+1,date('Y F',$this->data['date']));
		$PFilter->setcallJSFunction("queryReport");
		$this->data['period_filter'] = $PFilter->render();
				
		$this->data['title'] = "Temperaturi";
		$this->data['output'] = $spreadsheet->render();
		$this->data['jsFiles'] = ['tools.js','prognoza/temperatures.js'];
		
		$data['data'] = $this->data;
		
		return view('prognoza/temperaturi',$data);
	}
	
	/*Weather*/
	public function Weather()
	{
		$session = \Config\Services::session();
		if ($session->get('user') === NULL) return view('login.php'); 
		
		include(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php');

		$this->data['title'] = "Prognoza Meteo";
		$this->data['output'] = '<div id="spreadsheet" class="w-100"></div>';
		$this->data['jsFiles'] = ['tools.js','inaExcel.js','inaFilterPeriod.js','inaFilterButtons.js','prognoza/meteo.js'];
		
		$data['data'] = $this->data;
		
		return view('prognoza/content',$data);
	}
	
	public function upload()
	{

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			header('Content-Type: application/json');
		
			$files = $_FILES['files'];
			// Save the uploaded files
			$saveDir = WRITEPATH.'uploads/';
			
			if(count($files['name'])>0)
			{
				if (!file_exists($saveDir)) {
					mkdir($saveDir, 0777, true);
				}
			}
			for ($index = 0; $index < count($files['name']); $index++) {
				$filePath = $files['tmp_name'][$index];
				if (is_uploaded_file($filePath)) {
					$destFile = $saveDir.'/'. $files['name'][$index];
					move_uploaded_file($filePath, $destFile);
					try {
						$result['msg'] = $this->importFile($destFile);
						$result['type'] = 'info';
					}
					catch (\Exception $e) {
						log_message('error',$e->getMessage());
						log_message('error',$e->getTraceAsString());
						$result['msg'] = ' Eroare, este '.$files['name'][$index].' fisierul corect?';
						$result['type'] = 'error';
					}

					echo json_encode($result);
					unlink($destFile);
				}
			}
			exit();
			
		}
		else
			return redirect()->to( base_url('/Realizat/ImportReadings'))->with('msg', 'Ai incercat sa incarci un fisier?');
	}
	
	public function getTemperaturesLegend()
	{
		if(!$this->tColorsSize) $this->createTemperatureLegend();
		return $this->tColors[$this->tColorsSize-1];
	}
	
	private function createTemperatureLegend()
	{
		$legend = $this->temperaturesModel->createTemperatureLegend();

		$this->tColorsSize = $legend['tColorsSize'];
		$this->tColors = $legend['tColors'];
		$this->tCoef = $legend['tCoef'];
	}
	
	protected function computerTColor($value)
	{
		if($value == '') return "#FFFFFF";
		if(!$this->tColorsSize) $this->createTemperatureLegend();
		if($value<-50) $value = -50;
		if($value>50) $value = 50;
		
		$value += 50;
		if(($value*$this->tCoef)>=$this->tColorsSize)
			return $this->tColors[$this->tColorsSize-1];
		
		if($value*$this->tCoef<0)
			return $this->tColors[0];
		
		return $this->tColors[$value*$this->tCoef];
	}
	
	protected function computeColor($value, $coef, $minV)
	{
		$str = '#ff'.sprintf('%02x', 255-floor($coef * ($value-$minV)) ).sprintf('%02x', 255-floor($coef * ($value-$minV)) );
		
		//echo $value * $coef; exit();
		return $str;
	}
	
	private function importFile($file)
	{
		$msg = '';
		$pdfText = '';
		$fileInfo = pathinfo($file);
		//$sConsumtions = $this->importConsumptionsModel->countConsumptions();
				
		if(in_array(strtolower($fileInfo['extension']), array('xlsx')))
			$duplicate = $this->import_XLS($file);		
		elseif(in_array(strtolower($fileInfo['extension']), array('txt')))
			$duplicate = $this->import_CSV($file);

		if($duplicate) return basename($file)." este duplicat !";
		
		return "Fisier procesat";
	}
	
	protected function import_XLS($file)
	{
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
		$sheet = $spreadsheet->getSheet(0);
		
		$row = 1;
		$duplicate = true;
		
		while(true)
		{
			$row++;
			$dateText = $sheet->getCellByColumnAndRow(1, $row)->getValue();
			$hour = $sheet->getCellByColumnAndRow(2, $row)->getValue();
			$temperature = $sheet->getCellByColumnAndRow(3, $row)->getValue();
					
			
			
			if (empty($dateText) || empty($hour) || $temperature == '') break;
			
			log_message("debug",$dateText." ".$hour.":".$temperature);
			
			//$dateText = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($dateObj)->format('Y-m-d');
			$date = \DateTime::createFromFormat('d.m.Y H', $dateText.' '.$hour-1);
			
			if($duplicate && !$this->temperaturesModel->isDuplicate($date->format('Y-m-d H:00:00'), $temperature))
				$duplicate = false;
								
			$this->addSQLValuesString($date,$temperature);
		} 
		
		if(!$duplicate)
			$this->importSQLValues();
		
		return $duplicate;
	}
	
	protected function import_CSV($file)
	{
		$myfile = fopen($file, "r");
		if ($myfile === false) throw new \RuntimeException("Unable to open file: $file");
		$l=0;
		$duplicate = true;
		
		while(!feof($myfile)) {
			$line = preg_split('/[\s,]+/', fgets($myfile), 0, PREG_SPLIT_NO_EMPTY);
			if($l++<2) continue;
			log_message("debug",print_r($line, true));
			if(!isset($line[0]) || strlen($line[0])!=10) break;
			$date = \DateTime::createFromFormat('YmdH', $line[0]);
			$temperature = $line[1];
			
			if($duplicate && !$this->temperaturesModel->isDuplicate($date->format('Y-m-d H:00:00'), $temperature))
				$duplicate = false;
			
			$this->addSQLValuesString($date,$temperature);
		}
		fclose($myfile);
		
		if(!$duplicate)
			$this->importSQLValues();
		
		return $duplicate;
	}

	protected function addSQLValuesString($date,$temperature)
	{
		$textDate = $date->format('Y-m-d H:00:00');
		$this->sqlData .= "('" . $textDate . "'," . floatval($temperature) . "),";
	}
	
	protected function importSQLValues()
	{
		if(!empty($this->sqlData))
		{
			if(strlen($this->sqlData)>0) $this->sqlData = substr_replace($this->sqlData,'',-1);
			$this->temperaturesModel->importSQLValues($this->sqlData);			
		}
	}
	
}
