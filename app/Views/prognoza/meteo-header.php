	<button id="downloadMeteo"></button>
	<!--<button id="uploadMeteo"></button>-->
	<button id="saveSpreadsheet"></button>
	<button id="showToolbar"></button>

<script>	
$(document).ready(function () {
	
	function checkContainer () {
	  if ($('.k-spreadsheet-tabstrip').length > 0) { //if the container is visible on the page
		if(Cookies.get('showCard-' + document.title) != 'true')
		{
			$(".k-spreadsheet-quick-access-toolbar").toggleClass("d-none"); $(".k-spreadsheet-tabstrip").toggleClass("d-none");	
			$("#collapseCard > div > div:nth-child(4)").toggleClass("d-none");
		}
	  } else {
		setTimeout(checkContainer, 50); //wait 50 ms, then try again
		}
	}
	
	checkContainer();
	
	$("#downloadMeteo").kendoButton({
		icon: "k-icon k-i-download",
		click: function(e) {
			getMeteo();
		}
	});

	/*
	$("#uploadMeteo").kendoButton({
		icon: "k-icon k-i-upload",
		click: function(e) {
			uploadMeteo();
		}
	});*/

	$("#saveSpreadsheet").kendoButton({
		icon: "k-icon k-i-save",
		click: function(e) {
			$("button[title=\'Export...\']").click();
		}
	});
	
	$("#showToolbar").kendoButton({
		icon: "k-icon k-i-wrench",
		click: function(e) {
			$(".k-spreadsheet-quick-access-toolbar").toggleClass("d-none"); $(".k-spreadsheet-tabstrip").toggleClass("d-none");$("#collapseCard > div > div:nth-child(4)").toggleClass("d-none");resizeExcel();
			$(".k-spreadsheet-quick-access-toolbar").hasClass("d-none") == true ? Cookies.set("showCard-" + document.title,"false") : Cookies.set("showCard-" + document.title,"true"); 
		}
	});
});
</script>