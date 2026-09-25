<?php

namespace Kendo\UI;

class CheckBoxGroup extends \Kendo\UI\Widget {
    public function name() {
        return 'CheckBoxGroup';
    }
//>> Properties

    /**
    * Sets the enabled state of all checkboxes in the CheckBoxGroup.
    * @param boolean $value
    * @return \Kendo\UI\CheckBoxGroup
    */
    public function enabled($value) {
        return $this->setProperty('enabled', $value);
    }

    /**
    * The name attribute to be used for the checkbox inputs. If omitted, the id of the wrapper element will be used.
    * @param string $value
    * @return \Kendo\UI\CheckBoxGroup
    */
    public function inputName($value) {
        return $this->setProperty('inputName', $value);
    }

    /**
    * Adds CheckBoxGroupItem to the CheckBoxGroup.
    * @param \Kendo\UI\CheckBoxGroupItem|array,... $value one or more CheckBoxGroupItem to add.
    * @return \Kendo\UI\CheckBoxGroup
    */
    public function addItem($value) {
        return $this->add('items', func_get_args());
    }

    /**
    * The selected (checked) checkboxes values. Will also be used as a CheckBoxGroup widget value.
    * @param array $value
    * @return \Kendo\UI\CheckBoxGroup
    */
    public function value($value) {
        return $this->setProperty('value', $value);
    }

    /**
    * Specifies whether the checkbox inputs will be rendered one below the other ("vertical") or on the same line ("horizontal").
    * @param string $value
    * @return \Kendo\UI\CheckBoxGroup
    */
    public function layout($value) {
        return $this->setProperty('layout', $value);
    }

    /**
    * Specifies the label position according to its input for all items in the widget. Accepts "before" and "after".
    * @param string $value
    * @return \Kendo\UI\CheckBoxGroup
    */
    public function labelPosition($value) {
        return $this->setProperty('labelPosition', $value);
    }

    /**
    * Sets the change event of the CheckBoxGroup.
    * Fires when checking or unchecking a checkbox in the widget through user interaction.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\CheckBoxGroup
    */
    public function change($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('change', $value);
    }

    /**
    * Sets the focus event of the CheckBoxGroup.
    * Fires when a checkbox in the CheckBoxGroup is focused through user interaction.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\CheckBoxGroup
    */
    public function focus($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('focus', $value);
    }

    /**
    * Sets the select event of the CheckBoxGroup.
    * Fires when a checkbox input is clicked to be selected through user interaction. If prevented, the clicked input will not be checked/unchecked.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\CheckBoxGroup
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
