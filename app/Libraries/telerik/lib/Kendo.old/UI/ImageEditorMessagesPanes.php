<?php

namespace Kendo\UI;

class ImageEditorMessagesPanes extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines the localization for the crop pane.
    * @param \Kendo\UI\ImageEditorMessagesPanesCrop|array $value
    * @return \Kendo\UI\ImageEditorMessagesPanes
    */
    public function crop($value) {
        return $this->setProperty('crop', $value);
    }

    /**
    * Defines the localization for the resize pane.
    * @param \Kendo\UI\ImageEditorMessagesPanesResize|array $value
    * @return \Kendo\UI\ImageEditorMessagesPanes
    */
    public function resize($value) {
        return $this->setProperty('resize', $value);
    }

//<< Properties
}

?>
