<?php

namespace Kendo\UI;

class FloatingActionButtonAlignOffset extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Specifies the initial horizontal offset of the FloatingActionButton. Numeric values are treated as pixels. String values can specify pixels, percentages, ems, or other valid values.
    * @param float|string $value
    * @return \Kendo\UI\FloatingActionButtonAlignOffset
    */
    public function x($value) {
        return $this->setProperty('x', $value);
    }

    /**
    * Specifies the initial vertical offset of the FloatingActionButton. Numeric values are treated as pixels. String values can specify pixels, percentages, ems or other valid values.
    * @param float|string $value
    * @return \Kendo\UI\FloatingActionButtonAlignOffset
    */
    public function y($value) {
        return $this->setProperty('y', $value);
    }

//<< Properties
}

?>
