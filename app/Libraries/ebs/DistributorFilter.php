<?php
class DistributorFilter 
{
	private $buttonGroup;
	
	function __construct($field, $all = false)
	{
		$this->buttonGroup = new \Kendo\UI\ButtonGroup('select-distributor');
		$d1 = new \Kendo\UI\ButtonGroupItem();
		$d1->text("Transilvania Sud");
		$d2 = new \Kendo\UI\ButtonGroupItem();
		$d2->text("Transilvania Nord");
		$d3 = new \Kendo\UI\ButtonGroupItem();
		$d3->text("Muntenia Nord");
		$d4 = new \Kendo\UI\ButtonGroupItem();
		$d4->text("Muntenia Sud");
		$d5 = new \Kendo\UI\ButtonGroupItem();
		$d5->text("Banat");
		$d6 = new \Kendo\UI\ButtonGroupItem();
		$d6->text("Dobrogea");
		$d7 = new \Kendo\UI\ButtonGroupItem();
		$d7->text("Moldova");
		$d8 = new \Kendo\UI\ButtonGroupItem();
		$d8->text("Oltenia");

		$this->buttonGroup->addItem($d1,$d2,$d3,$d4,$d5,$d6,$d7,$d8);
		$this->buttonGroup->select('distributorFilterChanged');

		$this->field = $field;

		if($all)
		{
			$d9 = new \Kendo\UI\ButtonGroupItem();
			$d9->text("General");
			$this->buttonGroup->addItem($d9);
		}

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
			  const dId=[3,2,6,4,5,1,8,7,0];

					
			  var prevDistributor;
			  $( document ).ready(function() {
					var grid = $('#grid').data('kendoGrid');
					if(grid == undefined)
					{
						let urlParams = new URLSearchParams(window.location.search);
						let id = urlParams.get('".$this->field."');
						$('#select-distributor').data('kendoButtonGroup').select(dId.indexOf(parseInt(id)));
					}
					
					prevDistributor = $('#select-distributor').data('kendoButtonGroup').current().index();
				});
				
			  function distributorFilterChanged(e) {
				if (e.sender.current().index() == prevDistributor)
						resetFilter_".$this->field."();
					else
						prevDistributor = e.sender.current().index();

				var grid = $('#grid').data('kendoGrid');	

				if(grid == undefined)
				{
					let urlParams = new URLSearchParams(window.location.search);
					if(urlParams.has('".$this->field."')) urlParams.delete('".$this->field."');
					urlParams.append('".$this->field."', dId[e.sender.current().index()]);
					window.location = location.origin+location.pathname+'?'+urlParams;
				}
				
				var f = grid.dataSource._filter;
				var p = f;
				
				/*let idx=0;
				
				if(f.filters != undefined && f.filters[0].type != undefined && f.filters[0].type == 'quick') idx=1;
				while(f.filters != undefined && f.filters[idx] != undefined && f.filters[idx].field == undefined) {p = f; f = f.filters[idx]};
				
				let ffound = false;
				for(i = 0;i< p.filters.length;i++)
				{
					f = p.filters[i];
					if(f.filters[0].field == '".$this->field."') 
					{
						if(prevDistributor == -1)
						{
							p.filters.splice(i,1);
						}
						else
						{
							if(dId[prevDistributor] == 0)
							{
								f.filters[0].value = null;
								f.filters[0].operator = 'isnull';
							}
							else
							{
								f.filters[0].value = dId[prevDistributor];
								f.filters[0].operator = 'eq';
							}							
						}
						
						ffound = true;
						break;
					}
				}
								
				if (!ffound)
				{
					let v={};
					if(dId[prevDistributor] == 0)
					{
						v = {
						logic:'and',
						filters:[{ field: '".$this->field."', operator: 'isnull', value:null }]
						};
					}
					else
					{
						v = {
						logic:'and',
						filters:[{ field: '".$this->field."', operator: 'eq', value:dId[prevDistributor] }]
						};
					}
					p.filters.push(v);
				} */
				
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
					
					return null;
				};
				
				f = findFilter(p,f,'".$this->field."');

				if(!f)
				{
					let v={};
					if(dId[prevDistributor] == 0)
					{
						v = {
						logic:'and',
						filters:[{ field: '".$this->field."', operator: 'isnull', value:null }]
						};
					}
					else
					{
						v = {
						logic:'and',
						filters:[{ field: '".$this->field."', operator: 'eq', value:dId[prevDistributor] }]
						};
					}
					p.filters.push(v);					
				}
				else
				{
					if(prevDistributor == -1)
					{
						for(i=0;i<p.filters.length;i++)
							if(p.filters[i] == f) {p.filters.splice(i,1);break;}
					}
					else
					{
						if(dId[prevDistributor] == 0)
						{
							f.filters[0].value = null;
							f.filters[0].operator = 'isnull';
						}
						else
						{
							f.filters[0].value = dId[prevDistributor];
							f.filters[0].operator = 'eq';
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
			  
			function resetFilter_".$this->field."(){
				prevDistributor = -1;
				$('#select-distributor').data('kendoButtonGroup').selectedIndex = -1;
				$('#select-distributor').find('.k-selected').removeClass('k-selected');
			}
		</script>
		";
	}
}

?>