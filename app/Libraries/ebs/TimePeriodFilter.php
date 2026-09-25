<?php

class TimePeriodFilter 
{
	const months=array('IAN','FEB','MAR','APR','MAY','IUN','IUL','AUG','SEP','OCT','NOV','DEC');
	private $buttonMonthsGroup;
	private $buttonYearsGroup;
	private $field;
	private $monthGroupName;
	private $yearGroupName;
	private $changeJSFunctionName;
	private $label;
	private $subject;
	private $option;
	private $callJSFunction;
	private $applyGridFilter;
	private $selectionType;
	private $allowDeselect;
	
	function __construct($field,$subject, $label, $minYear = null , $maxYear = null, $selDateTime = "now", $selectionType = "single", $allowDeselect = true)
	{
		$this->field = $field;
		$this->monthGroupName = 'select_month_'.$field;
		$this->yearGroupName = 'select_year_'.$field;
		$this->changeJSFunctionName = 'timeFilterChanged_'.$field;
		$this->label = $label;
		$this->subject = $subject;
		$this->callJSFunction = null;
		$this->applyGridFilter = true;
		$this->allowDeselect = $allowDeselect;
		
		$this->buttonMonthsGroup = new \Kendo\UI\ButtonGroup($this->monthGroupName);
		
		if($selDateTime)
		{
			$dt = new \DateTime($selDateTime);
			$selYear = $dt->format('Y');
			$selMonth = self::months[$dt->format('n') - 1];
		}
		else
			$selYear = $selMonth = null;
		
		
		foreach(self::months as $m)
		{
			$d = new \Kendo\UI\ButtonGroupItem();
			$d->text($m);
			
			if($selMonth == $m) $d->selected(true);
			
			$this->buttonMonthsGroup->addItem($d);
			
		}
		$this->buttonMonthsGroup->select($this->changeJSFunctionName);
		$this->selectionType = $selectionType;
		$this->buttonMonthsGroup->selection($selectionType);

		$this->buttonYearsGroup = new \Kendo\UI\ButtonGroup($this->yearGroupName);

		if(!isset($minYear)) $minYear = date("Y");
		if(!isset($maxYear)) $maxYear = date("Y");
	
		for($i = $minYear;$i<=$maxYear;$i++)
		{
			$d = new \Kendo\UI\ButtonGroupItem();
			$d->text($i);
			if($selYear == $i) $d->selected(true);
			
			$this->buttonYearsGroup->addItem($d);
		}
		$this->buttonYearsGroup->select($this->changeJSFunctionName);	
		
		$this->option= '';
	}
		
	public function setOption($value)
	{
		$this->option = $value;
	}
	
	public function setcallJSFunction($functionName, $applyGridFilter=false)
	{
		$this->callJSFunction = $functionName;
		$this->applyGridFilter = $applyGridFilter;
	}
	
	public function render()
	{
		return '
		<div class="row py-1">
			<div class="col-auto align-self-center minw-120px">
			'. $this->label .'
			</div>
			<div class="col-auto">
			'. $this->buttonMonthsGroup->render() .'
			</div>
			<div class="col-auto">
			'. $this->buttonYearsGroup->render() .'
			</div>
		</div> '.
		"<script>
		
			  var selYear_".$this->field." = -1;
			  var selMonth_".$this->field." = -1;
			  
			  $( document ).ready(function() { 
					
					if($('#".$this->yearGroupName."').data('kendoButtonGroup').current().index() >= 0)
					{
						selYear_".$this->field." = $('#".$this->yearGroupName."').data('kendoButtonGroup').current().text().trim();
						$('#".$this->yearGroupName."').data('kendoButtonGroup').current().text().trim();
					}
					//else
					//	selYear_".$this->field." = $('#".$this->yearGroupName."').data('kendoButtonGroup').options.items[$('#".$this->yearGroupName."').data('kendoButtonGroup').options.items.length - 1].text;
					
					selMonth_".$this->field." = $('#".$this->monthGroupName."').data('kendoButtonGroup').current().index();
				
				
				
					if (selYear_".$this->field." >= 0 || selMonth_".$this->field." >= 0) 
					  {
						  if (selYear_".$this->field." < 0) 
						  {
							$('#".$this->yearGroupName."').data('kendoButtonGroup').select($('#".$this->yearGroupName."').data('kendoButtonGroup').options.items.length - 1);
							selYear_".$this->field." = $('#".$this->yearGroupName."').data('kendoButtonGroup').options.items[$('#".$this->yearGroupName."').data('kendoButtonGroup').options.items.length - 1].text;
						  }
						  startYear = selYear_".$this->field.";
						  endYear = selYear_".$this->field.";

						  
						  if (selMonth_".$this->field." < 0) 
						  {
							startMonth = 0;
							endMonth = 12;
						  }
						  else 
						  {
							  startMonth = selMonth_".$this->field.";
							  endMonth = startMonth + 1;
						  }
						  						  				  
					  
						  getBadges_".$this->monthGroupName."(selYear_".$this->field.");
					  }		
			});
			
			function updateBadges_".$this->field."() {
				getBadges_".$this->monthGroupName."(selYear_".$this->field.");
			}
			
			function getBadges_".$this->monthGroupName."(year) {

				if(year == -1)
				{
						for(i=0; i<12; i++)
							if($('#".$this->monthGroupName."').data('kendoButtonGroup').badge(i)>0)
								$('#".$this->monthGroupName."').data('kendoButtonGroup').badge(i,false);
						return;
				}
							
				var data = {
							models: [
									 {
										subject :'".$this->subject."',
										field : '".$this->field."',
										year: year,
										option:'".$this->option."'
									 }
									]
							};
							
				var jqxhr = $.post({
						url: window.location.origin+'/api?subject=custom&type=call&action=getMonthlyBadges',
						data: JSON.stringify(data),
						contentType: 'application/json; charset=utf-8'})
				.done(function(response) {
					if (typeof response !== 'undefined' && typeof response.errors !== 'undefined') {
					
						$('#staticNotification').data('kendoNotification').show(response.errors[0], 'error');
						return false;
					}
					else
					{
						for(i=0; i<12; i++)
							if($('#".$this->monthGroupName."').data('kendoButtonGroup').badge(i)>0)
								$('#".$this->monthGroupName."').data('kendoButtonGroup').badge(i,false);
						
						for(i=0; i<response.data.length; i++)
							$('#".$this->monthGroupName."').data('kendoButtonGroup').badge(response.data[i].month-1,response.data[i].nr);
						
					}
				})
				.fail(function() {
					
					kendo.ui.progress($(document.body), false);
					
					//probleme de retea
					$('#staticNotification').data('kendoNotification').show('Eroare, mai incercati odata.','error');
					return false;
				});
			}
			
			function ".$this->changeJSFunctionName."(e){
			
			let refresh = true;
			if (e.sender.wrapper[0].id == '".$this->monthGroupName."')
			{
				if ('".$this->selectionType."' =='single' && e.sender.current().index() == selMonth_".$this->field.")
				{
					if(".($this->allowDeselect ? 'true' : 'false').")
					{
						selMonth_".$this->field." = -1;
						$('#".$this->monthGroupName."').data('kendoButtonGroup').selectedIndex = -1;
						$('#".$this->monthGroupName."').find('.k-selected').removeClass('k-selected');
					}
					else 
						refresh = false;
				}
				else
					selMonth_".$this->field." = e.sender.current().index();
			}
			
			if (e.sender.wrapper[0].id == '".$this->yearGroupName."')
			{
				if (e.sender.options.items[e.sender.current().index()].text == selYear_".$this->field.")
				{
					if(".($this->allowDeselect ? 'true' : 'false').")
					{
						selYear_".$this->field." = -1;
						$('#".$this->yearGroupName."').data('kendoButtonGroup').selectedIndex = -1;
						$('#".$this->yearGroupName."').find('.k-selected').removeClass('k-selected');
						
						selMonth_".$this->field." = -1;
						$('#".$this->monthGroupName."').data('kendoButtonGroup').selectedIndex = -1;
						$('#".$this->monthGroupName."').find('.k-selected').removeClass('k-selected');
					}
					else
						refresh = false;
				}
				else
					selYear_".$this->field." = e.sender.options.items[e.sender.current().index()].text;	
			
				if(refresh)
					getBadges_".$this->monthGroupName."(selYear_".$this->field.");
			}
			  
			  if(!refresh) return;
			  
			  if (selYear_".$this->field." < 0 && selMonth_".$this->field." < 0)
			  {
				  startMonth = 0; endMonth = 12; startYear = 2000;endYear=2100;
			  }
			  else				  
			  {
				  if (selYear_".$this->field." < 0) 
				  {
					$('#".$this->yearGroupName."').data('kendoButtonGroup').select($('#".$this->yearGroupName."').data('kendoButtonGroup').options.items.length - 1);
					selYear_".$this->field." = $('#".$this->yearGroupName."').data('kendoButtonGroup').options.items[$('#".$this->yearGroupName."').data('kendoButtonGroup').options.items.length - 1].text;
				  
					getBadges_".$this->monthGroupName."(selYear_".$this->field.");
				  }
				  startYear = selYear_".$this->field.";
				  endYear = selYear_".$this->field.";

				  
				  if (selMonth_".$this->field." < 0) 
				  {
					startMonth = 0;
					endMonth = 12;
				  }
				  else 
				  {
					  startMonth = selMonth_".$this->field.";
					  endMonth = startMonth + 1;
				  }
				  
			  }
			  ".
			  ( $this->callJSFunction != null ? $this->callJSFunction."(e);" :"") .
			  ( $this->applyGridFilter == false ? "" :
			    "
				var grid = $('#grid').data('kendoGrid');
			  	var f = grid.dataSource._filter;
				var p = f;
				
				function findFilter(p, f, field){
					
					if(f.type != undefined && f.type == 'quick') return null;
					
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
				};
					
				f = findFilter(p,f,'".$this->field."');
				
				let sd = new Date(startYear, startMonth , 1);
				let ed = new Date(endYear, endMonth , 0);
				f.filters[0].value = sd.getFullYear() + '-'+(sd.getMonth()+1)+'-1';
				f.filters[1].value = ed.getFullYear() + '-'+(ed.getMonth()+1)+'-'+ed.getDate(); 
				
				//Reset selection
				grid.clearSelection();
				grid._selectedIds=[];
						  
				// Reset the scroll position and update the pageSize before invoking the operation.
						
				grid.dataSource.options.endless = null;
				grid._endlessPageSize = grid.dataSource.options.pageSize;
				grid.dataSource.pageSize(grid.dataSource.options.pageSize);
	
				grid.dataSource.read();
				").
				"

			}
			 
		</script>
		";
	}
}
?>
