<?php

namespace Kendo\UI;

class TreeListEditableMove extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Enables reordering of rows via a drag-and-drop UI.
    * @param boolean $value
    * @return \Kendo\UI\TreeListEditableMove
    */
    public function reorderable($value) {
        return $this->setProperty('reorderable', $value);
    }

//<< Properties
}

?>
