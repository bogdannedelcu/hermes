<?php

namespace Kendo\UI;

class TaskBoardColumnSettings extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Adds TaskBoardColumnSettingsButton to the TaskBoardColumnSettings.
    * @param \Kendo\UI\TaskBoardColumnSettingsButton|array,... $value one or more TaskBoardColumnSettingsButton to add.
    * @return \Kendo\UI\TaskBoardColumnSettings
    */
    public function addButton($value) {
        return $this->add('buttons', func_get_args());
    }

    /**
    * The field of the data item that provides the status of the column. Mapped with the status of the cards.
    * @param string $value
    * @return \Kendo\UI\TaskBoardColumnSettings
    */
    public function dataStatusField($value) {
        return $this->setProperty('dataStatusField', $value);
    }

    /**
    * The text field of the column.
    * @param string $value
    * @return \Kendo\UI\TaskBoardColumnSettings
    */
    public function dataTextField($value) {
        return $this->setProperty('dataTextField', $value);
    }

    /**
    * The field used to order columns (number based). Automatically adds sorting to the columns DataSource instance.If not set, columns will be rendered in the order they are fetched. And ordering will not be applied to the DataSource and respectively, not synced with the remote data source.
    * @param string $value
    * @return \Kendo\UI\TaskBoardColumnSettings
    */
    public function dataOrderField($value) {
        return $this->setProperty('dataOrderField', $value);
    }

    /**
    * Configures the width of the columns
    * @param string|float $value
    * @return \Kendo\UI\TaskBoardColumnSettings
    */
    public function width($value) {
        return $this->setProperty('width', $value);
    }

    /**
    * Sets the template option of the TaskBoardColumnSettings.
    * Controls the rendering of the column header. In the template context the buttons field provides the HTML for the buttons of the column.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\TaskBoardColumnSettings
    */
    public function templateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('template', $value);
    }

    /**
    * Sets the template option of the TaskBoardColumnSettings.
    * Controls the rendering of the column header. In the template context the buttons field provides the HTML for the buttons of the column.
    * @param string $value The template content.
    * @return \Kendo\UI\TaskBoardColumnSettings
    */
    public function template($value) {
        return $this->setProperty('template', $value);
    }

//<< Properties
}

?>
