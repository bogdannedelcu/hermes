	<?php
	
	$button0 = new \Kendo\UI\Button('uploadFile');
	$button0->icon('k-icon k-i-file-add');
	$button0->click('function(e) { $("#files\\\\[\\\\]").click(); }');

	echo $button0->render();
	/*
	$button0 = new \Kendo\UI\Button('downloadSky');
	$button0->icon('k-icon k-i-download');
	$button0->click('function(e) { downloadSky(); }');

	echo $button0->render();
	*/
	
	$button0 = new \Kendo\UI\Button('uploadProduction');
	$button0->icon('k-icon k-i-upload');
	$button0->click('function(e) { uploadProduction(true); }');

	echo $button0->render();
	
	$button1 = new \Kendo\UI\Button('saveSpreadsheet');
	$button1->icon('k-icon k-i-save');
	$button1->click('function(e) { $("button[title=\'Export...\']").click(); }');

	echo $button1->render();
	
	$button2 = new \Kendo\UI\Button('showToolbar');
	$button2->iconClass('k-icon k-i-wrench');
	$button2->click('function(e) { $(".k-spreadsheet-quick-access-toolbar").toggleClass("d-none"); $(".k-spreadsheet-tabstrip").toggleClass("d-none");$("#collapseCard > div > div:nth-child(4)").toggleClass("d-none");resizeExcel();
									$(".k-spreadsheet-quick-access-toolbar").hasClass("d-none") == true ? Cookies.set("showCard-" + document.title,"false") : Cookies.set("showCard-" + document.title,"true"); 
								 }');
	
	echo $button2->render();
	
	?>
			
	<script>
	
	function onGetFreeDays(response)
	{
		let sheet = $("#spreadsheet").data("kendoSpreadsheet").activeSheet();
		
		let r = [];
		for(let i = 0;i<response.freeDays.length;i++)
		{
			r.push("R2C"+ (response.freeDays[i]+1),"R3C"+ (response.freeDays[i]+1),"R28C"+ (response.freeDays[i]+1),"R29C"+ (response.freeDays[i]+1),"R30C"+ (response.freeDays[i]+1));
		}
		
		let maxDays = new Date(selYear_tp, selMonth_tp+1, 0).getDate();
		
		sheet.range("R28C1:R30C"+(maxDays+1)).background("rgb(167,214,255)").color("black").bold(true);
		sheet.range(r.join(",")).background("rgb(184,235,255)");
	}

	function markFreeDays() {
		
		var data = {
			models: [
					 {
						year : selYear_tp,
						month: selMonth_tp+1
					 }
					]
			};
						
		customCall(data,'getFreeDays',onGetFreeDays,false, false);
		
	}
	
	$(document).ready(function () {
		function checkContainer () {
		  if ($('.k-spreadsheet-tabstrip').length > 0) { //if the container is visible on the page
			if(Cookies.get('showCard-' + document.title) != 'true')
			{
				$(".k-spreadsheet-quick-access-toolbar").toggleClass("d-none"); $(".k-spreadsheet-tabstrip").toggleClass("d-none");	
				$("#collapseCard > div > div:nth-child(4)").toggleClass("d-none");
				resizeExcel();
			}
			markFreeDays();
		  } else {
			setTimeout(checkContainer, 50); //wait 50 ms, then try again
			}
		}
		
		checkContainer();
	});
	</script>