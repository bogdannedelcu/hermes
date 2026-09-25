<?php

namespace Kendo\UI;

class TaskBoardEditable extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Adds TaskBoardEditableButton to the TaskBoardEditable.
    * @param \Kendo\UI\TaskBoardEditableButton|array,... $value one or more TaskBoardEditableButton to add.
    * @return \Kendo\UI\TaskBoardEditable
    */
    public function addButton($value) {
        return $this->add('buttons', func_get_args());
    }

    /**
    * The Kendo Form configuration for Card editing.
    * @param  $value
    * @return \Kendo\UI\TaskBoardEditable
    */
    public function form($value) {
        return $this->setProperty('form', $value);
    }

    /**
    * Sets the headerTemplate option of the TaskBoardEditable.
    * Controls the rendering of the header
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\TaskBoardEditable
    */
    public function headerTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('headerTemplate', $value);
    }

    /**
    * Sets the headerTemplate option of the TaskBoardEditable.
    * Controls the rendering of the header
    * @param string $value The template content.
    * @return \Kendo\UI\TaskBoardEditable
    */
    public function headerTemplate($value) {
        return $this->setProperty('headerTemplate', $value);
    }

//<< Properties
}

?>
