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
	
	$("#select_month_tp").parent().hide();
	queryReport();
	
});


function queryReport()
{
	kendo.ui.progress($(document.body), true);	
	
	data = {
				"take": 10000,
				"skip": 0,
				"page": 1,
				"pageSize": 10000,
				"filter": {
					"logic": "and",
					"filters": [
						{
							"field": "pisen_year",
							"operator": "eq",
							"value": selYear_tp
						}
					]
				},
				"group": []
			};
				
	apiCallRead(data, 'procast_view_transelectrica_pisen', dataReceived);
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

	currRow = 1;
	let now = new Date();
	
	if(selYear_tp != -1 && selMonth_tp != -1)
		now = new Date(selYear_tp,selMonth_tp);
	else
		now.setDate(0);
	
	//set column width
	let subtitle1 =['Judet/Luna'];
	sheet.columnWidth(0,85);
	counties = [];
	
	data = response.data;
	
	dataArray = [];	
	idx = 0;
	
	//prepare subtitle
	for(let i=1;i<=12;i++)
	{

		let d = new Date(now.getTime());
		d.setMonth(i-1);
		d.setDate(i); 

		subtitle1.push(d.toLocaleDateString("ro-ro", { month:'short' }).toUpperCase().slice(0, -1));
		sheet.columnWidth(i+1,45);
	}
		
	vmin = 10000000;
	vmax = -10000000;
	
	//prepare data
	for(j=0;j<coduriJudete.length;j++)
	{
		counties.push([coduriJudete[j]]);
		row = [];
		for(let i=1;i<=12;i++)
		{		
			if(data[idx] != undefined)
			{
				month = parseInt(data[idx].pisen_date.substr(5,2));
				if(month == i && coduriJudete[j] == data[idx].county_code)
				{
					fea = parseFloat(data[idx].pisen_ea);
					if(coduriJudete[j]!='RO')
					{
						if(vmin>fea) vmin = fea;
						if(vmax<fea) vmax = fea;
					}
					
					row.push(fea);
					idx++;
				}
				else row.push('');
			}
			else row.push('');
		}
		dataArray.push(row);
	}
	
	colorCoef = maxColors / ((vmax-vmin) == 0 ? 0.000001 : (vmax-vmin));

	//prepare color ranges
	let colorRanges=[];
	for(j=0;j<coduriJudete.length;j++)
	{
		for(let i=1;i<=12;i++)
		{
			let s = '';
			if(dataArray[j][i-1]!= '')
			{	 
				let color = computeColor(dataArray[j][i-1]);
				if(colorRanges[color] !== undefined)
					s = colorRanges[color];
				
				
				colorRanges[color] = s.concat( "R"+(j+3)+"C"+(i+1)+"," );
			}
			else
			{
				if(colorRanges['#ffffff'] !== undefined)
					s = colorRanges['#ffffff'];
				
				colorRanges['#ffffff'] = s.concat( "R"+(j+3)+"C"+(i+1)+"," );
			}
		}
	}
	//apply colors
	Object.keys(colorRanges).forEach(key => {
	  sheet.range(colorRanges[key].slice(0, -1)).background(key);
	});	
	
	//set title
	setExcelTitle(14,'Puterea Instalata Sistemul Energetic National   '+"   "+now.toLocaleString('ro-ro',{year:'numeric'}) );
	
	//set subtitle
	setExcelRow(subtitle1).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
	
	//set data
	sheet.range("A3:A46").values(counties).textAlign("center").bold(true);
	sheet.range("B3:N46").values(dataArray).format("0.00").textAlign("center");
}

function uploadPISEN ()
{

	var data = {
		models: [
				 {
					year : selYear_tp,
					data: sheet.range("B3:M45").values()
				 }
				]
		};
	
	customCall(data,'uploadPISEN');
}