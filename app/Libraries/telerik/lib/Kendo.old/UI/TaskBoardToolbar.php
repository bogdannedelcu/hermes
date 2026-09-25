<?php

namespace Kendo\UI;

class TaskBoardToolbar extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Adds TaskBoardToolbarItem to the TaskBoardToolbar.
    * @param \Kendo\UI\TaskBoardToolbarItem|array,... $value one or more TaskBoardToolbarItem to add.
    * @return \Kendo\UI\TaskBoardToolbar
    */
    public function addItem($value) {
        return $this->add('items', func_get_args());
    }

//<< Properties
}

?>
