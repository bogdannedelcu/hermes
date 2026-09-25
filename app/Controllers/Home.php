<?php
namespace App\Controllers;

include_once(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php');
include_once(APPPATH . 'Libraries/telerik/lib/DataSourceResultNew.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class Home extends BaseController
{
	private $data = [
        'title'   => '',
		'menu' => ''];
		
	public function index()
	{
		$session = \Config\Services::session();
		
		if(isset($_GET['logout']))
			unset($_SESSION['user']);
		
		$mdModel = new \App\Models\MasterDataModel();
		$this->data['card'] = "home-card.php";
		
		$this->data['alert'] = $mdModel->executeAlert(2);

		$this->data['jsFiles'] = ['home.js'];
		
		if ($session->get('user') === NULL) return view('login.php'); 
		$data['data'] = $this->data;
		
		return view('home',$data);
	}
	
	public function system()
	{
		$session = \Config\Services::session();
		
		if(isset($_GET['logout']))
			unset($_SESSION['user']);
		
		if ($session->get('user') === NULL) return view('login.php'); 
		$this->data['title'] = 'System';
		
		$masterDataModel =  new \App\Models\MasterDataModel();
		
		$form = new \Kendo\UI\Form('cleanupForm');
		$form->formData(array("date"=>date("m-Y", strtotime("-1 months")),"consumptions" => true,"invoices" => true,"actual_readings" => true,"curves_variance" => true,"curves" => true));
		$form->buttonsTemplate("<div style='width:100%;overflow:hidden;'>
								<div><button id='deleteBtn' type='button' onClick= 'deleteData()' class='k-grid-update k-button k-button-md k-button-rectangle k-rounded-md k-button-solid k-button-solid-error' style='float:left;min-width:80px;'><span class='k-icon k-i-cancel k-button-icon'></span> Șterge Datele </button></div>
								</div>");			
		
		/******CLEAN UP DATA**********/
		$date = new \Kendo\UI\FormItem();
		$date
			->label("Luna-An")
			->editor("DatePicker")
			->editorOptions(array('start' => 'year','depth'=>'year','format'=>'MM-yyyy'))
			->field("date");
		
		$formItemD = new \Kendo\UI\FormItem();
		$formItemD
			->label("Sterge datele din perioada")
			->type("group");
		
		$formItemD->addItem($date);	

		$consumptions = new \Kendo\UI\FormItem();
		$consumptions
			->label("Consumuri")
			->field("consumptions");

		$invoices = new \Kendo\UI\FormItem();
		$invoices
			->label("Facturi (din luna urmatoare)")
			->field("invoices");
			

		$formItem1 = new \Kendo\UI\FormItem();
		$formItem1
			->label("Sterge datele din Modul Facturare")
			->type("group");
			
		$formItem1->addItem($consumptions);	
		$formItem1->addItem($invoices);	
		
		$formItem2 = new \Kendo\UI\FormItem();
		$formItem2
			->label("Sterge datele din Modul Realizat")
			->type("group");

		$actual_readings = new \Kendo\UI\FormItem();
		$actual_readings
			->label("Date Orare")
			->field("actual_readings");

		$curves_variance = new \Kendo\UI\FormItem();
		$curves_variance
			->label("Variatie Curbe")
			->field("curves_variance");

		$curves = new \Kendo\UI\FormItem();
		$curves
			->label("Curbe")
			->field("curves");
			
		$formItem2->addItem($actual_readings);	
		$formItem2->addItem($curves_variance);	
		$formItem2->addItem($curves);	
		
		$form->addItem($formItemD);
		$form->addItem($formItem1);
		$form->addItem($formItem2);
		
		$form->orientation('horizontal');
		$form->attributes(array("method" => "post", "autocomplete"=>"off", "action" => ""));

		
		$tabstrip = new \Kendo\UI\TabStrip('tabstrip');

		$cleanData = new \Kendo\UI\TabStripItem('cleanData');
		$cleanData->text("Sterge Date")
        ->selected(true)
		->enabled(true)
		->content($form->render());
		
				
		// set animation
		$animation = new \Kendo\UI\TabStripAnimation();
		$openAnimation = new \Kendo\UI\TabStripAnimationOpen();
		$openAnimation->effects("fadeIn");
		$animation->open($openAnimation);

		$tabstrip->animation($animation);
		
		$tabstrip
			->collapsible(false)
			->addItem($cleanData);
		
		$this->data['output'] = $tabstrip->render(); 
		$this->data['jsFiles'] = ['tools.js','system.js'];
		
		$data['data'] = $this->data;
		
		return view('system',$data);
	}
}
