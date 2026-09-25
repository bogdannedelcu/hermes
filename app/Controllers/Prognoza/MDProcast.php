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

class MDProcast extends BaseController
{
	use \GridTools;
	private $data = [
        'title'   => '',
		'menu' => 'prognoza',
		'msg' => '',
		'output' => '',
		'upload' =>'',
		'div-card' => ''];
	
	protected $proCastModel;	
	protected $sqlData;
	
	protected $tColors;
	protected $tColorsSize;
	protected $tCoef;

	protected $supplierID;
	
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
		$this->proCastModel = new \App\Models\Prognoza\PCModel();
		$this->supplierID = $_SESSION['select-supplier'];
		
    }

	public function index()
	{
	}

	function computeColor($value, $coef)
	{
		
		$v = min(255,floor($value * $coef));
		$str = '#ff'.sprintf('%02x', 255-$v).sprintf('%02x', 255-$v);
		
		//echo $value * $coef; exit();
		return $str;
	}
	
	private function computerSkyColor($coverage)
	{
		
		//$color = (int)((1-$coverage)*255);
		//return "rgb(255,255,$color)";
		
		$color = (int)((1-$coverage)*255);
		return "rgb(255,$color,$color)";
	}
	
	public function ProductionUpload()
	{
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			header('Content-Type: application/json');
		
			$regionID = $_POST['region_id'];
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
						$result['msg'] = $this->import_XLS_production($destFile, $regionID);
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
	
	protected function import_XLS_production($file, $regionID)
	{
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
		$sheet = $spreadsheet->getSheet(0);
		
		$row = 1;
	
		while(true)
		{
			$row++;
			$dateText = $sheet->getCellByColumnAndRow(1, $row)->getValue();
			$interval = $sheet->getCellByColumnAndRow(2, $row)->getValue();
			$ea = $sheet->getCellByColumnAndRow(3, $row)->getValue();
								
			if (empty($dateText) || empty($interval) || $ea == '') break;
			
			log_message("error",$dateText." ".$interval.":".$ea);
			
			//$dateText = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($dateObj)->format('Y-m-d');
			$date = \DateTime::createFromFormat('Y-m-d H:i', $dateText.' '.substr($interval,0,5));
			
			/*if($duplicate && !$this->proCastModel->isProductionDuplicate($regionID, $date->format('Y-m-d H:00:00'), $ea))
				$duplicate = false;*/
								
			$this->addSQLValuesString($date, $regionID, $ea);
		} 
		
		return $this->importSQLValues();
	}
	
	protected function addSQLValuesString($date,$regionID,$ea)
	{
		$textDate = $date->format('Y-m-d H:i:00');
		$this->sqlData.="({$this->supplierID}, $regionID, '$textDate', $ea),";
	}
	
	protected function importSQLValues()
	{
		if(!empty($this->sqlData))
		{
			if(strlen($this->sqlData)>0) $this->sqlData = substr_replace($this->sqlData,'',-1);
			log_message("error",$this->sqlData);
			return $this->proCastModel->importProductionSQLValues($this->sqlData);			
		}
		
		return false;
	}
	
	public function ProductionForecast()
	{
		require_once(APPPATH . 'Libraries/ebs/FastExcel.php');
	
		$session = \Config\Services::session();
		if ($session->get('user') === NULL) return view('login.php'); 
		
		include(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php');
		
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
		
		$pcRegionsData = $this->proCastModel->getProCastRegions();
				
		$spreadsheet = new \Kendo\UI\Spreadsheet('spreadsheet');
		$spreadsheet->attr('style', 'width: 100%;');
		
		$cl = new \FastExcel($null, 'Productie Estimata');
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
		$formatter = new \IntlDateFormatter('ro_RO', IntlDateFormatter::LONG, IntlDateFormatter::NONE,null,null,"MMMM-yyyy");
		$cl->addTitle("Productie Estimata - ".ucfirst($formatter->format($this->data['date'])),$maxDays+1	,18);
		
		$cl->addHeader($weekDaysHeader,"black","rgb(167,214,255)",18,14);		
		$cl->addHeader($daysHeader,"black","rgb(167,214,255)",18,14);
		$cl->setRowValueTypes($valueTypes);
		$cl->setRowAlignment($alignment);
		
		$pcRegionName = $_GET['region'] ?? '';
		$regionID = 0;
		
		if(!empty($pcRegionName))
			$regionID = $this->proCastModel->getRegionID($pcRegionName);
		else
			if(!empty($pcRegionsData))
				$regionID = $pcRegionsData[0]['value'];
				
		$td = explode('-',$YMdate);
		$gData = $this->proCastModel->getEstimatedProduction($regionID, $td[1],$td[0]);
		
		$rowValueTypes = array();
		$rowData = array();
		
		$rowValueTypes[0] = 'time';
		for($d=1;$d<$maxDays;$d++)
			$rowValueTypes[$d] = '#,###0.000';
		
		$cl->setRowValueTypes($rowValueTypes);
		$i=0;
		
		$minV = 1000;
		$maxV = -1000;
		
		foreach($gData as $c)
		{
			if($c['ea'] < $minV)
				$minV = $c['ea'];
			
			if($c['ea'] > $maxV)
				$maxV = $c['ea'];
		}

		$colorCoef = 255/(($maxV-$minV) == 0 ? 0.000001 : ($maxV-$minV));
		
		for($h=0;$h<24;$h++)
		{
			$rowData=array();
			
			$rowData[0] = sprintf("%d", $h+1);//.':00:00';
			$rowAttr[0]['background']="#ffffff";
			$rowAttr[0]['color']="#000000";
			$rowAttr[0]['bold']=true;
			$rowAttr[0]['textAlign']="right";
			
			for($d=1;$d<=$maxDays;$d++)
			{
				$xDate = $td[0].'-'.str_pad($td[1], 2, "0", STR_PAD_LEFT).'-'.sprintf("%02d", $d).' '.sprintf("%02d", $h).':00:00';
				if(isset($gData[$i]) && $gData[$i]['prod_datetime'] == $xDate)
				{
					$rowData[$d] = floatval($gData[$i++]['ea']);
					$rowAttr[$d]['background']=$this->computeColor($rowData[$d], $colorCoef);
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
				$minRow[] = '=IFERROR(SMALL('.$col.'4:'.$col.'27, COUNTIFS('.$col.'4:'.$col.'27, "=0")+1),0)';
				$avgRow[] = '=IFERROR(AVERAGE('.$col.'4:'.$col.'27),0)';
				$maxRow[] = '=MAX('.$col.'4:'.$col.'27)';				
			}
			
			$lastRow = $cl->getCurrentRow();
			$cl->addRow($minRow);
			$cl->addRow($avgRow);
			$cl->addRow($maxRow);
			
			$cl->addRow(['=IFERROR(AVERAGE(B4:'.$cl->ColumnNumberToColumnName($maxDays).'27),0)'],'black','white',[['format'=>'#,##0.00','background'=>'#a7d6ff','color'=>'#000000','textAlign'=>'center','bold'=>true]]);
		}
			

		$cl->mergeCells();
		
		
		//upload
		$files=['.xlsx','.txt'];
		$upload = new \Kendo\UI\Upload('files[]');
		$upload->async(array(
				'saveUrl' => site_url().'/Prognoza/MDProcast/ProductionUpload',
				'removeUrl' => site_url().'/Prognoza/MDProcast/ProductionRemove',
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
		
		$pcRegions = new \TypesFilter('pc_regions',$pcRegionsData,$regionID);
		$this->data['pc_regions'] = $pcRegions->render();
		
		$mY = $this->proCastModel->get_min_max_years('procast_prod_forecast','prod_datetime');

		$PFilter = new \TimePeriodFilter('tp','procast_view_production_forecast_badges','Perioada',$mY['minY'],max($mY['maxY'],date('Y')+1), date('Y F',$this->data['date']));
		$PFilter->setcallJSFunction("queryReport");
		$this->data['period_filter'] = $PFilter->render();
				
		$this->data['title'] = "Productie Estimata";
		$this->data['output'] = $spreadsheet->render();
		$this->data['jsFiles'] = ['tools.js','prognoza/production.js'];
		
		$data['data'] = $this->data;
		
		return view('prognoza/content',$data);
	}
	
	public function TypicalProduction()
	{
		require_once(APPPATH . 'Libraries/ebs/FastExcel.php');
	
		$session = \Config\Services::session();
		if ($session->get('user') === NULL) return view('login.php'); 
		
		include(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php');
		
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
		
		$pcRegionsData = $this->proCastModel->getProCastRegions();
				
		$spreadsheet = new \Kendo\UI\Spreadsheet('spreadsheet');
		$spreadsheet->attr('style', 'width: 100%;');
		
		$cl = new \FastExcel($null, 'Productie Caracteristica');
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
		$formatter = new \IntlDateFormatter('ro_RO', IntlDateFormatter::LONG, IntlDateFormatter::NONE,null,null,"MMMM");
		$cl->addTitle("Productie Caracteristica - ".ucfirst($formatter->format($this->data['date'])),$maxDays+1	,18);
		
		$cl->addHeader($weekDaysHeader,"black","rgb(167,214,255)",18,14);		
		$cl->addHeader($daysHeader,"black","rgb(167,214,255)",18,14);
		$cl->setRowValueTypes($valueTypes);
		$cl->setRowAlignment($alignment);
		
		$pcRegionName = $_GET['region'] ?? '';
		$regionID = 0;
		
		if(!empty($pcRegionName))
			$regionID = $this->proCastModel->getRegionID($pcRegionName);
		else
			if(!empty($pcRegionsData))
				$regionID = $pcRegionsData[0]['value'];
				
		$td = explode('-',$YMdate);
		$gData = $this->proCastModel->getTypicalProduction($regionID, $td[1]);
		
		log_message('error',print_r($gData,true));
		
		$rowValueTypes = array();
		$rowData = array();
		
		$rowValueTypes[0] = 'time';
		for($d=1;$d<$maxDays;$d++)
			$rowValueTypes[$d] = '#,###0.000';
		
		$cl->setRowValueTypes($rowValueTypes);
		$i=0;
		
		$minV = 1000;
		$maxV = -1000;
		
		foreach($gData as $c)
		{
			if($c['ea'] < $minV)
				$minV = $c['ea'];
			
			if($c['ea'] > $maxV)
				$maxV = $c['ea'];
		}

		$colorCoef = 255/(($maxV-$minV) == 0 ? 0.000001 : ($maxV-$minV));
		
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
				if(isset($gData[$i]) && $gData[$i]['hour'] == $h && $gData[$i]['day'] == $d)
				{
					$rowData[$d] = floatval($gData[$i++]['ea']);
					$rowAttr[$d]['background']=$this->computeColor($rowData[$d], $colorCoef);
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
				$minRow[] = '=IFERROR(SMALL('.$col.'4:'.$col.'27, COUNTIFS('.$col.'4:'.$col.'27, "=0")+1),0)';
				$avgRow[] = '=IFERROR(AVERAGE('.$col.'4:'.$col.'27),0)';
				$maxRow[] = '=MAX('.$col.'4:'.$col.'27)';				
			}
			
			$lastRow = $cl->getCurrentRow();
			$cl->addRow($minRow);
			$cl->addRow($avgRow);
			$cl->addRow($maxRow);
			
			$cl->addRow(['=IFERROR(AVERAGE(B4:'.$cl->ColumnNumberToColumnName($maxDays).'27),0)'],'black','white',[['format'=>'#,##0.00','background'=>'#a7d6ff','color'=>'#000000','textAlign'=>'center','bold'=>true]]);
		}
			

		$cl->mergeCells();
		
		
		//upload
		$files=['.xlsx','.txt'];
		$upload = new \Kendo\UI\Upload('files[]');
		$upload->async(array(
				'saveUrl' => site_url().'/Prognoza/MDProcast/upload',
				'removeUrl' => site_url().'/Prognoza/MDProcast/remove',
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
		
		$pcRegions = new \TypesFilter('pc_regions',$pcRegionsData,$regionID);
		$this->data['pc_regions'] = $pcRegions->render();
		
		$PFilter = new \TimePeriodFilter('tp','procast_view_typical_badges','Perioada',2024,2024,date("2024-{$td[1]}-01")); //an bisect!
		$PFilter->setcallJSFunction("queryReport");
		$this->data['period_filter'] = $PFilter->render();
				
		$this->data['title'] = "Productie Caracteristica";
		$this->data['output'] = $spreadsheet->render();
		$this->data['jsFiles'] = ['tools.js','prognoza/production_typical.js'];
		
		$data['data'] = $this->data;
		
		return view('prognoza/content',$data);
	}
	
	public function MinProduction()
	{
		require_once(APPPATH . 'Libraries/ebs/FastExcel.php');
	
		$session = \Config\Services::session();
		if ($session->get('user') === NULL) return view('login.php'); 
		
		include(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php');
		
		$pcRegionsData = $this->proCastModel->getProCastRegions();
				
		$spreadsheet = new \Kendo\UI\Spreadsheet('spreadsheet');
		$spreadsheet->attr('style', 'width: 100%;');
		
		$cl = new \FastExcel($null, 'Productie Minima');
		$cl->setFrozen(2,0);
		$spreadsheet->addSheet($cl->getSheet());
		
		$monthsHeader = ['Interval'];
		$valueType = ['Text'];
		$alignment = ['left'];
		$colWidth =[60];
		
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
		$cl->addTitle("Valori Minime Productie",13,14)->height(20);
		
		$cl->addHeader($monthsHeader,"black","rgb(167,214,255)",18,14);
		$cl->setRowValueTypes($valueTypes);
		$cl->setRowAlignment($alignment);
		
		$gData = $this->proCastModel->getMinProduction();
		
		if(count($gData) == 288)
		{
			$rowValueTypes = array();
			$rowData = array();
			
			
			$rowValueTypes[0] = '#0';
			for($d=1;$d<=12;$d++)
				$rowValueTypes[$d] = '#,###0.000';
			
			$cl->setRowValueTypes($rowValueTypes);
			$i=0;
			
			$minV = 1000;
			$maxV = -1000;
			
			foreach($gData as $c)
			{
				if($c['ea'] < $minV)
					$minV = $c['ea'];
				
				if($c['ea'] > $maxV)
					$maxV = $c['ea'];
			}
			
			$colorCoef = 255/(($maxV-$minV) == 0 ? 0.000001 : (($maxV-$minV) < 1 ? 1 : ($maxV-$minV)));
			
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
					$rowData[$d] = floatval($gData[$i++]['ea']);
					$rowAttr[$d]['background']=$this->computeColor($rowData[$d], $colorCoef);
					$rowAttr[$d]['color']="#000000";
				}
				
				$cl->addRow($rowData,'black','white',$rowAttr);
			}
			

			$cl->addTotal(-1,1);
			$cl->addRow(['=IFERROR(SUM(B3:'.$cl->ColumnNumberToColumnName(12).'26),0)'],'black','white',[['format'=>'#,##0.00','background'=>'#a7d6ff','color'=>'#000000','textAlign'=>'center','bold'=>true]]);

				
			
			$cl->mergeCells();
		}
		
		$this->data['title'] = "Productie Minima";
		$this->data['output'] = $spreadsheet->render();
		$this->data['jsFiles'] = ['tools.js','prognoza/minprod.js'];
		
		$data['data'] = $this->data;
		
		return view('prognoza/content',$data);
	}
	
	public function AssignRegions()
	{
		$transport = $this->createGridTransport('procast_region_counties');

		$schema = $this->createGridSchema(
		['rc_id','region_id','supplier_id','region_name','county','pods','podsNo'],
		['string','number','number','string','string','string','number']);
		
		$filterItem = new \Kendo\Data\DataSourceFilterItem();
		$filterItem->field('supplier_id');
		$filterItem->operator('eq');
		$filterItem->value($_SESSION['select-supplier']);

		$dataSource = $this->createDataSource($transport,$schema);
		$dataSource->addFilterItem($filterItem);
		$dataSource->serverFiltering(true);
					
		$column1 = $this->createGridColumn('region_name','Regiune Prosumator',75);
		$column2 = $this->createGridColumn('county','Judet',75);
		$column2 = $this->createGridColumn('pods','POD-uri',75);
		$column2 = $this->createGridColumn('podsNo','Nr. POD-uri',75);

		
		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true)
			 ->dataBound('function(e) {
										//$("button.k-grid-addRC").unbind("click").bind("click",function(){ProcastRegionDialog();});
										$("button.k-grid-assignRC").unbind("click").bind("click",function(){ProcastRCDialog();});
										
									}');
					  
		//$this->addCustomOperation('addRC','Adaugă Regiune','k-icon k-i-plus');
		$this->addCustomOperation('assignRC','Alocă Judet','k-icon k-i-share');
		//$this->addCustomCommand('editRC','k-icon k-i-pencil','ProcastRCDialog');

		$column = $this->setGridEditable('popup', false, false, true, false, false);
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		

		$this->data['title'] = 'Alocare Regiuni Prosumator';
		$this->data['output'] = $this->grid->render();
		$this->data['jsFiles'] = ['tools.js','prognoza/procast_region_counties.js'];
		
		$data['data'] = $this->data;
		return view('prognoza/content',$data);
	}
	
	public function Regions()
	{
		$transport = $this->createGridTransport('procast_regions');

		$schema = $this->createGridSchema(
		['region_id','supplier_id','region_name','lat','lon','dec','az','kwp'],
		['number','number','string','number','number','number','number','number']);
		
		$filterItem = new \Kendo\Data\DataSourceFilterItem();
		$filterItem->field('supplier_id');
		$filterItem->operator('eq');
		$filterItem->value($_SESSION['select-supplier']);

		$dataSource = $this->createDataSource($transport,$schema);
		$dataSource->addFilterItem($filterItem);
		$dataSource->serverFiltering(true);
					
		$column1 = $this->createGridColumn('region_name','Regiune Prosumator',75);
		$column2 = $this->createGridColumn('rlat','Latitudine',75);
		$column3 = $this->createGridColumn('rlon','Longitudine',75);
		$column4 = $this->createGridColumn('rdec','Inclinare',75);
		$column5 = $this->createGridColumn('raz','Orientare',75);
		$column6 = $this->createGridColumn('rkwp','KWh',75);
		
		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true)
			 ->dataBound('function(e) {
										$("button.k-grid-addRC").unbind("click").bind("click",function(){ProcastRegionDialog();});
										
									}');
					  
		$this->addCustomOperation('addRC','Adaugă Regiune','k-icon k-i-plus');
		$this->addCustomCommand('editRC','k-icon k-i-pencil','ProcastRegionDialog');

		$column = $this->setGridEditable('popup', false, false, true, false, false);
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		

		$this->data['title'] = 'Regiuni Prosumator';
		$this->data['output'] = $this->grid->render();
		$this->data['jsFiles'] = ['tools.js','prognoza/region.js'];
		
		$data['data'] = $this->data;
		return view('prognoza/content',$data);
	}
	
	/*TRANSELECTRICA*/
	public function Transelectrica()
	{
		$session = \Config\Services::session();
		if ($session->get('user') === NULL) return view('login.php'); 
		
		include(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php');
	
		
		//upload
		$files=['.xlsx','.txt'];
		$upload = new \Kendo\UI\Upload('files[]');
		$upload->async(array(
				'saveUrl' => site_url().'/Prognoza/MDProcast/upload',
				'removeUrl' => site_url().'/Prognoza/MDProcast/remove',
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
				
		$view_types = new \TypesFilter('counties',coduriJudete,'RO');
		$this->data['counties'] = $view_types->render();
		
		$PFilter = new \TimePeriodFilter('tp','procast_view_transelectrica_badges','Perioada',2024,date('Y')+1); 
		$PFilter->setcallJSFunction("queryReport");
		$this->data['period_filter'] = $PFilter->render();
				
		$this->data['title'] = "Transelectrica";
		$this->data['output'] = '<div id="spreadsheet" class="w-100"></div>';
		$this->data['jsFiles'] = ['tools.js','excel_tools.js','prognoza/transelectrica.js'];
		
		$data['data'] = $this->data;
		
		return view('prognoza/content',$data);
	}
	
	public function PISEN()
	{
		$session = \Config\Services::session();
		if ($session->get('user') === NULL) return view('login.php'); 
		
		include(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php');
		
		$PFilter = new \TimePeriodFilter('tp','procast_view_transelectrica_pisen_badges','Perioada',2024,date('Y')); 
		$PFilter->setcallJSFunction("queryReport");
		$this->data['period_filter'] = $PFilter->render();
		
		$this->data['title'] = "Puterea Instalata SEN";
		$this->data['output'] = '<div id="spreadsheet" class="w-100"></div>';
		$this->data['jsFiles'] = ['tools.js','excel_tools.js','prognoza/pisen.js'];
		
		$data['data'] = $this->data;
		
		return view('prognoza/content',$data);
	}
}
	