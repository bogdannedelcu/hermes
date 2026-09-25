<div class="container ms-0">

 <div class="row py-1">

    <div class="col">
     <?php
		echo $data['period_filter'];
	  ?>
    </div>
</div>
<div class="row py-1">
	<div class="col-auto align-self-center minw-100px"></div>
	<div class="col">
	  <?php
		echo $data['counties'];
	  ?>
	<div class="dropZone d-none">	
			<!--<input style="margin: 20px 0 20px 0; padding: 50px 100px 50px 100px;box-shadow: 0px 0px 7px 3px rgba(0,0,0,0.10);" type="file" name="file[]" class="form-control" multiple id="file" onchange="readURL(this);" accept=".xlsx, .pdf" /> -->
			<?php echo $data['upload']; ?>
	</div>
	</div>
  </div>
  
</div>

<script>

 $( document ).ready(function() {
	
	$(".minw-120px").addClass("minw-100px").removeClass("minw-120px");
 
 });


	function onUpload(e) {
		$("#grid").addClass("k-state-disabled");
		kendo.ui.progress($(document.body), true);
	}
	
	function onComplete(e) {
		kendo.ui.progress($(document.body), false);
		$("#grid").removeClass("k-state-disabled");
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
                    
                }
            }
			
			if(e.response.msg.includes('duplicat'))
			{
				$("#staticNotification").data("kendoNotification").show(e.response.msg,'error');
			}
			else
				location.reload();
				
		}
    }
	
	function onError(e)
	{
		$("#staticNotification").data("kendoNotification").show('Eroare comunicare, mai incercati odata!','error');
	}

</script>