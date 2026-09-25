function uploadTemperatures()
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
						YMdate : selYear_tp + "-"+(selMonth_tp+1),
						values:sheet.range("B4:"+rangeMax).values()
					 }
					]
			};
			
	var jqxhr = $.post({
					url: window.location.origin+'/api?subject=custom&type=call&action=uploadTemperatures',
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