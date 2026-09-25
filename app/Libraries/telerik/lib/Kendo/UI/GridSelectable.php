<?php

namespace Kendo\UI;

class GridSelectable extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Can be set to the following string values: "row" - the user can select a single row.; "cell" - the user can select a single cell.; "multiple, row" - the user can select multiple rows. or "multiple, cell" - the user can select multiple cells..
    * @param string $value
    * @return \Kendo\UI\GridSelectable
    */
    public function mode($value) {
        return $this->setProperty('mode', $value);
    }

    /**
    * When set to true, visually hidden elements that match by the filter option criteria but are overlapped by other elements that also can be selected, are ignored.
    * @param boolean $value
    * @return \Kendo\UI\GridSelectable
    */
    public function ignoreOverlapped($value) {
        return $this->setProperty('ignoreOverlapped', $value);
    }

//<< Properties
}

?>
