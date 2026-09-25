<?php 
namespace App\Controllers\Realizat;
use App\Controllers\BaseController;

use DateTime;
use DateTimeZone;
use DateInterval;

require_once(__DIR__ .'/../tools.php');
require_once(__DIR__ .'/../GridTools.php');

class ImportDailyReadings extends BaseController
{
	use \GridTools;
	private $data = [
        'title'   => 'Import Realizat Zilnic',
		'msg' => '',
		'output' => '',
		'upload' =>'',
		'div-card' => 'card-realizat',
		'menu' => 'consumuri'];
	
	private $sqlValues = '';
	private $sqlValuesLines = 0;
	private $qIDs = [];
	
	protected $importDailyReadingslModel;
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
        $this->importDailyReadingslModel = new \App\Models\Realizat\ImportDailyReadingsModel();
    }
	
	public function index()
	{
		//grid
		$this->data['output'] = '<div id="grid_wrapper"/>';	
	    $this->data['jsFiles'] = ['tools.js','inaGrid.js','inaDS.js','dialogs/pod.js','realizat/importDailyReadings.js'];
		
	    $data['data'] = $this->data;
		return view('realizat/import_readings',$data);
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
					$destFile = $saveDir.$files['name'][$index];
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

	private function importFile($filePath)
	{
		$msg = '';
		$pdfText = '';
		$fileInfo = pathinfo($filePath);
		//$sConsumtions = $this->importConsumptionsModel->countConsumptions();
		
		if(in_array(strtolower($fileInfo['extension']), array('csv','xlsx')))
		{		
			$rows_imported = 0;
			$err_msg='';
			
			if(strtolower($fileInfo['extension']) == 'xlsx')
			{
				$this->spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
				$spreadsheet = $this->spreadsheet;
			}
			
			switch($this->getFiletype($filePath))
			{
				case 'ENEL':
					$result = $this->import_ENEL($filePath);		
				break;
				case 'DEER_TS':
				case 'DEER_MN':
				case 'DEER_TN':
					$result = $this->import_DEER_TN_TS_MN($filePath);
				break;
				case 'DELGAZ':
					$result = $this->import_DELGAZ($filePath);
				break;
				case 'OLTENIA':
					$result = $this->import_OLTENIA($filePath);
				break;
				default:
					$result = -110;
			}			
		
			$sqlQueue = $this->importDailyReadingslModel->getSqlQueue();
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

		}
		else
		{
			$msg = 'Eroare: Va rugam sa selectati un fisier acceptat!';
		}
		
		return $msg;
	}

	public function getFiletype($filePath)
	{
		$fileName = basename($filePath);
		$fileName = strtolower($fileName);
		$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
		
		if($extension=='xlsx' && str_contains($fileName,"rapoartedatemasuraee")) return 'DELGAZ';
		elseif($extension=='xlsx' && str_contains($fileName,'curba de sarcina')) return 'DEER_MN';
		elseif($extension=='csv' && str_contains($fileName,'ro00')) return 'ENEL';
		elseif($extension=='csv' && (str_contains($fileName,'_pros_') || str_contains($fileName,'_amr_'))) return 'OLTENIA';
		
		log_message('error','Unknown file type:'.$fileName);
		return false;
	}
	
	public function import_ENEL($filePath)
	{	
		
		$fileName = basename($filePath);
		$pod = explode('_', $fileName)[0];
		
		$distributorId = $this->importDailyReadingslModel->getDistributorIdByPODPrefix($pod);
		$this->initSQLValuesString();
	
		log_message('error', "Import daily readings $filePath for $pod, distributor_id:$distributorId");
		
		$rowIndex= 0; $affected = 0;
		// Open the file for reading
		if (($handle = fopen($filePath, 'r')) !== FALSE) {
		
			// Loop through each line of the CSV file
			while (($row = fgetcsv($handle, 10000, ';')) !== FALSE) {  // Use ';' as the delimiter
				
				if($rowIndex++ == 0) continue;
				
				$colIndex = 0;$readingDate='';
				
				if(count($row) > 96) 
					$freq = 15;
				else
					$freq = 60;
				
				foreach($row as $col)
				{
					if($colIndex++ == 0)
					{
						$readingDate = $col;
						continue;
					}
					
					if($this->isNumber($col))
					{
						$col = $this->getNumber($col);
						if($freq == 60)
						{
							for($idx=0;$idx<60;$idx+=15)
							{
								$hour = $colIndex - 2;
								$min = $idx;
								
								/*Changed after 1st Oct 2025 ?!*/
								//$this->addSQLValuesString("$readingDate $hour:$min:00", $col/1000); //multiply the same value 4 times 
								$this->addSQLValuesString("$readingDate $hour:$min:00", $col/4/1000); //multiply the same value 4 times 
							}
						}
						else
						{
							$min = sprintf('%02d', (($colIndex - 2) % 4 ) * 15);
							$hour = sprintf('%02d', intdiv(($colIndex - 2) , 4));
							
							$this->addSQLValuesString("$readingDate $hour:$min:00", $col/1000); 
						}
					}
				}
			}
			
			$this->importSQLValues($pod,$distributorId);
			$affected = $this->sqlValuesLines;
		
			// Close the file
			fclose($handle);		
		}
	
		return ['rows' => $rowIndex, 'imported' => $affected,'fileInfo' => 'Fisier import realizat zilnic'];	
	}
	
	public function import_DEER_TN_TS_MN($filePath)
	{
		log_message('error', "Import daily readings $filePath for DEER_TN_TS_MN");

		$sheetCount = $this->spreadsheet->getSheetCount();
		$sheetIdx= 0; 
		$podDevLocArr = [];
		while($sheetIdx < $sheetCount)
		{
			$sheet = $this->spreadsheet->getSheet($sheetIdx);
			$podRaw = $sheet->getCellByColumnAndRow(2, 9)->getValue(); //cod
			$podRaw = explode('_',$podRaw);
			if(count($podRaw) == 1)
			{
				if(!isset($podDevLocArr[$podRaw[0]]))
				{
					$pod = $this->importDailyReadingslModel->getPodByDevLoc($podRaw[0]);
					if(!empty($pod))
						$podDevLocArr[$podRaw[0]] = $pod;
				}
				else
					$pod = $podDevLocArr[$podRaw[0]];
			}
			else
				$pod = trim($podRaw[0]);
			
			if(empty($pod))
			{
				log_message('error', "Import daily readings $filePath, pod not found!");
				$sheetIdx++;
				continue;
			}

			$distributorId = $this->importDailyReadingslModel->getDistributorIdByPODPrefix($pod);
			if(empty($distributorId))
			{
				log_message('error', "Import daily readings $filePath for $pod, distributor_id not found!");
				$sheetIdx++;
				continue;
			}
			
			$this->initSQLValuesString();
			$rowIndex = 12; $affected = 0; $readingDate= '';
			while(true){
			
				$readingDateRaw = $sheet->getCellByColumnAndRow(1,$rowIndex)->getValue();
					
				if(empty($readingDateRaw)) break;
				
				$readingDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelTodateTimeObject($readingDateRaw)->format('Y-m-d H:i:s');
				//log_message('info', "Import daily readings $filePath for $pod, reading date:$readingDate");
				$ea = $sheet->getCellByColumnAndRow(3,$rowIndex)->getValue();
				
				if($this->isNumber($ea))
					$this->addSQLValuesString($readingDate, $ea); 
				
				$rowIndex++;
			}
			
			$this->importSQLValues($pod,$distributorId, $county=null, $city=null, $address=null);
			$affected = $this->sqlValuesLines;
			$sheetIdx++;
		}

		return ['rows' => $rowIndex, 'imported' => $affected,'fileInfo' => 'Fisier import realizat zilnic'];		
	}

	public function import_DELGAZ($filePath)
	{	
		$sheet = $this->spreadsheet->getSheet(0);
		
		$pod = $sheet->getCellByColumnAndRow(2, 2)->getValue(); //cod
		$cod = $sheet->getCellByColumnAndRow(2, 3)->getValue(); //pod
		$county = $sheet->getCellByColumnAndRow(2, 4)->getValue();
		$city = $sheet->getCellByColumnAndRow(2, 5)->getValue();
		$address = $sheet->getCellByColumnAndRow(2, 6)->getValue().' '.$sheet->getCellByColumnAndRow(2, 7)->getValue();
	
		$distributorId = $this->importDailyReadingslModel->getDistributorIdByPODPrefix($cod);
		$this->initSQLValuesString();
	
		log_message('info', "Import daily readings $filePath for $pod, distributor_id:$distributorId");
		
		$rowIndex= 0; $affected = 0; $readingDate= '';
		// Open the file for reading
		while(true){
			
			$readingDate = $sheet->getCellByColumnAndRow(1,$rowIndex+21)->getValue();		
			if(empty($readingDate)) break;
			
			$readingDate = $this->formatDateText($readingDate,"d.m.Y H:i","Y-m-d H:i:s","-15 minutes");
		
			$ea = $sheet->getCellByColumnAndRow(2,$rowIndex+21)->getValue();
			
			if($this->isNumber($ea))
				$this->addSQLValuesString($readingDate, $ea/1000); 
			
			$rowIndex++;
		}

		$this->importSQLValues($pod,$distributorId, $county, $city, $address);
		$affected = $this->sqlValuesLines;
		

		return ['rows' => $rowIndex, 'imported' => $affected,'fileInfo' => 'Fisier import realizat zilnic'];		
	}

	public function import_OLTENIA($filePath)
	{
		$fileName = basename($filePath);
		
		$this->initSQLValuesString();
		
		$distributorId = 7; //Oltenia
		log_message('error', "Import daily readings $filePath, distributor_id:$distributorId");
		
		$rowIndex= 0; $affected = 0;
		// Open the file for reading
		if (($handle = fopen($filePath, 'r')) !== FALSE) {
		
			// Loop through each line of the CSV file
			while (($row = fgetcsv($handle, 10000, ';')) !== FALSE) {  // Use ';' as the delimiter
				
				if($rowIndex++ == 0) continue;
				
				$colIndex = 0;$readingDate='';
				
				if($row[3]!='EA') continue;

				$pod = $row[0];
				$rawDate = explode('.',$row[6]);
				$readingDate = $rawDate[2].'-'.$rawDate[1].'-'.$rawDate[0];
				
				for($index = 10; $index < count($row); $index++)
				{
					if($row[$index] != '')
					{
						$colIndex = $index - 10 + 1;
						$min = sprintf('%02d', (($colIndex - 1) % 4 ) * 15);
						$hour = sprintf('%02d', intdiv(($colIndex - 1) , 4));
						
						if($this->isNumber($row[$index]))
							$this->addSQLValuesString("$readingDate $hour:$min:00", $this->getNumber($row[$index])/1000); 
					}
				}

				$this->importSQLValues($pod,$distributorId);
				$affected = $this->sqlValuesLines;
			}
		
			// Close the file
			fclose($handle);		
		}
	
		return ['rows' => $rowIndex, 'imported' => $affected,'fileInfo' => 'Fisier import realizat zilnic'];	
	}
	
	private function formatDateText($dateText, $sourceFormat, $destFormat, $modify=null)
	{
		
		$date = \DateTime::createFromFormat($sourceFormat, $dateText ?? 0);
		if ($date === false) return false;
		
		if($modify)
			 $date->modify($modify);
		
		return $date->format($destFormat);
	}
	
	private function isNumber($str)
	{
		$str = str_replace(',','.',$str);
		return is_numeric($str);
	}
	
	private function getNumber($str)
	{
		if(str_contains($str,','))
		{
			$str = str_replace('.','',$str);
			$str = str_replace(',','.',$str);
		}
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
	
	private function addSQLValuesString($reading_datetime, $reading_ea)
	{
		if ($this->sqlValuesLines > 0) $this->sqlValues .= ",";		
		
		$this->sqlValues .= "('".$reading_datetime."',".$reading_ea.")";
		$this->sqlValuesLines+=1;
	}
		
	private function importSQLValues($pod, $distributorId, $county = null, $city = null, $address = null)
	{
		if ($this->sqlValuesLines > 0)
		{
			//log_message('error',$this->sqlValues);
			//return $this->importDailyReadingslModel->import_actual_data($this->sqlValues);
			$this->importDailyReadingslModel->import_daily_readings_pod($pod, $distributorId, $county, $city, $address);
			$this->qIDs[] = $this->importDailyReadingslModel->async_import_daily_readings_data($pod, $this->sqlValues);
		}
		return 0;
	}
	
}
