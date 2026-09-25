
function waitFor () {
  if ($("#tabstrip-tab-1").length > 0) { //if the container is visible on the page
		
		$("#tabstrip-tab-1").click();
  } else {
	setTimeout(waitFor, 50); //wait 50 ms, then try again
	}
}

$(document).ready(function () {
	
	waitFor();
});

function deleteData()
{
	kendo.ui.progress($(document.body), true);
	
	$("#staticNotification").data("kendoNotification").show("Operatiune in lucru...", "error");
	var data = {
			models: [
					 {
						consumptions:$("#consumptions").data("kendoCheckBox").value(),
						invoices:$("#invoices").data("kendoCheckBox").value(),
						actual_readings:$("#actual_readings").data("kendoCheckBox").value(),
						curves_variance:$("#curves_variance").data("kendoCheckBox").value(),
						curves:$("#curves").data("kendoCheckBox").value(),
						date:kendo.toString($("#date").data("kendoDatePicker").value(), "MM") + "-" + kendo.toString($("#date").data("kendoDatePicker").value(), "yyyy")
					 }
					]
			};
							
	var jqxhr = $.post({
			url: window.location.origin+"/api?subject=custom&type=call&action=systemDeleteData",
			data: JSON.stringify(data),
			contentType: "application/json; charset=utf-8"})
	.done(function(response) {
		if (typeof response !== "undefined" && typeof response.errors !== "undefined") {

		$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
		kendo.ui.progress($(document.body), false);
		return false;
	}
	else
	{
		$("#staticNotification").data("kendoNotification").show(response, "error");
		kendo.ui.progress($(document.body), false);
		return false;			
	}
	})
	.fail(function() {
		//probleme de retea
		$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
		kendo.ui.progress($(document.body), false);
		return false;
	});
	
	return false;	
}

function reloadPage(){location.reload();};

function systemReloadCache()
{
	kendo.ui.progress($(document.body), true);
	
	var data = {models: [{}]};
	customCall(data,'systemReloadCache',reloadPage);
}