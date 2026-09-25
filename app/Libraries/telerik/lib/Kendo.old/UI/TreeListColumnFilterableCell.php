<?php

namespace Kendo\UI;

class TreeListColumnFilterableCell extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Sets the data source of the TreeListColumnFilterableCell.
    * @param array|\Kendo\Data\DataSource $value
    * @return \Kendo\UI\TreeListColumnFilterableCell
    */
    public function dataSource($value) {
        return $this->setProperty('dataSource', $value);
    }

    /**
    * Specifies the name of the field which will provide the text representation for the AutoComplete suggestion (when using String type column) when CustomDataSource is provided. By default the name of the field bound to the column will be used.
    * @param string $value
    * @return \Kendo\UI\TreeListColumnFilterableCell
    */
    public function dataTextField($value) {
        return $this->setProperty('dataTextField', $value);
    }

    /**
    * Specifies the delay of the AutoComplete widget which will provide the suggest functionality (when using String type column).
    * @param float $value
    * @return \Kendo\UI\TreeListColumnFilterableCell
    */
    public function delay($value) {
        return $this->setProperty('delay', $value);
    }

    /**
    * Specifies the width of the input before it is initialized or turned into a widget. Provides convenient way to set the width according to the column width.
    * @param float $value
    * @return \Kendo\UI\TreeListColumnFilterableCell
    */
    public function inputWidth($value) {
        return $this->setProperty('inputWidth', $value);
    }

    /**
    * Specifies the AutoComplete filter option. The possible values are the same as the ones for the AutoComplete filter option - "startswith", "endswith", "contains". The "contains" operator performs a case-insensitive search. To perform a case-sensitive filtering, set a custom filtering function through the dataSource.filter.operator option.
    * @param string $value
    * @return \Kendo\UI\TreeListColumnFilterableCell
    */
    public function suggestionOperator($value) {
        return $this->setProperty('suggestionOperator', $value);
    }

    /**
    * Specifies the minLength option of the AutoComplete widget when column is of type string.
    * @param float $value
    * @return \Kendo\UI\TreeListColumnFilterableCell
    */
    public function minLength($value) {
        return $this->setProperty('minLength', $value);
    }

    /**
    * When set to false the TreeList will not render the cell filtering widget for that specific column.
    * @param boolean $value
    * @return \Kendo\UI\TreeListColumnFilterableCell
    */
    public function enabled($value) {
        return $this->setProperty('enabled', $value);
    }

    /**
    * Specifies the default operator that will be used for the cell filtering.
    * @param string $value
    * @return \Kendo\UI\TreeListColumnFilterableCell
    */
    public function operator($value) {
        return $this->setProperty('operator', $value);
    }

    /**
    * Specifies whether to show or hide the DropDownList with the operators.
    * @param boolean $value
    * @return \Kendo\UI\TreeListColumnFilterableCell
    */
    public function showOperators($value) {
        return $this->setProperty('showOperators', $value);
    }

    /**
    * Sets the template option of the TreeListColumnFilterableCell.
    * JavaScript function which will customize how the input for the filter value is rendered. The function receives an object argument with two fields: element - the default input inside the filter cell; or dataSource - a Kendo UI DataSource instance, which has the same settings as the TreeList dataSource, but will only contain data items with unique values for the current column. This instance is also used by the default AutoComplete widget, which is used inside the filter cell if no template is set. Keep in mind that the passed dataSource instance may still not be populated at the time the template function is called, if the TreeList uses remote binding..
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TreeListColumnFilterableCell
    */
    public function template($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('template', $value);
    }

//<< Properties
}

?>
