<?php

namespace Kendo\UI;

class Stepper extends \Kendo\UI\Widget {
    public function name() {
        return 'Stepper';
    }
//>> Properties

    /**
    * Indicates whether the Steps in the Stepper will render their indicator element (the icon or number placed in a circle above the Step label).
    * @param boolean $value
    * @return \Kendo\UI\Stepper
    */
    public function indicator($value) {
        return $this->setProperty('indicator', $value);
    }

    /**
    * Indicates whether the Steps in the Stepper will render their label element (the text placed below the Step indicator circle).
    * @param boolean $value
    * @return \Kendo\UI\Stepper
    */
    public function label($value) {
        return $this->setProperty('label', $value);
    }

    /**
    * Indicates whether the Stepper will force the user to follow the Steps sequence or not. If set to "false" it will allow the user to select any step. If in its default state ("true") the user will be able to select the step immediately after the currently selected step or any previous step.
    * @param boolean $value
    * @return \Kendo\UI\Stepper
    */
    public function linear($value) {
        return $this->setProperty('linear', $value);
    }

    /**
    * Applicable for scenarios when keyboard is used for navigation. Indicates whether the selection will change upon focus change or it will require additional action (Enter or Spacebar key press) in order to select the focused step.
    * @param boolean $value
    * @return \Kendo\UI\Stepper
    */
    public function selectOnFocus($value) {
        return $this->setProperty('selectOnFocus', $value);
    }

    /**
    * Adds StepperStep to the Stepper.
    * @param \Kendo\UI\StepperStep|array,... $value one or more StepperStep to add.
    * @return \Kendo\UI\Stepper
    */
    public function addStep($value) {
        return $this->add('steps', func_get_args());
    }

    /**
    * The type of the Stepper orientation (horizontal or vertical).
    * @param string $value
    * @return \Kendo\UI\Stepper
    */
    public function orientation($value) {
        return $this->setProperty('orientation', $value);
    }

    /**
    * Sets the activate event of the Stepper.
    * Fires when a new Step has been selected upon user interaction.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Stepper
    */
    public function activate($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('activate', $value);
    }

    /**
    * Sets the select event of the Stepper.
    * Fires when the user clicks on a Step to select it.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Stepper
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
