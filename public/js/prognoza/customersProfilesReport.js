$( document ).ready(function() {
	
	let gridCommands = 	[{ command:
			[{ 	name: "editItem",
				iconClass:"k-icon k-i-pencil",
				text: "",
				click: function(e){	e.preventDefault();	var tr = $(e.target).closest("tr");	item = this.dataItem(tr); CustomerConsumptionTypeDialog(item);},
				title:"Edit"							
			},{ 	
				name: "destroy",
				text: "",
				visible:function(dataItem) { return dataItem.deletable},
			}],
			title: "", minScreenWidth: 801, width: "120px", attributes: { style: "text-align: center" },
			headerTemplate:''
			//headerTemplate:'<button type="button" class="k-col-pdf multiDownload k-state-disabled k-button k-button-md k-rounded-md k-button-solid k-button-solid-base k-icon-button" onclick="multiPDFDownload()"><span class="k-icon k-i-pdf k-button-icon"></span></button> \
			//				<button type="button" class="k-col-xml multiDownload k-state-disabled k-button k-button-md k-rounded-md k-button-solid k-button-solid-base k-icon-button" onclick="multiXMLDownload()"><span class="k-icon k-i-zip k-button-icon"></span></button>'
	}];
	
	let gridColumns = inaGridColumns(false, gridCommands, ["customer_name","pod","consumption_type_name","profile_name","synthetic_type",""], 
										   titles=["Client","POD", 'Profil Consum', 'Profil Zile Libere','Sintetice',''], 
										   types=["string","string","string", "string","string"]);
	
	gridColumns[2].template = "#=(pod==''?'Toate':pod)#";
	
	let gridToolBar = [{template:'<a id="addProfile" class="k-button k-button-icontext"><span class="k-icon k-i-plus"></span>Adaugă</a>'}];
	
	inaGrid('forecast_customers_consumption_types', 'grid', inaDS({subject:"forecast_customers_consumption_types", model:{id:"fcct_id"},filter:{field: "supplier_id",operator: "eq",value: 1},serverFiltering:true}), gridColumns, gridToolBar);
		
	$("#grid").data("kendoGrid").dataSource.read();
	
	$("#addProfile").kendoButton({
			click: function() {CustomerConsumptionTypeDialog(null)}
		});
		
	dialog = $('<div id="dialog" />').appendTo('body');
	$('<form id="cctForm" />').appendTo('body');
	
	// Form configuration
	let form = $("#cctForm").kendoForm({
		formData: { supplier_name: 1 },
		orientation: "horizontal",
		buttonsTemplate: "<div style='width:100%;overflow:hidden;'></div>",
		items: [{
			field: "customer",
			label: "Client",
			editor: "DropDownList",
			editorOptions: {
				dataSource: inaDS({subject:"customers", model:{id:"customer_id"}, filter:{field: "customer_status",operator: "eq",value: "activ"}}),
				dataTextField: "customer_name",
				dataValueField: "customer_id",
				filter: "contains",
				optionLabel: "Selectati Client...",
				change: function() {
				  $("#c_pod").data("kendoDropDownList").dataSource.read();
				}
			},
			validation: { required: true }
		}, {
			field: "c_pod",
			label: "POD",
			editor: "DropDownList",
			editorOptions: {
				dataSource: inaDS({subject:"forecast_view_available_pods", model:{id:"pod_no"}}),
				dataTextField: "pod_no",
				dataValueField: "pod_no",
				cascadeFrom: "customer",
				filter: "contains",
				optionLabel: "Toate POD-urile"
			},
			validation: { required: false }
		}, {
			field: "consumption_type",
			label: "Profil Consum",
			editor: "DropDownList",
			editorOptions: {
				dataSource: inaDS({subject:"forecast_consumption_types", model:{id:"fct_id"}}),
				dataTextField: "consumption_type_name",
				dataValueField: "fct_id",
				filter: "contains",
				optionLabel: "Selectati Tip Consum..."
			},
			validation: { required: true }
		}, {
			field: "profile_name",
			label: "Profil Zile Libere",
			editor: "DropDownList",
			editorOptions: {
				dataSource: inaDS({subject:"working_free_days_profiles", model:{id:"wfd_profile_id"}}),
				dataTextField: "profile_name",
				dataValueField: "wfd_profile_id",
				filter: "contains",
				optionLabel: "Selectati Profil Zile Libere..."
			},
			validation: { required: true }
		}],
		attributes: { method: "post", autocomplete: "off" }
	});

	//create estimation types
	customCall({models:[{}]},'getFilterEstimationTypes',createEstimationTypesFilter);
	
	// Dialog configuration
	$("#dialog").kendoDialog({
		title: "Adaugă Tip Consum",
		content:form,
		width: "600px",
		closable: true,
		modal: true,
		visible: false,
		actions: [
			{ text: "Anulează" },
			{ text: "Adaugă", primary: true, action: saveCustomerConsumptionType }
		]
	});
});

function createEstimationTypesFilter(response)
{
	inaFilterButtons("estimation_types_options", "estimation_types", -1, "Tip Estimare",response,refreshGrid,'','minw-100px my-3',true);	
}

function refreshGrid()
{
	resizeGrid();
	
	if(inaOptions['estimation_types'].selectedValue != -1)
	{
		var grid = $("#grid").data("kendoGrid");

		// Get the value to filter by
		var selectedValue = inaOptions['estimation_types'].selectedValue;

		// Define the filter for the single field
		var filter = {
			field: "consumption_type_name",   // The field to filter on
			operator: "eq",          // Equality operator
			value: selectedValue     // The value to match
		};

		// Apply the filter to the grid
		grid.dataSource.filter(filter);
	}
	else
		$("#grid").data("kendoGrid").dataSource.read();
}

var customerConsumptionTypeId = null;

function CustomerConsumptionTypeDialog(e = null)
{ 
	if(e == null)
	{
		customerConsumptionTypeId = e;
		
		$("#dialog").data("kendoDialog").title("Adaugă Tip Consum/POD");
		$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Adaugă");
		$("#customer").data("kendoDropDownList").enable(true);
		
		let genItem = $("#consumption_type").data("kendoDropDownList").dataSource.data().find((element)=>{return element.consumption_type_name=="General"});
		$("#consumption_type").data("kendoDropDownList").value(genItem.fct_id);
		
		genItem = $("#profile_name").data("kendoDropDownList").dataSource.data().find((element)=>{return element.profile_name=="General"});
		$("#profile_name").data("kendoDropDownList").value(genItem.wfd_profile_id);
		
	}
	else
	{
		$("#dialog").data("kendoDialog").title("Editează Tip Consum/Consumator");
		$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Salvează");
		
		let ds = $("#grid").data("kendoGrid").dataSource; 
		let cData = e;
		customerConsumptionTypeId = cData.fcct_id;
	
		$("#customer").data("kendoDropDownList").value(cData.customer_id);
		$("#customer").data("kendoDropDownList").enable(false);
		
		var dds = $("#c_pod").data("kendoDropDownList").dataSource;	
		if(cData.pod != "" && dds.get(cData.pod) === undefined) 
		{
			dds.add({id:cData.pod, pod_no:cData.pod, customer_id:cData.customer_id});
			$("#c_pod").data("kendoDropDownList").value(cData.pod);
		}
		else
			$("#c_pod").data("kendoDropDownList").value(cData.pod)

		
		if(cData.fct_id != "") $("#consumption_type").data("kendoDropDownList").value(cData.fct_id);
		$("#profile_name").data("kendoDropDownList").value(cData.wfd_profile_id);

	}			
	
							
	$(".k-form-error").remove();
	$(".k-invalid").removeClass("k-invalid");
	
	$("#dialog").data("kendoDialog").open();

}
		
function saveCustomerConsumptionType()
{
	//use grid model
	var data = {
		models: [
				 {
					fcct_id:customerConsumptionTypeId,
					customer_id:$("#customer").data("kendoDropDownList").value(),
					pod:$("#c_pod").data("kendoDropDownList").value(),
					fct_id:$("#consumption_type").data("kendoDropDownList").value(),
					wfd_profile_id:$("#profile_name").data("kendoDropDownList").value(),
				 }
				]
	};
	
	let uType = "create";
	if(customerConsumptionTypeId != null) uType = "update";
	
	var jqxhr = $.post({
			url: window.location.origin+"/api?subject=forecast_customers_consumption_types&type="+uType,
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
				$("#c_pod").data("kendoDropDownList").dataSource.read();
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