function moveUP(id)
{
	customCall({models:[{id_field:"fct_id",id_value:id,subject:'forecast_consumption_types',direction:'down'}]},'reorder',refreshGrid);
}

function moveDown(id)
{
	customCall({models:[{id_field:"fct_id",id_value:id,subject:'forecast_consumption_types',direction:'up'}]},'reorder',refreshGrid);	
}

function refreshGrid()
{
	$("#grid").data("kendoGrid").dataSource.read();
}

/*
var dialog;
      
function onDialogClose() {
	//to do
}

function addCT() 
{
	dialog.data("kendoDialog").open();
}


 $(document).ready(function () {
	
	$("body").append('<div id="dialog"><div id="validation-success"></div></div>');
	
	
	dialog = $('#dialog');

	dialog.kendoDialog({
		width: "450px",
		title: "Software Update",
		closable: false,
		modal: true,
		content: "<form id='dialogform'></form>",
		actions: [
			{ text: 'Skip this version' },
			{ text: 'Remind me later' },
			{ text: 'Install update', primary: true }
		],
		close: onDialogClose
	});
	
	
	var validationSuccess = $("#validation-success");
	
	$("#dialogform").kendoForm({
		formData: {
			FirstName: "John",
			LastName: "Doe",
			Email: "john.doe@email.com",
			Country: "1",
			City: "Strasbourg",
			AddressLine: ""
		},
		layout: "grid",
		grid: {
			cols: 2,
			gutter: 20
		},
		items: [
			{
				type: "group",
				label: "Personal Information",
				layout: "grid",
				grid: { cols: 1, gutter: 10},
				items: [
					{ 
						field: "FirstName", 
						label: "First Name:", 
						validation: { required: true } 
					},
					{ 
						field: "LastName", 
						label: "Last Name:", 
						validation: { required: true } 
					},
					{ 
						field: "Email", 
						label: "Email", 
						validation: { 
							required: true, 
							email: true 
						}
					}
				]
			},
			{
				type: "group",
				label: "Shipping Address",
				layout: "grid",
				grid: { cols: 2, gutter: 10 },                          
				items: [
					{ 
						field: "Country", 
						editor: "DropDownList", 
						label: "Country", 
						validation: { required: true }, 
						colSpan: 1,
						editorOptions: {
							optionLabel: "Select...",
							dataSource: [
								{ Name: "France", Id: 1 },
								{ Name: "Germany", Id: 2 },
								{ Name: "Italy", Id: 3 },
								{ Name: "Spain", Id: 4 }
							],
							dataTextField: "Name",
							dataValueField: "Id"
						}
					},
					{ 
						field: "City", 
						label: "City", 
						validation: { required: true },
						colSpan: 1,
					},
					{ 
						field: "AddressLine", 
						label: "Address Line", 
						colSpan: 2,
						validation: { required: true } 
					},
				]
			}
		],
		validateField: function(e) {
			validationSuccess.html("");
		},
		submit: function(e) {
			e.preventDefault();
			validationSuccess.html("<div class='k-messagebox k-messagebox-success'>Form data is valid!</div>");
		},
		clear: function(ev) {
			validationSuccess.html("");
		}
	});
});
*/
