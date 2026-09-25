<?php 
namespace App\Controllers\Realizat;
use App\Controllers\BaseController;

//use PhpOffice\PhpSpreadsheet\Spreadsheet;
//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use CodeIgniter\Database\Query;
use DateTime;
use DateTimeZone;
use DateInterval;

require_once(__DIR__ .'/../tools.php');
require_once(__DIR__ .'/../GridTools.php');

class ImportReadings extends BaseController
{
	use \GridTools;
	private $data = [
        'title'   => 'Import Date Orare',
		'msg' => '',
		'output' => '',
		'upload' =>'',
		'div-card' => 'card-realizat',
		'menu' => 'consumuri'];
	
	private $sqlValues = '';
	private $sqlValuesLines = 0;
	private $qIDs = [];
	
	protected $importActualModel;
	protected $spreadsheet = NULL;
	
	
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
	
	public function index()
	{
		//grid
		$this->viewImportResult();
		
		$hasNewData = $this->importActualModel->hasNewData();
		
		//upload
		$files=['.xlsx','.pdf','.xlsm'];
		$upload = new \Kendo\UI\Upload('files[]');
		$upload->async(array(
				'saveUrl' => site_url().'/Realizat/ImportReadings/upload',
				'removeUrl' => site_url().'/Realizat/ImportReadings/remove',
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
	   
		//stepper
		$stepper = new \Kendo\UI\Stepper('stepper');
        $stepper->linear(false);
				//->activate('onStepper'); handled by onclick

        $step1 = new \Kendo\UI\StepperStep();
        $step1->label("Reset")
			->selected(!$hasNewData)
            ->icon("trash");
		
		$step2 = new \Kendo\UI\StepperStep();
        $step2->label("Incarca fisiere")
            ->icon("attachment");

        $step3 = new \Kendo\UI\StepperStep();
        $step3->label("Verifica")
		    ->selected($hasNewData)
            ->icon("preview")
            ->error(true);

        $step4 = new \Kendo\UI\StepperStep();
        $step4->label("Salveaza Date Orare")
            ->icon("file-add");
		
        $stepper->addStep($step1);
        $stepper->addStep($step2);
		$stepper->addStep($step3);
		$stepper->addStep($step4);

        $this->data['stepper'] =  $stepper->render();
	   
	    $data['data'] = $this->data;
		return view('realizat/import_readings',$data);
	}

	public function viewImportResult()
	{
		
		$transport = $this->createGridTransport('actual_data');

		$schema = $this->createGridSchema([
		'data_id','supplier_name','distributor_name','reading_datetime','curve_name','curve_type','actual_ea',
		'customer_name','customer_code','incompleteData','error'],
		['number','string','string','string','string','string','number'
		,'string','string','number','string'],null,['error']);
		
		
		$sum1 = new \Kendo\Data\DataSourceAggregateItem();
		$sum1->field('actual_ea')
			 ->aggregate("sum");
		
		$group = new \Kendo\Data\DataSourceGroupItem();
		$group->field('customer_name')
				->addAggregate($sum1);
	  		
		$dataSource = new \Kendo\Data\DataSource();
		
		$dataSource->transport($transport)
				   ->pageSize(1000)
				   ->batch(true)
				   ->schema($schema)
				   //->addAggregateItem($sum1)
				   //->addGroupItem($group)
				   ->serverGrouping(true);
		
		$filterItemS = new \Kendo\Data\DataSourceFilterItem();
		$filterItemS->field('supplier_id');
		$filterItemS->operator('eq');
		$filterItemS->value($_SESSION['select-supplier']);
			
		$dataSource->addFilterItem($filterItemS);
		$dataSource->serverFiltering(true);
		$dataSource->serverPaging(true);
		$dataSource->serverSorting(true);
		
		$column1 = $this->createGridColumn('distributor_name','Distribuitor',80);
		$column3 = $this->createGridColumn('customer_name','Client',80);
		//$column3->groupHeaderTemplate("#=data.value# EA:<span class='text-danger'>#=kendo.format('{0:N3}', aggregates.actual_ea.sum )# MWh</span>");
		
		$column31 = $this->createGridColumn('customer_code','Cod Client',80);
		
		$column4 = $this->createGridColumn('curve_type','Tip Curba',50);
		//$column5->groupHeaderTemplate("#=data.value# EA:<span class='text-danger'>#=kendo.format('{0:N3}', aggregates.actual_ea.sum )# MWh</span>");		
		
		$column5 = $this->createGridColumn('curve_name','Curba',50);
		//$column4->groupHeaderTemplate("#=data.value# EA:<span class='text-danger'>#=kendo.format('{0:N3}', aggregates.actual_ea.sum )# MWh</span>");		
				
		$column6 = $this->createGridColumn('reading_datetime','Interval',80);
		$column6->template("#=(data.error != '' || data.incompleteData == 1) ? '<div class=\"text-danger\">'+reading_datetime+'</div>' : reading_datetime#");
		
		$column7 = $this->createGridColumn('actual_ea','EA (MWh)',50);
		$column7->template("#=kendo.format('{0:N3}',data.actual_ea)#");
				//->aggregates('sum');
				
		$column8 = $this->createGridColumn('error','Eroare',80);
		$column8->template("<div class=\"text-danger\">#=(data.error == '' && data.incompleteData == 1) ? 'Date Incomplete' : error#</div>");
		//->template("#=(data.error =='POD nou') ? ('<button class=\"k-button k-button-solid-primary k-rounded-md\" onClick=createPODDialog(\"'+ data.customer_name.replace(/\s/g, '_') +'\",\"'+ pod.trim() +'\");>'+data.error+'</button>') : ((data.error.startsWith('Consum facturat')) ? '<a class=\"k-button k-button-solid-error k-rounded-md a-btn \" target=\"_blank\" href=\"' + window.location.origin +'/PdfInvoice?invoiceId='+data.error.slice(16)+'\">Verifica</a>' : data.error) #");
			 
		$this->grid->height(550)
				 ->dataSource($dataSource)
				 ->resizable(true);
		$this->addCustomCommand('editR','k-icon k-i-pencil','editReadings');
		$column = $this->setGridEditable('popup', false, false, true, false, true);
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		$this->data['jsFiles'] = ['tools.js','import_readings.js'];
		$this->data['output'] = $this->grid->render();
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

	private function importFile($file)
	{
		$msg = '';
		$pdfText = '';
		$fileInfo = pathinfo($file);
		//$sConsumtions = $this->importConsumptionsModel->countConsumptions();
		
		if(in_array(strtolower($fileInfo['extension']), array('xlsx','xlsm')))
		{		
			$rows_imported = 0;
			$err_msg='';
			
			switch($this->getXLSxtype($file))
			{
				case 'ENEL':
					$result = $this->import_ENEL($file);		
				break;
				case 'DEER_TN':
				case 'DEER_TS': 
				case 'DEER_MN':
					$result = $this->import_DEER_TN_TS_MN($file);
				break;
				case 'DEER_TS2':
				case 'DEER_MN2':
				case 'DEER_TN2':
					$result = $this->import_DEER_TN_TS_MN2($file);
				break;
				case 'DELGAZ':
					$result = $this->import_DELGAZ($file);
				break;
				case 'OLTENIA':
					$result = $this->import_OLTENIA($file);
				break;
				default:
					$result = -110;
			}			
		
			$sqlQueue = $this->importActualModel->getSqlQueue();
			$sqlQueue->waitFor($this->qIDs);

			if (gettype($result)=='integer')
			{	
				$err = $result;
				$result = ['rows' => 0, 'imported'=>0,'fileInfo'=>'Fisier import'];					
				$msg = 'Eroare '.$err;
			}
			
			if (!empty($result['cleanup_dup']))
				$cd_msg=' '.$result['cleanup_dup'].' totaluri duplicate in pdf.';
			else
				$cd_msg = '';
			
			if (empty($result['cleanup']))
				$msg = $msg.'<br><b>'.$result['fileInfo'].'</b> '.$fileInfo['filename'].' contine '.$result['rows'].' inregistrari.';// din care <b>'.$result['imported'].'</b> au fost importate! '.$cd_msg.' '.'<b style="color:red">'.$err_msg.'</b>';						 
			else
				$msg = $msg.'<br>'.$result['fileInfo'].' '.$fileInfo['filename'].' contine '.$result['rows'].' inregistrari.';// din care <b>'.($result['imported']-$result['cleanup']).'</b> au fost importate ('.$result['cleanup'].' consumuri zero ignorate.'.$cd_msg.' )!'.$err_msg;						 	
								
		
			/*$nConsumtions = $this->importConsumptionsModel->countConsumptions() - $sConsumtions;
			if ($nConsumtions == 0)
				$msg = $fileInfo['filename'].' nu are consumuri noi';
			else
				$msg = $fileInfo['filename'].' contine '.$nConsumtions.' consumuri';*/
		}
		else
		{
			$msg = 'Eroare: Va rugam sa selectati un fisier acceptat!';
		}
		
		return $msg;
	}

	public function getXLSxtype($fileName)
	{
		log_message('error','getXLSxtype:'.$fileName);
		$this->spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileName);
		
		$spreadsheet = $this->spreadsheet;
        
        $sheet = $spreadsheet->getSheet(0); //getActiveSheet();
		$distributor_name = $sheet->getCellByColumnAndRow(1, 1)->getValue();
		
		if(strtolower(trim($distributor_name))=='distribuitor' || $distributor_name=='')
			$distributor_name = $sheet->getCellByColumnAndRow(2, 2)->getValue(); //Oltenia
		
		log_message("debug","Sheet Name:".$this->spreadsheet->getSheetNames()[0]);
		if($this->spreadsheet->getSheetCount()>=2 && str_contains(strtolower($this->spreadsheet->getSheetNames()[0]),'coeficienti')) return 'DELGAZ';
		
		if(empty($distributor_name))
			$distributor_name = $sheet->getCellByColumnAndRow(2, 2)->getValue();

		if ($distributor_name == 'DATA') return 'ENEL';
		//elseif (strpos($distributor_name,'Distributie Energie Electrica Romania S.A. Zona Transilvania Nord') !== FALSE) 
		elseif (str_contains(strtolower($distributor_name), 'distributie energie electrica')) 
		{
			$ddata = substr($sheet->getCellByColumnAndRow(4, 4)->getValue(),0,5);
			
			if($ddata == '59403') return 'DEER_MN2'; //OK
			elseif($ddata == '59402') return 'DEER_TS2';//doar la covasna 
				
			return 'DEER_TN2';
		}//DEER_TS,DEER_MN, DEER_TN trebuie scoase
		elseif (strpos($distributor_name,'DEER zona Transilvania Sud') !== FALSE) return 'DEER_TS';
		elseif (strpos($distributor_name,'Zona Muntenia Nord') !== FALSE) return 'DEER_MN';
		elseif (strpos($distributor_name,'Zona Transilvania Nord') !== FALSE) return 'DEER_TN';
		elseif (strpos($distributor_name,'OLTENIA') !== FALSE) return 'OLTENIA';

		return false;
	}
	
	private function str_contains_any($haystack, array $needles, bool $caseSensitive = false): bool
	{
		if ($haystack === null) return false;
		foreach ($needles as $needle) {
			if ($needle === '') continue;
			if ($caseSensitive) {
				if (mb_strpos($haystack, $needle) !== false) return true;
			} else {
				if (mb_stripos($haystack, $needle) !== false) return true;
			}
		}
		return false;
	}

	public function import_ENEL($fileName)
	{	
		
		$spreadsheet = $this->spreadsheet;
        
        $sheet = $spreadsheet->getSheet(0); //getActiveSheet();
		$file_info = 'Fisier import date orare';
		$affected = 0;
		$row = 1;
		
		$distributor_name = $sheet->getCellByColumnAndRow(1, 1)->getValue();
		
		if($distributor_name == 'DATA')
			for($i = 2;$i<10;$i++)
			{
				$curveName = $sheet->getCellByColumnAndRow($i, 1)->getValue();
				
				if($this->str_contains_any($curveName,['RELDG','R_TL','R_CT','R_CL','R_IL'])) $distributor_name = 'RETELE ELECTRICE DOBROGEA S.A.';
				elseif($this->str_contains_any($curveName,['RELMS','R_B_','R_IF','R_GR'])) $distributor_name = 'RETELE ELECTRICE MUNTENIA S.A.';
				elseif($this->str_contains_any($curveName,['RELBN','R_CS','R_CT','R_AR'])) $distributor_name = 'RETELE ELECTRICE BANAT S.A.';
				else 
					continue;
				
				break;
			}

		log_message('error', 'Import readings for '.$distributor_name);
		//find total row
		$totalRow = 1;
		
		while( $sheet->getCellByColumnAndRow(1, ++$totalRow)->getValue() != 'Total' && $sheet->getCellByColumnAndRow(1, ++$totalRow)->getValue() != '');
		$totalRow--;
		
		$row = 1;$cleanup_dup = 0;
		$this->initSQLValuesString();
		
		if(!empty($distributor_name) && $distributor_name!='DATA'){
			while (true) {
				
				$row++;
				
				$day = $sheet->getCellByColumnAndRow(1, $row)->getValue();
				if(!empty($day) && gettype($day) == 'string') $day = trim($day);
				
				
				if( $day == 'Total' || empty($day)) break;
				
				$tdatetime = \DateTime::createFromFormat('d.m.Y H:i', $day ?? 0);
				if(gettype($tdatetime) == 'boolean') 
				{
					//\PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($day)->format('Y-m-d ');
					$timestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($day);
					$tdate = new DateTime();
					$tdate->setTimestamp($timestamp);
										
					$hour = $sheet->getCellByColumnAndRow(2, $row)->getValue();
					$timestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($hour);
					$thour = new DateTime();
					$thour->setTimestamp($timestamp);
					$thour->setTimezone(new DateTimeZone("UTC")); 
					
					$reading_datetime = $tdate->format('Y-m-d').' '. $thour->format('H:i:00');
					$col=3;				
					
					//log_message('error','Date converted on row '.$row.':'.$day.' to '.$reading_datetime);
//					continue;
				}
				else
				{
					$col=2;	
					$reading_datetime = $tdatetime->format('Y-m-d H:i:s');
				}

				
				
				while(true) {
									
					$error = '';
					
					$curve_type = 'sintetica';
					$customer_name = $customer_code = "";
					$curve_name = $sheet->getCellByColumnAndRow($col, 1)->getValue();					
					
					if ( $curve_name=='Total' || empty($curve_name) ) break; //end of columns for ENEL

					$curve_name = str_replace('_','',$curve_name);
					if ($sheet->getCellByColumnAndRow($col, $totalRow)->getValue() == 0)
					{
						$col++;
						continue; //. ignore zero totals!
					}
					
					$actual_ea = $sheet->getCellByColumnAndRow($col, $row)->getValue();
					
						
					$result = $this->importActualModel->checkEnelCurveData($distributor_name,$curve_name,$reading_datetime);
					
					$this->addSQLValuesString($distributor_name, $reading_datetime, $curve_name, $result['curve_type'], $actual_ea,  $result['customer_name'], $customer_code, $result['customer_id'], $result['curve_id'], $result['error']);
					
					if($this->sqlValuesLines > 1000)
					{
						$affected += $this->importSQLValues();
						$this->initSQLValuesString();
					}
					
					$col++;
				}
				
			}
			
			$affected += $this->importSQLValues();
			$this->initSQLValuesString();
				
		}
	
		$this->importActualModel->cleanZeroConsumption();
		return ['rows' => $row-6, 'imported' => $affected,'fileInfo' => $file_info];
		
	}
	
	public function import_DEER_TN_TS_MN($fileName)
	{	
	
		$spreadsheet = $this->spreadsheet;
        
        $sheet = $spreadsheet->getSheet(0); //getActiveSheet();
		$file_info = 'Fisier import date orare';
		$affected = 0;

		$distributor_name = $sheet->getCellByColumnAndRow(1, 1)->getValue();
		
		//if (in_array($distributor_name,array('Distributie En. El. Romania - Zona TN','Distributie Energie Electrica Romania-TS')))
		
		// de
		if ($distributor_name == 'Distributie Energie Electrica Romania S.A. Zona Transilvania Nord')
			$distributor_name = 'DEER Transilvania Nord';
		elseif ($distributor_name == 'DEER zona Transilvania Sud')
			$distributor_name = 'DEER Transilvania Sud';
		elseif ($distributor_name == 'Distributie Energie Electrica Romania S.A. Zona Muntenia Nord')
			$distributor_name = 'DEER Muntenia Nord';
		else
			$distributor_name = '';
		
		
		//find total row
		$totalRow = 1;
		
		while( $sheet->getCellByColumnAndRow(1, ++$totalRow)->getValue() != 'Total');
		
		
		$row = 6;$cleanup_dup = 0;
		$this->initSQLValuesString();
		
		if(!empty($distributor_name)){
			while (true) {
				
				$row++;
				
				$day = $sheet->getCellByColumnAndRow(1, $row)->getValue();
				if($day == 'Total' || empty($day)) break;
				
				if ($distributor_name == 'DEER Transilvania Sud' || $distributor_name == 'DEER Muntenia Nord')
				{
					$year_month=date("Ym", strtotime("-1 months"));
					
					if(!empty($_SESSION['actual-data-date']))
					{
						$ym = explode('-',$_SESSION['actual-data-date']);
						$year_month = $ym[1].$ym[0];
					}					

					$reading_datetime = $this->formatDateText($year_month.$day.':'. $sheet->getCellByColumnAndRow(2, $row)->getValue().'00','Ymj:His','Y-m-d H:i:s');
					
					$col=3;
				}
				elseif($distributor_name == 'DEER Transilvania Nord')
				{
					$minArray = $sheet->getCellByColumnAndRow(3, $row)->getValue();
					$time = explode('-',$minArray)[0];
					$tdatetime = \DateTime::createFromFormat('d.m.YHi', $sheet->getCellByColumnAndRow(1, $row)->getValue().$time ?? 0);
										
					$reading_datetime = $tdatetime->format('Y-m-d H:i:s');
				
					$col=4;
				}				
				
				
				while(true) {
									
					$error = '';
					
					if($sheet->getCellByColumnAndRow($col, 6)->getValue() == 'CS mas') $curve_type = 'masurata';
					elseif ($sheet->getCellByColumnAndRow($col, 6)->getValue() == 'CS sin') $curve_type = 'sintetica';
					elseif ($sheet->getCellByColumnAndRow($col, 6)->getValue() == 'CS prod')
					{
						$col++;
						continue;
					}
					else
					{
						$curve_type = $sheet->getCellByColumnAndRow($col, 6)->getValue();
						if(empty($curve_type) && ($distributor_name != 'DEER Transilvania Nord') ) break;
						
						else $error = 'Tip curba necunoscut';
					}
					
					if ($sheet->getCellByColumnAndRow($col, 2)->getValue() instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText)
						$cn = $sheet->getCellByColumnAndRow($col, 2)->getValue()->getPlainText();
					else
						$cn = $sheet->getCellByColumnAndRow($col, 2)->getValue();
					
					if ($sheet->getCellByColumnAndRow($col, 3)->getValue() instanceof PHPExcel_RichText)
						$cc = $sheet->getCellByColumnAndRow($col, 3)->getValue()->getPlainText();
					else
						$cc = $sheet->getCellByColumnAndRow($col, 3)->getValue();
					
					if(!empty($cn))
					{
						$customer_name = $cn;
						$customer_code = $cc;
					} //else keep old values!
					else/*if ($distributor_name == 'DEER Transilvania Nord')*/
					{
						$customer_name = $customer_code = "";
					}
					
					$curve_name = $sheet->getCellByColumnAndRow($col, 4)->getValue();
					if(substr($curve_name,0,5) == '00000') $curve_name = substr($curve_name,5);
											
					//rewrite synthetic curves
					if ($distributor_name == 'DEER Transilvania Sud' && $curve_type == 'sintetica')
					{
						$ident = strpos($curve_name, 'ETS');
						if($ident>0)
						{
							if(str_contains($curve_name,'rezidual'))
								$curvePrefix = 'REZ';
							else
								$curvePrefix = 'SIN';
							
							$curve_name = $curvePrefix.'-'.substr($curve_name, $ident+3, 3);
						}
					}
					elseif ($distributor_name == 'DEER Muntenia Nord' && $curve_type == 'sintetica')
					{
						$ident = strpos($curve_name, 'ETS');
						if($ident>0)
							$curve_name = 'SIN-'.substr($curve_name, $ident+3, 3);
					}
					
					if ($curve_name=='Totaluri') break; //end of columns for TS & MN
					elseif ($curve_name == 'TOTAL Masurat+Sintetic' || $curve_name == 'Total sintetic' || empty($curve_name)) break; //only for TN
					elseif ($curve_name == 'Total Masurat') 
					{
						$col++;
						continue;//only for TN
					}
					
					if ($sheet->getCellByColumnAndRow($col, $totalRow)->getValue() == 0)
					{
						$col++;
						continue; //. ignore zero totals!
					}
					
					$actual_ea = $sheet->getCellByColumnAndRow($col, $row)->getValue();
					
					//	$result = $this->importActualModel->checkCurveData($distributor_name,$this->cleanString($curve_name),$reading_datetime, $curve_type, $customer_name, $customer_code);
					$result = $this->importActualModel->checkEnelCurveData($distributor_name,$this->cleanString($curve_name),$reading_datetime);
					
					$this->addSQLValuesString($distributor_name, $reading_datetime, $curve_name, $curve_type, $actual_ea,  $result['customer_name'], $customer_code, $result['customer_id'], $result['curve_id'], $result['error']);
					
					if($this->sqlValuesLines > 1000)
					{
						$affected += $this->importSQLValues();
						$this->initSQLValuesString();
					}
					
					$col++;
				}
				
			}
			
			$affected += $this->importSQLValues();
			$this->initSQLValuesString();
				
		}
	
		$this->importActualModel->cleanZeroConsumption();
		return ['rows' => $row-6, 'imported' => $affected,'fileInfo' => $file_info];
	}

	public function import_DEER_TN_TS_MN2($fileName)
	{	
	
		$spreadsheet = $this->spreadsheet;
        
        $sheet = $spreadsheet->getSheet(0); //getActiveSheet();
		$file_info = 'Fisier import date orare';
		$affected = 0;

		$distributor_name = $sheet->getCellByColumnAndRow(1, 1)->getValue();
		
		//if (in_array($distributor_name,array('Distributie En. El. Romania - Zona TN','Distributie Energie Electrica Romania-TS')))
		//if ($distributor_name == 'Distributie Energie Electrica Romania S.A. Zona Transilvania Nord')
		
	if (str_contains(strtolower($distributor_name), 'distributie energie electrica'))
		{
			$ddata = substr($sheet->getCellByColumnAndRow(4, 4)->getValue(),0,5);
			
			if($ddata == '59403') $distributor_name = 'DEER Muntenia Nord';
			elseif($ddata == '59402') $distributor_name = 'DEER Transilvania Sud';
			elseif($ddata == '59404') $distributor_name = 'DEER Transilvania Nord';
			else $distributor_name = 'DEER Transilvania Sud';
		}
		else
			$distributor_name = '';
		
		log_message('error', 'Import readings for '.$distributor_name);
		//find total row
		$totalRow = 1;
		
		while( $sheet->getCellByColumnAndRow(1, ++$totalRow)->getValue() != 'Total');
		
		
		$row = 6;$cleanup_dup = 0;
		$this->initSQLValuesString();
		
		$h=0;$m=0;$rowDate="";
		if(!empty($distributor_name)){
			while (true) {
				
				$row++;
				
				$day = $sheet->getCellByColumnAndRow(1, $row)->getValue();
				if($day == 'Total' || empty($day)) break;
				

				if($sheet->getCellByColumnAndRow(1, $row)->getValue() != $rowDate)
				{
					$rowDate = $sheet->getCellByColumnAndRow(1, $row)->getValue();
					$h = 0; $m = 0;
				}
				else
				{
					$interval = $sheet->getCellByColumnAndRow(3, $row)->getValue();
					if(!empty($interval))
					{
						$h = substr($interval,0,2);
						$m = substr($interval,2,2);	
					}
					else
					{
						$m+=15;
						if($m > 45)
						{
							$h++;
							$m = 0;
						}
					}
				}
				//log_message("error",$rowDate.str_pad($h,2,"0").str_pad($m,2,"0"));
				$tdatetime = \DateTime::createFromFormat('d.m.YHi', $rowDate.str_pad($h,2,"0",STR_PAD_LEFT).str_pad($m,2,"0",STR_PAD_LEFT));									
				$reading_datetime = $tdatetime->format('Y-m-d H:i:s');
			
				$col=4;
				
				while(true) {	
									
					$error = '';
					
					if($sheet->getCellByColumnAndRow($col, 6)->getValue() == 'CS mas') $curve_type = 'masurata';
					elseif ($sheet->getCellByColumnAndRow($col, 6)->getValue() == 'CS sin') $curve_type = 'sintetica';
					elseif ($sheet->getCellByColumnAndRow($col, 6)->getValue() == 'CS prod')
					{
						$col++;
						continue;
					}
					else
					{
						$curve_type = $sheet->getCellByColumnAndRow($col, 6)->getValue();
						if(empty($curve_type)) $error = 'Tip curba necunoscut';
					}
					
					if ($sheet->getCellByColumnAndRow($col, 2)->getValue() instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText)
						$cn = $sheet->getCellByColumnAndRow($col, 2)->getValue()->getPlainText();
					else
						$cn = $sheet->getCellByColumnAndRow($col, 2)->getValue();
					
					if ($sheet->getCellByColumnAndRow($col, 3)->getValue() instanceof PHPExcel_RichText)
						$cc = $sheet->getCellByColumnAndRow($col, 3)->getValue()->getPlainText();
					else
						$cc = $sheet->getCellByColumnAndRow($col, 3)->getValue();
					
					if(!empty($cn))
					{
						$customer_name = $cn;
						$customer_code = $cc;
					} //else keep old values!
					else
					{
						$customer_name = $customer_code = "";
					}
					
					$curve_name = $sheet->getCellByColumnAndRow($col, 4)->getValue();
					
					if(substr($curve_name,0,5) == '00000') $curve_name = substr($curve_name,5);
					
					if ($curve_name == 'TOTAL Masurat+Sintetic' || $curve_name == 'Total sintetic' || empty($curve_name)) break; //only for TN
					elseif ($curve_name == 'Total Masurat') 
					{
						$col++;
						continue;//only for TN
					}
					
					if ($sheet->getCellByColumnAndRow($col, $totalRow)->getValue() == 0)
					{
						$col++;
						continue; //. ignore zero totals!
					}
					
					$actual_ea = $sheet->getCellByColumnAndRow($col, $row)->getValue();
					
					//	$result = $this->importActualModel->checkCurveData($distributor_name,$this->cleanString($curve_name),$reading_datetime, $curve_type, $customer_name, $customer_code);
					$result = $this->importActualModel->checkEnelCurveData($distributor_name,$this->cleanString($curve_name),$reading_datetime);
					
					$this->addSQLValuesString($distributor_name, $reading_datetime, $curve_name, $curve_type, $actual_ea,  $result['customer_name'], $customer_code, $result['customer_id'], $result['curve_id'], $result['error']);
					
					if($this->sqlValuesLines > 1000)
					{
						$affected += $this->importSQLValues();
						$this->initSQLValuesString();
					}
					
					$col++;
				}
				
			}
			
			$affected += $this->importSQLValues();
			$this->initSQLValuesString();
				
		}
	
		$this->importActualModel->cleanZeroConsumption();
		return ['rows' => $row-6, 'imported' => $affected,'fileInfo' => $file_info];
	}

	public function import_DELGAZ($fileName)
	{	
	
		$spreadsheet = $this->spreadsheet;
        
		$sc = $spreadsheet->getSheetCount();
		
		$file_info = 'Fisier import date orare';
		$affected = 0;
		$row = 10;
		
		if($sc >=2)
		{			
			$sheet = $spreadsheet->getSheet(1);
			$distributor_name = 'DELGAZ GRID S.A.';

			log_message('error', 'Import readings for '.$distributor_name);
			
			//find total row
			$totalRow = $row;
			
			while( $sheet->getCellByColumnAndRow(1, ++$totalRow)->getValue() != 'Total' && $sheet->getCellByColumnAndRow(1, ++$totalRow)->getValue() != '');
			$totalRow--;
			
			$row = 10;$cleanup_dup = 0;
			$this->initSQLValuesString();
			
			$monthYear=date("m-Y", strtotime("-1 months"));
					
			if(!empty($_SESSION['actual-data-date']))
				$monthYear = $_SESSION['actual-data-date'];
					
			$loopDateTime = DateTime::createFromFormat('m-Y-d H:i:s', $monthYear.'-01 00:00:00');
			
			while (true) {
				
				$row++;
				
				$day = $sheet->getCellByColumnAndRow(1, $row)->getValue();
				
				if( $day == 'Total' || empty($day)) break;
				
				$reading_datetime = $loopDateTime->format('Y-m-d H:i:s');//buggy excel! \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($day??0)->sub(DateInterval::createFromDateString('15 minutes'))->format('Y-m-d H:i:s');
				//log_message('error',$reading_datetime);
				
				//$loopDateTime->modify('+15 minutes'); //datetime change buggy
				$reading_datetime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($day??0)->sub(DateInterval::createFromDateString('15 minutes'))->format('Y-m-d H:i:s');
							
				$col=3;
				while(true) {
									
					$error = '';
					
					
					$curve_name = $sheet->getCellByColumnAndRow($col, 9)->getValue();					
					
					if ( empty($curve_name) ) 
					{
						$ptype = $sheet->getCellByColumnAndRow($col, 4)->getValue();
						if(empty($ptype)) break;
						
						if($ptype == 'SINTETIC' || $ptype == 'REZIDUAL')
						{
							if($sheet->getCellByColumnAndRow($col, 6)->getValue() == 0)
							{
								$col++;
								continue;
							}
							
							$curve_name = substr($ptype,0,3).'001-'.$sheet->getCellByColumnAndRow($col, 5)->getValue();
							$customer_name = $customer_code = "";
							$curve_type = 'sintetica';
						}
						else
						{
							$curve_name = $sheet->getCellByColumnAndRow($col, 5)->getValue();
							$customer_name = $customer_code = "";
							$curve_type = 'sintetica';
						}
					}
					else
					{
						if(!str_contains($curve_name,'_')) $curve_name = 'VIRTAGR_'.$curve_name;
						$customer_name = $sheet->getCellByColumnAndRow($col, 6)->getValue();
						$customer_code = $sheet->getCellByColumnAndRow($col, 7)->getValue(); //este pod la Moldova
						$curve_type = 'masurata';
					}
					
					if($row == 11)
					{
						log_message("debug","DateTime:$reading_datetime Curve:$curve_name");
					}
					
					$actual_ea = $sheet->getCellByColumnAndRow($col, $row)->getValue();
					if($actual_ea == null) $actual_ea = 0;
						
					$result = $this->importActualModel->checkCurveData($distributor_name,$this->cleanString($curve_name),$reading_datetime, $curve_type, $customer_name, $customer_code);
				
					$this->addSQLValuesString($distributor_name, $reading_datetime, $curve_name, $curve_type, $actual_ea,  $result['customer_name'], $customer_code, $result['customer_id'], $result['curve_id'], $result['error']);
					
					if($this->sqlValuesLines > 1000)
					{
						$affected += $this->importSQLValues();
						$this->initSQLValuesString();
					}
					
					$col++;
				}
				
			}
			
				
			$affected += $this->importSQLValues();
			$this->initSQLValuesString();
				
		}
		
		$this->importActualModel->cleanZeroConsumption();
		return ['rows' => $row-6, 'imported' => $affected,'fileInfo' => $file_info];
		
	}

	public function import_OLTENIA($fileName)
	{	
	
		$spreadsheet = $this->spreadsheet;
        
		$sc = $spreadsheet->getSheetCount();
		$sn = $spreadsheet->getSheetNames();
		$file_info = 'Fisier import date orare';
		$affected = 0;
		$row = 6;
		
		$sheetNo = array_search('Total LC',$sn,true);
		
		if($sc >= 2 && $sheetNo != false)
		{
			$sheet = $spreadsheet->getSheet(0);
			$distributor_name = trim($sheet->getCellByColumnAndRow(2, 2)->getValue() ?? '');
			
			$sheet = $spreadsheet->getSheet($sheetNo);
			
		
			log_message('info', 'Import_Oltenia readings for '.$distributor_name);
			
			
			
			//find total row
			$totalRow = $row;
			
			while( $sheet->getCellByColumnAndRow(1, ++$totalRow)->getValue() != 'Total' && $sheet->getCellByColumnAndRow(1, ++$totalRow)->getValue() != '');
			$totalRow--;
			
			$startRow = 0;
			for($r=1;$r<=10;$r++)
			{
				if(str_contains(strtolower($sheet->getCellByColumnAndRow(1, $r)->getValue()),"ora") || str_contains(strtolower($sheet->getCellByColumnAndRow(1, $r)->getValue()),"time"))
				{
					$startRow = $r;
					break;
				}
			}
			
			if($startRow == 0)
				return ['rows' => 0, 'imported' => $affected,'fileInfo' => "Fisier incorect, nu gasesc randul de start!"];
			
			$curveRow = $startRow - 2; //3
			$podRow = $startRow - 1; //4
			$row = $startRow; $cleanup_dup = 0;
			$this->initSQLValuesString();
			log_message('info', "curve row:$curveRow, podRow:$podRow");

			while (true) {
				
				$row++;
				
				$day = $sheet->getCellByColumnAndRow(1, $row)->getValue();
								
				if( $day == 'Total' || empty($day)) break;
				
				$tdatetime = \DateTime::createFromFormat('d.m.Y H:i', $day ?? 0);
				if($tdatetime == null)
					$tdatetime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($day);
				
				$tdatetime->modify("-15 minute");
				$reading_datetime = $tdatetime->format('Y-m-d H:i:s');
				//log_message('error',$day.'==>'.$reading_datetime);
				
				$col=2;
				while(true) {
									
					$error = '';
					
					$curve_name = $sheet->getCellByColumnAndRow($col, $curveRow)->getValue();					
					
					if ( empty($curve_name) ) break; 
					else
					{
						$curve_type = 'masurata'; //toate curbele sunt masurate
						$customer_name = '';
						$customer_code = $sheet->getCellByColumnAndRow($col, $podRow)->getValue(); //este pod
					}
					
					$um = $sheet->getCellByColumnAndRow($col, $startRow)->getValue();
					
					$actual_ea = $sheet->getCellByColumnAndRow($col, $row)->getValue();
					
					if(!$this->isNumber($actual_ea)) break;
					
					if($um == 'kWh' || $um == '') $actual_ea = $actual_ea / 1000;
						
					$result = $this->importActualModel->checkCurveData($distributor_name,$this->cleanString($curve_name),$reading_datetime, $curve_type, $customer_name, $customer_code);
					
					$this->addSQLValuesString($distributor_name, $reading_datetime, $curve_name, $curve_type, $actual_ea,  $result['customer_name'], $customer_code, $result['customer_id'], $result['curve_id'], $result['error']);
					
					if($this->sqlValuesLines > 1000)
					{
						$affected += $this->importSQLValues();
						$this->initSQLValuesString();
					}
					
					$col++;
				}
			
			$affected += $this->importSQLValues();
			$this->initSQLValuesString();
		
			}
		
		}
		elseif($sc >= 2 && in_array(strtolower($sn[1]), ['date id','worksheet']))
		{
			$sheet = $spreadsheet->getSheet(0);
			$distributor_name = trim($sheet->getCellByColumnAndRow(2, 2)->getValue() ?? '');
			
			$sheet = $spreadsheet->getSheet(1);
					
		
			log_message('error', 'Import_Oltenia readings for '.$distributor_name);
			
			
			
			//find total row
			$totalRow = $row;
			
			while( $sheet->getCellByColumnAndRow(1, ++$totalRow)->getValue() != 'Total' && $sheet->getCellByColumnAndRow(1, ++$totalRow)->getValue() != '');
			$totalRow--;
			
			$row = 2;$cleanup_dup = 0;
			$this->initSQLValuesString();
			

			while (true) {
				
				$row++;
				
				$day = $sheet->getCellByColumnAndRow(1, $row)->getValue();
				$h = $sheet->getCellByColumnAndRow(2, $row)->getValue();
		
				if( $day == 'Total' || empty($day) || $h == 'Total') break;
				
				
				$hour = $h[0].$h[1].$h[2].$h[3].$h[4].':00';
				
				$reading_datetime = $day.' '.$hour;
				//log_message('error', $reading_datetime);
				
				$col=4;
				while(true) {
									
					$error = '';
					
					$curve_name = $sheet->getCellByColumnAndRow($col, 1)->getValue();					
					
					if ( empty($curve_name) ) $curve_name = $sheet->getCellByColumnAndRow($col, 2)->getValue();
					else $curve_name = str_replace(['”','“'], '',$curve_name);					
					
					if ( empty($curve_name) ) break;				
					else
					{
						$curve_type = 'sintetica'; //toate curbele sunt masurate
						$customer_name = '';
						$customer_code = ''; 
					}
					

					$actual_ea = $sheet->getCellByColumnAndRow($col, $row)->getValue();
										
					$result = $this->importActualModel->checkCurveData($distributor_name,$this->cleanString($curve_name),$reading_datetime, $curve_type, $customer_name, $customer_code);
					
					$this->addSQLValuesString($distributor_name, $reading_datetime, $curve_name, $curve_type, $actual_ea,  $result['customer_name'], $customer_code, $result['customer_id'], $result['curve_id'], $result['error']);
					
					if($this->sqlValuesLines > 1000)
					{
						$affected += $this->importSQLValues();
						$this->initSQLValuesString();
					}
					
					$col++;
				}
			
			$affected += $this->importSQLValues();
			$this->initSQLValuesString();
		
			}
			
		}
		
		$this->importActualModel->cleanZeroConsumption();
		return ['rows' => $row-6, 'imported' => $affected,'fileInfo' => $file_info];
	}
	
	private function formatDateText ($dateText, $sourceFormat, $destFormat)
	{
		
		$date = \DateTime::createFromFormat($sourceFormat, $dateText ?? 0);
		if ($date === false) return false;
		
		return $date->format($destFormat);
	}
	
	private function isNumber($str)
	{
		$str = str_replace(',','.',$str);
		return is_numeric($str);
	}
	
	private function getNumber($str)
	{
		$str = str_replace('.','',$str);
		$str = str_replace(',','.',$str);
		return $str;
	}
	private function cleanString($str)
	{
		return str_replace( array( '\'', '"', '\b' , '\n', '\r', '\t', '\Z','\\','%','_','\0'), '', $str ?? '');
	}

	private function initSQLValuesString()
	{
		$this->sqlValues='';
		$this->sqlValuesLines=0;
	}
	
	private function addSQLValuesString($distributor_name, $reading_datetime, $curve_name, $curve_type, $actual_ea, $customer_name, $customer_code,$customer_id,$curve_id, $error)
	{
		
		if($this->importActualModel->isDuplicated($distributor_name,$curve_name,$reading_datetime)) return;
				
		$distributor_name = $this->cleanString($distributor_name);
		$curve_name = $this->cleanString($curve_name);
		$customer_code = $this->cleanString($customer_code);
		$customer_name = $this->cleanString($customer_name);
		$curve_type = $this->cleanString($curve_type);
		
		if ($this->sqlValuesLines > 0) $this->sqlValues .= ",";		
		
		$this->sqlValues .= "('".$_SESSION['select-supplier']."','".$distributor_name."','".$reading_datetime."','".$curve_name."','".$curve_type."',".$actual_ea.",'".$customer_name."','".$customer_code."',".($customer_id > 0 ? $customer_id : 'NULL').",'".$curve_id."','".$error."')";
		$this->sqlValuesLines+=1;
	}
		
	private function importSQLValues()
	{
		if ($this->sqlValuesLines > 0)
		{
			//log_message('error',$this->sqlValues);
			return $this->importActualModel->import_actual_data($this->sqlValues);
			//$this->qIDs[] = $this->importActualModel->async_import_actual_data($this->sqlValues);
		}
		return 0;
	}
	
}
