function inaGrid(subject, id, gridDS ={}, gridColumns = null, gridToolBar = [], gridWidth=null, gridHeight=null)
{
	inaOptions[id]={
		subject:subject,
		id:id,
		gridOptions: 
		{
			dataSource: gridDS,
			columns: gridColumns,
			columnMenu: {
				//filterable: true
			 },
			width: gridWidth,
			height: gridHeight,
			groupable: window.matchMedia("(min-width: 801px)").matches,
			editable:"popup",
			sortable: true,
			resizable: true,
			autoBind: false,
			filterable: {
					multi: true,
					search: true,
					//mode:"row" //"menu,row"
				},
			reorderable: true,
				toolbar: gridToolBar.concat(["excel", "pdf","search"]),
				scrollable: {
					endless: true
				},
			noRecords: {
				template: () => `<div class="p-3 text-cente border border-2 bl-light fw-bolder mx-auto">Nu am gasit date in sistem.</div>`
			}
		}
	};
	
	if($("#"+id).data("kendoGrid") !== undefined)
		$("#"+id).data("kendoGrid").destroy();

	//cleanup wrapper
	$("#"+id+"_wrapper").empty();
	
	//create html inside wrapper
	$("#"+id+"_wrapper").html(`<div id="${id}"></div>`);
	
	$("#grid").kendoGrid(inaOptions[id].gridOptions);
}

function inaGridColumns(selectable = false, commands = null, fields=[], titles=[], types=[])
{
	
	let gridColumns=[];
	
	if(selectable)
		gridColumns.push({
			selectable:true,/*minScreenWidth: 801,*/
			width: 40,
			attributes: {class: "k-text-center"},
			headerAttributes: {class: "k-text-center"}
			});
	
	if(commands)
		gridColumns = gridColumns.concat(commands);
	
	for (let i = 0; i < fields.length; i++) {
		
		let column = {field:fields[i]};
		
		if(i < titles.length)
			column.title=titles[i];
		
		if(i < types.length)
			column.type=titles[i];
		
		gridColumns.push(column);
	}
	
	return gridColumns;
	
	/*[{
		selectable:true,minScreenWidth: 801,
		width: 40,
		attributes: {class: "k-text-center"},
		headerAttributes: {class: "k-text-center"}
	},
	{ command:
			[{ 	name: "xmlInvoice",
				iconClass:"k-icon k-i-file-programming",
				text: "",
				click: getXMLInvoice,
				title:"XML"							
			},{ 	
				name: "invoice",
				iconClass:"k-icon k-i-file-txt",
				text: "",
				click: showInvoice,
				title:"Factura"							
			},{ 	
				name: "email",
				iconClass:"k-icon k-i-envelop",
				text: "",
				click: sendInvoice,
				title:"Email"							
			},{ name: "rowMenu",
				iconClass:"k-icon k-i-more-vertical",
				text: "",
				click: rowMenu,
				title:"Menu",
				visible: function(dataItem) { return dataItem.type == 'OP'}						
			}],
			title: "", minScreenWidth: 801, width: "150px", attributes: { style: "text-align: center" },
			headerTemplate:''
			//headerTemplate:'<button type="button" class="k-col-pdf multiDownload k-state-disabled k-button k-button-md k-rounded-md k-button-solid k-button-solid-base k-icon-button" onclick="multiPDFDownload()"><span class="k-icon k-i-pdf k-button-icon"></span></button> \
			//				<button type="button" class="k-col-xml multiDownload k-state-disabled k-button k-button-md k-rounded-md k-button-solid k-button-solid-base k-icon-button" onclick="multiXMLDownload()"><span class="k-icon k-i-zip k-button-icon"></span></button>'
	},{
		field: "customer_name",
		title: "Client", minScreenWidth: 801
	},{
		field: "profile_name",
		title: "Profil"
	},{
		field: "far_date",
		title: "Data",
		type: "string"
	},{
		field: "dayIndexName",
		title: "Corelare",
		type: "string"
	},{
		field: "far_ea",
		title: "Realizat",
		type: "number",
		template:"#= kendo.toString(parseFloat(far_ea), 'n3') #"
	},{
		field: "avgDayIndex",
		title: "Medie Realizat",
		type: "number",
		template:"#= kendo.toString(parseFloat(avgDayIndex), 'n3') #"
	},{
		field: "proposedDayIndexName",
		title: "Propunere Corelare",
		type: "string"
	},{
		field: "proposedDayIndexAverage",
		title: "Medie Propunere",
		type: "number",
		template:"#= kendo.toString(parseFloat(proposedDayIndexAverage), 'n3') #"
	}
	];*/
}