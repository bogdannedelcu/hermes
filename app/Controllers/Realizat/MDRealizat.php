<?php 
namespace App\Controllers\Realizat;
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

class MDRealizat extends BaseController
{
	use \GridTools;
	private $data = [
        'title'   => 'Raport Verificare Diferente (facturat - realizat)',
		'menu' => 'realizat',
		'msg' => '',
		'output' => '',
		'upload' =>'',
		'div-card' => 'card-realizat'];
		
	protected $importActualModel;

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
        $this->importActualModel = new \App\Models\Realizat\ImportActualModel();
    }
		
	public function Curves()
	{
		$this->data['title'] = 'Curbe Consum';
		
		$transport = $this->createGridTransport('actual_curves');

		$schema = $this->createGridSchema([
		'curve_id','supplier_id','distributor_id','curve_name','curve_type','distributor_name','pods',
		'NumPods'],
		['number','number','number','string','string','string','string',
		'number']);
					
		$dataSource = new \Kendo\Data\DataSource();
		
		$dataSource->transport($transport)
				   ->batch(true)
				   ->schema($schema);
				
		$filterItemS = new \Kendo\Data\DataSourceFilterItem();
		$filterItemS->field('supplier_id');
		$filterItemS->operator('eq');
		$filterItemS->value($_SESSION['select-supplier']);
		
		$dataSource->addFilterItem($filterItemS);
		$dataSource->serverFiltering(true);
		$dataSource->serverPaging(true);
		$dataSource->serverSorting(true);
		
		$column1 = $this->createGridColumn('distributor_name','Distribuitor',60);
		$column2 = $this->createGridColumn('curve_name','Curba Consum',80);
		$column3 = $this->createGridColumn('curve_type','Tip',40);
		$column4 = $this->createGridColumn('Customers','Clienti',60);
		$column5 = $this->createGridColumn('NumProfiles','Nr.Profile',40);
		$column6 = $this->createGridColumn('Profiles','Profile',60);
		$column7 = $this->createGridColumn('NumPods','Nr.POD-uri',40);
		$column8 = $this->createGridColumn('pods','POD-uri',240);
			 
		$this->grid->height(550)
				 ->dataSource($dataSource)
				 ->resizable(true);
		
		$column = $this->setGridEditable('popup', false, false, true, false, true);
		
		$column->headerTemplate('<button type="button" class="bulkDestroy k-state-disabled k-grid-delete k-button k-button-md k-button-rectangle k-rounded-md k-button-solid k-button-solid-base k-icon-button" onclick="bulkDestroy()"><span class="k-icon k-i-close k-button-icon"></span></button>');
		
		$this->grid->change('onChange');
		
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		$DFilter = new \DistributorFilter('distributor_id');
		
		
		$this->data['distributor_filter'] = $DFilter->render();
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		
		return view('realizat/curves',$data);
	}
	
	public function VariatiiCurbe()
	{
		
		$this->data['title'] = 'Variatie Curbe de Consum';
		
		$transport = $this->createGridTransport('actual_curves_variance');

		$schema = $this->createGridSchema([
		'acv_id','supplier_id','consumption_date','pod','curve_id','consumption_profile','supplier_name',
		'distributor_name','city','customer_name','distributor_id','curve_name','curve_type','consumption_ea'],
		['number','number','date','string','number','string','string',
		'string','string','string','number','string','string', 'number']);
		
		$dgroup = new \Kendo\Data\DataSourceGroupItem();
		$dgroup->field('distributor_name');
		
		$cgroup = new \Kendo\Data\DataSourceGroupItem();
		$cgroup->field('customer_name');
				
		$dataSource = new \Kendo\Data\DataSource();
		
		$dataSource->transport($transport)
				   ->pageSize(1000)
				   ->batch(true)
				   ->schema($schema)
				   ->addGroupItem($dgroup)
				   ->addGroupItem($cgroup);
		
		$idY = $this->importActualModel->get_actualconsumption_date_min_max_years();
		$filterItem1 = $this->createNowFilter('consumption_date',false,$idY['maxY']);
		
		$filterItemS = new \Kendo\Data\DataSourceFilterItem();
		$filterItemS->field('supplier_id');
		$filterItemS->operator('eq');
		$filterItemS->value($_SESSION['select-supplier']);
		
		$filterItem = $this->combineFilters($filterItem1,$filterItemS,'and');		
		
		$dataSource->addFilterItem($filterItem);
		$dataSource->serverFiltering(true);
		$dataSource->serverPaging(true);
		$dataSource->serverSorting(true);
		
		$column1 = $this->createGridColumn('distributor_name','Distribuitor',80);
		$column2 = $this->createGridColumn('customer_name','Client',80);
		$column2 = $this->createGridColumn('customer_code','Cod Client',40);
		$column3 = $this->createGridColumn('city','Oras',80);
		$column4 = $this->createGridColumn('pod','POD',80);
		$column5 = $this->createGridColumn('curve_name','Curba',80);
		$column6 = $this->createGridColumn('consumption_profile','Profil',80);
		$column7 = $this->createGridColumn('curve_type','Tip',80);
		$column8 = $this->createGridColumn('consumption_ea','EA Consumat(MWh)',80);
		
		$column4 = $this->createGridColumn('consumption_date','Data Consum',50);
				
			 
		$this->grid->height(550)
				 ->dataSource($dataSource)
				 ->resizable(true);
		
		$this->addCustomOperation('addCV','Adaugă','k-icon k-i-plus');
		$column = $this->setGridEditable('popup', false, false, true, false, true);
		
		$column->headerTemplate('<button type="button" class="bulkDestroy k-state-disabled k-grid-delete k-button k-button-md k-button-rectangle k-rounded-md k-button-solid k-button-solid-base k-icon-button" onclick="bulkDestroy()"><span class="k-icon k-i-close k-button-icon"></span></button>');
		
		$this->grid->change('onChange');
		
		
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		
		$IDFilter = new \TimePeriodFilter('consumption_date','actual_curves_variance','Data Consum',$idY['minY'],$idY['maxY']);
		$DFilter = new \DistributorFilter('distributor_id');
		
		
		$this->data['consumption_date_filter'] = $IDFilter->render();
		$this->data['distributor_filter'] = $DFilter->render();
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		
		return view('realizat/variatie_curbe',$data);
	}

	public function Readings()
	{
		$this->data['title'] = 'Date Orare';
		
		$transport = $this->createGridTransport('actual_readings');
		$transport->parameterMap('function(data) {
			
					  if(selMonth_reading_datetime == -1)
						data.month = (new Date()).getMonth()+1;
					  else
						data.month = selMonth_reading_datetime + 1;
					
					  if(selYear_reading_datetime == -1)
					    data.year = (new Date().getFullYear())-1;
					  else
						  data.year = selYear_reading_datetime;
					  
					  data.quick = $(".k-searchbox input").val();
					  
					  data.distributorID = dId[prevDistributor] ?? null;
					  data.supplierID = supplierID;
					  return kendo.stringify(data);
				  }');
				  
		$schema = $this->createGridSchema([
		'reading_id','supplier_id','reading_datetime','customer_name','curve_name','curve_type','distributor_id',
		'distributor_name','ea','consumption_ea','POD','NoPODs'],
		['number','number','date','string','string','string','number',
		'string','number','number','string','string']);
				
		$dataSource = new \Kendo\Data\DataSource();
		
		$dataSource->transport($transport)
				   ->pageSize(1000)
				   ->batch(true)
				   ->schema($schema);
		
		$idY = $this->importActualModel->get_actualreading_date_min_max_years();
		$filterItem1 = $this->createNowFilter('reading_datetime',false,$idY['maxY']);
		
		$filterItemS = new \Kendo\Data\DataSourceFilterItem();
		$filterItemS->field('supplier_id');
		$filterItemS->operator('eq');
		$filterItemS->value($_SESSION['select-supplier']);
		
		$filterItem = $this->combineFilters($filterItem1,$filterItemS,'and');		
		
		$dataSource->addFilterItem($filterItem);
		$dataSource->serverFiltering(true);
		$dataSource->serverPaging(true);
		$dataSource->serverSorting(false);
		
		$column1 = $this->createGridColumn('reading_datetime','Data',80);
		$column2 = $this->createGridColumn('distributor_name','Distribuitor',80);
		$column3 = $this->createGridColumn('customer_name','Client',80);
		$column4 = $this->createGridColumn('curve_name','Cod Curba',80);
		$column5 = $this->createGridColumn('curve_type','Tip Curba',80);
		$column5->template("#=(data.curve_type=='necunoscuta' ? '<div class=\"text-danger fw-bolder\">'+data.curve_type+'</div>' : data.curve_type)#");
		$column6 = $this->createGridColumn('NoPODs','NoPODs',40);
		$column7 = $this->createGridColumn('POD','POD',80);
		$column8 = $this->createGridColumn('ea','EA Realizat(MWh)',80);
		$column8->template("#=(data.ea != data.consumption_ea ? '<div class=\"text-danger fw-bolder\">'+data.ea+'</div>' : data.ea)#");
		$column8 = $this->createGridColumn('consumption_ea','EA Consumat(MWh)',80);
				
			 
		$this->grid->height(550)
				 ->dataSource($dataSource)
				 ->resizable(true);
		
		//$this->setGridEditable('inline');
		//$this->addCustomCommand('editS','k-icon k-i-pencil','serviceDialog');
		//$this->addCustomCommand('status','k-icon k-i-minus-outline','setServiceStatus');
		//$this->addCustomOperation('addS','Adaugă','k-icon k-i-plus');
		$column = $this->setGridEditable('popup', false, false, true, false, true);
		
		$column->headerTemplate('<button type="button" class="bulkDestroy k-state-disabled k-grid-delete k-button k-button-md k-button-rectangle k-rounded-md k-button-solid k-button-solid-base k-icon-button" onclick="bulkDestroy()"><span class="k-icon k-i-close k-button-icon"></span></button>');
		
		$this->grid->change('onChange');
		
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
			
		$IDFilter = new \TimePeriodFilter('reading_datetime','actual_view_badge_readings2','Data',$idY['minY'],$idY['maxY']);
		$DFilter = new \DistributorFilter('distributor_id');
		
		
		$this->data['readings_date_filter'] = $IDFilter->render();
		$this->data['distributor_filter'] = $DFilter->render();
		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		
		return view('realizat/readings',$data);
	}
	
	public function ProfilesCorrelation()
	{
		$this->data['title'] = 'Corelatie Profiluri Sintetice Date Orare - Date Consum';
		
		$transport = $this->createGridTransport('actual_curve_profile_mapping');

		$schema = $this->createGridSchema(['acpm_id','supplier_id','distributor_id','consumption_profile_name','readings_profile_name','supplier_name','distributor_name'],
		['number','number','number','string','string','string','string']);
				
		$dataSource = new \Kendo\Data\DataSource();
		
		$dataSource->transport($transport)
				   ->pageSize(1000)
				   ->batch(true)
				   ->schema($schema);
			
		$filterItemS = new \Kendo\Data\DataSourceFilterItem();
		$filterItemS->field('supplier_id');
		$filterItemS->operator('eq');
		$filterItemS->value($_SESSION['select-supplier']);
		

		$dataSource->addFilterItem($filterItemS);
		$dataSource->serverFiltering(true);
		$dataSource->serverPaging(true);
		$dataSource->serverSorting(true);
		
		$distributors = $this->importActualModel->getDistributorsArray();
		$column1 = $this->createGridColumn('distributor_id','Distribuitor',80);
		$column1->values($distributors);

		$column4 = $this->createGridColumn('readings_profile_name','Denumire Profil Date Orare',80);
		$column3 = $this->createGridColumn('consumption_profile_name','Denumire Profil Date Consum',80);
		
			
			 
		$this->grid->height(550)
				 ->dataSource($dataSource)
				 ->resizable(true);
		
		$this->setGridEditable('inline');
		$this->createGridMenu();
		$this->setGridScrollable('infinite');

		
		$this->data['output'] = $this->grid->render();		
		$data['data'] = $this->data;
				
		
		return view('realizat/raport_verificare',$data);
	}

	public function RaportRealizatZilnic()
	{
		$this->data['output'] = '<div id="spreadsheet" class="w-100"></div>';
		$this->data['jsFiles'] = ['tools.js','inaDS.js','inaFilterButtons.js','inaFilterPeriod.js','inaFilterDistributor.js','tools_excel.js','realizat/dailyReadings.js'];
		
		$this->data['title'] = 'Raport Realizat Zilnic';
		$data['data'] = $this->data;
		
		return view('realizat/content',$data);
	}
	
	public function ExportRaportOrar()
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
			$this->data['date'] = strtotime("last day of previous month");
			$YMdate = date('Y-n',$this->data['date']);
		}
		
		log_message('error',$YMdate);
		
		$distributorID = '-1';
		if(isset($_GET['distributor_id']) && !empty($_GET['distributor_id']) && is_numeric($_GET['distributor_id']))
			$distributorID = $_GET['distributor_id'];
		
		$elementChanged = $_GET['elementChanged'] ?? '';
		
		$customersIDs = [];
		$customerNames = 'Toti Clientii';
		if(isset($_GET['customersIDs']) && !empty($_GET['customersIDs']))
		{
			$customersIDs = explode(',',$_GET['customersIDs']);
			$customerNames = implode(',', $this->importActualModel->getCustomersNames($customersIDs,$distributorID));
		}
		
		$includedPODs = explode(',', ($_GET['includedPODs'] ?? ''));
		
		$spreadsheet = new \Kendo\UI\Spreadsheet('spreadsheet');
		$spreadsheet->attr('style', 'width: 100%;');
		
		$cl = new \FastExcel($null, 'Raport Realizat');
		$cl->setFrozen(3,0);
		$spreadsheet->addSheet($cl->getSheet());
		
		$daysHeader = ['Interval'/*'Ora/Ziua'*/];
		$valueType = ['Text'];
		$alignment = ['left'];
		$colWidth =[60/*120*/];
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
		$cl->addTitle("Realizat - ".ucfirst($formatter->format($this->data['date']))." / $customerNames",$maxDays+1	,18);
		
		$cl->addHeader($weekDaysHeader,"black","rgb(167,214,255)",18,14);		
		$cl->addHeader($daysHeader,"black","rgb(167,214,255)",18,14);
		$cl->setRowValueTypes($valueTypes);
		$cl->setRowAlignment($alignment);
		
		if($elementChanged == 'customers') $includedCurves=[];
		else $includedCurves = explode(',', ($_GET['includedCurves'] ?? ''));
				
		if(count($customersIDs) > 0 && (count($includedPODs)==0 || empty($includedPODs[0])) )
			$includedPODs = $this->importActualModel->getPODsByDistributorCustomer($distributorID,implode(',',$customersIDs),date('Y-m',$this->data['date']),($_GET['includedCurves'] ?? ''), true);
		
		
		if(/*in_array($distributorID,[1,4,5]) && */count($includedPODs)>0 && !empty($includedPODs[0]))
			$mcData = $this->importActualModel->getRaportOrarEnel($this->data['date'],$customersIDs,$distributorID,$includedPODs,$includedCurves, ($type == 1) );
		else
			$mcData = $this->importActualModel->getRaportOrar($this->data['date'],$customersIDs,$distributorID,$includedPODs,$includedCurves, ($type == 1) );
		
		
		$minV = 1000;
		$maxV = -1000;
		
		foreach($mcData as $c)
		{
			if($c['ea'] < $minV)
				$minV = $c['ea'];
			
			if($c['ea'] > $maxV)
				$maxV = $c['ea'];
		}
		$colorCoef = 255/(($maxV-$minV) == 0 ? 0.000001 : ($maxV-$minV));
		
		// Matrice interval(ora) × zi: fiecare valoare e pusă pe ZIUA REALĂ din reading_datetime,
		// nu după poziția rândului în rezultat. Astfel datele rare (ex. o singură zi importată)
		// apar în coloana zilei corecte, iar zilele fără date rămân 0.
		$grid      = array();  // timeKey (ora/interval) => [ zi => ea ]
		$timeOrder = array();  // ordinea cronologică a intervalelor, în ordinea de sosire (ORDER BY ora,minut)
		foreach($mcData as $c)
		{
			$dex  = explode(' ', $c['reading_datetime']);
			$time = $dex[1];
			$fDay = intval(explode('-', $dex[0])[2]);
			if(!isset($grid[$time])) { $grid[$time] = array(); $timeOrder[] = $time; }
			$grid[$time][$fDay] = floatval($c['ea']);
		}

		$interval = 1;
		foreach($timeOrder as $time)
		{
			$rowData = array();
			$rowAttr = array();
			$rowData[0] = $interval++;
			$rowAttr[0]['background'] = "#ffffff";
			$rowAttr[0]['color']      = "#000000";
			$rowAttr[0]['textAlign']  = "right";
			$rowAttr[0]['bold']       = true;

			for($day = 1; $day <= $maxDays; $day++)
			{
				if(isset($grid[$time][$day]))
				{
					$val = $grid[$time][$day];
					$rowData[$day] = $val;
					$rowAttr[$day]['background'] = $this->computeColor($val - $minV, $colorCoef);
				}
				else
				{
					$rowData[$day] = 0;
					$rowAttr[$day]['background'] = "#ffffff";
				}
				$rowAttr[$day]['color'] = "#000000";
			}
			$cl->addRow($rowData,'black','white',$rowAttr);
		}

		if(count($timeOrder)>0)
		{
			$cl->addTotal(-1,1);
			$cl->addRow(['=SUM(B'.$cl->getCurrentRow().':AF'.$cl->getCurrentRow().')'],'black','white',[['format'=>'#,###0.000','background'=>'#a7d6ff','color'=>'#000000','textAlign'=>'center','bold'=>true]]);
		}
		
		
		$cl->mergeCells();
		
		
		$this->data['checkColor'] = $this->importActualModel->checkForecastData($this->data['date'],$customersIDs,$distributorID,$includedPODs);
		
		$mY = $this->importActualModel->get_min_max_years2(['actual_readings_1','actual_readings_2','actual_readings_3','actual_readings_4','actual_readings_5','actual_readings_6', 'actual_readings_7','actual_readings_8','actual_readings_9','actual_readings_10','actual_readings_11','actual_readings_12'],'reading_datetime');

		$PFilter = new \TimePeriodFilter('tp','actual_view_badge_readings2','Perioana',$mY['minY'],$mY['maxY'],date('Y F',$this->data['date']),'single',false);
		$PFilter->setcallJSFunction("queryReport");
		$this->data['period_filter'] = $PFilter->render();
		
		$DFilter = new \DistributorFilter('distributor_id',false);
		$this->data['distributor_filter'] = $DFilter->render();

		//$IHFilter = new \ActiveFilter('interval_filer','','',['Interval','Ora'],0);
		//$this->data['interval_filer'] = $TFilter->render();
		
		$TFilter = new \ActiveFilter('type_filer','','',['Orar','15min'],0);
		$this->data['type_filter'] = $TFilter->render();
			
		$this->data['title'] = "Raport Realizat Orar";
		$this->data['output'] = $spreadsheet->render();
		
		$this->data['jsFiles'] = ['tools.js'];
		$data['data'] = $this->data;
		
		return view('realizat/export_orar',$data);
	}
	
	function computeColor($value, $coef)
	{
		$str = '#ff'.sprintf('%02x', 255-floor($value * $coef) ).sprintf('%02x', 255-floor($value * $coef) );
		
		//echo $value * $coef; exit();
		return $str;
	}

	
}
