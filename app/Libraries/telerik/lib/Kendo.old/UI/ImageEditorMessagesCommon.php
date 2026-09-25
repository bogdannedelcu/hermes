<?php

namespace Kendo\UI;

class ImageEditorMessagesCommon extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines the localization for the common field.
    * @param string $value
    * @return \Kendo\UI\ImageEditorMessagesCommon
    */
    public function width($value) {
        return $this->setProperty('width', $value);
    }

    /**
    * Defines the localization for the common field.
    * @param string $value
    * @return \Kendo\UI\ImageEditorMessagesCommon
    */
    public function height($value) {
        return $this->setProperty('height', $value);
    }

    /**
    * Defines the localization for the common field.
    * @param string $value
    * @return \Kendo\UI\ImageEditorMessagesCommon
    */
    public function cancel($value) {
        return $this->setProperty('cancel', $value);
    }

    /**
    * Defines the localization for the common field.
    * @param string $value
    * @return \Kendo\UI\ImageEditorMessagesCommon
    */
    public function confirm($value) {
        return $this->setProperty('confirm', $value);
    }

    /**
    * Defines the localization for the common field.
    * @param string $value
    * @return \Kendo\UI\ImageEditorMessagesCommon
    */
    public function lockAspectRatio($value) {
        return $this->setProperty('lockAspectRatio', $value);
    }

//<< Properties
}

?>
