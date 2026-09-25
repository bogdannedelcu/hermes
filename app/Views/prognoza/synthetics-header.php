	<div class="container  ms-0">
		<div class="row py-1">
		<div class="col">
		<?php
			$button = new \Kendo\UI\Button('generateCurve');
			$button->icon('k-icon fa-solid fa-bezier-curve');
			$button->click('function(e) { synthGeneratorDialog(); }');

			echo $button->render();
			
			$button0 = new \Kendo\UI\Button('uploadSynthetics');
			$button0->icon('k-icon k-i-upload');
			$button0->click('function(e) { uploadSynthetics(); }');

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
		</div>
		<div class="col-auto align-left">
		<?php
		
			echo $data['customer_filter'];
			
		?>
		</div>
		<div class="col-auto align-left d-none">
		<span class="mx-2">Valori</span>
		<input id='synthetic_type' value='<?php echo $data['synthetic_type']?>' />
		</div>
		</div>
	</div>		
	<script>
	$(document).ready(function () {
		
	function onGetFreeDays(response)
	{
		let sheet = $("#spreadsheet").data("kendoSpreadsheet").activeSheet();
		
		let r = [];
		for(let i = 0;i<response.freeDays.length;i++)
		{
			r.push("R2C"+ (response.freeDays[i]+1),"R3C"+ (response.freeDays[i]+1),"R28C"+ (response.freeDays[i]+1));
		}
		
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
	
		customCall(data,'getFreeDays',onGetFreeDays);
		
	}
		
		function checkContainer () {
		  if ($('.k-spreadsheet-tabstrip').length > 0) { //if the container is visible on the page
			if(Cookies.get('showCard-' + document.title) != 'true')
			{
				$(".k-spreadsheet-quick-access-toolbar").toggleClass("d-none"); $(".k-spreadsheet-tabstrip").toggleClass("d-none");	
				$("#collapseCard > div > div:nth-child(4)").toggleClass("d-none");
				resizeExcel();
				markFreeDays();
			}
		  } else {
			setTimeout(checkContainer, 50); //wait 50 ms, then try again
			}
		}
		
		checkContainer();
	});
	
	</script>