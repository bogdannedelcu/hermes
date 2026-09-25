<?php

namespace Kendo\Dataviz\UI;

class MapMessages extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Specifies alt attribute value for the Map tile <img> elements.
    * @param string $value
    * @return \Kendo\Dataviz\UI\MapMessages
    */
    public function tileTitle($value) {
        return $this->setProperty('tileTitle', $value);
    }

//<< Properties
}

?>
