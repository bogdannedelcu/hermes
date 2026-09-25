var spreadsheet = null;
var sheet = null;
var prevCustomers = [];
var currRow = 1;
var maxCols = -1;
var colOffset = 0;
var historyRange = "";
const maxColors = 255;

var originalSummaryvalues = [];
var intervalSize;

var monthsOffset={};
var dataArray=[];
var temperaturesArray=[];
var temperatureLegend=[];
var requestedTemperatures = 0;
var counterReceivedTemperatures = 0;

var vmin = 10000000;
var vmax = -10000000;
var colorCoef = 1;

const getMethods = (obj) => {
  let properties = new Set()
  let currentObj = obj
  do {
    Object.getOwnPropertyNames(currentObj).map(item => properties.add(item))
  } while ((currentObj = Object.getPrototypeOf(currentObj)))
  return [...properties.keys()].filter(item => typeof obj[item] === 'function')
}

var prevDestCustomerID = -1;
var prevDestPOD = -1;
	
$( document ).ready(function() {
	
	let cell = {"format":"#","background":"#ff0000","value":1};
	let cells = Array(400).fill(cell);// [cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell];
	let row = {cells};
	let rows = [row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row];
	spreadsheet = $("#spreadsheet").kendoSpreadsheet({
			rows:50,
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
	sheet.frozenColumns(1);
	
	if(Cookies.get('showCard-' + document.title) != 'true')
	{
		$(".k-spreadsheet-quick-access-toolbar").toggleClass("d-none"); $(".k-spreadsheet-tabstrip").toggleClass("d-none");	
		$("#collapseCard > div > div:nth-child(4)").toggleClass("d-none");
	}

	$("#tempResolution").kendoNumericTextBox({
	   format: "n",
	   value:1,
	   step:0.5,
	   min:0,
	   decimals:1,
	   change: function() {
		updateTemperatureScale();
	   },
	   spin: function() {
		updateTemperatureScale();
	   }
	});
	
	resizeExcel();
	queryEstimatesReport();	
	showDayIndexFilter();
});

function setExcelTitle(numDays,title, offset = 0)
{
	var range = sheet.range("R"+currRow+"C"+(offset+1)+":R"+currRow+"C"+(numDays+offset)).merge().value(title.toUpperCase()).fontSize(16).textAlign("center").bold(true);
	currRow++;
	
	return range;
}

function setExcelRow(arr,offset = 0)
{
	var range = sheet.range("R"+currRow+"C"+(offset+1)+":R"+currRow+"C"+(arr.length+offset)).values([arr]);
	currRow++;
	
	return range;
}

function addTotalRow(title,numDays,formula,format,startDataRow = 4)
{
	sheet.range("R"+currRow+"C1").value(title).background("rgb(167,214,255)");
	
	for(let i=1;i<=numDays;i++)
		sheet.range('R'+currRow+'C'+(i+1)).formula('='+formula+'(R'+startDataRow+'C'+(i+1)+':R'+(currRow-1)+'C'+(i+1)+')');

	sheet.range("R"+currRow+"C1:R"+currRow+"C"+(numDays+1)).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
	sheet.range("R"+currRow+"C2:R"+currRow+"C"+(numDays+1)).format(format);
	
	currRow++;
}

function setRangeBackground(colorRanges) {

	Object.keys(colorRanges).forEach(key => {
	  sheet.range(colorRanges[key].slice(0, -1)).background(key);
	});	
}

function computeColor(value)
{
	if(isNaN(value)) return '#ffffff';
	if(value<vmin) value = vmin;
	if(value>vmax) value = vmax;
	
	let v= 255-Math.floor(255/maxColors) * Math.floor(colorCoef * (value-vmin));
	let h='0';
	
	if(v<16) h = h+ v.toString(16);
	else h = v.toString(16);
	//console.log('#' + h + h);
	return '#ff' + h + h ;
}

function computeTColor(value)
{
	let legend = temperatureLegend;
	
	if(value == null || isNaN(value)) return "#FFFFFF";

	if(value<-50) value = -50;
	if(value>50) value = 50;
	
	value += 50;
	if((value*legend.tCoef)>=legend.tColorsSize)
		return legend.tColors[legend.tColorsSize-1];
	
	if(value*legend.tCoef<0)
		return legend.tColors[0];
	
	return legend.tColors[Math.round(value*legend.tCoef)];
}

/**********events********************/
function scaleChanged(e)
{
	if(e.sender.value()=='Temperatura')
	{
		temperaturesArray=[];
		let months = Object.keys(monthsOffset);
		requestedTemperatures = counterReceivedTemperatures = 0;
		for(let mdx=0;mdx<months.length;mdx++)
		{
			let data = {
			models: [
					 {
						month : months[mdx],
						year: selYear_tp
					 }
					]
			};
			requestedTemperatures++;
			customCall(data, 'getTemperatures', fillTemperatures, false, false);
		}
	}
	else
	{
		showEnergyScale();
	}
}

function mClose(e)
{
	//avoid multiple runs!
	if($("#mCustomers").data("kendoMultiSelect").value().join() != prevCustomers.join())
	{
		
		prevCustomers = $("#mCustomers").data("kendoMultiSelect").value();
		$("#selectPod").data("kendoDropDownList").value(null);
		setTimeout(function() {
			queryEstimatesReport();
		}, 0);
	}
	
	$("#selectPod").data("kendoDropDownList").dataSource.read();
}

function mDeselect()
{
	
	//avoid multiple runs
	$('#mCustomers').data("kendoMultiSelect").open();
}

function podChanged()
{
	queryEstimatesReport();
}

function consumption_typesChanged()
{
	if ($("#consumption_types").data("kendoButtonGroup").current().index() == -1)
		$("#mCustomers").data("kendoMultiSelect").dataSource.filter(null);
	else
	{
		$("#mCustomers").data("kendoMultiSelect").value(null);
		let filter = 
			{
				field:"consumption_type_name",
				operator:"eq",
				value:$("#consumption_types").data("kendoButtonGroup").current().text().trim()
			};
		$("#mCustomers").data("kendoMultiSelect").dataSource.filter(filter);
	}
	
	queryEstimatesReport();
}

function tpChanged()
{
	if(selMonth_tp == -1 || selYear_tp == -1) return;
	
	$("#mCustomers").data("kendoMultiSelect").dataSource.read();
	queryEstimatesReport();
}

function queryEstimatesReport()
{
	
	kendo.ui.progress($(document.body), true);
	var data = {
			models: [
					 {
						consumption_types: selected_consumption_types,
						customers :$("#mCustomers").data("kendoMultiSelect").value(),
						pod: $("#selectPod").data("kendoDropDownList").value(),
						month : $('#select_month_tp').data('kendoButtonGroup').selectedIndices.sort(function(a, b) {return a - b;}).map(a => a+1),
						year: selYear_tp
					 }
					]
			};
			
		customCall(data, 'getFarData', farDataReceived, false, false);

		sheet.select("A1");
		 $(".k-spreadsheet-scroller").scrollTop(0).scrollLeft(0);
		
		sheet.batch(function(){
			sheet.range("R1C1:R30C370").clear().values('');
			sheet.range(kendo.spreadsheet.SHEETREF).clear();
			sheet.rowHeight(1, 20);
			updateColumnsView(true);
		});
	
}

function fillTemperatures(data)
{
	counterReceivedTemperatures++;
	
	let temperatures = data.temperatures;
	let month = data.month;
	let monthOffset = monthsOffset[month];
	
	let numDays = calcNumDays(selYear_tp,month-1);
	
	if(temperatures.length != numDays*24)
		$('#staticNotification').data('kendoNotification').show('Lista de temperaturi este incompleta in '+selYear_tp+'-'+month, 'error');
	
	let rIdx = 0;
	for(let h=0;h<24;h++)
	{
		if(temperaturesArray[h] === undefined)
			temperaturesArray.push([]);
		
		for(let d=1;d<=numDays;d++)
		{
			if(temperatures[rIdx] === undefined) {temperaturesArray[h].push('');continue;}
			
			let td = parseInt(temperatures[rIdx].temperature_datetime.substring(8,10));
			let th = parseInt(temperatures[rIdx].temperature_datetime.substring(11,13));
			
			if(temperatures[rIdx] !== undefined && th == h && td == d)
			{
				let temp = parseFloat(temperatures[rIdx].temperature);
				temperaturesArray[h].push(temp);
				rIdx++
			}
			else
				temperaturesArray[h].push('');
		}
	}
	
	if(counterReceivedTemperatures == requestedTemperatures)
	{
		temperatureLegend = data.legend;
		showTemperatureScale();
	}
}

function showTemperatureScale()
{
	
	let rangeMax = "R27C"+colOffset;
	
	var uniqTemp = new Set();
	//constru colorRanges to avoid forEachCell background set due to slow
	let colorRanges=[];
	for(let h=0;h<24;h++)
	{
		for(let d=1;d<=colOffset;d++)
		{
			let s = '';
			let color = "#FFFFFF";
			
			if(temperaturesArray[h] !== undefined && temperaturesArray[h][d-1] !== undefined)
			{
				let tea = parseFloat(temperaturesArray[h][d-1]);
							
				if(isNaN(tea))
					color = "#FFFFFF";
				else
				{
					uniqTemp.add(Math.round(tea));
					color = computeTColor(tea);
				}
					
				if(colorRanges[color] !== undefined)
						s = colorRanges[color];
					
				colorRanges[color] = s.concat("R"+(h+4)+"C"+(d+1)+",");
			}
		}
	}
	
	sheet.batch(function() {
		setRangeBackground(colorRanges);	
	});	
	
	showTemperatureFilter(uniqTemp);
}

function updateColumnsView(all = false)
{
	if(all)
	{
		for(let d=1;d<=colOffset;d++)
			sheet.unhideColumn(d);
		return;
	}
		
	let index = $("#dayIndexButtonGroup").data("kendoButtonGroup").current().index();
	let selDay = $("#dayIndexButtonGroup").data("kendoButtonGroup").current().text().trim();

	for(let d=1;d<=colOffset;d++)
	{
		if(index == 0)
			sheet.unhideColumn(d);
		else
		{
			let tday = sheet.range("R2C"+(d+1)).value();
			if(index == 3)
			{
				if( tday == 'M'  || tday == 'J')
					sheet.unhideColumn(d);
				else
					sheet.hideColumn(d);
			}
			else
			{
				if(tday != selDay) 
					sheet.hideColumn(d);
				else 
					sheet.unhideColumn(d);
			}
		}
	}
}

function showDayIndexFilter()
{
	if($("#dayIndexButtonGroup").data("kendoButtonGroup") !== undefined)
	{
		$("#dayIndexButtonGroup").data("kendoButtonGroup").destroy(); //detach events
		$("#dayIndexButtonGroup").empty(); //remove the button group from the DOM
	}
	
	var btnSpan2 = document.createElement('span'); btnSpan2.innerHTML = "&#9733;"; document.getElementById("dayIndexButtonGroup").appendChild(btnSpan2);
	['D','L','X','V','S'].forEach((element) => {var btnSpan2 = document.createElement('span'); btnSpan2.innerHTML = element; document.getElementById("dayIndexButtonGroup").appendChild(btnSpan2);});
		
	/*sheet.batch(function() {
		updateColumnsView(true);		
	});*/	
	
	$("#dayIndexButtonGroup").kendoButtonGroup({
		index: 0,
		select: function(e) {
			
			sheet.batch(function() {
				updateColumnsView();		
			});	
			
        },
		selection: "single"
	});
}

function updateTemperatureScale()
{
	var index = $("#tempButtonGroup").data("kendoButtonGroup").current().index();

	let selTea = parseInt($("#tempButtonGroup").data("kendoButtonGroup").current().text());
	
	let resolution = $("#tempResolution").data("kendoNumericTextBox").value();
	//constru colorRanges to avoid forEachCell background set due to slow
	let colorRanges=[];
	for(let h=0;h<24;h++)
	{
		for(let d=1;d<=(colOffset-1);d++)
		{
			let s = '';
			
			let tea = parseFloat(temperaturesArray[h][d-1]);
			let color = "#FFFFFF";
			if(isNaN(selTea) || Math.abs(selTea-tea) <= resolution)
			{
				color = computeTColor(tea);
				if(colorRanges[color] !== undefined)
					s = colorRanges[color];				
			}
			else if(colorRanges[color] !== undefined)
					s = colorRanges[color];	
			
			colorRanges[color] = s.concat("R"+(h+4)+"C"+(d+1)+",");
			
		}
	}
	
	sheet.batch(function() {
		setRangeBackground(colorRanges);		
	});
}

function showTemperatureFilter(uniqTemp)
{
	if(uniqTemp.size<2) return ;
	
	if($("#tempButtonGroup").data("kendoButtonGroup") !== undefined)
	{
		$("#tempButtonGroup").data("kendoButtonGroup").destroy(); //detach events
		$("#tempButtonGroup").empty(); //remove the button group from the DOM
	}
	
	$("#tempResolutionWrapper").removeClass("d-none");
	$("#tempButtonGroup").removeClass("d-none");
	
	var btnSpan = document.createElement('span'); btnSpan.innerHTML = "&#9733;"; document.getElementById("tempButtonGroup").appendChild(btnSpan);
	[...uniqTemp].sort(function(a, b) {return a - b;}).forEach((element) => {var btnSpan = document.createElement('span'); btnSpan.style.backgroundColor=computeTColor(element); btnSpan.innerHTML = element; document.getElementById("tempButtonGroup").appendChild(btnSpan);});
	
	$("#tempButtonGroup").kendoButtonGroup({
		index: 0,
		select: function(e) {
            updateTemperatureScale();	
        },
		selection: "single"
	});
}

function showEnergyScale()
{
	$("#tempResolutionWrapper").addClass("d-none");
	$("#tempButtonGroup").addClass("d-none");
	
	let rangeMax = "R27C"+colOffset;
	
	//constru colorRanges to avoid forEachCell background set due to slow
	let colorRanges=[];
	for(let h=0;h<24;h++)
	{
		for(let d=1;d<=colOffset;d++)
		{
			let s = '';
			if(dataArray[h][d]!= '')
			{	 
				let color = computeColor(dataArray[h][d]);
				if(colorRanges[color] !== undefined)
					s = colorRanges[color];
				
				
				colorRanges[color] = s.concat("R"+(h+4)+"C"+(d+1)+",");
			}
			else
			{
				if(colorRanges['#ffffff'] !== undefined)
					s = colorRanges['#ffffff'];
				
				colorRanges['#ffffff'] = s.concat("R"+(h+4)+"C"+(d+1)+",");
			}
		}
	}
	
	sheet.batch(function() {
		setRangeBackground(colorRanges);		
	});	
}

function farDataReceived(data)
{
	fillExcel(data);		
	prevCustomers = $("#mCustomers").data("kendoMultiSelect").value();
}

function calcNumDays(year, month)
{
	let now = new Date();
		
	if(month != -1 && year != -1)
		now = new Date(year,month);
	else
		now.setDate(0);

	return daysInMonth(now);
}

function fillExcel(vdata)
{	
	let months = Object.keys(vdata);
	colOffset = 0;
	monthsOffset = {};
	dataArray=[];
	temperaturesArray=[];
	requestedTemperatures = 0;
	counterReceivedTemperatures = 0;

	let temperatureScale = ($("#scale").data("kendoDropDownList").value()=='Temperatura');
		
	for(let mdx=0;mdx<months.length;mdx++)
	{
		let selMonth = parseInt(months[mdx])-1;
		let now = new Date();
		
		if(selYear_tp != -1 &&  selMonth != -1)
			now = new Date(selYear_tp,selMonth);
		else
			now.setDate(0);

		let numDays = calcNumDays(selYear_tp,selMonth); 

		//sheet = spreadsheet.insertSheet({name:now.toLocaleString('ro-ro',{month:'short', year:'numeric'})});
		//spreadsheet.removeSheet(spreadsheet.sheetByIndex(0));	
			
		currRow = 1;
		
		if(sheet === undefined) return;
		
		//set column width
		let subtitle1 =[];
		let subtitle2 =[];
		sheet.columnWidth(0,55);
		
		if(colOffset == 0)
		{
			subtitle1.push('');
			subtitle2.push('Interval');	
			monthsOffset[selMonth+1] = 1;
		}
		else
			monthsOffset[selMonth+1] = colOffset;
		
		for(let i=1;i<=numDays;i++)
		{

			let d = new Date(now.getTime());
			d.setDate(i); 

			subtitle1.push(d.toLocaleDateString("ro-ro", { weekday: 'narrow' }));
			subtitle2.push(i);

			sheet.columnWidth(i+colOffset,45);
		}

		//set title
		setExcelTitle((colOffset == 0 ? 1 : 0)+numDays,'PROFIL '+now.toLocaleString('ro-ro',{month:'short', year:'numeric'}), colOffset);
		
		//set subtitle
		setExcelRow(subtitle1,colOffset).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
		setExcelRow(subtitle2,colOffset).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
		
		fillData(vdata[months[mdx]],numDays,colOffset);

		// mark free days
		var data = {
			models: [
					 {
						year : selYear_tp,
						month: selMonth+1,
						customers: $("#mCustomers").data("kendoMultiSelect").value().join()
					 }
					]
			};
		
		customCall(data,'getFreeDays',setFreeDays, true, false);
		customCall(data,'getSunData',setSun, true, false);
		if(temperatureScale)
		{
			requestedTemperatures++;
			customCall(data, 'getTemperatures', fillTemperatures, true, false);
		}
		
		if(colOffset == 0) colOffset++;
		colOffset += numDays;
	}
	
	let rangeMax = "R27C"+(colOffset);
	sheet.range("R4C1:"+rangeMax).values(dataArray);
	
	if(!temperatureScale) showEnergyScale();
	
	sheet.batch(function (){
		addTotalRow('Total',colOffset-1,'SUM','0.000');
		sheet.range("R29C1").formula("=SUM(R4C2:R27C"+colOffset+")").bold(true).format('#.000');
		sheet.range("A4:A27").bold(true).textAlign("right");
		sheet.range("R4C2:"+"R27C"+colOffset).format("0.000").textAlign("center");
		updateColumnsView();
	});
}

function setFreeDays(response)
{
	let month = parseInt(response.month);
	let offset = monthsOffset[month];
	
	let r = [];
	for(let i = 0;i<response.freeDays.length;i++)
	{
		r.push("R2C"+ (response.freeDays[i]+offset),"R3C"+ (response.freeDays[i]+offset),"R28C"+ (response.freeDays[i]+offset));
	}
	
	sheet.batch(function() {
		sheet.range(r.join(",")).background("rgb(184,235,255)");
	});		
}

function setSun(response)
{
	let month = parseInt(response.month);
	let offset = monthsOffset[month];
	
	let ss = [];
	let sr = [];
	
	for(let i = 0;i<response.sunData.length;i++)
	{
		sr.push("R"+(parseInt(response.sunData[i].sunrise)+4)+"C"+ (response.sunData[i].day+offset));
		ss.push("R"+(parseInt(response.sunData[i].sunset)+4)+"C"+ (response.sunData[i].day+offset));
	}
	
	sheet.batch(function() {
		sheet.range(sr.join(",")).borderBottom({ size: 2, color: "black" });
		sheet.range(ss.join(",")).borderTop({ size: 2, color: "black" });
	});	
}

function fillData(response, numDays, offset=0)
{
	let rIdx=0;
	vmin = 10000000;
	vmax = -10000000;
	
	for(let h=0;h<24;h++)
	{
		if(offset == 0)
			dataArray.push([parseInt(h)+1]);
		for(let d=1;d<=numDays;d++)
		{
			if(response[rIdx] !== undefined && response[rIdx].hour == h && response[rIdx].day == d)
			{
				let fea = '';
				if(response[rIdx].far_ea != null)
				{
					fea = parseFloat(response[rIdx].far_ea);
					if(vmin>fea) vmin = fea;
					if(vmax<fea) vmax = fea;
				}
				dataArray[h].push(fea);
				rIdx++
			}
			else
				dataArray[h].push('');
		}
	}
	currRow+=24;
	colorCoef = maxColors / ((vmax-vmin) == 0 ? 0.000001 : (vmax-vmin));
}

function uploadProfile()
{
	if(prevCustomers.length != 1)
	{
		$('#staticNotification').data('kendoNotification').show("Selectati un singur client.", 'error');
		return;
	}
		
	kendo.ui.progress($(document.body), true);

	let sheet = $("#spreadsheet").data("kendoSpreadsheet").activeSheet();
	let maxDays = new Date(selYear_tp, selMonth_tp+1, 0).getDate();
	let rangeMax = "AF27";
	
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
	
	var data = {
			models: [
					 {
						YMdate : selYear_tp + "-"+(selMonth_tp+1),
						values:sheet.range("B4:"+rangeMax).values(),
						customerID: prevCustomers[0],
						pod:$("#selectPod").data("kendoDropDownList").value()
					 }
					]
			};
			
	var jqxhr = $.post({
					url: window.location.origin+'/api?subject=custom&type=call&action=uploadProfile',
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
	
}

function synthGeneratorDialog()
{
	$("body").append('<div id="dialog">');
		
	dialog = $('#dialog');

	dialog.kendoDialog({
		width: "450px",
		title: "Generează curbă sintetică",
		closable: true,
		modal: true,
		close: function(e){e.sender.destroy();},
		content: "<form id='curveSettingsForm'></form>",
		actions: [
			{ text: 'Anulare' },
			{
			  text: "Generează",
			  action: function(e){
				  // e.sender is a reference to the dialog widget object
				  // OK action was clicked
				  
				  		if($("#source_pod").data("kendoDropDownList").value() == '') 
						{ 
							$('#staticNotification').data('kendoNotification').show("Selectati POD sursa...", 'error');
							return false;
						}
						
						if($("#dest_pod").data("kendoDropDownList").value() == '') 
						{ 
							$('#staticNotification').data('kendoNotification').show("Selectati POD destinatie...", 'error');
							return false;
						}
			
						if($("#toBeValue").data("kendoNumericTextBox").value() <= 0) 
						{ 
							$('#staticNotification').data('kendoNotification').show("Introduceti o valoare mai mare ca 0...", 'error');
							return false;
						}
						
						prevDestCustomerID = $("#dest_client").data("kendoDropDownList").value();
						prevDestPOD = $("#dest_pod").data("kendoDropDownList").value();
						
						var data = {
						models: [
								 {
									source_customer: $("#source_client").data("kendoDropDownList").value(),
									source_pod: $("#source_pod").data("kendoDropDownList").value(),
									dest_customer: $("#dest_client").data("kendoDropDownList").value(),
									dest_pod: $("#dest_pod").data("kendoDropDownList").value(),
									start_year: $("#start_date").data("kendoDatePicker").value().getFullYear(),
									start_month: $("#start_date").data("kendoDatePicker").value().getMonth()+1,
									stop_year: $("#stop_date").data("kendoDatePicker").value().getFullYear(),
									stop_month: $("#stop_date").data("kendoDatePicker").value().getMonth()+1,
									toBeValue:$("#toBeValue").data("kendoNumericTextBox").value(),
									valueType:$("#value_type").data("kendoSwitch").value() ? "EA" : "Percent",
								 }
								]
						};
					
						customCall(data,'generateCurveForPOD',true);
				  
				  // Returning false will prevent the closing of the dialog
				  if($("#keepOpen").data("kendoSwitch").value())  return false;
				  else return true;
			  },
			  primary: true
			}
		]
	});
	
	
	let startDate = kendo.parseDate(selYear_tp + "-" + (selMonth_tp + 1) + "-01", "yyyy-MM-dd");
	let stopDate = kendo.date.lastDayOfMonth(startDate);
		
	$("#curveSettingsForm").kendoForm({
		buttonsTemplate:"",
		orientation: "horizontal",
		formData: {
			start_date:startDate,
			stop_date:stopDate,
			value_type: true,
			keepOpen: false
		},
		layout: "grid",
		items: [
			{
				type: "group",
				label: "POD sursa",
				items: [
					{ 
						field: "source_client", 
						editor: "DropDownList", 
						label: "Client", 
						validation: { required: false },
						hint: "Selectati clientul...",						
						editorOptions: {
							optionLabel: "Select...",
							filter: "contains",
							height:400,
							dataSource: {
								schema: {
									type:"json",						 
									model: {
									  id: "customer_id"
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
										url: window.location.origin + "/api?subject=custom&type=call&action=getCustomersInTPWithConsumptionTypes",
										dataType: "json",
										type:"post",
										data: function() {
											
											let m = selMonth_tp+1;
											let y = selYear_tp;
											if($("#start_date").length > 0)
											{
												let d = $("#start_date").data("kendoDatePicker").value();
												m = d.getMonth() + 1;
												y = d.getFullYear();
											}
											return {
													models:[{month:m, year: y}]
											}                  
										}           
									},
									parameterMap: function (options, type) {
									  return kendo.stringify(options);
									}
								}, 
								sort: { field: "customer_name", dir: "asc" },
								pageSize: 10000
							},
							dataTextField: "customer_name",
							dataValueField: "customer_id",
							dataBound: function(e){
													if($("#mCustomers").data("kendoMultiSelect").value().length == 0)													
														e.sender.select(1);
													else
														e.sender.value($("#mCustomers").data("kendoMultiSelect").value()[0]);
													
													if(e.sender.dataSource.data().length == 0)
														$("#source_client-form-hint").text("Nu am gasit clienti, schimbati data start/stop");
													else
														$("#source_client-form-hint").text("");
													e.sender.trigger("change");
												}
						}
					},
					{ 
						field: "source_pod", 
						editor: "DropDownList", 
						label: "POD", 
						hint: "Judet ...",
						validation: { required: false }, 
						editorOptions: {
							optionLabel: "Select...",
							filter: "contains",
							height:400,
							cascadeFrom: "source_client",
							dataSource: {
								schema: {
									type:"json",						 
									model: {
									  id: "pod_no"
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
										url: window.location.origin + "/api?subject=pods&type=read&action=grid",
										dataType: "json",
										type:"post"         
									},
									parameterMap: function (options, type) {
									  return kendo.stringify(options);
									}
								}, 
								group: { field: "county" },
								sort: { field: "pod_no", dir: "asc" },
								pageSize: 10000
							},
							dataValueField: "pod_no",
							dataTextField: "pod_no",
							dataBound: function(e){
													if($("#selectPod").data("kendoDropDownList").value() == "") 
														e.sender.select(1);
													else 
														e.sender.value($("#selectPod").data("kendoDropDownList").value());
													
													e.sender.trigger("change");
													},
							change: function(e){
													$("#source_pod-form-hint").text("Judet "+ (e.sender.dataItem().county ?? "...")); 
													if(e.sender.dataItem().pod_no != "")
														updateToBeValue(e.sender.dataItem().pod_no, $("#start_date").data("kendoDatePicker").value());
												}
							
						}
					}
				]
			},
			{
				type: "group",
				label: "POD destintatie",
				items: [
					{ 
						field: "dest_client", 
						editor: "DropDownList", 
						label: "Client", 
						validation: { required: false }, 
						editorOptions: {
							optionLabel: "Select...",
							filter: "contains",
							height:400,
							dataSource: {
								schema: {
									type:"json",						 
									model: {
									  id: "customer_id"
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
										url: window.location.origin + "/api?subject=customers&type=read&action=grid",
										dataType: "json",
										type:"post",
										data: function() {
											return {
													"filter": {
														"logic": "and",
														"filters": [
															{
																"field": "supplier_id",
																"operator": "eq",
																"value": supplierID
															}
														]
													}
											}                  
										}           
									},
									parameterMap: function (options, type) {
									  return kendo.stringify(options);
									}
								}, 
								sort: { field: "customer_name", dir: "asc" },
								pageSize: 10000
							},
							dataTextField: "customer_name",
							dataValueField: "customer_id",
							dataBound: function(e){
									if( prevDestCustomerID != -1 )
									{
										e.sender.value(prevDestCustomerID);
										prevDestCustomerID = -1;
									}
									else
										e.sender.select(1);
									
									e.sender.trigger("change");
								}
						}
					},
					{ 
						field: "dest_pod", 
						editor: "DropDownList", 
						label: "POD", 
						hint: "Judet ...",
						validation: { required: false }, 
						editorOptions: {
							optionLabel: "Select...",
							filter: "contains",
							height:400,
							cascadeFrom: "dest_client",
							dataSource: {
								schema: {
									type:"json",						 
									model: {
									  id: "pod_no"
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
										url: window.location.origin + "/api?subject=pods&type=read&action=grid",
										dataType: "json",
										type:"post"         
									},
									parameterMap: function (options, type) {
									  return kendo.stringify(options);
									}
								}, 
								group: { field: "county" },
								sort: { field: "pod_no", dir: "asc" },
								pageSize: 10000
							},
							dataValueField: "pod_no",
							dataTextField: "pod_no",
							dataBound: function(e){
									if(prevDestPOD != -1)
									{
										e.sender.value(prevDestPOD);
										prevDestPOD = -1;
									}
									else
										e.sender.select(1);
									
									e.sender.trigger("change");
								},
							change: function(e){$("#dest_pod-form-hint").text("Judet " + (e.sender.dataItem().county ?? "..."));}
							
						}
					}
				]
			},
			{
				type: "group",
				label: "Parametrii",
				layout: "grid",
				grid: {
					cols: 2,
					gutter: 2
				},
				items: [
					{ 
						field: "start_date", 
						editor: "DatePicker", 
						label: "Start", 
						validation: { required: false }, 
						editorOptions: {
							depth: "year",
							start: "year",
							change:dateChanged,
							format: "MM-yyyy"
						},
						attributes: {
							//readonly:true
						}
					},
					{ 
						field: "stop_date", 
						editor: "DatePicker", 
						label: "Stop", 
						validation: { required: false }, 
						editorOptions: {
							depth: "year",
							start: "year",
							change:dateChanged,
							format: "MM-yyyy"
						},
						attributes: {
							//readonly:true
						}
					}
					,
					{ 
						field: "value_type", 
						editor: "Switch", 
						label: "Tip Valoare", 
						validation: { required: false }, 
						editorOptions: {
							messages: {
								checked: "EA",
								unchecked: "Procent"
							},
							width:90,
							change: value_typeChanged
							
						}
					}
					,
					{ 
						field: "toBeValue", 
						editor: "NumericTextBox", 
						label: "", 
						validation: { required: false }, 
						editorOptions: {
							format:"0.000",
							decimals:3
						}
					}
				]
			},
			{
				type: "group",
				label: "",
				layout: "grid",
				items: [
						{ 
							field: "keepOpen", 
							label: "Pastreaza fereastra deschisa", 
							editor: "Switch", 
							validation: { required: false },
						}
					]
			}
		]
	});
	
	$("#start_date").click(function() {
		$("#start_date").data("kendoDatePicker").open();
	});

	$("#stop_date").click(function() {
		$("#stop_date").data("kendoDatePicker").open();
	});
	
	$("#start_date").attr("readonly", true);
	$("#stop_date").attr("readonly", true);
	
	dialog.data("kendoDialog").center();

}

function dateChanged(e)
{
	if(this.element.context.id == 'start_date')
	{
		if(e.sender.value() > $("#stop_date").data("kendoDatePicker").value() || $("#value_type").data("kendoSwitch").value())
			$("#stop_date").data("kendoDatePicker").value(e.sender.value());
		
		$("#source_client").data("kendoDropDownList").dataSource.read();
	}
	if(this.element.context.id == 'stop_date')
	{
		if(e.sender.value() < $("#start_date").data("kendoDatePicker").value() || $("#value_type").data("kendoSwitch").value())
		{
			$("#start_date").data("kendoDatePicker").value(e.sender.value());
			$("#source_client").data("kendoDropDownList").dataSource.read();
		}
	}
	
	updateToBeValue($("#source_pod").data("kendoDropDownList").value(), $("#start_date").data("kendoDatePicker").value());
}

function updateToBeValue(pod, date)
{
	if(!$("#value_type").data("kendoSwitch").value()) return;
	
	var data = {
		models: [
				 {
					supplier_id: supplierID,
					pod: pod,
					year:date.getFullYear(),
					month:date.getMonth()+1,
				 }
				]
		};
	
	customCall(data,'getFarPODEA',toBeValueReceived);
}

function value_typeChanged(e)
{
	if(e.checked)
	{
		$("#toBeValue").data("kendoNumericTextBox").setOptions({format: "0.000", decimals: 3 });
		updateToBeValue($("#source_pod").data("kendoDropDownList").value(), $("#start_date").data("kendoDatePicker").value());
		$("fieldset:nth-child(3) .k-switch-track").css("background","#428bca").css("color","white");
		$("#stop_date").data("kendoDatePicker").value($("#start_date").data("kendoDatePicker").value());
	}
	else
	{
		$("#toBeValue").data("kendoNumericTextBox").setOptions({format: "0.00", decimals: 2 });
		$("#toBeValue").data("kendoNumericTextBox").value(100);
		$("fieldset:nth-child(3) .k-switch-track").css("background","#d9534f").css("color","white");
	}
}

function toBeValueReceived(response)
{
	$("#toBeValue").data("kendoNumericTextBox").value(response.ea);
}