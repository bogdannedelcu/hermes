<?php

namespace App\Controllers;

require_once('tools.php');
require_once('GridTools.php');
require_once(APPPATH . 'Libraries/ebs/TimePeriodFilter.php');

class Buysell extends BaseController
{
	use \GridTools;
	private $data = [
        'title'   => '',
		'output' => '',
		'div-card' => 'card-buysell',
		'menu' => 'achizitiivanzare'];
	
	private $buysellModel;
	
	function __construct() {	
		checkAuth();
	}
	
	public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {

        parent::initController($request, $response, $logger);

        // Load the model
        $this->buysellModel = new \App\Models\BuysellModel();
    }
	
	public function index()
	{
		$session = \Config\Services::session();
		if ($session->get('user') === NULL) return view('login.php'); 
				
		$data['data'] = $this->data;
				
		return view('buysell',$data);
	}
	
	public function companies()
	{
		$transport = $this->createGridTransport('companies');

		$schema = $this->createGridSchema(['company_id','company_name','company_order'],['number','string','number']);

		$sortItem = $this->createSortItem('company_order','asc');
		$dataSource = $this->createDataSource($transport, $schema);
		$dataSource->addSortItem($sortItem);

		$column1 = $this->createGridColumn('company_name','Companie',150);

		$column2 = $this->createGridColumn('company_order','Ordinea de afisare',150);
		
		
		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true)
			  ->dataBound('function(e) {
										$("#grid tbody tr .k-grid-delete").each(function () {
											var currentDataItem = $("#grid").data("kendoGrid").dataItem($(this).closest("tr"));
									 
											//Check in the current dataItem if the row is editable
											if (currentDataItem.deletable == false) {
												$(this).remove();
											}
										});
									}')
			 ->save('function(e) {
					  e.sender.one("dataBound", function() {
						e.sender.dataSource.read();
					  });
					  }');
		$this->setGridEditable('inline', true, true, true, false, false);	

		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		

		$this->data['title'] = 'Companii';
		$this->data['output'] = $this->grid->render();

		$data['data'] = $this->data;
				
		return view('buysell',$data);
	}

	public function buy()
	{
		$suppliers = $this->buysellModel->getSuppliersArray();
		$companies = $this->buysellModel->getCompaniesArray();
		
		$transport = $this->createGridTransport('purchases');

		$schema = $this->createGridSchema(['transaction_id','supplier_id','seller_id','date_start','date_end','quantity','price','value'],
										  ['number','string','string','date','date','number','number','number'],
										  ['supplier_id'=>$suppliers[0]['value'],'date_start' =>(new \DateTime())->format('Y-m-01'),'date_end' =>(new \DateTime())->format('Y-m-t'), 'seller_id'=>$companies[0]['value'],'quantity'=>0,'price'=>0],
										  ['value']);
		
		

		
		$tCount = $this->createAggregateItem("date_start","count");
		$tValue = $this->createAggregateItem("value","sum");
		$tQuantity = $this->createAggregateItem("quantity","sum");
		
		$dS = $this->buysellModel->get_buysell_min_max_years('purchases');
		$filterItem = $this->createNowFilter('date_start', false, date('Y'));
		
		$dataSource = $this->createDataSource($transport,$schema);
		$dataSource->addFilterItem($filterItem);
		$dataSource->addAggregateItem($tCount);
		$dataSource->addAggregateItem($tValue);
		$dataSource->addAggregateItem($tQuantity);

		$group = new \Kendo\Data\DataSourceGroupItem();
		$group->field('date_start')
				->addAggregate($tCount)
				->addAggregate($tValue)
				->addAggregate($tQuantity);
		$dataSource->addGroupItem($group);

		$column1 = $this->createGridColumn('supplier_id','Cumparator',150);
		$column1->values($suppliers);

		$column2 = $this->createGridColumn('seller_id','Vanzator',150);
		$column2->values($companies);

		$column3 = $this->createGridColumn('date_start','Data Start',100);
		$column3->sortable(false);
		$column3->footerTemplate('#=count# tranzactii');
		$column3->aggregates('count');
		$column3->groupHeaderTemplate(
			"#=kendo.format('{0:MMMM yyyy}', data.value)# " .
			"&nbsp;<span class='text-success'>#=kendo.format('{0:N3}', aggregates.quantity.sum)# MWh</span> " .
			"&nbsp;<span class='text-primary'>#=kendo.format('{0:N0}', aggregates.value.sum)# Lei</span> " .
			"&nbsp;<span class='text-muted'>#=aggregates.date_start.count# tranzactii</span>" .
			"&nbsp;<button class='k-button k-button-sm btn-copy-month' data-val='#=kendo.format(\"{0:yyyy-MM-dd}\", data.value)#' onclick='return false;'>+</button>"
		);
		$column4 = $this->createGridColumn('date_end','Data Stop',80);						

		$column5 = $this->createGridColumn('quantity','Cantitate EA [MWh]',100);
		$column5->template("#=kendo.format('{0:N3}',data.quantity)#");
		$column5->footerTemplate("#=kendo.format('{0:N3}',(sum ? sum : 0))# MWh");
		$column5->aggregates('sum');
		
		$column6 = $this->createGridColumn('price','Pret EA [lei]',100);
		$column6->template("#=kendo.toString(data.price)#");
		
		$column7 = $this->createGridColumn('value','Valoare EA [Lei]',100);
		$column7->template("#=kendo.format('{0:N2}',quantity * price)#");
		$column7->footerTemplate("#=kendo.format('{0:N2}',(sum ? sum : 0))# lei");
		$column7->aggregates('sum');
		
		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true)
			 ->edit('function(e){	if(e.model.isNew() && selYear_date_start > -1 && selMonth_date_start > -1)
													{
														let y = selYear_date_start;
														let m = selMonth_date_start  + 1;
														let d = new Date(y, m, 0);
														e.model.set("date_start", y+"-"+m+"-1");
														e.model.set("date_end", y+"-"+m+"-"+d.getDate());
													}
													
									$("#quantity").data("kendoNumericTextBox").options.decimals=3;
									$("#quantity").data("kendoNumericTextBox").options.format="#.###";
									$("#quantity").data("kendoNumericTextBox").value(e.model.quantity);
									$("#price").data("kendoNumericTextBox").options.decimals=8;
									$("#price").data("kendoNumericTextBox").options.format="#.########";
									$("#price").data("kendoNumericTextBox").value(e.model.price);
									
									$("#seller_id").data("kendoDropDownList").setOptions({optionLabel:"Selecteaza vanzator...",filter:"contains",height:400});
								}');
				 
		$this->setGridEditable('inline', true, true, true, false, false);
				

		$this->data['title'] = 'Cumparare EA';
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		$SDFilter = new \TimePeriodFilter('date_start','purchases','Data Cumparare',$dS['minY'],date('Y'));
		
		$this->data['output'] = $this->grid->render();
		$this->data['date_start_filter'] = $SDFilter->render();

		$this->data['gridJS'] .= <<<'COPYJS'
<script>
(function() {
    var months = [
        {v:1,t:"Ianuarie"},{v:2,t:"Februarie"},{v:3,t:"Martie"},
        {v:4,t:"Aprilie"},{v:5,t:"Mai"},{v:6,t:"Iunie"},
        {v:7,t:"Iulie"},{v:8,t:"August"},{v:9,t:"Septembrie"},
        {v:10,t:"Octombrie"},{v:11,t:"Noiembrie"},{v:12,t:"Decembrie"}
    ];

    function buildSelect(id, sel) {
        return '<select id="'+id+'" class="k-input" style="flex:1">' +
            months.map(function(m) {
                return '<option value="'+m.v+'"'+(m.v===sel?' selected':'')+'>'+m.t+'</option>';
            }).join('') + '</select>';
    }

    $(document).on('click', '.btn-copy-month', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var raw = ($(this).data('val') || '').toString();
        var mt  = raw.match(/(\d{4})-(\d{2})/);
        if (!mt) { alert('Nu s-a putut determina luna (val=' + raw + ').'); return; }

        var dy = parseInt(mt[1]);
        var dm = parseInt(mt[2]);
        var sy = dm === 1 ? dy - 1 : dy;
        var sm = dm === 1 ? 12 : dm - 1;

        if ($('#copyMonthWin').length === 0) {
            $('<div id="copyMonthWin"></div>').appendTo('body').kendoWindow({
                title: 'Copiaza luna', modal: true, visible: false, width: 370
            });
        }

        var html =
            '<div style="padding:16px">' +
            '<p><b>Copiaza DIN luna:</b></p>' +
            '<div style="display:flex;gap:8px;margin-bottom:14px">' +
            buildSelect('cpSrcMonth', sm) +
            '<input type="number" id="cpSrcYear" value="'+sy+'" min="2015" max="2035" style="width:78px" class="k-input">' +
            '</div>' +
            '<p><b>IN luna (destinatie):</b></p>' +
            '<div style="display:flex;gap:8px;margin-bottom:20px">' +
            buildSelect('cpDstMonth', dm) +
            '<input type="number" id="cpDstYear" value="'+dy+'" min="2015" max="2035" style="width:78px" class="k-input">' +
            '</div>' +
            '<div style="display:flex;gap:8px;justify-content:flex-end">' +
            '<button class="k-button" id="cpBtnCancel">Anuleaza</button>' +
            '<button class="k-button k-primary" id="cpBtnOk">Copiaza</button>' +
            '</div></div>';

        var win = $('#copyMonthWin').data('kendoWindow');
        win.content(html).center().open();

        $('#cpBtnCancel').off('click').on('click', function() { win.close(); });

        $('#cpBtnOk').off('click').on('click', function() {
            var sm2 = parseInt($('#cpSrcMonth').val());
            var sy2 = parseInt($('#cpSrcYear').val());
            var dm2 = parseInt($('#cpDstMonth').val());
            var dy2 = parseInt($('#cpDstYear').val());

            if (!sm2||sm2<1||sm2>12||!sy2||!dm2||dm2<1||dm2>12||!dy2) {
                alert('Completati toate campurile corect.'); return;
            }

            win.close();
            kendo.ui.progress($(document.body), true);

            $.post({
                url: window.location.origin + '/api?subject=custom&type=call&action=copyPreviousMonthPurchases',
                data: JSON.stringify({ models: [{ year: dy2, month: dm2, srcYear: sy2, srcMonth: sm2 }] }),
                contentType: 'application/json; charset=utf-8'
            }).done(function(resp) {
                kendo.ui.progress($(document.body), false);
                var n = $('#staticNotification').data('kendoNotification');
                if (resp && resp.error)        { n.show(resp.error,   'error');   }
                else if (resp && resp.success) { n.show(resp.success, 'success'); $('#grid').data('kendoGrid').dataSource.read(); }
                else                           { n.show(JSON.stringify(resp), 'info'); }
            }).fail(function(xhr) {
                kendo.ui.progress($(document.body), false);
                $('#staticNotification').data('kendoNotification').show('Eroare la comunicarea cu serverul (' + xhr.status + ').', 'error');
            });
        });
    });
})();
</script>
COPYJS;

		$data['data'] = $this->data;

		return view('buysell',$data);
	}

	public function sell()
	{
		$suppliers = $this->buysellModel->getSuppliersArray();
		$companies = $this->buysellModel->getCompaniesArray();
		
		$transport = $this->createGridTransport('sales');

		$schema = $this->createGridSchema(['transaction_id','supplier_id','buyer_id','date_start','date_end','quantity','price','value'],
										  ['number','string','string','date','date','number','number','number'],
										  ['supplier_id'=>$suppliers[0]['value'],'buyer_id'=>$companies[0]['value'],'quantity'=>0,'price'=>0],
										  ['value']);
		
		$tCount = $this->createAggregateItem("date_start","count");
		$tValue = $this->createAggregateItem("value","sum");
		$tQuantity = $this->createAggregateItem("quantity","sum");
		
		$dS = $this->buysellModel->get_buysell_min_max_years('sales');
		$filterItem = $this->createNowFilter('date_start',false,date('Y'));
		
		$dataSource = $this->createDataSource($transport,$schema);		
		$dataSource->addFilterItem($filterItem);
		$dataSource->addAggregateItem($tCount);
		$dataSource->addAggregateItem($tValue);
		$dataSource->addAggregateItem($tQuantity);	

		$group = new \Kendo\Data\DataSourceGroupItem();
		$group->field('buyer_id')
				->addAggregate($tCount)
				->addAggregate($tValue)
				->addAggregate($tQuantity);
		//$dataSource->addGroupItem($group);
		
		$column1 = $this->createGridColumn('supplier_id','Vanzator',150);
		$column1->values($suppliers);
		
		$column2 = $this->createGridColumn('buyer_id','Cumparator',150);
		$column2->values($companies);
		$column2->groupHeaderTemplate("EA:<span class='text-danger'>#=kendo.format('{0:N3}', aggregates.quantity.sum)# MWh</span> 
		<span class='text-success'>#=kendo.format('{0:N0}', aggregates.value.sum)# Lei</span> 
		<span class='text-primary'>#=kendo.format('{0:N0}',aggregates.date_start.count)# tranzactii</span>");
		
		$column3 = $this->createGridColumn('date_start','Data Start',100);
		$column3->footerTemplate('#=count# tranzactii');
		$column3->aggregates('count');
		$column4 = $this->createGridColumn('date_end','Data Stop',80);						

		$column5 = $this->createGridColumn('quantity','Cantitate EA [MWh]',100);
		$column5->template("#=kendo.format('{0:N3}',data.quantity)#");
		$column5->footerTemplate("#=kendo.format('{0:N3}',(sum ? sum : 0))# MWh");
		$column5->aggregates('sum');
		
		$column6 = $this->createGridColumn('price','Pret EA [lei]',100);
		$column6->template("#=kendo.toString(data.price)#");
		
		$column7 = $this->createGridColumn('value','Valoare EA [Lei]',100);
		$column7->template("#=kendo.format('{0:N2}',quantity * price)#");
		$column7->footerTemplate("#=kendo.format('{0:N2}',(sum ? sum : 0))# lei");
		$column7->aggregates('sum');

		$this->grid->height(550)
			 ->dataSource($dataSource)
			 ->resizable(true)
			 ->edit('function(e){	if(e.model.isNew() && selYear_date_start > -1 && selMonth_date_start > -1)
													{
														let y = selYear_date_start;
														let m = selMonth_date_start  + 1;
														let d = new Date(y, m, 0);
														e.model.set("date_start", y+"-"+m+"-1");
														e.model.set("date_end", y+"-"+m+"-"+d.getDate());
													}
									//$("#price").data("kendoNumericTextBox").setOptions({decimals:8});
									$("#quantity").data("kendoNumericTextBox").options.decimals=3;
									$("#quantity").data("kendoNumericTextBox").options.format="#.###";
									$("#quantity").data("kendoNumericTextBox").value(e.model.quantity);
									$("#price").data("kendoNumericTextBox").options.decimals=8;
									$("#price").data("kendoNumericTextBox").options.format="#.########";
									$("#price").data("kendoNumericTextBox").value(e.model.price);

									$("#buyer_id").data("kendoDropDownList").setOptions({optionLabel:"Selecteaza cumparator...",filter:"contains",height:400});
								}');
				 

		$this->setGridEditable('inline', true, true, true, false, false);
		$this->createGridMenu();
		$this->setGridScrollable('infinite');
		
		$SDFilter = new \TimePeriodFilter('date_start','sales','Data Vanzare',$dS['minY'],date('Y'));
		
		$this->data['title'] = 'Vanzare EA';
		$this->data['output'] = $this->grid->render();
		$this->data['date_start_filter'] = $SDFilter->render();
		
		$data['data'] = $this->data;
				
		return view('buysell',$data);
	}
	
	public function monthly()
	{	
		$transport = $this->createGridTransport('view_raport_monthly_data');

		$schema = $this->createGridSchema(['id','date','supplier_name','pQuantity','pPrice','pValue','sQuantity','sPrice','sValue','PL','Diff'],
										  ['number','date','string','number','number','number','number','number','number','number','number'],
										 );
		
		$dataSource = $this->createDataSource($transport,$schema);

		$grid = new \Kendo\UI\Grid('grid');
				
		
		$column1 = new \Kendo\UI\GridColumn();
		$column1->field('supplier_name')
					->title('Furnizor');
	
		$column2 = new \Kendo\UI\GridColumn();
		$column2->field('date')
					->title('Data')
					->template("#=kendo.format('{0:dd/MM/yyyy}',date)#");
					
		$column3 = new \Kendo\UI\GridColumn();
		$column3->field('pQuantity')
					->title('Cantitate EA Cumparata')
					->template("#=kendo.format('{0:N3}',pQuantity)#");

		$column4 = new \Kendo\UI\GridColumn();
		$column4->field('pPrice')
					->title('Pret Cumparare (lei)')
					->template("#=kendo.format('{0:N2}',pPrice)#");
					
		$column5 = new \Kendo\UI\GridColumn();
		$column5->field('pValue')
					->title('Valoare Cumparare (lei)')
					->template("#=kendo.format('{0:N2}',pValue)#");
		
		$column6 = new \Kendo\UI\GridColumn();
		$column6->field('sQuantity')
					->title('Cantitate EA Vanduta')
					->template("#=kendo.format('{0:N3}',sQuantity)#");

		$column7 = new \Kendo\UI\GridColumn();
		$column7->field('sPrice')
					->title('Pret Vanzare (lei)')
					->template("#=kendo.format('{0:N2}',sPrice)#");
					
		$column8 = new \Kendo\UI\GridColumn();
		$column8->field('sValue')
					->title('Valoare vanzare (lei)')
					->template("#=kendo.format('{0:N2}',sValue)#");

		$column9 = new \Kendo\UI\GridColumn();
		$column9->field('PL')
					->title('Balanta')
					->template("#=kendo.format('{0:N2}',PL)#");
					
		$column10 = new \Kendo\UI\GridColumn();
		$column10->field('Diff')
					->title('Diff')
					->template("#=kendo.format('{0:N2}',Diff)#");
					
		$excel = new \Kendo\UI\GridExcel();
		$excel->fileName('Raport.xlsx')
			  ->filterable(true)
			  ->proxyURL(site_url().'/buysell?subject=view_raport_monthly_data&type=save');
			  			
		$grid->addColumn($column1,$column2,$column3,$column4,$column5,$column6,$column7,$column8,$column9,$column10)
			 ->dataSource($dataSource)
			 ->height(550)
			 ->pageable(true)
			 ->sortable(true)
			 ->navigatable(true)
			 ->resizable(true)
			 ->reorderable(true)
			 ->groupable(true)
			 ->filterable(true)
			 ->addToolbarItem(new \Kendo\UI\GridToolbarItem('excel'), new \Kendo\UI\GridToolbarItem('pdf'), new \Kendo\UI\GridToolbarItem('search'))
			 ->excel($excel);

		$this->data['menu']='rapoarte';
		$this->data['title'] = 'Balanta Lunara EA';
		$this->data['output'] = $grid->render();
		$data['data'] = $this->data;
				
		return view('buysell',$data);
	}
	
	public function pandl()
	{
		$transport = $this->createGridTransport('view_raport_dezechilibru');

		$schema = $this->createGridSchema(['id','Data','ZonaLicenta','Consumator','Oras','Tip','Banda','Cantitate','PretEn','CostUnitarMediu','ValoareEnergie','CostEnergie','Balanta'],
										  ['number','date','string','string','string','string','string','number','number','number','number','number','number'],
										 );
		
		$dataSource = new \Kendo\Data\DataSource();


		$sum1 = new \Kendo\Data\DataSourceAggregateItem();
		$sum1->field('Balanta')
			 ->aggregate("sum");
			 
		$dataSource->transport($transport)
				   ->pageSize(10000)
				   ->batch(true)
				   ->schema($schema)
				   ->addAggregateItem($sum1);			 

		$grid = new \Kendo\UI\Grid('grid');
				
			
		$column1 = new \Kendo\UI\GridColumn();
		$column1->field('Data')
					->title('Data')
					->template("#=kendo.format('{0:dd/MM/yyyy}',Data)#")
					->groupHeaderTemplate("#=kendo.format('{0:dd-MM-yyyy}',data.value)#, Balanta: #=kendo.format('{0:N2}', aggregates.Balanta.sum)#");
		
		$column2 = new \Kendo\UI\GridColumn();
		$column2->field('ZonaLicenta')
					->groupHeaderTemplate("#=data.value#, Balanta: #=kendo.format('{0:N2}', aggregates.Balanta.sum)#")
					->title('Zona Licenta');
		
		$column3 = new \Kendo\UI\GridColumn();
		$column3->field('Consumator')
					->groupHeaderTemplate("#=data.value#, Balanta: #=kendo.format('{0:N2}', aggregates.Balanta.sum)#")
					->title('Consumator');

		$column4 = new \Kendo\UI\GridColumn();
		$column4->field('Oras')
					->groupHeaderTemplate("#=data.value#, Balanta: #=kendo.format('{0:N2}', aggregates.Balanta.sum)#")
					->title('Oras');

		$column5 = new \Kendo\UI\GridColumn();
		$column5->field('Tip')
					->groupHeaderTemplate("#=data.value#, Balanta: #=kendo.format('{0:N2}', aggregates.Balanta.sum)#")
					->title('Tip');
		
		$column6 = new \Kendo\UI\GridColumn();
		$column6->field('Banda')
					->groupHeaderTemplate("#=data.value#, Balanta: #=kendo.format('{0:N2}', aggregates.Balanta.sum)#")
					->title('Banda');
		
		$column7 = new \Kendo\UI\GridColumn();
		$column7->field('Cantitate')
					->title('Cantitate EA Consumata')
					->template("#=kendo.format('{0:N3}',Cantitate)#");

		$column8 = new \Kendo\UI\GridColumn();
		$column8->field('PretEn')
					->title('Pret EA Unitar')
					->template("#=kendo.format('{0:N2}',PretEn)#");
					
		$column9 = new \Kendo\UI\GridColumn();
		$column9->field('CostUnitarMediu')
					->title('Cost EA Unitar Mediu')
					->template("#=kendo.format('{0:N2}',CostUnitarMediu)#");
		
		$column10 = new \Kendo\UI\GridColumn();
		$column10->field('ValoareEnergie')
					->aggregates('sum')
					->title('Valoare Energie Consumata')
					->template("#=kendo.format('{0:N2}',ValoareEnergie)#");

		$column11 = new \Kendo\UI\GridColumn();
		$column11->field('CostEnergie')
					->aggregates('sum')
					->title('Cost Energie Consumata')
					->template("#=kendo.format('{0:N2}',CostEnergie)#");
					
		$column12 = new \Kendo\UI\GridColumn();
		$column12->field('Balanta')
					->aggregates('sum')
					->title('Dezechilibru (lei)')
					->template("#=kendo.format('{0:N2}',Balanta)#");
					
		$excel = new \Kendo\UI\GridExcel();
		$excel->fileName('Raport.xlsx')
			  ->filterable(true)
			  ->proxyURL(site_url().'/buysell?subject=view_raport_monthly_data&type=save');
	
		$grid->addColumn($column1,$column2,$column3,$column4,$column5,$column6,$column7,$column8,$column9,$column10,$column11,$column12)
			 ->dataSource($dataSource)
			 ->height(550)
			 ->pageable(true)
			 ->sortable(true)
			 ->navigatable(true)
			 ->resizable(true)
			 ->reorderable(true)
			 ->groupable(true)
			 ->filterable(true)
			 ->addToolbarItem(new \Kendo\UI\GridToolbarItem('excel'), new \Kendo\UI\GridToolbarItem('pdf'), new \Kendo\UI\GridToolbarItem('search'))
			 ->excel($excel);

		$this->data['menu']='rapoarte';
		$this->data['title'] = 'Raport Dezechilibru';
		$this->data['output'] = $grid->render();
		$data['data'] = $this->data;
				
		return view('buysell',$data);
	}
}
