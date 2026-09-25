<?php

namespace Kendo\UI;

class FormGrid extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines the columns of the grid.
    * @param float|string $value
    * @return \Kendo\UI\FormGrid
    */
    public function cols($value) {
        return $this->setProperty('cols', $value);
    }

    /**
    * Defines the width of the gutters between the columns / rows.
    * @param float|string $value
    * @return \Kendo\UI\FormGrid
    */
    public function gutter($value) {
        return $this->setProperty('gutter', $value);
    }

//<< Properties
}

?>
