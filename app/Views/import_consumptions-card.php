<form action="<?php echo base_url('ImportConsumptions/upload');?>" name="ajax_form" id="ajax_form" method="post" accept-charset="utf-8" enctype="multipart/form-data"> 
	<div class="container-fluid">
		<div class="row">
			<div class="col py-4">
				<?php echo $data['stepper']; ?>
			</div>
		</div>
		<div class="row d-none">
			<div class="col dropZone">	
					<!--<input style="margin: 20px 0 20px 0; padding: 50px 100px 50px 100px;box-shadow: 0px 0px 7px 3px rgba(0,0,0,0.10);" type="file" name="file[]" class="form-control" multiple id="file" onchange="readURL(this);" accept=".xlsx, .pdf" /> -->
					<?php echo $data['upload']; ?>
			</div>
			<div class="col">
				<div style="padding: 12px 0px 0px 20px">
					<button type="submit" id="send_form" class="btn btn-primary">Incarca</button>
					<a href="<?php echo base_url('ImportConsumptions/clean_import');?>" class="btn btn-danger" role="button" aria-pressed="true" style="margin-left:100px;">Sterge importul nefacturat!</a>
				</div>	
			</div>
		</div>
		<div class="row">
			<div class="col">
			</div>
			<div class="col">
			</div>
		</div>
	</div>
</form> 

<!--
<div style="padding: 20px 0 0 20px">
	Fisiere acceptate: (ENEL.xlsx) DOBROGEA xlsx/pdf, BANAT.xlsx/pdf, MUNTENIA SUD.xlsx/pdf, (DEER.xlsx) TRANSILVANIA NORD.xlsx/pdf, TRANSILVANIA SUD.xlsx/pdf,MUNTENIA NORD.xlsx/pdf, (DELGAZ.xlsx) MOLDOVA.xlsx/pdf, OLTENIA.xlsx/pdf
</div>	
-->

<script>


	/*function onSelect(e) {
		$("#grid").addClass("k-state-disabled");
		kendo.ui.progress($(document.body), true);
	}*/

	function onUpload(e) {
		$("#grid").addClass("k-state-disabled");
		kendo.ui.progress($(document.body), true);
	}
	
	function onComplete(e) {
		kendo.ui.progress($(document.body), false);
		$("#grid").removeClass("k-state-disabled");
		$("#stepper").data("kendoStepper").select(2);
		$("#grid").data("kendoGrid").dataSource.read();
	}
    
	function onSuccess(e) {
        if (e.operation == "upload") {
            for (var i = 0; i < e.files.length; i++) {
                var file = e.files[i].rawFile;

                if (file) {
                    var reader = new FileReader();

                    reader.onloadend = function () {
						//location.reload();
                        //$("#stepper").data("kendoStepper").next();
						$("#staticNotification").data("kendoNotification").show(e.response.msg,e.response.type);
                    };

                    reader.readAsDataURL(file);
                }
            }
        }
    }
	
	function onError(e)
	{
		onComplete(e);
	}
	
	function onStepper(e)
	{
		if (e == null) 
			step = $("#stepper").data("kendoStepper").selectedStep.options.index;
		else
			step = e.step.options.index;
		
		if (step == 0) //reset
		{		
			$(".k-grid-search > input").val(''); //reset grid search			
			$("#grid").data("kendoGrid").dataSource.filter([]);
			
		  var jqxhr = $.post( "<?php echo site_url().'/api?subject=import_consumptions&type=truncate'?>", function(data) {
			//alert( "success" );
			})
			.done(function(response) {
			//alert( "second success" );
			$("#staticNotification").data("kendoNotification").show(response.msg,"info");
			$("#grid").data("kendoGrid").dataSource.read();
			})
			.fail(function() {
			//alert( "Mai incercati odata" );
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
			})
			.always(function() {

			});
		}
		else if (step == 1) //load files
		{
			$("#files\\[\\]").click();
		}
		else if (step == 2) //check
		{
			
			// $("form.k-filter-menu button[type='reset']").trigger("click"); // clear filter
			$(".k-grid-search > input").val(''); //reset grid search			
			$("#grid").data("kendoGrid").dataSource.filter([]);
			 
			var jqxhr = $.ajax({
				url:"<?php echo site_url().'/api?subject=import_consumptions&type=review'?>",
				type:'POST',
				data: JSON.stringify({consumption_date:kendo.toString($("#DatePicker").data("kendoDatePicker").value(), "yyyy-MM-01")}),
				dataType: "json",
				contentType: "application/json; charset=utf-8",
				success: function(data) {/*alert( "success" );*/}
			})
			.done(function(response) {
			//alert( "second success" );
			if (response.msg > 0)
				$("#stepper").data("kendoStepper").selectedStep.options.label='Adauga ' + response.msg + ' POD-uri';
			else 
				$("#stepper").data("kendoStepper").selectedStep.options.label='Verifica consumuri';
			
			$("#stepper").data("kendoStepper").select(2);
			$("#grid").data("kendoGrid").dataSource.read();
			
			$("#staticNotification").data("kendoNotification").show(response.msg + ' POD-uri noi.',response.type);
			})
			.fail(function() {
			//alert( "Mai incercati odata" );
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
			})
			.always(function() {

			});
		}
		else if (step == 3) //save
		{
			var jqxhr = $.ajax({
				url:"<?php echo site_url().'/api?subject=import_consumptions&type=save_consumptions'?>",
				type:'POST',
				data: JSON.stringify({consumption_date:kendo.toString($("#DatePicker").data("kendoDatePicker").value(), "yyyy-MM-01")}),
				dataType: "json",
				contentType: "application/json; charset=utf-8",
				success: function(data) {/*alert( "success" );*/}
			})
			.done(function(response) {
			//alert( "second success" );
				$("#staticNotification").data("kendoNotification").show(response.msg,response.type);
				$("#grid").data("kendoGrid").dataSource.read();
				
				if ($("#grid").data("kendoGrid").dataSource.data().length > 0)
				{
					$("#stepper").data("kendoStepper").select(2);
					onStepper(null);
				}
			})
			.fail(function() {
			//alert( "Mai incercati odata" );
				$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
			})
			.always(function() {

			});
		}			
	}
	

	$( document ).ready(function() {
		$("#stepper").on('click', '.k-step', function(){
            	onStepper(null);
		})
	})
	
</script>

<?php if (session('msg') || $data['msg']) : ?>
        <div class="alert alert-info alert-dismissible">
            <?= session('msg') ?><?= $data['msg'] ?>
            <button type="button" class="close" data-dismiss="alert"><span>×</span></button>
        </div>
<?php endif ?>  