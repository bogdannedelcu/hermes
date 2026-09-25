<?php

namespace Kendo\UI;

class TileLayoutGap extends \Kendo\SerializableObject {
//>> Properties

    /**
    * A value in pixels determining the space between horizontal spacing between the layout items.
    * @param float $value
    * @return \Kendo\UI\TileLayoutGap
    */
    public function columns($value) {
        return $this->setProperty('columns', $value);
    }

    /**
    * A value in pixels determining the space between vertical spacing between the layout items.
    * @param float $value
    * @return \Kendo\UI\TileLayoutGap
    */
    public function rows($value) {
        return $this->setProperty('rows', $value);
    }

//<< Properties
}

?>
