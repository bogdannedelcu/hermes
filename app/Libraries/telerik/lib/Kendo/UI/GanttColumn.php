<?php

namespace Kendo\UI;

class GanttColumn extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The HTML attributes of the table cell (<td>) that is rendered for the column.
    * @param  $value
    * @return \Kendo\UI\GanttColumn
    */
    public function attributes($value) {
        return $this->setProperty('attributes', $value);
    }

    /**
    * Specifies whether this column can be edited by the user.
    * @param boolean $value
    * @return \Kendo\UI\GanttColumn
    */
    public function editable($value) {
        return $this->setProperty('editable', $value);
    }

    /**
    * Sets the editor option of the GanttColumn.
    * Provides a way to specify a custom editing UI for the column. To create the editing UI, use the container parameter.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\GanttColumn
    */
    public function editor($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('editor', $value);
    }

    /**
    * If set to true, the column will show the icons that are used for expanding and collapsing child rows. By default, the "title" column of the Gantt is expandable.
    * @param boolean $value
    * @return \Kendo\UI\GanttColumn
    */
    public function expandable($value) {
        return $this->setProperty('expandable', $value);
    }

    /**
    * The field to which the column is bound. The value of this field is displayed by the column during data binding.The field name should be a valid Javascript identifier and should contain only alphanumeric characters (or "$" or "_"), and may not start with a digit.
    * @param string $value
    * @return \Kendo\UI\GanttColumn
    */
    public function field($value) {
        return $this->setProperty('field', $value);
    }

    /**
    * If set to true and if filtering is enabled for the entire Gantt, a filter menu will be displayed for this column. If set to false, the filter menu will not be displayed. By default, a filter menu is displayed for all columns when filtering is enabled through the filterable option. Can be set to a JavaScript object which represents the filter menu configuration.
    * @param boolean|\Kendo\UI\GanttColumnFilterable|array $value
    * @return \Kendo\UI\GanttColumn
    */
    public function filterable($value) {
        return $this->setProperty('filterable', $value);
    }

    /**
    * The format that is applied to the value before it is displayed. Takes the form "{0:format}" where "format" is a standard number format,custom number format, standard date format or a custom date format.
    * @param string $value
    * @return \Kendo\UI\GanttColumn
    */
    public function format($value) {
        return $this->setProperty('format', $value);
    }

    /**
    * The HTML attributes of the table header cell (<th>) that is rendered for the column.
    * @param  $value
    * @return \Kendo\UI\GanttColumn
    */
    public function headerAttributes($value) {
        return $this->setProperty('headerAttributes', $value);
    }

    /**
    * Sets the headerTemplate option of the GanttColumn.
    * The template which renders the column header content. By default, the value of the title column option is displayed in the column header cell.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\GanttColumn
    */
    public function headerTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('headerTemplate', $value);
    }

    /**
    * Sets the headerTemplate option of the GanttColumn.
    * The template which renders the column header content. By default, the value of the title column option is displayed in the column header cell.
    * @param string $value The template content.
    * @return \Kendo\UI\GanttColumn
    */
    public function headerTemplate($value) {
        return $this->setProperty('headerTemplate', $value);
    }

    /**
    * If set to true, the Gantt will not display the column. By default, all columns are displayed.
    * @param boolean $value
    * @return \Kendo\UI\GanttColumn
    */
    public function hidden($value) {
        return $this->setProperty('hidden', $value);
    }

    /**
    * If set to true, the Gantt will display the column in the column menu. By default, the column menu includes all data-bound columns, that is, the ones with a set field option.
    * @param boolean $value
    * @return \Kendo\UI\GanttColumn
    */
    public function menu($value) {
        return $this->setProperty('menu', $value);
    }

    /**
    * The pixel screen width below which the column will be hidden. The setting takes precedence over the hidden setting and the two cannot not be used at the same time.
    * @param float $value
    * @return \Kendo\UI\GanttColumn
    */
    public function minScreenWidth($value) {
        return $this->setProperty('minScreenWidth', $value);
    }

    /**
    * If set to true the user could sort this column by clicking its header cells. By default sorting is disabled.
    * @param boolean|\Kendo\UI\GanttColumnSortable|array $value
    * @return \Kendo\UI\GanttColumn
    */
    public function sortable($value) {
        return $this->setProperty('sortable', $value);
    }

    /**
    * Sets the template option of the GanttColumn.
    * The template which renders the column content. The Gantt renders table rows (<tr>) which represent the data source items. Each table row consists of table cells (<td>) which represent the GanttList columns. By default, the HTML-encoded value of the field is displayed in the column.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\GanttColumn
    */
    public function templateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('template', $value);
    }

    /**
    * Sets the template option of the GanttColumn.
    * The template which renders the column content. The Gantt renders table rows (<tr>) which represent the data source items. Each table row consists of table cells (<td>) which represent the GanttList columns. By default, the HTML-encoded value of the field is displayed in the column.
    * @param string $value The template content.
    * @return \Kendo\UI\GanttColumn
    */
    public function template($value) {
        return $this->setProperty('template', $value);
    }

    /**
    * The text that is displayed in the column header cell. If not set the field is used.
    * @param string $value
    * @return \Kendo\UI\GanttColumn
    */
    public function title($value) {
        return $this->setProperty('title', $value);
    }

    /**
    * The width of the column. Numeric values are treated as pixels.
    * @param string|float $value
    * @return \Kendo\UI\GanttColumn
    */
    public function width($value) {
        return $this->setProperty('width', $value);
    }

//<< Properties

    /**
    * Adds nested GanttColumn to the current column.
    * @param \Kendo\UI\GanttColumn|array,... $value one or more GanttColumn to add.
    * @return \Kendo\UI\Gantt
    */
    public function addColumn($value) {
        return $this->add('columns', func_get_args());
    }
}

?>
