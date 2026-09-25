function inaExcel(subject, id, intervals=24,scale="energy", maxCols = 40, maxRows = 40)
{
	inaOptions[id]={
		subject:subject,
		id:id,
		maxCols:maxCols,
		maxRoes:maxRows,
		intervals:intervals,
		currRow:1,
		totalRows:0,
		scale:scale,
		_computeColor: function(value, mmin = this.vmin, mmax = this.vmax, cCoef = this.colorCoef)
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
		},
		_computeTColor: function(value)
		{
			let legend = this.legend;
			
			if(value == null || isNaN(value)) return "#FFFFFF";

			if(value<-50) value = -50;
			if(value>50) value = 50;
			
			value += 50;
			if((value*legend.tCoef)>=legend.tColorsSize)
				return legend.tColors[legend.tColorsSize-1];
			
			if(value*legend.tCoef<0)
				return legend.tColors[0];
			
			return legend.tColors[Math.round(value*legend.tCoef)];
		},
		setExcelTitle: function (title, selSheet = this.sheet)
						{
							if(zoomType == "Normal") {fontSize = normalFontSize + 4; rowHeight = normalRowHeight + 2;}
							else if(zoomType == "Mic") {fontSize = smallFontSize; rowHeight = smallRowHeight;}

							var range = selSheet.range("R1C1:R1C"+this.numDays).merge().value(title.toUpperCase()).fontSize(fontSize).textAlign("center").bold(true);
							selSheet.rowHeight(this.currRow-1, rowHeight);
							this.currRow++;
							
							return range;
						},
		setExcelRow: function (arr, selSheet = this.sheet)
					{
						var range = selSheet.range("R"+this.currRow+"C1:R"+this.currRow+"C"+arr.length).values([arr]);
						this.currRow++;						
						return range;
					},
		markFreeDays: function () {
					
					let io = this;
					function onGetFreeDays(response)
					{		
						let r = [];
						for(let i = 0;i<response.freeDays.length;i++)
						{
							r.push("R2C"+ (response.freeDays[i]+1),"R3C"+ (response.freeDays[i]+1));
							
							for(t = 0; t< io.totalRows; t++)
								r.push("R"+(4+t+io.intervals)+"C"+ (response.freeDays[i]+1));
						}
												
						io.sheet.range(r.join(",")).background("rgb(184,235,255)");						
					}
					
					var data = {
						models: [
								 {
									year : io.year,
									month: io.month
								 }
								]
						};
									
					customCall(data,'getFreeDays',onGetFreeDays,false, false);
					
				},
		prepareExcel: function (year, month) 				/*initialisation*/
				{
					//update object
					this.numDays = calcNumDays(year,month);
					this.year = year;
					this.month = month;
					this.colOffset = 0;
					this.currRow = 1;
					this.totalRows = 0;

					//set column width
					let subtitle1 =[];
					let subtitle2 =[];
					this.sheet.columnWidth(0,55);
					subtitle1.push('');
					subtitle2.push('Interval');	

					let now = new Date();
					now = new Date(year,month-1);
					colOffset = this.colOffset;

					for(let i=1;i<=this.numDays;i++)
					{

						let d = new Date(now.getTime());
						d.setDate(i); 

						subtitle1.push(d.toLocaleDateString("ro-ro", { weekday: 'narrow' }));
						subtitle2.push(i);

						this.sheet.columnWidth(i+colOffset,45);
					}

					let io = this;
					this.sheet.batch(function(){

						io.sheet.select("A1");
						$(".k-spreadsheet-scroller").scrollTop(0).scrollLeft(0);

						io.sheet.range("R1C1:R30C370").clear().values('');
						io.sheet.range(kendo.spreadsheet.SHEETREF).clear();
						io.sheet.rowHeight(1, 20);
						
						//set title
						io.setExcelTitle(io.subject + ' ' + now.toLocaleString('ro-ro',{month:'short', year:'numeric'}));
						
						//set subtitle
						io.setExcelRow(subtitle1).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
						io.setExcelRow(subtitle2).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
						
						io.markFreeDays();
					});
				},
				/* data:datetime, value*/
		setExcelData: function (response)
				{
					let rIdx=0;
					this.vmin = 10000000;
					this.vmax = -10000000;
					this.dataArray=[];
					
					for (let i = 0; i < this.intervals; i++) {
						this.dataArray[i] = [i+1];
						for (let d = 1; d <= this.numDays; d++) {
							
							if(this.intervals > 24)
							{
								hour = Math.floor(i / 4);
								minute = (i % 4) * 15;
							}
							
							const r = response.find(obj => {
								const date = kendo.parseDate(obj.datetime, "yyyy-MM-dd HH:mm:ss");
								return (this.intervals == 24 && date.getDate() === (d) && date.getHours() === i) || 
								 (this.intervals>24 && date.getDate() === (d) && date.getHours() === hour && date.getMinutes() === minute);
							});
							
							if(r !== undefined)
							{
								let fea = '';
								if(r.value != null)
								{
									fea = parseFloat(r.value);
									if(this.vmin>fea) this.vmin = fea;
									if(this.vmax<fea) this.vmax = fea;
								}
								this.dataArray[i][d] = fea; // Or any other initial value
							}
						}
					}

					this.colorCoef = maxColors / ((this.vmax-this.vmin) == 0 ? 0.000001 : (this.vmax-this.vmin));
				
					let io = this;
					this.sheet.batch(function(){
						io.sheet.range("R4C1:"+"R"+(io.intervals+3)+"C32").values(io.dataArray);
					});
					
					this.showScale();
				},
		addTotalRow: function(title,formula,format,startDataRow = 4, selSheet = this.sheet)
				{
					let io = this;
					
					let tRow = io.intervals+startDataRow+io.totalRows;
					io.sheet.batch(function(){
						selSheet.range("R"+tRow+"C1").value(title).background("rgb(167,214,255)");
						
						for(let i=1;i<=io.numDays;i++)
							selSheet.range('R'+tRow+'C'+(i+1)).formula('=IFERROR('+formula+'(R'+startDataRow+'C'+(i+1)+':R'+(io.intervals+startDataRow-1)+'C'+(i+1)+'),"")');

						selSheet.range("R"+tRow+"C1:R"+tRow+"C"+(io.numDays+1)).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
						selSheet.range("R"+tRow+"C2:R"+tRow+"C"+(io.numDays+1)).format(format);
					});
					var range = selSheet.range("R"+tRow+"C1:R"+tRow+"C"+(io.numDays+1));
					
					//this.currRow++;
					this.totalRows++;
										
					return range;
				},
		showScale: function()
				{					
					//constru colorRanges to avoid forEachCell background set due to slow
					var compute = (this.scale == "energy" ? this._computeColor.bind(this) : this._computeTColor.bind(this) );
					let colorRanges=[];
					for(let h=0;h<this.intervals;h++)
					{
						for(let d=1;d<=this.numDays;d++)
						{
							let s = '';
							if(this.dataArray[h][d]!= '')
							{	
								let color = compute(this.dataArray[h][d]);
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
					let io = this;
					this.sheet.batch(function() {
						Object.keys(colorRanges).forEach(key => {
						  io.sheet.range(colorRanges[key].slice(0, -1)).background(key);
						});			
					});	
				}
		
	};
	
	let cell = {"format":"#","background":"#ff0000","value":1};
	let cells = Array(maxCols).fill(cell);
	let row = {cells};
	let rows = Array(maxRows).fill(row);
	inaOptions[id].spreadsheet = $("#spreadsheet").kendoSpreadsheet({
			rows:maxRows,
			columns:maxCols,
          sheets: [{
            name: subject,
            rows: []
          }],  
		  /*changing: function(e){
            if(e.data !='')
				e.range.background(computeColor(parseFloat(e.data)));
			else
				e.range.background("#ffffff");
			
		}*/
        }).getKendoSpreadsheet();
	
	inaOptions[id].sheet = inaOptions[id].spreadsheet.activeSheet();
	inaOptions[id].sheet.frozenColumns(1);
}

/*excel tools*/
const maxColors = 100;
const smallRowHeight = 12;
const normalRowHeight = 18;
const smallFontSize = 10;
const normalFontSize = 12;

var zoomType = "Normal";

var rangeMax = "";

function calcNumDays(year, month)
{
	let now = new Date();
		
	if(month != -1 && year != -1)
		now = new Date(year,month-1);
	else
		now.setDate(0);

	return daysInMonth(now);
}

function daysInMonth(date) {
  
  return parseInt(kendo.toString(kendo.date.lastDayOfMonth(date),"dd"));
}