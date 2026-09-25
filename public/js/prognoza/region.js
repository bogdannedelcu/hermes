function refreshGrid() {
	$("#grid").data("kendoGrid").dataSource.read();
}

function ProcastRegionDialog(e)
{
	
	let region_name = "";
	let rlat = 44.4358;
	let rlon = 26.0993;
	let rdec = 45;
	let raz = 0;
	let rkwp = 1000;
	let region_id = null;
	
	if(e)
	{
		let d = $("#grid").data("kendoGrid").dataSource.get(e);
		
		region_id = d.region_id;
		region_name = d.region_name;
		rlat = d.rlat;
		rlon = d.rlon;
		rdec = d.rdec;
		raz = d.raz;
		rkwp = d.rkwp;
	}
	
	$("body").append('<div id="dialog">');
		
	dialog = $('#dialog');

	dialog.kendoDialog({
		width: "550px",
		title: e ? "Editeaza Regiune" : "Creaza Regiune",
		closable: true,
		modal: true,
		close: function(e){e.sender.destroy();},
		content: "<form id='regionForm'></form>",
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
									region_id: region_id,
									supplier_id: supplierID,
									region_name: $("#region_name").data("kendoTextBox").value(),
									rlat: $("#rlat").data("kendoNumericTextBox").value(),
									rlon: $("#rlon").data("kendoNumericTextBox").value(),
									rdec: $("#rdec").data("kendoNumericTextBox").value(),
									raz: $("#raz").data("kendoNumericTextBox").value(),
									rkwp: $("#rkwp").data("kendoNumericTextBox").value()
								 }
								]
						};
					
						apiCall(data,'procast_regions',e ? 'update' : 'create',refreshGrid);
				  
				  // Returning false will prevent the closing of the dialog
				  if($("#keepOpen").data("kendoSwitch").value())  return false;
				  else return true;
			  },
			  primary: true
			}
		]
	});
	
	$("#regionForm").kendoForm({
		buttonsTemplate:"",
		orientation: "horizontal",
		formData: {
			region_name: region_name,
			rlat: rlat,
			rlon: rlon,
			rdec: rdec,
			raz: raz,
			rkwp: rkwp,
			keepOpen: false
		},
		layout: "grid",
		items: [
				{ 
					field: "region_name", 
					editor: "TextBox", 
					label: "Regiune", 
					validation: { required: false }, 
					editorOptions: {
					}
				},
				{ 
					field: "rlat", 
					editor: "NumericTextBox", 
					label: "Latitudine", 
					hint: "-90 (sud) … 90 (nord)",
					validation: { required: false }, 
					editorOptions: {
						decimals:4,
						format:"n4"
					}
				},
				{ 
					field: "rlon", 
					editor: "NumericTextBox", 
					label: "Longitudine", 
					hint: "-180 (vest) … 180 (est)",
					validation: { required: false }, 
					editorOptions: {
						decimals:4,
						format:"n4"
					}
				},
				{ 
					field: "rdec", 
					editor: "NumericTextBox", 
					label: "Inclinare", 
					hint: "0 (orizontal) … 90 (vertical)",
					validation: { required: false }, 
					editorOptions: {
						decimals:0,
						format:"n0"
					}
				},
				{ 
					field: "raz", 
					editor: "NumericTextBox", 
					label: "Orientare",
					hint: "-180 = nord, -90 = est, 0 = sud, 90 = vest, 180 = nord",
					validation: { required: false }, 
					editorOptions: {
						decimals:0,
						format:"n0"
					}
				},
				{ 
					field: "rkwp", 
					editor: "NumericTextBox", 
					label: "Putere instalata (KWh)",
					validation: { required: false }, 
					editorOptions: {
						decimals:0,
						format:"n0"
					}
				},				{ 
					field: "keepOpen", 
					label: "Pastreaza fereastra deschisa", 
					editor: "Switch", 
					validation: { required: false },
				}
		]
	});	
	
	dialog.data("kendoDialog").center();
}