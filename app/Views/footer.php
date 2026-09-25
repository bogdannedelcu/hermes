<?php
if(isset($data['gridJS'])) echo $data['gridJS'];
?>

<footer class="page-footer font-small bg-light pt-4 d-none">

  <!-- Footer Elements -->
  <div class="container">

    <!--Grid row-->
    <div class="row">

      <!--Grid column-->
      <div class="col-sm">
		<p>Page rendered in {elapsed_time} seconds</p>
	  </div>
	  
	       <!--Grid column-->
      <div class="col-sm">
		<p>Environment: <?= 'DEMO' /*ENVIRONMENT*/ ?></p>
	  </div>
	  
	       <!--Grid column-->
      <div class="col-sm">
		<p>EBS CI: <?= CodeIgniter\Codeigniter::CI_VERSION ?></p>	  
	  </div>
	  
	<div class="col-sm">
		&copy; <?= date('Y') ?> <a href="https://cloudromania.ro/"> INA INTERNATIONAL SERVICES SRL </a>
	</div>
	
	</div>
	
  </div>
	

</footer>

<script>
	function resizeGrid() {
		
		if($("#grid").length>0)
		{
			var $el = $('.card');  //record the elem so you don't crawl the DOM everytime  
			var bottom = $el.position().top + $el.outerHeight(true);

			var height = window.innerHeight-bottom-2;//$(".card").outerHeight(true)-$(".navbar").outerHeight(true);
			$("#grid").height(height);
			$("#grid").data("kendoGrid").resize();
		}
		//$("#grid").data("kendoGrid").setOptions({height:height});
	}

	function resizeExcel() {
		
		if($("#spreadsheet").length>0)
		{		
			var $el = $('.card');  //record the elem so you don't crawl the DOM everytime  
			var bottom = $el.position().top + $el.outerHeight(true);

			var height = window.innerHeight-bottom-2;//$(".card").outerHeight(true)-$(".navbar").outerHeight(true);
			
			$("#spreadsheet").height(height);
			$("#spreadsheet").data("kendoSpreadsheet").resize();
		}
		//$("#grid").data("kendoGrid").setOptions({height:height});
	}
	
	var retries = 0;
	$(document).ready(function () {
			
		function checkGrid () {
		  if ($('#grid').length > 0) { //if the container is visible on the page
	        resizeGrid();
		  } else {
			if(retries++<20)
			setTimeout(checkGrid, 50); //wait 50 ms, then try again
			}
		}
		
		checkGrid();
	});
</script>
