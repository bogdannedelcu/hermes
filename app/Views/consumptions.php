<?php 
require_once('header.php'); 
require_once('dialogs.php');

echo $data['output'];
consumptionDialog();

require_once('footer.php');
?>
<script src="/telerik/js/jszip.min.js"></script>
<style>
body {
	overflow:hidden;
}
</style>

<script>

  
  function onChange(e) {
			var grid = $("#grid").data("kendoGrid");
	  
			let selection = this.selectedKeyNames();
			
			if(selection.length > 0)
			{
				let ffound = (grid.dataSource._data.filter(function (e){return grid.selectedKeyNames().includes(e.consumption_id.toString()) & e.deletable ;}).length>0);
				
				if(ffound)
					$(".bulkDestroy").removeClass('k-state-disabled');
				else
					$(".bulkDestroy").addClass('k-state-disabled');

			}else
			{
				$(".bulkDestroy").addClass('k-state-disabled');
				$("#grid").data("kendoGrid")._selectedIds=[];
			}
					
            console.log("The selected product ids are: [" + this.selectedKeyNames().join(", ") + "]");
        };
		
  function bulkDestroy() {
	var grid = $("#grid").data("kendoGrid");
	
	if (confirm("Sigur doriti sa stergeti " + grid.selectedKeyNames().length + " consumuri? \n\n Doar consumurile nefacturate vor fi sterse.") != true) {
	  return;
	}

	var data = {
			models: [
					 {
						consumptionsIDs :grid.selectedKeyNames().join(", ")
					 }
					]
			};
							
	var jqxhr = $.post({
						url: window.location.origin+'/api?subject=custom&type=call&action=bulkConsumptionsDestroy',
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
							$('#staticNotification').data('kendoNotification').show(response.deleted_no +' consumuri au fost sterse!', 'info');
							
							updateBadges_consumption_date();
							getBadges_active();
							 
							grid.clearSelection();
							$(".bulkDestroy").addClass('k-state-disabled');
							//grid._selectedIds=[];
							$("#grid").data("kendoGrid").dataSource.read();
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
