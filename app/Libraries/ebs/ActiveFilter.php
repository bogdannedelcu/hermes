<?php
class ActiveFilter 
{
	private $buttonGroup;
	private $label,$field,$subject,$values,$default;
	
	function __construct($field, $subject, $label="Client", $names=['Activ','Inactiv'], $default=-1)
	{
		$this->buttonGroup = new \Kendo\UI\ButtonGroup('select-activ');

		foreach($names as $n)
		{
			$d = new \Kendo\UI\ButtonGroupItem();
			$d->text($n);
			$this->buttonGroup->addItem($d);
		}
		
		$this->label = $label;
		$this->values = $names;
		$this->buttonGroup->select('activeFilterChanged');
		$this->buttonGroup->index($default);

		$this->subject = $subject;
		$this->field = $field;
		$this->default = $default;		
	}

	function render()
	{
		return "
		<div class='row py-1'>
			<div class='col-auto align-self-center minw-120px'>
			". $this->label ."
			</div>
			<div class='col-auto'>
			". $this->buttonGroup->render() ."
			</div>
		</div> 
		<script>
			  const aId=['".implode("','",$this->values)."'];

					
			  var prevActiveSelection = ".$this->default.";
			  
			  $( document ).ready(function() {
					
					var grid = $('#grid').data('kendoGrid');
					if(grid == undefined)
					{
						let urlParams = new URLSearchParams(window.location.search);
						let id = urlParams.get('".$this->field."');
						if(id != null)
						{
							$('#select-activ').data('kendoButtonGroup').select(parseInt(id));
							prevActiveSelection = parseInt(id);
						}
					}
					
					if(prevActiveSelection == -1) 
						prevActiveSelection = $('#select-activ').data('kendoButtonGroup').current().index();
				
					getBadges_active();
				});
			
			  function getBadges_active() {
				
				if('".$this->subject."'.length == 0) return;
					
				var data = {
							models: [
									 {
										subject :'".$this->subject."',
										field : '".$this->field."'
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
				
				if(grid == undefined)
				{
					let urlParams = new URLSearchParams(window.location.search);
					if(urlParams.has('".$this->field."')) urlParams.delete('".$this->field."');
					urlParams.append('".$this->field."', e.sender.current().index());
					window.location = location.origin+location.pathname+'?'+urlParams;
				}
				
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
				
				
				f = findFilter(p,f,'".$this->field."');

				if(!f)
				{
					let v={};

					v = {
					logic:'and',
					filters:[{ field: '".$this->field."', operator: 'eq', value:aId[prevActiveSelection] }]
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
						f.filters[0].value = aId[prevActiveSelection];
						f.filters[0].operator = 'eq';
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
		";
	}
}

?>