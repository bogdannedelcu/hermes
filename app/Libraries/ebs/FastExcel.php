<?php

class FastExcel {
		
	private $sheet;
	private $currentRow;
	private $headerRow;
	private $headerLength;
	
	private $border;
	
	private $borderTop;
	private $borderLeft;
	private $borderBottom;
	private $borderRight;
	
	private $mergedCellsArray;
	private $rowValueTypes;
	private $rowAlignment;
	
	private $rowOffset;
	private $colOffset;
	
	private $colAttributes;
	
	public function __construct(&$sheet,$name='') {
		
		if(is_null($sheet))
			$this->sheet = new \Kendo\UI\SpreadsheetSheet();
		else
			$this->sheet = $sheet;			

		if($name!='')
			$this->sheet->name($name);
		
		$this->currentRow = 0;
		$this->headerRow = 0;
		$this->headerLength = 0;
		
		$this->border = false;
		
		$this->borderTop = new \Kendo\UI\SpreadsheetSheetRowCellBorderTop();
		$this->borderLeft = new \Kendo\UI\SpreadsheetSheetRowCellBorderLeft();
		$this->borderBottom = new \Kendo\UI\SpreadsheetSheetRowCellBorderBottom();
		$this->borderRight = new \Kendo\UI\SpreadsheetSheetRowCellBorderRight();
		
		$this->mergedCellsArray=[];
		$this->rowValueTypes=[];
		$this->rowAlignment=[];
		
		$this->rowOffset = 0;
		$this->colOffset = 0;
		
		$this->colAttributes = [];
	}
	
	public function getSheet()
	{
		return $this->sheet;
	}
	
	
	public function setBorder($border = true)
	{
		$this->border = $border;
	}
	
	public function setColumnsWidth($columnsWidth)
	{
		foreach($columnsWidth as $cw)
		{
			$column = new \Kendo\UI\SpreadsheetSheetColumn();
			$column->width($cw);
			$this->sheet->addColumn($column);
		}
	}
	
	public function mergeCells($range = null)
	{
		if($range != null)
			array_push($this->mergedCellsArray,$range);
		
		if(is_null($range))
		{		
			//align merged cells
			foreach($this->mergedCellsArray as $mc)
			{
				$d = explode(":",$mc);
				$r = ltrim($d[0], $d[0][0]);
				$row = new \Kendo\UI\SpreadsheetSheetRow();
				$row->index($r-1);
				$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
				$cell->index(0);
				$cell->verticalAlign("center");
				$cell->wrap(true);
				$cell->background("#ffffff");
				$row->addCell($cell);
				$this->sheet->addRow($row);
			}
			$this->sheet->mergedCells($this->mergedCellsArray);
		}
	}
	
	public function addTitle($title,$length,$size = 32)
	{
		array_push($this->mergedCellsArray,"A".($this->currentRow+1).":".$this->ColumnNumberToColumnName($length-1).($this->currentRow+1));
		
		$row = new \Kendo\UI\SpreadsheetSheetRow();
		$row->height(40);
		$this->sheet->addRow($row);

		$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
		$row->addCell($cell);
		$cell->value($title);
		$cell->bold(true);
		$cell->fontSize($size);
		$cell->textAlign("center");
		$cell->color("black");
		
		$this->currentRow++;
		
		return $row;
	}
	
	public function addHeader($headerArray,$color="black",$background="rgb(167,214,255)",$height=80,$size = 12)
	{
		
		for($i=0;$i<$this->rowOffset;$i++)
		{
			$row = new \Kendo\UI\SpreadsheetSheetRow();
			$this->sheet->addRow($row);
		}
	
	
		$this->headerLength = count($headerArray);
		
		$row = new \Kendo\UI\SpreadsheetSheetRow();
		$row->height($height);
		
		for($i=0;$i<$this->colOffset;$i++)
		{
			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$row->addCell($cell);
		}
		
		foreach($headerArray as $header)
		{
			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$row->addCell($cell);

			$cell->value($header);
			$cell->textAlign("center");
			$cell->verticalAlign("center");
			$cell->wrap(true);
			$cell->background($background);
			$cell->color($color);
			$cell->bold(true);
			$cell->fontSize($size);
			
			if($this->border){$cell->borderTop($this->borderTop);$cell->borderLeft($this->borderLeft);$cell->borderBottom($this->borderBottom);$cell->borderRight($this->borderRight);}
		}
		$this->sheet->addRow($row);
		$this->currentRow++;
		$this->headerRow=$this->currentRow;
	}
	
	public function setRowValueTypes($rowValueTypes)
	{
		$this->rowValueTypes = $rowValueTypes;
	}
	
	public function setRowAlignment($rowAlignment)
	{
		$this->rowAlignment = $rowAlignment;
	}
	public function addRow($rowArray=[],$color="black",$background="#ffffff",$rowAttr=[])
	{
		$row = new \Kendo\UI\SpreadsheetSheetRow();
		
		for($i=0;$i<$this->colOffset;$i++)
		{
			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$row->addCell($cell);
		}
		
		$i=0;
		foreach($rowArray as $ra)
		{
			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$row->addCell($cell);
			if(isset($this->rowValueTypes[$i]))
			{
				$cell->format($this->rowValueTypes[$i]);
				if(substr($ra,0,1)=='=')
					$cell->formula($ra);
				else
					$cell->value($ra);
			}
			else
			{
				if(is_numeric($ra))
				{
					$cell->value(floatval($ra));
					if($i==0)
							$cell->format("#,###");
						else
							$cell->format("#,##0.000");
				}
				else
				{
					if(substr($ra,0,1)=='=')
					{
						$cell->formula($ra);
						$cell->format("#,##0.000");
					}
					else
						$cell->value($ra);
				}
			}
			if(isset($this->rowAlignment[$i]))
			$cell->textAlign($this->rowAlignment[$i]);
				else
			$cell->textAlign("center");
			
			if(count($rowAttr) == 0)
			{
				$cell->background($background);
				$cell->color($color);
			}
			else
			{
				//$cell->background($rowAttr[$i]['background']);
				//$cell->color($rowAttr[$i]['color']);
				if(isset($rowAttr[$i]['background'])) $cell->background($rowAttr[$i]['background']);
				if(isset($rowAttr[$i]['color'])) $cell->color($rowAttr[$i]['color']);
				if(isset($rowAttr[$i]['format'])) $cell->format($rowAttr[$i]['format']);
				if(isset($rowAttr[$i]['textAlign'])) $cell->textAlign($rowAttr[$i]['textAlign']);
				if(isset($rowAttr[$i]['bold'])) $cell->bold($rowAttr[$i]['bold']);
			}
			
			if(isset($this->colAttributes[$i]))
			{
				foreach($this->colAttributes[$i] as $key => $value)
					$cell->{$key}($value);
			}
			
			if($this->border){$cell->borderTop($this->borderTop);$cell->borderLeft($this->borderLeft);$cell->borderBottom($this->borderBottom);$cell->borderRight($this->borderRight);}
			$i++;
		}
		$this->sheet->addRow($row);
		$this->currentRow++;
	}
	
	public function addTotal($headRowNum=-1,$colNum=1,$formula=[],$color="black",$background="rgb(167,214,255)")
	{
		if($headRowNum<0) $headRowNum = $this->headerRow;
		
		$row = new \Kendo\UI\SpreadsheetSheetRow();
		for($i=0;$i<$this->colOffset;$i++)
		{
			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$row->addCell($cell);
		}
		
		for($col = 1;$col<$colNum;$col++)
		{
			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$row->addCell($cell);
			$cell->background($background);
			$cell->color($color);
			$cell->bold(true);
		}
		
		$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
		$row->addCell($cell);
		$cell->Value("TOTAL");
		$cell->textAlign("center");
		$cell->background($background);
		$cell->color($color);
		$cell->bold(true);
		
		for($col = $colNum;$col<$this->headerLength;$col++)
		{
			$cell = new \Kendo\UI\SpreadsheetSheetRowCell();
			$row->addCell($cell);
			if(isset($formula[$col]))
				$cell->formula($formula[$col]);
			else 
				$cell->formula("sum(".$this->ColumnNumberToColumnName($col).($headRowNum+1).":".$this->ColumnNumberToColumnName($col).($headRowNum+$this->currentRow-$this->headerRow).")");
			
			$cell->textAlign("center");
			$cell->background($background);
			$cell->color($color);
			$cell->bold(true);
			
			if(isset($this->rowValueTypes[$col]))
			{
				$cell->format($this->rowValueTypes[$col]);
				
			}
		}
		
		$this->sheet->addRow($row);
		$this->currentRow++;
	}
	
	public function setFrozen($rows,$cols)
	{
		$this->sheet->frozenColumns($cols);
		$this->sheet->frozenRows($rows);
	}
	
	public function setFilter($range)
	{
		$filter = new \Kendo\UI\SpreadsheetSheetFilter();
		$filter->ref($range);
		$this->sheet->filter($filter);	
	}
	
	public function setOffset($row=0,$col=0)
	{
		$this->rowOffset=$row;
		$this->colOffset=$col;
	}
	
	public function addColAttributes($row,$attribute,$value)
	{
		$this->colAttributes[$row][$attribute] = $value;
	}
	
	public function getCurrentRow()
	{
		return $this->currentRow;
	}
	
	public function ColumnNumberToColumnName($no)
	{
		$result = '';
		$prefA = intdiv($no,26);
		
		for($i=0;$i<$prefA;$i++)
			$result.='A';
		
		$result .= chr(65 + $no % 26);
		
		return $result;
	}
}

?>