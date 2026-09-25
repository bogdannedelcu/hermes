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
					$origName = $files['name'][$index];
					$destFile = $saveDir.$origName;
					move_uploaded_file($filePath, $destFile);

					$fileType = $this->getFiletype($destFile);   // false daca nu e recunoscut
					$status = 'OK'; $exInfo = '';
					try {
						$result['msg'] = $this->importFile($destFile);
						$result['type'] = 'info';
						if ($fileType === false || stripos((string)$result['msg'],'eroare') !== false)
							$status = 'ERR';
					}
					catch (\Exception $e) {
						log_message('error',$e->getMessage());
						log_message('error',$e->getTraceAsString());
						$result['msg'] = ' Eroare, este '.$origName.' fisierul corect?';
						$result['type'] = 'error';
						$status = 'ERR';
						$exInfo = $e->getMessage();
					}

					// Arhiveaza fisierul incarcat (inclusiv cele care nu se parseaza) + log pentru debug
					$this->archiveImport($destFile, $origName, $fileType, $status, (string)$result['msg'], $exInfo);

					echo json_encode($result);
					unlink($destFile);
				}
			}
			exit();
			
		}
		else
			return redirect()->to( base_url('/Realizat/ImportReadings'))->with('msg', 'Ai incercat sa incarci un fisier?');
	}

	/*
	 * Arhiveaza fisierul incarcat intr-un folder pe zi si scrie o linie de log (text) cu rezultatul.
	 * Se pastreaza TOATE fisierele (inclusiv cele care nu se parseaza), nimic nu se sterge din arhiva.
	 * Log minimal: data, user, fisier, tip detectat, status OK/ERR, cale arhiva, mesaj + eventuala exceptie.
	 */
	private function archiveImport($srcFile, $origName, $fileType, $status, $msg, $exInfo = '')
	{
		try {
			$day = date('Y-m-d');
			$baseDir = WRITEPATH.'import_archive/daily_readings/';
			$archiveDir = $baseDir.$day.'/';
			if (!is_dir($archiveDir)) mkdir($archiveDir, 0777, true);

			$user = \Config\Services::session()->get('user');
			$typeLabel = ($fileType === false || $fileType === '') ? 'UNKNOWN' : $fileType;
			$safeName = basename($origName);
			$archiveName = date('His').'_user'.$user.'_'.$status.'_'.$safeName;

			@copy($srcFile, $archiveDir.$archiveName);

			$clean = function($s) {
				$s = preg_replace('/<[^>]+>/', ' ', (string)$s);   // tag -> spatiu (nu lipi cuvintele)
				return trim(preg_replace('/\s+/', ' ', str_replace(["\r","\n","|"], [' ',' ','/'], $s)));
			};

			$logLine = date('Y-m-d H:i:s').' | user='.$user.' | file='.$safeName.' | type='.$typeLabel
					 .' | status='.$status.' | archived='.$day.'/'.$archiveName.' | msg='.$clean($msg);
			if ($exInfo !== '') $logLine .= ' | ex='.$clean($exInfo);

			file_put_contents($baseDir.'import_log.txt', $logLine.PHP_EOL, FILE_APPEND | LOCK_EX);
		}
		catch (\Exception $e) {
			log_message('error', 'archiveImport failed: '.$e->getMessage());
		}
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
				case 'TRANSILVANIA':
					$result = $this->import_TRANSILVANIA($filePath);
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
		elseif($extension=='csv' && (str_contains($fileName,'coloane') || $this->isTransilvaniaCSV($filePath))) return 'TRANSILVANIA';

		log_message('error','Unknown file type:'.$fileName);
		return false;
	}

	/*
	 * Detectie dupa continut a formatului Transilvania (curba de sarcina pe coloane):
	 * exportul real nu are 'coloane' in nume, dar contine antetul "Curba de sarcina"
	 * si coloanele "Wh_Delivered/Wh_Received". Citim doar primele randuri.
	 */
	private function isTransilvaniaCSV($filePath)
	{
		if (($h = fopen($filePath, 'r')) === FALSE) return false;
		$found = false; $n = 0;
		while ($n < 12 && ($line = fgets($h)) !== FALSE) {
			if (stripos($line, 'Wh_Delivered') !== false ||
				(stripos($line, 'Curba de sarcina') !== false && stripos($line, 'Titlu') !== false)) {
				$found = true; break;
			}
			$n++;
		}
		fclose($h);
		return $found;
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

			// Citesc antetul ca sa detectez formatul:
			//  vechi: Zi;Q1;...;Q96                 (o singura serie EA pe fisier)
			//  nou  : Zi;Frecventa;Marime;Q1;...;Q96 (mai multe randuri/zi: EA/EAP/ER/ERC)
			$header = fgetcsv($handle, 10000, ';');
			$marimeIdx = false; $frecventaIdx = false; $qCols = []; // qCols: index coloana => numar sfert (Q1=1)
			if ($header !== FALSE) {
				foreach ($header as $i => $h) {
					$h = strtoupper(trim($h ?? ''));
					if ($h === 'MARIME') $marimeIdx = $i;
					elseif ($h === 'FRECVENTA') $frecventaIdx = $i;
					elseif (preg_match('/^Q(\d+)$/', $h, $m)) $qCols[$i] = (int)$m[1];
				}
			}
			$newFormat = ($marimeIdx !== false && !empty($qCols));

			if ($newFormat)
			{
				// Format nou: importam DOAR randurile cu Marime = EA (sarim EAP/ER/ERC)
				while (($row = fgetcsv($handle, 10000, ';')) !== FALSE) {
					$rowIndex++;
					if (!isset($row[$marimeIdx]) || strtoupper(trim($row[$marimeIdx])) !== 'EA') continue;

					$readingDate = $row[0];
					$freq = ($frecventaIdx !== false && (int)$row[$frecventaIdx] > 0) ? (int)$row[$frecventaIdx] : 15;

					// q = numarul slotului de 15 min (Q1=00:00 ... Q96=23:45), pentru ambele frecvente.
					// La Freq=60 valoarea orara apare doar in slotul de start al orei (Q1,Q5,Q9..),
					// cu "-" in rest; o impartim la 4 in cele 4 sferturi ale orei respective.
					foreach ($qCols as $i => $q) {
						if (!isset($row[$i]) || !$this->isNumber($row[$i])) continue;
						$val = $this->getNumber($row[$i]);
						if ($freq == 60) {
							$base = ($q - 1) * 15; // minute de la miezul noptii
							for ($k = 0; $k < 4; $k++) {
								$tot = $base + $k * 15;
								$this->addSQLValuesString(sprintf("%s %02d:%02d:00", $readingDate, intdiv($tot, 60), $tot % 60), $val/4/1000);
							}
						} else {
							$min  = sprintf('%02d', (($q - 1) % 4) * 15);
							$hour = sprintf('%02d', intdiv(($q - 1), 4));
							$this->addSQLValuesString("$readingDate $hour:$min:00", $val/1000);
						}
					}
				}
			}
			else
			{
				// Format vechi (Zi;Q1;...): logica nemodificata
				$rowIndex = 1; // antetul a fost deja citit mai sus
				while (($row = fgetcsv($handle, 10000, ';')) !== FALSE) {

					$rowIndex++;

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

	/*
	 * Format pe coloane (DEER Transilvania): un fisier CSV cu mai multe POD-uri asezate pe coloane.
	 * Structura reala variaza (cu/fara randuri goale in antet), asa ca localizam randurile dupa continut:
	 *  - prima linie de date = primul rand al carui col0 e o data valida (d.m.Y H:i:s);
	 *  - randul cu coduri POD = un rand dinaintea datelor care contine coduri de min. 12 cifre
	 *    (pus sub coloana Wh_Delivered de importat; se curata backtick/apostrof pus de Excel);
	 *  - randul "Total" din footer = cifre de control per coloana.
	 * col0 = data (sfarsit de interval => shift -15 min), valori in MWh (fara impartire).
	 */
	public function import_TRANSILVANIA($filePath)
	{
		log_message('error', "Import daily readings $filePath for TRANSILVANIA");

		// Incarca toate randurile (fgetcsv respecta ghilimelele => zecimalele cu virgula raman intregi)
		$rows = [];
		if (($handle = fopen($filePath, 'r')) !== FALSE) {
			while (($row = fgetcsv($handle, 0, ',')) !== FALSE) {
				$rows[] = $row;
			}
			fclose($handle);
		}

		if (count($rows) < 8) return -110;

		// 1) Prima linie de date: primul rand cu col0 = data valida
		$dataStart = -1;
		for ($i = 0; $i < count($rows); $i++) {
			if ($this->formatDateText(trim($rows[$i][0] ?? ''), "d.m.Y H:i:s", "Y-m-d H:i:s") !== false) {
				$dataStart = $i; break;
			}
		}
		if ($dataStart < 0) return -110; // niciun rand de date

		// 2) Randul cu codurile POD: cautat in antet (inainte de date); coduri de min. 12 cifre
		$podRow = null;
		for ($i = 0; $i < $dataStart; $i++) {
			foreach ($rows[$i] as $cell) {
				if (preg_match('/^\d{12,}$/', trim(ltrim((string)$cell, "`' \t")))) { $podRow = $rows[$i]; break 2; }
			}
		}
		if ($podRow === null)
			return ['rows' => 0, 'imported' => 0, 'fileInfo' => 'Eroare: lipseste randul cu coduri POD (transilvania) -'];

		// Randul de control "Total" din footer (cifre de control per coloana)
		$totalRow = null;
		for ($i = $dataStart; $i < count($rows); $i++) {
			if (isset($rows[$i][0]) && strtolower(trim($rows[$i][0])) === 'total') { $totalRow = $rows[$i]; break; }
		}

		$affected = 0; $rowIndex = 0; $importedPods = 0;

		foreach ($podRow as $col => $podRaw) {
			$pod = trim(ltrim((string)$podRaw, "`' \t")); // curata backtick/apostrof pus de Excel
			if ($col == 0 || !preg_match('/^\d{12,}$/', $pod)) continue; // col0 = data; sar coloanele fara cod POD

			$distributorId = $this->importDailyReadingslModel->getDistributorIdByPODPrefix($pod);
			if (empty($distributorId)) {
				log_message('error', "Import daily readings $filePath for $pod, distributor_id not found!");
				continue;
			}

			$this->initSQLValuesString();
			$colSum = 0; $valCount = 0;

			// Ma opresc la primul col0 care nu e data (footer: Minimul/Media/Maximul/Total)
			for ($r = $dataStart; $r < count($rows); $r++) {
				$readingDate = $this->formatDateText(trim($rows[$r][0] ?? ''), "d.m.Y H:i:s", "Y-m-d H:i:s", "-15 minutes");
				if ($readingDate === false) break;

				$val = $rows[$r][$col] ?? '';
				if ($this->isNumber($val)) {
					$num = $this->getNumber($val);
					$this->addSQLValuesString($readingDate, $num);
					$colSum += (float)$num; $valCount++;
				}
			}

			$this->importSQLValues($pod, $distributorId);
			$affected += $this->sqlValuesLines;
			$rowIndex = max($rowIndex, $this->sqlValuesLines);
			$importedPods++;

			// Verificare cifra de control: suma parsata vs totalul din fisier
			if ($totalRow !== null && isset($totalRow[$col]) && $this->isNumber($totalRow[$col])) {
				$control = (float)$this->getNumber($totalRow[$col]);
				$tol = max(0.001, abs($control) * 0.005);
				if (abs($colSum - $control) > $tol) {
					$err = 'Total gresit: '.number_format($colSum, 3, '.', '').' vs '.number_format($control, 3, '.', '').' MWh';
					$this->importDailyReadingslModel->setDailyReadingsError($pod, $err);
					log_message('error', "Import daily readings $filePath for $pod: $err (n=$valCount)");
				}
			}
		}

		if ($importedPods == 0) return -110; // rand POD gasit dar niciun cod valid importat

		return ['rows' => $rowIndex, 'imported' => $affected, 'fileInfo' => 'Fisier import realizat zilnic (Transilvania)'];
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
