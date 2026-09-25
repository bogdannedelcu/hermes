<?php
include_once(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php');

function dataSource($subject, $field, $distinct=false, $filter = null)
{
	$transport = new \Kendo\Data\DataSourceTransport();

	$read = new \Kendo\Data\DataSourceTransportRead();
	
	$option = '';
	if($distinct)
		$option = 'distinct';
	
	$read->url(site_url().'/api?action=field&subject='.$subject.'&field='.$field.'&type=read&option='.$option)
		 ->contentType('application/json')
		 ->type('POST');

	$transport->read($read)
			  ->parameterMap('function(data) {
				  return kendo.stringify(data);
			   }');

	$schema = new \Kendo\Data\DataSourceSchema();
	$schema->data('data')
		   ->total('total');

	$dataSource = new \Kendo\Data\DataSource();
	
	$dataSource->transport($transport)
			   ->schema($schema)
			   ->serverFiltering(true);
	
	if($filter)
		$dataSource->addFilterItem($filter);
	
	return $dataSource;
}

function serviceDialog()
{
	/* form */
	$form = new \Kendo\UI\Form('cForm');
	$form->formData(array('supplier_name' => 1));
	$form->orientation("horizontal");
	$form->buttonsTemplate("<div style='width:100%;overflow:hidden;'></div>");


	$supplier = new \Kendo\UI\FormItem();
	$supplier
		->label("Furnizor")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('suppliers','supplier_name'),'dataTextField' => 'supplier_name','dataValueField'=>'supplier_id' ,"filter" => "contains", "optionLabel" => "Selectati Furnizor..."))
		->field("supplier_name")
		->validation(array("required" => true));

	$service_name = new \Kendo\UI\FormItem();
	$service_name
		->label("Denumire")
		->editor("TextBox")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("placeholder" => "Denumire Serviciu"))
		->field("service_name")
		->validation(array("required" => true));
	
	$energy_type = new \Kendo\UI\FormItem();
	$energy_type
		->label("Tip Energie")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => ['EA','ERI','ERC','ERI_X3','ERC_X3'], "placeholder" => "Tipul energiei..."))
		->field("energy_type")
		->validation(array("required" => true));

	$voltage_level = new \Kendo\UI\FormItem();
	$voltage_level
		->label("Nivel Tensiune")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => ['JT','MT','IT','Toate'], "placeholder" => "Nivelul tensiunii..."))
		->field("voltage_level")
		->validation(array("required" => true));

	$pod_type = new \Kendo\UI\FormItem();
	$pod_type
		->label("Tip Pod")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => ['Comercial','Necomercial','Toate'], "placeholder" => "Tipul pod-ului..."))
		->field("service_pod_type")
		->validation(array("required" => true));

	$print_order = new \Kendo\UI\FormItem();
	$print_order->label("Ordinea de tiparire")
		->editor("NumericTextBox")
		->field("service_print_order")
		->editorOptions(array("decimals" => 0,"format" => "{0:#}","max"=>9999))
		->validation(array("required" => true));	
	
	$service_code = new \Kendo\UI\FormItem();
	$service_code
		->label("Cod Serviciu")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => ['EA','TAXDISTR','TAXTRANS','TAXOPCOMH','CFV','COG','ACCIZA','ERI','ERC','Other'], "placeholder" => "Cod-ul serviciului..."))
		->field("service_code")
		->validation(array("required" => true));

	$service_mu = new \Kendo\UI\FormItem();
	$service_mu
		->label("UM")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => ['MWh','kVArh','MWh/h'], "placeholder" => "Unitatea de masura..."))
		->field("service_measurment_unit")
		->validation(array("required" => true));

	$service_type = new \Kendo\UI\FormItem();
	$service_type
		->label("Tip Serviciu")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('service_types','service_type'),'dataTextField' => 'service_type','dataValueField'=>'service_type_id' ,"filter" => "contains", "optionLabel" => "Selectati Tip Serviciu..."))
		->field("service_type_id")
		->validation(array("required" => true));
		
	$service_status = new \Kendo\UI\FormItem();
	$service_status
		->label("Status")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => ['Activ','Inactiv'], "placeholder" => "Status..."))
		->field("service_status")
		->validation(array("required" => true));
					
	$form->addItem($supplier);
	$form->addItem($service_name);
	$form->addItem($energy_type);
	$form->addItem($voltage_level);
	$form->addItem($pod_type);
	$form->addItem($print_order);
	$form->addItem($service_code);
	$form->addItem($service_mu);
	$form->addItem($service_type);
	$form->addItem($service_status);
	$form->attributes(array("method" => "post", "autocomplete"=>"off"));

	$dialogContent = $form->render();
	
	
	/*dialog*/
	$dialog = new \Kendo\UI\Dialog('dialog');
		
	$skipAction = new \Kendo\UI\DialogAction();
	$skipAction->text("Anulează");

	$intallAction = new \Kendo\UI\DialogAction();
	$intallAction->text("Adaugă")
				 ->primary(true)
				 ->action(new \Kendo\JavaScriptFunction('function() { return saveService(); }'));
				 
	$dialog->title('Adaugă Lot')
		   ->width('600px')

		   ->closable(true)
		   ->modal(true)
		   ->visible(false)
		   //->show('function(e) {$("#customer_vat_code").data("kendoTextBox").focus();}')
		   //->close('onClose')
		   ->addAction($skipAction, $intallAction)
		   ->content($dialogContent);
	
	echo '
	<script>
		var idServiceID = null;
		
		$( document ).ready(function() {
			$("button.k-grid-addS").bind("click",function(){serviceDialog();})
			$("#select-activ").data("kendoButtonGroup").select(0);
			$("#select-activ").data("kendoButtonGroup").trigger("select");			
		});
		
		function saveService()
		{
				
			//use grid model
			var data = {
				models: [
						 {
							service_id:idServiceID,
							supplier_id :$("#supplier_name").data("kendoDropDownList").value(),
							service_name :$("#service_name").data("kendoTextBox").value(),
							energy_type:$("#energy_type").data("kendoDropDownList").value(),
							voltage_level:$("#voltage_level").data("kendoDropDownList").value(),
							service_pod_type:$("#service_pod_type").data("kendoDropDownList").value(),
							service_print_order:$("#service_print_order").data("kendoNumericTextBox").value(),
							service_code:$("#service_code").data("kendoDropDownList").value(),
							service_measurment_unit:$("#service_measurment_unit").data("kendoDropDownList").value(),
							service_type_id:$("#service_type_id").data("kendoDropDownList").value(),
							service_status:$("#service_status").data("kendoDropDownList").value()
						 }
						]
			};
			
			let stype = "create";
			if (idServiceID !== null) stype = "update";
				
			
			var jqxhr = $.post({
					url: window.location.origin+"/api?subject=services&type="+stype,
					data: JSON.stringify(data),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

					$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
					return false;
				}
				else
				{
					$("#grid").data("kendoGrid").dataSource.read();
					$("#dialog").data("kendoDialog").close();
					
					if (typeof getBadges_active === "function")
						getBadges_active();
				}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				return false;
			});
			
			return false;
		}
		
		function setServiceStatus(id)
		{
			var data = {
					models: [
							 {
								service_id:id,
								service_status:"Inactiv"
							 }
							]
					};
									
			var jqxhr = $.post({
					url: window.location.origin+"/api?subject=custom&type=call&action=setServiceStatus",
					data: JSON.stringify(data),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

				$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
				return false;
			}
			else
			{
				let d = $("#grid").data("kendoGrid").dataSource._data;
				for(i=0;i<d.length;i++)
				{
					if (d[i].service_id == id)
					{
						$("[data-uid="+d[i].uid+"] > td.k-command-cell > button.k-grid-status").hide();
						$("[data-uid="+d[i].uid+"] > td.k-command-cell > button.k-grid-editC").addClass("ms-0");
						$("[data-uid="+d[i].uid+"] > td")[10].innerText= "Inactiv";
						
						d[i].service_status = "Inactiv";
						$("#staticNotification").data("kendoNotification").show(d[i].service_name + " a fost deactivat.", "info");
						break;
					}
				}
				
				if (typeof getBadges_active === "function")
					getBadges_active();
							
			}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				return false;
			});
			
			return false;
		}
		
		function serviceDialog(e = null)
		{				
			//reset
			idServiceID = e;
			$("#supplier_name").data("kendoDropDownList").enable(true);
			$("#service_type_id").data("kendoDropDownList").enable(true);
			$("#supplier_name").data("kendoDropDownList").enable(true);
			$("#service_pod_type").data("kendoDropDownList").enable(true);
			$("#voltage_level").data("kendoDropDownList").enable(true);
			$("#energy_type").data("kendoDropDownList").enable(true);
			$("#service_code").data("kendoDropDownList").enable(true);	
			
			if(e == null)
			{
				$("#dialog").data("kendoDialog").title("Adaugă Servciu");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Adaugă");
		
				$("#service_measurment_unit").data("kendoDropDownList").value("MWh/h");
				$("#service_pod_type").data("kendoDropDownList").value("Toate");
				$("#voltage_level").data("kendoDropDownList").value("Toate");
				$("#energy_type").data("kendoDropDownList").value("EA");
				$("#service_code").data("kendoDropDownList").value("EA");
				$("#service_status").data("kendoDropDownList").value("Activ");
				$("#service_type_id").data("kendoDropDownList").value(3); 
				$("#service_name").data("kendoTextBox").value("");
				$("#service_print_order").data("kendoNumericTextBox").value(100);
			}else
			{
				var deletable = false;
				var gridData = $("#grid").data("kendoGrid").dataSource.data();
					for(let i=0;i<gridData.length;i++)
					  if(e === gridData[i].id) {					  
						$("#service_name").data("kendoTextBox").value(gridData[i].service_name);
						$("#service_type_id").data("kendoDropDownList").value(gridData[i].service_type_id);
						$("#service_measurment_unit").data("kendoDropDownList").value(gridData[i].service_measurment_unit);
						$("#service_pod_type").data("kendoDropDownList").value(gridData[i].service_pod_type);
						$("#voltage_level").data("kendoDropDownList").value(gridData[i].voltage_level);
						$("#energy_type").data("kendoDropDownList").value(gridData[i].energy_type);
						$("#service_code").data("kendoDropDownList").value(gridData[i].service_code);
						$("#service_status").data("kendoDropDownList").value(gridData[i].service_status);
						$("#service_type_id").data("kendoDropDownList").value(gridData[i].service_type_id); 
						$("#service_name").data("kendoTextBox").value(gridData[i].service_name);
						$("#service_print_order").data("kendoNumericTextBox").value(gridData[i].service_print_order);
						deletable = gridData[i].deletable;
					  }
				if(!deletable)
				{
					$("#supplier_name").data("kendoDropDownList").enable(false);
					$("#service_type_id").data("kendoDropDownList").enable(false);
					$("#service_pod_type").data("kendoDropDownList").enable(false);
					$("#voltage_level").data("kendoDropDownList").enable(false);
					$("#energy_type").data("kendoDropDownList").enable(false);
					$("#service_code").data("kendoDropDownList").enable(false);
				}
				
				$("#dialog").data("kendoDialog").title("Editează Serviciu");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Salvează");
			}
			
							
			$(".k-form-error").remove();
			$(".k-invalid").removeClass("k-invalid");
			
			$("#dialog").data("kendoDialog").open();
		}
		
	</script>
	'.
	$dialog->render();
}

function priceDialog()
{
	echo '
	<script>
	function createPriceDialog(customer_id)
	{
		window.location.href = window.location.origin + "/index.php/ebsMD/service_prices?action=add&customer_id="+customer_id;
	}
	function createContractDialog(customer_id)
	{
		window.location.href = window.location.origin + "/index.php/ebsMD/contracts_management?action=add&customer_id="+customer_id;
	}
	
	</script>';
}

function contractDialog()
{
	/*form*/
	
	$form = new \Kendo\UI\Form('contract');
	$form->formData(array('supplier_name' => 1, 'customer_name' =>'','contract_number'=>'','contract_component_type'=>'Zile calendaristice','price_component'=>'0.15'));
	$form->orientation("horizontal");
	$form->buttonsTemplate("<div style='width:100%;overflow:hidden;'></div>");

	$supplier = new \Kendo\UI\FormItem();
	$supplier
		->label("Furnizor")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('suppliers','supplier_name'),'dataTextField' => 'supplier_name','dataValueField'=>'supplier_id' ,"filter" => "contains", "optionLabel" => "Selectati Furnizor..."))
		->field("supplier_name")
		->validation(array("required" => true));
		
	$customer = new \Kendo\UI\FormItem();
	$customer
		->label("Nume Client")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('customers','customer_name'),'dataTextField' => 'customer_name','dataValueField'=>'customer_id' ,"filter" => "contains", "placeholder" => "Selectati clientul..."))
		->field("customer_name")
		->validation(array("required" => true));
		
	$numar = new \Kendo\UI\FormItem();
	$numar
		->label("Numar Contract")
		->attributes(array("maxlength" => "50"))
		->field("contract_number")
		->validation(array("required" => true))
		->hint("Exista facturi emise pe acest contract. Doriti sa adaugati un <a href='".site_url('ebsMD/service_prices')."'>pret nou</a>?");

	$contract_date = new \Kendo\UI\FormItem();
	$contract_date->label("Data Contract")
		->editor("DatePicker")
		->editorOptions(array('format'=>'dd-MM-yyyy'))
		->field("contract_date")
		->validation(array("required" => true));

	$start_date = new \Kendo\UI\FormItem();
	$start_date->label("Data Start")
		->editor("DatePicker")
		->editorOptions(array('format'=>'dd-MM-yyyy'))
		->field("contract_start_date");

	$stop_date = new \Kendo\UI\FormItem();
	$stop_date->label("Data Inchidere")
		->editor("DatePicker")
		->editorOptions(array('format'=>'dd-MM-yyyy'))
		->field("contract_stop_date");

	$price_en = new \Kendo\UI\FormItem();
	$price_en->label("Pret Energie Activa")
		->editor("NumericTextBox")
		->field("price_energy")
		->editorOptions(array("decimals" => 8,"format" => "{0:#.########}"));		
	
	$component = new \Kendo\UI\FormItem();
	$component
		->label("Tip Componenta")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => ['Zile calendaristice','Zile lucratoare','Pret fix','Fara']))
		->field("contract_component_type")
		->validation(array("required" => true));
		
	$price_comp = new \Kendo\UI\FormItem();
	$price_comp->label("Pret Componenta")
		->editor("NumericTextBox")
		->field("price_component")
		->editorOptions(array("decimals" => 8,"format" => "{0:#.########}"));				
	
	$form->addItem($supplier);
	$form->addItem($customer);
	$form->addItem($numar);
	$form->addItem($contract_date);
	$form->addItem($start_date);
	$form->addItem($stop_date);
	$form->addItem($price_en);
	$form->addItem($component);
	$form->addItem($price_comp);
	//$form->change("onContractChange");
	$form->attributes(array("method" => "post", "autocomplete"=>"off"));

	$dialogContent = $form->render();
	
	/*dialog*/
	$dialog = new \Kendo\UI\Dialog('dialog');
	$skipAction = new \Kendo\UI\DialogAction();
	$skipAction->text("Anulează");

	$intallAction = new \Kendo\UI\DialogAction();
	$intallAction->text("Adaugă")
				 ->primary(true)
				 ->action(new \Kendo\JavaScriptFunction('function() { return saveContract(); }'));
				 
	$dialog->title('Adaugă Contract')
		   ->width('600px')

		   ->closable(true)
		   ->modal(true)
		   ->visible(false)
		   ->show('function(e) {$("#customer_name").data("kendoDropDownList").focus();}')
		   //->close('onClose')
		   ->addAction($skipAction, $intallAction)
		   ->content($dialogContent);
		
	echo '
	<script>
		
		var contractID = null;
		
		$( document ).ready(function() {
			$("button.k-grid-addContract").bind("click",function(){ContractDialog();})				
		});
			
		function ContractDialog(e = null)
		{
			contractID = e;
			
			$(".k-form-error").hide();
			$(".k-invalid").removeClass("k-invalid");
				
			if(e != null)
			{
				$("#dialog").data("kendoDialog").title("Editează Contract");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Salvează");
				
				var data = {
					contract_id :e
				};
				var jqxhr = $.post(window.location.origin+"/api?action=dialog&subject=contractDialogData&type=read", JSON.stringify(data), function() {
				//alert( "success" );
				})
				.done(function(response) {				
					if (typeof response.msg !== "undefined") {
						$("#staticNotification").data("kendoNotification").show(response.msg, response.type);
					}
					else
					{
						//get form data
						$("#supplier_name").data("kendoDropDownList").value(response.supplier_id);
						$("#customer_name").data("kendoDropDownList").value(response.customer_id);
						$("#contract_number").data("kendoTextBox").value(response.contract_number);
						$("#contract_date").data("kendoDatePicker").value(response.contract_date);
						$("#contract_start_date").data("kendoDatePicker").value(response.contract_start_date);
						$("#contract_stop_date").data("kendoDatePicker").value(response.contract_stop_date);
						$("#price_energy").data("kendoNumericTextBox").value(response.price_energy);
						$("#contract_component_type").data("kendoDropDownList").value(response.contract_component_type);
						$("#price_component").data("kendoNumericTextBox").value(response.price_component);
					
						if(response.deletable == 0)
						{
							$("#supplier_name").data("kendoDropDownList").enable(false);
							$("#customer_name").data("kendoDropDownList").enable(false);
							$("#contract_start_date").data("kendoDatePicker").enable(false);	
							$("#price_energy").data("kendoNumericTextBox").enable(false);
							$("#contract_component_type").data("kendoDropDownList").enable(false);
							$("#price_component").data("kendoNumericTextBox").enable(false);
							$("#contract_number-form-hint").addClass("k-form-error").show();
						}
						else
						{
							$("#supplier_name").data("kendoDropDownList").enable(true);
							$("#customer_name").data("kendoDropDownList").enable(true);							
							$("#contract_start_date").data("kendoDatePicker").enable(true);	
							$("#price_energy").data("kendoNumericTextBox").enable(true);
							$("#contract_component_type").data("kendoDropDownList").enable(true);
							$("#price_component").data("kendoNumericTextBox").enable(true);
							$("#contract_number-form-hint").hide();
						}	
					
						$("#dialog").data("kendoDialog").open();
					}
					
				})
				.fail(function() {
					//probleme de retea
					$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				});
			}
			else
			{
				//reset
				$("#dialog").data("kendoDialog").title("Adaugă Contract");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Adaugă");
				
				$("#supplier_name").data("kendoDropDownList").enable(true);
				$("#customer_name").data("kendoDropDownList").enable(true);							
				$("#contract_start_date").data("kendoDatePicker").enable(true);	
				$("#price_energy").data("kendoNumericTextBox").enable(true);
				$("#contract_component_type").data("kendoDropDownList").enable(true);
				$("#price_component").data("kendoNumericTextBox").enable(true);
				$("#contract_number-form-hint").hide();
				
				$("#supplier_name").data("kendoDropDownList").value(1);
				$("#customer_name").data("kendoDropDownList").value("");
				$("#contract_number").data("kendoTextBox").value("");
				$("#contract_date").data("kendoDatePicker").value(new Date());
				let d = new Date(selYear_contract_date,selMonth_contract_date,1);
				d.setMonth(selMonth_contract_date+1);
				$("#contract_start_date").data("kendoDatePicker").value(d);
				$("#contract_stop_date").data("kendoDatePicker").value("");
				$("#price_energy").data("kendoNumericTextBox").value("");
				$("#contract_component_type").data("kendoDropDownList").value("Fara");
				$("#price_component").data("kendoNumericTextBox").value("");
				$("#contract_number-form-hint").hide();
				
				$("#dialog").data("kendoDialog").open();
			}
			
		}
		
		function saveContract()
		{
			 if($("#customer_name").data("kendoDropDownList").value() == "")
			   {
				 $("#staticNotification").data("kendoNotification").show("Ati ales un client?", "error");
				 return false;
			   }
			 if($("#contract_number").data("kendoTextBox").value() == "")
			   {
				 $("#staticNotification").data("kendoNotification").show("Aveti un numar de contract?", "error");
				 return false;
			   }
			 if(!$("#contract_date").data("kendoDatePicker").value())
			   {
				 $("#staticNotification").data("kendoNotification").show("Data contractului?", "error");
				 return false;
			   }
			   
			   if($("#contract_start_date").data("kendoDatePicker").value() == null || $("#contract_start_date").data("kendoDatePicker").value() >= $("#contract_stop_date").data("kendoDatePicker").value())
			   {
				 $("#staticNotification").data("kendoNotification").show("Data de start a contractului nu este setata sau este mai mare decat data de stop.", "error");
				 return false;
			   }
			   
			 if( ($("#price_energy").data("kendoNumericTextBox").value() || $("#price_component").data("kendoNumericTextBox").value()) && 
				!$("#contract_start_date").data("kendoDatePicker").value() )
			   {
				 $("#staticNotification").data("kendoNotification").show("Data de start?", "error");
				 return false;
			   }
			   
			var data = {
				contract_id :contractID,
				supplier_id:$("#supplier_name").data("kendoDropDownList").value(),
				customer_id:$("#customer_name").data("kendoDropDownList").value(),
				contract_number:$("#contract_number").data("kendoTextBox").value(),
				contract_date: kendo.toString($("#contract_date").data("kendoDatePicker").value(),"yyyy-MM-dd"),
				contract_stop_date: kendo.toString($("#contract_stop_date").data("kendoDatePicker").value(),"yyyy-MM-dd"),
				contract_component_type: $("#contract_component_type").data("kendoDropDownList").value(),
				contract_start_date: kendo.toString($("#contract_start_date").data("kendoDatePicker").value(),"yyyy-MM-dd"),
				price_energy: $("#price_energy").data("kendoNumericTextBox").value(),
				price_component: $("#price_component").data("kendoNumericTextBox").value()
			};
			var jqxhr = $.post(window.location.origin+"/api?action=dialog&subject=contractDialogData&type=save", JSON.stringify(data), function() {
			//alert( "success" );
			})
			.done(function(response) {				
				if (typeof response.msg !== "undefined") {
					$("#staticNotification").data("kendoNotification").show(response.msg, response.type);
				}

				$("#grid").data("kendoGrid").dataSource.read();				
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
			});
		}
		
	</script>
	';
	
	echo $dialog->render();
	
	
	/*echo '
	<script>
	function createContractDialog(customer_id)
	{
		window.location.href = window.location.origin + "/index.php/ebs/contracts_management#/add";
	}
	</script>';*/
}

function verifyMDDialog()
{
	echo '
	<script>
	function verifyMDDialog(customer_name)
	{
		window.location.href = window.location.origin + "/index.php/ebs/pods_management#/add";
	}
	</script>';
}

function podDialog()
{
	/*form*/
	
	$form = new \Kendo\UI\Form('profil');
	$form->formData(array('pod' => '','customer_name' =>'','pod_type'=>'comercial','city'=>'','address'=>''));
	$form->orientation("horizontal");
	$form->buttonsTemplate("<div style='width:100%;overflow:hidden;'></div>");

	$customer = new \Kendo\UI\FormItem();
	$customer
		->label("Nume Client")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('customers','customer_name'),'dataTextField' => 'customer_name','dataValueField'=>'customer_id' ,"filter" => "contains", "placeholder" => "Selectati clientul..."))
		->field("customer_name")
		->validation(array("required" => true));

	$lot = new \Kendo\UI\FormItem();
	$lot
		->label("Lot Client")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('zones','zone_name'),'dataTextField' => 'zone_name','dataValueField'=>'zone_id','cascadeFrom' => 'customer_name', "filter" => "contains", "placeholder" => "Selectati lotul..."))
		->field("zone_name")
		->validation(array("required" => true));
			
	$pod = new \Kendo\UI\FormItem();
	$pod
		->label("POD")
		->attributes(array("maxlength" => "50"))
		->field("pod")
		->validation(array("required" => true));

	$tip = new \Kendo\UI\FormItem();
	$tip
		->label("Tip POD")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => ['comercial','necomercial'], "placeholder" => "Selectati tipul..."))
		->field("pod_type")
		->validation(array("required" => true));
		

	$judet = new \Kendo\UI\FormItem();
	$judet
		->label("Judet")
		->editor("AutoComplete")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('counties','county'),'dataTextField' => 'county','dataValueField'=>'county_id', "filter" => "contains", "placeholder" => "Selectati judetul..."))
		->field("county")
		->validation(array("required" => true));


	$oras = new \Kendo\UI\FormItem();
	$oras
		->label("Localitate")
		->editor("AutoComplete")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('pods','city',true),'dataTextField' => 'city','dataValueField'=>'zone_id', "filter" => "contains", "placeholder" => "Selectati localitatea..."))
		->field("city")
		->validation(array("required" => true));

	$adress = new \Kendo\UI\FormItem();
	$adress
		->label("Adresa")
		->editor("AutoComplete")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('pods','address',true),'dataTextField' => 'address', "filter" => "contains", "placeholder" => "Selectati adresa..."))
		->field("address")
		->validation(array("required" => true));		

	$podStatus = new \Kendo\UI\FormItem();
	$podStatus
		->label("Status")
		->editor("Switch")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => ['Activ','Inactiv'], "messages"=>array('checked' => 'Activ','unchecked'=>'Inactiv')))
		->field("pod_status");	
	
	$form->addItem($customer);
	$form->addItem($lot);
	$form->addItem($pod);
	$form->addItem($tip);
	$form->addItem($judet);
	$form->addItem($oras);
	$form->addItem($adress);
	$form->addItem($podStatus);
	$form->change("onCustomerChange");
	$form->attributes(array("method" => "post", "autocomplete"=>"off"));

	$dialogContent = $form->render();
	
	/*dialog*/
	$dialog = new \Kendo\UI\Dialog('dialog');
	$skipAction = new \Kendo\UI\DialogAction();
	$skipAction->text("Anulează");

	$intallAction = new \Kendo\UI\DialogAction();
	$intallAction->text("Adaugă")
				 ->primary(true)
				 ->action(new \Kendo\JavaScriptFunction('function() { return savePOD(); }'));
				 
	$dialog->title('Adaugă POD')
		   ->width('600px')

		   ->closable(true)
		   ->modal(true)
		   ->visible(false)
		   ->show('function(e) {$("#customer_name").data("kendoDropDownList").focus();}')
		   //->close('onClose')
		   ->addAction($skipAction, $intallAction)
		   ->content($dialogContent);
		
	echo '
	<script>
	
		/* Master Data */
		var MasterData =window.location.pathname.endsWith("pods_management");
		var idPodID = null;
		
		$( document ).ready(function() {
			if(MasterData)
			{
				$("button.k-grid-addP").bind("click",function(){podDialog();})
				$("#select-activ").data("kendoButtonGroup").select(0);
				$("#select-activ").data("kendoButtonGroup").trigger("select");			
			}
		});
		
		function podDialog(e = null)
		{				
			//reset
			idPodID = e;
			$("#customer_name").data("kendoDropDownList").enable(true);
			$("#pod").data("kendoTextBox").enable(true);
				
			if(e == null)
			{
				$("#dialog").data("kendoDialog").title("Adaugă Pod");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Adaugă");
							
				$("#customer_name").data("kendoDropDownList").value("");
				$("#customer_name").data("kendoDropDownList").filterInput.val("");
				$("#customer_name").data("kendoDropDownList")._prev="";
				$("#customer_name").data("kendoDropDownList").dataSource.filter([]);
				$("#pod_type").data("kendoDropDownList").value("comercial");
				$("#zone_name").data("kendoDropDownList").value("");
				$("#pod").data("kendoTextBox").value("");
				$("#county").data("kendoAutoComplete").value("");
				$("#city").data("kendoAutoComplete").value("");
				$("#address").data("kendoAutoComplete").value("");
				$("#pod_status").data("kendoSwitch").value(true);
				
				
				$(".k-form-error").remove();
				$(".k-invalid").removeClass("k-invalid"); 

			}else
			{
				var gridData = $("#grid").data("kendoGrid").dataSource.data();
					for(let i=0;i<gridData.length;i++)
					  if(e === gridData[i].id) {
						$("#customer_name").data("kendoDropDownList").value(gridData[i].customer_id);
						$("#county").data("kendoAutoComplete").value(gridData[i].county);
						$("#city").data("kendoAutoComplete").value(gridData[i].city);
						$("#address").data("kendoAutoComplete").value(gridData[i].address);
						$("#pod").data("kendoTextBox").value(gridData[i].pod_no);
						$("#pod_type").data("kendoDropDownList").value(gridData[i].pod_type);
						$("#zone_name").data("kendoDropDownList").value(gridData[i].zone_id);
						if(!gridData[i].deletable){
							//$("#customer_name").data("kendoDropDownList").enable(false);
							$("#pod").data("kendoTextBox").enable(false);
						}
						$("#pod_status").data("kendoSwitch").value(gridData[i].pod_status == "Activ");
						break;
					  }
				$("#dialog").data("kendoDialog").title("Editează Pod");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Salvează");
			}
			
							
			$(".k-form-error").remove();
			$(".k-invalid").removeClass("k-invalid");
			
			$("#dialog").data("kendoDialog").open();
		}
		
		
		/*Used in Import Consumptions*/
		function onCustomerChange(e){
			
			if(e.field == "customer_name"){
				
				//use grid model
				var data = {
				filter: {
							field: "customer_id",
							operator:"eq",
							value:e.value
						}
				};
				
				var jqxhr = $.post({
						url: window.location.origin+"/api?subject=customers&type=read",
						data: JSON.stringify(data),
						contentType: "application/json; charset=utf-8"})
				.done(function(response) {
					if (typeof response !== "undefined" && typeof response.total !== "undefined" && response.total == 1) {
						$("#city").data("kendoAutoComplete").value(response.data[0].customer_city);
						$("#address").data("kendoAutoComplete").value(response.data[0].customer_address);
						return false;
					}
				})
			}	
		}
		
		function savePOD()
		{
			
			if($("#county").data("kendoAutoComplete").value() == "")
			{
				$("#staticNotification").data("kendoNotification").show("Ați ales un județ ?", "error");
				return false;
			}
			
			//use grid model
			var data = {
				models: [
						 {
							pod_id: idPodID,
							customer_id :$("#customer_name").data("kendoDropDownList").value(),
							pod_no:$("#pod").val(),
							zone_id:$("#zone_name").val(),
							pod_type:$("#pod_type").val(),
							county: $("#county").data("kendoAutoComplete").value(),
							city: $("#city").data("kendoAutoComplete").value(),
							address: $("#address").data("kendoAutoComplete").value(),
							pod_status: $("#pod_status").data("kendoSwitch").value() ? "Activ" : "Inactiv"
						 }
						]
			};
			
			let stype = "create";
			if (idPodID !== null) stype = "update";
			
			var jqxhr = $.post({
					url: window.location.origin+"/api?subject=pods&type="+stype,
					data: JSON.stringify(data),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {
					if(!MasterData) onStepper();
					$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
					return false;
				}
				else
				{
					if(!MasterData) 
						onStepper();
					else 
						$("#grid").data("kendoGrid").dataSource.read();
					
					$("#dialog").data("kendoDialog").close();
				}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				return false;
			});
			
			return false;
		}
		
		function createPODDialog(suggested_customer_name, pod)
		{
			//reset
			
			$("#customer_name").data("kendoDropDownList").value("");
			$("#customer_name").data("kendoDropDownList").filterInput.val("");
			$("#customer_name").data("kendoDropDownList")._prev="";
			$("#customer_name").data("kendoDropDownList").dataSource.filter([]);
			$("#zone_name").data("kendoDropDownList").value("");
			$("#city").data("kendoAutoComplete").value("");
			$("#address").data("kendoAutoComplete").value("");
			$("#pod_status").data("kendoSwitch").value(true);
			
			$(".k-form-error").remove();
			$(".k-invalid").removeClass("k-invalid");
			
			var data = {
				suggested_customer_name :suggested_customer_name,
				pod:pod
			};
			var jqxhr = $.post(window.location.origin+"/api?action=dialog&subject=podDialogData&type=read", JSON.stringify(data), function() {
			//alert( "success" );
			})
			.done(function(response) {
				
				if (response.error == 0)
				{
					//get form data
					$("#customer_name").data("kendoDropDownList").value(response.customer_id);
					$("#city").data("kendoAutoComplete").value(response.city);
					$("#address").data("kendoAutoComplete").value(response.address);
				}
				
				$("#pod").val(pod);
				
				$("#dialog").data("kendoDialog").open();
				if (typeof response.msg !== "undefined") {
					$("#staticNotification").data("kendoNotification").show(response.msg, response.type);
				}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
			});
		}
	</script>
	
	';
	
	echo $dialog->render();
}

function tariffPriceDialog($title)
{
	/*form*/
	
	$form = new \Kendo\UI\Form('tForm');
	$form->formData(array('supplier_name' => 1));
	$form->orientation("horizontal");
	$form->buttonsTemplate("<div style='width:100%;overflow:hidden;'></div>");


	$supplier = new \Kendo\UI\FormItem();
	$supplier
		->label("Furnizor")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('suppliers','supplier_name'),'dataTextField' => 'supplier_name','dataValueField'=>'supplier_id' ,"filter" => "contains", "optionLabel" => "Selectati Furnizor..."))
		->field("supplier_name")
		->validation(array("required" => true));

			
	$distributor = new \Kendo\UI\FormItem();
	$distributor
		->label("Distribuitor")
		->editor("DropDownList")
		->editorOptions(array("dataSource" => dataSource('distributors','distributor_name'),'dataTextField' => 'distributor_name','dataValueField'=>'distributor_id', "filter" => "contains", "optionLabel" => "General","cascade"=>new \Kendo\JavaScriptFunction('function(e) { onDistributorChange();}')))
		->attributes(array("maxlength" => "50"))
		->field("distributor_name")
		->validation(array("required" => false));
	
		
	$filterItemS1 = new \Kendo\Data\DataSourceFilterItem();
	$filterItemS1->field('service_type_id');
	if($title == 'Tarife')
		$filterItemS1->operator('neq');
	else
		$filterItemS1->operator('eq');
	
	$filterItemS1->value('3');
	
	$filterItemS2 = new \Kendo\Data\DataSourceFilterItem();
	$filterItemS2->field('service_status');
	$filterItemS2->operator('eq');
	$filterItemS2->value('Activ');
	
	$filterItemS3 = new \Kendo\Data\DataSourceFilterItem();
	$filterItemS3->field('service_id');
	$filterItemS3->operator('eq');
	$filterItemS3->value('0');
	
	$filterItemS4 = new \Kendo\Data\DataSourceFilterItem();
	$filterItemS4->filters(array($filterItemS2,$filterItemS3));
	$filterItemS4->logic('or');
	
	$filterItemS = new \Kendo\Data\DataSourceFilterItem();
	$filterItemS->filters(array($filterItemS1,$filterItemS4));
	$filterItemS->logic('and');
	
	$service = new \Kendo\UI\FormItem();
	$service
		->label("Serviciu")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('services','service_name',null,$filterItemS),'dataTextField' => 'service_name','dataValueField'=>'service_id' ,'cascadeFrom' => 'supplier_name', "filter" => "contains","dataBound"=>new \Kendo\JavaScriptFunction('function(e) { $("#service_name").data("kendoDropDownList").select(0);}')))
		->field("service_name")
		->validation(array("required" => true));
	
	$customer = new \Kendo\UI\FormItem();
	$customer
		->label("Client")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('customers','customer_name'),'dataTextField' => 'customer_name','dataValueField'=>'customer_id' ,'cascadeFrom' => 'supplier_name', "filter" => "contains", "optionLabel" => "Selectati Client..."))
		->field("customer_name")
		->validation(array("required" => true));

	$filterItem1 = new \Kendo\Data\DataSourceFilterItem();
	$filterItem1->field('contract_stop_date');
	$filterItem1->operator('isnull');
	$filterItem1->value(null);
	
	$filterItem2 = new \Kendo\Data\DataSourceFilterItem();
	$filterItem2->field('contract_stop_date');
	$filterItem2->operator('gt');
	$filterItem2->value(date('Y-m-d'));
	
	$filterItem3 = new \Kendo\Data\DataSourceFilterItem();
	$filterItem3->filters(array($filterItem1,$filterItem2));
	$filterItem3->logic('or');	
	
	$contract = new \Kendo\UI\FormItem();
	$contract
		->label("Contract")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('contracts','contract_calculated_numberdate',null,$filterItem3),'dataTextField' => 'contract_calculated_numberdate','dataValueField'=>'contract_id' ,'cascadeFrom' => 'customer_name', "filter" => "contains"))
		->field("contract_calculated_numberdate")
		->validation(array("required" => false));
		
	$lot = new \Kendo\UI\FormItem();
	$lot
		->label("Lot")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('zones','zone_name'),'dataTextField' => 'zone_name','dataValueField'=>'zone_id','cascadeFrom' => 'customer_name', "filter" => "contains", "optionLabel" => "Selectati LOT..."))
		->field("zone_name")
		->validation(array("required" => false));
			
	$pod = new \Kendo\UI\FormItem();
	$pod
		->label("POD")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('pods','pod_no'),'dataTextField' => 'pod_no','dataValueField'=>'pod_id','cascadeFrom' => 'zone_name', "filter" => "contains", "optionLabel" => "Selectati POD..."))
		->field("pod")
		->validation(array("required" => false));

	$value = new \Kendo\UI\FormItem();
	$value->label("Valoare")
		->editor("NumericTextBox")
		->field("service_value")
		->editorOptions(array("decimals" => 8,"format" => "{0:#.########}"))
		->validation(array("required" => true));		
	
	$start_date = new \Kendo\UI\FormItem();
	$start_date->label("Data Start")
		->editor("DatePicker")
		->editorOptions(array('format'=>'dd-MM-yyyy'))
		->field("start_date")
		->validation(array("required" => true));
	
	$form->addItem($supplier);
	$form->addItem($customer);
	$form->addItem($distributor);
	$form->addItem($service);
	$form->addItem($contract);
	$form->addItem($lot);
	$form->addItem($pod);
	$form->addItem($value);
	$form->addItem($start_date);
	
	$form->attributes(array("method" => "post", "autocomplete"=>"off"));

	$dialogContent = $form->render();
	
	/*dialog*/
	$dialog = new \Kendo\UI\Dialog('dialog');
	$skipAction = new \Kendo\UI\DialogAction();
	$skipAction->text("Anulează");

	$intallAction = new \Kendo\UI\DialogAction();
	$intallAction->text("Adaugă")
				 ->primary(true)
				 ->action(new \Kendo\JavaScriptFunction('function() { return savePreturiTarife(); }'));
				 
	$dialog->title('Adaugă '.$title)
		   ->width('600px')

		   ->closable(true)
		   ->modal(true)
		   ->visible(false)
		   ->show('function(e) {$("#service_name").data("kendoDropDownList").focus(); onDistributorChange();}')
		   //->close('onClose')
		   ->addAction($skipAction, $intallAction)
		   ->content($dialogContent);
		
	echo '
	<script>
		var ptID = null;
		
		function savePreturiTarife()
		{
			//use grid model
			var data = {
				models: [
						 {
							service_rate_id: ptID,
							supplier_id: $("#supplier_name").data("kendoDropDownList").value(),
							service_id: $("#service_name").data("kendoDropDownList").value(),
							distributor_id: ($("#distributor_name").data("kendoDropDownList").value() == "" ? null : $("#distributor_name").data("kendoDropDownList").value()),
							customer_id: ($("#customer_name").data("kendoDropDownList").value() == "" ? null : $("#customer_name").data("kendoDropDownList").value()),
							contract_id: ($("#contract_calculated_numberdate").data("kendoDropDownList").value()== "" ? null : $("#contract_calculated_numberdate").data("kendoDropDownList").value()),
							zone_id: ($("#zone_name").data("kendoDropDownList").value() == "" ? null : $("#zone_name").data("kendoDropDownList").value()),
							pod_id: ($("#pod").data("kendoDropDownList").value() == "" ? null : $("#pod").data("kendoDropDownList").value()),
							service_value: $("#service_value").data("kendoNumericTextBox").value(),
							start_date: $("#start_date").val()
						 }
						]
			};
			
			/*let dataItem = {
							service_rate_id: ptID,
							supplier_id: $("#supplier_name").data("kendoDropDownList").value(),
							service_id: $("#service_name").data("kendoDropDownList").value(),
							distributor_id: ($("#distributor_name").data("kendoDropDownList").value() == "" ? null : $("#distributor_name").data("kendoDropDownList").value()),
							customer_id: ($("#customer_name").data("kendoDropDownList").value() == "" ? null : $("#customer_name").data("kendoDropDownList").value()),
							contract_id: ($("#contract_calculated_numberdate").data("kendoDropDownList").value()== "" ? null : $("#contract_calculated_numberdate").data("kendoDropDownList").value()),
							zone_id: ($("#zone_name").data("kendoDropDownList").value() == "" ? null : $("#zone_name").data("kendoDropDownList").value()),
							pod_id: ($("#pod").data("kendoDropDownList").value() == "" ? null : $("#pod").data("kendoDropDownList").value()),
							service_value: $("#service_value").data("kendoNumericTextBox").value(),
							start_date: $("#start_date").val()
						 };
			
			let grid = $("#grid").data("kendoGrid");			
			// add a new data item
			grid.dataSource.add(dataItem);
			// save the created data item
			grid.dataSource.sync();*/
			

			   if($("#service_name").data("kendoDropDownList").value() == "")
			   {
				 $("#staticNotification").data("kendoNotification").show("Ati ales un serviciu?", "error");
				 return false;
			   }
			   if($("#service_value").data("kendoNumericTextBox").value() == null)
			   {
				 $("#staticNotification").data("kendoNotification").show("Ati introdus valoarea?", "error");
				 return false;
			   }
				
				'.($title == 'Preturi' ? '
				if($("#customer_name").data("kendoDropDownList").value() == "")
			   {
				 $("#staticNotification").data("kendoNotification").show("Ati ales clientul?", "error");
				 return false;
			   }
			   
			   if($("#contract_calculated_numberdate").data("kendoDropDownList").value() == "")
			   {
				 $("#staticNotification").data("kendoNotification").show("Ati ales contractul?", "error");
				 return false;
			   }
			   ':'').'
			
			let stype = "create";
			if (ptID !== null) stype = "update";
			
			var jqxhr = $.post({
					url: window.location.origin+"/api?subject=service_rates&type="+stype,
					data: JSON.stringify(data),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

					$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
				}
				else
				{
					$("#grid").data("kendoGrid").dataSource.read();
					'.($title == 'Preturi' ? '$("#dialog").data("kendoDialog").close();' : '').'
					
					if(ptID !== null) $("#dialog").data("kendoDialog").close();
					'.($title != 'Preturi' ? '
					else
						$("#staticNotification").data("kendoNotification").show("Tariful de "+data.models[0].service_value+" lei/"+$("#service_name").data("kendoDropDownList").text()+" a fost adaugat!","info");
					' : '').'
				}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
			});
			
			
			return false;
		}
		
		function TarifeDialog(e = null)
		{
			//reset
			ptID = e;
			
			$("#customer_name-form-label").parent().hide();
			$("#zone_name-form-label").parent().hide();
			$("#pod-form-label").parent().hide();
			$("#contract_calculated_numberdate-form-label").parent().hide();
			$("#customer_name").data("kendoDropDownList").value(null);
			$("#zone_name").data("kendoDropDownList").value(null);
			$("#pod").data("kendoDropDownList").value(null);
			$("#contract_calculated_numberdate").data("kendoDropDownList").value(null);

			var service = $("#service_name").data("kendoDropDownList");

			if (service.dataSource._filter.filters[1] == undefined) 
			{
				service.dataSource._filter.filters[1]=service.dataSource._filter.filters[0];
				service.dataSource._filter.filters[0] = {field:"supplier_id",operator:"eq",value:"1"};
				console.log("haa0 - supplier filter was manually initialized");
			}

			if(e != null)
			{
				$("#dialog").data("kendoDialog").title("Editează Tarife");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Salvează");
				var gridData = $("#grid").data("kendoGrid").dataSource.data();
					gridData.forEach(function(dataItem) {
					  if(e === dataItem.id) {					  
						$("#distributor_name").data("kendoDropDownList").value(dataItem.distributor_id);
						
						
						service.dataSource._filter.filters[1].filters[1].filters[1].value = dataItem.service_id;
						service.dataSource.filter(service.dataSource._filter);
						service.value(dataItem.service_id);
						//$("#service_name").data("kendoDropDownList").value(dataItem.service_id);
						$("#service_value").data("kendoNumericTextBox").value(dataItem.service_value);
						$("#start_date").data("kendoDatePicker").value(dataItem.start_date);
					  
						if(dataItem.deletable == 0)
							$("#supplier_name").data("kendoDropDownList").enable(false);
						else
							$("#supplier_name").data("kendoDropDownList").enable(true);
					  }
				  });
			}
			else
			{
				$("#dialog").data("kendoDialog").title("Adaugă Tarife");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Adaugă");
				if(prevDistributor == -1 || prevDistributor == 8)
					$("#distributor_name").data("kendoDropDownList").select(0);
				else
					$("#distributor_name").data("kendoDropDownList").value(dId[prevDistributor]);
				//$("#service_name").data("kendoDropDownList").select(0);
				service.dataSource._filter.filters[1].filters[1].filters[1].value = 0;
				service.dataSource.filter(service.dataSource._filter);
				$("#start_date").data("kendoDatePicker").value(new Date(selYear_start_date,selMonth_start_date,1));
				$("#service_value").data("kendoNumericTextBox").value(null);
				$("#supplier_name").data("kendoDropDownList").enable(true);
			}
			

				
			$(".k-form-error").remove();
			$(".k-invalid").removeClass("k-invalid");
			
			$("#dialog").data("kendoDialog").open();
			
		}
		
		function PreturiDialog(e = null)
		{
			//reset
			ptID = e;
			
			$("#distributor_name-form-label").parent().hide();
			$("#distributor_name").data("kendoDropDownList").value(null);
			
			if(e != null)
			{
				$("#dialog").data("kendoDialog").title("Editează Preturi");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Salvează");
				var gridData = $("#grid").data("kendoGrid").dataSource.data();
					gridData.forEach(function(dataItem) {
					  if(e === dataItem.id) {
						$("#service_name").data("kendoDropDownList").dataSource.options.filter[0].filters[1].filters[1].value = dataItem.service_id;
						$("#service_name").data("kendoDropDownList").dataSource.read();
						$("#service_name").data("kendoDropDownList").value(dataItem.service_id);
						$("#customer_name").data("kendoDropDownList").value(dataItem.customer_id);
						$("#zone_name").data("kendoDropDownList").value(dataItem.zone_id);
						$("#pod").data("kendoDropDownList").value(dataItem.pod_id);
						$("#contract_calculated_numberdate").data("kendoDropDownList").value(dataItem.contract_id);
						$("#service_value").data("kendoNumericTextBox").value(dataItem.service_value);
						$("#start_date").data("kendoDatePicker").value(dataItem.start_date);
						
						if(dataItem.deletable == 0)
							$("#supplier_name").data("kendoDropDownList").enable(false);
						else
							$("#supplier_name").data("kendoDropDownList").enable(true);
					  }
				  });
			}
			else
			{
				$("#service_name").data("kendoDropDownList").dataSource.options.filter[0].filters[1].filters[1].value = 0;
				$("#service_name").data("kendoDropDownList").dataSource.read();
				$("#dialog").data("kendoDialog").title("Adaugă Preturi");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Adaugă");
				$("#customer_name").data("kendoDropDownList").value(null);
				$("#contract_calculated_numberdate").data("kendoDropDownList").value(null);			
				$("#zone_name").data("kendoDropDownList").value(null);
				$("#pod").data("kendoDropDownList").value(null);
				$("#service_value").data("kendoNumericTextBox").value(null);
				$("#start_date").data("kendoDatePicker").value(new Date(selYear_start_date,selMonth_start_date,1));
				$("#supplier_name").data("kendoDropDownList").enable(true);
				
			}
			
			$(".k-form-error").remove();
			$(".k-invalid").removeClass("k-invalid");
			
			$("#dialog").data("kendoDialog").open();
			
		}
		
		$( document ).ready(function() {
			$("button.k-grid-addT").bind("click",function(){'.$title.'Dialog();})				
		});
		
		function onDistributorChange(e)
		{
			'. ($title == 'Preturi' ? '' :' 
			var service = $("#service_name").data("kendoDropDownList");
			var distributor = $("#distributor_name").data("kendoDropDownList");
			
			let old = (service.dataSource._filter.filters[2] == undefined ? 0 : service.dataSource._filter.filters[2].value); 
			let changed = false;
			if(distributor.value() == "")
			{
				service.dataSource._filter.filters[2]={field:"service_type_id",operator:"eq",value:"2"};
				changed = old!=2;
			}
			else
			{
				service.dataSource._filter.filters[2]={field:"service_type_id",operator:"eq",value:"1"};
				changed = old!=1;
			}
			
			//service.dataSource.read();
			
			if(changed)
			{
				service.dataSource.filter(service.dataSource._filter);
			}
			'). '
		}
		
	</script>
			
			
	';
	
	echo $dialog->render();
}

function invoiceDialog()
{
	/* form */
	$form = new \Kendo\UI\Form('tForm');
	$form->formData(array('supplier_name' => 1,'invoice_no'=>'AUTO','invoice_date'=>date('Y-m-d'),'invoice_due_date'=>(new DateTime())->add(new DateInterval('P30D'))->format('Y-m-d')));
	$form->orientation("horizontal");
	$form->buttonsTemplate("<div style='width:100%;overflow:hidden;'></div>");


	$supplier = new \Kendo\UI\FormItem();
	$supplier
		->label("Furnizor")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('suppliers','supplier_name'),'dataTextField' => 'supplier_name','dataValueField'=>'supplier_id' ,"filter" => "contains", "optionLabel" => "Selectati Furnizor..."))
		->field("supplier_name")
		->validation(array("required" => true));

	$invoice_no = new \Kendo\UI\FormItem();
	$invoice_no
		->label("Invoice no")
		->editor("TextBox")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("placeholder" => "Selectati nr facturii..."))
		->field("invoice_no")
		->validation(array("required" => true));

	$invoice_date = new \Kendo\UI\FormItem();
	$invoice_date->label("Data Factura")
		->editor("DatePicker")
		->editorOptions(array('format'=>'dd-MM-yyyy'))
		->field("invoice_date")
		->validation(array("required" => true));
	
	$due_date = new \Kendo\UI\FormItem();
	$due_date->label("Data Scadenta (30)")
		->editor("DatePicker")
		->editorOptions(array('format'=>'dd-MM-yyyy'))
		->field("invoice_due_date")
		->validation(array("required" => true));
		
	$customer = new \Kendo\UI\FormItem();
	$customer
		->label("Client")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('customers','customer_name'),'dataTextField' => 'customer_name','dataValueField'=>'customer_id' ,'cascadeFrom' => 'supplier_name', "filter" => "contains", "optionLabel" => "Selectati Client..."))
		->field("customer_name")
		->validation(array("required" => true));

	$filterItem1 = new \Kendo\Data\DataSourceFilterItem();
	$filterItem1->field('contract_stop');
	$filterItem1->operator('isnull');
	$filterItem1->value(null);
	
	$filterItem2 = new \Kendo\Data\DataSourceFilterItem();
	$filterItem2->field('contract_stop');
	$filterItem2->operator('gt');
	$filterItem2->value(date('Y-m-d'));
	
	$filterItem3 = new \Kendo\Data\DataSourceFilterItem();
	$filterItem3->filters(array($filterItem1,$filterItem2));
	$filterItem3->logic('or');	
	
	$contract = new \Kendo\UI\FormItem();
	$contract
		->label("Contract")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('contracts','contract_calculated_numberdate',false/*,$filterItem3*/),'dataTextField' => 'contract_calculated_numberdate','dataValueField'=>'contract_id' ,'cascadeFrom' => 'customer_name', "filter" => "contains"))
		->field("contract_calculated_numberdate")
		->validation(array("required" => true));
		
	$lot = new \Kendo\UI\FormItem();
	$lot
		->label("Lot")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('zones','zone_name'),'dataTextField' => 'zone_name','dataValueField'=>'zone_id','cascadeFrom' => 'customer_name', "filter" => "contains"))
		->field("zone_name")
		->validation(array("required" => false));

	$sFilterItem1 = new \Kendo\Data\DataSourceFilterItem();
	$sFilterItem1->field('storno_no');
	$sFilterItem1->operator('isnull');
	$sFilterItem1->value(null);
	
	$storno_no = new \Kendo\UI\FormItem();
	$storno_no
		->label("Storno no")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('invoices','invoice_no',null,$sFilterItem1),'dataTextField' => 'invoice_no','dataValueField'=>'invoice_id','cascadeFrom' => 'customer_name', "filter" => "contains", "optionLabel" => "Selectati factura..."))
		->field("storno_no")
		->validation(array("required" => false));
		
	$form->addItem($supplier);
	$form->addItem($customer);
	$form->addItem($contract);
	$form->addItem($lot);
	$form->addItem($invoice_date);
	$form->addItem($due_date);
	$form->addItem($invoice_no);
	$form->addItem($storno_no);
	$form->change("onInvoiceChange");
	$form->attributes(array("method" => "post", "autocomplete"=>"off"));

	$dialogContent = $form->render();
	
	
	/*dialog*/
	$dialog = new \Kendo\UI\Dialog('dialog');
	
	$editItems = new \Kendo\UI\DialogAction();
	$editItems->text('Editeaza Produse si Servicii')
			->cssClass('k-button-solid-info editPS')
			->action(new \Kendo\JavaScriptFunction('function() {window.open(window.location.origin + "/index.php/invoices/invoiced_items?invoiceId=" + idInvoiceID,"_blank"); return false;}'));
			//->action(new \Kendo\JavaScriptFunction('function() {window.open(window.location.origin + "/index.php/ebs/invoiced_items?invoiceId=" + idInvoiceID,"_blank"); return false;}'));
	
	$skipAction = new \Kendo\UI\DialogAction();
	$skipAction->text("Anulează");

	$intallAction = new \Kendo\UI\DialogAction();
	$intallAction->text("Adaugă")
				 ->primary(true)
				 ->action(new \Kendo\JavaScriptFunction('function() { return saveInvoice(); }'));
				 
	$dialog->title('Adaugă Factura')
		   ->width('600px')

		   ->closable(true)
		   ->modal(true)
		   ->visible(false)
		   ->show('function(e) {$("#customer_name").data("kendoDropDownList").focus();}')
		   //->close('onClose')
		   ->addAction($skipAction, $intallAction, $editItems)
		   ->content($dialogContent);
	
	echo '
	<script>
		var idInvoiceID = null;
		var invoiceDueDays= 30;
		$( document ).ready(function() {
			$("button.k-grid-addI").bind("click",function(){InvoiceDialog();})				
		});
		
		function onInvoiceChange(e)
		{
			//update invoice due date
			if(e.field == "customer_name")
			{
				let id = $("#invoice_date").data("kendoDatePicker").value();
				invoiceDueDays = e.value.customer_invoice_due_days;
				$("#invoice_due_date").data("kendoDatePicker").value(kendo.date.addDays(id,invoiceDueDays));
				$("#invoice_due_date-form-label").text("Data Scadentei ("+invoiceDueDays+")");
			}
			
			if(e.field == "invoice_date")
			{
				$("#invoice_due_date").data("kendoDatePicker").value(kendo.date.addDays(e.value,invoiceDueDays));
			}
			
		}
		
		function saveInvoice()
		{
			//use grid model
			var data = {
				models: [
						 {
							invoice_id:idInvoiceID,
							supplier_id :$("#supplier_name").val(),
							invoice_no:$("#invoice_no").val(),
							invoice_date:$("#invoice_date").data("kendoDatePicker").value(),
							invoice_due_date:$("#invoice_due_date").data("kendoDatePicker").value(),
							customer_id :$("#customer_name").val(),
							contract_id:$("#contract_calculated_numberdate").val(),
							zone_id: ($("#zone_name").val() == "" ? null : $("#zone_name").val()),
							invoice_status:"In pregatire",
							storno_no:($("#storno_no").data("kendoDropDownList").value() == "" ? null : $("#storno_no").data("kendoDropDownList").text())
						 }
						]
			};
			
			let stype = "create";
			if (idInvoiceID !== null) stype = "update";
				
			
			var jqxhr = $.post({
					url: window.location.origin+"/api?subject=invoices&type="+stype,
					data: JSON.stringify(data),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

					$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
					return false;
				}
				else
				{
					$("#grid").data("kendoGrid").dataSource.read();
					$("#dialog").data("kendoDialog").close();
				}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				return false;
			});
			
			return false;
		}
		
		function InvoiceDialog(e = null)
		{
			//reset
			idInvoiceID = e;
		
			if(e == null)
			{
				$("#dialog").data("kendoDialog").title("Adaugă Factură");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Adaugă");
				$("#invoice_no").data("kendoTextBox").value("AUTO");
				$("#invoice_date").data("kendoDatePicker").value(new Date());
				$("#invoice_due_date").data("kendoDatePicker").value(kendo.date.addDays(new Date(),30));
				$("#customer_name").data("kendoDropDownList").value(null);
				$("#zone_name").data("kendoDropDownList").value(null);
				$("#storno_no").data("kendoDropDownList").value(null);
				$("#contract_calculated_numberdate").data("kendoDropDownList").value(null);
				invoiceDueDays = 30;
				$(".editPS").hide();
			}else
			{
				var gridData = $("#grid").data("kendoGrid").dataSource.data();
					for(let i=0;i<gridData.length;i++)
					  if(e === gridData[i].id) {					  
						$("#customer_name").data("kendoDropDownList").value(gridData[i].customer_id);
						$("#contract_calculated_numberdate").data("kendoDropDownList").value(gridData[i].contract_id);
						$("#zone_name").data("kendoDropDownList").value(gridData[i].zone_id);
						$("#invoice_date").data("kendoDatePicker").value(gridData[i].invoice_date);
						$("#invoice_due_date").data("kendoDatePicker").value(gridData[i].invoice_due_date);
						$("#invoice_no").data("kendoTextBox").value(gridData[i].invoice_no);
						$("#storno_no").data("kendoDropDownList").value(gridData[i].storno_no);
						
						invoiceDueDays = $("#customer_name").data("kendoDropDownList").dataItem().customer_invoice_due_days;					
						$("#invoice_due_date-form-label").text("Data Scadentei ("+invoiceDueDays+")");
					  }
				  
				$("#dialog").data("kendoDialog").title("Editează Factură");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Salvează");
				$(".editPS").show();
			}
			
							
			$(".k-form-error").remove();
			$(".k-invalid").removeClass("k-invalid");
			
			$("#dialog").data("kendoDialog").open();
		}
		
	</script>
	'.
	$dialog->render();
}

function invoicedItemsDialog($invoiceID, $customerID, $zoneID, $totalEA)
{
/* form */
	$form = new \Kendo\UI\Form('tForm');
	$form->formData(array('invoiced_item_unit_price' => 0,'invoiced_item_quantity' => 0,'invoiced_item_vat' => 0));
	$form->orientation("horizontal");
	$form->buttonsTemplate("<div style='width:100%;overflow:hidden;'></div>");


	$filterItemS = new \Kendo\Data\DataSourceFilterItem();
	$filterItemS->field('service_status');
	$filterItemS->operator('eq');
	$filterItemS->value('Activ');
	
	/*$service = new \Kendo\UI\FormItem();
	$service
		->label("Serviciu")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('services','service_name',null,$filterItemS),'dataTextField' => 'service_name','dataValueField'=>'service_id' ,'cascadeFrom' => 'supplier_name', "filter" => "contains","dataBound"=>new \Kendo\JavaScriptFunction('function(e) { $("#service_name").data("kendoDropDownList").select(0);}')))
		->field("service_name")
		->validation(array("required" => true));*/
	
	$service = new \Kendo\UI\FormItem();
	$service
		->label("Tarif/Serviciu")
		->editor("AutoComplete")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('services','service_name',null,$filterItemS),'dataTextField' => 'service_name','dataValueField'=>'service_id', "filter" => "contains","placeholder" => "Selectati serviciu...", 
															"select"=>new \Kendo\JavaScriptFunction("function(e) { if (e.item != null) serviceDataItemIndex = e.item.index(); }")))
		->field("invoiced_item_name")
		->validation(array("required" => true)); 

	$itemUM = new \Kendo\UI\FormItem();
	$itemUM
		->label("UM")
		->attributes(array("maxlength" => "5"))
		->field("invoiced_item_measurement_unit")
		->validation(array("required" => true));
	
	$filterItemP1 = new \Kendo\Data\DataSourceFilterItem();
	$filterItemP1->field('zone_id');
	$filterItemP1->operator('eq');
	$filterItemP1->value($zoneID);
	
	$filterItemP2 = new \Kendo\Data\DataSourceFilterItem();
	$filterItemP2->field('customer_id');
	$filterItemP2->operator('eq');
	$filterItemP2->value($customerID);
	
	$filterItemP = new \Kendo\Data\DataSourceFilterItem();
	$filterItemP->filters(array($filterItemP1,$filterItemP2));
	$filterItemP->logic('and');
	
	$pod = new \Kendo\UI\FormItem();
	$pod
		->label("POD")
		->editor("AutoComplete")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('pods','pod_no',null,$filterItemP),'dataTextField' => 'pod_no','dataValueField'=>'pod_if', "filter" => "contains", "placeholder" => "Selectati POD...",
						"select"=>new \Kendo\JavaScriptFunction("function(e) { if (e.item != null) podDataItemIndex = e.item.index(); }")))
		->field("invoiced_pod_no")
		->validation(array("required" => true));

	$distributor = new \Kendo\UI\FormItem();
	$distributor->label("Distribuitor")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('distributors','distributor_name'),'dataTextField' => 'distributor_name','dataValueField'=>'distributor_id', "filter" => "contains", "placeholder" => "Selectati distribuitorul..."))
		->field("distributor_name")
		->validation(array("required" => true));
		
	$no = new \Kendo\UI\FormItem();
	$no->label("Pozitie")
		->editor("NumericTextBox")
		->field("invoiced_item_no")
		->editorOptions(array("decimals" => 0,"format" => "{0:#}"))
		->validation(array("required" => true));

	$quantity = new \Kendo\UI\FormItem();
	$quantity->label("Cantitate")
		->editor("NumericTextBox")
		->field("invoiced_item_quantity")
		->editorOptions(array("decimals" => 3,"format" => "{0:#.###}"))
		->validation(array("required" => true));	
		
	$unitPrice = new \Kendo\UI\FormItem();
	$unitPrice->label("Pret Unitar")
		->editor("NumericTextBox")
		->field("invoiced_item_unit_price")
		->editorOptions(array("decimals" => 8,"format" => "{0:#.########}"))
		->validation(array("required" => true));	

	$value = new \Kendo\UI\FormItem();
	$value->label("Valoare")
		->editor("NumericTextBox")
		->field("invoiced_item_value")
		->editorOptions(array("decimals" => 2,"format" => "{0:#.##}"))
		->validation(array("required" => true));

	$vat = new \Kendo\UI\FormItem();
	$vat->label("TVA")
		->editor("NumericTextBox")
		->field("invoiced_item_vat")
		->editorOptions(array("decimals" => 2,"format" => "{0:#.##}"))
		->validation(array("required" => true));
	
	$form->addItem($no);	
	$form->addItem($service);
	$form->addItem($pod);
	$form->addItem($distributor);
	$form->addItem($quantity);
	$form->addItem($itemUM);
	$form->addItem($unitPrice);
	$form->addItem($value);
	$form->addItem($vat);

	$form->change("onInvoicedItemChange");
	$form->attributes(array("method" => "post", "autocomplete"=>"off"));

	$dialogContent = $form->render();
	
	
	/*dialog*/
	$dialog = new \Kendo\UI\Dialog('dialog');
	
	$skipAction = new \Kendo\UI\DialogAction();
	$skipAction->text("Anulează");

	$intallAction = new \Kendo\UI\DialogAction();
	$intallAction->text("Adaugă")
				 ->primary(true)
				 ->action(new \Kendo\JavaScriptFunction('function() { return saveInvoicedItem(); }'));
				 
	$dialog->title('Adaugă Tarife/Servicii')
		   ->width('600px')

		   ->closable(true)
		   ->modal(true)
		   ->visible(false)
		   ->show('function(e) {$("#invoiced_item_name").data("kendoAutoComplete").focus();}')
		   //->close('onClose')
		   ->addAction($skipAction, $intallAction)
		   ->content($dialogContent);
	
	echo '
	<script>
		var idInvoiceItemID = null;
		var idInvoiceID = '.$invoiceID.';
		var idServiceRateID = null;
		var vatValue = 19;
		
		$( document ).ready(function() {
			$("button.k-grid-addII").bind("click",function(){invoicedItemsDialog();})				
		});
		
		var serviceDataItemIndex = 0;
		var podDataItemIndex = 0;
		
		function readCustom(custom,data,callback)
		{						
			var jqxhr = $.post({
							url: window.location.origin+"/api?subject=custom&type=call&action="+custom,
							data: JSON.stringify(data),
							contentType: "application/json; charset=utf-8"})
					.done(function(response) {
						if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

						$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
						return false;
					}
					else
					{
						if(response)	callback(response);									
					}
					})
					.fail(function() {
						//probleme de retea
						$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
						return false;
					});
		}
		
		function readField(subject,field,filter,callback)
		{			
			var jqxhr = $.post({
					url: window.location.origin+"/api?action=field&subject="+subject+"&type=read&field="+field,
					data: JSON.stringify(filter),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

					$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
					return false;
				}
				else
				{
					if(response.data[0][field])	callback(response.data[0][field]);
				}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				return false;
			});
		}
		
		function read(subject,filter,callback)
		{			
			var jqxhr = $.post({
					url: window.location.origin+"/api?subject="+subject+"&type=read",
					data: JSON.stringify(filter),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

					$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
					return false;
				}
				else
				{
					if(response.data[0])	callback(response.data[0]);
				}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				return false;
			});
		}
		
		function onInvoicedItemChange(e)
		{
			//update invoice vat
			if(["invoiced_item_quantity","invoiced_item_unit_price"].find(element => element = e.field))
			{
				let p = $("#invoiced_item_unit_price").data("kendoNumericTextBox").value();
				let q = $("#invoiced_item_quantity").data("kendoNumericTextBox").value();
				$("#invoiced_item_value").data("kendoNumericTextBox").value(Math.round((p*q + Number.EPSILON) * 100) / 100);
				let v = $("#invoiced_item_value").data("kendoNumericTextBox").value();
				$("#invoiced_item_vat").data("kendoNumericTextBox").value(Math.round((v*vatValue/100 + Number.EPSILON) * 100) / 100);
			}
			if(e.field == "invoiced_item_name")
			{
				
				if ($("#invoiced_item_name").data("kendoAutoComplete").dataSource._data.length > 0)
				{
					readField("services","service_measurment_unit",{"filter":{"filters":[{"field":"service_id","operator":"eq","value":$("#invoiced_item_name").data("kendoAutoComplete").dataSource._data[serviceDataItemIndex].service_id}],"logic":"and"}}, function(v){$("#invoiced_item_measurement_unit").data("kendoTextBox").value(v);});
					
						var data = {
						models: [
								 {
									service_id:$("#invoiced_item_name").data("kendoAutoComplete").dataSource._data[serviceDataItemIndex].service_id,
									invoice_id: idInvoiceID,
									distributor_id: $("#distributor_name").data("kendoDropDownList").value()
								 }
								]
						};
						
					readCustom("getServicePriceFromInvoice",data,function(v){$("#invoiced_item_unit_price").data("kendoNumericTextBox").value(v.service_value)});
				}
			}
			if(e.field == "invoiced_pod_no")
			{
				
				readField("view_pods_distributors","distributor_id",{"filter":{"filters":[{"field":"pod_no","operator":"eq","value":$("#invoiced_pod_no").data("kendoAutoComplete").value()}],"logic":"and"}}, function(v){$("#distributor_name").data("kendoDropDownList").value(v);});
			}
		}
		
		function saveInvoicedItem()
		{
			//use grid model
			var data = {
				models: [
						 {
							invoiced_item_id:idInvoiceItemID,
							invoice_id:idInvoiceID,
							service_rate_id :idServiceRateID,
							distributor_id: $("#distributor_name").data("kendoDropDownList").value(),
							invoiced_item_no: $("#invoiced_item_no").data("kendoNumericTextBox").value(),
							invoiced_pod_no: $("#invoiced_pod_no").data("kendoAutoComplete").value(),
							invoiced_item_name: $("#invoiced_item_name").data("kendoAutoComplete").value(),
							invoiced_item_measurement_unit: $("#invoiced_item_measurement_unit").data("kendoTextBox").value(),
							invoiced_item_quantity: $("#invoiced_item_quantity").data("kendoNumericTextBox").value(),
							invoiced_item_unit_price: $("#invoiced_item_unit_price").data("kendoNumericTextBox").value(),
							invoiced_item_value: $("#invoiced_item_value").data("kendoNumericTextBox").value(),
							invoiced_item_vat: $("#invoiced_item_vat").data("kendoNumericTextBox").value()
						 }
						]
			};
			
			let stype = "create";
			if (idInvoiceItemID !== null) stype = "update";
				
			
			var jqxhr = $.post({
					url: window.location.origin+"/api?subject=invoiced_items&type="+stype,
					data: JSON.stringify(data),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

					$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
					return false;
				}
				else
				{
					$("#grid").data("kendoGrid").dataSource.read();
					
					read("invoices",{"filter":{"filters":[{"field":"invoice_id","operator":"eq","value":idInvoiceID}],"logic":"and"}}, function(v){$("#total_ea").text(v.invoice_calculated_ea_quantity+" MWh");$("#total_amount").text(v.invoice_calculated_total+" LEI");});
					
					$("#dialog").data("kendoDialog").close();
					
				}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				return false;
			});
			
			return false;
		}
		
		function getNewInvoicedItem()
		{
			idInvoiceItemID = null;
			
			var data = {
						invoice_id:idInvoiceID,
						invoiced_item_id:null
					};
									
			var jqxhr = $.post({
					url: window.location.origin+"/api?action=dialog&subject=invoicedItemsDialogData&type=read",
					data: JSON.stringify(data),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

				$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
				return false;
			}
			else
			{
				vatValue = response.vat_value;

				$("#invoiced_item_no").data("kendoNumericTextBox").value(response.invoiced_item_no);
				$("#invoiced_item_name").data("kendoAutoComplete").value(response.service_name);
				$("#invoiced_pod_no").data("kendoAutoComplete").value(response.invoiced_pod_no);
				$("#distributor_name").data("kendoDropDownList").value(response.distributor_id);
				$("#invoiced_item_quantity").data("kendoNumericTextBox").value(response.invoiced_item_quantity);
				$("#invoiced_item_measurement_unit").data("kendoTextBox").value(response.service_measurment_unit);
				$("#invoiced_item_unit_price").data("kendoNumericTextBox").value(response.service_value);
				let p = $("#invoiced_item_unit_price").data("kendoNumericTextBox").value();
				let q = $("#invoiced_item_quantity").data("kendoNumericTextBox").value();
				$("#invoiced_item_value").data("kendoNumericTextBox").value(Math.round((p*q + Number.EPSILON) * 100) / 100);
				let v = $("#invoiced_item_value").data("kendoNumericTextBox").value();
				$("#invoiced_item_vat").data("kendoNumericTextBox").value(Math.round((v*vatValue/100 + Number.EPSILON) * 100) / 100);
				
				idServiceRateID = response.service_rate_id;
			}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				return false;
			});
			
			return false;
		}
		
		function getExistingInvoicedItem(invoiceItemID)
		{
			idInvoiceItemID = invoiceItemID;
			
			var data = {
						invoice_id:idInvoiceID,
						invoiced_item_id:invoiceItemID
					};
									
			var jqxhr = $.post({
					url: window.location.origin+"/api?action=dialog&subject=invoicedItemsDialogData&type=read",
					data: JSON.stringify(data),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

				$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
				return false;
			}
			else
			{
				vatValue = response.vat_value;

				$("#invoiced_item_no").data("kendoNumericTextBox").value(response.invoiced_item_no);
				$("#invoiced_item_name").data("kendoAutoComplete").value(response.invoiced_item_name);
				$("#invoiced_pod_no").data("kendoAutoComplete").value(response.invoiced_pod_no);
				$("#distributor_name").data("kendoDropDownList").value(response.distributor_id);
				$("#invoiced_item_unit_price").data("kendoNumericTextBox").value(response.invoiced_item_unit_price);
				$("#invoiced_item_quantity").data("kendoNumericTextBox").value(response.invoiced_item_quantity);
				$("#invoiced_item_measurement_unit").data("kendoTextBox").value(response.invoiced_item_measurement_unit);
				$("#invoiced_item_value").data("kendoNumericTextBox").value(response.invoiced_item_value);
				$("#invoiced_item_vat").data("kendoNumericTextBox").value(response.invoiced_item_vat);
				
				idServiceRateID = response.service_rate_id;
			}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				return false;
			});
			
			return false;
		}
		
		function invoicedItemsDialog(e = null)
		{
		
			if(e == null)
			{
				$("#dialog").data("kendoDialog").title("Adaugă Tarife/Servicii");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Adaugă");
				
				getNewInvoicedItem();
			
			}else
			{
				$("#dialog").data("kendoDialog").title("Editează Tarife/Servicii");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Salvează");
			
				getExistingInvoicedItem(e);
			}
			
							
			$(".k-form-error").remove();
			$(".k-invalid").removeClass("k-invalid");
			
			$("#dialog").data("kendoDialog").open();
		}
		
	</script>
	'.
	$dialog->render();	
}

function customerDialog()
{
	/* form */
	$form = new \Kendo\UI\Form('cForm');
	$form->formData(array('supplier_name' => 1,'customer_invoice_due_days' => 30,'customer_anre_band' => 'Auto','customer_status' => 'Activ'));
	$form->orientation("horizontal");
	$form->buttonsTemplate("<div style='width:100%;overflow:hidden;'></div>");


	$supplier = new \Kendo\UI\FormItem();
	$supplier
		->label("Furnizor")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('suppliers','supplier_name'),'dataTextField' => 'supplier_name','dataValueField'=>'supplier_id' ,"filter" => "contains", "optionLabel" => "Selectati Furnizor..."))
		->field("supplier_name")
		->validation(array("required" => true));

	$customer_name = new \Kendo\UI\FormItem();
	$customer_name
		->label("Denumire")
		->editor("TextBox")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("placeholder" => "Nume Client"))
		->field("customer_name")
		->validation(array("required" => true));

	$customer_registration_number = new \Kendo\UI\FormItem();
	$customer_registration_number
		->label("Nr. Reg.Com.")
		->editor("TextBox")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("placeholder" => "Numar Registrul Comertului"))
		->field("customer_registration_number")
		->validation(array("required" => true));

	$customer_vat_code = new \Kendo\UI\FormItem();
	$customer_vat_code
		->label("C.U.I.")
		->editor("TextBox")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("placeholder" => "Cod unic de identificare"))
		->field("customer_vat_code")
		->hint("Modifica si apasa ENTER sau TAB pentru preluare date ANAF")
		->validation(array("required" => true));

	//judet
	$customer_county = new \Kendo\UI\FormItem();
	$customer_county
		->label("Judet")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => ['Alba','Arad','Argeş','Bacău','Bihor','Bistriţa-Năsăud','Botoşani','Brăila','Braşov','Buzău','Călăraşi','Caraş-Severin','Cluj','Constanţa','Covasna','Dâmboviţa','Dolj','Galaţi','Giurgiu','Gorj','Harghita','Hunedoara','Ialomiţa','Iaşi','Ilfov','Maramureş','Mehedinţi','Mureş','Neamţ','Olt','Prahova','Sălaj','Satu Mare','Sibiu','Suceava','Teleorman','Timiş','Tulcea','Vâlcea','Vaslui','Vrancea','Bucureşti']))
		->field("customer_county")
		->validation(array("required" => true));
	
	//localitate
	$customer_city = new \Kendo\UI\FormItem();
	$customer_city
		->label("Localitate")
		->editor("AutoComplete")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('customers','customer_city',true),'dataTextField' => 'customer_city', "filter" => "contains", "placeholder" => "Selectati orasul..."))
		->field("customer_city")
		->validation(array("required" => true));

	$customer_address = new \Kendo\UI\FormItem();
	$customer_address
		->label("Adresa")
		->editor("AutoComplete")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('customers','customer_address',true),'dataTextField' => 'customer_address', "filter" => "contains", "placeholder" => "Selectati adresa..."))
		->field("customer_address")
		->validation(array("required" => true));
	
	$customer_bank = new \Kendo\UI\FormItem();
	$customer_bank
		->label("Banca")
		->editor("TextBox")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("placeholder" => "Banca"))
		->field("customer_bank")
		->validation(array("required" => true));
		
	$customer_bank_account = new \Kendo\UI\FormItem();
	$customer_bank_account
		->label("IBAN")
		->editor("TextBox")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("placeholder" => "Contul bancar"))
		->field("customer_bank_account")
		->validation(array("required" => true));
	
	$customer_invoice_due_days = new \Kendo\UI\FormItem();
	$customer_invoice_due_days->label("Scadenta (nr zile)")
		->editor("NumericTextBox")
		->field("customer_invoice_due_days")
		->editorOptions(array("decimals" => 0,"format" => "{0:#}"))
		->validation(array("required" => true));
	
	//status
	
	$customer_status = new \Kendo\UI\FormItem();
	$customer_status
		->label("Status")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => ['Activ','Inactiv']))
		->field("customer_status")
		->validation(array("required" => true));
		
	//anre

	$customer_anre_band = new \Kendo\UI\FormItem();
	$customer_anre_band
		->label("Banda ANRE")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => ['Auto','IA','IB','IC','ID','IE','IF']))
		->field("customer_anre_band")
		->validation(array("required" => true));
			
	$form->addItem($supplier);
	$form->addItem($customer_name);
	$form->addItem($customer_registration_number);
	$form->addItem($customer_vat_code);
	$form->addItem($customer_county);
	$form->addItem($customer_city);
	$form->addItem($customer_address);
	$form->addItem($customer_bank);
	$form->addItem($customer_bank_account);
	$form->addItem($customer_invoice_due_days);
	$form->addItem($customer_anre_band);
	$form->addItem($customer_status);
	$form->change("onCustomerChange");
	$form->attributes(array("method" => "post", "autocomplete"=>"off"));

	$dialogContent = $form->render();
	
	
	/*dialog*/
	$dialog = new \Kendo\UI\Dialog('dialog');
		
	$skipAction = new \Kendo\UI\DialogAction();
	$skipAction->text("Anulează");

	$intallAction = new \Kendo\UI\DialogAction();
	$intallAction->text("Adaugă")
				 ->primary(true)
				 ->action(new \Kendo\JavaScriptFunction('function() { return saveCustomer(); }'));
				 
	$dialog->title('Adaugă Client')
		   ->width('600px')

		   ->closable(true)
		   ->modal(true)
		   ->visible(false)
		   ->show('function(e) {$("#customer_vat_code").data("kendoTextBox").focus();}')
		   //->close('onClose')
		   ->addAction($skipAction, $intallAction)
		   ->content($dialogContent);
	
	echo '
	<script>
		var idCustomerID = null;
		var invoiceDueDays= 30;
		
		$( document ).ready(function() {
			$("button.k-grid-addC").bind("click",function(){CustomerDialog();})				
		});
		
		function onCustomerChange(e)
		{
			console.log("onCustomerChange");
			//update invoice due date
			if(e.field == "customer_vat_code" &&  $("#customer_vat_code").data("kendoTextBox").value().length >=6 )
			{
				var data = {
					models: [{
							cui: $("#customer_vat_code").data("kendoTextBox").value()
							}]
					};
							
				var jqxhr = $.post({
						url: window.location.origin+"/api?subject=custom&type=call&action=getANAFData",
						data: JSON.stringify(data),
						contentType: "application/json; charset=utf-8"})
				.done(function(response) {
					if (typeof response !== "undefined" && typeof response.errors !== "undefined") {
					
						$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
						return false;
					}
					else
					{
						if (response.message == "SUCCESS")
						{							
							console.log(response);
							$("#customer_name").data("kendoTextBox").value(response.found[0].date_generale.denumire);
							$("#customer_registration_number").data("kendoTextBox").value(response.found[0].date_generale.nrRegCom);
							$("#customer_vat_code").data("kendoTextBox").value(response.found[0].date_generale.cui);
							$("#customer_county").data("kendoDropDownList").value(response.found[0].adresa_domiciliu_fiscal.ddenumire_Judet);
							$("#customer_city").data("kendoAutoComplete").value(response.found[0].adresa_domiciliu_fiscal.ddenumire_Localitate);
							$("#customer_address").data("kendoAutoComplete").value(response.found[0].adresa_domiciliu_fiscal.ddenumire_Strada);
							
							$("#customer_bank").data("kendoTextBox").value("");
							$("#customer_bank_account").data("kendoTextBox").value("");
							$("#customer_invoice_due_days").data("kendoNumericTextBox").value(30);
							$("#customer_anre_band").data("kendoDropDownList").value("Auto");
						}
						
					}
				})
				.fail(function() {
					
					
					//probleme de retea
					$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
					return false;
				});	
			}			
		}
		
		function saveCustomer()
		{
				
			//use grid model
			var data = {
				models: [
						 {
							customer_id:idCustomerID,
							supplier_id :$("#supplier_name").val(),
							customer_name:$("#customer_name").data("kendoTextBox").value(),
							customer_registration_number:$("#customer_registration_number").data("kendoTextBox").value(),
							customer_vat_code: $("#customer_vat_code").data("kendoTextBox").value(),
							customer_bank: $("#customer_bank").data("kendoTextBox").value(),
							customer_bank_account: $("#customer_bank_account").data("kendoTextBox").value(),
							customer_county: $("#customer_county").data("kendoDropDownList").value(),
							customer_city: $("#customer_city").data("kendoAutoComplete").value(),
							customer_address: $("#customer_address").data("kendoAutoComplete").value(),
							customer_invoice_due_days: $("#customer_invoice_due_days").data("kendoNumericTextBox").value(),
							customer_anre_band: $("#customer_anre_band").data("kendoDropDownList").value(),
							customer_status: $("#customer_status").data("kendoDropDownList").value()
						 }
						]
			};
			
			let stype = "create";
			if (idCustomerID !== null) stype = "update";
				
			
			var jqxhr = $.post({
					url: window.location.origin+"/api?subject=customers&type="+stype,
					data: JSON.stringify(data),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

					$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
					return false;
				}
				else
				{
					$("#grid").data("kendoGrid").dataSource.read();
					$("#dialog").data("kendoDialog").close();
					
					if (typeof getBadges_active === "function")
						getBadges_active();
				}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				return false;
			});
			
			return false;
		}
		
		function setCustomerStatus(id)
		{
			var data = {
					models: [
							 {
								customer_id :id,
								customer_status:"Inactiv"
							 }
							]
					};
									
			var jqxhr = $.post({
					url: window.location.origin+"/api?subject=custom&type=call&action=setCustomerStatus",
					data: JSON.stringify(data),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

				$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
				return false;
			}
			else
			{
				let d = $("#grid").data("kendoGrid").dataSource._data;
				for(i=0;i<d.length;i++)
				{
					if (d[i].customer_id == id)
					{
						$("[data-uid="+d[i].uid+"] > td.k-command-cell > button.k-grid-status").hide();
						$("[data-uid="+d[i].uid+"] > td.k-command-cell > button.k-grid-editC").addClass("ms-0");
						$("[data-uid="+d[i].uid+"] > td")[11].innerText= "Inactiv";
						
						d[i].customer_status = "Inactiv";
						$("#staticNotification").data("kendoNotification").show(d[i].customer_name + " a fost deactivata.", "info");
						break;
					}
				}
				
				if (typeof getBadges_active === "function")
					getBadges_active();
							
			}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				return false;
			});
			
			return false;
		}
		
		function CustomerDialog(e = null)
		{				
			//reset
			idCustomerID = e;
		
			if(e == null)
			{
				$("#dialog").data("kendoDialog").title("Adaugă Client");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Adaugă");
				
				$("#customer_name").data("kendoTextBox").value("");
				$("#customer_registration_number").data("kendoTextBox").value("");
				$("#customer_vat_code").data("kendoTextBox").value("");
				$("#customer_bank").data("kendoTextBox").value("");
				$("#customer_bank_account").data("kendoTextBox").value("");
				$("#customer_county").data("kendoDropDownList").value("");
				$("#customer_city").data("kendoAutoComplete").value("");
				$("#customer_address").data("kendoAutoComplete").value("");
				$("#customer_invoice_due_days").data("kendoNumericTextBox").value(30);
				$("#customer_anre_band").data("kendoDropDownList").value("Auto");
				$("#customer_status").data("kendoDropDownList").value("Activ");
			}else
			{
				var gridData = $("#grid").data("kendoGrid").dataSource.data();
					for(let i=0;i<gridData.length;i++)
					  if(e === gridData[i].id) {					  
						$("#customer_name").data("kendoTextBox").value(gridData[i].customer_name);
						$("#customer_registration_number").data("kendoTextBox").value(gridData[i].customer_registration_number);
						$("#customer_vat_code").data("kendoTextBox").value(gridData[i].customer_vat_code);
						$("#customer_bank").data("kendoTextBox").value(gridData[i].customer_bank);
						$("#customer_bank_account").data("kendoTextBox").value(gridData[i].customer_bank_account);
						$("#customer_county").data("kendoDropDownList").value(gridData[i].customer_county);
						$("#customer_city").data("kendoAutoComplete").value(gridData[i].customer_city);
						$("#customer_address").data("kendoAutoComplete").value(gridData[i].customer_address);
						$("#customer_invoice_due_days").data("kendoNumericTextBox").value(gridData[i].customer_invoice_due_days);
						$("#customer_anre_band").data("kendoDropDownList").value(gridData[i].customer_anre_band);
						$("#customer_status").data("kendoDropDownList").value(gridData[i].customer_status);
					  }
				  
				$("#dialog").data("kendoDialog").title("Editează Client");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Salvează");
			}
			
							
			$(".k-form-error").remove();
			$(".k-invalid").removeClass("k-invalid");
			
			$("#dialog").data("kendoDialog").open();
		}
		
	</script>
	'.
	$dialog->render();
}

function zonesDialog()
{
	/* form */
	$form = new \Kendo\UI\Form('cForm');
	$form->formData(array('supplier_name' => 1));
	$form->orientation("horizontal");
	$form->buttonsTemplate("<div style='width:100%;overflow:hidden;'></div>");


	$supplier = new \Kendo\UI\FormItem();
	$supplier
		->label("Furnizor")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('suppliers','supplier_name'),'dataTextField' => 'supplier_name','dataValueField'=>'supplier_id' ,"filter" => "contains", "optionLabel" => "Selectati Furnizor..."))
		->field("supplier_name")
		->validation(array("required" => true));

	$customer = new \Kendo\UI\FormItem();
	$customer
		->label("Client")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('customers','customer_name'),'dataTextField' => 'customer_name','dataValueField'=>'customer_id','cascadeFrom' => 'supplier_name', "filter" => "contains", "placeholder" => "Selectati clientul..."))
		->field("customer_name")
		->validation(array("required" => true));
		
	$zone_name = new \Kendo\UI\FormItem();
	$zone_name
		->label("Denumire")
		->editor("TextBox")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("placeholder" => "Denumire Lot"))
		->field("zone_name")
		->validation(array("required" => true));

				
	$form->addItem($supplier);
	$form->addItem($customer);
	$form->addItem($zone_name);
	$form->attributes(array("method" => "post", "autocomplete"=>"off"));

	$dialogContent = $form->render();
	
	
	/*dialog*/
	$dialog = new \Kendo\UI\Dialog('dialog');
		
	$skipAction = new \Kendo\UI\DialogAction();
	$skipAction->text("Anulează");

	$intallAction = new \Kendo\UI\DialogAction();
	$intallAction->text("Adaugă")
				 ->primary(true)
				 ->action(new \Kendo\JavaScriptFunction('function() { return saveZone(); }'));
				 
	$dialog->title('Adaugă Lot')
		   ->width('600px')

		   ->closable(true)
		   ->modal(true)
		   ->visible(false)
		   //->show('function(e) {$("#customer_vat_code").data("kendoTextBox").focus();}')
		   //->close('onClose')
		   ->addAction($skipAction, $intallAction)
		   ->content($dialogContent);
	
	echo '
	<script>
		var idZoneID = null;
		
		$( document ).ready(function() {
			$("button.k-grid-addZ").bind("click",function(){ZoneDialog();})
			$("#select-activ").data("kendoButtonGroup").select(0);
			$("#select-activ").data("kendoButtonGroup").trigger("select");			
		});
		
		function saveZone()
		{
				
			//use grid model
			var data = {
				models: [
						 {
							zone_id:idZoneID,
							supplier_id :$("#supplier_name").val(),
							customer_id :$("#customer_name").val(),
							zone_name:$("#zone_name").data("kendoTextBox").value()
						 }
						]
			};
			
			let stype = "create";
			if (idZoneID !== null) stype = "update";
				
			
			var jqxhr = $.post({
					url: window.location.origin+"/api?subject=zones&type="+stype,
					data: JSON.stringify(data),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

					$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
					return false;
				}
				else
				{
					$("#grid").data("kendoGrid").dataSource.read();
					$("#dialog").data("kendoDialog").close();
					
					if (typeof getBadges_active === "function")
						getBadges_active();
				}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				return false;
			});
			
			return false;
		}
			
		function ZoneDialog(e = null)
		{				
			//reset
			idZoneID = e;
		
			if(e == null)
			{
				$("#dialog").data("kendoDialog").title("Adaugă Lot");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Adaugă");
				$("#supplier_name").data("kendoDropDownList").enable(true);
				$("#customer_name").data("kendoDropDownList").enable(true);
				 
				$("#zone_name").data("kendoTextBox").value("");
			}else
			{
				var gridData = $("#grid").data("kendoGrid").dataSource.data();
					for(let i=0;i<gridData.length;i++)
					  if(e === gridData[i].id) {					  
						$("#zone_name").data("kendoTextBox").value(gridData[i].zone_name);
						$("#customer_name").data("kendoDropDownList").value(gridData[i].customer_id);
					  }
				 $("#supplier_name").data("kendoDropDownList").enable(false);
				 $("#customer_name").data("kendoDropDownList").enable(false);
				$("#dialog").data("kendoDialog").title("Editează Lot");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Salvează");
			}
			
							
			$(".k-form-error").remove();
			$(".k-invalid").removeClass("k-invalid");
			
			$("#dialog").data("kendoDialog").open();
		}
		
	</script>
	'.
	$dialog->render();
}

function consumptionDialog()
{
	/* form */
	$form = new \Kendo\UI\Form('tForm');
	$form->formData(array('supplier_name' => 1,'invoice_no'=>'AUTO','invoice_date'=>date('Y-m-d'),'invoice_due_date'=>(new DateTime())->add(new DateInterval('P30D'))->format('Y-m-d')));
	$form->orientation("horizontal");
	$form->buttonsTemplate("<div style='width:100%;overflow:hidden;'></div>");

	$date = new \Kendo\UI\FormItem();
	$date->label("Data")
		->editor("DatePicker")
		->editorOptions(array('format'=>'dd-MM-yyyy'))
		->field("consumption_date")
		->validation(array("required" => true));
			
	$pod = new \Kendo\UI\FormItem();
	$pod
		->label("POD")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('pods','pod_no'),'dataTextField' => 'pod_no','dataValueField'=>'pod_id', "filter" => "contains", "optionLabel" => "Selectati POD..."))
		->field("pod")
		->validation(array("required" => false))
		->hint("_");
	
	$tip = new \Kendo\UI\FormItem();
	$tip
		->label("Tip Energie")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => ['EA','ERI','ERC'], "placeholder" => "Selectati tipul..."))
		->field("energy_type")
		->validation(array("required" => true));

	$ea = new \Kendo\UI\FormItem();
	$ea->label("EA [kWh]")
		->editor("NumericTextBox")
		->field("total_consumption_ae")
		->editorOptions(array("decimals" => 0,"format" => "{0:#}"))
		->validation(array("required" => true));
	
	$er = new \Kendo\UI\FormItem();
	$er->label("ER [kVArh]")
		->editor("NumericTextBox")
		->field("total_consumption_re")
		->editorOptions(array("decimals" => 0,"format" => "{0:#}"))
		->validation(array("required" => true));
	
	$erx3 = new \Kendo\UI\FormItem();
	$erx3->label("ER x3 [kVArh]")
		->editor("NumericTextBox")
		->field("total_consumption_re_3x")
		->editorOptions(array("decimals" => 0,"format" => "{0:#}"))
		->validation(array("required" => true));
		
	$form->addItem($date);
	$form->addItem($pod);
	$form->addItem($tip);
	$form->addItem($ea);
	$form->addItem($er);
	$form->addItem($erx3);
	$form->change("onConsumptionChange");
	$form->attributes(array("method" => "post", "autocomplete"=>"off"));

	$dialogContent = $form->render();
	
	
	/*dialog*/
	$dialog = new \Kendo\UI\Dialog('dialog');
	
	$skipAction = new \Kendo\UI\DialogAction();
	$skipAction->text("Anulează");

	$intallAction = new \Kendo\UI\DialogAction();
	$intallAction->text("Adaugă")
				 ->primary(true)
				 ->action(new \Kendo\JavaScriptFunction('function() { return saveConsumption(); }'));
				 
	$dialog->title('Adaugă Consum')
		   ->width('600px')

		   ->closable(true)
		   ->modal(true)
		   ->visible(false)
		   ->show('function(e) {$("#pod").data("kendoDropDownList").focus();}')
		   //->close('onClose')
		   ->addAction($skipAction, $intallAction)
		   ->content($dialogContent);
	
	echo '
	<script>
		var idConsumptionID = null;
		var mUnit = "kWh";
		
		$( document ).ready(function() {
			$("button.k-grid-addI").bind("click",function(){ConsumptionDialog();})				
		});
		
		function onConsumptionChange(e)
		{
			//update invoice due date
			if(e.field == "pod")
			{
				$("#pod-form-hint").html("<i>"+e.value.customer_name+"</i>");		
			}
			
			if(e.field == "energy_type")
			{
				if(e.value == "EA")
				{
					$("#total_consumption_ae").data("kendoNumericTextBox").enable(true);
					$("#total_consumption_re").data("kendoNumericTextBox").enable(false);
					$("#total_consumption_re_3x").data("kendoNumericTextBox").enable(false);
					
					$("#total_consumption_re").data("kendoNumericTextBox").value(0);
					$("#total_consumption_re_3x").data("kendoNumericTextBox").value(0);
					
					mUnit = "kWh";
				}
				else
				{
					$("#total_consumption_ae").data("kendoNumericTextBox").enable(false);
					$("#total_consumption_re").data("kendoNumericTextBox").enable(true);
					$("#total_consumption_re_3x").data("kendoNumericTextBox").enable(true);
					
					$("#total_consumption_ae").data("kendoNumericTextBox").value(0);
					
					mUnit = "kVArh";
				}
			}
		}
		
		function saveConsumption()
		{
			//use grid model
			var data = {
				models: [
						 {
							supplier_id:supplierID,
							consumption_id:idConsumptionID,
							pod :$("#pod").data("kendoDropDownList").text(),
							energy_type: $("#energy_type").data("kendoDropDownList").value(),
							total_consumption_mu: mUnit,
							consumption_date: kendo.toString($("#consumption_date").data("kendoDatePicker").value(),"yyyy-MM-dd"),
							total_consumption_ae: $("#total_consumption_ae").data("kendoNumericTextBox").value(),
							total_consumption_re: $("#total_consumption_re").data("kendoNumericTextBox").value(),
							total_consumption_re_3x: $("#total_consumption_re_3x").data("kendoNumericTextBox").value()
						 }
						]
			};
			
			var jqxhr = $.post({
					url: window.location.origin+"/api?action=dialog&subject=consumptionsDialogData&type=save",
					data: JSON.stringify(data),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

					$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
					return false;
				}
				else
				{
					
					if (typeof response !== "undefined" && typeof response.error !== "undefined")
						$("#staticNotification").data("kendoNotification").show(response.msg, "error");
					else
					{
						$("#grid").data("kendoGrid").dataSource.read();
						$("#dialog").data("kendoDialog").close();
					}
				}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				return false;
			});
			
			return false;
		}
		
		function ConsumptionDialog(e = null)
		{
			idConsumptionID = e;
		
			if(e == null)
			{
				$("#dialog").data("kendoDialog").title("Adaugă Consum");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Adaugă");
				
				$("#consumption_date").data("kendoDatePicker").value(new Date(selYear_consumption_date,selMonth_consumption_date,1));
				
				var gridData = $("#grid").data("kendoGrid").dataSource.data();
				$("#pod").data("kendoDropDownList").text($("tr[aria-rowindex=2] td:nth-child(6)").text());
				$("#pod-form-hint").html("<i>"+$("tr[aria-rowindex=2] td:nth-child(4)").text()+"</i>");
				$("#pod").data("kendoDropDownList").enable(true);

				$("#energy_type").data("kendoDropDownList").value("EA");
				mUnit = "kWh";
							
				$("#total_consumption_ae").data("kendoNumericTextBox").value(0);
				$("#total_consumption_re").data("kendoNumericTextBox").value(0);
				$("#total_consumption_re_3x").data("kendoNumericTextBox").value(0);
				
				$("#total_consumption_re").data("kendoNumericTextBox").enable(false); 
				$("#total_consumption_re_3x").data("kendoNumericTextBox").enable(false);
				$("#total_consumption_ae").data("kendoNumericTextBox").enable(true);

				
			}else
			{
				var gridData = $("#grid").data("kendoGrid").dataSource.data();
				let ffound = false;
				for(let i=0;i<gridData.length;i++)
				{
				  if(e === gridData[i].id) {
						
					$("#consumption_date").data("kendoDatePicker").value(gridData[i].consumption_date);
					
					$("#pod").data("kendoDropDownList").text(gridData[i].pod);
					$("#pod-form-hint").html("<i>"+gridData[i].customer_name+"</i>");
					
					$("#energy_type").data("kendoDropDownList").value(gridData[i].energy_type);
					mUnit = gridData[i].total_consumption_mu;
					
					$("#total_consumption_ae").data("kendoNumericTextBox").value(gridData[i].total_consumption_ae);
					$("#total_consumption_re").data("kendoNumericTextBox").value(gridData[i].total_consumption_re);
					$("#total_consumption_re_3x").data("kendoNumericTextBox").value(gridData[i].total_consumption_re_3x);
				  
					ffound = true;
				  }
				  
				  if(ffound) break;
				}
				
				$("#pod").data("kendoDropDownList").enable(false);
				if($("#energy_type").data("kendoDropDownList").value() == "EA")
				{
					$("#total_consumption_re").data("kendoNumericTextBox").enable(false); 
					$("#total_consumption_re_3x").data("kendoNumericTextBox").enable(false);
					$("#total_consumption_ae").data("kendoNumericTextBox").enable(true);
				}
				else
				{
					$("#total_consumption_re").data("kendoNumericTextBox").enable(true); 
					$("#total_consumption_re_3x").data("kendoNumericTextBox").enable(true);
					$("#total_consumption_ae").data("kendoNumericTextBox").enable(false);
				}
				
				$("#dialog").data("kendoDialog").title("Editează Consum");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Salvează");
			}
			
							
			$(".k-form-error").remove();
			$(".k-invalid").removeClass("k-invalid");
			
			$("#dialog").data("kendoDialog").open();
		}
		
	</script>
	'.
	$dialog->render();
}


function curveVarianceDialog()
{
	/* form */
	$form = new \Kendo\UI\Form('tForm');
	$form->formData(array('supplier_name' => 1));
	$form->orientation("horizontal");
	$form->buttonsTemplate("<div style='width:100%;overflow:hidden;'></div>");
		
	$pod = new \Kendo\UI\FormItem();
	$pod
		->label("POD")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('pods','pod_no'),'dataTextField' => 'pod_no','dataValueField'=>'pod_id', "filter" => "contains", "optionLabel" => "Selectati POD..."))
		->field("pod")
		->validation(array("required" => false))
		->hint("_");
	
	$curba = new \Kendo\UI\FormItem();
	$curba
		->label("Curba")
		->editor("DropDownList")
		->attributes(array("maxlength" => "50"))
		->editorOptions(array("dataSource" => dataSource('actual_curves','curve_id'),'dataTextField' => 'curve_name','dataValueField'=>'curve_id', "filter" => "contains", "optionLabel" => "Selectati Curba..."))
		->field("curve_name")
		->validation(array("required" => true));

	$profil = new \Kendo\UI\FormItem();
	$profil->label("Profil")
		->editor("TextBox")
		->field("profile")
		->validation(array("required" => false));
	
	
	$form->addItem($pod);
	$form->addItem($curba);
	$form->addItem($profil);
	$form->change("onFormChange");
	$form->attributes(array("method" => "post", "autocomplete"=>"off"));

	$dialogContent = $form->render();
	
	
	/*dialog*/
	$dialog = new \Kendo\UI\Dialog('dialog');
	
	$skipAction = new \Kendo\UI\DialogAction();
	$skipAction->text("Anulează");

	$intallAction = new \Kendo\UI\DialogAction();
	$intallAction->text("Adaugă")
				 ->primary(true)
				 ->action(new \Kendo\JavaScriptFunction('function() { return saveCurveVariance(); }'));
				 
	$dialog->title('Adaugă Variatie Curba')
		   ->width('600px')

		   ->closable(true)
		   ->modal(true)
		   ->visible(false)
		   ->show('function(e) {$("#pod").data("kendoDropDownList").focus();}')
		   //->close('onClose')
		   ->addAction($skipAction, $intallAction)
		   ->content($dialogContent);
	
	echo '
	<script>
		var idConsumptionID = null;
		var mUnit = "kWh";
		
		$( document ).ready(function() {
			$("button.k-grid-addCV").bind("click",function(){CurveVarianceDialog();})				
		});
		
		function onFormChange(e)
		{
			//update invoice due date
			if(e.field == "pod")
			{
				$("#pod-form-hint").html("<i>"+e.value.customer_name+"</i>");		
			}
		}
		
		function saveCurveVariance()
		{
			//use grid model
			var data = {
				models: [
						 {
							supplier_id:supplierID,
							acv_id:null,
							pod :$("#pod").data("kendoDropDownList").text(),
							consumption_date: kendo.toString(new Date(selYear_consumption_date,selMonth_consumption_date,1) ,"yyyy-MM-dd"),
							curve_id: $("#curve_name").data("kendoDropDownList").value(),
							consumption_profile: $("#profile").data("kendoTextBox").value()
						 }
						]
			};
			
			var jqxhr = $.post({
					url: window.location.origin+"/api?subject=actual_curves_variance&type=create",
					data: JSON.stringify(data),
					contentType: "application/json; charset=utf-8"})
			.done(function(response) {
				if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

					$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
					return false;
				}
				else
				{
					
					if (typeof response !== "undefined" && typeof response.error !== "undefined")
						$("#staticNotification").data("kendoNotification").show(response.msg, "error");
					else
					{
						$("#grid").data("kendoGrid").dataSource.read();
						$("#dialog").data("kendoDialog").close();
					}
				}
			})
			.fail(function() {
				//probleme de retea
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
				return false;
			});
			
			return false;
		}
		
		function CurveVarianceDialog(e = null)
		{
			idConsumptionID = e;
		
			if(e == null)
			{
				$("#dialog").data("kendoDialog").title("Adaugă Variatie Curba");
				$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Adaugă");
				$("#pod-form-hint").html("<i> - </i>");				
				$("#pod").data("kendoDropDownList").value("");
				$("#curve_name").data("kendoDropDownList").value("");
				$("#profile").data("kendoTextBox").value("");

				
			}
									
			$(".k-form-error").remove();
			$(".k-invalid").removeClass("k-invalid");
			
			$("#dialog").data("kendoDialog").open();
		}
		
	</script>
	'.
	$dialog->render();
}
?>