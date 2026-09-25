var prevDestCustomerID = -1;
var prevSourceCustomerID = -1;
var maxColors = 100;
	
$(document).ready(function () {
	
	setTimeout(function () {
		if($('#select-customer').data('kendoDropDownList').value())
		{
			$("#synthetic_type").parent().removeClass("d-none");
			var dataSource = new kendo.data.DataSource({
			data: [
				{ id: 'suplimentar', text: "suplimentare"},
				{ id: 'absolut', text: "absolute"},
			  ],
			  sort: { field: "id", dir: "asc" }
			});
			
			$("#synthetic_type").kendoDropDownList({
					dataTextField: "text",
					dataValueField: "id",
					dataSource: dataSource
				});
		
		}
	},250);
	
});


function queryReport(e)
{
	window.location.href = location.protocol + "//" + location.host + location.pathname+"?date="+selYear_tp + "-"+(selMonth_tp+1)+"&customerID="+$('#select-customer').data('kendoDropDownList').value();
}

function synthGeneratorDialog()
{	
	$("body").append('<div id="dialog">');
		
	dialog = $('#dialog');

	dialog.kendoDialog({
		width: "450px",
		title: "Generează curbă sintetică",
		closable: true,
		modal: true,
		close: function(e){e.sender.destroy();},
		content: "<form id='curveSettingsForm'></form>",
		actions: [
			{ text: 'Anulare', 
			  action: function (e)
			  {
				  prevSourceCustomerID = $("#source_client").data("kendoDropDownList").value();
			      prevDestCustomerID = $("#dest_client").data("kendoDropDownList").value();
			  }
			},
			{
			  text: "Generează",
			  action: function(e){
				  // e.sender is a reference to the dialog widget object
				  // OK action was clicked

						
						if(!$("#source_client").data("kendoDropDownList").value()) 
						{ 
							$('#staticNotification').data('kendoNotification').show("Selectati clientul sursa...", 'error');
							return false;
						}
						
						if(!$("#dest_client").data("kendoDropDownList").value()) 
						{ 
							$('#staticNotification').data('kendoNotification').show("Selectati clientul destinatie...", 'error');
							return false;
						}
						
				  			
						if($("#toBeValue").data("kendoNumericTextBox").value() == null) 
						{ 
							$('#staticNotification').data('kendoNotification').show("Introduceti EA...", 'error');
							return false;
						}
						
						let srcMonths = calculateNoMonths($("#start_date").data("kendoDatePicker").value(), $("#stop_date").data("kendoDatePicker").value());
						let destMonths = calculateNoMonths($("#dest_start_date").data("kendoDatePicker").value(), $("#dest_stop_date").data("kendoDatePicker").value());
						if(srcMonths != destMonths) 
						{ 
							$('#staticNotification').data('kendoNotification').show("Lungimea perioadei sursa nu este egala cu lungimea perioadei destinatie...", 'error');
							return false;
						}
						
						
						prevSourceCustomerID = $("#source_client").data("kendoDropDownList").value();
						prevDestCustomerID = $("#dest_client").data("kendoDropDownList").value();
						
						var data = {
						models: [
								 {
									source_customer: $("#source_client").data("kendoDropDownList").value(),
									dest_customer: $("#dest_client").data("kendoDropDownList").value(),
									start_year: $("#start_date").data("kendoDatePicker").value().getFullYear(),
									start_month: $("#start_date").data("kendoDatePicker").value().getMonth()+1,
									stop_year: $("#stop_date").data("kendoDatePicker").value().getFullYear(),
									stop_month: $("#stop_date").data("kendoDatePicker").value().getMonth()+1,
									dest_start_year: $("#dest_start_date").data("kendoDatePicker").value().getFullYear(),
									dest_start_month: $("#dest_start_date").data("kendoDatePicker").value().getMonth()+1,
									dest_stop_year: $("#dest_stop_date").data("kendoDatePicker").value().getFullYear(),
									dest_stop_month: $("#dest_stop_date").data("kendoDatePicker").value().getMonth()+1,
									toBeValue:$("#toBeValue").data("kendoNumericTextBox").value(),
									valueType:$("#value_type").data("kendoSwitch").value() ? "EA" : "Percent",
									synthetic_type:$("#synthetic_type").data("kendoDropDownList").value(),
									day_correlation:$("#dayCorrelation").data("kendoSwitch").value()
								 }
								]
						};
					
						if($("#dest_client").data("kendoDropDownList").value() == $('#select-customer').data('kendoDropDownList').value() 
												&& $("#dest_start_date").data("kendoDatePicker").value().getMonth() == selMonth_tp
												&& $("#dest_start_date").data("kendoDatePicker").value().getFullYear() == selYear_tp)
							customCall(data,'generateCurveForCustomer',getSynthetics,true);
						else
							customCall(data,'generateCurveForCustomer',null,true);
				  
				  // Returning false will prevent the closing of the dialog
				  if($("#keepOpen").data("kendoSwitch").value())  return false;
				  else return true;
			  },
			  primary: true
			}
		]
	});
	
	
	let startDate = kendo.parseDate(selYear_tp + "-" + (selMonth_tp + 1) + "-01", "yyyy-MM-dd");
	let stopDate = kendo.date.lastDayOfMonth(startDate);
	
	const d = new Date();
	let maxYear = d.getFullYear()-1;
	let minYear = d.getFullYear()-1;
	if(d.getMonth() == 11) maxYear += 1;
	
	let destMinDate = new Date(minYear, 0, 1);
	let destMaxDate = new Date(maxYear, 11, 31);
	
	prevDestCustomerID = $('#select-customer').data('kendoDropDownList').value();
		
	$("#curveSettingsForm").kendoForm({
		buttonsTemplate:"",
		orientation: "horizontal",
		formData: {
			start_date:startDate,
			stop_date:stopDate,
			dest_start_date:startDate,
			dest_stop_date:stopDate,
			value_type: true,
			dialog_synthetic_type: true,
			dayCorrelation:true,
			keepOpen: false,
		},
		layout: "grid",
		items: [
			{
				type: "group",
				label: "Sursa Realizat",
				layout: "grid",
				grid: {
					cols: 2,
					gutter: 2
				},
				items: [
					{ 
						field: "start_date", 
						editor: "DatePicker", 
						label: "Start", 
						validation: { required: false }, 
						editorOptions: {
							depth: "year",
							start: "year",
							change:dateChanged,
							format: "MM-yyyy"
						},
						attributes: {
							//readonly:true
						}
					},
					{ 
						field: "stop_date", 
						editor: "DatePicker", 
						label: "Stop", 
						validation: { required: false }, 
						editorOptions: {
							depth: "year",
							start: "year",
							change:dateChanged,
							format: "MM-yyyy"
						},
						attributes: {
							//readonly:true
						}
					},
					{ 
						field: "source_client", 
						editor: "DropDownList", 
						label: "Client", 
						colSpan:2,
						validation: { required: false },
						hint: "Selectati clientul...",						
						editorOptions: {
							optionLabel: "Select...",
							filter: "contains",
							height:400,
							autoWidth:true,
							change: function(e){if(e.sender.value() && prevSourceCustomerID != -1) prevSourceCustomerID = -1;},
							dataSource: {
								schema: {
									type:"json",						 
									model: {
									  id: "customer_id"
									},
									data: "data",
									total:"total",
									data: function(response) {				 
										if(response.message !== undefined)
											kendo.alert("&#128711; " + response.message);
										if(response.error !== undefined)
											kendo.alert("&#128711; " + response.error);
										return response.data;
									}
								},
								transport: {
									read: 
									{
										url: window.location.origin + "/api?subject=custom&type=call&action=getCustomersInTPWithConsumptionTypes",
										dataType: "json",
										type:"post",
										data: function() {
											
											let m = selMonth_tp+1;
											let y = selYear_tp;
											if($("#start_date").length > 0 && $("#start_date").data("kendoDatePicker").value())
											{
												let d = $("#start_date").data("kendoDatePicker").value();
												m = d.getMonth() + 1;
												y = d.getFullYear();
											}
											return {
													models:[{month:m, year: y}]
											}                  
										}           
									},
									parameterMap: function (options, type) {
									  return kendo.stringify(options);
									}
								}, 
								serverFiltering: false,
								sort: { field: "customer_name", dir: "asc" },
								pageSize: 10000
							},
							dataTextField: "customer_name",
							dataValueField: "customer_id",
							dataBound: function(e){
													if( prevSourceCustomerID != -1 )
													{
														e.sender.value(prevSourceCustomerID);
														e.sender.trigger("change");
													}	
												}
						},
						attributes:{
							style: "width:300px;"
						}
					}
				]
			},
			{
				type: "group",
				label: "Destintatie Sintetice",
				layout: "grid",
				grid: {
					cols: 2,
					gutter: 2
				},
				items: [
					{ 
						field: "dest_start_date", 
						editor: "DatePicker", 
						label: "Start", 
						validation: { required: false }, 
						editorOptions: {
							depth: "year",
							start: "year",
							change:destDateChanged,
							format: "MM-yyyy",
							min: destMinDate,
							max: destMaxDate
						},
						attributes: {
							//readonly:true
						}
					},
					{ 
						field: "dest_stop_date", 
						editor: "DatePicker", 
						label: "Stop", 
						validation: { required: false }, 
						editorOptions: {
							depth: "year",
							start: "year",
							change:destDateChanged,
							format: "MM-yyyy",
							min: destMinDate,
							max: destMaxDate
						},
						attributes: {
							//readonly:true
						}
					},
					{ 
						field: "dest_client", 
						editor: "DropDownList", 
						label: "Client", 
						colSpan:2,
						validation: { required: false }, 
						editorOptions: {
							optionLabel: "Select...",
							filter: "contains",
							height:400,
							autoWidth:true,
							change: function(e){},
							dataSource: {
								schema: {
									type:"json",						 
									model: {
									  id: "customer_id"
									},
									data: "data",
									total:"total",
									data: function(response) {				 
										if(response.message !== undefined)
											kendo.alert("&#128711; " + response.message);
										if(response.error !== undefined)
											kendo.alert("&#128711; " + response.error);
										return response.data;
									}
								},
								transport: {
									read: 
									{
										url: window.location.origin + "/api?subject=customers&type=read&action=grid",
										dataType: "json",
										type:"post",
										data: function() {
											return {
													"filter": {
														"logic": "and",
														"filters": [
															{
																"field": "supplier_id",
																"operator": "eq",
																"value": supplierID
															}
														]
													}
											}                  
										}           
									},
									parameterMap: function (options, type) {
									  return kendo.stringify(options);
									}
								}, 
								serverFiltering: false,
								sort: { field: "customer_name", dir: "asc" },
								pageSize: 10000
							},
							dataTextField: "customer_name",
							dataValueField: "customer_id",
							dataBound: function(e){
									if(prevDestCustomerID != -1){
										e.sender.value(prevDestCustomerID);
										prevDestCustomerID = -1;
									}
									
									if(e.sender.dataSource.data().length == 0)
										$("#source_client-form-hint").text("Nu am gasit clienti, schimbati data start/stop");
									else
										$("#source_client-form-hint").text("");
									e.sender.trigger("change");
								}
						},
						attributes:{
							style: "width:300px;"
						}
					}
				]
			},
			{
				type: "group",
				label: "Energie Activa",
				layout: "grid",
				grid: {
					cols: 2,
					gutter: 2
				},
				items: [
					{ 
						field: "value_type", 
						editor: "Switch", 
						label: "Tip Valoare", 
						validation: { required: false }, 
						editorOptions: {
							messages: {
								checked: "EA",
								unchecked: "Procent"
							},
							width:90,
							change: value_typeChanged
							
						}
					}
					,
					{ 
						field: "toBeValue", 
						editor: "NumericTextBox", 
						label: "", 
						validation: { required: false }, 
						editorOptions: {
							format:"0.000",
							decimals:3
						}
					}
				]
			},
			{
				type: "group",
				label: "Optiuni",
				layout: "grid",
				items: [
						{ 
							field: "dayCorrelation", 
							label: "Coreleaza ziua saptamanii", 
							editor: "Switch", 
							validation: { required: false },
						}
					]
			},
			{
				type: "group",
				label: "",
				layout: "grid",
				items: [
						{ 
							field: "keepOpen", 
							label: "Pastreaza fereastra deschisa", 
							editor: "Switch", 
							validation: { required: false },
						}
					]
			}
		]
	});
	
	$("#start_date").click(function() {
		$("#start_date").data("kendoDatePicker").open();
	});

	$("#stop_date").click(function() {
		$("#stop_date").data("kendoDatePicker").open();
	});
	
	$("#start_date").attr("readonly", true);
	$("#stop_date").attr("readonly", true);
	
	dialog.data("kendoDialog").center();

}

function dateChanged(e)
{
	
	if(this.element.context.id == 'start_date')
	{
		if(e.sender.value() > $("#stop_date").data("kendoDatePicker").value() || $("#value_type").data("kendoSwitch").value())
			$("#stop_date").data("kendoDatePicker").value(e.sender.value());
		
		$("#source_client").data("kendoDropDownList").dataSource.read();
	}
	if(this.element.context.id == 'stop_date')
	{
		if(e.sender.value() < $("#start_date").data("kendoDatePicker").value() || $("#value_type").data("kendoSwitch").value())
		{
			$("#start_date").data("kendoDatePicker").value(e.sender.value());
			$("#source_client").data("kendoDropDownList").dataSource.read();
		}
	}
	
	if(this.element.context_id == 'value_type' && e.sender.value())
	{
		 $("#stop_date").data("kendoDatePicker").value($("#start_date").data("kendoDatePicker").value());
	}
	
	/*
	updateToBeValue($("#source_pod").data("kendoDropDownList").value(), $("#start_date").data("kendoDatePicker").value());
	*/
}

function destDateChanged(e)
{
	
	if(this.element.context.id == 'dest_start_date')
	{
		if(e.sender.value() > $("#dest_stop_date").data("kendoDatePicker").value() || $("#value_type").data("kendoSwitch").value())
			$("#dest_stop_date").data("kendoDatePicker").value(e.sender.value());
		
//		$("#source_client").data("kendoDropDownList").dataSource.read();
	}
	if(this.element.context.id == 'dest_stop_date')
	{
		if(e.sender.value() < $("#dest_start_date").data("kendoDatePicker").value() || $("#value_type").data("kendoSwitch").value())
		{
			$("#dest_start_date").data("kendoDatePicker").value(e.sender.value());
//			$("#source_client").data("kendoDropDownList").dataSource.read();
		}
	}
	
	if(this.element.context_id == 'value_type' && e.sender.value())
	{
		 $("#dest_stop_date").data("kendoDatePicker").value($("#dest_start_date").data("kendoDatePicker").value());
	}
	
}

function value_typeChanged(e)
{
	
	if(e.checked)
	{
		$("#toBeValue").data("kendoNumericTextBox").setOptions({format: "0.000", decimals: 3 });
		$("#stop_date").data("kendoDatePicker").value($("#start_date").data("kendoDatePicker").value());
		//updateToBeValue($("#source_pod").data("kendoDropDownList").value(), $("#start_date").data("kendoDatePicker").value());
		$("fieldset:nth-child(3) .k-switch-track").css("background","#428bca").css("color","white");
		$("#stop_date").data("kendoDatePicker").value($("#start_date").data("kendoDatePicker").value());
	}
	else
	{
		$("#toBeValue").data("kendoNumericTextBox").setOptions({format: "0.00", decimals: 2 });
		$("#toBeValue").data("kendoNumericTextBox").value(100);
		$("fieldset:nth-child(3) .k-switch-track").css("background","#d9534f").css("color","white");
	}
	
}


function getSynthetics()
{

	var data = {
			models: [
					 {
						customer_id :$('#select-customer').data('kendoDropDownList').value(),
						year : selMonth_tp+1,
						month: selYear_tp
					 }
					]
			};
	
	customCall(data,'getSynthetics',syntheticsReceived);
	
}

function calculateNoMonths(startDate, stopDate)
{
	startYear = startDate.getFullYear();
	stopYear = stopDate.getFullYear();
	startMonth = startDate.getMonth();
	stopMonth = stopDate.getMonth();
	noMonths = 0;
	
	while(startMonth != stopMonth || startYear != stopYear)
	{
		if(startMonth == 11) {startMonth = 0;startYear++;}
		else startMonth++;
		
		noMonths++;
	}

	return noMonths;
}

function ntoe(num)
{
	let d =  Math.floor(num/27);
	let c = num%27;
	
	if(d>0)
		return String.fromCharCode(d+64,c+65);
	
	return String.fromCharCode(c+64);
}

function daysInMonth(date) {
  
  return parseInt(kendo.toString(kendo.date.lastDayOfMonth(date),"dd"));
}

function computeColor(value)
{
	if(isNaN(value)) return '#ffffff';
	if(value<vmin) value = vmin;
	if(value>vmax) value = vmax;
	
	let v= 255-Math.floor(255/maxColors) * Math.floor(colorCoef * (value-vmin));
	let h='0';
	
	if(v<16) h = h+ v.toString(16);
	else h = v.toString(16);
	//console.log('#' + h + h);
	return '#ff' + h + h ;
}

function syntheticsReceived(response)
{
	var sheet = $("#spreadsheet").data("kendoSpreadsheet").activeSheet(); 
	let now = new Date();
		
	if(selYear_tp != -1 && selMonth_tp != -1)
		now = new Date(selYear_tp,selMonth_tp);
	else
		now.setDate(0);

	numDays = daysInMonth(now);
	
	dataArray=[];
	let rIdx=0;
	vmin = 10000000;
	vmax = -10000000;
	
	for(let h=0;h<24;h++)
	{
		dataArray.push([]);
		for(let d=1;d<=numDays;d++)
		{
			//let dt = kendo.parseDate(response[rIdx].synthetics_datetime, "yyyy-MM-dd HH:mm:ss"); //Fri Dec 22 2000
			//if(response[rIdx] !== undefined && dt.getHours() == h && dt.getDate() == d)
			if(response[rIdx] !== undefined)
			{
				dD = parseInt(response[rIdx].synthetics_datetime.substring(8,10));
				dH = parseInt(response[rIdx].synthetics_datetime.substring(11,13));
			
				if(dH == h && dD == d)
				{
					let fea = '';
					if(response[rIdx].synthetic_ea != null)
					{
						fea = parseFloat(response[rIdx].synthetic_ea);
						if(vmin>fea) vmin = fea;
						if(vmax<fea) vmax = fea;
					}
					dataArray[h].push(fea);
					rIdx++
				}
				else
					dataArray[h].push('');
			}
			else
				dataArray[h].push('');
		}
	}

	colorCoef = maxColors / ((vmax-vmin) == 0 ? 0.000001 : (vmax-vmin));
	
	
	let rangeMax = "AF27";
	
	switch(numDays) {
	  case 31:
		rangeMax = "AF27";
		break;
	  case 30:
		rangeMax = "AE27";
		break;
	  case 29:
		rangeMax = "AD27";
		break;
	  case 28:
		rangeMax = "AC27";
		break;
	}	
	
	sheet.range("B4:"+rangeMax).values(dataArray);
	var valRange = sheet.range("B4:"+rangeMax).format("0.000").textAlign("center");
	
	//showEnergyScale();		

	let colorRanges=[];
	for(let h=0;h<24;h++)
	{
		for(let d=1;d<=numDays;d++)
		{
			let s = '';
			if(dataArray[h][d-1]!= '')
			{	 
				let color = computeColor(dataArray[h][d-1]);
				if(colorRanges[color] !== undefined)
					s = colorRanges[color];
				
				
				colorRanges[color] = s.concat(ntoe(d+1)+(h+4)+",");
			}
			else
			{
				if(colorRanges['#ffffff'] !== undefined)
					s = colorRanges['#ffffff'];
				
				colorRanges['#ffffff'] = s.concat(ntoe(d+1)+(h+4)+",");
			}
		}
	}
	
	sheet.batch(function() {
		Object.keys(colorRanges).forEach(key => {
		  sheet.range(colorRanges[key].slice(0, -1)).background(key);
		});		
	});	

}

function uploadSynthetics()
{
	if($('#select-customer').data('kendoDropDownList').value() == '')
	{
		kendo.alert("&#x26A0; Selectati un client!");
		return false;
	}
	
	var spreadsheet = $("#spreadsheet").data("kendoSpreadsheet");
	var sheet = spreadsheet.activeSheet();

	let maxDays = new Date(selYear_tp, selMonth_tp+1, 0).getDate();
	let rangeMax = "AF27";
	
	switch(maxDays) {
	  case 31:
		rangeMax = "AF27";
		break;
	  case 30:
		rangeMax = "AE27";
		break;
	  case 29:
		rangeMax = "AD27";
		break;
	  case 28:
		rangeMax = "AC27";
		break;
	}	
	
	let values = sheet.range("B4:" + rangeMax).values();
	
	let hasData = false;
	let doesNotHaveData = false;

	for(let a=0;a<values.length;a++)
	{
		v = values[a];
		for(let b=0;b<v.length;b++)
		{
			if(v[b] == null ||  v[b].toString().trim() == '')
			{
				doesNotHaveData = true;
			}
			else
				hasData = true;
		}
	}
	
	if(doesNotHaveData && hasData)
	{
		kendo.alert("&#x26A0; Lipsesc date!");
		return false;
	}
	
	if(rangeMax!='AF27')
	{
		let extraValues = sheet.range(rangeMax.slice(0,2) +'4:AF27').values();
		for(a=0;a<extraValues.length;a++)
		{
			v = extraValues[a];
			for(b=1;b<v.length;b++)
			{
				if(v[b] != null && v[b].toString().trim() != '')
				{
					kendo.alert("&#x26A0; Prea multe date!");
					return false;
				}
			}
		}
	}
	
	var data = {
			models: [
					 {
						synthetic_type :$('#synthetic_type').data('kendoDropDownList').value(),
						customer_id :$('#select-customer').data('kendoDropDownList').value(),
						YMdate : selYear_tp + "-"+(selMonth_tp+1),
						values:values
					 }
					]
			};
			
	var jqxhr = $.post({
					url: window.location.origin+'/api?subject=custom&type=call&action=uploadSynthetics',
					data: JSON.stringify(data),
					contentType: 'application/json; charset=utf-8'})
			.done(function(response) {
				if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
				
					$('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');
					return false;
				}
				else
				{
					$('#staticNotification').data('kendoNotification').show(response, 'info');
				}
			})
			.fail(function() {
				
				kendo.ui.progress($(document.body), false);
				
				//probleme de retea
				$('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
				return false;
			});
	
}