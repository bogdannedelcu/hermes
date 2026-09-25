$( document ).ready(function() {
	
	getMinMaxYears('working_free_days','free_date',createPeriodFilter);

	let gridDS = {
			schema: {
				type:"json",						 
				model: {
				  id: "id",
				  /*fields: {
					paymentDate : {
						type: "date",
						parse: function(value) {
							return new Date(value*1000);
						}
					 },
					 email_ts : {
						type: "date",
						parse: function(value) {
							if(value) return new Date(value*1000);
						}
					 },
					 paymentAmount : {
						type:"number",
						parse: function(value) {
							if(value == null) return 0;
							return parseFloat(value);
						}
					 },
				  }*/
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
				url: window.location.origin + "/index.php/api?subject=custom&type=call&action=customersFreeDaysReport",
				dataType: "json",
				type:"post",
				data: function() {
				return {
						 models: [
						 {
							month:inaOptions['free_date'].selectedMonth,
							year:inaOptions['free_date'].selectedYear
						 }
						]
				  }                  
				}           
			},
			parameterMap: function (options, type) {
			  return kendo.stringify(options);
			}
		},
		/*aggregate: [
			{ field: "paymentAmount", aggregate: "sum"},
			{ field: "id", aggregate: "count"},
		], 
		sort: { field: "paymentDate", dir: "desc" },*/
		pageSize: 10000
	};

	let gridColumns = [
			/*{
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
			},*/{
				field: "source",
				title: "Sursa", minScreenWidth: 801
			},{
				field: "customer_name",
				title: "Client", minScreenWidth: 801
			},{
				field: "profile_name",
				title: "Profil", minScreenWidth: 801
			},{
				field: "far_date",minScreenWidth: 801,
				title: "Data",
				type: "string"
			},{
				field: "dayIndexName",minScreenWidth: 801,
				title: "Corelare",
				type: "string"
			},{
				field: "far_ea",minScreenWidth: 801,
				title: "Realizat",
				type: "number",
				template:"#= kendo.toString(parseFloat(far_ea), 'n3') #"
			},{
				field: "avgDayIndex",minScreenWidth: 801,
				title: "Medie Realizat",
				type: "number",
				template:"#= kendo.toString(parseFloat(avgDayIndex), 'n3') #"
			},{
				field: "proposedDayIndexName",minScreenWidth: 801,
				title: "Propunere Corelare",
				type: "string"
			},{
				field: "proposedDayIndexAverage",minScreenWidth: 801,
				title: "Medie Propunere",
				type: "number",
				template:"#= kendo.toString(parseFloat(proposedDayIndexAverage), 'n3') #"
			}
			];
	
	inaGrid('verify_free_days', 'grid', gridDS, gridColumns);
});

function createPeriodFilter(response)
{
	const currentDate = new Date();
	const lastYearDate = new Date(currentDate); lastYearDate.setFullYear(currentDate.getFullYear() - 1);
	inaPeriodFilter("working_free_days","free_date","Perioada",lastYearDate,response.minYear,response.maxYear,refreshGrid,badgesOptions);
}

function badgesOptions(id)
{
	return "";
}

function refreshGrid()
{
	resizeGrid();
	$("#grid").data("kendoGrid").dataSource.read();
}