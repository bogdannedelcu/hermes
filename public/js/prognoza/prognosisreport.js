$( document ).ready(function() {

	//create time period filter
	getMinMaxYears('forecast_estimates','forecast_datetime',createPeriodFilter);
	
	//create estimation types
	customCall({models:[{}]},'getFilterEstimationTypes',createEstimationTypesFilter);

	let gridCommands = 	[{ command:
			[{ 	name: "profileItem",
				iconClass:"k-icon fa-solid fa-arrow-right-arrow-left",
				text: "",
				click: function(e){	e.preventDefault();	var tr = $(e.target).closest("tr");	item = this.dataItem(tr); profileC(item);},
				title:"Edit",
				visible: function(e){ if(!e.far_ea || !e.invoiced_ea) return false; if(Math.abs(parseFloat(far_ea) - parseFloat(invoiced_ea)) > 1) return true; return false;}			
			}],
			title: "", minScreenWidth: 801, width: "120px", attributes: { style: "text-align: center" },
			//headerTemplate:'<button type="button" class="bulkProfileC k-state-disabled k-button k-button-md k-button-rectangle k-rounded-md k-button-solid k-button-solid-base k-icon-button" onclick="bulkProfileC()"><span class="k-icon fa-solid fa-arrow-right-arrow-left k-button-icon"></span></button>'
	}];
	
	let gridColumns = inaGridColumns(false, gridCommands, ["customer_name","consumptionType","forecast_ea","far_ea","invoiced_ea",""], 
										   titles=["Client","Tip Estimare","Prognoza", "Realizat (R)", "Facturat (F)","R-F"], 
										   types=["string","string","number", "number","number","number"]);
	
	gridColumns[3].template = "#= forecast_ea ? kendo.toString(parseFloat(forecast_ea), 'n3') : '' #";
	gridColumns[3].footerTemplate = "#= sum ? kendo.toString(parseFloat(sum), 'n3') : '' #";
	
	gridColumns[4].template = "#= far_ea ? kendo.toString(parseFloat(far_ea), 'n3') : '' #";
	gridColumns[4].footerTemplate = "#= sum ? kendo.toString(parseFloat(sum), 'n3') : '' #";
	
	gridColumns[5].template = "#= invoiced_ea ? kendo.toString(parseFloat(invoiced_ea), 'n3') : '' #";
	gridColumns[5].footerTemplate = "#= sum ? kendo.toString(parseFloat(sum), 'n3') : '' #";
	
	gridColumns[6].template = "#= (invoiced_ea && far_ea && (Math.abs(parseFloat(far_ea) - parseFloat(invoiced_ea)) > 1)) ? \"<span class='text-danger'>\"+kendo.toString(parseFloat(far_ea) - parseFloat(invoiced_ea), 'n3')+\"</span>\" : '' #";
	gridColumns[6].footerTemplate = function(data) {  return ((data.invoiced_ea.sum && data.far_ea.sum ) ? "<span class='text-danger'>"+kendo.toString(data.far_ea.sum - data.invoiced_ea.sum, 'n3')+"</span>" : '')};
	let gridToolBar = [];
	
	inaGrid('getForecastReport', 'grid', inaDS({custom:true,subject:"getForecastReport", model:{id:"customer_id",fields: { forecast_ea: { type: "number" },far_ea: { type: "number" },invoiced_ea: { type: "number" }}}, aggregate: [{ field: "forecast_ea", aggregate: "sum" },{ field: "far_ea", aggregate: "sum" },{ field: "invoiced_ea", aggregate: "sum" }], transportCallback:dsGridCallback}), gridColumns, gridToolBar);

});

function dsGridCallback()
{
	return {models:[{estimationType:inaOptions['estimation_types']?.selectedValue ?? '',month:inaOptions['forecast_datetime']?.selectedMonth ?? new Date().getMonth() + 1, year: inaOptions['forecast_datetime']?.selectedYear ?? new Date().getFullYear()}]};
}

function createPeriodFilter(response)
{
	const currentDate = new Date();
	inaPeriodFilter("forecast_estimates","forecast_datetime","Perioada",currentDate,response.minYear,response.maxYear,queryReport);

}

function createEstimationTypesFilter(response)
{
	inaFilterButtons("estimation_types_options", "estimation_types", -1, "Tip Estimare",response,queryReport,'','minw-100px',true);	
}

function profileC(dataItem) 
{
	var data = {
		models: [
				 {
					customers: [{customer_id: dataItem.customer_id, far_ea: dataItem.far_ea, invoiced_ea:dataItem.invoiced_ea}],
					year : inaOptions['forecast_datetime'].selectedYear,
					month: inaOptions['forecast_datetime'].selectedMonth
				 }
				]
		};
	kendo.ui.progress($(document.body), true);
	customCall(data,'updateCustomersProfile',onUpdateCustomersProfile);
}

function onUpdateCustomersProfile() 
{
	var grid = $("#grid").data("kendoGrid");
	grid.clearSelection();
	
	grid.dataSource.read();
}
	
function bulkProfileC() 
{
	var grid = $("#grid").data("kendoGrid");
	var customers = [];
	
	grid.selectedKeyNames().forEach((id)=>{
											let dataItem = grid.dataSource.get(id); 
											if(dataItem.canprofileC) 
												customers.push({customer_id:id, far_ea: dataItem.far_ea, invoiced_ea: dataItem.invoiced_ea});
										});
	
	var data = {
		models: [
				 {
					customers: customers,
					year : selYear_report_date-1,
					month: selMonth_report_date+1,
				 }
				]
		};
	kendo.ui.progress($(document.body), true);
	customCall(data,'updateCustomersProfile',onUpdateCustomersProfile);
}


function queryReport(e)
{
	
	if(inaOptions['estimation_types'] == undefined) return; 
	$("#grid").data("kendoGrid").dataSource.read();
	resizeGrid();
}

function consumption_typesChanged()
{
	$('#grid').data('kendoGrid').dataSource.read();
}

function onChange(e) 
{
	var grid = $("#grid").data("kendoGrid");
	
	let selection = this.selectedKeyNames();
	
	
	selection.filter(function(item) {
		return !grid.dataSource.get(item).canprofileC;
	}).forEach((id) => {var dataItem = grid.dataSource.get(id); 
						var row = grid.tbody.find("tr[data-uid='" + dataItem.uid + "']"); 
						row.find(":checkbox").prop('checked', false);
						row.removeClass("k-selected");
						});
	
	//selection.forEach((id) => {var dataItem = grid.dataSource.get(id); var row = grid.tbody.find("tr[data-uid='" + dataItem.uid + "']"); if(!dataItem.canprofileC){ row.find(":checkbox").prop('checked', false);row.removeClass("k-selected"); } }  );
	
	if(selection.length > 0)
	{
		$(".bulkProfileC").removeClass('k-state-disabled');
	}else
	{
		$(".bulkProfileC").addClass('k-state-disabled');
		//$("#grid").data("kendoGrid")._selectedIds=[];
	}
		
	
	//console.log("The selected product ids are: [" + this.selectedKeyNames().join(", ") + "]");
};

