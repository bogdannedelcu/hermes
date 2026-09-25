var spreadsheet = null;
var sheet = null;
var prevCustomers = [];

let gtDate = new Date();
gtDate.setDate(gtDate.getDate() + 1);
var prevDateRange = {start: gtDate, end:gtDate};
var currRow = 1;
var maxCols = -1;
var numDays = 31;
var historyRange = "";
var allDataRange = ""; // analitic
const maxColors = 100;

var originalSummaryvalues = [];
var intervalSize;

var vmin = 10000000;
var vmax = -10000000;
var colorCoef = 1;

var viewType = null;
var zoomType = null;

const smallRowHeight = 12;
const normalRowHeight = 18;
const smallFontSize = 10;
const normalFontSize = 12;

var subtot = [];

const getMethods = (obj) => {
  let properties = new Set()
  let currentObj = obj
  do {
    Object.getOwnPropertyNames(currentObj).map(item => properties.add(item))
  } while ((currentObj = Object.getPrototypeOf(currentObj)))
  return [...properties.keys()].filter(item => typeof obj[item] === 'function')
}

function getEstimationRange()
{
	return {
			start:$("#estimation_start_date").data("kendoDatePicker").value(),
			end:$("#estimation_end_date").data("kendoDatePicker").value()
			};
}

function openDatePicker(e)
{
	var calendar = this.dateView.calendar;
    calendar.navigate(prevDateRange.start, "month");
}

function dateChanged(e)
{
	if(this.element.context.id == 'estimation_start_date')
	{
		prevDateRange.start = this.value();
		$("#select_month_tp").data("kendoButtonGroup").select(prevDateRange.start.getMonth());
		$("#mCustomers").data("kendoMultiSelect").dataSource.read();
		if(prevDateRange.start>prevDateRange.end || prevDateRange.start.getMonth()!=prevDateRange.end.getMonth() || (viewType == 'Analitic' && prevDateRange.start!=prevDateRange.end)) 
		{
			let dt = $("#estimation_end_date").data("kendoDatePicker");
			dt.value(prevDateRange.start);
			dt.trigger("change");
		}
		else
			queryEstimatesReport();
	}
	else
	{
		prevDateRange.end = this.value();
		$("#select_month_tp").data("kendoButtonGroup").select(prevDateRange.end.getMonth());
		$("#mCustomers").data("kendoMultiSelect").dataSource.read();
		if(prevDateRange.end<prevDateRange.start || prevDateRange.start.getMonth()!=prevDateRange.end.getMonth() || (viewType == 'Analitic' && prevDateRange.start!=prevDateRange.end)) 
		{
			let dt = $("#estimation_start_date").data("kendoDatePicker");
			dt.value(prevDateRange.end);
			dt.trigger("change");
		}	
		else
			queryEstimatesReport();
	}
}

function forecastPeriodChanged()
{
	let diff = (prevDateRange.end.getTime() - prevDateRange.start.getTime()) / (1000 * 3600 * 24);
	
	let start = $("#estimation_start_date").data("kendoDatePicker").value();
	let end = $("#estimation_end_date").data("kendoDatePicker").value();
	
	start.setMonth(selMonth_tp);
	start.setYear(selYear_tp);
	
	end = kendo.date.addDays(start, diff);
	if(end.getMonth()!=start.getMonth()) end  = start;
	
	$("#estimation_start_date").data("kendoDatePicker").value(start);
	$("#estimation_end_date").data("kendoDatePicker").value(end);
	
	prevDateRange.start = start;
	prevDateRange.end = end;
	
	$("#estimation_start_date").data("kendoDatePicker").trigger("change");
	//$("#estimation_end_date").data("kendoDatePicker").trigger("change");
}

$( document ).ready(function() {
	
	let tomorrow = new Date();tomorrow.setDate(tomorrow. getDate() + 1);
	prevDateRange = { 
						start:tomorrow,
						end:tomorrow
					};
	$("#estimation_start_date").kendoDatePicker({
												value:tomorrow,
												change:dateChanged,
												format: "dd-MM-yyyy",
												open:openDatePicker
												});
	$("#estimation_end_date").kendoDatePicker({
												value:tomorrow,
												change:dateChanged,
												format: "dd-MM-yyyy",
												open:openDatePicker
												});
												
	$("#estimation_start_date").click(function() {
		$("#estimation_start_date").data("kendoDatePicker").open();
	});
	 
	$("#estimation_end_date").click(function() {
		$("#estimation_end_date").data("kendoDatePicker").open();
	});
	$("#estimation_start_date").attr("readonly", true);
	$("#estimation_end_date").attr("readonly", true);
	
	let cell = {"format":"#","background":"#ff0000","value":1};
	let cells = [cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell];
	let row = {cells};
	let rows = [row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row];
	spreadsheet = $("#spreadsheet").kendoSpreadsheet({
			rows:110,
			columns:500,
          sheets: [{
            name: "Sheet 1",
            rows: []
          }],  
		  changing: function(e){
            if(e.data !='')
				e.range.background(computeColor(parseFloat(e.data)));
			else
				e.range.background("#ffffff");
			
		}
        }).getKendoSpreadsheet();
		
	sheet = spreadsheet.activeSheet(); 

	
	if(Cookies.get('showCard-' + document.title) != 'true')
	{
		$(".k-spreadsheet-quick-access-toolbar").toggleClass("d-none"); $(".k-spreadsheet-tabstrip").toggleClass("d-none");	
		$("#collapseCard > div > div:nth-child(4)").toggleClass("d-none");
	}

	$('#zoom_types').data('kendoButtonGroup').select(0);
	zoomType = "Mic";
	
	$('#view_types').data('kendoButtonGroup').select(0);
	viewType = "Sumar";
	
	$('#view_interval').data('kendoButtonGroup').select(1);
	intervalType = "60min";
	
	resizeExcel();
	queryEstimatesReport();	
	
	customCall({models: [{}]},'getCustomersWhithoutOptions',customersAlertReceived);
});

function customersAlertReceived(response)
{
	if(response.length > 0)
	{
		$("body").append('<div id="dialog">');
		let dialog = $('#dialog');
		 
		dialog.kendoDialog({
			width: "450px",
			title: "Lista Clienti de Configurat",
			closable: false,
			modal: false,
			themecolor:"dark",
			content: "<p>&#x2022;&ensp;"+response.join("<br>&#x2022;&ensp;")+"</p>",
			actions: [
				{ text: 'Verifică', primary: true }
			],
			close: function(e){e.sender.destroy();},
        });
		
		dialog.data("kendoDialog").open();
		dialog.data("kendoDialog").center();
	}
}

//number to excel string
function ntoe(num)
{
	let d =  Math.floor(num/27);
	let c = num%27;
	
	if(d>0)
		return String.fromCharCode(d+64,c+65);
	
	return String.fromCharCode(c+64);
}

function setExcelTitle(numDays,title, selSheet = sheet)
{
	if(zoomType == "Normal") {fontSize = normalFontSize + 4; rowHeight = normalRowHeight + 2;}
	else if(zoomType == "Mic") {fontSize = smallFontSize; rowHeight = smallRowHeight;}

	var range = selSheet.range("A"+currRow+":"+ntoe(numDays)+currRow).merge().value(title.toUpperCase()).fontSize(fontSize).textAlign("center").bold(true);
	selSheet.rowHeight(currRow-1, rowHeight);
	currRow++;
	
	return range;
}

function setExcelRow(arr, selSheet = sheet)
{
	//var range = selSheet.range("A"+currRow+":"+ntoe(arr.length)+currRow).values([arr]);
	var range = selSheet.range("R"+currRow+"C1:R"+currRow+"C"+arr.length).values([arr]);
	currRow++;
	
	return range;
}

function addTotalRow(title,numDays,formula,format,startDataRow = 4, selSheet = sheet)
{
	//selSheet.range("A"+currRow).value(title).background("rgb(167,214,255)");
	selSheet.range("R"+currRow+"C1").value(title).background("rgb(167,214,255)");
	
	for(let i=1;i<=numDays;i++)
		selSheet.range('R'+currRow+'C'+(i+1)).formula('='+formula+'(R'+startDataRow+'C'+(i+1)+':R'+(currRow-1)+'C'+(i+1)+')');
		//selSheet.range(ntoe(i+1)+currRow).formula('='+formula+'('+ntoe(i+1)+'4:'+ntoe(i+1)+'27)');

	selSheet.range("R"+currRow+"C1:R"+currRow+"C"+(numDays+1)).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
	selSheet.range("R"+currRow+"C2:R"+currRow+"C"+(numDays+1)).format(format);
	
	//selSheet.range("A"+currRow+":"+ntoe(numDays+1)+currRow).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
	//selSheet.range("B"+currRow+":"+ntoe(numDays+1)+currRow).format(format);
		//arr.push('='+formula+'('+ntoe(i+1)+'4:'+ntoe(i+1)+'27)');
	
	//setExcelRow(arr).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");

	var range = selSheet.range("R"+currRow+"C1:R"+currRow+"C"+(numDays+1));
	
	currRow++;

	return range;
}

function addTotalRow2(title,numDays,formula,format,startDataRow = 4,endDataRow = currRow, selSheet = sheet)
{
	//selSheet.range("A"+currRow).value(title).background("rgb(167,214,255)");
	selSheet.range("R"+currRow+"C1").value(title).background("rgb(167,214,255)");
	
	for(let i=1;i<=numDays;i++)
		selSheet.range('R'+currRow+'C'+(i+1)).formula('=IFERROR('+formula+'(R'+startDataRow+'C'+(i+1)+':R'+(endDataRow-1)+'C'+(i+1)+'), "")');
		//selSheet.range(ntoe(i+1)+currRow).formula('='+formula+'('+ntoe(i+1)+'4:'+ntoe(i+1)+'27)');

	selSheet.range("R"+currRow+"C1:R"+currRow+"C"+(numDays+1)).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
	selSheet.range("R"+currRow+"C2:R"+currRow+"C"+(numDays+1)).format(format);
	
	//selSheet.range("A"+currRow+":"+ntoe(numDays+1)+currRow).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
	//selSheet.range("B"+currRow+":"+ntoe(numDays+1)+currRow).format(format);
		//arr.push('='+formula+'('+ntoe(i+1)+'4:'+ntoe(i+1)+'27)');
	
	//setExcelRow(arr).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");

	var range = selSheet.range("R"+currRow+"C1:R"+currRow+"C"+(numDays+1));
	
	currRow++;

	return range;
}

function setRangeBackground(colorRanges, selSheet = sheet) {

	Object.keys(colorRanges).forEach(key => {
	  selSheet.range(colorRanges[key].slice(0, -1)).background(key);
	});	
}

function daysInMonth(date) {
  
  return parseInt(kendo.toString(kendo.date.lastDayOfMonth(date),"dd"));
}

function computeColor(value, mmin = vmin, mmax = vmax, cCoef = colorCoef)
{
	if(isNaN(value)) return '#ffffff';
	if(value<mmin) value = mmin;
	if(value>mmax) value = mmax;
	
	let v= Math.abs(255-Math.floor(255/maxColors) * Math.floor(cCoef * (value-mmin)));
	let h='0';
	
	if(v<16) h = h+ v.toString(16);
	else h = v.toString(16);
	//console.log('#' + h + h);
	return '#ff' + h + h ;
}

/**********events********************/
function mClose(e)
{
	console.log('query mClose');
	//avoid multiple runs!
	if($("#mCustomers").data("kendoMultiSelect").value().join() != prevCustomers.join())
	{	
		prevCustomers = $("#mCustomers").data("kendoMultiSelect").value();
		
		/*
		setTimeout(function() {
			queryEstimatesReport();
		}, 0);*/
		
	}

}

function mDeselect()
{
	
	//avoid multiple runs
	$('#mCustomers').data("kendoMultiSelect").open();
}

function mChange()
{
	console.log('query mChange');
	if(viewType == 'Analitic')
	{
		if(prevCustomers.length != 0)
		{
			let v = $("#mCustomers").data("kendoMultiSelect").value();
			prevCustomers = v.filter(n => !prevCustomers.includes(n));
			$("#mCustomers").data("kendoMultiSelect").value(prevCustomers);
		}
		else
			prevCustomers = $("#mCustomers").data("kendoMultiSelect").value();	
	}
	else
		prevCustomers = $("#mCustomers").data("kendoMultiSelect").value();
	
	queryEstimatesReport();
}

function mSelect()
{
	console.log('query mSelect');
	//avoid multiple runs
	if(viewType == 'Analitic')
	{
		$('#mCustomers').data("kendoMultiSelect").close();
		/*
		$("#mCustomers").data("kendoMultiSelect").value([]);	
		
		prevCustomers = $("#mCustomers").data("kendoMultiSelect").value();
		$('#mCustomers').data("kendoMultiSelect").close();
		*/
	}
}

function view_intervalChanged(e)
{
	intervalType = $("#view_interval").data("kendoButtonGroup").current().text().trim();
	if(intervalType == "15min")
		$("#uploadEstimates").hide();
	else
		$("#uploadEstimates").show();
	
	queryEstimatesReport();
}

function zoom_typesChanged(e)
{
	
	zoomType = $("#zoom_types").data("kendoButtonGroup").current().text().trim();
	
	queryEstimatesReport();
}

function view_typesChanged(e)
{
	viewType = $("#view_types").data("kendoButtonGroup").current().text().trim();
	
	if(viewType == "") 
		viewType = 'Sumar';
	
	if(viewType == 'Sumar')
	{
		$('#zoom_types').data('kendoButtonGroup').select(0);
		zoomType = "Mic";
		$('#zoom_label').show();
		$('#zoom_types').show();
		
		$('#view_interval').show();
		$('#interval_label').show();

		
		$('#extra_info').show();
		$('#extra_info_label').show();
	}
	else if(viewType == 'Lunar') 
	{
		$('#zoom_types').data('kendoButtonGroup').select(1);
		zoomType = "Normal";
		$('#zoom_types').hide();
		$('#zoom_label').hide();
		
		$('#view_interval').show();
		$('#interval_label').show();
		
		$('#extra_info').hide();
		$('#extra_info_label').hide();
	}
	else
	{
		$('#zoom_types').data('kendoButtonGroup').select(1);
		zoomType = "Normal";
		$('#zoom_types').hide();
		$('#zoom_label').hide();
		
		$('#extra_info').hide();
		$('#extra_info_label').hide();		
		
		$('#view_interval').hide();
		$('#interval_label').hide();
	}
	
	if(viewType == 'Analitic')
	{

		if(prevDateRange.start != prevDateRange.end)
		{
			let dt = $("#estimation_end_date").data("kendoDatePicker");
			dt.value(prevDateRange.start);
			dt.trigger("change");
		}
			
		if($("#mCustomers").data("kendoMultiSelect").value().length != 1)
		{
			$("#mCustomers").data("kendoMultiSelect").value([]);
			$("#mCustomers").data("kendoMultiSelect").setOptions({placeholder:"Selectati doar un client"});
			prepHistoryViewEstimates();
			return;
		}

	}
	else
		$("#mCustomers").data("kendoMultiSelect").setOptions({placeholder:"Toti clientii"});
	
	queryEstimatesReport();
}

function consumption_typesChanged()
{
	if ($("#consumption_types").data("kendoButtonGroup").current().index() == -1)
		$("#mCustomers").data("kendoMultiSelect").dataSource.filter(null);
	else
	{
		let filter = 
			{
				field:"consumption_type_name",
				operator:"contains",
				value:$("#consumption_types").data("kendoButtonGroup").current().text().trim()
			};
		
		$("#mCustomers").data("kendoMultiSelect").dataSource.filter(filter);
	}
	
	queryEstimatesReport();
}

function queryEstimatesReport(estimatesResponse)
{		

	$("#spreadsheet").data("kendoSpreadsheet").activeSheet(spreadsheet.sheets()[0]);
	for(let s=(spreadsheet.sheets().length-1);s>0;s--)
		spreadsheet.removeSheet(spreadsheet.sheetByIndex(s));
	
	
	sheet.batch(function() {
	
		if(viewType == 'Lunar') 
			prepPresentViewEstimates();
		else if (viewType == 'Analitic')
			prepHistoryViewEstimates();
		else 
			prepSummaryViewEstimates();
		
		if(viewType == 'Analitic' && prevCustomers.length != 1) return;
		getEstimates();
	
		sheet.select("A1");
		 $(".k-spreadsheet-scroller").scrollTop(0).scrollLeft(0);
	});
	
	if(estimatesResponse)
	{
		
		let respSheet = spreadsheet.insertSheet({name:"mesaje"});
		let i=1;
		respSheet.columnWidth(0,700);
		
		estimatesResponse.split("<br>").forEach((e) => {respSheet.range("R"+i+"C1").value(e);i++;});
		
		if(i>2)
			$('#staticNotification').data('kendoNotification').show("Ai verificat erorile de estimare?", 'error');
		else
			$('#staticNotification').data('kendoNotification').show("Estimarea a fost finalizata.", 'info');
	}
}

function getEstimates()
{
	kendo.ui.progress($(document.body), true);
			
	let dr = getEstimationRange();
	var start = performance.now();
	
	var data = {
			models: [
					 {
						customers :prevCustomers,
						month : selMonth_tp+1,
						year: selYear_tp,
						viewType: viewType,
						intervalType:intervalType,
						consumptionType:selected_consumption_types,
						eStart:kendo.toString(dr.start,"yyyy-MM-dd"),
						eEnd:kendo.toString(dr.end,"yyyy-MM-dd")
					 }
					]
			};
			
	var jqxhr = $.post({
					url: window.location.origin+'/api?subject=custom&type=call&action=readEstimates',
					data: JSON.stringify(data),
					contentType: 'application/json; charset=utf-8'})
			.done(function(response) {
				if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
				
					kendo.ui.progress($(document.body), false);
					$('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');

					return false;
				}
				else
				{
					var end = performance.now();
					//$('#staticNotification').data('kendoNotification').show("Calcul = "+(end - start),'error');
					console.log("Calcul = "+(end - start));
					start = performance.now();
					sheet.batch(function() {
					if(viewType == 'Lunar') 
						fillPrezentViewEstimates(response);
					else if(viewType == 'Analitic')
						fillHistoryViewEstimates(response);
					else
					{ //sumary view
						if($("#extraInfo").data("kendoSwitch").value())
							customCall(data,'getEstimateTrend',estimateTrendReceived);
						fillSummaryViewEstimates(response);
					}
					});
					
					if(viewType == 'Analitic' && allDataRange!='')
					{
						sheet.batch(function() {
							let allData = sheet.range(allDataRange).values();
							if(allData.length > 0 && allData[0].length > 0)
								setRangeBackground(dataToColorRange(allData[0].length,4,1,allData));
						});
					}
					
					end = performance.now();
					//$('#staticNotification').data('kendoNotification').show("Afisare = "+(end - start),'error');
					console.log("Afisare = "+(end - start));
					
					kendo.ui.progress($(document.body), false);
					
				}
			})
			.fail(function() {
				
				kendo.ui.progress($(document.body), false);
				
				//probleme de retea
				$('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
				return false;
			});
	
}

function onExtraInfoChange(e)
{
	if(e.checked)
	{
		let dr = getEstimationRange();
			
		var data = {
				models: [
						 {
							customers :prevCustomers,
							month : selMonth_tp+1,
							year: selYear_tp,
							viewType: viewType,
							consumptionType:selected_consumption_types,
							eStart:kendo.toString(dr.start,"yyyy-MM-dd"),
							eEnd:kendo.toString(dr.end,"yyyy-MM-dd"),
						 }
						]
				};
		customCall(data,'getEstimateTrend',estimateTrendReceived);
	}
}

function estimateTrendReceived(response)
{
	if (response.length == 0) return;
	let lastCustomerName = '';
	
	
	fontSize = normalFontSize;
	if(zoomType == "Mic") fontSize = smallFontSize;
	
	let cidx = 2;
	sheet.batch(function() {
		for(c = 2;c<=maxCols;c++)
		{
			for(i = 0; i<intervalSize; i++)
			{
				if(sheet.range("R2C"+cidx).value() != '') lastCustomerName = sheet.range("R2C"+cidx).value();

				day = sheet.range("R4C"+(c+i)).value();
				el = response.find(( el ) => el.customer_name == lastCustomerName && parseInt(el.forecast_date.substring(8)) == day);
				
				if(el !== undefined && el.change_ea_per != null)
				{
					let pea = parseFloat(el.change_ea_per);
					let tColor = "red";
					if (pea>0) tColor = "green";
					
					sheet.range("R30C"+cidx).value(pea).format("0.00 \\\%").textAlign("center").color(tColor).bold(true).fontSize(fontSize);
				}
				
				cidx++;
			}
		}
	});
}

function prepPresentViewEstimates()
{
	let now = new Date();
	
	if(selYear_tp != -1 && selMonth_tp != -1)
		now = new Date(selYear_tp,selMonth_tp);
	else
		now.setDate(0);

	numDays = daysInMonth(now);

	//sheet = spreadsheet.insertSheet({name:now.toLocaleString('ro-ro',{month:'short', year:'numeric'})});
	//spreadsheet.removeSheet(spreadsheet.sheetByIndex(0));	
	sheet.range("A1:AF100").clear().values('').fontSize(normalFontSize);
	sheet.range(kendo.spreadsheet.SHEETREF).clear();
	sheet.rowHeight(1, 20);
		
	currRow = 1;
	
	if(sheet === undefined) return;
	
	//set column width
	let subtitle1 =[''];
	let subtitle2 =['Interval'];
	sheet.columnWidth(0,55);
	
	for(let i=1;i<=numDays;i++)
	{

		let d = new Date(now.getTime());
		d.setDate(i); 

		subtitle1.push(d.toLocaleDateString("ro-ro", { weekday: 'narrow' }));
		subtitle2.push(i);

		sheet.columnWidth(i,45);
	}

	//set title
	setExcelTitle(numDays+1,'Estimari '+now.toLocaleString('ro-ro',{month:'short', year:'numeric'}) );
	
	//set subtitle
	setExcelRow(subtitle1).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
	setExcelRow(subtitle2).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
	
	sheet.rowHeight(1,normalRowHeight);
	sheet.rowHeight(2,normalRowHeight);
}

function onPrezentGetFreeDays(response)
{
	if(intervalType == "60min")
		viewSize = 24+4;
	else
		viewSize = 96+4;
	
	let r = [];
	for(let i = 0;i<response.freeDays.length;i++)
	{
		r.push("R2C"+ (response.freeDays[i]+1),"R3C"+ (response.freeDays[i]+1),"R"+viewSize+"C"+ (response.freeDays[i]+1),"R"+(viewSize+1)+"C"+ (response.freeDays[i]+1));
	}
	
	sheet.range(r.join(",")).background("rgb(184,235,255)");

}

function fillPrezentViewEstimates(response)
{
	let arr=[];
	let rIdx=0;
	vmin = 10000000;
	vmax = -10000000;
	
	if(intervalType == "60min")
		viewSize = 24;
	else
		viewSize = 96;
	
	for(let h=0;h<viewSize;h++)
	{
		arr.push([parseInt(h)+1]);
		for(let d=1;d<=numDays;d++)
		{
			if(response[rIdx] !== undefined && ((viewSize == 24 && response[rIdx].hour == h) || (viewSize == 96 && response[rIdx].hour*4 + response[rIdx].minute/15 == h)) && response[rIdx].day == d)
				{
					let fea = '';
					if(response[rIdx].forecast_ea != null)
					{
						fea = parseFloat(response[rIdx].forecast_ea);
						if(vmin>fea) vmin = fea;
						if(vmax<fea) vmax = fea;
					}
					arr[h].push(fea);
					rIdx++
				}
			else
				arr[h].push('');
		}
	}
	currRow+=viewSize;
	colorCoef = maxColors / ((vmax-vmin) == 0 ? 0.000001 : (vmax-vmin));
	
	excelViewSize = viewSize + 3;
	
	let rangeMax = "AF"+excelViewSize;
	
	switch(numDays) {
	  case 31:
		rangeMax = "AF"+excelViewSize;
		break;
	  case 30:
		rangeMax = "AE"+excelViewSize;
		break;
	  case 29:
		rangeMax = "AD"+excelViewSize;
		break;
	  case 28:
		rangeMax = "AC"+excelViewSize;
		break;
	}	
	
	sheet.range("A4:"+rangeMax).values(arr);
	var hourRange = sheet.range("A4:A"+excelViewSize).bold(true).textAlign("right");
	
	var valRange = sheet.range("B4:"+rangeMax).format("0.000").textAlign("center");
	
	
	//constru colorRanges to avoid forEachCell background set due to slow
	let colorRanges=[];
	for(let h=0;h<viewSize;h++)
	{
		sheet.rowHeight(h+3,normalRowHeight);
		for(let d=1;d<=numDays;d++)
		{
			let s = '';
			if(arr[h][d]!= '')
			{	 
				let color = computeColor(arr[h][d]);
				if(colorRanges[color] !== undefined)
					s = colorRanges[color];
				
				
				colorRanges[color] = s.concat(ntoe(d+1)+(h+4)+",");
			}
			else
			{
				if(colorRanges['#ffffff'] !== undefined)
					s = colorRanges['#ffffff'];
				
				colorRanges['#ffffff'] = s.concat(ntoe(d+1)+(h+4)+",");
			}
		}
	}
	
	
	console.log("ColorRanges Length="+Object.keys(colorRanges).length);
	setRangeBackground(colorRanges);
	
	sheet.range(ntoe(parseInt(kendo.toString(prevDateRange.start,"dd"))+1)+'4:'+ntoe(parseInt(kendo.toString(prevDateRange.start,"dd"))+1)+excelViewSize).borderLeft({ size: 2, color: "rgb(167,214,255)" });
	sheet.range(ntoe(parseInt(kendo.toString(prevDateRange.end,"dd"))+1)+'4:'+ntoe(parseInt(kendo.toString(prevDateRange.end,"dd"))+1)+excelViewSize).borderRight({ size: 2, color: "rgb(167,214,255)" });
				
	//dateRangeChange();
	//addTotalRow('Media',numDays,'AVERAGE','0.000');
	sheet.rowHeight(currRow-1,normalRowHeight);
	let multiply = '';
	if(intervalType == '60min') multiply = '4*';
	addTotalRow2('Putere',numDays,'AVERAGE','0.000');
	addTotalRow2('Energie',numDays,multiply+'SUM','0.000',4,currRow-1);
	
	
	
	var data = {
			models: [
					 {
						year : selYear_tp,
						month: selMonth_tp+1
					 }
					]
			};
	
	customCall(data,'getFreeDays',onPrezentGetFreeDays);
	
	/*hourRange.forEachCell(function (row, column, cellProperties) {
		if(row<40 && column < 40 && cellProperties.value !== undefined && cellProperties.value != '')
			//cellProperties.background="blue";
			sheet.range(row,column).background(computeColor(cellProperties.value));
	
	});*/
}

function prepHistoryViewEstimates()
{
	sheet.range("A1:AH50").clear().values('');
	sheet.range(kendo.spreadsheet.SHEETREF).clear();
	sheet.rowHeight(1, 20);
	sheet.columnWidth(0,70);
	sheet.columnWidth(1,70);
	sheet.columnWidth(2,70);
	currRow = 1;
	currCol = 1;
	
	if(sheet === undefined) return;
}

function prepSummaryViewEstimates()
{
	$("#spreadsheet").data("kendoSpreadsheet").activeSheet().range("R1C1:R100C500").values('').clear();
	currRow = 1;
		
	let now = new Date();
	
	if(selYear_tp != -1 && selMonth_tp != -1)
		now = new Date(selYear_tp,selMonth_tp);
	else
		now.setDate(0);

	numDays = daysInMonth(now);

	sheet.range("A1:AF100").clear().values('');
	
	if(sheet === undefined) return;
	
	//set column width
	sheet.columnWidth(0,65);
	

	/*	let d = new Date(now.getTime());
		d.setDate(i); 

		subtitle1.push(d.toLocaleDateString("ro-ro", { weekday: 'narrow' }));
		subtitle2.push(i);

		sheet.columnWidth(i,45);
	}*/

	//set title
	if(prevDateRange.start.toString() == prevDateRange.end.toString())
		setExcelTitle(numDays+1,'Sumar Estimari '+prevDateRange.start.toLocaleString('ro-ro',{day:'2-digit', month:'short', year:'numeric'}) ).textAlign("center");
	else
		setExcelTitle(numDays+1,'Sumar Estimari '+prevDateRange.start.toLocaleString('ro-ro',{day:'2-digit', month:'short', year:'numeric'}) + ' - ' + prevDateRange.end.toLocaleString('ro-ro',{day:'2-digit',month:'short', year:'numeric'}) ).textAlign("center");
	
	//set subtitle
	//setExcelRow(subtitle1).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
	//setExcelRow(subtitle2).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");	
	return;
}

function fillSummaryViewEstimates(response)
{
	let subtitle1 =[''];
	let subtitle2 =[''];
	let subtitle3 =['Interval'];
	
	let h=1;
	
	let data=response['data'];
	let customers=response['customers'];
	intervalSize = response['intervalSize'];
	
	if (intervalSize > 1) sheet.rowHeight(1, 70);
	
	let row = [];
	let arr = [];
	let forecast = {};
	
	vmin = 10000000;
	vmax = -10000000;
	
	maxCols = customers.length + 1;
	
	if(intervalType == "60min")
		viewSize = 24;
	else
		viewSize = 96;
	
	for(let h=0;h<viewSize;h++)
	{		
		for(let minute=0;(viewSize==96 && minute<=45)||(viewSize==24 && minute == 0);minute+=15)
		{
			row = [];
			let rowIndex = h+minute/15+4;
			
			if(zoomType == "Normal") 
				sheet.rowHeight(rowIndex,16);
			else
				sheet.rowHeight(rowIndex,12);
			
			for(let c = 0;c<customers.length;c++)
			{
				for(let i=0;i<intervalSize;i++)
				{	
					if(i == 0)
					{
						if(zoomType == "Normal") 
							sheet.columnWidth(1+c+i,60);
						else
							sheet.columnWidth(1+c+i,40);
					}
					
					let t = kendo.date.addDays(prevDateRange.start,i);
					
					if(h == 0 && minute == 0)
					{	
						if(i == 0)
						{
							forecast = data.find(({ customer_id }) => customer_id === customers[c]);
							subtitle1.push(forecast.customer_name);
						}
						else subtitle1.push('');
						
						subtitle2.push(t.toLocaleDateString("ro-ro", { weekday: 'narrow' }));
						subtitle3.push(t.toLocaleDateString("ro-ro", { day: 'numeric' }));
					}
					
					
					let datehour = kendo.toString(t,'yyyy-MM-dd ')+h.toString().padStart(2, '0')+':'+minute.toString().padStart(2, '0')+':00';
					
					/*
					for(let dt=0;dt<data.length;dt++)
					{
						if(data[dt].customer_id == customers[c] && (data[dt].forecast_datetime == datehour))
						{
							forecast = data[dt];
							data.splice(dt,1);
							debugger;
							break;
						}						
					}*/
					//forecast = data.filter(function(v, i) { return ((v.customer_id == customers[c] && v.forecast_datetime == datehour)); });
					
					forecast = data.find(function(dt, index) {
						if(dt.customer_id == customers[c] && (dt.forecast_datetime == datehour))
							return true;
					});
					
					if(forecast !== undefined)
					{
						fea = parseFloat(forecast.forecast_ea);
						row.push(fea);
						if(vmin>fea) vmin = fea;
						if(vmax<fea) vmax = fea;
					}
					else
						row.push('');
				}
			}
			
			arr.push(row);
		}
	}
	
	if(intervalSize>1)
	{
		startTime = new Date();
		for(let c = 0;c<customers.length;c++)
			sheet.range("R2C"+(2+c*intervalSize)+":R2C"+(1+c*intervalSize+intervalSize)).merge();

		endTime = new Date();
		var timeDiff = endTime - startTime; //in ms
		  // strip the ms
		timeDiff /= 1000;

		  // get seconds 
		var seconds = Math.round(timeDiff);
		console.log("merge: "+seconds + " seconds");  
	}
	
	//sheet.resize(50, Math.max(customers.length*intervalSize+20,100));
	
	if(customers.length > 0)
	{	
		let hours = [];
		for(let i=0;i<=viewSize;i++)
			hours.push([i+1]);
		
		if(zoomType == "Normal") 
		{
			fontSize = normalFontSize; rowHeight = normalRowHeight;
			
			sheet.range('R5C1:R'+(viewSize+4)+'C1').values(hours).bold(true).textAlign("center").fontSize(fontSize);
			setExcelRow(subtitle1).background("rgb(167,214,255)").color("black").bold(true).textAlign("center").wrap(true).fontSize(fontSize);
			setExcelRow(subtitle2).background("rgb(167,214,255)").color("black").bold(true).textAlign("center").fontSize(fontSize);
			setExcelRow(subtitle3).background("rgb(167,214,255)").color("black").bold(true).textAlign("center").fontSize(fontSize);
			for(i = 2; i<(viewSize+4); i++)
				sheet.rowHeight(i, rowHeight);
		}
		else if(zoomType == "Mic") 
		{
			fontSize = smallFontSize; rowHeight = smallRowHeight;
			
			sheet.range('R5C1:R'+(viewSize+4)+'C1').values(hours).bold(true).textAlign("center").fontSize(fontSize);
			setExcelRow(subtitle1).background("rgb(167,214,255)").color("black").bold(true).textAlign("center").fontSize(fontSize);
			setExcelRow(subtitle2).background("rgb(167,214,255)").color("black").bold(true).textAlign("center").fontSize(fontSize);
			setExcelRow(subtitle3).background("rgb(167,214,255)").color("black").bold(true).textAlign("center").fontSize(fontSize);		
			for(i = 1; i<(viewSize+4); i++)
				sheet.rowHeight(i, rowHeight);
		}
		
		
		
				
		sheet.range("R5C2:R"+(viewSize+4)+"C"+(1+customers.length*intervalSize)).values(arr).format("0.000").textAlign("center").fontSize(fontSize);
		
		setRangeBackground(dataToColorRange(customers.length*intervalSize,5,1,arr));
		currRow+=viewSize;
		
		let multiply ='';
		if(intervalType == '60min')
			multiply = '4*';
		
		addTotalRow2('Putere',customers.length*intervalSize,'AVERAGE','0.000',5).fontSize(fontSize);	
		addTotalRow2('Energie',customers.length*intervalSize,multiply+"SUM","0.000",5,currRow-1).fontSize(fontSize);
		sheet.range("R"+currRow+"C1").formula("=SUM(R"+(currRow-1)+"C2:R"+(currRow-1)+"C"+(customers.length*intervalSize+1)+")").background("rgb(167,214,255)").color("black").bold(true).textAlign("center").format("0.000").fontSize(fontSize);
		currRow+=2;

		sheet.range("R"+currRow+"C1").value("Nr. Clienti").background("rgb(167,214,255)").color("black").bold(true).textAlign("center").format("0").fontSize(fontSize);
		sheet.range("R"+currRow+"C2").value(customers.length).background("rgb(167,214,255)").color("black").bold(true).textAlign("center").format("0").fontSize(fontSize);
	}
	
	originalSummaryvalues = sheet.range("R5C2:R"+(viewSize+4)+"C"+((maxCols-1)*intervalSize+1)).values();
	
	//transpose
	originalSummaryvalues = originalSummaryvalues[0].map((_, colIndex) => originalSummaryvalues.map(row => row[colIndex]));
		
	return;
}

function responseToData(iSize,startDate, subtitle1, subtitle2, responseArray)
{
	var ret = [];
	
	let firstDay = startDate.getDate();
	
	//header
	for(let d=1;d<=iSize;d++)
	{
		let t = kendo.date.addDays(startDate,d-1);
		subtitle1.push(t.toLocaleDateString("ro-ro", { weekday: 'narrow' }));
		subtitle2.push(t.toLocaleDateString("ro-ro", { day: 'numeric' }));
	}
	
	//data
	let arr = responseArray;
	let rIdx=0;
	let data=[];
	
	if(arr.length > 0)
	{
		for(let h=0;h<24;h++)
		{
			data.push([]);
			for(let d=1;d<=iSize;d++)
			{	
				let el = arr.find(function(dt, index) {
					if(dt.hour == h && (dt.day- firstDay + 1 == d))
						return true;
				});
				
				if(el !== undefined)
				{
					let fea = '';
					if(el.ea != null)
					{
						fea = parseFloat(el.ea);
						if(vmin>fea) vmin = fea;
						if(vmax<fea) vmax = fea;
					}
					
					data[h].push(fea);
				}
				else
					data[h].push('');
				
				/*if(arr[rIdx] !== undefined && arr[rIdx].hour == h && (arr[rIdx].day- firstDay + 1) == d)
					{
						let fea = parseFloat(arr[rIdx].ea);
						data[h].push(fea);
						if(vmin>fea) vmin = fea;
						if(vmax<fea) vmax = fea;
						rIdx++
					}
				else
					data[h].push('');*/
			}
		}
		ret = data;
	}
	
	return ret;
}

function dataToColorRange(intervalSize,rowOffset,colOffset,data)
{
	if(data.length == 0) return [];
	
	colorCoef = maxColors / ((vmax-vmin) == 0 ? 0.000001 : (vmax-vmin));
	
	//constru colorRanges to avoid forEachCell background set due to slow

	if(intervalType == "60min" || viewType == 'Analitic')
		viewSize = 24;
	else
		viewSize = 96;
	
	let colorRanges=[];
	for(let h=0;h<viewSize;h++)
	{
		for(let d=1;d<=intervalSize;d++)
		{
			let s = '';
			if(data[h][d-1]!= '')
			{	 
				let color = computeColor(data[h][d-1]);
				if(colorRanges[color] !== undefined)
					s = colorRanges[color];
				
				
				colorRanges[color] = s.concat("R"+(h+rowOffset)+"C"+(d+colOffset)+",");
			}
			else
			{
				if(colorRanges['#ffffff'] !== undefined)
					s = colorRanges['#ffffff'];
				
				colorRanges['#ffffff'] = s.concat("R"+(h+rowOffset)+"C"+(d+colOffset)+",");
			}
		}
	}
	
	console.log("ColorRanges Length="+Object.keys(colorRanges).length);
	
	return colorRanges;
}

function fillHistoryViewEstimates(response)
{
	if(response.customer_name === undefined) return;
	
	//set title
	if(prevDateRange.start.toString() == prevDateRange.end.toString())
		setExcelTitle(numDays+1,'Analitic Estimare ' + response.consumption_type + ': '+ response.customer_name + ' '+ prevDateRange.start.toLocaleString('ro-ro',{day:'2-digit', month:'short', year:'numeric'}) ).textAlign("left");
	else
		setExcelTitle(numDays+1,'Analitic Estimari '+prevDateRange.start.toLocaleString('ro-ro',{day:'2-digit', month:'short', year:'numeric'}) + ' - ' + prevDateRange.end.toLocaleString('ro-ro',{day:'2-digit',month:'short', year:'numeric'}) ).textAlign("left");
	
	let synXls=[];
	let estXls=[];
	
	vmin = 10000000;
	vmax = -10000000;
	allDataRange = '';
	
	var intervalSize = Math.floor((prevDateRange.end-prevDateRange.start)/24/3600/1000)+1;
		
	let subtitle1 =['','Prognoza','Medie'];
	let subtitle2 =['Interval','',response['history_type']];
	subtot = [];
	
	let lastColumn = 2;
	
	if(response['manual_estimate'])
		subtitle2[1] = "Manuala";
	else
		subtitle2[1] = "Automata";
	
	response['history_interval'].forEach((element) => {
		let t = kendo.parseDate(element.date, "yyyy-MM-dd");
		subtitle1.push(t.toLocaleDateString("ro-ro", { weekday: 'narrow' }));
		subtitle2.push(t.toLocaleDateString("ro-ro", { year:'2-digit',month:"2-digit", day: 'numeric' }));
		let fea = parseFloat(element.ea);
		subtot.push(fea);
		});
	
	/*
	if(response['estimates'].length > 0 )
	{
		let t = kendo.parseDate(response['estimates'][0].forecast_datetime, "yyyy-MM-dd");
		subtitle2[1] = t.toLocaleDateString("ro-ro", { year:'2-digit',month:"2-digit", day: 'numeric' });
	}*/
		
	fontSize = normalFontSize; rowHeight = normalRowHeight;
	setExcelRow(subtitle1).background("rgb(167,214,255)").color("black").bold(true).textAlign("center").fontSize(fontSize);
	setExcelRow(subtitle2).background("rgb(167,214,255)").color("black").bold(true).textAlign("center").fontSize(fontSize);
	
	//add vertical headder
	let iSize = response['history_interval'].length;
	
	let currCol = 1;
	let vHeader = [];
	let historyValues = [];
	let prognosisValues = [];
	
	const zeroPad = (num, places) => String(num).padStart(places, '0');
	
	let idx=0;
	for(let h=0;h<24;h++) 
	{
		sheet.rowHeight(h+1,rowHeight);
		//hours
		vHeader.push([h+1]);
		
		//formula
		sheet.range('R'+(h+4)+'C3').formula('=IFERROR(AVERAGE(R'+(h+4)+'C4'+':R'+(h+4)+'C'+(iSize + 3)+'),"")').format("0.000").textAlign("center");
		
		var ed = [];
		for(let d=0;d<iSize;d++)
		{
			if(h == 0)
				sheet.columnWidth(3+d,70);
				
			let hday = response['history_interval'][d].date+" "+zeroPad(h,2);
			let el = response['history'].find(function(dt, index) {
					let t = dt.far_datetime.slice(0,-6);
					
					if(t == hday)
						return true;
				});
			
			if(el !== undefined)
			{
				let fea = '';
				if(el.far_ea != null)
				{
					fea = parseFloat(el.far_ea);
					if(vmin>fea) vmin = fea;
					if(vmax<fea) vmax = fea;
				}
				
				ed.push(fea);
			}
			else
				ed.push('');
		}
		
		historyValues.push(ed);
		
		//current estimates
		let ce = response['estimates'].find(function(dt, index) {
					let t = kendo.parseDate(dt.forecast_datetime, "yyyy-MM-dd HH:mm:ss");
					
					if(t.getHours() == h)
						return true;
				});
				
		let fea = '';
		if(ce !== undefined && ce.forecast_ea!= null)
		{
			fea = parseFloat(ce.forecast_ea);
			if(vmin>fea) vmin = fea;
			if(vmax<fea) vmax = fea;
		}

		
		prognosisValues.push([fea]);
	}

	sheet.range("A4:A27").values(vHeader).bold(true).textAlign("right").fontSize(fontSize);
	sheet.range("R4C4:R27C"+(3+iSize)).values(historyValues).format("0.000").textAlign("center").fontSize(fontSize);
	sheet.range("R4C2:R27C2").values(prognosisValues).format("0.000").textAlign("center").fontSize(fontSize);
	currRow+=24;
	
	addTotalRow('Total',2,'SUM','0.000');
	sheet.range("R28C4:R28C"+(3+iSize)).values([subtot]).format("0.000").fontSize(fontSize).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");;	
	
	allDataRange = 'R4C2:R27C'+(3+iSize);
	historyRange = 'R4C2:R27C2';
	
	
	//pod sheets
	response.pods.forEach((pod) => {
		let pSheet = spreadsheet.insertSheet({name:pod});
		
		if(pSheet === undefined)
			pSheet = spreadsheet.sheetByName(pod);
		
		currRow = 1;
				
		setExcelTitle(numDays+1,'Analitic Estimare ' + response.customer_name + ' '+ pod + ' ' + response[pod].county, pSheet).textAlign("left");		
		
		let subtitle1 =['','Tip Estimare','Prognoza'];
		let subtitle2 =['Interval','',''];
		
		let iSize = response[pod]['history_interval'].length;
		
		var subtot=[];
		response[pod]['history_interval'].forEach((element) => {
			let t = kendo.parseDate(element.date, "yyyy-MM-dd");
			subtitle1.push(t.toLocaleDateString("ro-ro", { weekday: 'narrow' }));
			subtitle2.push(t.toLocaleDateString("ro-ro", { year:'2-digit',month:"2-digit", day: 'numeric' }));
			let fea = parseFloat(element.ea);
			subtot.push(fea);
		});
		
		let lastColumn = 2+iSize;
	
		fontSize = normalFontSize; rowHeight = normalRowHeight;
		setExcelRow(subtitle1,pSheet).background("rgb(167,214,255)").color("black").bold(true).textAlign("center").fontSize(fontSize);
		setExcelRow(subtitle2,pSheet).background("rgb(167,214,255)").color("black").bold(true).textAlign("center").fontSize(fontSize);
		pSheet.columnWidth(1,120);	
		pSheet.rowHeight(1,normalRowHeight);
		pSheet.rowHeight(2,normalRowHeight);
	
		
		let vHeader = [];
		let typeValues = [];
		let prognosisValues = [];
		let colorRanges=[];
		
		let i=1;
		
		let vmin = 1000;
		let vmax = -1000;
		response[pod]['estimates'].forEach((ce)=>{
			let fea = parseFloat(ce.forecast_ea);
			if(vmin>fea) vmin = fea;
			if(vmax<fea) vmax = fea;
		});
		
		let colorCoef = maxColors / ((vmax-vmin) == 0 ? 0.000001 : (vmax-vmin));
		
		let idx=0;
		var podHvs = [];
		for(let h=0;h<24;h++) 
		{
			pSheet.rowHeight(3+h,normalRowHeight);
			vHeader.push([h+1]);
			
			//current estimates
			let ce = response[pod]['estimates'].find(function(dt, index) {
						let t = kendo.parseDate(dt.forecast_datetime, "yyyy-MM-dd HH:mm:ss");
						
						if(t.getHours() == h)
							return true;
					});
					
			let fea = '';
			let tv = '';
			let s = '';
						
			if(ce !== undefined && ce.forecast_ea!= null)
			{
				fea = parseFloat(ce.forecast_ea);
				tv = ce.estimation_type;
								
				let color = computeColor(fea, vmin, vmax, colorCoef);
				if(colorRanges[color] !== undefined)
					s = colorRanges[color];
				
				colorRanges[color] = s.concat("R"+(h+4)+"C3,");
			}
			else
			{
				if(colorRanges['#ffffff'] !== undefined)
					s = colorRanges['#ffffff'];
				
				colorRanges['#ffffff'] = s.concat("R"+(h+4)+"C3,");
			}
		
			typeValues.push([tv]);
			prognosisValues.push([fea]);
			
			var ed = [];
			for(let d=0;d<iSize;d++)
			{
				let hday = response[pod]['history_interval'][d].date+" "+zeroPad(h,2);
				let el = response[pod]['history'].find(function(dt, index) {
						let t = dt.far_datetime.slice(0,-6);
						
						if(t == hday)
							return true;
					});
				
				if(el !== undefined)
				{
					let fea = '';
					if(el.far_ea != null)
					{
						fea = parseFloat(el.far_ea);

						let color = computeColor(fea, vmin, vmax, colorCoef);
						if(colorRanges[color] !== undefined)
							s = colorRanges[color];
						
						colorRanges[color] = s.concat("R"+(h+4)+"C"+(4+d)+",");
					}
					
					ed.push(fea);
				}
				else
					ed.push('');
			}
			
			podHvs.push(ed);
			
		}
			
		pSheet.range("A4:A27").values(vHeader).bold(true).textAlign("right").fontSize(fontSize);
		pSheet.range("R4C2:R27C2").values(typeValues).textAlign("center").fontSize(fontSize);
		pSheet.range("R4C3:R27C3").values(prognosisValues).format("0.000").textAlign("center").fontSize(fontSize);
		pSheet.range("R4C4:R27C"+(3+iSize)).values(podHvs).format("0.000").textAlign("center").fontSize(fontSize);
		pSheet.range("R28C3").formula("=SUM(R4C3:R27C3").background("rgb(167,214,255)").color("black").bold(true).textAlign("center").format("0.000").fontSize(fontSize);

		//addTotalRow('Total',2,'SUM','0.000');
		pSheet.range("R28C4:R28C"+(3+iSize)).values([subtot]).format("0.000").fontSize(fontSize).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");;	
	
		setRangeBackground(colorRanges,pSheet);
	
	
		//estimatesResponse.split("<br>").forEach((e) => {respSheet.range("R"+i+"C1").value(e);i++;});
	});
	
	return;
	//calc excel data
}

function uploadEstimates()
{
	if($("#mCustomers").data("kendoMultiSelect").value().length != 1 && viewType != 'Sumar')
	{
		kendo.alert("<b>&#9888;Selectati doar un singur client!</b>");
		return;
	}
	
	kendo.ui.progress($(document.body), true);
	
	let customer_id = null;
	let customer_names = [];
	let	modified_data = 
		{
			day_interval:[],
			customers_name:[],
			values:[],
			length:0
		};
		
	let maxDays = new Date(selYear_tp, selMonth_tp+1, 0).getDate();
	
	switch(maxDays) {
	  case 31:
		rangeMax = "AF27";
		break;
	  case 30:
		rangeMax = "AE27";
		break;
	  case 29:
		rangeMax = "AD27";
		break;
	  case 28:
		rangeMax = "AC27";
		break;
	}	
	
	let values = [];
	let partial = false;
	
	let fdm = new Date(selYear_tp, selMonth_tp, 1);
	let ldm = new Date(selYear_tp, selMonth_tp+1, 0);
	let cdt = new Date();
	
	let mStart = new Date();
	let mEnd = new Date();
	
	if(viewType == 'Lunar') {
		
		if(ldm <= cdt && !testMode)
		{
			kendo.alert("<div><h3 class='d-inline'>&#x26A0;</h3> <b>Estimarile se pot face doar in viitor</b>!</div>");
			kendo.ui.progress($(document.body), false);
			return;
		}
		
		if(fdm > cdt) {mStart = fdm; mEnd = ldm;}
		else {mStart.setDate(cdt.getDate() + 1); mEnd = ldm;}
		
		partial = true;	
		values = sheet.range("R4C"+(mStart.getDate()+1)+":R27C"+(mEnd.getDate()+1)).values(); customer_id = $("#mCustomers").data("kendoMultiSelect").value()[0];
		
		}
	else if (viewType == 'Analitic') 
	{
		if(prevDateRange.start <= cdt && !testMode)
		{
			kendo.alert("<div><h3 class='d-inline'>&#x26A0;</h3> <b>Estimarile se pot face doar in viitor</b>!</div>");
			kendo.ui.progress($(document.body), false);
			return;
		}
		values = sheet.range(historyRange).values(); partial = true; customer_id = $("#mCustomers").data("kendoMultiSelect").value()[0];
	}
	else 
	{
		if(prevDateRange.start <= cdt && !testMode)
		{
			kendo.alert("<div><h3 class='d-inline'>&#x26A0;</h3> <b>Estimarile se pot face doar in viitor</b>!</div>");
			kendo.ui.progress($(document.body), false);
			return;
		}
		values = sheet.range("R5C2:R28C"+((maxCols-1)*intervalSize+1)).values();
		//transpose
		values = values[0].map((_, colIndex) => values.map(row => row[colIndex]));
		
		if(values.toString() == originalSummaryvalues.toString()) {$('#staticNotification').data('kendoNotification').show('Nu au fost valori modificate!', 'info'); kendo.ui.progress($(document.body), false); return;}
	
		let customersNo = maxCols - 1;
		customer_names = sheet.range("R2C2:R2C"+(customersNo*intervalSize+1)).values()[0];
		
		for(i=0;i<customersNo*intervalSize;i++)
			if(customer_names[i]=='')customer_names[i] = customer_names[i-1];
	
		i=0;
		for(c=0;c<customersNo;c++)
		{
			for(day = 0;day<intervalSize;day++)
				for(h=0;h<24;h++)
					if(originalSummaryvalues[c*intervalSize+day][h] != values[c*intervalSize+day][h])
					{
						modified_data.day_interval.push(day);
						modified_data.values.push(values[c*intervalSize+day]);
						modified_data.customers_name.push(customer_names[c*intervalSize+day]);
						modified_data.length++;
						break;
					}	
		}	
	
	}
			
	var data = {
			models: [
					 {
						customer_id : customer_id,
						YMdate : selYear_tp + "-"+(selMonth_tp+1),
						values:values,
						modified_data:modified_data,
						start: viewType == 'Lunar' ? kendo.toString( mStart , "yyyy-MM-dd") :kendo.toString(prevDateRange.start, "yyyy-MM-dd"),
						end: viewType == 'Lunar' ? kendo.toString( mEnd, "yyyy-MM-dd") : kendo.toString(prevDateRange.end, "yyyy-MM-dd"),
						partial:partial
					 }
					]
			};
	
	customCall(data,'uploadEstimates');	
	
	originalSummaryvalues = sheet.range("R5C2:R28C"+((maxCols-1)*intervalSize+1)).values();
	//transpose
	originalSummaryvalues = originalSummaryvalues[0].map((_, colIndex) => originalSummaryvalues.map(row => row[colIndex]));
	
	/*
	var jqxhr = $.post({
					url: window.location.origin+'/api?subject=custom&type=call&action=uploadEstimates',
					data: JSON.stringify(data),
					contentType: 'application/json; charset=utf-8'})
			.done(function(response) {
				if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
				
					kendo.ui.progress($(document.body), false);
					$('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');
					return false;
				}
				else
				{
					kendo.ui.progress($(document.body), false);
					$('#staticNotification').data('kendoNotification').show(response, 'info');
				}
			})
			.fail(function() {
				
				kendo.ui.progress($(document.body), false);
				
				//probleme de retea
				$('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
				return false;
			});
	*/
	
}

function estimate()
{
	if(viewType == 'Analitic' && prevCustomers.length == 0)
	{
		kendo.alert("<b>Selectati un client!</b>"); 
		return
	}
		
	let customerType = '';
	if ($("#consumption_types").data("kendoButtonGroup").current().index() != -1)
		customerType = $("#consumption_types").data("kendoButtonGroup").current().text().trim();
				
	let cdt = new Date();

	if(prevDateRange.start <= cdt && !testMode)
	{
		kendo.alert("<div><h3 class='d-inline'>&#x26A0;</h3> <b>Estimarile se pot face doar in viitor</b>!</div>");
		return;
	}
	
	kendo.ui.progress($(document.body), true);
	
	var data = {
		models: [
				 {
					customers:prevCustomers,
					start: kendo.toString(prevDateRange.start, "yyyy-MM-dd"),
					end: kendo.toString(prevDateRange.end, "yyyy-MM-dd"),
					customerType:selected_consumption_types//customerType
				 }
				]
		};
	
	customCall(data,'estimate',queryEstimatesReport, true, false);
	/*
	var existCondition = setInterval(function() {
	 if (spreadsheet.sheets().length == 1) {
		clearInterval(existCondition);
		
	 }
	}, 100); // check every 100ms
*/
	

	
}