<?php

namespace Kendo\UI;

class RadioGroup extends \Kendo\UI\Widget {
    public function name() {
        return 'RadioGroup';
    }
//>> Properties

    /**
    * Sets the enabled state of all radio buttons in the RadioGroup.
    * @param boolean $value
    * @return \Kendo\UI\RadioGroup
    */
    public function enabled($value) {
        return $this->setProperty('enabled', $value);
    }

    /**
    * The name attribute to be used for the radio inputs. If omitted, the id of the wrapper element will be used.
    * @param string $value
    * @return \Kendo\UI\RadioGroup
    */
    public function inputName($value) {
        return $this->setProperty('inputName', $value);
    }

    /**
    * Sets a value controlling the size of the radio inputs. Can also be set to the following string values: "small"; "medium"; "large" or null.
    * @param string $value
    * @return \Kendo\UI\RadioGroup
    */
    public function inputSize($value) {
        return $this->setProperty('inputSize', $value);
    }

    /**
    * Adds RadioGroupItem to the RadioGroup.
    * @param \Kendo\UI\RadioGroupItem|array,... $value one or more RadioGroupItem to add.
    * @return \Kendo\UI\RadioGroup
    */
    public function addItem($value) {
        return $this->add('items', func_get_args());
    }

    /**
    * The selected (checked) radio button value. Will also be used as a RadioGroup widget value.
    * @param string $value
    * @return \Kendo\UI\RadioGroup
    */
    public function value($value) {
        return $this->setProperty('value', $value);
    }

    /**
    * Specifies whether the radio buttons will be rendered one below the other ("vertical") or on the same line ("horizontal").
    * @param string $value
    * @return \Kendo\UI\RadioGroup
    */
    public function layout($value) {
        return $this->setProperty('layout', $value);
    }

    /**
    * Specifies the label position according to its radio button for all items in the widget. Accepts "before" and "after".
    * @param string $value
    * @return \Kendo\UI\RadioGroup
    */
    public function labelPosition($value) {
        return $this->setProperty('labelPosition', $value);
    }

    /**
    * Sets the change event of the RadioGroup.
    * Fires when the selected radio input in the RadioGroup is changed through user interaction.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\RadioGroup
    */
    public function change($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('change', $value);
    }

    /**
    * Sets the focus event of the RadioGroup.
    * Fires when a radio input in the RadioGroup is focused through user interaction.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\RadioGroup
    */
    public function focus($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('focus', $value);
    }

    /**
    * Sets the select event of the RadioGroup.
    * Fires when a radio input is clicked to be selected through user interaction. If prevented, the clicked input will not be selected.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\RadioGroup
    */
    public function select($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('select', $value);
    }


//<< Properties
}

?>
