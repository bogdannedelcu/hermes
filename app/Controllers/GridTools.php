<?php
include_once(APPPATH . 'Libraries/telerik/lib/Kendo/Autoload.php');
include_once(APPPATH . 'Libraries/telerik/lib/DataSourceResultNew.php');

trait GridTools {
		
	private $grid;
	private $subject;
	private $gridColumns=[];
	private $gridFields=[];
	
	private $customCommands=[];
	private $customOperations=[];
	
	private $columns=[];
	private $columnTypes=[];

	public function init()
	{
		$this->grid = new \Kendo\UI\Grid('grid');
		$this->grid->height('100%');
		$this->data['gridJS'] = '
					<script>

					//telerik search panel start
					var initialFilters;

					function onFilter(e) {
							// Remove the initial filter if the "Clear" button in the menu is pressed. Otherwise the cleared initial filter will be restored when you use the search input.
							if(initialFilters && !e.filter) {
							  initialFilters.filters = initialFilters.filters.filter(f => f.field !== e.field);
							}
							e.clearSelection();
					}

					$(function() {
						let grid = $("#grid").data("kendoGrid");
						initialFilters = grid.dataSource.filter();

					// Turn off the default search event and append a custom one.
					grid.wrapper.find(".k-grid-toolbar").off("input.kendoGrid").on("input.kendoGrid", ".k-grid-search input", function(e) {
						  // The logic is practically the same as the built-in filter with the addition of the combinedFilter.
						  let combinedFilter = [],
							  input = e.currentTarget;
							
						  grid.clearSelection();
						  grid._selectedIds=[];
						  clearTimeout(grid._searchTimeOut);

						  // The reason for the delay is to prevent multiple requests if you\'re typing fast.
						  //timeout = setTimeout(function() {
							grid._searchTimeOut = null;
							var options = grid.options,
								searchFields = options.search ? options.search.fields : null,
								expression = {filters: [], logic:"or", type:"quick"},
								value = input.value;

							if (!searchFields) {
							  // The getColumnsFields function can be found down below.
							  searchFields = getColumnsFields(options.columns);
							}

							if (grid.dataSource.options.endless) {
							  grid.dataSource.options.endless = null;
							  grid._endlessPageSize = grid.dataSource.options.pageSize;
							  grid.dataSource.pageSize(grid.dataSource.options.pageSize);
							}

							if (value) {
							  for (var i = 0; i < searchFields.length; i++) {
								grid._pushExpression(expression.filters, searchFields[i], value);
							  }
							} else {
							  expression = {};
							}
							// Add the search expressions to the combined filter array.
							if(!$.isEmptyObject(expression)) {
							  combinedFilter.push(expression);
							}
							
							// Check for any predefined filters.
							if(initialFilters && initialFilters.filters.length > 0) {
							  // Push each predefined filter to the combined filter array.
							  initialFilters.filters.forEach(function(x) {
								combinedFilter.push(x);
							  });
							}

							// Apply the filter.
							grid.dataSource.filter(combinedFilter);
						  //}, 500);
					  });

					  // This logic is used for when you have multi-header columns(nested columns in simpler words.)
					  function leafColumns(columns) {
						var result = [];

						for (var idx = 0; idx < columns.length; idx++) {
						  if (!columns[idx].columns) {
							result.push(columns[idx]);
							continue;
						  }
						  result = result.concat(leafColumns(columns[idx].columns));
						}

						return result;
					  }

					  // The following method retrieves the names of the fields from the columns.
					  function getColumnsFields(columns) {
						var result = [];
						columns = leafColumns(columns);

						for (var idx = 0; idx < columns.length; idx++) {
						  if (typeof columns[idx] === "string") {
							result.push(columns[idx]);
						  } else if (columns[idx].field) {
							result.push(columns[idx].field);
						  }
						}
						return result;
					  }
					});
				//telerik search panel end
				</script>
				';
	}
	
	private function createGridTransport($subject,$jsReadExternalData=null)
	{
		if (!isset($this->grid)) $this->init();
	
		$this->subject = $subject;
	
		$transport = new \Kendo\Data\DataSourceTransport();

		$create = new \Kendo\Data\DataSourceTransportCreate();

		$create->url(site_url().'/api?subject='.$subject.'&type=create')
			 ->contentType('application/json')
			 ->type('POST');

		$read = new \Kendo\Data\DataSourceTransportRead();

		$read->url(site_url().'/api?subject='.$subject.'&type=read')
			 ->contentType('application/json')
			 ->type('POST');

		if($jsReadExternalData)
			$read->data(new \Kendo\JavaScriptFunction($jsReadExternalData));
		
		$update = new \Kendo\Data\DataSourceTransportUpdate();

		$update->url(site_url().'/api?subject='.$subject.'&type=update')
			 ->contentType('application/json')
			 ->type('POST');

		$destroy = new \Kendo\Data\DataSourceTransportDestroy();

		$destroy->url(site_url().'/api?subject='.$subject.'&type=destroy')
			 ->contentType('application/json')
			 ->type('POST');

		$transport->create($create)
				  ->read($read)
				  ->update($update)
				  ->destroy($destroy)
				  ->parameterMap('function(data) {
					  return kendo.stringify(data);
				  }');
		
		return $transport;
	}
	
	private function createNowFilter($field, $unlimited = false, $year = 'Y')
	{
		$dt = new \DateTime('now');
		
		if($year != 'Y')
			$dt->setDate($year, $dt->format('m'),$dt->format('d'));
		
		$filterItem1 = new \Kendo\Data\DataSourceFilterItem();
		$filterItem1->field($field);
		$filterItem1->operator('gte');
		if($unlimited)
			$filterItem1->value('2000-01-01');
		else
			$filterItem1->value($dt->format("Y-m-01"));


		$filterItem2 = new \Kendo\Data\DataSourceFilterItem();
		$filterItem2->field($field);
		$filterItem2->operator('lte');
		if($unlimited)
			$filterItem2->value('2100-01-01');
		else
			$filterItem2->value($dt->format("Y-m-t"));

		$filterItem = new \Kendo\Data\DataSourceFilterItem();
		$filterItem->filters(array($filterItem1,$filterItem2));
		$filterItem->logic('and');	
		
		return $filterItem;
	}
	
	private function createFilter($field,$operator,$values)
	{
		$filterItem = new \Kendo\Data\DataSourceFilterItem();
		
		if($operator == 'in' || $operator == 'not in')
		{
			$neg = '';
			$operators = explode(' ',$operator);
			if($operators[0] == 'not') 
			{
				$neg = 'n';
				$op = $operators[1];
			}
			else
				$op = $operators[0];
			
			$filters = [];
			foreach($values as $v)
			{
				$fI = new \Kendo\Data\DataSourceFilterItem();
				$fI->field($field);
				$fI->operator($neg.'eq');
				$fI->value($v);
				
				array_push($filters,$fI);
			}
			
			$filterItem->filters($filters);
			if($neg)
				$filterItem->logic('and');
			else
				$filterItem->logic('or');
		}
		elseif($operator == 'eq' || $operator == 'neq')
		{
			$filterItem->field($field);
			$filterItem->operator($operator);
			$filterItem->value($values[0]);
		}
		
		return $filterItem;
	}
	
	private function combineFilters($filter1,$filter2, $logic)
	{
		$filterItem = new \Kendo\Data\DataSourceFilterItem();
		$filterItem->filters(array($filter1,$filter2));
		$filterItem->logic($logic);	
		
		return $filterItem;
	}
	
	private function createSortItem($field, $order='desc')
	{	
		$sortItem = new \Kendo\Data\DataSourceSortItem();
		$sortItem->dir($order);
		$sortItem->field($field);
		
		return $sortItem;
	}

	private function createAggregateItem($field, $aggregate)
	{
		$aggregateItem = new \Kendo\Data\DataSourceAggregateItem();
		$aggregateItem->field($field)
                 ->aggregate($aggregate);
		
		return $aggregateItem;
	}

	private function createGridSchema($columns = null,$columnTypes=null,$defaultValues=null,$readonlyColumns=[])
	{
		$model = new \Kendo\Data\DataSourceSchemaModel();

		if(empty($columns))
		{
			$dsResult = new \DataSourceResult(config('Database')->default['DSN'],config('Database')->default['username'],config('Database')->default['password']);
			$columns = $dsResult->getTableColumns($this->subject);
			$dataTypes = $dsResult->getTableColumnsDataTypes($this->subject);
			for($i=0;$i<count($dataTypes);$i++)
			{
				switch($dataTypes[$i])
				{
					case 'int':
					case 'bigint':
					case 'double':
					case 'decimal':
						$columnTypes[$i] = 'number';
					break;
					case 'date':
						$columnTypes[$i] = 'date';
					break;					
					default:
						$columnTypes[$i] = 'string';
					break;										
				}
			}			
		}
		
		$this->columns = $columns;
		$this->columnTypes = $columnTypes;
		
		$IDField = new \Kendo\Data\DataSourceSchemaModelField($columns[0]);
		$IDField->type($columnTypes[0])
					   ->editable(false)
						->nullable(true);

		$this->gridFields[$columns[0]] = $IDField;
		$model->id($columns[0])
			->addField($IDField);
			
		
		for($i=1;$i<count($columns);$i++)
		{
			$field = new \Kendo\Data\DataSourceSchemaModelField($columns[$i]);
			$field->type($columnTypes[$i]);
			
			if(isset($defaultValues[$columns[$i]]))
				$field->defaultValue($defaultValues[$columns[$i]]);
			
			if(in_array($columns[$i],$readonlyColumns))
			{	$field->editable(false)
					  ->validation(array('required' => false));
			}
			else
				$field->validation(array('required' => true));

			$this->gridFields[$columns[$i]] = $field;
			$model->addField($field);
		}

		$schema = new \Kendo\Data\DataSourceSchema();
		$schema->data('data')
			   ->errors('errors')
			   ->total('total')
			   ->groups('groups')
			   ->aggregates('aggregates')
			   ->model($model);
		
		return $schema;
	}

	private function createDataSource($transport,$schema)
	{
		$dataSource = new \Kendo\Data\DataSource();

		$dataSource->transport($transport)
				   ->pageSize(1000)
				   ->batch(true)
				   ->schema($schema)
				   ->serverFiltering(true)
				   ->serverPaging(true)
				   ->serverSorting(true)
				   ->serverAggregates(true)
				   ->serverGrouping(true);
		
		return $dataSource;		 
	}

	private function addCustomOperation($name,$text, $icon)
	{
			$customOperation = new \Kendo\UI\GridToolbarItem();
			$customOperation->name($name);
			$customOperation->text($text);
			$customOperation->iconClass($icon);
			//$gti->templateId("template_".$co['name']);

	
		array_push($this->customOperations,$customOperation);
	}
	
	private function addCustomCommand($name,$icon,$jsFunctionName)
	{	
		$customCommand = new \Kendo\UI\GridColumnCommandItem();
		$customCommand->iconClass($icon);
		$customCommand->click(new \Kendo\JavaScriptFunction('function(e) {                  
																	  // prevent page scroll position change
																	  e.preventDefault();
																	  // e.target is the DOM element representing the button
																	  var tr = $(e.target).closest("tr"); // get the current table row (tr)

																	  // get the data bound to the current table row
																	  item = this.dataItem(tr);
																	  
																	  '.$jsFunctionName.'(item.id);
																	  }'
															));
		$customCommand->name($name);
		$customCommand->text('');
		$customCommand->visible(new \Kendo\JavaScriptFunction('function(e) { if (typeof e.can'.$name.' != "undefined") return (e.can'.$name.' != 0); else return true;}'));
		
		array_push($this->customCommands,$customCommand);	
	}
	
	private function setGridEditable($editType='inline',$allowCreate = true, $allowEdit=true, $allowDelete=true, $allowClone = false, $allowSelection=false)
	{
		$this->grid->editable($editType);

		if($allowCreate)
			$this->grid->addToolbarItem(new \Kendo\UI\GridToolbarItem('create'));
		
		if($this->customOperations)
			foreach($this->customOperations as $cO)
				$this->grid->addToolbarItem($cO);
		
		$command = null;
		
		if($allowEdit || $allowDelete || $this->customCommands)
		{
			$command = new \Kendo\UI\GridColumn();
			$command->media("(min-width: 800px)");
		}
		
		if($allowEdit)
		{			
			$editCommand = new \Kendo\UI\GridColumnCommandItem();
			$editCommand->className('edit');
			$editCommand->name('edit');
			$editCommand->text('');
			$editCommand->visible(new \Kendo\JavaScriptFunction('function(e) { if (typeof e.editable != "undefined") return (e.editable != 0); else return true;}'));
			$command->addCommandItem($editCommand);
		}
		
		foreach($this->customCommands as $c)
			$command->addCommandItem($c);
		
		if($allowDelete)
		{
			$destroyCommand = new \Kendo\UI\GridColumnCommandItem();
			$destroyCommand->className('destroy');
			$destroyCommand->name('destroy');
			$destroyCommand->text('');
			$destroyCommand->visible(new \Kendo\JavaScriptFunction('function(e) { if (typeof e.deletable != "undefined") return (e.deletable != 0); else return true;}'));
			$command->addCommandItem($destroyCommand);
		}

		if($allowClone)
		{
			//not implemented!
			$link = $target = '';
			$cloneCommand = new \Kendo\UI\GridColumnCommandItem();
			$cloneCommand->iconClass('k-icon k-i-copy');
			$cloneCommand->click(new \Kendo\JavaScriptFunction('function(e) {                  
																		  // prevent page scroll position change
																		  e.preventDefault();
																		  // e.target is the DOM element representing the button
																		  var tr = $(e.target).closest("tr"); // get the current table row (tr)

																		  // get the data bound to the current table row
																		  item = this.dataItem(tr);
																		  
																		  window.open("'.$link.'"+item.id, "'.$target.'");
																		  }'
																));
			$cloneCommand->name('clone');
			$cloneCommand->text('');

			$command->addCommandItem($cloneCommand);	
		}

		if($allowSelection)
		{
			$selectColumn = new \Kendo\UI\GridColumn();
			$selectColumn->selectable(true)
			->attributes(array('class' => 'checkbox-align'))
			->headerAttributes(array('class' => 'checkbox-align'))
			->width(32)
			->media("(min-width: 800px)");
			$this->grid->addColumn($selectColumn);
			$this->grid->persistSelection(true);
		}
		
		if(isset($command))
		{
			if($allowEdit && !$allowDelete && count($this->customCommands) ==0 ) 
				$cWidth = 62;
			else 
				$cWidth = ($allowEdit+$allowDelete+count($this->customCommands))*30+2;
			
			$command->title('&nbsp;')
					->width($cWidth);
			$this->grid->addColumn($command);
		}
			
		return $command;
	}

	private function setGridScrollable($scrollType='infinite')
	{	
		if($scrollType == 'infinite')
		{
			$scrollable = new \Kendo\UI\GridScrollable();
			$pageable = new \Kendo\UI\GridPageable();
		
			$scrollable->endless(true);
			$messages = new \Kendo\UI\GridPageableMessages();
			$display = '{2} inregistrari';
			$messages->display($display);
			$pageable->messages($messages)
					 ->numeric(false)
					->previousNext(false);
			
			$this->grid->pageable($pageable);
			$this->grid->scrollable($scrollable);
			
			$this->grid->group('function(e) {	var grid = $("#grid").data("kendoGrid");
										if (grid.dataSource.options.endless) {
										  grid.dataSource.options.endless = null;
										  grid._endlessPageSize = grid.dataSource.options.pageSize;
										  grid.dataSource.pageSize(grid.dataSource.options.pageSize);
										} }');
		}
		else

			$this->grid->navigatable(true);


	}
	
	private function & createTemplateColumn($templateName)
	{
		include_once('Templates.php');
		$colTemplate = new \Kendo\UI\GridColumn();
		$colTemplate->template("#=resColTemplate(data)#")
					->media("(max-width: 799px)");
		
		$el = array_push($this->gridColumns,$colTemplate);

		$template = new Templates();

		$this->data['gridJS'] .= '
					<script>
					var resColTemplate = kendo.template($("#responsive-column-template").html());
					</script>' .
					$template->render($templateName);

		return $this->gridColumns[$el-1];
	}

	private function & createGridColumn($field,$columnName,$width=null)
	{
		$column = new \Kendo\UI\GridColumn();
		$column->field($field)
					->title($columnName)
					->sortable(true)
					->media("(min-width: 800px)");
					
		if($width != null) $column->width($width);
		
		if(strpos($field, 'date_') !== FALSE || strpos($field, '_date') !== FALSE)
		{
			$column->template("#= data.".$field." ? kendo.format('{0:dd/MM/yyyy}',data.".$field.") : '' #");
			$column->groupHeaderTemplate("#=kendo.format('{0:dd-MM-yyyy}',data.value)#");
		}
					
		$el = array_push($this->gridColumns,$column);
		
		return $this->gridColumns[$el-1];
	}

	private function createGridMenu($xlsFileName='Export.xlsx')
	{
		foreach($this->gridColumns as $c)
			$this->grid->addColumn($c);
			
		$excel = new \Kendo\UI\GridExcel();
		$excel->fileName($xlsFileName)
			  ->filterable(true)
			  ->allPages(true)
			  ->proxyURL(site_url().'/api?subject='.$this->subject.'&type=save');
			  
		$columnMenu = new \Kendo\UI\GridColumnMenu();
		$columnMenu->filterable(true);
		$this->grid->columnMenu($columnMenu)
					->addToolbarItem(new \Kendo\UI\GridToolbarItem('excel'), new \Kendo\UI\GridToolbarItem('pdf'), new \Kendo\UI\GridToolbarItem('search'))
					->excel($excel)
					->reorderable(true)
					->filterable(true)
					->groupable(true)
					->sortable(true);
					
	}
}

?>