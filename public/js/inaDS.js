function inaDS(ds)
{
	//subject, custom = false, model = null, aggregate = null, sort = null, filter = null, transportCallback = null, distinct = null
	
	let url = "";
	if(ds.custom ?? false)
		url = window.location.origin + "/index.php/api?subject=custom&type=call&action="+ds.subject;
	else
	{
		url = window.location.origin + "/index.php/api?subject="+ds.subject+"&type=read";
		if(ds.distinct ?? false)
			url += "&action=field&option=distinct&field="+ds.distinct;
	}
	
	return {
		schema: {
			type:"json",						 
			model: (ds.model ?? null) != null ? ds.model : {
			  id: "id",
			  /*fields: {
				paymentDate : {
					type: "date",
					parse: function(value) {
						return new Date(value*1000);
					}
				 },
				 email_ts : {
					type: "date",
					parse: function(value) {
						if(value) return new Date(value*1000);
					}
				 },
				 paymentAmount : {
					type:"number",
					parse: function(value) {
						if(value == null) return 0;
						return parseFloat(value);
					}
				 },
			  }*/
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
			url: url,
			dataType: "json",
			type:"post",
			data: function() {
			
			if(typeof (ds.transportCallback ?? null) === 'function') 
				return ds.transportCallback();
			else
				return {			
						 models: [
						 {
							 
						 }
						]
				  }                  
			}           
		},
		destroy: 
		{
			url: window.location.origin + "/index.php/api?subject="+ds.subject+"&type=destroy",
			dataType: "json",
			type:"post"          
		},
		parameterMap: function (options, type) {
		  return kendo.stringify(options);
		}
	},
	serverFiltering: ds.serverFiltering ?? undefined,
	filter: ds.filter ?? undefined,
	aggregate: ds.aggregate ?? undefined, /*[
		{ field: "paymentAmount", aggregate: "sum"},
		{ field: "id", aggregate: "count"},
	]*/ 
	serverSorting: ds.serverSorting ?? false,
	sort: ds.sort ?? undefined, /*{ field: "paymentDate", dir: "desc" },*/
	group: ds.group ?? undefined,
	batch: true,
	pageSize: 10000
	};
}