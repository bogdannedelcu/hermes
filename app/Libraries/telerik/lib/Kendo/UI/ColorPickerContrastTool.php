<?php

namespace Kendo\UI;

class ColorPickerContrastTool extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Sets the background color for the contrast tool in the ColorGradient.
    * @param string| $value
    * @return \Kendo\UI\ColorPickerContrastTool
    */
    public function backgroundColor($value) {
        return $this->setProperty('backgroundColor', $value);
    }

//<< Properties
}

?>
