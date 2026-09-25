	<div class="container  ms-0">
		<div class="row py-1">
		<div class="col">
		<?php
			
			$button0 = new \Kendo\UI\Button('estimate');
			$button0->icon('k-icon fa-solid fa-calculator');
			$button0->click('function(e) { estimate(); }');

			echo $button0->render();			
			
			$button0 = new \Kendo\UI\Button('uploadEstimates');
			$button0->icon('k-icon k-i-upload');
			$button0->click('function(e) { uploadEstimates(); }');

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
		
			//echo $data['customer_filter'];
			
		?>
		</div>
		</div>
	</div>		