function ProcastRegionDialog()
{
	    kendo.prompt("Denumire Regiune Noua", "")
        .done(function(txtData){
/* The result can be observed in the DevTools(F12) console of the browser. */
			if(txtData.length == 0) return;
			
			var data = {
			models: [
					 {
						supplier_id: supplierID,
						region_name: txtData
					 }
					]
			};
		
			apiCall(data,'procast_regions','create',refreshGrid);
        })
        .fail(function(txtData){
/* The result can be observed in the DevTools(F12) console of the browser. */
        });
	
	//detect enter on input
	$("[role='alertdialog'] input").on('keyup', function (e) {
    if (e.key === 'Enter' || e.keyCode === 13) {
		console.log("enter");
        $("[role='alertdialog'] .k-button-solid-primary").click();
    } });
}

function refreshData() {
	$("#grid").data("kendoGrid").dataSource.read();
	$("#county").data("kendoDropDownList").dataSource.read();
}

function refreshGrid() {
	$("#grid").data("kendoGrid").dataSource.read();
}

function ProcastRCDialog()
{

	$("body").append('<div id="dialog">');
		
	dialog = $('#dialog');

	dialog.kendoDialog({
		width: "450px",
		title: "Aloca Judet",
		closable: true,
		modal: true,
		close: function(e){e.sender.destroy();},
		content: "<form id='countyForm'></form>",
		actions: [
			{ text: 'Anulare' },
			{
			  text: "Aloca Judet",
			  action: function(e){
				  // e.sender is a reference to the dialog widget object
				  // OK action was clicked
				  
				  		if($("#county").data("kendoDropDownList").value() == '') 
						{ 
							$('#staticNotification').data('kendoNotification').show("Selectati judetul...", 'error');
							return false;
						}
			
						var data = {
						models: [
								 {
									supplier_id: supplierID,
									region_id: $("#region_name").data("kendoDropDownList").value(),
									county:$("#county").data("kendoDropDownList").value()
								 }
								]
						};
					
						apiCall(data,'procast_region_counties','create',refreshData);
				  
				  // Returning false will prevent the closing of the dialog
				  if($("#keepOpen").data("kendoSwitch").value())  return false;
				  else return true;
			  },
			  primary: true
			}
		]
	});
	
		
	$("#countyForm").kendoForm({
		buttonsTemplate:"",
		orientation: "horizontal",
		formData: {
			region_name: "",
			county: "",
			keepOpen: false
		},
		layout: "grid",
		items: [
				{ 
					field: "region_name", 
					editor: "DropDownList", 
					label: "Regiune", 
					validation: { required: false }, 
					editorOptions: {
						optionLabel: "Select...",
						dataSource: {
							schema: {
								type:"json",						 
								model: {
								  id: "region_id"
								},
								data: "data",
								total:"total",
								data: function(response) {				 
									if(response.message !== undefined)
										kendo.alert("&#128711; " + response.message);
									if(response.error !== undefined)
										kendo.alert("&#128711; " + response.error);
									return response.data;
								}
							},
							transport: {
								read: 
								{
									url: window.location.origin + "/api?subject=procast_regions&type=read&action=grid",
									dataType: "json",
									type:"post",
									data: function() {
										return {
												"filter": {
													"logic": "and",
													"filters": [
														{
															"field": "supplier_id",
															"operator": "eq",
															"value": supplierID
														}
													]
												}
										}                  
									}           
								},
								parameterMap: function (options, type) {
								  return kendo.stringify(options);
								}
							}, 
							sort: { field: "region_name", dir: "asc" },
							pageSize: 10000
						},
						dataTextField: "region_name",
						dataValueField: "region_id",
						dataBound: function(e){e.sender.select(e.sender.dataSource.data()[0].region_id);}
					}
				},
				{ 
					field: "county", 
					editor: "DropDownList", 
					label: "Judet", 
					validation: { required: false }, 
					editorOptions: {
						optionLabel: "Select...",
						filter: "contains",
						dataSource: {
							schema: {
								type:"json",						 
								model: {
								  id: "county"
								},
								data: "data",
								total:"total",
								data: function(response) {				 
									if(response.message !== undefined)
										kendo.alert("&#128711; " + response.message);
									if(response.error !== undefined)
										kendo.alert("&#128711; " + response.error);
									return response.data;
								}
							},
							transport: {
								read: 
								{
									url: window.location.origin + "/api?subject=procaast_unallocated_counties&type=read&action=grid",
									dataType: "json",
									type:"post",
									data: function() {
										return {
												"filter": {
													"logic": "and",
													"filters": [
														{
															"field": "supplier_id",
															"operator": "eq",
															"value": supplierID
														}
													]
												}
										}                  
									}           
								},
								parameterMap: function (options, type) {
								  return kendo.stringify(options);
								}
							}, 
							sort: { field: "county", dir: "asc" },
							pageSize: 10000
						},
						dataValueField: "county",
						dataTextField: "county",
					}
				},
				{ 
					field: "keepOpen", 
					label: "Pastreaza fereastra deschisa", 
					editor: "Switch", 
					validation: { required: false },
				}
		]
	});

	
}
