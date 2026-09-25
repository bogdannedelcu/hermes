<?php

namespace Kendo\UI;

class GridColumnExportable extends \Kendo\SerializableObject {
//>> Properties

    /**
    * If set to false the column will be excluded from the exported Excel file.
    * @param boolean $value
    * @return \Kendo\UI\GridColumnExportable
    */
    public function excel($value) {
        return $this->setProperty('excel', $value);
    }

    /**
    * If set to false the column will be excluded from the exported PDF file.
    * @param boolean $value
    * @return \Kendo\UI\GridColumnExportable
    */
    public function pdf($value) {
        return $this->setProperty('pdf', $value);
    }

//<< Properties
}

?>
