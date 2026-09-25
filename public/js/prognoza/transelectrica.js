//corelare senGraph => RO 
var prevData = [];

$( document ).ready(function() {
	
	let cell = {"format":"#","background":"#ff0000","value":1};
	let cells = [cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell,cell];
	let row = {cells};
	let rows = [row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row,row];
	spreadsheet = $("#spreadsheet").kendoSpreadsheet({
			rows:50,
			columns:500,
          sheets: [{
            name: "Sheet 1",
            rows: []
          }],  
		  changing: function(e){/*
            if(e.data !='')
				e.range.background(computeColor(parseFloat(e.data)));
			else
				e.range.background("#ffffff");
			*/
		}
        }).getKendoSpreadsheet();
		
	sheet = spreadsheet.activeSheet(); 
	
	
	queryReport();
	
});

function uploadTranselectrica()
{
	kendo.ui.progress($(document.body), true);
	
	let judete = [];
	regions.forEach((element) => judete.push(element.indicator.code));
	
	console.log(judete);
	
	customCall({models: [{year:selYear_tp,month:selMonth_tp+1,judete:judete}]},'getTranselectricaData',queryReport);
}

function queryReport()
{
	kendo.ui.progress($(document.body), true);	
	customCall({models: [{year:selYear_tp,month:selMonth_tp+1,county_code:$("#counties").data("kendoButtonGroup").current().text().trim()}]},'viewTranselectricaData', dataReceived);
}

function countiesChanged()
{
	queryReport();
}


function dataReceived(response)
{
	sheet.batch(function() {
		renderData(response);	
	});
}
function renderData(response)
{
	sheet.range("R1C1:R30C35").clear().values('').fontSize(normalFontSize);
	//sheet.range(kendo.spreadsheet.SHEETREF).clear();
	sheet.rowHeight(1, 20);

	if (response.total == 0) return;
	
	currRow = 1;
	let now = new Date();
	
	if(selYear_tp != -1 && selMonth_tp != -1)
		now = new Date(selYear_tp,selMonth_tp);
	else
		now.setDate(0);

	numDays = daysInMonth(now);
	
	//set column width
	let subtitle1 =[''];
	let subtitle2 =['Interval'];
	sheet.columnWidth(0,60);
	
	for(let i=1;i<=numDays;i++)
	{

		let d = new Date(now.getTime());
		d.setDate(i); 

		subtitle1.push(d.toLocaleDateString("ro-ro", { weekday: 'narrow' }));
		subtitle2.push(i);

		sheet.columnWidth(i,45);
	}
	
	data = response.data;

	//set title
	setExcelTitle(numDays+1,'Transelectrica   '+data[0].county_code + "   "+now.toLocaleString('ro-ro',{month:'short', year:'numeric'}) );
	
	//set subtitle
	setExcelRow(subtitle1).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
	setExcelRow(subtitle2).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
	
	let arr=[];
	let rIdx=0;
	vmin = 10000000;
	vmax = -10000000;
		
	rangeMax = "AF27";
	
	switch(numDays) {
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
	
	//constru colorRanges to avoid forEachCell background set due to slow

	let hours=[];
	let dataArray = [];
	let idx = 0;
	for(let h=0;h<24;h++)
	{
		sheet.rowHeight(h+3,normalRowHeight);
		hours.push([parseInt(h)+1]);
		let dayArray = [];
		for(let d=1;d<=numDays;d++)
		{
			let s = '';
			if(data[idx] != undefined)
			{	
				dD = parseInt(data[idx].forecast_datetime.substring(8,10));
				dH = parseInt(data[idx].forecast_datetime.substring(11,13));
			
				if(dH == h && dD == d)
				{
					let fea = '';
					if(data[idx].forecast_ea != null)
					{
						fea = parseFloat(data[idx].forecast_ea);
						if(vmin>fea) vmin = fea;
						if(vmax<fea) vmax = fea;
					}
					dayArray.push(fea);
					idx++
				}
				else
					dayArray.push('');
			}
			else
				dayArray.push('');
		}
		dataArray.push(dayArray);
	}
	
	sheet.range("A4:"+rangeMax).values(hours);
	var hourRange = sheet.range("A4:A27").bold(true).textAlign("right");
	
	colorCoef = maxColors / ((vmax-vmin) == 0 ? 0.000001 : (vmax-vmin));
	
	let colorRanges=[];
	for(let h=0;h<24;h++)
	{
		for(let d=1;d<=numDays;d++)
		{
			let s = '';
			if(dataArray[h][d-1]!= '')
			{	 
				let color = computeColor(dataArray[h][d-1]);
				if(colorRanges[color] !== undefined)
					s = colorRanges[color];
				
				
				colorRanges[color] = s.concat( "R"+(h+4)+"C"+(d+1)+"," );
			}
			else
			{
				if(colorRanges['#ffffff'] !== undefined)
					s = colorRanges['#ffffff'];
				
				colorRanges['#ffffff'] = s.concat( "R"+(h+4)+"C"+(d+1)+"," );
			}
		}
	}
	sheet.range("B4:"+rangeMax).values(dataArray).format("0").textAlign("center");
	prevData = [...dataArray];
//	sheet.batch(function() {
		Object.keys(colorRanges).forEach(key => {
		  sheet.range(colorRanges[key].slice(0, -1)).background(key);
		});		
//	});
	
	currRow+=24;
	sheet.rowHeight(currRow-1,normalRowHeight);
	addTotalRow('Total',numDays,'SUM','0');
	
	/*var data = {
			models: [
					 {
						year : selYear_tp,
						month: selMonth_tp+1
					 }
					]
			};
	
	customCall(data,'getFreeDays',onPrezentGetFreeDays);*/
	
	/*hourRange.forEachCell(function (row, column, cellProperties) {
		if(row<40 && column < 40 && cellProperties.value !== undefined && cellProperties.value != '')
			//cellProperties.background="blue";
			sheet.range(row,column).background(computeColor(cellProperties.value));
	
	});*/
}

function uploadManualTranselectrica()
{
	var data = {
	models: [
			 {
				year : selYear_tp,
				month : selMonth_tp+1,
				county_code:$("#counties").data("kendoButtonGroup").current().text().trim(),
				data: sheet.range("B4:"+rangeMax).values()
			 }
			]
	};
	
	customCall(data,'uploadManualTranselectrica');
}

