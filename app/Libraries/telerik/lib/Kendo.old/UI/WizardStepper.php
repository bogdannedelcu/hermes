<?php

namespace Kendo\UI;

class WizardStepper extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Specifies whether the Stepper instance will display the indicators of its steps.
    * @param boolean $value
    * @return \Kendo\UI\WizardStepper
    */
    public function indicator($value) {
        return $this->setProperty('indicator', $value);
    }

    /**
    * Specifies whether the Stepper instance will display the labels of its steps.
    * @param boolean $value
    * @return \Kendo\UI\WizardStepper
    */
    public function label($value) {
        return $this->setProperty('label', $value);
    }

    /**
    * Specifies whether the Stepper will allow non-linear navigation (selection of steps which are not immediately next to the current step).
    * @param boolean $value
    * @return \Kendo\UI\WizardStepper
    */
    public function linear($value) {
        return $this->setProperty('linear', $value);
    }

//<< Properties
}

?>
