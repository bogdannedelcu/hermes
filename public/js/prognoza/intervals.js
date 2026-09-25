function uploadIntervals()
{

	kendo.ui.progress($(document.body), true);

	let sheet = $("#spreadsheet").data("kendoSpreadsheet").activeSheet();

	var data = {
			models: [
					 {
						customerID:$('#select-customer').data('kendoDropDownList').value(),
						values:sheet.range("B3:M26").values()
					 }
					]
			};
			
	var jqxhr = $.post({
					url: window.location.origin+'/api?subject=custom&type=call&action=uploadIntervals',
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

function queryReport()
{
	window.location.href = location.protocol + "//" + location.host + location.pathname+"?customerID="+$('#select-customer').data('kendoDropDownList').value();
}

function onChanging(e)
{
	if(e.changeType == 'edit' || e.changeType == 'paste')
	{
		if(Array.isArray(e.data))
		{	
			let sheet = $("#spreadsheet").data("kendoSpreadsheet").activeSheet();
			let my=e.data.length-1;
			let mx=e.data[0].length-1;
			let x=y=0;
			e.range.forEachCell(function (row, column, cellProperties) {
				
				while(x<=mx && y<=my)
				{
					let cv = e.data[y][x].value;
					
					if(['z','Z','n','N'].includes(cv))
						cv = cv.toUpperCase();
					else
						cv = 'N';
					
					if(cv =='Z')
						sheet.range(row+y,column+x).background('#00ff00');
					else
						sheet.range(row+y,column+x).background("#ff0000");
				
					sheet.range(row+y,column+x).value(cv);
					
					if(x<mx) x++;
					else
					{
						x = 0;
						if(y<my) y++;
						else break;
					}
				}
				//console.log(row, column, cellProperties);
			});
			e.preventDefault();
			return;
		}
		else if(['z','Z','n','N'].includes(e.data))
			{
				if(e.data.toUpperCase() =='Z')
					e.range.background('#00ff00');
				else
					e.range.background("#ff0000");
				
				e.range.value(e.data.toUpperCase());
				e.preventDefault();
				return;
			}
		
		e.preventDefault();
		kendo.alert("Z = interval de zi<br>N = interval de noapte");
	}
	
	//autofill to do
}