<?php
class TypesFilter 
{
	private $buttonGroup;
	private $dIds;
	private $cid;
	
	function __construct($cid, $values, $selectedValue = null)
	{
		$this->buttonGroup = new \Kendo\UI\ButtonGroup($cid);
		$t=[];
		$index = 0;

		foreach($values as $value)
		{
			if(isset($value['text']))
			{
				$d = new \Kendo\UI\ButtonGroupItem();
				$d->text($value['text']);
				
				$this->buttonGroup->addItem($d);
				if(is_numeric($value['value']))
					$t[] = $value['value'];
				else
					$t[] = "'{$value['value']}'";
			
				if($selectedValue == $value['value']) 
					$this->buttonGroup->index($index);
			}
			else
			{
				$d = new \Kendo\UI\ButtonGroupItem();
				$d->text($value);
				
				$this->buttonGroup->addItem($d);
				if(is_numeric($value))
					$t[] = $value;
				else
					$t[] = "'{$value}'";
				
				if($selectedValue == $value) 
					$this->buttonGroup->index($index);
			}
			
			$index++;
		}
		$this->buttonGroup->select('changed_'.$cid);
		
		$this->cid = $cid;
		$this->dIds = implode(',',$t);
	}

	function render()
	{
		return "
		<div class='row pb-1 pt-1'>
			<div class='col'>
			". $this->buttonGroup->render()."
			</div>
		</div>

		<script>
			  var selected_".$this->cid." = -1;
			  var prev".$this->cid."=-1;
			  const ".$this->cid."=[".$this->dIds."];
			  
			  function changed_".$this->cid."(e)
			  {
				  if (e.sender.current().index() == prev".$this->cid.")
						resetFilter_".$this->cid."();
					else
						prev".$this->cid." = e.sender.current().index();
					
					if(prev".$this->cid." != -1) 
						selected_".$this->cid." = ".$this->cid."[prev".$this->cid."];
					else
						selected_".$this->cid." = -1;
					
				  if( typeof ".$this->cid."Changed === 'function') ".$this->cid."Changed();
			  }
			  
			  function resetFilter_".$this->cid."()
			  {
				prev".$this->cid." = -1;
				$('#".$this->cid."').data('kendoButtonGroup').selectedIndex = -1;
				$('#".$this->cid."').find('.k-selected').removeClass('k-selected');
			  }			  
			  
		</script>
		";
	}
}

?>