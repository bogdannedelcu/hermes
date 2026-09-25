var spreadsheet = null;
var sheet = null;
var prevCustomers = [];
var currRow = 1;
var maxCols = -1;
var colOffset = 0;
var historyRange = "";
const maxColors = 255;

var dataArray=[];
var intervals = 96;

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

	$("#exportToForecast").kendoButton({
			icon: "k-icon k-i-check-outline",
			click: function(e) {
				
					dialog = $("<div></div>").kendoDialog({
					title: "Confirmare",
					content: "Esti sigur ca vrei sa transferi datele in Prognoza?",
					width: "400px",
					modal: true,
					actions: [
						{ text: "Nu", primary: false},
						{ text: "Da", 
							action: function() {
							var data = selectPodDSCallback(); // same body as pods filter
							customCall(data, 'saveDailyReadingsToForecast');}, 
							primary: true }
					],
					close: function(e) {
						// Handle when dialog is closed without clicking any button
						if (e.userTriggered && !e.sender._result) {
							
						}
					}
				}).data("kendoDialog");
			
				dialog.open();
			}
		});
			
	$("#saveSpreadsheet").kendoButton({
                icon: "k-icon k-i-save",
                click: function(e) {
                    $("button[title='Export...']").click();
                }
            });
			
	$("#showToolbar").kendoButton({
	icon: "k-icon k-i-wrench",
	click: function(e) {
		$(".k-spreadsheet-quick-access-toolbar").toggleClass("d-none");
		$(".k-spreadsheet-tabstrip").toggleClass("d-none");
		$("#collapseCard > div > div:nth-child(4)").toggleClass("d-none");
		resizeExcel();
		
		if ($(".k-spreadsheet-quick-access-toolbar").hasClass("d-none")) {
			Cookies.set("showCard-" + document.title, "false");
		} else {
			Cookies.set("showCard-" + document.title, "true");
		}
	}
	});
			
	/*
	$button0 = new \Kendo\UI\Button('uploadProfile');
	$button0->icon('k-icon k-i-upload');
	$button0->click('function(e) { uploadProfile(); }');*/
	
	inaFilterButtons("view", "view_type", "Orar", "Interval",["Orar","Minut"],refreshReport,"","pe-2");
	
	//create time period filter
	getMinMaxYears('customer_daily_readings_all','reading_datetime',createPeriodFilter);

	//create distributor filter
	inaDistributorFilter('customer_daily_readings_all',-1,false, refreshAll,'daily_readings_distributor');
	
	//create customer filter	
	$("#mCustomers").kendoMultiSelect({
		dataTextField: "customer_name",
		dataValueField: "customer_id",
		dataSource: inaDS({custom:true, subject:"getCustomersInTPWithDailyReadings", model:{id:"customer_id"}, transportCallback:mCustomersDSCallback }),
		placeholder: "Selecteaza clientii...",
		autoClose: true, 
		ignoreCase: true, 
		filter: "contains", 
		close: mClose,
		deselect: mDeselect
	});

	//create pod filter	
	$("#selectPod").kendoDropDownList({
		dataTextField: "pod",
		dataValueField: "pod",
		dataSource: inaDS({custom:true, subject:"getPODsOfCustomersWithDailyReadings", model:{id:"customer_id"},group: { field: "county" }, transportCallback:selectPodDSCallback }),
		optionLabel: "Selecteaza POD...",
		filter: "contains",
		change: podChanged,
		height: 500
	});
	
	let cell = {"format":"#","background":"#ff0000","value":1};
	let cells = Array(400).fill(cell);// [cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell];
	let row = {cells};
	let rows = [row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row];
	spreadsheet = $("#spreadsheet").kendoSpreadsheet({
			rows:1000,
			columns:40,
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

	resizeExcel();
	
	$(".minw-120px").addClass("minw-100px").removeClass("minw-120px");
});

function selectPodDSCallback() 
{
	return {models:[{distributorID:inaOptions['distributor_id'].selectedDistributor,month:inaOptions['reading_datetime']?.selectedMonth ?? new Date().getMonth() + 1, year: inaOptions['reading_datetime']?.selectedYear ?? new Date().getFullYear(),customerIDs:$('#mCustomers').data('kendoMultiSelect').value()}]}
}

function mCustomersDSCallback()
{
	return {models:[{distributorID:inaOptions['distributor_id'].selectedDistributor,month:inaOptions['reading_datetime']?.selectedMonth ?? new Date().getMonth() + 1, year: inaOptions['reading_datetime']?.selectedYear ?? new Date().getFullYear()}]};
}

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

/**********events********************/

function mClose(e)
{
	//avoid multiple runs!
	if($("#mCustomers").data("kendoMultiSelect").value().join() != prevCustomers.join())
	{
		
		prevCustomers = $("#mCustomers").data("kendoMultiSelect").value();
		$("#selectPod").data("kendoDropDownList").value(null);
		setTimeout(function() {
			refreshReport();
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
	refreshReport();
}

function tpChanged()
{
	if(inaOptions['reading_datetime'].selectedMonth == -1 || inaOptions['reading_datetime'].selectedYear == -1) return;
	
	$("#mCustomers").data("kendoMultiSelect").dataSource.read();
	refreshReport();
}

function refreshAll()
{
	if ($("#mCustomers").data("kendoMultiSelect") === undefined) return;
	
	$("#mCustomers").data("kendoMultiSelect").dataSource.read();
	$("#selectPod").data("kendoDropDownList").dataSource.read();
	
	refreshReport();
}

function refreshReport()
{
	if ($("#mCustomers").data("kendoMultiSelect") === undefined) return;

	intervals = inaOptions['view_type'].selectedValue == "Orar" ? 24 : 96;
	kendo.ui.progress($(document.body), true);
	var data = {
			models: [
					 {
						intervals: intervals,
						distributorID: inaOptions['distributor_id'].selectedDistributor,
						customers :$("#mCustomers").data("kendoMultiSelect").value(),
						pod: $("#selectPod").data("kendoDropDownList").value(),
						month : inaOptions['reading_datetime']?.selectedMonth ?? new Date().getMonth() + 1,
						year: inaOptions['reading_datetime']?.selectedYear ?? new Date().getFullYear()
					 }
					]
			};
			
		customCall(data, 'getDailyReadingsData', excelDataReceived, false, false);
		customCall(data, 'getDailyReadingsErrors', excelErrorsReceived, false, false);

		sheet.select("A1");
		 $(".k-spreadsheet-scroller").scrollTop(0).scrollLeft(0);
		
		sheet.batch(function(){
			sheet.range("R1C1:R30C370").clear().values('');
			sheet.range(kendo.spreadsheet.SHEETREF).clear();
			sheet.rowHeight(1, 20);
		});
	
}

function showEnergyScale()
{	
	let rangeMax = "R"+(intervals+3)+"C"+colOffset;
	
	//constru colorRanges to avoid forEachCell background set due to slow
	let colorRanges=[];
	for(let h=0;h<intervals;h++)
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

function excelDataReceived(data)
{
	fillExcel(data);		
	prevCustomers = $("#mCustomers").data("kendoMultiSelect").value();
}

// 3. Format as Kendo Grid would
function populateSheetWithKendoStyle(sheet, data) {
  // Apply header styling (like Kendo grid header)
  const headerRange = sheet.range("R1C1:R1C4");
  headerRange.background("#f5f5f5")
            .bold(true)
            .textAlign("center");

  // Prepare data rows
  const rows = data.map(item => [
    item.customer_name,
    item.pod,
    item.date,
    item.Intervals
  ]);

  // Add data to sheet
  if (rows.length > 0) {
    sheet.range("R2C1:R"+(rows.length+1)+"C4").values(rows);
  }

  // resize columns
	sheet.columnWidth(0,300);
	sheet.columnWidth(1,200);
	sheet.columnWidth(2,100);
	sheet.columnWidth(3,100);
	
	sheet.range("R1C1:R"+(rows.length+1)+"C4").filter(true);

}

function excelErrorsReceived(data)
{
	let respSheet = spreadsheet.sheetByName("DateLipsa");
	if(respSheet)
		spreadsheet.removeSheet(respSheet);
	
	respSheet = spreadsheet.insertSheet({name:"DateLipsa"});

	//respSheet.columnWidth(0,700);

	// 2. Prepare headers
	const headers = ["Client", "POD", "Data", "Nr. Intervale"];
	respSheet.range("R1C1:R1C4").values([headers]);
	respSheet.batch(function (){
	populateSheetWithKendoStyle(respSheet, data);});
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
	dataArray=[];

	let now = new Date();
	
	if(inaOptions['reading_datetime'].selectedYear != -1 &&  inaOptions['reading_datetime'].selectedMonth != -1)
		now = new Date(inaOptions['reading_datetime'].selectedYear,inaOptions['reading_datetime'].selectedMonth-1);
	else
		now.setDate(0);

	let numDays = calcNumDays(inaOptions['reading_datetime'].selectedYear,inaOptions['reading_datetime'].selectedMonth-1); 

	//sheet = spreadsheet.insertSheet({name:now.toLocaleString('ro-ro',{month:'short', year:'numeric'})});
	//spreadsheet.removeSheet(spreadsheet.sheetByIndex(0));	
		
	currRow = 1;
	
	if(sheet === undefined) return;
	
	//set column width
	let subtitle1 =[];
	let subtitle2 =[];
	sheet.columnWidth(0,55);
	

	subtitle1.push('');
	subtitle2.push('Interval');	

	
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
	
	fillData(vdata,numDays,colOffset);

	// mark free days
	var data = {
		models: [
				 {
					year : inaOptions['reading_datetime'].selectedYear,
					month: inaOptions['reading_datetime'].selectedMonth,
					customers: $("#mCustomers").data("kendoMultiSelect").value().join()
				 }
				]
		};
	
	customCall(data,'getFreeDays',setFreeDays, true, false);
	
	if(colOffset == 0) colOffset++;
	colOffset += numDays;

	let rangeMax = "R27C"+(colOffset);
	if(intervals > 24)
		rangeMax = "R99C"+(colOffset);
	
	sheet.range("R4C1:"+rangeMax).values(dataArray);
	
	showEnergyScale();
	
	sheet.batch(function (){
		addTotalRow('Total',colOffset-1,'SUM','0.000');
		sheet.range("R"+(intervals+5)+"C1").formula("=SUM(R4C2:R"+(intervals+3)+"C"+colOffset+")").bold(true).format('#.000');
		sheet.range("A4:A27").bold(true).textAlign("right");
		sheet.range("R4C2:"+`R${intervals+53}C`+colOffset).format("0.000").textAlign("center");
	});
}

function setFreeDays(response)
{
	let month = parseInt(response.month);
	let offset = 1;
	
	let r = [];
	for(let i = 0;i<response.freeDays.length;i++)
	{
		r.push("R2C"+ (response.freeDays[i]+offset),"R3C"+ (response.freeDays[i]+offset),"R"+(intervals+4)+"C"+ (response.freeDays[i]+offset));
	}
	
	sheet.batch(function() {
		sheet.range(r.join(",")).background("rgb(184,235,255)");
	});		
}

function fillData(response, numDays, offset=0)
{
	let rIdx=0;
	vmin = 10000000;
	vmax = -10000000;

	for (let i = 0; i < intervals; i++) {
		dataArray[i] = [i+1];
		for (let d = 1; d <= numDays; d++) {
			
			if(intervals > 24)
			{
				hour = Math.floor(i / 4);
				minute = (i % 4) * 15;
			}
			
			const r = response.find(obj => {
				const date = kendo.parseDate(obj.datetime, "yyyy-MM-dd HH:mm:ss");
				return (intervals == 24 && date.getDate() === (d) && date.getHours() === i) || 
				 (intervals>24 && date.getDate() === (d) && date.getHours() === hour && date.getMinutes() === minute);
			});
			
			if(r !== undefined)
			{
				let fea = '';
				if(r.ea != null)
				{
					fea = parseFloat(r.ea);
					if(vmin>fea) vmin = fea;
					if(vmax<fea) vmax = fea;
				}
				dataArray[i][d] = fea; // Or any other initial value
			}
		}
	}

	currRow+=intervals;
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
	let maxDays = new Date(inaOptions['reading_datetime'].selectedYear, inaOptions['reading_datetime'].selectedMonth+1, 0).getDate();
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
						YMdate : inaOptions['reading_datetime'].selectedYear + "-"+(inaOptions['reading_datetime'].selectedMonth+1),
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

function createPeriodFilter(response)
{
	const currentDate = new Date();
	const lastYearDate = new Date(currentDate); lastYearDate.setFullYear(currentDate.getFullYear());
	inaPeriodFilter("customer_daily_readings_all","reading_datetime","Perioada",lastYearDate,response.minYear,response.maxYear,refreshAll);
}
