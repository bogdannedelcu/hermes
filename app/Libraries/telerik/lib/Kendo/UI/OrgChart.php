<?php

namespace Kendo\UI;

class OrgChart extends \Kendo\UI\Widget {
    public function name() {
        return 'OrgChart';
    }
//>> Properties

    /**
    * An array of strings defining the cards top-border color for each level starting from the top level. If not set, the colors from the Kendo Chart series will be used.
    * @param array $value
    * @return \Kendo\UI\OrgChart
    */
    public function cardsColors($value) {
        return $this->setProperty('cardsColors', $value);
    }

    /**
    * Sets the data source of the OrgChart.
    * @param array|\Kendo\Data\DataSource $value
    * @return \Kendo\UI\OrgChart
    */
    public function dataSource($value) {
        return $this->setProperty('dataSource', $value);
    }

    /**
    * If set to false, the user will not be able to edit the data to which the OrgChart is bound. By default, editing is enabled.The editable option can also be set to a JavaScript object (which represents the editing configuration).
    * @param boolean|\Kendo\UI\OrgChartEditable|array $value
    * @return \Kendo\UI\OrgChart
    */
    public function editable($value) {
        return $this->setProperty('editable', $value);
    }

    /**
    * Specifies the field the nodes should be grouped by. If any value is passed, the OrgChart uses its grouping rendering mode.
    * @param string $value
    * @return \Kendo\UI\OrgChart
    */
    public function groupField($value) {
        return $this->setProperty('groupField', $value);
    }

    /**
    * Sets the groupHeaderTemplate option of the OrgChart.
    * Provides an option to customize the default template for the group label (using the field value and name by default).
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\OrgChart
    */
    public function groupHeaderTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('groupHeaderTemplate', $value);
    }

    /**
    * Sets the groupHeaderTemplate option of the OrgChart.
    * Provides an option to customize the default template for the group label (using the field value and name by default).
    * @param string $value The template content.
    * @return \Kendo\UI\OrgChart
    */
    public function groupHeaderTemplate($value) {
        return $this->setProperty('groupHeaderTemplate', $value);
    }

    /**
    * Provides configuration options for the messages present in the OrgChart widget.
    * @param \Kendo\UI\OrgChartMessages|array $value
    * @return \Kendo\UI\OrgChart
    */
    public function messages($value) {
        return $this->setProperty('messages', $value);
    }

    /**
    * Sets the template option of the OrgChart.
    * Defines a node card template.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\OrgChart
    */
    public function templateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('template', $value);
    }

    /**
    * Sets the template option of the OrgChart.
    * Defines a node card template.
    * @param string $value The template content.
    * @return \Kendo\UI\OrgChart
    */
    public function template($value) {
        return $this->setProperty('template', $value);
    }

    /**
    * Sets the cancel event of the OrgChart.
    * Triggered when the user is about to cancel the changes for the currently edited node. Preventable.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\OrgChart
    */
    public function cancel($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('cancel', $value);
    }

    /**
    * Sets the change event of the OrgChart.
    * Triggered when the selected node has been changed upon user interaction.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\OrgChart
    */
    public function change($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('change', $value);
    }

    /**
    * Sets the collapse event of the OrgChart.
    * Triggered before a node has been collapsed upon user interaction. Preventable.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\OrgChart
    */
    public function collapse($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('collapse', $value);
    }

    /**
    * Sets the create event of the OrgChart.
    * Triggered when a node is about to be created upon user interaction. Preventable.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\OrgChart
    */
    public function create($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('create', $value);
    }

    /**
    * Sets the dataBinding event of the OrgChart.
    * Triggered before the actual change in the dataSource occurs. Preventable.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\OrgChart
    */
    public function dataBinding($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('dataBinding', $value);
    }

    /**
    * Sets the dataBound event of the OrgChart.
    * Triggered after the dataSource change event has been processed (adding/removing/loading/editing items).
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\OrgChart
    */
    public function dataBound($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('dataBound', $value);
    }

    /**
    * Sets the delete event of the OrgChart.
    * Triggered when a node is about to be deleted as of a consequence of user interaction. Preventable.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\OrgChart
    */
    public function delete($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('delete', $value);
    }

    /**
    * Sets the edit event of the OrgChart.
    * Triggered when a node is about to enter edit mode upon user interaction. Preventable.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\OrgChart
    */
    public function edit($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('edit', $value);
    }

    /**
    * Sets the expand event of the OrgChart.
    * Triggered before a node has been expanded upon user interaction. Preventable.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\OrgChart
    */
    public function expand($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('expand', $value);
    }

    /**
    * Sets the save event of the OrgChart.
    * Triggered when the user attempts to save the current changes on the edited node. Preventable.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\OrgChart
    */
    public function save($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('save', $value);
    }

    /**
    * Sets the select event of the OrgChart.
    * Triggered when the user attempts to select a new node or group of nodes (in grouped scenario). Preventable.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\OrgChart
    */
    public function select($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('select', $value);
    }


//<< Properties
}

?>
