function inaFilterButtons(subject, id, value=-1, label="",list=["Activ","Inactiv"],filterCallback=null,badgesOptions='',labelClass="minw-100px", allowDeselect=false)
{
	inaOptions[id]={
		subject:subject,
		id:id,
		label:label,
		value:value,
		filterCallback:filterCallback,
		badgesOptions:badgesOptions,
		list:list,
		allowDeselect:allowDeselect
	};
	
	let filterGroupId = "#"+id+"BG";

	//cleanup controls
	if($(filterGroupId).data("kendoButtonGroup") !== undefined)
	{
		$(filterGroupId).data("kendoButtonGroup").destroy(); //detach events
		$(filterGroupId).empty(); //remove the button group from the DOM
	}
	
	//cleanup wrapper
	$("#"+id+"_wrapper").empty();
	
	$("#"+id+"_wrapper").html(`
       <div class="d-flex">
            <div class="flex-fill align-self-center ${labelClass}">${label}</div>
            <div class="flex-fill align-self-center"><div id="${id}BG"></div></div>
        </div>
	`);
		
	list.forEach((element) => {let btnSpan = document.createElement('span'); btnSpan.innerHTML = element; $(filterGroupId).append(btnSpan);});
	inaOptions[id].selectedValue = value;
	$(filterGroupId).kendoButtonGroup({
		index: inaOptions[id].list.indexOf(inaOptions[id].value),
		select: function(e) {	
			if(inaOptions[id].allowDeselect && inaOptions[id].list.indexOf(inaOptions[id].selectedValue) == this.current().index())
			{
				this.selectedIndex = -1;inaOptions[id].selectedValue = -1;
				$(filterGroupId).find(".k-selected").removeClass("k-selected");
			}
			else
			{
				inaOptions[id].selectedValue = inaOptions[id].list[this.current().index()];
			}
			
			//updateDistributorDSFilter();
			if(filterCallback != null)
				filterCallback(id);
        },
		selection: "single"
	});
	
	//updateDistributorDSFilter();
	
	inaGetButtonFilterBadges(id);
	
	if(filterCallback != null)
		filterCallback(id);
}

function inaGetButtonFilterBadges(id)
{
	var data = {
			models: [
					 {
						subject : inaOptions[id].subject,
						field : id,
						option: inaOptions[id].badgesOptions
					 }
					]
			};
	
	customCall(data, 'getSubjectBadges', updateButtonFilterBadges, false, false);
}

function updateButtonFilterBadges(response)
{
	if(response.id === undefined || response.total == 0) return;
	let bgID = '#'+response.id+'BG';
	let bg = $(bgID).data('kendoButtonGroup');
	for(i=0; i<$(bgID).children().length; i++)
		if(bg.badge(i)>0)
			bg.badge(i,false);
	
	for(i=0; i<Math.max(response.data.length,inaOptions[response.id].list.length); i++)
		bg.badge(i,response.data[i].nr);
		//bg.badge(inaOptions[response.id].list.indexOf(parseInt(response.data[i][response.id])),response.data[i].nr);
}
