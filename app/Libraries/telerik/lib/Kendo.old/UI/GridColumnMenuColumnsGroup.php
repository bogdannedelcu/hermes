<?php

namespace Kendo\UI;

class GridColumnMenuColumnsGroup extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The titles of the columns that are part of the group. In case some column does not have a specified title, you can use the field instead. Columns that don't have specified either a title or a field, are not displayed in the column menu.
    * @param array $value
    * @return \Kendo\UI\GridColumnMenuColumnsGroup
    */
    public function columns($value) {
        return $this->setProperty('columns', $value);
    }

    /**
    * The text displayed in the header of the group.
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuColumnsGroup
    */
    public function title($value) {
        return $this->setProperty('title', $value);
    }

//<< Properties
}

?>
