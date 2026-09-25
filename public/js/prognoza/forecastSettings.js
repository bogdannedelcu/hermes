$( document ).ready(function() {
	
	let gridCommands = 	[{ command:
			[{ 	name: "editItem",
				iconClass:"k-icon k-i-pencil",
				text: "",
				click: function(e){	e.preventDefault();	var tr = $(e.target).closest("tr");	item = this.dataItem(tr); ConsumptionTypeDialog(item);},
				title:"Edit"							
			},
			{ 	name: "moveUP",
				iconClass:"k-icon k-i-arrow-up",
				text: "",
				click: function(e){	e.preventDefault();	var tr = $(e.target).closest("tr");	item = this.dataItem(tr); moveUP(item.id);},
				title:"Edit"							
			},
			{ 	name: "moveDown",
				iconClass:"k-icon k-i-arrow-down",
				text: "",
				click: function(e){	e.preventDefault();	var tr = $(e.target).closest("tr");	item = this.dataItem(tr); moveDown(item.id);},
				title:"Edit"							
			},
			{ 	
				name: "destroy",
				text: "",
				visible:function(dataItem) { return dataItem.deletable},
			}],
			title: "", minScreenWidth: 801, width: "160px", attributes: { style: "text-align: center" },
			headerTemplate:''
			//headerTemplate:'<button type="button" class="k-col-pdf multiDownload k-state-disabled k-button k-button-md k-rounded-md k-button-solid k-button-solid-base k-icon-button" onclick="multiPDFDownload()"><span class="k-icon k-i-pdf k-button-icon"></span></button> \
			//				<button type="button" class="k-col-xml multiDownload k-state-disabled k-button k-button-md k-rounded-md k-button-solid k-button-solid-base k-icon-button" onclick="multiXMLDownload()"><span class="k-icon k-i-zip k-button-icon"></span></button>'
	}];
	
	let gridColumns = inaGridColumns(false, gridCommands, ["consumption_type_name","EstimationType","Outliers","NoActiveCustomers",""], 
										   titles=["Tip Consumator","EstimationType", 'Filtru Valori Aberante', 'Clienti Activi',''], 
										   types=["string","string","string", "number"]);
	
	gridColumns[2].template = '#=EstimationType.replace("simple","simpla").replace("temperature","temperatura").replace("manual","manuala")#';
	
	let gridToolBar = [{template:'<a id="addProfile" class="k-button k-button-icontext"><span class="k-icon k-i-plus"></span>Adaugă</a>'}];
	
	inaGrid('forecast_consumption_types', 'grid', inaDS({subject:"forecast_consumption_types", model:{id:"fct_id"},filter:{field: "supplier_id",operator: "eq",value: 1},serverFiltering:true}), gridColumns, gridToolBar);
		
	$("#grid").data("kendoGrid").dataSource.read();
	
	$("#addProfile").kendoButton({
			click: function() {ConsumptionTypeDialog(null)}
		});
});

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
	resizeGrid();
	$("#grid").data("kendoGrid").dataSource.read();
}


/********EDIT DIOALOG**************/
var consumptionTypeItem = null;
var consumptionTypeOptions = null;

function CreateDialog()
{
	if($("#dialog").length)
	{
		$("#dialog").data("kendoDialog").destroy();
		$("#dialog").remove();
	}
							
	dialog = $('<div id="dialog" />').appendTo('body');

	// Define the dialog
	var dialog = $("#dialog").kendoDialog({
		title: "Adaugă Tip Consum",
		width: "850px",
		closable: true,
		modal: true,
		visible: false,
		actions: [
			{ text: "Anulează"},
			{
				text: "Adaugă",
				primary: true,
				action: function () {
					return saveConsumptionType();
				}
			}
		],
		content: '<form id="ctForm" />'
	}).data("kendoDialog");
}

function showForm(response)
{
	const options = {};
				for (const { option_name, value } of response.data) {
				  options[option_name] = value;
				}
	
	consumptionTypeOptions = options;	
	
	estimationTypeChanged(null);
}

function createSimpleForm(options)
{		
	// Define the form structure
	$("#ctForm").kendoForm({
                orientation: "horizontal",
                layout: "grid",
                grid: { cols: 2, gutter: 30 },
                formData: {
                    supplier_name: 1,
					ctype:consumptionTypeItem ? consumptionTypeItem.consumption_type_name : '',
					estimationType:"simple",
					intervalZ: options ? (parseInt(options.intervalZ) ? Math.max(parseInt(options.intervalZ),0) : 0) : 0,
					intervalN: options ? (parseInt(options.intervalN) ? Math.max(parseInt(options.intervalN),0) : 0) : 0,
					outliers: options ? options.outliers == "1" : true,
					outliers_interval: options ? options.outliers_interval : "18-6",
					outliers_radius: options ? options.outliers_radius: 1,
					outliers_size:options ? options.outliers_size : 5,
					ignore_synthetics:options ? Number(options.ignore_synthetics) == 1 : false,
					max_months_before:options ? options.max_months_before : 13,
                },
                items: [
                    {
                        field: "ctype",
                        label: "Denumire Tip Consum",
                        editor: "TextBox",
                        colSpan: 1,
                        validation: { required: true }
                    },
					{
						field: "estimationType",
						label: "Estimare",
						editor: "DropDownList",
						editorOptions: {
							dataSource: [ { value: "simple", text: "Simpla (doar zilele saptamanii)" },
										  { value: "temperature", text: "pe baza de Temperatura" },
										  { value: "prosumator", text: "pe baza de Productie" },
										  { value: "manual", text: "Manuala" }],
									dataTextField: "text",
                                    dataValueField: "value",
							change: function (e) {
                                        estimationTypeChanged(e);
                                    }
						},
						colSpan: 1,
						validation: { required: false }
					},
                    {
                        type: "group",
                        label: "Setari",
                        layout: "grid",
                        grid: { cols: 2, gutter: 10 },
                        items: [
                            {
                                field: "intervalZ",
                                label: "Selectie date interval Zi",
                                editor: "DropDownList",
                                editorOptions: {
                                    dataSource: [{value:0,text:"Corelare data"}, {value:1,text:"Corelare zi saptamana"}],
									dataTextField: "text",
                                    dataValueField: "value"
                                },
                                colSpan: 3,
                                validation: { required: false }
                            },
                            {
                                field: "intervalN",
                                label: "Selectie date interval Noapte",
                                editor: "DropDownList",
                                editorOptions: {
                                    dataSource: [{value:0, text:"Corelare data"}, {value:1,text:"Corelare zi saptamana"}],
									dataTextField: "text",
                                    dataValueField: "value"
                                },
                                colSpan: 3,
                                validation: { required: false }
                            }
                        ]
                    },
					{
                        type: "group",
                        label: "Preselectie date",
                        items: [
							{
                                field: "ignore_synthetics",
                                label: "Ignora Sintetice Abs.",
                                editor: "CheckBox",
                                validation: { required: false }
                            },
                            {
                                field: "max_months_before",
                                label: "Perioada maxima din trecut (luni)",
                                editor: "NumericTextBox",
                                editorOptions: { format: "0", decimals: 0, step: 1, min:0, max: 120},
                                hint: "0 - nelimitat<br> 1 - realizat zilnic<br> 13 - realizat zilnic + anul trecut",
                                validation: { required: false }
                            }
                        ]
                    },{type:"group",label:" ",layout:"grid",items:[]},
                    {
                        type: "group",
                        label: "Valori aberante",
                        items: [
                            {
                                field: "outliers",
                                label: "Filtru valori aberante",
                                editor: "Switch",
                                validation: { required: false }
                            },
                            {
                                field: "outliers_interval",
                                label: "Interval (Ex. 18-6)",
                                editor: "TextBox",
                                attributes: { style: "width:100px" },
                                validation: { required: false }
                            },
                            {
                                field: "outliers_size",
                                label: "Coeficient (ex. 3.5)",
                                editor: "NumericTextBox",
								editorOptions: {
									format: "0.0", decimals: 1, step: 0.5, min:2 
								},
                                attributes: { style: "width:100px" },
                                validation: { required: false }
                            },
                            {
                                field: "outliers_radius",
                                label: "Raza (ex. 1)",
                                editor: "NumericTextBox",
								editorOptions: {
									format: "0", decimals: 0, step: 1, min:1, max:3 
								},
                                attributes: { style: "width:100px" },
                                validation: { required: false }
                            }
                        ]
                    }
                ],
                buttonsTemplate: `<div style='width:100%;overflow:hidden;'></div>`
	});
}

function createTemperatureForm(options)
{
	// Define the form structure
	form = $("#ctForm").kendoForm({
                orientation: "horizontal",
                layout: "grid",
                grid: { cols: 2, gutter: 30 },
                formData: {
					supplier_name: 1,
					ctype:consumptionTypeItem ? consumptionTypeItem.consumption_type_name : '',
					estimationType:"temperature",
					temperature_source: options ? (options.temperature_source ? options.temperature_source : 'temperature' ) : 'temperature',
					temperature_selected_value_type: options ? (options.temperature_selected_value_type ? options.temperature_selected_value_type : 'AVG' ) : 'AVG',
					days_margin:options ? options.days_margin : 15,
					temperature_margin:options ? options.temperature_margin : 3.5,
					days_margin_max:options ? options.days_margin_max : 30,
					temperature_margin_max:options ? options.temperature_margin_max : 9.5,					
					nc_temperature_margin:options ? options.nc_temperature_margin : 3.5,
					nc_interval_marginZ:options ? options.nc_interval_marginZ : 1,	
					nc_interval_marginN:options ? options.nc_interval_marginN : 3,	
					nc_selected_value_type:options ? options.nc_selected_value_type : 'MINDEV',	
					outliers: options ? options.outliers == "1" : true,
					outliers_interval: options ? options.outliers_interval : "18-6",
					outliers_radius: options ? options.outliers_radius: 1,
					outliers_size:options ? options.outliers_size : 5,
					ignore_synthetics:options ? Number(options.ignore_synthetics) == 1 : 0,
					max_months_before:options ? options.max_months_before : 13,
                },
                items: [
                    {
                        field: "ctype",
                        label: "Denumire Tip Consum",
                        editor: "TextBox",
                        colSpan: 1,
                        validation: { required: true }
                    },
					{
						field: "estimationType",
						label: "Estimare",
						editor: "DropDownList",
						editorOptions: {
							dataSource: [ { value: "simple", text: "Simpla (doar zilele saptamanii)" },
										  { value: "temperature", text: "pe baza de Temperatura" },
										  { value: "prosumator", text: "pe baza de Productie" },
										  { value: "manual", text: "Manuala" }],
									dataTextField: "text",
                                    dataValueField: "value",
							change: function (e) {
                                        estimationTypeChanged(e);
                                    }
						},
						colSpan: 1,
						validation: { required: false }
					},
                    {
                        type: "group",
                        label: "Setari - Faza I",
                        layout: "grid",
                        grid: { cols: 1, gutter: 10 },
                        items: [
							{
                                field: "temperature_selected_value_type",
                                label: "Functie Calcul Estimare",
                                editor: "DropDownList",
                                editorOptions: {
                                    dataSource: [
                                        { value: "AVG", text: "AVG" },
                                        { value: "MIN", text: "MIN" },
                                        { value: "MAX", text: "MAX" }
                                    ],
                                    dataTextField: "text",
                                    dataValueField: "value"
                                },
								attributes: { style: "width:100px" },
                                colSpan: 1,
                                validation: { required: false }
                            },
                            {
                                field: "days_margin",
                                label: "Interval selectie (zile)",
                                editor: "NumericTextBox",
                                editorOptions: { format: "0", decimals: 0, min:0, max:45 },
                                attributes: { style: "width:100px" },
                                colSpan: 1,
                                hint: "Zero pt. luna curenta",
                                validation: { required: false }
                            },
							{
                                field: "temperature_margin",
                                label: "Marja temperatura (°C)",
                                editor: "NumericTextBox",
                                editorOptions: { format: "0.0", decimals: 1, step: 0.5, min:0, max:30 },
                                attributes: { style: "width:100px" },
                                colSpan: 1,
                                validation: { required: false }
                            }]
					},
					{
                        type: "group",
                        label: "Preselectie date",
                        items: [
							{
                                field: "temperature_source",
                                label: "Sursa de temperatura",
                                editor: "DropDownList",
                                editorOptions: {
                                    dataSource: [
                                        { value: "temperature", text: "alte surse" },
                                        { value: "temp", text: "temp" },
                                        { value: "feelslike", text: "feelslike" }
                                    ],
                                    dataTextField: "text",
                                    dataValueField: "value"
                                },
								attributes: { style: "width:100px" },
                                colSpan: 1,
                                validation: { required: false }
                            },
							{
                                field: "ignore_synthetics",
                                label: "Ignora Sintetice Abs.",
                                editor: "CheckBox",
                                validation: { required: false }
                            },
                            {
                                field: "max_months_before",
                                label: "Perioada maxima din trecut (luni)",
                                editor: "NumericTextBox",
                                editorOptions: { format: "0", decimals: 0, step: 1, min:0, max: 120},
                                hint: "0 - nelimitat<br> 1 - realizat zilnic<br> 13 - realizat zilnic + anul trecut",
                                validation: { required: false }
                            }
                        ]
                    },
					{
                        type: "group",
                        label: "Setari - Faza II (Date lipsa FAZA I)",
                        layout: "grid",
                        grid: { cols: 1, gutter: 10 },
                        items: [
                            {
                                field: "days_margin_max",
                                label: "Interval selectie (zile)",
                                editor: "NumericTextBox",
                                editorOptions: { format: "0", decimals: 0, min:0, max:45 },
                                attributes: { style: "width:100px" },
								colSpan: 1,
                                validation: { required: false }
                            },
                           
                            {
                                field: "temperature_margin_max",
                                label: "Marja temperatura (°C)",
                                editor: "NumericTextBox",
                                editorOptions: { format: "0.0", decimals: 1, step: 0.5, min:1.5, max:30 },
                                attributes: { style: "width:100px" },
								colSpan: 1,
                                validation: { required: false }
                            }]
					},
					{type:"group",label:" ",layout:"grid",items:[]},
                    {
                        type: "group",
                        label: "Setari - Faza III (Date lipsa/clienti noi - coreleaza luni)",
                        layout: "grid",
                        items: [
                            {
                                field: "nc_temperature_margin",
                                label: "Marja temperatura (°C)",
                                editor: "NumericTextBox",
                                editorOptions: { format: "0.0", decimals: 1, step: 0.5, min:0, max:30 },
								attributes: { style: "width:100px" },
                                validation: { required: false }
                            },
                            {
                                field: "nc_interval_marginZ",
                                label: "Marja interval zi (h)",
                                editor: "NumericTextBox",
                                editorOptions: { format: "0", decimals: 0, min: 0, max: 12 },
								attributes: { style: "width:100px" },
                                validation: { required: false }
                            },
                            {
                                field: "nc_interval_marginN",
                                label: "Marja interval noapte (h)",
                                editor: "NumericTextBox",
                                editorOptions: { format: "0", decimals: 0, min: 0, max: 12 },
								attributes: { style: "width:100px" },
                                validation: { required: false }
                            },
                            {
                                field: "nc_selected_value_type",
                                label: "Selecteaza val.",
                                editor: "DropDownList",
                                editorOptions: {
                                    dataSource: [
                                        { value: "MINDEV", text: "Apropiata de medie" },
                                        { value: "MIN", text: "Minima" },
                                        { value: "MAX", text: "Maxima" }
                                    ],
                                    dataTextField: "text",
                                    dataValueField: "value"
                                },
								attributes: { style: "width:180px" },
                                validation: { required: false }
                            }
                        ]
                    },{
                        type: "group",
                        label: "Valori aberante",
                        items: [
                            {
                                field: "outliers",
                                label: "Filtru valori aberante",
                                editor: "Switch",
                                validation: { required: false }
                            },
                            {
                                field: "outliers_interval",
                                label: "Interval (Ex. 18-6)",
                                editor: "TextBox",
                                attributes: { style: "width:100px" },
                                validation: { required: false }
                            },
                            {
                                field: "outliers_size",
                                label: "Coeficient (ex. 3.5)",
                                editor: "NumericTextBox",
								editorOptions: {
									format: "0.0", decimals: 1, step: 0.5, min:2 
								},
                                attributes: { style: "width:100px" },
                                validation: { required: false }
                            },
                            {
                                field: "outliers_radius",
                                label: "Raza (ex. 1)",
                                editor: "NumericTextBox",
								editorOptions: {
									format: "0", decimals: 0, step: 1, min:1, max:3 
								},
                                attributes: { style: "width:100px" },
                                validation: { required: false }
                            }
                        ]
                    },
                ],
                buttonsTemplate: `<div style='width:100%;overflow:hidden;'></div>`
	}).data("kendoForm");
}

function createProsumatorForm(options)
{
	// Define the form structure
	form = $("#ctForm").kendoForm({
                orientation: "horizontal",
                layout: "grid",
                grid: { cols: 2, gutter: 30 },
                formData: {
                    supplier_name: 1,
					ctype:consumptionTypeItem ? consumptionTypeItem.consumption_type_name : '',
					estimationType:"prosumator",
					ignore_synthetics:options ? Number(options.ignore_synthetics) == 1 : 0,
					max_months_before:options ? options.max_months_before : 13,
					prod_estimation_type: options ? (options.prod_estimation_type ? options.prod_estimation_type : 'Statistic' ) : "Statistic",
					prod_days_margin: options ? options.prod_days_margin : 15,
					prod_days_margin_max: options ? options.prod_days_margin_max : 30,
					prod_margin: options ? options.prod_margin : 0.250,
					prod_margin_max: options ? options.prod_margin_max : 0.600,
					prod_alt_estimate: options ? Math.max(options.prod_alt_estimate,2) :2
                },
                items: [
                    {
                        field: "ctype",
                        label: "Denumire Tip Consum",
                        editor: "TextBox",
                        colSpan: 1,
                        validation: { required: true }
                    },
					{
						field: "estimationType",
						label: "Estimare",
						editor: "DropDownList",
						editorOptions: {
							dataSource: [ { value: "simple", text: "Simpla (doar zilele saptamanii)" },
										  { value: "temperature", text: "pe baza de Temperatura" },
										  { value: "prosumator", text: "pe baza de Productie" },
										  { value: "manual", text: "Manuala" }],
									dataTextField: "text",
                                    dataValueField: "value",
							change: function (e) {
                                        estimationTypeChanged(e);
                                    }
						},
						colSpan: 1,
						validation: { required: false }
					},
                    {
                        type: "group",
                        label: "Setari - FAZA I",
                        layout: "grid",
                        grid: { cols: 1, gutter: 10 },
                        items: [
                            {
                                field: "prod_estimation_type",
                                label: "Calcul",
                                editor: "DropDownList",
                                editorOptions: {
                                    dataSource: ["AVG","MIN","MAX","Statistic"]
                                },
								attributes: { style: "width:200px" },
                                validation: { required: false }
                            },
                            {
                                field: "prod_days_margin",
                                label: "Interval selectie (zile)",
                                editor: "NumericTextBox",
                                editorOptions: { format: "0", decimals: 0, min:0, max: 45 },
                                attributes: { style: "width:100px" },
                                hint: "Zero pt. luna curenta",
                                validation: { required: false }
                            },
                            {
                                field: "prod_margin",
                                label: "Marja EA (MWh)",
                                editor: "NumericTextBox",
                                editorOptions: { format: "0.000", decimals: 3, min: 0, step:0.05 },
                                attributes: { style: "width:100px" },
                                validation: { required: false }
                            }]
					},
					{
                        type: "group",
                        label: "Preselectie date",
                        items: [
							{
                                field: "ignore_synthetics",
                                label: "Ignora Sintetice Abs.",
                                editor: "CheckBox",
                                validation: { required: false }
                            },
                            {
                                field: "max_months_before",
                                label: "Perioada maxima din trecut (luni)",
                                editor: "NumericTextBox",
                                editorOptions: { format: "0", decimals: 0, step: 1, min:0, max: 120},
                                hint: "0 - nelimitat<br> 1 - realizat zilnic<br> 13 - realizat zilnic + anul trecut",
                                validation: { required: false }
                            }
                        ]
                    },
					{
                        type: "group",
                        label: "Setari - FAZA II (date lipsa faza I)",
                        layout: "grid",
                        grid: { cols: 1, gutter: 10 },
                        items: [
                            {
                                field: "prod_days_margin_max",
                                label: "Interval selectie (zile)",
                                editor: "NumericTextBox",
                                editorOptions: { format: "0", decimals: 0, min:0, max: 45 },
                                attributes: { style: "width:100px" },
                                validation: { required: false }
                            },
                            {
                                field: "prod_margin_max",
                                label: "Marja EA (MWh)",
                                editor: "NumericTextBox",
                                editorOptions: { format: "0.000", decimals: 3, min:0, step:0.05 },
                                attributes: { style: "width:100px" },
                                validation: { required: false }
                            }
                        ]
                    },{type:"group",label:" ",layout:"grid",items:[]},
					{
                        type: "group",
                        label: "Setari - FAZA III (Date lipsa/interval noapte)",
                        layout: "grid",
                        grid: { cols: 1, gutter: 10 },
						items: [{
                                field: "prod_alt_estimate",
                                label: "Aplica estimarea",
                                editor: "DropDownList",
                                editorOptions: {
                                    dataSource: inaDS({subject:"forecast_consumption_types", model:{id:"consumption_type_name"},filter:{field: "EstimationType",operator: "neq",value: "prosumator"},serverFiltering:true}),
                                    dataTextField: "consumption_type_name",
                                    dataValueField: "fct_id"
                                },
								attributes: { style: "width:180px" },
                                validation: { required: false }
                            }]
					},
                    
                ],
                buttonsTemplate: `<div style='width:100%;overflow:hidden;'></div>`
	}).data("kendoForm");
}

function createManualForm(options)
{
	// Define the form structure
	form = $("#ctForm").kendoForm({
                orientation: "horizontal",
                layout: "grid",
                grid: { cols: 2, gutter: 30 },
                formData: {
                    supplier_name: 1,
					ctype:consumptionTypeItem ? consumptionTypeItem.consumption_type_name : '',
					estimationType:"manual"
                },
                items: [
                    {
                        field: "ctype",
                        label: "Denumire Tip Consum",
                        editor: "TextBox",
                        colSpan: 1,
                        validation: { required: true }
                    },
					{
						field: "estimationType",
						label: "Estimare",
						editor: "DropDownList",
						editorOptions: {
							dataSource: [ { value: "simple", text: "Simpla (doar zilele saptamanii)" },
										  { value: "temperature", text: "pe baza de Temperatura" },
										  { value: "prosumator", text: "pe baza de Productie" },
										  { value: "manual", text: "Manuala" }],
									dataTextField: "text",
                                    dataValueField: "value",
							change: function (e) {
                                        estimationTypeChanged(e);
                                    }
						},
						colSpan: 1,
						validation: { required: false }
					},
                ],
                buttonsTemplate: `<div style='width:100%;overflow:hidden;'></div>`
	}).data("kendoForm");
}

function estimationTypeChanged(e)
{
	if(e) //estimation type changed
	{
		if(consumptionTypeOptions)
		{
			//recreate dialog with new form
			if(e.sender.value() == "simple") {consumptionTypeOptions.simple = 1;consumptionTypeOptions.temperature = 0;consumptionTypeOptions.prosumator = 0;consumptionTypeOptions.manual = 0;}
			else if(e.sender.value() == "temperature") {consumptionTypeOptions.simple = 0;consumptionTypeOptions.temperature = 1;consumptionTypeOptions.prosumator = 0;consumptionTypeOptions.manual = 0;}
			else if(e.sender.value() == "prosumator") {consumptionTypeOptions.simple = 0;consumptionTypeOptions.temperature = 0;consumptionTypeOptions.prosumator = 1;consumptionTypeOptions.manual = 0;}
			else if(e.sender.value() == "manual") {consumptionTypeOptions.simple = 0;consumptionTypeOptions.temperature = 0;consumptionTypeOptions.prosumator = 0;consumptionTypeOptions.manual = 1;}
		}

		CreateDialog();
	}

	//create form
	if(consumptionTypeOptions) //edit
	{
		let options = consumptionTypeOptions;
		
		if(options.simple == "1")
			createSimpleForm(options);
		else if(options.temperature == "1")
			createTemperatureForm(options);
		else if(options.prosumator == "1")
			createProsumatorForm(options);
		else createManualForm(options);   /*if(options.manual == "1")*/
	}
	else //new
	{
		if(e.sender.value() == "simple") {createSimpleForm(null);}
		else if(e.sender.value() == "temperature") {createTemperatureForm(null);}
		else if(e.sender.value() == "prosumator") {createProsumatorForm(null);}
		else if(e.sender.value() == "manual") {createManualForm(null);}
	}

	
	$(".k-form-error").remove();
	$(".k-invalid").removeClass("k-invalid");
	
	$("#dialog").data("kendoDialog").open();
}

function saveConsumptionType()
{
	
	//validation rules
	if($("#ctype").data("kendoTextBox").value().trim() == '')
	{
		$("#staticNotification").data("kendoNotification").show("Denumirea este incorecta.", "error");
		return false;
	}
		
	let estimationOptions = {};
	if ($("#estimationType").data("kendoDropDownList").value() == "simple")
	{
		estimationOptions.simple = 1;estimationOptions.temperature = 0;estimationOptions.prosumator = 0;estimationOptions.manual = 0;
		estimationOptions.intervalZ = $("#intervalZ").data("kendoDropDownList").value();
		estimationOptions.intervalN = $("#intervalN").data("kendoDropDownList").value();
	}
	else if ($("#estimationType").data("kendoDropDownList").value() == "temperature")
	{
		estimationOptions.simple = 0;estimationOptions.temperature = 1;estimationOptions.prosumator = 0;estimationOptions.manual = 0;

		estimationOptions.temperature_source = $("#temperature_source").data("kendoDropDownList").value();
		estimationOptions.temperature_margin = $("#temperature_margin").data("kendoNumericTextBox").value();
		estimationOptions.temperature_margin_max = $("#temperature_margin_max").data("kendoNumericTextBox").value();
		estimationOptions.temperature_selected_value_type = $("#temperature_selected_value_type").data("kendoDropDownList").value();
		estimationOptions.days_margin = $("#days_margin").data("kendoNumericTextBox").value();
		estimationOptions.days_margin_max = $("#days_margin_max").data("kendoNumericTextBox").value();
		
		estimationOptions.nc_temperature_margin = $("#nc_temperature_margin").data("kendoNumericTextBox").value();
		estimationOptions.nc_interval_marginZ = $("#nc_interval_marginZ").data("kendoNumericTextBox").value();
		estimationOptions.nc_interval_marginN = $("#nc_interval_marginN").data("kendoNumericTextBox").value();
		estimationOptions.nc_selected_value_type = $("#nc_selected_value_type").data("kendoDropDownList").value();
	}
	else if ($("#estimationType").data("kendoDropDownList").value() == "prosumator")
	{
		estimationOptions.simple = 0;estimationOptions.temperature = 0;estimationOptions.prosumator = 1;estimationOptions.manual = 0;
		
		estimationOptions.prod_margin = $("#prod_margin").data("kendoNumericTextBox").value();
		estimationOptions.prod_margin_max = $("#prod_margin_max").data("kendoNumericTextBox").value();
		estimationOptions.prod_days_margin = $("#prod_days_margin").data("kendoNumericTextBox").value();
		estimationOptions.prod_days_margin_max = $("#prod_days_margin_max").data("kendoNumericTextBox").value();
		estimationOptions.prod_estimation_type = $("#prod_estimation_type").data("kendoDropDownList").value();
		estimationOptions.prod_alt_estimate = $("#prod_alt_estimate").data("kendoDropDownList").value();
	}
	else if ($("#estimationType").data("kendoDropDownList").value() == "manual")
	{
		estimationOptions.simple = 0;estimationOptions.temperature = 0;estimationOptions.prosumator = 0;estimationOptions.manual = 1;
	}
	
	if($("#max_months_before").data("kendoNumericTextBox") !== undefined)
		estimationOptions.max_months_before = $("#max_months_before").data("kendoNumericTextBox").value();

	if($("#ignore_synthetics").data("kendoCheckBox") !== undefined)
		estimationOptions.ignore_synthetics = Number($("#ignore_synthetics").data("kendoCheckBox").value());
	
	if($("#outliers").data("kendoSwitch") !== undefined)
	{
		estimationOptions.outliers = $("#outliers").data("kendoSwitch").value();
		estimationOptions.outliers_size = $("#outliers_size").data("kendoNumericTextBox").value();
		estimationOptions.outliers_interval = $("#outliers_interval").data("kendoTextBox").value();
		estimationOptions.outliers_radius = $("#outliers_radius").data("kendoNumericTextBox").value();
	}
		
	
	//use grid model
	var data = {
		models: [
				 {
					fct_id: consumptionTypeItem ? consumptionTypeItem.fct_id : null,
					supplier_id:supplierID,
					consumption_type_name:$("#ctype").data("kendoTextBox").value(),
					temperature:estimationOptions.temperature,
					temperature_source:estimationOptions.temperature_source ?? 'temperature',
					temperature_margin:estimationOptions.temperature_margin ?? 3.5,
					temperature_margin_max:estimationOptions.temperature_margin_max ?? 9.5,
					temperature_selected_value_type: estimationOptions.temperature_selected_value_type ?? 'AVG',
					days_margin:estimationOptions.days_margin ?? 15,
					days_margin_max:estimationOptions.days_margin_max ?? 30,
					simple:estimationOptions.simple,
					last_day:0,
					intervalZ:estimationOptions.intervalZ ?? 0,
					intervalN:estimationOptions.intervalN ?? 1,
					last_dayT:0,
					prosumator:estimationOptions.prosumator,							
					prod_margin:estimationOptions.prod_margin ?? 0.200,
					prod_margin_max:estimationOptions.prod_margin_max ?? 0.600,
					prod_days_margin:estimationOptions.prod_days_margin ?? 15,
					prod_days_margin_max:estimationOptions.prod_days_margin_max ?? 30,
					prod_estimation_type:estimationOptions.prod_estimation_type ?? 'Statistic',
					prod_alt_estimate:estimationOptions.prod_alt_estimate ?? 2,
					manual:estimationOptions.manual,
					outliers:estimationOptions.outliers ?? 0,
					outliers_size:estimationOptions.outliers_size ?? 5,
					outliers_interval:estimationOptions.outliers_interval ?? "20-5",
					outliers_radius:estimationOptions.outliers_radius ?? 1,
					ignore_synthetics:estimationOptions.ignore_synthetics ?? 0,
					max_months_before:estimationOptions.max_months_before ?? 13,
					nc_temperature_margin:estimationOptions.nc_temperature_margin ?? 5.5,
					nc_interval_marginZ:estimationOptions.nc_interval_marginZ ?? 2,
					nc_interval_marginN:estimationOptions.nc_interval_marginN ?? 4,
					nc_selected_value_type:estimationOptions.nc_selected_value_type ?? 'MINDEV'
				 }
				]
	};
	
	let uType = "create";
	if(consumptionTypeItem != null) uType = "update";
	
	var jqxhr = $.post({
			url: window.location.origin+"/api?subject=forecast_consumption_types&type="+uType,
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

function ConsumptionTypeDialog(e = null)
{
	CreateDialog();
		
	consumptionTypeItem = e;
	if(e != null)
	{
		$("#dialog").data("kendoDialog").title("Editează Tip Consum");
		$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Salvează");
		
		var data = {
					filter: {
					field: "fct_id",
					operator:"eq",
					value:e.id
					}
		};
		
		apiCall(data,"forecast_consumption_types_options","read",showForm);
	}
	else
	{
		consumptionTypeOptions = null;
		
		$("#dialog").data("kendoDialog").title("Adaugă Tip Consum");
		$(".k-dialog-buttongroup > button.k-button-solid-primary").text("Adaugă");
		
		createSimpleForm(null);
	
		$(".k-form-error").remove();
		$(".k-invalid").removeClass("k-invalid");
		
		$("#dialog").data("kendoDialog").open();
	}
		
}