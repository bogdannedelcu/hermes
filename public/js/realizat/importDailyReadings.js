$(document).ready(function () {
	
	let gridColumns = inaGridColumns(false, null, ["distributor_name","customer_name","pod", "county", "city", "address", "interval_min","interval_max", "ea", "error"], 
										   titles=["Distribuitor","Client","POD", "Judet", "Oras", "Adresa", "Interval Start","Interval Stop", "EA(MWh)", "Eroare"], 
										   types=["string","string","string", "string", "string", "string", "string", "string", "number", "string"]);
	
	gridColumns[8].template="#= kendo.toString(parseFloat(ea), 'n3') #";
	gridColumns[9].template="#= (data.error =='POD Nou') ? ('<button class=\"k-button k-button-solid-primary k-rounded-md\" onClick=createPODFromDailyReadings('+data.id+');>Pod Nou</button>') : data.error ? data.error : '' #";
	
	let gridDS = inaDS({subject:"validateDailyReadings", custom:true});
	inaGrid("import_daily_readings", 'grid', gridDS, gridColumns);
	
	$("#stepper").kendoStepper({
		linear:false,
		steps: [{
			label: "Reset",
			icon:"trash"
		},{
			label: "Incarca fisiere",
			icon:"attachment"
		},{
			label: "Verifica",
			icon:"preview",
			selected: true
		},{
			label: "Salveaza Realizat Zilnic",
			icon: "file-add"
		}]
	});
	
	if($("#files").data('kendoUpload') !== undefined)
	{
		$("#files").data('kendoUpload').destroy();
		$("#files").empty();
	}
			
	$("#files").attr("accept",".csv, .xlsx");
			
	$("#files").kendoUpload({
		async: {
				saveUrl: window.location.origin+window.location.pathname+'/upload',
				autoUpload: true
				},
		upload: onUpload,
		success: onSuccess,
		complete: onComplete,
		multiple:true,
		validation: {
			allowedExtensions: [".csv",".xlsx"],
			//maxFileSize: 10485760 //10MB
		}
	});
		
			
	$("#stepper").on('click', '.k-step', function(){
			onStepper(null);
	});
	
	refreshGrid();
});


function refreshGrid()
{
	// $("form.k-filter-menu button[type='reset']").trigger("click"); // clear filter
	$(".k-grid-search > input").val(''); //reset grid search			
	$("#grid").data("kendoGrid").dataSource.filter([]);
	$("#grid").data("kendoGrid").dataSource.read();
}

/*	function onSelect(e) {
	$("#grid").addClass("k-state-disabled");
	kendo.ui.progress($(document.body), true);
}*/

function onUpload(e) {
	kendo.ui.progress($(document.body), true);
}

function onComplete(e) {
	kendo.ui.progress($(document.body), false);
	$("#stepper").data("kendoStepper").select(2);
	onStepper();
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
	$("#stepper").data("kendoStepper").select(2);
}

function onStepper(e)
{
	if (e == null) 
		step = $("#stepper").data("kendoStepper").selectedStep.options.index;
	else
		step = e.step.options.index;
	
	if (step == 0) //reset
	{		
		var data = {
			models: [
					 {
						/*subject : inaOptions[id].subject,
						field : id,
						year: inaOptions[id].selectedYear,
						option: inaOptions[id].badgesCallback ? inaOptions[id].badgesCallback(id) : ''*/
					 }
					]
			};
	
		customCall(data, 'resetImportDailyReadings', refreshGrid, false, false);
	}
	else if (step == 1) //load files
	{
		$("#files").click();
	}
	else if (step == 2) //check
	{
		refreshGrid();
	}
	else if (step == 3) //save
	{
		kendo.ui.progress($(document.body), true);
		
		var data = {
		models: [
				 {
					/*subject : inaOptions[id].subject,
					field : id,
					year: inaOptions[id].selectedYear,
					option: inaOptions[id].badgesCallback ? inaOptions[id].badgesCallback(id) : ''*/
				 }
				]
		};
	
		customCall(data, 'saveDailyReadings', refreshGrid, false, false);
	
	}			
}
//pod, distributor_id,county, city, address
function createPODFromDailyReadings(id)
{
	var dataItem = $("#grid").data("kendoGrid").dataSource.get(id);
	//kendo.confirm(`Adauga POD nou in Master Data: ${dataItem.pod}: ${dataItem.county} ${dataItem.city} ${dataItem.address}`);
	
	createPODDialog({pod:dataItem.pod, customer_id:null, zone_id:null, pod_type:'comercial', county:dataItem.county, city:dataItem.city, address:dataItem.address, pod_status:'Activ'});
}
