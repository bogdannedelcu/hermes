function refreshGrid() {
	$("#grid").data("kendoGrid").dataSource.read();
}

function MonthCorrelationDialog()
{
	if($('#dialog').length == 0)
		$("body").append('<div id="dialog">');
		
	dialog = $('#dialog');

	dialog.kendoDialog({
		width: "550px",
		title: "Adauga Corelatie",
		closable: true,
		modal: true,
		close: function(e){
			   e.sender.destroy();
			   $('.k-dialog').remove();
			},
		content: "<form id='monthCorrelationForm' style='height:600px;'></form>",
		actions: [
			{ text: 'Anulare' },
			{
			  text: "Salveaza",
			  action: function(d){
				  // e.sender is a reference to the dialog widget object
				  // OK action was clicked
				  
					var data = {
					models: [
							 {
								supplier_id: supplierID,
								month: $("#month").data("kendoDropDownList").value(),
								correlated_months: $("#correlated_months").data("kendoCheckBoxGroup").value()
							 }
							]
					};
				
					apiCall(data,'months_correlation', 'update', refreshGrid);
			  
				  // Returning false will prevent the closing of the dialog
				  if($("#keepOpen").data("kendoSwitch").value())  return false;
				  else return true;

			  },
			  primary: true
			}
		]
	});
	
	var correlated_months = [];
	// Use the value of the widget
	let data = $("#grid").data("kendoGrid").dataSource.data();
	data.forEach(d => {
		if(d.month == 1)
		{
			correlated_months.push(d.correlated_month);
		}
	});
	
	$("#monthCorrelationForm").kendoForm({
		buttonsTemplate:"",
		orientation: "horizontal",
		formData: {
			month: 1,
			keepOpen: true,
			correlated_months:correlated_months
		},
		layout: "grid",
		items: [
				{ 
					field: "month", 
					editor: "DropDownList", 
					label: "Luna", 
					validation: { required: false }, 
					editorOptions: {
						dataTextField:"label",
						dataValueField:"value",
						dataSource: {
							data: [
								{value:1,label:"Ianuarie"}, {value:2,label:"Februarie"}, {value:3,label:"Martie"},
									{value:4,label:"Aprilie"}, {value:5,label:"Mai"}, {value:6,label:"Iunie"},
									{value:7,label:"Iulie"}, {value:8,label:"August"}, {value:9,label:"Septembrie"},
									{value:10,label:"Octombrie"}, {value:11,label:"Noiembrie"}, {value:12,label:"Decembrie"}]
						},
						height: 500,
						valueTemplate: "<strong>#=label#</strong>",
						change: function(e) {
							var value = this.value();
							var correlated_months = [];
							// Use the value of the widget
							let data = $("#grid").data("kendoGrid").dataSource.data();
							data.forEach(d => {
								if(d.month == value)
								{
									correlated_months.push(d.correlated_month);
								}
							});
							
							$("#correlated_months").data("kendoCheckBoxGroup").value(correlated_months);
						}
					}
				},
				{ 
					field: "correlated_months", 
					editor: "CheckBoxGroup", 
					label: "Corelari", 
					validation: { required: false },
					editorOptions: {
						 items: [ 	{value:1,label:"Ianuarie"}, {value:2,label:"Februarie"}, {value:3,label:"Martie"},
									{value:4,label:"Aprilie"}, {value:5,label:"Mai"}, {value:6,label:"Iunie"},
									{value:7,label:"Iulie"}, {value:8,label:"August"}, {value:9,label:"Septembrie"},
									{value:10,label:"Octombrie"}, {value:11,label:"Noiembrie"}, {value:12,label:"Decembrie"}]
					}
				},	
				{ 
					field: "keepOpen", 
					label: "Pastreaza fereastra deschisa", 
					editor: "Switch", 
					validation: { required: false },
				},				
		]
	});	
	
	dialog.data("kendoDialog").center();
}