<?php

namespace Kendo\UI;

class RadioGroupItem extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Collection of key-value pairs that would be used to generate the attributes attached to each k-radio-item element.
    * @param  $value
    * @return \Kendo\UI\RadioGroupItem
    */
    public function attributes($value) {
        return $this->setProperty('attributes', $value);
    }

    /**
    * Custom class that would be set on the respective k-radio-item element.
    * @param string $value
    * @return \Kendo\UI\RadioGroupItem
    */
    public function cssClass($value) {
        return $this->setProperty('cssClass', $value);
    }

    /**
    * Defines whether the radio button is enabled or not. By default all radio buttons are enabled.
    * @param boolean $value
    * @return \Kendo\UI\RadioGroupItem
    */
    public function enabled($value) {
        return $this->setProperty('enabled', $value);
    }

    /**
    * Determines whether the radio button label content should be rendered as an HTML string or it should be encoded.
    * @param boolean $value
    * @return \Kendo\UI\RadioGroupItem
    */
    public function encoded($value) {
        return $this->setProperty('encoded', $value);
    }

    /**
    * Specifies the label content for the radio button.
    * @param string $value
    * @return \Kendo\UI\RadioGroupItem
    */
    public function label($value) {
        return $this->setProperty('label', $value);
    }

    /**
    * Specifies the value for the radio button.
    * @param string $value
    * @return \Kendo\UI\RadioGroupItem
    */
    public function value($value) {
        return $this->setProperty('value', $value);
    }

//<< Properties
}

?>
