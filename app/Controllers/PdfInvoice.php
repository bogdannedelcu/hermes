<?php 
namespace App\Controllers;

require_once('tools.php');
use CodeIgniter\Controller;

class PdfInvoice extends Controller
{

	private $data = [
        'title'   => 'Invoice',
		'invoiceNo'   => 0];
	
	function __construct() {	
		checkAuth();
	}
	
	
	public function generatePDF($invoiceId, $streamPDF = false, $forceCreatePDF = false)
	{
		$pdfInvoiceModel = new \App\Models\pdfInvoiceModel($invoiceId);
		
		$data['invoiceData'] = $pdfInvoiceModel->get_invoice_data();
		$data['customerData'] = $pdfInvoiceModel->get_customer_data();
		
		$filename = $data['invoiceData']['invoice_no'].' - '.$data['customerData']['customer_name'].'.pdf';	
						
		if ($data['invoiceData']['invoice_status'] != 'In pregatire' && !$forceCreatePDF && file_exists(WRITEPATH.'invoices/'.$filename) && $streamPDF)
		{
			if ($streamPDF)
			{				
				if(!file_exists(WRITEPATH.'invoices/'.$filename))
				{ 
				  echo 'Eroare critica: Fisierul nu exista!';
				  exit();
				}
				
				$data   = file_get_contents(WRITEPATH.'invoices/'.$filename);
				
				header("Content-type: application/octet-stream");
				header("Content-disposition: attachment;filename=".$filename);
	  
				echo $data;
				
				exit(0);
			}
			else return;			
		}
		$data['distributor_data'] = $pdfInvoiceModel->get_distributor_data();
		$data['supplierData'] = $pdfInvoiceModel->get_supplier_data();
		$data['invoicedItems'] = $pdfInvoiceModel->get_invoiced_items();
		$data['eaQuantities'] = $pdfInvoiceModel->get_ea_quantities();
		$data['reQuantities'] = $pdfInvoiceModel->get_re_quantities();
		$data['invoice_annex'] = $pdfInvoiceModel->get_invoice_annex();
		$data['invoice_footer'] = $pdfInvoiceModel->get_invoice_footer();
		$data['pod_devices'] = $pdfInvoiceModel->get_pod_devices();
		$data['vat'] = $pdfInvoiceModel->get_invoice_vat();
		
		log_message('info',print_r($data['invoice_footer'], TRUE));
		log_message('info',print_r($data['distributor_data'], TRUE));
		
		if(!empty($data['distributor_data']))
		{
			$data['invoice_footer'] = str_replace('{distributor_name}',$data['distributor_data']['distributor_name'],$data['invoice_footer']);
			$data['invoice_footer'] = str_replace('{distributor_emergency_phone}',$data['distributor_data']['distributor_emergency_phone'],$data['invoice_footer']);
			$data['invoice_footer'] = str_replace('{distributor_order_anre}',$data['distributor_data']['distributor_order_anre'],$data['invoice_footer']);
		}
		
		//$data['invoicedTotals'] = $pdfInvoiceModel->get_invoiced_total_value();-- se calculeaza in view
		ob_get_clean();
		$options = new \Dompdf\Options();
		$options->set('isRemoteEnabled', true);
		$options->set("isPhpEnabled", true);
		
		$dompdf = new \Dompdf\Dompdf($options);
		$dompdf->setHttpContext(stream_context_create([
			'ssl' => [
				'verify_peer' => FALSE,
				'verify_peer_name' => FALSE,
				'allow_self_signed'=> TRUE
			],
		]));
		
        $dompdf->loadHtml(view('pdfInvoice', $data));
		
		$dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
		
		// Parameters
		$x          = 305;
		$y          = 820;
		$text       = 'Factura No.'.$data['invoiceData']['invoice_no'].'                             Pagina {PAGE_NUM} din {PAGE_COUNT}';     
		$font       = $dompdf->getFontMetrics()->get_font('Helvetica', 'normal');   
		$size       = 10;    
		$color      = array(0,0,0);
		$word_space = 0.0;
		$char_space = 0.0;
		$angle      = 0.0;

		$dompdf->getCanvas()->page_text(
		  $x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle
		);
		
		$savein = 'uploads/invoices/';
		$filename = $data['invoiceData']['invoice_no'].' - '.$data['customerData']['customer_name'].'.pdf';
		$pdf = $dompdf->output();      // gets the PDF as a string
		file_put_contents(WRITEPATH.'invoices/'.$filename, $pdf);    // save the pdf file on server
		
		if ($streamPDF)
		{
			$array = array(
				"compress" => 0,
				"Attachment" => 0
			);
			
			ob_end_clean();
			$dompdf->stream($filename);
			
			exit(0);
		}		
	}
	
    public function index() 
	{
		$request = $this->request;
		$invoiceId = $request->getVar('invoiceId');
		
		if ($invoiceId === NULL) return;
		
		$this->generatePDF($invoiceId,true);

    }
	
	public function downloadPDFs()
	{
		$invoiceIds = $this->request->getVar('invoiceIds');
		
		$pdfInvoiceModel = new \App\Models\pdfInvoiceModel($invoiceIds);
		
		$files = $pdfInvoiceModel->getInvoiceFileNames($invoiceIds);
		
		$zipname = WRITEPATH.'invoices/Facturi.zip';
		
		$zip = new \ZipArchive;
		if($zip->open($zipname, \ZipArchive::CREATE)!==TRUE) {
			exit("Eroare creare zip <$zipname>\n");
		}
		
		$c = 0;
		foreach ($files as $file) {

		  if(!file_exists($file))
			  exit("Eroare critica: $file nu exista!");
		
		  $c++;
		  $zip->addFile($file,basename($file));
		}
		
		if($c==0)
			$zip->addEmptyDir('.');
		
		$zip->close();
		
		header('Content-Type: application/zip');
		header('Content-disposition: attachment; filename='.basename($zipname));
		header('Content-Length: ' . filesize($zipname));
		readfile($zipname);
		
		unlink($zipname);
	}

}
