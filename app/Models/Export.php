<?php
namespace App\Controllers;

use IntlDateFormatter;
require_once(APPPATH . 'Libraries/ebs/FastExcel.php');

class Export extends BaseController
{
	private $data = [
        'title'   => '',
		'output' => '',
		'div-card' => 'card-raport',
		'menu' => 'rapoarte'];
	
	public function index()
	{
		$session = \Config\Services::session();
		if ($session->get('user') === NULL) return view('login.php'); 

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$type = $_GET['type'];
			if ($type == 'save') {
				$fileName = $_POST['fileName'];
				$contentType = $_POST['contentType'];
				$base64 = $_POST['base64'];

				$data = base64_decode($base64);

				header('Content-Type:' . $contentType);
				header('Content-Length:' . strlen($data));
				header('Content-Disposition: attachment; filename=' . $fileName);

				echo $data;
			}

			exit;
		}
		
		
		include(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php');
		
		if(isset($_GET['date']))
		{
			$this->data['date'] = \DateTime::createFromFormat('Y-n-d',$_GET['date'].'-01')->getTimestamp();
		}
		else
			$this->data['date'] = strtotime("last day of previous month");
			
			
		$exportModel = new \App\Models\ExportModel();
		$exportModel->updateCustomerBand();
		
		$data1 = $exportModel->get_sheet1(date('Y-m-t',$this->data['date']));
		$data3 = $exportModel->get_sheet3(date('Y-m-t',$this->data['date']));
		
		$spreadsheet = new \Kendo\UI\Spreadsheet('spreadsheet');
		$spreadsheet->renderEvent('function(e) { var height = window.innerHeight-$(".card").outerHeight()-$(".navbar").outerHeight()-25; e.sender.element.innerHeight(height);}');

		$spreadsheet->attr('style', 'width: 100%;');

		$excel = new \Kendo\UI\SpreadsheetExcel();
		$excel->fileName('ANRE '.date("Y-m", $this->data['date']).'.xlsx')
			  ->proxyURL('export?date='.date('Y-m',$this->data['date']).'&type=save');

		$spreadsheet->excel($excel);

		$pdf = new \Kendo\UI\SpreadsheetPdf();
		$pdf->fileName('ANRE '.date("Y-m", $this->data['date']).'.pdf')
			  ->proxyURL('export?date='.date('Y-m',$this->data['date']).'&type=save');

		$spreadsheet->pdf($pdf);

		$backgroundColors = ['DEER Muntenia Nord'=>'#A8D08D','DEER Transilvania Nord'=>'#8EAADB','DEER Transilvania Sud'=>'#FFD966','DELGAZ GRID S.A.'=>'#AEAAAA','DISTRIBUTIE ENERGIE OLTENIA S.A.'=>'#F4B083','RETELE ELECTRICE BANAT S.A.'=>'#BE91DF','RETELE ELECTRICE DOBROGEA S.A.'=>'#FFFF00','RETELE ELECTRICE MUNTENIA S.A.'=>'#68A19F'];
		$color = '#000000';
		
		$sheet = new \Kendo\UI\SpreadsheetSheet();
		$monthNumber = date("m", $this->data['date']);
		$sheet->name($monthNumber);
		$sheet->frozenRows(2);
		
		$mergedCellsArray=array("A1:G1");

		$spreadsheet->addSheet($sheet);

		$row = new \Kendo\UI\SpreadsheetSheetRow();
		$row->height(40);
		$sheet->addRow($row);

		$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
		$row->addCell($cell);
		// sudo locale-gen ro_RO
		// sudo locale-gen ro_RO.UTF-8
		//setlocale(LC_TIME, 'ro_RO');
		//$cell->value(strftime("%^B-%Y", $this->data['date']));
		$formatter = new \IntlDateFormatter('ro_RO', IntlDateFormatter::LONG, IntlDateFormatter::NONE,null,null,"MMMM-yyyy");
		$cell->value(ucfirst($formatter->format($this->data['date'])));
		
		$cell->fontSize(32);
		$cell->textAlign("center");
		//$cell->background("rgb(96,181,255)");
		$cell->color("black");


		$row = new \Kendo\UI\SpreadsheetSheetRow();
		$row->height(25);
		$sheet->addRow($row);

		$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
		$row->addCell($cell);

		$cell->value("Nr.Crt.");
		$cell->textAlign("center");
		$cell->background("rgb(167,214,255)");
		$cell->color("black");
		$cell->bold(true);
		
		$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
		$row->addCell($cell);

		$cell->value("Zona Licenta");
		$cell->textAlign("center");
		$cell->background("rgb(167,214,255)");
		$cell->color("black");
		$cell->bold(true);

		$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
		$row->addCell($cell);
		
		$cell->value("Consumator");
		$cell->textAlign("center");
		$cell->background("rgb(167,214,255)");
		$cell->color("black");
		$cell->bold(true);
		
		$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
		$row->addCell($cell);

		$cell->value("Adresa Loc Consum");
		$cell->textAlign("center");
		$cell->background("rgb(167,214,255)");
		$cell->color("black");
		$cell->bold(true);
		
		$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
		$row->addCell($cell);

		$cell->value("Tip");
		$cell->textAlign("center");
		$cell->background("rgb(167,214,255)");
		$cell->color("black");
		$cell->bold(true);
		
		$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
		$row->addCell($cell);
	
		$cell->value("Consum (MWh)");
		$cell->textAlign("center");
		$cell->background("rgb(167,214,255)");
		$cell->color("black");
		$cell->bold(true);
		
		$no=1;
		$zl = '';
		$zl_start = 0; $zl_end = 0;
		
		foreach($data1 as $d)
		{
			if($zl != $d['ZonaLicenta']) 
			{
				if($zl_start != 0)
				{
					$zl_end = $no+1;
					array_push($mergedCellsArray,'B'.$zl_start.':B'.$zl_end);
					$zl_start=$no+2;
					
				}
				else $zl_start = $no+2;
				
				$zl = $d['ZonaLicenta'];
			}
			
			$row = new \Kendo\UI\SpreadsheetSheetRow();
			$sheet->addRow($row);

			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$row->addCell($cell);

			$cell->value($no++);
			$cell->textAlign("center");
			$cell->background($backgroundColors[$d['ZonaLicenta']]);
			$cell->color($color);
			
			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$row->addCell($cell);

			$cell->value($d['ZonaLicenta']);
			$cell->textAlign("center");	
			$cell->background($backgroundColors[$d['ZonaLicenta']]);			
			$cell->color($color);

			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$row->addCell($cell);

			$cell->value($d['Consumator']);
			$cell->textAlign("left");	
			$cell->background($backgroundColors[$d['ZonaLicenta']]);
			$cell->color($color);
			
			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$row->addCell($cell);
			$cell->background($backgroundColors[$d['ZonaLicenta']]);
			$cell->color($color);
			
			$cell->value($d['Oras']);
			$cell->textAlign("left");	
			$cell->background($backgroundColors[$d['ZonaLicenta']]);
			$cell->color($color);
			
			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$row->addCell($cell);

			$cell->value($d['Tip']);
			$cell->textAlign("left");			
			$cell->background($backgroundColors[$d['ZonaLicenta']]);
			$cell->color($color);
			
			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$row->addCell($cell);

			/*$cell->value($d['LocurideConsum']);
			$cell->textAlign("left");	
			$cell->background($backgroundColors[$d['ZonaLicenta']]);
			$cell->color($color);
			
			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$row->addCell($cell);*/

			$cell->value(floatval($d['Consum']));
			$cell->textAlign("center");	
			$cell->background($backgroundColors[$d['ZonaLicenta']]);
			$cell->color($color);
		}
		
		$filter = new \Kendo\UI\SpreadsheetSheetFilter();
		$filter->ref("A2:F".($no+1));
		$sheet->filter($filter);
		
		
		$row = new \Kendo\UI\SpreadsheetSheetRow();
		$row->index($no+1);
		$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
		$cell->index(4);
		$cell->value("Total EA");
		$cell->textAlign("right");
		$cell->color("black");
		$cell->bold(true);
		
		$row->addCell($cell);
		
		$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
		$cell->index(5);
		$cell->formula("sum(F3:F".($no+1).")");
		$cell->textAlign("center");
		$cell->color("black");
		$cell->bold(true);
		$cell->format("#,###0.000");
		
		$row->addCell($cell);
		$sheet->addRow($row);
		
		//merge last cells
		$zl_end = $no+1;
		array_push($mergedCellsArray,'B'.$zl_start.':B'.$zl_end);
		
		$sheet->mergedCells($mergedCellsArray);
		
		//align merged cells
		foreach($mergedCellsArray as $mc)
		{
			$d = explode(":",$mc);
			$r = ltrim($d[0], $d[0][0]);
			$row = new \Kendo\UI\SpreadsheetSheetRow();
			$row->index($r-1);
			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$cell->index(1);
			$cell->verticalAlign("center");
			$row->addCell($cell);
			$sheet->addRow($row);
		}
		
		$column = new \Kendo\UI\SpreadsheetSheetColumn();
		$column->width(100);
		$sheet->addColumn($column);

		$column = new \Kendo\UI\SpreadsheetSheetColumn();
		$column->width(215);
		$sheet->addColumn($column);

		$column = new \Kendo\UI\SpreadsheetSheetColumn();
		$column->width(315);
		$sheet->addColumn($column);

		$column = new \Kendo\UI\SpreadsheetSheetColumn();
		$column->width(115);
		$sheet->addColumn($column);
		
		$column = new \Kendo\UI\SpreadsheetSheetColumn();
		$column->width(70);
		$sheet->addColumn($column);

		$column = new \Kendo\UI\SpreadsheetSheetColumn();
		$column->width(155);
		$sheet->addColumn($column);

		$column = new \Kendo\UI\SpreadsheetSheetColumn();
		$column->width(155);
		$sheet->addColumn($column);
		
		
		//monthly summary------------------------------------------------------------------------------------//
		
		//construct sheet3Formulas
		
		$licenseZone = '';$idxStart=3;$idxStop=3;
		foreach($data3 as $d)
		{
			if($licenseZone == '') $licenseZone = $d['ZonaLicenta'];
			if($d['ZonaLicenta'] != $licenseZone)
			{
				$zoneFormula[$licenseZone] = '=SUM(ANRE!G'.$idxStart.':G'.($idxStop-1).')';
				$licenseZone = $d['ZonaLicenta'];
				$idxStart = $idxStop;
			}
			
			$idxStop ++;
				
		}
		$zoneFormula[$licenseZone] = '=SUM(ANRE!G'.$idxStart.':G'.($idxStop-1).')';
						
		$summary = new \FastExcel($sheet);
		$summary->setBorder();
		$summary->addHeader(["Zona Licenta","TOTAL PV\n[1]","TOTAL CURBE\n[2]","DIFERENTE\n[1]-[2]","TOTAL PRE\n[3]","DIFERENTE\n[1]-[3]","COGENERARE"]);

		$data = $exportModel->get_sheet1_summary(date('Y-m-t',$this->data['date']));
		$summary->setRowValueTypes(['Text','#,###0.000','#,###0.000','#,###0.000','#,###0.000','#,##0.000','#,##0.000']);
		
		$idx = $no+13;
		foreach($data as $d)
		{
			//$summary->addRow([$d['ZonaLicenta'],$d['TotalPV'],$d['TotalCurbe'],$d['Diferente1'],$d['TotalPre'],$d['Diferente2'],$d['Cogenerare']],'black',$backgroundColors[$d['ZonaLicenta']]);
			if(isset($zoneFormula[$d['ZonaLicenta']])) $f = $zoneFormula[$d['ZonaLicenta']];
			else $f=0.000;
			$summary->addRow([$d['ZonaLicenta'],$f,(float)$d['TotalCurbe'],"=C$idx-B$idx",$d['TotalPre'],"=B$idx-E$idx",$d['Cogenerare']],'black',$backgroundColors[$d['ZonaLicenta']]);
			$idx++;
		}
		
		$summary->addTotal($no+12);
		
//------------------------ACH-V----------------------------------------------------//
		$sheet = new \Kendo\UI\SpreadsheetSheet();
		$sheet->name('ACH-V');
		$spreadsheet->addSheet($sheet);
		
		$ach = new \FastExcel($sheet);
		
		$ach->setColumnsWidth([50,200,100,100,100,100,100]);
		$ach->addTitle("ACHIZITIE",7);
		$ach->addHeader(['Nr.','Contraparte','Perioada','',"Cantitate\n[MWh]",'Pret','Valoare']);
		$ach->setRowValueTypes(['#,###','Text','Text','Text','#,###0.000','#,##0.00','#,##0.00']);
		$i=1;
		foreach($exportModel->get_purchases(date('Y-m-t',$this->data['date'])) as $d)
		{
			$ach->addRow([$i++,$d['company_name'],substr($d['date_start'],0,10),substr($d['date_end'],0,10),floatval($d['quantity']),floatval($d['price']),'=E'.($i+1).'*F'.($i+1)]);
		}
		
		if($i>1)$ach->addTotal(-1,4,[5=>'G'.($i+2).'/E'.($i+2).'']);
		else 
			$i--;
		$achTotalRow = $ach->getCurrentRow();
		
		$ach->addRow();		$ach->addRow();		$ach->addRow();		$ach->addRow();		$ach->addRow();
		$i+=5;
		
		$ach->addTitle("VANZARE",7);
		$ach->addHeader(['Nr.','Contraparte','Perioada','',"Cantitate\n[MWh]",'Pret','Valoare']);
		$ach->setRowValueTypes(['#,###','Text','Text','Text','#,###0.000','#,##0.00','#,##0.00']);
		
		$anreCount = count($data3);
		
		$ach->addRow([1,'CLIENTI',date('Y-m-01',$this->data['date']),date('Y-m-t',$this->data['date']),'=SUM(ANRE!G3:G'.($anreCount+2).')','=G'.($i+5).'/E'.($i+5),'=ANRE!U'.($anreCount+3)]);
		$j=2;
		foreach($exportModel->get_sales(date('Y-m-t',$this->data['date'])) as $d)
		{
			if(strcasecmp($d['company_name'] ,'Clienti') == 0) continue;
			$ach->addRow([$j++,$d['company_name'],substr($d['date_start'],0,10),substr($d['date_end'],0,10),floatval($d['quantity']),floatval($d['price']),'=E'.($i+$j+3).'*F'.($i+$j+3)]);
		}
		
		$ach->addTotal(-1,4,[5=>'G'.($i+$j+4).'/E'.($i+$j+4).'']);
		
		$ach->mergeCells();
		
		
//-------------------------ANRE---------------------------------------------------//
		$backgroundColors = ['IA'=>'#FFFF00','IB'=>'#FF8800','IC'=>'#CCAAFF','ID'=>'#88BB00','IE'=>'#3399FF','IF'=>'#FF7C80','Altii'=>'#FFFFFF'];
			
		$anre = new \FastExcel($null, 'ANRE');

		$spreadsheet->addSheet($anre->getSheet());

		$anre->setFrozen(2,4);
		$anre->setColumnsWidth([50,50,250,50,115,50,100,100,100,100,100,100,100,100,100,100,100,100,100,100,100,100,100,100]);
		setlocale(LC_TIME, 'ro_RO');
		//$anre->addTitle(strftime("%^B-%Y", $this->data['date']),18);
		$formatter = new \IntlDateFormatter('ro_RO', IntlDateFormatter::LONG, IntlDateFormatter::NONE,null,null,"MMMM-yyyy");
		$anre->addTitle(ucfirst($formatter->format($this->data['date'])),18);

		$anre->addHeader(["Zona\nLicenta","Nr.","Consumator","Banda","Oras","Tip",
		"Cantitate\n[MWh]","Transport\n[lei/MWh]","Distributie\n[lei/MWh]","Cogenerare\n[lei/MWh]","Certificate Verzi\n[lei/MWh]","Acciza\n[lei/MWh]","Piete Centralizate\n[lei/MWh]","Pret Energie\n[lei/MWh]",
		"Valoare Transport\n[lei]","Valoare Distributie\n[lei]","Valoare Cogenerare\n[lei]","Valoare Certificate Verzi\n[lei]","Valoare Acciza\n[lei]","Valoare Piete Centralizate\n[lei]","Valoare Energie\n[lei]","Total fara TVA\n[lei]",
		"Total cu TVA\n[lei]","Valoare Energie Reactiva\n[lei]"]);
		$anre->setRowValueTypes(['Text','#,###','Text','Text','Text','Text',
		'#,###0.000','#,##0.00','#,##0.00','#,##0.00','#,##0.00','#,##0.00','#,##0.00','#,##0.00',
		'#,##0.00','#,##0.00','#,##0.00','#,##0.00','#,##0.00','#,##0.00','#,##0.00','#,##0.00',
		'#,##0.00','#,##0.00']);
		
		$no=1;
		$zl = '';
		$zl_start = 0; $zl_end = 0;
		$mergedCellsArray=[];
		foreach($data3 as $d)
		{				
			if($zl != $d['ZonaLicenta']) 
			{
				if($zl_start != 0)
				{
					$zl_end = $no+1;
					$anre->mergeCells('A'.$zl_start.':A'.$zl_end);
					$zl_start=$zl_end+1;
					
				}
				else $zl_start = $no+2;
				
				$zl = $d['ZonaLicenta'];
			}
		
			$anre->addRow([$d['ZonaLicenta'],$no++,$d['Consumator'],$d['Banda'],$d['Oras'],$d['Tip'],
			'=\''.$monthNumber.'\'!F'.($no+1),floatval($d['Transport']),floatval($d['Distributie']),floatval($d['Cogenerare']),floatval($d['CertificateVerzi']),floatval($d['Acciza']),floatval($d['PieteCentralizate']),floatval($d['PretEn']),
			'=G'.($no+1).'*H'.($no+1),'=G'.($no+1).'*I'.($no+1),'=G'.($no+1).'*J'.($no+1),'=G'.($no+1).'*K'.($no+1),'=G'.($no+1).'*L'.($no+1),'=G'.($no+1).'*M'.($no+1),'=G'.($no+1).'*N'.($no+1).'+T'.($no+1),'=sum(O'.($no+1).':U'.($no+1).')-T'.($no+1),
			'=V'.($no+1).'*1.19',floatval($d['ValoareEnergieReactiva'])],'black',$backgroundColors[$d['Banda']]);
		}
		
		//merge last cells
		$zl_end = $no+1;
		$anre->mergeCells('A'.$zl_start.':A'.$zl_end);

		$anre->setFilter("A2:X".$zl_end);
		
		$anre->addTotal(-1,14);

//Summary ANRE---------------------------------------------------//

		
		$anre->setOffset(5,2);
		$anre->setBorder();
		
		$anre->addHeader(["Categorie Clienti\nConsum anual cuprins in intervalul (MWh):","Banda","Consum / \nCategorie","Nr \nClienti\n/luna","Renuntari \nClienti","Clienti \nNoi",
		"Pret mediu \nde vanzare 4)",
		"Contravaloare \nservicii 5)",
		"Valoare \nacciza\n[lei]",
		"Valoare \ncogenerare\n[lei]",
		"Valoare \ncertificate verzi\n[lei]"]);
		
		$anre->setRowValueTypes(['Text','#,###','#,###0.000','#,###0','#,###','#,###',
		'#,##0.00',
		'#,##0.00',
		'#,##0.00',
		'#,##0.00',
		'#,##0.00']);
		
		$anre->addColAttributes(0,"textAlign","left");
		$anre->addColAttributes(0,"bold",true);
		$anre->addColAttributes(1,"bold",true);
		
		$lastRow = $no+1;
		$i=1;
		$bands = $exportModel->get_all_bands();
		foreach($bands as $b)
		{
			$anre->addRow(['Banda '.$b['minConsumption'].' - '.$b['maxConsumption'],$b['anreBand'],"=SUMIF(D3:D".($no+1).",D".($no+8+$i).",G3:G".($no+1).")",floatval($exportModel->get_customers_band($b['anreBand'],date('Y-m-t',$this->data['date']))),floatval($exportModel->get_removed_customers_no(date('m',$this->data['date']),date('Y',$this->data['date']),$b['anreBand'])),floatval($exportModel->get_new_customers_no(date('m',$this->data['date']),date('Y',$this->data['date']),$b['anreBand'])),
			"=(SUMIF(D3:D".($lastRow).",D".($no+8+$i).",O3:O".($lastRow).")+SUMIF(D3:D".($lastRow).",D".($no+8+$i).",P3:P".($lastRow).")+SUMIF(D3:D".($lastRow).",D".($no+8+$i).",U3:U".($lastRow)."))/max(E".($no+8+$i).",1)",
			"=(SUMIF(D3:D".($lastRow).",D".($no+8+$i).",O3:O".($lastRow).")+SUMIF(D3:D".($lastRow).",D".($no+8+$i).",P3:P".($lastRow)."))/max(E".($no+8+$i).",1)",
			"=SUMIF(D3:D".($lastRow).",D".($no+8+$i).",S3:S".($lastRow).")",
			"=SUMIF(D3:D".($lastRow).",D".($no+8+$i).",Q3:Q".($lastRow).")",
			"=SUMIF(D3:D".($lastRow).",D".($no+8+$i).",R3:R".($lastRow).")"]
			,'black',$backgroundColors[$b['anreBand']]);
			
			$no++;
		}
		
		$anre->mergeCells();
				
		
//Raport P & L	
		$pandl = new \FastExcel($null, 'P&L');
		$pandl->setFrozen(2,0);
		$spreadsheet->addSheet($pandl->getSheet());
		
		$pandl->setColumnsWidth([50,50,250,50,115,50,100,100,100,100,100]);
		setlocale(LC_TIME, 'ro_RO');
		//$pandl->addTitle("P&L - ".strftime("%^B-%Y", $this->data['date']),10);
		$formatter = new \IntlDateFormatter('ro_RO', IntlDateFormatter::LONG, IntlDateFormatter::NONE,null,null,"MMMM-yyyy");
		$pandl->addTitle("P&L - ".ucfirst($formatter->format($this->data['date'])),10);
		
		$pandl->addHeader(["Zona\nLicenta","Nr.","Consumator","Banda","Consum EA","Pret Unitar","Valoare Consum","Cantitate EA Cumparata","Pret Cumparare","Valoare Cumparata","P&L"]);
		$pandl->setRowValueTypes(['Text','#,###','Text','Text','#,###0.000','#,##0.00','#,##0.00','#,##0.00','#,###0.000','#,##0.00','#,##0.00']);
		$i=1;
		
		$purchases=[];
		$consumption = $exportModel->get_pandl_consumptions(date('Y-m-01',$this->data['date']));
		foreach($exportModel->get_pandl_purchases(date('Y-m-01',$this->data['date'])) as $p)
		{
			if(!isset($purchases[$p['price']])) $purchases[$p['price']] = 0;
			$purchases[$p['price']] += $p['quantity'];
		}
		
		foreach($consumption as $c)
		{
			$Aprice = $Avolume = 0;
			foreach($purchases as $price=>$quantity)
			{
				if($c['price'] > $price)
				{
					$Aprice = $price;

					if($quantity<$c['ea'])
					{
						$Avolume = $quantity;
						$c['ea'] -= $Avolume;
						$pandl->addRow([$c['distributor_name'],$i++,$c['customer'],$c['band'],floatval($Avolume),floatval($c['price']),'=E'.($i+1).'*F'.($i+1),floatval($Avolume),floatval($Aprice),'=H'.($i+1).'*\'ACH-V\'!$F$'.$achTotalRow/*'*I'.($i+1)*/,'=G'.($i+1).'-J'.($i+1)]);
						unset($purchases[$price]);
						continue; //next price
					}
					else
					{
						$Avolume = $c['ea'];
						$purchases[$price] -=$c['ea'];
						$pandl->addRow([$c['distributor_name'],$i++,$c['customer'],$c['band'],floatval($c['ea']),floatval($c['price']),'=E'.($i+1).'*F'.($i+1),floatval($Avolume),floatval($Aprice),'=H'.($i+1).'*\'ACH-V\'!$F$'.$achTotalRow/*'*I'.($i+1)*/,'=G'.($i+1).'-J'.($i+1)]);
						$c['ea'] = 0;
						break; //next customer
					}
				}
			}
			
			//cheaper price not found
			if($c['ea']!=0)
			{
				foreach($purchases as $price=>$quantity)
				{
					$Aprice = $price;

					if($quantity<$c['ea'])
					{
						$Avolume = $quantity;
						$c['ea'] -= $Avolume;
						$pandl->addRow([$c['distributor_name'],$i++,$c['customer'],$c['band'],floatval($Avolume),floatval($c['price']),'=E'.($i+1).'*F'.($i+1),floatval($Avolume),floatval($Aprice),'=H'.($i+1).'*\'ACH-V\'!$F$'.$achTotalRow/*'*I'.($i+1)*/,'=G'.($i+1).'-J'.($i+1)]);
						unset($purchases[$price]);
						continue; //next price
					}
					else
					{
						$Avolume = $c['ea'];
						$purchases[$price] -=$c['ea'];
						$pandl->addRow([$c['distributor_name'],$i++,$c['customer'],$c['band'],floatval($c['ea']),floatval($c['price']),'=E'.($i+1).'*F'.($i+1),floatval($Avolume),floatval($Aprice),'=H'.($i+1).'*\'ACH-V\'!$F$'.$achTotalRow/*'*I'.($i+1)*/,'=G'.($i+1).'-J'.($i+1)]);
						$c['ea'] = 0;
						break; //next customer
					}
				}
			}
		}
		
		if($i>1)
			$pandl->addTotal(-1,4,[5=>'G'.($i+2).'/E'.($i+2),8=>'J'.($i+2).'/H'.($i+2)]);
	
		$pandl->mergeCells();
		$this->data['title'] = "Raport ANRE";
		$this->data['output'] = $spreadsheet->render();
				
		$data['data'] = $this->data;
				
		return view('export',$data);
	}
	
	public function raport_consumlunar()
	{
		$session = \Config\Services::session();
		if ($session->get('user') === NULL) return view('login.php'); 
		
		include(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php');
		
		
		if(isset($_GET['year']))
		{
			$this->data['year'] = $_GET['year'];
		}
		else
			$this->data['year'] = date('Y');
		
		$exportModel = new \App\Models\ExportModel();
		$mcData = $exportModel->get_monthly_consumption($this->data['year']);
			
		$spreadsheet = new \Kendo\UI\Spreadsheet('spreadsheet');
		$spreadsheet->attr('style', 'width: 100%;');
		$spreadsheet->renderEvent('function(e) { var height = window.innerHeight-$(".card").outerHeight()-$(".navbar").outerHeight()-25; e.sender.element.innerHeight(height);}');
		
		$cl = new \FastExcel($null, 'Consum Realizat');
		$cl->setFrozen(2,0);
		$spreadsheet->addSheet($cl->getSheet());
		$cl->setColumnsWidth([300,80,80,80,80,80,80,80,80,80,80,80,80]);
		
		$cl->addTitle("Consum - ".$this->data['year'],10);
		
		$cl->addHeader(["Consumator/Luna","Ianuarie","Februarie","Martie","Aprilie","Mai","Iunie","Iulie","August","Septembrie","Octombrie","Noiembrie","Decembrie"]);
		$cl->setRowValueTypes(['Text','#,###0.000','#,###0.000','#,###0.000','#,###0.000','#,###0.000','#,###0.000','#,###0.000','#,###0.000','#,###0.000','#,###0.000','#,###0.000','#,###0.000']);
		$cl->setRowAlignment(['left','center','center','center','center','center','center','center','center','center','center','center','center']);
		$i=1;
		foreach($mcData as $c)
		{
			$cl->addRow([$c['customer_name'],floatval($c['1']),floatval($c['2']),floatval($c['3']),floatval($c['4']),floatval($c['5']),floatval($c['6']),floatval($c['7']),floatval($c['8']),floatval($c['9']),floatval($c['10']),floatval($c['11']),floatval($c['12'])]);
			$i++;
		}
		
		if($i>1)
			$cl->addTotal(-1,1);
		
		$cl->mergeCells();
		
			
		$rY = $exportModel->get_min_max_years('invoices','invoice_date');
		
		$range = [];
		if(!empty($rY))
		{
			for($y = $rY['minY'];$y<=$rY['maxY'];$y++)
				array_push($range,array('text' => (string)$y, 'value' => $y));
		}
		
		$this->data['range'] = $range;
		$this->data['title'] = "Raport Consum Lunar";
		$this->data['output'] = $spreadsheet->render();
				
		$data['data'] = $this->data;
				
		return view('export',$data);
	}
}
