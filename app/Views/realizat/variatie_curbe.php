<?php
require_once(__DIR__.'/../header.php'); 
require_once(__DIR__.'/../dialogs.php');

	
echo $data['output'];

curveVarianceDialog();

require_once(__DIR__.'/../footer.php');
?>

<!-- Include JSZip to enable Excel Export-->
<script src="/telerik/js/jszip.min.js"></script>
<style>
body {
	overflow:hidden;
}
</style> 

<script>
  function onChange(e) {
			let selection = this.selectedKeyNames();
			
			if(selection.length > 0)
			{
				$(".bulkDestroy").removeClass('k-state-disabled');
			}else
			{
				$(".bulkDestroy").addClass('k-state-disabled');
				//$("#grid").data("kendoGrid")._selectedIds=[];
			}
				
			
            //console.log("The selected product ids are: [" + this.selectedKeyNames().join(", ") + "]");
        };
		
	function bulkDestroy() {

	var grid = $("#grid").data("kendoGrid");
	
	if (confirm("Sigur doriti sa stergeti " + grid.selectedKeyNames().length + " curbe inregistrate? \n\n") != true) {
	  return;
	}

	var data = {
			models: [
					 {
						curvesVariancesIds : grid.selectedKeyNames()
					 }
					]
			};
							
	var jqxhr = $.post({
						url: window.location.origin+'/api?subject=custom&type=call&action=bulkCurvesVarianceDestroy',
						data: JSON.stringify(data),
						contentType: 'application/json; charset=utf-8'})
				.done(function(response) {
					if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
					
						$('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');
						return false;
					}
					else
					{
						if(response.deleted_no !== 'undefined')
						{
							$('#staticNotification').data('kendoNotification').show(response.deleted_no +' inregistrari au fost sterse!', 'info');
							
							grid.clearSelection();
							grid._selectedIds=[];
							$("#grid").data("kendoGrid").dataSource.read();
							updateBadges_consumption_date();
						}
							
						
					}
				})
				.fail(function() {				
					//probleme de retea
					$('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
					return false;
				});
				
	}
</script>

</body>
</html>
