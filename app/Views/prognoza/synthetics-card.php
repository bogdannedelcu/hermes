<div class="container ms-0">

 <div class="row py-1">

    <div class="col">
     <?php
		echo $data['period_filter'];
	  ?>
    </div>
	
	<div class="col-auto align-left maxw-100px">
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

</script>