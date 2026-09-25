<?php namespace App\Controllers;

//use PhpOffice\PhpSpreadsheet\Spreadsheet;
//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use CodeIgniter\Database\Query;
use DateTime;
use DateInterval;

require_once('tools.php');
require_once('GridTools.php');

class ImportConsumptions extends BaseController
{
	use \GridTools;
	private $data = [
        'title'   => 'Importa Consumuri',
		'msg' => '',
		'output' => '',
		'upload' =>'',
		'div-card' => 'card-consumptions',
		'menu' => 'consumuri'];
	
	private $pdfError = [
		0 => 'No Error',
		-1 => 'Distributor Name Error',
		-2 => 'Supplier Name Not Found',
		-3 => 'Contract Number Not Found',
		-4 => 'Invoice Start/End Date Not Found',
		-5 => 'Voltage Type Not Found',
		-6 => 'Customer Name Not Found',
		-7 => 'Energy Type Not Found',
		-8 => 'POD Not Found',
		-9 => 'Device SN Not Found',
		-10 => 'Indexes not found',
		-11 => 'Reading dates not found',
		-12 => 'Unknown consumption location id',
		-80 => 'Fisier Stornare POD-ul nu a fost gasit. Mai incearca odata!',
		-100 => 'POD Nespecificat. Factura stornare.',
		-110 => 'Fisier necunoscut.'
		];
	
	private $sqlValues = '';
	private $sqlValuesLines = 0;
	private $consumption_date;
	protected $importConsumptionsModel;
	
	private $consumptionKeys = [];

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
        $this->importConsumptionsModel = new \App\Models\ImportConsumptionsModel();
						 
		$cd = $_SESSION['consumptions-date'] ??  date("m-Y",strtotime("-1 month"));
		log_message('error','consumptions-date:'.$cd);
		$this->consumption_date = DateTime::createFromFormat('m-Y-d', $cd.'-01')->format('Y-m-t');
		log_message('error','this->consumptions-date:'.$this->consumption_date);
    }
		
	public function index()
	{
		//grid
		$this->viewImportResult();
		
		$hasNewConsumptions = $this->importConsumptionsModel->hasNewConsumptions();
		
		//upload
		$files=['.xlsx','.pdf','.xlsm'];
		$upload = new \Kendo\UI\Upload('files[]');
		$upload->async(array(
				'saveUrl' => site_url().'/ImportConsumptions/upload',
				'removeUrl' => site_url().'ImportConsumptions/remove',
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
			->selected(!$hasNewConsumptions)
            ->icon("trash");
		
		$step2 = new \Kendo\UI\StepperStep();
        $step2->label("Incarca fisiere")
            ->icon("attachment");

        $step3 = new \Kendo\UI\StepperStep();
        $step3->label("Verifica")
		    ->selected($hasNewConsumptions)
            ->icon("preview")
            ->error(true);

        $step4 = new \Kendo\UI\StepperStep();
        $step4->label("Salveaza Consumul")
            ->icon("file-add");
		
        $stepper->addStep($step1);
        $stepper->addStep($step2);
		$stepper->addStep($step3);
		$stepper->addStep($step4);

        $this->data['stepper'] =  $stepper->render();
	   
	    $data['data'] = $this->data;
		return view('import_consumptions',$data);
	}

	public function clean_import()
	{
		$db = \Config\Database::connect();
		$db->query('delete FROM consumptions where invoice_id is null');
		
		$noFiles=0;
		$files = glob(WRITEPATH . 'uploads/*'); // get all file names
		foreach($files as $file){ // iterate files
		  if(is_file($file)) {
			unlink($file); // delete file
			$noFiles +=1;
		  }
		}

		$this->data['msg'] = 'Importul de date a fost sters! '.$db->affectedRows().' inregistrari sterse! '.$noFiles. ' fisiere temporare sterse!';
		
		$data['data'] = $this->data;

		return view('import_consumptions',$data);
	}
	
	public function viewImportResult()
	{
		
		$transport = $this->createGridTransport('import_consumptions');

		$schema = $this->createGridSchema([
		'consumption_id','distributor_name','supplier_name','customer_name','customer_code','contract_number','consumption_location_id',
		'pod','voltage_level_delimitation','voltage_level_measurment','invoice_start_date','invoice_end_date','reading_start_date','reading_end_date',
		'device_serial_number','energy_type','index_old','index_new','total_consumption_ae','total_consumption_re','total_consumption_re_3x',
		'total_consumption_mu','curve_name','curve_profile','source','status','error'],
		['number','string','string','string','string','string','string'
		,'string','string','string','date','date','date','date'
		,'string','string','number','number','number','number','number'
		,'string','string','string','string','string','string'],null,['error']);
		
		
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
	  		
		$dataSource = new \Kendo\Data\DataSource();
		
		$dataSource->transport($transport)
				   ->pageSize(1000)
				   ->batch(true)
				   ->schema($schema)
				   ->addAggregateItem($sum1)
				   ->addAggregateItem($sum2)
				   ->addAggregateItem($sum3)
				   ->addGroupItem($group)
				   ->serverAggregates(true)
				   ->serverGrouping(true);
		
		$filterItem1 = new \Kendo\Data\DataSourceFilterItem();
		$filterItem1->field('error');
		$filterItem1->operator('contains');
		$filterItem1->value('Consum');

		$filterItem2 = new \Kendo\Data\DataSourceFilterItem();
		$filterItem2->field('error');
		$filterItem2->operator('eq');
		$filterItem2->value('POD nou');

		$filterItem = new \Kendo\Data\DataSourceFilterItem();
		$filterItem->filters(array($filterItem1,$filterItem2));
		$filterItem->logic('or');
		
		$dataSource->addFilterItem($filterItem);
		$dataSource->serverFiltering(true);
		$dataSource->serverPaging(true);
		$dataSource->serverSorting(true);
		
		$column1 = $this->createGridColumn('distributor_name','Distribuitor',80);
		$column2 = $this->createGridColumn('supplier_name','Furnizor',80);
		$column3 = $this->createGridColumn('customer_name','Client',80);
		$column3->groupHeaderTemplate("#=data.value# EA:<span class='text-danger'>#=kendo.format('{0:N3}', aggregates.total_consumption_ae.sum / 1000)# MWh</span> 
		ER:<span class='text-success'>#=kendo.format('{0:N0}', aggregates.total_consumption_re.sum)#</span> 
		ER_3X:<span class='text-primary'>#=kendo.format('{0:N0}',aggregates.total_consumption_re_3x.sum)#</span>");
				
		$column4 = $this->createGridColumn('pod','Pod',140);
		$column4->groupHeaderTemplate("POD #=data.value# EA:<span class='text-danger'>#=kendo.format('{0:N3}', aggregates.total_consumption_ae.sum / 1000)# MWh</span> 
		ER:<span class='text-success'>#=kendo.format('{0:N0}', aggregates.total_consumption_re.sum)#</span> 
		ER_3X:<span class='text-primary'>#=kendo.format('{0:N0}',aggregates.total_consumption_re_3x.sum)#</span>");
		$column11 = $this->createGridColumn('invoice_start_date','Data Factura',80);
						
		$column5 = $this->createGridColumn('voltage_level_measurment','Voltaj',50);
		$column6 = $this->createGridColumn('energy_type','Energie',50);
		
		$column7 = $this->createGridColumn('total_consumption_ae','EA',50);
		$column7->template("#=(data.total_consumption_ae != 0 && !data.error.startsWith('Ignora'))? ('<b>'+kendo.format('{0:N0}',data.total_consumption_ae)+'</b>') : kendo.format('{0:N0}',data.total_consumption_ae)#")
				->aggregates('sum');
		$column7->footerTemplate("#=data.total_consumption_ae.sum !== undefined ? kendo.format('{0:N3} MWh',data.total_consumption_ae.sum/1000) : ''#");
		
		$column8 = $this->createGridColumn('total_consumption_re','ER',50);
		$column8->template("#=(data.total_consumption_re != 0 && !data.error.startsWith('Ignora'))? ('<b>'+kendo.format('{0:N0}',data.total_consumption_re)+'</b>') : kendo.format('{0:N0}',data.total_consumption_re)#")
				->aggregates('sum');
		
		$column9 = $this->createGridColumn('total_consumption_re_3x','ER_3X',50);		
		$column9->template("#=(data.total_consumption_re_3x != 0 && !data.error.startsWith('Ignora'))? ('<b>'+kendo.format('{0:N0}',data.total_consumption_re_3x)+'</b>') : kendo.format('{0:N0}',data.total_consumption_re_3x)#")
				->aggregates('sum');
				
		$column10 = $this->createGridColumn('total_consumption_mu','UM',50);
		
		$column12 = $this->createGridColumn('reading_start_date','Cit Start',60);
		$column13 = $this->createGridColumn('reading_end_date','Cit Sfarsit',60);
		
		$column14 = $this->createGridColumn('index_old','Idx Vechi',60);
		$column15 = $this->createGridColumn('index_new','Idx Nou',60);
		
		$column22 = $this->createGridColumn('curve_name','Curba Consum',70);
		//$column22->hidden(true);
		$column23 = $this->createGridColumn('curve_profile','Profilul Curbei',70);
		//$column23->hidden(true);
		
		$column16 = $this->createGridColumn('error','Actiune',80)
							->template("#=(data.error =='POD nou') ? ('<button class=\"k-button k-button-solid-primary k-rounded-md\" onClick=createPODDialog(\"'+ data.customer_name.replace(/\s/g, '_') +'\",\"'+ pod.trim() +'\");>'+data.error+'</button>') : ((data.error.startsWith('Consum facturat')) ? '<a class=\"k-button k-button-solid-error k-rounded-md a-btn \" target=\"_blank\" href=\"' + window.location.origin +'/PdfInvoice?invoiceId='+data.error.slice(16)+'\">Verifica</a>' : data.error) #");
			 
		$this->grid->height(550)
				 ->dataSource($dataSource)
				 ->resizable(true);
		
		$this->setGridEditable('inline');
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
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
			return redirect()->to( base_url('ImportConsumptions'))->with('msg', 'Ai incercat sa incarci un fisier?');
	}

	private function importFile($file)
	{
		$msg = '';
		$pdfText = '';
		$fileInfo = pathinfo($file);
		$sConsumtions = $this->importConsumptionsModel->countConsumptions();
		
		if(in_array(strtolower($fileInfo['extension']), array('pdf','xlsx','xlsm')))
		{		
			$rows_imported = 0;
			$err_msg='';
			
			if($fileInfo['extension'] == 'pdf')
			{
				$destFile = WRITEPATH.'uploads/'.$fileInfo['filename'].'.txt';
				shell_exec('pdftotext -layout ' . escapeshellarg($file).' '.escapeshellarg($destFile));
				$pdfText = file_get_contents($destFile); /*$pdfText.$pdf->getText()*/
				
				switch($this->getPDFtype($pdfText))
				{
					case 'ENEL':
						$result = $this->import_ENEL_pdf($pdfText);
						break;
					case 'DEER_TN':
						$result = $this->import_DEER_TN_pdf($pdfText);
						break;
					case 'DEER_TS':
						$result = $this->import_DEER_TS_pdf($pdfText);
					break;
					case 'DEER_MN':
						$result = $this->import_DEER_TS_pdf($pdfText);
					break;
					case 'DELGAZ':
						$result = $this->import_DELGAZ_pdf($pdfText);
					break;
					case 'OLTENIA':
						$result = $this->import_OLTENIA_pdf($pdfText);
					break;
					default:
						$result = -110;
				}
			}
			else
			{
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
					case 'DEER_TN2':
					case 'DEER_TS2':
					case 'DEER_MN2':
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
			}
			
			
			if (gettype($result)=='integer')
			{	
				$err = $result;
				$result = ['rows' => 0, 'imported'=>0,'fileInfo'=>'Fisier import'];					
				$err_msg = $this->pdfError[$err];
			}
			
			if (!empty($result['cleanup_dup']))
				$cd_msg=' '.$result['cleanup_dup'].' totaluri duplicate in pdf.';
			else
				$cd_msg = '';
			
			if (empty($result['cleanup']))
				$msg = $msg.'<br><b>'.$result['fileInfo'].'</b> '.$fileInfo['filename'].' contine '.$result['rows'].' inregistrari.';// din care <b>'.$result['imported'].'</b> au fost importate! '.$cd_msg.' '.'<b style="color:red">'.$err_msg.'</b>';						 
			else
				$msg = $msg.'<br>'.$result['fileInfo'].' '.$fileInfo['filename'].' contine '.$result['rows'].' inregistrari.';// din care <b>'.($result['imported']-$result['cleanup']).'</b> au fost importate ('.$result['cleanup'].' consumuri zero ignorate.'.$cd_msg.' )!'.$err_msg;						 	
								
		
			$nConsumtions = $this->importConsumptionsModel->countConsumptions() - $sConsumtions;
			if ($nConsumtions == 0)
				$msg = $fileInfo['filename'].' nu are consumuri noi';
			else
				$msg = $fileInfo['filename'].' contine '.$nConsumtions.' consumuri';
		}
		else
		{
			$err = -110;
			$result = ['rows' => 0, 'imported'=>0,'fileInfo'=>'Fisier import'];	
			$msg = 'Eroare: Va rugam sa selectati un fisier acceptat!';
		}
		
		return $msg;
	}

	public function getPDFtype($pdfText)
	{
			$ret = false;
			
			if (strpos($pdfText,'Distributie Energie Electrica Romania S.A. Zona MN') !== FALSE) $ret = 'DEER_MN';
			//elseif (strpos($pdfText,'SDEE Transilvania Sud') !== FALSE) $ret =  'DEER_TS';
			elseif (strpos($pdfText,'SDEE Transilvania Sud') !== FALSE) $ret =  'DEER_TS';
			elseif (strpos($pdfText,'www.eneldistributie.ro') !== FALSE) $ret =  'ENEL';
			elseif (strpos($pdfText,'DELGAZ GRID SA') !== FALSE) $ret =  'DELGAZ';
			elseif (strpos($pdfText,'DISTRIBUTIE ENERGIE OLTENIA S.A.') !== FALSE) $ret = 'OLTENIA';
			elseif (strpos($pdfText,'Distributie Energie Electrica Romania S.A.') !== FALSE) $ret =  'DEER_TN';
			
			log_message("error","PDF Type:".$ret);
			return $ret;
	}
	
	public function getXLSxtype($fileName)
	{
		
		log_message('error',$fileName);
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileName);
        
        $sheet = $spreadsheet->getSheet(0); //$spreadsheet->getActiveSheet();
		$distributor_name = $sheet->getCellByColumnAndRow(1, 2)->getValue();
		if(empty($distributor_name))
			$distributor_name = $sheet->getCellByColumnAndRow(2, 2)->getValue();
		
		if (strpos($distributor_name,'E-DISTRIBUTIE') !== FALSE) return 'ENEL';
		elseif (strpos($distributor_name,'RETELE ELECTRICE') !== FALSE) return 'ENEL';
		elseif (strpos($distributor_name,'Zona TN') !== FALSE) return 'DEER_TN';
		elseif (strpos($distributor_name,'Romania-TS') !== FALSE) return 'DEER_TS';
		elseif (strpos($distributor_name,'DEER MN') !== FALSE) return 'DEER_MN';
		elseif (strpos($distributor_name,'Distributie En. El. Romania') !== FALSE)
		{
			$ddata = substr($sheet->getCellByColumnAndRow(9, 2)->getValue(),0,5);
			log_message('error', $ddata);
			if($ddata == '59403') return 'DEER_MN2';
			elseif($ddata == '59404') return 'DEER_TN2';
			elseif($ddata == '59402') return 'DEER_TS2';
			else return FALSE;
		}
		elseif (strpos($distributor_name,'Delgaz') !== FALSE) return 'DELGAZ';	
		elseif (strpos($distributor_name,'OLTENIA') !== FALSE) return 'OLTENIA';
		
		log_message('error','Distributor not found');
		return false;
	}
	
	public function import_ENEL_pdf($pdfText)
	{
		//log_message('info',$pdfText);
		$row = $affected = 0;
		$status = 'continue';
		$file_info = 'Fisier import citiri'; 
		
		$distributor_name = $supplier_name = $customer_name = $customer_code = $contract_number = $consumption_location_id = $pod = $voltage_level_delimitation = $voltage_level_measurment = $invoice_start_date = $invoice_end_date = $reading_start_date = $reading_end_date = $device_serial_number = $energy_type = $index_old = $index_new = $total_consumption_mu = '';
		$total_consumption_ae = $total_consumption_re = $total_consumption_re_3x = 0;
		$this->initSQLValuesString();
		
		$distributor_name = $this->get_string_between($pdfText,'FURNIZOR','Adresa');	
		//if (strpos($pdfText,"www.eneldistributie.ro") !== FALSE) $distributor_name='E-DISTRIBUTIE DOBROGEA S.A.';
		//else return -1;
		
		$supplier_name = trim($this->get_string_between($pdfText,"www.eneldistributie.ro","Call Center"));
		if (empty($supplier_name)) return -2;
		
		$text_array1 = explode('Contract nr.: ', $pdfText);
		foreach (array_slice($text_array1,1) as $textData) 
		{
			$contract_number = trim($this->get_string_between($textData,'','Oferta/Tarif/Nivel'));
			if (empty($contract_number)) return -3;
			
			$consumption_location_id = trim($this->get_string_between($textData,'Cod identificare loc consum: ','de distributie'));
			if (empty($consumption_location_id)) return -12;
			$customer_code = $consumption_location_id;
			
			$invoice_dates = trim($this->get_string_between($textData,'Perioada de facturare: ','Cod identificare loc consum'));
			if (empty($invoice_dates)) return -4;
			$invoice_dates_array = explode(' - ',$invoice_dates);
			if (count($invoice_dates_array)!=2) return -4;
			$invoice_start_date = $this->formatDateText($invoice_dates_array[0],'d.m.Y','Y-m-d');
			$invoice_end_date = $this->formatDateText($invoice_dates_array[1],'d.m.Y','Y-m-d');
						
			$voltage_level_measurment = trim($this->get_string_between($textData,'de distributie ','Consum anual estimat'));
			if (!in_array($voltage_level_measurment, array('JT','MT','IT'))) return -5;
//			$voltage_level_measurment = $voltage_level_delimitation;
			
			$customer_name =  str_replace(["\r","\n"], "", $this->get_string_between($textData,'DETALII FACTURARE LOC DE CONSUM ','Tip energie'));
			if (empty($invoice_dates)) return -6;
			
			$energy_array = explode('ENERGIE',$textData);
			foreach (array_slice($energy_array,1) as $energy_row) 
			{
				if ($this->startsWith($energy_row,' ACTIVA'))
				{							
						$waitForFinish = (strpos($this->get_string_between($energy_row,'','TOTAL LOC DE CONSUM'),"Total") !== false);					
						
						foreach(preg_split("/((\r?\n)|(\r\n?))/", $energy_row) as $line){
							
							if ($this->startsWith($line,' ACTIVA')) continue;
							
							// do stuff with $line
							$lineData = preg_split('/ /', $line, 0, PREG_SPLIT_NO_EMPTY);
							if(!empty($lineData))
							{							
								//log_message('info',print_r($lineData, TRUE));
								
								if ($status == 'getReadingsDates') 
								{											
									$reading_start_date = $this->formatDateText($lineData[0],'d.m.Y','Y-m-d');
									$reading_end_date = $this->formatDateText($lineData[2],'d.m.Y','Y-m-d');
									$status = 'continue';
																										
									$total_consumption_re = 0;
									$total_consumption_re_3x = 0;

									$estimataAnterior = 0;
									$estimataAnteriorArr = $this->get_string_between($energy_row,'Estimata anterior EA','kWh',2);
									
									/*
									if(!empty($estimataAnteriorArr))
									{
										foreach($estimataAnteriorArr as $e_arr)
											if($this->isNumber($e_arr))
												$estimataAnterior+=$e_arr;
										
										$sliced_energy_row = $this->get_string_between($energy_row,$pod,$pod);
										
										if(strpos($sliced_energy_row,$line) !== false)
										{
											$readingDatesArr = $this->get_string_between($sliced_energy_row,'Total',"Estimata anterior EA",2);
											if(!empty($readingDatesArr) && count($readingDatesArr)>1)
											{
												//rewrite reading dates MunteniaSud
												$r = preg_split("/((\r?\n)|(\r\n?))/", $readingDatesArr[0]);
												$lD = preg_split('/ /', $r[1], 0, PREG_SPLIT_NO_EMPTY);
												if(!empty($lD)) $reading_start_date = $this->formatDateText($lD[0],'d.m.Y','Y-m-d');
												
												$r = preg_split("/((\r?\n)|(\r\n?))/", $readingDatesArr[1]);
												$lD = preg_split('/ /', $r[1], 0, PREG_SPLIT_NO_EMPTY);
												if(!empty($lD)) $reading_end_date = $this->formatDateText($lD[2],'d.m.Y','Y-m-d');														
											}
										}
									}*/
									
									$row++;						
									$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
									
									//$estimataAnterior = trim($this->get_string_between($energy_row,'Estimata anterior EA','kWh'));
									/*if($estimataAnterior != 0)
									{
										$row++;
										$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,'1970-01-01','1970-01-01',$device_serial_number,$energy_type,'0','0',$estimataAnterior,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
									}*/
									continue;
								}
								
								if ($this->startsWith($line,'Pierderi en. activa LEA'))
								{
									$pierderi = $lineData[4];
									if($this->isNumber($pierderi)) 
										$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$pierderi,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
								}

								if ($this->startsWith($line,'Estimata anterior EA'))
								{
									$estimata = $lineData[3];
									log_message('error','estimata anteriorX POD:'.$pod.' '.$estimata);
									if($this->isNumber($estimata)) 
									{
										$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,'1970-01-01','1970-01-01',$device_serial_number,$energy_type,'0','0',$estimata,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
										continue;
									}
								}								
								
								$info = $lineData[0]; 
								if (in_array($info, array('EA','EANO','EANW','EAZ','EAN','EAV','EAG')))
								{
									$energy_type = $info;
									
									//negative invoice
									if (count($lineData) == 6 || count($lineData) == 5)
									{
										$total_consumption_ae = $this->getNumber($lineData[1]);
										if ($this->isNumber($total_consumption_ae) /*and $total_consumption_ae < 0*/)
										{
											log_message('error',$pod.':'.print_r($lineData,true));
											$pod_device = $this->importConsumptionsModel->get_pod_device_by_location_id($consumption_location_id,$distributor_name);
											$pod = $pod_device['pod'];
											$device_serial_number = $pod_device['device_serial_number'];
											$file_info = 'Fisier stornare';
											if (empty($pod)) return -80;
											$energy_type = 'EA';
											
											//rewrite invoice date for Muntenia Sud: E-DISTRIBUTIE MUNTENIA S.A.
											$tdate = \DateTime::createFromFormat('Y-m-d', $invoice_start_date);
											$tdate->modify('+1 month');			
											$invoice_end_date = $tdate->format('Y-m-d');
											
											$row++;
											
											
											$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,'1970-01-01','1970-01-01',$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,'kWh','pdf');
										
										}
										continue;
										//return -100;
									}
									else 									
									{	//rewrite invoice date for Muntenia Sud: E-DISTRIBUTIE MUNTENIA S.A.
										$tdate = \DateTime::createFromFormat('Y-m-d', $invoice_end_date);
										$tdate->modify('-1 month');			
										$invoice_start_date = $tdate->format('Y-m-d');
									}
									
									$pod = $lineData[1];
									if (empty($pod)) return -8;
									
									if (strlen($pod) >15 )
									{
										$device_serial_number = substr($pod,15);
										$pod = substr($pod,0,15);
										$lineIdx = 2; 
									}
									else
									{
										$device_serial_number = $lineData[2];
										$lineIdx = 3;
									}
									
									if (empty($device_serial_number)) return -9;
									
									if (count($lineData) < ($lineIdx + 8)) return -10;
									
									$index_old = $this->getNumber($lineData[$lineIdx + 1]);//$indexes[0];
									$index_new = $this->getNumber($lineData[$lineIdx + 4]);//substr($indexes[1],2);
									
									$total_consumption_ae = $this->getNumber($lineData[$lineIdx + 7]);
									$total_consumption_mu = $this->getNumber($lineData[$lineIdx + 8]);
									
									if (!$this->isNumber($index_old) or !$this->isNumber($index_new) or !$this->isNumber($total_consumption_ae) or empty($total_consumption_mu)) return -10;
																	
									if (!$waitForFinish)
									{
										$total_consumption_re = 0;
										$total_consumption_re_3x = 0;
										$row++;
						//				log_message('info',$textData);
						//				log_message('info',$distributor_name.','.$supplier_name.','.$contract_number.','.$invoice_start_date.','.$invoice_end_date.','.$voltage_level_measurment.','.$customer_name.','.$energy_type.','.$pod.','.$device_serial_number,$index_old,$index_new);							


										//estimare anterioara MunteniaSud
										$estimataAnterior = 0;
										$estimataAnteriorArr = $this->get_string_between($energy_row,'Estimata anterior EA','kWh',2);
										if(!empty($estimataAnteriorArr))
										{
											foreach($estimataAnteriorArr as $e_arr)
												if($this->isNumber($e_arr))
													$estimataAnterior+=$e_arr;
											
											$reading_start_date = $reading_end_date = '1970-01-01';
											$total_consumption_ae = $estimataAnterior;
											$index_new = $index_old = 0;
											$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,'kWh','pdf');
											
											log_message('error','estimata anterior2 POD:'.$pod.' '.$estimataAnterior);
										}
										else
											$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$invoice_start_date,$invoice_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,'kWh','pdf');
									}
								}
							}
														
							if ($this->startsWith($line,'Total loc de consum') or empty($lineData)) break;
							
							if ($info == 'Total') 
								{
									$status = 'getReadingsDates';
									$lineData = preg_split('/ /', $line, 0, PREG_SPLIT_NO_EMPTY);
									if(count($lineData)==6 && $this->isNumber($lineData[1]))
										$total_consumption_ae = $this->getNumber($lineData[1]);
									
									continue;
								}							
						}
				}
				if ($this->startsWith($energy_row,' REACTIVA'))
				{
					//log_message('info',$energy_row);

					$reactive_energy_array = preg_split('(CAPACITIVA|INDUCTIVA)',$energy_row);
					
					$ret = $this->get_readings_dates_re($energy_row);
					if (!empty($ret))
					{
						$reading_start_date = $ret[0];
						$reading_end_date = $ret[1];
					}
					else return -11;
					
					$energyTypeIndex=0;
					if (count($reactive_energy_array)%2 != 1) return -7;
					
					foreach($reactive_energy_array as $reactive_energy)
					{
						$status = '';
						$total_consumption_re_3x = 0;
					
						$energyTypeIndex++;
						if ($this->startsWith($reactive_energy,' REACTIVA')) continue;
						
						if ($energyTypeIndex%2 == 0) $energy_type = 'ERC';
						if ($energyTypeIndex%2 == 1) $energy_type = 'ERI';

						$waitForFinish = (strpos($reactive_energy,"Din care facturat") !== false);
						
						//log_message('info','$energyTypeIndex='.($energyTypeIndex%2).' $reactive_energy='.$reactive_energy);
						
						$lines = preg_split("/((\r?\n)|(\r\n?))/", $reactive_energy);
						foreach( $lines as $line){
														
							// do stuff with $line
							$lineData = preg_split('/ /', $line, 0, PREG_SPLIT_NO_EMPTY);
							if(!empty($lineData))
							{							
								//log_message('info',print_r($lineData, TRUE));
														
								if (strpos($line,'TOTAL LOC DE CONSUM') !== false) 
								{
	//								if ()
										$status = 'finished';
	//								else
	//									break;
								}
																				
								if (($this->startsWith($line,'Conform') or ($this->startsWith($line,'Total'))) and !$waitForFinish) 
								{
									$status = 'finished';
								}
								
								if ($status != 'finished')
								{
									if ($this->startsWith($line,'Total') or ($this->startsWith($line,'Conform') and $waitForFinish)) continue;
							
									//Muntenia Sud
									if ($this->startsWith($line,'Din care facturat'))
									{
										$lineArr = $this->get_string_between($reactive_energy,'Din care facturat','Total facturat',2);
										if(!empty($lineArr) && count($lineArr)>1)
										{
										
											//extract ER totals - prima citire
											$r = preg_split("/((\r?\n)|(\r\n?))/", $lineArr[0]);
											
											$lineData = preg_split('/ /', $r[0], 0, PREG_SPLIT_NO_EMPTY);
											if (count($lineData)>2)
												$total_consumption_re = $this->getNumber($lineData[0]);
											else 
												$total_consumption_re = 0;
											
											//rewrite reading dates MunteniaSud
											$lineData = preg_split('/ /', $r[1], 0, PREG_SPLIT_NO_EMPTY);
											if(!empty($lineData)) $reading_start_date = $this->formatDateText($lineData[0],'d.m.Y','Y-m-d');
											
											//extract x3 Muntenia Sud
											if(!empty($lineData) && $energy_type == 'ERC')
											{												
												if ($this->isNumber($lineData[3]))
													$total_consumption_re_3x = $this->getNumber($lineData[3]);
												else 
													$total_consumption_re_3x = 0;
											}
											if(!empty($lineData) && $energy_type == 'ERI')
											{
													if ($this->isNumber($lineData[2]))
														$total_consumption_re_3x = $this->getNumber($lineData[2]);
													else 
														$total_consumption_re_3x = 0;
											}
											
											//extract ER totals - a doua citire
											$r = preg_split("/((\r?\n)|(\r\n?))/", $lineArr[1]);

											$lineData = preg_split('/ /', $r[0], 0, PREG_SPLIT_NO_EMPTY);
											
											if (count($lineData)>2)
												$total_consumption_re += $this->getNumber($lineData[0]);

											
											//rewrite reading dates MunteniaSud + x3
											$lineData = preg_split('/ /', $r[1], 0, PREG_SPLIT_NO_EMPTY);
											if(!empty($lineData) && $energy_type == 'ERC')
											{
												$reading_end_date = $this->formatDateText($lineData[2],'d.m.Y','Y-m-d');
												
												if ($this->isNumber($lineData[3]))
													$total_consumption_re_3x = $this->getNumber($lineData[3]);
											}
											if(!empty($lineData) && $energy_type == 'ERI')
											{
													$t = substr($lineData[1],1);
													$reading_end_date = $this->formatDateText($t,'d.m.Y','Y-m-d');
													
													if ($this->isNumber($lineData[2]))
														$total_consumption_re_3x = $this->getNumber($lineData[2]);
											}
												
											$status ='finished';
											continue;
										}
										else
										{
											$total_consumption_re = $this->getNumber($lineData[3]);
											$status ='_x3';
											continue;
										}
									}
									
									/*
									if ($this->startsWith($line,'Din care facturat'))
									{
										$total_consumption_re = $this->getNumber($lineData[3]);
										$status ='_x3';
										continue;
									}
									*/
									
									if ($status == '_x3')
									{
										if ($energy_type == 'ERC') $idx = 3; //typo in pdf
										else $idx = 2;
										
										if ($this->isNumber($lineData[$idx]))
											$total_consumption_re_3x = $this->getNumber($lineData[$idx]);
										else 
											$total_consumption_re_3x = 0;

										$ret = $this->get_readings_dates_re_3x($line);										
										if (!empty($ret))
										{
											$reading_start_date = $ret[0];
											$reading_end_date = $ret[1];
										}
										
										$status = 'finished';
										continue;
									}
																		
									
									$pod = $lineData[0];
									if (empty($pod)) return -8;
									
									if (strlen($pod) >15 )
									{
										$device_serial_number = substr($pod,15);
										$pod = substr($pod,0,15);
										$lineIdx = 1; 
									}
									else
									{
										$device_serial_number = $lineData[1];
										$lineIdx = 2;
									}
									
									if (empty($device_serial_number)) return -9;
									
									if (count($lineData) < ($lineIdx + 7)) return -10;
											
									$index_old = $this->getNumber($lineData[$lineIdx + 1]);//$indexes[0];
									$index_new = $this->getNumber($lineData[$lineIdx + 4]);//substr($indexes[1],2);
									
									//ignora liniile nefacturate
									$total_consumption_re = 0; //$this->getNumber($lineData[$lineIdx + 7]);
									
									$total_consumption_mu = $this->getNumber($lineData[$lineIdx + 8]);
									
									if (!$this->isNumber($index_old) or !$this->isNumber($index_new) or !$this->isNumber($total_consumption_re) or empty($total_consumption_mu)) return -10;
								}
								
								if ($status == 'finished' or count($lines) <=2)
								{
									$total_consumption_ae = 0;
									$row++;
									
									$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');

									break;
								}
							}
						}
					}
				}
				
			}
		}
		
		
		$affected = $this->importSQLValues();
		
		return ['rows' => $row, 'imported' => $affected,'fileInfo' => $file_info];
	}

	public function import_ENEL($fileName)
	{	
	
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileName);
        
        $sheet = $spreadsheet->getActiveSheet();
		$file_info = 'Fisier import citiri';
		
		$row = 1; $cleanup_dup = 0;
		$this->initSQLValuesString();
		
		$distributor_name = "";
		while (true) {
			
			$row++;
			if ($sheet->getCellByColumnAndRow(1, $row)->getValue() === NULL) break;
			if(empty($distributor_name))
			{
				$distributor_name = $sheet->getCellByColumnAndRow(1, $row)->getValue();
							
				if(str_contains($distributor_name,'E-DISTRIBUTIE DOBROGEA S.A.')) $distributor_name = 'RETELE ELECTRICE DOBROGEA S.A.';
				elseif(str_contains($distributor_name,'E-DISTRIBUTIE MUNTENIA S.A.')) $distributor_name = 'RETELE ELECTRICE MUNTENIA S.A.';
				elseif(str_contains($distributor_name,'E-DISTRIBUTIE BANAT S.A.')) $distributor_name = 'RETELE ELECTRICE BANAT S.A.';
				else $distributor_name="";
				
				$pod = $sheet->getCellByColumnAndRow(9, $row)->getValue();
				if(empty($distributor_name))
					$distributor_name = $this->importConsumptionsModel->getDistributorNameByPODPrefix($pod);
			}
			else
				$pod = $sheet->getCellByColumnAndRow(9, $row)->getValue();
			
			if($this->cleanString($sheet->getCellByColumnAndRow(28, $row)->getValue() == 'DUPLICAT')) continue;
			
			$supplier_name = $sheet->getCellByColumnAndRow(2, $row)->getValue();
			$customer_name = $sheet->getCellByColumnAndRow(3, $row)->getValue();
			$customer_code = $sheet->getCellByColumnAndRow(4, $row)->getValue();
			$contract_number = $sheet->getCellByColumnAndRow(5, $row)->getValue();
			//$contract_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(6, $row)->getValue()??0)->format('Y-m-d');
			
			$consumption_location_id = $sheet->getCellByColumnAndRow(10, $row)->getValue();
			$customer_code = $consumption_location_id; //customer code nu apare in pdf-uri
			
			$voltage_level_delimitation = $sheet->getCellByColumnAndRow(12, $row)->getValue();
			$voltage_level_measurment = $sheet->getCellByColumnAndRow(11, $row)->getValue();
			$invoice_start_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(13, $row)->getValue()??0)->format('Y-m-d');
			$invoice_end_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(14, $row)->getValue()??0)->format('Y-m-d');
			
			//exceptia Muntenia Sud
			if ($invoice_start_date == $invoice_end_date)
			{
				$date = \DateTime::createFromFormat('Y-m-d', $invoice_start_date);
				if ($date === false) return false;
				$days = $date->format('t');
				$date->add(new DateInterval('P'.$days.'D'));
				$invoice_end_date = $date->format('Y-m-d');
			}				
			
			$reading_start_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(15, $row)->getValue()??0)->format('Y-m-d');
			$reading_end_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(16, $row)->getValue()??0)->format('Y-m-d');
			$device_serial_number = $sheet->getCellByColumnAndRow(17, $row)->getValue();
			$energy_type = $sheet->getCellByColumnAndRow(18, $row)->getValue();

			//if($energy_type == 'EAP') continue;
			//log_message('error', $energy_type);
			
			$index_old = $sheet->getCellByColumnAndRow(20, $row)->getValue();
			$index_new = $sheet->getCellByColumnAndRow(21, $row)->getValue();
			$total_consumption_ae = $sheet->getCellByColumnAndRow(31, $row)->getValue();
			$total_consumption_re = $sheet->getCellByColumnAndRow(32, $row)->getValue();
			$total_consumption_re_3x = $sheet->getCellByColumnAndRow(33, $row)->getValue();
			$total_consumption_mu = $sheet->getCellByColumnAndRow(35, $row)->getValue();
			
			$curveName = $profileCC = '';
			
			if($this->cleanString($sheet->getCellByColumnAndRow(28, $row)->getValue() == 'STORNO'))
			{
				$r = $row+1;
				$stornoFound = false;
				$tCurveName = $this->cleanString($sheet->getCellByColumnAndRow(37, $row)->getValue());
				
				while(!empty($this->cleanString($sheet->getCellByColumnAndRow(9, $r)->getValue())))
					if(
						$this->cleanString($sheet->getCellByColumnAndRow(9, $r)->getValue()) == $pod &&
						(empty($this->cleanString($sheet->getCellByColumnAndRow(28, $r)->getValue())) || 
						$sheet->getCellByColumnAndRow(28, $r)->getValue() != 'DUPLICAT') && 
						$total_consumption_ae == -$sheet->getCellByColumnAndRow(31, $r)->getValue() &&
						$total_consumption_re == -$sheet->getCellByColumnAndRow(32, $r)->getValue() &&
						$total_consumption_re_3x == -$sheet->getCellByColumnAndRow(33, $r)->getValue() &&
						$tCurveName == $sheet->getCellByColumnAndRow(37, $r)->getValue())
					{
						$stornoFound = true;
						break;
					}
					else
						$r++;
				
				if($stornoFound)
				{
					$sheet->setCellValueByColumnAndRow(28,$row,'DUPLICAT');
					$sheet->setCellValueByColumnAndRow(28,$r,'DUPLICAT');
					$cleanup_dup+=1;
					continue;
				}
			}
			elseif	(empty($this->cleanString($sheet->getCellByColumnAndRow(28, $row)->getValue()))) //check if was storned later
			{
				$r = $row+1;
				$stornoFound = false;
				$tCurveName = $this->cleanString($sheet->getCellByColumnAndRow(37, $row)->getValue());
				
				while(!empty($this->cleanString($sheet->getCellByColumnAndRow(9, $r)->getValue())))
					if( 
						$this->cleanString($sheet->getCellByColumnAndRow(9, $r)->getValue()) == $pod &&
						$this->cleanString($sheet->getCellByColumnAndRow(28, $r)->getValue() == 'STORNO' && 
						$total_consumption_ae == -$sheet->getCellByColumnAndRow(31, $r)->getValue() &&
						$total_consumption_re == -$sheet->getCellByColumnAndRow(32, $r)->getValue() &&
						$total_consumption_re_3x == -$sheet->getCellByColumnAndRow(33, $r)->getValue()) &&
						$tCurveName == $sheet->getCellByColumnAndRow(37, $r)->getValue())
						
					{
						$stornoFound = true;
						break;
					}
					else
						$r++;
				
				if($stornoFound)
				{
					$sheet->setCellValueByColumnAndRow(28,$row,'DUPLICAT');
					$sheet->setCellValueByColumnAndRow(28,$r,'DUPLICAT');
					$cleanup_dup+=1;
					continue;
				}
			}

			if(!in_array($energy_type,['EAP','ERI','ERC']))
			{
				$profileCC = $this->cleanString($sheet->getCellByColumnAndRow(36, $row)->getValue());
				$curveName = $this->cleanString($sheet->getCellByColumnAndRow(37, $row)->getValue());
				
				if(empty($curveName) && $total_consumption_ae != 0) $curveName = $pod;
				
				if( !empty($profileCC) || !empty($curveName) )
					$curveID = $this->importConsumptionsModel->saveCurveVariance($this->consumption_date, $distributor_name, $pod, $profileCC, $curveName);
			}

			$extraKey = $this->cleanString($sheet->getCellByColumnAndRow(42, $row)->getValue());
			if (!$this->importConsumptionsModel->import_check_duplicates_xlsx_in_pdf($pod,$energy_type,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x))
				$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,$curveName,$profileCC,'xlsx',$extraKey);
			else
				$cleanup_dup+=1;
		}
		
		$affected = $this->importSQLValues();
						
		$affected_cleanup = $this->importConsumptionsModel->import_cleanup_xlsx();
		
		return ['rows' => $row-1, 'imported' => $affected,'cleanup' => $affected_cleanup,'cleanup_dup'=>$cleanup_dup,'fileInfo' => $file_info];
	}
	
	public function import_DEER_TN_TS_MN($fileName)
	{	
		log_message('error','import_DEER_TN_TS_MN');			
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileName);
        
        $sheet = $spreadsheet->getActiveSheet();
		$file_info = 'Fisier import citiri';
		
		$row = 1;$cleanup_dup = 0;
		$this->initSQLValuesString();
		
		while (true) {
			
			$row++;
			$distributor_name = $sheet->getCellByColumnAndRow(1, $row)->getValue();
			
			//if (in_array($distributor_name,array('Distributie En. El. Romania - Zona TN','Distributie Energie Electrica Romania-TS')))

			if ($distributor_name == 'Distributie En. El. Romania - Zona TN')
				$distributor_name = 'DEER Transilvania Nord';
			elseif ($distributor_name == 'Distributie Energie Electrica Romania-TS')
				$distributor_name = 'DEER Transilvania Sud';
			elseif ($distributor_name == 'DEER MN')
				$distributor_name = 'DEER Muntenia Nord';
			else
				break;
			
			$supplier_name = $this->importConsumptionsModel->get_supplier_name($sheet->getCellByColumnAndRow(2, $row)->getValue());
			$customer_name = $sheet->getCellByColumnAndRow(3, $row)->getValue();
			$customer_code = $sheet->getCellByColumnAndRow(4, $row)->getValue();
			$contract_number = $sheet->getCellByColumnAndRow(5, $row)->getValue();
			//$contract_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(6, $row)->getValue()??0)->format('Y-m-d');
			

			$voltage_level_delimitation = $sheet->getCellByColumnAndRow(11, $row)->getValue();
			$voltage_level_measurment	 = $sheet->getCellByColumnAndRow(12, $row)->getValue();
			
			if (($distributor_name == 'DEER Transilvania Nord') || ($distributor_name == 'DEER Muntenia Nord'))
			{				
				$pod_data = explode('_',$sheet->getCellByColumnAndRow(9, $row)->getValue());			
				$pod = $pod_data[0];
				$consumption_location_id='';
				
				if(count($pod_data) == 2)
				{				
					$consumption_location_id = $pod_data[1];
					$voltage_level_measurment = $sheet->getCellByColumnAndRow(12, $row)->getValue();
					$energy_type = $sheet->getCellByColumnAndRow(18, $row)->getValue();
				}
				else 
				{
					$energy_type = $pod_data[2];

					for ($i=1;$i<4;$i++)
					{
						$pod_data = explode('_',$sheet->getCellByColumnAndRow(9, $row+$i)->getValue());
						if(count($pod_data) == 2 && $pod_data[0] == $pod)
						{				
							$consumption_location_id = '';
							$voltage_level_measurment = $sheet->getCellByColumnAndRow(12, $row+$i)->getValue();
							break;
						}				
					}
				}			
				$total_consumption_mu = $sheet->getCellByColumnAndRow(35, $row)->getValue();
			}
			elseif ($distributor_name == 'DEER Transilvania Sud')
			{
				if($this->cleanString($sheet->getCellByColumnAndRow(28, $row)->getValue() == 'DUPLICAT')) continue; //ignore storno
					
				$pod = $this->cleanString($sheet->getCellByColumnAndRow(9, $row)->getValue());
				$energy_type = $this->cleanString($sheet->getCellByColumnAndRow(18, $row)->getValue());
				$consumption_location_id =$pod;
				
				if(empty($voltage_level_measurment))
				{
					for ($i=1;$i<4;$i++)
					{
						if ($pod == $this->cleanString($this->cleanString($sheet->getCellByColumnAndRow(9, $row-$i)->getValue())))
							if(!empty($sheet->getCellByColumnAndRow(12, $row-$i)->getValue()))
							{
								$voltage_level_measurment = $sheet->getCellByColumnAndRow(12, $row-$i)->getValue();
								break;
							}							
					}
				}
				
				$total_consumption_mu = $sheet->getCellByColumnAndRow(35, $row)->getValue();
				if($energy_type == 'EA') $total_consumption_mu = 'kWh';
					else $total_consumption_mu = 'kVArh';
					
			}
			/*elseif ($distributor_name == 'DEER Muntenia Nord')
			{
				$pod = $this->cleanString($sheet->getCellByColumnAndRow(10, $row)->getValue());
				$energy_type = $this->cleanString($sheet->getCellByColumnAndRow(18, $row)->getValue());
				
				$consumption_location_id = (empty(trim($this->cleanString($sheet->getCellByColumnAndRow(9, $row)->getValue()))) ? '' : $pod);
				
				//Muntenia Nord
				if(empty($voltage_level_measurment))
					$sheet->getCellByColumnAndRow(12, $row+1)->getValue(); 
				
				if(empty($voltage_level_delimitation))
					$sheet->getCellByColumnAndRow(11, $row-1)->getValue();

				switch ($voltage_level_measurment)
				{
					case '6 KV':
					case '20 KV':
					case '400 V':
						$voltage_level_measurment = 'MT';
					break;
					case '230 V':
						$voltage_level_measurment = 'JT';
					break;						
				}
			}*/
			
			$tmp = $voltage_level_delimitation;$voltage_level_delimitation=$voltage_level_measurment;$voltage_level_measurment=$tmp;
			
					
			if(strpos($sheet->getCellByColumnAndRow(13, $row)->getValue(),'.') >0)
			{
				$invoice_start_date = $this->formatDateText($sheet->getCellByColumnAndRow(13, $row)->getValue(),'d.m.Y','Y-m-d');
				$invoice_end_date = $this->formatDateText($sheet->getCellByColumnAndRow(14, $row)->getValue(),'d.m.Y','Y-m-d');
			}
			else
			{
				$invoice_start_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(13, $row)->getValue()??0)->format('Y-m-d');
				$invoice_end_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(14, $row)->getValue()??0)->format('Y-m-d');
			}
			
			$reading_start_date = $this->formatDateText($sheet->getCellByColumnAndRow(15, $row)->getValue(),'d.m.Y','Y-m-d');
			$reading_end_date = $this->formatDateText($sheet->getCellByColumnAndRow(16, $row)->getValue(),'d.m.Y','Y-m-d');			
			
			$device_serial_number = $sheet->getCellByColumnAndRow(17, $row)->getValue();
			
			$index_old = $sheet->getCellByColumnAndRow(20, $row)->getValue();
			$index_new = $sheet->getCellByColumnAndRow(21, $row)->getValue();
			$total_consumption_ae = $sheet->getCellByColumnAndRow(31, $row)->getValue();
			$total_consumption_re = $sheet->getCellByColumnAndRow(32, $row)->getValue();
			$total_consumption_re_3x = $sheet->getCellByColumnAndRow(33, $row)->getValue();
			
			if($distributor_name == 'DEER Muntenia Nord' && empty($consumption_location_id)) //totalurile pe MN
			{
				$consumption_location_id = $pod;
			}
			
			if($distributor_name == 'DEER Transilvania Sud' and $this->cleanString($sheet->getCellByColumnAndRow(28, $row)->getValue()) == 'STORNO') //check is a storno
			{
				$sheet->setCellValueByColumnAndRow(28,$row,'DUPLICAT');
				$r = $row+1;
				$stornoFound = false;
				while($this->cleanString($sheet->getCellByColumnAndRow(9, $r)->getValue()) == $pod)
					if(empty($this->cleanString($sheet->getCellByColumnAndRow(28, $r)->getValue())) && 
						$total_consumption_ae == -$sheet->getCellByColumnAndRow(31, $r)->getValue() &&
						$total_consumption_re == -$sheet->getCellByColumnAndRow(32, $r)->getValue() &&
						$total_consumption_re_3x == -$sheet->getCellByColumnAndRow(33, $r)->getValue())
					{
						$stornoFound = true;
						break;
					}
					else
						$r++;
				
				if($stornoFound)
				{
					$sheet->setCellValueByColumnAndRow(28,$r,'DUPLICAT');
					$cleanup_dup+=1;
					continue;
				}
			}
			elseif($distributor_name == 'DEER Transilvania Sud' and empty($this->cleanString($sheet->getCellByColumnAndRow(28, $row)->getValue()))) //check if was storned later
			{
				$r = $row+1;
				$stornoFound = false;
				while($this->cleanString($sheet->getCellByColumnAndRow(9, $r)->getValue()) == $pod)
					if($this->cleanString($sheet->getCellByColumnAndRow(28, $r)->getValue() == 'STORNO' && 
						$total_consumption_ae == -$sheet->getCellByColumnAndRow(31, $r)->getValue() &&
						$total_consumption_re == -$sheet->getCellByColumnAndRow(32, $r)->getValue() &&
						$total_consumption_re_3x == -$sheet->getCellByColumnAndRow(33, $r)->getValue()))
					{
						$stornoFound = true;
						break;
					}
					else
						$r++;
				
				if($stornoFound)
				{
					$sheet->setCellValueByColumnAndRow(28,$row,'DUPLICAT');
					$sheet->setCellValueByColumnAndRow(28,$r,'DUPLICAT');
					$cleanup_dup+=1;
					continue;
				}
			}
			/*
				$total_consumption_re = $total_consumption_re_3x = 0;
				$energy_type = 'EA';
				$total_consumption_ae = $sheet->getCellByColumnAndRow(31, $row)->getValue();
				$total_consumption_mu = 'kWh';
				$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'xlsx');				
			
				$energy_type = 'ERI';
				$total_consumption_ae = 0;
				$total_consumption_re = $sheet->getCellByColumnAndRow(32, $row)->getValue();
				$total_consumption_re_3x = $sheet->getCellByColumnAndRow(33, $row)->getValue();
				$total_consumption_mu = 'kVArh';
				$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'xlsx');				
				
				$energy_type = 'ERC';
				$total_consumption_ae = 0;
				$total_consumption_re = $sheet->getCellByColumnAndRow(34, $row)->getValue();
				$total_consumption_re_3x = $sheet->getCellByColumnAndRow(35, $row)->getValue();
				$total_consumption_mu = 'kVArh';
				$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'xlsx');								
			}
			else*/
			
			if ( ($distributor_name == 'DEER Transilvania Sud' ) || ($distributor_name == 'DEER Transilvania Nord') )
			{
				//$profileCC = $this->numberToString($sheet->getCellByColumnAndRow(36, $row)->getValue()); //due to TN
				//$curveName = $this->numberToString($sheet->getCellByColumnAndRow(37, $row)->getValue());
				
				$profileCC = $this->cleanString($sheet->getCellByColumnAndRow(36, $row)->getValue()); //due to TN
				$curveName = $this->cleanString($sheet->getCellByColumnAndRow(37, $row)->getValue());
				
				if(!empty($curveName) && strpos($curveName,"E+") > 0) $curveName = $pod; //TN bug
				if(!empty($profileCC)) $profileCC = str_replace('EDTN','',$profileCC); //TN bug
				
				if(($distributor_name == 'DEER Transilvania Sud' ) && empty($curveName))
				{
					if(str_contains($pod,'5940201')) $curveName = 'SIN-BV';
					elseif(str_contains($pod,'5940202')) $curveName = 'SIN-SB';
					elseif(str_contains($pod,'5940203'))
					{
						if($profileCC=='3000000000056')
							$curveName = 'REZ-MS';
						else
							$curveName = 'SIN-MS';
					}
					elseif(str_contains($pod,'5940204')) $curveName = 'SIN-AB';
					elseif(str_contains($pod,'5940205')) $curveName = 'SIN-HR';
					elseif(str_contains($pod,'5940206')) $curveName = 'SIN-CV';
				}
				elseif(empty($curveName) && !empty($profileCC)) $curveName = $profileCC;

				$curveID = $this->importConsumptionsModel->saveCurveVariance($this->consumption_date, $distributor_name, $pod, $profileCC, $curveName);
			}
			elseif($distributor_name == 'DEER Muntenia Nord' )
			{
				$curveName = '';
				$profileCC = $this->cleanString($sheet->getCellByColumnAndRow(36, $row)->getValue());
				
				if(empty($profileCC)) $curveName = $pod;

				if(empty($curveName))
				{
					if(str_contains($pod,'5940301')) $curveName = 'SIN-PH';
					elseif(str_contains($pod,'5940302')) $curveName = 'SIN-BR';
					elseif(str_contains($pod,'5940303')) $curveName = 'SIN-BZ';
					elseif(str_contains($pod,'5940304')) $curveName = 'SIN-VR';
					elseif(str_contains($pod,'5940305')) $curveName = 'SIN-GL';
					elseif(!empty($profileCC)) $curveName = $profileCC;
				}
				$curveID = $this->importConsumptionsModel->saveCurveVariance($this->consumption_date, $distributor_name, $pod, $profileCC, $curveName);
			}
			
			
			if (!$this->importConsumptionsModel->import_check_duplicates_xlsx_in_pdf($pod,$energy_type,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x))
				$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,$curveName,$profileCC,'xlsx');
			else
				$cleanup_dup+=1;
		}
		
		$affected = $this->importSQLValues();
		
		$affected_cleanup = $this->importConsumptionsModel->import_cleanup_xlsx();
						
		return ['rows' => $row-1, 'imported' => $affected,'cleanup' => $affected_cleanup,'cleanup_dup'=>$cleanup_dup,'fileInfo' => $file_info];
	}

	public function import_DEER_TN_TS_MN2($fileName)
	{	
		log_message('error','import_DEER_TN_TS_MN2');			
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileName);
        
        $sheet = $spreadsheet->getActiveSheet();
		$file_info = 'Fisier import citiri';
		
		$row = 1;$cleanup_dup = 0;
		$this->initSQLValuesString();
		$podDevLocArr[] = '';
		while (true) {
			
			$row++;
			$distributor_name = $sheet->getCellByColumnAndRow(1, $row)->getValue();
			
			//if (in_array($distributor_name,array('Distributie En. El. Romania - Zona TN','Distributie Energie Electrica Romania-TS')))
				
			if($distributor_name == 'Distributie En. El. Romania')
			{
				$rawPOD = $sheet->getCellByColumnAndRow(9, $row)->getValue();
				if(empty($rawPOD)) continue;
				
				$ddata = substr($rawPOD,0,5);
				
				if($ddata == '59403')
					$distributor_name = 'DEER Muntenia Nord';
				elseif($ddata == '59404')
					$distributor_name = 'DEER Transilvania Nord';
				elseif($ddata == '59402')
					$distributor_name = 'DEER Transilvania Sud';
				else 
				{
					log_message('error',$ddata." not found");
					break;
				}
			}
			elseif ($distributor_name == 'Distributie En. El. Romania - Zona TN')
				$distributor_name = 'DEER Transilvania Nord';
			elseif ($distributor_name == 'Distributie Energie Electrica Romania-TS')
				$distributor_name = 'DEER Transilvania Sud';
			elseif ($distributor_name == 'DEER MN')
				$distributor_name = 'DEER Muntenia Nord';
			else
				break;
			
			$supplier_name = $this->importConsumptionsModel->get_supplier_name($sheet->getCellByColumnAndRow(2, $row)->getValue());
			$customer_name = $sheet->getCellByColumnAndRow(3, $row)->getValue();
			$customer_code = $sheet->getCellByColumnAndRow(4, $row)->getValue();
			$contract_number = $sheet->getCellByColumnAndRow(5, $row)->getValue();
			//$contract_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(6, $row)->getValue()??0)->format('Y-m-d');
			

			$voltage_level_delimitation = $sheet->getCellByColumnAndRow(11, $row)->getValue();
			$voltage_level_measurment	 = $sheet->getCellByColumnAndRow(12, $row)->getValue();
			
			

			//if (($distributor_name == 'DEER Transilvania Nord') || ($distributor_name == 'DEER Muntenia Nord'))
			{
				if($this->cleanString($sheet->getCellByColumnAndRow(28, $row)->getValue() == 'DUPLICAT')) continue; //ignore storno
				$pod_data = explode('_',$rawPOD);			
				$pod = $pod_data[0];
				$consumption_location_id='';
				
				if(count($pod_data) == 2)
				{				
					$consumption_location_id = $pod_data[1];
					$voltage_level_measurment = $sheet->getCellByColumnAndRow(12, $row)->getValue();
					$energy_type = $sheet->getCellByColumnAndRow(18, $row)->getValue();
					
					if(!isset($podDevLocArr[$pod]))
					{
						$podDevLocArr[$pod] = $pod_data[1];
						$this->importConsumptionsModel->savePodDevLoc($pod,$pod_data[1]);
					}
				}
				else 
				{
					$energy_type = $pod_data[2];

					for ($i=1;$i<4;$i++)
					{
						$pod_data = explode('_',$sheet->getCellByColumnAndRow(9, $row+$i)->getValue());
						if(count($pod_data) == 2 && $pod_data[0] == $pod)
						{				
							$consumption_location_id = '';
							$voltage_level_measurment = $sheet->getCellByColumnAndRow(12, $row+$i)->getValue();
							break;
						}				
					}
				}			
				$total_consumption_mu = $sheet->getCellByColumnAndRow(35, $row)->getValue();
			}
			
			$tmp = $voltage_level_delimitation;$voltage_level_delimitation=$voltage_level_measurment;$voltage_level_measurment=$tmp;
			
					
			if(strpos($sheet->getCellByColumnAndRow(13, $row)->getValue(),'.') >0)
			{
				$invoice_start_date = $this->formatDateText($sheet->getCellByColumnAndRow(13, $row)->getValue(),'d.m.Y','Y-m-d');
				$invoice_end_date = $this->formatDateText($sheet->getCellByColumnAndRow(14, $row)->getValue(),'d.m.Y','Y-m-d');
			}
			else
			{
				$invoice_start_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(13, $row)->getValue()??0)->format('Y-m-d');
				$invoice_end_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(14, $row)->getValue()??0)->format('Y-m-d');
			}
			
			$reading_start_date = $this->formatDateText($sheet->getCellByColumnAndRow(15, $row)->getValue(),'d.m.Y','Y-m-d');
			$reading_end_date = $this->formatDateText($sheet->getCellByColumnAndRow(16, $row)->getValue(),'d.m.Y','Y-m-d');			
			
			$device_serial_number = $sheet->getCellByColumnAndRow(17, $row)->getValue();
			
			$index_old = $sheet->getCellByColumnAndRow(20, $row)->getValue();
			$index_new = $sheet->getCellByColumnAndRow(21, $row)->getValue();
			$total_consumption_ae = $sheet->getCellByColumnAndRow(31, $row)->getValue();
			$total_consumption_re = $sheet->getCellByColumnAndRow(32, $row)->getValue();
			$total_consumption_re_3x = $sheet->getCellByColumnAndRow(33, $row)->getValue();
			
			if($distributor_name == 'DEER Muntenia Nord' && empty($consumption_location_id)) //totalurile pe MN
			{
				$consumption_location_id = $pod;
			}
			
			if($distributor_name == 'DEER Transilvania Sud' and $this->cleanString($sheet->getCellByColumnAndRow(28, $row)->getValue()) == 'STORNO') //check is a storno
			{
				$sheet->setCellValueByColumnAndRow(28,$row,'DUPLICAT');
				$r = $row+1;
				$stornoFound = false;
				while($this->cleanString($sheet->getCellByColumnAndRow(9, $r)->getValue()) == $pod)
					if(empty($this->cleanString($sheet->getCellByColumnAndRow(28, $r)->getValue())) && 
						$total_consumption_ae == -$sheet->getCellByColumnAndRow(31, $r)->getValue() &&
						$total_consumption_re == -$sheet->getCellByColumnAndRow(32, $r)->getValue() &&
						$total_consumption_re_3x == -$sheet->getCellByColumnAndRow(33, $r)->getValue())
					{
						$stornoFound = true;
						break;
					}
					else
						$r++;
				
				if($stornoFound)
				{
					$sheet->setCellValueByColumnAndRow(28,$r,'DUPLICAT');
					$cleanup_dup+=1;
					continue;
				}
			}
			elseif($distributor_name == 'DEER Transilvania Sud' and empty($this->cleanString($sheet->getCellByColumnAndRow(28, $row)->getValue()))) //check if was storned later
			{
				$r = $row+1;
				$stornoFound = false;
				while($this->cleanString($sheet->getCellByColumnAndRow(9, $r)->getValue()) == $pod)
					if($this->cleanString($sheet->getCellByColumnAndRow(28, $r)->getValue() == 'STORNO' && 
						$total_consumption_ae == -$sheet->getCellByColumnAndRow(31, $r)->getValue() &&
						$total_consumption_re == -$sheet->getCellByColumnAndRow(32, $r)->getValue() &&
						$total_consumption_re_3x == -$sheet->getCellByColumnAndRow(33, $r)->getValue()))
					{
						$stornoFound = true;
						break;
					}
					else
						$r++;
				
				if($stornoFound)
				{
					$sheet->setCellValueByColumnAndRow(28,$row,'DUPLICAT');
					$sheet->setCellValueByColumnAndRow(28,$r,'DUPLICAT');
					$cleanup_dup+=1;
					continue;
				}
			}
				
			$profileCC = $this->cleanString($sheet->getCellByColumnAndRow(36, $row)->getValue()); //due to TN
			$curveName = $this->cleanString($sheet->getCellByColumnAndRow(37, $row)->getValue());
			
			if(!empty($curveName) && strpos($curveName,"E+") > 0) $curveName = $pod; //TN bug
			if(!empty($profileCC)) $profileCC = str_replace('EDTN','',$profileCC); //TN bug
			if(empty($curveName) && !empty($profileCC)) $curveName = $profileCC;

			$curveID = $this->importConsumptionsModel->saveCurveVariance($this->consumption_date, $distributor_name, $pod, $profileCC, $curveName);

			if (!$this->importConsumptionsModel->import_check_duplicates_xlsx_in_pdf($pod,$energy_type,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x))
				$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,$curveName,$profileCC,'xlsx');
			else
				$cleanup_dup+=1;
		}
		
		$affected = $this->importSQLValues();
		
		$affected_cleanup = $this->importConsumptionsModel->import_cleanup_xlsx();
						
		return ['rows' => $row-1, 'imported' => $affected,'cleanup' => $affected_cleanup,'cleanup_dup'=>$cleanup_dup,'fileInfo' => $file_info];
	}
	
	public function import_DEER_TN_pdf($pdfText)
	{

		log_message('error',$pdfText);
		
		$row = $affected = 0;
		$status = 'continue';
		$file_info = 'Fisier import citiri'; 
		$this->initSQLValuesString();
		
		$distributor_name = $supplier_name = $customer_name = $customer_code = $contract_number = $consumption_location_id = $pod = $voltage_level_delimitation = $voltage_level_measurment = $invoice_start_date = $invoice_end_date = $reading_start_date = $reading_end_date = $device_serial_number = $energy_type = $index_old = $index_new = $total_consumption_mu = '';
		$total_consumption_ae = $total_consumption_re = $total_consumption_re_3x = 0;
		
		//================DATE GENERALE: Distribuitor, Furnizor==========================================================================================//
		if (strpos($pdfText,"Distributie Energie Electrica Romania S.A.") !== FALSE) $distributor_name='DEER Transilvania Nord';
		else return -1;
		
		$supplier_name = $this->importConsumptionsModel->get_supplier_name($this->get_string_between($pdfText,"Furnizor:",","));
		if (empty($supplier_name)) 
		{
			return -2;
		}
		
		if (strpos($supplier_name,"Adresa furnizor:"))
			return $this->import_DEER_TS_pdf($pdfText);

		//================DATE PV: Nume Client,Cod Client, Nr Contract, Date Factura, Nivel Tensiune, POD ==========================================================================================//		
		//$pv_array = explode('Date de masurare contor de energie electrica', $pdfText);
		$pv_array = explode('contor de energie electrica', $pdfText);
		foreach (array_slice($pv_array,1) as $pv) 
		{
			$contract_number = $device_serial_number = $reading_start_date = $reading_end_date = $consumption_location_id = '';
						
			$invoice_dates = $this->get_string_between($pv,'perioada: ');

			if (empty($invoice_dates)) return -4;
			
			$invoice_dates_array = explode(' - ',$invoice_dates);
			if (count($invoice_dates_array)!=2) return -4;
			$invoice_start_date = $this->formatDateText($invoice_dates_array[0],'d.m.Y','Y-m-d');
			$invoice_end_date = $this->formatDateText($invoice_dates_array[1],'d.m.Y','Y-m-d');
			
			$customer_name =  $this->get_string_between($pv,'Nume consumator: ');
			if (empty($customer_name)) return -6;
			
			$customer_code = $this->get_string_between($pv,'Cod consumator: ');
			
			$pod = $this->get_string_between($pv,'POD: ');
			if (empty($pod)) return -8;
			
			$voltage_level_delimitation = $this->get_string_between($pv,'Nivel tensiune in punctul de delimitare:');
			if ($voltage_level_delimitation == 'joasa tensiune') $voltage_level_delimitation='JT';
			if ($voltage_level_delimitation == 'medie tensiune') $voltage_level_delimitation='MT';
			if ($voltage_level_delimitation == 'inalta tensiune') $voltage_level_delimitation='IT';
			
			if (!in_array($voltage_level_delimitation, array('JT','MT','IT'))) 
				return -5;

			if (strpos($this->get_string_between($pv,'Tensiunea in punctul de masurare:'), '230') !== FALSE ) 
				$voltage_level_measurment = 'JT';
			else 
				$voltage_level_measurment = 'MT';
			
			$tmp = $voltage_level_delimitation;$voltage_level_delimitation=$voltage_level_measurment;$voltage_level_measurment=$tmp;
			
			//log_message('error','POD:'.$pod);
			
			//================TOTALURI =========================================================================================================================//
			$total_data_array = explode('Cantitati de facturat',$pv);
			foreach (array_slice($total_data_array,1) as $td)
			{
				$data_array = $this->get_array_between($td,'Energie activa');
				//log_message('info',print_r($data_array,true));
				if(empty($data_array)) $data_array = $this->get_array_between($td,'Energie Activa');
				
				if(!empty($data_array) && count($data_array)==2)
				{
						$energy_type = 'EA';
						
						$total_consumption_ae = $this->getNumber($data_array[0]);
						$total_consumption_mu = $data_array[1];
						
						$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
						
						$total_consumption_ae = 0;
				}
				
				$data_array = $this->get_array_between($td,'Energie React. Ind Reg. 1');
				if(!empty($data_array) && count($data_array)==2)
				{
						$energy_type = 'ERI';
						
						$total_consumption_re = $this->getNumber($data_array[0]);
						$total_consumption_mu = $data_array[1];
				}
				
				$data_array3 = $this->get_array_between($td,'Energie React. Ind Reg. 3');
				if(!empty($data_array3) && count($data_array3)==2) 
				{
						$energy_type = 'ERI';
						$total_consumption_re_3x = $this->getNumber($data_array3[0]);
						$total_consumption_mu = $data_array3[1];
				}
				
				if($total_consumption_re != 0 || $total_consumption_re_3x !=0)
				{
						$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');						
						$total_consumption_re = $total_consumption_re_3x = 0;
				}
				
				$data_array = $this->get_array_between($td,'Energie React. Cap Reg. 1');
				if(!empty($data_array) && count($data_array)==2)
				{
						$energy_type = 'ERC';
						
						$total_consumption_re = $this->getNumber($data_array[0]);
						$total_consumption_mu = $data_array[1];
				}				
				
				$data_array3 = $this->get_array_between($td,'Energie React. Cap Reg. 3');
				if(!empty($data_array3) && count($data_array3)==2) 
				{	
					
					$energy_type = 'ERC';
					$total_consumption_re_3x = $this->getNumber($data_array3[0]);
					$total_consumption_mu = $data_array3[1];					
				}
				
				if($total_consumption_re != 0 || $total_consumption_re_3x !=0)
				{
						$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');						
						$total_consumption_re = $total_consumption_re_3x = 0;
				}
				
			}
			
			
			//================DATE CONTOR: Serie, Citiri, Date Citiri ==========================================================================================//		
			$contor_data_array = explode('Serie contor:',$pv);
			foreach (array_slice($contor_data_array,1) as $cd) 
			{
				$device_serial_number = $this->get_string_between($cd,'','Locatie dispozitiv:');
				if (empty($device_serial_number)) return -9;
				
				$consumption_location_id = $this->get_string_between($cd,'Locatie dispozitiv:');
				if (empty($consumption_location_id)) return -12;
				
				$reading_dates = $this->get_string_between($cd,'Perioada:','Tensiunea in punctul de masurare:');
				if (empty($reading_dates)) return -11;
				$reading_dates_array = explode(' - ',$reading_dates);
				if (count($reading_dates_array)!=2) return -11;
				$reading_start_date = $this->formatDateText($reading_dates_array[0],'d.m.Y','Y-m-d');
				$reading_end_date = $this->formatDateText($reading_dates_array[1],'d.m.Y','Y-m-d');
				
				$index_old = $index_new = $total_consumption_ae = $total_consumption_re = $total_consumption_re_3x = 0;
				
				$data_array = $this->get_array_between($cd,'Cant. Energie Activa');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'EA';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'KWh'; //UM apare rar sau la facturat
					
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					
					$index_old=$index_new=$total_consumption_mu='';
				}

				$data_array = $this->get_array_between($cd,'Cant. En. Reactiva Ind.');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'ERI';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'kVArh'; //UM apare rar sau la facturat
					
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					
					$index_old=$index_new=$total_consumption_mu='';
				}

				$data_array = $this->get_array_between($cd,'Cant. En. Reactiva Cap.');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'ERC';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'kVArh'; //UM apare rar sau la facturat
		
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
						
					$index_old=$index_new=$total_consumption_mu='';
				}
			}
		}
		
		$affected = $this->importSQLValues();
		$row = $this->sqlValuesLines;			
		
		return ['rows' => $row, 'imported' => $affected,'fileInfo' => $file_info];
	}
	
	public function import_DEER_TS_pdf($pdfText)
	{
		log_message('info','import TS PDF'/*$pdfText*/);

		$row = $affected = 0;
		$status = 'continue';
		$file_info = 'Fisier import citiri'; 
		$this->initSQLValuesString();
		
		$distributor_name = $supplier_name = $customer_name = $customer_code = $contract_number = $consumption_location_id = $pod = $voltage_level_delimitation = $voltage_level_measurment = $invoice_start_date = $invoice_end_date = $reading_start_date = $reading_end_date = $device_serial_number = $energy_type = $index_old = $index_new = $total_consumption_mu = '';
		$total_consumption_ae = $total_consumption_re = $total_consumption_re_3x = 0;
		
		//================DATE GENERALE: Distribuitor, Furnizor==========================================================================================//
		//if (strpos($pdfText,"SDEE Transilvania Sud") !== FALSE) $distributor_name='DEER Transilvania Sud';
		if (strpos($pdfText,"Distributie Energie Electrica Romania S.A. Zona MN") !== FALSE) 	
			$distributor_name='DEER Muntenia Nord';
		elseif (strpos($pdfText,"Distributie Energie Electrica Romania S.A.") !== FALSE) 
			$distributor_name='DEER Transilvania Sud';
		else return -1;
		
		$supplier_name = $this->importConsumptionsModel->get_supplier_name($this->get_string_between($pdfText,"Furnizor:","Fax"));
		if (empty($supplier_name)) return -2;
		
		//log_message('info',print_r($distributor_name,true));

		//================DATE PV: Nume Client,Cod Client, Nr Contract, Date Factura, Nivel Tensiune, POD ==========================================================================================//		
		$pv_array = explode('Date de consum de energie electrica', $pdfText);
		foreach (array_slice($pv_array,1) as $pv) 
		{
			$contract_number = $device_serial_number = $reading_start_date = $reading_end_date = $consumption_location_id = '';
						
			$invoice_dates = $this->get_string_between($pv,'perioada: ');

			if (empty($invoice_dates)) return -4;
			
			$invoice_dates_array = explode(' - ',$invoice_dates);
			if (count($invoice_dates_array)!=2) return -4;
			$invoice_start_date = $this->formatDateText($invoice_dates_array[0],'d.m.Y','Y-m-d');
			$invoice_end_date = $this->formatDateText($invoice_dates_array[1],'d.m.Y','Y-m-d');
			
			$customer_name =  $this->get_string_between($pv,'Nume consumator:');
			if (empty($customer_name)) return -6;
			
			$customer_code = $this->get_string_between($pv,'Cod consumator:');
			
			$pod = $this->get_string_between($pv,'POD:');
			if (empty($pod)) return -8;
			
			$consumption_location_id = $pod; //$this->get_string_between($cd,'Locatie dispozitiv:'); //lipseste in excel
			if (empty($consumption_location_id)) return -12;
			
			$voltage_level_delimitation = $this->get_string_between($pv,'Nivel tensiune in punctul de delimitare:');
			if ($voltage_level_delimitation == 'joasa tensiune') $voltage_level_delimitation='JT';
			if ($voltage_level_delimitation == 'medie tensiune') $voltage_level_delimitation='MT';
			if ($voltage_level_delimitation == 'inalta tensiune') $voltage_level_delimitation='IT';
			
			if (!in_array($voltage_level_delimitation, array('JT','MT','IT'))) 
				return -5;

			if (strpos($this->get_string_between($pv,'Tensiunea in punctul de masurare:'), '230') !== FALSE ) 
				$voltage_level_measurment = 'JT';
			else 
				$voltage_level_measurment = 'MT';
			
			$tmp = $voltage_level_delimitation;$voltage_level_delimitation=$voltage_level_measurment;$voltage_level_measurment=$tmp;
			
			//================TOTALURI =========================================================================================================================//
			//log_message('info',$pod);
			//excel sync
			$ea_registered = false; $total_consumption_ae = 0;
			
			/* -->removed since we check overall totals!
			
			$ea_arr1 = $this->get_array_between($pv,'Cant. Energie Activa Citita',"\n",5);			
			if (!empty($ea_arr1))
			{
				$ea_arr2 = $this->get_array_between($pv,'Cant.Energie Activa Estimata');
				//log_message('info',print_r($ea_arr2,true));
				if (!empty($ea_arr2) && count($ea_arr2) == 1)
				{
					$energy_type = 'EA';
					$total_consumption_mu = 'kWh';
					
					if (gettype($ea_arr1[0]) == 'array')
						foreach($ea_arr1 as $e)
						{
							$total_consumption_ae += $this->getNumber($e[4]);	
						}
					else
						$total_consumption_ae = $this->getNumber($ea_arr1[4]);
					
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					
					$total_consumption_ae = $this->getNumber($ea_arr2[0]);	
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					
					$total_consumption_ae = 0;
					
					$ea_registered = true;
				}
			}
			*/
			
			//total energii
			$total_data_array = explode('Cantitati de facturat',$pv);
			//if($pod == '594020100002846892')
						//log_message('info',print_r($total_data_array,true));
					
			foreach (array_slice($total_data_array,1) as $td)
			{
				//if(!$ea_registered)
				{
					$data_array = $this->get_array_between($td,'Energie activa');
					
					if(empty($data_array)) $data_array = $this->get_array_between($td,'Energie Activa');
										
					if(!empty($data_array))//xls sync
					{
							$idx = count($data_array)-2;
							
							if($idx>=0)
							{
								
								$energy_type = 'EA';
								
								$total_consumption_ae = $this->getNumber($data_array[$idx]);
								$total_consumption_mu = $data_array[$idx+1];
								
								if(!$ea_registered || ($ea_registered && $total_consumption_ae<0))
								{
									$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
									
									$total_consumption_ae = 0;
								}
							}
					}
				}
				
				$total_consumption_ae = 0;
				$data_array = $this->get_array_between($td,'En.react.ind.cos phi[0,65-0,9)');
				//log_message('info',print_r($data_array,true));
				if(!empty($data_array) && count($data_array)==2)
				{
						$energy_type = 'ERI';
						
						$total_consumption_re = $this->getNumber($data_array[0]);
						$total_consumption_mu = $data_array[1];
				}
				
				$data_array3 = $this->get_array_between($td,'En.react.ind.cos phi<0,65');
				if(!empty($data_array3) && count($data_array3)==2) 
				{
						$energy_type = 'ERI';
						$total_consumption_re_3x = $this->getNumber($data_array3[0]);
						$total_consumption_mu = $data_array3[1];
				}
				
				if($total_consumption_re != 0 || $total_consumption_re_3x !=0)
				{
						$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');						
						$total_consumption_re = $total_consumption_re_3x = 0;
				}
				
				$data_array = $this->get_array_between($td,'En.react.cap.cos phi[0,65-0,9)');
				if(!empty($data_array) && count($data_array)==2)
				{
						$energy_type = 'ERC';
						
						$total_consumption_re = $this->getNumber($data_array[0]);
						$total_consumption_mu = $data_array[1];
				}				
				
				$data_array3 = $this->get_array_between($td,'En.react.cap.cos phi<0,65');
				if(!empty($data_array3) && count($data_array3)==2) 
				{	
					
					$energy_type = 'ERC';
					$total_consumption_re_3x = $this->getNumber($data_array3[0]);
					$total_consumption_mu = $data_array3[1];					
				}
				
				if($total_consumption_re != 0 || $total_consumption_re_3x !=0)
				{
						$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
						$total_consumption_re = $total_consumption_re_3x = 0;
				}	
				
			}
			
			
			//================DATE CONTOR: Serie, Citiri, Date Citiri ==========================================================================================//		
			$contor_data_array = explode('Serie contor:',$pv);
			foreach (array_slice($contor_data_array,1) as $cd) 
			{
				$device_serial_number = $this->get_string_between($cd,'','Locatie dispozitiv:');
				if (empty($device_serial_number)) return -9;
				
				$reading_dates = $this->get_string_between($cd,'Perioada:','Tensiunea in punctul de masurare:');
				if (empty($reading_dates)) return -11;
				$reading_dates_array = explode(' - ',$reading_dates);
				if (count($reading_dates_array)!=2) return -11;
				$reading_start_date = $this->formatDateText($reading_dates_array[0],'d.m.Y','Y-m-d');
				$reading_end_date = $this->formatDateText($reading_dates_array[1],'d.m.Y','Y-m-d');
				
				$index_old = $index_new = $total_consumption_ae = $total_consumption_re = $total_consumption_re_3x = 0;
				
				$data_array = $this->get_array_between($cd,'Cant. Energie Activa');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'EA';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'kWh'; //UM apare rar sau la facturat
					
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					
					$index_old=$index_new=$total_consumption_mu='';
				}

				$data_array = $this->get_array_between($cd,'Cant. En. Reactiva Ind.');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'ERI';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'kVArh'; //UM apare rar sau la facturat
					
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					
					$index_old=$index_new=$total_consumption_mu='';
				}

				$data_array = $this->get_array_between($cd,'Cant. En. Reactiva Cap.');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'ERC';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'kVArh'; //UM apare rar sau la facturat
					
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					
					$index_old=$index_new=$total_consumption_mu='';
				}
			}
		}
		
		$affected = $this->importSQLValues();
		$row = $this->sqlValuesLines;

		return ['rows' => $row, 'imported' => $affected,'fileInfo' => $file_info];
	}
	
	public function import_DEER_MN_pdf($pdfText) //la fel cu DEER_TS_pdf cu exceptia furnizorului si totalurile ERI/ERC
	{
		log_message('info','import MN PDF'/*$pdfText*/);
		$row = $affected = 0;
		$status = 'continue';
		$file_info = 'Fisier import citiri'; 
		$this->initSQLValuesString();
		
		$distributor_name = $supplier_name = $customer_name = $customer_code = $contract_number = $consumption_location_id = $pod = $voltage_level_delimitation = $voltage_level_measurment = $invoice_start_date = $invoice_end_date = $reading_start_date = $reading_end_date = $device_serial_number = $energy_type = $index_old = $index_new = $total_consumption_mu = '';
		$total_consumption_ae = $total_consumption_re = $total_consumption_re_3x = 0;

		//================DATE GENERALE: Distribuitor, Furnizor==========================================================================================//
		if (strpos($pdfText,"Distributie Energie Electrica Romania S.A. Zona MN") !== FALSE) $distributor_name='DEER Muntenia Nord';
		else return -1;
		
		$supplier_name = $this->importConsumptionsModel->get_supplier_name($this->get_string_between($pdfText,"Furnizor:","Fax"));
		if (empty($supplier_name)) return -2;

		//================DATE PV: Nume Client,Cod Client, Nr Contract, Date Factura, Nivel Tensiune, POD ==========================================================================================//		
		$pv_array = explode('Date de consum de energie electrica', $pdfText);
		foreach (array_slice($pv_array,1) as $pv) 
		{
			$contract_number = $device_serial_number = $reading_start_date = $reading_end_date = $consumption_location_id = '';
						
			$invoice_dates = $this->get_string_between($pv,'perioada: ');

			if (empty($invoice_dates)) return -4;
			
			$invoice_dates_array = explode(' - ',$invoice_dates);
			if (count($invoice_dates_array)!=2) return -4;
			$invoice_start_date = $this->formatDateText($invoice_dates_array[0],'d.m.Y','Y-m-d');
			$invoice_end_date = $this->formatDateText($invoice_dates_array[1],'d.m.Y','Y-m-d');
			
			$customer_name =  $this->get_string_between($pv,'Nume consumator:');
			if (empty($customer_name)) return -6;
			
			$customer_code = $this->get_string_between($pv,'Cod consumator:');
			
			$pod = $this->get_string_between($pv,'POD:');
			if (empty($pod)) return -8;
			
			$consumption_location_id = $pod; //$this->get_string_between($cd,'Locatie dispozitiv:'); //lipseste in excel
			if (empty($consumption_location_id)) return -12;
			
			$voltage_level_delimitation = $this->get_string_between($pv,'Nivel tensiune in punctul de delimitare:');
			if ($voltage_level_delimitation == 'joasa tensiune') $voltage_level_delimitation='JT';
			if ($voltage_level_delimitation == 'medie tensiune') $voltage_level_delimitation='MT';
			if ($voltage_level_delimitation == 'inalta tensiune') $voltage_level_delimitation='IT';
			
			if (!in_array($voltage_level_delimitation, array('JT','MT','IT'))) 
				return -5;

			if (strpos($this->get_string_between($pv,'Tensiunea in punctul de masurare:'), '230') !== FALSE ) 
				$voltage_level_measurment = 'JT';
			else 
				$voltage_level_measurment = 'MT';
			
			$tmp = $voltage_level_delimitation;$voltage_level_delimitation=$voltage_level_measurment;$voltage_level_measurment=$tmp;
			
			//================TOTALURI =========================================================================================================================//
			
			//excel sync
			/*$total_ae_estimat = 0;
			
			$ea_arr1 = $this->get_array_between($pv,'Cant. Energie Activa Citita',"\n",5);			
			if (!empty($ea_arr1))
			{
				$ea_arr2 = $this->get_array_between($pv,'Cant.Energie Activa Estimata');
				if (!empty($ea_arr2) && count($ea_arr2) == 1)
				{					
					if (gettype($ea_arr1[0]) == 'array')
						foreach($ea_arr1 as $e)
						{
							$total_ae_estimat += $this->getNumber($e[4]);	
						}
					else
						$total_ae_estimat = $this->getNumber($ea_arr1[4]);
				}
			}*/
		
			//total energii
			$total_data_array = explode('Cantitati de facturat',$pv);
			foreach (array_slice($total_data_array,1) as $td)
			{

				$total_consumption_ae = 0;
				$data_array = $this->get_array_between($td,'Energie',"\n",5);
				//log_message('info',print_r($data_array,true));
				
				foreach($data_array as $da)
				{
					
					//log_message('info',print_r($da,true));
					$idx = count($da)-2;
					
					if($idx>=0)
					{
						$energy_type = 'EA';
						
						$total_consumption_ae += $this->getNumber($da[$idx]);
						$total_consumption_mu = $da[$idx+1];					
					}
				}
				
				if ($total_consumption_ae != 0)
				{
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					$total_consumption_ae = 0;
				}

				
				$data_array = $this->get_array_between($td,'En.react.ind.cosphi[0,65-0,90)');
				if(!empty($data_array) && count($data_array)==2)
				{
						$energy_type = 'ERI';
						
						$total_consumption_re = $this->getNumber($data_array[0]);
						$total_consumption_mu = $data_array[1];
				}
				
				$data_array3 = $this->get_array_between($td,'En.react.ind.cosphi<0,65');
				if(!empty($data_array3) && count($data_array3)==2) 
				{
						$energy_type = 'ERI';
						$total_consumption_re_3x = $this->getNumber($data_array3[0]);
						$total_consumption_mu = $data_array3[1];
				}
				
				if($total_consumption_re != 0 || $total_consumption_re_3x !=0)
				{
						$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');						
						$total_consumption_re = $total_consumption_re_3x = 0;
				}
				
				$data_array = $this->get_array_between($td,'En.react.cap.cosphi [0,65-0,9)');
				if(!empty($data_array) && count($data_array)==2)
				{
						$energy_type = 'ERC';
						
						$total_consumption_re = $this->getNumber($data_array[0]);
						$total_consumption_mu = $data_array[1];
				}				
				
				$data_array3 = $this->get_array_between($td,'En.react.cap.cosphi<0,65');
				if(!empty($data_array3) && count($data_array3)==2) 
				{	
					
					$energy_type = 'ERC';
					$total_consumption_re_3x = $this->getNumber($data_array3[0]);
					$total_consumption_mu = $data_array3[1];					
				}
				
				if($total_consumption_re != 0 || $total_consumption_re_3x !=0)
				{
						$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
						$total_consumption_re = $total_consumption_re_3x = 0;
				}	
				
			}
			
			
			//================DATE CONTOR: Serie, Citiri, Date Citiri ==========================================================================================//		
			$contor_data_array = explode('Serie contor:',$pv);
			foreach (array_slice($contor_data_array,1) as $cd) 
			{
				$device_serial_number = $this->get_string_between($cd,'','Locatie dispozitiv:');
				if (empty($device_serial_number)) return -9;
				
				$reading_dates = $this->get_string_between($cd,'Perioada:','Tensiunea in punctul de masurare:');
				if (empty($reading_dates)) return -11;
				$reading_dates_array = explode(' - ',$reading_dates);
				if (count($reading_dates_array)!=2) return -11;
				$reading_start_date = $this->formatDateText($reading_dates_array[0],'d.m.Y','Y-m-d');
				$reading_end_date = $this->formatDateText($reading_dates_array[1],'d.m.Y','Y-m-d');
				
				$index_old = $index_new = $total_consumption_ae = $total_consumption_re = $total_consumption_re_3x = 0;
				
				$data_array = $this->get_array_between($cd,'Cant. Energie Activa');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'EA';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'kWh'; //UM apare rar sau la facturat
					
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					
					$index_old=$index_new=$total_consumption_mu='';
				}

				$data_array = $this->get_array_between($cd,'Cant. En. Reactiva Ind.');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'ERI';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'kVArh'; //UM apare rar sau la facturat
					
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					
					$index_old=$index_new=$total_consumption_mu='';
				}

				$data_array = $this->get_array_between($cd,'Cant. En. Reactiva Cap.');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'ERC';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'kVArh'; //UM apare rar sau la facturat
					
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					
					$index_old=$index_new=$total_consumption_mu='';
				}
			}
		}
		
		$affected = $this->importSQLValues();
		$row = $this->sqlValuesLines;
		
		return ['rows' => $row, 'imported' => $affected,'fileInfo' => $file_info];
	}

	public function import_DELGAZ($fileName)
	{	
	
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileName);
        
        $sheet = $spreadsheet->getActiveSheet();
		$file_info = 'Fisier import citiri';
		
		$row = 1;$cleanup_dup = 0;
		$this->initSQLValuesString();
		
		while (true) {
			$device_serial_number = $index_old=$index_new = ''; $reading_start_date=$reading_end_date='1970-01-01';
			
			$row++;
			$distributor_name = $sheet->getCellByColumnAndRow(1, $row)->getValue();
			if ($distributor_name === NULL) break;
			if($distributor_name == 'Delgaz Grid SA') $distributor_name='DELGAZ GRID S.A.';
			
			$supplier_name = $this->importConsumptionsModel->get_supplier_name($sheet->getCellByColumnAndRow(2, $row)->getValue());
						
			$customer_name = $sheet->getCellByColumnAndRow(3, $row)->getValue();
			$customer_code = $sheet->getCellByColumnAndRow(4, $row)->getValue();
			$contract_number = $sheet->getCellByColumnAndRow(5, $row)->getValue();
			//$contract_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(6, $row)->getValue()??0)->format('Y-m-d');
			$pod = $sheet->getCellByColumnAndRow(11, $row)->getValue();
			$consumption_location_id = $sheet->getCellByColumnAndRow(12, $row)->getValue();
			$pod = $consumption_location_id; //exceptia
			
			$customer_code = $consumption_location_id; // nu e in pdf-uri
			
			$voltage_level_delimitation = $sheet->getCellByColumnAndRow(7, $row)->getValue();
			$voltage_level_measurment = $sheet->getCellByColumnAndRow(8, $row)->getValue();
			$invoice_start_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(13, $row)->getValue()??0)->format('Y-m-d');
			$invoice_end_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(14, $row)->getValue()??0)->format('Y-m-d');
			
			
			//$reading_start_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(15, $row)->getValue()??0)->format('Y-m-d');
			//$reading_end_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(16, $row)->getValue()??0)->format('Y-m-d');
			//$device_serial_number = $sheet->getCellByColumnAndRow(17, $row)->getValue();
			$energy_type = $sheet->getCellByColumnAndRow(18, $row)->getValue();
			if($energy_type[0]=='I') 
				$energy_type = substr($energy_type,1); // ignora prima litera
			//$index_old = $sheet->getCellByColumnAndRow(20, $row)->getValue();
			//$index_new = $sheet->getCellByColumnAndRow(21, $row)->getValue();
			$total_consumption_ae = $sheet->getCellByColumnAndRow(30, $row)->getValue();
			$total_consumption_re = $sheet->getCellByColumnAndRow(31, $row)->getValue();
			$total_consumption_re_3x = $sheet->getCellByColumnAndRow(32, $row)->getValue();
			
			switch (strtolower($sheet->getCellByColumnAndRow(34, $row)->getValue()))
			{
				case 'kwh': 
					$total_consumption_mu='kWh';
					break;
				case 'kvarh':
				case 'var': 
					$total_consumption_mu = 'kVArh';
					break;
				default: 	
					$total_consumption_mu = $sheet->getCellByColumnAndRow(34, $row)->getValue();
			}
		
			
			$profileCC = $this->cleanString($sheet->getCellByColumnAndRow(35, $row)->getValue());
			$curveName = $this->cleanString($sheet->getCellByColumnAndRow(36, $row)->getValue());
			
			if($profileCC == 'PROFIL LA 15 min') $profileCC = '';
			elseif($profileCC == 'REZ001') $curveName = 'REZ001-'.$voltage_level_delimitation;
			elseif (!empty($profileCC)) $curveName = 'SIN001-'.$voltage_level_delimitation;
			
			$curveID = $this->importConsumptionsModel->saveCurveVariance($this->consumption_date, $distributor_name, $pod, $profileCC, $curveName);

			//linii separate pt sync cu pdf
			if($total_consumption_re != 0)
			{
				if (!$this->importConsumptionsModel->import_check_duplicates_xlsx_in_pdf($pod,$energy_type,$total_consumption_ae,$total_consumption_re,0))
				$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,0,$total_consumption_mu,$curveName,$profileCC,'xlsx');
				else
					$cleanup_dup+=1;
			}
			
			if($total_consumption_re_3x != 0)
			{
				if (!$this->importConsumptionsModel->import_check_duplicates_xlsx_in_pdf($pod,$energy_type,$total_consumption_ae,0,$total_consumption_re_3x))
				$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,0,$total_consumption_re_3x,$total_consumption_mu,$curveName,$profileCC,'xlsx');
				else
					$cleanup_dup+=1;
			}
			
			if($total_consumption_ae != 0)
			{
				if (!$this->importConsumptionsModel->import_check_duplicates_xlsx_in_pdf($pod,$energy_type,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x))
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,$curveName,$profileCC,'xlsx');
				else
					$cleanup_dup+=1;
			}
		}
		
		
		$affected = $this->importSQLValues();
						
		$affected_cleanup = $this->importConsumptionsModel->import_cleanup_xlsx();
		
		return ['rows' => $row-1, 'imported' => $affected,'cleanup' => $affected_cleanup,'cleanup_dup'=>$cleanup_dup,'fileInfo' => $file_info];
	}
	
	public function import_DELGAZ_pdf($pdfText)
	{
		//log_message('info',$pdfText);
		$row = $affected = 0;
		$status = 'continue';
		$file_info = 'Fisier import citiri'; 
		$this->initSQLValuesString();
		
		$distributor_name = $supplier_name = $customer_name = $customer_code = $contract_number = $consumption_location_id = $pod = $voltage_level_delimitation = $voltage_level_measurment = $invoice_start_date = $invoice_end_date = $reading_start_date = $reading_end_date = $device_serial_number = $energy_type = $index_old = $index_new = $total_consumption_mu = '';
				
		//================DATE GENERALE: Distribuitor, Furnizor==========================================================================================//
		if (strpos($pdfText,"DELGAZ GRID SA") !== FALSE) $distributor_name='DELGAZ GRID S.A.';
		else return -1;
		
		$supplier_name = $this->importConsumptionsModel->get_supplier_name($this->get_string_between($pdfText,"DELGAZ GRID SA","CUI"));
		if (empty($supplier_name)) return -2;

		//================DATE PV: Nume Client,Cod Client, Nr Contract, Date Factura, Nivel Tensiune, POD ==========================================================================================//		
		$pv_array = explode('Anexa nr.', $pdfText);
		foreach (array_slice($pv_array,1) as $pv) 
		{
			$contract_number = $device_serial_number = $reading_start_date = $reading_end_date = $consumption_location_id = '';
			
			
			$invoice_dates_array = $this->get_array_between($pv,'JT');
			log_message('error',print_r($invoice_dates_array,true)); exit();
			if (empty($invoice_dates_array) || count($invoice_dates_array)!=7) 
			{
				$invoice_dates_array = $this->get_array_between($pv,'MT');
				$voltage_level_measurment='MT';
			}
			else $voltage_level_measurment='JT';
			
			
			if (empty($invoice_dates_array) || count($invoice_dates_array)!=7) continue; //consum zero return -4;	
			
			if (!in_array($voltage_level_measurment, array('JT','MT','IT'))) 
				return -5;
			
			$invoice_start_date = $this->formatDateText($invoice_dates_array[0],'d.m.y','Y-m-d');
			$invoice_end_date = $this->formatDateText($invoice_dates_array[2],'d.m.y','Y-m-d');
			
			$customer_name =  $this->get_string_between($pv,'Denumire consumator:','Cod consumator');
			if (empty($customer_name)) return -6;
			
			$consumption_location_id = $this->get_string_between($pv,'Cod loc consum:',',');
			$customer_code = $consumption_location_id; //nu e acelasi cod din excel ... $this->get_string_between($pv,'Cod consumator:');
			
			$pod = $consumption_location_id;//'EMO'.$this->get_string_between($pv,'EMO',' ');
			if (strlen($pod)<5) return -8;
			
			//================TOTALURI =========================================================================================================================//
			$total_facturat = $this->get_string_between($pv,'Produse si servicii facturate','Total factură');
			$total_facturat_arr = preg_split("/((\r?\n)|(\r\n?))/", $total_facturat);
			
			
			for ($jump_idx = 1; $jump_idx <=2; $jump_idx+=1)
			{
				$pierderiEA = $EA = $ERC = $ERI = $ERC_3x = $ERI_3x = 0;
				$total_consumption_ae = $total_consumption_re = $total_consumption_re_3x = 0;
				$device_serial_number = $index_old=$index_new = ''; $reading_start_date=$reading_end_date='1970-01-01';
				
				if ( $jump_idx == 1) $voltage_level_measurment = 'MT';
				else $voltage_level_measurment = 'JT';
				
				for($i=0;$i<count($total_facturat_arr);$i++)
				{
					//log_message('info',$total_facturat_arr[$i]);
					if($this->startsWith($total_facturat_arr[$i],'Pierderi Ea in trafo'))
					{
						$i+=$jump_idx;	
						$arr = preg_split('/ /', $total_facturat_arr[$i], 0, PREG_SPLIT_NO_EMPTY);
						if(count($arr)>=5)
						{
							$p = $this->getNumber($arr[4]);
							if($arr[5] == 'MWh') $p *=1000;
							
							$pierderiEA += $p;

							$invoice_start_date = $this->formatDateText($arr[1],'d.m.y','Y-m-d');
							$invoice_end_date = $this->formatDateText($arr[3],'d.m.y','Y-m-d');
							
							$i+=3-$jump_idx; 
							if($i>=count($total_facturat_arr)) break;
						}
					}

					if($this->startsWith($total_facturat_arr[$i],'Pierderi Ea in LES'))
					{
						$i+=$jump_idx;	
						$arr = preg_split('/ /', $total_facturat_arr[$i], 0, PREG_SPLIT_NO_EMPTY);
						if(count($arr)>=5)
						{
							$p = $this->getNumber($arr[4]);
							if($arr[5] == 'MWh') $p *=1000;
							
							$pierderiEA += $p;

							$invoice_start_date = $this->formatDateText($arr[1],'d.m.y','Y-m-d');
							$invoice_end_date = $this->formatDateText($arr[3],'d.m.y','Y-m-d');
							
							$i+=3-$jump_idx; 
							if($i>=count($total_facturat_arr)) break;
						}
					}
					
					if($this->startsWith($total_facturat_arr[$i],'Er capacitivă de plată X3'))
					{
						$i+=$jump_idx;	
						$arr = preg_split('/ /', $total_facturat_arr[$i], 0, PREG_SPLIT_NO_EMPTY);
						if(count($arr)>=5)
						{
							$ERC_3x = $this->getNumber($arr[4]);
												
							$invoice_start_date = $this->formatDateText($arr[1],'d.m.y','Y-m-d');
							$invoice_end_date = $this->formatDateText($arr[3],'d.m.y','Y-m-d');
							
							
							$total_consumption_mu = 'kVArh';
							$energy_type = 'ERC';
							$total_consumption_re = 0;
							$total_consumption_re_3x = $ERC_3x;
					
							if($total_consumption_re!=0 || $total_consumption_re_3x!=0)
								$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,0,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');

							
							$i+=3-$jump_idx;
							if($i>=count($total_facturat_arr)) break;
						}
					}
					
					if($this->startsWith($total_facturat_arr[$i],'Er inductivă de plată X3'))
					{				
						$i+=$jump_idx;	
						$arr = preg_split('/ /', $total_facturat_arr[$i], 0, PREG_SPLIT_NO_EMPTY);
						if(count($arr)>=5)
						{
							$ERI_3x = $this->getNumber($arr[4]);
							
							
							$invoice_start_date = $this->formatDateText($arr[1],'d.m.y','Y-m-d');
							$invoice_end_date = $this->formatDateText($arr[3],'d.m.y','Y-m-d');
							
							$total_consumption_mu = 'kVArh';
							$energy_type = 'ERI';
							$total_consumption_re = 0;
							$total_consumption_re_3x = $ERI_3x;
					
							if($total_consumption_re!=0 || $total_consumption_re_3x!=0)
								$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,0,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');

							
							$i+=3-$jump_idx;
							if($i>=count($total_facturat_arr)) break;
						}
					}
					
					if($this->startsWith($total_facturat_arr[$i],'Er capacitivă de plată'))
					{
						$i+=$jump_idx;	
						$arr = preg_split('/ /', $total_facturat_arr[$i], 0, PREG_SPLIT_NO_EMPTY);
						
						if(count($arr)>=5)
						{
							$ERC = $this->getNumber($arr[4]);
							$invoice_start_date = $this->formatDateText($arr[1],'d.m.y','Y-m-d');
							$invoice_end_date = $this->formatDateText($arr[3],'d.m.y','Y-m-d');
							
							$total_consumption_mu = 'kVArh';
							$energy_type = 'ERC';
							$total_consumption_re = $ERC;
							$total_consumption_re_3x = 0;
					
							if($total_consumption_re!=0 || $total_consumption_re_3x!=0)
								$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,0,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');

							
							$i+=3-$jump_idx;
							if($i>=count($total_facturat_arr)) break;
						}
					}
					
					if($this->startsWith($total_facturat_arr[$i],'Er inductivă de plată'))
					{
						$i+=$jump_idx;	
						$arr = preg_split('/ /', $total_facturat_arr[$i], 0, PREG_SPLIT_NO_EMPTY);
						
						if(count($arr)>=5)
						{
							$ERI = $this->getNumber($arr[4]);
							
							
							$invoice_start_date = $this->formatDateText($arr[1],'d.m.y','Y-m-d');
							$invoice_end_date = $this->formatDateText($arr[3],'d.m.y','Y-m-d');
							
							$total_consumption_mu = 'kVArh';
							$energy_type = 'ERI';
							$total_consumption_re = $ERI;
							$total_consumption_re_3x = 0;
					
							if($total_consumption_re!=0 || $total_consumption_re_3x!=0)
								$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,0,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');

							
							$i+=3-$jump_idx;
							if($i>=count($total_facturat_arr)) break;
						}
					}
					
					if($this->startsWith($total_facturat_arr[$i],'Energie activă'))
					{	
						$i+=$jump_idx;	
						$arr = preg_split('/ /', $total_facturat_arr[$i], 0, PREG_SPLIT_NO_EMPTY);
						if(count($arr)>=5)
						{
							$EA = $this->getNumber($arr[4]);
							if($arr[5] == 'MWh') $EA *=1000;
							
							$total_consumption_ae += $EA;
							
							$invoice_start_date_ea = $this->formatDateText($arr[1],'d.m.y','Y-m-d');
							$invoice_end_date_ea = $this->formatDateText($arr[3],'d.m.y','Y-m-d');
							
							$i+=3-$jump_idx;
							if($i>=count($total_facturat_arr)) break;
						}
					}	
				}
				
				$total_consumption_ae += $pierderiEA;				
				if($total_consumption_ae!=0)
				{
					log_message('error',$pod);
					log_message('error',$total_consumption_ae);
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date_ea,$invoice_end_date_ea,$reading_start_date,$reading_end_date,$device_serial_number,'EA',$index_old,$index_new,$total_consumption_ae,0,0,'kWh','pdf');		
				}
			}
			

					

			/*
			$total_consumption_ae = 0;
			$total_consumption_mu = 'kVArh';
			$energy_type = 'ERI';
			$total_consumption_re = $ERI;
			$total_consumption_re_3x = $ERI_3x;
			
			if($total_consumption_re!=0 || $total_consumption_re_3x!=0)
				$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');


			$total_consumption_ae = 0;
			$total_consumption_mu = 'kVArh';
			$energy_type = 'ERC';
			$total_consumption_re = $ERC;
			$total_consumption_re_3x = $ERC_3x;
			
			if($total_consumption_re!=0 || $total_consumption_re_3x!=0)
				$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
			
			$total_consumption_re = $ERI;
			$total_consumption_re_3x = $ERI_3x;
			*/
			
			
			/* -->Incercare de import citiri: totalurile nu sunt impartite per contor si nu se face deosebirea intre RI/RC si X3 
			$citiri_data_array = explode('Denumire şi cod', $pv);
			foreach (array_slice($citiri_data_array,1) as $ca) 
			{
				$ca_lines_arr = preg_split("%\R%", $ca, 0, PREG_SPLIT_NO_EMPTY);
				for ($i=0;$i<len($ca_lines_arr)-1;i++)
				{
					if(strpos($ca_lines_arr[$i+1],'( citire )') > 1 or strpos($ca_lines_arr[$i+1],'( estimare )') > 1)
					{
						$arr = preg_split('/   /', $total_facturat_arr[$i], 0, PREG_SPLIT_NO_EMPTY);
						$reading_start_date = $arr[1];
						$device_serial_number = $arr[3];
						if(strpos($arr[5],'Energie activă')>1) $energy_type = 'EA';
						if(strpos($arr[5],'Energie activă')>1) $energy_type = 'EA';
						if(strpos($arr[5],'Energie activă')>1) $energy_type = 'EA';
						
					}
				}
			}
			*/
			
			/*
			
			//================DATE CONTOR: Serie, Citiri, Date Citiri ==========================================================================================//		
			$contor_data_array = explode('Serie contor:',$pv);
			foreach (array_slice($contor_data_array,1) as $cd) 
			{
				$device_serial_number = $this->get_string_between($cd,'','Locatie dispozitiv:');
				if (empty($device_serial_number)) return -9;
				
				$consumption_location_id = $this->get_string_between($cd,'Locatie dispozitiv:');
				if (empty($consumption_location_id)) return -12;
				
				$reading_dates = $this->get_string_between($cd,'Perioada:','Tensiunea in punctul de masurare:');
				if (empty($reading_dates)) return -11;
				$reading_dates_array = explode(' - ',$reading_dates);
				if (count($reading_dates_array)!=2) return -11;
				$reading_start_date = $this->formatDateText($reading_dates_array[0],'d.m.Y','Y-m-d');
				$reading_end_date = $this->formatDateText($reading_dates_array[1],'d.m.Y','Y-m-d');
				
				$index_old = $index_new = $total_consumption_ae = $total_consumption_re = $total_consumption_re_3x = 0;
				
				$data_array = $this->get_array_between($cd,'Cant. Energie Activa');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'EA';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'KWh'; //UM apare rar sau la facturat
					
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					
					$index_old=$index_new=$total_consumption_mu='';
				}

				$data_array = $this->get_array_between($cd,'Cant. En. Reactiva Ind.');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'ERI';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'kVArh'; //UM apare rar sau la facturat
					
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					
					$index_old=$index_new=$total_consumption_mu='';
				}

				$data_array = $this->get_array_between($cd,'Cant. En. Reactiva Cap.');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'ERC';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'kVArh'; //UM apare rar sau la facturat
		
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
						
					$index_old=$index_new=$total_consumption_mu='';
				}
			}
			*/
		}
		
		$row = $this->sqlValuesLines;
		$affected = $this->importSQLValues();
					;				
		return ['rows' => $row, 'imported' => $affected,'fileInfo' => $file_info];
	}
	
	public function import_OLTENIA_pdf($pdfText)
	{

		$row = $affected = 0;
		$status = 'continue';
		$file_info = 'Fisier import citiri'; 
		$this->initSQLValuesString();
		
		$distributor_name = $supplier_name = $customer_name = $customer_code = $contract_number = $consumption_location_id = $pod = $voltage_level_delimitation = $voltage_level_measurment = $invoice_start_date = $invoice_end_date = $reading_start_date = $reading_end_date = $device_serial_number = $energy_type = $index_old = $total_consumption_mu = '';
		$total_consumption_ae = $total_consumption_re = $total_consumption_re_3x = 0;
		
		//================DATE GENERALE: Distribuitor, Furnizor==========================================================================================//
		if (strpos($pdfText,"DISTRIBUTIE ENERGIE OLTENIA S.A.") !== FALSE) $distributor_name='DISTRIBUTIE ENERGIE OLTENIA S.A.';
		else return -1;
		
		$supplier_name = $this->importConsumptionsModel->get_supplier_name($this->get_string_between($pdfText,'Destinatar: '));
		if (empty($supplier_name)) return -2;

		//================DATE PV: Nume Client,Cod Client, Nr Contract, Date Factura, Nivel Tensiune, POD ==========================================================================================//		
		$pv_array = explode('Cod loc de consum', $pdfText);
		foreach (array_slice($pv_array,1) as $pv) 
		{

			$consumption_location_id = ltrim($this->get_string_between($pv,'(LC) :','Denumire'), '0');
			$customer_code = $consumption_location_id; //nu e acelasi cod din excel ... $this->get_string_between($pv,'Cod consumator:');
		
			$customer_name =  $this->get_string_between($pv,'Denumire LC :');
			if (empty($customer_name)) return -6;
		
			$pod_data_array = explode('Cod punct de masura', $pv);
			
			foreach (array_slice($pod_data_array,1) as $pdata)
			{			
				$index_old=$index_new = '';$reading_start_date=$reading_end_date='1970-01-01';
			
				$pod = $this->get_string_between($pdata,'Cod unic:','CONTOR');
				if (strlen($pod)<5) return -8;
								
				$voltage_level_delimitation=$this->get_string_between($pdata,'Nivel de tensiune PM:');
				$voltage_level_measurment=$this->get_string_between($pdata,'Nivel de tensiune punct de delimitare (PD):','Tip tarif:');
			
				$invoice_dates_array = $this->get_array_between($pdata,'PERIOADA FACTURARE');
				
				if (empty($invoice_dates_array) || count($invoice_dates_array)!=4) continue; //nefacturat return -4;
				
				$invoice_start_date = $this->formatDateText($invoice_dates_array[1],'d.m.Y','Y-m-d');
				$invoice_end_date = $this->formatDateText($invoice_dates_array[3],'d.m.Y','Y-m-d');
				
				$device_serial_number = ltrim($this->get_string_between($pdata,'Serie contor: ','Tip'),'0');
				if (empty($device_serial_number)) return -9; 
				
				//================TOTALURI =========================================================================================================================//
				$totaluri = $this->get_string_between($pdata,'a produselor facturate','Total');
				if(!empty($totaluri))
				{
					//log_message('info',$pod);
					$total_consumption_ae = $total_consumption_re = $total_consumption_re_3x = 0;
					
					
					$e_arr = $this->get_string_between($totaluri,'Energie electrica activa',"\n",10);
					$idx = 0;
					
					if(empty($e_arr))
					{
						$e_arr = $this->get_string_between($totaluri,'Energie el. activa',"\n",10);
						$idx = 1;
					}
					
					if(!empty($e_arr))
					{
						foreach($e_arr as $e)
						{
							$arr = preg_split('/ /', $e, 0, PREG_SPLIT_NO_EMPTY);
							if(!empty($arr))
							{
								$EA = $this->getNumber($arr[$idx + 1]);
								if ($arr[$idx]=='MWh') $EA = $EA*1000;
								
								$total_consumption_ae+=$EA;
							}
						}
						
						$total_consumption_mu = 'kWh';
						$energy_type='EA';
						if($total_consumption_ae!=0)
							$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');		
					}
					
					$total_consumption_ae = $total_consumption_re = $total_consumption_re_3x = 0;
					$e_arr = $this->get_string_between($totaluri,'Energie reactiva capacitiva',"\n",10);
					if(!empty($e_arr))
					{
						foreach($e_arr as $e)
						{
							$arr = preg_split('/ /', $this->killSingleSpaces($e), 0, PREG_SPLIT_NO_EMPTY);
							if(!empty($arr))
							{
								if ($arr[0] == 1)
									$total_consumption_re+=$this->getNumber($arr[2]);
								if ($arr[0] == 3)
									$total_consumption_re_3x+=$this->getNumber($arr[2]);
							}
							
						}
						
						$total_consumption_mu = 'kVArh';
						$energy_type='ERC';
						if($total_consumption_re!=0 || $total_consumption_re_3x!=0)
							$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');		
					}
					
					$total_consumption_ae = $total_consumption_re = $total_consumption_re_3x = 0;
					$e_arr = $this->get_string_between($totaluri,'Energie reactiva inductiva',"\n",10);
					if(!empty($e_arr))
					{
						foreach($e_arr as $e)
						{
							$arr = preg_split('/ /', $this->killSingleSpaces($e), 0, PREG_SPLIT_NO_EMPTY);
							if(!empty($arr))
							{
								if ($arr[0] == 1)
									$total_consumption_re+=$this->getNumber($arr[2]);
								if ($arr[0] == 3)
									$total_consumption_re_3x+=$this->getNumber($arr[2]);
							}
						}
						
						$total_consumption_mu = 'kVArh';
						$energy_type='ERI';
						if($total_consumption_re!=0 || $total_consumption_re_3x!=0)
							$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');		
					}
				}
			
			}						
			/*
			
			//================DATE CONTOR: Serie, Citiri, Date Citiri ==========================================================================================//		
			$contor_data_array = explode('Serie contor:',$pv);
			foreach (array_slice($contor_data_array,1) as $cd) 
			{
				$device_serial_number = $this->get_string_between($cd,'','Locatie dispozitiv:');
				if (empty($device_serial_number)) return -9;
				
				$consumption_location_id = $this->get_string_between($cd,'Locatie dispozitiv:');
				if (empty($consumption_location_id)) return -12;
				
				$reading_dates = $this->get_string_between($cd,'Perioada:','Tensiunea in punctul de masurare:');
				if (empty($reading_dates)) return -11;
				$reading_dates_array = explode(' - ',$reading_dates);
				if (count($reading_dates_array)!=2) return -11;
				$reading_start_date = $this->formatDateText($reading_dates_array[0],'d.m.Y','Y-m-d');
				$reading_end_date = $this->formatDateText($reading_dates_array[1],'d.m.Y','Y-m-d');
				
				$index_old = $index_new = $total_consumption_ae = $total_consumption_re = $total_consumption_re_3x = 0;
				
				$data_array = $this->get_array_between($cd,'Cant. Energie Activa');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'EA';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'KWh'; //UM apare rar sau la facturat
					
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					
					$index_old=$index_new=$total_consumption_mu='';
				}

				$data_array = $this->get_array_between($cd,'Cant. En. Reactiva Ind.');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'ERI';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'kVArh'; //UM apare rar sau la facturat
					
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
					
					$index_old=$index_new=$total_consumption_mu='';
				}

				$data_array = $this->get_array_between($cd,'Cant. En. Reactiva Cap.');
				if(!empty($data_array) && count($data_array)>3)
				{
					$energy_type = 'ERC';
					
					$index_old = $this->getNumber($data_array[1]);
					$index_new = $this->getNumber($data_array[2]);
					$total_consumption_mu = 'kVArh'; //UM apare rar sau la facturat
		
					$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,'','','pdf');
						
					$index_old=$index_new=$total_consumption_mu='';
				}
			}
			*/
		}
		
		$row = $this->sqlValuesLines;
		$affected = $this->importSQLValues();
					;				
		return ['rows' => $row, 'imported' => $affected,'fileInfo' => $file_info];
	}
	
	public function import_OLTENIA($fileName)
	{	
	
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileName);
        
        $sheet = $spreadsheet->getSheet(0);//getActiveSheet();
		$file_info = 'Fisier import citiri';
		
		$row = 1;$cleanup_dup = 0;
		$this->initSQLValuesString();
		
		while (true) {
			
			//only totals
			if($sheet->getCellByColumnAndRow(1, ++$row)->getValue() == '*') continue;
			
			$fixCol = 0;	
			
			$index_old=$index_new = '';$reading_start_date=$reading_end_date='1970-01-01';
			
			$distributor_name = trim($sheet->getCellByColumnAndRow(2, $row)->getValue() ?? '');
			if (empty($distributor_name)) break;
			
			if($sheet->getCellByColumnAndRow(3, 1)->getValue() == '') $fixCol = 1;
			
			$supplier_name = $this->importConsumptionsModel->get_supplier_name($sheet->getCellByColumnAndRow(3+$fixCol, $row)->getValue());

			if($sheet->getCellByColumnAndRow(5, 1)->getValue() == '') $fixCol ++;
			if($sheet->getCellByColumnAndRow(6, 1)->getValue() == '') $fixCol ++;
			
			$customer_name = $sheet->getCellByColumnAndRow(4+$fixCol, $row)->getValue();
			
			if($sheet->getCellByColumnAndRow(7, 1)->getValue() == '') $fixCol ++;

			$customer_code = $sheet->getCellByColumnAndRow(11+$fixCol, $row)->getValue();
			$contract_number = $sheet->getCellByColumnAndRow(6+$fixCol, $row)->getValue();
			//$contract_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(6, $row)->getValue()??0)->format('Y-m-d');
			$pod = substr($sheet->getCellByColumnAndRow(10+$fixCol, $row)->getValue(),1); //ignora ;
			$consumption_location_id = $sheet->getCellByColumnAndRow(11+$fixCol, $row)->getValue();
						
			$voltage_level_delimitation = $sheet->getCellByColumnAndRow(13+$fixCol, $row)->getValue();
			$voltage_level_measurment = $sheet->getCellByColumnAndRow(12+$fixCol, $row)->getValue();
			
			//$invoice_start_date = $this->formatDateText($sheet->getCellByColumnAndRow(14, $row)->getValue(),'m/d/Y','Y-m-d');
			//$invoice_end_date = $this->formatDateText($sheet->getCellByColumnAndRow(15, $row)->getValue(),'m/d/Y','Y-m-d');
				
			$invoice_start_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(14+$fixCol, $row)->getValue()??0)->format('Y-m-d');
			$invoice_end_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(15+$fixCol, $row)->getValue()??0)->format('Y-m-d');
			
			
			//$reading_start_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(15, $row)->getValue()??0)->format('Y-m-d');
			//$reading_end_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($sheet->getCellByColumnAndRow(16, $row)->getValue()??0)->format('Y-m-d');
			$device_serial_number = $sheet->getCellByColumnAndRow(18+$fixCol, $row)->getValue();
			$energy_type = $sheet->getCellByColumnAndRow(19+$fixCol, $row)->getValue(); 
			//$index_old = $sheet->getCellByColumnAndRow(20, $row)->getValue();
			//$index_new = $sheet->getCellByColumnAndRow(21, $row)->getValue();
			$total_consumption_ae = $sheet->getCellByColumnAndRow(32+$fixCol, $row)->getValue();
			$total_consumption_re = $sheet->getCellByColumnAndRow(33+$fixCol, $row)->getValue();
			$total_consumption_re_3x = $sheet->getCellByColumnAndRow(34+$fixCol, $row)->getValue();
			
			$total_consumption_mu=$sheet->getCellByColumnAndRow(36+$fixCol, $row)->getValue();
		
			$profileCC = $this->cleanString($sheet->getCellByColumnAndRow(37+$fixCol, $row)->getValue());
			
			if(empty($profileCC))
				$curveName = $this->cleanString($sheet->getCellByColumnAndRow(38+$fixCol, $row)->getValue());
			else
				$curveName = $profileCC;
			
			if(!empty($profileCC) ||  !empty($curveName))
				$curveID = $this->importConsumptionsModel->saveCurveVariance($this->consumption_date, $distributor_name, $pod, $profileCC, $curveName);
				
			if (!$this->importConsumptionsModel->import_check_duplicates_xlsx_in_pdf($pod,$energy_type,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x))
				$this->addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,$curveName,$profileCC,'xlsx');
			else
				$cleanup_dup+=1;
		}
		
		
		$affected = $this->importSQLValues();
						
		$affected_cleanup = $this->importConsumptionsModel->import_cleanup_xlsx();
		
		return ['rows' => $row-1, 'imported' => $affected,'cleanup' => $affected_cleanup,'cleanup_dup'=>$cleanup_dup,'fileInfo' => $file_info];
	}
	
	private function get_readings_dates_re($str)
	{
		$status ='';
		
		foreach(preg_split("/((\r?\n)|(\r\n?))/", $str) as $line)
		{
			if ($this->startsWith($line,'Total loc de consum'))
			{
				$status = 'getReadingsDates';
				continue;
			}
			
			if ($status == 'getReadingsDates')
			{
				$lineData = preg_split('/ /', $line, 0, PREG_SPLIT_NO_EMPTY);
				if(!empty($lineData))
				{													
					$ret[0] = $this->formatDateText($lineData[0],'d.m.Y','Y-m-d');
					$ret[1] = $this->formatDateText($lineData[2],'d.m.Y','Y-m-d');

					return $ret;
				}
			}
		}
		
		return false;
	}
	
	private function get_readings_dates_re_3x($str)
	{
		$lineData = preg_split('/ /', $str, 0, PREG_SPLIT_NO_EMPTY);
		if(!empty($lineData))
		{													
			$ret[0] = $this->formatDateText($lineData[0],'d.m.Y','Y-m-d');
			
			if (strlen($lineData[1]) == 1)
				$ret[1] = $this->formatDateText($lineData[2],'d.m.Y','Y-m-d');
			else
				$ret[1] = $this->formatDateText(substr($lineData[1],1),'d.m.Y','Y-m-d');
			
			return $ret;
		}
	
		return false;
	}
	
	private function get_capacitive_line($str)
	{
		$start = strpos($str,'CAPACITIVA');
		if ($start === false) return false;
		
		$stopInductive = strpos($str,'INDUCTIVA');
		
		if ($stopInductive !== false) return substr($str,$start,$stopInductive-$start);
		
		return $str;
	}
	
	private function get_inductive_line($str)
	{
		$start = strpos($str,'INDUCTIVA');
		if ($start === false) return false;
			
		return substr($str,$start);

	}
	
	private function get_string_between($str, $starting_word, $ending_word="\n",$findings = 1)
	{
		$index = 0;
		$ret = array();
		
		do {
		
			if (strlen($starting_word)==0) $substring_start =0;
			else
			{
				$substring_start = strpos($str, $starting_word);
				//Adding the strating index of the strating word to 
				//its length would give its ending index
				if ($substring_start === false) break;
				$substring_start += strlen($starting_word);
			}		
			//Length of our required sub string
			$substring_stop = strpos($str, $ending_word, $substring_start);
			if ($substring_stop === false) $substring_stop = strlen($str)-1;
			
			$size = $substring_stop  - $substring_start;
			
			// Return the substring from the index substring_start of length size 
			if ($findings == 1) return trim(substr($str, $substring_start, $size));
			else $ret[$index] = trim(substr($str, $substring_start, $size));
			
			$index++;
			$str = substr($str,$substring_stop+strlen($ending_word));
		} while ($index < $findings);
		
		if(empty($ret)) return false;

		return $ret;
	}

	private function get_array_between($str, $starting_word, $ending_word="\n",$findings = 1)
	{
		$ret=array();
		$idx = 0;
		
		$line = $this->get_string_between($str,$starting_word,$ending_word,$findings);
		
		if (!empty($line))
		{		
			if($findings >1) //array of splitted strings
			{
				if (gettype($line)=='string')
					$ret[$idx] = (preg_split('/ /', $line, 0, PREG_SPLIT_NO_EMPTY)); 
				elseif (gettype($line)=='array')
					foreach($line as $l)
						$ret[$idx++] = (preg_split('/ /', $l, 0, PREG_SPLIT_NO_EMPTY));
			}
			else return (preg_split('/ /', $line, 0, PREG_SPLIT_NO_EMPTY)); //just a splitted string
		}
		return $ret;
	}
	
	private function startsWith ($string, $startString)
	{
		$len = strlen($startString);
		return (substr($string, 0, $len) === $startString);
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
		return str_replace( [ '\'', '"', "\b" , "\n", "\r", "\t", "\Z",'\\','%','_','\0'], '', $str ?? '');
	}
	
	private function numberToString($value)
	{
		$value = $this->cleanString($value);
		if( strpos($value,'E+') > 0) return sprintf("%d", $value);
		
		return $value;
	}
	
	private function countArrayDimensions($array)
	{
		if (is_array(reset($array)))
			$return = $this->countArrayDimensions(reset($array)) + 1;
		else
			$return = 1;

		return $return;
	}
	
	private function initSQLValuesString()
	{
		$this->sqlValues='';
		$this->sqlValuesLines=0;
	}
	
	private function addSQLValuesString($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,$curveName,$profileCC,$source,$extra_key='')
	{
		
		$distributor_name = $this->cleanString($distributor_name);
		$supplier_name = $this->cleanString($supplier_name);
		$customer_code = $this->cleanString($customer_code);
		$contract_number = $this->cleanString($contract_number);
		$consumption_location_id = $this->cleanString($consumption_location_id);
		$pod = $this->cleanString($pod);
		$customer_name = $this->importConsumptionsModel->getCustomerName($pod,$this->cleanString($customer_name));
		$voltage_level_delimitation = $this->cleanString($voltage_level_delimitation);
		$voltage_level_measurment = $this->cleanString($voltage_level_measurment);
		$invoice_start_date = $this->cleanString($invoice_start_date);
		$invoice_end_date = $this->cleanString($invoice_end_date);
		$reading_start_date = $this->cleanString($reading_start_date);
		$reading_end_date = $this->cleanString($reading_end_date);
		$device_serial_number = $this->cleanString($device_serial_number);
		$index_old = $this->cleanString($index_old);
		$index_new = $this->cleanString($index_new);
		$total_consumption_ae = $this->cleanString($total_consumption_ae);
		$total_consumption_re = $this->cleanString($total_consumption_re);
		$total_consumption_re_3x = $this->cleanString($total_consumption_re_3x);
		$total_consumption_mu = $this->cleanString($total_consumption_mu);
		$extra_key = $this->cleanString($extra_key);
		
		
		if($source=='xlsx')
		{
			$consumptionKey = $distributor_name.$supplier_name.$pod.$voltage_level_measurment.$invoice_end_date.$reading_start_date.$reading_end_date.$device_serial_number.$energy_type.$index_old.$index_new.$total_consumption_ae.$total_consumption_re.$total_consumption_re_3x.$total_consumption_mu.$consumption_location_id;
			
			while(in_array($consumptionKey, $this->consumptionKeys))
			{
				$index_old++;
				$consumptionKey = $distributor_name.$supplier_name.$pod.$voltage_level_measurment.$invoice_end_date.$reading_start_date.$reading_end_date.$device_serial_number.$energy_type.$index_old.$index_new.$total_consumption_ae.$total_consumption_re.$total_consumption_re_3x.$total_consumption_mu.$consumption_location_id;
			}				
			
			array_push($this->consumptionKeys, $consumptionKey);
		}
			
		$source = $this->cleanString($source);
		
		$error = $this->importConsumptionsModel->checkReading($distributor_name,$supplier_name,$customer_name,$customer_code,$contract_number,$consumption_location_id,$pod,$voltage_level_delimitation,$voltage_level_measurment,$invoice_start_date,$invoice_end_date,$reading_start_date,$reading_end_date,$device_serial_number,$energy_type,$index_old,$index_new,$total_consumption_ae,$total_consumption_re,$total_consumption_re_3x,$total_consumption_mu,$this->consumption_date, $source);
		//log_message("error","$total_consumption_ae ".print_r($error,true)."fix pix");
		
		//if($pod == 'RO001E141189223') log_message('error',"('".$distributor_name."','".$supplier_name."','".$customer_name."','".$customer_code."','".$contract_number."','".$consumption_location_id."','".$pod."','".$voltage_level_delimitation."','".$voltage_level_measurment."','".$invoice_start_date."','".$invoice_end_date."','".$reading_start_date."','".$reading_end_date."','".$device_serial_number."','".$energy_type."','".$index_old."','".$index_new."','".$total_consumption_ae."','".$total_consumption_re."','".$total_consumption_re_3x."','".$total_consumption_mu."','".$this->consumption_date."','".$source."','".$error."')");
		//if($error == 'POD nou' || strpos($error , 'Consum') !== FALSE)
		{
			if ($this->sqlValuesLines > 0) $this->sqlValues .= ",";		
			$this->sqlValues .= "('".$distributor_name."','".$supplier_name."','".$customer_name."','".$customer_code."','".$contract_number."','".$consumption_location_id."','".$pod."','".$voltage_level_delimitation."','".$voltage_level_measurment."','".$invoice_start_date."','".$invoice_end_date."','".$reading_start_date."','".$reading_end_date."','".$device_serial_number."','".$energy_type."','".$index_old."','".$index_new."','".$total_consumption_ae."','".$total_consumption_re."','".$total_consumption_re_3x."','".$total_consumption_mu."','".$curveName."','".$profileCC."','".$this->consumption_date."','".$source."','".$extra_key."','".$error."')";
			$this->sqlValuesLines+=1;
		}
	}
		
	private function importSQLValues()
	{
		if ($this->sqlValuesLines > 0)
			return $this->importConsumptionsModel->import_consumptions($this->sqlValues);
		
		return 0;
	}
	
	private function killSingleSpaces($str)
	{
		$ret='';
		$j=0;
		for($i = 0;$i<strlen($str)-1;$i++)
		{
			if($str[$i]!=' ')
			{
				$ret .= $str[$i];
				$numSpaces = 0;
			}
			else 
			{
				if($numSpaces == 0 && $str[$i+1] == ' ') 
				{
					$ret.= $str[$i];
					$numSpaces++;
				}
			}
		}
		
		return $ret;
	}
}
