<?php include(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>🟢 EBS - <?= $data['title'] ?></title>
	<meta name="description" content="EBS, Electricity billing system">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="shortcut icon" type="image/png" href="/favicon.ico"/>

	<link href="/telerik/css/web/kendo.common.min.css" rel="stylesheet" />
	<link href="/telerik/css/web/kendo.rtl.min.css" rel="stylesheet" />
    <link href="/telerik/css/web/kendo.bootstrap.min.css" rel="stylesheet" />
    
	
	<link rel="stylesheet" type="text/css" href="/css/ebs.css">

	<!-- CSS only -->
	<!-- ORIGINAL SOURCES
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
	<script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.5/dist/js.cookie.min.js"></script>-->
	
	<link href="/extern/5.2.0-beta1-bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
	<script src="/extern/3.5.1.jquery.min.js"></script>
	<script src="/extern/1.16.0.popper.min.js"></script>
	<script src="/extern/5.2.0-beta1-bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="/extern/fontawesome/css/all.min.css"/>
	<script src="/extern/js.cookie.3.0.5.min.js"></script>

	<script src="/telerik/js/jquery.min.js"></script>
	<script src="/telerik/js/kendo.all.min.js"></script>
	<script src="/telerik/js/cultures/kendo.culture.ro-RO.min.js"></script>
	<script src="/telerik/js/messages/kendo.messages.ro-RO.min.js"></script>
	<script src="/telerik/js/kendo.timezones.min.js"></script>

<header>

	<nav class="navbar navbar-expand-lg navbar-light  bg-light fixed-top shadow-sm">
		
	  <div class="collapse navbar-collapse" id="navbarNavDropdown">
		<ul class="navbar-nav">
		  <li class="nav-item align-self-center">
		  	 <a class="border rounded ms-1 me-1 bg-white text-nowrap" href="<?php echo site_url();?>">
				<i class="fa-solid fa-power-off fa-lg me-1 text-success"></i><i class="fa-solid fa-e fa-xl text-success"></i><i class="fa-solid fa-b fa-xl text-success"></i><i class="fa-solid fa-s fa-xl text-success" style="margin-left:-2px;"></i>
			 </a>
		  </li> 
		  <li class="nav-item active">
			<div class="btn-group">		
			  <button type="button" class="btn btn-light border me-1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span><i class="fa-solid fa-bars fa-xl"></i></span>
			  </button>				
			  <ul class="dropdown-menu">
				<li><a class="dropdown-item" href="<?php echo site_url('home/system')?>">System</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item" href="#" onclick="window.open('http://192.168.1.92:8501/','_blank').focus();return false;">Energy View</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item" href="#" onclick="window.open('https://inais.atlassian.net/servicedesk/customer/portal/1/article/191299608','_blank').focus();return false;">Knowledge Base</a></li>
				<li><a class="dropdown-item" href="#" onclick="window.open('https://inais.atlassian.net/servicedesk/customer/portal/1/group/7/create/20','_blank').focus();return false;">Creaza Ticket</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item" href="http://192.168.1.102/admin/mycpanel">Prognoza</a></li>
				<li><a class="dropdown-item" href="#" onclick="window.open('http://192.168.1.102/test/test1','_blank').focus();return false;">Prognoza Test 1</a></li>
				<li><a class="dropdown-item" href="#" onclick="window.open('http://192.168.1.102/test/test6','_blank').focus();return false;">Prognoza Test 6</a></li>
			  </ul>
			</div>
		  </li>
		  <li class="nav-item pe-1">
			<div class="btn-group">
			  <button type="button" class="btn btn-danger dropdown-toggle tmenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span><i class="fa-solid fa-cube fa-lg me-2"></i></span>Master Data
			  </button>
			  <ul class="dropdown-menu">
				<li><a class="dropdown-item it-md" href="<?php echo site_url('ebsMD/customers_management')?>">Clienti</a></li>
				<li><a class="dropdown-item it-md" href="<?php echo site_url('ebsMD/contracts_management')?>">Contracte</a></li>
				<li><a class="dropdown-item it-md" href="<?php echo site_url('ebsMD/zones_management')?>">Loturi Clienti</a></li>
				<li><a class="dropdown-item it-md" href="<?php echo site_url('ebsMD/pods_management')?>">POD-uri</a></li>
				<li><a class="dropdown-item it-md" href="<?php echo site_url('ebsMD/service_prices')?>">Preturi</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item it-md" href="<?php echo site_url('ebsMD/service_tariffs')?>">Tarife</a></li>
				<li><a class="dropdown-item it-md" href="<?php echo site_url('ebsMD/services_management')?>">Servicii</a></li>				
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item it-md" href="<?php echo site_url('ebsMD/distributors_management')?>">Distribuitori</a></li>
				<li><a class="dropdown-item it-md" href="<?php echo site_url('ebsMD/suppliers_management')?>">Furnizori</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item it-md" href="<?php echo site_url('ebsMD/working_days')?>">Zile Lucratoare</a></li>
				<li><a class="dropdown-item it-md" href="<?php echo site_url('ebsMD/naming_management')?>">Denumiri Furnizori</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item it-md" href="<?php echo site_url('ebs/settings_management')?>">Setari</a></li>
				<li><a class="dropdown-item it-md" href="<?php echo site_url('ebs/users')?>">Utilizatori</a></li>
			  </ul>
			</div>
			<hr class="mx-2 mt-1 mb-0 border-danger border border-3 d-none" id='masterdata'>
		  </li>
		  <li class="nav-item pe-1">
			<div class="btn-group">
			  <button type="button" class="btn btn-warning dropdown-toggle tmenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span><i class="fa-solid fa-bolt fa-lg me-2"></i></span>Achizitii Vanzare
			  </button>
			  <ul class="dropdown-menu">
				<li><a class="dropdown-item it-av" href="<?php echo site_url('buysell/buy')?>">Achizitii</a></li>
				<li><a class="dropdown-item it-av" href="<?php echo site_url('buysell/sell')?>">Vanzare</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item it-av" href="<?php echo site_url('buysell/companies')?>">Companii</a></li>
			  </ul>
			</div>
			<hr class="mx-2 mt-1 mb-0 border-warning border border-3 d-none" id='achizitiivanzare'>
		  </li>
		  <li class="nav-item pe-1">
		  	<div class="btn-group">
			  <button type="button" class="btn btn-primary dropdown-toggle tmenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span><i class="fa-solid fa-circle-dot fa-lg me-2"></i></span>Consumuri
			  </button>
			  <ul class="dropdown-menu">
				<li><a class="dropdown-item it-c" href="<?php echo site_url('ImportConsumptions')?>">Import Consumuri</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item it-c" href="<?php echo site_url('consumptions')?>">Editare Consumuri</a></li>
				<!--<li><a class="dropdown-item it-c" href="<?php echo site_url('ebs/view_consumed_services')?>">Raport Cantitati</a></li>-->
			  </ul>
			</div>
			<hr class="mx-2 mt-1 mb-0 border-primary border border-3 d-none" id='consumuri'>
		  </li>
		  <li class="nav-item pe-1">
		  	<div class="btn-group">
			  <button type="button" class="btn btn-success dropdown-toggle tmenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span><i class="fa-solid fa-file-invoice fa-lg me-2"></i></span>Facturi
			  </button>
			  <ul class="dropdown-menu">
				<li><a class="dropdown-item it-f" id="newInvoice" href="<?php echo site_url('Invoices#Create')?>">Adauga Factura</a></li>
				<li><a class="dropdown-item it-f" href="<?php echo site_url('BillEverything')?>">Factureaza Tot</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item it-f" href="<?php echo site_url('Invoices')?>">Facturi</a></li>
			  	<!--<li><a class="dropdown-item it-f" href="<?php echo site_url('InvoicesOld/')?>">Lista Facturi</a></li>-->
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item it-f" href="<?php echo site_url('Invoices/to_be_invoiced')?>">Energie Nefacturata</a></li>			
			  </ul>
			</div>
			<hr class="mx-2 mt-1 mb-0 border-success border border-3 d-none" id='facturi'>			
		  </li>
		  <li class="nav-item pe-1">
			<div class="btn-group">
			  <button type="button" class="btn btn-dark dropdown-toggle tmenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span><i class="fa-solid fa-tornado fa-lg me-2"></i></span>Prognoza
			  </button>
			  <ul class="dropdown-menu forecast-menu">
			    <div class="row">
				<div class="col  ps-4 pe-4">
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDPrognoza/Synthetics')?>">Sintetice</a></li>
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDPrognoza/CustomersProfile')?>">Profil Consumatori</a></li>
					<li><a class="dropdown-item it-md" href="<?php echo site_url('prognoza/MDPrognoza/CustomerFreeDays')?>">Zile Libere</a></li>
					<li><hr class="dropdown-divider"></li>
					<li><a class="dropdown-item it-md" href="<?php echo site_url('prognoza/MDPrognoza/CustomersFreeDaysReport')?>">Verifica Zile Libere</a></li>
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDPrognoza/Profiles')?>">Consum Realizat</a></li>				
				</div>
				<div class="col pe-4">
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDPrognoza/Temperatures')?>">Temperaturi</a></li>
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDPrognoza/Weather')?>">Prognoza Meteo</a></li>
					<li><hr class="dropdown-divider"></li>
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/Prognoza/Estimare')?>">Estimare</a></li>
					<li><hr class="dropdown-divider"></li>
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDPrognoza/PrognosisReport')?>">Raport Prognoza</a></li>
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDPrognoza/PodData')?>">POD-uri lipsa</a></li>
					<li><hr class="dropdown-divider"></li>
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDPrognoza/Intervals')?>">Intervale Orare</a></li>
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDPrognoza/Months')?>">Corelare Luni</a></li>
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDPrognoza/ForecastSettings')?>">Setari Prognoza</a></li>
				</div>
				<div class="col pe-4">
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDProcast/ProductionForecast')?>">Productie</a></li>
					<li><hr class="dropdown-divider"></li>
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDProcast/TypicalProduction')?>">Productie Caracteristica</a></li>
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDProcast/MinProduction')?>">Productie Minima</a></li>
					<li><hr class="dropdown-divider"></li>				
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDProcast/AssignRegions')?>">Alocare Regiuni</a></li>
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDProcast/Regions')?>">Setari Regiuni</a></li>
					<li><hr class="dropdown-divider"></li>
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDProcast/Transelectrica')?>">Transelectrica</a></li>
					<li><a class="dropdown-item" href="<?php echo site_url('prognoza/MDProcast/PISEN')?>">Pi SEN</a></li>
				</div>
				</div>
			  </ul>
			</div>
			<hr class="mx-2 mt-1 mb-0 border-dark border border-3 d-none" id='prognoza'>
		  </li>
		  <li class="nav-item pe-1">
			<div class="btn-group">
			  <button type="button" class="btn btn-secondary dropdown-toggle tmenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span><i class="fa-solid fa-check-double me-2"></i></span>Realizat
			  </button>
			  <ul class="dropdown-menu forecast-menu">
				<div class="row">
				<div class="col  ps-4 pe-4">
					<li><a class="dropdown-item" href="<?php echo site_url('realizat/ImportReadings')?>">Import Date Orare</a></li>
					<li><hr class="dropdown-divider"></li>
					<li><a class="dropdown-item" href="<?php echo site_url('realizat/MDRealizat/ExportRaportOrar?type_filer=0')?>">Raport Orar</a></li>
					<li><a class="dropdown-item" href="<?php echo site_url('realizat/MDRealizat/Readings')?>">Date Orare</a></li>
					<li><a class="dropdown-item" href="<?php echo site_url('realizat/MDRealizat/VariatiiCurbe')?>">Variatii Curbe</a></li>
					<li><hr class="dropdown-divider"></li>
					<li><a class="dropdown-item" href="<?php echo site_url('realizat/MDRealizat/ProfilesCorrelation')?>">Corelare Profiluri Sintetice</a></li>
					<li><a class="dropdown-item" href="<?php echo site_url('realizat/MDRealizat/Curves')?>">Curbe Consum</a></li>
				</div>
				<div class="col pe-4">
					<li><a class="dropdown-item" href="<?php echo site_url('realizat/ImportDailyReadings')?>">Import Realizat Zilnic</a></li>
					<li><hr class="dropdown-divider"></li>
					<li><a class="dropdown-item" href="<?php echo site_url('realizat/MDRealizat/RaportRealizatZilnic')?>">Raport Realizat Zilnic</a></li>
				</div>
				</div>
			  </ul>
			</div>
			<hr class="mx-2 mt-1 mb-0 border-secondary border border-3 d-none" id='realizat'>
		  </li>
		  <li class="nav-item pe-1">
		  	<div class="btn-group">
			  <button type="button" class="btn btn-info text-dark dropdown-toggle tmenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span><i class="fa-solid fa-chart-pie me-2"></i></span>Rapoarte
			  </button>
			  <ul class="dropdown-menu">
				<li><a class="dropdown-item it-r" href="<?php echo site_url('ebsMD/raport?view=view_raport_abacus')?>">Contabilitate</a></li>	
				<li><a class="dropdown-item it-r" href="<?php echo site_url('ebsMD/raport?view=view_raport_distribuitori')?>">Raport Distribuitori</a></li>	
				<li><a class="dropdown-item it-r" href="<?php echo site_url('export')?>">Raport ANRE</a></li>
				<li><a class="dropdown-item it-r" href="<?php echo site_url('export/raport_consumlunar')?>">Raport Consum Lunar</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item it-r" href="<?php echo site_url('buysell/pandl')?>">Dezechilibru</a></li>
				<li><a class="dropdown-item it-r" href="<?php echo site_url('buysell/monthly')?>">Balanta Lunara</a></li>	
				<!--<a class="dropdown-item" href="<?php echo site_url('ebs/raport?view=view_raport_anre_1')?>">Raport ANRE 1</a>					
				<a class="dropdown-item" href="<?php echo site_url('ebs/raport?view=view_raport_anre_2')?>">Raport ANRE 2</a>-->
			  </ul>
			</div>
			<hr class="mx-2 mt-1 mb-0 border-primary border border-3 d-none" id='rapoarte'>			
		  </li>
		  <li class="nav-item pe-1">
		  	<div class="btn-group">
			  <button id="suppliers_selection" type="button" class="btn btn-light text-dark dropdown-toggle border-dark fw-bold text-truncate tmenu" style="max-width:100px" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span><i class="fa-solid fa-plug-circle-bolt me-2"></i></span>Furnizori
			  </button>
			  <ul class="dropdown-menu">
				<li><a class="dropdown-item it-r" href="#" onclick="setSupplier(1,'Hermes Energy');">Hermes Energy</a></li>	
				<!--<li><a class="dropdown-item it-r" href="#" onclick="setSupplier(4,'Conarg');">Conarg</a></li>-->	
			  </ul>
			</div>
			<hr class="mx-2 mt-1 mb-0 border-primary border border-3 d-none" id='rapoarte'>			
		  </li>
		  <li class="nav-item ms-2">
		  <a class="btn btn-outline-dark" href="<?php echo site_url('?logout=true')?>" role="button"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
		  </li>
		
		</ul>
	  </div>
		
	  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	  </button>
	</nav>

</header>

</head>
<body class="mt-5 pt-3">

<script>
var testMode = false;

var supplierID = 1;
$("#<?php echo $data['menu']?>").toggleClass("d-none");

function setSupplier(id,name)
{
	var data = {
	models: [
			 {
				sessionVar :'select-supplier',
				value : id
			 }
			]
	};
	
	var jqxhr = $.post({
						url: window.location.origin+'/api?subject=custom&type=call&action=setSessionVar',
						data: JSON.stringify(data),
						contentType: 'application/json; charset=utf-8'})
				.done(function(response) {
					if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
					
						$('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');
						return false;
					}
					else
					{
						supplierID = id;
						$("#suppliers_selection").text(name);
					}
				})
				.fail(function() {
					
					kendo.ui.progress($(document.body), false);
					
					//probleme de retea
					$('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
					return false;
				});
				
}

function getSupplier(id)
{
	var data = {
	models: [
			 {
				sessionVar :'select-supplier',
				supplierID : supplierID
			 }
			]
	};
	
	var jqxhr = $.post({
						url: window.location.origin+'/api?subject=custom&type=call&action=getSessionVar',
						data: JSON.stringify(data),
						contentType: 'application/json; charset=utf-8'})
				.done(function(response) {
					if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
					
						$('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');
						return false;
					}
					else
					{
						if (supplierID != response['select-supplier']) 
						{
							supplierID = 0;
							window.location.href = window.location.origin;
						}

					}
				})
				.fail(function() {
					
					kendo.ui.progress($(document.body), false);
					
					//probleme de retea
					$('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
					return false;
				});
				
}

</script>

<div class="card m-2">
  <div class="card-header d-flex justify-content-between  <?php if(isset($data['div-card'])) echo $data['div-card']; ?>">
	<div class="d-flex">
		<div class="pe-3">
		<?php
			$titleClass = isset($data['title_class']) ? ' class="'.$data['title_class'].'"' : '';
			if (isset($data['title_link'])) echo '<h5'.$titleClass.'><a href="'.$data['title_link'].'">'.$data['title'].'</a></h5>';
			else echo '<h5'.$titleClass.'>'. $data['title'] .'</h5>';
		?>
		</div>
		<div class="card-options">
			<?php 
				if(!empty($data['card'])) require_once($data['card']);
				elseif($data['title'] == 'Raport ANRE') require_once('export-card.php');
				elseif($data['title'] == 'Raport Consum Lunar') require_once('raport_consumlunar-card.php');
				elseif($data['title'] == 'Factureaza tot') require_once('bill_everything-card.php');
				elseif(in_array($data['title'],['Preturi','Clienti','Loturi Clienti','POD-uri','Contracte'])) 
				{
					require_once(APPPATH . 'Libraries/ebs/CustomerFilter.php');
					$customerFilter = new CustomerFilter();
					echo $customerFilter->render();
				}
				elseif($data['title'] == 'Factura No.') 
				{
					require_once(APPPATH . 'Libraries/ebs/InvoiceNoFilter.php');
					$invoiceFilter = new InvoiceNoFilter();
					echo $invoiceFilter->render();
				}
				elseif( $data['title'] == 'Import Date Orare')	require_once('realizat/import_readings-header.php');
				elseif( $data['title'] == 'Raport Realizat Orar')	require_once('realizat/export_orar-header.php');
				elseif( $data['title'] == 'Raport Realizat Zilnic')	require_once('realizat/daily_readings-header.php');
				elseif( $data['title'] == 'Importa Consumuri')	require_once('import_consumptions-header.php');
				elseif( $data['title'] == 'Temperaturi')	require_once('prognoza/temperaturi-header.php');
				elseif( $data['title'] == 'Intervale Orare')	require_once('prognoza/intervals-header.php');
				elseif( $data['title'] == 'Sintetice')	require_once('prognoza/synthetics-header.php');
				elseif( $data['title'] == 'Estimare')	require_once('prognoza/estimates-header.php');
				elseif( $data['title'] == 'Date Orare / Profile Consum')	require_once('prognoza/profiles-header.php');
				elseif( $data['title'] == 'Productie Minima')	require_once('prognoza/production-min.php');
				elseif( $data['title'] == 'Productie Caracteristica')	require_once('prognoza/production_typical-header.php');
				elseif( $data['title'] == 'Productie Estimata')	require_once('prognoza/production_estimated-header.php');
				elseif( $data['title'] == 'Transelectrica')	require_once('prognoza/transelectrica-header.php');
				elseif( $data['title'] == 'Prognoza Meteo')	require_once('prognoza/meteo-header.php');
				elseif( $data['title'] == 'Puterea Instalata SEN')	require_once('prognoza/pisen-header.php');
			?>
		</div>
	</div>
	<div>
		<a data-bs-toggle="collapse" href="#collapseCard" role="button" aria-expanded="true" aria-controls="collapseCard">
			<i class="fa-solid fa-angle-up" id="collapseIcon"></i>
		</a>
	</div>
  </div>
  <script>
  
  function checkLogin()
  {
	  if(supplierID !=0) getSupplier(supplierID);
  }
  
  setInterval(checkLogin, 60000);
  
  $( document ).ready(function() {
	  
	  if(location.origin.includes('cloudromania'))
	  {
		  $('.navbar').removeClass('bg-light').addClass('bg-danger bg-gradient').css('--bs-bg-opacity','.3');
		  testMode = true;
	  }
	  
	  //if(supplierID == 0) 
		  setSupplier(1,'Hermes Energy');
      
	  const myCollapsible = $('#collapseCard');
	  
	  if (myCollapsible.children().length == 0)
	  {
		  $("#collapseIcon").hide();
	  }
	  else
	  {
		myCollapsible.on('hidden.bs.collapse', event => {
		  $("#collapseIcon").toggleClass("fa-angle-up");
		  $("#collapseIcon").toggleClass("fa-angle-down");
		  
		  resizeGrid();
		  resizeExcel();
		});

		myCollapsible.on('shown.bs.collapse', event => {
		  $("#collapseIcon").toggleClass("fa-angle-up");
		  $("#collapseIcon").toggleClass("fa-angle-down");
		  
		  resizeGrid();
		  resizeExcel();
		});	  
	  }
});

  </script>
	<div class="collapse show" id="collapseCard">
	  <?php 
	  
	  switch($data['title'])
	  {
		/*==========FACTURAT=============*/
		case 'Facturi':
			require_once('invoices-card.php');
		break;
		case 'Factura No.':
			require_once('invoiced_items-card.php');
		break;		
		case 'Importa Consumuri':
			require_once('import_consumptions-card.php');			
		break;
		case 'Consumuri':
			require_once('consumptions-card.php');			
		break;
		case 'Servicii':
			require_once('services-card.php');
		break;
		case 'Tarife':
		case 'Preturi':
			require_once('service_rates-card.php');
		break;
		case 'Clienti':
			require_once('customers-card.php');
		break;
		case 'Loturi Clienti':
			require_once('zones-card.php');
		break;
		case 'POD-uri':
			require_once('pods-card.php');
		break;
		case 'Contracte':
			require_once('contracts-card.php');
		break;
		/*=========VANZARE-CUMPARARE========*/
		case 'Vanzare EA':
		case 'Cumparare EA':
			require_once('ach_buysell-card.php');			
		break;
		/*==========REALIZAT=============*/
		case 'Import Date Orare':
			require_once('realizat/import_readings-card.php');
		break;
		case 'Import Realizat Zilnic':
			require_once('realizat/import_daily_readings-card.php');
		break;
		case 'Curbe Consum':
			require_once('realizat/curves-card.php');
		break;
		case 'Variatie Curbe de Consum':
			require_once('realizat/variatie_curbe-card.php');
		break;
		case 'Raport Realizat Orar':
			require_once('realizat/export_orar-card.php');
		break;
		case 'Raport Realizat Zilnic':
			require_once('realizat/daily_readings-card.php');
		break;
		case 'Date Orare':
			require_once('realizat/readings-card.php');
		break;
		case 'Raport Verificare Diferente (facturat - realizat)':
			require_once('realizat/raport_verificare-card.php');
		break;
		/*==========PROGNOZA=============*/
		case 'Zile Libere':
			require_once('prognoza/zilelibere-card.php');
		break;
		case 'Verifica Zile Libere':
			require_once('prognoza/verifica-zilelibere-card.php');
		break;
		case 'Profil Consumatori':
			require_once('prognoza/customers-profiles-report-card.php');
		break;
		case 'Temperaturi':
			require_once('prognoza/temperaturi-card.php');
		break;
		case 'Prognoza Meteo':
			require_once('prognoza/meteo-card.php');
		break;
		case 'Sintetice':
			require_once('prognoza/synthetics-card.php');
		break;
		case 'Estimare':
			require_once('prognoza/estimates-card.php');
		break;
		case 'Raport Prognoza':
			require_once('prognoza/prognosis_report-card.php');
		break;
		case 'Raport Date POD':
			require_once('prognoza/poddata_report-card.php');
		break;
		case 'Date Orare / Profile Consum':
			require_once('prognoza/profiles-card.php');
		break;
		case 'Productie Estimata':
			require_once('prognoza/production_estimated-card.php');
		break;
		case 'Productie Caracteristica':
			require_once('prognoza/production_typical-card.php');
		break;
		case 'Transelectrica':
			require_once('prognoza/transelectrica-card.php');
		break;
		case 'Puterea Instalata SEN':
			require_once('prognoza/pisen-card.php');
		break;
		}
	  ?>
	 </div>
	<div>
		<?php
			$staticNotification = new \Kendo\UI\Notification('staticNotification');
			$staticNotification->autoHideAfter(10000);
			echo $staticNotification->render();
		?>
	</div>
</div>

<?php
if(isset($data['jsFiles']) && count($data['jsFiles']) > 0)
{
	foreach($data['jsFiles'] as $jsFile)
	{
		echo("<script src='/js/$jsFile?".filemtime($_SERVER['DOCUMENT_ROOT']."/js/$jsFile") ."'></script>\n");
	}
}
?>


