<?php

namespace Kendo\UI;

class ImageEditorMessagesPanesCrop extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines the localization for the crop pane field.
    * @param string $value
    * @return \Kendo\UI\ImageEditorMessagesPanesCrop
    */
    public function title($value) {
        return $this->setProperty('title', $value);
    }

    /**
    * Defines the localization for the crop pane field.
    * @param string $value
    * @return \Kendo\UI\ImageEditorMessagesPanesCrop
    */
    public function aspectRatio($value) {
        return $this->setProperty('aspectRatio', $value);
    }

    /**
    * Defines the localization for the crop pane field.
    * @param \Kendo\UI\ImageEditorMessagesPanesCropAspectRatioItems|array $value
    * @return \Kendo\UI\ImageEditorMessagesPanesCrop
    */
    public function aspectRatioItems($value) {
        return $this->setProperty('aspectRatioItems', $value);
    }

    /**
    * Defines the localization for the crop pane field.
    * @param string $value
    * @return \Kendo\UI\ImageEditorMessagesPanesCrop
    */
    public function orientation($value) {
        return $this->setProperty('orientation', $value);
    }

    /**
    * Defines the localization for the crop pane field.
    * @param string $value
    * @return \Kendo\UI\ImageEditorMessagesPanesCrop
    */
    public function portrait($value) {
        return $this->setProperty('portrait', $value);
    }

    /**
    * Defines the localization for the crop pane field.
    * @param string $value
    * @return \Kendo\UI\ImageEditorMessagesPanesCrop
    */
    public function landscape($value) {
        return $this->setProperty('landscape', $value);
    }

//<< Properties
}

?>
