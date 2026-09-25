<?php

namespace Kendo\UI;

class WizardValidateForms extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Indicates whether navigation to previous Step will trigger current Step Form validation. By default, Form validation is enabled for any step navigation.
    * @param boolean $value
    * @return \Kendo\UI\WizardValidateForms
    */
    public function validateOnPrevious($value) {
        return $this->setProperty('validateOnPrevious', $value);
    }

//<< Properties
}

?>
