var inaOptions=[];

const coduriJudete = ['RO', 'AB', 'AG', 'AR', 'B', 'BC', 'BH', 'BN', 'BR', 'BT', 'BV', 'BZ', 'CJ', 'CL', 'CS', 'CT', 'CV', 'DB', 'DJ', 'GJ', 'GL', 'GR', 'HD', 'HR', 'IF', 'IL', 'IS', 'MH', 'MM', 'MS', 'NT', 'OT', 'PH', 'SB', 'SJ', 'SM', 'SV', 'TL', 'TM', 'TR', 'VL', 'VN', 'VS'];

function translateDate(date)
{
	if(date.includes('Monday')) return date.replace('Monday','Luni');
	if(date.includes('Tuesday')) return date.replace('Tuesday','Marti');
	if(date.includes('Wednesday')) return date.replace('Wednesday','Miercuri');
	if(date.includes('Thursday')) return date.replace('Thursday','Joi');
	if(date.includes('Friday')) return date.replace('Friday','Vineri');
	if(date.includes('Saturday')) return date.replace('Saturday','Sambata');
	if(date.includes('Sunday')) return date.replace('Sunday','Duminica');
}

function dataSource(subject, fields, field = null)
{
	var sourceOption = '';
	if(field != null)
	{
		sourceOption = '&action=field&option=distinct&field='+field;
	}

	var dataSource = new kendo.data.DataSource({
			batch: true,
			transport: {
				read:  {
					url: window.location.origin + "/api?type=read&subject="+subject+sourceOption,
					type: "POST"
				},
				create: {
					url: window.location.origin + "/api?type=create&subject="+subject,
					type: "POST"
				},
				parameterMap: function(options, operation) {
					if (operation !== "read" && options.models) {
						return {models: kendo.stringify(options.models)};
					}
				}
			},
			schema: {
				data:'data',
				errors:'errors',
				groups:'groups',
				total:'total',
				aggregates:'aggregates',
				model: {
					id:  Object.keys(fields)[0],
					fields: fields
				}
			},
			serverFiltering:true
		}); 
	
	return dataSource;
}


function getMinMaxYears(subject, field, onSentData)
{
	let data = {
			models: [
					 {
						subject : subject,
						field:  field
					 }
					]
			};
			
	customCall(data, "getMinMaxYears", onSentData);
}

function customCall(data, action, onSentData = null, block = false, message = true)
{
	sendData(data,"/api?subject=custom&type=call&action="+action,onSentData, block, message);
}

function apiCallRead(data = null, subject, onSentData = null, block = false, message = true)
{
	if(data == null)
	{
		data = {
				"take": 1000,
				"skip": 0,
				"page": 1,
				"pageSize": 1000,
				"filter": {
					"logic": "and",
					"filters": [
						{
							"field": "supplier_id",
							"operator": "eq",
							"value": supplierID
						}
					]
				},
				"group": []
				};
	}
	
	apiCall(data,subject,"read",onSentData, block, message);
}

function apiCallCreate(data, subject, onSentData = null, block = false, message = true)
{

	let payload = {
			models: [data]
			};
	
	apiCall(payload,subject,"create",onSentData, block, message);
}

function apiCallUpdate(data, subject, onSentData = null, block = false, message = true)
{

	let payload = {
			models: [data]
			};
	
	apiCall(payload,subject,"update",onSentData, block, message);
}

function apiCallDestroy(data, subject, onSentData = null, block = false, message = true)
{

	let payload = {
			models: [data]
			};
	
	apiCall(payload,subject,"destroy",onSentData, block, message);
}

function apiCall(data, subject, type, onSentData = null, block = false, message = true)
{
	sendData(data,"/api?subject="+subject+"&type="+type+"&action=grid",onSentData, block, message);
}

function sendData(data, destination, onSentData = null, block = false, message = true)
{
	var jqxhr = $.post({
					url: window.location.origin+destination,
					data: JSON.stringify(data),
					contentType: 'application/json; charset=utf-8'})
			.done(function(response) {
				if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
				
					if(!block) kendo.ui.progress($(document.body), false);
					if(message) $('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');
					return false;
				}
				else
				{
					if(!block) kendo.ui.progress($(document.body), false);

					if(message && response !== undefined && typeof response !== 'object')
						$('#staticNotification').data('kendoNotification').show(response, 'info');
					
					if (typeof onSentData === "function") { 
						onSentData(response);
					}
				}
			})
			.fail(function() {
				
				if(!block) kendo.ui.progress($(document.body), false);
				
				//probleme de retea
				if(message) $('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
				return false;
			});
}