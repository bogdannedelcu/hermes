function changed_pc_regions(e)
{
	queryReport(e);
}

function queryReport(e)
{
	window.location.href = location.protocol + "//" + location.host + location.pathname+"?date="+selYear_tp + "-"+(selMonth_tp+1)+"&region="+$("#pc_regions").data("kendoButtonGroup").current().text().trim();
}

function skyReceived(response)
{
	console.log(response);
}

function downloadSky()
{
	var data = {
			models: [
					 {
						supplierID: supplierID,
						month: selMonth_tp,
						year: selYear_tp,
						regionName: $("#pc_regions").data("kendoButtonGroup").current().text().trim(),
						source: "forecast.solar"
					 }
					]
			};
	
	customCall(data,'downloadSky',skyReceived);	
}

function uploadProduction(estimated)
{

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
						regionName: $("#pc_regions").data("kendoButtonGroup").current().text().trim(),
						YMdate : selYear_tp + "-"+(selMonth_tp+1),
						values:sheet.range("B4:"+rangeMax).values()
					 }
					]
			};
			
	var jqxhr = $.post({
					url: window.location.origin+'/api?subject=custom&type=call&action=' + (estimated ? 'uploadEstimatedProduction':'uploadProduction'),
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


 $( document ).ready(function() {
	
	$(".minw-120px").addClass("minw-100px").removeClass("minw-120px");
 
 });


function onUpload(e) {
	
	e.data = {};
	Object.assign(e.data, {region_id:pc_regions[$("#pc_regions").data("kendoButtonGroup").current().index()]});
			
	$("#grid").addClass("k-state-disabled");
	kendo.ui.progress($(document.body), true);
}

function onComplete(e) {
	kendo.ui.progress($(document.body), false);
	$("#grid").removeClass("k-state-disabled");
}

function onSuccess(e) {
	if (e.operation == "upload") {
		for (var i = 0; i < e.files.length; i++) {
			var file = e.files[i].rawFile;

			if (file) {
				var reader = new FileReader();

				reader.onloadend = function () {
					//location.reload();
					//$("#stepper").data("kendoStepper").next();
					$("#staticNotification").data("kendoNotification").show(e.response.msg,e.response.type);
				};
				
			}
		}
		
		if(e.response.msg.includes('duplicat'))
		{
			$("#staticNotification").data("kendoNotification").show(e.response.msg,'error');
		}
		else
			location.reload();
			
	}
}

function onError(e)
{
	$("#staticNotification").data("kendoNotification").show('Eroare comunicare, mai incercati odata!','error');
}