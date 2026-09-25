$( document ).ready(function() {

	//create time period filter
	getMinMaxYears('weather_data','weather_datetime',createPeriodFilter);
	
	//create counties
	inaFilterButtons("weather_data", "county_code", "B", "Judet",['B','AB','AR','AG','BC','BH','BN','BT','BV','BR','BZ','CS','CL','CJ','CT','CV','DB','DJ','GL','GR','GJ','HR','HD','IL','IS','IF','MM','MH','MS','NT','OT','PH','SM','SJ','SB','SV','TR','TM','TL','VL','VS','VN'],refreshReport,"county_code");
	inaFilterButtons("weather_data", "layer", "temp", "UM",['temp','feelslike','dew','humidity','precip','precipprob','snow','snowdepth','windgust','windspeed','winddir','pressure','cloudcover','visibility','solarradiation','solarenergy','uvindex'],refreshReport,"layer");
	
	inaExcel("Prognoza Meteo", "weather");
		
	resizeExcel();

});

function refreshReport()
{
	if( inaOptions['weather_datetime'] === undefined ||
		inaOptions['layer'] === undefined ||
		inaOptions['county_code'] === undefined ) return;
	
	inaOptions['weather'].prepareExcel(inaOptions['weather_datetime'].selectedYear, inaOptions['weather_datetime'].selectedMonth);
	inaOptions['weather'].addTotalRow("MIN","MIN","0.00");
	inaOptions['weather'].addTotalRow("AVG","AVERAGE","0.00");
	inaOptions['weather'].addTotalRow("MAX","MAX","0.00");
	
	var data = {
	models: [
			 {
				year : inaOptions['weather_datetime'].selectedYear,
				month: inaOptions['weather_datetime'].selectedMonth,
				layer: inaOptions['layer'].selectedValue,
				countyCode: inaOptions['county_code'].selectedValue
			 }
			]
	};
					
	customCall(data,'getWeatherData',weatherDataReceived,false, false);
}


function weatherDataReceived(response)
{
	inaOptions['weather'].legend = response.legend;
	if(inaOptions['layer'].selectedValue == "temp" || inaOptions['layer'].selectedValue == "feelslike")
		inaOptions['weather'].scale="legend";
	else
		inaOptions['weather'].scale="energy";
	

	inaOptions['weather'].setExcelData(response.data);
}

function createPeriodFilter(response)
{
	const currentDate = new Date();
	const lastYearDate = new Date(currentDate); lastYearDate.setFullYear(currentDate.getFullYear());
	if(currentDate.getMonth()==11) response.maxYear++;
	inaPeriodFilter("weather_data","weather_datetime","Perioada",lastYearDate,Math.min(response.minYear,2024),response.maxYear,refreshReport);
}

function getMeteo() {
	
	kendo.ui.progress($(document.body), true);
	var data = {
		models: [
				 {
					year : inaOptions['weather_datetime'].selectedYear,
					month: inaOptions['weather_datetime'].selectedMonth 
				 }
				]
		};
					
	customCall(data,'getMeteo',refreshReport,false,true);
}

