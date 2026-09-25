<?php

$datePicker = new \Kendo\UI\DatePicker('DatePicker');
$datePicker->start('year');
$datePicker->depth('year');
$datePicker->format('MM-yyyy');
$datePicker->change('changeActualDataDate');

if(!empty($_SESSION['consumptions-date']))
	$datePicker->value($_SESSION['consumptions-date']);
else 
	$datePicker->value(date("m-Y", strtotime("-1 months")));


?>

<div class="container">
  <div class="row align-items-start">
    <div class="col-auto align-self-center">
      <?php echo $datePicker->render();?>
    </div>
    <div class="col align-self-center">
      <button class="k-button  k-button-rectangle k-rounded-md k-button-solid-error k-button-solid-base text-nowrap d-none" onclick="changeActualDataDate()"><i class="fa-solid fa-file-invoice"></i> Schimba Data</button>
    </div>
    <div class="col align-self-center">
      <button class="k-button  k-button-rectangle k-rounded-md k-button-solid-primary k-button-solid-base text-nowrap d-none" onclick="Button2()"><i class="fa-solid fa-trash-can"></i>Button</button>
    </div>
  </div>
</div>

<script>
function changeActualDataDate(item) {

	kendo.ui.progress($(document.body), true);
	var data = {
		models: [
				 {
					sessionVar :'consumptions-date',
					value : kendo.toString($("#DatePicker").data("kendoDatePicker").value(), "MM") + "-" + kendo.toString($("#DatePicker").data("kendoDatePicker").value(), "yyyy")
				 }
				]
	};

	var jqxhr = $.post({
			url: window.location.origin+"/api?subject=custom&type=call&action=setSessionVar",
			data: JSON.stringify(data),
			contentType: "application/json; charset=utf-8"})
	.done(function(response) {
		if (typeof response !== "undefined" && typeof response.errors !== "undefined") {
		
			$("#staticNotification").data("kendoNotification").show(response.errors[0], "error");
			return false;
		}
		else
		{

			$("#grid").data("kendoGrid").dataSource.read();
		
		}
	})
	.fail(function() {
			
		//probleme de retea
		$("#staticNotification").data("kendoNotification").show("Eroare, mai incercati odata.","error");
		return false;
	})
	.always(function() {
		kendo.ui.progress($(document.body), false);
	})
	;

}
</script>