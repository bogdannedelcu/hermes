<?php

namespace Kendo\UI;

class CheckBoxGroupItem extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Collection of key-value pairs that would be used to generate the attributes attached to each k-checkbox-item element.
    * @param  $value
    * @return \Kendo\UI\CheckBoxGroupItem
    */
    public function attributes($value) {
        return $this->setProperty('attributes', $value);
    }

    /**
    * Any custom classes that should be attached to the k-checkbox-item element.
    * @param string $value
    * @return \Kendo\UI\CheckBoxGroupItem
    */
    public function cssClass($value) {
        return $this->setProperty('cssClass', $value);
    }

    /**
    * Defines whether the checkbox is enabled or not. By default all checkboxes are enabled.
    * @param boolean $value
    * @return \Kendo\UI\CheckBoxGroupItem
    */
    public function enabled($value) {
        return $this->setProperty('enabled', $value);
    }

    /**
    * Determines whether the checkbox label content should be rendered as an HTML string or it should be encoded.
    * @param boolean $value
    * @return \Kendo\UI\CheckBoxGroupItem
    */
    public function encoded($value) {
        return $this->setProperty('encoded', $value);
    }

    /**
    * Specifies the label content for the checkbox.
    * @param string $value
    * @return \Kendo\UI\CheckBoxGroupItem
    */
    public function label($value) {
        return $this->setProperty('label', $value);
    }

    /**
    * Specifies the value for the checkbox.
    * @param string $value
    * @return \Kendo\UI\CheckBoxGroupItem
    */
    public function value($value) {
        return $this->setProperty('value', $value);
    }

//<< Properties
}

?>
