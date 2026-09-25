function refreshGrid() {
	$("#grid").data("kendoGrid").dataSource.read();
}

function editReadings(e)
{
	
	let distributor_name = "";
	let curve_name = "";
	
	if(e)
	{
		let d = $("#grid").data("kendoGrid").dataSource.get(e);
		
		distributor_name = d.distributor_name;
		curve_name = d.curve_name;
	}
	else return;
	
	$("body").append('<div id="dialog">');
		
	dialog = $('#dialog');

	dialog.kendoDialog({
		width: "550px",
		title: e ? "Editeaza Distribuitor" : "Creaza Date Orare",
		closable: true,
		modal: true,
		close: function(e){e.sender.destroy();},
		content: "<form id='readingsForm'></form>",
		actions: [
			{ text: 'Anulare' },
			{
			  text: e ? "Salveaza" : "Adauga",
			  action: function(d){
				  // e.sender is a reference to the dialog widget object
				  // OK action was clicked
				  
						var data = {
						models: [
								 {
									supplier_id: supplierID,
									distributor_name: distributor_name,
									curve_name: curve_name,
									year:$("#DatePicker").data("kendoDatePicker").value().getFullYear(),
									month:$("#DatePicker").data("kendoDatePicker").value().getMonth()+1,
									new_distributor_name:$("#distributor_name").data("kendoDropDownList").text()
								 }
								]
						};
					
						apiCall(data,'actual_data',e ? 'update' : 'create',refreshGrid);
			  },
			  primary: true
			}
		]
	});

	$("#readingsForm").kendoForm({
		buttonsTemplate:"",
		orientation: "horizontal",
		formData: {
			distributor_name: distributor_name
		},
		layout: "grid",
		items: [
				{ 
					field: "distributor_name", 
					editor: "DropDownList", 
					label: "Distribuitor", 
					validation: { required: false }, 
					editorOptions: {
					dataSource:dataSource('distributors', {distributor_id: { type: "number" }}),
						dataValueField:"distributor_name",//"distributor_id",
						dataTextField:"distributor_name"
						
					}
				}
		]
	});	
	
	dialog.data("kendoDialog").center();
}