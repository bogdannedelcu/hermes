var dialog = $("#dialog");
var weekDays= {0:'',1:'Duminica',2:'Luni',3:'Marti',4:'Miercuri',5:'Joi',6:'Vineri',7:'Sambata'};
var newProfileID = '';
var deleteteProfileID = '';
var prevProfileID = 0;

$( document ).ready(function() {
	
		getMinMaxYears('working_free_days','free_date',createPeriodFilter);
				
        $("#selectWFDProfile").kendoDropDownList({
			filter: "contains",
			height: 400,
			optionLabel: 'ADAUGA PROFIL',
			optionLabelTemplate: "<span class = 'me-2'>ADAUGA PROFIL</span><span><button type='button' class='destroy k-grid-delete k-button k-button-md k-rounded-md k-button-solid k-button-solid-base k-icon-button'> <span class='k-icon k-i-add k-button-icon'></span> </button></span>",
			dataTextField: "profile_name",
			dataValueField: "wfd_profile_id",
			template:"<span class='fw-bold align-middle'>#=profile_name#</span><span class='float-end #=(profile_name==\"General\" ? \"invisible\" : \"\")#'><button type='button' class='destroy k-grid-delete k-button k-button-md k-rounded-md k-button-solid k-button-solid-base k-icon-button' onclick='deleteProfile(#=wfd_profile_id#)'> <span class='k-icon k-i-close k-button-icon'></span> </button></span>",
			dataBound: function(e) {
				let dataItem = null;
			  if (this.dataSource.data().length > 0) 
			  { 
				if(newProfileID != '')
				{
					dataItem = this.dataSource.view().find(function(item) {return item.wfd_profile_id == newProfileID; });
					newProfileID = '';
				}				
				else
					dataItem = this.dataSource.view().find(function(item) {return item.profile_name == "General"; }); 
				
				if (dataItem)
				{
					this.value(dataItem.wfd_profile_id); 
					prevProfileID = dataItem.wfd_profile_id;
					refreshGrid();
					inaGetPeriodMonthlyBadges('free_date');
				}
			  }
			},
			change:profileChanged,
			dataSource: {
							schema: {
								type:"json",						 
								model: {
								  id: "wfd_profile_id"
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
									url: window.location.origin + "/api?subject=working_free_days_profiles&type=read",
									dataType: "json",
									type:"post"         
								},
								parameterMap: function (options, type) {
								  return kendo.stringify(options);
								}
							}, 
							sort: { field: "profile_name", dir: "asc" },
							pageSize: 10000
						}
        });
});

function profileChanged(e)
{
	if(this.value()=='') //add profile
	{
		dialog = $("#dialog");
	
		if(dialog.length == 0)
		{
			dialog = $('<div id="dialog" />').appendTo('body');
			
			$('<form id="wfdProfileForm" />').appendTo('body');
			 
			dialog.kendoDialog({
				width: "450px",
				title: "Creaza Profil Nou",
				closable: true,
				modal: true,
				close: function(e) {
					e.sender.destroy();
				},
				content:  $("#wfdProfileForm").kendoForm({
						orientation: "horizontal",
						formData: {
							ID: 1,
							free_date: new Date()
						},
						items: [{
							field: "profileName",
							label: "Denumire Profil:",
							validation: { required: true },
							editor:"TextBox",
							 attributes:{
								//class: "w-100px"
								style:"width:250px;"
							},
							editorOptions:{
								fillMode: "flat",
							}
						},
						{
							field: "sourceProfile",
							label: "Profil Sursa:",
							validation: { required: false },
							editor:"DropDownList",							
							 attributes:{
								//class: "w-100px"
								style:"width:250px;"
							},
							editorOptions:{
								fillMode: "flat",
								dataTextField: "profile_name",
								dataValueField: "wfd_profile_id",	
								optionLabel: 'Profil Necompletat',
								dataSource: {
									schema: {
										type:"json",						 
										model: {
										  id: "wfd_profile_id"
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
											url: window.location.origin + "/api?subject=working_free_days_profiles&type=read",
											dataType: "json",
											type:"post"         
										},
										parameterMap: function (options, type) {
										  return kendo.stringify(options);
										}
									}, 
									sort: { field: "profile_name", dir: "asc" },
									pageSize: 10000
								}
							}
						}],
						buttonsTemplate: ""
					}),
				actions: [
					{ text: 'Anuleaza', action: function(e){
						$("#selectWFDProfile").data("kendoDropDownList").value(prevProfileID);
					}},
					{ text: 'Adauga', cssClass: "saveProfileButton", primary: true , action: dialogOnSaveProfile}
				]
			});
			
			$("#profileName").data("kendoTextBox").focus(); 
		}
	}
	else //refresh grid //update badges
	{	
		prevProfileID = this.value();
		refreshGrid();
		inaGetPeriodMonthlyBadges('free_date');
	}
}

function createPeriodFilter(response)
{
	inaPeriodFilter("working_free_days","free_date","Perioada",new Date(),response.minYear,response.maxYear,refreshGrid,badgesOptions);
}

function badgesOptions(id)
{
	return "wfdProfileId:"+prevProfileID;
}

function refreshGrid()
{
	// Get the grid instance 
	let grid = $("#grid").data("kendoGrid"); 
	// Apply the filter to the grid 
	
	let filter = [];
	filter = filter.concat(inaOptions['free_date'].dsFilter);
	filter.push({ field: "wfd_profile_id", operator: "eq", value: prevProfileID });

	grid.dataSource.filter(filter);
	resizeGrid();
}

function initYearlyDialog()
{
	dialog = $("#dialog");
	
	const currentYear = new Date().getFullYear(); 
	const firstDayOfNextYear = new Date(currentYear + 1, 0, 1); 
	const lastDayOfNextYear = new Date(currentYear + 1, 03, 31); let lastSunday = lastDayOfNextYear; 
	while (lastSunday.getDay() !== 0) { lastSunday.setDate(lastSunday.getDate() - 1); }
	
	if(dialog.length == 0)
	{
		dialog = $('<div id="dialog" />').appendTo('body');
		
		$('<form id="wfdForm" />').appendTo('body');
		 
		dialog.kendoDialog({
			width: "450px",
			title: "Profil " + $("#selectWFDProfile").data("kendoDropDownList").text() + "- Adauga  Zile Libere pentru tot Anul ",
			closable: true,
			modal: true,
			close: function(e) {
				e.sender.destroy();
			},
			content:  $("#wfdForm").kendoForm({
					orientation: "horizontal",
					formData: {
						ID: 1,
						free_date: lastSunday
					},
					items: [{
						field: "free_date",
						label: "Data Paște:",
						validation: { required: true },
						editor:"DatePicker",
						 attributes:{
							//class: "w-100px"
							style:"width:250px;"
						},
						editorOptions:{
							fillMode: "flat",
							format: "dddd, dd/MM/yyyy",
							start: "month"
						},
						hint: "Adauga toate zilele nelucratoare pentru anul selectat."
					},],
					buttonsTemplate: ""
				}),
			actions: [
				{ text: 'Anuleaza' },
				{ text: 'Adauga', primary: true , action: dialogOnSaveYear}
			]
		});
	}

}

function initDayDialog(e)
{
	
	let data = {name:null, date: null, mapping:null};
	if(e != null) data = $("#grid").data("kendoGrid").dataSource.get(e);
	
	dialog = $("#dialog");
	
	if(dialog.length == 0)
	{
		dialog = $('<div id="dialog" />').appendTo('body');
		
		$('<form id="freeDayForm" />').appendTo('body');
		 	
		dialog.kendoDialog({
			width: "450px",
			title: "Profil " + $("#selectWFDProfile").data("kendoDropDownList").text()+ " - Adauga Zile Libere",
			closable: true,
			modal: true,
			close: function(e) {
				e.sender.destroy();
			},
			content:  $("#freeDayForm").kendoForm({
					orientation: "horizontal",
					formData: {
						ID: e,
						name: data.name ?? 'Zi nelucratoare',
						free_date: data.free_date ?? new Date(inaOptions['free_date'].selectedYear, inaOptions['free_date'].selectedMonth-1),
						mapping: data.mapping ?? 1
					},
					items: [
					{
						field: "name",
						label: "Denumire:",
						validation: { required: true },
						editor:"AutoComplete",
						 attributes:{
							//class: "w-100px"
							style:"width:250px;"
						},
						editorOptions:{
							fillMode: "flat",
							filter: "startswith",
							dataTextField: "name",
							dataSource: dataSource('working_free_days',{name: { type: "string" }},'name'),
						},
						hint: "Denumire zi nelucratoare."
					},
					{
						field: "free_date",
						label: "Data:",
						validation: { required: true },
						editor:"DatePicker",
						 attributes:{
							//class: "w-100px"
							style:"width:250px;"
						},
						editorOptions:{
							fillMode: "flat",
							format: "dddd, dd/MM/yyyy",
							start: "month"
						},
						hint: "Data zilei nelucratoare."
					},
					{
						field: "mapping",
						label: "Corelare:",
						validation: { required: false },
						editor:"DropDownList",
						 attributes:{
							//class: "w-100px"
							style:"width:250px;"
						},
						editorOptions:{
							fillMode: "flat",
							dataSource: {data: [
												{ text: "Duminica", value: 1},
												{ text: "Luni", value: 2 },
												{ text: "Marti", value: 3 },
												{ text: "Miercuri", value: 4 },
												{ text: "Joi", value: 5 },
												{ text: "Vineri", value: 6 },
												{ text: "Sambata", value: 7 },
											   ],
										},
							dataTextField: "text",
							dataValueField: "value",
							optionLabel: {
											text: "Selecteaza o zi...",
											value: null
										 }
						},
						hint: "Doar in prognoza, corelare sarbatoare cu ziua din saptamana."
					},],
					buttonsTemplate: ""
				}),
			actions: [
				{ text: 'Anuleaza' },
				{ text: e == null ? 'Adauga' : 'Salveaza', primary: true , action: dialogOnSaveDay}
			]
		});
		
		$("#free_date").click(function() {
			$("#free_date").data("kendoDatePicker").open();
		});
	}

}


function FreeDays()
{
	initYearlyDialog();
	
	dialog.data("kendoDialog").open();
	
}

function OneFreeDay(e)
{
	initDayDialog(e);
	
	dialog.data("kendoDialog").open();
	
}

function dialogOnSaveProfile()
{
	if($("#profileName").data("kendoTextBox").value() == null)
	{
		$("#selectWFDProfile").data("kendoDropDownList").value(prevProfileID);
		return;
	}
	
	let item = $("#selectWFDProfile").data("kendoDropDownList").dataSource.data().find( (element)=>{ return element.profile_name == $("#profileName").data("kendoTextBox").value()});
	
	if(item !== undefined){
		kendo.alert("<span class='text-danger'>Numele profilului trebbuie sa fie unic.</span>");
		$("#selectWFDProfile").data("kendoDropDownList").value(prevProfileID);
		return;
	}
	
	var data = 
			{
				profile_name:  $("#profileName").data("kendoTextBox").value(),
				sourceProfile: $("#sourceProfile").data("kendoDropDownList").value(),
				supplier_id: supplierID
			};
	
	apiCallCreate(data,'working_free_days_profiles',function(response)
	{
		if(response.data[0].wfd_profile_id !== undefined)
		{
			newProfileID=response.data[0].wfd_profile_id;
			$("#selectWFDProfile").data("kendoDropDownList").dataSource.read();
		}
	});
}

function deleteProfile(profileID)
{
	$('body').append($('<div id="confirm">'));
	$("#confirm").kendoConfirm
	({
		title: "Confirmare stergere profil",
		content:"Sunteti sigur ca doriti sa stergeti profilul "+$("#selectWFDProfile").data("kendoDropDownList").dataSource.get(profileID).profile_name+" ?",
		actions:[
		{ text: 'Anulare', primary:false, action: function(e) { }},
		{ text: 'Sterge', primary: true, action: function(e) { 
			apiCallDestroy({wfd_profile_id:profileID},'working_free_days_profiles',function(response)
			{
				if(prevProfileID == profileID)
				{
					deleteteProfileID = profileID;
					$("#selectWFDProfile").data("kendoDropDownList").dataSource.read();
				}
			});
		}}
		],
	});
}

function dialogOnSaveYear()
{
	var data = {
			models: [
					 {
						freeDate :kendo.toString($("#free_date").data("kendoDatePicker").value(),"yyyy-MM-dd"),
						wfdProfileId:  $("#selectWFDProfile").data("kendoDropDownList").value()
					 }
					]
			};
	
	if( $("#free_date").data("kendoDatePicker").value().getDay() != 0 )
	{
		kendo.confirm("&#x26A0; Trebuie sa fie Duminica.");
		return false;
	}
	
	customCall(data,'createWorkingFreeDays',onDataSaved);
}

function dialogOnSaveDay()
{
	if( $("#name").data("kendoAutoComplete").value().trim().length == 0)
	{
		kendo.confirm("&#x26A0; Este necesar sa adaugati denumirea!");
		return false;
	}

	var data = {
			models: [
					 {
						freeDate :kendo.toString($("#free_date").data("kendoDatePicker").value(),"yyyy-MM-dd"),
						name :$("#name").data("kendoAutoComplete").value().trim(),
						mapping: $("#mapping").data("kendoDropDownList").value(),
						wfdProfileId:  $("#selectWFDProfile").data("kendoDropDownList").value()
					 }
					]
			};
	
	customCall(data,'createWorkingOneFreeDay',onDataSaved);
}

function onDataSaved()
{
	$("#grid").data("kendoGrid").dataSource.read();
}
