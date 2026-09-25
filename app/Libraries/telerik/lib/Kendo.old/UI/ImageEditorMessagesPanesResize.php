<?php

namespace Kendo\UI;

class ImageEditorMessagesPanesResize extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines the localization for the resize pane field.
    * @param string $value
    * @return \Kendo\UI\ImageEditorMessagesPanesResize
    */
    public function title($value) {
        return $this->setProperty('title', $value);
    }

    /**
    * Defines the localization for the resize pane field.
    * @param string $value
    * @return \Kendo\UI\ImageEditorMessagesPanesResize
    */
    public function pixels($value) {
        return $this->setProperty('pixels', $value);
    }

    /**
    * Defines the localization for the resize pane field.
    * @param string $value
    * @return \Kendo\UI\ImageEditorMessagesPanesResize
    */
    public function percents($value) {
        return $this->setProperty('percents', $value);
    }

//<< Properties
}

?>
