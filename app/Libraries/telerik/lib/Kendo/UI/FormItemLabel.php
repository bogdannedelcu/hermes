<?php

namespace Kendo\UI;

class FormItemLabel extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines the text of the label.
    * @param string $value
    * @return \Kendo\UI\FormItemLabel
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }

    /**
    * Specifies if the field is optional by rendering additional text next to the label.
    * @param boolean $value
    * @return \Kendo\UI\FormItemLabel
    */
    public function optional($value) {
        return $this->setProperty('optional', $value);
    }

    /**
    * Specifies if the label text will HTML-encoded before it is displayed. If set to false the label text will be displayed as is.
    * @param boolean $value
    * @return \Kendo\UI\FormItemLabel
    */
    public function encoded($value) {
        return $this->setProperty('encoded', $value);
    }

//<< Properties
}

?>
