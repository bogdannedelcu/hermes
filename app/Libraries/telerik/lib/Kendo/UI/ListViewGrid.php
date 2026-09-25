<?php

namespace Kendo\UI;

class ListViewGrid extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines the columns of the grid.
    * @param float|string $value
    * @return \Kendo\UI\ListViewGrid
    */
    public function cols($value) {
        return $this->setProperty('cols', $value);
    }

    /**
    * Defines the rows of the grid.
    * @param float|string $value
    * @return \Kendo\UI\ListViewGrid
    */
    public function rows($value) {
        return $this->setProperty('rows', $value);
    }

    /**
    * Defines the width of the gutters between the columns / rows.
    * @param float|string $value
    * @return \Kendo\UI\ListViewGrid
    */
    public function gutter($value) {
        return $this->setProperty('gutter', $value);
    }

//<< Properties
}

?>
