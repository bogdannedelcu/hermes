//applied in:https://ebs.cloudromania.ro/index.php/ebsMD/working_free_days

function inaPeriodFilter(subject,id,label,value,yearMin,yearMax,filterCallback=null,badgesCallback=null,allowDeselect = false)
{
	inaOptions[id]={
		subject:subject,
		id:id,
		label:label,
		value:value,
		yearMin:yearMin,
		yearMax:yearMax,
		filterCallback:filterCallback,
		badgesCallback:badgesCallback,
		allowDeselect:allowDeselect,
		dsFilter:null,
	};
	
	let yearsGroupId = "#"+id+"BGYears";
	let monthsGroupId = "#"+id+"BGMonths";
	
	//cleanup controls
	if($(yearsGroupId).data("kendoButtonGroup") !== undefined)
	{
		$(yearsGroupId).data("kendoButtonGroup").destroy(); //detach events
		$(yearsGroupId).empty(); //remove the button group from the DOM
	}

	if($(monthsGroupId).data("kendoButtonGroup") !== undefined)
	{
		$(monthsGroupId).data("kendoButtonGroup").destroy(); //detach events
		$(monthsGroupId).empty(); //remove the button group from the DOM
	}
	
	//cleanup wrapper
	$("#"+id+"_wrapper").empty();
	
	//create html inside wrapper
	$("#"+id+"_wrapper").html(`
	<div class="col-auto align-self-center minw-100px">
		${label}
	</div>
		
	<div class="col-auto align-self-center">
		<div id="${id}BGMonths"></div>
		<div id="${id}BGYears" class="ms-4"></div>
	</div>`);
	
	
	['IAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'].forEach((element) => {let btnSpan = document.createElement('span'); btnSpan.innerHTML = element; $(monthsGroupId).append(btnSpan);});
	Array.from({length:yearMax-yearMin+1},(v,k)=>k+yearMin).forEach((element) => {let btnSpan = document.createElement('span'); btnSpan.innerHTML = element; $(yearsGroupId).append(btnSpan);});
	
	inaOptions[id].selectedMonth = value.getMonth()+1;
	$(monthsGroupId).kendoButtonGroup({
		index: inaOptions[id].selectedMonth-1,
		select: function(e) {	

			let refresh = true;
			if(inaOptions[id].selectedMonth == this.current().index()+1)
			{
				if(inaOptions[id].allowDeselect)
				{
					this.selectedIndex = -1;inaOptions[id].selectedMonth = -1;
					$("#"+id+"BGMonths").find(".k-selected").removeClass("k-selected");					
				}
				else
					refresh  = false;
			}
			else
			{
				inaOptions[id].selectedMonth = this.current().index()+1;
				
				if(inaOptions[id].selectedYear == -1)
				{
					$("#"+id+"BGYears").data("kendoButtonGroup").select(0);
					inaOptions[id].selectedYear = $("#"+id+"BGYears").data("kendoButtonGroup").current().text();
				}
			}
			
			if(refresh)
			{
				updatePeriodDSFilter(id);
				if(filterCallback != null)
					filterCallback(id);		
			}		
        },
		selection: "single"
	});
	
	inaOptions[id].selectedYear =  value.getFullYear();
	$(yearsGroupId).kendoButtonGroup({
		index: inaOptions[id].selectedYear-yearMin,
		select: function(e) {
			
			let refresh = true;
			if(inaOptions[id].selectedYear == this.current().text())
			{
				if(inaOptions[id].allowDeselect)
				{
					this.selectedIndex = -1;inaOptions[id].selectedYear = -1;
					$("#"+id+"BGYears").find(".k-selected").removeClass("k-selected");
					
					//deselect month
					inaOptions[id].selectedMonth = -1;
					$("#"+id+"BGMonths").data("kendoButtonGroup").selectedIndex = -1;
					$("#"+id+"BGMonths").find(".k-selected").removeClass("k-selected");
				}
				else
					refresh = false;
			}
			else
				inaOptions[id].selectedYear = this.current().text();
			
			if(refresh)
			{
				updatePeriodDSFilter(id);
				inaGetPeriodMonthlyBadges(id);
				if(filterCallback != null)
					filterCallback(id);		
			}			
        },
		selection: "single"
	});
	
	updatePeriodDSFilter(id);
	
	if(badgesCallback == null)
		inaGetPeriodMonthlyBadges(id);
	
	if(filterCallback != null)
		filterCallback(id);
}

function inaGetPeriodMonthlyBadges(id)
{
	var data = {
			models: [
					 {
						subject : inaOptions[id].subject,
						field : id,
						year: inaOptions[id].selectedYear,
						option: inaOptions[id].badgesCallback ? inaOptions[id].badgesCallback(id) : ''
					 }
					]
			};
	
	customCall(data, 'getMonthlyBadges', updatePeriodMonthlyBadges, false, false);
}

function updatePeriodMonthlyBadges(response)
{
	if(response.id === undefined || response.data.length == 0) return;
	
	let bgMonths = $('#'+response.id+'BGMonths').data('kendoButtonGroup');
	for(i=0; i<12; i++)
		if(bgMonths.badge(i)>0)
			bgMonths.badge(i,false);
	
	for(i=0; i<response.data.length; i++)
		bgMonths.badge(response.data[i].month-1,response.data[i].nr);
}

function updatePeriodDSFilter(id)
{
	let yearsGroupId = "#"+id+"BGYears";
	let monthsGroupId = "#"+id+"BGMonths";
	
	let selYear = inaOptions[id].selectedYear;
	let selMonth = inaOptions[id].selectedMonth;

	let daysInMonth = 31;
	let minDate = '2000-1-1';
	let maxDate = '2100-12-31';
	if(selYear != -1 && selMonth != -1)
	{
		daysInMonth = new Date(selYear, selMonth, 0).getDate();
		minDate = selYear+"-"+selMonth+"-1";
		maxDate = selYear+"-"+selMonth+"-"+daysInMonth;
	}
	
	if(selMonth == -1 && selYear != -1)
	{
		minDate = selYear+"-1-1";
		maxDate = selYear+"-12-31";
	}
	
	let filters = [];
	
//	$("#grid").data("kendoGrid").dataSource.filter().filters.forEach(filter => {if(filter.field != id) filters.push(filter)});
		
	filters.push({
					  "field": id,
					  "operator": "gte",
					  "value": minDate
					  });
					  
	filters.push({
					  "field": id,
					  "operator": "lte",
					  "value": maxDate
					});

	inaOptions[id].dsFilter = {logic:"and",filters:filters};

//	$("#grid").data("kendoGrid").dataSource.filter({filters:filters, logic: "and"});
}

