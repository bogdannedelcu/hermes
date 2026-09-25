/*excel tools*/
const maxColors = 100;
const smallRowHeight = 12;
const normalRowHeight = 18;
const smallFontSize = 10;
const normalFontSize = 12;

var zoomType = "Normal";

var rangeMax = "";

function daysInMonth(date) {
  
  return parseInt(kendo.toString(kendo.date.lastDayOfMonth(date),"dd"));
}

function addTotalRow(title,numDays,formula,format,startDataRow = 4, selSheet = sheet)
{
	selSheet.range("R"+currRow+"C1").value(title).background("rgb(167,214,255)");
	
	for(let i=1;i<=numDays;i++)
		selSheet.range('R'+currRow+'C'+(i+1)).formula('='+formula+'(R'+startDataRow+'C'+(i+1)+':R'+(currRow-1)+'C'+(i+1)+')');

	selSheet.range("R"+currRow+"C1:R"+currRow+"C"+(numDays+1)).background("rgb(167,214,255)").color("black").bold(true).textAlign("center");
	selSheet.range("R"+currRow+"C2:R"+currRow+"C"+(numDays+1)).format(format);
	
	var range = selSheet.range("R"+currRow+"C1:R"+currRow+"C"+(numDays+1));
	
	currRow++;

	return range;
}

function setExcelTitle(numDays,title, selSheet = sheet)
{
	if(zoomType == "Normal") {fontSize = normalFontSize + 4; rowHeight = normalRowHeight + 2;}
	else if(zoomType == "Mic") {fontSize = smallFontSize; rowHeight = smallRowHeight;}

	var range = selSheet.range("R1C1:R1C"+numDays).merge().value(title.toUpperCase()).fontSize(fontSize).textAlign("center").bold(true);
	selSheet.rowHeight(currRow-1, rowHeight);
	currRow++;
	
	return range;
}

function setExcelRow(arr, selSheet = sheet)
{
	var range = selSheet.range("R"+currRow+"C1:R"+currRow+"C"+arr.length).values([arr]);
	currRow++;
	
	return range;
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