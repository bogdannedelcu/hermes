			<?php

			$button1 = new \Kendo\UI\Button('exportToForecast');
			$button1->icon('k-icon k-i-check-outline '.$data['checkColor']);
			$button1->click('function(e) { exportReadingsToForecast(); }');

			echo $button1->render();
			
			$button1 = new \Kendo\UI\Button('saveSpreadsheet');
			$button1->icon('k-icon k-i-save');
			$button1->click('function(e) { $("button[title=\'Export...\']").click(); }');

			echo $button1->render();
			
			$button2 = new \Kendo\UI\Button('showToolbar');
			$button2->iconClass('k-icon k-i-wrench');
			$button2->click('function(e) { $(".k-spreadsheet-quick-access-toolbar").toggleClass("d-none"); $(".k-spreadsheet-tabstrip").toggleClass("d-none");$("#collapseCard > div > div:nth-child(4)").toggleClass("d-none");$("#colCurves").toggleClass("d-inline-flex").toggleClass("d-none");resizeExcel();
											$(".k-spreadsheet-quick-access-toolbar").hasClass("d-none") == true ? Cookies.set("showCard-" + document.title,"false") : Cookies.set("showCard-" + document.title,"true");											
										 }');
			
			echo $button2->render();
			
			?>
			
	<script>
	
	function onGetFreeDays(response)
	{
		let sheet = $("#spreadsheet").data("kendoSpreadsheet").activeSheet();
		
		let r = [];
		
		let hrow = "28";
		if($('#select-activ').data('kendoButtonGroup').current().index() == 1)
			hrow = "100";
			
		for(let i = 0;i<response.freeDays.length;i++)
		{
			
			r.push("R2C"+ (response.freeDays[i]+1),"R3C"+ (response.freeDays[i]+1),"R"+hrow+"C"+ (response.freeDays[i]+1));
		}
		
		sheet.range(r.join(",")).background("rgb(184,235,255)");
	}

	function markFreeDays() {
		
		var data = {
			models: [
					 {
						year : selYear_tp,
						month: selMonth_tp+1,
						customers: $("#mCustomers").data("kendoMultiSelect").value().join()
					 }
					]
			};
	
		customCall(data,'getFreeDays',onGetFreeDays);
		
	}
	
	$(document).ready(function () {
		
		function checkContainer () {
		  if ($('.k-spreadsheet-tabstrip').length > 0) { //if the container is visible on the page
			if(Cookies.get('showCard-' + document.title) != 'true')
			{
				$(".k-spreadsheet-quick-access-toolbar").toggleClass("d-none"); $(".k-spreadsheet-tabstrip").toggleClass("d-none");	
				$("#collapseCard > div > div:nth-child(4)").toggleClass("d-none");
				$("#colCurves").removeClass("d-inline-flex").addClass("d-none");
				resizeExcel();
				markFreeDays();
			}
		  } else {
			setTimeout(checkContainer, 50); //wait 50 ms, then try again
			}
		}
		
		checkContainer();
	});
	
	function exportReadingsToForecast()
	{
		kendo.ui.progress($(document.body), true);
		
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
						customers :$("#mCustomers").data("kendoMultiSelect").value(),
						pods :$("#mPODs").data("kendoMultiSelect").value(),
						distributorID:dId[$('#select-distributor').data('kendoButtonGroup').current().index()] ?? 0,
						YMdate : selYear_tp + "-"+(selMonth_tp+1),
						values: $("#spreadsheet").data("kendoSpreadsheet").activeSheet().range("B4:"+rangeMax).values()
					 }
					]
			};
			
		var jqxhr = $.post({
					url: window.location.origin+'/api?subject=custom&type=call&action=actual_exportReadingsToForecast',
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
					if(response[0]!='0') $("#exportToForecast > span").removeClass("text-danger").addClass("text-success");
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
	</script>