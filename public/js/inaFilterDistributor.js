function inaDistributorFilter(subject, value=-1, general=false,filterCallback=null,badgesOptions='')
{
	inaOptions['distributor_id']={
		subject:subject,
		id:"distributor_id",
		label:"Distribuitor",
		value:value,
		general:general,
		filterCallback:filterCallback,
		badgesOptions:badgesOptions,
		dsFilter:null,
		distributorIds:[3,2,6,4,5,1,8,7,0]
	};
	
	let distributorGroupId = "#distributor_idBG";

	//cleanup controls
	if($(distributorGroupId).data("kendoButtonGroup") !== undefined)
	{
		$(distributorGroupId).data("kendoButtonGroup").destroy(); //detach events
		$(distributorGroupId).empty(); //remove the button group from the DOM
	}
	
	//cleanup wrapper
	$("#distributor_id_wrapper").empty();
	
	//create html inside wrapper
	$("#distributor_id_wrapper").html(`
	<div class="container d-inline-flex p-0">
       <div class="d-flex">
            <div class="flex-fill align-self-center minw-100px">Distribuitor</div>
            <div class="flex-fill align-self-center minw-100px"><div id="distributor_idBG"></div></div>
        </div>
    </div>
	`);	
	
	['Transilvania Sud','Transilvania Nord','Muntenia Nord','Muntenia Sud','Banat','Dobrogea','Moldova','Oltenia'].forEach((element) => {let btnSpan = document.createElement('span'); btnSpan.innerHTML = element; $(distributorGroupId).append(btnSpan);});

	if(general)
		{let btnSpan = document.createElement('span'); btnSpan.innerHTML = 'General'; $(distributorGroupId).append(btnSpan);}
	
	inaOptions['distributor_id'].selectedDistributor = value;
	$(distributorGroupId).kendoButtonGroup({
		index: inaOptions['distributor_id'].distributorIds.indexOf(parseInt(inaOptions['distributor_id'].selectedDistributor)),
		select: function(e) {	
			
			if(inaOptions['distributor_id'].distributorIds.indexOf(parseInt(inaOptions['distributor_id'].selectedDistributor)) == this.current().index())
			{
				this.selectedIndex = -1;inaOptions['distributor_id'].selectedDistributor = -1;
				$(distributorGroupId).find(".k-selected").removeClass("k-selected");
			}
			else
			{
				inaOptions['distributor_id'].selectedDistributor = inaOptions['distributor_id'].distributorIds[this.current().index()];
			}
			
			updateDistributorDSFilter();
			if(filterCallback != null)
				filterCallback('distributor_id');
        },
		selection: "single"
	});
	
	updateDistributorDSFilter();
	
	inaGetDistributorBadges();
	
	if(filterCallback != null)
		filterCallback('distributor_id');
}

function inaGetDistributorBadges()
{
	var data = {
			models: [
					 {
						subject : inaOptions['distributor_id'].subject,
						field : 'distributor_id',
						option: inaOptions['distributor_id'].badgesOptions
					 }
					]
			};
	
	customCall(data, 'getSubjectBadges', updateDistributorBadges, false, false);
}

function updateDistributorBadges(response)
{
	if(response.id === undefined) return;
	
	let bg = $('#distributor_idBG').data('kendoButtonGroup');
	for(i=0; i<$('#distributor_idBG').children().length; i++)
		if(bg.badge(i)>0)
			bg.badge(i,false);
	
	for(i=0; i<response.data.length; i++)
		bg.badge(inaOptions['distributor_id'].distributorIds.indexOf(parseInt(response.data[i].distributor_id)),response.data[i].nr);
}

function updateDistributorDSFilter()
{

	let filters = [];
	
	if(inaOptions['distributor_id'].selectedDistributor == -1) 
		inaOptions['distributor_id'].dsFilter = [];
	else
	{	
		filters.push({
					  "field": 'distributor_id',
					  "operator": "eq",
					  "value": inaOptions['distributor_id'].selectedDistributor
					  });

		inaOptions['distributor_id'].dsFilter = {logic:"and",filters:filters};
	}
}

