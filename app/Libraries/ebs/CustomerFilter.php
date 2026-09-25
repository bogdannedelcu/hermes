<?php
class CustomerFilter 
{
	private $dropDownList;
	private $onChanged;
	
	function __construct($textField='customer_name', $valueField='customer_id', $subject='customers', $onChanged=null,$filter=null,$dSource=null,$optionLabel='Selectati Clientul...')
	{	
		if($dSource == null)
		{
			$dataSource = $this->dataSource($subject);
			if($filter!=null)
				$dataSource->addFilterItem($filter);
		}
		else
			$dataSource = $dSource;
		
		$this->dropDownList = new \Kendo\UI\DropDownList('select-customer');
		$this->dropDownList->dataSource($dataSource)
           ->dataTextField($textField)
           ->dataValueField($valueField)
           ->optionLabel($optionLabel)
		   ->change('customerFilterChanged')
		   ->height(500)
		   ->filter('contains');
		   
		 $this->onChanged = $onChanged;
		 
		if (isset($_SESSION['select-customer']))
			$this->dropDownList->value($_SESSION['select-customer']);
			
		
	}

	function dataSource($subject)
	{
		$transport = new \Kendo\Data\DataSourceTransport();

		$read = new \Kendo\Data\DataSourceTransportRead();
		
		$read->url(site_url().'/api?subject='.$subject.'&type=read')
			 ->contentType('application/json')
			 ->type('POST');

		$transport->read($read)
				  ->parameterMap('function(data) {
					  return kendo.stringify(data);
				   }');

		$schema = new \Kendo\Data\DataSourceSchema();
		$schema->data('data')
			   ->total('total');

		$dataSource = new \Kendo\Data\DataSource();
		
		$dataSource->transport($transport)
				   ->schema($schema)
				   ->serverFiltering(true);
			
		return $dataSource;
	}

	function render()
	{
		if($this->onChanged != null) $onChangeFName=$this->onChanged."();";
		else $onChangeFName="";
		
		return 
		"<div class='container'>
		  <div class='row align-items-start'>
			<div class='col minw-300px'><b>".
			  $this->dropDownList->render().
			"</b></div>
		  </div>
		</div>".
		 
			"<script>
			
			function customerFilterChanged(){
			
			var data = {
				models: [
						 {
							sessionVar :'select-customer',
							value : $('#select-customer').data('kendoDropDownList').value()
						 }
						]
				};
				
			var jqxhr = $.post({
							url: window.location.origin+'/api?subject=custom&type=call&action=setSessionVar',
							data: JSON.stringify(data),
							contentType: 'application/json; charset=utf-8'})
					.done(function(response) {
						if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
						
							$('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');
							return false;
						}
						else
						{
							$onChangeFName
						}
					})
					.fail(function() {
						
						kendo.ui.progress($(document.body), false);
						
						//probleme de retea
						$('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
						return false;
					});
				".
				($this->onChanged == null ?
				"	
			
					var grid = $('#grid').data('kendoGrid');
					
					if ( grid.dataSource._filter == undefined)
					grid.dataSource._filter = {filters:[],logic:'and'};	
						
					var f = grid.dataSource._filter;
					var p = f;
					
								
					function findFilter(p, f, field){
						
						
						if (f.type != undefined && f.type == 'quick') return null;
						
						if(f.field != undefined)
						{
							if (f.field == field) return p;
							else return null;
						}
						
						for(let i=0;i<f.filters.length;i++)
						{
							p = f;
							ret = findFilter(p,f.filters[i],field);
							
							if(ret != null) return f.filters[i]; 
						}
						
						return null;
					};
					
					
					f = findFilter(p,f,'customer_id');
					
					if(!f)
					{
						let v={};

						v = {
						logic:'and',
						filters:[{ field: 'customer_id', operator: 'eq', value:$('#select-customer').data('kendoDropDownList').value() }]
						};
												
						p.filters.push(v);					
					}
					else
					{
						if($('#select-customer').data('kendoDropDownList').value() == '')
						{
							for(i=0;i<p.filters.length;i++)
								if(p.filters[i] == f) {p.filters.splice(i,1);break;}
						}
						else
						{
							f.value = $('#select-customer').data('kendoDropDownList').value();
							f.operator = 'eq';
						}
					}
					
					//Reset selection
					grid.clearSelection();
					grid._selecteaIds=[];
					
					//Reset scroll
					grid.dataSource.options.endless = null;
					grid._endlessPageSize = grid.dataSource.options.pageSize;
					grid.dataSource.pageSize(grid.dataSource.options.pageSize);
					
					//grid.dataSource.read();
			
			"
			: 
			""
			).
			
			"
			}
			</script>";
		
		/*return '
		<div class='row py-1'>
			<div class='col-auto align-self-center minw-120px'>
			'. $this->label .'
			</div>
			<div class='col-auto'>
			'. $this->buttonGroup->render() .'
			</div>
		</div> 
		<script>
			  const aId=[''.implode('','',$this->values).''];

					
			  var prevActiveSelection;
			  
			  $( document ).ready(function() {
					prevActiveSelection = $('#select-activ').data('kendoButtonGroup').current().index();
				
					getBadges_active();
				});
			
			  function getBadges_active() {
						
				var data = {
							models: [
									 {
										subject :''.$this->subject.'',
										field : ''.$this->field.''
									 }
									]
							};
							
				var jqxhr = $.post({
						url: window.location.origin+'/api?subject=custom&type=call&action=getActiveBadges',
						data: JSON.stringify(data),
						contentType: 'application/json; charset=utf-8'})
				.done(function(response) {
					if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
					
						$('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');
						return false;
					}
					else
					{
						for(i=0; i<aId.length; i++)
							if($('#select-activ').data('kendoButtonGroup').badge(i)>0)
								$('#select-activ').data('kendoButtonGroup').badge(i,false);
						
						for(i=0; i<response.data.length; i++)
							$('#select-activ').data('kendoButtonGroup').badge(i,response.data[i].nr);
						
					}
				})
				.fail(function() {
					
					kendo.ui.progress($(document.body), false);
					
					//probleme de retea
					$('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
					return false;
				});
			  }
			
			  function activeFilterChanged(e) {
				if (e.sender.current().index() == prevActiveSelection)
						resetFilter();
					else
						prevActiveSelection = e.sender.current().index();

				var grid = $('#grid').data('kendoGrid');
				
				if ( grid.dataSource._filter == undefined)
						grid.dataSource._filter = {filters:[],logic:'and'};	
					
				var f = grid.dataSource._filter;
				var p = f;
				
							
				function findFilter(p, f, field){
					
					
					if (f.type != undefined && f.type == 'quick') return null;
					
					if(f.field != undefined)
					{
						if (f.field == field) return p;
						else return null;
					}
					
					for(let i=0;i<f.filters.length;i++)
					{
						p = f;
						ret = findFilter(p,f.filters[i],field);
						
						if(ret != null) return ret; 
					}
					
					return null;
				};
				
				
				f = findFilter(p,f,''.$this->field.'');

				if(!f)
				{
					let v={};

					v = {
					logic:'and',
					filters:[{ field: ''.$this->field.'', operator: 'eq', value:aId[prevActiveSelection] }]
					};
											
					p.filters.push(v);					
				}
				else
				{
					if(prevActiveSelection == -1)
					{
						for(i=0;i<p.filters.length;i++)
							if(p.filters[i] == f) {p.filters.splice(i,1);break;}
					}
					else
					{
						for(i=0;i<p.filters.length;i++)
							if(p.filters[i] == f)
							{
								f.filters[i].value = aId[prevActiveSelection];
								f.filters[i].operator = 'eq';
							}
					}
				}
				
				//Reset selection
				grid.clearSelection();
				grid._selectedIds=[];
				
				//Reset scroll
				grid.dataSource.options.endless = null;
				grid._endlessPageSize = grid.dataSource.options.pageSize;
				grid.dataSource.pageSize(grid.dataSource.options.pageSize);
				
				grid.dataSource.read();
			  }
			  
			function resetFilter(){
				prevActiveSelection = -1;
				$('#select-activ').data('kendoButtonGroup').selectedIndex = -1;
				$('#select-activ').find('.k-selected').removeClass('k-selected');
			}
		</script>
		';*/
	}
}

?>