<?php namespace App\Controllers;

require_once('tools.php');
require_once('GridTools.php');

class BillEverything extends BaseController
{
	use \GridTools;
	private $data = [
        'title'   => 'Factureaza tot',
		'invoicing_date' => '',
		'output' => '',
		'menu'=>'facturi'];

	protected $billEverythingModel;

	function __construct() {
		checkAuth();
		//$_SESSION['msg'] = '';
	}
	
	    /**
     * Initializer 
     */
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {

        parent::initController($request, $response, $logger);

        // Load the model
        $this->billEverythingModel = new \App\Models\InvoicesModel();//new \App\Models\BillEverythingModel();
    }
	
	
	public function index()
	{
		$this->data['invoicing_date']=date("d-m-Y");
	
		$transport = $this->createGridTransport('view_status_tobeinvoiced','readInvoiceDate');

		$schema = $this->createGridSchema([
		'id','supplier_name','supplier_id','client','customer_id','contract_number','contract_id',
		'lot','zone_id','pods','ea_price','ea_quantity','invoice_value','status'],
		['string','string','number','string','number','string','number'
		,'string','number','number','number','number','number','string']);
		
	  		
		$dataSource = $this->createDataSource($transport,$schema);
					
		$dataSource->serverFiltering(true);
		$dataSource->serverPaging(true);
		$dataSource->serverSorting(true);
		
		$column1 = $this->createGridColumn('supplier_name','Furnizor',100);
		$column2 = $this->createGridColumn('client','Client',150);
		$column3 = $this->createGridColumn('lot','Lot',100);			
		$column4 = $this->createGridColumn('pods','Pod-uri',80);						
		$column5 = $this->createGridColumn('contract_number','Contract',150);
		$column6 = $this->createGridColumn('ea_price','Pret EA [lei]',100);
		$column6->template("#=kendo.format('{0:N2}',data.ea_price)#");
		$column7 = $this->createGridColumn('ea_quantity','Cantitate EA [MWh]',100);
		$column7->template("#=kendo.format('{0:N3}',data.ea_quantity)#");
		$column8 = $this->createGridColumn('invoice_value','Total Facturat [Lei]',100);
		$column8->template("#=kendo.format('{0:N2}',data.invoice_value)#");
		$column9 = $this->createGridColumn('error','Actiune',100);
		$column9->template("#=(data.error =='Pret nou') ? ('<button class=\"k-button k-button-solid-error k-rounded-md\" onClick=createPriceDialog('+ data.customer_id +');>'+data.error+'</button>') : 
							 ((data.error =='Contract nou') ? ('<button class=\"k-button k-button-solid-error k-rounded-md\" onClick=createContractDialog('+ data.customer_id +');>'+data.error+'</button>') : 
							 ((data.error =='Verifica MD') ? ('<button class=\"k-button k-button-solid-error k-rounded-md\" onClick=verifyMDDialog('+ data.customer_id +');>'+data.error+'</button>') : data.error)) #");

		$this->grid->height(550)
				 ->dataSource($dataSource)
				 ->resizable(true);
		
		$this->addCustomCommand('invoice','fa-solid fa-file-invoice','invoiceSelection');
		$this->setGridEditable('inline', false, false, true, false, true);
		
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		$this->data['output'] = $this->grid->render();
		
		$data['data'] = $this->data;
		return view('bill_everything',$data);
	}
}
