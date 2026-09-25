
	<?php
	$yearDDList = new \Kendo\UI\DropDownList('invoice_year');
	$yearDDList->value($data['year'])
	  ->dataTextField('text')
	  ->dataValueField('value')
	  ->dataSource($data['range'])
	  ->change('function(e) {
		window.location.href = location.protocol + "//" + location.host + location.pathname+"?year="+this.value();
		}');

	echo $yearDDList->render();
	
	$button1 = new \Kendo\UI\Button('saveSpreadsheet');
	$button1->icon('k-icon k-i-save');
	$button1->click('function(e) { $("button[title=\'Export...\']").click(); }');

	echo $button1->render();
	
	$button2 = new \Kendo\UI\Button('showToolbar');
	$button2->iconClass('k-icon k-i-wrench');
	$button2->click('function(e) { $(".k-spreadsheet-quick-access-toolbar").toggleClass("d-none"); $(".k-spreadsheet-tabstrip").toggleClass("d-none");var spreadsheet = $("#spreadsheet").data("kendoSpreadsheet");spreadsheet.refresh();}');
	
	echo $button2->render();
	
	?>
			
	<script>
	$(document).ready(function () {
			
		function checkContainer () {
		  if ($('.k-spreadsheet-tabstrip').length > 0) { //if the container is visible on the page
			$(".k-spreadsheet-quick-access-toolbar").toggleClass("d-none"); $(".k-spreadsheet-tabstrip").toggleClass("d-none");	
			
		  } else {
			setTimeout(checkContainer, 50); //wait 50 ms, then try again
			}
		}
		
		checkContainer();
	});
	</script>
