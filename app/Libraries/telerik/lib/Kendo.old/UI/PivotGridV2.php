<?php

namespace Kendo\UI;

class PivotGridV2 extends \Kendo\UI\Widget {
    public function name() {
        return 'PivotGridV2';
    }
//>> Properties

    /**
    * Sets the data source of the PivotGridV2.
    * @param array|\Kendo\Data\DataSource $value
    * @return \Kendo\UI\PivotGridV2
    */
    public function dataSource($value) {
        return $this->setProperty('dataSource', $value);
    }

    /**
    * If set to false the widget will not bind to the data source during initialization. In this case data binding will occur when the change event of the data source is fired. By default the widget will bind to the data source specified in the configuration.
    * @param boolean $value
    * @return \Kendo\UI\PivotGridV2
    */
    public function autoBind($value) {
        return $this->setProperty('autoBind', $value);
    }

    /**
    * Configures the Kendo UI PivotGridV2 PDF export settings.
    * @param \Kendo\UI\PivotGridV2Pdf|array $value
    * @return \Kendo\UI\PivotGridV2
    */
    public function pdf($value) {
        return $this->setProperty('pdf', $value);
    }

    /**
    * The width of the table columns. Value is treated as pixels.
    * @param float $value
    * @return \Kendo\UI\PivotGridV2
    */
    public function columnWidth($value) {
        return $this->setProperty('columnWidth', $value);
    }

    /**
    * The height of the PivotGridV2. Numeric values are treated as pixels.
    * @param float|string $value
    * @return \Kendo\UI\PivotGridV2
    */
    public function height($value) {
        return $this->setProperty('height', $value);
    }

    /**
    * Sets the columnHeaderTemplate option of the PivotGridV2.
    * The template which renders the content of the column header cell. By default it renders the caption of the tuple member.The fields which can be used in the template are: member - the member of the corresponding column header cell or tuple - the tuple of the corresponding column header cell. For information about the tuple structure check this link.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\PivotGridV2
    */
    public function columnHeaderTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('columnHeaderTemplate', $value);
    }

    /**
    * Sets the columnHeaderTemplate option of the PivotGridV2.
    * The template which renders the content of the column header cell. By default it renders the caption of the tuple member.The fields which can be used in the template are: member - the member of the corresponding column header cell or tuple - the tuple of the corresponding column header cell. For information about the tuple structure check this link.
    * @param string $value The template content.
    * @return \Kendo\UI\PivotGridV2
    */
    public function columnHeaderTemplate($value) {
        return $this->setProperty('columnHeaderTemplate', $value);
    }

    /**
    * Sets the dataCellTemplate option of the PivotGridV2.
    * The template which renders the content of the data cell. By default renders the formatted value (fmtValue) of the data item.The fields which can be used in the template are: columnTuple - the tuple of the corresponding column header cell; rowTuple - the tuple of the corresponding row header cell; measure - the value of the data cell measure or dataItem - the data item itself. For information about the tuple structure check this link. About the data item structure review this help topic.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\PivotGridV2
    */
    public function dataCellTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('dataCellTemplate', $value);
    }

    /**
    * Sets the dataCellTemplate option of the PivotGridV2.
    * The template which renders the content of the data cell. By default renders the formatted value (fmtValue) of the data item.The fields which can be used in the template are: columnTuple - the tuple of the corresponding column header cell; rowTuple - the tuple of the corresponding row header cell; measure - the value of the data cell measure or dataItem - the data item itself. For information about the tuple structure check this link. About the data item structure review this help topic.
    * @param string $value The template content.
    * @return \Kendo\UI\PivotGridV2
    */
    public function dataCellTemplate($value) {
        return $this->setProperty('dataCellTemplate', $value);
    }

    /**
    * Sets the rowHeaderTemplate option of the PivotGridV2.
    * The template which renders the content of the row header cell. By default it renders the caption of the tuple member.The fields which can be used in the template are: member - the member of the corresponding row header cell or tuple - the tuple of the corresponding row header cell. For information about the tuple structure check this link.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\PivotGridV2
    */
    public function rowHeaderTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('rowHeaderTemplate', $value);
    }

    /**
    * Sets the rowHeaderTemplate option of the PivotGridV2.
    * The template which renders the content of the row header cell. By default it renders the caption of the tuple member.The fields which can be used in the template are: member - the member of the corresponding row header cell or tuple - the tuple of the corresponding row header cell. For information about the tuple structure check this link.
    * @param string $value The template content.
    * @return \Kendo\UI\PivotGridV2
    */
    public function rowHeaderTemplate($value) {
        return $this->setProperty('rowHeaderTemplate', $value);
    }

    /**
    * Sets the dataBinding event of the PivotGridV2.
    * Fired before the widget binds to its data source.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\PivotGridV2
    */
    public function dataBinding($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('dataBinding', $value);
    }

    /**
    * Sets the dataBound event of the PivotGridV2.
    * Fired after the widget is bound to the data from its data source.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\PivotGridV2
    */
    public function dataBound($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('dataBound', $value);
    }

    /**
    * Sets the expandMember event of the PivotGridV2.
    * Fired before column or row field is expanded.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\PivotGridV2
    */
    public function expandMember($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('expandMember', $value);
    }

    /**
    * Sets the collapseMember event of the PivotGridV2.
    * Fired before column or row field is collapsed.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\PivotGridV2
    */
    public function collapseMember($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('collapseMember', $value);
    }

    /**
    * Sets the pdfExport event of the PivotGridV2.
    * Fired when the user clicks the "Export to PDF" toolbar button.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\PivotGridV2
    */
    public function pdfExport($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('pdfExport', $value);
    }


//<< Properties
    /**
    * Set the selector to PivotConfigurator.
    */
    public function configurator($selector) {
        return $this->setProperty('configurator', $selector);
    }
}

?>
