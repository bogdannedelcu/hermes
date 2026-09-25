<?php

namespace Kendo\UI;

class ColorPickerMessages extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Allows customization of the "Apply" button text.
    * @param string $value
    * @return \Kendo\UI\ColorPickerMessages
    */
    public function apply($value) {
        return $this->setProperty('apply', $value);
    }

    /**
    * Allows customization of the "Cancel" button text.
    * @param string $value
    * @return \Kendo\UI\ColorPickerMessages
    */
    public function cancel($value) {
        return $this->setProperty('cancel', $value);
    }

    /**
    * Allows customization of the Clear Color button label.
    * @param string $value
    * @return \Kendo\UI\ColorPickerMessages
    */
    public function clearColor($value) {
        return $this->setProperty('clearColor', $value);
    }

    /**
    * Overrides the messages.hex property. Legacy option.
    * @param string $value
    * @return \Kendo\UI\ColorPickerMessages
    */
    public function previewInput($value) {
        return $this->setProperty('previewInput', $value);
    }

    /**
    * Allows customization of the "Contrast ratio" text in the contrast tool.
    * @param string $value
    * @return \Kendo\UI\ColorPickerMessages
    */
    public function contrastRatio($value) {
        return $this->setProperty('contrastRatio', $value);
    }

    /**
    * Allows customization of the "Fail" text in the contrast tool.
    * @param string $value
    * @return \Kendo\UI\ColorPickerMessages
    */
    public function fail($value) {
        return $this->setProperty('fail', $value);
    }

    /**
    * Allows customization of the "Pass" text in the contrast tool.
    * @param string $value
    * @return \Kendo\UI\ColorPickerMessages
    */
    public function pass($value) {
        return $this->setProperty('pass', $value);
    }

    /**
    * Allows customization of the Gradient view button.
    * @param string $value
    * @return \Kendo\UI\ColorPickerMessages
    */
    public function gradient($value) {
        return $this->setProperty('gradient', $value);
    }

    /**
    * Allows customization of the Palette view button.
    * @param string $value
    * @return \Kendo\UI\ColorPickerMessages
    */
    public function palette($value) {
        return $this->setProperty('palette', $value);
    }

    /**
    * Allows customization of the toggle format button's title in the Gradient's input editor.
    * @param string $value
    * @return \Kendo\UI\ColorPickerMessages
    */
    public function toggleFormat($value) {
        return $this->setProperty('toggleFormat', $value);
    }

    /**
    * Allows customization of the rgb's red input's aria-label in the Gradient's input editor.
    * @param string $value
    * @return \Kendo\UI\ColorPickerMessages
    */
    public function red($value) {
        return $this->setProperty('red', $value);
    }

    /**
    * Allows customization of the rgb's green input's aria-label in the Gradient's input editor.
    * @param string $value
    * @return \Kendo\UI\ColorPickerMessages
    */
    public function green($value) {
        return $this->setProperty('green', $value);
    }

    /**
    * Allows customization of the rgb's blue input's aria-label in the Gradient's input editor. ### messages.alpha String (default: "Alpha")Allows customization of the rgb's alpha input's aria-label in the Gradient's input editor.
    * @param string $value
    * @return \Kendo\UI\ColorPickerMessages
    */
    public function blue($value) {
        return $this->setProperty('blue', $value);
    }

    /**
    * Allows customization of the hex input's aria-label in the Gradient's input editor.
    * @param string $value
    * @return \Kendo\UI\ColorPickerMessages
    */
    public function hex($value) {
        return $this->setProperty('hex', $value);
    }

//<< Properties
}

?>
