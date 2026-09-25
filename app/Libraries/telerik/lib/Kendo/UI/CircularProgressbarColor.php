<?php

namespace Kendo\UI;

class CircularProgressbarColor extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The color of the pointer in the specified range.
    * @param string $value
    * @return \Kendo\UI\CircularProgressbarColor
    */
    public function color($value) {
        return $this->setProperty('color', $value);
    }

    /**
    * The lower range value of the applied color.
    * @param float $value
    * @return \Kendo\UI\CircularProgressbarColor
    */
    public function from($value) {
        return $this->setProperty('from', $value);
    }

    /**
    * The upper range value of the applied color.
    * @param float $value
    * @return \Kendo\UI\CircularProgressbarColor
    */
    public function to($value) {
        return $this->setProperty('to', $value);
    }

//<< Properties
}

?>
