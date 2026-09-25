<?php

namespace Kendo\UI;

class TaskBoardPreviewPane extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Adds TaskBoardPreviewPaneButton to the TaskBoardPreviewPane.
    * @param \Kendo\UI\TaskBoardPreviewPaneButton|array,... $value one or more TaskBoardPreviewPaneButton to add.
    * @return \Kendo\UI\TaskBoardPreviewPane
    */
    public function addButton($value) {
        return $this->add('buttons', func_get_args());
    }

    /**
    * Sets the template option of the TaskBoardPreviewPane.
    * The template rendering of the preview pane.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\TaskBoardPreviewPane
    */
    public function templateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('template', $value);
    }

    /**
    * Sets the template option of the TaskBoardPreviewPane.
    * The template rendering of the preview pane.
    * @param string $value The template content.
    * @return \Kendo\UI\TaskBoardPreviewPane
    */
    public function template($value) {
        return $this->setProperty('template', $value);
    }

    /**
    * Sets the headerTemplate option of the TaskBoardPreviewPane.
    * The template rendering of the header for the preview pane.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\TaskBoardPreviewPane
    */
    public function headerTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('headerTemplate', $value);
    }

    /**
    * Sets the headerTemplate option of the TaskBoardPreviewPane.
    * The template rendering of the header for the preview pane.
    * @param string $value The template content.
    * @return \Kendo\UI\TaskBoardPreviewPane
    */
    public function headerTemplate($value) {
        return $this->setProperty('headerTemplate', $value);
    }

//<< Properties
}

?>
