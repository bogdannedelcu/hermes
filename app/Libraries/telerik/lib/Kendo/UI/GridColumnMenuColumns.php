<?php

namespace Kendo\UI;

class GridColumnMenuColumns extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The sort order which will be applied over the columns list. By default, the columns menu items are in the same order as the columns in the grid.The supported values are: "asc" (ascending order) or "desc" (descending order).
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuColumns
    */
    public function sort($value) {
        return $this->setProperty('sort', $value);
    }

    /**
    * Adds GridColumnMenuColumnsGroup to the GridColumnMenuColumns.
    * @param \Kendo\UI\GridColumnMenuColumnsGroup|array,... $value one or more GridColumnMenuColumnsGroup to add.
    * @return \Kendo\UI\GridColumnMenuColumns
    */
    public function addGroup($value) {
        return $this->add('groups', func_get_args());
    }

//<< Properties
}

?>
