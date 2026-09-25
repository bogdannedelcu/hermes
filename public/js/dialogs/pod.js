function createPODDialog(pod)
{
	//pod = null, customer_id=null, zone_id=null, pod_type='comercial', county = null, city = null, address = null, pod_status = 'Activ'
	initalPodData = pod;
	
	let dialog = $("#dialog");

	if(dialog.length == 0)
	{
		dialog = $('<div id="dialog" />').appendTo('body');
		
		$('<form id="podForm" />').appendTo('body');
		 
		dialog.kendoDialog({
			width: "500px",
			title: (pod.pod_id ?? null) ? "Editează POD" : "Creaza POD Nou",
			closable: true,
			modal: true,
			close: function(e) {
				e.sender.destroy();
			},
			content: $("#podForm").kendoForm({
					orientation: "horizontal",
					formData: {
						pod_id:pod.pod_id ?? null,
						customer_id:pod.customer_id ?? null,
						zone_id:pod.zone_id ?? null,
						pod_no:pod.pod ?? '',
						pod_type:pod.pod_type ?? 'comercial',
						county:pod.county ?? null,
						city:pod.city ?? null,
						address:pod.address ?? null,
						pod_status:(pod.pod_status ?? null) == 'Activ'
					},
					change:podFormChanged,
					items: [
					{
						field: "pod_id",
						editor: "hidden", // Set the type to hidden
						value: pod.pod_id ?? null
					},
					{
						field: "customer_id",
						label: "Client",
						editor:"DropDownList",							
						 attributes:{
							//class: "w-100px"
							//style:"width:450px;"
						},
						editorOptions:{
							fillMode: "flat",
							dataTextField: "customer_name",
							dataValueField: "customer_id",	
							optionLabel: 'Selectează Client...',
							filter: "contains",
							dataSource: inaDS({subject:"customers", model:{id:"customer_id"}, filter:{ field: "customer_status", operator: "eq", value: "Activ"} }),
							height:300
						}
					},
					{
						field: "zone_id",
						label: "Lot Client",
						editor:"DropDownList",							
						editorOptions:{
							fillMode: "flat",
							dataTextField: "zone_name",
							dataValueField: "zone_id",	
							cascadeFrom: "customer_id",
							dataSource: inaDS({subject:"zones", model:{id:"zone_id"}})
						}
					},
					{
						field: "pod_no",
						label: "POD",
						editor:"TextBox",
						 attributes:{
							//class: "w-100px"
							//style:"width:250px;"
						},
						editorOptions:{
							fillMode: "flat",
						}
					},
					{
						field: "pod_type",
						label: "Tip POD",
						editor:"DropDownList",							
						editorOptions:{
							fillMode: "flat",
							dataSource: ["comercial","necomercial"]
						}
					},
					{
						field: "county",
						label: "Judet",
						editor:"AutoComplete",							
						editorOptions:{
							fillMode: "flat",
							dataTextField: "county",
							dataValueField: "county_id",	
							placeholder: 'Caută Județ...',
							filter: "contains",
							dataSource: inaDS({subject:"counties", model:{id:"county_id"}})
						}
					},
					{
						field: "city",
						label: "Localitate",
						editor:"AutoComplete",							
						editorOptions:{
							fillMode: "flat",
							dataTextField: "city",
							dataValueField: "city",	
							placeholder: 'Caută Localitate...',
							filter: "contains",
							dataSource: inaDS({subject:"pods", model:{id:"city"},distinct:"city"})
						}
					},
					{
						field: "address",
						label: "Adresa",
						editor:"AutoComplete",							
						editorOptions:{
							fillMode: "flat",
							dataTextField: "address",
							dataValueField: "address",	
							placeholder: 'Caută Județ...',
							filter: "contains",
							dataSource: inaDS({subject:"pods", model:{id:"address"},distinct:"address"})
						}
					},
					{
						field: "pod_status",
						label: "Status",
						editor:"Switch",							
						editorOptions:{
							fillMode: "flat",
							dataSource: ['Activ','Inactiv'],
							messages: {	
										checked:'Activ',
										unchecked:'Inactiv'
									  },
							width:100
						}
					}
					],
					buttonsTemplate: ""
				}),
			actions: [
				{ text: 'Anuleaza', action: function(e){
					//$("#selectWFDProfile").data("kendoDropDownList").value(prevProfileID);
				}},
				{ text: (pod.pod_id ?? null) ? "Salvează" : 'Adaugă' , cssClass: "saveProfileButton", primary: true , action: savePOD}
			]
		});
		
		//$("#profileName").data("kendoTextBox").focus(); 
	}
}

function podFormChanged(e)
{
	if(e.field == "customer_id"){

		//let dataItem = $("#customer_id").data("kendoDropDownList").dataSource.get(e.value);
		
		if($("#county").data("kendoAutoComplete").value() == "")
			$("#county").data("kendoAutoComplete").value(e.value.customer_county);
		
		if($("#city").data("kendoAutoComplete").value() == "")
			$("#city").data("kendoAutoComplete").value(e.value.customer_city);
				
		if($("#address").data("kendoAutoComplete").value() == "")
			$("#address").data("kendoAutoComplete").value(e.value.customer_address);
		
	}	
}

function savePOD()
{
	
	if($("#pod_no").data("kendoTextBox").value() == "")
	{
		$("#staticNotification").data("kendoNotification").show("Este POD-ul definit ?", "error");
		return false;
	}
	else if($("#customer_id").data("kendoDropDownList").value() == "")
	{
		$("#staticNotification").data("kendoNotification").show("Ați ales Client ?", "error");
		return false;
	}
	else if($("#zone_id").data("kendoDropDownList").value() == "")
	{
		$("#staticNotification").data("kendoNotification").show("Ați ales Lot Client ?", "error");
		return false;
	}	
	else if($("#county").data("kendoAutoComplete").value() == "" || $("#city").data("kendoAutoComplete").value() == "" || $("#address").data("kendoAutoComplete").value() == "")
	{
		$("#staticNotification").data("kendoNotification").show("Ați introdus Județ, Localitate, Adresă?", "error");
		return false;
	}	
	//use grid model
	var data = {
		pod_id: $("#pod_id").val(),
		customer_id :$("#customer_id").data("kendoDropDownList").value(),
		pod_no:$("#pod_no").data("kendoTextBox").value(),
		zone_id:$("#zone_id").data("kendoDropDownList").value(),
		pod_type:$("#pod_type").val(),
		county: $("#county").data("kendoAutoComplete").value(),
		city: $("#city").data("kendoAutoComplete").value(),
		address: $("#address").data("kendoAutoComplete").value(),
		pod_status: $("#pod_status").data("kendoSwitch").value() ? "Activ" : "Inactiv"
	};
	
	if ( data.pod_id != '') 
		apiCallUpdate(data, "pods", refreshGrid);
	else
		apiCallCreate(data, "pods", refreshGrid);
	
	return true;
}